<template>
  <div class="welcome-view">
    <!-- 欢迎横幅 -->
    <div class="welcome-banner">
      <i class="fas fa-shield-halved"></i>
      <div class="welcome-banner-text">
        <h2>欢迎使用 CredStat</h2>
        <p>一个安全的管理凭证信息的系统</p>
      </div>
    </div>

    <!-- 功能特点 -->
    <h4 class="feature-title">功能特点</h4>
    <div class="row">
      <div class="col-md-4">
        <div class="card feature-card">
          <div class="card-body">
            <i class="fas fa-database text-primary fa-3x mb-3"></i>
            <h5>系统登录信息管理</h5>
            <p>集中管理各类系统的登录凭证，包括各类系统平台，包含网址、账号、密码等信息</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card feature-card">
          <div class="card-body">
            <i class="fas fa-search text-primary fa-3x mb-3"></i>
            <h5>快速查询</h5>
            <p>提供高效的搜索功能，支持多种关键词搜索，快速找到所需的系统登录信息</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card feature-card">
          <div class="card-body">
            <i class="fas fa-shield-alt text-primary fa-3x mb-3"></i>
            <h5>安全存储</h5>
            <p>所有凭证信息都经过安全处理后存储在数据库中，确保信息的安全性</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'WelcomeView'
}
</script>

<style scoped>
.welcome-view {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0;
}

.welcome-banner {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 28px 32px;
  background: linear-gradient(135deg, #1770e6 0%, #1259b8 100%);
  border-radius: 2px;
  color: #fff;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(23, 112, 230, 0.3);
}

.welcome-banner i {
  font-size: 44px;
  color: #fff;
}

.welcome-banner h2 {
  margin: 0 0 6px;
  font-size: 22px;
  font-weight: 600;
  color: #fff;
}

.welcome-banner p {
  margin: 0;
  font-size: 14px;
  opacity: 0.85;
}

.feature-title {
  margin: 0 0 12px;
  font-size: 15px;
  font-weight: 600;
  color: #333;
}

.feature-card {
  height: 100%;
  border-radius: 2px;
  transition: box-shadow 0.2s, transform 0.2s;
}

.feature-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.feature-card .card-body h5 {
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 8px;
  color: #333;
}

.feature-card .card-body p {
  font-size: 12px;
  color: #606266;
  line-height: 1.7;
  margin: 0;
}
</style>
