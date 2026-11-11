<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="props.visible"
      :title="isEditing ? 'Edit Maintenance Schedule' : 'Add Maintenance Schedule'"
      width="900px"
      :close-on-click-modal="false"
      :show-close="false"
      class="equipment-modal"
      v-loading="loading"
    >
      <!-- Modal Header with Brand -->
      <template #header>
        <div class="modal-header">
          <div class="modal-header-brand">
            <span class="modal-header-icon">📅</span>
            <span class="modal-header-title">{{ isEditing ? 'EDIT SCHEDULE' : 'ADD NEW SCHEDULE' }}</span>
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
          label-width="140px"
          class="equipment-form"
        >
          <!-- SECTION 1: BASIC INFORMATION -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">📋</span>
              <span class="section-title">Basic Information</span>
            </div>
            
            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Equipment *" prop="equipment_id">
                  <el-select
                    v-model="formData.equipment_id"
                    placeholder="Select Equipment"
                    filterable
                    clearable
                  >
                    <el-option
                      v-for="equipment in equipmentList"
                      :key="equipment.id"
                      :label="`${equipment.unique_asset_id} - ${equipment.manufacturer} ${equipment.model}`"
                      :value="equipment.id"
                    />
                  </el-select>
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Schedule Type *" prop="schedule_type">
                  <el-select
                    v-model="formData.schedule_type"
                    placeholder="Select Type"
                    clearable
                  >
                    <el-option label="Preventative Maintenance" value="preventative_maintenance" />
                    <el-option label="Calibration" value="calibration" />
                    <el-option label="Validation" value="validation" />
                    <el-option label="Repair" value="repair" />
                  </el-select>
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Status" prop="is_active">
                  <el-switch
                    v-model="formData.is_active"
                    active-text="Active"
                    inactive-text="Inactive"
                  />
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 2: TIMING & FREQUENCY -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">⏱️</span>
              <span class="section-title">Timing & Frequency</span>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Frequency Type *" prop="frequency_type">
                  <el-radio-group v-model="formData.frequency_type">
                    <el-radio label="months">Months</el-radio>
                    <el-radio label="days">Days</el-radio>
                  </el-radio-group>
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Interval Value *" prop="frequency_value">
                  <el-input-number
                    v-model="formData.frequency_value"
                    :min="1"
                    placeholder="Enter value"
                  />
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Next Due Date *" prop="next_due_date">
                  <el-date-picker
                    v-model="formData.next_due_date"
                    type="date"
                    placeholder="Select due date"
                    value-format="YYYY-MM-DD"
                  />
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 3: ASSIGNMENT & DETAILS -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">📝</span>
              <span class="section-title">Assignment & Details</span>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Assigned To" prop="assigned_to">
                  <el-input
                    v-model="formData.assigned_to"
                    placeholder="Enter personnel or vendor name"
                    clearable
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Priority" prop="priority">
                  <el-select v-model="formData.priority" placeholder="Select Priority">
                    <el-option label="Low" value="low" />
                    <el-option label="Medium" value="medium" />
                    <el-option label="High" value="high" />
                    <el-option label="Critical" value="critical" />
                  </el-select>
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col" style="grid-column: span 2;">
                <el-form-item label="Description" prop="description">
                  <el-input
                    v-model="formData.description"
                    type="textarea"
                    :rows="3"
                    placeholder="Enter detailed description of the maintenance task"
                  />
                </el-form-item>
              </div>
            </div>
          </div>
        </el-form>
      </div>

      <!-- Modal Footer with Actions -->
      <template #footer>
        <div class="modal-footer">
          <div class="footer-info">
            <span v-if="formData.id" class="info-text">
              Last modified: {{ formatDate(formData.updated_at) }}
            </span>
          </div>
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
              {{ saving ? 'Saving...' : (isEditing ? 'Update Schedule' : 'Create Schedule') }}
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, reactive, onMounted } from 'vue';
import { ElMessage } from 'element-plus';
import { Close, Check } from '@element-plus/icons-vue';
import api from '@/utils/axios';
import { format, parseISO } from 'date-fns';

