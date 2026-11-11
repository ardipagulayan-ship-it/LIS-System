<template>
  <div class="content-wrapper">
    <div class="container">
      <!-- Quick Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-card__header">
            <span class="stat-card__title">Pending Requests</span>
            <div class="stat-card__header__badge">
              <span class="badge badge--warning">{{ stats.pending }}</span>
            </div>
          </div>

          <div class="stat-card__content">
            <el-icon class="stat-icon"><Clock /></el-icon>
            <span>Awaiting Processing</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card__header">
            <span class="stat-card__title">In Progress</span>
            <div class="stat-card__header__badge">
              <span class="badge badge--primary">{{ stats.in_progress }}</span>
            </div>
          </div>
          <div class="stat-card__content">
            <el-icon class="stat-icon"><Loading /></el-icon>
            <span>Being Processed</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card__header">
            <span class="stat-card__title">Completed Today</span>
            <div class="stat-card__header__badge">
              <span class="badge badge--success">{{
                stats.completed_today
              }}</span>
            </div>
          </div>
          <div class="stat-card__content">
            <el-icon class="stat-icon"><Check /></el-icon>
            <span>Results Ready</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card__header">
            <span class="stat-card__title">Total Patients</span>
            <div class="stat-card__header__badge">
              <span class="badge badge--info">{{ stats.total_patients }}</span>
            </div>
          </div>
          <div class="stat-card__content">
            <el-icon class="stat-icon"><User /></el-icon>
            <span>Registered Patients</span>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="card-section">
        <div class="triage-tabs-container">
          <div class="triage-tabs">
            <button
              v-for="tab in frontdeskTabs"
              :key="tab.key"
              :class="['triage-tab', { active: activeTab === tab.key }]"
              @click="activeTab = tab.key"
            >
              {{ tab.label }}
            </button>
          </div>
        </div>

        <!-- Recent Requests Tab -->
        <template v-if="activeTab === 'requests'">
          <!-- Request Filters -->
          <div class="filter-action-container">
            <div class="filter-actions">
              <el-input
                v-model="requestSearchQuery"
                placeholder="Search ID, Patient, Doctor..."
                class="search-input"
                clearable
                @keyup.enter="handleRequestSearchFilter"
                @clear="handleRequestSearchFilter"
              >
                <template #prefix>
                  <el-icon><Search /></el-icon>
                </template>
              </el-input>
              <el-select
                class="search-input"
                v-model="requestFilterPriority"
                placeholder="Priority"
                clearable
                @change="handleRequestSearchFilter"
              >
                <el-option label="Routine" value="Routine" />
                <el-option label="Urgent" value="Urgent" />
                <el-option label="STAT" value="STAT" />
              </el-select>
              <el-date-picker
                v-model="requestDateRange"
                type="daterange"
                range-separator="To"
                start-placeholder="Start date"
                end-placeholder="End date"
                @change="handleRequestSearchFilter"
                style="width: 500px"
              />
              <el-button type="secondary" @click="handleRequestSearchFilter">
                <el-icon><Filter /></el-icon> Filter
              </el-button>
            </div>
            <div class="header-actions">
              <el-button
                class="custom-button-primary"
                @click="handleNewRequest"
              >
                <el-icon><DocumentAdd /></el-icon>New Request
              </el-button>
            </div>
          </div>
          <div class="table-section">
            <div class="custom-table" v-loading="loadingRecentRequest">
              <el-table
                v-loading="loadingRecentRequest"
                :data="recentRequests"
                row-key="id"
                highlight-current-row
                height="100%"
                @row-click="selectRequest"
              >
                <!-- Request Table Columns -->
                <el-table-column prop="id" label="Request ID" width="120" />
                <el-table-column label="Sample ID" width="150">
                  <template #default="{ row }">
                    <div class="sample-id-cell">
                      <strong>{{ row.sample_id }}</strong>
                    </div>
                  </template>
                </el-table-column>
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
                <el-table-column
                  prop="doctor.name"
                  label="Doctor"
                  min-width="100"
                />
                <!-- Test Statuses Column -->
                <el-table-column
                  label="Requested Tests"
                  min-width="250"
                  show-overflow-tooltip
                >
                  <template #default="{ row }">
                    <span v-for="(test, index) in row.tests" :key="test.id">
                      {{ test.name
                      }}{{ index < row.tests.length - 1 ? ", " : "" }}
                    </span>
                  </template>
                </el-table-column>
                <el-table-column label="Requested" width="120" align="right">
                  <template #default="{ row }">
                    <span class="date-text">{{
                      formatDateTimeShort(null, null, row.request_date)
                    }}</span>
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
              v-model:current-page="requestCurrentPage"
              v-model:page-size="requestPageSize"
              :page-sizes="[10, 20, 50, 100]"
              :total="requestTotal"
              layout="total, sizes, prev, pager, next, jumper"
              @size-change="handleRequestSizeChange"
              @current-change="handleRequestCurrentChange"
              small
            />
          </div>
        </template>

        <!-- Patient Registration Tab -->
        <template v-if="activeTab === 'patients'">
          <div class="table-section">
            <div class="filter-action-container">
              <div class="filter-actions">
                <el-input
                  v-model="patientSearchQuery"
                  placeholder="Search ID, Name, Email, Phone..."
                  class="search-input"
                  clearable
                  @keyup.enter="handlePatientSearch"
                  @clear="handlePatientSearch"
                >
                  <template #prefix>
                    <el-icon><Search /></el-icon>
                  </template>
                </el-input>
                <el-button type="primary" @click="handlePatientSearch">
                  <el-icon><Search /></el-icon> Search
                </el-button>
              </div>
              <div class="header-actions">
                <el-button
                  class="custom-button-primary"
                  @click="handleNewPatient"
                >
                  <el-icon><Plus /></el-icon>New Patient
                </el-button>
              </div>
            </div>
            <div class="custom-table" v-loading="loadingPatients">
              <el-table
                :data="patients"
                :row-class-name="getRowClassName"
                highlight-current-row
              >
                <!-- Patient Table Columns -->
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column
                  prop="last_name"
                  label="Last Name"
                  min-width="180"
                />
                <el-table-column
                  prop="first_name"
                  label="First Name"
                  min-width="180"
                />
                <el-table-column
                  prop="middle_name"
                  label="Middle Name"
                  min-width="180"
                />
                <el-table-column prop="email" label="Email" min-width="200" />
                <el-table-column prop="phone" label="Phone" width="150" />
                <el-table-column prop="gender" label="Gender" width="100" />
                <el-table-column
                  prop="date_of_birth"
                  label="Date of Birth"
                  width="130"
                >
                  <template #default="{ row }">
                    {{ formatDate(row.date_of_birth) }}
                    <!-- Use date only format -->
                  </template>
                </el-table-column>
                <el-table-column
                  label="Actions"
                  width="150"
                  fixed="right"
                  align="center"
                >
                  <template #default="{ row }">
                    <el-button-group>
                      <el-tooltip content="Edit Patient" placement="top">
                        <el-button
                          type="primary"
                          size="small"
                          @click="handleEditPatient(row)"
                        >
                          <el-icon><Edit /></el-icon>
                        </el-button>
                      </el-tooltip>
                      <el-tooltip content="Create New Request" placement="top">
                        <el-button
                          type="success"
                          size="small"
                          @click="handleNewRequestForPatient(row)"
                        >
                          <el-icon><DocumentAdd /></el-icon>
                        </el-button>
                      </el-tooltip>
                    </el-button-group>
                  </template>
                </el-table-column>
              </el-table>
            </div>
          </div>
          <div class="pagination">
            <el-pagination
              v-model:current-page="patientCurrentPage"
              v-model:page-size="patientPageSize"
              :page-sizes="[10, 20, 50, 100]"
              :total="patientTotal"
              layout="total, sizes, prev, pager, next, jumper"
              @size-change="handlePatientSizeChange"
              @current-change="handlePatientCurrentChange"
              small
            />
          </div>
        </template>
      </div>

      <!-- Right side: Request Details Drawer (opened by clicking a row in the worklist) -->
      <el-drawer
        v-model="drawerVisible"
        :title="`Request Details — ${selectedRequest?.sample_id || ''}`"
        size="750px"
        direction="rtl"
        @close="clearSelectedRequestData"
      >
        <template v-if="selectedRequest">
          <!-- Patient Header -->
          <div class="patient-header">
            <div class="patient-name-row">
              <h2 class="drawer-patient-name">
                {{ selectedRequest.patient?.last_name }},
                {{ selectedRequest.patient?.first_name }}
                {{ selectedRequest.patient?.middle_name }}
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
                >Hospital # {{ selectedRequest.patient?.id || "N/A" }}</span
              >
              <span class="meta-sep">·</span>
              <span class="meta-item">{{
                selectedRequest.patient_type?.name || "OPD"
              }}</span>
              <span class="meta-sep">·</span>
              <span class="meta-item"
                >{{ selectedRequest.ward?.name || "No ward"
                }}{{
                  selectedRequest.room ? ", " + selectedRequest.room.name : ""
                }}</span
              >
              <span class="meta-sep">·</span>
              <span class="meta-item"
                >DOB
                {{ formatDate(selectedRequest.patient?.date_of_birth) }} ({{
                  calculateAge(selectedRequest.patient?.date_of_birth)
                }}
                yrs)</span
              >
              <span class="meta-sep">·</span>
              <span class="meta-item">{{
                selectedRequest.patient?.gender || "N/A"
              }}</span>
              <span class="meta-sep">·</span>
              <span class="meta-item"
                >Requested
                {{ formatDateTime(selectedRequest.request_date) }}</span
              >
            </div>
          </div>

          <!-- Ordered Tests (single table for all ordered tests) -->
          <div class="info-card">
            <div class="card-header">
              <div class="card-header-left">
                <el-icon class="card-icon"><List /></el-icon>
                <span class="card-title">Ordered Tests</span>
              </div>
              <span class="card-count"
                >{{ selectedRequest.tests?.length || 0 }} test(s)</span
              >
            </div>
            <div class="card-body">
              <el-table
                v-if="selectedRequest.tests && selectedRequest.tests.length"
                :data="selectedRequest.tests"
                size="small"
                style="width: 100%; font-size: 12px"
              >
                <el-table-column label="Test Name" min-width="220">
                  <template #default="{ row }">
                    <strong>{{ row.name }}</strong>
                  </template>
                </el-table-column>
                <el-table-column label="Status" width="120" align="center">
                  <template #default="{ row }">
                    <el-tag
                      :type="getStatusType(row.pivot?.item_status)"
                      size="small"
                      >{{ row.pivot?.item_status || "Pending" }}</el-tag
                    >
                  </template>
                </el-table-column>
                <el-table-column label="Details" min-width="180">
                  <template #default="{ row }">
                    <span class="status-detail-text">{{
                      getTestStatusDetail(row)
                    }}</span>
                  </template>
                </el-table-column>
              </el-table>
              <div v-else class="empty-state">
                <el-icon size="40"><List /></el-icon>
                <p>No tests found for this request.</p>
              </div>
            </div>
          </div>
        </template>
        <template #footer>
          <div class="drawer-footer">
            <div class="reject-section">
              <el-button
                class="reject-btn"
                :icon="Delete"
                @click="handleDeleteRequest(selectedRequest)"
                >Delete Request</el-button
              >
            </div>
            <div class="accept-section">
              <el-button
                class="custom-button-secondary"
                :icon="List"
                @click="handleViewTests(selectedRequest)"
                >View Tests</el-button
              >
              <el-button
                class="custom-button-primary"
                :icon="Edit"
                :disabled="
                  !selectedRequest ||
                  selectedRequest.status === 'Completed' ||
                  selectedRequest.status === 'Cancelled'
                "
                @click="handleUpdateStatus(selectedRequest)"
                >Update Status</el-button
              >
            </div>
          </div>
        </template>
      </el-drawer>

      <!-- Dialogs -->
      <PatientModal
        :visible="patientDialogVisible"
        :patient-data="patientForm"
        @close="patientDialogVisible = false"
        @save="handlePatientSaved"
      />

      <NewRequestModal
        v-model="requestDialogVisible"
        :initial-patient="selectedPatient"
        @request-created="handleRequestCreated"
        @search-patient="openPatientSearchDialog"
      />

      <PatientSearchDialog
        v-model="patientSearchDialogVisible"
        @patient-selected="handlePatientSelected"
      />

      <!-- Test Details Modal -->
      <ViewTestsModal
        v-model="testDetailsModalVisible"
        :request="selectedRequest"
        :tests="selectedRequestTests"
      />

      <NewConsultationModal
        v-model="showNewConsultation"
        @created="handleConsultationCreated"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import {
  Plus,
  Edit,
  Delete,
  Search,
  View,
  DocumentAdd,
  Clock,
  Loading,
  Check,
  User,
  Close,
  Filter,
  List,
  UserFilled,
} from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox } from "element-plus";
import api from "@/utils/axios";
import { format, parseISO, differenceInYears } from "date-fns";
import NewRequestModal from "@/components/modals/request/NewRequestModal.vue";
import ViewTestsModal from "@/components/modals/request/ViewTestsModal.vue";
import PatientModal from "@/components/modals/patient/PatientModal.vue";
import PatientSearchDialog from "@/components/modals/patient/PatientSearchDialog.vue";
import NewConsultationModal from "@/components/doctors-corner/NewConsultationModal.vue";

