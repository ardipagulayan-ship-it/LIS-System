<template>
  <div class="page-container">
    
    <!-- Quick Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-card__header">
          <span class="stat-card__title">Pending Requests</span>
          <span class="badge badge--warning">{{ stats.pending }}</span>
        </div>
        <div class="stat-card__content">
          <el-icon class="stat-icon"><Clock /></el-icon>
          <span>Awaiting Processing</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-card__header">
          <span class="stat-card__title">In Progress</span>
          <span class="badge badge--primary">{{ stats.in_progress }}</span>
        </div>
        <div class="stat-card__content">
          <el-icon class="stat-icon"><Loading /></el-icon>
          <span>Being Processed</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-card__header">
          <span class="stat-card__title">Completed Today</span>
          <span class="badge badge--success">{{ stats.completed_today }}</span>
        </div>
        <div class="stat-card__content">
          <el-icon class="stat-icon"><Check /></el-icon>
          <span>Results Ready</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-card__header">
          <span class="stat-card__title">Total Patients</span>
          <span class="badge badge--info">{{ stats.total_patients }}</span>
        </div>
        <div class="stat-card__content">
          <el-icon class="stat-icon"><User /></el-icon>
          <span>Registered Patients</span>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="card">


      <div class="card__body">
        <!-- Recent Requests Tab -->
        <template v-if="activeTab === 'requests'" class="table-wrapper">
                      <div class="filter-section">
              <div class="filter-row">
                <el-input
                  v-model="requestSearchQuery"
                  placeholder="Search ID, Patient, Doctor..."
                  class="filter-input filter-input--large"
                  clearable
                  @keyup.enter="handleRequestSearchFilter"
                  @clear="handleRequestSearchFilter"
                >
                  <template #prefix>
                    <el-icon><Search /></el-icon>
                  </template>
                </el-input>
                <el-select v-model="requestFilterPriority" placeholder="Priority" clearable @change="handleRequestSearchFilter" class="filter-select">
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
                  class="filter-date"
                />
                <el-button type="primary" @click="handleRequestSearchFilter" class="btn btn-primary">
                  <el-icon><Filter /></el-icon> Filter
                </el-button>
              </div>
            </div>

          <table v-loading="loadingRecentRequest" class="table">
            <thead class="table__head">
              <tr>
                <th class="table__cell table__cell--header">Request ID</th>
                <th class="table__cell table__cell--header">Last Name</th>
                <th class="table__cell table__cell--header">First Name</th>
                <th class="table__cell table__cell--header">Middle Name</th>
                <th class="table__cell table__cell--header">Doctor</th>
                <th class="table__cell table__cell--header">Requested Tests</th>
                <th class="table__cell table__cell--header">Request Date</th>
                <th class="table__cell table__cell--header">Priority</th>
                <th class="table__cell table__cell--header">Actions</th>
              </tr>
            </thead>
            <tbody class="table__body">
              <tr v-for="row in recentRequests" :key="row.id" class="table__row">
                <td class="table__cell">{{ row.id }}</td>
                <td class="table__cell">{{ row.patient?.last_name }}</td>
                <td class="table__cell">{{ row.patient?.first_name }}</td>
                <td class="table__cell">{{ row.patient?.middle_name }}</td>
                <td class="table__cell">{{ row.doctor?.name }}</td>
                <td class="table__cell">
                  <div v-if="row.tests && row.tests.length">
                    <div v-for="(testNames, categoryName) in groupTestsByCategory(row.tests)" :key="categoryName" class="test-group">
                      <strong class="test-group__label">{{ categoryName }}:</strong>
                      <span class="test-group__names">{{ testNames.join(', ') }}</span>
                    </div>
                  </div>
                  <span v-else>N/A</span>
                </td>
                <td class="table__cell">{{ formatDate(row.request_date) }}</td>
                <td class="table__cell">
                  <span :class="['badge', `badge--${getPriorityTagType(row.priority_level)}`]">
                    {{ row.priority_level || 'N/A' }}
                  </span>
                </td>
                <td class="table__cell table__cell--actions">
                  <div class="action-buttons">
                    <el-button type="info" size="small" @click="handleViewTests(row)" class="btn btn-sm" title="View Tests">
                      <el-icon><List /></el-icon>
                    </el-button>
                    <el-button type="primary" size="small" @click="handleViewRequest(row)" class="btn btn-sm" title="View Details">
                      <el-icon><View /></el-icon>
                    </el-button>
                    <el-button type="success" size="small" @click="handleUpdateStatus(row)" :disabled="row.status === 'Completed' || row.status === 'Cancelled'" class="btn btn-sm" title="Update Status">
                      <el-icon><Edit /></el-icon>
                    </el-button>
                    <el-button type="danger" size="small" @click="handleDeleteRequest(row)" class="btn btn-sm" title="Delete Request">
                      <el-icon><Delete /></el-icon>
                    </el-button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div class="pagination-container">
            <el-pagination
              v-model:current-page="requestCurrentPage"
              v-model:page-size="requestPageSize"
              :page-sizes="[10, 20, 50, 100]"
              :total="requestTotal"
              layout="total, sizes, prev, pager, next, jumper"
              @size-change="handleRequestSizeChange"
              @current-change="handleRequestCurrentChange"
            />
          </div>
        </template>

        <!-- Patient Registration Tab -->
        <template v-if="activeTab === 'registration'" class="table-wrapper">
                      <div class="filter-section">
              <div class="filter-row">
                <el-input
                  v-model="patientSearchQuery"
                  placeholder="Search ID, Name, Email, Phone..."
                  class="filter-input filter-input--large"
                  clearable
                  @keyup.enter="handlePatientSearch"
                  @clear="handlePatientSearch"
                >
                  <template #prefix>
                    <el-icon><Search /></el-icon>
                  </template>
                </el-input>
                <el-button type="primary" @click="handlePatientSearch" class="btn btn-primary">
                  <el-icon><Search /></el-icon> Search
                </el-button>
              </div>
            </div>
          <table v-loading="loadingPatients" class="table">
            <thead class="table__head">
              <tr>
                <th class="table__cell table__cell--header">ID</th>
                <th class="table__cell table__cell--header">Last Name</th>
                <th class="table__cell table__cell--header">First Name</th>
                <th class="table__cell table__cell--header">Middle Name</th>
                <th class="table__cell table__cell--header">Email</th>
                <th class="table__cell table__cell--header">Phone</th>
                <th class="table__cell table__cell--header">Gender</th>
                <th class="table__cell table__cell--header">Date of Birth</th>
                <th class="table__cell table__cell--header">Actions</th>
              </tr>
            </thead>
            <tbody class="table__body">
              <tr v-for="row in patients" :key="row.id" class="table__row">
                <td class="table__cell">{{ row.id }}</td>
                <td class="table__cell">{{ row.last_name }}</td>
                <td class="table__cell">{{ row.first_name }}</td>
                <td class="table__cell">{{ row.middle_name }}</td>
                <td class="table__cell">{{ row.email }}</td>
                <td class="table__cell">{{ row.phone }}</td>
                <td class="table__cell">{{ row.gender }}</td>
                <td class="table__cell">{{ formatDate(row.date_of_birth) }}</td>
                <td class="table__cell table__cell--actions">
                  <div class="action-buttons">
                    <el-button type="primary" size="small" @click="handleEditPatient(row)" class="btn btn-sm" title="Edit Patient">
                      <el-icon><Edit /></el-icon>
                    </el-button>
                    <el-button type="success" size="small" @click="handleNewRequestForPatient(row)" class="btn btn-sm" title="Create New Request">
                      <el-icon><DocumentAdd /></el-icon>
                    </el-button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div class="pagination-container">
            <el-pagination
              v-model:current-page="patientCurrentPage"
              v-model:page-size="patientPageSize"
              :page-sizes="[10, 20, 50, 100]"
              :total="patientTotal"
              layout="total, sizes, prev, pager, next, jumper"
              @size-change="handlePatientSizeChange"
              @current-change="handlePatientCurrentChange"
            />
          </div>
        </template>
      </div>
    </div>

    <!-- Patient Modal Dialog -->
    <el-dialog
      v-model="patientDialogVisible"
      :title="patientDialogType === 'add' ? 'New Patient Registration' : 'Edit Patient'"
      width="600px"
      class="patient-dialog"
      :close-on-click-modal="false"
      @close="resetPatientForm"
    >
      <el-form
        ref="patientFormRef"
        :model="patientForm"
        :rules="patientRules"
        class="form-vertical"
      >
        <div class="form-row">
          <div class="form-col">
            <el-form-item label="Last Name" prop="lname">
              <el-input v-model="patientForm.lname" placeholder="Enter patient last name" />
            </el-form-item>
          </div>
          <div class="form-col">
            <el-form-item label="First Name" prop="fname">
              <el-input v-model="patientForm.fname" placeholder="Enter patient first name" />
            </el-form-item>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <el-form-item label="Middle Name" prop="mname">
              <el-input v-model="patientForm.mname" placeholder="Enter patient middle name" />
            </el-form-item>
          </div>
          <div class="form-col">
            <el-form-item label="Gender" prop="gender">
              <el-select v-model="patientForm.gender" placeholder="Select gender">
                <el-option label="Male" value="Male" />
                <el-option label="Female" value="Female" />
                <el-option label="Other" value="Other" />
              </el-select>
            </el-form-item>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <el-form-item label="Email" prop="email">
              <el-input v-model="patientForm.email" placeholder="Enter email address" />
            </el-form-item>
          </div>
          <div class="form-col">
            <el-form-item label="Phone" prop="phone">
              <el-input v-model="patientForm.phone" placeholder="Enter phone number" />
            </el-form-item>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <el-form-item label="Date of Birth" prop="date_of_birth">
              <el-date-picker
                v-model="patientForm.date_of_birth"
                type="date"
                placeholder="Select date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                style="width: 100%;"
              />
            </el-form-item>
          </div>
          <div class="form-col">
            <el-form-item label="Civil Status" prop="civil_status">
              <el-select v-model="patientForm.civil_status" placeholder="Select civil status">
                <el-option label="Single" value="Single" />
                <el-option label="Married" value="Married" />
                <el-option label="Divorced" value="Divorced" />
                <el-option label="Widowed" value="Widowed" />
              </el-select>
            </el-form-item>
          </div>
        </div>

        <el-form-item label="Address" prop="address">
          <el-input v-model="patientForm.address" type="textarea" placeholder="Enter address" rows="3" />
        </el-form-item>
      </el-form>

      <template #footer>
        <div class="modal-footer">
          <el-button @click="patientDialogVisible = false" class="btn btn-secondary">Cancel</el-button>
          <el-button type="primary" @click="submitPatientForm" :loading="submittingPatient" class="btn btn-primary">
            {{ patientDialogType === 'add' ? 'Register' : 'Update' }}
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- Test Details Modal -->
    <el-dialog
      v-model="testDetailsModalVisible"
      :title="`Test Details for Request #${selectedRequest ? selectedRequest.id : ''}`"
      width="700px"
      class="test-details-dialog"
      :close-on-click-modal="false"
      @close="clearSelectedRequestData"
    >
      <table class="table table--compact">
        <thead class="table__head">
          <tr>
            <th class="table__cell table__cell--header">Test Name</th>
            <th class="table__cell table__cell--header">Category</th>
            <th class="table__cell table__cell--header">Status</th>
            <th class="table__cell table__cell--header">Rejection Reason</th>
            <th class="table__cell table__cell--header">Price</th>
          </tr>
        </thead>
        <tbody class="table__body">
          <tr v-for="row in selectedRequestTests" :key="row.id" class="table__row">
            <td class="table__cell">{{ row.name }}</td>
            <td class="table__cell">{{ row.test_category?.name || 'N/A' }}</td>
            <td class="table__cell">
              <span :class="['badge', `badge--${getStatusType(row.pivot?.item_status)}`]">
                {{ row.pivot?.item_status || 'N/A' }}
              </span>
            </td>
            <td class="table__cell">{{ row.pivot?.rejection_reason }}</td>
            <td class="table__cell">{{ formatCurrency(row.pivot?.price_at_request) }}</td>
          </tr>
        </tbody>
      </table>

      <template #footer>
        <div class="modal-footer">
          <el-button @click="testDetailsModalVisible = false" class="btn btn-secondary">Close</el-button>
        </div>
      </template>
    </el-dialog>
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

    <NewConsultationModal v-model="showNewConsultation" @created="handleConsultationCreated" />

