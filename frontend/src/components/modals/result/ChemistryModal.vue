<template>
  <Teleport to="#app">
<el-dialog
  :model-value="props.visible"
  :title="'Chemistry Result Window'"
  :fullscreen="true"
  :show-close="true"
  class="hematology-modal"
  @close="handleClose"
  v-loading="props.loading || parametersLoading"
>
  <!-- Top Action Bar -->
  <div class="action-bar">
    <div class="action-buttons">
      <el-button type="success" @click="handlePreview">
        <el-icon><View /></el-icon>
        Preview (F1)
      </el-button>
      <el-button type="warning" @click="triggerSave" :disabled="disableSaveButton">
        <el-icon><Document /></el-icon>
        Save (F2)
      </el-button>
      <el-button type="primary" @click="handlePrintRelease" :disabled="disablePrintButton">
        <el-icon><Printer /></el-icon>
        Print & Release (F4)
      </el-button>
      <el-button type="primary" @click="handleValidateRelease" :disabled="disableValidateButton">
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
              <el-input :model-value="props.requestDetails.sample_id" readonly/>
        </el-form-item>
        <el-form-item label="Patient ID:">
              <el-input :model-value="props.requestDetails.patient?.id" readonly>
            <template #suffix>
              <el-icon><User /></el-icon>
            </template>
          </el-input>
        </el-form-item>
        <el-form-item label="Patient Name:">
              <el-input :model-value="props.requestDetails.patient?.last_name + ', ' + props.requestDetails.patient?.first_name + (props.requestDetails.patient?.middle_name ? ' ' + props.requestDetails.patient?.middle_name : ' ')" readonly/>
        </el-form-item>
        <el-form-item label="Date of Birth:">
          <el-date-picker
            :model-value="props.requestDetails.patient?.date_of_birth" readonly
            type="date"
                style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="Age:">
          <el-input :model-value="calculateAge(props.requestDetails.patient?.date_of_birth)" readonly>
            <template #suffix>{{ suffixUnit }}</template>
          </el-input>
        </el-form-item>
          </div>

          <!-- Column 2 -->
          <div style="flex: 1">
            <el-form-item label="Sex:">
              <el-input :model-value="props.requestDetails.patient?.gender" readonly/>
            </el-form-item>
        <el-form-item label="Civil Status:">
              <el-input :model-value="props.requestDetails.patient?.civil_status" readonly/>
        </el-form-item>
        <el-form-item label="Address:">
              <el-input :model-value="props.requestDetails.patient?.address" readonly/>
        </el-form-item>
        <el-form-item label="Contact No.:">
              <el-input :model-value="props.requestDetails.patient?.phone" readonly/>
        </el-form-item>
        <el-form-item label="Email Address:">
              <el-input :model-value="props.requestDetails.patient?.email" readonly/>
        </el-form-item>
          </div>
      </el-form>
      </div>

      <!-- Additional Details Section -->
      <div class="section additional-details">
      <div class="section-title">Additional Details</div>
        <el-form label-position="left" label-width="120px" class="compact-form">
          <div style="flex: 1">
            <el-form-item label="Priority:">
              <el-input :model-value="props.requestDetails.priority_level" readonly/>
            </el-form-item>
            <el-form-item label="Received Date Time:">
          <el-date-picker
                    :model-value="formatDateTime(props.requestDetails.request_date)"
            type="datetime"
            readonly
            style="width: 100%"
          />
        </el-form-item>
            <el-form-item label="Requesting Doctor:">
              <el-input :model-value="props.requestDetails.doctor?.name" readonly
            style="width: 100%"
          />
        </el-form-item>
            <el-form-item label="Referring Doctor:">
              <el-input :model-value="props.requestDetails.referring_doctor" readonly
                style="width: 100%"/>
        </el-form-item>
          </div>
          <div style="flex: 1">
                <el-form-item label="Patient Type:">
              <el-input :model-value="props.requestDetails.patient_type?.name" readonly/>
        </el-form-item>
            <el-form-item label="Ward:">
                  <el-input :model-value="props.requestDetails.ward?.name" readonly/>
        </el-form-item>
            <el-form-item label="Room:"> 
                  <el-input :model-value="props.requestDetails.room?.name" readonly/>
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
            Retrive Re-run
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
        :data="formattedTestResults" 
        style="width: 100%"
        row-key="id"
        @selection-change="handleSelectionChange"
        size="small"
        class="compact-table"
      >
        <el-table-column type="selection" width="40" :selectable="() => true" />
        <el-table-column label="Test Name / Parameter" min-width="180">
          <template #default="{ row }">
            <span>
              {{ row.parameterName || `Parameter ${row.parameterId}` }}
            </span>
          </template>
          </el-table-column>
        <el-table-column label="Flag" width="60" align="center" >
           <template #default="{ row }">
             <span :class="getFlagClass(row.flag)">{{ row.flag ? `(${row.flag})` : '' }}</span>
           </template>
        </el-table-column>
        <el-table-column label="S.I. Unit" align="center">
          <el-table-column label="Result" width="110" align="center">
            <template #default="{ row }">
              <el-input
                v-if="row.isParameter"
                v-model="row.resultValue"
                @change="updateResultValue(row, $event)"
                size="small"
                placeholder="Enter result"
              />
              <span v-else></span>
            </template>
        </el-table-column>
          <el-table-column prop="unit" label="Units" width="90" align="center" >
          </el-table-column>
          <el-table-column label="Reference Range" min-width="120" align="center">
          <template #default="{ row }">
              <div>
                <span :class="{ 'range-undefined': row.referenceRange === 'Not defined' || row.referenceRange === 'N/A' }">
                  {{ row.referenceRange }}
                </span>
              </div>
            </template>
          </el-table-column>
        </el-table-column>
        <el-table-column label="Conventional Unit" align="center">
          <el-table-column label="Result" width="90" align="center">
            <template #default="{ row }">
              <span>{{ row.conventional }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="units" label="Units" width="90" align="center" />
          <el-table-column label="Reference Range" min-width="120" align="center">
            <template #default="{ row }">
              <div>
                <span :class="{ 'range-undefined': row.refRange === 'Not defined' || row.refRange === 'N/A' }">
                  {{ row.refRange }}
                </span>
              </div>
            </template>
          </el-table-column>
        </el-table-column>
        <el-table-column label="Instrument/Analyzer" width="200" align="center">
          <template #default="{ row }">
            <div v-if="row.isParameter">
              <el-select 
                v-model="row.instrument" 
                style="width: 100%" 
                size="small"
                filterable 
                placeholder="Select Analyzer"
                @change="(value) => handleInstrumentChange(row, value)"
                :loading="loadingAnalyzers"
              >
                <el-option 
                  v-for="analyzer in analyzers" 
                  :key="analyzer.id" 
                  :label="analyzer.name" 
                  :value="analyzer.id" 
                />
            </el-select>
            </div>
            <span v-else></span>
          </template>
        </el-table-column>
        <el-table-column label="Remarks" width="250" align="center">
          <template #default="{ row }">
            <el-input
              v-if="row.isParameter" 
              v-model="row.resultComment" 
              @input="() => updateParameterComment(row)"
              placeholder="Remarks" 
              size="small" 
            />
            <span v-else></span>
          </template>
        </el-table-column>
      </el-table>

      <div class="total-dif">Total DIF Count: 0</div>
    </div>
    <el-alert
      v-if="qcHoldBannerMessage"
      type="error"
      :closable="false"
      show-icon
      class="mb-2"
      title="QC Lockout Active"
      :description="qcHoldBannerMessage"
    />
  <!-- Moved remarks, comments, and signatories to bottom -->
  <div class="bottom-container">
    <div class="top-section">
      <div class="remarks-panel">
        <div class="panel-label">Remarks</div>
        <el-input
            v-model="localRemarks"
          type="textarea"
          :rows="3"
          placeholder="RESULTS VERIFIED"
        />
      </div>
      <div class="comments-panel">
        <div class="panel-label">Comment</div>
        <el-input
            v-model="localComments"
          type="textarea"
          :rows="3"
          placeholder="Enter comments..."
        />
      </div>
    </div>
      <div class="signatories-section">
        <div class="section-title">Signatories</div>
        <el-form label-position="left" label-width="120px" class="section-signatories">
          <el-form-item label="Performed By:">
            <el-select 
              v-model="localSignatories.performedBy" 
              style="width: 100%" 
              filterable 
              placeholder="Select Performer"
              :loading="loadingRoles"
            >
              <el-option v-for="user in loadedPerformers" :key="user.id" :label="user.name" :value="user.id" />
            </el-select>
          </el-form-item>
          <el-form-item label="Verified By:">
            <el-select 
              v-model="localSignatories.verifiedBy" 
              style="width: 100%" 
              filterable 
              placeholder="Select Verifier"
              :loading="loadingRoles"
            >
              <el-option v-for="user in loadedVerifiers" :key="user.id" :label="user.name" :value="user.id" />
            </el-select>
          </el-form-item>
          <el-form-item label="Pathologist:">
            <el-select 
              v-model="localSignatories.pathologist" 
              style="width: 100%" 
              filterable 
              placeholder="Select Pathologist"
              :loading="loadingRoles"
            >
              <el-option v-for="user in loadedPathologists" :key="user.id" :label="user.name" :value="user.id" />
            </el-select>
          </el-form-item>
        </el-form>
      </div>
    </div>
  </div>
</el-dialog>
</Teleport>
</template>

<script setup>
/**
 * BUTTON STATE MANAGEMENT SYSTEM
 * 
 * This component uses computed properties to directly control button states:
 * - disableSaveButton → Save button
 * - disablePrintButton → Print & Release button
 * - disableValidateButton → Validate & Release button
 * 
 * These computed properties automatically react to changes in test parameters
 * and test statuses. No additional reactive variables are used for button states.
 */
import { ref, computed, reactive, watch, onMounted, onUnmounted, nextTick } from 'vue';
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
import { format, parseISO } from 'date-fns';
import api from '@/utils/axios';
import { ElMessage } from 'element-plus';
import { differenceInYears, differenceInMonths, differenceInDays } from 'date-fns';


const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  requestDetails: {
    type: Object,
    default: () => ({ tests: [], patient: {}, performer_id: null, verifier_id: null, pathologist_id: null, remarks: '', comment: '' }),
  },
  loading: {
    type: Boolean,
    default: false,
  },
  verifiers: {
    type: Array,
    default: () => [],
  },
  performers: {
    type: Array,
    default: () => [],
  },
  pathologists: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['update:visible', 'close', 'save', 'validate', 'print']);

