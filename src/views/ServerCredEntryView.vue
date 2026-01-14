<template>
  <div class="server-cred-entry-view">
    <el-card class="mt-5" shadow="hover">
      <template #header>
        <div class="card-header">
          <h3 class="m-0">服务器基本信息录入</h3>
        </div>
      </template>
      
      <div class="el-card__body">
      <el-form
        ref="serverCredFormRef"
        :model="formData"
        :rules="formRules"
        label-position="top"
        label-width="100px"
        size="default"
        autocomplete="off"
      >
        <!-- 第一行：服务器所属网络区域、服务器类型、宿主机集群 -->
        <el-row :gutter="[20, 20]">
          <el-col :xs="24" :sm="12" :md="8" :lg="8">
            <el-form-item label="服务器所属网络区域" prop="server_cred_network_area">
              <el-select
                v-model="formData.server_cred_network_area"
                placeholder="请选择网络区域"
                style="width: 100%"
              >
                <el-option label="内网" value="内网"></el-option>
                <el-option label="DMZ" value="DMZ"></el-option>
              </el-select>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="8" :lg="8">
            <el-form-item label="服务器类型" prop="server_cred_server_type">
              <el-select
                v-model="formData.server_cred_server_type"
                placeholder="请选择服务器类型"
                style="width: 100%"
                @change="toggleHostClusterField"
              >
                <el-option label="物理机" value="物理机"></el-option>
                <el-option label="虚拟机" value="虚拟机"></el-option>
              </el-select>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="8" :lg="8" ref="hostClusterCol">
            <el-form-item label="宿主机集群" prop="server_cred_host_cluster">
              <el-select
                v-model="formData.server_cred_host_cluster"
                placeholder="请选择宿主机集群"
                filterable
                remote
                reserve-keyword
                :remote-method="remoteSearchCluster"
                :loading="clusterLoading"
                clearable
                style="width: 100%"
              >
                <el-option
                  v-for="cluster in clusterOptions"
                  :key="cluster.cluster_name"
                  :label="cluster.cluster_name"
                  :value="cluster.cluster_name"
                ></el-option>
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        
        <!-- 第二行：服务器名称、服务器IP -->
        <el-row :gutter="[20, 20]">
          <el-col :xs="24" :sm="12" :md="12" :lg="12">
            <el-form-item label="服务器名称" prop="server_cred_server_name">
              <el-input
                v-model="formData.server_cred_server_name"
                placeholder="请输入服务器名称"
              ></el-input>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="12" :lg="12">
            <el-form-item label="服务器IP" prop="server_cred_server_ip">
              <el-input
                v-model="formData.server_cred_server_ip"
                placeholder="请输入服务器IP地址"
              ></el-input>
            </el-form-item>
          </el-col>
        </el-row>
        
        <!-- 隐藏的诱饵字段，用于防止浏览器自动填充 -->
        <div class="autofill-bait" style="position: absolute; left: -9999px; top: -9999px;">
          <input type="text" name="username" autocomplete="off" />
          <input type="password" name="password" autocomplete="off" />
        </div>
        
        <!-- 将操作系统类型、端口号、登录用户名和密码放在同一行 -->
        <el-row :gutter="[20, 20]">
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="操作系统类型" prop="server_cred_server_os">
              <el-select
                v-model="formData.server_cred_server_os"
                placeholder="请选择操作系统"
                style="width: 100%"
                @change="handleOSTypeChange"
                filterable
                default-first-option
                clearable
              >
                <el-option
                  v-for="option in osOptions"
                  :key="option.value"
                  :label="option.label"
                  :value="option.value"
                ></el-option>
              </el-select>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="端口号" prop="server_cred_server_port">
              <el-input-number
                v-model="formData.server_cred_server_port"
                placeholder="请输入端口号"
                style="width: 100%"
              ></el-input-number>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="登录用户名" prop="server_cred_login_username">
              <el-input
                v-model="formData.server_cred_login_username"
                placeholder="请输入登录用户名"
                autocomplete="new-username"
                :name="'random-username-' + Math.random().toString(36).substring(2, 15)"
                readonly
                @focus="$event.target.removeAttribute('readonly')"
                :key="usernameKey"
              ></el-input>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="密码" prop="server_cred_login_password">
              <el-input
                v-model="formData.server_cred_login_password"
                type="password"
                placeholder="请输入登录密码"
                show-password
                autocomplete="new-password"
                :name="'random-password-' + Math.random().toString(36).substring(2, 15)"
                readonly
                @focus="$event.target.removeAttribute('readonly')"
                :key="passwordKey"
              ></el-input>
            </el-form-item>
          </el-col>
        </el-row>
        
        <!-- 新增行：EDR安装、NTP配置 -->
        <el-row :gutter="[20, 20]">
          <el-col :xs="24" :sm="12" :md="12" :lg="12">
            <el-form-item label="EDR安装" prop="server_cred_edr_installed">
              <el-select
                v-model="formData.server_cred_edr_installed"
                placeholder="请选择是否安装EDR"
                style="width: 100%"
              >
                <el-option label="是" value="是"></el-option>
                <el-option label="否" value="否"></el-option>
              </el-select>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="12" :lg="12">
            <el-form-item label="NTP配置" prop="server_cred_ntp_configured">
              <el-select
                v-model="formData.server_cred_ntp_configured"
                placeholder="请选择是否配置NTP"
                style="width: 100%"
              >
                <el-option label="是" value="是"></el-option>
                <el-option label="否" value="否"></el-option>
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="[20, 20]">
          <el-col :xs="24" :sm="24" :md="24" :lg="24">
            <el-form-item label="备注信息" prop="server_cred_notes">
              <el-input
                v-model="formData.server_cred_notes"
                type="textarea"
                :rows="3"
                placeholder="请输入备注信息"
              ></el-input>
            </el-form-item>
          </el-col>
        </el-row>
        
        <!-- 表单提交按钮 -->
        <el-form-item>
          <div class="d-flex gap-2">
            <el-button type="primary" @click="openImportDialog" :icon="DocumentAdd">导入</el-button>
            <el-button @click="resetForm" :icon="RefreshRight">重置</el-button>
            <el-button type="warning" @click="fillTestData" :icon="EditPen">一键填充测试数据</el-button>
            <el-button type="primary" @click="saveServerCred" :icon="Check" ref="saveBtnRef">保存信息</el-button>
          </div>
        </el-form-item>
      </el-form>
      </div>
    </el-card>

    <!-- 导入XLS文件对话框 -->
    <el-dialog
      v-model="importDialogVisible"
      title="导入服务器账号密码"
      width="80%"
      :close-on-click-modal="false"
    >
      <!-- 导入说明 -->
      <div class="mb-4">
        <h6 class="text-bold">导入说明</h6>
        <el-divider></el-divider>
        <el-descriptions :column="1" border>
          <el-descriptions-item label="1">请确保XLS文件格式与模板一致</el-descriptions-item>
          <el-descriptions-item label="2">支持的字段：服务器所属网络区域、服务器类型、宿主机集群、服务器名称、服务器IP、操作系统类型、端口号、用户名、密码、备注信息</el-descriptions-item>
          <el-descriptions-item label="3">标有 <span class="text-danger">*</span> 的字段为必填项</el-descriptions-item>
          <el-descriptions-item label="4">服务器类型为"物理机"时，宿主机集群将被忽略</el-descriptions-item>
        </el-descriptions>
      </div>
      
      <!-- 文件上传区域 -->
      <div class="mb-4">
        <el-upload
          class="upload-demo"
          :auto-upload="false"
          :file-list="fileList"
          :on-change="handleFileChange"
          :before-upload="beforeUpload"
          accept=".xls"
        >
          <el-button type="primary" :icon="Upload">选择XLS文件</el-button>
          <template #tip>
            <div class="el-upload__tip text-muted">支持的XLS格式：Excel 97-2003格式，第一行为表头</div>
          </template>
        </el-upload>
      </div>
      
      <!-- 预览区域 -->
      <div class="mb-4" v-if="importData.length > 0">
        <h6 class="text-bold">数据预览</h6>
        <el-divider></el-divider>
        <el-table
          :data="importData.slice(0, 10)"
          border
          style="width: 100%"
          max-height="300"
        >
          <el-table-column
            v-for="(header, index) in previewHeaders"
            :key="index"
            :prop="header"
            :label="header"
            align="center"
          ></el-table-column>
        </el-table>
        <el-alert
          v-if="importData.length > 10"
          type="info"
          :closable="false"
          show-icon
          class="mt-2"
        >
          仅显示前10行，共 {{ importData.length }} 行数据
        </el-alert>
      </div>
      
      <!-- 进度显示 -->
      <div class="mb-4" v-if="showProgress">
        <h6 class="text-bold">导入进度</h6>
        <el-divider></el-divider>
        <el-progress
          :percentage="importProgress"
          :status="progressStatus"
          :format="progressFormat"
          :color="progressColor"
        ></el-progress>
        <el-text class="mt-2 block text-center">{{ progressText }}</el-text>
      </div>
      
      <!-- 错误信息 -->
      <div class="mb-4" v-if="errors.length > 0">
        <h6 class="text-bold text-danger">错误信息</h6>
        <el-divider></el-divider>
        <div class="error-container" style="max-height: 300px; overflow-y: auto; border: 1px solid #f56c6c; border-radius: 4px; padding: 10px; background-color: #fef0f0;">
          <div 
            v-for="(error, index) in errors" 
            :key="index"
            class="error-item"
            style="margin-bottom: 10px; padding: 10px; background-color: #fff2f0; border: 1px solid #ffccc7; border-radius: 4px;"
          >
            <div class="error-content" style="color: #cf1322; font-size: 14px; line-height: 1.5;">
              {{ error }}
            </div>
          </div>
        </div>
      </div>
      
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="importDialogVisible = false">关闭</el-button>
          <el-button type="primary" :disabled="!canImport" @click="startImport">开始导入</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script>
