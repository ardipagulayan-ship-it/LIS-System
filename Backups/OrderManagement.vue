<template>
  <div class="order-management-container">
      <!-- Left side: Request List -->
    <div class="request-list">
        <div class="page-header">
            <div class="filters">
                <h2>Worklist</h2><span style="font-size:12px; margin-top: 4px;">Test requests lined-up for processing</span>
            </div>
            <div class="filters">
                <!-- NEW: Patient Filter -->
                <el-input
                    v-model="searchQuery"
                    placeholder="Search by ID or Name..."
                    clearable
                    @clear="handleFilterChange"
                    @input="handleSearchDebounced"
                    style="width: 180px;"
                >
                    <template #prefix>
                    <el-icon><Search /></el-icon>
                    </template>
                </el-input>
                <!-- NEW: Patient Type Filter -->
                <el-select
                    v-model="selectedPatientTypeId"
                    placeholder="Patient Type"
                    clearable
                    @change="handleFilterChange"
                    style="width: 180px;"
                >
                    <el-option
                        v-for="pType in patientTypes"
                        :key="pType.id"
                        :label="pType.name"
                        :value="pType.id"
                    />
                </el-select>
            </div>
        </div>

        <div class="custom-table" v-loading="loading">
            <el-table
                :data="requests"
                highlight-current-row
                @row-click="selectRequest"
                style="width: 100%"
                >
                <el-table-column label="Accession ID"  width="150">
                    <template #default="{ row }">
                        {{row.sample_id || row.id }}
                    </template>
                </el-table-column>
                <el-table-column label="Priority" width="100">
                    <template #default="{ row }">
                        <div class="status-cell">
                            <el-badge
                                :value="row.priority_level"
                                :type="getPriorityType(row.priority_level)"
                                class="priority-badge"
                            />
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="Hospital #" prop="patient.id" width="120"></el-table-column>
                <el-table-column label="Patient Name" width="200">
                    <template #default="{ row }">
                        {{ row.patient.last_name }}, {{ row.patient.first_name }}
                    </template>
                </el-table-column>
                <el-table-column label="Location" width="200">
                    <template #default="{ row }">
                        {{ row.ward?.name || '' }} {{ row.room ? `/ ${row.room.name}` : '' }}
                    </template>
                </el-table-column>
                <el-table-column label="Tests">
                    <template #default="{ row }">
                        <span v-for="(test, index) in row.tests" :key="test.id">
                            {{ test.name }}{{ index < row.tests.length - 1 ? ', ' : '' }}
                        </span>
                    </template>
                </el-table-column>
            </el-table>
        </div>

        <div class="pagination">
            <el-pagination
                v-if="total > 0"
                v-model:current-page="currentPage"
                v-model:page-size="pageSize"
                :page-sizes="[10, 20, 50]"
                :total="total"
                layout="total, sizes, prev, pager, next, jumper"
                @size-change="handleSizeChange"
                @current-change="handleCurrentChange"
                small
            />
        </div>
    </div>

    <!-- Right side: Request Details Drawer -->
    <el-drawer
        v-model="drawerVisible"
        :title="`Request Details — ${selectedRequest?.sample_id || ''}`"
        size="900px"
        direction="rtl"
    >
        <template v-if="selectedRequest">
            <!-- Patient Header -->
            <div class="patient-header">
                <div class="patient-name-row">
                    <h2 class="patient-name">{{ selectedRequest.patient.last_name }}, {{ selectedRequest.patient.first_name }} {{ selectedRequest.patient.middle_name }}</h2>
                    <el-tag :type="getPriorityType(selectedRequest.priority_level)" size="small" class="priority-badge">{{ selectedRequest.priority_level }}</el-tag>
                </div>
                <div class="patient-meta">
                    <span class="meta-item">Hospital # {{ selectedRequest.patient.id }}</span>
                    <span class="meta-sep">·</span>
                    <span class="meta-item">{{ selectedRequest.ward?.name || '' }}{{ selectedRequest.room ? ', ' + selectedRequest.room.name : '' }}</span>
                    <span class="meta-sep">·</span>
                    <span class="meta-item">DOB {{ formatDate(selectedRequest.patient.date_of_birth) }} ({{ calculateAge(selectedRequest.patient.date_of_birth) }} yrs)</span>
                    <span class="meta-sep">·</span>
                    <span class="meta-item">{{ selectedRequest.patient.gender }}</span>
                    <span class="meta-sep">·</span>
                    <span class="meta-item">Requested {{ formatDateTime(selectedRequest.request_date) }}</span>
                </div>
            </div>
          <!-- Grouped Test Sections -->
          <div class="grouped-tests-section">
              <el-collapse v-model="activeCollapseNames">
                  <el-collapse-item
                      v-for="(testsInCategory, categoryName) in groupedSelectedTests"
                      :key="categoryName"
                      :title="`${categoryName} (${testsInCategory.length})`"
                      :name="categoryName"
                      style="margin-bottom: 20px;"
                  >
                      <template #title>
                        <div class="category-header">
                          <span>{{ categoryName }} ({{ testsInCategory.length }} items)</span>
                          <div class="category-actions">
                            <!-- Batch Accept Button -->
                            <el-tooltip content="Check-in All Test Items" placement="top">
                              <el-button
                                  type="warning"
                                  size="small"
                                  icon="Finished"
                                  circle
                                  :loading="categoryLoading[categoryName]?.accepting"
                                  :disabled="!canAcceptCategory(testsInCategory) || categoryLoading[categoryName]?.accepting"
                                  @click.stop="handleBatchActionClick(categoryName, testsInCategory, 'accept')"
                              >
                              </el-button>
                            </el-tooltip>
                            <!-- Batch Collect Button -->
                            <el-tooltip content="Mark as Send-out" placement="top">
                              <el-button
                                  type="primary"
                                  size="small"
                                  icon="Promotion"
                                  circle
                                  :loading="categoryLoading[categoryName]?.collecting"
                                  :disabled="!canCollectCategory(testsInCategory) || categoryLoading[categoryName]?.collecting"
                                  @click.stop="handleBatchActionClick(categoryName, testsInCategory, 'collect')"
                              >
                              </el-button>
                            </el-tooltip>
                            <!-- Batch Reject Button -->
                            <el-tooltip content="Park All Test Items" placement="top">
                              <el-button
                                  type="danger"
                                  :icon="Odometer"
                                  size="small"
                                  circle
                                  :loading="categoryLoading[categoryName]?.rejecting"
                                  :disabled="!canRejectCategory(testsInCategory) || categoryLoading[categoryName]?.rejecting"
                                  @click.stop="handleBatchActionClick(categoryName, testsInCategory, 'reject')"
                              />
                            </el-tooltip>
                            <!-- Batch Print Button -->
                            <el-tooltip content="Print All Accepted Barcodes in Category" placement="top">
                              <el-button
                                  type="default"
                                  size="small"
                                  icon="Printer"
                                  circle
                                  :loading="categoryLoading[categoryName]?.printing"
                                  :disabled="!canPrintCategory(testsInCategory) || categoryLoading[categoryName]?.printing"
                                  @click.stop="handleBatchActionClick(categoryName, testsInCategory, 'print')"
                              >
                              </el-button>
                            </el-tooltip>
                          </div>
                        </div>
                      </template>
                      <!-- Table for tests within this category -->
                      <el-table
                        :data="testsInCategory"
                        border
                        stripe
                        size="small"
                        class="category-table"
                        :row-key="row => row.pivot.id"
                      >
                          <el-table-column label="Status" width="100" align="center">
                            <template #default="{ row }">
                                <el-tag :type="getStatusType(row.pivot.item_status)" size="small">{{ row.pivot.item_status }}</el-tag>
                            </template>
                          </el-table-column>
                          <el-table-column prop="name" label="Test Name" min-width="180" />
                          <el-table-column label="Status Info" width="250" align="center">
                            <template #default="{ row }">
                              <div v-if="row.pivot.item_status === 'Accepted'">
                                <span>Added to Workqueue</span>
                              </div>
                              <div v-else-if="row.pivot.item_status === 'Send-out'">
                                <span v-if="row.pivot.sendout_by">Sent out by ID: {{ row.pivot.sendout_by }}</span>
                                <br v-if="row.pivot.sendout_by && row.pivot.sendout_at">
                                <span v-if="row.pivot.sendout_at">{{ formatDateTime(row.pivot.sendout_at) }}</span>
                              </div>
                              <div v-else-if="row.pivot.item_status === 'Checked-in'">
                                <span v-if="row.pivot.checkin_by">Checked in by ID: {{ row.pivot.checkin_by }}</span>
                                <br v-if="row.pivot.checkin_by && row.pivot.checkin_at">
                                <span v-if="row.pivot.checkin_at">{{ formatDateTime(row.pivot.checkin_at) }}</span>
                              </div>
                              <div v-else-if="row.pivot.item_status === 'Parked'">
                                <span v-if="row.pivot.park_by">Parked by ID: {{ row.pivot.park_by }}</span>
                                <br v-if="row.pivot.park_by && row.pivot.park_at">
                                <span v-if="row.pivot.park_at">{{ formatDateTime(row.pivot.park_at) }}</span>
                                <br v-if="row.pivot.rejection_reason">
                                <span class="rejection-reason" v-if="row.pivot.rejection_reason">
                                  Reason: {{ row.pivot.rejection_reason }}
                                </span>
                              </div>
                            </template>
                          </el-table-column>
                          <el-table-column label="Actions" width="300" align="center" fixed="right">
                            <template #default="{ row }">
                              <el-button-group>  <!-- Individual Collect Button -->
                                <!-- Individual Accept Button -->
                                <el-button
                                  v-if="canAccept(row)"
                                  type="warning"
                                  icon="Check"
                                  size="small"
                                  @click="handleTestAction('accept', row)"
                                  :loading="itemLoading[row.pivot.id]?.accepting"
                                  :disabled="itemLoading[row.pivot.id]?.accepting"
                                >
                                  Check-in
                                </el-button>
                                <!-- Individual Collect Button -->
                                <el-button
                                    v-if="canCollect(row)"
                                    type="primary"
                                    icon="Promotion"
                                    size="small"
                                    @click="handleTestAction('collect', row)"
                                    :loading="itemLoading[row.pivot.id]?.collecting"
                                    :disabled="itemLoading[row.pivot.id]?.collecting"
                                >
                                  Send-out
                                </el-button>
                                <!-- Individual Reject Button -->
                                <el-button
                                    v-if="canReject(row)"
                                    type="danger"
                                    icon="Odometer"
                                    size="small"
                                    @click="handleTestAction('reject', row)"
                                    :loading="itemLoading[row.pivot.id]?.rejecting"
                                    :disabled="itemLoading[row.pivot.id]?.rejecting"
                                >
                                  Park Test
                                </el-button>
                              </el-button-group>
                            </template>
                          </el-table-column>
                      </el-table>
                  </el-collapse-item>
              </el-collapse>
          </div>
            </template>
        </el-drawer>

      <!-- Reject Dialog (Used for both individual and batch) -->
      <el-dialog
          v-model="rejectDialogVisible"
          :title="currentlyRejectingCategoryName ? `Park All Eligible in ${currentlyRejectingCategoryName}` : 'Park Test Item'"
          width="450px"
          :before-close="handleRejectDialogClose"
          destroy-on-close
          append-to-body
      >
          <el-form :model="rejectForm" ref="rejectFormRef" :rules="rejectRules" label-position="top">
              <p v-if="currentlyRejectingItem">
                  Rejecting: <strong>{{ currentlyRejectingItem.name || 'N/A' }}</strong>
              </p>
              <p v-if="currentlyRejectingCategoryName">
                  About to reject <strong>{{ itemsToBatchReject.length }}</strong> eligible item(s) in <strong>{{ currentlyRejectingCategoryName }}</strong>.
              </p>
              <el-form-item label="Reason for Rejection" prop="reason">
              <el-input
                  v-model="rejectForm.reason"
                  type="textarea"
                  :rows="3"
                  placeholder="Please provide a reason"
              />
              </el-form-item>
          </el-form>
          <template #footer>
              <span class="dialog-footer">
              <el-button @click="handleRejectDialogClose">Cancel</el-button>
              <el-button type="danger" @click="confirmReject" :loading="isConfirmingReject">
                  Confirm Rejection
              </el-button>
              </span>
          </template>
      </el-dialog>
  </div> <!-- End phlebotomy-container -->

