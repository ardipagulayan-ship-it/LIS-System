<template>
  <div class="worksheet-container">
    <!-- Header -->
    <div class="worksheet-header">
      <div class="header-tabs">
        <div class="tab active">HOME</div>
        <div class="tab active">WORKSHEET</div>
      </div>
      <div class="header-actions">
        <el-button type="primary" :icon="Printer" @click="printReport">Print</el-button>
        <el-button type="default" :icon="Refresh" @click="refreshData">Refresh</el-button>
        <el-button type="default" :icon="Close" @click="closeWorksheet">Close</el-button>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
      <div class="filter-row">
        <div class="filter-group">
          <label>Section:</label>
          <el-select v-model="selectedSection" placeholder="Select Section" @change="onSectionChange">
            <el-option
              v-for="section in testCategories"
              :key="section.id"
              :label="section.name"
              :value="section.id"
            />
          </el-select>
        </div>

        <div class="filter-group">
          <label>Patient Type:</label>
          <el-select v-model="selectedPatientType" placeholder="All">
            <el-option label="All" value="all" />
            <el-option
              v-for="type in patientTypes"
              :key="type.id"
              :label="type.name"
              :value="type.id"
            />
          </el-select>
        </div>

        <div class="filter-group">
          <label>From:</label>
          <el-date-picker
            v-model="dateFrom"
            type="datetime"
            placeholder="Select date and time"
            format="MM/DD/YYYY hh:mm:ss A"
            value-format="YYYY-MM-DD HH:mm:ss"
          />
        </div>

        <div class="filter-group">
          <label>To:</label>
          <el-date-picker
            v-model="dateTo"
            type="datetime"
            placeholder="Select date and time"
            format="MM/DD/YYYY hh:mm:ss A"
            value-format="YYYY-MM-DD HH:mm:ss"
          />
        </div>

        <el-button type="primary" :icon="Search" @click="applyFilters">Apply</el-button>
      </div>
    </div>

    <!-- Content Tabs -->
    <div class="content-tabs">
      <div class="tab active">List</div>
    </div>

    <!-- Data Table -->
    <div class="table-container">
      <el-table
        :data="worksheetData"
        v-loading="loading"
        stripe
        border
        height="600"
        @row-click="onRowClick"
        :row-class-name="getRowClassName"
      >
        <!-- Dynamic columns based on selected section -->
        <el-table-column prop="sample_id" label="Sample ID" fixed="left" />
        <el-table-column prop="patient_id" label="Patient ID"  />
        <el-table-column prop="patient_name" label="Patient Name"  />
        <el-table-column prop="date_of_birth" label="Date Of Birth"  />
        <el-table-column prop="sex" label="Sex"  />
        <el-table-column prop="age" label="Age"  />
        <el-table-column prop="requestor" label="Requestor"  />
        <el-table-column prop="med_tech" label="Med Tech"  />
        <el-table-column prop="verified_by" label="Verified By"  />
        <el-table-column prop="date_collected" label="Date Collected"  />
        <el-table-column prop="date_released" label="Date Released"  />
        <el-table-column prop="patient_type" label="Patient Type"  />
        <el-table-column prop="ward" label="Ward"  />

        <!-- Dynamic test result columns based on section -->
        <template v-if="selectedSection && dynamicColumns.length > 0">
          <el-table-column
            v-for="column in dynamicColumns"
            :key="column.key"
            :prop="column.key"
            :label="column.label"
            :width="column.width"
            align="center"
          />
        </template>
      </el-table>
    </div>

    <!-- Footer -->
    <div class="worksheet-footer">
      <span class="count">COUNT: {{ totalCount }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { Printer, Refresh, Close, Search } from '@element-plus/icons-vue'
import axios from '@/utils/axios'

// Reactive data
const loading = ref(false)
const selectedSection = ref(null)
const selectedPatientType = ref('all')
const dateFrom = ref('')
const dateTo = ref('')
const worksheetData = ref([])
const testCategories = ref([])
const patientTypes = ref([])
const selectedRow = ref(null)

// Computed properties
const totalCount = computed(() => worksheetData.value.length)

const dynamicColumns = ref([])

// Load dynamic columns when section changes
const loadDynamicColumns = async () => {
  if (!selectedSection.value) {
    dynamicColumns.value = []
    return
  }

  try {
    const response = await axios.get('/management-report/column-metadata', {
      params: { category_id: selectedSection.value }
    })
    dynamicColumns.value = response.data.columns || []
  } catch (error) {
    console.error('Error loading dynamic columns:', error)
    // Fallback to empty array if API fails
    dynamicColumns.value = []
  }
}

// Methods
const loadTestCategories = async () => {
  try {
    const response = await axios.get('/test-categories')
    testCategories.value = response.data.data || response.data
  } catch (error) {
    console.error('Error loading test categories:', error)
    ElMessage.error('Failed to load test categories')
  }
}

const loadPatientTypes = async () => {
  try {
    const response = await axios.get('/patient-types')
    patientTypes.value = response.data.data || response.data
  } catch (error) {
    console.error('Error loading patient types:', error)
    ElMessage.error('Failed to load patient types')
  }
}

const loadWorksheetData = async () => {
  if (!selectedSection.value) {
    ElMessage.warning('Please select a section first')
    return
  }

  loading.value = true
  try {
    const params = {
      section_id: selectedSection.value,
      patient_type: selectedPatientType.value !== 'all' ? selectedPatientType.value : null,
      date_from: dateFrom.value,
      date_to: dateTo.value
    }

    const response = await axios.get('/management-report/worksheet', { params })
    worksheetData.value = response.data.data || response.data
  } catch (error) {
    console.error('Error loading worksheet data:', error)
    ElMessage.error('Failed to load worksheet data: ' + (error.response?.data?.message || error.message))
    worksheetData.value = []
  } finally {
    loading.value = false
  }
}

const onSectionChange = async () => {
  // Clear data when section changes
  worksheetData.value = []
  selectedRow.value = null
  
  // Load dynamic columns for the new section
  await loadDynamicColumns()
}

const applyFilters = () => {
  loadWorksheetData()
}

const onRowClick = (row) => {
  selectedRow.value = row
}

const getRowClassName = ({ row }) => {
  return selectedRow.value && selectedRow.value.id === row.id ? 'selected-row' : ''
}

const printReport = () => {
  if (worksheetData.value.length === 0) {
    ElMessage.warning('No data to print')
    return
  }
  
  // Implement print functionality
  window.print()
}

const refreshData = () => {
  if (selectedSection.value) {
    loadWorksheetData()
  }
}

const closeWorksheet = () => {
  // Navigate back or close the worksheet
  window.history.back()
}

// Initialize default date range (current month)
const initializeDateRange = () => {
  const now = new Date()
  const firstDay = new Date(now.getFullYear(), now.getMonth(), 1)
  const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59)
  
  // Use YYYY-MM-DD HH:mm:ss to match value-format and backend expectations
  const pad = (n) => String(n).padStart(2, '0')
  const toMysql = (d) => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
  dateFrom.value = toMysql(firstDay)
  dateTo.value = toMysql(lastDay)
}

