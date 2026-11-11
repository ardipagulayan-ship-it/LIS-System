<template>
  <div class="hematology-results">
    <div class="page-header">
      <h2 class="page-title">Chemistry Test Requests</h2>
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
        <el-select v-model="patientTypeFilter" placeholder="Filter by patient type" clearable>
          <el-option label="Inpatient" value="inpatient" />
          <el-option label="Outpatient" value="outpatient" />
          <el-option label="Emergency" value="emergency" />
          <el-option label="Labor" value="labor" />
          <el-option label="OPD" value="opd" />
        </el-select>
      </div>
    </div>

    <el-card class="content-card">
      <el-table :data="requests" v-loading="loading" style="width: 100%" stripe border>
        <el-table-column prop="hematology_status" label="Status" width="150" align="center">
          <template #default="{ row }">
            <template v-if="hasBlockingCdsViolations(row)">
              <el-tag type="danger" effect="dark" size="small">CDS BLOCK</el-tag>
            </template>
            <template v-else>
              <el-tag :type="getStatusType(row.hematology_status)" size="small">
                {{ row.hematology_status }}
              </el-tag>
            </template>
            <div class="progress-detail">
                {{ getCompletionDetail(row) }}
              </div>
          </template>
        </el-table-column>
        <el-table-column label="Specimen ID" width="160">
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
              <span class="specimen-id-text">{{ row.sample_id }}</span>
              <el-badge :value = row.priority_level :type="getPriorityType(row.priority_level)" size="small" style="margin-left: 5px;" />
            </div>
          </template>
        </el-table-column>
        <el-table-column label="Patient Name" min-width="150" :formatter="formatPatientName" />
        <el-table-column prop="patient.date_of_birth" label="DoB" width="120" :formatter="formatDob" />
        <el-table-column label="Age" prop="patient_age" width="150">
          <template #default="scope">
            <span>{{ calculateAge(scope.row.patient.date_of_birth) }} | {{ scope.row.patient.gender }}</span>
          </template>
        </el-table-column>
        <el-table-column label="Location" width="250">
          <template #default="{ row }">
            <span>{{ row.ward?.name || '-' }} | {{ row.room?.name || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="request_date" label="Request Date" width="160" :formatter="formatDateTime" />
        <el-table-column label="Hematology Tests" min-width="150">
          <template #default="{ row }">
            <div v-if="row.tests && row.tests.length">
              <div v-for="test in row.tests" :key="test.id" class="test-item">
                <span>{{ test.name }}</span>
                <el-tag
                  v-if="['Pending', 'Collected', 'Send-out', 'Parked', 'Accepted'].includes(test.pivot?.item_status)"
                  :type="getPivotStatusType(test.pivot?.item_status)"
                  size="small"
                  class="status-badge"
                >
                  {{ test.pivot?.item_status }}
                </el-tag>
              </div>
            </div>
            <span v-else>{{ row.hematology_test_list || 'No Hematology Tests' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="160" align="center" fixed="right">
          <template #default="{ row }">
            <el-button-group>
              <el-button type="primary" size="small" :icon="View" @click="viewResults(row.id)">View</el-button>
              <el-button type="success" size="small" :icon="Printer" @click="printReport(row.id)" :disabled="row.hematology_status !== 'Validated' && row.hematology_status !== 'Printed'">Print</el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container">
        <el-pagination
          v-if="total > 0"
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[15, 30, 50, 100]"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <HematologyModal
      v-if="isViewingDetails" 
      :visible="isViewingDetails"
      :request-details="selectedRequestDetails"
      :verifiers="verifierList"
      :performers="performerList"
      :pathologists="pathologistList"
      :loading="detailsLoading"
      @close="closeDetailsView"
      @save="handleSaveResults" 
      @print="handlePrintResults"
      @validate="handleValidateResults"
    />
  </div>

</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { ElTable, ElTableColumn, ElButton, ElCard, ElPagination, ElTag, ElMessage, ElInput, ElSelect, ElOption, ElIcon, ElProgress } from 'element-plus';
import { View, Printer, Search, WarningFilled } from '@element-plus/icons-vue';
import { format, parseISO, differenceInYears, differenceInMonths, differenceInDays } from 'date-fns';
import api from '@/utils/axios'; // Assuming your axios instance is here
import { useRouter } from 'vue-router'; // Uncomment if using router for navigation
import HematologyModal from '@/components/modals/result/ChemistryModal.vue';
const router = useRouter(); // Uncomment if using router

// --- State ---
const requests = ref([]);
const loading = ref(false);
const currentPage = ref(1);
const pageSize = ref(15);
const total = ref(0);
const detailsDialogVisible = ref(false);
const selectedRequest = ref([]);
const searchQuery = ref(''); // Added searchQuery state
const patientTypeFilter = ref(''); // Changed from statusFilter to patientTypeFilter
const cdsViolations = ref({}); // { requestId: { triggered_rules: [], has_blocking_rules: false } }

// --- State for Viewing Details ---
const isViewingDetails = ref(false); // Flag to control detail view/modal visibility
const selectedRequestDetails = ref(null); // Holds the full request details
const verifierList = ref([]);
const performerList = ref([]);
const pathologistList = ref([]);
const detailsLoading = ref(false);

// --- Helper Functions ---
// Calculate age from date of birth with appropriate unit (years, months, or days)
const calculateAge = (dob) => {
  if (!dob) return 'N/A';
  
  try {
    const birthDate = parseISO(dob);
    const today = new Date();
    
    // Calculate differences
    const years = differenceInYears(today, birthDate);
    const months = differenceInMonths(today, birthDate);
    const days = differenceInDays(today, birthDate);
    
    // Return appropriate unit based on age
    if (years >= 1) {
      return `${years} ${years === 1 ? 'year' : 'years'}`;
    } else if (months >= 1) {
      return `${months} ${months === 1 ? 'month' : 'months'}`;
    } else {
      return `${days} ${days === 1 ? 'day' : 'days'}`;
    }
  } catch (e) {
    console.error('Error calculating age:', e);
    return 'Invalid Date';
  }
};

// --- Fetch Data ---
//API: /results/hematology
const fetchHematologyRequests = async () => {
  loading.value = true;
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
      search: searchQuery.value,
      patient_type: patientTypeFilter.value // Changed from status to patient_type
    };
    const response = await api.get('/results/chemistry', { params });
    requests.value = response.data.data;
    total.value = response.data.total;
    
    // Fetch CDS violations for these requests
    if (requests.value.length > 0) {
      await fetchCdsViolations(requests.value.map(r => r.id));
    }
    console.log(requests.value);
  } catch (error) {
    console.error("Error fetching Chemistry requests:", error);
    ElMessage.error('Failed to fetch Chemistry requests.');
    requests.value = [];
    total.value = 0;
  } finally {
    loading.value = false;
  }
};

// Fetch CDS violations for a list of request IDs
const fetchCdsViolations = async (requestIds) => {
  if (!requestIds || requestIds.length === 0) return;
  
  try {
    const response = await api.post('/cds/violations/batch', {
      request_ids: requestIds
    });
    
    // Update cdsViolations state
    const newViolations = { ...cdsViolations.value };
    Object.keys(response.data).forEach(requestId => {
      newViolations[requestId] = response.data[requestId];
    });
    cdsViolations.value = newViolations;
  } catch (error) {
    console.error('Error fetching CDS violations:', error);
  }
};

// CDS Helper functions
const hasCdsViolations = (row) => {
  return cdsViolations.value[row.id]?.triggered_rules?.length > 0;
};

const hasBlockingCdsViolations = (row) => {
  return cdsViolations.value[row.id]?.has_blocking_rules === true;
};

// --- Lifecycle ---
onMounted(() => {
  fetchHematologyRequests();
});

// --- Formatters ---
const formatPatientName = (row) => {
  if (!row.patient) return 'N/A';
  return `${row.patient.last_name || ''}, ${row.patient.first_name || ''} ${row.patient.middle_name || ''}`.trim();
};

const formatDob = (row, column, cellValue) => {
  if (!cellValue) return 'N/A';
  try {
    return format(parseISO(cellValue), 'yyyy-MM-dd');
  } catch (e) {
    return 'Invalid Date';
  }
};

const formatDateTime = (row, column, cellValue) => {
  if (!cellValue) return 'N/A';
  try {
    return format(parseISO(cellValue), 'MM/dd/yyyy p');
  } catch (e) {
    return 'Invalid Date';
  }
};

// --- Status/Priority Styling ---
const getStatusType = (status) => {
    switch (status) {
        case 'Pending Sample': return 'info';
        case 'Checked-in': return 'warning';
        case 'Validated': return 'primary';
        case 'Printed': return 'success';
        default: return 'default';
    }
};

const getPriorityType = (priority) => {
    switch (priority) {
        case 'STAT': return 'danger';
        case 'Urgent': return 'warning';
        case 'Routine': return 'primary';
        default: return 'info';
    }
};

const getPivotStatusType = (status) => {
  switch (status) {
    case 'Pending': return 'warning';
    case 'Collected': return 'primary';
    case 'Send-out': return 'primary';
    case 'Parked': return 'danger';
    case 'Accepted': return 'success';
    default: return 'default';
  }
};

// Calculate the completion percentage for a test request
const calculateCompletionPercentage = (row) => {
  // Make sure there are tests to calculate from
  if (!row.tests || row.tests.length === 0) return 0;
  
  let totalParameters = 0;
  let completedParameters = 0;
  
  // Examine each test in the request
  row.tests.forEach(test => {
    // If we have parameter counts available, use them for accurate calculation
    if (test.parameter_count) {
      totalParameters += test.parameter_count;
      
      // If we have completed_parameter_count available, use it
      if (test.completed_parameter_count) {
        completedParameters += test.completed_parameter_count;
      } 
      // Otherwise, estimate based on test status
      else if (test.pivot && ['Processing Results', 'Result Validated', 'Result Printed'].includes(test.pivot.item_status)) {
        // Assume all parameters are completed if the test status indicates completion
        completedParameters += test.parameter_count;
      }
    } 
    // If parameter counts are not available, fallback to test-level tracking
    else {
      // Count each test as a single unit
      totalParameters++;
      
      // Consider the test completed if its status indicates completion
      if (test.pivot && ['Processing Results', 'Result Validated', 'Result Printed'].includes(test.pivot.item_status)) {
        completedParameters++;
      }
    }
  });
  
  // If we have no parameters to track, return 0
  if (totalParameters === 0) return 0;
  
  // Calculate and return the percentage
  return Math.round((completedParameters / totalParameters) * 100);
};

// Determine the progress status based on completion percentage
const getProgressStatus = (row) => {
  const percentage = calculateCompletionPercentage(row);
  
  if (percentage === 100) return 'success';
  if (percentage > 50) return 'warning';
  if (percentage > 0) return 'exception';
  return '';
};

// Format the progress text
const formatProgress = (percentage) => {
  //if (percentage === 100) return 'Completed';
  //if (percentage === 0) return 'Not Started';
  //return `${percentage}% Complete`;
  return `${getCompletionDetail}`;
};

// Get detailed parameter completion information
const getCompletionDetail = (row) => {
  if (!row.tests || row.tests.length === 0) return '';
  
  let totalParams = 0;
  let completedParams = 0;
  
  // Count total parameters and completed parameters
  row.tests.forEach(test => {
    if (test.parameter_count) {
      totalParams += test.parameter_count;
      completedParams += test.completed_parameter_count || 0;
    } else {
      totalParams++;
      if (test.pivot && ['Processing Results', 'Result Validated', 'Result Printed'].includes(test.pivot.item_status)) {
        completedParams++;
      }
    }
  });
  
  return `${completedParams}/${totalParams} parameters complete`;
};

// --- Actions ---
const viewResults = async (requestId) => {
  console.log(`View results for request ID: ${requestId}`);

  if (!requestId) {
    ElMessage.warning('Invalid Request ID.');
    return;
  }

  detailsLoading.value = true;
  selectedRequestDetails.value = null; // Clear previous details
  try {
    const response = await api.get(`/results/chemistry/${requestId}`);
    selectedRequestDetails.value = response.data.request_details;
    verifierList.value = response.data.user_lists?.verifiers || [];
    performerList.value = response.data.user_lists?.performers || [];
    pathologistList.value = response.data.user_lists?.pathologists || [];

    // --- Show the results modal ---
    isViewingDetails.value = true; 
    console.log("Fetched Details:", selectedRequestDetails.value);
    ElMessage.success('Request details loaded.');

  } catch (error) {
    console.error(`Error fetching details for request ${requestId}:`, error);
    ElMessage.error(error.response?.data?.message || 'Failed to load request details.');
    isViewingDetails.value = false; // Keep detail view closed on error
  } finally {
    detailsLoading.value = false;
  }
};

const printReport = async (requestId) => {
  console.log(`Print report for request ID: ${requestId}`);
  
  if (!requestId) {
    ElMessage.warning('Invalid Request ID.');
    return;
  }
  
  try {
    loading.value = true;
    
    // Get the current request to check its status
    const request = requests.value.find(req => req.id === requestId);
    
    // Update status to Printed if current status is Validated
    if (request && request.hematology_status === 'Validated') {
      // Call API to update status to Printed
      await api.put(`/laboratory-requests/${requestId}/status`, {
        status: 'Result Printed'
      });
      
      ElMessage.success('Status updated to Printed');
      
      // Refresh the data to show the updated status
      await fetchHematologyRequests();
    }
    
    // Now open the print view regardless of whether we updated the status
    openPrintView(requestId);
    
  } catch (error) {
    console.error('Error printing report:', error);
    ElMessage.error(`Failed to print report: ${error.response?.data?.message || error.message}`);
  } finally {
    loading.value = false;
  }
};

// Helper function to open print view
const openPrintView = async (requestId) => {
  try {
    // Get the detailed data for printing
    const response = await api.get(`/results/chemistry/${requestId}`);
    const requestDetails = response.data.request_details;
    
    // Create a print window with the report content
    const printWindow = window.open('', '_blank');
    if (!printWindow) {
      ElMessage.warning('Unable to open print window. Please check your browser settings.');
      return;
    }
    
    // Format the print content
    printWindow.document.write(`
      <html>
      <head>
        <title>Hematology Test Results</title>
        <style>
          body { font-family: Arial, sans-serif; margin: 20px; }
          .header { text-align: center; margin-bottom: 20px; }
          .logo { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
          .patient-info { margin-bottom: 20px; border: 1px solid var(--color-border-primary, #ccc); padding: 10px; }
          .info-row { display: flex; margin-bottom: 5px; }
          .info-label { font-weight: bold; width: 150px; }
          table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
          th, td { border: 1px solid var(--color-border-secondary, #ddd); padding: 8px; text-align: left; }
          th { background-color: var(--color-bg-secondary, #f2f2f2); }
          .footer { margin-top: 30px; }
          .signatures { display: flex; justify-content: space-between; margin-top: 50px; }
          .signature-line { border-top: 1px solid #000; width: 200px; text-align: center; padding-top: 5px; }
          .flag-abnormal { color: orange; font-weight: bold; }
          .flag-critical { color: red; font-weight: bold; }
        </style>
      </head>
      <body>
        <div class="header">
          <div class="logo">HOSPITAL LABORATORY SYSTEM</div>
          <div>Hematology Test Results</div>
        </div>

        <div class="patient-info">
          <div class="info-row">
            <div class="info-label">Patient ID:</div>
            <div>${requestDetails.patient?.id || 'N/A'}</div>
          </div>
          <div class="info-row">
            <div class="info-label">Patient Name:</div>
            <div>${requestDetails.patient?.last_name || ''}, ${requestDetails.patient?.first_name || ''} ${requestDetails.patient?.middle_name || ''}</div>
          </div>
          <div class="info-row">
            <div class="info-label">Sample ID:</div>
            <div>${requestDetails.sample_id || 'N/A'}</div>
          </div>
          <div class="info-row">
            <div class="info-label">Age / Gender:</div>
            <div>${calculateAge(requestDetails.patient?.date_of_birth)} / ${requestDetails.patient?.gender || 'N/A'}</div>
          </div>
          <div class="info-row">
            <div class="info-label">Request Date:</div>
            <div>${requestDetails.request_date ? format(parseISO(requestDetails.request_date), 'yyyy-MM-dd HH:mm') : 'N/A'}</div>
          </div>
          <div class="info-row">
            <div class="info-label">Ordering Physician:</div>
            <div>${requestDetails.doctor?.name || 'N/A'}</div>
          </div>
        </div>

        <table>
          <thead>
            <tr>
              <th>Test/Parameter</th>
              <th>Result</th>
              <th>Unit</th>
              <th>Reference Range</th>
              <th>Flag</th>
            </tr>
          </thead>
          <tbody>
            ${generateTableRows(requestDetails)}
          </tbody>
        </table>

        <div class="signatures">
          <div class="signature">
            <div class="signature-line">
              ${getPerformerName(requestDetails)}
            </div>
            <div>Medical Technologist</div>
          </div>
          <div class="signature">
            <div class="signature-line">
              ${getPathologistName(requestDetails)}
            </div>
            <div>Pathologist</div>
          </div>
        </div>

        <div style="margin-top: 30px; text-align: center;">
          <p>Date Printed: ${format(new Date(), 'yyyy-MM-dd HH:mm')}</p>
        </div>
      </body>
      </html>
    `);
    
    // Trigger print dialog
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
      printWindow.print();
      setTimeout(() => printWindow.close(), 1000);
    }, 500);
    
  } catch (error) {
    console.error('Error generating print view:', error);
    ElMessage.error('Failed to generate print view.');
  }
};

