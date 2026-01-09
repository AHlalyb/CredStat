<?php
/**
 * 获取主域名列表
 * 返回所有主域名信息
 */

// 设置响应头
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

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
    'message' => '获取失败',
    'data' => []
];

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // 连接数据库
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        
        // 查询主域名列表
        $sql = "SELECT * FROM main_domain_info ORDER BY main_domain_info_id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $mainDomains = $stmt->fetchAll();
        
        // 更新响应
        $response['success'] = true;
        $response['message'] = '获取成功';
        $response['data'] = $mainDomains;
        
    } catch (PDOException $e) {
        $response['message'] = '数据库操作错误: ' . $e->getMessage();
        error_log('获取主域名列表失败: ' . $e->getMessage());
    } catch (Exception $e) {
        $response['message'] = '操作错误: ' . $e->getMessage();
        error_log('获取主域名列表失败: ' . $e->getMessage());
    }
} else {
    $response['message'] = '仅支持GET请求';
}

// 返回响应
echo json_encode($response, JSON_UNESCAPED_UNICODE);
