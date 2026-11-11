<template>
    <div class="phlebotomy">
    <div class="page-header">
      <h2 class="page-title">Phlebotomy</h2>
      </div>
    </div>
  <div class="phlebotomy-container">
    <!-- Left side: Request List -->
    <div class="request-list">
      <div class="header">
        <div class="filters">
          <el-input
            v-model="searchQuery"
            placeholder="Search by ID or Name..."
            clearable
            @input="handleSearch"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
        </div>
      </div>

      <div class="request-cards" v-loading="loading">
        <el-card
          v-for="request in requests"
          :key="request.id"
          :class="['request-card', { 'selected': selectedRequest?.id === request.id }]"
          @click="selectRequest(request)"
          shadow="hover"
        >
          <div class="request-header">
            <div class="header-left">
              <span class="sample-id">{{ request.sample_id || request.id }}</span>
            </div>
            <el-badge
                :value="request.priority_level"
                :type="getPriorityType(request.priority_level)"
                class="priority-badge"
              />
          </div>
          <div class="patient-info">
            <h3>{{ request.patient.lname }}, {{ request.patient.fname }} {{ request.patient.mname }}</h3>
            <p>
              <span v-if="request.ward">Ward: {{ request.ward.name }}</span>
              <span v-if="request.room"> / Room: {{ request.room.name }}</span>
            </p>
            <p>
              <span class="patient-id">ID: {{ request.patient.id }}</span>
              <span class="separator">•</span>
              <span>{{ request.patient.gender }} | {{ calculateAge(request.patient.date_of_birth) }} years</span>
            </p>
          </div>
          <div class="request-footer">
            <span>{{ formatDate(request.request_date) }}</span>
            <span>{{ request.tests.length }} test{{ request.tests.length > 1 ? 's' : '' }}</span>
          </div>
        </el-card>
      </div>

      <div class="pagination">
        <el-pagination
          v-if="total > 0"
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[10, 20, 50]"
          :total="total"
          layout="total, sizes, prev, pager, next"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
          small
        />
      </div>
    </div>

    <!-- Right side: Request Details -->
    <div class="request-details" v-if="selectedRequest">
      <div class="details-header">
        <div class="header-info">
          <h2>Request Details</h2>
          <el-tag :type="getPriorityType(selectedRequest.priority_level)" size="large">
            {{ selectedRequest.priority_level }}
          </el-tag>
        </div>
      </div>

      <div class="tests-section">
        <el-table :data="selectedRequest.tests" border stripe>
          <el-table-column label="Status" width="100" align="center">
            <template #default="{ row }">
              <el-tag :type="getStatusType(row.pivot.item_status)" size="small">{{ row.pivot.item_status }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="name" label="Test Name" min-width="180" />
          <el-table-column label="Actions" width="100" fixed="right" align="center">
            <template #default="{ row }">
              <div style="display: flex; justify-content: center; gap: 4px">
                <el-tooltip content="Collect Sample" placement="top" v-if="row.pivot.item_status === 'Pending'">
                  <el-button
                    type="primary"
                    size="small"
                    circle
                    :loading="row.collecting"
                    @click="collectSample(row)"
                  >
                    <el-icon><CollectionTag /></el-icon>
                  </el-button>
                </el-tooltip>
                <el-tooltip content="Accept Item" placement="top" v-if="row.pivot.item_status === 'Collected'">
                  <el-button
                    type="success"
                    size="small"
                    circle
                    :loading="row.accepting"
                    @click="acceptTestItem(row)"
                  >
                    <el-icon><Check /></el-icon>
                  </el-button>
                </el-tooltip>
                <el-tooltip content="Print Barcode" placement="top" v-if="row.pivot.item_status === 'Accepted'">
                  <el-button
                    type="primary"
                    size="small"
                    circle
                    @click="printBarcode(row)"
                  >
                    <el-icon><Printer /></el-icon>
                  </el-button>
                </el-tooltip>
                <el-tooltip content="Reject Item (Not Implemented)" placement="top" v-if="row.pivot.item_status === 'Pending' || row.pivot.item_status === 'Collected'">
                  <el-button
                    type="danger"
                    size="small"
                    circle
                    disabled
                    @click="handleTestAction(row, 'reject')"
                  >
                    <el-icon><Close /></el-icon>
                  </el-button>
                </el-tooltip>
              </div>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </div>

    <!-- Re-add Reject Dialog -->
    <el-dialog
      v-model="rejectDialogVisible"
      title="Reject Request Item"
      width="500px"
      destroy-on-close
    >
      <el-form :model="rejectForm" ref="rejectFormRef" :rules="rejectRules" label-position="top">
        <p>Rejecting Test: <strong>{{ currentlyRejectingItem?.name || '' }}</strong></p>
        <el-form-item label="Reason for Rejection" prop="reason">
          <el-input
            v-model="rejectForm.reason"
            type="textarea"
            :rows="3"
            placeholder="Please provide a reason for rejection"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="rejectDialogVisible = false">Cancel</el-button>
          <el-button type="danger" @click="confirmReject" :loading="submitting">
            Confirm Rejection
          </el-button>
        </span>
      </template>
    </el-dialog>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Search, Check, Close, Printer, Box, CollectionTag } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import api from '@/utils/axios';
