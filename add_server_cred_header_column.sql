ALTER TABLE `server_cred` 
ADD COLUMN `server_cred_header` VARCHAR(100) DEFAULT '' COMMENT '服务器负责人' AFTER `server_cred_notes`;