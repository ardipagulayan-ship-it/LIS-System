<template>
  <el-dialog v-model="visible" title="New Consultation" width="700px" @close="resetForm">
        <div class="section patient-details">
            <h3>Patient Details</h3>
            <el-form :model="selectedPatient" label-position="left" ref="formRef" label-width="120px" class="compact-form">
            <div style="flex: 1">
                <el-form-item label="Patient ID:">
                    <div class="search-input">
                        <el-input v-model="selectedPatient.id" style="width: 100%" readonly>
                        <template #append>
                            <el-button :icon="Search" @click="showPatientSearch = true" />
                        </template>
                    </el-input>
                    </div>
                </el-form-item>
                <el-form-item label="Last Name:">
                <el-input v-model="selectedPatient.last_name" style="width: 100%" readonly/>
                </el-form-item>
                <el-form-item label="First Name:">
                <el-input v-model="selectedPatient.first_name" style="width: 100%" readonly/>   
                </el-form-item>
                <el-form-item label="Middle Name:">
                <el-input v-model="selectedPatient.middle_name" style="width: 100%" readonly/>
                </el-form-item>
                <el-form-item label="Date of Birth:">
                <el-date-picker
                    v-model="selectedPatient.date_of_birth"
                    type="date"
                    placeholder="Select date"
                    style="width: 100%"
                    readonly
                />
                </el-form-item>
                <el-form-item label="Age:">
                <div style="display: flex; width: 100%">
                    <el-input v-model="selectedPatient.age" style="flex: 1; margin-right: 5px" readonly/>
                    <el-select v-model="selectedPatient.age_unit" style="width: 100px" readonly>
                    <el-option label="Year(s)" value="Year(s)" />
                    <el-option label="Month(s)" value="Month(s)" />
                    <el-option label="Day(s)" value="Day(s)" />
                    </el-select>
                </div>
                </el-form-item>
            </div>
            <div style="flex: 1">
                <el-form-item label="Sex:">
                <el-select v-model="selectedPatient.gender" placeholder="Select sex" style="width: 100%" readonly>
                    <el-option label="Male" value="male" />
                    <el-option label="Female" value="female" />
                </el-select>
                </el-form-item>
                <el-form-item label="Civil Status:">
                <el-select v-model="selectedPatient.civil_status" placeholder="Select status" style="width: 100%" readonly>
                    <el-option label="Single" value="single" />
                    <el-option label="Married" value="married" />
                    <el-option label="Widowed" value="widowed" />
                    <el-option label="Divorced" value="divorced" />
                </el-select>  
                </el-form-item>
                <el-form-item label="Address:">
                <el-input v-model="selectedPatient.address" type="textarea" style="width: 100%" readonly/>
                </el-form-item>
                <el-form-item label="Contact No:">
                <el-input v-model="selectedPatient.phone" style="width: 100%" readonly/>
                </el-form-item>
                <el-form-item label="Email Address:">
                <el-input v-model="selectedPatient.email" style="width: 100%" readonly/>
                </el-form-item>
            </div>
            </el-form>
        </div>
        <div class="section additional-details">
            <h3>Reason of Visit</h3>
            <el-form :model="form" label-position="left" ref="formRef" label-width="150px">
                <el-form-item label="Reason" prop="reason">
                    <el-input v-model="form.reason" type="textarea" placeholder="Reason for consultation" />
                </el-form-item>
                <el-form-item label="Urgency" prop="urgency">
                    <el-select v-model="form.urgency" placeholder="Select urgency">
                    <el-option label="Routine" value="Routine" />
                    <el-option label="Urgent" value="Urgent" />
                    <el-option label="STAT" value="STAT" />
                    </el-select>
                </el-form-item>
                <el-form-item label="Attending Doctor" prop="referring_physician_id">
                    <el-input v-model="form.referring_physician_id" placeholder="Optional" />
                </el-form-item>
                <el-form-item label="Consultation Type" prop="consultation_type">
                    <el-select v-model="form.consultation_type" placeholder="Select type">
                    <el-option label="General Consultation" value="General Consultation" />
                    <el-option label="Follow up" value="Follow up" />
                    <el-option label="Obgyne" value="Obgyne" />
                    <el-option label="Pediatric" value="Pediatric" />
                    </el-select>
                </el-form-item>
            </el-form>
        </div>
    <template #footer>
      <el-button @click="visible = false">Cancel</el-button>
      <el-button type="primary" @click="submitForm">{{ props.consultation ? 'Update' : 'Create' }}</el-button>
      <el-button v-if="props.consultation" type="danger" @click="deleteConsultation">Delete</el-button>
    </template>
    <PatientSearchModal v-model="showPatientSearch" @select-patient="onSelectPatient" />
  </el-dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import api from '@/utils/axios';
import { ElMessage } from 'element-plus';
import { Check, Delete, Close, Search } from '@element-plus/icons-vue';
import PatientSearchModal from '@/components/modals/patient/PatientSearchModal.vue';

const props = defineProps({
  modelValue: Boolean,
  consultation: Object
});
const emit = defineEmits(['update:modelValue', 'created', 'updated', 'deleted']);

const visible = ref(false);
watch(() => props.modelValue, val => visible.value = val);
watch(visible, val => emit('update:modelValue', val));

const showPatientSearch = ref(false);
const selectedPatient = ref({});

