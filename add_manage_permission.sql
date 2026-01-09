-- 添加管理权限字段到用户表
ALTER TABLE credstat_user ADD COLUMN credstat_user_perm_manage INT(1) NOT NULL DEFAULT 0 COMMENT '管理权限：0-无，1-有';

-- 验证字段是否添加成功
DESCRIBE credstat_user;