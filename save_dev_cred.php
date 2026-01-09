<?php
/**
 * save_dev_cred.php
 * 保存网络设备登录信息的后端接口
 */

// 加载安全工具类
require_once __DIR__ . '/app/utils/SecurityUtils.php';

// 加载安全配置
$securityConfig = require __DIR__ . '/app/config/security.php';

// 使用SecurityUtils设置安全响应头
// 检查headers配置是否存在
if (isset($securityConfig['headers'])) {
    SecurityUtils::setSecureHeaders($securityConfig['headers']);
}

// 设置基本响应头
header('Content-Type: application/json');

// 初始化响应数据
$response = [
    'success' => false,
    'message' => ''
];

// 请求方法检查已移至processSwitchCredRequest函数内部处理
// 此处不再需要独立的请求方法检查

// 加载数据库配置
try {
    $dbConfig = require __DIR__ . '/app/config/database.php';
    error_log('数据库配置加载成功');
} catch (Exception $e) {
    error_log('数据库配置加载失败: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '数据库配置加载失败: ' . $e->getMessage()
    ]);
    exit;
}

// 验证和获取表单数据 - 支持自定义数据源
function validateFormData($securityConfig, $postData = null) {
    // 使用提供的数据或$_POST
    $dataSource = $postData ?: $_POST;
    
    $requiredFields = [
        'switchDevType' => '网络设备类型',
        'switchNetType' => '设备所属网络',
        'switchArea' => '设备所属物理区域',
        'switchBuildingFloor' => '设备所属楼宇-楼层',
        'switchLocation' => '设备所在楼层位置',
        'switchCnName' => '中文命名',
        'switchSystemName' => '系统命名',
        'switchBrand' => '设备品牌',
        'switchModel' => '设备型号',
        'switchManagementIp' => '管理IP',
        'switchProtocol' => '管理协议',
        'switchPort' => '端口',
        'switchPassword' => '密码'
        // 用户名和特权密码不再是必填项
    ];
    
    $data = [];
    $errors = [];
    
    // 验证必填字段
    foreach ($requiredFields as $field => $fieldName) {
        if (!isset($dataSource[$field]) || trim($dataSource[$field]) === '') {
            $errors[] = "请输入{$fieldName}";
        } else {
            $sanitized = SecurityUtils::sanitizeInput(trim($dataSource[$field]));
            
            // 检查网络类型是否有效
            if ($field === 'switchNetType' && !in_array($sanitized, ['内网', '外网'])) {
                $errors[] = "{$fieldName}必须是'内网'或'外网'";
            } else {
                // 检查字符串长度
                $maxLength = ($field === 'switchRemark') ? 
                    ($securityConfig['input_filter']['max_text_length'] ?? 1000) : 
                    ($securityConfig['input_filter']['max_string_length'] ?? 255);
                if (strlen($sanitized) > $maxLength) {
                    $errors[] = "{$fieldName}长度不能超过{$maxLength}个字符";
                } else {
                    $data[$field] = $sanitized;
                }
            }
        }
    }
    
    // 处理可选的用户名、特权密码和SNMP团体字字段
    foreach (['switchUsername', 'switchPrivilegedPassword', 'switchSNMPCommunity'] as $field) {
        if (isset($dataSource[$field])) {
            $sanitized = SecurityUtils::sanitizeInput(trim($dataSource[$field]));
            $maxLength = $securityConfig['input_filter']['max_string_length'] ?? 255;
            if (strlen($sanitized) > $maxLength) {
                $fieldName = ($field === 'switchUsername') ? '用户名' : ($field === 'switchPrivilegedPassword') ? '特权密码' : 'SNMP团体字';
                $errors[] = "{$fieldName}长度不能超过{$maxLength}个字符";
            } else {
                $data[$field] = $sanitized;
            }
        } else {
            // 如果字段不存在，设置为空字符串
            $data[$field] = '';
        }
    }
    
    // 验证IP地址
    if (empty($errors) && isset($data['switchManagementIp'])) {
        if (!SecurityUtils::validateIP($data['switchManagementIp'])) {
            $errors[] = '请输入有效的IP地址';
        }
    }
    
    // 验证端口号
    if (empty($errors) && isset($data['switchPort'])) {
        $port = intval($data['switchPort']);
        $portMin = $securityConfig['validation']['port_min'] ?? 1;
        $portMax = $securityConfig['validation']['port_max'] ?? 65535;
        if ($port < $portMin || $port > $portMax) {
            $errors[] = "请输入有效的端口号 ({$portMin}-{$portMax})";
        }
    }
    
    // 验证管理协议
    if (empty($errors) && isset($data['switchProtocol'])) {
        $allowedProtocols = $securityConfig['validation']['allowed_protocols'] ?? ['SSH', 'Telnet', 'HTTP', 'HTTPS', 'SNMP'];
        if (!in_array($data['switchProtocol'], $allowedProtocols)) {
            $errors[] = '不支持的管理协议类型';
        }
    }
    
    // 获取可选的备注信息
        if (empty($errors)) {
            if (isset($dataSource['switchRemark'])) {
                $sanitizedRemark = SecurityUtils::sanitizeInput(trim($dataSource['switchRemark']));
                $maxRemarkLength = $securityConfig['input_filter']['max_text_length'] ?? 1000;
                if (strlen($sanitizedRemark) > $maxRemarkLength) {
                    $errors[] = "备注长度不能超过{$maxRemarkLength}个字符";
                } else {
                    $data['switchRemark'] = $sanitizedRemark;
                }
            } else {
                $data['switchRemark'] = '';
            }
        }
        
        // 添加创建者字段
        if (empty($errors)) {
            // 获取当前登录用户
            $data['createdBy'] = SecurityUtils::getCurrentUser() ?? 'system';
        }
    
    return [$data, $errors];
}

