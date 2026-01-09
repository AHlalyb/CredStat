<?php
/**
 * 保存二级域名及证书信息
 * 处理二级域名及证书数据的提交
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
    $dbConfig = require __DIR__ . '/app/config/database.php';
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
        
        // 提取二级域名及证书信息
        $mainDomainId = $requestData['mainDomain'] ?? '';
        $subDomain = $requestData['subDomain'] ?? '';
        $mappingIp = $requestData['mappingIp'] ?? '';
        $serverIpPort = $requestData['serverIpPort'] ?? '';
        $certExpiry = $requestData['certExpiry'] ?? '';
        $certStatus = $requestData['certStatus'] ?? '';
        $desc = $requestData['desc'] ?? '';
        $notes = $requestData['notes'] ?? '';
        
        // 转换日期格式：从ISO 8601到YYYY-MM-DD
        if (!empty($certExpiry)) {
            try {
                $certExpiryObj = new DateTime($certExpiry);
                $certExpiry = $certExpiryObj->format('Y-m-d');
            } catch (Exception $e) {
                $response['message'] = '证书到期日期格式无效: ' . $e->getMessage();
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }
        } else {
            // 空值处理
            $certExpiry = null;
        }
        
        // 验证必填字段
        if (empty($mainDomainId) || empty($subDomain)) {
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
        
        // 插入二级域名及证书信息
        $subDomainSql = "INSERT INTO sub_domain_info (sub_domain_info_main_domain_id, sub_domain_info_name, sub_domain_info_public_ip, sub_domain_info_server_addr, sub_domain_info_cert_expiry_date, sub_domain_info_cert_status, sub_domain_info_desc, sub_domain_info_notes) VALUES (:sub_domain_info_main_domain_id, :sub_domain_info_name, :sub_domain_info_public_ip, :sub_domain_info_server_addr, :sub_domain_info_cert_expiry_date, :sub_domain_info_cert_status, :sub_domain_info_desc, :sub_domain_info_notes)";
        
        $subDomainStmt = $pdo->prepare($subDomainSql);
        $subDomainStmt->bindValue(':sub_domain_info_main_domain_id', $mainDomainId, PDO::PARAM_INT);
        $subDomainStmt->bindValue(':sub_domain_info_name', $subDomain, PDO::PARAM_STR);
        $subDomainStmt->bindValue(':sub_domain_info_public_ip', $mappingIp, is_null($mappingIp) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $subDomainStmt->bindValue(':sub_domain_info_server_addr', $serverIpPort, is_null($serverIpPort) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $subDomainStmt->bindValue(':sub_domain_info_cert_expiry_date', $certExpiry, is_null($certExpiry) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $subDomainStmt->bindValue(':sub_domain_info_cert_status', $certStatus, is_null($certStatus) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $subDomainStmt->bindValue(':sub_domain_info_desc', $desc, PDO::PARAM_STR);
        $subDomainStmt->bindValue(':sub_domain_info_notes', $notes, PDO::PARAM_STR);
        $subDomainStmt->execute();
        
        // 获取插入的二级域名ID
        $subDomainId = $pdo->lastInsertId();
        
        // 提交事务
        $pdo->commit();
        
        // 更新响应
        $response['success'] = true;
        $response['message'] = '保存成功';
        $response['data'] = [
            'subDomainId' => $subDomainId
        ];
        
    } catch (PDOException $e) {
        // 回滚事务
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['message'] = '数据库操作错误: ' . $e->getMessage();
        error_log('保存二级域名及证书失败: ' . $e->getMessage());
    } catch (Exception $e) {
        // 回滚事务
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['message'] = '操作错误: ' . $e->getMessage();
        error_log('保存二级域名及证书失败: ' . $e->getMessage());
    }
} else {
    $response['message'] = '仅支持POST请求';
}

// 返回响应
echo json_encode($response, JSON_UNESCAPED_UNICODE);
