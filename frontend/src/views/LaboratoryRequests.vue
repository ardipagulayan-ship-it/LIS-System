<template>
  <div class="page-container">
    <header class="page-header">
      <h1 class="page-title">Laboratory Requests</h1>
      <el-button type="primary" @click="handleAdd" class="btn btn-primary">
        <el-icon><Plus /></el-icon>New Request
      </el-button>
    </header>

    <main class="page-content">
      <div class="card">
        <div class="card__header">
          <div class="filter-row">
            <el-input
              v-model="searchQuery"
              placeholder="Search requests..."
              class="filter-input filter-input--large"
              clearable
              @clear="handleSearch"
              @input="handleSearch"
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>
          </div>
        </div>

        <div class="card__body">
          <div class="table-wrapper">
            <table v-loading="loading" class="table">
              <thead class="table__head">
                <tr>
                  <th class="table__cell table__cell--header">ID</th>
                  <th class="table__cell table__cell--header">Patient</th>
                  <th class="table__cell table__cell--header">Doctor</th>
                  <th class="table__cell table__cell--header">Request Date</th>
                  <th class="table__cell table__cell--header">Status</th>
                  <th class="table__cell table__cell--header">Total Amount</th>
                  <th class="table__cell table__cell--header">Actions</th>
                </tr>
              </thead>
              <tbody class="table__body">
                <tr v-for="row in requests" :key="row.id" class="table__row">
                  <td class="table__cell">
                    <div class="specimen-id-cell">
                      <el-tooltip
                        v-if="hasCdsViolations(row)"
                        placement="top"
                        effect="dark"
                      >
                        <template #content>
                          <div class="cds-tooltip-content">
                            <div class="tooltip-header">Triggered CDS Rules:</div>
                            <div v-for="(rule, index) in cdsViolations[row.id].triggered_rules" :key="index" class="tooltip-item">
                              <span :class="['rule-type-tag', rule.is_blocking ? 'blocking' : 'alert']">
                                {{ rule.is_blocking ? 'BLOCKING' : 'ALERT' }}
                              </span>
                              • {{ rule.rule_name }}: {{ rule.alert_message }}
                            </div>
                          </div>
                        </template>
                        <el-tag 
                          size="small" 
                          :type="hasBlockingCdsViolations(row) ? 'danger' : 'warning'"
                          effect="dark"
                          class="cds-list-tag"
                        >
                          CDS
                        </el-tag>
                      </el-tooltip>
                      {{ row.id }}
                    </div>
                  </td>
                  <td class="table__cell">{{ row.patient?.name }}</td>
                  <td class="table__cell">{{ row.doctor?.name }}</td>
                  <td class="table__cell">{{ formatDate(row.request_date) }}</td>
                  <td class="table__cell">
                    <div class="status-cell">
                      <template v-if="hasBlockingCdsViolations(row)">
                        <span class="status-pill status-pill-red" title="Blocked by CDS Rule">
                          CDS BLOCK
                        </span>
                      </template>
                      <template v-else>
                        <span :class="['badge', `badge--${getStatusType(row.status)}`]">
                          {{ row.status }}
                        </span>
                      </template>
                    </div>
                  </td>
                  <td class="table__cell">{{ formatCurrency(row.total_amount) }}</td>
                  <td class="table__cell table__cell--actions">
                    <div class="action-buttons">
                      <el-button type="primary" size="small" @click="handleView(row)" class="btn btn-sm">
                        <el-icon><View /></el-icon>
                      </el-button>
                      <el-button
                        type="success"
                        size="small"
                        @click="handleUpdateStatus(row)"
                        :disabled="row.status === 'Completed'"
                        class="btn btn-sm"
                      >
                        <el-icon><Check /></el-icon>
                      </el-button>
                      <el-button
                        type="danger"
                        size="small"
                        @click="handleDelete(row)"
                        :disabled="row.status === 'Completed'"
                        class="btn btn-sm"
                      >
                        <el-icon><Delete /></el-icon>
                      </el-button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="pagination-container">
            <el-pagination
              v-model:current-page="currentPage"
              v-model:page-size="pageSize"
              :page-sizes="[10, 20, 50, 100]"
              :total="total"
              layout="total, sizes, prev, pager, next, jumper"
              @size-change="handleSizeChange"
              @current-change="handleCurrentChange"
            />
          </div>
        </div>
      </div>
    </main>

    <!-- Add/Edit Request Dialog -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogType === 'add' ? 'New Laboratory Request' : 'Edit Request'"
      width="800px"
      class="request-dialog"
      :close-on-click-modal="false"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        class="form-vertical"
      >
        <div class="form-row">
          <div class="form-col">
            <el-form-item label="Patient" prop="patient_id">
              <el-select
                v-model="form.patient_id"
                placeholder="Select patient"
                filterable
                remote
                :remote-method="searchPatients"
                :loading="loadingPatients"
                style="width: 100%"
              >
                <el-option
                  v-for="patient in patients"
                  :key="patient.id"
                  :label="patient.name"
                  :value="patient.id"
                />
              </el-select>
            </el-form-item>
          </div>
          <div class="form-col">
            <el-form-item label="Doctor" prop="doctor_id">
              <el-select
                v-model="form.doctor_id"
                placeholder="Select doctor"
                filterable
                remote
                :remote-method="searchDoctors"
                :loading="loadingDoctors"
                style="width: 100%"
              >
                <el-option
                  v-for="doctor in doctors"
                  :key="doctor.id"
                  :label="doctor.name"
                  :value="doctor.id"
                />
              </el-select>
            </el-form-item>
          </div>
        </div>

        <el-form-item label="Request Date" prop="request_date">
          <el-date-picker
            v-model="form.request_date"
            type="datetime"
            placeholder="Select date and time"
            style="width: 100%"
          />
        </el-form-item>

        <el-form-item label="Tests" prop="tests">
          <el-select
            v-model="form.tests"
            multiple
            filterable
            placeholder="Select tests"
            style="width: 100%"
          >
            <el-option
              v-for="test in tests"
              :key="test.id"
              :label="test.name"
              :value="test.id"
            >
              <span>{{ test.name }}</span>
              <span class="test-price">{{ formatCurrency(test.price) }}</span>
            </el-option>
          </el-select>
        </el-form-item>

        <el-form-item label="Notes" prop="notes">
          <el-input
            v-model="form.notes"
            type="textarea"
            :rows="3"
            placeholder="Enter notes"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <div class="modal-footer">
          <el-button @click="dialogVisible = false" class="btn btn-secondary">Cancel</el-button>
          <el-button type="primary" @click="handleSubmit" :loading="submitting" class="btn btn-primary">
            {{ dialogType === 'add' ? 'Create' : 'Update' }}
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- View Request Dialog -->
    <el-dialog
      v-model="viewDialogVisible"
      title="Laboratory Request Details"
      width="800px"
      class="view-dialog"
      :close-on-click-modal="false"
    >
      <div v-if="selectedRequest" class="request-details">
        <div class="details-grid">
          <div class="detail-item">
            <span class="detail-label">Patient:</span>
            <span class="detail-value">{{ selectedRequest.patient?.name }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Doctor:</span>
            <span class="detail-value">{{ selectedRequest.doctor?.name }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Request Date:</span>
            <span class="detail-value">{{ formatDate(selectedRequest.request_date) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Status:</span>
            <span :class="['badge', `badge--${getStatusType(selectedRequest.status)}`]">
              {{ selectedRequest.status }}
            </span>
          </div>
        </div>

        <div class="tests-section">
          <h3 class="section-title">Requested Tests</h3>
          <table class="table table--compact">
            <thead class="table__head">
              <tr>
                <th class="table__cell table__cell--header">Test Name</th>
                <th class="table__cell table__cell--header">Price</th>
              </tr>
            </thead>
            <tbody class="table__body">
              <tr v-for="test in selectedRequest.tests" :key="test.id" class="table__row">
                <td class="table__cell">{{ test.name }}</td>
                <td class="table__cell">{{ formatCurrency(test.price) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="total-amount">
          <strong>Total Amount:</strong>
          <span class="amount-value">{{ formatCurrency(selectedRequest.total_amount) }}</span>
        </div>

        <div v-if="selectedRequest.notes" class="notes-section">
          <h3 class="section-title">Notes</h3>
          <p class="notes-text">{{ selectedRequest.notes }}</p>
        </div>
      </div>

      <template #footer>
        <div class="modal-footer">
          <el-button @click="viewDialogVisible = false" class="btn btn-secondary">Close</el-button>
        </div>
      </template>
    </el-dialog>

    <!-- Update Status Dialog -->
    <el-dialog
      v-model="statusDialogVisible"
      title="Update Request Status"
      width="400px"
      class="status-dialog"
      :close-on-click-modal="false"
    >
      <el-form
        ref="statusFormRef"
        :model="statusForm"
        :rules="statusRules"
        class="form-vertical"
      >
        <el-form-item label="Status" prop="status">
          <el-select v-model="statusForm.status" placeholder="Select status">
            <el-option label="Pending" value="Pending" />
            <el-option label="In Progress" value="In Progress" />
            <el-option label="Completed" value="Completed" />
            <el-option label="Cancelled" value="Cancelled" />
          </el-select>
        </el-form-item>
      </el-form>

      <template #footer>
        <div class="modal-footer">
          <el-button @click="statusDialogVisible = false" class="btn btn-secondary">Cancel</el-button>
          <el-button type="primary" @click="handleStatusSubmit" :loading="updatingStatus" class="btn btn-primary">
            Update
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- Delete Confirmation Dialog -->
    <el-dialog
      v-model="deleteDialogVisible"
      title="Delete Request"
      width="400px"
      class="delete-dialog"
      :close-on-click-modal="false"
    >
      <p class="confirmation-text">Are you sure you want to delete this request? This action cannot be undone.</p>

      <template #footer>
        <div class="modal-footer">
          <el-button @click="deleteDialogVisible = false" class="btn btn-secondary">Cancel</el-button>
          <el-button type="danger" @click="confirmDelete" :loading="deleting" class="btn btn-danger">
            Delete
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Plus, Edit, Delete, Search, View, Check } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import api from '@/utils/axios';

const loading = ref(false);
const submitting = ref(false);
const deleting = ref(false);
const updatingStatus = ref(false);
const requests = ref([]);
const patients = ref([]);
const doctors = ref([]);
const tests = ref([]);
const loadingPatients = ref(false);
const loadingDoctors = ref(false);
const total = ref(0);
const currentPage = ref(1);
const pageSize = ref(10);
const searchQuery = ref('');
const cdsViolations = ref({});

const dialogVisible = ref(false);
const viewDialogVisible = ref(false);
const statusDialogVisible = ref(false);
const deleteDialogVisible = ref(false);
const dialogType = ref('add');
const formRef = ref(null);
const statusFormRef = ref(null);
const selectedRequest = ref(null);

const form = ref({
  patient_id: '',
  doctor_id: '',
  request_date: '',
  tests: [],
  notes: ''
});

const statusForm = ref({
  status: ''
});

const rules = {
  patient_id: [
    { required: true, message: 'Please select patient', trigger: 'change' }
  ],
  doctor_id: [
    { required: true, message: 'Please select doctor', trigger: 'change' }
  ],
  request_date: [
    { required: true, message: 'Please select request date', trigger: 'change' }
  ],
  tests: [
    { required: true, message: 'Please select at least one test', trigger: 'change' }
  ]
};

const statusRules = {
  status: [
    { required: true, message: 'Please select status', trigger: 'change' }
  ]
};

const formatDate = (date) => {
  return new Date(date).toLocaleString();
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(value);
};

const getStatusType = (status) => {
  const types = {
    'Pending': 'warning',
    'In Progress': 'primary',
    'Completed': 'success',
    'Cancelled': 'danger'
  };
  return types[status] || 'info';
};

const fetchRequests = async () => {
  loading.value = true;
  try {
    const response = await api.get('/laboratory-requests', {
      params: {
        page: currentPage.value,
        per_page: pageSize.value,
        search: searchQuery.value
      }
    });
    requests.value = response.data.data;
    total.value = response.data.total;
    
    // Load CDS violations for current page
    loadCdsViolations();
  } catch (error) {
    ElMessage.error('Error fetching requests');
    console.error('Error:', error);
  } finally {
    loading.value = false;
  }
};

const loadCdsViolations = async () => {
  try {
    const requestIds = (requests.value || []).map(r => r.id).filter(id => !!id);
    if (requestIds.length === 0) return;

    const res = await api.post('/results/check-cds-bulk', {
      request_ids: requestIds
    });

    const results = res.data?.results || {};
    cdsViolations.value = { ...cdsViolations.value, ...results };
  } catch (e) {
    console.error('Failed loading CDS violations', e);
  }
};

const hasCdsViolations = (row) => {
  const cdsInfo = cdsViolations.value[row.id];
  return !!(cdsInfo && cdsInfo.triggered_rules && cdsInfo.triggered_rules.length > 0);
};

const hasBlockingCdsViolations = (row) => {
  const cdsInfo = cdsViolations.value[row.id];
  return !!(cdsInfo && cdsInfo.has_blocking_rules);
};

const fetchTests = async () => {
  try {
    const response = await api.get('/tests');
    tests.value = response.data.data;
  } catch (error) {
    ElMessage.error('Error fetching tests');
    console.error('Error:', error);
  }
};

const searchPatients = async (query) => {
  if (query) {
    loadingPatients.value = true;
    try {
      const response = await api.get('/patients', {
        params: { search: query }
      });
      patients.value = response.data.data;
    } catch (error) {
      console.error('Error:', error);
    } finally {
      loadingPatients.value = false;
    }
  } else {
    patients.value = [];
  }
};

const searchDoctors = async (query) => {
  if (query) {
    loadingDoctors.value = true;
    try {
      const response = await api.get('/doctors', {
        params: { search: query }
      });
      doctors.value = response.data.data;
    } catch (error) {
      console.error('Error:', error);
    } finally {
      loadingDoctors.value = false;
    }
  } else {
    doctors.value = [];
  }
};

const handleSearch = () => {
  currentPage.value = 1;
  fetchRequests();
};

const handleSizeChange = (val) => {
  pageSize.value = val;
  fetchRequests();
};

const handleCurrentChange = (val) => {
  currentPage.value = val;
  fetchRequests();
};

const resetForm = () => {
  form.value = {
    patient_id: '',
    doctor_id: '',
    request_date: '',
    tests: [],
    notes: ''
  };
  if (formRef.value) {
    formRef.value.resetFields();
  }
};

const handleAdd = () => {
  dialogType.value = 'add';
  resetForm();
  dialogVisible.value = true;
};

const handleView = (row) => {
  selectedRequest.value = row;
  viewDialogVisible.value = true;
};

const handleUpdateStatus = (row) => {
  selectedRequest.value = row;
  statusForm.value.status = row.status;
  statusDialogVisible.value = true;
};

const handleDelete = (row) => {
  selectedRequest.value = row;
  deleteDialogVisible.value = true;
};

const handleSubmit = async () => {
  if (!formRef.value) return;
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      submitting.value = true;
      try {
        if (dialogType.value === 'add') {
          await api.post('/laboratory-requests', form.value);
          ElMessage.success('Request created successfully');
        } else {
          await api.put(`/laboratory-requests/${form.value.id}`, form.value);
          ElMessage.success('Request updated successfully');
        }
        dialogVisible.value = false;
        fetchRequests();
      } catch (error) {
        ElMessage.error('Error saving request');
        console.error('Error:', error);
      } finally {
        submitting.value = false;
      }
    }
  });
};

const handleStatusSubmit = async () => {
  if (!statusFormRef.value) return;
  
  await statusFormRef.value.validate(async (valid) => {
    if (valid) {
      updatingStatus.value = true;
      try {
        await api.put(`/laboratory-requests/${selectedRequest.value.id}/status`, {
          status: statusForm.value.status
        });
        ElMessage.success('Status updated successfully');
        statusDialogVisible.value = false;
        fetchRequests();
      } catch (error) {
        ElMessage.error('Error updating status');
        console.error('Error:', error);
      } finally {
        updatingStatus.value = false;
      }
    }
  });
};

const confirmDelete = async () => {
  if (!selectedRequest.value) return;
  
  deleting.value = true;
  try {
    await api.delete(`/laboratory-requests/${selectedRequest.value.id}`);
    ElMessage.success('Request deleted successfully');
    deleteDialogVisible.value = false;
    fetchRequests();
  } catch (error) {
    ElMessage.error('Error deleting request');
    console.error('Error:', error);
  } finally {
    deleting.value = false;
  }
};

onMounted(() => {
  fetchRequests();
  fetchTests();
});
</script>

<style scoped>
.page-container {
  width: 100%;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-lg) var(--spacing-xl);
  background: var(--color-bg-primary);
  border-bottom: 1px solid var(--color-border-primary);
  margin-bottom: 0;
  box-shadow: var(--shadow-sm);
}

.page-title {
  margin: 0;
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-primary);
  letter-spacing: -0.5px;
}

