<template>
  <div class="settings-view">
    <el-card class="mt-5" shadow="hover">
      <template #header>
        <div class="card-header">
          <h3 class="m-0">用户设置</h3>
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
        ></el-alert>
        
        <el-row :gutter="[20, 20]" class="mb-3">
          <el-col :span="24" class="d-flex justify-content-between align-items-center">
            <h6>用户管理</h6>
            <el-button type="primary" @click="showUserModal" size="small">
              <el-icon><Plus /></el-icon> 添加用户
            </el-button>
          </el-col>
        </el-row>
        
        <el-table v-if="users.length > 0" :data="users" stripe border @sort-change="handleSortChange">
          <el-table-column prop="id" label="序号" width="80" align="center"></el-table-column>
          <el-table-column prop="account" label="用户账号" width="150" sortable></el-table-column>
          <el-table-column prop="name" label="用户名称" width="150" sortable></el-table-column>
          <el-table-column prop="remark" label="备注"></el-table-column>
          <el-table-column prop="created_at" label="创建时间" width="200" sortable :sort-method="dateSort"></el-table-column>
          <el-table-column prop="updated_at" label="更新时间" width="200" sortable :sort-method="dateSort"></el-table-column>
          <el-table-column label="账号状态" width="150" align="center">
            <template #default="scope">
              <el-switch
                v-model="scope.row.status"
                active-color="#13ce66"
                inactive-color="#ff4949"
                @change="(value) => handleStatusChange(scope.row, value)"
                :loading="userStatusLoading[scope.row.id] || false"
              ></el-switch>
            </template>
          </el-table-column>

          <el-table-column label="操作" width="120" align="center">
            <template #default="scope">
              <el-button type="primary" size="small" circle @click="editUser(scope.row)">
                <el-icon><Edit /></el-icon>
              </el-button>
              <el-button type="danger" size="small" circle @click="deleteUser(scope.row.id)" style="margin-left: 10px">
                <el-icon><Delete /></el-icon>
              </el-button>
            </template>
          </el-table-column>
        </el-table>
        
        <el-empty v-else description="暂无用户数据"></el-empty>
        
        <div v-if="total > 0" class="mt-3 text-center">
          <el-pagination
            v-model:current-page="currentUserPage"
            v-model:page-size="userPageSize"
            :page-sizes="[5, 10, 20]"
            layout="total, sizes, prev, pager, next, jumper"
            :total="total"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
          ></el-pagination>
        </div>
      </div>
    </el-card>
    
    <!-- 用户模态框 -->
    <el-dialog
      v-model="userModalVisible"
      :title="isEditingUser ? '编辑用户' : '添加用户'"
      width="40%"
    >
      <!-- 用户消息提示 -->
      <el-alert
        v-if="userMessageVisible"
        :title="userMessageText"
        :type="userMessageType"
        show-icon
        class="mb-4"
      ></el-alert>
      
      <el-form label-position="top" label-width="100px">
        <el-input type="hidden" v-model="currentUserInfo.id"></el-input>
        
        <!-- 保存按钮区域 - 调整到表单顶部 -->
        <el-form-item>
          <div class="text-right mb-4">
            <el-button type="primary" @click="saveUser" :loading="savingUser">
              <el-icon v-if="savingUser"><Loading /></el-icon>
              <el-icon v-else><Check /></el-icon> 保存
            </el-button>
            <el-button @click="userModalVisible = false" style="margin-left: 10px">取消</el-button>
          </div>
        </el-form-item>
        
        <el-form-item label="用户账号" required>
          <el-input
            v-model="currentUserInfo.account"
            placeholder="请输入用户账号"
          ></el-input>
        </el-form-item>
        <el-form-item label="用户名称" required>
          <el-input
            v-model="currentUserInfo.name"
            placeholder="请输入用户名称"
          ></el-input>
        </el-form-item>
        <el-form-item label="密码" :required="!isEditingUser">
          <el-input
            v-model="currentUserInfo.password"
            type="password"
            placeholder="请输入密码"
            show-password
          ></el-input>
          <div v-if="isEditingUser" class="el-form-item__help">密码为空表示不修改现有密码</div>
        </el-form-item>
        <el-form-item label="备注">
          <el-input
            v-model="currentUserInfo.remark"
            type="textarea"
            :rows="2"
            placeholder="请输入备注信息（可选）"
          ></el-input>
        </el-form-item>
        <el-form-item label="账号状态">
          <el-switch
            v-model="currentUserInfo.status"
            active-color="#13ce66"
            inactive-color="#ff4949"
          ></el-switch>
          <div class="el-form-item__help">{{ currentUserInfo.status ? '启用' : '禁用' }}</div>
        </el-form-item>
      </el-form>
    </el-dialog>
  </div>
</template>

<script>
// 导入Element Plus图标
import { Plus, Edit, Delete, Loading, Check } from '@element-plus/icons-vue';

