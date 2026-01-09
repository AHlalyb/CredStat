# 系统内网Windows环境搭建流程

## 1. 系统环境准备

### 1.1 硬件配置要求
| 组件 | 最低配置 | 推荐配置 |
|------|----------|----------|
| CPU | 4核处理器 | 8核处理器 |
| 内存 | 8GB RAM | 16GB RAM |
| 存储 | 100GB 可用空间 | 200GB 可用空间 |
| 网络 | 100Mbps 内网连接 | 1Gbps 内网连接 |

### 1.2 操作系统要求
| 操作系统版本 | 支持情况 |
|--------------|----------|
| Windows Server 2016 | ✅ 支持 |
| Windows Server 2019 | ✅ 支持 |
| Windows Server 2022 | ✅ 支持 |
| Windows 10 (64位) | ✅ 支持 |
| Windows 11 (64位) | ✅ 支持 |

### 1.3 系统依赖要求
| 依赖名称 | 版本要求 | 用途 |
|----------|----------|------|
| Node.js | 16.x 或 18.x | 前端开发和构建环境 |
| PHP | 7.4.x 或 8.0.x | 后端脚本执行环境 |
| MySQL | 5.7.x 或 8.0.x | 数据库服务 |
| Nginx/Apache | 最新稳定版 | Web服务器 |

## 2. 基础服务搭建指南

### 2.1 Node.js 安装

#### 2.1.1 下载渠道
- 内网资源链接：`\\内网服务器\软件库\Node.js\node-v18.18.0-x64.msi`
- 离线安装包获取：联系系统管理员获取

#### 2.1.2 安装步骤
1. 双击下载的 `node-v18.18.0-x64.msi` 文件
2. 按照安装向导提示进行安装，选择默认安装路径
3. 在"Custom Setup"页面，确保勾选"Add to PATH"选项
4. 点击"Install"开始安装
5. 安装完成后，点击"Finish"

#### 2.1.3 验证方式
打开命令提示符（CMD），执行以下命令：
```bash
node -v
npm -v
```
预期输出：
```
v18.18.0
9.8.1
```

### 2.2 PHP 安装

#### 2.2.1 下载渠道
- 内网资源链接：`\\内网服务器\软件库\PHP\php-7.4.33-Win32-vc15-x64.zip`
- 离线安装包获取：联系系统管理员获取

#### 2.2.2 安装步骤
1. 解压下载的 `php-7.4.33-Win32-vc15-x64.zip` 到 `C:\php` 目录
2. 复制 `php.ini-development` 文件并重命名为 `php.ini`
3. 编辑 `php.ini` 文件，修改以下配置：
   ```ini
   extension_dir = "ext"
   extension=mysqli
   extension=pdo_mysql
   upload_max_filesize = 20M
   post_max_size = 20M
   max_execution_time = 300
   ```
4. 将 `C:\php` 添加到系统环境变量 PATH 中

#### 2.2.3 验证方式
打开命令提示符（CMD），执行以下命令：
```bash
php -v
```
预期输出：
```
PHP 7.4.33 (cli) (built: Aug  1 2023 11:47:35) ( NTS )
Copyright (c) The PHP Group
Zend Engine v3.4.0, Copyright (c) Zend Technologies
```

### 2.3 MySQL 安装

#### 2.3.1 下载渠道
- 内网资源链接：`\\内网服务器\软件库\MySQL\mysql-8.0.33-winx64.zip`
- 离线安装包获取：联系系统管理员获取

#### 2.3.2 安装步骤
1. 解压下载的 `mysql-8.0.33-winx64.zip` 到 `C:\mysql` 目录
2. 在 `C:\mysql` 目录下创建 `my.ini` 文件，内容如下：
   ```ini
   [mysqld]
   basedir=C:\mysql
   datadir=C:\mysql\data
   port=3306
   max_connections=200
   character-set-server=utf8mb4
   default-storage-engine=INNODB
   sql_mode=NO_ENGINE_SUBSTITUTION,STRICT_TRANS_TABLES
   [mysql]
   default-character-set=utf8mb4
   ```
3. 初始化MySQL：
   ```bash
   cd C:\mysql\bin
   mysqld --initialize-insecure
   ```
4. 安装MySQL服务：
   ```bash
   mysqld --install
   ```
5. 启动MySQL服务：
   ```bash
   net start mysql
   ```