</div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
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
  UserFilled
} from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import api from '@/utils/axios';
import { format, parseISO } from 'date-fns';
import NewRequestModal from '@/components/modals/request/NewRequestModal.vue';
import PatientSearchDialog from '@/components/modals/patient/PatientSearchDialog.vue';
import NewConsultationModal from '@/components/doctors-corner/NewConsultationModal.vue';

// Loading States
const loadingStats = ref(false);
const loadingRecentRequest = ref(false);
const loadingPatients = ref(false);
const submittingPatient = ref(false);

// Stats Data
const stats = ref({
  pending: 0,
  in_progress: 0,
  completed_today: 0,
  total_patients: 0
});

// Tabs
const activeTab = ref('requests');

// Requests Data & State
const recentRequests = ref([]);
const requestSearchQuery = ref('');
const requestFilterPriority = ref('');
const requestDateRange = ref([]);
const requestCurrentPage = ref(1);
const requestPageSize = ref(10);
const requestTotal = ref(0);

// Patients Data & State
const patients = ref([]);
const patientSearchQuery = ref('');
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
const patientDialogType = ref('add');
const patientForm = ref({
  id: null,
  last_name: '',
  first_name: '',
  middle_name: '',
  email: '',
  phone: '',
  gender: '',
  date_of_birth: '',
  address: '',
  civil_status: '',
});

