<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="props.visible"
      :title="isEditing ? 'Edit Material' : 'Add New Material'"
      width="900px"
      :close-on-click-modal="false"
      :show-close="false"
      class="inventory-modal"
      v-loading="loading"
    >
      <!-- Modal Header with Brand -->
      <template #header>
        <div class="modal-header">
          <div class="modal-header-brand">
            <span class="modal-header-icon"></span>
            <span class="modal-header-title">{{ isEditing ? 'EDIT MATERIAL INFORMATION' : 'ADD NEW MATERIAL' }}</span>
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
          class="inventory-form"
        >
          <!-- SECTION 1: BASIC INFORMATION -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">📦</span>
              <span class="section-title">Basic Information</span>
            </div>
            
            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Generic Name *" prop="generic_name">
                  <el-input
                    v-model="formData.generic_name"
                    placeholder="e.g., Acetone"
                    clearable
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Material Type *" prop="material_type">
                  <el-select
                    v-model="formData.material_type"
                    placeholder="Select type"
                    style="width: 100%"
                    clearable
                  >
                    <el-option label="Reagent" value="reagent" />
                    <el-option label="Control" value="control" />
                    <el-option label="Calibrator" value="calibrator" />
                    <el-option label="Consumable" value="consumable" />
                    <el-option label="Equipment" value="equipment" />
                  </el-select>
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Manufacturer *" prop="manufacturer">
                  <el-input
                    v-model="formData.manufacturer"
                    placeholder="e.g., Sigma-Aldrich"
                    clearable
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Catalog Number *" prop="catalog_number">
                  <el-input
                    v-model="formData.catalog_number"
                    placeholder="e.g., CAT-12345"
                    clearable
                  />
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Description">
                  <el-input
                    v-model="formData.description"
                    type="textarea"
                    :rows="2"
                    placeholder="Brief description of the material"
                  />
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 2: SPECIFICATIONS -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">📏</span>
              <span class="section-title">Specifications</span>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Unit of Measure">
                  <el-input 
                    v-model="formData.standard_unit_of_measure" 
                    placeholder="e.g., ml, g, kit"
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Shelf Life (Days)">
                  <el-input-number
                    v-model="formData.shelf_life_days"
                    :min="1"
                    style="width: 100%"
                    placeholder="Days"
                  />
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 3: STORAGE & SAFETY -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">⚠️</span>
              <span class="section-title">Storage & Safety</span>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Storage Req.">
                  <el-input
                    v-model="formData.storage_requirements"
                    type="textarea"
                    :rows="2"
                    placeholder="e.g., 2-8°C, Keep away from light"
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Hazard Class">
                  <el-input 
                    v-model="formData.hazard_classification" 
                    placeholder="e.g., Flammable, Toxic"
                  />
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 4: ASSOCIATIONS -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">🔗</span>
              <span class="section-title">Associations</span>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Associated Tests">
                  <el-select
                    v-model="formData.test_ids"
                    multiple
                    placeholder="Select tests"
                    style="width: 100%"
                    collapse-tags
                  >
                    <el-option
                      v-for="test in availableTests"
                      :key="test?.id"
                      :label="test?.name"
                      :value="test?.id"
                    />
                  </el-select>
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Associated Vendors">
                  <el-select
                    v-model="formData.vendor_ids"
                    multiple
                    placeholder="Select vendors"
                    style="width: 100%"
                    collapse-tags
                  >
                    <el-option
                      v-for="vendor in availableVendors"
                      :key="vendor?.id"
                      :label="vendor?.vendor_name"
                      :value="vendor?.id"
                    />
                  </el-select>
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 5: STATUS -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">🔘</span>
              <span class="section-title">Status</span>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Status">
                  <el-switch
                    v-model="formData.is_active"
                    active-text="Active"
                    inactive-text="Inactive"
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
              {{ saving ? 'Saving...' : (isEditing ? 'Update Material' : 'Add Material') }}
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
  material: {
    type: Object,
    default: null
  },
  availableTests: {
    type: Array,
    default: () => []
  },
  availableVendors: {
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
  generic_name: '',
  manufacturer: '',
  catalog_number: '',
  description: '',
  standard_unit_of_measure: 'unit',
  shelf_life_days: null,
  material_type: 'consumable',
  storage_requirements: '',
  hazard_classification: '',
  is_active: true,
  test_ids: [],
  vendor_ids: [],
  updated_at: null
});

