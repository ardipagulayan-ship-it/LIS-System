<template>
  <div class="microbiology-module">
    <div class="page-header">
      <h2 class="page-title">Microbiology</h2>
      <div class="actions">
        <el-input
          v-model="searchQuery"
          placeholder="Search requests..."
          clearable
          style="width: 250px"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <el-select v-model="filterStatus" placeholder="Status" clearable style="width: 150px">
          <el-option label="Pending" value="Pending" />
          <el-option label="In Progress" value="In Progress" />
          <el-option label="Completed" value="Completed" />
        </el-select>
        <el-date-picker
          v-model="dateRange"
          type="daterange"
          range-separator="To"
          start-placeholder="Start date"
          end-placeholder="End date"
          style="width: 350px"
        />
        <el-button type="primary" @click="handleSearch">
          <el-icon><Search /></el-icon>
          Search
        </el-button>
      </div>
    </div>

    <el-card class="main-content">
      <el-table 
        :data="filteredRequests" 
        style="width: 100%" 
        row-key="id" 
        border
        v-loading="loading"
      >
        <el-table-column prop="status" label="Status" width="140">
          <template #default="{ row }">
            <template v-if="hasBlockingCdsViolations(row)">
              <el-tag type="danger" effect="dark" size="small">CDS BLOCK</el-tag>
            </template>
            <template v-else>
              <el-tag :type="getStatusType(row.status)">{{ row.status }}</el-tag>
            </template>
          </template>
        </el-table-column>
        <el-table-column prop="id" label="Request ID" width="160">
          <template #default="{ row }">
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
              <span class="specimen-id-text">{{ row.id }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="patientName" label="Patient Name" min-width="180" />
        <el-table-column prop="patientId" label="Patient ID" width="120" />
        <el-table-column prop="requestDate" label="Request Date" width="180">
          <template #default="{ row }">
            {{ formatDate(row.requestDate) }}
          </template>
        </el-table-column>
        <el-table-column prop="testName" label="Test" min-width="150" />
        <el-table-column prop="sampleSource" label="Sample Source" width="150" />
        <el-table-column label="Actions" width="200" fixed="right">
          <template #default="{ row }">
            <el-button-group>
              <el-button 
                type="primary" 
                size="small" 
                @click="handleViewResult(row)"
                :disabled="row.status === 'Pending'"
              >
                <el-icon><View /></el-icon>
              </el-button>
              <el-button 
                type="success" 
                size="small" 
                @click="handleUpdateStatus(row)"
              >
                <el-icon><Edit /></el-icon>
              </el-button>
              <el-button 
                type="warning" 
                size="small" 
                @click="handlePrintResult(row)"
                :disabled="row.status !== 'Completed'"
              >
                <el-icon><Printer /></el-icon>
              </el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          :total="totalItems"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- Microbiology Modal for viewing results -->
    <MicrobiologyModal
      v-model:visible="resultModalVisible"
      :request-data="selectedRequest"
      @close="handleModalClose"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { 
  Search, 
  View, 
  Edit, 
  Printer,
  WarningFilled
} from '@element-plus/icons-vue';
import MicrobiologyModal from '@/components/modals/result/MicrobiologyModal.vue';
import { format } from 'date-fns';

// Data and state
const loading = ref(false);
const searchQuery = ref('');
const filterStatus = ref('');
const dateRange = ref([]);
const currentPage = ref(1);
const pageSize = ref(10);
const totalItems = ref(0);
const resultModalVisible = ref(false);
const selectedRequest = ref(null);
const cdsViolations = ref({}); // { requestId: { triggered_rules: [], has_blocking_rules: false } }

// Dummy data for microbiology test requests
const testRequests = ref([
  {
    id: 'REQ-2025-0120',
    patientName: 'John Smith',
    patientId: 'P-2025-001',
    requestDate: new Date('2025-01-18'),
    testName: 'Culture & Sensitivity',
    sampleSource: 'Urine',
    status: 'Completed'
  },
  {
    id: 'REQ-2025-0121',
    patientName: 'Emily Johnson',
    patientId: 'P-2025-015',
    requestDate: new Date('2025-01-18'),
    testName: 'Gram Stain',
    sampleSource: 'Sputum',
    status: 'In Progress'
  },
  {
    id: 'REQ-2025-0123',
    patientName: 'Robert Davis',
    patientId: 'P-2025-022',
    requestDate: new Date('2025-01-19'),
    testName: 'Culture & Sensitivity',
    sampleSource: 'Blood',
    status: 'Pending'
  },
  {
    id: 'REQ-2025-0125',
    patientName: 'Maria Garcia',
    patientId: 'P-2025-034',
    requestDate: new Date('2025-01-19'),
    testName: 'AFB Stain',
    sampleSource: 'Sputum',
    status: 'Completed'
  },
  {
    id: 'REQ-2025-0128',
    patientName: 'David Brown',
    patientId: 'P-2025-045',
    requestDate: new Date('2025-01-20'),
    testName: 'Culture & Sensitivity',
    sampleSource: 'Wound Swab',
    status: 'In Progress'
  }
]);

// Filtered and paginated requests
const filteredRequests = computed(() => {
  let filtered = [...testRequests.value];
  
  // Apply search query filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(req => 
      req.id.toLowerCase().includes(query) ||
      req.patientName.toLowerCase().includes(query) ||
      req.patientId.toLowerCase().includes(query) ||
      req.testName.toLowerCase().includes(query)
    );
  }
  
  // Apply status filter
  if (filterStatus.value) {
    filtered = filtered.filter(req => req.status === filterStatus.value);
  }
  
  // Apply date range filter
  if (dateRange.value && dateRange.value.length === 2 && dateRange.value[0] && dateRange.value[1]) {
    const startDate = new Date(dateRange.value[0]);
    startDate.setHours(0, 0, 0, 0);
    
    const endDate = new Date(dateRange.value[1]);
    endDate.setHours(23, 59, 59, 999);
    
    filtered = filtered.filter(req => {
      const requestDate = new Date(req.requestDate);
      return requestDate >= startDate && requestDate <= endDate;
    });
  }
  
  totalItems.value = filtered.length;
  
  // Apply pagination
  const start = (currentPage.value - 1) * pageSize.value;
  const end = start + pageSize.value;
  return filtered.slice(start, end);
});

