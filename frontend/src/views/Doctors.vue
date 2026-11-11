<template>
  <div class="doctors">
    <div class="page-header">
      <h2>Doctors</h2>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>Add Doctor
      </el-button>
    </div>

    <el-card>
      <template #header>
        <div class="card-header">
          <el-input
            v-model="searchQuery"
            placeholder="Search doctors..."
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
        :data="doctors"
        style="width: 100%"
        border
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="Name" />
        <el-table-column prop="email" label="Email" />
        <el-table-column prop="phone" label="Phone" width="150" />
        <el-table-column prop="specialization" label="Specialization" />
        <el-table-column prop="gender" label="Gender" width="100">
          <template #default="{ row }">
            {{ row.gender === 'M' ? 'Male' : 'Female' }}
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

    <!-- Add/Edit Doctor Dialog -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogType === 'add' ? 'Add Doctor' : 'Edit Doctor'"
      width="500px"
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
        <el-form-item label="Email" prop="email">
          <el-input v-model="form.email" />
        </el-form-item>
        <el-form-item label="Phone" prop="phone">
          <el-input v-model="form.phone" />
        </el-form-item>
        <el-form-item label="Specialization" prop="specialization">
          <el-input v-model="form.specialization" />
        </el-form-item>
        <el-form-item label="Gender" prop="gender">
          <el-select v-model="form.gender" placeholder="Select gender">
            <el-option label="Male" value="M" />
            <el-option label="Female" value="F" />
          </el-select>
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
      title="Delete Doctor"
      width="400px"
    >
      <p>Are you sure you want to delete this doctor?</p>
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
import { ElMessage } from 'element-plus';
import api from '@/utils/axios';

const loading = ref(false);
const submitting = ref(false);
const deleting = ref(false);
const doctors = ref([]);
const total = ref(0);
const currentPage = ref(1);
const pageSize = ref(10);
const searchQuery = ref('');
const dialogVisible = ref(false);
const deleteDialogVisible = ref(false);
const dialogType = ref('add');
const formRef = ref(null);
const selectedDoctor = ref(null);

const form = ref({
  name: '',
  email: '',
  phone: '',
  specialization: '',
  gender: '',
  address: ''
});

const rules = {
  name: [
    { required: true, message: 'Please enter doctor name', trigger: 'blur' },
    { min: 2, message: 'Name must be at least 2 characters', trigger: 'blur' }
  ],
  email: [
    { required: true, message: 'Please enter email address', trigger: 'blur' },
    { type: 'email', message: 'Please enter a valid email address', trigger: 'blur' }
  ],
  phone: [
    { required: true, message: 'Please enter phone number', trigger: 'blur' }
  ],
  specialization: [
    { required: true, message: 'Please enter specialization', trigger: 'blur' }
  ],
  gender: [
    { required: true, message: 'Please select gender', trigger: 'change' }
  ]
};

const fetchDoctors = async () => {
  loading.value = true;
  try {
    const response = await api.get('/doctors', {
      params: {
        page: currentPage.value,
        per_page: pageSize.value,
        search: searchQuery.value
      }
    });
    doctors.value = response.data.data;
    total.value = response.data.total;
  } catch (error) {
    ElMessage.error('Error fetching doctors');
    console.error('Error:', error);
  } finally {
    loading.value = false;
  }
};

const handleSearch = () => {
  currentPage.value = 1;
  fetchDoctors();
};

const handleSizeChange = (val) => {
  pageSize.value = val;
  fetchDoctors();
};

const handleCurrentChange = (val) => {
  currentPage.value = val;
  fetchDoctors();
};

const resetForm = () => {
  form.value = {
    name: '',
    email: '',
    phone: '',
    specialization: '',
    gender: '',
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
  selectedDoctor.value = row;
  deleteDialogVisible.value = true;
};

const handleSubmit = async () => {
  if (!formRef.value) return;
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      submitting.value = true;
      try {
        if (dialogType.value === 'add') {
          await api.post('/doctors', form.value);
          ElMessage.success('Doctor added successfully');
        } else {
          await api.put(`/doctors/${form.value.id}`, form.value);
          ElMessage.success('Doctor updated successfully');
        }
        dialogVisible.value = false;
        fetchDoctors();
      } catch (error) {
        ElMessage.error('Error saving doctor');
        console.error('Error:', error);
      } finally {
        submitting.value = false;
      }
    }
  });
};

const confirmDelete = async () => {
  if (!selectedDoctor.value) return;
  
  deleting.value = true;
  try {
    await api.delete(`/doctors/${selectedDoctor.value.id}`);
    ElMessage.success('Doctor deleted successfully');
    deleteDialogVisible.value = false;
    fetchDoctors();
  } catch (error) {
    ElMessage.error('Error deleting doctor');
    console.error('Error:', error);
  } finally {
    deleting.value = false;
  }
};

onMounted(() => {
  fetchDoctors();
});
</script>

<style scoped>
.doctors {
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