const formRules = {
  generic_name: [
    { required: true, message: 'Please enter generic name', trigger: 'blur' }
  ],
  manufacturer: [
    { required: true, message: 'Please enter manufacturer', trigger: 'blur' }
  ],
  catalog_number: [
    { required: true, message: 'Please enter catalog number', trigger: 'blur' }
  ],
  material_type: [
    { required: true, message: 'Please select material type', trigger: 'change' }
  ]
};

const isEditing = computed(() => !!formData.id);

const resetForm = () => {
  formData.id = null;
  formData.generic_name = '';
  formData.manufacturer = '';
  formData.catalog_number = '';
  formData.description = '';
  formData.standard_unit_of_measure = 'unit';
  formData.shelf_life_days = null;
  formData.material_type = 'consumable';
  formData.storage_requirements = '';
  formData.hazard_classification = '';
  formData.is_active = true;
  formData.test_ids = [];
  formData.vendor_ids = [];
  formData.updated_at = null;
};

watch(() => props.material, (newMaterial) => {
  if (newMaterial) {
    Object.assign(formData, {
      ...newMaterial,
      test_ids: newMaterial.tests?.map(t => t.id) || [],
      vendor_ids: newMaterial.vendors?.map(v => v.id) || []
    });
  } else {
    resetForm();
  }
}, { immediate: true });

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

    if (isEditing.value) {
      await api.put(`eim/imm/materials/${formData.id}`, formData);
      ElMessage.success('Material updated successfully');
    } else {
      await api.post('eim/imm/materials', formData);
      ElMessage.success('Material created successfully');
    }

    emit('save');
    handleClose();
  } catch (error) {
    if (error.response?.data?.message) {
      ElMessage.error(error.response.data.message);
    } else {
      ElMessage.error('Failed to save material');
    }
    console.error('Error saving material:', error);
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
.inventory-modal :deep(.el-dialog__header) {
  padding: 0;
  border-bottom: none;
}

.inventory-modal :deep(.el-dialog__body) {
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
.inventory-form {
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

.inventory-form :deep(.el-form-item) {
  margin-bottom: 0;
}

.inventory-form :deep(.el-form-item__label) {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: var(--color-text-secondary);
  line-height: 32px;
  padding-right: var(--spacing-md);
}

.inventory-form :deep(.el-input__wrapper),
.inventory-form :deep(.el-select),
.inventory-form :deep(.el-date-editor),
.inventory-form :deep(.el-input-number) {
  border: 1px solid var(--color-border-primary);
  border-radius: var(--border-radius-md);
  transition: all var(--transition-normal);
}

.inventory-form :deep(.el-input__wrapper:hover),
.inventory-form :deep(.el-select:hover),
.inventory-form :deep(.el-date-editor:hover),
.inventory-form :deep(.el-input-number:hover) {
  border-color: var(--color-text-tertiary);
}

.inventory-form :deep(.el-input__wrapper:focus-within),
.inventory-form :deep(.el-select:focus-within),
.inventory-form :deep(.el-date-editor:focus-within),
.inventory-form :deep(.el-input-number:focus-within) {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px var(--color-primary-light);
}

.inventory-form :deep(.el-input__inner),
.inventory-form :deep(.el-date-editor__editor) {
  font-size: var(--font-size-sm);
  color: var(--color-text-primary);
}

.inventory-form :deep(.el-input__inner::placeholder),
.inventory-form :deep(.el-date-editor__editor::placeholder) {
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
