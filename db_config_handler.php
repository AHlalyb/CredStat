<?php
/**
 * 数据库配置处理后端文件
 * 用于处理数据库连接测试、配置保存和登录信息管理
 */

// 加载SecurityUtils类
require_once __DIR__ . '/app/utils/SecurityUtils.php';

// 设置响应头
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 数据库表名
$systemLoginTable = 'login_info';

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取POST数据
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['action'])) {
        echo json_encode(['success' => false, 'message' => '缺少必要参数: action']);
        exit;
    }
    
    // 配置文件路径
    $configFilePath = 'app/config/database.php';
    
    // 根据action处理不同请求
    switch ($data['action']) {
        case 'test_connection':
        testConnection($data);
        break;
            
        case 'saveSettings':
            saveSettings($data, $configFilePath);
            break;
            
        case 'loadSettings':
            loadSettings($configFilePath);
            break;
            
        case 'saveLoginInfo':
            saveLoginInfo($data);
            break;
            
        case 'searchLoginInfo':
            searchLoginInfo($data);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => '未知的操作: ' . $data['action']]);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => '只允许POST请求']);
}

/**
 * 测试数据库连接
 */
function testConnection($data) {
    // 验证必要参数
    if (!isset($data['host']) || !isset($data['port']) || !isset($data['dbname']) || !isset($data['username'])) {
        echo json_encode(['success' => false, 'message' => '缺少必要的数据库连接参数']);
        exit;
    }
    
    // 提取连接参数
    $host = $data['host'];
    $port = $data['port'];
    $dbname = $data['dbname'];
    $username = $data['username'];
    $password = isset($data['password']) ? $data['password'] : '';
    
    try {
        // 创建数据库连接
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        
        // 尝试连接数据库
        $pdo = new PDO($dsn, $username, $password, $options);
        
        // 连接成功，尝试创建表
        global $systemLoginTable;
        $createTableSql = "CREATE TABLE IF NOT EXISTS $systemLoginTable (
            login_info_id INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
            login_info_system_name VARCHAR(255) NOT NULL COMMENT '系统名称',
            login_info_ip_url VARCHAR(500) NOT NULL COMMENT 'IP或URL地址',
            login_info_login_type VARCHAR(100) NOT NULL COMMENT '登录方式(如:http、ssh、数据库等)',
            login_info_username VARCHAR(255) NOT NULL COMMENT '账号',
            login_info_password VARCHAR(500) NOT NULL COMMENT '密码(加密存储)',
            login_info_remark TEXT COMMENT '备注信息',
            login_info_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            login_info_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
            login_info_created_by VARCHAR(100) DEFAULT NULL COMMENT '创建人',
            login_info_is_active TINYINT(1) DEFAULT 1 COMMENT '是否有效(1:有效,0:无效)',
            PRIMARY KEY (login_info_id),
            INDEX idx_system_name (login_info_system_name),
            INDEX idx_ip_url (login_info_ip_url),
            INDEX idx_created_at (login_info_created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统登录信息表';";
        $pdo->exec($createTableSql);
        
        // 连接成功
        echo json_encode([
            'success' => true,
            'message' => "连接成功！已连接到 {$host}:{$port}/{$dbname}，表已创建"
        ]);
    } catch (PDOException $e) {
        // 连接失败
        $errorMessage = '连接失败: ' . $e->getMessage();
        echo json_encode([
            'success' => false,
            'message' => $errorMessage
        ]);
    }
}

/**
 * 保存数据库配置到文件
 */
function saveSettings($data, $configFilePath) {
    // 验证必要参数（支持前端发送的参数名格式）
    if (!isset($data['dbHost']) || !isset($data['dbPort']) || !isset($data['dbName']) || !isset($data['dbUser'])) {
        echo json_encode(['success' => false, 'message' => '缺少必要的数据库配置参数']);
        exit;
    }
    
    // 确保目录存在
    $configDir = dirname($configFilePath);
    if (!is_dir($configDir)) {
        if (!mkdir($configDir, 0755, true)) {
            echo json_encode(['success' => false, 'message' => '无法创建配置目录: ' . $configDir]);
            exit;
        }
    }
    
    // 准备配置内容
    $password = isset($data['dbPassword']) ? $data['dbPassword'] : '';
    $configContent = <<<PHP
<?php
/**
 * 数据库连接配置文件
 * 自动生成时间: " . date('Y-m-d H:i:s') . "
 */

// 数据库配置信息
return [
    'host' => '{$data['dbHost']}',      // 数据库主机地址
    'port' => '{$data['dbPort']}',       // 数据库端口
    'username' => '{$data['dbUser']}',       // 数据库用户名
    'password' => '{$password}',       // 数据库密码
    'dbname' => '{$data['dbName']}',     // 数据库名称
    'charset' => 'utf8mb4'      // 数据库字符集
];
PHP;
    
    // 保存配置文件
    if (file_put_contents($configFilePath, $configContent) !== false) {
        echo json_encode([
            'success' => true,
            'message' => '数据库配置已成功保存！',
            'file_path' => $configFilePath
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '保存配置文件失败，请检查文件权限',
            'file_path' => $configFilePath
        ]);
    }
}

/**
 * 加载已保存的数据库配置
 */
function loadSettings($configFilePath) {
    if (file_exists($configFilePath)) {
        // 尝试加载配置文件
        try {
            $config = include $configFilePath;
            
            if (is_array($config)) {
                echo json_encode([
                    'success' => true,
                    'data' => $config
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => '配置文件格式错误',
                    'data' => []
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => '加载配置文件失败: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => '配置文件不存在',
            'data' => []
        ]);
    }
}

/**
 * 保存系统登录信息
 */
function saveLoginInfo($data) {
    global $systemLoginTable;
    
    // 验证必要参数
    if (!isset($data['systemName']) || !isset($data['ipUrl']) || !isset($data['loginType']) || 
        !isset($data['username']) || !isset($data['password'])) {
        echo json_encode(['success' => false, 'message' => '缺少必要的登录信息参数']);
        exit;
    }
    
    // 加载数据库配置
    $configFilePath = 'app/config/database.php';
    $config = loadConfig($configFilePath);
    
    if (!$config) {
        echo json_encode(['success' => false, 'message' => '未找到数据库配置或配置无效']);
        exit;
    }
    
    try {
        // 创建数据库连接
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        
        $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        
        // 加密密码，使用SecurityUtils类的可逆加密
        $encryptedPassword = SecurityUtils::encrypt($data['password']);
        
        // 准备SQL语句，匹配完整的表结构
        $sql = "INSERT INTO $systemLoginTable (login_info_system_name, login_info_ip_url, login_info_login_type, 
                login_info_username, login_info_password, login_info_remark, login_info_created_at, 
                login_info_updated_at, login_info_created_by, login_info_is_active) 
                VALUES (:systemName, :ipUrl, :loginType, :username, :password, :remark, NOW(), NOW(), :createdBy, :isActive)";
        
        // 准备并执行语句
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':systemName' => $data['systemName'],
            ':ipUrl' => $data['ipUrl'],
            ':loginType' => $data['loginType'],
            ':username' => $data['username'],
            ':password' => $encryptedPassword,
            ':remark' => isset($data['remark']) ? $data['remark'] : '',
            ':createdBy' => isset($data['createdBy']) ? $data['createdBy'] : 'system',
            ':isActive' => 1
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => '系统登录信息保存成功！'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => '保存失败: ' . $e->getMessage()
        ]);
    }
}