6. 设置MySQL root密码：
   ```bash
   mysqladmin -u root password "YourRootPassword"
   ```

#### 2.3.3 验证方式
连接MySQL数据库：
```bash
mysql -u root -p
```
输入密码后，预期输出MySQL命令行提示符：
```
mysql>
```

### 2.4 Nginx 安装

#### 2.4.1 下载渠道
- 内网资源链接：`\\内网服务器\软件库\Nginx\nginx-1.24.0.zip`
- 离线安装包获取：联系系统管理员获取

#### 2.4.2 安装步骤
1. 解压下载的 `nginx-1.24.0.zip` 到 `C:\nginx` 目录
2. 编辑 `C:\nginx\conf\nginx.conf` 文件，修改以下配置：
   ```nginx
   server {
       listen       80;
       server_name  localhost;
       root   "C:\phpstudy_pro\WWW\CredStat\dist";
       index  index.html index.htm index.php;
       
       location / {
           try_files $uri $uri/ /index.html;
       }
       
       location ~ \.php$ {
           root           "C:\phpstudy_pro\WWW\CredStat";
           fastcgi_pass   127.0.0.1:9000;
           fastcgi_index  index.php;
           fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
           include        fastcgi_params;
       }
   }
   ```
3. 安装并启动Nginx服务：
   ```bash
   cd C:\nginx
   start nginx
   ```

#### 2.4.3 验证方式
打开浏览器访问 `http://localhost`，预期显示Nginx欢迎页面或系统首页

## 3. 系统部署流程

### 3.1 项目获取

#### 3.1.1 下载渠道
- 内网Git仓库：`http://内网Git服务器/CredStat.git`
- 离线安装包：`\\内网服务器\软件库\CredStat\CredStat-v1.0.0.zip`

#### 3.1.2 项目获取步骤
1. 使用Git克隆项目（推荐）：
   ```bash
   cd C:\phpstudy_pro\WWW
   git clone http://内网Git服务器/CredStat.git
   ```
2. 或解压离线安装包到 `C:\phpstudy_pro\WWW\CredStat` 目录

### 3.2 前端依赖安装与构建

#### 3.2.1 安装依赖
```bash
cd C:\phpstudy_pro\WWW\CredStat
npm install
```

#### 3.2.2 构建项目
```bash
npm run build
```
构建成功后，会在项目根目录生成 `dist` 目录

### 3.3 后端配置

