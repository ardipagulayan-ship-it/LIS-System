<template>
  <el-card>
    <h2>Doctors Corner</h2>
    <el-tabs v-model="activeTab">
      <el-tab-pane label="Consultations" name="consultations">
        <ConsultationList @select="handleSelectConsultation" @view="openNurseView" />
      </el-tab-pane>
      <el-tab-pane label="Nurse View" name="nurse">
        <NurseView :consultation="selectedConsultation" />
      </el-tab-pane>
      <el-tab-pane label="Doctor View" name="doctor">
        <DoctorView :consultation="selectedConsultation" />
      </el-tab-pane>
    </el-tabs>
    <NurseView v-model="showNurseModal" :consultation="nurseModalConsultation" @saved="onNurseSaved" />
  </el-card>
</template>

<script setup>
import { ref } from 'vue';
import ConsultationList from '@/components/doctors-corner/ConsultationList.vue';
import NurseView from '@/components/doctors-corner/NurseView.vue';
import DoctorView from '@/components/doctors-corner/DoctorView.vue';

const activeTab = ref('consultations');
const selectedConsultation = ref(null);

const showNurseModal = ref(false);
const nurseModalConsultation = ref(null);

function handleSelectConsultation(consultation) {
  selectedConsultation.value = consultation;
  activeTab.value = 'nurse';
}

function openNurseView(consultation) {
  nurseModalConsultation.value = consultation;
  showNurseModal.value = true;
}

function onNurseSaved() {
  showNurseModal.value = false;
  // Optionally refresh consultations or show a message
}
</script> 