// Methods
const handleSearch = () => {
  currentPage.value = 1; // Reset to first page on new search
};

const handleViewResult = (row) => {
  // Simulate fetching full request data including details for the modal
  selectedRequest.value = {
    ...row,
    patientDetails: {
      sampleId: 'S' + row.id.split('-')[2],
      patientId: row.patientId,
      patientName: row.patientName,
      sex: 'male', // Example, fetch real data
      dateOfBirth: new Date('1990-01-01'), // Example, fetch real data
      address: '123 Sample St', // Example, fetch real data
      contactNo: '555-1234', // Example, fetch real data
      emailAddress: 'patient@example.com' // Example, fetch real data
    },
    additionalDetails: {
      dateReceived: row.requestDate,
      timeReceived: row.requestDate,
      dateReleased: row.status === 'Completed' ? new Date() : null,
      request: row.testName.toLowerCase().replace(/\s+/g, '_'),
      sampleSource: row.sampleSource,
      referredBy: ['dr_smith'], // Example, fetch real data
      physicians: ['dr_johnson'] // Example, fetch real data
    },
    testResults: null, // Initialize, modal will create default if null
    signatories: { // Example, fetch real data
        performedBy: '',
        verifiedBy: 'ana_cruz',
        pathologist: 'juan_cruz'
    },
    remarks: '', // Example, fetch real data
    comments: '' // Example, fetch real data
  };
  resultModalVisible.value = true;
};

const handleUpdateStatus = (row) => {
  // Logic to update status
  console.log('Update status for:', row.id);
};

const handlePrintResult = (row) => {
  // Logic to print result
  console.log('Print result for:', row.id);
};

const handleModalClose = () => {
  selectedRequest.value = null;
};

// CDS Helper functions
const hasCdsViolations = (row) => {
  return cdsViolations.value[row.id]?.triggered_rules?.length > 0;
};

const hasBlockingCdsViolations = (row) => {
  return cdsViolations.value[row.id]?.has_blocking_rules === true;
};

// Fetch CDS violations (Mock for now since it's dummy data)
const fetchCdsViolations = async (requestIds) => {
  if (!requestIds || requestIds.length === 0) return;
  // In a real scenario, this would call api.post('/cds/violations/batch')
  console.log('Fetching CDS violations for:', requestIds);
};

const handleSizeChange = (val) => {
  pageSize.value = val;
  currentPage.value = 1; // Reset to first page when changing page size
};

const handleCurrentChange = (val) => {
  currentPage.value = val;
};

onMounted(() => {
  // Simulate loading data
  loading.value = true;
  setTimeout(() => {
    loading.value = false;
  }, 500);

  // Fetch CDS violations for dummy data
  if (testRequests.value.length > 0) {
    fetchCdsViolations(testRequests.value.map(r => r.id));
  }
});

// Utility functions
const formatDate = (date) => {
  if (!date) return '';
  return format(new Date(date), 'MMM dd, yyyy');
};

const getStatusType = (status) => {
  const statusMap = {
    'Pending': 'warning',
    'In Progress': 'primary', 
    'Completed': 'success'
  };
  return statusMap[status] || 'info';
};
</script>

<style scoped>
.microbiology-module {
  padding: 0;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-title {
  color: var(--color-primary);
  font-size: 24px;
  font-weight: 600;
  margin: 0;
}

.actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.main-content {
  margin-bottom: 20px;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

/* CDS Rule Styles */
.specimen-id-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.cds-list-tag {
  font-weight: bold;
  cursor: help;
}

.cds-tooltip-content {
  padding: 5px;
  max-width: 300px;
}

.tooltip-header {
  font-weight: bold;
  margin-bottom: 5px;
  color: #ff4d4f;
  border-bottom: 1px solid #555;
  padding-bottom: 3px;
}

.tooltip-item {
  margin-bottom: 3px;
  font-size: 12px;
  line-height: 1.4;
}

.specimen-id-text {
  font-family: monospace;
  font-weight: bold;
}
</style> 