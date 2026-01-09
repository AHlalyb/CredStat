<?php
// 设置字符编码为UTF-8
header('Content-Type: application/json; charset=UTF-8');

// 初始化响应数组
$response = array(
    'success' => false,
    'message' => ''
);

// 数据处理在后面的代码中完成

// 加载数据库配置文件
$dbConfig = require __DIR__ . '/app/config/database.php';

// 检查必要的POST参数
$requiredFields = ['systemName', 'ipUrl', 'loginType', 'username', 'password'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        $response['message'] = '缺少必填字段: ' . $field;
        echo json_encode($response);
        exit;
    }
}

// 检查配置是否完整
$requiredConfig = array('host', 'port', 'dbname', 'username');
foreach ($requiredConfig as $key) {
    if (!isset($dbConfig[$key]) || empty($dbConfig[$key])) {
        $response['message'] = '数据库配置不完整，请先配置数据库连接：缺少 ' . $key;
        echo json_encode($response);
        exit;
    }
}

// 密码可以为空，只需要检查是否设置
if (!isset($dbConfig['password'])) {
    $dbConfig['password'] = '';
}

// 尝试连接数据库
$conn = null;
try {
    // 使用PDO连接MySQL
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
    $conn = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // 加载SecurityUtils类
    require_once __DIR__ . '/app/utils/SecurityUtils.php';
    
    // 获取POST数据
    $systemName = trim($_POST['systemName']);
    $ipUrl = trim($_POST['ipUrl']);
    $loginType = trim($_POST['loginType']);
    $loginUsername = trim($_POST['username']);
    $loginPassword = trim($_POST['password']);
    $remark = isset($_POST['remark']) ? trim($_POST['remark']) : '';
    
    // 使用SecurityUtils类进行可逆加密
    $encryptedPassword = SecurityUtils::encrypt($loginPassword);
    
    // 准备SQL语句，插入登录信息
    $sql = "INSERT INTO login_info (login_info_system_name, login_info_ip_url, login_info_login_type, 
                                   login_info_username, login_info_password, login_info_remark, 
                                   login_info_created_at, login_info_updated_at) 
           VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $conn->prepare($sql);
    
    // 绑定参数
    $stmt->bindParam(1, $systemName, PDO::PARAM_STR);
    $stmt->bindParam(2, $ipUrl, PDO::PARAM_STR);
    $stmt->bindParam(3, $loginType, PDO::PARAM_STR);
    $stmt->bindParam(4, $loginUsername, PDO::PARAM_STR);
    $stmt->bindParam(5, $encryptedPassword, PDO::PARAM_STR);
    $stmt->bindParam(6, $remark, PDO::PARAM_STR);
    
    // 执行SQL语句
    $stmt->execute();
    
    $response['success'] = true;
    $response['message'] = '系统登录信息保存成功！';
    
} catch (PDOException $e) {
    // 处理数据库错误
    $response['message'] = '数据库操作失败：' . $e->getMessage();
} catch (Exception $e) {
    // 处理其他错误
    $response['message'] = '操作失败：' . $e->getMessage();
} finally {
    // 关闭数据库连接
    if ($conn !== null) {
        $conn = null;
    }
}

// 返回JSON响应
echo json_encode($response);
