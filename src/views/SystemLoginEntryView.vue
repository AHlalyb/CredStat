<template>
  <div class="system-login-entry-view">
    <el-card class="mt-5" shadow="hover">
      <template #header>
        <div class="card-header">
          <h3 class="m-0">系统登录信息录入</h3>
        </div>
      </template>
      
      <div class="el-card__body">
      <el-form
        ref="loginEntryForm"
        :model="formData"
        :rules="formRules"
        label-position="top"
        label-width="100px"
        size="default"
        autocomplete="off"
      >
        <el-row :gutter="[20, 20]">
          <el-col :xs="24" :sm="12" :md="8" :lg="8">
            <el-form-item label="系统名称" prop="systemName" required>
              <el-input
                v-model="formData.systemName"
                placeholder="请输入系统名称"
              ></el-input>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="8" :lg="8">
            <el-form-item label="IP/URL" prop="ipUrl" required>
              <el-input
                v-model="formData.ipUrl"
                placeholder="请输入IP地址或URL"
              ></el-input>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="12" :md="8" :lg="8">
            <el-form-item label="登录方式" prop="loginType" required>
              <el-select
                v-model="formData.loginType"
                placeholder="请选择或输入登录方式"
                filterable
                allow-create
                default-first-option
                style="width: 100%"
              >
                <el-option label="web" value="web"></el-option>
                <el-option label="telnet" value="telnet"></el-option>
                <el-option label="ssh" value="ssh"></el-option>
              </el-select>
            </el-form-item>
          </el-col>
          
          <!-- 隐藏的诱饵字段，用于防止浏览器自动填充 -->
          <div class="autofill-bait" style="position: absolute; left: -9999px; top: -9999px;">
            <input type="text" name="username" autocomplete="off" />
            <input type="password" name="password" autocomplete="off" />
          </div>
          <el-col :xs="24" :sm="8" :md="7" :lg="7">
            <el-form-item label="账号" prop="username" required>
              <el-input
                v-model="formData.username"
                placeholder="请输入账号"
                autocomplete="new-username"
                :name="'random-username-' + Math.random().toString(36).substring(2, 15)"
                readonly
                @focus="$event.target.removeAttribute('readonly')"
                :key="usernameKey"
              ></el-input>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="8" :md="7" :lg="7">
            <el-form-item label="密码" prop="password" required>
              <el-input
                v-model="formData.password"
                type="password"
                placeholder="请输入密码"
                show-password
                autocomplete="new-password"
                :name="'random-password-' + Math.random().toString(36).substring(2, 15)"
                readonly
                @focus="$event.target.removeAttribute('readonly')"
                :key="passwordKey"
              ></el-input>
            </el-form-item>
          </el-col>
          
          <el-col :xs="24" :sm="8" :md="4" :lg="4">
            <el-form-item label="是否有效" prop="isActive">
              <el-select
                v-model="formData.isActive"
                placeholder="请选择是否有效"
                style="width: 100%"
              >
                <el-option label="有效" value="1"></el-option>
                <el-option label="无效" value="0"></el-option>
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        
        <!-- 动态添加的账户密码表单组 - 水平排列 -->
        <div v-if="formData.additionalAccounts.length > 0" class="additional-accounts-container mb-4 p-3 border rounded bg-gray-50">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="text-bold mb-0">附加账户</h5>
          </div>
          <el-row :gutter="[20, 20]">
            <el-col 
              v-for="(account, index) in formData.additionalAccounts" 
              :key="index"
              :xs="24"
              :sm="12"
              :md="12"
              :lg="6"
              class="additional-account-col"
            >
              <el-transition name="fade-slide" mode="out-in">
                <div class="account-group">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-sm text-gray-600 mb-0">账户 {{ index + 1 }}</h6>
                    <el-button 
                      type="danger" 
                      size="small" 
                      @click="confirmDeleteAccount(index)"
                      :icon="Delete"
                    >
                      删除
                    </el-button>
                  </div>
                  <el-form-item :label="'账号' + (index + 1)" :prop="'additionalAccounts.' + index + '.username'" required class="mb-2">
                    <el-input
                      v-model="account.username"
                      placeholder="请输入账号"
                      autocomplete="new-username"
                      :name="'random-username-' + Math.random().toString(36).substring(2, 15) + '-' + index"
                      readonly
                      @focus="$event.target.removeAttribute('readonly')"
                      size="small"
                    ></el-input>
                  </el-form-item>
                  
                  <el-form-item :label="'密码' + (index + 1)" :prop="'additionalAccounts.' + index + '.password'" required>
                    <el-input
                      v-model="account.password"
                      type="password"
                      placeholder="请输入密码"
                      show-password
                      autocomplete="new-password"
                      :name="'random-password-' + Math.random().toString(36).substring(2, 15) + '-' + index"
                      readonly
                      @focus="$event.target.removeAttribute('readonly')"
                      size="small"
                    ></el-input>
                  </el-form-item>
                </div>
              </el-transition>
            </el-col>
          </el-row>
        </div>
        
        <!-- 添加账户按钮 -->
        <el-row :gutter="[20, 20]">
          <el-col :xs="24" :sm="24" :md="16" :lg="16">
            <el-form-item>
              <el-button 
                type="primary" 
                @click="addAccount" 
                :disabled="formData.additionalAccounts.length >= 4"
                :icon="Plus"
              >
                添加账户
              </el-button>
              <span class="text-sm text-gray-500 ml-2">
                已添加 {{ formData.additionalAccounts.length }} / 4 个附加账户
                <span v-if="formData.additionalAccounts.length < 4" class="text-green-600">(还可添加 {{ 4 - this.formData.additionalAccounts.length }} 个)</span>
                <span v-else class="text-red-600">(已达上限)</span>
              </span>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="[20, 20]">
          
          <el-col :xs="24" :sm="24" :md="24" :lg="24">
            <el-form-item label="备注" prop="remark">
              <el-input
                v-model="formData.remark"
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
            <el-button type="primary" @click="saveLoginInfo" :icon="Check" ref="saveButtonRef">保存</el-button>
          </div>
        </el-form-item>
      </el-form>
      </div>
    </el-card>

    <!-- 导入XLS文件对话框 -->
    <el-dialog
      v-model="importDialogVisible"
      title="导入系统登录信息"
      width="80%"
      :close-on-click-modal="false"
    >
      <!-- 导入说明 -->
      <div class="mb-4">
        <h6 class="text-bold">导入说明</h6>
        <el-divider></el-divider>
        <el-descriptions :column="1" border>
          <el-descriptions-item label="1">请确保XLS文件格式与模板一致</el-descriptions-item>
          <el-descriptions-item label="2">支持的字段：系统名称、IP/URL、登录方式、账号、密码、备注</el-descriptions-item>
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
import { DocumentAdd, RefreshRight, Check, Upload, EditPen, Plus, Delete } from '@element-plus/icons-vue';

