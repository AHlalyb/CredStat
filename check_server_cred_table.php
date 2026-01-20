<?php
/**
 * 检查server_cred表结构
 */

// 设置错误报告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "开始检查server_cred表结构...\n";

// 加载数据库配置
$dbConfig = require __DIR__ . '/app/config/database.php';

try {
    // 连接数据库
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "数据库连接成功\n";
    
    // 查询表结构
    echo "\n1. 查询server_cred表结构...\n";
    $tableInfo = $pdo->query("DESCRIBE server_cred")->fetchAll();
    
    echo "表结构如下：\n";
    echo "+----------------------------+-------------+------+-----+---------+----------------+\n";
    echo "| Field                      | Type        | Null | Key | Default | Extra          |\n";
    echo "+----------------------------+-------------+------+-----+---------+----------------+\n";
    
    foreach ($tableInfo as $field) {
        printf("| %-30s | %-11s | %-4s | %-3s | %-7s | %-16s |\n", 
            $field['Field'], 
            $field['Type'], 
            $field['Null'], 
            $field['Key'], 
            $field['Default'] ?? 'NULL', 
            $field['Extra']
        );
    }
    
    echo "+----------------------------+-------------+------+-----+---------+----------------+\n";
    
    // 查询一些示例数据，确认ID列
    echo "\n2. 查询server_cred表中的示例数据...\n";
    $sampleData = $pdo->query("SELECT * FROM server_cred LIMIT 3")->fetchAll();
    
    if ($sampleData) {
        echo "示例数据（仅显示ID和服务器名称）：\n";
        foreach ($sampleData as $index => $row) {
            echo "记录 " . ($index + 1) . ":\n";
            foreach ($row as $key => $value) {
                if (strpos($key, 'id') !== false || strpos($key, 'server_name') !== false) {
                    echo "  - {$key}: {$value}\n";
                }
            }
        }
    } else {
        echo "表中暂无数据\n";
    }
    
    echo "\n3. 检查server_cred_volu_info表结构（与磁盘信息相关）...\n";
    $voluTableInfo = $pdo->query("DESCRIBE server_cred_volu_info")->fetchAll();
    
    echo "表结构如下：\n";
    echo "+----------------------------+-------------+------+-----+---------+----------------+\n";
    echo "| Field                      | Type        | Null | Key | Default | Extra          |\n";
    
    foreach ($voluTableInfo as $field) {
        printf("| %-30s | %-11s | %-4s | %-3s | %-7s | %-16s |\n", 
            $field['Field'], 
            $field['Type'], 
            $field['Null'], 
            $field['Key'], 
            $field['Default'] ?? 'NULL', 
            $field['Extra']
        );
    }
    
    echo "+----------------------------+-------------+------+-----+---------+----------------+\n";
    
    echo "\n🎉 表结构检查完成\n";
    
} catch (PDOException $e) {
    echo "❌ 数据库错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 测试错误: " . $e->getMessage() . "\n";
}