// Loading States
const loadingStats = ref(false);
const loadingRecentRequest = ref(false);
const loadingPatients = ref(false);
const submittingPatient = ref(false);

// Tabs configuration
const frontdeskTabs = [
  { key: "requests", label: "Recent Requests" },
  { key: "patients", label: "Patient Registration" },
];

// Stats Data
const stats = ref({
  pending: 0,
  in_progress: 0,
  completed_today: 0,
  total_patients: 0,
});

// Tabs
const activeTab = ref("requests");

// Requests Data & State
const recentRequests = ref([]);
const requestSearchQuery = ref("");
const requestFilterPriority = ref("");
const requestDateRange = ref([]);
const requestCurrentPage = ref(1);
const requestPageSize = ref(10);
const requestTotal = ref(0);

// Patients Data & State
const patients = ref([]);
const patientSearchQuery = ref("");
const patientCurrentPage = ref(1);
const patientPageSize = ref(10);
const patientTotal = ref(0);

// Dialog Visibility
const patientDialogVisible = ref(false);
const requestDialogVisible = ref(false);
const patientSearchDialogVisible = ref(false);
const testDetailsModalVisible = ref(false);
const showNewConsultation = ref(false);

// Form Refs and Data
const patientFormRef = ref(null);
const patientDialogType = ref("add");
const patientForm = ref({
  id: null,
  last_name: "",
  first_name: "",
  middle_name: "",
  email: "",
  phone: "",
  gender: "",
  date_of_birth: "",
  address: "",
  civil_status: "",
});

