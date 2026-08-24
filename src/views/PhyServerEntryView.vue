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
          <el-row :gutter="20">
            <el-col :xs="24" :sm="12" :md="8">
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
            
            <el-col :xs="24" :sm="12" :md="8">
              <el-form-item label="机柜编号" prop="phyServerCabinet" required>
                <el-input
                  v-model="formData.phyServerCabinet"
                  placeholder="请输入机柜编号"
                ></el-input>
              </el-form-item>
            </el-col>
            
            <el-col :xs="24" :sm="12" :md="8">
              <el-form-item label="U位" prop="phyServerCabinetPosition" required>
                <el-input
                  v-model="formData.phyServerCabinetPosition"
                  placeholder="格式：_U-_U，如1U-4U"
                ></el-input>
              </el-form-item>
            </el-col>
          </el-row>
          
          <!-- 基础信息 -->
          <el-row :gutter="20">
            <el-col :xs="24" :sm="12" :md="8">
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
            
            <el-col :xs="24" :sm="12" :md="8">
              <el-form-item label="型号" prop="phyServerModel" required>
                <el-input
                  v-model="formData.phyServerModel"
                  placeholder="请输入服务器型号"
                ></el-input>
              </el-form-item>
            </el-col>
            
            <el-col :xs="24" :sm="12" :md="8">
              <el-form-item label="SN（序列号）" prop="phyServerSn" required>
                <el-input
                  v-model="formData.phyServerSn"
                  placeholder="请输入服务器序列号"
                ></el-input>
              </el-form-item>
            </el-col>
          </el-row>
          
          <!-- 管理信息 -->
          <el-row :gutter="20">
            <el-col :xs="24" :sm="12" :md="8">
              <el-form-item label="BMC地址" prop="phyServerBmcIp" required>
                <el-input
                  v-model="formData.phyServerBmcIp"
                  placeholder="请输入BMC地址"
                ></el-input>
              </el-form-item>
            </el-col>
            
            <el-col :xs="24" :sm="12" :md="8">
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
            
            <el-col :xs="24" :sm="12" :md="8">
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
          <el-row :gutter="20">
            <el-col :xs="24" :sm="12" :md="8">
              <el-form-item label="采购日期" prop="purchaseDate" required>
                <el-date-picker
                  v-model="formData.purchaseDate"
                  type="date"
                  placeholder="选择采购日期"
                  style="width: 100%"
                ></el-date-picker>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="8">
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
          
          <!-- 硬件信息 -->
          <div class="hardware-section">
            <el-divider content-position="left">硬件信息</el-divider>
            
            <!-- 硬盘信息 -->
            <div class="mb-4">
              <div class="section-header">
                <h5 class="mb-0">硬盘信息</h5>
                <el-button type="primary" @click="addHardDisk" :icon="Plus">添加硬盘</el-button>
              </div>
              
              <div class="mt-3">
                <el-card 
                  v-for="(disk, index) in formData.hardDisks" 
                  :key="index" 
                  class="mb-3" 
                  shadow="never"
                >
                  <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                      <el-row :gutter="20">
                        <el-col :xs="24" :sm="12" :md="6">
                          <el-form-item 
                            :prop="'hardDisks.' + index + '.slot'" 
                            :rules="formRules.hardDiskSlot"
                            label="硬盘槽位"
                            required
                          >
                            <el-input-number 
                              v-model="disk.slot" 
                              controls-position="right" 
                              :min="0"
                              style="width: 100%"
                            ></el-input-number>
                          </el-form-item>
                        </el-col>
                        
                        <el-col :xs="24" :sm="12" :md="6">
                          <el-form-item 
                            :prop="'hardDisks.' + index + '.size'" 
                            :rules="formRules.hardDiskSize"
                            label="硬盘大小"
                            required
                          >
                            <el-input 
                              v-model="disk.size" 
                              placeholder="请输入硬盘大小，如：200GB或200TB"
                              style="width: 100%"
                            ></el-input>
                          </el-form-item>
                        </el-col>
                        
                        <el-col :xs="24" :sm="12" :md="6">
                          <el-form-item 
                            :prop="'hardDisks.' + index + '.raidName'" 
                            :rules="formRules.raidName"
                            label="RAID名称"
                            required
                          >
                            <el-input 
                              v-model="disk.raidName" 
                              placeholder="请输入RAID名称"
                            ></el-input>
                          </el-form-item>
                        </el-col>
                        
                        <el-col :xs="24" :sm="12" :md="6">
                          <el-form-item 
                            :prop="'hardDisks.' + index + '.raidLevel'" 
                            :rules="formRules.raidLevel"
                            label="RAID级别"
                            required
                          >
                            <el-select 
                              v-model="disk.raidLevel" 
                              placeholder="请选择RAID级别"
                              style="width: 100%"
                            >
                              <el-option label="RAID0" value="RAID0"></el-option>
                              <el-option label="RAID1" value="RAID1"></el-option>
                              <el-option label="RAID5" value="RAID5"></el-option>
                              <el-option label="RAID6" value="RAID6"></el-option>
                              <el-option label="RAID10" value="RAID10"></el-option>
                            </el-select>
                          </el-form-item>
                        </el-col>
                        
                        <el-col :span="24">
                          <el-form-item 
                            :prop="'hardDisks.' + index + '.remark'" 
                            label="备注"
                          >
                            <el-input 
                              v-model="disk.remark" 
                              type="textarea"
                              :rows="2"
                              placeholder="请输入备注信息"
                            ></el-input>
                          </el-form-item>
                        </el-col>
                      </el-row>
                    </div>
                    
                    <el-tooltip content="删除" placement="top">
                      <el-button 
                        type="danger" 
                        circle 
                        @click="removeHardDisk(index)"
                      >
                        <el-icon><Delete /></el-icon>
                      </el-button>
                    </el-tooltip>
                  </div>
                </el-card>
              </div>
            </div>
            
            <!-- 电源数量 -->
            <el-row :gutter="20" class="mb-4">
              <el-col :xs="24" :sm="12" :md="8">
                <el-form-item label="电源数量" prop="powerSupplyCount" required>
                  <el-input-number 
                    v-model="formData.powerSupplyCount" 
                    controls-position="right" 
                    :min="1"
                    style="width: 100%"
                  ></el-input-number>
                </el-form-item>
              </el-col>
            </el-row>
            
            <!-- 网卡信息 -->
            <div class="mb-4">
              <div class="section-header">
                <h5 class="mb-0">网卡信息</h5>
                <el-button type="primary" @click="addNic" :icon="Plus">添加网卡</el-button>
              </div>
              
              <div class="mt-3">
                <el-card 
                  v-for="(nic, index) in formData.nics" 
                  :key="index" 
                  class="mb-3" 
                  shadow="never"
                >
                  <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                      <el-row :gutter="20">
                        <el-col :xs="24" :sm="12" :md="6">
                          <el-form-item 
                            :prop="'nics.' + index + '.position'" 
                            :rules="formRules.nicPosition"
                            label="网卡位置"
                            required
                          >
                            <el-select 
                              v-model="nic.position" 
                              placeholder="请选择网卡位置"
                              style="width: 100%"
                            >
                              <el-option label="板载" value="板载"></el-option>
                              <el-option label="PCIE1" value="PCIE1"></el-option>
                              <el-option label="PCIE2" value="PCIE2"></el-option>
                              <el-option label="PCIE3" value="PCIE3"></el-option>
                              <el-option label="PCIE4" value="PCIE4"></el-option>
                              <el-option label="PCIE5" value="PCIE5"></el-option>
                              <el-option label="PCIE6" value="PCIE6"></el-option>
                              <el-option label="PCIE7" value="PCIE7"></el-option>
                              <el-option label="PCIE8" value="PCIE8"></el-option>
                              <el-option label="PCIE9" value="PCIE9"></el-option>
                              <el-option label="PCIE10" value="PCIE10"></el-option>
                            </el-select>
                          </el-form-item>
                        </el-col>
                        
                        <el-col :xs="24" :sm="12" :md="6">
                          <el-form-item 
                            :prop="'nics.' + index + '.portCount'" 
                            :rules="formRules.nicPortCount"
                            label="网口数量"
                            required
                          >
                            <el-select 
                              v-model="nic.portCount" 
                              placeholder="请选择网口数量"
                              style="width: 100%"
                            >
                              <el-option label="2口" value="2口"></el-option>
                              <el-option label="3口" value="3口"></el-option>
                              <el-option label="4口" value="4口"></el-option>
                            </el-select>
                          </el-form-item>
                        </el-col>
                        
                        <el-col :xs="24" :sm="12" :md="6">
                          <el-form-item 
                            :prop="'nics.' + index + '.speed'" 
                            :rules="formRules.nicSpeed"
                            label="速率规格"
                            required
                          >
                            <el-select 
                              v-model="nic.speed" 
                              placeholder="请选择速率规格"
                              style="width: 100%"
                            >
                              <el-option label="千兆" value="千兆"></el-option>
                              <el-option label="万兆" value="万兆"></el-option>
                            </el-select>
                          </el-form-item>
                        </el-col>
                        
                        <el-col :xs="24" :sm="12" :md="6">
                          <el-form-item 
                            :prop="'nics.' + index + '.interface'" 
                            :rules="formRules.nicInterface"
                            label="接口类型"
                            required
                          >
                            <el-select 
                              v-model="nic.interface" 
                              placeholder="请选择接口类型"
                              style="width: 100%"
                            >
                              <el-option label="光口" value="光口"></el-option>
                              <el-option label="电口" value="电口"></el-option>
                            </el-select>
                          </el-form-item>
                        </el-col>
                      </el-row>
                    </div>
                    
                    <el-tooltip content="删除" placement="top">
                      <el-button 
                        type="danger" 
                        circle 
                        @click="removeNic(index)"
                      >
                        <el-icon><Delete /></el-icon>
                      </el-button>
                    </el-tooltip>
                  </div>
                </el-card>
              </div>
            </div>
            
            <!-- HBA卡信息 -->
            <div class="mb-4">
              <div class="section-header">
                <h5 class="mb-0">HBA卡信息</h5>
                <el-button type="primary" @click="toggleHbacardInfo" :icon="Plus">
                  {{ showHbacardInfo ? '隐藏HBA卡信息' : '添加HBA卡信息' }}
                </el-button>
              </div>
              
              <div class="mt-3" v-if="showHbacardInfo">
                <el-card 
                  v-for="(hbacard, index) in formData.hbacards" 
                  :key="index" 
                  class="mb-3" 
                  shadow="never"
                >
                  <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                      <el-row :gutter="20">
                        <el-col :xs="24" :sm="12" :md="12">
                          <el-form-item 
                            :prop="'hbacards.' + index + '.portCount'" 
                            :rules="formRules.hbacardPortCount"
                            label="端口数量"
                          >
                            <el-select 
                              v-model="hbacard.portCount" 
                              placeholder="请选择端口数量"
                              style="width: 100%"
                            >
                              <el-option label="2口" value="2口"></el-option>
                              <el-option label="4口" value="4口"></el-option>
                            </el-select>
                          </el-form-item>
                        </el-col>
                        
                        <el-col :xs="24" :sm="12" :md="12">
                          <el-form-item 
                            :prop="'hbacards.' + index + '.speed'" 
                            :rules="formRules.hbacardSpeed"
                            label="端口速度"
                          >
                            <el-select 
                              v-model="hbacard.speed" 
                              placeholder="请选择端口速度"
                              style="width: 100%"
                            >
                              <el-option label="8Gbps" value="8Gbps"></el-option>
                              <el-option label="16Gbps" value="16Gbps"></el-option>
                              <el-option label="32Gbps" value="32Gbps"></el-option>
                            </el-select>
                          </el-form-item>
                        </el-col>
                      </el-row>
                    </div>
                    
                    <el-tooltip content="删除" placement="top">
                      <el-button 
                        type="danger" 
                        circle 
                        @click="removeHbacard(index)"
                      >
                        <el-icon><Delete /></el-icon>
                      </el-button>
                    </el-tooltip>
                  </div>
                </el-card>
              </div>
            </div>
            
            <!-- 设备连线信息 -->
            <div class="mb-4">
              <div class="section-header">
                <h5 class="mb-0">设备连线信息</h5>
                <el-button type="primary" @click="addConnection" :icon="Plus">添加连线</el-button>
              </div>
              
              <div class="mt-3">
                <el-card 
                  v-for="(connection, index) in formData.connections" 
                  :key="index" 
                  class="mb-3" 
                  shadow="never"
                >
                  <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                      <el-row :gutter="20">
                        <el-col :xs="24" :sm="12" :md="6">
                          <el-form-item 
                            :prop="'connections.' + index + '.interfaceName'" 
                            :rules="formRules.interfaceName"
                            label="接口名称"
                            required
                          >
                            <el-input 
                              v-model="connection.interfaceName" 
                              placeholder="请输入接口名称"
                            ></el-input>
                          </el-form-item>
                        </el-col>
                        
                        <el-col :xs="24" :sm="12" :md="6">
                          <el-form-item 
                            :prop="'connections.' + index + '.cableType'" 
                            :rules="formRules.cableType"
                            label="线缆类型"
                            required
                          >
                            <el-select 
                              v-model="connection.cableType" 
                              placeholder="请选择线缆类型"
                              style="width: 100%"
                            >
                              <el-option label="光纤" value="光纤"></el-option>
                              <el-option label="网线" value="网线"></el-option>
                            </el-select>
                          </el-form-item>
                        </el-col>
                        
                        <el-col :xs="24" :sm="12" :md="6">
                          <el-form-item 
                            :prop="'connections.' + index + '.peerDeviceName'" 
                            :rules="formRules.peerDeviceName"
                            label="对端设备名称"
                            required
                          >
                            <el-input 
                              v-model="connection.peerDeviceName" 
                              placeholder="请输入对端设备名称"
                            ></el-input>
                          </el-form-item>
                        </el-col>
                        
                        <el-col :xs="24" :sm="12" :md="6">
                          <el-form-item 
                            :prop="'connections.' + index + '.peerDeviceInterface'" 
                            :rules="formRules.peerDeviceInterface"
                            label="对端设备接口"
                            required
                          >
                            <el-input 
                              v-model="connection.peerDeviceInterface" 
                              placeholder="请输入对端设备接口"
                            ></el-input>
                          </el-form-item>
                        </el-col>
                      </el-row>
                    </div>
                    
                    <el-tooltip content="删除" placement="top">
                      <el-button 
                        type="danger" 
                        circle 
                        @click="removeConnection(index)"
                      >
                        <el-icon><Delete /></el-icon>
                      </el-button>
                    </el-tooltip>
                  </div>
                </el-card>
              </div>
            </div>
          </div>
          
          <!-- 现场图片上传模块 -->
          <div class="mb-4">
            <el-divider content-position="left">现场图片</el-divider>
            
            <el-form-item label="上传图片">
              <el-upload
                ref="uploadRef"
                class="upload-container"
                :auto-upload="false"
                :file-list="fileList"
                :on-change="handleFileChange"
                :on-remove="handleFileRemove"
                multiple
                list-type="picture-card"
                accept="image/*"
              >
                <el-icon><Plus /></el-icon>
                <div class="el-upload__text">
                  将文件拖到此处，或<em>点击上传</em>
                </div>
                <template #tip>
                  <div class="el-upload__tip">
                    支持上传多张图片，格式：JPG、PNG、GIF
                    <span style="font-weight: bold; color: #E63946; font-size: 1.1em; background-color: #FFF5F5; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-left: 5px;">
                      <el-icon><DocumentCopy /></el-icon> 可ctl+v粘贴图片
                    </span>
                  </div>
                </template>
              </el-upload>
            </el-form-item>
          </div>
          
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
              <el-button type="warning" @click="fillTestData" :icon="EditPen">一键填充测试数据</el-button>
              <el-button type="primary" @click="saveForm" :icon="Check" ref="saveButtonRef" data-testid="save-button">保存</el-button>            </div>
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
import { 
  DocumentAdd, 
  RefreshRight, 
  Check, 
  Upload, 
  Plus, 
  Delete,
  DocumentCopy,
  EditPen
} from '@element-plus/icons-vue';

