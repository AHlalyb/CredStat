package main

import (
	"bufio"
	"fmt"
	"io"
	"net"
	"regexp"
	"strings"
	"time"
)

// Telnet 协议常量
const (
	telnetIAC  = 255 // Interpret As Command
	telnetDONT = 254
	telnetDO   = 253
	telnetWONT = 252
	telnetWILL = 251
	telnetSB   = 250 // Sub-negotiation Begin
	telnetSE   = 240 // Sub-negotiation End

	telnetOptECHO = 1 // ECHO 选项
	telnetOptSGA  = 3 // Suppress Go Ahead
)

// 登录提示符匹配（允许后面紧跟换行符或字符串结尾，避免 Telnet 流式数据分批导致匹配失败）
var (
	telnetLoginPromptRe    = regexp.MustCompile(`(?i)(login|username|user name|user id|user\s*[:：]|account|账号)\s*[:：]?\s*(?:[\r\n]+|$)`)
	telnetPasswordPromptRe = regexp.MustCompile(`(?i)(password|passwd|pass word|口令|密码)\s*[:：]?\s*(?:[\r\n]+|$)`)
	// 交换机用户态提示符：Switch>、Ruijie> 等（行末锚定）。需要 enable 进入特权模式
	switchUserPromptRe = regexp.MustCompile(`(?i)(?:^|[\r\n])\s*[a-z0-9_\-\/\.]{1,24}>\s*(?:[\r\n]+|$)`)
	// 交换机特权态提示符：Switch#、<Huawei>、[Huawei] 等，可直接执行跳转命令
	switchPrivPromptRe = regexp.MustCompile(`(?i)(?:^|[\r\n])\s*(?:[a-z0-9_\-\/\.]{1,24}#|<[a-z0-9_\-\/\.]{1,24}>|\[[a-z0-9_\-\/\.]{1,24}\])\s*(?:[\r\n]+|$)`)
	// SSH 跳转时的主机指纹确认提示
	sshConfirmRe = regexp.MustCompile(`(?i)(yes/no|continue connecting|are you sure.*host key|authenticity.*can't be established)`)
)

// autoLoginTimeout 自动登录总超时：超过该时间仍未完成登录则停止自动应答，
// 将控制权交给用户手动处理（如密码过期、额外验证码等）。
const autoLoginTimeout = 90 * time.Second

// loginStage 自动登录的一个阶段：匹配到指定提示符后发送内容
type loginStage struct {
	match *regexp.Regexp
	send  string // 匹配后发送的内容（发送时自动补 \r）
	desc  string
}

// autoLogin 设备登录自动应答状态机。支持多阶段：
// 跳板交换机登录 → 交换机提示符(发跳转命令) → SSH确认 → 目标设备登录。
// 每个阶段按顺序匹配，匹配即发送内容并进入下一阶段；未命中时继续等待。
type autoLogin struct {
	stages   []loginStage
	idx      int
	pending  string
	deadline time.Time
	active   bool
}

// newAutoLogin 构建自动登录状态机。
// skip 表示跳过前 N 个阶段（SSH 跳板场景下交换机登录已由 ssh 库完成，
// 需跳过交换机用户名/密码阶段，直接从"提示符→跳转命令"开始）。
func newAutoLogin(cred *Credential, skip int) *autoLogin {
	stages := buildAutoLoginStages(cred)
	if skip > len(stages) {
		skip = len(stages)
	}
	return &autoLogin{
		stages:   stages,
		idx:      skip,
		active:   skip < len(stages),
		deadline: time.Now().Add(autoLoginTimeout),
	}
}

// buildAutoLoginStages 按凭据构建自动登录阶段列表。
// agent 类型为 TCP 隧道直连（无 CLI 阶段），仅包含目标设备登录；
// ssh/telnet 类型包含跳板机登录 + enable + 跳转命令 + 目标设备登录。
func buildAutoLoginStages(cred *Credential) []loginStage {
	var stages []loginStage
	// CLI 跳板（ssh/telnet 类型）：跳板机登录 + 跳转命令
	if cred.Jump != nil && !strings.EqualFold(cred.Jump.Type, "agent") {
		j := cred.Jump
		if j.Username != "" {
			stages = append(stages, loginStage{telnetLoginPromptRe, j.Username, "跳板用户名"})
		}
		if j.Password != "" {
			stages = append(stages, loginStage{telnetPasswordPromptRe, j.Password, "跳板密码"})
		}
		// 用户态提示符（Switch> 等）→ 发送 enable 进入特权模式（锐捷/思科类交换机）
		stages = append(stages, loginStage{switchUserPromptRe, "enable", "进入特权模式"})
		// 特权态提示符（Switch#、<Huawei>、[Huawei] 等）→ 发送跳转命令
		stages = append(stages, loginStage{switchPrivPromptRe, buildJumpCommand(cred), "发送跳转命令"})
		// 目标为 SSH 时交换机可能询问是否信任主机指纹
		if strings.EqualFold(cred.Protocol, "ssh") {
			stages = append(stages, loginStage{sshConfirmRe, "yes", "确认SSH主机指纹"})
		}
	}
	// 目标设备登录
	if cred.Username != "" {
		stages = append(stages, loginStage{telnetLoginPromptRe, cred.Username, "目标用户名"})
	}
	if cred.Password != "" {
		stages = append(stages, loginStage{telnetPasswordPromptRe, cred.Password, "目标密码"})
	}
	return stages
}

