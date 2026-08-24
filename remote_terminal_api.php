<?php
/**
 * remote_terminal_api.php
 * 远程终端软件配置与启动接口
 * 支持：PuTTY / SecureCRT (CRT)
 *
 * 操作：
 *   action=getConfig  读取当前配置
 *   action=saveConfig 保存远程软件类型与路径
 *   action=launch     按当前配置启动终端软件连接目标设备
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 配置文件路径（存于 app/config 目录）
$configDir = __DIR__ . '/app/config';
$configFile = $configDir . '/remote_terminal.json';

/**
 * 默认配置
 */
function getDefaultConfig()
{
    return [
        'software' => 'putty', // putty | crt
        'putty_path' => 'C:\\tools\\putty.exe',
        'crt_path' => 'C:\\Program Files\\VanDyke Software\\SecureCRT\\SecureCRT.exe'
    ];
}

/**
 * 读取配置
 */
function loadTerminalConfig($file)
{
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data)) {
            return array_merge(getDefaultConfig(), $data);
        }
    }
    return getDefaultConfig();
}

/**
 * 保存配置
 */
function saveTerminalConfig($file, $config)
{
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0755, true);
    }
    file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Windows 命令行参数转义（用双引号包裹并转义内部引号）
 */
function winArg($value)
{
    return '"' . str_replace('"', '\\"', $value) . '"';
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}
$action = isset($input['action']) ? $input['action'] : '';

switch ($action) {
    case 'getConfig':
        echo json_encode(['success' => true, 'config' => loadTerminalConfig($configFile)], JSON_UNESCAPED_UNICODE);
        break;

    case 'saveConfig':
        $software = isset($input['software']) ? $input['software'] : 'putty';
        if (!in_array($software, ['putty', 'crt'])) {
            echo json_encode(['success' => false, 'message' => '不支持的远程软件类型'], JSON_UNESCAPED_UNICODE);
            break;
        }
        $config = loadTerminalConfig($configFile);
        $config['software'] = $software;
        if ($software === 'putty') {
            if (isset($input['putty_path']) && trim($input['putty_path']) !== '') {
                $config['putty_path'] = trim($input['putty_path']);
            }
        } else {
            if (isset($input['crt_path']) && trim($input['crt_path']) !== '') {
                $config['crt_path'] = trim($input['crt_path']);
            }
        }
        saveTerminalConfig($configFile, $config);
        echo json_encode(['success' => true, 'config' => $config], JSON_UNESCAPED_UNICODE);
        break;

    case 'launch':
        $config = loadTerminalConfig($configFile);
        $ip = isset($input['ip']) ? trim($input['ip']) : '';
        $port = isset($input['port']) ? trim($input['port']) : '';
        $protocol = isset($input['protocol']) ? strtolower(trim($input['protocol'])) : 'ssh';
        $username = isset($input['username']) ? trim($input['username']) : '';

        if (empty($ip)) {
            echo json_encode(['success' => false, 'message' => '缺少IP地址'], JSON_UNESCAPED_UNICODE);
            break;
        }
        if (!in_array($protocol, ['ssh', 'telnet'])) {
            echo json_encode(['success' => false, 'message' => '不支持的协议'], JSON_UNESCAPED_UNICODE);
            break;
        }

        // 根据配置选择软件并拼接命令行参数
        if ($config['software'] === 'crt') {
            $exePath = $config['crt_path'];
            $softwareName = 'SecureCRT';
            if ($protocol === 'ssh') {
                $args = '/T /SSH2';
                if ($username !== '') {
                    $args .= ' /L ' . winArg($username);
                }
                if ($port !== '') {
                    $args .= ' /P ' . winArg($port);
                }
                $args .= ' ' . winArg($ip);
            } else {
                $args = '/T /TELNET ' . winArg($ip);
                if ($port !== '') {
                    $args .= ' ' . winArg($port);
                }
            }
        } else {
            $exePath = $config['putty_path'];
            $softwareName = 'PuTTY';
            if ($protocol === 'ssh') {
                $args = '-ssh';
                if ($port !== '') {
                    $args .= ' -P ' . winArg($port);
                }
                if ($username !== '') {
                    $args .= ' -l ' . winArg($username);
                }
                $args .= ' ' . winArg($ip);
            } else {
                $args = '-telnet';
                if ($port !== '') {
                    $args .= ' -P ' . winArg($port);
                }
                $args .= ' ' . winArg($ip);
            }
        }

        // 检查软件路径是否存在
        if (!file_exists($exePath)) {
            echo json_encode([
                'success' => false,
                'message' => $softwareName . ' 软件不存在，请在「系统设置 -> 远程终端设置」中配置正确的路径：' . $exePath
            ], JSON_UNESCAPED_UNICODE);
            break;
        }

        // Windows 下通过 start 异步启动，不阻塞 PHP 进程
        $cmd = 'start "" ' . winArg($exePath) . ' ' . $args;
        pclose(popen($cmd, 'r'));

        echo json_encode([
            'success' => true,
            'message' => '已启动 ' . $softwareName . ' 连接 ' . $ip . ($port !== '' ? ':' . $port : ''),
            'command' => $cmd
        ], JSON_UNESCAPED_UNICODE);
        break;

    default:
        echo json_encode(['success' => false, 'message' => '未知操作'], JSON_UNESCAPED_UNICODE);
        break;
}
