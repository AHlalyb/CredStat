# 移植到CentOS 7服务器详细步骤

## 1. 环境准备阶段

### 1.1 目标服务器基础配置要求

**硬件要求**：
- CPU：至少2核
- 内存：至少4GB
- 磁盘空间：至少50GB可用空间
- 网络：稳定的网络连接

**系统要求**：
- CentOS 7.9（最小安装）
- 系统已更新到最新补丁
- 关闭或配置防火墙
- SELinux配置（如需）

### 1.2 必要依赖安装清单

**基础依赖**：
```bash
# 更新系统
yum update -y

# 安装必要的基础依赖
yum install -y epel-release wget curl vim net-tools unzip tar gcc gcc-c++ make

# 安装编译依赖
yum install -y pcre-devel zlib-devel openssl-devel libxml2-devel libjpeg-devel libpng-devel freetype-devel curl-devel libzip-devel
```

**数据库依赖**：
```bash
# 安装MySQL依赖
yum install -y libaio numactl
```

**PHP依赖**：
```bash
# 安装PHP依赖
yum install -y libmcrypt-devel mhash-devel libcurl-devel openssl-devel
```

### 1.3 版本匹配验证步骤

**验证源服务器版本**：
```bash
# 检查CentOS版本
cat /etc/centos-release

# 检查MySQL版本
mysql --version

# 检查Nginx版本
nginx -v

# 检查PHP版本
php -v

# 检查PHP-FPM版本
php-fpm -v
```

**目标服务器版本验证**：
```bash
# 确保CentOS版本一致
cat /etc/centos-release

# 验证安装的软件版本
mysql --version
nginx -v
php -v
php-fpm -v
```

## 2. 数据迁移流程

### 2.1 MySQL 8.0数据库完整备份策略

**1. 备份数据库数据**：
```bash
# 使用mysqldump备份所有数据库
mysqldump -u root -p --all-databases --routines --triggers --events > all_databases.sql

# 备份单个数据库（如果只需要备份特定数据库）
mysqldump -u root -p --databases credstat --routines --triggers --events > credstat.sql
```

**2. 备份用户权限**：
```bash
# 备份用户权限
mysql -u root -p -e "SHOW GRANTS FOR 'root'@'localhost';" > user_grants.sql
mysql -u root -p -e "SELECT user,host FROM mysql.user;" > user_list.sql

# 生成权限备份脚本
mysql -u root -p -e "SELECT CONCAT('SHOW GRANTS FOR ''', user, '''@''', host, ''';') FROM mysql.user WHERE user != 'mysql.session' AND user != 'mysql.sys';" | grep -v CONCAT | mysql -u root -p > all_user_grants.sql
```

**3. 备份配置文件**：
```bash
# 备份MySQL配置文件
cp /etc/my.cnf /etc/my.cnf.d/ ~/mysql_config_backup/
```

### 2.2 数据传输方式及校验机制

**1. 使用scp传输备份文件**：
```bash
# 传输数据库备份文件
scp all_databases.sql root@目标服务器IP:/root/

# 传输配置文件
scp -r ~/mysql_config_backup/ root@目标服务器IP:/root/
```

**2. 使用rsync传输（可选，更高效）**：
```bash
# 使用rsync传输文件
rsync -avz all_databases.sql root@目标服务器IP:/root/
rsync -avz ~/mysql_config_backup/ root@目标服务器IP:/root/
```

**3. 数据校验**：
```bash
# 在源服务器生成校验和
md5sum all_databases.sql > backup_checksum.md5

# 传输校验和文件
scp backup_checksum.md5 root@目标服务器IP:/root/

# 在目标服务器验证
md5sum -c backup_checksum.md5
```

### 2.3 目标服务器数据库恢复步骤及完整性验证方法

**1. 安装MySQL 8.0**：
```bash
# 下载MySQL YUM仓库
wget https://dev.mysql.com/get/mysql80-community-release-el7-3.noarch.rpm

# 安装仓库
yum localinstall mysql80-community-release-el7-3.noarch.rpm -y

# 安装MySQL
yum install mysql-community-server -y

# 启动MySQL服务
systemctl start mysqld
systemctl enable mysqld

# 获取初始密码
grep 'temporary password' /var/log/mysqld.log

# 登录并修改密码
mysql -u root -p
ALTER USER 'root'@'localhost' IDENTIFIED BY '新密码';
```

**2. 恢复数据库数据**：
```bash
# 恢复所有数据库
mysql -u root -p < all_databases.sql

# 恢复配置文件
cp -r /root/mysql_config_backup/* /etc/

# 重启MySQL服务
systemctl restart mysqld
```