export default {
  name: 'SystemLoginEntryView',
  components: {
    DocumentAdd,
    RefreshRight,
    Check,
    Upload,
    EditPen,
    Plus,
    Delete
  },
  data() {
    return {
      // 表单引用
      loginEntryForm: null,
      saveButtonRef: null,
      // 表单数据
      formData: {
        systemName: '',
        ipUrl: '',
        loginType: '',
        username: '',
        password: '',
        isActive: '1',
        remark: '',
        // 动态添加的账户密码
        additionalAccounts: []
      },
      // 用于防止自动填充的key，每次重置表单时更新
      usernameKey: Date.now(),
      passwordKey: Date.now(),
      // 表单验证规则
      formRules: {
        systemName: [{ required: true, message: '请输入系统名称', trigger: 'blur' }],
        ipUrl: [{ required: true, message: '请输入IP地址或URL', trigger: 'blur' }],
        loginType: [{ required: true, message: '请选择登录方式', trigger: 'change' }],
        username: [{ required: true, message: '请输入账号', trigger: 'blur' }],
        password: [{ required: true, message: '请输入密码', trigger: 'blur' }],
        additionalAccounts: {
          type: 'array',
          validator: (rule, value, callback) => {
            if (value.length > 4) {
              callback(new Error('最多只能添加4个附加账户'));
            } else {
              callback();
            }
          },
          trigger: 'change'
        }
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
      // Element Plus的表单事件已经通过v-model和@click等指令绑定，不再需要手动实现
    },
    
    // 动态添加账户密码表单组
    addAccount() {
      // 限制最多只能添加4个附加账户
      if (this.formData.additionalAccounts.length >= 4) {
        this.$message.warning('最多只能添加4个附加账户');
        return;
      }
      
      // 添加新的账户密码表单组
      this.formData.additionalAccounts.push({
        username: '',
        password: ''
      });
    },
    
    // 确认删除账户
    confirmDeleteAccount(index) {
      this.$confirm('确定要删除此账户吗？此操作不可撤销', '删除确认', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(() => {
        this.deleteAccount(index);
      }).catch(() => {
        // 取消删除，不做任何操作
      });
    },
    
    // 删除账户
    deleteAccount(index) {
      // 移除附加账户数量下限限制，允许删除所有附加账户
      
      // 使用splice方法删除指定索引的账户
      this.formData.additionalAccounts.splice(index, 1);
      this.$message.success('账户删除成功');
    },
    
    // 重置表单
    resetForm() {
      if (this.$refs.loginEntryForm) {
        this.$refs.loginEntryForm.resetFields();
      }
      
      // 清空动态添加的账户列表
      this.formData.additionalAccounts = [];
      
      // 更新key值，强制重新渲染输入框，清除浏览器自动填充
      this.usernameKey = Date.now();
      this.passwordKey = Date.now();
    },
    
    // 保存登录信息
    saveLoginInfo() {
      if (this.$refs.loginEntryForm) {
        this.$refs.loginEntryForm.validate((valid) => {
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
            
            // 获取当前用户信息
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
            
            // 构建表单数据
            const formData = {
              systemName: this.formData.systemName.trim(),
              ipUrl: this.formData.ipUrl.trim(),
              loginType: this.formData.loginType.trim(),
              username: this.formData.username.trim(),
              password: this.formData.password,
              isActive: this.formData.isActive,
              remark: this.formData.remark.trim(),
              createdBy: currentUser ? (currentUser.name || currentUser.username) : '',
              // 动态添加的账户密码
              username1: this.formData.additionalAccounts[0]?.username?.trim() || '',
              password1: this.formData.additionalAccounts[0]?.password || '',
              username2: this.formData.additionalAccounts[1]?.username?.trim() || '',
              password2: this.formData.additionalAccounts[1]?.password || '',
              username3: this.formData.additionalAccounts[2]?.username?.trim() || '',
              password3: this.formData.additionalAccounts[2]?.password || '',
              username4: this.formData.additionalAccounts[3]?.username?.trim() || '',
              password4: this.formData.additionalAccounts[3]?.password || ''
            };
            
            // 发送请求
            fetch('db_config_handler.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                action: 'saveLoginInfo',
                ...formData
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
              // 恢复按钮状态
              if (saveBtn) {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
              }
              
              if (data.success) {
                // 提交成功
                this.$message.success('保存成功');
                this.resetForm();
              } else {
                // 提交失败
                this.$message.error('保存失败: ' + (data.message || '未知错误'));
              }
            })
            .catch(error => {
              // 恢复按钮状态
              if (saveBtn) {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
              }
              
              this.$message.error('保存出错: ' + (error.message || '网络错误'));
              console.error('保存登录信息出错:', error);
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
        'systemName': '系统名称',
        'ipUrl': 'IP/URL',
        'loginType': '登录方式',
        'username': '账号',
        'password': '密码',
        'remark': '备注',
        'isActive': '是否有效'
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
      const requiredFields = ['系统名称', 'IP/URL', '登录方式', '账号', '密码'];
      
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
          // 系统名称
          systemName: (this.getFieldValue(row, '系统名称') || '').trim(),
          // IP/URL
          ipUrl: (this.getFieldValue(row, 'IP/URL') || '').trim(),
          // 登录方式
          loginType: (this.getFieldValue(row, '登录方式') || '').trim(),
          // 账号
          username: (this.getFieldValue(row, '账号') || '').trim(),
          // 密码
          password: this.getFieldValue(row, '密码') || '',
          // 备注
          remark: this.getFieldValue(row, '备注') || '',
          // 是否有效
          isActive: (this.getFieldValue(row, '是否有效') || '1'),
          // 创建人
          createdBy: currentUser ? currentUser.username : ''
        };
        
        // 发送API请求
        fetch('db_config_handler.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'saveLoginInfo',
            ...formData
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
      const loginTypes = ['web', 'telnet', 'ssh', 'rdp'];
      
      // 填充表单数据
      this.formData.systemName = `测试系统-${randomNum}`;
      this.formData.ipUrl = `192.168.${Math.floor(Math.random() * 255)}.${Math.floor(Math.random() * 255)}`;
      this.formData.loginType = loginTypes[Math.floor(Math.random() * loginTypes.length)];
      this.formData.username = 'admin';
      this.formData.password = 'Test@123456';
      this.formData.remark = '这是一条测试备注信息，用于测试系统登录信息录入功能。';
      
      // 显示成功提示
      this.$message.success('测试数据填充完成');
    }
  },
  mounted() {
    // 初始化表单功能
    this.initForm();
  }
};
</script>

<style scoped>
/* 附加账户容器样式优化 */
.additional-accounts-container {
  background-color: #f5f7fa;
  border-radius: 8px;
  padding: 20px;
  margin-top: 10px;
}

/* 附加账户主标题样式 */
.additional-accounts-container h5 {
  margin-top: 0;
  margin-bottom: 15px;
}

/* 附加账户组样式 */
.account-group {
  background-color: #ffffff;
  padding: 15px;
  border-radius: 6px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

/* 账户标题样式 */
.account-group h6 {
  font-weight: bold;
  margin-bottom: 12px;
  color: #303133;
}

/* 附加账户列样式 */
.additional-account-col {
  margin-bottom: 15px;
}

/* 删除动画效果 */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

.fade-slide-leave-active {
  position: absolute;
  width: 100%;
}

/* 表单项间距优化 */
.account-group .el-form-item {
  margin-bottom: 12px;
}

/* 表单标签样式 */
.account-group .el-form-item__label {
  font-weight: 500;
  font-size: 13px;
  color: #606266;
}

/* 按钮间距优化 */
.el-button + .el-button {
  margin-left: 10px;
}

/* 主账号密码区域样式 */
.main-account-section {
  margin-bottom: 20px;
}
</style>