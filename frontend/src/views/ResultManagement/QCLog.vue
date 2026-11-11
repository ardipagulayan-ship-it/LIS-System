<template>
  <div class="qc-log">
    <div class="page-header">
      <h2 class="page-title">Quality Control Log</h2>
      <div class="header-actions">
        <el-tag v-if="currentEquipment?.on_hold" type="danger" effect="dark">On Hold</el-tag>
        <el-tag v-else type="success">Operational</el-tag>
        <span class="equipment-name">{{ currentEquipment?.name || 'Equipment' }}</span>
      </div>
    </div>

    <el-card>
      <template #header>
        <div class="card-header">
          <span>Quality Control Results for {{ currentEquipment?.name || 'Equipment' }}</span>
          <el-button type="danger" @click="openResolveDialog">Resolve QC Issue Now</el-button>
        </div>
      </template>

      <el-tabs v-model="activeParamId" @tab-click="handleTabClick">
        <el-tab-pane 
          v-for="p in qcParameters" 
          :key="p.id" 
          :name="String(p.id)"
        >
          <template #label>
            <span>{{ resolveParamName(p) }}</span>
            <el-tag v-if="isParamFailed(p.id)" type="danger" size="small" class="fail-badge" style="margin-left:8px">Fail</el-tag>
          </template>
          <el-table :data="resultsByParameter[p.id] || []" v-loading="loading" style="width: 100%">
            <el-table-column label="Run Date" width="120">
              <template #default="{ row }">{{ row.run_date }}</template>
            </el-table-column>
            <el-table-column label="Run Time" width="110">
              <template #default="{ row }">{{ row.run_time }}</template>
            </el-table-column>
            <el-table-column :label="p.parameter_name || p.test_name || 'Result'" min-width="160">
              <template #default="{ row }">{{ row.result_value }}</template>
            </el-table-column>
            <el-table-column label="Operator" min-width="140">
              <template #default="{ row }">{{ row.operator?.name || '-' }}</template>
            </el-table-column>
            <el-table-column label="Westgard Rules" min-width="220">
              <template #default="{ row }">
                <template v-if="Array.isArray(row.westgard_violations) && row.westgard_violations.length">
                  <el-tag v-for="v in row.westgard_violations" :key="v" size="small" type="danger" style="margin-right:4px">{{ v }}</el-tag>
                </template>
                <span v-else>-</span>
              </template>
            </el-table-column>
            <el-table-column label="Status" width="100">
              <template #default="{ row }">
                <el-tag :type="row.is_valid ? 'success' : 'danger'" size="small">{{ row.is_valid ? 'Valid' : 'Invalid' }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column label="Remarks" min-width="160">
              <template #default="{ row }">{{ row.remarks || '' }}</template>
            </el-table-column>
          </el-table>
        </el-tab-pane>
      </el-tabs>
    </el-card>

    <div class="recommendation-section" style="margin-top:2rem;">
      <el-card class="recommendation-card">
        <template #header>
          <div class="recommendation-header">
            <span>Global QC Recommendations</span>
            <el-button type="primary" size="small" @click="fetchRecommendations">Refresh</el-button>
          </div>
        </template>

        <el-alert
          v-for="rec in recommendations"
          :key="rec.id"
          :title="rec.title"
          :description="rec.description"
          :type="rec.qc_type === 'instrument_check' ? 'warning' : (rec.qc_type === 'qc_review' ? 'info' : 'success')"
          show-icon
          class="recommendation-alert"
          style="margin-bottom: 1rem;"
        />
        <div v-if="!recommendations.length" class="text-gray-400">No global recommendations</div>
      </el-card>
    </div>

    <el-dialog v-model="resolveDialogVisible" title="Resolve QC Issue" width="460px">
      <p>Confirm you have performed corrective action and rerun QC with passing status for the selected equipment.</p>
      <el-form :model="resolveForm" label-width="160px">
        <el-form-item label="Corrective Action ID (optional)">
          <el-input v-model="resolveForm.corrective_action_id" placeholder="Enter corrective action id" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="resolveDialogVisible = false">Cancel</el-button>
        <el-button type="primary" :loading="resolving" @click="releaseLockout">Release QC Lockout</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/utils/axios';
import { ElMessage } from 'element-plus';

// Data
const selectedCategoryId = ref(null);
const equipmentSummary = ref({ total_equipment: 0, on_hold_count: 0, equipment: [] });
const selectedEquipmentId = ref(null);
const lockedResults = ref([]); // retained for release flow
const loading = ref(false);
const resolving = ref(false);
const resolveDialogVisible = ref(false);
const resolveForm = ref({ corrective_action_id: '' });

const equipmentOptions = computed(() => equipmentSummary.value.equipment || []);
const equipmentLabel = (eq) => `${eq.name || 'Equipment'}${eq.on_hold ? ' (On Hold)' : ''}`;
const route = useRoute();
const router = useRouter();
const currentEquipment = computed(() => equipmentOptions.value.find(eq => eq.id === selectedEquipmentId.value));

// QC parameters and results
const qcParameters = ref([]);
const resultsByParameter = ref({});
const activeParamId = ref('');

const recommendations = ref([]);

async function fetchRecommendations() {
  try {
    const { data } = await api.get('/quality-controls/qc-recommendations');
    recommendations.value = Array.isArray(data) ? data : [];
  } catch (err) {
    recommendations.value = [];
    ElMessage.error('Could not load QC recommendations');
  }
}

onMounted(async () => {
  const qCategory = parseInt(route.query.category_id);
  const qEquipment = parseInt(route.query.equipment_id);
  const qRequest = parseInt(route.query.request_id);
  if (!qEquipment || Number.isNaN(qEquipment)) {
    ElMessage.error('Missing equipment_id');
    return;
  }
  selectedEquipmentId.value = qEquipment;
  selectedCategoryId.value = qCategory && !Number.isNaN(qCategory) ? qCategory : null;
  if (selectedCategoryId.value) {
    await loadEquipmentSummary();
  }
  await loadQCParameters();
  if (qcParameters.value.length > 0) {
    await Promise.all(qcParameters.value.map(p => loadQCResultsForParam(p.id)));
  }
  await loadLockedResults(qRequest);
  await fetchRecommendations();
  // Default to first parameter tab
  if (qcParameters.value.length > 0) {
    activeParamId.value = String(qcParameters.value[0].id);
    // already preloaded
  }
});

async function loadEquipmentSummary() {
  if (!selectedCategoryId.value) return;
  try {
    const { data } = await api.get('/qc-lockout/status-summary', { params: { test_category_id: selectedCategoryId.value } });
    equipmentSummary.value = data || { equipment: [] };
    if (!equipmentSummary.value.equipment.some(eq => eq.id === selectedEquipmentId.value)) {
      selectedEquipmentId.value = null;
    }
    lockedResults.value = [];
  } catch (e) {
    equipmentSummary.value = { equipment: [] };
  }
}

async function loadLockedResults(requestId) {
  if (!selectedEquipmentId.value) return;
  loading.value = true;
  try {
    const { data } = await api.get(`/qc-lockout/locked-results/${selectedEquipmentId.value}`);
    let rows = Array.isArray(data) ? data : [];
    if ((!rows || rows.length === 0) && requestId) {
      // Fallback: fetch results tied to this request and equipment
      const res2 = await api.get(`/laboratory-requests/${requestId}/results-by-equipment/${selectedEquipmentId.value}`);
      rows = Array.isArray(res2.data) ? res2.data : [];
    }
    lockedResults.value = rows;
  } catch (e) {
    lockedResults.value = [];
    ElMessage.error('Failed to load locked results');
  } finally {
    loading.value = false;
  }
}

async function loadQCParameters() {
  try {
    // Get QC configuration and parameters for this equipment
    const { data } = await api.get(`/quality-controls/equipment/${selectedEquipmentId.value}`);
    // Expecting array of QC controls, each with parameter_name
    const params = Array.isArray(data?.parameters) ? data.parameters : (Array.isArray(data) ? data : []);
    qcParameters.value = params.map(p => ({
      id: p.id || p.quality_control_test_parameter_id || p.qc_test_parameter_id || p.qctp_id || p,
      parameter_name: p.parameter_name || p.test_name || p.name || p.test_parameter_name || 'Parameter',
    }));
  } catch (e) {
    qcParameters.value = [];
  }
}

async function loadQCResultsForParam(paramId) {
  if (!paramId) return;
  try {
    const { data } = await api.get(`/quality-control-test-parameters/${paramId}/results`);
    resultsByParameter.value = { ...resultsByParameter.value, [paramId]: Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []) };
  } catch (e) {
    resultsByParameter.value = { ...resultsByParameter.value, [paramId]: [] };
  }
}