// 保存数据到数据库
function saveToDatabase($data, $dbConfig, $securityConfig) {
    try {
        // 创建数据库连接
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
        error_log('数据库连接信息: ' . str_replace(['password=' . $dbConfig['password']], ['password=***'], $dsn));
        
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
        error_log('数据库连接成功');
        
        // 使用SecurityUtils加密密码，应用安全配置中的加密选项
        $encryptedPassword = SecurityUtils::encrypt($data['switchPassword']);
        $encryptedPrivilegedPassword = SecurityUtils::encrypt($data['switchPrivilegedPassword']);
        $encryptedSNMPCommunity = SecurityUtils::encrypt($data['switchSNMPCommunity']);
        
        // 准备SQL语句
        $sql = "INSERT INTO net_dev_cred (
            net_dev_cred_dev_type,
            net_dev_cred_net_type,
            net_dev_cred_physical_area,
            net_dev_cred_building_floor,
            net_dev_cred_floor_location,
            net_dev_cred_chinese_name,
            net_dev_cred_system_name,
            net_dev_cred_dev_brand,
            net_dev_cred_dev_sign,
            net_dev_cred_management_ip,
            net_dev_cred_protocol,
            net_dev_cred_port,
            net_dev_cred_username,
            net_dev_cred_password_hash,
            net_dev_cred_enable_password_hash,
            net_dev_cred_snmp,
            net_dev_cred_description,
            net_dev_cred_created_by,
            created_at,
            updated_at
        ) VALUES (
            :net_dev_cred_dev_type,
            :net_dev_cred_net_type,
            :net_dev_cred_physical_area,
            :net_dev_cred_building_floor,
            :net_dev_cred_floor_location,
            :net_dev_cred_chinese_name,
            :net_dev_cred_system_name,
            :net_dev_cred_dev_brand,
            :net_dev_cred_dev_sign,
            :net_dev_cred_management_ip,
            :net_dev_cred_protocol,
            :net_dev_cred_port,
            :net_dev_cred_username,
            :net_dev_cred_password_hash,
            :net_dev_cred_enable_password_hash,
            :net_dev_cred_snmp,
            :net_dev_cred_description,
            :net_dev_cred_created_by,
            :created_at,
            :updated_at
        )";
        
        // 准备预处理语句
        $stmt = $pdo->prepare($sql);
        error_log('SQL语句准备成功');
        
        // 设置时间戳
        $now = date('Y-m-d H:i:s');
        
        // 获取创建者信息
        $createdBy = $data['createdBy'] ?? 'system';
        
        // 绑定参数并记录日志（排除密码信息）
        $paramLog = [
            'net_dev_cred_dev_type' => $data['switchDevType'],
            'net_dev_cred_net_type' => $data['switchNetType'],
            'net_dev_cred_physical_area' => $data['switchArea'],
            'net_dev_cred_building_floor' => $data['switchBuildingFloor'],
            'net_dev_cred_floor_location' => $data['switchLocation'],
            'net_dev_cred_chinese_name' => $data['switchCnName'],
            'net_dev_cred_system_name' => $data['switchSystemName'],
            'net_dev_cred_dev_brand' => $data['switchBrand'],
            'net_dev_cred_dev_sign' => $data['switchModel'],
            'net_dev_cred_management_ip' => $data['switchManagementIp'],
            'net_dev_cred_protocol' => $data['switchProtocol'],
            'net_dev_cred_port' => $data['switchPort'],
            'net_dev_cred_username' => $data['switchUsername'],
            'net_dev_cred_password_hash' => '***',
            'net_dev_cred_enable_password_hash' => '***',
            'net_dev_cred_snmp' => '***',
            'net_dev_cred_description' => $data['switchRemark'],
            'net_dev_cred_created_by' => $createdBy,
            'created_at' => $now,
            'updated_at' => $now
        ];
        error_log('绑定参数: ' . json_encode($paramLog));
        
        $stmt->bindParam(':net_dev_cred_dev_type', $data['switchDevType']);
        $stmt->bindParam(':net_dev_cred_net_type', $data['switchNetType']);
        $stmt->bindParam(':net_dev_cred_physical_area', $data['switchArea']);
        $stmt->bindParam(':net_dev_cred_building_floor', $data['switchBuildingFloor']);
        $stmt->bindParam(':net_dev_cred_floor_location', $data['switchLocation']);
        $stmt->bindParam(':net_dev_cred_chinese_name', $data['switchCnName']);
        $stmt->bindParam(':net_dev_cred_system_name', $data['switchSystemName']);
        $stmt->bindParam(':net_dev_cred_dev_brand', $data['switchBrand']);
        $stmt->bindParam(':net_dev_cred_dev_sign', $data['switchModel']);
        $stmt->bindParam(':net_dev_cred_management_ip', $data['switchManagementIp']);
        $stmt->bindParam(':net_dev_cred_protocol', $data['switchProtocol']);
        $stmt->bindParam(':net_dev_cred_port', $data['switchPort'], PDO::PARAM_INT);
        $stmt->bindParam(':net_dev_cred_username', $data['switchUsername']);
        $stmt->bindParam(':net_dev_cred_password_hash', $encryptedPassword);
        $stmt->bindParam(':net_dev_cred_enable_password_hash', $encryptedPrivilegedPassword);
        $stmt->bindParam(':net_dev_cred_snmp', $encryptedSNMPCommunity);
        $stmt->bindParam(':net_dev_cred_description', $data['switchRemark']);
        $stmt->bindParam(':net_dev_cred_created_by', $createdBy);
        $stmt->bindParam(':created_at', $now);
        $stmt->bindParam(':updated_at', $now);
        
        // 执行预处理语句
        error_log('开始执行SQL语句');
        $result = $stmt->execute();
        error_log('SQL执行结果: ' . ($result ? '成功' : '失败'));
        
        return $result;
    } catch (PDOException $e) {
        error_log('数据库PDO异常: 错误代码=' . $e->getCode() . ', 错误消息=' . $e->getMessage());
        error_log('异常堆栈跟踪: ' . $e->getTraceAsString());
        
        // 检查是否是唯一约束冲突
        if ($e->getCode() === '23000') {
            throw new Exception('该设备的管理IP、协议和端口组合已存在');
        }
        throw $e;
    } catch (Exception $e) {
        error_log('数据库操作异常: 错误消息=' . $e->getMessage());
        error_log('异常堆栈跟踪: ' . $e->getTraceAsString());
        throw $e;
    }
}

