<template>
  <div class="reference-range-management">
    <div class="page-header">
      <h2 class="page-title">
        Reference Range Management
        <span v-if="selectedEquipmentName" class="equipment-badge">
          - {{ selectedEquipmentName }}
        </span>
      </h2>
      <div class="card-header">
            <el-select
                v-model="selectedCategory"
                placeholder="Filter by Test Category"
                clearable
                @change="fetchRanges"
                class="filter-select"
                >
            <el-option
                v-for="cat in testCategories"
                :key="cat.id"
                :label="cat.name || 'Hematology'"
                :value="cat.id"
            />
            </el-select>
            <el-input
                v-model="searchQuery"
                placeholder="Search reference ranges..."
                class="search-input"
                clearable
                @clear="fetchRanges"
                @input="fetchRanges"
            >
            <template #prefix>
                <el-icon><Search /></el-icon>
            </template>
            </el-input>
            <div class="header-spacer"></div>
            <el-button type="primary" @click="openCreateModal">
                <el-icon><Plus /></el-icon>Add Reference Range
            </el-button>
        </div>
    </div>
    <div class="reference-range-container">
    <el-card class="main-card">
      <div v-if="ranges.length === 0 && !loading" class="empty-state">
        <el-empty description="No reference ranges found">
          <el-button type="primary" @click="openCreateModal">
            <el-icon><Plus /></el-icon>Add Reference Range
          </el-button>
        </el-empty>
      </div>
      <div v-else>
        <div v-for="(group, equipmentId) in groupedRanges" :key="equipmentId" class="equipment-group">
          <h3 class="equipment-title">
            Equipment: {{ group[0]?.equipment?.manufacturer && group[0]?.equipment?.model ? `${group[0].equipment.manufacturer} - ${group[0].equipment.model}` : 'N/A' }}
          </h3>
          <el-table
            :data="group"
            class="ranges-table"
            v-loading="loading"
            border
            stripe
          >
            <el-table-column prop="id" label="ID" width="80" />
            <el-table-column prop="test_parameter_name" label="Test Parameter" />
            <el-table-column prop="si_range" label="SI Range" />
            <el-table-column prop="si_unit" label="SI Unit" />
            <el-table-column prop="conv_range" label="Conv Range" />
            <el-table-column prop="conv_unit" label="Conv Unit" />
            <el-table-column prop="low_critical_value" label="Low Critical" />
            <el-table-column prop="low_value" label="Low Value" />
            <el-table-column prop="high_value" label="High Value" />
            <el-table-column prop="high_critical_value" label="High Critical" />
            <el-table-column prop="age_min" label="Age Min" />
            <el-table-column prop="age_max" label="Age Max" />
            <el-table-column prop="age_unit" label="Age Unit" />
            <el-table-column prop="gender" label="Gender" />
            <el-table-column label="Actions" width="150" fixed="right" align="center">
              <template #default="{ row }">
                <el-button-group>
                  <el-button type="primary" size="small" @click="openEditModal(row)">
                    <el-icon><Edit /></el-icon>
                  </el-button>
                  <el-button type="danger" size="small" @click="confirmDelete(row)">
                    <el-icon><Delete /></el-icon>
                  </el-button>
                </el-button-group>
              </template>
            </el-table-column>
          </el-table>
        </div>
        <div v-if="total > 0" class="pagination-container">
          <el-pagination
            v-model:current-page="currentPage"
            v-model:page-size="pageSize"
            :page-sizes="[10, 20, 50, 100]"
            :total="total"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="fetchRanges"
            @current-change="fetchRanges"
          />
        </div>
      </div>
    </el-card>
    </div>
    <!-- Add/Edit Dialog -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogType === 'add' ? 'Add Reference Range' : 'Edit Reference Range'"
      width="900px"
      class="reference-range-dialog"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="160px"
        label-position="left"
      >
        <el-divider content-position="left">Basic Information</el-divider>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Test Parameter" prop="test_parameter_id">
              <el-select 
                v-model="form.test_parameter_id" 
                placeholder="Select test parameter"
                style="width: 100%"
                filterable
              >
                <el-option
                  v-for="param in testParameters"
                  :key="param.id"
                  :label="param.test_name"
                  :value="param.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Equipment" prop="equipment_id">
              <el-select 
                v-model="form.equipment_id" 
                placeholder="Select equipment"
                style="width: 100%"
                filterable
                clearable
              >
                <el-option
                  v-for="eq in equipment"
                  :key="eq.id"
                  :label="`${eq.manufacturer} - ${eq.model}`"
                  :value="Number(eq.id)"
                />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider content-position="left">SI (System International) Values</el-divider>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="SI Range" prop="si_range">
              <el-input 
                v-model="form.si_range" 
                placeholder="e.g., 4.0-11.0"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="SI Unit" prop="si_unit">
              <el-input 
                v-model="form.si_unit" 
                placeholder="e.g., 10^9/L"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider content-position="left">Conventional Values</el-divider>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Conv Range" prop="conv_range">
              <el-input 
                v-model="form.conv_range" 
                placeholder="e.g., 4.0-11.0"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Conv Unit" prop="conv_unit">
              <el-input 
                v-model="form.conv_unit" 
                placeholder="e.g., 10^9/L"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider content-position="left">Critical & Normal Values</el-divider>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Low Critical Value" prop="low_critical_value">
              <el-input-number 
                v-model="form.low_critical_value" 
                :precision="4"
                :step="0.0001"
                style="width: 100%"
                placeholder="Low critical threshold"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Low Value" prop="low_value">
              <el-input-number 
                v-model="form.low_value" 
                :precision="4"
                :step="0.0001"
                style="width: 100%"
                placeholder="Normal low value"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="High Value" prop="high_value">
              <el-input-number 
                v-model="form.high_value" 
                :precision="4"
                :step="0.0001"
                style="width: 100%"
                placeholder="Normal high value"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="High Critical Value" prop="high_critical_value">
              <el-input-number 
                v-model="form.high_critical_value" 
                :precision="4"
                :step="0.0001"
                style="width: 100%"
                placeholder="High critical threshold"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider content-position="left">Age & Gender Criteria</el-divider>
        <el-row :gutter="20">
          <el-col :span="8">
            <el-form-item label="Age Min" prop="age_min">
              <el-input-number 
                v-model="form.age_min" 
                :min="0"
                style="width: 100%"
                placeholder="Minimum age"
              />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="Age Max" prop="age_max">
              <el-input-number 
                v-model="form.age_max" 
                :min="0"
                style="width: 100%"
                placeholder="Maximum age"
              />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="Age Unit" prop="age_unit">
              <el-select 
                v-model="form.age_unit" 
                placeholder="Select age unit"
                style="width: 100%"
              >
                <el-option label="Days" value="Days" />
                <el-option label="Months" value="Months" />
                <el-option label="Years" value="Years" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row>
          <el-col :span="12">
            <el-form-item label="Gender" prop="gender">
              <el-select 
                v-model="form.gender" 
                placeholder="Select gender"
                style="width: 100%"
              >
                <el-option label="Any" value="Any" />
                <el-option label="Male" value="Male" />
                <el-option label="Female" value="Female" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="dialogVisible = false">Cancel</el-button>
          <el-button type="primary" @click="handleSubmit" :loading="submitting">
            {{ dialogType === 'add' ? 'Add' : 'Update' }}
          </el-button>
        </span>
      </template>
    </el-dialog>
    <!-- Delete Confirmation Dialog -->
    <el-dialog
      v-model="deleteDialogVisible"
      title="Delete Reference Range"
      width="400px"
    >
      <p>Are you sure you want to delete this reference range?</p>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="deleteDialogVisible = false">Cancel</el-button>
          <el-button type="danger" @click="confirmDeleteRange" :loading="deleting">
            Delete
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import { Plus, Edit, Delete, Search } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import api from '@/utils/axios';