</template>

<script setup>
import { ref, computed, onMounted, watch, reactive, nextTick } from 'vue';
// Ensure all needed icons are imported
import { Search, Check, Printer, CollectionTag, CloseBold, Odometer, Promotion, WarningFilled, InfoFilled, WarnTriangleFilled } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import api from '@/utils/axios'; // Your configured axios instance
import JsBarcode from 'jsbarcode';
import debounce from 'lodash/debounce';
import { format, parseISO, differenceInYears } from 'date-fns';

// --- Core State ---
const loading = ref(false); // For fetching request list
const  requests = ref([]);
const selectedRequest = ref(null);
const searchQuery = ref('');
const currentPage = ref(1);
const pageSize = ref(10);
const total = ref(0);

// --- Filter State ---
const patientTypes = ref([]); // To store patient types from API
const selectedPatientTypeId = ref(null); // v-model for the filter dropdown

// --- UI State ---
const activeCollapseNames = ref([]); // Controls which categories are expanded
const drawerVisible = ref(false); // Controls the request details drawer

// --- Loading States ---
// For category-level batch actions
const categoryLoading = ref({}); // { CategoryName: { collecting: false, accepting: false, printing: false, rejecting: false } }
// For individual row actions (using a map keyed by pivot ID for better tracking)
const itemLoading = ref({}); // { pivotId: { collecting: false, accepting: false, printing: false, rejecting: false } }

