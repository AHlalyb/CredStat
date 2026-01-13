<?php
/**
 * 测试login_info_created_by字段写入功能
 */

// 设置错误报告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "开始测试login_info_created_by字段写入功能...\n";

// 加载数据库配置
$dbConfig = require __DIR__ . '/app/config/database.php';

try {
    // 连接数据库
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "数据库连接成功\n";
    
    // 1. 检查login_info表结构
    echo "\n1. 检查login_info表结构...\n";
    $tableInfo = $pdo->query("DESCRIBE login_info")->fetchAll();
    $hasCreatedByField = false;
    $createdByFieldType = '';
    
    foreach ($tableInfo as $field) {
        if ($field['Field'] === 'login_info_created_by') {
            $hasCreatedByField = true;
            $createdByFieldType = $field['Type'];
            break;
        }
    }
    
    if ($hasCreatedByField) {
        echo "✅ login_info_created_by字段存在，数据类型: {$createdByFieldType}\n";
    } else {
        echo "❌ login_info_created_by字段不存在\n";
    }
    
    // 2. 测试直接插入数据
    echo "\n2. 测试直接插入数据...\n";
    $testData = [
        'systemName' => '测试系统',
        'ipUrl' => 'http://test.com',
        'loginType' => 'web',
        'username' => 'testuser',
        'password' => 'testpass',
        'remark' => '测试数据',
        'createdBy' => '测试用户'
    ];
    
    // 加载SecurityUtils类
    require_once __DIR__ . '/app/utils/SecurityUtils.php';
    $encryptedPassword = SecurityUtils::encrypt($testData['password']);
    
    $sql = "INSERT INTO login_info (login_info_system_name, login_info_ip_url, login_info_login_type, 
                                   login_info_username, login_info_password, login_info_remark, 
                                   login_info_created_at, login_info_updated_at, login_info_created_by) 
           VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $testData['systemName'],
        $testData['ipUrl'],
        $testData['loginType'],
        $testData['username'],
        $encryptedPassword,
        $testData['remark'],
        $testData['createdBy']
    ]);
    
    $insertedId = $pdo->lastInsertId();
    echo "✅ 直接插入数据成功，ID: {$insertedId}\n";
    
    // 3. 验证数据是否正确插入
    echo "\n3. 验证数据是否正确插入...\n";
    $result = $pdo->query("SELECT login_info_created_by FROM login_info WHERE login_info_id = {$insertedId}")->fetch();
    if ($result && $result['login_info_created_by'] === $testData['createdBy']) {
        echo "✅ login_info_created_by字段正确写入: {$result['login_info_created_by']}\n";
    } else {
        echo "❌ login_info_created_by字段写入失败，实际值: " . ($result['login_info_created_by'] ?? 'NULL') . "\n";
    }
    
    // 4. 测试save_login_info.php脚本
    echo "\n4. 测试save_login_info.php脚本...\n";
    
    // 模拟POST请求
    $_POST = $testData;
    ob_start();
    include __DIR__ . '/save_login_info.php';
    $output = ob_get_clean();
    
    echo "脚本输出: {$output}\n";
    $response = json_decode($output, true);
    if ($response && $response['success']) {
        echo "✅ save_login_info.php脚本执行成功\n";
        
        // 验证最新插入的数据
        $latestResult = $pdo->query("SELECT login_info_id, login_info_created_by FROM login_info ORDER BY login_info_id DESC LIMIT 1")->fetch();
        if ($latestResult && $latestResult['login_info_id'] > $insertedId) {
            echo "✅ 最新插入记录ID: {$latestResult['login_info_id']}, created_by: " . ($latestResult['login_info_created_by'] ?? 'NULL') . "\n";
        }
    } else {
        echo "❌ save_login_info.php脚本执行失败: " . ($response['message'] ?? '未知错误') . "\n";
    }
    
    // 5. 清理测试数据
    echo "\n5. 清理测试数据...\n";
    $pdo->query("DELETE FROM login_info WHERE login_info_system_name = '测试系统'");
    echo "✅ 测试数据清理完成\n";
    
    echo "\n🎉 测试完成\n";
    
} catch (PDOException $e) {
    echo "❌ 数据库错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 测试错误: " . $e->getMessage() . "\n";
}