const route = useRoute();

const loading = ref(false);
const submitting = ref(false);
const deleting = ref(false);
const ranges = ref([]);
const testParameters = ref([]);
const equipment = ref([]);
const testCategories = ref([]);
const selectedCategory = ref(null);
const selectedEquipmentId = ref(null);
const selectedEquipmentName = ref('');
const total = ref(0);
const currentPage = ref(1);
const pageSize = ref(10);
const searchQuery = ref('');
const dialogVisible = ref(false);
const deleteDialogVisible = ref(false);
const dialogType = ref('add');
const formRef = ref(null);
const selectedRange = ref(null);

const form = ref({
  test_parameter_id: '',
  equipment_id: '',
  // analyzer_id removed - use equipment_id only
  si_range: '',
  si_unit: '',
  conv_range: '',
  conv_unit: '',
  low_critical_value: '',
  low_value: '',
  high_value: '',
  high_critical_value: '',
  age_min: '',
  age_max: '',
  age_unit: '',
  gender: 'Any'
});

const rules = {
  test_parameter_id: [
    { required: true, message: 'Please select test parameter', trigger: 'change' }
  ],
  equipment_id: [
    { required: true, message: 'Please select equipment', trigger: 'change' }
  ]
};

const fetchRanges = async () => {
  loading.value = true;
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
      search: searchQuery.value
    };
    if (selectedCategory.value) {
      params.test_category_id = selectedCategory.value;
    }
    // Filter by equipment_id if provided from route query or selected
    const rawEquipmentId = route.query.equipment_id || selectedEquipmentId.value;
    if (rawEquipmentId) {
      // Clean the equipment ID (extract numeric part)
      const cleanId = String(rawEquipmentId).split(':')[0].trim();
      if (cleanId && !isNaN(cleanId)) {
        params.equipment_id = cleanId;
      }
    }
    const response = await api.get('/reference-ranges', { params });

    console.log(response.data);
    let dataArr = [];
    if (Array.isArray(response.data)) {
      dataArr = response.data;
      total.value = dataArr.length;
    } else if (Array.isArray(response.data.data)) {
      dataArr = response.data.data;
      total.value = response.data.total || dataArr.length;
    }
    ranges.value = dataArr.map(range => ({
      ...range,
      test_parameter_name: range.test_parameter?.test_name || '',
      si_range: range.si_range,
      si_unit: range.si_unit,
      conv_range: range.conv_range,
      conv_unit: range.conv_unit,
      low_critical_value: range.low_critical_value,
      low_value: range.low_value,
      high_value: range.high_value,
      high_critical_value: range.high_critical_value,
      age_min: range.age_min,
      age_max: range.age_max,
      age_unit: range.age_unit,
      gender: range.gender,
      analyzer_name: (range.equipment?.manufacturer && range.equipment?.model ? `${range.equipment.manufacturer} - ${range.equipment.model}` : '') || range.analyzer?.name || ''
    }));
  } catch (error) {
    console.error('Error fetching reference ranges:', error);
    ElMessage.error('Error fetching reference ranges');
  } finally {
    loading.value = false;
  }
};

