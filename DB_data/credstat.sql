/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50726 (5.7.26)
 Source Host           : localhost:3306
 Source Schema         : credstat

 Target Server Type    : MySQL
 Target Server Version : 50726 (5.7.26)
 File Encoding         : 65001

 Date: 06/01/2026 13:44:31
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for base_obj
-- ----------------------------
DROP TABLE IF EXISTS `base_obj`;
CREATE TABLE `base_obj`  (
  `base_obj_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `base_obj_room` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '机房/站点，JSON格式存储',
  `base_obj_net_device_type` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '网络设备类型，JSON格式存储',
  `base_obj_net_device_brand` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '网络设备品牌，JSON格式存储',
  `base_obj_net_device_model` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '网络设备型号，JSON格式存储',
  `base_obj_server_os` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '服务器操作系统，JSON格式存储',
  `base_obj_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `base_obj_updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`base_obj_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '基础对象设置表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of base_obj
-- ----------------------------
INSERT INTO `base_obj` VALUES (1, '[\"东院区新楼4楼机房\",\"东院区老楼2楼机房\",\"中心机房\",\"儿科楼1楼机房\",\"内科楼13楼机房\",\"外科楼2楼机房\",\"老门诊楼15楼机房\",\"行政楼1楼机房\",\"门诊4楼机房\"]', '[\"AC\",\"交换机\",\"网闸\",\"路由器\",\"防火墙\"]', '[\"H3C\",\"SINO\",\"华为\",\"启明星辰\",\"奇安信\",\"深信服\",\"锐捷\"]', '[\"S5120\"]', '[\"AnolisOS-7.9-GA-x86_64\",\"Centos 7.9\",\"Linux\",\"Windows Server 2012\",\"Windows Server 2016\",\"Windows Server 2019\",\"WindowsServer2008 R2\",\"ubuntu-20.04.6\",\"欧拉(openEuler 22.03 LTS-SP3)\"]', '2025-12-12 20:46:27', '2025-12-15 20:00:40');

-- ----------------------------
-- Table structure for certificate_info
-- ----------------------------
DROP TABLE IF EXISTS `certificate_info`;
CREATE TABLE `certificate_info`  (
  `certificate_info_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '证书ID',
  `certificate_info_sub_domain_id` int(11) NOT NULL COMMENT '关联二级域名ID',
  `certificate_info_expire_date` date NOT NULL COMMENT '证书到期时间',
  `certificate_info_status` enum('valid','expired','expiring','invalid') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'valid' COMMENT '证书状态',
  `certificate_info_issuer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '颁发机构',
  `certificate_info_algorithm` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '加密算法',
  `certificate_info_public_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '公钥内容',
  `certificate_info_private_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '私钥内容',
  `certificate_info_cert_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '证书编号',
  `certificate_info_remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注信息',
  `certificate_info_create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `certificate_info_update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`certificate_info_id`) USING BTREE,
  UNIQUE INDEX `uk_cert_sub_domain`(`certificate_info_sub_domain_id`) USING BTREE,
  INDEX `idx_cert_expire`(`certificate_info_expire_date`) USING BTREE,
  INDEX `idx_cert_status`(`certificate_info_status`) USING BTREE,
  INDEX `idx_cert_issuer`(`certificate_info_issuer`) USING BTREE,
  CONSTRAINT `fk_cert_sub_domain` FOREIGN KEY (`certificate_info_sub_domain_id`) REFERENCES `sub_domain_info` (`sub_domain_info_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '证书信息表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of certificate_info
-- ----------------------------

-- ----------------------------
-- Table structure for cluster
-- ----------------------------
DROP TABLE IF EXISTS `cluster`;
CREATE TABLE `cluster`  (
  `cluster_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '集群ID，主键',
  `cluster_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '集群名称',
  `cluster_address` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '集群地址',
  `cluster_username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '集群用户名',
  `cluster_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '集群密码（加密存储）',
  `cluster_created_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `cluster_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `cluster_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`cluster_id`) USING BTREE,
  INDEX `idx_cluster_name`(`cluster_name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '宿主机集群表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of cluster
-- ----------------------------
INSERT INTO `cluster` VALUES (1, 'DMZ-VM虚拟化集群', 'https://172.27.2.11', 'administrator@vsphere.local', 'RlUBzoOkQdxXRpBTe586Wg==', 'lyb', '2025-12-26 17:33:41', '2025-12-26 17:33:41');

-- ----------------------------
-- Table structure for cluster_physical_machine
-- ----------------------------
DROP TABLE IF EXISTS `cluster_physical_machine`;
CREATE TABLE `cluster_physical_machine`  (
  `cluster_pm_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '物理机ID，主键',
  `cluster_id` int(11) NOT NULL COMMENT '所属集群ID，外键',
  `cluster_pm_ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '物理机IP',
  `cluster_pm_username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '物理机用户名',
  `cluster_pm_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '物理机密码（加密存储）',
  `cluster_pm_bmc_ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '物理机带外地址',
  `cluster_pm_bmc_username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '物理机带外用户名',
  `cluster_pm_bmc_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '物理机带外密码（加密存储）',
  `cluster_pm_created_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `cluster_pm_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `cluster_pm_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`cluster_pm_id`) USING BTREE,
  INDEX `idx_cluster_id`(`cluster_id`) USING BTREE,
  INDEX `idx_cluster_pm_ip`(`cluster_pm_ip`) USING BTREE,
  CONSTRAINT `fk_cluster_pm_cluster_id` FOREIGN KEY (`cluster_id`) REFERENCES `cluster` (`cluster_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '宿主机集群物理机表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of cluster_physical_machine
-- ----------------------------
INSERT INTO `cluster_physical_machine` VALUES (1, 1, '172.27.2.1', 'root', '0EAKjAPfDNuYaOyDqYv16Q==', '172.168.1.31', 'root', 'ZRKwV7jbO0t22u+psxJnvw==', 'lyb', '2025-12-26 17:33:41', '2025-12-26 17:33:41');
INSERT INTO `cluster_physical_machine` VALUES (2, 1, '172.27.2.2', 'root', '0EAKjAPfDNuYaOyDqYv16Q==', '172.168.1.32', 'root', 'ZRKwV7jbO0t22u+psxJnvw==', 'lyb', '2025-12-26 17:33:41', '2025-12-26 17:33:41');
INSERT INTO `cluster_physical_machine` VALUES (3, 1, '172.27.2.3', 'root', '0EAKjAPfDNuYaOyDqYv16Q==', '172.168.1.33', 'root', 'ZRKwV7jbO0t22u+psxJnvw==', 'lyb', '2025-12-26 17:33:41', '2025-12-26 17:33:41');
INSERT INTO `cluster_physical_machine` VALUES (4, 1, '172.27.2.4', 'root', '0EAKjAPfDNuYaOyDqYv16Q==', '172.168.1.34', 'root', 'ZRKwV7jbO0t22u+psxJnvw==', 'lyb', '2025-12-26 17:33:41', '2025-12-26 17:33:41');
INSERT INTO `cluster_physical_machine` VALUES (5, 1, '172.27.2.5', 'root', '0EAKjAPfDNuYaOyDqYv16Q==', '172.168.1.35', 'root', 'ZRKwV7jbO0t22u+psxJnvw==', 'lyb', '2025-12-26 17:33:41', '2025-12-26 17:33:41');
INSERT INTO `cluster_physical_machine` VALUES (6, 1, '172.27.2.6', 'root', '0EAKjAPfDNuYaOyDqYv16Q==', '172.168.1.36', 'root', 'ZRKwV7jbO0t22u+psxJnvw==', 'lyb', '2025-12-26 17:33:41', '2025-12-26 17:33:41');
INSERT INTO `cluster_physical_machine` VALUES (7, 1, '172.27.2.7', 'root', '0EAKjAPfDNuYaOyDqYv16Q==', '172.168.1.37', 'root', 'ZRKwV7jbO0t22u+psxJnvw==', 'lyb', '2025-12-26 17:33:41', '2025-12-26 17:33:41');
INSERT INTO `cluster_physical_machine` VALUES (8, 1, '172.27.2.8', 'root', '0EAKjAPfDNuYaOyDqYv16Q==', '172.168.1.38', 'root', 'ZRKwV7jbO0t22u+psxJnvw==', 'lyb', '2025-12-26 17:33:41', '2025-12-26 17:33:41');

-- ----------------------------
-- Table structure for credstat_user
-- ----------------------------
DROP TABLE IF EXISTS `credstat_user`;
CREATE TABLE `credstat_user`  (
  `credstat_user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID，主键',
  `credstat_user_account` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户账号，唯一',
  `credstat_user_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户名称',
  `credstat_user_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户密码，加密存储',
  `credstat_user_remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '用户备注',
  `credstat_user_status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '账号状态',
  `credstat_user_images_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '用户头像路径',
  `credstat_user_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `credstat_user_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`credstat_user_id`) USING BTREE,
  UNIQUE INDEX `credstat_user_account`(`credstat_user_account`) USING BTREE,
  INDEX `idx_credstat_user_account`(`credstat_user_account`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '系统用户表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of credstat_user
-- ----------------------------
INSERT INTO `credstat_user` VALUES (1, 'admin', '系统管理员', '$2y$12$YjWGPkR1l1UCzc6vmRTDaOh/uXG3eN8h1EzuoHvWYX7ClA6MxRlW.', '系统管理员账号', 1, 'uploads/user_images/admin_1765536191.jpg', '2025-12-05 14:25:58', '2025-12-12 18:43:11');
INSERT INTO `credstat_user` VALUES (2, 'lyb', '吕永波', '$2y$12$z0JSld4NNpTwMYmfRegw4.LCAcI6fqAaBRXNZ5CEEabB4TZDrfM3i', '吕永波的账号', 1, 'uploads/user_images/lyb_1765540696.png', '2025-12-05 18:34:51', '2025-12-12 19:58:16');

-- ----------------------------
-- Table structure for login_info
-- ----------------------------
DROP TABLE IF EXISTS `login_info`;
CREATE TABLE `login_info`  (
  `login_info_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `login_info_system_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '系统名称',
  `login_info_ip_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'IP或URL地址',
  `login_info_login_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '登录方式(如:http、ssh、数据库等)',
  `login_info_username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '账号',
  `login_info_password` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '密码(加密存储)',
  `login_info_remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '备注信息',
  `login_info_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `login_info_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `login_info_created_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '创建人',
  `login_info_is_active` tinyint(1) NULL DEFAULT 1 COMMENT '是否有效(1:有效,0:无效)',
  PRIMARY KEY (`login_info_id`) USING BTREE,
  INDEX `idx_system_name`(`login_info_system_name`) USING BTREE,
  INDEX `idx_ip_url`(`login_info_ip_url`) USING BTREE,
  INDEX `idx_created_at`(`login_info_created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 128 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统登录信息表，存储各类系统的登录凭证信息' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of login_info
-- ----------------------------
INSERT INTO `login_info` VALUES (1, '深信服SCP', 'https://172.168.1.208:4430', 'web', 'admin', 'jYaueIO3k1uRYhsFhUF5mw==', '', '2025-12-26 16:48:32', '2025-12-26 16:48:32', 'lyb', 1);
INSERT INTO `login_info` VALUES (2, 'SQL专家云', 'http://192.168.201.6', 'web', 'zhuancloud@grqsh.com', '0GNelBuxh4BZvril7FhWfA==', '', '2025-12-26 16:48:32', '2025-12-26 16:48:32', 'lyb', 1);
INSERT INTO `login_info` VALUES (3, '深信服EDR', 'https://192.168.201.214', 'web', 'admin', 'WAZjRQvaCtni+WEIUpk6MA==', '', '2025-12-26 16:48:32', '2025-12-26 16:48:32', 'lyb', 1);
INSERT INTO `login_info` VALUES (4, '深信服超融合集群_主HCI', 'https://172.168.1.207', 'web', 'admin', 'Z5f6K720xdR6avF6Ifyi7g==', '', '2025-12-26 16:48:32', '2025-12-26 16:48:32', 'lyb', 1);
INSERT INTO `login_info` VALUES (5, '天融信VPN', 'https://192.168.202.105:8080', 'web', 'superman', 'h5kL9kDtztS9Bsugv61tTg==', '', '2025-12-26 16:48:32', '2025-12-26 16:48:32', 'lyb', 1);
INSERT INTO `login_info` VALUES (6, '输血路由器H3C ER3100', 'http://192.168.115.253', 'web', 'admin', 'tUsGUQGVywUoAYdwb3QWDw==', '', '2025-12-26 16:48:32', '2025-12-26 16:48:32', 'lyb', 1);
INSERT INTO `login_info` VALUES (7, '深信服SIP', 'https://192.168.202.107', 'web', 'admin', 'qDC+IGftfN5kWAxbx3qhng==', '', '2025-12-26 16:48:32', '2025-12-26 16:48:32', 'lyb', 1);
INSERT INTO `login_info` VALUES (8, '深信服探针_核心', 'https://192.168.202.108', 'web', 'admin', '6tPdr9b6m6qBjhbYUmrKoA==', '', '2025-12-26 16:48:32', '2025-12-26 16:48:32', 'lyb', 1);
INSERT INTO `login_info` VALUES (9, '深信服探针_服务器', 'https://192.168.202.109', 'web', 'admin', '6tPdr9b6m6qBjhbYUmrKoA==', '', '2025-12-26 16:48:32', '2025-12-26 16:48:32', 'lyb', 1);
INSERT INTO `login_info` VALUES (10, '医星HIS_NODE1_BMC', 'https://172.168.1.1', 'web', 'admin', '0EAKjAPfDNuYaOyDqYv16Q==', '', '2025-12-26 16:48:32', '2025-12-26 16:48:32', 'lyb', 1);
INSERT INTO `login_info` VALUES (11, 'KVM', 'http://172.168.1.61', 'web', 'admin', 'tUsGUQGVywUoAYdwb3QWDw==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (12, '宏杉分布式存储', 'https://192.168.206.209:8088', 'web', 'admin', '/BdtUj6en91/FTY88b3YHw==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (13, '医星HIS_NODE2_BMC', 'https://172.168.1.2', 'web', 'admin', '0EAKjAPfDNuYaOyDqYv16Q==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (14, 'PACS数据库服务器BMC', 'https://172.168.1.3', 'web', 'admin', '0EAKjAPfDNuYaOyDqYv16Q==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (15, 'PACS存储服务器BMC', 'https://172.168.1.4', 'web', 'admin', '0EAKjAPfDNuYaOyDqYv16Q==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (16, '科林布瑞RDBMS（写）', 'https://172.168.1.5', 'web', 'admin', '0EAKjAPfDNuYaOyDqYv16Q==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (17, '科林布瑞RDBMS（读）', 'https://172.168.1.6', 'web', 'admin', '0EAKjAPfDNuYaOyDqYv16Q==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (18, '深信服超融合集群_主节点1BMC', 'https://172.168.1.7', 'web', 'admin', 'd5yJ2/HP+mrEhMHy2uaJDQ==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (19, '深信服超融合集群_主节点2BMC', 'https://172.168.1.8', 'web', 'admin', 'd5yJ2/HP+mrEhMHy2uaJDQ==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (20, '深信服超融合集群_主节点3BMC', 'https://172.168.1.9', 'web', 'admin', 'd5yJ2/HP+mrEhMHy2uaJDQ==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (21, '深信服超融合集群_主节点4BMC', 'https://172.168.1.10', 'web', 'admin', 'd5yJ2/HP+mrEhMHy2uaJDQ==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (22, '深信服超融合集群_主节点5BMC', 'https://172.168.1.11', 'web', 'admin', 'd5yJ2/HP+mrEhMHy2uaJDQ==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (23, '深信服超融合集群_主节点6BMC', 'https://172.168.1.12', 'web', 'admin', 'd5yJ2/HP+mrEhMHy2uaJDQ==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (24, '深信服超融合集群_备节点1BMC', 'https://172.168.1.13', 'web', 'admin', 'd5yJ2/HP+mrEhMHy2uaJDQ==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (25, '深信服超融合集群_备节点2BMC', 'https://172.168.1.14', 'web', 'admin', 'd5yJ2/HP+mrEhMHy2uaJDQ==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (26, '深信服超融合集群_备节点3BMC', 'https://172.168.1.15', 'web', 'admin', 'd5yJ2/HP+mrEhMHy2uaJDQ==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (27, '医星HIS_SAN交换机1', '172.168.1.20', 'telnet', 'admin', 'sXqxo7j0t9eyxdRt8SxGjA==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (28, '医星HIS_SAN交换机2', '172.168.1.21', 'telnet', 'admin', 'sXqxo7j0t9eyxdRt8SxGjA==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (29, '分布式存储节点1BMC', 'https://172.168.1.22', 'web', 'admin', 'ScosTu8/1Lz22uxeY6hgUg==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (30, '分布式存储节点2BMC', 'https://172.168.1.23', 'web', 'admin', 'ScosTu8/1Lz22uxeY6hgUg==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (31, '分布式存储节点3BMC', 'https://172.168.1.24', 'web', 'admin', 'ScosTu8/1Lz22uxeY6hgUg==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (32, '分布式存储节点4BMC', 'https://172.168.1.25', 'web', 'admin', 'ScosTu8/1Lz22uxeY6hgUg==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (33, '分布式存储节点5BMC', 'https://172.168.1.26', 'web', 'admin', 'ScosTu8/1Lz22uxeY6hgUg==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (34, '科力锐数据库备份与恢复系统', 'http://192.168.205.25', 'web', 'admin', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (35, '科力锐数据库备份与恢复系统', 'http://192.168.205.25', 'web', 'larmyy', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (36, '内科楼H3C无线AC', 'http://10.0.0.62', 'web', 'admin', 'tUsGUQGVywUoAYdwb3QWDw==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (37, '内科楼锐捷无线AC', 'https://192.168.202.102', 'web', 'superman', 'lZbr1HCB92r3GLfAV7t0xg==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (38, '宏杉SAN存储MS3000G2_NODE1_控制器A', 'https://172.168.1.16:8443', 'web', 'admin', 'ZBmVGY3X1I1lI3mAHa1qgw==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (39, '宏杉SAN存储MS3000G2_NODE1_控制器B', 'https://172.168.1.17:8443', 'web', 'admin', 'ZBmVGY3X1I1lI3mAHa1qgw==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (40, '宏杉SAN存储MS3000G2_NODE2_控制器A', 'https://172.168.1.18:8443', 'web', 'admin', 'ZBmVGY3X1I1lI3mAHa1qgw==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (41, '宏杉SAN存储MS3000G2_NODE2_控制器B', 'https://172.168.1.19:8443', 'web', 'admin', 'ZBmVGY3X1I1lI3mAHa1qgw==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (42, '深信服超融合集群_备节点1', 'https://172.168.1.210', 'web', 'admin', 'OZCutkyMHGK98LlGUa+RZg==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (43, '深信服超融合集群_备节点2', 'https://172.168.1.211', 'web', 'admin', 'OZCutkyMHGK98LlGUa+RZg==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (44, '深信服超融合集群_备节点3', 'https://172.168.1.212', 'web', 'admin', 'OZCutkyMHGK98LlGUa+RZg==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (45, '深信服超融合集群_备节点4', 'https://172.168.1.214', 'web', 'admin', 'OZCutkyMHGK98LlGUa+RZg==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (46, '深信服超融合集群_备节点5', 'https://172.168.1.215', 'web', 'admin', 'OZCutkyMHGK98LlGUa+RZg==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (47, '深信服超融合集群_备HCI', 'https://172.168.1.213', 'web', 'admin', 'OZCutkyMHGK98LlGUa+RZg==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (48, '物流仓库H3C ER2200G2路由器', 'http://192.168.148.110:8080', 'web', 'admin', 'tUsGUQGVywUoAYdwb3QWDw==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (49, '深信服监控中心', 'https://172.168.1.101', 'web', 'SecAdmin', '0wbvzzpl7+r/cFt5zubytA==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (50, '深信服监控中心', 'https://172.168.1.101', 'web', 'SysAdmin', '0wbvzzpl7+r/cFt5zubytA==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (51, 'DELLEMC存储-DMZ', 'https://192.168.201.99:3033', 'web', 'admin', 'yFx+ADVSQ8vi4NYRSE7INQ==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (52, '科力锐数据库备份与恢复系统', 'http://192.168.205.25', 'web', 'audadmin', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (53, '科力锐数据库备份与恢复系统', 'http://192.168.205.25', 'web', 'readmin', 'X9ZlfMX33dS/qsI+ClEtmA==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (54, '横渡数据库防护系统', 'http://172.168.1.103', 'web', 'admin', 'MCGfhEgZ3T5mznK26aGqQw==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (55, 'DMZ_DELL服务器BMC4', 'https://172.168.1.34', 'web', 'root', 'ZRKwV7jbO0t22u+psxJnvw==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (56, 'DMZ_DELL服务器BMC1', 'https://172.168.1.31', 'web', 'root', 'ZRKwV7jbO0t22u+psxJnvw==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (57, 'DMZ_DELL服务器BMC8', 'https://172.168.1.38', 'web', 'root', 'ZRKwV7jbO0t22u+psxJnvw==', '', '2025-12-26 16:48:33', '2025-12-26 16:48:33', 'lyb', 1);
INSERT INTO `login_info` VALUES (58, 'DMZ_DELL服务器BMC7', 'https://172.168.1.37', 'web', 'root', 'ZRKwV7jbO0t22u+psxJnvw==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (59, 'DMZ_DELL服务器BMC6', 'https://172.168.1.36', 'web', 'root', 'ZRKwV7jbO0t22u+psxJnvw==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (60, 'DMZ_DELL服务器BMC5', 'https://172.168.1.35', 'web', 'root', 'ZRKwV7jbO0t22u+psxJnvw==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (61, 'DMZ_DELL服务器BMC3', 'https://172.168.1.33', 'web', 'root', 'ZRKwV7jbO0t22u+psxJnvw==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (62, 'DMZ_DELL服务器BMC2', 'https://172.168.1.32', 'web', 'root', 'ZRKwV7jbO0t22u+psxJnvw==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (63, '医星HIS_ROSEHA', 'http://192.168.205.5:9999', 'web', 'webadmin', 'lngLcdFa20yEBS1kIkNreQ==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (64, '奇安信专网防火墙1', 'https://172.168.1.110', 'web', 'admin', 'fMNw1sc/MFtBUzJDULxnmw==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (65, '奇安信专网防火墙2', 'https://172.168.1.111', 'web', 'admin', 'fMNw1sc/MFtBUzJDULxnmw==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (66, '横渡数据库防护系统', 'http://172.168.1.103', 'web', 'operation', 'x7NCVNxlu8KKEhK/4AgCdA==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (67, '鼎甲迪备异地备份系统', 'http://192.168.205.109', 'web', 'admin', 't5wnbwTxH2SbZHvo1yl46Q==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (68, '鼎甲迪备异地备份系统', 'http://192.168.205.109', 'web', 'scutech', 'kZEXjeThOv/jKOA3SY9Vrw==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (69, '医星HIS数据库脱敏系统', 'http://192.168.201.42', 'web', 'admin', 'y1SmHBnIHcAcUf3dZDzEug==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (70, '奇安信外网防火墙1', 'https://172.168.1.112', 'web', 'admin', '2m1dasWjbAtyWJ1PCxpOxQ==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (71, '奇安信外网防火墙2', 'https://172.168.1.113', 'web', 'admin', '2m1dasWjbAtyWJ1PCxpOxQ==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (72, '奇安信外网防火墙1', 'https://172.168.1.112', 'web', 'audit', 'C/ADaO9RZ+85PfA8xI7pBA==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (73, '鼎甲迪备异地备份系统', 'http://192.168.205.109', 'web', 'JJJC', 'CyCDhN6p3CpnC7bT80Dp6w==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (74, '深信服网站防火墙旁路', 'https://4.4.4.6', 'web', 'admin', 'NtsXHiTJkk4b/OMO2pCPGQ==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (75, '数据平台GP集群1BMC-对应系统IP192.168.204.26', 'https://172.168.1.52', 'web', 'admin', 'fLKszXZRJXKjEyRYjPRd7w==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (76, '数据平台GP集群2BMC-对应系统IP192.168.204.27', 'https://172.168.1.53', 'web', 'admin', 'fLKszXZRJXKjEyRYjPRd7w==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (77, '数据平台GP集群3BMC-对应系统IP192.168.204.28', 'https://172.168.1.54', 'web', 'admin', 'fLKszXZRJXKjEyRYjPRd7w==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (78, '数据平台GP集群4BMC-对应系统IP192.168.204.29', 'https://172.168.1.55', 'web', 'admin', 'fLKszXZRJXKjEyRYjPRd7w==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (79, '数据平台GP集群5BMC-对应系统IP192.168.204.30', 'https://172.168.1.56', 'web', 'admin', 'fLKszXZRJXKjEyRYjPRd7w==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (80, '数据平台GP集群6BMC-对应系统IP192.168.204.31', 'https://172.168.1.57', 'web', 'admin', 'fLKszXZRJXKjEyRYjPRd7w==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (81, 'APP5BMC-对应系统IP192.168.204.32', 'https://172.168.1.58', 'web', 'admin', 'fLKszXZRJXKjEyRYjPRd7w==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (82, '集成平台数据库-对应系统IP192.168.204.33', 'https://172.168.1.59', 'web', 'admin', 'fLKszXZRJXKjEyRYjPRd7w==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (83, '西区服务器BMC', 'https://192.168.60.110', 'web', 'USERID', 'mmOkvnNV+JJE4xZVdEW80g==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (84, 'DELLEMC存储-pacs', 'https://172.168.1.45', 'web', 'Admin', 'CTfeiwuIZvxkUPX08X8K/A==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (85, '健康管理中心深信服内网防火墙', 'https://192.168.183.247', 'web', 'admin', 'LzDasDkmDMFtTiRJqw2mDw==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (86, '横渡数据库防护系统', 'http://172.168.1.103', 'web', 'auditor', 'aInqyZQ7VcRhV6oqZ4V3dQ==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (87, '互联网医院-电信云防火墙', 'https://172.18.1.172:4433', 'web', 'hillstone', 'UR5JgaPyf/sezTfgfiJkYA==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (88, '超融合集群-数据平台HCI', 'https://172.168.1.180', 'web', 'admin', 'e8OGmTgEfgLzh/yufBbWAA==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (89, '超融合集群-主HCI', 'https://172.168.1.207', 'web', '172.168.1.180', 'e8OGmTgEfgLzh/yufBbWAA==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (90, '恒安exsi', 'https://192.168.206.65', 'web', 'root', '+ZEBwSWArQiYYMfllYGPpA==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (91, '恒安exsi', 'https://192.168.206.69', 'web', 'root', '+ZEBwSWArQiYYMfllYGPpA==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (92, '锐捷乐享平台', 'http://192.168.206.250:8082', 'web', 'sysadmin', 'oVE4D7+dAV/8V6QExxACmA==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (93, '锐捷乐享平台', 'http://192.168.206.250', 'web', 'admin', 'oVE4D7+dAV/8V6QExxACmA==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (94, '锐捷乐享平台', 'http://192.168.206.250', 'web', 'zqh', 'vWPxVhuancgLQGZZXS/M2w==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (95, '健康管理中心锐捷内网AC', 'https://10.100.183.253', 'web', 'admin', 'mvlwEW0DiDeZCa8Xj9BVaw==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (96, '静配中心内网AC', 'http://192.168.132.2', 'web', 'admin', 'JnLks1tEY9gdUZ7tNV9wog==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (97, '奇安信外网防火墙2', 'https://10.0.0.206', 'web', 'admin', 'fMNw1sc/MFtBUzJDULxnmw==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (98, '奇安信外网防火墙1', 'https://10.0.0.202', 'web', 'admin', 'fMNw1sc/MFtBUzJDULxnmw==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (99, '公共卫生中心深信服内网防火墙', 'https://10.192.1.251', 'web', 'admin', 'T588S5oyeOzQU3sRnoLR1w==', '', '2025-12-26 16:48:34', '2025-12-26 16:48:34', 'lyb', 1);
INSERT INTO `login_info` VALUES (100, '公共卫生中心锐捷内网ess', 'http://10.192.1.253:8080', 'web', 'admin', '8IYPn4ZrZ59jYAaExXoPKMGM/lWJa6kbhwqIEgKYa14=', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (101, '公共卫生中心锐捷内网AC', 'https://10.100.171.253', 'web', 'admin', 'udtYATdS7aKUQ+4mT7UB9g==', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (102, '超融合区域深信服防火墙1', 'https://192.168.202.95', 'web', 'admin', '3K9yeLzesvmiACdeT9axYzc8W7+fFir0U9xKIyVnkqQ=', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (103, '超融合区域深信服防火墙2', 'https://192.168.202.96', 'web', 'admin', '3K9yeLzesvmiACdeT9axYzc8W7+fFir0U9xKIyVnkqQ=', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (104, 'HIS区域深信服防火墙1', 'https://192.168.202.97', 'web', 'admin', '3K9yeLzesvmiACdeT9axYzc8W7+fFir0U9xKIyVnkqQ=', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (105, 'HIS区域深信服防火墙2', 'https://192.168.202.98', 'web', 'admin', '3K9yeLzesvmiACdeT9axYzc8W7+fFir0U9xKIyVnkqQ=', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (106, '深信服超融合BMC', 'https://172.168.1.94', 'web', 'sangfor', 'hgn0xUgQNRDRUJF0isIxUQ==', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (107, '医星HIS_ROSEHA', 'http://192.168.205.3:9999', 'web', 'webadmin', 'lngLcdFa20yEBS1kIkNreQ==', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (108, '阳途准入', 'http://192.168.202.111', 'web', 'admin', 'wFfdmKJzWqqYg143eG5PKA==', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (109, '西区服务器BMC', 'http://192.168.88.253', 'web', 'admin', '82pO8nLD+vy2uPxbPSJ4wA==', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (110, '深信服网站防火墙旁路带外', 'https://172.168.1.118', 'web', 'admin', 'NtsXHiTJkk4b/OMO2pCPGQ==', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (111, '机房建设TrueNas', 'http://172.168.1.168', 'web', 'truenas_admin', '4fgvyf5bXEQcnwYdSU8L/A==', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (112, '卫宁SQL NODE1_BMC', 'https://172.168.1.67', 'web', 'admin', 'fLKszXZRJXKjEyRYjPRd7w==', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (113, '卫宁SQL NODE2_BMC', 'https://172.168.1.68', 'web', 'admin', 'fLKszXZRJXKjEyRYjPRd7w==', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (114, '卫宁RAC NODE1_BMC', 'https://172.168.1.66', 'web', 'admin', 'fLKszXZRJXKjEyRYjPRd7w==', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (115, '卫宁RAC NODE2_BMC', 'https://172.168.1.69', 'web', 'admin', 'fLKszXZRJXKjEyRYjPRd7w==', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (116, '输血直报VPN（小机房）', 'https://10.87.152.1:4430', 'web', 'hfzc', 'G9wGBmu5rUt2RK/Me6ZCVg==', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (117, '深信服超融合集群_卫宁HIS', 'https://172.29.1.31', 'web', 'admin', 'Y9izDgbTuOrCUmBB2nHrUA==', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (118, 'zabbix', 'http://192.168.206.240', 'web', 'Admin', 'CWKQZ/xRTrbfXeSznVND8A==', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (119, 'zabbix', 'http://192.168.206.240:8080', 'web', 'Admin', 'CWKQZ/xRTrbfXeSznVND8A==', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (120, 'Grafana', 'http://192.168.206.241:3000', 'web', 'admin', '4fgvyf5bXEQcnwYdSU8L/A==', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (121, '内科楼锐捷无线AC', 'https://192.168.202.102', 'web', 'admin', 'lZbr1HCB92r3GLfAV7t0xg==', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (122, '天融信VPN-借用', 'https://192.168.202.106:8080', 'web', 'superman', 'G0vCy9ilUE1PgX9qfuso/w==', '', '2025-12-26 16:48:35', '2025-12-26 16:48:35', 'lyb', 1);
INSERT INTO `login_info` VALUES (123, '物流科路由器', 'http://192.168.148.110:8080', 'web', 'admin', 'tUsGUQGVywUoAYdwb3QWDw==', '', '2025-12-26 16:51:08', '2025-12-26 16:51:08', 'lyb', 1);
INSERT INTO `login_info` VALUES (124, 'DMZ虚拟化集群', 'https://172.27.2.11', 'web', 'administrator@vsphere.local', 'RlUBzoOkQdxXRpBTe586Wg==', '', '2025-12-26 16:56:43', '2025-12-26 16:56:43', 'lyb', 1);
INSERT INTO `login_info` VALUES (125, '阿里云-域名证书', 'https://account.aliyun.com/login/login.htm', 'web', 'hi20084269@aliyun.com', 'h6RVNvx5uaH2iDiWiBY73A==', '', '2025-12-26 16:59:58', '2025-12-26 16:59:58', 'lyb', 1);
INSERT INTO `login_info` VALUES (126, '网站云防护', 'https://ah.anyu.qianxin.com/#/login', 'web', 'xiechang', 'aJ6krzeTfCGMhFrL3RUzLQ==', '', '2025-12-26 17:00:16', '2025-12-26 17:00:16', 'lyb', 1);
INSERT INTO `login_info` VALUES (127, '体检中心外网路由器', 'https://183.162.92.66:4430/index.htm', 'web', 'admin', 'mvlwEW0DiDeZCa8Xj9BVaw==', '', '2025-12-26 17:11:45', '2025-12-26 17:11:45', 'lyb', 1);

-- ----------------------------
-- Table structure for main_domain_info
-- ----------------------------
DROP TABLE IF EXISTS `main_domain_info`;
CREATE TABLE `main_domain_info`  (
  `main_domain_info_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主域名ID',
  `main_domain_info_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '主域名名称',
  `main_domain_info_regist_date` date NOT NULL COMMENT '注册时间',
  `main_domain_info_expire_date` date NOT NULL COMMENT '到期时间',
  `main_domain_info_create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `main_domain_info_update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`main_domain_info_id`) USING BTREE,
  UNIQUE INDEX `main_domain_info_name`(`main_domain_info_name`) USING BTREE,
  INDEX `idx_main_domain_expire`(`main_domain_info_expire_date`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '主域名信息表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of main_domain_info
-- ----------------------------
INSERT INTO `main_domain_info` VALUES (4, 'lasrmyy.cn', '2024-09-03', '2029-09-03', '2025-12-15 11:26:40', '2025-12-15 11:26:40');
INSERT INTO `main_domain_info` VALUES (5, 'layyst.com', '2019-11-12', '2031-11-12', '2025-12-15 11:27:33', '2025-12-15 11:27:33');
INSERT INTO `main_domain_info` VALUES (6, 'lasrmmy.com', '2019-02-28', '2031-02-28', '2025-12-15 11:28:15', '2025-12-15 11:28:15');
INSERT INTO `main_domain_info` VALUES (7, 'layy.cn', '2004-06-05', '2031-06-09', '2025-12-15 11:28:58', '2025-12-15 11:28:58');

-- ----------------------------
-- Table structure for net_dev_cred
-- ----------------------------
DROP TABLE IF EXISTS `net_dev_cred`;
CREATE TABLE `net_dev_cred`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `net_dev_cred_dev_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '交换机' COMMENT '网络设备类型(交换机/路由器/防火墙等)',
  `net_dev_cred_net_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '内网' COMMENT '网络设备所属网络(内网/外网)',
  `net_dev_cred_physical_area` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '网络设备所属物理区域',
  `net_dev_cred_building_floor` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '网络设备所属楼宇-楼层',
  `net_dev_cred_floor_location` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '网络设备所在楼层位置',
  `net_dev_cred_chinese_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '中文命名',
  `net_dev_cred_system_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '系统命名',
  `net_dev_cred_dev_brand` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '设备品牌',
  `net_dev_cred_dev_sign` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '设备型号',
  `net_dev_cred_management_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '管理IP',
  `net_dev_cred_protocol` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '管理协议',
  `net_dev_cred_port` int(11) NOT NULL COMMENT '端口',
  `net_dev_cred_username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '用户名',
  `net_dev_cred_password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '密码哈希',
  `net_dev_cred_enable_password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '特权密码哈希',
  `net_dev_cred_snmp` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'SNMP团体字',
  `net_dev_cred_probe_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '拨测状态(success/fail)',
  `net_dev_cred_probe_message` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '最近拨测结果',
  `net_dev_cred_probe_time` datetime NULL DEFAULT NULL COMMENT '最近拨测时间',
  `net_dev_cred_snmp_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'SNMP测试状态(success/fail)',
  `net_dev_cred_snmp_message` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '最近SNMP测试结果',
  `net_dev_cred_snmp_time` datetime NULL DEFAULT NULL COMMENT '最近SNMP测试时间',
  `net_dev_cred_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '备注',
  `net_dev_cred_created_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'system',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_switch_management_ip_protocol_port`(`net_dev_cred_management_ip`, `net_dev_cred_protocol`, `net_dev_cred_port`) USING BTREE,
  INDEX `idx_switch_chinese_name`(`net_dev_cred_chinese_name`) USING BTREE,
  INDEX `idx_switch_system_name`(`net_dev_cred_system_name`) USING BTREE,
  INDEX `idx_switch_brand`(`net_dev_cred_dev_brand`) USING BTREE,
  INDEX `idx_switch_dev_sign`(`net_dev_cred_dev_sign`) USING BTREE,
  INDEX `idx_switch_physical_area`(`net_dev_cred_physical_area`) USING BTREE,
  INDEX `idx_switch_building_floor`(`net_dev_cred_building_floor`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE,
  INDEX `idx_switch_dev_type`(`net_dev_cred_dev_type`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 117 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '交换机登录信息表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of net_dev_cred
-- ----------------------------
INSERT INTO `net_dev_cred` VALUES (1, '入侵防御系统', '内网', '机房A-2层', '机房A-2层', '办公区1楼', '入侵防御系统1', 'Juniper SRX Series', 'Palo Alto', 'DEV-50357352', '134.33.248.171', 'http', 80, 'admin', 'TFdoTktVOVB6SzEva01aUkdmN2NFQT09', 'Z0h4d1dwM1dHU1MyZTQ1NVdHc3NhQT09', NULL, '设备位置: 数据中心1区', 'system', '2024-01-07 22:10:55', '2024-01-07 22:10:55');
INSERT INTO `net_dev_cred` VALUES (2, 'VPN设备', '内网', '机房A-2层', '办公区1楼', '机房A-1层', 'VPN设备2', 'Cisco ASA 5500', 'HUAWEI', 'DEV-CDE4BE0C', '57.115.101.153', 'telnet', 23, 'admin', 'NUJZUlVmU2U1YU1abzJJWTZIU2V5QT09', 'dTg5MU1NWjQxbDcrOW5EcUtXM3hjQT09', NULL, '设备位置: 机房B-2层', 'system', '2024-06-27 21:58:00', '2024-06-27 21:58:00');
INSERT INTO `net_dev_cred` VALUES (3, '路由器', '内网', '数据中心2区', '机房A-1层', '机房B-1层', '路由器3', 'Palo Alto PA-220', 'HUAWEI', 'DEV-C124D6D1', '6.145.63.225', 'ssh', 22, 'admin', 'dWlNendndmk5SDBEQml1cStJa29BQT09', 'dzRPV3VYdXlzYldUbzAyRWNIaHUrdz09', NULL, '设备位置: 数据中心4区', 'system', '2024-03-20 08:09:36', '2024-03-20 08:09:36');
INSERT INTO `net_dev_cred` VALUES (4, '负载均衡器', '内网', '数据中心3区', '机房A-1层', '机房B-1层', '负载均衡器4', 'Cisco ASA 5500', 'Palo Alto', 'DEV-086A410A', '75.26.11.191', 'https', 443, 'admin', 'QzFnaUpNb3Z2UnA2RlkyZ2w4S0lUUT09', 'eS9uSm5peXZhRVZNZnNGZjlxYXlLUT09', NULL, '设备位置: 数据中心2区', 'system', '2024-10-05 14:23:56', '2024-10-05 14:23:56');
INSERT INTO `net_dev_cred` VALUES (5, '路由器', '内网', '办公区1楼', '机房B-2层', '机房B-2层', '路由器5', 'Fortinet FortiGate', 'Aruba', 'DEV-D5502501', '104.164.63.154', 'http', 80, 'admin', 'aVIxeWkrcmFKTFNNaEtkV1NKTTI5Zz09', 'QVorbU5yZDJDUjR0b3hNZnVRT2lxUT09', NULL, '设备位置: 办公区3楼', 'system', '2024-12-09 06:56:00', '2024-12-09 06:56:00');
INSERT INTO `net_dev_cred` VALUES (6, 'VPN设备', '内网', '办公区3楼', '数据中心4区', '机房B-1层', 'VPN设备6', 'Cisco ASA 5500', 'Palo Alto', 'DEV-4DF51CB3', '222.36.97.60', 'https', 443, 'admin', 'UnVCWUM4NEJtcm5zNmE3UHQvWTR6QT09', 'bjBESldrMi9WZ2hNV3ZyYkpGQkYxdz09', NULL, '设备位置: 机房A-2层', 'system', '2024-09-19 02:22:16', '2024-09-19 02:22:16');
INSERT INTO `net_dev_cred` VALUES (7, '路由器', '内网', '数据中心1区', '数据中心3区', '办公区1楼', '路由器7', 'Cisco Catalyst 9300', 'Fortinet', 'DEV-42C25EBC', '71.132.240.193', 'https', 443, 'admin', 'QXAyaU91WFRTWUZMQzB2YzRLSjNIZz09', 'ZXVncFFTM3hMVVM0UVJXelV6eGFuQT09', NULL, '设备位置: 机房A-1层', 'system', '2023-05-06 10:58:07', '2023-05-06 10:58:07');
INSERT INTO `net_dev_cred` VALUES (8, '路由器', '内网', '机房B-1层', '机房A-1层', '机房B-2层', '路由器8', 'Juniper EX4300', 'Cisco', 'DEV-C8276DC5', '159.97.101.33', 'http', 80, 'admin', 'YmVaZ3ozRGdqb1pySTdjWGc5SmhxZz09', 'bmd1ZXpWNUNxcytQSzVNYWs4QytpUT09', NULL, '设备位置: 数据中心1区', 'system', '2024-01-06 13:17:13', '2024-01-06 13:17:13');
INSERT INTO `net_dev_cred` VALUES (9, '入侵防御系统', '内网', '数据中心1区', '办公区1楼', '数据中心4区', '入侵防御系统9', 'Aruba 2930F', 'HUAWEI', 'DEV-46D9CA3B', '39.152.223.128', 'telnet', 23, 'admin', 'aG5ibHZvQmZ1R21sK0pwTjRobzgrdz09', 'My9sc0xVbzJrS25oSC81NlVETzFuZz09', NULL, '设备位置: 办公区2楼', 'system', '2023-08-02 02:41:06', '2023-08-02 02:41:06');
INSERT INTO `net_dev_cred` VALUES (10, '入侵防御系统', '内网', '数据中心4区', '办公区4楼', '办公区4楼', '入侵防御系统10', 'Juniper SRX Series', 'Cisco', 'DEV-A60E9B4A', '113.214.135.189', 'http', 80, 'admin', 'TW9zT0lFZWJnei9Gd2hoZWQ1VFhjZz09', 'RzJyZXNVUFNVZmlId1BsNEVyR01KQT09', NULL, '设备位置: 办公区1楼', 'system', '2025-04-01 22:57:55', '2025-04-01 22:57:55');
INSERT INTO `net_dev_cred` VALUES (11, '无线AP', '内网', '数据中心3区', '数据中心4区', '数据中心3区', '无线AP11', 'Palo Alto PA-220', 'HUAWEI', 'DEV-925D4FD3', '57.84.151.112', 'ssh', 22, 'admin', 'M2U4QmFBWWRxVm1FU3hqRENzOTNJZz09', 'YjFwV0NUdm1YZkxzN3NGZ2V5dWEzQT09', NULL, '设备位置: 办公区2楼', 'system', '2024-07-30 14:20:54', '2024-07-30 14:20:54');
INSERT INTO `net_dev_cred` VALUES (12, '防火墙', '内网', '数据中心2区', '办公区4楼', '办公区4楼', '防火墙12', 'Aruba 2930F', 'Fortinet', 'DEV-7505A61C', '21.236.138.15', 'ssh', 22, 'admin', 'TEVQK2N0TGtsbU1QQ1lzMHk4Unl2QT09', 'N1RKSDl2VnRZTFVpRG9mdmhSdmM3UT09', NULL, '设备位置: 数据中心1区', 'system', '2024-04-24 11:16:39', '2024-04-24 11:16:39');
INSERT INTO `net_dev_cred` VALUES (13, '路由器', '内网', '机房A-1层', '数据中心1区', '机房A-2层', '路由器13', 'Cisco Catalyst 9300', 'Palo Alto', 'DEV-006E26D5', '96.94.91.101', 'https', 443, 'admin', 'MUZ1QUppbm9Kdzh1Y0taMDVyVy9OZz09', 'c1hpUnBTanhZN1RDZzdCRzNEQzZrUT09', NULL, '设备位置: 办公区1楼', 'system', '2024-05-02 04:30:50', '2024-05-02 04:30:50');
INSERT INTO `net_dev_cred` VALUES (14, '负载均衡器', '内网', '办公区2楼', '数据中心3区', '机房B-2层', '负载均衡器14', 'Cisco ASA 5500', 'HUAWEI', 'DEV-1678FE15', '53.66.154.52', 'telnet', 23, 'admin', 'QjZzU2RPcWRwSWdod0k5NnVBcHZ2QT09', 'RFNSYlh3SitMcGpWVlNjaHdmVW50Zz09', NULL, '设备位置: 机房A-1层', 'system', '2025-05-17 10:44:39', '2025-05-17 10:44:39');
INSERT INTO `net_dev_cred` VALUES (15, '交换机', '内网', '办公区2楼', '机房A-2层', '数据中心4区', '交换机15', 'HUAWEI AR6000', 'Palo Alto', 'DEV-AC265463', '69.145.180.92', 'telnet', 23, 'admin', 'ZWZMRURsZE5YWm8xd3NFOHBqL0tPdz09', 'eklyMTZqQ01iL3B5NWN6V2xIdWpYUT09', NULL, '设备位置: 数据中心3区', 'system', '2025-07-31 18:46:13', '2025-07-31 18:46:13');
INSERT INTO `net_dev_cred` VALUES (16, '交换机', '内网', '数据中心3区', '办公区2楼', '数据中心2区', '交换机16', 'HUAWEI USG6000', 'Fortinet', 'DEV-05F953F8', '39.86.147.60', 'ssh', 22, 'admin', 'SWNkUGhRR3BSdkNOZVhXVzhMWUdMUT09', 'cGN0ejltSjYweldjTDR6dmZVT05oQT09', NULL, '设备位置: 机房A-1层', 'system', '2025-03-07 22:51:46', '2025-03-07 22:51:46');
INSERT INTO `net_dev_cred` VALUES (17, '交换机', '内网', '数据中心4区', '机房A-2层', '办公区2楼', '交换机17', 'Cisco ISR 4000', 'HUAWEI', 'DEV-E325CBC1', '110.98.129.137', 'https', 443, 'admin', 'SmFBbXhXZGZOMGhJUFY0eUt2NzJ4Zz09', 'MXRHd0pOWjVyMWtqZld3RVBnRzB4dz09', NULL, '设备位置: 机房A-1层', 'system', '2024-06-04 14:56:20', '2024-06-04 14:56:20');
INSERT INTO `net_dev_cred` VALUES (18, '入侵防御系统', '内网', '机房B-1层', '办公区4楼', '数据中心3区', '入侵防御系统18', 'Palo Alto PA-220', 'Palo Alto', 'DEV-4DA9127C', '114.55.120.123', 'ssh', 22, 'admin', 'OVFrd01iMEFDRjFsVVYzTDd0THczUT09', 'czNNRHFveEJZUmVCR3BrbDA5aHJMZz09', NULL, '设备位置: 办公区4楼', 'system', '2025-10-16 07:20:28', '2025-10-16 07:20:28');
INSERT INTO `net_dev_cred` VALUES (19, '路由器', '内网', '机房A-1层', '办公区4楼', '数据中心2区', '路由器19', 'HUAWEI AR6000', 'Juniper', 'DEV-08FC78DC', '227.48.79.116', 'https', 443, 'admin', 'SGg1YzdPQlVONkFSZFRxOVJ5N0VoUT09', 'Um5yNEJqMzZ6U00rSFpUbU9GazFCZz09', NULL, '设备位置: 数据中心2区', 'system', '2025-04-11 09:08:27', '2025-04-11 09:08:27');
INSERT INTO `net_dev_cred` VALUES (20, 'VPN设备', '内网', '数据中心4区', '数据中心2区', '数据中心4区', 'VPN设备20', 'Juniper SRX Series', 'HUAWEI', 'DEV-11C055AD', '246.155.217.244', 'ssh', 22, 'admin', 'ZGxBcTFYY3BpQmRVZlhMeUdvVnI3UT09', 'LzYrK3NwT1BwUHMza2o0RzBvcHNuUT09', NULL, '设备位置: 办公区4楼', 'system', '2025-02-12 18:39:34', '2025-02-12 18:39:34');
INSERT INTO `net_dev_cred` VALUES (21, '无线AP', '内网', '机房A-2层', '办公区2楼', '数据中心4区', '无线AP21', 'Fortinet FortiGate', 'Cisco', 'DEV-BF5F473A', '117.25.240.102', 'https', 443, 'admin', 'cFFNRDdUTW9oRWhyTlFkL1E0SHY1Zz09', 'RWtDWTQrZHd3ekZ1VFlZMjg3TWZGUT09', NULL, '设备位置: 办公区2楼', 'system', '2023-03-29 03:36:34', '2023-03-29 03:36:34');
INSERT INTO `net_dev_cred` VALUES (22, '入侵检测系统', '内网', '数据中心2区', '办公区2楼', '数据中心2区', '入侵检测系统22', 'Juniper SRX Series', 'Juniper', 'DEV-E7A1FA77', '182.108.90.242', 'http', 80, 'admin', 'M0p2aVV2NStncmpqTExzS0t6QmZsUT09', 'NDJ3Ym1yL1EyNHZoOXFNNkRHWmFGQT09', NULL, '设备位置: 数据中心4区', 'system', '2025-05-19 11:41:26', '2025-05-19 11:41:26');
INSERT INTO `net_dev_cred` VALUES (23, '交换机', '内网', '办公区2楼', '数据中心2区', '数据中心3区', '交换机23', 'Juniper MX Series', 'Juniper', 'DEV-0F245621', '40.81.60.132', 'telnet', 23, 'admin', 'QzNkTDI4NVYzbDJHOWEwdVZUZ2FNUT09', 'M2kxMVhqdDF1NTlZNHhHUkNyZE8zZz09', NULL, '设备位置: 办公区1楼', 'system', '2025-03-17 20:21:55', '2025-03-17 20:21:55');
INSERT INTO `net_dev_cred` VALUES (24, '防火墙', '内网', '数据中心4区', '数据中心2区', '机房A-2层', '防火墙24', 'HUAWEI S5700', 'Cisco', 'DEV-2CDA3819', '147.84.82.81', 'http', 80, 'admin', 'UkFxb0xqOE1iTURYRkhpbHIrU3hhUT09', 'NFhkK1VHd1ljMmZhN0txcTR4STlSdz09', NULL, '设备位置: 数据中心1区', 'system', '2024-12-18 13:00:11', '2024-12-18 13:00:11');
INSERT INTO `net_dev_cred` VALUES (25, '交换机', '内网', '机房A-1层', '办公区2楼', '办公区1楼', '交换机25', 'Cisco ISR 4000', 'Aruba', 'DEV-2D4584F0', '143.4.84.131', 'ssh', 22, 'admin', 'V1NZUWI2bzJaTnZYZVBNWGpadmc1QT09', 'WGpaajdhUGRZZXJBcG9panFoQzR0Zz09', NULL, '设备位置: 办公区4楼', 'system', '2025-09-24 06:05:35', '2025-09-24 06:05:35');
INSERT INTO `net_dev_cred` VALUES (26, 'VPN设备', '内网', '机房B-1层', '机房A-1层', '办公区1楼', 'VPN设备26', 'Juniper SRX Series', 'Palo Alto', 'DEV-C525172E', '30.203.66.10', 'https', 443, 'admin', 'RjlZY0M2MmhJalFwMEJpUGVYc1I2Zz09', 'Zm5sUkZsWWh0SHE4QWd3dldLbmovUT09', NULL, '设备位置: 数据中心2区', 'system', '2024-03-01 20:26:42', '2024-03-01 20:26:42');
INSERT INTO `net_dev_cred` VALUES (27, '无线AP', '内网', '机房B-1层', '数据中心1区', '数据中心2区', '无线AP27', 'HUAWEI S5700', 'Fortinet', 'DEV-76011ADE', '66.132.250.178', 'http', 80, 'admin', 'cW5EUHRHeXJwcExIZU44VmhHUGJGUT09', 'ZkVwSkNkSkpDY0tvYlU1SStqSHc1UT09', NULL, '设备位置: 办公区4楼', 'system', '2023-11-15 12:57:28', '2023-11-15 12:57:28');
INSERT INTO `net_dev_cred` VALUES (28, '负载均衡器', '内网', '办公区3楼', '办公区4楼', '数据中心4区', '负载均衡器28', 'Palo Alto PA-220', 'Fortinet', 'DEV-81C79133', '254.142.35.142', 'http', 80, 'admin', 'Uy9sMDFNTWliZzNhKzIyN2ZwZ0NBdz09', 'RkhKcGZZR3pmSXRETGZIU0lpaCs5QT09', NULL, '设备位置: 机房A-1层', 'system', '2025-03-27 18:05:13', '2025-03-27 18:05:13');
INSERT INTO `net_dev_cred` VALUES (29, '入侵检测系统', '内网', '机房A-1层', '机房A-2层', '数据中心1区', '入侵检测系统29', 'Cisco ISR 4000', 'Palo Alto', 'DEV-F30F598A', '158.42.29.55', 'ssh', 22, 'admin', 'RlR3dUZtRFNxQ1RKUjF2SkVnNTk2QT09', 'bmxqN1VTSVJJK2tXWEd4UTgrQjVZdz09', NULL, '设备位置: 办公区3楼', 'system', '2023-08-31 05:37:27', '2023-08-31 05:37:27');
INSERT INTO `net_dev_cred` VALUES (30, '无线AP', '内网', '数据中心3区', '机房B-1层', '机房B-1层', '无线AP30', 'Aruba 2930F', 'Aruba', 'DEV-6B14E25C', '98.159.116.227', 'ssh', 22, 'admin', 'UDFmWTVGWlpwK2RqVWp3NXJ6NERqZz09', 'UVB4eVczL0xwQnB3aG5nN0w4UDB4QT09', NULL, '设备位置: 机房B-1层', 'system', '2023-11-13 10:36:35', '2023-11-13 10:36:35');
INSERT INTO `net_dev_cred` VALUES (31, '负载均衡器', '内网', '机房A-1层', '数据中心1区', '数据中心1区', '负载均衡器31', 'Juniper MX Series', 'Cisco', 'DEV-963F9BBC', '123.249.116.20', 'http', 80, 'admin', 'S2w4MWdzb2d0TGhRbmgzNU04T25EUT09', 'clFuczNGRXFGTFhCK1ViVGpEdHc0UT09', NULL, '设备位置: 办公区4楼', 'system', '2024-03-13 03:12:21', '2024-03-13 03:12:21');
INSERT INTO `net_dev_cred` VALUES (32, '防火墙', '内网', '办公区3楼', '办公区2楼', '办公区3楼', '防火墙32', 'Juniper EX4300', 'Palo Alto', 'DEV-BF67519D', '41.137.223.215', 'https', 443, 'admin', 'b2JONkkwN3FYcTg3djh3YnF1UTVFZz09', 'VC9GVkt0ZW02MGcwVDUvY1E5VVRldz09', NULL, '设备位置: 数据中心4区', 'system', '2023-02-05 22:29:10', '2023-02-05 22:29:10');
INSERT INTO `net_dev_cred` VALUES (33, '防火墙', '内网', '数据中心2区', '机房A-2层', '数据中心2区', '防火墙33', 'HUAWEI AR6000', 'Palo Alto', 'DEV-02D8645D', '196.41.60.242', 'https', 443, 'admin', 'QzVzSUExUnJIdFIzL3hnWmxLajVWUT09', 'WmQyN2ExVDk2VXowRENQYUZCRUpHZz09', NULL, '设备位置: 数据中心2区', 'system', '2025-09-13 17:08:35', '2025-09-13 17:08:35');
INSERT INTO `net_dev_cred` VALUES (34, '交换机', '内网', '机房A-1层', '办公区2楼', '数据中心4区', '交换机34', 'Cisco ISR 4000', 'Palo Alto', 'DEV-C9FA2C30', '181.176.135.15', 'ssh', 22, 'admin', 'NDFPNkUwUFMwb1l2UjF5REh0ckkvdz09', 'TFluajRCdm5mY2drNHBXdjQvMGg5Zz09', NULL, '设备位置: 办公区3楼', 'system', '2024-08-07 08:42:12', '2024-08-07 08:42:12');
INSERT INTO `net_dev_cred` VALUES (35, '路由器', '内网', '办公区1楼', '办公区2楼', '办公区4楼', '路由器35', 'HUAWEI AR6000', 'Juniper', 'DEV-90236395', '23.123.121.77', 'http', 80, 'admin', 'OWtuWGNMRGJPbnBWZkR0ZXV1Y2hWQT09', 'SUxISWVLaFRlN2g4VUhLNG5qZTBidz09', NULL, '设备位置: 办公区2楼', 'system', '2023-03-14 11:17:51', '2023-03-14 11:17:51');
INSERT INTO `net_dev_cred` VALUES (36, 'VPN设备', '内网', '机房B-2层', '机房B-2层', '机房B-1层', 'VPN设备36', 'Juniper MX Series', 'Juniper', 'DEV-54D52555', '151.175.230.216', 'telnet', 23, 'admin', 'N3JBWXlVeDk1VERCOWQ4dFEycFNQdz09', 'UzVCeU1TZWZCNXIwanJrNlp1TDQ3UT09', NULL, '设备位置: 办公区1楼', 'system', '2024-09-29 09:47:42', '2024-09-29 09:47:42');
INSERT INTO `net_dev_cred` VALUES (37, '负载均衡器', '内网', '办公区3楼', '办公区3楼', '机房A-2层', '负载均衡器37', 'Juniper SRX Series', 'Aruba', 'DEV-41BE7A7B', '117.98.206.108', 'telnet', 23, 'admin', 'V05maG5rWFVNblpFRDhMWDhTNStrQT09', 'MnZHbmt0dGtZLzNiaUEzcHdDdWExUT09', NULL, '设备位置: 机房B-2层', 'system', '2024-08-26 15:43:05', '2024-08-26 15:43:05');
INSERT INTO `net_dev_cred` VALUES (38, '路由器', '内网', '机房B-2层', '数据中心1区', '数据中心1区', '路由器38', 'Juniper EX4300', 'Palo Alto', 'DEV-DAF4B80A', '74.26.166.21', 'ssh', 22, 'admin', 'OEN0V1IwUWZuRWpJSUc3UG9Za1REZz09', 'RUhmMmpRTUNhaHBxMDdvdVBIMXJrdz09', NULL, '设备位置: 机房B-2层', 'system', '2025-03-19 00:03:53', '2025-03-19 00:03:53');
INSERT INTO `net_dev_cred` VALUES (39, '无线AP', '内网', '办公区4楼', '机房A-1层', '办公区4楼', '无线AP39', 'HUAWEI S5700', 'Aruba', 'DEV-C0E4A2C0', '172.222.243.250', 'ssh', 22, 'admin', 'Zk5XWDZhS3YxQVRIOW1qSUlKeUQrQT09', 'SUw0SHU2WktrZFNmeVZNdmo1TUh6QT09', NULL, '设备位置: 机房A-2层', 'system', '2025-08-03 18:45:56', '2025-08-03 18:45:56');
INSERT INTO `net_dev_cred` VALUES (40, '负载均衡器', '内网', '机房A-1层', '数据中心1区', '办公区3楼', '负载均衡器40', 'Aruba 2930F', 'Aruba', 'DEV-9A2DC563', '104.131.218.166', 'ssh', 22, 'admin', 'cjV0TUhzdmprRlVrQ090bkp1K0REUT09', 'b1hCcnJOOGhGc2tMWkFxcXR3bys3QT09', NULL, '设备位置: 办公区3楼', 'system', '2025-09-22 22:39:48', '2025-09-22 22:39:48');
INSERT INTO `net_dev_cred` VALUES (41, '入侵检测系统', '内网', '数据中心1区', '机房B-2层', '机房A-2层', '入侵检测系统41', 'HUAWEI USG6000', 'Palo Alto', 'DEV-BB7A8063', '198.4.68.7', 'telnet', 23, 'admin', 'S2ord3JFMkd2OTJtVHNqY1EvNVlWUT09', 'dmdjbVp2a3NwTjlwMFozWFYwYnJMUT09', NULL, '设备位置: 办公区2楼', 'system', '2023-02-05 15:05:32', '2023-02-05 15:05:32');
INSERT INTO `net_dev_cred` VALUES (42, '无线AP', '内网', '数据中心4区', '办公区1楼', '数据中心4区', '无线AP42', 'Juniper SRX Series', 'Aruba', 'DEV-9921AF9D', '36.4.247.157', 'telnet', 23, 'admin', 'TEJUZlNHR2Y0cEU1WW50RXpBY0QvUT09', 'QlJ1dWVMZ0NBZFR2b2xYaXpFYjR6UT09', NULL, '设备位置: 办公区3楼', 'system', '2025-03-09 22:40:38', '2025-03-09 22:40:38');
INSERT INTO `net_dev_cred` VALUES (43, '负载均衡器', '内网', '机房A-2层', '数据中心3区', '办公区3楼', '负载均衡器43', 'Fortinet FortiGate', 'Juniper', 'DEV-8EA8B0FE', '13.88.200.100', 'telnet', 23, 'admin', 'Yy9qWmx4NVA5Vk51UTZFOTdSVDdEdz09', 'WDZpOFZBL0VudllQdEMyTW1TWEtLdz09', NULL, '设备位置: 数据中心3区', 'system', '2025-02-01 20:50:37', '2025-02-01 20:50:37');
INSERT INTO `net_dev_cred` VALUES (44, 'VPN设备', '内网', '机房B-1层', '数据中心4区', '办公区3楼', 'VPN设备44', 'Aruba 2930F', 'Aruba', 'DEV-224C8A42', '254.141.153.253', 'https', 443, 'admin', 'OGhBWFY1MEw0L0VOd3FJUFl2VDFvZz09', 'L2ZydHZsRDJ3QkRRUzByaXBWbzlBUT09', NULL, '设备位置: 数据中心4区', 'system', '2023-05-21 22:23:30', '2023-05-21 22:23:30');
INSERT INTO `net_dev_cred` VALUES (45, '负载均衡器', '内网', '机房B-2层', '办公区2楼', '机房B-1层', '负载均衡器45', 'HUAWEI USG6000', 'Fortinet', 'DEV-E0C8990A', '95.226.221.108', 'https', 443, 'admin', 'dVY4eUdvaS8wNFlDNHVVOS81Y29LUT09', 'NUJQRVpZMURETkY0NUdLdFVZV2pHUT09', NULL, '设备位置: 办公区3楼', 'system', '2023-06-27 10:03:13', '2023-06-27 10:03:13');
INSERT INTO `net_dev_cred` VALUES (46, '入侵检测系统', '内网', '办公区4楼', '数据中心1区', '办公区4楼', '入侵检测系统46', 'Cisco ISR 4000', 'Juniper', 'DEV-7B374029', '70.84.61.169', 'https', 443, 'admin', 'U3NrT2pzTGZ0V2VSVkJBRUV3Y2VXQT09', 'NTR3U0F5QXY0MU1sdm5Fc2lLR1BSQT09', NULL, '设备位置: 数据中心4区', 'system', '2023-07-20 12:21:02', '2023-07-20 12:21:02');
INSERT INTO `net_dev_cred` VALUES (47, '无线AP', '内网', '机房B-1层', '办公区4楼', '办公区2楼', '无线AP47', 'Cisco Catalyst 9300', 'Juniper', 'DEV-58677051', '220.43.214.30', 'https', 443, 'admin', 'S0J5Ulh1TVJsUnRLWXQ5Z1V2UjJmdz09', 'U3NrRkNSWXJ6V2RqUHZZNHJGcDFIdz09', NULL, '设备位置: 数据中心4区', 'system', '2023-12-16 02:18:25', '2023-12-16 02:18:25');
INSERT INTO `net_dev_cred` VALUES (48, '负载均衡器', '内网', '办公区3楼', '数据中心1区', '机房B-1层', '负载均衡器48', 'Cisco ASA 5500', 'Palo Alto', 'DEV-6CE4D07E', '24.113.93.18', 'https', 443, 'admin', 'dzZWRDJpVTZIaFpIbUthcHl2bW9KZz09', 'Q21uZTJCL2hlMFBJMitsYytPMk1BZz09', NULL, '设备位置: 机房A-1层', 'system', '2025-07-12 15:59:09', '2025-07-12 15:59:09');
INSERT INTO `net_dev_cred` VALUES (49, '交换机', '内网', '机房A-2层', '数据中心1区', '办公区3楼', '交换机49', 'Aruba 2930F', 'Cisco', 'DEV-76116947', '11.208.147.50', 'telnet', 23, 'admin', 'MWYzbmhPallibS8zUUZMaXZFdGJQdz09', 'UUVMNzZLL1ROM1lvWm0vY3ZMWnA3dz09', NULL, '设备位置: 办公区2楼', 'system', '2024-12-19 18:22:54', '2024-12-19 18:22:54');
INSERT INTO `net_dev_cred` VALUES (50, 'VPN设备', '内网', '数据中心1区', '办公区1楼', '机房A-2层', 'VPN设备50', 'Aruba 2930F', 'Cisco', 'DEV-68531C81', '168.176.104.95', 'http', 80, 'admin', 'MjRHY1VmV3kwd3RuZ0wwNHc2K3ZoZz09', 'NVcwUjN1YXp3N1dCdWFudGpsUnBydz09', NULL, '设备位置: 数据中心2区', 'system', '2025-12-02 21:03:29', '2025-12-02 21:03:29');
INSERT INTO `net_dev_cred` VALUES (51, '无线AP', '内网', '数据中心4区', '数据中心1区', '办公区1楼', '无线AP51', 'Cisco ASA 5500', 'HUAWEI', 'DEV-351ADCE6', '221.64.25.41', 'http', 80, 'admin', 'a3NqT2F6R0Q5UTNqVHRLUFdqcmhHZz09', 'OExyajJoRWd3Vkt5OTYxWFl0MWloZz09', NULL, '设备位置: 机房A-1层', 'system', '2023-09-19 14:30:43', '2023-09-19 14:30:43');
INSERT INTO `net_dev_cred` VALUES (52, '负载均衡器', '内网', '数据中心3区', '机房B-1层', '办公区4楼', '负载均衡器52', 'Cisco ASA 5500', 'Cisco', 'DEV-6B278ECF', '18.83.65.152', 'ssh', 22, 'admin', 'bnVKNzRCTXJJaXpzMTZPMWorLzFadz09', 'YjJ3d2JTTFBhWnZ5TE0wOUdxSXFxdz09', NULL, '设备位置: 办公区4楼', 'system', '2025-07-02 12:08:33', '2025-07-02 12:08:33');
INSERT INTO `net_dev_cred` VALUES (53, '交换机', '内网', '机房B-1层', '办公区2楼', '办公区4楼', '交换机53', 'HUAWEI S5700', 'Fortinet', 'DEV-C27D055D', '34.88.227.167', 'ssh', 22, 'admin', 'aXhQaU8zS2JnWDhQR083bUlVOVhVQT09', 'U0I5S1pLVjRCY3ljOWlzdmlNNG1yQT09', NULL, '设备位置: 办公区1楼', 'system', '2023-10-16 01:51:35', '2023-10-16 01:51:35');
INSERT INTO `net_dev_cred` VALUES (54, '路由器', '内网', '数据中心4区', '数据中心3区', '数据中心4区', '路由器54', 'HUAWEI S5700', 'Juniper', 'DEV-04E4264F', '215.210.172.132', 'ssh', 22, 'admin', 'YUhGY01WMDN2S1lDRFpQc0p6UkhXUT09', 'RVU2N3JDRk9VK3U4cThNbXczaXJidz09', NULL, '设备位置: 办公区2楼', 'system', '2023-01-07 18:04:38', '2023-01-07 18:04:38');
INSERT INTO `net_dev_cred` VALUES (55, '入侵检测系统', '内网', '机房B-2层', '数据中心3区', '数据中心4区', '入侵检测系统55', 'Cisco ISR 4000', 'Fortinet', 'DEV-4A7B2523', '191.205.229.231', 'telnet', 23, 'admin', 'WHNyb0ZkZENXZWh6QmhtMkRZRHU1dz09', 'L3NuQnU5NW9lcW4xWWZTUXhBZnliZz09', NULL, '设备位置: 机房B-2层', 'system', '2024-07-02 07:08:19', '2024-07-02 07:08:19');
INSERT INTO `net_dev_cred` VALUES (56, '入侵防御系统', '内网', '办公区2楼', '机房B-2层', '办公区3楼', '入侵防御系统56', 'Juniper MX Series', 'Aruba', 'DEV-71D801F2', '192.112.228.63', 'https', 443, 'admin', 'NSsrandyN1h5d1ZzS0tDb2dkODRNdz09', 'bEFoelh3eXdNR1hhUGF2QWxyR1l3QT09', NULL, '设备位置: 机房B-2层', 'system', '2023-03-20 15:33:43', '2023-03-20 15:33:43');
INSERT INTO `net_dev_cred` VALUES (57, '交换机', '内网', '机房B-1层', '机房B-2层', '数据中心2区', '交换机57', 'HUAWEI S5700', 'HUAWEI', 'DEV-99B5F5A2', '204.160.35.124', 'ssh', 22, 'admin', 'QWRhai9nOVA1UjBYMjB0c2lTMjJPUT09', 'Q2I4dm14TnUwZWFoeWtPeUR5NklMdz09', NULL, '设备位置: 机房B-2层', 'system', '2023-04-04 02:16:59', '2023-04-04 02:16:59');
INSERT INTO `net_dev_cred` VALUES (58, '无线AP', '内网', '数据中心1区', '机房A-2层', '机房B-2层', '无线AP58', 'Palo Alto PA-220', 'Cisco', 'DEV-CB21B162', '222.15.72.6', 'http', 80, 'admin', 'ZzhZeXVGQm96czBYdFl4U013TGdUZz09', 'V2w5MCtLMW40QUNibkkyMkFOQ1Jxdz09', NULL, '设备位置: 办公区3楼', 'system', '2024-03-03 13:44:57', '2024-03-03 13:44:57');
INSERT INTO `net_dev_cred` VALUES (59, '负载均衡器', '内网', '数据中心4区', '办公区3楼', '机房A-2层', '负载均衡器59', 'Aruba 2930F', 'Juniper', 'DEV-E05092F0', '57.44.70.203', 'telnet', 23, 'admin', 'Sm1mOU8wdS9vYUtpazJ5YmtjN0dwdz09', 'M0U0MTNQQXVweHJiRnRqTjdHZHRwZz09', NULL, '设备位置: 办公区3楼', 'system', '2025-04-20 12:23:05', '2025-04-20 12:23:05');
INSERT INTO `net_dev_cred` VALUES (60, 'VPN设备', '内网', '机房A-1层', '机房B-1层', '数据中心2区', 'VPN设备60', 'Juniper SRX Series', 'Fortinet', 'DEV-B4C7255F', '111.250.151.163', 'https', 443, 'admin', 'ekkzQS8zalZRQnNMOERTN0w0ZWR2Zz09', 'dURGQncveEVqUXBkTG56UVFNZ3VUUT09', NULL, '设备位置: 办公区3楼', 'system', '2023-01-10 03:39:24', '2023-01-10 03:39:24');
INSERT INTO `net_dev_cred` VALUES (61, '交换机', '内网', '办公区3楼', '数据中心2区', '数据中心1区', '交换机61', 'Aruba 2930F', 'Juniper', 'DEV-8EB4DEC3', '245.166.125.52', 'https', 443, 'admin', 'cnRxdkNnbHZFWGJqRWNpbFFGbnBKdz09', 'MkgraCtqd2VHWkIycUFCU1JCVFg5QT09', NULL, '设备位置: 机房A-2层', 'system', '2025-02-08 19:15:59', '2025-02-08 19:15:59');
INSERT INTO `net_dev_cred` VALUES (62, 'VPN设备', '内网', '数据中心2区', '办公区1楼', '机房B-1层', 'VPN设备62', 'Juniper EX4300', 'Fortinet', 'DEV-8D1398F4', '117.211.154.70', 'ssh', 22, 'admin', 'dGdFUzRteFpRK3d4SGh6MTJKM0VCUT09', 'Tnh2UW9DWVdXYnhlMEEyQVFsRExlQT09', NULL, '设备位置: 机房A-2层', 'system', '2024-07-26 08:40:39', '2024-07-26 08:40:39');
INSERT INTO `net_dev_cred` VALUES (63, '负载均衡器', '内网', '办公区1楼', '数据中心1区', '办公区3楼', '负载均衡器63', 'Cisco ASA 5500', 'Palo Alto', 'DEV-AFC9CB5F', '226.89.58.177', 'https', 443, 'admin', 'WmMxUFhYNnN2YmtqZzk0SnRMa1lxdz09', 'T0p2ekpaMXR4NS9IRlFsaUd6TS8rQT09', NULL, '设备位置: 办公区4楼', 'system', '2025-08-05 20:10:56', '2025-08-05 20:10:56');
INSERT INTO `net_dev_cred` VALUES (64, '入侵检测系统', '内网', '机房B-2层', '机房A-2层', '机房A-2层', '入侵检测系统64', 'Cisco ISR 4000', 'Palo Alto', 'DEV-FDEDC36E', '134.106.17.46', 'telnet', 23, 'admin', 'SUF4NXZNOEVsMXEzK25VR3hRa2M4dz09', 'ZnJ4OXpIVExXNkZlVDV3czZsOEFnQT09', NULL, '设备位置: 办公区4楼', 'system', '2025-02-06 03:26:51', '2025-02-06 03:26:51');
INSERT INTO `net_dev_cred` VALUES (65, '防火墙', '内网', '机房B-2层', '办公区2楼', '办公区1楼', '防火墙65', 'Juniper MX Series', 'Fortinet', 'DEV-F4ECE68D', '133.126.9.96', 'https', 443, 'admin', 'aUloMDhQdWRLcGF5SUdqRjhzZHg3dz09', 'NVd4U2ZLWFpuczM1WXJDb3RZdzlOQT09', NULL, '设备位置: 数据中心2区', 'system', '2023-10-23 13:26:17', '2023-10-23 13:26:17');
INSERT INTO `net_dev_cred` VALUES (66, '入侵防御系统', '内网', '数据中心1区', '办公区3楼', '机房A-2层', '入侵防御系统66', 'Juniper EX4300', 'HUAWEI', 'DEV-937A6CCC', '19.57.141.94', 'http', 80, 'admin', 'UHpxZmV6empDd011YUFUWU5EZjFQQT09', 'cEo3NzEzUmxiSG03R1lzSnpDcWVmUT09', NULL, '设备位置: 机房A-2层', 'system', '2025-02-27 20:47:30', '2025-02-27 20:47:30');
INSERT INTO `net_dev_cred` VALUES (67, '路由器', '内网', '办公区3楼', '办公区4楼', '机房B-1层', '路由器67', 'Juniper SRX Series', 'Juniper', 'DEV-1F6C3E73', '87.240.225.238', 'ssh', 22, 'admin', 'TU1RRy8xQTB3THdYUUlLS0dtL3R3UT09', 'YWR4eDNVQ2swK3JHK1JsamJnTEI1Zz09', NULL, '设备位置: 办公区2楼', 'system', '2024-10-12 09:08:41', '2024-10-12 09:08:41');
INSERT INTO `net_dev_cred` VALUES (68, '入侵检测系统', '内网', '数据中心4区', '办公区4楼', '机房B-1层', '入侵检测系统68', 'Juniper SRX Series', 'HUAWEI', 'DEV-5C2C49C9', '126.61.226.200', 'http', 80, 'admin', 'ekNUbE13S3g1REh0Y0U2UTNHZzlCdz09', 'czM2YWVJQlhnbllMeW9zMGNxZ1phZz09', NULL, '设备位置: 机房B-2层', 'system', '2024-08-21 17:22:15', '2024-08-21 17:22:15');
INSERT INTO `net_dev_cred` VALUES (69, '路由器', '内网', '办公区4楼', '机房A-2层', '机房B-1层', '路由器69', 'Cisco Catalyst 9300', 'Aruba', 'DEV-86B3C36C', '201.225.160.64', 'https', 443, 'admin', 'U3lObG1vVG9BUkJNMkVoWm1UcThsdz09', 'WDNTWkhuS0hjR3JYMGxrdXYrVjhadz09', NULL, '设备位置: 机房B-1层', 'system', '2023-07-09 05:42:51', '2023-07-09 05:42:51');
INSERT INTO `net_dev_cred` VALUES (70, '交换机', '内网', '数据中心4区', '办公区3楼', '办公区4楼', '交换机70', 'Cisco ISR 4000', 'Juniper', 'DEV-1039AC53', '29.46.22.174', 'http', 80, 'admin', 'TW5LeGVSNlA5akFyVjVSZmRPRnJFZz09', 'Y3IrM05LbGVPbmRPYTNZUW1QbkRUQT09', NULL, '设备位置: 数据中心1区', 'system', '2024-08-04 04:17:41', '2024-08-04 04:17:41');
INSERT INTO `net_dev_cred` VALUES (71, '负载均衡器', '内网', '数据中心4区', '机房B-2层', '机房A-1层', '负载均衡器71', 'Cisco Catalyst 9300', 'Juniper', 'DEV-6CDAB220', '207.9.214.124', 'ssh', 22, 'admin', 'RWdCQ29acUcvSjJQRlVBRGJ6VTdidz09', 'dUR4TmNFdVdjUUFuTTJQWlkyd2l1dz09', NULL, '设备位置: 办公区2楼', 'system', '2025-05-14 02:42:28', '2025-05-14 02:42:28');
INSERT INTO `net_dev_cred` VALUES (72, '负载均衡器', '内网', '数据中心2区', '办公区1楼', '数据中心3区', '负载均衡器72', 'Aruba 2930F', 'Juniper', 'DEV-BFC6FE85', '234.188.57.94', 'ssh', 22, 'admin', 'SktSekJwdDZvRVF0cExLblRNcEd0dz09', 'YmF6S2F6dlVxblF4TlRkaDdmb3dRUT09', NULL, '设备位置: 数据中心3区', 'system', '2023-04-25 03:49:05', '2023-04-25 03:49:05');
INSERT INTO `net_dev_cred` VALUES (73, '入侵检测系统', '内网', '办公区3楼', '数据中心2区', '机房B-2层', '入侵检测系统73', 'HUAWEI S5700', 'Juniper', 'DEV-D8039B94', '228.176.175.16', 'http', 80, 'admin', 'N0NxcklSYVVvZ3kzRm05TEhvZlZxUT09', 'cDcvc2RpQWQ5Q0hjOHpCc1REMmpTQT09', NULL, '设备位置: 机房A-2层', 'system', '2024-06-10 23:47:05', '2024-06-10 23:47:05');
INSERT INTO `net_dev_cred` VALUES (74, '无线AP', '内网', '办公区3楼', '数据中心1区', '机房A-2层', '无线AP74', 'Juniper EX4300', 'Aruba', 'DEV-2B66EDF5', '248.201.222.48', 'https', 443, 'admin', 'a1g2OUxINkhCSkM4VE1mb2F2MUdndz09', 'OG1Wb3U5cEtWME5NdWdYajBMR1ZXQT09', NULL, '设备位置: 机房B-2层', 'system', '2025-11-15 23:00:59', '2025-11-15 23:00:59');
INSERT INTO `net_dev_cred` VALUES (75, '入侵检测系统', '内网', '机房A-2层', '数据中心3区', '办公区4楼', '入侵检测系统75', 'Juniper SRX Series', 'Fortinet', 'DEV-8999857C', '175.122.36.117', 'ssh', 22, 'admin', 'VEtva2hDOUpyZi9XeEFPR2phL3B1dz09', 'WVFPVmhBcmVaSDdoZ01RV2lxbEN4QT09', NULL, '设备位置: 办公区3楼', 'system', '2024-12-02 17:46:28', '2024-12-02 17:46:28');
INSERT INTO `net_dev_cred` VALUES (76, '交换机', '内网', '办公区3楼', '办公区3楼', '办公区4楼', '交换机76', 'Cisco Catalyst 9300', 'Juniper', 'DEV-F4DFFE68', '136.36.49.224', 'telnet', 23, 'admin', 'bHFHaDN6ekhnaEdkQlJSZFJPa1hSdz09', 'YThLVFdqOVVLSWp2c0h1bi9qaGRhdz09', NULL, '设备位置: 办公区3楼', 'system', '2025-02-27 19:25:49', '2025-02-27 19:25:49');
INSERT INTO `net_dev_cred` VALUES (77, '防火墙', '内网', '数据中心1区', '机房B-1层', '办公区2楼', '防火墙77', 'Cisco ASA 5500', 'HUAWEI', 'DEV-A3803891', '62.97.87.152', 'https', 443, 'admin', 'RG1yWU5laUNWQVdPY3lhb1JSc3JyQT09', 'Q0psdzFCdTlDTUNwRGh0Q0VFR2gyQT09', NULL, '设备位置: 数据中心3区', 'system', '2023-07-14 20:52:01', '2023-07-14 20:52:01');
INSERT INTO `net_dev_cred` VALUES (78, '入侵检测系统', '内网', '机房A-1层', '办公区3楼', '机房A-1层', '入侵检测系统78', 'HUAWEI S5700', 'Aruba', 'DEV-D9F0D084', '146.96.253.52', 'http', 80, 'admin', 'MUtDNjhacE1qcU00TCtmbWlsK1loQT09', 'VStNbmE2RzBINmtqWlh4T1o3MXI4QT09', NULL, '设备位置: 数据中心2区', 'system', '2025-07-27 18:14:07', '2025-07-27 18:14:07');
INSERT INTO `net_dev_cred` VALUES (79, '负载均衡器', '内网', '数据中心3区', '办公区3楼', '机房B-1层', '负载均衡器79', 'HUAWEI AR6000', 'Cisco', 'DEV-4874E791', '112.155.37.40', 'ssh', 22, 'admin', 'dEtiTFdXanVnaVA4M1ZtazBDbXgvZz09', 'YS93eU5FaEpub1QwN0NCVzI3cnJoUT09', NULL, '设备位置: 办公区2楼', 'system', '2024-11-21 23:41:50', '2024-11-21 23:41:50');
INSERT INTO `net_dev_cred` VALUES (80, '交换机', '内网', '数据中心4区', '机房A-2层', '办公区1楼', '交换机80', 'Juniper SRX Series', 'Fortinet', 'DEV-9C3EDFEB', '3.164.179.200', 'ssh', 22, 'admin', 'eU1sQXpCb2JKZDFjZmQ0anZJcmdzQT09', 'Z2xWQytZWkZqcnhsdHNrclB3d0lEdz09', NULL, '设备位置: 办公区3楼', 'system', '2025-08-10 10:00:34', '2025-08-10 10:00:34');
INSERT INTO `net_dev_cred` VALUES (81, '路由器', '内网', '数据中心1区', '机房A-1层', '办公区1楼', '路由器81', 'Juniper EX4300', 'Fortinet', 'DEV-84545E0C', '38.229.165.212', 'http', 80, 'admin', 'S2hhMGRCTml2RUR4NldKMzdvaVJvUT09', 'MmdYVWNGdHA2bnkwZmRvdWNrQm00dz09', NULL, '设备位置: 数据中心4区', 'system', '2023-04-10 09:35:56', '2023-04-10 09:35:56');
INSERT INTO `net_dev_cred` VALUES (82, '入侵防御系统', '内网', '数据中心1区', '数据中心2区', '办公区3楼', '入侵防御系统82', 'Cisco Catalyst 9300', 'Aruba', 'DEV-D60E91B1', '48.140.48.222', 'http', 80, 'admin', 'enJYMHRwY3ZrdjB2dXVBTW41UzY0Zz09', 'SkxmN0ZDK3FLTHRBZjFXcU9DaERPUT09', NULL, '设备位置: 办公区1楼', 'system', '2024-03-03 04:07:35', '2024-03-03 04:07:35');
INSERT INTO `net_dev_cred` VALUES (83, '入侵检测系统', '内网', '数据中心1区', '机房B-2层', '办公区3楼', '入侵检测系统83', 'Cisco ASA 5500', 'Aruba', 'DEV-FF7BA1BA', '110.183.74.232', 'http', 80, 'admin', 'NG5rZXZHblpIa09yV3IrLzZsbVhDUT09', 'STErSXNUcXRUZExWUHNlYmVRdElxQT09', NULL, '设备位置: 机房B-1层', 'system', '2023-07-19 16:46:12', '2023-07-19 16:46:12');
INSERT INTO `net_dev_cred` VALUES (84, '负载均衡器', '内网', '办公区3楼', '办公区2楼', '数据中心3区', '负载均衡器84', 'Palo Alto PA-220', 'Palo Alto', 'DEV-BBC89B26', '119.62.219.117', 'http', 80, 'admin', 'OFdMZFBFTjJ0SVNqK1dZK21sdGFNZz09', 'OWR6b294d0dONGdNK0VCM2RTL0RzQT09', NULL, '设备位置: 办公区1楼', 'system', '2025-07-17 01:15:10', '2025-07-17 01:15:10');
INSERT INTO `net_dev_cred` VALUES (85, '交换机', '内网', '数据中心1区', '数据中心2区', '办公区1楼', '交换机85', 'HUAWEI AR6000', 'Cisco', 'DEV-8DB1EECE', '50.138.239.3', 'http', 80, 'admin', 'ckhkRlB0S1NpMXZNVHNTMm9BREQ1UT09', 'VisrK3lJUmpaMWF1UzFUS0hUN29IQT09', NULL, '设备位置: 数据中心2区', 'system', '2025-03-15 16:35:20', '2025-03-15 16:35:20');
INSERT INTO `net_dev_cred` VALUES (86, '防火墙', '内网', '机房B-1层', '办公区2楼', '办公区3楼', '防火墙86', 'Palo Alto PA-220', 'Palo Alto', 'DEV-32A60707', '176.126.217.70', 'https', 443, 'admin', 'ZmxTdXhONUdYWUk4L0QwTTkxR3FUZz09', 'R1JHcEMyM3ZGY2ZUWDFadExSaC9qdz09', NULL, '设备位置: 数据中心4区', 'system', '2023-08-16 07:46:07', '2023-08-16 07:46:07');
INSERT INTO `net_dev_cred` VALUES (87, '负载均衡器', '内网', '机房A-1层', '数据中心3区', '数据中心3区', '负载均衡器87', 'HUAWEI AR6000', 'Fortinet', 'DEV-D46D0B9B', '167.167.251.210', 'https', 443, 'admin', 'amxMb0d3U2RkWFRkd3FmazE3U0p4Zz09', 'YUFBaTRyZTM2VGhad1hzOGZsejV3QT09', NULL, '设备位置: 数据中心3区', 'system', '2024-12-06 01:47:58', '2024-12-06 01:47:58');
INSERT INTO `net_dev_cred` VALUES (88, '入侵检测系统', '内网', '机房B-1层', '机房B-2层', '数据中心1区', '入侵检测系统88', 'Juniper SRX Series', 'Palo Alto', 'DEV-A51E09A2', '122.85.159.227', 'ssh', 22, 'admin', 'aEV5OU9STUN4THhCRjZpRk1LQStUQT09', 'TzdTNFo2eWtBbXV2WlV2eTE2R0Qzdz09', NULL, '设备位置: 机房A-1层', 'system', '2025-08-29 02:52:50', '2025-08-29 02:52:50');
INSERT INTO `net_dev_cred` VALUES (89, '防火墙', '内网', '数据中心2区', '办公区4楼', '办公区3楼', '防火墙89', 'Juniper MX Series', 'Juniper', 'DEV-B9A23202', '66.65.33.187', 'https', 443, 'admin', 'cXdmQ0N4S09JRmViSmVhdllpUnVYZz09', 'TG05MTY5Qk1UZURUUUw4eGEzNDB3Zz09', NULL, '设备位置: 办公区2楼', 'system', '2025-02-05 09:35:27', '2025-02-05 09:35:27');
INSERT INTO `net_dev_cred` VALUES (90, '负载均衡器', '内网', '数据中心4区', '办公区1楼', '机房A-1层', '负载均衡器90', 'Juniper MX Series', 'Fortinet', 'DEV-9A614889', '210.230.198.139', 'ssh', 22, 'admin', 'NWpXbFVLUFNBeWR2YmJmVzNCVHRPQT09', 'ODYrVnVVV2JOc2dIWGpJZUU1UXlEQT09', NULL, '设备位置: 数据中心2区', 'system', '2025-05-17 11:05:51', '2025-05-17 11:05:51');
INSERT INTO `net_dev_cred` VALUES (91, '交换机', '内网', '数据中心2区', '机房A-2层', '数据中心2区', '交换机91', 'Fortinet FortiGate', 'Cisco', 'DEV-109266AA', '235.129.82.186', 'ssh', 22, 'admin', 'cmxVck1lZ3NZVkUyWmFxSitRdGM2dz09', 'YmZpdllUQkQ2dXo0UElXcFVEMHU1QT09', NULL, '设备位置: 数据中心4区', 'system', '2024-03-10 11:38:29', '2024-03-10 11:38:29');
INSERT INTO `net_dev_cred` VALUES (92, 'VPN设备', '内网', '数据中心2区', '机房B-1层', '数据中心4区', 'VPN设备92', 'Fortinet FortiGate', 'HUAWEI', 'DEV-47109EAF', '6.137.63.133', 'telnet', 23, 'admin', 'WFVYQzVQSzdjME9RbzZZaWs3WVdMQT09', 'Q25UNVZQcmRRTUhiQ2tncUZjbkRVZz09', NULL, '设备位置: 数据中心1区', 'system', '2023-08-11 02:55:46', '2023-08-11 02:55:46');
INSERT INTO `net_dev_cred` VALUES (93, '路由器', '内网', '办公区1楼', '数据中心1区', '数据中心2区', '路由器93', 'HUAWEI USG6000', 'Cisco', 'DEV-170F9C33', '8.170.10.154', 'telnet', 23, 'admin', 'ZnR2VmVsbG9GZmFwcnh5aGNCSi9qdz09', 'WVloOURrYTRLTXdWODY3U3lhblE3QT09', NULL, '设备位置: 办公区3楼', 'system', '2024-11-13 14:44:20', '2024-11-13 14:44:20');
INSERT INTO `net_dev_cred` VALUES (94, '负载均衡器', '内网', '数据中心2区', '办公区3楼', '数据中心1区', '负载均衡器94', 'Juniper EX4300', 'Palo Alto', 'DEV-6AEDA401', '95.38.94.214', 'http', 80, 'admin', 'YThHSmhQM0QvRmJ6VmlLM2pjcy9kZz09', 'bUMyVWtVYi80SjhhZFZjWkdwdmY2Zz09', NULL, '设备位置: 办公区1楼', 'system', '2024-01-02 19:50:07', '2024-01-02 19:50:07');
INSERT INTO `net_dev_cred` VALUES (95, '无线AP', '内网', '机房A-2层', '数据中心3区', '机房A-2层', '无线AP95', 'Cisco ASA 5500', 'Juniper', 'DEV-2FA0059F', '17.45.103.34', 'https', 443, 'admin', 'Q2dYclVnMVhEWFlaLzl4ODNwelRjdz09', 'dnRRZG9nRGZYQlcxNU54RzNnUUViZz09', NULL, '设备位置: 办公区1楼', 'system', '2025-11-02 15:34:10', '2025-11-02 15:34:10');
INSERT INTO `net_dev_cred` VALUES (96, 'VPN设备', '内网', '办公区4楼', '机房A-1层', '机房B-1层', 'VPN设备96', 'HUAWEI AR6000', 'Palo Alto', 'DEV-50D22A88', '93.219.56.234', 'https', 443, 'admin', 'QkNYbUhPY3FCRitkeEMrQU1MbXY0QT09', 'ejVJeFp5UGlCT0Q2SlVBTkRoQnpIUT09', NULL, '设备位置: 办公区3楼', 'system', '2025-02-10 15:49:39', '2025-02-10 15:49:39');
INSERT INTO `net_dev_cred` VALUES (97, '防火墙', '内网', '办公区4楼', '数据中心1区', '数据中心4区', '防火墙97', 'HUAWEI USG6000', 'HUAWEI', 'DEV-B6CA76F8', '12.25.11.93', 'http', 80, 'admin', 'TUlJSXhwRm92RU9rRytOM3VERmFRdz09', 'TVpYY0wzUHo2UDZJZjFqbDR5Uk0yZz09', NULL, '设备位置: 办公区1楼', 'system', '2025-06-26 15:02:32', '2025-06-26 15:02:32');
INSERT INTO `net_dev_cred` VALUES (98, '防火墙', '内网', '机房B-2层', '办公区3楼', '办公区3楼', '防火墙98', 'Juniper SRX Series', 'HUAWEI', 'DEV-1460795A', '183.35.69.70', 'ssh', 22, 'admin', 'Smlja2YyUS9NaFQ3T29pZVFPNzJvZz09', 'SjM0dGo4MmZjbWM1UUladHlFU0pRdz09', NULL, '设备位置: 办公区3楼', 'system', '2023-06-10 00:18:17', '2023-06-10 00:18:17');
INSERT INTO `net_dev_cred` VALUES (99, '入侵检测系统', '内网', '机房B-1层', '办公区4楼', '机房B-1层', '入侵检测系统99', 'Juniper EX4300', 'Palo Alto', 'DEV-7E020573', '2.237.108.98', 'telnet', 23, 'admin', 'TCszaVRHY1hrWkttdUdRY1ErbFdyZz09', 'T2tjQ1NmWjU1eVhpeWY5cjlIb1krQT09', NULL, '设备位置: 机房B-2层', 'system', '2024-03-14 18:58:37', '2024-03-14 18:58:37');
INSERT INTO `net_dev_cred` VALUES (100, '负载均衡器', '内网', '办公区1楼', '机房B-2层', '数据中心1区', '负载均衡器100', 'Juniper EX4300', 'Cisco', 'DEV-F9B49F95', '232.148.201.66', 'ssh', 22, 'admin', 'Q2c3czZSUURSQTBZSEJHUmpMcmI1dz09', 'NmRGK3dqQVFwMVlyNG5kNkdxbmY5Zz09', NULL, '设备位置: 数据中心2区', 'system', '2023-04-10 12:49:40', '2023-04-10 12:49:40');
INSERT INTO `net_dev_cred` VALUES (101, '交换机', '内网', '本部', '门诊-3F', '弱电井', '交换机1', 'mz1F-sw1', 'H3C', 's5120', '1.1.1.1', 'Telnet', 23, 'admin', 'ODJwTzhuTEQrdnkydVB4YlBTSjR3QT09', 'cEdRZEFrVUZta2xJOVhIVGY4cEVudz09', 'RDhkWmx3dkxIS0RJVnpsMEc2RUxydz09', '备注信息', 'system', '2025-11-29 09:10:05', '2025-11-29 13:38:51');
INSERT INTO `net_dev_cred` VALUES (102, '路由器', '外网', '东区', '新楼-5F', '505办公桌下', '505办公桌下交换机', 'dongqu-505sw1', 'ruijie', 'S1', '192.168.202.202', 'HTTP', 80, 'admin', '$2y$12$LYJmS5t/iRI7UCaz8llCGuiGZfVO62RzdPcGKUfDErRae/orb9lWu', '$2y$12$HmakmmI7e.OY5yV3DqUh6ufKgBaJAN6S6sBKE.99HypqRJKwOGjbK', '$2y$12$EcIM56r2zQKDxkH.AZnvVeuucZI26bq3fnYHvE.6R2mng9YXlKQqu', 'beizhuxinxidongqu', 'system', '2025-11-29 13:35:04', '2025-11-29 13:35:04');
INSERT INTO `net_dev_cred` VALUES (103, '交换机', '内网', '测试区域', '测试楼-1楼', '测试位置', '测试设备', 'test-switch-001', '测试品牌', '测试型号', '192.168.1.1', 'SSH', 22, 'admin', 'aVlvQkdlbG1qM05xTGYxdGhvdHRGQT09', 'cEdRZEFrVUZta2xJOVhIVGY4cEVudz09', 'RDhkWmx3dkxIS0RJVnpsMEc2RUxydz09', '测试备注', 'system', '2025-12-05 22:45:34', '2025-12-05 22:45:34');
INSERT INTO `net_dev_cred` VALUES (105, '交换机', '内网', '2', '3', '4', '5', '6', '7', '8', '9.9.9.9', 'SSH', 10, '11', 'bm13azJiY0xnR3dyR2xIbEpsdjNWZz09', 'b1hEMXBuODBHSGNxcFhIaFgyeUlFdz09', 'anlBdzU2OHpuZDJ5Ynpad1ZxTFp3dz09', '15', 'system', '2025-12-05 22:56:38', '2025-12-05 22:56:38');
INSERT INTO `net_dev_cred` VALUES (110, '交换机', '内网', '院本部', '门诊楼-3楼', '弱电井', '门诊3楼弱电井A', 'NW-MZ3F-SW1', 'H3C', 'S5120', '1.1.18.18', 'SSH', 1818, 'admin', 'SE5PMUdZd0s1WlRrK1RxUm5PaWd1UT09', 'RktjeWVBWHk1dTJtRWlnMCt1ZUdGUT09', 'U0tKTk5ERG5sdEMxdVlWQkd3c0QrZz09', 'beizhuadmin', 'system', '2025-12-06 16:18:57', '2025-12-06 16:18:57');
INSERT INTO `net_dev_cred` VALUES (111, '交换机', '内网', '院本部', '门诊楼15楼', '弱电井', '门诊楼15楼内网交换机11', 'NW-MZ-15F-SW11', 'H3C', 'S5120', '9.9.9.99', 'SSH', 22, 'admin', 'Q1RmZWl3dUladnhrVVBYMDhYOEsvQT09', 'RGFaSm8wTVBIYzNWS0o3cUg2OW1aZz09', 'NkQ5cVZSaGc0YUtPeWFVMlJXSUpxZz09', 'beizhu', 'system', '2025-12-06 16:38:50', '2025-12-06 16:38:50');
INSERT INTO `net_dev_cred` VALUES (112, '交换机', '内网', '院本部', '门诊楼15楼', '弱电井', '门诊楼15楼内网交换机123', 'NW-MZ-15F-SW123', 'H3C', 'S5120', '9.91.91.9', 'SSH', 22, 'admin', 'UmVSNy9kM1dld0JrNHh1ZFNTOVZ1dz09', 'SzVDZzVxdDFtVVFDejhNUTFnMUZUUT09', 'eFpiSHFCTW5KcHF6bHVNbllyN2Y5dz09', 'admin121', 'lyb', '2025-12-06 16:45:55', '2025-12-06 16:45:55');
INSERT INTO `net_dev_cred` VALUES (113, '路由器', '内网', 'sasa', 'sasa', 'sasa', 'qwasa', 'sasa', 'asasa', 'sasa', '1.1.1.123', 'Telnet', 23, 'asa', '9LRqO+m9jcmc61FkTMTv1Q==', 'x35geh+fbeYyw/xqUjueiQ==', '9LRqO+m9jcmc61FkTMTv1Q==', 'sa', 'system', '2025-12-16 14:34:58', '2025-12-16 14:34:58');
INSERT INTO `net_dev_cred` VALUES (114, '路由器', '内网', 'sa', 'sasa', 'sas', 'sa', 'sas', 'sa', 'sa', '12.1.2.1', 'SSH', 22, 'sasa', '9LRqO+m9jcmc61FkTMTv1Q==', '5wvhjrWZKM6PBxVzr09r3Q==', '9LRqO+m9jcmc61FkTMTv1Q==', 'asas', 'lyb', '2025-12-16 14:46:21', '2025-12-16 14:46:21');
INSERT INTO `net_dev_cred` VALUES (115, 'AC', '内网', '院本部', '门诊楼-3楼', '弱电井', 'sas', 'saas', '华为', 'S5120', '43.12.12.1', 'HTTP', 22, 'sas', '9LRqO+m9jcmc61FkTMTv1Q==', '5wvhjrWZKM6PBxVzr09r3Q==', 'cZP+CcV/muVhhXC8w+Ut7w==', 'sasa', 'lyb', '2025-12-16 15:16:09', '2025-12-16 15:16:09');
INSERT INTO `net_dev_cred` VALUES (116, '路由器', '内网', 'sasa', 'wqwq', 'ruodiano', 'wqw', 'qwq', '启明星辰', 'S5120', '12.1.1.2', 'Telnet', 22, 'wqw', '9pZXGS9to3l6AVKOR8pSCA==', '5rscTMoJoeOLX01RqOIMCg==', '5rscTMoJoeOLX01RqOIMCg==', 'wqw', 'lyb', '2025-12-16 18:49:21', '2025-12-16 18:49:21');

-- ----------------------------
-- Table structure for phy_server_connection
-- ----------------------------
DROP TABLE IF EXISTS `phy_server_connection`;
CREATE TABLE `phy_server_connection`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `server_id` int(11) NOT NULL COMMENT '关联的服务器ID',
  `interfaceName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '接口名称',
  `cableType` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '线缆类型',
  `peerDeviceName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '对端设备名称',
  `peerDeviceInterface` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '对端设备接口',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_server_id`(`server_id`) USING BTREE,
  CONSTRAINT `phy_server_connection_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `phy_server_info` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 29 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '物理服务器连接信息表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of phy_server_connection
-- ----------------------------
INSERT INTO `phy_server_connection` VALUES (1, 1, 'eth0', '网线', '交换机-819', 'GigabitEthernet0/0/6', '2025-12-17 18:31:08');
INSERT INTO `phy_server_connection` VALUES (2, 1, 'eth1', '光纤', '存储设备-819', 'FC0/0/5', '2025-12-17 18:31:08');
INSERT INTO `phy_server_connection` VALUES (3, 2, 'eth0', '网线', '交换机-924', 'GigabitEthernet0/0/10', '2025-12-17 18:31:26');
INSERT INTO `phy_server_connection` VALUES (4, 2, 'eth1', '光纤', '存储设备-924', 'FC0/0/1', '2025-12-17 18:31:26');
INSERT INTO `phy_server_connection` VALUES (5, 3, 'eth0', '网线', '交换机-790', 'GigabitEthernet0/0/12', '2025-12-17 18:36:09');
INSERT INTO `phy_server_connection` VALUES (6, 3, 'eth1', '光纤', '存储设备-790', 'FC0/0/2', '2025-12-17 18:36:09');
INSERT INTO `phy_server_connection` VALUES (25, 13, 'eth0', '网线', '交换机-391', 'GigabitEthernet0/0/21', '2025-12-17 19:03:51');
INSERT INTO `phy_server_connection` VALUES (26, 13, 'eth1', '光纤', '存储设备-391', 'FC0/0/5', '2025-12-17 19:03:51');
INSERT INTO `phy_server_connection` VALUES (27, 14, 'eth0', '网线', '交换机-234', 'GigabitEthernet0/0/21', '2025-12-17 19:03:57');
INSERT INTO `phy_server_connection` VALUES (28, 14, 'eth1', '光纤', '存储设备-234', 'FC0/0/6', '2025-12-17 19:03:57');

-- ----------------------------
-- Table structure for phy_server_hard_disk
-- ----------------------------
DROP TABLE IF EXISTS `phy_server_hard_disk`;
CREATE TABLE `phy_server_hard_disk`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `server_id` int(11) NOT NULL COMMENT '关联的服务器ID',
  `slot` int(11) NOT NULL COMMENT '硬盘槽位',
  `size` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '硬盘大小',
  `raidName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'RAID名称',
  `raidLevel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'RAID级别',
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '备注信息',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_server_id`(`server_id`) USING BTREE,
  CONSTRAINT `phy_server_hard_disk_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `phy_server_info` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 43 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '物理服务器硬盘信息表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of phy_server_hard_disk
-- ----------------------------
INSERT INTO `phy_server_hard_disk` VALUES (1, 1, 0, '480GB', 'RAID1', 'RAID1', '系统盘', '2025-12-17 18:31:08');
INSERT INTO `phy_server_hard_disk` VALUES (2, 1, 1, '480GB', 'RAID1', 'RAID1', '系统盘', '2025-12-17 18:31:08');
INSERT INTO `phy_server_hard_disk` VALUES (3, 1, 2, '8TB', 'RAID5', 'RAID5', '数据盘', '2025-12-17 18:31:08');
INSERT INTO `phy_server_hard_disk` VALUES (4, 2, 0, '480GB', 'RAID1', 'RAID1', '系统盘', '2025-12-17 18:31:26');
INSERT INTO `phy_server_hard_disk` VALUES (5, 2, 1, '480GB', 'RAID1', 'RAID1', '系统盘', '2025-12-17 18:31:26');
INSERT INTO `phy_server_hard_disk` VALUES (6, 2, 2, '8TB', 'RAID5', 'RAID5', '数据盘', '2025-12-17 18:31:26');
INSERT INTO `phy_server_hard_disk` VALUES (7, 3, 0, '480GB', 'RAID1', 'RAID1', '系统盘', '2025-12-17 18:36:09');
INSERT INTO `phy_server_hard_disk` VALUES (8, 3, 1, '480GB', 'RAID1', 'RAID1', '系统盘', '2025-12-17 18:36:09');
INSERT INTO `phy_server_hard_disk` VALUES (9, 3, 2, '8TB', 'RAID5', 'RAID5', '数据盘', '2025-12-17 18:36:09');
INSERT INTO `phy_server_hard_disk` VALUES (37, 13, 0, '480GB', 'RAID1', 'RAID1', '系统盘', '2025-12-17 19:03:51');
INSERT INTO `phy_server_hard_disk` VALUES (38, 13, 1, '480GB', 'RAID1', 'RAID1', '系统盘', '2025-12-17 19:03:51');
INSERT INTO `phy_server_hard_disk` VALUES (39, 13, 2, '8TB', 'RAID5', 'RAID5', '数据盘', '2025-12-17 19:03:51');
INSERT INTO `phy_server_hard_disk` VALUES (40, 14, 0, '480GB', 'RAID1', 'RAID1', '系统盘', '2025-12-17 19:03:57');
INSERT INTO `phy_server_hard_disk` VALUES (41, 14, 1, '480GB', 'RAID1', 'RAID1', '系统盘', '2025-12-17 19:03:57');
INSERT INTO `phy_server_hard_disk` VALUES (42, 14, 2, '8TB', 'RAID5', 'RAID5', '数据盘', '2025-12-17 19:03:57');

-- ----------------------------
-- Table structure for phy_server_hbacard
-- ----------------------------
DROP TABLE IF EXISTS `phy_server_hbacard`;
CREATE TABLE `phy_server_hbacard`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `server_id` int(11) NOT NULL COMMENT '关联的服务器ID',
  `portCount` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '端口数量',
  `speed` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '端口速度',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_server_id`(`server_id`) USING BTREE,
  CONSTRAINT `phy_server_hbacard_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `phy_server_info` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 15 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '物理服务器HBA卡信息表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of phy_server_hbacard
-- ----------------------------
INSERT INTO `phy_server_hbacard` VALUES (1, 1, '2口', '16Gbps', '2025-12-17 18:31:08');
INSERT INTO `phy_server_hbacard` VALUES (2, 2, '2口', '16Gbps', '2025-12-17 18:31:26');
INSERT INTO `phy_server_hbacard` VALUES (3, 3, '2口', '16Gbps', '2025-12-17 18:36:09');
INSERT INTO `phy_server_hbacard` VALUES (13, 13, '2口', '16Gbps', '2025-12-17 19:03:51');
INSERT INTO `phy_server_hbacard` VALUES (14, 14, '2口', '16Gbps', '2025-12-17 19:03:57');

-- ----------------------------
-- Table structure for phy_server_info
-- ----------------------------
DROP TABLE IF EXISTS `phy_server_info`;
CREATE TABLE `phy_server_info`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `phyServerRoom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '机房/站点',
  `phyServerCabinet` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '机柜编号',
  `phyServerCabinetPosition` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'U位',
  `phyServerBrand` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '厂商',
  `phyServerModel` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '型号',
  `phyServerSn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '序列号',
  `phyServerBmcIp` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'BMC地址',
  `phyServerBmcUsername` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'BMC用户名',
  `phyServerBmcPassword` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '加密存储的BMC密码',
  `purchaseDate` date NOT NULL COMMENT '采购日期',
  `maintenanceDate` date NOT NULL COMMENT '维保截止日期',
  `powerSupplyCount` int(11) NOT NULL DEFAULT 1 COMMENT '电源数量',
  `phyServerNotes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '备注信息',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `phyServerCreatedby` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '创建人',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_room_cabinet`(`phyServerRoom`, `phyServerCabinet`) USING BTREE,
  INDEX `idx_brand_model`(`phyServerBrand`, `phyServerModel`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE,
  INDEX `idx_created_by`(`phyServerCreatedby`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 15 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '物理服务器信息表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of phy_server_info
-- ----------------------------
INSERT INTO `phy_server_info` VALUES (1, '测试机房-819', 'CAB-819', '27U-8U', 'Dell', 'PowerEdge R740-819', 'SN-Q78KQZZ08AR', '192.168.122.8', 'admin', 'veI0ApwJG8HHWqp8Uwy4ag==', '2024-12-17', '2026-12-17', 1, '这是一条测试备注信息，用于测试物理服务器信息录入功能。', '2025-12-17 18:31:08', '2025-12-17 18:31:08', 'lyb');
INSERT INTO `phy_server_info` VALUES (2, '测试机房-924', 'CAB-924', '36U-7U', '联想', 'PowerEdge R740-924', 'SN-1K0M7OV1PRI', '192.168.74.141', 'admin', 'veI0ApwJG8HHWqp8Uwy4ag==', '2024-12-17', '2026-12-17', 1, '这是一条测试备注信息，用于测试物理服务器信息录入功能。', '2025-12-17 18:31:26', '2025-12-17 18:31:26', 'lyb');
INSERT INTO `phy_server_info` VALUES (3, '测试机房-790', 'CAB-790', '23U-10U', '华为', 'PowerEdge R740-790', 'SN-XB1XSU3WA5', '192.168.27.107', 'admin', 'veI0ApwJG8HHWqp8Uwy4ag==', '2024-12-17', '2026-12-17', 1, '这是一条测试备注信息，用于测试物理服务器信息录入功能。', '2025-12-17 18:36:09', '2025-12-17 18:36:09', 'lyb');
INSERT INTO `phy_server_info` VALUES (13, '测试机房-391', 'CAB-391', '29U-8U', 'H3C', 'PowerEdge R740-391', 'SN-KQ4LQFQOE', '192.168.216.181', 'admin', 'veI0ApwJG8HHWqp8Uwy4ag==', '2024-12-17', '2026-12-17', 1, '这是一条测试备注信息，用于测试物理服务器信息录入功能。', '2025-12-17 19:03:51', '2025-12-17 19:03:51', 'system');
INSERT INTO `phy_server_info` VALUES (14, '测试机房-234', 'CAB-234', '12U-5U', 'Dell', 'PowerEdge R740-234', 'SN-51101JFDFFM', '192.168.61.133', 'admin', 'veI0ApwJG8HHWqp8Uwy4ag==', '2024-12-17', '2026-12-17', 2, '这是一条测试备注信息，用于测试物理服务器信息录入功能。', '2025-12-17 19:03:57', '2025-12-17 19:03:57', 'system');

-- ----------------------------
-- Table structure for phy_server_nic
-- ----------------------------
DROP TABLE IF EXISTS `phy_server_nic`;
CREATE TABLE `phy_server_nic`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `server_id` int(11) NOT NULL COMMENT '关联的服务器ID',
  `position` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '网卡位置',
  `portCount` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '网口数量',
  `speed` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '速率规格',
  `interface` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '接口类型',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_server_id`(`server_id`) USING BTREE,
  CONSTRAINT `phy_server_nic_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `phy_server_info` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 15 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '物理服务器网卡信息表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of phy_server_nic
-- ----------------------------
INSERT INTO `phy_server_nic` VALUES (1, 1, '板载', '4口', '万兆', '光口', '2025-12-17 18:31:08');
INSERT INTO `phy_server_nic` VALUES (2, 2, '板载', '4口', '万兆', '光口', '2025-12-17 18:31:26');
INSERT INTO `phy_server_nic` VALUES (3, 3, '板载', '4口', '万兆', '光口', '2025-12-17 18:36:09');
INSERT INTO `phy_server_nic` VALUES (13, 13, '板载', '4口', '万兆', '光口', '2025-12-17 19:03:51');
INSERT INTO `phy_server_nic` VALUES (14, 14, '板载', '4口', '万兆', '光口', '2025-12-17 19:03:57');

-- ----------------------------
-- Table structure for phy_servers
-- ----------------------------
DROP TABLE IF EXISTS `phy_servers`;
CREATE TABLE `phy_servers`  (
  `phy_servers_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '服务器唯一标识',
  `phy_servers_room` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '机房/站点',
  `phy_servers_cabinet` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '机柜编号',
  `phy_servers_cabinet_position` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'U位（格式：_U-_U）',
  `phy_servers_brand` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '厂商',
  `phy_servers_model` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '型号',
  `phy_servers_serial_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '序列号（SN）',
  `phy_servers_bmc_ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'BMC地址',
  `phy_servers_bmc_username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'BMC账号',
  `phy_servers_bmc_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'BMC密码（加密存储）',
  `phy_servers_purchase_date` date NOT NULL COMMENT '采购日期',
  `phy_servers_maintenance_date` date NOT NULL COMMENT '维保截止日期',
  `phy_servers_power_supply_count` int(11) NOT NULL COMMENT '电源数量',
  `phy_servers__notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `phy_servers_created_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '创建人',
  `phy_servers_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `phy_servers_updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`phy_servers_id`) USING BTREE,
  UNIQUE INDEX `phy_servers_serial_number`(`phy_servers_serial_number`) USING BTREE,
  UNIQUE INDEX `phy_servers_bmc_ip`(`phy_servers_bmc_ip`) USING BTREE,
  UNIQUE INDEX `phy_servers_serial_number_2`(`phy_servers_serial_number`) USING BTREE,
  UNIQUE INDEX `phy_servers_bmc_ip_2`(`phy_servers_bmc_ip`) USING BTREE,
  INDEX `idx_phy_servers_room_cabinet`(`phy_servers_room`, `phy_servers_cabinet`) USING BTREE,
  INDEX `idx_phy_servers_serial_number`(`phy_servers_serial_number`) USING BTREE,
  INDEX `idx_phy_servers_bmc_ip`(`phy_servers_bmc_ip`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '主表：物理服务器基本信息表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of phy_servers
-- ----------------------------

-- ----------------------------
-- Table structure for phy_servers_images
-- ----------------------------
DROP TABLE IF EXISTS `phy_servers_images`;
CREATE TABLE `phy_servers_images`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `server_id` int(11) NOT NULL COMMENT '关联的服务器ID',
  `image_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '图片文件名',
  `image_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '图片类型',
  `image_data` longblob NOT NULL COMMENT '图片二进制数据',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_server_id`(`server_id`) USING BTREE,
  CONSTRAINT `phy_servers_images_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `phy_server_info` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '物理服务器图片信息表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of phy_servers_images
-- ----------------------------
INSERT INTO `phy_servers_images` VALUES (1, 14, 'pasted-image-1765969436490.png', 'image/png', 0x89504E470D0A1A0A0000000D49484452000002C40000019208020000006B0840A300001000494441547801ECDD0B7C54E599C7F14310B9A88098845C908B565B5B04DDB249D0566BADAD46D3B8C15DBB6BABA66989B52105B5588C8D298A285EB084B586369B5EB46A2B148AA6ADD5B555914944111AB4EB8D6B1224918B72095E70FFEF3993C94C2E2464923367667EFD1C2667CEBCE77DDFF37D7DDF79CEFB9EA4099FF03F0410400001041040200C81048BFF2180000208208040140878B78A0413DE6D1B6A860002082080405408104C44453351490410400001B70428E7C80508268EDC8C3310400001041040204880602208835D0410400001B70428279604082662A935B916041040000104222040301101748A44000104DC12A01C04DC102098704399321040000104108861018289186E5C2E0D0104DC12A01C04E25B806022BEDB9FAB470001041040206C018289B009C9000104DC12A01C0410F0A600C18437DB855A218000020820103502041351D354541401B7042807010410383201828923F3223502082080000208B41320986807C25B04DC12A01C041040205604082662A525A3EA3A3E5C59B6BF624D9755AE5F31BBA064597DEBE7E66DB9CFB27C8B0AF317D5B61EB57FD694E717988FEC3726C1ECA58D9655BB30E8A0F351C3D2928535CEAE65E9ACA07C7C8BDACA32451414E6876E3A51C7ED9C1B97CD694BDC9A1D3F1140A0CF05B61F9C3BA3E5A54EB2555F0EE9A173563484A452F76FEBA44A6CF7DC90146604687F96A594F96D07D5D343C781A011C39F978691E061CA7FB48B1F66106BAB551789CC08662ECDAE86A94FC742BB3AD303C709263CD008F1578541692903D2C67475DDBEA5D55676515E7AFBCFB38A2B0AADCAA078A271D9F2BA49D3676459EAF9411DB5A666BD5557E10F08CA15855856E3967A6BFD920EB18839B1B0625D5B412AA26A6E765272F6BCCA0AB3634D2CACAC9895D996803D041070452065C0E81306A4755254DAB47955EA9ECE367D622729820EA54D2BCA5AB3D87F67A2AFFF765FCFFA8EB7BFB97586B29D37A5B6A4F5AD6525E6CCAD700A9A979DA804219B4E5C526759CD2B4B43630E33ECB4DDDEF84F51E2D2EAA6CE123B818E891BCC8985CBD2CB4C89F373D3ACDA47ABAD9C6919FE1CA2E107C14434B4520CD5D1CC49CC283BD8600DB0D61C9831E3C0CAEDED2FAE7EC532AB60C1B4D4D6C900CB4A4F4D55220D0405F617FFBAD678A2E6B195A9051DBFE97D35DB73A667274D2E30DDB252A1864E4ECD2AD6005430695D8D1D5BE888BD29074B71C3BC8E818BFD312F0820E0BE809993D83FF7F14FAC146B4DD97E0D179A6D0CAE858682E09840C3C3D21273436F7F1FE71754AE77BEB6FD61416ADEA5292B9786CE6806E716B4AF78A2CA7C915B567DAD6F474A5A87FB19276D838A2BADB6921561989B0D7B9CB1C30E3BB2B16F6F9C84F6AB89246AB35AE39240E2C2C99635D90C744A64CAADAC080E591A96AEF05F85FFA24CC8E2441E4AEFCD8D60C29BED1275B5EA698507E5940D2BBFC65ABBE1D05AEBA8F2F2A13929A167D62E2CDD92579C6129A468CCCEB316DB63844687BA8AE563CD6C81733BA2045ACBD011ED686499F3D8E6B65C6A57AF4B49CBCCCDB32AB53C610EAB33FB3BA49D8FF6754BA188C4D9D9515DA21D6D1A9E949576740FE11CD48E33C3E11F954C66FC4300817E1648195C5A3EECBBD6A1BA0D87AC6B8695970D363713416566CED00CA5D3BB7D357549E9273A5FC6ADDFD305939C4985F9B95B1699EFE07C7F7F2F373712ED3ABEE9E34139FB771B972DAE6E9A9C99E57F1BF243914449758A262C17CC9F573577EC328D187356F8145E68472352E844A612E76B40ABCCDD5C6A97EECF4933A985661242C397FF48BB1FF6B44448FCA18BB252C7B48368775684DF124C44B801E2B1F897967F3CFAAA4167AEF9A8E3B4446085423D707E6E967F3EB32C273931A748537F415A4AE97CE52FD99E5374D9B8C0276B6A1BB32FD32890555C602DB71753D3731738218879559F9C58A81B88E4C4249DE29FBDB0EF2AD4B13367683C32F707CE71AD77D8CB1CFE9B15A56743000157043EF8E35F065C5D92B0F68183EDA625ECD2B3A665372ED1D773634363625656AAEE3D667716F19B554BD3EB2B340D90A461C1EEE0EAE3219B3D15A17950FBBEA5440B22BE45659AF2ACD2806097A597B4AC8C24270A99B3C232839233DF6935F86A9BF4F18EEA8AEA66FDB45253D3CC0FFF3F4512256B32E699C9D18C59D32D2DBC2A00D2C1FC82C5565185265FFDE9ECC556955E52DDDC545DA61D4DAE343AEBBCBA11B2AFCB4C544CEE6416B635074FFC2498F04433F4B412B1906EFBC127B60FFCFA944139970C7872F907EDAEC8E9EDD327B69F2AB49A7D8B836732CB7D764A8D1193A6CFCB4B4FCD9BAF574B63C7CCA2A2D65E9A31CB1E269C12EC3EACDB949AA9A66FEB58C64C8514CE00515038DB3CB6A983DAEC7B820E4B95CAB9355BA561430081FE14687CFCE377BE36F0F329832F4EF9F88F9D3DA99D9E3B337B7BC59CC5FEC588F4DCBCD4EAFBDA7AB1BF6EA6D76BC6D152CCA1DBFAAD0B3579D0E93667C5D862DD51E84EC39CA8CE1E1C499843811B92F919BE391A46FCDB7D5651485C9259638702F6A74ED8111882345E4D9FB87E49A17D8A19AC4CB6FE7F1ABE54BA1DF168E924590BAF15ED461B33F51214DCF8CFF3D80F82098F3548EC57477398CEBCE59421E5D71CDDC9F5D62EACC9749E8468BD5D285BB9C36AB214E39B2E67F75E7367A091A2C2F247EBAD290B4B4A9DD0DEEECF661CB17F77A3C0E9C33ADD9C68CAD43D84BAB77DD752555990AA1B027307A0E9C74ACB44272649C83FDD2298614835E9722535243D6F1040A0D702A9970C2DBD64904EFFFC35C30AA7E867C72D6D5AEEA41DCD81C508CD5558D58F99858CA0A46646A1B1B1C11C491C37266396BFBF5754E946C2997D748E04BEF24D4ACBCC7398CE6E8F21413B9A57B02CE78B5F73A52669EB44426B4AADA798C3F683146D79D6FA8318FBD376A7D8799A7354E8EAC6446B4A51D5A55B141569706BFBD512B3FCAA69183B99875F08263A691C0E454EC07C9DAFF74F18945BE676411180FD6892BEFE4DB5D433CD54A465690AA1D97252DA41C3A4E926A51D6AD83B1A2F4C7ACB1A33562B1A217D58BDDA39D13F5254AE574A93BF19299C3846072CDD8E684DD4041996D977C61DFFC486F99C7F08201061017FAC607AA8E62A56077EFDDBA9567A6AEA8E5A5FFDD6CD3B740FA0B125E85B3F680468FB46F79F95BB40A347B29921F08F277A6B4D9C1AF25B5D21BFEB91946DFF16868608B336EAE4D2FA7B9EE681503BBCD0A7A19B2656FD49B5D2B1D49AEA444D9933164CDBFA6875F3A44B5B17763367144EAEABB047B9D6F45EFC4930E1C55689DF3A9987A8ADD69ED93A8B6059FE3B0C13A107660E82EE338226003545113434D42C2C285966053F33A1384393998949C956FBE0C39F898295D61147A146697593F36486F69DCD892DE2B785B872043C21E05B54B97E724161D0EA86D602DAEE04FC753C715C72F3665F6363F2D8B1F611D3EBA74FB4342D61BEF5F51DEF9F63B03F745E1473985554E55CE27C7F6BD859B23D676EDB7064A76BFBA5D0A0671D0AF33562D81FEBC52C9798E841038EDE1D6E6B58BAD8979EE1D450E9CC1312C9D99707C52E59C50593D6B53E51AE149EDC22184C78D2834A4556409301951566BDD05E56680B0BD233B2ACEA92255661C787A5F51D6F2F79DAE388591CD5C2A43951AB18C5EA8EBA2309BE24C50A958DD9450BE6978D5B1EFCA844204D508CA28140234EF00D8A8EB4CD5E064E61070104DC15A829AF5837B1B038A3D3D58DA0AAA4A6A55A8D6B6A9BCCBC63D0E12E77152294AD4CCDB49FE0AE9897BEC23C0361869D764F39E8FCEE672694A887DB96FA94BC69ADBFA951BFE2BE6AABFDF3E656C6E5D989EB9D27CA7B98A9EBC908265C27A7C0C30BE83E40F1C1626BA61337D851457E4199CF4AB4AC3A7B1A530141C9B23F95AB9FDB4F335554395FF0E6C432DF94829CE4D602EA1BDB9E04F7E7A3890D3B58B1CC8A465EBDFD808573FFD17A123F1140C0D302EACB81A982F4DCBCC9CEB0D07995353D3053CB075AD408F905F2CE129BE14273151555C519E6918582424D394CCACE4EB2CC5FC033F727212729ECF04F612A59DB2A6AD0CC4450729383C6AB765BE0CFE56515074D7B981BAA8EB18BA57917FF401794AFBBBBDD944630D10D101FBB2AA080C0FE5D6DD36DB4DF1A5568E5D2FC56F774F33874BED62027E7E65D34C31CB4C379A7E7E7D764DA4732B2A6246A72C2F4DBD26A2BFBB2B1CEAF8097FAFF6E8C3D81E1BF260D343AC57FFF5150EE734A54A1814D4343BB650EF351C9B2C09FFAF6E7C40F04107049C0B7B43A35E82969F562BB53EB1EC3F976D7F247AEF367E8B4E8A971405FF666E5747EEB2F909B39CB0CFB29A8CCD505652B77248E1B63D7DC7C8BCFC8B20701FB573AB5245A316B9AB3486A26329555D09FDF6D9B99D018D2B6692ED3CE2CF4C58E5134AF19BA053D33119A5CEFEC6A9845939ECEA9E89C086F0413116E008A0F11503F77A6197454FBEA7B81B781233AE87FBE4187CC6662F6A083FEB73A62AF98B4BEED24D837275B76C86F12CFC8724A34FB661C691B20DA1FE9322B27435E1140A0FF045AA3877625042D50B68E0F4AE9F462B3726A4F46DA6147E044E794D0EEEC0C02C1C38E496E26324D56FE9CF536F42C93C6FEE74424F66EEB8B4A099A78683DAA9FAA5E707D3452997AEA036D4E3534F2F84BD421AF6F04135E6F21EA87000208208080C70508263CDE40540F01041040A08F05C8AECF050826FA9C940C114000010410882F018289F86A6FAE16010410704B8072E2488060228E1A9B4B450001041040A03F040826FA43953C11400001B7042807010F08104C78A011A802020820800002D12C403011CDAD47DD1140C02D01CA410081C308104C1C06878F10400001041040A07B018289EE8D488100026E09500E020844A500C14454361B95460001041040C03B020413DE690B4FD7E4D0A14F3C5D3F2A7744022446000104FA5420E1AD4D8D6C08742BB0ADB1E99DA65DFBF6B784FF9FDFFE03077734EFD9B8E59D6E0B250102084491803AB5BAB63A38A34414B55A5F5535E1E4F1A96C08742B30263571C890C1EFEE7CEF40CBC170460A0D34EF34ED1E3C78D0B83149DD161AED09A83F027125A04EADAEAD0EAE6ECE2811574DAF8B6599239CFFE6E3E8DC84848411C70D1B31E2D8F7DEDF1FCE65EFDDD732EAF8E39495320C271FCE450001AF09A853AB6BAB83AB9B8753379DAE4C9495320C271FCE75538060C24DEDA82FEBB86386A89F8773195A285126E1E4D0E15C0E2080808704D4C1D5CDC3A9904E5726E1E4C0B9EE0B104CB86F1EC525867FA370E8D0A1F033896241AA8E40AC0BA883AB9B8773953A5D99849303E7BA2F4030E1BE79749648AD114000010410E8428060A20B180E2380000208208040CF0408267AE6E4562ACA41000104104020EA040826A2AEC9A830020820800002DE1288CF60C25B6D406D10400001047A2DF0F4FF3EF3E0438F7CF249E77FA5F7ED8D9BEEFFD9929616FF1FDCDBBC65EBCF1EF8F9EEDDBB3B2DAEFA4F7FD1D6E9471C3CBC00C1C4E17DF8342202B50B0B0AF30FBF2DAA0DD4CCB7A8303FF4EDECA58D56FD8AD90525CBEA03A9D8410081D814F8EC69A7D56DD8F0C61B6F767A791F7DF8E1DEBD7B9D5043AF7FFDEBD36FBCF9D66F1FFE9D228C5F3FF8DB1D3B9A82CFDA6FFFCF39F2D8D23F04461FB9DF150000100049444154A16BAE2D7EFDF5379CE3BC762AD09FC144A7057210811E0924E6CCADA8AAEC629B3EB12D8FFA15CBD64D2C2CCE683BE2ECA5E72E989BE12B259E70387845207604347930EBBAD9816DC15DF7EEDEBDE7BF7F561138A21DA5E978C1AB56ADDEB87153CEC5179D7EFAE78E3DEED80D1B5E3DF4C9A18EC902472ECEBE50A3D0FD8BEF3B69C2F8C0C18E3B8B16DFEF841DB3AEBFF1DD9D3B9D04070E1CF8C9ADB73BC715973807835F755009942CF860F4EE134C446FDBC576CD9B57B6C6010D4B4B82261E346951B26C5BDBB5FB96D666CD9D9165E621CA7DF6E1B1E98996B575D99CC2FCD2EA26AB2D1FFB435E104020EA05CEFBD23937DFFC236D3F28FEFE35D77C573BF36E2B2BFDF14D7AAB4D6FB5298DBEA7F56D3DFFCEBB5FFBE7FFE96BFED1DF2D5DFA8715975C7CD1A4C9A7A7A6A6FEDF3F5FBFE8C2AF0E1D3254D3155A0459785FB94290A7FFF76FDAB45359F5AB1E322992D8B56BB7020E851D679F95B5F8BF1F50B9DA16DCBDF0F8E347EAE0DD77CD5FF5826FB5AF263843CD733CF5F433C14722BF1F5E0D0826C2F3E3ECFE1248CC999EE15BBCA2C16AF4AD699E64D5F8E3899A9AF5C9195963FCA52ACE58965E94976EF99656A74ECF5C6DAF8C9454373755576EBE3430AB314F09FC27F00301040E2BB0A3A9B9ABCFB76E6BE8EA23978F0F1D3AF48451A3061D35E8370F3DFCD24B2F8F183E5C6F8F1F3972DDFA7FDCFFC0926DDBEAF55669B4DDF2E39BE6DC78C3699FF9F43D77DF3161FCB843870E3DB7EA85DF3CF8F04F172D4E4A4EAA7DF1A5F2C53F53243178F0E06FE75F7573C98D274D18AF8040B1C8E5FF7E594F2E4AF3101B376EFEEA05E7AB2CA53FEFBC7395DBD6ADDBB4EDDEB5FBC2AF5EA083AACC8409E35E5CF392F69D4DA1C6F2152B5596F336365E092662A31D63F12AC6E4CE9C527BDFA2C77C56F6E5C5330AADCA853596AFA62E694A469AFF724D9CD1545DA689C465E965B332336639CB225A04995C302BD39F881F0820D0738175EB5F7B65DD868EE96B5E5CBBADBEB1E3F1481DD9BA6DDB1D0BEE1E939EF61FFF3EEDA8A38E523512121272BF7EC9D5577DABEA97BF5EF978B5E61B7430B0250C18A0A58DF4B4D44BBF7EC9B7BEF99F8989897AD5FED1470F529A0103068C18315C39343535EF3F7040D1C9B1C71EA3E3DD6E0A1416DE73E7D42CFF70F36EF3BB2D07CC939EA79E7ACAC27B17E855393801C7BF4EF9BCF62DCBD2EB13D57F3EE9A409DAB41F331BC144CC34650C5E48DAB4DCD47575961D3D641517584B0A2BD64DCC9B96DA7AA9A979F335FD509633397B66DB41FBC3C615B3ED590AC519DACCF398F6615E1040E0F002179CFFC55DBBF7F86A5F0E4EB66AF58BFBF71F989AD9F67518FCA9FBFB2FAF7D65C15D0BBF7CDE973EF7D9CFFEF6E1479DB841AFDA5FBBF69559338BFFFEECF37FFAF393818A7DF4D1472FBDB476FBF67702473ADDD1C4C6DE7DFBD6AFAF5BF1C7C7955BA7690E7FF0CF4FFE75C249E39D1842293503A175961B7E384733136D01C7CE9D1B5E7D4D73184A104B1BC1442CB566CC5D4B7DA36E859AD6D4DAB3AB1953275BD6E4CCACD0AB6C58FA98352DD7CC5598C726ECDF01595267ED684E9DAE38C3BF2D68176A84E6C03B0410081638EFDCB30E1C6879F679FF1AFFDF9FF37DF0C1873A189C26B2FB679E31F9AE3BE77DE5FCF3F4CDFDF6DB1BDF7CEB6DD5A7A1B171C386D7CE3E6BEAB8B12796959668E9E1E0C1837F7DEAE95FFEFAC18D9B36D7BEB8E6E343FE672DB5D8A1890A6D3A2BB029F12BEBFEF1A9934FD214C2EB6FBCB96AD56A65386AD4F18104DDEE2C5A7CBF963CAEF8AF6F04526AED43EB2C55951569A9A98A2A145BE8A3877EFB88EA76C2A851DA8FA58D6022965A33C6AEA571D9E26A2BBB20C7AA7E54C39AF9AD8DC4A4C64A2D76B45D674D794975DDCA521343CCF6652CA874A287824956E238FBB98A86A525FE872DDACE610F0104BA1150E8F0F1C71F3FF3F717B4E9AB576FBB39C1DD8FB52A3164C81095999498F895F3BFFCD8D23FBCF3CE8EAAAADF9C79E619E3C68DD5712D5268ED43B30B8A8A145E9C7CF2495AFE58B9F289C6C6ED3F7BE0E7A5B7DCAAF0A2B4EC56EDEBC80315BF686969F1D5D40E1932383D3DED9863867DFBEA2B53D3525A5A0E8E1C3952B9056F5AB39875FD8D9AEFD416880F94C089246E2EB9B1D32861D2E91377EFDABD75EB36E731CCC02C854E8C998D6022669A32E62EC4F7D8CA1D5AD4C8C82BCA6E5C5E32BBB4366BEEBC05974E5CBF7C853D5161AED757536759130BED182268FA417318CD9BB7352E9B5358B226635EC7DF1A35A7F20F01040E27100820023B9DA4F6C0A1B3CF9EAAE9849B6EBE65D4A8E32FCDBD447146A0520A38BE9E73F1299F3A59931043870E993573C6C27B17DC3EEF2763C79E989232FADC73CEB9F79E3B7544C7F7EDDBF7ECB3CF5FF8D50B060E1CA8D3478F4E1E317CC4FBEFBF7FC20927E86D607BFDF5378E3DE69885F7DCA9C9066D9A75183A74A8E61B1455ECDAB5FBF6DBCA4E689D6F50D0A098439147E05C67E7C5352FAD5DBB4E8188B627AAFFBC69D3E69B6E2EEB98CC491C5DAF0413D1D55EF153DBE695D57593A6CF308B1AE9B979A95AB6B07F29237346616AB599A8B025B28A351561D298BF5B35A72DC8C8CA9CB87E49996F4A59D57C7B05C44ECC0B02081C9180C2086D47748ACB8935F7F0CABAF535352F262626BEFADA3F9F7BFE05CDA31CA60E1F7DF4D1238FFE7EE4C811B36F98F5C69B6F3EFBDCF3CA41E9478D1A75D595DF1C3F7E9CF69DAD6EC306050A29A3939DB7CEEBDF9E7D6EF3E62DCE7EE0F5E79555DA57864AAF1D673B63F22495F2CC337FD75B451B0F3FFA7BADC89C7AEA29C545D72A0A71B68BB32F5489C121881247EF463011BD6D17DB35377FB42AF01B190A1A3ADDB72C33FDA0187F756685133798A8A2A0307FC9F6A464CB79D882958ED8FE0F251EAE8E6BEC28A0B984BF3EF5F40DB3E73CFCF0EFAEB8E21B77DC3E7746D1F79E7EFA991FCCBAE177BF5FDAD0D0D831AAD8B1A3E98E3BEFD9B76FFFD5577E4BEB17F9577FEBCF7FF9EBC38FFCFEC08116CD6768BA22508A32575C72CE17CF1E3C7870E0E02796D5314FCD55BCFAEA3F35C1706DD14C0D44CEA66909051645DFBF66D50B3E1DD147C71F3F52614420AB98DC219888C9668DD18B0A7AC43229FD443B9228DB6CFF3D895999415145A5A62BE62D985F96635597141496545B39D332625484CB42201E057EF9AB07AFBBE1C6BABA57BF53907FE71DB79D79C66445039F3EF5945BE7965E7FDD0FB46A30FFCEBB1F7CE8617DF7AF7CBC5AFB5AEF786175CDADF3EED09AC8B5DF9B3E74A879DE2265F4E89B4B7EB477DFDEF2C5F71F3C7830D8F185D53EAD746465FAC78DA38F3E5AC1C79D0BEED9BA759BD64782536AB2E181FB1739330D8157E791082D790416443A8D242E9BF66FCE42497086D1BB4F3011BD6D17C335CF9855692F6AB4BBC4F4DCD6472C2BEC2724CCAF86B6CE58987D75E6D6B73AD37FA4AAD3ACF4391B022102BC891A812BBFF55F0FDC5F3E6BE68CD33EF3698511817A6B7FFCB871DF2BFC6EF94FEFB9F25B57242424E45C92AD61A178C6B55F3EEFDC9F2EBCEBBC2F9DA38381F4C70C1B36FD3BDF9EFDC3EB0233105A7AD0F695F3BFFC9D6F5F7D94FDE72B9478E0C081D3BFFBEDFFF9C503F36E2D1B3E7CB88EB075142098E868C2110410400001EF0A282050DC7044F5537A9DD5ED2943EDFFF53071B7B9C555028289B86A6E2E1681480B503E0208C4A200C1442CB62AD7840002082080808B0204132E625314026E09500E020820E0A600C1849BDA94850002082080400C0A104CC460A3F6DF251D6AFDE3F6BD2E22212121FC4C7A5D7A5F9F487E0820D05E401D5CDDBCFDD12379AFD395C9919C41DAC80B104C44BE0DA2A806EFEF6B39F618F32BDABDAEF331C38628935E9FCE890820E07101757075F3702AA9D39549383970AEFB020413EE9B476589BA51D8F3FEFE3D7BF60E3F6E583817A05864E7AEF7959532EC693EA4430081681050A756D7560757370FA7BE3A5D99282B65184E3E9CEBA640C25B9B1AD910E856605B63734BCBC113460D1F3AA4EDEFCBF6E2BFD46143078F4E1A79F0E0879BB735755B28091040208A04D4A9D5B5D5C1D5CD7B3138044ED1E9CA445929C328BAFC38AF6AC2C9E353D91038797CEAE111C6A6278F4E3A5ED38F810EDFEB1D8D14C98923268C1D7DF812F9140104A24B409D5A5D5B1DBCD78343E04465A2AC94617409C4736D59E608FCD7CB0E02082080000208F4468060A2376A619CC3A9082080000208C49A00C144ACB528D783000208208080CB02311A4CB8AC4871082080000208C4B100C1441C373E978E0002082080405F0884154CF44505C80301041040000104A25B806022BADB8FDA238000020820D013817E4D4330D1AFBC648E000208208040EC0B104CC47E1B738508208000026E09C46939041371DAF05C360208208000027D254030D15792E48300020820E09600E5784C8060C2630D42751040000104108836018289686B31EA8B000208B825403908F4508060A28750244300010410400081CE0508263A77E128020820E09600E52010F502041351DF845C000208208000029115209888AC3FA52380805B0294830002FD264030D16FB4648C0002082080407C08104CC4473B739508B82540390820108702041371D8E85C320208208000027D294030D1979AE485805B02948300020878488060C2438D41551040000104108846018289686C35EAEC9600E52080000208F4408060A20748244100010410400081AE050826BAB6E113B70428070104104020AA050826A2BAF9A83C02082080000291172098887C1BB85503CA410001041040A05F040826FA85954C114000010410881F018289BE6E6BF243000104104020CE040826E2ACC1B95C041040000104FA5A205A8389BE76203F04104000010410E8A500C1442FE1380D010410400001041C81C307134E1A5E1140000104104000812E050826BAA4E103041040000104A247209235259888A43E65238000020820100302041331D0885C0202082080805B0294D39900C144672A1C43000104104000811E0B24D46FDFC986000208208080A704A84C740924A4A78C624300010410400001047A2DC032478F27714888000208C49A00D78340DF08104CF48D23B920800002082010B702041371DBF45C380208B825403908C4BA00C144ACB730D7870002082080403F0B104CF43330D92380805B02948300029112209888943CE5228000020820102302041331D2905C06026E09500E020820D05E8060A2BD08EF11400001041040E0880408268E888BC408B825403908208040F408104C444F5B51530410400001043C294030E1C966A1526E09500E0208208040F8020413E11B9203020820800002712D403011D7CDEFD6C5530E02082080402C0B104CC472EB726D082080000208B82090105DFF8FE9D4F67002DB77F229020820800002EE0B24A4A78C624300010410400001047A2DC032C7114FFF700202082080000208040B104C046BB08F00020820800002472CE0D960E288AF84131040000104104020220204131161A7500410400001046246C02298889DB6E44A104000010410888800C14444D829140104104000812314F0707282090F370E55430001041040201A040826A2A195A82302082080805B0294D30B0182895EA0710A02082080000208B409104CB459B08700020820E09600E5C4949613A975000010004944415400C1444C35271783000208208080FB020413EE9B5322020820E09600E520E08A00C1842BCC1482000208208040EC0A104CC46EDB72650820E09600E52010E702041371FE1F00978F000208208040B8020413E10A723E0208B82540390820E0510182098F360CD5420001041040205A040826A2A5A5A827026E09500E020820708402041347084672041040000104100815209808F5E01D026E09500E020820103302041331D3945C0802082080000291112098888C3BA5BA2540390820800002FD2E4030D1EFC414800002082080406C0B104CC476FBBA757594830002082010C702041371DCF891BBF40F5796EDAF58D365F9F52B6617942CAB6FFDDCBC2DF759966F5161FEA2DAD6A3F6CF9AF2FC02F391FDC62498BDB4D1B26A17061D743E6A585AB2B0C6D9B52C9D15948F6F515B59A68882C2FCD04D27EAB89D73E3B2396D895BB3E3270208F4B9C0F6837367B4BCD449B6EACB213D74CE8A869054EAFE6D9D5489ED9E1B92C28C00EDCFB29432BFEDA07A7AE838103462F8F3D230123C4CF98F76F1C30C626DB5EA229119C1CCA5D9D530F5E9586857677AE038C184071AA1C75588998483D25206A48DE9EA727C4BABADECA2BCF4F69F671557145A9541F144E3B2E57593A6CFC8B2D4F3833A6A4DCD7AABAEC21F1038A146E3967A6BFD920EB18839B1B0625D5B412AA26A6E765272F6BCCA0AB3634D2CACAC9895D996803D041070452065C0E81306A4755254DAB47955EA9ECE367D622729820EA54D2BCA5AB3D87F67A2AFFF765FCFFA8EB7BFB97586B29D37A5B6A4F5AD6525E6CCAD700A9A979DA804219B4E5C526759CD2B4B43630E33EC38634E5072252EAD6EEA2CB113E898B8C19C58B82CBDCC94383F37CDAA7DB4DACA999611948BD7770926BCDE4231563F332731A3EC608335C05A7360C68C032BB7B7BFC0FA15CBAC8205D3525B27032C2B3D355589341014D85FFCEB5AE3899AC756A61674FCA6F7D56CCF999E9D34B9C074CB4A851A3A3935AB580350C1A475359AE1D07BFFA61C2CC50DF33A062EFE04FC400001B705CC9CC4FEB98F7F62A5586BCAF66BB8D06C63701D341404C7041A1E9696981B7AFBFB38BFA072BDF3B5ED0F0B52F32E4D59B93474463338B7A07DC51355E68BDCB2EA6B7D3B52D23ADCCF38691B545C69B595AC08C3DC6CD8E38C1D76D8918D7D7BE324B45F4D24519BD51A970412174EB6ACC966A05322536E654570C8D2B07485FF2AFC1765421627F2507A6F6E04131DDB8523FD283028A76C58F935D6DA0D87D65A4795970FCD49092DAC7661E996BCE20C4B214563769EB5D81E23343AD4552C1F6B660B9CDB1125D05A868E684723CB9CC736B7E552BB7A5D4A5A666E9E55A9E50973589DD9DF21ED7CB4AF5B0A4524CECE8EEA12ED68D3F0A4ACB4A37B08E7A0769C190EFFA86432E31F0208F4B340CAE0D2F261DFB50ED56D38645D33ACBC6CB0B999082A33738666289DDEEDABA94B4A3FD1F9326EFD9E2E98E44C2ACCCFDDB2C87C07E7FBFB7BB9B99168D7F14D1F0FCAD9BFDBB86C7175D3E4CC2CFFDB901F8A244AAA533461B960FEBCAAB9639769C498B3C2A7F0423B1A9142273295385F035A65EEE652BB747F4E9A492D3493101ABEFC47DAFDB0A72542E20F5D94953AA61D44BBB322FC966022C20D108FC5BFB4FCE3D1570D3A73CD471DA725022B14EA81F373B3FCF3996539C98939459AFA0BD2524AE72B7FC9F69CA2CBC6053E5953DB987D994681ACE2026BB9BD989A9EBBC00941CCABFAE4C442DD40242726E914FFEC857D57A18E9D3943E391B93F708E6BBDC35EE6F0DFAC283D1B0208B822F0C11FFF32E0EA9284B50F1C6C372D61979E352DBB7189BE9E1B1B1A13B3B25275EF31BBB388DFAC5A9A5E5FA16980240D0B7607571F0FD9ECA908CD83DAF72D255A10F12D2AD39467950604BB2CBDA46565243951C89C159619949CF94EABC157DBA48F7754575437EBA7959A9A667EF8FF299228599331CF4C8E66CC9A6E69E15501900EE6172CB68A2A34F9EA4F672FB6AAF492EAE6A6EA32ED6872A5D159E7D58D907D5D66A2627227B3B0AD3978E267E482094F5C3E95705F60FBC127B60FFCFA944139970C7872F907EDCA777AFBF489EDA70AAD66DFE2E099CC729F9D5263C4A4E9F3F2D253F3E6EBD5D2D831B3A8A8B59766CCB28709A704BB0FEB36A566AAE9DB3A963153218533401414CE368F6DEAA036FB9EA0C352A5726ECD5669D81040A03F051A1FFFF89DAF0DFC7CCAE08B533EFE63674F6AA7E7CECCDE5E3167B17F31223D372FB5FABEB65EECAF9BE9F59A71B41473E8B67EEB424D1E74BACD5931B6587714BAD33027AAB3074712E650E086647E866F8E8611FF769F5514129764D6D8A180FDA913760486208D57D327AE5F52689F62062B93ADFF9F862F956E473C5A3A49D6C26B45BBD1C64CBD040537FEF33CF68360C2630D12FBD5D11CA6336F396548F935477772BDB50B6B329D27215A6F17CA56EEB09A2CC5F8A6CBD9BDD7DC1968A4A8B0FCD17A6BCAC2925227B4B7FBB31947ECDFDD2870FAB04E37279A32750FA1EE6DDFB5545516A4EA86C0DC0168FAB1D232D1894912F24FB7086618524DBA5C490D49CF1B0410E8B540EA25434B2F19A4D33F7FCDB0C229FAD9714B9B963B694773603142731556F56366212328A99951686C6C304712C78DC998E5EFEF1555BA9170661F9D2381AF7C93D232F31CA6B3DB6348D08EE6152CCBF9E2D75CA949DA3A91D09A52EB29E6B0FD20455B9EB5FE20C6FEB4DD29769EE61C15BABA31D19A525475E91645451ADCDA7EB5C42CBF6A1AC64E16C197EE8A2698E84E88CF5D15305FE7EBFD1306E596B95D5004603F9AA4AF7F5313F54C331569599A4268B69C9476D03069BA4969871AF68EC60B93DEB2C68CD58A46481F56AF764EF48F1495EB95D2E46F460A278ED1014BB7235A1335418665F69D71C73FB1613EE71F02084458C01F2B981EAAB98AD5815FFF76AA959E9ABAA3D657BF75F30EDD03686C09FAD60F1A01DABED1FD67E52ED0E8916C6608FCE389DE5A13A786FC5657C8EF7A2465DBBF85A121C2AC8D3AB9B4FE9EA77920D40E2FF469E8A689557F52AD742CB5A63A5153E68C05D3B63E5ADD3CE9D2D685DDCC198593EB2AEC51AE35BD177F124C78B155E2B74EE6216AABB567B6CE225896FF0EC344E8819983A0FB8CA009404D51040D0D350B0B4A9659C1CF4C28CED064666252B2D53EF8F067A260A575C451A8515ADDE43C99A17D6773628BF86D21AE1C014F08F81655AE9F5C5018B4BAA1B580B63B017F1D4F1C97DCBCD9D7D8983C76AC7DC4F4FAE9132D4D4B986F7D7DC7FBE718EC0F9D17C51C661555399738DFDF1A76966CCF99DB361CD9E9DA7E2934E85987C27C8D18F6C77A31CB25267AD080A37787DB1A962EF6A5673835543AF3844472F6E541B14B5671C1A475AD4F942B85273782094F364BDC564A9301951566BDD05E56680B0BD233B2ACEA92255661C787A5F51D6F2F79DAE388591CD5C2A43951AB18C5EA8EBA2309D654AC50D9985DB4607ED9B8E5C18F4A04D204C5281A0834E204DFA0E848DBEC65E014761040C05D819AF28A75130B8B333A5DDD08AA4A6A5AAAD5B8A6B6C9CC3B061DEE72572142D9CAD44CFB09EE8A79E92BCC331066D869F79483CEEF7E6642897AB86DA94FC99BD6FA9B1AF52BEEABB6DA3F6F6E655C9E9DB8DE79A2BC8799BA9E8C60C275720A3CBC80EE03141F2CB6663A71831D55E41794F9AC44CBAAB3A7311510942CFB53B9FAB9FD34534595F3056F4E2CF34D29C8496E2DA0BEB1ED49707F3E9AD8B08315CBAC68E4D5DB0F5838F71FAD27F11301043C2DA0BE1C982A48CFCD9BEC0C0B9D5759D30333B57CA0458D905F20EF2CB1192E34575151559C611E592828D494C3A4ECEC24CBFC053C737F127292C20EFF14A692B5ADA206CD4C0425373968BC6AB705FE5C5E5671D0B487B9A1EA18BB589A77F10F7441F97A6A9760C253CD11F795514060FFAEB6E936DA6F8D2AB472697EAB7BBA791C3A5F6B909373F32E9A610EDAE1BCD3F3F36B32ED2319595312353961FA6D69B5957DD958E757C04BFD7F37C69EC0F03B6BA0D129FEFB8F82729F53A20A0D6C1A1ADA2D73988F4A9605FED4B73F277E2080804B02BEA5D5A9414F49AB17DB9D5AF718CEB7BB963F729D3F43A7454F8D03FAB2372BA7F35B7F81DCCC5966D84F4165AE2E285BB92371DC18BBE6E65B7C46963D08D8BFD2A925D18A59D39C45523391A9AC82FEFC6EDBCC84C690B64D73997666A12F768CA279CDD02DE89989D0E47A6757C32C9AF4744E45E744782398887003507C8880FAB933CDA0A3DA57DF0BBC0D1CD141FFF30D3A643613B3071DF4BFD5117BC5A4F56D27C1BE39D9B2437E9378469653A2D937E348DB00D1FE4897593919F28A000246A07FFEB5460FED720F5AA06C1D1F94D2E9C566E5D49E8CB4C38EC089CE29A1DDD9190482871D93DC4C649AACFC39EB6DE859268DFDCF8948ECDDD617951234F1D07A543F55BDE0FA68A4B2EBA94F2CCBA986461E7F89F6416FBF104C78BB7DA81D0208208000029E172098F07C135141041040A06B013E41C00B0204135E6805EA80000208208040140B104C4471E351750410704B80721040E07002041387D3E13304104000010410E8568060A25B22122080805B0294830002D1294030119DED46AD114000010410F08C00C184679A828A20E09600E5208000027D2B4030D1B79EE486000208208040DC09104CC45D9373C16E09500E020820102F020413F1D2D25C27020820800002FD244030D14FB0B196EDA1439F78F492A8160208208040A40512DEDAD4C88640B702DB1A9BDE69DAB56F7F4BF8FFC5EE3F707047F39E8D5BDEE9B650122080401409A853AB6BAB83334A4451ABF55555134E1E9FCA8640B702635213870C19FCEECEF70EB41C0C67A4D040F34ED3EEC183078D1B93D46DA124400081281250A756D756075737679488A286EB93AAB2CC11CE7FF371746E4242C288E3868D1871EC7BEFEF0FE7B2F7EE6B1975FC71CA4A1986930FE7228080D704D4A9D5B5D5C1D5CDC3A99B4E5726CA4A1986930FE7BA294030E1A676B765793DC171C70C513F0FA7965A285126E1E4C0B90820E06501757075F3706AA8D39549383970AEFB020413EE9B477189E1DF281C3A7428FC4CA25890AA2310EB02EAE0EAE6E15CA54E5726E1E4C0B9EE0BC46530E13E332522800002082010BB020413B1DBB65C19020820800002AE08F46330E14AFD290401041040000104222C403011E106A078041040000104222E1066050826C204E47404104000818809BCBD71D36F1FFEDD810387FB7B7A4F54FF79FDFABAE02A5656FDEAE5B5AFE8C8471F7DD4D2E23FB7A1A171E7CE5D3AE86C7BF7EE7B77E74E67DBB3E7BD4F3E69FF57807564E5E3D5CBFEB0E2D0A143CE29F1FC4A3011CFAD1F35D7EE5B54B8B0A6EBDAD694E7CF59D1D0FEF3DA850585F9662BF7B5FF88F708201023022FBFBC76EFDEBD43860CEEEA7AF6EDDBF78FBA0D1B376D5AFCDF0F68FFC081038A0F76EFDAADB861D7AEDD0A051E5BFA078505DA36BCFAEA5D772FAC6FF08F25FFF3CB5FDD72CBADB7DD7647D94F6EFB69F97F07628E4041AB56AD7EFC893FBDFEC69B0F54FCE2FE9F2D71B6552FF89455204D879D983D403011B34D1BD51776D8E8A171D99CE0D8A271D9F2ED3945B969BAE0FA15B34DF4E0C41035532B2BAACC36234B1FB1218040AC082C5A7CBF7D9F607ABA661D56FB6ABEFD9D6B02471C224B5E00001000494441549C1DA5712EF7B5D7FE6FF0E0A32FCEBE302525E591DF3DF6F4FFFE4DF1C1EB6FBCB17CF91F6FF9C96DB52FBE74DE79E70EB0FFF795F3BF7CE18517FCFC17557BF7EE73CEBDE28A6F2CBC77C18CEF7F6FC8E090604553117F79F2A9DF3CF4F0D9674F3DFBACACD34FFF9CB6E4E4A40DAFBEF6C9A143CACC39BDE3AB6AE5546FD6F5372AA6711228BEF9C9ADB73BC715D93807835F755009942CF8A0D7F60926BCD622D4C7086415978D5B5E98BFA8D6BC09FE67C28532DF94B25999FEA30D4B17FBA614E5A5DB6FD3731798E8A1A26A6E76927D80170410883D81E2A26BEDFB848AABAEBCE294533EF5C0FD8B9CB76DAF95154AA30BFFF8E38F9F5BB56AF2A4D38F3AEAA88B2EBCE0BC2F9D7BC9C517293EF8DCE73EFB8DCBFFFDB4D33E7DD6D4CCF4347327A2C40A02BE70F659B36F9875ECB1C7E89B5B21C5B1C71EABE3ED364D5DCCBFF3EE1756FBBE9E73F12BAFAC1B3468D0D9674DFDE8A38F9F5FF5C2E5FF7199C28B76E9036F1549682EE4FEC5F7A99E679F95A5991295A26DC1DD0B8F3F7EA40EDE7DD77C4D6C28360A9CA29DD75F7FE3A9A79FD18EC73782098F3750DC562F356F7E594E6365E8EA46EDC2D2DAACB9150BA6A5FA5DEA57DC579FEBBCF52DEA2CF8F0A7E3070208F4486047537357E9B66EF3CFFF7795C0E5E3EFBDF7DE337F7BF69C2F9EFDF6C64DCF3DBF2A78D311A7329A2AF8E73F5F1F3C7888BEC55B0E1E4C4E4A5ABA6CB9D623366DDAF278F59FFEF18FBA2D5BB7FDEAD70FEDD9F3DEBA75EB95434DED8B0A0B34F1A0F50BCD317CEAE493944FC2C081BBF7EC51ACA06F7A2D61249E70C265D3FEED961FDFA4D98E1F14173DF6D81FAE2D9AF9F813D5B36616AB328A48744AC74DF3101B376EFEEA05E70F1D3A549F6A3A44EB265BB76ED3A635970BBF7A810E9E306AD48409E35E5CF392F69D4DA1C6F2152B4F9A30DE79EBE55782092FB74E9CD74DF144456006C2B6C89855392FCF9984B0DFFB965637ADAB74A6072BAC82AAE20CFB302F0820D04B8175EB5F7B65DD868E27D7BCB8765B7D63BBE3117CAB2F752D340C1F3E3C2B3363CB96ADFFF8C706675BB3E6E5071F7AE4C9279FFAE8A38FF6EDDBA74510DDF4BFFFFE7B3F5DB4F8A692D257D6AD537CA025097D3D7FF8E18779FF76E9E7FFE50CCD4F24240C78F3ADB75F7AF995477FF798261E7EF3E0C3751B5E9D3EBDC0F9E257E2FFFCC67F0C1932E4D8638629CE183C78F0D813C7BCF6CFFFFB59C5CFEFBAEB5E1D3721C290A1B7CF5F70F73DF76919E59D7776A8F476380A1416DE73E7D42CFF9CEABBCDEFB6D80F8D9E7AEA299A29D1ABD23B01C7BF4EF9BCF69D4DF53FE9A409DA9CB75E7E2598F072EBC469DD96CD314BA14E88E0BC56ACB3D62F697F70F6D2C6AC62F354C4BCEC446BB222096B61E08189D2EA26ABAE22F0B6E372499CD272D908742370C1F95FDCB57B8FAFF6E5E074AB56BFB87FFF81A9996D5F72C19F46647FD5AAD57F7FF6F94BB22FD4FAC5D7BEFA956BBF375D5BDEBFE5EE79EFBDF3BE74CE770AAED6714D129C79C6E433264F1A316244D92D376B6963E0C08193269D7EF0E007F5F50DC533BEFF95F3CFFBE217CECEF8D729C71D77DCB4BC4BBFF5CDFF1C366CD8C084840BBEF2E531E9E9BFFFFD32CD61687BA0E217CF3DB7AAA161FBA73E75F2071F7C70DBBC3B8B67DEF0F023BF3B69C2845BE796DE76EB2D2A57AFF7DE7D4746C6BFFEA36EC3ED77DC75F7BD3FD5A4C26164FEFCE45F279C34DE8921944C897F72EBED37FC708E6626DA028E9D3B35B3A2390C25F0FE4630E1FD368ABB1AE6CD3721825610AB2ACB7292AD49D39DB70593AC8985CE2311F6ABB3BA61D5AF78D42AAA3273129AB770523ACF4C0425369FC61D23171C2B026E5FC779E79E75E040CBB3CFFB7F81EAEFCFF93EF8E0431D74BB1E5D97F7D7A79E7EE8E1471519285C08A4D2F7F1FFFCF2D73997647FE3F27F5724A1E38A0C2EFCDA05CEBEDE6A534854F1F3CADF3EFCE8FBEFBF7FDF7DE5B3AE9B5DF8BDA276CF2828D9B1C71EAB798B032D2D9AC370B6C14306BFF9D65BCA4A7315375CFF831BAE9FD9DCFCEE238FFEFEBA1B7EE4DCF0E8B5A8F8BAAA5FFE7AF3E62DA53F9EF3A3D9D72BA5B2EA745BB4F87E2D795CF15FDF087CAAC4B7FCF8260D7A69A9A98A2A742DFAE8A1DF3EA2390F4D6968DFFB1BC184F7DB289E6BB875F38EC47163BA16A85F31BBB47A7D75997AB2B6D0072CBA3E8B4F1040E0B0020A1D3EFEF8E367FEFE82364DECEBED6193BBFDE15B6F6FFCDE35DF4D4F4B6D6939B8D08E091416CC2929DDB871D32F7FF51BED6BD3F19616FF1F9008D46FF0D1479F3EF1735F3EEFDC2BEC5FD35878EF024D57043E0DEC0C1F7E5C6EEE257BF7EE3DE5539FD2D4C5BF9C79C63BDBDF39F79C2F68814369B4AE31C0B2B47A72F75DF3F5F5AFD7F1E3C7E9D5D91F397284D268D39AC5ACEB6FD4B8A42D101FE8B81349DC5C7263A751C2A4D327EEDEB57BEBD66D4E881398A5D0891EDF08263CDE40F15DBD9A9AF5C91959410F4904381A9696E42FAA6DF0D5365981A98B760F5804D2B283400F0448122A100820023BA19F47F2DD35D3BF73CAA74E560D3461F0EDFCAB6EBEF947DA7E78C3ACF1E3C65E75E537B5AF4DC79DEF7E250B6C038F1A78F65953150D3CF4D0230A38B46DD8F06AE05367E713CBDAB56B976608BE70F6593FAFACDAB973D7AF7EFDD0C89123B332431EC9DABF7FFF6F1E7C588B207A6D6E6ED6ABB3AFE34E3E8A1516DE73A7220C6D9A75D0DC83E61B1455ECDAB5FBF6DBCAF4A9934C4183620E451ECEDBC0EB8B6B5E5ABB769D02116D4F54FF79D3A6CD37DD5CD63159207DC477082622DE0454A02B81DA854BEA92A664F87F6DAB43AAA4F413D3A6CD53470D7D48B3433A0E208040AF04144668EBD5A92E9DA41982112386EB8B59DBA8E38F1F3468D0F0E38ED3BE361D0F5E04712A5457F76AD5AF7EA34997AE6626F49116171416EC3F70E0BC2F9D73F249137E78E34D9AA2B8EACA2BB4C6E164E2BC0E1932F4928B2FBAFCF2CBF47AFCC8E3F5EAEC0F1D32C449D0F155A1890ECEBE6196020BED38DB1993278D1C39E29967FEAEB78A361E7EF4F7CEB314C5ADBFFEAA21EEE2EC0B35F9111C8228B1D7368209AFB508F5B105B47E5150D9985DE67F30C23E16FCB2A5BEED17D8CC2C85F3AC250F5A061B79729F4A2110118177DF7D77DBB6FAADDBB65DF4B5AF0E1C38F0E9A7FFA68841DBA64D5B02F5D9B1A369EFDE7D13268CBFE1FA99FBF6EE55D8F1C2EA9ACF7CFAD4B7376ED2C4C3F6EDEF7CD2E12F6A07CEED76E7F5D7DF78F5D57F6A82E1DAA2999A6C70364D4B28B028FAFE35AB5EF0E9883ED2EA89C2886E73F3600282090F364ABC57C9FC3647E996BCCAA0BF2761484E1C97DCF60B1A15EB26E64DB34CCA82C2FBAC2205EF66E3414B03C53F04E24B4041C08F6F99FBA39B7EFCCE8E1DC3470CEFF4E2478C18917FD5B7CA4A4B5252462BC18927A69F6EFFD9CAE1C38FD35B6753AC50F0EDABAEBEF29B7F79F2A99FDC3AFFA881033519A0D5930577DC3674E8905BE7DD71D73DF71D3C78D049DCD272E0F127FEF4E8A38F3DF9E453CA53AFDAD791031D1ED470D29F7AEA291DFFB896F34884E651020B229D4612CE9FB5186AFF810A27370FBE124C78B051E2BD4AF66F7374FC1BD8E6CF4E9888C1FE558EAA4A25F01FE964F6223D77814910B7925C3802312EA06FD6D93FBC4EDFD0BACEA4A4C41BAE9B79D79DB7DF73D71D29A34DACA083814DDFC4FACED622C569A77D2621C17CE5E9C855577EF38B5F385B9BC20B7DEA24FEEC699FF9FCBF9CA9C511AD592C5E74AFD20C1F6E4213BD6A5F476EB8EE07CE7318279D34E127653F2E2EFADEB5F6AFA4065E67167FFFCEF9B7293870328CAB57231B5717CCC52280000208C49280BEFE478C187EF4D147F7D54529E6509EED72D3111D770E2A3419396244E0AD7330CE5F0926E2FC3F002E3F0C014E4500010410B00508266C065E10400001041040A0B7020413BD95E33CB704280701041040C0E30204131E6F20AA87000208208080D7050826BCDE426ED5AF47E51C3A74A847E9BA4E949090107E265D67CF270820106101757075F3702AA1D39549383970AEFB020413EE9B477189EFEF6B39F6982EFFBE5B4F2EEC98614394494F5292060104A251401D5CDD3C9C9AEB7465124E0E9CEBBE00C184BBE6515B9A6E14F6BCBF7FCF9EBDC38F1B16CE452816D9B9EB7D65A50CC3C987731140C06B02EAD4EADAEAE0EAE6E1D44DA72B1365A50CC3C98773DD1448786B53231B02DD0A6C6B6C6E693978C2A8E143870C0EE73FD06143078F4E1A79F0E0879BB735755B28091040208A04D4A9D5B5D5C1D5CD1925A2A8E1FAA4AA09278F4F8DBD8D2BEA7381B1E9C9A3938ED7F46338638473AE069AE4C41113C68EEEF34A922102084450409D5A5D5B1DDCE9E9E1BC2A1365A50C237839147D44022C7384F31F3CE72280000208208080154E30011F02082080000208204030C17F03082080000208C4BE40FF5E213313FDEB4BEE082080000208C4BC00C144CC3731178800020820E09640BC96433011AF2DCF75238000020820D0470204137D04493608208000026E09508ED7040826BCD622D4070104104000812813209888B206A3BA082080805B029483404F0508267A2A453A04104000010410E8548060A253160E228000026E09500E02D12F403011FD6DC815208000020820105101828988F253380208B82540390820D07F020413FD674BCE082080000208C48500C1445C34331789805B0294830002F1284030118FADCE35238000020820D0870204137D88495608B82540390820808097040826BCD41AD4050104104000812814209888C246A3CA6E09500E02082080404F0408267AA2441A04104000010410E8528060A24B1A3E704B80721040000104A25B806022BADB8FDA23800002082010710182898837815B15A01C041040000104FA478060A27F5CC915010410400081B8112098E8E3A6263B041040000104E24D806022DE5A9CEB450001041040A08F05A23498E86305B243000104104000815E0B104CF49A8E1311400001041040C0081C36983009F88700020820800002081C4E8060E2703A7C86000208208040740844B496041311E5A770041040000104A25F806022FADB902B4000010410704B80723A152098E894858308208000020820D0530182899E4A910E01041040C02D01CA8932018289286B30AA8B000208208080D7040826BCD622D407010410704B807210E8230182893E82241B041040000104E25520A17EFB4E360410400081FE13206704625E20213D65141B0208208000020820D06B019639E2754E8AEB4620D604B81E0410889800C144C4E82918010410400081D8102098888D76E42A10704B8072104000810E0204131D483880000208208000024722403071245AA445C02D01CA41000104A2488060228A1A8BAA228000020820E0450182092FB60A75724B80721040000104FA408060A20F10C9020104104000817816209888E7D677EBDA29070104104020A605082662BA79B938041040000104FA5F8060A2FF8DDD2A8172104000010410888800C14444D82914010410400081D811209838D2B6243D020820800002088408104C8470F006010410400001048E54C0ABC1C4915E07E91140000104104020420204131182A7580410400001046243C0B2082662A525B90E041040000104222440301121788A450001041040E04804BC9C9660C2CBAD43DD10400001044B299C350000086F49444154108802018289286824AA8800020820E09600E5F4468060A2376A9C83000208208000020101828900053B08208000026E09504E6C09104CC4567B7235082080000208B82E90F0D6A6463604104000819814E0A210704720E1E4F1A96C082080000208208040AF0558E6707D328802114020D604B81E04E25D806022DEFF0BE0FA11400001041008538060224C404E470001B70428070104BC2A4030E1D596A15E082080000208448900C144943414D544C02D01CA410001048E548060E248C5488F0002082080000221020413211CBC41C02D01CA41000104624780602276DA922B41000104104020220204131161A750B704280701041040A0FF050826FADF98121040000104108869018289986E5EB72E8E721040000104E2598060229E5B9F6B470001041040A00F040826FA00D1AD2C28070104104000012F0A104C78B155A81302082080000251244030D1A1B13880000208208000024722403071245AA445000104104000810E02110B263AD4840308208000020820109502041351D96C541A010410400001D704BA2D8860A25B221220800002082080C0E10408260EA7C367082080000208B82510C5E5104C4471E35175041040000104BC204030E18556A00E08208000026E09504E3F08104CF4032A59228000020820104F020413F1D4DA5C2B020820E09600E5C49500C1445C3537178B000208208040DF0B104CF4BD2939228000026E09500E029E102098F044335009041040000104A2578060227ADB8E9A2380805B029483000287152098382C0F1F228000020820804077020413DD09F1390208B8254039082010A502041351DA70541B010410400001AF08104C78A525A807026E09500E020820D0C70204137D0C4A76082080000208C49B00C144BCB538D7EB9600E5208000027123403011374DCD85228000020820D03F020413FDE34AAE6E09500E0208208040C405082622DE045400010410400081E816209888EEF673ABF69483000208208040970204135DD2F00102082080000208F4448060A2274A6EA5A11C041040000104A2508060220A1B8D2A238000020820E02581780C26BCE44F5D104000010410887A018289A86F422E00010410400081C80AF45F3011D9EBA2740410400001041070498060C225688A410001041040C0AB02E1D68B60225C41CE47000104104020CE050826E2FC3F002E1F01041040C02D81D82D87602276DB962B430001041040C0150182095798290401041040C02D01CA715F8060C27D734A44000104104020A604082662AA39B918041040C02D01CA41A04D8060A2CD823D04104000010410E88500C1442FD03805010410704B8072108806018289686825EA88000208208080870508263CDC38540D0104DC12A01C041008478060221C3DCE45000104104000018B6082FF081040C035010A420081D814209888CD76E5AA104000010410704D8060C2356A0A42C02D01CA41000104DC15209870D79BD21040000104108839018289986B522EC82D01CA4100010410700408261C075E11400001041040A097020413BD84E334B704280701041040C0EB0204135E6F21EA87000208208080C70508263CDE406E558F72104000010410E8AD00C1446FE5380F010410400001046C0182099BC1AD17CA41000104104020F604082662AF4DB9220410400001045C1588C960C255410A43000104104020CE050826E2FC3F002E1F0104104000817005C20826C22D9AF311400001041040201604082662A115B9060410400001040E27D0CF9F114CF43330D923800002082010EB020413B1DEC25C1F02082080805B02715B0EC144DC363D178E000208208040DF08104CF48D23B92080000208B82540399E132098F05C935021041040000104A24B806022BADA8BDA228000026E09500E023D162098E831150911400001041040A033018289CE543886000208B825403908C48000C1440C342297800002082080402405082622A94FD90820E09600E52080403F0A104CF4232E59238000020820100F020413F1D0CA5C23026E09500E0208C4A500C1445C363B178D000208208040DF09104CF49D253921E09600E5208000029E122098F054735019041040000104A24F806022FADA8C1ABB25403908208000023D122098E811138910400001041040A02B018289AE6438EE9600E5208000020844B900C144943720D547000104104020D2020413916E01B7CAA71C041040000104FA498060A29F60C91601041040008178112098E8DB962637041040000104E24E806022EE9A9C0B460001041040A06F05A23398E85B0372430001041040008130040826C2C0E35404104000010410B0ACC30513F8208000020820800002DD0A104C744B4402041040000104BC2E10D9FA114C44D69FD2114000010410887A018289A86F422E0001041040C02D01CAE95C8060A273178E22800002082080400F0508267A0845320410400001B7042827DA040826A2ADC5A82F0208208000021E132098F05883501D041040C02D01CA41A0AF040826FA4A927C1040000104108853018289386D782E1B0104DC12A01C04625F806022F6DB982B440001041040A05F050826FA9597CC1140C02D01CA410081C809104C44CE9E9211400001041088090182899868462E0201B7042807010410E8284030D1D184230820800002082070040204134780455204DC12A01C041040209A040826A2A9B5A82B0208208000021E142098F060A35025B704280701041040A02F040826FA42913C1040000104108863018289386E7CB72E9D721040000104625B806022B6DB97AB430001041040A0DF050826FA9DD8AD022807010410400081C808104C44C69D521140000104108819018289236C4A922380000208208040A800C144A807EF10400001041040E008053C1A4C1CE155901C010410400001042226403011317A0A4600010410402006047409041342604300010410400081DE0B104CF4DE8E33114000010410704BC0D3E5104C78BA79A81C020820800002DE17F87F000000FFFFE094B3FA0000000649444154030009E5CA797C8FC0F30000000049454E44AE426082, '2025-12-17 19:03:57');

-- ----------------------------
-- Table structure for server_cred
-- ----------------------------
DROP TABLE IF EXISTS `server_cred`;
CREATE TABLE `server_cred`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `server_cred_network_area` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '内网' COMMENT '服务器所属网络区域',
  `server_cred_server_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '物理机' COMMENT '服务器类型',
  `server_cred_host_cluster` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '宿主机集群',
  `server_cred_server_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '服务器名称',
  `server_cred_server_ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '服务器IP地址',
  `server_cred_server_port` int(11) NOT NULL DEFAULT 3389 COMMENT '服务器端口号',
  `server_cred_server_os` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '操作系统类型',
  `server_cred_login_username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '登录用户名',
  `server_cred_login_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '加密存储的密码',
  `server_cred_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '备注信息',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `is_active` tinyint(1) NULL DEFAULT 1 COMMENT '是否有效',
  `server_cred_created_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'system' COMMENT '创建人',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_server_ip_port_user`(`server_cred_server_ip`, `server_cred_server_port`, `server_cred_login_username`) USING BTREE,
  INDEX `idx_server_name`(`server_cred_server_name`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 269 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '服务器账号密码管理表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of server_cred
-- ----------------------------
INSERT INTO `server_cred` VALUES (1, '内网', '虚拟机', '深信服超融合主集群', 'LIS自动贴标尿机接口服务', '192.168.201.98', 3389, 'Windows', 'administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:07:55', '2025-12-10 19:07:55', 1, 'admin');
INSERT INTO `server_cred` VALUES (2, '内网', '虚拟机', '深信服超融合主集群', '扫脸设备前置机----医保服务0003', '192.168.201.28', 3389, 'Windows', 'Administrator', '4byWFIslTWRjrUYNE93Zew==', '', '2025-12-10 19:07:55', '2025-12-10 19:07:55', 1, 'admin');
INSERT INTO `server_cred` VALUES (3, '内网', '虚拟机', '深信服超融合主集群', '包药机', '192.168.201.205', 3389, 'Windows', 'Administrator', 'WvATEoWA3yL++TsKbBxnyg==', '', '2025-12-10 19:07:55', '2025-12-10 19:07:55', 1, 'admin');
INSERT INTO `server_cred` VALUES (4, '内网', '虚拟机', '深信服超融合主集群', '信成智能急救系统', '192.168.201.141', 3389, 'Windows', 'administrator', '0TgPgJeW/so/FmWGO7DI1Q==', '', '2025-12-10 19:07:55', '2025-12-10 19:07:55', 1, 'admin');
INSERT INTO `server_cred` VALUES (5, '内网', '虚拟机', '深信服超融合主集群', '老移动护理（好智系统）', '192.168.201.62', 3389, 'Windows', 'Administrator', '45o1K34+6k23vjeWCcMEvQ==', '', '2025-12-10 19:07:55', '2025-12-10 19:07:55', 1, 'admin');
INSERT INTO `server_cred` VALUES (6, '内网', '虚拟机', '深信服超融合主集群', 'win2016_new_srv', '192.168.201.188', 3389, 'Windows', 'Administrator', 'KNpYErwqdR0jOL58h8+hKA==', '', '2025-12-10 19:07:55', '2025-12-10 19:07:55', 1, 'admin');
INSERT INTO `server_cred` VALUES (7, '内网', '虚拟机', '深信服超融合主集群', '影像云外网前置服务器', '192.168.201.17', 3389, 'Windows', 'administrator', '7R3GfRha9FGd9LBTDNOKKw==', '', '2025-12-10 19:07:55', '2025-12-10 19:07:55', 1, 'admin');
INSERT INTO `server_cred` VALUES (8, '内网', '虚拟机', '深信服超融合主集群', '洪门停车', '192.168.201.126', 3389, 'Windows', 'Administrator', 'Xz2aVxiU/RTHqmTP8DIxUA==', '', '2025-12-10 19:07:55', '2025-12-10 19:07:55', 1, 'admin');
INSERT INTO `server_cred` VALUES (9, '内网', '虚拟机', '深信服超融合主集群', '病理报告回传服务器', '192.168.201.110', 3389, 'Windows', 'Administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:07:55', '2025-12-10 19:07:55', 1, 'admin');
INSERT INTO `server_cred` VALUES (10, '内网', '虚拟机', '深信服超融合主集群', '电子病例保存服务器', '192.168.201.89', 3389, 'Windows', 'Administrator', '3pAag5dlhYKbfRfzPpC9MSm4Vi5T5RU6s6vuFP7OeoM=', '', '2025-12-10 19:07:55', '2025-12-10 19:07:55', 1, 'admin');
INSERT INTO `server_cred` VALUES (11, '内网', '虚拟机', '深信服超融合主集群', '便民平台', '192.168.201.169', 3389, 'Windows', 'Administrator', 'cJHcib/tM5DSdawJDJSgPw==', '', '2025-12-10 19:07:55', '2025-12-10 19:07:55', 1, 'admin');
INSERT INTO `server_cred` VALUES (12, '内网', '虚拟机', '深信服超融合主集群', '星海', '192.168.201.219', 3389, 'Windows', 'Administrator', '8DOhK+qrbo2eduzu/hRZig==', '', '2025-12-10 19:07:55', '2025-12-10 19:07:55', 1, 'admin');
INSERT INTO `server_cred` VALUES (13, '内网', '虚拟机', '深信服超融合主集群', '医保一卡通', '192.168.201.132', 3389, 'Windows', 'Administrator', '9YFZ9ZzcPv78cRgkPXF6cQ==', '', '2025-12-10 19:07:55', '2025-12-10 19:07:55', 1, 'admin');
INSERT INTO `server_cred` VALUES (14, '内网', '虚拟机', '深信服超融合主集群', '南师大叫号设备巡检服务器-0001', '192.168.201.30', 3389, 'Windows', 'administrator', 'U6rdO07b3FuoHTJjC92qNw==', '', '2025-12-10 19:07:55', '2025-12-10 19:07:55', 1, 'admin');
INSERT INTO `server_cred` VALUES (15, '内网', '虚拟机', '深信服超融合主集群', '梅山南路门诊药房服务器', '192.168.201.44', 3389, 'Windows', 'Administrator', '2Bzmgr8fG0Cwu1okPM/ViQ==', '', '2025-12-10 19:07:55', '2025-12-10 19:07:55', 1, 'admin');
INSERT INTO `server_cred` VALUES (16, '内网', '虚拟机', '深信服超融合主集群', '西院区自动化药房服务器', '192.168.201.174', 3389, 'Windows', 'Administrator', '78rtKAeAFzpph6T7Ca9g8j9TzioQjlIESrmjGWyO3iU=', '', '2025-12-10 19:07:55', '2025-12-10 19:07:55', 1, 'admin');
INSERT INTO `server_cred` VALUES (17, '内网', '虚拟机', '深信服超融合主集群', 'PACS云胶片转发', '192.168.201.104', 3389, 'Windows', 'Administrator', 'tPAKSexKCqvH1jexWRQgNw==', '', '2025-12-10 19:07:55', '2025-12-10 19:07:55', 1, 'admin');
INSERT INTO `server_cred` VALUES (18, '内网', '虚拟机', '深信服超融合主集群', '省预算管理服务器', '192.168.201.55', 3389, 'Windows', 'Administrator', 'IsIDFxf2FGMK+vdoAPXS3w==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (19, '内网', '虚拟机', '深信服超融合主集群', '扫脸设备前置机----医保服务0004', '192.168.201.29', 3389, 'Windows', 'Administrator', '4byWFIslTWRjrUYNE93Zew==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (20, '内网', '虚拟机', '深信服超融合主集群', 'NEW-LIS', '192.168.201.3', 3389, 'Windows', 'Administrator', '42KDMqIFpOv7F9+wk6NfsQ==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (21, '内网', '虚拟机', '深信服超融合主集群', 'FTP服务器', '192.168.201.168', 3389, 'Windows', 'Administrator', '8vJ3ohPpL9sFJnAnCTLPOw==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (22, '内网', '虚拟机', '深信服超融合主集群', '全国细菌耐药检测网中间件', '192.168.201.101', 3389, 'Windows', 'Administrator', 'u2uiGWVHjmjNDE+flb2IhA==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (23, '内网', '虚拟机', '深信服超融合主集群', '电子病例归档数据库服务器', '192.168.201.87', 3389, 'Windows', 'Administrator', '3pAag5dlhYKbfRfzPpC9MSm4Vi5T5RU6s6vuFP7OeoM=', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (24, '内网', '虚拟机', '深信服超融合主集群', '营养膳食2', '192.168.201.210', 3388, 'Windows', 'Administrator', 'kmKI3+F+1ZoDD8Y3d60iug==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (25, '内网', '虚拟机', '深信服超融合主集群', '院内血糖管理系统', '192.168.201.146', 3389, 'Windows', 'layyxtgl', '+2l9x7D/d1PMoM1VBhRoyQ==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (26, '内网', '虚拟机', '深信服超融合主集群', '西院区网站', '192.168.201.9', 3389, 'Windows', 'layyservers', '6dhV2Qqqfr0zAaK/kpxanoMDcTxZDeuntQ40iVuY1gg=', '防篡改sangfor@la,.3338406', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (27, '内网', '虚拟机', '深信服超融合主集群', '电子健康卡', '192.168.201.111', 3389, 'Windows', 'Administrator', '7jz1ecXbhvp4NqjQzPR6UA==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (28, '内网', '虚拟机', '深信服超融合主集群', '职业病体检服务器', '192.168.201.43', 3389, 'Windows', 'Administrator', 'q/E0d0LYo1Jh+JH59mxhUg==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (29, '内网', '虚拟机', '深信服超融合主集群', 'NEW-阳途桌管', '192.168.201.66', 3389, 'Windows', 'Administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (30, '内网', '虚拟机', '深信服超融合主集群', '堡垒机应用发布服务器', '192.168.201.69', 3389, 'Windows', 'Administrator', '71mowgRYeiP4kj9z6IYW8A==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (31, '内网', '虚拟机', '深信服超融合主集群', '天融信', '192.168.201.140', 3389, 'Windows', 'Administrator', 'XutfqaoHurUEdLDrR78zBg==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (32, '内网', '虚拟机', '深信服超融合主集群', 'NEW血透服务器', '192.168.201.70', 3389, 'Windows', 'Administrator', 'g87q5Pg1av/QUTv5OIjNnQ==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (33, '内网', '虚拟机', '深信服超融合主集群', '审计平台', '192.168.201.204', 3389, 'Windows', 'Administrator', 'OUQ3Nb7hlfbfOrJ9hbLYDg==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (34, '内网', '虚拟机', '深信服超融合主集群', '电子处方服务器', '192.168.201.124', 3389, 'Windows', 'Administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (35, '内网', '虚拟机', '深信服超融合主集群', 'larmyy_PDC', '192.168.201.56', 3389, 'Windows', 'Administrator', '9lDBl8uBKwMAvzPdEDxaFg==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (36, '内网', '虚拟机', '深信服超融合主集群', '无人发药机', '192.168.201.113', 3389, 'Windows', 'administrator', 'CjCfN9xqaV0U7isOHXsiqg==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (37, '内网', '虚拟机', '深信服超融合主集群', '效益分析', '192.168.201.195', 3389, 'Windows', 'Administrator', 'GpuU1iqVCEMhpiQQzWn3Vw==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (38, '内网', '虚拟机', '深信服超融合主集群', 'LIS血培养阴性报告自动审核', '192.168.201.158', 3389, 'Windows', 'administrator', 'gm8Sv3/57R6qSI9UOEwL8g==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (39, '内网', '虚拟机', '深信服超融合主集群', '泰康人寿健保通', '192.168.201.129', 3389, 'Windows', 'Administrator', 'nnxVW9+iC+3wIPDOYcTshA==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (40, '内网', '虚拟机', '深信服超融合主集群', 'OA数据库服务器', '192.168.201.164', 3389, 'Windows', 'administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (41, '内网', '虚拟机', '深信服超融合主集群', '扫脸设备前置机----医保服务0005', '192.168.201.31', 3389, 'Windows', 'Administrator', '4byWFIslTWRjrUYNE93Zew==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (42, '内网', '虚拟机', '深信服超融合主集群', '二门诊新发药机0001', '192.168.201.127', 3389, 'Windows', 'Administrator', 'AU6E6TzjQ6LuFrYsgAReHQ==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (43, '内网', '虚拟机', '深信服超融合主集群', '短信服务器201.105', '192.168.201.105', 3389, 'Windows', 'lenovo', '82pO8nLD+vy2uPxbPSJ4wA==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (44, '内网', '虚拟机', '深信服超融合主集群', '旭辉IOT扫脸服务', '192.168.201.25', 3389, 'Windows', 'administrator', 'RuX0yRxpolnUVmqRmdXX3A==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (45, '内网', '虚拟机', '深信服超融合主集群', '包药机WEB', '192.168.201.177', 3389, 'Windows', 'Administrator', 'HKwiN90H2j//FH0gb4VYejhAIXahPCkUI/KlIGHI7KI=', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (46, '内网', '虚拟机', '深信服超融合主集群', '短信服务器201.82', '192.168.201.82', 3389, 'Windows', 'Administrator', 'U9Oc2qsfCS5YpMaJUj2qjg==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (47, '内网', '虚拟机', '深信服超融合主集群', 'NEW-HERP', '192.168.201.92', 3389, 'Windows', 'Administrator', '2ER+aXkM6othv1GF+dML8Q==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (48, '内网', '虚拟机', '深信服超融合主集群', '医院his接口服务器', '192.168.201.37', 3389, 'Windows', 'administrator', 'kC0m2V8X1Y1Gbm9mMlOBBg==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (49, '内网', '虚拟机', '深信服超融合主集群', '血透', '192.168.201.81', 3389, 'Windows', 'Administrator', 'g87q5Pg1av/QUTv5OIjNnQ==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (50, '内网', '虚拟机', '深信服超融合主集群', 'HIS自动升级服务', '192.168.201.2', 3389, 'Windows', 'administrator', '+uOFaYmW4hTFVJltEFS9ow==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (51, '内网', '虚拟机', '深信服超融合主集群', '上报卡', '192.168.201.136', 3389, 'Windows', 'Administrator', 'KWJcH1L0Ry4xBidgolOQvw==', '', '2025-12-10 19:07:56', '2025-12-10 19:07:56', 1, 'admin');
INSERT INTO `server_cred` VALUES (52, '内网', '虚拟机', '深信服超融合主集群', '营养医嘱', '192.168.201.221', 3389, 'Windows', 'Administrator', 'ZN6JpLB4jEHDuhPp9j0y1Q==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (53, '内网', '虚拟机', '深信服超融合主集群', '二门诊包药机', '192.168.201.95', 3389, 'Windows', 'administrator', 'htFCjMyB1IRghcCQavIppg==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (54, '内网', '虚拟机', '深信服超融合主集群', '医星V21中间件1', '192.168.201.208', 3389, 'Windows', 'administrator', '18iwtbwoiFqATCjDne/fDQ==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (55, '内网', '虚拟机', '深信服超融合主集群', '二门诊新发药机0002', '192.168.201.128', 3389, 'Windows', 'Administrator', 'AU6E6TzjQ6LuFrYsgAReHQ==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (56, '内网', '虚拟机', '深信服超融合主集群', '全民健康平台', '192.168.201.192', 3389, 'Windows', 'Administrator', 'IatMfbBy5BFI/SAwX6AsDQ==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (57, '内网', '虚拟机', '深信服超融合主集群', 'NEW手麻', '192.168.201.180', 3389, 'Windows', 'administrator', 'Pzw7QgPzSDZrnOtzlvuTGg==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (58, '内网', '虚拟机', '深信服超融合主集群', 'SQL监控', '192.168.201.6', 3389, 'Windows', 'administrator', 'sDomxMI0VauIpTZNNPNyFQ==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (59, '内网', '虚拟机', '深信服超融合主集群', 'HIS掌医服务器', '192.168.201.38', 3389, 'Windows', 'HISZYSERVER', '+VWExCuOvsUyIimZi20TpQ==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (60, '内网', '虚拟机', '深信服超融合主集群', 'OA应用服务器', '192.168.201.4', 3389, 'Windows', 'Administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (61, '内网', '虚拟机', '深信服超融合主集群', '联众drgs监控系统', '192.168.201.148', 3389, 'Windows', 'layylzdrgs', 'CdEdVOWYPohFTpZZVDbbhw==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (62, '内网', '虚拟机', '深信服超融合主集群', '内网windows跳板机', '192.168.201.173', 3389, 'Windows', 'administrator', 'Ic5eLtrMCZ1pio7CzgCqeA==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (63, '内网', '虚拟机', '深信服超融合主集群', '西区排队叫号1', '192.168.201.71', 3389, 'Windows', 'Administrator', 'RCvKvsVJ75BUNPrnWIgKerZglciL8tZ1CkXsreyYAnI=', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (64, '内网', '虚拟机', '深信服超融合主集群', 'NEW-短信服务器', '192.168.201.60', 3389, 'Windows', 'administrator', '2p9lDL2MZ41XcXPQNTZSyw==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (65, '内网', '虚拟机', '深信服超融合主集群', '双数传染病系统数据库服务器', '192.168.201.20', 3389, 'Windows', 'administrator', 'vKc4XgCwcDc5fSDM8HxyUw==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (66, '内网', '虚拟机', '深信服超融合主集群', '老营养膳食', '192.168.201.24', 3389, 'Windows', 'administrator', 'g8Isw8bfmJXKcFNpD0A3DQ==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (67, '内网', '虚拟机', '深信服超融合主集群', '老体检', '192.168.201.33', 3389, 'Windows', 'Administrator', '45o1K34+6k23vjeWCcMEvQ==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (68, '内网', '虚拟机', '深信服超融合主集群', '核酸混检', '192.168.201.232', 3389, 'Windows', 'Administrator', '3iK3gzGJAggq0AkfMFKD6A==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (69, '内网', '虚拟机', '深信服超融合主集群', 'larmyy_BDC', '192.168.201.57', 3389, 'Windows', 'Administrator', '9lDBl8uBKwMAvzPdEDxaFg==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (70, '内网', '虚拟机', '深信服超融合主集群', '可视化CA归档', '192.168.201.23', 3389, 'Windows', 'administrator', 'jbeA7Mjz5Rv8ewOX65bv1A==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (71, '内网', '虚拟机', '深信服超融合主集群', '支付宝公众号', '192.168.201.135', 3389, 'Windows', 'Administrator', 'tCVWF1D8m1cMsEPlbEf9Fg==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (72, '内网', '虚拟机', '深信服超融合主集群', '医技FTP', '192.168.201.167', 3389, 'Windows', 'administrator', 'f44LTCLM6/11T/F+nX6cQQ==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (73, '内网', '虚拟机', '深信服超融合主集群', '扫脸设备前置机----医保服务0001', '192.168.201.26', 3389, 'Windows', 'Administrator', '4byWFIslTWRjrUYNE93Zew==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (74, '内网', '虚拟机', '深信服超融合主集群', '排队叫号', '192.168.201.149', 3389, 'Windows', 'Administrator', 'GbVhtrZzSzgRbyS3XsBiIlEzqrvfkdnWW4pyHBdNmYQ=', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (75, '内网', '虚拟机', '深信服超融合主集群', '扫脸设备前置机----医保服务0002', '192.168.201.27', 3389, 'Windows', 'Administrator', '4byWFIslTWRjrUYNE93Zew==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (76, '内网', '虚拟机', '深信服超融合主集群', '医保费用明细上传前置机', '192.168.201.211', 3389, 'Windows', 'Administrator', '5IUUwz788t+i/QP0HXoucw==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (77, '内网', '虚拟机', '深信服超融合主集群', '生殖电子病历系统和人脸核对系统调试服务器', '192.168.201.96', 3389, 'Windows', 'administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (78, '内网', '虚拟机', '深信服超融合主集群', '省号源平台服务器', '192.168.201.100', 3389, 'Windows', 'administrator', 'pI0H+/YowB7W5RQ+MVrUFg==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (79, '内网', '虚拟机', '深信服超融合主集群', 'DELL-SC5020-DSM-仲裁', '192.168.201.99', 3389, 'Windows', 'Administrator', 'yFx+ADVSQ8vi4NYRSE7INQ==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (80, '内网', '虚拟机', '深信服超融合主集群', '镇痛泵服务器', '192.168.201.79', 3389, 'Windows', 'Administrator', 'J3x/9WIvn47j6v+oOHMZX4VJsv2yFuw93mmnnx2l5Vg=', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (81, '内网', '虚拟机', '深信服超融合主集群', '自动化药房2', '192.168.201.175', 3389, 'Windows', 'Administrator', '78rtKAeAFzpph6T7Ca9g8j9TzioQjlIESrmjGWyO3iU=', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (82, '内网', '虚拟机', '深信服超融合主集群', '电子发票打印服务', '192.168.201.250', 3389, 'Windows', 'administrator', 'Mk9wLUs9iVIR5nHDOUoKiA==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (83, '内网', '虚拟机', '深信服超融合主集群', '微信平台', '192.168.201.61', 3389, 'Windows', 'Administrator', 'SSchdnws7isv9O/yLVnVfA==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (84, '内网', '虚拟机', '深信服超融合主集群', '危急值服务', '192.168.201.137', 3389, 'Windows', 'Administrator', '6DYZ2tl2VCv6nYrxDflFBw==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (85, '内网', '虚拟机', '深信服超融合主集群', '核医学科', '192.168.201.75', 3389, 'Windows', 'Administrator', 'uCajNJcaw6O7YvNGYtKQfQ==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (86, '内网', '虚拟机', '深信服超融合主集群', '移动护理测试机', '192.168.201.163', 3389, 'Windows', 'Administrator', 'ZUmbuI0hyH43lbY7UdR3iw==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (87, '内网', '虚拟机', '深信服超融合主集群', '对账服务', '192.168.201.121', 3389, 'Windows', 'Administrator', 'SApfnwO/C0Q3V5toxVzJVg==', '', '2025-12-10 19:07:57', '2025-12-10 19:07:57', 1, 'admin');
INSERT INTO `server_cred` VALUES (88, '内网', '虚拟机', '深信服超融合主集群', '新机房监控平台', '172.168.1.100', 3389, 'Windows', 'Administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (89, '内网', '虚拟机', '深信服超融合主集群', '医星V21中间件2', '192.168.201.209', 3389, 'Windows', 'administrator', '18iwtbwoiFqATCjDne/fDQ==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (90, '内网', '虚拟机', '深信服超融合主集群', 'hisqz06', '192.168.201.203', 3389, 'Windows', 'Administrator', 'LynhWXfEbJj+AbnH0YRAjQ==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (91, '内网', '虚拟机', '深信服超融合主集群', '智慧园接口', '192.168.201.112', 3389, 'Windows', 'administrator', 'EItiCtbAGiPRik7ruzxCYw==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (92, '内网', '虚拟机', '深信服超融合主集群', '院感服务器', '192.168.201.103', 3389, 'Windows', 'Administrator', '45o1K34+6k23vjeWCcMEvQ==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (93, '内网', '虚拟机', '深信服超融合主集群', '手术室智能药柜服务器', '192.168.201.125', 3389, 'Windows', 'layyznyg', 'sm+XWrP/Ao0q+w+xEwMdOQ==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (94, '内网', '虚拟机', '深信服超融合主集群', '电子病例归档应用服务器', '192.168.201.88', 3389, 'Windows', 'Administrator', '3pAag5dlhYKbfRfzPpC9MSm4Vi5T5RU6s6vuFP7OeoM=', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (95, '内网', '虚拟机', '深信服超融合主集群', '合理用药', '192.168.201.131', 3389, 'Windows', 'Administrator', 'g/8WjFGAq6wO+8skDSvdSg==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (96, '内网', '虚拟机', '深信服超融合主集群', '互联网医院数据库', '192.168.201.59', 3389, 'Windows', 'Administrator', '2ESNBCudP7sEKtZRAprvEWreGA0uCv586CRW5OAhEng=', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (97, '内网', '虚拟机', '深信服超融合主集群', '支付平台', '192.168.201.39', 3389, 'Windows', 'Administrator', 'wGS/VLnpf9zXX8CKNB4wuA==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (98, '内网', '虚拟机', '深信服超融合主集群', '病案', '192.168.206.112', 3389, 'Windows', 'Administrator', 'jRF2QSBK8fbGOQW4CSjKag==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (99, '内网', '虚拟机', '深信服超融合主集群', '本部网站', '192.168.201.1', 3389, 'Windows', 'layyservers', '6dhV2Qqqfr0zAaK/kpxanoMDcTxZDeuntQ40iVuY1gg=', '防篡改sangfor@la,.3338406', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (100, '内网', '虚拟机', '深信服超融合主集群', '自助机服务（新建）', '192.168.201.123', 3389, 'Windows', 'Administrator', 'dwxK+6fUWCXcQPy/h9Jwsg==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (101, '内网', '虚拟机', '深信服超融合主集群', '西院区自助机服务', '192.168.201.238', 3389, 'Windows', 'administrator', 'P30FJo0XLRp9HGcMoKuj2Q==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (102, '内网', '虚拟机', '深信服超融合主集群', '自助机HIS服务（新建）0005', '192.168.201.247', 3389, 'Windows', 'administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (103, '内网', '虚拟机', '深信服超融合主集群', '自助机HIS服务分发0002', '192.168.201.184', 3389, 'Windows', 'administrator', 'D2s42IZNiaKG1a6i2gUt2g==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (104, '内网', '虚拟机', '深信服超融合主集群', '梅山路东院区自助机服务器_克隆', '192.168.201.240', 3389, 'Windows', 'administrator', 'dwxK+6fUWCXcQPy/h9Jwsg==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (105, '内网', '虚拟机', '深信服超融合主集群', '深圳证通新自助机的服务', '192.168.201.161', 3389, 'Windows', 'administrator', 'c+4QXoC9iwYr3UVk4UlpAA==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (106, '内网', '虚拟机', '深信服超融合主集群', '西区自助服务', '192.168.201.176', 3389, 'Windows', 'Administrator', 'U6X1lFofvP87vYY8a3GZHEIdsYUDQ3fvMYbKu7T8aHI=', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (107, '内网', '虚拟机', '深信服超融合主集群', '自助机HIS服务分发0003', '192.168.201.185', 3389, 'Windows', 'administrator', 'D2s42IZNiaKG1a6i2gUt2g==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (108, '内网', '虚拟机', '深信服超融合主集群', '自助机HIS服务分发0004', '192.168.201.186', 3389, 'Windows', 'administrator', 'D2s42IZNiaKG1a6i2gUt2g==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (109, '内网', '虚拟机', '深信服超融合主集群', '自助机HIS服务（新建）0001', '192.168.201.144', 3389, 'Windows', 'administrator', 'dwxK+6fUWCXcQPy/h9Jwsg==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (110, '内网', '虚拟机', '深信服超融合主集群', '自助机HIS服务（新建）0007', '192.168.201.239', 3389, 'Windows', 'administrator', 'qRDM8RMPlihXPNZ52c5G2A==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (111, '内网', '虚拟机', '深信服超融合主集群', '扫脸自助服务', '192.168.201.7', 3389, 'Windows', 'administrator', 'aRGT38IIYBPxOO6qUZw4mQ==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (112, '内网', '虚拟机', '深信服超融合主集群', '自助机HIS服务（新建）0004', '192.168.201.246', 3389, 'Windows', 'administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (113, '内网', '虚拟机', '深信服超融合主集群', '自助机服务器（本部）', '192.168.201.143', 3389, 'Windows', 'administrator', 'dwxK+6fUWCXcQPy/h9Jwsg==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (114, '内网', '虚拟机', '深信服超融合主集群', '自助机HIS服务分发0001', '192.168.201.183', 3389, 'Windows', 'administrator', 'D2s42IZNiaKG1a6i2gUt2g==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (115, '内网', '虚拟机', '深信服超融合主集群', '核酸自助服务区', '192.168.201.190', 3389, 'Windows', 'Administrator', 'Ad5DUth1hyGRy0f25y7VuA==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (116, '内网', '虚拟机', '深信服超融合主集群', '西院区自助机服务_克隆-test-250', '192.168.201.245', 3389, 'Windows', 'administrator', 'P30FJo0XLRp9HGcMoKuj2Q==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (117, '内网', '虚拟机', '深信服超融合主集群', '自助机HIS服务（新建）0002', '192.168.201.244', 3389, 'Windows', 'administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (118, '内网', '虚拟机', '深信服超融合主集群', 'new-输血', '192.168.206.172', 3389, 'Windows', 'administrator', 'NWSV8V6lPtmHxJO7nI+s4g==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (119, '内网', '虚拟机', '深信服超融合主集群', '掌医主业务服务器1', '192.168.201.154', 22, 'Linux', 'root', 'ff9LOqIdPFS3jZ+fDGtnSw==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (120, '内网', '虚拟机', '深信服超融合主集群', '数据平台BI数据库(201.234)', '192.168.201.234', 22, 'Linux', 'oracle', 'rbtoF8+UxsBl/wFSoL5aFQ==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (121, '内网', '虚拟机', '深信服超融合主集群', 'EMPI-WEB-201.133', '192.168.201.133', 22, 'Linux', 'root', 'HNO1GYwK5ZTk+TqRnOiguQ==', '', '2025-12-10 19:07:58', '2025-12-10 19:07:58', 1, 'admin');
INSERT INTO `server_cred` VALUES (122, '内网', '虚拟机', '深信服超融合主集群', 'cdss3', '192.168.201.172', 22, 'Linux', 'root', 'H3EKiw20EOqaNvB/xuXSMQ==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (123, '内网', '虚拟机', '深信服超融合主集群', '掌医主业务服务器2', '192.168.201.155', 22, 'Linux', 'root', 'ff9LOqIdPFS3jZ+fDGtnSw==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (124, '内网', '虚拟机', '深信服超融合主集群', '卫计委上报服务器', '192.168.201.178', 22, 'Linux', 'root', 'HNO1GYwK5ZTk+TqRnOiguQ==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (125, '内网', '虚拟机', '深信服超融合主集群', '电子票据缓存协调服务器', '192.168.201.228', 22, 'Linux', 'root', 'ZWnDkknyLmgHSAhnMTeReg==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (126, '内网', '虚拟机', '深信服超融合主集群', '电子票据平台应用服务器1', '192.168.201.226', 22, 'Linux', 'root', 'ZWnDkknyLmgHSAhnMTeReg==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (127, '内网', '虚拟机', '深信服超融合主集群', '电子票据文件系统服务器', '192.168.201.235', 22, 'Linux', 'root', 'ZWnDkknyLmgHSAhnMTeReg==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (128, '内网', '虚拟机', '深信服超融合主集群', '新体检导诊服务器', '192.168.201.68', 22, 'Linux', 'root', 'kln4lsME9a+5eZ3jaFAryQ==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (129, '内网', '虚拟机', '深信服超融合主集群', '新体检服务器1', '192.168.201.67', 22, 'Linux', 'root', 'kln4lsME9a+5eZ3jaFAryQ==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (130, '内网', '虚拟机', '深信服超融合主集群', '电子票据数据库服务器', '192.168.201.231', 22, 'Linux', 'root', 'ZWnDkknyLmgHSAhnMTeReg==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (131, '内网', '虚拟机', '深信服超融合主集群', 'EDR3.2.17', '192.168.201.214', 22, 'Linux', 'admin', 'WAZjRQvaCtni+WEIUpk6MA==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (132, '内网', '虚拟机', '深信服超融合主集群', 'WINDOWS2019-模板-克隆版本_克隆0002_克隆_克隆', '11.11.11.11', 22, 'Linux', 'administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (133, '内网', '虚拟机', '深信服超融合主集群', '数据平台CI数据库', '192.168.201.233', 22, 'Linux', 'root', 'qOJ8zaTK+n2s/OJ73awftw==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (134, '内网', '虚拟机', '深信服超融合主集群', '数据平台备份服务器new', '192.168.201.139', 22, 'Linux', 'root', '/gzlrEEertKJ9R2Ohx35FA==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (135, '内网', '虚拟机', '深信服超融合主集群', '转诊审核系统数据库', '192.168.201.107', 22, 'Linux', 'root', 'JkURMkNgLUmsKSroKY8rUA==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (136, '内网', '虚拟机', '深信服超融合主集群', '移动护理前置机', '192.168.201.83', 22, 'Linux', 'root', 'HiOlRMA7nf8SnH993XuX9A==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (137, '内网', '虚拟机', '深信服超融合主集群', '互联网+护理-数据库服务器', '192.168.201.91', 22, 'Linux', 'root', '+8BHCagcPb0fhVKlKbQCygYbJLmQN1Hsx0yoJF3btb0=', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (138, '内网', '虚拟机', '深信服超融合主集群', 'cdss1', '192.168.201.170', 22, 'Linux', 'root', '6vdMfDmP1+SDfND4zSdLkA==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (139, '内网', '虚拟机', '深信服超融合主集群', '医保办智能监管数据库服务器', '192.168.201.218', 22, 'Linux', 'root', 'hmLGf5f2y+A1xvCSRRxlGQ==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (140, '内网', '虚拟机', '深信服超融合主集群', '掌医数据库服务器', '192.168.201.156', 22, 'Linux', 'root', 'ff9LOqIdPFS3jZ+fDGtnSw==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (141, '内网', '虚拟机', '深信服超融合主集群', '支付宝小程序', '192.168.201.243', 22828, 'Linux', 'root', 'V9yjb6EmmQrzR2WTsiJWGA==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (142, '内网', '虚拟机', '深信服超融合主集群', 'cdss2', '192.168.201.171', 22, 'Linux', 'root', 'gK/2zTNThalxeh+Ndac3vg==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (143, '内网', '虚拟机', '深信服超融合主集群', '掌医文件服务器', '192.168.201.157', 22, 'Linux', 'root', 'ff9LOqIdPFS3jZ+fDGtnSw==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (144, '内网', '虚拟机', '深信服超融合主集群', 'Sangfor_SCP_6.3.80R3（2.10）', '172.168.1.208', 22, 'Linux', 'admin', 'jYaueIO3k1uRYhsFhUF5mw==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (145, '内网', '虚拟机', '深信服超融合主集群', '电子票据平台应用服务器2', '192.168.201.227', 22, 'Linux', 'root', 'ZWnDkknyLmgHSAhnMTeReg==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (146, '内网', '虚拟机', '深信服超融合主集群', '互联网+护理-服务器', '192.168.201.90', 22, 'Linux', 'root', '+8BHCagcPb0fhVKlKbQCygYbJLmQN1Hsx0yoJF3btb0=', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (147, '内网', '虚拟机', '深信服超融合主集群', '转诊审核系统服务器', '192.168.201.106', 22, 'Linux', 'root', 'JkURMkNgLUmsKSroKY8rUA==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (148, '内网', '虚拟机', '深信服超融合主集群', '医保办智能监管应用服务器', '192.168.201.217', 22, 'Linux', 'root', 'hmLGf5f2y+A1xvCSRRxlGQ==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (149, '内网', '虚拟机', '深信服超融合主集群', '电子票据Web服务器（反向代理）', '192.168.201.230', 22, 'Linux', 'root', 'ZWnDkknyLmgHSAhnMTeReg==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (150, '内网', '虚拟机', '深信服超融合主集群', '供应室服务器', '192.168.201.151', 22, 'Linux', '管理员：root', 'gBDtwFpkpViI/NwkOQf5ww==', '普通用户:u1，用户u1的密码：GYB@123!#%', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (151, '内网', '虚拟机', '深信服超融合主集群', '移动护理大屏服务器', '192.168.201.85', 22, 'Linux', 'root', 'v4kkEXHEa6v4c0XMo0kOCA==', '', '2025-12-10 19:07:59', '2025-12-10 19:07:59', 1, 'admin');
INSERT INTO `server_cred` VALUES (152, '内网', '虚拟机', '深信服超融合主集群', 'EMPI--DB', '192.168.201.134', 22, 'Linux', 'root', 'HNO1GYwK5ZTk+TqRnOiguQ==', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (153, '内网', '虚拟机', '深信服超融合主集群', '电子票据负载均衡服务器', '192.168.201.229', 22, 'Linux', 'root', 'ZWnDkknyLmgHSAhnMTeReg==', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (154, '内网', '虚拟机', '深信服超融合主集群', '传染病医院真趣生命体征监护+智能输液系统', '192.168.206.120', 22, 'Linux', 'root', 'OPkS+PYiiufe9GYKvBGPNg==', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (155, '内网', '虚拟机', '深信服超融合主集群', '全闭环输液检测系统业务服务器', '192.168.206.61', 22, 'Linux', 'root', 'Ct6+Bbhg0WmhSbysL9ChL7ljJQKxkeCtz8RIGOqIHZI=', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (156, '内网', '虚拟机', '深信服超融合主集群', '内镜追溯系统服务器', '192.168.206.89', 22, 'Linux', 'root', 'Y+mBOna+AzTc9UNe5x+DJE1ZlVZLpQpjCkyJsGS8RH0=', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (157, '内网', '虚拟机', '深信服超融合主集群', '患者服务部服务器', '192.168.206.63', 22, 'Linux', 'Administrator', 'n2rZhDwBUVJZQoqN/fOfPA==', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (158, '内网', '虚拟机', '深信服超融合主集群', '护理质控平台测试服务器', '192.168.206.98', 22, 'Linux', 'root', 'kb70S1pXDITD2FV8FjFXuYebm2rLYcilcsVEztcXXDc=', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (159, '内网', '虚拟机', '深信服超融合主集群', '护理质控平台应用服务器', '192.168.206.97', 22, 'Linux', 'root', '1PU7g80A0XJ5hyRPsQRhebhl9dZ5fRmiLWNnz8M0YQI=', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (160, '内网', '虚拟机', '深信服超融合主集群', '万方数据库服务器', '192.168.206.20', 22, 'Linux', 'Administrator', 'MSeuqnAqn5iKim3MXn4XxQ==', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (161, '内网', '虚拟机', '深信服超融合主集群', '全闭环输液检测系统数据库服务器', '192.168.206.62', 22, 'Linux', 'root', 'Ct6+Bbhg0WmhSbysL9ChL7ljJQKxkeCtz8RIGOqIHZI=', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (162, '内网', '虚拟机', '深信服超融合主集群', '护理质控平台数据库服务器', '192.168.206.96', 22, 'Linux', 'root', 'W0HwVSElK3cm9cB4yqE12Aodq/UAqbYKAzQfXr6CBS8=', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (163, '内网', '虚拟机', '深信服超融合主集群', '体检测试库服务器', '192.168.201.16', 22, 'Linux', 'root', 'Iz9uomR1XP0ajjusBvUkOA==', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (164, '内网', '虚拟机', '深信服超融合主集群', '衡道-WisPath智慧数字病理系统1', '192.168.206.141', 22, 'Linux', 'root', 'Cgrmu954FVDtbr/XxRZwig==', '一、hengdao/hengdao@123!二、虚拟IP-衡道负载:192.168.206.147', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (165, '内网', '虚拟机', '深信服超融合主集群', '衡道-WisPath智慧数字病理系统2', '192.168.206.142', 22, 'Linux', 'root', 'Cgrmu954FVDtbr/XxRZwig==', '一、hengdao/hengdao@123!二、虚拟IP-衡道负载:192.168.206.147', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (166, '内网', '虚拟机', '深信服超融合主集群', '衡道-数据库存储1', '192.168.206.143', 22, 'Linux', 'root', 'Cgrmu954FVDtbr/XxRZwig==', '一、hengdao/hengdao@123!二、虚拟IP-衡道负载:192.168.206.147', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (167, '内网', '虚拟机', '深信服超融合主集群', '衡道-数据库存储2', '192.168.206.144', 22, 'Linux', 'root', 'Cgrmu954FVDtbr/XxRZwig==', '一、hengdao/hengdao@123!二、虚拟IP-衡道负载:192.168.206.147', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (168, '内网', '虚拟机', '深信服超融合主集群', '输血服务器', '192.168.206.92', 3389, 'Windows', 'administrator', '5sfPvIcJeZDTTVSl0XwnQQ==', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (169, '内网', '虚拟机', '深信服超融合主集群', '核医学科服务器（北京美致医疗）', '192.168.206.94', 3389, 'Windows', 'administrator', 'Nsp3hGB8H8fpIsNOXqO/uQ==', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (170, '内网', '虚拟机', '深信服超融合主集群', '病理上传服务器', '192.168.206.90', 3389, 'Windows', 'administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (171, '内网', '虚拟机', '深信服超融合主集群', 'NEW-病案', '192.168.206.231', 3389, 'Windows', 'administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (172, '内网', '虚拟机', '深信服超融合主集群', '就诊消息免订阅推送平台', '192.168.206.99', 3389, 'Windows', 'Administrator', 'pIprZBmIa4JvREABQkhukcxTN1fKcdVlcPTKKSnFOdE=', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (173, '内网', '虚拟机', '深信服超融合主集群', 'NEW-心电', '192.168.206.12', 3389, 'Windows', 'administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (174, '内网', '虚拟机', '深信服超融合主集群', '省检验互认接口', '192.168.206.4', 3389, 'Windows', 'administrator', 'Q+kJPErDy80SB0NtuG5TYA2mq6drPgJR027EZ7C97d8=', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (175, '内网', '虚拟机', '深信服超融合主集群', '新hrp文件服务器', '192.168.206.45', 3389, 'Windows', 'administrator', 'kzIUyzo7d0B4b3RdenYahA==', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (176, '内网', '虚拟机', '深信服超融合主集群', 'NEW-人事应用+数据库服务器', '192.168.206.93', 3389, 'Windows', 'administrator', 'f/HBLTJGGv3GdwuqD6+X9Q==', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (177, '内网', '虚拟机', '深信服超融合主集群', '新HRP系统测试服务器', '192.168.206.41', 3389, 'Windows', 'administrator', 'OYAd0TJQJCZ1bXKLb6eIf99yjoesoTZwqZgxxA6/NCc=', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (178, '内网', '虚拟机', '深信服超融合主集群', '雷度血气分析', '192.168.206.64', 3389, 'Windows', 'administrator', 'C8drlzm1f3EF2bSNCA4u/g==', '', '2025-12-10 19:08:00', '2025-12-10 19:08:00', 1, 'admin');
INSERT INTO `server_cred` VALUES (179, '内网', '虚拟机', '深信服超融合主集群', '传染病医院鼎鹏信息发布数据库服务器', '192.168.206.121', 3389, 'Windows', 'layydpxx', 'obiLhlxaySR+0de2q8T7wNB1vv/tYZ06UpF+XItcj0U=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (180, '内网', '虚拟机', '深信服超融合主集群', '新HRP系统应用服务器', '192.168.206.42', 3389, 'Windows', 'administrator', 'De/zYPssMzbIGMLOC9uDKKN9i4B5RqI8qR2WC+jBnVo=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (181, '内网', '虚拟机', '深信服超融合主集群', '消毒供应服务器', '192.168.206.40', 3389, 'Windows', 'Administrator', 'W+k+1Dla4j5GRX/KCW+l+w==', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (182, '内网', '虚拟机', '深信服超融合主集群', 'HRP系统CA服务器', '192.168.206.46', 3389, 'Windows', 'administrator', 'XOLYu4RhHAaMZgA/c8zk3w==', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (183, '内网', '虚拟机', '深信服超融合主集群', '瑞智联心电自动采集egateway服务器', '192.168.206.74', 3389, 'Windows', 'layyegateway', 'rvmqlYcO/40JTVbDLgcat7Sm5Jm+O4tK1hStPBuuhps=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (184, '内网', '虚拟机', '深信服超融合主集群', '新HRP系统数据库服务器', '192.168.206.43', 3389, 'Windows', 'administrator', 'EjuBTZRZ65fUvggET2Tke3nQOd45S2z4BADqFR/QAts=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (185, '内网', '虚拟机', '深信服超融合主集群', 'HIS前置机-讯飞传染病前置机', '192.168.206.77', 3389, 'Windows', 'Administrator', 'kZQDRplIh3HX86eHnkMe7g==', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (186, '内网', '虚拟机', '深信服超融合主集群', '瑞智联心电自动采集中央站服务器1', '192.168.206.73', 3389, 'Windows', 'layyzyz', '/X2MB1XNIRcdZeWe/5NoqQ==', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (187, '内网', '虚拟机', '深信服超融合主集群', '瑞智联心电自动采集中央站服务器2', '192.168.206.80', 3389, 'Windows', 'layyzyz', '/X2MB1XNIRcdZeWe/5NoqQ==', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (188, '内网', '虚拟机', '深信服超融合主集群', '瑞智联心电自动采集中央站服务器3', '192.168.206.81', 3389, 'Windows', 'layyzyz', '/X2MB1XNIRcdZeWe/5NoqQ==', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (189, '内网', '虚拟机', '深信服超融合主集群', '瑞智联心电自动采集中央站服务器4', '192.168.206.82', 3389, 'Windows', 'layyzyz', '/X2MB1XNIRcdZeWe/5NoqQ==', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (190, '内网', '虚拟机', '深信服超融合主集群', '文件摆渡-内端机', '192.168.206.168', 3389, 'Windows', 'administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (191, '内网', '虚拟机', '深信服超融合主集群', '新营养系统服务器', '192.168.206.72', 3389, 'Windows', 'layyxyyxt', 'KWE+lxhuIZPHAUayFREes1aZxaEmGgcl34T/+TQBQqE=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (192, '内网', '虚拟机', '深信服超融合主集群', 'LIS微服务-卫宁his', '192.168.206.103', 3389, 'Windows', 'administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (193, '内网', '虚拟机', '深信服超融合主集群', '衡道-前置机', '192.168.206.146', 3389, 'Windows', 'administrator', 'EpzoVk5zn0J4j3KuS3jPRw==', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (194, '内网', '虚拟机', '深信服超融合主集群', 'PACS数据库容灾备份', '192.168.206.22', 3389, 'Windows', 'administrator', '5rnj3L5U+qt4s2P0Ou1h1kLMlX5lC2fGmBsUYayWobQ=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (195, '内网', '虚拟机', '深信服超融合主集群', '医惠移动护理应用服务器（卫宁HIS）', '192.168.206.150', 3389, 'Windows', 'administrator', '82dKGKTtBLjoJ3s9RvvW/AEW9ib84Le8dNeHb/IBQkY=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (196, '内网', '虚拟机', '深信服超融合主集群', '医惠移动护理应用服务测试', '192.168.206.149', 3389, 'Windows', 'administrator', 'apRpsfs64dnuBZfidl2efSJP8GFaDsxvsCBk8RCpUy4=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (197, '内网', '虚拟机', '深信服超融合主集群', '标源质控软件', '192.168.206.151', 3389, 'Windows', 'administrator', 'BXZApfo9jta8MMRWlYsXI4xlimZvtaHcatj3VlclmvQ=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (198, '内网', '虚拟机', '深信服超融合主集群', '腹透及慢病管理系统服务器', '192.168.201.86', 3389, 'Windows', 'Administrator', 'HgbXLebN87ynKo5WpHoC9w==', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (199, '内网', '虚拟机', '深信服超融合主集群', '血透数据库环境', '192.168.206.152', 3389, 'Windows', 'administrator', 'polsUaiN464RAg2heqttBRprlzF1Pdn/mKjhbV/QOyE=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (200, '内网', '虚拟机', '深信服超融合主集群', '血透系统应用环境', '192.168.206.153', 3389, 'Windows', 'administrator', 'polsUaiN464RAg2heqttBRprlzF1Pdn/mKjhbV/QOyE=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (201, '内网', '虚拟机', '深信服超融合备集群', 'NEW院感服务器', '192.168.206.210', 3389, 'Windows', 'administrator', '04wH7dlI1QPVXRVIiNxNl+QMbmt/Rhb8hSsdaP9v6C4=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (202, '内网', '虚拟机', '深信服超融合备集群', '体检HIS接口', '192.168.206.221', 3389, 'Windows', 'administrator', 'W74yodcL7SyYygSegFFgzLJHRMnQyGlmcVXj16R+AKY=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (203, '内网', '虚拟机', '深信服超融合备集群', 'HIS脱敏测试库', '192.168.201.142', 3389, 'Windows', 'administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (204, '内网', '虚拟机', '深信服超融合备集群', 'LIS测试库acmp-dcc7', '192.168.201.153', 3389, 'Windows', 'administrator', 'sLogZQBF//2eYFXoFKXGxms9VUFrt6H/zN28EqTk4yA=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (205, '内网', '虚拟机', '深信服超融合备集群', 'pacs升级测试服务器', '192.168.206.122', 3389, 'Windows', 'administrator', 'h7pwbgjK1xfFsIZDRJ9LtzvDrbEFcTZYdifGIF41oq4=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (206, '内网', '虚拟机', '深信服超融合备集群', 'xc管理2023', '192.168.201.242', 3389, 'Windows', 'xc', 'fVzZ6CmxJ8Q+KApn/wwQ3Q==', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (207, '内网', '虚拟机', '深信服超融合备集群', '心电服务器(新版BS)', '192.168.206.75', 3389, 'Windows', 'layyxdbs', 'OZlr+uJp8cHTvGEe/nCCsA==', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (208, '内网', '虚拟机', '深信服超融合备集群', '梅山南路自动化药房服务器', '192.168.206.135', 3389, 'Windows', 'administrator', 'Y/lEe+xsUcekZhDefxeKLMwnAbZD6uwGrE2uf0d5/Ls=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (209, '内网', '虚拟机', '深信服超融合备集群', '合理用药服务器-对接卫宁', '192.168.206.78', 3389, 'Windows', 'administrator', 'wRrx2yiVDfFnE/rriM6SZ/EBkWIXUvDvDmRE5yB3nqo=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (210, '内网', '虚拟机', '深信服超融合备集群', '医惠移动护理数据库服务器（卫宁HIS）', '192.168.206.100', 3389, 'Windows', 'administrator', 'Jx97gn2qJQ2vasksp6DdHDQIbiw1JFUQWzJ4IgFHup4=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (211, '内网', '虚拟机', '深信服超融合备集群', '快鱼-西院区病房呼叫（新HIS）', '192.168.206.104', 3389, 'Windows', 'administrator', 't6Gbcs7YRWqYdeiID+MUrCGEs1d83mEA6iwW6CxbMmo=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (212, '内网', '虚拟机', '深信服超融合备集群', 'HRP接口服务器（卫宁HIS）', '192.168.206.47', 3389, 'Windows', 'administrator', 'sx92wBvyUIhqJzpsh/Vjeg==', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (213, '内网', '虚拟机', '深信服超融合备集群', '绩效服务器', '192.168.206.137', 3389, 'Windows', 'administrator', '/7Qm5hiCspZBLvEs4KgfxYMMMhUNqnPN8Mb8q3kx6HQ=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (214, '内网', '虚拟机', '深信服超融合备集群', '西院区住院部包药机', '192.168.206.138', 3389, 'Windows', 'administrator', '/7Qm5hiCspZBLvEs4KgfxYMMMhUNqnPN8Mb8q3kx6HQ=', '', '2025-12-10 19:08:01', '2025-12-10 19:08:01', 1, 'admin');
INSERT INTO `server_cred` VALUES (215, '内网', '虚拟机', '深信服超融合备集群', '梅山南路门诊包药机（测试）', '192.168.206.139', 3389, 'Windows', 'administrator', 'lM1K9g3r6RDxNc6eGAzkDhVQ+gl1Kjkbb848YV64LS0=', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (216, '内网', '虚拟机', '深信服超融合备集群', '肿瘤上报中间库（卫宁HIS）', '192.168.206.140', 3389, 'Windows', 'administrator', 'fwpZ8tCDoKpU9pOQlrZsbkvI9NaCeMYqpXAgkHvysAw=', 'mysql：root/LAyy7m#W', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (217, '内网', '虚拟机', '深信服超融合备集群', '电子屏管理服务器', '192.168.206.159', 3389, 'Windows', 'administrator', 'L5t6g0vik5lsmHAcSLA3ZHf7+HbnLGLRHij0OxuLzBY=', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (218, '内网', '虚拟机', '深信服超融合备集群', '卫宁HIS-入院准备中心测试', '192.168.206.154', 3389, 'Windows', 'administrator', 'CgBUl4c1nGPuLH9/IdJxRWcSewLNNk8E8OfWZDm1nIE=', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (219, '内网', '虚拟机', '深信服超融合备集群', 'NEW-TRUENAS', '172.168.1.168', 22, 'Linux', 'null', 'Yh6aceixtp0rAHvWEztofw==', 'truenas_admin(网页)', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (220, '内网', '虚拟机', '深信服超融合备集群', 'ZABBIX5TLS', '192.168.201.58', 22, 'Linux', 'root', 'TCL/7Six+LeUh/sodlSuEg==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (221, '内网', '虚拟机', '深信服超融合备集群', '锐捷乐享平台服务器1', '192.168.206.250', 22, 'Linux', 'root', '6EXZx4a40McMQZ2gW3WWcQ==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (222, '内网', '虚拟机', '深信服超融合备集群', '锐捷乐享平台服务器2', '192.168.206.251', 22, 'Linux', 'root', '6EXZx4a40McMQZ2gW3WWcQ==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (223, '内网', '虚拟机', '深信服超融合备集群', '医保追溯码前置机2', '192.168.206.169', 22, 'Linux', 'root', 'b5IEXWtJlG2JlsiX40ewSw==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (224, '内网', '虚拟机', '深信服超融合备集群', 'HIS数据库脱敏服务器', '19.168.201.42', 22, 'Linux', 'root', 'rug/7o66tO+zY3RVbVvp0w==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (225, '内网', '虚拟机', '深信服超融合备集群', '来邦--西院区病房呼叫（新HIS）', '192.168.206.115', 22, 'Linux', 'root', 'De/kHdxRWTSff5VxHaCfAw==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (226, 'DMZ', '虚拟机', '虚拟化集群', 'HQMS服务器', '192.168.10.40', 3389, 'Windows', 'administrator', 'ly9a9upZHwi63aHJT4ITww==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (227, 'DMZ', '虚拟机', '虚拟化集群', '职业病体检前置机服务器', '192.168.10.101', 3389, 'Windows', 'administrator', 'O6jCkSZSIgXtl9eguDENHw==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (228, 'DMZ', '虚拟机', '虚拟化集群', '老HQMS', '192.168.10.42', 3389, 'Windows', 'Administrator', 'rAKbTVSr1Dt5PlUxamccVA==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (229, 'DMZ', '虚拟机', '虚拟化集群', '新体检前置机', '192.168.10.21', 3389, 'Windows', 'Administrator', '/qjf3C5AVAJZN69nOgNN0g==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (230, 'DMZ', '虚拟机', '虚拟化集群', '慢病门诊微信支付前置机', '192.168.10.27', 3389, 'Windows', 'Administrator', 'SJpLBQah9RZyblWgxJaJSA==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (231, 'DMZ', '虚拟机', '虚拟化集群', '外网FTP服务器', '172.27.1.168', 3389, 'Windows', 'administrator', '/vaOHAfPRvQ47OlReeX3QA==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (232, 'DMZ', '虚拟机', '虚拟化集群', '医师考试-测试-大表哥', '172.27.1.11', 3389, 'Windows', 'administrator', 'mJjdCwdsesOTLXFX3+1unQ==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (233, 'DMZ', '虚拟机', '虚拟化集群', '检验结果互认上传服务器', '172.27.1.14', 3389, 'Windows', 'administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (234, 'DMZ', '虚拟机', '虚拟化集群', 'DMZ区远程用', '172.27.1.21', 3389, 'Windows', 'administrator', '/NTwnYK3u7H5lUB7BKWirA==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (235, 'DMZ', '虚拟机', '虚拟化集群', '检验科外送数据对接前置机', '172.27.1.22', 3389, 'Windows', 'administrator', 'E/tavehNzPiHffTn9KkvBA==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (236, 'DMZ', '虚拟机', '虚拟化集群', '双数传染病系统应用服务器', '172.27.1.23', 3389, 'Windows', 'administrator', 'vKc4XgCwcDc5fSDM8HxyUw==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (237, 'DMZ', '虚拟机', '虚拟化集群', 'NEW-人事招聘服务器', '172.27.1.25', 3389, 'Windows', 'administrator', 'HMoo9iQ4LOdjJg8w/2O3ShB+Z4/BgJarn4ZVy/qEhaA=', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (238, 'DMZ', '虚拟机', '虚拟化集群', '税务云前置机', '172.27.1.32', 3389, 'Windows', 'administrator', '9DEU1NJ4hSzz9kQi25RrbQ==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (239, 'DMZ', '虚拟机', '虚拟化集群', '瑞智联心电自动采集mobileserver服务器', '172.27.1.33', 3389, 'Windows', 'administrator', 'Fz/SBypIxUiPIL9A9sVLPtHhykU7xKptngyfa0LMZ2E=', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (240, 'DMZ', '虚拟机', '虚拟化集群', '数据平台运营APP前置机', '172.27.1.34', 3389, 'Windows', 'administrator', '/agr1VgpZTXoTDYy9vxc33ynIJBSikEFLqzAKM04VAI=', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (241, 'DMZ', '虚拟机', '虚拟化集群', '农业银行银医直联前置机', '172.27.1.35', 3389, 'Windows', 'administrator', 'VXgZSUOS0jqoxGj/qa/rzw==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (242, 'DMZ', '虚拟机', '虚拟化集群', '维保保运维管理服务器', '172.27.1.36', 3389, 'Windows', 'LAyywbb', 'ldu09fedvOfRdv7+eFGVgA==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (243, 'DMZ', '虚拟机', '虚拟化集群', '全益佳云服务器前置机', '172.27.1.39', 3389, 'Windows', 'administrator', 'LGVNgyPrAuyDM2yyxrZKPRH7167hMLcies8IygYXzEs=', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (244, 'DMZ', '虚拟机', '虚拟化集群', 'OA移动应用服务器', '172.27.1.44', 3389, 'Windows', 'administrator', 'MJBSa4AB9gmij63PfFYEqQ==', '', '2025-12-10 19:08:02', '2025-12-10 19:08:02', 1, 'admin');
INSERT INTO `server_cred` VALUES (245, 'DMZ', '虚拟机', '虚拟化集群', '（新网）pacs云胶片前置机', '172.27.1.46', 3389, 'Windows', 'administrator', '1+HiaGU3PvxGnMFhisHt1IJAZbqibnP90jFSKEYh5FM=', '', '2025-12-10 19:08:03', '2025-12-10 19:08:03', 1, 'admin');
INSERT INTO `server_cred` VALUES (246, 'DMZ', '虚拟机', '虚拟化集群', '住院病例核查前置机', '172.27.1.47', 3389, 'Windows', 'administrator', 'g+F4chivRRb/tX7VQOddSDl4VG0WhOWPoep2S/Vf23E=', '', '2025-12-10 19:08:03', '2025-12-10 19:08:03', 1, 'admin');
INSERT INTO `server_cred` VALUES (247, 'DMZ', '虚拟机', '虚拟化集群', '卫宁HIS-DHP的前置机', '172.27.1.52', 3389, 'Windows', 'administrator', 'MXSHfFcBcK6NHAKfw4AFVejeWOdiJpEWHE29NMXm+A0=', '', '2025-12-10 19:08:03', '2025-12-10 19:08:03', 1, 'admin');
INSERT INTO `server_cred` VALUES (248, 'DMZ', '虚拟机', '虚拟化集群', '血透质控上报服务器', '172.27.1.54', 3389, 'Windows', 'Administrator', 'KSoKIkyOqTwxxngY3SrnpWwAxisPOKbJVYvN1bhambc=', '', '2025-12-10 19:08:03', '2025-12-10 19:08:03', 1, 'admin');
INSERT INTO `server_cred` VALUES (249, 'DMZ', '虚拟机', '虚拟化集群', '互联网+延续护理前置机', '172.27.1.12', 22, 'Linux', 'root', '+8BHCagcPb0fhVKlKbQCygYbJLmQN1Hsx0yoJF3btb0=', '', '2025-12-10 19:08:03', '2025-12-10 19:08:03', 1, 'admin');
INSERT INTO `server_cred` VALUES (250, 'DMZ', '虚拟机', '虚拟化集群', '互联网+延续护理测试机', '172.27.1.13', 22, 'Linux', 'root', '+8BHCagcPb0fhVKlKbQCygYbJLmQN1Hsx0yoJF3btb0=', '', '2025-12-10 19:08:03', '2025-12-10 19:08:03', 1, 'admin');
INSERT INTO `server_cred` VALUES (251, 'DMZ', '虚拟机', '虚拟化集群', '医保办智能监管规则服务器（前置机）', '172.27.1.15', 22, 'Linux', 'root', 'hmLGf5f2y+A1xvCSRRxlGQ==', '', '2025-12-10 19:08:03', '2025-12-10 19:08:03', 1, 'admin');
INSERT INTO `server_cred` VALUES (252, 'DMZ', '虚拟机', '虚拟化集群', '深信服超融合云端探针', '172.27.1.16', 22, 'Linux', '——', 'QVwP3Gy+1d/h521mwdyUsA==', '', '2025-12-10 19:08:03', '2025-12-10 19:08:03', 1, 'admin');
INSERT INTO `server_cred` VALUES (253, 'DMZ', '虚拟机', '虚拟化集群', '护理质控平台前置服务器', '172.27.1.24', 22, 'Linux', 'root', '5xuiy7RIOcpY2FmYMfpUXe6fzDoBRo84I98hYdIDYwU=', '', '2025-12-10 19:08:03', '2025-12-10 19:08:03', 1, 'admin');
INSERT INTO `server_cred` VALUES (254, 'DMZ', '虚拟机', '虚拟化集群', '掌医前置服务器', '172.27.1.26', 22, 'Linux', 'root', 'U8nNMzFxpjgPomF1Umv1DQ==', '', '2025-12-10 19:08:03', '2025-12-10 19:08:03', 1, 'admin');
INSERT INTO `server_cred` VALUES (255, 'DMZ', '虚拟机', '虚拟化集群', '掌医测试服务器', '172.27.1.27', 22, 'Linux', 'root', 'U8nNMzFxpjgPomF1Umv1DQ==', '', '2025-12-10 19:08:03', '2025-12-10 19:08:03', 1, 'admin');
INSERT INTO `server_cred` VALUES (256, 'DMZ', '虚拟机', '虚拟化集群', '线上财务系统服务器1', '172.27.1.28', 22, 'Linux', 'root', 'H7Pjcp7xt0nJkD3dd1CPZg==', '', '2025-12-10 19:08:03', '2025-12-10 19:08:03', 1, 'admin');
INSERT INTO `server_cred` VALUES (257, 'DMZ', '虚拟机', '虚拟化集群', '线上财务系统服务器2', '172.27.1.29', 22, 'Linux', 'root', 'H7Pjcp7xt0nJkD3dd1CPZg==', '', '2025-12-10 19:08:03', '2025-12-10 19:08:03', 1, 'admin');
INSERT INTO `server_cred` VALUES (258, 'DMZ', '虚拟机', '虚拟化集群', '锐捷乐享前置机', '172.27.1.40', 22, 'Linux', 'root', 'SakWQX8+7ixqcPelzC0Ekg==', '', '2025-12-10 19:08:03', '2025-12-10 19:08:03', 1, 'admin');
INSERT INTO `server_cred` VALUES (259, 'DMZ', '虚拟机', '虚拟化集群', '互联网医院三方业务跳转', '172.27.1.43', 22, 'Linux', 'root', 'kp3SBn8TWrNHnYNa6ADeaw==', '', '2025-12-10 19:08:03', '2025-12-10 19:08:03', 1, 'admin');
INSERT INTO `server_cred` VALUES (260, 'DMZ', '虚拟机', '虚拟化集群', '掌医三方业务跳转NG主节点', '172.27.1.45', 22, 'Linux', 'root', 'bWGzEozCtrpUr+2LIxpS5A==', '', '2025-12-10 19:08:03', '2025-12-10 19:08:03', 1, 'admin');
INSERT INTO `server_cred` VALUES (261, 'DMZ', '虚拟机', '虚拟化集群', '安徽便民平台前置机（卫宁HIS）', '172.27.1.49', 22, 'Linux', 'root', 'ogZd4gluidbjdY+nJBjAZYdPUlBk6jyx0qRMfvrOF14=', '', '2025-12-10 19:08:03', '2025-12-10 19:08:03', 1, 'admin');
INSERT INTO `server_cred` VALUES (262, 'DMZ', '虚拟机', '虚拟化集群', '卫宁-公共_运维平台前置机_运维_1', '172.27.1.53', 22, 'Linux', 'root', 'vt6aI8QqcqUuR+IRWmFZfA==', '', '2025-12-10 19:08:03', '2025-12-10 19:08:03', 1, 'admin');
INSERT INTO `server_cred` VALUES (263, '内网', '物理机', 'test集群', 'test主机', '1.1.1.1', 3389, 'Linux', 'admin', 'tUsGUQGVywUoAYdwb3QWDw==', 'sa', '2025-12-15 18:00:36', '2025-12-15 18:00:36', 1, 'system');
INSERT INTO `server_cred` VALUES (264, '内网', '物理机', '', 'test物理机服务器1', '211.1.1.1', 3389, 'Linux', 'admin', '8tM0VVUrYFsxc2WZSvNhyA==', 'sasasa', '2025-12-15 19:02:44', '2025-12-15 19:02:44', 1, 'system');
INSERT INTO `server_cred` VALUES (265, '内网', '物理机', '', 'test物理服务器2', '212.2.2.2', 3389, 'Linux', 'admin', 'x35geh+fbeYyw/xqUjueiQ==', 'sas', '2025-12-15 19:04:38', '2025-12-15 19:04:38', 1, 'system');
INSERT INTO `server_cred` VALUES (266, '内网', '物理机', '', '测试服务器52', '52.52.1.1', 22, 'openeuler', 'root', 'tUsGUQGVywUoAYdwb3QWDw==', 'sasa', '2025-12-15 19:53:00', '2025-12-15 19:53:00', 1, 'lyb');
INSERT INTO `server_cred` VALUES (267, '内网', '物理机', '', 'test', '12.12.11.11', 3389, 'Windows Server 2012', 'sas', '9LRqO+m9jcmc61FkTMTv1Q==', 'saas', '2025-12-16 14:17:52', '2025-12-16 14:17:52', 1, 'system');
INSERT INTO `server_cred` VALUES (268, '内网', '虚拟机', 'sasasa', 'sasas', '212.121.121.1', 22, 'AnolisOS-7.9-GA-x86_64', 'sasa', 'x35geh+fbeYyw/xqUjueiQ==', 'sas', '2025-12-16 18:48:50', '2025-12-16 18:48:50', 1, 'lyb');

-- ----------------------------
-- Table structure for sub_domain_info
-- ----------------------------
DROP TABLE IF EXISTS `sub_domain_info`;
CREATE TABLE `sub_domain_info`  (
  `sub_domain_info_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '二级域名ID',
  `sub_domain_info_main_domain_id` int(11) NOT NULL COMMENT '关联主域名ID',
  `sub_domain_info_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '二级域名名称',
  `sub_domain_info_public_ip` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '映射公网IP',
  `sub_domain_info_server_addr` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '服务器IP/端口',
  `sub_domain_info_cert_expiry_date` date NULL DEFAULT NULL COMMENT '证书到期时间',
  `sub_domain_info_cert_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '证书状态',
  `sub_domain_info_status` enum('正常','停用') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '正常' COMMENT '域名状态',
  `sub_domain_info_desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '业务描述',
  `sub_domain_info_notes` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '备注',
  `sub_domain_info_create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `sub_domain_info_update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`sub_domain_info_id`) USING BTREE,
  UNIQUE INDEX `uk_sub_domain_main_name`(`sub_domain_info_main_domain_id`, `sub_domain_info_name`) USING BTREE,
  INDEX `idx_sub_domain_public_ip`(`sub_domain_info_public_ip`) USING BTREE,
  INDEX `idx_sub_domain_server`(`sub_domain_info_server_addr`) USING BTREE,
  CONSTRAINT `fk_sub_domain_main` FOREIGN KEY (`sub_domain_info_main_domain_id`) REFERENCES `main_domain_info` (`main_domain_info_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 42 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '二级域名信息表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of sub_domain_info
-- ----------------------------
INSERT INTO `sub_domain_info` VALUES (5, 6, 'www', '112.30.89.83', '', NULL, '', '正常', '微信公众号', '', '2025-12-15 15:20:20', '2025-12-15 15:52:42');
INSERT INTO `sub_domain_info` VALUES (6, 6, 'weixin', '112.30.89.83', '', NULL, '', '正常', '微信公众号', '', '2025-12-15 15:21:56', '2025-12-15 15:52:46');
INSERT INTO `sub_domain_info` VALUES (7, 6, '@', '112.30.89.83', '', NULL, '', '正常', '微信公众号', '', '2025-12-15 15:22:10', '2025-12-15 15:22:10');
INSERT INTO `sub_domain_info` VALUES (8, 6, 'ihosp', '220.180.2.239', '', NULL, '', '正常', '互联网医院', '', '2025-12-15 15:22:25', '2025-12-15 15:22:25');
INSERT INTO `sub_domain_info` VALUES (9, 6, 'wechat', '218.22.198.163', '', NULL, '', '正常', '互联网加护理', '', '2025-12-15 15:22:48', '2025-12-15 15:22:48');
INSERT INTO `sub_domain_info` VALUES (10, 6, 'ts-wechat', '218.22.198.163', '', NULL, '', '正常', '互联网加护理', '', '2025-12-15 15:23:23', '2025-12-15 15:23:23');
INSERT INTO `sub_domain_info` VALUES (11, 6, 'doc', '218.22.198.163', '', NULL, '', '正常', '互联网加护理', '', '2025-12-15 15:23:40', '2025-12-15 15:23:40');
INSERT INTO `sub_domain_info` VALUES (12, 6, 'ts-doc', '218.22.198.163', '', NULL, '', '正常', '互联网加护理', '', '2025-12-15 15:23:58', '2025-12-15 15:23:58');
INSERT INTO `sub_domain_info` VALUES (13, 6, 'admin', '218.22.198.163', '', NULL, '', '正常', '互联网加护理', '', '2025-12-15 15:24:17', '2025-12-15 15:24:17');
INSERT INTO `sub_domain_info` VALUES (14, 6, 'ts-admin', '218.22.198.163', '', NULL, '', '正常', '互联网加护理', '', '2025-12-15 15:24:34', '2025-12-15 15:24:34');
INSERT INTO `sub_domain_info` VALUES (15, 6, 'hcrm', '218.22.198.163', '', NULL, '', '正常', '互联网加护理', '', '2025-12-15 15:24:48', '2025-12-15 15:24:48');
INSERT INTO `sub_domain_info` VALUES (16, 6, 'ts-hcrm', '218.22.198.163', '', NULL, '', '正常', '互联网加护理', '', '2025-12-15 15:25:03', '2025-12-15 15:25:03');
INSERT INTO `sub_domain_info` VALUES (17, 6, 'hcrm-admin', '218.22.198.163', '', NULL, '', '正常', '互联网加护理', '', '2025-12-15 15:25:18', '2025-12-15 15:25:18');
INSERT INTO `sub_domain_info` VALUES (18, 6, 'ts-hcrm-admin', '218.22.198.163', '218.22.198.163', NULL, '', '正常', '互联网加护理', '', '2025-12-15 15:25:36', '2025-12-15 15:25:36');
INSERT INTO `sub_domain_info` VALUES (19, 6, 'wbb', '112.30.89.83', '', NULL, '', '正常', '维保保', '', '2025-12-15 15:26:06', '2025-12-15 15:26:06');
INSERT INTO `sub_domain_info` VALUES (20, 7, '@', '218.22.198.162', '192.168.201.1:443', NULL, '', '正常', '本部网站', 'CNAME：f9eeec3ee7f181fb.qaxcloudwaf.com', '2025-12-15 15:27:33', '2025-12-15 15:27:33');
INSERT INTO `sub_domain_info` VALUES (21, 7, 'www', '218.22.198.162', '192.168.201.1:443', NULL, '', '正常', '本部网站', 'CNAME:2dc3c180d28abe7e.qaxcloudwaf.com', '2025-12-15 15:29:16', '2025-12-15 15:29:16');
INSERT INTO `sub_domain_info` VALUES (22, 5, 'kdxf', '218.22.198.163', '', NULL, '', '正常', '科大讯飞智慧医院', '暂停\n', '2025-12-15 15:50:03', '2025-12-15 15:50:03');
INSERT INTO `sub_domain_info` VALUES (23, 5, 'zytest', '218.22.198.163', '', NULL, '', '正常', '', '掌医测试', '2025-12-15 15:50:19', '2025-12-15 15:50:19');
INSERT INTO `sub_domain_info` VALUES (24, 5, '@', '218.22.198.163', '', NULL, '', '正常', '掌医', '', '2025-12-15 15:50:30', '2025-12-15 15:50:30');
INSERT INTO `sub_domain_info` VALUES (25, 5, 'hlzk', '218.22.198.163', '', NULL, '', '正常', '护理质控平台', '', '2025-12-15 15:50:39', '2025-12-15 15:50:39');
INSERT INTO `sub_domain_info` VALUES (26, 5, 'zhcw', '218.22.198.163', '', NULL, '', '正常', '智慧财务', '', '2025-12-15 15:50:51', '2025-12-15 15:50:51');
INSERT INTO `sub_domain_info` VALUES (27, 5, 'yunying', '', '', NULL, '', '正常', '', '', '2025-12-15 15:51:06', '2025-12-15 15:51:06');
INSERT INTO `sub_domain_info` VALUES (28, 5, 'zhcwweb', '218.22.198.163', '', NULL, '', '正常', '', '', '2025-12-15 15:51:14', '2025-12-15 15:51:14');
INSERT INTO `sub_domain_info` VALUES (29, 4, '@', '218.22.198.166', '', NULL, '', '正常', '', '', '2025-12-15 15:51:34', '2025-12-15 15:51:34');
INSERT INTO `sub_domain_info` VALUES (32, 4, 'www', '218.22.198.166', '', NULL, '', '正常', '', '', '2025-12-15 15:52:49', '2025-12-15 15:52:49');
INSERT INTO `sub_domain_info` VALUES (33, 4, 'zhcw', '218.22.198.166', '', NULL, '', '正常', '', '', '2025-12-15 15:53:08', '2025-12-15 15:53:08');
INSERT INTO `sub_domain_info` VALUES (34, 4, 'yunying', '218.22.198.166', '', NULL, '', '正常', '', '', '2025-12-15 15:53:17', '2025-12-15 15:53:17');
INSERT INTO `sub_domain_info` VALUES (35, 4, 'zhcwwjyl', '218.22.198.166', '', NULL, '', '正常', '', '', '2025-12-15 15:53:24', '2025-12-15 15:53:24');
INSERT INTO `sub_domain_info` VALUES (36, 4, 'yyss', '218.22.198.166', '', NULL, '', '正常', '', '', '2025-12-15 15:53:32', '2025-12-15 15:53:32');
INSERT INTO `sub_domain_info` VALUES (37, 4, 'oa', '218.22.198.166', '', NULL, '', '正常', '', '', '2025-12-15 15:53:43', '2025-12-15 15:53:43');
INSERT INTO `sub_domain_info` VALUES (38, 4, 'dicom', '218.22.198.166', '', NULL, '', '正常', '', '', '2025-12-15 15:53:51', '2025-12-15 15:53:51');
INSERT INTO `sub_domain_info` VALUES (39, 4, 'winexmy', '218.22.198.166', '', NULL, '', '正常', '', '', '2025-12-15 15:54:06', '2025-12-15 15:54:06');
INSERT INTO `sub_domain_info` VALUES (40, 4, '3center-data', '218.22.198.166', '', NULL, '', '正常', '', '', '2025-12-15 15:54:13', '2025-12-15 15:54:13');
INSERT INTO `sub_domain_info` VALUES (41, 4, 'winexapp', '218.22.198.166', '', NULL, '', '正常', '', '', '2025-12-15 15:54:21', '2025-12-15 15:54:21');

SET FOREIGN_KEY_CHECKS = 1;
