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
          
          <!-- 统一表单标签（Windows和Linux都显示，根据系统类型调整内容） -->
          <div v-if="isWindows || !isWindows" style="width: 100%; margin-bottom: 1px; padding-bottom: 1px; border-bottom: 1px solid #ebeef5; height: 24px; overflow: visible;">
            <div v-if="isWindows" style="float: left; width: 80px; font-weight: 500; margin-right: 15px;">盘符号</div>
            <div v-else style="float: left; width: 120px; font-weight: 500; margin-right: 15px;">设备名称</div>
            <div style="float: left; width: 100px; font-weight: 500; margin-right: 15px;">{{ isWindows ? '容量' : '文件系统类型' }}</div>
            <div style="float: left; width: 120px; font-weight: 500; margin-right: 15px;">{{ isWindows ? '已使用空间' : '容量' }}</div>
            <div style="float: left; width: 120px; font-weight: 500; margin-right: 15px;">{{ isWindows ? '' : '已使用空间' }}</div>
            <div style="float: left; width: 100px; font-weight: 500; margin-right: 15px;">{{ isWindows ? '' : '挂载点' }}</div>
            <div style="float: left; font-weight: 500;">磁盘信息备注</div>
            <div style="clear: both;"></div>
          </div>
          
          <!-- 动态磁盘表单列表 -->
          <div class="disk-forms-list">
            <div v-for="(disk, index) in diskForms" :key="index" class="disk-form-item mb-2 p-1 border rounded bg-gray-50">
              <!-- Windows磁盘表单 -->
              <el-form v-if="isWindows" :model="disk" :rules="windowsFormRules" label-position="top" label-width="0" inline class="flex items-center w-full">
                <!-- 盘符号 -->
                <el-form-item prop="driveLetter" style="margin-bottom: 0; width: 80px; margin-right: 15px;">
                  <el-input v-model="disk.driveLetter" placeholder="如C:、D:" style="width: 80px;"></el-input>
                </el-form-item>
                
                <!-- 容量 -->
                <el-form-item prop="capacity" style="margin-bottom: 0; width: 120px; margin-right: 15px;">
                  <el-input v-model="disk.capacity" placeholder="如100GB" style="width: 120px;"></el-input>
                </el-form-item>
                
                <!-- 已使用空间 -->
                <el-form-item prop="usedSpace" style="margin-bottom: 0; width: 140px; margin-right: 15px;">
                  <el-input v-model="disk.usedSpace" placeholder="如50GB" style="width: 140px;"></el-input>
                </el-form-item>
                
                <!-- 磁盘信息备注和删除按钮 -->
                <div style="flex: 1; display: flex; align-items: center;">
                  <el-form-item prop="notes" style="margin-bottom: 0; flex: 1; margin-right: 10px;">
                    <el-input 
                      v-model="disk.notes" 
                      type="textarea" 
                      :rows="1" 
                      maxlength="500" 
                      show-word-limit
                      placeholder="请输入磁盘信息备注"
                      style="width: 100%;"
                    ></el-input>
                  </el-form-item>
                  <el-button 
                    type="danger" 
                    @click="removeDisk(index)" 
                    size="small" 
                    circle
                  >
                    <el-icon><Delete /></el-icon>
                  </el-button>
                </div>
              </el-form>
              
              <!-- Linux磁盘表单 -->
              <el-form v-else :model="disk" :rules="linuxFormRules" label-position="top" label-width="0" inline class="flex items-center w-full">
                <!-- 设备名称 -->
                <el-form-item prop="deviceName" style="margin-bottom: 0; width: 120px; margin-right: 15px;">
                  <el-input v-model="disk.deviceName" placeholder="如/dev/sda1" style="width: 120px;"></el-input>
                </el-form-item>
                
                <!-- 文件系统类型 -->
                <el-form-item prop="fileSystemType" style="margin-bottom: 0; width: 100px; margin-right: 15px;">
                  <el-select v-model="disk.fileSystemType" placeholder="选择类型" style="width: 100px;">
                    <el-option label="ext4" value="ext4"></el-option>
                    <el-option label="xfs" value="xfs"></el-option>
                    <el-option label="btrfs" value="btrfs"></el-option>
                    <el-option label="tmpfs" value="tmpfs"></el-option>
                    <el-option label="swap" value="swap"></el-option>
                  </el-select>
                </el-form-item>
                
                <!-- 容量 -->
                <el-form-item prop="capacity" style="margin-bottom: 0; width: 120px; margin-right: 15px;">
                  <el-input v-model="disk.capacity" placeholder="如100GB" style="width: 120px;"></el-input>
                </el-form-item>
                
                <!-- 已使用空间 -->
                <el-form-item prop="usedSpace" style="margin-bottom: 0; width: 120px; margin-right: 15px;">
                  <el-input v-model="disk.usedSpace" placeholder="如50GB" style="width: 120px;"></el-input>
                </el-form-item>
                
                <!-- 挂载点 -->
                <el-form-item prop="mountPoint" style="margin-bottom: 0; width: 100px; margin-right: 15px;">
                  <el-input v-model="disk.mountPoint" placeholder="如/" style="width: 100px;"></el-input>
                </el-form-item>
                
                <!-- 磁盘信息备注和删除按钮 -->
                <div style="flex: 1; display: flex; align-items: center;">
                  <el-form-item prop="notes" style="margin-bottom: 0; flex: 1; margin-right: 10px;">
                    <el-input 
                      v-model="disk.notes" 
                      type="textarea" 
                      :rows="1" 
                      maxlength="500" 
                      show-word-limit
                      placeholder="请输入磁盘信息备注"
                      style="width: 100%;"
                    ></el-input>
                  </el-form-item>
                  <el-button 
                    type="danger" 
                    @click="removeDisk(index)" 
                    size="small" 
                    circle
                  >
                    <el-icon><Delete /></el-icon>
                  </el-button>
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
      <!-- 切换按钮 -->
      <div class="disk-extract-tabs mb-4">
        <el-button 
          type="primary" 
          :plain="extractType !== 'windows'" 
          @click="extractType = 'windows'" 
          class="mr-2"
          size="large"
        >
          windows磁盘提取
        </el-button>
        <el-button 
          type="primary" 
          :plain="extractType !== 'linux'" 
          @click="extractType = 'linux'" 
          size="large"
        >
          linux磁盘提取
        </el-button>
      </div>
      
      <!-- Windows磁盘提取页面 -->
      <div v-if="extractType === 'windows'" class="windows-disk-extract">
        <!-- 命令复制区域 -->
        <div class="command-copy-section mb-4">
          <p class="text-sm text-gray-600 mb-2">请在Windows服务器上执行以下命令，然后将输出结果粘贴到下方文本框中：</p>
          <div class="flex items-center mb-2">
            <el-input
              v-model="windowsCommand"
              readonly
              class="mr-2"
            ></el-input>
            <el-button type="primary" @click="copyWindowsCommand">复制命令</el-button>
          </div>
        </div>
        
        <!-- 文本输入区域 -->
        <div class="command-output-section mb-4" style="min-height: 200px; margin-top: 20px;">
          <h4 style="margin-bottom: 10px; font-weight: bold;">命令输出结果</h4>
          <p style="margin-bottom: 10px; font-size: 14px; color: #606266;">请将Windows服务器上执行wmic命令的完整输出结果粘贴到下方文本区域</p>
          <!-- 使用原生textarea代替Element Plus组件，确保兼容性 -->
          <textarea
            v-model="commandOutput"
            placeholder="在此粘贴命令输出结果..."
            rows="10"
            style="width: 100%; border: 2px solid #409EFF; border-radius: 4px; padding: 10px; font-size: 14px; font-family: inherit; min-height: 150px; background-color: #f9f9f9; resize: vertical;"
            @paste="handlePaste"
          ></textarea>
          <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
            <div v-if="commandOutput" style="font-size: 12px; color: #67C23A;">
              ✓ 已检测到粘贴内容，点击下方按钮解析
            </div>
            <button 
              v-if="commandOutput"
              @click="commandOutput = ''"
              style="background: none; border: none; color: #409EFF; cursor: pointer; font-size: 12px; padding: 0;"
            >
              清空
            </button>
          </div>
        </div>
        
        <!-- 解析按钮 -->
        <div class="parse-button-section mb-4">
          <el-button type="primary" @click="parseWindowsDiskInfo">解析并填充磁盘信息</el-button>
        </div>
        
        <!-- 解析结果预览 -->
        <div v-if="parsedDisks.length > 0" class="parse-result-section mb-4">
          <h6 class="text-bold mb-2">解析结果预览</h6>
          <el-table :data="parsedDisks" size="small" border>
            <el-table-column label="盘符号" prop="driveLetter"></el-table-column>
            <el-table-column label="容量" prop="capacity"></el-table-column>
            <el-table-column label="已使用空间" prop="usedSpace"></el-table-column>
            <el-table-column label="磁盘信息备注" prop="notes"></el-table-column>
          </el-table>
        </div>
      </div>
      
      <!-- Linux磁盘提取页面 -->
      <div v-else-if="extractType === 'linux'" class="linux-disk-extract">
        <!-- Linux磁盘提取内容 -->
        <div class="command-copy-section mb-4">
          <p class="text-sm text-gray-600 mb-2">请在Linux服务器上执行以下命令，然后将输出结果粘贴到下方文本框中：</p>
          <div class="flex items-center mb-2">
            <el-input
              v-model="linuxCommand"
              readonly
              class="mr-2"
            ></el-input>
            <el-button type="primary" @click="copyLinuxCommand">复制命令</el-button>
          </div>
        </div>
        
        <!-- 文本输入区域 -->
        <div class="command-output-section mb-4" style="min-height: 200px; margin-top: 20px;">
          <h4 style="margin-bottom: 10px; font-weight: bold;">命令输出结果</h4>
          <p style="margin-bottom: 10px; font-size: 14px; color: #606266;">请将Linux服务器上执行命令的完整输出结果粘贴到下方文本区域</p>
          <textarea
            v-model="linuxCommandOutput"
            placeholder="在此粘贴命令输出结果..."
            rows="10"
            style="width: 100%; border: 2px solid #409EFF; border-radius: 4px; padding: 10px; font-size: 14px; font-family: inherit; min-height: 150px; background-color: #f9f9f9; resize: vertical;"
            @paste="handleLinuxPaste"
          ></textarea>
          <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
            <div v-if="linuxCommandOutput" style="font-size: 12px; color: #67C23A;">
              ✓ 已检测到粘贴内容，点击下方按钮解析
            </div>
            <button 
              v-if="linuxCommandOutput"
              @click="linuxCommandOutput = ''"
              style="background: none; border: none; color: #409EFF; cursor: pointer; font-size: 12px; padding: 0;"
            >
              清空
            </button>
          </div>
        </div>
        
        <!-- 解析按钮 -->
        <div class="parse-button-section mb-4">
          <el-button type="primary" @click="parseLinuxDiskInfo">解析并填充磁盘信息</el-button>
        </div>
        
        <!-- 解析结果预览 -->
        <div v-if="parsedLinuxDisks.length > 0" class="parse-result-section mb-4">
          <h6 class="text-bold mb-2">解析结果预览</h6>
          <el-table :data="parsedLinuxDisks" size="small" border>
            <el-table-column label="设备名称" prop="deviceName"></el-table-column>
            <el-table-column label="文件系统类型" prop="fileSystemType"></el-table-column>
            <el-table-column label="容量" prop="capacity"></el-table-column>
            <el-table-column label="已使用空间" prop="usedSpace"></el-table-column>
            <el-table-column label="挂载点" prop="mountPoint"></el-table-column>
            <el-table-column label="磁盘信息备注" prop="notes"></el-table-column>
          </el-table>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script>