// buildJumpCommand 生成在交换机 CLI 上执行的跳转命令
//   - telnet 目标：telnet <ip> 或 telnet <ip> <port>
//   - ssh 目标：ssh <ip> 或 ssh -p <port> <ip>
func buildJumpCommand(cred *Credential) string {
	ip := cred.IP
	if strings.Contains(ip, ":") { // IPv6
		ip = "[" + ip + "]"
	}
	switch strings.ToLower(cred.Protocol) {
	case "ssh":
		if cred.Port != "" && cred.Port != "22" {
			return "ssh -p " + cred.Port + " " + ip
		}
		return "ssh " + ip
	default: // telnet
		if cred.Port != "" && cred.Port != "23" {
			return "telnet " + ip + " " + cred.Port
		}
		return "telnet " + ip
	}
}

// jumpLoginStageCount 跳板交换机登录所需的阶段数（SSH 跳板时由 ssh 库跳过）
func jumpLoginStageCount(cred *Credential) int {
	if cred.Jump == nil {
		return 0
	}
	n := 0
	if cred.Jump.Username != "" {
		n++
	}
	if cred.Jump.Password != "" {
		n++
	}
	return n
}

// feed 每次收到设备数据后调用；按阶段匹配提示符并自动发送对应内容。
// 发送 Enter 使用 \r（与 xterm 回车一致），兼容绝大多数网络设备。
// w 为发送通道（telnet 为原始连接，SSH 跳板为 stdin）。
func (a *autoLogin) feed(w io.Writer, data []byte) {
	if !a.active {
		return
	}
	if time.Now().After(a.deadline) {
		a.active = false
		return
	}
	a.pending += string(data)
	if len(a.pending) > 512 {
		a.pending = a.pending[len(a.pending)-512:]
	}
	lower := strings.ToLower(a.pending)
	for i := a.idx; i < len(a.stages); i++ {
		st := a.stages[i]
		if st.match.MatchString(lower) {
			a.idx = i + 1
			a.pending = ""
			if st.send != "" {
				_, _ = fmt.Fprintf(w, "%s\r", st.send)
				fmt.Printf("[autoLogin] %s → %s\n", st.desc, st.send)
			}
			if a.idx >= len(a.stages) {
				a.active = false
			}
			return
		}
	}
}

