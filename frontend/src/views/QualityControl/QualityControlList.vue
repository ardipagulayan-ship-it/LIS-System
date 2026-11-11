<template>
  <div class="quality-control-list">
    <div class="page-header">
      <h2 class="page-title">Quality Control Management</h2>
      <div class="header-actions">
        <el-button type="primary" @click="openDialog()">
          <el-icon><Plus /></el-icon>Add Quality Control
        </el-button>
      </div>
    </div>
    <div class="quality-control-list-container">
    <el-card class="mb-4">
      <template #header>
        <div class="card-header">
          <span>Control</span>
          <el-input
            v-model="searchQuery"
            placeholder="Search quality controls..."
            class="search-input"
            clearable
            @clear="fetchQualityControls"
            @input="fetchQualityControls"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
        </div>
      </template>
      <el-table
        v-loading="loading"
        :data="paginatedQualityControls"
        style="width: 100%"
        border
        stripe
        highlight-current-row
        @row-click="handleControlSelect"
        @current-change="handleControlSelect"
      >
        <el-table-column label="Equipment" width="250">
          <template #default="{ row }">
            {{ row.equipment ? `${row.equipment.manufacturer} - ${row.equipment.model}` : '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="equipment.serial_number" label="Serial Number" width="180" />
        <el-table-column prop="control_name" label="Control Name" width="200" />
        <el-table-column prop="lot_number" label="Lot Number" width="150" />
        <el-table-column prop="expiry_date" label="Expiry Date" width="150">
           <template #default="{ row }">
             {{ formatDate(row.expiry_date) }}
           </template>
        </el-table-column>
        <el-table-column label="Actions" width="120" fixed="right">
          <template #default="{ row }">
            <el-button-group>
              <el-button type="primary" size="small" @click.stop="openDialog(row)">
                <el-icon><Edit /></el-icon>
              </el-button>
              <el-button type="danger" size="small" @click.stop="confirmDelete(row)">
                <el-icon><Delete /></el-icon>
              </el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination-container">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="filteredQualityControls.length"
          layout="total, sizes, prev, pager, next, jumper"
        />
      </div>
    </el-card>

    <el-card>
       <template #header>
         <div class="card-header">
           <span>Control Target</span>
            <el-button-group>
              <el-button type="primary" size="small" :icon="Plus" @click="openParameterDialog">Add</el-button>
              <el-button type="warning" size="small" :icon="Edit" @click="editParameter" :disabled="!selectedParameter">Edit</el-button>
              <el-button type="danger" size="small" :icon="Delete" @click="deleteParameter" :disabled="!selectedParameter">Delete</el-button>
              <el-button type="info" size="small" :icon="Refresh" @click="refreshParameters">Refresh</el-button>
            </el-button-group>
         </div>
       </template>
       <el-table
         :data="selectedControlParameters"
         v-loading="parameterLoading"
         style="width: 100%"
         border
         stripe
         @current-change="handleParameterSelect"
         empty-text="No test parameters found. Click 'Add' to add test parameters to this quality control."
       >
         <el-table-column prop="id" label="Sequence No." width="120">
           <template #default="{ row, $index }">
             {{ row.pivot?.id || row.id || $index + 1 }}
           </template>
         </el-table-column>
         <el-table-column prop="test_name" label="Test Name" width="180">
           <template #default="{ row }">
             {{ row.test_name || row.name || '-' }}
           </template>
         </el-table-column>
         <el-table-column prop="test_code" label="Test Code" width="150">
           <template #default="{ row }">
             {{ row.test_code || '-' }}
           </template>
         </el-table-column>
         <el-table-column prop="pivot.target" label="Target" width="100">
           <template #default="{ row }">
             {{ row.pivot?.target ?? row.target ?? '-' }}
           </template>
         </el-table-column>
         <el-table-column prop="pivot.tolerance" label="Tolerance" width="100">
           <template #default="{ row }">
             {{ row.pivot?.tolerance ?? row.tolerance ?? '-' }}
           </template>
         </el-table-column>
         <el-table-column prop="pivot.minus_3sd" label="-3 SD" width="100">
           <template #default="{ row }">
             {{ row.pivot?.minus_3sd ?? row.minus_3sd ?? '-' }}
           </template>
         </el-table-column>
         <el-table-column prop="pivot.minus_2sd" label="-2 SD" width="100">
           <template #default="{ row }">
             {{ row.pivot?.minus_2sd ?? row.minus_2sd ?? '-' }}
           </template>
         </el-table-column>
         <el-table-column prop="pivot.minus_1sd" label="-1 SD" width="100">
           <template #default="{ row }">
             {{ row.pivot?.minus_1sd ?? row.minus_1sd ?? '-' }}
           </template>
         </el-table-column>
         <el-table-column prop="pivot.plus_1sd" label="+1 SD" width="100">
           <template #default="{ row }">
             {{ row.pivot?.plus_1sd ?? row.plus_1sd ?? '-' }}
           </template>
         </el-table-column>
         <el-table-column prop="pivot.plus_2sd" label="+2 SD" width="100">
           <template #default="{ row }">
             {{ row.pivot?.plus_2sd ?? row.plus_2sd ?? '-' }}
           </template>
         </el-table-column>
         <el-table-column prop="pivot.plus_3sd" label="+3 SD" width="100">
           <template #default="{ row }">
             {{ row.pivot?.plus_3sd ?? row.plus_3sd ?? '-' }}
           </template>
         </el-table-column>
         <el-table-column label="Actions" width="220" fixed="right">
           <template #default="{ row }">
             <el-button-group>
               <el-button type="info" size="small" @click="viewRawResults(row)">
                 Raw Results
               </el-button>
                <el-button type="success" size="small" @click="viewLJGraph(row)">
                 LJ Graph
               </el-button>
             </el-button-group>
           </template>
         </el-table-column>
       </el-table>
    </el-card>

    <!-- Add/Edit Dialog -->
    <el-dialog
      v-model="dialog"
      :title="editedIndex === -1 ? 'Add Quality Control' : 'Edit Quality Control'"
      width="700px"
    >
      <el-form
        ref="formRef"
        :model="editedItem"
        label-width="120px"
      >
        <el-form-item label="Test Parameter" prop="test_parameter_id">
          <el-select v-model="editedItem.test_parameter_id" filterable placeholder="Select Test Parameter">
            <el-option v-for="param in testParameters" :key="param.id" :label="param.name" :value="param.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="Equipment" prop="equipment_id">
          <el-select v-model="editedItem.equipment_id" filterable placeholder="Select Equipment">
            <el-option v-for="eq in equipment" :key="eq.id" :label="`${eq.manufacturer} - ${eq.model}`" :value="eq.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="Control Name" prop="control_name">
          <el-input v-model="editedItem.control_name" />
        </el-form-item>
        <el-form-item label="Lot Number" prop="lot_number">
          <el-input v-model="editedItem.lot_number" />
        </el-form-item>
        <el-form-item label="Expiry Date" prop="expiry_date">
          <el-date-picker v-model="editedItem.expiry_date" type="date" placeholder="Pick a date" style="width: 100%" />
        </el-form-item>
        <el-form-item label="Mean" prop="mean">
          <el-input v-model.number="editedItem.mean" type="number" />
        </el-form-item>
        <el-form-item label="SD" prop="sd">
          <el-input v-model.number="editedItem.sd" type="number" />
        </el-form-item>
        <el-form-item label="Target" prop="target">
          <el-input v-model.number="editedItem.target" type="number" />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="closeDialog">Cancel</el-button>
          <el-button type="primary" @click="save">{{ editedIndex === -1 ? 'Add' : 'Update' }}</el-button>
        </span>
      </template>
    </el-dialog>
    <!-- Delete Confirmation Dialog -->
    <el-dialog
      v-model="deleteDialog"
      title="Delete Quality Control"
      width="400px"
    >
      <p>Are you sure you want to delete this quality control?</p>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="deleteDialog = false">Cancel</el-button>
          <el-button type="danger" @click="deleteItem">Delete</el-button>
        </span>
        </template>
      </el-dialog>

    <!-- Add/Edit Parameter Dialog -->
    <el-dialog
      v-model="parameterDialog"
      :title="parameterEditedIndex === -1 ? 'Add Test Parameter' : 'Edit Test Parameter'"
      width="700px"
      :close-on-click-modal="false"
    >
      <el-form
        ref="parameterFormRef"
        :model="parameterEditedItem"
        :rules="parameterFormRules"
        label-width="160px"
        label-position="left"
      >
        <el-form-item label="Test Parameter" prop="test_parameter_id" required>
          <el-select 
            v-model="parameterEditedItem.test_parameter_id" 
            filterable 
            placeholder="Select Test Parameter"
            :disabled="parameterEditedIndex > -1"
            style="width: 100%"
            clearable
          >
            <el-option 
              v-for="param in availableTestParameters" 
              :key="param.id" 
              :label="`${param.test_name || param.name} (${param.test_code || ''})`" 
              :value="param.id" 
            />
          </el-select>
          <div v-if="availableTestParameters.length === 0" class="form-help-text">
            <el-alert type="warning" :closable="false" style="margin-top: 8px;">
              No test parameters available for this equipment's test category.
            </el-alert>
          </div>
        </el-form-item>

        <el-divider content-position="left">Control Limits</el-divider>
        
        <el-form-item label="Target" prop="target">
          <el-input 
            v-model.number="parameterEditedItem.target" 
            type="number" 
            step="0.0001"
            placeholder="Enter target value"
            @input="calculateSDLimits"
          >
            <template #append>
              <el-button @click="calculateSDLimits" :icon="Refresh" circle size="small" title="Calculate SD limits" />
            </template>
          </el-input>
          <div class="form-help-text">Mean or expected value</div>
        </el-form-item>

        <el-form-item label="Tolerance (SD)" prop="tolerance">
          <el-input 
            v-model.number="parameterEditedItem.tolerance" 
            type="number" 
            step="0.0001"
            placeholder="Enter standard deviation"
            @input="calculateSDLimits"
          >
            <template #append>
              <el-button @click="calculateSDLimits" :icon="Refresh" circle size="small" title="Calculate SD limits" />
            </template>
          </el-input>
          <div class="form-help-text">Standard deviation value</div>
        </el-form-item>

        <el-divider content-position="left">Standard Deviation Limits</el-divider>
        <div class="sd-limits-grid">
          <el-form-item label="-3 SD" prop="minus_3sd">
            <el-input 
              v-model.number="parameterEditedItem.minus_3sd" 
              type="number" 
              step="0.0001"
              placeholder="Auto-calculated"
              :disabled="autoCalculateSD"
            />
          </el-form-item>
          <el-form-item label="-2 SD" prop="minus_2sd">
            <el-input 
              v-model.number="parameterEditedItem.minus_2sd" 
              type="number" 
              step="0.0001"
              placeholder="Auto-calculated"
              :disabled="autoCalculateSD"
            />
          </el-form-item>
          <el-form-item label="-1 SD" prop="minus_1sd">
            <el-input 
              v-model.number="parameterEditedItem.minus_1sd" 
              type="number" 
              step="0.0001"
              placeholder="Auto-calculated"
              :disabled="autoCalculateSD"
            />
          </el-form-item>
          <el-form-item label="+1 SD" prop="plus_1sd">
            <el-input 
              v-model.number="parameterEditedItem.plus_1sd" 
              type="number" 
              step="0.0001"
              placeholder="Auto-calculated"
              :disabled="autoCalculateSD"
            />
          </el-form-item>
          <el-form-item label="+2 SD" prop="plus_2sd">
            <el-input 
              v-model.number="parameterEditedItem.plus_2sd" 
              type="number" 
              step="0.0001"
              placeholder="Auto-calculated"
              :disabled="autoCalculateSD"
            />
          </el-form-item>
          <el-form-item label="+3 SD" prop="plus_3sd">
            <el-input 
              v-model.number="parameterEditedItem.plus_3sd" 
              type="number" 
              step="0.0001"
              placeholder="Auto-calculated"
              :disabled="autoCalculateSD"
            />
          </el-form-item>
        </div>
        
        <el-alert
          v-if="autoCalculateSD"
          type="info"
          :closable="false"
          show-icon
          style="margin-top: 10px;"
        >
          SD limits are auto-calculated from Target and Tolerance. Enter values above to enable auto-calculation.
        </el-alert>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="closeParameterDialog">Cancel</el-button>
          <el-button type="primary" @click="validateAndSaveParameter" :loading="parameterSaving">
            {{ parameterEditedIndex === -1 ? 'Add' : 'Update' }}
          </el-button>
        </span>
      </template>
    </el-dialog>

    <!-- Delete Parameter Confirmation Dialog -->
    <el-dialog
      v-model="deleteParameterDialog"
      title="Delete Test Parameter"
      width="400px"
    >
      <p>Are you sure you want to remove this test parameter from the quality control?</p>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="deleteParameterDialog = false">Cancel</el-button>
          <el-button type="danger" @click="confirmDeleteParameter">Delete</el-button>
        </span>
      </template>
    </el-dialog>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Plus, Edit, Delete, Search, Refresh } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import api from '@/utils/axios';

import { useRouter } from 'vue-router';
const router = useRouter();

const loading = ref(false);
const dialog = ref(false);
const deleteDialog = ref(false);
const editedIndex = ref(-1);
const editedItem = ref({
  test_parameter_id: '',
  equipment_id: '',
  control_name: '',
  lot_number: '',
  expiry_date: '',
  mean: '',
  sd: '',
  target: ''
});
const defaultItem = {
  test_parameter_id: '',
  equipment_id: '',
  control_name: '',
  lot_number: '',
  expiry_date: '',
  mean: '',
  sd: '',
  target: ''
};
const testParameters = ref([]);
const equipment = ref([]);
const qualityControls = ref([]);
const searchQuery = ref('');
const currentPage = ref(1);
const pageSize = ref(10);
const formRef = ref(null);
const selectedControl = ref(null);
const selectedControlParameters = ref([]);
const parameterLoading = ref(false);
const selectedParameter = ref(null);
const parameterDialog = ref(false);
const parameterEditedIndex = ref(-1);
const parameterEditedItem = ref({
  quality_control_id: '',
  test_parameter_id: '',
  target: '',
  tolerance: '',
  minus_3sd: '',
  minus_2sd: '',
  minus_1sd: '',
  plus_1sd: '',
  plus_2sd: '',
  plus_3sd: ''
});
const parameterDefaultItem = {
  quality_control_id: '',
  test_parameter_id: '',
  target: '',
  tolerance: '',
  minus_3sd: '',
  minus_2sd: '',
  minus_1sd: '',
  plus_1sd: '',
  plus_2sd: '',
  plus_3sd: ''
};
const deleteParameterDialog = ref(false);
const parameterFormRef = ref(null);
const parameterSaving = ref(false);
const autoCalculateSD = ref(true);

// Form validation rules
const parameterFormRules = {
  test_parameter_id: [
    { required: true, message: 'Please select a test parameter', trigger: 'change' }
  ],
  target: [
    { required: true, message: 'Please enter target value', trigger: 'blur' },
    { type: 'number', message: 'Target must be a number', trigger: 'blur' }
  ],
  tolerance: [
    { required: true, message: 'Please enter tolerance (SD) value', trigger: 'blur' },
    { type: 'number', message: 'Tolerance must be a number', trigger: 'blur' },
    { validator: (rule, value, callback) => {
        if (value !== null && value !== undefined && value <= 0) {
          callback(new Error('Tolerance must be greater than 0'));
        } else {
          callback();
        }
      }, trigger: 'blur' }
  ]
};

const filteredQualityControls = computed(() => {
  let filtered = qualityControls.value || [];
  if (searchQuery.value) {
    filtered = filtered.filter(qc =>
      qc.control_name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      qc.lot_number?.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
  }
  return filtered;
});

const paginatedQualityControls = computed(() => {
  let filtered = filteredQualityControls.value || [];
  // Apply pagination
  const start = (currentPage.value - 1) * pageSize.value;
  const end = start + pageSize.value;
  return filtered.slice(start, end);
});

const fetchQualityControls = async () => {
  loading.value = true;
  try {
    // Fetch all quality controls by getting all pages
    let allQualityControls = [];
    let currentPage = 1;
    let hasMorePages = true;
    
    while (hasMorePages) {
      const response = await api.get('/quality-controls', { params: { page: currentPage, per_page: 100 } });
      
      // Handle paginated response structure
      if (response.data.data && Array.isArray(response.data.data)) {
        allQualityControls = allQualityControls.concat(response.data.data);
        // Check if there are more pages
        hasMorePages = response.data.current_page < response.data.last_page;
        currentPage++;
      } else if (Array.isArray(response.data)) {
        allQualityControls = allQualityControls.concat(response.data);
        hasMorePages = false;
      } else {
        hasMorePages = false;
      }
    }
    
    qualityControls.value = allQualityControls;
  } catch (error) {
    console.error('Error fetching quality controls:', error);
    ElMessage.error('Error fetching quality controls');
    qualityControls.value = [];
  } finally {
    loading.value = false;
  }
};

const fetchTestParameters = async (testCategoryId = null) => {
  try {
    const params = {};
    if (testCategoryId) {
      params.test_category_id = testCategoryId;
    }
    const response = await api.get('/test-parameters', { params });
    // Handle both paginated and non-paginated responses
    if (response.data.data && Array.isArray(response.data.data)) {
      testParameters.value = response.data.data;
    } else if (Array.isArray(response.data)) {
      testParameters.value = response.data;
    } else {
      testParameters.value = [];
    }
  } catch (error) {
    console.error('Error fetching test parameters:', error);
    ElMessage.error('Error fetching test parameters');
    testParameters.value = [];
  }
};

const fetchEquipment = async () => {
  try {
    const response = await api.get('eim/equipment', { params: { per_page: 1000 } });
    equipment.value = Array.isArray(response.data) ? response.data : (response.data.data || []);
  } catch (error) {
    ElMessage.error('Error fetching equipment');
  }
};

const handleControlSelect = async (row, column, event) => {
  // @row-click passes (row, column, event), @current-change passes (row)
  // If column exists, it's from @row-click, so row is the first param
  // If column is undefined, it's from @current-change, so row is the row
  const currentRow = row;
  
  if (!currentRow || !currentRow.id) {
    selectedControl.value = null;
    selectedControlParameters.value = [];
    return;
  }
  
  console.log('Selected control:', currentRow);
  selectedControl.value = currentRow;
  parameterLoading.value = true;
  
  // If equipment data is not fully loaded, fetch it
  if (currentRow.equipment && !currentRow.equipment.test_category_id) {
    try {
      const equipmentResponse = await api.get(`/eim/equipment/${currentRow.equipment.id}`);
      if (equipmentResponse.data && equipmentResponse.data.data) {
        currentRow.equipment = equipmentResponse.data.data;
      } else if (equipmentResponse.data) {
        currentRow.equipment = equipmentResponse.data;
      }
      selectedControl.value = currentRow;
    } catch (error) {
      console.warn('Could not fetch full equipment details:', error);
    }
  }
  
  try {
    console.log('Fetching parameters for quality control ID:', currentRow.id);
    const response = await api.get(`/quality-controls/${currentRow.id}/parameters`);
    console.log('Parameters API response:', response);
    console.log('Parameters response data:', response.data);
    
    // Handle response structure - backend returns {data: [...]}
    if (response.data && response.data.data && Array.isArray(response.data.data)) {
      selectedControlParameters.value = response.data.data;
      console.log(`Loaded ${response.data.data.length} parameters`);
      if (response.data.data.length === 0) {
        ElMessage.info('No parameters found for this quality control');
      }
    } else if (Array.isArray(response.data)) {
      selectedControlParameters.value = response.data;
      console.log(`Loaded ${response.data.length} parameters`);
      if (response.data.length === 0) {
        ElMessage.info('No parameters found for this quality control');
      }
    } else {
      selectedControlParameters.value = [];
      console.log('No parameters found - unexpected response structure');
      ElMessage.info('No parameters found for this quality control');
    }
  } catch (error) {
    console.error('Error fetching control parameters:', error);
    if (error.response) {
      console.error('Error response status:', error.response.status);
      console.error('Error response data:', error.response.data);
      ElMessage.error(`Error fetching control parameters: ${error.response.data?.message || error.message}`);
    } else {
      ElMessage.error(`Error fetching control parameters: ${error.message}`);
    }
    selectedControlParameters.value = [];
  } finally {
    parameterLoading.value = false;
  }
};

const openDialog = (item) => {
  if (item) {
    editedIndex.value = qualityControls.value.indexOf(item);
    editedItem.value = { ...item };
  } else {
    editedIndex.value = -1;
    editedItem.value = { ...defaultItem };
  }
  dialog.value = true;
};

const closeDialog = () => {
  dialog.value = false;
  editedItem.value = { ...defaultItem };
  editedIndex.value = -1;
};

const save = async () => {
  try {
    if (editedIndex.value > -1) {
      // Update
      const response = await api.put(`/quality-controls/${editedItem.value.id}`, editedItem.value);
      const updatedItem = response.data.data || response.data;
      const index = qualityControls.value.findIndex(qc => qc.id === editedItem.value.id);
      if (index !== -1) {
        qualityControls.value[index] = updatedItem;
      }
      ElMessage.success('Quality control updated');
    } else {
      // Create
      const response = await api.post('/quality-controls', editedItem.value);
      const newItem = response.data.data || response.data;
      qualityControls.value.unshift(newItem);
      ElMessage.success('Quality control added');
    }
    closeDialog();
    fetchQualityControls(); // Refresh the list
  } catch (error) {
    console.error('Error saving quality control:', error);
    ElMessage.error('Error saving quality control');
  }
};

const confirmDelete = (item) => {
  editedIndex.value = qualityControls.value.indexOf(item);
  editedItem.value = { ...item };
  deleteDialog.value = true;
};

const deleteItem = async () => {
  try {
    await api.delete(`/quality-controls/${editedItem.value.id}`);
    const index = qualityControls.value.findIndex(qc => qc.id === editedItem.value.id);
    if (index !== -1) {
      qualityControls.value.splice(index, 1);
    }
    // Clear selection if deleted item was selected
    if (selectedControl.value && selectedControl.value.id === editedItem.value.id) {
      selectedControl.value = null;
      selectedControlParameters.value = [];
    }
    ElMessage.success('Quality control deleted');
    deleteDialog.value = false;
  } catch (error) {
    console.error('Error deleting quality control:', error);
    ElMessage.error('Error deleting quality control');
  }
};

const viewRawResults = (parameterRow) => {
   if (selectedControl.value) {
     router.push({
       path: `/quality-control-results/${parameterRow.pivot.id}`,
       query: { view: 'raw' }
     });
   } else {
     ElMessage.warning('Please select a Quality Control first.');
   }
};

const viewLJGraph = (parameterRow) => {
  if (selectedControl.value) {
     router.push({
       path: `/quality-control-results/${parameterRow.pivot.id}`,
       query: { view: 'ljgraph' }
     });
     console.log(selectedControl.value);
   } else {
     ElMessage.warning('Please select a Quality Control first.');
   }
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString() + ' ' + date.toLocaleTimeString(); // Or use a specific format 'MM/DD/YYYY HH:mm:ss'
};

const handleParameterSelect = (currentRow) => {
  selectedParameter.value = currentRow;
};

const refreshParameters = async () => {
  if (selectedControl.value) {
    await handleControlSelect(selectedControl.value);
    ElMessage.success('Parameters refreshed');
  } else {
    ElMessage.warning('Please select a Quality Control first');
  }
};

const availableTestParameters = computed(() => {
  let filtered = testParameters.value || [];
  
  // Filter by equipment's test category if a control is selected
  if (selectedControl.value && selectedControl.value.equipment) {
    const equipmentCategoryId = selectedControl.value.equipment.test_category_id;
    if (equipmentCategoryId) {
      filtered = filtered.filter(tp => {
        // Check if test parameter's test has the same test_category_id
        // The test parameter might have test_category_id directly or via test relationship
        const paramCategoryId = tp.test_category_id || (tp.test && tp.test.test_category_id);
        return paramCategoryId === equipmentCategoryId;
      });
    }
  }
  
  // Filter out already added parameters
  if (selectedControlParameters.value && selectedControlParameters.value.length > 0) {
    const addedIds = selectedControlParameters.value.map(p => p.id || p.test_parameter_id);
    filtered = filtered.filter(tp => !addedIds.includes(tp.id));
  }
  
  return filtered;
});

const openParameterDialog = async (item = null) => {
  if (!selectedControl.value) {
    ElMessage.warning('Please select a Quality Control first');
    return;
  }
  
  // Fetch test parameters filtered by equipment's test category if available
  if (selectedControl.value.equipment && selectedControl.value.equipment.test_category_id) {
    const categoryId = selectedControl.value.equipment.test_category_id;
    await fetchTestParameters(categoryId);
  } else {
    // If no test category, fetch all or refresh
    await fetchTestParameters();
  }
  
  if (item) {
    // Edit mode
    const itemPivotId = item?.pivot?.id ?? null;
    const itemParamId = item?.id ?? item?.test_parameter_id ?? null;
    parameterEditedIndex.value = selectedControlParameters.value.findIndex(p => {
      const pPivotId = p?.pivot?.id ?? null;
      const pParamId = p?.id ?? p?.test_parameter_id ?? null;
      return (itemPivotId && pPivotId && pPivotId === itemPivotId) || (itemParamId && pParamId && pParamId === itemParamId);
    });
    const target = item.pivot?.target || item.target || '';
    const tolerance = item.pivot?.tolerance || item.tolerance || '';
    
    parameterEditedItem.value = {
      quality_control_id: selectedControl.value.id,
      test_parameter_id: itemParamId,
      target: target,
      tolerance: tolerance,
      minus_3sd: item.pivot?.minus_3sd || item.minus_3sd || '',
      minus_2sd: item.pivot?.minus_2sd || item.minus_2sd || '',
      minus_1sd: item.pivot?.minus_1sd || item.minus_1sd || '',
      plus_1sd: item.pivot?.plus_1sd || item.plus_1sd || '',
      plus_2sd: item.pivot?.plus_2sd || item.plus_2sd || '',
      plus_3sd: item.pivot?.plus_3sd || item.plus_3sd || ''
    };
    
    // If target and tolerance exist, check if SD limits match calculated values
    // If they match, enable auto-calculate; otherwise disable it
    if (target && tolerance) {
      const calculatedMinus3 = parseFloat((parseFloat(target) - (3 * parseFloat(tolerance))).toFixed(4));
      const existingMinus3 = parseFloat(item.pivot?.minus_3sd || item.minus_3sd || 0);
      autoCalculateSD.value = Math.abs(calculatedMinus3 - existingMinus3) < 0.0001;
    } else {
      autoCalculateSD.value = true;
    }
  } else {
    // Add mode
    parameterEditedIndex.value = -1;
    parameterEditedItem.value = {
      ...parameterDefaultItem,
      quality_control_id: selectedControl.value.id
    };
    autoCalculateSD.value = true;
  }
  parameterDialog.value = true;
};

const calculateSDLimits = () => {
  const target = parseFloat(parameterEditedItem.value.target);
  const tolerance = parseFloat(parameterEditedItem.value.tolerance);

  if (isNaN(target) || isNaN(tolerance) || tolerance <= 0) {
    // Clear SD limits if target or tolerance is invalid
    parameterEditedItem.value.minus_3sd = '';
    parameterEditedItem.value.minus_2sd = '';
    parameterEditedItem.value.minus_1sd = '';
    parameterEditedItem.value.plus_1sd = '';
    parameterEditedItem.value.plus_2sd = '';
    parameterEditedItem.value.plus_3sd = '';
    return;
  }

  // Calculate SD limits
  parameterEditedItem.value.minus_3sd = parseFloat((target - (3 * tolerance)).toFixed(4));
  parameterEditedItem.value.minus_2sd = parseFloat((target - (2 * tolerance)).toFixed(4));
  parameterEditedItem.value.minus_1sd = parseFloat((target - (1 * tolerance)).toFixed(4));
  parameterEditedItem.value.plus_1sd = parseFloat((target + (1 * tolerance)).toFixed(4));
  parameterEditedItem.value.plus_2sd = parseFloat((target + (2 * tolerance)).toFixed(4));
  parameterEditedItem.value.plus_3sd = parseFloat((target + (3 * tolerance)).toFixed(4));
};

const closeParameterDialog = () => {
  parameterDialog.value = false;
  parameterEditedItem.value = { ...parameterDefaultItem };
  parameterEditedIndex.value = -1;
  autoCalculateSD.value = true;
  // Reset form validation
  if (parameterFormRef.value) {
    parameterFormRef.value.resetFields();
  }
};

const validateAndSaveParameter = async () => {
  if (!parameterFormRef.value) {
    return;
  }

  try {
    // Validate form
    await parameterFormRef.value.validate();
    
    // Ensure SD limits are calculated if target and tolerance are provided
    if (parameterEditedItem.value.target && parameterEditedItem.value.tolerance) {
      calculateSDLimits();
    }
    
    // Proceed with save
    await saveParameter();
  } catch (error) {
    if (error !== false) { // false means validation failed, which is expected
      console.error('Validation error:', error);
      ElMessage.warning('Please correct the form errors before saving');
    }
  }
};

const saveParameter = async () => {
  parameterSaving.value = true;
  try {
    // Prepare data with proper number types
    const dataToSave = {
      quality_control_id: parameterEditedItem.value.quality_control_id,
      test_parameter_id: parameterEditedItem.value.test_parameter_id,
      target: parameterEditedItem.value.target ? parseFloat(parameterEditedItem.value.target) : null,
      tolerance: parameterEditedItem.value.tolerance ? parseFloat(parameterEditedItem.value.tolerance) : null,
      minus_3sd: parameterEditedItem.value.minus_3sd ? parseFloat(parameterEditedItem.value.minus_3sd) : null,
      minus_2sd: parameterEditedItem.value.minus_2sd ? parseFloat(parameterEditedItem.value.minus_2sd) : null,
      minus_1sd: parameterEditedItem.value.minus_1sd ? parseFloat(parameterEditedItem.value.minus_1sd) : null,
      plus_1sd: parameterEditedItem.value.plus_1sd ? parseFloat(parameterEditedItem.value.plus_1sd) : null,
      plus_2sd: parameterEditedItem.value.plus_2sd ? parseFloat(parameterEditedItem.value.plus_2sd) : null,
      plus_3sd: parameterEditedItem.value.plus_3sd ? parseFloat(parameterEditedItem.value.plus_3sd) : null,
    };

    if (parameterEditedIndex.value > -1) {
      // Update - find the pivot ID
      const param = selectedControlParameters.value[parameterEditedIndex.value];
      const pivotId = param.pivot?.id || param.id;
      
      if (!pivotId) {
        ElMessage.error('Parameter ID not found');
        return;
      }
      
      const response = await api.put(`/quality-control-test-parameters/${pivotId}`, dataToSave);
      ElMessage.success('Parameter updated successfully');
      closeParameterDialog();
      // Refresh parameters
      if (selectedControl.value) {
        await handleControlSelect(selectedControl.value);
      }
    } else {
      // Create
      const response = await api.post('/quality-control-test-parameters', dataToSave);
      ElMessage.success('Parameter added successfully');
      closeParameterDialog();
      // Refresh parameters
      if (selectedControl.value) {
        await handleControlSelect(selectedControl.value);
      }
    }
  } catch (error) {
    console.error('Error saving parameter:', error);
    if (error.response?.data?.errors) {
      const errors = error.response.data.errors;
      const errorMessage = Object.values(errors).flat().join(', ');
      ElMessage.error(`Error: ${errorMessage}`);
    } else if (error.response?.data?.message) {
      ElMessage.error(error.response.data.message);
    } else {
      ElMessage.error('Error saving parameter. Please try again.');
    }
  } finally {
    parameterSaving.value = false;
  }
};

const editParameter = () => {
  if (!selectedParameter.value) {
    ElMessage.warning('Please select a parameter to edit');
    return;
  }
  openParameterDialog(selectedParameter.value);
};

const deleteParameter = async () => {
  if (!selectedParameter.value) {
    ElMessage.warning('Please select a parameter to delete');
    return;
  }
  if (!selectedControl.value) {
    ElMessage.warning('Please select a Quality Control first');
    return;
  }
  deleteParameterDialog.value = true;
};

const confirmDeleteParameter = async () => {
  try {
    const param = selectedParameter.value;
    const pivotId = param.pivot?.id || param.id;
    
    if (!pivotId) {
      ElMessage.error('Parameter ID not found');
      deleteParameterDialog.value = false;
      return;
    }
    
    await api.delete(`/quality-control-test-parameters/${pivotId}`);
    ElMessage.success('Parameter removed');
    deleteParameterDialog.value = false;
    selectedParameter.value = null;
    
    // Refresh parameters
    if (selectedControl.value) {
      await handleControlSelect(selectedControl.value);
    }
  } catch (error) {
    console.error('Error deleting parameter:', error);
    ElMessage.error('Error deleting parameter');
  }
};

onMounted(() => {
  fetchQualityControls();
  fetchTestParameters();
  fetchEquipment();
});
</script>

<style scoped>
.quality-control-list {
  width: 100%;
}
.quality-control-list-container {
  display: grid;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-xl);
}
.header-actions {
  display: flex;
  gap: var(--spacing-sm);
}
.card-header {
  display: flex;
  align-items: center;
  justify-content: flex-end;
}
.search-input {
  width: 300px;
}
.pagination-container {
  margin-top: var(--spacing-xl);
  display: flex;
  justify-content: flex-end;
}

.page-header h2 {
  margin: 0;
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
}

.form-help-text {
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
  margin-top: var(--spacing-xs);
}

.sd-limits-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-sm);
  margin-top: var(--spacing-sm);
}

.sd-limits-grid .el-form-item {
  margin-bottom: 0;
}

.sd-limits-grid .el-form-item__label {
  font-weight: var(--font-weight-medium);
}

@media (max-width: 768px) {
  .sd-limits-grid {
    grid-template-columns: 1fr;
  }
}
</style> 