// 导入XLS文件解析库
import * as XLSX from 'xlsx';
// 导入Element Plus图标
import { DocumentAdd, RefreshRight, Check, Upload, EditPen } from '@element-plus/icons-vue';

export default {
  name: 'ServerCredEntryView',
  components: {
    DocumentAdd,
    RefreshRight,
    Check,
    Upload,
    EditPen
  },
  data() {
    return {
      // 表单数据
      formData: {
        server_cred_network_area: '内网',
        server_cred_server_type: '虚拟机',
        server_cred_host_cluster: '',
        server_cred_server_name: '',
        server_cred_server_ip: '',
        server_cred_server_os: '',
        server_cred_server_port: null,
        server_cred_login_username: '',
        server_cred_login_password: '',
        server_cred_edr_installed: '是',
        server_cred_ntp_configured: '是',
        server_cred_notes: ''
      },
      // 用于防止自动填充的key，每次重置表单时更新
      usernameKey: Date.now(),
      passwordKey: Date.now(),
      // 操作系统类型选项
      osOptions: [],
      // 集群选项
      clusterOptions: [],
      // 集群搜索加载状态
      clusterLoading: false,
      // 表单验证规则
      formRules: {
        server_cred_server_name: [
          { required: true, message: '请输入服务器名称', trigger: 'blur' }
        ],
        server_cred_server_ip: [
          { required: true, message: '请输入服务器IP地址', trigger: 'blur' },
          { validator: (rule, value, callback) => {
              if (!value) {
                callback();
                return;
              }
              // 简单的IP地址验证
              const ipRegex = /^((25[0-5]|(2[0-4]|1\d|[1-9]|)\d)\.?\b){4}$/;
              if (ipRegex.test(value)) {
                callback();
              } else {
                callback(new Error('请输入有效的IP地址'));
              }
            }, trigger: 'blur' }
        ],
        server_cred_server_os: [
          { required: true, message: '请选择操作系统类型', trigger: 'change' }
        ],
        server_cred_server_port: [
          { required: true, message: '请输入端口号', trigger: 'blur' },
          { type: 'number', min: 1, max: 65535, message: '端口号必须在1-65535之间', trigger: 'blur' }
        ],
        server_cred_login_username: [
          { required: true, message: '请输入登录用户名', trigger: 'blur' }
        ],
        server_cred_login_password: [
          { required: true, message: '请输入登录密码', trigger: 'blur' }
        ],
        // 宿主机集群验证规则，通过自定义验证器实现动态验证
        server_cred_host_cluster: [
          {
            validator: this.validateHostCluster,
            trigger: 'blur, change'
          }
        ]
      },
      // 导入相关数据
      importDialogVisible: false,
      fileList: [],
      importData: [],
      previewHeaders: [],
      showProgress: false,
      importProgress: 0,
      progressStatus: '',
      progressText: '',
      progressColor: '',
      errors: [],
      canImport: false
    };
  },
  methods: {
    // 初始化表单功能
    initForm() {
      // 初始加载时根据默认值显示/隐藏宿主机集群字段
      this.toggleHostClusterField();
      // 加载操作系统类型选项
      this.loadOsOptions();
      // 初始加载集群选项
      this.remoteSearchCluster('');
    },
    
    // 远程搜索集群
    remoteSearchCluster(query) {
      this.clusterLoading = true;
      
      // 获取当前用户信息
      let currentUser = null;
      try {
        const userInfo = localStorage.getItem('currentUser');
        if (userInfo) {
          currentUser = JSON.parse(userInfo);
        }
      } catch (error) {
        console.error('获取用户信息失败:', error);
      }
      
      // 发送API请求获取集群列表
      // 使用后端API期望的参数格式：keyword1和queryType
      // 使用相对路径，Vite开发服务器会自动代理到后端服务器
      fetch('/search_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          keyword1: query || '',
          keyword2: '',
          queryType: 'cluster', // 指定查询类型为cluster
          page: 1,
          pageSize: 20
        })
      })
      .then(response => {
        // 先检查响应是否为JSON格式
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          return response.json();
        } else {
          // 如果不是JSON，返回错误响应
          return {
            success: false,
            message: '无法连接到服务器，请确保PHP环境正常运行'
          };
        }
      })
      .then(data => {
        this.clusterLoading = false;
        
        if (data.success) {
          this.clusterOptions = data.data.map(item => ({
            cluster_name: item.cluster_name
          }));
        } else {
          console.error('获取集群列表失败:', data.message);
          this.$message.error(`获取集群列表失败: ${data.message || '未知错误'}`);
          this.clusterOptions = [];
        }
      })
      .catch(error => {
        this.clusterLoading = false;
        console.error('获取集群列表出错:', error);
        this.$message.error(`获取集群列表出错: ${error.message || '网络错误'}`);
        this.clusterOptions = [];
      });
    },
    
    // 从数据库加载操作系统类型选项
    loadOsOptions() {
      // 获取当前用户信息
      let currentUser = null;
      try {
        const userInfo = localStorage.getItem('currentUser');
        if (userInfo) {
          currentUser = JSON.parse(userInfo);
        }
      } catch (error) {
        console.error('获取用户信息失败:', error);
      }
      
      // 发送API请求获取操作系统类型
      fetch('base_obj_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'getBaseObject',
          type: 'serverOs'
        })
      })
      .then(response => {
        // 检查响应是否为JSON格式
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          return response.json();
        } else {
          throw new Error('响应格式错误，不是JSON');
        }
      })
      .then(data => {
        if (data.success) {
          // 解析JSON数据并转换为选项格式
          try {
            // 假设data.data包含base_obj_server_os字段的JSON字符串
            const osList = Array.isArray(data.data) ? data.data : JSON.parse(data.data || '[]');
            // 转换为Element Plus Select组件所需的选项格式
            this.osOptions = osList.map(os => ({
              label: os,
              value: os
            }));
          } catch (error) {
            console.error('解析操作系统类型数据失败:', error);
            this.$message.error('解析操作系统类型数据失败');
            // 设置默认选项
            this.osOptions = [
              { label: 'Windows', value: 'Windows' },
              { label: 'Linux', value: 'Linux' }
            ];
          }
        } else {
          console.error('获取操作系统类型失败:', data.message);
          this.$message.error(`获取操作系统类型失败: ${data.message || '未知错误'}`);
          // 设置默认选项
          this.osOptions = [
            { label: 'Windows', value: 'Windows' },
            { label: 'Linux', value: 'Linux' }
          ];
        }
      })
      .catch(error => {
        console.error('获取操作系统类型出错:', error);
        this.$message.error(`获取操作系统类型出错: ${error.message || '网络错误'}`);
        // 设置默认选项
        this.osOptions = [
          { label: 'Windows', value: 'Windows' },
          { label: 'Linux', value: 'Linux' }
        ];
      });
    },
    
    // 切换宿主机集群字段的显示/隐藏和必填状态
    toggleHostClusterField() {
      if (this.$refs.hostClusterCol && this.$refs.hostClusterCol.$el) {
        const hostClusterEl = this.$refs.hostClusterCol.$el;
        const formItemEl = hostClusterEl.querySelector('.el-form-item');
        
        if (this.formData.server_cred_server_type === '物理机') {
          // 物理机：隐藏宿主机集群字段，移除必填验证
          hostClusterEl.classList.add('d-none');
          if (formItemEl) {
            formItemEl.classList.remove('is-required');
          }
          // 清空宿主机集群值
          this.formData.server_cred_host_cluster = '';
        } else {
          // 虚拟机：显示宿主机集群字段，添加必填验证
          hostClusterEl.classList.remove('d-none');
          if (formItemEl) {
            formItemEl.classList.add('is-required');
          }
        }
        
        // 类型切换后，重新验证表单，确保验证状态正确
        if (this.$refs.serverCredFormRef) {
          this.$refs.serverCredFormRef.validateField('server_cred_host_cluster');
        }
      }
    },
    
    // 操作系统类型变化事件
    handleOSTypeChange() {
      // 根据操作系统类型自动填充端口号
      if (this.formData.server_cred_server_os === 'Windows') {
        this.formData.server_cred_server_port = 3389;
      } else if (this.formData.server_cred_server_os === 'Linux') {
        this.formData.server_cred_server_port = 22;
      } else {
        this.formData.server_cred_server_port = null;
      }
    },
    
    // 宿主机集群自定义验证器
    validateHostCluster(rule, value, callback) {
      if (this.formData.server_cred_server_type === '虚拟机') {
        if (!value || value.trim() === '') {
          callback(new Error('请选择或输入宿主机集群'));
        } else {
          callback();
        }
      } else {
        callback(); // 物理机不需要验证宿主机集群
      }
    },
    
    // 重置表单
    resetForm() {
      if (this.$refs.serverCredFormRef) {
        this.$refs.serverCredFormRef.resetFields();
      }
      // 重置端口为null
      this.formData.server_cred_server_port = null;
      // 重置宿主机集群字段的显示/隐藏状态
      this.toggleHostClusterField();
      
      // 更新key值，强制重新渲染输入框，清除浏览器自动填充
      this.usernameKey = Date.now();
      this.passwordKey = Date.now();
    },
    
    // 保存服务器账号密码
    saveServerCred() {
      // 先验证表单
      if (this.$refs.serverCredFormRef) {
        this.$refs.serverCredFormRef.validate((valid) => {
          if (valid) {
            this.saveServerCredAfterValidation();
          } else {
            this.$message.warning('请检查表单填写是否正确');
            return false;
          }
        });
      } else {
        this.$message.error('表单引用未找到，请刷新页面重试');
      }
    },
    
    // 表单验证通过后保存数据
    saveServerCredAfterValidation() {
      // 显示加载状态
      let saveBtn = null;
      let originalText = '';
      
      // 获取保存按钮
      saveBtn = this.$refs.saveBtnRef && this.$refs.saveBtnRef.$el;
      if (saveBtn) {
        originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="el-icon-loading"></i> 保存中...';
        saveBtn.disabled = true;
      } else {
        console.error('保存按钮未找到');
      }
      
      // 获取当前登录用户名（使用credstat_user_name字段）
      let username = '';
      try {
        // 先从sessionStorage获取，再尝试localStorage，与系统登录信息录入保持一致
        let userInfo = sessionStorage.getItem('currentUser');
        if (!userInfo) {
          userInfo = localStorage.getItem('currentUser');
        }
        if (userInfo) {
          const parsedUser = JSON.parse(userInfo);
          // 优先使用name字段（对应credstat_user_name），如果不存在则使用username
          username = parsedUser.name || parsedUser.username || '';
        }
      } catch (error) {
        console.error('获取用户信息失败:', error);
      }
      
      // 如果获取不到用户名，使用默认值'system'作为备选
      if (!username) {
        username = 'system';
      }
      
      // 验证创建人信息
      if (!username || username.trim() === '') {
        this.$message.error('当前登录用户信息无效，请重新登录');
        // 恢复按钮状态
        if (saveBtn) {
          saveBtn.innerHTML = originalText;
          saveBtn.disabled = false;
        }
        return;
      }
      
      // 构建表单数据，根据服务器类型决定是否包含宿主机集群数据
      const formData = {
        server_cred_network_area: this.formData.server_cred_network_area,
        server_cred_server_type: this.formData.server_cred_server_type,
        server_cred_server_name: this.formData.server_cred_server_name.trim(),
        server_cred_server_ip: this.formData.server_cred_server_ip.trim(),
        server_cred_server_os: this.formData.server_cred_server_os, // 直接使用用户输入的操作系统类型
        server_cred_server_port: this.formData.server_cred_server_port,
        server_cred_login_username: this.formData.server_cred_login_username.trim(),
        server_cred_login_password: this.formData.server_cred_login_password,
        server_cred_edr_installed: this.formData.server_cred_edr_installed,
        server_cred_ntp_configured: this.formData.server_cred_ntp_configured,
        server_cred_notes: this.formData.server_cred_notes.trim(),
        server_cred_created_by: username
      };
      
      // 仅当服务器类型为虚拟机时，才包含宿主机集群数据
      if (this.formData.server_cred_server_type === '虚拟机') {
        formData.server_cred_host_cluster = this.formData.server_cred_host_cluster;
      }
      
      // 发送真实的API请求
      fetch('save_server_cred.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
      })
      .then(response => {
        // 先检查响应是否为JSON格式
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          return response.json();
        } else {
          // 如果不是JSON，返回错误响应
          return {
            success: false,
            message: '无法连接到服务器，请确保PHP环境正常运行'
          };
        }
      })
      .then(data => {
        // 恢复按钮状态
        if (saveBtn) {
          saveBtn.innerHTML = originalText;
          saveBtn.disabled = false;
        }
        
        if (data.success) {
          this.$message.success('保存成功');
          this.resetForm();
        } else {
          this.$message.error(`保存失败: ${data.message || '未知错误'}`);
        }
      })
      .catch(error => {
        // 恢复按钮状态
        if (saveBtn) {
          saveBtn.innerHTML = originalText;
          saveBtn.disabled = false;
        }
        
        this.$message.error(`保存出错: ${error.message || '网络错误'}`);
        console.error('保存服务器账号密码出错:', error);
      });
    },
    
    // 打开导入对话框
    openImportDialog() {
      this.importDialogVisible = true;
      this.fileList = [];
      this.importData = [];
      this.previewHeaders = [];
      this.showProgress = false;
      this.importProgress = 0;
      this.progressStatus = '';
      this.progressText = '';
      this.progressColor = '';
      this.errors = [];
      this.canImport = false;
    },
    
    // 处理文件选择
    async handleFileChange(file, fileList) {
      this.fileList = [file];
      
      try {
        // 读取文件内容
        const content = await this.readFile(file.raw);
        // 解析XLS
        const result = this.parseXLS(content, file.raw);
        // 预览数据
        this.previewData(result.data);
      } catch (error) {
        console.error('处理文件失败:', error);
        this.$message.error('处理文件失败: ' + error.message);
      }
    },
    
    // 上传前验证
    beforeUpload(file) {
      // 检查文件类型
      if (!file.name.endsWith('.xls')) {
        this.$message.error('请选择XLS文件');
        return false;
      }
      return true;
    },
    
    // 读取文件
    readFile(file) {
      return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => {
          resolve(e.target.result);
        };
        reader.onerror = (e) => reject(e.target.error);
        reader.readAsArrayBuffer(file);
      });
    },
      
    // 解析XLS文件
    parseXLS(arrayBuffer, file) {
      // 使用xlsx库读取XLS文件
      const workbook = XLSX.read(arrayBuffer, {
        type: 'array',
        cellDates: true,
        cellText: true
      });
      
      // 获取第一个工作表
      const firstSheetName = workbook.SheetNames[0];
      const worksheet = workbook.Sheets[firstSheetName];
      
      // 将工作表转换为JSON数据
      const jsonData = XLSX.utils.sheet_to_json(worksheet, {
        header: 1, // 先以数组形式读取，获取表头
        defval: '' // 空单元格默认值
      });
      
      if (jsonData.length < 2) {
        throw new Error('XLS文件至少需要包含表头和一条数据');
      }
      
      // 提取表头（第一行）
      const headers = jsonData[0];
      
      // 解析数据行
      const data = [];
      for (let i = 1; i < jsonData.length; i++) {
        const rowArray = jsonData[i];
        const row = {};
        
        // 遍历表头，将数据行转换为对象
        headers.forEach((header, index) => {
          if (header && header.trim()) { // 只处理非空表头
            row[header.trim()] = rowArray[index] ? String(rowArray[index]).trim() : '';
          }
        });
        
        // 只添加有数据的行
        if (Object.values(row).some(value => value)) {
          data.push(row);
        }
      }
      
      return { headers, data };
    },
    
    // 预览数据，处理表头和数据显示
    previewData(data) {
      this.importData = data;
      if (data.length > 0) {
        this.previewHeaders = Object.keys(data[0]);
      }
      this.canImport = true;
    },
    
    // 开始导入
    startImport() {
      if (!this.importData || this.importData.length === 0) {
        this.$message.error('没有可导入的数据');
        return;
      }
      
      // 验证数据
      const validationResult = this.validateData(this.importData);
      
      // 过滤掉空的错误信息
      const filteredErrors = validationResult.errors.filter(error => error && error.trim());
      
      if (filteredErrors.length > 0) {
        this.errors = filteredErrors;
        this.$message.warning(`数据验证失败，共发现 ${filteredErrors.length} 个错误`);
        return;
      }
      
      // 隐藏错误信息
      this.errors = [];
      
      // 显示进度条
      this.showProgress = true;
      this.importProgress = 0;
      this.progressStatus = '';
      this.progressText = '准备开始导入...';
      this.progressColor = '';
      
      // 添加调试信息
      console.log('开始导入数据，共', this.importData.length, '条记录');
      
      // 开始导入
      this.importDataBatch(this.importData);
    },
    
    // 字段名映射：英文数据库字段名到中文字段名的映射
    getFieldNameMapping() {
      return {
        // 英文数据库字段名 => 中文字段名
        'server_cred_network_area': '服务器所属网络区域',
        'server_cred_server_type': '服务器类型',
        'server_cred_host_cluster': '宿主机集群',
        'server_cred_server_name': '服务器名称',
        'server_cred_server_ip': '服务器IP',
        'server_cred_server_os': '操作系统类型',
        'server_cred_server_port': '端口号',
        'server_cred_login_username': '用户名',
        'server_cred_login_password': '密码',
        'server_cred_notes': '备注信息'
      };
    },
    
    // 获取字段值，支持中英文字段名
    getFieldValue(row, fieldName) {
      // 直接返回字段值，不进行映射转换
      // 因为XLS文件的表头是中文，解析出来的行对象的键也是中文
      return row[fieldName] || '';
    },
    
    // 验证数据
    validateData(data) {
      const errors = [];
      const requiredFields = ['服务器名称', '服务器IP', '端口号', '用户名', '密码'];
      
      data.forEach((row, index) => {
        // 行号从1开始（不包括表头）
        const rowNumber = index + 2;
        
        // 验证必填字段
        requiredFields.forEach(field => {
          if (!this.getFieldValue(row, field)?.trim()) {
            errors.push(`第 ${rowNumber} 行：${field} 不能为空`);
          }
        });
        
        // 验证端口号
        const port = this.getFieldValue(row, '端口号');
        if (port) {
          const portNum = parseInt(port);
          if (isNaN(portNum) || portNum < 1 || portNum > 65535) {
            errors.push(`第 ${rowNumber} 行：端口号必须是1-65535之间的数字`);
          }
        }
        
        // 验证网络区域
        const networkArea = this.getFieldValue(row, '服务器所属网络区域');
        if (networkArea && !['内网', 'DMZ'].includes(networkArea)) {
          errors.push(`第 ${rowNumber} 行：服务器所属网络区域必须是"内网"或"DMZ"`);
        }
        
        // 验证服务器类型
        const serverType = this.getFieldValue(row, '服务器类型');
        if (serverType && !['物理机', '虚拟机'].includes(serverType)) {
          errors.push(`第 ${rowNumber} 行：服务器类型必须是"物理机"或"虚拟机"`);
        }
        
        // 操作系统类型不再强制验证，允许任意值
      });
      
      return {
        valid: errors.length === 0,
        errors
      };
    },
    
    // 批量导入数据
    async importDataBatch(data) {
      const total = data.length;
      let successCount = 0;
      let failedCount = 0;
      const failedRows = [];
      
      for (let i = 0; i < data.length; i++) {
        const row = data[i];
        
        try {
          await this.importRow(row);
          successCount++;
        } catch (error) {
          failedCount++;
          failedRows.push(`第 ${i + 2} 行：${error.message}`);
        }
        
        // 更新进度
        this.importProgress = Math.round(((i + 1) / total) * 100);
        this.progressText = `正在导入：${i + 1}/${total} 条记录`;
        
        // 根据进度更新状态
        if (this.importProgress < 100) {
          this.progressStatus = 'progress';
          this.progressColor = '#409EFF';
        } else {
          this.progressStatus = 'success';
          this.progressColor = '#67C23A';
        }
      }
      
      // 显示导入结果
      this.showImportResult(successCount, failedCount, failedRows);
    },
    
    // 导入单行数据
    importRow(row) {
      return new Promise((resolve, reject) => {
        // 获取当前用户信息，与系统登录信息录入保持一致
        let currentUser = null;
        try {
          // 先从sessionStorage获取，再尝试localStorage
          let userInfo = sessionStorage.getItem('currentUser');
          if (!userInfo) {
            userInfo = localStorage.getItem('currentUser');
          }
          if (userInfo) {
            currentUser = JSON.parse(userInfo);
          }
        } catch (error) {
          console.error('获取用户信息失败:', error);
        }
        
        // 构建表单数据，支持中英文字段名
        const formData = {
          // 服务器所属网络区域
          server_cred_network_area: this.getFieldValue(row, '服务器所属网络区域') || '内网',
          // 服务器类型
          server_cred_server_type: this.getFieldValue(row, '服务器类型') || '物理机',
          // 宿主机集群
          server_cred_host_cluster: this.getFieldValue(row, '宿主机集群') || '',
          // 服务器名称
          server_cred_server_name: (this.getFieldValue(row, '服务器名称') || '').trim(),
          // 服务器IP
          server_cred_server_ip: (this.getFieldValue(row, '服务器IP') || '').trim(),
          // 操作系统类型
          server_cred_server_os: (this.getFieldValue(row, '操作系统类型') || '').trim(),
          // 端口号
          server_cred_server_port: (this.getFieldValue(row, '端口号') || '').trim(),
          // 用户名
          server_cred_login_username: (this.getFieldValue(row, '用户名') || '').trim(),
          // 密码
          server_cred_login_password: this.getFieldValue(row, '密码') || '',
          // EDR安装
          server_cred_edr_installed: this.getFieldValue(row, 'EDR安装') || '否',
          // NTP配置
          server_cred_ntp_configured: this.getFieldValue(row, 'NTP配置') || '否',
          // 备注信息
          server_cred_notes: this.getFieldValue(row, '备注信息') || '',
          // 创建人
          server_cred_created_by: currentUser ? currentUser.username : ''
        };
        
        // 调试信息
        console.log('导入数据行:', formData);
        
        // 发送API请求
        fetch('save_server_cred.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(formData)
        })
        .then(response => {
          console.log('API响应状态:', response.status);
          // 先检查响应是否为JSON格式
          const contentType = response.headers.get('content-type');
          if (contentType && contentType.includes('application/json')) {
            return response.json();
          } else {
            // 如果不是JSON，返回错误响应
            return {
              success: false,
              message: '无法连接到服务器，请确保PHP环境正常运行'
            };
          }
        })
        .then(data => {
          console.log('API响应数据:', data);
          if (data.success) {
            resolve();
          } else {
            reject(new Error(data.message || '导入失败'));
          }
        })
        .catch(error => {
          console.error('API请求错误:', error);
          reject(new Error('网络错误: ' + error.message));
        });
      });
    },
    
    // 显示导入结果
    showImportResult(successCount, failedCount, failedRows) {
      let message = `导入完成：成功 ${successCount} 条，失败 ${failedCount} 条`;
      
      if (failedCount > 0) {
        // 显示失败原因
        this.errors = failedRows;
        this.$message.error(message + '\n\n请查看下方错误信息了解失败原因');
        // 隐藏进度条
        this.showProgress = false;
      } else {
        // 隐藏进度条
        this.showProgress = false;
        
        this.$message.success(message + '\n\n导入成功！');
        
        // 延迟关闭模态框，让用户看到成功信息
        setTimeout(() => {
          this.importDialogVisible = false;
        }, 1500);
      }
    },
    
    // 一键填充测试数据
    fillTestData() {
      // 生成随机测试数据
      const randomNum = Math.floor(Math.random() * 1000);
      const networkAreas = ['内网', 'DMZ'];
      const serverTypes = ['物理机', '虚拟机'];
      const osTypes = ['Windows', 'Linux', 'CentOS', 'Ubuntu'];
      
      // 填充表单数据
      this.formData.server_cred_network_area = networkAreas[Math.floor(Math.random() * networkAreas.length)];
      this.formData.server_cred_server_type = serverTypes[Math.floor(Math.random() * serverTypes.length)];
      this.formData.server_cred_host_cluster = this.formData.server_cred_server_type === '虚拟机' ? `测试集群-${randomNum}` : '';
      this.formData.server_cred_server_name = `测试服务器-${randomNum}`;
      this.formData.server_cred_server_ip = `192.168.${Math.floor(Math.random() * 255)}.${Math.floor(Math.random() * 255)}`;
      this.formData.server_cred_server_os = osTypes[Math.floor(Math.random() * osTypes.length)];
      this.formData.server_cred_server_port = this.formData.server_cred_server_os === 'Windows' ? 3389 : 22;
      this.formData.server_cred_login_username = 'admin';
      this.formData.server_cred_login_password = 'Test@123456';
      this.formData.server_cred_notes = '这是一条测试备注信息，用于测试服务器账号密码录入功能。';
      
      // 显示成功提示
      this.$message.success('测试数据填充完成');
      
      // 更新宿主机集群字段的显示/隐藏状态
      this.toggleHostClusterField();
    }
  },
  mounted() {
    console.log('服务器账号密码录入视图已挂载');
    // 初始化表单功能
    this.initForm();
  }
};
</script>

<style scoped>
/* 视图特定样式 */
.server-cred-entry-view {
  padding: 0 20px;
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
</style>