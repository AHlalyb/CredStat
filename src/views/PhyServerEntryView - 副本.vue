<template>
  <div class="phy-server-entry-view">
    <el-card class="mt-5" shadow="hover">
      <template #header>
        <div class="card-header">
          <h3 class="m-0">物理服务器信息录入</h3>
        </div>
      </template>
      
      <div class="el-card__body">
      <el-form
        ref="phyServerFormRef"
        :model="formData"
        :rules="formRules"
        label-position="top"
        label-width="100px"
        size="default"
        autocomplete="off"
      >
        <!-- 物理位置信息 -->
        <el-row :gutter="[20, 20]">
          <el-col :xs="24" :sm="12" :md="6" :lg="4">
            <el-form-item label="机房/站点" prop="phyServerRoom" required>
              <el-select
              v-model="formData.phyServerRoom"
              placeholder="请选择或输入机房/站点"
              filterable
              allow-create
              default-first-option
              style="width: 100%"
            >
              <el-option
                v-for="option in roomOptions"
                :key="option.value"
                :label="option.label"
                :value="option.value"
              ></el-option>
            </el-select>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="6" :lg="4">
            <el-form-item label="机柜编号" prop="phyServerCabinet" required>
              <el-input
                v-model="formData.phyServerCabinet"
                placeholder="请输入机柜编号"
              ></el-input>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="6" :lg="4">
            <el-form-item label="U位" prop="phyServerCabinetPosition" required>
              <el-input
                v-model="formData.phyServerCabinetPosition"
                placeholder="格式：_U-_U，如1U-4U"
              ></el-input>
            </el-form-item>
          </el-col>
        </el-row>
        
        <!-- 基础信息 -->
        <el-row :gutter="[20, 20]">
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="厂商" prop="phyServerBrand" required>
              <el-select
                v-model="formData.phyServerBrand"
                placeholder="请选择或输入厂商"
                filterable
                allow-create
                default-first-option
                style="width: 100%"
              >
                <el-option label="Dell" value="Dell"></el-option>
                <el-option label="HP" value="HP"></el-option>
                <el-option label="联想" value="联想"></el-option>
                <el-option label="H3C" value="H3C"></el-option>
                <el-option label="深信服" value="深信服"></el-option>
                <el-option label="华为" value="华为"></el-option>
                <el-option label="浪潮" value="浪潮"></el-option>
                <el-option label="其他" value="其他"></el-option>
              </el-select>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="型号" prop="phyServerModel" required>
              <el-input
                v-model="formData.phyServerModel"
                placeholder="请输入服务器型号"
              ></el-input>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="SN（序列号）" prop="phyServerSn" required>
              <el-input
                v-model="formData.phyServerSn"
                placeholder="请输入服务器序列号"
              ></el-input>
            </el-form-item>
          </el-col>
        </el-row>
        
        <!-- 隐藏的诱饵字段，用于防止浏览器自动填充 -->
        <div class="autofill-bait" style="position: absolute; left: -9999px; top: -9999px;">
          <input type="text" name="username" autocomplete="off" />
          <input type="password" name="password" autocomplete="off" />
        </div>
        
        <!-- 管理信息 -->
        <el-row :gutter="[20, 20]">
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="BMC地址" prop="phyServerBmcIp" required>
              <el-input
                v-model="formData.phyServerBmcIp"
                placeholder="请输入BMC地址"
              ></el-input>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="BMC账号" prop="phyServerBmcUsername" required>
              <el-input
                v-model="formData.phyServerBmcUsername"
                placeholder="请输入BMC账号"
                autocomplete="new-bmc-username"
                :name="'random-bmc-username-' + Math.random().toString(36).substring(2, 15)"
                readonly
                @focus="$event.target.removeAttribute('readonly')"
                :key="bmcUsernameKey"
              ></el-input>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="BMC密码" prop="phyServerBmcPassword" required>
              <el-input
                v-model="formData.phyServerBmcPassword"
                type="password"
                placeholder="请输入BMC密码"
                show-password
                autocomplete="new-bmc-password"
                :name="'random-bmc-password-' + Math.random().toString(36).substring(2, 15)"
                readonly
                @focus="$event.target.removeAttribute('readonly')"
                :key="bmcPasswordKey"
              ></el-input>
            </el-form-item>
          </el-col>
        </el-row>

        <!-- 日期信息 -->
        <el-row :gutter="[20, 20]">
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="采购日期" prop="purchaseDate" required>
              <el-date-picker
                v-model="formData.purchaseDate"
                type="date"
                placeholder="选择采购日期"
                style="width: 100%"
              ></el-date-picker>
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="6" :lg="6">
            <el-form-item label="维保截止日期" prop="maintenanceDate" required>
              <el-date-picker
                v-model="formData.maintenanceDate"
                type="date"
                placeholder="选择维保截止日期"
                style="width: 100%"
              ></el-date-picker>
            </el-form-item>
          </el-col>
        </el-row>
        
        <!-- 备注信息 -->
        <el-form-item label="备注信息" prop="phyServerNotes">
          <el-input
            v-model="formData.phyServerNotes"
            type="textarea"
            :rows="3"
            placeholder="请输入备注信息"
          ></el-input>
        </el-form-item>
        
        <!-- 表单提交按钮 -->
        <el-form-item>
          <div class="d-flex gap-2">
            <el-button type="primary" @click="openImportDialog" :icon="DocumentAdd">导入</el-button>
            <el-button @click="resetForm" :icon="RefreshRight">重置</el-button>
            <el-button type="primary" @click="saveForm" :icon="Check">保存</el-button>
          </div>
        </el-form-item>
      </el-form>
      </div>
    </el-card>
    
    <!-- 导入XLS文件对话框 -->
    <el-dialog
      v-model="importDialogVisible"
      title="导入物理服务器信息"
      width="80%"
      :close-on-click-modal="false"
    >
      <!-- 导入说明 -->
      <div class="mb-4">
        <h6 class="text-bold">导入说明</h6>
        <el-divider></el-divider>
        <el-descriptions :column="1" border>
          <el-descriptions-item label="1">请确保XLS文件格式与模板一致</el-descriptions-item>
          <el-descriptions-item label="2">支持的字段：机房/站点、机柜编号、U位、厂商、型号、SN（序列号）、BMC地址、BMC账号、BMC密码、采购日期、维保截止日期、备注信息</el-descriptions-item>
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
        <el-alert
          v-for="(error, index) in errors"
          :key="index"
          type="error"
          :message="error"
          show-icon
          :closable="false"
          class="mb-2"
        ></el-alert>
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
import { DocumentAdd, RefreshRight, Check, Upload } from '@element-plus/icons-vue';

