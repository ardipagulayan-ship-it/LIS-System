<template>
  <el-form :model="form" ref="formRef" label-width="120px" label-position="top">
    <el-form-item label="History">
      <el-input v-model="form.history" />
    </el-form-item>
    <el-form-item label="Father">
      <el-input v-model="form.father" />
    </el-form-item>
    <el-form-item label="Mother">
      <el-input v-model="form.mother" />
    </el-form-item>
    <el-form-item label="Others">
      <el-input v-model="form.others" />
    </el-form-item>
    <el-form-item label="Specifics">
      <el-input v-model="form.specifics" />
    </el-form-item>
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
const form = ref({ history: '', father: '', mother: '', others: '', specifics: '' });
function save() { emit('save', form.value); }
</script> 