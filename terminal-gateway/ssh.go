package main

import (
	"fmt"
	"io"
	"net"
	"strings"
	"time"

	"golang.org/x/crypto/ssh"
)

// startSSHSession 建立 SSH 会话并双向转发：设备输出→WS，WS 输入→设备。
// 跳板接入方式（依 jump.type）：
//   - agent  : 经 agent TCP 隧道直连目标设备，SSH 认证由 ssh 库直接完成
//   - ssh/telnet：先 SSH 登录跳板机，在 CLI 中注入跳转命令，自动完成目标设备嵌套登录
//   - 无跳板 : 中心直连目标设备
func startSSHSession(conn *wsConn, cred *Credential) error {
	var addr string
	var loginUser, loginPass string
	var viaAgent bool

	switch {
	case cred.Jump != nil && strings.EqualFold(cred.Jump.Type, "agent"):
		// agent TCP 隧道：ssh 库通过隧道直连目标设备
		viaAgent = true
		addr = net.JoinHostPort(cred.IP, cred.Port)
		loginUser, loginPass = cred.Username, cred.Password
		sendMsg(conn, "login_ok", "正在通过 Agent 跳板连接 "+cred.IP+" ...")
	case cred.Jump != nil:
		// CLI 跳板（ssh/telnet 类型）：先 SSH 登录跳板机
		addr = net.JoinHostPort(cred.Jump.IP, cred.Jump.Port)
		loginUser, loginPass = cred.Jump.Username, cred.Jump.Password
		sendMsg(conn, "login_ok", "正在登录跳板机 "+cred.Jump.IP+" ...")
	default:
		// 直连
		addr = net.JoinHostPort(cred.IP, cred.Port)
		loginUser, loginPass = cred.Username, cred.Password
	}

	user := loginUser
	if user == "" {
		user = "root" // SSH 默认回退用户名
	}
	clientConfig := &ssh.ClientConfig{
		User:            user,
		Auth:            []ssh.AuthMethod{ssh.Password(loginPass)},
		HostKeyCallback: ssh.InsecureIgnoreHostKey(),
		Timeout:         time.Duration(config.ConnectTimeout) * time.Second,
	}

	var client *ssh.Client
	if viaAgent {
		// agent 场景：TCP 连接经 agent 隧道建立，再在隧道上完成 SSH 握手
		tunnelConn, derr := dialViaAgent(cred.Jump, cred.IP, cred.Port)
		if derr != nil {
			return fmt.Errorf("SSH 连接失败(%s): %v", addr, derr)
		}
		sshConn, chans, reqs, serr := ssh.NewClientConn(tunnelConn, addr, clientConfig)
		if serr != nil {
			tunnelConn.Close()
			return fmt.Errorf("SSH 连接失败(%s): %v", addr, serr)
		}
		client = ssh.NewClient(sshConn, chans, reqs)
	} else {
		var err error
		client, err = ssh.Dial("tcp", addr, clientConfig)
		if err != nil {
			return fmt.Errorf("SSH 连接失败(%s): %v", addr, err)
		}
	}
	defer client.Close()

	session, err := client.NewSession()
	if err != nil {
		return fmt.Errorf("SSH 会话创建失败: %v", err)
	}
	defer session.Close()

	// 申请 PTY（终端类型、行列）
	cols, rows := 80, 24
	modes := ssh.TerminalModes{
		ssh.ECHO:          1,
		ssh.TTY_OP_ISPEED: 14400,
		ssh.TTY_OP_OSPEED: 14400,
	}
	if err := session.RequestPty("xterm-256color", rows, cols, modes); err != nil {
		return fmt.Errorf("请求 PTY 失败: %v", err)
	}

	stdin, err := session.StdinPipe()
	if err != nil {
		return err
	}
	stdout, err := session.StdoutPipe()
	if err != nil {
		return err
	}
	stderr, err := session.StderrPipe()
	if err != nil {
		return err
	}

	if err := session.Shell(); err != nil {
		return fmt.Errorf("启动 shell 失败: %v", err)
	}

	// 空闲超时
	it := newIdleTracker(time.Duration(config.IdleTimeout)*time.Second, func() {
		sendMsg(conn, "exit", "连接空闲超时，已断开")
		_ = session.Close()
		_ = client.Close()
	})
	it.start()
	defer it.stop()

	// CLI 跳板自动登录应答：检测跳板机提示符 → 发送跳转命令 → 目标设备嵌套登录。
	// 跳板机自身的 SSH 登录已由 ssh 库完成，跳过对应阶段。
	// agent 场景由 ssh 库直接认证目标设备，无需自动登录。
	var auto *autoLogin
	if cred.Jump != nil && !strings.EqualFold(cred.Jump.Type, "agent") {
		auto = newAutoLogin(cred, jumpLoginStageCount(cred))
	}

	// 设备输出（stdout + stderr）→ WS
	streams := io.MultiReader(stdout, stderr)
	outputDone := make(chan struct{})
	go func() {
		defer close(outputDone)
		buf := make([]byte, 4096)
		for {
			n, err := streams.Read(buf)
			if n > 0 {
				it.touch()
				if auto != nil {
					auto.feed(stdin, buf[:n]) // 自动填跳转命令与目标登录
				}
				if werr := conn.writeJSON(wsMessage{Type: "output", Data: string(buf[:n])}); werr != nil {
					return
				}
			}
			if err != nil {
				return
			}
		}
	}()

	// WS 输入 → 设备 stdin；resize → WindowChange
	go func() {
		defer func() {
			_ = session.Close()
			_ = client.Close()
		}()
		for {
			var msg wsMessage
			if err := conn.readJSON(&msg); err != nil {
				return
			}
			switch msg.Type {
			case "input":
				if msg.Data != "" {
					it.touch()
					if _, err := stdin.Write([]byte(msg.Data)); err != nil {
						return
					}
				}
			case "resize":
				if msg.Cols > 0 && msg.Rows > 0 {
					_ = session.WindowChange(msg.Rows, msg.Cols)
				}
			}
		}
	}()

	// 等待 shell 结束或连接关闭
	if err := session.Wait(); err != nil {
		if _, ok := err.(*ssh.ExitError); !ok {
			// 忽略正常的退出码，其他错误记录
			return fmt.Errorf("SSH 会话结束: %v", err)
		}
	}
	<-outputDone
	return nil
}