const calculateAge = (dob) =>
  dob ? differenceInYears(new Date(), parseISO(dob)) : "N/A";
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

// Helper function to get tab counts
const getTabCount = (tabKey) => {
  if (tabKey === "requests") return recentRequests.value.length;
  if (tabKey === "patients") return patients.value.length;
  return 0;
};

// Selected Items for Actions
const selectedRequest = ref(null);
const selectedPatient = ref(null);
const selectedRequestTests = ref([]);

// Drawer Visibility (drawer layout)
const drawerVisible = ref(false); // Controls the request details drawer

// Fetch Dashboard Stats (Updated)
const fetchStats = async () => {
  loadingStats.value = true;
  try {
    // Call the new backend endpoint
    const response = await api.get("/dashboard/frontdesk-stats");
    // Update stats ref with the response data
    stats.value = response.data;
  } catch (error) {
    ElMessage.error("Error fetching statistics");
    console.error("Error fetching stats:", error);
    // Reset stats on error
    stats.value = {
      pending: 0,
      in_progress: 0,
      completed_today: 0,
      total_patients: 0,
    };
  } finally {
    loadingStats.value = false;
  }
};

// Fetch Recent Requests (Laboratory Requests)
const fetchRequests = async () => {
  loadingRecentRequest.value = true;
  try {
    const params = {
      page: requestCurrentPage.value,
      per_page: requestPageSize.value,
      search: requestSearchQuery.value,
      priority_level: requestFilterPriority.value,
      start_date: requestDateRange.value?.[0]
        ? format(new Date(requestDateRange.value[0]), "yyyy-MM-dd")
        : "",
      end_date: requestDateRange.value?.[1]
        ? format(new Date(requestDateRange.value[1]), "yyyy-MM-dd")
        : "",
    };
    // Remove empty params
    Object.keys(params).forEach(
      (key) =>
        (params[key] === "" ||
          params[key] === null ||
          params[key] === undefined) &&
        delete params[key],
    );

    const response = await api.get("/laboratory-requests", { params });
    recentRequests.value = response.data.data;
    requestTotal.value = response.data.total;
  } catch (error) {
    ElMessage.error("Error fetching recent requests");
    console.error("Error fetching requests:", error);
  } finally {
    loadingRecentRequest.value = false;
  }
};

