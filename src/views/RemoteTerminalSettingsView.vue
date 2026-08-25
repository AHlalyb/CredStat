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

        <!-- 本机协议助手安装指引 -->
        <el-alert type="warning" show-icon class="mb-4" :closable="false">
          <template #title>
            <b>重要：请先在本机安装「远程终端协议助手」</b>
          </template>
          <template #default>
            <p class="m-0 mb-1">
              远程终端软件运行在<b>你的电脑</b>上，而本系统部署在服务器上，因此需要在本机注册
              <code>crt://</code> / <code>putty://</code> 协议，让网页能直接调起本机终端软件（每台管理电脑只需安装一次）。
            </p>
            <p class="m-0">
              下载以下 3 个文件放到本机同一文件夹（例如
              <code>D:\TermTool</code>），右键管理员运行
              <code>install_terminal_protocol.bat</code>，然后编辑
              <code>terminal_config.ini</code> 填写本机软件路径即可：
            </p>
            <div class="mt-2">
              <el-button size="small" type="primary" tag="a" href="terminal_protocol/install_terminal_protocol.bat" download>
                下载安装脚本 .bat
              </el-button>
              <el-button size="small" tag="a" href="terminal_protocol/terminal_launcher.vbs" download>
                下载协议处理器 .vbs
              </el-button>
              <el-button size="small" tag="a" href="terminal_protocol/terminal_config.ini" download>
                下载模板配置 .ini
              </el-button>
              <el-button size="small" type="warning" @click="downloadConfig" :disabled="!form.crt_path && !form.putty_path">
                下载我的配置 .ini（含上方已填路径）
              </el-button>
            </div>
            <p class="m-0 mb-1">
              <b>提示：</b>推荐先在上方填写本机软件路径并保存，然后点「下载我的配置 .ini」生成配置，用其覆盖
              <code>D:\TermTool</code> 下的 <code>terminal_config.ini</code>，避免手动编辑导致的编码/路径错误。
            </p>
          </template>
        </el-alert>

        <el-form label-position="top" label-width="120px" style="max-width: 720px;">
          <el-form-item label="远程终端软件" required>
            <el-radio-group v-model="form.software">
              <el-radio value="putty">PuTTY</el-radio>
              <el-radio value="crt">SecureCRT (CRT)</el-radio>
            </el-radio-group>
            <div class="el-form-item__help">选择点击"远程"时调用的终端软件（需与本机 terminal_config.ini 中 software 一致）</div>
          </el-form-item>

          <el-form-item v-if="form.software === 'putty'" label="PuTTY 软件路径" required>
            <el-input
              v-model="form.putty_path"
              placeholder="例如：C:\tools\putty.exe"
              clearable
            ></el-input>
            <div class="el-form-item__help">填写本机 putty.exe 的完整路径（保存在当前浏览器中，不上传服务器）</div>
          </el-form-item>

          <el-form-item v-else label="SecureCRT 软件路径" required>
            <el-input
              v-model="form.crt_path"
              placeholder="例如：C:\Program Files\VanDyke Software\SecureCRT\SecureCRT.exe"
              clearable
            ></el-input>
            <div class="el-form-item__help">填写本机 SecureCRT.exe 的完整路径（保存在当前浏览器中，不上传服务器）</div>
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
            <el-button type="success" @click="testLaunch">
              <el-icon><Connection /></el-icon>
              启动连接
            </el-button>
          </el-form-item>
        </el-form>

        <!-- 使用说明 -->
        <el-alert title="使用说明" type="info" show-icon class="mt-4" :closable="false">
          <template #default>
            <p class="m-0">1. 本机安装并注册「远程终端协议助手」（见上方黄色提示，每台电脑一次）</p>
            <p class="m-0">2. 在本页选择终端软件并填写本机路径，点击"保存设置"（仅保存在当前浏览器）</p>
            <p class="m-0">3. 信息查询页点击"远程"，网页将调起本机已注册的协议助手启动终端软件</p>
            <p class="m-0">4. 可在"测试连接"区域输入 IP/端口/协议/用户名，点击"启动连接"验证</p>
            <p class="m-0">5. 更换电脑后需在新电脑上重新安装协议助手并保存路径</p>
          </template>
        </el-alert>
      </div>
    </el-card>
  </div>
</template>

<script>
import { Check, Loading, Connection } from '@element-plus/icons-vue';

