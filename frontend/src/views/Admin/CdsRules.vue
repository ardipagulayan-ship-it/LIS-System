<template>
  <div class="cds-rules">
    <div class="page-header">
      <h2 class="page-title">Clinical Decision Support (CDS) Rules</h2>
    </div>
    <el-card shadow="never">
      <template #header>
      <div class="card-header">
        <span>Correlation Rules</span>
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
        <el-table-column prop="rule_name" label="Rule Name" min-width="200" />
        <el-table-column label="Category" min-width="140">
          <template #default="scope">
            {{ scope.row.test_category_name || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="Trigger Parameter" min-width="220">
          <template #default="scope">{{ parameterName(scope.row.trigger_test_id) }}</template>
        </el-table-column>
        <el-table-column prop="action_type" label="Action" width="160" />
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

      <el-dialog v-model="showModal" :title="editing ? 'Edit Rule' : 'Create Rule'" width="1000px" class="rule-builder-dialog">
        <el-form :model="form" label-width="180px">
          <el-form-item label="Rule Name" required>
            <el-input v-model="form.rule_name" placeholder="e.g., Severe Dengue Alert" />
          </el-form-item>
          
          <el-form-item label="Test Category">
            <el-select v-model="form.test_category_id" filterable placeholder="Select category first (optional)" clearable @change="onCategoryChange" style="width:100%">
              <el-option v-for="cat in testCategories" :key="cat.id" :label="cat.name" :value="cat.id" />
            </el-select>
            <div class="form-hint">Filter parameters by category for easier selection</div>
          </el-form-item>

          <el-form-item label="Trigger Parameter" required>
            <el-select v-model="form.trigger_test_id" filterable placeholder="Select the parameter that triggers this rule" style="width:100%">
              <el-option v-for="p in filteredParameters" :key="p.id" :label="paramLabel(p)" :value="p.id" />
            </el-select>
            <div class="form-hint">This parameter will initiate the rule check when a result is entered</div>
            <div class="form-hint" v-if="form.test_category_id">Showing parameters from selected category</div>
          </el-form-item>

          <el-form-item label="Action Type" required>
            <el-select v-model="form.action_type" style="width:100%">
              <el-option label="Stop Auto-Verification (Require Manual Review)" value="STOP_AUTO_VERIFY" />
              <el-option label="Order Reflex Test Automatically" value="REFLEX_TEST" />
              <el-option label="Add Comment to Result" value="ADD_COMMENT" />
            </el-select>
          </el-form-item>

          <el-form-item v-if="form.action_type==='REFLEX_TEST'" label="Reflex Test Parameter">
            <el-select v-model="form.reflex_test_id" filterable placeholder="Select parameter to add as reflex test" style="width:100%">
              <el-option :value="null" label="-- Select --" />
              <el-option v-for="p in filteredParameters" :key="'r-'+p.id" :label="paramLabel(p)" :value="p.id" />
            </el-select>
          </el-form-item>

          <el-form-item label="Alert Message">
            <el-input v-model="form.alert_message" type="textarea" :rows="2" placeholder="Message shown to technologist when rule triggers" />
          </el-form-item>

          <el-form-item label="Correlated Tests" required>
            <el-select v-model="correlatedTestIds" multiple filterable placeholder="Select all parameters referenced in the rule logic" style="width:100%">
              <el-option v-for="p in filteredParameters" :key="'c-'+p.id" :label="paramLabel(p)" :value="p.id" />
            </el-select>
            <div class="form-hint">Select all test parameters that will be checked in the rule conditions below</div>
          </el-form-item>

          <el-form-item label="Rule Conditions" required>
            <div class="rule-builder-container">
              <div class="rule-builder-header">
                <el-select v-model="ruleStructure.operator" size="small" style="width:120px">
                  <el-option label="ALL of (AND)" value="AND" />
                  <el-option label="ANY of (OR)" value="OR" />
                </el-select>
                <span class="rule-builder-header-text">the following conditions must be true:</span>
                <el-button size="small" type="primary" @click="addCondition" style="margin-left:auto">Add Condition</el-button>
                <el-button size="small" @click="addGroup" style="margin-left:8px">Add Group</el-button>
              </div>
              
              <div class="rule-conditions-list">
                <div v-for="(item, idx) in ruleStructure.conditions" :key="item.id" class="rule-item">
                  <div v-if="item.type === 'condition'" class="rule-condition">
                    <div class="condition-content">
                      <el-select v-model="item.parameter_id" filterable placeholder="Parameter" style="width:200px" size="small">
                        <el-option v-for="p in filteredParameters" :key="'cond-'+idx+'-'+p.id" :label="paramLabel(p)" :value="p.id" />
                      </el-select>
                      
                      <el-select v-model="item.metric" placeholder="Compare" style="width:140px" size="small">
                        <el-option label="Current Value" value="absolute" />
                        <el-option label="% Change from Previous" value="delta_percent" />
                        <el-option label="Absolute Change" value="delta_absolute" />
                        <el-option label="Ratio vs Previous" value="ratio_vs_previous" />
                      </el-select>
                      
                      <el-select v-model="item.operator" style="width:100px" size="small">
                        <el-option label="&lt;" value="<" />
                        <el-option label="≤" value="<=" />
                        <el-option label="&gt;" value=">" />
                        <el-option label="≥" value=">=" />
                        <el-option label="=" value="==" />
                        <el-option label="≠" value="!=" />
                      </el-select>
                      
                      <el-input-number v-model="item.value" :precision="2" style="width:150px" size="small" placeholder="Value" />
                      
                      <el-button size="small" type="danger" text @click="removeItem(idx)" style="margin-left:8px">Remove</el-button>
                    </div>
                    <div v-if="item.metric !== 'absolute'" class="metric-hint">
                      {{ getMetricHint(item.metric) }}
                    </div>
                  </div>
                  
                  <div v-else-if="item.type === 'group'" class="rule-group">
                    <div class="group-header">
                      <el-select v-model="item.operator" size="small" style="width:120px">
                        <el-option label="ALL of (AND)" value="AND" />
                        <el-option label="ANY of (OR)" value="OR" />
                      </el-select>
                      <span class="group-header-text">the following:</span>
                      <el-button size="small" type="primary" text @click="addConditionToGroup(idx)" style="margin-left:8px">Add Condition</el-button>
                      <el-button size="small" text @click="addGroupToGroup(idx)" style="margin-left:4px">Add Group</el-button>
                      <el-button size="small" type="danger" text @click="removeItem(idx)" style="margin-left:auto">Remove Group</el-button>
                    </div>
                    <div class="group-content">
                      <div v-for="(subItem, subIdx) in item.conditions" :key="subItem.id" class="rule-item nested">
                        <div v-if="subItem.type === 'condition'" class="rule-condition">
                          <div class="condition-content">
                            <el-select v-model="subItem.parameter_id" filterable placeholder="Parameter" style="width:200px" size="small">
                              <el-option v-for="p in filteredParameters" :key="'sub-'+idx+'-'+subIdx+'-'+p.id" :label="paramLabel(p)" :value="p.id" />
                            </el-select>
                            <el-select v-model="subItem.metric" placeholder="Compare" style="width:140px" size="small">
                              <el-option label="Current Value" value="absolute" />
                              <el-option label="% Change from Previous" value="delta_percent" />
                              <el-option label="Absolute Change" value="delta_absolute" />
                              <el-option label="Ratio vs Previous" value="ratio_vs_previous" />
                            </el-select>
                            <el-select v-model="subItem.operator" style="width:100px" size="small">
                              <el-option label="&lt;" value="<" />
                              <el-option label="≤" value="<=" />
                              <el-option label="&gt;" value=">" />
                              <el-option label="≥" value=">=" />
                              <el-option label="=" value="==" />
                              <el-option label="≠" value="!=" />
                            </el-select>
                            <el-input-number v-model="subItem.value" :precision="2" style="width:150px" size="small" placeholder="Value" />
                            <el-button size="small" type="danger" text @click="removeFromGroup(idx, subIdx)" style="margin-left:8px">Remove</el-button>
                          </div>
                          <div v-if="subItem.metric !== 'absolute'" class="metric-hint">
                            {{ getMetricHint(subItem.metric) }}
                          </div>
                        </div>
                        <div v-else-if="subItem.type === 'group'" class="rule-group nested-group">
                          <div class="group-header">
                            <el-select v-model="subItem.operator" size="small" style="width:120px">
                              <el-option label="ALL of (AND)" value="AND" />
                              <el-option label="ANY of (OR)" value="OR" />
                            </el-select>
                            <el-button size="small" type="danger" text @click="removeFromGroup(idx, subIdx)" style="margin-left:auto">Remove</el-button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-if="ruleStructure.conditions.length === 0" class="empty-conditions">
                  <el-empty description="No conditions added. Click 'Add Condition' to start building your rule." :image-size="80" />
                </div>
              </div>

              <div class="rule-preview">
                <el-divider>Rule Preview</el-divider>
                <div class="preview-text">{{ rulePreviewText }}</div>
              </div>
            </div>
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
import { ref, onMounted, computed, watch } from 'vue';
import api from '@/utils/axios';
import { ElMessage, ElMessageBox } from 'element-plus';

const rules = ref([]);
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const search = ref('');
const selectedCategoryId = ref(null);
const loading = ref(false);
const parameters = ref([]);
const filteredParameters = ref([]);
const testCategories = ref([]);

const showModal = ref(false);
const editing = ref(false);
const currentId = ref(null);
const form = ref({
  rule_name: '',
  test_category_id: null,
  trigger_test_id: null,
  rule_logic_json: {},
  correlated_tests: '',
  alert_message: '',
  action_type: 'STOP_AUTO_VERIFY',
  reflex_test_id: null,
  is_active: true,
});

const correlatedTestIds = ref([]);
const ruleStructure = ref({
  operator: 'AND',
  conditions: []
});

let nextId = 1;

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

function getMetricHint(metric) {
  const hints = {
    'delta_percent': 'Calculates percentage change: (Previous - Current) / Previous',
    'delta_absolute': 'Calculates absolute difference: |Current - Previous|',
    'ratio_vs_previous': 'Calculates ratio: Current / Previous'
  };
  return hints[metric] || '';
}

const rulePreviewText = computed(() => {
  if (ruleStructure.value.conditions.length === 0) {
    return 'No conditions defined yet.';
  }
  return buildRulePreview(ruleStructure.value);
});

function buildRulePreview(node) {
  if (node.type === 'condition') {
    const param = parameterName(node.parameter_id);
    const metric = node.metric === 'absolute' ? 'Current Value' : 
                   node.metric === 'delta_percent' ? '% Change' :
                   node.metric === 'delta_absolute' ? 'Absolute Change' :
                   'Ratio vs Previous';
    return `${param} (${metric}) ${node.operator} ${node.value}`;
  }
  if (node.type === 'group' || !node.type) {
    const op = node.operator === 'AND' ? 'AND' : 'OR';
    const conditions = (node.conditions || []).map(c => buildRulePreview(c));
    if (conditions.length === 0) return '';
    return `(${conditions.join(` ${op} `)})`;
  }
  return '';
}

function addCondition() {
  ruleStructure.value.conditions.push({
    id: nextId++,
    type: 'condition',
    parameter_id: null,
    operator: '<',
    value: null,
    metric: 'absolute'
  });
}

function addGroup() {
  ruleStructure.value.conditions.push({
    id: nextId++,
    type: 'group',
    operator: 'AND',
    conditions: []
  });
}

function addConditionToGroup(groupIdx) {
  const group = ruleStructure.value.conditions[groupIdx];
  if (group && group.type === 'group') {
    group.conditions.push({
      id: nextId++,
      type: 'condition',
      parameter_id: null,
      operator: '<',
      value: null,
      metric: 'absolute'
    });
  }
}

function addGroupToGroup(groupIdx) {
  const group = ruleStructure.value.conditions[groupIdx];
  if (group && group.type === 'group') {
    group.conditions.push({
      id: nextId++,
      type: 'group',
      operator: 'AND',
      conditions: []
    });
  }
}

function removeItem(idx) {
  ruleStructure.value.conditions.splice(idx, 1);
}

function removeFromGroup(groupIdx, itemIdx) {
  const group = ruleStructure.value.conditions[groupIdx];
  if (group && group.type === 'group') {
    group.conditions.splice(itemIdx, 1);
  }
}

function buildRuleJson(node) {
  if (node.type === 'condition') {
    return {
      parameter_id: node.parameter_id,
      operator: node.operator,
      value: node.value,
      metric: node.metric || 'absolute'
    };
  }
  if (node.type === 'group' || !node.type) {
    return {
      op: node.operator || 'AND',
      conditions: (node.conditions || []).map(c => buildRuleJson(c))
    };
  }
  return null;
}

function parseRuleJson(json) {
  if (!json || typeof json !== 'object') {
    return { operator: 'AND', conditions: [] };
  }
  
  if (json.parameter_id !== undefined) {
    return {
      id: nextId++,
      type: 'condition',
      parameter_id: json.parameter_id,
      operator: json.operator || '<',
      value: json.value || null,
      metric: json.metric || 'absolute'
    };
  }
  
  if (json.op && json.conditions) {
    return {
      id: nextId++,
      type: 'group',
      operator: json.op,
      conditions: json.conditions.map(c => parseRuleJson(c))
    };
  }
  
  return { operator: json.op || 'AND', conditions: json.conditions ? json.conditions.map(c => parseRuleJson(c)) : [] };
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
  // Clear selections if they're no longer in the filtered list
  if (form.value.trigger_test_id) {
    const stillAvailable = filteredParameters.value.some(p => p.id === form.value.trigger_test_id);
    if (!stillAvailable) {
      form.value.trigger_test_id = null;
    }
  }
  if (form.value.reflex_test_id) {
    const stillAvailable = filteredParameters.value.some(p => p.id === form.value.reflex_test_id);
    if (!stillAvailable) {
      form.value.reflex_test_id = null;
    }
  }
  // Filter correlated tests
  correlatedTestIds.value = correlatedTestIds.value.filter(id => 
    filteredParameters.value.some(p => p.id === id)
  );
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
    
    const res = await api.get('/cds-rules', { params });
    const data = res.data;
    rules.value = data.data || [];
    pagination.value = {
      current_page: data.current_page || 1,
      last_page: data.last_page || 1,
      per_page: data.per_page || 15,
      total: data.total || (data.data ? data.data.length : 0),
    };
  } catch (e) {
    ElMessage.error('Failed to load CDS rules');
    console.error('Error loading rules:', e);
  } finally {
    loading.value = false;
  }
}

function changePage(p) { loadRules(p); }

function openCreate() {
  editing.value = false;
  currentId.value = null;
  form.value = { 
    rule_name: '', 
    test_category_id: null,
    trigger_test_id: null, 
    rule_logic_json: {}, 
    correlated_tests: '', 
    alert_message: '', 
    action_type: 'STOP_AUTO_VERIFY', 
    reflex_test_id: null, 
    is_active: true 
  };
  correlatedTestIds.value = [];
  ruleStructure.value = { operator: 'AND', conditions: [] };
  nextId = 1;
  filteredParameters.value = parameters.value;
  showModal.value = true;
}

function openEdit(r) {
  editing.value = true;
  currentId.value = r.id;
  form.value = { ...r };
  
  // Set category from the rule's trigger test parameter
  if (r.test_category_id) {
    form.value.test_category_id = r.test_category_id;
    onCategoryChange(r.test_category_id);
  } else {
    // Try to find category from parameter
    const param = parameters.value.find(p => p.id === r.trigger_test_id);
    if (param && param.test_category_id) {
      form.value.test_category_id = param.test_category_id;
      onCategoryChange(param.test_category_id);
    } else {
      filteredParameters.value = parameters.value;
    }
  }
  
  // Parse correlated tests from comma-separated string to array
  correlatedTestIds.value = r.correlated_tests ? r.correlated_tests.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id)) : [];
  
  // Parse rule logic JSON to visual structure
  if (r.rule_logic_json) {
    const parsed = parseRuleJson(r.rule_logic_json);
    if (parsed.type) {
      ruleStructure.value = { operator: parsed.operator || 'AND', conditions: [parsed] };
    } else {
      ruleStructure.value = parsed;
    }
  } else {
    ruleStructure.value = { operator: 'AND', conditions: [] };
  }
  
  showModal.value = true;
}

