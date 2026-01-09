<?php
/**
 * 保存宿主机集群信息
 * 处理宿主机集群表单数据的提交
 */

// 设置响应头
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Username');
header('Access-Control-Expose-Headers: *');
header('Access-Control-Max-Age: 3600');
header('Access-Control-Allow-Credentials: true');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// 设置错误报告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 加载数据库配置
$dbConfig = require __DIR__ . '/app/config/database.php';

// 加载SecurityUtils类
require_once __DIR__ . '/app/utils/SecurityUtils.php';

// 初始化响应
$response = [
    'success' => false,
    'message' => '保存失败',
    'data' => []
];

// 配置日志文件路径
$logsDir = __DIR__ . '/logs';
if (!file_exists($logsDir)) {
    mkdir($logsDir, 0755, true);
}
$logFile = $logsDir . '/cluster.log';

// 自定义日志记录函数
function writeLog($message, $requestId = null) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] " . ($requestId ? "[{$requestId}] " : "") . $message . "\n";
    
    // 直接写入文件，确保日志能够被正确记录
    $fileHandle = fopen($logFile, 'a');
    if ($fileHandle) {
        $result = fwrite($fileHandle, $logMessage);
        fclose($fileHandle);
        return $result !== false;
    }
    
    // 如果直接写入失败，尝试使用error_log函数
    return error_log($logMessage, 3, $logFile);
}

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 获取请求数据
        global $rawInput;
        if (!isset($rawInput)) {
            $rawInput = file_get_contents('php://input');
        }
        $requestData = json_decode($rawInput, true);
        
        // 记录请求开始
        $requestId = uniqid('cluster_', true);
        writeLog("开始处理宿主机集群录入请求", $requestId);
        writeLog("请求数据: " . $rawInput, $requestId);
        
        // 验证请求数据
        if (empty($requestData)) {
            $response['message'] = '无效的请求数据';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 提取集群基础信息
        $clusterName = $requestData['clusterName'] ?? '';
        $clusterAddress = $requestData['clusterAddress'] ?? '';
        $clusterUsername = $requestData['clusterUsername'] ?? '';
        $clusterPassword = $requestData['clusterPassword'] ?? '';
        $physicalMachines = $requestData['physicalMachines'] ?? [];
        
        // 获取当前登录用户
        $createdBy = SecurityUtils::getCurrentUser() ?? 'system';
        
         // 连接数据库
        try {
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
            writeLog("数据库连接成功", $requestId);
        } catch (PDOException $e) {
            writeLog("数据库连接失败: " . $e->getMessage(), $requestId);
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
        
        // 开始事务
        $pdo->beginTransaction();
        
        // 加密集群密码
        $encryptedPassword = SecurityUtils::encrypt($clusterPassword);
        
        // 插入集群基础信息
        $clusterSql = "INSERT INTO cluster (
            cluster_name, 
            cluster_address, 
            cluster_username, 
            cluster_password, 
            cluster_created_by
        ) VALUES (
            :cluster_name, 
            :cluster_address, 
            :cluster_username, 
            :cluster_password, 
            :cluster_created_by
        )";
        
        $clusterStmt = $pdo->prepare($clusterSql);
        $clusterStmt->bindValue(':cluster_name', $clusterName, PDO::PARAM_STR);
        $clusterStmt->bindValue(':cluster_address', $clusterAddress, PDO::PARAM_STR);
        $clusterStmt->bindValue(':cluster_username', $clusterUsername, PDO::PARAM_STR);
        $clusterStmt->bindValue(':cluster_password', $encryptedPassword, PDO::PARAM_STR);
        $clusterStmt->bindValue(':cluster_created_by', $createdBy, PDO::PARAM_STR);
        
        writeLog("执行集群插入SQL: {$clusterSql}", $requestId);
        writeLog("绑定参数: cluster_name={$clusterName}, cluster_address={$clusterAddress}, cluster_username={$clusterUsername}, cluster_created_by={$createdBy}", $requestId);
        
        try {
            $clusterStmt->execute();
            writeLog("集群插入成功", $requestId);
        } catch (PDOException $e) {
            writeLog("集群插入失败: " . $e->getMessage(), $requestId);
            writeLog("错误代码: " . $e->getCode(), $requestId);
            writeLog("SQLSTATE: " . $e->errorInfo[0], $requestId);
            throw new Exception("集群插入失败: " . $e->getMessage());
        }
        
        // 获取插入的集群ID
        $clusterId = $pdo->lastInsertId();
        
        // 插入物理机信息
        if (!empty($physicalMachines)) {
            $pmSql = "INSERT INTO cluster_physical_machine (
                cluster_id, 
                cluster_pm_name, 
                cluster_pm_ip, 
                cluster_pm_username, 
                cluster_pm_password, 
                cluster_pm_bmc_ip, 
                cluster_pm_bmc_username, 
                cluster_pm_bmc_password, 
                cluster_pm_created_by
            ) VALUES (
                :cluster_id, 
                :cluster_pm_name, 
                :cluster_pm_ip, 
                :cluster_pm_username, 
                :cluster_pm_password, 
                :cluster_pm_bmc_ip, 
                :cluster_pm_bmc_username, 
                :cluster_pm_bmc_password, 
                :cluster_pm_created_by
            )";
            
            $pmStmt = $pdo->prepare($pmSql);
            
            writeLog("执行物理机插入SQL: {$pmSql}", $requestId);
            
            foreach ($physicalMachines as $pm) {
                // 加密物理机密码和带外密码
                $pmPassword = SecurityUtils::encrypt($pm['pmPassword'] ?? '');
                $pmBmcPassword = SecurityUtils::encrypt($pm['pmBmcPassword'] ?? '');
                
                $pmName = $pm['pmName'] ?? '';
                $pmIp = $pm['pmIp'] ?? '';
                $pmUsername = $pm['pmUsername'] ?? '';
                $pmBmcIp = $pm['pmBmcIp'] ?? '';
                $pmBmcUsername = $pm['pmBmcUsername'] ?? '';
                
                writeLog("绑定物理机参数: cluster_id={$clusterId}, cluster_pm_name={$pmName}, cluster_pm_ip={$pmIp}, cluster_pm_username={$pmUsername}, cluster_pm_created_by={$createdBy}", $requestId);
                
                $pmStmt->bindValue(':cluster_id', $clusterId, PDO::PARAM_INT);
                $pmStmt->bindValue(':cluster_pm_name', $pmName, PDO::PARAM_STR);
                $pmStmt->bindValue(':cluster_pm_ip', $pmIp, PDO::PARAM_STR);
                $pmStmt->bindValue(':cluster_pm_username', $pmUsername, PDO::PARAM_STR);
                $pmStmt->bindValue(':cluster_pm_password', $pmPassword, PDO::PARAM_STR);
                $pmStmt->bindValue(':cluster_pm_bmc_ip', $pmBmcIp, PDO::PARAM_STR);
                $pmStmt->bindValue(':cluster_pm_bmc_username', $pmBmcUsername, PDO::PARAM_STR);
                $pmStmt->bindValue(':cluster_pm_bmc_password', $pmBmcPassword, PDO::PARAM_STR);
                $pmStmt->bindValue(':cluster_pm_created_by', $createdBy, PDO::PARAM_STR);
                
                try {
                    $pmStmt->execute();
                    writeLog("物理机插入成功: cluster_id={$clusterId}, pm_name={$pmName}, pm_ip={$pmIp}", $requestId);
                } catch (PDOException $e) {
                    writeLog("物理机插入失败: " . $e->getMessage(), $requestId);
                    writeLog("错误代码: " . $e->getCode(), $requestId);
                    writeLog("SQLSTATE: " . $e->errorInfo[0], $requestId);
                    throw new Exception("物理机插入失败: " . $e->getMessage());
                }
            }
        }
        
        // 提交事务
        $pdo->commit();
        writeLog("事务提交成功", $requestId);
        
        // 更新响应
        $response['success'] = true;
        $response['message'] = '保存成功';
        $response['data'] = [
            'clusterId' => $clusterId
        ];
        writeLog("响应数据: " . json_encode($response), $requestId);
        
    } catch (PDOException $e) {
        // 回滚事务
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
            writeLog("事务回滚成功", $requestId);
        }
        $response['message'] = '数据库操作错误: ' . $e->getMessage();
        writeLog('保存宿主机集群失败: ' . $e->getMessage(), $requestId);
        writeLog('错误详情: ' . $e->getTraceAsString(), $requestId);
    } catch (Exception $e) {
        // 回滚事务
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
            writeLog("事务回滚成功", $requestId);
        }
        $response['message'] = '操作错误: ' . $e->getMessage();
        writeLog('保存宿主机集群失败: ' . $e->getMessage(), $requestId);
        writeLog('错误详情: ' . $e->getTraceAsString(), $requestId);
    }
} else {
    $response['message'] = '仅支持POST请求';
    writeLog("不支持的请求方法: {$_SERVER['REQUEST_METHOD']}");
}

// 记录响应
if (isset($requestId)) {
    writeLog("返回响应: " . json_encode($response), $requestId);
} else {
    writeLog("返回响应: " . json_encode($response));
}

// 返回响应
echo json_encode($response, JSON_UNESCAPED_UNICODE);