**3. 恢复用户权限**：
```bash
# 恢复用户权限
mysql -u root -p < all_user_grants.sql
```

**4. 完整性验证**：
```bash
# 验证数据库存在
mysql -u root -p -e "SHOW DATABASES;"

# 验证表结构
mysql -u root -p -e "USE credstat; SHOW TABLES;"

# 验证数据量
mysql -u root -p -e "USE credstat; SELECT COUNT(*) FROM 表名;"

# 验证用户权限
mysql -u root -p -e "SHOW GRANTS FOR 'root'@'localhost';"
```

## 3. Nginx/1.20.1迁移

### 3.1 配置文件迁移方法

**1. 备份源服务器配置**：
```bash
# 备份Nginx配置文件
cp -r /etc/nginx/ ~/nginx_config_backup/

# 备份虚拟主机配置
cp -r /etc/nginx/conf.d/ ~/nginx_vhost_backup/
```

**2. 传输配置文件**：
```bash
# 传输配置文件
scp -r ~/nginx_config_backup/ root@目标服务器IP:/root/
scp -r ~/nginx_vhost_backup/ root@目标服务器IP:/root/
```

**3. 在目标服务器安装Nginx 1.20.1**：
```bash
# 添加Nginx YUM仓库
cat > /etc/yum.repos.d/nginx.repo << EOF
[nginx-stable]
name=nginx stable repo
baseurl=http://nginx.org/packages/centos/7/basearch/
gpgcheck=1
enabled=1
gpgkey=https://nginx.org/keys/nginx_signing.key
module_hotfixes=true
EOF

# 安装Nginx 1.20.1
yum install -y nginx-1.20.1
```

**4. 恢复配置文件**：
```bash
# 恢复配置文件
cp -r /root/nginx_config_backup/* /etc/nginx/
cp -r /root/nginx_vhost_backup/* /etc/nginx/conf.d/

# 验证配置文件
nginx -t
```

### 3.2 网站根目录文件同步方案

**1. 同步网站文件**：
```bash
# 使用rsync同步网站文件（确保代码文件不做任何修改）
rsync -avz --delete /path/to/website/ root@目标服务器IP:/path/to/website/

# 例如同步CredStat项目
rsync -avz --delete /var/www/html/CredStat/ root@目标服务器IP:/var/www/html/CredStat/
```

**2. 权限设置**：
```bash
# 设置正确的文件权限
chown -R nginx:nginx /var/www/html/CredStat/
chmod -R 755 /var/www/html/CredStat/
chmod -R 644 /var/www/html/CredStat/*.php
```

### 3.3 服务启动顺序及依赖关系说明

**启动顺序**：
1. MySQL服务（必须先启动，因为PHP和Nginx依赖数据库）
2. PHP-FPM服务（Nginx依赖PHP-FPM处理PHP请求）
3. Nginx服务

**启动命令**：
```bash
# 启动MySQL
systemctl start mysqld

# 启动PHP-FPM
systemctl start php-fpm

# 启动Nginx
systemctl start nginx

# 设置自启动
systemctl enable mysqld php-fpm nginx
```

## 4. PHP 7.4.33环境迁移

### 4.1 PHP配置文件迁移策略

**1. 备份源服务器配置**：
```bash
# 备份PHP配置文件
cp /etc/php.ini ~/php_config_backup/
cp -r /etc/php.d/ ~/php_config_backup/
cp -r /etc/php-fpm.d/ ~/php_config_backup/
cp /etc/php-fpm.conf ~/php_config_backup/
```

**2. 传输配置文件**：
```bash
# 传输配置文件
scp -r ~/php_config_backup/ root@目标服务器IP:/root/
```

**3. 在目标服务器安装PHP 7.4.33**：
```bash
# 添加Remi仓库
yum install -y https://rpms.remirepo.net/enterprise/remi-release-7.rpm

# 启用PHP 7.4仓库
yum-config-manager --enable remi-php74

# 安装PHP 7.4.33及相关扩展
yum install -y php-7.4.33 php-fpm-7.4.33 php-mysqlnd php-pdo php-gd php-mbstring php-xml php-openssl php-curl php-zip
```

**4. 恢复配置文件**：
```bash
# 恢复PHP配置文件
cp /root/php_config_backup/php.ini /etc/
cp -r /root/php_config_backup/php.d/ /etc/
cp /root/php_config_backup/php-fpm.conf /etc/
cp -r /root/php_config_backup/php-fpm.d/ /etc/

# 重启PHP-FPM服务
systemctl restart php-fpm
```

### 4.2 已安装PHP扩展的清单及安装方法