// Selected Items for Actions
const selectedRequest = ref(null);
const selectedPatient = ref(null);
const selectedRequestTests = ref([]);

// Fetch Dashboard Stats (Updated)
const fetchStats = async () => {
  loadingStats.value = true;
  try {
    // Call the new backend endpoint
    const response = await api.get('/dashboard/frontdesk-stats');
    // Update stats ref with the response data
    stats.value = response.data;
  } catch (error) {
    ElMessage.error('Error fetching statistics');
    console.error('Error fetching stats:', error);
    // Reset stats on error
    stats.value = { pending: 0, in_progress: 0, completed_today: 0, total_patients: 0 };
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
      start_date: requestDateRange.value?.[0] ? format(new Date(requestDateRange.value[0]), 'yyyy-MM-dd') : '',
      end_date: requestDateRange.value?.[1] ? format(new Date(requestDateRange.value[1]), 'yyyy-MM-dd') : '',
    };
    // Remove empty params
    Object.keys(params).forEach(key => (params[key] === '' || params[key] === null || params[key] === undefined) && delete params[key]);

    const response = await api.get('/laboratory-requests', { params });
    recentRequests.value = response.data.data;
    requestTotal.value = response.data.total;
  } catch (error) {
    ElMessage.error('Error fetching recent requests');
    console.error('Error fetching requests:', error);
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
    Object.keys(params).forEach(key => (params[key] === '' || params[key] === null) && delete params[key]);

    const response = await api.get('/patients', { params });
    patients.value = response.data.data;
    patientTotal.value = response.data.total;
  } catch (error) {
    ElMessage.error('Error fetching patients');
    console.error('Error fetching patients:', error);
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
  if (activeTab.value === 'requests' && recentRequests.value.length === 0) {
    fetchRequests();
  } else if (activeTab.value === 'registration' && patients.value.length === 0) {
    fetchPatients();
  }
};

