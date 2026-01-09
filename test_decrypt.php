<?php
/**
 * 解密功能测试脚本
 */

// 加载SecurityUtils类
require_once __DIR__ . '/app/utils/SecurityUtils.php';

echo "=== 解密功能测试 ===\n\n";

// 测试数据 - 使用一个已知的加密字符串进行测试
$encryptedTestData = "U2FsdGVkX18+Y0V0eGJnMzRkNTY3ODkwMTIzNDU2Nzg5MDEyMzQ1Njc4OTA=";
echo "测试加密数据: $encryptedTestData\n";

try {
    // 测试解密功能
    $decryptedData = SecurityUtils::decrypt($encryptedTestData);
    echo "解密结果: $decryptedData\n";
    
    if (!empty($decryptedData)) {
        echo "✓ 解密成功！\n";
    } else {
        echo "✗ 解密失败！\n";
    }
} catch (Exception $e) {
    echo "✗ 解密发生异常: " . $e->getMessage() . "\n";
}

echo "\n=== 用户认证测试 ===\n\n";

// 测试用户认证功能
try {
    $currentUser = SecurityUtils::getCurrentUser();
    echo "当前用户: " . ($currentUser ?? "未认证") . "\n";
    
    if ($currentUser !== null) {
        echo "✓ 用户已认证！\n";
    } else {
        echo "✗ 用户未认证！\n";
    }
} catch (Exception $e) {
    echo "✗ 用户认证发生异常: " . $e->getMessage() . "\n";
}

echo "\n=== 配置加载测试 ===\n\n";

// 测试配置加载
require_once __DIR__ . '/app/config/security.php';
echo "安全配置已加载！\n";

echo "\n=== 测试完成 ===\n";