<template>
  <div class="cluster-entry-view">
    <el-card class="mt-5" shadow="hover">
      <template #header>
        <div class="card-header">
          <h3 class="m-0">宿主机集群录入</h3>
        </div>
      </template>
      
      <div class="el-card__body">
      <el-form
        ref="clusterForm"
        :model="formData"
        :rules="formRules"
        label-position="top"
        label-width="100px"
        size="default"
        autocomplete="off"
      >
        <!-- 集群基础信息 -->
        <div class="mb-4">
          <h6 class="text-bold mb-3">集群基础信息</h6>
          <el-row :gutter="[20, 20]">
            <el-col :xs="24" :sm="12" :md="6" :lg="6">
              <el-form-item label="集群名称" prop="clusterName" required>
                <el-input
                  v-model="formData.clusterName"
                  placeholder="请输入集群名称"
                ></el-input>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6" :lg="6">
              <el-form-item label="集群地址" prop="clusterAddress" required>
                <el-input
                  v-model="formData.clusterAddress"
                  placeholder="请输入集群地址"
                ></el-input>
              </el-form-item>
            </el-col>
            <!-- 隐藏的诱饵字段，用于防止浏览器自动填充 -->
            <div class="autofill-bait" style="position: absolute; left: -9999px; top: -9999px;">
              <input type="text" name="username" autocomplete="off" />
              <input type="password" name="password" autocomplete="off" />
            </div>
            
            <el-col :xs="24" :sm="12" :md="6" :lg="6">
              <el-form-item label="集群用户名" prop="clusterUsername" required>
                <el-input
                  v-model="formData.clusterUsername"
                  placeholder="请输入集群用户名"
                  autocomplete="new-username"
                  :name="'random-username-' + Math.random().toString(36).substring(2, 15)"
                  readonly
                  @focus="$event.target.removeAttribute('readonly')"
                  :key="clusterUsernameKey"
                ></el-input>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6" :lg="6">
              <el-form-item label="集群密码" prop="clusterPassword" required>
                <el-input
                  v-model="formData.clusterPassword"
                  type="password"
                  placeholder="请输入集群密码"
                  show-password
                  autocomplete="new-password"
                  :name="'random-password-' + Math.random().toString(36).substring(2, 15)"
                  readonly
                  @focus="$event.target.removeAttribute('readonly')"
                  :key="clusterPasswordKey"
                ></el-input>
              </el-form-item>
            </el-col>
          </el-row>
        </div>
        
        <!-- 物理机信息组 -->
        <div class="mb-4">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h6 style="font-weight: bold;">物理机信息</h6>
            <el-button type="primary" @click="addPhysicalMachine" :icon="Plus" size="small">
              添加物理机
            </el-button>
          </div>
          
          <!-- 物理机信息表格 -->
          <div class="physical-machines-table">
            <el-table
              :data="formData.physicalMachines"
              style="width: 100%"
              border
              stripe
              :header-cell-style="{backgroundColor: '#f5f7fa', color: '#606266', fontWeight: 'bold', textAlign: 'center'}"
              :cell-style="{textAlign: 'center'}"
            >
              <el-table-column prop="pmName" label="物理机名称" min-width="120">
                <template #default="{ row, $index }">
                  <el-form-item
                    :prop="`physicalMachines.${$index}.pmName`"
                    :rules="formRules['physicalMachines.*.pmName']"
                    style="margin-bottom: 0;"
                  >
                    <el-input
                      v-model="row.pmName"
                      placeholder="物理机名称"
                      size="small"
                    ></el-input>
                  </el-form-item>
                </template>
              </el-table-column>
              
              <el-table-column prop="pmIp" label="物理机IP" min-width="140">
                <template #default="{ row, $index }">
                  <el-form-item
                    :prop="`physicalMachines.${$index}.pmIp`"
                    :rules="formRules['physicalMachines.*.pmIp']"
                    style="margin-bottom: 0;"
                  >
                    <el-input
                      v-model="row.pmIp"
                      placeholder="物理机IP"
                      size="small"
                    ></el-input>
                  </el-form-item>
                </template>
              </el-table-column>
              
              <el-table-column prop="pmUsername" label="物理机用户名" min-width="130">
                <template #default="{ row, $index }">
                  <el-form-item
                    :prop="`physicalMachines.${$index}.pmUsername`"
                    :rules="formRules['physicalMachines.*.pmUsername']"
                    style="margin-bottom: 0;"
                  >
                    <el-input
                      v-model="row.pmUsername"
                      placeholder="物理机用户名"
                      autocomplete="new-pm-username"
                      :name="'random-pm-username-' + $index + '-' + Math.random().toString(36).substring(2, 15)"
                      readonly
                      @focus="$event.target.removeAttribute('readonly')"
                      :key="row.pmUsernameKey"
                      size="small"
                    ></el-input>
                  </el-form-item>
                </template>
              </el-table-column>
              
              <el-table-column prop="pmPassword" label="物理机密码" min-width="140">
                <template #default="{ row, $index }">
                  <el-form-item
                    :prop="`physicalMachines.${$index}.pmPassword`"
                    :rules="formRules['physicalMachines.*.pmPassword']"
                    style="margin-bottom: 0;"
                  >
                    <el-input
                      v-model="row.pmPassword"
                      type="password"
                      placeholder="物理机密码"
                      show-password
                      autocomplete="new-pm-password"
                      :name="'random-pm-password-' + $index + '-' + Math.random().toString(36).substring(2, 15)"
                      readonly
                      @focus="$event.target.removeAttribute('readonly')"
                      :key="row.pmPasswordKey"
                      size="small"
                    ></el-input>
                  </el-form-item>
                </template>
              </el-table-column>
              
              <el-table-column prop="pmBmcIp" label="BMC IP" min-width="120">
                <template #default="{ row, $index }">
                  <el-form-item
                    :prop="`physicalMachines.${$index}.pmBmcIp`"
                    style="margin-bottom: 0;"
                  >
                    <el-input
                      v-model="row.pmBmcIp"
                      placeholder="BMC IP"
                      size="small"
                    ></el-input>
                  </el-form-item>
                </template>
              </el-table-column>
              
              <el-table-column prop="pmBmcUsername" label="BMC用户名" min-width="120">
                <template #default="{ row, $index }">
                  <el-form-item
                    :prop="`physicalMachines.${$index}.pmBmcUsername`"
                    style="margin-bottom: 0;"
                  >
                    <el-input
                      v-model="row.pmBmcUsername"
                      placeholder="BMC用户名"
                      autocomplete="new-pm-bmc-username"
                      :name="'random-pm-bmc-username-' + $index + '-' + Math.random().toString(36).substring(2, 15)"
                      readonly
                      @focus="$event.target.removeAttribute('readonly')"
                      :key="row.pmBmcUsernameKey"
                      size="small"
                    ></el-input>
                  </el-form-item>
                </template>
              </el-table-column>
              
              <el-table-column prop="pmBmcPassword" label="BMC密码" min-width="140">
                <template #default="{ row, $index }">
                  <el-form-item
                    :prop="`physicalMachines.${$index}.pmBmcPassword`"
                    style="margin-bottom: 0;"
                  >
                    <el-input
                      v-model="row.pmBmcPassword"
                      type="password"
                      placeholder="BMC密码"
                      show-password
                      autocomplete="new-pm-bmc-password"
                      :name="'random-pm-bmc-password-' + $index + '-' + Math.random().toString(36).substring(2, 15)"
                      readonly
                      @focus="$event.target.removeAttribute('readonly')"
                      :key="row.pmBmcPasswordKey"
                      size="small"
                    ></el-input>
                  </el-form-item>
                </template>
              </el-table-column>
              
              <el-table-column label="操作" min-width="100" fixed="right">
                <template #default="{ $index }">
                  <el-button
                    type="danger"
                    size="small"
                    @click="removePhysicalMachine($index)"
                    :icon="Delete"
                    circle
                    title="删除"
                  ></el-button>
                </template>
              </el-table-column>
            </el-table>
          </div>
        </div>
        
        <!-- 表单操作按钮 -->
        <el-form-item>
          <el-button @click="resetForm" :icon="RefreshRight">重置</el-button>
          <el-button type="warning" @click="fillTestData" :icon="EditPen">一键填充测试数据</el-button>
          <el-button type="primary" @click="saveCluster" :icon="Check">保存信息</el-button>
        </el-form-item>
      </el-form>
      </div>
    </el-card>
  </div>
