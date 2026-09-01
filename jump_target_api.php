<?php
/**
 * jump_target_api.php
 * 跳板目标配置管理接口（CRUD）
 *
 * 跳板目标类型：
 *   - agent   : 目标网络内部署的 agent 代理程序，中心网关经 agent TCP 隧道直连目标设备
 *   - ssh     : 中心网关 SSH 登录跳板机后，在 CLI 执行 telnet/ssh 目标IP 跳转
 *   - telnet  : 中心网关 Telnet 登录跳板机后，在 CLI 执行 telnet/ssh 目标IP 跳转
 */

require_once __DIR__ . '/app/utils/SecurityUtils.php';

$securityConfig = require __DIR__ . '/app/config/security.php';
if (isset($securityConfig['headers'])) {
    SecurityUtils::setSecureHeaders($securityConfig['headers']);
}
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $dbConfig = require __DIR__ . '/app/config/database.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '数据库配置加载失败']);
    exit;
}

try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '数据库连接失败']);
    exit;
}

// 获取请求数据（JSON body 优先）
$rawBody = file_get_contents('php://input');
$requestData = json_decode($rawBody, true);
if (!is_array($requestData)) {
    $requestData = $_POST;
}
$action = isset($requestData['action']) ? trim($requestData['action']) : '';
$username = isset($requestData['username']) ? trim($requestData['username']) : '';
if ($username === '' && isset($_SERVER['HTTP_X_USERNAME'])) {
    $username = trim($_SERVER['HTTP_X_USERNAME']);
}

// 校验用户权限：有对应操作权限 或 有管理权限 即通过
function checkUserPerm($pdo, $username, $permField) {
    if ($username === '') {
        return false;
    }
    try {
        $stmt = $pdo->prepare("SELECT {$permField}, credstat_user_perm_manage FROM credstat_user WHERE credstat_user_account = ?");
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }
        return intval($row[$permField]) === 1 || intval($row['credstat_user_perm_manage']) === 1;
    } catch (Exception $e) {
        return false;
    }
}

