# 内网 Windows 环境 Go 安装指南

> 适用场景：生产服务器处于**内网/隔离网**（无法直接访问 `go.dev`、`proxy.golang.org` 等外网资源），需要安装 Go 并编译本项目终端网关。
> 已验证环境：`go1.26.5 windows/amd64`（本指南兼容 Go 1.20+ 全系）。

---

## 目录

- [1. 总体思路](#1-总体思路)
- [2. 下载 Go 安装包](#2-下载-go-安装包)
- [3. 方式一：ZIP 免安装部署（推荐）](#3-方式一zip-免安装部署推荐)
- [4. 方式二：MSI 图形化安装](#4-方式二msi-图形化安装)
- [5. 配置环境变量](#5-配置环境变量)
- [6. 验证安装](#6-验证安装)
- [7. 内网依赖模块处理（关键）](#7-内网依赖模块处理关键)
- [8. 编译本项目终端网关](#8-编译本项目终端网关)
- [9. 常见问题排查](#9-常见问题排查)

---

## 1. 总体思路

内网机器装 Go 的本质就三步：

1. **下载**：在一台能上外网的机器上下载 Go 安装包（约 80MB）
2. **拷贝**：通过 U 盘 / 内网共享 / 审批通道 拷贝到内网服务器
3. **配置**：解压 + 设置环境变量 + 处理内网依赖源

关键决策：**尽量选 ZIP 免安装版**，不用 MSI，避免安装器联网检查和权限问题。

---

## 2. 下载 Go 安装包

### 2.1 官方下载地址（有网机器操作）

| 类型 | 地址 |
|---|---|
| 官方下载页 | `https://go.dev/dl/` |
| Windows 免安装 ZIP | `https://go.dev/dl/go1.26.5.windows-amd64.zip` |
| Windows MSI 安装包 | `https://go.dev/dl/go1.26.5.windows-amd64.msi` |

国内镜像（下载更快，版本可能稍旧）：

- 腾讯镜像：`https://mirrors.cloud.tencent.com/golang/`
- 阿里镜像：`https://mirrors.aliyun.com/golang/`
- 华为镜像：`https://repo.huaweicloud.com/golang/`

### 2.2 确认服务器架构

内网服务器执行以下命令确认位数（本项目服务器为 64 位 Windows）：

```cmd
echo %PROCESSOR_ARCHITECTURE%
```

- 输出 `AMD64` → 下载 `windows-amd64` 包
- 输出 `ARM64` → 下载 `windows-arm64` 包

---

## 3. 方式一：ZIP 免安装部署（推荐）

### 3.1 解压

将 `go1.26.5.windows-amd64.zip` 拷贝到服务器后，**务必解压到不含中文/空格的路径**，例如：

```
D:\Programs\go          ← 推荐（不含中文）
C:\go                   ← 也可以
```

解压后目录结构如下（确认 `bin` 目录存在）：

```
D:\Programs\go\
  ├─ bin\        ← 含 go.exe、gofmt.exe
  ├─ pkg\
  ├─ src\
  └─ VERSION
```

### 3.2 设置环境变量

```powershell
# 以管理员身份运行 PowerShell，执行：

# 1. GOROOT 指向 Go 安装目录
[Environment]::SetEnvironmentVariable("GOROOT", "D:\Programs\go", "Machine")

# 2. GOPATH 指向工作目录（存放编译产物和模块缓存）
[Environment]::SetEnvironmentVariable("GOPATH", "D:\go", "Machine")

# 3. PATH 追加 go.exe 所在目录
$path = [Environment]::GetEnvironmentVariable("Path", "Machine")
[Environment]::SetEnvironmentVariable("Path", $path + ";D:\Programs\go\bin", "Machine")
```

> 若不想改系统环境变量，也可在**当前会话**临时设置（重启失效）：
> ```cmd
> set GOROOT=D:\Programs\go
> set GOPATH=D:\go
> set PATH=%PATH%;D:\Programs\go\bin
> ```

---

## 4. 方式二：MSI 图形化安装

适合**半内网**（服务器能连外网或可联网安装）场景：

1. 双击 `go1.26.5.windows-amd64.msi`
2. 一路 `Next`，默认安装到 `C:\Program Files\Go`
3. MSI 会自动配置 `GOROOT` 和 `PATH`

> ⚠️ MSI 缺点：部分内网环境安装器会联网检查，且无法自定义 GOPATH 默认值。能走 ZIP 就走 ZIP。

---

## 5. 配置环境变量

### 5.1 核心变量一览

| 变量 | 值示例 | 说明 |
|---|---|---|
| `GOROOT` | `D:\Programs\go` | Go 安装目录，**必须** |
| `GOPATH` | `D:\go` | 工作目录（模块缓存、编译产物） |
| `GOBIN` | `D:\go\bin` | 可选，`go install` 产物目录 |
| `GOPROXY` | 见下文 | 模块下载代理地址，**内网关键** |
| `GOFLAGS` | `-mod=mod` | 可选，自动更新 go.mod |
| `CGO_ENABLED` | `0` | 编译纯静态二进制时建议设置 |

### 5.2 查看当前配置

```cmd
go env GOROOT GOPATH GOPROXY
```

---

## 6. 验证安装

```cmd
go version
```

期望输出：

```
go version go1.26.5 windows/amd64
```

再验证环境变量生效：

```cmd
go env GOROOT
go env GOPATH
go env GOPROXY
```

---

## 7. 内网依赖模块处理（关键）

Go 项目需要下载第三方依赖（本项目用到 `golang.org/x/crypto/ssh`、`github.com/gorilla/websocket` 等）。内网环境按以下方案三选一。

### 方案 A：使用内网 GOPROXY（推荐，长期好用）

如果内网有 **Nexus / Artifactory / Athens** 等制品库，配置代理地址：

```cmd
go env -w GOPROXY=http://nexus.example.com/repository/go-proxy/
go env -w GOSUMDB=off
```

> `GOSUMDB=off` 是内网代理必须的，否则校验和数据库 `sum.golang.org` 无法访问会报错。

### 方案 B：有网机器拉取 + vendor 拷贝（最简单）

适合一次性把项目源码拷进内网编译的场景：

```cmd
:: 在有网机器上，进入项目目录
go mod tidy
go mod vendor        :: 把依赖全部复制到项目 vendor/ 目录

:: 将整个项目（含 vendor/）拷入内网
:: 内网机器上编译：
go build -mod=vendor -o gateway.exe .
```

> `vendor` 方式编译时**完全不联网**，是内网最稳的方案。

### 方案 C：直接拷贝模块缓存

在有网机器上执行：

```cmd
go mod download
:: 将 %GOPATH%\pkg\mod 整个目录拷贝到内网相同位置
```

---

## 8. 编译本项目终端网关

进入网关源码目录（假设为 `D:\code\terminal-gateway`），执行：

```cmd
:: 内网环境建议加 -mod=vendor（配合方案 B）
set CGO_ENABLED=0
go build -trimpath -ldflags "-s -w" -o terminal-gateway.exe .
```

| 参数 | 说明 |
|---|---|
| `CGO_ENABLED=0` | 纯静态编译，目标机器无需任何运行库 |
| `-trimpath` | 去掉编译路径信息，减小体积 |
| `-ldflags "-s -w"` | 去掉符号表，进一步减小体积 |

编译产物 `terminal-gateway.exe` 为**单文件**，拷贝到任意 Windows 服务器即可运行，无需安装 Go。

---

## 9. 常见问题排查

| 现象 | 原因 | 解决 |
|---|---|---|
| `'go' 不是内部或外部命令` | PATH 未配置 | 检查 `%PATH%` 是否含 `go\bin`；重开终端 |
| `go version` 输出旧版本 | 多个 Go 残留 | 删除旧目录，检查 PATH 顺序 |
| `dial tcp: lookup proxy.golang.org: no such host` | 内网无法访问默认代理 | 按第 7 节配置内网代理或 vendor |
| `verifying module: checksum mismatch` / `GOSUMDB ... refused` | 内网无法访问 sum.golang.org | `go env -w GOSUMDB=off` |
| `zip: not a valid zip file` | 下载包损坏 | 重新下载并校验 |
| 解压报"路径过长" | 解压到中文/过长路径 | 改用 `D:\Programs\go` 等短路径 |
| 编译报 `CGO_ENABLED` 相关错误 | 依赖 CGO 库 | 本项目为纯 Go 库，确认 `CGO_ENABLED=0` |
| 端口被占用 | 网关端口冲突 | 检查 `netstat -ano \| findstr 7822` |

---

## 附录：本项目 Go 依赖清单（预计）

终端网关预计使用以下模块：

| 模块 | 用途 |
|---|---|
| `golang.org/x/crypto/ssh` | SSH 客户端连接设备 |
| `github.com/gorilla/websocket` | 前端 WebSocket 通信 |
| `github.com/reiver/go-telnet` | Telnet 客户端连接设备 |

> 具体依赖以网关源码 `go.mod` 为准，内网部署时按第 7 节处理即可。
