<template>
  <el-dialog
    :model-value="modelValue"
    title="Update Request Status"
    width="500px"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-width="120px"
    >
      <el-form-item label="Status" prop="status">
        <el-select v-model="form.status" placeholder="Select status">
          <el-option label="Pending" value="Pending" />
          <el-option label="In Progress" value="In Progress" />
          <el-option label="Completed" value="Completed" />
          <el-option label="Cancelled" value="Cancelled" />
        </el-select>
      </el-form-item>
      <el-form-item label="Notes" prop="notes">
        <el-input
          v-model="form.notes"
          type="textarea"
          :rows="3"
          placeholder="Enter status update notes"
        />
      </el-form-item>
    </el-form>
    <template #footer>
      <span class="dialog-footer">
        <el-button @click="$emit('update:modelValue', false)">Cancel</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="loading">
          Update Status
        </el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, defineProps, defineEmits } from 'vue';
import { ElMessage } from 'element-plus';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true
  },
  initialStatus: {
    type: String,
    default: 'Pending'
  }
});

const emit = defineEmits(['update:modelValue', 'submit']);

const formRef = ref(null);
const loading = ref(false);

const form = ref({
  status: props.initialStatus,
  notes: ''
});

const rules = {
  status: [{ required: true, message: 'Please select status', trigger: 'change' }]
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();
    loading.value = true;
    emit('submit', form.value);
    emit('update:modelValue', false);
  } catch (error) {
    ElMessage.error('Please check the form fields');
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
</style> 