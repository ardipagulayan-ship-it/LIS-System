<template>
  <div class="hematology-container">
    <div class="page-header">
      <h2 class="page-title">Hematology Test Requests</h2>
      <div class="header-actions">
        <el-input
          v-model="searchQuery"
          placeholder="Search by ID or patient name"
          class="search-input"
          clearable
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <el-select v-model="statusFilter" placeholder="Filter by status" clearable>
          <el-option label="Pending" value="pending" />
          <el-option label="In Progress" value="in_progress" />
          <el-option label="Completed" value="completed" />
          <el-option label="Rejected" value="rejected" />
        <el-option label="For Rerun" value="for_rerun" />
        </el-select>
      </div>
    </div>

    <el-card class="request-list">
      <el-table
        :data="filteredRequests"
        style="width: 100%"
        v-loading="loading"
      >
        <el-table-column prop="id" label="Request ID" width="120" />
        <el-table-column prop="patientName" label="Patient Name" />
        <el-table-column prop="patientId" label="Patient ID" width="120" />
        <el-table-column prop="requestDate" label="Request Date" width="180" />
        <el-table-column prop="status" label="Status" width="120">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ formatStatus(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="200" fixed="right">
          <template #default="{ row }">
            <el-button-group>
              <el-button
                type="primary"
                size="small"
                @click="viewDetails(row)"
              >
                View
              </el-button>
              <el-button
                v-if="row.status === 'pending'"
                type="danger"
                size="small"
                @click="rejectRequest(row)"
              >
                Reject
              </el-button>
              <el-button
                v-if="row.status === 'completed' || row.status === 'for_rerun'"
                type="success"
                size="small"
                @click="printResults(row)"
              >
                Print
              </el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container">
        <el-pagination
          @size-change="handleSizeChange"
          @current-change="handlePageChange"
          :current-page="currentPage"
          :page-sizes="[10, 20, 50, 100]"
          :page-size="pageSize"
          layout="total, sizes, prev, pager, next, jumper"
          :total="total"
        />
      </div>
    </el-card>

    <hematology-modal
      :visible="detailsDialogVisible"
      :request-data="selectedRequest || {}"
      @update:visible="detailsDialogVisible = $event"
      @close="handleModalClose"
    />

    <!-- Reject Dialog -->
    <el-dialog
      v-model="rejectDialogVisible"
      title="Reject Test Request"
      width="30%"
    >
      <el-form
        ref="rejectForm"
        :model="rejectForm"
        :rules="rejectRules"
        label-width="100px"
      >
        <el-form-item label="Reason" prop="reason">
          <el-input
            v-model="rejectForm.reason"
            type="textarea"
            :rows="3"
            placeholder="Enter reason for rejection"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="rejectDialogVisible = false">Cancel</el-button>
          <el-button
            type="danger"
            @click="confirmReject"
            :loading="rejecting"
          >
            Reject
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import api from '@/utils/axios';
import { ElMessage } from 'element-plus';
import { Search } from '@element-plus/icons-vue';
import HematologyModal from '@/components/modals/result/HematologyModal.vue';

// State
const searchQuery = ref('');
const statusFilter = ref('');
const loading = ref(false);
const detailsDialogVisible = ref(false);
const rejectDialogVisible = ref(false);
const rejecting = ref(false);
const selectedRequest = ref(null);

const currentPage = ref(1);
const pageSize = ref(10);
const total = ref(0);

// Data - Replace with API calls
const requests = ref([]);

// Form
const rejectForm = ref({
  reason: ''
});

const rejectRules = {
  reason: [
    { required: true, message: 'Please enter reason for rejection', trigger: 'blur' }
  ]
};

// Computed
const filteredRequests = computed(() => {
  return requests.value;
});

watch([searchQuery, statusFilter], () => {
  currentPage.value = 1; // Reset to first page on filter/search change
  fetchRequests();
});

// Methods
const getStatusType = (status) => {
  const types = {
    pending: 'warning',
    in_progress: 'primary',
    completed: 'success',
    rejected: 'danger',
    for_rerun: 'info'
  };
  return types[status] || 'info';
};

const formatStatus = (status) => {
  const statusMap = {
    pending: 'Pending',
    in_progress: 'In Progress',
    completed: 'Completed',
    rejected: 'Rejected',
    for_rerun: 'For Rerun'
  };
  return statusMap[status] || status;
};

const fetchRequests = async () => {
  loading.value = true;
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
      search: searchQuery.value,
      status: statusFilter.value
    };
    const response = await api.get('/laboratory-requests', { params });
    requests.value = response.data.data;
    total.value = response.data.total;
  } catch (error) {
    console.error('Error fetching requests:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchRequests();
});

const handlePageChange = (page) => {
  currentPage.value = page;
  fetchRequests();
};

const handleSizeChange = (size) => {
  pageSize.value = size;
  fetchRequests();
};

const viewDetails = (request) => {
  selectedRequest.value = request;
  detailsDialogVisible.value = true;
};

const rejectRequest = (request) => {
  selectedRequest.value = request;
  rejectDialogVisible.value = true;
};

const confirmReject = async () => {
  if (!selectedRequest.value) return;
  
  try {
    rejecting.value = true;
    // TODO: Call API to reject request
    // await new Promise(resolve => setTimeout(resolve, 1000)); // Simulate API call
    await api.put(`/laboratory-requests/${selectedRequest.value.id}/reject`, { reason: rejectForm.value.reason });
    ElMessage.success('Request rejected successfully!');
    fetchRequests(); // Refresh the list
    
    // const index = requests.value.findIndex(r => r.id === selectedRequest.value.id);
    // if (index !== -1) {
    //   requests.value[index].status = 'rejected';
    // }
    
    rejectDialogVisible.value = false;
    rejectForm.value.reason = '';
  } catch (error) {
    console.error('Error rejecting request:', error);
  } finally {
    rejecting.value = false;
  }
};

const printResults = (request) => {
  // TODO: Implement print functionality
  console.log('Printing results for request:', request);
};

const handleModalClose = () => {
  selectedRequest.value = null;
  detailsDialogVisible.value = false;
};
</script>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-title {
  margin: 0;
  color: var(--color-primary);
}

.header-actions {
  display: flex;
  gap: 10px;
}

.search-input {
  width: 300px;
}

.request-list {
  margin-top: 20px;
}

.details-content {
  padding: 20px;
}

.test-list {
  margin-top: 20px;
}

.test-list h3 {
  margin-bottom: 15px;
  color: var(--color-primary);
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style>