// --- Reject Dialog State ---
const rejectDialogVisible = ref(false);
const rejectFormRef = ref(null);
const rejectForm = reactive({ reason: '' });
const rejectRules = {
reason: [{ required: true, message: 'Rejection reason is required', trigger: 'blur' }]
};
const isConfirmingReject = ref(false); // Loading state ONLY for the reject dialog confirm button
const currentlyRejectingItem = ref(null); // Holds the item for individual rejection mode
const currentlyRejectingCategoryName = ref(null); // Holds category name for batch rejection mode
const itemsToBatchReject = ref([]); // Holds items for batch rejection mode

// --- API Calls ---

// Fetch Patient Types for the dropdown
const fetchPatientTypes = async () => {
  try {
      const response = await api.get('/patient-types'); // Assuming endpoint exists
      patientTypes.value = response.data || []; // Adjust based on actual API response structure
  } catch (error) {
      console.error("Error fetching patient types:", error);
      ElMessage.error("Could not load patient types for filtering.");
      patientTypes.value = []; // Ensure it's an array on error
  }
};

// Fetch Phlebotomy Requests (Modified to include filter)
const fetchRequests = async () => {
loading.value = true;
const previousSelectionId = selectedRequest.value?.id; // Store previous selection ID
selectedRequest.value = null; // Clear selection during fetch
categoryLoading.value = {}; // Clear all category loading states
itemLoading.value = {}; // Clear all individual item loading states

try {
  const params = {
    page: currentPage.value,
    per_page: pageSize.value,
    search: searchQuery.value || undefined,
    patient_type_id: selectedPatientTypeId.value || undefined, // Add patient type filter
  };
  const response = await api.get('/order/requests', { params });

  // Basic processing, ensure tests array exists
  requests.value = response.data.data.map(req => ({
      ...req,
      tests: req.tests || [], // Ensure tests is always an array
  }));
  total.value = response.data.total;

  // Attempt to re-select the previously selected request or select the first one
  if (previousSelectionId) {
      selectedRequest.value = requests.value.find(r => r.id === previousSelectionId) || null;
  }
  if (!selectedRequest.value && requests.value.length > 0) {
      selectedRequest.value = requests.value[0];
  }
  initializeSelectedRequestState(); // Setup UI state for the newly selected/fetched request

} catch (error) {
  console.error('Error fetching laboratory requests:', error);
  ElMessage.error('Failed to fetch laboratory requests');
  requests.value = [];
  total.value = 0;
} finally {
  loading.value = false;
}
};

// --- UI Initialization ---
// Helper to set up active collapse names and initialize loading states when a request is selected or fetched
const initializeSelectedRequestState = () => {
if (!selectedRequest.value) {
  activeCollapseNames.value = []; // Collapse all if no request selected
  return;
}
const groups = groupTestsByCategory(selectedRequest.value.tests);
activeCollapseNames.value = Object.keys(groups); // Expand all categories initially

// Initialize category loading states (or reset if already exists)
const newCategoryLoading = {};
Object.keys(groups).forEach(catName => {
  newCategoryLoading[catName] = { collecting: false, accepting: false, printing: false, rejecting: false };
});
categoryLoading.value = newCategoryLoading;

// Reset individual item loading map
itemLoading.value = {};
};

// --- Selection & Pagination Handlers ---
const selectRequest = (request) => {
selectedRequest.value = request;
drawerVisible.value = true; // Open drawer when a row is clicked
initializeSelectedRequestState(); // Re-initialize state for the new selection
};

const handleSearch = debounce(() => { currentPage.value = 1; fetchRequests(); }, 300);
const handleSizeChange = (val) => { pageSize.value = val; currentPage.value = 1; fetchRequests(); };
const handleCurrentChange = (val) => { currentPage.value = val; fetchRequests(); };

// Debounced handler for search input
const handleSearchDebounced = debounce(() => {
  currentPage.value = 1; // Reset page when searching
  fetchRequests();
}, 300);

// Handler for filter changes (search clear, dropdown change)
const handleFilterChange = () => {
  currentPage.value = 1; // Reset page when filter changes
  fetchRequests();
};