// 导入XLS文件解析库
import * as XLSX from 'xlsx';
// 导入Element Plus图标
import { DocumentAdd, RefreshRight, Check, Upload, EditPen, Delete } from '@element-plus/icons-vue';
// 导入Tesseract.js库
import { createWorker } from 'tesseract.js';

export default {
  name: 'ServerCredEntryView',
  components: {
    DocumentAdd,
    RefreshRight,
    Check,
    Upload,
    EditPen,
    Delete
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
      extractType: 'windows', // windows或linux
      commandOutput: '',
      extractedDisks: [],
      parsedDisks: [], // 解析后的Windows磁盘信息
      // Windows命令相关
      windowsCommand: 'wmic logicaldisk get DeviceID, VolumeName, Size, FreeSpace, FileSystem, Description',
      // Linux命令相关
      linuxCommand: 'echo "=====BLKID_OUTPUT=====" &&df -h && echo "=====BLKID_OUTPUT=====" && blkid',
      linuxCommandOutput: '',
      parsedLinuxDisks: [], // 解析后的Linux磁盘信息
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
    
    // 解析Windows磁盘信息
    parseWindowsDiskInfo() {
      if (!this.commandOutput) {
        this.$message.error('请粘贴命令输出结果');
        return;
      }
      
      try {
        const lines = this.commandOutput.split('\n');
        const parsedDisks = [];
        
        // 检查是否为Windows wmic命令输出
        // wmic输出格式包含"DeviceID"、"Size"、"FreeSpace"等关键字
        let isWmicOutput = false;
        for (const line of lines) {
          if (line.includes('DeviceID') && (line.includes('Size') || line.includes('FreeSpace'))) {
            isWmicOutput = true;
            break;
          }
        }
        
        // 如果不是wmic输出，提示用户
        if (!isWmicOutput) {
          // 检查是否包含Linux命令输出特征
          if (this.commandOutput.includes('df -h') || this.commandOutput.includes('=====BLKID_OUTPUT=====')) {
            this.$message.error('检测到Linux命令输出，请切换到Linux磁盘提取标签页进行解析');
            return;
          } else {
            this.$message.error('请粘贴有效的Windows wmic命令输出');
            return;
          }
        }
        
        // 跳过表头行，开始解析数据行
        for (let i = 1; i < lines.length; i++) {
          const line = lines[i].trim();
          if (!line) continue;
          
          // 解析wmic输出格式，字段之间用多个空格分隔
          // 实际格式：Description DeviceID FileSystem FreeSpace Size VolumeName
          // 示例：本地固定磁盘       C:          NTFS     289069330432 321543729152
          const parts = line.split(/\s+/);
          
          // 提取字段，注意VolumeName可能包含空格
          let description = '';
          let deviceID = '';
          let fileSystem = '';
          let freeSpace = '';
          let size = '';
          let volumeName = '';
          
          let index = 0;
          
          // 处理Description（可能包含空格，如"本地固定磁盘"）
          while (index < parts.length && !/^[A-Z]:$/.test(parts[index])) {
            description += (description ? ' ' : '') + parts[index++];
          }
          
          // 提取DeviceID（驱动器号格式，如C:）
          if (index < parts.length && /^[A-Z]:$/.test(parts[index])) {
            deviceID = parts[index++];
          }
          
          // 提取FileSystem
          if (index < parts.length && !isNaN(parts[index]) === false) {
            fileSystem = parts[index++];
          }
          
          // 提取FreeSpace（数字）
          if (index < parts.length && !isNaN(parts[index])) {
            freeSpace = parts[index++];
          }
          
          // 提取Size（数字）
          if (index < parts.length && !isNaN(parts[index])) {
            size = parts[index++];
          }
          
          // 剩余部分为VolumeName（可能包含空格）
          while (index < parts.length) {
            volumeName += (volumeName ? ' ' : '') + parts[index++];
          }
          
          // 排除光盘项
          if (description.includes('光盘')) {
            continue;
          }
          
          // 转换为数字
          const totalBytes = parseInt(size) || 0;
          const freeBytes = parseInt(freeSpace) || 0;
          const usedBytes = totalBytes - freeBytes;
          
          // 确保usedBytes为正数
          const positiveUsedBytes = Math.max(0, usedBytes);
          
          // 单位换算
          const totalCapacity = this.formatStorageSize(totalBytes);
          const usedSpace = this.formatStorageSize(positiveUsedBytes);
          
          // 只添加有效的磁盘信息（盘符号不能为空）
          if (deviceID) {
            parsedDisks.push({
              driveLetter: deviceID,
              capacity: totalCapacity,
              usedSpace: usedSpace,
              notes: description
            });
          }
        }
        
        if (parsedDisks.length === 0) {
          this.$message.warning('未解析到有效磁盘信息，请检查命令输出格式');
          return;
        }
        
        // 更新解析结果
        this.parsedDisks = parsedDisks;
        
        // 填充到表单
        this.fillDiskForms(parsedDisks);
        
        this.$message.success(`成功解析 ${parsedDisks.length} 个磁盘信息`);
      } catch (error) {
        console.error('解析磁盘信息失败:', error);
        this.$message.error('解析磁盘信息失败，请检查命令输出格式');
      }
    },
    
    // 格式化存储大小，保留一位小数，四舍五入
    formatStorageSize(bytes) {
      if (!bytes || bytes === 0) return '0GB';
      
      const kb = 1024;
      const mb = kb * 1024;
      const gb = mb * 1024;
      const tb = gb * 1024;
      
      let size = bytes;
      let unit = '';
      
      if (size >= tb) {
        // 转换为TB
        size = size / tb;
        unit = 'TB';
      } else if (size >= gb) {
        // 转换为GB
        size = size / gb;
        unit = 'GB';
      } else if (size >= mb) {
        // 转换为GB（小于1TB的都显示为GB）
        size = size / gb;
        unit = 'GB';
      } else {
        // 转换为GB（小于1GB的也显示为GB，如0.9GB）
        size = size / gb;
        unit = 'GB';
      }
      
      // 保留一位小数，四舍五入
      return `${size.toFixed(1)}${unit}`;
    },
    
    // 填充磁盘表单
    fillDiskForms(disks) {
      // 清空当前磁盘表单
      this.diskForms = [];
      
      // 填充解析后的磁盘信息
      disks.forEach(disk => {
        this.diskForms.push({
          driveLetter: disk.driveLetter || '',
          capacity: disk.capacity || '',
          usedSpace: disk.usedSpace || '',
          notes: disk.notes || ''
        });
      });
    },
    
    // 解析Linux磁盘信息
    parseLinuxDiskInfo() {
      if (!this.linuxCommandOutput) {
        this.$message.error('请粘贴命令输出结果');
        return;
      }
      
      try {
        // 分离df -h和blkid命令输出
        const outputs = this.linuxCommandOutput.split('=====BLKID_OUTPUT=====');
        
        // 找到df -h输出和blkid输出
        let dfOutput = '';
        let blkidOutput = '';
        
        // 遍历所有输出片段，寻找包含"文件系统"或"Filesystem"的片段（df -h输出）
        for (let i = 0; i < outputs.length; i++) {
          const output = outputs[i].trim();
          if (output.includes('文件系统') || output.includes('Filesystem')) {
            dfOutput = output;
            // blkid输出应该在df -h输出之后的片段中
            if (i + 1 < outputs.length) {
              blkidOutput = outputs.slice(i + 1).join('=====BLKID_OUTPUT=====').trim();
            }
            break;
          }
        }
        
        // 如果没有找到包含"文件系统"的片段，尝试查找包含"/dev/"的片段作为备选
        if (!dfOutput) {
          for (const output of outputs) {
            if (output.includes('/dev/')) {
              dfOutput = output;
              break;
            }
          }
        }
        
        // 解析df -h输出
        const dfDisks = this.parseDfOutput(dfOutput);
        
        // 解析blkid输出
        const blkidMap = this.parseBlkidOutput(blkidOutput);
        
        // 合并信息并排除系统默认挂载项
        const finalDisks = this.mergeAndFilterDisks(dfDisks, blkidMap);
        
        if (finalDisks.length === 0) {
          // 添加调试信息
          console.log('解析Linux磁盘信息调试：');
          console.log('原始输出:', this.linuxCommandOutput);
          console.log('分割结果:', outputs);
          console.log('dfOutput:', dfOutput);
          console.log('blkidOutput:', blkidOutput);
          console.log('dfDisks:', dfDisks);
          console.log('blkidMap:', blkidMap);
          
          // 更详细的错误提示
          if (dfDisks.length === 0) {
            this.$message.warning('未解析到df -h输出，请检查命令输出格式');
          } else {
            this.$message.warning('所有磁盘信息都被过滤掉了，请检查命令输出格式');
          }
          return;
        }
        
        // 更新解析结果
        this.parsedLinuxDisks = finalDisks;
        
        // 填充到表单
        this.fillLinuxDiskForms(finalDisks);
        
        this.$message.success(`成功解析 ${finalDisks.length} 个磁盘信息`);
      } catch (error) {
        console.error('解析Linux磁盘信息失败:', error);
        this.$message.error('解析磁盘信息失败，请检查命令输出格式');
      }
    },
    
    // 解析df -h命令输出
    parseDfOutput(output) {
      const lines = output.split('\n');
      const disks = [];
      let foundHeader = false;
      
      // 遍历所有行，跳过空行和命令行
      for (let i = 0; i < lines.length; i++) {
        let line = lines[i].trim();
        if (!line) continue;
        
        // 跳过命令提示符行（如[root@localhost ~]#）
        if (line.startsWith('[') && line.includes('#')) continue;
        
        // 跳过命令行
        if (line.includes('echo ') || line.includes('&&') || line.includes('blkid')) continue;
        
        // 跳过包含"=====BLKID_OUTPUT====="的行
        if (line.includes('=====BLKID_OUTPUT=====')) continue;
        
        // 标记表头行，从下一行开始解析数据
        if (line.includes('文件系统') || line.includes('Filesystem')) {
          foundHeader = true;
          continue;
        }
        
        // 只有在找到表头后才解析数据行，并且确保行包含有效磁盘信息
        if (foundHeader || (line.includes('/dev/') && !line.includes(' '))) {
          // 解析df -h输出格式，字段之间用多个空格分隔
          const parts = line.split(/\s+/);
          
          // 确保至少有6个字段（设备名称、容量、已用、可用、已用%、挂载点）
          if (parts.length >= 6) {
            // 找出包含%的字段索引（已用%）
            let usePercentIndex = -1;
            for (let j = 0; j < parts.length; j++) {
              if (parts[j].includes('%')) {
                usePercentIndex = j;
                break;
              }
            }
            
            // 如果找到了已用%字段，计算挂载点起始索引
            if (usePercentIndex >= 0) {
              const mountPointIndex = usePercentIndex + 1;
              
              // 合并挂载点字段（如果挂载点包含空格）
              const mountPoint = parts.slice(mountPointIndex).join(' ');
              
              disks.push({
                deviceName: parts[0],
                capacity: parts[1],
                usedSpace: parts[2],
                mountPoint: mountPoint
              });
            } else {
              // 兼容不同格式，尝试默认解析
              disks.push({
                deviceName: parts[0],
                capacity: parts[1],
                usedSpace: parts[2],
                mountPoint: parts[parts.length - 1]
              });
            }
          } else if (line.includes('/dev/')) {
            // 尝试解析简单格式的磁盘信息
            console.log('尝试解析简单格式行:', line);
          }
        }
      }
      
      // 如果没有找到表头但包含/dev/行，尝试重新解析所有行
      if (disks.length === 0) {
        for (let i = 0; i < lines.length; i++) {
          let line = lines[i].trim();
          if (!line) continue;
          
          // 直接查找包含/dev/的行，跳过其他行
          if (line.includes('/dev/') && line.split(/\s+/).length >= 6) {
            const parts = line.split(/\s+/);
            disks.push({
              deviceName: parts[0],
              capacity: parts[1],
              usedSpace: parts[2],
              mountPoint: parts[parts.length - 1]
            });
          }
        }
      }
      
      return disks;
    },
    
    // 解析blkid命令输出
    parseBlkidOutput(output) {
      const blkidMap = new Map();
      const lines = output.split('\n');
      
      for (const line of lines) {
        const trimmedLine = line.trim();
        if (!trimmedLine) continue;
        
        // 跳过命令行、空行和分隔符行
        if (trimmedLine.includes('blkid') && !trimmedLine.includes('/dev/')) continue;
        if (trimmedLine.includes('=====BLKID_OUTPUT=====')) continue;
        
        // 匹配设备路径和类型信息
        // 示例：/dev/sda1: UUID="..." TYPE="ext4" PARTUUID="..."
        const deviceMatch = trimmedLine.match(/^([^:]+):/);
        const typeMatch = trimmedLine.match(/TYPE="([^"]+)"/);
        
        if (deviceMatch && typeMatch) {
          const devicePath = deviceMatch[1];
          const fileSystemType = typeMatch[1];
          blkidMap.set(devicePath, fileSystemType);
        } else {
          console.log('跳过无效blkid行:', trimmedLine);
        }
      }
      
      return blkidMap;
    },
    
    // 合并信息并排除系统默认挂载项
    mergeAndFilterDisks(dfDisks, blkidMap) {
      const finalDisks = [];
      
      console.log('mergeAndFilterDisks调试：');
      console.log('dfDisks数量:', dfDisks.length);
      console.log('blkidMap条目数量:', blkidMap.size);
      console.log('blkidMap内容:', blkidMap);
      
      for (const disk of dfDisks) {
        console.log('处理磁盘:', disk);
        
        // 排除tmpfs、devtmpfs、overlay类型的文件系统
        if (disk.deviceName.startsWith('tmpfs') || 
            disk.deviceName.startsWith('devtmpfs') || 
            disk.deviceName.startsWith('overlay')) {
          console.log('排除系统默认挂载项:', disk.deviceName);
          continue;
        }
        
        // 排除/run/user/*和/sys/fs/cgroup路径的挂载点
        if (disk.mountPoint.startsWith('/run/user/') || 
            disk.mountPoint === '/sys/fs/cgroup') {
          console.log('排除系统默认挂载点:', disk.mountPoint);
          continue;
        }
        
        // 匹配文件系统类型
        let fileSystemType = '';
        
        // 直接匹配设备名称
        if (blkidMap.has(disk.deviceName)) {
          fileSystemType = blkidMap.get(disk.deviceName);
          console.log('直接匹配到文件系统类型:', fileSystemType);
        } else {
          // 对于LVM设备（如/dev/mapper/centos-root）
          if (disk.deviceName.startsWith('/dev/mapper/')) {
            // 尝试从blkidMap中查找相关的物理卷
            // 遍历blkidMap，找到对应的文件系统类型
            let foundType = '';
            for (const [device, type] of blkidMap) {
              // 如果是xfs或ext4类型，直接使用
              if (type === 'xfs' || type === 'ext4') {
                foundType = type;
                break;
              }
            }
            fileSystemType = foundType || 'xfs'; // 默认类型
            console.log('LVM设备使用文件系统类型:', fileSystemType);
          } else {
            // 尝试匹配分区名称（如/dev/sda1 -> /dev/sda）
            const parentDevice = disk.deviceName.replace(/\d+$/, '');
            if (blkidMap.has(parentDevice)) {
              fileSystemType = blkidMap.get(parentDevice);
              console.log('匹配到父设备类型:', fileSystemType);
            } else {
              // 尝试匹配类似的设备（如/dev/vda1 -> /dev/vda）
              let matchingDevice = '';
              for (const [device, type] of blkidMap) {
                if (disk.deviceName.includes(device)) {
                  matchingDevice = device;
                  fileSystemType = type;
                  break;
                }
              }
              console.log('匹配到类似设备:', matchingDevice, '类型:', fileSystemType);
            }
          }
        }
        
        // 即使没有匹配到文件系统类型，也添加到最终结果
        finalDisks.push({
          deviceName: disk.deviceName,
          fileSystemType: fileSystemType,
          capacity: disk.capacity,
          usedSpace: disk.usedSpace,
          mountPoint: disk.mountPoint,
          notes: ''
        });
        console.log('添加到最终结果:', disk.deviceName);
      }
      
      console.log('最终磁盘数量:', finalDisks.length);
      return finalDisks;
    },
    
    // 填充Linux磁盘表单
    fillLinuxDiskForms(disks) {
      // 清空当前磁盘表单
      this.diskForms = [];
      
      // 填充解析后的磁盘信息
      disks.forEach(disk => {
        this.diskForms.push({
          deviceName: disk.deviceName || '',
          fileSystemType: disk.fileSystemType || '',
          capacity: disk.capacity || '',
          usedSpace: disk.usedSpace || '',
          mountPoint: disk.mountPoint || '',
          notes: disk.notes || ''
        });
      });
    },
    
    // 处理粘贴事件
    handlePaste() {
      // 可以在这里添加粘贴后的处理逻辑
      this.$nextTick(() => {
        if (this.commandOutput) {
          this.$message.success('内容已粘贴成功，请点击解析按钮');
        }
      });
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
    // 复制Windows命令到剪贴板
    copyWindowsCommand() {
      navigator.clipboard.writeText(this.windowsCommand)
        .then(() => {
          this.$message.success('命令已成功复制到剪贴板');
        })
        .catch(err => {
          console.error('复制命令失败:', err);
          this.$message.error('复制命令失败，请手动复制');
        });
    },
    
    // 复制Linux命令到剪贴板
    copyLinuxCommand() {
      navigator.clipboard.writeText(this.linuxCommand)
        .then(() => {
          this.$message.success('命令已成功复制到剪贴板');
        })
        .catch(err => {
          console.error('复制命令失败:', err);
          this.$message.error('复制命令失败，请手动复制');
        });
    },
    
    // 从字节转换为GB
    bytesToGB(bytes) {
      if (!bytes || isNaN(bytes)) {
        return null;
      }
      return bytes / (1024 * 1024 * 1024);
    },
    
    // 格式化存储容量，自动选择合适的单位
    formatStorage(bytes) {
      if (!bytes || isNaN(bytes)) {
        return '';
      }
      
      const tb = bytes / (1024 * 1024 * 1024 * 1024);
      const gb = bytes / (1024 * 1024 * 1024);
      const mb = bytes / (1024 * 1024);
      
      if (tb >= 1) {
        // 大于等于1TB，使用TB，保留1位小数
        return `${tb.toFixed(1)}TB`;
      } else if (gb >= 1) {
        // 大于等于1GB，使用GB，四舍五入为整数
        return `${Math.round(gb)}GB`;
      } else if (mb >= 1) {
        // 大于等于1MB，使用MB，四舍五入为整数
        return `${Math.round(mb)}MB`;
      } else {
        // 小于1MB，使用KB，四舍五入为整数
        return `${Math.round(bytes / 1024)}KB`;
      }
    },
    
    // 从命令输出提取磁盘信息
    extractFromCommand() {
      if (!this.commandOutput) {
        this.$message.error('请输入命令输出');
        return;
      }
      
      const lines = this.commandOutput.split('\n');
      const disks = [];
      
      if (this.isWindows) {
        // Windows命令输出解析
        // 跳过表头行和空行
        for (let i = 1; i < lines.length; i++) {
          const line = lines[i].trim();
          if (!line) continue;
          
          // 使用正则表达式匹配wmic输出格式
          // 示例格式："本地固定磁盘  C:        NTFS        289096192000     321543729152"
          const winRegex = /^(\S.*?)\s+([A-Z]:)\s+(\w+)?\s+(\d+)\s+(\d+)$/;
          const match = winRegex.exec(line);
          
          if (match) {
            const description = match[1];
            const driveLetter = match[2];
            const fileSystem = match[3] || '';
            const freeSpace = parseInt(match[4]);
            const totalSize = parseInt(match[5]);
            
            // 过滤掉光盘记录
            if (description.includes('光盘')) {
              continue;
            }
            
            // 计算已使用空间
            const usedSpace = totalSize - freeSpace;
            
            // 转换为GB
            const capacityGb = this.bytesToGB(totalSize);
            const usedSpaceGb = this.bytesToGB(usedSpace);
            
            if (capacityGb !== null && usedSpaceGb !== null) {
              disks.push({
                driveLetter: driveLetter,
                capacityGb: capacityGb,
                usedSpaceGb: usedSpaceGb,
                description: description
              });
            }
          }
        }
      } else {
        // Linux命令输出解析
        // 跳过表头
        for (let i = 1; i < lines.length; i++) {
          const line = lines[i].trim();
          if (!line) continue;
          
          // 解析df -h输出
          const dfRegex = /^(\/[\w\-]+)\s+([\d.]+[GMK])\s+([\d.]+[GMK])\s+([\d.]+[GMK])\s+(\d+)%\s+(\/[\w\-]+)$/;
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
      }
      
      if (disks.length === 0) {
        this.$message.warning('未识别到磁盘信息，请确保命令输出格式正确');
        return;
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
          // 转换GB为字节，用于格式化
          const totalBytes = disk.capacityGb * 1024 * 1024 * 1024;
          const usedBytes = disk.usedSpaceGb * 1024 * 1024 * 1024;
          
          this.diskForms.push({
            driveLetter: disk.driveLetter || '',
            capacity: this.formatStorage(totalBytes),
            usedSpace: this.formatStorage(usedBytes),
            notes: disk.description || ''
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
    // 切换宿主机集群字段显示/隐藏
    toggleHostClusterField() {
      if (this.formData.server_cred_server_type === '虚拟机') {
        this.$nextTick(() => {
          this.$refs.hostClusterCol.style.display = 'block';
        });
      } else {
        this.$nextTick(() => {
          this.$refs.hostClusterCol.style.display = 'none';
        });
        this.formData.server_cred_host_cluster = '';
      }
    },
    // 处理操作系统类型变化
    handleOSTypeChange() {
      // 重置磁盘表单
      this.diskForms = [this.getEmptyDiskForm()];
    },
    // 验证宿主机集群字段
    validateHostCluster(rule, value, callback) {
      if (this.formData.server_cred_server_type === '虚拟机' && !value) {
        callback(new Error('请选择宿主机集群'));
      } else {
        callback();
      }
    },
    // 重置表单
    resetForm() {
      this.$refs.serverCredFormRef.resetFields();
      // 重置磁盘表单
      this.diskForms = [this.getEmptyDiskForm()];
      // 更新自动填充key，防止浏览器自动填充
      this.usernameKey = Date.now();
      this.passwordKey = Date.now();
      // 重置导入相关数据
      this.importDialogVisible = false;
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
      this.$message.success('表单已重置');
    },
    // 填充测试数据
    fillTestData() {
      this.formData = {
        server_cred_network_area: '内网',
        server_cred_server_type: '虚拟机',
        server_cred_host_cluster: '测试集群',
        server_cred_server_name: '测试服务器',
        server_cred_server_ip: '192.168.1.100',
        server_cred_server_os: 'Windows',
        server_cred_server_port: 22,
        server_cred_login_username: 'testuser',
        server_cred_login_password: 'testpassword',
        server_cred_edr_installed: '是',
        server_cred_ntp_configured: '是',
        server_cred_notes: '测试服务器，用于功能测试'
      };
      
      this.diskForms = [
        {
          driveLetter: 'C:',
          capacity: '100GB',
          usedSpace: '50GB',
          notes: '系统盘'
        },
        {
          driveLetter: 'D:',
          capacity: '500GB',
          usedSpace: '200GB',
          notes: '数据盘'
        }
      ];
      
      this.$message.success('测试数据已填充');
    },
    // 保存服务器信息
    saveServerCred() {
      this.$refs.serverCredFormRef.validate((valid) => {
        if (valid) {
          // 构建完整的表单数据，包括磁盘信息
          const fullData = {
            ...this.formData,
            diskInfo: this.diskForms,
            action: 'save_server_cred' // 添加action字段，用于后端识别请求类型
          };
          
          // 添加加载状态
          const loading = this.$loading({
            lock: true,
            text: '保存中...',
            spinner: 'el-icon-loading',
            background: 'rgba(0, 0, 0, 0.7)'
          });
          
          // 发送保存请求
          console.log('保存服务器信息:', fullData);
          
          // 设置请求超时时间（5秒）
          const timeoutPromise = new Promise((_, reject) => {
            setTimeout(() => {
              reject(new Error('请求超时'));
            }, 5000);
          });
          
          // 使用fetch API发送POST请求，并添加超时处理
          Promise.race([
            fetch('server_cred_api.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify(fullData)
            }),
            timeoutPromise
          ])
          .then(response => {
            console.log('响应状态:', response.status);
            console.log('响应头:', response.headers);
            
            // 检查响应状态码
            if (!response.ok) {
              throw new Error(`HTTP错误! 状态码: ${response.status}`);
            }
            
            // 先读取响应为文本，然后尝试解析为JSON
            return response.text().then(text => {
              try {
                return JSON.parse(text);
              } catch (error) {
                // 如果解析失败，抛出错误
                throw new Error(`响应不是有效的JSON格式: ${text}`);
              }
            });
          })
          .then(data => {
            loading.close();
            console.log('响应数据:', data);
            
            // 检查响应状态
            if (data.status === 'success') {
              this.$message.success('服务器信息保存成功');
            } else {
              // 显示实际错误信息
              this.$message.error(`保存失败: ${data.message || '未知错误'}`);
            }
          })
          .catch(error => {
            loading.close();
            console.error('保存服务器信息失败:', error);
            
            // 根据错误类型显示更具体的错误信息
            if (error.message.includes('请求超时')) {
              this.$message.error('保存失败，请求超时，请稍后重试');
            } else if (error.message.includes('HTTP错误')) {
              this.$message.error(`保存失败，服务器错误: ${error.message}`);
            } else if (error.message.includes('响应不是有效的JSON格式')) {
              this.$message.error(`保存失败，服务器返回格式错误: ${error.message}`);
            } else if (error.message.includes('NetworkError')) {
              this.$message.error('保存失败，网络连接错误，请检查网络连接');
            } else {
              this.$message.error(`保存失败: ${error.message}`);
            }
          });
        } else {
          this.$message.error('表单验证失败，请检查填写内容');
          return false;
        }
      });
    },
    // 打开导入对话框
    openImportDialog() {
      this.importDialogVisible = true;
    },
    // 处理文件上传
    handleFileChange(file, fileList) {
      this.fileList = fileList;
      this.canImport = false;
      this.importData = [];
      this.previewHeaders = [];
      
      // 读取文件内容
      const reader = new FileReader();
      reader.onload = (e) => {
        const data = e.target.result;
        try {
          // 解析XLS文件
          const workbook = XLSX.read(data, { type: 'array' });
          const sheetName = workbook.SheetNames[0];
          const worksheet = workbook.Sheets[sheetName];
          
          // 将工作表转换为JSON数据
          const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
          
          if (jsonData.length < 2) {
            this.$message.error('文件内容为空或格式不正确');
            return;
          }
          
          // 获取表头
          this.previewHeaders = jsonData[0];
          
          // 处理数据行
          this.importData = jsonData.slice(1).filter(row => {
            // 过滤空行
            return row.some(cell => cell !== undefined && cell !== null && cell !== '');
          });
          
          this.canImport = this.importData.length > 0;
        } catch (error) {
          console.error('解析文件失败:', error);
          this.$message.error('文件格式不正确，请检查文件是否为有效的XLS文件');
        }
      };
      reader.readAsArrayBuffer(file.raw);
    },
    // 上传前检查
    beforeUpload(file) {
      const isXLS = file.type === 'application/vnd.ms-excel';
      if (!isXLS) {
        this.$message.error('请上传XLS格式的文件');
        return false;
      }
      return true;
    },
    // 开始导入
    startImport() {
      this.showProgress = true;
      this.importProgress = 0;
      this.progressStatus = 'success';
      this.progressText = '开始导入...';
      this.progressColor = '#67C23A';
      this.errors = [];
      
      // 模拟导入进度
      const timer = setInterval(() => {
        this.importProgress += 10;
        if (this.importProgress >= 100) {
          clearInterval(timer);
          this.progressStatus = 'success';
          this.progressText = '导入完成';
          this.$message.success('导入成功');
          this.importDialogVisible = false;
        }
      }, 300);
    },
    // 进度条格式化函数
    progressFormat(percentage) {
      return `${percentage}%`;
    }
  },
  mounted() {
    this.initForm();
  }
};
</script>

<style scoped>
/* 自定义样式 */
.server-cred-entry-view {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.disk-info-section {
  background-color: #f8f9fa;
}

.auto-extract-section {
  margin-bottom: 20px;
}

.disk-form-actions {
  margin-top: 10px;
  text-align: right;
}
</style>
