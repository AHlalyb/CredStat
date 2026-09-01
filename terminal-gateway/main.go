package main

import (
	"encoding/json"
	"flag"
	"fmt"
	"io"
	"log"
	"net/http"
	"net/url"
	"os"
	"strings"
	"sync"
	"time"

	"github.com/gorilla/websocket"
)

// Config 网关配置
type Config struct {
	Listen         string `json:"listen"`          // 监听地址，如 :7822
	PHPBaseURL     string `json:"php_base_url"`    // PHP 会话接口地址，如 http://127.0.0.1/ws_session_api.php
	GatewayKey     string `json:"gateway_key"`     // 与 PHP 端一致的共享密钥
	AgentToken     string `json:"agent_token"`     // agent 跳板共享密钥（与 agent --token 一致，可为空）
	IdleTimeout    int    `json:"idle_timeout"`    // 设备会话空闲超时（秒），0=不限制
	ConnectTimeout int    `json:"connect_timeout"` // 连接设备超时（秒）
}

// Credential 从 PHP 换取的设备连接凭据
type Credential struct {
	IP       string          `json:"ip"`
	Port     string          `json:"port"`
	Username string          `json:"username"`
	Password string          `json:"password"`
	Protocol string          `json:"protocol"`
	Jump     *JumpCredential `json:"jump,omitempty"` // 跳板目标凭据，非空则经跳板接入
}

// JumpCredential 跳板目标凭据
//   - type=agent  : 经 agent TCP 隧道直连目标设备（无需登录跳板）
//   - type=ssh    : SSH 登录跳板机后 CLI 跳转（telnet/ssh 目标IP）
//   - type=telnet : Telnet 登录跳板机后 CLI 跳转（telnet/ssh 目标IP）
type JumpCredential struct {
	Name     string `json:"name"`
	Type     string `json:"type"`
	IP       string `json:"ip"`
	Port     string `json:"port"`
	Username string `json:"username"`
	Password string `json:"password"`
}

// wsMessage WebSocket 消息（JSON）
//   - client→server: {"type":"input","data":"..."} / {"type":"resize","cols":N,"rows":N}
//   - server→client: {"type":"output","data":"..."} / {"type":"error","msg":"..."} / {"type":"exit","msg":"..."}
type wsMessage struct {
	Type string `json:"type"`
	Data string `json:"data"`
	Msg  string `json:"msg"`
	Cols int    `json:"cols"`
	Rows int    `json:"rows"`
}

// wsConn 带写锁的 WebSocket 连接（gorilla/websocket 写操作不可并发）
type wsConn struct {
	mu   sync.Mutex
	conn *websocket.Conn
}

func (c *wsConn) writeJSON(v interface{}) error {
	c.mu.Lock()
	defer c.mu.Unlock()
	return c.conn.WriteJSON(v)
}

func (c *wsConn) readJSON(v interface{}) error {
	return c.conn.ReadJSON(v)
}

func (c *wsConn) close() { _ = c.conn.Close() }

var (
	config   Config
	upgrader = websocket.Upgrader{
		// 网关与页面不同端口，浏览器 Origin 为 http(s)://server，必须放行
		CheckOrigin: func(r *http.Request) bool { return true },
	}
)

// redeemCredential 使用一次性 ticket 向 PHP 内部接口换取设备连接凭据
func redeemCredential(ticket string) (*Credential, error) {
	u, err := url.Parse(config.PHPBaseURL)
	if err != nil {
		return nil, fmt.Errorf("PHP 接口地址无效: %v", err)
	}
	q := u.Query()
	q.Set("action", "redeem")
	q.Set("ticket", ticket)
	u.RawQuery = q.Encode()

	req, err := http.NewRequest("GET", u.String(), nil)
	if err != nil {
		return nil, err
	}
	req.Header.Set("X-Gateway-Key", config.GatewayKey)

	client := &http.Client{Timeout: 10 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		return nil, fmt.Errorf("向 PHP 换取凭据失败: %v", err)
	}
	defer resp.Body.Close()
	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("读取 PHP 响应失败: %v", err)
	}
	if len(body) == 0 {
		return nil, fmt.Errorf("PHP 响应为空")
	}

	var r struct {
		Success bool   `json:"success"`
		Message string `json:"message"`
		Credential
	}
	if err := json.Unmarshal(body, &r); err != nil {
		return nil, fmt.Errorf("PHP 响应解析失败: %v, body=%s", err, string(body))
	}
	if !r.Success {
		return nil, fmt.Errorf("换取凭据失败: %s", r.Message)
	}
	if r.IP == "" {
		return nil, fmt.Errorf("凭据不完整（IP 缺失）")
	}
	// 用户名允许为空（某些设备/Telnet 可能无需用户名）
	return &r.Credential, nil
}

