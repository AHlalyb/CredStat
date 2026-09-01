import { createRouter, createWebHistory } from 'vue-router'

// 导入视图组件
const LoginView = () => import('../views/LoginView.vue')
const WelcomeView = () => import('../views/WelcomeView.vue')
const InfoQueryView = () => import('../views/InfoQueryView.vue')
const SystemLoginEntryView = () => import('../views/SystemLoginEntryView.vue')
const ServerCredEntryView = () => import('../views/ServerCredEntryView.vue')
const SwitchCredEntryView = () => import('../views/SwitchCredEntryView.vue')
const ClusterEntryView = () => import('../views/ClusterEntryView.vue')
const PhyServerEntryView = () => import('../views/PhyServerEntryView.vue')
const DomainCertEntryView = () => import('../views/DomainCertEntryView.vue')
const DatabaseSettingsView = () => import('../views/DatabaseSettingsView.vue')
const UserSettingsView = () => import('../views/UserSettingsView.vue')
const BaseObjectSettingsView = () => import('../views/BaseObjectSettingsView.vue')
const ServiceManagementView = () => import('../views/ServiceManagementView.vue')
const RemoteTerminalSettingsView = () => import('../views/RemoteTerminalSettingsView.vue')
const JumpTargetView = () => import('../views/JumpTargetView.vue')
const TerminalWindow = () => import('../views/TerminalWindow.vue')

const routes = [
  {
    path: '/',
    name: 'login',
    component: LoginView,
    meta: {
      title: '登录',
      requiresAuth: false
    }
  },
  {
    path: '/welcome',
    name: 'welcome',
    component: WelcomeView,
    meta: {
      title: '首页',
      requiresAuth: true
    }
  },
  {
    path: '/info-query',
    name: 'infoQuery',
    component: InfoQueryView,
    meta: {
      title: '信息查询',
      requiresAuth: true
    }
  },
  {
    path: '/system-login-entry',
    name: 'systemLoginEntry',
    component: SystemLoginEntryView,
    meta: {
      title: '系统登录信息录入',
      requiresAuth: true
    }
  },
  { path: '/server-cred-entry', name: 'serverCredEntry', component: ServerCredEntryView, meta: { title: '服务器基本信息录入', requiresAuth: true } },
  {
    path: '/switch-cred-entry',
    name: 'switchCredEntry',
    component: SwitchCredEntryView,
    meta: {
      title: '网络设备登录信息录入',
      requiresAuth: true
    }
  },
  {
    path: '/cluster-entry',
    name: 'clusterEntry',
    component: ClusterEntryView,
    meta: {
      title: '宿主机集群录入',
      requiresAuth: true
    }
  },
  {
    path: '/phy-server-entry',
    name: 'phyServerEntry',
    component: PhyServerEntryView,
    meta: {
      title: '物理服务器信息录入',
      requiresAuth: true
    }
  },
  {
    path: '/domain-cert-entry',
    name: 'domainCertEntry',
    component: DomainCertEntryView,
    meta: {
      title: '域名及证书管理',
      requiresAuth: true
    }
  },
  {
    path: '/database-settings',
    name: 'databaseSettings',
    component: DatabaseSettingsView,
    meta: {
      title: '数据库设置',
      requiresAuth: true
    }
  },
  {
    path: '/user-settings',
    name: 'userSettings',
    component: UserSettingsView,
    meta: {
      title: '用户设置',
      requiresAuth: true
    }
  },
  {
    path: '/base-object-settings',
    name: 'baseObjectSettings',
    component: BaseObjectSettingsView,
    meta: {
      title: '基础对象设置',
      requiresAuth: true
    }
  },
  {
    path: '/service-management',
    name: 'serviceManagement',
    component: ServiceManagementView,
    meta: {
      title: '服务管理',
      requiresAuth: true
    }
  },
  {
    path: '/remote-terminal-settings',
    name: 'remoteTerminalSettings',
    component: RemoteTerminalSettingsView,
    meta: {
      title: '远程终端设置',
      requiresAuth: true
    }
  },
  {
    path: '/jump-target-settings',
    name: 'jumpTargetSettings',
    component: JumpTargetView,
    meta: {
      title: '跳板目标设置',
      requiresAuth: true
    }
  },
  {
    // 独立窗口的 Web 终端页面（通过 window.open 打开）
    path: '/terminal',
    name: 'terminal',
    component: TerminalWindow,
    meta: {
      title: 'Web 终端',
      requiresAuth: true
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// 路由前置守卫，设置页面标题和登录验证
router.beforeEach((to, from, next) => {
  // 设置页面标题
  if (to.meta.title) {
    document.title = `CredStat - ${to.meta.title}`
  }
  
  // 检查是否需要登录 - 更严格的登录状态检查
  let isLoggedIn = false;
  let currentUser = null;
  
  try {
    // 只检查sessionStorage中的登录状态，关闭浏览器后自动清除
    const loginFlag = sessionStorage.getItem('isLoggedIn');
    // 检查是否有有效的用户信息
    const userInfo = sessionStorage.getItem('currentUser');
    
    if (loginFlag === 'true' && userInfo) {
      currentUser = JSON.parse(userInfo);
      // 只有当登录标志为true且有有效的用户信息时，才认为用户已登录
      isLoggedIn = true;
    }
  } catch (error) {
    // 如果解析用户信息失败，认为用户未登录
    isLoggedIn = false;
    // 清理无效的登录状态
    sessionStorage.removeItem('isLoggedIn');
    sessionStorage.removeItem('currentUser');
  }
  
  // 强制登录检查：确保所有需要认证的路由都被正确保护
  const requiresAuth = to.meta.requiresAuth !== undefined ? to.meta.requiresAuth : true;
  
  if (requiresAuth && !isLoggedIn) {
    // 需要登录但未登录，跳转到登录页面
    next({ name: 'login', replace: true });
  } else if (to.name === 'login' && isLoggedIn && currentUser) {
    // 已登录访问登录页，跳转到首页
    next({ name: 'welcome', replace: true });
  } else if (isLoggedIn && currentUser) {
    // 检查是否需要管理权限
    const requiresManagePermission = ['databaseSettings', 'userSettings', 'remoteTerminalSettings', 'jumpTargetSettings'].includes(to.name);
    if (requiresManagePermission && currentUser.permissions?.manage !== 1) {
      // 没有管理权限，跳转到首页并显示提示
      next({ name: 'welcome', replace: true });
      // 这里需要在全局事件总线或Vuex中触发提示，暂时只做跳转
    } else {
      // 检查是否需要信息录入权限
      const requiresInfoEntryPermission = ['systemLoginEntry', 'serverCredEntry', 'switchCredEntry', 'clusterEntry', 'phyServerEntry', 'domainCertEntry'].includes(to.name);
      if (requiresInfoEntryPermission && currentUser.permissions?.add !== 1) {
        // 没有信息录入权限，跳转到首页并显示提示
        next({ name: 'welcome', replace: true });
      } else {
        // 正常访问
        next();
      }
    }
  } else {
    // 正常访问
    next();
  }
})

export default router