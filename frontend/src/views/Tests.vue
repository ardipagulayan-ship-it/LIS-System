<template>
  <div class="tests">
    <div class="page-header">
      <h2 class="page-title">Tests Management</h2>
      <div class="card-header">
        <el-input
          v-model="searchQuery"
          placeholder="Search tests..."
          class="search-input"
          clearable
          @clear="handleSearch"
          @input="handleSearch"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <span style="margin-left: 10px;"></span>
        <el-button type="primary" @click="handleAdd">
          <el-icon><Plus /></el-icon>Add Test
        </el-button>
      </div>
    </div>
    <div class="test-container">
      <el-card style="height: 100%; overflow-y: auto;">
        <div class="grouped-tests-section">
          <el-collapse v-model="activeCategories">
            <el-collapse-item
              v-for="(group, categoryName) in groupedTests"
              :key="categoryName"
              :title="`${categoryName} (${group.length})`"
              :name="categoryName"
              style="margin-bottom: 20px;"
            >
              <el-table
                :data="group"
                border
                style="width: 100%"
              >
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column prop="name" label="Name" />
                
                <el-table-column prop="price" label="Price" width="120" align="right">
                  <template #default="{ row }">
                    <el-icon style = "color: orange"><PriceTag /></el-icon> {{ formatCurrency(row.price) }}
                  </template>
                </el-table-column>
                <el-table-column prop="description" label="Description" show-overflow-tooltip />
                <el-table-column label="Parameters" width="120" align="center">
                  <template #default="{ row }">
                    <el-button 
                      :type="row.test_parameters_count > 0 ? 'success' : 'info'" 
                      size="small" 
                      @click="openParameterModal(row)"
                    >
                      {{ row.test_parameters_count }}
                    </el-button>
                  </template>
                </el-table-column>
                <el-table-column label="Actions" width="200" fixed="right" align="center">
                  <template #default="{ row }">
                    <el-button-group>
                      <el-button type="primary" size="small" @click="handleEdit(row)">
                        <el-icon><Edit /></el-icon>
                      </el-button>
                      <el-button type="danger" size="small" @click="handleDelete(row)">
                        <el-icon><Delete /></el-icon>
                      </el-button>
                    </el-button-group>
                  </template>
                </el-table-column>
              </el-table>
            </el-collapse-item>
          </el-collapse>
        </div>

        <div class="pagination-container">
          <el-pagination
            v-model:current-page="currentPage"
            v-model:page-size="pageSize"
            :page-sizes="[100, 200, 500, 1000]"
            :total="total"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
          />
        </div>
      </el-card>
    </div>

    <!-- Add/Edit Test Dialog -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogType === 'add' ? 'Add Test' : 'Edit Test'"
      width="700px"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="100px"
      >
        <el-form-item label="Name" prop="name">
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item label="Category" prop="test_category_id">
          <el-select v-model="form.test_category_id" placeholder="Select category">
            <el-option
              v-for="category in categories"
              :key="category.id"
              :label="category.name"
              :value="category.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Price" prop="price">
          <el-input-number
            v-model="form.price"
            :precision="2"
            :step="0.01"
            :min="0"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="Description" prop="description">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="3"
            placeholder="Enter description"
          />
        </el-form-item>
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
      title="Delete Test"
      width="400px"
    >
      <p>Are you sure you want to delete this test?</p>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="deleteDialogVisible = false">Cancel</el-button>
          <el-button type="danger" @click="confirmDelete" :loading="deleting">
            Delete
          </el-button>
        </span>
      </template>
    </el-dialog>

    <!-- Test Parameter Modal -->
    <el-dialog
      v-model="parameterModalVisible"
      title="Test Parameters"
      width="1500px"
      @close="resetParameterModal"
      >
      <template #header>
        <h2 class="page-title">Test Parameters</h2>
      </template>
      <el-divider />
      <div v-if="selectedTestForParameters">
        <div style="display: flex; justify-content: space-between; align-items: center;">
        <div style="margin-bottom: 16px; font-weight: bold;">
          Test: {{ selectedTestForParameters.name }}
        </div>
        <el-button type="primary" @click="openParameterForm()" style="margin-bottom: 12px;">
          <el-icon><Plus /></el-icon> Add Parameter
        </el-button>
        </div>
        <el-table :data="testParameters" border stripe style="width: 100%; margin-bottom: 16px;">
          <el-table-column prop="id" label="ID" width="60" />
          <el-table-column prop="test_name" label="Name" />
          <el-table-column prop="test_code" label="Code" align="center" />
          <el-table-column prop="specimen_type" label="Specimen Type" align="center" />
          <el-table-column prop="unit" label="Unit" align="center"/>
          <el-table-column prop="conversion_factor" label="Conversion Factor" align="right" />
          <el-table-column prop="sequence_no" label="Seq #" width="80" align="center" />
          <el-table-column label="Actions" width="140" align="center" fixed="right">
            <template #default="{ row }">
              <el-button-group>
                <el-button size="small" type="primary" @click="openParameterForm(row)"><el-icon><Edit /></el-icon></el-button>
                <el-button size="small" type="danger" @click="confirmDeleteParameter(row)"><el-icon><Delete /></el-icon></el-button>
              </el-button-group>
            </template>
          </el-table-column>
        </el-table>
        <el-empty v-if="!testParameters.length" description="No parameters found." />
      </div>
      <!-- Add/Edit Parameter Form -->
      <el-dialog v-model="parameterFormVisible" :title="parameterFormType === 'add' ? 'Add Parameter' : 'Edit Parameter'" width="500px" append-to-body>
        <el-form :model="parameterForm" :rules="parameterRules" ref="parameterFormRef" label-width="120px">
          <el-form-item label="Name" prop="test_name">
            <el-input v-model="parameterForm.test_name" />
          </el-form-item>
          <el-form-item label="Code" prop="test_code">
            <el-input v-model="parameterForm.test_code" />
          </el-form-item>
          <el-form-item label="Specimen Type" prop="specimen_type">
            <el-input v-model="parameterForm.specimen_type" />
          </el-form-item>
          <el-form-item label="Unit" prop="unit">
            <el-input v-model="parameterForm.unit" />
          </el-form-item>
          <el-form-item label="Conv. Factor" prop="conversion_factor">
            <el-input v-model="parameterForm.conversion_factor" type="number" />
          </el-form-item>
          <el-form-item label="Sequence No" prop="sequence_no">
            <el-input-number v-model="parameterForm.sequence_no" :min="0" />
          </el-form-item>
        </el-form>
        <template #footer>
          <span class="dialog-footer">
            <el-button @click="parameterFormVisible = false">Cancel</el-button>
            <el-button type="primary" @click="submitParameterForm" :loading="parameterSubmitting">
              {{ parameterFormType === 'add' ? 'Add' : 'Update' }}
            </el-button>
          </span>
        </template>
      </el-dialog>
      <!-- Delete Confirmation -->
      <el-dialog v-model="parameterDeleteVisible" title="Delete Parameter" width="400px" append-to-body>
        <p>Are you sure you want to delete this parameter?</p>
        <template #footer>
          <span class="dialog-footer">
            <el-button @click="parameterDeleteVisible = false">Cancel</el-button>
            <el-button type="danger" @click="deleteParameter" :loading="parameterDeleting">Delete</el-button>
          </span>
        </template>
      </el-dialog>
      <template #footer>
        <el-divider />
        <div class="pagination-container">
          <el-pagination
            v-model:current-page="currentPageTestParameters"
            v-model:page-size="pageSizeTestParameters"
            :page-sizes="[10, 20, 50, 100]"
            :total="totalTestParameters"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChangeTestParameters"
            @current-change="handleCurrentChangeTestParameters"
          />
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Plus, Edit, Delete, Search } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import api from '@/utils/axios';

