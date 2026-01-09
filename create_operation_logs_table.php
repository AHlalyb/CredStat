<?php
/**
 * 创建操作日志表脚本
 */

// 加载数据库配置
$dbConfig = require __DIR__ . '/app/config/database.php';

try {
    // 创建数据库连接
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
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
    
    // 创建操作日志表
    $sql = "CREATE TABLE IF NOT EXISTS operation_logs (
        operation_id INT(11) NOT NULL AUTO_INCREMENT COMMENT '日志ID',
        operation_user_id INT(11) NOT NULL COMMENT '操作用户ID',
        operation_type VARCHAR(50) NOT NULL COMMENT '操作类型',
        operation_details TEXT NOT NULL COMMENT '操作详情',
        operation_ip VARCHAR(45) NOT NULL COMMENT '操作IP地址',
        operation_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '操作时间',
        PRIMARY KEY (operation_id) USING BTREE,
        INDEX idx_operation_user_id (operation_user_id) USING BTREE,
        INDEX idx_operation_type (operation_type) USING BTREE,
        INDEX idx_operation_created_at (operation_created_at) USING BTREE
    ) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '操作日志表' ROW_FORMAT = Dynamic";
    
    $pdo->exec($sql);
    echo "成功创建操作日志表: operation_logs\n";
    
    echo "操作日志表创建完成！\n";
    
} catch (PDOException $e) {
    echo "数据库操作错误: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "操作异常: " . $e->getMessage() . "\n";
    exit(1);
}