<template>
  <el-dialog
    :model-value="modelValue"
    :title="'Patient Order Details'"
    :fullscreen="true"
    :show-close="false"
    class="request-dialog"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <template #header>
      <div class="dialog-header">
        <div class="header-actions">
          <el-button type="primary" @click="handleSave">
            <el-icon><Check /></el-icon>Save Order
          </el-button>
          <el-button type="danger" align="right" @click="$emit('update:modelValue', false)">
            <el-icon><Close /></el-icon>Close
          </el-button>
        </div>
      </div>
    </template>

    <div class="order-content">
      <!-- Details Sections -->
      <div class="details-sections">
        <!-- Patient Details Section -->
        <div class="section patient-details">
          <h3>Patient Details</h3>
          <el-form :model="patientForm" label-position="left" label-width="120px" class="compact-form">
            <div style="flex: 1">
              <el-form-item label="Sample ID:">
                <el-input v-model="patientForm.sampleId" disabled style="width: 100%"/>
              </el-form-item>
              <el-form-item label="Patient ID:">
                <div class="search-input">
                  <el-input v-model="patientForm.patientId" disabled style="width: 100%">
                    <template #append>
                      <el-button :icon="Search" @click="handlePatientSearch" />
                    </template>
                  </el-input>
                </div>
              </el-form-item>
              <el-form-item label="Last Name:">
                <el-input v-model="patientForm.lastName" style="width: 100%"/>
              </el-form-item>
              <el-form-item label="First Name:">
                <el-input v-model="patientForm.firstName" style="width: 100%"/>
              </el-form-item>
              <el-form-item label="Middle Name:">
                <el-input v-model="patientForm.middleName" style="width: 100%"/>
              </el-form-item>
              <el-form-item label="Date of Birth:">
                <el-date-picker
                  v-model="patientForm.dateOfBirth"
                  type="date"
                  placeholder="Select date"
                  style="width: 100%"
                />
              </el-form-item>
              <el-form-item label="Age:">
                <div style="display: flex; width: 100%">
                  <el-input v-model="patientForm.age" style="flex: 1; margin-right: 5px" />
                  <el-select v-model="patientForm.ageUnit" style="width: 100px">
                    <el-option label="Year(s)" value="Year(s)" />
                    <el-option label="Month(s)" value="Month(s)" />
                    <el-option label="Day(s)" value="Day(s)" />
                  </el-select>
                </div>
              </el-form-item>
            </div>
            <div style="flex: 1">
              <el-form-item label="Sex:">
                <el-select v-model="patientForm.sex" placeholder="Select sex" style="width: 100%"  >
                  <el-option label="Male" value="male" />
                  <el-option label="Female" value="female" />
                </el-select>
              </el-form-item>
              <el-form-item label="Civil Status:">
                <el-select v-model="patientForm.civilStatus" placeholder="Select status" style="width: 100%">
                  <el-option label="Single" value="single" />
                  <el-option label="Married" value="married" />
                  <el-option label="Widowed" value="widowed" />
                  <el-option label="Divorced" value="divorced" />
                </el-select>
              </el-form-item>
              <el-form-item label="Address:">
                <el-input v-model="patientForm.address" style="width: 100%"/>
              </el-form-item>
              <el-form-item label="Contact No:">
                <el-input v-model="patientForm.contactNo" style="width: 100%"/>
              </el-form-item>
              <el-form-item label="Email Address:">
                <el-input v-model="patientForm.email" style="width: 100%"/>
              </el-form-item>
            </div>
          </el-form>
        </div>

        <!-- Additional Details Section -->
        <div class="section additional-details">
          <h3>Additional Details</h3>
          <el-form :model="additionalForm" label-position="left" label-width="150px" class="compact-form">
            <div style="flex: 1">
              <el-form-item label="OR No.:">
                <el-input v-model="additionalForm.orNo" />
              </el-form-item>
              <el-form-item label="Priority Level:">
                <el-select v-model="additionalForm.priorityLevel" placeholder="Select level">
                  <el-option label="Routine" value="routine" />
                  <el-option label="Urgent" value="urgent" />
                  <el-option label="STAT" value="stat" />
                </el-select>
              </el-form-item>
              <el-form-item label="Healthcare Provider:">
                <el-select v-model="additionalForm.healthcareProvider" placeholder="Select provider">
                  <el-option label="Walk-In" value="walk-in" />
                  <el-option label="HMO" value="hmo" />
                  <el-option label="Company" value="company" />
                </el-select>
              </el-form-item>
            </div>
            <div style="flex: 1">
              <el-form-item label="Referred By:">
                <el-select v-model="additionalForm.referredBy" multiple placeholder="Select referrer">
                  <el-option
                    v-for="doctor in doctors"
                    :key="doctor.id"
                    :label="doctor.name"
                    :value="doctor.id"
                  />
                </el-select>
              </el-form-item>
              <el-form-item label="Healthcare Type:">
                <el-input v-model="additionalForm.healthcareType" disabled />
              </el-form-item>
              <el-form-item label="Requesting Doctor(s):">
                <el-select v-model="additionalForm.physician" multiple placeholder="Select physician">
                  <el-option
                    v-for="doctor in doctors"
                    :key="doctor.id"
                    :label="doctor.name"
                    :value="doctor.id"
                  />
                </el-select>
              </el-form-item>
              <el-form-item label="Home Service:">
                <div class="checkbox-group">
                  <el-checkbox v-model="additionalForm.isAR" label="A/R" />
                  <el-checkbox v-model="additionalForm.isFOC" label="FOC" />
                </div>
              </el-form-item>
              <el-form-item label="Payee:">
                <el-select v-model="additionalForm.payee" placeholder="Select payment type">
                  <el-option label="Cash" value="cash" />
                  <el-option label="Credit Card" value="credit" />
                  <el-option label="Insurance" value="insurance" />
                </el-select>
              </el-form-item>
            </div>
          </el-form>
        </div>
      </div>

      <!-- Test Items Section -->
      <div class="section test-items">
        <h3>Test Items</h3>
        <el-tabs v-model="activeRoutine" stretch>
          <el-tab-pane label="Routine Test" name="routine">
            <!-- Test Selection Tabs -->
            <div class="section test-selection">
              <el-tabs v-model="activeTestTab">

                <!-- Hematology -->
                <el-tab-pane label="Hematology" name="hematology">
                  <el-table v-loading="loading" :data="hematologyTests" style="width: 100%" border>
                    <el-table-column type="selection" width="55" />
                    <el-table-column prop="name" label="Test Name" />
                    <el-table-column prop="price" label="Price" width="120">
                      <template #default="{ row }">
                        {{ formatCurrency(row.price) }}
                      </template>
                    </el-table-column>
                  </el-table>
                </el-tab-pane>

                <!-- Chemistry -->
                <el-tab-pane label="Chemistry" name="chemistry">
                  <el-table :data="chemistryTests" style="width: 100%" border>
                    <el-table-column type="selection" width="55" />
                    <el-table-column prop="name" label="Test Name" />
                    <el-table-column prop="price" label="Price" width="120">
                      <template #default="{ row }">
                        {{ formatCurrency(row.price) }}
                      </template>
                    </el-table-column>
                  </el-table>
                </el-tab-pane>

                <!-- Clinical Microscopy -->
                <el-tab-pane label="Clinical Microscopy" name="microscopy">
                  <el-table :data="microscopyTests" style="width: 100%" border>
                    <el-table-column type="selection" width="55" />
                    <el-table-column prop="name" label="Test Name" />
                    <el-table-column prop="price" label="Price" width="120">
                      <template #default="{ row }">
                        {{ formatCurrency(row.price) }}
                      </template>
                    </el-table-column>
                  </el-table>
                </el-tab-pane>

                <!-- Microbiology / Bacteriology -->
                <el-tab-pane label="Microbiology / Bacteriology" name="microbiology">
                  <el-table :data="microbiologyTests" style="width: 100%" border>
                    <el-table-column type="selection" width="55" />
                    <el-table-column prop="name" label="Test Name" />
                    <el-table-column prop="price" label="Price" width="120">
                      <template #default="{ row }">
                        {{ formatCurrency(row.price) }}
                      </template>
                    </el-table-column>
                  </el-table>
                </el-tab-pane>

                <!-- Immunology / Tumor Markers -->
                <el-tab-pane label="Immunology / Tumor Markers" name="immunology">
                  <el-table :data="immunologyTests" style="width: 100%" border>
                    <el-table-column type="selection" width="55" />
                    <el-table-column prop="name" label="Test Name" />
                    <el-table-column prop="price" label="Price" width="120">
                      <template #default="{ row }">
                        {{ formatCurrency(row.price) }}
                      </template>
                    </el-table-column>
                  </el-table>
                </el-tab-pane>
              </el-tabs>
            </div>
          </el-tab-pane>
          <!-- Imaging Services -->
          <el-tab-pane label="Imaging Services" name="imaging">
            <el-table :data="imagingServices" style="width: 100%" border>
              <el-table-column type="selection" width="55" />
              <el-table-column prop="name" label="Test Name" />
              <el-table-column prop="price" label="Price" width="120">
                <template #default="{ row }">
                  {{ formatCurrency(row.price) }}
                </template>
              </el-table-column>
            </el-table>
          </el-tab-pane>
          <!-- Packages -->
          <el-tab-pane label="Packages" name="package">
            <el-table :data="packages" style="width: 100%" border>
              <el-table-column type="selection" width="55" />
              <el-table-column prop="name" label="Test Name" />
              <el-table-column prop="price" label="Price" width="120">
                <template #default="{ row }">
                  {{ formatCurrency(row.price) }}
                </template>
              </el-table-column>
            </el-table>
          </el-tab-pane>
          <el-tab-pane label="Special test" name="specialTests">
            <el-table :data="specialTests" style="width: 100%" border>
              <el-table-column type="selection" width="55" />
              <el-table-column prop="name" label="Test Name" />
              <el-table-column prop="price" label="Price" width="120">
                <template #default="{ row }">
                  {{ formatCurrency(row.price) }}
                </template>
              </el-table-column>
            </el-table>
          </el-tab-pane>
          <!-- Clinical Procedures -->
          <el-tab-pane label="Clinical Procedures" name="procedures">
            <el-table :data="procedures" style="width: 100%" border>
              <el-table-column type="selection" width="55" />
              <el-table-column prop="name" label="Test Name" />
              <el-table-column prop="price" label="Price" width="120">
                <template #default="{ row }">
                  {{ formatCurrency(row.price) }}
                </template>
              </el-table-column>
            </el-table>
          </el-tab-pane>
        </el-tabs>
      </div>

      <!-- Order Summary -->
      <div class="section order-summary">
        <div class="summary-content">
          <div class="distributions">
            <h4>Distributions</h4>
            <el-form :model="distributionForm" label-width="80px">
              <el-form-item label="Copy:">
                <el-input v-model="distributionForm.copy" />
              </el-form-item>
              <el-form-item label="Send:">
                <el-input v-model="distributionForm.send" />
              </el-form-item>
              <el-form-item label="Pickup:">
                <el-input v-model="distributionForm.pickup" />
              </el-form-item>
              <el-form-item label="Fax:">
                <el-input v-model="distributionForm.fax" />
              </el-form-item>
              <el-form-item label="Mail:">
                <el-input v-model="distributionForm.mail" />
              </el-form-item>
              <el-form-item label="Email:">
                <el-input v-model="distributionForm.email" />
              </el-form-item>
              <el-form-item label="Send To:">
                <el-input v-model="distributionForm.sendTo" />
              </el-form-item>
            </el-form>
          </div>

          <div class="computations">
            <h4>Computations</h4>
            <el-form :model="computationForm" label-width="150px">
              <el-form-item label="Discount Type:">
                <el-select v-model="computationForm.discountType" placeholder="Select type">
                  <el-option label="N/A" value="na" />
                  <el-option label="Senior Citizen" value="senior" />
                  <el-option label="PWD" value="pwd" />
                </el-select>
              </el-form-item>
              <el-form-item label="Discount %:">
                <el-input v-model="computationForm.discountPercentage" disabled />
              </el-form-item>
              <el-form-item label="Ordered Test:">
                <el-input v-model="computationForm.orderedTest" disabled />
              </el-form-item>
              <el-form-item label="Home Service Fee:">
                <el-input v-model="computationForm.homeServiceFee" disabled />
              </el-form-item>
              <el-form-item label="Total Amount:">
                <el-input v-model="computationForm.totalAmount" disabled />
              </el-form-item>
            </el-form>
          </div>
        </div>
      </div>
    </div>
  </el-dialog>
