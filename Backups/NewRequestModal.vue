<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="modelValue"
      title="New Laboratory Request"
      width="95%"
      :close-on-click-modal="false"
      :show-close="false"
      class="equipment-modal"
      top="2vh"
      @update:model-value="$emit('update:modelValue', $event)"
    >
      <!-- Modal Header with Brand -->
      <template #header>
        <div class="modal-header">
          <div class="modal-header-brand">
            <span class="modal-header-icon">🧪</span>
            <span class="modal-header-title">NEW LABORATORY REQUEST</span>
          </div>
          <div class="modal-header-actions">
            <el-button type="danger" @click="$emit('update:modelValue', false); handleClose()" size="small">
              <el-icon><Close /></el-icon>
              Close
            </el-button>
          </div>
        </div>
      </template>

      <!-- Main Content -->
      <div class="modal-body-content">
        <div class="equipment-form">
          <!-- SECTION 1: PATIENT INFORMATION -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">👤</span>
              <span class="section-title">Patient Information</span>
            </div>
            
            <el-form :model="patientForm" label-width="120px">
              <div class="form-row">
                <div class="form-col">
                  <el-form-item label="Patient ID">
                    <div class="search-input-wrapper">
                      <el-input v-model="patientForm.patientId" readonly>
                        <template #append>
                          <el-button :icon="Search" @click="handlePatientSearch" />
                        </template>
                      </el-input>
                    </div>
                  </el-form-item>
                </div>
                <div class="form-col">
                  <el-form-item label="Full Name">
                    <el-input :value="`${patientForm.last_name}, ${patientForm.first_name} ${patientForm.middle_name || ''}`" readonly />
                  </el-form-item>
                </div>
              </div>

              <div class="form-row">
                <div class="form-col">
                  <el-form-item label="Date of Birth">
                    <el-date-picker
                      v-model="patientForm.dateOfBirth"
                      type="date"
                      placeholder="Date of Birth"
                      style="width: 100%"
                      readonly
                    />
                  </el-form-item>
                </div>
                <div class="form-col">
                  <el-form-item label="Age / Sex">
                    <div style="display: flex; gap: 10px;">
                      <el-input v-model="patientForm.age" style="flex: 1" readonly />
                      <el-input v-model="patientForm.sex" style="flex: 1" readonly />
                    </div>
                  </el-form-item>
                </div>
              </div>

              <div class="form-row">
                <div class="form-col">
                  <el-form-item label="Contact">
                    <el-input v-model="patientForm.contactNo" readonly />
                  </el-form-item>
                </div>
                <div class="form-col">
                  <el-form-item label="Address">
                    <el-input v-model="patientForm.address" readonly />
                  </el-form-item>
                </div>
              </div>
            </el-form>
          </div>

          <!-- SECTION 2: REQUEST DETAILS -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">📋</span>
              <span class="section-title">Request Details</span>
            </div>

            <el-form :model="additionalForm" label-width="140px">
              <div class="form-row">
                <div class="form-col">
                  <el-form-item label="Accession ID">
                    <el-input v-model="additionalForm.sample_id" readonly placeholder="Auto-generated" />
                  </el-form-item>
                </div>
                <div class="form-col">
                  <el-form-item label="Priority Level">
                    <el-select v-model="additionalForm.priorityLevel" placeholder="Select level" class="full-width">
                      <el-option label="Routine" value="Routine" />
                      <el-option label="Urgent" value="Urgent" />
                      <el-option label="STAT" value="STAT" />
                    </el-select>
                  </el-form-item>
                </div>
              </div>

              <div class="form-row">
                <div class="form-col">
                  <el-form-item label="Requesting Doctor">
                    <el-select 
                      v-model="additionalForm.physician" 
                      placeholder="Select physician" 
                      class="full-width"
                      filterable
                    >
                      <el-option
                        v-for="doctor in doctors"
                        :key="doctor.id"
                        :label="doctor.name"
                        :value="doctor.id"
                      />
                    </el-select>
                  </el-form-item>
                </div>
                <div class="form-col">
                  <el-form-item label="OR No.">
                    <el-input v-model="additionalForm.orNo" />
                  </el-form-item>
                </div>
              </div>

              <div class="form-row">
                <!-- <div class="form-col">
                  <el-form-item label="Healthcare Provider">
                    <el-select v-model="additionalForm.healthcareProvider" placeholder="Select provider" class="full-width">
                      <el-option label="Walk-In" value="walk-in" />
                      <el-option label="HMO" value="hmo" />
                      <el-option label="Company" value="company" />
                    </el-select>
                  </el-form-item>
                </div> -->
                <div class="form-col">
                  <el-form-item label="Referred By">
                    <el-select v-model="additionalForm.referredBy" multiple placeholder="Select referrer" class="full-width" filterable>
                      <el-option
                        v-for="doctor in doctors"
                        :key="doctor.id"
                        :label="doctor.name"
                        :value="doctor.id"
                      />
                    </el-select>
                  </el-form-item>
                </div>

                <div class="form-col">
                  <el-form-item label="Ward">
                    <el-select v-model="additionalForm.ward_id" placeholder="Select ward" class="full-width" clearable :loading="loadingWards">
                      <el-option v-for="w in wards" :key="w.id" :label="w.name" :value="w.id" />
                    </el-select>
                  </el-form-item>
                </div>
              </div>

              <div class="form-row">
                <!-- <div class="form-col">
                  <el-form-item label="Healthcare Type">
                    <el-input v-model="additionalForm.healthcareType" disabled />
                  </el-form-item>
                </div> -->
                <div class="form-col">
                  <el-form-item label="Patient Type">
                    <el-select 
                        v-model="additionalForm.patient_type_id" 
                        placeholder="Select Patient Type" 
                        class="full-width"
                        clearable
                        :loading="loadingPatientTypes"
                    >
                      <el-option v-for="ptype in patientTypes" :key="ptype.id" :label="ptype.name" :value="ptype.id" />
                    </el-select>
                  </el-form-item>
                </div>
                <div class="form-col">
                  <el-form-item label="Room">
                    <el-select v-model="additionalForm.room_id" placeholder="Select room" class="full-width" clearable :loading="loadingRooms">
                      <el-option v-for="r in rooms" :key="r.id" :label="r.name" :value="r.id" />
                    </el-select>
                  </el-form-item>
                </div>
              </div>

              <!-- <div class="form-row">
                <div class="form-col">
                  <el-form-item label="Payment Type">
                    <el-select v-model="additionalForm.payee" placeholder="Select payment type" class="full-width">
                      <el-option label="Cash" value="cash" />
                      <el-option label="Credit Card" value="credit" />
                      <el-option label="Insurance" value="insurance" />
                    </el-select>
                  </el-form-item>
                </div>
              </div> -->

              <div class="form-row">
                <!-- <div class="form-col">
                  <el-form-item label="Home Service">
                    <el-switch v-model="additionalForm.homeService" />
                  </el-form-item>
                </div> -->
              </div>