const fetchTestCategories = async () => {
  try {
    const response = await api.get('/test-categories');
    // If paginated, use .data, else use response.data
    testCategories.value = Array.isArray(response.data) ? response.data : response.data.data;
  } catch (error) {
    ElMessage.error('Error fetching test categories');
  }
};

const fetchTestParameters = async () => {
  try {
    const response = await api.get('/test-parameters');
    testParameters.value = response.data;
  } catch (error) {
    ElMessage.error('Error fetching test parameters');
  }
};

const fetchEquipment = async () => {
  try {
    const response = await api.get('eim/equipment', { params: { per_page: 1000 } });
    equipment.value = response.data.data || response.data || [];
  } catch (error) {
    ElMessage.error('Error fetching equipment');
  }
};

const groupedRanges = computed(() => {
  // Group by equipment_id
  const groups = {};
  for (const range of ranges.value) {
    const key = range.equipment_id || 'none';
    if (!groups[key]) groups[key] = [];
    groups[key].push(range);
  }
  return groups;
});

const openCreateModal = async () => {
  // Ensure equipment is loaded before opening modal
  if (equipment.value.length === 0) {
    await fetchEquipment();
  }
  
  dialogType.value = 'add';
  // Pre-populate equipment if selected (ensure it's a number)
  const defaultEquipmentId = selectedEquipmentId.value ? Number(selectedEquipmentId.value) : null;
  form.value = { 
    test_parameter_id: '', 
    equipment_id: defaultEquipmentId,
    si_range: '', 
    si_unit: '', 
    conv_range: '', 
    conv_unit: '', 
    low_critical_value: '', 
    low_value: '', 
    high_value: '', 
    high_critical_value: '', 
    age_min: '', 
    age_max: '', 
    age_unit: 'Years', 
    gender: 'Any' 
  };
  dialogVisible.value = true;
};

const openEditModal = async (range) => {
  // Ensure equipment is loaded before opening modal
  if (equipment.value.length === 0) {
    await fetchEquipment();
  }
  
  dialogType.value = 'edit';
  const equipmentId = range.equipment_id ? Number(range.equipment_id) : null;
  form.value = { 
    ...range,
    equipment_id: equipmentId,
  };
  dialogVisible.value = true;
};

const handleSubmit = async () => {
  if (!formRef.value) return;
  await formRef.value.validate(async (valid) => {
    if (valid) {
      submitting.value = true;
      try {
        if (dialogType.value === 'add') {
          await api.post('/reference-ranges', form.value);
          ElMessage.success('Reference range added successfully');
        } else {
          await api.put(`/reference-ranges/${form.value.id}`, form.value);
          ElMessage.success('Reference range updated successfully');
        }
        dialogVisible.value = false;
        fetchRanges();
      } catch (error) {
        ElMessage.error('Error saving reference range');
      } finally {
        submitting.value = false;
      }
    }
  });
};

