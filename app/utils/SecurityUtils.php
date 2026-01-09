<?php
/**
 * 安全工具类
 * 提供密码加密、验证和其他安全相关功能
 */

class SecurityUtils {
    /**
     * 安全配置
     * @var array
     */
    private static $config = null;
    
    /**
     * 加载安全配置
     */
    private static function loadConfig() {
        if (self::$config === null) {
            $configPath = __DIR__ . '/../../app/config/security.php';
            if (file_exists($configPath)) {
                self::$config = include($configPath);
            } else {
                // 默认配置
                self::$config = [
                    'password' => [
                        'algorithm' => PASSWORD_DEFAULT,
                        'cost' => 12,
                    ],
                ];
            }
        }
        return self::$config;
    }
    
    /**
     * 加密密码
     * @param string $password 明文密码
     * @param int|null $algorithm 可选的加密算法，默认为配置中的算法
     * @param array|null $options 可选的加密选项，默认为配置中的选项
     * @return string 加密后的密码哈希
     */
    public static function hashPassword($password, $algorithm = null, $options = null) {
        // 如果提供了算法和选项，则直接使用
        if ($algorithm !== null) {
            return password_hash($password, $algorithm, $options);
        }
        
        // 否则使用配置文件中的设置
        $config = self::loadConfig();
        
        // 尝试不同的配置格式
        if (isset($config['password_hash'])) {
            // 新的switch_security.php格式
            $passwordConfig = $config['password_hash'];
            return password_hash($password, $passwordConfig['algorithm'], $passwordConfig['options']);
        } else if (isset($config['password'])) {
            // 原始的security.php格式
            $passwordConfig = $config['password'];
            
            // 根据算法选择不同的选项
            if ($passwordConfig['algorithm'] === PASSWORD_BCRYPT) {
                $options = ['cost' => $passwordConfig['cost']];
            } elseif ($passwordConfig['algorithm'] === PASSWORD_ARGON2I || 
                    $passwordConfig['algorithm'] === PASSWORD_ARGON2ID) {
                $options = [
                    'memory_cost' => $passwordConfig['memory_cost'],
                    'time_cost' => $passwordConfig['time_cost'],
                    'threads' => $passwordConfig['threads'],
                ];
            } else {
                $options = [];
            }
            
            return password_hash($password, $passwordConfig['algorithm'], $options);
        }
        
        // 默认使用PHP的默认算法
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * 验证密码
     * @param string $password 明文密码
     * @param string $hash 加密后的密码哈希
     * @return bool 是否验证成功
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * 检查密码哈希是否需要重新加密（算法更新或成本变化）
     * @param string $hash 现有的密码哈希
     * @return bool 是否需要重新加密
     */
    public static function needsRehash($hash) {
        $config = self::loadConfig();
        $passwordConfig = $config['password'];
        
        if ($passwordConfig['algorithm'] === PASSWORD_BCRYPT) {
            $options = ['cost' => $passwordConfig['cost']];
        } elseif ($passwordConfig['algorithm'] === PASSWORD_ARGON2I || 
                 $passwordConfig['algorithm'] === PASSWORD_ARGON2ID) {
            $options = [
                'memory_cost' => $passwordConfig['memory_cost'],
                'time_cost' => $passwordConfig['time_cost'],
                'threads' => $passwordConfig['threads'],
            ];
        } else {
            return true;
        }
        
        return password_needs_rehash($hash, $passwordConfig['algorithm'], $options);
    }
    
    /**
     * 可逆加密字符串（用于需要解密的场景，如密码存储）
     * @param string $data 要加密的数据
     * @return string 加密后的数据
     */
    public static function encrypt($data) {
        if (empty($data)) {
            return '';
        }
        
        $config = self::loadConfig();
        
        // 获取加密配置
        $encryptionConfig = $config['encryption'] ?? [];
        
        // 获取加密密钥，必须从配置文件中读取，不允许使用默认密钥
        if (!isset($encryptionConfig['key']) || empty($encryptionConfig['key'])) {
            throw new Exception('Encryption key not found in configuration');
        }
        
        // 获取固定初始化向量，必须从配置文件中读取，不允许使用默认IV
        if (!isset($encryptionConfig['iv']) || empty($encryptionConfig['iv'])) {
            throw new Exception('Encryption IV not found in configuration');
        }
        
        $key = $encryptionConfig['key'];
        $iv = $encryptionConfig['iv'];
        $algorithm = $encryptionConfig['algorithm'] ?? 'aes-256-cbc';
        $keyLength = $encryptionConfig['key_length'] ?? 32;
        $ivLength = $encryptionConfig['iv_length'] ?? 16;
        
        // 确保密钥长度符合要求
        $key = substr(hash('sha256', $key), 0, $keyLength);
        
        // 确保IV长度符合要求
        $iv = substr(hash('md5', $iv), 0, $ivLength);
        
        // 加密数据 - 注意：openssl_encrypt() with $options = 0 already returns base64 encoded string
        $encrypted = openssl_encrypt($data, $algorithm, $key, 0, $iv);
        
        if ($encrypted === false) {
            throw new Exception('Encryption failed: ' . openssl_error_string());
        }
        
        // 直接返回加密数据，不需要包含IV，因为IV是固定的
        return $encrypted;
    }
    
    /**
     * 解密字符串
     * @param string $encryptedData 加密的数据
     * @return string 解密后的数据
     */
    public static function decrypt($encryptedData) {
        if (empty($encryptedData)) {
            return '';
        }
        
        $config = self::loadConfig();
        
        // 获取加密配置
        $encryptionConfig = $config['encryption'] ?? [];
        
        // 获取加密密钥，必须从配置文件中读取，不允许使用默认密钥
        if (!isset($encryptionConfig['key']) || empty($encryptionConfig['key'])) {
            throw new Exception('Encryption key not found in configuration');
        }
        
        // 获取固定初始化向量，必须从配置文件中读取，不允许使用默认IV
        if (!isset($encryptionConfig['iv']) || empty($encryptionConfig['iv'])) {
            throw new Exception('Encryption IV not found in configuration');
        }
        
        $key = $encryptionConfig['key'];
        $iv = $encryptionConfig['iv'];
        $algorithm = $encryptionConfig['algorithm'] ?? 'aes-256-cbc';
        $keyLength = $encryptionConfig['key_length'] ?? 32;
        $ivLength = $encryptionConfig['iv_length'] ?? 16;
        
        // 确保密钥长度符合要求
        $key = substr(hash('sha256', $key), 0, $keyLength);
        
        // 确保IV长度符合要求
        $iv = substr(hash('md5', $iv), 0, $ivLength);
        
        // 直接解密数据 - 注意：openssl_decrypt() with $options = 0 expects base64 encoded string
        $decrypted = openssl_decrypt($encryptedData, $algorithm, $key, 0, $iv);
        
        // 如果解密失败，尝试处理旧的双重base64编码格式
        if ($decrypted === false) {
            // 尝试解码一次base64，模拟旧的双重编码
            $decodedOnce = base64_decode($encryptedData);
            if ($decodedOnce !== false) {
                // 再次尝试解密
                $decrypted = openssl_decrypt($decodedOnce, $algorithm, $key, 0, $iv);
            }
        }
        
        return $decrypted !== false ? $decrypted : '';
    }
    
    /**
     * 生成安全的随机字符串
     * @param int $length 字符串长度
     * @return string 随机字符串
     */
    public static function generateRandomString($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * 清理输入数据，防止XSS攻击
     * @param string $input 输入数据
     * @return string 清理后的数据
     */
    public static function sanitizeInput($input) {
        // 去除首尾空白字符
        $input = trim($input);
        // 转义特殊字符
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }
    
    /**
     * 验证IP地址格式
     * @param string $ip IP地址
     * @return bool 是否有效
     */
    public static function validateIP($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
    
    /**
     * 验证端口号
     * @param int $port 端口号
     * @param int|null $minPort 可选的最小端口号
     * @param int|null $maxPort 可选的最大端口号
     * @return bool 是否有效
     */
    public static function validatePort($port, $minPort = null, $maxPort = null) {
        // 如果提供了端口范围，则直接使用
        if ($minPort !== null && $maxPort !== null) {
            return $port >= $minPort && $port <= $maxPort;
        }
        
        // 否则从配置中读取
        $config = self::loadConfig();
        
        // 尝试不同的配置格式
        if (isset($config['validation']['port_min']) && isset($config['validation']['port_max'])) {
            $portMin = $config['validation']['port_min'] ?? 1;
            $portMax = $config['validation']['port_max'] ?? 65535;
            return $port >= $portMin && $port <= $portMax;
        }
        
        // 默认端口范围
        return $port >= 1 && $port <= 65535;
    }
    
    /**
     * 验证操作系统类型
     * @param string $os 操作系统类型
     * @return bool 是否有效
     */
    public static function validateOS($os) {
        $config = self::loadConfig();
        $allowedOS = $config['validation']['allowed_os'] ?? ['Windows', 'Linux'];
        
        return in_array($os, $allowedOS);
    }
    
    /**
     * 验证字符串长度
     * @param string $string 字符串
     * @param int $maxLength 最大长度
     * @return bool 是否有效
     */
    public static function validateStringLength($string, $maxLength = 100) {
        return strlen($string) <= $maxLength;
    }
    
    /**
     * 设置安全的HTTP响应头
     * @param array $customHeaders 可选的自定义响应头数组，优先级高于配置文件中的设置
     */
    public static function setSecureHeaders(array $customHeaders = null) {
        $config = self::loadConfig();
        
        // 如果提供了自定义响应头，则使用它们
        if (is_array($customHeaders)) {
            foreach ($customHeaders as $header => $value) {
                header("$header: $value");
            }
        } else {
            // 否则使用配置文件中的设置
            if (isset($config['headers']) && is_array($config['headers'])) {
                foreach ($config['headers'] as $header => $value) {
                    header("$header: $value");
                }
            }
        }
    }
    
    /**
     * 获取当前登录用户
     * @return string|null 当前登录用户的用户名或ID，如果未登录则返回null
     */
    public static function getCurrentUser() {
        // 尝试从会话中获取用户信息
        $user = null;
        
        // 检查是否已经有会话
        if (session_status() === PHP_SESSION_ACTIVE) {
            // 会话已激活，直接检查
            if (isset($_SESSION['username'])) {
                $user = $_SESSION['username'];
            } elseif (isset($_SESSION['user_id'])) {
                $user = $_SESSION['user_id'];
            }
        } elseif (session_status() === PHP_SESSION_NONE) {
            // 由于PHP会话目录权限问题，暂时跳过会话功能
            // 改为直接从HTTP头获取用户信息
            // 如果需要会话功能，请确保PHP会话目录有正确的读写权限
            $user = null;
        }
        
        // 如果会话中没有用户信息，尝试从其他地方获取（例如API请求头）
        if ($user === null) {
            // 检查多种可能的HTTP头格式，不同服务器可能转换头的格式
            $userHeaders = [
                // 原始格式（连字符）
                $_SERVER['HTTP_X-USERNAME'] ?? null,
                // PHP-FPM格式（连字符转换为下划线，全部大写）
                $_SERVER['HTTP_X_USERNAME'] ?? null,
                // 其他可能的格式
                $_SERVER['HTTP_USERNAME'] ?? null,
                $_SERVER['REDIRECT_HTTP_X_USERNAME'] ?? null,
                $_SERVER['REDIRECT_HTTP_X_USERNAME'] ?? null,
                $_SERVER['HTTP_AUTH_USER'] ?? null,
                $_SERVER['REMOTE_USER'] ?? null
            ];
            
            // 遍历所有可能的头，找到第一个非空值
            foreach ($userHeaders as $possibleUser) {
                if (!empty($possibleUser)) {
                    $user = $possibleUser;
                    error_log("从HTTP头获取到用户: $user");
                    break;
                }
            }
            
            // 如果仍然没有获取到用户，记录日志
            if ($user === null) {
                error_log("未能从HTTP头获取用户信息，使用默认值");
                // 添加调试信息，记录所有可能的头
                $debugHeaders = [];
                foreach (array_keys($_SERVER) as $key) {
                    if (strpos($key, 'HTTP_') === 0) {
                        $debugHeaders[$key] = $_SERVER[$key];
                    }
                }
                error_log("所有HTTP头: " . json_encode($debugHeaders));
            }
        }
        
        // 如果都没有，返回null
        return $user;
    }
}
