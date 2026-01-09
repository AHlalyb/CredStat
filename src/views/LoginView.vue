<template>
  <div class="login-container">
    <div class="login-box shadow-lg">
      <div class="login-header text-center mb-4">
        <h1 class="login-title">凭证统计管理系统</h1>
        <p class="login-subtitle">请输入您的账号和密码</p>
      </div>
      
      <form class="login-form" @submit.prevent="handleLogin">
        <!-- 用户名输入框 - 水平排列 -->
        <div class="form-row mb-4">
          <label for="username" class="form-label">用户名</label>
          <div class="input-wrapper">
            <div class="input-group">
              <span class="input-group-text">
                <i class="fas fa-user"></i>
              </span>
              <input 
                type="text" 
                class="form-control" 
                id="username" 
                v-model="loginForm.username" 
                placeholder="请输入用户名" 
                required 
                minlength="3" 
                maxlength="20"
                autocomplete="username"
              >
            </div>
            <div v-if="errors.username" class="invalid-feedback d-block">
              {{ errors.username }}
            </div>
          </div>
        </div>
        
        <!-- 密码输入框 - 水平排列 -->
        <div class="form-row mb-4">
          <label for="password" class="form-label">密码</label>
          <div class="input-wrapper">
            <div class="input-group">
              <span class="input-group-text">
                <i class="fas fa-lock"></i>
              </span>
              <input 
                :type="showPassword ? 'text' : 'password'" 
                class="form-control" 
                id="password" 
                v-model="loginForm.password" 
                placeholder="请输入密码" 
                required 
                minlength="6" 
                maxlength="20"
                autocomplete="current-password"
              >
              <button 
                type="button" 
                class="btn btn-outline-secondary" 
                @click="togglePasswordVisibility"
                aria-label="显示/隐藏密码"
              >
                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
              </button>
            </div>
            <div v-if="errors.password" class="invalid-feedback d-block">
              {{ errors.password }}
            </div>
          </div>
        </div>
        
        <!-- 忘记密码和登录按钮 - 水平布局，左右对齐 -->
        <div class="form-row justify-content-between align-items-center mb-4">
          <div class="forgot-password-wrapper">
            <a href="#" class="forgot-password-link" @click.prevent="handleForgotPassword">忘记密码?</a>
          </div>
          <div class="login-btn-wrapper">
            <button 
              type="submit" 
              class="btn btn-primary login-btn"
              :disabled="isLoading"
            >
              <i v-if="isLoading" class="fas fa-spinner fa-spin me-2"></i>
              {{ isLoading ? '登录中...' : '登录' }}
            </button>
          </div>
        </div>
      </form>
      
      <!-- 全局错误提示 -->
      <div v-if="loginError" class="alert alert-danger mt-4" role="alert">
        <i class="fas fa-exclamation-circle me-1"></i>
        {{ loginError }}
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import { useRouter } from 'vue-router';

// 定义登录响应数据接口
interface LoginResponse {
  success: boolean;
  message: string;
  data?: {
    id: number;
    account: string;
  };
}

// 定义登录请求数据接口
interface LoginRequest {
  username: string;
  password: string;
}