// Request Actions
const handleViewRequest = (row) => {
  console.log('View Request:', row);
  ElMessageBox.alert(`Details for Request ID: ${row.id}\nPatient: ${row.patient.name}\nStatus: ${row.status}`, 'Request Details');
};

const handleViewTests = (row) => {
  if (row.tests && row.tests.length > 0) {
    selectedRequest.value = row;
    selectedRequestTests.value = row.tests;
    testDetailsModalVisible.value = true;
  } else {
    ElMessage.info('This request has no tests associated with it.');
  }
};

const handleUpdateStatus = async (row) => {
  try {
    const { value } = await ElMessageBox.prompt(
      'Select new status for Request ID: ' + row.id,
      'Update Status',
      {
        confirmButtonText: 'Update',
        cancelButtonText: 'Cancel',
        inputType: 'select',
        inputValue: row.status,
        inputPlaceholder: 'Choose a status',
        inputValidator: (value) => value ? true : 'Please select a status',
        inputOptions: [
          { value: 'Pending', label: 'Pending' },
          { value: 'In Progress', label: 'In Progress' },
          { value: 'Completed', label: 'Completed' },
          { value: 'Cancelled', label: 'Cancelled' }
        ]
      }
    );

    if (value && value !== row.status) {
      await api.put(`/laboratory-requests/${row.id}`, { status: value });
      ElMessage.success(`Status updated to ${value}`);
      fetchRequests();
      fetchStats();
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('Failed to update status');
      console.error('Status update error:', error);
    } else {
      ElMessage.info('Update cancelled');
    }
  }
};

