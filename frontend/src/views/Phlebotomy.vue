<template>
  <div class="content-wrapper">
    <!-- Left side: Request List -->

    <div class="request-list">
      <div class="page-header">
        <div class="header-title-area">
          <h2 class="page-title">Worklist</h2>
          <span class="page-subtitle"
            >Test requests lined-up for extraction</span
          >
        </div>
        <div class="filters">
          <!-- NEW: Patient Filter -->
          <el-input
            v-model="searchQuery"
            placeholder="Search by ID or Name..."
            clearable
            @clear="handleFilterChange"
            @input="handleSearchDebounced"
            style="width: 180px"
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
            style="width: 180px"
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
      <div class="container">
        <div class="custom-table" v-loading="loading">
          <el-table
            :data="requests"
            highlight-current-row
            @row-click="selectRequest"
          >
            <el-table-column label="Sample ID" width="150">
              <template #default="{ row }">
                <div class="sample-id-cell">
                  <strong>{{ row.sample_id }}</strong>
                </div>
              </template>
            </el-table-column>
            <el-table-column
              label="Hospital #"
              prop="patient.id"
              width="120"
            ></el-table-column>
            <el-table-column label="Patient Name" width="300">
              <template #default="{ row }">
                <div class="patient-info-cell">
                  <div class="patient-name-row">
                    <div class="patient-name">
                      {{ row.patient?.last_name || "" }},
                      {{ row.patient?.first_name || "" }}
                    </div>
                    <div class="status-cell">
                      <el-badge
                        :value="getPriority(row.priority_level)"
                        :type="getPriorityType(row.priority_level)"
                        class="priority-badge"
                      />
                    </div>
                  </div>
                  <div class="patient-meta">
                    <span class="meta-item"
                      >{{ calculateAge(row.patient?.date_of_birth) }} /
                      {{ row.patient?.gender || "N/A" }}</span
                    >
                    <span class="meta-divider">|</span>
                    <span class="meta-item">{{
                      row.patient_type?.name || "OPD"
                    }}</span>
                  </div>
                </div>
              </template>
            </el-table-column>
            <el-table-column label="Location" width="200">
              <template #default="{ row }">
                {{ row.ward?.name || "" }}
                {{ row.room ? `/ ${row.room.name}` : "" }}
              </template>
            </el-table-column>
            <el-table-column label="Tests">
              <template #default="{ row }">
                <span v-for="(test, index) in row.tests" :key="test.id">
                  {{ test.name }}{{ index < row.tests.length - 1 ? ", " : "" }}
                </span>
              </template>
            </el-table-column>
            <el-table-column label="" align="right" width="50">
              <el-icon><ArrowRight /></el-icon>
            </el-table-column>
          </el-table>
        </div>
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
      size="650px"
      direction="rtl"
    >
      <template v-if="selectedRequest">
        <!-- Patient Header -->
        <div class="patient-header">
          <div class="patient-name-row">
            <h2 class="drawer-patient-name">
              {{ selectedRequest.patient.last_name }},
              {{ selectedRequest.patient.first_name }}
              {{ selectedRequest.patient.middle_name }}
            </h2>
            <el-tag
              :type="getPriorityType(selectedRequest.priority_level)"
              size="small"
              class="priority-badge"
              >{{ selectedRequest.priority_level }}</el-tag
            >
          </div>
          <div class="patient-meta">
            <span class="meta-item"
              >Hospital # {{ selectedRequest.patient.id }}</span
            >
            <span class="meta-sep">·</span>
            <span class="meta-item"
              >{{ selectedRequest.ward?.name || ""
              }}{{
                selectedRequest.room ? ", " + selectedRequest.room.name : ""
              }}</span
            >
            <span class="meta-sep">·</span>
            <span class="meta-item"
              >DOB {{ formatDate(selectedRequest.patient.date_of_birth) }} ({{
                calculateAge(selectedRequest.patient.date_of_birth)
              }}
              yrs)</span
            >
            <span class="meta-sep">·</span>
            <span class="meta-item">{{ selectedRequest.patient.gender }}</span>
            <span class="meta-sep">·</span>
            <span class="meta-item"
              >Requested
              {{ formatDateTime(selectedRequest.request_date) }}</span
            >
          </div>
        </div>

        <!-- Card 2: Ordered Tests -->
        <div class="info-card">
          <div class="card-header">
            <div class="card-header-left">
              <el-icon class="card-icon"><DocumentChecked /></el-icon>
              <span class="card-title">Ordered Tests</span>
            </div>
          </div>
          <div class="card-body">
            <!-- Ordered Tests Table -->
            <div class="ordered-tests-section">
              <el-table
                :data="selectedRequest.tests"
                size="small"
                style="width: 100%"
              >
                <el-table-column label="Test Name">
                  <template #default="{ row }">
                    <div class="test-name-cell">
                      <strong>{{ row.name }}</strong>
                    </div>
                  </template>
                </el-table-column>
                <el-table-column label="Status" width="120">
                  <template #default="{ row }">
                    <el-tag
                      :type="getStatusType(row.pivot.item_status)"
                      size="small"
                      >{{ row.pivot.item_status }}</el-tag
                    >
                  </template>
                </el-table-column>
                <el-table-column label="Actions" width="100" align="right">
                  <template #default="{ row }">
                    <el-dropdown trigger="hover">
                      <el-button size="small" circle>
                        <el-icon><MoreFilled /></el-icon>
                      </el-button>
                      <template #dropdown>
                        <el-dropdown-menu>
                          <el-dropdown-item @click="printTestItemBarcode(row)">
                            <template #default>
                              <el-icon><Printer /></el-icon>
                              <span>Print Label</span>
                            </template>
                          </el-dropdown-item>
                          <el-dropdown-item
                            v-if="canCollect(row)"
                            @click="collectTestItem(row)"
                          >
                            <template #default>
                              <el-icon><CollectionTag /></el-icon>
                              <span>Collect</span>
                            </template>
                          </el-dropdown-item>
                          <el-dropdown-item
                            v-if="canAccept(row)"
                            @click="acceptTestItem(row)"
                          >
                            <template #default>
                              <el-icon><Check /></el-icon>
                              <span>Accept</span>
                            </template>
                          </el-dropdown-item>
                          ----------------
                          <el-dropdown-item
                            v-if="canReject(row)"
                            @click="handleTestAction('reject', row)"
                            class="dropdown-danger-item"
                          >
                            <template #default>
                              <el-icon><CloseBold /></el-icon>
                              <span>Reject</span>
                            </template>
                          </el-dropdown-item>
                        </el-dropdown-menu>
                      </template>
                    </el-dropdown>
                  </template>
                </el-table-column>
              </el-table>
            </div>
          </div>
        </div>
      </template>
      <template #footer>
        <div class="drawer-footer">
          <div class="reject-section">
            <el-button
              :icon="CloseBold"
              @click="openReportIssueDialog(selectedRequest)"
              class="reject-btn"
            >
              Reject All
            </el-button>
          </div>
          <div class="accept-section">
            <el-button
              class="custom-button-secondary"
              :icon="Printer"
              @click="handlePrintLabel(selectedRequest)"
              >Print Label</el-button
            >
            <el-button
              v-if="canCollectSample"
              class="custom-button-primary"
              :icon="CollectionTag"
              @click="handleCollectSample(selectedRequest)"
              >Collect All</el-button
            >
            <el-button
              v-if="canAcceptSample"
              class="custom-button-success"
              :icon="Check"
              @click="handleAcceptSample(selectedRequest)"
              >Accept All</el-button
            >
          </div>
        </div>
      </template>
    </el-drawer>

    <!-- Reject Dialog (Used for both individual and batch) -->
    <el-dialog
      v-model="rejectDialogVisible"
      :title="
        currentlyRejectingCategoryName
          ? `Reject All Eligible in ${currentlyRejectingCategoryName}`
          : 'Reject Test Item'
      "
      width="450px"
      :before-close="handleRejectDialogClose"
      destroy-on-close
      append-to-body
    >
      <el-form
        :model="rejectForm"
        ref="rejectFormRef"
        :rules="rejectRules"
        label-position="top"
      >
        <p v-if="currentlyRejectingItem">
          Rejecting:
          <strong>{{ currentlyRejectingItem.name || "N/A" }}</strong>
        </p>
        <p v-if="currentlyRejectingCategoryName">
          About to reject
          <strong>{{ itemsToBatchReject.length }}</strong> eligible item(s) in
          <strong>{{ currentlyRejectingCategoryName }}</strong
          >.
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
          <el-button
            type="danger"
            @click="confirmReject"
            :loading="isConfirmingReject"
          >
            Confirm Rejection
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>

  <!-- End phlebotomy-container -->