const localSignatories = ref({ performedBy: null, verifiedBy: null, pathologist: null });
const localRemarks = ref('');
const localComments = ref('');
const resultFields = ref([]);
const testParameters = ref({});
// QC banner state and updater
const qcHoldBannerMessage = ref('');
const updateQCHoldBanner = () => {
  try {
    const involvedAnalyzerIds = Array.from(new Set(
      Object.values(testParameters.value)
        .flat()
        .map(p => (p.analyzer_id ?? p.instrument))
        .filter(id => id != null)
        .map(id => parseInt(id))
    ));
    if (involvedAnalyzerIds.length > 0 && testCategoryId.value) {
      api.get(`/qc-lockout/status-summary`, { params: { test_category_id: testCategoryId.value } })
        .then(({ data }) => {
          const failing = (data?.analyzers || []).filter(a => involvedAnalyzerIds.includes(a.id) && a.on_hold);
          if (failing.length > 0) {
            const lines = failing.map(a => `${a.name || 'Analyzer'}: ${(a.violations || []).join(', ')}`);
            qcHoldBannerMessage.value = lines.join(' | ');
          } else {
            qcHoldBannerMessage.value = '';
          }
        })
        .catch(() => { /* keep previous banner */ });
    } else {
      qcHoldBannerMessage.value = '';
    }
  } catch (_) {}
};
const parametersLoading = ref(false);
const analyzers = ref([]);
const loadingAnalyzers = ref(false);

// Arrays to store role-specific users fetched from the API
const loadedPathologists = ref([]);
const loadedVerifiers = ref([]);
const loadedPerformers = ref([]);
const loadingRoles = ref(false);

// Remove hardcoded constant and replace with reactive variable
const testCategoryId = ref(null);

// Define loadAnalyzers before it's used in any watcher
const loadAnalyzers = async (testCategoryId = null) => {
  loadingAnalyzers.value = true;
  try {
    let endpoint = '/analyzers';
    
    // If a test category ID is provided, use the filtered endpoint
    if (testCategoryId) {
      endpoint = `/analyzers/by-test-category/${testCategoryId}`;
      console.log(`Loading analyzers for test category: ${testCategoryId}`);
    } else {
      console.log('Loading all analyzers (no category filter)');
    }
    
    const response = await api.get(endpoint);
    analyzers.value = response.data;
    console.log('Loaded analyzers:', analyzers.value);
  } catch (error) {
    console.error('Error loading analyzers:', error);
    ElMessage.error('Failed to load analyzers');
  } finally {
    loadingAnalyzers.value = false;
  }
};

// Define convertAllSIValues before it's used in any watcher
const convertAllSIValues = () => {
  console.log('Converting all SI values to conventional...');
  
  // Loop through all tests and their parameters
  Object.keys(testParameters.value).forEach(testId => {
    const params = testParameters.value[testId];
    if (Array.isArray(params)) {
      params.forEach(param => {
        if (param.resultValue && !isNaN(parseFloat(param.resultValue)) && param.conversion_factor) {
          // Calculate conventional value
          const numericValue = parseFloat(param.resultValue);
          const conversionFactor = parseFloat(param.conversion_factor);
          param.conventional = (numericValue * conversionFactor).toFixed(2);
          console.log(`Auto-converted ${param.name}: ${param.resultValue} ${param.unit} → ${param.conventional} ${param.conventional_unit}`);
        }
      });
    }
  });
};

// Simple function to force reactive updates of button states
const forceButtonUpdate = () => {
  console.log('Forcing button state update...');
  
  // Log current parameter state for debugging
  const allParams = Object.values(testParameters.value).flat();
  const paramsWithValues = allParams.filter(param => 
    param && param.resultValue && param.resultValue.trim() !== ''
  );
  const savedParams = paramsWithValues.filter(param => param.result_id !== null);
  const unsavedParams = paramsWithValues.filter(param => param.result_id === null);
  
  console.log(`Button state calculation:
    - Total parameters: ${allParams.length}
    - Parameters with values: ${paramsWithValues.length}
    - Saved parameters: ${savedParams.length}
    - Unsaved parameters: ${unsavedParams.length}
  `);

  // Manually compute expected button states
  const shouldDisableSave = unsavedParams.length === 0;
  const shouldDisablePrint = unsavedParams.length > 0;
  const shouldDisableValidate = unsavedParams.length > 0 || 
    props.requestDetails.tests.every(test => test.pivot && test.pivot.item_status === 'Result Printed');

  console.log(`Expected button states:
    - Save button should be disabled: ${shouldDisableSave}
    - Print button should be disabled: ${shouldDisablePrint}
    - Validate button should be disabled: ${shouldDisableValidate}
  `);

  // Force Vue to re-evaluate the computed properties
  nextTick(() => {
    console.log('Actual button states after update:');
    console.log('- Save button disabled:', disableSaveButton.value);
    console.log('- Print button disabled:', disablePrintButton.value);
    console.log('- Validate button disabled:', disableValidateButton.value);
  });
};

// Fetch users by role type
const fetchUsersByRole = async (roleType) => {
  try {
    const response = await api.get(`/users/role/${roleType}`);
    return response.data;
  } catch (error) {
    console.error(`Error fetching ${roleType}s:`, error);
    ElMessage.error(`Failed to load ${roleType}s`);
    return [];
  }
};

// Function to load all role-specific users
const loadRoleUsers = async () => {
  loadingRoles.value = true;
  try {
    // First load users by role type
    const [pathologists, verifiers, performers] = await Promise.all([
      fetchUsersByRole('pathologist'),
      fetchUsersByRole('verifier'),
      fetchUsersByRole('performer')
    ]);
    
    loadedPathologists.value = pathologists;
    loadedVerifiers.value = verifiers;
    loadedPerformers.value = performers;
    
    console.log('Loaded pathologists:', loadedPathologists.value);
    console.log('Loaded verifiers:', loadedVerifiers.value);
    console.log('Loaded performers:', loadedPerformers.value);
    
    // If we have specific user IDs from the test records, ensure they're loaded
    // This is for when users might not be in the role-specific lists
    const userIdsToLoad = [];
    
    // Skip IDs that are already in our loaded lists
    if (localSignatories.value.performedBy && 
        !loadedPerformers.value.some(user => user.id === localSignatories.value.performedBy)) {
      userIdsToLoad.push(localSignatories.value.performedBy);
    }
    
    if (localSignatories.value.verifiedBy && 
        !loadedVerifiers.value.some(user => user.id === localSignatories.value.verifiedBy)) {
      userIdsToLoad.push(localSignatories.value.verifiedBy);
    }
    
    if (localSignatories.value.pathologist && 
        !loadedPathologists.value.some(user => user.id === localSignatories.value.pathologist)) {
      userIdsToLoad.push(localSignatories.value.pathologist);
    }
    
    // If we have unique IDs to load
    if (userIdsToLoad.length > 0) {
      const uniqueIds = [...new Set(userIdsToLoad)];
      console.log('Loading additional users by ID:', uniqueIds);
      
      // Fetch additional users by ID
      try {
        const userPromises = uniqueIds.map(id => api.get(`/users/${id}`));
        const userResponses = await Promise.all(userPromises);
        
        // Add fetched users to appropriate lists
        userResponses.forEach(response => {
          const user = response.data;
          if (!user || !user.id) return;
          
          // Check if this user ID matches any of our signatories
          if (user.id === localSignatories.value.performedBy) {
            // Add to performers if not already there
            if (!loadedPerformers.value.some(p => p.id === user.id)) {
              loadedPerformers.value.push(user);
              console.log(`Added performer ${user.name} to performers list`);
            }
          }
          
          if (user.id === localSignatories.value.verifiedBy) {
            // Add to verifiers if not already there
            if (!loadedVerifiers.value.some(v => v.id === user.id)) {
              loadedVerifiers.value.push(user);
              console.log(`Added verifier ${user.name} to verifiers list`);
            }
          }
          
          if (user.id === localSignatories.value.pathologist) {
            // Add to pathologists if not already there
            if (!loadedPathologists.value.some(p => p.id === user.id)) {
              loadedPathologists.value.push(user);
              console.log(`Added pathologist ${user.name} to pathologists list`);
            }
          }
        });
      } catch (error) {
        console.error('Error loading users by ID:', error);
      }
    }
  } catch (error) {
    console.error('Error loading role users:', error);
    ElMessage.error('Failed to load signatories');
  } finally {
    loadingRoles.value = false;
  }
};

// Import the calculate age method from the parent component
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
      return `${years}`;
    } else if (months >= 1) {
      return `${months}`;
    } else {
      return `${days}`;
    }
  } catch (e) {
    console.error('Error calculating age:', e);
    return 'Invalid Date';
  }
};

// Compute the suffix unit based on the patient's age
const suffixUnit = computed(() => {
  if (!props.requestDetails?.patient?.date_of_birth) return 'years';
  
  const birthDate = parseISO(props.requestDetails.patient.date_of_birth);
  const today = new Date();
  
  const years = differenceInYears(today, birthDate);
  const months = differenceInMonths(today, birthDate);
  
  if (years >= 1) {
    return years === 1 ? 'year' : 'years';
  } else if (months >= 1) {
    return months === 1 ? 'month' : 'months';
  } else {
    return 'days';
  }
});

// Helper function to determine the correct parameter name
const getParameterName = (param, test) => {
  // Log the parameter object to debug
  console.log('Getting name for parameter:', param);
  
  // First try to get name directly from param
  if (param.name && typeof param.name === 'string' && param.name.trim() !== '') {
    return param.name.trim();
  }
  
  // Next try test_name
  if (param.test_name && typeof param.test_name === 'string' && param.test_name.trim() !== '') {
    return param.test_name.trim();
  }
  
  // Try the testParameter relationship if it exists
  if (param.testParameter && param.testParameter.name) {
    return param.testParameter.name.trim();
  }
  
  // If all else fails, use the parameter ID
  return `Parameter ${param.id}`;
};

