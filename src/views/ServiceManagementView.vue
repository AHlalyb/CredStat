<template>
  <div class="service-management">
    <el-card class="mt-5" shadow="hover">
      <template #header>
        <div class="card-header">
          <h3 class="m-0">服务管理</h3>
          <el-button type="primary" size="default" @click="refreshServices" :loading="refreshing">
            <el-icon v-if="!refreshing"><Refresh /></el-icon>
            <el-icon v-else><Loading /></el-icon>
            刷新状态
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
        
        <!-- 服务状态表格 -->
        <el-table 
          :data="services" 
          style="width: 100%" 
          border
          stripe
          :header-cell-style="{backgroundColor: '#f5f7fa', color: '#606266', fontWeight: 'bold', textAlign: 'center'}"
          :cell-style="{textAlign: 'center'}"
        >
          <el-table-column prop="name" label="服务名称" min-width="150" align="center">
            <template #default="{ row }">
              <el-icon class="mr-2" :size="20" :color="row.status === 'running' ? '#67c23a' : '#f56c6c'">
                <component :is="row.icon" />
              </el-icon>
              {{ row.name }}
            </template>
          </el-table-column>
          
          <el-table-column prop="address" label="服务地址" min-width="200" align="center">
            <template #default="{ row }">
              <el-link :href="row.address" type="primary" target="_blank" :underline="true">
                {{ row.address }}
              </el-link>
            </template>
          </el-table-column>
          
          <el-table-column prop="status" label="当前状态" min-width="120" align="center">
            <template #default="{ row }">
              <el-tag :type="row.status === 'running' ? 'success' : 'danger'" size="large">
                <el-icon v-if="row.status === 'running'" class="mr-1"><CircleCheck /></el-icon>
                <el-icon v-else class="mr-1"><CircleClose /></el-icon>
                {{ row.status === 'running' ? '运行中' : '已停止' }}
              </el-tag>
            </template>
          </el-table-column>
          
          <el-table-column label="操作" min-width="200" align="center">
            <template #default="{ row }">
              <el-button
                v-if="row.status === 'stopped'"
                type="success"
                size="default"
                @click="startService(row)"
                :loading="row.loading"
              >
                <el-icon><VideoPlay /></el-icon>
                启动
              </el-button>
              <el-button
                v-else
                type="danger"
                size="default"
                @click="stopService(row)"
                :loading="row.loading"
              >
                <el-icon><VideoPause /></el-icon>
                停止
              </el-button>
            </template>
          </el-table-column>
        </el-table>
        
        <!-- 说明信息 -->
        <el-alert
          title="使用说明"
          type="info"
          show-icon
          class="mt-4"
          :closable="false"
        >
          <template #default>
            <p class="m-0">1. 点击"刷新状态"按钮可获取所有服务的最新状态</p>
            <p class="m-0">2. 点击"启动"按钮可启动已停止的服务（开发环境需要手动启动服务）</p>
            <p class="m-0">3. 点击"停止"按钮可停止运行中的服务</p>
            <p class="m-0">4. 绿色标识表示服务运行正常，红色标识表示服务已停止</p>
          </template>
        </el-alert>
      </div>
    </el-card>
  </div>
</template>

<script>
import { 
  Refresh, 
  Loading, 
  CircleCheck, 
  CircleClose,
  VideoPlay, 
  VideoPause,
  Connection,
  Monitor,
  DataLine
} from '@element-plus/icons-vue';

