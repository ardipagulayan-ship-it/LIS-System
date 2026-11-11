<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="props.visible"
      :title="isEditing ? 'Edit Service Log' : 'Add Service Log'"
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
            <span class="modal-header-icon">
              <el-icon><Document /></el-icon>
            </span>
            <span class="modal-header-title">{{ isEditing ? 'EDIT SERVICE LOG' : 'ADD SERVICE LOG' }}</span>
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
          <!-- SECTION 1: SERVICE INFORMATION -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">
                <el-icon><Monitor /></el-icon>
              </span>
              <span class="section-title">Service Information</span>
            </div>
            
            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Equipment *" prop="equipment_id">
                  <el-select 
                    v-model="formData.equipment_id" 
                    placeholder="Select Equipment" 
                    filterable 
                    clearable
                    class="full-width"
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
                <el-form-item label="Service Type *" prop="service_type">
                  <el-select v-model="formData.service_type" placeholder="Select Type" class="full-width">
                    <el-option label="Preventative Maintenance" value="preventative_maintenance" />
                    <el-option label="Calibration" value="calibration" />
                    <el-option label="Validation" value="validation" />
                    <el-option label="Repair" value="repair" />
                    <el-option label="Inspection" value="inspection" />
                  </el-select>
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 2: SCHEDULE & OUTCOME -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">
                <el-icon><Calendar /></el-icon>
              </span>
              <span class="section-title">Schedule & Outcome</span>
            </div>
            
            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Service Date *" prop="service_date">
                  <el-date-picker
                    v-model="formData.service_date"
                    type="datetime"
                    placeholder="Select date and time"
                    class="full-width"
                    value-format="YYYY-MM-DD HH:mm:ss"
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Outcome *" prop="outcome">
                  <el-select v-model="formData.outcome" placeholder="Select Outcome" class="full-width">
                    <el-option label="Pass" value="pass" />
                    <el-option label="Fail" value="fail" />
                    <el-option label="Partial" value="partial" />
                  </el-select>
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 3: SERVICE DETAILS -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">
                <el-icon><EditPen /></el-icon>
              </span>
              <span class="section-title">Service Details</span>
            </div>
            
            <div class="form-row">
              <div class="form-col" style="grid-column: span 2;">
                <el-form-item label="Action Taken *" prop="action_taken">
                  <el-input v-model="formData.action_taken" placeholder="Describe the action taken" />
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
                    placeholder="Detailed description of the service performed"
                  />
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 4: PERSONNEL -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">
                <el-icon><User /></el-icon>
              </span>
              <span class="section-title">Personnel</span>
            </div>
            
            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Service Provider" prop="service_provider">
                  <el-input v-model="formData.service_provider" placeholder="Enter provider name" />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Technician" prop="technician_id">
                  <el-select v-model="formData.technician_id" placeholder="Select Technician" clearable class="full-width">
                    <el-option
                      v-for="user in users"
                      :key="user.id"
                      :label="user.name"
                      :value="user.id"
                    />
                  </el-select>
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 5: DOWNTIME TRACKING -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">
                <el-icon><Timer /></el-icon>
              </span>
              <span class="section-title">Downtime Tracking</span>
            </div>
            
            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Downtime Start" prop="downtime_start">
                  <el-date-picker
                    v-model="formData.downtime_start"
                    type="datetime"
                    placeholder="Select start time"
                    class="full-width"
                    value-format="YYYY-MM-DD HH:mm:ss"
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Downtime End" prop="downtime_end">
                  <el-date-picker
                    v-model="formData.downtime_end"
                    type="datetime"
                    placeholder="Select end time"
                    class="full-width"
                    value-format="YYYY-MM-DD HH:mm:ss"
                  />
                </el-form-item>
              </div>
            </div>
          </div>

          <!-- SECTION 6: COST & ATTACHMENTS -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">
                <el-icon><Money /></el-icon>
              </span>
              <span class="section-title">Cost & Attachments</span>
            </div>
            
            <div class="form-row">
              <div class="form-col">
                <el-form-item label="Cost" prop="cost">
                  <el-input-number 
                    v-model="formData.cost" 
                    :precision="2" 
                    :step="0.01" 
                    :min="0"
                    class="full-width"
                    placeholder="0.00"
                  />
                </el-form-item>
              </div>
              <div class="form-col">
                <el-form-item label="Attachments">
                   <el-upload
                      class="upload-demo"
                      action="#"
                      :auto-upload="false"
                      :on-change="handleFileChange"
                      :file-list="fileList"
                      limit="1"
                    >
                      <el-button type="primary">Select File</el-button>
                    </el-upload>
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
              {{ saving ? 'Saving...' : (isEditing ? 'Update Service Log' : 'Add Service Log') }}
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </Teleport>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { 
  Document, 
  Monitor, 
  Calendar, 
  EditPen, 
  User, 
  Timer, 
  Money,
  Close,
  Check
} from '@element-plus/icons-vue'
import api from '@/utils/axios'
import { ElMessage } from 'element-plus'
import { format, parseISO } from 'date-fns'

