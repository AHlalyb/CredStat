-- 跳板目标配置表
CREATE TABLE IF NOT EXISTS `jump_target` (
  `jump_target_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `jump_target_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '跳板目标名称',
  `jump_target_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ssh' COMMENT '跳板类型(agent/ssh/telnet)',
  `jump_target_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'IP地址(agent/ssh/telnet)',
  `jump_target_port` int(11) DEFAULT NULL COMMENT '端口(ssh默认22/telnet默认23/agent默认19878)',
  `jump_target_username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '用户名(ssh/telnet类型)',
  `jump_target_password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '密码(ssh/telnet)或共享密钥token(agent)，加密存储',
  `jump_target_remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '备注',
  `jump_target_created_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'system',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`jump_target_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='跳板目标配置表';

-- 设备跳板字段改为指向跳板目标表，历史数据（指向设备ID）清空
UPDATE `net_dev_cred` SET `net_dev_cred_jump_id` = NULL;
ALTER TABLE `net_dev_cred` MODIFY COLUMN `net_dev_cred_jump_id` int(11) DEFAULT NULL COMMENT '跳板目标ID(指向jump_target表, 空=直连)';
