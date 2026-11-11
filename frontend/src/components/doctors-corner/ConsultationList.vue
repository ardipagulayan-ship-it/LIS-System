<template>
  <el-table :data="consultations" style="width: 100%">
    <el-table-column prop="consultation_id" label="Consultation ID" width="180" />
    <el-table-column prop="patient.last_name" label="Patient" />
    <el-table-column prop="reason" label="Reason" />
    <el-table-column prop="urgency" label="Urgency" width="100" />
    <el-table-column prop="status" label="Status" width="120" />
    <el-table-column prop="created_at" label="Created" width="180" />
    <el-table-column label="Actions" width="180">
      <template #default="{ row }">
        <el-button type="primary" size="small" @click="$emit('view', row)">View</el-button>
        <el-button type="warning" size="small" @click="editConsultation(row)">Edit</el-button>
        <el-button type="danger" size="small" @click="deleteConsultation(row)">Delete</el-button>
      </template>
    </el-table-column>
  </el-table>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/utils/axios';
import { ElMessage, ElMessageBox } from 'element-plus';

const consultations = ref([]);

async function fetchConsultations() {
  const res = await api.get('/consultations');
  consultations.value = res.data.data || [];
}

function handleRowClick(row) {
  emit('select', row);
}

const emit = defineEmits(['select', 'view', 'edit', 'deleted']);

async function editConsultation(row) {
  emit('edit', row);
}

async function deleteConsultation(row) {
  ElMessageBox.confirm('Are you sure you want to delete this consultation?', 'Delete Consultation', {
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel',
    type: 'warning',
  }).then(async () => {
    await api.delete(`/consultations/${row.id}`);
    ElMessage.success('Consultation deleted');
    fetchConsultations();
    emit('deleted', row);
  }).catch(() => {});
}

onMounted(fetchConsultations);
</script> 