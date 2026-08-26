# Web 终端网关部署指南

> 在浏览器中直接打开 SSH/Telnet 终端（xterm.js + Go 网关），替代本机 CRT/PuTTY 调起方案。
> 已通过真实设备联调验证（锐捷 S5300 交换机 SSH 登录/交互正常）。

---

## 1. 架构

```
浏览器 (Vue + xterm.js)
   │  WebSocket ws://服务器IP:7822/terminal?ticket=xxx&protocol=ssh|telnet
   ▼
Go 终端网关 (terminal-gateway.exe，独立进程，监听 7822)
   │  HTTP GET http://127.0.0.1/CredStat/ws_session_api.php?action=redeem&ticket=xxx
   │  携带 X-Gateway-Key 头（与 config.json 中 gateway_key 一致）
   ▼
PHP 会话接口 (ws_session_api.php)：校验用户权限 → 解密设备密码 → 一次性下发
   ▼
网络设备 (SSH:22 / Telnet:23)
```

**设计要点**：
- 设备密码**只在内网 PHP ↔ 网关之间传递，永不进入浏览器**
- ticket 一次性 + 60 秒有效期，即使泄露也无法复用
- 网关换取凭据必须携带共享密钥（`X-Gateway-Key`），未携带返回 403

---

## 2. 组件清单

| 组件 | 位置 | 说明 |
|---|---|---|
| Go 网关程序 | `terminal-gateway/terminal-gateway.exe` | 已编译完成，单文件免依赖 |
| 网关配置 | `terminal-gateway/config.json` | 监听端口、PHP 接口地址、密钥 |
| PHP 会话接口 | `ws_session_api.php`（项目根目录） | 已就位，随系统一起部署 |
| 前端终端弹窗 | `src/components/TerminalModal.vue` | 已集成到 `InfoQueryView.vue` |
| 前端依赖 | `@xterm/xterm`、`@xterm/addon-fit` | 已安装并构建进 `dist/` |

---

## 3. 服务器部署步骤

### 3.1 部署文件

将以下文件拷贝到服务器（建议与项目同目录）：

```
terminal-gateway/
  ├─ terminal-gateway.exe     ← 编译好的网关程序
  └─ config.json              ← 配置文件
```

### 3.2 修改配置（重要）

编辑 `terminal-gateway/config.json`：

```json
{
  "listen": ":7822",
  "php_base_url": "http://127.0.0.1/CredStat/ws_session_api.php",
  "gateway_key": "Gk7f9xQ2tR5vLm8nP3wZc4yB",
  "idle_timeout": 600,
  "connect_timeout": 10
}
```

| 字段 | 说明 |
|---|---|
| `listen` | 网关监听地址，`:7822` 表示所有网卡 7822 端口 |
| `php_base_url` | PHP 会话接口地址，**按实际部署 URL 调整**（若站点根目录直接指向项目，则去掉 `/CredStat`） |
| `gateway_key` | 共享密钥，**必须与 `ws_session_api.php` 中的 `$gatewayKey` 一致**；生产环境建议自行修改 |
| `idle_timeout` | 终端空闲多少秒自动断开（0 为不限制） |
| `connect_timeout` | 连接设备超时秒数 |

修改密钥后需同步修改 `ws_session_api.php` 第 30 行附近的 `$gatewayKey`。

### 3.3 防火墙放行

放行 TCP 7822 端口（以管理员运行 PowerShell）：

```powershell
New-NetFirewallRule -DisplayName "CredStat Web Terminal" -Direction Inbound -Protocol TCP -LocalPort 7822 -Action Allow
```

### 3.4 启动网关

```powershell
cd D:\...\terminal-gateway
.\terminal-gateway.exe
```

验证：

```powershell
Invoke-WebRequest http://127.0.0.1:7822/healthz
# 期望输出: ok
```

### 3.5 开机自启（计划任务）

```powershell
schtasks /Create /TN "CredStatTerminal" /TR "D:\...\terminal-gateway\terminal-gateway.exe" /SC ONSTART /RU SYSTEM /RL HIGHEST /F
```

> 也可改用 NSSM 注册为 Windows 服务，管理更完善。

---

## 4. 前端使用

远程下拉菜单改造后：

```
远程 ▾
  ├─ SSH     → Web 终端弹窗（浏览器内直接操作）
  ├─ Telnet  → Web 终端弹窗
  ├─ Web     → 打开网页
  └─ 本地终端(CRT/PuTTY) → 原有调起本机软件方式（保留）
```

终端弹窗功能：
- 实时交互、自动适应窗口大小（resize）
- `Ctrl+Shift+V` 粘贴（或底部按钮），工具栏复制按钮
- 顶部状态灯显示连接状态（黄=连接中 / 绿=已连接 / 灰=已断开 / 红=异常）
- 空闲超时自动断开，底部"断开连接"按钮手动断开

---

## 5. 安全说明

- 只有**有查询权限**的启用用户才能打开终端（`ws_session_api.php` 校验 `credstat_user_perm_query=1`）
- 凭据一次性下发，网关与 PHP 之间通过 `X-Gateway-Key` 认证
- 建议将 7822 端口仅对内网开放
- 如需操作审计，可在 `ws_session_api.php` 的 `create` 分支补充登录日志

---

## 6. 故障排查

| 现象 | 原因与解决 |
|---|---|
| 弹窗显示"换取凭据失败: forbidden" | 网关 `gateway_key` 与 `ws_session_api.php` 的 `$gatewayKey` 不一致 |
| 弹窗显示"ticket 无效或已过期" | `php_base_url` 不对或 PHP 接口不可达；检查配置和接口 URL |
| 弹窗显示"获取会话失败" | 用户无查询权限，或账号不存在 |
| 一直"正在建立连接..." | 7822 端口未放行，或网关未启动；浏览器 F12 查看 WS 连接错误 |
| 终端黑屏无输出 | 设备不可达、密码错误或协议与设备不符；检查 `net_dev_cred` 中协议/端口/密码配置 |
| SSH 连接失败提示 | 设备未开 SSH 或凭据错误；先在本机用 CRT/PuTTY 验证设备可登录 |
