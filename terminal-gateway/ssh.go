package main

import (
	"fmt"
	"io"
	"net"
	"time"

	"golang.org/x/crypto/ssh"
)

// startSSHSession 建立 SSH 会话并双向转发：设备输出→WS，WS 输入→设备
func startSSHSession(conn *wsConn, cred *Credential) error {
	addr := net.JoinHostPort(cred.IP, cred.Port)
	clientConfig := &ssh.ClientConfig{
		User:            cred.Username,
		Auth:            []ssh.AuthMethod{ssh.Password(cred.Password)},
		HostKeyCallback: ssh.InsecureIgnoreHostKey(),
		Timeout:         time.Duration(config.ConnectTimeout) * time.Second,
	}
	client, err := ssh.Dial("tcp", addr, clientConfig)
	if err != nil {
		return fmt.Errorf("SSH 连接失败(%s): %v", addr, err)
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
