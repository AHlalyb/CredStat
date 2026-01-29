<template>
  <div id="app">
    <!-- 只有登录页不显示导航栏和顶部栏 -->
    <template v-if="$route.name !== 'login'">
      <!-- 顶部栏 -->
      <header class="top-bar">
        <div v-if="currentUser" class="user-info-container">
          <el-dropdown trigger="click" @command="handleUserMenuCommand">
            <div class="user-info" @click.stop>
              <span class="username">{{ currentUser.name || currentUser.username }}</span>
              <el-avatar :size="32" :src="currentUser.avatar || defaultAvatar" class="ml-2">
                {{ (currentUser.name || currentUser.username).charAt(0).toUpperCase() }}
              </el-avatar>
            </div>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="profile" :icon="User">个人信息</el-dropdown-item>
                <el-dropdown-item command="avatar" :icon="Camera">修改头像</el-dropdown-item>
                <el-dropdown-item command="password" :icon="Key">修改密码</el-dropdown-item>
                <el-dropdown-item command="logout" :icon="SwitchButton" divided>退出登录</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </header>
      
      <!-- 侧边栏 -->
      <aside class="sidebar">
        <div class="sidebar-header">
          <h2>CredStat</h2>
        </div>
        <ul class="sidebar-menu">
          <li class="menu-item">
            <router-link to="/welcome" class="menu-link">
              <i class="fas fa-home me-2"></i> 首页
            </router-link>
          </li>
        <li class="menu-item">
          <router-link to="/info-query" class="menu-link">
            <i class="fas fa-search me-2"></i> 信息查询
          </router-link>
        </li>
        <li class="menu-item" v-if="hasInfoEntryPermission">
          <a href="#" class="menu-link has-submenu" data-target="infoEntrySubmenu">
            <i class="fas fa-keyboard me-2"></i> 信息录入
          </a>
          <ul class="submenu" id="infoEntrySubmenu">
            <li class="menu-item">
              <router-link to="/system-login-entry" class="menu-link">
                <i class="fas fa-sign-in-alt me-2"></i> 系统登录信息
              </router-link>
            </li>
            <li class="menu-item">
              <router-link to="/server-cred-entry" class="menu-link">
                <i class="fas fa-server me-2"></i> 服务器基本信息
              </router-link>
            </li>
            <li class="menu-item">
              <router-link to="/switch-cred-entry" class="menu-link">
                <i class="fas fa-exchange-alt me-2"></i> 网络设备登录信息
              </router-link>
            </li>
            <li class="menu-item">
              <router-link to="/cluster-entry" class="menu-link">
                <i class="fas fa-server me-2"></i> 宿主机集群
              </router-link>
            </li>
            <li class="menu-item">
              <router-link to="/phy-server-entry" class="menu-link">
                <i class="fas fa-server me-2"></i> 物理服务器信息录入
              </router-link>
            </li>
            <li class="menu-item">
              <router-link to="/domain-cert-entry" class="menu-link">
                <i class="fas fa-certificate me-2"></i> 域名及证书
              </router-link>
            </li>
          </ul>
          </li>
          <li class="menu-item">
          <a href="#" class="menu-link has-submenu" data-target="settingsSubmenu">
            <i class="fas fa-cog me-2"></i> 系统设置
          </a>
          <ul class="submenu" id="settingsSubmenu">
            <li class="menu-item" v-if="hasManagePermission">
              <router-link to="/database-settings" class="menu-link">
                <i class="fas fa-database me-2"></i> 数据库设置
              </router-link>
            </li>
            <li class="menu-item" v-if="hasManagePermission">
              <router-link to="/user-settings" class="menu-link">
                <i class="fas fa-users-cog me-2"></i> 用户设置
              </router-link>
            </li>
            <li class="menu-item">
              <router-link to="/base-object-settings" class="menu-link">
                <i class="fas fa-cubes me-2"></i> 基础对象设置
              </router-link>
            </li>
            <li class="menu-item">
              <router-link to="/service-management" class="menu-link">
                <i class="fas fa-server me-2"></i> 服务管理
              </router-link>
            </li>
          </ul>
          </li>
        </ul>
      </aside>
    </template>

      <!-- 主内容区域 -->
      <main :class="$route.name === 'login' ? 'main-content-full' : 'main-content'">
        <router-view />
      </main>


    
    <!-- 修改头像模态框 -->
    <el-dialog
      v-model="avatarModalVisible"
      title="修改头像"
      width="400px"
      :close-on-click-modal="false"
    >
      <div class="avatar-upload-container">
        <div class="avatar-preview-wrapper">
          <el-avatar :size="150" :src="avatarPreviewUrl" class="avatar-preview"></el-avatar>
        </div>
        
        <el-upload
          class="avatar-uploader"
          :auto-upload="false"
          :before-upload="(file) => file.size < 2 * 1024 * 1024"
          :file-list="avatarFile ? [avatarFile] : []"
          :on-change="handleAvatarChange"
          accept="image/*"
        >
          <el-button type="primary" :icon="Upload" class="mt-3">选择头像</el-button>
          <template #tip>
            <div class="el-upload__tip text-muted">支持 JPG、PNG 格式，文件大小不超过 2MB</div>
          </template>
        </el-upload>
      </div>
      
      <template #footer>
        <div class="dialog-footer text-center">
          <el-button @click="avatarModalVisible = false">取消</el-button>
          <el-button type="primary" @click="uploadAvatar" :loading="avatarUploading">
            <el-icon v-if="avatarUploading"><Loading /></el-icon>
            <el-icon v-else><Check /></el-icon> 上传头像
          </el-button>
        </div>
      </template>
    </el-dialog>
    
    <!-- 修改密码模态框 -->
    <el-dialog
      v-model="passwordModalVisible"
      title="修改密码"
      width="400px"
      :close-on-click-modal="false"
    >
      <el-form label-position="top" label-width="100px">
        <el-form-item label="原密码" required>
          <el-input
            v-model="passwordForm.oldPassword"
            type="password"
            placeholder="请输入原密码"
            show-password
          ></el-input>
        </el-form-item>
        
        <el-form-item label="新密码" required>
          <el-input
            v-model="passwordForm.newPassword"
            type="password"
            placeholder="请输入新密码"
            show-password
          ></el-input>
          <div class="el-form-item__help">密码长度不能少于6个字符</div>
        </el-form-item>
        
        <el-form-item label="确认新密码" required>
          <el-input
            v-model="passwordForm.confirmPassword"
            type="password"
            placeholder="请确认新密码"
            show-password
          ></el-input>
        </el-form-item>
      </el-form>
      
      <template #footer>
        <div class="dialog-footer text-center">
          <el-button @click="passwordModalVisible = false">取消</el-button>
          <el-button type="primary" @click="changePassword" :loading="changingPassword">
            <el-icon v-if="changingPassword"><Loading /></el-icon>
            <el-icon v-else><Check /></el-icon> 确认修改
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script>
// 导入Element Plus图标
import { SwitchButton, User, Camera, Key, Upload, Check, Close } from '@element-plus/icons-vue';

