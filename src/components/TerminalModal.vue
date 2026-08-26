<template>
  <!-- ========== 内嵌全屏模式：/terminal 独立页面使用（无弹窗外壳） ========== -->
  <div v-if="embedded" class="terminal-embedded">
    <!-- 顶部工具栏 -->
    <div class="terminal-header">
      <span class="dot" :class="statusClass"></span>
      <span class="term-title">Web 终端</span>
      <span class="page-status" :class="statusClass">{{ statusText }}</span>
      <div class="spacer"></div>
      <el-button size="small" :disabled="!activeSession || !activeSession.connected" @click="handleCopy">复制</el-button>
      <el-button size="small" :disabled="!activeSession || !activeSession.connected" @click="handlePaste">粘贴</el-button>
      <el-button
        size="small"
        type="danger"
        :disabled="!activeSession || (!activeSession.connected && !activeSession.connecting)"
        @click="closeActiveSession"
      >
        {{ activeSession && activeSession.connected ? '断开连接' : '关闭会话' }}
      </el-button>
    </div>

    <!-- 会话 Tab 栏 -->
    <div v-if="sessions.length > 0" class="session-tabs">
      <div
        v-for="s in sessions"
        :key="s.key"
        class="session-tab"
        :class="{ active: activeTab === s.key }"
        @click="switchTab(s.key)"
      >
        <span class="tab-dot" :class="'is-' + s.status"></span>
        <span class="tab-title" :title="s.deviceIp">{{ s.title }}</span>
        <span class="tab-close" title="关闭会话" @click.stop="removeSession(s.key)">×</span>
      </div>
    </div>

    <!-- 终端区域（所有会话容器常驻，切换仅控制显隐） -->
    <div class="terminal-body">
      <div
        v-for="s in sessions"
        :key="s.key"
        v-show="activeTab === s.key"
        :ref="(el) => setTerminalRef(el, s.key)"
        class="terminal-wrap"
      ></div>
      <div v-if="sessions.length === 0" class="terminal-empty">
        暂无会话，请通过设备列表「远程 → SSH / Telnet」添加多个终端会话
      </div>
    </div>
  </div>

  <!-- ========== 弹窗模式：设备列表页内嵌弹窗使用 ========== -->
  <el-dialog
    v-else
    :model-value="dialogVisible"
    title="Web 终端"
    width="55%"
    top="10vh"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    :modal="false"
    append-to-body
    destroy-on-close
    class="terminal-dialog"
    @close="handleClose"
    @closed="handleClosed"
    @update:model-value="handleDialogVisibleChange"
  >
    <!-- 会话 Tab 栏 -->
    <div v-if="sessions.length > 0" class="session-tabs">
      <div
        v-for="s in sessions"
        :key="s.key"
        class="session-tab"
        :class="{ active: activeTab === s.key }"
        @click="switchTab(s.key)"
      >
        <span class="tab-dot" :class="'is-' + s.status"></span>
        <span class="tab-title" :title="s.deviceIp">{{ s.title }}</span>
        <span class="tab-close" title="关闭会话" @click.stop="removeSession(s.key)">×</span>
      </div>
    </div>

    <!-- 终端区域（所有会话容器常驻，切换仅控制显隐） -->
    <div class="terminal-body">
      <div
        v-for="s in sessions"
        :key="s.key"
        v-show="activeTab === s.key"
        :ref="(el) => setTerminalRef(el, s.key)"
        class="terminal-wrap"
      ></div>
      <div v-if="sessions.length === 0" class="terminal-empty">
        暂无会话，请通过设备列表「远程 → SSH / Telnet」添加多个终端会话
      </div>
    </div>

    <template #footer>
      <span class="term-status" :class="statusClass">
        <span class="status-dot"></span>{{ statusText }}
      </span>
      <el-button size="small" :disabled="!activeSession || !activeSession.connected" @click="handleCopy">复制</el-button>
      <el-button size="small" :disabled="!activeSession || !activeSession.connected" @click="handlePaste">粘贴</el-button>
      <el-button
        size="small"
        type="danger"
        :disabled="!activeSession || (!activeSession.connected && !activeSession.connecting)"
        @click="closeActiveSession"
      >
        {{ activeSession && activeSession.connected ? '断开连接' : '关闭会话' }}
      </el-button>
    </template>
  </el-dialog>
