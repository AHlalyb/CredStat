<?php
/**
 * 创建用户表脚本
 * 用于创建credstat_user表
 */

// 设置错误报告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "开始执行创建用户表操作...\n";

// 加载数据库配置
$dbConfig = require __DIR__ . '/app/config/database.php';

try {
    // 创建数据库连接
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
    echo "连接数据库: {$dsn}\n";
    
    $pdo = new PDO(
        $dsn,
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    echo "数据库连接成功\n";
    
    // 读取SQL文件内容
    $sqlFile = __DIR__ . '/create_credstat_user_table.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL文件不存在: {$sqlFile}");
    }
    
    $sqlContent = file_get_contents($sqlFile);
    echo "读取SQL文件成功\n";
    
    // 执行SQL语句
    $pdo->exec($sqlContent);
    echo "成功创建credstat_user表\n";
    
    // 验证表是否创建成功
    $stmt = $pdo->query("SHOW TABLES LIKE 'credstat_user'");
    $result = $stmt->fetchAll();
    
    if (count($result) > 0) {
        echo "验证成功: credstat_user表已存在\n";
        
        // 显示表结构
        echo "表结构信息:\n";
        $stmt = $pdo->query("DESCRIBE credstat_user");
        $tableStructure = $stmt->fetchAll();
        foreach ($tableStructure as $column) {
            echo sprintf("%-30s %-20s %-10s %-10s %-20s\n", 
                $column['Field'], 
                $column['Type'], 
                $column['Null'], 
                $column['Key'], 
                $column['Default'] . ' ' . $column['Extra']
            );
        }
    } else {
        echo "警告: credstat_user表创建失败\n";
    }
    
} catch (PDOException $e) {
    echo "\n数据库操作错误: " . $e->getMessage() . "\n";
    echo "错误代码: " . $e->getCode() . "\n";
    echo "堆栈跟踪: " . $e->getTraceAsString() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n操作异常: " . $e->getMessage() . "\n";
    echo "堆栈跟踪: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n创建用户表操作完成！\n";