<!-- 
              <div class="form-row">
                <div class="form-col">
                  <el-form-item label="Flags">
                    <div style="display: flex; gap: 12px;">
                      <el-checkbox v-model="additionalForm.isAR" label="A/R" />
                      <el-checkbox v-model="additionalForm.isFOC" label="FOC" />
                    </div>
                  </el-form-item>
                </div>
              </div> -->
            </el-form>
          </div>

          <!-- SECTION 3: TEST SELECTION -->
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">🔬</span>
              <span class="section-title">Test Selection</span>
            </div>

            <el-tabs v-model="activeRoutine" class="test-tabs">
              <el-tab-pane label="Routine Test" name="routine">
                <el-tabs v-model="activeCategory" @tab-change="handleCategoryChange" type="card">
                  <el-tab-pane 
                    v-for="category in testCategories" 
                    :key="category.id"
                    :label="category.name"
                    :name="category.id"
                  >
                    <div class="table-container">
                      <el-table
                        v-loading="loadingTests[category.id]"
                        :data="testsByCategory[category.id] || []"
                        @selection-change="(selection) => handleTestSelectionChange(category.id, selection)"
                        style="width: 100%" 
                        border
                        height="400"
                      >
                        <el-table-column type="selection" width="55" />
                        <el-table-column prop="name" label="Test Name" />
                        <el-table-column prop="price" label="Price" width="120">
                          <template #default="{ row }">
                            {{ formatCurrency(row.price) }}
                          </template>
                        </el-table-column>
                      </el-table>
                    </div>
                  </el-tab-pane>
                </el-tabs>
              </el-tab-pane>

              <el-tab-pane label="Imaging Services" name="imaging">
                <div class="table-container">
                  <el-table
                    v-loading="loadingImagingServices"
                    :data="imagingServices"
                    @selection-change="handleImagingSelectionChange"
                    style="width: 100%" 
                    border
                    height="400"
                  >
                    <el-table-column type="selection" width="55" />
                    <el-table-column prop="name" label="Test Name" />
                    <el-table-column prop="price" label="Price" width="120">
                      <template #default="{ row }">
                        {{ formatCurrency(row.price) }}
                      </template>
                    </el-table-column>
                  </el-table>
                </div>
              </el-tab-pane>

              <el-tab-pane label="Packages" name="package">
                <div class="table-container">
                  <el-table
                    v-loading="loadingPackages"
                    :data="packages"
                    @selection-change="handlePackageSelectionChange"
                    style="width: 100%" 
                    border
                    height="400"
                  >
                    <el-table-column type="selection" width="55" />
                    <el-table-column prop="name" label="Test Name" />
                    <el-table-column prop="price" label="Price" width="120">
                      <template #default="{ row }">
                        {{ formatCurrency(row.price) }}
                      </template>
                    </el-table-column>
                  </el-table>
                </div>
              </el-tab-pane>

              <el-tab-pane label="Special Tests" name="specialTests">
                <div class="table-container">
                  <el-table
                    v-loading="loadingSpecialTests"
                    :data="specialTests"
                    @selection-change="handleSpecialTestSelectionChange"
                    style="width: 100%" 
                    border
                    height="400"
                  >
                    <el-table-column type="selection" width="55" />
                    <el-table-column prop="name" label="Test Name" />
                    <el-table-column prop="price" label="Price" width="120">
                      <template #default="{ row }">
                        {{ formatCurrency(row.price) }}
                      </template>
                    </el-table-column>
                  </el-table>
                </div>
              </el-tab-pane>

              <el-tab-pane label="Clinical Procedures" name="procedures">
                <div class="table-container">
                  <el-table
                    v-loading="loadingProcedures"
                    :data="procedures"
                    @selection-change="handleProcedureSelectionChange"
                    style="width: 100%" 
                    border
                    height="400"
                  >
                    <el-table-column type="selection" width="55" />
                    <el-table-column prop="name" label="Test Name" />
                    <el-table-column prop="price" label="Price" width="120">
                      <template #default="{ row }">
                        {{ formatCurrency(row.price) }}
                      </template>
                    </el-table-column>
                  </el-table>
                </div>
              </el-tab-pane>
            </el-tabs>
          </div>

          <!-- SECTION 4: COMPUTATION -->
          <!-- <div class="form-section">
            <div class="section-header">
              <span class="section-icon">💰</span>
              <span class="section-title">Order Summary & Computation</span>
            </div>
            
            <div class="form-row">
              <div class="form-col">
                <el-form :model="computationForm" label-width="140px">
                  <el-form-item label="Discount Type">
                    <el-select v-model="computationForm.discountType" placeholder="Select type" class="full-width">
                      <el-option label="N/A" value="na" />
                      <el-option label="Senior Citizen" value="senior" />
                      <el-option label="PWD" value="pwd" />
                    </el-select>
                  </el-form-item>
                  <el-form-item label="Discount %">
                    <el-input :value="(computationForm.discountPercentage * 100).toFixed(0) + ' %'" disabled />
                  </el-form-item>
                  <el-form-item label="Home Service Fee">
                    <el-input :value="formatCurrency(computationForm.homeServiceFee)" disabled />
                  </el-form-item>
                </el-form>
              </div>
              <div class="form-col">
                <el-form :model="computationForm" label-width="140px">
                  <el-form-item label="Subtotal">
                    <el-input :value="formatCurrency(computationForm.orderedTest)" disabled />
                  </el-form-item>
                  <el-form-item label="Total Amount">
                    <el-input :value="formatCurrency(computationForm.totalAmount)" disabled class="total-amount-input" />
                  </el-form-item>
                </el-form>
              </div>
            </div>
          </div> -->

          <!-- SECTION 5: DISTRIBUTIONS -->
          <!-- <div class="form-section">
            <div class="section-header">
              <span class="section-icon">📨</span>
              <span class="section-title">Distributions</span>
            </div>
            <el-form :model="distributionForm" label-width="120px">
              <div class="form-row">
                <div class="form-col">
                  <el-form-item label="Copy">
                    <el-input v-model="distributionForm.copy" />
                  </el-form-item>
                </div>
                <div class="form-col">
                  <el-form-item label="Send">
                    <el-input v-model="distributionForm.send" />
                  </el-form-item>
                </div>
              </div>
              <div class="form-row">
                <div class="form-col">
                  <el-form-item label="Pickup">
                    <el-input v-model="distributionForm.pickup" />
                  </el-form-item>
                </div>
                <div class="form-col">
                  <el-form-item label="Fax">
                    <el-input v-model="distributionForm.fax" />
                  </el-form-item>
                </div>
              </div>
              <div class="form-row">
                <div class="form-col">
                  <el-form-item label="Mail">
                    <el-input v-model="distributionForm.mail" />
                  </el-form-item>
                </div>
                <div class="form-col">
                  <el-form-item label="Email">
                    <el-input v-model="distributionForm.email" />
                  </el-form-item>
                </div>
              </div>
              <div class="form-row">
                <div class="form-col" style="grid-column: span 2;">
                  <el-form-item label="Send To">
                    <el-input v-model="distributionForm.sendTo" />
                  </el-form-item>
                </div>
              </div>
            </el-form>
          </div> -->

        </div>
      </div>

      <!-- Modal Footer with Actions -->
      <template #footer>
        <div class="modal-footer">
          <div class="footer-info">
             <!-- Optional footer info -->
          </div>
          <div class="footer-actions">
            <el-button @click="$emit('update:modelValue', false); handleClose()" class="btn-cancel">
              <el-icon><Close /></el-icon>
              Cancel
            </el-button>
            <el-button
              type="primary"
              @click="handleSave"
              :loading="submitting"
              class="btn-submit"
            >
              <el-icon><Check /></el-icon>
              Save Order
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </Teleport>
</template>

