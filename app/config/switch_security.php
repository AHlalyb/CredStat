<?php
/**
 * switch_security.php
 * 网络设备信息安全配置
 */

return [
    // 密码加密设置
    'password_hash' => [
        'algorithm' => PASSWORD_DEFAULT,
        'options' => [
            'cost' => 12
        ]
    ],
    
    // 数据验证规则
    'validation' => [
        'ip_regex' => '/^((25[0-5]|(2[0-4]|1\d|[1-9]|)\d)\.?\b){4}$/',
        'port_min' => 1,
        'port_max' => 65535,
        'allowed_protocols' => ['SSH', 'Telnet', 'HTTP', 'HTTPS', 'SNMP'],
        'max_string_length' => 255,
        'max_remark_length' => 1000
    ],
    
    // 输入过滤设置
    'input_filter' => [
        'strip_tags' => true,
        'trim' => true,
        'htmlspecialchars_flags' => ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5
    ],
    
    // CSRF保护设置
    'csrf' => [
        'enabled' => true,
        'token_name' => 'csrf_token',
        'expiration' => 3600 // 1小时
    ],
    
    // 会话安全设置
    'session' => [
        'use_only_cookies' => true,
        'cookie_httponly' => true,
        'cookie_secure' => false, // 在生产环境中应设置为true
        'cookie_samesite' => 'Lax'
    ],
    
    // 安全响应头
    'headers' => [
        'Content-Security-Policy' => "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:",
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()'
    ]
];