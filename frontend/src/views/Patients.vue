<template>
  <div class="patients">
    <div class="page-header">
      <h2>Patients</h2>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>Add Patient
      </el-button>
    </div>

    <el-card>
      <template #header>
        <div class="card-header">
          <el-input
            v-model="searchQuery"
            placeholder="Search patients..."
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
        :data="patients"
        style="width: 100%"
        border
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="lname" label="Last Name" />
        <el-table-column prop="fname" label="First Name" />
        <el-table-column prop="mname" label="Middle Name" />
        <el-table-column prop="email" label="Email" />
        <el-table-column prop="phone" label="Phone" width="150" />
        <el-table-column prop="gender" label="Gender" width="100">
          <template #default="{ row }">
            {{ row.gender === 'M' ? 'Male' : 'Female' }}
          </template>
        </el-table-column>
        <el-table-column prop="date_of_birth" label="Date of Birth" width="120">
          <template #default="{ row }">
            {{ formatDate(row.date_of_birth) }}
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="200" fixed="right">
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

    <!-- Add/Edit Patient Dialog -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogType === 'add' ? 'Add Patient' : 'Edit Patient'"
      width="500px"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="100px"
      >
        <el-form-item label="Last Name" prop="lname">
          <el-input v-model="form.lname" />
        </el-form-item>
        <el-form-item label="First Name" prop="fname">
          <el-input v-model="form.fname" />
        </el-form-item>
        <el-form-item label="Middle Name" prop="mname">
          <el-input v-model="form.mname" />
        </el-form-item>
        <el-form-item label="Email" prop="email">
          <el-input v-model="form.email" />
        </el-form-item>
        <el-form-item label="Phone" prop="phone">
          <el-input v-model="form.phone" />
        </el-form-item>
        <el-form-item label="Gender" prop="gender">
          <el-select v-model="form.gender" placeholder="Select gender">
            <el-option label="Male" value="M" />
            <el-option label="Female" value="F" />
          </el-select>
        </el-form-item>
        <el-form-item label="Date of Birth" prop="date_of_birth">
          <el-date-picker
            v-model="form.date_of_birth"
            type="date"
            placeholder="Select date"
          />
        </el-form-item>
        <el-form-item label="Address" prop="address">
          <el-input
            v-model="form.address"
            type="textarea"
            :rows="3"
            placeholder="Enter address"
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
      title="Delete Patient"
      width="400px"
    >
      <p>Are you sure you want to delete this patient?</p>
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
import { ref, onMounted } from 'vue';
import { Plus, Edit, Delete, Search } from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import api from '@/utils/axios';

const loading = ref(false);
const submitting = ref(false);
const deleting = ref(false);
const patients = ref([]);
const total = ref(0);
const currentPage = ref(1);
const pageSize = ref(10);
const searchQuery = ref('');
const dialogVisible = ref(false);
const deleteDialogVisible = ref(false);
const dialogType = ref('add');
const formRef = ref(null);
const selectedPatient = ref(null);

const form = ref({
  lname: '',
  fname: '',
  mname: '',  
  email: '',
  phone: '',
  gender: '',
  date_of_birth: '',
  address: ''
});

const rules = {
  name: [
    { required: true, message: 'Please enter patient name', trigger: 'blur' },
    { min: 2, message: 'Name must be at least 2 characters', trigger: 'blur' }
  ],
  email: [
    { required: true, message: 'Please enter email address', trigger: 'blur' },
    { type: 'email', message: 'Please enter a valid email address', trigger: 'blur' }
  ],
  phone: [
    { required: true, message: 'Please enter phone number', trigger: 'blur' }
  ],
  gender: [
    { required: true, message: 'Please select gender', trigger: 'change' }
  ],
  date_of_birth: [
    { required: true, message: 'Please select date of birth', trigger: 'change' }
  ]
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString();
};

const fetchPatients = async () => {
  loading.value = true;
  try {
    const response = await api.get('/patients', {
      params: {
        page: currentPage.value,
        per_page: pageSize.value,
        search: searchQuery.value
      }
    });
    patients.value = response.data.data;
    total.value = response.data.total;
  } catch (error) {
    ElMessage.error('Error fetching patients');
    console.error('Error:', error);
  } finally {
    loading.value = false;
  }
};

const handleSearch = () => {
  currentPage.value = 1;
  fetchPatients();
};

const handleSizeChange = (val) => {
  pageSize.value = val;
  fetchPatients();
};

const handleCurrentChange = (val) => {
  currentPage.value = val;
  fetchPatients();
};

const resetForm = () => {
  form.value = {
    lname: '',
    fname: '',
    mname: '',
    email: '',
    phone: '',
    gender: '',
    date_of_birth: '',
    address: ''
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
  form.value = { ...row };
  dialogVisible.value = true;
};

const handleDelete = (row) => {
  selectedPatient.value = row;
  deleteDialogVisible.value = true;
};

const handleSubmit = async () => {
  if (!formRef.value) return;
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      submitting.value = true;
      try {
        if (dialogType.value === 'add') {
          await api.post('/patients', form.value);
          ElMessage.success('Patient added successfully');
        } else {
          await api.put(`/patients/${form.value.id}`, form.value);
          ElMessage.success('Patient updated successfully');
        }
        dialogVisible.value = false;
        fetchPatients();
      } catch (error) {
        ElMessage.error('Error saving patient');
        console.error('Error:', error);
      } finally {
        submitting.value = false;
      }
    }
  });
};

const confirmDelete = async () => {
  if (!selectedPatient.value) return;
  
  deleting.value = true;
  try {
    await api.delete(`/patients/${selectedPatient.value.id}`);
    ElMessage.success('Patient deleted successfully');
    deleteDialogVisible.value = false;
    fetchPatients();
  } catch (error) {
    ElMessage.error('Error deleting patient');
    console.error('Error:', error);
  } finally {
    deleting.value = false;
  }
};

onMounted(() => {
  fetchPatients();
});
</script>

<style scoped>
.patients {
  width: 100%;
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

:deep(.el-table) {
  margin-top: 10px;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
</style> 