</template>

<script>
// 导入Element Plus图标
import { Plus, Delete, RefreshRight, Check, EditPen } from '@element-plus/icons-vue';
// 导入Element Plus组件
import { ElMessage, ElLoading } from 'element-plus';

export default {
  name: 'ClusterEntryView',
  components: {
    Plus,
    Delete,
    RefreshRight,
    Check,
    EditPen
  },
  data() {
    return {
      // 表单引用
      clusterForm: null,
      // 表单数据
      formData: {
        clusterName: '',
        clusterAddress: '',
        clusterUsername: '',
        clusterPassword: '',
        // 物理机列表 - 整合到formData中
        physicalMachines: [
          {
            id: 'pm_1',
            pmName: '物理机1',
            pmIp: '172.27.2.1',
            pmUsername: 'root',
            pmPassword: 'XDak@la_8274',
            pmBmcIp: '172.168.1.31',
            pmBmcUsername: 'root',
            pmBmcPassword: 'zHb2Z~xwrm28',
            pmUsernameKey: Date.now(),
            pmPasswordKey: Date.now(),
            pmBmcUsernameKey: Date.now(),
            pmBmcPasswordKey: Date.now()
          }
        ]
      },
      // 用于防止自动填充的key，每次重置表单时更新
      clusterUsernameKey: Date.now(),
      clusterPasswordKey: Date.now(),
      // 表单验证规则 - 添加物理机字段的验证规则
      formRules: {
        clusterName: [{ required: true, message: '请输入集群名称', trigger: 'blur' }],
        clusterAddress: [{ required: true, message: '请输入集群地址', trigger: 'blur' }],
        clusterUsername: [{ required: true, message: '请输入集群用户名', trigger: 'blur' }],
        clusterPassword: [{ required: true, message: '请输入集群密码', trigger: 'blur' }],
        // 物理机字段验证规则 - 验证每台物理机的必填字段
        'physicalMachines.*.pmName': [{ required: true, message: '请输入物理机名称', trigger: 'blur' }],
        'physicalMachines.*.pmIp': [{ required: true, message: '请输入物理机IP', trigger: 'blur' }],
        'physicalMachines.*.pmUsername': [{ required: true, message: '请输入物理机用户名', trigger: 'blur' }],
        'physicalMachines.*.pmPassword': [{ required: true, message: '请输入物理机密码', trigger: 'blur' }]
      }
    };
  },
  mounted() {
  },
  methods: {
    // 添加物理机
    addPhysicalMachine() {
      const nextNumber = this.formData.physicalMachines.length + 1;
      this.formData.physicalMachines.push({
        id: 'pm_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
        pmName: '物理机' + nextNumber,
        pmIp: '',
        pmUsername: '',
        pmPassword: '',
        pmBmcIp: '',
        pmBmcUsername: '',
        pmBmcPassword: '',
        pmUsernameKey: Date.now(),
        pmPasswordKey: Date.now(),
        pmBmcUsernameKey: Date.now(),
        pmBmcPasswordKey: Date.now()
      });
    },
    
    // 删除物理机
    removePhysicalMachine(index) {
      if (this.formData.physicalMachines.length > 1) {
        this.formData.physicalMachines.splice(index, 1);
      } else {
        this.$message.warning('至少需要保留一台物理机');
      }
    },
    
    // 重置表单
    resetForm() {
      if (this.$refs.clusterForm) {
        this.$refs.clusterForm.resetFields();
      }
      // 更新key值，强制重新渲染输入框，清除浏览器自动填充
      this.clusterUsernameKey = Date.now();
      this.clusterPasswordKey = Date.now();
      
      // 重置物理机列表，保留一个空的物理机
      this.formData.physicalMachines = [
        {
          id: 'pm_1',
          pmName: '物理机1',
          pmIp: '',
          pmUsername: '',
          pmPassword: '',
          pmBmcIp: '',
          pmBmcUsername: '',
          pmBmcPassword: '',
          pmUsernameKey: Date.now(),
          pmPasswordKey: Date.now(),
          pmBmcUsernameKey: Date.now(),
          pmBmcPasswordKey: Date.now()
        }
      ];
    },
    
    // 保存集群信息
    async saveCluster() {
      if (this.$refs.clusterForm) {
        this.$refs.clusterForm.validate(async (valid) => {
          if (valid) {
            try {
              // 验证物理机信息
              const validationResult = this.validatePhysicalMachines();
              if (!validationResult.valid) {
                ElMessage.error(validationResult.message);
                return;
              }
              
              // 显示加载状态
              const loading = ElLoading.service({
                lock: true,
                text: '保存中...',
                spinner: 'el-icon-loading',
                background: 'rgba(0, 0, 0, 0.7)'
              });
              
              // 获取当前用户信息
              let currentUser = null;
              try {
                const userInfo = localStorage.getItem('currentUser');
                if (userInfo) {
                  currentUser = JSON.parse(userInfo);
                }
              } catch (error) {
                throw new Error('获取用户信息失败');
              }
              
              // 构建请求头
              const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
              };
              
              if (currentUser && currentUser.username) {
                headers['X-Username'] = currentUser.username;
              }
              
              // 构建完整的请求数据
              const requestData = {
                clusterName: this.formData.clusterName,
                clusterAddress: this.formData.clusterAddress,
                clusterUsername: this.formData.clusterUsername,
                clusterPassword: this.formData.clusterPassword,
                physicalMachines: this.formData.physicalMachines
              };
              
              // 发送API请求
              const response = await fetch('/save_cluster.php', {
                method: 'POST',
                headers: headers,
                body: JSON.stringify(requestData)
              });
              
              const result = await response.json();
              
              if (!result.success) {
                throw new Error(result.message || '保存失败');
              }
              
              // 隐藏加载状态
              loading.close();
              
              // 保存成功
              ElMessage.success('保存成功！');
              this.resetForm();
            } catch (error) {
              ElMessage.error('保存失败: ' + error.message);
            }
          }
        });
      }
    },
    
    // 验证物理机信息
    validatePhysicalMachines() {
      for (let i = 0; i < this.formData.physicalMachines.length; i++) {
        const machine = this.formData.physicalMachines[i];
        if (!machine.pmName.trim()) {
          return {
            valid: false,
            message: `请输入第 ${i + 1} 台物理机的名称`
          };
        }
        
        if (!machine.pmIp.trim()) {
          return {
            valid: false,
            message: `请输入第 ${i + 1} 台物理机的IP地址`
          };
        }
        
        // IP地址格式验证
        if (!this.isValidIp(machine.pmIp)) {
          return {
            valid: false,
            message: `第 ${i + 1} 台物理机的IP地址格式不正确`
          };
        }
        
        if (!machine.pmUsername.trim()) {
          return {
            valid: false,
            message: `请输入第 ${i + 1} 台物理机的用户名`
          };
        }
        
        if (!machine.pmPassword) {
          return {
            valid: false,
            message: `请输入第 ${i + 1} 台物理机的密码`
          };
        }
      }
      
      return {
        valid: true,
        message: ''
      };
    },
    
    // IP地址格式验证
    isValidIp(ip) {
      const ipRegex = /^((25[0-5]|(2[0-4]|1\d|[1-9]|)\d)\.?\b){4}$/;
      return ipRegex.test(ip);
    },
    
    // 一键填充测试数据
    fillTestData() {
      // 生成随机测试数据
      const randomNum = Math.floor(Math.random() * 1000);
      
      // 填充集群基础信息
      this.formData.clusterName = `测试集群-${randomNum}`;
      this.formData.clusterAddress = `192.168.${Math.floor(Math.random() * 255)}.${Math.floor(Math.random() * 255)}`;
      this.formData.clusterUsername = 'admin';
      this.formData.clusterPassword = 'Test@123456';
      
      // 生成多台物理机测试数据
      const physicalMachines = [];
      const machineCount = Math.floor(Math.random() * 3) + 2; // 生成2-4台物理机
      
      for (let i = 0; i < machineCount; i++) {
        physicalMachines.push({
          id: `pm_${Date.now()}_${i}`,
          pmName: `测试物理机${i + 1}`,
          pmIp: `172.27.2.${i + 1}`,
          pmUsername: 'root',
          pmPassword: `PM_Pass@${i + 1}`,
          pmBmcIp: `172.168.1.${30 + i + 1}`,
          pmBmcUsername: 'root',
          pmBmcPassword: `BMC_Pass@${i + 1}`,
          pmUsernameKey: Date.now(),
          pmPasswordKey: Date.now(),
          pmBmcUsernameKey: Date.now(),
          pmBmcPasswordKey: Date.now()
        });
      }
      
      this.formData.physicalMachines = physicalMachines;
      
      // 显示成功提示
      ElMessage.success('测试数据填充完成');
    }
  }
};
</script>