function resolveParamName(p) {
  // Prefer parameter_name from backend; fallback to others
  if (p.parameter_name) return p.parameter_name;
  if (p?.test_name || p?.name || p?.test_parameter_name) return p.test_name || p.name || p.test_parameter_name;
  const rows = resultsByParameter.value[p.id] || [];
  const latest = rows[0];
  return latest?.testParameter?.test_name || latest?.parameter_name || 'Parameter';
}

function isParamFailed(paramId) {
  const rows = resultsByParameter.value[paramId] || [];
  const latest = rows[0];
  return !!(latest && (latest.is_valid === false || (Array.isArray(latest.westgard_violations) && latest.westgard_violations.length > 0)));
}

async function handleTabClick(tab) {
  const pid = parseInt(tab.props.name);
  if (!resultsByParameter.value[pid]) {
    await loadQCResultsForParam(pid);
  }
}

function openResolveDialog() {
  resolveDialogVisible.value = true;
}

async function releaseLockout() {
  if (!selectedEquipmentId.value) return;
  resolving.value = true;
  try {
    // Release requires a test_parameter_id. For mass-release, we iterate unique parameter ids from the locked results
    const uniqueParams = Array.from(new Set(lockedResults.value.map(r => r.test_parameter_id).filter(Boolean)));
    if (uniqueParams.length === 0) {
      ElMessage.warning('No parameter ids found to release. Ensure QC has passed and results are available.');
      resolving.value = false;
      return;
    }
    const correctiveId = resolveForm.value.corrective_action_id ? Number(resolveForm.value.corrective_action_id) : null;
    let okCount = 0;
    for (const pid of uniqueParams) {
      const payload = { equipment_id: selectedEquipmentId.value, test_parameter_id: pid };
      if (correctiveId) payload.corrective_action_id = correctiveId;
      try {
        await api.post('/qc-lockout/release', payload);
        okCount++;
      } catch (_) {}
    }
    ElMessage.success(`Released QC lockout for ${okCount}/${uniqueParams.length} parameter(s).`);
    resolveDialogVisible.value = false;
    await loadLockedResults();
    await loadEquipmentSummary();
  } catch (e) {
    ElMessage.error('Failed to release QC lockout');
  } finally {
    resolving.value = false;
  }
}
</script>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-md);
}
.page-title { margin: 0; }
.header-actions { display: flex; gap: var(--spacing-sm); }
.card-header { display: flex; justify-content: space-between; align-items: center; }
.fail-badge {
  font-size: var(--font-size-xs) !important;
  height: 18px !important;
  line-height: 16px !important;
  padding: 0 var(--spacing-xs) !important;
  vertical-align: middle;
}
.recommendation-section {
  margin-top: var(--spacing-2xl);
}
.recommendation-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.recommendation-alert {
  margin-bottom: var(--spacing-sm);
}
.text-gray-400 {
  color: var(--color-text-tertiary);
}
</style>


