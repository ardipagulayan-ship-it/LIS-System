<template>
  <el-dialog
    v-model="dialogVisible"
    :title="'Microbiology Result Window'"
    :fullscreen="true"
    :show-close="true"
    class="microbiology-modal"
  >
    <!-- Top Action Bar -->
    <div class="action-bar">
      <div class="action-buttons">
        <el-button type="success" @click="handlePreview">
          <el-icon><View /></el-icon>
          Preview (F1)
        </el-button>
        <el-button type="warning" @click="handleSave">
          <el-icon><Document /></el-icon>
          Save (F2)
        </el-button>
        <el-button type="primary" @click="handlePrintRelease">
          <el-icon><Printer /></el-icon>
          Print & Release (F4)
        </el-button>
        <el-button type="primary" @click="handleValidateRelease">
          <el-icon><Check /></el-icon>
          Validate & Release (F5)
        </el-button>
      </div>
      <el-button type="danger" @click="handleClose">
        <el-icon><Close /></el-icon>
        Close
      </el-button>
    </div>

    <div class="content-container">
      <!-- Details Sections -->
      <div class="details-sections">
        <!-- Patient Details Section -->
        <div class="section patient-details">
          <div class="section-title">Patient Details</div>
          <el-form label-position="left" label-width="120px" class="compact-form">
            <!-- Column 1 -->
            <div style="flex: 1">
              <el-form-item label="Sample ID:">
                <el-input v-model="patientDetails.sampleId"/>
              </el-form-item>
              <el-form-item label="Patient ID:">
                <el-input v-model="patientDetails.patientId">
                  <template #suffix>
                    <el-icon><User /></el-icon>
                  </template>
                </el-input>
              </el-form-item>
              <el-form-item label="Patient Name:">
                <el-input v-model="patientDetails.patientName"/>
              </el-form-item>
              <el-form-item label="Date of Birth:">
                <el-date-picker
                  v-model="patientDetails.dateOfBirth"
                  type="date"
                  style="width: 100%"
                />
              </el-form-item>
              <el-form-item label="Age:">
                <el-input v-model="patientDetails.age">
                  <template #suffix>{{patientDetails.ageUnit}}</template>
                </el-input>
              </el-form-item>
            </div>

            <!-- Column 2 -->
            <div style="flex: 1">
              <el-form-item label="Sex:">
                <el-select v-model="patientDetails.sex" style="width: 100%">
                  <el-option label="Male" value="male"/>
                  <el-option label="Female" value="female"/>
                </el-select>
              </el-form-item>
              <el-form-item label="Civil Status:">
                <el-select v-model="patientDetails.civilStatus" style="width: 100%">
                  <el-option label="Single" value="single"/>
                  <el-option label="Married" value="married"/>
                </el-select>
              </el-form-item>
              <el-form-item label="Address:">
                <el-input v-model="patientDetails.address"/>
              </el-form-item>
              <el-form-item label="Contact No.:">
                <el-input v-model="patientDetails.contactNo" />
              </el-form-item>
              <el-form-item label="Email Address:">
                <el-input v-model="patientDetails.emailAddress"/>
              </el-form-item>
            </div>
          </el-form>
        </div>

        <!-- Additional Details Section -->
        <div class="section additional-details">
          <div class="section-title">Additional Details</div>
          <el-form label-position="left" label-width="120px" class="compact-form">
            <div style="flex: 1">
              <el-form-item label="Date Received:">
                <el-date-picker
                  v-model="additionalDetails.dateReceived"
                  type="datetime"
                  disabled
                  style="width: 100%"
                />
              </el-form-item>
              <el-form-item label="Time Received:">
                <el-time-picker
                  v-model="additionalDetails.timeReceived"
                  disabled
                  style="width: 100%"
                />
              </el-form-item>
              <el-form-item label="Date Released:">
                <el-date-picker
                  v-model="additionalDetails.dateReleased"
                  type="datetime"
                  disabled
                  style="width: 100%"
                />
              </el-form-item>
            </div>
            <div style="flex: 1">
              <el-form-item label="Request:">
                <el-select v-model="additionalDetails.request" style="width: 100%">
                  <el-option label="Culture & Sensitivity" value="culture_sensitivity" />
                  <el-option label="Gram Stain" value="gram_stain" />
                  <el-option label="AFB Stain" value="afb_stain" />
                </el-select>
              </el-form-item>           
              <el-form-item label="Sample Source:">
                <el-input v-model="additionalDetails.sampleSource" placeholder="e.g., Urine, Blood, Sputum" />
              </el-form-item>
              <el-form-item label="Referred By:">
                <el-select v-model="additionalDetails.referredBy" multiple style="width: 100%">
                  <el-option label="Dr. Smith" value="dr_smith" />
                </el-select>
              </el-form-item>
              <el-form-item label="Physicians:">
                <el-select v-model="additionalDetails.physicians" multiple style="width: 100%">
                  <el-option label="Dr. Johnson" value="dr_johnson" />
                  <el-option label="Dr. Smith" value="dr_smith" />
                  <el-option label="Dr. Williams" value="dr_williams" />
                </el-select>
              </el-form-item>
            </div>
          </el-form>
        </div>
      </div>

      <!-- Test Results Section -->
      <div class="section test-results">
        <div class="panel-header">
          <div class="header-title">Test Results</div>
          <div class="header-actions">
            <el-button type="primary">
              <el-icon><Plus /></el-icon>
              Add Test Result
            </el-button>
          </div>
        </div>
        <el-table 
          :data="testResults" 
          style="width: 100%"
          row-key="id"
          @selection-change="handleSelectionChange"
          size="small"
          class="compact-table"
        >
          <el-table-column type="selection" width="40" :selectable="() => true" :reserve-selection="true" />
          <el-table-column prop="testName" label="Test Name" min-width="150"/>
          <el-table-column prop="organism" label="Organism Isolated" min-width="180">
            <template #default="{ row }">
              <el-input v-model="row.organism" size="small" placeholder="Enter organism" />
            </template>
          </el-table-column>
          <el-table-column prop="sensitivity" label="Sensitivity" min-width="250">
            <template #default="{ row }">
              <el-tag 
                v-for="(drug, index) in row.sensitivity" 
                :key="index" 
                :type="getSensitivityType(drug.result)" 
                closable
                @close="removeSensitivityDrug(row, index)"
                style="margin-right: 5px; margin-bottom: 5px;"
              >
                {{ drug.antibiotic }}: {{ drug.result }}
              </el-tag>
              <el-button size="small" @click="addSensitivityDrug(row)">Add Drug</el-button>
            </template>
          </el-table-column>
          <el-table-column prop="gramStainResult" label="Gram Stain Result" min-width="150">
             <template #default="{ row }">
              <el-input v-model="row.gramStainResult" size="small" placeholder="Gram Stain Result" />
            </template>
          </el-table-column>
          <el-table-column prop="remarks" label="Remarks" min-width="200">
            <template #default="{ row }">
              <el-input v-model="row.remarks" placeholder="Remarks" size="small" />
            </template>
          </el-table-column>
        </el-table>

        <div class="total-dif">Total Tests: {{ testResults.length }}</div>
      </div>
          <!-- Remarks, comments, and signatories -->
      <div class="bottom-container">
        <div class="top-section">
          <div class="remarks-panel">
            <div class="panel-label">Final Report Remarks</div>
            <el-input
              v-model="remarks"
              type="textarea"
              :rows="3"
              placeholder="e.g., No growth after X days of incubation"
            />
          </div>
          <div class="comments-panel">
            <div class="panel-label">Internal Comments</div>
            <el-input
              v-model="comments"
              type="textarea"
              :rows="3"
              placeholder="Internal notes..."
            />
          </div>
        </div>
            <!--Signatories Section-->
        <div class="signatories-section">
          <div class="section-title">Signatories</div>
            <el-form label-position="left" label-width="120px" class="section-signatories">
              <el-form-item label="Performed By:">
                <el-select v-model="signatories.performedBy" style="width: 100%">
                  <el-option label="Select Performer" value="" />
                </el-select>
              </el-form-item>
              <el-form-item label="Verified By:">
                <el-select v-model="signatories.verifiedBy" style="width: 100%" >
                  <el-option 
                    label="ANA CRUZ, RMT" 
                    value="ana_cruz" 
                  />
                </el-select>
              </el-form-item>
              <el-form-item label="Pathologist:">
                <el-select v-model="signatories.pathologist" style="width: 100%">
                  <el-option 
                    label="(SGD.) DR. JUAN DELA CRUZ, M.D." 
                    value="juan_cruz" 
                  />
                </el-select>
              </el-form-item>
            </el-form>
        </div>
      </div>
    </div>

    <!-- Add Sensitivity Drug Dialog -->
    <el-dialog v-model="sensitivityDialogVisible" title="Add Antibiotic Sensitivity" width="400px">
      <el-form :model="sensitivityForm" label-width="100px">
        <el-form-item label="Antibiotic">
          <el-input v-model="sensitivityForm.antibiotic" placeholder="Antibiotic name" />
        </el-form-item>
        <el-form-item label="Result (S/I/R)">
          <el-select v-model="sensitivityForm.result" placeholder="Select sensitivity">
            <el-option label="Sensitive (S)" value="S" />
            <el-option label="Intermediate (I)" value="I" />
            <el-option label="Resistant (R)" value="R" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="sensitivityDialogVisible = false">Cancel</el-button>
          <el-button type="primary" @click="confirmAddSensitivityDrug">Confirm</el-button>
        </span>
      </template>
    </el-dialog>

  </el-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import {
  View,
  Document,
  Printer,
  Check,
  Close,
  User,
  Refresh,
  Plus
} from '@element-plus/icons-vue';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  requestData: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['update:visible', 'close']);

