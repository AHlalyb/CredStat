<template>
  <div class="info-query-view">
    <el-card class="mt-5" shadow="hover">
      <template #header>
        <div class="card-header">
          <h3 class="m-0">信息查询</h3>
        </div>
      </template>
      
      <div class="el-card__body">
        <!-- 查询表单 -->
        <el-form ref="searchForm" :model="searchForm" label-position="top" label-width="100px" size="default" class="mb-4">
          <el-row :gutter="[20, 20]">
            <el-col :xs="24" :sm="12" :md="6">
              <el-form-item label="关键词1">
                <div class="keyword-input-wrapper">
                  <el-input
                    v-model="searchForm.keyword1"
                    placeholder="请输入第一个关键词，支持模糊匹配"
                    clearable
                    class="keyword-input"
                  ></el-input>
                  <el-select
                    v-model="searchForm.keyword1MatchType"
                    placeholder="匹配方式"
                    class="match-type-select"
                    style="width: 120px;"
                  >
                    <el-option label="包含" value="include"></el-option>
                    <el-option label="不包含" value="exclude"></el-option>
                  </el-select>
                </div>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6">
              <el-form-item label="关键词2">
                <div class="keyword-input-wrapper">
                  <el-input
                    v-model="searchForm.keyword2"
                    placeholder="请输入第二个关键词，支持模糊匹配"
                    clearable
                    class="keyword-input"
                  ></el-input>
                  <el-select
                    v-model="searchForm.keyword2MatchType"
                    placeholder="匹配方式"
                    class="match-type-select"
                    style="width: 120px;"
                  >
                    <el-option label="包含" value="include"></el-option>
                    <el-option label="不包含" value="exclude"></el-option>
                  </el-select>
                </div>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6">
              <el-form-item label="查询类别" required>
                <el-select
                  v-model="searchForm.queryType"
                  placeholder="请选择查询类别"
                  style="width: 100%"
                >
                  <el-option label="信息系统登录信息" value="system"></el-option>
                  <el-option label="服务器账号密码" value="server"></el-option>
                  <el-option label="网络设备登录信息" value="network"></el-option>
                  <el-option label="宿主机集群信息" value="cluster"></el-option>
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="3">
              <el-form-item>
                <el-button type="primary" @click="performSearch" class="w-100" size="small">
                  <el-icon><Search /></el-icon> 查询
                </el-button>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="3">
              <el-form-item>
                <el-button @click="resetSearch" class="w-100" size="small">
                  <el-icon><RefreshRight /></el-icon> 重置
                </el-button>
              </el-form-item>
            </el-col>
          </el-row>
        </el-form>
        
        <!-- 查询结果 -->
        <div class="search-results mt-4">
          <el-row :gutter="[20, 20]" class="mb-3">
            <el-col :xs="24" :sm="18">
              <el-alert
                :title="searchMessage"
                :type="messageType"
                show-icon
                :closable="false"
              ></el-alert>
            </el-col>
            <el-col :xs="24" :sm="6" class="text-right">
              <!-- 导出按钮 -->
              <el-button
                type="success"
                :disabled="!canExport"
                size="small"
                @click="openExportDialog"
              >
                <el-icon><Download /></el-icon> 导出结果
              </el-button>
            </el-col>
          </el-row>
          
          <!-- 结果表格 -->
          <el-table
            v-if="searchData.length > 0"
            :data="searchData"
            border
            style="width: 100%"
            stripe
            :header-cell-style="{background: '#f5f7fa', color: '#606266', fontWeight: 'bold'}"
          >
            <el-table-column label="序号" width="70" align="center">
              <template #default="scope">
                <div class="index-cell">
                  {{ scope.$index + 1 }}
                  <!-- 状态图标 -->
                  <div v-if="searchForm.queryType === 'system' || searchForm.queryType === 'server'" class="status-icons">
                    <!-- 信息系统登录信息状态图标 -->
                    <template v-if="searchForm.queryType === 'system'">
                      <el-tooltip v-if="!isSystemRecordComplete(scope.row)" content="录入信息不完整" placement="top">
                        <span class="status-icon incomplete"></span>
                      </el-tooltip>
                      <el-tooltip v-if="!isSystemRecordValid(scope.row)" content="数据标注为无效" placement="top">
                        <span class="status-icon invalid"></span>
                      </el-tooltip>
                    </template>
                    <!-- 服务器账号密码状态图标 -->
                    <template v-else-if="searchForm.queryType === 'server'">
                      <el-tooltip v-if="!isServerRecordComplete(scope.row)" content="录入信息不完整" placement="top">
                        <span class="status-icon incomplete"></span>
                      </el-tooltip>
                      <el-tooltip v-if="!isServerRecordValid(scope.row)" content="数据标注为无效" placement="top">
                        <span class="status-icon invalid"></span>
                      </el-tooltip>
                    </template>
                  </div>
                </div>
              </template>
            </el-table-column>
            
            <!-- 动态列 -->
            <el-table-column
              v-for="column in columns"
              :key="column.prop"
              :label="column.label"
              :prop="column.prop"
              :min-width="column.minWidth"
              align="center"
            >
              <!-- 集群宿主机数量列的自定义模板 -->
              <template #default="scope" v-if="column.prop === 'pm_count' && searchForm.queryType === 'cluster'">
                <el-link
                  type="primary"
                  :underline="true"
                  @click="showClusterPhysicalMachines(scope.row.cluster_id, scope.row.cluster_name)"
                  :disabled="scope.row.pm_count === 0"
                >
                  {{ scope.row.pm_count }}
                </el-link>
              </template>
              <!-- 网络设备中文名称(系统名称)列的自定义模板 -->
              <template #default="scope" v-else-if="column.prop === 'name' && searchForm.queryType === 'network'">
                <div class="network-name-cell">
                  <div class="chinese-name">{{ scope.row.name || '无' }}</div>
                  <div class="system-name">({{ scope.row.system_name || '无' }})</div>
                </div>
              </template>
              <!-- 网络设备管理IP:端口(协议)列的自定义模板 -->
              <template #default="scope" v-else-if="column.prop === 'ip_port_protocol' && searchForm.queryType === 'network'">
                <div class="ip-port-protocol-cell">
                  <div class="ip-port">{{ scope.row.ip_port || '无' }}</div>
                  <div class="protocol">({{ scope.row.protocol || '无' }})</div>
                </div>
              </template>
            </el-table-column>
            
            <!-- 操作列 -->
            <el-table-column label="操作" width="115" align="center" fixed="right">
              <template #default="scope">
                <div class="action-buttons">
                  <!-- 编辑按钮 -->
                  <el-tooltip :content="hasPermission('edit') ? '编辑' : '您没有修改权限'" placement="top">
                    <el-button
                      type="primary"
                      circle
                      size="small"
                      @click="editRecord(scope.row)"
                      :disabled="!hasPermission('edit')"
                      :class="{ 'permission-disabled': !hasPermission('edit') }"
                    >
                      <el-icon><Edit /></el-icon>
                    </el-button>
                  </el-tooltip>
                  <!-- 删除按钮 -->
                  <el-tooltip :content="hasPermission('delete') ? '删除' : '您没有删除权限'" placement="top">
                    <el-button
                      type="danger"
                      circle
                      size="small"
                      @click="deleteRecord(scope.row)"
                      :disabled="!hasPermission('delete')"
                      :class="{ 'permission-disabled': !hasPermission('delete') }"
                    >
                      <el-icon><Delete /></el-icon>
                    </el-button>
                  </el-tooltip>
                  <!-- 查询详情按钮 -->
                  <el-tooltip :content="hasPermission('query') ? '查询详情' : '您没有查询权限'" placement="top">
                    <el-button
                      type="primary"
                      circle
                      size="small"
                      @click="handleDetailQuery(scope.row)"
                      :disabled="!hasPermission('query')"
                      :class="{ 'permission-disabled': !hasPermission('query') }"
                    >
                      <el-icon><Search /></el-icon>
                    </el-button>
                  </el-tooltip>
                </div>
              </template>
            </el-table-column>
          </el-table>
          
          <!-- 无数据提示 -->
          <div v-else class="no-data">
            <el-empty description="未找到匹配的记录"></el-empty>
          </div>
          
          <!-- 分页控件 -->
          <div v-if="total > 0" class="mt-4 text-center">
            <el-pagination
              @size-change="handleSizeChange"
              @current-change="handleCurrentChange"
              :current-page="currentPage"
              :page-sizes="[10, 20, 50, 100]"
              :page-size="pageSize"
              layout="total, sizes, prev, pager, next, jumper"
              :total="total"
            ></el-pagination>
          </div>
        </div>
      </div>
    </el-card>
    
    <!-- 导出进度对话框 -->
    <el-dialog
      v-model="exportProgressVisible"
      title="导出进度"
      width="30%"
      :close-on-click-modal="false"
      :show-close="false"
    >
      <div class="text-center">
        <el-icon class="is-loading" style="font-size: 40px; margin-bottom: 20px;"><Loading /></el-icon>
        <p>{{ exportProgressText }}</p>
        <el-progress
          :percentage="exportProgress"
          :status="exportProgressStatus"
          :color="exportProgressColor"
          class="mt-3"
        ></el-progress>
      </div>
      <template #footer>
        <div class="dialog-footer text-center">
          <el-button type="primary" @click="exportProgressVisible = false" :disabled="exportProgress < 100">
            关闭
          </el-button>
        </div>
      </template>
    </el-dialog>
    
    <!-- 修改记录对话框 -->
    <el-dialog
      v-model="editRecordVisible"
      :title="editDialogTitle"
      width="90%"
      :fullscreen="false"
    >
      <el-form
        ref="editFormRef"
        :model="editFormData"
        :rules="editFormRules"
        label-position="top"
        label-width="100px"
        size="default"
        autocomplete="off"
      >
        <!-- 隐藏的诱饵字段，用于防止浏览器自动填充 -->
        <div class="autofill-bait" style="position: absolute; left: -9999px; top: -9999px;">
          <input type="text" name="username" autocomplete="off" />
          <input type="password" name="password" autocomplete="off" />
        </div>
        
        <!-- 信息系统登录信息表单 -->
        <div v-if="currentRecord && currentRecord.category === 'system'" class="mb-4">
          <h6 class="text-bold mb-3">信息系统登录信息</h6>
          <el-row :gutter="[20, 20]">
            <el-col :xs="24" :sm="12" :md="8" :lg="8">
              <el-form-item label="系统名称" prop="systemName" required>
                <el-input
                  v-model="editFormData.systemName"
                  placeholder="请输入系统名称"
                ></el-input>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="8" :lg="8">
              <el-form-item label="IP/URL" prop="ipUrl" required>
                <el-input
                  v-model="editFormData.ipUrl"
                  placeholder="请输入IP地址或URL"
                ></el-input>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="8" :lg="8">
              <el-form-item label="登录方式" prop="type" required>
                <el-select
                  v-model="editFormData.type"
                  placeholder="请选择或输入登录方式"
                  filterable
                  allow-create
                  default-first-option
                  style="width: 100%"
                >
                  <el-option label="web" value="web"></el-option>
                  <el-option label="telnet" value="telnet"></el-option>
                  <el-option label="ssh" value="ssh"></el-option>
                  <el-option label="rdp" value="rdp"></el-option>
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="8" :lg="8">
              <el-form-item label="账号" prop="username" required>
                <el-input
                  v-model="editFormData.username"
                  placeholder="请输入账号"
                  autocomplete="new-username"
                  :name="'random-username-' + Math.random().toString(36).substring(2, 15)"
                  readonly
                  @focus="$event.target.removeAttribute('readonly')"
                  :key="clusterUsernameKey"
                ></el-input>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="8" :lg="8">
              <el-form-item label="密码" prop="password" required>
                <el-input
                  v-model="editFormData.password"
                  type="password"
                  placeholder="请输入密码"
                  show-password
                  autocomplete="new-password"
                  :name="'random-password-' + Math.random().toString(36).substring(2, 15)"
                  readonly
                  @focus="$event.target.removeAttribute('readonly')"
                  :key="clusterPasswordKey"
                ></el-input>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="8" :lg="8">
              <el-form-item label="是否有效" prop="isActive">
                <el-select
                  v-model="editFormData.isActive"
                  placeholder="请选择是否有效"
                  style="width: 100%"
                >
                  <el-option label="有效" value="1"></el-option>
                  <el-option label="无效" value="0"></el-option>
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="24" :md="24" :lg="24">
              <el-form-item label="备注" prop="remark">
                <el-input
                  v-model="editFormData.remark"
                  type="textarea"
                  :rows="3"
                  placeholder="请输入备注信息（可选）"
                ></el-input>
              </el-form-item>
            </el-col>
          </el-row>
        </div>
        
        <!-- 服务器账号密码表单 -->
        <div v-else-if="currentRecord && currentRecord.category === 'server'" class="mb-4">
          <h6 class="text-bold mb-3">服务器账号密码</h6>
          <!-- 第一行：服务器所属网络区域、服务器类型、宿主机集群 -->
          <el-row :gutter="[20, 20]">
            <el-col :xs="24" :sm="12" :md="8" :lg="8">
              <el-form-item label="服务器所属网络区域" prop="server_cred_network_area">
                <el-select
                  v-model="editFormData.server_cred_network_area"
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
                  v-model="editFormData.server_cred_server_type"
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
                  v-model="editFormData.server_cred_host_cluster"
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
              <el-form-item label="服务器名称" prop="server_cred_server_name" required>
                <el-input
                  v-model="editFormData.server_cred_server_name"
                  placeholder="请输入服务器名称"
                ></el-input>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="12" :lg="12">
              <el-form-item label="服务器IP" prop="server_cred_server_ip" required>
                <el-input
                  v-model="editFormData.server_cred_server_ip"
                  placeholder="请输入服务器IP地址"
                ></el-input>
              </el-form-item>
            </el-col>
          </el-row>
          
          <!-- 将操作系统类型、端口号、登录用户名和密码放在同一行 -->
          <el-row :gutter="[20, 20]">
            <el-col :xs="24" :sm="12" :md="6" :lg="6">
              <el-form-item label="操作系统类型" prop="server_cred_server_os" required>
                <el-select
                  v-model="editFormData.server_cred_server_os"
                  placeholder="请选择操作系统"
                  style="width: 100%"
                  @change="handleOSTypeChange"
                  filterable
                  default-first-option
                  clearable
                >
                  <el-option label="Windows" value="Windows"></el-option>
                  <el-option label="Linux" value="Linux"></el-option>
                  <el-option label="Unix" value="Unix"></el-option>
                  <el-option label="其他" value="其他"></el-option>
                </el-select>
              </el-form-item>
            </el-col>
            
            <el-col :xs="24" :sm="12" :md="6" :lg="6">
              <el-form-item label="端口号" prop="server_cred_server_port" required>
                <el-input-number
                  v-model="editFormData.server_cred_server_port"
                  placeholder="请输入端口号"
                  style="width: 100%"
                ></el-input-number>
              </el-form-item>
            </el-col>
            
            <el-col :xs="24" :sm="12" :md="6" :lg="6">
              <el-form-item label="登录用户名" prop="server_cred_login_username" required>
                <el-input
                  v-model="editFormData.server_cred_login_username"
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
              <el-form-item label="密码" prop="server_cred_login_password" required>
                <el-input
                  v-model="editFormData.server_cred_login_password"
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
                  v-model="editFormData.server_cred_edr_installed"
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
                  v-model="editFormData.server_cred_ntp_configured"
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
                  v-model="editFormData.server_cred_notes"
                  type="textarea"
                  :rows="3"
                  placeholder="请输入备注信息"
                ></el-input>
              </el-form-item>
            </el-col>
          </el-row>
        </div>
        
        <!-- 网络设备登录信息表单 -->
        <div v-else-if="currentRecord && currentRecord.category === 'network'" class="mb-4">
          <!-- 加载状态 -->
          <el-skeleton :loading="saveEditLoading" animated :rows="10" style="margin-bottom: 20px;">
            <!-- 第一行：网络设备类型、设备所属网络、设备所属物理区域、设备所属楼宇-楼层、设备所在楼层位置 -->
            <el-row :gutter="[20, 20]">
            <el-col :xs="24" :sm="12" :md="6" :lg="4">
              <el-form-item label="网络设备类型" prop="dev_type" required>
                <el-select
                  v-model="editFormData.dev_type"
                  placeholder="请选择或输入设备类型"
                  filterable
                  allow-create
                  default-first-option
                  style="width: 100%"
                >
                  <!-- 设备类型选项 -->
                  <el-option label="交换机" value="交换机"></el-option>
                  <el-option label="路由器" value="路由器"></el-option>
                  <el-option label="防火墙" value="防火墙"></el-option>
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6" :lg="4">
              <el-form-item label="设备所属网络" prop="net_type" required>
                <el-select
                  v-model="editFormData.net_type"
                  placeholder="请选择所属网络"
                  style="width: 100%"
                >
                  <el-option label="内网" value="内网"></el-option>
                  <el-option label="外网" value="外网"></el-option>
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6" :lg="4">
              <el-form-item label="设备所属物理区域" prop="physical_area" required>
                <el-input
                  v-model="editFormData.physical_area"
                  placeholder="请输入物理区域"
                ></el-input>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6" :lg="6">
              <el-form-item label="设备所属楼宇-楼层" prop="building_floor" required>
                <el-input
                  v-model="editFormData.building_floor"
                  placeholder="示例：门诊楼-3楼"
                ></el-input>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6" :lg="6">
              <el-form-item label="设备所在楼层位置" prop="floor_location" required>
                <el-input
                  v-model="editFormData.floor_location"
                  placeholder="示例：弱电井"
                ></el-input>
              </el-form-item>
            </el-col>
          </el-row>
          
          <!-- 第二行：中文命名、系统命名、设备品牌、设备型号 -->
          <el-row :gutter="[20, 20]">
            <el-col :xs="24" :sm="12" :md="6" :lg="6">
              <el-form-item label="中文命名" prop="name" required>
                <el-input
                  v-model="editFormData.name"
                  placeholder="请输入设备中文命名"
                ></el-input>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6" :lg="6">
              <el-form-item label="系统命名" prop="system_name" required>
                <el-input
                  v-model="editFormData.system_name"
                  placeholder="请输入设备系统命名"
                ></el-input>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6" :lg="6">
              <el-form-item label="设备品牌" prop="dev_brand" required>
                <el-select
                  v-model="editFormData.dev_brand"
                  placeholder="请选择或输入设备品牌"
                  filterable
                  allow-create
                  default-first-option
                  style="width: 100%"
                >
                  <!-- 设备品牌选项 -->
                  <el-option label="华为" value="华为"></el-option>
                  <el-option label="H3C" value="H3C"></el-option>
                  <el-option label="思科" value="思科"></el-option>
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6" :lg="6">
              <el-form-item label="设备型号" prop="dev_model" required>
                <el-select
                  v-model="editFormData.dev_model"
                  placeholder="请选择或输入设备型号"
                  filterable
                  allow-create
                  default-first-option
                  style="width: 100%"
                >
                  <!-- 设备型号选项 -->
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>
          
          <!-- 管理信息 -->
          <el-row :gutter="[20, 20]">
            <el-col :xs="24" :sm="12" :md="8" :lg="8">
              <el-form-item label="管理IP" prop="management_ip" required>
                <el-input
                  v-model="editFormData.management_ip"
                  placeholder="请输入管理IP地址"
                ></el-input>
              </el-form-item>
            </el-col>
            
            <el-col :xs="24" :sm="12" :md="8" :lg="8">
              <el-form-item label="管理协议" prop="protocol" required>
                <el-select
                  v-model="editFormData.protocol"
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
              <el-form-item label="端口" prop="port" required>
                <el-input-number
                  v-model="editFormData.port"
                  :min="1"
                  :max="65535"
                  placeholder="请输入端口号"
                  style="width: 100%"
                ></el-input-number>
              </el-form-item>
            </el-col>
          </el-row>
          
          <!-- 第四行：用户名、密码、使能密码 -->
          <el-row :gutter="[20, 20]">
            <el-col :xs="24" :sm="12" :md="8" :lg="8">
              <el-form-item label="用户名" prop="username" required>
                <el-input
                  v-model="editFormData.username"
                  placeholder="请输入用户名"
                  autocomplete="new-username"
                  :name="'random-username-' + Math.random().toString(36).substring(2, 15)"
                  readonly
                  @focus="$event.target.removeAttribute('readonly')"
                  :key="clusterUsernameKey"
                ></el-input>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="8" :lg="8">
              <el-form-item label="密码" prop="password" required>
                <el-input
                  v-model="editFormData.password"
                  type="password"
                  placeholder="请输入密码"
                  show-password
                  autocomplete="new-password"
                  :name="'random-password-' + Math.random().toString(36).substring(2, 15)"
                  readonly
                  @focus="$event.target.removeAttribute('readonly')"
                  :key="clusterPasswordKey"
                ></el-input>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="8" :lg="8">
              <el-form-item label="使能密码" prop="enable_password">
                <el-input
                  v-model="editFormData.enable_password"
                  type="password"
                  placeholder="请输入使能密码"
                  show-password
                  autocomplete="new-password"
                  :name="'random-enable-password-' + Math.random().toString(36).substring(2, 15)"
                  readonly
                  @focus="$event.target.removeAttribute('readonly')"
                ></el-input>
              </el-form-item>
            </el-col>
          </el-row>
          
          <!-- 第五行：SNMP团体字 -->
          <el-row :gutter="[20, 20]">
            <el-col :xs="24" :sm="12" :md="8" :lg="8">
              <el-form-item label="SNMP团体字" prop="snmp_community">
                <el-input
                  v-model="editFormData.snmp_community"
                  type="password"
                  placeholder="请输入SNMP团体字"
                  show-password
                  autocomplete="new-snmp-community"
                  :name="'random-snmp-community-' + Math.random().toString(36).substring(2, 15)"
                  readonly
                  @focus="$event.target.removeAttribute('readonly')"
                ></el-input>
              </el-form-item>
            </el-col>
          </el-row>
          
          <!-- 第六行：备注 -->
          <el-row :gutter="[20, 20]">
            <el-col :xs="24" :sm="24" :md="24" :lg="24">
              <el-form-item label="备注" prop="remark">
                <el-input
                  v-model="editFormData.remark"
                  type="textarea"
                  :rows="3"
                  placeholder="请输入备注信息（可选）"
                ></el-input>
              </el-form-item>
            </el-col>
          </el-row>
          </el-skeleton>
        </div>
        
        <!-- 宿主机集群信息表单 -->
        <div v-else-if="currentRecord && currentRecord.category === 'cluster'" class="mb-4">
          <!-- 集群基础信息 -->
          <div class="mb-4">
            <h6 class="text-bold mb-3">集群基础信息</h6>
            <el-row :gutter="[20, 20]">
              <el-col :xs="24" :sm="12" :md="6">
                <el-form-item label="集群名称" prop="clusterName" required>
                  <el-input
                    v-model="editFormData.clusterName"
                    placeholder="请输入集群名称"
                  ></el-input>
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12" :md="6">
                <el-form-item label="集群地址" prop="clusterAddress" required>
                  <el-input
                    v-model="editFormData.clusterAddress"
                    placeholder="请输入集群地址"
                  ></el-input>
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12" :md="6">
                <el-form-item label="集群用户名" prop="clusterUsername" required>
                  <el-input
                    v-model="editFormData.clusterUsername"
                    placeholder="请输入集群用户名"
                    autocomplete="new-username"
                    :name="'random-username-' + Math.random().toString(36).substring(2, 15)"
                    readonly
                    @focus="$event.target.removeAttribute('readonly')"
                    :key="clusterUsernameKey"
                  ></el-input>
                </el-form-item>
              </el-col>
              <el-col :xs="24" :sm="12" :md="6">
                <el-form-item label="集群密码" prop="clusterPassword" required>
                  <el-input
                    v-model="editFormData.clusterPassword"
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
              <el-button type="primary" @click="addPhysicalMachine" :icon="Plus" size="small" :disabled="!hasPermission('edit')">
                添加物理机
              </el-button>
            </div>
            
            <!-- 物理机信息表格 -->
            <div class="physical-machines-table">
              <el-table
                :data="editFormData.physicalMachines"
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
                      :rules="editFormRules['physicalMachines.*.pmName']"
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
                      :rules="editFormRules['physicalMachines.*.pmIp']"
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
                      :rules="editFormRules['physicalMachines.*.pmUsername']"
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
                      :rules="editFormRules['physicalMachines.*.pmPassword']"
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
                      :disabled="!hasPermission('edit')"
                    ></el-button>
                  </template>
                </el-table-column>
              </el-table>
            </div>
          </div>
        </div>
        
        <!-- 表单操作按钮 -->
        <el-form-item>
          <el-button
          type="primary"
          @click="handleSaveEdit"
          :icon="Check"
          :loading="saveEditLoading"
          :disabled="!hasPermission('edit')"
        >保存修改</el-button>
        </el-form-item>
      </el-form>
    </el-dialog>
    
    <!-- 删除确认对话框 -->
    <el-dialog
      v-model="deleteConfirmVisible"
      title="确认删除"
      width="400px"
    >
      <div class="delete-confirm-content">
        <p>您确定要删除这条记录吗？</p>
        <p class="text-danger mt-2">警告：此操作不可逆，删除后数据将无法恢复！</p>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="deleteConfirmVisible = false">取消</el-button>
          <el-button type="danger" @click="confirmDelete">
            <el-icon><Delete /></el-icon> 确认删除
          </el-button>
        </div>
      </template>
    </el-dialog>
    
    <!-- 详细信息对话框 -->
    <el-dialog
      v-model="detailDialogVisible"
      title="记录详情"
      width="70%"
      :close-on-click-modal="false"
    >
      <div class="detail-dialog-content">
        <div v-if="currentRecord" class="detail-info">
          <!-- 信息系统登录信息详情 -->
          <div v-if="currentRecord.category === 'system'" class="system-detail">
            <el-descriptions
              border
              :column="2"
              :size="'medium'"
              class="detail-descriptions"
            >
              <!-- 第一行 -->
              <el-descriptions-item label="ID">
                <span>{{ currentRecord.id || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="系统名称">
                <span>{{ currentRecord.systemName || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第二行 -->
              <el-descriptions-item label="IP或URL地址">
                <span>{{ currentRecord.ipUrl || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="登录方式">
                <span>{{ currentRecord.loginType || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第三行 -->
              <el-descriptions-item label="账号">
                <span>{{ currentRecord.username || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="密码">
                <span>{{ currentRecord.password || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第四行 -->
              <el-descriptions-item label="备注信息">
                <span>{{ currentRecord.remark || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="创建时间">
                <span>{{ this.formatDateTime(currentRecord.created_at) || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第五行 -->
              <el-descriptions-item label="更新时间">
                <span>{{ this.formatDateTime(currentRecord.updated_at) || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="创建人">
                <span>{{ currentRecord.createdBy || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第六行 -->
              <el-descriptions-item label="是否有效">
                <span>{{ Number(currentRecord.isActive) === 1 ? '有效' : '无效' }}</span>
              </el-descriptions-item>
            </el-descriptions>
          </div>
          <!-- 服务器账号密码详情 -->
          <div v-else-if="currentRecord.category === 'server'" class="server-detail">
            <el-descriptions
              border
              :column="2"
              :size="'medium'"
              class="detail-descriptions"
            >
              <!-- 第一行 -->
              <el-descriptions-item label="服务器所属网络区域">
                <span>{{ currentRecord.server_cred_network_area || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="服务器类型">
                <span>{{ currentRecord.server_cred_server_type || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第二行 -->
              <el-descriptions-item label="宿主机集群">
                <span>{{ currentRecord.server_cred_host_cluster || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="服务器名称">
                <span>{{ currentRecord.server_cred_server_name || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第三行 -->
              <el-descriptions-item label="服务器IP地址">
                <span>{{ currentRecord.server_cred_server_ip || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="操作系统类型">
                <span>{{ currentRecord.server_cred_server_os || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第四行 -->
              <el-descriptions-item label="服务器端口号">
                <span>{{ currentRecord.server_cred_server_port || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="用户名">
                <span>{{ currentRecord.server_cred_login_username || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第五行 -->
              <el-descriptions-item label="密码">
                <span>{{ currentRecord.server_cred_login_password || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="EDR安装">
                <span>{{ currentRecord.server_cred_edr_installed || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第六行 -->
              <el-descriptions-item label="NTP配置">
                <span>{{ currentRecord.server_cred_ntp_configured || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="是否有效">
                <span>{{ Number(currentRecord.is_active) === 1 ? '有效' : (Number(currentRecord.is_active) === 2 ? '无效' : '无') }}</span>
              </el-descriptions-item>
              
              <!-- 第七行 -->
              <el-descriptions-item label="备注信息">
                <span>{{ currentRecord.server_cred_notes || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第八行 -->
              <el-descriptions-item label="创建人">
                <span>{{ currentRecord.server_cred_created_by || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="创建时间">
                <span>{{ this.formatDateTime(currentRecord.created_at) || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第九行 -->
              <el-descriptions-item label="更新时间">
                <span>{{ this.formatDateTime(currentRecord.updated_at) || '无' }}</span>
              </el-descriptions-item>
            </el-descriptions>
            
            <!-- 磁盘信息可展开区域 -->
            <div class="disk-info-section mt-4">
              <el-collapse>
                <el-collapse-item title="磁盘信息">
                  <div v-if="currentRecord.disks && currentRecord.disks.length > 0">
                    <!-- Windows磁盘信息 -->
                    <el-table v-if="currentRecord.server_cred_server_os && currentRecord.server_cred_server_os.toLowerCase().includes('windows')" :data="currentRecord.disks" border style="width: 100%">
                      <el-table-column prop="driveLetter" label="盘符号" min-width="80" align="center"></el-table-column>
                      <el-table-column prop="capacity" label="容量" min-width="100" align="center"></el-table-column>
                      <el-table-column prop="usedSpace" label="已使用空间" min-width="120" align="center"></el-table-column>
                      <el-table-column prop="notes" label="磁盘信息备注" min-width="200" align="left"></el-table-column>
                    </el-table>
                    <!-- Linux磁盘信息 -->
                    <el-table v-else :data="currentRecord.disks" border style="width: 100%">
                      <el-table-column prop="deviceName" label="设备名称" min-width="120" align="center"></el-table-column>
                      <el-table-column prop="fileSystemType" label="文件系统类型" min-width="120" align="center"></el-table-column>
                      <el-table-column prop="capacity" label="容量" min-width="100" align="center"></el-table-column>
                      <el-table-column prop="usedSpace" label="已使用空间" min-width="120" align="center"></el-table-column>
                      <el-table-column prop="mountPoint" label="挂载点" min-width="150" align="center"></el-table-column>
                      <el-table-column prop="notes" label="磁盘信息备注" min-width="200" align="left"></el-table-column>
                    </el-table>
                  </div>
                  <div v-else class="no-disk-data">
                    <el-empty description="未找到磁盘信息"></el-empty>
                  </div>
                </el-collapse-item>
              </el-collapse>
            </div>
          </div>
          <!-- 网络设备登录信息详情 -->
          <div v-else-if="currentRecord.category === 'network'" class="network-detail">
            <el-descriptions
              border
              :column="2"
              :size="'medium'"
              class="detail-descriptions"
            >
              <!-- 第一列：1-10项 -->
              <!-- 第一行 -->
              <el-descriptions-item label="网络设备类型">
                <span>{{ currentRecord.net_dev_cred_dev_type || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="网络设备所属网络">
                <span>{{ currentRecord.net_dev_cred_net_type || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第二行 -->
              <el-descriptions-item label="网络设备所属物理区域">
                <span>{{ currentRecord.net_dev_cred_physical_area || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="网络设备所属楼宇-楼层">
                <span>{{ currentRecord.net_dev_cred_building_floor || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第三行 -->
              <el-descriptions-item label="网络设备所在楼层位置">
                <span>{{ currentRecord.net_dev_cred_floor_location || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="中文命名">
                <span>{{ currentRecord.net_dev_cred_chinese_name || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第四行 -->
              <el-descriptions-item label="系统命名">
                <span>{{ currentRecord.net_dev_cred_system_name || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="设备品牌">
                <span>{{ currentRecord.net_dev_cred_dev_brand || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第五行 -->
              <el-descriptions-item label="设备型号">
                <span>{{ currentRecord.net_dev_cred_dev_sign || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="管理IP">
                <span>{{ currentRecord.net_dev_cred_management_ip || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第二列：11-20项 -->
              <!-- 第六行 -->
              <el-descriptions-item label="管理协议">
                <span>{{ currentRecord.net_dev_cred_protocol || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="端口">
                <span>{{ currentRecord.net_dev_cred_port || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第七行 -->
              <el-descriptions-item label="用户名">
                <span>{{ currentRecord.net_dev_cred_username || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="密码">
                <span>{{ currentRecord.net_dev_cred_password_hash || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第八行 -->
              <el-descriptions-item label="特权密码">
                <span>{{ currentRecord.net_dev_cred_enable_password_hash || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="SNMP团体字">
                <span>{{ currentRecord.net_dev_cred_snmp || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第九行 -->
              <el-descriptions-item label="创建人">
                <span>{{ currentRecord.net_dev_cred_created_by || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="创建日期">
                <span>{{ this.formatDateTime(currentRecord.created_at) || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第十行 -->
              <el-descriptions-item label="更新日期">
                <span>{{ this.formatDateTime(currentRecord.updated_at) || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="备注">
                <span>{{ currentRecord.net_dev_cred_description || '无' }}</span>
              </el-descriptions-item>
            </el-descriptions>
          </div>
          <!-- 其他类型记录详情 -->
          <div v-else>
            <el-descriptions
              border
              :column="2"
              :size="'medium'"
              class="detail-descriptions"
            >
              <el-descriptions-item
                v-for="(value, key) in getDisplayFields()"
                :key="key"
                :label="getFieldName(key)"
              >
                <div class="field-value">
                  <span v-if="value === '' || value === null || value === undefined">无</span>
                  <span v-else-if="typeof value === 'object'">
                    {{ JSON.stringify(value) }}
                  </span>
                  <span v-else class="value-text" :title="value.length > 50 ? value : ''">
                    {{ value.length > 50 ? value.substring(0, 50) + '...' : value }}
                  </span>
                </div>
              </el-descriptions-item>
            </el-descriptions>
          </div>
        </div>
        <div v-else class="no-data">
          <el-empty description="未找到记录信息"></el-empty>
        </div>
      </div>
      <template #footer>
        <div class="dialog-footer text-center">
          <el-button type="primary" @click="detailDialogVisible = false">
            <el-icon><Close /></el-icon> 关闭
          </el-button>
        </div>
      </template>
    </el-dialog>
    
    <!-- 集群物理机详情对话框 -->
    <el-dialog
      v-model="physicalMachineDialogVisible"
      :title="`集群：${currentClusterName} - 宿主机详情`"
      width="80%"
      :close-on-click-modal="false"
    >
      <div class="physical-machine-detail">
        <el-alert
          v-if="physicalMachineLoading"
          type="info"
          show-icon
          class="mb-4"
        >
          <el-icon class="is-loading"><Loading /></el-icon>
          <span>正在加载宿主机信息...</span>
        </el-alert>
        
        <el-table
          v-if="!physicalMachineLoading && physicalMachineList.length > 0"
          :data="physicalMachineList"
          border
          style="width: 100%"
          stripe
          :header-cell-style="{background: '#f5f7fa', color: '#606266', fontWeight: 'bold'}"
        >
          <el-table-column type="index" label="序号" width="80" align="center"></el-table-column>
          
          <el-table-column prop="cluster_pm_name" label="宿主机名称" min-width="120" align="center"></el-table-column>
          
          <el-table-column
            v-for="column in physicalMachineColumns"
            :key="column.prop"
            :label="column.label"
            :prop="column.prop"
            :min-width="column.minWidth"
            align="center"
          ></el-table-column>
        </el-table>
        
        <div v-else-if="!physicalMachineLoading && physicalMachineList.length === 0" class="no-data">
          <el-empty description="该集群暂无关联的宿主机"></el-empty>
        </div>
        
        <!-- 分页控件 -->
        <div v-if="!physicalMachineLoading && physicalMachineList.length > 0" class="mt-4 text-center">
          <el-pagination
            layout="total, prev, pager, next, jumper"
            :total="physicalMachineList.length"
            :page-size="10"
            :current-page.sync="physicalMachineCurrentPage"
            @current-change="handlePhysicalMachinePageChange"
          ></el-pagination>
        </div>
      </div>
      
      <template #footer>
        <div class="dialog-footer text-center">
          <el-button type="primary" @click="physicalMachineDialogVisible = false">
            <el-icon><Close /></el-icon> 关闭
          </el-button>
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
      <!-- 操作系统类型选择器 -->
      <div class="os-type-selector mb-4">
        <el-form-item label="操作系统类型">
          <el-select
            v-model="dialogOsType"
            placeholder="请选择操作系统类型"
            style="width: 100%"
            @change="handleDialogOsTypeChange"
          >
            <el-option label="Windows" value="Windows"></el-option>
            <el-option label="Linux" value="Linux"></el-option>
          </el-select>
        </el-form-item>
      </div>
      
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
    
    <!-- 导出选择对话框 -->
    <el-dialog
      v-model="exportSelectVisible"
      title="导出结果设置"
      width="600px"
      :close-on-click-modal="false"
    >
      <div class="export-select-dialog">
        <!-- 列选择 -->
        <div class="mb-4">
          <div class="flex justify-between items-center mb-2">
            <h4 class="text-bold m-0">导出列选择</h4>
            <el-button type="text" @click="toggleSelectAll">
              {{ isAllSelected ? '取消全选' : '全选' }}
            </el-button>
          </div>
          <el-checkbox-group v-model="selectedColumns" class="column-select-group">
            <el-checkbox
              v-for="column in exportColumns"
              :key="column.value"
              :label="column.value"
              class="column-checkbox"
            >
              {{ column.label }}
            </el-checkbox>
          </el-checkbox-group>
        </div>
        
        <!-- 高级选项 -->
        <div class="mb-4" v-if="searchForm.queryType === 'server'">
          <h4 class="text-bold m-0 mb-2">高级信息选项</h4>
          <el-checkbox v-model="exportDiskInfo">磁盘信息</el-checkbox>
          <p class="text-gray-500 text-sm mt-1">选中后将导出服务器磁盘信息，根据操作系统类型区分显示</p>
        </div>
        
        <!-- 文件类型选择 -->
        <div class="mb-4">
          <h4 class="text-bold m-0 mb-2">导出文件类型为：</h4>
          <div class="file-type-select">
            <div
              v-for="type in exportFileTypes"
              :key="type.value"
              class="file-type-item"
              :class="{ 'file-type-selected': selectedFileType === type.value }"
              @click="selectedFileType = type.value"
            >
              <img :src="type.icon" :alt="type.label" class="file-type-icon" />
              <span class="file-type-label">{{ type.label }}</span>
            </div>
          </div>
        </div>
      </div>
      
      <template #footer>
        <div class="dialog-footer text-center">
          <el-button @click="exportSelectVisible = false">取消</el-button>
          <el-button type="primary" @click="confirmExport">确认导出</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
/* 网络设备名称单元格样式 */
.network-name-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60px; /* 确保行高足够容纳两行文本 */
  line-height: 1.4;
}

.chinese-name {
  font-weight: bold;
  margin-bottom: 4px;
  word-break: break-word;
  text-align: center;
}

.system-name {
  font-size: 0.9em;
  color: #606266;
  word-break: break-word;
  text-align: center;
}

/* 网络设备管理IP:端口(协议)单元格样式 */
.ip-port-protocol-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60px; /* 确保行高足够容纳两行文本 */
  line-height: 1.4;
}

.ip-port {
  word-break: break-word;
  text-align: center;
  margin-bottom: 4px;
}

.protocol {
  font-size: 0.9em;
  color: #606266;
  word-break: break-word;
  text-align: center;
}

/* 调整表格行高 */
:deep(.el-table__row) {
  height: auto;
}

:deep(.el-table__cell) {
  padding: 8px 0;
  line-height: 1.4;
}

/* 权限禁用按钮样式 */
:deep(.permission-disabled) {
  opacity: 0.5;
  cursor: not-allowed;
  pointer-events: none;
}

/* 关键词输入框和匹配方式选择器布局 */
.keyword-input-wrapper {
  display: flex;
  gap: 10px;
  align-items: center;
  width: 100%;
}

.keyword-input {
  flex: 1;
}

.match-type-select {
  width: 120px;
}

/* 导出选择对话框样式 */
.export-select-dialog {
  padding: 10px 0;
}

.column-select-group {
  display: grid !important;
  grid-template-columns: repeat(3, 1fr) !important;
  gap: 15px !important;
  max-height: 300px;
  overflow-y: auto;
  padding: 10px;
  width: 100%;
}

/* 穿透Element Plus样式封装，确保网格布局应用到复选框组 */
:deep(.el-checkbox-group) {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
  width: 100%;
}

/* 确保每个复选框项占满网格单元格 */
:deep(.el-checkbox) {
  display: flex;
  align-items: center;
  width: 100%;
  box-sizing: border-box;
}

/* 响应式设计 */
@media (max-width: 768px) {
  .column-select-group, :deep(.el-checkbox-group) {
    grid-template-columns: repeat(2, 1fr) !important;
  }
}

@media (max-width: 480px) {
  .column-select-group, :deep(.el-checkbox-group) {
    grid-template-columns: 1fr !important;
  }
}

.file-type-select {
  display: flex;
  gap: 20px;
  align-items: center;
}

.file-type-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 10px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  width: 100px;
}

.file-type-item:hover {
  border-color: #409eff;
}

.file-type-selected {
  border-color: #409eff;
  background-color: #ecf5ff;
}

.file-type-icon {
  width: 40px;
  height: 40px;
  margin-bottom: 8px;
}

.file-type-label {
  font-size: 14px;
  color: #606266;
}

.text-bold {
  font-weight: bold;
}

.flex {
  display: flex;
}

.justify-between {
  justify-content: space-between;
}

.items-center {
  align-items: center;
}

.m-0 {
  margin: 0;
}

.mb-2 {
  margin-bottom: 8px;
}

.mb-4 {
  margin-bottom: 16px;
}
</style>

<script>
// 导入Element Plus图标
import { 
  Search, RefreshRight, Download, DocumentCopy, Document, 
  Edit, Delete, Loading, Check, ArrowDown, Close, Plus
} from '@element-plus/icons-vue';

// 导入Element Plus组件
import { ElMessage } from 'element-plus';

export default {
  name: 'InfoQueryView',
  components: {
    Search,
    RefreshRight,
    Download,
    DocumentCopy,
    Document,
    Edit,
    Delete,
    Loading,
    Check,
    ArrowDown,
    Close,
    Plus
  },
  data() {
    return {
      // 权限检查相关
      userPermissions: {
        add: 0,
        delete: 0,
        edit: 0,
        query: 0
      },
      // 查询表单数据
      searchForm: {
        keyword1: '',
        keyword1MatchType: 'include',
        keyword2: '',
        keyword2MatchType: 'include',
        queryType: ''
      },
      // 查询结果
      searchData: [],
      // 动态列配置
      columns: [],
      // 查询状态
      searchMessage: '请输入关键词并选择查询类型进行搜索',
      messageType: 'info',
      // 分页信息
      currentPage: 1,
      pageSize: 10,
      total: 0,
      // 导出相关
      canExport: false,
      exportProgressVisible: false,
      exportProgress: 0,
      exportProgressText: '正在准备导出数据...',
      exportProgressStatus: '',
      exportProgressColor: '',
      // 对话框显示状态
      editRecordVisible: false,
      deleteConfirmVisible: false,
      detailDialogVisible: false,
      extractDialogVisible: false,
      // 当前操作的记录
      currentRecord: null,
      // 编辑对话框相关
        editDialogTitle: '修改记录',
        editFormData: {
          // 信息系统登录信息
          systemName: '',
          ipUrl: '',
          type: '',
          username: '',
          password: '',
          isActive: '1',
          remark: '',
          // 服务器账号密码
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
          server_cred_notes: '',
          // 网络设备登录信息
          dev_type: '',
          net_type: '',
          brand_sign: '',
          ip_port_protocol: '',
          enable_password: '',
          dev_brand: '',
          dev_model: '',
          dev_sign: '',
          area: '',
          building_floor: '',
          location: '',
          physical_area: '',
          floor_location: '',
          // 宿主机集群信息
          cluster_id: '',
          clusterName: '',
          clusterAddress: '',
          clusterUsername: '',
          clusterPassword: '',
          physicalMachines: []
        },
      // 磁盘信息数据
      diskForms: [this.getEmptyDiskForm()],
      // 自动提取磁盘信息对话框
      extractType: 'windows', // windows或linux
      dialogOsType: '', // 对话框中选择的操作系统类型
      commandOutput: '',
      extractedDisks: [],
      parsedDisks: [], // 解析后的Windows磁盘信息
      // Windows命令相关
      windowsCommand: 'wmic logicaldisk get DeviceID, VolumeName, Size, FreeSpace, FileSystem, Description',
      // Linux命令相关
      linuxCommand: 'echo "=====BLKID_OUTPUT=====" &&df -h && echo "=====BLKID_OUTPUT=====" && blkid',
      linuxCommandOutput: '',
      parsedLinuxDisks: [], // 解析后的Linux磁盘信息
      // 操作系统类型选项
      osOptions: [
        { value: 'Windows', label: 'Windows' },
        { value: 'Linux', label: 'Linux' },
        { value: 'Unix', label: 'Unix' },
        { value: '其他', label: '其他' }
      ],
      // 集群选项
      clusterOptions: [],
      // 集群搜索加载状态
      clusterLoading: false,
      // 用于防止自动填充的key，每次重置表单时更新
      usernameKey: Date.now(),
      passwordKey: Date.now(),
      editFormFields: [],
      editFormRules: {
        // 信息系统登录信息验证规则
        systemName: [
          { required: true, message: '请输入系统名称', trigger: 'blur' },
          { max: 100, message: '系统名称不能超过100个字符', trigger: 'blur' }
        ],
        ipUrl: [
          { required: true, message: '请输入IP/URL', trigger: 'blur' },
          { max: 100, message: 'IP/URL不能超过100个字符', trigger: 'blur' }
        ],
        type: [
          { required: true, message: '请输入登录方式', trigger: 'blur' },
          { max: 50, message: '登录方式不能超过50个字符', trigger: 'blur' }
        ],
        username: [
          { required: true, message: '请输入用户名', trigger: 'blur' },
          { max: 50, message: '用户名不能超过50个字符', trigger: 'blur' }
        ],
        password: [
          { required: true, message: '请输入密码', trigger: 'blur' },
          { max: 100, message: '密码不能超过100个字符', trigger: 'blur' }
        ],
        isActive: [
          { required: true, message: '请选择是否有效', trigger: 'change' }
        ],
        remark: [
          { max: 255, message: '备注不能超过255个字符', trigger: 'blur' }
        ],
        
        // 服务器账号密码验证规则
        server_cred_server_name: [
          { required: true, message: '请输入服务器名称', trigger: 'blur' },
          { max: 100, message: '服务器名称不能超过100个字符', trigger: 'blur' }
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
          { required: true, message: '请输入登录用户名', trigger: 'blur' },
          { max: 50, message: '登录用户名不能超过50个字符', trigger: 'blur' }
        ],
        server_cred_login_password: [
          { required: true, message: '请输入登录密码', trigger: 'blur' },
          { max: 100, message: '登录密码不能超过100个字符', trigger: 'blur' }
        ],
        // 宿主机集群验证规则，通过自定义验证器实现动态验证
        server_cred_host_cluster: [
          {
            validator: this.validateHostCluster,
            trigger: 'blur, change'
          }
        ],
        
        // 网络设备登录信息验证规则
        dev_type: [
          { required: true, message: '请选择网络设备类型', trigger: 'change' }
        ],
        net_type: [
          { required: true, message: '请选择设备所属网络', trigger: 'change' }
        ],
        physical_area: [
          { required: true, message: '请输入设备所属物理区域', trigger: 'blur' }
        ],
        building_floor: [
          { required: true, message: '请输入设备所属楼宇-楼层', trigger: 'blur' }
        ],
        floor_location: [
          { required: true, message: '请输入设备所在楼层位置', trigger: 'blur' }
        ],
        name: [
          { required: true, message: '请输入中文命名', trigger: 'blur' }
        ],
        system_name: [
          { required: true, message: '请输入系统命名', trigger: 'blur' }
        ],
        dev_brand: [
          { required: true, message: '请选择设备品牌', trigger: 'change' }
        ],
        dev_model: [
          { required: true, message: '请选择设备型号', trigger: 'change' }
        ],
        management_ip: [
          { required: true, message: '请输入管理IP', trigger: 'blur' }
        ],
        protocol: [
          { required: true, message: '请选择管理协议', trigger: 'change' }
        ],
        port: [
          { required: true, message: '请输入端口', trigger: 'blur' }
        ],
        username: [
          { required: true, message: '请输入用户名', trigger: 'blur' },
          { max: 50, message: '用户名不能超过50个字符', trigger: 'blur' }
        ],
        password: [
          { required: true, message: '请输入密码', trigger: 'blur' },
          { max: 100, message: '密码不能超过100个字符', trigger: 'blur' }
        ],
        enable_password: [
          { max: 100, message: '特权密码不能超过100个字符', trigger: 'blur' }
        ],
        snmp_community: [
          { max: 100, message: 'SNMP团体字不能超过100个字符', trigger: 'blur' }
        ],
        remark: [
          { max: 255, message: '备注不能超过255个字符', trigger: 'blur' }
        ],
        
        // 宿主机集群信息验证规则
        clusterName: [
          { required: true, message: '请输入集群名称', trigger: 'blur' },
          { max: 100, message: '集群名称不能超过100个字符', trigger: 'blur' }
        ],
        clusterAddress: [
          { required: true, message: '请输入集群地址', trigger: 'blur' },
          { max: 100, message: '集群地址不能超过100个字符', trigger: 'blur' }
        ],
        clusterUsername: [
          { required: true, message: '请输入集群用户名', trigger: 'blur' },
          { max: 50, message: '集群用户名不能超过50个字符', trigger: 'blur' }
        ],
        clusterPassword: [
          { required: true, message: '请输入集群密码', trigger: 'blur' },
          { max: 100, message: '集群密码不能超过100个字符', trigger: 'blur' }
        ],
        'physicalMachines.*.pmName': [
          { required: false, message: '请输入物理机名称', trigger: 'blur' },
          { max: 50, message: '物理机名称不能超过50个字符', trigger: 'blur', transform: (value) => value || '' }
        ],
        'physicalMachines.*.pmIp': [
          { required: false, message: '请输入物理机IP', trigger: 'blur' },
          { validator: this.isValidIpOptional, message: '请输入有效的IP地址', trigger: 'blur', transform: (value) => value || '' }
        ],
        'physicalMachines.*.pmUsername': [
          { required: false, message: '请输入物理机用户名', trigger: 'blur' },
          { max: 50, message: '物理机用户名不能超过50个字符', trigger: 'blur', transform: (value) => value || '' }
        ],
        'physicalMachines.*.pmPassword': [
          { required: false, message: '请输入物理机密码', trigger: 'blur' },
          { max: 100, message: '物理机密码不能超过100个字符', trigger: 'blur', transform: (value) => value || '' }
        ],
        'physicalMachines.*.pmBmcIp': [
          { required: false, message: '请输入BMC IP', trigger: 'blur' },
          { validator: this.isValidIpOptional, message: '请输入有效的BMC IP地址', trigger: 'blur', transform: (value) => value || '' }
        ],
        'physicalMachines.*.pmBmcUsername': [
          { required: false, message: '请输入BMC用户名', trigger: 'blur' },
          { max: 50, message: 'BMC用户名不能超过50个字符', trigger: 'blur', transform: (value) => value || '' }
        ],
        'physicalMachines.*.pmBmcPassword': [
          { required: false, message: '请输入BMC密码', trigger: 'blur' },
          { max: 100, message: 'BMC密码不能超过100个字符', trigger: 'blur', transform: (value) => value || '' }
        ]
      },
      saveEditLoading: false,
      clusterUsernameKey: Date.now(),
      clusterPasswordKey: Date.now(),
      
      // 编辑记录消息
      editRecordMessage: '请修改记录信息',
      editRecordMessageType: 'info',
      
      // 物理机详情相关
      physicalMachineDialogVisible: false,
      currentClusterId: '',
      currentClusterName: '',
      physicalMachineList: [],
      physicalMachineLoading: false,
      physicalMachineColumns: [],
      physicalMachineCurrentPage: 1,
      
      // 导出相关
      exportSelectVisible: false,
      exportColumns: [],
      selectedColumns: [],
      isAllSelected: false,
      exportFileTypes: [
        { value: 'excel', label: 'CSV格式', icon: '/icons/csv.png' },
        { value: 'html', label: 'HTML格式', icon: '/icons/html.png' }
      ],
      selectedFileType: 'excel',
      exportDiskInfo: true,
      
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
      // 字段中文名称映射
      fieldNameMap: {
        // 通用字段
        'id': 'ID',
        'created_at': '创建时间',
        'updated_at': '更新时间',
        'created_by': '创建人',
        'is_active': '是否有效',
        // 信息系统登录信息
        'systemName': '系统名称',
        'ipUrl': 'IP/URL',
        'type': '登录方式',
        'username': '用户名',
        'password': '密码',
        'remark': '备注',
        // 服务器账号密码
        'name': '服务器名称',
        'ip': '服务器IP',
        'port': '端口号',
        'os': '操作系统类型',
        'loginUsername': '登录用户名',
        'loginPassword': '密码',
        'networkArea': '服务器所属网络区域',
        'serverType': '服务器类型',
        'hostCluster': '宿主机集群',
        'notes': '服务器备注',
        // 服务器账号密码原始字段
        'server_cred_server_name': '服务器名称',
        'server_cred_server_ip': '服务器IP',
        'server_cred_server_port': '端口号',
        'server_cred_server_os': '操作系统类型',
        'server_cred_login_username': '登录用户名',
        'server_cred_login_password': '密码',
        'server_cred_notes': '服务器备注',
        'server_cred_network_area': '服务器所属网络区域',
        'server_cred_server_type': '服务器类型',
        'server_cred_host_cluster': '宿主机集群',
        // 网络设备登录信息
        'dev_type': '设备类型',
        'net_type': '网络类型',
        'name': '中文名称(系统名称)',
        'brand_sign': '设备品牌(设备型号)',
        'ip_port_protocol': '管理IP:端口(协议)',
        'enable_password': '使能密码',
        'dev_brand': '设备品牌',
        'dev_model': '设备型号',
        'dev_sign': '设备标识',
        'area': '设备区域',
        'building_floor': '设备楼宇-楼层',
        'location': '设备位置',
        'physical_area': '设备物理区域',
        'floor_location': '设备楼层位置',
        // 宿主机集群信息
        'cluster_id': '集群ID',
        'cluster_name': '集群名称',
        'cluster_address': '集群地址',
        'cluster_username': '集群用户名',
        'cluster_password': '集群密码',
        'pm_count': '集群宿主机数量',
        // 信息系统登录信息导出字段映射
        'login_info_system_name': '系统名称',
        'login_info_ip_url': 'IP或URL地址',
        'login_info_login_type': '登录方式',
        'login_info_username': '账号',
        'login_info_password': '密码',
        'login_info_remark': '备注信息',
        'login_info_created_at': '创建时间',
        'login_info_updated_at': '更新时间',
        'login_info_created_by': '创建人',
        'login_info_is_active': '是否有效'
      }
    };
  },
  computed: {
    isWindows() {
      return this.editFormData.server_cred_server_os && this.editFormData.server_cred_server_os.toLowerCase().includes('windows');
    }
  },
  watch: {
    'editFormData.server_cred_server_os'() {
      // 操作系统类型变化时，重置磁盘表单
      this.diskForms = [this.getEmptyDiskForm()];
    }
  },
  created() {
    // 从localStorage和sessionStorage获取用户权限
    console.log('Checking localStorage and sessionStorage for user info...');
    
    // Check specific keys that might contain user info
    const potentialKeys = ['userInfo', 'currentUser', 'loginUser', 'user'];
    let foundUserInfo = null;
    let foundKey = null;
    let storageType = null;
    
    // Try to find user info in sessionStorage first, then localStorage
    const storages = [sessionStorage, localStorage];
    const storageNames = ['sessionStorage', 'localStorage'];
    
    for (let i = 0; i < storages.length && !foundUserInfo; i++) {
      const storage = storages[i];
      const storageName = storageNames[i];
      
      for (const key of potentialKeys) {
        const value = storage.getItem(key);
        if (value) {
          try {
            const parsed = JSON.parse(value);
            console.log(`Found data in ${storageName}.${key}:`, parsed);
            foundUserInfo = parsed;
            foundKey = key;
            storageType = storageName;
            break; // Use the first found user info
          } catch (e) {
            console.log(`Found non-JSON data in ${storageName}.${key}:`, value);
          }
        }
      }
    }
    
    // Debug: if no user info found, create a test user with search-only permissions for better testing
    if (!foundUserInfo) {
      console.log('No user info found in sessionStorage or localStorage, creating test permissions');
      // Create test permissions with search-only access for better testing
      this.userPermissions = {
        add: 0,
        delete: 0,
        edit: 0,
        query: 1
      };
      console.log('Set test permissions with search-only access');
    } else {
      console.log(`Using found user info from ${storageType}.${foundKey}:`, foundUserInfo);
      
      // Try to infer permissions from user role first (role-based permissions take precedence)
      const userRole = foundUserInfo.role || foundUserInfo.position || '';
      console.log(`User role: ${userRole}`);
      
      // Role-based permission mapping
      let foundPermissions = null;
      if (userRole.toLowerCase().includes('admin') || userRole.toLowerCase().includes('管理员')) {
        foundPermissions = {
          add: 1,
          delete: 1,
          edit: 1,
          query: 1
        };
        console.log('Set admin permissions based on user role');
      } else if (userRole.toLowerCase().includes('search') || userRole.toLowerCase().includes('查询')) {
        foundPermissions = {
          add: 0,
          delete: 0,
          edit: 0,
          query: 1
        };
        console.log('Set search-only permissions based on user role');
      } else if (userRole.toLowerCase().includes('delete') || userRole.toLowerCase().includes('删除')) {
        foundPermissions = {
          add: 0,
          delete: 1,
          edit: 0,
          query: 1
        };
        console.log('Set delete permissions based on user role');
      }
      
      // If no role-based permissions, check for explicit permissions in user info
      if (!foundPermissions) {
        // Check various permission property names
        const permissionProperties = ['permissions', 'userPermissions', 'auth', 'rights', 'privileges'];
        for (const prop of permissionProperties) {
          if (foundUserInfo[prop]) {
            foundPermissions = foundUserInfo[prop];
            console.log(`Found permissions in '${prop}' property:`, foundPermissions);
            break;
          }
        }
        
        // Additional check: if permissions are directly on the user object
        if (!foundPermissions) {
          // Check if the user object itself has permission-like properties
          const directPermissions = {};
          const permissionKeys = ['add', 'delete', 'edit', 'query'];
          let hasDirectPermissions = false;
          
          for (const key of permissionKeys) {
            if (foundUserInfo[key] !== undefined) {
              directPermissions[key] = foundUserInfo[key];
              hasDirectPermissions = true;
            }
          }
          
          if (hasDirectPermissions) {
            foundPermissions = directPermissions;
            console.log('Found direct permissions on user object:', foundPermissions);
          }
        }
      }
      
      if (foundPermissions) {
        // Merge found permissions with default structure and normalize values
        const normalizedPermissions = {};
        const defaultPermissionKeys = Object.keys(this.userPermissions);
        
        // Ensure all default permission keys are included
        for (const key of defaultPermissionKeys) {
          let value = foundPermissions[key] !== undefined ? foundPermissions[key] : this.userPermissions[key];
          
          // Normalize the value to a number
          let normalizedValue = 0;
          if (typeof value === 'string') {
            // Handle various string representations
            if (value.toLowerCase() === 'true' || value === '1' || value === 'yes' || value === 'on') {
              normalizedValue = 1;
            } else if (value.toLowerCase() === 'false' || value === '0' || value === 'no' || value === 'off') {
              normalizedValue = 0;
            } else {
              normalizedValue = parseInt(value) || 0;
            }
          } else if (typeof value === 'boolean') {
            normalizedValue = value ? 1 : 0;
          } else if (typeof value === 'number') {
            normalizedValue = value;
          }
          
          normalizedPermissions[key] = normalizedValue;
          console.log(`Normalized permission ${key}: ${value} -> ${normalizedValue}`);
        }
        
        this.userPermissions = normalizedPermissions;
      } else {
        console.log('No permissions found in user info, setting default search-only permissions');
        // Set default search-only permissions for security
        this.userPermissions = {
          add: 0,
          delete: 0,
          edit: 0,
          query: 1
        };
      }
      
      console.log('Final userPermissions:', this.userPermissions);
    }
    
    // Verify permissions are working correctly
    console.log('Permission verification:');
    console.log('- Has edit permission:', this.hasPermission('edit'));
    console.log('- Has delete permission:', this.hasPermission('delete'));
    console.log('- Has query permission:', this.hasPermission('query'));
  },
  methods: {
    // 权限检查函数
    hasPermission(action) {
      // Permission values are already normalized to numbers during initialization
      return this.userPermissions[action] === 1;
    },
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
        // 确保端口号字段类型正确
        if (this.editFormData.server_cred_server_port) {
          this.editFormData.server_cred_server_port = Number(this.editFormData.server_cred_server_port);
        }
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
      // 根据当前操作系统类型设置dialogOsType和extractType
      this.dialogOsType = this.editFormData.server_cred_server_os || 'Windows';
      if (this.dialogOsType.toLowerCase().includes('windows')) {
        this.extractType = 'windows';
      } else {
        this.extractType = 'linux';
      }
      this.extractDialogVisible = true;
    },
    // 处理对话框操作系统类型变化
    handleDialogOsTypeChange() {
      if (this.dialogOsType.toLowerCase().includes('windows')) {
        this.extractType = 'windows';
      } else {
        this.extractType = 'linux';
      }
    },
    // 切换宿主机集群字段显示
    toggleHostClusterField() {
      if (this.editFormData.server_cred_server_type === '物理机') {
        this.editFormData.server_cred_host_cluster = '';
      }
    },
    // 远程搜索集群
    remoteSearchCluster(query) {
      if (query !== '') {
        this.clusterLoading = true;
        // 模拟远程搜索
        setTimeout(() => {
          this.clusterOptions = [
            { cluster_name: `集群${query}1` },
            { cluster_name: `集群${query}2` },
            { cluster_name: `集群${query}3` }
          ];
          this.clusterLoading = false;
        }, 1000);
      } else {
        this.clusterOptions = [];
      }
    },
    // 验证宿主机集群
    validateHostCluster(rule, value, callback) {
      if (this.editFormData.server_cred_server_type === '虚拟机' && !value) {
        callback(new Error('请选择宿主机集群'));
      } else {
        callback();
      }
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
      // 创建现有磁盘备注信息映射表
      const existingNotesMap = {};
      this.diskForms.forEach(existingDisk => {
        if (existingDisk.driveLetter && existingDisk.notes) {
          existingNotesMap[existingDisk.driveLetter] = existingDisk.notes;
        }
      });
      
      // 清空当前磁盘表单
      this.diskForms = [];
      
      // 填充解析后的磁盘信息，保留原有备注
      disks.forEach(disk => {
        const driveLetter = disk.driveLetter || '';
        const existingNotes = existingNotesMap[driveLetter];
        
        this.diskForms.push({
          driveLetter: driveLetter,
          capacity: disk.capacity || '',
          usedSpace: disk.usedSpace || '',
          notes: existingNotes || (disk.notes || '')
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
          this.$message.warning('未解析到有效磁盘信息，请检查命令输出格式');
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
      
      for (const disk of dfDisks) {
        // 排除tmpfs、devtmpfs、overlay类型的文件系统
        if (disk.deviceName.startsWith('tmpfs') || 
            disk.deviceName.startsWith('devtmpfs') || 
            disk.deviceName.startsWith('overlay')) {
          continue;
        }
        
        // 排除/run/user/*和/sys/fs/cgroup路径的挂载点
        if (disk.mountPoint.startsWith('/run/user/') || 
            disk.mountPoint === '/sys/fs/cgroup') {
          continue;
        }
        
        // 匹配文件系统类型
        let fileSystemType = '';
        
        // 直接匹配设备名称
        if (blkidMap.has(disk.deviceName)) {
          fileSystemType = blkidMap.get(disk.deviceName);
        } else {
          // 对于LVM设备（如/dev/mapper/centos-root）
          if (disk.deviceName.startsWith('/dev/mapper/')) {
            // 尝试从blkidMap中查找相关的物理卷
            for (const [device, type] of blkidMap) {
              // 如果是xfs或ext4类型，直接使用
              if (type === 'xfs' || type === 'ext4') {
                fileSystemType = type;
                break;
              }
            }
          }
        }
        
        finalDisks.push({
          deviceName: disk.deviceName,
          fileSystemType: fileSystemType,
          capacity: disk.capacity,
          usedSpace: disk.usedSpace,
          mountPoint: disk.mountPoint,
          notes: ''
        });
      }
      
      return finalDisks;
    },
    // 填充Linux磁盘表单
    fillLinuxDiskForms(disks) {
      // 创建现有磁盘备注信息映射表
      const existingNotesMap = {};
      this.diskForms.forEach(existingDisk => {
        if (existingDisk.deviceName && existingDisk.notes) {
          existingNotesMap[existingDisk.deviceName] = existingDisk.notes;
        }
      });
      
      // 清空当前磁盘表单
      this.diskForms = [];
      
      // 填充解析后的磁盘信息，保留原有备注
      disks.forEach(disk => {
        const deviceName = disk.deviceName || '';
        const existingNotes = existingNotesMap[deviceName];
        
        this.diskForms.push({
          deviceName: deviceName,
          fileSystemType: disk.fileSystemType || '',
          capacity: disk.capacity || '',
          usedSpace: disk.usedSpace || '',
          mountPoint: disk.mountPoint || '',
          notes: existingNotes || (disk.notes || '')
        });
      });
    },
    // 复制Windows命令
    copyWindowsCommand() {
      navigator.clipboard.writeText(this.windowsCommand).then(() => {
        this.$message.success('命令已复制到剪贴板');
      }).catch(err => {
        console.error('复制失败:', err);
        this.$message.error('复制失败，请手动复制');
      });
    },
    // 复制Linux命令
    copyLinuxCommand() {
      navigator.clipboard.writeText(this.linuxCommand).then(() => {
        this.$message.success('命令已复制到剪贴板');
      }).catch(err => {
        console.error('复制失败:', err);
        this.$message.error('复制失败，请手动复制');
      });
    },
    // 处理操作系统类型变化
    handleOSTypeChange() {
      // 操作系统类型变化时，重置磁盘表单
      this.diskForms = [this.getEmptyDiskForm()];
    },
    
    // 查询磁盘信息
    async fetchDiskInfo(serverId) {
      try {
        // 发送请求获取磁盘信息
        const response = await fetch('/server_cred_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'get_disk_info',
            server_cred_id: serverId
          })
        });
        
        const data = await response.json();
        
        if (data.success) {
          // 填充磁盘信息
          this.fillDiskInfoFromData(data.data);
        } else {
          console.error('获取磁盘信息失败:', data.message);
          this.$message.error('获取磁盘信息失败');
        }
      } catch (error) {
        console.error('获取磁盘信息出错:', error);
        this.$message.error('获取磁盘信息出错，请稍后重试');
      }
    },
    
    // 从数据填充磁盘信息
    fillDiskInfoFromData(diskData) {
      if (!diskData || !Array.isArray(diskData)) {
        this.diskForms = [this.getEmptyDiskForm()];
        return;
      }
      
      this.diskForms = [];
      
      diskData.forEach(item => {
        if (this.isWindows) {
          this.diskForms.push({
            driveLetter: item.drive_letter || '',
            capacity: item.capacity || '',
            usedSpace: item.used_space || '',
            notes: item.notes || ''
          });
        } else {
          this.diskForms.push({
            deviceName: item.device_name || '',
            fileSystemType: item.file_system_type || '',
            capacity: item.capacity || '',
            usedSpace: item.used_space || '',
            mountPoint: item.mount_point || '',
            notes: item.notes || ''
          });
        }
      });
      
      // 如果没有磁盘信息，添加一个空表单
      if (this.diskForms.length === 0) {
        this.diskForms = [this.getEmptyDiskForm()];
      }
    },
    // 检查是否有任何操作权限
    hasAnyPermission() {
      // Permission values are already normalized to numbers during initialization
      return Object.values(this.userPermissions).some(perm => perm === 1);
    },
    // 打开导出弹窗
    openExportDialog() {
      // 根据当前查询类别设置导出列
      this.setExportColumns();
      // 初始化选中列（默认全选）
      this.selectedColumns = this.exportColumns.map(col => col.value);
      this.isAllSelected = true;
      // 打开弹窗
      this.exportSelectVisible = true;
    },
    // 设置导出列
    setExportColumns() {
      switch (this.searchForm.queryType) {
        case 'system':
          // 信息系统登录信息
          this.exportColumns = [
            { value: 'login_info_system_name', label: '系统名称' },
            { value: 'login_info_ip_url', label: 'IP或URL地址' },
            { value: 'login_info_login_type', label: '登录方式' },
            { value: 'login_info_username', label: '账号' },
            { value: 'login_info_password', label: '密码' },
            { value: 'login_info_remark', label: '备注信息' },
            { value: 'login_info_created_at', label: '创建时间' },
            { value: 'login_info_updated_at', label: '更新时间' },
            { value: 'login_info_created_by', label: '创建人' },
            { value: 'login_info_is_active', label: '是否有效' }
          ];
          break;
        case 'server':
          // 服务器账号密码
          this.exportColumns = [
            { value: 'server_cred_network_area', label: '服务器所属网络区域' },
            { value: 'server_cred_server_type', label: '服务器类型' },
            { value: 'server_cred_host_cluster', label: '宿主机集群' },
            { value: 'server_cred_server_name', label: '服务器名称' },
            { value: 'server_cred_server_ip', label: '服务器IP地址' },
            { value: 'server_cred_server_port', label: '服务器端口号' },
            { value: 'server_cred_server_os', label: '操作系统类型' },
            { value: 'server_cred_login_username', label: '用户名' },
            { value: 'server_cred_login_password', label: '密码' },
            { value: 'server_cred_edr_installed', label: 'EDR安装' },
            { value: 'server_cred_ntp_configured', label: 'NTP配置' },
            { value: 'server_cred_notes', label: '备注信息' },
            { value: 'created_at', label: '创建时间' },
            { value: 'updated_at', label: '更新时间' },
            { value: 'is_active', label: '是否有效' },
            { value: 'server_cred_created_by', label: '创建人' }
          ];
          break;
        // 可以根据需要添加其他查询类别的列配置
        default:
          this.exportColumns = [];
      }
    },
    // 全选/取消全选
    toggleSelectAll() {
      if (this.isAllSelected) {
        this.selectedColumns = [];
      } else {
        this.selectedColumns = this.exportColumns.map(col => col.value);
      }
      this.isAllSelected = !this.isAllSelected;
    },
    // 确认导出
    confirmExport() {
      if (this.selectedColumns.length === 0) {
        ElMessage.warning('请至少选择一列进行导出');
        return;
      }
      // 关闭弹窗
      this.exportSelectVisible = false;
      // 调用导出函数
      this.exportResults(this.selectedFileType, this.selectedColumns);
    },
    // 导出结果
    exportResults(format, selectedColumns = []) {
      // 验证是否选择了查询类别
      if (!this.searchForm.queryType) {
        this.showSearchMessage('请先选择查询类别', 'danger');
        return;
      }
      
      // 获取当前登录用户名
      let username = '';
      const sessionUser = sessionStorage.getItem('currentUser');
      const localUser = localStorage.getItem('userInfo');
      if (sessionUser) {
        username = JSON.parse(sessionUser).username;
      } else if (localUser) {
        username = JSON.parse(localUser).username || JSON.parse(localUser).account;
      }
      
      // 显示导出进度对话框
      this.exportProgressVisible = true;
      this.exportProgress = 0;
      this.exportProgressText = '正在准备导出数据...';
      this.exportProgressStatus = '';
      this.exportProgressColor = '';
      
      // 模拟进度更新
      const updateProgress = (text, percentage) => {
        this.exportProgress = percentage;
        this.exportProgressText = text;
        if (percentage === 100) {
          this.exportProgressStatus = 'success';
          this.exportProgressColor = '#67C23A';
        }
      };
      
      // 发送导出请求
      updateProgress('正在获取数据...', 20);
      
      fetch('search_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          keyword1: this.searchForm.keyword1,
          keyword1MatchType: this.searchForm.keyword1MatchType,
          keyword2: this.searchForm.keyword2,
          keyword2MatchType: this.searchForm.keyword2MatchType,
          queryType: this.searchForm.queryType,
          page: 1,
          pageSize: 1000000, // 导出所有数据（使用足够大的数值）
          export: true,
          exportFormat: format === 'html' ? 'pdf' : 'excel',
          username: username,
          selectedColumns: selectedColumns,
          exportDiskInfo: this.exportDiskInfo
        })
      })
      .then(response => {
        updateProgress('正在处理导出数据...', 50);
        
        // 检查响应是否为JSON（错误情况）
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          return response.json().then(data => {
            throw new Error(data.message || '导出失败');
          });
        }
        
        // 对于文件响应，获取文件名
        const contentDisposition = response.headers.get('content-disposition');
        let filename = `查询结果_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}`;
        
        if (format === 'html') {
          filename += '.html';
        } else {
          filename += '.csv';
        }
        
        if (contentDisposition) {
          const filenameMatch = contentDisposition.match(/filename\*?=(?:UTF-8'')?([^;]+)/);
          if (filenameMatch) {
            filename = decodeURIComponent(filenameMatch[1]);
          }
        }
        
        updateProgress('正在生成文件...', 80);
        
        // 将响应转换为Blob
        return response.blob().then(blob => {
          updateProgress('准备下载...', 100);
          
          // 创建下载链接
          const url = window.URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.download = filename;
          
          // 模拟点击下载
          document.body.appendChild(link);
          link.click();
          
          // 清理
          setTimeout(() => {
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
          }, 0);
          
          // 关闭进度对话框
          setTimeout(() => {
            this.exportProgressVisible = false;
            this.showSearchMessage('导出成功', 'success');
          }, 500);
        });
      })
      .catch(error => {
        // 关闭进度对话框
        this.exportProgressVisible = false;
        this.showSearchMessage(`导出失败: ${error.message}`, 'danger');
        console.error('导出出错:', error);
      });
    },
    
    // 执行搜索
    performSearch(page = 1) {
      // 验证是否选择了查询类别
      if (!this.searchForm.queryType) {
        this.showSearchMessage('请先选择查询类别', 'danger');
        return;
      }
      
      // 获取当前登录用户名
      let username = '';
      const sessionUser = sessionStorage.getItem('currentUser');
      const localUser = localStorage.getItem('userInfo');
      if (sessionUser) {
        username = JSON.parse(sessionUser).username;
      } else if (localUser) {
        username = JSON.parse(localUser).username || JSON.parse(localUser).account;
      }
      
      // 显示搜索状态消息
      this.showSearchMessage('正在搜索...', 'info');
      
      // 发送真实的API请求
      fetch('search_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          keyword1: this.searchForm.keyword1,
          keyword1MatchType: this.searchForm.keyword1MatchType,
          keyword2: this.searchForm.keyword2,
          keyword2MatchType: this.searchForm.keyword2MatchType,
          queryType: this.searchForm.queryType,
          page: page,
          pageSize: this.pageSize,
          username: username
        })
      })
      .then(response => {
        // 先检查响应是否为JSON格式
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          return response.json().catch(parseError => {
            // JSON解析失败，返回错误响应
            console.error('JSON解析失败:', parseError);
            return {
              success: false,
              message: '服务器返回了无效的JSON格式，请联系管理员检查服务器日志',
              data: [],
              total: 0,
              page: 1,
              page_size: this.pageSize
            };
          });
        } else {
          // 如果不是JSON，返回错误响应
          return {
            success: false,
            message: '无法连接到服务器，请确保PHP环境正常运行',
            data: [],
            total: 0,
            page: 1,
            page_size: this.pageSize
          };
        }
      })
      .then(data => {
        if (data.success) {
          // 处理搜索结果，确保字段映射正确
          let processedData = data.data;
          
          // 针对服务器账号密码类型，处理IP字段映射
          if (this.searchForm.queryType === 'server') {
            processedData = data.data.map(item => {
              // 如果有server_cred_server_ip字段，将其映射到ip字段
              // 同时保留所有原始字段，包括server_cred_disks
              return {
                ...item,
                ip: item.server_cred_server_ip || item.ip || '',
                name: item.server_cred_server_name || item.name || '',
                port: item.server_cred_server_port || item.port || '',
                os: item.server_cred_server_os || item.os || '',
                username: item.server_cred_login_username || item.username || '',
                password: item.server_cred_login_password || item.password || ''
              };
            });
          }
          // 针对网络设备登录信息类型，处理相关字段
          else if (this.searchForm.queryType === 'network') {
            processedData = data.data.map(item => {
              // 构建设备品牌（型号）字段
              const brandSign = `${item.net_dev_cred_dev_brand || ''} (${item.net_dev_cred_dev_sign || ''})`;
              
              // 构建管理IP:端口(协议)字段
              const ipPort = `${item.net_dev_cred_management_ip || ''}:${item.net_dev_cred_port || ''}`;
              const protocol = item.net_dev_cred_protocol || '';
              const ipPortProtocol = `${ipPort} (${protocol})`;
              
              return {
                ...item,
                // 网络类型字段，直接使用net_dev_cred_net_type
                net_type: item.net_dev_cred_net_type || '',
                // 设备品牌（型号）字段
                brand_sign: brandSign,
                // 管理IP:端口(协议)字段
                ip_port_protocol: ipPortProtocol,
                // 单独的IP:端口和协议字段，用于后续显示
                ip_port: ipPort,
                protocol: protocol,
                // 添加系统名称字段，用于后续显示
                system_name: item.net_dev_cred_system_name || ''
              };
            });
          }
          
          // 更新搜索结果
          this.searchData = processedData;
          this.total = data.total;
          this.currentPage = data.page;
          
          // 更新动态列
          this.updateColumns(this.searchForm.queryType);
          
          // 显示成功消息
          this.showSearchMessage(`搜索完成，共找到 ${data.total} 条记录`, 'success');
          
          // 启用/禁用导出按钮
          this.canExport = data.total > 0;
        } else {
          // 显示错误消息
          this.showSearchMessage(`搜索失败: ${data.message || '未知错误'}`, 'danger');
          
          // 清空搜索结果
          this.searchData = [];
          this.total = 0;
          this.canExport = false;
        }
      })
      .catch(error => {
        // 显示错误消息
        this.showSearchMessage(`搜索出错: ${error.message || '网络错误'}`, 'danger');
        
        // 清空搜索结果
        this.searchData = [];
        this.total = 0;
        this.canExport = false;
      });
    },
    
    // 重置搜索
    resetSearch() {
      // 清空查询表单
      this.searchForm = {
        keyword1: '',
        keyword2: '',
        queryType: ''
      };
      
      // 清空搜索结果
      this.searchData = [];
      this.total = 0;
      this.currentPage = 1;
      this.columns = [];
      this.canExport = false;
      
      // 更新消息
      this.showSearchMessage('请输入关键词并选择查询类型进行搜索', 'info');
    },
    
    // 更新动态列
    updateColumns(queryType) {
      let columns = [];
      
      switch (queryType) {
        case 'system':
          // 信息系统登录信息 - 仅显示指定字段
          columns = [
            { prop: 'name', label: '系统名称', minWidth: 150 },
            { prop: 'ipUrl', label: 'IP/URL', minWidth: 150 },
            { prop: 'loginType', label: '登录方式', minWidth: 45 },
            { prop: 'username', label: '用户名', minWidth: 120 },
            { prop: 'password', label: '密码', minWidth: 120 }
          ];
          break;
        case 'server':
          // 服务器账号密码 - 仅显示指定字段
          columns = [
            { prop: 'name', label: '服务器名称', minWidth: 150 },
            { prop: 'ip', label: 'IP', minWidth: 120 },
            { prop: 'port', label: '服务器端口', minWidth: 60 },
            { prop: 'os', label: '服务器操作系统', minWidth: 150 },
            { prop: 'username', label: '登录用户名', minWidth: 120 },
            { prop: 'password', label: '登录密码', minWidth: 120 }
          ];
          break;
        case 'network':
          // 网络设备登录信息 - 仅显示指定字段
          columns = [
            { prop: 'dev_type', label: '设备类型', minWidth: 85 },
            { prop: 'net_type', label: '网络类型', minWidth: 85},
            { prop: 'name', label: '中文名称(系统名称)', minWidth: 200 },
            { prop: 'brand_sign', label: '设备品牌（型号）', minWidth: 180 },
            { prop: 'ip_port_protocol', label: '管理IP:端口(协议)', minWidth: 180 },
            { prop: 'username', label: '用户名', minWidth: 120 },
            { prop: 'password', label: '密码', minWidth: 120 },
            { prop: 'enable_password', label: '使能密码', minWidth: 120 }
          ];
          break;
        case 'cluster':
          // 宿主机集群信息 - 仅显示指定字段
          columns = [
            { prop: 'cluster_name', label: '集群名称', minWidth: 150 },
            { prop: 'cluster_address', label: '集群地址', minWidth: 150 },
            { prop: 'cluster_username', label: '集群用户名', minWidth: 120 },
            { prop: 'cluster_password', label: '集群密码', minWidth: 90 },
            { prop: 'pm_count', label: '集群宿主机数量', minWidth: 65 }
          ];
          break;
        default:
          // 默认显示所有字段
          columns = [
            { prop: 'name', label: '名称', minWidth: 150 },
            { prop: 'ip_url', label: 'IP/URL', minWidth: 150 },
            { prop: 'type', label: '类型', minWidth: 120 },
            { prop: 'username', label: '用户名', minWidth: 120 },
            { prop: 'password', label: '密码', minWidth: 120 },
            { prop: 'remark', label: '备注', minWidth: 150 },
            { prop: 'created_at', label: '创建时间', minWidth: 150 },
            { prop: 'category', label: '类别', minWidth: 120 }
          ];
      }
      
      this.columns = columns;
    },
    
    // 处理分页变化
    handleCurrentChange(page) {
      this.performSearch(page);
    },
    
    // 处理每页显示条数变化
    handleSizeChange(size) {
      this.pageSize = size;
      this.currentPage = 1; // 重置到第一页
      this.performSearch(1);
    },
    
    // 显示集群关联的物理机
    showClusterPhysicalMachines(clusterId, clusterName) {
      this.currentClusterId = clusterId;
      this.currentClusterName = clusterName;
      this.physicalMachineCurrentPage = 1;
      this.physicalMachineDialogVisible = true;
      
      // 初始化物理机表格列
      this.initPhysicalMachineColumns();
      
      // 查询物理机数据
      this.getClusterPhysicalMachines();
    },
    
    // 初始化物理机表格列
    initPhysicalMachineColumns() {
      this.physicalMachineColumns = [
        { prop: 'cluster_pm_ip', label: '物理机IP', minWidth: 120 },
        { prop: 'cluster_pm_username', label: '物理机用户名', minWidth: 150 },
        { prop: 'cluster_pm_password', label: '物理机密码', minWidth: 150 },
        { prop: 'cluster_pm_bmc_ip', label: 'BMC IP', minWidth: 120 },
        { prop: 'cluster_pm_bmc_username', label: 'BMC用户名', minWidth: 150 },
        { prop: 'cluster_pm_bmc_password', label: 'BMC密码', minWidth: 150 },
        { prop: 'cluster_pm_created_at', label: '创建时间', minWidth: 150 }
      ];
    },
    
    // 获取集群关联的物理机
    getClusterPhysicalMachines() {
      this.physicalMachineLoading = true;
      
      // 发送API请求
      fetch('search_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          keyword1: '',
          keyword2: '',
          queryType: 'cluster_physical_machine',
          clusterId: this.currentClusterId,
          page: 1,
          pageSize: 100 // 一次获取所有相关物理机
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          this.physicalMachineList = data.data;
        } else {
          this.physicalMachineList = [];
          this.$message.error(`获取宿主机信息失败: ${data.message || '未知错误'}`);
        }
      })
      .catch(error => {
        this.physicalMachineList = [];
        this.$message.error(`网络错误: ${error.message}`);
      })
      .finally(() => {
        this.physicalMachineLoading = false;
      });
    },
    
    // 加载物理机信息到编辑表单
    loadPhysicalMachines(clusterId) {
      // 发送API请求获取物理机信息
      fetch('search_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          keyword1: '',
          keyword2: '',
          queryType: 'cluster_physical_machine',
          clusterId: clusterId,
          page: 1,
          pageSize: 100 // 一次获取所有相关物理机
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // 将物理机信息转换为编辑表单所需的格式
          const physicalMachines = data.data.map((machine, index) => ({
            id: machine.cluster_pm_id || `pm_${Date.now()}_${index}`,
            pmName: machine.cluster_pm_name || `物理机${index + 1}`,
            pmIp: machine.cluster_pm_ip || '',
            pmUsername: machine.cluster_pm_username || '',
            pmPassword: machine.cluster_pm_password || '',
            pmBmcIp: machine.cluster_pm_bmc_ip || '',
            pmBmcUsername: machine.cluster_pm_bmc_username || '',
            pmBmcPassword: machine.cluster_pm_bmc_password || '',
            pmUsernameKey: Date.now(),
            pmPasswordKey: Date.now(),
            pmBmcUsernameKey: Date.now(),
            pmBmcPasswordKey: Date.now()
          }));
          
          // 更新编辑表单中的物理机信息
          this.editFormData.physicalMachines = physicalMachines;
          
          // 清除表单验证状态
          this.$nextTick(() => {
            if (this.$refs.editFormRef) {
              this.$refs.editFormRef.clearValidate();
            }
          });
        } else {
          this.editFormData.physicalMachines = [];
          this.$message.error(`获取物理机信息失败: ${data.message || '未知错误'}`);
        }
      })
      .catch(error => {
        this.editFormData.physicalMachines = [];
        this.$message.error(`网络错误: ${error.message}`);
      });
    },
    
    // 添加物理机
    addPhysicalMachine() {
      // 检查是否有编辑权限
      if (!this.hasPermission('edit')) {
        ElMessage.error('您没有修改权限，请联系管理员获取相应权限');
        return;
      }
      
      const nextNumber = this.editFormData.physicalMachines.length + 1;
      this.editFormData.physicalMachines.push({
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
      // 检查是否有编辑权限
      if (!this.hasPermission('edit')) {
        ElMessage.error('您没有修改权限，请联系管理员获取相应权限');
        return;
      }
      
      if (this.editFormData.physicalMachines.length > 1) {
        this.editFormData.physicalMachines.splice(index, 1);
      } else {
        this.$message.warning('至少需要保留一台物理机');
      }
    },
    
    // 重置编辑表单
    resetEditForm() {
      if (this.$refs.editFormRef) {
        this.$refs.editFormRef.resetFields();
      }
      // 更新key值，强制重新渲染输入框，清除浏览器自动填充
      this.clusterUsernameKey = Date.now();
      this.clusterPasswordKey = Date.now();
      
      // 获取当前记录类型
      const recordType = this.currentRecord?.category || 'unknown';
      
      // 根据记录类型重置表单数据
      switch (recordType) {
        case 'cluster':
          // 重置物理机列表，保留一个空的物理机
          this.editFormData.physicalMachines = [{
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
          }];
          break;
        // 其他类型的表单数据由resetFields()自动处理
        default:
          break;
      }
    },
    
    // 处理物理机分页变化
    handlePhysicalMachinePageChange(page) {
      this.physicalMachineCurrentPage = page;
    },
    
    // 编辑记录
    editRecord(record) {
      // 检查是否有编辑权限
      if (!this.hasPermission('edit')) {
        ElMessage.error('您没有修改权限，请联系管理员获取相应权限');
        return;
      }
      
      this.currentRecord = record;
      this.editRecordVisible = true;
      
      // 获取记录类型
      const recordType = record.category || 'unknown';
      
      // 设置对话框标题
      switch (recordType) {
        case 'system':
          this.editDialogTitle = `编辑信息系统登录信息`;
          break;
        case 'server':
          this.editDialogTitle = `编辑服务器账号密码`;
          break;
        case 'network':
          this.editDialogTitle = `编辑网络设备登录信息`;
          break;
        case 'cluster':
          this.editDialogTitle = `编辑宿主机集群信息`;
          break;
        default:
          this.editDialogTitle = `编辑记录`;
      }
      
      // 初始化表单数据
      switch (recordType) {
        case 'system':
          // 信息系统登录信息
          this.editFormData = {
            login_info_id: record.id || record.login_info_id || '',
            systemName: record.name || record.login_info_system_name || '',
            ipUrl: record.ip_url || record.login_info_ip_url || '',
            type: record.loginType || record.login_info_login_type || record.type || record.login_info_type || '',
            username: record.username || record.login_info_username || '',
            password: record.password || record.login_info_password || '',
            isActive: record.isActive || record.login_info_is_active || '1',
            remark: record.remark || record.login_info_remark || ''
          };
          break;
        case 'server':
          // 服务器账号密码
          this.editFormData = {
            server_cred_id: record.id || record.server_cred_id || '',
            server_cred_network_area: record.network_area || record.server_cred_network_area || '内网',
            server_cred_server_type: record.server_type || record.server_cred_server_type || '虚拟机',
            server_cred_host_cluster: record.host_cluster || record.server_cred_host_cluster || '',
            server_cred_server_name: record.name || record.server_cred_server_name || '',
            server_cred_server_ip: record.ip || record.server_cred_server_ip || '',
            server_cred_server_os: record.os || record.server_cred_server_os || '',
            server_cred_server_port: record.port || record.server_cred_server_port ? Number(record.port || record.server_cred_server_port) : null,
            server_cred_login_username: record.username || record.server_cred_login_username || '',
            server_cred_login_password: record.password || record.server_cred_login_password || '',
            server_cred_edr_installed: record.server_cred_edr_installed || '是',
            server_cred_ntp_configured: record.server_cred_ntp_configured || '是',
            server_cred_notes: record.notes || record.server_cred_notes || ''
          };
          
          // 初始化磁盘表单
          this.diskForms = [];
          
          // 尝试从record中获取磁盘信息
          console.log('=== 磁盘信息填充调试 ===');
          console.log('record对象:', record);
          console.log('record.server_cred_disks:', record.server_cred_disks);
          console.log('record.server_cred_disks类型:', typeof record.server_cred_disks);
          
          // 尝试多种可能的字段名
          let diskData = null;
          const possibleDiskFields = [
            'server_cred_disks', 'disks', 'disk_forms', 'diskForms', 
            'server_cred_disk', 'disk', 'server_disks', 'server_disk',
            'disk_info', 'diskInfo', 'disks_info', 'disksInfo'
          ];
          
          // 尝试从record的直接字段中获取
          for (const field of possibleDiskFields) {
            if (record[field] !== undefined && record[field] !== null && record[field] !== '') {
              diskData = record[field];
              console.log(`找到磁盘数据字段: ${field}`, diskData);
              break;
            }
          }
          
          // 尝试从record的嵌套结构中获取
          if (!diskData) {
            // 尝试从record.data中获取
            if (record.data && typeof record.data === 'object') {
              for (const field of possibleDiskFields) {
                if (record.data[field] !== undefined && record.data[field] !== null && record.data[field] !== '') {
                  diskData = record.data[field];
                  console.log(`从record.data找到磁盘数据字段: ${field}`, diskData);
                  break;
                }
              }
            }
          }
          
          console.log('最终使用的磁盘数据:', diskData);
          
          if (diskData) {
            try {
              const disks = typeof diskData === 'string' ? JSON.parse(diskData) : diskData;
              console.log('解析后的磁盘数据:', disks);
              console.log('解析后的数据类型:', typeof disks);
              console.log('是否为数组:', Array.isArray(disks));
              
              if (Array.isArray(disks)) {
                console.log('磁盘数据数组长度:', disks.length);
                disks.forEach((disk, index) => {
                  console.log(`磁盘${index}:`, disk);
                  this.diskForms.push({
                    driveLetter: disk.driveLetter || disk.drive_letter || disk.driveletter || disk.DriveLetter || disk.DRIVELETTER || '',
                    capacity: disk.capacity || disk.Capacity || disk.size || disk.Size || disk.total || disk.Total || '',
                    usedSpace: disk.usedSpace || disk.used_space || disk.usedspace || disk.UsedSpace || disk.used || disk.Used || disk.usedSize || '',
                    deviceName: disk.deviceName || disk.device_name || disk.devicename || disk.DeviceName || disk.device || disk.Device || '',
                    fileSystemType: disk.fileSystemType || disk.file_system_type || disk.fsType || disk.FileSystemType || disk.fstype || disk.FSType || disk.type || disk.Type || '',
                    mountPoint: disk.mountPoint || disk.mount_point || disk.mountpoint || disk.MountPoint || disk.mount || disk.Mount || disk.path || disk.Path || '',
                    notes: disk.notes || disk.Notes || disk.comment || disk.Comment || disk.description || disk.Description || ''
                  });
                });
              } else if (typeof disks === 'object' && disks !== null) {
                // 处理单个磁盘对象的情况
                console.log('处理单个磁盘对象');
                this.diskForms.push({
                  driveLetter: disks.driveLetter || disks.drive_letter || disks.driveletter || disks.DriveLetter || disks.DRIVELETTER || '',
                  capacity: disks.capacity || disks.Capacity || disks.size || disks.Size || disks.total || disks.Total || '',
                  usedSpace: disks.usedSpace || disks.used_space || disks.usedspace || disks.UsedSpace || disks.used || disks.Used || disks.usedSize || '',
                  deviceName: disks.deviceName || disks.device_name || disks.devicename || disks.DeviceName || disks.device || disks.Device || '',
                  fileSystemType: disks.fileSystemType || disks.file_system_type || disks.fsType || disks.FileSystemType || disks.fstype || disks.FSType || disks.type || disks.Type || '',
                  mountPoint: disks.mountPoint || disks.mount_point || disks.mountpoint || disks.MountPoint || disks.mount || disks.Mount || disks.path || disks.Path || '',
                  notes: disks.notes || disks.Notes || disks.comment || disks.Comment || disks.description || disks.Description || ''
                });
              }
            } catch (error) {
              console.error('解析磁盘信息失败:', error);
              console.error('错误堆栈:', error.stack);
            }
          }
          
          console.log('最终diskForms:', this.diskForms);
          
          // 如果没有磁盘信息，添加一个空的磁盘表单
          if (this.diskForms.length === 0) {
            console.log('没有磁盘信息，添加空表单');
            this.diskForms = [this.getEmptyDiskForm()];
          }
          
          // 从数据库获取磁盘信息
          const serverId = this.editFormData.server_cred_id;
          if (serverId) {
            console.log('从数据库获取磁盘信息，serverId:', serverId);
            this.fetchDiskInfo(serverId);
          }
          console.log('=== 磁盘信息填充调试结束 ===');
          break;
        case 'network':
          // 网络设备登录信息
          // 显示加载状态
          this.saveEditLoading = true;
          
          try {
            // 从record中提取原始字段值，确保使用正确的字段名
            const netDevCredId = record.id || record.net_dev_cred_id || '';
            const devType = record.dev_type || record.net_dev_cred_dev_type || '';
            const netType = record.net_type || record.net_dev_cred_net_type || '';
            const chineseName = record.name || record.net_dev_cred_chinese_name || '';
            const systemName = record.system_name || record.net_dev_cred_system_name || '';
            const devBrand = record.dev_brand || record.net_dev_cred_dev_brand || '';
            // 设备型号从net_dev_cred_dev_sign字段获取
            const devModel = record.dev_model || record.net_dev_cred_dev_sign || '';
            const devSign = record.dev_sign || record.net_dev_cred_dev_sign || '';
            const managementIp = record.net_dev_cred_management_ip || '';
            const protocol = record.net_dev_cred_protocol || '';
            const port = record.net_dev_cred_port || '';
            // 设备所属物理区域从net_dev_cred_physical_area字段获取
            const physicalArea = record.physical_area || record.net_dev_cred_physical_area || '';
            const buildingFloor = record.building_floor || record.net_dev_cred_building_floor || '';
            // 设备所在楼层位置从net_dev_cred_floor_location字段获取
            const floorLocation = record.floor_location || record.net_dev_cred_floor_location || '';
            const username = record.username || record.net_dev_cred_username || '';
            const password = record.password || record.net_dev_cred_password || '';
            const enablePassword = record.enable_password || record.net_dev_cred_enable_password || '';
            const snmpCommunity = record.snmp_community || record.net_dev_cred_snmp || '';
            const remark = record.remark || record.net_dev_cred_description || '';
            
            // 构建组合字段
            const ipPortProtocol = `${managementIp}:${port} (${protocol})`;
            const brandSign = `${devBrand} (${devSign})`;
            
            this.editFormData = {
              net_dev_cred_id: netDevCredId,
              // 设备基本信息
              dev_type: devType,
              net_type: netType,
              name: chineseName, // 中文命名
              system_name: systemName, // 系统命名
              dev_brand: devBrand,
              dev_model: devModel, // 设备型号，来自net_dev_cred_dev_sign
              dev_sign: devSign,
              // 设备位置信息
              physical_area: physicalArea, // 设备所属物理区域，来自net_dev_cred_physical_area
              building_floor: buildingFloor, // 设备所属楼宇-楼层
              floor_location: floorLocation, // 设备所在楼层位置，来自net_dev_cred_floor_location
              // 管理信息
              management_ip: managementIp, // 管理IP
              protocol: protocol, // 管理协议
              port: port, // 端口
              // 认证信息
              username: username,
              password: password,
              enable_password: enablePassword, // 特权密码/使能密码
              snmp_community: snmpCommunity, // SNMP团体字
              remark: remark, // 备注
              // 组合字段（用于显示）
              ip_port_protocol: ipPortProtocol,
              brand_sign: brandSign
            };
          } catch (error) {
            console.error('加载网络设备数据失败:', error);
            this.$message.error('加载网络设备数据失败，请稍后重试');
            // 重置表单数据
            this.resetEditForm();
          } finally {
            // 隐藏加载状态
            this.saveEditLoading = false;
          }
          break;
        case 'cluster':
          // 宿主机集群信息
          this.editFormData = {
            cluster_id: record.cluster_id || '',
            clusterName: record.cluster_name || '',
            clusterAddress: record.cluster_address || '',
            clusterUsername: record.cluster_username || '',
            clusterPassword: record.cluster_password || '',
            physicalMachines: [{
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
            }]
          };
          // 加载物理机信息
          this.loadPhysicalMachines(record.cluster_id);
          break;
        default:
          // 默认表单数据 - 使用 generic id
          this.editFormData = {
            id: record.id || ''
          };
      }
      
      // 清除表单验证状态
      if (this.$refs.editFormRef) {
        this.$refs.editFormRef.clearValidate();
      }
      
      // 更新key值，强制重新渲染输入框，清除浏览器自动填充
      this.clusterUsernameKey = Date.now();
      this.clusterPasswordKey = Date.now();
    },
    
    // 删除记录
    deleteRecord(record) {
      // 检查是否有删除权限
      if (!this.hasPermission('delete')) {
        ElMessage.error('您没有删除权限，请联系管理员获取相应权限');
        return;
      }
      
      this.currentRecord = record;
      this.deleteConfirmVisible = true;
    },
    
    // 查询详细信息
    handleDetailQuery(record) {
      // 检查是否有查询权限
      if (!this.hasPermission('query')) {
        ElMessage.error('您没有查询详情权限，请联系管理员获取相应权限');
        return;
      }
      
      // 设置当前记录
      this.currentRecord = record;
      // 打开详细信息对话框
      this.detailDialogVisible = true;
    },
    
    // 获取字段中文名称
    getFieldName(fieldKey) {
      // 从字段映射中获取中文名称，如未找到则返回原字段名
      return this.fieldNameMap[fieldKey] || fieldKey;
    },
    
    // 获取要显示的字段，根据记录类型决定是否显示原始字段
    getDisplayFields() {
      if (!this.currentRecord) {
        return {};
      }
      
      const displayFields = {};
      const recordType = this.currentRecord.category || 'unknown';
      
      // 遍历所有字段
      for (const key in this.currentRecord) {
        // 过滤掉category字段（内部使用，不需要显示）
        if (key === 'category') {
          continue;
        }
        
        // 对于服务器账号密码类型，显示所有字段，包括原始字段
        if (recordType === 'server') {
          displayFields[key] = this.currentRecord[key];
        } 
        // 对于其他类型，仍然过滤掉原始数据库字段名
        else if (!this.isOriginalField(key)) {
          displayFields[key] = this.currentRecord[key];
        }
      }
      
      return displayFields;
    },
    
    // 判断是否为原始数据库字段
    isOriginalField(fieldKey) {
      // 原始字段特征：
      // 1. 以表前缀开头，如login_info_、server_cred_、net_dev_cred_、cluster_cred_
      // 2. 不在fieldNameMap中（或在fieldNameMap中但为重复字段）
      const originalPrefixes = ['login_info_', 'server_cred_', 'net_dev_cred_', 'cluster_cred_'];
      
      // 检查是否以原始字段前缀开头
      for (const prefix of originalPrefixes) {
        if (fieldKey.indexOf(prefix) === 0) {
          return true;
        }
      }
      
      return false;
    },
    
    // 确认删除记录
    confirmDelete() {
      // 再次检查是否有删除权限（双重保障）
      if (!this.hasPermission('delete')) {
        ElMessage.error('您没有删除权限，请联系管理员获取相应权限');
        this.deleteConfirmVisible = false;
        return;
      }
      
      // 关闭确认对话框
      this.deleteConfirmVisible = false;
      
      // 显示加载状态
      this.showSearchMessage('正在删除记录...', 'info');
      
      // 获取当前登录用户名
      let username = '';
      const sessionUser = sessionStorage.getItem('currentUser');
      const localUser = localStorage.getItem('userInfo');
      if (sessionUser) {
        username = JSON.parse(sessionUser).username;
      } else if (localUser) {
        const parsedLocalUser = JSON.parse(localUser);
        username = parsedLocalUser.username || parsedLocalUser.account;
      }
      
      // 获取记录类型
      const recordType = this.currentRecord.category || 'unknown';
      console.log(`Deleting record of type: ${recordType}`);
      
      // 映射前端记录类型到后端更新类型
      const typeMapping = {
        'system': 'login_info',
        'server': 'server_cred',
        'network': 'net_dev_cred',
        'cluster': 'cluster_cred'
      };
      
      // 获取对应的后端更新类型
      const finalType = typeMapping[recordType] || recordType;
      console.log(`Mapping frontend type '${recordType}' to backend delete type '${finalType}'`);
      
      // 发送真实的API请求
      fetch('search_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'delete',
          type: finalType,
          id: this.currentRecord.id || this.currentRecord.login_info_id || this.currentRecord.server_cred_id || this.currentRecord.net_dev_cred_id,
          username: username
        })
      })
      .then(response => {
        // 先检查响应是否为JSON格式
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          return response.json().catch(parseError => {
            // JSON解析失败，返回错误响应
            console.error('JSON解析失败:', parseError);
            return {
              success: false,
              message: '服务器返回了无效的JSON格式，请联系管理员检查服务器日志'
            };
          });
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
          // 更新成功，刷新搜索结果
          this.performSearch(this.currentPage);
          
          // 显示成功消息
          this.showSearchMessage('记录删除成功', 'success');
          ElMessage.success('记录删除成功');
        } else {
          // 删除失败
          throw new Error(data.message || '未知错误');
        }
      })
      .catch(error => {
        // 显示错误消息
        this.showSearchMessage(`记录删除失败：${error.message}`, 'danger');
        ElMessage.error(`记录删除失败：${error.message}`);
        console.error('删除记录出错:', error);
      });
    },
    
    // 处理保存修改
    handleSaveEdit() {
      console.group('=== 保存修改流程开始 ===', new Date().toISOString());
      
      // 1. 初始状态记录
      console.log('1. 初始状态:');
      console.log('   - 当前记录:', JSON.stringify(this.currentRecord, null, 2));
      console.log('   - 当前表单数据:', JSON.stringify(this.editFormData, null, 2));
      
      // 2. 权限检查
      console.log('\n2. 权限检查:');
      const hasEditPerm = this.hasPermission('edit');
      console.log('   - hasPermission(edit):', hasEditPerm);
      
      if (!hasEditPerm) {
        console.error('   - 权限检查失败: 用户没有修改权限');
        ElMessage.error('您没有修改权限，请联系管理员获取相应权限');
        console.groupEnd();
        return;
      }
      console.log('   - 权限检查通过');
      
      // 3. 记录类型处理
      console.log('\n3. 记录类型处理:');
      const recordType = this.currentRecord.category || 'unknown';
      console.log('   - 前端记录类型 recordType:', recordType);
      
      // 3.1 类型映射
      const typeMapping = {
        'system': 'login_info',
        'server': 'server_cred',
        'network': 'net_dev_cred',
        'cluster': 'cluster_cred'
      };
      console.log('   - 类型映射表:', typeMapping);
      
      const finalType = typeMapping[recordType] || recordType;
      console.log('   - 最终发送到后端的类型 finalType:', finalType);
      
      // 4. 获取用户名
      console.log('\n4. 获取用户名:');
      let username = '';
      const sessionUser = sessionStorage.getItem('currentUser');
      console.log('   - sessionUser 存在:', !!sessionUser);
      const localUser = localStorage.getItem('userInfo');
      console.log('   - localUser 存在:', !!localUser);
      
      if (sessionUser) {
        const parsedSession = JSON.parse(sessionUser);
        username = parsedSession.username;
        console.log('   - 从 sessionStorage 获取用户名:', username);
      } else if (localUser) {
        const parsedLocal = JSON.parse(localUser);
        username = parsedLocal.username || parsedLocal.account;
        console.log('   - 从 localStorage 获取用户名:', username);
      }
      console.log('   - 最终用户名:', username);
      
      // 5. 表单验证
      console.log('\n5. 表单验证:');
      this.$refs.editFormRef.validate((valid, invalidFields) => {
        console.log('   - 验证结果:', { valid });
        if (invalidFields && Object.keys(invalidFields).length > 0) {
          console.log('   - 验证失败字段:', JSON.stringify(invalidFields, null, 2));
        }
        
        if (valid) {
          // 6. 表单验证通过，准备API请求
          console.log('\n6. 表单验证通过，准备API请求:');
          this.saveEditLoading = true;
          
          // 6.1 构建请求数据
          const apiRequestData = { ...this.editFormData };
          
          // 为服务器账号密码类型添加磁盘信息
          if (recordType === 'server') {
            apiRequestData.disk_forms = this.diskForms;
          }
          
          const apiRequest = {
            action: 'update',
            type: finalType, // 使用映射后的类型
            data: apiRequestData,
            username: username
          };
          console.log('   - API请求数据:', JSON.stringify(apiRequest, null, 2));
          
          // 7. 发送API请求
          console.log('\n7. 发送API请求:');
          console.log('   - URL: search_api.php');
          console.log('   - 方法: POST');
          
          fetch('search_api.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify(apiRequest)
          })
          .then(response => {
            console.log('\n8. API响应接收:');
            console.log('   - 状态码:', response.status);
            console.log('   - 状态文本:', response.statusText);
            console.log('   - 响应头:', Object.fromEntries(response.headers));
            
            // 8.1 检查响应类型
            const contentType = response.headers.get('content-type');
            console.log('   - Content-Type:', contentType);
            
            if (contentType && contentType.includes('application/json')) {
              console.log('   - 响应是JSON格式，开始解析');
              return response.json().catch(parseError => {
                console.error('   - JSON解析失败:', parseError.stack || parseError);
                return {
                  success: false,
                  message: '服务器返回了无效的JSON格式，请联系管理员检查服务器日志'
                };
              });
            } else {
              console.error('   - 响应不是JSON格式');
              return {
                success: false,
                message: '无法连接到服务器，请确保PHP环境正常运行'
              };
            }
          })
          .then(apiResponse => {
            console.log('\n9. API响应处理:');
            console.log('   - 响应数据:', JSON.stringify(apiResponse, null, 2));
            
            if (apiResponse.success) {
              // 10. 处理成功响应
              console.log('   - 响应成功');
              
              // 10.1 刷新搜索结果
              console.log('   - 刷新搜索结果...');
              this.performSearch(this.currentPage);
              
              // 10.2 显示成功消息
              console.log('   - 显示成功消息');
              this.showSearchMessage('记录修改成功', 'success');
              ElMessage.success('记录修改成功');
              
              // 10.3 关闭对话框
              console.log('   - 关闭对话框...');
              setTimeout(() => {
                this.editRecordVisible = false;
                this.saveEditLoading = false;
              }, 1000);
              
            } else {
              // 11. 处理失败响应
              console.error('   - 响应失败:', apiResponse.message);
              throw new Error(apiResponse.message || '未知错误');
            }
          })
          .catch(error => {
            // 12. 错误处理
            console.error('\n12. 错误处理:');
            console.error('   - 捕获到错误:', error.stack || error);
            
            // 12.1 显示错误消息
            const errorMsg = `记录修改失败：${error.message}`;
            console.log('   - 显示错误消息:', errorMsg);
            this.showSearchMessage(errorMsg, 'danger');
            ElMessage.error(errorMsg);
            
            // 12.2 重置加载状态
            this.saveEditLoading = false;
          })
          .finally(() => {
            // 13. 流程结束
            console.log('\n13. 保存修改流程结束');
            console.groupEnd();
          });
          
        } else {
          // 表单验证失败
          console.error('   - 表单验证失败');
          
          // 显示验证失败字段
          let errorMsgs = [];
          for (const field in invalidFields) {
            if (invalidFields[field]?.length > 0) {
              errorMsgs.push(`${field}: ${invalidFields[field][0].message}`);
            }
          }
          console.log('   - 验证失败字段:', errorMsgs);
          
          ElMessage.warning('表单验证失败，请检查输入');
          console.groupEnd();
          return false;
        }
      });
    },
    
    // 显示搜索消息
    showSearchMessage(text, type = 'info') {
      this.searchMessage = text;
      this.messageType = type;
    },
    
    // 转义HTML字符
    escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    },
    
    // 验证物理机信息
    validatePhysicalMachines(rule, value, callback) {
      if (!Array.isArray(value) || value.length === 0) {
        callback(new Error('至少需要一台物理机'));
        return;
      }
      
      for (let i = 0; i < value.length; i++) {
        const machine = value[i];
        
        // 验证物理机IP
        if (!machine.pmIp) {
          callback(new Error(`第 ${i + 1} 台物理机IP不能为空`));
          return;
        }
        
        if (!this.isValidIp(machine.pmIp)) {
          callback(new Error(`第 ${i + 1} 台物理机IP格式不正确`));
          return;
        }
        
        // 验证物理机用户名
        if (!machine.pmUsername) {
          callback(new Error(`第 ${i + 1} 台物理机用户名不能为空`));
          return;
        }
        
        // 验证物理机密码
        if (!machine.pmPassword) {
          callback(new Error(`第 ${i + 1} 台物理机密码不能为空`));
          return;
        }
        
        // 验证BMC IP格式（如果有）
        if (machine.pmBmcIp && !this.isValidIp(machine.pmBmcIp)) {
          callback(new Error(`第 ${i + 1} 台物理机BMC IP格式不正确`));
          return;
        }
      }
      
      callback();
    },
    
    // IP地址格式验证（允许为空） - Element Plus验证器格式
    isValidIpOptional(rule, value, callback) {
      if (value === null || value === undefined || value === '') {
        callback(); // 验证通过
        return;
      }
      const ipStr = String(value).trim();
      if (ipStr === '') {
        callback(); // 验证通过
        return;
      }
      const ipRegex = /^(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)){3}$/;
      if (ipRegex.test(ipStr)) {
        callback(); // 验证通过
      } else {
        callback(new Error('请输入有效的IP地址')); // 验证失败
      }
    },
    
    // IP地址格式验证（必填） - Element Plus验证器格式
    isValidIp(rule, value, callback) {
      if (value === null || value === undefined || value === '') {
        callback(new Error('请输入IP地址')); // 验证失败
        return;
      }
      const ipStr = String(value).trim();
      if (ipStr === '') {
        callback(new Error('请输入IP地址')); // 验证失败
        return;
      }
      const ipRegex = /^(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)){3}$/;
      if (ipRegex.test(ipStr)) {
        callback(); // 验证通过
      } else {
        callback(new Error('请输入有效的IP地址')); // 验证失败
      }
    },
    
    // 格式化日期时间字段为YYYY-MM-DD HH:MM:SS格式
    formatDateTime(dateTime) {
      if (!dateTime) return '';
      
      const date = new Date(dateTime);
      if (isNaN(date.getTime())) return '';
      
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      const hours = String(date.getHours()).padStart(2, '0');
      const minutes = String(date.getMinutes()).padStart(2, '0');
      const seconds = String(date.getSeconds()).padStart(2, '0');
      
      return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    },
    
    // 检查信息系统登录信息记录是否完整
    isSystemRecordComplete(record) {
      // 需要检查的字段
      const requiredFields = [
        'login_info_system_name', // 系统名称
        'login_info_ip_url',      // IP/URL
        'login_info_login_type',  // 登录方式
        'login_info_username',    // 账号
        'login_info_password',    // 密码
        'login_info_is_active'    // 是否有效
      ];
      
      // 检查每个字段是否为空值或缺失
      for (const field of requiredFields) {
        const value = record[field];
        if (value === null || value === undefined || value === '' || value === '无') {
          return false;
        }
      }
      
      return true;
    },
    
    // 检查信息系统登录信息记录是否有效
    isSystemRecordValid(record) {
      // 检查login_info_is_active字段是否为有效状态
      const isActive = record.login_info_is_active;
      return isActive !== '0' && isActive !== 0;
    },
    
    // 检查服务器账号密码记录是否完整
    isServerRecordComplete(record) {
      // 需要检查的字段
      const requiredFields = [
        'server_cred_network_area',   // 服务器所属网络区域
        'server_cred_server_type',     // 服务器类型
//        'server_cred_host_cluster',    // 宿主机集群
        'server_cred_server_name',     // 服务器名称
        'server_cred_server_ip',       // 服务器IP
        'server_cred_server_port',     // 服务器端口
        'server_cred_server_os',       // 操作系统类型
        'server_cred_login_username',  // 登录用户名
        'server_cred_login_password',  // 登录密码
        'server_cred_edr_installed',   // EDR安装
        'server_cred_ntp_configured',  // NTP配置
        'is_active'                    // 是否有效
      ];
      
      // 检查每个字段是否为空值或缺失
      for (const field of requiredFields) {
        const value = record[field];
        if (value === null || value === undefined || value === '' || value === '无') {
          return false;
        }
      }
      
      // 检查磁盘信息是否存在
      const diskData = record.server_cred_disks || record.disks || record.disk_forms || record.diskForms || 
                      record.server_cred_disk || record.disk || record.server_disks || record.server_disk ||
                      record.disk_info || record.diskInfo || record.disks_info || record.disksInfo;
      
      if (!diskData || (Array.isArray(diskData) && diskData.length === 0)) {
        return false;
      }
      
      return true;
    },
    
    // 检查服务器账号密码记录是否有效
    isServerRecordValid(record) {
      // 检查is_active字段是否为有效状态
      const isActive = record.is_active;
      return isActive !== '0' && isActive !== 0;
    }
  },
  mounted() {
    // 页面挂载后初始化查询功能
    console.log('信息查询视图已挂载');
  }
}
</script>

<style scoped>
/* 视图特定样式 */
.info-query-view {
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
  margin-top: 10px;
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

/* 无数据提示样式 */
.no-data {
  text-align: center;
  padding: 40px 0;
  color: #909399;
}

/* 操作按钮样式 */
.action-buttons {
  display: flex;
  gap: 1px;
}

/* 详细信息对话框样式 */
.detail-dialog-content {
  max-height: 60vh;
  overflow-y: auto;
  padding: 10px 0;
}

.detail-descriptions {
  font-size: 14px;
}

.detail-descriptions :deep(.el-descriptions__label) {
  font-weight: bold;
  background-color: #f7fafc;
  width: 150px;
}

.detail-descriptions :deep(.el-descriptions__content) {
  word-break: break-all;
}

.field-value {
  display: inline-block;
  vertical-align: top;
  max-width: 100%;
}

.value-text {
  display: inline-block;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.field-value:hover .value-text {
  white-space: normal;
  word-break: break-all;
}

/* 响应式设计 */
@media (max-width: 768px) {
  .detail-descriptions :deep(.el-descriptions__label) {
    width: 100px;
  }
}

/* 导出进度样式 */
.export-progress {
  text-align: center;
  padding: 20px 0;
}

/* 物理机表格样式 */
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
  border-color: #409eff;
  outline: none;
}

.physical-machines-table .el-table--stripe .el-table__body tr.el-table__row--striped td {
  background-color: #fafafa;
}

.physical-machines-table .el-table__row:hover > td {
  background-color: #ecf5ff !important;
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

/* 分页组件响应式样式 */
@media (max-width: 768px) {
  .el-pagination {
    font-size: 12px;
  }
  
  .el-pagination__sizes {
    margin-right: 5px;
  }
  
  .el-pagination__sizes .el-select {
    width: 80px;
  }
  
  .el-pagination__jump {
    margin-left: 5px;
  }
}

@media (max-width: 480px) {
  /* 小屏幕设备上的分页组件调整 */
  .el-pagination {
    padding: 0 5px;
  }
  
  .el-pagination__sizes {
    display: none;
  }
  
  .el-pagination__jump {
    display: none;
  }
  
  .el-pagination__layout {
    justify-content: center;
  }
}

/* 状态图标样式 */
.index-cell {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.status-icons {
  display: flex;
  flex-direction: column;
  margin-left: 4px;
  gap: 2px;
}

.status-icon {
  width: 12px;
  height: 12px;
  border-radius: 2px;
  display: inline-block;
  cursor: pointer;
}

.status-icon.incomplete {
  background-color: #FFFF00;
  box-shadow: 0 0 2px rgba(255, 255, 0, 0.8);
}

.status-icon.invalid {
  background-color: #FF0000;
  box-shadow: 0 0 2px rgba(255, 0, 0, 0.8);
}

/* 响应式调整 */
@media (max-width: 768px) {
  .status-icon {
    width: 10px;
    height: 10px;
  }
  
  .status-icons {
    margin-left: 3px;
  }
}
</style>