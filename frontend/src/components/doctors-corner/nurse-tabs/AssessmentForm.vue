<template>
  <el-form :model="form" ref="formRef" label-width="180px" label-position="top">
    <el-form-item label="Assessment of Fitness">
      <el-radio-group v-model="form.fitness_class">
        <el-radio :label="'A'">Class A - Physically fit for any work</el-radio>
        <el-radio :label="'B'">Class B - Physically under-developed or with correctible defects, (error of refraction dental caries, defective hearing, and other similar defects) but otherwise fit to work</el-radio>
        <el-radio :label="'C'">Class C - Employable but owing to certain impairments or conditions, (heart disease, hypertension, anatomical defects) requires special placement or limited duty in a specified or</el-radio>
        <el-radio :label="'D'">Class D - Unfit or unsafe for any type of employment (active PTB, advanced heart disease with threatened failure, malignant hypertension, and other similar illnesses)</el-radio>
      </el-radio-group>
    </el-form-item>
    <el-form-item label="Remarks">
      <el-input v-model="form.remarks" type="textarea" rows="4" />
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
const form = ref({ fitness_class: '', remarks: '' });
function save() { emit('save', form.value); }
</script> 