</template>

<script setup>
import { ref, computed, onMounted, watch, reactive, nextTick } from "vue";
// Ensure all needed icons are imported
import {
  Search,
  Check,
  Printer,
  CollectionTag,
  CloseBold,
  WarningFilled,
  InfoFilled,
  WarnTriangleFilled,
  DocumentChecked,
  ArrowRight,
  Operation,
  MoreFilled,
} from "@element-plus/icons-vue";
import { ElMessage } from "element-plus";
import api from "@/utils/axios"; // Your configured axios instance
import JsBarcode from "jsbarcode";
import debounce from "lodash/debounce";
import { format, parseISO, differenceInYears } from "date-fns";

// --- Core State ---
const loading = ref(false); // For fetching request list
const requests = ref([]);
const selectedRequest = ref(null);
const searchQuery = ref("");
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
const rejectForm = reactive({ reason: "" });
const rejectRules = {
  reason: [
    {
      required: true,
      message: "Rejection reason is required",
      trigger: "blur",
    },
  ],
};
const isConfirmingReject = ref(false); // Loading state ONLY for the reject dialog confirm button
const currentlyRejectingItem = ref(null); // Holds the item for individual rejection mode
const currentlyRejectingCategoryName = ref(null); // Holds category name for batch rejection mode
const itemsToBatchReject = ref([]); // Holds items for batch rejection mode

// --- API Calls ---

