<!-- ============================================================================
   REUSABLE MODAL TEMPLATE
   Use this template as a base for all modals throughout the application
   ============================================================================ -->

<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="props.visible"
      :title="modalTitle"
      width="900px"
      :close-on-click-modal="false"
      :show-close="false"
      class="app-modal"
      v-loading="loading"
    >
      <!-- Modal Header -->
      <template #header>
        <div class="modal-header">
          <div class="modal-header-brand">
            <span class="modal-header-icon">{{ modalIcon }}</span>
            <span class="modal-header-title">{{ modalTitle }}</span>
          </div>
          <div class="modal-header-actions">
            <el-button type="danger" @click="handleClose" size="small">
              <el-icon><Close /></el-icon>
              Close
            </el-button>
          </div>
        </div>
      </template>

      <!-- Modal Content -->
      <div class="modal-content">
        <!-- Add your form or content here -->
        <el-form
          ref="formRef"
          :model="formData"
          :rules="formRules"
          label-width="140px"
          class="app-form"
        >
          <!-- Form sections go here -->
        </el-form>
      </div>

      <!-- Modal Footer -->
      <template #footer>
        <div class="modal-footer">
          <div class="footer-info">
            <!-- Optional: Display metadata like last modified -->
          </div>
          <div class="footer-actions">
            <el-button @click="handleClose" class="btn-secondary">
              <el-icon><Close /></el-icon>
              Cancel
            </el-button>
            <el-button
              type="primary"
              @click="handleSubmit"
              :loading="saving"
            >
              <el-icon><Check /></el-icon>
              {{ saving ? 'Saving...' : 'Save' }}
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
  data: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'save']);

const formRef = ref(null);
const loading = ref(false);
const saving = ref(false);

const formData = reactive({
  // Define your form fields here
});

const formRules = reactive({
  // Define your validation rules here
});

const modalTitle = computed(() => formData.id ? 'Edit Item' : 'Add New Item');
const modalIcon = '📋';

watch(() => props.data, (newData) => {
  if (newData) {
    // Populate form with data
    Object.keys(newData).forEach(key => {
      if (key in formData) {
        formData[key] = newData[key];
      }
    });
  } else {
    // Reset form
    resetForm();
  }
}, { immediate: true, deep: true });

const resetForm = () => {
  Object.keys(formData).forEach(key => {
    formData[key] = null;
  });
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();
    saving.value = true;

    // API call logic here
    ElMessage.success('Saved successfully');
    emit('save', formData);
    handleClose();
  } catch (error) {
    ElMessage.error('Failed to save');
    console.error('Error:', error);
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
.app-modal :deep(.el-dialog__header) {
  padding: 0;
  border-bottom: none;
}

.app-modal :deep(.el-dialog__body) {
  padding: 0;
  max-height: calc(100vh - 200px);
  overflow-y: auto;
}

.app-modal :deep(.el-form-item__label) {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: var(--color-text-secondary);
}

/* Ensure consistent button styling */
:deep(.el-button) {
  font-weight: var(--font-weight-medium);
  border-radius: var(--border-radius-md);
  transition: all var(--transition-fast);
}

:deep(.el-button:hover) {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}
</style>
