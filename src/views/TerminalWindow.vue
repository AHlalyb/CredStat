<template>
  <div class="terminal-page">
    <TerminalModal ref="terminalModal" embedded />
  </div>
</template>

<script>
import TerminalModal from '../components/TerminalModal.vue';

export default {
  name: 'TerminalWindow',
  components: { TerminalModal },
  data() {
    return {
      channel: null,
    };
  },
  mounted() {
    // 1. 从 URL 读取首个会话参数并建立连接
    const q = this.$route.query || {};
    if (q.ip) {
      this.$refs.terminalModal.addSession({
        deviceId: q.deviceId,
        account: q.account,
        protocol: q.protocol,
        deviceIp: q.ip,
      });
    }
    // 2. 监听设备列表页通过 BroadcastChannel 推送的新会话
    if ('BroadcastChannel' in window) {
      this.channel = new BroadcastChannel('credstat-terminal');
      this.channel.onmessage = (e) => {
        const msg = e.data || {};
        if (msg.type === 'add-session' && msg.session) {
          this.$refs.terminalModal.addSession(msg.session);
        }
      };
    }
  },
  beforeUnmount() {
    if (this.channel) {
      this.channel.close();
      this.channel = null;
    }
  },
};
</script>

<style scoped>
.terminal-page {
  height: 100vh;
  width: 100vw;
  margin: 0;
  padding: 0;
  background: #1e1e1e;
  overflow: hidden;
}
</style>
