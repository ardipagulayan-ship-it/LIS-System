<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="props.visible"
      :title="isEditing ? 'Edit Vendor' : 'Add New Vendor'"
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
            <span class="modal-header-title">{{ isEditing ? 'EDIT VENDOR INFORMATION' : 'ADD NEW VENDOR' }}</span>
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
          <!-- SECTION 1: VENDOR DETAILS -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">🏢</span>
              <span class="section-title">Vendor Details</span>
            </div>
            
            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Vendor Name *" prop="vendor_name">
                  <el-input 
                    v-model="formData.vendor_name" 
                    placeholder="Enter vendor name"
                    clearable
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Contact Person" prop="contact_person">
                  <el-input 
                    v-model="formData.contact_person" 
                    placeholder="Enter contact person"
                    clearable
                  />
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Email" prop="email">
                  <el-input 
                    v-model="formData.email" 
                    type="email" 
                    placeholder="Enter email address"
                    clearable
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Phone" prop="phone">
                  <el-input 
                    v-model="formData.phone" 
                    placeholder="Enter phone number"
                    clearable
                  />
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Website" prop="website">
                  <el-input 
                    v-model="formData.website" 
                    placeholder="Enter website URL"
                    clearable
                  />
                </el-form-item>
              </div>
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

          <!-- SECTION 2: ADDRESS INFORMATION -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">📍</span>
              <span class="section-title">Address Information</span>
            </div>

            <div class="form-row">
              <div class="form-col" style="grid-column: span 2;">
                <el-form-item label="Address" prop="address">
                  <el-input
                    v-model="formData.address"
                    type="textarea"
                    :rows="2"
                    placeholder="Enter street address"
                    clearable
                  />
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="City" prop="city">
                  <el-input v-model="formData.city" placeholder="City" clearable />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="State" prop="state">
                  <el-input v-model="formData.state" placeholder="State" clearable />
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Postal Code" prop="postal_code">
                  <el-input v-model="formData.postal_code" placeholder="Postal Code" clearable />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Country" prop="country">
                  <el-input v-model="formData.country" placeholder="Country" clearable />
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 3: FINANCIAL INFORMATION -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">💰</span>
              <span class="section-title">Financial Information</span>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Tax ID" prop="tax_id">
                  <el-input v-model="formData.tax_id" placeholder="Enter Tax ID" clearable />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Payment Terms" prop="payment_terms">
                  <el-input v-model="formData.payment_terms" placeholder="e.g. Net 30" clearable />
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Currency" prop="currency">
                  <el-select v-model="formData.currency" style="width: 100%" placeholder="Select Currency">
                    <el-option label="USD" value="USD" />
                    <el-option label="EUR" value="EUR" />
                    <el-option label="GBP" value="GBP" />
                    <el-option label="PHP" value="PHP" />
                  </el-select>
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 4: ADDITIONAL NOTES -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">📝</span>
              <span class="section-title">Additional Notes</span>
            </div>

            <div class="form-row">
              <div class="form-col" style="grid-column: span 2;">
                <el-form-item label="Notes" prop="notes">
                  <el-input
                    v-model="formData.notes"
                    type="textarea"
                    :rows="3"
                    placeholder="Enter any additional notes"
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
              {{ saving ? 'Saving...' : (isEditing ? 'Update Vendor' : 'Add Vendor') }}
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </Teleport>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { ElMessage } from 'element-plus';
import { Close, Check } from '@element-plus/icons-vue';
import api from '@/utils/axios';
import { format, parseISO } from 'date-fns';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  vendor: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['update:visible', 'close', 'save']);

const formRef = ref(null);
const loading = ref(false);
const saving = ref(false);

const isEditing = computed(() => !!props.vendor);

const formData = reactive({
  id: null,
  vendor_name: '',
  contact_person: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  state: '',
  postal_code: '',
  country: '',
  website: '',
  tax_id: '',
  payment_terms: '',
  currency: 'USD',
  is_active: true,
  notes: '',
  updated_at: null
});

const formRules = {
  vendor_name: [
    { required: true, message: 'Please enter vendor name', trigger: 'blur' }
  ],
  email: [
    { type: 'email', message: 'Please enter a valid email address', trigger: 'blur' }
  ]
};

// Reset form when dialog opens/closes or vendor changes
watch(() => props.visible, (newVal) => {
  if (newVal) {
    if (props.vendor) {
      Object.assign(formData, props.vendor);
    } else {
      resetForm();
    }
  }
});

watch(() => props.vendor, (newVal) => {
  if (props.visible) {
    if (newVal) {
      Object.assign(formData, newVal);
    } else {
      resetForm();
    }
  }
});

const resetForm = () => {
  Object.assign(formData, {
    id: null,
    vendor_name: '',
    contact_person: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    state: '',
    postal_code: '',
    country: '',
    website: '',
    tax_id: '',
    payment_terms: '',
    currency: 'USD',
    is_active: true,
    notes: '',
    updated_at: null
  });
  if (formRef.value) {
    formRef.value.clearValidate();
  }
};

const handleClose = () => {
  emit('update:visible', false);
  emit('close');
};

const handleSubmit = async () => {
  if (!formRef.value) return;
  
  try {
    await formRef.value.validate();
    saving.value = true;
    
    if (isEditing.value) {
      await api.put(`eim/imm/vendors/${formData.id}`, formData);
      ElMessage.success('Vendor updated successfully');
    } else {
      await api.post('eim/imm/vendors', formData);
      ElMessage.success('Vendor created successfully');
    }
    
    emit('save');
    handleClose();
  } catch (error) {
    console.error('Failed to save vendor:', error);
    if (error.response?.data?.message) {
      ElMessage.error(error.response.data.message);
    } else {
      ElMessage.error('Failed to save vendor');
    }
  } finally {
    saving.value = false;
  }
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  try {
    return format(parseISO(date), 'MMM dd, yyyy HH:mm');
  } catch {
    return date;
  }
};
</script>

<style scoped>
/* Same styles as AddLotModal and AddEquipmentModal for consistency */
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