const confirmDelete = (range) => {
  selectedRange.value = range;
  deleteDialogVisible.value = true;
};

const confirmDeleteRange = async () => {
  if (!selectedRange.value) return;
  deleting.value = true;
  try {
    await api.delete(`/reference-ranges/${selectedRange.value.id}`);
    ElMessage.success('Reference range deleted successfully');
    deleteDialogVisible.value = false;
    fetchRanges();
  } catch (error) {
    ElMessage.error('Error deleting reference range');
  } finally {
    deleting.value = false;
  }
};

const loadSelectedEquipment = async () => {
  if (selectedEquipmentId.value) {
    try {
      // Ensure equipment ID is a valid number (extract numeric part if it contains non-numeric characters)
      const equipmentId = String(selectedEquipmentId.value).split(':')[0].trim();
      
      // Validate that it's a valid number
      if (!equipmentId || isNaN(equipmentId) || equipmentId === '') {
        console.warn('Invalid equipment ID:', selectedEquipmentId.value);
        selectedEquipmentName.value = '';
        return;
      }
      
      const response = await api.get(`eim/equipment/${equipmentId}`);
      const eq = response.data;
      selectedEquipmentName.value = `${eq.manufacturer} - ${eq.model}`;
    } catch (error) {
      console.error('Error loading equipment:', error);
      selectedEquipmentName.value = '';
    }
  } else {
    selectedEquipmentName.value = '';
  }
};

// Watch for route changes to update equipment filter
watch(() => route.query.equipment_id, async (newEquipmentId) => {
  if (newEquipmentId) {
    // Extract numeric part from equipment_id (handle cases like "3:1" -> "3")
    const cleanId = String(newEquipmentId).split(':')[0].trim();
    if (cleanId && !isNaN(cleanId)) {
      selectedEquipmentId.value = cleanId;
      await loadSelectedEquipment();
      await fetchRanges();
    } else {
      console.warn('Invalid equipment_id from route:', newEquipmentId);
      selectedEquipmentId.value = null;
      selectedEquipmentName.value = '';
      await fetchRanges();
    }
  } else {
    selectedEquipmentId.value = null;
    selectedEquipmentName.value = '';
    await fetchRanges();
  }
}, { immediate: false });

onMounted(async () => {
  // Get equipment_id from route query if provided
  if (route.query.equipment_id) {
    // Extract numeric part from equipment_id (handle cases like "3:1" -> "3")
    const cleanId = String(route.query.equipment_id).split(':')[0].trim();
    if (cleanId && !isNaN(cleanId)) {
      selectedEquipmentId.value = cleanId;
      await loadSelectedEquipment();
    } else {
      console.warn('Invalid equipment_id from route:', route.query.equipment_id);
    }
  }
  
  await fetchTestCategories();
  await fetchRanges();
  await fetchTestParameters();
  await fetchEquipment();
});
</script>

<style scoped>
.reference-range-management {
  width: 100%;
}
.reference-range-container {
  display: grid;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-xl);
}
.page-title {
  margin: 0;
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-primary);
}
.card-header {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-md);
  align-items: center;
}
.filter-select {
  width: 220px;
}
.search-input {
  width: 300px;
}
.header-spacer {
  width: var(--spacing-md);
}
.equipment-group {
  margin-bottom: var(--spacing-3xl);
  text-align: left;
}
.equipment-title {
  margin-bottom: var(--spacing-sm);
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
}
.ranges-table {
  width: 100%;
}
.pagination-container {
  margin-top: var(--spacing-xl);
  display: flex;
  justify-content: flex-end;
}
.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-md);
}

.equipment-badge {
  color: var(--color-primary);
  font-weight: var(--font-weight-medium);
  font-size: var(--font-size-sm);
  margin-left: var(--spacing-sm);
}

.empty-state {
  padding: var(--spacing-3xl) var(--spacing-xl);
  text-align: center;
}

.reference-range-dialog :deep(.el-divider__text) {
  background-color: var(--color-bg-primary);
  padding: 0 var(--spacing-lg);
  font-weight: var(--font-weight-semibold);
  color: var(--color-primary);
}

.reference-range-dialog :deep(.el-form-item__label) {
  font-weight: var(--font-weight-medium);
}

.reference-range-dialog :deep(.el-input-number) {
  width: 100%;
}
</style> 