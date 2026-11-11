<template>
  <el-form :model="form" ref="formRef" label-width="180px" label-position="top">
    <el-form-item>
      <el-radio-group v-model="form.certificate_type">
        <el-radio :label="'Sick Leave'">Sick Leave</el-radio>
        <el-radio :label="'Employment'">Employment</el-radio>
        <el-radio :label="'Fit to Work'">Fit to Work</el-radio>
        <el-radio :label="'Medical Assistance'">Medical Assistance</el-radio>
      </el-radio-group>
    </el-form-item>
    <el-form-item label="Diagnosis">
      <el-input v-model="form.diagnosis" type="textarea" rows="2" />
    </el-form-item>
    <el-row :gutter="20">
      <el-col :span="12">
        <el-form-item label="Reason of Consultation">
          <el-input v-model="form.reason_of_consultation" />
        </el-form-item>
        <el-form-item label="Recovery Duration">
          <el-input v-model="form.recovery_duration" />
        </el-form-item>
      </el-col>
      <el-col :span="12">
        <el-form-item label="Examined / Treated On">
          <el-input v-model="form.examined_on" />
        </el-form-item>
        <el-form-item label="Physically Fit For">
          <el-radio-group v-model="form.physically_fit_for">
            <el-radio :label="'Enrollment'">Enrollment</el-radio>
            <el-radio :label="'Employment'">Employment</el-radio>
            <el-radio :label="'Others'">Others</el-radio>
          </el-radio-group>
          <el-input v-if="form.physically_fit_for === 'Others'" v-model="form.physically_fit_for_others" placeholder="Specify" />
        </el-form-item>
      </el-col>
    </el-row>
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
const form = ref({
  certificate_type: '',
  diagnosis: '',
  reason_of_consultation: '',
  examined_on: '',
  recovery_duration: '',
  physically_fit_for: '',
  physically_fit_for_others: '',
  remarks: ''
});
function save() { emit('save', form.value); }
</script> 