const formRef = ref();
const form = ref({
  reason: '',
  urgency: '',
  referring_physician_id: '',
  consultation_type: ''
});
const rules = {
  reason: [{ required: true, message: 'Reason required', trigger: 'blur' }],
  urgency: [{ required: true, message: 'Urgency required', trigger: 'change' }],
  consultation_type: [{ required: true, message: 'Type required', trigger: 'change' }],
};

watch(() => props.consultation, (val) => {
  if (val) {
    form.value = {
      reason: val.reason || '',
      urgency: val.urgency || '',
      referring_physician_id: val.referring_physician_id || '',
      consultation_type: val.consultation_type || ''
    };
    selectedPatient.value = val.patient || {};
  } else {
    resetForm();
  }
}, { immediate: true });

// Make sure calculateAge, resetPatientForm, resetAdditionalForm, clearTestSelections, fetchDoctors etc. exist
// Add calculateAge function if it doesn't exist
// Computed property to calculate age in the most appropriate unit
const computedAge = computed(() => {
  if (!selectedPatient.value.date_of_birth) return { value: '', unit: 'Year(s)' };
  
  const today = new Date();
  const birthDate = new Date(selectedPatient.value.date_of_birth);
  
  let years = today.getFullYear() - birthDate.getFullYear();
  let months = today.getMonth() - birthDate.getMonth();
  let days = today.getDate() - birthDate.getDate();

  if (days < 0) {
    months--;
    const lastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
    days += lastMonth.getDate();
  }

  if (months < 0) {
    years--;
    months += 12;
  }

  // Determine which unit to display based on age
  if (years >= 1) {
    return { value: years, unit: 'Year(s)' };
  } else if (months >= 1) {
    return { value: months, unit: 'Month(s)' };
  } else {
    return { value: days >= 0 ? days : 0, unit: 'Day(s)' };
  }
});

// Watch for changes in dateOfBirth to update age
watch(() => selectedPatient.value.date_of_birth, () => {
  const age = computedAge.value;
  selectedPatient.value.age = age.value;
  selectedPatient.value.age_unit = age.unit;
}, { immediate: true });

function resetForm() {
  form.value = { reason: '', urgency: '', referring_physician_id: '', consultation_type: '' };
  selectedPatient.value = {};
}

function onSelectPatient(patient) {
  selectedPatient.value = patient;
  showPatientSearch.value = false;
}

async function submitForm() {
  await formRef.value.validate(async (valid) => {
    if (!valid) return;
    if (!selectedPatient.value.id) {
      ElMessage.error('Please select a patient');
      return;
    }
    try {
      const payload = { ...form.value, patient_id: selectedPatient.value.id };
      delete payload.consultation_type;
      let res;
      if (props.consultation && props.consultation.id) {
        res = await api.put(`/consultations/${props.consultation.id}`, payload);
        ElMessage.success('Consultation updated');
        emit('updated', res.data);
      } else {
        res = await api.post('/consultations', payload);
        ElMessage.success('Consultation created');
        emit('created', res.data);
      }
      visible.value = false;
      resetForm();
    } catch (e) {
      ElMessage.error('Failed to save consultation');
    }
  });
}

async function deleteConsultation() {
  if (!props.consultation || !props.consultation.id) return;
  ElMessageBox.confirm('Are you sure you want to delete this consultation?', 'Delete Consultation', {
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel',
    type: 'warning',
  }).then(async () => {
    await api.delete(`/consultations/${props.consultation.id}`);
    ElMessage.success('Consultation deleted');
    emit('deleted', props.consultation);
    visible.value = false;
    resetForm();
  }).catch(() => {});
}
</script> 

<style scoped>
.request-dialog {
  display: flex;
  flex-direction: column;
}

.dialog-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 16px;
  background-color: #f5f7fa;
  border-bottom: 1px solid #dcdfe6;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.order-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 4px;
  overflow-y: auto;
}

.details-sections {
  display: flex;
  gap: 20px;
  margin-bottom: 0px;
}

.section {
  background-color: #fff;
  border-radius: 4px;
  padding: 16px;
  border: 1px solid #dcdfe6;
}

.patient-details {
  flex: 1;
}

.additional-details {
  flex: 1;
}

.test-items {
  flex: 1;
}

h3 {
  font-size: 16px;
  font-weight: bold;
  color: var(--color-primary);
  margin-bottom: 16px;
  padding-bottom: 8px;
  border-bottom: 2px solid var(--color-primary);
}

.age-input {
  display: flex;
  align-items: center;
  gap: 10px;
}

.search-input {
  display: flex;
  align-items: center;
}

.checkbox-group {
  display: flex;
  gap: 20px;
}

.summary-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.distributions,
.computations {
  background: #f5f7fa;
  padding: 20px;
  border-radius: 4px;
}

.compact-form {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  align-items: center;
}

.form-row {
  display: flex;
  gap: 12px;
  align-items: center;
}

.compact-form :deep(.el-form-item) {
  margin-bottom: 8px;
  margin-right: 0;
  display: flex;
  align-items: center;
}

.compact-form :deep(.el-form-item__label) {
  font-size: 13px;
  color: #606266;
  padding-right: 12px;
  line-height: 32px;
  white-space: nowrap;
}

.compact-form :deep(.el-input__wrapper),
.compact-form :deep(.el-select),
.compact-form :deep(.el-date-editor.el-input),
.compact-form :deep(.el-date-editor.el-input__wrapper) {
  height: 32px;
  line-height: 32px;
  box-shadow: none;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
}

.compact-form :deep(.el-input__inner) {
  height: 32px;
  line-height: 32px;
  font-size: 13px;
}
</style> 