**1. 查看已安装的PHP扩展**：
```bash
# 在源服务器查看
php -m > php_extensions.txt

# 传输到目标服务器
scp php_extensions.txt root@目标服务器IP:/root/
```

**2. 在目标服务器安装相同扩展**：
```bash
# 安装必要的PHP扩展
yum install -y php-mysqlnd php-pdo php-gd php-mbstring php-xml php-openssl php-curl php-zip php-json php-session php-filter php-hash php-iconv

# 验证扩展安装
php -m > installed_extensions.txt
diff php_extensions.txt installed_extensions.txt
```

### 4.3 PHP-FPM配置迁移及验证步骤

**1. 恢复PHP-FPM配置**：
```bash
# 恢复PHP-FPM配置
cp /root/php_config_backup/php-fpm.conf /etc/
cp -r /root/php_config_backup/php-fpm.d/ /etc/

# 验证配置
php-fpm -t
```

**2. 启动并验证PHP-FPM**：
```bash
# 启动PHP-FPM
systemctl start php-fpm

# 验证服务状态
systemctl status php-fpm

# 验证PHP-FPM监听端口
netstat -tlnp | grep php-fpm

# 创建测试文件验证PHP
cat > /var/www/html/info.php << EOF
<?php
phpinfo();
?>
EOF

# 通过浏览器访问验证
# http://目标服务器IP/info.php
```

## 5. 系统服务配置

### 5.1 服务自启动设置

```bash
# 设置服务自启动
systemctl enable mysqld php-fpm nginx

# 验证自启动设置
systemctl list-unit-files | grep enabled | grep -E "mysqld|php-fpm|nginx"
```

### 5.2 防火墙规则配置

```bash
# 查看当前防火墙状态
systemctl status firewalld

# 开启防火墙（如果未开启）
systemctl start firewalld
systemctl enable firewalld

# 添加HTTP和HTTPS规则
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https

# 添加MySQL端口（如果需要远程访问）
firewall-cmd --permanent --add-port=3306/tcp

# 重新加载防火墙规则
firewall-cmd --reload

# 验证防火墙规则
firewall-cmd --list-all
```

### 5.3 SELinux策略调整（如适用）

```bash
# 查看SELinux状态
getenforce

# 如果SELinux为enforcing模式，需要调整策略

# 允许HTTP访问文件
semanage fcontext -a -t httpd_sys_content_t "/var/www/html/CredStat(/.*)?"
restorecon -Rv /var/www/html/CredStat/

# 允许HTTP执行PHP脚本
semanage fcontext -a -t httpd_sys_script_exec_t "/var/www/html/CredStat/*.php"
restorecon -Rv /var/www/html/CredStat/*.php

# 允许HTTP连接到数据库
semanage permissive -a httpd_t

# 或者临时设置SELinux为permissive模式（测试用）
setenforce 0

# 永久禁用SELinux（不推荐，仅作为最后手段）
# 修改/etc/selinux/config文件，设置SELINUX=permissive
```

## 6. 迁移后验证步骤

### 6.1 各服务状态检查方法

```bash
# 检查MySQL服务状态
systemctl status mysqld
mysql -u root -p -e "SELECT 1;"

# 检查PHP-FPM服务状态
systemctl status php-fpm
netstat -tlnp | grep php-fpm

# 检查Nginx服务状态
systemctl status nginx
netstat -tlnp | grep nginx

# 检查网站访问
curl -I http://localhost
```

### 6.2 功能验证测试用例

**1. 数据库连接测试**：
```bash
# 创建数据库连接测试脚本
cat > /var/www/html/db_test.php << EOF
<?php
$servername = "localhost";
$username = "root";
$password = "密码";
$dbname = "credstat";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "数据库连接成功";
} catch(PDOException $e) {
    echo "数据库连接失败: " . $e->getMessage();
}
$conn = null;
?>
EOF

# 执行测试
php /var/www/html/db_test.php
```

**2. 网站功能测试**：
- 访问登录页面：http://目标服务器IP/CredStat/
- 尝试登录系统
- 测试数据录入功能
- 测试信息查询功能
- 测试导出功能

**3. API接口测试**：
```bash
# 测试API接口
curl -X POST http://目标服务器IP/CredStat/db_config_handler.php \
  -H "Content-Type: application/json" \
  -d '{"action":"test"}'
```

### 6.3 性能基准测试建议

**1. 使用ab工具测试HTTP性能**：
```bash
# 安装ab工具
yum install -y httpd-tools

# 测试并发性能
ab -n 1000 -c 100 http://目标服务器IP/CredStat/
```

**2. 使用mysqltest测试数据库性能**：
```bash
# 测试数据库查询性能
mysqlslap --user=root --password=密码 --host=localhost --concurrency=10 --iterations=100 --query="SELECT * FROM credstat.表名 LIMIT 100;"
```

