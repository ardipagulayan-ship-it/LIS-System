<template>
  <el-dialog
    :model-value="modelValue"
    title="Search Patient"
    width="70%"
    :close-on-click-modal="false"
    :destroy-on-close="true"
    @update:model-value="$emit('update:modelValue', $event)"
    @open="onDialogOpen"
  >
    <div class="search-dialog-content">
      <!-- Search Input -->
      <el-input
        v-model="searchQuery"
        placeholder="Search by Patient Name or ID"
        clearable
        @input="handleSearchInput"
        style="margin-bottom: 15px;"
      >
        <template #prepend>
          <el-icon><Search /></el-icon>
        </template>
      </el-input>

      <!-- Results Table -->
      <el-table
        v-loading="loading"
        :data="patients"
        style="width: 100%; margin-bottom: 15px;"
        border
        highlight-current-row
        @row-dblclick="handleRowDoubleClick"
      >
        <el-table-column prop="id" label="Patient ID" width="120" />
        <el-table-column prop="last_name" label="Last Name" sortable />
        <el-table-column prop="first_name" label="First Name" sortable />
        <el-table-column prop="middle_name" label="Middle Name" sortable />
        <el-table-column prop="date_of_birth" label="Date of Birth" width="150">
          <template #default="{ row }">
            {{ formatDate(row.date_of_birth) }}
          </template>
        </el-table-column>
        <el-table-column prop="gender" label="Gender" width="100" />
        <el-table-column label="Actions" width="120" align="center">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="selectPatient(row)">
              Select
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- Pagination -->
      <el-pagination
        v-if="total > 0"
        layout="prev, pager, next, total, sizes"
        :total="total"
        :page-sizes="[10, 20, 50]"
        :page-size="pageSize"
        :current-page="currentPage"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
        style="justify-content: flex-end;"
      />
    </div>

    <template #footer>
      <span class="dialog-footer">
        <el-button @click="$emit('update:modelValue', false)">Cancel</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import { ElMessage } from 'element-plus';
import api from '@/utils/axios';
import { format, parseISO } from 'date-fns'; // For date formatting
import { Search } from '@element-plus/icons-vue';
import debounce from 'lodash/debounce'; // For debouncing search input

const props = defineProps({
  modelValue: Boolean, // Controls dialog visibility
});

const emit = defineEmits(['update:modelValue', 'patient-selected']);

// State
const loading = ref(false);
const patients = ref([]);
const searchQuery = ref('');
const currentPage = ref(1);
const pageSize = ref(10);
const total = ref(0);

// Fetch Patients Function
const fetchPatients = async () => {
  loading.value = true;
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
      search: searchQuery.value,
      // Add any other specific filters needed for patient search
    };
    // Remove empty params
    Object.keys(params).forEach(key => (params[key] === '' || params[key] === null) && delete params[key]);

    // Adjust API endpoint if necessary (e.g., if it's just '/patients')
    const response = await api.get('/patients', { params });
    patients.value = response.data.data;
    total.value = response.data.total;
  } catch (error) {
    ElMessage.error('Error fetching patients');
    console.error('Error fetching patients:', error);
    patients.value = [];
    total.value = 0;
  } finally {
    loading.value = false;
  }
};

// Debounced search handler
const debouncedFetchPatients = debounce(fetchPatients, 300);

const handleSearchInput = () => {
  currentPage.value = 1; // Reset to first page on new search
  debouncedFetchPatients();
};

// Pagination Handlers
const handleSizeChange = (newSize) => {
  pageSize.value = newSize;
  currentPage.value = 1; // Reset to first page
  fetchPatients();
};

const handleCurrentChange = (newPage) => {
  currentPage.value = newPage;
  fetchPatients();
};

// Selection Handler
const selectPatient = (patient) => {
  console.log('Selected patient in dialog:', patient); // Debug log
  emit('patient-selected', patient);
  emit('update:modelValue', false); // Close dialog after selection
};

// Double click handler
const handleRowDoubleClick = (row) => {
  selectPatient(row);
};

// Fetch initial data when dialog opens
const onDialogOpen = () => {
  if (patients.value.length === 0) { // Only fetch if list is empty
      fetchPatients();
  }
};

// Utility: Format Date
const formatDate = (dateString) => {
  if (!dateString) return '';
  try {
    return format(parseISO(dateString), 'yyyy-MM-dd');
  } catch (e) {
    console.error('Error formatting date:', e);
    return dateString; // Return original string if parsing fails
  }
};

// Watch for modelValue to change to false, then reset state if needed (optional)
// watch(() => props.modelValue, (newValue) => {
//   if (!newValue) {
//     // Optional: Reset search state when dialog closes
//     // searchQuery.value = '';
//     // patients.value = [];
//     // currentPage.value = 1;
//     // total.value = 0;
//   }
// });

</script>

<style scoped>
.search-dialog-content {
  /* Add any specific styling if needed */
  height: 500px;
  overflow-y: auto;
}
.dialog-footer {
  text-align: right;
}
</style> 