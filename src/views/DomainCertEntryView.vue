<template>
  <div class="domain-cert-entry-view">
    <!-- 页面标题 -->
    <el-card class="mt-5" shadow="hover">
      <template #header>
        <div class="card-header">
          <h3 class="m-0">域名及证书管理</h3>
        </div>
      </template>
      
      <div class="el-card__body">
      <el-tabs v-model="activeTab" type="card">
        <el-tab-pane label="主域名管理" name="mainDomain">
          <div class="mb-4">
            <el-button type="primary" @click="addMainDomainRow" :icon="Plus">
              添加主域名
            </el-button>
          </div>
          
          <!-- 主域名录入区域 -->
          <el-card class="mb-4" shadow="hover">
            <template #header>
              <div class="card-header">
                <h6 class="m-0">主域名录入</h6>
              </div>
            </template>
            
            <div class="main-domain-entries">
              <el-card
                v-for="(domain, index) in mainDomainEntries"
                :key="domain.id"
                shadow="hover"
                class="mb-3"
              >
                <template #header>
                  <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h7 class="m-0">主域名信息</h7>
                    <el-button
                      type="danger"
                      size="small"
                      @click="removeMainDomainRow(index)"
                      :icon="Delete"
                    >
                      删除
                    </el-button>
                  </div>
                </template>
                
                <el-form
                  :model="domain"
                  :rules="mainDomainRules"
                  label-width="100px"
                  label-position="top"
                >
                  <el-row :gutter="[20, 20]">
                    <el-col :xs="24" :sm="12" :md="8">
                      <el-form-item label="主域名" prop="domainName" required>
                        <el-input
                          v-model="domain.domainName"
                          placeholder="请输入主域名，如example.com"
                          maxlength="255"
                        ></el-input>
                      </el-form-item>
                    </el-col>
                    <el-col :xs="24" :sm="12" :md="8">
                      <el-form-item label="注册时间" prop="registerDate" required>
                        <el-date-picker
                          v-model="domain.registerDate"
                          type="date"
                          placeholder="选择注册时间"
                          style="width: 100%"
                        ></el-date-picker>
                      </el-form-item>
                    </el-col>
                    <el-col :xs="24" :sm="12" :md="8">
                      <el-form-item label="到期时间" prop="expiryDate" required>
                        <el-date-picker
                          v-model="domain.expiryDate"
                          type="date"
                          placeholder="选择到期时间"
                          style="width: 100%"
                        ></el-date-picker>
                      </el-form-item>
                    </el-col>
                  </el-row>
                  <el-form-item>
                    <el-button
                      type="primary"
                      @click="saveMainDomainRow(index)"
                      :icon="Check"
                    >
                      保存
                    </el-button>
                  </el-form-item>
                </el-form>
              </el-card>
            </div>
          </el-card>
          
          <!-- 主域名列表 -->
          <el-card shadow="hover">
            <template #header>
              <div class="card-header">
                <h6 class="m-0">主域名列表</h6>
              </div>
            </template>
            
            <el-table
              v-loading="loading.mainDomain"
              :data="mainDomains"
              border
              style="width: 100%"
            >
              <el-table-column prop="domainName" label="主域名" min-width="150"></el-table-column>
              <el-table-column prop="registerDate" label="注册时间" min-width="120"></el-table-column>
              <el-table-column prop="expiryDate" label="到期时间" min-width="120"></el-table-column>
              <el-table-column label="操作" width="180" fixed="right">
                <template #default="scope">
                  <div class="action-buttons">
                    <el-tooltip content="编辑" placement="top">
                      <el-button
                        type="primary"
                        circle
                        @click="editMainDomain(scope.row)"
                      >
                        <el-icon><Edit /></el-icon>
                      </el-button>
                    </el-tooltip>
                    <el-tooltip content="删除" placement="top">
                      <el-button
                        type="danger"
                        circle
                        @click="deleteMainDomain(scope.row.id)"
                      >
                        <el-icon><Delete /></el-icon>
                      </el-button>
                    </el-tooltip>
                  </div>
                </template>
              </el-table-column>
            </el-table>
          </el-card>
        </el-tab-pane>
        
        <el-tab-pane label="二级域名及证书管理" name="subDomain">
          <div class="mb-4">
            <el-button type="primary" @click="addSubDomainCertRow" :icon="Plus">
              添加二级域名及证书
            </el-button>
          </div>
          
          <!-- 二级域名录入区域 -->
          <el-card class="mb-4" shadow="hover">
            <template #header>
              <div class="card-header">
                <h6 class="m-0">二级域名及证书录入</h6>
              </div>
            </template>
            
            <div class="sub-domain-entries">
              <el-card
                v-for="(domain, index) in subDomainEntries"
                :key="domain.id"
                shadow="hover"
                class="mb-3"
              >
                <template #header>
                  <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h7 class="m-0">二级域名及证书信息</h7>
                    <el-button
                      type="danger"
                      size="small"
                      @click="removeSubDomainCertRow(index)"
                      :icon="Delete"
                    >
                      删除
                    </el-button>
                  </div>
                </template>
                
                <el-form
                  :model="domain"
                  :rules="subDomainRules"
                  label-width="120px"
                  label-position="top"
                >
                  <el-row :gutter="[20, 20]">
                    <el-col :xs="24" :sm="12" :md="6">
                      <el-form-item label="主域名" prop="mainDomain" required>
                        <el-select
                          v-model="domain.mainDomain"
                          placeholder="请选择主域名"
                          style="width: 100%"
                        >
                          <el-option
                            v-for="mainDomain in mainDomains"
                            :key="mainDomain.id"
                            :label="mainDomain.domainName"
                            :value="mainDomain.id"
                          ></el-option>
                        </el-select>
                      </el-form-item>
                    </el-col>
                    <el-col :xs="24" :sm="12" :md="6">
                      <el-form-item label="二级域名" prop="subDomain" required>
                        <el-input
                          v-model="domain.subDomain"
                          placeholder="请输入二级域名"
                          maxlength="255"
                        ></el-input>
                      </el-form-item>
                    </el-col>
                    <el-col :xs="24" :sm="12" :md="6">
                      <el-form-item label="映射公网IP" prop="mappingIp">
                        <el-input
                          v-model="domain.mappingIp"
                          placeholder="请输入映射公网IP地址"
                          maxlength="15"
                        ></el-input>
                      </el-form-item>
                    </el-col>
                    <el-col :xs="24" :sm="12" :md="6">
                      <el-form-item label="服务器IP/端口" prop="serverIpPort">
                        <el-input
                          v-model="domain.serverIpPort"
                          placeholder="请输入服务器IP:端口，如：192.168.1.100:8080"
                          maxlength="50"
                        ></el-input>
                      </el-form-item>
                    </el-col>
                    <el-col :xs="24" :sm="12" :md="6">
                      <el-form-item label="证书到期时间" prop="certExpiry">
                        <el-date-picker
                          v-model="domain.certExpiry"
                          type="date"
                          placeholder="选择证书到期时间"
                          style="width: 100%"
                        ></el-date-picker>
                      </el-form-item>
                    </el-col>
                    <el-col :xs="24" :sm="12" :md="6">
                      <el-form-item label="证书状态" prop="certStatus">
                        <el-select
                          v-model="domain.certStatus"
                          placeholder="请选择证书状态"
                          style="width: 100%"
                        >
                          <el-option label="有效" value="valid"></el-option>
                          <el-option label="已过期" value="expired"></el-option>
                          <el-option label="即将过期" value="expiring"></el-option>
                          <el-option label="无效" value="invalid"></el-option>
                        </el-select>
                      </el-form-item>
                    </el-col>
                    <el-col :xs="24" :sm="24" :md="12">
                      <el-form-item label="业务描述" prop="desc">
                        <el-input
                          v-model="domain.desc"
                          placeholder="请输入业务描述"
                          maxlength="255"
                          show-word-limit
                        ></el-input>
                      </el-form-item>
                    </el-col>
                    <el-col :xs="24" :sm="24" :md="12">
                      <el-form-item label="备注" prop="notes">
                        <el-input
                          v-model="domain.notes"
                          placeholder="请输入备注信息"
                          maxlength="500"
                          show-word-limit
                          type="textarea"
                          :rows="3"
                        ></el-input>
                      </el-form-item>
                    </el-col>
                  </el-row>
                  <el-form-item>
                    <el-button
                      type="primary"
                      @click="saveSubDomainCertRow(index)"
                      :icon="Check"
                    >
                      保存
                    </el-button>
                  </el-form-item>
                </el-form>
              </el-card>
            </div>
          </el-card>
          
          <!-- 二级域名及证书列表 -->
          <el-card shadow="hover">
            <template #header>
              <div class="card-header">
                <h6 class="m-0">二级域名及证书列表</h6>
              </div>
            </template>
            
            <el-table
              v-loading="loading.subDomain"
              :data="subDomains"
              border
              style="width: 100%"
            >
              <el-table-column prop="mainDomain" label="主域名" min-width="150"></el-table-column>
              <el-table-column prop="subDomain" label="二级域名" min-width="150"></el-table-column>
              <el-table-column prop="mappingIp" label="映射公网IP" min-width="120"></el-table-column>
              <el-table-column prop="serverIpPort" label="服务器IP/端口" min-width="150"></el-table-column>
              <el-table-column prop="certExpiry" label="证书到期时间" min-width="120"></el-table-column>
              <el-table-column label="证书状态" min-width="100">
                <template #default="scope">
                  <el-tag :type="getCertStatusType(scope.row.certStatus)">
                    {{ getCertStatusText(scope.row.certStatus) }}
                  </el-tag>
                </template>
              </el-table-column>
              <el-table-column prop="desc" label="业务描述" min-width="200"></el-table-column>
              <el-table-column prop="notes" label="备注" min-width="250"></el-table-column>
              <el-table-column label="操作" width="180" fixed="right">
                <template #default="scope">
                  <div class="action-buttons">
                    <el-tooltip content="编辑" placement="top">
                      <el-button
                        type="primary"
                        circle
                        @click="editSubDomain(scope.row)"
                      >
                        <el-icon><Edit /></el-icon>
                      </el-button>
                    </el-tooltip>
                    <el-tooltip content="删除" placement="top">
                      <el-button
                        type="danger"
                        circle
                        @click="deleteSubDomain(scope.row.id)"
                      >
                        <el-icon><Delete /></el-icon>
                      </el-button>
                    </el-tooltip>
                  </div>
                </template>
              </el-table-column>
            </el-table>
          </el-card>
        </el-tab-pane>
      </el-tabs>
      </div>
    </el-card>
  </div>