</template>

<script>
import { Terminal } from '@xterm/xterm';
import { FitAddon } from '@xterm/addon-fit';
import '@xterm/xterm/css/xterm.css';

export default {
  name: 'TerminalModal',
  props: {
    // 内嵌全屏模式：为 true 时不显示弹窗外壳，直接在页面内渲染（用于 /terminal 独立页面）
    embedded: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      dialogVisible: false,
      sessions: [],        // 多会话：每个元素为 { key, title, deviceId, account, protocol, deviceIp, statusText, status, connected, connecting, term, fitAddon, ws }
      activeTab: '',       // 当前激活会话 key
      wsPort: 7822,
      _containerMap: {},   // 非响应式：key -> xterm 容器 DOM
    };
  },
  computed: {
    activeSession() {
      return this.sessions.find((s) => s.key === this.activeTab) || null;
    },
    statusText() {
      return this.activeSession ? this.activeSession.statusText : '未连接';
    },
    status() {
      return this.activeSession ? this.activeSession.status : 'disconnected';
    },
    statusClass() {
      return `is-${this.status}`;
    },
  },
  methods: {
    // ===== 公开方法：父组件调用，新增一个终端会话 =====
    async addSession({ deviceId, account, protocol, deviceIp }) {
      const proto = protocol === 'telnet' ? 'Telnet' : 'SSH';
      const session = {
        key: `${protocol}-${deviceIp}-${Date.now()}-${Math.floor(Math.random() * 10000)}`,
        title: `${proto} - ${deviceIp}`,
        deviceId,
        account,
        protocol,
        deviceIp,
        statusText: '正在建立连接...',
        status: 'connecting',
        connected: false,
        connecting: true,
        term: null,
        fitAddon: null,
        ws: null,
      };
      this.sessions.push(session);
      this.activeTab = session.key;
      if (!this.embedded) {
        this.dialogVisible = true;
      }

      // 等待容器渲染后初始化 xterm
      await this.$nextTick();
      const container = this._containerMap[session.key];
      if (!container) {
        session.statusText = '终端容器初始化失败';
        session.status = 'error';
        session.connecting = false;
        return;
      }

      // 初始化 xterm
      const term = new Terminal({
        cursorBlink: true,
        fontSize: 14,
        lineHeight: 1.15,
        fontFamily: 'Consolas, "Courier New", monospace',
        theme: { background: '#1e1e1e', foreground: '#d4d4d4' },
        scrollback: 5000,
        convertEol: true,
      });
      const fitAddon = new FitAddon();
      term.loadAddon(fitAddon);
      term.open(container);
      fitAddon.fit();
      session.term = term;
      session.fitAddon = fitAddon;

      // 键盘输入 → WebSocket
      term.onData((data) => {
        this.send(session, { type: 'input', data });
      });
      // 尺寸变化 → resize
      term.onResize(({ cols, rows }) => {
        this.send(session, { type: 'resize', cols, rows });
      });
      // Ctrl/Cmd+V 粘贴
      term.attachCustomKeyEventHandler((e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'v') {
          e.preventDefault();
          this.handlePaste();
          return false;
        }
        return true;
      });

      term.focus();

      // 获取会话 ticket 并建立 WebSocket
      try {
        const ticket = await this.createTicket(session);
        const wsProtocol = location.protocol === 'https:' ? 'wss://' : 'ws://';
        const wsUrl = `${wsProtocol}${location.hostname}:${this.wsPort}/terminal?ticket=${ticket}&protocol=${session.protocol}`;
        this.connectWs(session, wsUrl);
      } catch (err) {
        session.statusText = err.message || '获取会话失败';
        session.status = 'error';
        session.connecting = false;
        this.writeLine(session, `\r\n\x1b[31m[${session.statusText}]\x1b[0m`);
      }
    },

    async createTicket(session) {
      const resp = await fetch('ws_session_api.php?action=create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ account: session.account, deviceId: session.deviceId }),
      });
      const data = await resp.json();
      if (!data.success) {
        throw new Error(data.message || '获取会话失败');
      }
      if (data.wsPort) this.wsPort = data.wsPort;
      return data.ticket;
    },

    connectWs(session, url) {
      const ws = new WebSocket(url);
      session.ws = ws;
      ws.onopen = () => {
        session.connected = true;
        session.connecting = false;
        session.statusText = '已连接';
        session.status = 'connected';
        if (this.activeTab === session.key && session.term) {
          session.term.focus();
        }
      };
      ws.onmessage = (e) => {
        try {
          const msg = JSON.parse(e.data);
          if (msg.type === 'output') {
            if (session.term) session.term.write(msg.data);
          } else if (msg.type === 'error' || msg.type === 'exit') {
            session.connected = false;
            session.connecting = false;
            session.statusText = msg.msg || '连接已关闭';
            session.status = msg.type === 'error' ? 'error' : 'disconnected';
            this.writeLine(session, `\r\n\x1b[31m[${session.statusText}]\x1b[0m`);
          }
        } catch (_) { /* 忽略非 JSON 消息 */ }
      };
      ws.onclose = () => {
        session.connected = false;
        session.connecting = false;
        if (session.status === 'connected') {
          session.statusText = '连接已断开';
          session.status = 'disconnected';
        }
      };
      ws.onerror = () => {
        session.connected = false;
        session.connecting = false;
        session.statusText = '连接异常';
        session.status = 'error';
      };
    },

    send(session, msg) {
      if (session.ws && session.ws.readyState === WebSocket.OPEN) {
        session.ws.send(JSON.stringify(msg));
      }
    },

    writeLine(session, text) {
      if (session && session.term) session.term.write(text);
    },

    // ===== Tab 管理 =====
    setTerminalRef(el, key) {
      if (el) {
        this._containerMap[key] = el;
      } else {
        delete this._containerMap[key];
      }
    },

    switchTab(key) {
      if (!key || this.activeTab === key) return;
      this.activeTab = key;
      this.$nextTick(() => {
        const s = this.sessions.find((x) => x.key === key);
        if (s && s.fitAddon && s.term) {
          s.fitAddon.fit();
          s.term.focus();
        }
      });
    },

    removeSession(key) {
      const idx = this.sessions.findIndex((s) => s.key === key);
      if (idx === -1) return;
      this.disposeSession(this.sessions[idx]);
      this.sessions.splice(idx, 1);
      if (this.activeTab === key) {
        const next = this.sessions[Math.min(idx, this.sessions.length - 1)];
        this.activeTab = next ? next.key : '';
      }
      if (this.sessions.length === 0) {
        if (!this.embedded) {
          this.dialogVisible = false;
        }
      } else if (this.activeTab) {
        this.$nextTick(() => {
          const s = this.sessions.find((x) => x.key === this.activeTab);
          if (s && s.fitAddon) s.fitAddon.fit();
        });
      }
    },

    disposeSession(session) {
      if (session.ws) {
        session.ws.onclose = null;
        try { session.ws.close(); } catch (_) {}
        session.ws = null;
      }
      if (session.term) {
        try { session.term.dispose(); } catch (_) {}
        session.term = null;
        session.fitAddon = null;
      }
      delete this._containerMap[session.key];
    },

    // 断开/关闭当前激活会话
    closeActiveSession() {
      const s = this.activeSession;
      if (s) this.removeSession(s.key);
    },

    // ===== 弹窗生命周期 =====
    handleClose() {
      // 用户点击右上角× 时 el-dialog 已进入关闭流程，无需额外处理
    },

    handleDialogVisibleChange(val) {
      if (!val && this.dialogVisible) {
        // 关闭弹窗 → 清理所有会话
        this.cleanupAll();
      }
    },

    handleClosed() {
      // destroy-on-close 后容器已销毁，兜底清理
      this.cleanupAll();
    },

    cleanupAll() {
      this.sessions.forEach((s) => this.disposeSession(s));
      this.sessions = [];
      this.activeTab = '';
      this.dialogVisible = false;
    },

    // ===== 复制 / 粘贴（作用于当前激活会话） =====
    async handleCopy() {
      const s = this.activeSession;
      if (!s || !s.term) return;
      try {
        const text = s.term.getSelection();
        if (text) {
          await navigator.clipboard.writeText(text);
          this.$message.success('已复制选中内容');
        }
      } catch (_) {
        this.$message.warning('复制失败');
      }
    },

    async handlePaste() {
      const s = this.activeSession;
      if (!s) return;
      try {
        const text = await navigator.clipboard.readText();
        if (text) this.send(s, { type: 'input', data: text });
      } catch (_) {
        this.$message.warning('无法读取剪贴板，请使用 Ctrl+Shift+V');
      }
    },
  },
  beforeUnmount() {
    // 组件销毁时兜底清理，避免 WebSocket 泄漏
    this.cleanupAll();
  },
};
</script>