// --- Formatting & Type Helpers ---
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return format(parseISO(dateString), 'MMM dd, yyyy');
};

// New function to format date and time
const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A';
  return format(parseISO(dateString), 'MMM dd, yyyy p');
};

const calculateAge = (dateOfBirth) => {
  if (!dateOfBirth) return 'N/A';
  return differenceInYears(new Date(), parseISO(dateOfBirth));
};
const getStatusType = (s) => ({'Accepted': 'success', 'Rejected': 'danger', 'Send-out': 'primary', 'Parked': 'danger', 'Checked-in': 'warning'}[s] || 'info');
const getPriorityType = (p) => ({ 'STAT': 'danger', 'Urgent': 'warning', 'Routine': 'primary' }[p] || 'info');

// --- State Update Logic ---
// Central function to update items in selectedRequest.value.tests based on backend response
const updateLocalItemsState = (updatedItemsData) => {
if (!selectedRequest.value?.tests || !Array.isArray(updatedItemsData)) {
  console.error("Cannot update local state: invalid context or data.");
  return; // Exit if no request selected or data is invalid
}
console.log("Updating local state with:", JSON.parse(JSON.stringify(updatedItemsData))); // Log deep copy

// Create a map for efficient lookup by pivot ID
const testsMap = new Map(selectedRequest.value.tests.map(item => [item.pivot.id, item]));

updatedItemsData.forEach(updatedItem => {
  const pivotId = updatedItem.pivot?.id;
  if (!pivotId) {
    console.warn("Skipping update for item missing pivot ID:", updatedItem);
    return; // Skip if no pivot ID in the update data
  }

  if (testsMap.has(pivotId)) {
    const index = selectedRequest.value.tests.findIndex(item => item.pivot?.id === pivotId);
    if (index !== -1) {
      // Merge updated data into the existing item in the array to maintain reactivity
      // Prioritize pivot data from the update
      const existingItem = selectedRequest.value.tests[index];
      const newPivotData = { ...(existingItem.pivot || {}), ...(updatedItem.pivot || {}) };
       // Explicitly use item_status from pivot if present in update
      if (updatedItem.pivot?.item_status) {
          newPivotData.item_status = updatedItem.pivot.item_status;
      } else if (updatedItem.item_status) { // Fallback to top-level status
           newPivotData.item_status = updatedItem.item_status;
      }

      // Replace the item in the array using splice for reactivity
      // Make sure to create a *new* object to guarantee change detection
      selectedRequest.value.tests.splice(index, 1, {
          ...existingItem, // Keep original non-pivot data unless overwritten
          ...updatedItem, // Overwrite with new non-pivot data
          pivot: newPivotData // Assign the carefully merged pivot data
      });
      console.log(`---> Updated Pivot ID ${pivotId} state. New Status: ${selectedRequest.value.tests[index].pivot.item_status}`);
    } else {
      console.warn(`Index not found for known pivot ID ${pivotId}. State might be inconsistent.`);
    }
  } else {
    console.warn(`Updated item with Pivot ID ${pivotId} not found in local state map.`);
    // Optionally add the item if it was somehow missing?
    // selectedRequest.value.tests.push(updatedItem);
  }
});
};

// --- Individual Item Actions ---
// These functions handle the API call and state update for single items

const collectTestItem = async (item) => {
  const pivotId = item?.pivot?.id;
  if (!pivotId) return;
  itemLoading.value[pivotId] = { ...itemLoading.value[pivotId], collecting: true };
  try {
      const response = await api.post(`/order/requests/collect/${pivotId}`);
      ElMessage.success(`Marked as Send-out: ${item.name}`);
      if (response.data.item) updateLocalItemsState([response.data.item]);
  } catch (error) {
      console.error(`Error collecting item ${pivotId}:`, error);
      const errorResponse = error.response;
      ElMessage.error(errorResponse?.data?.message || 'Collect failed');
  }
  finally {
      // Reset loading state in the map
      if(itemLoading.value[pivotId]) itemLoading.value[pivotId].collecting = false;
  }
};

const acceptTestItem = async (item) => {
  const pivotId = item?.pivot?.id;
  if (!pivotId) return;
  itemLoading.value[pivotId] = { ...itemLoading.value[pivotId], accepting: true };
  try {
      const response = await api.post(`/order/requests/accept/${pivotId}`);
      ElMessage.success(`Accepted: ${item.name}`);
      // G6 — Surface ULM status if dispatch failed
      if (response.data?.ulm_status === 'failed') {
        ElMessage.error('ULM dispatch failed. Order was saved — please retry sync from QC.')
      }
      if (response.data.item) updateLocalItemsState([response.data.item]);
  } catch (error) {
      console.error(`Error accepting item ${pivotId}:`, error);
      const errorResponse = error.response;
      ElMessage.error(errorResponse?.data?.message || 'Accept failed');
  }
  finally {
      if(itemLoading.value[pivotId]) itemLoading.value[pivotId].accepting = false;
  }
};

