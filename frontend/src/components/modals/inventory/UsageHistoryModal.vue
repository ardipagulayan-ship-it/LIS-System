<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="props.visible"
      :title="`Usage History`"
      width="1000px"
      :close-on-click-modal="false"
      :show-close="false"
      class="inventory-modal"
    >
      <template #header>
        <div class="modal-header">
          <div class="modal-header-brand">
            <span class="modal-header-icon"></span>
            <span class="modal-header-title">
              USAGE HISTORY - {{ props.equipment?.manufacturer }} {{ props.equipment?.model }}
            </span>
          </div>
          <div class="modal-header-actions">
            <el-button type="danger" @click="handleClose" size="small">
              <el-icon><Close /></el-icon>
              Close
            </el-button>
          </div>
        </div>
      </template>

      <div class="modal-body-content">
        <el-table 
          :data="usageHistory" 
          stripe 
          v-loading="loading" 
          height="100%"
          style="width: 100%"
        >
          <el-table-column prop="material.generic_name" label="Material" min-width="200" />
          <el-table-column prop="lot.lot_number" label="Lot Number" width="150" />
          <el-table-column prop="test.name" label="Test" width="150" />
          <el-table-column prop="usage_type" label="Type" width="120">
            <template #default="{ row }">
              <el-tag size="small">{{ row.usage_type }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="quantity_used" label="Quantity Used" width="120" />
          <el-table-column prop="usage_date" label="Date" width="180">
            <template #default="{ row }">
              {{ formatDateTime(row.usage_date) }}
            </template>
          </el-table-column>
          <el-table-column prop="usedBy.name" label="Used By" width="150" />
        </el-table>
      </div>

      <template #footer>
        <div class="modal-footer">
          <div class="footer-info">
            <!-- Pagination could go here if implemented -->
          </div>
          <div class="footer-actions">
            <el-button @click="handleClose" class="btn-cancel">
              Close
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Close } from '@element-plus/icons-vue';
import api from '@/utils/axios';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  equipment: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['update:visible', 'close']);

const loading = ref(false);
const usageHistory = ref([]);

watch(() => props.visible, (newVal) => {
  if (newVal && props.equipment) {
    loadUsageHistory();
  }
});

const loadUsageHistory = async () => {
  if (!props.equipment?.id) return;
  
  loading.value = true;
  try {
    const response = await api.get('eim/equipment-usage/history', {
      params: { equipment_id: props.equipment.id }
    });
    usageHistory.value = response.data || [];
  } catch (error) {
    console.error('Failed to load usage history', error);
  } finally {
    loading.value = false;
  }
};

const handleClose = () => {
  emit('update:visible', false);
  emit('close');
};

const formatDateTime = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleString();
};
</script>

<style scoped>
/* Reuse the standard modal styles */
.inventory-modal :deep(.el-dialog__header) {
  padding: 0;
  margin: 0;
  display: none;
}

.inventory-modal :deep(.el-dialog__body) {
  padding: 0;
  margin: 0;
  height: calc(100vh - 200px);
  display: flex;
  flex-direction: column;
}

.inventory-modal :deep(.el-dialog__footer) {
  padding: 0;
  margin: 0;
}

.modal-header {
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
  padding: 20px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-header-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.modal-header-icon {
  width: 4px;
  height: 24px;
  background-color: var(--color-secondary);
  border-radius: 2px;
  box-shadow: 0 0 8px rgba(255, 255, 255, 0.3);
}

.modal-header-title {
  font-size: 18px;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.modal-header-actions .el-button {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
  backdrop-filter: blur(4px);
  transition: all 0.3s ease;
}

.modal-header-actions .el-button:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-1px);
}

.modal-body-content {
  flex: 1;
  overflow-y: hidden; /* Let table handle scrolling */
  padding: 24px;
  background-color: #f8f9fa;
  display: flex;
  flex-direction: column;
}

.modal-footer {
  padding: 16px 24px;
  background-color: white;
  border-top: 1px solid var(--color-border-light);
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.03);
}

.footer-actions {
  display: flex;
  gap: 12px;
  width: 100%;
  justify-content: flex-end;
}

.btn-cancel {
  border: 1px solid var(--color-border-base);
  color: var(--color-text-regular);
  padding: 10px 20px;
}

@media (max-width: 768px) {
  .inventory-modal :deep(.el-dialog) {
    width: 95% !important;
    margin-top: 2vh !important;
  }
}
</style>