<script setup>
import { ref, defineProps, defineEmits, computed, watch, onMounted } from 'vue';
import { Check, Close, Search } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import api from '@/utils/axios';
import { format, parseISO, differenceInYears, differenceInMonths, differenceInDays } from 'date-fns';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true
  },
  initialPatient: {
    type: Object,
    default: null,
  }
});

const emit = defineEmits(['update:modelValue', 'save', 'search-patient', 'request-created']);

const activeRoutine = ref('routine');
const submitting = ref(false);

// Loading states for tests
const loadingImagingServices = ref(false);
const loadingPackages = ref(false);
const loadingSpecialTests = ref(false);
const loadingProcedures = ref(false);

// Refs for test data
const imagingServices = ref([]);
const packages = ref([]);
const specialTests = ref([]);
const procedures = ref([]);

// Refs for selected test rows
const selectedImagingServices = ref([]);
const selectedPackages = ref([]);
const selectedSpecialTests = ref([]);
const selectedProcedures = ref([]);

const patientForm = ref({
  sampleId: '',
  patientId: '',
  last_name: '',
  first_name: '',
  middle_name: '',
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
  sample_id: '',
  healthcareProvider: '',
  healthcareType: 'Walk-In',
  homeService: false,
  isAR: false,
  isFOC: false,
  payee: 'cash',
  orNo: '',
  priorityLevel: 'Routine',
  referredBy: '',
  physician: '',
  patient_type_id: null,
  ward_id: null,
  room_id: null
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
  discountPercentage: 0,
  orderedTest: 0,
  homeServiceFee: 0,
  totalAmount: 0
});

