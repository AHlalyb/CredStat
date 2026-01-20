<?php
/**
 * 测试服务器账号密码删除功能
 */

// 设置错误报告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "开始测试服务器账号密码删除功能...\n";

// 加载数据库配置
$dbConfig = require __DIR__ . '/app/config/database.php';

try {
    // 连接数据库
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "数据库连接成功\n";
    
    // 直接测试删除功能，使用现有数据的ID
    echo "\n1. 测试删除功能...\n";
    
    // 首先获取一个现有的ID用于测试
    $existingIdResult = $pdo->query("SELECT id FROM server_cred LIMIT 1")->fetch();
    
    if ($existingIdResult) {
        $testId = $existingIdResult['id'];
        echo "使用现有记录ID: {$testId} 进行测试\n";
    } else {
        echo "表中暂无数据，将插入一条测试数据...\n";
        // 插入测试数据
        $insertSql = "INSERT INTO server_cred (server_cred_network_area, server_cred_server_type, server_cred_server_name, server_cred_server_ip, server_cred_server_os, server_cred_login_username, server_cred_login_password, server_cred_created_by) VALUES ('内网', '物理机', '测试服务器_' . time(), '192.168.1.100', 'Linux', 'testuser', 'testpass', 'system')";
        $pdo->exec($insertSql);
        $testId = $pdo->lastInsertId();
        echo "✅ 测试数据插入成功，ID: {$testId}\n";
    }
    
    // 构建删除请求数据
    $deleteRequestData = [
        'action' => 'delete',
        'type' => 'server_cred',
        'id' => $testId
    ];
    
    // 发送请求到search_api.php
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:3006/search_api.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($deleteRequestData));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP状态码: {$httpCode}\n";
    echo "响应内容: {$response}\n";
    
    $responseData = json_decode($response, true);
    if ($responseData && $responseData['success']) {
        echo "✅ 删除请求成功\n";
        
        // 验证数据是否已删除
        $verifySql = "SELECT id FROM server_cred WHERE id = :id";
        $stmt = $pdo->prepare($verifySql);
        $stmt->execute(['id' => $testId]);
        $verifyResult = $stmt->fetch();
        
        if (!$verifyResult) {
            echo "✅ 验证数据已成功删除\n";
            echo "🎉 删除功能测试通过！\n";
        } else {
            echo "❌ 数据未被删除\n";
        }
    } else {
        echo "❌ 删除请求失败: " . ($responseData['message'] ?? '未知错误') . "\n";
    }
    
    echo "\n测试完成\n";
    
} catch (PDOException $e) {
    echo "❌ 数据库错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 测试错误: " . $e->getMessage() . "\n";
}
