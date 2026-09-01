// 跳板 Agent：部署在能直接访问目标设备的网络中，为中心网关提供 TCP 隧道。
//
// 工作原理：
//   - Agent 监听一个 TCP 端口（默认 19878）
//   - 中心网关连接 Agent 后发送一行 JSON 连接请求：{"action":"connect","ip":"...","port":"...","token":"..."}
//   - Agent 校验 token 后代为连接目标 IP:端口，成功则回 {"ok":true}，随后裸 TCP 双向透传
//   - Telnet/SSH 的协议协商与登录均由中心网关完成，Agent 只负责纯字节转发
//
// 部署方式（命令行）：
//   agent.exe --listen :19878 --token 你的密钥
//
// 部署方式（Windows 服务，推荐）：
//   1. 用 install-service.bat 注册为系统服务
//   2. 本程序检测到以服务方式启动时，自动进入服务模式（svc.Run），
//      服务管理器可正常 start/stop/restart，无需额外工具。
package main

import (
	"bufio"
	"encoding/json"
	"flag"
	"fmt"
	"io"
	"log"
	"net"
	"os"
	"path/filepath"
	"sync"
	"time"

	"golang.org/x/sys/windows/svc"
)

type connectRequest struct {
	Action string `json:"action"`
	IP     string `json:"ip"`
	Port   string `json:"port"`
	Token  string `json:"token,omitempty"`
}

type connectResponse struct {
	OK    bool   `json:"ok"`
	Error string `json:"error,omitempty"`
}

func main() {
	listen := flag.String("listen", ":19878", "监听地址")
	token := flag.String("token", "", "共享密钥（与网关 config.json 的 agent_token 一致，为空则不校验）")
	flag.Parse()

	// 以 Windows 服务方式运行时进入服务模式
	if isWin, err := svc.IsWindowsService(); err == nil && isWin {
		runAsService(listen, token)
		return
	}

	// 普通命令行模式
	log.Println("Agent 以命令行模式运行")
	log.Println("提示: 若运行在 Windows CMD 中，请关闭「快速编辑模式」，否则点击窗口会导致程序暂停")
	serve(*listen, *token)
}

// ---------- Windows 服务模式 ----------

type agentService struct {
	listen *string
	token  *string
}

func runAsService(listen, token *string) {
	// 服务模式下日志写到 agent.exe 同目录的 agent.log，便于排障
	redirectLogToFile()
	log.Println("Agent 以 Windows 服务模式运行")

	// 服务名必须与 install-service.bat 注册的名称一致，SCM 才能正确派发
	const serviceName = "CredStatAgent"
	if err := svc.Run(serviceName, &agentService{listen: listen, token: token}); err != nil {
		log.Fatalf("服务运行失败: %v", err)
	}
}

// redirectLogToFile 将日志输出重定向到 agent.exe 同目录的 agent.log
func redirectLogToFile() {
	exe, err := os.Executable()
	if err != nil {
		return
	}
	f, err := os.OpenFile(filepath.Join(filepath.Dir(exe), "agent.log"),
		os.O_CREATE|os.O_APPEND|os.O_WRONLY, 0644)
	if err != nil {
		return
	}
	log.SetOutput(f)
}

// Execute 实现 svc.Handler 接口，供 SCM 控制服务启停
func (s *agentService) Execute(args []string, r <-chan svc.ChangeRequest, changes chan<- svc.Status) (bool, uint32) {
	const cmdsAccepted = svc.AcceptStop | svc.AcceptShutdown | svc.AcceptPauseAndContinue
	changes <- svc.Status{State: svc.StartPending}

	// 服务主体在后台运行；此处保持与 SCM 的握手
	errCh := make(chan error, 1)
	go func() {
		errCh <- serve(*s.listen, *s.token)
	}()

	changes <- svc.Status{State: svc.Running, Accepts: cmdsAccepted}

	for {
		select {
		case c := <-r:
			switch c.Cmd {
			case svc.Interrogate:
				changes <- c.CurrentStatus
			case svc.Stop, svc.Shutdown:
				log.Println("收到停止/关机请求，Agent 退出")
				return false, 0
			case svc.Pause:
				changes <- svc.Status{State: svc.Paused, Accepts: cmdsAccepted}
			case svc.Continue:
				changes <- svc.Status{State: svc.Running, Accepts: cmdsAccepted}
			}
		case err := <-errCh:
			if err != nil {
				log.Printf("服务主体异常退出: %v", err)
				return false, 1
			}
			return false, 0
		}
	}
}

// ---------- 核心逻辑 ----------

func serve(listen, token string) error {
	ln, err := net.Listen("tcp", listen)
	if err != nil {
		return fmt.Errorf("监听失败: %v", err)
	}
	authDesc := "未启用"
	if token != "" {
		authDesc = "已启用"
	}
	log.Printf("Agent 已启动，监听 %s，token 校验: %s", listen, authDesc)

	for {
		c, err := ln.Accept()
		if err != nil {
			log.Printf("接受连接失败: %v", err)
			continue
		}
		go handleConn(c, token)
	}
}

func handleConn(c net.Conn, token string) {
	defer c.Close()
	_ = c.SetDeadline(time.Now().Add(30 * time.Second))

	// 读取连接请求（一行 JSON）
	br := bufio.NewReader(c)
	line, err := br.ReadString('\n')
	if err != nil {
		return
	}
	var req connectRequest
	if err := json.Unmarshal([]byte(line), &req); err != nil {
		writeResp(c, connectResponse{OK: false, Error: "请求格式错误"})
		return
	}
	if token != "" && req.Token != token {
		writeResp(c, connectResponse{OK: false, Error: "token 校验失败"})
		return
	}
	if req.Action != "connect" || req.IP == "" || req.Port == "" {
		writeResp(c, connectResponse{OK: false, Error: "参数不完整"})
		return
	}

	// 代为连接目标设备
	target, err := net.DialTimeout("tcp", net.JoinHostPort(req.IP, req.Port), 10*time.Second)
	if err != nil {
		writeResp(c, connectResponse{OK: false, Error: "连接目标失败: " + err.Error()})
		return
	}
	defer target.Close()

	if err := writeResp(c, connectResponse{OK: true}); err != nil {
		return
	}
	_ = c.SetDeadline(time.Time{})
	log.Printf("隧道已建立: %s:%s（来自 %s）", req.IP, req.Port, c.RemoteAddr())

	// 双向透传：网关 ↔ 目标设备
	// 使用 sync.WaitGroup 确保两个方向都完成后再关闭连接
	var wg sync.WaitGroup
	wg.Add(2)

	// 方向1: 网关(c) → 目标设备(target)
	go func() {
		defer wg.Done()
		_, _ = io.Copy(target, br)
		// 通知对端不再发送数据（半关闭）
		if tc, ok := target.(*net.TCPConn); ok {
			_ = tc.CloseWrite()
		}
	}()

	// 方向2: 目标设备(target) → 网关(c)
	go func() {
		defer wg.Done()
		_, _ = io.Copy(c, target)
		// 通知对端不再发送数据（半关闭）
		if tc, ok := c.(*net.TCPConn); ok {
			_ = tc.CloseWrite()
		}
	}()

	wg.Wait()
	log.Printf("隧道已关闭: %s:%s", req.IP, req.Port)
}

func writeResp(c net.Conn, resp connectResponse) error {
	b, _ := json.Marshal(resp)
	_, err := fmt.Fprintf(c, "%s\n", b)
	return err
}
