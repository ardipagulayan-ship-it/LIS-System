<template>
  <div class="chemistry-container">
    <div class="header">
      <h2>Chemistry Laboratory</h2>
      <div class="actions">
        <el-button type="primary" @click="refreshList">
          <el-icon><Refresh /></el-icon>
          Refresh
        </el-button>
      </div>
    </div>

    <div class="filters">
      <el-form :inline="true" class="filter-form">
        <el-form-item label="Date Range:">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="To"
            start-placeholder="Start date"
            end-placeholder="End date"
            size="small"
          />
        </el-form-item>
        <el-form-item label="Status:">
          <el-select v-model="statusFilter" size="small">
            <el-option label="All" value="" />
            <el-option label="Pending" value="pending" />
            <el-option label="In Progress" value="in_progress" />
            <el-option label="Completed" value="completed" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="applyFilters" size="small">Apply</el-button>
          <el-button @click="resetFilters" size="small">Reset</el-button>
        </el-form-item>
      </el-form>
    </div>

    <div class="test-requests">
      <el-table
        :data="filteredRequests"
        style="width: 100%"
        border
        highlight-current-row
        @row-click="handleRowClick"
        size="small"
        v-loading="loading"
      >
        <el-table-column prop="sampleId" label="Sample ID" width="100" />
        <el-table-column prop="patientId" label="Patient ID" width="120" />
        <el-table-column prop="patientName" label="Patient Name" min-width="180" />
        <el-table-column prop="testName" label="Test" width="120" />
        <el-table-column prop="dateReceived" label="Date Received" width="150">
          <template #default="scope">
            {{ formatDateTime(scope.row.dateReceived) }}
          </template>
        </el-table-column>
        <el-table-column prop="status" label="Status" width="120">
          <template #default="scope">
            <el-tag :type="getStatusType(scope.row.status)" size="small">
              {{ scope.row.status }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="180">
          <template #default="scope">
            <el-button-group>
              <el-button 
                type="primary" 
                size="small" 
                @click.stop="viewResults(scope.row)"
                :disabled="scope.row.status === 'Pending'"
              >
                <el-icon><View /></el-icon>
              </el-button>
              <el-button 
                type="warning" 
                size="small" 
                @click.stop="updateStatus(scope.row)"
                :disabled="scope.row.status === 'Completed'"
              >
                <el-icon><Edit /></el-icon>
              </el-button>
              <el-button 
                type="success" 
                size="small" 
                @click.stop="printResults(scope.row)"
                :disabled="scope.row.status !== 'Completed'"
              >
                <el-icon><Printer /></el-icon>
              </el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          layout="total, sizes, prev, pager, next"
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :total="totalRequests"
          :page-sizes="[10, 20, 50, 100]"
        />
      </div>
    </div>

    <!-- Chemistry Modal -->
    <ChemistryModal 
      v-model:visible="showModal"
      :request-data="selectedRequest"
      @close="closeModal"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import ChemistryModal from '@/components/modals/result/ChemistryModal.vue';
import { View, Edit, Printer, Refresh } from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox } from 'element-plus';

// Table data
const loading = ref(false);
const testRequests = ref([
  {
    id: 1,
    sampleId: 'C24050001',
    patientId: 'P24010001',
    patientName: 'John Smith',
    testName: 'Lipid Profile',
    dateReceived: new Date('2024-03-15T09:30:00'),
    status: 'Pending'
  },
  {
    id: 2,
    sampleId: 'C24050002',
    patientId: 'P24010015',
    patientName: 'Maria Garcia',
    testName: 'Liver Function',
    dateReceived: new Date('2024-03-15T10:45:00'),
    status: 'In Progress'
  },
  {
    id: 3,
    sampleId: 'C24050003',
    patientId: 'P24010022',
    patientName: 'Robert Johnson',
    testName: 'Glucose',
    dateReceived: new Date('2024-03-15T11:20:00'),
    status: 'Completed'
  },
  {
    id: 4,
    sampleId: 'C24050004',
    patientId: 'P24010030',
    patientName: 'Sara Williams',
    testName: 'Kidney Function',
    dateReceived: new Date('2024-03-15T13:15:00'),
    status: 'Pending'
  },
  {
    id: 5,
    sampleId: 'C24050005',
    patientId: 'P24010042',
    patientName: 'James Brown',
    testName: 'Electrolytes',
    dateReceived: new Date('2024-03-15T14:30:00'),
    status: 'In Progress'
  }
]);

