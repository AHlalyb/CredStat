<?php
/**
 * 基础对象设置API
 * 实现基础对象的增删改查操作
 */

// 设置响应头
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 加载数据库配置
$dbConfig = require __DIR__ . '/app/config/database.php';

// 初始化响应
$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 初始化请求数据和操作类型
    $requestData = [];
    $action = '';
    
    // 检查请求类型
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // 处理JSON请求，从请求体中获取action
        $rawInput = file_get_contents('php://input');
        $requestData = json_decode($rawInput, true);
        $action = isset($requestData['action']) ? trim($requestData['action']) : '';
    } else {
        // 处理表单请求，从$_POST中获取action
        $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    }
    
    try {
        // 连接数据库
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        
        // 检查数据表是否存在，不存在则创建
        createBaseObjTable($pdo);
        
        // 根据操作类型执行不同的逻辑
        switch ($action) {
            case 'getBaseObject':
                // 获取基础对象数据
                $type = isset($requestData['type']) ? trim($requestData['type']) : '';
                $response = getBaseObject($pdo, $type);
                break;
                
            case 'saveBaseObject':
                // 保存基础对象数据
                $type = isset($requestData['type']) ? trim($requestData['type']) : '';
                $values = isset($requestData['values']) ? $requestData['values'] : [];
                $response = saveBaseObject($pdo, $type, $values);
                break;
                
            default:
                $response['message'] = '无效的操作类型';
                break;
        }
        
    } catch (PDOException $e) {
        $errorMsg = '数据库操作错误: ' . $e->getMessage();
        $response['message'] = $errorMsg;
        error_log($errorMsg);
    } catch (Exception $e) {
        $errorMsg = '操作错误: ' . $e->getMessage();
        $response['message'] = $errorMsg;
        error_log($errorMsg);
    }
} else {
    $response['message'] = '仅支持POST请求';
}

// 返回响应
echo json_encode($response, JSON_UNESCAPED_UNICODE);

/**
 * 创建基础对象数据表
 * @param PDO $pdo PDO连接对象
 */
function createBaseObjTable($pdo) {
    $tableName = 'base_obj';
    
    // 检查表是否存在
    $checkSql = "SHOW TABLES LIKE :tableName";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindValue(':tableName', $tableName, PDO::PARAM_STR);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        // 表不存在，创建表
        $createSql = "CREATE TABLE {$tableName} (
            base_obj_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '主键ID',
            base_obj_room TEXT NOT NULL COMMENT '机房/站点，JSON格式存储',
            base_obj_net_device_type TEXT NOT NULL COMMENT '网络设备类型，JSON格式存储',
            base_obj_net_device_brand TEXT NOT NULL COMMENT '网络设备品牌，JSON格式存储',
            base_obj_net_device_model TEXT NOT NULL COMMENT '网络设备型号，JSON格式存储',
            base_obj_server_os TEXT NOT NULL COMMENT '服务器操作系统，JSON格式存储',
            base_obj_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            base_obj_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='基础对象设置表'";
        
        $pdo->exec($createSql);
        
        // 初始化表数据
        $initSql = "INSERT INTO {$tableName} (
            base_obj_room, 
            base_obj_net_device_type, 
            base_obj_net_device_brand, 
            base_obj_net_device_model, 
            base_obj_server_os
        ) VALUES (
            '[]', '[]', '[]', '[]', '[]'
        )";
        $pdo->exec($initSql);
    }
}

/**
 * 获取基础对象数据
 * @param PDO $pdo PDO连接对象
 * @param string $type 基础对象类型
 * @return array 获取结果
 */
function getBaseObject($pdo, $type) {
    $tableName = 'base_obj';
    
    // 映射类型到字段名 - 支持驼峰命名法和蛇形命名法
    $typeFieldMap = [
        'room' => 'base_obj_room',
        'netDeviceType' => 'base_obj_net_device_type',
        'netDeviceBrand' => 'base_obj_net_device_brand',
        'netDeviceModel' => 'base_obj_net_device_model',
        'serverOs' => 'base_obj_server_os',
        'server_os' => 'base_obj_server_os',
        'net_device_type' => 'base_obj_net_device_type',
        'net_device_brand' => 'base_obj_net_device_brand',
        'net_device_model' => 'base_obj_net_device_model'
    ];
    
    if (!isset($typeFieldMap[$type])) {
        return [
            'success' => false,
            'message' => '无效的基础对象类型'
        ];
    }
    
    $fieldName = $typeFieldMap[$type];
    
    // 查询数据
    $sql = "SELECT {$fieldName} FROM {$tableName} LIMIT 1";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch();
    
    if (!$result) {
        return [
            'success' => true,
            'message' => '获取基础对象数据成功',
            'data' => []
        ];
    }
    
    // 解析JSON数据
    $values = json_decode($result[$fieldName], true);
    if (is_null($values)) {
        $values = [];
    }
    
    return [
        'success' => true,
        'message' => '获取基础对象数据成功',
        'data' => $values
    ];
}

/**
 * 保存基础对象数据
 * @param PDO $pdo PDO连接对象
 * @param string $type 基础对象类型
 * @param array $values 要保存的值列表
 * @return array 保存结果
 */
function saveBaseObject($pdo, $type, $values) {
    $tableName = 'base_obj';
    
    // 映射类型到字段名 - 支持驼峰命名法和蛇形命名法
    $typeFieldMap = [
        'room' => 'base_obj_room',
        'netDeviceType' => 'base_obj_net_device_type',
        'netDeviceBrand' => 'base_obj_net_device_brand',
        'netDeviceModel' => 'base_obj_net_device_model',
        'serverOs' => 'base_obj_server_os',
        'server_os' => 'base_obj_server_os',
        'net_device_type' => 'base_obj_net_device_type',
        'net_device_brand' => 'base_obj_net_device_brand',
        'net_device_model' => 'base_obj_net_device_model'
    ];
    
    if (!isset($typeFieldMap[$type])) {
        return [
            'success' => false,
            'message' => '无效的基础对象类型'
        ];
    }
    
    $fieldName = $typeFieldMap[$type];
    
    // 验证值列表
    if (!is_array($values)) {
        $values = [];
    }
    
    // 去重并排序
    $uniqueValues = array_unique($values);
    sort($uniqueValues);
    
    // 转换为JSON格式
    $jsonValues = json_encode($uniqueValues, JSON_UNESCAPED_UNICODE);
    
    // 更新数据
    $sql = "UPDATE {$tableName} SET {$fieldName} = :values WHERE base_obj_id = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':values', $jsonValues, PDO::PARAM_STR);
    $stmt->execute();
    
    return [
        'success' => true,
        'message' => '保存基础对象数据成功'
    ];
}