<template>
  <div class="jump-target-settings">
    <el-card class="mt-5" shadow="hover">
      <template #header>
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="m-0">跳板目标设置</h3>
          <el-button type="primary" @click="openCreateDialog">
            <el-icon><Plus /></el-icon>
            新增跳板目标
          </el-button>
        </div>
      </template>

      <div class="el-card__body">
        <!-- 说明 -->
        <el-alert type="info" show-icon class="mb-4" :closable="false">
          <template #title>
            <b>跳板目标说明</b>
          </template>
          <template #default>
            <p class="m-0 mb-1">
              中心网关无法直接访问目标设备时，可为其绑定跳板目标。留空跳板目标即表示直连。
            </p>
            <p class="m-0 mb-1">
              <el-tag size="small" type="success">Agent</el-tag>
              在目标网络内的跳板机部署 agent 代理程序（agent.exe），中心网关经其 TCP 隧道直连目标设备。填写 agent 所在主机的 IP 与端口（默认 19878）。
            </p>
            <p class="m-0 mb-1">
              <el-tag size="small">SSH</el-tag>
              <el-tag size="small" type="warning">Telnet</el-tag>
              中心网关 SSH/Telnet 登录跳板机（交换机/堡垒机等），在命令行执行 telnet/ssh 目标IP 跳转，并自动完成目标设备的嵌套登录。填写跳板机的 IP、端口、用户名、密码。
            </p>
            <p class="m-0">
              配置后，在「网络设备登录信息」录入/编辑中选择对应的跳板目标即可。
            </p>
          </template>
        </el-alert>

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

        <!-- 列表 -->
        <el-table :data="list" v-loading="loading" border stripe>
          <el-table-column prop="jump_target_id" label="ID" width="70" align="center"></el-table-column>
          <el-table-column prop="jump_target_name" label="名称" min-width="140" show-overflow-tooltip></el-table-column>
          <el-table-column label="类型" width="100" align="center">
            <template #default="{ row }">
              <el-tag :type="typeTag(row.jump_target_type)" size="small">{{ typeLabel(row.jump_target_type) }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="jump_target_ip" label="IP" width="140"></el-table-column>
          <el-table-column prop="jump_target_port" label="端口" width="90" align="center"></el-table-column>
          <el-table-column prop="jump_target_username" label="用户名" width="120" show-overflow-tooltip></el-table-column>
          <el-table-column prop="jump_target_remark" label="备注" min-width="140" show-overflow-tooltip></el-table-column>
          <el-table-column prop="updated_at" label="更新时间" width="165" align="center"></el-table-column>
          <el-table-column label="操作" width="150" align="center" fixed="right">
            <template #default="{ row }">
              <el-button size="small" type="primary" link @click="openEditDialog(row)">编辑</el-button>
              <el-button size="small" type="danger" link @click="deleteTarget(row)">删除</el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </el-card>

    <!-- 新增/编辑对话框 -->
    <el-dialog
      :title="dialogMode === 'create' ? '新增跳板目标' : '编辑跳板目标'"
      v-model="dialogVisible"
      width="600px"
      :close-on-click-modal="false"
    >
      <el-form ref="formRef" :model="form" :rules="formRules" label-width="100px">
        <el-form-item label="名称" prop="name">
          <el-input v-model="form.name" placeholder="例如：机房核心交换机" maxlength="100"></el-input>
        </el-form-item>

        <el-form-item label="类型" prop="type">
          <el-radio-group v-model="form.type">
            <el-radio value="agent">Agent 隧道</el-radio>
            <el-radio value="ssh">SSH 跳板</el-radio>
            <el-radio value="telnet">Telnet 跳板</el-radio>
          </el-radio-group>
          <div class="el-form-item__help">
            {{ typeHelp(form.type) }}
          </div>
        </el-form-item>

        <el-form-item label="IP 地址" prop="ip">
          <el-input v-model="form.ip" placeholder="跳板机 IP 地址" maxlength="45"></el-input>
        </el-form-item>

        <el-form-item label="端口" prop="port">
          <el-input-number
            v-model="form.port"
            :min="1"
            :max="65535"
            style="width: 100%"
            :placeholder="defaultPortHint"
          ></el-input-number>
          <div class="el-form-item__help">
            {{ defaultPortHint }}
          </div>
        </el-form-item>

        <el-form-item v-if="form.type === 'agent'" label="共享密钥" prop="token">
          <el-input
            v-model="form.token"
            type="password"
            placeholder="Agent 的 --token 共享密钥，留空表示不校验 token"
            show-password
          ></el-input>
          <div class="el-form-item__help">需与 agent.exe 启动参数 --token 一致，留空表示 agent 不校验 token</div>
        </el-form-item>

        <el-form-item v-if="form.type !== 'agent'" label="用户名" prop="jt_username">
          <el-input v-model="form.jt_username" placeholder="登录跳板机的用户名" maxlength="100"></el-input>
        </el-form-item>

        <el-form-item v-if="form.type !== 'agent'" label="密码" prop="password">
          <el-input
            v-model="form.password"
            type="password"
            :placeholder="dialogMode === 'edit' ? '留空表示不修改' : '登录跳板机的密码'"
            show-password
          ></el-input>
        </el-form-item>

        <el-form-item label="备注" prop="remark">
          <el-input v-model="form.remark" placeholder="可选，如部署位置、说明等" maxlength="255"></el-input>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm" :loading="submitting">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { Plus } from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox } from 'element-plus';

export default {
  name: 'JumpTargetView',
  components: {
    Plus
  },
  data() {
    return {
      loading: false,
      list: [],
      dialogVisible: false,
      dialogMode: 'create',
      submitting: false,
      form: {
        id: null,
        name: '',
        type: 'ssh',
        ip: '',
        port: 22,
        jt_username: '',
        password: '',
        token: '',
        remark: ''
      },
      formRules: {
        name: [{ required: true, message: '请输入跳板目标名称', trigger: 'blur' }],
        type: [{ required: true, message: '请选择跳板类型', trigger: 'change' }],
        ip: [{ required: true, message: '请输入 IP 地址', trigger: 'blur' }],
        port: [{ required: true, message: '请输入端口', trigger: 'blur' }]
      },
      messageVisible: false,
      messageText: '',
      messageType: 'info'
    };
  },
  computed: {
    defaultPortHint() {
      if (this.form.type === 'agent') return 'Agent 默认端口 19878';
      if (this.form.type === 'ssh') return 'SSH 默认端口 22';
      return 'Telnet 默认端口 23';
    }
  },
  watch: {
    'form.type'(newType) {
      // 切换类型时按默认端口填充（仅在新建或用户未手动改过时）
      if (this.form.port === null || this.form.port === undefined) {
        return;
      }
      const defaults = { agent: 19878, ssh: 22, telnet: 23 };
      if (this.dialogMode === 'create') {
        this.form.port = defaults[newType] || 22;
      }
    }
  },
  mounted() {
    this.loadList();
  },
  methods: {
    typeLabel(type) {
      return { agent: 'Agent', ssh: 'SSH', telnet: 'Telnet' }[type] || type;
    },
    typeTag(type) {
      if (type === 'agent') return 'success';
      if (type === 'telnet') return 'warning';
      return 'primary';
    },
    typeHelp(type) {
      if (type === 'agent') {
        return '在目标网络内跳板机部署 agent.exe，中心网关经其 TCP 隧道直连目标设备';
      }
      if (type === 'ssh') {
        return '中心网关 SSH 登录跳板机，CLI 执行 telnet/ssh 目标IP 跳转';
      }
      return '中心网关 Telnet 登录跳板机，CLI 执行 telnet/ssh 目标IP 跳转';
    },
    getCurrentUsername() {
      try {
        const raw = sessionStorage.getItem('currentUser');
        if (raw) {
          const user = JSON.parse(raw);
          return user.username || '';
        }
      } catch (error) {
        console.error('获取用户信息失败:', error);
      }
      return '';
    },
    showMessage(text, type = 'info') {
      this.messageText = text;
      this.messageType = type;
      this.messageVisible = true;
    },
    // 加载列表
    async loadList() {
      this.loading = true;
      try {
        const response = await fetch('jump_target_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Username': this.getCurrentUsername()
          },
          body: JSON.stringify({ action: 'list', username: this.getCurrentUsername() })
        });
        const data = await response.json();
        if (data.success) {
          this.list = data.data || [];
        } else {
          this.showMessage(data.message || '加载失败', 'error');
        }
      } catch (error) {
        console.error('加载跳板目标列表失败:', error);
        this.showMessage('加载跳板目标列表失败', 'error');
      } finally {
        this.loading = false;
      }
    },
    // 打开新增对话框
    openCreateDialog() {
      this.dialogMode = 'create';
      this.form = {
        id: null,
        name: '',
        type: 'ssh',
        ip: '',
        port: 22,
        jt_username: '',
        password: '',
        token: '',
        remark: ''
      };
      this.dialogVisible = true;
      this.$nextTick(() => {
        this.$refs.formRef && this.$refs.formRef.clearValidate();
      });
    },
    // 打开编辑对话框
    openEditDialog(row) {
      this.dialogMode = 'edit';
      this.form = {
        id: row.jump_target_id,
        name: row.jump_target_name,
        type: row.jump_target_type,
        ip: row.jump_target_ip,
        port: row.jump_target_port,
        jt_username: row.jump_target_username || '',
        password: '',
        token: '',
        remark: row.jump_target_remark || ''
      };
      this.dialogVisible = true;
      this.$nextTick(() => {
        this.$refs.formRef && this.$refs.formRef.clearValidate();
      });
    },
    // 提交表单
    async submitForm() {
      try {
        await this.$refs.formRef.validate();
      } catch (error) {
        return;
      }
      this.submitting = true;
      try {
        const payload = {
          action: this.dialogMode === 'create' ? 'create' : 'update',
          username: this.getCurrentUsername(),
          name: this.form.name,
          type: this.form.type,
          ip: this.form.ip,
          port: this.form.port,
          jt_username: this.form.jt_username,
          password: this.form.password,
          token: this.form.token,
          remark: this.form.remark
        };
        if (this.dialogMode === 'edit') {
          payload.id = this.form.id;
        }
        const response = await fetch('jump_target_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Username': this.getCurrentUsername()
          },
          body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (data.success) {
          this.dialogVisible = false;
          this.showMessage(data.message || '保存成功', 'success');
          this.loadList();
        } else {
          this.showMessage(data.message || '保存失败', 'error');
        }
      } catch (error) {
        console.error('保存跳板目标失败:', error);
        this.showMessage('保存失败，请稍后重试', 'error');
      } finally {
        this.submitting = false;
      }
    },
    // 删除
    async deleteTarget(row) {
      try {
        await ElMessageBox.confirm(
          `确定要删除跳板目标「${row.jump_target_name}」吗？若已有设备绑定将无法删除。`,
          '删除确认',
          { type: 'warning', confirmButtonText: '删除', cancelButtonText: '取消' }
        );
      } catch (error) {
        return;
      }
      try {
        const response = await fetch('jump_target_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Username': this.getCurrentUsername()
          },
          body: JSON.stringify({ action: 'delete', id: row.jump_target_id, username: this.getCurrentUsername() })
        });
        const data = await response.json();
        if (data.success) {
          this.showMessage(data.message || '删除成功', 'success');
          this.loadList();
        } else {
          this.showMessage(data.message || '删除失败', 'error');
        }
      } catch (error) {
        console.error('删除跳板目标失败:', error);
        this.showMessage('删除失败，请稍后重试', 'error');
      }
    }
  }
};
</script>

<style scoped>
.jump-target-settings :deep(.el-form-item__help) {
  font-size: 12px;
  color: #909399;
}
</style>
