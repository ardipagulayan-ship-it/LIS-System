<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="props.visible"
      title="Update Equipment Status"
      width="600px"
      :close-on-click-modal="false"
      :show-close="false"
      class="equipment-modal"
    >
      <!-- Modal Header with Brand -->
      <template #header>
        <div class="modal-header">
          <div class="modal-header-brand">
            <span class="modal-header-icon"></span>
            <span class="modal-header-title">UPDATE EQUIPMENT STATUS</span>
          </div>
          <div class="modal-header-actions">
            <el-button type="danger" @click="handleClose" size="small">
              <el-icon><Close /></el-icon>
              Close
            </el-button>
          </div>
        </div>
      </template>

      <!-- Main Content -->
      <div class="modal-body-content">
        <el-form
          ref="formRef"
          :model="formData"
          :rules="formRules"
          label-width="120px"
          class="equipment-form"
        >
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">🔄</span>
              <span class="section-title">Status Details</span>
            </div>

            <div class="form-row">
              <div class="form-col full-width">
                 <el-form-item label="Equipment">
                    <el-input :model-value="equipment?.unique_asset_id" disabled />
                 </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col full-width">
                <el-form-item label="New Status *" prop="current_status">
                  <el-select
                    v-model="formData.current_status"
                    placeholder="Select status"
                    style="width: 100%"
                  >
                    <el-option label="In Service" value="in_service" />
                    <el-option label="Under Maintenance" value="under_maintenance" />
                    <el-option label="Calibration Due" value="calibration_due" />
                    <el-option label="Out of Service" value="out_of_service" />
                    <el-option label="Retired" value="retired" />
                  </el-select>
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col full-width">
                <el-form-item label="Reason" prop="reason">
                  <el-input
                    v-model="formData.reason"
                    type="textarea"
                    :rows="3"
                    placeholder="Enter reason for status change..."
                  />
                </el-form-item>
              </div>
            </div>
          </div>
        </el-form>
      </div>

      <!-- Modal Footer -->
      <template #footer>
        <div class="modal-footer">
          <div class="footer-info"></div>
          <div class="footer-actions">
            <el-button @click="handleClose" class="btn-cancel">
              <el-icon><Close /></el-icon>
              Cancel
            </el-button>
            <el-button
              type="primary"
              @click="handleSubmit"
              :loading="saving"
              class="btn-submit"
            >
              <el-icon><Check /></el-icon>
              Update Status
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </Teleport>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { ElMessage } from 'element-plus';
import { Close, Check } from '@element-plus/icons-vue';
import api from '@/utils/axios';

const props = defineProps({
  visible: {
    type: Boolean,
    required: true
  },
  equipment: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'save']);

const formRef = ref(null);
const saving = ref(false);

const formData = reactive({
  current_status: '',
  reason: ''
});

const formRules = {
  current_status: [
    { required: true, message: 'Please select a status', trigger: 'change' }
  ]
};

watch(() => props.equipment, (newVal) => {
  if (newVal) {
    formData.current_status = newVal.current_status;
    formData.reason = '';
  }
}, { immediate: true });

const handleClose = () => {
  emit('close');
};

const handleSubmit = async () => {
  if (!formRef.value) return;
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      saving.value = true;
      try {
        await api.put(`/eim/equipment/${props.equipment.id}/status`, formData);
        ElMessage.success('Equipment status updated successfully');
        emit('save');
        handleClose();
      } catch (error) {
        console.error('Error updating status:', error);
        ElMessage.error('Failed to update status');
      } finally {
        saving.value = false;
      }
    }
  });
};
</script>

<style scoped>
.equipment-modal :deep(.el-dialog__header) {
  padding: 0;
  border-bottom: none;
}

.equipment-modal :deep(.el-dialog__body) {
  padding: 0;
  max-height: calc(100vh - 200px);
  overflow-y: auto;
}

/* Modal Header Brand */
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-md);
  padding: var(--spacing-md) var(--spacing-xl);
  background: var(--color-dark);
  color: var(--color-text-light);
  margin: -20px -20px 0 -20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: var(--border-radius-md) var(--border-radius-md) 0 0;
}

.modal-header-brand {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  font-weight: var(--font-weight-semibold);
  letter-spacing: 0.3px;
}

.modal-header-icon {
  background-image: url('@/assets/icons/exclamation-mark.png');
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center;
  font-size: var(--font-size-xl);
  width: 24px;
  height: 24px;
  display: inline-block;
}

.modal-header-title {
  font-size: var(--font-size-sm);
  opacity: 0.9;
  text-transform: uppercase;
  white-space: nowrap;
}

.modal-header-actions {
  display: flex;
  gap: var(--spacing-sm);
}

.modal-header-actions .el-button {
  color: var(--color-text-light);
}

.modal-header-actions .el-button--danger {
  background-color: var(--color-danger);
  border-color: var(--color-danger);
}

