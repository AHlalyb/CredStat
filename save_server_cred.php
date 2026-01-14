<?php
/**
 * 服务器账号密码信息保存接口
 * 处理前端提交的服务器信息，进行验证后存储到数据库
 */

// 设置响应头为JSON格式，编码为UTF-8
header('Content-Type: application/json; charset=utf-8');

// 确保只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '仅支持POST请求'
    ]);
    exit;
}

// 加载数据库配置信息
function loadDbConfig() {
    // 检查数据库配置文件是否存在
    $configFile = 'app/config/database.php';
    if (!file_exists($configFile)) {
        return null;
    }
    
    // 包含配置文件
    $config = include($configFile);
    
    // 验证配置是否完整
    $requiredKeys = ['host', 'port', 'dbname', 'username'];
    foreach ($requiredKeys as $key) {
        if (!isset($config[$key]) || empty($config[$key])) {
            return null;
        }
    }
    
    // 密码可以为空，只需要检查是否设置
    if (!isset($config['password'])) {
        $config['password'] = '';
    }
    
    return $config;
}

// 安全的数据清理函数
function sanitizeInput($input) {
    // 使用trim去除首尾空白字符
    $input = trim($input);
    // 转义特殊字符，防止XSS攻击
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

// 验证IP地址格式
function validateIP($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

// 验证端口号
function validatePort($port) {
    $port = intval($port);
    return $port >= 1 && $port <= 65535;
}

// 移除操作系统类型验证，允许任意值
    function validateOS($os) {
        return true;
    }

// 加载SecurityUtils类
require_once __DIR__ . '/app/utils/SecurityUtils.php';

// 连接数据库并保存数据
function saveToDatabase($data) {
    // 加载数据库配置
    $dbConfig = loadDbConfig();
    if (!$dbConfig) {
        logOperation('数据库配置错误：配置文件未找到或不完整', [], true);
        return [
            'success' => false,
            'message' => '数据库配置未找到或不完整，请先配置数据库连接',
            'code' => 'CONFIG_ERROR'
        ];
    }
    
    try {
        // 创建PDO连接（使用预处理语句防止SQL注入）
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // 错误时抛出异常
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, // 使用真实的预处理语句
        ];
        
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        
        // 检查数据表是否存在，如果不存在则尝试创建
        $checkTableSql = "SHOW TABLES LIKE 'server_cred'";
        $stmt = $pdo->query($checkTableSql);
        if ($stmt->rowCount() === 0) {
            try {
                // 尝试创建表（使用新的字段名，包含所有必要字段）
                $createTableSql = "
                CREATE TABLE IF NOT EXISTS `server_cred` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '主键ID',
                  `server_cred_server_name` VARCHAR(100) NOT NULL COMMENT '服务器名称',
                  `server_cred_server_ip` VARCHAR(50) NOT NULL COMMENT '服务器IP地址',
                  `server_cred_server_port` INT NOT NULL DEFAULT 3389 COMMENT '服务器端口号',
                  `server_cred_server_os` VARCHAR(100) NOT NULL COMMENT '操作系统类型',
                  `server_cred_login_username` VARCHAR(100) NOT NULL COMMENT '登录用户名',
                  `server_cred_login_password` VARCHAR(255) NOT NULL COMMENT '加密存储的密码',
                  `server_cred_edr_installed` VARCHAR(10) NOT NULL DEFAULT '是' COMMENT 'EDR安装',
                  `server_cred_ntp_configured` VARCHAR(10) NOT NULL DEFAULT '是' COMMENT 'NTP配置',
                  `server_cred_notes` TEXT COMMENT '备注信息',
                  `server_cred_network_area` VARCHAR(50) DEFAULT '内网' COMMENT '网络区域',
                  `server_cred_server_type` VARCHAR(50) DEFAULT '物理机' COMMENT '服务器类型',
                  `server_cred_host_cluster` VARCHAR(100) DEFAULT '' COMMENT '所属集群',
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
                  `is_active` TINYINT(1) DEFAULT 1 COMMENT '是否有效',
                  `server_cred_created_by` VARCHAR(100) NOT NULL COMMENT '创建人',
                  UNIQUE KEY `uk_server_ip_port_user` (`server_cred_server_ip`, `server_cred_server_port`, `server_cred_login_username`),
                  INDEX `idx_server_name` (`server_cred_server_name`),
                  INDEX `idx_created_at` (`created_at`),
                  INDEX `idx_created_by` (`server_cred_created_by`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务器基本信息表';
                ";
                $pdo->exec($createTableSql);
                logOperation('数据表server_cred创建成功');
            } catch (PDOException $e) {
                logOperation('数据表创建失败: ' . $e->getMessage(), [], true);
                return [
                    'success' => false,
                    'message' => '数据表创建失败，请联系管理员',
                    'code' => 'TABLE_CREATION_ERROR'
                ];
            }
        } else {
            // 检查表结构，确保所有必要字段都存在
            try {
                // 获取表的当前字段
                $describeSql = "DESCRIBE server_cred";
                $stmt = $pdo->query($describeSql);
                $currentFields = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                
                // 定义所有必要字段及其属性
                $requiredFields = [
                    'server_cred_network_area' => "VARCHAR(50) DEFAULT '内网' COMMENT '网络区域'",
                    'server_cred_server_type' => "VARCHAR(50) DEFAULT '物理机' COMMENT '服务器类型'",
                    'server_cred_host_cluster' => "VARCHAR(100) DEFAULT '' COMMENT '所属集群'",
                    'server_cred_edr_installed' => "VARCHAR(10) NOT NULL DEFAULT '是' COMMENT 'EDR安装'",
                    'server_cred_ntp_configured' => "VARCHAR(10) NOT NULL DEFAULT '是' COMMENT 'NTP配置'",
                    'server_cred_notes' => "TEXT COMMENT '备注信息'",
                    'is_active' => "TINYINT(1) DEFAULT 1 COMMENT '是否有效'",
                    'server_cred_created_by' => "VARCHAR(100) NOT NULL DEFAULT 'system' COMMENT '创建人'"
                ];
                
                // 检查并添加缺失的字段
                foreach ($requiredFields as $fieldName => $fieldDefinition) {
                    if (!in_array($fieldName, $currentFields)) {
                        $alterSql = "ALTER TABLE server_cred ADD COLUMN $fieldName $fieldDefinition";
                        $pdo->exec($alterSql);
                        logOperation("字段 $fieldName 添加成功");
                    }
                }
                
                // 检查唯一索引
                $checkIndexSql = "SHOW INDEX FROM server_cred WHERE Key_name = 'uk_server_ip_port_user'";
                $stmt = $pdo->query($checkIndexSql);
                if ($stmt->rowCount() === 0) {
                    $createIndexSql = "ALTER TABLE server_cred ADD UNIQUE KEY uk_server_ip_port_user (server_cred_server_ip, server_cred_server_port, server_cred_login_username)";
                    $pdo->exec($createIndexSql);
                    logOperation('唯一索引 uk_server_ip_port_user 创建成功');
                }
                
            } catch (PDOException $e) {
                logOperation('表结构检查和修复失败: ' . $e->getMessage(), [], true);
                return [
                    'success' => false,
                    'message' => '表结构检查和修复失败，请联系管理员',
                    'code' => 'TABLE_ALTER_ERROR'
                ];
            }
        }
        
        // 准备SQL插入语句（使用新的字段名）
        $sql = "INSERT INTO `server_cred` 
                (`server_cred_server_name`, `server_cred_server_ip`, `server_cred_server_port`, `server_cred_server_os`, 
                 `server_cred_network_area`, `server_cred_server_type`, `server_cred_host_cluster`,
                 `server_cred_login_username`, `server_cred_login_password`, `server_cred_edr_installed`,
                 `server_cred_ntp_configured`, `server_cred_notes`, `server_cred_created_by`)
                VALUES (:serverName, :serverIP, :serverPort, :serverOS, 
                        :networkArea, :serverType, :hostCluster,
                        :username, :password, :edrInstalled,
                        :ntpConfigured, :notes, :createdBy)";
        
        $stmt = $pdo->prepare($sql);
        
        // 绑定参数（预处理语句防止SQL注入）
        $stmt->bindParam(':serverName', $data['serverName'], PDO::PARAM_STR);
        $stmt->bindParam(':serverIP', $data['serverIP'], PDO::PARAM_STR);
        $stmt->bindParam(':serverPort', $data['serverPort'], PDO::PARAM_INT);
        $stmt->bindParam(':serverOS', $data['serverOS'], PDO::PARAM_STR);
        $stmt->bindParam(':networkArea', $data['networkArea'], PDO::PARAM_STR);
        $stmt->bindParam(':serverType', $data['serverType'], PDO::PARAM_STR);
        $stmt->bindParam(':hostCluster', $data['hostCluster'], PDO::PARAM_STR);
        $stmt->bindParam(':username', $data['username'], PDO::PARAM_STR);
        $stmt->bindParam(':password', $data['password'], PDO::PARAM_STR);
        $stmt->bindParam(':edrInstalled', $data['edrInstalled'], PDO::PARAM_STR);
        $stmt->bindParam(':ntpConfigured', $data['ntpConfigured'], PDO::PARAM_STR);
        $stmt->bindParam(':notes', $data['notes'], PDO::PARAM_STR);
        $stmt->bindParam(':createdBy', $data['createdBy'], PDO::PARAM_STR);
        
        // 开始事务以确保数据一致性
        $pdo->beginTransaction();
        
        try {
            // 执行插入操作
            if ($stmt->execute()) {
                $insertId = $pdo->lastInsertId();
                // 提交事务
                $pdo->commit();
                
                return [
                    'success' => true,
                    'message' => '服务器信息保存成功！',
                    'id' => $insertId
                ];
            } else {
                // 获取错误信息
                $errorInfo = $stmt->errorInfo();
                $pdo->rollBack();
                logOperation('数据插入失败: ' . $errorInfo[2], [], true);
                return [
                    'success' => false,
                    'message' => '保存失败：' . $errorInfo[2],
                    'code' => 'INSERT_ERROR'
                ];
            }
        } catch (Exception $e) {
            // 发生异常时回滚事务
            $pdo->rollBack();
            throw $e; // 重新抛出异常由上层处理
        }
    } catch (PDOException $e) {
        // 检查是否是唯一键冲突
        if ($e->getCode() === '23000') {
            logOperation('数据重复: 服务器IP/端口/用户名组合已存在', ['server_ip' => $data['serverIP'], 'port' => $data['serverPort'], 'username' => $data['username']], true);
            return [
                'success' => false,
                'message' => '该服务器的账号已存在，请检查是否重复录入',
                'code' => 'DUPLICATE_ERROR'
            ];
        }
        
        // 数据库连接错误
        if (strpos($e->getMessage(), 'SQLSTATE[HY000]') !== false) {
            logOperation('数据库连接错误: ' . $e->getMessage(), [], true);
            return [
                'success' => false,
                'message' => '数据库连接失败，请检查数据库配置',
                'code' => 'DB_CONNECTION_ERROR'
            ];
        }
        
        logOperation('数据库错误: ' . $e->getMessage(), [], true);
        return [
            'success' => false,
            'message' => '数据库操作错误，请稍后重试',
            'code' => 'DATABASE_ERROR'
        ];
    } catch (Exception $e) {
        logOperation('系统错误: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()], true);
        return [
            'success' => false,
            'message' => '系统处理错误，请联系管理员',
            'code' => 'SYSTEM_ERROR'
        ];
    }
}

// 主处理逻辑
function processRequest() {
    // 获取请求数据，支持JSON格式
    $requestData = [];
    
    // 检查请求类型
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        // 从JSON请求体获取数据
        $rawInput = file_get_contents('php://input');
        $requestData = json_decode($rawInput, true);
        
        // 检查JSON解码是否成功
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'message' => '请求数据格式错误，不是有效的JSON格式'
            ];
        }
    } else {
        // 从表单数据中获取数据
        $requestData = $_POST;
    }
    
    // 从请求数据中获取字段（使用新的字段名）
    $requiredFields = ['server_cred_server_name', 'server_cred_server_ip', 'server_cred_server_os', 'server_cred_server_port', 'server_cred_login_username', 'server_cred_login_password'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($requestData[$field]) || empty(trim($requestData[$field]))) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        return [
            'success' => false,
            'message' => '缺少必要字段：' . implode(', ', $missingFields)
        ];
    }
    
    // 清理和验证数据
    try {
        $serverName = sanitizeInput($requestData['server_cred_server_name']);
        $serverIP = sanitizeInput($requestData['server_cred_server_ip']);
        $serverOS = sanitizeInput($requestData['server_cred_server_os']);
        $serverPort = sanitizeInput($requestData['server_cred_server_port']);
        $username = sanitizeInput($requestData['server_cred_login_username']);
        $password = $requestData['server_cred_login_password']; // 密码不进行HTML转义，后续会哈希处理
        // 兼容旧字段名，优先使用server_cred_notes，如果不存在则使用server_cred_description
        $notes = isset($requestData['server_cred_notes']) ? sanitizeInput($requestData['server_cred_notes']) : '';
        if (empty($notes) && isset($requestData['server_cred_description'])) {
            $notes = sanitizeInput($requestData['server_cred_description']);
        }
        
        // 新增字段处理
        $networkArea = isset($requestData['server_cred_network_area']) ? sanitizeInput($requestData['server_cred_network_area']) : '内网';
        $serverType = isset($requestData['server_cred_server_type']) ? sanitizeInput($requestData['server_cred_server_type']) : '物理机';
        $hostCluster = isset($requestData['server_cred_host_cluster']) ? sanitizeInput($requestData['server_cred_host_cluster']) : '';
        // EDR安装和NTP配置字段
        $edrInstalled = isset($requestData['server_cred_edr_installed']) ? sanitizeInput($requestData['server_cred_edr_installed']) : '是';
        $ntpConfigured = isset($requestData['server_cred_ntp_configured']) ? sanitizeInput($requestData['server_cred_ntp_configured']) : '是';
        // 验证创建人信息，不能为空
        $createdBy = isset($requestData['server_cred_created_by']) ? sanitizeInput($requestData['server_cred_created_by']) : '';
        if (empty($createdBy)) {
            return [
                'success' => false,
                'message' => '创建人信息不能为空',
                'code' => 'MISSING_CREATED_BY'
            ];
        }
        
        // 验证字段映射的完整性
        $receivedFields = array_keys($requestData);
        $expectedFields = [
            'server_cred_server_name', 'server_cred_server_ip', 'server_cred_server_os', 
            'server_cred_server_port', 'server_cred_login_username', 'server_cred_login_password',
            'server_cred_notes',
            'server_cred_network_area', 'server_cred_server_type', 'server_cred_host_cluster',
            'server_cred_edr_installed', 'server_cred_ntp_configured',
            'server_cred_created_by'
        ];
        
        // 记录接收到但预期外的字段，用于调试
        $unexpectedFields = array_diff($receivedFields, $expectedFields);
        if (!empty($unexpectedFields)) {
            logOperation('收到未预期的表单字段', ['unexpected_fields' => $unexpectedFields]);
        }
        
        // 验证数据格式
        if (!validateIP($serverIP)) {
            return [
                'success' => false,
                'message' => '无效的服务器IP地址'
            ];
        }
        
        // 确保端口号被正确转换为整数类型
        $serverPort = intval($serverPort);
        if (!validatePort($serverPort)) {
            return [
                'success' => false,
                'message' => '无效的端口号，端口号必须在1-65535之间'
            ];
        }
        
        // 不再验证操作系统类型，允许任意值
        // 移除了validateOS调用，允许用户输入任何操作系统类型
        
        // 验证字段长度
        if (strlen($serverName) > 100) {
            return [
                'success' => false,
                'message' => '服务器名称长度不能超过100个字符'
            ];
        }
        
        if (strlen($username) > 100) {
            return [
                'success' => false,
                'message' => '用户名长度不能超过100个字符'
            ];
        }
        
        if (strlen($notes) > 65535) {
            return [
                'success' => false,
                'message' => '备注信息长度不能超过65535个字符'
            ];
        }
        
        // 验证宿主机集群选择值的合法性（仅当服务器类型为虚拟机时）
        if ($serverType === '虚拟机' && !empty($hostCluster)) {
            // 验证宿主机集群是否存在于cluster表中
            $dbConfig = loadDbConfig();
            if ($dbConfig) {
                try {
                    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ];
                    
                    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
                    
                    // 检查宿主机集群是否存在
                    $checkSql = "SELECT COUNT(*) as count FROM cluster WHERE cluster_name = :clusterName";
                    $stmt = $pdo->prepare($checkSql);
                    $stmt->bindParam(':clusterName', $hostCluster, PDO::PARAM_STR);
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result['count'] == 0) {
                        return [
                            'success' => false,
                            'message' => '选择的宿主机集群不存在，请重新选择'
                        ];
                    }
                } catch (PDOException $e) {
                    // 数据库连接或查询错误，记录日志但不影响保存
                    logOperation('验证宿主机集群失败: ' . $e->getMessage(), [], true);
                }
            }
        }
        
        // 对密码进行可逆加密处理，使用SecurityUtils类
        $encryptedPassword = SecurityUtils::encrypt($password);
        
        // 准备保存到数据库的数据
        $data = [
            'serverName' => $serverName,
            'serverIP' => $serverIP,
            'serverPort' => $serverPort, // 已转换为整数类型
            'serverOS' => $serverOS,
            'networkArea' => $networkArea,
            'serverType' => $serverType,
            'hostCluster' => $hostCluster,
            'username' => $username,
            'password' => $encryptedPassword,
            'edrInstalled' => $edrInstalled,
            'ntpConfigured' => $ntpConfigured,
            'notes' => $notes,
            'createdBy' => $createdBy
        ];
        
        // 保存到数据库
        return saveToDatabase($data);
    } catch (Exception $e) {
        logOperation('数据清理和验证过程出错: ' . $e->getMessage(), [], true);
        return [
            'success' => false,
            'message' => '数据处理错误，请检查输入格式',
            'code' => 'DATA_PROCESSING_ERROR'
        ];
    }
}

