<?php
/**
 * 添加用户权限字段的数据库迁移脚本
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
    
    // 检查并添加权限字段
    $fields = [
        'credstat_user_perm_add' => "tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否有增加权限'",
        'credstat_user_perm_delete' => "tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否有删除权限'",
        'credstat_user_perm_edit' => "tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否有修改权限'",
        'credstat_user_perm_query' => "tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否有查询权限'"
    ];
    
    // 获取当前表结构
    $stmt = $pdo->query("DESCRIBE credstat_user");
    $currentFields = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($fields as $fieldName => $fieldDefinition) {
        if (!in_array($fieldName, $currentFields)) {
            // 添加字段
            $sql = "ALTER TABLE credstat_user ADD COLUMN {$fieldName} {$fieldDefinition}";
            $pdo->exec($sql);
            echo "成功添加字段: {$fieldName}\n";
        } else {
            echo "字段已存在: {$fieldName}\n";
        }
    }
    
    // 为管理员用户授予所有权限
    $sql = "UPDATE credstat_user SET 
            credstat_user_perm_add = 1, 
            credstat_user_perm_delete = 1, 
            credstat_user_perm_edit = 1, 
            credstat_user_perm_query = 1 
            WHERE credstat_user_account = 'admin'";
    $pdo->exec($sql);
    echo "已为管理员用户授予所有权限\n";
    
    echo "权限字段添加完成！\n";
    
} catch (PDOException $e) {
    echo "数据库操作错误: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "操作异常: " . $e->getMessage() . "\n";
    exit(1);
}