// sendMsg 向浏览器发送一条消息
func sendMsg(c *wsConn, msgType, data string) {
	_ = c.writeJSON(wsMessage{Type: msgType, Data: data, Msg: data})
}

// idleTracker 空闲超时检测：任何读写都会刷新最后活跃时间，超时后回调
type idleTracker struct {
	mu          sync.Mutex
	lastActive  time.Time
	idleTimeout time.Duration
	onTimeout   func()
	stopCh      chan struct{}
}

func newIdleTracker(timeout time.Duration, onTimeout func()) *idleTracker {
	return &idleTracker{lastActive: time.Now(), idleTimeout: timeout, onTimeout: onTimeout, stopCh: make(chan struct{})}
}

func (t *idleTracker) touch() {
	t.mu.Lock()
	t.lastActive = time.Now()
	t.mu.Unlock()
}

func (t *idleTracker) start() {
	if t.idleTimeout <= 0 {
		return
	}
	go func() {
		ticker := time.NewTicker(30 * time.Second)
		defer ticker.Stop()
		for {
			select {
			case <-ticker.C:
				t.mu.Lock()
				idle := time.Since(t.lastActive)
				t.mu.Unlock()
				if idle > t.idleTimeout && t.onTimeout != nil {
					t.onTimeout()
					return
				}
			case <-t.stopCh:
				return
			}
		}
	}()
}

func (t *idleTracker) stop() { close(t.stopCh) }

// handleTerminal WebSocket 终端入口：ws://host:port/terminal?ticket=xxx&protocol=ssh|telnet
func handleTerminal(w http.ResponseWriter, r *http.Request) {
	rawConn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		log.Printf("websocket 升级失败: %v", err)
		return
	}
	conn := &wsConn{conn: rawConn}
	defer conn.close()

	ticket := r.URL.Query().Get("ticket")
	protocol := strings.ToLower(r.URL.Query().Get("protocol"))
	if ticket == "" || protocol == "" {
		sendMsg(conn, "error", "缺少 ticket 或 protocol 参数")
		return
	}
	if protocol != "ssh" && protocol != "telnet" {
		sendMsg(conn, "error", "不支持的协议: "+protocol)
		return
	}

	cred, err := redeemCredential(ticket)
	if err != nil {
		sendMsg(conn, "error", err.Error())
		return
	}
	log.Printf("终端会话已授权: %s %s:%s (%s)", protocol, cred.IP, cred.Port, cred.Username)

	// 建立设备会话（ssh.go / telnet.go）
	if protocol == "ssh" {
		err = startSSHSession(conn, cred)
	} else {
		err = startTelnetSession(conn, cred)
	}
	if err != nil {
		sendMsg(conn, "error", err.Error())
	}
	sendMsg(conn, "exit", "连接已关闭")
}

func main() {
	configPath := flag.String("config", "config.json", "配置文件路径")
	flag.Parse()

	data, err := os.ReadFile(*configPath)
	if err != nil {
		log.Fatalf("读取配置失败: %v", err)
	}
	if err := json.Unmarshal(data, &config); err != nil {
		log.Fatalf("解析配置失败: %v", err)
	}
	if config.Listen == "" {
		config.Listen = ":7822"
	}
	if config.PHPBaseURL == "" {
		log.Fatal("配置缺少 php_base_url")
	}
	if config.GatewayKey == "" || config.GatewayKey == "CHANGE_ME_GATEWAY_KEY" {
		log.Fatal("请先在 config.json 中配置 gateway_key")
	}
	if config.ConnectTimeout <= 0 {
		config.ConnectTimeout = 10
	}

	http.HandleFunc("/terminal", handleTerminal)
	http.HandleFunc("/healthz", func(w http.ResponseWriter, r *http.Request) {
		_, _ = w.Write([]byte("ok"))
	})

	log.Printf("终端网关已启动，监听 %s", config.Listen)
	log.Printf("PHP 会话接口: %s", config.PHPBaseURL)
	if err := http.ListenAndServe(config.Listen, nil); err != nil {
		log.Fatalf("服务启动失败: %v", err)
	}
}
