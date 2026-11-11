<template>
  <el-form :model="form" ref="formRef" label-width="180px" label-position="top">
    <el-form-item label="Menarche">
      <el-input v-model="form.menarche" />
    </el-form-item>
    <el-form-item label="Interval">
      <el-input v-model="form.interval" />
    </el-form-item>
    <el-form-item label="Duration">
      <el-input v-model="form.duration" />
    </el-form-item>
    <el-form-item label="Amount">
      <el-input v-model="form.amount" />
    </el-form-item>
    <el-form-item label="Pain">
      <el-input v-model="form.pain" />
    </el-form-item>
    <el-form-item label="Last Menstrual Period">
      <el-input v-model="form.last_menstrual_period" />
    </el-form-item>
    <el-form-item label="Previous Menstrual Period">
      <el-input v-model="form.previous_menstrual_period" />
    </el-form-item>
    <el-form-item label="Age of Gestation">
      <el-input v-model="form.age_of_gestation" />
    </el-form-item>
    <el-form-item label="Estimated Date of Confinement">
      <el-input v-model="form.estimated_date_of_confinement" />
    </el-form-item>
    <el-form-item label="Gravida">
      <el-input v-model="form.gravida" />
    </el-form-item>
    <el-form-item label="Parity">
      <el-input v-model="form.parity" />
    </el-form-item>
    <el-form-item label="Term">
      <el-input v-model="form.term" />
    </el-form-item>
    <el-form-item label="Preterm">
      <el-input v-model="form.preterm" />
    </el-form-item>
    <el-form-item label="Aborted">
      <el-input v-model="form.aborted" />
    </el-form-item>
    <el-form-item label="Living">
      <el-input v-model="form.living" />
    </el-form-item>
    <el-form-item label="Others">
      <el-input v-model="form.others" type="textarea" />
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
const form = ref({ menarche: '', interval: '', duration: '', amount: '', pain: '', last_menstrual_period: '', previous_menstrual_period: '', age_of_gestation: '', estimated_date_of_confinement: '', gravida: '', parity: '', term: '', preterm: '', aborted: '', living: '', others: '' });
function save() { emit('save', form.value); }
</script> 