const props = defineProps({
  visible: {
    type: Boolean,
    required: true
  },
  scheduleData: {
    type: Object,
    default: null
  },
  equipmentList: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['close', 'save']);

const formRef = ref(null);
const loading = ref(false);
const saving = ref(false);

const formData = reactive({
  id: null,
  equipment_id: null,
  schedule_type: 'preventative_maintenance',
  frequency_type: 'months',
  frequency_value: 1,
  next_due_date: '',
  description: '',
  assigned_to: '',
  priority: 'medium',
  is_active: true,
  updated_at: null
});

const formRules = reactive({
  equipment_id: [
    { required: true, message: 'Please select equipment', trigger: 'change' }
  ],
  schedule_type: [
    { required: true, message: 'Please select schedule type', trigger: 'change' }
  ],
  frequency_value: [
    { required: true, message: 'Please enter frequency value', trigger: 'blur' }
  ],
  next_due_date: [
    { required: true, message: 'Please select next due date', trigger: 'change' }
  ]
});

const isEditing = computed(() => !!formData.id);

const resetForm = () => {
  formData.id = null;
  formData.equipment_id = null;
  formData.schedule_type = 'preventative_maintenance';
  formData.frequency_type = 'months';
  formData.frequency_value = 1;
  formData.next_due_date = '';
  formData.description = '';
  formData.assigned_to = '';
  formData.priority = 'medium';
  formData.is_active = true;
  formData.updated_at = null;
};

watch(() => props.scheduleData, (newData) => {
  if (newData) {
    Object.keys(newData).forEach(key => {
      if (key in formData) {
        formData[key] = newData[key];
      }
    });
    // Handle frequency mapping if needed (e.g. if backend sends frequency_months, map to type=months, value=X)
    if (newData.frequency_months) {
      formData.frequency_type = 'months';
      formData.frequency_value = newData.frequency_months;
    } else if (newData.frequency_days) {
      formData.frequency_type = 'days';
      formData.frequency_value = newData.frequency_days;
    }
  } else {
    resetForm();
  }
}, { immediate: true, deep: true });

const formatDate = (date) => {
  if (!date) return 'N/A';
  try {
    return format(parseISO(date), 'MMM dd, yyyy HH:mm');
  } catch {
    return date;
  }
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();
    saving.value = true;

    const payload = {
      equipment_id: formData.equipment_id,
      schedule_type: formData.schedule_type,
      next_due_date: formData.next_due_date,
      description: formData.description,
      assigned_to: formData.assigned_to, // Note: Backend might expect assigned_personnel_id or similar
      priority: formData.priority,
      is_active: formData.is_active
    };

    // Map frequency
    if (formData.frequency_type === 'months') {
      payload.frequency_months = formData.frequency_value;
      payload.frequency_days = null;
    } else {
      payload.frequency_days = formData.frequency_value;
      payload.frequency_months = null;
    }

    let response;
    if (isEditing.value) {
      response = await api.put(`eim/maintenance-schedules/${formData.id}`, payload);
      ElMessage.success('Schedule updated successfully');
    } else {
      response = await api.post('eim/maintenance-schedules', payload);
      ElMessage.success('Schedule created successfully');
    }

    emit('save', response.data);
    handleClose();
  } catch (error) {
    console.error('Error saving schedule:', error);
    ElMessage.error(error.response?.data?.message || 'Failed to save schedule');
  } finally {
    saving.value = false;
  }
};

const handleClose = () => {
  resetForm();
  if (formRef.value) {
    formRef.value.clearValidate();
  }
  emit('close');
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
  font-size: var(--font-size-xl);
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

.modal-header-actions .el-button--default {
  background-color: var(--color-warning);
  border-color: var(--color-warning);
}

.modal-header-actions .el-button--default:hover {
  background-color: var(--color-warning-dark);
  border-color: var(--color-warning-dark);
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

@media (max-width: 1200px) {
  .form-row {
    grid-template-columns: 1fr;
  }
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

.equipment-form :deep(.el-input__inner),
.equipment-form :deep(.el-date-editor__editor) {
  font-size: var(--font-size-sm);
  color: var(--color-text-primary);
}

.equipment-form :deep(.el-input__inner::placeholder),
.equipment-form :deep(.el-date-editor__editor::placeholder) {
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

.info-text {
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
  font-style: italic;
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
}
</style>