// Fetch parameters for a single test
const fetchTestParameters = async (testId, resultsDataSource = null) => {
  if (!testId) return;
  
  console.log(`Log 4: fetchTestParameters called for testId: ${testId} with resultsDataSource:`, JSON.parse(JSON.stringify(resultsDataSource)));
  
  try {
    // Get the parameters for this test
    const response = await api.get(`/tests/${testId}/parameters`);
    
    if (!Array.isArray(response.data)) {
      console.error('Invalid response data format (not an array):', response.data);
      testParameters.value[testId] = [];
      return;
    }
    
    console.log(`Received ${response.data.length} parameters for test ID ${testId}:`, response.data);
    console.log('First parameter sample:', {
      id: response.data[0]?.id,
      name: response.data[0]?.name,
      conversion_factor: response.data[0]?.conversion_factor,
      unit: response.data[0]?.unit,
      conventional_unit: response.data[0]?.conventional_unit
    });

    let needsMoreDetails = false;
    for (const param of response.data) {
      if (param.test_name && (!param.name || param.name.trim() === '')) {
        param.name = param.test_name;
      }
      if ((!param.name || param.name.trim() === '') && param.id) {
        needsMoreDetails = true;
        break;
      }
    }

    if (needsMoreDetails) {
      console.log('Some parameters missing names, getting more details...');
      try {
        // Pass the resultsDataSource to fetchParametersWithDetails
        await fetchParametersWithDetails(testId, response.data, resultsDataSource);
        return; 
      } catch (detailsError) {
        console.error('Failed to get parameter details, falling back to basic parameters:', detailsError);
      }
    }

    // Determine the source for saved results
    const sourceForSavedResults = resultsDataSource || props.requestDetails.testParameters || [];
    console.log(`Log 5: testId: ${testId} - sourceForSavedResults chosen:`, resultsDataSource ? "resultsDataSource (passed in)" : "props.requestDetails.testParameters (fallback)");
    console.log(`Log 5.1: testId: ${testId} - content of sourceForSavedResults:`, JSON.parse(JSON.stringify(sourceForSavedResults)));

    const savedResults = sourceForSavedResults?.[testId] || [];
    console.log(`Log 6: testId: ${testId} - savedResults extracted for this testId:`, JSON.parse(JSON.stringify(savedResults)));
    
    const savedResultsMap = {};
    if (Array.isArray(savedResults)) {
      savedResults.forEach(result => {
        if (result && result.id) {
          savedResultsMap[result.id] = result;
        }
      });
    }
    
    testParameters.value[testId] = response.data
      .filter(param => param && param.id)
      .map(param => {
        const savedResult = savedResultsMap[param.id];
        // Conditionally log savedResult to avoid JSON error with undefined
        if (savedResult !== undefined) {
          console.log(`Log 7: testId: ${testId}, ParamDef ID: ${param.id} - Found savedResult for merging:`, JSON.parse(JSON.stringify(savedResult)));
        } else {
          console.log(`Log 7: testId: ${testId}, ParamDef ID: ${param.id} - No savedResult found for merging (savedResult is undefined).`);
        }
        const paramObj = {
          id: param.id,
          name: param.name || param.test_name || '',
          test_name: param.test_name || param.name || `Parameter ${param.id}`,
          resultValue: savedResult ? savedResult.resultValue || '' : '',
          unit: param.unit || '',
          instrument: savedResult ? savedResult.instrument || null : null,
          resultComment: savedResult ? (savedResult.resultComment || '') : '',
          flag: savedResult ? savedResult.flag || null : null,
          referenceRanges: Array.isArray(param.reference_ranges) ? param.reference_ranges : [],
          conventional: savedResult ? savedResult.conventional || '' : '',
          conventional_unit: param.conventional_unit || '',
          conversion_factor: param.conversion_factor || null,
          result_id: savedResult && savedResult.result_id ? savedResult.result_id : null
        };
        // ... (log for param state)
        return paramObj;
      });
      
    console.log(`Processed ${testParameters.value[testId].length} parameters for test ID ${testId}`, testParameters.value[testId]);
  } catch (error) {
    console.error(`Error fetching parameters for test ${testId}:`, error);
    testParameters.value[testId] = [];
  }
};

// Fetch parameters with more details - alternative API call
const fetchParametersWithDetails = async (testId, basicParameters = [], resultsDataSource = null) => {
  try {
    const response = await api.get(`/test-parameters?test_id=${testId}&include=details`);
    
    if (Array.isArray(response.data) && response.data.length > 0) {
      console.log('Got detailed parameters:', response.data);
      
      // Determine the source for saved results
      const sourceForSavedResults = resultsDataSource || props.requestDetails.testParameters;
      const savedResults = sourceForSavedResults?.[testId] || [];
      
      const savedResultsMap = {};
      if (Array.isArray(savedResults)) {
        savedResults.forEach(result => {
          if (result && result.id) {
            savedResultsMap[result.id] = result;
          }
        });
      }
      
      const basicParamMap = {};
      basicParameters.forEach(param => {
        if (param && param.id) {
          basicParamMap[param.id] = param;
        }
      });
      
      testParameters.value[testId] = response.data
        .filter(param => param && param.id)
        .map(param => {
          const savedResult = savedResultsMap[param.id];
          const basicParam = basicParamMap[param.id];
          // ... (rest of the mapping logic, ensure it uses savedResult correctly for values)
          const paramObj = {
            id: param.id,
            name: param.name || (basicParam ? basicParam.name : '') || (basicParam ? basicParam.test_name : '') || '',
            test_name: param.test_name || param.name || (basicParam ? basicParam.test_name : '') || (basicParam ? basicParam.name : '') || `Parameter ${param.id}`,
            resultValue: savedResult ? savedResult.resultValue || '' : '',
            unit: param.unit || (basicParam ? basicParam.unit : '') || '',
            instrument: savedResult ? savedResult.instrument || null : null,
            resultComment: savedResult ? savedResult.resultComment || '' : '',
            flag: savedResult ? savedResult.flag || null : null,
            referenceRanges: Array.isArray(param.reference_ranges) ? param.reference_ranges : 
                           (basicParam && Array.isArray(basicParam.reference_ranges) ? 
                            basicParam.reference_ranges : []),
            conventional: savedResult ? savedResult.conventional || '' : '',
            conventional_unit: param.conventional_unit || (basicParam ? basicParam.conventional_unit : '') || '',
            conversion_factor: param.conversion_factor || (basicParam ? basicParam.conversion_factor : null),
            result_id: savedResult && savedResult.result_id ? savedResult.result_id : null
          };
        // ... (log for param state)
          return paramObj;
        });
        
      console.log(`Processed ${testParameters.value[testId].length} detailed parameters for test ID ${testId}`);
    } else {
      console.warn('No detailed parameters found, using basic parameters');
      throw new Error('No detailed parameters available');
    }
  } catch (error) {
    console.error(`Error fetching detailed parameters for test ${testId}:`, error);
    throw error; 
  }
};

// Fetch parameters for all tests in the current request
const fetchAllTestParameters = async (tests, resultsDataSource = null) => {
  if (!tests || tests.length === 0) {
      console.log("No tests provided to fetchAllTestParameters");
      return Promise.resolve(); 
  }
  console.log("Starting fetchAllTestParameters for tests:", tests, "using resultsDataSource:", resultsDataSource);
  parametersLoading.value = true; // Corrected
  testParameters.value = {};
  
  try {
    const promises = tests.map(test => fetchTestParameters(test.id, resultsDataSource));
    await Promise.all(promises);
    console.log("All parameters fetched:", JSON.parse(JSON.stringify(testParameters.value)));
  } catch (error) {
     console.error("Error fetching one or more test parameters:", error);
     ElMessage.error('Failed to load parameters for some tests.');
  } finally {
    parametersLoading.value = false;
  }
  
  return Promise.resolve();
};

const formatDateTime = (data) => {
  if (!data) return 'N/A';
  try {
    return format(parseISO(data), 'yyyy-MM-dd p');
  } catch (e) {
    return 'Invalid Date';
  }
};

// Add try/catch to the first watch function
watch(() => props.requestDetails, async (newDetails) => {
  try {
    if (newDetails && newDetails.tests && Array.isArray(newDetails.tests)) {
      console.log("Request Details updated:", newDetails);
      
      // Additional debug logging for testParameters
      if (newDetails.testParameters) {
        console.log("DEBUG - Examining testParameters structure:");
        Object.keys(newDetails.testParameters).forEach(testId => {
          const params = newDetails.testParameters[testId];
          console.log(`Test ${testId} has ${params.length} parameters:`);
          params.forEach(param => {
            console.log(`Parameter ${param.id}: resultValue = ${param.resultValue}, resultComment = ${param.resultComment}, result_id = ${param.result_id}`);
          });
        });
      }
      
      // Initialize result fields from tests
      resultFields.value = newDetails.tests.map(test => ({
        id: test.id,
        pivot_id: test.pivot?.id,
        testName: test.name,
        unit: test.unit || '',
        referenceRange: test.reference_range || '',
        conventional_unit: test.conventional_unit || '',
        conventional_reference_range: test.conventional_reference_range || '',
        initial_resultValue: test.initial_resultValue || '',
        initial_instrument: test.initial_instrument || null,
        initial_resultComment: test.initial_resultComment || '',
        // Track if the test already has a stored result
        result_id: test.result_id || null
      }));
      
      // Debug logging for non-parameterized tests
      console.log("DEBUG - Non-parameterized test initial values:");
      resultFields.value.forEach(test => {
        if (test.initial_resultValue || test.initial_resultComment) {
          console.log(`Test ${test.id}: result_value = ${test.initial_resultValue}, result_comment = ${test.initial_resultComment}, result_id = ${test.result_id}`);
        }
      });

      // Initialize remarks and comments from request details if available
      localRemarks.value = newDetails.remarks || 'RESULTS VERIFIED';
      localComments.value = newDetails.comment || '';
      
      // Initialize signatories from tests - we need to check any test for the values
      // since they're now stored at the laboratory_request_test level
      if (newDetails.tests.length > 0) {
        // Find first test with performer, verifier, or pathologist info
        const testWithTrackingInfo = newDetails.tests.find(test => 
          test.performer_id || test.verifier_id || test.pathologist_id
        );
        
        console.log("Test with tracking info:", testWithTrackingInfo);
        
        if (testWithTrackingInfo) {
          localSignatories.value = {
            performedBy: testWithTrackingInfo.performer_id || null,
            verifiedBy: testWithTrackingInfo.verifier_id || null,
            pathologist: testWithTrackingInfo.pathologist_id || null
          };
          console.log("Initialized signatories:", localSignatories.value);
        } else {
          // Fallback to old structure if pivot data not available
          localSignatories.value = {
            performedBy: newDetails.performer_id || null,
            verifiedBy: newDetails.verifier_id || null,
            pathologist: newDetails.pathologist_id || null
          };
        }
      }

      if (newDetails.tests.length > 0) {
        // For initial load/prop update, pass newDetails.testParameters as the resultsDataSource
        await fetchAllTestParameters(newDetails.tests, newDetails.testParameters);
        
        // IMPORTANT: Preserve result_ids for parameters that have values and are already saved
        console.log("Checking parameters with values to determine if they're already saved...");
        
        // Get all parameters with values
        let allParams = Object.values(testParameters.value).flat();
        let paramsWithValues = allParams.filter(param => 
          param && param.resultValue && param.resultValue.trim() !== ''
        );
        
        // Check if all parameters with values have result_ids (meaning they're already saved)
        let allSaved = paramsWithValues.every(param => param.result_id !== null);
        let anyUnsaved = paramsWithValues.some(param => param.result_id === null);
        
        console.log(`Parameter save status: 
          - Parameters with values: ${paramsWithValues.length}
          - All saved: ${allSaved}
          - Any unsaved: ${anyUnsaved}
        `);
        
        // Run detailed debug
        debugParameterStates();
        
        // After parameters are loaded, convert any existing values to conventional
        convertAllSIValues();
        
        // Force button state update
        forceButtonUpdate();
      } else {
        console.log("Watcher: Tests array is empty, skipping parameter fetch.");
        testParameters.value = {};
        parametersLoading.value = false;
      }
    } else {
      if (!newDetails) {
        console.log("Watcher: newDetails is null or undefined.");
      } else if (!newDetails.tests) {
        console.log("Watcher: newDetails.tests is null or undefined.");
      } else if (!Array.isArray(newDetails.tests)) {
        console.log("Watcher: newDetails.tests is not an array. Type:", typeof newDetails.tests, "Value:", newDetails.tests);
      }

      resultFields.value = [];
      localRemarks.value = 'RESULTS VERIFIED';
      localComments.value = '';
      localSignatories.value = { performedBy: null, verifiedBy: null, pathologist: null };
      testParameters.value = {};
      parametersLoading.value = false;
    }
  } catch (error) {
    console.error("Error in requestDetails watcher:", error);
    // Handle errors but don't crash
  }
}, { immediate: true, deep: true });

