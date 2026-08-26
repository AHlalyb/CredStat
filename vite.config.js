import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, './src')
    }
  },
  server: {
    host: '0.0.0.0',
    port: 3006,
    open: true,
    cors: true,
    // 配置代理，将PHP请求代理到本地PHP服务器
    proxy: {
      // 将所有.php请求代理到本地Apache服务器（假设运行在80端口）
      '/db_config_handler.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/db_config_handler.php'
      },
      '/user_api.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/user_api.php'
      },
      '/test_php.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/test_php.php'
      },
      '/search_api.php': {
        target: 'http://localhost:8000',
        changeOrigin: true
      },
      '/save_server_cred.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/save_server_cred.php'
      },
      '/save_login_info.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/save_login_info.php'
      },
      '/get_clusters.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/get_clusters.php'
      },
      '/save_dev_cred.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/save_dev_cred.php'
      },
      '/save_cluster.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/save_cluster.php'
      },
      '/verify_cluster.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/verify_cluster.php'
      },
      '/save_phy_server.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/save_phy_server.php'
      },
      '/get_main_domains.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/get_main_domains.php'
      },
      '/get_sub_domains.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/get_sub_domains.php'
      },
      '/save_main_domain.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/save_main_domain.php'
      },
      '/save_sub_domain.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/save_sub_domain.php'
      },
      '/delete_main_domain.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/delete_main_domain.php'
      },
      '/delete_sub_domain.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/delete_sub_domain.php'
      },
      '/base_obj_api.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/base_obj_api.php'
      },
      '/server_cred_api.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/server_cred_api.php'
      },
      '/remote_terminal_api.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/remote_terminal_api.php'
      },
      '/network_manage_api.php': {
        target: 'http://localhost',
        changeOrigin: true,
        // 重写路径，确保正确的项目目录
        rewrite: () => '/CredStat/network_manage_api.php'
      },
      '/ws_session_api.php': {
        target: 'http://localhost',
        changeOrigin: true,
        rewrite: (path) => '/CredStat' + path
      }
    }
  },
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    minify: 'terser',
    sourcemap: false,
    rollupOptions: {
      output: {
        manualChunks: {
          vue: ['vue'],
          vuex: ['vuex'],
          'vue-router': ['vue-router']
        }
      }
    }
  }
})