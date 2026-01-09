<?php
/**
 * 数据库字段添加脚本
 * 功能：为cluster_physical_machine表添加cluster_pm_name字段
 *
 * 使用方法：将此文件上传到PHP服务器环境，然后通过浏览器访问执行
 * 例如：http://localhost/CredStat/add_cluster_pm_name_field.php
 */

// 引入数据库配置文件
require_once 'db_config_handler.php';

// 获取数据库配置
$dbConfig = require 'db_config.php';

try {
    // 创建数据库连接
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    // 检查字段是否已存在
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `cluster_physical_machine` LIKE 'cluster_pm_name'");
    $stmt->execute();
    $columnExists = $stmt->fetch();

    if ($columnExists) {
        echo "字段 cluster_pm_name 已存在，无需重复添加。";
    } else {
        // 添加字段的SQL语句
        $sql = "ALTER TABLE `cluster_physical_machine`
                ADD COLUMN `cluster_pm_name` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '物理机名称' AFTER `cluster_id`";

        $pdo->exec($sql);

        echo "成功为 cluster_physical_machine 表添加 cluster_pm_name 字段！<br>";
        echo "字段属性：<br>";
        echo "- 类型：VARCHAR(100)<br>";
        echo "- 字符集：utf8mb4<br>";
        echo "- 排序规则：utf8mb4_unicode_ci<br>";
        echo "- 约束：NOT NULL DEFAULT ''<br>";
        echo "- 注释：物理机名称<br>";
        echo "- 位置：位于 cluster_id 字段之后<br>";

        // 可选：为新字段创建索引（如果需要按名称查询）
        try {
            $indexSql = "CREATE INDEX `idx_cluster_pm_name` ON `cluster_physical_machine` (`cluster_pm_name`)";
            $pdo->exec($indexSql);
            echo "<br>已为 cluster_pm_name 字段创建索引 idx_cluster_pm_name（用于提升查询性能）。";
        } catch (PDOException $indexEx) {
            // 索引可能已存在或其他错误，忽略
            echo "<br>提示：索引创建跳过（可能已存在或无需索引）。";
        }
    }

} catch (PDOException $e) {
    echo "数据库操作失败：" . $e->getMessage();
    error_log("添加字段错误: " . $e->getMessage());
}
