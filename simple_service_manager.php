<?php
// simple_service_manager.php
// 简单的服务管理API，用于启动/停止服务和检查状态

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 禁用所有错误输出
error_reporting(0);
ini_set('display_errors', 0);

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'OPTIONS请求处理成功']);
    exit;
}

// 获取请求参数
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$service = $_GET['service'] ?? $_POST['service'] ?? '';

// 服务配置
$services = [
    'php' => [
        'name' => 'PHP开发服务器',
        'command' => 'php -S localhost:8000',
        'port' => 8000,
        'checkUrl' => 'http://localhost:8000/search_api.php'
    ],
    'mysql' => [
        'name' => 'MySQL数据库',
        'port' => 3306
    ]
];

// 响应函数
function respond($success, $message, $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// 检查端口是否被占用
function checkPort($port) {
    $handle = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
    if ($handle) {
        fclose($handle);
        return true; // 端口已被占用，服务可能运行中
    }
    return false; // 端口未被占用
}

// 检查服务状态
function checkServiceStatus($serviceId) {
    global $services;
    
    if (!isset($services[$serviceId])) {
        return false;
    }
    
    $service = $services[$serviceId];
    return checkPort($service['port']);
}

// 启动服务（仅用于开发环境）
function startService($serviceId) {
    global $services;
    
    if (!isset($services[$serviceId])) {
        return false;
    }
    
    $service = $services[$serviceId];
    
    // 检查服务是否已经运行
    if (checkServiceStatus($serviceId)) {
        return true;
    }
    
    // 仅支持PHP服务启动
    if ($serviceId === 'php') {
        // 在Windows上使用start命令启动服务，在后台运行
        $command = 'start /B ' . $service['command'] . ' > NUL 2>&1';
        exec($command);
        sleep(1); // 等待服务启动
        return checkServiceStatus($serviceId);
    }
    
    return false;
}

// 路由处理
switch ($action) {
    case 'status':
        // 检查服务状态
        if (empty($service)) {
            // 检查所有服务状态
            $status = [];
            foreach (array_keys($services) as $serviceId) {
                $status[$serviceId] = checkServiceStatus($serviceId);
            }
            respond(true, '服务状态检查成功', $status);
        } else {
            // 检查单个服务状态
            $status = checkServiceStatus($service);
            respond(true, $services[$service]['name'] . '状态检查成功', ['status' => $status]);
        }
        break;
        
    case 'start':
        // 启动服务
        if (empty($service)) {
            respond(false, '请指定要启动的服务');
        }
        
        $result = startService($service);
        if ($result) {
            respond(true, $services[$service]['name'] . '启动成功');
        } else {
            respond(false, $services[$service]['name'] . '启动失败，可能已在运行或权限不足');
        }
        break;
        
    case 'stop':
        // 停止服务（简单实现，仅支持PHP服务）
        if ($service === 'php') {
            // 在Windows上查找并终止PHP进程
            // 方法1：根据窗口标题查找（原始方法，作为备选）
            exec('taskkill /F /IM php.exe /FI "WINDOWTITLE eq *localhost:8000*" 2>&1', $output, $return);
            
            if ($return === 0) {
                respond(true, 'PHP开发服务器已停止');
            } else {
                // 方法2：如果方法1失败，尝试使用netstat查找占用8000端口的进程
                exec('netstat -ano | findstr :8000 | findstr LISTENING', $netstatOutput, $netstatReturn);
                
                if ($netstatReturn === 0 && count($netstatOutput) > 0) {
                    // 提取PID
                    $line = trim($netstatOutput[0]);
                    $parts = preg_split('/\s+/', $line);
                    $pid = end($parts);
                    
                    // 使用PID终止进程
                    exec("taskkill /F /PID {$pid} 2>&1", $killOutput, $killReturn);
                    
                    if ($killReturn === 0) {
                        respond(true, 'PHP开发服务器已停止');
                    } else {
                        // 方法3：尝试终止所有PHP进程（最后手段）
                        exec('taskkill /F /IM php.exe 2>&1', $allKillOutput, $allKillReturn);
                        if ($allKillReturn === 0) {
                            respond(true, '所有PHP进程已终止');
                        } else {
                            respond(false, '停止PHP开发服务器失败，可能未运行或需要管理员权限');
                        }
                    }
                } else {
                    respond(false, '停止PHP开发服务器失败，可能未运行');
                }
            }
        } else {
            respond(false, '仅支持停止PHP开发服务器');
        }
        break;
        
    default:
        respond(false, '未知操作，请使用status/start/stop');
        break;
}
