<template>
  <div class="test-results">
    <div class="page-header">
      <h2>Test Results</h2>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>Record Results
      </el-button>
    </div>

    <el-card>
      <template #header>
        <div class="card-header">
          <el-input
            v-model="searchQuery"
            placeholder="Search results..."
            class="search-input"
            clearable
            @clear="handleSearch"
            @input="handleSearch"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
        </div>
      </template>

      <el-table
        v-loading="loading"
        :data="results"
        style="width: 100%"
        border
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="laboratory_request.patient.name" label="Patient" />
        <el-table-column prop="laboratory_request.doctor.name" label="Doctor" />
        <el-table-column prop="test.name" label="Test" />
        <el-table-column prop="result" label="Result" />
        <el-table-column prop="unit" label="Unit" width="100" />
        <el-table-column prop="reference_range" label="Reference Range" width="150" />
        <el-table-column prop="status" label="Status" width="120">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ row.status }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="reported_at" label="Reported At" width="150">
          <template #default="{ row }">
            {{ formatDate(row.reported_at) }}
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="200" fixed="right">
          <template #default="{ row }">
            <el-button-group>
              <el-button type="primary" size="small" @click="handleView(row)">
                <el-icon><View /></el-icon>
              </el-button>
              <el-button 
                type="success" 
                size="small" 
                @click="handleEdit(row)"
                :disabled="row.status === 'Final'"
              >
                <el-icon><Edit /></el-icon>
              </el-button>
              <el-button 
                type="danger" 
                size="small" 
                @click="handleDelete(row)"
                :disabled="row.status === 'Final'"
              >
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
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- Add/Edit Result Dialog -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogType === 'add' ? 'Record Test Results' : 'Edit Results'"
      width="800px"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="140px"
      >
        <el-form-item label="Laboratory Request" prop="laboratory_request_id">
          <el-select 
            v-model="form.laboratory_request_id" 
            placeholder="Select request"
            filterable
            remote
            :remote-method="searchRequests"
            :loading="loadingRequests"
            style="width: 100%"
          >
            <el-option
              v-for="request in requests"
              :key="request.id"
              :label="`${request.patient.name} - ${request.doctor.name}`"
              :value="request.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Test" prop="test_id">
          <el-select 
            v-model="form.test_id" 
            placeholder="Select test"
            style="width: 100%"
          >
            <el-option
              v-for="test in availableTests"
              :key="test.id"
              :label="test.name"
              :value="test.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Result" prop="result">
          <el-input v-model="form.result" />
        </el-form-item>
        <el-form-item label="Unit" prop="unit">
          <el-input v-model="form.unit" />
        </el-form-item>
        <el-form-item label="Reference Range" prop="reference_range">
          <el-input v-model="form.reference_range" />
        </el-form-item>
        <el-form-item label="Status" prop="status">
          <el-select v-model="form.status" placeholder="Select status">
            <el-option label="Draft" value="Draft" />
            <el-option label="Final" value="Final" />
          </el-select>
        </el-form-item>
        <el-form-item label="Notes" prop="notes">
          <el-input
            v-model="form.notes"
            type="textarea"
            :rows="3"
            placeholder="Enter notes"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="dialogVisible = false">Cancel</el-button>
          <el-button type="primary" @click="handleSubmit" :loading="submitting">
            {{ dialogType === 'add' ? 'Record' : 'Update' }}
          </el-button>
        </span>
      </template>
    </el-dialog>

    <!-- View Result Dialog -->
    <el-dialog
      v-model="viewDialogVisible"
      title="Test Result Details"
      width="800px"
    >
      <div v-if="selectedResult" class="result-details">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="Patient">
            {{ selectedResult.laboratory_request.patient.name }}
          </el-descriptions-item>
          <el-descriptions-item label="Doctor">
            {{ selectedResult.laboratory_request.doctor.name }}
          </el-descriptions-item>
          <el-descriptions-item label="Test">
            {{ selectedResult.test.name }}
          </el-descriptions-item>
          <el-descriptions-item label="Status">
            <el-tag :type="getStatusType(selectedResult.status)">
              {{ selectedResult.status }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="Result">
            {{ selectedResult.result }} {{ selectedResult.unit }}
          </el-descriptions-item>
          <el-descriptions-item label="Reference Range">
            {{ selectedResult.reference_range }}
          </el-descriptions-item>
          <el-descriptions-item label="Reported At">
            {{ formatDate(selectedResult.reported_at) }}
          </el-descriptions-item>
        </el-descriptions>

        <div v-if="selectedResult.notes" class="notes">
          <h3>Notes</h3>
          <p>{{ selectedResult.notes }}</p>
        </div>
      </div>
    </el-dialog>

    <!-- Delete Confirmation Dialog -->
    <el-dialog
      v-model="deleteDialogVisible"
      title="Delete Result"
      width="400px"
    >
      <p>Are you sure you want to delete this result?</p>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="deleteDialogVisible = false">Cancel</el-button>
          <el-button type="danger" @click="confirmDelete" :loading="deleting">
            Delete
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Plus, Edit, Delete, Search, View } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import api from '@/utils/axios';