// 记录操作日志
function logOperation($message, $data = [], $isError = false) {
    $logLevel = $isError ? 'ERROR' : 'INFO';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$logLevel] $message";
    
    // 如果有附加数据，添加到日志中（确保不记录明文密码）
    if (!empty($data)) {
        $safeData = $data;
        // 移除可能的敏感信息
        unset($safeData['password'], $safeData['server_cred_login_password']);
        $logMessage .= " | 数据: " . json_encode($safeData);
    }
    
    // 写入错误日志
    error_log($logMessage);
    
    // 也可以写入到自定义日志文件（确保目录可写）
    if (is_writable('logs')) {
        file_put_contents('logs/server_cred.log', $logMessage . "\n", FILE_APPEND);
    }
}

// 执行处理逻辑并返回结果
$result = processRequest();

// 记录操作结果日志
if (isset($result['success'])) {
    if ($result['success']) {
        logOperation('服务器信息保存成功', ['id' => isset($result['id']) ? $result['id'] : '未知']);
    } else {
        logOperation('服务器信息保存失败: ' . $result['message'], ['errorCode' => isset($result['code']) ? $result['code'] : 'unknown'], true);
    }
}

// 返回JSON响应
echo json_encode($result, JSON_UNESCAPED_UNICODE);

exit;