// Helper function to generate table rows for the print view
const generateTableRows = (requestDetails) => {
  if (!requestDetails.tests || !requestDetails.results) {
    return '<tr><td colspan="5">No results available</td></tr>';
  }
  
  let rows = '';
  
  // Process tests with their results
  requestDetails.tests.forEach(test => {
    // Add the test name as a header row
    rows += `
      <tr style="background-color: var(--color-bg-secondary, #f9f9f9)">
        <td colspan="5"><strong>${test.name}</strong></td>
      </tr>
    `;
    
    // Get results for this test
    const testResults = requestDetails.results.filter(result => 
      result.test_id === test.id
    );
    
    if (testResults.length === 0) {
      rows += `
        <tr>
          <td colspan="5" style="text-align: center">No results available for this test</td>
        </tr>
      `;
      return;
    }
    
    // Add results
    testResults.forEach(result => {
      const parameterName = result.testParameter ? result.testParameter.name : 'Result';
      const flagClass = result.flag === 'H' || result.flag === 'L' ? 'flag-critical' : 
                      (result.flag === 'h' || result.flag === 'l' ? 'flag-abnormal' : '');
      
      rows += `
        <tr>
          <td>${parameterName}</td>
          <td>${result.result_value || 'N/A'}</td>
          <td>${result.unit || ''}</td>
          <td>${result.testParameter?.reference_range || 'N/A'}</td>
          <td class="${flagClass}">${result.flag || ''}</td>
        </tr>
      `;
    });
  });
  
  return rows;
};