/* Breadcrumb Navigation */
.breadcrumb {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  font-size: var(--font-size-sm);
}

.breadcrumb__item {
  color: var(--color-text-tertiary);
  display: flex;
  align-items: center;
}

.breadcrumb__item--active {
  color: var(--color-primary);
  font-weight: var(--font-weight-semibold);
}

.breadcrumb__link {
  color: var(--color-primary);
  text-decoration: none;
  transition: color var(--transition-normal) ease;
  cursor: pointer;
}

.breadcrumb__link:hover {
  color: var(--color-primary-dark);
  text-decoration: underline;
}

.breadcrumb__separator {
  color: var(--color-border-primary);
  margin: 0 var(--spacing-xs);
}

.page-content {
  flex: 1;
  padding: var(--spacing-2xl) var(--spacing-xl);
}

/* Card Styles */
.card {
  background-color: var(--color-bg-primary);
  border: 1px solid var(--color-border-primary);
  border-radius: var(--border-radius-md);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.card__header {
  padding: var(--spacing-lg);
  border-bottom: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-secondary);
}

.card__body {
  padding: var(--spacing-lg);
}

/* Filter Styles */
.filter-row {
  display: flex;
  gap: var(--spacing-md);
  align-items: flex-end;
}

.filter-input {
  flex: 1;
  min-width: 200px;
}

