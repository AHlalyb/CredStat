<?php
// 设置CORS头，允许跨域请求
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// 加载数据库配置
$dbConfig = require __DIR__ . '/app/config/database.php';

// 引入SecurityUtils类
require_once __DIR__ . '/app/utils/SecurityUtils.php';

// 获取请求数据
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// 初始化响应
$response = [
    'status' => 'error',
    'message' => '未知错误'
];

// 检查请求数据
if (!isset($data['action'])) {
    $response['message'] = '缺少action参数';
    echo json_encode($response);
    exit;
}

// 处理不同的action
switch ($data['action']) {
    case 'save_server_cred':
        saveServerCred($data, $dbConfig);
        break;
    case 'get_disk_info':
        getDiskInfo($data, $dbConfig);
        break;
    case 'import_server_cred':
        importServerCred($data, $dbConfig);
        break;
    default:
        $response['message'] = '无效的action参数';
        echo json_encode($response);
        break;
}

/**
 * 保存服务器凭证信息
 * @param array $data 请求数据
 * @param array $dbConfig 数据库配置
 */
function saveServerCred($data, $dbConfig) {
    // 初始化响应
    $response = [
        'status' => 'error',
        'message' => '保存失败'
    ];
    
    try {
        // 连接数据库
        $conn = new mysqli(
            $dbConfig['host'],
            $dbConfig['username'],
            $dbConfig['password'],
            $dbConfig['dbname'],
            $dbConfig['port']
        );
        
        // 检查连接
        if ($conn->connect_error) {
            $response['message'] = '数据库连接失败: ' . $conn->connect_error;
            echo json_encode($response);
            return;
        }
        
        // 开启事务
        if (!$conn->begin_transaction()) {
            throw new Exception('开启事务失败: ' . $conn->error);
        }
        
        // 准备插入服务器基本信息的SQL语句
        $sql = "INSERT INTO server_cred (
            server_cred_network_area,
            server_cred_server_type,
            server_cred_host_cluster,
            server_cred_server_name,
            server_cred_server_ip,
            server_cred_server_os,
            server_cred_server_port,
            server_cred_login_username,
            server_cred_login_password,
            server_cred_edr_installed,
            server_cred_ntp_configured,
            server_cred_notes,
            server_cred_created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        // 创建预处理语句
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('准备SQL语句失败: ' . $conn->error);
        }
        
        // 加密密码
        $encryptedPassword = SecurityUtils::encrypt($data['server_cred_login_password']);
        
        // 绑定参数
        $createdBy = 'system';
        $bindResult = $stmt->bind_param(
            'sssssssssssss',
            $data['server_cred_network_area'],
            $data['server_cred_server_type'],
            $data['server_cred_host_cluster'],
            $data['server_cred_server_name'],
            $data['server_cred_server_ip'],
            $data['server_cred_server_os'],
            $data['server_cred_server_port'],
            $data['server_cred_login_username'],
            $encryptedPassword,
            $data['server_cred_edr_installed'],
            $data['server_cred_ntp_configured'],
            $data['server_cred_notes'],
            $createdBy // server_cred_created_by，默认值为system
        );
        if (!$bindResult) {
            throw new Exception('绑定参数失败: ' . $stmt->error);
        }
        
        // 执行插入语句
        if (!$stmt->execute()) {
            throw new Exception('插入服务器基本信息失败: ' . $stmt->error);
        }
        
        // 获取插入的服务器ID
        $serverId = $stmt->insert_id;
        
        // 关闭预处理语句
        $stmt->close();
        
        // 插入磁盘信息
        if (isset($data['diskInfo']) && is_array($data['diskInfo'])) {
            // 确定操作系统类型
            $osType = $data['server_cred_server_os'] ? strtolower($data['server_cred_server_os']) : 'linux';
            $osType = strpos($osType, 'windows') !== false ? 'windows' : 'linux';
            
            // 准备插入磁盘信息的SQL语句
            $diskSql = "INSERT INTO server_cred_volu_info (
                server_cred_id,
                server_cred_volu_os_type,
                server_cred_volu_windows_drive_letter,
                server_cred_volu_linux_device_name,
                server_cred_volu_linux_mount_point,
                server_cred_volu_capacity,
                server_cred_volu_used_space,
                server_cred_volu_file_system_type,
                server_cred_volu_notes,
                server_cred_volu_created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            // 创建预处理语句
            $diskStmt = $conn->prepare($diskSql);
            if (!$diskStmt) {
                throw new Exception('准备磁盘SQL语句失败: ' . $conn->error);
            }
            
            // 遍历磁盘信息并插入
            foreach ($data['diskInfo'] as $disk) {
                // 根据操作系统类型设置相应的字段值
                $driveLetter = $osType === 'windows' ? ($disk['driveLetter'] ?? '') : null;
                $deviceName = $osType === 'linux' ? ($disk['deviceName'] ?? '') : null;
                $mountPoint = $osType === 'linux' ? ($disk['mountPoint'] ?? '') : null;
                
                // 为数组元素赋值给临时变量，以便通过引用传递给bind_param
                $capacity = $disk['capacity'] ?? '';
                $usedSpace = $disk['usedSpace'] ?? '';
                $fileSystemType = $disk['fileSystemType'] ?? '';
                $diskNotes = $disk['notes'] ?? '';
                
                // 绑定参数
                $diskCreatedBy = 'system';
                $diskBindResult = $diskStmt->bind_param(
                    'isssssssss',
                    $serverId,
                    $osType,
                    $driveLetter,
                    $deviceName,
                    $mountPoint,
                    $capacity,
                    $usedSpace,
                    $fileSystemType,
                    $diskNotes,
                    $diskCreatedBy // 默认创建人
                );
                if (!$diskBindResult) {
                    throw new Exception('绑定磁盘参数失败: ' . $diskStmt->error);
                }
                
                // 执行插入语句
                if (!$diskStmt->execute()) {
                    throw new Exception('插入磁盘信息失败: ' . $diskStmt->error);
                }
            }
            
            // 关闭预处理语句
            $diskStmt->close();
        }
        
        // 提交事务
        if (!$conn->commit()) {
            throw new Exception('提交事务失败: ' . $conn->error);
        }
        
        // 设置成功响应
        $response = [
            'status' => 'success',
            'message' => '服务器信息保存成功',
            'server_id' => $serverId
        ];
        
        // 关闭数据库连接
        $conn->close();
        
    } catch (Exception $e) {
        // 回滚事务
        if (isset($conn)) {
            // 检查是否支持in_transaction属性（PHP 5.6不支持）
            if (property_exists($conn, 'in_transaction')) {
                if ($conn->in_transaction) {
                    $conn->rollback();
                    $conn->close();
                }
            } else {
                // PHP 5.6兼容处理，尝试回滚，忽略错误
                try {
                    $conn->rollback();
                    $conn->close();
                } catch (Exception $rollbackException) {
                    // 忽略回滚错误
                }
            }
        }
        
        $response['message'] = $e->getMessage();
    }
    
    // 返回响应
    echo json_encode($response);
}

