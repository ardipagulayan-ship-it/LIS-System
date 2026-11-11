<template>
  <el-form :model="form" ref="formRef" label-width="180px" label-position="top">
    <el-form-item label="Gestation Term">
      <el-input v-model="form.gestation_term" />
    </el-form-item>
    <el-form-item label="Past Term">
      <el-input v-model="form.past_term" />
    </el-form-item>
    <el-form-item label="Post Term">
      <el-input v-model="form.post_term" />
    </el-form-item>
    <el-form-item label="Age of Gestation">
      <el-input v-model="form.age_of_gestation" />
    </el-form-item>
    <el-form-item label="Type of Delivery">
      <el-input v-model="form.type_of_delivery" />
    </el-form-item>
    <el-form-item label="Apgar Score">
      <el-input v-model="form.afgar_score" />
    </el-form-item>
    <el-form-item label="Weight of Birth">
      <el-input v-model="form.weight_of_birth" />
    </el-form-item>
    <el-form-item label="Place of Delivery">
      <el-input v-model="form.place_of_delivery" />
    </el-form-item>
    <el-form-item label="Length of Stay at Nursery">
      <el-input v-model="form.length_of_stay_at_nursery" />
    </el-form-item>
    <el-form-item label="Others">
      <el-input v-model="form.others" type="textarea" />
    </el-form-item>
    <el-form-item label="Immunization History (JSON)">
      <el-input v-model="form.immunization_history" type="textarea" />
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
const form = ref({ gestation_term: '', past_term: '', post_term: '', age_of_gestation: '', type_of_delivery: '', afgar_score: '', weight_of_birth: '', place_of_delivery: '', length_of_stay_at_nursery: '', others: '', immunization_history: '' });
function save() { emit('save', form.value); }
</script> 