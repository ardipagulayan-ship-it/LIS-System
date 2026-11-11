<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="props.visible"
      title="Quick Actions"
      width="600px"
      :close-on-click-modal="false"
      :show-close="false"
      class="inventory-modal"
    >
      <template #header>
        <div class="modal-header">
          <div class="modal-header-brand">
            <span class="modal-header-icon"></span>
            <span class="modal-header-title">
              QUICK ACTIONS
            </span>
          </div>
          <div class="modal-header-actions">
            <el-button type="danger" @click="handleClose" size="small">
              <el-icon><Close /></el-icon>
              Close
            </el-button>
          </div>
        </div>
      </template>

      <div class="modal-body-content">
        <div class="quick-actions-grid">
          <el-button type="primary" size="large" @click="handleAction('createMaterial')">
            <el-icon><Plus /></el-icon> Add Material
          </el-button>
          <el-button type="success" size="large" @click="handleAction('createLot')">
            <el-icon><Collection /></el-icon> Add Lot
          </el-button>
          <el-button type="warning" size="large" @click="handleAction('createVendor')">
            <el-icon><Shop /></el-icon> Add Vendor
          </el-button>
          <el-button type="info" size="large" @click="handleAction('reorderReport')">
            <el-icon><DataAnalysis /></el-icon> Reorder Report
          </el-button>
        </div>
      </div>

      <template #footer>
        <div class="modal-footer">
          <div class="footer-info"></div>
          <div class="footer-actions">
            <el-button @click="handleClose" class="btn-cancel">
              Close
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </Teleport>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'
import { Close, Plus, Collection, Shop, DataAnalysis } from '@element-plus/icons-vue'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:visible', 'close', 'action'])

const handleClose = () => {
  emit('update:visible', false)
  emit('close')
}

const handleAction = (action) => {
  emit('action', action)
  handleClose()
}
</script>

<style scoped>
/* Reuse the standard modal styles */
.inventory-modal :deep(.el-dialog__header) {
  padding: 0;
  margin: 0;
  display: none;
}

.inventory-modal :deep(.el-dialog__body) {
  padding: 0;
  margin: 0;
  /* Adjust height for this specific modal as it's small */
  min-height: 300px;
  display: flex;
  flex-direction: column;
}

.inventory-modal :deep(.el-dialog__footer) {
  padding: 0;
  margin: 0;
}

.modal-header {
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
  padding: 20px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-header-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.modal-header-icon {
  width: 4px;
  height: 24px;
  background-color: var(--color-secondary);
  border-radius: 2px;
  box-shadow: 0 0 8px rgba(255, 255, 255, 0.3);
}

.modal-header-title {
  font-size: 18px;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.modal-header-actions .el-button {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
  backdrop-filter: blur(4px);
  transition: all 0.3s ease;
}

.modal-header-actions .el-button:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-1px);
}

.modal-body-content {
  flex: 1;
  padding: 24px;
  background-color: #f8f9fa;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
  padding: 20px;
}

.quick-actions-grid .el-button {
  height: 80px;
  font-size: 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  justify-content: center;
  align-items: center;
}

.modal-footer {
  padding: 16px 24px;
  background-color: white;
  border-top: 1px solid var(--color-border-light);
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.03);
}

.footer-actions {
  display: flex;
  gap: 12px;
  width: 100%;
  justify-content: flex-end;
}

.btn-cancel {
  border: 1px solid var(--color-border-base);
  color: var(--color-text-regular);
  padding: 10px 20px;
}
</style>
