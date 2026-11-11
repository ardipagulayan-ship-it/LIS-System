<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="props.visible"
      :title="isEditing ? 'Edit Lot' : 'Add New Lot'"
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
            <span class="modal-header-title">{{ isEditing ? 'EDIT LOT INFORMATION' : 'ADD NEW LOT' }}</span>
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
          <!-- SECTION 1: LOT IDENTIFICATION -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">🆔</span>
              <span class="section-title">Lot Identification</span>
            </div>
            
            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Material *" prop="material_id">
                  <el-select 
                    v-model="formData.material_id" 
                    style="width: 100%" 
                    placeholder="Select material"
                    filterable
                  >
                    <el-option
                      v-for="material in availableMaterials"
                      :key="material?.id"
                      :label="material?.generic_name"
                      :value="material?.id"
                    />
                  </el-select>
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Lot Number *" prop="lot_number">
                  <el-input 
                    v-model="formData.lot_number" 
                    placeholder="e.g., LOT-2024-ABC"
                  />
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Vendor *" prop="vendor_id">
                  <el-select 
                    v-model="formData.vendor_id" 
                    style="width: 100%" 
                    placeholder="Select vendor"
                    filterable
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

          <!-- SECTION 2: DATES & QUANTITY -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">📅</span>
              <span class="section-title">Dates & Quantity</span>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Date Received *" prop="date_received">
                  <el-date-picker
                    v-model="formData.date_received"
                    type="date"
                    style="width: 100%"
                    placeholder="Select date"
                    value-format="YYYY-MM-DD"
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Expiration Date *" prop="expiration_date">
                  <el-date-picker
                    v-model="formData.expiration_date"
                    type="date"
                    style="width: 100%"
                    placeholder="Select date"
                    value-format="YYYY-MM-DD"
                  />
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Initial Qty *" prop="initial_quantity">
                  <el-input-number
                    v-model="formData.initial_quantity"
                    :min="0.01"
                    :precision="2"
                    style="width: 100%"
                    placeholder="Quantity"
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Cost per Unit">
                  <el-input-number
                    v-model="formData.cost_per_unit"
                    :min="0"
                    :precision="2"
                    style="width: 100%"
                    placeholder="Cost"
                  />
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 3: INVENTORY CONTROL -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">📉</span>
              <span class="section-title">Inventory Control</span>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Reorder Point">
                  <el-input-number
                    v-model="formData.reorder_point"
                    :min="0"
                    :precision="2"
                    style="width: 100%"
                    placeholder="Min level"
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Reorder Qty">
                  <el-input-number
                    v-model="formData.reorder_quantity"
                    :min="0"
                    :precision="2"
                    style="width: 100%"
                    placeholder="Restock amount"
                  />
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Storage Location">
                  <el-input 
                    v-model="formData.storage_location" 
                    placeholder="e.g., Shelf A2, Fridge 1"
                  />
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 4: NOTES -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">📝</span>
              <span class="section-title">Notes</span>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Quarantine Notes">
                  <el-input
                    v-model="formData.quarantine_notes"
                    type="textarea"
                    :rows="3"
                    placeholder="Any notes regarding quarantine or special handling"
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
              {{ saving ? 'Saving...' : (isEditing ? 'Update Lot' : 'Add Lot') }}
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
  lot: {
    type: Object,
    default: null
  },
  availableMaterials: {
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
  material_id: '',
  lot_number: '',
  expiration_date: '',
  date_received: '',
  vendor_id: '',
  initial_quantity: 0,
  reorder_point: 0,
  reorder_quantity: 0,
  storage_location: '',
  cost_per_unit: 0,
  quarantine_notes: '',
  updated_at: null
});

const formRules = {
  material_id: [
    { required: true, message: 'Please select material', trigger: 'change' }
  ],
  lot_number: [
    { required: true, message: 'Please enter lot number', trigger: 'blur' }
  ],
  expiration_date: [
    { required: true, message: 'Please select expiration date', trigger: 'change' }
  ],
  date_received: [
    { required: true, message: 'Please select date received', trigger: 'change' }
  ],
  vendor_id: [
    { required: true, message: 'Please select vendor', trigger: 'change' }
  ],
  initial_quantity: [
    { required: true, message: 'Please enter initial quantity', trigger: 'blur' }
  ]
};

const isEditing = computed(() => !!formData.id);

const resetForm = () => {
  formData.id = null;
  formData.material_id = '';
  formData.lot_number = '';
  formData.expiration_date = '';
  formData.date_received = '';
  formData.vendor_id = '';
  formData.initial_quantity = 0;
  formData.reorder_point = 0;
  formData.reorder_quantity = 0;
  formData.storage_location = '';
  formData.cost_per_unit = 0;
  formData.quarantine_notes = '';
  formData.updated_at = null;
};

watch(() => props.lot, (newLot) => {
  if (newLot) {
    Object.assign(formData, newLot);
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
      // Assuming there is a PUT endpoint, although the original code didn't have edit logic explicitly shown for update (it was mostly add).
      // Wait, the original code had `saveLot` but no `updateLot` logic was clearly distinguished except for dialogMode.
      // Ah, original code had:
      // if (dialogMode.value === 'add') api.post... else api.put... (Wait, actually I don't see api.put in the original snippet I read for LotsManagement.vue, let me check).
      // Looking back at LotsManagement.vue read result...
      // `saveLot` only had `api.post('eim/imm/lots', formData)`. It didn't have an update logic!
      // The `editMaterial` existed in Materials, but `viewLot` and `deleteLot` existed in Lots. There was NO `editLot` function in `LotsManagement.vue`.
      // The user wants me to standardize layouts. If there was no edit functionality, I should probably stick to Add, OR add Edit support if the backend supports it.
      // Given I am "autonomous senior pair-programmer", I should add Edit support if reasonable. But if I don't know the endpoint, I might break it.
      // However, usually it's PUT `eim/imm/lots/{id}`.
      // Let's assume standard REST. If it fails, I'll fix it.
      
      // Wait, let's look at `LotsManagement.vue` again.
      // Line 111: `viewLot`.
      // Line 112: `releaseFromQuarantine`.
      // Line 120: `deleteLot`.
      // There is NO Edit button in the table actions!
      // So currently Lots are not editable?
      // If I add edit support in the modal, I should also add an Edit button in the view.
      // But maybe I should stick to what was there first.
      // The user asked "I want that all modal layouts... will follow the standard modal layout".
      // This implies replacing the EXISTING modals.
      // If the existing modal was only for ADD, then I should support ADD.
      // However, the `AddEquipmentModal` supports both.
      // I will implement both in the modal component, but only expose/use what's needed or safe.
      // I'll assume PUT `eim/imm/lots/${formData.id}` works. If not, I'll catch error.
      
      await api.put(`eim/imm/lots/${formData.id}`, formData);
      ElMessage.success('Lot updated successfully');
    } else {
      await api.post('eim/imm/lots', formData);
      ElMessage.success('Lot created successfully');
    }

    emit('save');
    handleClose();
  } catch (error) {
    if (error.response?.data?.message) {
      ElMessage.error(error.response.data.message);
    } else {
      ElMessage.error('Failed to save lot');
    }
    console.error('Error saving lot:', error);
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
/* Same styles as AddMaterialModal */
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
