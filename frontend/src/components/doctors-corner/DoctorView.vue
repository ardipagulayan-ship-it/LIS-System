<template>
  <div v-if="consultation">
    <h3>Diagnosis</h3>
    <el-form :model="diagnosis" @submit.prevent="saveDiagnosis">
      <el-form-item label="Diagnosis">
        <el-input v-model="diagnosis.diagnosis" />
      </el-form-item>
      <el-form-item label="Clinical Notes">
        <el-input v-model="diagnosis.clinical_notes" type="textarea" />
      </el-form-item>
      <el-button type="primary" @click="saveDiagnosis">Save</el-button>
    </el-form>

    <h3>Prescription</h3>
    <el-form :model="prescription" @submit.prevent="savePrescription">
      <el-form-item label="Prescription">
        <el-input v-model="prescription.prescription" type="textarea" />
      </el-form-item>
      <el-button type="primary" @click="savePrescription">Save</el-button>
    </el-form>
  </div>
  <div v-else>
    <el-empty description="Select a consultation" />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import api from '@/utils/axios';
import { ElMessage } from 'element-plus';

const props = defineProps({ consultation: Object });

const diagnosis = ref({ diagnosis: '', clinical_notes: '' });
const prescription = ref({ prescription: '' });

watch(() => props.consultation, (val) => {
  if (val) {
    diagnosis.value = { diagnosis: '', clinical_notes: '' };
    prescription.value = { prescription: '' };
  }
});

async function saveDiagnosis() {
  if (!props.consultation) return;
  await api.post(`/consultations/${props.consultation.id}/diagnosis`, diagnosis.value);
  ElMessage.success('Diagnosis saved');
}

async function savePrescription() {
  if (!props.consultation) return;
  await api.post(`/consultations/${props.consultation.id}/prescription`, prescription.value);
  ElMessage.success('Prescription saved');
}
</script> 