import JsBarcode from 'jsbarcode';
import debounce from 'lodash/debounce';
import { format, parseISO } from 'date-fns';

const loading = ref(false);
const submitting = ref(false);
const searchQuery = ref('');
const currentPage = ref(1);
const pageSize = ref(10);
const total = ref(0);
const requests = ref([]);
const selectedRequest = ref(null);

// Re-add Reject Dialog State
const rejectDialogVisible = ref(false);
const rejectFormRef = ref(null);
const rejectForm = ref({ reason: '' });
const rejectRules = {
  reason: [{ required: true, message: 'Please provide a reason for rejection', trigger: 'blur' }]
};
const currentlyRejectingItem = ref(null);

const fetchRequests = async () => {
  loading.value = true;
  selectedRequest.value = null;
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
      search: searchQuery.value,
    };
    Object.keys(params).forEach(key => (!params[key]) && delete params[key]);

    const response = await api.get('/phlebotomy/requests', { params });

    const processedData = response.data.data.map(req => ({
        ...req,
        sample_id: req.sample_id,
        priority_level: req.priority_level,
        request_date: req.request_date,
        tests: req.tests.map(test => ({ 
            ...test, 
            accepting: false,
            collecting: false
        }))
    }));

    requests.value = processedData;
    total.value = response.data.total;

    if (selectedRequest.value) {
      const reselected = requests.value.find(r => r.id === selectedRequest.value.id);
      selectedRequest.value = reselected || (requests.value.length > 0 ? requests.value[0] : null);
    } else if (requests.value.length > 0) {
        selectedRequest.value = requests.value[0];
    }

  } catch (error) {
    console.error('Error fetching phlebotomy requests:', error);
    ElMessage.error('Failed to fetch laboratory requests');
    requests.value = [];
    total.value = 0;
    selectedRequest.value = null;
  } finally {
    loading.value = false;
  }
};

const selectRequest = (request) => {
  selectedRequest.value = request;
};

const handleSearch = debounce(() => {
  currentPage.value = 1;
  fetchRequests();
}, 300);

const handleSizeChange = (val) => {
  pageSize.value = val;
  currentPage.value = 1;
  fetchRequests();
};

const handleCurrentChange = (val) => {
  currentPage.value = val;
  fetchRequests();
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  try {
    return format(parseISO(dateString), 'MMM dd, yyyy HH:mm');
  } catch (e) {
    console.error("Error formatting date:", e);
    return dateString;
  }
};

const calculateAge = (dateOfBirth) => {
  if (!dateOfBirth) return 'N/A';
  try {
    const today = new Date();
    const birthDate = parseISO(dateOfBirth);
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
      age--;
    }
    return age >= 0 ? age : 0;
  } catch (e) {
    return 'N/A';
  }
};

const getStatusType = (status) => {
  const types = {
    'Pending': 'warning',
    'Collected': 'primary',
    'Accepted': 'success',
    'Rejected': 'danger',
    'In Progress': 'primary'
  };
  return types[status] || 'info';
};

const getPriorityType = (priority) => {
  const types = {
    'STAT': 'danger',
    'Urgent': 'warning',
    'Routine': 'primary'
  };
  return types[priority] || 'info';
};

const collectSample = async (testItem) => {
  if (!testItem || !testItem.pivot || !testItem.pivot.id) {
    console.error('Invalid test item data for collection:', testItem);
    ElMessage.error('Cannot collect item: Invalid data.');
    return;
  }
  const pivotId = testItem.pivot.id;
  testItem.collecting = true;

  try {
    await api.put(`/phlebotomy/requests/${pivotId}/collect`);
    ElMessage.success(`Test "${testItem.name}" marked as Collected.`);

    const testIndex = selectedRequest.value.tests.findIndex(t => t.pivot.id === pivotId);
    if (testIndex !== -1) {
        selectedRequest.value.tests[testIndex].pivot.item_status = 'Collected';
    }

  } catch (error) {
    console.error(`Error collecting test item ${pivotId}:`, error);
    ElMessage.error(`Failed to mark test "${testItem.name}" as Collected.`);
  } finally {
     if (testItem) testItem.collecting = false;
  }
};

