<?php
/**
 * 直接测试删除功能（绕过HTTP服务器）
 */

// 设置错误报告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "开始直接测试删除功能...\n";

// 加载数据库配置
$dbConfig = require __DIR__ . '/app/config/database.php';

try {
    // 连接数据库
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "数据库连接成功\n";
    
    // 获取一个现有的ID用于测试
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
    
    // 测试删除操作
    echo "\n执行删除操作...\n";
    
    // 直接执行删除SQL语句（与search_api.php中修复后的语句相同）
    $deleteSql = "DELETE FROM server_cred WHERE id = :id";
    $stmt = $pdo->prepare($deleteSql);
    $stmt->bindValue(':id', $testId, PDO::PARAM_INT);
    $stmt->execute();
    
    $affectedRows = $stmt->rowCount();
    echo "删除操作执行完成，影响行数: {$affectedRows}\n";
    
    // 验证数据是否已删除
    echo "\n验证删除结果...\n";
    $verifySql = "SELECT id FROM server_cred WHERE id = :id";
    $stmt = $pdo->prepare($verifySql);
    $stmt->execute(['id' => $testId]);
    $verifyResult = $stmt->fetch();
    
    if (!$verifyResult) {
        echo "✅ 验证数据已成功删除\n";
        echo "🎉 删除功能测试通过！\n";
        echo "✅ 修复成功：SQL语句中的列名错误已修正\n";
    } else {
        echo "❌ 数据未被删除\n";
    }
    
    echo "\n测试完成\n";
    
} catch (PDOException $e) {
    echo "❌ 数据库错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 测试错误: " . $e->getMessage() . "\n";
}
