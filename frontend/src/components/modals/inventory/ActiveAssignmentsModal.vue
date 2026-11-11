<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="props.visible"
      :title="`Active Assignments`"
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
              ACTIVE ASSIGNMENTS - {{ props.equipment?.manufacturer }} {{ props.equipment?.model }}
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
          :data="activeAssignments" 
          stripe 
          v-loading="loading"
          height="100%"
          style="width: 100%"
        >
          <el-table-column prop="material.generic_name" label="Material" min-width="200">
            <template #default="{ row }">
              {{ row.material?.generic_name || 'N/A' }}
            </template>
          </el-table-column>
          <el-table-column prop="material.material_type" label="Type" width="120">
            <template #default="{ row }">
              <el-tag size="small" :type="row.material?.material_type === 'reagent' ? 'primary' : 'success'">
                {{ row.material?.material_type || 'N/A' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="lot.lot_number" label="Lot Number" width="150">
            <template #default="{ row }">
              {{ row.lot?.lot_number || 'N/A' }}
            </template>
          </el-table-column>
          <el-table-column prop="lot.current_stock_level" label="Current Stock" width="130">
            <template #default="{ row }">
              {{ row.lot?.current_stock_level || 'N/A' }}
            </template>
          </el-table-column>
          <el-table-column prop="lot.expiration_date" label="Expiration Date" width="130">
            <template #default="{ row }">
              {{ formatDate(row.lot?.expiration_date) }}
            </template>
          </el-table-column>
          <el-table-column prop="test.name" label="Test" min-width="150">
            <template #default="{ row }">
              {{ row.test?.name || 'N/A' }}
            </template>
          </el-table-column>
          <el-table-column prop="quantity_per_test" label="Qty/Test" width="100" />
          <el-table-column prop="assigned_at" label="Assigned Date" width="150">
            <template #default="{ row }">
              {{ formatDateTime(row.assigned_at) }}
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="180" fixed="right">
            <template #default="{ row }">
              <el-button size="small" @click="viewAssignmentDetails(row)">View</el-button>
              <el-button 
                size="small" 
                type="warning" 
                @click="deactivateAssignment(row)"
                v-if="row.is_active"
              >
                Deactivate
              </el-button>
              <el-button size="small" type="danger" @click="deleteAssignment(row)">Delete</el-button>
            </template>
          </el-table-column>
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
import { ElMessage, ElMessageBox } from 'element-plus';
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

const emit = defineEmits(['update:visible', 'close', 'update']);

const loading = ref(false);
const activeAssignments = ref([]);

watch(() => props.visible, (newVal) => {
  if (newVal && props.equipment) {
    loadAssignments();
  }
});

const loadAssignments = async () => {
  if (!props.equipment?.id) return;
  
  loading.value = true;
  try {
    const response = await api.get('eim/equipment-usage/assignments', {
      params: { equipment_id: props.equipment.id }
    });
    activeAssignments.value = response.data || [];
  } catch (error) {
    console.error('Failed to load assignments', error);
    ElMessage.error('Failed to load assignments');
  } finally {
    loading.value = false;
  }
};

const viewAssignmentDetails = (assignment) => {
  console.log('View assignment details:', assignment);
  ElMessage.info('Assignment details feature coming soon');
};

const deactivateAssignment = async (assignment) => {
  try {
    await ElMessageBox.confirm(
      'Are you sure you want to deactivate this assignment?',
      'Confirm Deactivate',
      { type: 'warning' }
    );

    await api.post(`eim/equipment-usage/assignments/${assignment.id}/deactivate`);
    ElMessage.success('Assignment deactivated successfully');
    loadAssignments();
    emit('update');
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Failed to deactivate assignment', error);
      ElMessage.error('Failed to deactivate assignment');
    }
  }
};

const deleteAssignment = async (assignment) => {
  try {
    await ElMessageBox.confirm(
      'Are you sure you want to delete this assignment?',
      'Confirm Delete',
      { type: 'warning' }
    );

    await api.delete(`eim/equipment-usage/assignments/${assignment.id}`);
    ElMessage.success('Assignment deleted successfully');
    loadAssignments();
    emit('update');
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Failed to delete assignment', error);
      ElMessage.error('Failed to delete assignment');
    }
  }
};

const handleClose = () => {
  emit('update:visible', false);
  emit('close');
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString();
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
  overflow-y: hidden;
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