const loading = ref(false);
const submitting = ref(false);
const deleting = ref(false);
const tests = ref([]);
const categories = ref([]);
const total = ref(0);
const currentPage = ref(1);
const pageSize = ref(100);
const searchQuery = ref('');
const dialogVisible = ref(false);
const deleteDialogVisible = ref(false);
const dialogType = ref('add');
const formRef = ref(null);
const selectedTest = ref(null);
const activeCategories = ref([]);
const parameterModalVisible = ref(false);
const selectedTestForParameters = ref(null);
const testParameters = ref([]);
const parameterFormVisible = ref(false);
const parameterFormType = ref('add');
const parameterForm = ref({
  test_name: '',
  test_code: '',
  specimen_type: '',
  unit: '',
  conversion_factor: '',
  sequence_no: 0
});
const currentPageTestParameters = ref(1);
const pageSizeTestParameters = ref(10);
const totalTestParameters = ref(0);
const parameterFormRef = ref(null);
const parameterSubmitting = ref(false);
const parameterDeleteVisible = ref(false);
const parameterDeleting = ref(false);
const selectedParameter = ref(null);
const parameterRules = {
  test_name: [{ required: true, message: 'Please enter name', trigger: 'blur' }],
  sequence_no: [{ required: true, message: 'Please enter sequence', trigger: 'blur' }]
};

