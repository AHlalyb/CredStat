$mysql = 'C:\phpstudy_pro\Extensions\MySQL8.0.12\bin\mysql.exe'
if (-not (Test-Path $mysql)) { $mysql = 'C:\phpstudy_pro\Extensions\MySQL5.7.26\bin\mysql.exe' }
if (-not (Test-Path $mysql)) { Write-Host 'MYSQL_NOT_FOUND'; exit }
& $mysql -uroot -proot credstat -e "SELECT jump_target_id, jump_target_name, jump_target_type, jump_target_ip, jump_target_port, jump_target_username, LEFT(jump_target_password_hash,50) AS pwd_prefix, jump_target_remark FROM jump_target;" 2>&1