<style scoped>
/* 视图特定样式 */
.cluster-entry-view {
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

.physical-machines-table {
  margin-top: 15px;
}

.physical-machines-table .el-table {
  border-radius: 4px;
  overflow: hidden;
}

.physical-machines-table .el-table--border {
  border: 1px solid #ebeef5;
}

.physical-machines-table .el-table--border td,
.physical-machines-table .el-table--border th,
.physical-machines-table .el-table__body-wrapper .el-table--border.is-scrolling-left ~ .el-table__fixed {
  border-right: 1px solid #ebeef5;
}

.physical-machines-table .el-table td,
.physical-machines-table .el-table th {
  padding: 10px 0;
  min-height: 42px;
}

.physical-machines-table .el-table .el-form-item {
  margin-bottom: 0;
}

.physical-machines-table .el-table .cell {
  padding: 0 8px;
}

.physical-machines-table .el-input__inner {
  border: 1px solid #dcdfe6;
  border-radius: 4px;
}

.physical-machines-table .el-input__inner:focus {
  border-color: #1770e6;
  outline: none;
}

.physical-machines-table .el-table--stripe .el-table__body tr.el-table__row--striped td {
  background-color: #fafafa;
}

.physical-machines-table .el-table__row:hover > td {
  background-color: #f5f7fa !important;
}

@media (max-width: 768px) {
  .physical-machines-table .el-table {
    font-size: 12px;
  }
  
  .physical-machines-table .el-table td,
  .physical-machines-table .el-table th {
    padding: 6px 0;
    min-height: 36px;
  }
  
  .physical-machines-table .el-button--small.circle {
    padding: 6px;
  }
}

@media (max-width: 480px) {
  .physical-machines-table .el-table {
    font-size: 10px;
  }
  
  .physical-machines-table .el-table td,
  .physical-machines-table .el-table th {
    padding: 4px 0;
    min-height: 32px;
  }
}
</style>