const acceptTestItem = async (testItem) => {
  if (!testItem || !testItem.pivot || !testItem.pivot.id) {
    console.error('Invalid test item data for acceptance:', testItem);
    ElMessage.error('Cannot accept item: Invalid data.');
    return;
  }

  const pivotId = testItem.pivot.id;
  testItem.accepting = true;

  try {
    await api.put(`/phlebotomy/requests/${pivotId}/accept`);
    ElMessage.success(`Test "${testItem.name}" accepted successfully.`);

    const testIndex = selectedRequest.value.tests.findIndex(t => t.pivot.id === pivotId);
    if (testIndex !== -1) {
        selectedRequest.value.tests[testIndex].pivot.item_status = 'Accepted';
    }

  } catch (error) {
    console.error(`Error accepting test item ${pivotId}:`, error);
    ElMessage.error(`Failed to accept test "${testItem.name}".`);
  } finally {
     if (testItem) testItem.accepting = false;
  }
};

const printBarcode = (test) => {
  if (!selectedRequest.value || !test.pivot || !test.pivot.id) {
    console.warn("Cannot print barcode: Missing data.");
    return;
  }

  const sampleId = selectedRequest.value.sample_id || selectedRequest.value.id;
  const requestDate = selectedRequest.value.request_date;
  const patient = selectedRequest.value.patient;
  const barcodeData = `${sampleId}-${test.pivot.id}`;
  const testName = test.name || 'N/A';
  const patientLname = patient?.lname || '';
  const patientFname = patient?.fname || '';

  const canvas = document.createElement('canvas');
  try {
    JsBarcode(canvas, barcodeData, {
      format: 'CODE128',
      width: 1.5,
      height: 60,
      displayValue: true,
      fontSize: 14,
      margin: 5,
      textMargin: 2
    });
  } catch (e) {
    console.error("JsBarcode Error:", e);
    ElMessage.error('Failed to generate barcode.');
    return;
  }

  const printWindow = window.open('', '_blank');
  if (!printWindow) {
    ElMessage.error('Failed to open print window.');
    return;
  }

  const htmlContent = `
  <!DOCTYPE html>
  <html>
    <head>
      <title>Print Barcode</title>
      <style>
        @media print { @page { margin: 3mm; size: auto; } html, body { margin: 0; padding: 0; } }\n    body { margin: 5px; font-family: 'Arial', sans-serif; line-height: 1.2; }\n    .label-container { display: inline-block; text-align: center; page-break-inside: avoid; border: 1px solid #eee; padding: 3px; margin: 2px; }\n    .barcode-img { display: block; margin: 0 auto 2px auto; }\n    .patient-info p { margin: 0; font-size: 9px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; }\n  </style>
    </head>
    <body>
      <div class="label-container">
        <img class="barcode-img" src="${canvas.toDataURL()}" alt="Barcode"/>
        <div class="patient-info">
          <p>${patientLname}, ${patientFname}</p>
          <p>SID: ${sampleId}</p>
          <p>${testName}</p>
          <p>${formatDate(requestDate)}</p>
        </div>
      </div>
      <script>
        window.onload = function() {
          try {
            window.print();
            let printed = false;
            window.onafterprint = function() { printed = true; setTimeout(window.close, 50); };
            setTimeout(() => { if (!printed) { window.close(); } }, 500);
          } catch(e) {
            console.error('Print Error:', e);
            window.close();
          }
        };
      <\/script>
    </body>
  </html>`;

  try {
    printWindow.document.open();
    printWindow.document.write(htmlContent);
    printWindow.document.close();
  } catch (e) {
    console.error("Error writing to print window:", e);
    ElMessage.error('Failed to prepare print content.');
    try { printWindow.close(); } catch (err) {}
  }
};

const handleTestAction = (testItem, action) => {
  if (action === 'reject') {
      currentlyRejectingItem.value = testItem;
      rejectForm.value.reason = '';
      rejectDialogVisible.value = true;
      ElMessage.info('Reject functionality dialog shown (backend not implemented yet).');
  } else {
      ElMessage.warning(`Action '${action}' not implemented.`);
  }
};

const confirmReject = async () => {
  if (!currentlyRejectingItem.value) return;
  
  try {
    await rejectFormRef.value.validate();
    
    console.log(`Rejecting item ID: ${currentlyRejectingItem.value.pivot.id} for reason: ${rejectForm.value.reason}`);
    await new Promise(resolve => setTimeout(resolve, 500));

    const testIndex = selectedRequest.value.tests.findIndex(t => t.pivot.id === currentlyRejectingItem.value.pivot.id);
    if (testIndex !== -1) {
        selectedRequest.value.tests[testIndex].pivot.item_status = 'Rejected'; 
    }
    
    rejectDialogVisible.value = false;
    ElMessage.success(`Test "${currentlyRejectingItem.value.name}" marked as Rejected (simulated).`);
    currentlyRejectingItem.value = null;

  } catch (error) {
    if (error === false) {
        ElMessage.warning('Please provide a reason for rejection.');
    } else {
        console.error('Error rejecting request item:', error);
        ElMessage.error('Failed to reject request item.');
    }
  } finally {
  }
};