export default {
  name: 'App',
  components: {
    SwitchButton,
    User,
    Camera,
    Key,
    Upload,
    Check,
    Close
  },
  data() {
    return {
      // 个人信息相关模态框
      avatarModalVisible: false,
      passwordModalVisible: false,
      // 当前登录用户信息
      currentUser: null,
      // 默认头像
      defaultAvatar: 'https://cube.elemecdn.com/3/7c/3ea6beec64369c2642b92c6726f1epng.png',
      // 头像上传相关
      avatarFile: null,
      avatarPreview: '',
      avatarUploading: false,
      // 修改密码相关
      passwordForm: {
        oldPassword: '',
        newPassword: '',
        confirmPassword: ''
      },
      passwordFormRules: {
        oldPassword: [{ required: true, message: '请输入原密码', trigger: 'blur' }],
        newPassword: [
          { required: true, message: '请输入新密码', trigger: 'blur' },
          { min: 6, message: '新密码长度不能少于6个字符', trigger: 'blur' }
        ],
        confirmPassword: [
          { required: true, message: '请确认新密码', trigger: 'blur' },
          { validator: this.validateConfirmPassword, trigger: 'blur' }
        ]
      },
      changingPassword: false
    };
  },
  computed: {
    // 头像预览URL
    avatarPreviewUrl() {
      if (this.avatarPreview) {
        return this.avatarPreview;
      }
      return this.currentUser?.avatar || this.defaultAvatar;
    },
    // 检查用户是否拥有管理权限
    hasManagePermission() {
      return this.currentUser?.permissions?.manage === 1 || false;
    },
    // 检查用户是否拥有信息录入权限
    hasInfoEntryPermission() {
      return this.currentUser?.permissions?.add === 1 || false;
    }
  },
  mounted() {
    // 获取当前登录用户信息
    this.getCurrentUser();
    // 初始化菜单交互
    this.initMenuInteraction();
  },
  watch: {
    // 监听路由变化，当从登录页跳转到其他页面时，重新初始化菜单交互和获取用户信息
    $route(to, from) {
      if (from.name === 'login' && to.name !== 'login') {
        // 路由变化后，确保DOM已经更新
        this.$nextTick(() => {
          // 获取当前登录用户信息
          this.getCurrentUser();
          // 重新初始化菜单交互
          this.initMenuInteraction();
        });
      }
    }
  },
  methods: {
    // 获取当前登录用户信息
    async getCurrentUser() {
      try {
        // 检查sessionStorage中的用户信息
        const userInfo = sessionStorage.getItem('currentUser');
        if (userInfo) {
          let currentUser = JSON.parse(userInfo);
          
          // 发送API请求获取完整用户信息，包括credstat_user_name和avatar
          const response = await fetch('user_api.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              action: 'getCurrentUser',
              username: currentUser.username
            })
          });
          
          const data = await response.json();
          if (data.success && data.user) {
            // 更新用户信息，使用数据库返回的name、avatar和权限信息
            currentUser = {
              ...currentUser,
              name: data.user.name || currentUser.username, // 使用数据库返回的name，否则使用username
              id: data.user.id, // 保存用户ID
              avatar: data.user.avatar || currentUser.avatar || this.defaultAvatar, // 使用数据库返回的avatar
              permissions: data.user.permissions // 保存完整的权限信息
            };
            
            // 更新sessionStorage
            sessionStorage.setItem('currentUser', JSON.stringify(currentUser));
          }
          
          this.currentUser = currentUser;
        }
      } catch (error) {
        console.error('获取用户信息失败:', error);
        this.currentUser = null;
      }
    },
    

    
    // 处理用户菜单命令
    handleUserMenuCommand(command) {
      switch (command) {
        case 'profile':
          this.$message.info('个人信息功能开发中');
          break;
        case 'avatar':
          this.avatarModalVisible = true;
          this.avatarPreview = '';
          this.avatarFile = null;
          break;
        case 'password':
          this.passwordModalVisible = true;
          this.resetPasswordForm();
          break;
        case 'logout':
          this.$confirm('确定要退出登录吗？', '退出登录', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
          }).then(() => {
            // 清除sessionStorage中的登录状态
            sessionStorage.removeItem('isLoggedIn');
            sessionStorage.removeItem('currentUser');
            // 重置当前用户信息
            this.currentUser = null;
            // 跳转到登录页面
            this.$router.push('/login');
          }).catch(() => {
            this.$message.info('已取消退出登录');
          });
          break;
        default:
          break;
      }
    },
    
    // 验证确认密码
    validateConfirmPassword(rule, value, callback) {
      if (value !== this.passwordForm.newPassword) {
        callback(new Error('两次输入的密码不一致'));
      } else {
        callback();
      }
    },
    
    // 处理头像文件选择
    handleAvatarChange(file, fileList) {
      this.avatarFile = file;
      // 生成预览
      const reader = new FileReader();
      reader.onload = (e) => {
        this.avatarPreview = e.target.result;
      };
      reader.readAsDataURL(file.raw);
    },
    
    // 上传头像
    async uploadAvatar() {
      if (!this.avatarFile) {
        this.$message.warning('请选择头像文件');
        return;
      }
      
      this.avatarUploading = true;
      
      try {
        // 创建FormData对象
        const formData = new FormData();
        formData.append('avatar', this.avatarFile.raw);
        formData.append('username', this.currentUser.username);
        formData.append('action', 'uploadAvatar');
        
        // 发送文件上传请求
        const response = await fetch('user_api.php', {
          method: 'POST',
          body: formData
        });
        
        const data = await response.json();
        if (data.success) {
          // 更新当前用户头像
          this.currentUser.avatar = data.avatarUrl;
          // 保存到sessionStorage
          sessionStorage.setItem('currentUser', JSON.stringify(this.currentUser));
          
          this.$message.success('头像上传成功');
          this.avatarModalVisible = false;
          this.resetAvatarForm();
        } else {
          this.$message.error(`头像上传失败：${data.message}`);
        }
      } catch (error) {
        this.$message.error('头像上传失败，请稍后重试');
        console.error('头像上传失败:', error);
      } finally {
        this.avatarUploading = false;
      }
    },
    
    // 重置头像表单
    resetAvatarForm() {
      this.avatarFile = null;
      this.avatarPreview = '';
    },
    
    // 修改密码
    async changePassword() {
      // 验证表单
      if (!this.passwordForm.oldPassword || !this.passwordForm.newPassword || !this.passwordForm.confirmPassword) {
        this.$message.warning('请填写完整的密码信息');
        return;
      }
      
      if (this.passwordForm.newPassword !== this.passwordForm.confirmPassword) {
        this.$message.error('两次输入的新密码不一致');
        return;
      }
      
      if (this.passwordForm.newPassword.length < 6) {
        this.$message.error('新密码长度不能少于6个字符');
        return;
      }
      
      this.changingPassword = true;
      
      try {
        // 发送密码修改请求到后端API
        const response = await fetch('user_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'changePassword',
            userId: this.currentUser.id,
            oldPassword: this.passwordForm.oldPassword,
            newPassword: this.passwordForm.newPassword
          })
        });
        
        const data = await response.json();
        
        if (data.success) {
          this.$message.success('密码修改成功');
          this.passwordModalVisible = false;
          this.resetPasswordForm();
        } else {
          this.$message.error(`密码修改失败: ${data.message || '未知错误'}`);
        }
      } catch (error) {
        this.$message.error('密码修改失败，请稍后重试');
        console.error('密码修改失败:', error);
      } finally {
        this.changingPassword = false;
      }
    },
    
    // 重置密码表单
    resetPasswordForm() {
      this.passwordForm = {
        oldPassword: '',
        newPassword: '',
        confirmPassword: ''
      };
    },
    // 打开设置模态框 - 使用Element Plus
    openSettingsModal() {
      this.settingsModalVisible = true;
    },
    
    // 初始化菜单交互
    initMenuInteraction() {
      // 移除旧的事件监听器，避免重复绑定
      const sidebarMenu = document.querySelector('.sidebar-menu');
      
      if (sidebarMenu) {
        // 移除旧的事件监听器
        const oldHandler = sidebarMenu._menuClickHandler;
        if (oldHandler) {
          sidebarMenu.removeEventListener('click', oldHandler);
        }
        
        // 创建新的事件处理函数
        const newHandler = (e) => {
          const link = e.target.closest('.menu-link.has-submenu');
          if (link) {
            e.preventDefault();
            
            // 获取目标子菜单ID
            const targetSubmenuId = link.getAttribute('data-target');
            const targetSubmenu = document.getElementById(targetSubmenuId);
            
            if (targetSubmenu) {
              // 切换子菜单显示状态
              targetSubmenu.classList.toggle('show');
              // 切换菜单项的open类，用于旋转箭头图标
              link.classList.toggle('open');
            }
          }
        };
        
        // 保存事件处理函数，以便后续移除
        sidebarMenu._menuClickHandler = newHandler;
        
        // 添加新的事件监听器
        sidebarMenu.addEventListener('click', newHandler);
      }
    },
    

  }
}