// Individual Reject is handled via dialog -> confirmReject function
const printTestItemBarcode = (item) => {
  const pivotId = item?.pivot?.id;
  if (!pivotId || !selectedRequest.value) {
      ElMessage.warning('Cannot print barcode: Missing data.');
      return;
  }
  itemLoading.value[pivotId] = { ...itemLoading.value[pivotId], printing: true };

  // --- Barcode Generation Logic ---
  const { patient, sample_id, id: reqId, request_date } = selectedRequest.value;
  const sId = sample_id || reqId; // Use sample_id if available, else request id
  const bcData = `${sId}-${pivotId}`;
  const tName = item.name || 'N/A';
  const pLname = patient?.lname || '';
  const pFname = patient?.fname || '';
  const canvas = document.createElement('canvas');
  try {
      JsBarcode(canvas, bcData, { 
          format: 'CODE128' || 'EAN13' || 'CODABAR',
          width: 1.5, 
          height: 60, 
          displayValue: true, 
          fontSize: 14, 
          margin: 5, 
          textMargin: 2 
      });
  } catch (e) {
      console.error("JsBarcode Error:", e);
      ElMessage.error('Barcode generation failed.');
      if(itemLoading.value[pivotId]) itemLoading.value[pivotId].printing = false; // Reset loading
      return;
  }
  // --- Print Window Logic ---
  const printWindow = window.open('', '_blank');
  if (!printWindow) {
      ElMessage.error('Failed to open print window.');
      if(itemLoading.value[pivotId]) itemLoading.value[pivotId].printing = false; // Reset loading
      return;
  }
  // Correctly escape <\/script> tag within template literal
  const htmlContent = `<!DOCTYPE html>
  <html>
      <head>
          <title>Print Barcode</title>
          <style>
              @media print { 
                  @page { 
                      margin: 3mm; 
                      size: auto; 
                  }  
                  html, body { 
                      margin: 0; 
                      padding: 0; 
                  } 
              } 
              body { 
              margin: 5px; 
              font-family: 'Arial', sans-serif; 
              line-height: 1.2; 
              } 
              .lbl { 
              display: inline-block; 
              text-align: center; 
              page-break-inside: avoid; 
              border: 1px solid #eee; 
              padding: 3px; 
              margin: 2px; 
              } 
              .img { 
              display: block; 
              margin: 0 auto 2px auto; 
              } 
              .pi p { 
              margin: 0; 
              font-size: 9px; 
              white-space: nowrap; 
              overflow: hidden; t
              ext-overflow: ellipsis; 
              max-width: 150px; 
              }
          </style>
      </head>
      <body>
          <div class="lbl">
              <img class="img" src="${canvas.toDataURL()}"/>
              <div class="pi">
                  <p>${pLname}, ${pFname}</p>
                  <p>SID: ${sId}</p>
                  <p>${tName}</p>
                  <p>${formatDate(request_date)}</p>
              </div>
          </div>
          <script>
              window.onload=()=>{
                  try{
                      window.print();
                      let p=false;
                      window.onafterprint=()=>{
                          p=true;
                          setTimeout(window.close,50)
                      };
                      setTimeout(()=>{
                          if(!p)window.close()
                      },500)
                  }
                  catch(e){
                      window.close()
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
  } finally {
      // Reset loading state using the map
      if(itemLoading.value[pivotId]) itemLoading.value[pivotId].printing = false;
  }
};

// --- Event Handlers ---

// Handles clicks on individual action buttons in the table rows
const handleTestAction = (action, item) => {
  const pivotId = item?.pivot?.id;
  console.log(`Individual Action Click: ${action}, Pivot ID: ${pivotId}`);
  if (!pivotId) {
      console.error("Action triggered with invalid item (no pivot ID):", item);
      return;
  }

  switch (action) {
      case 'collect':
          collectTestItem(item);
          break;
      case 'accept':
          acceptTestItem(item);
          break;
      case 'reject': // Sets up the reject dialog for this specific item
          currentlyRejectingItem.value = item;
          currentlyRejectingCategoryName.value = null; // Ensure batch mode is off
          itemsToBatchReject.value = [];
          rejectForm.reason = ''; // Clear previous reason
          rejectDialogVisible.value = true;
          // Reset validation state when dialog opens
          nextTick(() => { rejectFormRef.value?.resetFields(); });
          break;
      case 'print':
          printTestItemBarcode(item);
          break;
      default:
          console.warn('Unknown individual action requested:', action);
  }
};

// Handles clicks on BATCH action buttons in category headers
const handleBatchActionClick = (categoryName, tests, action) => {
  console.log(`Batch Action Click: ${action}, Category: ${categoryName}`);
  switch (action) {
      case 'collect':
          batchCollect(tests, categoryName);
          break;
      case 'accept':
          batchAccept(tests, categoryName);
          break;
      case 'reject': // Sets up the reject dialog for the entire category
          const itemsEligible = tests.filter(t => ['Accepted'].includes(t.pivot?.item_status));
          //const itemsEligible = tests.filter(t => t.pivot?.item_status === 'Accepted');
          if (!itemsEligible.length) {
              ElMessage.info(`No items in "${categoryName}" are eligible for rejection.`);
              return;
          }
          currentlyRejectingCategoryName.value = categoryName; // Set batch mode category
          itemsToBatchReject.value = itemsEligible; // Store eligible items
          currentlyRejectingItem.value = null; // Ensure individual mode is off
          rejectForm.reason = ''; // Clear previous reason
          rejectDialogVisible.value = true; // Open the dialog
           // Reset validation state when dialog opens
          nextTick(() => { rejectFormRef.value?.resetFields(); });
          break;
      case 'print':
          printCategoryBarcodes(tests, categoryName);
          break;
      default:
          console.warn(`Unknown batch action requested: ${action}`);
  }
};

// --- Reject Dialog Logic ---
// Handles the 'Confirm' button click in the reject dialog
const confirmReject = async () => {
if (!rejectFormRef.value) return; // Safety check for form reference

// Validate the form input (reason is required)
await rejectFormRef.value.validate(async (valid) => {
  if (!valid) {
      console.log('Reject form validation failed');
      return; // Stop if validation fails
  }

  isConfirmingReject.value = true; // Start dialog confirm button loading
  const reason = rejectForm.reason;
  let categoryNameForLoading = null; // Track category name if batch op for finally block
  let pivotIdForLoading = null; // Track pivotId if individual op for finally block

  try {
    // --- Batch Rejection Mode ---
    if (currentlyRejectingCategoryName.value && itemsToBatchReject.value.length > 0) {
      categoryNameForLoading = currentlyRejectingCategoryName.value;
      const itemIds = itemsToBatchReject.value.map(item => item.pivot.id);

      // Set category loading state
      if (categoryLoading.value[categoryNameForLoading]) {
           categoryLoading.value[categoryNameForLoading].rejecting = true;
      } else {
          // Initialize defensively if somehow missing
          categoryLoading.value[categoryNameForLoading] = { rejecting: true };
      }

      console.log(`BATCH Park IDs: ${itemIds.join(', ')} in category ${categoryNameForLoading}`);
      // Call the batch reject API endpoint
      const response = await api.post('/order/requests/reject-batch', { item_ids: itemIds, reason: reason });
      console.log('Batch park response:', response.data);

      // Process results from the batch operation
      const successCount = response.data.results?.success?.length || 0;
      const failCount = response.data.results?.failed?.length || 0;
      if (successCount > 0) ElMessage.success(`${successCount} item(s) parked successfully in ${categoryNameForLoading}.`);
      if (failCount > 0) ElMessage.warning(`${failCount} item(s) in ${categoryNameForLoading} could not be parked.`);
      if (successCount === 0 && failCount === 0) ElMessage.info('No items were affected by the batch parked.');

      // Update local state with successfully updated items from the response
      if (response.data.updatedItems) {
          updateLocalItemsState(response.data.updatedItems);
      }

    // --- Individual Rejection Mode ---
    } else if (currentlyRejectingItem.value?.pivot?.id) {
      const item = currentlyRejectingItem.value;
      pivotIdForLoading = item.pivot.id;
      itemLoading.value[pivotIdForLoading] = { ...itemLoading.value[pivotIdForLoading], rejecting: true };

      console.log(`Parked ID: ${pivotIdForLoading}`);
      const response = await api.post(`/order/requests/reject/${pivotIdForLoading}`, { reason: reason });
      console.log('Individual reject response:', response.data);

      ElMessage.success(`Rejected: ${item.name}`);
      // Update local state for the single item
      if (response.data.item) {
          updateLocalItemsState([response.data.item]);
      }

    } else {
       // This case should not happen if the dialog is opened correctly
       throw new Error("Confirm reject called in an invalid state (no individual item or batch category).");
    }

    handleRejectDialogClose(); // Close the dialog after successful operation

  } catch (error) {
      console.error('Error during rejection process:', error);
      const errorResponse = error.response;
      ElMessage.error(errorResponse?.data?.message || 'Rejection failed. Please try again.');
      // Keep the dialog open on error to allow user to retry or cancel
  } finally {
      isConfirmingReject.value = false; // Always reset dialog button loading
      // Reset category loading ONLY if it was a batch operation
      if (categoryNameForLoading && categoryLoading.value[categoryNameForLoading]) {
          categoryLoading.value[categoryNameForLoading].rejecting = false;
      }
      // Reset individual item loading state if it was set
      if (pivotIdForLoading && itemLoading.value[pivotIdForLoading]) {
          itemLoading.value[pivotIdForLoading].rejecting = false;
      }
  }
});
};

// Handles closing the reject dialog (via X, Cancel button, or Esc key)
const handleRejectDialogClose = (done) => {
// Prevent closing if the rejection is currently processing
if (isConfirmingReject.value) {
  ElMessage.warning('Please wait for the operation to complete.');
  return;
}
// Reset state variables associated with the dialog
rejectDialogVisible.value = false;
currentlyRejectingItem.value = null;
currentlyRejectingCategoryName.value = null;
itemsToBatchReject.value = [];
rejectForm.reason = ''; // Clear the reason input
// Call done() if it's provided by el-dialog's before-close hook
if (typeof done === 'function') {
  done();
}
};


// --- Batch Action Implementations ---
// These functions handle the API calls and state updates for batch operations

const batchCollect = async (tests, categoryName) => {
  const itemsToCollect = tests.filter(t => t.pivot?.item_status === 'Accepted');
  if (!itemsToCollect.length) { ElMessage.info(`No Pending items in "${categoryName}" to collect.`); return; }
  const itemIds = itemsToCollect.map(t => t.pivot.id);

  // Set category loading state
  if (!categoryLoading.value[categoryName]) categoryLoading.value[categoryName] = {}; // Initialize if needed
  categoryLoading.value[categoryName].collecting = true;

  console.log(`BATCH Collecting IDs: ${itemIds.join(', ')} in ${categoryName}`);
  try {
      // Use POST as defined in routes
      const response = await api.post('/order/requests/collect-batch', { item_ids: itemIds });
      const successCount = response.data.results?.success?.length || 0;
      const failCount = response.data.results?.failed?.length || 0;
      if (successCount > 0) ElMessage.success(`${successCount} item(s) collected successfully.`);
      if (failCount > 0) ElMessage.warning(`${failCount} item(s) could not be collected.`);
      if (response.data.updatedItems) updateLocalItemsState(response.data.updatedItems);
  } catch (error) {
      console.error("Error during batch collect:", error);
      ElMessage.error(error.response?.data?.message || 'Batch collection failed.');
  } finally {
      if (categoryLoading.value[categoryName]) categoryLoading.value[categoryName].collecting = false;
  }
};

const batchAccept = async (tests, categoryName) => {
  const itemsToAccept = tests.filter(t => ['Accepted', 'Send-out', 'Parked'].includes(t.pivot?.item_status));
  if (!itemsToAccept.length) { ElMessage.info(`No Collected items in "${categoryName}" to accept.`); return; }
  const itemIds = itemsToAccept.map(t => t.pivot.id);

  if (!categoryLoading.value[categoryName]) categoryLoading.value[categoryName] = {};
  categoryLoading.value[categoryName].accepting = true;

  console.log(`BATCH Accepting IDs: ${itemIds.join(', ')} in ${categoryName}`);
  try {
      // Use POST as defined in routes
      const response = await api.post('/order/requests/accept-batch', { item_ids: itemIds });
      const successCount = response.data.results?.success?.length || 0;
      const failCount = response.data.results?.failed?.length || 0;
      if (successCount > 0) ElMessage.success(`${successCount} item(s) accepted successfully.`);
      if (failCount > 0) ElMessage.warning(`${failCount} item(s) could not be accepted.`);
      // G6 — Surface per-item or batch ULM status
      const batchFailed = ['failed'].includes(response.data?.ulm_status)
      const perItemFailed = (response.data?.results?.success || []).some(t => t.ulm_status === 'failed')
      if (batchFailed || perItemFailed) {
        ElMessage.error('ULM dispatch failed for one or more tests. See ulm.log for details.')
      }
      if (response.data.updatedItems) updateLocalItemsState(response.data.updatedItems);
  } catch (e) { ElMessage.error(e.response?.data?.message || 'Batch accept failed.'); }
  finally { if(categoryLoading.value[categoryName]) categoryLoading.value[categoryName].accepting = false; }
};

// Batch Print (ensure loading state is handled)
const printCategoryBarcodes = (tests, categoryName) => {
  const itemsToPrint = tests.filter(t => t.pivot?.item_status === 'Accepted');
  if (!itemsToPrint.length) { ElMessage.info(`No Accepted items in "${categoryName}" to print.`); return; }
  if (!selectedRequest.value) { ElMessage.warning("No request selected."); return; }

  if (!categoryLoading.value[categoryName]) categoryLoading.value[categoryName] = {};
  categoryLoading.value[categoryName].printing = true;

  // --- Barcode Generation Logic ---
  const { patient, sample_id, id: reqId, request_date } = selectedRequest.value;
  const sId = sample_id || reqId;
  const pLname = patient?.lname || '';
  const pFname = patient?.fname || '';
  let barcodeHtml = '';
  itemsToPrint.forEach(item => {
      const bcData = `${sId}`;
      const tName = item.name || 'N/A';
      const canvas = document.createElement('canvas');
      try {
          JsBarcode(canvas, bcData, { 
              format: 'CODE128', 
              width: 1.5, 
              height: 60, 
              displayValue: true, 
              fontSize: 14, 
              margin: 5, 
              textMargin: 2 
          });
          barcodeHtml = `
          <div class="label-container">
              <img class="barcode-img" src="${canvas.toDataURL()}"/>
              <div class="patient-info">
                  <p>${pLname}, ${pFname}</p>
                  <p>SID: ${sId}</p>
                  <p>${tName}</p>
                  <p>${formatDate(request_date)}</p>
              </div>
          </div>`;
      } catch (e) {
          console.error("JsBarcode Error for item:", tName, e);
          barcodeHtml += `<div class="label-container error">Barcode failed for ${tName}</div>`;
      }
  });
  // --- Print Window Logic ---
  const printWindow = window.open('', '_blank');
  if (!printWindow) {
      ElMessage.error('Failed to open print window.');
      if (categoryLoading.value[categoryName]) categoryLoading.value[categoryName].printing = false;
      return;
  }
  // Correctly escape <\/script> tag within template literal
  const fullHtml = `<!DOCTYPE html>
  <html>
      <head>
          <title>Print Barcodes - ${categoryName}</title>
          <style>
              @media print{
                  @page{
                      margin:3mm;
                      size:custom;
                      width:288px;
                      height:2in;
                  }
                  html,body{
                      margin:0;
                      padding:0;
                      width:3in;
                      height:2in;
                  }
              }
              body{
                  margin:5px;
                  font-family:'Arial',sans-serif
              }
              .label-container{
                  display:inline-block;
                  vertical-align:top;
                  text-align:center;
                  page-break-inside:avoid;
                  border:1px solid #eee;
                  padding:3px;
                  margin:2px
                  }
              .label-container.error{
                  border-color:red;
                  color:red
              }
              .barcode-img{
                  display:block;
                  margin:0 auto 2px auto
              }
              .patient-info p{
                  margin:0;
                  font-size:9px;
                  white-space:nowrap;
                  overflow:hidden;
                  text-overflow:ellipsis;
                  max-width:150px
              }
          </style>
      </head>
      <body>${barcodeHtml}
          <script>window.onload=()=>{
              try{
                  window.print();
                  let p=false;
                  window.onafterprint=()=>{
                      p=true;
                      setTimeout(window.close,50)
                  };
                  setTimeout(()=>{
                      if(!p)window.close()},500)
              }catch(e){
                      window.close()
                  }
          }
          <\/script>
      </body>
  </html>`;
  try {
      printWindow.document.open();
      printWindow.document.write(fullHtml);
      printWindow.document.close();
  } catch (e) {
      console.error("Error writing batch print window:", e);
      ElMessage.error('Failed to prepare batch print content.');
      try { printWindow.close(); } catch (err) {}
  } finally {
      if (categoryLoading.value[categoryName]) categoryLoading.value[categoryName].printing = false;
  }
};

// --- Computed Properties & Helpers ---

// Helper function to group tests by category name
const groupTestsByCategory = (tests = []) => {
  return tests.reduce((groups, test) => {
      const categoryName = test.test_category?.name || 'Uncategorized'; // Use category name, fallback
      if (!groups[categoryName]) groups[categoryName] = [];
      groups[categoryName].push(test);
      return groups;
  }, {});
};

// Computed property to get grouped tests for the selected request
const groupedSelectedTests = computed(() => {
  const groups = groupTestsByCategory(selectedRequest.value?.tests);
  // Ensure loading states exist for displayed categories
  Object.keys(groups).forEach(catName => {
      if (!categoryLoading.value[catName]) {
          // Initialize if missing (e.g., after fetch/selection change)
          categoryLoading.value[catName] = { collecting: false, accepting: false, printing: false, rejecting: false };
      }
  });
  return groups;
});

// --- Visibility/Disabled State Helpers ---
// These determine if buttons should be shown or enabled based on item status

// Individual button checks
// TODO: Add Send-out and Parked to the statuses
const canCollect = (item) => ['Accepted'].includes(item?.pivot?.item_status); // For Send-out
const canAccept = (item) => ['Accepted', 'Send-out', 'Parked'].includes(item?.pivot?.item_status); // For Check-in
const canReject = (item) => ['Accepted'].includes(item?.pivot?.item_status); // For Park Test
const canPrintBarcode = (item) => item?.pivot?.item_status === 'Accepted'; // For Print Barcode

// Batch button checks (based on the array of tests in the category)
const canCollectCategory = (tests) => tests.some(canCollect);
const canAcceptCategory = (tests) => tests.some(canAccept);
const canPrintCategory = (tests) => tests.some(canPrintBarcode);
const canRejectCategory = (tests) => tests.some(canReject);


// --- Lifecycle Hooks ---
onMounted(() => {
fetchRequests();       // Fetch initial requests
fetchPatientTypes(); // Fetch patient types for the filter
});

</script>

<style scoped>
.order-management-container {
    display: flex;
    height: calc(100vh - 100px); /* Adjust for header/footer if any */
    gap: var(--spacing-lg);
    overflow: hidden; /* Prevent scrolling on the main container */
    color: var(--color-text-primary);
    width: 100%;
}

.request-list {
  background-color: var(--color-bg-primary);
  border-radius: var(--border-radius-md);
  padding: var(--spacing-lg);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border-secondary);
  display: flex;
  flex-direction: column;
  flex: 1 1 auto; /* Allow request-list to take full available width */
  overflow: auto; /* Enable scrolling within the panel */
  min-width: 0; /* Prevent flex overflow */
}

/* Compact Drawer Header - native el-drawer header */
:deep(.el-drawer__header) {
  padding: 12px 20px;
  border-bottom: 1px solid var(--color-border-secondary);
  background-color: var(--color-bg-primary);
  min-height: 48px;
}

:deep(.el-drawer__title) {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text-primary);
}

:deep(.el-drawer__close-btn) {
  color: var(--color-text-secondary);
  font-size: 1.1rem;
  padding: 4px;
}

:deep(.el-drawer__close-btn:hover) {
  color: var(--color-text-primary);
  background-color: var(--color-bg-secondary);
}

/* Patient Header Styles */
.patient-header {
  padding: var(--spacing-md) var(--spacing-lg);
  border-bottom: 1px solid var(--color-border-secondary);
  background-color: var(--color-bg-secondary);
}

.patient-name-row {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-xs);
}

.patient-name {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--color-text-primary);
  margin: 0;
  line-height: 1.2;
}

.priority-badge {
  flex-shrink: 0;
}

.patient-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px;
  font-size: 0.82rem;
  color: var(--color-text-secondary);
  line-height: 1.5;
}

.meta-item {
  white-space: nowrap;
}

.meta-sep {
  color: var(--color-border-secondary);
  font-weight: 600;
}

.header {
  margin: 0;
  padding: 0;
}

.page-header { /* Style for the main page title if outside the container */
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-xl);
  padding: 0;
}

.page-header h2 {
  margin: 0;
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
}

.request-list .filters {
  display: flex;
  gap: var(--spacing-sm);
  align-items: center;
}

.sample-id {
  font-weight: var(--font-weight-semibold);
  color: var(--color-primary);
}

.pagination {
  margin-top: var(--spacing-xl);
  display: flex;
  justify-content: flex-end;
}

.patient-info-section h3 {
    margin: 0 0 var(--spacing-sm) 0;
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-medium);
}

.grouped-tests-section {
  flex-grow: 1; /* Allow collapse section to fill space */
  overflow-y: auto; /* Enable scrolling within the details pane */
  margin: var(--spacing-md) calc(-1 * var(--spacing-lg)); /* Counteract parent padding */
  padding: 0 var(--spacing-lg);
}

.grouped-tests-section .el-collapse {
  border-top: none;
  border-bottom: none;
  margin: 0; /* Counteract parent padding */
}

.grouped-tests-section :deep(.el-collapse-item__header) {
  background-color: var(--color-bg-secondary);
  border-bottom: 1px solid var(--color-border-secondary);
  height: 44px;
  line-height: 44px;
  padding: 0 var(--spacing-lg);
  font-size: var(--font-size-base);
}

.grouped-tests-section :deep(.el-collapse-item__arrow) {
  margin: 0 var(--spacing-sm) 0 auto;
}

.grouped-tests-section :deep(.el-collapse-item__wrap) {
  border-bottom: none;
}

.grouped-tests-section :deep(.el-collapse-item__content) {
  padding: 0; /* Remove default padding */
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    font-weight: var(--font-weight-medium);
}

.category-actions {
    display: flex;
    gap: var(--spacing-xs);
}

.category-table {
    margin: 0;
}

.status-cell {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Deep selectors for element plus adjustments if needed */
:deep(.el-badge__content) {
    font-size: 11px;
    height: 18px;
    line-height: 18px;
    padding: 0 6px;
    border: none;
}

/* Aggressive overrides for el-table to achieve card-like rows */
.custom-table :deep(.el-table) {
  background-color: transparent !important;
  border: none !important;
}

.custom-table :deep(.el-table__body-wrapper) {
  border: none !important;
}

.custom-table :deep(.el-table__body) {
  border: none !important;
}

.custom-table :deep(.el-table__body-wrapper) table {
  border-collapse: separate !important;
  border-spacing: 0px var(--spacing-sm) !important;
}

.custom-table :deep(.el-table__row) {    
  display: table-row !important;
  background-color: var(--color-bg-primary) !important;
  box-shadow: var(--shadow-sm) !important;
  transition: all var(--transition-base) ease !important;
  cursor: pointer;
}

.custom-table :deep(.el-table__row:hover) {
  background-color: var(--color-primary-light) !important;
  transform: translateY(-2px) !important;
}

.custom-table :deep(.el-table__row.current-row) {
  background-color: var(--color-primary-light) !important;
  outline: 2px solid var(--color-primary) !important;
  transform: translateY(-2px) !important;
}

.custom-table :deep(.el-table__cell) {
  display: table-cell !important;
  vertical-align: middle !important;
  padding: var(--spacing-xs) 0 !important;
  border-bottom: none !important;
}

.custom-table :deep(.el-table__header th) {
  background-color: var(--color-bg-secondary) !important;
  color: var(--color-text-primary) !important;
  font-weight: var(--font-weight-bold) !important;
  padding: var(--spacing-xs) !important;
  border-bottom: 2px solid var(--color-border-primary) !important;
}

.info-card {
    background-color: var(--color-bg-secondary);
    border: 1px solid var(--color-border-secondary);
    border-radius: var(--border-radius-sm);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-md);
}
</style>
