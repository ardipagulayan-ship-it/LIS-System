<template>
  <el-form :model="form" ref="formRef" label-width="180px" label-position="top">
    <el-form-item label="Doctor's Note">
      <el-input v-model="form.doctor_note" type="textarea" rows="4" />
    </el-form-item>
    <!-- Add more doctor's note fields as needed -->
    <el-form-item>
      <el-button type="primary" @click="save">Save</el-button>
    </el-form-item>
  </el-form>
</template>
<script setup>
import { ref } from 'vue';
const props = defineProps({ consultation: Object });
const emit = defineEmits(['save']);
const formRef = ref();
const form = ref({ doctor_note: '' }); // Add more fields as needed
function save() { emit('save', form.value); }
</script> 