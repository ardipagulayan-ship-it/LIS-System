<template>
  <div class="lis-bridge-config">
    <div class="page-header">
      <h2 class="page-title">LIS Bridge Configuration</h2>
      <div class="header-actions">
        <el-button type="primary" @click="openCreateDialog">
          <el-icon><Plus /></el-icon>Add Bridge Config
        </el-button>
      </div>
    </div>

    <!-- Bridge Configs Table -->
    <el-card class="mb-4">
      <template #header>
        <div class="card-header">
          <span>Bridge Configurations</span>
          <el-input
            v-model="searchQuery"
            placeholder="Search configs..."
            class="search-input"
            clearable
            @input="fetchConfigs"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
        </div>
      </template>

      <el-table
        v-loading="loading"
        :data="paginatedConfigs"
        style="width: 100%"
        border
        stripe
        highlight-current-row
        @row-click="handleConfigSelect"
      >
        <el-table-column prop="name" label="Bridge Name" min-width="200" />
        <el-table-column prop="lis_url" label="LIS URL" min-width="200">
          <template #default="{ row }">
            {{ row.lis_url || '—' }}
          </template>
        </el-table-column>
        <el-table-column label="Status" width="120">
          <template #default="{ row }">
            <el-tag :type="row.is_active ? 'success' : 'danger'" size="small">
              {{ row.is_active ? 'Active' : 'Inactive' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="API Keys" width="100">
          <template #default="{ row }">
            <el-tag type="info" size="small">{{ row.api_keys_count }} key(s)</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Control ID Map" width="120">
          <template #default="{ row }">
            {{ row.control_id_map ? Object.keys(row.control_id_map).length + ' entries' : '—' }}
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="160" fixed="right">
          <template #default="{ row }">
            <el-button-group>
              <el-button type="primary" size="small" @click.stop="openEditDialog(row)">
                <el-icon><Edit /></el-icon>
              </el-button>
              <el-button type="info" size="small" @click.stop="openKeysDrawer(row)">
                <el-icon><Key /></el-icon>
              </el-button>
              <el-button type="danger" size="small" @click.stop="confirmDelete(row)">
                <el-icon><Delete /></el-icon>
              </el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[10, 20, 50]"
          :total="filteredConfigs.length"
          layout="total, sizes, prev, pager, next, jumper"
        />
      </div>
    </el-card>

    <!-- Ingest Logs -->
    <el-card>
      <template #header>
        <div class="card-header">
          <span>Ingest Logs</span>
          <el-button-group>
            <el-button size="small" @click="fetchLogs">Refresh</el-button>
            <el-button size="small" type="warning" @click="openLogFilter">Filter</el-button>
          </el-button-group>
        </div>
      </template>

      <el-table
        v-loading="logLoading"
        :data="logs"
        style="width: 100%"
        border
        stripe
        max-height="400"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="Bridge" width="150">
          <template #default="{ row }">
            {{ getConfigName(row.lis_bridge_config_id) }}
          </template>
        </el-table-column>
        <el-table-column prop="route" label="Route" min-width="200" />
        <el-table-column label="Status" width="100">
          <template #default="{ row }">
            <el-tag :type="statusTagType(row.status_code)" size="small">
              {{ row.status_code }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="duration_ms" label="Duration (ms)" width="120">
          <template #default="{ row }">
            <span :class="row.duration_ms > 5000 ? 'text-danger' : ''">
              {{ row.duration_ms }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="Timestamp" width="180">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="Request" width="80" fixed="right">
          <template #default="{ row }">
            <el-button size="small" text type="primary" @click="viewRequest(row)">
              View
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container">
        <el-pagination
          v-model:current-page="logPage"
          v-model:page-size="logPageSize"
          :page-sizes="[20, 50, 100]"
          :total="logTotal"
          layout="total, sizes, prev, pager, next, jumper"
          @current-change="fetchLogs"
          @size-change="fetchLogs"
        />
      </div>
    </el-card>

    <!-- Create / Edit Bridge Config Dialog -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="600px"
      @close="closeDialog"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="formRules"
        label-width="150px"
        label-position="left"
      >
        <el-form-item label="Bridge Name" prop="name">
          <el-input v-model="form.name" placeholder="e.g. Sysmex XN-1000 Bridge" />
        </el-form-item>

        <el-form-item label="LIS URL" prop="lis_url">
          <el-input v-model="form.lis_url" placeholder="http://middleware.local:8000" />
        </el-form-item>

        <el-form-item label="Patient Endpoint" prop="patient_lis_url">
          <el-input v-model="form.patient_lis_url" placeholder="http://middleware.local:8000/api/patient-results" />
        </el-form-item>

        <el-form-item label="QC Endpoint" prop="qc_lis_url">
          <el-input v-model="form.qc_lis_url" placeholder="http://middleware.local:8000/api/qc-results" />
        </el-form-item>

        <el-form-item label="Active" prop="is_active">
          <el-switch v-model="form.is_active" />
        </el-form-item>

        <el-form-item label="Control ID Map" prop="control_id_map">
          <div class="control-id-map-editor">
            <div v-for="(value, key, idx) in form.control_id_map" :key="idx" class="map-entry">
              <el-input v-model="form.control_id_map[key]" type="number" placeholder="QC ID" style="width: 120px" />
              <span class="map-arrow">→</span>
              <el-input v-model="form.control_id_map[key]" type="number" placeholder="Local QC ID" style="width: 120px" />
              <el-button type="danger" size="small" @click="removeMapEntry(key)">
                <el-icon><Delete /></el-icon>
              </el-button>
            </div>
            <div class="map-add-row">
              <el-input v-model="newMapKey" placeholder="Wire control_id (e.g. QC_NORMAL)" style="width: 180px" />
              <span class="map-arrow">→</span>
              <el-input v-model="newMapValue" type="number" placeholder="Local QC ID" style="width: 140px" />
              <el-button type="primary" size="small" @click="addMapEntry">Add</el-button>
            </div>
          </div>
          <div class="form-tip">
            Maps wire-format control_id strings (sent by the middleware) to local quality_controls.id values.
            Example: <code>QC_NORMAL</code> → <code>1</code>
          </div>
        </el-form-item>
      </el-form>

      <template #footer>
        <span class="dialog-footer">
          <el-button @click="dialogVisible = false">Cancel</el-button>
          <el-button type="primary" @click="saveConfig" :loading="dialogSaving">
            {{ editedIndex === -1 ? 'Create' : 'Update' }}
          </el-button>
        </span>
      </template>
    </el-dialog>

    <!-- Delete Confirmation Dialog -->
    <el-dialog v-model="deleteDialogVisible" title="Delete Bridge Config" width="400px">
      <p>
        Are you sure you want to delete <strong>{{ deleteTarget?.name }}</strong>?
        This will also delete all associated API keys.
      </p>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="deleteDialogVisible = false">Cancel</el-button>
          <el-button type="danger" @click="executeDelete">Delete</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- API Keys Drawer -->
    <el-drawer
      v-model="keysDrawerVisible"
      :title="`API Keys — ${selectedConfig?.name}`"
      size="550px"
      direction="rtl"
    >
      <div class="keys-drawer-content">
        <!-- Create new key -->
        <el-card class="mb-4" shadow="never">
          <template #header>
            <span>Generate New Key</span>
          </template>
          <div class="create-key-row">
            <el-input
              v-model="newKeyExpiresAt"
              type="date"
              placeholder="Optional expiry date"
              style="width: 200px"
            />
            <el-button type="primary" @click="generateKey" :loading="generatingKey">
              Generate Key
            </el-button>
          </div>
          <div v-if="newRawToken" class="new-token-display">
            <el-alert type="warning" :closable="false" show-icon>
              <template #title>
                Copy this token now — it will NOT be shown again!
              </template>
            </el-alert>
            <div class="token-value">{{ newRawToken }}</div>
            <el-button type="default" size="small" @click="copyToken">Copy to Clipboard</el-button>
          </div>
        </el-card>

        <!-- Existing keys -->
        <el-table :data="apiKeys" border stripe>
          <el-table-column prop="id" label="ID" width="60" />
          <el-table-column prop="key_prefix" label="Key Hash (partial)" min-width="180" />
          <el-table-column label="Status" width="100">
            <template #default="{ row }">
              <el-tag v-if="row.revoked_at" type="danger" size="small">Revoked</el-tag>
              <el-tag v-else-if="row.expires_at && row.expires_at < new Date().toISOString()" type="warning" size="small">Expired</el-tag>
              <el-tag v-else type="success" size="small">Active</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="last_used_at" label="Last Used" width="150">
            <template #default="{ row }">
              {{ row.last_used_at ? formatDate(row.last_used_at) : 'Never' }}
            </template>
          </el-table-column>
          <el-table-column prop="expires_at" label="Expires" width="150">
            <template #default="{ row }">
              {{ row.expires_at ? formatDate(row.expires_at) : 'Never' }}
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="120" fixed="right">
            <template #default="{ row }">
              <el-button
                v-if="!row.revoked_at"
                type="warning"
                size="small"
                @click="revokeKey(row)"
              >Revoke</el-button>
              <el-button
                type="danger"
                size="small"
                @click="deleteKey(row)"
              >Delete</el-button>
            </template>
          </el-table-column>
        </el-table>

        <!-- Test Connection -->
        <div class="test-connection-section">
          <el-divider>Connection Test</el-divider>
          <el-button type="info" @click="testConnection" :loading="testingConnection">
            Test Bridge Connection
          </el-button>
          <div v-if="connectionTestResult" class="connection-result mt-3">
            <el-alert
              :type="connectionTestResult.ok ? 'success' : 'danger'"
              :title="connectionTestResult.ok ? 'Connection successful' : 'Connection failed'"
              :description="connectionTestResult.message || `HTTP ${connectionTestResult.http_status}`"
              show-icon
            />
          </div>
        </div>
      </div>
    </el-drawer>

    <!-- Request Body Viewer Dialog -->
    <el-dialog v-model="requestViewerVisible" title="Request Details" width="700px">
      <pre class="request-body-viewer">{{ requestViewerContent }}</pre>
    </el-dialog>

    <!-- Log Filter Dialog -->
    <el-dialog v-model="logFilterVisible" title="Filter Logs" width="500px">
      <el-form label-width="140px">
        <el-form-item label="Bridge Config">
          <el-select v-model="logFilter.config_id" placeholder="All configs" clearable>
            <el-option
              v-for="c in configs"
              :key="c.id"
              :label="c.name"
              :value="c.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Status Code">
          <el-input v-model.number="logFilter.status_code" placeholder="e.g. 422" clearable />
        </el-form-item>
        <el-form-item label="Route">
          <el-input v-model="logFilter.route" placeholder="/api/v1/patient-results" clearable />
        </el-form-item>
        <el-form-item label="From Date">
          <el-date-picker v-model="logFilter.from" type="date" value-format="YYYY-MM-DD" style="width: 100%" />
        </el-form-item>
        <el-form-item label="To Date">
          <el-date-picker v-model="logFilter.to" type="date" value-format="YYYY-MM-DD" style="width: 100%" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="logFilterVisible = false">Cancel</el-button>
        <el-button type="primary" @click="applyLogFilter">Apply Filter</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Plus, Edit, Delete, Search, Key } from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import api from '@/utils/axios';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();

// ── Config table ──────────────────────────────────────────────────────────────
const loading = ref(false);
const configs = ref([]);
const searchQuery = ref('');
const currentPage = ref(1);
const pageSize = ref(10);

const filteredConfigs = computed(() => {
  if (!searchQuery.value) return configs.value;
  const q = searchQuery.value.toLowerCase();
  return configs.value.filter(c =>
    c.name.toLowerCase().includes(q) ||
    (c.lis_url || '').toLowerCase().includes(q)
  );
});

const paginatedConfigs = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  return filteredConfigs.value.slice(start, start + pageSize.value);
});

// ── Dialog ────────────────────────────────────────────────────────────────────
const dialogVisible = ref(false);
const dialogTitle = ref('');
const dialogSaving = ref(false);
const editedIndex = ref(-1);
const formRef = ref(null);
const form = ref({
  name: '',
  lis_url: '',
  patient_lis_url: '',
  qc_lis_url: '',
  is_active: true,
  control_id_map: {},
});
const newMapKey = ref('');
const newMapValue = ref('');

const formRules = {
  name: [{ required: true, message: 'Bridge name is required', trigger: 'blur' }],
};

function openCreateDialog() {
  dialogTitle.value = 'New Bridge Config';
  editedIndex.value = -1;
  form.value = {
    name: '',
    lis_url: '',
    patient_lis_url: '',
    qc_lis_url: '',
    is_active: true,
    control_id_map: {},
  };
  dialogVisible.value = true;
}

function openEditDialog(row) {
  dialogTitle.value = 'Edit Bridge Config';
  editedIndex.value = configs.value.findIndex(c => c.id === row.id);
  form.value = {
    name: row.name,
    lis_url: row.lis_url || '',
    patient_lis_url: row.patient_lis_url || '',
    qc_lis_url: row.qc_lis_url || '',
    is_active: row.is_active,
    control_id_map: row.control_id_map || {},
  };
  dialogVisible.value = true;
}

function closeDialog() {
  dialogVisible.value = false;
  formRef.value?.resetFields();
  newMapKey.value = '';
  newMapValue.value = '';
}

function addMapEntry() {
  if (!newMapKey.value) return;
  form.value.control_id_map[newMapKey.value] = newMapValue.value ? parseInt(newMapValue.value) : null;
  newMapKey.value = '';
  newMapValue.value = '';
}

function removeMapEntry(key) {
  delete form.value.control_id_map[key];
}

async function saveConfig() {
  const valid = await formRef.value.validate().catch(() => false);
  if (!valid) return;

  dialogSaving.value = true;
  try {
    if (editedIndex.value === -1) {
      // Create
      const { data } = await api.post('/lis-bridge/configs', form.value);
      configs.value.unshift(data);
      ElMessage.success('Bridge config created.');
    } else {
      // Update
      const id = configs.value[editedIndex.value].id;
      const { data } = await api.put(`/lis-bridge/configs/${id}`, form.value);
      configs.value[editedIndex.value] = data;
      ElMessage.success('Bridge config updated.');
    }
    dialogVisible.value = false;
  } catch (err) {
    ElMessage.error(err.response?.data?.message || 'Failed to save config.');
  } finally {
    dialogSaving.value = false;
  }
}

// ── Delete ────────────────────────────────────────────────────────────────────
const deleteDialogVisible = ref(false);
const deleteTarget = ref(null);

function confirmDelete(row) {
  deleteTarget.value = row;
  deleteDialogVisible.value = true;
}

async function executeDelete() {
  try {
    await api.delete(`/lis-bridge/configs/${deleteTarget.value.id}`);
    configs.value = configs.value.filter(c => c.id !== deleteTarget.value.id);
    ElMessage.success('Bridge config deleted.');
    deleteDialogVisible.value = false;
  } catch (err) {
    ElMessage.error('Failed to delete config.');
  }
}

// ── API Keys Drawer ────────────────────────────────────────────────────────────
const keysDrawerVisible = ref(false);
const selectedConfig = ref(null);
const apiKeys = ref([]);
const generatingKey = ref(false);
const newRawToken = ref('');
const testingConnection = ref(false);
const connectionTestResult = ref(null);
const newKeyExpiresAt = ref('');

async function openKeysDrawer(row) {
  selectedConfig.value = row;
  keysDrawerVisible.value = true;
  connectionTestResult.value = null;
  newRawToken.value = '';
  newKeyExpiresAt.value = '';
  await fetchKeys(row.id);
}

async function fetchKeys(configId) {
  try {
    const { data } = await api.get(`/lis-bridge/configs/${configId}/keys`);
    apiKeys.value = data;
  } catch (err) {
    ElMessage.error('Failed to load API keys.');
  }
}

async function generateKey() {
  generatingKey.value = true;
  newRawToken.value = '';
  try {
    const payload = {};
    if (newKeyExpiresAt.value) payload.expires_at = newKeyExpiresAt.value;

    const { data } = await api.post(
      `/lis-bridge/configs/${selectedConfig.value.id}/keys`,
      payload
    );
    newRawToken.value = data.raw_token;
    apiKeys.value.unshift(data);
    ElMessage.success('API key generated. Copy it now — it will not be shown again!');
  } catch (err) {
    ElMessage.error('Failed to generate API key.');
  } finally {
    generatingKey.value = false;
  }
}

function copyToken() {
  navigator.clipboard.writeText(newRawToken.value).then(() => {
    ElMessage.success('Token copied to clipboard.');
  });
}

async function revokeKey(row) {
  try {
    await api.post(`/lis-bridge/configs/${selectedConfig.value.id}/keys/${row.id}/revoke`);
    await fetchKeys(selectedConfig.value.id);
    ElMessage.success('API key revoked.');
  } catch (err) {
    ElMessage.error('Failed to revoke key.');
  }
}

async function deleteKey(row) {
  try {
    await ElMessageBox.confirm('Permanently delete this API key?', 'Delete API Key', {
      confirmButtonText: 'Delete',
      cancelButtonText: 'Cancel',
      type: 'warning',
    });
    await api.delete(`/lis-bridge/configs/${selectedConfig.value.id}/keys/${row.id}`);
    apiKeys.value = apiKeys.value.filter(k => k.id !== row.id);
    ElMessage.success('API key deleted.');
  } catch (err) {
    if (err !== 'cancel') ElMessage.error('Failed to delete key.');
  }
}

async function testConnection() {
  testingConnection.value = true;
  connectionTestResult.value = null;
  try {
    const { data } = await api.post(`/lis-bridge/internal-test`, { config_id: selectedConfig.value.id });
    connectionTestResult.value = data;
  } catch (err) {
    connectionTestResult.value = {
      ok: false,
      message: err.response?.data?.message || 'Request failed.',
    };
  } finally {
    testingConnection.value = false;
  }
}

// ── Ingest Logs ───────────────────────────────────────────────────────────────
const logLoading = ref(false);
const logs = ref([]);
const logPage = ref(1);
const logPageSize = ref(20);
const logTotal = ref(0);
const logFilterVisible = ref(false);
const logFilter = ref({
  config_id: null,
  status_code: null,
  route: '',
  from: '',
  to: '',
});

async function fetchLogs() {
  logLoading.value = true;
  try {
    const params = {
      per_page: logPageSize.value,
      ...Object.fromEntries(
        Object.entries(logFilter.value).filter(([, v]) => v !== '' && v !== null && v !== undefined)
      ),
    };
    const { data } = await api.get('/lis-bridge/ingest-logs', { params });
    logs.value = data.data;
    logTotal.value = data.total;
  } catch (err) {
    ElMessage.error('Failed to load ingest logs.');
  } finally {
    logLoading.value = false;
  }
}

function openLogFilter() {
  logFilterVisible.value = true;
}

function applyLogFilter() {
  logPage.value = 1;
  logFilterVisible.value = false;
  fetchLogs();
}

// ── Request Viewer ─────────────────────────────────────────────────────────────
const requestViewerVisible = ref(false);
const requestViewerContent = ref('');

function viewRequest(row) {
  const body = typeof row.request_body === 'string' ? JSON.parse(row.request_body) : row.request_body;
  requestViewerContent.value = JSON.stringify(body, null, 2);
  requestViewerVisible.value = true;
}

// ── Utilities ─────────────────────────────────────────────────────────────────
async function fetchConfigs() {
  loading.value = true;
  try {
    const { data } = await api.get('/lis-bridge/configs');
    configs.value = data;
  } catch (err) {
    ElMessage.error('Failed to load bridge configs.');
  } finally {
    loading.value = false;
  }
}

function getConfigName(configId) {
  return configs.value.find(c => c.id === configId)?.name || `Config #${configId}`;
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleString();
}

function statusTagType(code) {
  if (code >= 200 && code < 300) return 'success';
  if (code >= 400 && code < 500) return 'warning';
  if (code >= 500) return 'danger';
  return 'info';
}

function handleConfigSelect(row) {
  // Could show a detail panel
}

// ── Init ──────────────────────────────────────────────────────────────────────
onMounted(() => {
  fetchConfigs();
  fetchLogs();
});
</script>

<style scoped>
.lis-bridge-config {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.search-input {
  width: 300px;
}

.pagination-container {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
}

.mb-4 {
  margin-bottom: 16px;
}

.mt-3 {
  margin-top: 12px;
}

/* Control ID Map editor */
.control-id-map-editor {
  width: 100%;
}

.map-entry {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.map-arrow {
  color: #909399;
  font-size: 13px;
}

.map-add-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
}

.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 6px;
  line-height: 1.4;
}

.form-tip code {
  background: #f5f7fa;
  padding: 1px 4px;
  border-radius: 3px;
  font-size: 12px;
}

/* API Key drawer */
.keys-drawer-content {
  padding: 0 16px;
}

.create-key-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.new-token-display {
  margin-top: 12px;
}

.token-value {
  font-family: monospace;
  font-size: 12px;
  background: #1e1e1e;
  color: #d4d4d4;
  padding: 12px;
  border-radius: 4px;
  word-break: break-all;
  margin: 10px 0;
}

.test-connection-section {
  margin-top: 16px;
}

/* Request viewer */
.request-body-viewer {
  font-family: 'Courier New', monospace;
  font-size: 12px;
  background: #1e1e1e;
  color: #d4d4d4;
  padding: 16px;
  border-radius: 4px;
  overflow-x: auto;
  max-height: 500px;
  overflow-y: auto;
  white-space: pre-wrap;
  word-break: break-all;
}

.text-danger {
  color: #f56c6c;
  font-weight: 600;
}
</style>