switch ($action) {
    case 'list':
        try {
            $stmt = $pdo->query(
                "SELECT jump_target_id, jump_target_name, jump_target_type,
                        jump_target_ip, jump_target_port, jump_target_username,
                        jump_target_remark, created_at, updated_at
                 FROM jump_target ORDER BY jump_target_id ASC"
            );
            $response['success'] = true;
            $response['data'] = $stmt->fetchAll();
        } catch (PDOException $e) {
            $response['message'] = '查询失败: ' . $e->getMessage();
        }
        break;

    case 'create':
        if (!checkUserPerm($pdo, $username, 'credstat_user_perm_add')) {
            $response['message'] = '您没有新增权限';
            break;
        }
        $name = isset($requestData['name']) ? SecurityUtils::sanitizeInput(trim($requestData['name'])) : '';
        $type = isset($requestData['type']) ? strtolower(trim($requestData['type'])) : '';
        $ip = isset($requestData['ip']) ? SecurityUtils::sanitizeInput(trim($requestData['ip'])) : '';
        $port = isset($requestData['port']) ? intval($requestData['port']) : null;
        $jtUsername = isset($requestData['jt_username']) ? SecurityUtils::sanitizeInput(trim($requestData['jt_username'])) : '';
        $password = isset($requestData['password']) ? (string)$requestData['password'] : '';
        $token = isset($requestData['token']) ? (string)$requestData['token'] : '';
        $remark = isset($requestData['remark']) ? SecurityUtils::sanitizeInput(trim($requestData['remark'])) : '';

        if ($name === '') {
            $response['message'] = '请输入跳板目标名称';
            break;
        }
        if (!in_array($type, ['agent', 'ssh', 'telnet'])) {
            $response['message'] = '跳板类型必须为 agent/ssh/telnet';
            break;
        }
        if ($ip === '') {
            $response['message'] = '请输入 IP 地址';
            break;
        }
        if ($port === null || $port <= 0 || $port > 65535) {
            $response['message'] = '请输入正确的端口';
            break;
        }
        if (in_array($type, ['ssh', 'telnet']) && $password === '') {
            $response['message'] = 'SSH/Telnet 类型必须填写登录密码';
            break;
        }

        try {
            // agent 类型：jump_target_password_hash 字段存放共享密钥 token（可为空=不校验）
            $secretValue = $type === 'agent' ? $token : $password;
            $encryptedPassword = SecurityUtils::encrypt($secretValue);
            $stmt = $pdo->prepare(
                "INSERT INTO jump_target
                    (jump_target_name, jump_target_type, jump_target_ip, jump_target_port,
                     jump_target_username, jump_target_password_hash, jump_target_remark, jump_target_created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$name, $type, $ip, $port, $jtUsername, $encryptedPassword, $remark, $username]);
            $response['success'] = true;
            $response['message'] = '跳板目标创建成功';
            $response['id'] = intval($pdo->lastInsertId());
        } catch (PDOException $e) {
            $response['message'] = '创建失败: ' . $e->getMessage();
        }
        break;

    case 'update':
        if (!checkUserPerm($pdo, $username, 'credstat_user_perm_edit')) {
            $response['message'] = '您没有修改权限';
            break;
        }
        $id = isset($requestData['id']) ? intval($requestData['id']) : 0;
        if ($id <= 0) {
            $response['message'] = '缺少跳板目标ID';
            break;
        }
        $name = isset($requestData['name']) ? SecurityUtils::sanitizeInput(trim($requestData['name'])) : '';
        $type = isset($requestData['type']) ? strtolower(trim($requestData['type'])) : '';
        $ip = isset($requestData['ip']) ? SecurityUtils::sanitizeInput(trim($requestData['ip'])) : '';
        $port = isset($requestData['port']) ? intval($requestData['port']) : null;
        $jtUsername = isset($requestData['jt_username']) ? SecurityUtils::sanitizeInput(trim($requestData['jt_username'])) : '';
        $password = isset($requestData['password']) ? (string)$requestData['password'] : '';
        $token = isset($requestData['token']) ? (string)$requestData['token'] : '';
        $remark = isset($requestData['remark']) ? SecurityUtils::sanitizeInput(trim($requestData['remark'])) : '';

        if ($name === '') {
            $response['message'] = '请输入跳板目标名称';
            break;
        }
        if (!in_array($type, ['agent', 'ssh', 'telnet'])) {
            $response['message'] = '跳板类型必须为 agent/ssh/telnet';
            break;
        }
        if ($ip === '') {
            $response['message'] = '请输入 IP 地址';
            break;
        }
        if ($port === null || $port <= 0 || $port > 65535) {
            $response['message'] = '请输入正确的端口';
            break;
        }

        try {
            // agent 类型用 token（可为空=不修改），ssh/telnet 用密码（留空=不修改）
            $secretValue = $type === 'agent' ? $token : $password;
            if ($secretValue !== '') {
                // 密钥有变更则更新
                $encryptedPassword = SecurityUtils::encrypt($secretValue);
                $stmt = $pdo->prepare(
                    "UPDATE jump_target SET
                        jump_target_name = ?, jump_target_type = ?, jump_target_ip = ?,
                        jump_target_port = ?, jump_target_username = ?,
                        jump_target_password_hash = ?, jump_target_remark = ?
                     WHERE jump_target_id = ?"
                );
                $stmt->execute([$name, $type, $ip, $port, $jtUsername, $encryptedPassword, $remark, $id]);
            } else {
                // 密钥留空表示不修改
                $stmt = $pdo->prepare(
                    "UPDATE jump_target SET
                        jump_target_name = ?, jump_target_type = ?, jump_target_ip = ?,
                        jump_target_port = ?, jump_target_username = ?, jump_target_remark = ?
                     WHERE jump_target_id = ?"
                );
                $stmt->execute([$name, $type, $ip, $port, $jtUsername, $remark, $id]);
            }
            $response['success'] = true;
            $response['message'] = '跳板目标更新成功';
        } catch (PDOException $e) {
            $response['message'] = '更新失败: ' . $e->getMessage();
        }
        break;

    case 'delete':
        if (!checkUserPerm($pdo, $username, 'credstat_user_perm_delete')) {
            $response['message'] = '您没有删除权限';
            break;
        }
        $id = isset($requestData['id']) ? intval($requestData['id']) : 0;
        if ($id <= 0) {
            $response['message'] = '缺少跳板目标ID';
            break;
        }
        try {
            // 检查是否有设备引用该跳板目标
            $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM net_dev_cred WHERE net_dev_cred_jump_id = ?");
            $stmt->execute([$id]);
            $cnt = intval($stmt->fetch()['cnt']);
            if ($cnt > 0) {
                $response['message'] = "该跳板目标正被 {$cnt} 台设备使用，请先解除设备绑定";
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM jump_target WHERE jump_target_id = ?");
            $stmt->execute([$id]);
            $response['success'] = true;
            $response['message'] = '跳板目标已删除';
        } catch (PDOException $e) {
            $response['message'] = '删除失败: ' . $e->getMessage();
        }
        break;

    default:
        $response['message'] = '未知操作: ' . $action;
        break;
}

echo json_encode($response);
