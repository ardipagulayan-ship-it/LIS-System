<template>
  <div class="room-management">
    <div class="page-header">
      <h2>Room Management</h2>
      <el-button type="primary" @click="openCreateModal">
        <el-icon><Plus /></el-icon>Add Room
      </el-button>
    </div>
    <el-card>
      <template #header>
        <div class="card-header">
          <el-input
            v-model="searchQuery"
            placeholder="Search rooms..."
            class="search-input"
            clearable
            @clear="fetchRooms"
            @input="fetchRooms"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
        </div>
      </template>
      <el-table
        v-loading="loading"
        :data="rooms"
        style="width: 100%"
        border
        stripe
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="Name" />
        <el-table-column prop="ward_name" label="Ward" />
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
          @size-change="fetchRooms"
          @current-change="fetchRooms"
        />
      </div>
    </el-card>
    <!-- Add/Edit Dialog -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogType === 'add' ? 'Add Room' : 'Edit Room'"
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
        <el-form-item label="Ward" prop="ward_id">
          <el-select v-model="form.ward_id" placeholder="Select ward">
            <el-option
              v-for="ward in wards"
              :key="ward.id"
              :label="ward.name"
              :value="ward.id"
            />
          </el-select>
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
      title="Delete Room"
      width="400px"
    >
      <p>Are you sure you want to delete this room?</p>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="deleteDialogVisible = false">Cancel</el-button>
          <el-button type="danger" @click="confirmDeleteRoom" :loading="deleting">
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
const rooms = ref([]);
const wards = ref([]);
const total = ref(0);
const currentPage = ref(1);
const pageSize = ref(10);
const searchQuery = ref('');
const dialogVisible = ref(false);
const deleteDialogVisible = ref(false);
const dialogType = ref('add');
const formRef = ref(null);
const selectedRoom = ref(null);

const form = ref({
  name: '',
  ward_id: ''
});

const rules = {
  name: [
    { required: true, message: 'Please enter room name', trigger: 'blur' }
  ],
  ward_id: [
    { required: true, message: 'Please select a ward', trigger: 'change' }
  ]
};

const fetchRooms = async () => {
  loading.value = true;
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
      search: searchQuery.value
    };
    const response = await api.get('/rooms', { params });
    let dataArr = [];
    if (Array.isArray(response.data)) {
      dataArr = response.data;
      total.value = dataArr.length;
    } else if (Array.isArray(response.data.data)) {
      dataArr = response.data.data;
      total.value = response.data.total || dataArr.length;
    }
    rooms.value = dataArr.map(room => ({
      ...room,
      ward_name: room.ward?.name || ''
    }));
    total.value = response.data.total;
  } catch (error) {
    ElMessage.error('Error fetching rooms');
  } finally {
    loading.value = false;
  }
};

const fetchRanges = async () => {
  loading.value = true;
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
      search: searchQuery.value
    };
    const response = await api.get('/reference-ranges', { params });
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
      test_parameter_name: range.test_parameter?.name || '',
      analyzer_name: range.analyzer?.name || ''
    }));
  } catch (error) {
    console.error('Error fetching reference ranges:', error);
    ElMessage.error('Error fetching reference ranges');
  } finally {
    loading.value = false;
  }
};

const fetchWards = async () => {
  try {
    const response = await api.get('/wards');
    wards.value = response.data;
  } catch (error) {
    ElMessage.error('Error fetching wards');
  }
};

const openCreateModal = () => {
  dialogType.value = 'add';
  form.value = { name: '', ward_id: '' };
  dialogVisible.value = true;
};

const openEditModal = (room) => {
  dialogType.value = 'edit';
  form.value = { ...room };
  dialogVisible.value = true;
};

const handleSubmit = async () => {
  if (!formRef.value) return;
  await formRef.value.validate(async (valid) => {
    if (valid) {
      submitting.value = true;
      try {
        if (dialogType.value === 'add') {
          await api.post('/rooms', form.value);
          ElMessage.success('Room added successfully');
        } else {
          await api.put(`/rooms/${form.value.id}`, form.value);
          ElMessage.success('Room updated successfully');
        }
        dialogVisible.value = false;
        fetchRooms();
      } catch (error) {
        ElMessage.error('Error saving room');
      } finally {
        submitting.value = false;
      }
    }
  });
};

const confirmDelete = (room) => {
  selectedRoom.value = room;
  deleteDialogVisible.value = true;
};

const confirmDeleteRoom = async () => {
  if (!selectedRoom.value) return;
  deleting.value = true;
  try {
    await api.delete(`/rooms/${selectedRoom.value.id}`);
    ElMessage.success('Room deleted successfully');
    deleteDialogVisible.value = false;
    fetchRooms();
  } catch (error) {
    ElMessage.error('Error deleting room');
  } finally {
    deleting.value = false;
  }
};

onMounted(() => {
  fetchRooms();
  fetchWards();
});
</script>

<style scoped>
.room-management {
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