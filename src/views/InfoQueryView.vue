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
              <el-dropdown>
                <el-button
                  type="success"
                  :disabled="!canExport"
                  size="small"
                >
                  <el-icon><Download /></el-icon> 导出结果
                  <el-icon class="el-icon--right"><ArrowDown /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item @click="exportResults('html')">
                      <el-icon><DocumentCopy /></el-icon> 导出HTML
                    </el-dropdown-item>
                    <el-dropdown-item @click="exportResults('excel')">
                      <el-icon><Document /></el-icon> 导出CSV
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
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
            <el-table-column type="index" label="序号" width="55" align="center"></el-table-column>
            
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
            <el-table-column label="操作" width="160" align="center" fixed="right">
              <template #default="scope">
                <div class="action-buttons">
                  <!-- 编辑按钮 -->
                  <el-tooltip content="编辑" placement="top">
                    <el-button
                      type="primary"
                      circle
                      size="small"
                      @click="editRecord(scope.row)"
                      :disabled="!hasPermission('edit')"
                    >
                      <el-icon><Edit /></el-icon>
                    </el-button>
                  </el-tooltip>
                  <!-- 删除按钮 -->
                  <el-tooltip content="删除" placement="top">
                    <el-button
                      type="danger"
                      circle
                      size="small"
                      @click="deleteRecord(scope.row)"
                      :disabled="!hasPermission('delete')"
                    >
                      <el-icon><Delete /></el-icon>
                    </el-button>
                  </el-tooltip>
                  <!-- 查询详情按钮 -->
                  <el-tooltip content="查询详情" placement="top">
                    <el-button
                      type="primary"
                      circle
                      size="small"
                      @click="handleDetailQuery(scope.row)"
                      :disabled="!hasPermission('query')"
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
              @current-change="handleCurrentChange"
              :current-page="currentPage"
              :page-size="pageSize"
              layout="total, prev, pager, next, jumper"
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
              <el-form-item label="服务器所属网络区域" prop="networkArea">
                <el-select
                  v-model="editFormData.networkArea"
                  placeholder="请选择网络区域"
                  style="width: 100%"
                >
                  <el-option label="内网" value="内网"></el-option>
                  <el-option label="DMZ" value="DMZ"></el-option>
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="8" :lg="8">
              <el-form-item label="服务器类型" prop="serverType">
                <el-select
                  v-model="editFormData.serverType"
                  placeholder="请选择服务器类型"
                  style="width: 100%"
                >
                  <el-option label="物理机" value="物理机"></el-option>
                  <el-option label="虚拟机" value="虚拟机"></el-option>
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="8" :lg="8">
              <el-form-item label="宿主机集群" prop="hostCluster">
                <el-select
                  v-model="editFormData.hostCluster"
                  placeholder="请选择宿主机集群"
                  filterable
                  clearable
                  style="width: 100%"
                >
                  <!-- 这里可以根据需要动态加载集群列表 -->
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>
          
          <!-- 第二行：服务器名称、服务器IP -->
          <el-row :gutter="[20, 20]">
            <el-col :xs="24" :sm="12" :md="12" :lg="12">
              <el-form-item label="服务器名称" prop="name" required>
                <el-input
                  v-model="editFormData.name"
                  placeholder="请输入服务器名称"
                ></el-input>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="12" :lg="12">
              <el-form-item label="服务器IP" prop="ip" required>
                <el-input
                  v-model="editFormData.ip"
                  placeholder="请输入服务器IP地址"
                ></el-input>
              </el-form-item>
            </el-col>
          </el-row>
          
          <!-- 第三行：操作系统类型、端口号 -->
          <el-row :gutter="[20, 20]">
            <el-col :xs="24" :sm="12" :md="12" :lg="12">
              <el-form-item label="操作系统类型" prop="os" required>
                <el-select
                  v-model="editFormData.os"
                  placeholder="请选择操作系统"
                  style="width: 100%"
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
            <el-col :xs="24" :sm="12" :md="12" :lg="12">
              <el-form-item label="端口号" prop="port" required>
                <el-input-number
                  v-model="editFormData.port"
                  placeholder="请输入端口号"
                  style="width: 100%"
                ></el-input-number>
              </el-form-item>
            </el-col>
          </el-row>
          
          <!-- 第四行：登录用户名、密码 -->
          <el-row :gutter="[20, 20]">
            <el-col :xs="24" :sm="12" :md="12" :lg="12">
              <el-form-item label="登录用户名" prop="loginUsername" required>
                <el-input
                  v-model="editFormData.loginUsername"
                  placeholder="请输入登录用户名"
                  autocomplete="new-username"
                  :name="'random-username-' + Math.random().toString(36).substring(2, 15)"
                  readonly
                  @focus="$event.target.removeAttribute('readonly')"
                  :key="clusterUsernameKey"
                ></el-input>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="12" :lg="12">
              <el-form-item label="密码" prop="loginPassword" required>
                <el-input
                  v-model="editFormData.loginPassword"
                  type="password"
                  placeholder="请输入登录密码"
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
          
          <!-- 第五行：服务器备注 -->
          <el-row :gutter="[20, 20]">
            <el-col :xs="24" :sm="24" :md="24" :lg="24">
              <el-form-item label="服务器备注" prop="notes">
                <el-input
                  v-model="editFormData.notes"
                  placeholder="请输入服务器备注"
                  type="textarea"
                  rows="3"
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
          <!-- 服务器账号密码详情 -->
          <div v-if="currentRecord.category === 'server'" class="server-detail">
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
              <el-descriptions-item label="服务器IP">
                <span>{{ currentRecord.server_cred_server_ip || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="操作系统类型">
                <span>{{ currentRecord.server_cred_server_os || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第四行 -->
              <el-descriptions-item label="端口号">
                <span>{{ currentRecord.server_cred_server_port || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="登录用户名">
                <span>{{ currentRecord.server_cred_login_username || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第五行 -->
              <el-descriptions-item label="密码">
                <span>{{ currentRecord.server_cred_login_password || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="备注信息">
                <span>{{ currentRecord.server_cred_notes || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第六行 -->
              <el-descriptions-item label="创建时间">
                <span>{{ currentRecord.created_at || '无' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="修改时间">
                <span>{{ currentRecord.updated_at || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第七行 -->
              <el-descriptions-item label="创建人">
                <span>{{ currentRecord.server_cred_created_by || '无' }}</span>
              </el-descriptions-item>
            </el-descriptions>
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
                <span>{{ currentRecord.created_at || '无' }}</span>
              </el-descriptions-item>
              
              <!-- 第十行 -->
              <el-descriptions-item label="更新日期">
                <span>{{ currentRecord.updated_at || '无' }}</span>
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
        remark: '',
        // 服务器账号密码
        name: '',
        ip: '',
        port: '',
        os: '',
        loginUsername: '',
        loginPassword: '',
        networkArea: '',
        serverType: '',
        hostCluster: '',
        notes: '',
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
        remark: [
          { max: 255, message: '备注不能超过255个字符', trigger: 'blur' }
        ],
        
        // 服务器账号密码验证规则
        name: [
          { required: true, message: '请输入服务器名称', trigger: 'blur' },
          { max: 100, message: '服务器名称不能超过100个字符', trigger: 'blur' }
        ],
        ip: [
          { required: true, message: '请输入IP', trigger: 'blur' },
          { validator: this.isValidIpOptional, message: '请输入有效的IP地址', trigger: 'blur' }
        ],
        port: [
          { required: true, message: '请输入服务器端口', trigger: 'blur' },
          { type: 'number', min: 1, max: 65535, message: '端口号必须在1-65535之间', trigger: 'blur' }
        ],
        os: [
          { required: true, message: '请输入服务器操作系统', trigger: 'blur' },
          { max: 50, message: '服务器操作系统不能超过50个字符', trigger: 'blur' }
        ],
        loginUsername: [
          { required: true, message: '请输入登录用户名', trigger: 'blur' },
          { max: 50, message: '登录用户名不能超过50个字符', trigger: 'blur' }
        ],
        loginPassword: [
          { required: true, message: '请输入登录密码', trigger: 'blur' },
          { max: 100, message: '登录密码不能超过100个字符', trigger: 'blur' }
        ],
        networkArea: [
          { max: 50, message: '网络区域不能超过50个字符', trigger: 'blur' }
        ],
        serverType: [
          { max: 50, message: '服务器类型不能超过50个字符', trigger: 'blur' }
        ],
        hostCluster: [
          { max: 100, message: '宿主机集群不能超过100个字符', trigger: 'blur' }
        ],
        notes: [
          { max: 255, message: '服务器备注不能超过255个字符', trigger: 'blur' }
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
        'pm_count': '集群宿主机数量'
      }
    };
  },
  created() {
    // 从localStorage获取用户权限
    console.log('Checking localStorage for user info...');
    
    // Check specific keys that might contain user info
    const potentialKeys = ['userInfo', 'currentUser', 'loginUser', 'user'];
    let foundUserInfo = null;
    let foundKey = null;
    
    // Try to find user info in any of the potential keys
    for (const key of potentialKeys) {
      const value = localStorage.getItem(key);
      if (value) {
        try {
          const parsed = JSON.parse(value);
          console.log(`Found data in localStorage.${key}:`, parsed);
          foundUserInfo = parsed;
          foundKey = key;
          break; // Use the first found user info
        } catch (e) {
          console.log(`Found non-JSON data in localStorage.${key}:`, value);
        }
      }
    }
    
    // Debug: if no user info found, create a test user with full permissions
    if (!foundUserInfo) {
      console.log('No user info found in localStorage, creating test permissions');
      // Create test permissions with full access for debugging
      this.userPermissions = {
        add: 1,
        delete: 1,
        edit: 1,
        query: 1
      };
      console.log('Set test permissions with full access');
    } else {
      console.log(`Using found user info from key '${foundKey}':`, foundUserInfo);
      
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
    // 检查是否有任何操作权限
    hasAnyPermission() {
      // Permission values are already normalized to numbers during initialization
      return Object.values(this.userPermissions).some(perm => perm === 1);
    },
    // 导出结果
    exportResults(format) {
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
          username: username
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
              if (item.server_cred_server_ip) {
                return {
                  ...item,
                  ip: item.server_cred_server_ip
                };
              }
              return item;
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
            { prop: 'ip_url', label: 'IP/URL', minWidth: 150 },
            { prop: 'type', label: '登录方式', minWidth: 120 },
            { prop: 'username', label: '用户名', minWidth: 120 },
            { prop: 'password', label: '密码', minWidth: 120 }
          ];
          break;
        case 'server':
          // 服务器账号密码 - 仅显示指定字段
          columns = [
            { prop: 'name', label: '服务器名称', minWidth: 150 },
            { prop: 'ip', label: 'IP', minWidth: 120 },
            { prop: 'port', label: '服务器端口', minWidth: 110 },
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
            { prop: 'cluster_password', label: '集群密码', minWidth: 120 },
            { prop: 'pm_count', label: '集群宿主机数量', minWidth: 120 }
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
            type: record.type || record.login_info_type || '',
            username: record.username || record.login_info_username || '',
            password: record.password || record.login_info_password || '',
            remark: record.remark || record.login_info_remark || ''
          };
          break;
        case 'server':
          // 服务器账号密码
          this.editFormData = {
            server_cred_id: record.id || record.server_cred_id || '',
            name: record.name || record.server_cred_name || '',
            ip: record.ip || record.server_cred_server_ip || '',
            port: record.port || record.server_cred_server_port || '',
            os: record.os || record.server_cred_os || '',
            loginUsername: record.username || record.server_cred_username || '',
            loginPassword: record.password || record.server_cred_password || '',
            networkArea: record.network_area || record.server_cred_network_area || '',
            serverType: record.server_type || record.server_cred_server_type || '',
            hostCluster: record.host_cluster || record.server_cred_host_cluster || '',
            description: record.description || record.server_cred_description || '',
            notes: record.notes || record.server_cred_notes || ''
          };
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
          const apiRequest = {
            action: 'update',
            type: finalType, // 使用映射后的类型
            data: this.editFormData,
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
</style>