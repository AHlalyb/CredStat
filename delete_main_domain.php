<?php
/**
 * 删除主域名
 * 根据ID删除指定的主域名信息
 */

// 设置响应头
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

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
    'message' => '删除失败',
    'data' => null
];

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 获取请求体
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        // 验证参数
        if (!isset($data['id']) || empty($data['id'])) {
            throw new Exception('缺少必要参数: id');
        }
        
        $id = intval($data['id']);
        
        // 连接数据库
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        
        // 执行删除操作
        $sql = "DELETE FROM main_domain_info WHERE main_domain_info_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        // 检查是否有行被删除
        if ($stmt->rowCount() > 0) {
            // 删除成功
            $response['success'] = true;
            $response['message'] = '主域名删除成功';
        } else {
            // 没有行被删除
            $response['message'] = '未找到要删除的主域名';
        }
        
    } catch (PDOException $e) {
        $response['message'] = '数据库操作错误: ' . $e->getMessage();
        error_log('删除主域名失败: ' . $e->getMessage());
    } catch (Exception $e) {
        $response['message'] = '操作错误: ' . $e->getMessage();
        error_log('删除主域名失败: ' . $e->getMessage());
    }
} else {
    $response['message'] = '仅支持POST请求';
}

// 返回响应
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>