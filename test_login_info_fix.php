<?php
/**
 * 测试login_info_created_by字段修复效果
 */

// 设置错误报告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "开始测试login_info_created_by字段修复效果...\n";

// 加载数据库配置
$dbConfig = require __DIR__ . '/app/config/database.php';

try {
    // 连接数据库
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "数据库连接成功\n";
    
    // 1. 测试直接调用db_config_handler.php的saveLoginInfo接口
    echo "\n1. 测试直接调用db_config_handler.php的saveLoginInfo接口...\n";
    
    $testData = [
        'action' => 'saveLoginInfo',
        'systemName' => '测试系统_' . time(),
        'ipUrl' => 'http://test.com',
        'loginType' => 'web',
        'username' => 'testuser',
        'password' => 'testpass',
        'remark' => '测试数据',
        'createdBy' => '测试用户_' . time()
    ];
    
    // 发送请求到db_config_handler.php
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:3007/db_config_handler.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP状态码: {$httpCode}\n";
    echo "响应内容: {$response}\n";
    
    $responseData = json_decode($response, true);
    if ($responseData && $responseData['success']) {
        echo "✅ 请求成功\n";
        
        // 验证数据是否正确插入
        $latestResult = $pdo->query("SELECT login_info_id, login_info_created_by, login_info_system_name FROM login_info ORDER BY login_info_id DESC LIMIT 1")->fetch();
        if ($latestResult) {
            echo "✅ 最新插入记录ID: {$latestResult['login_info_id']}\n";
            echo "✅ 系统名称: {$latestResult['login_info_system_name']}\n";
            echo "✅ created_by: " . ($latestResult['login_info_created_by'] ?? 'NULL') . "\n";
            
            if ($latestResult['login_info_created_by'] === $testData['createdBy']) {
                echo "✅ login_info_created_by字段正确写入\n";
            } else {
                echo "❌ login_info_created_by字段写入错误\n";
            }
        }
    } else {
        echo "❌ 请求失败: " . ($responseData['message'] ?? '未知错误') . "\n";
    }
    
    // 2. 测试边界条件：没有createdBy字段
    echo "\n2. 测试边界条件：没有createdBy字段...\n";
    
    $testDataNoCreatedBy = [
        'action' => 'saveLoginInfo',
        'systemName' => '测试系统_no_created_by_' . time(),
        'ipUrl' => 'http://test.com',
        'loginType' => 'web',
        'username' => 'testuser',
        'password' => 'testpass',
        'remark' => '测试数据（无createdBy）'
        // 故意不包含createdBy字段
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:3007/db_config_handler.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testDataNoCreatedBy));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP状态码: {$httpCode}\n";
    echo "响应内容: {$response}\n";
    
    $responseData = json_decode($response, true);
    if ($responseData && $responseData['success']) {
        echo "✅ 请求成功\n";
        
        // 验证数据是否正确插入
        $latestResult = $pdo->query("SELECT login_info_id, login_info_created_by, login_info_system_name FROM login_info ORDER BY login_info_id DESC LIMIT 1")->fetch();
        if ($latestResult) {
            echo "✅ 最新插入记录ID: {$latestResult['login_info_id']}\n";
            echo "✅ 系统名称: {$latestResult['login_info_system_name']}\n";
            echo "✅ created_by: " . ($latestResult['login_info_created_by'] ?? 'NULL') . "\n";
            
            // 应该是默认值'system'
            if ($latestResult['login_info_created_by'] === 'system') {
                echo "✅ login_info_created_by字段使用了默认值'system'\n";
            } else {
                echo "⚠️ login_info_created_by字段值为: {$latestResult['login_info_created_by']}\n";
            }
        }
    } else {
        echo "❌ 请求失败: " . ($responseData['message'] ?? '未知错误') . "\n";
    }
    
    // 3. 测试边界条件：空的createdBy字段
    echo "\n3. 测试边界条件：空的createdBy字段...\n";
    
    $testDataEmptyCreatedBy = [
        'action' => 'saveLoginInfo',
        'systemName' => '测试系统_empty_created_by_' . time(),
        'ipUrl' => 'http://test.com',
        'loginType' => 'web',
        'username' => 'testuser',
        'password' => 'testpass',
        'remark' => '测试数据（空createdBy）',
        'createdBy' => ''
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:3007/db_config_handler.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testDataEmptyCreatedBy));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP状态码: {$httpCode}\n";
    echo "响应内容: {$response}\n";
    
    $responseData = json_decode($response, true);
    if ($responseData && $responseData['success']) {
        echo "✅ 请求成功\n";
        
        // 验证数据是否正确插入
        $latestResult = $pdo->query("SELECT login_info_id, login_info_created_by, login_info_system_name FROM login_info ORDER BY login_info_id DESC LIMIT 1")->fetch();
        if ($latestResult) {
            echo "✅ 最新插入记录ID: {$latestResult['login_info_id']}\n";
            echo "✅ 系统名称: {$latestResult['login_info_system_name']}\n";
            echo "✅ created_by: " . ($latestResult['login_info_created_by'] ?? 'NULL') . "\n";
            
            // 应该是空字符串
            if ($latestResult['login_info_created_by'] === '') {
                echo "✅ login_info_created_by字段正确写入空字符串\n";
            } else {
                echo "⚠️ login_info_created_by字段值为: {$latestResult['login_info_created_by']}\n";
            }
        }
    } else {
        echo "❌ 请求失败: " . ($responseData['message'] ?? '未知错误') . "\n";
    }
    
    echo "\n🎉 测试完成\n";
    
} catch (PDOException $e) {
    echo "❌ 数据库错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 测试错误: " . $e->getMessage() . "\n";
}