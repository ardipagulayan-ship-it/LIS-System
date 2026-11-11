<template>
  <div class="delta-check-rules">
    <div class="page-header">
      <h2 class="page-title">Delta Check Rules Configuration</h2>
    </div>
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>Delta Check Rules</span>
        </div>
      </template>
      <el-row :gutter="12" class="mb-3">
        <el-col :xs="24" :sm="12" :md="4">
          <el-input v-model="search" placeholder="Search by name" @keyup.enter="loadRules" clearable />
        </el-col>
        <el-col :xs="24" :sm="12" :md="4">
          <el-select v-model="selectedCategoryId" placeholder="Filter by Category" clearable @change="onCategoryFilterChange" style="width:100%">
            <el-option label="All Categories" :value="null" />
            <el-option v-for="cat in testCategories" :key="cat.id" :label="cat.name" :value="cat.id" />
          </el-select>
        </el-col>
        <el-col :xs="24" :sm="12" :md="4">
          <el-button @click="loadRules">Search</el-button>
        </el-col>
        <el-col :xs="24" :sm="12" :md="12" class="text-right">
          <el-button type="primary" @click="openCreate">New Rule</el-button>
        </el-col>
      </el-row>

      <el-table :data="rules" stripe border v-loading="loading" style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="rule_name" label="Rule Name" min-width="180" />
        <el-table-column label="Category" min-width="140">
          <template #default="scope">
            {{ scope.row.test_category_name || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="Test Parameter" min-width="200">
          <template #default="scope">
            {{ parameterName(scope.row.test_parameter_id) }}
          </template>
        </el-table-column>
        <el-table-column label="Equipment" min-width="150">
          <template #default="scope">
            {{ scope.row.equipment ? `${scope.row.equipment.manufacturer} - ${scope.row.equipment.model}` : 'All Equipment' }}
          </template>
        </el-table-column>
        <el-table-column label="Percentage Limit" width="140" align="right">
          <template #default="scope">
            {{ scope.row.percentage_limit ? scope.row.percentage_limit + '%' : '-' }}
          </template>
        </el-table-column>
        <el-table-column label="Absolute Limit" width="140" align="right">
          <template #default="scope">
            {{ scope.row.absolute_limit || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="Time Window" width="120" align="center">
          <template #default="scope">
            {{ scope.row.time_window_days || 30 }} days
          </template>
        </el-table-column>
        <el-table-column label="Updated" min-width="180">
          <template #default="scope">{{ formatDate(scope.row.updated_at) }}</template>
        </el-table-column>
        <el-table-column label="Active" width="120" align="center">
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

      <el-dialog v-model="showModal" :title="editing ? 'Edit Delta Check Rule' : 'Create Delta Check Rule'" width="800px">
        <el-form :model="form" label-width="180px" :rules="validationRules" ref="formRef">
          <el-form-item label="Rule Name" prop="rule_name" required>
            <el-input v-model="form.rule_name" placeholder="e.g., Hemoglobin Delta Check" />
          </el-form-item>
          
          <el-form-item label="Test Category" prop="test_category_id">
            <el-select v-model="form.test_category_id" filterable placeholder="Select category first (optional)" clearable @change="onCategoryChange" style="width:100%">
              <el-option v-for="cat in testCategories" :key="cat.id" :label="cat.name" :value="cat.id" />
            </el-select>
            <div class="form-hint">Filter parameters by category for easier selection</div>
          </el-form-item>
          
          <el-form-item label="Test Parameter" prop="test_parameter_id" required>
            <el-select v-model="form.test_parameter_id" filterable placeholder="Select test parameter" style="width:100%">
              <el-option v-for="p in filteredParameters" :key="p.id" :label="paramLabel(p)" :value="p.id" />
            </el-select>
            <div class="form-hint" v-if="form.test_category_id">Showing parameters from selected category</div>
          </el-form-item>

          <el-form-item label="Equipment">
            <el-select v-model="form.equipment_id" filterable placeholder="Select equipment (optional - leave empty for all)" clearable style="width:100%">
              <el-option v-for="eq in equipment" :key="eq.id" :label="`${eq.manufacturer} - ${eq.model}`" :value="eq.id" />
            </el-select>
            <div class="form-hint">Leave empty to apply rule to all equipment for this parameter</div>
          </el-form-item>

          <el-form-item label="Comparison Type" prop="comparison_type" required>
            <el-radio-group v-model="form.comparison_type">
              <el-radio label="percentage">Percentage Only</el-radio>
              <el-radio label="absolute">Absolute Only</el-radio>
              <el-radio label="both">Both</el-radio>
            </el-radio-group>
          </el-form-item>

          <el-form-item label="Percentage Limit (%)" prop="percentage_limit">
            <el-input-number v-model="form.percentage_limit" :min="0" :max="100" :precision="2" style="width:100%" placeholder="e.g., 25.50" />
            <div class="form-hint">Required if comparison type is 'percentage' or 'both'</div>
          </el-form-item>

          <el-form-item label="Absolute Limit" prop="absolute_limit">
            <el-input-number v-model="form.absolute_limit" :min="0" :precision="2" style="width:100%" placeholder="e.g., 2.5" />
            <div class="form-hint">Required if comparison type is 'absolute' or 'both'</div>
          </el-form-item>

          <el-form-item label="Critical Threshold (%)">
            <el-input-number v-model="form.critical_threshold" :min="0" :max="100" :precision="2" style="width:100%" placeholder="Optional" />
          </el-form-item>

          <el-form-item label="Time Window (Days)" prop="time_window_days" required>
            <el-input-number v-model="form.time_window_days" :min="1" :max="365" style="width:100%" />
            <div class="form-hint">How many days back to look for previous result</div>
          </el-form-item>

          <el-form-item label="Action on Violation" prop="action_on_violation" required>
            <el-select v-model="form.action_on_violation" style="width:100%">
              <el-option label="Flag Only" value="flag" />
              <el-option label="Flag and Stop Auto-Verification" value="flag_and_stop" />
              <el-option label="Alert Only" value="alert_only" />
            </el-select>
          </el-form-item>

          <el-form-item label="Alert Message">
            <el-input v-model="form.alert_message" type="textarea" :rows="3" placeholder="Message shown when violation occurs" />
          </el-form-item>

          <el-form-item label="Severity Level">
            <el-select v-model="form.severity_level_id" filterable placeholder="Select severity level (optional)" clearable style="width:100%">
              <el-option v-for="s in severityLevels" :key="s.id" :label="s.display_name" :value="s.id" />
            </el-select>
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
const selectedCategoryId = ref(null);
const loading = ref(false);
const parameters = ref([]);
const filteredParameters = ref([]);
const testCategories = ref([]);
const equipment = ref([]);
const severityLevels = ref([]);

const showModal = ref(false);
const editing = ref(false);
const currentId = ref(null);
const formRef = ref(null);
const form = ref({
  rule_name: '',
  test_category_id: null,
  test_parameter_id: null,
  equipment_id: null,
  percentage_limit: null,
  absolute_limit: null,
  critical_threshold: null,
  time_window_days: 30,
  comparison_type: 'both',
  action_on_violation: 'flag',
  alert_message: '',
  severity_level_id: null,
  is_active: true,
});

const validationRules = {
  rule_name: [{ required: true, message: 'Please enter rule name', trigger: 'blur' }],
  test_parameter_id: [{ required: true, message: 'Please select test parameter', trigger: 'change' }],
  comparison_type: [{ required: true, message: 'Please select comparison type', trigger: 'change' }],
  time_window_days: [{ required: true, message: 'Please enter time window', trigger: 'blur' }],
  action_on_violation: [{ required: true, message: 'Please select action', trigger: 'change' }],
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
  const name = p.test_name || p.parameter_name || p.name || `#${p.id}`;
  const category = p.test_category_name ? ` (${p.test_category_name})` : '';
  return `${name}${category}`;
}

async function loadParameters() {
  try {
    const res = await api.get('/test-parameters', { params: { per_page: 500 } });
    const data = Array.isArray(res.data) ? res.data : res.data.data;
    parameters.value = data || [];
    filteredParameters.value = parameters.value;
  } catch (e) {
    ElMessage.error('Failed to load test parameters');
  }
}

async function loadTestCategories() {
  try {
    const res = await api.get('/test-categories');
    const data = Array.isArray(res.data) ? res.data : res.data.data;
    testCategories.value = data || [];
  } catch (e) {
    ElMessage.error('Failed to load test categories');
  }
}

function onCategoryChange(categoryId) {
  if (categoryId) {
    filteredParameters.value = parameters.value.filter(p => p.test_category_id === categoryId);
  } else {
    filteredParameters.value = parameters.value;
  }
  // Clear parameter selection if it's no longer in the filtered list
  if (form.value.test_parameter_id) {
    const stillAvailable = filteredParameters.value.some(p => p.id === form.value.test_parameter_id);
    if (!stillAvailable) {
      form.value.test_parameter_id = null;
    }
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

async function loadSeverityLevels() {
  try {
    const res = await api.get('/delta-check-severity-levels');
    severityLevels.value = Array.isArray(res.data) ? res.data : (res.data.data || []);
  } catch (e) {
    // Severity levels might not be available, so we'll just continue without them
    console.warn('Failed to load severity levels:', e);
    severityLevels.value = [];
  }
}

function onCategoryFilterChange() {
  pagination.value.current_page = 1;
  loadRules(1);
}

async function loadRules(page = 1) {
  loading.value = true;
  try {
    const params = { 
      page, 
      per_page: pagination.value.per_page, 
      search: search.value || undefined
    };
    
    // Add category filter if selected
    if (selectedCategoryId.value) {
      params.test_category_id = selectedCategoryId.value;
    }
    
    const res = await api.get('/delta-check-rules', { params });
    const data = res.data;
    rules.value = data.data || [];
    pagination.value = {
      current_page: data.current_page || 1,
      last_page: data.last_page || 1,
      per_page: data.per_page || 15,
      total: data.total || (data.data ? data.data.length : 0),
    };
  } catch (e) {
    ElMessage.error('Failed to load delta check rules');
    console.error('Error loading rules:', e);
  } finally {
    loading.value = false;
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
    test_category_id: null,
    test_parameter_id: null,
    equipment_id: null,
    percentage_limit: null,
    absolute_limit: null,
    critical_threshold: null,
    time_window_days: 30,
    comparison_type: 'both',
    action_on_violation: 'flag',
    alert_message: '',
    severity_level_id: null,
    is_active: true,
  };
  filteredParameters.value = parameters.value;
  showModal.value = true;
}

function openEdit(r) {
  editing.value = true;
  currentId.value = r.id;
  form.value = { ...r };
  // Set category from the rule's test parameter
  if (r.test_category_id) {
    form.value.test_category_id = r.test_category_id;
    onCategoryChange(r.test_category_id);
  } else {
    // Try to find category from parameter
    const param = parameters.value.find(p => p.id === r.test_parameter_id);
    if (param && param.test_category_id) {
      form.value.test_category_id = param.test_category_id;
      onCategoryChange(param.test_category_id);
    } else {
      filteredParameters.value = parameters.value;
    }
  }
  showModal.value = true;
}

async function saveRule() {
  if (!formRef.value) return;
  
  await formRef.value.validate(async (valid) => {
    if (!valid) return;

    // Validate that at least one limit is provided
    if (form.value.comparison_type === 'percentage' && !form.value.percentage_limit) {
      ElMessage.warning('Percentage limit is required for percentage comparison type');
      return;
    }
    if (form.value.comparison_type === 'absolute' && !form.value.absolute_limit) {
      ElMessage.warning('Absolute limit is required for absolute comparison type');
      return;
    }
    if (form.value.comparison_type === 'both' && !form.value.percentage_limit && !form.value.absolute_limit) {
      ElMessage.warning('At least one limit (percentage or absolute) must be provided');
      return;
    }

    try {
      if (editing.value && currentId.value) {
        await api.put(`/delta-check-rules/${currentId.value}`, form.value);
        ElMessage.success('Rule updated successfully');
      } else {
        await api.post('/delta-check-rules', form.value);
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
    await api.put(`/delta-check-rules/${r.id}/toggle-active`);
    await loadRules(pagination.value.current_page);
    ElMessage.success('Status updated');
  } catch (e) {
    ElMessage.error('Failed to update status');
  }
}

async function removeRule(r) {
  try {
    await ElMessageBox.confirm(`Delete rule "${r.rule_name}"?`, 'Confirm', { type: 'warning' });
    await api.delete(`/delta-check-rules/${r.id}`);
    await loadRules();
    ElMessage.success('Rule deleted');
  } catch (e) {
    if (e !== 'cancel') {
      ElMessage.error('Failed to delete rule');
    }
  }
}

onMounted(async () => {
  await loadTestCategories();
  await loadParameters();
  await loadEquipment();
  await loadSeverityLevels();
  await loadRules();
});
</script>

<style scoped>
.delta-check-rules {
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