// Filters
const dateRange = ref([]);
const statusFilter = ref('');
const currentPage = ref(1);
const pageSize = ref(10);
const totalRequests = ref(100);

// Modal control
const showModal = ref(false);
const selectedRequest = ref(null);

// Computed filtered requests
const filteredRequests = computed(() => {
  let filtered = [...testRequests.value];
  
  if (statusFilter.value) {
    filtered = filtered.filter(req => req.status.toLowerCase() === statusFilter.value);
  }
  
  if (dateRange.value && dateRange.value.length === 2) {
    const startDate = new Date(dateRange.value[0]);
    const endDate = new Date(dateRange.value[1]);
    endDate.setHours(23, 59, 59, 999);
    
    filtered = filtered.filter(req => {
      const requestDate = new Date(req.dateReceived);
      return requestDate >= startDate && requestDate <= endDate;
    });
  }
  
  return filtered;
});

// Format date function
const formatDateTime = (date) => {
  if (!date) return '';
  return format(new Date(date), 'yyyy-MM-dd HH:mm');
};

// Get status type for tag coloring
const getStatusType = (status) => {
  const statusMap = {
    'Pending': 'info',
    'In Progress': 'warning',
    'Completed': 'success'
  };
  return statusMap[status] || 'info';
};

// Event handlers
const handleRowClick = (row) => {
  selectedRequest.value = row;
};

const viewResults = (row) => {
  selectedRequest.value = row;
  showModal.value = true;
};

const updateStatus = (row) => {
  ElMessageBox.prompt('Enter new status (Pending, In Progress, Completed)', 'Update Status', {
    confirmButtonText: 'Update',
    cancelButtonText: 'Cancel',
    inputValue: row.status
  }).then(({ value }) => {
    if (['Pending', 'In Progress', 'Completed'].includes(value)) {
      // Update the row's status
      const index = testRequests.value.findIndex(item => item.id === row.id);
      if (index !== -1) {
        testRequests.value[index].status = value;
      }
      ElMessage({
        type: 'success',
        message: `Status updated to ${value}`
      });
    } else {
      ElMessage({
        type: 'error',
        message: 'Invalid status. Please use Pending, In Progress, or Completed'
      });
    }
  }).catch(() => {
    // Cancelled
  });
};

const printResults = (row) => {
  ElMessage({
    type: 'success',
    message: `Printing results for ${row.patientName}`
  });
};

const refreshList = () => {
  loading.value = true;
  setTimeout(() => {
    loading.value = false;
    ElMessage({
      type: 'success',
      message: 'Test requests refreshed'
    });
  }, 1000);
};

const applyFilters = () => {
  currentPage.value = 1;
  ElMessage({
    type: 'success',
    message: 'Filters applied'
  });
};

const resetFilters = () => {
  dateRange.value = [];
  statusFilter.value = '';
  currentPage.value = 1;
  ElMessage({
    type: 'info',
    message: 'Filters reset'
  });
};

const closeModal = () => {
  showModal.value = false;
  selectedRequest.value = null;
};

onMounted(() => {
  // Fetch initial data
  refreshList();
});
</script>

<style scoped>
.chemistry-container {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header h2 {
  margin: 0;
  color: #303133;
}

.filters {
  margin-bottom: 20px;
  padding: 15px;
  background-color: #f5f7fa;
  border-radius: 4px;
}

.test-requests {
  background-color: #fff;
  border-radius: 4px;
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

:deep(.el-table__row) {
  cursor: pointer;
}

:deep(.el-tag--info) {
  background-color: #f4f4f5;
  color: #909399;
  border-color: #e9e9eb;
}

:deep(.el-tag--warning) {
  background-color: #fdf6ec;
  color: #e6a23c;
  border-color: #faecd8;
}

:deep(.el-tag--success) {
  background-color: #f0f9eb;
  color: #67c23a;
  border-color: #e1f3d8;
}
</style> 