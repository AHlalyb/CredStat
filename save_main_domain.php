<?php
/**
 * 保存主域名信息
 * 处理主域名表单数据的提交
 */

// 设置响应头
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 设置错误报告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 加载数据库配置
try {
    // 检查文件是否存在
    $dbConfigPath = __DIR__ . '/app/config/database.php';
    if (!file_exists($dbConfigPath)) {
        throw new Exception('数据库配置文件不存在: ' . $dbConfigPath);
    }
    
    $dbConfig = require $dbConfigPath;
    if (!is_array($dbConfig)) {
        throw new Exception('数据库配置文件未返回有效数组');
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => '加载数据库配置失败: ' . $e->getMessage()
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// 初始化响应
$response = [
    'success' => false,
    'message' => '保存失败',
    'data' => []
];

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 获取请求数据
        $rawInput = file_get_contents('php://input');
        $requestData = json_decode($rawInput, true);
        
        // 验证请求数据
        if (empty($requestData)) {
            $response['message'] = '无效的请求数据';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 提取主域名信息
        $domainName = $requestData['domainName'] ?? '';
        $registerDate = $requestData['registerDate'] ?? '';
        $expiryDate = $requestData['expiryDate'] ?? '';
        
        // 转换日期格式：从ISO 8601到YYYY-MM-DD
        try {
            $registerDateObj = new DateTime($registerDate);
            $registerDate = $registerDateObj->format('Y-m-d');
            
            $expiryDateObj = new DateTime($expiryDate);
            $expiryDate = $expiryDateObj->format('Y-m-d');
        } catch (Exception $e) {
            $response['message'] = '日期格式无效: ' . $e->getMessage();
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 验证必填字段
        if (empty($domainName) || empty($registerDate) || empty($expiryDate)) {
            $response['message'] = '请填写所有必填字段';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 连接数据库
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        
        // 开始事务
        $pdo->beginTransaction();
        
        // 插入主域名信息
        $mainDomainSql = "INSERT INTO main_domain_info (main_domain_info_name, main_domain_info_regist_date, main_domain_info_expire_date) VALUES (:main_domain_info_name, :main_domain_info_regist_date, :main_domain_info_expire_date)";
        
        $mainDomainStmt = $pdo->prepare($mainDomainSql);
        $mainDomainStmt->bindValue(':main_domain_info_name', $domainName, PDO::PARAM_STR);
        $mainDomainStmt->bindValue(':main_domain_info_regist_date', $registerDate, PDO::PARAM_STR);
        $mainDomainStmt->bindValue(':main_domain_info_expire_date', $expiryDate, PDO::PARAM_STR);
        $mainDomainStmt->execute();
        
        // 获取插入的主域名ID
        $mainDomainId = $pdo->lastInsertId();
        
        // 提交事务
        $pdo->commit();
        
        // 更新响应
        $response['success'] = true;
        $response['message'] = '保存成功';
        $response['data'] = [
            'mainDomainId' => $mainDomainId
        ];
        
    } catch (PDOException $e) {
        // 回滚事务
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['message'] = '数据库操作错误: ' . $e->getMessage();
        error_log('保存主域名失败: ' . $e->getMessage());
    } catch (Exception $e) {
        // 回滚事务
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['message'] = '操作错误: ' . $e->getMessage();
        error_log('保存主域名失败: ' . $e->getMessage());
    }
} else {
    $response['message'] = '仅支持POST请求';
}

// 返回响应
echo json_encode($response, JSON_UNESCAPED_UNICODE);
