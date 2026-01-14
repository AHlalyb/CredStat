-- 数据库表字段重命名脚本
-- 原表名: server_cred_volu_info
-- 执行前请先备份数据

ALTER TABLE `server_cred_volu_info`
    CHANGE COLUMN `os_type` `server_cred_volu_os_type` ENUM('windows', 'linux') NOT NULL COMMENT '操作系统类型',
    CHANGE COLUMN `windows_drive_letter` `server_cred_volu_windows_drive_letter` VARCHAR(10) DEFAULT NULL COMMENT 'Windows盘符号',
    CHANGE COLUMN `linux_device_name` `server_cred_volu_linux_device_name` VARCHAR(50) DEFAULT NULL COMMENT 'Linux设备名称',
    CHANGE COLUMN `linux_mount_point` `server_cred_volu_linux_mount_point` VARCHAR(100) DEFAULT NULL COMMENT 'Linux挂载点',
    CHANGE COLUMN `capacity_gb` `server_cred_volu_capacity` VARCHAR(20) NOT NULL COMMENT '容量（完整字符串，如100GB、50MB、2TB）',
    CHANGE COLUMN `used_space_gb` `server_cred_volu_used_space` VARCHAR(20) DEFAULT NULL COMMENT '已使用空间（完整字符串，如100GB、50MB、2TB）',
    CHANGE COLUMN `file_system_type` `server_cred_volu_file_system_type` VARCHAR(50) DEFAULT NULL COMMENT '文件系统类型（通用）',
    CHANGE COLUMN `notes` `server_cred_volu_notes` TEXT COMMENT '磁盘信息备注（最多500字符）',
    CHANGE COLUMN `created_at` `server_cred_volu_created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    CHANGE COLUMN `updated_at` `server_cred_volu_updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    CHANGE COLUMN `created_by` `server_cred_volu_created_by` VARCHAR(100) NOT NULL COMMENT '创建人',
    CHANGE COLUMN `updated_by` `server_cred_volu_updated_by` VARCHAR(100) DEFAULT NULL COMMENT '更新人';

-- 注意：
-- 1. 执行前请确保数据库连接正常
-- 2. 执行前请备份server_cred_volu_info表的数据
-- 3. 执行后需要更新项目中所有引用这些字段的代码
-- 4. 执行后需要测试所有相关功能，确保数据完整性和系统功能正常
