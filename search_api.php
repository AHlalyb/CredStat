<?php
/**
 * 信息查询功能API
 * 支持基于关键词的模糊匹配查询
 * 支持按查询类型筛选或全类别查询
 */

// 启用输出缓冲，防止PHP Warning污染JSON响应
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// 设置响应头
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 加载数据库配置
$dbConfig = require __DIR__ . '/app/config/database.php';

// 加载SecurityUtils类
require_once __DIR__ . '/app/utils/SecurityUtils.php';

// 初始化响应
$response = [
    'success' => false,
    'message' => '',
    'data' => [],
    'total' => 0,
    'page' => 1,
    'page_size' => 10
];

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // 直接返回CORS头，不处理具体业务逻辑
    echo json_encode([
        'success' => true,
        'message' => 'OPTIONS请求处理成功'
    ]);
    exit;
}

// 处理POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取请求参数
    $rawInput = file_get_contents('php://input');
    
    // 记录原始请求数据（使用base64编码，避免中文乱码）
    error_log('搜索API原始请求(base64): ' . base64_encode($rawInput));
    
    // 解析JSON数据
    $requestData = json_decode($rawInput, true);
    
    // 记录请求日志（使用base64编码，避免中文乱码）
    error_log('搜索API请求(base64): ' . base64_encode(json_encode($requestData)));
    
    // 提取请求参数
    $action = isset($requestData['action']) ? trim($requestData['action']) : 'search';
    $keyword1 = isset($requestData['keyword1']) ? trim($requestData['keyword1']) : '';
    $keyword2 = isset($requestData['keyword2']) ? trim($requestData['keyword2']) : '';
    $queryType = isset($requestData['queryType']) ? trim($requestData['queryType']) : '';
    $page = isset($requestData['page']) ? intval($requestData['page']) : 1;
    $pageSize = isset($requestData['pageSize']) ? intval($requestData['pageSize']) : 10;
    $export = isset($requestData['export']) ? boolval($requestData['export']) : false;
    $exportFormat = isset($requestData['exportFormat']) ? strtolower(trim($requestData['exportFormat'])) : 'excel';
    $data = isset($requestData['data']) ? $requestData['data'] : [];
    $username = isset($requestData['username']) ? trim($requestData['username']) : '';
    
    // 验证用户权限
    try {
        // 连接数据库验证用户权限
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        
        // 获取用户权限
        $userPermissions = [
            'add' => 0,
            'delete' => 0,
            'edit' => 0,
            'query' => 0
        ];
        
        if (!empty($username)) {
            $userSql = "SELECT 
                credstat_user_perm_add, 
                credstat_user_perm_delete, 
                credstat_user_perm_edit, 
                credstat_user_perm_query 
            FROM credstat_user 
            WHERE credstat_user_account = :username";
            $stmt = $pdo->prepare($userSql);
            $stmt->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if ($user) {
                $userPermissions = [
                    'add' => intval($user['credstat_user_perm_add']),
                    'delete' => intval($user['credstat_user_perm_delete']),
                    'edit' => intval($user['credstat_user_perm_edit']),
                    'query' => intval($user['credstat_user_perm_query'])
                ];
            }
        }
        
        // 检查权限
        if ($action === 'update' && $userPermissions['edit'] !== 1) {
            throw new Exception('您没有修改权限');
        }
        
        // 拥有编辑权限的用户也可以执行查询操作
        // 获取集群列表数据的操作无需权限验证
        if ($action === 'search' && $userPermissions['query'] !== 1 && $userPermissions['edit'] !== 1) {
            // 检查是否是获取集群列表的操作
            $queryType = isset($requestData['queryType']) ? trim($requestData['queryType']) : '';
            if ($queryType !== 'cluster') {
                throw new Exception('您没有查询权限');
            }
        }
        
        // 其他操作的权限检查可以在这里添加
        
    } catch (PDOException $e) {
        error_log('权限验证数据库错误: ' . $e->getMessage());
        // 如果权限验证失败，默认只允许查询
        $userPermissions = [
            'add' => 0,
            'delete' => 0,
            'edit' => 0,
            'query' => 1
        ];
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 添加调试日志
    error_log('解析后的查询参数:');
    error_log('keyword1: ' . $keyword1);
    error_log('keyword2: ' . $keyword2);
    error_log('queryType: ' . $queryType);
    error_log('page: ' . $page);
    error_log('pageSize: ' . $pageSize);
    error_log('export: ' . ($export ? 'true' : 'false'));
    error_log('exportFormat: ' . $exportFormat);
    
    // 验证页码和每页数量
    $page = max(1, $page);
    $pageSize = max(1, min(100, $pageSize)); // 限制每页数量在1-100之间
    
    // 如果是导出请求，获取所有数据
    if ($export) {
        $pageSize = PHP_INT_MAX; // 获取所有数据
    }
    
    // 记录查询参数
    error_log('双关键词查询参数: keyword1=' . $keyword1 . ', keyword2=' . $keyword2 . ', type=' . $queryType);
    
    try {
        // 连接数据库
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        error_log('数据库连接成功');
        
        // 开始事务
        $pdo->beginTransaction();
        
        // 处理不同的操作类型
        if ($action === 'update') {
            // 更新操作
            error_log('执行更新操作');
            error_log('更新数据: ' . json_encode($data));
            
            // 获取更新类型（前端发送的是type字段，查询操作使用queryType字段）
            $updateType = isset($requestData['type']) ? trim($requestData['type']) : '';
            error_log('更新类型: ' . $updateType);
            
            // 根据更新类型处理不同的更新逻辑
            switch ($updateType) {
                case 'cluster':
                    // 更新集群信息
                    if (isset($data['cluster_id']) && !empty($data['cluster_id'])) {
                        $clusterId = $data['cluster_id'];
                        
                        // 更新集群基本信息
                        $updateSql = "UPDATE cluster SET 
                            cluster_name = :clusterName,
                            cluster_address = :clusterAddress,
                            cluster_username = :clusterUsername,
                            cluster_password = :clusterPassword,
                            cluster_updated_at = NOW()
                            WHERE cluster_id = :clusterId";
                        
                        $stmt = $pdo->prepare($updateSql);
                        $stmt->bindValue(':clusterName', $data['clusterName'], PDO::PARAM_STR);
                        $stmt->bindValue(':clusterAddress', $data['clusterAddress'], PDO::PARAM_STR);
                        $stmt->bindValue(':clusterUsername', $data['clusterUsername'], PDO::PARAM_STR);
                        // 加密密码
                        $encryptedPassword = SecurityUtils::encrypt($data['clusterPassword']);
                        $stmt->bindValue(':clusterPassword', $encryptedPassword, PDO::PARAM_STR);
                        $stmt->bindValue(':clusterId', $clusterId, PDO::PARAM_INT);
                        $stmt->execute();
                        error_log('集群基本信息更新成功');
                        
                        // 更新物理机信息
                        if (isset($data['physicalMachines']) && is_array($data['physicalMachines'])) {
                            // 先删除原有物理机信息
                            $deleteSql = "DELETE FROM cluster_physical_machine WHERE cluster_id = :clusterId";
                            $stmt = $pdo->prepare($deleteSql);
                            $stmt->bindValue(':clusterId', $clusterId, PDO::PARAM_INT);
                            $stmt->execute();
                            error_log('原有物理机信息删除成功');
                            
                            // 插入新的物理机信息
                            foreach ($data['physicalMachines'] as $pm) {
                                if (!empty($pm['pmName']) || !empty($pm['pmIp'])) {
                                    $insertSql = "INSERT INTO cluster_physical_machine (
                                        cluster_id, 
                                        cluster_pm_name, 
                                        cluster_pm_ip, 
                                        cluster_pm_username, 
                                        cluster_pm_password, 
                                        cluster_pm_bmc_ip, 
                                        cluster_pm_bmc_username, 
                                        cluster_pm_bmc_password,
                                        cluster_pm_created_at
                                    ) VALUES (
                                        :clusterId, 
                                        :pmName, 
                                        :pmIp, 
                                        :pmUsername, 
                                        :pmPassword, 
                                        :pmBmcIp, 
                                        :pmBmcUsername, 
                                        :pmBmcPassword,
                                        NOW()
                                    )";
                                    
                                    $stmt = $pdo->prepare($insertSql);
                                    $stmt->bindValue(':clusterId', $clusterId, PDO::PARAM_INT);
                                    $stmt->bindValue(':pmName', $pm['pmName'], PDO::PARAM_STR);
                                    $stmt->bindValue(':pmIp', $pm['pmIp'], PDO::PARAM_STR);
                                    $stmt->bindValue(':pmUsername', $pm['pmUsername'], PDO::PARAM_STR);
                                    // 加密物理机密码
                                    $encryptedPmPassword = SecurityUtils::encrypt($pm['pmPassword']);
                                    $stmt->bindValue(':pmPassword', $encryptedPmPassword, PDO::PARAM_STR);
                                    $stmt->bindValue(':pmBmcIp', $pm['pmBmcIp'], PDO::PARAM_STR);
                                    $stmt->bindValue(':pmBmcUsername', $pm['pmBmcUsername'], PDO::PARAM_STR);
                                    // 加密BMC密码
                                    $encryptedBmcPassword = SecurityUtils::encrypt($pm['pmBmcPassword']);
                                    $stmt->bindValue(':pmBmcPassword', $encryptedBmcPassword, PDO::PARAM_STR);
                                    $stmt->execute();
                                }
                            }
                            error_log('物理机信息更新成功');
                        }
                    }
                    break;
                case 'network':
                    // 更新网络设备登录信息
                    if (isset($data['id']) && !empty($data['id'])) {
                        $netDevId = $data['id'];
                        
                        // 更新网络设备基本信息
                        $updateSql = "UPDATE net_dev_cred SET 
                            net_dev_cred_dev_type = :dev_type,
                            net_dev_cred_net_type = :net_type,
                            net_dev_cred_chinese_name = :name,
                            net_dev_cred_system_name = :system_name,
                            net_dev_cred_dev_brand = :dev_brand,
                            net_dev_cred_dev_sign = :dev_sign,
                            net_dev_cred_physical_area = :physical_area,
                            net_dev_cred_building_floor = :building_floor,
                            net_dev_cred_floor_location = :floor_location,
                            net_dev_cred_management_ip = :management_ip,
                            net_dev_cred_protocol = :protocol,
                            net_dev_cred_port = :port,
                            net_dev_cred_username = :username,
                            net_dev_cred_password_hash = :password,
                            net_dev_cred_enable_password_hash = :enable_password,
                            net_dev_cred_snmp = :snmp_community,
                            net_dev_cred_description = :remark,
                            updated_at = NOW()
                            WHERE id = :id";
                        
                        $stmt = $pdo->prepare($updateSql);
                        $stmt->bindValue(':dev_type', $data['dev_type'], PDO::PARAM_STR);
                        $stmt->bindValue(':net_type', $data['net_type'], PDO::PARAM_STR);
                        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
                        $stmt->bindValue(':system_name', $data['system_name'], PDO::PARAM_STR);
                        $stmt->bindValue(':dev_brand', $data['dev_brand'], PDO::PARAM_STR);
                        $stmt->bindValue(':dev_sign', $data['dev_sign'], PDO::PARAM_STR);
                        $stmt->bindValue(':physical_area', $data['physical_area'], PDO::PARAM_STR);
                        $stmt->bindValue(':building_floor', $data['building_floor'], PDO::PARAM_STR);
                        $stmt->bindValue(':floor_location', $data['floor_location'], PDO::PARAM_STR);
                        $stmt->bindValue(':management_ip', $data['management_ip'], PDO::PARAM_STR);
                        $stmt->bindValue(':protocol', $data['protocol'], PDO::PARAM_STR);
                        $stmt->bindValue(':port', $data['port'], PDO::PARAM_STR);
                        $stmt->bindValue(':username', $data['username'], PDO::PARAM_STR);
                        // 加密密码
                        $encryptedPassword = SecurityUtils::encrypt($data['password']);
                        $stmt->bindValue(':password', $encryptedPassword, PDO::PARAM_STR);
                        // 加密使能密码
                        $encryptedEnablePassword = SecurityUtils::encrypt($data['enable_password']);
                        $stmt->bindValue(':enable_password', $encryptedEnablePassword, PDO::PARAM_STR);
                        // 加密SNMP团体字
                        $encryptedSnmp = SecurityUtils::encrypt($data['snmp_community']);
                        $stmt->bindValue(':snmp_community', $encryptedSnmp, PDO::PARAM_STR);
                        $stmt->bindValue(':remark', $data['remark'], PDO::PARAM_STR);
                        $stmt->bindValue(':id', $netDevId, PDO::PARAM_INT);
                        $stmt->execute();
                        error_log('网络设备信息更新成功');
                    }
                    break;
                case 'login_info':
                    // 更新系统登录信息
                    // 兼容前端不同的字段名
                    $loginInfoId = isset($data['id']) ? $data['id'] : (isset($data['login_info_id']) ? $data['login_info_id'] : '');
                    if (!empty($loginInfoId)) {
                        // 更新系统登录信息
                        $updateSql = "UPDATE login_info SET 
                            login_info_system_name = :systemName,
                            login_info_ip_url = :ip,
                            login_info_login_type = :loginType,
                            login_info_username = :account,
                            login_info_password = :password,
                            login_info_remark = :remark,
                            login_info_is_active = :isActive,
                            login_info_updated_at = NOW()
                            WHERE login_info_id = :id";
                        
                        $stmt = $pdo->prepare($updateSql);
                        // 获取前端可能使用的不同字段名
                        $systemName = isset($data['systemName']) ? $data['systemName'] : (isset($data['name']) ? $data['name'] : '');
                        $ip = isset($data['ip']) ? $data['ip'] : (isset($data['ipUrl']) ? $data['ipUrl'] : '');
                        $loginType = isset($data['loginType']) ? $data['loginType'] : (isset($data['type']) ? $data['type'] : '');
                        $account = isset($data['account']) ? $data['account'] : (isset($data['username']) ? $data['username'] : '');
                        $password = isset($data['password']) ? $data['password'] : '';
                        $remark = isset($data['remark']) ? $data['remark'] : '';
                        $isActive = isset($data['isActive']) ? $data['isActive'] : '1';
                        
                        $stmt->bindValue(':systemName', $systemName, PDO::PARAM_STR);
                        $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
                        $stmt->bindValue(':loginType', $loginType, PDO::PARAM_STR);
                        $stmt->bindValue(':account', $account, PDO::PARAM_STR);
                        // 加密密码
                        $encryptedPassword = SecurityUtils::encrypt($password);
                        $stmt->bindValue(':password', $encryptedPassword, PDO::PARAM_STR);
                        $stmt->bindValue(':remark', $remark, PDO::PARAM_STR);
                        $stmt->bindValue(':isActive', $isActive, PDO::PARAM_INT);
                        $stmt->bindValue(':id', $loginInfoId, PDO::PARAM_INT);
                        $stmt->execute();
                        error_log('系统登录信息更新成功');
                    }
                    break;
                case 'server_cred':
                    // 更新服务器账号密码
                    $serverId = null;
                    if (isset($data['id']) && !empty($data['id'])) {
                        $serverId = $data['id'];
                    } elseif (isset($data['server_cred_id']) && !empty($data['server_cred_id'])) {
                        $serverId = $data['server_cred_id'];
                    }
                    
                    if ($serverId) {
                        // 更新服务器账号密码信息
                        $updateSql = "UPDATE server_cred SET 
                            server_cred_server_name = :name,
                            server_cred_server_ip = :ip,
                            server_cred_server_port = :port,
                            server_cred_server_os = :os,
                            server_cred_login_username = :loginUsername,
                            server_cred_login_password = :loginPassword,
                            server_cred_network_area = :networkArea,
                            server_cred_server_type = :serverType,
                            server_cred_host_cluster = :hostCluster,
                            server_cred_notes = :notes,
                            updated_at = NOW()
                            WHERE id = :id";
                        
                        $stmt = $pdo->prepare($updateSql);
                        // 处理服务器基本信息字段，支持前端的完整字段名
                        $name = isset($data['server_cred_server_name']) ? $data['server_cred_server_name'] : (isset($data['name']) ? $data['name'] : '');
                        $ip = isset($data['server_cred_server_ip']) ? $data['server_cred_server_ip'] : (isset($data['ip']) ? $data['ip'] : '');
                        $port = isset($data['server_cred_server_port']) ? $data['server_cred_server_port'] : (isset($data['port']) ? $data['port'] : 22);
                        $os = isset($data['server_cred_server_os']) ? $data['server_cred_server_os'] : (isset($data['os']) ? $data['os'] : '');
                        $loginUsername = isset($data['server_cred_login_username']) ? $data['server_cred_login_username'] : (isset($data['loginUsername']) ? $data['loginUsername'] : '');
                        $loginPassword = isset($data['server_cred_login_password']) ? $data['server_cred_login_password'] : (isset($data['loginPassword']) ? $data['loginPassword'] : '');
                        $networkArea = isset($data['server_cred_network_area']) ? $data['server_cred_network_area'] : (isset($data['networkArea']) ? $data['networkArea'] : '');
                        $serverType = isset($data['server_cred_server_type']) ? $data['server_cred_server_type'] : (isset($data['serverType']) ? $data['serverType'] : '');
                        $hostCluster = isset($data['server_cred_host_cluster']) ? $data['server_cred_host_cluster'] : (isset($data['hostCluster']) ? $data['hostCluster'] : '');
                        $notes = isset($data['server_cred_notes']) ? $data['server_cred_notes'] : (isset($data['notes']) ? $data['notes'] : '');
                        
                        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
                        $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
                        $stmt->bindValue(':port', $port, PDO::PARAM_INT);
                        $stmt->bindValue(':os', $os, PDO::PARAM_STR);
                        $stmt->bindValue(':loginUsername', $loginUsername, PDO::PARAM_STR);
                        // 加密密码
                        $encryptedPassword = SecurityUtils::encrypt($loginPassword);
                        $stmt->bindValue(':loginPassword', $encryptedPassword, PDO::PARAM_STR);
                        $stmt->bindValue(':networkArea', $networkArea, PDO::PARAM_STR);
                        $stmt->bindValue(':serverType', $serverType, PDO::PARAM_STR);
                        $stmt->bindValue(':hostCluster', $hostCluster, PDO::PARAM_STR);
                        $stmt->bindValue(':notes', $notes, PDO::PARAM_STR);
                        $stmt->bindValue(':id', $serverId, PDO::PARAM_INT);
                        $stmt->execute();
                        error_log('服务器账号密码更新成功');
                        
                        // 处理磁盘信息
                        if (isset($data['disk_forms']) && is_array($data['disk_forms'])) {
                            // 1. 删除原有磁盘信息
                            $deleteDiskSql = "DELETE FROM server_cred_volu_info WHERE server_cred_id = :serverId";
                            $deleteStmt = $pdo->prepare($deleteDiskSql);
                            $deleteStmt->bindValue(':serverId', $serverId, PDO::PARAM_INT);
                            $deleteStmt->execute();
                            error_log('原有磁盘信息删除成功，影响行数: ' . $deleteStmt->rowCount());
                            
                            // 2. 插入新的磁盘信息
                            $osType = isset($data['os']) ? strtolower($data['os']) : 'linux';
                            $osType = strpos($osType, 'windows') !== false ? 'windows' : 'linux';
                            
                            $insertDiskSql = "INSERT INTO server_cred_volu_info (
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
                            
                            $insertStmt = $pdo->prepare($insertDiskSql);
                            
                            foreach ($data['disk_forms'] as $disk) {
                                $driveLetter = $osType === 'windows' ? ($disk['driveLetter'] ?? '') : null;
                                $deviceName = $osType === 'linux' ? ($disk['deviceName'] ?? '') : null;
                                $mountPoint = $osType === 'linux' ? ($disk['mountPoint'] ?? '') : null;
                                
                                $capacity = $disk['capacity'] ?? '';
                                $usedSpace = $disk['usedSpace'] ?? '';
                                $fileSystemType = $disk['fileSystemType'] ?? '';
                                $diskNotes = $disk['notes'] ?? '';
                                
                                $createdBy = 'system';
                                
                                $insertStmt->bindValue(1, $serverId, PDO::PARAM_INT);
                                $insertStmt->bindValue(2, $osType, PDO::PARAM_STR);
                                $insertStmt->bindValue(3, $driveLetter, PDO::PARAM_STR);
                                $insertStmt->bindValue(4, $deviceName, PDO::PARAM_STR);
                                $insertStmt->bindValue(5, $mountPoint, PDO::PARAM_STR);
                                $insertStmt->bindValue(6, $capacity, PDO::PARAM_STR);
                                $insertStmt->bindValue(7, $usedSpace, PDO::PARAM_STR);
                                $insertStmt->bindValue(8, $fileSystemType, PDO::PARAM_STR);
                                $insertStmt->bindValue(9, $diskNotes, PDO::PARAM_STR);
                                $insertStmt->bindValue(10, $createdBy, PDO::PARAM_STR);
                                
                                $insertStmt->execute();
                            }
                            error_log('磁盘信息更新成功，插入条数: ' . count($data['disk_forms']));
                        }
                    }
                    break;
                default:
                    throw new Exception('不支持的更新类型: ' . $updateType);
            }
            
            // 提交事务
            $pdo->commit();
            
            // 返回成功响应
            $response['success'] = true;
            $response['message'] = '更新成功';
            $response['data'] = $data;
        } elseif ($action === 'delete') {
            // 删除操作
            error_log('执行删除操作');
            
            // 检查删除权限，拥有编辑权限的用户也可以执行删除操作
            if ($userPermissions['delete'] !== 1 && $userPermissions['edit'] !== 1) {
                throw new Exception('您没有删除权限');
            }
            
            // 获取删除类型和ID
            $deleteType = isset($requestData['type']) ? trim($requestData['type']) : '';
            $id = isset($requestData['id']) ? $requestData['id'] : '';
            error_log('删除类型: ' . $deleteType);
            error_log('删除ID: ' . $id);
            
            if (empty($deleteType) || empty($id)) {
                throw new Exception('删除参数不完整');
            }
            
            // 根据删除类型执行不同的删除逻辑
            switch ($deleteType) {
                case 'login_info':
                    // 删除系统登录信息
                    $deleteSql = "DELETE FROM login_info WHERE login_info_id = :id";
                    break;
                case 'server_cred':
                    // 删除服务器账号密码
                    $deleteSql = "DELETE FROM server_cred WHERE server_cred_id = :id";
                    break;
                case 'net_dev_cred':
                    // 删除网络设备登录信息
                    $deleteSql = "DELETE FROM net_dev_cred WHERE net_dev_cred_id = :id";
                    break;
                case 'cluster_cred':
                    // 删除集群信息
                    $deleteSql = "DELETE FROM cluster WHERE cluster_id = :id";
                    break;
                default:
                    throw new Exception('不支持的删除类型: ' . $deleteType);
            }
            
            // 执行删除操作
            $stmt = $pdo->prepare($deleteSql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            error_log('删除成功，影响行数: ' . $stmt->rowCount());
            
            // 提交事务
            $pdo->commit();
            
            // 返回成功响应
            $response['success'] = true;
            $response['message'] = '删除成功';
            $response['data'] = [];
        } else {
            // 查询操作
            // 初始化变量
            $allResults = [];
            $total = 0;
            
            // 全类别查询或特定类型查询
            if (empty($queryType) || $queryType === 'all') {
                // 全类别查询：先查询所有数据，然后合并分页
                $tables = ['login_info', 'server_cred', 'net_dev_cred'];
                
                foreach ($tables as $table) {
                    try {
                        // 对每个表查询所有匹配数据（不分页）
                    $tableResults = searchTable($pdo, $table, $keyword1, $keyword2, 1, PHP_INT_MAX, $requestData);
                        $allResults = array_merge($allResults, $tableResults['data']);
                    } catch (Exception $e) {
                        error_log('查询表 ' . $table . ' 出错: ' . $e->getMessage());
                        // 跳过出错的表，继续查询其他表
                        continue;
                    }
                }
                
                // 计算实际找到的记录总数
                $total = count($allResults);
                
                // 对合并后的所有数据进行分页
                $offset = ($page - 1) * $pageSize;
                $results = array_slice($allResults, $offset, $pageSize);
            } else {
                // 特定类型查询：直接分页查询
                $table = '';
                error_log('查询类型: ' . $queryType);
                switch ($queryType) {
                    case 'system':
                        $table = 'login_info';
                        break;
                    case 'server':
                        $table = 'server_cred';
                        break;
                    case 'network':
                        $table = 'net_dev_cred';
                        break;
                    case 'cluster':
                        $table = 'cluster';
                        break;
                    case 'cluster_physical_machine':
                        $table = 'cluster_physical_machine';
                        break;
                    default:
                        throw new Exception('无效的查询类型: ' . $queryType);
                }
                error_log('选定的表: ' . $table);
                
                // 使用普通查询逻辑
                $tableResults = searchTable($pdo, $table, $keyword1, $keyword2, $page, $pageSize, $requestData);
                $results = $tableResults['data'];
                $total = $tableResults['total'];
            }
            
            // 返回查询结果前，对数据进行最终清理
            $cleanedResults = [];
            foreach ($results as $result) {
                $cleanedResult = [];
                foreach ($result as $key => $value) {
                    // 确保键名是字符串
                    $cleanKey = (string)$key;
                    
                    // 清理值，确保JSON编码成功
                    if (is_null($value)) {
                        // NULL值转换为空字符串
                        $cleanValue = '';
                    } elseif (is_string($value)) {
                        // 清理字符串，移除可能导致JSON编码失败的字符
                        $cleanValue = trim($value);
                        // 移除控制字符
                        $cleanValue = preg_replace('/[\x00-\x1F\x7F]/', '', $cleanValue);
                        // 确保是有效的UTF-8
                        $cleanValue = mb_convert_encoding($cleanValue, 'UTF-8', 'UTF-8');
                    } else {
                        // 其他类型直接使用
                        $cleanValue = $value;
                    }
                    
                    $cleanedResult[$cleanKey] = $cleanValue;
                }
                
                // 密码已经在searchTable函数中解密，这里不需要再次解密
                // 只需要确保密码字段存在，避免前端显示问题
                try {
                    // 如果是物理机数据，确保密码字段存在
                    if ((isset($cleanedResult['category']) && $cleanedResult['category'] === 'cluster_physical_machine') || 
                        (isset($cleanedResult['cluster_pm_ip']) && isset($cleanedResult['cluster_pm_username']))) {
                        // 确保物理机密码字段存在
                        if (!isset($cleanedResult['cluster_pm_password'])) {
                            $cleanedResult['cluster_pm_password'] = '';
                        }
                        // 确保BMC密码字段存在
                        if (!isset($cleanedResult['cluster_pm_bmc_password'])) {
                            $cleanedResult['cluster_pm_bmc_password'] = '';
                        }
                    }
                } catch (Exception $e) {
                    error_log("处理密码字段失败: {$e->getMessage()}");
                }
                
                $cleanedResults[] = $cleanedResult;
            }
            
            // 修复全类别查询的total计算：使用所有结果的数量
            if (empty($queryType) || $queryType === 'all') {
                // 对于全类别查询，使用合并后的结果数量作为total
                $total = count($allResults);
            } else {
                // 修复特定类型查询的total计算问题：如果total为0，但有结果，使用结果数量作为total
                if ($total === 0 && count($cleanedResults) > 0) {
                    $total = count($cleanedResults);
                }
            }
            
            // 记录成功日志
            error_log('搜索API成功返回: ' . count($cleanedResults) . ' 条记录');
            error_log('搜索API总记录数: ' . $total . ' 条记录');
            
            // 检查是否是导出请求
            if ($export) {
                // 获取用户选择的列
                $selectedColumns = isset($requestData['selectedColumns']) && is_array($requestData['selectedColumns']) ? $requestData['selectedColumns'] : [];
                // 执行导出
                exportData($cleanedResults, $exportFormat, $selectedColumns);
                exit;
            } else {
                // 返回JSON响应
                $response['success'] = true;
                $response['message'] = '查询成功';
                $response['data'] = $cleanedResults;
                $response['total'] = $total;
                $response['page'] = $page;
                $response['page_size'] = $pageSize;
            }
        }
        
        // 提交事务
        $pdo->commit();
        
    } catch (PDOException $e) {
        $errorMsg = '数据库查询错误: ' . $e->getMessage();
        $response['message'] = $errorMsg;
        error_log($errorMsg . ' 错误代码: ' . $e->getCode());
    } catch (Exception $e) {
        $errorMsg = '查询错误: ' . $e->getMessage();
        $response['message'] = $errorMsg;
        error_log($errorMsg);
    }
} else {
    $response['message'] = '仅支持POST请求';
    error_log('搜索API错误: 仅支持POST请求，收到 ' . $_SERVER['REQUEST_METHOD']);
}

