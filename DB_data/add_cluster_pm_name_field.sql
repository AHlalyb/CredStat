-- 为cluster_physical_machine表添加cluster_pm_name字段
-- 字段说明：
-- 1. VARCHAR(100) - 物理机名称，考虑到实际业务场景，100字符足够使用
-- 2. NOT NULL - 物理机名称为必填项，不能为空
-- 3. DEFAULT '' - 设置空字符串为默认值，确保新增记录时可以正常插入
-- 4. COMMENT - 添加注释说明字段用途
-- 5. AFTER cluster_id - 将字段放置在cluster_id之后，便于逻辑分组
-- 6. 使用utf8mb4字符集和utf8mb4_unicode_ci排序规则，与现有表结构保持一致

ALTER TABLE `cluster_physical_machine`
ADD COLUMN `cluster_pm_name` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '物理机名称' AFTER `cluster_id`;

-- 为cluster_pm_name字段添加索引，提高按名称查询的性能
-- 如果业务中有按物理机名称搜索的需求，此索引将显著提升查询效率
-- 如果不确定是否需要索引，可以先注释掉，在实际使用中根据性能情况决定是否添加

-- CREATE INDEX `idx_cluster_pm_name` ON `cluster_physical_machine` (`cluster_pm_name`);