.filter-input--large {
  min-width: 300px;
}

/* Table Wrapper */
.table-wrapper {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

/* Table Styles */
.table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--font-size-sm);
}

.table__head {
  background-color: var(--color-bg-secondary);
  border-bottom: 2px solid var(--color-border-primary);
}

.table__row {
  border-bottom: 1px solid var(--color-border-secondary);
  transition: background-color var(--transition-normal);
}

.table__row:hover {
  background-color: var(--color-bg-secondary);
}

.table__cell {
  padding: var(--spacing-md) var(--spacing-lg);
  text-align: left;
  color: var(--color-text-primary);
}

.table__cell--header {
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-secondary);
  text-transform: uppercase;
  font-size: var(--font-size-xs);
  letter-spacing: 0.5px;
  white-space: nowrap;
}

.table__cell--actions {
  text-align: center;
}

.table--compact :deep(.table__cell) {
  padding: var(--spacing-sm) var(--spacing-md);
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: var(--spacing-xs);
  justify-content: center;
  flex-wrap: wrap;
}

/* Badge Styles */
.badge {
  display: inline-block;
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: var(--border-radius-full);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  white-space: nowrap;
}

.badge--success {
  background-color: var(--color-success-light);
  color: var(--color-success);
}

.badge--warning {
  background-color: var(--color-warning-light);
  color: var(--color-warning);
}