const dialogVisible = ref(props.visible);
const sensitivityDialogVisible = ref(false);
const currentTestResultRow = ref(null);
const sensitivityForm = ref({
  antibiotic: '',
  result: 'S'
});

// Watch for changes in props.visible
watch(() => props.visible, (newValue) => {
  dialogVisible.value = newValue;
  if (newValue && props.requestData) {
    // Pre-fill data when modal becomes visible
    patientDetails.value = { ...props.requestData.patientDetails }; 
    additionalDetails.value = { ...props.requestData.additionalDetails };
    testResults.value = props.requestData.testResults ? JSON.parse(JSON.stringify(props.requestData.testResults)) : createDefaultTestResult(props.requestData.testName);
    signatories.value = { ...props.requestData.signatories };
    remarks.value = props.requestData.remarks || '';
    comments.value = props.requestData.comments || '';
  } else if (!newValue) {
    // Reset data when modal closes
    resetModalData();
  }
});

// Watch for changes in dialogVisible
watch(dialogVisible, (newValue) => {
  emit('update:visible', newValue);
});

// Patient Details
const patientDetails = ref({
  sampleId: '',
  patientId: '',
  patientName: '',
  sex: '',
  dateOfBirth: null,
  age: '',
  ageUnit: 'Year(s)',
  civilStatus: '',
  address: '',
  contactNo: '',
  emailAddress: ''
});

