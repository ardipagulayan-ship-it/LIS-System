<template>
  <el-dialog
    :model-value="modelValue"
    title="Search Patient"
    width="900px"
    class="patient-search-dialog"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div class="search-header">
      <el-input
        v-model="searchQuery"
        placeholder="Search by ID, name, or contact number..."
        class="search-input"
        clearable
        @input="handleSearchInput"
      >
        <template #prefix>
          <el-icon><Search /></el-icon>
        </template>
      </el-input>
    </div>

    <el-table
      v-loading="loading"
      :data="patients"
      style="width: 100%"
      border
      highlight-current-row
      @row-click="selectPatient"
    >
      <el-table-column prop="id" label="Patient ID" width="120" />
      <el-table-column prop="last_name" label="Last Name" width="150" />
      <el-table-column prop="first_name" label="First Name" width="150" />
      <el-table-column prop="middle_name" label="Middle Name" width="150" />
      <el-table-column prop="date_of_birth" label="Date of Birth" width="120">
        <template #default="{ row }">
          {{ formatDate(row.dateOfBirth) }}
        </template>
      </el-table-column>
      <el-table-column prop="phone" label="Contact No." width="120" />
      <el-table-column prop="address" label="Address" min-width="200" show-overflow-tooltip />
    </el-table>

    <div class="pagination-container">
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :page-sizes="[10, 20, 50]"
        :total="total"
        layout="total, sizes, prev, pager, next"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
      />
    </div>
  </el-dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Search } from '@element-plus/icons-vue';
import { debounce } from 'lodash';
import api from '@/utils/axios';
import { format, parseISO } from 'date-fns'; // For date formatting

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true
  }
});

const emit = defineEmits(['update:modelValue', 'select-patient']);

const searchQuery = ref('');
const loading = ref(false);
const patients = ref([]);
const currentPage = ref(1);
const pageSize = ref(10);
const total = ref(0);

const formatDate = (dateString) => {
  if (!dateString) return '';
  try {
    return format(parseISO(dateString), 'yyyy-MM-dd');
  } catch (e) {
    console.error('Error formatting date:', e);
    return dateString; // Return original string if parsing fails
  }
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
    console.error('Error fetching patients:', error);
  } finally {
    loading.value = false;
  }
};

const handleSearchInput = debounce(() => {
  currentPage.value = 1;
  fetchPatients();
}, 300);

const handleSizeChange = (val) => {
  pageSize.value = val;
  fetchPatients();
};

const handleCurrentChange = (val) => {
  currentPage.value = val;
  fetchPatients();
};

const selectPatient = (row) => {
  emit('select-patient', row);
  emit('update:modelValue', false);
};

watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    searchQuery.value = '';
    currentPage.value = 1;
    fetchPatients();
  }
});
</script>

<style scoped>
.patient-search-dialog {
  .search-header {
    margin-bottom: 20px;
    
    .search-input {
      width: 100%;
    }
  }
  
  :deep(.el-dialog__body) {
    padding: 20px;
  }
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style> 