#### 3.3.1 数据库配置
1. 创建数据库：
   ```sql
   CREATE DATABASE credstat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. 配置数据库连接（如需要）：
   - 检查并修改 `save_phy_server.php` 等PHP文件中的数据库连接配置

#### 3.3.2 PHP配置
- 确保PHP已启用 `mysqli` 和 `pdo_mysql` 扩展
- 确保 `upload_max_filesize` 和 `post_max_size` 配置足够大（建议20M以上）

### 3.4 服务启动

#### 3.4.1 启动PHP-FPM
如果使用Nginx+PHP-FPM模式：
```bash
# 假设PHP-FPM已安装
cd C:\php
php-cgi.exe -b 127.0.0.1:9000 -c php.ini
```

#### 3.4.2 启动Nginx
```bash
cd C:\nginx
start nginx
```

#### 3.4.3 启动开发服务器（可选）
```bash
cd C:\phpstudy_pro\WWW\CredStat
npm run dev
```

## 4. 常见问题处理

### 4.1 Node.js 安装问题

#### 4.1.1 问题：安装完成后，`node -v` 命令无法识别
**解决方案**：
1. 检查系统环境变量 PATH 中是否包含 Node.js 安装路径
2. 重新启动命令提示符或计算机

#### 4.1.2 问题：npm install 安装依赖失败
**解决方案**：
1. 检查网络连接是否正常
2. 使用内网npm镜像：
   ```bash
   npm config set registry http://内网npm镜像地址
   ```
3. 使用离线依赖包：联系系统管理员获取 `node_modules` 离线包

### 4.2 MySQL 安装问题

#### 4.2.1 问题：MySQL服务启动失败
**解决方案**：
1. 检查 `my.ini` 配置文件中的路径是否正确
2. 检查端口3306是否被占用
3. 查看错误日志 `C:\mysql\data\*.err`

#### 4.2.2 问题：无法连接到MySQL服务器
**解决方案**：
1. 检查MySQL服务是否正在运行
2. 检查防火墙设置，确保3306端口已开放
3. 检查用户名和密码是否正确

### 4.3 Nginx 安装问题

#### 4.3.1 问题：Nginx无法启动
**解决方案**：
1. 检查端口80是否被占用
2. 检查 `nginx.conf` 配置文件语法是否正确：
   ```bash
   cd C:\nginx
   nginx -t
   ```
3. 查看错误日志 `C:\nginx\logs\error.log`

#### 4.3.2 问题：PHP文件无法解析
**解决方案**：
1. 检查Nginx配置中是否正确配置了PHP解析
2. 检查PHP-FPM是否正在运行

### 4.4 系统运行问题

#### 4.4.1 问题：上传图片失败
**解决方案**：
1. 检查PHP配置中的 `upload_max_filesize` 和 `post_max_size` 是否足够大
2. 检查服务器磁盘空间是否充足
3. 检查文件上传目录权限

#### 4.4.2 问题：页面显示空白
**解决方案**：
1. 检查浏览器控制台是否有错误信息
2. 检查Nginx配置中的 `root` 路径是否正确
3. 检查 `dist` 目录是否存在且包含正确的文件

## 5. 环境验证

### 5.1 服务状态验证

#### 5.1.1 检查Node.js
```bash
node -v
npm -v
```
预期输出Node.js和npm版本信息

#### 5.1.2 检查PHP
```bash
php -v
```
预期输出PHP版本信息

#### 5.1.3 检查MySQL
```bash
netstat -an | findstr 3306
```
预期输出：`TCP    0.0.0.0:3306           0.0.0.0:0              LISTENING`

#### 5.1.4 检查Nginx
```bash
netstat -an | findstr 80
```
预期输出：`TCP    0.0.0.0:80             0.0.0.0:0              LISTENING`

### 5.2 系统功能验证

#### 5.2.1 访问系统首页
打开浏览器访问 `http://localhost`，预期显示系统登录页面

#### 5.2.2 测试物理服务器信息录入
1. 登录系统
2. 进入"物理服务器信息录入"页面
3. 点击"一键填充测试数据"按钮
4. 点击"保存"按钮
5. 预期显示保存成功消息

#### 5.2.3 测试图片上传功能
1. 在物理服务器信息录入页面，点击"上传图片"区域
2. 选择一张测试图片
3. 点击"保存"按钮
4. 预期图片成功保存到数据库

### 5.3 数据库验证

#### 5.3.1 检查数据库表
```sql
USE credstat;
SHOW TABLES;
```
预期显示系统相关表，包括 `phy_server_info` 和 `phy_servers_images` 等

#### 5.3.2 检查数据
```sql
SELECT COUNT(*) FROM phy_server_info;
SELECT COUNT(*) FROM phy_servers_images;
```
预期显示已保存的数据记录数

## 6. 维护与更新

### 6.1 前端更新
```bash
cd C:\phpstudy_pro\WWW\CredStat
git pull
npm install
npm run build
```

### 6.2 后端更新
```bash
cd C:\phpstudy_pro\WWW\CredStat
git pull
```

### 6.3 数据库更新
如果数据库结构有变化，执行相应的SQL语句进行更新

## 7. 附录

### 7.1 常用命令

| 命令 | 用途 |
|------|------|
| `npm run dev` | 启动开发服务器 |
| `npm run build` | 构建生产版本 |
| `start nginx` | 启动Nginx |
| `nginx -s stop` | 停止Nginx |
| `nginx -s reload` | 重新加载Nginx配置 |
| `net start mysql` | 启动MySQL服务 |
| `net stop mysql` | 停止MySQL服务 |
| `php -v` | 查看PHP版本 |
| `node -v` | 查看Node.js版本 |

### 7.2 端口占用检查
```bash
# 检查指定端口
netstat -an | findstr 端口号
# 检查所有端口
netstat -an
```

### 7.3 进程管理
```bash
# 查看指定进程
tasklist | findstr 进程名
# 结束进程
taskkill /F /PID 进程ID
```

## 8. 联系方式

如有任何问题，请联系系统管理员：
- 姓名：XXX
- 邮箱：XXX@company.com
- 电话：XXX-XXXXXXX

---

**文档版本**：v1.0.0  
**发布日期**：2025-12-18  
**作者**：系统运维团队