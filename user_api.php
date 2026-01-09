<?php
/**
 * 用户管理API
 * 实现用户信息的增删改查操作
 */

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
    'total' => 0
];

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 初始化请求数据和操作类型
    $requestData = [];
    $action = '';
    
    // 检查请求类型
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    
    if (strpos($contentType, 'multipart/form-data') !== false) {
        // 处理文件上传请求，从$_POST中获取action
        $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    } else {
        // 处理JSON请求，从请求体中获取action
        $rawInput = file_get_contents('php://input');
        $requestData = json_decode($rawInput, true);
        $action = isset($requestData['action']) ? trim($requestData['action']) : '';
    }
    
    try {
        // 连接数据库
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        
        // 根据操作类型执行不同的逻辑
        switch ($action) {
            case 'list':
                // 获取用户列表
                $page = isset($requestData['page']) ? intval($requestData['page']) : 1;
                $pageSize = isset($requestData['pageSize']) ? intval($requestData['pageSize']) : 10;
                $response = getUserList($pdo, $page, $pageSize);
                break;
                
            case 'add':
                // 添加用户
                $userData = $requestData['userData'] ?? [];
                $response = addUser($pdo, $userData);
                break;
                
            case 'edit':
                // 编辑用户
                $userData = $requestData['userData'] ?? [];
                $response = editUser($pdo, $userData);
                break;
                
            case 'delete':
                // 删除用户
                $userId = $requestData['userId'] ?? '';
                $response = deleteUser($pdo, $userId);
                break;
                
            case 'login':
                // 用户登录
                $username = $requestData['username'] ?? '';
                $password = $requestData['password'] ?? '';
                $response = loginUser($pdo, $username, $password);
                break;
                
            case 'getCurrentUser':
                // 获取当前登录用户的完整信息
                $username = $requestData['username'] ?? '';
                $response = getCurrentUser($pdo, $username);
                break;
                
            case 'uploadAvatar':
                // 处理文件上传
                $response = uploadAvatar($pdo);
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
 * 获取用户列表
 * @param PDO $pdo PDO连接对象
 * @param int $page 页码
 * @param int $pageSize 每页数量
 * @return array 用户列表
 */
function getUserList($pdo, $page, $pageSize) {
    $page = max(1, $page);
    $pageSize = max(1, min(100, $pageSize));
    $offset = ($page - 1) * $pageSize;
    
    // 查询总数
    $countSql = "SELECT COUNT(*) as total FROM credstat_user";
    $countStmt = $pdo->query($countSql);
    $total = $countStmt->fetchColumn();
    
    // 查询数据
    $dataSql = "SELECT 
                credstat_user_id as id,
                credstat_user_account as account,
                credstat_user_name as name,
                credstat_user_status as credstat_user_status,
                credstat_user_remark as remark,
                credstat_user_created_at as created_at,
                credstat_user_updated_at as updated_at
            FROM credstat_user 
            ORDER BY credstat_user_created_at DESC 
            LIMIT :offset, :pageSize";
    $stmt = $pdo->prepare($dataSql);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll();
    
    return [
        'success' => true,
        'message' => '获取用户列表成功',
        'data' => $data,
        'total' => $total
    ];
}

/**
 * 添加用户
 * @param PDO $pdo PDO连接对象
 * @param array $userData 用户数据
 * @return array 添加结果
 */
function addUser($pdo, $userData) {
    // 验证必填字段
    if (empty($userData['account'])) {
        return [
            'success' => false,
            'message' => '用户账号不能为空'
        ];
    }
    
    if (empty($userData['password'])) {
        return [
            'success' => false,
            'message' => '用户密码不能为空'
        ];
    }
    
    // 检查账号是否已存在
    $checkSql = "SELECT COUNT(*) as count FROM credstat_user WHERE credstat_user_account = :account";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindValue(':account', $userData['account'], PDO::PARAM_STR);
    $checkStmt->execute();
    $count = $checkStmt->fetchColumn();
    
    if ($count > 0) {
        return [
            'success' => false,
            'message' => '用户账号已存在'
        ];
    }
    
    // 加密密码
    $encryptedPassword = SecurityUtils::hashPassword($userData['password']);
    
    // 插入数据
    $insertSql = "INSERT INTO credstat_user (
        credstat_user_account,
        credstat_user_name,
        credstat_user_password,
        credstat_user_status,
        credstat_user_remark
    ) VALUES (
        :account,
        :name,
        :password,
        :status,
        :remark
    )";
    
    $stmt = $pdo->prepare($insertSql);
    $stmt->bindValue(':account', $userData['account'], PDO::PARAM_STR);
    $stmt->bindValue(':name', $userData['name'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':password', $encryptedPassword, PDO::PARAM_STR);
    // 使用整数类型处理状态字段，前端已转换为1或0
    $stmt->bindValue(':status', $userData['credstat_user_status'] ?? 1, PDO::PARAM_INT);
    $stmt->bindValue(':remark', $userData['remark'] ?? '', PDO::PARAM_STR);
    $stmt->execute();
    
    return [
        'success' => true,
        'message' => '添加用户成功'
    ];
}

/**
 * 编辑用户
 * @param PDO $pdo PDO连接对象
 * @param array $userData 用户数据
 * @return array 编辑结果
 */
function editUser($pdo, $userData) {
    // 验证必填字段
    if (!isset($userData['id']) || $userData['id'] === null) {
        return [
            'success' => false,
            'message' => '用户ID不能为空'
        ];
    }
    
    if (empty($userData['account'])) {
        return [
            'success' => false,
            'message' => '用户账号不能为空'
        ];
    }
    
    // 检查账号是否已存在（排除当前用户）
    $checkSql = "SELECT COUNT(*) as count FROM credstat_user WHERE credstat_user_account = :account AND credstat_user_id != :id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindValue(':account', $userData['account'], PDO::PARAM_STR);
    $checkStmt->bindValue(':id', $userData['id'], PDO::PARAM_INT);
    $checkStmt->execute();
    $count = $checkStmt->fetchColumn();
    
    if ($count > 0) {
        return [
            'success' => false,
            'message' => '用户账号已存在'
        ];
    }
    
    // 构建更新SQL，只更新请求中提供的字段
    $updateFields = [];
    $params = [':id' => $userData['id']];
    
    // 只更新请求中提供的字段
    if (isset($userData['account'])) {
        $updateFields[] = "credstat_user_account = :account";
        $params[':account'] = $userData['account'];
    }
    
    if (isset($userData['name'])) {
        $updateFields[] = "credstat_user_name = :name";
        $params[':name'] = $userData['name'];
    }
    
    if (isset($userData['credstat_user_status'])) {
        $updateFields[] = "credstat_user_status = :status";
        $params[':status'] = $userData['credstat_user_status'];
    }
    
    if (isset($userData['remark'])) {
        $updateFields[] = "credstat_user_remark = :remark";
        $params[':remark'] = $userData['remark'];
    }
    
    // 如果提供了密码，则更新密码
    if (!empty($userData['password'])) {
        $updateFields[] = "credstat_user_password = :password";
        $params[':password'] = SecurityUtils::hashPassword($userData['password']);
    }
    
    // 如果没有要更新的字段，直接返回成功
    if (empty($updateFields)) {
        return [
            'success' => true,
            'message' => '编辑用户成功'
        ];
    }
    
    // 构建完整的更新SQL
    $updateSql = "UPDATE credstat_user SET " . implode(", ", $updateFields) . " WHERE credstat_user_id = :id";
    
    $stmt = $pdo->prepare($updateSql);
    $stmt->execute($params);
    
    return [
        'success' => true,
        'message' => '编辑用户成功'
    ];
}

/**
 * 删除用户
 * @param PDO $pdo PDO连接对象
 * @param int $userId 用户ID
 * @return array 删除结果
 */
function deleteUser($pdo, $userId) {
    // 验证必填字段
    if (empty($userId)) {
        return [
            'success' => false,
            'message' => '用户ID不能为空'
        ];
    }
    
    // 执行删除
    $deleteSql = "DELETE FROM credstat_user WHERE credstat_user_id = :id";
    $stmt = $pdo->prepare($deleteSql);
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    return [
        'success' => true,
        'message' => '删除用户成功'
    ];
}

/**
 * 用户登录
 * @param PDO $pdo PDO连接对象
 * @param string $username 用户名
 * @param string $password 密码
 * @return array 登录结果
 */
function loginUser($pdo, $username, $password) {
    // 验证必填字段
    if (empty($username)) {
        return [
            'success' => false,
            'message' => '用户名不能为空'
        ];
    }
    
    if (empty($password)) {
        return [
            'success' => false,
            'message' => '密码不能为空'
        ];
    }
    
    // 查询用户信息
    $sql = "SELECT 
            credstat_user_id as id,
            credstat_user_account as account,
            credstat_user_name as name,
            credstat_user_password as password,
            credstat_user_images_path as avatar
        FROM credstat_user 
        WHERE credstat_user_account = :account";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':account', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch();
    
    // 检查用户是否存在
    if (!$user) {
        return [
            'success' => false,
            'message' => '用户名或密码错误'
        ];
    }
    
    // 验证密码
    if (!SecurityUtils::verifyPassword($password, $user['password'])) {
        return [
            'success' => false,
            'message' => '用户名或密码错误'
        ];
    }
    
    // 登录成功
    return [
        'success' => true,
        'message' => '登录成功',
        'data' => [
            'id' => $user['id'],
            'username' => $user['account'],
            'name' => $user['name'],
            'avatar' => $user['avatar']
        ]
    ];
}

/**
 * 获取当前登录用户的完整信息
 * @param PDO $pdo PDO连接对象
 * @param string $username 用户名
 * @return array 用户信息
 */
function getCurrentUser($pdo, $username) {
    // 验证必填字段
    if (empty($username)) {
        return [
            'success' => false,
            'message' => '用户名不能为空'
        ];
    }
    
    // 查询用户信息
    $sql = "SELECT 
            credstat_user_id as id,
            credstat_user_account as username,
            credstat_user_name as name,
            credstat_user_images_path as avatar
        FROM credstat_user 
        WHERE credstat_user_account = :account";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':account', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch();
    
    // 检查用户是否存在
    if (!$user) {
        return [
            'success' => false,
            'message' => '用户不存在'
        ];
    }
    
    // 返回用户信息
    return [
        'success' => true,
        'message' => '获取用户信息成功',
        'user' => $user
    ];
}

/**
 * 上传用户头像
 * @param PDO $pdo PDO连接对象
 * @return array 上传结果
 */
function uploadAvatar($pdo) {
    try {
        // 检查是否有文件上传
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => '文件上传失败，请检查文件大小和格式'
            ];
        }
        
        // 检查用户名
        $username = $_POST['username'] ?? '';
        if (empty($username)) {
            return [
                'success' => false,
                'message' => '用户名不能为空'
            ];
        }
        
        // 获取上传文件信息
        $file = $_FILES['avatar'];
        $fileTmpPath = $file['tmp_name'];
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $fileType = $file['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        // 验证文件类型
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            return [
                'success' => false,
                'message' => '不支持的文件类型，请上传JPG、JPEG、PNG或GIF格式的图片'
            ];
        }
        
        // 验证文件大小（2MB以内）
        $maxFileSize = 2 * 1024 * 1024;
        if ($fileSize > $maxFileSize) {
            return [
                'success' => false,
                'message' => '文件大小超过限制，请上传不超过2MB的图片'
            ];
        }
        
        // 创建上传目录
        $uploadDir = __DIR__ . '/uploads/user_images/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return [
                    'success' => false,
                    'message' => '创建上传目录失败'
                ];
            }
        }
        
        // 生成唯一文件名
        $newFileName = $username . '_' . time() . '.' . $fileExtension;
        $destPath = $uploadDir . $newFileName;
        
        // 保存文件到服务器
        if (!move_uploaded_file($fileTmpPath, $destPath)) {
            return [
                'success' => false,
                'message' => '保存文件失败，请检查服务器权限'
            ];
        }
        
        // 更新数据库中的头像路径
        $relativePath = 'uploads/user_images/' . $newFileName;
        $sql = "UPDATE credstat_user SET credstat_user_images_path = :avatar_path WHERE credstat_user_account = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':avatar_path', $relativePath, PDO::PARAM_STR);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        // 检查更新结果
        if ($stmt->rowCount() === 0) {
            // 如果没有更新任何行，可能是用户名不存在
            return [
                'success' => false,
                'message' => '更新用户头像失败，用户不存在'
            ];
        }
        
        // 返回成功响应
        return [
            'success' => true,
            'message' => '头像上传成功',
            'avatarUrl' => $relativePath
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => '数据库操作错误: ' . $e->getMessage()
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => '操作错误: ' . $e->getMessage()
        ];
    }
}