/**
 * 搜索系统登录信息
 */
function searchLoginInfo($data) {
    global $systemLoginTable;
    
    // 加载数据库配置
    $configFilePath = 'app/config/database.php';
    $config = loadConfig($configFilePath);
    
    if (!$config) {
        echo json_encode(['success' => false, 'message' => '未找到数据库配置或配置无效']);
        exit;
    }
    
    try {
        // 创建数据库连接
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        
        $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        
        // 获取搜索关键词
        $keyword = isset($data['keyword']) ? $data['keyword'] : '';
        
        // 构建搜索SQL
        $sql = "SELECT login_info_id as id, login_info_system_name as systemName, login_info_ip_url as ipUrl, 
                login_info_login_type as loginType, login_info_username as username, 
                login_info_password as password, login_info_remark as remark 
                FROM $systemLoginTable 
                WHERE login_info_is_active = 1";
        
        $params = [];
        
        if (!empty($keyword)) {
            $sql .= " AND (login_info_system_name LIKE :keyword OR login_info_ip_url LIKE :keyword OR 
                      login_info_login_type LIKE :keyword OR login_info_username LIKE :keyword OR 
                      login_info_remark LIKE :keyword)";
            $params[':keyword'] = '%' . $keyword . '%';
        }
        
        $sql .= " ORDER BY login_info_created_at DESC";
        
        // 执行查询
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        // 注意：使用SecurityUtils类加密的密码是单向哈希，无法解密
        // 此处不再解密密码，直接返回加密后的密码
        
        echo json_encode([
            'success' => true,
            'message' => '查询成功',
            'data' => $results
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => '查询失败: ' . $e->getMessage()
        ]);
    }
}

/**
 * 加载配置文件
 */
function loadConfig($configFilePath) {
    if (file_exists($configFilePath)) {
        try {
            $config = include $configFilePath;
            return is_array($config) ? $config : false;
        } catch (Exception $e) {
            return false;
        }
    }
    return false;
}