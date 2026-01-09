<template>
  <div class="welcome-view">
    <div class="card">
      <div class="card-header">欢迎使用</div>
      <div class="card-body">
        <h3>CredStat</h3>
        <p>一个安全的管理凭证信息的系统</p>
        <div class="mt-4">
          <h4>功能特点</h4>
          <div class="row mt-3">
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <i class="fas fa-database text-primary fa-3x mb-3"></i>
                  <h5>系统登录信息管理</h5>
                  <p>集中管理各类系统的登录凭证，包括各类系统平台，包含网址、账号、密码等信息</p>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <i class="fas fa-search text-primary fa-3x mb-3"></i>
                  <h5>快速查询</h5>
                  <p>提供高效的搜索功能，支持多种关键词搜索，快速找到所需的系统登录信息</p>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card">
                <div class="card-body">
                  <i class="fas fa-shield-alt text-primary fa-3x mb-3"></i>
                  <h5>安全存储</h5>
                  <p>所有凭证信息都经过安全处理后存储在数据库中，确保信息的安全性</p>
                </div>
              </div>
            </div>
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
</style>
