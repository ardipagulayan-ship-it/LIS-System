<template>
  <el-form :model="form" ref="formRef" label-width="180px" label-position="top">
    <el-form-item label="Prescription">
      <el-input v-model="form.prescription" type="textarea" rows="4" />
    </el-form-item>
    <!-- Add more prescription fields as needed -->
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
const form = ref({ prescription: '' }); // Add more fields as needed
function save() { emit('save', form.value); }
</script> 