// Fetch Patients
const fetchPatients = async () => {
  loadingPatients.value = true;
  try {
    const params = {
      page: patientCurrentPage.value,
      per_page: patientPageSize.value,
      search: patientSearchQuery.value,
    };
    // Remove empty params
    Object.keys(params).forEach(
      (key) =>
        (params[key] === "" || params[key] === null) && delete params[key],
    );

    const response = await api.get("/patients", { params });
    patients.value = response.data.data;
    patientTotal.value = response.data.total;
  } catch (error) {
    ElMessage.error("Error fetching patients");
    console.error("Error fetching patients:", error);
  } finally {
    loadingPatients.value = false;
  }
};

// Request Filters
const handleRequestSearchFilter = () => {
  requestCurrentPage.value = 1;
  fetchRequests();
};

// Request Pagination
const handleRequestSizeChange = (val) => {
  requestPageSize.value = val;
  requestCurrentPage.value = 1;
  fetchRequests();
};
const handleRequestCurrentChange = (val) => {
  requestCurrentPage.value = val;
  fetchRequests();
};

// Patient Search
const handlePatientSearch = () => {
  patientCurrentPage.value = 1;
  fetchPatients();
};

// Patient Pagination
const handlePatientSizeChange = (val) => {
  patientPageSize.value = val;
  patientCurrentPage.value = 1;
  fetchPatients();
};
const handlePatientCurrentChange = (val) => {
  patientCurrentPage.value = val;
  fetchPatients();
};