async function saveRule() {
  if (!form.value.rule_name) {
    ElMessage.warning('Please enter a rule name');
    return;
  }
  if (!form.value.trigger_test_id) {
    ElMessage.warning('Please select a trigger parameter');
    return;
  }
  if (ruleStructure.value.conditions.length === 0) {
    ElMessage.warning('Please add at least one condition');
    return;
  }
  
  // Build JSON from visual structure
  const ruleJson = {
    op: ruleStructure.value.operator,
    conditions: ruleStructure.value.conditions.map(c => buildRuleJson(c))
  };
  
  // Validate conditions
  const validateCondition = (cond) => {
    if (cond.type === 'condition') {
      if (!cond.parameter_id || cond.value === null || cond.value === undefined) {
        return false;
      }
      return true;
    }
    if (cond.type === 'group') {
      if (!cond.conditions || cond.conditions.length === 0) {
        return false;
      }
      return cond.conditions.every(c => validateCondition(c));
    }
    return false;
  };
  
  if (!ruleStructure.value.conditions.every(c => validateCondition(c))) {
    ElMessage.warning('Please complete all conditions (select parameter, operator, and value)');
    return;
  }
  
  // Build correlated tests string from selected IDs
  form.value.correlated_tests = correlatedTestIds.value.join(',');
  
  // Add trigger parameter to correlated if not already there
  if (form.value.trigger_test_id && !correlatedTestIds.value.includes(form.value.trigger_test_id)) {
    form.value.correlated_tests = form.value.correlated_tests ? 
      `${form.value.trigger_test_id},${form.value.correlated_tests}` : 
      String(form.value.trigger_test_id);
  }
  
  const payload = { ...form.value, rule_logic_json: ruleJson };

  if (editing.value && currentId.value) {
    try {
      await api.put(`/cds-rules/${currentId.value}`, payload);
      ElMessage.success('Rule updated successfully');
    } catch (e) {
      ElMessage.error(e?.response?.data?.message || 'Failed to update rule');
      return;
    }
  } else {
    try {
      await api.post('/cds-rules', payload);
      ElMessage.success('Rule created successfully');
    } catch (e) {
      ElMessage.error(e?.response?.data?.message || 'Failed to create rule');
      return;
    }
  }
  showModal.value = false;
  await loadRules(pagination.value.current_page);
}