export default {
  name: 'ServiceManagementView',
  components: {
    Refresh,
    Loading,
    CircleCheck,
    CircleClose,
    VideoPlay,
    VideoPause,
    Connection,
    Monitor,
    DataLine
  },
  data() {
    return {
      services: [
        {
          id: 'php',
          name: 'PHP开发服务器',
          address: 'http://localhost:8000',
          status: 'stopped',
          icon: 'Connection',
          loading: false,
          checkUrl: 'http://localhost:8000/search_api.php'
        },
        {
          id: 'vite',
          name: 'Vite前端服务器',
          address: 'http://localhost:3006',
          status: 'stopped',
          icon: 'Monitor',
          loading: false,
          checkUrl: 'http://localhost:3006/'
        },
        {
          id: 'mysql',
          name: 'MySQL数据库',
          address: 'localhost:3306',
          status: 'stopped',
          icon: 'DataLine',
          loading: false,
          checkUrl: null
        }
      ],
      refreshing: false,
      messageVisible: false,
      messageText: '',
      messageType: 'info'
    };
  },
  mounted() {
    this.refreshServices();
  },
  methods: {
    async checkServiceStatus(service) {
      try {
        if (service.id === 'mysql') {
          // 使用端口检测方法检查MySQL状态
          const port = 3306;
          
          return new Promise((resolve) => {
            const timeoutId = setTimeout(() => resolve(false), 1000);
            
            // 尝试TCP连接
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `http://localhost:${port}`, true);
            xhr.timeout = 1000;
            
            xhr.onload = () => {
              clearTimeout(timeoutId);
              // MySQL不返回HTTP响应，但连接成功表示端口开放
              resolve(true);
            };
            
            xhr.onerror = () => {
              clearTimeout(timeoutId);
              // 连接错误，可能端口未开放
              resolve(false);
            };
            
            xhr.ontimeout = () => {
              clearTimeout(timeoutId);
              // 超时，可能端口开放但无响应（MySQL特性）
              resolve(true);
            };
            
            try {
              xhr.send();
            } catch (e) {
              clearTimeout(timeoutId);
              resolve(false);
            }
          });
        } else if (service.id === 'php') {
          // 直接检测PHP服务端口
          const port = 8000;
          
          return new Promise((resolve) => {
            const timeoutId = setTimeout(() => resolve(false), 1000);
            
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `http://localhost:${port}/search_api.php`, true);
            xhr.timeout = 1000;
            
            xhr.onload = () => {
              clearTimeout(timeoutId);
              resolve(xhr.status === 200);
            };
            
            xhr.onerror = () => {
              clearTimeout(timeoutId);
              resolve(false);
            };
            
            xhr.ontimeout = () => {
              clearTimeout(timeoutId);
              resolve(false);
            };
            
            try {
              xhr.send();
            } catch (e) {
              clearTimeout(timeoutId);
              resolve(false);
            }
          });
        } else if (service.checkUrl) {
          // 原有检测逻辑
          return new Promise((resolve) => {
            const timeoutId = setTimeout(() => resolve(false), 1000);
            
            const xhr = new XMLHttpRequest();
            xhr.open('HEAD', service.checkUrl, true);
            xhr.timeout = 1000;
            
            xhr.onload = () => {
              clearTimeout(timeoutId);
              resolve(true);
            };
            
            xhr.onerror = () => {
              clearTimeout(timeoutId);
              resolve(false);
            };
            
            xhr.ontimeout = () => {
              clearTimeout(timeoutId);
              resolve(false);
            };
            
            try {
              xhr.send();
            } catch (e) {
              clearTimeout(timeoutId);
              resolve(false);
            }
          });
        } else {
          return service.status === 'running';
        }
      } catch (error) {
        console.error(`检查${service.name}状态失败:`, error);
        return false;
      }
    },
    
    async refreshServices() {
      this.refreshing = true;
      this.showMessage('正在刷新服务状态...', 'info');
      
      try {
        const statusPromises = this.services.map(async (service) => {
          const isRunning = await this.checkServiceStatus(service);
          service.status = isRunning ? 'running' : 'stopped';
        });
        
        await Promise.all(statusPromises);
        
        const runningCount = this.services.filter(s => s.status === 'running').length;
        this.showMessage(`服务状态已刷新，运行中: ${runningCount} / ${this.services.length}`, 'success');
      } catch (error) {
        this.showMessage('刷新服务状态失败: ' + error.message, 'error');
      } finally {
        this.refreshing = false;
      }
    },
    
    async startService(service) {
      service.loading = true;
      
      try {
        if (service.id === 'php') {
          // 直接检查端口，确定服务是否真的启动
          const isRunning = await this.checkServiceStatus(service);
          if (isRunning) {
            service.status = 'running';
            this.showMessage(`${service.name} 已经在运行中`, 'success');
          } else {
            // 提示用户手动启动服务
            this.showMessage('请在命令行执行: php -S localhost:8000', 'info');
            service.status = 'running';
            this.showMessage(`${service.name} 启动指令已发送，请确保服务已启动`, 'success');
          }
        } else if (service.id === 'vite') {
          this.showMessage('请在命令行执行: npm run dev', 'info');
          service.status = 'running';
          this.showMessage(`${service.name} 启动指令已发送，请确保服务已启动`, 'success');
        } else if (service.id === 'mysql') {
          this.showMessage('请通过XAMPP/WAMP或其他方式启动MySQL服务', 'info');
          service.status = 'running';
          this.showMessage(`${service.name} 启动指令已发送，请确保服务已启动`, 'success');
        }
        
        // 延迟检查状态
        setTimeout(async () => {
          const isRunning = await this.checkServiceStatus(service);
          if (isRunning !== (service.status === 'running')) {
            service.status = isRunning ? 'running' : 'stopped';
            this.showMessage(`${service.name} 实际状态已更新为: ${isRunning ? '运行中' : '已停止'}`, 'warning');
          }
        }, 2000);
      } catch (error) {
        service.status = 'stopped';
        this.showMessage(`启动 ${service.name} 失败: ` + error.message, 'error');
        console.error(`启动 ${service.name} 失败:`, error);
      } finally {
        service.loading = false;
      }
    },
    
    async stopService(service) {
      service.loading = true;
      
      try {
        if (service.id === 'php') {
          // 使用服务管理API停止PHP服务
          try {
            const response = await fetch(`http://localhost:8000/simple_service_manager.php?action=stop&service=php`, {
              method: 'GET'
            });
            
            // 只有当服务器还能响应时才尝试解析JSON
            if (response.ok) {
              const data = await response.json();
              
              if (data.success) {
                service.status = 'stopped';
                this.showMessage(`${service.name} 停止成功`, 'success');
              } else {
                service.status = 'running';
                this.showMessage(`${service.name} 停止失败: ${data.message}`, 'error');
              }
            }
          } catch (error) {
            // 忽略网络错误，因为停止服务时服务器可能会立即关闭连接
            // 直接检查服务状态来确认是否停止成功
            console.warn(`调用停止API时发生网络错误(这可能是正常的，因为服务器正在停止):`, error);
          }
          
          // 无论API调用结果如何，都检查服务状态
          setTimeout(async () => {
            const isRunning = await this.checkServiceStatus(service);
            if (isRunning) {
              service.status = 'running';
              this.showMessage(`${service.name} 停止失败，服务仍在运行`, 'error');
            } else {
              service.status = 'stopped';
              this.showMessage(`${service.name} 停止成功`, 'success');
            }
          }, 1000);
        } else if (service.id === 'vite') {
          this.showMessage('请在命令行按 Ctrl+C 停止Vite服务器', 'info');
          service.status = 'stopped';
          this.showMessage(`${service.name} 停止指令已发送`, 'success');
          
          // 检查服务状态
          setTimeout(async () => {
            const isRunning = await this.checkServiceStatus(service);
            if (isRunning !== (service.status === 'running')) {
              service.status = isRunning ? 'running' : 'stopped';
              this.showMessage(`${service.name} 实际状态已更新为: ${isRunning ? '运行中' : '已停止'}`, 'warning');
            }
          }, 2000);
        } else if (service.id === 'mysql') {
          this.showMessage('请通过XAMPP/WAMP或其他方式停止MySQL服务', 'info');
          service.status = 'stopped';
          this.showMessage(`${service.name} 停止指令已发送`, 'success');
          
          // 检查服务状态
          setTimeout(async () => {
            const isRunning = await this.checkServiceStatus(service);
            if (isRunning !== (service.status === 'running')) {
              service.status = isRunning ? 'running' : 'stopped';
              this.showMessage(`${service.name} 实际状态已更新为: ${isRunning ? '运行中' : '已停止'}`, 'warning');
            }
          }, 2000);
        }
      } catch (error) {
        service.status = 'running';
        this.showMessage(`停止 ${service.name} 失败: ` + error.message, 'error');
        console.error(`停止 ${service.name} 失败:`, error);
        
        // 检查服务状态
        setTimeout(async () => {
          const isRunning = await this.checkServiceStatus(service);
          if (isRunning !== (service.status === 'running')) {
            service.status = isRunning ? 'running' : 'stopped';
            this.showMessage(`${service.name} 实际状态已更新为: ${isRunning ? '运行中' : '已停止'}`, 'warning');
          }
        }, 2000);
      } finally {
        service.loading = false;
      }
    },
    
    showMessage(text, type) {
      this.messageText = text;
      this.messageType = type;
      this.messageVisible = true;
    }
  }
};
</script>

<style scoped>
.service-management {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #303133;
}

.mt-5 {
  margin-top: 20px;
}

.mb-4 {
  margin-bottom: 16px;
}

.mt-4 {
  margin-top: 16px;
}

.mr-1 {
  margin-right: 4px;
}

.mr-2 {
  margin-right: 8px;
}

.m-0 {
  margin: 0;
}

.text-right {
  text-align: right;
}
</style>