// 在输出JSON响应之前，清除所有之前的输出缓冲（包括PHP Warning）
while (ob_get_level()) {
    ob_end_clean();
}

// 尝试编码响应
$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);

if ($jsonResponse === false) {
    // 如果JSON编码失败，记录错误信息并返回简化响应
    $jsonError = json_last_error();
    $jsonErrorMsg = json_last_error_msg();
    error_log('JSON编码失败，错误码: ' . $jsonError . ', 错误信息: ' . $jsonErrorMsg);
    
    // 清除任何可能的输出
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // 返回简化的错误响应
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '查询过程中出现错误，请稍后重试',
        'data' => [],
        'total' => 0,
        'page' => 1,
        'page_size' => 10
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo $jsonResponse;
}

/**
 * 根据字段名获取中文名称
 * @param string $field 字段名
 * @return string 中文名称
 */
function getFieldLabel($field) {
    $fieldMap = [
        'login_info_system_name' => '系统名称',
        'login_info_ip_url' => 'IP或URL地址',
        'login_info_login_type' => '登录方式',
        'login_info_username' => '账号',
        'login_info_password' => '密码',
        'login_info_remark' => '备注信息',
        'login_info_created_at' => '创建时间',
        'login_info_updated_at' => '更新时间',
        'login_info_created_by' => '创建人',
        'login_info_is_active' => '是否有效'
    ];
    
    return isset($fieldMap[$field]) ? $fieldMap[$field] : $field;
}