// startTelnetSession 建立 Telnet 会话并双向转发：设备输出→WS，WS 输入→设备。
// 与 go-telnet 库不同，这里手动解析 IAC 协商命令并正确响应，
// 否则部分设备（如锐捷）收不到 ECHO 协商确认会关闭回显，导致输入无回显。
// 跳板接入方式（依 jump.type）：
//   - agent  : 经 agent TCP 隧道直连目标设备
//   - ssh/telnet：先连接跳板机，自动登录后在 CLI 中注入跳转命令
//   - 无跳板 : 中心直连目标设备
func startTelnetSession(conn *wsConn, cred *Credential) error {
	var addr string
	var raw net.Conn
	var err error

	dialer := &net.Dialer{Timeout: time.Duration(config.ConnectTimeout) * time.Second}

	switch {
	case cred.Jump != nil && strings.EqualFold(cred.Jump.Type, "agent"):
		// agent TCP 隧道：直连目标设备
		sendMsg(conn, "login_ok", "正在通过 Agent 跳板连接 "+cred.IP+" ...")
		raw, err = dialViaAgent(cred.Jump, cred.IP, cred.Port)
		if err != nil {
			return fmt.Errorf("Telnet 连接失败(agent): %v", err)
		}
	case cred.Jump != nil:
		// CLI 跳板（ssh/telnet 类型）：先连接跳板机
		sendMsg(conn, "login_ok", "正在登录跳板机 "+cred.Jump.IP+" ...")
		addr = net.JoinHostPort(cred.Jump.IP, cred.Jump.Port)
		raw, err = dialer.Dial("tcp", addr)
		if err != nil {
			return fmt.Errorf("Telnet 连接跳板失败(%s): %v", addr, err)
		}
	default:
		// 直连
		addr = net.JoinHostPort(cred.IP, cred.Port)
		raw, err = dialer.Dial("tcp", addr)
		if err != nil {
			return fmt.Errorf("Telnet 连接失败(%s): %v", addr, err)
		}
	}
	defer raw.Close()

	reader := bufio.NewReader(raw)

	// 空闲超时
	it := newIdleTracker(time.Duration(config.IdleTimeout)*time.Second, func() {
		sendMsg(conn, "exit", "连接空闲超时，已断开")
		_ = raw.Close()
	})
	it.start()
	defer it.stop()

	// 自动登录应答：跳板机登录 → enable → 跳转命令 → 目标设备登录
	auto := newAutoLogin(cred, 0)

	// 设备输出 → WS（解析并响应 IAC 协商，转发纯数据）
	outputDone := make(chan struct{})
	go func() {
		defer close(outputDone)
		for {
			data, rerr := readTelnetData(reader, raw)
			if len(data) > 0 {
				it.touch()
				auto.feed(raw, data) // 自动填写账号密码
				if werr := conn.writeJSON(wsMessage{Type: "output", Data: string(data)}); werr != nil {
					return
				}
			}
			if rerr != nil {
				return
			}
		}
	}()

	// WS 输入 → 设备（Telnet 无 resize 概念）
	go func() {
		defer raw.Close()
		for {
			var msg wsMessage
			if err := conn.readJSON(&msg); err != nil {
				return
			}
			if msg.Type == "input" && msg.Data != "" {
				it.touch()
				if _, err := raw.Write([]byte(msg.Data)); err != nil {
					return
				}
			}
		}
	}()

	// 等待设备输出结束（连接关闭）
	<-outputDone
	return nil
}

// readTelnetData 读取一段数据：解析并响应 IAC 协商命令，返回剥离命令后的纯数据字节。
// 循环读取直到当前 bufio 缓冲耗尽，避免逐字节返回。
func readTelnetData(reader *bufio.Reader, conn net.Conn) ([]byte, error) {
	out := make([]byte, 0, 256)
	for {
		b, err := reader.ReadByte()
		if err != nil {
			return out, err
		}
		if b == telnetIAC {
			cmd, err := reader.ReadByte()
			if err != nil {
				return out, err
			}
			switch cmd {
			case telnetWILL, telnetWONT, telnetDO, telnetDONT:
				opt, err := reader.ReadByte()
				if err != nil {
					return out, err
				}
				respondTelnetOption(conn, cmd, opt)
			case telnetSB:
				// 跳过子协商内容直到 IAC SE
				for {
					b2, err := reader.ReadByte()
					if err != nil {
						return out, err
					}
					if b2 == telnetIAC {
						b3, err := reader.ReadByte()
						if err != nil {
							return out, err
						}
						if b3 == telnetSE {
							break
						}
					}
				}
			case telnetIAC:
				// 数据中转义的 255（IAC IAC）
				out = append(out, telnetIAC)
			default:
				// 忽略其它命令（NOP、GA、IP、AYT 等）
			}
		} else {
			out = append(out, b)
		}
		// 当前缓冲耗尽则返回本批数据
		if reader.Buffered() == 0 {
			break
		}
	}
	return out, nil
}

// respondTelnetOption 对设备发来的选项协商做出响应。
// 核心策略：接受服务器回显（WILL ECHO → DO ECHO），
// 拒绝本地回显（DO ECHO → WONT ECHO），避免登录密码被本地回显泄露。
func respondTelnetOption(conn net.Conn, cmd, opt byte) {
	switch cmd {
	case telnetWILL:
		// 设备将启用某选项
		switch opt {
		case telnetOptECHO, telnetOptSGA:
			writeTelnetCmd(conn, telnetDO, opt)
		default:
			writeTelnetCmd(conn, telnetDONT, opt)
		}
	case telnetWONT:
		// 设备将关闭某选项，无需回应
	case telnetDO:
		// 设备要求客户端启用某选项
		switch opt {
		case telnetOptECHO:
			// 拒绝本地回显，由服务器回显
			writeTelnetCmd(conn, telnetWONT, opt)
		case telnetOptSGA:
			writeTelnetCmd(conn, telnetWILL, opt)
		default:
			writeTelnetCmd(conn, telnetWONT, opt)
		}
	case telnetDONT:
		// 设备要求客户端关闭某选项，无需回应
	}
}

func writeTelnetCmd(conn net.Conn, cmd, opt byte) {
	_, _ = conn.Write([]byte{telnetIAC, cmd, opt})
}
