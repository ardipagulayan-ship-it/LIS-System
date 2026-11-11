<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="props.visible"
      :title="isEditing ? 'Edit Consumable' : 'Add New Consumable'"
      width="800px"
      :close-on-click-modal="false"
      :show-close="false"
      class="equipment-modal"
      v-loading="loading"
    >
      <!-- Modal Header with Brand -->
      <template #header>
        <div class="modal-header">
          <div class="modal-header-brand">
            <span class="modal-header-icon">📦</span>
            <span class="modal-header-title">{{ isEditing ? 'EDIT CONSUMABLE INFORMATION' : 'ADD NEW CONSUMABLE' }}</span>
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
                <el-form-item label="Material ID *" prop="material_id">
                  <el-input
                    v-model="formData.material_id"
                    placeholder="e.g., MAT-2024-001"
                    clearable
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Name *" prop="name">
                  <el-input
                    v-model="formData.name"
                    placeholder="e.g., Glucose Reagent"
                    clearable
                  />
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
                <el-form-item label="Catalog Number *" prop="catalog_number">
                  <el-input
                    v-model="formData.catalog_number"
                    placeholder="Manufacturer catalog no."
                    clearable
                  />
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 2: LOT & EXPIRATION -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">📅</span>
              <span class="section-title">Lot & Expiration</span>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Lot Number *" prop="lot_number">
                  <el-input
                    v-model="formData.lot_number"
                    placeholder="Batch/Lot number"
                    clearable
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Expiration Date *" prop="expiration_date">
                  <el-date-picker
                    v-model="formData.expiration_date"
                    type="date"
                    placeholder="Select expiration date"
                    value-format="YYYY-MM-DD"
                    style="width: 100%"
                  />
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 3: STOCK & STORAGE -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">📍</span>
              <span class="section-title">Stock & Storage</span>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Current Stock *" prop="current_stock_level">
                  <el-input-number
                    v-model="formData.current_stock_level"
                    :min="0"
                    :precision="0"
                    placeholder="Quantity"
                    style="width: 100%"
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Unit of Measure *" prop="unit_of_measure">
                  <el-select
                    v-model="formData.unit_of_measure"
                    placeholder="Select unit"
                    style="width: 100%"
                    filterable
                    allow-create
                    default-first-option
                  >
                    <el-option label="Box" value="Box" />
                    <el-option label="Kit" value="Kit" />
                    <el-option label="Bottle" value="Bottle" />
                    <el-option label="Vial" value="Vial" />
                    <el-option label="Pack" value="Pack" />
                    <el-option label="Piece" value="Piece" />
                  </el-select>
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Reorder Level *" prop="reorder_level">
                  <el-input-number
                    v-model="formData.reorder_level"
                    :min="0"
                    :precision="0"
                    placeholder="Min stock level"
                    style="width: 100%"
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Storage Location *" prop="storage_location">
                  <el-input
                    v-model="formData.storage_location"
                    placeholder="e.g., Refrigerator 1, Shelf B"
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
              {{ saving ? 'Saving...' : (isEditing ? 'Update Consumable' : 'Add Consumable') }}
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, reactive } from 'vue';
import { ElMessage } from 'element-plus';
import { Close, Check } from '@element-plus/icons-vue';
import api from '@/utils/axios';
import { format, parseISO } from 'date-fns';

const props = defineProps({
  visible: {
    type: Boolean,
    required: true
  },
  consumableData: {
    type: Object,
    default: null
  },
  availableTests: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['close', 'save', 'update:visible']);

const formRef = ref(null);
const loading = ref(false);
const saving = ref(false);

const formData = reactive({
  id: null,
  material_id: '',
  name: '',
  manufacturer: '',
  catalog_number: '',
  lot_number: '',
  expiration_date: '',
  current_stock_level: 0,
  unit_of_measure: '',
  reorder_level: 0,
  storage_location: '',
  updated_at: null
});

const formRules = reactive({
  material_id: [
    { required: true, message: 'Material ID is required', trigger: 'blur' }
  ],
  name: [
    { required: true, message: 'Name is required', trigger: 'blur' }
  ],
  manufacturer: [
    { required: true, message: 'Manufacturer is required', trigger: 'blur' }
  ],
  catalog_number: [
    { required: true, message: 'Catalog number is required', trigger: 'blur' }
  ],
  lot_number: [
    { required: true, message: 'Lot number is required', trigger: 'blur' }
  ],
  expiration_date: [
    { required: true, message: 'Expiration date is required', trigger: 'change' }
  ],
  current_stock_level: [
    { required: true, message: 'Current stock is required', trigger: 'blur' }
  ],
  unit_of_measure: [
    { required: true, message: 'Unit of measure is required', trigger: 'change' }
  ],
  reorder_level: [
    { required: true, message: 'Reorder level is required', trigger: 'blur' }
  ],
  storage_location: [
    { required: true, message: 'Storage location is required', trigger: 'blur' }
  ]
});

const isEditing = computed(() => !!formData.id);

const resetForm = () => {
  formData.id = null;
  formData.material_id = '';
  formData.name = '';
  formData.manufacturer = '';
  formData.catalog_number = '';
  formData.lot_number = '';
  formData.expiration_date = '';
  formData.current_stock_level = 0;
  formData.unit_of_measure = '';
  formData.reorder_level = 0;
  formData.storage_location = '';
  formData.updated_at = null;
};

// Watch consumableData prop to populate form when editing
watch(() => props.consumableData, (newData) => {
  if (newData) {
    Object.keys(newData).forEach(key => {
      if (key in formData) {
        formData[key] = newData[key];
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
      material_id: formData.material_id,
      name: formData.name,
      manufacturer: formData.manufacturer,
      catalog_number: formData.catalog_number,
      lot_number: formData.lot_number,
      expiration_date: formData.expiration_date,
      current_stock_level: formData.current_stock_level,
      unit_of_measure: formData.unit_of_measure,
      reorder_level: formData.reorder_level,
      storage_location: formData.storage_location
    };

    let response;
    if (isEditing.value) {
      response = await api.put(`eim/consumables/${formData.id}`, payload);
      ElMessage.success('Consumable updated successfully');
    } else {
      response = await api.post('eim/consumables', payload);
      ElMessage.success('Consumable added successfully');
    }

    emit('save', response.data);
    handleClose();
  } catch (error) {
    if (error.response?.data?.message) {
      ElMessage.error(error.response.data.message);
    } else {
      ElMessage.error('Failed to save consumable');
    }
    console.error('Error saving consumable:', error);
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
  emit('update:visible', false);
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