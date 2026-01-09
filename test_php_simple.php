<?php
// 简单的API测试脚本
header('Content-Type: application/json; charset=UTF-8');

// 禁用所有错误输出
error_reporting(0);
ini_set('display_errors', 0);

echo json_encode([
    'success' => true,
    'message' => 'PHP运行正常',
    'time' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE);
