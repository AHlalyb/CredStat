<?php
/**
 * network_manage_api.php
 * 网络设备管理接口
 * 提供：ping 测试、SNMP 测试
 * 请求方式：POST JSON
 * 请求参数：
 *   action    : ping | snmp
 *   username  : 当前登录用户名（鉴权）
 *   ip        : 管理IP（ping/snmp 均需要）
 *   id        : 网络设备记录ID（snmp 从数据库取 SNMP 团体字时使用）
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

    switch ($action) {
        case 'ping':
            handlePing($requestData);
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
        echo json_encode([
            'success' => true,
            'message' => "SNMP 测试成功，设备返回系统描述：\n" . $result['sysDescr'],
            'data' => [
                'ip' => $ip,
                'community' => $community,
                'sysDescr' => $result['sysDescr'],
                'raw' => isset($result['raw']) ? base64_encode($result['raw']) : null
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => "SNMP 测试失败：" . $result['error'],
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
