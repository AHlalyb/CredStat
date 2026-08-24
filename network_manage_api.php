<?php
/**
 * network_manage_api.php
 * 网络设备管理接口
 * 提供：ping 测试、拨测（probe）、SNMP 测试
 * 请求方式：POST JSON
 * 请求参数：
 *   action    : ping | probe | snmp
 *   username  : 当前登录用户名（鉴权）
 *   ip        : 管理IP（ping/probe/snmp 均需要）
 *   port      : 管理端口（probe 使用，缺省按协议取默认端口）
 *   protocol  : 远程协议 ssh/telnet/http/https（probe 使用）
 *   id        : 网络设备记录ID（probe/snmp 从数据库取账号密码/SNMP 团体字时使用）
 */

// 设置响应头
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理CORS预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    echo json_encode(['success' => true, 'message' => 'OPTIONS请求处理成功']);
    exit;
}

try {
    // 读取请求体
    $rawBody = file_get_contents('php://input');
    $requestData = json_decode($rawBody, true);
    if (!is_array($requestData)) {
        $requestData = $_POST;
    }

    $action = isset($requestData['action']) ? trim($requestData['action']) : '';
    $username = isset($requestData['username']) ? trim($requestData['username']) : '';

    // 记录请求日志
    error_log('网络设备管理API请求: ' . base64_encode($rawBody));

    // 鉴权
    $dbConfig = require __DIR__ . '/app/config/database.php';
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);

    $userPermissions = ['query' => 0, 'edit' => 0, 'manage' => 0];
    if (!empty($username)) {
        $stmt = $pdo->prepare("SELECT
            credstat_user_perm_add,
            credstat_user_perm_delete,
            credstat_user_perm_edit,
            credstat_user_perm_query,
            credstat_user_perm_manage
        FROM credstat_user
        WHERE credstat_user_account = :username");
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $userPermissions = [
                'query' => intval($user['credstat_user_perm_query']),
                'edit' => intval($user['credstat_user_perm_edit']),
                'manage' => intval($user['credstat_user_perm_manage'])
            ];
        }
        if (!($userPermissions['query'] === 1 || $userPermissions['edit'] === 1)) {
            throw new Exception('您没有权限执行此操作');
        }
    }

    // 确保数据库已具备拨测/SNMP 状态字段（自动补齐，无需手动执行 SQL）
    ensureProbeColumns($pdo);

    switch ($action) {
        case 'ping':
            handlePing($requestData);
            break;
        case 'probe':
            handleProbe($pdo, $requestData);
            break;
        case 'snmp':
            handleSnmpTest($pdo, $requestData);
            break;
        default:
            throw new Exception('无效的操作类型: ' . $action);
    }

} catch (Exception $e) {
    error_log('网络设备管理API错误: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 确保 net_dev_cred 表具备拨测/SNMP 状态字段（自动补齐，幂等）
 */
function ensureProbeColumns($pdo)
{
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM net_dev_cred LIKE 'net_dev_cred_probe_status'");
        if ($stmt->rowCount() > 0) {
            return;
        }
        $pdo->exec(
            "ALTER TABLE `net_dev_cred`
                ADD COLUMN `net_dev_cred_probe_status` varchar(20) NULL DEFAULT NULL COMMENT '拨测状态(success/fail)' AFTER `net_dev_cred_snmp`,
                ADD COLUMN `net_dev_cred_probe_message` varchar(1000) NULL DEFAULT NULL COMMENT '最近拨测结果' AFTER `net_dev_cred_probe_status`,
                ADD COLUMN `net_dev_cred_probe_time` datetime NULL DEFAULT NULL COMMENT '最近拨测时间' AFTER `net_dev_cred_probe_message`,
                ADD COLUMN `net_dev_cred_snmp_status` varchar(20) NULL DEFAULT NULL COMMENT 'SNMP测试状态(success/fail)' AFTER `net_dev_cred_probe_time`,
                ADD COLUMN `net_dev_cred_snmp_message` varchar(1000) NULL DEFAULT NULL COMMENT '最近SNMP测试结果' AFTER `net_dev_cred_snmp_status`,
                ADD COLUMN `net_dev_cred_snmp_time` datetime NULL DEFAULT NULL COMMENT '最近SNMP测试时间' AFTER `net_dev_cred_snmp_message`"
        );
    } catch (Exception $e) {
        error_log('ensureProbeColumns: ' . $e->getMessage());
    }
}

/**
 * 系统错误信息（如 Winsock 错误）可能为 GBK 编码，统一转为 UTF-8，避免 json_encode 失败
 */
function probeSafeText($text)
{
    if ($text === '' || $text === null) {
        return '';
    }
    if (mb_check_encoding($text, 'UTF-8')) {
        return $text;
    }
    $converted = @mb_convert_encoding($text, 'UTF-8', 'GBK');
    if ($converted !== false && $converted !== '') {
        return $converted;
    }
    return '连接超时';
}

/**
 * 将拨测/SNMP 测试结果写入数据库状态字段
 */
function probeSaveStatus($pdo, $id, $type, $status, $message)
{
    if ($id <= 0) {
        return;
    }
    $column = ($type === 'snmp') ? 'snmp' : 'probe';
    try {
        $stmt = $pdo->prepare(
            "UPDATE `net_dev_cred` SET
                `net_dev_cred_{$column}_status` = :status,
                `net_dev_cred_{$column}_message` = :message,
                `net_dev_cred_{$column}_time` = NOW()
             WHERE `id` = :id"
        );
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':message', mb_substr($message, 0, 1000), PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    } catch (Exception $e) {
        error_log('probeSaveStatus: ' . $e->getMessage());
    }
}

/**
 * 全自动拨测：通过 IP、端口、协议及账号密码判断设备是否在线、远程方式是否正确，并记录状态
 */
function handleProbe($pdo, $requestData)
{
    $id = isset($requestData['id']) ? intval($requestData['id']) : 0;
    $ip = isset($requestData['ip']) ? trim($requestData['ip']) : '';
    $port = isset($requestData['port']) ? intval($requestData['port']) : 0;
    $protocol = isset($requestData['protocol']) ? strtolower(trim($requestData['protocol'])) : '';

    if (empty($ip)) {
        throw new Exception('缺少管理IP参数');
    }
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        throw new Exception('IP地址格式不正确');
    }

    // 从数据库读取账号密码（用于认证验证）
    $username = '';
    $password = '';
    if ($id > 0) {
        $stmt = $pdo->prepare(
            "SELECT net_dev_cred_username, net_dev_cred_password_hash, net_dev_cred_protocol, net_dev_cred_port
             FROM net_dev_cred WHERE id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $username = isset($row['net_dev_cred_username']) ? trim($row['net_dev_cred_username']) : '';
            if (!empty($row['net_dev_cred_password_hash'])) {
                try {
                    require_once __DIR__ . '/app/utils/SecurityUtils.php';
                    $decrypted = SecurityUtils::decrypt($row['net_dev_cred_password_hash']);
                    if ($decrypted !== null && $decrypted !== false) {
                        $password = $decrypted;
                    }
                } catch (Exception $e) {
                    $password = '';
                }
            }
            if (empty($protocol)) {
                $protocol = strtolower(trim($row['net_dev_cred_protocol'] ?? ''));
            }
            if ($port < 1 && !empty($row['net_dev_cred_port'])) {
                $port = intval($row['net_dev_cred_port']);
            }
        }
    }

    // 默认端口
    if ($port < 1 || $port > 65535) {
        $port = ($protocol === 'ssh') ? 22
            : (($protocol === 'telnet') ? 23
            : (($protocol === 'https') ? 443 : 80));
    }

    // 1. 在线检测：TCP 端口连通性
    $tcpResult = probeTcpConnect($ip, $port, 3);
    if (!$tcpResult['success']) {
        $message = "设备不在线：无法建立 TCP 连接 {$ip}:{$port}\n原因：" . $tcpResult['error'];
        probeSaveStatus($pdo, $id, 'probe', 'fail', $message);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'data' => [
                'ip' => $ip,
                'port' => $port,
                'protocol' => $protocol,
                'online' => false,
                'auth' => 'skipped',
                'detail' => $tcpResult['error']
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    // 2. 协议 / 认证验证
    $verify = probeVerifyProtocol($ip, $port, $protocol, $username, $password);

    if ($verify['success']) {
        $message = "设备在线，远程方式正确。\n[协议] " . strtoupper($protocol) . " {$ip}:{$port}\n[结果] " . $verify['message'];
        $status = 'success';
    } else {
        $message = "设备在线，但远程方式验证失败。\n[协议] " . strtoupper($protocol) . " {$ip}:{$port}\n[结果] " . $verify['message'];
        $status = 'fail';
    }
    probeSaveStatus($pdo, $id, 'probe', $status, $message);

    echo json_encode([
        'success' => $verify['success'],
        'message' => $message,
        'data' => [
            'ip' => $ip,
            'port' => $port,
            'protocol' => $protocol,
            'online' => true,
            'auth' => $verify['auth'],
            'detail' => $verify['message']
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

/**
 * TCP 端口连通性检测
 */
function probeTcpConnect($ip, $port, $timeout = 3)
{
    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client("tcp://{$ip}:{$port}", $errno, $errstr, $timeout);
    if ($fp) {
        stream_set_timeout($fp, $timeout);
        fclose($fp);
        return ['success' => true];
    }
    return ['success' => false, 'error' => $errstr !== '' ? probeSafeText($errstr) : '连接超时'];
}

/**
 * 按协议执行远程方式 / 认证验证
 */
function probeVerifyProtocol($ip, $port, $protocol, $username, $password)
{
    switch ($protocol) {
        case 'ssh':
            return probeVerifySsh($ip, $port, $username, $password);
        case 'telnet':
            return probeVerifyTelnet($ip, $port, $username, $password);
        case 'http':
        case 'https':
            return probeVerifyHttp($ip, $port, $protocol, $username, $password);
        default:
            return [
                'success' => true,
                'message' => "协议 {$protocol} 端口开放（TCP 连通），未做登录认证验证",
                'auth' => 'skipped'
            ];
    }
}

/**
 * SSH 验证：读取服务 banner 确认 SSH 协议，尽量做账号认证
 */
function probeVerifySsh($ip, $port, $username, $password)
{
    $banner = '';
    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client("tcp://{$ip}:{$port}", $errno, $errstr, 5);
    if (!$fp) {
        return ['success' => false, 'message' => 'SSH 端口无法连接: ' . ($errstr !== '' ? probeSafeText($errstr) : '超时'), 'auth' => 'skipped'];
    }
    stream_set_timeout($fp, 5);
    $banner = @fgets($fp, 256);
    if ($banner !== false) {
        $banner = trim($banner);
    }
    fclose($fp);

    if ($banner === '' || stripos($banner, 'SSH-') !== 0) {
        return [
            'success' => false,
            'message' => '端口已开放，但未返回 SSH 协议 Banner' . ($banner !== '' ? "（响应: {$banner}）" : '（无响应）'),
            'auth' => 'skipped'
        ];
    }

    // 尝试使用 phpseclib 做真实认证验证（如已安装）
    $sshAuthed = false;
    $sshDetail = '';
    $phpseclib = __DIR__ . '/vendor/autoload.php';
    if ($username !== '' && $password !== '' && file_exists($phpseclib)) {
        try {
            require_once $phpseclib;
            if (class_exists('phpseclib3\\Net\\SSH2')) {
                $ssh = new \phpseclib3\Net\SSH2($ip, $port, 5);
                if ($ssh->login($username, $password)) {
                    $sshAuthed = true;
                    $sshDetail = "账号 {$username} 认证成功";
                } else {
                    $sshDetail = "账号 {$username} 认证失败（用户名或密码错误）";
                }
            }
        } catch (Exception $e) {
            $sshDetail = '';
        }
    }

    if ($sshAuthed) {
        return ['success' => true, 'message' => "SSH 服务正常，{$sshDetail}", 'auth' => 'passed'];
    }
    if ($sshDetail !== '') {
        return ['success' => false, 'message' => "SSH 服务正常（{$banner}），但{$sshDetail}", 'auth' => 'failed'];
    }
    return [
        'success' => true,
        'message' => "SSH 服务正常（{$banner}），未做账号认证验证",
        'auth' => 'skipped'
    ];
}

/**
 * Telnet 验证：使用账号密码执行真实登录交互
 * 支持两种模式：
 *   1) 标准账号+密码（先 login/username 提示，后 password 提示）
 *   2) 仅密码认证（如 Cisco/H3C 的 User Access Verification，直接要求输入密码）
 */
function probeVerifyTelnet($ip, $port, $username, $password)
{
    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client("tcp://{$ip}:{$port}", $errno, $errstr, 5);
    if (!$fp) {
        return ['success' => false, 'message' => 'Telnet 端口无法连接: ' . ($errstr !== '' ? probeSafeText($errstr) : '超时'), 'auth' => 'skipped'];
    }
    stream_set_timeout($fp, 5);

    // 读取并剥离 Telnet 协商字节，直到出现登录提示
    $buffer = '';
    $deadline = microtime(true) + 5;
    $authMode = ''; // 'user_pass' 或 'password_only'
    while (microtime(true) < $deadline) {
        $chunk = @fread($fp, 512);
        if ($chunk === false || $chunk === '') {
            if (feof($fp)) break;
            continue;
        }
        $buffer .= $chunk;
        // 剥离 Telnet IAC 协商序列
        $clean = probeStripTelnetIac($buffer);
        // 模式 A：标准账号+密码（先出现 login / username 提示）
        if (preg_match('/(?:\blogin\s*[:：]|\buser\s*name\s*[:：]|用户名\s*[:：]|账号\s*[:：])/i', $clean, $m)) {
            $authMode = 'user_pass';
            break;
        }
        // 模式 B：仅密码认证（出现 User Access Verification / Password: 但前面没有 login 提示）
        if (preg_match('/(?:user\s*access\s*verification|password\s*[:：])/i', $clean, $m)) {
            $authMode = 'password_only';
            break;
        }
        if (stripos($clean, 'Press ENTER') !== false || stripos($clean, 'press any key') !== false) {
            // 某些设备要求先按回车
            @fwrite($fp, "\r\n");
        }
    }
    $clean = probeStripTelnetIac($buffer);

    // 未出现登录提示：可能设备不支持登录或已直接进入
    if ($authMode === '') {
        // 若提示 "Press ENTER"，发送回车后继续
        @fwrite($fp, "\r\n");
        usleep(300000);
        $buffer .= (string)@fread($fp, 512);
        $clean = probeStripTelnetIac($buffer);
        if (preg_match('/(?:\blogin\s*[:：]|\buser\s*name\s*[:：]|用户名\s*[:：]|账号\s*[:：])/i', $clean, $m)) {
            $authMode = 'user_pass';
        } elseif (preg_match('/(?:user\s*access\s*verification|password\s*[:：])/i', $clean, $m)) {
            $authMode = 'password_only';
        } else {
            fclose($fp);
            return [
                'success' => false,
                'message' => 'Telnet 端口已开放，但未检测到登录提示，可能不是标准 Telnet 登录服务',
                'auth' => 'skipped'
            ];
        }
    }

    // 未配置账号密码
    if ($username === '' && $password === '') {
        fclose($fp);
        return [
            'success' => true,
            'message' => 'Telnet 服务正常，检测到登录提示（未配置账号密码）',
            'auth' => 'skipped'
        ];
    }

    // ========== 模式 B：仅密码认证 ==========
    if ($authMode === 'password_only') {
        // 如果当前缓冲区还没有出现 Password: 提示，等待它
        $passwordPrompt = false;
        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            $clean = probeStripTelnetIac($buffer);
            if (preg_match('/password\s*[:：]/i', $clean)) {
                $passwordPrompt = true;
                break;
            }
            $chunk = @fread($fp, 512);
            if ($chunk === false || $chunk === '') {
                if (feof($fp)) break;
                continue;
            }
            $buffer .= $chunk;
        }
        if (!$passwordPrompt) {
            fclose($fp);
            return [
                'success' => false,
                'message' => 'Telnet 仅密码认证：检测到 User Access Verification，但未出现 Password 提示',
                'auth' => 'failed'
            ];
        }
        // 发送密码（使用密码字段，若为空则使用用户名作为密码回退）
        $sendPass = $password !== '' ? $password : $username;
        @fwrite($fp, $sendPass . "\r\n");
        // 等待结果
        $buffer = '';
        $deadline = microtime(true) + 5;
        $resultText = '';
        while (microtime(true) < $deadline) {
            $chunk = @fread($fp, 512);
            if ($chunk === false || $chunk === '') {
                if (feof($fp)) break;
                continue;
            }
            $buffer .= $chunk;
            $clean = probeStripTelnetIac($buffer);
            if ($clean !== '') {
                $resultText = $clean;
            }
            // 失败标志
            if (preg_match('/(?:login incorrect|authentication failed|access denied|invalid|错误|失败|密码错误|被拒绝)/i', $clean)) {
                fclose($fp);
                return [
                    'success' => false,
                    'message' => "Telnet 仅密码认证失败：密码错误，请检查密码",
                    'auth' => 'failed'
                ];
            }
            // 成功标志：出现设备提示符
            if (preg_match('/[#>\$]\s*$/', $clean)) {
                fclose($fp);
                return [
                    'success' => true,
                    'message' => "Telnet 仅密码认证成功，已进入设备命令行提示符",
                    'auth' => 'passed'
                ];
            }
        }
        fclose($fp);
        if ($resultText !== '') {
            return [
                'success' => true,
                'message' => "Telnet 仅密码认证交互完成，未检测到明确的失败标志",
                'auth' => 'passed'
            ];
        }
        return [
            'success' => false,
            'message' => 'Telnet 仅密码认证结果无法确认（响应为空或超时）',
            'auth' => 'failed'
        ];
    }

    // ========== 模式 A：标准账号+密码 ==========
    // 发送用户名
    @fwrite($fp, $username . "\r\n");
    // 等待密码提示
    $buffer = '';
    $deadline = microtime(true) + 5;
    $passwordPrompt = '';
    while (microtime(true) < $deadline) {
        $chunk = @fread($fp, 512);
        if ($chunk === false || $chunk === '') {
            if (feof($fp)) break;
            continue;
        }
        $buffer .= $chunk;
        $clean = probeStripTelnetIac($buffer);
        if (preg_match('/password\s*[:：]/i', $clean)) {
            $passwordPrompt = 'password:';
            break;
        }
        if (preg_match('/(?:login incorrect|authentication failed|invalid|denied|错误|失败|密码错误)/i', $clean)) {
            break;
        }
    }

    if ($passwordPrompt === '') {
        fclose($fp);
        return [
            'success' => false,
            'message' => 'Telnet 登录交互异常：未检测到密码提示，可能用户名错误或设备不要求密码',
            'auth' => 'failed'
        ];
    }

    // 发送密码
    @fwrite($fp, $password . "\r\n");

    // 等待登录结果
    $buffer = '';
    $deadline = microtime(true) + 5;
    $resultText = '';
    while (microtime(true) < $deadline) {
        $chunk = @fread($fp, 512);
        if ($chunk === false || $chunk === '') {
            if (feof($fp)) break;
            continue;
        }
        $buffer .= $chunk;
        $clean = probeStripTelnetIac($buffer);
        if ($clean !== '') {
            $resultText = $clean;
        }
        // 失败标志
        if (preg_match('/(?:login incorrect|authentication failed|access denied|invalid username|错误|失败|密码错误|被拒绝)/i', $clean)) {
            fclose($fp);
            return [
                'success' => false,
                'message' => "Telnet 登录失败（账号 {$username}）：检测到失败提示，请检查用户名或密码",
                'auth' => 'failed'
            ];
        }
        // 成功标志：出现设备提示符
        if (preg_match('/[#>\$]\s*$/', $clean)) {
            fclose($fp);
            return [
                'success' => true,
                'message' => "Telnet 登录成功（账号 {$username}），已进入设备命令行提示符",
                'auth' => 'passed'
            ];
        }
    }

    fclose($fp);
    if ($resultText !== '') {
        return [
            'success' => true,
            'message' => "Telnet 交互完成，未检测到明确的失败标志（账号 {$username}）",
            'auth' => 'passed'
        ];
    }
    return [
        'success' => false,
        'message' => 'Telnet 登录结果无法确认（响应为空或超时）',
        'auth' => 'failed'
    ];
}

/**
 * 剥离 Telnet IAC 协商字节序列
 */
function probeStripTelnetIac($data)
{
    $result = '';
    $len = strlen($data);
    $i = 0;
    while ($i < $len) {
        $ord = ord($data[$i]);
        if ($ord === 255) { // IAC
            if ($i + 1 < $len) {
                $cmd = ord($data[$i + 1]);
                if ($cmd === 251 || $cmd === 252 || $cmd === 253 || $cmd === 254) {
                    // WILL/WONT/DO/DONT 后跟 1 字节选项
                    $i += 3;
                    continue;
                } elseif ($cmd === 250) {
                    // SB ... SE
                    $j = strpos($data, chr(255) . chr(240), $i + 2);
                    $i = ($j !== false) ? $j + 2 : $len;
                    continue;
                } else {
                    $i += 2;
                    continue;
                }
            }
            break;
        }
        $result .= $data[$i];
        $i++;
    }
    return $result;
}

/**
 * HTTP/HTTPS 验证：请求管理页面，按状态码判断服务与认证
 */
function probeVerifyHttp($ip, $port, $protocol, $username, $password)
{
    if (!function_exists('curl_init')) {
        // 无 cURL 时退化为 TCP 检测
        $tcp = probeTcpConnect($ip, $port, 3);
        if ($tcp['success']) {
            return ['success' => true, 'message' => "{$protocol} 端口开放（TCP 连通，服务器未启用 cURL，未做 HTTP 验证）", 'auth' => 'skipped'];
        }
        return ['success' => false, 'message' => "{$protocol} 端口无法连接: " . $tcp['error'], 'auth' => 'skipped'];
    }

    $url = "{$protocol}://{$ip}:{$port}/";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_NOBODY => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'CredStat-Probe/1.0'
    ]);
    if ($username !== '') {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
    }
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode <= 0) {
        return ['success' => false, 'message' => "HTTP 请求失败: " . ($error ?: '无响应'), 'auth' => 'skipped'];
    }
    if ($httpCode >= 200 && $httpCode < 500) {
        if ($httpCode === 401 || $httpCode === 403) {
            return [
                'success' => true,
                'message' => "Web 服务正常（HTTP {$httpCode}，需要认证，账号密码可能生效）",
                'auth' => ($username !== '') ? 'passed' : 'skipped'
            ];
        }
        return [
            'success' => true,
            'message' => "Web 服务正常（HTTP {$httpCode}）",
            'auth' => 'skipped'
        ];
    }
    return [
        'success' => false,
        'message' => "Web 服务异常（HTTP {$httpCode}）",
        'auth' => 'skipped'
    ];
}

/**
 * Ping 测试
 */
function handlePing($requestData)
{
    $ip = isset($requestData['ip']) ? trim($requestData['ip']) : '';
    if (empty($ip)) {
        throw new Exception('缺少管理IP参数');
    }
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        throw new Exception('IP地址格式不正确');
    }

    // 检查 exec 是否可用
    $disabled = array_map('trim', explode(',', ini_get('disable_functions')));
    if (!function_exists('exec') || in_array('exec', $disabled)) {
        throw new Exception('服务器禁用了exec函数，无法执行ping测试');
    }

    $isWindows = strncasecmp(PHP_OS, 'WIN', 3) === 0;
    $safeIp = escapeshellarg($ip);
    if ($isWindows) {
        $cmd = 'ping -n 1 -w 1500 ' . $safeIp;
    } else {
        $cmd = 'ping -c 1 -W 2 ' . $safeIp;
    }

    $output = [];
    $exitCode = -1;
    exec($cmd . ' 2>&1', $output, $exitCode);

    // Windows 下输出为 GBK 编码，转换为 UTF-8
    $lines = array_map(function ($line) {
        if (!mb_check_encoding($line, 'UTF-8')) {
            $converted = @iconv('GBK', 'UTF-8//IGNORE', $line);
            return $converted !== false ? $converted : $line;
        }
        return $line;
    }, $output);

    $raw = implode("\n", $lines);

    // 判断是否成功：退出码为 0 且存在 TTL/时间 标志
    $success = ($exitCode === 0);
    $ttlMatch = preg_match('/[Tt][Tt][Ll][=:]\s*\d+/', $raw);
    $zhMatch = preg_match('/时间[=:]\s*\d+/', $raw);
    if ($ttlMatch || $zhMatch) {
        $success = true;
    }

    // 提取延迟（毫秒）
    $latency = null;
    if (preg_match('/时间[=:<]?\s*<?\s*([\d.]+)\s*ms/i', $raw, $m) ||
        preg_match('/[Tt]ime[=:<]?\s*<?\s*([\d.]+)\s*ms/i', $raw, $m)) {
        $latency = $m[1];
    }

    if ($success) {
        $msg = $latency !== null
            ? "IP {$ip} 可以连通，延迟约 {$latency} ms"
            : "IP {$ip} 可以连通";
        echo json_encode([
            'success' => true,
            'message' => $msg,
            'data' => [
                'ip' => $ip,
                'latency' => $latency,
                'raw' => $raw
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => "IP {$ip} 无法连通（请求超时或无响应）",
            'data' => [
                'ip' => $ip,
                'raw' => $raw
            ]
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

/**
 * SNMP 测试：从数据库获取并解密 SNMP 团体字，执行 SNMP v1 GET 请求
 */
function handleSnmpTest($pdo, $requestData)
{
    $ip = isset($requestData['ip']) ? trim($requestData['ip']) : '';
    if (empty($ip)) {
        throw new Exception('缺少管理IP参数');
    }
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        throw new Exception('IP地址格式不正确');
    }

    // 尝试从数据库获取 SNMP 团体字（解密）
    $community = null;
    $id = isset($requestData['id']) ? intval($requestData['id']) : 0;
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT net_dev_cred_snmp FROM net_dev_cred WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['net_dev_cred_snmp'])) {
            try {
                require_once __DIR__ . '/app/utils/SecurityUtils.php';
                $decrypted = SecurityUtils::decrypt($row['net_dev_cred_snmp']);
                if ($decrypted !== null && $decrypted !== false) {
                    $community = $decrypted;
                }
            } catch (Exception $e) {
                // 解密失败则回退使用密文（极少数情况）
                $community = $row['net_dev_cred_snmp'];
            }
        }
    }
    // 默认团体字
    if (empty($community)) {
        $community = 'public';
    }

    // SNMP 端口（默认 161，允许通过参数覆盖以支持非标准端口）
    $snmpPort = isset($requestData['snmp_port']) ? intval($requestData['snmp_port']) : 161;
    if ($snmpPort < 1 || $snmpPort > 65535) {
        $snmpPort = 161;
    }

    $result = snmpGetSysDescr($ip, $community, 3, $snmpPort);

    // 注意：SNMP 原始响应是二进制数据，必须 base64 编码后才能放进 JSON
    if ($result['success']) {
        $message = "SNMP 测试成功，设备返回系统描述：\n" . $result['sysDescr'];
        probeSaveStatus($pdo, $id, 'snmp', 'success', $message);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => [
                'ip' => $ip,
                'community' => $community,
                'sysDescr' => $result['sysDescr'],
                'raw' => isset($result['raw']) ? base64_encode($result['raw']) : null
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    } else {
        $message = "SNMP 测试失败：" . $result['error'];
        probeSaveStatus($pdo, $id, 'snmp', 'fail', $message);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'data' => [
                'ip' => $ip,
                'community' => $community,
                'error' => $result['error'],
                'raw' => isset($result['raw']) ? base64_encode($result['raw']) : null
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
    exit;
}

/**
 * 执行 SNMP v1 GET 请求，获取 sysDescr (1.3.6.1.2.1.1.1.0)
 */
function snmpGetSysDescr($ip, $community, $timeout = 3, $port = 161)
{
    // 优先使用 PHP snmp 扩展
    if (function_exists('snmpget') && !in_array('snmpget', array_map('trim', explode(',', ini_get('disable_functions'))))) {
        try {
            $result = @snmpget($ip, $community, '.1.3.6.1.2.1.1.1.0', $timeout, 1);
            if ($result !== false) {
                $text = is_array($result) ? json_encode($result, JSON_UNESCAPED_UNICODE) : (string)$result;
                // snmpget 返回形如 "STRING: xxx" 或 "Hex-STRING: ..."
                if (preg_match('/STRING:\s*(.+)$/i', $text, $m)) {
                    $text = trim($m[1], " \t\n\r\0\x0B\"");
                }
                return ['success' => true, 'sysDescr' => $text, 'raw' => $text];
            }
            return ['success' => false, 'error' => '设备无响应或团体字错误（snmpget 超时）'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'snmpget 异常: ' . $e->getMessage()];
        }
    }

    // 使用纯 PHP UDP socket 实现 SNMP v1 GET
    $socket = @stream_socket_client("udp://{$ip}:{$port}", $errno, $errstr, $timeout);
    if (!$socket) {
        return ['success' => false, 'error' => "无法连接 {$ip}:{$port} (UDP) - {$errstr}"];
    }

    stream_set_timeout($socket, $timeout);

    // 构造 SNMP v1 GetRequest
    $request = snmpBuildGetRequest($community, '1.3.6.1.2.1.1.1.0');

    if (@fwrite($socket, $request) === false) {
        fclose($socket);
        return ['success' => false, 'error' => '发送 SNMP 请求失败'];
    }

    $response = '';
    while (!feof($socket)) {
        $chunk = fread($socket, 8192);
        if ($chunk === false || $chunk === '') {
            break;
        }
        $response .= $chunk;
    }
    fclose($socket);

    if ($response === '') {
        return ['success' => false, 'error' => '设备无响应（UDP 超时），请检查设备是否启用 SNMP、团体字是否正确、网络是否可达'];
    }

    // 解析响应
    $parsed = snmpParseResponse($response);
    if ($parsed === null) {
        return ['success' => false, 'error' => 'SNMP 响应解析失败，可能不是有效的 SNMP 报文', 'raw' => bin2hex($response)];
    }

    if ($parsed['errorStatus'] !== 0) {
        $errText = snmpErrorText($parsed['errorStatus']);
        return ['success' => false, 'error' => "SNMP 返回错误: {$errText}", 'raw' => $response];
    }

    // 部分设备 sysDescr 可能为非 UTF-8 编码（如 GBK），统一转换为 UTF-8 以便显示
    $sysDescr = $parsed['value'];
    if ($sysDescr !== '' && !mb_check_encoding($sysDescr, 'UTF-8')) {
        $converted = @mb_convert_encoding($sysDescr, 'UTF-8', 'GBK');
        $sysDescr = ($converted !== false && $converted !== '') ? $converted : $sysDescr;
    }

    return ['success' => true, 'sysDescr' => $sysDescr, 'raw' => $response];
}

/**
 * 构造 SNMP v1 GetRequest 报文
 */
function snmpBuildGetRequest($community, $oid)
{
    $oidBytes = snmpEncodeOid($oid);
    $oidTlv = chr(0x06) . snmpEncodeLength(strlen($oidBytes)) . $oidBytes;
    $nullTlv = chr(0x05) . chr(0x00);
    $varbind = chr(0x30) . snmpEncodeLength(strlen($oidTlv) + strlen($nullTlv)) . $oidTlv . $nullTlv;
    $varbinds = chr(0x30) . snmpEncodeLength(strlen($varbind)) . $varbind;

    $requestId = mt_rand(1, 0x7FFFFFFF);
    $requestIdTlv = chr(0x02) . snmpEncodeLength(4) . pack('N', $requestId);
    $errorStatusTlv = chr(0x02) . chr(0x01) . chr(0x00);
    $errorIndexTlv = chr(0x02) . chr(0x01) . chr(0x00);

    $pduContent = $requestIdTlv . $errorStatusTlv . $errorIndexTlv . $varbinds;
    $pdu = chr(0xA0) . snmpEncodeLength(strlen($pduContent)) . $pduContent;

    $versionTlv = chr(0x02) . chr(0x01) . chr(0x00); // SNMP v1
    $communityTlv = chr(0x04) . snmpEncodeLength(strlen($community)) . $community;

    $msgContent = $versionTlv . $communityTlv . $pdu;
    return chr(0x30) . snmpEncodeLength(strlen($msgContent)) . $msgContent;
}

/**
 * BER 长度编码
 */
function snmpEncodeLength($length)
{
    if ($length < 0x80) {
        return chr($length);
    }
    $bytes = '';
    while ($length > 0) {
        $bytes = chr($length & 0xFF) . $bytes;
        $length >>= 8;
    }
    return chr(0x80 | strlen($bytes)) . $bytes;
}

/**
 * OID 编码（点分十进制 -> BER）
 */
function snmpEncodeOid($oid)
{
    $parts = array_map('intval', explode('.', $oid));
    if (count($parts) < 2) {
        return '';
    }
    $bytes = chr(40 * $parts[0] + $parts[1]);
    for ($i = 2; $i < count($parts); $i++) {
        $bytes .= snmpEncodeSubId($parts[$i]);
    }
    return $bytes;
}

/**
 * OID 子标识符编码（大整数，7位一组）
 */
function snmpEncodeSubId($value)
{
    $bytes = chr($value & 0x7F);
    $value >>= 7;
    while ($value > 0) {
        $bytes = chr(($value & 0x7F) | 0x80) . $bytes;
        $value >>= 7;
    }
    return $bytes;
}

/**
 * 解析 SNMP 响应报文，提取 error-status 和 varbind 值
 */
function snmpParseResponse($data)
{
    $offset = 0;
    if (!isset($data[$offset])) {
        return null;
    }

    // 读取外层 SEQUENCE
    list($type, $len) = snmpReadHeader($data, $offset);
    if ($type !== 0x30 || $len === null) {
        return null;
    }
    $msgEnd = $offset + $len;

    // version
    list($t, $l) = snmpReadHeader($data, $offset);
    if ($t !== 0x02) {
        return null;
    }
    $version = snmpReadInteger($data, $offset, $l);

    // community
    list($t, $l) = snmpReadHeader($data, $offset);
    if ($t !== 0x04) {
        return null;
    }
    $community = substr($data, $offset, $l);
    $offset += $l;

    // PDU
    if ($offset >= $msgEnd) {
        return null;
    }
    $pduType = ord($data[$offset]);
    list($t, $pduLen) = snmpReadHeader($data, $offset);
    if ($pduLen === null) {
        return null;
    }
    $pduEnd = $offset + $pduLen;

    // request-id
    list($t, $l) = snmpReadHeader($data, $offset);
    if ($t !== 0x02) {
        return null;
    }
    $requestId = snmpReadInteger($data, $offset, $l);

    // error-status
    list($t, $l) = snmpReadHeader($data, $offset);
    if ($t !== 0x02) {
        return null;
    }
    $errorStatus = snmpReadInteger($data, $offset, $l);

    // error-index
    list($t, $l) = snmpReadHeader($data, $offset);
    if ($t !== 0x02) {
        return null;
    }
    $errorIndex = snmpReadInteger($data, $offset, $l);

    // varbind-list
    if ($offset >= $pduEnd) {
        return null;
    }
    list($t, $l) = snmpReadHeader($data, $offset);
    if ($t !== 0x30) {
        return null;
    }
    $vbListEnd = $offset + $l;

    // varbind
    list($t, $l) = snmpReadHeader($data, $offset);
    if ($t !== 0x30) {
        return null;
    }
    $vbEnd = $offset + $l;

    // OID
    list($t, $l) = snmpReadHeader($data, $offset);
    if ($t !== 0x06) {
        return null;
    }
    $oidRaw = substr($data, $offset, $l);
    $offset += $l;

    // value
    if ($offset >= $vbEnd) {
        return null;
    }
    $valueType = ord($data[$offset]);
    list($t, $l) = snmpReadHeader($data, $offset);
    if ($l === null) {
        return null;
    }
    $valueRaw = substr($data, $offset, $l);
    $offset += $l;

    $value = '';
    switch ($valueType) {
        case 0x04: // OCTET STRING
            $value = $valueRaw;
            break;
        case 0x06: // OID
            $value = snmpDecodeOid($valueRaw);
            break;
        case 0x02: // INTEGER
            $value = (string)snmpReadIntegerRaw($valueRaw);
            break;
        case 0x43: // TimeTicks
            $value = 'TimeTicks: ' . hexdec(bin2hex($valueRaw));
            break;
        default:
            $value = 'type_' . sprintf('0x%02X', $valueType);
    }

    return [
        'version' => $version,
        'community' => $community,
        'pduType' => $pduType,
        'requestId' => $requestId,
        'errorStatus' => $errorStatus,
        'errorIndex' => $errorIndex,
        'value' => $value
    ];
}

/**
 * 读取 BER 头（type + length），返回 [type, length]
 */
function snmpReadHeader(&$data, &$offset)
{
    if (!isset($data[$offset])) {
        return [null, null];
    }
    $type = ord($data[$offset]);
    $offset++;
    if (!isset($data[$offset])) {
        return [null, null];
    }
    $first = ord($data[$offset]);
    $offset++;
    if ($first < 0x80) {
        return [$type, $first];
    }
    $numBytes = $first & 0x7F;
    $length = 0;
    for ($i = 0; $i < $numBytes; $i++) {
        if (!isset($data[$offset])) {
            return [null, null];
        }
        $length = ($length << 8) | ord($data[$offset]);
        $offset++;
    }
    return [$type, $length];
}

/**
 * 读取 INTEGER 值（带符号）
 */
function snmpReadInteger(&$data, &$offset, $length)
{
    $raw = substr($data, $offset, $length);
    $offset += $length;
    return snmpReadIntegerRaw($raw);
}

function snmpReadIntegerRaw($raw)
{
    if ($raw === '') {
        return 0;
    }
    $value = 0;
    $len = strlen($raw);
    $first = ord($raw[0]);
    for ($i = 0; $i < $len; $i++) {
        $value = ($value << 8) | ord($raw[$i]);
    }
    // 处理负数（首位为1）
    if ($first & 0x80) {
        $value -= (1 << (8 * $len));
    }
    return $value;
}

/**
 * OID 解码（BER -> 点分十进制）
 */
function snmpDecodeOid($raw)
{
    if ($raw === '') {
        return '';
    }
    $parts = [];
    $first = ord($raw[0]);
    $parts[] = intdiv($first, 40);
    $parts[] = $first % 40;

    $value = 0;
    $len = strlen($raw);
    for ($i = 1; $i < $len; $i++) {
        $byte = ord($raw[$i]);
        $value = ($value << 7) | ($byte & 0x7F);
        if (!($byte & 0x80)) {
            $parts[] = $value;
            $value = 0;
        }
    }
    return implode('.', $parts);
}

/**
 * SNMP 错误状态文本
 */
function snmpErrorText($status)
{
    $map = [
        0 => 'noError',
        1 => 'tooBig',
        2 => 'noSuchName',
        3 => 'badValue',
        4 => 'readOnly',
        5 => 'genErr',
        6 => 'noAccess',
        7 => 'wrongType',
        8 => 'wrongLength',
        9 => 'wrongEncoding',
        10 => 'wrongValue',
        11 => 'noCreation',
        12 => 'inconsistentValue',
        13 => 'resourceUnavailable',
        14 => 'commitFailed',
        15 => 'undoFailed',
        16 => 'authorizationError',
        17 => 'notWritable',
        18 => 'inconsistentName'
    ];
    return isset($map[$status]) ? $map[$status] . " (状态码{$status})" : "未知错误 (状态码{$status})";
}
