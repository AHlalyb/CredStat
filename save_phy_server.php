<?php
/**
 * 物理服务器信息保存接口
 * 处理前端提交的物理服务器信息，进行验证后存储到数据库
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

// 验证日期格式
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// 加载SecurityUtils类
require_once __DIR__ . '/app/utils/SecurityUtils.php';

// 日志记录函数
function logOperation($message, $data = [], $isError = false) {
    $logDir = __DIR__ . '/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/phy_server.log';
    $timestamp = date('Y-m-d H:i:s');
    $level = $isError ? 'ERROR' : 'INFO';
    $logEntry = "[{$timestamp}] [{$level}] {$message}";
    
    if (!empty($data)) {
        $logEntry .= ' Data: ' . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    $logEntry .= "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// 连接数据库并保存数据
function saveToDatabase($data, $images = []) {
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
        
        // 调试：显示当前连接的数据库名
        try {
            $dbNameSql = "SELECT DATABASE()";
            $stmt = $pdo->query($dbNameSql);
            $dbName = $stmt->fetchColumn();
            logOperation('当前连接的数据库', ['dbname' => $dbName]);
        } catch (PDOException $e) {
            logOperation('获取数据库名失败: ' . $e->getMessage(), [], true);
        }
        
        // 调试：显示所有表
        try {
            $allTablesSql = "SHOW TABLES";
            $stmt = $pdo->query($allTablesSql);
            $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            logOperation('当前数据库中的所有表', $allTables);
        } catch (PDOException $e) {
            logOperation('获取所有表失败: ' . $e->getMessage(), [], true);
        }
        
        // 无论主表是否存在，都确保图片表存在且结构正确
        try {
            // 先检查并删除旧表（如果存在），确保表结构正确
            $dropTableSql = "DROP TABLE IF EXISTS `phy_servers_images`";
            $pdo->exec($dropTableSql);
            logOperation('旧数据表phy_servers_images已删除（如果存在）');
            
            // 创建图片信息表
            $createImagesTableSql = "
            CREATE TABLE `phy_servers_images` (
              `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '主键ID',
              `server_id` INT NOT NULL COMMENT '关联的服务器ID',
              `image_name` VARCHAR(255) NOT NULL COMMENT '图片文件名',
              `image_type` VARCHAR(50) NOT NULL COMMENT '图片类型',
              `image_data` LONGBLOB NOT NULL COMMENT '图片二进制数据',
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
              FOREIGN KEY (`server_id`) REFERENCES `phy_server_info`(`id`) ON DELETE CASCADE,
              INDEX `idx_server_id` (`server_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='物理服务器图片信息表';
            ";
            $pdo->exec($createImagesTableSql);
            logOperation('数据表phy_servers_images创建成功');
            
            // 检查图片表结构
            $checkImageTableSql = "SHOW COLUMNS FROM `phy_servers_images`";
            $stmt = $pdo->query($checkImageTableSql);
            $imageTableColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            logOperation('phy_servers_images表结构', $imageTableColumns);
            
            // 检查server_id列是否存在
            $hasServerId = false;
            foreach ($imageTableColumns as $column) {
                if ($column['Field'] === 'server_id') {
                    $hasServerId = true;
                    break;
                }
            }
            logOperation('server_id列是否存在', ['hasServerId' => $hasServerId]);
        } catch (PDOException $e) {
            logOperation('图片表创建或检查失败: ' . $e->getMessage(), [], true);
        }
        
        // 检查数据表是否存在，如果不存在则尝试创建
        $checkTableSql = "SHOW TABLES LIKE 'phy_server_info'";
        $stmt = $pdo->query($checkTableSql);
        if ($stmt->rowCount() === 0) {
            try {
                // 尝试创建主表
                $createTableSql = "
                CREATE TABLE IF NOT EXISTS `phy_server_info` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '主键ID',
                  `phyServerRoom` VARCHAR(100) NOT NULL COMMENT '机房/站点',
                  `phyServerCabinet` VARCHAR(50) NOT NULL COMMENT '机柜编号',
                  `phyServerCabinetPosition` VARCHAR(50) NOT NULL COMMENT 'U位',
                  `phyServerBrand` VARCHAR(50) NOT NULL COMMENT '厂商',
                  `phyServerModel` VARCHAR(100) NOT NULL COMMENT '型号',
                  `phyServerSn` VARCHAR(100) NOT NULL COMMENT '序列号',
                  `phyServerBmcIp` VARCHAR(50) NOT NULL COMMENT 'BMC地址',
                  `phyServerBmcUsername` VARCHAR(100) NOT NULL COMMENT 'BMC用户名',
                  `phyServerBmcPassword` VARCHAR(255) NOT NULL COMMENT '加密存储的BMC密码',
                  `purchaseDate` DATE NOT NULL COMMENT '采购日期',
                  `maintenanceDate` DATE NOT NULL COMMENT '维保截止日期',
                  `powerSupplyCount` INT NOT NULL DEFAULT 1 COMMENT '电源数量',
                  `phyServerNotes` TEXT COMMENT '备注信息',
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
                  `phyServerCreatedby` VARCHAR(100) NOT NULL COMMENT '创建人',
                  INDEX `idx_room_cabinet` (`phyServerRoom`, `phyServerCabinet`),
                  INDEX `idx_brand_model` (`phyServerBrand`, `phyServerModel`),
                  INDEX `idx_created_at` (`created_at`),
                  INDEX `idx_created_by` (`phyServerCreatedby`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='物理服务器信息表';
                ";
                $pdo->exec($createTableSql);
                logOperation('数据表phy_server_info创建成功');
                
                // 创建硬盘信息表
                $createDiskTableSql = "
                CREATE TABLE IF NOT EXISTS `phy_server_hard_disk` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '主键ID',
                  `server_id` INT NOT NULL COMMENT '关联的服务器ID',
                  `slot` INT NOT NULL COMMENT '硬盘槽位',
                  `size` VARCHAR(50) NOT NULL COMMENT '硬盘大小',
                  `raidName` VARCHAR(50) NOT NULL COMMENT 'RAID名称',
                  `raidLevel` VARCHAR(20) NOT NULL COMMENT 'RAID级别',
                  `remark` TEXT COMMENT '备注信息',
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                  FOREIGN KEY (`server_id`) REFERENCES `phy_server_info`(`id`) ON DELETE CASCADE,
                  INDEX `idx_server_id` (`server_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='物理服务器硬盘信息表';
                ";
                $pdo->exec($createDiskTableSql);
                logOperation('数据表phy_server_hard_disk创建成功');
                
                // 创建网卡信息表
                $createNicTableSql = "
                CREATE TABLE IF NOT EXISTS `phy_server_nic` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '主键ID',
                  `server_id` INT NOT NULL COMMENT '关联的服务器ID',
                  `position` VARCHAR(50) NOT NULL COMMENT '网卡位置',
                  `portCount` VARCHAR(20) NOT NULL COMMENT '网口数量',
                  `speed` VARCHAR(20) NOT NULL COMMENT '速率规格',
                  `interface` VARCHAR(20) NOT NULL COMMENT '接口类型',
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                  FOREIGN KEY (`server_id`) REFERENCES `phy_server_info`(`id`) ON DELETE CASCADE,
                  INDEX `idx_server_id` (`server_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='物理服务器网卡信息表';
                ";
                $pdo->exec($createNicTableSql);
                logOperation('数据表phy_server_nic创建成功');
                
                // 创建HBA卡信息表
                $createHbacardTableSql = "
                CREATE TABLE IF NOT EXISTS `phy_server_hbacard` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '主键ID',
                  `server_id` INT NOT NULL COMMENT '关联的服务器ID',
                  `portCount` VARCHAR(20) NOT NULL COMMENT '端口数量',
                  `speed` VARCHAR(20) NOT NULL COMMENT '端口速度',
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                  FOREIGN KEY (`server_id`) REFERENCES `phy_server_info`(`id`) ON DELETE CASCADE,
                  INDEX `idx_server_id` (`server_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='物理服务器HBA卡信息表';
                ";
                $pdo->exec($createHbacardTableSql);
                logOperation('数据表phy_server_hbacard创建成功');
                
                // 创建连接信息表
                $createConnectionTableSql = "
                CREATE TABLE IF NOT EXISTS `phy_server_connection` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '主键ID',
                  `server_id` INT NOT NULL COMMENT '关联的服务器ID',
                  `interfaceName` VARCHAR(50) NOT NULL COMMENT '接口名称',
                  `cableType` VARCHAR(20) NOT NULL COMMENT '线缆类型',
                  `peerDeviceName` VARCHAR(100) NOT NULL COMMENT '对端设备名称',
                  `peerDeviceInterface` VARCHAR(50) NOT NULL COMMENT '对端设备接口',
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                  FOREIGN KEY (`server_id`) REFERENCES `phy_server_info`(`id`) ON DELETE CASCADE,
                  INDEX `idx_server_id` (`server_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='物理服务器连接信息表';
                ";
                $pdo->exec($createConnectionTableSql);
                logOperation('数据表phy_server_connection创建成功');
            } catch (PDOException $e) {
                logOperation('数据表创建失败: ' . $e->getMessage(), [], true);
                return [
                    'success' => false,
                    'message' => '数据表创建失败，请联系管理员',
                    'code' => 'TABLE_CREATION_ERROR'
                ];
            }
        }
        
        // 开始事务
        $pdo->beginTransaction();
        
        try {
            // 准备SQL插入语句（主表）
            $sql = "INSERT INTO `phy_server_info` 
                    (`phyServerRoom`, `phyServerCabinet`, `phyServerCabinetPosition`, `phyServerBrand`, 
                     `phyServerModel`, `phyServerSn`, `phyServerBmcIp`, `phyServerBmcUsername`, 
                     `phyServerBmcPassword`, `purchaseDate`, `maintenanceDate`, `powerSupplyCount`, 
                     `phyServerNotes`, `phyServerCreatedby`)
                    VALUES (:phyServerRoom, :phyServerCabinet, :phyServerCabinetPosition, :phyServerBrand, 
                            :phyServerModel, :phyServerSn, :phyServerBmcIp, :phyServerBmcUsername, 
                            :phyServerBmcPassword, :purchaseDate, :maintenanceDate, :powerSupplyCount, 
                            :phyServerNotes, :phyServerCreatedby)";
            
            $stmt = $pdo->prepare($sql);
            
            // 加密BMC密码
            $encryptedBmcPassword = SecurityUtils::encrypt($data['phyServerBmcPassword']);
            
            // 绑定参数（预处理语句防止SQL注入）
            $stmt->bindParam(':phyServerRoom', $data['phyServerRoom'], PDO::PARAM_STR);
            $stmt->bindParam(':phyServerCabinet', $data['phyServerCabinet'], PDO::PARAM_STR);
            $stmt->bindParam(':phyServerCabinetPosition', $data['phyServerCabinetPosition'], PDO::PARAM_STR);
            $stmt->bindParam(':phyServerBrand', $data['phyServerBrand'], PDO::PARAM_STR);
            $stmt->bindParam(':phyServerModel', $data['phyServerModel'], PDO::PARAM_STR);
            $stmt->bindParam(':phyServerSn', $data['phyServerSn'], PDO::PARAM_STR);
            $stmt->bindParam(':phyServerBmcIp', $data['phyServerBmcIp'], PDO::PARAM_STR);
            $stmt->bindParam(':phyServerBmcUsername', $data['phyServerBmcUsername'], PDO::PARAM_STR);
            $stmt->bindParam(':phyServerBmcPassword', $encryptedBmcPassword, PDO::PARAM_STR);
            $stmt->bindParam(':purchaseDate', $data['purchaseDate'], PDO::PARAM_STR);
            $stmt->bindParam(':maintenanceDate', $data['maintenanceDate'], PDO::PARAM_STR);
            $stmt->bindParam(':powerSupplyCount', $data['powerSupplyCount'], PDO::PARAM_INT);
            $stmt->bindParam(':phyServerNotes', $data['phyServerNotes'], PDO::PARAM_STR);
            $stmt->bindParam(':phyServerCreatedby', $data['createdBy'], PDO::PARAM_STR);
            
            // 执行插入操作
            $stmt->execute();
            
            // 获取插入的服务器ID
            $serverId = $pdo->lastInsertId();
            
            // 插入硬盘信息
            if (!empty($data['hardDisks']) && is_array($data['hardDisks'])) {
                $diskSql = "INSERT INTO `phy_server_hard_disk` 
                           (`server_id`, `slot`, `size`, `raidName`, `raidLevel`, `remark`)
                           VALUES (:server_id, :slot, :size, :raidName, :raidLevel, :remark)";
                $diskStmt = $pdo->prepare($diskSql);
                
                foreach ($data['hardDisks'] as $disk) {
                    if (is_array($disk)) {
                        $slot = isset($disk['slot']) ? intval($disk['slot']) : 0;
                        $size = isset($disk['size']) ? strval($disk['size']) : '';
                        $raidName = isset($disk['raidName']) ? strval($disk['raidName']) : '';
                        $raidLevel = isset($disk['raidLevel']) ? strval($disk['raidLevel']) : '';
                        $remark = isset($disk['remark']) ? strval($disk['remark']) : '';
                        
                        $diskStmt->bindParam(':server_id', $serverId, PDO::PARAM_INT);
                        $diskStmt->bindParam(':slot', $slot, PDO::PARAM_INT);
                        $diskStmt->bindParam(':size', $size, PDO::PARAM_STR);
                        $diskStmt->bindParam(':raidName', $raidName, PDO::PARAM_STR);
                        $diskStmt->bindParam(':raidLevel', $raidLevel, PDO::PARAM_STR);
                        $diskStmt->bindParam(':remark', $remark, PDO::PARAM_STR);
                        $diskStmt->execute();
                        
                        logOperation('保存硬盘信息', [
                            'server_id' => $serverId,
                            'slot' => $slot,
                            'size' => $size,
                            'raidName' => $raidName,
                            'raidLevel' => $raidLevel,
                            'remark' => $remark
                        ]);
                    }
                }
            }
            
            // 插入网卡信息
            if (!empty($data['nics']) && is_array($data['nics'])) {
                $nicSql = "INSERT INTO `phy_server_nic` 
                          (`server_id`, `position`, `portCount`, `speed`, `interface`)
                          VALUES (:server_id, :position, :portCount, :speed, :interface)";
                $nicStmt = $pdo->prepare($nicSql);
                
                foreach ($data['nics'] as $nic) {
                    if (is_array($nic)) {
                        $position = isset($nic['position']) ? strval($nic['position']) : '';
                        $portCount = isset($nic['portCount']) ? strval($nic['portCount']) : '';
                        $speed = isset($nic['speed']) ? strval($nic['speed']) : '';
                        $interface = isset($nic['interface']) ? strval($nic['interface']) : '';
                        
                        $nicStmt->bindParam(':server_id', $serverId, PDO::PARAM_INT);
                        $nicStmt->bindParam(':position', $position, PDO::PARAM_STR);
                        $nicStmt->bindParam(':portCount', $portCount, PDO::PARAM_STR);
                        $nicStmt->bindParam(':speed', $speed, PDO::PARAM_STR);
                        $nicStmt->bindParam(':interface', $interface, PDO::PARAM_STR);
                        $nicStmt->execute();
                        
                        logOperation('保存网卡信息', [
                            'server_id' => $serverId,
                            'position' => $position,
                            'portCount' => $portCount,
                            'speed' => $speed,
                            'interface' => $interface
                        ]);
                    }
                }
            }
            
            // 插入HBA卡信息
            if (!empty($data['hbacards']) && is_array($data['hbacards'])) {
                $hbacardSql = "INSERT INTO `phy_server_hbacard` 
                              (`server_id`, `portCount`, `speed`)
                              VALUES (:server_id, :portCount, :speed)";
                $hbacardStmt = $pdo->prepare($hbacardSql);
                
                foreach ($data['hbacards'] as $hbacard) {
                    if (is_array($hbacard)) {
                        $portCount = isset($hbacard['portCount']) ? strval($hbacard['portCount']) : '';
                        $speed = isset($hbacard['speed']) ? strval($hbacard['speed']) : '';
                        
                        $hbacardStmt->bindParam(':server_id', $serverId, PDO::PARAM_INT);
                        $hbacardStmt->bindParam(':portCount', $portCount, PDO::PARAM_STR);
                        $hbacardStmt->bindParam(':speed', $speed, PDO::PARAM_STR);
                        $hbacardStmt->execute();
                        
                        logOperation('保存HBA卡信息', [
                            'server_id' => $serverId,
                            'portCount' => $portCount,
                            'speed' => $speed
                        ]);
                    }
                }
            }
            
            // 插入连接信息
            if (!empty($data['connections']) && is_array($data['connections'])) {
                $connectionSql = "INSERT INTO `phy_server_connection` 
                                 (`server_id`, `interfaceName`, `cableType`, `peerDeviceName`, `peerDeviceInterface`)
                                 VALUES (:server_id, :interfaceName, :cableType, :peerDeviceName, :peerDeviceInterface)";
                $connectionStmt = $pdo->prepare($connectionSql);
                
                foreach ($data['connections'] as $connection) {
                    if (is_array($connection)) {
                        $interfaceName = isset($connection['interfaceName']) ? strval($connection['interfaceName']) : '';
                        $cableType = isset($connection['cableType']) ? strval($connection['cableType']) : '';
                        $peerDeviceName = isset($connection['peerDeviceName']) ? strval($connection['peerDeviceName']) : '';
                        $peerDeviceInterface = isset($connection['peerDeviceInterface']) ? strval($connection['peerDeviceInterface']) : '';
                        
                        $connectionStmt->bindParam(':server_id', $serverId, PDO::PARAM_INT);
                        $connectionStmt->bindParam(':interfaceName', $interfaceName, PDO::PARAM_STR);
                        $connectionStmt->bindParam(':cableType', $cableType, PDO::PARAM_STR);
                        $connectionStmt->bindParam(':peerDeviceName', $peerDeviceName, PDO::PARAM_STR);
                        $connectionStmt->bindParam(':peerDeviceInterface', $peerDeviceInterface, PDO::PARAM_STR);
                        $connectionStmt->execute();
                        
                        logOperation('保存连接信息', [
                            'server_id' => $serverId,
                            'interfaceName' => $interfaceName,
                            'cableType' => $cableType,
                            'peerDeviceName' => $peerDeviceName,
                            'peerDeviceInterface' => $peerDeviceInterface
                        ]);
                    }
                }
            }
            
            // 插入图片信息
            $imageCount = 0;
            if (!empty($images) && is_array($images)) {
                try {
                    // 再次检查表结构，确保万无一失
                    $checkImageTableSql = "SHOW COLUMNS FROM `phy_servers_images`";
                    $stmt = $pdo->query($checkImageTableSql);
                    $imageTableColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    logOperation('插入前phy_servers_images表结构', $imageTableColumns);
                    
                    // 构建插入SQL，只使用实际存在的列
                    $availableColumns = [];
                    $bindParams = [];
                    foreach ($imageTableColumns as $column) {
                        $availableColumns[] = $column['Field'];
                    }
                    logOperation('可用列', $availableColumns);
                    
                    // 检查必要列是否存在
                    $requiredColumns = ['server_id', 'image_name', 'image_type', 'image_data'];
                    $missingColumns = array_diff($requiredColumns, $availableColumns);
                    if (!empty($missingColumns)) {
                        logOperation('缺少必要列', ['missingColumns' => $missingColumns], true);
                        // 如果缺少必要列，跳过图片保存
                        $imageCount = 0;
                    } else {
                        // 构建插入SQL
                        $insertColumns = implode(', ', array_map(function($col) { return "`$col`"; }, $requiredColumns));
                        $insertValues = implode(', ', array_map(function($col) { return ":$col"; }, $requiredColumns));
                        $imageSql = "INSERT INTO `phy_servers_images` ($insertColumns) VALUES ($insertValues)";
                        logOperation('图片插入SQL', ['sql' => $imageSql]);
                        
                        $imageStmt = $pdo->prepare($imageSql);
                        
                        foreach ($images as $image) {
                            if (is_array($image) && isset($image['name'], $image['type'], $image['data'])) {
                                $imageStmt->bindParam(':server_id', $serverId, PDO::PARAM_INT);
                                $imageStmt->bindParam(':image_name', $image['name'], PDO::PARAM_STR);
                                $imageStmt->bindParam(':image_type', $image['type'], PDO::PARAM_STR);
                                $imageStmt->bindParam(':image_data', $image['data'], PDO::PARAM_LOB);
                                $imageStmt->execute();
                                $imageCount++;
                                
                                logOperation('保存图片信息', [
                                    'server_id' => $serverId,
                                    'image_name' => $image['name'],
                                    'image_type' => $image['type']
                                ]);
                            }
                        }
                    }
                } catch (PDOException $e) {
                    logOperation('图片插入失败: ' . $e->getMessage(), [], true);
                    // 继续执行，不影响主表数据保存
                    $imageCount = 0;
                }
            }
            
            // 提交事务
            $pdo->commit();
            
            logOperation('物理服务器信息保存成功', ['server_id' => $serverId, 'image_count' => $imageCount]);
            return [
                'success' => true,
                'message' => '物理服务器信息保存成功，共保存了' . 
                             count($data['hardDisks']) . '个硬盘、' .
                             count($data['nics']) . '个网卡、' .
                             count($data['hbacards']) . '个HBA卡、' .
                             count($data['connections']) . '个连接信息、' .
                             $imageCount . '张图片',
                'data' => ['server_id' => $serverId]
            ];
        } catch (PDOException $e) {
            // 回滚事务
            $pdo->rollBack();
            
            logOperation('数据库操作失败: ' . $e->getMessage(), [], true);
            return [
                'success' => false,
                'message' => '数据库操作失败：' . $e->getMessage(),
                'code' => 'DATABASE_ERROR'
            ];
        }
    } catch (Exception $e) {
        logOperation('系统错误: ' . $e->getMessage(), [], true);
        return [
            'success' => false,
            'message' => '系统错误：' . $e->getMessage(),
            'code' => 'SYSTEM_ERROR'
        ];
    }
}

// 处理multipart/form-data请求
$requestData = [];
if (isset($_POST['formData'])) {
    $requestData = json_decode($_POST['formData'], true);
    // 记录请求数据（不包含图片）
    logOperation('收到物理服务器信息保存请求', $requestData);
} else {
    // 兼容旧的JSON格式请求
    $rawInput = file_get_contents('php://input');
    $requestData = json_decode($rawInput, true);
    // 记录请求数据
    logOperation('收到物理服务器信息保存请求', $requestData);
}

// 验证请求数据
if (empty($requestData)) {
    echo json_encode([
        'success' => false,
        'message' => '请求数据为空'
    ]);
    exit;
}

// 数据验证
$requiredFields = [
    'phyServerRoom' => '机房/站点',
    'phyServerCabinet' => '机柜编号',
    'phyServerCabinetPosition' => 'U位',
    'phyServerBrand' => '厂商',
    'phyServerModel' => '型号',
    'phyServerSn' => '序列号',
    'phyServerBmcIp' => 'BMC地址',
    'phyServerBmcUsername' => 'BMC用户名',
    'phyServerBmcPassword' => 'BMC密码',
    'purchaseDate' => '采购日期',
    'maintenanceDate' => '维保截止日期'
];

foreach ($requiredFields as $field => $label) {
    if (!isset($requestData[$field]) || empty(trim($requestData[$field]))) {
        echo json_encode([
            'success' => false,
            'message' => $label . '不能为空'
        ]);
        exit;
    }
}

// 验证IP地址格式
if (!validateIP($requestData['phyServerBmcIp'])) {
    echo json_encode([
        'success' => false,
        'message' => 'BMC地址格式不正确'
    ]);
    exit;
}

// 验证日期格式
if (!validateDate($requestData['purchaseDate'])) {
    echo json_encode([
        'success' => false,
        'message' => '采购日期格式不正确'
    ]);
    exit;
}

if (!validateDate($requestData['maintenanceDate'])) {
    echo json_encode([
        'success' => false,
        'message' => '维保截止日期格式不正确'
    ]);
    exit;
}

// 获取当前登录用户
$createdBy = SecurityUtils::getCurrentUser() ?? 'system';

// 准备数据
$data = [
    'phyServerRoom' => sanitizeInput($requestData['phyServerRoom']),
    'phyServerCabinet' => sanitizeInput($requestData['phyServerCabinet']),
    'phyServerCabinetPosition' => sanitizeInput($requestData['phyServerCabinetPosition']),
    'phyServerBrand' => sanitizeInput($requestData['phyServerBrand']),
    'phyServerModel' => sanitizeInput($requestData['phyServerModel']),
    'phyServerSn' => sanitizeInput($requestData['phyServerSn']),
    'phyServerBmcIp' => sanitizeInput($requestData['phyServerBmcIp']),
    'phyServerBmcUsername' => sanitizeInput($requestData['phyServerBmcUsername']),
    'phyServerBmcPassword' => $requestData['phyServerBmcPassword'],
    'purchaseDate' => $requestData['purchaseDate'],
    'maintenanceDate' => $requestData['maintenanceDate'],
    'powerSupplyCount' => isset($requestData['powerSupplyCount']) ? intval($requestData['powerSupplyCount']) : 1,
    'phyServerNotes' => isset($requestData['phyServerNotes']) ? sanitizeInput($requestData['phyServerNotes']) : '',
    'hardDisks' => isset($requestData['hardDisks']) && is_array($requestData['hardDisks']) ? $requestData['hardDisks'] : [],
    'nics' => isset($requestData['nics']) && is_array($requestData['nics']) ? $requestData['nics'] : [],
    'hbacards' => isset($requestData['hbacards']) && is_array($requestData['hbacards']) ? $requestData['hbacards'] : [],
    'connections' => isset($requestData['connections']) && is_array($requestData['connections']) ? $requestData['connections'] : [],
    'createdBy' => $createdBy
];

// 处理图片数据
$images = [];
if (isset($_FILES['images']) && !empty($_FILES['images']['name'])) {
    // 单张图片情况
    if (is_string($_FILES['images']['name'])) {
        if ($_FILES['images']['error'] === UPLOAD_ERR_OK) {
            $images[] = [
                'name' => $_FILES['images']['name'],
                'type' => $_FILES['images']['type'],
                'data' => file_get_contents($_FILES['images']['tmp_name'])
            ];
        }
    } else {
        // 多张图片情况
        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $images[] = [
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'data' => file_get_contents($_FILES['images']['tmp_name'][$i])
                ];
            }
        }
    }
    logOperation('处理图片数据', ['image_count' => count($images)]);
}

// 保存到数据库
$result = saveToDatabase($data, $images);

// 输出结果
echo json_encode($result, JSON_UNESCAPED_UNICODE);