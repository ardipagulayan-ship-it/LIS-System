<template>
  <el-dialog
    v-model="dialogVisible"
    :title="'Clinical Microscopy Result Window'"
    :fullscreen="true"
    :show-close="true"
    class="microscopy-modal"
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
                  <el-option label="Urinalysis" value="urinalysis" />
                  <el-option label="Stool Analysis" value="stool" />
                  <el-option label="Semen Analysis" value="semen" />
                </el-select>
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
            <el-button type="primary" plain>
              <el-icon><Refresh /></el-icon>
              Retrieve Re-run
            </el-button>
            <el-button type="primary" plain>
              <el-icon><Document /></el-icon>
              Show Delta Check
            </el-button>
            <el-button type="primary">
              <el-icon><Plus /></el-icon>
              Add Test
            </el-button>
          </div>
        </div>
        <el-table 
          :data="testResults" 
          style="width: 100%"
          row-key="testName"
          :default-checked-keys="testResults.map(test => test.testName)"
          @selection-change="handleSelectionChange"
          size="small"
          class="compact-table"
        >
          <el-table-column type="selection" width="40" :selectable="() => true" :reserve-selection="true" />
          <el-table-column prop="testName" label="Test Name" min-width="150"/>
          <el-table-column prop="result" label="Result" width="150" align="center">
            <template #default="{ row }">
              <el-input v-model="row.result" size="small" />
            </template>
          </el-table-column>
          <el-table-column prop="referenceRange" label="Reference Range" width="150" align="center" />
          <el-table-column prop="method" label="Method" width="150" align="center">
            <template #default="{ row }">
              <el-select v-model="row.method" style="width: 100%" size="small">
                <el-option label="Microscopy" value="Microscopy" />
                <el-option label="Macroscopic" value="Macroscopic" />
                <el-option label="Chemical" value="Chemical" />
                <el-option label="Manual" value="Manual" />
              </el-select>
            </template>
          </el-table-column>
          <el-table-column prop="remarks" label="Remarks" width="300" align="center">
            <template #default="{ row }">
              <el-input v-model="row.remarks" placeholder="Remarks" size="small" />
            </template>
          </el-table-column>
        </el-table>

        <div class="total-dif">Total Tests: {{ testResults.length }}</div>
      </div>
          <!-- Moved remarks, comments, and signatories to bottom -->
      <div class="bottom-container">
        <div class="top-section">
          <div class="remarks-panel">
            <div class="panel-label">Remarks</div>
            <el-input
              v-model="remarks"
              type="textarea"
              :rows="3"
              placeholder="RESULTS VERIFIED"
            />
          </div>
          <div class="comments-panel">
            <div class="panel-label">Comment</div>
            <el-input
              v-model="comments"
              type="textarea"
              :rows="3"
              placeholder="Enter comments..."
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
                    label="MARIA SANTOS, RMT" 
                    value="maria_santos" 
                  />
                </el-select>
              </el-form-item>
              <el-form-item label="Pathologist:">
                <el-select v-model="signatories.pathologist" style="width: 100%">
                  <el-option 
                    label="(SGD.) DR. JAMES GARCIA, M.D." 
                    value="james_garcia" 
                  />
                </el-select>
              </el-form-item>
            </el-form>
        </div>
      </div>
    </div>
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

// Watch for changes in props.visible
watch(() => props.visible, (newValue) => {
  dialogVisible.value = newValue;
});

// Watch for changes in dialogVisible
watch(dialogVisible, (newValue) => {
  emit('update:visible', newValue);
});

// Patient Details
const patientDetails = ref({
  sampleId: '0004358',
  patientId: 'M24-05-277',
  patientName: 'MIRANDA, HECTOR ELCID',
  sex: 'male',
  dateOfBirth: new Date('1999-12-25'),
  age: '',
  ageUnit: 'Year(s)',
  civilStatus: 'single',
  address: 'PASIG CITY',
  contactNo: '09123456789',
  emailAddress: 'mirandaelcid27@gmail.com'
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
  dateReceived: new Date('2025-01-28'),
  timeReceived: new Date('2025-01-28T08:44:46'),
  dateReleased: new Date('2025-01-28T08:47:15'),
  request: 'urinalysis',
  referredBy: ['dr_smith'],
  physicians: ['dr_johnson']
});

