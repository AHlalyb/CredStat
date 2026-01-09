<template>
  <div class="settings-view">
    <el-card class="mt-5" shadow="hover">
      <template #header>
        <div class="card-header">
          <h3 class="m-0">数据库设置</h3>
        </div>
      </template>
      
      <div class="el-card__body">
        <!-- 消息提示 -->
        <el-alert
          v-if="messageVisible"
          :title="messageText"
          :type="messageType"
          show-icon
          class="mb-4"
        ></el-alert>
        
        <el-form label-position="top" label-width="120px">
          <el-row :gutter="[20, 20]">
            <el-col :xs="24" :sm="12" :md="6">
              <el-form-item label="数据库主机地址" required>
                <el-input
                  v-model="dbSettings.host"
                  placeholder="例如: localhost 或 IP 地址"
                ></el-input>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6">
              <el-form-item label="数据库端口" required>
                <el-input-number
                  v-model="dbSettings.port"
                  :min="1"
                  :max="65535"
                  placeholder="例如: 3306"
                ></el-input-number>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6">
              <el-form-item label="数据库名称" required>
                <el-input
                  v-model="dbSettings.dbname"
                  placeholder="例如: credstat"
                ></el-input>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6">
              <el-form-item label="数据库用户名" required>
                <el-input
                  v-model="dbSettings.username"
                  placeholder="例如: root"
                ></el-input>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6">
              <el-form-item label="数据库密码">
                <el-input
                  v-model="dbSettings.password"
                  type="password"
                  placeholder="数据库密码"
                  show-password
                ></el-input>
              </el-form-item>
            </el-col>
          </el-row>
          <el-row>
            <el-col :span="24" class="text-right">
              <el-button @click="testConnection" type="warning" size="default" :loading="testingConnection">
                <el-icon v-if="testingConnection"><Loading /></el-icon>
                <el-icon v-else><Connection /></el-icon> 测试连接
              </el-button>
              <el-button @click="saveSettings" type="primary" size="default" :loading="savingSettings">
                <el-icon v-if="savingSettings"><Loading /></el-icon>
                <el-icon v-else><Check /></el-icon> 保存设置
              </el-button>
            </el-col>
          </el-row>
        </el-form>
      </div>
    </el-card>
  </div>
</template>

<script>
// 导入Element Plus图标
import { Loading, Connection, Check } from '@element-plus/icons-vue';

export default {
  name: 'DatabaseSettingsView',
  components: {
    Loading,
    Connection,
    Check
  },
  data() {
    return {
      // 消息提示
      messageVisible: false,
      messageText: '',
      messageType: 'info',
      // 设置表单数据
      dbSettings: {
        host: 'localhost',
        port: 3306,
        dbname: 'credstat',
        username: 'root',
        password: ''
      },
      // 按钮加载状态
      testingConnection: false,
      savingSettings: false
    };
  },
  methods: {
    // 显示消息提示
    showMessage(message, type = 'info') {
      this.messageVisible = true;
      this.messageText = message;
      this.messageType = type;
      
      // 3秒后自动隐藏消息
      setTimeout(() => {
        this.messageVisible = false;
      }, 3000);
    },
    
    // 测试数据库连接
    testConnection() {
      this.testingConnection = true;
      
      // 模拟测试连接
      setTimeout(() => {
        this.testingConnection = false;
        this.showMessage('数据库连接测试成功！', 'success');
      }, 1500);
    },
    
    // 保存数据库设置
    saveSettings() {
      this.savingSettings = true;
      
      // 模拟保存设置
      setTimeout(() => {
        this.savingSettings = false;
        this.showMessage('数据库设置保存成功！', 'success');
      }, 1500);
    }
  }
}
</script>

<style scoped>
/* 视图特定样式 */
.settings-view {
  padding: 0 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>