// Add try/catch to the second watch function
watch(() => props.requestDetails, (newDetails) => {
  try {
    if (newDetails && newDetails.tests && Array.isArray(newDetails.tests) && newDetails.tests.length > 0) {
      // Log the structure of the test data to help debug
      console.log('First test from request details:', newDetails.tests[0]);
      
      // Get the category ID from the first test
      const firstTest = newDetails.tests[0];
      
      // Try different possible paths to find the test_category_id
      let categoryId = null;
      
      if (firstTest.test_category_id) {
        categoryId = firstTest.test_category_id;
        testCategoryId.value = categoryId;
        console.log(`Found category ID directly on test: ${categoryId}`);
      } else if (firstTest.testCategory && firstTest.testCategory.id) {
        categoryId = firstTest.testCategory.id;
        testCategoryId.value = categoryId;
        console.log(`Found category ID via testCategory relation: ${categoryId}`);
      } else {
        // In production hematology service, we could hardcode a known ID from your database
        testCategoryId.value = null;
        console.log('Could not find test category ID, loading all analyzers');
      }
      
      if (categoryId) {
        loadAnalyzers(categoryId);
      } else {
        // Fallback to loading all analyzers
        loadAnalyzers();
      }
    } else {
      console.log('No tests found in request details, loading all analyzers');
      loadAnalyzers();
    }
  } catch (error) {
    console.error("Error in analyzer loading watcher:", error);
    // Try to load all analyzers as a fallback
    loadAnalyzers();
  }
}, { immediate: true });

// --- Patient Age Calculation ---
const calculatePatientAge = (dob) => {
  if (!dob) return { years: null, months: null, days: null, monthsTotal: null, daysTotal: null };
  try {
    const birthDate = new Date(dob);
    const today = new Date();
    const years = differenceInYears(today, birthDate);
    const monthsTotal = differenceInMonths(today, birthDate);
    const daysTotal = differenceInDays(today, birthDate);
    let months = today.getMonth() - birthDate.getMonth();
    let days = today.getDate() - birthDate.getDate();
    if (months < 0 || (months === 0 && days < 0)) {
    }
    if (days < 0) {
      months--;
      const lastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
      days += lastMonth.getDate();
    }
     if (months < 0) {
       months += 12;
     }

    return { years, months, days, monthsTotal, daysTotal };
  } catch (e) {
    console.error("Error calculating age:", e);
    return { years: null, months: null, days: null, monthsTotal: null, daysTotal: null };
  }
};

const patientAge = computed(() => calculatePatientAge(props.requestDetails?.patient?.date_of_birth));
const patientGender = computed(() => props.requestDetails?.patient?.gender || 'Any');

// --- Helper to calculate Conventional Value ---
const calculateConventionalValue = (siValue, factor) => {
  if (siValue === null || siValue === '' || factor === null || factor === undefined) {
    return ''; // Cannot calculate if SI value or factor is missing
  }
  const si = parseFloat(siValue);
  const f = parseFloat(factor);
  if (isNaN(si) || isNaN(f)) {
    return 'ERR'; // Error if values are not numeric
  }
  // Perform calculation and round to a reasonable number of decimal places
  const conventional = si * f;
  // Basic rounding - adjust precision as needed
  return Number(conventional.toFixed(2)).toString();
};

// --- Helper function for flag CSS class (needed in template) ---
const getFlagClass = (flag) => {
    if (flag === 'L' || flag === 'H') return 'flag-critical';
    if (flag === 'l' || flag === 'h') return 'flag-abnormal';
    return '';
};

// Helper function to generate table rows for printing
const generatePrintTableRows = () => {
    return formattedTestResults.value.map(result => {
        if (!result.isParameter) return '';
        return `
            <tr>
                <td>${result.parameterName}</td>
                <td>${result.resultValue}</td>
                <td>${result.unit}</td>
                <td>${result.referenceRange}</td>
                <td class="${getFlagClass(result.flag)}">${result.flag || ''}</td>
            </tr>
        `;
    }).join('');
};

// Helper function to get performer name
const getPerformerName = () => {
    return loadedPerformers.value.find(p => p.id === localSignatories.value.performedBy)?.name || 'N/A';
};

// Helper function to get pathologist name
const getPathologistName = () => {
    return loadedPathologists.value.find(p => p.id === localSignatories.value.pathologist)?.name || 'N/A';
};

// --- Computed property for table display with improved reference range handling ---
const formattedTestResults = computed(() => {
  if (!resultFields.value || resultFields.value.length === 0) return [];

  const currentPatientAge = calculatePatientAge(props.requestDetails?.patient?.date_of_birth);
  const currentPatientGender = props.requestDetails?.patient?.gender || 'Any';
  
  // Array to hold the formatted results for the table
  const results = [];
  
  // Iterate through all tests
  resultFields.value.forEach(test => {
    if (!test || !test.id) return;
    
    // Get parameters for this test
    const params = testParameters.value[test.id] || [];
    
    console.log(`Processing parameters for test ${test.id} - ${test.testName}:`, params);
    
    if (params.length > 0) {
      // Process each parameter
      params.forEach(param => {
        if (!param || !param.id) return;
        
        // Debug log each parameter with result_comment value
        console.log(`Processing parameter ${param.id} for UI:`, {
          name: param.name,
          test_name: param.test_name,
          resultValue: param.resultValue,
          resultComment: param.resultComment // <-- Log the result_comment value
        });
        
        // Find applicable reference range
        let applicableRange = null;
        
        try {
          // Find an applicable reference range based on analyzer, age, gender
          if (param.referenceRanges && param.referenceRanges.length > 0) {
            // First try to match by analyzer if available
            if (param.instrument) {
              const analyzerMatches = param.referenceRanges.filter(range => 
                range && ((range.analyzer_id && range.analyzer_id === param.instrument) || 
                (range.analyzer && range.analyzer === param.instrument))
              );
              
              if (analyzerMatches.length > 0) {
                // Try to find age/gender match within analyzer matches
                applicableRange = analyzerMatches.find(range => {
                  if (!range || range.age_min === undefined || range.age_max === undefined) return false;
                  
                  const ageMatch = currentPatientAge.years >= range.age_min && 
                                   currentPatientAge.years <= range.age_max;
                  const genderMatch = !range.gender || range.gender === 'Any' || 
                                     range.gender === currentPatientGender;
                                     
                  return ageMatch && genderMatch;
                });
                
                // If no specific age/gender match, use the first analyzer match
                if (!applicableRange) {
                  applicableRange = analyzerMatches[0];
                }
              }
            }
            
            // If no analyzer match, try just age/gender match
            if (!applicableRange) {
              applicableRange = param.referenceRanges.find(range => {
                if (!range || range.age_min === undefined || range.age_max === undefined) return false;
                
                const ageMatch = currentPatientAge.years >= range.age_min && 
                               currentPatientAge.years <= range.age_max;
                const genderMatch = !range.gender || range.gender === 'Any' || 
                                  range.gender === currentPatientGender;
                                  
                return ageMatch && genderMatch;
              });
            }
            
            // If still no match, use the first range
            if (!applicableRange && param.referenceRanges.length > 0) {
              applicableRange = param.referenceRanges[0];
            }
          }
        } catch (error) {
          console.error('Error finding applicable range:', error);
        }
        
        // Create fallback range object if none found
        if (!applicableRange) {
          applicableRange = {
            si_unit: param.unit || '',
            si_range: 'N/A',
            low_value: null,
            high_value: null,
            conv_unit: param.conventional_unit || '',
            conv_range: 'N/A',
            conv_low: null,
            conv_high: null
          };
        }
        
        // Add parameter to results array
        results.push({
          id: `param-${param.id}`,
          isParameter: true,
          isTestHeader: false,
          testId: test.id,
          parameterId: param.id,
          parameterName: getParameterName(param, test), // Use the helper function
          flag: param.flag || null,
          resultValue: param.resultValue || '',
          unit: (applicableRange.si_unit) || param.unit || '',
          referenceRange: applicableRange.si_range || 
                         ((applicableRange.low_value !== null && applicableRange.high_value !== null) ?
                          `${applicableRange.low_value} - ${applicableRange.high_value}` : 'N/A'),
          conventional: param.conventional || '',
          units: applicableRange.conv_unit || param.conventional_unit || '',
          refRange: applicableRange.conv_range || 
                   ((applicableRange.conv_low !== null && applicableRange.conv_high !== null) ?
                    `${applicableRange.conv_low} - ${applicableRange.conv_high}` : 'N/A'),
          instrument: param.instrument || null,
          resultComment: param.resultComment || '', // Ensure resultComment is never null or undefined
          analyzerName: param.analyzer_name || ''
        });
      });
    } else {
      // Non-parameterized test - check if it has a result value
      if (test.initial_resultValue && test.initial_resultValue.trim() !== '') {
        // Log non-parameterized test data for debugging
        console.log(`Non-parameterized test data for ${test.id} for UI:`, {
          test_id: test.id,
          result_value: test.initial_resultValue,
          unit: test.unit,
          analyzer_id: test.initial_instrument,
          result_comment: test.initial_resultComment, // <-- Log the result_comment value
          has_result_id: !!test.result_id
        });
        
        const resultEntry = {
          test_id: test.id,
          remarks: localRemarks.value,  // Add remarks at the test level
          comment: localComments.value  // Add comment at the test level
        };
        
        resultEntry.result_value = test.initial_resultValue.trim();
        resultEntry.unit = test.unit ? test.unit.trim() : '';
        resultEntry.analyzer_id = test.initial_instrument;
        resultEntry.result_comment = test.initial_resultComment || '';
        
        // Include the result_id if available to help backend identify existing records
        if (test.result_id) {
          resultEntry.result_id = test.result_id;
        }
        
        results.push(resultEntry);
      }
    }
  });
  
  console.log('Final formattedTestResults:', results);
  return results;
});

const handleClose = () => {
  emit('update:visible', false);
  emit('close');
};

