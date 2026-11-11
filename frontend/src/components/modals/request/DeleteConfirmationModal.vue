<template>
  <el-dialog
    :model-value="modelValue"
    title="Delete Request"
    width="400px"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <p>Are you sure you want to delete this request?</p>
    <template #footer>
      <span class="dialog-footer">
        <el-button @click="$emit('update:modelValue', false)">Cancel</el-button>
        <el-button type="danger" @click="handleConfirm" :loading="loading">
          Delete
        </el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, defineProps, defineEmits } from 'vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true
  }
});

const emit = defineEmits(['update:modelValue', 'confirm']);

const loading = ref(false);

const handleConfirm = async () => {
  loading.value = true;
  try {
    emit('confirm');
    emit('update:modelValue', false);
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