// Lifecycle
onMounted(() => {
  initializeDateRange()
  loadTestCategories()
  loadPatientTypes()
})

// Watch for section changes to load data automatically
watch(selectedSection, async (newSection) => {
  if (newSection) {
    await loadDynamicColumns()
    loadWorksheetData()
  }
})
</script>

<style scoped>
.worksheet-container {
  display: grid;
}

.worksheet-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--color-bg-primary);
  padding: 15px 20px;
  border-radius: var(--border-radius-lg);
  margin-bottom: 20px;
  box-shadow: var(--shadow-sm);
}

.header-tabs {
  display: flex;
  gap: 20px;
}

.tab {
  padding: 8px 16px;
  background: var(--color-bg-tertiary);
  border-radius: var(--border-radius-sm);
  font-weight: var(--font-weight-medium);
}

.tab.active {
  background: var(--color-primary);
  color: var(--color-text-light);
}

.header-actions {
  display: flex;
  gap: 10px;
}

.filters-section {
  background: var(--color-bg-primary);
  padding: 10px;
  border-radius: var(--border-radius-lg);
  margin-bottom: 20px;
  box-shadow: var(--shadow-sm);
}

.filter-row {
  display: flex;
  align-items: center;
  gap: 20px;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 300px;
}

.filter-group label {
  font-weight: var(--font-weight-medium);
  min-width: 100px;
  color: var(--color-text-secondary);
}

.content-tabs {
  background: var(--color-bg-primary);
  padding: 10px 20px;
  border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;
  margin-bottom: 0;
  box-shadow: var(--shadow-sm);
}

.table-container {
  background: var(--color-bg-primary);
  border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.worksheet-footer {
  background: var(--color-bg-primary);
  padding: 10px 20px;
  border-radius: var(--border-radius-lg);
  margin-top: 20px;
  box-shadow: var(--shadow-sm);
}

.count {
  font-weight: var(--font-weight-medium);
  color: var(--color-text-tertiary);
}

:deep(.selected-row) {
  background-color: var(--color-primary-light) !important;
}

:deep(.el-table__row:hover) {
  background-color: var(--color-bg-secondary);
}

:deep(.el-table__row.selected-row:hover) {
  background-color: var(--color-primary-light) !important;
}
</style>
