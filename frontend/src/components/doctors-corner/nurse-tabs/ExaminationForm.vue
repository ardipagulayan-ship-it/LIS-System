<template>
  <el-form :model="form" ref="formRef" label-width="180px" label-position="top">
    <el-form-item label="General Survey">
      <el-input v-model="form.general_survey" />
    </el-form-item>
    <el-form-item label="Blood Pressure">
      <el-input v-model="form.blood_pressure" />
    </el-form-item>
    <el-form-item label="Respiratory Rate">
      <el-input v-model="form.respiratory_rate" />
    </el-form-item>
    <el-form-item label="Oxygen Saturation">
      <el-input v-model="form.oxygen_saturation" />
    </el-form-item>
    <el-form-item label="Heart Rate">
      <el-input v-model="form.heart_rate" />
    </el-form-item>
    <el-form-item label="Temperature">
      <el-input v-model="form.temperature" />
    </el-form-item>
    <el-form-item label="Skin">
      <el-input v-model="form.skin" />
    </el-form-item>
    <el-form-item label="HEENT">
      <el-input v-model="form.heent" />
    </el-form-item>
    <el-form-item label="Heart">
      <el-input v-model="form.heart" />
    </el-form-item>
    <el-form-item label="Chest and Lungs">
      <el-input v-model="form.chest_and_lungs" />
    </el-form-item>
    <el-form-item label="Abdomen">
      <el-input v-model="form.abdomen" />
    </el-form-item>
    <el-form-item label="Genito-Urinary">
      <el-input v-model="form.genito_urinary" />
    </el-form-item>
    <el-form-item label="Extremities">
      <el-input v-model="form.extremities" />
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
const form = ref({ general_survey: '', blood_pressure: '', respiratory_rate: '', oxygen_saturation: '', heart_rate: '', temperature: '', skin: '', heent: '', heart: '', chest_and_lungs: '', abdomen: '', genito_urinary: '', extremities: '' });
function save() { emit('save', form.value); }
</script> 