const form = ref({
  name: '',
  test_category_id: '',
  price: 0,
  description: ''
});

const rules = {
  name: [
    { required: true, message: 'Please enter test name', trigger: 'blur' },
    { min: 2, message: 'Name must be at least 2 characters', trigger: 'blur' }
  ],
  test_category_id: [
    { required: true, message: 'Please select category', trigger: 'change' }
  ],
  price: [
    { required: true, message: 'Please enter price', trigger: 'blur' }
  ]
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(value);
};

const fetchTests = async () => {
  loading.value = true;
  const params = {
    page_size: pageSize.value,
    search: searchQuery.value
  };
  try {
    const response = await api.get('/tests', { params});
    tests.value = response.data.data;
    total.value = response.data.total;
  } catch (error) {
    ElMessage.error('Error fetching tests');
    console.error('Error:', error);
  } finally {
    loading.value = false;
  }
};

const fetchCategories = async () => {
  try {
    const response = await api.get('/test-categories');
    categories.value = response.data.data;
  } catch (error) {
    ElMessage.error('Error fetching categories');
    console.error('Error:', error);
  }
};

const handleSearch = () => {
  currentPage.value = 1;
  fetchTests();
};

const handleSizeChange = (val) => {
  pageSize.value = val;
  fetchTests();
};

const handleCurrentChange = (val) => {
  currentPage.value = val;
  fetchTests();
};

const handleSizeChangeTestParameters = (val) => {
  pageSizeTestParameters.value = val;
  fetchTestParameters();
};

const handleCurrentChangeTestParameters = (val) => {
  currentPageTestParameters.value = val;
  fetchTestParameters();
};

const resetForm = () => {
  form.value = {
    name: '',
    test_category_id: '',
    price: 0,
    description: ''
  };
  if (formRef.value) {
    formRef.value.resetFields();
  }
};

const handleAdd = () => {
  dialogType.value = 'add';
  resetForm();
  dialogVisible.value = true;
};

const handleEdit = (row) => {
  dialogType.value = 'edit';
  form.value = { ...row, test_category_id: row.test_category_id || row.category_id };
  dialogVisible.value = true;
};

const handleDelete = (row) => {
  selectedTest.value = row;
  deleteDialogVisible.value = true;
};

const handleSubmit = async () => {
  if (!formRef.value) return;
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      submitting.value = true;
      try {
        if (dialogType.value === 'add') {
          await api.post('/tests', form.value);
          ElMessage.success('Test added successfully');
        } else {
          await api.put(`/tests/${form.value.id}`, form.value);
          ElMessage.success('Test updated successfully');
        }
        dialogVisible.value = false;
        fetchTests();
      } catch (error) {
        ElMessage.error('Error saving test');
        console.error('Error:', error);
      } finally {
        submitting.value = false;
      }
    }
  });
};

const confirmDelete = async () => {
  if (!selectedTest.value) return;
  
  deleting.value = true;
  try {
    await api.delete(`/tests/${selectedTest.value.id}`);
    ElMessage.success('Test deleted successfully');
    deleteDialogVisible.value = false;
    fetchTests();
  } catch (error) {
    ElMessage.error('Error deleting test');
    console.error('Error:', error);
  } finally {
    deleting.value = false;
  }
};

