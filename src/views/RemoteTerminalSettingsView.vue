<template>
  <div class="remote-terminal-settings">
    <el-card class="mt-5" shadow="hover">
      <template #header>
        <div class="card-header">
          <h3 class="m-0">远程终端设置</h3>
          <el-button type="primary" size="default" @click="saveConfig" :loading="saving">
            <el-icon v-if="!saving"><Check /></el-icon>
            <el-icon v-else><Loading /></el-icon>
            保存设置
          </el-button>
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
          closable
          @close="messageVisible = false"
        ></el-alert>

        <el-form label-position="top" label-width="120px" style="max-width: 720px;">
          <el-form-item label="远程终端软件" required>
            <el-radio-group v-model="form.software">
              <el-radio value="putty">PuTTY</el-radio>
              <el-radio value="crt">SecureCRT (CRT)</el-radio>
            </el-radio-group>
            <div class="el-form-item__help">选择点击"远程"时调用的终端软件</div>
          </el-form-item>

          <el-form-item v-if="form.software === 'putty'" label="PuTTY 软件路径" required>
            <el-input
              v-model="form.putty_path"
              placeholder="例如：C:\tools\putty.exe"
              clearable
            ></el-input>
            <div class="el-form-item__help">填写 putty.exe 的完整路径</div>
          </el-form-item>

          <el-form-item v-else label="SecureCRT 软件路径" required>
            <el-input
              v-model="form.crt_path"
              placeholder="例如：C:\Program Files\VanDyke Software\SecureCRT\SecureCRT.exe"
              clearable
            ></el-input>
            <div class="el-form-item__help">填写 SecureCRT.exe 的完整路径</div>
          </el-form-item>
        </el-form>

        <el-divider></el-divider>

        <!-- 测试连接 -->
        <h4 class="mt-0 mb-3">测试连接</h4>
        <el-form inline label-position="top" style="max-width: 720px;">
          <el-form-item label="设备IP" required>
            <el-input
              v-model="testForm.ip"
              placeholder="例如：192.168.1.1"
              clearable
              style="width: 220px;"
            ></el-input>
          </el-form-item>
          <el-form-item label="端口">
            <el-input
              v-model="testForm.port"
              placeholder="例如：22"
              clearable
              style="width: 120px;"
            ></el-input>
          </el-form-item>
          <el-form-item label="协议" required>
            <el-select v-model="testForm.protocol" style="width: 130px;">
              <el-option label="SSH" value="ssh"></el-option>
              <el-option label="Telnet" value="telnet"></el-option>
            </el-select>
          </el-form-item>
          <el-form-item label="用户名">
            <el-input
              v-model="testForm.username"
              placeholder="可选"
              clearable
              style="width: 150px;"
            ></el-input>
          </el-form-item>
          <el-form-item label=" ">
            <el-button type="success" @click="testLaunch" :loading="testing">
              <el-icon v-if="!testing"><Connection /></el-icon>
              <el-icon v-else><Loading /></el-icon>
              启动连接
            </el-button>
          </el-form-item>
        </el-form>

        <!-- 使用说明 -->
        <el-alert title="使用说明" type="info" show-icon class="mt-4" :closable="false">
          <template #default>
            <p class="m-0">1. 先选择远程终端软件（PuTTY 或 SecureCRT），并填写对应软件的完整路径</p>
            <p class="m-0">2. 点击"保存设置"，信息查询页面的"远程"按钮将按此配置启动终端软件</p>
            <p class="m-0">3. 可在"测试连接"区域输入 IP/端口/协议/用户名，点击"启动连接"验证配置是否正确</p>
            <p class="m-0">4. 路径填写后系统会在启动时校验软件是否存在</p>
          </template>
        </el-alert>
      </div>
    </el-card>
  </div>
</template>

<script>
import { Check, Loading, Connection } from '@element-plus/icons-vue';

export default {
  name: 'RemoteTerminalSettingsView',
  components: {
    Check,
    Loading,
    Connection
  },
  data() {
    return {
      // 表单数据
      form: {
        software: 'putty',
        putty_path: '',
        crt_path: ''
      },
      saving: false,
      // 测试连接
      testForm: {
        ip: '',
        port: '',
        protocol: 'ssh',
        username: ''
      },
      testing: false,
      // 消息提示
      messageVisible: false,
      messageText: '',
      messageType: 'info'
    };
  },
  mounted() {
    this.loadConfig();
  },
  methods: {
    // 显示消息
    showMessage(text, type = 'info') {
      this.messageText = text;
      this.messageType = type;
      this.messageVisible = true;
    },
    // 加载配置
    async loadConfig() {
      try {
        const response = await fetch('remote_terminal_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ action: 'getConfig' })
        });
        const data = await response.json();
        if (data.success && data.config) {
          this.form.software = data.config.software || 'putty';
          this.form.putty_path = data.config.putty_path || '';
          this.form.crt_path = data.config.crt_path || '';
        }
      } catch (error) {
        console.error('加载远程终端配置失败:', error);
      }
    },
    // 保存配置
    async saveConfig() {
      // 校验路径
      const path = this.form.software === 'putty' ? this.form.putty_path : this.form.crt_path;
      if (!path || path.trim() === '') {
        this.showMessage('请填写 ' + (this.form.software === 'putty' ? 'PuTTY' : 'SecureCRT') + ' 软件的完整路径', 'warning');
        return;
      }

      this.saving = true;
      try {
        const response = await fetch('remote_terminal_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'saveConfig',
            software: this.form.software,
            putty_path: this.form.putty_path,
            crt_path: this.form.crt_path
          })
        });
        const data = await response.json();
        if (data.success) {
          this.form.software = data.config.software;
          this.form.putty_path = data.config.putty_path;
          this.form.crt_path = data.config.crt_path;
          this.showMessage('远程终端设置保存成功', 'success');
        } else {
          this.showMessage('保存失败：' + (data.message || '未知错误'), 'error');
        }
      } catch (error) {
        console.error('保存远程终端配置失败:', error);
        this.showMessage('保存失败，请稍后重试', 'error');
      } finally {
        this.saving = false;
      }
    },
    // 测试启动连接
    async testLaunch() {
      if (!this.testForm.ip || this.testForm.ip.trim() === '') {
        this.showMessage('请输入设备IP', 'warning');
        return;
      }
      this.testing = true;
      try {
        const response = await fetch('remote_terminal_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'launch',
            ip: this.testForm.ip.trim(),
            port: this.testForm.port.trim(),
            protocol: this.testForm.protocol,
            username: this.testForm.username.trim()
          })
        });
        const data = await response.json();
        if (data.success) {
          this.showMessage(data.message || '连接已启动', 'success');
        } else {
          this.showMessage(data.message || '启动失败', 'error');
        }
      } catch (error) {
        console.error('启动远程连接失败:', error);
        this.showMessage('启动失败，请稍后重试', 'error');
      } finally {
        this.testing = false;
      }
    }
  }
};
</script>

<style scoped>
.remote-terminal-settings :deep(.el-form-item__help) {
  font-size: 12px;
  color: #909399;
}
</style>