// Tab Switching
const handleTabClick = () => {
  if (activeTab.value === "requests" && recentRequests.value.length === 0) {
    fetchRequests();
  } else if (
    activeTab.value === "registration" &&
    patients.value.length === 0
  ) {
    fetchPatients();
  }
};

// Request Actions
// Opens the request details drawer when a row in the worklist is clicked
const selectRequest = (row) => {
  if (!row) return;
  selectedRequest.value = row;
  selectedRequestTests.value = row.tests || [];
  drawerVisible.value = true; // Open drawer when a row is clicked
};

const handleViewRequest = (row) => {
  // Route the "View Details" action into the request details drawer
  selectRequest(row);
};

const handleViewTests = (row) => {
  if (row.tests && row.tests.length > 0) {
    selectedRequest.value = row;
    selectedRequestTests.value = row.tests;
    testDetailsModalVisible.value = true;
  } else {
    ElMessage.info("This request has no tests associated with it.");
  }
};

const handleUpdateStatus = async (row) => {
  try {
    const { value } = await ElMessageBox.prompt(
      "Select new status for Request ID: " + row.id,
      "Update Status",
      {
        confirmButtonText: "Update",
        cancelButtonText: "Cancel",
        inputType: "select",
        inputValue: row.status,
        inputPlaceholder: "Choose a status",
        inputValidator: (value) => (value ? true : "Please select a status"),
        inputOptions: [
          { value: "Pending", label: "Pending" },
          { value: "In Progress", label: "In Progress" },
          { value: "Completed", label: "Completed" },
          { value: "Cancelled", label: "Cancelled" },
        ],
      },
    );

    if (value && value !== row.status) {
      await api.put(`/laboratory-requests/${row.id}`, { status: value });
      ElMessage.success(`Status updated to ${value}`);
      fetchRequests();
      fetchStats();
    }
  } catch (error) {
    if (error !== "cancel") {
      ElMessage.error("Failed to update status");
      console.error("Status update error:", error);
    } else {
      ElMessage.info("Update cancelled");
    }
  }
};