**3. 系统资源使用监控**：
```bash
# 监控CPU和内存使用
top

# 监控磁盘I/O
iotop

# 监控网络流量
iftop
```

## 7. 回滚方案

### 7.1 详细的迁移失败回滚步骤

**1. 停止所有服务**：
```bash
# 停止服务
systemctl stop nginx php-fpm mysqld
```

**2. 恢复源服务器配置**：
```bash
# 如果目标服务器配置被破坏，从备份恢复
cp -r /root/mysql_config_backup/* /etc/
cp /root/php_config_backup/php.ini /etc/
cp -r /root/php_config_backup/php.d/ /etc/
cp /root/php_config_backup/php-fpm.conf /etc/
cp -r /root/php_config_backup/php-fpm.d/ /etc/
cp -r /root/nginx_config_backup/* /etc/nginx/
```

**3. 恢复数据库**：
```bash
# 如果数据库迁移失败，重新恢复
mysql -u root -p < all_databases.sql
```

**4. 重启服务**：
```bash
# 按顺序重启服务
systemctl start mysqld
systemctl start php-fpm
systemctl start nginx
```

### 7.2 数据恢复机制

**1. 定期备份策略**：
- 在迁移前对源服务器进行完整备份
- 定期对目标服务器进行备份
- 建立备份轮换机制

**2. 增量备份**：
```bash
# 设置MySQL增量备份
# 在my.cnf中启用binlog
# log-bin=/var/lib/mysql/binlog
# server-id=1

# 定期备份binlog
mysqladmin -u root -p flush-logs
```

**3. 灾难恢复**：
- 保存多个时间点的备份
- 建立备份验证机制
- 定期测试恢复流程

## 8. 附录

### 8.1 常用命令汇总

**服务管理**：
- `systemctl start|stop|restart|status 服务名` - 管理系统服务
- `systemctl enable|disable 服务名` - 设置服务自启动

**文件传输**：
- `scp 源文件 目标用户@目标IP:目标路径` - 安全复制文件
- `rsync -avz 源目录 目标用户@目标IP:目标路径` - 高效同步文件

**网络测试**：
- `ping 目标IP` - 测试网络连通性
- `netstat -tlnp` - 查看监听端口
- `curl -I URL` - 测试HTTP响应

**数据库管理**：
- `mysql -u 用户名 -p` - 登录MySQL
- `mysqldump -u 用户名 -p 数据库名 > 备份文件.sql` - 备份数据库
- `mysql -u 用户名 -p 数据库名 < 备份文件.sql` - 恢复数据库

### 8.2 故障排查指南

**常见问题及解决方案**：

1. **Nginx启动失败**
   - 检查配置文件：`nginx -t`
   - 检查端口占用：`netstat -tlnp | grep 80`
   - 查看错误日志：`tail -f /var/log/nginx/error.log`

2. **PHP-FPM启动失败**
   - 检查配置文件：`php-fpm -t`
   - 检查端口占用：`netstat -tlnp | grep 9000`
   - 查看错误日志：`tail -f /var/log/php-fpm/error.log`

3. **MySQL启动失败**
   - 检查配置文件：`mysqld --validate-config`
   - 检查磁盘空间：`df -h`
   - 查看错误日志：`tail -f /var/log/mysqld.log`

4. **网站访问404错误**
   - 检查文件路径是否正确
   - 检查Nginx配置中的root路径
   - 检查文件权限

5. **数据库连接失败**
   - 检查MySQL服务状态
   - 检查数据库用户名和密码
   - 检查防火墙规则
   - 检查SELinux策略

### 8.3 最佳实践建议

1. **环境隔离**：
   - 使用相同版本的操作系统和软件
   - 保持配置文件的一致性
   - 使用环境变量管理敏感信息

2. **安全加固**：
   - 定期更新系统和软件补丁
   - 配置强密码策略
   - 限制远程访问权限
   - 启用防火墙和SELinux

3. **监控告警**：
   - 部署监控系统（如Zabbix）
   - 设置关键服务的告警
   - 定期检查日志文件

4. **文档管理**：
   - 记录所有配置变更
   - 维护详细的部署文档
   - 建立故障处理流程

5. **自动化部署**：
   - 使用Ansible或Docker等工具自动化部署
   - 建立CI/CD流程
   - 实现环境的快速复制和迁移

---

**文档完成时间**：2026年1月19日
**文档版本**：1.0
**适用场景**：CentOS 7服务器环境迁移
**注意事项**：本方案仅适用于相同版本的软件环境迁移，不同版本可能需要调整部分步骤。