const triggerSave = async () => {
    const payload = preparePayload();
    
    // If no payload (no new results to save), return early
    if (!payload) {
        console.log("No parameters to save, skipping save operation");
        ElMessage.info("No changes to save");
        return;
    }
    
    console.log("================================================================");
    console.log("FULL SAVE PAYLOAD:", JSON.stringify(payload, null, 2));
    console.log("================================================================");
    
    if (!props.requestDetails?.id) {
        ElMessage.error("Cannot save results: Request ID is missing.");
        return;
    }
    
    try {
        const response = await api.post(`/laboratory-requests/${props.requestDetails.id}/results`, payload);
        console.log("Save POST response:", JSON.parse(JSON.stringify(response.data))); 
        ElMessage.success("Results saved successfully");

        let latestResultsDataSource = null;

        if (response.data && Array.isArray(response.data.results) && response.data.results.length > 0) {
            console.log("Attempting to transform POST response results for immediate UI refresh. Full results array:", JSON.parse(JSON.stringify(response.data.results)));
            const transformedResults = {};
            response.data.results.forEach(item => {
                // Access test_parameter_id from the nested test_parameter object
                const testParameterId = item.test_parameter?.id;
                console.log(`Processing item from POST response: test_id = ${item.test_id}, test_parameter.id = ${testParameterId}`, item);
                
                if (item.test_id == null || testParameterId == null) { 
                    console.warn("Skipping result item due to missing (null/undefined) test_id or test_parameter.id. Actual values:", { test_id: item.test_id, test_parameter_id_from_nested: testParameterId }, item);
                    return;
                }

                if (!transformedResults[item.test_id]) {
                    transformedResults[item.test_id] = [];
                }

                // Calculate conventional value for the transformed object
                const siValue = item.result_value;
                const conversionFactorValue = item.test_parameter?.conversion_factor;
                let conventionalValue = ''; 

                if (siValue && siValue.trim() !== '' && conversionFactorValue != null) {
                    const numericSI = parseFloat(siValue);
                    const numericFactor = parseFloat(conversionFactorValue);
                    if (!isNaN(numericSI) && !isNaN(numericFactor)) {
                        conventionalValue = (numericSI * numericFactor).toFixed(2);
                    }
                }

                const paramResultObj = {
                    id: testParameterId,             
                    result_id: item.id,             
                    resultValue: item.result_value,
                    unit: item.unit || item.test_parameter?.unit || '', 
                    instrument: item.analyzer_id,
                    resultComment: item.result_comment || item.test_parameter?.result_comment || '',
                    flag: item.flag || null,
                    name: item.test_parameter?.name || item.test_parameter?.test_name || '',
                    test_name: item.test_parameter?.test_name || item.test_parameter?.name || '',
                    conventional_unit: item.test_parameter?.conventional_unit || '',
                    conversion_factor: conversionFactorValue, // Use the retrieved factor
                    referenceRanges: Array.isArray(item.test_parameter?.reference_ranges) ? item.test_parameter.reference_ranges : [],
                    conventional: conventionalValue, // Assign calculated conventional value
                };
                transformedResults[item.test_id].push(paramResultObj);
            });
            latestResultsDataSource = transformedResults;
            console.log("Transformed POST results into latestResultsDataSource:", JSON.parse(JSON.stringify(latestResultsDataSource)));
        } else {
            // Fallback to fetching from GET if POST response is not as expected, though this is our problematic path
            console.warn("POST response did not contain usable results. Falling back to GET for latest state (might be stale).");
            const latestRequest = await api.get(`/laboratory-requests/${props.requestDetails.id}`);
            console.log("Full latestRequest.data after save (fallback path):", JSON.parse(JSON.stringify(latestRequest.data)));
            if (latestRequest.data && latestRequest.data.testParameters) { // Still check for the ideal field from GET
                latestResultsDataSource = latestRequest.data.testParameters;
                console.log("Using latestResultsDataSource from GET (fallback path):", JSON.parse(JSON.stringify(latestResultsDataSource)));
            } else {
                console.warn("No testParameters in GET response either (fallback path). latestResultsDataSource remains null.");
            }
        }

        // Always re-fetch all parameters, passing the LATEST results (ideally from POST transform) as the dataSource
        await fetchAllTestParameters(props.requestDetails.tests, latestResultsDataSource);
        convertAllSIValues(); // Explicitly call to recalculate all conventional values after data refresh
        forceButtonUpdate();

        // Optionally, verify button states after save
        nextTick(() => {
            // Count parameters with values
            const allParams = Object.values(testParameters.value).flat();
            const paramsWithValues = allParams.filter(param => 
                param && param.resultValue && param.resultValue.trim() !== ''
            );
            const unsavedParams = paramsWithValues.filter(param => param.result_id === null);
            
            console.log(`Button state verification after save:\n    - Parameters with values: ${paramsWithValues.length}\n    - Unsaved parameters: ${unsavedParams.length}\n    - Save button should be disabled: ${unsavedParams.length === 0}\n    - Print/Validate buttons should be enabled: ${unsavedParams.length === 0}`);
        });

        // Update the status in the laboratory request (this updates the list view)
        api.put(`/laboratory-requests/${props.requestDetails.id}/status`, {
            status: 'Processing Results',
            test_category_id: testCategoryId.value  // Use dynamic category ID
        }).then(() => {
            console.log("Updated laboratory request status to Processing Results");
        }).catch(error => {
            console.error("Error updating laboratory request status:", error);
        });

        // Emit save event to parent
        emit('save', props.requestDetails.id, payload);
    } catch (error) {
        console.error("Error saving results:", error);
        ElMessage.error(`Failed to save results: ${error.response?.data?.message || error.message}`);
    }
};

const handleValidateRelease = () => {
  // Check button state first
  if (disableValidateButton.value) {
      // Get specific reason for disabling
      const allTestParams = Object.values(testParameters.value).flat();
      const allSaved = allTestParams.every(param => param.result_id !== null);
      const allPrinted = props.requestDetails.tests.every(test => 
        test.pivot && test.pivot.item_status === 'Result Printed'
      );
      
      if (allPrinted) {
          ElMessage.warning("Tests are already in Printed status and cannot be validated again.");
      } else {
          ElMessage.warning("Please save all results before validating and releasing");
      }
      return;
  }

  const proceedValidate = () => {
      const payload = {
          ...preparePayload(),
          status: 'validated'
      };

      console.log("Prepared Validate Payload:", payload);

      if (!props.requestDetails?.id) {
          ElMessage.error("Cannot validate results: Request ID is missing.");
          return;
      }

      // Call the API to validate and save the results
      api.put(`/laboratory-requests/${props.requestDetails.id}/results`, payload)
          .then(response => {
              console.log("Validate response:", response.data);
              
              // Update the laboratory_request_test status to 'Validated'
              return api.put(`/laboratory-requests/${props.requestDetails.id}/status`, {
                  status: 'Result Validated',
                  test_category_id: testCategoryId.value  // Use dynamic category ID
              });
  updateQCHoldBanner();
          })
          .then(() => {
              ElMessage.success("Results validated successfully");
              
              // Refresh test statuses to ensure UI is up-to-date
              refreshTestStatuses();
              
              // Emit an event to notify the parent component
              emit('validate', props.requestDetails.id, payload);

              handleClose();
          })
          .catch(error => {
              console.error("Error validating results:", error);
              const status = error.response?.status;
              const data = error.response?.data || {};
              if (status === 403 && data.error_type === 'qc_lockout') {
                  const issues = data.issues || [];
                  const lines = issues.map(i => {
                      const analyzer = i.analyzer_name || `Analyzer #${i.analyzer_id}`;
                      const param = i.test_parameter_name || `Parameter #${i.test_parameter_id}`;
                      const vio = Array.isArray(i.violations) ? i.violations.join(', ') : i.violations;
                      return `${analyzer} → ${param}: ${vio}`;
                  });
                  ElMessageBox.alert(
                      `QC HOLD: Some results run on a failing analyzer are unreliable.\n\n` +
                      lines.join('\n'),
                      'QC Lockout Active',
                      { type: 'error', showClose: false }
                  );
              } else {
                  ElMessage.error(`Failed to validate results: ${data.message || error.message}`);
              }
          });
  };

  // Pre-check analyzer QC hold before hitting backend
  try {
      const involvedAnalyzerIds = Array.from(new Set(
          Object.values(testParameters.value)
              .flat()
              .map(p => p.analyzer_id)
              .filter(id => id != null)
      ));
      if (involvedAnalyzerIds.length > 0 && testCategoryId.value) {
          api.get(`/qc-lockout/status-summary`, { params: { test_category_id: testCategoryId.value } })
              .then(({ data }) => {
                  const failing = (data?.analyzers || []).filter(a => involvedAnalyzerIds.includes(a.id) && a.on_hold);
                  if (failing.length > 0) {
                      const lines = failing.map(a => `${a.name || 'Analyzer'}: ${(a.violations || []).join(', ')}`);
                      ElMessageBox.alert(
                          `QC HOLD: Analyzer QC is out of control for this request.\n\n` +
                          lines.join('\n'),
                          'QC Lockout Active',
                          { type: 'error', showClose: false }
                      );
                      return; // Do not proceed
                  }
                  proceedValidate();
              })
              .catch(() => proceedValidate());
          return; // Defer
      }
  } catch (_) {}

  proceedValidate();
  updateQCHoldBanner();
  
};

