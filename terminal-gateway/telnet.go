package main

import (
	"bufio"
	"fmt"
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

// 登录提示符匹配（末尾锚定，避免误触发）
var (
	telnetLoginPromptRe    = regexp.MustCompile(`(?i)(login|username|user name|user id|account|账号)\s*[:：]?\s*$`)
	telnetPasswordPromptRe = regexp.MustCompile(`(?i)(password|passwd|pass word|口令|密码)\s*[:：]?\s*$`)
)

// autoLogin 设备登录自动应答：设备提示 login/Password 时自动输入账号密码，
// 无需用户在终端里手动敲。登录完成（密码已发送）后不再自动应答，
// 避免密码错误导致设备重新提示时无限重试（此时可手动输入）。
type autoLogin struct {
	username string
	password string
	userSent bool // 已发送用户名
	passSent bool // 已发送密码
	pending  string // 最近输出缓冲，用于跨包匹配提示符
}

func newAutoLogin(cred *Credential) *autoLogin {
	return &autoLogin{username: cred.Username, password: cred.Password}
}

// feed 每次收到设备数据后调用；若识别到登录提示符则自动发送对应凭据。
// 发送 Enter 使用 \r（与 xterm 回车一致），兼容绝大多数网络设备。
func (a *autoLogin) feed(conn net.Conn, data []byte) {
	if a.userSent && a.passSent {
		return
	}
	a.pending += string(data)
	if len(a.pending) > 128 {
		a.pending = a.pending[len(a.pending)-128:]
	}
	lower := strings.ToLower(a.pending)
	if !a.userSent {
		if a.username != "" && telnetLoginPromptRe.MatchString(lower) {
			_, _ = fmt.Fprintf(conn, "%s\r", a.username)
			a.userSent = true
			a.pending = ""
		}
		return
	}
	if !a.passSent && a.password != "" && telnetPasswordPromptRe.MatchString(lower) {
		_, _ = fmt.Fprintf(conn, "%s\r", a.password)
		a.passSent = true
		a.pending = ""
	}
}

// startTelnetSession 建立 Telnet 会话并双向转发：设备输出→WS，WS 输入→设备。
// 与 go-telnet 库不同，这里手动解析 IAC 协商命令并正确响应，
// 否则部分设备（如锐捷）收不到 ECHO 协商确认会关闭回显，导致输入无回显。
func startTelnetSession(conn *wsConn, cred *Credential) error {
	addr := net.JoinHostPort(cred.IP, cred.Port)

	dialer := &net.Dialer{Timeout: time.Duration(config.ConnectTimeout) * time.Second}
	raw, err := dialer.Dial("tcp", addr)
	if err != nil {
		return fmt.Errorf("Telnet 连接失败(%s): %v", addr, err)
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

	// 自动登录应答：设备提示 login/Password 时自动输入账号密码
	auto := newAutoLogin(cred)

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