export default defineComponent({
  name: 'LoginView',
  setup() {
    // 获取路由实例
    const router = useRouter();
    return {
      router
    };
  },
  data() {
    return {
      loginForm: {
        username: '',
        password: '',
        rememberMe: false
      },
      errors: {
        username: '',
        password: ''
      },
      loginError: '',
      showPassword: false,
      isLoading: false
    };
  },
  mounted() {
    // 检查本地存储中是否有记住的用户名和密码
    const savedUsername = localStorage.getItem('rememberedUsername');
    const savedPassword = localStorage.getItem('rememberedPassword');
    if (savedUsername && savedPassword) {
      this.loginForm.username = savedUsername;
      this.loginForm.password = savedPassword;
      this.loginForm.rememberMe = true;
    }
  },
  methods: {
    // 显示/隐藏密码
    togglePasswordVisibility() {
      this.showPassword = !this.showPassword;
    },
    
    // 处理忘记密码
    handleForgotPassword() {
      alert('忘记密码功能尚未实现');
      // 这里可以跳转到忘记密码页面或显示忘记密码模态框
    },
    
    // 表单验证
    validateForm() {
      let isValid = true;
      // 重置错误对象，保持正确的类型结构
      this.errors = {
        username: '',
        password: ''
      };
      
      // 用户名验证
      if (!this.loginForm.username.trim()) {
        this.errors.username = '用户名不能为空';
        isValid = false;
      } else if (this.loginForm.username.length < 3) {
        this.errors.username = '用户名长度不能少于3个字符';
        isValid = false;
      } else if (this.loginForm.username.length > 20) {
        this.errors.username = '用户名长度不能超过20个字符';
        isValid = false;
      } else if (!/^[a-zA-Z0-9_-]+$/.test(this.loginForm.username)) {
        this.errors.username = '用户名只能包含字母、数字、下划线和连字符';
        isValid = false;
      }
      
      // 密码验证
      if (!this.loginForm.password) {
        this.errors.password = '密码不能为空';
        isValid = false;
      } else if (this.loginForm.password.length < 6) {
        this.errors.password = '密码长度不能少于6个字符';
        isValid = false;
      } else if (this.loginForm.password.length > 20) {
        this.errors.password = '密码长度不能超过20个字符';
        isValid = false;
      }
      
      return isValid;
    },
    
    // 处理登录
    handleLogin() {
      // 清除之前的错误信息
      this.loginError = '';
      
      // 表单验证
      if (!this.validateForm()) {
        return;
      }
      
      // 显示加载状态
      this.isLoading = true;
      
      // 构建请求数据
      const requestData: LoginRequest = {
        username: this.loginForm.username.trim(),
        password: this.loginForm.password
      };
      
      // 发送登录请求
      fetch('user_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'login',
          ...requestData
        })
      })
      .then(response => {
        // 先检查响应是否为JSON格式
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          return response.json() as Promise<LoginResponse>;
        } else {
          // 如果不是JSON，返回错误响应
          return Promise.resolve<LoginResponse>({
            success: false,
            message: '无法连接到服务器，请确保PHP环境正常运行'
          });
        }
      })
      .then(data => {
        // 隐藏加载状态
        this.isLoading = false;
        
        if (data.success) {
          // 登录成功
          if (this.loginForm.rememberMe) {
            localStorage.setItem('rememberedUsername', this.loginForm.username);
            localStorage.setItem('rememberedPassword', this.loginForm.password);
          } else {
            localStorage.removeItem('rememberedUsername');
            localStorage.removeItem('rememberedPassword');
          }
          
          // 登录状态始终存储在sessionStorage中，关闭浏览器后自动清除
          sessionStorage.setItem('isLoggedIn', 'true');
          sessionStorage.setItem('currentUser', JSON.stringify({
            username: this.loginForm.username
          }));
          
          // 跳转到欢迎页面
          this.router.push('/welcome').then(() => {
            // 路由跳转完成后，确保DOM已经更新
            this.$nextTick(() => {
              // 这里不需要额外操作，因为App.vue的watch会处理
            });
          });
        } else {
          // 登录失败
          this.loginError = data.message || '登录失败，请检查用户名和密码';
        }
      })
      .catch(error => {
        // 隐藏加载状态
        this.isLoading = false;
        
        // 网络错误或其他错误
        this.loginError = '登录过程中发生错误，请稍后重试';
        console.error('登录错误:', error);
      });
    }
  }
});
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.login-box {
  background-color: #ffffff;
  border-radius: 16px;
  padding: 40px;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.login-header {
  margin-bottom: 30px;
}

.login-title {
  font-size: 28px;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 8px;
}

.login-subtitle {
  font-size: 14px;
  color: #718096;
  margin: 0;
}

.login-form {
  margin-top: 20px;
}

.form-row {
  display: flex;
  align-items: center;
  margin-bottom: 16px;
}

.form-label {
  font-weight: 600;
  color: #2d3748;
  margin: 0;
  width: 80px;
  text-align: left;
  flex-shrink: 0;
}

.input-wrapper {
  flex: 1;
  margin-left: 16px;
}

.input-group {
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  overflow: hidden;
  width: 100%;
}

.form-control {
  border: 1px solid #e2e8f0;
  border-left: none;
  border-right: none;
  border-radius: 0;
  padding: 12px 16px;
  font-size: 15px;
  transition: all 0.3s ease;
  width: 100%;
}

.form-control:focus {
  border-color: #667eea;
  box-shadow: none;
  outline: none;
}

.input-group-text {
  background-color: #f7fafc;
  border: 1px solid #e2e8f0;
  border-right: none;
  color: #718096;
  padding: 12px 16px;
}

.btn-outline-secondary {
  border: 1px solid #e2e8f0;
  border-left: none;
  color: #718096;
  padding: 12px 16px;
  transition: all 0.3s ease;
}

.btn-outline-secondary:hover {
  background-color: #f7fafc;
  border-color: #cbd5e0;
  color: #4a5568;
}

.forgot-password-link {
  font-size: 13px;
  color: #667eea;
  text-decoration: none;
  transition: color 0.3s ease;
}

.forgot-password-link:hover {
  color: #5a67d8;
  text-decoration: underline;
}

.login-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 8px;
  padding: 12px 24px;
  font-size: 16px;
  font-weight: 600;
  color: #ffffff;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.login-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
}

.login-btn:active {
  transform: translateY(0);
}

.login-btn:disabled {
  opacity: 0.7;
  transform: none;
  box-shadow: none;
  cursor: not-allowed;
}

.invalid-feedback {
  font-size: 13px;
  margin-top: 4px;
}

.alert-danger {
  font-size: 14px;
  border-radius: 8px;
  border: none;
  background-color: #fed7d7;
  color: #c53030;
}

/* 左右对齐布局 */
.justify-content-between {
  justify-content: space-between;
}

.align-items-center {
  align-items: center;
}

.forgot-password-wrapper {
  margin: 0;
}

.login-btn-wrapper {
  margin: 0;
}

/* 移除记住我相关样式 */
.form-check-label {
  display: none;
}

/* 响应式设计 */
@media (max-width: 576px) {
  .login-box {
    padding: 30px 20px;
    margin: 10px;
  }
  
  .login-title {
    font-size: 24px;
  }
  
  /* 小屏幕下调整为垂直布局 */
  .form-row {
    flex-direction: column;
    align-items: stretch;
  }
  
  .form-label {
    width: auto;
    margin-bottom: 8px;
  }
  
  .input-wrapper {
    margin-left: 0;
  }
  
  .form-control,
  .input-group-text,
  .btn-outline-secondary {
    padding: 10px 14px;
    font-size: 14px;
  }
  
  .login-btn {
    padding: 12px;
    font-size: 15px;
  }
}

/* 动画效果 */
.login-box {
  animation: fadeInUp 0.6s ease;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