.badge--danger {
  background-color: var(--color-danger-light);
  color: var(--color-danger);
}

.badge--info {
  background-color: var(--color-primary-light);
  color: var(--color-primary);
}

.badge--primary {
  background-color: var(--color-primary-light);
  color: var(--color-primary);
}

/* Pagination */
.pagination-container {
  display: flex;
  justify-content: flex-end;
  padding: var(--spacing-lg);
  border-top: 1px solid var(--color-border-secondary);
  margin-top: var(--spacing-lg);
}

/* Form Styles */
.form-vertical {
  width: 100%;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-lg);
}

.form-col {
  width: 100%;
}

.form-row .form-col:only-child {
  grid-column: 1 / -1;
}

.test-price {
  float: right;
  color: var(--color-text-secondary);
  font-size: var(--font-size-xs);
}

/* Modal Styles */
.request-dialog :deep(.el-dialog__header) {
  border-bottom: 1px solid var(--color-border-secondary);
  padding: var(--spacing-lg);
}

.request-dialog :deep(.el-dialog__body) {
  padding: var(--spacing-lg);
}

.view-dialog :deep(.el-dialog__header) {
  border-bottom: 1px solid var(--color-border-secondary);
  padding: var(--spacing-lg);
}

.view-dialog :deep(.el-dialog__body) {
  padding: var(--spacing-lg);
}