/**
 * 获取服务器磁盘信息
 * @param array $data 请求数据
 * @param array $dbConfig 数据库配置
 */
function getDiskInfo($data, $dbConfig) {
    // 初始化响应
    $response = [
        'success' => false,
        'message' => '获取磁盘信息失败',
        'data' => []
    ];
    
    try {
        // 检查服务器ID
        if (!isset($data['server_cred_id'])) {
            $response['message'] = '缺少服务器ID参数';
            echo json_encode($response);
            return;
        }
        
        $serverId = $data['server_cred_id'];
        
        // 连接数据库
        $conn = new mysqli(
            $dbConfig['host'],
            $dbConfig['username'],
            $dbConfig['password'],
            $dbConfig['dbname'],
            $dbConfig['port']
        );
        
        // 检查连接
        if ($conn->connect_error) {
            $response['message'] = '数据库连接失败: ' . $conn->connect_error;
            echo json_encode($response);
            return;
        }
        
        // 查询服务器基本信息，获取操作系统类型
        $osSql = "SELECT server_cred_server_os FROM server_cred WHERE id = ?";
        $osStmt = $conn->prepare($osSql);
        if (!$osStmt) {
            throw new Exception('准备操作系统查询语句失败: ' . $conn->error);
        }
        
        $osStmt->bind_param('i', $serverId);
        $osStmt->execute();
        $osResult = $osStmt->get_result();
        
        $osType = 'linux'; // 默认值
        if ($osRow = $osResult->fetch_assoc()) {
            $osType = strtolower($osRow['server_cred_server_os']);
            $osType = strpos($osType, 'windows') !== false ? 'windows' : 'linux';
        }
        $osStmt->close();
        
        // 查询磁盘信息
        $diskSql = "SELECT 
            server_cred_volu_windows_drive_letter as drive_letter,
            server_cred_volu_linux_device_name as device_name,
            server_cred_volu_linux_mount_point as mount_point,
            server_cred_volu_capacity as capacity,
            server_cred_volu_used_space as used_space,
            server_cred_volu_file_system_type as file_system_type,
            server_cred_volu_notes as notes
        FROM server_cred_volu_info 
        WHERE server_cred_id = ?";
        
        $diskStmt = $conn->prepare($diskSql);
        if (!$diskStmt) {
            throw new Exception('准备磁盘查询语句失败: ' . $conn->error);
        }
        
        $diskStmt->bind_param('i', $serverId);
        $diskStmt->execute();
        $diskResult = $diskStmt->get_result();
        
        $diskData = [];
        while ($row = $diskResult->fetch_assoc()) {
            $diskData[] = $row;
        }
        
        $diskStmt->close();
        $conn->close();
        
        // 设置成功响应
        $response = [
            'success' => true,
            'message' => '获取磁盘信息成功',
            'data' => $diskData
        ];
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    // 返回响应
    echo json_encode($response);
}

/**
 * 导入服务器凭证信息
 * @param array $data 请求数据
 * @param array $dbConfig 数据库配置
 */
function importServerCred($data, $dbConfig) {
    // 初始化响应
    $response = [
        'status' => 'error',
        'message' => '导入失败',
        'imported' => 0
    ];
    
    try {
        // 检查导入数据
        if (!isset($data['data']) || !is_array($data['data'])) {
            $response['message'] = '缺少导入数据';
            echo json_encode($response);
            return;
        }
        
        $importData = $data['data'];
        $importCount = count($importData);
        
        if ($importCount == 0) {
            $response['message'] = '导入数据为空';
            echo json_encode($response);
            return;
        }
        
        // 连接数据库
        $conn = new mysqli(
            $dbConfig['host'],
            $dbConfig['username'],
            $dbConfig['password'],
            $dbConfig['dbname'],
            $dbConfig['port']
        );
        
        // 检查连接
        if ($conn->connect_error) {
            $response['message'] = '数据库连接失败: ' . $conn->connect_error;
            echo json_encode($response);
            return;
        }
        
        // 开启事务
        if (!$conn->begin_transaction()) {
            throw new Exception('开启事务失败: ' . $conn->error);
        }
        
        $successCount = 0;
        
        // 遍历导入数据
        foreach ($importData as $row) {
            try {
                // 准备插入服务器基本信息的SQL语句
                $sql = "INSERT INTO server_cred (
                    server_cred_network_area,
                    server_cred_server_type,
                    server_cred_host_cluster,
                    server_cred_server_name,
                    server_cred_server_ip,
                    server_cred_server_os,
                    server_cred_server_port,
                    server_cred_login_username,
                    server_cred_login_password,
                    server_cred_edr_installed,
                    server_cred_ntp_configured,
                    server_cred_notes,
                    server_cred_created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                // 创建预处理语句
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('准备SQL语句失败: ' . $conn->error);
                }
                
                // 加密密码
                $encryptedPassword = SecurityUtils::encrypt($row['密码'] ?? '');
                
                // 绑定参数
                $createdBy = 'system';
                $networkArea = $row['服务器所属网络区域'] ?? '';
                $serverType = $row['服务器类型'] ?? '';
                $hostCluster = $row['宿主机集群'] ?? '';
                $serverName = $row['服务器名称'] ?? '';
                $serverIp = $row['服务器IP'] ?? '';
                $serverOs = $row['操作系统类型'] ?? '';
                $serverPort = $row['端口号'] ?? 0;
                $username = $row['用户名'] ?? '';
                $edrInstalled = $row['EDR安装'] ?? '是';
                $ntpConfigured = $row['NTP配置'] ?? '是';
                $notes = $row['备注信息'] ?? '';
                
                $bindResult = $stmt->bind_param(
                    'sssssssssssss',
                    $networkArea,
                    $serverType,
                    $hostCluster,
                    $serverName,
                    $serverIp,
                    $serverOs,
                    $serverPort,
                    $username,
                    $encryptedPassword,
                    $edrInstalled,
                    $ntpConfigured,
                    $notes,
                    $createdBy
                );
                if (!$bindResult) {
                    throw new Exception('绑定参数失败: ' . $stmt->error);
                }
                
                // 执行插入语句
                if (!$stmt->execute()) {
                    throw new Exception('插入服务器基本信息失败: ' . $stmt->error);
                }
                
                // 获取插入的服务器ID
                $serverId = $stmt->insert_id;
                
                // 关闭预处理语句
                $stmt->close();
                
                // 这里可以添加插入磁盘信息的逻辑
                // 由于导入数据中可能不包含磁盘信息，暂时跳过
                
                $successCount++;
                
            } catch (Exception $e) {
                // 记录错误但继续处理其他记录
                error_log('导入服务器信息失败: ' . $e->getMessage());
                continue;
            }
        }
        
        // 提交事务
        if (!$conn->commit()) {
            throw new Exception('提交事务失败: ' . $conn->error);
        }
        
        // 关闭数据库连接
        $conn->close();
        
        // 设置成功响应
        $response = [
            'status' => 'success',
            'message' => "导入成功，共导入 {$successCount} 条记录",
            'imported' => $successCount
        ];
        
    } catch (Exception $e) {
        // 回滚事务
        if (isset($conn)) {
            // 检查是否支持in_transaction属性（PHP 5.6不支持）
            if (property_exists($conn, 'in_transaction')) {
                if ($conn->in_transaction) {
                    $conn->rollback();
                    $conn->close();
                }
            } else {
                // PHP 5.6兼容处理，尝试回滚，忽略错误
                try {
                    $conn->rollback();
                    $conn->close();
                } catch (Exception $rollbackException) {
                    // 忽略回滚错误
                }
            }
        }
        
        $response['message'] = $e->getMessage();
    }
    
    // 返回响应
    echo json_encode($response);
}
?>