export default {
  name: 'PhyServerEntryView',
  components: {
    DocumentAdd,
    RefreshRight,
    Check,
    Upload
  },
  data() {
    return {
      // 表单引用
      phyServerFormRef: null,
      // 表单数据
      formData: {
        phyServerRoom: '',
        phyServerCabinet: '',
        phyServerCabinetPosition: '',
        phyServerBrand: '',
        phyServerModel: '',
        phyServerSn: '',
        phyServerBmcIp: '',
        phyServerBmcUsername: '',
        phyServerBmcPassword: '',
        purchaseDate: '',
        maintenanceDate: '',
        powerSupplyCount: 1,
        phyServerNotes: ''
      },
      // 用于防止自动填充的key，每次重置表单时更新
      bmcUsernameKey: Date.now(),
      bmcPasswordKey: Date.now(),
      // 机房/站点选项数据
      roomOptions: [],
      // 表单验证规则
      formRules: {
        phyServerRoom: [{ required: true, message: '请选择机房/站点', trigger: 'change' }],
        phyServerCabinet: [{ required: true, message: '请输入机柜编号', trigger: 'blur' }],
        phyServerCabinetPosition: [{ required: true, message: '请输入U位', trigger: 'blur' }, { pattern: /^\d+U-\d+U$/, message: '请输入格式为"_U-_U"的机柜位置，如"1U-4U"', trigger: 'blur' }],
        phyServerBrand: [{ required: true, message: '请选择厂商', trigger: 'change' }],
        phyServerModel: [{ required: true, message: '请输入服务器型号', trigger: 'blur' }],
        phyServerSn: [{ required: true, message: '请输入服务器序列号', trigger: 'blur' }],
        phyServerBmcIp: [{ required: true, message: '请输入BMC地址', trigger: 'blur' }, { pattern: /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/, message: '请输入有效的IP地址', trigger: 'blur' }],
        phyServerBmcUsername: [{ required: true, message: '请输入BMC账号', trigger: 'blur' }],
        phyServerBmcPassword: [{ required: true, message: '请输入BMC密码', trigger: 'blur' }],
        purchaseDate: [{ required: true, message: '请选择采购日期', trigger: 'change' }],
        maintenanceDate: [{ required: true, message: '请选择维保截止日期', trigger: 'change' }],
        powerSupplyCount: [{ required: true, message: '请输入电源数量', trigger: 'blur' }]
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
  mounted() {
    // 组件挂载时获取机房/站点选项数据
    this.fetchRoomOptions();
  },
  methods: {
    // 获取机房/站点选项数据
    async fetchRoomOptions() {
      try {
        const response = await fetch('base_obj_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'getBaseObject',
            type: 'room'
          })
        });
        
        const data = await response.json();
        if (data.success) {
          // 解析返回的JSON数据，转换为下拉选项格式
          this.roomOptions = (data.data || []).map(value => ({
            label: value,
            value: value
          }));
        } else {
          console.error('获取机房/站点选项失败:', data.message);
          this.$message.error('获取机房/站点选项失败，请稍后重试');
        }
      } catch (error) {
        console.error('获取机房/站点选项失败:', error);
        this.$message.error('获取机房/站点选项失败，请稍后重试');
      }
    },
    // 重置表单
    resetForm() {
      if (this.phyServerFormRef) {
        this.phyServerFormRef.resetFields();
      }
      
      // 更新key值，强制重新渲染输入框，清除浏览器自动填充
      this.bmcUsernameKey = Date.now();
      this.bmcPasswordKey = Date.now();
    },
    
    // 保存表单
    saveForm() {
      this.phyServerFormRef.validate((valid) => {
        if (valid) {
          // 显示加载状态
          const saveBtn = document.querySelector('.el-button[type="primary"]:not([@click="openImportDialog"])');
          const originalText = saveBtn.innerHTML;
          saveBtn.innerHTML = '<i class="el-icon-loading"></i> 保存中...';
          saveBtn.disabled = true;
          
          // 发送网络请求
          // 构建正确的URL，使用相对路径，让Vite代理处理
          const apiUrl = '/save_phy_server.php';
          
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
                console.error('响应内容:', text);
                throw new Error(`响应格式错误，不是JSON: ${text.substring(0, 100)}...`);
              });
            }
          })
          .then(data => {
            // 恢复按钮状态
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
            
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
            console.error('网络请求失败:', error);
            console.error('错误堆栈:', error.stack);
            
            // 恢复按钮状态
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
            
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
          console.log('表单验证失败');
          return false;
        }
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
        'phyServerRoom': '机房/站点',
        'phyServerCabinet': '机柜编号',
        'phyServerCabinetPosition': 'U位',
        'phyServerBrand': '厂商',
        'phyServerModel': '型号',
        'phyServerSn': 'SN（序列号）',
        'phyServerBmcIp': 'BMC地址',
        'phyServerBmcUsername': 'BMC账号',
        'phyServerBmcPassword': 'BMC密码',
        'purchaseDate': '采购日期',
        'maintenanceDate': '维保截止日期',
        'phyServerNotes': '备注信息'
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
      const requiredFields = ['机房/站点', '机柜编号', 'U位', '厂商', '型号', 'SN（序列号）', 'BMC地址', 'BMC账号', 'BMC密码', '采购日期', '维保截止日期'];
      
      data.forEach((row, index) => {
        // 行号从1开始（不包括表头）
        const rowNumber = index + 2;
        
        // 验证必填字段
        requiredFields.forEach(field => {
          if (!this.getFieldValue(row, field)?.trim()) {
            errors.push(`第 ${rowNumber} 行：${field} 不能为空`);
          }
        });
        
        // 验证IP地址格式
        const bmcIp = this.getFieldValue(row, 'BMC地址');
        if (bmcIp) {
          const ipPattern = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
          if (!ipPattern.test(bmcIp)) {
            errors.push(`第 ${rowNumber} 行：BMC地址格式不正确`);
          }
        }
        
        // 验证U位格式
        const uPosition = this.getFieldValue(row, 'U位');
        if (uPosition) {
          const uPattern = /^\d+U-\d+U$/;
          if (!uPattern.test(uPosition)) {
            errors.push(`第 ${rowNumber} 行：U位格式不正确，应为"_U-_U"，如"1U-4U"`);
          }
        }
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
          // 机房/站点
          phyServerRoom: (this.getFieldValue(row, '机房/站点') || '').trim(),
          // 机柜编号
          phyServerCabinet: (this.getFieldValue(row, '机柜编号') || '').trim(),
          // U位
          phyServerCabinetPosition: (this.getFieldValue(row, 'U位') || '').trim(),
          // 厂商
          phyServerBrand: (this.getFieldValue(row, '厂商') || '').trim(),
          // 型号
          phyServerModel: (this.getFieldValue(row, '型号') || '').trim(),
          // SN（序列号）
          phyServerSn: (this.getFieldValue(row, 'SN（序列号）') || '').trim(),
          // BMC地址
          phyServerBmcIp: (this.getFieldValue(row, 'BMC地址') || '').trim(),
          // BMC账号
          phyServerBmcUsername: (this.getFieldValue(row, 'BMC账号') || '').trim(),
          // BMC密码
          phyServerBmcPassword: this.getFieldValue(row, 'BMC密码') || '',
          // 采购日期
          purchaseDate: this.getFieldValue(row, '采购日期') || '',
          // 维保截止日期
          maintenanceDate: this.getFieldValue(row, '维保截止日期') || '',
          // 备注信息
          phyServerNotes: this.getFieldValue(row, '备注信息') || ''
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
        fetch('/save_phy_server.php', {
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
    }
  }
};
</script>

<style scoped>
/* 视图特定样式 */
.phy-server-entry-view {
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

/* 响应式样式 */
@media (max-width: 768px) {
  .phy-server-entry-view {
    padding: 0 10px;
  }
}
</style>