// Doctors list
const doctors = ref([]);
const loadingDoctors = ref(false);

// Patient Types, Wards, Rooms
const patientTypes = ref([]);
const loadingPatientTypes = ref(false);
const wards = ref([]);
const loadingWards = ref(false);
const rooms = ref([]);
const loadingRooms = ref(false);

// Test categories
const testCategories = ref([]);
const loadingCategories = ref(false);
const activeCategory = ref(null);

// Tests by category
const testsByCategory = ref({});
const loadingTests = ref({});
const selectedTests = ref({});

// --- Fetch Functions ---

const fetchDoctors = async () => {
  loadingDoctors.value = true;
  try {
    const response = await api.get('/doctors');
    doctors.value = response.data.data || response.data; 
  } catch (error) {
    console.error('Error fetching doctors:', error);
  } finally {
    loadingDoctors.value = false;
  }
};

const fetchPatientTypes = async () => {
  loadingPatientTypes.value = true;
  try {
    const response = await api.get('/patient-types'); 
    patientTypes.value = response.data;
  } catch (error) {
    console.error('Error fetching patient types:', error);
  } finally {
    loadingPatientTypes.value = false;
  }
};

const fetchWards = async () => {
  loadingWards.value = true;
  try {
    const response = await api.get('/wards');
    wards.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching wards:', error);
  } finally {
    loadingWards.value = false;
  }
};