// Computed property to calculate age in the most appropriate unit
const computedAge = computed(() => {
  if (!patientDetails.value.dateOfBirth) return { value: '', unit: 'Year(s)' };
  
  const today = new Date();
  const birthDate = new Date(patientDetails.value.dateOfBirth);
  
  let years = today.getFullYear() - birthDate.getFullYear();
  let months = today.getMonth() - birthDate.getMonth();
  let days = today.getDate() - birthDate.getDate();

  if (days < 0) {
    months--;
    const lastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
    days += lastMonth.getDate();
  }

  if (months < 0) {
    years--;
    months += 12;
  }

  // Determine which unit to display based on age
  if (years >= 1) {
    return { value: years, unit: 'Year(s)' };
  } else if (months >= 1) {
    return { value: months, unit: 'Month(s)' };
  } else {
    return { value: days, unit: 'Day(s)' };
  }
});

// Watch for changes in dateOfBirth to update age
watch(() => patientDetails.value.dateOfBirth, () => {
  const age = computedAge.value;
  patientDetails.value.age = age.value;
  patientDetails.value.ageUnit = age.unit;
}, { immediate: true });

// Additional Details
const additionalDetails = ref({
  dateReceived: new Date(),
  timeReceived: new Date(),
  dateReleased: new Date(),
  request: 'culture_sensitivity',
  sampleSource: '',
  referredBy: [],
  physicians: []
});