/**
 * 导出数据
 * @param array $data 要导出的数据
 * @param string $format 导出格式 (pdf 或 excel)
 * @param array $selectedColumns 用户选择的导出列
 */
function exportData($data, $format, $selectedColumns = []) {
    if (empty($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '没有可导出的数据']);
        exit;
    }
    
    // 检查数据类型
    $dataType = 'default';
    if (!empty($data[0])) {
        // 如果有category字段，使用它
        if (isset($data[0]['category'])) {
            $dataType = $data[0]['category'];
        } 
        // 否则，检查是否有cluster_id字段，这是集群数据的特征
        elseif (isset($data[0]['cluster_id'])) {
            $dataType = 'cluster';
        }
    }
    
    // 对于集群类型数据，需要加载物理机信息
    if ($dataType === 'cluster') {
        // 连接数据库
        $dbConfig = require __DIR__ . '/app/config/database.php';
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        
        // 为每个集群加载物理机信息
        foreach ($data as &$cluster) {
            $clusterId = $cluster['cluster_id'];
            // 查询物理机信息
            $stmt = $pdo->prepare("SELECT * FROM cluster_physical_machine WHERE cluster_id = :clusterId");
            $stmt->bindValue(':clusterId', $clusterId, PDO::PARAM_INT);
            $stmt->execute();
            $physicalMachines = $stmt->fetchAll();
            
            // 解密物理机密码和BMC密码
            foreach ($physicalMachines as &$pm) {
                // 解密物理机密码
                if (isset($pm['cluster_pm_password']) && !empty($pm['cluster_pm_password'])) {
                    $pm['cluster_pm_password'] = SecurityUtils::decrypt($pm['cluster_pm_password']);
                }
                
                // 解密BMC密码
                if (isset($pm['cluster_pm_bmc_password']) && !empty($pm['cluster_pm_bmc_password'])) {
                    $pm['cluster_pm_bmc_password'] = SecurityUtils::decrypt($pm['cluster_pm_bmc_password']);
                }
            }
            
            // 添加物理机信息到集群数据中
            $cluster['physical_machines'] = $physicalMachines;
        }
    }
    
    // 根据格式导出
    switch ($format) {
        case 'pdf':
            exportToPDF($data, $dataType, $selectedColumns);
            break;
        case 'excel':
        default:
            exportToExcel($data, $dataType, $selectedColumns);
            break;
    }
}