const handlePrintRelease = () => {
  // Check button state first
  if (disablePrintButton.value) {
      ElMessage.warning("Please save all results before printing and releasing");
      return;
  }

  const proceedPrint = () => {
      // First create a payload similar to the save payload but with 'completed' status
      const payload = {
          ...preparePayload(),
          status: 'completed' // Set status to 'completed' instead of 'released'
      };

      if (!payload) {
          ElMessage.error("No results to print and release.");
          return;
      }

      console.log("Prepared Print & Release Payload:", payload);

      if (!props.requestDetails?.id) {
          ElMessage.error("Cannot print results: Request ID is missing.");
          return;
      }

      // Show printing dialog
      ElMessage({
          message: 'Printing CBC test results...',
          type: 'info',
          duration: 2000
      });

  // Check if tests are already printed directly using the same logic 
  // that's in the disableValidateButton computed property
  const alreadyPrinted = props.requestDetails.tests.every(test => 
      test.pivot && test.pivot.item_status === 'Result Printed'
  );

  // Call the API to release and save the results
  api.put(`/laboratory-requests/${props.requestDetails.id}/results`, payload)
      .then(response => {
          console.log("Print & Release response:", response.data);
          
          // Only update status if not already printed
          if (!alreadyPrinted) {
              // Update the laboratory_request_test status to 'Released'
              return api.put(`/laboratory-requests/${props.requestDetails.id}/status`, {
                  status: 'Result Printed',
                  test_category_id: testCategoryId.value  // Use dynamic category ID
              });
          }
          return Promise.resolve(); // Skip status update if already printed
      })
      .then(() => {
          const successMessage = alreadyPrinted 
              ? "Results printed successfully" 
              : "Results printed and released successfully";
              
          ElMessage.success(successMessage);
          
          // Refresh test statuses to ensure UI is up-to-date
          refreshTestStatuses();
          
          // Open a print dialog with sample CBC results
          const printWindow = window.open('', '_blank');
          if (printWindow) {
              printWindow.document.write(`
                  <html>
                  <head>
                      <title>Chemistry Test Results</title>
                      <style>
                          body { font-family: Arial, sans-serif; margin: 20px; }
                          .header { text-align: center; margin-bottom: 20px; }
                          .patient-info { margin-bottom: 20px; }
                          .info-row { display: flex; margin-bottom: 5px; }
                          .info-label { font-weight: bold; width: 150px; }
                          table { width: 100%; border-collapse: collapse; }
                          th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                          th { background-color: #f2f2f2; }
                          .footer { margin-top: 30px; }
                          .signatures { display: flex; justify-content: space-between; margin-top: 80px; }
                          .signature-line { border-top: 1px solid #000; width: 200px; text-align: center; padding-top: 5px; }
                          .flag-abnormal { color: var(--color-warning); font-weight: bold; }
                          .flag-critical { color: var(--color-danger); font-weight: bold; }
                      </style>
                  </head>
                  <body>
                      <div class="header">
                          <h2>HOSPITAL LABORATORY MANAGEMENT SYSTEM</h2>
                          <h3>CBC TEST RESULTS</h3>
                      </div>
                      
                      <div class="patient-info">
                          <div class="info-row">
                              <div class="info-label">Patient ID:</div>
                              <div>${props.requestDetails.patient?.id || 'N/A'}</div>
                          </div>
                          <div class="info-row">
                              <div class="info-label">Patient Name:</div>
                              <div>${props.requestDetails.patient?.last_name || ''}, ${props.requestDetails.patient?.first_name || ''} ${props.requestDetails.patient?.middle_name || ''}</div>
                          </div>
                          <div class="info-row">
                              <div class="info-label">Date of Birth:</div>
                              <div>${props.requestDetails.patient?.date_of_birth ? format(parseISO(props.requestDetails.patient?.date_of_birth), 'yyyy-MM-dd') : 'N/A'}</div>
                          </div>
                          <div class="info-row">
                              <div class="info-label">Age / Gender:</div>
                              <div>${calculateAge(props.requestDetails.patient?.date_of_birth)} / ${props.requestDetails.patient?.gender || 'N/A'}</div>
                          </div>
                      </div>
                      
                      <div class="test-info">
                          <h3>Test Information</h3>
                          <div class="info-row">
                              <div class="info-label">Sample ID:</div>
                              <div>${props.requestDetails.sample_id || 'N/A'}</div>
                          </div>
                          <div class="info-row">
                              <div class="info-label">Collection Date:</div>
                              <div>${props.requestDetails.request_date ? format(parseISO(props.requestDetails.request_date), 'yyyy-MM-dd HH:mm') : 'N/A'}</div>
                          </div>
                          <div class="info-row">
                              <div class="info-label">Requesting Doctor:</div>
                              <div>${props.requestDetails.doctor?.name || 'N/A'}</div>
                          </div>
                      </div>
                      
                      <h3>Test Results</h3>
                      <table>
                          <thead>
                              <tr>
                                  <th>Parameter</th>
                                  <th>Result</th>
                                  <th>Unit</th>
                                  <th>Reference Range</th>
                                  <th>Flag</th>
                              </tr>
                          </thead>
                          <tbody>
                              ${generatePrintTableRows()}
                          </tbody>
                      </table>
                      
                      <div class="signatures">
                          <div class="signature">
                              <div class="signature-line">
                                  ${getPerformerName()}
                              </div>
                              <div>Medical Technologist</div>
                          </div>
                          <div class="signature">
                              <div class="signature-line">
                                  ${getPathologistName()}
                              </div>
                              <div>Pathologist</div>
                          </div>
                      </div>
                      
                      <div style="margin-top: 30px; text-align: center;">
                          <p>This is a computer-generated report and does not require a signature.</p>
                          <p>Date Printed: ${format(new Date(), 'yyyy-MM-dd HH:mm')}</p>
                      </div>
                  </body>
                  </html>
              `);
              
              // Trigger the print dialog
              printWindow.document.close();
              printWindow.focus();
              printWindow.print();
              
              // Close the window after printing
              setTimeout(() => {
                  printWindow.close();
              }, 1000);
          } else {
              ElMessage.warning("Unable to open print preview window. Please check your browser settings.");
          }
          
          // Emit event to refresh the parent component
          emit('print', props.requestDetails.id, payload);

          handleClose();
      })
      .catch(error => {
          console.error("Error printing and releasing results:", error);
          ElMessage.error(`Failed to print and release results: ${error.response?.data?.message || error.message}`);
      });
  };

  // Pre-check analyzer QC hold before printing
  try {
      const involvedAnalyzerIds = Array.from(new Set(
          Object.values(testParameters.value)
              .flat()
              .map(p => p.analyzer_id)
              .filter(id => id != null)
      ));
      if (involvedAnalyzerIds.length > 0 && testCategoryId.value) {
          api.get(`/qc-lockout/status-summary`, { params: { test_category_id: testCategoryId.value } })
              .then(({ data }) => {
                  const failing = (data?.analyzers || []).filter(a => involvedAnalyzerIds.includes(a.id) && a.on_hold);
                  if (failing.length > 0) {
                      const lines = failing.map(a => `${a.name || 'Analyzer'}: ${(a.violations || []).join(', ')}`);
                      ElMessageBox.alert(
                          `QC HOLD: Printing is blocked until QC passes for the analyzer.\n\n` +
                          lines.join('\n'),
                          'QC Lockout Active',
                          { type: 'error', showClose: false }
                      );
                      return; // Block printing
                  }
                  proceedPrint();
              })
              .catch(() => proceedPrint());
          return; // Defer
      }
  } catch (_) {}

  proceedPrint();
  updateQCHoldBanner();
};

const handlePreview = () => {
  console.log('Preview triggered');
  ElMessage.info('Preview function not yet implemented.');
};

const handleSelectionChange = (selection) => {
  console.log('Selection changed:', selection);
};

const updateResultValue = (row, value) => {
  if (!row.isParameter || !row.testId || !row.parameterId) return;
  
  // Find the parameter in testParameters and update it directly
  const testParams = testParameters.value[row.testId];
  if (!testParams) return;
  
  const paramToUpdate = testParams.find(p => p.id === row.parameterId);
  if (!paramToUpdate) return;
  
  // Track if this is a new value or a modified value
  const hadPreviousValue = paramToUpdate.resultValue && paramToUpdate.resultValue.trim() !== '';
  
  // Update the value in the source
  paramToUpdate.resultValue = row.resultValue;
  const hasNewValue = paramToUpdate.resultValue && paramToUpdate.resultValue.trim() !== '';
  
  // Always mark as unsaved when any value is entered or changed
  if (hasNewValue) {
    if (paramToUpdate.result_id !== null) {
      console.log(`Marking parameter ${paramToUpdate.id} as unsaved due to value change`);
    }
    paramToUpdate.result_id = null; // Mark as needing to be saved
  }
  // If value is cleared, also mark as unsaved
  if (!hasNewValue) {
    paramToUpdate.result_id = null;
  }
  // Force Vue reactivity by replacing the array reference
  testParameters.value[row.testId] = [...testParameters.value[row.testId]];
  // Force button state update after changing value
  forceButtonUpdate();
  
  // Process only if we have a value
  if (paramToUpdate.resultValue && !isNaN(parseFloat(paramToUpdate.resultValue))) {
    const numericValue = parseFloat(paramToUpdate.resultValue);
    
    // Calculate conventional value locally using conversion_factor
    if (paramToUpdate.conversion_factor && !isNaN(parseFloat(paramToUpdate.conversion_factor))) {
      const conversionFactor = parseFloat(paramToUpdate.conversion_factor);
      paramToUpdate.conventional = (numericValue * conversionFactor).toFixed(2);
      console.log(`Calculated conventional value: ${paramToUpdate.conventional} using factor: ${conversionFactor}`);
    } else {
      console.log('No conversion factor available for parameter:', {
        id: paramToUpdate.id,
        name: paramToUpdate.name,
        conversion_factor: paramToUpdate.conversion_factor
      });
      paramToUpdate.conventional = '';
    }
    
    // Call API only for flag calculation
    api.post('/results/calculate-derived', {
      result: numericValue,
      unit: paramToUpdate.unit || row.unit,
      test_parameter_id: row.parameterId, 
      patient_id: props.requestDetails?.patient?.id
    }).then(response => {
      // Only update flag from API response
      paramToUpdate.flag = response.data.flag;
      console.log('Updated flag after API call:', paramToUpdate.flag);
      // Force Vue reactivity by replacing the array reference
      testParameters.value[row.testId] = [...testParameters.value[row.testId]];
      // Force button state update after flag calculation (in case of async changes)
      forceButtonUpdate();
    }).catch(error => {
      console.error('API Error:', error);
      paramToUpdate.flag = 'ERR';
      testParameters.value[row.testId] = [...testParameters.value[row.testId]];
      forceButtonUpdate();
    });
  } else {
    // Clear values if input is empty or invalid
    paramToUpdate.flag = null;
    paramToUpdate.conventional = '';
    testParameters.value[row.testId] = [...testParameters.value[row.testId]];
    forceButtonUpdate();
  }
};

const updateParameterComment = (row) => {
  if (!row.isParameter || !row.testId || !row.parameterId) return;
  
  const testParams = testParameters.value[row.testId];
  if (!testParams) {
    console.error(`updateParameterComment: No test parameters found for test ID ${row.testId}`);
    return;
  }
  
  const paramIndex = testParams.findIndex(p => p.id === row.parameterId);
  if (paramIndex === -1) {
    console.error(`updateParameterComment: Parameter with ID ${row.parameterId} not found in test ${row.testId}`);
    return;
  }

  const paramToUpdate = testParams[paramIndex];

  // Explicitly sync the row.resultComment (updated by v-model) to the paramToUpdate object
  // This ensures the source data in testParameters.value is updated.
  if (paramToUpdate.resultComment !== row.resultComment) {
    paramToUpdate.resultComment = row.resultComment;
    console.log(`Synced row.resultComment to paramToUpdate.resultComment for param ${paramToUpdate.id}`);
  }

  let markedUnsaved = false;
  if (paramToUpdate.result_id !== null) { // If it was previously saved
    paramToUpdate.result_id = null;
    markedUnsaved = true;
    console.log(`Marking parameter ${paramToUpdate.id} (testId: ${row.testId}) as unsaved due to comment change on a previously saved item.`);
  } else if (paramToUpdate.resultValue && paramToUpdate.resultValue.trim() !== '' && paramToUpdate.resultComment && paramToUpdate.resultComment.trim() !== '') {
    // If it has a value, and now a comment, and was already unsaved, it remains unsaved (result_id is already null)
    // No change to result_id needed, but Save button logic will see it as an unsaved item with content.
  } else if (paramToUpdate.resultComment && paramToUpdate.resultComment.trim() !== ''){
    // If it only has a comment (no resultValue yet) and was previously no-result-id (e.g. pristine)
    paramToUpdate.result_id = null; // Stays unsaved, but this confirms it for Save button logic
    markedUnsaved = true; // Consider it a change that needs saving
    console.log(`Marking parameter ${paramToUpdate.id} (testId: ${row.testId}) as unsaved due to new comment on an item without a value/prior save.`);
  }
  
  // Create a new array for the specific test to ensure reactivity for that part of testParameters.value
  // This is important if child properties of objects within an array are changed.
  const newTestParamsArray = [...testParams];
  testParameters.value[row.testId] = newTestParamsArray;
  
  forceButtonUpdate();
  
  console.log(`updateParameterComment: Parameter ${row.parameterId} (testId: ${row.testId}) resultComment is now: "${paramToUpdate.resultComment}". Marked unsaved: ${markedUnsaved}. Current result_id: ${paramToUpdate.result_id}`);
};

