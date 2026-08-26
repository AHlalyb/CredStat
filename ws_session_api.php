<?php
/**
 * 终端网关会话凭据下发接口
 *
 * action=create  前端调用：校验登录用户 + 设备存在 → 解密凭据 → 生成一次性 ticket（TTL 60 秒）写入临时文件
 * action=redeem  网关调用：携带 X-Gateway-Key 校验 → 换取设备连接凭据 → 立即作废 ticket
 *
 * 设计要点：
 *  - 设备密码永远不会下发到浏览器，只在内网 PHP ↔ 网关之间传递
 *  - ticket 一次性 + 短 TTL，即使泄露也仅可用于一次短时连接
 *  - redeem 必须携带与网关 config.json 一致的 gateway_key，防止端口直接暴露时被冒用
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/utils/SecurityUtils.php';

// ===== 配置 =====
// 网关共享密钥：必须与 terminal-gateway/config.json 中的 gateway_key 完全一致
$gatewayKey = 'Gk7f9xQ2tR5vLm8nP3wZc4yB';
// ticket 临时文件目录（PHP 可写）
$ticketDir = __DIR__ . '/app/config/terminal_sessions';
// ticket 有效期（秒）
$ticketTTL = 60;

/**
 * 输出 JSON 并结束
 */
function terminalJsonOut($data)
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== 数据库连接 =====
$dbConfig = require __DIR__ . '/app/config/database.php';
$dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    error_log('ws_session_api: DB连接失败 ' . $e->getMessage());
    terminalJsonOut(['success' => false, 'message' => '数据库连接失败']);
}

// 确保 ticket 目录可写
if (!is_dir($ticketDir)) {
    @mkdir($ticketDir, 0755, true);
}

$action = $_GET['action'] ?? '';

// ===== action=create：前端换取 ticket =====
if ($action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }
    $account = trim((string)($input['account'] ?? ''));
    $deviceId = intval($input['deviceId'] ?? 0);

    if ($account === '' || $deviceId <= 0) {
        terminalJsonOut(['success' => false, 'message' => '参数错误：缺少账号或设备ID']);
    }

    // 1. 校验用户：存在、启用、有查询权限（与系统权限模型一致）
    $userStmt = $pdo->prepare(
        "SELECT credstat_user_id FROM credstat_user
         WHERE credstat_user_account = :account AND credstat_user_status = 1 AND credstat_user_perm_query = 1"
    );
    $userStmt->execute([':account' => $account]);
    if (!$userStmt->fetch()) {
        terminalJsonOut(['success' => false, 'message' => '无权限或用户不存在']);
    }

    // 2. 查询设备凭据
    $devStmt = $pdo->prepare(
        "SELECT net_dev_cred_management_ip, net_dev_cred_protocol, net_dev_cred_port,
                net_dev_cred_username, net_dev_cred_password_hash
         FROM net_dev_cred WHERE id = :id"
    );
    $devStmt->execute([':id' => $deviceId]);
    $dev = $devStmt->fetch();

    if (!$dev || empty($dev['net_dev_cred_management_ip'])) {
        terminalJsonOut(['success' => false, 'message' => '设备不存在或未配置管理IP']);
    }

    // 3. 解密设备密码
    $password = SecurityUtils::decrypt($dev['net_dev_cred_password_hash']);
    if ($password === null || $password === false || $password === '') {
        terminalJsonOut(['success' => false, 'message' => '设备密码解密失败']);
    }

    // 4. 生成一次性 ticket 并落盘
    $ticket = bin2hex(random_bytes(16));
    $session = [
        'ip'       => $dev['net_dev_cred_management_ip'],
        'port'     => (string)($dev['net_dev_cred_port'] ?: 22),
        'username' => (string)($dev['net_dev_cred_username'] ?: ''),
        'password' => $password,
        'protocol' => strtolower((string)$dev['net_dev_cred_protocol']),
        'expire'   => time() + $ticketTTL,
    ];
    $ticketFile = $ticketDir . '/' . $ticket . '.json';

    // 顺带清理过期的历史 ticket
    foreach (glob($ticketDir . '/*.json') ?: [] as $f) {
        if (is_file($f) && @filemtime($f) < time() - $ticketTTL) {
            @unlink($f);
        }
    }

    if (file_put_contents($ticketFile, json_encode($session)) === false) {
        error_log('ws_session_api: ticket 写入失败 ' . $ticketFile);
        terminalJsonOut(['success' => false, 'message' => '会话创建失败，请检查目录权限']);
    }

    terminalJsonOut(['success' => true, 'ticket' => $ticket]);
}

// ===== action=redeem：网关换取凭据（一次性） =====
if ($action === 'redeem') {
    // 校验网关密钥
    if (($_SERVER['HTTP_X_GATEWAY_KEY'] ?? '') !== $gatewayKey) {
        http_response_code(403);
        terminalJsonOut(['success' => false, 'message' => 'forbidden']);
    }

    $ticket = trim((string)($_GET['ticket'] ?? ''));
    if (!preg_match('/^[a-f0-9]{32}$/', $ticket)) {
        terminalJsonOut(['success' => false, 'message' => 'ticket 格式无效']);
    }

    $ticketFile = $ticketDir . '/' . $ticket . '.json';
    if (!is_file($ticketFile)) {
        terminalJsonOut(['success' => false, 'message' => 'ticket 无效或已过期']);
    }

    $session = json_decode((string)file_get_contents($ticketFile), true);
    @unlink($ticketFile); // 一次性：无论成败立即作废

    if (!is_array($session) || time() > (int)($session['expire'] ?? 0)) {
        terminalJsonOut(['success' => false, 'message' => 'ticket 已过期']);
    }

    terminalJsonOut([
        'success'  => true,
        'ip'       => $session['ip'],
        'port'     => $session['port'],
        'username' => $session['username'],
        'password' => $session['password'],
        'protocol' => $session['protocol'],
    ]);
}

terminalJsonOut(['success' => false, 'message' => '未知操作']);
