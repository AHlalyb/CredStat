import { createStore } from 'vuex'

// 定义基础模块结构
const baseModule = {
  state: () => ({}),
  mutations: {},
  actions: {},
  getters: {}
}

const store = createStore({
  state: {
    // 全局状态
    loading: false,
    message: {
      text: '',
      type: 'success' // success, info, warning, danger
    },
    currentUser: null
  },
  mutations: {
    // 设置加载状态
    setLoading(state, status) {
      state.loading = status
    },
    // 设置消息
    setMessage(state, { text, type = 'success' }) {
      state.message = { text, type }
    },
    // 清除消息
    clearMessage(state) {
      state.message = { text: '', type: 'success' }
    },
    // 设置当前用户
    setCurrentUser(state, user) {
      state.currentUser = user
    }
  },
  actions: {
    // 显示消息
    showMessage({ commit }, { text, type = 'success', duration = 3000 }) {
      commit('setMessage', { text, type })
      if (type !== 'danger') {
        setTimeout(() => {
          commit('clearMessage')
        }, duration)
      }
    },
    // 处理异步操作
    async handleAsync({ commit }, asyncFn) {
      commit('setLoading', true)
      try {
        const result = await asyncFn()
        commit('setLoading', false)
        return result
      } catch (error) {
        commit('setLoading', false)
        commit('setMessage', { 
          text: error.message || '操作失败', 
          type: 'danger' 
        })
        throw error
      }
    }
  },
  getters: {
    // 获取加载状态
    isLoading: state => state.loading,
    // 获取消息
    message: state => state.message,
    // 获取当前用户
    currentUser: state => state.currentUser
  },
  modules: {
    // 使用基础模块结构，后续可以根据需要扩展
    server: baseModule,
    search: baseModule,
    physicalServer: baseModule,
    cluster: baseModule,
    domainCert: baseModule,
    network: baseModule
  }
})

export default store