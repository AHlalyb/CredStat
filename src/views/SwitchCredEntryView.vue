<template>
  <div class="switch-cred-entry-view">
    <el-card class="mt-5" shadow="hover">
      <template #header>
        <div class="card-header">
          <h3 class="m-0">网络设备登录信息录入</h3>
        </div>
      </template>
      
      <div class="el-card__body">
      <el-form
        ref="switchCredForm"
        :model="formData"
        :rules="formRules"
        label-position="top"
        label-width="100px"
        size="default"
        autocomplete="off"
      >
        <!-- 第一行：网络设备类型、设备所属网络、设备所属物理区域、设备所属楼宇-楼层、设备所在楼层位置 -->
        <el-row :gutter="[20, 20]">
          <el-col :xs="24" :sm="12" :md="6" :lg="4">
            <el-form-item label="网络设备类型" prop="switchDevType" required>
              <el-select
                v-model="formData.switchDevType"
                placeholder="请选择或输入设备类型"
                filterable
                allow-create
                default-first-option
                style="width: 100%"
                :loading="loading.netDeviceTypes"
              >
                <el-option
                  v-for="type in baseObjData.netDeviceTypes"
                  :key="type"
                  :label="type"
                  :value="type"
                ></el-option>
              </el-select>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="6" :lg="4">
            <el-form-item label="设备所属网络" prop="switchNetType" required>
              <el-select
                v-model="formData.switchNetType"
                placeholder="请选择所属网络"
                style="width: 100%"
              >
                <el-option label="内网" value="内网"></el-option>
                <el-option label="外网" value="外网"></el-option>
              </el-select>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="6" :lg="4">
            <el-form-item label="设备所属物理区域" prop="switchArea" required>
              <el-input
                v-model="formData.switchArea"
                placeholder="请输入物理区域"
              ></el-input>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="设备所属楼宇-楼层" prop="switchBuildingFloor" required>
              <el-input
                v-model="formData.switchBuildingFloor"
                placeholder="示例：门诊楼-3楼"
              ></el-input>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="设备所在楼层位置" prop="switchLocation" required>
              <el-input
                v-model="formData.switchLocation"
                placeholder="示例：弱电井"
              ></el-input>
            </el-form-item>
          </el-col>
        </el-row>
        
        <!-- 第二行：中文命名、系统命名、设备品牌、设备型号 -->
        <el-row :gutter="[20, 20]">
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="中文命名" prop="switchCnName" required>
              <el-input
                v-model="formData.switchCnName"
                placeholder="请输入设备中文命名"
              ></el-input>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="系统命名" prop="switchSystemName" required>
              <el-input
                v-model="formData.switchSystemName"
                placeholder="请输入设备系统命名"
              ></el-input>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="设备品牌" prop="switchBrand" required>
              <el-select
                v-model="formData.switchBrand"
                placeholder="请选择或输入设备品牌"
                filterable
                allow-create
                default-first-option
                style="width: 100%"
                :loading="loading.netDeviceBrands"
              >
                <el-option
                  v-for="brand in baseObjData.netDeviceBrands"
                  :key="brand"
                  :label="brand"
                  :value="brand"
                ></el-option>
              </el-select>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="设备型号" prop="switchModel" required>
              <el-select
                v-model="formData.switchModel"
                placeholder="请选择或输入设备型号"
                filterable
                allow-create
                default-first-option
                style="width: 100%"
                :loading="loading.netDeviceModels"
              >
                <el-option
                  v-for="model in baseObjData.netDeviceModels"
                  :key="model"
                  :label="model"
                  :value="model"
                ></el-option>
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        
        <!-- 管理信息 -->
        <el-row :gutter="[20, 20]">
          <el-col :xs="24" :sm="12" :md="8" :lg="8">
            <el-form-item label="管理IP" prop="switchManagementIp" required>
              <el-input
                v-model="formData.switchManagementIp"
                placeholder="请输入管理IP地址"
              ></el-input>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="8" :lg="8">
            <el-form-item label="管理协议" prop="switchProtocol" required>
              <el-select
                v-model="formData.switchProtocol"
                placeholder="请选择管理协议"
                style="width: 100%"
              >
                <el-option label="SSH" value="SSH"></el-option>
                <el-option label="Telnet" value="Telnet"></el-option>
                <el-option label="HTTP" value="HTTP"></el-option>
                <el-option label="HTTPS" value="HTTPS"></el-option>
              </el-select>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="8" :lg="8">
            <el-form-item label="端口" prop="switchPort" required>
              <el-input-number
                v-model="formData.switchPort"
                :min="1"
                :max="65535"
                placeholder="请输入端口号"
                style="width: 100%"
              ></el-input-number>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="8" :lg="8">
            <el-form-item label="跳板交换机" prop="switchJumpId">
              <el-select
                v-model="formData.switchJumpId"
                placeholder="留空=直连；中心无法直达设备时经此跳板目标接入"
                style="width: 100%"
                clearable
                filterable
              >
                <el-option
                  v-for="jumpDev in jumpDeviceList"
                  :key="jumpDev.jump_target_id"
                  :label="jumpDev.jump_target_name + ' [' + typeLabel(jumpDev.jump_target_type) + '] (' + jumpDev.jump_target_ip + ')'"
                  :value="jumpDev.jump_target_id"
                ></el-option>
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        
        <!-- 隐藏的诱饵字段，用于防止浏览器自动填充 -->
        <div class="autofill-bait" style="position: absolute; left: -9999px; top: -9999px;">
          <input type="text" name="username" autocomplete="off" />
          <input type="password" name="password" autocomplete="off" />
        </div>
        
        <!-- 认证信息 -->
        <el-row :gutter="[20, 20]">
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="用户名" prop="switchUsername">
              <el-input
                v-model="formData.switchUsername"
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
            <el-form-item label="密码" prop="switchPassword" required>
              <el-input
                v-model="formData.switchPassword"
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
          
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="特权密码" prop="switchPrivilegedPassword">
              <el-input
                v-model="formData.switchPrivilegedPassword"
                type="password"
                placeholder="请输入特权密码"
                show-password
                autocomplete="new-privileged-password"
                :name="'random-privileged-password-' + Math.random().toString(36).substring(2, 15)"
                readonly
                @focus="$event.target.removeAttribute('readonly')"
                :key="privilegedPasswordKey"
              ></el-input>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="SNMP团体字" prop="switchSNMPCommunity">
              <el-input
                v-model="formData.switchSNMPCommunity"
                type="password"
                placeholder="请输入SNMP团体字"
                show-password
                autocomplete="new-snmp-community"
                :name="'random-snmp-community-' + Math.random().toString(36).substring(2, 15)"
                readonly
                @focus="$event.target.removeAttribute('readonly')"
                :key="snmpCommunityKey"
              ></el-input>
            </el-form-item>
          </el-col>
        </el-row>
        
        <!-- 备注 -->
        <el-row :gutter="[20, 20]">
          <el-col :xs="24" :sm="24" :md="24" :lg="24">
            <el-form-item label="备注" prop="switchRemark">
              <el-input
                v-model="formData.switchRemark"
                type="textarea"
                :rows="3"
                placeholder="请输入备注信息（可选）"
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
            <el-button type="primary" @click="saveForm" :icon="Check" ref="saveButtonRef">保存</el-button>
          </div>
        </el-form-item>
      </el-form>
      </div>
    </el-card>

    <!-- 导入XLS文件对话框 -->
    <el-dialog
      v-model="importDialogVisible"
      title="导入网络设备登录信息"
      width="80%"
      :close-on-click-modal="false"
    >
      <!-- 导入说明 -->
      <div class="mb-4">
        <h6 class="text-bold">导入说明</h6>
        <el-divider></el-divider>
        <el-descriptions :column="1" border>
          <el-descriptions-item label="1">请确保XLS文件格式与模板一致</el-descriptions-item>
          <el-descriptions-item label="2">支持的字段：网络设备类型、设备所属网络、设备所属物理区域、设备所属楼宇-楼层、设备所在楼层位置、中文命名、系统命名、设备品牌、设备型号、管理IP、管理协议、端口、用户名、密码、特权密码、SNMP团体字、备注</el-descriptions-item>
          <el-descriptions-item label="3">标有 <span class="text-danger">*</span> 的字段为必填项</el-descriptions-item>
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
  name: 'SwitchCredEntryView',
  components: {
    DocumentAdd,
    RefreshRight,
    Check,
    Upload,
    EditPen
  },
  data() {
    return {
      // 表单引用
      switchCredForm: null,
      saveButtonRef: null,
      // 表单数据
      formData: {
        switchDevType: '',
        switchNetType: '',
        switchArea: '',
        switchBuildingFloor: '',
        switchLocation: '',
        switchCnName: '',
        switchSystemName: '',
        switchBrand: '',
        switchModel: '',
        switchManagementIp: '',
        switchProtocol: '',
        switchPort: null,
        switchJumpId: null,
        switchUsername: '',
        switchPassword: '',
        switchPrivilegedPassword: '',
        switchSNMPCommunity: '',
        switchRemark: ''
      },
      // 用于防止自动填充的key，每次重置表单时更新
      usernameKey: Date.now(),
      passwordKey: Date.now(),
      privilegedPasswordKey: Date.now(),
      snmpCommunityKey: Date.now(),
      // 表单验证规则
      formRules: {
        switchDevType: [{ required: true, message: '请选择网络设备类型', trigger: 'change' }],
        switchNetType: [{ required: true, message: '请选择设备所属网络', trigger: 'change' }],
        switchArea: [{ required: true, message: '请输入设备所属物理区域', trigger: 'blur' }],
        switchBuildingFloor: [{ required: true, message: '请输入设备所属楼宇-楼层', trigger: 'blur' }],
        switchLocation: [{ required: true, message: '请输入设备所在楼层位置', trigger: 'blur' }],
        switchCnName: [{ required: true, message: '请输入中文命名', trigger: 'blur' }],
        switchSystemName: [{ required: true, message: '请输入系统命名', trigger: 'blur' }],
        switchBrand: [{ required: true, message: '请选择设备品牌', trigger: 'change' }],
        switchModel: [{ required: true, message: '请选择设备型号', trigger: 'change' }],
        switchManagementIp: [{ required: true, message: '请输入管理IP', trigger: 'blur' }],
        switchProtocol: [{ required: true, message: '请选择管理协议', trigger: 'change' }],
        switchPort: [{ required: true, message: '请输入端口', trigger: 'blur' }],
        switchPassword: [{ required: true, message: '请输入密码', trigger: 'blur' }]
      },
      // 导入相关数据
      importDialogVisible: false,
      fileList: [],
      importData: [],
      previewHeaders: [],
      showProgress: false,
      // 跳板交换机候选列表
      jumpDeviceList: [],
      importProgress: 0,
      progressStatus: '',
      progressText: '',
      progressColor: '',
      errors: [],
      canImport: false,
      // 从base_obj表获取的数据
      baseObjData: {
        netDeviceTypes: [],
        netDeviceBrands: [],
        netDeviceModels: []
      },
      // 加载状态
      loading: {
        netDeviceTypes: false,
        netDeviceBrands: false,
        netDeviceModels: false
      }
    };
  },
  methods: {
    // 加载跳板目标列表（Agent/SSH/Telnet 类型）
    fetchJumpDevices() {
      let currentUser = null;
      try {
        const userInfo = localStorage.getItem('currentUser');
        if (userInfo) {
          currentUser = JSON.parse(userInfo);
        }
      } catch (error) {
        console.error('获取用户信息失败:', error);
      }
      
      fetch('jump_target_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Username': currentUser ? currentUser.username : ''
        },
        body: JSON.stringify({
          action: 'list',
          username: currentUser ? currentUser.username : ''
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success && Array.isArray(data.data)) {
          this.jumpDeviceList = data.data;
        }
      })
      .catch(() => {
        console.error('加载跳板目标列表失败');
      });
    },
    // 跳板类型显示标签
    typeLabel(type) {
      return { agent: 'Agent', ssh: 'SSH', telnet: 'Telnet' }[type] || type;
    },
    
    // 初始化密码可见性切换
    initPasswordToggles() {
      // Element Plus的el-input组件已经内置了show-password属性，不再需要手动实现
    },
    
    // 初始化下拉菜单
    initDropdowns() {
      // Element Plus的el-select组件已经内置了下拉菜单功能，不再需要手动实现
    },
    
    // 初始化表单事件
    initFormEvents() {
      // Element Plus的表单事件已经通过v-model和@click等指令绑定，不再需要手动实现
    },
    
    // 密码可见性切换功能
    togglePasswordVisibility(inputElement, toggleButton) {
      // Element Plus的el-input组件已经内置了show-password属性，不再需要手动实现
    },
    
    // 获取base_obj表数据
    async fetchBaseObjData() {
      try {
        // 获取网络设备类型
        this.loading.netDeviceTypes = true;
        const typeResponse = await fetch('/base_obj_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ action: 'getBaseObject', type: 'netDeviceType' })
        });
        
        if (!typeResponse.ok) {
          throw new Error(`HTTP错误! 状态码: ${typeResponse.status}`);
        }
        
        const typeData = await typeResponse.json();
        
        // 获取设备品牌
        this.loading.netDeviceBrands = true;
        const brandResponse = await fetch('/base_obj_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ action: 'getBaseObject', type: 'netDeviceBrand' })
        });
        
        if (!brandResponse.ok) {
          throw new Error(`HTTP错误! 状态码: ${brandResponse.status}`);
        }
        
        const brandData = await brandResponse.json();
        
        // 获取设备型号
        this.loading.netDeviceModels = true;
        const modelResponse = await fetch('/base_obj_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ action: 'getBaseObject', type: 'netDeviceModel' })
        });
        
        if (!modelResponse.ok) {
          throw new Error(`HTTP错误! 状态码: ${modelResponse.status}`);
        }
        
        const modelData = await modelResponse.json();
        
        // 更新组件数据
        this.baseObjData.netDeviceTypes = typeData.success ? typeData.data : [];
        this.baseObjData.netDeviceBrands = brandData.success ? brandData.data : [];
        this.baseObjData.netDeviceModels = modelData.success ? modelData.data : [];
        
      } catch (error) {
        this.$message.error('获取基础对象数据失败: ' + error.message);
      } finally {
        // 关闭所有加载状态
        this.loading.netDeviceTypes = false;
        this.loading.netDeviceBrands = false;
        this.loading.netDeviceModels = false;
      }
    },
    
    // 重置表单
    resetForm() {
      if (this.$refs.switchCredForm) {
        this.$refs.switchCredForm.resetFields();
      }
      // 重置端口为null
      this.formData.switchPort = null;
      this.formData.switchJumpId = null;
      
      // 更新key值，强制重新渲染输入框，清除浏览器自动填充
      this.usernameKey = Date.now();
      this.passwordKey = Date.now();
      this.privilegedPasswordKey = Date.now();
      this.snmpCommunityKey = Date.now();
    },
    
    // 保存表单
    saveForm() {
      if (this.$refs.switchCredForm) {
        this.$refs.switchCredForm.validate((valid) => {
          if (valid) {
            // 显示加载状态
            let saveBtn = null;
            let originalText = '';
            
            // 获取保存按钮
            saveBtn = this.$refs.saveButtonRef && this.$refs.saveButtonRef.$el;
            if (saveBtn) {
              originalText = saveBtn.innerHTML;
              saveBtn.innerHTML = '<i class="el-icon-loading"></i> 保存中...';
              saveBtn.disabled = true;
            }
            
            // 发送网络请求
            // 构建正确的URL，使用相对路径，让Vite代理处理
            const apiUrl = '/save_dev_cred.php';
            
            // 获取当前登录用户信息
            let currentUser = null;
            try {
              const userInfo = localStorage.getItem('currentUser');
              if (userInfo) {
                currentUser = JSON.parse(userInfo);
              }
            } catch (error) {
              console.error('获取用户信息失败:', error);
            }
            
            // 构建请求头，添加当前用户信息
            const headers = {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            };
            
            if (currentUser && currentUser.username) {
              headers['X-Username'] = currentUser.username;
            }
            
            fetch(apiUrl, {
              method: 'POST',
              headers: headers,
              body: JSON.stringify(this.formData)
            })
            .then(response => {
              // 检查响应是否成功
              if (!response.ok) {
                throw new Error(`HTTP错误：${response.status} ${response.statusText}`);
              }
              
              // 检查响应是否为JSON格式
              const contentType = response.headers.get('content-type');
              if (contentType && contentType.includes('application/json')) {
                return response.json();
              } else {
                // 读取响应文本，查看具体错误
                return response.text().then(text => {
                  throw new Error(`响应格式错误，不是JSON: ${text.substring(0, 100)}...`);
                });
              }
            })
            .then(data => {
              // 恢复按钮状态
              if (saveBtn) {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
              }
              
              if (data.success) {
                // 提交成功
                this.$message.success('保存成功：' + data.message);
                this.resetForm();
              } else {
                // 提交失败
                this.$message.error('保存失败：' + (data.message || '未知错误'));
              }
            })
            .catch(error => {
              // 恢复按钮状态
              if (saveBtn) {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
              }
              
              // 处理网络错误
              let errorMessage = '保存失败：';
              if (error.message.includes('Failed to fetch')) {
                errorMessage += '无法连接到服务器，请检查网络连接或服务器状态';
              } else if (error.message.includes('HTTP错误')) {
                // 获取HTTP状态码和错误信息
                const statusMatch = error.message.match(/HTTP错误：(\d+)\s+(.*)/);
                if (statusMatch) {
                  const [, statusCode, statusText] = statusMatch;
                  if (statusCode === '404') {
                    errorMessage += '请求的API地址不存在，请联系管理员检查服务器配置';
                  } else if (statusCode === '500') {
                    errorMessage += '服务器内部错误，请联系管理员检查服务器日志';
                  } else {
                    errorMessage += `服务器返回${statusCode}错误：${statusText}`;
                  }
                } else {
                  errorMessage += '服务器返回错误：' + error.message;
                }
              } else if (error.message.includes('响应格式错误')) {
                errorMessage += '服务器返回格式错误，请联系管理员检查API实现';
              } else {
                errorMessage += '网络错误或服务器异常，请稍后重试';
              }
              this.$message.error(errorMessage);
            });
          } else {
            this.$message.warning('请检查表单填写是否正确');
            return false;
          }
        });
      }
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
      if (!validationResult.valid) {
        this.errors = validationResult.errors;
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
      
      // 开始导入
      this.importDataBatch(this.importData);
    },
    
    // 字段名映射：英文数据库字段名到中文字段名的映射
    getFieldNameMapping() {
      return {
        // 英文数据库字段名 => 中文字段名
        'dev_type': '网络设备类型',
        'network_type': '设备所属网络',
        'area': '设备所属物理区域',
        'building_floor': '设备所属楼宇-楼层',
        'location': '设备所在楼层位置',
        'chinese_name': '中文命名',
        'system_name': '系统命名',
        'brand': '设备品牌',
        'model': '设备型号',
        'management_ip': '管理IP',
        'protocol': '管理协议',
        'port': '端口',
        'username': '用户名',
        'password': '密码',
        'privileged_password': '特权密码',
        'snmp_community': 'SNMP团体字',
        'remark': '备注'
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
      const requiredFields = ['网络设备类型', '设备所属网络', '设备所属物理区域', '设备所属楼宇-楼层', '设备所在楼层位置', '中文命名', '系统命名', '设备品牌', '设备型号', '管理IP', '管理协议', '端口', '密码'];
      
      data.forEach((row, index) => {
        // 行号从1开始（不包括表头）
        const rowNumber = index + 2;
        
        // 验证必填字段
        requiredFields.forEach(field => {
          if (!this.getFieldValue(row, field)?.trim()) {
            errors.push(`第 ${rowNumber} 行：${field} 不能为空`);
          }
        });
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
          this.progressColor = '#1770E6';
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
        
        // 获取字段名映射
        const mapping = this.getFieldNameMapping();
        
        // 构建表单数据，支持中英文字段名
        const formData = {
          // 网络设备类型
          switchDevType: this.getFieldValue(row, '网络设备类型'),
          // 设备所属网络
          switchNetType: this.getFieldValue(row, '设备所属网络'),
          // 设备所属物理区域
          switchArea: this.getFieldValue(row, '设备所属物理区域'),
          // 设备所属楼宇-楼层
          switchBuildingFloor: this.getFieldValue(row, '设备所属楼宇-楼层'),
          // 设备所在楼层位置
          switchLocation: this.getFieldValue(row, '设备所在楼层位置'),
          // 中文命名
          switchCnName: this.getFieldValue(row, '中文命名'),
          // 系统命名
          switchSystemName: this.getFieldValue(row, '系统命名'),
          // 设备品牌
          switchBrand: this.getFieldValue(row, '设备品牌'),
          // 设备型号
          switchModel: this.getFieldValue(row, '设备型号'),
          // 管理IP
          switchManagementIp: this.getFieldValue(row, '管理IP'),
          // 管理协议
          switchProtocol: this.getFieldValue(row, '管理协议'),
          // 端口
          switchPort: parseInt(this.getFieldValue(row, '端口')),
          // 用户名
          switchUsername: this.getFieldValue(row, '用户名'),
          // 密码
          switchPassword: this.getFieldValue(row, '密码'),
          // 特权密码
          switchPrivilegedPassword: this.getFieldValue(row, '特权密码'),
          // SNMP团体字
          switchSNMPCommunity: this.getFieldValue(row, 'SNMP团体字'),
          // 备注
          switchRemark: this.getFieldValue(row, '备注'),
          // 创建人
          createdBy: currentUser ? currentUser.username : ''
        };
        
        // 构建请求头，添加当前用户信息
        const headers = {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        };
        
        if (currentUser && currentUser.username) {
          headers['X-Username'] = currentUser.username;
        }
        
        // 发送API请求
        fetch('/save_dev_cred.php', {
          method: 'POST',
          headers: headers,
          body: JSON.stringify(formData)
        })
        .then(response => {
          // 检查响应是否为JSON格式
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
          if (data.success) {
            resolve();
          } else {
            reject(new Error(data.message || '导入失败'));
          }
        })
        .catch(error => {
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
      } else {
        // 隐藏进度条
        this.showProgress = false;
        
        this.$message.success(message + '\n\n导入成功！');
        
        // 关闭模态框
        this.importDialogVisible = false;
      }
    },
    
    // 一键填充测试数据
    fillTestData() {
      // 生成随机测试数据
      const randomNum = Math.floor(Math.random() * 1000);
      const deviceTypes = ['交换机', '路由器', '防火墙', '负载均衡'];
      const netTypes = ['内网', '外网'];
      const protocols = ['SSH', 'Telnet', 'HTTP', 'HTTPS'];
      const brands = ['华为', '华三', '思科', '锐捷'];
      const models = ['S5720', 'AR2200', 'USG6000', 'F5-BIG-IP'];
      
      // 填充表单数据
      this.formData.switchDevType = deviceTypes[Math.floor(Math.random() * deviceTypes.length)];
      this.formData.switchNetType = netTypes[Math.floor(Math.random() * netTypes.length)];
      this.formData.switchArea = `测试区域-${randomNum}`;
      this.formData.switchBuildingFloor = `测试大楼-${Math.floor(Math.random() * 10) + 1}楼`;
      this.formData.switchLocation = '弱电井';
      this.formData.switchCnName = `测试${this.formData.switchDevType}-${randomNum}`;
      this.formData.switchSystemName = `${this.formData.switchDevType}_TEST_${randomNum}`;
      this.formData.switchBrand = brands[Math.floor(Math.random() * brands.length)];
      this.formData.switchModel = models[Math.floor(Math.random() * models.length)];
      this.formData.switchManagementIp = `192.168.${Math.floor(Math.random() * 255)}.${Math.floor(Math.random() * 255)}`;
      this.formData.switchProtocol = protocols[Math.floor(Math.random() * protocols.length)];
      this.formData.switchPort = this.formData.switchProtocol === 'SSH' ? 22 : this.formData.switchProtocol === 'Telnet' ? 23 : this.formData.switchProtocol === 'HTTP' ? 80 : 443;
      this.formData.switchUsername = 'admin';
      this.formData.switchPassword = 'Test@123456';
      this.formData.switchPrivilegedPassword = 'Priv@123456';
      this.formData.switchSNMPCommunity = 'public';
      this.formData.switchRemark = '这是一条测试备注信息，用于测试网络设备登录信息录入功能。';
      
      // 显示成功提示
      this.$message.success('测试数据填充完成');
    }
  },
  mounted() {
    // 初始化表单功能
    this.initFormEvents();
    this.initDropdowns();
    this.initPasswordToggles();
    
    // 获取base_obj表数据
    this.fetchBaseObjData();
    
    // 获取跳板交换机候选列表
    this.fetchJumpDevices();
  }
};
</script>