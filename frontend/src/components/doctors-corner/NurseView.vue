<template>
  <el-dialog v-model="visible" title="Nurse Consultation Details" width="95vw" top="2vh" @close="resetAllForms">
    <el-tabs v-model="activeTab">
      <el-tab-pane label="Medical History" name="medical">
        <MedicalHistoryForm :consultation="consultation" />
      </el-tab-pane>
      <el-tab-pane label="Family History" name="family">
        <FamilyHistoryForm :consultation="consultation" />
      </el-tab-pane>
      <el-tab-pane label="Maternal History" name="maternal">
        <MaternalHistoryForm :consultation="consultation" />
      </el-tab-pane>
      <el-tab-pane label="Birth History" name="birth">
        <BirthHistoryForm :consultation="consultation" />
      </el-tab-pane>
      <el-tab-pane label="Examination" name="exam">
        <ExaminationForm :consultation="consultation" />
      </el-tab-pane>
      <el-tab-pane label="Diagnostics" name="diagnostics">
        <DiagnosticsForm :consultation="consultation" />
      </el-tab-pane>
      <el-tab-pane label="Assessment" name="assessment">
        <AssessmentForm :consultation="consultation" />
      </el-tab-pane>
      <el-tab-pane label="Medical Certificate" name="certificate">
        <MedicalCertificateForm :consultation="consultation" />
      </el-tab-pane>
      <el-tab-pane label="Doctor's Note" name="note">
        <DoctorNoteForm :consultation="consultation" />
      </el-tab-pane>
      <el-tab-pane label="Prescription" name="prescription">
        <PrescriptionForm :consultation="consultation" />
      </el-tab-pane>
    </el-tabs>
  </el-dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import MedicalHistoryForm from './nurse-tabs/MedicalHistoryForm.vue';
import FamilyHistoryForm from './nurse-tabs/FamilyHistoryForm.vue';
import MaternalHistoryForm from './nurse-tabs/MaternalHistoryForm.vue';
import BirthHistoryForm from './nurse-tabs/BirthHistoryForm.vue';
import ExaminationForm from './nurse-tabs/ExaminationForm.vue';
import DiagnosticsForm from './nurse-tabs/DiagnosticsForm.vue';
import AssessmentForm from './nurse-tabs/AssessmentForm.vue';
import MedicalCertificateForm from './nurse-tabs/MedicalCertificateForm.vue';
import DoctorNoteForm from './nurse-tabs/DoctorNoteForm.vue';
import PrescriptionForm from './nurse-tabs/PrescriptionForm.vue';

const props = defineProps({
  modelValue: Boolean,
  consultation: Object
});
const emit = defineEmits(['update:modelValue', 'saved']);

const visible = ref(false);
const activeTab = ref('medical');

watch(() => props.modelValue, val => visible.value = val);
watch(visible, val => emit('update:modelValue', val));

function resetAllForms() {
  // Optionally reset all child forms
  activeTab.value = 'medical';
}
</script>

<style scoped>
.section {
  background: var(--color-background-light);
  border-radius: 4px;
  padding: 16px;
  margin-bottom: 16px;
  border: 1px solid var(--color-border);
}
h3 {
  font-size: 16px;
  font-weight: bold;
  color: var(--color-primary);
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid var(--color-primary);
}

</style> 