</template>

<script>
// 导入Element Plus图标
import { Plus, Check, Edit, Delete } from '@element-plus/icons-vue';

export default {
  name: 'DomainCertEntryView',
  components: {
    Plus,
    Check,
    Edit,
    Delete
  },
  data() {
    return {
      // 当前激活的标签页
      activeTab: 'mainDomain',
      // 主域名数据
      mainDomains: [],
      // 二级域名数据
      subDomains: [],
      // 主域名录入行数据
      mainDomainEntries: [],
      // 二级域名录入行数据
      subDomainEntries: [],
      // 加载状态
      loading: {
        mainDomain: false,
        subDomain: false
      },
      // 主域名表单验证规则
      mainDomainRules: {
        domainName: [
          { required: true, message: '请输入主域名', trigger: 'blur' },
          { pattern: /^([a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.)+[a-zA-Z]{2,}$/, message: '请输入有效的域名格式，如example.com', trigger: 'blur' }
        ],
        registerDate: [{ required: true, message: '请选择注册时间', trigger: 'change' }],
        expiryDate: [{ required: true, message: '请选择到期时间', trigger: 'change' }]
      },
      // 二级域名表单验证规则
      subDomainRules: {
        mainDomain: [{ required: true, message: '请选择主域名', trigger: 'change' }],
        subDomain: [{ required: true, message: '请输入二级域名', trigger: 'blur' }],
        mappingIp: [
          { required: false, message: '请输入映射公网IP地址', trigger: 'blur' },
          { pattern: /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/, message: '请输入有效的IP地址格式，如192.168.1.1', trigger: 'blur' }
        ],
        serverIpPort: [
          { required: false, message: '请输入服务器IP:端口', trigger: 'blur' },
          { pattern: /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?):(?:[0-9]{1,4}|[1-5][0-9]{4}|6[0-4][0-9]{3}|65[0-4][0-9]{2}|655[0-2][0-9]|6553[0-5])$/, message: '请输入有效的服务器IP:端口，格式如：192.168.1.100:8080', trigger: 'blur' }
        ],
        certExpiry: [{ required: false, message: '请选择证书到期时间', trigger: 'change' }],
        certStatus: [{ required: false, message: '请选择证书状态', trigger: 'change' }],
        desc: [{ max: 255, message: '业务描述不能超过255个字符', trigger: 'blur' }],
        notes: [{ max: 500, message: '备注信息不能超过500个字符', trigger: 'blur' }]
      }
    };
  },
  mounted() {
    console.log('域名及证书录入视图已挂载');
    // 初始化域名证书管理功能
    this.initDomainCertManagement();
  },
  methods: {
    /**
     * 初始化域名证书管理功能
     */
    async initDomainCertManagement() {
      console.log('=== 开始初始化域名证书管理功能 ===');
      
      // 初始化主域名列表
      await this.initMainDomainList();
      
      // 初始化二级域名列表
      await this.initSubDomainCertList();
    },
    
    /**
     * 添加主域名录入行
     */
    addMainDomainRow() {
      console.log('添加主域名录入行');
      this.mainDomainEntries.push({
        id: 'mainDomainRow_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
        domainName: '',
        registerDate: '',
        expiryDate: ''
      });
    },
    
    /**
     * 删除主域名录入行
     */
    removeMainDomainRow(index) {
      console.log('删除主域名录入行:', index);
      this.mainDomainEntries.splice(index, 1);
    },
    
    /**
     * 保存主域名录入行
     */
    async saveMainDomainRow(index) {
      console.log('保存主域名录入行:', index);
      
      const mainDomainData = this.mainDomainEntries[index];
      
      try {
        // 调用后端API保存到数据库
        const response = await fetch('/save_main_domain.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(mainDomainData)
        });
        
        const result = await response.json();
        console.log('API响应结果:', result);
        
        if (result.success) {
          // 保存成功，保存到内部状态
          const savedDomain = {
            id: result.data?.mainDomainId || Date.now(),
            ...mainDomainData
          };
          this.mainDomains.push(savedDomain);
          
          // 显示保存成功消息
          this.$message.success('主域名信息保存成功！');
          
          // 刷新主域名列表
          await this.initMainDomainList();
          
          // 移除当前录入行
          this.mainDomainEntries.splice(index, 1);
        } else {
          // 保存失败
          this.$message.error('主域名信息保存失败：' + result.message);
        }
      } catch (error) {
        console.error('保存主域名信息时发生错误:', error);
        this.$message.error('主域名信息保存失败：网络错误或服务器异常');
      }
    },
    
    /**
     * 初始化主域名列表
     */
    async initMainDomainList() {
      console.log('初始化主域名列表');
      
      this.loading.mainDomain = true;
      
      try {
        // 调用后端API获取主域名列表
        const response = await fetch('/get_main_domains.php', {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
          }
        });
        
        console.log('响应状态:', response.status);
        console.log('响应头:', response.headers);
        
        if (!response.ok) {
          throw new Error(`HTTP错误! 状态码: ${response.status}`);
        }
        
        // 获取原始响应文本
        const responseText = await response.text();
        console.log('原始响应文本:', responseText);
        
        // 检查响应文本是否为空
        if (!responseText.trim()) {
          throw new Error('服务器返回空响应');
        }
        
        // 尝试解析JSON
        const result = JSON.parse(responseText);
        console.log('解析后的响应:', result);
        
        if (result.success) {
          // 更新内部状态
          if (Array.isArray(result.data)) {
            this.mainDomains = result.data.map(domain => ({
              id: domain.main_domain_info_id,
              domainName: domain.main_domain_info_name,
              registerDate: domain.main_domain_info_regist_date,
              expiryDate: domain.main_domain_info_expire_date
            }));
          } else {
            this.mainDomains = [];
          }
        } else {
          // 处理API返回的错误
          this.$message.error('获取主域名列表失败: ' + (result.message || '未知错误'));
          this.mainDomains = [];
        }
      } catch (error) {
        console.error('获取主域名列表时发生错误:', error);
        this.$message.error('获取主域名列表失败: ' + error.message);
        // 确保mainDomains是数组
        this.mainDomains = [];
      } finally {
        this.loading.mainDomain = false;
      }
    },
    
    /**
     * 编辑主域名
     */
    editMainDomain(row) {
      console.log('编辑主域名:', row);
      // 实现编辑功能
      this.$message.info('编辑功能开发中');
    },
    
    /**
     * 删除主域名
     */
    deleteMainDomain(id) {
      console.log('删除主域名:', id);
      // 实现删除功能
      this.$confirm('确定要删除这条主域名信息吗？', '删除确认', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(async () => {
        try {
          // 调用后端API删除主域名
          const response = await fetch('/delete_main_domain.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id })
          });
          
          // 检查响应状态
          if (!response.ok) {
            throw new Error(`HTTP错误：${response.status} ${response.statusText}`);
          }
          
          // 检查响应内容类型
          const contentType = response.headers.get('content-type');
          if (contentType && contentType.includes('application/json')) {
            // 读取响应文本，先检查是否为空
            const responseText = await response.text();
            if (!responseText.trim()) {
              throw new Error('服务器返回空响应');
            }
            
            // 尝试解析JSON
            const result = JSON.parse(responseText);
            if (result.success) {
              // 删除成功，更新内部状态
              this.mainDomains = this.mainDomains.filter(domain => domain.id !== id);
              this.$message.success('主域名删除成功！');
            } else {
              this.$message.error('主域名删除失败：' + result.message);
            }
          } else {
            // 非JSON响应，读取文本内容并抛出错误
            const responseText = await response.text();
            throw new Error(`服务器返回非JSON响应：${responseText.substring(0, 100)}...`);
          }
        } catch (error) {
          console.error('删除主域名时发生错误:', error);
          this.$message.error(`主域名删除失败：网络错误或服务器异常`);
        }
      }).catch(() => {
        this.$message.info('已取消删除');
      });
    },
    
    /**
     * 添加二级域名及证书录入行
     */
    addSubDomainCertRow() {
      console.log('添加二级域名及证书录入行');
      this.subDomainEntries.push({
        id: 'subDomainCertRow_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
        mainDomain: '',
        subDomain: '',
        mappingIp: '',
        serverIpPort: '',
        certExpiry: '',
        certStatus: '',
        desc: '',
        notes: ''
      });
    },
    
    /**
     * 删除二级域名及证书录入行
     */
    removeSubDomainCertRow(index) {
      console.log('删除二级域名及证书录入行:', index);
      this.subDomainEntries.splice(index, 1);
    },
    
    /**
     * 保存二级域名及证书信息
     */
    async saveSubDomainCertRow(index) {
      console.log('保存二级域名及证书信息:', index);
      
      const subDomainData = this.subDomainEntries[index];
      
      try {
        // 调用后端API保存到数据库
        const response = await fetch('/save_sub_domain.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(subDomainData)
        });
        
        const result = await response.json();
        console.log('API响应结果:', result);
        
        if (result.success) {
          // 保存成功，保存到内部状态
          const savedDomain = {
            id: result.data?.subDomainId || Date.now(),
            ...subDomainData,
            mainDomain: this.mainDomains.find(domain => domain.id == subDomainData.mainDomain)?.domainName || ''
          };
          this.subDomains.push(savedDomain);
          
          // 显示保存成功消息
          this.$message.success('二级域名及证书信息保存成功！');
          
          // 刷新二级域名列表
          await this.initSubDomainCertList();
          
          // 移除当前录入行
          this.subDomainEntries.splice(index, 1);
        } else {
          // 保存失败
          this.$message.error('二级域名及证书信息保存失败：' + result.message);
        }
      } catch (error) {
        console.error('保存二级域名及证书信息时发生错误:', error);
        this.$message.error('二级域名及证书信息保存失败：网络错误或服务器异常');
      }
    },
    
    /**
     * 初始化二级域名及证书列表
     */
    async initSubDomainCertList() {
      console.log('初始化二级域名及证书列表');
      
      this.loading.subDomain = true;
      
      try {
        // 调用后端API获取二级域名列表
        const response = await fetch('/get_sub_domains.php', {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
          }
        });
        
        console.log('二级域名响应状态:', response.status);
        console.log('二级域名响应头:', response.headers);
        
        if (!response.ok) {
          throw new Error(`HTTP错误! 状态码: ${response.status}`);
        }
        
        // 获取原始响应文本
        const responseText = await response.text();
        console.log('二级域名原始响应文本:', responseText);
        
        // 检查响应文本是否为空
        if (!responseText.trim()) {
          throw new Error('服务器返回空响应');
        }
        
        // 尝试解析JSON
        const result = JSON.parse(responseText);
        console.log('二级域名解析后的响应:', result);
        
        if (result.success) {
          // 更新内部状态，确保data是数组
          this.subDomains = Array.isArray(result.data) ? result.data : [];
        } else {
          // 处理API返回的错误
          this.$message.error('获取二级域名列表失败: ' + (result.message || '未知错误'));
          this.subDomains = [];
        }
      } catch (error) {
        console.error('获取二级域名列表时发生错误:', error);
        this.$message.error('获取二级域名列表失败: ' + error.message);
        // 确保subDomains是数组
        this.subDomains = [];
      } finally {
        this.loading.subDomain = false;
      }
    },
    
    /**
     * 编辑二级域名
     */
    editSubDomain(row) {
      console.log('编辑二级域名:', row);
      // 实现编辑功能
      this.$message.info('编辑功能开发中');
    },
    
    /**
     * 删除二级域名
     */
    deleteSubDomain(id) {
      console.log('删除二级域名:', id);
      // 实现删除功能
      this.$confirm('确定要删除这条二级域名及证书信息吗？', '删除确认', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(async () => {
        try {
          // 调用后端API删除二级域名
          const response = await fetch('/delete_sub_domain.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id })
          });
          
          // 检查响应状态
          if (!response.ok) {
            throw new Error(`HTTP错误：${response.status} ${response.statusText}`);
          }
          
          // 检查响应内容类型
          const contentType = response.headers.get('content-type');
          if (contentType && contentType.includes('application/json')) {
            // 读取响应文本，先检查是否为空
            const responseText = await response.text();
            if (!responseText.trim()) {
              throw new Error('服务器返回空响应');
            }
            
            // 尝试解析JSON
            const result = JSON.parse(responseText);
            if (result.success) {
              // 删除成功，更新内部状态
              this.subDomains = this.subDomains.filter(domain => domain.id !== id);
              this.$message.success('二级域名删除成功！');
            } else {
              this.$message.error('二级域名删除失败：' + result.message);
            }
          } else {
            // 非JSON响应，读取文本内容并抛出错误
            const responseText = await response.text();
            throw new Error(`服务器返回非JSON响应：${responseText.substring(0, 100)}...`);
          }
        } catch (error) {
          console.error('删除二级域名时发生错误:', error);
          this.$message.error('二级域名删除失败：网络错误或服务器异常');
        }
      }).catch(() => {
        this.$message.info('已取消删除');
      });
    },
    
    /**
     * 获取证书状态对应的标签类型
     */
    getCertStatusType(status) {
      // 支持中文和英文状态值
      const statusTypes = {
        'valid': 'success',
        'expired': 'danger',
        'expiring': 'warning',
        'invalid': 'info',
        '有效': 'success',
        '已过期': 'danger',
        '即将过期': 'warning',
        '无效': 'info'
      };
      return statusTypes[status] || 'info';
    },
    
    /**
     * 获取证书状态对应的文本
     */
    getCertStatusText(status) {
      // 支持中文和英文状态值
      const statusTexts = {
        'valid': '有效',
        'expired': '已过期',
        'expiring': '即将过期',
        'invalid': '无效',
        '有效': '有效',
        '已过期': '已过期',
        '即将过期': '即将过期',
        '无效': '无效'
      };
      return statusTexts[status] || '未知';
    }
  }
};
</script>

<style scoped>
/* 视图特定样式 */
.domain-cert-entry-view {
  padding: 0;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.text-bold {
  font-weight: bold;
}

.text-danger {
  color: #f56c6c;
}

.text-muted {
  color: #909399;
}

.mt-5 {
  margin-top: 30px;
}

.mb-4 {
  margin-bottom: 20px;
}

.mt-2 {
  margin-top: 10px;
}

.mb-2 {
  margin-bottom: 10px;
}

.block {
  display: block;
}

/* 操作按钮样式，与信息查询模块保持一致 */
.action-buttons {
  display: flex;
  gap: 10px;
}
</style>