const handleDeleteRequest = async (row) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete Request ID: ${row.id} for patient ${row.patient.name}? This action cannot be undone.`,
      "Confirm Deletion",
      {
        confirmButtonText: "Delete",
        cancelButtonText: "Cancel",
        type: "warning",
      },
    );
    await api.delete(`/laboratory-requests/${row.id}`);
    ElMessage.success("Request deleted successfully");
    // Close the details drawer since the selected request no longer exists
    drawerVisible.value = false;
    fetchRequests();
    fetchStats();
  } catch (error) {
    if (error !== "cancel") {
      ElMessage.error("Failed to delete request");
      console.error("Delete request error:", error);
    } else {
      ElMessage.info("Deletion cancelled");
    }
  }
};

// Patient Actions
const handleNewPatient = () => {
  patientDialogType.value = "add";
  resetPatientForm();
  patientDialogVisible.value = true;
};

const handleEditPatient = (row) => {
  patientDialogType.value = "edit";
  const formattedData = {
    ...row,
    date_of_birth: row.date_of_birth
      ? format(parseISO(row.date_of_birth), "yyyy-MM-dd")
      : null,
  };
  patientForm.value = formattedData;
  patientDialogVisible.value = true;
};

const handlePatientSaved = () => {
  patientDialogVisible.value = false;
  fetchPatients();
  fetchStats();
};

// Request Form Actions
const handleNewRequest = () => {
  selectedPatient.value = null;
  requestDialogVisible.value = true;
};

const handleNewRequestForPatient = (row) => {
  selectedPatient.value = row;
  requestDialogVisible.value = true;
};

const handleRequestCreated = () => {
  fetchRequests();
  fetchStats();
};

// Patient Search Dialog Actions
const openPatientSearchDialog = () => {
  console.log("Opening patient search dialog...");
  patientSearchDialogVisible.value = true;
};

const handlePatientSelected = (patient) => {
  console.log("Patient selected from search:", patient);
  selectedPatient.value = patient;
  patientSearchDialogVisible.value = false;
};

// Reset Forms
const resetPatientForm = () => {
  patientForm.value = {
    id: null,
    last_name: "",
    first_name: "",
    middle_name: "",
    email: "",
    phone: "",
    gender: "",
    date_of_birth: "",
    address: "",
    civil_status: "",
  };
  if (patientFormRef.value) {
    patientFormRef.value.resetFields();
  }
};

// Added handler to clear data when modal closes
const clearSelectedRequestData = () => {
  selectedRequest.value = null;
  selectedRequestTests.value = [];
};

// Utility Functions
const formatDate = (dateString) => {
  if (!dateString) return "";
  try {
    return format(new Date(dateString), "MMM dd, yyyy");
  } catch (e) {
    return dateString;
  }
};

const formatCurrency = (value) => {
  const num = parseFloat(value);
  if (isNaN(num)) return "";
  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  }).format(num);
};

// Re-add function for individual test item status tags in the modal
const getStatusType = (status) => {
  const statusMap = {
    Pending: "warning", // Or maybe 'info'
    Collected: "primary",
    Accepted: "success", // Example statuses
    "In Progress": "primary",
    Completed: "success",
    Cancelled: "danger",
    Rejected: "danger",
    // Add other relevant item statuses here
  };
  return statusMap[status] || "info"; // Default to info for unknown statuses
};

// Renamed and modified function to group test *names* by category
const groupTestsByCategory = (tests) => {
  if (!tests || !tests.length) {
    return {};
  }
  const groups = {};
  tests.forEach((test) => {
    const categoryName = test.test_category?.name || "Uncategorized";
    const testName = test.name || "Unknown Test"; // Get the test name

    if (!groups[categoryName]) {
      groups[categoryName] = []; // Initialize as an array for test names
    }
    groups[categoryName].push(testName); // Add the test name to the array
  });
  return groups;
};

// Read-only status detail line shown per test item inside the drawer
const getTestStatusDetail = (test) => {
  const pivot = test?.pivot || {};
  if (pivot.rejection_reason) return `Reason: ${pivot.rejection_reason}`;
  if (pivot.accepted_at) return `Accepted ${formatDateTime(pivot.accepted_at)}`;
  if (pivot.collected_at)
    return `Collected ${formatDateTime(pivot.collected_at)}`;
  if (pivot.checkin_at) return `Checked-in ${formatDateTime(pivot.checkin_at)}`;
  if (pivot.sent_out_at) return `Send-out ${formatDateTime(pivot.sent_out_at)}`;
  return "Awaiting processing";
};

// Status tag type for the request level status
const getRequestStatusType = (status) =>
  ({
    Pending: "warning",
    "In Progress": "primary",
    Completed: "success",
    Cancelled: "danger",
  })[status] || "info";

// New Consultation Actions
const handleConsultationCreated = (consultation) => {
  showNewConsultation.value = false;
  // Optionally refresh consultations or show a message
};

// Full date + time formatter used by the details drawer
const formatDateTime = (dateTimeValue) => {
  if (!dateTimeValue) return "N/A";
  try {
    return format(parseISO(dateTimeValue), "MMM dd, yyyy hh:mm a");
  } catch (e) {
    return "Invalid Date";
  }
};

const formatDateTimeShort = (row, column, cellValue) => {
  if (!cellValue) return "N/A";
  try {
    return format(parseISO(cellValue), "MM/dd/yyyy p");
  } catch (e) {
    return "Invalid Date";
  }
};

// Lifecycle Hooks
onMounted(() => {
  fetchRequests();
  fetchPatients();
  fetchStats();
});
</script>

<style scoped>
.frontdesk {
  display: flex;
  height: calc(100vh - 80px); /* Adjust for header/footer if any */
  gap: var(--spacing-lg);
  overflow: hidden; /* Prevent scrolling on the main container */
  color: var(--color-text-primary);
  width: 100%;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-lg);
  color: var(--color-text-primary);
}

.page-header h2 {
  margin: 0;
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-medium);
  color: var(--color-text-primary);
}

/* Stat Card Styling */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: var(--spacing-sm);
}

.stat-card {
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-border-secondary);
  border-radius: var(--border-radius-md);
  padding: var(--spacing-lg);
  box-shadow: var(--shadow-sm);
  transition: all var(--transition-base) cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  transition: height var(--transition-base) ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: var(--color-border-primary);
}

.stat-card:hover::before {
  height: 5px;
}

.stat-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.stat-card__header__badge {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
}

.stat-card__title {
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-card__content {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  color: var(--color-text-secondary);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
}

.stat-icon {
  font-size: 1.5rem;
  color: var(--color-primary);
  opacity: 0.7;
}

.stat-card:nth-child(2) .stat-icon {
  color: var(--color-success);
}

.stat-card:nth-child(3) .stat-icon {
  color: var(--color-warning);
}

.stat-card:nth-child(4) .stat-icon {
  color: var(--color-info);
}
/* End Stat ard */

/* Badges */
/* Badge Styles */
.badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: var(--border-radius-sm);
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
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

.badge--primary {
  background-color: var(--color-primary-light);
  color: var(--color-primary);
}

.badge--info {
  background-color: var(--color-info-light);
  color: var(--color-info);
}
/* end Badges */
.main-content {
  margin-top: var(--spacing-lg);
}

:deep(.el-card__header) {
  padding: var(--spacing-lg) var(--spacing-xl);
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-sm);
}

.request-details {
  padding: var(--spacing-xl);
}

.tests-list {
  margin-top: var(--spacing-xl);
}

.tests-list h3 {
  margin-bottom: var(--spacing-lg);
}

.total-amount {
  margin-top: var(--spacing-xl);
  text-align: right;
}

.notes {
  margin-top: var(--spacing-xl);
}

.notes h3 {
  margin-bottom: var(--spacing-sm);
}

.notes p {
  margin: 0;
  color: var(--color-text-tertiary);
}

.request-dialog {
  display: flex;
  flex-direction: column;
}

.dialog-header {
  padding: var(--spacing-sm) var(--spacing-xl);
  border-bottom: 1px solid var(--color-border-primary);
}

.order-content {
  padding: var(--spacing-xl);
  height: calc(100vh - 120px);
  overflow-y: auto;
}

.section {
  background: var(--color-bg-primary);
  padding: var(--spacing-xl);
  margin-bottom: var(--spacing-xl);
  border-radius: var(--border-radius-md);
  box-shadow: var(--shadow-sm);
}

.section h3 {
  margin-top: 0;
  margin-bottom: var(--spacing-xl);
  font-size: var(--font-size-xl);
  font-weight: var(--font-weight-medium);
}

.age-input {
  display: flex;
  align-items: center;
  gap: 10px;
}

.search-input {
  align-items: center;
  max-width: 250px;
}

.checkbox-group {
  display: flex;
  gap: 20px;
}

.test-selection {
  margin-top: 20px;
}

.order-summary {
  margin-top: 20px;
}

.summary-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

:deep(.el-dialog__header) {
  margin: 0;
  padding: 0;
}

:deep(.el-dialog__body) {
  padding: 0;
}

:deep(.el-tabs__header) {
  margin-bottom: 20px;
}

:deep(.el-form-item:last-child) {
  margin-bottom: 0;
}

.patient-search-dialog {
  .search-header {
    margin-bottom: 20px;
    .search-input {
      width: 100%;
    }
  }

  :deep(.el-dialog__body) {
    padding: 20px;
  }
}
.filter-action-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--color-bg-primary);
  margin-bottom: 10px;
  padding-top: 10px;
  border-top: 1px solid var(--color-border-secondary);
}

.filter-actions {
  display: flex;
  gap: var(--spacing-sm);
}

.header-actions {
  display: flex;
  gap: var(--spacing-sm);
}

.filter-actions .search-input {
  flex-grow: 1;
}

/* Styles for the test status group column */
.status-group {
  margin-bottom: var(--spacing-xs);
  line-height: var(--line-height-normal);
}

.status-group:last-child {
  margin-bottom: 0;
}

.category-name {
  font-weight: var(--font-weight-semibold);
  margin-right: var(--spacing-sm);
  font-size: var(--font-size-sm);
  color: var(--color-text-primary);
}

/* Optional: Style for the list of test names if needed */
.test-names-list {
  font-size: var(--font-size-sm);
  color: var(--color-text-secondary);
}

/* Triage Tabs */
.triage-tabs-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-md);
  gap: var(--spacing-md);
  width: 100%;
  background: var(--color-bg-secondary);
  padding: 5px;
}

.triage-tabs {
  display: flex;
  gap: var(--spacing-sm);
  flex: 1;
}

.triage-tab {
  position: relative;
  padding: var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-secondary);
  color: var(--color-text-secondary);
  border-radius: var(--border-radius-md);
  cursor: pointer;
  font-size: var(--font-size-xs);
  font-weight: 500;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.triage-tab:hover {
  color: var(--color-primary);
}

.triage-tab.active {
  font-weight: 600;
  background-color: var(--color-primary-light); /* Very soft light blue */
  color: var(--color-primary); /* Bold primary blue text */
  border-bottom: 2px solid var(--color-primary); /* Soft blue border */
  border-radius: 0px;
}

.card-section {
  background-color: var(--color-bg-primary);
  border-radius: var(--border-radius-md);
  padding: var(--spacing-md);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border-secondary);
  display: flex;
  flex-direction: column;
  flex: 1 1 auto; /* Allow request-list to take full available width */
  overflow: hidden; /* Enable scrolling within the panel */
  min-width: 0; /* Prevent flex overflow */
}

.patient-info-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.patient-name-row {
  display: flex;
  align-items: center;
  gap: 4px;
}

.patient-name {
  font-weight: 600;
  color: var(--color-text-primary);
  font-size: var(--font-size-xs);
}

.sample-id-cell {
  font-weight: 600;
  color: var(--color-text-primary);
  font-size: var(--font-size-xs);
}

.priority-badge {
  flex-shrink: 0;
}
</style>