// Fetch Patient Types for the dropdown
const fetchPatientTypes = async () => {
  try {
    const response = await api.get("/patient-types"); // Assuming endpoint exists
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
    const response = await api.get("/phlebotomy/requests", { params });

    // Basic processing, ensure tests array exists
    requests.value = response.data.data.map((req) => ({
      ...req,
      tests: req.tests || [], // Ensure tests is always an array
    }));
    total.value = response.data.total;

    // Attempt to re-select the previously selected request or select the first one
    if (previousSelectionId) {
      selectedRequest.value =
        requests.value.find((r) => r.id === previousSelectionId) || null;
    }
    if (!selectedRequest.value && requests.value.length > 0) {
      selectedRequest.value = requests.value[0];
    }

    initializeSelectedRequestState(); // Setup UI state for the newly selected/fetched request
  } catch (error) {
    console.error("Error fetching phlebotomy requests:", error);
    ElMessage.error("Failed to fetch laboratory requests");
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
  Object.keys(groups).forEach((catName) => {
    newCategoryLoading[catName] = {
      collecting: false,
      accepting: false,
      printing: false,
      rejecting: false,
    };
  });
  categoryLoading.value = newCategoryLoading;

  // Reset individual item loading map
  itemLoading.value = {};

  console.log(selectedRequest.value);
};

// --- Selection & Pagination Handlers ---
const selectRequest = (request) => {
  selectedRequest.value = request;
  drawerVisible.value = true; // Open drawer when a row is clicked
  initializeSelectedRequestState(); // Re-initialize state for the new selection
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
const formatDate = (d) => (d ? format(parseISO(d), "MMM dd, yyyy") : "N/A");
const formatDateTime = (d) => (d ? format(parseISO(d), "MM/dd/yyyy p") : "N/A");
const calculateAge = (dob) =>
  dob ? differenceInYears(new Date(), parseISO(dob)) : "N/A";
const getStatusType = (s) =>
  ({
    Pending: "warning",
    Collected: "primary",
    Accepted: "success",
    Rejected: "danger",
  })[s] || "info";
const getPriorityType = (p) =>
  ({ STAT: "danger", Urgent: "danger", Routine: "primary" })[p] || "info";
const getPriority = (p) => {
  switch (p) {
    case "STAT":
      return "STAT";
    case "Urgent":
      return "Urgent";
    case "Routine":
      "";
  }
};

const canAcceptSample = computed(() => {
  return selectedRequest.value?.tests.some(
    (test) => test.pivot?.item_status === "Collected",
  );
});

const canCollectSample = computed(() => {
  return selectedRequest.value?.tests.some(
    (test) => test.pivot?.item_status === "Pending",
  );
});

// --- State Update Logic ---
// Central function to update items in selectedRequest.value.tests based on backend response
const updateLocalItemsState = (updatedItemsData) => {
  if (!selectedRequest.value?.tests || !Array.isArray(updatedItemsData)) {
    console.error("Cannot update local state: invalid context or data.");
    return; // Exit if no request selected or data is invalid
  }
  console.log(
    "Updating local state with:",
    JSON.parse(JSON.stringify(updatedItemsData)),
  ); // Log deep copy

  // Create a map for efficient lookup by pivot ID
  const testsMap = new Map(
    selectedRequest.value.tests.map((item) => [item.pivot.id, item]),
  );

  updatedItemsData.forEach((updatedItem) => {
    const pivotId = updatedItem.pivot?.id;
    if (!pivotId) {
      console.warn("Skipping update for item missing pivot ID:", updatedItem);
      return; // Skip if no pivot ID in the update data
    }

    if (testsMap.has(pivotId)) {
      const index = selectedRequest.value.tests.findIndex(
        (item) => item.pivot?.id === pivotId,
      );
      if (index !== -1) {
        // Merge updated data into the existing item in the array to maintain reactivity
        // Prioritize pivot data from the update
        const existingItem = selectedRequest.value.tests[index];
        const newPivotData = {
          ...(existingItem.pivot || {}),
          ...(updatedItem.pivot || {}),
        };
        // Explicitly use item_status from pivot if present in update
        if (updatedItem.pivot?.item_status) {
          newPivotData.item_status = updatedItem.pivot.item_status;
        } else if (updatedItem.item_status) {
          // Fallback to top-level status
          newPivotData.item_status = updatedItem.item_status;
        }

        // Replace the item in the array using splice for reactivity
        // Make sure to create a *new* object to guarantee change detection
        selectedRequest.value.tests.splice(index, 1, {
          ...existingItem, // Keep original non-pivot data unless overwritten
          ...updatedItem, // Overwrite with new non-pivot data
          pivot: newPivotData, // Assign the carefully merged pivot data
        });

        console.log(
          `---> Updated Pivot ID ${pivotId} state. New Status: ${selectedRequest.value.tests[index].pivot.item_status}`,
        );
      } else {
        console.warn(
          `Index not found for known pivot ID ${pivotId}. State might be inconsistent.`,
        );
      }
    } else {
      console.warn(
        `Updated item with Pivot ID ${pivotId} not found in local state map.`,
      );
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
  itemLoading.value[pivotId] = {
    ...itemLoading.value[pivotId],
    collecting: true,
  };
  try {
    const response = await api.post(`/phlebotomy/requests/collect/${pivotId}`);
    ElMessage.success(`Collected: ${item.name}`);
    if (response.data.item) updateLocalItemsState([response.data.item]);
  } catch (error) {
    console.error(`Error collecting item ${pivotId}:`, error);
    const errorResponse = error.response;
    ElMessage.error(errorResponse?.data?.message || "Collect failed");
  } finally {
    // Reset loading state in the map
    if (itemLoading.value[pivotId])
      itemLoading.value[pivotId].collecting = false;
  }
};

const acceptTestItem = async (item) => {
  const pivotId = item?.pivot?.id;
  if (!pivotId) return;
  itemLoading.value[pivotId] = {
    ...itemLoading.value[pivotId],
    accepting: true,
  };
  try {
    const response = await api.post(`/phlebotomy/requests/accept/${pivotId}`);
    ElMessage.success(`Accepted: ${item.name}`);
    if (response.data.item) updateLocalItemsState([response.data.item]);
  } catch (error) {
    console.error(`Error accepting item ${pivotId}:`, error);
    const errorResponse = error.response;
    ElMessage.error(errorResponse?.data?.message || "Accept failed");
  } finally {
    if (itemLoading.value[pivotId])
      itemLoading.value[pivotId].accepting = false;
  }
};

// Individual Reject is handled via dialog -> confirmReject function

// --- Section Label Printing (2in x 1in) ---
// Labels are printed PER SECTION (test category) - not per test.
// Layout (as per reference image):
//   PATIENT NAME                  #N  SEX
//   [barcode =====================] AGE
//   <patient / hospital id>
//   <sample id>
//   DOB: MM/dd/yyyy
//   yyyy-MM-dd HH:mm:ss

const escapeLabelHtml = (value) =>
  String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");

// Generate the barcode image (CODE128 encoding of the sample/accession ID)
const generateLabelBarcodeDataUrl = (bcData) => {
  const canvas = document.createElement("canvas");
  JsBarcode(canvas, bcData, {
    format: "CODE128",
    width: 1.5,
    height: 26,
    displayValue: false,
    margin: 0,
    maxWidth: 140,
  });
  return canvas.toDataURL();
};

// Build the HTML for one 2in (W) x 1in (H) section label
const buildSectionLabelHtml = (request, sectionNumber, barcodeDataUrl) => {
  const { patient, sample_id, id: reqId } = request;
  const sId = sample_id || reqId || "";
  const patientName = [
    patient?.last_name,
    patient?.first_name,
    patient?.middle_name,
  ]
    .filter(Boolean)
    .join(" ");
  const hospitalNo = patient?.id ?? "";
  const sex = patient?.gender || "";
  const ageYears = patient?.date_of_birth
    ? calculateAge(patient.date_of_birth)
    : null;
  const age =
    typeof ageYears === "number" && !Number.isNaN(ageYears)
      ? `${ageYears}y`
      : "";
  const dob = patient?.date_of_birth
    ? format(parseISO(patient.date_of_birth), "MM/dd/yyyy")
    : "";
  const printedAt = format(new Date(), "yyyy-MM-dd HH:mm:ss");

  return `
        <div class="label">
            <div class="label-top">
                <span class="label-name">${escapeLabelHtml(patientName)}</span>
                <span class="label-demographics">
                    <span class="label-seq">#${sectionNumber}</span>
                    <span class="label-sex">${escapeLabelHtml(sex)}</span>
                </span>
            </div>
            <div class="label-barcode-row">
                <img class="label-barcode" src="${barcodeDataUrl}" alt="" />
                <span class="label-age">${escapeLabelHtml(age)}</span>
            </div>
            <div class="label-details">
                <div class="label-line">${escapeLabelHtml(hospitalNo)}</div>
                <div class="label-line">${escapeLabelHtml(sId)}</div>
                <div class="label-line">DOB: ${escapeLabelHtml(dob)}</div>
                <div class="label-line">${printedAt}</div>
            </div>
        </div>`;
};

// Build the labels for the given sections: [{ name, number }]
// Returns an empty string when the barcode cannot be generated.
const buildSectionLabelsHtml = (request, sections) => {
  const bcData = `${request.sample_id || request.id}`;
  let barcodeDataUrl = "";
  try {
    barcodeDataUrl = generateLabelBarcodeDataUrl(bcData);
  } catch (e) {
    console.error("JsBarcode Error:", e);
    ElMessage.error("Barcode generation failed.");
    return "";
  }

  return sections
    .map((section) =>
      buildSectionLabelHtml(request, section.number, barcodeDataUrl),
    )
    .join("");
};

// Open the print window - every page is a single 2in x 1in label
const openLabelPrintWindow = (labelsHtml, title) => {
  const printWindow = window.open("", "_blank");
  if (!printWindow) {
    ElMessage.error("Failed to open print window.");
    return false;
  }

  // Correctly escape <\/script> tag within template literal
  const fullHtml = `<!DOCTYPE html>
    <html>
        <head>
            <title>${title}</title>
            <style>
                @page {
                    size: 2in 1in;
                    margin: 0;
                }
                html,
                body {
                    margin: 0;
                    padding: 0;
                    background: #fff;
                }
                body {
                    font-family: 'Arial', sans-serif;
                    color: #000;
                }
                .label {
                    box-sizing: border-box;
                    width: 2in;
                    height: 1in;
                    padding: 3px 4px;
                    overflow: hidden;
                    display: flex;
                    flex-direction: column;
                    background: #fff;
                    page-break-after: always;
                    page-break-inside: avoid;
                }
                .label:last-child {
                    page-break-after: auto;
                }
                .label-top {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    gap: 4px;
                }
                .label-name {
                    flex: 1 1 auto;
                    min-width: 0;
                    font-size: 9px;
                    font-weight: bold;
                    line-height: 1.1;
                    text-transform: uppercase;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                .label-demographics {
                    flex-shrink: 0;
                    display: flex;
                    gap: 5px;
                    font-size: 7.5px;
                    font-weight: bold;
                    line-height: 1.1;
                    white-space: nowrap;
                }
                .label-barcode-row {
                    display: flex;
                    align-items: center;
                    gap: 4px;
                    margin-top: 1px;
                }
                .label-barcode {
                    display: block;
                    height: 26px;
                    width: auto;
                }
                .label-age {
                    margin-left: auto;
                    font-size: 7.5px;
                    font-weight: bold;
                }
                .label-details {
                    margin-top: auto;
                    display: flex;
                    flex-direction: column;
                }
                .label-line {
                    font-size: 7px;
                    line-height: 1.15;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
            </style>
        </head>
        <body>
            ${labelsHtml}
            <script>
                window.onload = () => {
                    try {
                        window.print();
                        setTimeout(window.close, 500);
                    } catch (e) {
                        window.close();
                    }
                };
            <\/script>
        </body>
    </html>`;

  try {
    printWindow.document.open();
    printWindow.document.write(fullHtml);
    printWindow.document.close();
    return true;
  } catch (e) {
    console.error("Error writing to print window:", e);
    ElMessage.error("Failed to prepare print content.");
    try {
      printWindow.close();
    } catch (err) {}
    return false;
  }
};

// Row action: print the label of the SECTION the clicked test belongs to
const printTestItemBarcode = (item) => {
  const pivotId = item?.pivot?.id;
  if (!pivotId || !selectedRequest.value) {
    ElMessage.warning("Cannot print label: Missing data.");
    return;
  }
  itemLoading.value[pivotId] = {
    ...itemLoading.value[pivotId],
    printing: true,
  };

  try {
    const sectionNames = Object.keys(
      groupTestsByCategory(selectedRequest.value.tests),
    );
    const sectionName = item.test_category?.name || "Uncategorized";
    const sectionIndex = sectionNames.indexOf(sectionName);
    const sectionNumber = sectionIndex === -1 ? 1 : sectionIndex + 1;

    const labelsHtml = buildSectionLabelsHtml(selectedRequest.value, [
      { name: sectionName, number: sectionNumber },
    ]);
    if (!labelsHtml) return;

    if (
      openLabelPrintWindow(
        labelsHtml,
        `Label - ${selectedRequest.value.sample_id || selectedRequest.value.id}`,
      )
    ) {
      ElMessage.success(`Printing label for section: ${sectionName}`);
    }
  } finally {
    if (itemLoading.value[pivotId]) itemLoading.value[pivotId].printing = false;
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
    case "collect":
      collectTestItem(item);
      break;
    case "accept":
      acceptTestItem(item);
      break;
    case "reject": // Sets up the reject dialog for this specific item
      currentlyRejectingItem.value = item;
      currentlyRejectingCategoryName.value = null; // Ensure batch mode is off
      itemsToBatchReject.value = [];
      rejectForm.reason = ""; // Clear previous reason
      rejectDialogVisible.value = true;
      // Reset validation state when dialog opens
      nextTick(() => {
        rejectFormRef.value?.resetFields();
      });
      break;
    case "print":
      printTestItemBarcode(item);
      break;
    default:
      console.warn("Unknown individual action requested:", action);
  }
};

// Handles clicks on BATCH action buttons in category headers
const handleBatchActionClick = (categoryName, tests, action) => {
  console.log(`Batch Action Click: ${action}, Category: ${categoryName}`);
  switch (action) {
    case "collect":
      batchCollect(tests, categoryName);
      break;
    case "accept":
      batchAccept(tests, categoryName);
      break;
    case "reject": // Sets up the reject dialog for the entire category
      const itemsEligible = tests.filter((t) =>
        ["Pending", "Collected"].includes(t.pivot?.item_status),
      );
      if (!itemsEligible.length) {
        ElMessage.info(
          `No items in "${categoryName}" are eligible for rejection.`,
        );
        return;
      }
      currentlyRejectingCategoryName.value = categoryName; // Set batch mode category
      itemsToBatchReject.value = itemsEligible; // Store eligible items
      currentlyRejectingItem.value = null; // Ensure individual mode is off
      rejectForm.reason = ""; // Clear previous reason
      rejectDialogVisible.value = true; // Open the dialog
      // Reset validation state when dialog opens
      nextTick(() => {
        rejectFormRef.value?.resetFields();
      });
      break;
    case "print":
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
      console.log("Reject form validation failed");
      return; // Stop if validation fails
    }

    isConfirmingReject.value = true; // Start dialog confirm button loading
    const reason = rejectForm.reason;
    let categoryNameForLoading = null; // Track category name if batch op for finally block
    let pivotIdForLoading = null; // Track pivotId if individual op for finally block

    try {
      // --- Batch Rejection Mode ---
      if (
        currentlyRejectingCategoryName.value &&
        itemsToBatchReject.value.length > 0
      ) {
        categoryNameForLoading = currentlyRejectingCategoryName.value;
        const itemIds = itemsToBatchReject.value.map((item) => item.pivot.id);

        // Set category loading state
        if (categoryLoading.value[categoryNameForLoading]) {
          categoryLoading.value[categoryNameForLoading].rejecting = true;
        } else {
          // Initialize defensively if somehow missing
          categoryLoading.value[categoryNameForLoading] = { rejecting: true };
        }

        console.log(
          `BATCH Rejecting IDs: ${itemIds.join(", ")} in category ${categoryNameForLoading}`,
        );
        // Call the batch reject API endpoint
        const response = await api.post("/phlebotomy/requests/reject-batch", {
          item_ids: itemIds,
          reason: reason,
        });
        console.log("Batch reject response:", response.data);

        // Process results from the batch operation
        const successCount = response.data.results?.success?.length || 0;
        const failCount = response.data.results?.failed?.length || 0;
        if (successCount > 0)
          ElMessage.success(
            `${successCount} item(s) rejected successfully in ${categoryNameForLoading}.`,
          );
        if (failCount > 0)
          ElMessage.warning(
            `${failCount} item(s) in ${categoryNameForLoading} could not be rejected.`,
          );
        if (successCount === 0 && failCount === 0)
          ElMessage.info("No items were affected by the batch rejection.");

        // Update local state with successfully updated items from the response
        if (response.data.updatedItems) {
          updateLocalItemsState(response.data.updatedItems);
        }

        // --- Individual Rejection Mode ---
      } else if (currentlyRejectingItem.value?.pivot?.id) {
        const item = currentlyRejectingItem.value;
        pivotIdForLoading = item.pivot.id;
        itemLoading.value[pivotIdForLoading] = {
          ...itemLoading.value[pivotIdForLoading],
          rejecting: true,
        };

        console.log(`INDIVIDUAL Rejecting ID: ${pivotIdForLoading}`);
        const response = await api.post(
          `/phlebotomy/requests/reject/${pivotIdForLoading}`,
          { reason: reason },
        );
        console.log("Individual reject response:", response.data);

        ElMessage.success(`Rejected: ${item.name}`);
        // Update local state for the single item
        if (response.data.item) {
          updateLocalItemsState([response.data.item]);
        }
      } else {
        // This case should not happen if the dialog is opened correctly
        throw new Error(
          "Confirm reject called in an invalid state (no individual item or batch category).",
        );
      }

      handleRejectDialogClose(); // Close the dialog after successful operation
    } catch (error) {
      console.error("Error during rejection process:", error);
      const errorResponse = error.response;
      ElMessage.error(
        errorResponse?.data?.message || "Rejection failed. Please try again.",
      );
      // Keep the dialog open on error to allow user to retry or cancel
    } finally {
      isConfirmingReject.value = false; // Always reset dialog button loading
      // Reset category loading ONLY if it was a batch operation
      if (
        categoryNameForLoading &&
        categoryLoading.value[categoryNameForLoading]
      ) {
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
    ElMessage.warning("Please wait for the operation to complete.");
    return;
  }
  // Reset state variables associated with the dialog
  rejectDialogVisible.value = false;
  currentlyRejectingItem.value = null;
  currentlyRejectingCategoryName.value = null;
  itemsToBatchReject.value = [];
  rejectForm.reason = ""; // Clear the reason input
  // Call done() if it's provided by el-dialog's before-close hook
  if (typeof done === "function") {
    done();
  }
};

const handleCollectSample = async (request) => {
  if (!request || !request.tests) return;

  const eligibleTests = request.tests.filter(
    (t) => t.pivot?.item_status === "Pending",
  );

  if (eligibleTests.length === 0) {
    ElMessage.info("No pending tests to collect for this request.");
    return;
  }

  ElMessage.info(`Attempting to collect ${eligibleTests.length} sample(s)...`);

  // Execute collections in parallel and wait for all to complete
  await Promise.all(eligibleTests.map((test) => collectTestItem(test)));

  ElMessage.success("Sample collection process initiated.");
};

const handlePrintLabel = (request) => {
  if (!request || !request.tests) return;

  // Safety check: at least one test must have a valid pivot ID
  const eligibleTests = request.tests.filter((t) => t.pivot?.id);
  if (eligibleTests.length === 0) {
    ElMessage.info("No valid tests to print labels for this request.");
    return;
  }

  // Labels are printed PER SECTION (test category) - not per test
  const grouped = groupTestsByCategory(eligibleTests);
  const sections = Object.keys(grouped).map((name, index) => ({
    name,
    number: index + 1,
  }));

  ElMessage.info(`Preparing ${sections.length} section label(s)...`);

  const labelsHtml = buildSectionLabelsHtml(request, sections);
  if (!labelsHtml) return;

  if (
    openLabelPrintWindow(
      labelsHtml,
      `Labels - ${request.sample_id || request.id}`,
    )
  ) {
    ElMessage.success(
      `Printing ${sections.length} label(s) - one per section.`,
    );
  }
};

const handleAcceptSample = async (request) => {
  if (!request || !request.tests) return;

  const eligibleTests = request.tests.filter(
    (t) => t.pivot?.item_status === "Collected",
  );

  if (eligibleTests.length === 0) {
    ElMessage.info("No collected tests to accept for this request.");
    return;
  }

  ElMessage.info(`Attempting to accept ${eligibleTests.length} sample(s)...`);

  // Execute acceptances in parallel and wait for all to complete
  await Promise.all(eligibleTests.map((test) => acceptTestItem(test)));

  ElMessage.success("Sample acceptance process initiated.");
};

// --- Batch Action Implementations ---
// These functions handle the API calls and state updates for batch operations

const batchCollect = async (tests, categoryName) => {
  const itemsToCollect = tests.filter(
    (t) => t.pivot?.item_status === "Pending",
  );
  if (!itemsToCollect.length) {
    ElMessage.info(`No Pending items in "${categoryName}" to collect.`);
    return;
  }
  const itemIds = itemsToCollect.map((t) => t.pivot.id);

  // Set category loading state
  if (!categoryLoading.value[categoryName])
    categoryLoading.value[categoryName] = {}; // Initialize if needed
  categoryLoading.value[categoryName].collecting = true;

  console.log(`BATCH Collecting IDs: ${itemIds.join(", ")} in ${categoryName}`);
  try {
    // Use POST as defined in routes
    const response = await api.post("/phlebotomy/requests/collect-batch", {
      item_ids: itemIds,
    });
    const successCount = response.data.results?.success?.length || 0;
    const failCount = response.data.results?.failed?.length || 0;
    if (successCount > 0)
      ElMessage.success(`${successCount} item(s) collected successfully.`);
    if (failCount > 0)
      ElMessage.warning(`${failCount} item(s) could not be collected.`);
    if (response.data.updatedItems)
      updateLocalItemsState(response.data.updatedItems);
  } catch (error) {
    console.error("Error during batch collect:", error);
    ElMessage.error(
      error.response?.data?.message || "Batch collection failed.",
    );
  } finally {
    if (categoryLoading.value[categoryName])
      categoryLoading.value[categoryName].collecting = false;
  }
};

const batchAccept = async (tests, categoryName) => {
  const itemsToAccept = tests.filter(
    (t) => t.pivot?.item_status === "Collected",
  );
  if (!itemsToAccept.length) {
    ElMessage.info(`No Collected items in "${categoryName}" to accept.`);
    return;
  }
  const itemIds = itemsToAccept.map((t) => t.pivot.id);

  if (!categoryLoading.value[categoryName])
    categoryLoading.value[categoryName] = {};
  categoryLoading.value[categoryName].accepting = true;

  console.log(`BATCH Accepting IDs: ${itemIds.join(", ")} in ${categoryName}`);
  try {
    // Use POST as defined in routes
    const response = await api.post("/phlebotomy/requests/accept-batch", {
      item_ids: itemIds,
    });
    const successCount = response.data.results?.success?.length || 0;
    const failCount = response.data.results?.failed?.length || 0;
    if (successCount > 0)
      ElMessage.success(`${successCount} item(s) accepted successfully.`);
    if (failCount > 0)
      ElMessage.warning(`${failCount} item(s) could not be accepted.`);
    if (response.data.updatedItems)
      updateLocalItemsState(response.data.updatedItems);
  } catch (e) {
    ElMessage.error(e.response?.data?.message || "Batch accept failed.");
  } finally {
    if (categoryLoading.value[categoryName])
      categoryLoading.value[categoryName].accepting = false;
  }
};

// Batch Print - prints ONE label for the given section
// (labels are per section, not per test)
const printCategoryBarcodes = (tests, categoryName) => {
  if (!selectedRequest.value) {
    ElMessage.warning("No request selected.");
    return;
  }
  if (!tests || tests.length === 0) {
    ElMessage.info(`No items in "${categoryName}" to print.`);
    return;
  }

  // Set category loading state
  if (!categoryLoading.value[categoryName])
    categoryLoading.value[categoryName] = {}; // Initialize if needed
  categoryLoading.value[categoryName].printing = true;

  try {
    const sectionNames = Object.keys(
      groupTestsByCategory(selectedRequest.value.tests),
    );
    const sectionIndex = sectionNames.indexOf(categoryName);
    const sectionNumber = sectionIndex === -1 ? 1 : sectionIndex + 1;

    const labelsHtml = buildSectionLabelsHtml(selectedRequest.value, [
      { name: categoryName, number: sectionNumber },
    ]);
    if (!labelsHtml) return;

    openLabelPrintWindow(labelsHtml, `Label - ${categoryName}`);
  } finally {
    if (categoryLoading.value[categoryName])
      categoryLoading.value[categoryName].printing = false;
  }
};

// --- Computed Properties & Helpers ---

// Helper function to group tests by category name
const groupTestsByCategory = (tests = []) => {
  return tests.reduce((groups, test) => {
    const categoryName = test.test_category?.name || "Uncategorized"; // Use category name, fallback
    if (!groups[categoryName]) groups[categoryName] = [];
    groups[categoryName].push(test);
    return groups;
  }, {});
};

// Computed property to get grouped tests for the selected request
const groupedSelectedTests = computed(() => {
  const groups = groupTestsByCategory(selectedRequest.value?.tests);
  // Ensure loading states exist for displayed categories
  Object.keys(groups).forEach((catName) => {
    if (!categoryLoading.value[catName]) {
      // Initialize if missing (e.g., after fetch/selection change)
      categoryLoading.value[catName] = {
        collecting: false,
        accepting: false,
        printing: false,
        rejecting: false,
      };
    }
  });
  return groups;
});

// --- Visibility/Disabled State Helpers ---
// These determine if buttons should be shown or enabled based on item status

// Individual button checks
const canCollect = (item) => item?.pivot?.item_status === "Pending";
const canAccept = (item) => item?.pivot?.item_status === "Collected";
const canReject = (item) =>
  ["Pending", "Collected"].includes(item?.pivot?.item_status);
const canPrintBarcode = (item) => true; // Always allow printing label regardless of status

// Batch button checks (based on the array of tests in the category)
const canCollectCategory = (tests) => tests.some(canCollect);
const canAcceptCategory = (tests) => tests.some(canAccept);
const canPrintCategory = (tests) => tests.some(canPrintBarcode);
const canRejectCategory = (tests) => tests.some(canReject);

// --- Lifecycle Hooks ---
onMounted(() => {
  fetchRequests(); // Fetch initial requests
  fetchPatientTypes(); // Fetch patient types for the filter
});
</script>

<style scoped>
.request-list {
  background-color: var(--color-bg-primary);
  border-radius: var(--border-radius-md);
  padding: var(--spacing-lg);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border-secondary);
  display: flex;
  flex-direction: column;
  flex: 1 1 auto; /* Allow request-list to take full available width */
  overflow: hidden; /* Enable scrolling within the panel */
  min-width: 0; /* Prevent flex overflow */
}

/* Patient Header Styles */
.patient-info-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.patient-name-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.patient-name {
  font-weight: 600;
  color: var(--color-text-primary);
  font-size: 13px;
}

.sample-id-cell {
  font-weight: 600;
  color: var(--color-text-primary);
  font-size: 13px;
}

.priority-badge {
  flex-shrink: 0;
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

.request-cards {
  flex-grow: 1; /* Allow card list to take available space */
  overflow-y: auto; /* Enable scrolling for cards */
  margin: 0 calc(-1 * var(--spacing-xs)); /* Counteract card margin */
  padding: 0 var(--spacing-xs);
}

.request-card {
  margin-bottom: var(--spacing-sm);
  cursor: pointer;
  transition: all var(--transition-slow) cubic-bezier(0.4, 0, 0.2, 2);
}

.request-card:hover {
  border-color: var(--color-primary-light);
  box-shadow: var(--shadow-md);
  transform: translateY(4px);
}

.request-card.selected {
  border-color: var(--color-primary);
  background-color: var(--color-primary-light);
}

.request-card.selected .sample-id {
  color: var(--color-primary);
}

.request-card.selected .patient-info h3 {
  color: var(--color-primary);
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-bold);
}

.request-card.selected .patient-info .patient-id {
  color: var(--color-primary);
}

.request-card .request-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-sm);
}

.sample-id {
  font-weight: var(--font-weight-semibold);
  color: var(--color-primary);
}

.patient-info h3 {
  margin: 0 0 var(--spacing-xs) 0;
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-medium);
}

.patient-info p {
  margin: 0 0 var(--spacing-2xs, 2px) 0;
  font-size: var(--font-size-sm);
  color: var(--color-text-secondary);
}
.patient-info .patient-id {
  font-weight: var(--font-weight-medium);
}
.patient-info .separator {
  margin: 0 var(--spacing-xs);
  color: var(--color-border-primary);
}

.request-footer {
  margin-top: var(--spacing-sm);
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
  display: flex;
  justify-content: space-between;
}

.details-header .header-info {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
}
.details-header h2 {
  margin: 0;
  font-size: var(--font-size-2xl);
}

.header-info h2 {
  margin: 0;
  font-size: var(--font-size-xl);
  color: var(--color-text-primary);
}

.grouped-tests-section {
  flex-grow: 1; /* Allow collapse section to fill space */
  overflow-y: auto; /* Enable scrolling within the details pane */
  margin: 0 calc(-1 * var(--spacing-lg)); /* Counteract parent padding */
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
/* Style the arrow position */
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
  gap: var(--spacing-xs); /* Space between batch buttons */
}
.category-actions .el-button {
  /* Ensure consistent size */
  padding: 0;
  margin: 0;
}

.category-table {
  margin: 0;
}

.status-cell {
  margin-top: 4px;
}
/* Test Item Card Styles */
.tests-cards-container {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  padding: var(--spacing-md) 0;
}

.test-item-card {
  margin: 0;
  border: 1px solid var(--color-border-secondary);
  border-radius: var(--border-radius-sm);
  transition: all var(--transition-base) ease;
}

.test-item-card:hover {
  border-color: var(--color-primary-light);
  box-shadow: var(--shadow-sm);
}

.test-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: var(--spacing-md);
}

.test-card-left {
  display: flex;
  flex-direction: row;
  gap: var(--spacing-sm);
  flex: 1;
}

.test-name {
  margin: 0;
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-medium);
  color: var(--color-text-primary);
}