// Helper functions to get names for the report
const getPerformerName = (requestDetails) => {
  const performerId = requestDetails.tests?.[0]?.pivot?.perform_by;
  if (performerId) {
    const performer = performerList.value.find(user => user.id === performerId);
    return performer ? performer.name : 'N/A';
  }
  return 'N/A';
};

const getPathologistName = (requestDetails) => {
  const pathologistId = requestDetails.tests?.[0]?.pivot?.pathologist_id;
  if (pathologistId) {
    const pathologist = pathologistList.value.find(user => user.id === pathologistId);
    return pathologist ? pathologist.name : 'N/A';
  }
  return 'N/A';
};

// --- Placeholder for saving results (to be implemented based on modal event) ---
const handleSaveResults = (requestId, resultsData) => {
  console.log('Save results action triggered from modal:', { requestId, resultsData });
  
  // Show success message
  ElMessage.success('Results saved successfully');
  
  // Refresh the list to show updated status
  fetchHematologyRequests();
  
  // Keep modal open so user can continue working if needed
};

// Handle print results event from the modal
const handlePrintResults = (requestId, resultsData) => {
  console.log('Print & Release action triggered from modal:', { requestId, resultsData });
  
  // Show success message
  ElMessage.success('Results printed and released successfully');
  
  // Refresh the list to show updated status
  fetchHematologyRequests();
  
  // Keep modal open in case user needs to do more
};

