<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="visible"
      :title="isEditing ? 'Edit Patient' : 'New Patient Registration'"
      width="800px"
      :close-on-click-modal="false"
      :show-close="false"
      class="equipment-modal"
      @close="handleClose"
    >
      <!-- Modal Header with Brand -->
      <template #header>
        <div class="modal-header">
          <div class="modal-header-brand">
            <span class="modal-header-icon">👤</span>
            <span class="modal-header-title">{{ isEditing ? 'EDIT PATIENT' : 'NEW PATIENT' }}</span>
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
          <!-- SECTION 1: PERSONAL INFORMATION -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">🆔</span>
              <span class="section-title">Personal Information</span>
            </div>
            
            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Last Name *" prop="last_name">
                  <el-input v-model="formData.last_name" placeholder="Enter last name" />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="First Name *" prop="first_name">
                  <el-input v-model="formData.first_name" placeholder="Enter first name" />
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Middle Name" prop="middle_name">
                  <el-input v-model="formData.middle_name" placeholder="Enter middle name" />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Gender *" prop="gender">
                  <el-select v-model="formData.gender" placeholder="Select gender" class="full-width">
                    <el-option label="Male" value="Male" />
                    <el-option label="Female" value="Female" />
                    <el-option label="Other" value="Other" />
                  </el-select>
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Date of Birth *" prop="date_of_birth">
                  <el-date-picker
                    v-model="formData.date_of_birth"
                    type="date"
                    placeholder="Select date"
                    format="YYYY-MM-DD"
                    value-format="YYYY-MM-DD"
                    class="full-width"
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Civil Status" prop="civil_status">
                  <el-select v-model="formData.civil_status" placeholder="Select status" class="full-width">
                    <el-option label="Single" value="Single" />
                    <el-option label="Married" value="Married" />
                    <el-option label="Divorced" value="Divorced" />
                    <el-option label="Widowed" value="Widowed" />
                  </el-select>
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 2: CONTACT DETAILS -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">📞</span>
              <span class="section-title">Contact Details</span>
            </div>

            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Email" prop="email">
                  <el-input v-model="formData.email" placeholder="Enter email address" />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Phone" prop="phone">
                  <el-input v-model="formData.phone" placeholder="Enter phone number" />
                </el-form-item>
              </div>
            </div>

            <div class="form-row">
              <div class="form-col" style="grid-column: span 2;">
                <el-form-item label="Address" prop="address">
                  <el-input
                    v-model="formData.address"
                    type="textarea"
                    :rows="2"
                    placeholder="Enter complete address"
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
            <span v-if="isEditing" class="info-text">
              ID: {{ formData.id }}
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
              {{ saving ? 'Saving...' : (isEditing ? 'Update Patient' : 'Register Patient') }}
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

const props = defineProps({
  visible: {
    type: Boolean,
    required: true
  },
  patientData: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'save']);

const formRef = ref(null);
const saving = ref(false);

const formData = reactive({
  id: null,
  last_name: '',
  first_name: '',
  middle_name: '',
  email: '',
  phone: '',
  gender: '',
  date_of_birth: '',
  address: '',
  civil_status: ''
});

const formRules = reactive({
  last_name: [{ required: true, message: 'Please enter last name', trigger: 'blur' }],
  first_name: [{ required: true, message: 'Please enter first name', trigger: 'blur' }],
  gender: [{ required: true, message: 'Please select gender', trigger: 'change' }],
  date_of_birth: [{ required: true, message: 'Please select date of birth', trigger: 'change' }],
  email: [
    { required: true, message: 'Please enter email address', trigger: 'blur' },
    { type: 'email', message: 'Please enter valid email', trigger: 'blur' }
  ]
});

const isEditing = computed(() => !!formData.id);

const resetForm = () => {
  formData.id = null;
  formData.last_name = '';
  formData.first_name = '';
  formData.middle_name = '';
  formData.email = '';
  formData.phone = '';
  formData.gender = '';
  formData.date_of_birth = '';
  formData.address = '';
  formData.civil_status = '';
};

watch(() => props.patientData, (newData) => {
  if (newData) {
    Object.keys(newData).forEach(key => {
      if (key in formData) {
        formData[key] = newData[key];
      }
    });
    // Ensure date is formatted correctly if it comes as ISO string
    if (newData.date_of_birth) {
       formData.date_of_birth = newData.date_of_birth.split('T')[0];
    }
  } else {
    resetForm();
  }
}, { immediate: true, deep: true });

const handleSubmit = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();
    saving.value = true;

    let response;
    if (isEditing.value) {
      response = await api.put(`patients/${formData.id}`, formData);
      ElMessage.success('Patient updated successfully');
    } else {
      response = await api.post('patients', formData);
      ElMessage.success('Patient registered successfully');
    }

    emit('save', response.data);
    handleClose();
  } catch (error) {
    console.error('Error saving patient:', error);
    ElMessage.error(error.response?.data?.message || 'Failed to save patient');
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

.full-width {
  width: 100%;
}

.equipment-form :deep(.el-input__wrapper),
.equipment-form :deep(.el-select),
.equipment-form :deep(.el-date-editor) {
  border: 1px solid var(--color-border-primary);
  border-radius: var(--border-radius-md);
  transition: all var(--transition-normal);
}

.equipment-form :deep(.el-input__wrapper:hover),
.equipment-form :deep(.el-select:hover),
.equipment-form :deep(.el-date-editor:hover) {
  border-color: var(--color-text-tertiary);
}

.equipment-form :deep(.el-input__wrapper:focus-within),
.equipment-form :deep(.el-select:focus-within),
.equipment-form :deep(.el-date-editor:focus-within) {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px var(--color-primary-light);
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

.btn-cancel {
  background-color: var(--color-bg-tertiary);
  border-color: var(--color-border-primary);
  color: var(--color-text-secondary);
}

.btn-cancel:hover {
  background-color: #e5e7eb;
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