/**
 * 导出为Excel (CSV格式)
 * @param array $data 要导出的数据
 * @param string $dataType 数据类型
 * @param array $selectedColumns 用户选择的导出列
 */
function exportToExcel($data, $dataType = 'default', $selectedColumns = []) {
    // 生成文件名，使用YYYYMMDDHHMMSS格式
    $filename = '查询结果_' . date('YmdHis') . '.csv';
    
    // 设置响应头，确保中文文件名正确显示
    // 先清除可能存在的旧响应头
    header_remove('Content-Disposition');
    
    // 设置正确的Content-Type和字符集
    header('Content-Type: text/csv; charset=utf-8');
    
    // 使用RFC 5987编码处理中文文件名
    $encodedFilename = urlencode($filename);
    header('Content-Disposition: attachment; filename*=UTF-8\'\'' . $encodedFilename);
    header('Cache-Control: max-age=0');
    
    // 打开输出流
    $output = fopen('php://output', 'w');
    
    // 添加BOM头，确保Excel能正确识别UTF-8编码
    fwrite($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    // 根据数据类型和用户选择的列处理
    if ($dataType === 'cluster') {
        // 导出集群信息和物理机信息
        fputcsv($output, ['宿主机集群导出']);
        fputcsv($output, []);
        
        // 遍历每个集群
        foreach ($data as $clusterIndex => $cluster) {
            // 导出集群基本信息
            $clusterColumns = [
                '集群名称', '集群ID', '集群地址', '集群用户名', '集群密码', '宿主机数量', '创建时间'
            ];
            fputcsv($output, $clusterColumns);
            
            $clusterData = [
                isset($cluster['cluster_name']) ? $cluster['cluster_name'] : '',
                isset($cluster['cluster_id']) ? $cluster['cluster_id'] : '',
                isset($cluster['cluster_address']) ? $cluster['cluster_address'] : '',
                isset($cluster['cluster_username']) ? $cluster['cluster_username'] : '',
                isset($cluster['cluster_password']) ? $cluster['cluster_password'] : '',
                isset($cluster['pm_count']) ? $cluster['pm_count'] : '',
                isset($cluster['cluster_created_at']) ? $cluster['cluster_created_at'] : ''
            ];
            fputcsv($output, $clusterData);
            
            // 导出物理机信息
            fputcsv($output, []);
            fputcsv($output, ['物理机信息']);
            
            $physicalMachineColumns = [
                '序号', '物理机IP', '物理机用户名', '物理机密码', 'BMC IP', 'BMC用户名', 'BMC密码', '创建时间'
            ];
            fputcsv($output, $physicalMachineColumns);
            
            // 写入物理机数据
            if (isset($cluster['physical_machines']) && is_array($cluster['physical_machines'])) {
                foreach ($cluster['physical_machines'] as $pmIndex => $pm) {
                    // 确保物理机密码和解密
                    $pmPassword = isset($pm['cluster_pm_password']) ? $pm['cluster_pm_password'] : '';
                    $bmcPassword = isset($pm['cluster_pm_bmc_password']) ? $pm['cluster_pm_bmc_password'] : '';
                    
                    $pmData = [
                        $pmIndex + 1,
                        isset($pm['cluster_pm_ip']) ? $pm['cluster_pm_ip'] : '',
                        isset($pm['cluster_pm_username']) ? $pm['cluster_pm_username'] : '',
                        $pmPassword,
                        isset($pm['cluster_pm_bmc_ip']) ? $pm['cluster_pm_bmc_ip'] : '',
                        isset($pm['cluster_pm_bmc_username']) ? $pm['cluster_pm_bmc_username'] : '',
                        $bmcPassword,
                        isset($pm['cluster_pm_created_at']) ? $pm['cluster_pm_created_at'] : ''
                    ];
                    fputcsv($output, $pmData);
                }
            }
            
            // 添加分隔行
            fputcsv($output, []);
            fputcsv($output, ['--------------------------------------------------']);
            fputcsv($output, []);
        }
    } else {
        // 使用用户选择的列或默认列
        if (!empty($selectedColumns)) {
            // 使用用户选择的列
            $exportColumns = $selectedColumns;
            
            // 写入中文列名
            $chineseColumns = array_map('getFieldLabel', $exportColumns);
            fputcsv($output, $chineseColumns);
            
            // 写入数据行
            foreach ($data as $index => $row) {
                $rowData = [];
                
                foreach ($exportColumns as $column) {
                    $value = isset($row[$column]) ? $row[$column] : '';
                    
                    // 处理是否有效字段
                    if ($column === 'login_info_is_active') {
                        $value = $value === '1' || $value === 1 ? '有效' : '无效';
                    }
                    
                    $rowData[] = $value;
                }
                
                fputcsv($output, $rowData);
            }
        } else {
            // 导出其他类型数据，使用默认字段列表
            // 定义需要导出的固定字段列表（与查询显示的结果一致）
            $exportColumns = [
                '序号' => 'serial',
                '名称' => 'name',
                'IP/URL' => 'ip_url',
                '类型' => 'type',
                '用户名' => 'username',
                '密码' => 'password',
                '备注' => 'remark',
                '创建时间' => 'created_at',
                '类别' => 'category'
            ];
            
            // 写入中文列名
            fputcsv($output, array_keys($exportColumns));
            
            // 写入数据行
            foreach ($data as $index => $row) {
                $rowData = [];
                $rowData[] = $index + 1; // 序号，从1开始
                $rowData[] = isset($row['name']) ? $row['name'] : '';
                $rowData[] = isset($row['ip_url']) ? $row['ip_url'] : '';
                $rowData[] = isset($row['type']) ? $row['type'] : '';
                $rowData[] = isset($row['username']) ? $row['username'] : '';
                $rowData[] = isset($row['password']) ? $row['password'] : '';
                $rowData[] = isset($row['remark']) ? $row['remark'] : '';
                $rowData[] = isset($row['created_at']) ? $row['created_at'] : '';
                $rowData[] = isset($row['category']) ? $row['category'] : '';
                fputcsv($output, $rowData);
            }
        }
    }
    
    // 关闭输出流
    fclose($output);
    exit;
}

/**
 * 导出为HTML
 * @param array $data 要导出的数据
 * @param string $dataType 数据类型
 * @param array $selectedColumns 用户选择的导出列
 */
function exportToPDF($data, $dataType = 'default', $selectedColumns = []) {
    // 生成文件名，使用YYYYMMDDHHMMSS格式，使用.html扩展名
    $filename = '查询结果_' . date('YmdHis') . '.html';
    
    // 生成HTML内容
    ob_start();
    
    // 输出HTML开始部分
    echo '<!DOCTYPE html>';
    echo '<html lang="zh-CN">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>查询结果</title>';
    echo '<style>';
    echo 'body {';
    echo '    font-family: Arial, sans-serif;';
    echo '    margin: 20px;';
    echo '}';
    echo 'h1 {';
    echo '    text-align: center;';
    echo '    color: #333;';
    echo '}';
    echo 'h2 {';
    echo '    color: #555;';
    echo '    margin-top: 30px;';
    echo '}';
    echo 'h3 {';
    echo '    color: #666;';
    echo '    margin-top: 20px;';
    echo '}';
    echo 'table {';
    echo '    width: 100%;';
    echo '    border-collapse: collapse;';
    echo '    margin: 20px 0;';
    echo '}';
    echo 'th, td {';
    echo '    border: 1px solid #ddd;';
    echo '    padding: 8px;';
    echo '    text-align: left;';
    echo '}';
    echo 'th {';
    echo '    background-color: #f2f2f2;';
    echo '    font-weight: bold;';
    echo '}';
    echo 'tr:nth-child(even) {';
    echo '    background-color: #f9f9f9;';
    echo '}';
    echo '.cluster-section {';
    echo '    margin-bottom: 40px;';
    echo '    padding: 20px;';
    echo '    border: 1px solid #eee;';
    echo '    border-radius: 5px;';
    echo '}';
    echo '.section-divider {';
    echo '    margin: 30px 0;';
    echo '    border-top: 2px solid #ddd;';
    echo '}';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<h1>查询结果</h1>';
    
    // 根据数据类型处理
    if ($dataType === 'cluster') {
        // 导出集群信息和物理机信息
        foreach ($data as $clusterIndex => $cluster) {
            echo '<div class="cluster-section">';
            echo '<h2>集群 ' . ($clusterIndex + 1) . '</h2>';
            
            echo '<h3>集群基本信息</h3>';
            echo '<table>';
            echo '<thead>';
            echo '<tr>';
            echo '<th>集群名称</th>';
            echo '<th>集群ID</th>';
            echo '<th>集群地址</th>';
            echo '<th>集群用户名</th>';
            echo '<th>集群密码</th>';
            echo '<th>宿主机数量</th>';
            echo '<th>创建时间</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            echo '<tr>';
            echo '<td>' . (isset($cluster['cluster_name']) ? $cluster['cluster_name'] : '') . '</td>';
            echo '<td>' . (isset($cluster['cluster_id']) ? $cluster['cluster_id'] : '') . '</td>';
            echo '<td>' . (isset($cluster['cluster_address']) ? $cluster['cluster_address'] : '') . '</td>';
            echo '<td>' . (isset($cluster['cluster_username']) ? $cluster['cluster_username'] : '') . '</td>';
            echo '<td>' . (isset($cluster['cluster_password']) ? $cluster['cluster_password'] : '') . '</td>';
            echo '<td>' . (isset($cluster['pm_count']) ? $cluster['pm_count'] : '') . '</td>';
            echo '<td>' . (isset($cluster['cluster_created_at']) ? $cluster['cluster_created_at'] : '') . '</td>';
            echo '</tr>';
            echo '</tbody>';
            echo '</table>';
            
            echo '<h3>物理机信息</h3>';
            echo '<table>';
            echo '<thead>';
            echo '<tr>';
            echo '<th>序号</th>';
            echo '<th>物理机IP</th>';
            echo '<th>物理机用户名</th>';
            echo '<th>物理机密码</th>';
            echo '<th>BMC IP</th>';
            echo '<th>BMC用户名</th>';
            echo '<th>BMC密码</th>';
            echo '<th>创建时间</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            if (isset($cluster['physical_machines']) && is_array($cluster['physical_machines'])) {
                foreach ($cluster['physical_machines'] as $pmIndex => $pm) {
                    echo '<tr>';
                    echo '<td>' . ($pmIndex + 1) . '</td>';
                    echo '<td>' . (isset($pm['cluster_pm_ip']) ? $pm['cluster_pm_ip'] : '') . '</td>';
                    echo '<td>' . (isset($pm['cluster_pm_username']) ? $pm['cluster_pm_username'] : '') . '</td>';
                    echo '<td>' . (isset($pm['cluster_pm_password']) ? $pm['cluster_pm_password'] : '') . '</td>';
                    echo '<td>' . (isset($pm['cluster_pm_bmc_ip']) ? $pm['cluster_pm_bmc_ip'] : '') . '</td>';
                    echo '<td>' . (isset($pm['cluster_pm_bmc_username']) ? $pm['cluster_pm_bmc_username'] : '') . '</td>';
                    echo '<td>' . (isset($pm['cluster_pm_bmc_password']) ? $pm['cluster_pm_bmc_password'] : '') . '</td>';
                    echo '<td>' . (isset($pm['cluster_pm_created_at']) ? $pm['cluster_pm_created_at'] : '') . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr>';
                echo '<td colspan="8">该集群下没有物理机</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
            echo '<div class="section-divider"></div>';
        }
    } else {
        // 导出其他类型数据
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        
        if (!empty($selectedColumns)) {
            // 使用用户选择的列
            $exportColumns = $selectedColumns;
            
            // 输出中文表头
            foreach ($exportColumns as $column) {
                echo '<th>' . getFieldLabel($column) . '</th>';
            }
        } else {
            // 导出其他类型数据，使用默认字段列表
            // 定义需要导出的固定字段列表（与查询显示的结果一致）
            $exportColumns = [
                '序号',
                '名称',
                'IP/URL',
                '类型',
                '用户名',
                '密码',
                '备注',
                '创建时间',
                '类别'
            ];
            
            // 输出中文表头
            foreach ($exportColumns as $column) {
                echo '<th>' . $column . '</th>';
            }
        }
        
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        // 输出数据
        foreach ($data as $index => $row) {
            echo '<tr>';
            
            if (!empty($selectedColumns)) {
                // 使用用户选择的列
                foreach ($selectedColumns as $column) {
                    $value = isset($row[$column]) ? $row[$column] : '';
                    
                    // 处理是否有效字段
                    if ($column === 'login_info_is_active') {
                        $value = $value === '1' || $value === 1 ? '有效' : '无效';
                    }
                    
                    echo '<td>' . $value . '</td>';
                }
            } else {
                // 使用默认字段列表
                echo '<td>' . ($index + 1) . '</td>'; // 序号，从1开始
                echo '<td>' . (isset($row['name']) ? $row['name'] : '') . '</td>';
                echo '<td>' . (isset($row['ip_url']) ? $row['ip_url'] : '') . '</td>';
                echo '<td>' . (isset($row['type']) ? $row['type'] : '') . '</td>';
                echo '<td>' . (isset($row['username']) ? $row['username'] : '') . '</td>';
                echo '<td>' . (isset($row['password']) ? $row['password'] : '') . '</td>';
                echo '<td>' . (isset($row['remark']) ? $row['remark'] : '') . '</td>';
                echo '<td>' . (isset($row['created_at']) ? $row['created_at'] : '') . '</td>';
                echo '<td>' . (isset($row['category']) ? $row['category'] : '') . '</td>';
            }
            
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    
    echo '</body>';
    echo '</html>';
    
    $htmlContent = ob_get_clean();
    
    // 设置响应头，确保中文文件名正确显示
    // 先清除可能存在的旧响应头
    header_remove('Content-Disposition');
    
    // 设置正确的Content-Type和字符集
    header('Content-Type: text/html; charset=utf-8');
    
    // 使用RFC 5987编码处理中文文件名
    $encodedFilename = urlencode($filename);
    header('Content-Disposition: attachment; filename*=UTF-8\'\'' . $encodedFilename);
    header('Cache-Control: max-age=0');
    
    // 输出HTML内容，浏览器会自动处理
    echo $htmlContent;
    exit;
}

/**
 * 查询指定表
 * @param PDO $pdo PDO连接对象
 * @param string $table 表名
 * @param string $keyword1 第一个关键词
 * @param string $keyword2 第二个关键词
 * @param int $page 页码
 * @param int $pageSize 每页数量
 * @param array $requestData 请求数据
 * @return array 查询结果
 */
function searchTable($pdo, $table, $keyword1, $keyword2, $page, $pageSize, $requestData = []) {
    $results = [];
    $total = 0;
    
    // 准备参数数组
    $params = [];
    
    // 基础查询SQL
    $baseSql = '';
    $whereClause = '';
    $orderClause = '';
    $category = '';
    
    // 根据表名构建不同的查询逻辑
    switch ($table) {
        case 'login_info':
            // 查询信息系统登录信息 - 返回所有字段
            $baseSql = "SELECT 
                        *, 
                        login_info_id as id, 
                        login_info_system_name as name, 
                        login_info_ip_url as ipUrl, 
                        login_info_login_type as loginType, 
                        login_info_username as username, 
                        login_info_password as password, 
                        login_info_remark as remark, 
                        login_info_created_at as created_at,
                        login_info_updated_at as updated_at,
                        login_info_created_by as createdBy,
                        login_info_is_active as isActive,
                        'system' as category
                    FROM login_info";
            $orderClause = " ORDER BY login_info_created_at DESC";
            $category = 'system';
            break;
            
        case 'server_cred':
            // 查询服务器账号密码 - 返回所有字段
            $baseSql = "SELECT 
                        *, 
                        server_cred_server_name as name, 
                        server_cred_server_ip as ip_url, 
                        server_cred_server_port as port, 
                        server_cred_server_os as os, 
                        server_cred_login_username as username, 
                        server_cred_login_password as password, 
                        server_cred_notes as description, 
                        server_cred_notes as remark,
                        'server' as category
                    FROM server_cred 
                    WHERE is_active = 1";
            $orderClause = " ORDER BY created_at DESC";
            $category = 'server';
            break;
            
        case 'net_dev_cred':
            // 查询网络设备登录信息 - 返回所有字段
            $baseSql = "SELECT 
                        *, 
                        id as id, 
                        net_dev_cred_chinese_name as name, 
                        net_dev_cred_management_ip as ip_url, 
                        net_dev_cred_protocol as protocol, 
                        net_dev_cred_port as port, 
                        net_dev_cred_dev_type as dev_type, 
                        net_dev_cred_username as username, 
                        net_dev_cred_password_hash as password, 
                        net_dev_cred_enable_password_hash as enable_password, 
                        net_dev_cred_description as remark,
                        'network' as category
                    FROM net_dev_cred";
            $orderClause = " ORDER BY created_at DESC";
            $category = 'network';
            break;
            
        case 'cluster':
            // 查询宿主机集群信息 - 包含集群宿主机数量
            $baseSql = "SELECT 
                        c.*, 
                        c.cluster_name, 
                        c.cluster_address, 
                        c.cluster_username, 
                        c.cluster_password, 
                        COUNT(pm.cluster_pm_id) as pm_count, 
                        'cluster' as category
                    FROM cluster c
                    LEFT JOIN cluster_physical_machine pm ON c.cluster_id = pm.cluster_id
                    GROUP BY c.cluster_id";
            $orderClause = " ORDER BY c.cluster_created_at DESC";
            $category = 'cluster';
            break;
            
        case 'cluster_physical_machine':
            // 查询集群关联的物理机信息
            $baseSql = "SELECT 
                        pm.*, 
                        pm.cluster_pm_ip, 
                        pm.cluster_pm_username, 
                        pm.cluster_pm_password, 
                        pm.cluster_pm_bmc_password, 
                        pm.cluster_pm_created_at, 
                        'cluster_physical_machine' as category
                    FROM cluster_physical_machine pm
                    WHERE 1=1";
            
            // 添加cluster_id过滤条件
            if (isset($requestData['clusterId'])) {
                $baseSql .= " AND pm.cluster_id = :clusterId";
                $params[':clusterId'] = $requestData['clusterId'];
            }
            
            $orderClause = " ORDER BY pm.cluster_pm_created_at DESC";
            $category = 'cluster_physical_machine';
            break;
            
        default:
            throw new Exception('无效的表名');
    }
    
    // 获取表的所有字段名
    $fields = [];
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll();
        foreach ($columns as $column) {
            $fields[] = $column['Field'];
        }
    } catch (Exception $e) {
        error_log("获取表 $table 字段失败: " . $e->getMessage());
        throw new Exception("获取表结构失败: " . $e->getMessage());
    }
    
    // 构建查询条件
    $conditions = [];
    
    // 获取关键词匹配方式，默认包含
    $keyword1MatchType = isset($requestData['keyword1MatchType']) ? $requestData['keyword1MatchType'] : 'include';
    $keyword2MatchType = isset($requestData['keyword2MatchType']) ? $requestData['keyword2MatchType'] : 'include';
    
    // 处理第一个关键词
    if (!empty($keyword1)) {
        $keyword1Param = '%' . $keyword1 . '%';
        $keyword1Conditions = [];
        
        foreach ($fields as $field) {
            // 跳过不需要搜索的字段
            if (in_array($field, ['id', 'created_at', 'updated_at', 'is_active', 'login_info_is_active'])) {
                continue;
            }
            $keyword1Conditions[] = "$field LIKE :keyword1";
        }
        
        if (!empty($keyword1Conditions)) {
            if ($keyword1MatchType === 'exclude') {
                // 排除包含关键词的记录：NOT (field1 LIKE :keyword OR field2 LIKE :keyword OR ...)
                $conditions[] = 'NOT (' . implode(' OR ', $keyword1Conditions) . ')';
            } else {
                // 包含关键词的记录：(field1 LIKE :keyword OR field2 LIKE :keyword OR ...)
                $conditions[] = '(' . implode(' OR ', $keyword1Conditions) . ')';
            }
            $params[':keyword1'] = $keyword1Param;
        }
    }
    
    // 处理第二个关键词
    if (!empty($keyword2)) {
        $keyword2Param = '%' . $keyword2 . '%';
        $keyword2Conditions = [];
        
        foreach ($fields as $field) {
            // 跳过不需要搜索的字段
            if (in_array($field, ['id', 'created_at', 'updated_at', 'is_active', 'login_info_is_active'])) {
                continue;
            }
            $keyword2Conditions[] = "$field LIKE :keyword2";
        }
        
        if (!empty($keyword2Conditions)) {
            if ($keyword2MatchType === 'exclude') {
                // 排除包含关键词的记录：NOT (field1 LIKE :keyword OR field2 LIKE :keyword OR ...)
                $conditions[] = 'NOT (' . implode(' OR ', $keyword2Conditions) . ')';
            } else {
                // 包含关键词的记录：(field1 LIKE :keyword OR field2 LIKE :keyword OR ...)
                $conditions[] = '(' . implode(' OR ', $keyword2Conditions) . ')';
            }
            $params[':keyword2'] = $keyword2Param;
        }
    }
    
    // 组合查询条件
    $sql = $baseSql;
    if (!empty($conditions)) {
        // 检查baseSql中是否已经包含WHERE子句
        if (strpos($baseSql, 'WHERE') !== false) {
            $sql .= ' AND ' . implode(' AND ', $conditions);
        } else {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
    }
    $sql .= $orderClause;
    
    // 计算总数 - 对于使用LEFT JOIN和GROUP BY的查询，需要特殊处理
    // 宿主机集群查询使用了LEFT JOIN和GROUP BY，直接计数会导致结果不准确
    // 需要使用子查询或单独的计数逻辑
    $countSql = '';
    
    if ($table === 'cluster') {
        // 对于cluster表，构建专门的集群计数查询
        // 直接统计cluster表的记录数，而非LEFT JOIN后的行数
        $countSql = "SELECT COUNT(*) as total FROM cluster c";
        
        // 添加WHERE条件（从主查询中提取）
        $wherePos = strpos($sql, 'WHERE');
        if ($wherePos !== false) {
            $whereClause = substr($sql, $wherePos);
            $countSql .= ' ' . $whereClause;
        }
    } else {
        // 其他表使用原来的计数查询逻辑
        $fromPos = strpos($sql, 'FROM');
        $orderPos = strpos($sql, 'ORDER BY');
        
        if ($fromPos !== false) {
            // 提取FROM子句及之后的部分
            $fromClause = substr($sql, $fromPos);
            
            // 移除ORDER BY子句（如果存在）
            if ($orderPos !== false) {
                $fromClause = substr($fromClause, 0, $orderPos - $fromPos);
            }
            
            // 构建计数查询
            $countSql = 'SELECT COUNT(*) as total ' . $fromClause;
        } else {
            // 如果无法提取FROM子句，使用简单的计数查询
            $countSql = "SELECT COUNT(*) as total FROM $table";
            
            // 添加WHERE条件（如果有）
            $wherePos = strpos($sql, 'WHERE');
            if ($wherePos !== false) {
                $whereClause = substr($sql, $wherePos);
                $countSql .= ' ' . $whereClause;
            }
        }
    }
    
    // 调试计数查询
    error_log('计数查询SQL: ' . $countSql);
    error_log('计数查询参数: ' . json_encode($params));
    
    $countStmt = $pdo->prepare($countSql);
    
    // 绑定参数
    foreach ($params as $param => $value) {
        $countStmt->bindValue($param, $value, PDO::PARAM_STR);
    }
    
    $countStmt->execute();
    $total = $countStmt->fetchColumn();
    
    // 确保total是数字类型
    $total = intval($total);
    
    // 调试计数结果
    error_log('计数结果: ' . $total);
    
    // 分页查询
    $offset = ($page - 1) * $pageSize;
    $sql .= " LIMIT :offset, :pageSize";
    
    $stmt = $pdo->prepare($sql);
    
    // 绑定参数
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value, PDO::PARAM_STR);
    }
    
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);
    $stmt->execute();
    
    $results = $stmt->fetchAll();
    
    // 解密密码（如果需要）并清理数据
        foreach ($results as &$result) {
            // 先执行解密逻辑，再执行清理数据逻辑
            // 因为加密后的密码可能包含特殊字符，清理逻辑会移除这些字符导致解密失败
            
            // 获取当前用户信息用于审计日志
            $currentUser = SecurityUtils::getCurrentUser();
            
            // 对于信息查询功能，允许解密密码
            $hasDecryptPermission = true;
            
            // 如果无法获取用户信息，使用默认用户名
            if ($currentUser === null) {
                $currentUser = 'system';
            }
            
            // 解密密码字段（包括别名和原始字段名）
            if (isset($result['password']) && $hasDecryptPermission) {
                // 记录解密操作前的密码状态
                $passwordBefore = $result['password'];
                
                // 根据不同表的加密方式解密
                switch ($table) {
                    case 'login_info':
                    case 'server_cred':
                    case 'net_dev_cred':
                    case 'cluster':
                        // 这些表使用SecurityUtils::encrypt加密
                        $decryptedPassword = SecurityUtils::decrypt($result['password']);
                        $result['password'] = $decryptedPassword;
                        
                        // 记录解密操作到日志
                        error_log("[AUDIT] Password decrypted for {$table} by {$currentUser}: " . 
                                 (isset($result['name']) ? $result['name'] : (isset($result['cluster_name']) ? $result['cluster_name'] : 'unknown')));
                        break;
                }
            }
            
            // 解密集群密码字段
            if (isset($result['cluster_password']) && $hasDecryptPermission) {
                // 记录解密操作前的密码状态
                $passwordBefore = $result['cluster_password'];
                
                // 根据不同表的加密方式解密
                if ($table === 'cluster') {
                    // cluster表使用SecurityUtils::encrypt加密
                    $decryptedPassword = SecurityUtils::decrypt($result['cluster_password']);
                    $result['cluster_password'] = $decryptedPassword;
                    
                    // 记录解密操作到日志
                    error_log("[AUDIT] Cluster password decrypted for cluster by {$currentUser}: " . 
                             (isset($result['cluster_name']) ? $result['cluster_name'] : 'unknown'));
                }
            }
            
            // 解密原始密码字段（用于悬浮窗显示）
            if (isset($result['server_cred_login_password']) && $hasDecryptPermission) {
                $decryptedRawPassword = SecurityUtils::decrypt($result['server_cred_login_password']);
                $result['server_cred_login_password'] = $decryptedRawPassword;
            }
            
            if (isset($result['login_info_password']) && $hasDecryptPermission) {
                $decryptedRawPassword = SecurityUtils::decrypt($result['login_info_password']);
                $result['login_info_password'] = $decryptedRawPassword;
            }
            
            // 解密原始密码字段（用于悬浮窗显示）
            if (isset($result['net_dev_cred_password_hash']) && $hasDecryptPermission) {
                $decryptedRawPassword = SecurityUtils::decrypt($result['net_dev_cred_password_hash']);
                $result['net_dev_cred_password_hash'] = $decryptedRawPassword;
            }
            
            // 解密使能密码（包括别名和原始字段名）
            if (isset($result['enable_password']) && $hasDecryptPermission) {
                // 使用SecurityUtils::encrypt加密
                $decryptedEnablePassword = SecurityUtils::decrypt($result['enable_password']);
                $result['enable_password'] = $decryptedEnablePassword;
            }
            
            // 解密原始使能密码字段（用于悬浮窗显示）
            if (isset($result['net_dev_cred_enable_password_hash']) && $hasDecryptPermission) {
                $decryptedEnablePassword = SecurityUtils::decrypt($result['net_dev_cred_enable_password_hash']);
                $result['net_dev_cred_enable_password_hash'] = $decryptedEnablePassword;
            }
            
            // 解密SNMP团体字（如果存在）
            if (isset($result['net_dev_cred_snmp']) && $hasDecryptPermission) {
                // 使用SecurityUtils::encrypt加密
                $decryptedSnmp = SecurityUtils::decrypt($result['net_dev_cred_snmp']);
                $result['net_dev_cred_snmp'] = $decryptedSnmp;
            }
            
            // 解密物理机密码和BMC密码
            if ($table === 'cluster_physical_machine' && $hasDecryptPermission) {
                // 解密物理机密码
                if (isset($result['cluster_pm_password']) && !empty($result['cluster_pm_password'])) {
                    $decryptedPassword = SecurityUtils::decrypt($result['cluster_pm_password']);
                    $result['cluster_pm_password'] = $decryptedPassword;
                    
                    // 记录解密操作到日志
                    error_log("[AUDIT] Physical machine password decrypted by {$currentUser}: cluster_id={$result['cluster_id']}, pm_ip={$result['cluster_pm_ip']}");
                }
                
                // 解密BMC密码
                if (isset($result['cluster_pm_bmc_password']) && !empty($result['cluster_pm_bmc_password'])) {
                    $decryptedBMCPassword = SecurityUtils::decrypt($result['cluster_pm_bmc_password']);
                    $result['cluster_pm_bmc_password'] = $decryptedBMCPassword;
                    
                    // 记录解密操作到日志
                    error_log("[AUDIT] BMC password decrypted by {$currentUser}: cluster_id={$result['cluster_id']}, pm_ip={$result['cluster_pm_ip']}");
                }
            }
            
            // 然后执行清理数据逻辑
            // 确保所有字符串值都是有效的UTF-8
            foreach ($result as $key => &$value) {
                if (is_string($value)) {
                    // 直接使用原始字符串，不进行转换，避免编码问题
                    // 移除控制字符和无效字符，使用UTF-8标志
                    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
                    // 确保字符串不是空的或只包含空格
                    $value = trim($value);
                } elseif (is_null($value)) {
                    // 将NULL值转换为空字符串
                    $value = '';
                } elseif (is_resource($value)) {
                    // 资源类型转换为字符串
                    $value = (string)$value;
                }
            }
        }
    
    return [
        'data' => $results,
        'total' => $total
    ];
}
