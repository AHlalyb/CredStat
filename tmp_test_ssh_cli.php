<?php
error_reporting(E_ALL & ~E_DEPRECATED);
$dbConfig = require __DIR__ . '/app/config/database.php';
$dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$ip = $argv[1] ?? '172.168.0.171';
$stmt = $pdo->prepare("SELECT net_dev_cred_username, net_dev_cred_password_hash, net_dev_cred_protocol, net_dev_cred_port FROM net_dev_cred WHERE net_dev_cred_management_ip = ?");
$stmt->execute([$ip]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo "设备 $ip 未找到\n";
    exit(1);
}
require_once __DIR__ . '/app/utils/SecurityUtils.php';
$username = trim($row['net_dev_cred_username']);
$password = SecurityUtils::decrypt($row['net_dev_cred_password_hash']);
$port = intval($row['net_dev_cred_port']) ?: 22;
echo "设备: $ip, 端口: $port, 协议: {$row['net_dev_cred_protocol']}, 账号: $username\n";

require_once __DIR__ . '/vendor/autoload.php';
$ssh = new \phpseclib3\Net\SSH2($ip, $port, 10);
if (!$ssh->login($username, $password)) {
    echo "登录失败：用户名或密码错误\n";
    exit(1);
}
echo "登录成功\n";

function cliOutputOk($output)
{
    $text = trim((string)$output);
    if ($text === '') return false;
    if (preg_match('/(?:unknown command|unrecognized|invalid input|not found|command not found|no such|unknown keyword|% invalid|syntax error|错误|未知命令|无效命令|命令不存在|无法识别)/i', $text)) return false;
    if (preg_match('/^\s*%\s*/', $text) || strlen($text) < 3) return false;
    return true;
}

$ssh->setTimeout(8);
$candidates = ['display version', 'show version', 'uname -a'];
foreach ($candidates as $cmd) {
    try {
        $out = $ssh->exec($cmd);
        echo "--- 执行: $cmd ---\n";
        echo is_string($out) ? $out : '(无输出/返回' . gettype($out) . ')';
        echo "\n[判定] " . (cliOutputOk($out) ? '有效输出' : '无效输出') . "\n\n";
        if (is_string($out) && cliOutputOk($out)) {
            echo ">>> 已进入命令行\n";
            exit(0);
        }
    } catch (Exception $e) {
        echo "--- 执行: $cmd 异常: " . $e->getMessage() . " ---\n";
    }
}
echo ">>> 未能确认进入命令行\n";
exit(1);
