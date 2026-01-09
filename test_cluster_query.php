<?php
/**
 * 测试集群查询API
 */

// 设置响应头
header('Content-Type: application/json; charset=UTF-8');

// 测试数据
$requestData = [
    'keyword1' => '',
    'keyword2' => '',
    'queryType' => 'cluster',
    'page' => 1,
    'pageSize' => 10
];

// 初始化cURL会话
$curl = curl_init();

// 设置cURL选项
curl_setopt_array($curl, [
    CURLOPT_URL => 'http://localhost/CredStat/search_api.php',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($requestData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
]);

// 执行请求
$response = curl_exec($curl);
$err = curl_error($curl);

// 关闭cURL会话
curl_close($curl);

// 处理响应
if ($err) {
    echo "cURL错误: {$err}";
} else {
    // 美化JSON输出
    $data = json_decode($response, true);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    // 检查是否成功
    if ($data['success']) {
        echo "\n\n查询成功，返回了 {$data['total']} 条记录\n";
        echo "返回的字段: " . implode(', ', array_keys(reset($data['data']))) . "\n";
    } else {
        echo "\n\n查询失败: {$data['message']}\n";
    }
}
