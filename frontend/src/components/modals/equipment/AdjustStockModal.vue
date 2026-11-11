<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="visible"
      title="Adjust Stock Level"
      width="500px"
      :before-close="handleClose"
      class="equipment-modal"
      destroy-on-close
    >
      <template #header>
        <div class="modal-header">
          <div class="header-icon">
            <el-icon><Sort /></el-icon>
          </div>
          <div class="header-title">
            <h3>Adjust Stock Level</h3>
            <span class="header-subtitle">
              Add or deduct stock from inventory
            </span>
          </div>
        </div>
      </template>

      <div class="modal-body">
        <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          label-position="top"
          class="equipment-form"
        >
          <div class="form-section">
            <div class="form-grid">
              <div class="form-row">
                <div class="form-col full-width">
                  <el-form-item label="Current Stock">
                    <el-input :value="currentStock" disabled />
                  </el-form-item>
                </div>
              </div>

              <div class="form-row">
                <div class="form-col full-width">
                  <el-form-item label="Adjustment Type" prop="type">
                    <el-radio-group v-model="form.type" style="width: 100%">
                      <el-radio-button label="add">Add Stock</el-radio-button>
                      <el-radio-button label="deduct">Deduct Stock</el-radio-button>
                    </el-radio-group>
                  </el-form-item>
                </div>
              </div>

              <div class="form-row">
                <div class="form-col full-width">
                  <el-form-item label="Quantity" prop="quantity">
                    <el-input-number 
                      v-model="form.quantity" 
                      :min="0.01" 
                      :precision="2" 
                      style="width: 100%" 
                    />
                  </el-form-item>
                </div>
              </div>

              <div class="form-row">
                <div class="form-col full-width">
                  <el-form-item label="Notes" prop="notes">
                    <el-input 
                      v-model="form.notes" 
                      type="textarea" 
                      :rows="3" 
                      placeholder="Optional notes about this adjustment" 
                    />
                  </el-form-item>
                </div>
              </div>
            </div>
          </div>
        </el-form>
      </div>

      <template #footer>
        <div class="dialog-footer">
          <el-button @click="handleClose">Cancel</el-button>
          <el-button type="primary" :loading="saving" @click="handleSave">
            Apply
          </el-button>
        </div>
      </template>
    </el-dialog>
  </Teleport>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { Sort } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import api from '@/utils/axios'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  consumableData: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:visible', 'close', 'save'])

const formRef = ref(null)
const saving = ref(false)
const currentStock = ref(0)

const form = reactive({
  id: null,
  type: 'add',
  quantity: 0,
  notes: ''
})

const rules = {
  type: [{ required: true, message: 'Please select adjustment type', trigger: 'change' }],
  quantity: [{ required: true, message: 'Please enter quantity', trigger: 'blur' }]
}

watch(() => props.visible, (newVal) => {
  if (newVal && props.consumableData) {
    form.id = props.consumableData.id
    currentStock.value = props.consumableData.current_stock_level
    form.type = 'add'
    form.quantity = 0
    form.notes = ''
  }
})

const handleClose = () => {
  emit('update:visible', false)
  emit('close')
}

const handleSave = async () => {
  if (!formRef.value) return
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      try {
        saving.value = true
        
        const endpoint = form.type === 'add' ? 'add-stock' : 'deduct-stock'
        await api.post(`eim/consumables/${form.id}/${endpoint}`, {
          quantity: form.quantity,
          notes: form.notes
        })
        
        ElMessage.success('Stock adjusted successfully')
        emit('save')
        handleClose()
      } catch (error) {
        console.error('Error adjusting stock:', error)
        ElMessage.error(error.response?.data?.message || 'Failed to adjust stock')
      } finally {
        saving.value = false
      }
    }
  })
}
</script>

<style scoped>
.equipment-modal :deep(.el-dialog__header) {
  margin: 0;
  padding: 0;
  border-bottom: 1px solid var(--border-color-light);
}

.modal-header {
  display: flex;
  align-items: center;
  padding: 20px 24px;
  background-color: var(--background-color-white);
}

.header-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background-color: var(--color-primary-light-9);
  color: var(--color-primary);
  margin-right: 16px;
  font-size: 24px;
}

.header-title h3 {
  margin: 0 0 4px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--text-color-primary);
}

.header-subtitle {
  font-size: 13px;
  color: var(--text-color-secondary);
}

.modal-body {
  padding: 24px;
  max-height: 60vh;
  overflow-y: auto;
}

.form-section {
  background-color: var(--background-color-white);
  border: 1px solid var(--border-color-light);
  border-radius: 8px;
  padding: 20px;
}

.form-grid {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-row {
  display: flex;
  gap: 20px;
}

.form-col {
  flex: 1;
  min-width: 0;
}

.full-width {
  width: 100%;
}

.dialog-footer {
  padding: 20px 24px;
  border-top: 1px solid var(--border-color-light);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  background-color: var(--background-color-gray-light);
}
</style>
