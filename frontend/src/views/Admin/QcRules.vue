<template>
  <div class="qc-rules">
    <div class="page-header">
      <h2 class="page-title">Quality Control Rules Configuration</h2>
    </div>
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>QC Rules</span>
        </div>
      </template>
      <el-row :gutter="12" class="mb-3">
        <el-col :xs="24" :sm="12" :md="8">
          <el-select v-model="qcPolicy" placeholder="QC Policy" style="width:100%" :loading="qcPolicyLoading">
            <el-option label="Strictly Implemented" value="strict" />
            <el-option label="Medium (Supervisor Override)" value="medium" />
            <el-option label="Not Implemented" value="off" />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="12" :md="6">
          <el-button type="primary" @click="saveQcPolicy" :loading="qcPolicySaving">Save QC Policy</el-button>
        </el-col>
      </el-row>
      <el-row :gutter="12" class="mb-3">
        <el-col :xs="24" :sm="12" :md="4">
          <el-input v-model="search" placeholder="Search by name" @keyup.enter="loadRules" clearable />
        </el-col>
        <el-col :xs="24" :sm="12" :md="4">
          <el-button @click="loadRules">Search</el-button>
        </el-col>
        <el-col :xs="24" :sm="12" :md="16" class="text-right">
          <el-button type="primary" @click="openCreate">New Rule</el-button>
        </el-col>
      </el-row>

      <el-table :data="rules" stripe border v-loading="loading" style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="rule_name" label="Rule Name" min-width="180" />
        <el-table-column label="Test Parameter" min-width="150">
          <template #default="scope">
            {{ scope.row.test_parameter ? parameterName(scope.row.test_parameter.id) : 'Global' }}
          </template>
        </el-table-column>
        <el-table-column label="Equipment" min-width="150">
          <template #default="scope">
            {{ scope.row.equipment ? `${scope.row.equipment.manufacturer} - ${scope.row.equipment.model}` : 'All Equipment' }}
          </template>
        </el-table-column>
        <el-table-column prop="westgard_rule_type" label="Westgard Rule" width="120" align="center">
          <template #default="scope">
            <el-tag>{{ scope.row.westgard_rule_type }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Action" width="120" align="center">
          <template #default="scope">
            <el-tag :type="getActionType(scope.row.action_on_violation)">
              {{ scope.row.action_on_violation }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Severity" width="100" align="center">
          <template #default="scope">
            <el-tag :type="getSeverityType(scope.row.severity_level)">
              {{ scope.row.severity_level }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Enabled" width="100" align="center">
          <template #default="scope">
            <el-switch :model-value="!!scope.row.is_enabled" disabled />
          </template>
        </el-table-column>
        <el-table-column label="Active" width="100" align="center">
          <template #default="scope">
            <el-switch :model-value="!!scope.row.is_active" @change="() => toggleActive(scope.row)" />
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="180" align="center" fixed="right">
          <template #default="scope">
            <el-button-group>
              <el-button size="small" @click="openEdit(scope.row)"><el-icon><Edit /></el-icon>Edit</el-button>
              <el-button size="small" type="danger" @click="removeRule(scope.row)"><el-icon><Delete /></el-icon>Delete</el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>

      <div class="mt-3 text-right">
        <el-pagination
          background
          layout="prev, pager, next"
          :page-size="pagination.per_page"
          :current-page="pagination.current_page"
          :total="pagination.total"
          @current-change="changePage"
        />
      </div>

      <el-dialog v-model="showModal" :title="editing ? 'Edit QC Rule' : 'Create QC Rule'" width="800px">
        <el-form :model="form" label-width="200px" :rules="validationRules" ref="formRef">
          <el-form-item label="Rule Name" prop="rule_name" required>
            <el-input v-model="form.rule_name" placeholder="e.g., Hemoglobin 1-3s Rule" />
          </el-form-item>
          
          <el-form-item label="Westgard Rule Type" prop="westgard_rule_type" required>
            <el-select v-model="form.westgard_rule_type" style="width:100%">
              <el-option label="1-2s (Warning)" value="1-2s" />
              <el-option label="1-3s (Reject)" value="1-3s" />
              <el-option label="2-2s (Reject)" value="2-2s" />
              <el-option label="R-4s (Reject)" value="R-4s" />
              <el-option label="4-1s (Reject)" value="4-1s" />
              <el-option label="10x (Reject)" value="10x" />
            </el-select>
          </el-form-item>

          <el-form-item label="Test Parameter">
            <el-select v-model="form.test_parameter_id" filterable placeholder="Select test parameter (optional - leave empty for global)" clearable style="width:100%">
              <el-option v-for="p in parameters" :key="p.id" :label="paramLabel(p)" :value="p.id" />
            </el-select>
            <div class="form-hint">Leave empty to apply rule globally to all parameters</div>
          </el-form-item>

          <el-form-item label="Equipment">
            <el-select v-model="form.equipment_id" filterable placeholder="Select equipment (optional)" clearable style="width:100%">
              <el-option v-for="eq in equipment" :key="eq.id" :label="`${eq.manufacturer} - ${eq.model}`" :value="eq.id" />
            </el-select>
            <div class="form-hint">Leave empty to apply rule to all equipment</div>
          </el-form-item>

          <el-form-item label="QC Lot (Quality Control Test Parameter)">
            <el-select v-model="form.quality_control_test_parameter_id" filterable placeholder="Select QC lot (optional - for lot-specific rules)" clearable style="width:100%">
              <el-option v-for="qc in qualityControlTestParameters" :key="qc.id" :label="qcLabel(qc)" :value="qc.id" />
            </el-select>
            <div class="form-hint">Leave empty unless rule should apply to a specific QC lot only</div>
          </el-form-item>

          <el-form-item label="Action on Violation" prop="action_on_violation" required>
            <el-select v-model="form.action_on_violation" style="width:100%">
              <el-option label="Warning" value="warning" />
              <el-option label="Reject" value="reject" />
              <el-option label="Flag" value="flag" />
            </el-select>
          </el-form-item>

          <el-form-item label="Severity Level" prop="severity_level" required>
            <el-select v-model="form.severity_level" style="width:100%">
              <el-option label="Info" value="info" />
              <el-option label="Warning" value="warning" />
              <el-option label="Error" value="error" />
              <el-option label="Critical" value="critical" />
            </el-select>
          </el-form-item>

          <el-form-item label="Custom Threshold Multiplier">
            <el-input-number v-model="form.custom_threshold_multiplier" :min="0.1" :max="10" :precision="2" :step="0.1" style="width:100%" placeholder="Override default SD multiplier" />
            <div class="form-hint">Override default SD multiplier (e.g., 2.5 for 2.5SD instead of 2SD for 1-2s rule). Leave empty to use default.</div>
          </el-form-item>

          <el-form-item label="Alert Message">
            <el-input v-model="form.alert_message" type="textarea" :rows="3" placeholder="Message shown when rule violation occurs" />
          </el-form-item>

          <el-form-item label="Enabled">
            <el-switch v-model="form.enabled" />
            <span style="margin-left: var(--spacing-md); color: var(--color-text-tertiary); font-size: var(--font-size-xs)">Only enabled rules are checked</span>
          </el-form-item>

          <el-form-item label="Active">
            <el-switch v-model="form.is_active" />
            <span style="margin-left: var(--spacing-md); color: var(--color-text-tertiary); font-size: var(--font-size-xs)">Only active rules are evaluated</span>
          </el-form-item>
        </el-form>
        <template #footer>
          <el-button @click="showModal=false">Cancel</el-button>
          <el-button type="primary" @click="saveRule">Save Rule</el-button>
        </template>
      </el-dialog>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/utils/axios';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Edit, Delete } from '@element-plus/icons-vue';

const rules = ref([]);
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const search = ref('');
const loading = ref(false);
const parameters = ref([]);
const equipment = ref([]);
const qualityControlTestParameters = ref([]);
const qcPolicy = ref('strict');
const qcPolicyLoading = ref(false);
const qcPolicySaving = ref(false);

const showModal = ref(false);
const editing = ref(false);
const currentId = ref(null);
const formRef = ref(null);
const form = ref({
  rule_name: '',
  test_parameter_id: null,
  equipment_id: null,
  quality_control_test_parameter_id: null,
  westgard_rule_type: '1-2s',
  is_enabled: true,
  action_on_violation: 'reject',
  severity_level: 'error',
  alert_message: '',
  custom_threshold_multiplier: null,
  is_active: true,
});

const validationRules = {
  rule_name: [{ required: true, message: 'Please enter rule name', trigger: 'blur' }],
  westgard_rule_type: [{ required: true, message: 'Please select Westgard rule type', trigger: 'change' }],
  action_on_violation: [{ required: true, message: 'Please select action', trigger: 'change' }],
  severity_level: [{ required: true, message: 'Please select severity level', trigger: 'change' }],
};

function formatDate(s) {
  return s ? new Date(s).toLocaleString() : '';
}

function parameterName(id) {
  const p = parameters.value.find(x => x.id === id);
  return p ? (p.test_name || p.parameter_name || p.name || ('#'+id)) : ('#'+id);
}

function paramLabel(p) {
  if (!p) return '';
  return (p.test_name || p.parameter_name || p.name || `#${p.id}`);
}

function qcLabel(qc) {
  if (!qc) return '';
  const paramName = qc.test_parameter ? paramLabel(qc.test_parameter) : 'Unknown';
  const qcName = qc.quality_control ? qc.quality_control.control_name : '';
  const lot = qc.quality_control ? qc.quality_control.lot_number : '';
  return `${paramName} - ${qcName} (${lot})`;
}

function getActionType(action) {
  const types = {
    'warning': 'warning',
    'reject': 'danger',
    'flag': 'info'
  };
  return types[action] || 'info';
}

function getSeverityType(severity) {
  const types = {
    'info': 'info',
    'warning': 'warning',
    'error': 'danger',
    'critical': 'danger'
  };
  return types[severity] || 'info';
}

async function loadParameters() {
  try {
    const res = await api.get('/test-parameters', { params: { per_page: 500 } });
    const data = Array.isArray(res.data) ? res.data : res.data.data;
    parameters.value = data || [];
  } catch (e) {
    ElMessage.error('Failed to load test parameters');
  }
}

async function loadEquipment() {
  try {
    const res = await api.get('eim/equipment', { params: { per_page: 1000 } });
    equipment.value = Array.isArray(res.data) ? res.data : (res.data.data || []);
  } catch (e) {
    ElMessage.error('Failed to load equipment');
  }
}

async function loadQualityControlTestParameters() {
  try {
    const res = await api.get('/quality-control-test-parameters', { params: { per_page: 500 } });
    const data = Array.isArray(res.data) ? res.data : res.data.data;
    qualityControlTestParameters.value = data || [];
  } catch (e) {
    // This endpoint might not exist or might be paginated differently
    qualityControlTestParameters.value = [];
  }
}

async function loadRules(page = 1) {
  loading.value = true;
  try {
    const res = await api.get('/qc-rules', { 
      params: { 
        page, 
        per_page: pagination.value.per_page, 
        search: search.value || undefined 
      } 
    });
    const data = res.data;
    rules.value = data.data || [];
    pagination.value = {
      current_page: data.current_page || 1,
      last_page: data.last_page || 1,
      per_page: data.per_page || 15,
      total: data.total || (data.data ? data.data.length : 0),
    };
  } catch (e) {
    ElMessage.error('Failed to load QC rules');
  } finally {
    loading.value = false;
  }
}

async function loadQcPolicy() {
  qcPolicyLoading.value = true;
  try {
    const res = await api.get('/system-settings/qc_policy');
    qcPolicy.value = res.data?.value || 'strict';
  } catch (e) {
    qcPolicy.value = 'strict';
  } finally {
    qcPolicyLoading.value = false;
  }
}

async function saveQcPolicy() {
  qcPolicySaving.value = true;
  try {
    await api.put('/system-settings/qc_policy', { value: qcPolicy.value });
    ElMessage.success('QC policy updated');
  } catch (e) {
    ElMessage.error(e.response?.data?.message || 'Failed to update QC policy');
  } finally {
    qcPolicySaving.value = false;
  }
}

function changePage(p) { 
  loadRules(p); 
}

function openCreate() {
  editing.value = false;
  currentId.value = null;
  form.value = {
    rule_name: '',
    test_parameter_id: null,
    equipment_id: null,
    quality_control_test_parameter_id: null,
    westgard_rule_type: '1-2s',
    is_enabled: true,
    action_on_violation: 'reject',
    severity_level: 'error',
    alert_message: '',
    custom_threshold_multiplier: null,
    is_active: true,
  };
  showModal.value = true;
}

function openEdit(r) {
  editing.value = true;
  currentId.value = r.id;
  form.value = { ...r };
  showModal.value = true;
}

async function saveRule() {
  if (!formRef.value) return;
  
  await formRef.value.validate(async (valid) => {
    if (!valid) return;

    try {
      if (editing.value && currentId.value) {
        await api.put(`/qc-rules/${currentId.value}`, form.value);
        ElMessage.success('Rule updated successfully');
      } else {
        await api.post('/qc-rules', form.value);
        ElMessage.success('Rule created successfully');
      }
      showModal.value = false;
      await loadRules(pagination.value.current_page);
    } catch (e) {
      ElMessage.error(e?.response?.data?.message || 'Failed to save rule');
    }
  });
}

async function toggleActive(r) {
  try {
    await api.put(`/qc-rules/${r.id}/toggle-active`);
    await loadRules(pagination.value.current_page);
    ElMessage.success('Status updated');
  } catch (e) {
    ElMessage.error('Failed to update status');
  }
}

async function removeRule(r) {
  try {
    await ElMessageBox.confirm(`Delete rule "${r.rule_name}"?`, 'Confirm', { type: 'warning' });
    await api.delete(`/qc-rules/${r.id}`);
    await loadRules();
    ElMessage.success('Rule deleted');
  } catch (e) {
    if (e !== 'cancel') {
      ElMessage.error('Failed to delete rule');
    }
  }
}

onMounted(async () => {
  await loadParameters();
  await loadEquipment();
  await loadQualityControlTestParameters();
  await loadQcPolicy();
  await loadRules();
});
</script>

<style scoped>
.qc-rules {
  width: 100%;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-xl);
}

.page-title {
  margin: 0;
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
}

.mb-3 { margin-bottom: var(--spacing-md); }
.text-right { text-align: right; }
.mt-3 { margin-top: var(--spacing-md); }

.form-hint {
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
  margin-top: var(--spacing-xs);
  line-height: var(--line-height-relaxed);
}
</style>