// 将app实例挂载到全局，便于模板中的事件处理
let app = null;
export function setAppInstance(instance) {
  app = instance;
}
</script>

<style scoped>
/* 组件样式将从外部CSS文件引入 */
</style>

<style>
/* 顶部栏样式 */
.top-bar {
  position: fixed;
  top: 0;
  right: 0;
  padding: 10px 20px;
  z-index: 1001;
  display: flex;
  justify-content: flex-end;
}

/* 调整主内容区域的顶部边距，避免被顶部栏遮挡 */
.main-content {
  margin-top: 60px;
}

/* 登录页面全屏样式 */
.main-content-full {
  margin-top: 0;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* 欢迎信息样式 */
.welcome-message {
  font-size: 14px;
  color: #4a5568;
  margin-right: auto;
  margin-left: 20px;
  font-weight: 500;
  display: flex;
  align-items: center;
  height: 100%;
}

/* 登出容器样式 */
.logout-container {
  margin-left: auto;
  margin-right: 20px;
}

/* 登出按钮样式 */
.logout-btn {
  background-color: #f56565;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 6px 12px;
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  transition: background-color 0.2s ease;
}

.logout-btn:hover {
  background-color: #e53e3e;
}

/* 用户信息容器样式 */
.user-info-container {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  border-radius: 20px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.user-info:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.username {
  font-size: 14px;
  color: #4a5568;
  font-weight: 500;
}

.ml-2 {
  margin-left: 8px;
}

/* 头像上传容器样式 */
.avatar-upload-container {
  text-align: center;
}

.avatar-preview-wrapper {
  display: flex;
  justify-content: center;
  margin-bottom: 20px;
}

.avatar-preview {
  border: 2px solid #e2e8f0;
  transition: all 0.3s ease;
}

.avatar-preview:hover {
  transform: scale(1.05);
}

/* 调整顶部栏布局 */
.top-bar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
}
</style>