// Signatories
const signatories = ref({
  performedBy: '',
  verifiedBy: 'ana_cruz',
  pathologist: 'juan_cruz'
});

// Function to create a default test result structure
const createDefaultTestResult = (testName) => {
  return [
    {
      id: 1,
      testName: testName || 'Culture & Sensitivity', // Use provided test name or default
      organism: '',
      sensitivity: [],
      gramStainResult: testName === 'Gram Stain' ? '' : null, // Only relevant for Gram Stain
      remarks: ''
    }
  ];
};

// Test Results - Initialized based on requestData in watcher
const testResults = ref([]);

// Initialize with default if no requestData initially
if (!props.requestData?.testResults) {
  testResults.value = createDefaultTestResult(props.requestData?.testName);
}

const selectedTests = ref([]);
const remarks = ref('');
const comments = ref('');

// Reset Modal Data
const resetModalData = () => {
  patientDetails.value = {
    sampleId: '',
    patientId: '',
    patientName: '',
    sex: '',
    dateOfBirth: null,
    age: '',
    ageUnit: 'Year(s)',
    civilStatus: '',
    address: '',
    contactNo: '',
    emailAddress: ''
  };
  additionalDetails.value = {
    dateReceived: new Date(),
    timeReceived: new Date(),
    dateReleased: new Date(),
    request: 'culture_sensitivity',
    sampleSource: '',
    referredBy: [],
    physicians: []
  };
  signatories.value = {
    performedBy: '',
    verifiedBy: 'ana_cruz',
    pathologist: 'juan_cruz'
  };
  testResults.value = [];
  remarks.value = '';
  comments.value = '';
  selectedTests.value = [];
};

// Methods
const handlePreview = () => {
  console.log('Preview clicked');
};

const handleSave = () => {
  console.log('Save clicked');
  // Logic to save the current state of the modal data
};

const handlePrintRelease = () => {
  console.log('Print & Release clicked');
};

const handleValidateRelease = () => {
  console.log('Validate & Release clicked');
};

const handleClose = () => {
  emit('update:visible', false);
  emit('close');
};

const handleSelectionChange = (selection) => {
  selectedTests.value = selection.map(test => test.id);
};

// Sensitivity Methods
const addSensitivityDrug = (row) => {
  currentTestResultRow.value = row;
  sensitivityForm.value = { antibiotic: '', result: 'S' }; // Reset form
  sensitivityDialogVisible.value = true;
};

const confirmAddSensitivityDrug = () => {
  if (currentTestResultRow.value && sensitivityForm.value.antibiotic) {
    if (!currentTestResultRow.value.sensitivity) {
      currentTestResultRow.value.sensitivity = [];
    }
    currentTestResultRow.value.sensitivity.push({ ...sensitivityForm.value });
    sensitivityDialogVisible.value = false;
  }
};

const removeSensitivityDrug = (row, index) => {
  row.sensitivity.splice(index, 1);
};

const getSensitivityType = (result) => {
  switch (result) {
    case 'S': return 'success';
    case 'I': return 'warning';
    case 'R': return 'danger';
    default: return 'info';
  }
};

</script>

<style scoped>
.microbiology-modal {
  display: flex;
  flex-direction: column;
  height: 100vh;
}

.action-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-sm) var(--spacing-lg);
  background-color: var(--color-bg-secondary);
  border-bottom: 1px solid var(--color-border-secondary);
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
}

.action-buttons {
  display: flex;
  gap: var(--spacing-sm);
}

.content-container {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xl);
  padding: var(--spacing-xs);
  margin-top: 60px; /* Account for fixed action bar */
}

.details-sections {
  display: flex;
  gap: var(--spacing-xl);
  margin-bottom: 0px;
}

.section {
  background-color: var(--color-bg-primary);
  border-radius: var(--border-radius-md, 4px);
  padding: var(--spacing-lg);
  border: 1px solid var(--color-border-secondary);
}

.patient-details {
  flex: 2;
}

.additional-details {
  flex: 2;
}

.test-results {
  flex: 1;
  min-height: 300px;
}

