<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="visible"
      title="Add Digital Signature"
      width="500px"
      :before-close="handleClose"
      class="digital-signature-modal"
      destroy-on-close
    >
      <template #header>
        <div class="modal-header">
          <el-icon class="modal-icon"><EditPen /></el-icon>
          <span>Add Digital Signature</span>
        </div>
      </template>

      <el-form 
        ref="formRef" 
        :model="form" 
        :rules="rules" 
        label-width="120px"
        class="signature-form"
      >
        <div class="form-section">
          <el-form-item label="Signature Type" prop="type">
            <el-radio-group v-model="form.type">
              <el-radio label="technician">Technician Signature</el-radio>
              <el-radio label="reviewer">Reviewer Signature</el-radio>
            </el-radio-group>
          </el-form-item>
          
          <el-form-item label="Digital Signature" prop="signature">
            <el-input 
              v-model="form.signature" 
              type="textarea" 
              :rows="4"
              placeholder="Enter your digital signature (e.g. Full Name)"
            />
          </el-form-item>
        </div>
      </el-form>

      <template #footer>
        <div class="modal-footer">
          <el-button @click="handleClose">Cancel</el-button>
          <el-button type="primary" :loading="saving" @click="handleSave">
            Save Signature
          </el-button>
        </div>
      </template>
    </el-dialog>
  </Teleport>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { EditPen } from '@element-plus/icons-vue'
import api from '@/utils/axios'
import { ElMessage } from 'element-plus'

const props = defineProps({
  visible: {
    type: Boolean,
    required: true
  },
  logId: {
    type: [Number, String],
    required: true
  }
})

const emit = defineEmits(['close', 'save'])

const formRef = ref(null)
const saving = ref(false)

const form = reactive({
  type: 'technician',
  signature: ''
})

const rules = {
  type: [{ required: true, message: 'Please select signature type', trigger: 'change' }],
  signature: [{ required: true, message: 'Please enter signature', trigger: 'blur' }]
}

const handleClose = () => {
  if (formRef.value) formRef.value.resetFields()
  form.type = 'technician'
  form.signature = ''
  emit('close')
}

const handleSave = async () => {
  if (!formRef.value) return
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      try {
        saving.value = true
        
        await api.post(`eim/service-logs/${props.logId}/sign`, {
          signature_type: form.type,
          signature: form.signature
        })
        
        ElMessage.success('Signature added successfully')
        emit('save')
        handleClose()
      } catch (error) {
        console.error('Error saving signature:', error)
        ElMessage.error('Failed to save signature')
      } finally {
        saving.value = false
      }
    }
  })
}
</script>

<style scoped>
.modal-header {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.modal-icon {
  font-size: 1.5rem;
  color: var(--el-color-primary);
}

.form-section {
  padding: 10px 0;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 20px;
  border-top: 1px solid var(--el-border-color-lighter);
}
</style>
