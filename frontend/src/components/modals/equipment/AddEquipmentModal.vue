<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="props.visible"
      :title="isEditing ? 'Edit Equipment' : 'Add New Equipment'"
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
            <span class="modal-header-icon"></span>
            <span class="modal-header-title">{{ isEditing ? 'EDIT EQUIPMENT INFORMATION' : 'ADD NEW EQUIPMENT' }}</span>
          </div>
          <div class="modal-header-actions">
            <el-button type="default" @click="exportViolationsToSheets" size="small">
              <el-icon><Document /></el-icon>
              Export to Sheets
            </el-button>
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
                <el-form-item label="Unique Asset ID *" prop="unique_asset_id">
                  <el-input
                    v-model="formData.unique_asset_id"
                    placeholder="e.g., EQUIP-2024-001"
                    clearable
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Status *" prop="current_status">
                  <el-select
                    v-model="formData.current_status"
                    placeholder="Select status"
                    clearable
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
              <div class="form-col">
                <el-form-item label="Manufacturer *" prop="manufacturer">
                  <el-input
                    v-model="formData.manufacturer"
                    placeholder="e.g., Siemens, Abbott"
                    clearable
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Model *" prop="model">
                  <el-input
                    v-model="formData.model"
                    placeholder="e.g., Sysmex XN-1000"
                    clearable
                  />
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Serial Number *" prop="serial_number">
                  <el-input
                    v-model="formData.serial_number"
                    placeholder="Equipment serial number"
                    clearable
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Test Category" prop="test_category_id">
                  <el-select
                    v-model="formData.test_category_id"
                    placeholder="Select test category"
                    clearable
                  >
                    <el-option
                      v-for="category in testCategories"
                      :key="category.id"
                      :label="category.name"
                      :value="category.id"
                    />
                  </el-select>
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 2: PURCHASE & WARRANTY INFORMATION -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">📅</span>
              <span class="section-title">Purchase & Warranty Information</span>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Purchase Date *" prop="purchase_date">
                  <el-date-picker
                    v-model="formData.purchase_date"
                    type="date"
                    placeholder="Select purchase date"
                    value-format="YYYY-MM-DD"
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Warranty Expiration Date" prop="warranty_expiration_date">
                  <el-date-picker
                    v-model="formData.warranty_expiration_date"
                    type="date"
                    placeholder="Select warranty expiration date"
                    value-format="YYYY-MM-DD"
                  />
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 3: LOCATION INFORMATION -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">📍</span>
              <span class="section-title">Location Information</span>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Lab Location *" prop="location_lab">
                  <el-input
                    v-model="formData.location_lab"
                    placeholder="e.g., Hematology Lab, Main Building"
                    clearable
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Bench/Room Location" prop="location_bench">
                  <el-input
                    v-model="formData.location_bench"
                    placeholder="e.g., Bench A1, Room 102"
                    clearable
                  />
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 4: USAGE METRICS -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">📊</span>
              <span class="section-title">Usage Metrics</span>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Cycle Count" prop="cycle_count">
                  <el-input-number
                    v-model="formData.cycle_count"
                    :min="0"
                    placeholder="Total test cycles"
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Runtime Hours" prop="runtime_hours">
                  <el-input-number
                    v-model="formData.runtime_hours"
                    :min="0"
                    :step="0.01"
                    placeholder="Total operation hours"
                  />
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 5: DOCUMENTATION -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">📄</span>
              <span class="section-title">Documentation & Resources</span>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="SOP/Manual Link" prop="sop_operation_manual_link">
                  <el-input
                    v-model="formData.sop_operation_manual_link"
                    type="url"
                    placeholder="https://example.com/manual"
                    clearable
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
              {{ saving ? 'Saving...' : (isEditing ? 'Update Equipment' : 'Add Equipment') }}
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, reactive } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Close, Check } from '@element-plus/icons-vue';
import api from '@/utils/axios';
import { format, parseISO } from 'date-fns';

const props = defineProps({
  visible: {
    type: Boolean,
    required: true
  },
  equipment: {
    type: Object,
    default: null
  },
  testCategories: {
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
  unique_asset_id: '',
  current_status: 'in_service',
  manufacturer: '',
  model: '',
  serial_number: '',
  test_category_id: null,
  purchase_date: '',
  warranty_expiration_date: '',
  location_lab: '',
  location_bench: '',
  cycle_count: 0,
  runtime_hours: 0,
  sop_operation_manual_link: '',
  updated_at: null
});

const formRules = reactive({
  unique_asset_id: [
    { required: true, message: 'Asset ID is required', trigger: 'blur' },
    { min: 2, max: 50, message: 'Asset ID must be between 2 and 50 characters', trigger: 'blur' }
  ],
  current_status: [
    { required: true, message: 'Status is required', trigger: 'change' }
  ],
  manufacturer: [
    { required: true, message: 'Manufacturer is required', trigger: 'blur' }
  ],
  model: [
    { required: true, message: 'Model is required', trigger: 'blur' }
  ],
  serial_number: [
    { required: true, message: 'Serial number is required', trigger: 'blur' }
  ],
  purchase_date: [
    { required: true, message: 'Purchase date is required', trigger: 'change' }
  ],
  location_lab: [
    { required: true, message: 'Lab location is required', trigger: 'blur' }
  ]
});

const isEditing = computed(() => !!formData.id);

const resetForm = () => {
  formData.id = null;
  formData.unique_asset_id = '';
  formData.current_status = 'in_service';
  formData.manufacturer = '';
  formData.model = '';
  formData.serial_number = '';
  formData.test_category_id = null;
  formData.purchase_date = '';
  formData.warranty_expiration_date = '';
  formData.location_lab = '';
  formData.location_bench = '';
  formData.cycle_count = 0;
  formData.runtime_hours = 0;
  formData.sop_operation_manual_link = '';
  formData.updated_at = null;
};

// Watch equipment prop to populate form when editing
watch(() => props.equipment, (newEquipment) => {
  if (newEquipment) {
    Object.keys(newEquipment).forEach(key => {
      if (key in formData) {
        formData[key] = newEquipment[key];
      }
    });
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
      unique_asset_id: formData.unique_asset_id,
      current_status: formData.current_status,
      manufacturer: formData.manufacturer,
      model: formData.model,
      serial_number: formData.serial_number,
      test_category_id: formData.test_category_id || null,
      purchase_date: formData.purchase_date,
      warranty_expiration_date: formData.warranty_expiration_date || null,
      location_lab: formData.location_lab,
      location_bench: formData.location_bench || null,
      cycle_count: formData.cycle_count || 0,
      runtime_hours: formData.runtime_hours || 0,
      sop_operation_manual_link: formData.sop_operation_manual_link || null
    };

    let response;
    if (isEditing.value) {
      response = await api.put(`eim/equipment/${formData.id}`, payload);
      ElMessage.success('Equipment updated successfully');
    } else {
      response = await api.post('eim/equipment', payload);
      ElMessage.success('Equipment added successfully');
    }

    emit('save', response.data);
    handleClose();
  } catch (error) {
    if (error.response?.data?.message) {
      ElMessage.error(error.response.data.message);
    } else {
      ElMessage.error('Failed to save equipment');
    }
    console.error('Error saving equipment:', error);
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
  background-image: url('@/assets/icons/exclamation-mark.png');
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center;
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

.header-icon {
  font-size: 20px;
  opacity: 0.9;
}

.header-title {
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-bold);
  letter-spacing: 0.5px;
  text-transform: uppercase;
  opacity: 0.95;
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
