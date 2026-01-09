<?php
/**
 * 安全配置文件
 * 集中管理系统的安全设置和加密相关配置
 */

return array (
  'password' => 
  array (
    'algorithm' => 1,
    'cost' => 12,
    'memory_cost' => 65536,
    'time_cost' => 4,
    'threads' => 3,
  ),
  'encryption' => 
  array (
    'key' => 'dfde1822f6f36b85fe3aaa7637662f20ec65aafc9954ee0aa78f684df2c3d53b',
    'iv' => '3ab717a953550f172c1e07a12685d4f0',
    'algorithm' => 'aes-256-cbc',
    'key_length' => 32,
    'iv_length' => 16,
  ),
  'validation' => 
  array (
    'ip_pattern' => '/^((25[0-5]|(2[0-4]|1\\d|[1-9]|)\\d)\\.?\\b){4}$/',
    'port_min' => 1,
    'port_max' => 65535,
    'allowed_os' => 
    array (
      0 => 'Windows',
      1 => 'Linux',
    ),
  ),
  'input_filter' => 
  array (
    'max_string_length' => 100,
    'max_text_length' => 65535,
  ),
  'csrf' => 
  array (
    'enabled' => true,
    'token_name' => 'csrf_token',
    'expiration' => 3600,
  ),
  'session' => 
  array (
    'use_secure_cookie' => true,
    'use_httponly' => true,
    'use_samesite' => 'Strict',
  ),
  'headers' => 
  array (
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
  ),
);