const fetchRooms = async (wardId) => {
  if (!wardId) {
    rooms.value = [];
    return;
  }
  loadingRooms.value = true;
  try {
    const response = await api.get('/get-rooms', { params: { ward_id: wardId } });
    rooms.value = response.data;
  } catch (error) {
    console.error('Error fetching rooms:', error);
    rooms.value = [];
  } finally {
    loadingRooms.value = false;
  }
};

const fetchTestCategories = async () => {
  loadingCategories.value = true;
  loadingImagingServices.value = true;
  loadingPackages.value = true;
  loadingSpecialTests.value = true;
  loadingProcedures.value = true;

  try {
    const response = await api.get('/get-test-categories');
    const allCategories = response.data;
    
    const routineCategories = [];
    
    allCategories.forEach(category => {
      const name = category.name.toLowerCase();
      
      if (name.includes('imaging') || name.includes('x-ray') || name.includes('ultrasound')) {
        imagingServices.value = category.tests || [];
      } else if (name.includes('package')) {
        packages.value = category.tests || [];
      } else if (name.includes('special')) {
        specialTests.value = category.tests || [];
      } else if (name.includes('procedure')) {
        procedures.value = category.tests || [];
      } else {
        routineCategories.push(category);
      }
    });

    testCategories.value = routineCategories;
    
    // Populate routine tests
    if (testCategories.value.length > 0) {
      activeCategory.value = testCategories.value[0].id;
      testCategories.value.forEach(cat => {
        testsByCategory.value[cat.id] = cat.tests || [];
      });
    }
  } catch (error) {
    ElMessage.error('Error fetching test categories');
    console.error('Error fetching test categories:', error);
  } finally {
    loadingCategories.value = false;
    loadingImagingServices.value = false;
    loadingPackages.value = false;
    loadingSpecialTests.value = false;
    loadingProcedures.value = false;
  }
};

const fetchTestsForCategory = async (categoryId) => {
  if (!categoryId) return;
  loadingTests.value[categoryId] = true;
  try {
    const response = await api.get(`/test-categories/${categoryId}/tests`);
    testsByCategory.value[categoryId] = response.data;
    if (!selectedTests.value[categoryId]) {
      selectedTests.value[categoryId] = [];
    }
  } catch (error) {
    ElMessage.error(`Error fetching tests for category ${categoryId}`);
    testsByCategory.value[categoryId] = [];
  } finally {
    loadingTests.value[categoryId] = false;
  }
};