onMounted(() => {
  fetchRequests();
});
</script>

<style scoped>
.phlebotomy-container {
  display: grid;
  grid-template-columns: 450px 1fr;
  gap: 20px;
  height: calc(100vh - 160px);
}

.request-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  overflow: hidden;
  background-color: white;
  border-radius: 4px;
  padding: 16px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  border: 1px solid #dcdfe6;
}

.header {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.header h2 {
  margin: 0;
  font-size: 18px;
  color: #2c3e50;
}

.phlebotomy {
  width: 100%;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-header h2 {
  margin: 0;
  font-size: 24px;
  font-weight: 500;
}

.filters {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-bottom: 8px;
}

.filters .el-input {
  max-width: 200px;
}

.filters .el-select {
  width: 150px;
}

.request-cards {
  flex: 1;
  overflow-y: auto;
  padding-right: 4px;
}

.request-card {
  margin-bottom: 4px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.request-card:hover {
  transform: translateX(4px);
}

.request-card.selected {
  border-color: var(--el-color-primary);
  background-color: var(--el-color-primary-light-9);
}

.request-card.selected .sample-id {
  color: var(--el-color-primary);
}

.request-card.selected .patient-info h3 {
  color: var(--el-color-primary);
}

.request-card.selected .patient-info .patient-id {
  color: var(--el-color-primary);
}

.request-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.sample-id {
  font-weight: 600;
  color: var(--el-color-primary);
  font-size: 13px;
}

.status-tag {
  font-size: 12px;
  height: 22px;
  line-height: 20px;
  padding: 0 8px;
}

.patient-info h3 {
  margin: 0 0 2px 0;
  font-size: 13px;
  color: #2c3e50;
  font-weight: 500;
}

.patient-info p {
  margin: 0;
  color: #909399;
  font-size: 12px;
}

.request-footer {
  display: flex;
  justify-content: space-between;
  margin-top: 6px;
  color: #909399;
  font-size: 12px;
}

.request-details {
  background: white;
  border-radius: 4px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 15px;
  height: 100%;
  overflow-y: auto;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  border: 1px solid #dcdfe6;
}

.details-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 15px;
  border-bottom: 1px solid #e4e7ed;
}

.header-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-info h2 {
  margin: 0;
  font-size: 18px;
  color: #2c3e50;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.header-actions .el-button {
  padding: 8px 16px;
}

:deep(.el-descriptions) {
  padding: 16px;
  background-color: #f8f9fa;
  border-radius: 4px;
}

:deep(.el-descriptions__title) {
  font-size: 16px;
  color: #2c3e50;
}

:deep(.el-descriptions__label) {
  color: #606266;
}

.tests-section {
  margin-top: 4px;
}

.tests-section h3 {
  margin: 0 0 4px 0;
  font-size: 16px;
  color: #2c3e50;
}

:deep(.el-table) {
  border-radius: 4px;
}

:deep(.el-table th) {
  background-color: #f5f7fa;
  color: #2c3e50;
}

.notes-section {
  background-color: #f8f9fa;
  padding: 16px;
  border-radius: 4px;
}

.notes-section h3 {
  margin: 0 0 12px 0;
  font-size: 16px;
  color: #2c3e50;
}

.notes-section p {
  margin: 0;
  color: #606266;
  line-height: 1.5;
}

.pagination {
  display: flex;
  justify-content: flex-end;
  padding-top: 10px;
  border-top: 1px solid #e4e7ed;
}

:deep(.el-card__body) {
  padding: 12px !important;
}

:deep(.el-tag) {
  text-transform: capitalize;
}

:deep(.el-input__wrapper) {
  padding: 0 8px;
}

:deep(.el-input__inner) {
  height: 32px;
}

:deep(.el-select .el-input__wrapper) {
  padding: 0 8px;
}

:deep(.el-dialog) {
  border-radius: 8px;
}

:deep(.el-dialog__header) {
  margin: 0;
  padding: 20px;
  border-bottom: 1px solid #e4e7ed;
}

:deep(.el-dialog__body) {
  padding: 20px;
}

:deep(.el-dialog__footer) {
  padding: 16px 20px;
  border-top: 1px solid #e4e7ed;
}

.priority-badge {
  :deep(.el-badge__content) {
    font-size: 12px;
    height: 18px;
    line-height: 18px;
    padding: 0 6px;
    border: none;
  }
}

.patient-info .separator {
  margin: 0 6px;
  color: #c0c4cc;
}

.patient-info .patient-id {
  color: var(--el-color-primary);
}

.sample-type-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.sample-tube-icon {
  font-size: 16px;
  color: var(--el-color-primary);
}
</style> 