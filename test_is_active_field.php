<?php
/**
 * 测试login_info_is_active字段写入功能
 */

// 设置错误报告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "开始测试login_info_is_active字段写入功能...\n";

// 加载数据库配置
$dbConfig = require __DIR__ . '/app/config/database.php';

try {
    // 连接数据库
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "数据库连接成功\n";
    
    // 测试用例1：isActive=1（在用）
    echo "\n1. 测试用例1：isActive=1（在用）...\n";
    $testData1 = [
        'action' => 'saveLoginInfo',
        'systemName' => '测试系统_is_active_1_' . time(),
        'ipUrl' => 'http://test.com',
        'loginType' => 'web',
        'username' => 'testuser',
        'password' => 'testpass',
        'isActive' => '1',
        'remark' => '测试数据：在用',
        'createdBy' => '测试用户'
    ];
    
    // 发送请求到db_config_handler.php
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:3007/db_config_handler.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData1));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP状态码: {$httpCode}\n";
    echo "响应内容: {$response}\n";
    
    $responseData = json_decode($response, true);
    if ($responseData && $responseData['success']) {
        echo "✅ 请求成功\n";
        
        // 验证数据是否正确插入
        $latestResult = $pdo->query("SELECT login_info_id, login_info_is_active, login_info_system_name FROM login_info ORDER BY login_info_id DESC LIMIT 1")->fetch();
        if ($latestResult) {
            echo "✅ 最新插入记录ID: {$latestResult['login_info_id']}\n";
            echo "✅ 系统名称: {$latestResult['login_info_system_name']}\n";
            echo "✅ login_info_is_active: {$latestResult['login_info_is_active']}\n";
            
            if ((int)$latestResult['login_info_is_active'] === 1) {
                echo "✅ login_info_is_active字段正确写入1（在用）\n";
            } else {
                echo "❌ login_info_is_active字段写入错误\n";
            }
        }
    } else {
        echo "❌ 请求失败: " . ($responseData['message'] ?? '未知错误') . "\n";
    }
    
    // 测试用例2：isActive=0（停用）
    echo "\n2. 测试用例2：isActive=0（停用）...\n";
    $testData2 = [
        'action' => 'saveLoginInfo',
        'systemName' => '测试系统_is_active_0_' . time(),
        'ipUrl' => 'http://test.com',
        'loginType' => 'web',
        'username' => 'testuser',
        'password' => 'testpass',
        'isActive' => '0',
        'remark' => '测试数据：停用',
        'createdBy' => '测试用户'
    ];
    
    // 发送请求到db_config_handler.php
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:3007/db_config_handler.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData2));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP状态码: {$httpCode}\n";
    echo "响应内容: {$response}\n";
    
    $responseData = json_decode($response, true);
    if ($responseData && $responseData['success']) {
        echo "✅ 请求成功\n";
        
        // 验证数据是否正确插入
        $latestResult = $pdo->query("SELECT login_info_id, login_info_is_active, login_info_system_name FROM login_info ORDER BY login_info_id DESC LIMIT 1")->fetch();
        if ($latestResult) {
            echo "✅ 最新插入记录ID: {$latestResult['login_info_id']}\n";
            echo "✅ 系统名称: {$latestResult['login_info_system_name']}\n";
            echo "✅ login_info_is_active: {$latestResult['login_info_is_active']}\n";
            
            if ((int)$latestResult['login_info_is_active'] === 0) {
                echo "✅ login_info_is_active字段正确写入0（停用）\n";
            } else {
                echo "❌ login_info_is_active字段写入错误\n";
            }
        }
    } else {
        echo "❌ 请求失败: " . ($responseData['message'] ?? '未知错误') . "\n";
    }
    
    // 测试用例3：不包含isActive字段（默认应为1）
    echo "\n3. 测试用例3：不包含isActive字段（默认应为1）...\n";
    $testData3 = [
        'action' => 'saveLoginInfo',
        'systemName' => '测试系统_is_active_default_' . time(),
        'ipUrl' => 'http://test.com',
        'loginType' => 'web',
        'username' => 'testuser',
        'password' => 'testpass',
        'remark' => '测试数据：默认值',
        'createdBy' => '测试用户'
        // 故意不包含isActive字段
    ];
    
    // 发送请求到db_config_handler.php
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:3007/db_config_handler.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData3));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP状态码: {$httpCode}\n";
    echo "响应内容: {$response}\n";
    
    $responseData = json_decode($response, true);
    if ($responseData && $responseData['success']) {
        echo "✅ 请求成功\n";
        
        // 验证数据是否正确插入
        $latestResult = $pdo->query("SELECT login_info_id, login_info_is_active, login_info_system_name FROM login_info ORDER BY login_info_id DESC LIMIT 1")->fetch();
        if ($latestResult) {
            echo "✅ 最新插入记录ID: {$latestResult['login_info_id']}\n";
            echo "✅ 系统名称: {$latestResult['login_info_system_name']}\n";
            echo "✅ login_info_is_active: {$latestResult['login_info_is_active']}\n";
            
            if ((int)$latestResult['login_info_is_active'] === 1) {
                echo "✅ login_info_is_active字段正确使用默认值1（在用）\n";
            } else {
                echo "❌ login_info_is_active字段默认值错误\n";
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