const handleCategoryChange = async (categoryId) => {
  activeCategory.value = categoryId;
  if (!testsByCategory.value[categoryId]) {
    await fetchTestsForCategory(categoryId);
  }
};

// Fetch other services (Imaging, Packages, etc. - mocked or implemented as needed)
const fetchOtherServices = async () => {
  // Implement similar to fetchTestsForCategory if API exists
  // For now, placeholders or existing logic if any
};

// --- Handlers ---

const handlePatientSearch = () => {
  emit('search-patient');
};

const handleTestSelectionChange = (categoryId, selection) => {
  selectedTests.value[categoryId] = selection;
  calculateTotal();
};

const handleImagingSelectionChange = (selection) => {
  selectedImagingServices.value = selection;
  calculateTotal();
};

const handlePackageSelectionChange = (selection) => {
  selectedPackages.value = selection;
  calculateTotal();
};

const handleSpecialTestSelectionChange = (selection) => {
  selectedSpecialTests.value = selection;
  calculateTotal();
};

const handleProcedureSelectionChange = (selection) => {
  selectedProcedures.value = selection;
  calculateTotal();
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(value);
};

const calculateTotal = () => {
  let total = 0;
  
  // Sum from all categories
  Object.values(selectedTests.value).forEach(selection => {
    if (selection) {
      total += selection.reduce((sum, item) => sum + parseFloat(item.price), 0);
    }
  });

  // Sum from other tabs
  total += selectedImagingServices.value.reduce((sum, item) => sum + parseFloat(item.price), 0);
  total += selectedPackages.value.reduce((sum, item) => sum + parseFloat(item.price), 0);
  total += selectedSpecialTests.value.reduce((sum, item) => sum + parseFloat(item.price), 0);
  total += selectedProcedures.value.reduce((sum, item) => sum + parseFloat(item.price), 0);

  computationForm.value.orderedTest = total;
  
  // Apply discount
  const discountAmount = total * computationForm.value.discountPercentage;
  computationForm.value.totalAmount = total - discountAmount + computationForm.value.homeServiceFee;
};

watch(() => computationForm.value.discountType, (val) => {
  if (val === 'senior' || val === 'pwd') {
    computationForm.value.discountPercentage = 0.2;
  } else {
    computationForm.value.discountPercentage = 0;
  }
  calculateTotal();
});

// Watch discount type
watch(() => computationForm.value.discountType, (newType) => {
  if (newType === 'senior' || newType === 'pwd') {
    computationForm.value.discountPercentage = 0.20;
  } else {
    computationForm.value.discountPercentage = 0;
  }
  calculateTotal();
});

watch(() => computationForm.value.homeServiceFee, () => {
  calculateTotal();
});

watch(() => additionalForm.value.ward_id, (newWardId) => {
  additionalForm.value.room_id = null;
  if (newWardId) {
    fetchRooms(newWardId);
  } else {
    rooms.value = [];
  }
});

// Watch initialPatient prop
watch(() => props.initialPatient, (newPatient) => {
  if (newPatient) {
    patientForm.value.patientId = newPatient.id; // Or whatever ID field
    patientForm.value.last_name = newPatient.last_name;
    patientForm.value.first_name = newPatient.first_name;
    patientForm.value.middle_name = newPatient.middle_name;
    patientForm.value.sex = newPatient.gender;
    patientForm.value.dateOfBirth = newPatient.date_of_birth;
    patientForm.value.contactNo = newPatient.phone;
    patientForm.value.email = newPatient.email;
    patientForm.value.address = newPatient.address;
    patientForm.value.civilStatus = newPatient.civil_status;
    
    // Calculate Age
    if (newPatient.date_of_birth) {
       const dob = parseISO(newPatient.date_of_birth);
       const today = new Date();
       patientForm.value.age = differenceInYears(today, dob);
    }
  }
}, { immediate: true });