const groupedTests = computed(() => {
  const groups = {};
  for (const test of tests.value) {
    const cat = test.category?.name || 'Uncategorized';
    if (!groups[cat]) groups[cat] = [];
    groups[cat].push(test);
  }
  return groups;
});

function openParameterModal(test) {
  selectedTestForParameters.value = test;
  parameterModalVisible.value = true;
  currentPageTestParameters.value = 1; // Reset to first page
  fetchTestParameters(); // Fetch parameters when modal opens
}

async function fetchTestParameters() {
  if (!selectedTestForParameters.value) return;
  try {
    const params = {
      page: currentPageTestParameters.value,
      page_size: pageSizeTestParameters.value
    };
    const response = await api.get(`/tests/${selectedTestForParameters.value.id}/test-parameters`, { params });
    testParameters.value = response.data.data;
    totalTestParameters.value = response.data.total;
  } catch (error) {
    testParameters.value = [];
    totalTestParameters.value = 0;
    ElMessage.error('Error fetching test parameters');
    console.error('Error:', error);
  }
}

function openParameterForm(param = null) {
  if (param) {
    parameterFormType.value = 'edit';
    parameterForm.value = { ...param };
    selectedParameter.value = param;
  } else {
    parameterFormType.value = 'add';
    parameterForm.value = { test_name: '', test_code: '', specimen_type: '', unit: '', conversion_factor: '', sequence_no: 0, test_id: selectedTestForParameters.value.id };
    selectedParameter.value = null;
  }
  parameterFormVisible.value = true;
}

function resetParameterModal() {
  testParameters.value = [];
  selectedTestForParameters.value = null;
}

async function submitParameterForm() {
  if (!parameterFormRef.value) return;
  await parameterFormRef.value.validate(async (valid) => {
    if (valid) {
      parameterSubmitting.value = true;
      try {
        if (parameterFormType.value === 'add') {
          await api.post('/test-parameters', { ...parameterForm.value, test_id: selectedTestForParameters.value.id });
        } else {
          await api.put(`/test-parameters/${parameterForm.value.id}`, parameterForm.value);
        }
        parameterFormVisible.value = false;
        fetchTestParameters();
        fetchTests(); // update count
      } catch (error) {
        // handle error
      } finally {
        parameterSubmitting.value = false;
      }
    }
  });
}

function confirmDeleteParameter(param) {
  selectedParameter.value = param;
  parameterDeleteVisible.value = true;
}

async function deleteParameter() {
  if (!selectedParameter.value) return;
  parameterDeleting.value = true;
  try {
    await api.delete(`/test-parameters/${selectedParameter.value.id}`);
    parameterDeleteVisible.value = false;
    fetchTestParameters();
    fetchTests(); // update count
  } catch (error) {
    // handle error
  } finally {
    parameterDeleting.value = false;
  }
}

watch(parameterModalVisible, (val) => {
  if (val && selectedTestForParameters.value) fetchTestParameters();
});

onMounted(() => {
  fetchTests();
  fetchCategories();
});
</script>

<style scoped>
.tests {
  width: 100%;
}

.test-container {
  display: grid;
  /* Consider using max-height or similar for better responsiveness */
  height: calc(100vh - 160px); /* Adjust based on header/footer height */
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-header h2 {
  margin: 0;
  font-size: 24px;
  font-weight: 500;
}

.card-header {
  display: flex;
  justify-content: flex-end;
}

.search-input {
  width: 300px;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

:deep(.el-card__header) {
  padding: 15px 20px;
}

/* :deep(.el-table) {
  margin-top: 10px;
} */

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.grouped-tests-section {
  flex-grow: 1; /* Allow collapse section to fill space */
  overflow-y: auto; /* Enable scrolling within the details pane */
  margin: 0 calc(-1 * var(--spacing-lg));
  padding: 0 var(--spacing-lg);
}

.grouped-tests-section .el-collapse {
  border-top: none;
  border-bottom: none;
  margin: 0;
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
</style> 