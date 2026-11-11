<template>
  <div class="ward-management">
    <div class="page-header">
      <h2>Ward Management</h2>
      <el-button type="primary" @click="openCreateModal">
        <el-icon><Plus /></el-icon>Add Ward
      </el-button>
    </div>
    <el-card>
      <template #header>
        <div class="card-header">
          <el-input
            v-model="searchQuery"
            placeholder="Search wards..."
            class="search-input"
            clearable
            @clear="fetchWards"
            @input="fetchWards"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
        </div>
      </template>
      <el-table
        v-loading="loading"
        :data="wards"
        style="width: 100%"
        border
        stripe
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="Name" />
        <el-table-column label="Actions" width="180" fixed="right">
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
      <div class="pagination-container">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="fetchWards"
          @current-change="fetchWards"
        />
      </div>
    </el-card>
    <!-- Add/Edit Dialog -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogType === 'add' ? 'Add Ward' : 'Edit Ward'"
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
      title="Delete Ward"
      width="400px"
    >
      <p>Are you sure you want to delete this ward?</p>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="deleteDialogVisible = false">Cancel</el-button>
          <el-button type="danger" @click="confirmDeleteWard" :loading="deleting">
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
const wards = ref([]);
const total = ref(0);
const currentPage = ref(1);
const pageSize = ref(10);
const searchQuery = ref('');
const dialogVisible = ref(false);
const deleteDialogVisible = ref(false);
const dialogType = ref('add');
const formRef = ref(null);
const selectedWard = ref(null);

const form = ref({
  name: ''
});

const rules = {
  name: [
    { required: true, message: 'Please enter ward name', trigger: 'blur' }
  ]
};

const fetchWards = async () => {
  loading.value = true;
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
      search: searchQuery.value
    };
    const response = await api.get('/wards', { params });
    let dataArr = [];
    if (Array.isArray(response.data)) {
      dataArr = response.data;
      total.value = dataArr.length;
    } else if (Array.isArray(response.data.data)) {
      dataArr = response.data.data;
      total.value = response.data.total || dataArr.length;
    }
    wards.value = dataArr;
  } catch (error) {
    ElMessage.error('Error fetching wards');
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  dialogType.value = 'add';
  form.value = { name: '' };
  dialogVisible.value = true;
};

const openEditModal = (ward) => {
  dialogType.value = 'edit';
  form.value = { ...ward };
  dialogVisible.value = true;
};

const handleSubmit = async () => {
  if (!formRef.value) return;
  await formRef.value.validate(async (valid) => {
    if (valid) {
      submitting.value = true;
      try {
        if (dialogType.value === 'add') {
          await api.post('/wards', form.value);
          ElMessage.success('Ward added successfully');
        } else {
          await api.put(`/wards/${form.value.id}`, form.value);
          ElMessage.success('Ward updated successfully');
        }
        dialogVisible.value = false;
        fetchWards();
      } catch (error) {
        ElMessage.error('Error saving ward');
      } finally {
        submitting.value = false;
      }
    }
  });
};

const confirmDelete = (ward) => {
  selectedWard.value = ward;
  deleteDialogVisible.value = true;
};

const confirmDeleteWard = async () => {
  if (!selectedWard.value) return;
  deleting.value = true;
  try {
    await api.delete(`/wards/${selectedWard.value.id}`);
    ElMessage.success('Ward deleted successfully');
    deleteDialogVisible.value = false;
    fetchWards();
  } catch (error) {
    ElMessage.error('Error deleting ward');
  } finally {
    deleting.value = false;
  }
};

onMounted(() => {
  fetchWards();
});
</script>

<style scoped>
.ward-management {
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
.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
</style> 