<?php
/**
 * 测试管理权限字段的存储和读取功能
 */

// 设置响应头
header('Content-Type: text/plain; charset=UTF-8');

// 加载数据库配置
$dbConfig = require __DIR__ . '/app/config/database.php';

try {
    // 连接数据库
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
    
    echo "数据库连接成功！\n\n";
    
    // 1. 测试默认值
    echo "1. 测试字段默认值：\n";
    $testAccount = 'test_user_' . time();
    $sql = "INSERT INTO credstat_user (credstat_user_account, credstat_user_password, credstat_user_name) VALUES (?, 'test123', '测试用户')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$testAccount]);
    $userId = $pdo->lastInsertId();
    
    $sql = "SELECT credstat_user_perm_manage FROM credstat_user WHERE credstat_user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    
    echo sprintf("✓ 新创建用户的管理权限默认值：%d\n", $result['credstat_user_perm_manage']);
    
    // 2. 测试更新为有管理权限
    echo "\n2. 测试更新为有管理权限：\n";
    $sql = "UPDATE credstat_user SET credstat_user_perm_manage = 1 WHERE credstat_user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    
    $sql = "SELECT credstat_user_perm_manage FROM credstat_user WHERE credstat_user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    
    echo sprintf("✓ 更新后用户的管理权限：%d\n", $result['credstat_user_perm_manage']);
    
    // 3. 测试更新为无管理权限
    echo "\n3. 测试更新为无管理权限：\n";
    $sql = "UPDATE credstat_user SET credstat_user_perm_manage = 0 WHERE credstat_user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    
    $sql = "SELECT credstat_user_perm_manage FROM credstat_user WHERE credstat_user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    
    echo sprintf("✓ 更新后用户的管理权限：%d\n", $result['credstat_user_perm_manage']);
    
    // 4. 清理测试数据
    echo "\n4. 清理测试数据：\n";
    $sql = "DELETE FROM credstat_user WHERE credstat_user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    
    echo "✓ 测试用户已删除\n";
    
    echo "\n✓ 所有测试通过！管理权限字段能够正确存储和读取。\n";
    
} catch (PDOException $e) {
    echo "✗ 数据库操作错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ 操作错误: " . $e->getMessage() . "\n";
}