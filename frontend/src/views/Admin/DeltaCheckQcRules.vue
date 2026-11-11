<template>
  <div class="delta-check-qc-rules">
    <div class="page-header">
      <h2 class="page-title">Delta Check & QC Rules Configuration</h2>
    </div>
    <el-card shadow="never">
      <el-tabs v-model="activeTab" type="border-card">
        <el-tab-pane label="Delta Check Rules" name="delta-check">
          <DeltaCheckRules />
        </el-tab-pane>
        <el-tab-pane label="QC Rules" name="qc">
          <QcRules />
        </el-tab-pane>
      </el-tabs>
    </el-card>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import DeltaCheckRules from './DeltaCheckRules.vue';
import QcRules from './QcRules.vue';

const activeTab = ref('delta-check');
</script>

<style scoped>
.delta-check-qc-rules {
  width: 100%;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-title {
  margin: 0;
  font-size: 24px;
  font-weight: 500;
}
</style>