// 主处理函数，可被外部调用
function processSwitchCredRequest($postData = null) {
    global $dbConfig, $securityConfig;
    
    // 初始化响应数据
    $response = [
        'success' => false,
        'message' => ''
    ];
    
    try {
        error_log('开始处理网络设备登录信息保存请求');
        
        // 使用提供的数据或$_POST
        $dataToValidate = $postData ?: $_POST;
        
        // 验证表单数据，传递安全配置参数
        list($formData, $validationErrors) = validateFormData($securityConfig, $dataToValidate);
        
        if (!empty($validationErrors)) {
            $response['message'] = implode('; ', $validationErrors);
            error_log('表单验证错误: ' . $response['message']);
        } else {
            error_log('表单验证通过，开始保存数据');
            
            // 检查必填字段是否存在
            error_log('检查必填字段: switchDevType=' . (isset($formData['switchDevType']) ? $formData['switchDevType'] : '不存在'));
            error_log('检查必填字段: switchManagementIp=' . (isset($formData['switchManagementIp']) ? $formData['switchManagementIp'] : '不存在'));
            
            // 验证创建者字段
            if (empty($formData['createdBy'])) {
                $formData['createdBy'] = 'system';
            } else {
                // 验证创建者字段长度
                $maxCreatorLength = $securityConfig['input_filter']['max_string_length'] ?? 100;
                if (strlen($formData['createdBy']) > $maxCreatorLength) {
                    $response['message'] = '创建者字段长度不能超过' . $maxCreatorLength . '个字符';
                    error_log('创建者字段验证错误: ' . $response['message']);
                    return $response;
                }
            }
            
            // 保存数据，同时传递安全配置参数
            if (saveToDatabase($formData, $dbConfig, $securityConfig)) {
                $response['success'] = true;
                $response['message'] = '网络设备登录信息保存成功';
                error_log('数据保存成功');
            } else {
                $response['message'] = '保存失败，请稍后重试';
                error_log('数据保存失败，execute返回false');
            }
        }
    } catch (PDOException $e) {
        // 记录错误但不暴露详细信息给用户
        error_log('数据库操作错误: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
        
        // 根据错误代码提供更具体的错误消息
        if ($e->getCode() === '23000') {
            $response['message'] = '该设备的管理IP、协议和端口组合已存在，请检查输入';
        } elseif (strpos($e->getMessage(), 'SQLSTATE[HY000] [2002]') !== false) {
            $response['message'] = '数据库连接失败，请检查数据库服务是否运行';
        } elseif (strpos($e->getMessage(), 'SQLSTATE[42S02]') !== false) {
            $response['message'] = '数据库表结构错误，请联系系统管理员';
        } elseif (strpos($e->getMessage(), 'SQLSTATE[42S22]') !== false) {
            $response['message'] = '数据库字段错误，请检查数据格式';
            error_log('可能是字段名称错误或不存在');
        } elseif (strpos($e->getMessage(), 'SQLSTATE[23000]') !== false) {
            $response['message'] = '数据重复，请检查输入信息是否已存在';
        } else {
            $response['message'] = '数据库操作错误，请稍后重试';
        }
    } catch (Exception $e) {
        // 记录错误但不暴露详细信息给用户
        error_log('网络设备信息保存错误: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
        
        // 非PDO异常的具体处理
        if (strpos($e->getMessage(), '该设备的管理IP') !== false) {
            $response['message'] = $e->getMessage();
        } else {
            $response['message'] = '处理请求时发生错误，请稍后重试';
        }
    }
    
    return $response;
}

// validateFormData函数已经定义在前面，这里不再重复定义
// function validateFormData($securityConfig, $postData = null) {
//     // 使用提供的数据或$_POST
//     $dataSource = $postData ?: $_POST;
//     // 函数实现已移至文件前面

// 正常HTTP请求时执行
if (php_sapi_name() !== 'cli' && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查是否为JSON请求
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    $postData = null;
    
    if (strpos($contentType, 'application/json') !== false) {
        // 从JSON请求体获取数据
        $rawInput = file_get_contents('php://input');
        $postData = json_decode($rawInput, true);
        
        // 检查JSON解码是否成功
        if (json_last_error() !== JSON_ERROR_NONE) {
            $response = [
                'success' => false,
                'message' => '无效的JSON格式' . json_last_error_msg()
            ];
            echo json_encode($response);
            exit;
        }
    }
    
    $response = processSwitchCredRequest($postData);
    // 返回响应
    echo json_encode($response);
}