.modal-header-actions .el-button--danger:hover {
  background-color: var(--color-danger-dark);
  border-color: var(--color-danger-dark);
}

/* Modal Body Content */
.equipment-form {
  background-color: var(--color-bg-primary);
  padding: var(--spacing-xl);
  border-radius: var(--border-radius-lg);
  border: 1px solid var(--color-border-secondary);
}

/* Form Sections */
.form-section {
  margin-bottom: var(--spacing-2xl);
}

.form-section:last-child {
  margin-bottom: 0;
}

.section-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-md);
  border-bottom: 2px solid var(--color-primary);
}

.section-icon {
  font-size: var(--font-size-xl);
  opacity: 0.8;
}

.section-title {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
  letter-spacing: 0.3px;
  text-transform: uppercase;
}

/* Form Grid */
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-lg);
  margin-bottom: var(--spacing-md);
}

.full-width {
  grid-column: 1 / -1;
}

.form-col {
  display: flex;
  flex-direction: column;
}

.equipment-form :deep(.el-form-item) {
  margin-bottom: 0;
}

.equipment-form :deep(.el-form-item__label) {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: var(--color-text-secondary);
  line-height: 32px;
  padding-right: var(--spacing-md);
}

.equipment-form :deep(.el-input__wrapper),
.equipment-form :deep(.el-select),
.equipment-form :deep(.el-date-editor),
.equipment-form :deep(.el-input-number) {
  border: 1px solid var(--color-border-primary);
  border-radius: var(--border-radius-md);
  transition: all var(--transition-normal);
}

.equipment-form :deep(.el-input__wrapper:hover),
.equipment-form :deep(.el-select:hover),
.equipment-form :deep(.el-date-editor:hover),
.equipment-form :deep(.el-input-number:hover) {
  border-color: var(--color-text-tertiary);
}

.equipment-form :deep(.el-input__wrapper:focus-within),
.equipment-form :deep(.el-select:focus-within),
.equipment-form :deep(.el-date-editor:focus-within),
.equipment-form :deep(.el-input-number:focus-within) {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px var(--color-primary-light);
}

.equipment-form :deep(.el-input__inner) {
  font-size: var(--font-size-sm);
  color: var(--color-text-primary);
}

.equipment-form :deep(.el-input__inner::placeholder) {
  color: var(--color-text-disabled);
}

/* Modal Footer */
.modal-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-lg) var(--spacing-xl);
  background-color: var(--color-bg-secondary);
  border-top: 1px solid var(--color-border-secondary);
  gap: var(--spacing-md);
}

.footer-info {
  flex: 1;
}

.footer-actions {
  display: flex;
  gap: var(--spacing-sm);
  justify-content: flex-end;
}

.btn-cancel,
.btn-submit {
  padding: 8px var(--spacing-xl);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  border-radius: var(--border-radius-md);
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all var(--transition-normal);
}

.btn-cancel:hover,
.btn-submit:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-cancel {
  background-color: var(--color-bg-tertiary);
  border-color: var(--color-border-primary);
  color: var(--color-text-secondary);
}

.btn-cancel:hover {
  background-color: #e5e7eb;
  border-color: var(--color-text-tertiary);
}

.btn-submit {
  background-color: var(--color-primary);
  border-color: var(--color-primary);
  color: var(--color-text-light);
}

.btn-submit:hover {
  background-color: var(--color-primary-dark);
  border-color: var(--color-primary-dark);
}

.btn-submit:disabled {
  background-color: var(--color-border-primary);
  border-color: var(--color-border-primary);
  color: var(--color-text-disabled);
  cursor: not-allowed;
}

.btn-submit:disabled:hover {
  transform: none;
  box-shadow: none;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }

  .modal-footer {
    flex-direction: column;
  }

  .footer-actions {
    width: 100%;
    justify-content: stretch;
  }

  .btn-cancel,
  .btn-submit {
    flex: 1;
    justify-content: center;
  }
}

/* Focus and validation states */
.equipment-form :deep(.el-input.is-focus .el-input__wrapper),
.equipment-form :deep(.el-select.is-focus .el-input__wrapper) {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px var(--color-primary-light);
}

.equipment-form :deep(.el-form-item.is-error .el-input__wrapper),
.equipment-form :deep(.el-form-item.is-error .el-select__wrapper) {
  border-color: var(--color-danger);
  box-shadow: 0 0 0 2px var(--color-danger-light);
}

/* Scrollbar styling */
.equipment-modal :deep(.el-dialog__body)::-webkit-scrollbar {
  width: 6px;
}

.equipment-modal :deep(.el-dialog__body)::-webkit-scrollbar-track {
  background: transparent;
}

.equipment-modal :deep(.el-dialog__body)::-webkit-scrollbar-thumb {
  background: var(--color-border-primary);
  border-radius: 3px;
}

.equipment-modal :deep(.el-dialog__body)::-webkit-scrollbar-thumb:hover {
  background: var(--color-text-tertiary);
}
</style>
