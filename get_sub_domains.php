<?php
/**
 * 获取二级域名及证书列表
 * 返回所有二级域名及证书信息
 */

// 设置响应头
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// 设置错误报告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 加载数据库配置
try {
    $dbConfig = require __DIR__ . '/app/config/database.php';
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => '加载数据库配置失败: ' . $e->getMessage()
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// 初始化响应
$response = [
    'success' => false,
    'message' => '获取失败',
    'data' => []
];

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // 连接数据库
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        
        // 查询二级域名及证书列表，关联主域名信息
        $sql = "SELECT 
                    sdi.sub_domain_info_id as id, 
                    mdi.main_domain_info_name as mainDomain, 
                    sdi.sub_domain_info_name as subDomain, 
                    sdi.sub_domain_info_public_ip as mappingIp, 
                    sdi.sub_domain_info_server_addr as serverIpPort, 
                    sdi.sub_domain_info_cert_expiry_date as certExpiry, 
                    sdi.sub_domain_info_cert_status as certStatus,
                    sdi.sub_domain_info_desc as `desc`,
                    sdi.sub_domain_info_notes as notes
                FROM sub_domain_info sdi
                LEFT JOIN main_domain_info mdi ON sdi.sub_domain_info_main_domain_id = mdi.main_domain_info_id
                ORDER BY sdi.sub_domain_info_id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $subDomains = $stmt->fetchAll();
        
        // 状态映射数组，将英文状态转换为中文
        $statusMap = [
            'valid' => '有效',
            'expired' => '已过期',
            'expiring' => '即将过期',
            'invalid' => '无效'
        ];
        
        // 遍历数据，将英文状态转换为中文
        foreach ($subDomains as &$domain) {
            if (isset($domain['certStatus']) && array_key_exists($domain['certStatus'], $statusMap)) {
                $domain['certStatus'] = $statusMap[$domain['certStatus']];
            }
        }
        
        // 更新响应
        $response['success'] = true;
        $response['message'] = '获取成功';
        $response['data'] = $subDomains;
        
    } catch (PDOException $e) {
        $response['success'] = false;
        $response['message'] = '数据库操作错误: ' . $e->getMessage();
        error_log('获取二级域名及证书列表失败: ' . $e->getMessage());
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = '操作错误: ' . $e->getMessage();
        error_log('获取二级域名及证书列表失败: ' . $e->getMessage());
    }
} else {
    $response['success'] = false;
    $response['message'] = '仅支持GET请求';
}

// 返回响应
echo json_encode($response, JSON_UNESCAPED_UNICODE);