</template>

<script setup>
import { ref, defineProps, defineEmits, computed, watch } from 'vue';
import { Check, Delete, Close, Search } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import api from '@/utils/axios';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true
  },
  initialPatient: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['update:modelValue', 'save', 'search-patient']);

const activeTestTab = ref('hematology');  
const activeRoutine = ref('routine');

const patientForm = ref({
  sampleId: '0004356',
  patientId: 'M24-05-277',
  lastName: '',
  firstName: '',
  middleName: '',
  sex: '',
  dateOfBirth: '',
  age: '',
  ageUnit: 'Year(s)',
  civilStatus: '',
  address: '',
  contactNo: '',
  email: '',
});

const additionalForm = ref({
  healthcareProvider: '',
  healthcareType: 'Walk-In',
  homeService: false,
  isAR: false,
  isFOC: false,
  payee: 'CASH',
  orNo: '',
  priorityLevel: 'ROUTINE',
  referredBy: ['Dr. John Smith'],
  physician: ['Dr. Jane Doe']
});

const distributionForm = ref({
  copy: '',
  send: '',
  pickup: '',
  fax: '',
  mail: '',
  email: '',
  sendTo: ''
});

const computationForm = ref({
  discountType: 'na',
  discountPercentage: '0',
  orderedTest: '0.00',
  homeServiceFee: '0.00',
  totalAmount: '0.00'
});