const handleSave = async () => {
  if (!patientForm.value.patientId) {
    ElMessage.warning('Please select a patient first.');
    return;
  }
  
  submitting.value = true;
  try {
    // Construct payload
    const allSelectedTests = [];
    Object.values(selectedTests.value).forEach(list => {
      if(list) allSelectedTests.push(...list);
    });
    allSelectedTests.push(...selectedImagingServices.value);
    allSelectedTests.push(...selectedPackages.value);
    allSelectedTests.push(...selectedSpecialTests.value);
    allSelectedTests.push(...selectedProcedures.value);

    if (allSelectedTests.length === 0) {
      ElMessage.warning('Please select at least one test.');
      submitting.value = false;
      return;
    }

    const payload = {
      patient_id: patientForm.value.patientId,
      sample_id: additionalForm.value.sample_id,
      doctor_id: additionalForm.value.physician,
      priority_level: additionalForm.value.priorityLevel,
      payment_type: additionalForm.value.payee,
      patient_type_id: additionalForm.value.patient_type_id,
      ward_id: additionalForm.value.ward_id,
      room_id: additionalForm.value.room_id,
      or_no: additionalForm.value.orNo,
      healthcare_provider: additionalForm.value.healthcareProvider,
      healthcare_type: additionalForm.value.healthcareType,
      home_service: additionalForm.value.homeService,
      is_ar: additionalForm.value.isAR,
      is_foc: additionalForm.value.isFOC,
      discount_type: computationForm.value.discountType,
      discount_percentage: computationForm.value.discountPercentage,
      home_service_fee: computationForm.value.homeServiceFee,
      total_amount: computationForm.value.totalAmount,
      test_items: allSelectedTests.map(t => t.id),
      distribution: {
        copy: distributionForm.value.copy,
        send: distributionForm.value.send,
        pickup: distributionForm.value.pickup,
        fax: distributionForm.value.fax,
        mail: distributionForm.value.mail,
        email: distributionForm.value.email,
        send_to: distributionForm.value.sendTo
      },
      notes: ''
    };

    const response = await api.post('/laboratory-requests', payload);
    ElMessage.success('Request created successfully');
    emit('request-created', response.data);
    emit('update:modelValue', false);
    handleClose();
  } catch (error) {
    console.error('Error creating request:', error);
    ElMessage.error(error.response?.data?.message || 'Failed to create request');
  } finally {
    submitting.value = false;
  }
};

const handleClose = () => {
  // Reset forms if needed
  selectedTests.value = {};
  selectedImagingServices.value = [];
  // ... reset other selections
  calculateTotal();
};

const fetchNextSampleId = async () => {
  try {
    const response = await api.get('/laboratory-requests/generate-sample-id');
    additionalForm.value.sample_id = response.data.sample_id;
  } catch (error) {
    console.error('Error fetching sample ID:', error);
    ElMessage.error('Failed to generate Accession ID');
  }
};

onMounted(() => {
  fetchDoctors();
  fetchPatientTypes();
  fetchWards();
  fetchTestCategories();
  // fetchOtherServices();
  fetchNextSampleId();
});

watch(() => props.modelValue, (val) => {
  if (val) {
    fetchNextSampleId();
  }
});

</script>

<style scoped>
.equipment-modal :deep(.el-dialog__header) {
  padding: 0;
  border-bottom: none;
}

.equipment-modal :deep(.el-dialog__body) {
  padding: 0;
  height: calc(100vh - 150px); /* Adjust based on header/footer */
  overflow: hidden; /* Let body content handle scroll */
}

/* Modal Header Brand */
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-md);
  padding: var(--spacing-md) var(--spacing-xl);
  background: var(--color-dark);
  color: var(--color-text-light);
  margin: -20px -20px 0 -20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: var(--border-radius-md) var(--border-radius-md) 0 0;
}

.modal-header-brand {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  font-weight: var(--font-weight-semibold);
  letter-spacing: 0.3px;
}

.modal-header-icon {
  font-size: var(--font-size-xl);
}

.modal-header-title {
  font-size: var(--font-size-sm);
  opacity: 0.9;
  text-transform: uppercase;
  white-space: nowrap;
}

.modal-header-actions {
  display: flex;
  gap: var(--spacing-sm);
}

.modal-header-actions .el-button {
  color: var(--color-text-light);
}