.rejection-info {
  background-color: var(--color-danger-light);
  border-left: 3px solid var(--color-danger);
  padding: var(--spacing-sm) var(--spacing-md);
  margin-bottom: var(--spacing-md);
  border-radius: var(--border-radius-xs);
}

.rejection-info p {
  margin: 0;
  font-size: var(--font-size-sm);
  color: var(--color-text-secondary);
}

.test-card-actions {
  display: flex;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
  justify-content: flex-start;
}

/* Dropdown Danger Item Styling */
:deep(.el-dropdown-menu__item.dropdown-danger-item) {
  color: var(--color-danger) !important;
}
:deep(.el-dropdown-menu__item.dropdown-danger-item:hover) {
  background-color: var(--color-danger-light) !important;
  color: var(--color-danger) !important;
}
:deep(.el-dropdown-menu__item.dropdown-danger-item .el-icon) {
  color: var(--color-danger);
}

/* Style Reject Dialog */
.el-dialog__title {
  font-weight: var(--font-weight-medium);
}
.el-dialog__body p {
  margin-bottom: var(--spacing-md);
  color: var(--color-text-secondary);
}

/* Deep selectors for element plus adjustments if needed */
:deep(.el-badge__content) {
  font-size: 12px;
  height: 18px;
  line-height: 18px;
  padding: 0 6px;
  border: none;
}

.info-card {
  background-color: var(--color-bg-primary);
  border: 1px solid var(--color-border-secondary);
  border-radius: var(--border-radius-sm);
  padding: var(--spacing-md);
  margin-bottom: var(--spacing-md);
}
</style>
