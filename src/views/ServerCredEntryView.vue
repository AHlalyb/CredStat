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
        
        <!-- 磁盘信息录入模块 -->
        <div class="disk-info-section mb-4 p-4 border rounded">
          <h4 class="mb-3">磁盘信息</h4>
          <p class="text-sm text-gray-600 mb-4">请填写服务器的磁盘信息，支持动态添加多个磁盘条目</p>
          
          <!-- 自动提取磁盘信息 -->
          <div class="auto-extract-section mb-4">
            <el-button type="primary" @click="showExtractDialog" class="mb-2">自动提取磁盘信息</el-button>
          </div>
          
          <!-- 动态磁盘表单列表 -->
          <div class="disk-forms-list">
            <div v-for="(disk, index) in diskForms" :key="index" class="disk-form-item mb-4 p-3 border rounded bg-gray-50">
              <!-- Windows磁盘表单 -->
              <el-form v-if="isWindows" :model="disk" :rules="windowsFormRules" label-position="top" label-width="100px">
                <el-row :gutter="[20, 20]">
                  <el-col :xs="24" :sm="12" :md="8">
                    <el-form-item label="盘符号" prop="driveLetter">
                      <el-input v-model="disk.driveLetter" placeholder="如C:、D:"></el-input>
                    </el-form-item>
                  </el-col>
                  <el-col :xs="24" :sm="12" :md="8">
                    <el-form-item label="容量" prop="capacity">
                      <el-input v-model="disk.capacity" placeholder="如100GB、500MB、2TB"></el-input>
                    </el-form-item>
                  </el-col>
                  <el-col :xs="24" :sm="12" :md="8">
                    <el-form-item label="已使用空间" prop="usedSpace">
                      <el-input v-model="disk.usedSpace" placeholder="如50GB、200MB、1TB"></el-input>
                    </el-form-item>
                  </el-col>
                </el-row>
                <el-form-item label="磁盘信息备注" prop="notes">
                  <el-input v-model="disk.notes" type="textarea" :rows="2" maxlength="500" show-word-limit></el-input>
                </el-form-item>
                <div class="disk-form-actions">
                  <el-button type="danger" @click="removeDisk(index)" size="small">删除当前磁盘条目</el-button>
                </div>
              </el-form>
              
              <!-- Linux磁盘表单 -->
              <el-form v-else :model="disk" :rules="linuxFormRules" label-position="top" label-width="100px">
                <el-row :gutter="[20, 20]">
                  <el-col :xs="24" :sm="12" :md="8">
                    <el-form-item label="设备名称" prop="deviceName">
                      <el-input v-model="disk.deviceName" placeholder="如/dev/sda1、/dev/mapper/ao-root"></el-input>
                    </el-form-item>
                  </el-col>
                  <el-col :xs="24" :sm="12" :md="8">
                    <el-form-item label="文件系统类型" prop="fileSystemType">
                      <el-select v-model="disk.fileSystemType" placeholder="请选择文件系统类型">
                        <el-option label="ext4" value="ext4"></el-option>
                        <el-option label="xfs" value="xfs"></el-option>
                        <el-option label="btrfs" value="btrfs"></el-option>
                        <el-option label="tmpfs" value="tmpfs"></el-option>
                        <el-option label="swap" value="swap"></el-option>
                      </el-select>
                    </el-form-item>
                  </el-col>
                  <el-col :xs="24" :sm="12" :md="8">
                    <el-form-item label="容量" prop="capacity">
                      <el-input v-model="disk.capacity" placeholder="如100GB、500MB、2TB"></el-input>
                    </el-form-item>
                  </el-col>
                  <el-col :xs="24" :sm="12" :md="8">
                    <el-form-item label="已使用空间" prop="usedSpace">
                      <el-input v-model="disk.usedSpace" placeholder="如50GB、200MB、1TB"></el-input>
                    </el-form-item>
                  </el-col>
                  <el-col :xs="24" :sm="12" :md="8">
                    <el-form-item label="挂载点" prop="mountPoint">
                      <el-input v-model="disk.mountPoint" placeholder="如/、/home"></el-input>
                    </el-form-item>
                  </el-col>
                </el-row>
                <el-form-item label="磁盘信息备注" prop="notes">
                  <el-input v-model="disk.notes" type="textarea" :rows="2" maxlength="500" show-word-limit></el-input>
                </el-form-item>
                <div class="disk-form-actions">
                  <el-button type="danger" @click="removeDisk(index)" size="small">删除当前磁盘条目</el-button>
                </div>
              </el-form>
            </div>
          </div>
          
          <!-- 添加磁盘按钮 -->
          <div class="add-disk-section">
            <el-button type="primary" @click="addDisk" :disabled="diskForms.length >= 10" size="small">+ 添加磁盘</el-button>
            <span v-if="diskForms.length >= 10" class="text-sm text-gray-500 ml-2">最多支持添加10个磁盘条目</span>
          </div>
        </div>
        
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
    
    <!-- 自动提取磁盘信息对话框 -->
    <el-dialog
      v-model="extractDialogVisible"
      title="自动提取磁盘信息"
      width="80%"
      :close-on-click-modal="false"
    >
      <!-- 选项卡 -->
      <el-tabs v-model="extractType" class="mb-4">
        <!-- 截图粘贴选项卡 -->
        <el-tab-pane label="截图粘贴" name="screenshot">
          <div class="mb-4">
            <h6 class="text-bold">截图粘贴</h6>
            <p class="text-sm text-gray-600">请复制Windows磁盘信息截图，然后点击下方按钮粘贴并提取信息</p>
            <el-button type="primary" @click="extractFromScreenshot" class="mt-2" :loading="isExtracting">粘贴截图提取信息</el-button>
          </div>
          
          <!-- 截图预览区域 -->
          <div 
            class="screenshot-preview mb-4" 
            style="border: 1px dashed #ccc; border-radius: 4px; height: 300px; display: flex; align-items: center; justify-content: center; background-color: #f5f7fa; overflow: hidden;"
            ref="screenshotPreview"
          >
            <img 
              v-if="screenshotData" 
              :src="screenshotData" 
              style="max-width: 100%; max-height: 100%; object-fit: contain;"
              alt="截图预览"
            >
            <p v-else class="text-gray-500">截图预览区域</p>
          </div>
          
          <!-- 提取结果区域 -->
          <div v-if="screenshotExtracted" class="mb-4">
            <h6 class="text-bold">提取结果</h6>
            <el-divider></el-divider>
            <el-table :data="screenshotDisks" size="small" border>
              <el-table-column label="盘符号" prop="driveLetter"></el-table-column>
              <el-table-column label="容量" prop="capacity"></el-table-column>
              <el-table-column label="已使用" prop="usedSpace"></el-table-column>
              <el-table-column label="可用空间" prop="freeSpace"></el-table-column>
              <el-table-column label="使用率" prop="usage"></el-table-column>
              <el-table-column label="文件系统" prop="fileSystem"></el-table-column>
            </el-table>
          </div>
          
          <!-- OCR原始识别内容 -->
          <div v-if="ocrText" class="mb-4">
            <h6 class="text-bold">OCR原始识别内容</h6>
            <el-divider></el-divider>
            <el-collapse>
              <el-collapse-item title="查看OCR识别原始文本">
                <pre style="background-color: #f5f7fa; padding: 10px; border-radius: 4px; max-height: 300px; overflow-y: auto; font-size: 12px; line-height: 1.5;">{{ ocrText }}</pre>
              </el-collapse-item>
            </el-collapse>
          </div>
        </el-tab-pane>
        
        <!-- 命令输出选项卡 -->
        <el-tab-pane label="命令输出" name="command">
          <div class="mb-4">
            <h6 class="text-bold">命令输出</h6>
            <p class="text-sm text-gray-600 mb-2">请粘贴Linux系统下的命令输出，支持df -h和blkid命令</p>
            <el-textarea
              v-model="commandOutput"
              placeholder="请粘贴命令输出，例如df -h或blkid命令的输出结果"
              :rows="10"
              class="mb-2"
            ></el-textarea>
            <el-button type="primary" @click="extractFromCommand" class="mt-2">解析命令输出</el-button>
          </div>
        </el-tab-pane>
      </el-tabs>
      
      <!-- 提取结果区域 -->
      <div v-if="extractedDisks.length > 0" class="mb-4">
        <h6 class="text-bold">提取结果</h6>
        <el-divider></el-divider>
        <el-table :data="extractedDisks" size="small" border>
          <el-table-column label="设备名称" prop="deviceName" v-if="!isWindows"></el-table-column>
          <el-table-column label="盘符号" prop="driveLetter" v-if="isWindows"></el-table-column>
          <el-table-column label="容量(GB)" prop="capacityGb"></el-table-column>
          <el-table-column label="已使用空间(GB)" prop="usedSpaceGb"></el-table-column>
          <el-table-column label="挂载点" prop="mountPoint" v-if="!isWindows"></el-table-column>
        </el-table>
      </div>
      
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="extractDialogVisible = false">取消</el-button>
          <el-button type="primary" :disabled="extractedDisks.length === 0" @click="confirmExtract">确认导入</el-button>
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
// 导入Tesseract.js库
import { createWorker } from 'tesseract.js';

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
      // 磁盘信息数据
      diskForms: [this.getEmptyDiskForm()],
      // 自动提取磁盘信息对话框
      extractDialogVisible: false,
      extractType: 'screenshot', // 或 'command'
      commandOutput: '',
      extractedDisks: [],
      // 截图提取相关变量
      isExtracting: false,
      screenshotData: '',
      screenshotExtracted: false,
      screenshotDisks: [],
      ocrText: '', // OCR原始识别文本
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
      // 验证带单位的存储容量
      validateStorageUnit(rule, value, callback) {
        if (!value) {
          callback(new Error('请输入容量'));
          return;
        }
        
        // 正则表达式：支持数字（整数或小数）+ 单位（MB、GB、TB），不区分大小写
        const regex = /^\s*(\d+(?:\.\d+)?)\s*(MB|GB|TB)\s*$/i;
        if (!regex.test(value)) {
          callback(new Error('容量格式不正确，如100GB、500MB、2TB'));
          return;
        }
        
        // 验证数值部分大于等于0
        const match = value.match(regex);
        const numValue = parseFloat(match[1]);
        if (numValue < 0) {
          callback(new Error('容量必须大于等于0'));
          return;
        }
        
        callback();
      },
      
      // Windows磁盘表单验证规则
      windowsFormRules: {
        driveLetter: [
          { required: true, message: '请输入盘符号', trigger: 'blur' },
          { pattern: /^[A-Za-z]:$/, message: '盘符号格式不正确，如C:、D:', trigger: 'blur' }
        ],
        capacity: [
          { required: true, message: '请输入容量', trigger: 'blur' },
          { validator: 'validateStorageUnit', trigger: 'blur' }
        ],
        usedSpace: [
          { required: true, message: '请输入已使用空间', trigger: 'blur' },
          { validator: 'validateStorageUnit', trigger: 'blur' }
        ],
        notes: [
          { max: 500, message: '备注信息不能超过500个字符', trigger: 'blur' }
        ]
      },
      // Linux磁盘表单验证规则
      linuxFormRules: {
        deviceName: [
          { required: true, message: '请输入设备名称', trigger: 'blur' },
          { pattern: /^\/dev\/(?:[a-zA-Z0-9]+|mapper\/[a-zA-Z0-9_-]+)$/, message: '设备名称格式不正确，如/dev/sda1或/dev/mapper/ao-root', trigger: 'blur' }
        ],
        fileSystemType: [
          { required: true, message: '请选择文件系统类型', trigger: 'change' }
        ],
        capacity: [
          { required: true, message: '请输入容量', trigger: 'blur' },
          { validator: 'validateStorageUnit', trigger: 'blur' }
        ],
        usedSpace: [
          { required: true, message: '请输入已使用空间', trigger: 'blur' },
          { validator: 'validateStorageUnit', trigger: 'blur' }
        ],
        mountPoint: [
          { required: true, message: '请输入挂载点', trigger: 'blur' },
          { pattern: /^\//, message: '挂载点必须以/开头', trigger: 'blur' }
        ],
        notes: [
          { max: 500, message: '备注信息不能超过500个字符', trigger: 'blur' }
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
  computed: {
    isWindows() {
      return this.formData.server_cred_server_os.toLowerCase().includes('windows');
    }
  },
  watch: {
    'formData.server_cred_server_os'() {
      // 操作系统类型变化时，重置磁盘表单
      this.diskForms = [this.getEmptyDiskForm()];
    }
  },
  methods: {
    // 获取空磁盘表单
    getEmptyDiskForm() {
      if (this.isWindows) {
        return {
          driveLetter: '',
          capacity: '',
          usedSpace: '',
          notes: ''
        };
      } else {
        return {
          deviceName: '',
          fileSystemType: '',
          capacity: '',
          usedSpace: '',
          mountPoint: '',
          notes: ''
        };
      }
    },
    // 添加磁盘条目
    addDisk() {
      if (this.diskForms.length < 10) {
        this.diskForms.push(this.getEmptyDiskForm());
      }
    },
    // 删除磁盘条目
    removeDisk(index) {
      this.$confirm('确定要删除当前磁盘条目吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(() => {
        this.diskForms.splice(index, 1);
        this.$message({
          type: 'success',
          message: '删除成功'
        });
      }).catch(() => {
        // 取消删除
      });
    },
    // 显示自动提取磁盘信息对话框
    showExtractDialog() {
      this.extractDialogVisible = true;
    },
    // 从截图提取磁盘信息
    async extractFromScreenshot() {
      this.isExtracting = true;
      this.screenshotExtracted = false;
      this.screenshotDisks = [];
      
      try {
        // 检查浏览器是否支持Clipboard API
        if (!navigator.clipboard) {
          this.$message.error('您的浏览器不支持剪贴板API，请使用现代浏览器');
          return;
        }
        
        // 尝试从剪贴板获取图像数据
        const clipboardItems = await navigator.clipboard.read();
        let imageBlob = null;
        
        // 查找图像类型的剪贴板项
        for (const item of clipboardItems) {
          const types = item.types;
          for (const type of types) {
            if (type.startsWith('image/')) {
              imageBlob = await item.getType(type);
              break;
            }
          }
          if (imageBlob) break;
        }
        
        if (!imageBlob) {
          this.$message.error('剪贴板中没有图像数据，请先复制截图');
          return;
        }
        
        // 将Blob转换为Base64字符串，用于预览
        const reader = new FileReader();
        const base64Data = await new Promise((resolve, reject) => {
          reader.onloadend = () => {
            resolve(reader.result);
          };
          reader.onerror = reject;
          reader.readAsDataURL(imageBlob);
        });
        
        this.screenshotData = base64Data;
        
        // 初始化Tesseract worker
        const worker = await createWorker('eng', 1, { logger: m => console.log(m) });
        
        // 执行OCR识别
        const { data: { text } } = await worker.recognize(imageBlob);
        this.ocrText = text; // 保存OCR原始识别结果
        console.log('OCR识别结果:', text);
        
        // 关闭worker
        await worker.terminate();
        
        // 解析识别结果，提取磁盘信息
        const disks = this.parseDiskInfo(text);
        
        if (disks.length === 0) {
          console.log('OCR识别结果:', text);
          this.$message.warning('未识别到磁盘信息，请确保截图清晰且包含磁盘信息。OCR识别结果已输出到控制台，方便调试。');
          return;
        }
        
        this.screenshotDisks = disks;
        this.screenshotExtracted = true;
        
        // 处理提取的磁盘信息，计算已用空间
        this.extractedDisks = disks.map(disk => {
          // 解析容量和可用空间
          const capacityGb = this.parseCapacityToGB(disk.capacity);
          const freeSpaceGb = this.parseCapacityToGB(disk.freeSpace);
          
          // 计算已用空间：总容量 - 可用空间
          let usedSpaceGb = null;
          if (capacityGb !== null && freeSpaceGb !== null) {
            usedSpaceGb = capacityGb - freeSpaceGb;
          } else if (disk.usedSpace) {
            // 如果已有已用空间信息（从其他格式提取），直接使用
            usedSpaceGb = this.parseCapacityToGB(disk.usedSpace);
          }
          
          return {
            driveLetter: disk.driveLetter,
            capacityGb: capacityGb,
            usedSpaceGb: usedSpaceGb,
            mountPoint: disk.driveLetter // Windows系统下，盘符号作为挂载点
          };
        });
        
        this.$message.success(`截图识别成功，共提取到 ${disks.length} 个磁盘信息`);
      } catch (error) {
        console.error('截图提取失败:', error);
        this.$message.error('截图提取失败，请确保截图清晰且包含磁盘信息，或尝试其他方式');
      } finally {
        this.isExtracting = false;
      }
    },
    
    // 从识别文本中解析磁盘信息
    parseDiskInfo(text) {
      const disks = [];
      console.log('开始解析磁盘信息，原始文本:', text);
      
      // 正则表达式1：匹配Windows文件资源管理器格式 - 优化版本
      // 支持多行格式，例如：
      // 系统 (C:)
      // 272 GB 可用，共 446 GB
      const explorerRegex = /([^\n]+?)\s*\(([A-Z]+):\)[\s\S]*?([\d.]+)\s+([GMKTB]+)\s+可用，共\s+([\d.]+)\s+([GMKTB]+)/g;
      let match;
      
      console.log('尝试匹配Windows文件资源管理器格式...');
      while ((match = explorerRegex.exec(text)) !== null) {
        console.log('匹配到文件资源管理器格式:', match);
        const [, volumeLabel, driveLetter, freeSpace, freeUnit, totalCapacity, capUnit] = match;
        const free = freeSpace + ' ' + freeUnit;
        const capacity = totalCapacity + ' ' + capUnit;
        
        disks.push({
          driveLetter: driveLetter + ':',
          capacity: capacity,
          freeSpace: free,
          usedSpace: '',
          volumeLabel: volumeLabel.trim()
        });
      }
      
      // 如果没有匹配到，尝试匹配命令行格式
      if (disks.length === 0) {
        console.log('尝试匹配Windows命令行格式...');
        // 正则表达式2：匹配Windows命令行格式
        // 示例："C:   NTFS  100GB  50GB  50GB  50%"
        const diskRegex = /([A-Z]:)\s+([A-Za-z0-9]+)\s+([\d.]+[GMKTB]+)\s+([\d.]+[GMKTB]+)\s+([\d.]+[GMKTB]+)\s+(\d+)%/g;
        
        while ((match = diskRegex.exec(text)) !== null) {
          console.log('匹配到命令行格式:', match);
          const [, driveLetter, fileSystem, capacity, usedSpace, freeSpace, usage] = match;
          
          disks.push({
            driveLetter,
            fileSystem,
            capacity,
            usedSpace,
            freeSpace,
            usage: usage + '%'
          });
        }
      }
      
      // 如果仍然没有匹配到，尝试匹配磁盘管理界面格式
      if (disks.length === 0) {
        console.log('尝试匹配Windows磁盘管理界面格式...');
        // 正则表达式3：匹配Windows磁盘管理界面格式
        // 示例："卷  C  操作系统  NTFS  99 GB  30 GB  69 GB  31%"
        const altRegex = /卷\s+([A-Z])\s+[\u4e00-\u9fa5]+\s+([A-Za-z0-9]+)\s+([\d.]+)\s+([GMKTB]+)\s+([\d.]+)\s+([GMKTB]+)\s+([\d.]+)\s+([GMKTB]+)\s+(\d+)%/g;
        while ((match = altRegex.exec(text)) !== null) {
          console.log('匹配到磁盘管理界面格式:', match);
          const [, driveLetter, fileSystem, , capacityUnit, , usedUnit, , freeUnit, usage] = match;
          const capacity = match[3] + capacityUnit;
          const usedSpace = match[5] + usedUnit;
          const freeSpace = match[7] + freeUnit;
          
          disks.push({
            driveLetter: driveLetter + ':',
            fileSystem,
            capacity,
            usedSpace,
            freeSpace,
            usage: usage + '%'
          });
        }
      }
      
      // 如果仍然没有匹配到，尝试匹配简化的文件资源管理器格式
      if (disks.length === 0) {
        console.log('尝试匹配简化的文件资源管理器格式...');
        // 正则表达式4：简化的文件资源管理器格式，不依赖卷标
        const simpleRegex = /([A-Z]+):\s+([\d.]+)\s+([GMKTB]+)\s+可用，共\s+([\d.]+)\s+([GMKTB]+)/g;
        while ((match = simpleRegex.exec(text)) !== null) {
          console.log('匹配到简化格式:', match);
          const [, driveLetter, freeSpace, freeUnit, totalCapacity, capUnit] = match;
          const free = freeSpace + ' ' + freeUnit;
          const capacity = totalCapacity + ' ' + capUnit;
          
          disks.push({
            driveLetter: driveLetter + ':',
            capacity: capacity,
            freeSpace: free,
            usedSpace: ''
          });
        }
      }
      
      console.log('最终解析结果:', disks);
      return disks;
    },
    // 从命令输出提取磁盘信息
    extractFromCommand() {
      if (!this.commandOutput) {
        this.$message.error('请输入命令输出');
        return;
      }
      
      const lines = this.commandOutput.split('\n');
      const disks = [];
      
      // 跳过表头
      for (let i = 1; i < lines.length; i++) {
        const line = lines[i].trim();
        if (!line) continue;
        
        // 解析df -h输出
        const dfRegex = /^([\/\w\-]+)\s+([\d.]+[GMK])\s+([\d.]+[GMK])\s+([\d.]+[GMK])\s+(\d+)%\s+([\/\w\-]+)$/;
        const match = dfRegex.exec(line);
        
        if (match) {
          const device = match[1];
          const size = this.convertToGB(match[2]);
          const used = this.convertToGB(match[3]);
          const mountPoint = match[6];
          
          disks.push({
            deviceName: device,
            capacityGb: size,
            usedSpaceGb: used,
            mountPoint: mountPoint
          });
        }
      }
      
      this.extractedDisks = disks;
      this.$message.success('提取成功，共找到' + disks.length + '个磁盘');
    },
    // 单位转换（GB、MB、KB to GB）
    convertToGB(sizeStr) {
      const size = parseFloat(sizeStr);
      const unit = sizeStr.slice(-1);
      
      switch (unit) {
        case 'G':
          return size;
        case 'M':
          return size / 1024;
        case 'K':
          return size / (1024 * 1024);
        default:
          return size;
      }
    },
    
    // 将GB为单位的数值转换为带合适单位的字符串（自动选择MB/GB/TB）
    formatStorageCapacity(gbValue) {
      if (gbValue === null || gbValue === undefined) {
        return '';
      }
      
      const value = parseFloat(gbValue);
      
      // 根据数值大小选择合适的单位
      if (value < 1) {
        // 小于1GB，使用MB
        const mbValue = value * 1024;
        return `${mbValue.toFixed(2)}MB`;
      } else if (value < 1024) {
        // 1GB到1024GB之间，使用GB
        return `${value.toFixed(2)}GB`;
      } else {
        // 大于等于1024GB，使用TB
        const tbValue = value / 1024;
        return `${tbValue.toFixed(2)}TB`;
      }
    },
    
    // 将带单位的容量字符串转换为GB为单位的数值
    parseCapacityToGB(capacityStr) {
      if (!capacityStr) {
        return null;
      }
      
      // 正则表达式：支持数字（整数或小数）+ 单位（MB、GB、TB），不区分大小写
      const regex = /^\s*(\d+(?:\.\d+)?)\s*(MB|GB|TB)\s*$/i;
      const match = capacityStr.match(regex);
      
      if (match) {
        const value = parseFloat(match[1]);
        const unit = match[2].toUpperCase();
        
        switch (unit) {
          case 'MB':
            return value / 1024;
          case 'GB':
            return value;
          case 'TB':
            return value * 1024;
          default:
            return null;
        }
      }
      
      return null;
    },
    // 确认提取结果并填充到表单
    confirmExtract() {
      if (this.extractedDisks.length === 0) {
        this.$message.error('请先提取磁盘信息');
        return;
      }
      
      // 清空当前磁盘表单
      this.diskForms = [];
      
      // 将提取的磁盘信息填充到表单
      this.extractedDisks.forEach(disk => {
        if (this.isWindows) {
          this.diskForms.push({
            driveLetter: '',
            capacity: `${disk.capacityGb}GB`,
            usedSpace: `${disk.usedSpaceGb}GB`,
            notes: ''
          });
        } else {
          this.diskForms.push({
            deviceName: disk.deviceName,
            fileSystemType: '',
            capacity: `${disk.capacityGb}GB`,
            usedSpace: `${disk.usedSpaceGb}GB`,
            mountPoint: disk.mountPoint,
            notes: ''
          });
        }
      });
      
      this.extractDialogVisible = false;
      this.$message.success('磁盘信息已填充到表单');
    },
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
        server_cred_created_by: username,
        disks: this.diskForms
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