// Signatories
const signatories = ref({
  performedBy: '',
  verifiedBy: 'maria_santos',
  pathologist: 'james_garcia'
});

// Test Results with added remarks field - Customized for Urinalysis
const testResults = ref([
  {
    testName: 'Color',
    result: '',
    referenceRange: 'Yellow',
    method: 'Macroscopic',
    remarks: ''
  },
  {
    testName: 'Transparency',
    result: '',
    referenceRange: 'Clear',
    method: 'Macroscopic',
    remarks: ''
  },
  {
    testName: 'pH',
    result: '',
    referenceRange: '4.5 - 8.0',
    method: 'Chemical',
    remarks: ''
  },
  {
    testName: 'Specific Gravity',
    result: '',
    referenceRange: '1.010 - 1.025',
    method: 'Chemical',
    remarks: ''
  },
  {
    testName: 'Protein',
    result: '',
    referenceRange: 'Negative',
    method: 'Chemical',
    remarks: ''
  },
  {
    testName: 'Glucose',
    result: '',
    referenceRange: 'Negative',
    method: 'Chemical',
    remarks: ''
  },
  {
    testName: 'Ketones',
    result: '',
    referenceRange: 'Negative',
    method: 'Chemical',
    remarks: ''
  },
  {
    testName: 'Blood',
    result: '',
    referenceRange: 'Negative',
    method: 'Chemical',
    remarks: ''
  },
  {
    testName: 'Bilirubin',
    result: '',
    referenceRange: 'Negative',
    method: 'Chemical',
    remarks: ''
  },
  {
    testName: 'Urobilinogen',
    result: '',
    referenceRange: 'Normal',
    method: 'Chemical',
    remarks: ''
  },
  {
    testName: 'Nitrite',
    result: '',
    referenceRange: 'Negative',
    method: 'Chemical',
    remarks: ''
  },
  {
    testName: 'Leukocyte Esterase',
    result: '',
    referenceRange: 'Negative',
    method: 'Chemical',
    remarks: ''
  },
  {
    testName: 'RBC/hpf',
    result: '',
    referenceRange: '0-3',
    method: 'Microscopy',
    remarks: ''
  },
  {
    testName: 'WBC/hpf',
    result: '',
    referenceRange: '0-5',
    method: 'Microscopy',
    remarks: ''
  },
  {
    testName: 'Epithelial Cells',
    result: '',
    referenceRange: 'Few',
    method: 'Microscopy',
    remarks: ''
  },
  {
    testName: 'Bacteria',
    result: '',
    referenceRange: 'Few/None',
    method: 'Microscopy',
    remarks: ''
  },
  {
    testName: 'Mucus Threads',
    result: '',
    referenceRange: 'Few/None',
    method: 'Microscopy',
    remarks: ''
  },
  {
    testName: 'Casts',
    result: '',
    referenceRange: 'None',
    method: 'Microscopy',
    remarks: ''
  },
  {
    testName: 'Crystals',
    result: '',
    referenceRange: 'None/Few',
    method: 'Microscopy',
    remarks: ''
  }
]);

// Add this after the testResults ref
const selectedTests = ref(testResults.value.map(test => test.testName));

const remarks = ref('RESULTS VERIFIED');
const comments = ref('');

// Methods
const handlePreview = () => {
  // Implement preview functionality
  console.log('Preview clicked');
};

const handleSave = () => {
  // Implement save functionality
  console.log('Save clicked');
};

const handlePrintRelease = () => {
  // Implement print and release functionality
  console.log('Print & Release clicked');
};

const handleValidateRelease = () => {
  // Implement validate and release functionality
  console.log('Validate & Release clicked');
};

const handleClose = () => {
  emit('update:visible', false);
  emit('close');
};

const handleSelectionChange = (selection) => {
  selectedTests.value = selection.map(test => test.testName);
};
</script>

<style scoped>
.microscopy-modal {
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
  background-color: var(--color-success);
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