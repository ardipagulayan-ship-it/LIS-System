<template>
  <el-form :model="form" ref="formRef" label-width="220px" label-position="top">
    <el-row :gutter="20">
      <el-col :span="12">
        <el-form-item label="Chief Complaint">
          <el-input v-model="form.chief_complaint" />
        </el-form-item>
        <el-form-item label="History of Present Illness">
          <el-input v-model="form.history_of_present_illness" type="textarea" rows="3" />
        </el-form-item>
        <el-form-item label="Past Medical History">
          <el-input v-model="form.past_medical_history" type="textarea" rows="3" />
        </el-form-item>
        <el-form-item label="Other Medical Conditions">
          <el-input v-model="form.other_medical_conditions" type="textarea" rows="2" />
        </el-form-item>
      </el-col>
      <el-col :span="12">
        <el-form-item label="Allergies (Food and Medications)">
          <el-input v-model="form.allergies" />
        </el-form-item>
        <el-form-item label="Adverse Drug Reaction">
          <el-input v-model="form.adverse_drug_reaction" />
        </el-form-item>
        <el-form-item label="Blood Transfusion">
          <el-input v-model="form.blood_transfusion" />
        </el-form-item>
        <el-form-item label="Radiation">
          <el-input v-model="form.radiation" />
        </el-form-item>
        <el-form-item label="Previous Hospitalization">
          <el-input v-model="form.previous_hospitalization" />
        </el-form-item>
        <el-form-item label="Previous Operations">
          <el-radio-group v-model="form.previous_operations">
            <el-radio :label="'No'">No</el-radio>
            <el-radio :label="'Yes'">Yes</el-radio>
          </el-radio-group>
          <el-input v-if="form.previous_operations === 'Yes'" v-model="form.previous_operations_details" placeholder="If YES, mention the Operative Procedure, Date and Place of Operation" />
        </el-form-item>
        <el-form-item label="Current Medications">
          <el-input v-model="form.current_medications" type="textarea" rows="2" />
        </el-form-item>
        <el-form-item label="Personal / Social History">
          <el-row>
            <el-col :span="8">
              <span>Drugs</span>
              <el-radio-group v-model="form.drugs">
                <el-radio :label="'No'">No</el-radio>
                <el-radio :label="'Yes'">Yes</el-radio>
              </el-radio-group>
            </el-col>
            <el-col :span="8">
              <span>Alcohol</span>
              <el-radio-group v-model="form.alcohol">
                <el-radio :label="'No'">No</el-radio>
                <el-radio :label="'Yes'">Yes</el-radio>
              </el-radio-group>
            </el-col>
            <el-col :span="8">
              <span>Smoking</span>
              <el-radio-group v-model="form.smoking">
                <el-radio :label="'No'">No</el-radio>
                <el-radio :label="'Yes'">Yes</el-radio>
              </el-radio-group>
            </el-col>
          </el-row>
          <el-input v-model="form.personal_history_others" placeholder="Others" />
        </el-form-item>
      </el-col>
    </el-row>
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
  chief_complaint: '',
  history_of_present_illness: '',
  past_medical_history: '',
  other_medical_conditions: '',
  allergies: '',
  adverse_drug_reaction: '',
  blood_transfusion: '',
  radiation: '',
  previous_hospitalization: '',
  previous_operations: 'No',
  previous_operations_details: '',
  current_medications: '',
  drugs: 'No',
  alcohol: 'No',
  smoking: 'No',
  personal_history_others: ''
});
function save() { emit('save', form.value); }
</script> 