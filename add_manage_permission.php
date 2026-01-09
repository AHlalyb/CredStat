<?php
/**
 * 添加管理权限字段到用户表
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
    
    // 1. 添加管理权限字段
    $sql = "ALTER TABLE credstat_user ADD COLUMN credstat_user_perm_manage INT(1) NOT NULL DEFAULT 0 COMMENT '管理权限：0-无，1-有'";
    $pdo->exec($sql);
    echo "✓ 成功添加 credstat_user_perm_manage 字段到 credstat_user 表\n";
    
    // 2. 验证字段是否添加成功
    $sql = "DESCRIBE credstat_user";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetchAll();
    
    echo "\n✓ 验证字段添加结果：\n";
    echo "------------------------------------------------------------\n";
    echo sprintf("%-30s %-20s %-10s %-10s %-10s %-20s\n", "Field", "Type", "Null", "Key", "Default", "Extra");
    echo "------------------------------------------------------------\n";
    
    // 只显示与权限相关的字段
    foreach ($result as $field) {
        if (strpos($field['Field'], 'perm') !== false) {
            echo sprintf("%-30s %-20s %-10s %-10s %-10s %-20s\n", 
                $field['Field'], 
                $field['Type'], 
                $field['Null'], 
                $field['Key'], 
                $field['Default'], 
                $field['Extra']
            );
        }
    }
    
    echo "------------------------------------------------------------\n";
    echo "\n✓ 操作完成！管理权限字段已成功添加。\n";
    
} catch (PDOException $e) {
    echo "✗ 数据库操作错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ 操作错误: " . $e->getMessage() . "\n";
}