// Handle validate results event from the modal
const handleValidateResults = (requestId, resultsData) => {
  console.log('Validate & Release action triggered from modal:', { requestId, resultsData });
  
  // Show success message
  ElMessage.success('Results validated successfully');
  
  // Refresh the list to show updated status
  fetchHematologyRequests();
  
  // Keep modal open in case user needs to do more
};

// --- Pagination Handlers ---
const handleSizeChange = (val) => {
  pageSize.value = val;
  currentPage.value = 1; // Reset to first page when size changes
  fetchHematologyRequests();
};

const handleCurrentChange = (val) => {
  currentPage.value = val;
  fetchHematologyRequests();
};

const handleModalClose = () => {
  selectedRequest.value = null;
  detailsDialogVisible.value = false;
};

// --- Helper to close detail view ---
const closeDetailsView = () => {
  isViewingDetails.value = false;
  selectedRequestDetails.value = null;
};

// Watchers for filters/search to refetch data
watch(searchQuery, () => {
    currentPage.value = 1; // Reset to page 1 when searching
    fetchHematologyRequests();
});
watch(patientTypeFilter, () => { // Changed from statusFilter to patientTypeFilter
    currentPage.value = 1; // Reset to page 1 when filtering
    fetchHematologyRequests();
});
</script>

<style scoped>
/* .hematology-results {
  padding: 20px;
} */
.hematology-results {
  display: grid;
}
.header-actions {
  display: flex;
  gap: 10px;
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

.content-card {
  border: none;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.pagination-container {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}

/* Add custom styling for table/tags if needed */
.el-tag {
    text-transform: capitalize; /* Nicer display for status/priority */
}

.test-item {
  display: flex;
  align-items: center;
  margin-bottom: 2px; /* Spacing between test names */
}

.status-badge {
  margin-left: 8px;
}

.progress-container {
  margin-top: 8px;
  margin-left: 2px;
  width: 100%;
}

.test-progress {
  width: 100%;
  font-size: 4px;
}

.test-progress :deep(.el-progress-bar__inner) {
  transition: width 0.6s ease;
}

.test-progress :deep(.el-progress-bar__innerText) {
  font-weight: var(--font-weight-light);
  color: var(--color-text-primary);
}

.progress-detail {
  font-size: 12px;
  color: var(--el-text-color-secondary);
  margin-top: 4px;
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