.modal-header-actions .el-button--danger {
  background-color: var(--color-danger);
  border-color: var(--color-danger);
}

.modal-header-actions .el-button--danger:hover {
  background-color: var(--color-danger-dark);
  border-color: var(--color-danger-dark);
}

/* Modal Body Content */
.modal-body-content {
  height: 100%;
  overflow-y: auto;
  padding-right: 5px; /* Prevent scrollbar overlapping content */
}

.equipment-form {
  background-color: var(--color-bg-primary);
  padding: var(--spacing-xl);
  border-radius: var(--border-radius-lg);
  border: 1px solid var(--color-border-secondary);
}

/* Form Sections */
.form-section {
  margin-bottom: var(--spacing-2xl);
}

.form-section:last-child {
  margin-bottom: 0;
}

.section-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-md);
  border-bottom: 2px solid var(--color-primary);
}

.section-icon {
  font-size: var(--font-size-xl);
  opacity: 0.8;
}

.section-title {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
  letter-spacing: 0.3px;
  text-transform: uppercase;
}

/* Form Grid */
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-lg);
  margin-bottom: var(--spacing-md);
}

@media (max-width: 1200px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}

.form-col {
  display: flex;
  flex-direction: column;
}

.full-width {
  width: 100%;
}

.equipment-form :deep(.el-form-item) {
  margin-bottom: 0;
}

.equipment-form :deep(.el-form-item__label) {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: var(--color-text-secondary);
  line-height: 32px;
  padding-right: var(--spacing-md);
}

.equipment-form :deep(.el-input__wrapper),
.equipment-form :deep(.el-select),
.equipment-form :deep(.el-date-editor) {
  border: 1px solid var(--color-border-primary);
  border-radius: var(--border-radius-md);
  transition: all var(--transition-normal);
}

.equipment-form :deep(.el-input__wrapper:hover),
.equipment-form :deep(.el-select:hover),
.equipment-form :deep(.el-date-editor:hover) {
  border-color: var(--color-text-tertiary);
}

.equipment-form :deep(.el-input__wrapper:focus-within),
.equipment-form :deep(.el-select:focus-within),
.equipment-form :deep(.el-date-editor:focus-within) {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px var(--color-primary-light);
}

.search-input-wrapper {
  width: 100%;
}

.table-container {
  border: 1px solid var(--color-border-primary);
  border-radius: var(--border-radius-md);
  overflow: hidden;
}

.total-amount-input :deep(.el-input__inner) {
  font-weight: bold;
  color: var(--color-primary);
  font-size: 1.1em;
}

/* Modal Footer */
.modal-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-lg) var(--spacing-xl);
  background-color: var(--color-bg-secondary);
  border-top: 1px solid var(--color-border-secondary);
  gap: var(--spacing-md);
}

.footer-info {
  flex: 1;
}

.footer-actions {
  display: flex;
  gap: var(--spacing-sm);
  justify-content: flex-end;
}

.btn-cancel,
.btn-submit {
  padding: 8px var(--spacing-xl);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  border-radius: var(--border-radius-md);
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all var(--transition-normal);
}

.btn-cancel:hover,
.btn-submit:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-cancel {
  background-color: var(--color-bg-tertiary);
  border-color: var(--color-border-primary);
  color: var(--color-text-secondary);
}

.btn-cancel:hover {
  background-color: #e5e7eb;
  border-color: var(--color-text-tertiary);
}

.btn-submit {
  background-color: var(--color-primary);
  border-color: var(--color-primary);
  color: var(--color-text-light);
}

.btn-submit:hover {
  background-color: var(--color-primary-dark);
  border-color: var(--color-primary-dark);
}

.btn-submit:disabled {
  background-color: var(--color-border-primary);
  border-color: var(--color-border-primary);
  color: var(--color-text-disabled);
  cursor: not-allowed;
}

.btn-submit:disabled:hover {
  transform: none;
  box-shadow: none;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  .modal-footer {
    flex-direction: column;
  }
  .footer-actions {
    width: 100%;
    justify-content: stretch;
  }
}
</style>