async function toggleActive(r) {
  try {
    await api.put(`/cds-rules/${r.id}`, { is_active: !r.is_active });
    await loadRules(pagination.value.current_page);
    ElMessage.success('Status updated');
  } catch (e) {
    ElMessage.error('Failed to update status');
  }
}

async function removeRule(r) {
  try {
    await ElMessageBox.confirm(`Delete rule "${r.rule_name}"?`, 'Confirm', { type: 'warning' });
    await api.delete(`/cds-rules/${r.id}`);
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
  await loadRules();
});
</script>

<style scoped>
.cds-rules {
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

.mb-3 { margin-bottom: 12px; }
.text-right { text-align: right; }

.rule-builder-dialog {
  max-height: 90vh;
}

.rule-builder-container {
  border: 1px solid var(--color-border-primary);
  border-radius: var(--border-radius-md);
  padding: var(--spacing-lg);
  background: var(--color-bg-secondary);
  width: 100%;
}

.rule-builder-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-md);
  border-bottom: 2px solid var(--color-border-secondary);
}

.rule-builder-header-text {
  color: var(--color-text-secondary);
  font-size: var(--font-size-base);
}

.rule-conditions-list {
  min-height: 100px;
}

.rule-item {
  margin-bottom: var(--spacing-md);
}