const loading = ref(false);
const submitting = ref(false);
const deleting = ref(false);
const results = ref([]);
const requests = ref([]);
const tests = ref([]);
const loadingRequests = ref(false);
const total = ref(0);
const currentPage = ref(1);
const pageSize = ref(10);
const searchQuery = ref('');
const dialogVisible = ref(false);
const viewDialogVisible = ref(false);
const deleteDialogVisible = ref(false);
const dialogType = ref('add');
const formRef = ref(null);
const selectedResult = ref(null);

const form = ref({
  laboratory_request_id: '',
  test_id: '',
  result: '',
  unit: '',
  reference_range: '',
  status: 'Draft',
  notes: ''
});

const rules = {
  laboratory_request_id: [
    { required: true, message: 'Please select laboratory request', trigger: 'change' }
  ],
  test_id: [
    { required: true, message: 'Please select test', trigger: 'change' }
  ],
  result: [
    { required: true, message: 'Please enter result', trigger: 'blur' }
  ],
  unit: [
    { required: true, message: 'Please enter unit', trigger: 'blur' }
  ],
  reference_range: [
    { required: true, message: 'Please enter reference range', trigger: 'blur' }
  ],
  status: [
    { required: true, message: 'Please select status', trigger: 'change' }
  ]
};

const availableTests = computed(() => {
  if (!form.value.laboratory_request_id) return [];
  const request = requests.value.find(r => r.id === form.value.laboratory_request_id);
  return request ? request.tests : [];
});

const formatDate = (date) => {
  return new Date(date).toLocaleString();
};

const getStatusType = (status) => {
  const types = {
    'Draft': 'warning',
    'Final': 'success'
  };
  return types[status] || 'info';
};

const fetchResults = async () => {
  loading.value = true;
  try {
    const response = await api.get('/test-results', {
      params: {
        page: currentPage.value,
        per_page: pageSize.value,
        search: searchQuery.value
      }
    });
    results.value = response.data.data;
    total.value = response.data.total;
  } catch (error) {
    ElMessage.error('Error fetching results');
    console.error('Error:', error);
  } finally {
    loading.value = false;
  }
};

const fetchTests = async () => {
  try {
    const response = await api.get('/tests');
    tests.value = response.data.data;
  } catch (error) {
    ElMessage.error('Error fetching tests');
    console.error('Error:', error);
  }
};

const searchRequests = async (query) => {
  if (query) {
    loadingRequests.value = true;
    try {
      const response = await api.get('/laboratory-requests', {
        params: { 
          search: query,
          status: 'In Progress'
        }
      });
      requests.value = response.data.data;
    } catch (error) {
      console.error('Error:', error);
    } finally {
      loadingRequests.value = false;
    }
  } else {
    requests.value = [];
  }
};

const handleSearch = () => {
  currentPage.value = 1;
  fetchResults();
};

const handleSizeChange = (val) => {
  pageSize.value = val;
  fetchResults();
};

const handleCurrentChange = (val) => {
  currentPage.value = val;
  fetchResults();
};

const resetForm = () => {
  form.value = {
    laboratory_request_id: '',
    test_id: '',
    result: '',
    unit: '',
    reference_range: '',
    status: 'Draft',
    notes: ''
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

const handleView = (row) => {
  selectedResult.value = row;
  viewDialogVisible.value = true;
};

const handleEdit = (row) => {
  dialogType.value = 'edit';
  form.value = { ...row };
  dialogVisible.value = true;
};

const handleDelete = (row) => {
  selectedResult.value = row;
  deleteDialogVisible.value = true;
};

const handleSubmit = async () => {
  if (!formRef.value) return;
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      submitting.value = true;
      try {
        if (dialogType.value === 'add') {
          await api.post('/test-results', form.value);
          ElMessage.success('Results recorded successfully');
        } else {
          await api.put(`/test-results/${form.value.id}`, form.value);
          ElMessage.success('Results updated successfully');
        }
        dialogVisible.value = false;
        fetchResults();
      } catch (error) {
        ElMessage.error('Error saving results');
        console.error('Error:', error);
      } finally {
        submitting.value = false;
      }
    }
  });
};

const confirmDelete = async () => {
  if (!selectedResult.value) return;
  
  deleting.value = true;
  try {
    await api.delete(`/test-results/${selectedResult.value.id}`);
    ElMessage.success('Result deleted successfully');
    deleteDialogVisible.value = false;
    fetchResults();
  } catch (error) {
    ElMessage.error('Error deleting result');
    console.error('Error:', error);
  } finally {
    deleting.value = false;
  }
};

onMounted(() => {
  fetchResults();
  fetchTests();
});
</script>

<style scoped>
.test-results {
  width: 100%;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-xl);
}

.page-header h2 {
  margin: 0;
  font-size: var(--font-size-2xl, 24px);
  font-weight: var(--font-weight-medium);
}

.card-header {
  display: flex;
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

:deep(.el-card__header) {
  padding: var(--spacing-md) var(--spacing-xl);
}

:deep(.el-table) {
  margin-top: var(--spacing-sm);
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-md);
}

.result-details {
  padding: var(--spacing-xl);
}

.notes {
  margin-top: var(--spacing-xl);
}

.notes h3 {
  margin-bottom: var(--spacing-md);
}

.notes p {
  margin: 0;
  color: var(--color-text-secondary, #666);
}
</style> 