// Sample test data (replace with API calls)
const hematologyTests = ref([
  { name: 'Complete Blood Count', price: 350 },
  { name: 'Blood Typing', price: 250 },
]);

const chemistryTests = ref([
  { name: 'Blood Sugar', price: 200 },
  { name: 'Cholesterol', price: 300 },
]);

const microscopyTests = ref([
  { name: 'Urinalysis', price: 150 },
  { name: 'Fecalysis', price: 150 },
]);

const microbiologyTests = ref([
  { name: 'Culture and Sensitivity', price: 800 },
  { name: 'Gram Stain', price: 300 },
]);

const immunologyTests = ref([
  { name: 'HIV Test', price: 1200 },
  { name: 'COVID-19 Antibody', price: 2500 },
]);

const doctors = ref([
  { id: 1, name: 'Dr. John Smith' },
  { id: 2, name: 'Dr. Jane Doe' },
]);

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'PHP'
  }).format(value);
};

const handleSave = async () => {
  try {
    // Implement save logic
    emit('save', {
      patient: patientForm.value,
      additional: additionalForm.value,
      distribution: distributionForm.value,
      computation: computationForm.value
    });
    emit('update:modelValue', false);
  } catch (error) {
    ElMessage.error('Error saving request');
    console.error('Error:', error);
  }
};