<style scoped>
.terminal-dialog :deep(.el-dialog) {
  box-shadow: 0 8px 40px rgba(0,0,0,0.55);
  border: 1px solid #444;
  margin-left: auto !important;
  margin-right: 20px !important;
}

.terminal-dialog :deep(.el-dialog__body) {
  padding: 10px;
  background: #1e1e1e;
}

/* 会话 Tab 栏 */
.session-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-bottom: 8px;
}
.session-tab {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 10px;
  background: #2d2d2d;
  border: 1px solid #3c3c3c;
  border-radius: 4px;
  color: #ccc;
  font-size: 12px;
  cursor: pointer;
  user-select: none;
  max-width: 240px;
}
.session-tab:hover {
  background: #3a3a3a;
}
.session-tab.active {
  background: #0e639c;
  border-color: #0e639c;
  color: #fff;
}
.tab-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #909399;
  flex-shrink: 0;
}
.tab-dot.is-connecting { background: #e6a23c; }
.tab-dot.is-connected { background: #67c23a; }
.tab-dot.is-disconnected { background: #909399; }
.tab-dot.is-error { background: #f56c6c; }
.tab-title {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.tab-close {
  color: #999;
  font-size: 14px;
  line-height: 1;
  padding: 0 2px;
  border-radius: 2px;
  flex-shrink: 0;
}
.tab-close:hover {
  color: #fff;
  background: #f56c6c;
}

.terminal-body {
  position: relative;
}
.terminal-wrap {
  width: 100%;
  height: 55vh;
  background: #1e1e1e;
  padding: 4px;
  border-radius: 4px;
  overflow: hidden;
}
.terminal-empty {
  height: 55vh;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #666;
  font-size: 14px;
  background: #1e1e1e;
  border-radius: 4px;
}
.term-status {
  float: left;
  font-size: 12px;
  color: #909399;
  display: inline-flex;
  align-items: center;
}
.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #909399;
  margin-right: 6px;
  display: inline-block;
}
.term-status.is-connecting .status-dot { background: #e6a23c; }
.term-status.is-connected .status-dot { background: #67c23a; }
.term-status.is-disconnected .status-dot { background: #909399; }
.term-status.is-error .status-dot { background: #f56c6c; }

/* ========== 内嵌全屏模式（/terminal 独立页面） ========== */
.terminal-embedded {
  display: flex;
  flex-direction: column;
  height: 100vh;
  width: 100%;
  background: #1e1e1e;
  overflow: hidden;
}
.terminal-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  background: #2d2d2d;
  border-bottom: 1px solid #444;
  flex-shrink: 0;
  box-sizing: border-box;
}
.terminal-header .dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #909399;
  flex-shrink: 0;
}
.terminal-header .dot.is-connecting { background: #e6a23c; }
.terminal-header .dot.is-connected { background: #67c23a; }
.terminal-header .dot.is-disconnected { background: #909399; }
.terminal-header .dot.is-error { background: #f56c6c; }
.terminal-header .term-title {
  color: #e8e8e8;
  font-size: 14px;
  font-weight: 600;
  white-space: nowrap;
}
.terminal-header .page-status {
  font-size: 12px;
  color: #909399;
  white-space: nowrap;
}
.terminal-header .page-status.is-connecting { color: #e6a23c; }
.terminal-header .page-status.is-connected { color: #67c23a; }
.terminal-header .page-status.is-error { color: #f56c6c; }
.terminal-header .spacer {
  flex: 1;
}
.terminal-embedded .session-tabs {
  flex-shrink: 0;
  padding: 6px 10px;
  margin: 0;
  background: #252526;
  border-bottom: 1px solid #3c3c3c;
}
.terminal-embedded .terminal-body {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}
.terminal-embedded .terminal-wrap {
  flex: 1;
  height: auto;
  min-height: 0;
  border-radius: 0;
  box-sizing: border-box;
}
.terminal-embedded .terminal-empty {
  flex: 1;
  height: auto;
  border-radius: 0;
}
</style>
