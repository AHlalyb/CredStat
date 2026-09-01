package main

import (
	"bufio"
	"encoding/json"
	"fmt"
	"net"
	"time"
)

// agentConnectRequest 中心网关 → agent 的连接请求
type agentConnectRequest struct {
	Action string `json:"action"`
	IP     string `json:"ip"`
	Port   string `json:"port"`
	Token  string `json:"token,omitempty"` // 与 agent --token 一致的共享密钥
}

// agentConnectResponse agent → 中心网关 的响应
type agentConnectResponse struct {
	OK    bool   `json:"ok"`
	Error string `json:"error"`
}

// dialViaAgent 通过 agent TCP 隧道建立到目标设备的连接。
// 中心网关连接 agent 后发送连接请求，agent 代为连接目标 IP:端口，
// 之后裸 TCP 双向透传；telnet/ssh 的协议协商与登录均由中心网关完成，
// 因此 agent 程序只需要实现纯 TCP 转发。
func dialViaAgent(jump *JumpCredential, targetIP, targetPort string) (net.Conn, error) {
	addr := net.JoinHostPort(jump.IP, jump.Port)
	timeout := time.Duration(config.ConnectTimeout) * time.Second
	conn, err := net.DialTimeout("tcp", addr, timeout)
	if err != nil {
		return nil, fmt.Errorf("连接Agent失败(%s): %v", addr, err)
	}

	// 发送连接请求（请求行协议：JSON 一行 + \n）
	// token 优先使用跳板目标凭据中自定义的共享密钥，未配置则回退到全局 config.AgentToken
	token := jump.Password
	if token == "" {
		token = config.AgentToken
	}
	req := agentConnectRequest{Action: "connect", IP: targetIP, Port: targetPort, Token: token}
	reqData, _ := json.Marshal(req)
	_ = conn.SetDeadline(time.Now().Add(timeout))
	if _, err := fmt.Fprintf(conn, "%s\n", reqData); err != nil {
		conn.Close()
		return nil, fmt.Errorf("发送Agent请求失败: %v", err)
	}

	// 读取响应行
	line, err := bufio.NewReader(conn).ReadString('\n')
	if err != nil {
		conn.Close()
		return nil, fmt.Errorf("读取Agent响应失败: %v", err)
	}
	var resp agentConnectResponse
	if err := json.Unmarshal([]byte(line), &resp); err != nil {
		conn.Close()
		return nil, fmt.Errorf("Agent响应解析失败: %v", err)
	}
	if !resp.OK {
		conn.Close()
		return nil, fmt.Errorf("Agent连接目标失败: %s", resp.Error)
	}

	_ = conn.SetDeadline(time.Time{}) // 清除超时，进入双向透传
	return conn, nil
}