const preparePayload = () => {
    // Build the results array format needed by the API
    const results = [];

    resultFields.value.forEach(test => {
        const params = testParameters.value[test.id] || [];
        const resultEntry = {
            test_id: test.id,
            remarks: localRemarks.value,  // Add remarks at the test level
            comment: localComments.value  // Add comment at the test level
        };

        if (params.length > 0) {
            // If the test has parameters, add them to the payload
            // Filter out parameters with missing or empty result values
            const validParams = params.filter(param => {
                // Check if the parameter has a result value
                return param.resultValue && param.resultValue.trim() !== '';
            });
            
            if (validParams.length > 0) {
                resultEntry.parameters = validParams.map(param => {
                    // Explicitly get the resultComment from the parameter, defaulting to empty string
                    // This ensures we're using the correct value from the UI
                    const resultComment = typeof param.resultComment === 'string' ? param.resultComment : '';
                    
                    // Log each parameter's data for debugging
                    console.log(`Parameter data for ${param.id}:`, {
                        test_parameter_id: param.id,
                        result_value: param.resultValue,
                        unit: param.unit,
                        analyzer_id: param.instrument,
                        result_comment: resultComment,
                        has_result_id: !!param.result_id
                    });
                    
                    // Make sure result_comment is included in the parameters array only
                    const parameterData = {
                        test_parameter_id: param.id,
                        result_value: param.resultValue.trim(),
                        unit: param.unit ? param.unit.trim() : '',
                        analyzer_id: param.instrument,
                        result_comment: resultComment, // Always use the string version, never undefined
                        // Include the result_id if available to help backend identify existing records
                        ...(param.result_id ? { result_id: param.result_id } : {})
                    };
                    
                    return parameterData;
                });
                
                // Only add the test if it has valid parameters to save
                results.push(resultEntry);
            }
        } else {
            // Non-parameterized test - check if it has a result value
            if (test.initial_resultValue && test.initial_resultValue.trim() !== '') {
                // Ensure result_comment is properly defined for non-parameterized tests too
                const resultComment = typeof test.initial_resultComment === 'undefined' ? '' : test.initial_resultComment;
                
                // Log non-parameterized test data for debugging
                console.log(`Non-parameterized test data for ${test.id}:`, {
                    test_id: test.id,
                    result_value: test.initial_resultValue,
                    unit: test.unit,
                    analyzer_id: test.initial_instrument,
                    result_comment: resultComment,
                    has_result_id: !!test.result_id
                });
                
                // Add the non-parameterized test result
                resultEntry.result_value = test.initial_resultValue.trim();
                resultEntry.unit = test.unit ? test.unit.trim() : '';
                resultEntry.analyzer_id = test.initial_instrument;
                resultEntry.result_comment = resultComment; // Use our fixed variable
                
                // Include the result_id if available
                if (test.result_id) {
                    resultEntry.result_id = test.result_id;
                }
                
                results.push(resultEntry);
            }
        }
    });
    
    // If there are no results, return null to prevent an empty request
    if (results.length === 0) {
        return null;
    }
    
    return {
        laboratory_request_id: props.requestDetails.id,
        active_test_category_id: testCategoryId.value, // Use dynamic category ID
        results: results,
        performer_id: localSignatories.value.performedBy,
        verifier_id: localSignatories.value.verifiedBy,
        pathologist_id: localSignatories.value.pathologist,
        status: 'saved'
    };
};

const handleKeyDown = (event) => {
  if (props.visible) {
    switch (event.key) {
      case 'F1':
        event.preventDefault();
        handlePreview();
        break;
      case 'F2':
        event.preventDefault();
        // Only trigger save if the button is not disabled
        if (!disableSaveButton.value) {
          triggerSave();
        } else {
          ElMessage.info('All results are already saved');
        }
        break;
      case 'F4':
        event.preventDefault();
        // Only trigger print & release if the button is not disabled
        if (!disablePrintButton.value) {
          handlePrintRelease();
        } else {
          ElMessage.warning('Please save all results before printing and releasing');
        }
        break;
      case 'F5':
        event.preventDefault();
        // Only trigger validate & release if the button is not disabled
        if (!disableValidateButton.value) {
          handleValidateRelease();
        } else {
          // Determine the specific reason for disabling
          const allTestParams = Object.values(testParameters.value).flat();
          const allSaved = allTestParams.every(param => param.result_id !== null);
          const allPrinted = props.requestDetails.tests.every(test => 
            test.pivot && test.pivot.item_status === 'Result Printed'
          );
          
          if (allPrinted) {
            ElMessage.warning('Tests are already in Printed status and cannot be validated again');
          } else if (!allSaved) {
            ElMessage.warning('Please save all results before validating and releasing');
          }
        }
        break;
    }
  }
};

// Function to load analyzers based on the tests in the current request
const loadAnalyzersForTests = () => {
  // If we have test results
  if (resultFields.value && resultFields.value.length > 0) {
    console.log('First test in resultFields:', resultFields.value[0]);
    
    // Try to get the test category ID from the first test
    const firstTest = resultFields.value[0];
    
    // Try different paths to find the category ID
    let categoryId = null;
    
    if (firstTest.test_category_id) {
      categoryId = firstTest.test_category_id;
      console.log(`Found category ID in resultFields: ${categoryId}`);
    } else if (firstTest.testCategory && firstTest.testCategory.id) {
      categoryId = firstTest.testCategory.id;
      console.log(`Found category ID via testCategory relation: ${categoryId}`);
    } else {
      // For a Hematology modal, we could hard-code the Hematology category ID
      // This is just a fallback for when the data structure doesn't contain what we need
      console.log('Could not find category ID in resultFields');
    }
    
    if (categoryId) {
      loadAnalyzers(categoryId);
    } else {
      // If no category ID is found, try to get it from the request details
      if (props.requestDetails && props.requestDetails.tests && 
          props.requestDetails.tests.length > 0 && 
          props.requestDetails.tests[0].test_category_id) {
        loadAnalyzers(props.requestDetails.tests[0].test_category_id);
      } else {
        // If all else fails, load all analyzers
        loadAnalyzers();
      }
    }
  } else {
    // If no tests are loaded yet, load all analyzers
    console.log('No tests in resultFields, loading all analyzers');
    loadAnalyzers();
  }
};

// Add a watch to log button state changes for debugging
// watch(allResultsSaved, (newValue, oldValue) => {
//   if (newValue !== oldValue) {
//     console.log(`Button states updated: Save button disabled: ${newValue}, Print/Validate buttons enabled: ${newValue}`);
//   }
//   
//   // Update our reactive variable for immediate UI updates
//   isResultsSaved.value = newValue;
// });
// 
// // Watch for testsArePrinted changes
// watch(testsArePrinted, (newValue, oldValue) => {
//   if (newValue !== oldValue) {
//     console.log(`Printed state updated: Tests are printed: ${newValue}, Validate is disabled: ${newValue}`);
//   }
//   
//   // Update our reactive variable for immediate UI updates
//   resultsArePrinted.value = newValue;
// });

// Watch for changes in the request details to load category-specific analyzers
watch(() => props.requestDetails, (newDetails) => {
  if (newDetails && newDetails.tests && Array.isArray(newDetails.tests) && newDetails.tests.length > 0) {
    // Log the structure of the test data to help debug
    console.log('First test from request details:', newDetails.tests[0]);
    
    // Get the category ID from the first test
    const firstTest = newDetails.tests[0];
    
    // Try different possible paths to find the test_category_id
    let categoryId = null;
    
    if (firstTest.test_category_id) {
      categoryId = firstTest.test_category_id;
      console.log(`Found category ID directly on test: ${categoryId}`);
    } else if (firstTest.testCategory && firstTest.testCategory.id) {
      categoryId = firstTest.testCategory.id;
      console.log(`Found category ID via testCategory relation: ${categoryId}`);
    } else {
      // In production hematology service, we could hardcode a known ID from your database
      console.log('Could not find test category ID, loading all analyzers');
    }
    
    if (categoryId) {
      loadAnalyzers(categoryId);
    } else {
      // Fallback to loading all analyzers
      loadAnalyzers();
    }
  } else {
    console.log('No tests found in request details, loading all analyzers');
    loadAnalyzers();
  }
}, { immediate: true });

// Remove the initialization of old reactive variables in onMounted
onMounted(() => {
  loadAnalyzersForTests();
  loadRoleUsers(); // Load role-specific users
  window.addEventListener('keydown', handleKeyDown);
  
  // Initialize button states immediately
  nextTick(() => {
    console.log('Component mounted - initializing button states');
    
    // Check for parameters with values
    const allParams = Object.values(testParameters.value).flat();
    console.log(`Found ${allParams.length} total parameters on initial load`);
    
    // Run detailed debug
    debugParameterStates();
    
    // CRITICAL FIX: Check if any parameters have been saved previously
    // If all parameters with values already have result_ids, we should enable Print/Validate buttons
    const paramsWithValues = allParams.filter(param => 
      param && param.resultValue && param.resultValue.trim() !== ''
    );
    
    const unsavedParams = paramsWithValues.filter(param => param.result_id === null);
    
    // If we have parameters with values but all are already saved (have result_ids),
    // we should NOT mark them as unsaved - this keeps Save button disabled and Print/Validate enabled
    if (paramsWithValues.length > 0 && unsavedParams.length === 0) {
      console.log('All parameters with values already have result_ids - keeping them marked as saved');
      // No need to change result_ids, they should remain as is
    }
    // If we have any unsaved parameters with values, we should proceed as before
    else if (paramsWithValues.length > 0) {
      console.log('Parameters with values found, marking as unsaved to enable Save button');
      paramsWithValues.forEach(param => {
        // Clear result_id to mark as unsaved
        param.result_id = null;
        console.log(`Marked parameter ${param.id} with value "${param.resultValue}" as unsaved`);
      });
    }
    
    // Force update after setting initial state
    forceButtonUpdate();
    
    // Run debug again after modifications
    console.log('After initial state setup:');
    debugParameterStates();
  });
  
  // Enhanced debugging for signatories
  console.group('HematologyModal Signatories Debug');
  console.log('Initial signatories from props:', {
    performer_id: props.requestDetails?.performer_id,
    verifier_id: props.requestDetails?.verifier_id,
    pathologist_id: props.requestDetails?.pathologist_id
  });
  
  if (props.requestDetails?.tests?.length > 0) {
    console.log('First test tracking data:', {
      id: props.requestDetails.tests[0]?.id,
      performer_id: props.requestDetails.tests[0]?.performer_id,
      verifier_id: props.requestDetails.tests[0]?.verifier_id,
      pathologist_id: props.requestDetails.tests[0]?.pathologist_id
    });
  } else {
    console.log('No tests available in requestDetails');
  }
  
  console.log('Current localSignatories state:', localSignatories.value);
  console.groupEnd();
  // Initialize QC banner
  try { updateQCHoldBanner(); } catch (_) {}
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
});

