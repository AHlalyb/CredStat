<template>
  <div class="base-object-settings">
    <h1>基础对象设置</h1>
    <div class="button-group">
      <el-button type="primary" @click="openModal('room')">机房/站点</el-button>
      <el-button type="primary" @click="openModal('netDeviceType')">网络设备类型</el-button>
      <el-button type="primary" @click="openModal('netDeviceBrand')">网络设备品牌</el-button>
      <el-button type="primary" @click="openModal('netDeviceModel')">网络设备型号</el-button>
      <el-button type="primary" @click="openModal('serverOs')">服务器操作系统</el-button>
    </div>

    <!-- 基础对象设置模态框 -->
    <el-dialog
      v-model="modalVisible"
      :title="modalTitle"
      width="500px"
      :close-on-click-modal="false"
    >
      <div class="modal-content">
        <!-- 第一行：输入单元格和增加按钮 -->
        <div class="add-row">
          <el-input
            v-model="newValue"
            placeholder="请输入新值"
            class="input-new-value"
            @keyup.enter="addValue"
          ></el-input>
          <el-button type="primary" @click="addValue" :icon="Plus">增加</el-button>
        </div>

        <!-- 第二行：列表形式显示所有值 -->
        <div class="values-list">
          <div
            v-for="(value, index) in currentValues"
            :key="index"
            class="value-item"
          >
            <span class="value-text">{{ value }}</span>
            <el-button
              type="danger"
              size="small"
              @click="removeValue(index)"
              :icon="Delete"
            >删除</el-button>
          </div>
          <div v-if="currentValues.length === 0" class="no-values">暂无数据</div>
        </div>
      </div>

      <!-- 底部：保存和取消按钮 -->
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="cancel">取消</el-button>
          <el-button type="primary" @click="save" :icon="Check">保存</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { Plus, Delete, Check } from '@element-plus/icons-vue';

export default {
  name: 'BaseObjectSettingsView',
  components: {
    Plus,
    Delete,
    Check
  },
  data() {
    return {
      // 模态框状态
      modalVisible: false,
      modalTitle: '',
      currentType: '',
      // 输入和值列表
      newValue: '',
      currentValues: [],
      originalValues: [],
      // 数据映射
      typeConfig: {
        room: {
          title: '机房/站点',
          field: 'base_obj_room',
          apiField: 'rooms'
        },
        netDeviceType: {
          title: '网络设备类型',
          field: 'base_obj_net_device_type',
          apiField: 'netDeviceTypes'
        },
        netDeviceBrand: {
          title: '网络设备品牌',
          field: 'base_obj_net_device_brand',
          apiField: 'netDeviceBrands'
        },
        netDeviceModel: {
          title: '网络设备型号',
          field: 'base_obj_net_device_model',
          apiField: 'netDeviceModels'
        },
        serverOs: {
          title: '服务器操作系统',
          field: 'base_obj_server_os',
          apiField: 'serverOs'
        }
      }
    };
  },
  methods: {
    // 打开模态框
    async openModal(type) {
      this.currentType = type;
      this.modalTitle = this.typeConfig[type].title;
      
      // 加载当前类型的数据
      await this.loadData(type);
      
      this.modalVisible = true;
    },
    
    // 加载数据
    async loadData(type) {
      try {
        const response = await fetch('base_obj_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'getBaseObject',
            type: type
          })
        });
        
        const data = await response.json();
        if (data.success) {
          this.currentValues = data.data || [];
          this.originalValues = [...this.currentValues];
        } else {
          this.$message.error(`加载数据失败：${data.message}`);
          this.currentValues = [];
          this.originalValues = [];
        }
      } catch (error) {
        this.$message.error('加载数据失败，请稍后重试');
        this.currentValues = [];
        this.originalValues = [];
        console.error('加载数据失败:', error);
      }
    },
    
    // 添加新值
    addValue() {
      if (!this.newValue.trim()) {
        this.$message.warning('请输入要添加的值');
        return;
      }
      
      if (this.currentValues.includes(this.newValue.trim())) {
        this.$message.warning('该值已存在');
        return;
      }
      
      this.currentValues.push(this.newValue.trim());
      this.newValue = '';
    },
    
    // 移除值
    removeValue(index) {
      this.currentValues.splice(index, 1);
    },
    
    // 保存数据
    async save() {
      try {
        const response = await fetch('base_obj_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'saveBaseObject',
            type: this.currentType,
            values: this.currentValues
          })
        });
        
        const data = await response.json();
        if (data.success) {
          this.$message.success('保存成功');
          this.modalVisible = false;
          this.originalValues = [...this.currentValues];
        } else {
          this.$message.error(`保存失败：${data.message}`);
        }
      } catch (error) {
        this.$message.error('保存失败，请稍后重试');
        console.error('保存失败:', error);
      }
    },
    
    // 取消操作
    cancel() {
      this.currentValues = [...this.originalValues];
      this.newValue = '';
      this.modalVisible = false;
    }
  }
};
</script>

<style scoped>
.base-object-settings {
  padding: 20px;
}

.button-group {
  margin-top: 20px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.modal-content {
  padding: 10px 0;
}

.add-row {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.input-new-value {
  flex: 1;
}

.values-list {
  max-height: 300px;
  overflow-y: auto;
}

.value-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px;
  border-bottom: 1px solid #eee;
}

.value-text {
  flex: 1;
}

.no-values {
  text-align: center;
  color: #999;
  padding: 20px;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
</style>