.section-title {
  font-size: var(--font-size-base, 14px);
  font-weight: var(--font-weight-bold);
  color: var(--color-primary);
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-sm);
  border-bottom: 2px solid var(--color-primary);
}

.header-title {
  font-size: var(--font-size-base, 14px);
  font-weight: var(--font-weight-bold);
  color: var(--color-primary);
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-sm);
}

.compact-form {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-lg);
  align-items: center;
}

.form-row {
  display: flex;
  gap: var(--spacing-md);
  align-items: center;
}

.compact-form :deep(.el-form-item) {
  margin-bottom: var(--spacing-sm);
  margin-right: 0;
  display: flex;
  align-items: center;
}

.compact-form :deep(.el-form-item__label) {
  font-size: var(--font-size-sm, 13px);
  color: var(--color-text-secondary);
  padding-right: var(--spacing-md);
  line-height: 32px;
  white-space: nowrap;
}

.compact-form :deep(.el-input__wrapper),
.compact-form :deep(.el-select),
.compact-form :deep(.el-date-editor.el-input),
.compact-form :deep(.el-date-editor.el-input__wrapper) {
  height: 32px;
  line-height: 32px;
  box-shadow: none;
  border: 1px solid var(--color-border-secondary);
  border-radius: var(--border-radius-md, 4px);
}

.compact-form :deep(.el-input__inner) {
  height: 32px;
  line-height: 32px;
  font-size: var(--font-size-sm, 13px);
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-xl);
  border-bottom: 2px solid var(--color-primary);
}

.header-actions {
  display: flex;
  gap: var(--spacing-sm, 10px);
}

.total-dif {
  margin-top: var(--spacing-sm, 10px);
  padding: var(--spacing-sm, 10px);
  background-color: var(--color-warning); /* Changed color for Microbiology */
  color: var(--color-text-light, white);
  font-weight: var(--font-weight-bold);
}

.bottom-container {
  bottom: 0;
  left: 0;
  right: 0;
  background-color: var(--color-bg-primary);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
  z-index: 100;
  height: 200px;
}

.top-section {
  display: flex;
  gap: var(--spacing-lg);
  flex: 1;
}

.bottom-section {
  padding-top: var(--spacing-md);
}

.remarks-panel,
.comments-panel {
  flex: 1;
}

.signatories-section {
  width: 100%;
}

.signatories-section .section-title {
  margin-top: 0;
  font-size: var(--font-size-xs, 12px);
  margin-bottom: var(--spacing-md);
}
.signatories-section .section-signatories {
  flex: 1;
  display: grid; 
  grid-template-columns: 1fr 1fr 1fr; 
  gap: var(--spacing-lg);
}
.panel-label {
  font-weight: var(--font-weight-bold);
  margin-bottom: var(--spacing-xs);
  font-size: var(--font-size-xs, 12px);
}

:deep(.el-textarea__inner) {
  font-size: var(--font-size-xs, 12px);
}

:deep(.el-dialog__body) {
  padding: 0;
}

:deep(.el-form-item) {
  margin-bottom: var(--spacing-xs);
}

:deep(.el-form-item__label) {
  font-size: var(--font-size-xs, 12px);
  line-height: 28px;
  padding: 0 var(--spacing-sm) 0 0;
}

:deep(.el-input__wrapper),
:deep(.el-select),
:deep(.el-date-editor.el-input) {
  height: 28px;
  line-height: 28px;
}

.compact-table :deep(.el-table__header-wrapper) {
  th {
    padding: var(--spacing-xs) 0;
    background-color: var(--color-bg-secondary);
  }
}

.compact-table :deep(.el-table__body-wrapper) {
  td {
    padding: 2px 0;
  }
}

.compact-table :deep(.el-input__wrapper) {
  padding: 1px var(--spacing-sm);
}

.compact-table :deep(.el-input__inner) {
  height: 24px;
  line-height: 24px;
}

.compact-table :deep(.el-select) {
  width: 100%;
}

.compact-table :deep(.el-table__row) {
  height: 32px;
}

.compact-table :deep(.el-checkbox) {
  height: 24px;
  margin-right: var(--spacing-xs);
}
</style> 