// Enhanced handleInstrumentChange function to work with analyzer IDs
const handleInstrumentChange = async (row, analyzerId) => {
  if (!row.isParameter || !row.testId || !row.parameterId) {
    console.log('Missing required data for handleInstrumentChange:', { row });
    return;
  }
  
  console.log(`Changing analyzer for parameter ${row.parameterId} to ${analyzerId}`);
  
  // Find the parameter in testParameters
  const testParams = testParameters.value[row.testId];
  if (!testParams) {
    console.error(`No test parameters found for test ID ${row.testId}`);
    return;
  }
  
  const paramToUpdate = testParams.find(p => p.id === row.parameterId);
  if (!paramToUpdate) {
    console.error(`Parameter with ID ${row.parameterId} not found in test ${row.testId}`);
    return;
  }
  
  // Update the instrument in the source
  paramToUpdate.instrument = analyzerId;
  
  // Find the analyzer name for display but don't store in the UI element
  const selectedAnalyzer = analyzers.value.find(a => a.id === parseInt(analyzerId));
  const analyzerName = selectedAnalyzer ? selectedAnalyzer.name : 'Unknown';
  console.log(`Found analyzer: ${analyzerName}`);
  
  // Fetch updated reference ranges for the parameter with this analyzer
  try {
    console.log(`Fetching reference ranges for parameter ${row.parameterId} with analyzer ${analyzerId}`);
    
    // Add patient information for accurate reference range selection
    const patientId = props.requestDetails?.patient?.id;
    const patientAge = props.requestDetails?.patient_age;
    const patientGender = props.requestDetails?.patient?.gender || 'Any';
    
    console.log('Patient data for reference range lookup:', { 
      patientId, 
      patientAge, 
      patientGender 
    });
    
    const response = await api.get(`/tests/${row.testId}/parameters/${row.parameterId}/reference-ranges`, {
      params: {
        instrument: analyzerId,
        patient_id: patientId,
        patient_age: patientAge,
        patient_gender: patientGender
      }
    });
    
    console.log(`Received ${response.data.length} reference ranges for parameter ${row.parameterId}:`, response.data);
    
    if (response.data && Array.isArray(response.data)) {
      // Check if we have any reference ranges
      if (response.data.length === 0) {
        ElMessage.warning(`No reference ranges found for analyzer ${analyzerName}`);
        // Create a default reference range so the UI doesn't show N/A
        paramToUpdate.referenceRanges = [{
          id: 'default',
          test_parameter_id: row.parameterId,
          analyzer_id: analyzerId,
          analyzer: analyzerName,
          si_range: 'Not defined',
          conv_range: 'Not defined',
          low_value: null,
          high_value: null,
          gender: 'Any',
          age_min: 0,
          age_max: 120
        }];
      } else {
        // Update the reference ranges for this parameter
        paramToUpdate.referenceRanges = response.data;
        
        // Print example reference range to help debug
        if (response.data.length > 0) {
          console.log('Example reference range format:', response.data[0]);
        }
      }
      
      // If we have a result value, recalculate the flag
      if (paramToUpdate.resultValue && !isNaN(parseFloat(paramToUpdate.resultValue))) {
        console.log(`Recalculating flag for value ${paramToUpdate.resultValue} with new reference ranges`);
        updateResultValue(row, paramToUpdate.resultValue);
      }
    }
  } catch (error) {
    console.error(`Error fetching reference ranges for parameter ${row.parameterId}:`, error);
    ElMessage.error(`Failed to load reference ranges: ${error.message}`);
  }
};

// Simplified computed property to directly display the referring doctor name
const referringDoctorName = computed(() => {
  // Simply return the referring_doctor value directly from the request details
  // Since it's now stored as full text in the database
  return props.requestDetails.referring_doctor || 'N/A';
});

// Add this function after the other functions to refresh test status data
const refreshTestStatuses = async () => {
  if (!props.requestDetails?.id) {
    console.error('Cannot refresh test statuses: Request ID is missing');
    return;
  }
  
  try {
    // Fetch the latest test status data
    const response = await api.get(`/laboratory-requests/${props.requestDetails.id}`);
    
    if (response.data && response.data.tests) {
      // Update only the status-related properties of each test
      response.data.tests.forEach(updatedTest => {
        // Find the corresponding test in our local data
        const localTestIndex = props.requestDetails.tests.findIndex(t => t.id === updatedTest.id);
        if (localTestIndex !== -1) {
          // Update just the pivot data which contains the status
          props.requestDetails.tests[localTestIndex].pivot = updatedTest.pivot;
          console.log(`Updated test ${updatedTest.id} status to: ${updatedTest.pivot?.item_status}`);
        }
      });
      
      // Force re-evaluation of button states by triggering Vue's reactivity
      nextTick(() => {
        console.log('Test statuses refreshed, button states should update automatically');
        console.log('Print button disabled:', disablePrintButton.value);
        console.log('Validate button disabled:', disableValidateButton.value);
      });
    }
  } catch (error) {
    console.error('Error refreshing test statuses:', error);
  }
};

// Replace isResultsSaved and resultsArePrinted with these computed properties
// directly bound to the UI buttons
// 
// Keep QC banner in sync with data changes
watch(testParameters, () => { try { updateQCHoldBanner(); } catch (_) {} }, { deep: true })
watch(testCategoryId, () => { try { updateQCHoldBanner(); } catch (_) {} })
const disableSaveButton = computed(() => {
  const allParams = Object.values(testParameters.value).flat();
  const anyEmpty = allParams.some(param => !param.resultValue || param.resultValue.trim() === '');
  const anyUnsaved = allParams.some(param => param.resultValue && param.resultValue.trim() !== '' && param.result_id === null);
console.log('disableSaveButton check:', { anyEmpty, anyUnsaved, allParams });
  if (anyEmpty) return false;
  if (anyUnsaved) return false;
  return true;
});

const disablePrintButton = computed(() => {
  if (qcHoldBannerMessage.value) return true; // Disable when QC lockout active
  const allParams = Object.values(testParameters.value).flat();
  if (!props.requestDetails?.tests?.length || allParams.length === 0) return true;
  // If any parameter is empty or unsaved, disable Print
  const anyEmpty = allParams.some(param => !param.resultValue || param.resultValue.trim() === '');
  const anyUnsaved = allParams.some(param => param.resultValue && param.resultValue.trim() !== '' && param.result_id === null);
  return anyEmpty || anyUnsaved;
});

const disableValidateButton = computed(() => {
  if (qcHoldBannerMessage.value) return true; // Disable when QC lockout active
  const allParams = Object.values(testParameters.value).flat();
  if (!props.requestDetails?.tests?.length || allParams.length === 0) return true;
  // If any parameter is empty or unsaved, disable Validate
  const anyEmpty = allParams.some(param => !param.resultValue || param.resultValue.trim() === '');
  const anyUnsaved = allParams.some(param => param.resultValue && param.resultValue.trim() !== '' && param.result_id === null);
  // Also disable if already printed
  const testsArePrinted = props.requestDetails.tests.every(test => test.pivot && test.pivot.item_status === 'Result Printed');
  return anyEmpty || anyUnsaved || testsArePrinted;
});

// Add this function near the top of the script section
const debugParameterStates = () => {
  console.log('Debugging parameter states:');
  const allParams = Object.values(testParameters.value).flat();
  
  // Count parameters by different states
  const withValues = allParams.filter(p => p.resultValue && p.resultValue.trim() !== '');
  const withResultIds = allParams.filter(p => p.result_id !== null);
  const withValuesAndResultIds = allParams.filter(p => 
    p.resultValue && p.resultValue.trim() !== '' && p.result_id !== null
  );
  const withValuesNoResultIds = allParams.filter(p => 
    p.resultValue && p.resultValue.trim() !== '' && p.result_id === null
  );
  
  console.log(`Parameter counts:
    - Total parameters: ${allParams.length}
    - With values: ${withValues.length}
    - With result_ids: ${withResultIds.length}
    - With values AND result_ids: ${withValuesAndResultIds.length}
    - With values BUT NO result_ids: ${withValuesNoResultIds.length}
  `);
  
  // Detailed inspection of parameters with values
  if (withValues.length > 0) {
    console.log('Parameters with values:');
    withValues.forEach(param => {
      console.log(`Parameter ID ${param.id}: value="${param.resultValue}", result_id=${param.result_id}, saved=${param.result_id !== null}`);
    });
  }
  
  // Button states debug
  console.log('Current button states:');
  console.log('- Save button disabled:', disableSaveButton.value);
  console.log('- Print button disabled:', disablePrintButton.value);
  console.log('- Validate button disabled:', disableValidateButton.value);
};

</script>

<style scoped>
.hematology-modal {
  display: flex;
  flex-direction: column;
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
  overflow-y: auto;
}

.details-sections {
  display: flex;
  gap: var(--spacing-xl);
  margin-bottom: 0px;
}

.section {
  background-color: var(--color-bg-secondary);
  border-radius: var(--border-radius-md);
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
  font-size: var(--font-size-base);
  font-weight: bold;
  color: var(--color-primary);
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-sm);
  border-bottom: 2px solid var(--color-primary);
}

.header-title {
  font-size: var(--font-size-base);
  font-weight: bold;
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
  font-size: var(--font-size-sm);
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
  border-radius: var(--border-radius-md);
}

.compact-form :deep(.el-input__inner) {
  height: 32px;
  line-height: 32px;
  font-size: var(--font-size-sm);
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
  gap: 10px;
}

.total-dif {
  margin-top: 10px;
  padding: 10px;
  background-color: var(--color-danger);
  color: white;
  font-weight: bold;
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
  font-size: var(--font-size-xs);
  margin-bottom: var(--spacing-md);
}
.signatories-section .section-signatories {
  flex: 1;
  display: grid; 
  grid-template-columns: 1fr 1fr 1fr; 
  gap: var(--spacing-lg);
}
.panel-label {
  font-weight: bold;
  margin-bottom: var(--spacing-xs);
  font-size: var(--font-size-xs);
}

:deep(.el-textarea__inner) {
  font-size: var(--font-size-xs);
}

:deep(.el-dialog__body) {
  padding: 0;
}

:deep(.el-form-item) {
  margin-bottom: var(--spacing-xs);
}

:deep(.el-form-item__label) {
  font-size: var(--font-size-xs);
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

.flag-abnormal {
  color: var(--color-warning);
  font-weight: bold;
}
.flag-critical {
  color: var(--color-danger);
  font-weight: bold;
}

.analyzer-name {
  font-size: var(--font-size-xs, 12px);
  color: var(--color-text-secondary);
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.range-undefined {
  color: var(--color-text-disabled);
  font-style: italic;
}
</style> 