export default {
  name: 'UserSettingsView',
  components: {
    Plus,
    Edit,
    Delete,
    Loading,
    Check
  },
  data() {
    return {
      // 消息提示
      messageVisible: false,
      messageText: '',
      messageType: 'info',
      // 用户设置
      users: [],
      total: 0,
      currentUserPage: 1,
      userPageSize: 10,
      isEditingUser: false,
      currentUserInfo: {
        id: '',
        account: '',
        name: '',
        password: '',
        remark: '',
        status: true
      },
      userMessageVisible: false,
      userMessageText: '',
      userMessageType: 'info',
      // 模态框显示状态
      userModalVisible: false,
      // 按钮加载状态
      savingUser: false,
      // 用户状态加载状态
      userStatusLoading: {}
    };
  },
  mounted() {
    // 页面加载时加载用户列表
    this.loadUsers();
  },
  watch: {
    // 监听路由变化，每次路由激活时重新加载用户数据
    $route() {
      this.loadUsers();
    }
  },
  methods: {
    // 显示消息提示
    showMessage(message, type = 'info') {
      this.messageVisible = true;
      this.messageText = message;
      this.messageType = type;
      
      // 3秒后自动隐藏消息
      setTimeout(() => {
        this.messageVisible = false;
      }, 3000);
    },
    
    // 显示用户消息提示
    showUserMessage(message, type = 'info') {
      this.userMessageVisible = true;
      this.userMessageText = message;
      this.userMessageType = type;
      
      // 3秒后自动隐藏消息
      setTimeout(() => {
        this.userMessageVisible = false;
      }, 3000);
    },
    
    // 显示用户模态框
    showUserModal(user = null) {
      // 重置消息提示
      this.userMessageVisible = false;
      
      if (user && user.id) {
        // 编辑模式 - 只有当user对象存在且id不为空时才是编辑模式
        this.isEditingUser = true;
        this.currentUserInfo = {
          id: user.id,
          account: user.account,
          name: user.name || '',
          status: user.status || true,
          password: '',
          remark: user.remark || ''
        };
      } else {
        // 添加模式 - 当user为null或id为空时是添加模式
        this.isEditingUser = false;
        this.currentUserInfo = {
          id: '',
          account: '',
          name: '',
          status: true,
          password: '',
          remark: ''
        };
      }
      
      console.log('Modal opened with mode:', this.isEditingUser ? 'edit' : 'add', 'and user:', user);
      
      // 显示模态框
      this.userModalVisible = true;
    },
    
    // 处理账号状态变更
    handleStatusChange(user, newStatus) {
      // 保存原始状态和数据，用于取消或失败时恢复
      const originalStatus = user.status;
      const originalUser = { ...user };
      
      // 立即更新本地状态，让用户看到切换效果
      user.status = newStatus;
      
      // 确认提示
      this.$confirm(`您确定要${newStatus ? '启用' : '禁用'}该用户账号吗？`, '状态变更确认', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      })
      .then(() => {
        // 设置加载状态
        this.userStatusLoading = {
          ...this.userStatusLoading,
          [user.id]: true
        };
        
        // 发送状态变更请求 - 将布尔值转换为整数值
        fetch('user_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'edit',
            userData: {
              id: user.id,
              account: user.account || user.credstat_user_account || '',
              credstat_user_status: newStatus ? 1 : 0
            }
          })
        })
        .then(response => response.json())
        .then(data => {
          // 清除加载状态
          const { [user.id]: _, ...remainingLoadings } = this.userStatusLoading;
          this.userStatusLoading = remainingLoadings;
          
          if (data.success) {
            this.showMessage(`${newStatus ? '启用' : '禁用'}账号成功！`, 'success');
            // 重新加载用户列表，确保所有数据同步
            this.loadUsers();
          } else {
            // 如果更新失败，恢复原状态
            Object.assign(user, originalUser);
            this.showMessage(`更新账号状态失败：${data.message}`, 'danger');
          }
        })
        .catch(error => {
          // 清除加载状态
          const { [user.id]: _, ...remainingLoadings } = this.userStatusLoading;
          this.userStatusLoading = remainingLoadings;
          
          console.error('更新账号状态失败:', error);
          // 如果请求失败，恢复原状态和数据
          Object.assign(user, originalUser);
          this.showMessage('更新账号状态失败，请稍后重试', 'danger');
        });
      })
      .catch(error => {
        // 如果用户取消，恢复原状态和数据
        Object.assign(user, originalUser);
        
        if (error !== 'cancel') {
          console.error('状态变更确认出错:', error);
          this.showMessage('状态变更操作被取消', 'info');
        }
      });
    },
    
    // 保存用户
    saveUser() {
      // 基本验证 - 只验证必要字段，不验证ID
      if (!this.currentUserInfo.account.trim()) {
        this.showUserMessage('用户账号不能为空', 'danger');
        return;
      }
      
      if (!this.currentUserInfo.name.trim()) {
        this.showUserMessage('用户名称不能为空', 'danger');
        return;
      }
      
      if (!this.isEditingUser && !this.currentUserInfo.password) {
        this.showUserMessage('添加用户时密码不能为空', 'danger');
        return;
      }
      
      this.savingUser = true;
      
      const userData = {
        account: this.currentUserInfo.account.trim(),
        name: this.currentUserInfo.name.trim(),
        credstat_user_status: this.currentUserInfo.status ? 1 : 0,
        password: this.currentUserInfo.password,
        remark: this.currentUserInfo.remark.trim()
      };
      
      // 只在编辑用户时添加ID
      if (this.isEditingUser) {
        userData.id = this.currentUserInfo.id;
      }
      
      // 发送请求
      fetch('user_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: this.isEditingUser ? 'edit' : 'add',
          userData: userData
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // 关闭模态框
          this.userModalVisible = false;
          
          // 显示成功消息
          this.showUserMessage(data.message, 'success');
          
          // 重新加载用户列表
          this.loadUsers();
        } else {
          this.showUserMessage(data.message, 'danger');
        }
      })
      .catch(error => {
        console.error('保存用户失败:', error);
        this.showUserMessage('保存用户失败，请稍后重试', 'danger');
      })
      .finally(() => {
        this.savingUser = false;
      });
    },
    
    // 加载用户列表
    loadUsers() {
      fetch('user_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'list',
          page: this.currentUserPage,
          pageSize: this.userPageSize
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // 转换用户数据，确保状态值正确转换为布尔值
          this.users = data.data.map(user => {
            return {
              // 直接使用后端返回的字段
              id: user.id,
              account: user.account,
              name: user.name,
              remark: user.remark,
              created_at: user.created_at,
              updated_at: user.updated_at,
              // 正确转换状态值为布尔类型，确保switch开关能准确反映实际状态
              // 使用parseInt确保数值比较，避免字符串"0"被Boolean()转换为true
              status: parseInt(user.credstat_user_status) === 1
            };
          });
          this.total = data.total;
          console.log('Loaded users with full data:', this.users);
        } else {
          this.showMessage(data.message, 'danger');
        }
      })
      .catch(error => {
        console.error('加载用户列表失败:', error);
        this.showMessage('加载用户列表失败，请稍后重试', 'danger');
      });
    },
    
    // 处理分页大小变化
    handleSizeChange(newSize) {
      this.userPageSize = newSize;
      this.currentUserPage = 1;
      this.loadUsers();
    },
    
    // 处理当前页码变化
    handleCurrentChange(newPage) {
      this.currentUserPage = newPage;
      this.loadUsers();
    },
    
    // 处理排序变化
    handleSortChange({ column, prop, order }) {
      // 这里可以根据排序字段和方向重新加载数据
      // 目前前端实现，实际项目中应该传递排序参数给后端
      const sortedUsers = [...this.users];
      
      if (order) {
        if (prop === 'created_at' || prop === 'updated_at') {
          // 日期排序
          sortedUsers.sort((a, b) => {
            const dateA = new Date(a[prop]).getTime();
            const dateB = new Date(b[prop]).getTime();
            return order === 'ascending' ? dateA - dateB : dateB - dateA;
          });
        } else {
          // 字符串排序
          sortedUsers.sort((a, b) => {
            const valueA = a[prop]?.toString().toLowerCase() || '';
            const valueB = b[prop]?.toString().toLowerCase() || '';
            if (valueA < valueB) return order === 'ascending' ? -1 : 1;
            if (valueA > valueB) return order === 'ascending' ? 1 : -1;
            return 0;
          });
        }
        this.users = sortedUsers;
      } else {
        // 重置排序，重新加载数据
        this.loadUsers();
      }
    },
    
    // 日期排序方法
    dateSort(a, b) {
      const dateA = new Date(a).getTime();
      const dateB = new Date(b).getTime();
      return dateA - dateB;
    },
    
    // 编辑用户
    editUser(user) {
      this.showUserModal(user);
    },
    
    // 删除用户
    deleteUser(userId) {
      this.$confirm('您确定要删除该用户吗？此操作不可逆。', '删除确认', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      })
      .then(() => {
        // 发送请求
        return fetch('user_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'delete',
            userId: userId
          })
        });
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // 重新加载用户列表
          this.loadUsers();
          
          // 显示成功消息
          this.showMessage(data.message, 'success');
        } else {
          this.showMessage(data.message, 'danger');
        }
      })
      .catch(error => {
        if (error !== 'cancel') {
          console.error('删除用户失败:', error);
          this.showMessage('删除用户失败，请稍后重试', 'danger');
        }
      });
    }
  }
}
</script>

<style scoped>
/* 视图特定样式 */
.settings-view {
  padding: 0 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>