const props = defineProps({
  visible: {
    type: Boolean,
    required: true
  },
  logData: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'save'])

const formRef = ref(null)
const loading = ref(false)
const saving = ref(false)
const equipmentList = ref([])
const users = ref([])
const fileList = ref([])
const selectedFile = ref(null)

const formData = reactive({
  id: null,
  equipment_id: '',
  maintenance_schedule_id: null,
  service_type: '',
  service_date: '',
  outcome: '',
  action_taken: '',
  description: '',
  service_provider: '',
  technician_id: null,
  downtime_start: null,
  downtime_end: null,
  cost: 0,
  updated_at: null
})

const isEditing = computed(() => !!formData.id)

const formRules = reactive({
  equipment_id: [{ required: true, message: 'Please select equipment', trigger: 'change' }],
  service_type: [{ required: true, message: 'Please select service type', trigger: 'change' }],
  service_date: [{ required: true, message: 'Please select service date', trigger: 'change' }],
  outcome: [{ required: true, message: 'Please select outcome', trigger: 'change' }],
  action_taken: [{ required: true, message: 'Please enter action taken', trigger: 'blur' }]
})

// Fetch data
const fetchEquipment = async () => {
  try {
    const response = await api.get('eim/equipment')
    equipmentList.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching equipment:', error)
  }
}

const fetchUsers = async () => {
  try {
    const response = await api.get('users')
    users.value = response.data || []
  } catch (error) {
    console.error('Error fetching users:', error)
  }
}

onMounted(() => {
  fetchEquipment()
  fetchUsers()
})

watch(() => props.visible, (newVal) => {
  if (newVal) {
    if (props.logData) {
      Object.keys(formData).forEach(key => {
        if (key in props.logData) {
          formData[key] = props.logData[key]
        }
      })
      formData.id = props.logData.id
      formData.updated_at = props.logData.updated_at
      
      // If there are existing files, we might need to handle them, but for now reset file list
      fileList.value = []
    } else {
      resetForm()
    }
  }
})

const resetForm = () => {
  if (formRef.value) formRef.value.resetFields()
  formData.id = null
  formData.equipment_id = ''
  formData.maintenance_schedule_id = null
  formData.service_type = ''
  formData.service_date = ''
  formData.outcome = ''
  formData.action_taken = ''
  formData.description = ''
  formData.service_provider = ''
  formData.technician_id = null
  formData.downtime_start = null
  formData.downtime_end = null
  formData.cost = 0
  formData.updated_at = null
  fileList.value = []
  selectedFile.value = null
}

const handleFileChange = (file) => {
  selectedFile.value = file.raw
  fileList.value = [file]
}

const handleClose = () => {
  resetForm()
  if (formRef.value) {
    formRef.value.clearValidate()
  }
  emit('close')
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  try {
    return format(parseISO(date), 'MMM dd, yyyy HH:mm')
  } catch {
    return date
  }
}

const handleSubmit = async () => {
  if (!formRef.value) return
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      try {
        saving.value = true
        
        // Use FormData for file upload
        const payload = new FormData()
        Object.keys(formData).forEach(key => {
          if (formData[key] !== null && formData[key] !== undefined && key !== 'id' && key !== 'updated_at') {
            payload.append(key, formData[key])
          }
        })
        
        if (selectedFile.value) {
          payload.append('attachment', selectedFile.value)
        }
        
        if (isEditing.value) {
          await api.post(`eim/service-logs/${formData.id}?_method=PUT`, payload, {
            headers: { 'Content-Type': 'multipart/form-data' }
          })
          ElMessage.success('Service log updated successfully')
        } else {
          await api.post('eim/service-logs', payload, {
            headers: { 'Content-Type': 'multipart/form-data' }
          })
          ElMessage.success('Service log created successfully')
        }
        
        emit('save')
        handleClose()
      } catch (error) {
        console.error('Error saving service log:', error)
        if (error.response?.data?.message) {
          ElMessage.error(error.response.data.message)
        } else {
          ElMessage.error('Failed to save service log')
        }
      } finally {
        saving.value = false
      }
    }
  })
}
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
  display: flex;
  align-items: center;
  font-size: var(--font-size-xl);
  color: var(--color-text-light);
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
  display: flex;
  align-items: center;
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

.full-width {
  width: 100%;
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
