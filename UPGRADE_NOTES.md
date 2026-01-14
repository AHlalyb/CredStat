# 容量单位转换功能升级说明

## 1. 功能变更

### 1.1 前端表单调整
- 容量和已使用空间字段从数字输入改为文本输入，允许用户手动输入单位
- 支持的单位类型：MB、GB、TB
- 示例输入格式：100GB、500MB、2TB
- 自动验证输入格式，确保数值和单位正确

### 1.2 单位转换规则
- **输入转换**：将用户输入的带单位容量转换为GB为单位的数值存储到数据库
  - MB → GB：除以1024
  - GB → GB：保持不变
  - TB → GB：乘以1024
- **输出转换**：从数据库读取GB数值后，根据数值大小自动选择合适的单位显示
  - < 1GB：使用MB单位
  - 1GB - 1023GB：使用GB单位
  - ≥ 1024GB：使用TB单位

### 1.3 数据库字段调整
- **字段名**：保持不变（capacity_gb, used_space_gb）
- **字段类型**：从DECIMAL类型改为VARCHAR(20)类型
- **存储内容**：直接存储包含数值和单位的完整字符串，不再进行单位转换
- **示例存储**：
  - 500MB → 直接存储为"500MB"（VARCHAR类型）
  - 2TB → 直接存储为"2TB"（VARCHAR类型）

## 2. 技术实现

### 2.1 前端实现
- 添加了`validateStorageUnit`验证函数，确保输入格式正确
- 添加了`parseCapacityToGB`函数，用于将带单位容量转换为GB数值（保留用于其他可能的计算场景）
- 添加了`formatStorageCapacity`函数，用于将GB数值转换为带合适单位的字符串（保留用于其他可能的显示场景）

### 2.2 后端实现
- 修改了数据库表结构，将capacity_gb和used_space_gb字段从DECIMAL类型改为VARCHAR(20)类型
- 修改了保存逻辑，直接将前端传递的带单位容量字符串保存到数据库，不再进行单位转换
- 移除了parseStorageCapacity函数的调用，简化了保存流程

## 3. 使用示例

### 3.1 输入示例
```
容量：500MB → 直接存储为"500MB"
已使用空间：250MB → 直接存储为"250MB"

容量：100GB → 直接存储为"100GB"
已使用空间：60GB → 直接存储为"60GB"

容量：2TB → 直接存储为"2TB"
已使用空间：1.5TB → 直接存储为"1.5TB"
```

### 3.2 输出示例
```
数据库存储："500MB" → 直接显示为"500MB"
数据库存储："100GB" → 直接显示为"100GB"
数据库存储："2TB" → 直接显示为"2TB"
```

## 4. 影响范围

- 仅影响服务器基本信息录入页面的磁盘信息模块
- 已存在的数据将按照新的规则自动转换显示
- 不影响其他功能模块

## 5. 兼容性

- 向前兼容：已存储的数据可以正常读取和显示
- 向后兼容：新数据可以被旧版本系统读取（但可能显示为原始GB数值）

## 6. 验证规则

- 必须包含有效单位（MB、GB、TB）
- 数值必须大于等于0
- 支持整数和小数（如1.5GB）
- 单位不区分大小写（如100gb、500Mb）

## 7. 注意事项

- 建议使用标准单位（MB、GB、TB），避免使用其他单位
- 对于非常小的容量，系统会自动转换为MB显示
- 对于非常大的容量，系统会自动转换为TB显示
- 数据库中存储完整的带单位字符串，如"100GB"、"50MB"、"2TB"

## 8. 服务器磁盘信息表字段重命名

### 8.1 变更内容
- 将`server_cred_volu_info`表的所有字段添加了`server_cred_volu_`统一前缀
- 保持了字段的数据类型、约束条件和默认值不变
- 修改了`save_server_cred.php`中的INSERT语句，使用新字段名
- 更新了`create_server_cred_volu_info_table.sql`文件，使用新字段名

### 8.2 字段映射关系
| 原字段名 | 新字段名 |
|---------|---------|
| os_type | server_cred_volu_os_type |
| windows_drive_letter | server_cred_volu_windows_drive_letter |
| linux_device_name | server_cred_volu_linux_device_name |
| linux_mount_point | server_cred_volu_linux_mount_point |
| capacity_gb | server_cred_volu_capacity |
| used_space_gb | server_cred_volu_used_space |
| file_system_type | server_cred_volu_file_system_type |
| notes | server_cred_volu_notes |
| created_at | server_cred_volu_created_at |
| updated_at | server_cred_volu_updated_at |
| created_by | server_cred_volu_created_by |
| updated_by | server_cred_volu_updated_by |

### 8.3 命名规范
- 统一前缀：所有字段使用`server_cred_volu_`作为前缀
- 下划线命名：使用下划线分隔单词，提高可读性
- 清晰描述：字段名称准确反映字段内容和用途

### 8.4 执行步骤
1. 备份`server_cred_volu_info`表数据
2. 执行`rename_volu_fields.sql`脚本修改字段名称
3. 部署修改后的代码
4. 测试所有相关功能
5. 更新相关文档

### 8.5 注意事项
- 执行SQL脚本前务必做好数据备份
- 修改后需测试所有相关功能，确保系统正常运行
- 更新所有引用该表的代码，确保无字段引用错误

详细的字段映射关系和设计考量请参考`字段重命名对照表.md`文件。
