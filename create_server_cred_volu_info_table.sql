-- 创建服务器磁盘信息表
CREATE TABLE `server_cred_volu_info` (
  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '主键ID',
  `server_cred_id` INT NOT NULL COMMENT '服务器ID，外键关联server_cred表',
  `server_cred_volu_os_type` ENUM('windows', 'linux') NOT NULL COMMENT '操作系统类型',
  
  -- Windows特有字段
  `server_cred_volu_windows_drive_letter` VARCHAR(10) DEFAULT NULL COMMENT 'Windows盘符号',
  `windows_disk_type` ENUM('system', 'data', 'application', 'backup') DEFAULT NULL COMMENT 'Windows磁盘类型',
  `windows_file_system` ENUM('ntfs', 'fat32', 'exfat') DEFAULT NULL COMMENT 'Windows文件系统格式',
  
  -- Linux特有字段
  `server_cred_volu_linux_device_name` VARCHAR(50) DEFAULT NULL COMMENT 'Linux设备名称',
  `server_cred_volu_linux_mount_point` VARCHAR(100) DEFAULT NULL COMMENT 'Linux挂载点',
  `linux_mount_options` VARCHAR(200) DEFAULT NULL COMMENT 'Linux挂载选项',
  `linux_disk_purpose` ENUM('system', 'data', 'application', 'log', 'swap') DEFAULT NULL COMMENT 'Linux磁盘用途',
  
  -- 公共字段
  `server_cred_volu_capacity` VARCHAR(20) NOT NULL COMMENT '容量（完整字符串，如100GB、50MB、2TB）',
  `server_cred_volu_used_space` VARCHAR(20) DEFAULT NULL COMMENT '已使用空间（完整字符串，如100GB、50MB、2TB）',
  `server_cred_volu_file_system_type` VARCHAR(50) DEFAULT NULL COMMENT '文件系统类型（通用）',
  `server_cred_volu_notes` TEXT COMMENT '磁盘信息备注（最多500字符）',
  
  -- 元数据字段
  `server_cred_volu_created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `server_cred_volu_updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `server_cred_volu_created_by` VARCHAR(100) NOT NULL COMMENT '创建人',
  `server_cred_volu_updated_by` VARCHAR(100) DEFAULT NULL COMMENT '更新人',
  
  -- 外键约束
  CONSTRAINT `fk_server_cred_volu_info_server` FOREIGN KEY (`server_cred_id`) REFERENCES `server_cred` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务器磁盘信息表';