.status-dialog :deep(.el-dialog__header) {
  border-bottom: 1px solid var(--color-border-secondary);
  padding: var(--spacing-lg);
}

.status-dialog :deep(.el-dialog__body) {
  padding: var(--spacing-lg);
}

.delete-dialog :deep(.el-dialog__header) {
  border-bottom: 1px solid var(--color-border-secondary);
  padding: var(--spacing-lg);
}

.delete-dialog :deep(.el-dialog__body) {
  padding: var(--spacing-lg);
}

/* Modal Footer */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-md);
  padding: var(--spacing-lg);
  border-top: 1px solid var(--color-border-secondary);
}

/* Request Details */
.request-details {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-lg);
  padding: var(--spacing-lg);
  background-color: var(--color-bg-secondary);
  border-radius: var(--border-radius-md);
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.detail-label {
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-secondary);
  font-size: var(--font-size-sm);
}

.detail-value {
  color: var(--color-text-primary);
}

/* Sections */
.tests-section {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.section-title {
  margin: 0;
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
}

/* Total Amount */
.total-amount {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: var(--spacing-md);
  padding: var(--spacing-lg);
  background-color: var(--color-bg-secondary);
  border-radius: var(--border-radius-md);
}

.amount-label {
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-secondary);
}

.amount-value {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-bold);
  color: var(--color-success);
}