.rule-item.nested {
  margin-left: var(--spacing-2xl);
  border-left: 2px solid var(--color-primary);
  padding-left: var(--spacing-md);
}

.rule-condition {
  background: var(--color-bg-primary);
  border: 1px solid var(--color-border-primary);
  border-radius: var(--border-radius-md);
  padding: var(--spacing-md);
}

.condition-content {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}

.metric-hint {
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
  margin-top: var(--spacing-sm);
  font-style: italic;
}

.rule-group {
  background: var(--color-bg-primary);
  border: 1px solid var(--color-primary);
  border-radius: var(--border-radius-md);
  padding: var(--spacing-md);
}

.rule-group.nested-group {
  border-color: var(--color-success);
}

.group-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-md);
  padding-bottom: var(--spacing-sm);
  border-bottom: 1px solid var(--color-border-secondary);
}

.group-header-text {
  color: var(--color-text-secondary);
  font-size: var(--font-size-base);
}

.group-content {
  margin-top: var(--spacing-md);
}

.empty-conditions {
  padding: 40px;
  text-align: center;
}

.rule-preview {
  margin-top: var(--spacing-2xl);
  padding-top: var(--spacing-lg);
  border-top: 2px solid var(--color-border-secondary);
}

.preview-text {
  background: white;
  padding: 12px;
  border-radius: 4px;
  border: 1px solid #dcdfe6;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
  font-size: 13px;
  color: var(--color-text-secondary);
  line-height: 1.6;
}

.form-hint {
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
  margin-top: var(--spacing-xs);
  line-height: var(--line-height-relaxed);
}
</style>