const handleClear = () => {
  patientForm.value = {
    sampleId: '0004356',
    patientId: 'M24-05-277',
    lastName: '',
    firstName: '',
    middleName: '',
    sex: '',
    dateOfBirth: '',
    age: '',
    ageUnit: 'Year(s)',
    civilStatus: '',
    address: '',
    contactNo: '',
    email: '',
    patientNo: ''
  };
  additionalForm.value = {
    dateReceived: new Date(),
    timeReceived: new Date(),
    healthcareProvider: '',
    healthcareType: 'Walk-In',
    homeService: false,
    isAR: false,
    isFOC: false,
    payee: 'CASH',
    orNo: '',
    priorityLevel: 'ROUTINE',
    referredBy: ['Dr. John Smith'],
    physician: ['Dr. Jane Doe']
  };
  distributionForm.value = {
    copy: '',
    send: '',
    pickup: '',
    fax: '',
    mail: '',
    email: '',
    sendTo: ''
  };
  computationForm.value = {
    discountType: 'na',
    discountPercentage: '0',
    orderedTest: '0.00',
    homeServiceFee: '0.00',
    totalAmount: '0.00'
  };
};

// Computed property to calculate age in the most appropriate unit
const computedAge = computed(() => {
  if (!patientForm.value.dateOfBirth) return { value: '', unit: 'Year(s)' };
  
  const today = new Date();
  const birthDate = new Date(patientForm.value.dateOfBirth);
  
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
watch(() => patientForm.value.dateOfBirth, () => {
  const age = computedAge.value;
  patientForm.value.age = age.value;
  patientForm.value.ageUnit = age.unit;
}, { immediate: true });

const handlePatientSearch = () => {
  emit('search-patient');
};
</script>

<style scoped>
.request-dialog {
  display: flex;
  flex-direction: column;
}

.dialog-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 16px;
  background-color: #f5f7fa;
  border-bottom: 1px solid #dcdfe6;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.order-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 4px;
  overflow-y: auto;
}

.details-sections {
  display: flex;
  gap: 20px;
  margin-bottom: 0px;
}

.section {
  background-color: #fff;
  border-radius: 4px;
  padding: 16px;
  border: 1px solid #dcdfe6;
}

.patient-details {
  flex: 1;
}

.additional-details {
  flex: 1;
}

.test-items {
  flex: 1;
}

h3 {
  font-size: 16px;
  font-weight: bold;
  color: #087bff;
  margin-bottom: 16px;
  padding-bottom: 8px;
  border-bottom: 2px solid #087bff;
}

.age-input {
  display: flex;
  align-items: center;
  gap: 10px;
}

.search-input {
  display: flex;
  align-items: center;
}

.checkbox-group {
  display: flex;
  gap: 20px;
}

.summary-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.distributions,
.computations {
  background: #f5f7fa;
  padding: 20px;
  border-radius: 4px;
}

.compact-form {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  align-items: center;
}

.form-row {
  display: flex;
  gap: 12px;
  align-items: center;
}

.compact-form :deep(.el-form-item) {
  margin-bottom: 8px;
  margin-right: 0;
  display: flex;
  align-items: center;
}

.compact-form :deep(.el-form-item__label) {
  font-size: 13px;
  color: #606266;
  padding-right: 12px;
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
  border: 1px solid #dcdfe6;
  border-radius: 4px;
}

.compact-form :deep(.el-input__inner) {
  height: 32px;
  line-height: 32px;
  font-size: 13px;
}
</style> 