/* CDS Styles */
.specimen-id-cell {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
}

.cds-list-tag {
  font-weight: bold;
  font-size: 12px;
  padding: 0 4px;
  height: 18px;
  line-height: 16px;
  cursor: help;
}

.cds-tooltip-content {
  padding: 4px;
  max-width: 350px;
}

.tooltip-header {
  font-weight: bold;
  margin-bottom: 8px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
  padding-bottom: 4px;
}

.tooltip-item {
  margin-bottom: 6px;
  line-height: 1.4;
  font-size: 12px;
}

.rule-type-tag {
  display: inline-block;
  padding: 1px 4px;
  border-radius: 3px;
  font-size: 12px;
  font-weight: bold;
  margin-right: 4px;
  vertical-align: middle;
}

.rule-type-tag.blocking {
  background-color: #f56c6c;
  color: white;
}

.rule-type-tag.alert {
  background-color: #e6a23c;
  color: white;
}

.status-pill-red {
  background-color: #fef0f0;
  color: #f56c6c;
  border: 1px solid #fbc4c4;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: bold;
}

.tooltip-item {
  margin-bottom: 2px;
  font-size: 12px;
}

.status-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--spacing-xs);
}

.status-pill {
  display: inline-flex;
  align-items: center;
  padding: 2px 5px;
  border-radius: var(--border-radius-lg);
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  white-space: nowrap;
}

.status-pill-red {
  background: var(--color-danger);
  color: var(--color-bg-primary);
  border: 1px solid var(--color-danger);
}

/* Notes */
.notes-section {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.notes-text {
  margin: 0;
  color: var(--color-text-secondary);
  line-height: var(--line-height-relaxed);
}

/* Confirmation Text */
.confirmation-text {
  margin: 0 0 var(--spacing-lg) 0;
  color: var(--color-text-primary);
  line-height: var(--line-height-relaxed);
}

/* Responsive Design */
@media (max-width: 1024px) {
  .filter-row {
    gap: var(--spacing-sm);
  }

  .filter-input--large {
    min-width: 200px;
  }

  .details-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--spacing-md);
    padding: var(--spacing-lg);
  }

  .page-content {
    padding: var(--spacing-lg);
  }

  .page-title {
    font-size: var(--font-size-xl);
  }

  .filter-row {
    width: 100%;
    flex-direction: column;
  }

  .filter-input,
  .filter-input--large {
    width: 100%;
    min-width: unset;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .table__cell {
    padding: var(--spacing-sm) var(--spacing-md);
    font-size: var(--font-size-xs);
  }

  .action-buttons {
    flex-direction: column;
    gap: var(--spacing-xs);
  }

  .action-buttons .btn {
    width: 100%;
  }

  .pagination-container {
    justify-content: center;
    flex-wrap: wrap;
  }

  .total-amount {
    flex-direction: column;
    align-items: flex-start;
  }
}

@media (max-width: 576px) {
  .page-header {
    padding: var(--spacing-md);
  }

  .page-content {
    padding: var(--spacing-md);
  }

  .page-title {
    font-size: var(--font-size-lg);
  }

  .card {
    border-radius: var(--border-radius-sm);
  }

  .card__header {
    padding: var(--spacing-md);
  }

  .card__body {
    padding: var(--spacing-md);
  }

  .table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin: 0 calc(var(--spacing-md) * -1);
    padding: 0 var(--spacing-md);
  }

  .table__cell {
    padding: var(--spacing-xs) var(--spacing-sm);
    font-size: var(--font-size-xs);
  }

  .details-grid {
    padding: var(--spacing-md);
    gap: var(--spacing-md);
  }

  .modal-footer {
    flex-direction: column-reverse;
  }

  .modal-footer .btn {
    width: 100%;
  }
}
</style> 