const STORAGE_KEY = 'remoteTerminalConfig';

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
    // 从本机浏览器读取配置
    getLocalConfig() {
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        return raw ? JSON.parse(raw) : null;
      } catch (error) {
        return null;
      }
    },
    // 加载配置：优先本机浏览器，其次服务器（兼容服务器本机使用场景）
    async loadConfig() {
      const local = this.getLocalConfig();
      if (local) {
        this.form.software = local.software || 'putty';
        this.form.putty_path = local.putty_path || '';
        this.form.crt_path = local.crt_path || '';
        return;
      }
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
    // 保存配置到本机浏览器
    async saveConfig() {
      const path = this.form.software === 'putty' ? this.form.putty_path : this.form.crt_path;
      if (!path || path.trim() === '') {
        this.showMessage('请填写 ' + (this.form.software === 'putty' ? 'PuTTY' : 'SecureCRT') + ' 软件的完整路径', 'warning');
        return;
      }

      this.saving = true;
      try {
        const config = {
          software: this.form.software,
          putty_path: this.form.putty_path.trim(),
          crt_path: this.form.crt_path.trim()
        };
        // 1. 保存到本机浏览器（主要方式，远程调用依赖它）
        localStorage.setItem(STORAGE_KEY, JSON.stringify(config));
        // 2. 同步到服务器（仅用于服务器本机直接使用的场景，失败不影响）
        try {
          await fetch('remote_terminal_api.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ action: 'saveConfig', ...config })
          });
        } catch (error) {
          // 忽略服务器同步失败
        }
        this.showMessage('已保存到本机浏览器（当前电脑有效）', 'success');
      } catch (error) {
        console.error('保存远程终端配置失败:', error);
        this.showMessage('保存失败，请稍后重试', 'error');
      } finally {
        this.saving = false;
      }
    },
    // 生成并下载本机配置文件（UTF-8 编码，与协议助手 VBS 的读取方式一致，避免乱码）
    downloadConfig() {
      const path = this.form.software === 'putty' ? this.form.putty_path : this.form.crt_path;
      if (!path || path.trim() === '') {
        this.showMessage('请先填写 ' + (this.form.software === 'putty' ? 'PuTTY' : 'SecureCRT') + ' 软件的完整路径', 'warning');
        return;
      }
      const iniContent =
        'software=' + (this.form.software === 'putty' ? 'putty' : 'crt') + '\r\n' +
        'crt_path=' + this.form.crt_path.trim() + '\r\n' +
        'putty_path=' + this.form.putty_path.trim() + '\r\n';
      // Blob 默认以 UTF-8 编码生成文本文件
      const blob = new Blob([iniContent], { type: 'text/plain;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'terminal_config.ini';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
      this.showMessage('已生成 terminal_config.ini（UTF-8），请用它覆盖本机 D:\\TermTool 下的同名文件', 'success');
    },
    // 通过 a.click + 隐藏 iframe 双重触发本机自定义协议，调起终端软件
    launchLocalProtocol(url) {
      try {
        const link = document.createElement('a');
        link.href = url;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        if (link.parentNode) link.parentNode.removeChild(link);
      } catch (e) {
        console.error('a 标签调起协议失败:', e);
      }
      try {
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = url;
        document.body.appendChild(iframe);
        setTimeout(() => {
          if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
        }, 2000);
      } catch (e) {
        console.error('iframe 调起协议失败:', e);
      }
    },
    // 测试启动连接（走本机协议助手）
    testLaunch() {
      if (!this.testForm.ip || this.testForm.ip.trim() === '') {
        this.showMessage('请输入设备IP', 'warning');
        return;
      }
      const local = this.getLocalConfig();
      if (!local) {
        this.showMessage('请先在上方保存本机终端软件路径，并在本机安装「远程终端协议助手」', 'warning');
        return;
      }
      const scheme = local.software === 'crt' ? 'crt' : 'putty';
      // 分隔符 | 显式编码为 %7C，避免浏览器/系统对 URL 特殊字符的处理差异
      const url = `${scheme}://${this.testForm.protocol}%7C${encodeURIComponent(this.testForm.ip.trim())}%7C${encodeURIComponent(this.testForm.port.trim())}%7C${encodeURIComponent(this.testForm.username.trim())}`;
      this.launchLocalProtocol(url);
      this.showMessage('已调起本机终端软件连接 ' + this.testForm.ip.trim(), 'success');
    }
  }
};
</script>

<style scoped>
.remote-terminal-settings :deep(.el-form-item__help) {
  font-size: 12px;
  color: #909399;
}
.mb-1 {
  margin-bottom: 6px;
}
.mt-2 {
  margin-top: 10px;
}
</style>
