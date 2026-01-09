/**
 * main.js
 * 应用程序主入口文件
 * 整合所有功能模块
 */

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'

// 引入全局CSS文件
import '../css/main.css'
import '../css/components.css'

// 引入Element Plus和其样式
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'

const app = createApp(App)

app.use(router)
app.use(store)
app.use(ElementPlus) // 使用Element Plus

// 挂载应用
const appInstance = app.mount('#app')

// 将app实例挂载到全局，便于模板中的事件处理
import { setAppInstance } from './App.vue'
setAppInstance(appInstance)