const handleDeleteRequest = async (row) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete Request ID: ${row.id} for patient ${row.patient.name}? This action cannot be undone.`,
      'Confirm Deletion',
      {
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }
    );
    await api.delete(`/laboratory-requests/${row.id}`);
    ElMessage.success('Request deleted successfully');
    fetchRequests();
    fetchStats();
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('Failed to delete request');
      console.error('Delete request error:', error);
    } else {
      ElMessage.info('Deletion cancelled');
    }
  }
};

// Patient Actions
const handleNewPatient = () => {
  patientDialogType.value = 'add';
  resetPatientForm();
  patientDialogVisible.value = true;
};

const handleEditPatient = (row) => {
  patientDialogType.value = 'edit';
  const formattedData = {
      ...row,
      date_of_birth: row.date_of_birth ? format(parseISO(row.date_of_birth), 'yyyy-MM-dd') : null
  };
  patientForm.value = formattedData;
  patientDialogVisible.value = true;
};

const submitPatientForm = async () => {
  if (!patientFormRef.value) return;
  await patientFormRef.value.validate(async (valid) => {
    if (valid) {
      submittingPatient.value = true;
      try {
        if (patientDialogType.value === 'add') {
          await api.post('/patients', patientForm.value);
          ElMessage.success('Patient registered successfully');
        } else {
          await api.put(`/patients/${patientForm.value.id}`, patientForm.value);
          ElMessage.success('Patient updated successfully');
        }
        patientDialogVisible.value = false;
        fetchPatients();
        fetchStats();
      } catch (error) {
        ElMessage.error('Error saving patient');
        console.error('Patient save error:', error);
      } finally {
        submittingPatient.value = false;
      }
    }
  });
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
    last_name: '',
    first_name: '',
    middle_name: '',
    email: '',
    phone: '',
    gender: '',
    date_of_birth: '',
    address: '',
    civil_status: '',
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
  if (!dateString) return '';
  try {
    return format(new Date(dateString), 'MMM dd, yyyy');
  } catch (e) {
    return dateString;
  }
};

const formatCurrency = (value) => {
  const num = parseFloat(value);
  if (isNaN(num)) return '';
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(num);
};

const getPriorityTagType = (priority) => {
  const priorityMap = {
    'Routine': 'primary',
    'Urgent': 'warning',
    'STAT': 'danger'
  };
  return priorityMap[priority] || 'info';
};

// Re-add function for individual test item status tags in the modal
const getStatusType = (status) => {
  const statusMap = {
    'Pending': 'warning',       // Or maybe 'info'
    'Collected': 'primary',
    'Accepted': 'success',      // Example statuses
    'In Progress': 'primary', 
    'Completed': 'success',
    'Cancelled': 'danger',
    'Rejected': 'danger'
    // Add other relevant item statuses here
  };
  return statusMap[status] || 'info'; // Default to info for unknown statuses
};

// Renamed and modified function to group test *names* by category
const groupTestsByCategory = (tests) => {
  if (!tests || !tests.length) {
    return {};
  }
  const groups = {};
  tests.forEach(test => {
    const categoryName = test.test_category?.name || 'Uncategorized';
    const testName = test.name || 'Unknown Test'; // Get the test name

    if (!groups[categoryName]) {
      groups[categoryName] = []; // Initialize as an array for test names
    }
    groups[categoryName].push(testName); // Add the test name to the array
  });
  return groups;
};

// New Consultation Actions
const handleConsultationCreated = (consultation) => {
  showNewConsultation.value = false;
  // Optionally refresh consultations or show a message
};

// Lifecycle Hooks
onMounted(() => {
  fetchRequests();
  fetchPatients();
  fetchStats();
});
</script>

<style scoped>
.page-container {
  width: 100%;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-lg) var(--spacing-md);
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  margin-bottom: 0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.page-title {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -0.5px;
}

.header-actions {
  display: flex;
  gap: var(--spacing-lg);
}

.page-content {
  flex: 1;
  padding: var(--spacing-lg) var(--spacing-md);
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: var(--spacing-xl);
  margin-bottom: var(--spacing-3xl);
}

.stat-card {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 12px;
  padding: var(--spacing-md);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
  transition: height 0.3s ease;
}

.stat-card:nth-child(2)::before {
  background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}

.stat-card:nth-child(3)::before {
  background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
}

.stat-card:nth-child(4)::before {
  background: linear-gradient(90deg, #8b5cf6 0%, #7c3aed 100%);
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
  border-color: rgba(0, 0, 0, 0.12);
}

.stat-card:hover::before {
  height: 5px;
}

.stat-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: var(--spacing-lg);
}

.stat-card__title {
  font-size: var(--font-size-sm);
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-card__content {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  color: #1f2937;
  font-size: var(--font-size-lg);
  font-weight: 700;
}

.stat-icon {
  font-size: 2rem;
  color: #3b82f6;
  opacity: 0.7;
}

.stat-card:nth-child(2) .stat-icon {
  color: #10b981;
}

.stat-card:nth-child(3) .stat-icon {
  color: #f59e0b;
}

.stat-card:nth-child(4) .stat-icon {
  color: #8b5cf6;
}

/* Card Styles */
.card {
  background-color: #ffffff;
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  overflow: hidden;
  margin-bottom: var(--spacing-xl);
  transition: box-shadow 0.3s ease;
}

.card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.card__header {
  padding: var(--spacing-md) var(--spacing-lg);
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
}

.card__body {
  padding: var(--spacing-md) var(--spacing-lg);
}

/* Tabs */
.tabs :deep(.el-tabs__header) {
  margin-bottom: 0;
  border-bottom: none;
}

.tabs :deep(.el-tabs__nav) {
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.tabs :deep(.el-tabs__item) {
  font-weight: 500;
  color: #6b7280;
  border-bottom: 2px solid transparent;
  transition: all 0.3s ease;
}

.tabs :deep(.el-tabs__item.is-active) {
  color: #2563eb;
  border-bottom-color: #2563eb;
}

.tabs :deep(.el-tabs__content) {
  padding: 0;
}

/* Filter Section */
.filter-section {
  padding: var(--spacing-md);
  background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.filter-row {
  display: flex;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
  align-items: center;
}

.filter-input {
  flex: 1;
  min-width: 200px;
}

.filter-input--large {
  min-width: 220px;
}

.filter-select {
  min-width: 140px;
}

.filter-date {
  min-width: 200px;
}

/* Table Wrapper */
.table-wrapper {
  overflow-x: auto;
  padding: var(--spacing-xl);
}

/* Table Styles */
.table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--font-size-sm);
}

.table__head {
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
  border-bottom: 2px solid rgba(0, 0, 0, 0.1);
}

.table__row {
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  transition: background-color 0.2s ease;
}

.table__row:hover {
  background-color: #f9fafb;
}

.table__cell {
  padding: var(--spacing-md) var(--spacing-lg);
  text-align: left;
  color: #374151;
}

.table__cell--header {
  font-weight: 700;
  color: #111827;
  text-transform: uppercase;
  font-size: var(--font-size-xs);
  letter-spacing: 0.75px;
  white-space: nowrap;
  padding: var(--spacing-lg) var(--spacing-xl);
}

.table__cell--actions {
  text-align: center;
}

.table--compact :deep(.table__cell) {
  padding: var(--spacing-md) var(--spacing-lg);
}

/* Test Groups */
.test-group {
  display: flex;
  flex-direction: column;
  margin-bottom: var(--spacing-sm);
}

.test-group__label {
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
  margin-bottom: var(--spacing-xs);
}

.test-group__names {
  font-size: var(--font-size-xs);
  color: var(--color-text-secondary);
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
  padding: 4px 10px;
  border-radius: 6px;
  font-size: var(--font-size-xs);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  white-space: nowrap;
}

.badge--success {
  background-color: #d1fae5;
  color: #065f46;
}

.badge--warning {
  background-color: #fef3c7;
  color: #92400e;
}

.badge--danger {
  background-color: #fee2e2;
  color: #991b1b;
}

.badge--primary {
  background-color: #dbeafe;
  color: #1e40af;
}

.badge--info {
  background-color: #e0e7ff;
  color: #3730a3;
}

/* Pagination */
.pagination-container {
  display: flex;
  justify-content: flex-end;
  padding: var(--spacing-md) var(--spacing-lg);
  border-top: 1px solid rgba(0, 0, 0, 0.06);
  margin-top: var(--spacing-lg);
  background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
}

/* Form Styles */
.form-vertical {
  width: 100%;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-md);
}

.form-col {
  width: 100%;
}

.form-row .form-col:only-child {
  grid-column: 1 / -1;
}

/* Modal Styles */
.patient-dialog :deep(.el-dialog__header) {
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  padding: var(--spacing-md) var(--spacing-lg);
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
}

.patient-dialog :deep(.el-dialog__body) {
  padding: var(--spacing-md);
}

.test-details-dialog :deep(.el-dialog__header) {
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  padding: var(--spacing-md) var(--spacing-lg);
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
}

.test-details-dialog :deep(.el-dialog__body) {
  padding: var(--spacing-md);
}

/* Modal Footer */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-lg);
  padding: var(--spacing-md) var(--spacing-lg);
  border-top: 1px solid rgba(0, 0, 0, 0.06);
  background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
}


</style>