export default {
  name: 'PhyServerEntryView',
  components: {
    DocumentAdd,
    RefreshRight,
    Check,
    Upload,
    Plus,
    Delete,
    DocumentCopy,
    EditPen
  },
  data() {
    return {
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
        phyServerNotes: '',
        hardDisks: [{
          slot: null,
          size: null,
          raidName: '',
          raidLevel: '',
          remark: ''
        }],
        nics: [{
          position: '',
          portCount: '',
          speed: '',
          interface: ''
        }],
        hbacards: [{
          portCount: '',
          speed: ''
        }],
        connections: [{
          interfaceName: '',
          cableType: '',
          peerDeviceName: '',
          peerDeviceInterface: ''
        }]
      },
      // 用于防止自动填充的key，每次重置表单时更新
      bmcUsernameKey: Date.now(),
      bmcPasswordKey: Date.now(),
      // 机房/站点选项数据
      roomOptions: [],
      // 控制HBA卡信息显示/隐藏
      showHbacardInfo: false,
      // 表单验证规则
      formRules: {
        phyServerRoom: [{ required: true, message: '请选择机房/站点', trigger: 'change' }],
        phyServerCabinet: [{ required: true, message: '请输入机柜编号', trigger: 'blur' }],
        phyServerCabinetPosition: [
          { required: true, message: '请输入U位', trigger: 'blur' }, 
          { pattern: /^\d+U-\d+U$/, message: '请输入格式为"_U-_U"的机柜位置，如"1U-4U"', trigger: 'blur' }
        ],
        phyServerBrand: [{ required: true, message: '请选择厂商', trigger: 'change' }],
        phyServerModel: [{ required: true, message: '请输入服务器型号', trigger: 'blur' }],
        phyServerSn: [{ required: true, message: '请输入服务器序列号', trigger: 'blur' }],
        phyServerBmcIp: [
          { required: true, message: '请输入BMC地址', trigger: 'blur' }, 
          { pattern: /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/, message: '请输入有效的IP地址', trigger: 'blur' }
        ],
        phyServerBmcUsername: [{ required: true, message: '请输入BMC账号', trigger: 'blur' }],
        phyServerBmcPassword: [{ required: true, message: '请输入BMC密码', trigger: 'blur' }],
        purchaseDate: [{ required: true, message: '请选择采购日期', trigger: 'change' }],
        maintenanceDate: [{ required: true, message: '请选择维保截止日期', trigger: 'change' }],
        powerSupplyCount: [{ required: true, message: '请输入电源数量', trigger: 'blur' }],
        // 硬盘信息验证规则
        hardDiskSlot: [{ required: true, message: '请输入硬盘槽位', trigger: 'blur' }],
        hardDiskSize: [
          { required: true, message: '请输入硬盘大小', trigger: 'blur' },
          { pattern: /^\d+(GB|TB)$/, message: '请输入有效的硬盘大小，如：200GB或200TB', trigger: 'blur' }
        ],
        raidName: [{ required: true, message: '请输入RAID名称', trigger: 'blur' }],
        raidLevel: [{ required: true, message: '请选择RAID级别', trigger: 'change' }],
        // 网卡信息验证规则
        nicPosition: [{ required: true, message: '请选择网卡位置', trigger: 'change' }],
        nicPortCount: [{ required: true, message: '请选择网口数量', trigger: 'change' }],
        nicSpeed: [{ required: true, message: '请选择速率规格', trigger: 'change' }],
        nicInterface: [{ required: true, message: '请选择接口类型', trigger: 'change' }],
        // HBA卡信息验证规则（非必填）
        hbacardPortCount: [{ required: false, message: '请选择端口数量', trigger: 'change' }],
        hbacardSpeed: [{ required: false, message: '请选择端口速度', trigger: 'change' }],
        // 连接信息验证规则
        interfaceName: [{ required: true, message: '请输入接口名称', trigger: 'blur' }],
        cableType: [{ required: true, message: '请选择线缆类型', trigger: 'change' }],
        peerDeviceName: [{ required: true, message: '请输入对端设备名称', trigger: 'blur' }],
        peerDeviceInterface: [{ required: true, message: '请输入对端设备接口', trigger: 'blur' }]
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
    // 添加粘贴事件监听
    document.addEventListener('paste', this.handlePaste);
  },
  beforeUnmount() {
    // 移除粘贴事件监听
    document.removeEventListener('paste', this.handlePaste);
    // 释放所有临时URL，避免内存泄漏
    this.fileList.forEach(file => {
      if (file.url && file.url.startsWith('blob:')) {
        URL.revokeObjectURL(file.url);
      }
    });
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
    
    // 添加硬盘
    addHardDisk() {
      this.formData.hardDisks.push({
        slot: null,
        size: null,
        raidName: '',
        raidLevel: '',
        remark: ''
      });
    },
    
    // 删除硬盘
    removeHardDisk(index) {
      if (this.formData.hardDisks.length > 1) {
        this.formData.hardDisks.splice(index, 1);
      } else {
        this.$message.warning('至少保留一个硬盘信息');
      }
    },
    
    // 添加网卡
    addNic() {
      this.formData.nics.push({
        position: '',
        portCount: '',
        speed: '',
        interface: ''
      });
    },
    
    // 删除网卡
    removeNic(index) {
      if (this.formData.nics.length > 1) {
        this.formData.nics.splice(index, 1);
      } else {
        this.$message.warning('至少保留一个网卡信息');
      }
    },
    
    // 切换HBA卡信息显示/隐藏
    toggleHbacardInfo() {
      this.showHbacardInfo = !this.showHbacardInfo;
      // 如果隐藏HBA卡信息，重置HBA卡数据
      if (!this.showHbacardInfo) {
        this.formData.hbacards = [{
          portCount: '',
          speed: ''
        }];
      }
    },
    
    // 添加HBA卡
    addHbacard() {
      this.formData.hbacards.push({
        portCount: '',
        speed: ''
      });
    },
    
    // 删除HBA卡
    removeHbacard(index) {
      // 如果HBA卡信息未显示，允许删除所有HBA卡
      if (!this.showHbacardInfo) {
        this.formData.hbacards.splice(index, 1);
      } else if (this.formData.hbacards.length > 1) {
        this.formData.hbacards.splice(index, 1);
      } else {
        this.$message.warning('至少保留一个HBA卡信息');
      }
    },
    
    // 添加连线
    addConnection() {
      this.formData.connections.push({
        interfaceName: '',
        cableType: '',
        peerDeviceName: '',
        peerDeviceInterface: ''
      });
    },
    
    // 删除连线
    removeConnection(index) {
      if (this.formData.connections.length > 1) {
        this.formData.connections.splice(index, 1);
      } else {
        this.$message.warning('至少保留一个连线信息');
      }
    },
    
    // 一键填充测试数据
    fillTestData() {
      // 生成随机测试数据
      const randomNum = Math.floor(Math.random() * 1000);
      const today = new Date();
      const purchaseDate = new Date(today.getTime() - 365 * 24 * 60 * 60 * 1000);
      const maintenanceDate = new Date(today.getTime() + 365 * 24 * 60 * 60 * 1000);
      
      // 填充基本信息
      this.formData.phyServerRoom = '测试机房-' + randomNum;
      this.formData.phyServerCabinet = 'CAB-' + randomNum;
      this.formData.phyServerCabinetPosition = `${Math.floor(Math.random() * 40) + 1}U-${Math.floor(Math.random() * 10) + 5}U`;
      this.formData.phyServerBrand = ['Dell', 'HP', '联想', 'H3C', '华为'][Math.floor(Math.random() * 5)];
      this.formData.phyServerModel = 'PowerEdge R740-' + randomNum;
      this.formData.phyServerSn = 'SN-' + Math.random().toString(36).substring(2, 15).toUpperCase();
      this.formData.phyServerBmcIp = `192.168.${Math.floor(Math.random() * 255)}.${Math.floor(Math.random() * 255)}`;
      this.formData.phyServerBmcUsername = 'admin';
      this.formData.phyServerBmcPassword = 'Test@123456';
      this.formData.purchaseDate = purchaseDate;
      this.formData.maintenanceDate = maintenanceDate;
      this.formData.powerSupplyCount = Math.floor(Math.random() * 2) + 1;
      this.formData.phyServerNotes = '这是一条测试备注信息，用于测试物理服务器信息录入功能。';
      
      // 填充硬盘信息
      this.formData.hardDisks = [
        {
          slot: 0,
          size: '480GB',
          raidName: 'RAID1',
          raidLevel: 'RAID1',
          remark: '系统盘'
        },
        {
          slot: 1,
          size: '480GB',
          raidName: 'RAID1',
          raidLevel: 'RAID1',
          remark: '系统盘'
        },
        {
          slot: 2,
          size: '8TB',
          raidName: 'RAID5',
          raidLevel: 'RAID5',
          remark: '数据盘'
        }
      ];
      
      // 填充网卡信息
      this.formData.nics = [
        {
          position: '板载',
          portCount: '4口',
          speed: '万兆',
          interface: '光口'
        }
      ];
      
      // 填充HBA卡信息
      this.formData.hbacards = [
        {
          portCount: '2口',
          speed: '16Gbps'
        }
      ];
      
      // 填充设备连线信息
      this.formData.connections = [
        {
          interfaceName: 'eth0',
          cableType: '网线',
          peerDeviceName: '交换机-' + randomNum,
          peerDeviceInterface: 'GigabitEthernet0/0/' + Math.floor(Math.random() * 24)
        },
        {
          interfaceName: 'eth1',
          cableType: '光纤',
          peerDeviceName: '存储设备-' + randomNum,
          peerDeviceInterface: 'FC0/0/' + Math.floor(Math.random() * 8)
        }
      ];
      
      // 显示HBA卡信息
      this.showHbacardInfo = true;
      
      this.$message.success('测试数据填充完成！');
    },
    
    // 重置表单
    resetForm() {
      if (this.$refs.phyServerFormRef) {
        this.$refs.phyServerFormRef.resetFields();
      }
      
      // 重置动态添加的字段为初始状态
      this.formData.hardDisks = [{
        slot: null,
        size: null,
        raidName: '',
        raidLevel: '',
        remark: ''
      }];
      
      this.formData.nics = [{
        position: '',
        portCount: '',
        speed: '',
        interface: ''
      }];
      
      this.formData.hbacards = [{
        portCount: '',
        speed: ''
      }];
      
      this.formData.connections = [{
        interfaceName: '',
        cableType: '',
        peerDeviceName: '',
        peerDeviceInterface: ''
      }];
      
      // 更新key值，强制重新渲染输入框，清除浏览器自动填充
      this.bmcUsernameKey = Date.now();
      this.bmcPasswordKey = Date.now();
      
      // 清空文件列表
      this.fileList = [];
      if (this.$refs.uploadRef) {
        this.$refs.uploadRef.clearFiles();
      }
    },
    
    // 保存表单
    saveForm() {
      // 检查表单实例是否存在
      if (!this.$refs.phyServerFormRef) {
        console.error('表单实例未找到，无法执行验证');
        this.$message.error('表单初始化失败，请刷新页面重试');
        return;
      }
      
      this.$refs.phyServerFormRef.validate(async (valid) => {
        if (valid) {
          // 显示加载状态
          // 使用ref引用获取按钮元素
          let saveBtnElement = this.$refs.saveButtonRef?.$el || null;
          
          // 如果ref不可用，使用备用方案
          if (!saveBtnElement) {
            // 优先使用data-testid选择器（如果有）
            saveBtnElement = document.querySelector('[data-testid="save-button"]');
          }
          
          // 如果没有data-testid，使用文本内容选择器
          if (!saveBtnElement) {
            const primaryButtons = document.querySelectorAll('.el-button[type="primary"]');
            for (let i = 0; i < primaryButtons.length; i++) {
              if (primaryButtons[i].textContent.includes('保存')) {
                saveBtnElement = primaryButtons[i];
                break;
              }
            }
          }
          
          // 兜底方案：获取最后一个主按钮
          if (!saveBtnElement) {
            saveBtnElement = document.querySelector('.el-button[type="primary"]:last-child');
          }
          
          let originalText = '保存';
          if (saveBtnElement) {
            originalText = saveBtnElement.innerHTML;
            saveBtnElement.innerHTML = '<i class="el-icon-loading"></i> 保存中...';
            saveBtnElement.disabled = true;
          } else {
            console.warn('无法找到保存按钮元素，将继续执行保存操作');
          }
          
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
          
          // 准备发送的数据
          const submitData = {
            ...this.formData,
            // 将日期转换为字符串格式
            purchaseDate: this.formData.purchaseDate instanceof Date 
              ? this.formData.purchaseDate.toISOString().split('T')[0] 
              : this.formData.purchaseDate,
            maintenanceDate: this.formData.maintenanceDate instanceof Date 
              ? this.formData.maintenanceDate.toISOString().split('T')[0] 
              : this.formData.maintenanceDate,
            createdBy: currentUser?.username || 'system'
          };
          
          // 处理图片数据
          const formData = new FormData();
          formData.append('formData', JSON.stringify(submitData));
          
          // 添加图片数据
          for (let i = 0; i < this.fileList.length; i++) {
            const file = this.fileList[i].raw;
            if (file) {
              formData.append('images[]', file, file.name);
            }
          }
          
          fetch(apiUrl, {
            method: 'POST',
            headers: {
              'X-Username': currentUser?.username || 'system'
            },
            body: formData
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
            if (saveBtnElement) {
              saveBtnElement.innerHTML = originalText;
              saveBtnElement.disabled = false;
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
            console.error('网络请求失败:', error);
            console.error('错误堆栈:', error.stack);
            
            // 恢复按钮状态
            if (saveBtnElement) {
              saveBtnElement.innerHTML = originalText;
              saveBtnElement.disabled = false;
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
          console.log('表单验证失败');
          this.$message.error('表单填写有误，请检查后重新提交');
          return false;
        }
      });
    },
    
    // 处理文件选择
    handleFileChange(file, fileList) {
      this.fileList = fileList;
    },
    
    // 处理文件移除
    handleFileRemove(file, fileList) {
      // 释放被移除文件的临时URL，避免内存泄漏
      if (file.url && file.url.startsWith('blob:')) {
        URL.revokeObjectURL(file.url);
      }
      this.fileList = fileList;
    },
    
    // 处理粘贴事件
    async handlePaste(e) {
      try {
        // 检查是否包含图片数据
        if (e.clipboardData && e.clipboardData.items) {
          const items = Array.from(e.clipboardData.items);
          for (const item of items) {
            if (item.type.startsWith('image/')) {
              e.preventDefault(); // 阻止默认粘贴行为
              
              const file = item.getAsFile();
              // 创建一个新的File对象用于上传
              const newFile = new File([file], `pasted-image-${Date.now()}.png`, {
                type: file.type
              });
              
              // 创建临时URL用于图片预览
              const imageUrl = URL.createObjectURL(newFile);
              
              // 添加到文件列表，使用Element Plus Upload组件所需的格式
              const uploadFile = {
                uid: Date.now(),
                name: newFile.name,
                raw: newFile,
                status: 'ready',
                url: imageUrl // 添加url属性用于预览
              };
              
              this.fileList.push(uploadFile);
              
              // 显示上传成功反馈
              this.$message.success('图片粘贴成功');
              break;
            }
          }
        }
      } catch (error) {
        console.error('粘贴图片失败:', error);
        this.$message.error('图片粘贴失败: ' + error.message);
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
          phyServerNotes: this.getFieldValue(row, '备注信息') || '',
          // 默认硬件信息
          powerSupplyCount: 1,
          hardDisks: [{
            slot: null,
            size: null,
            raidName: '',
            raidLevel: '',
            remark: ''
          }],
          nics: [{
            position: '',
            portCount: '',
            speed: '',
            interface: ''
          }],
          hbacards: [{
            portCount: '',
            speed: ''
          }],
          connections: [{
            interfaceName: '',
            cableType: '',
            peerDeviceName: '',
            peerDeviceInterface: ''
          }]
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
  padding: 0;
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

.mt-3 {
  margin-top: 15px;
}

.mb-3 {
  margin-bottom: 15px;
}

.ms-3 {
  margin-left: 15px;
}

.d-flex {
  display: flex;
}

.justify-content-between {
  justify-content: space-between;
}

.align-items-start {
  align-items: flex-start;
}

.align-items-center {
  align-items: center;
}

.flex-grow-1 {
  flex-grow: 1;
}

.gap-2 {
  gap: 0.5rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.hardware-section :deep(.el-divider__text) {
  font-size: 16px;
  font-weight: 500;
}

/* 响应式样式 */
@media (max-width: 768px) {
  .phy-server-entry-view {
    padding: 0;
  }
  
  .section-header {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }
  
  .section-header .el-button {
    align-self: flex-start;
  }
}
</style>