<template>
  <el-row :gutter="20">
    <el-col :span="12">
      <el-card>
        <div slot="header"><b>Add New Request</b></div>
        <el-table :data="form.new_requests" style="width: 100%">
          <el-table-column prop="test_name" label="Test Name" />
        </el-table>
      </el-card>
    </el-col>
    <el-col :span="12">
      <el-card>
        <div slot="header"><b>View Previous Result</b></div>
        <el-form :model="form" label-width="120px">
          <el-form-item label="Chest X Ray">
            <el-input v-model="form.chest_xray" />
          </el-form-item>
          <el-form-item label="Urinalysis">
            <el-input v-model="form.urinalysis" />
          </el-form-item>
          <el-form-item label="CBC">
            <el-input v-model="form.cbc" />
          </el-form-item>
          <el-form-item label="ECG">
            <el-input v-model="form.ecg" />
          </el-form-item>
          <el-form-item label="Stool Exam">
            <el-input v-model="form.stool_exam" />
          </el-form-item>
          <el-form-item label="Drug Test">
            <el-input v-model="form.drug_test" />
          </el-form-item>
          <el-form-item label="HBSAG">
            <el-input v-model="form.hbsag" />
          </el-form-item>
          <el-form-item label="Chemistry">
            <el-input v-model="form.chemistry" />
          </el-form-item>
          <el-form-item label="Blood Type">
            <el-input v-model="form.blood_type" />
          </el-form-item>
        </el-form>
      </el-card>
    </el-col>
  </el-row>
  <el-form-item label="Others">
    <el-input v-model="form.others" type="textarea" rows="2" />
  </el-form-item>
  <el-form-item>
    <el-button type="primary" @click="save">Save</el-button>
  </el-form-item>
</template>
<script setup>
import { ref } from 'vue';
const props = defineProps({ consultation: Object });
const emit = defineEmits(['save']);
const formRef = ref();
const form = ref({
  new_requests: [],
  chest_xray: '',
  urinalysis: '',
  cbc: '',
  ecg: '',
  stool_exam: '',
  drug_test: '',
  hbsag: '',
  chemistry: '',
  blood_type: '',
  others: ''
});
function save() { emit('save', form.value); }
</script> 