<template>
  <div class="tat-container">
    <!-- Header -->
    <div class="tat-header">
      <div class="header-left">
        <h2>Turn Around Time (TAT) Report</h2>
        <p>Performance metrics and 6 Sigma analysis for laboratory tests</p>
      </div>
      <div class="header-right">
        <el-button type="primary" :icon="Printer" @click="printReport">Print</el-button>
        <el-button type="success" :icon="TrendCharts" @click="toggleGraph">
          {{ showGraph ? 'Hide Graph' : 'Show Graph' }}
        </el-button>
        <el-button :icon="Refresh" @click="refreshData">Refresh</el-button>
      </div>
    </div>

    <!-- Filters -->
    <el-card class="filter-card">
      <div class="filter-row">
        <div class="filter-item">
          <label>Section:</label>
          <el-select
            v-model="filters.sectionId"
            placeholder="Select Section"
            clearable
            @change="onSectionChange"
          >
            <el-option
              v-for="category in testCategories"
              :key="category.id"
              :label="category.name"
              :value="category.id"
            />
          </el-select>
        </div>
        <div class="filter-item">
          <label>Patient Type:</label>
          <el-select
            v-model="filters.patientType"
            placeholder="All Types"
            clearable
            @change="onFilterChange"
          >
            <el-option label="All" value="all" />
            <el-option
              v-for="type in patientTypes"
              :key="type.id"
              :label="type.name"
              :value="type.id"
            />
          </el-select>
        </div>
        <div class="filter-item">
          <label>From:</label>
          <el-date-picker
            v-model="filters.dateFrom"
            type="datetime"
            placeholder="Select date and time"
            format="MM/DD/YYYY hh:mm:ss A"
            value-format="YYYY-MM-DD HH:mm:ss"
            @change="onFilterChange"
          />
        </div>
        <div class="filter-item">
          <label>To:</label>
          <el-date-picker
            v-model="filters.dateTo"
            type="datetime"
            placeholder="Select date and time"
            format="MM/DD/YYYY hh:mm:ss A"
            value-format="YYYY-MM-DD HH:mm:ss"
            @change="onFilterChange"
          />
        </div>
        <div class="filter-item">
          <label>Group By:</label>
          <el-select
            v-model="filters.groupBy"
            placeholder="Group By"
            @change="onFilterChange"
          >
            <el-option label="Test" value="test" />
            <el-option label="Day" value="day" />
            <el-option label="Week" value="week" />
            <el-option label="Month" value="month" />
          </el-select>
        </div>
        <div class="filter-item">
          <el-button type="primary" @click="loadTATData">Go</el-button>
        </div>
      </div>
    </el-card>

    <!-- Tabs -->
    <el-card class="content-card">
      <el-tabs v-model="activeTab" class="tat-tabs">
        <el-tab-pane label="TAT Statistics" name="statistics">
          <!-- TAT Statistics Table -->
          <div class="table-container">
            <el-table
              :data="tatData"
              border
              stripe
              :loading="loading"
              class="tat-table"
              :header-cell-style="{ background: 'var(--color-bg-secondary)', color: 'var(--color-text-secondary)' }"
            >
              <el-table-column prop="group_name" label="Group" fixed="left"/>
              <el-table-column prop="count" label="Count" align="center" />
              
              <!-- TAT Statistics Columns -->
              <el-table-column label="Mean (Hours)" align="center">
                <template #default="{ row }">
                  {{ row.tat_statistics?.mean_hours || 'N/A' }}
                </template>
              </el-table-column>
              <el-table-column label="Median (Hours)" align="center">
                <template #default="{ row }">
                  {{ row.tat_statistics?.median_minutes ? (row.tat_statistics.median_minutes / 60).toFixed(2) : 'N/A' }}
                </template>
              </el-table-column>
              <el-table-column label="Min (Hours)" align="center">
                <template #default="{ row }">
                  {{ row.tat_statistics?.min_minutes ? (row.tat_statistics.min_minutes / 60).toFixed(2) : 'N/A' }}
                </template>
              </el-table-column>
              <el-table-column label="Max (Hours)" align="center">
                <template #default="{ row }">
                  {{ row.tat_statistics?.max_minutes ? (row.tat_statistics.max_minutes / 60).toFixed(2) : 'N/A' }}
                </template>
              </el-table-column>
              <el-table-column label="Std Dev (Hours)" align="center">
                <template #default="{ row }">
                  {{ row.tat_statistics?.std_dev_minutes ? (row.tat_statistics.std_dev_minutes / 60).toFixed(2) : 'N/A' }}
                </template>
              </el-table-column>
              <el-table-column label="95th Percentile (Hours)" align="center">
                <template #default="{ row }">
                  {{ row.tat_statistics?.p95_minutes ? (row.tat_statistics.p95_minutes / 60).toFixed(2) : 'N/A' }}
                </template>
              </el-table-column>
            </el-table>
          </div>
        </el-tab-pane>
        
        <el-tab-pane label="6 Sigma Analysis" name="sixsigma">
          <!-- 6 Sigma Analysis Table -->
          <div class="table-container">
            <el-table
              :data="tatData"
              border
              stripe
              :loading="loading"
              class="sixsigma-table"
              :header-cell-style="{ background: 'var(--color-bg-secondary)', color: 'var(--color-text-secondary)' }"
            >
              <el-table-column prop="group_name" label="Group" fixed="left" />
              <el-table-column prop="count" label="Count" align="center" />
              
              <!-- 6 Sigma Columns -->
              <el-table-column label="Sigma Level" align="center">
                <template #default="{ row }">
                  <el-tag :type="getSigmaLevelType(row.six_sigma?.sigma_level)">
                    {{ row.six_sigma?.sigma_level || 'N/A' }}
                  </el-tag>
                </template>
              </el-table-column>
              <el-table-column label="Process Capability" align="center">
                <template #default="{ row }">
                  <el-tag :type="getCapabilityType(row.six_sigma?.process_capability)">
                    {{ row.six_sigma?.process_capability || 'N/A' }}
                  </el-tag>
                </template>
              </el-table-column>
              <el-table-column label="Yield %" align="center">
                <template #default="{ row }">
                  {{ row.six_sigma?.yield_percentage || 'N/A' }}%
                </template>
              </el-table-column>
              <el-table-column label="DPMO" align="center">
                <template #default="{ row }">
                  {{ row.six_sigma?.defects_per_million || 'N/A' }}
                </template>
              </el-table-column>
              <el-table-column label="Target TAT (Hours)" align="center">
                <template #default="{ row }">
                  {{ row.six_sigma?.target_tat_minutes ? (row.six_sigma.target_tat_minutes / 60).toFixed(2) : 'N/A' }}
                </template>
              </el-table-column>
              <el-table-column label="Mean TAT (Hours)" align="center">
                <template #default="{ row }">
                  {{ row.six_sigma?.mean_tat_minutes ? (row.six_sigma.mean_tat_minutes / 60).toFixed(2) : 'N/A' }}
                </template>
              </el-table-column>
              <el-table-column label="Std Dev (Hours)" align="center">
                <template #default="{ row }">
                  {{ row.six_sigma?.standard_deviation ? (row.six_sigma.standard_deviation / 60).toFixed(2) : 'N/A' }}
                </template>
              </el-table-column>
              <el-table-column label="Defects" align="center">
                <template #default="{ row }">
                  {{ row.six_sigma?.defect_count || 'N/A' }}
                </template>
              </el-table-column>
            </el-table>
          </div>
        </el-tab-pane>

        <el-tab-pane label="Recommendations" name="recommendations">
          <!-- Recommendations Table -->
          <div class="table-container">
            <el-table
              :data="tatData"
              border
              stripe
              :loading="loading"
              class="recommendations-table"
              :header-cell-style="{ background: 'var(--color-bg-secondary)', color: 'var(--color-text-secondary)' }"
            >
              <el-table-column prop="group_name" label="Group" fixed="left" />
              <el-table-column prop="count" label="Count" align="center" />
              <el-table-column label="Recommendations" min-width="400">
                <template #default="{ row }">
                  <div v-if="row.six_sigma?.recommendations?.length">
                    <el-tag
                      v-for="(recommendation, index) in row.six_sigma.recommendations"
                      :key="index"
                      type="info"
                      size="small"
                      class="recommendation-tag"
                    >
                      {{ recommendation }}
                    </el-tag>
                  </div>
                  <span v-else>No recommendations available</span>
                </template>
              </el-table-column>
            </el-table>
          </div>
        </el-tab-pane>

        <el-tab-pane label="Detailed View" name="detailed">
          <!-- Detailed TAT Data -->
          <div class="table-container">
            <el-table
              :data="detailedTATData"
              border
              stripe
              :loading="loading"
              class="detailed-table"
              :header-cell-style="{ background: 'var(--color-bg-secondary)', color: 'var(--color-text-secondary)' }"
            >
              <el-table-column prop="test_name" label="Test Name" fixed="left" />
              <el-table-column prop="patient_name" label="Patient" />
              <el-table-column prop="patient_type" label="Patient Type" />
              <el-table-column prop="ward" label="Ward" />
              <el-table-column prop="request_date" label="Request Date" />
              <el-table-column prop="collected_at" label="Collected At" />
              <el-table-column prop="verify_at" label="Verified At" />
              <el-table-column label="Target TAT (Hours)" align="center">
                <template #default="{ row }">
                  {{ row.target_tat_hours !== null && row.target_tat_hours !== undefined ? row.target_tat_hours.toFixed(2) : 'N/A' }}
                </template>
              </el-table-column>
              <el-table-column label="Actual TAT (Hours)" width="140" align="center">
                <template #default="{ row }">
                  {{ row.total_tat_hours !== null && row.total_tat_hours !== undefined ? row.total_tat_hours.toFixed(2) : 'N/A' }}
                </template>
              </el-table-column>
              <el-table-column label="Performance" width="120" align="center">
                <template #default="{ row }">
                  <el-tag :type="getPerformanceType(row.tat_performance?.status)">
                    {{ row.tat_performance?.percentage ? row.tat_performance.percentage + '%' : 'N/A' }}
                  </el-tag>
                </template>
              </el-table-column>
              <el-table-column label="Variance (Hours)" width="140" align="center">
                <template #default="{ row }">
                  {{ row.tat_performance?.variance_minutes !== null && row.tat_performance?.variance_minutes !== undefined ? (row.tat_performance.variance_minutes / 60).toFixed(2) : 'N/A' }}
                </template>
              </el-table-column>
              <el-table-column label="Collection TAT (Hours)" width="160" align="center">
                <template #default="{ row }">
                  {{ row.collection_tat_minutes !== null && row.collection_tat_minutes !== undefined ? (row.collection_tat_minutes / 60).toFixed(2) : 'N/A' }}
                </template>
              </el-table-column>
              <el-table-column label="Processing TAT (Hours)" width="160" align="center">
                <template #default="{ row }">
                  {{ row.processing_tat_minutes !== null && row.processing_tat_minutes !== undefined ? (row.processing_tat_minutes / 60).toFixed(2) : 'N/A' }}
                </template>
              </el-table-column>
              <el-table-column label="Verification TAT (Hours)" width="160" align="center">
                <template #default="{ row }">
                  {{ row.verification_tat_minutes !== null && row.verification_tat_minutes !== undefined ? (row.verification_tat_minutes / 60).toFixed(2) : 'N/A' }}
                </template>
              </el-table-column>
            </el-table>
          </div>
        </el-tab-pane>
        
        <el-tab-pane label="Graph Representation" name="graph">
          <div v-if="showGraph" class="graph-container">
            <div class="chart-controls">
              <el-select v-model="chartType" placeholder="Select Chart Type" @change="updateChart">
                <el-option label="TAT Trend (Line)" value="line" />
                <el-option label="TAT Distribution (Bar)" value="bar" />
                <el-option label="6 Sigma Levels (Doughnut)" value="doughnut" />
              </el-select>
            </div>
            <div class="chart-wrapper">
              <canvas ref="chartCanvas" width="800" height="400"></canvas>
            </div>
          </div>
          <div v-else class="graph-placeholder">
            <el-empty description="Click 'Show Graph' to view chart representation">
              <el-button type="primary" @click="toggleGraph">Show Graph</el-button>
            </el-empty>
          </div>
        </el-tab-pane>
      </el-tabs>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, nextTick } from 'vue'
import { ElMessage } from 'element-plus'
import { Printer, TrendCharts, Refresh } from '@element-plus/icons-vue'
import api from '@/utils/axios'

// Reactive data
const loading = ref(false)
const tatData = ref([])
const detailedTATData = ref([])
const testCategories = ref([])
const patientTypes = ref([])
const activeTab = ref('statistics')
const showGraph = ref(false)
const chartType = ref('line')
const chartCanvas = ref(null)
let chartInstance = null

// Filters
const filters = ref({
  sectionId: null,
  patientType: 'all',
  dateFrom: '',
  dateTo: '',
  groupBy: 'test'
})

// Methods
const loadTATData = async () => {
  if (!filters.value.sectionId) {
    ElMessage.warning('Please select a section first')
    return
  }

  try {
    loading.value = true
    
    const params = {
      section_id: filters.value.sectionId,
      patient_type: filters.value.patientType !== 'all' ? filters.value.patientType : null,
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo,
      group_by: filters.value.groupBy
    }
    
    const response = await api.get('/management-report/tat', { params })
    
    tatData.value = response.data.data || []
    
    // Data loaded successfully
    
    // Load detailed data separately for better performance
    await loadDetailedTATData()
    
    // Update chart if graph is shown
    if (showGraph.value) {
      await updateChart()
    }
    
    ElMessage.success('TAT data loaded successfully')
    
  } catch (error) {
    console.error('Error loading TAT data:', error)
    ElMessage.error('Failed to load TAT data')
  } finally {
    loading.value = false
  }
}

const loadDetailedTATData = async () => {
  if (!filters.value.sectionId) {
    return
  }

  try {
    const params = {
      section_id: filters.value.sectionId,
      patient_type: filters.value.patientType !== 'all' ? filters.value.patientType : null,
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    
    const response = await api.get('/management-report/tat/detailed', { params })
    detailedTATData.value = response.data.data || []
    
  } catch (error) {
    console.error('Error loading detailed TAT data:', error)
    detailedTATData.value = []
  }
}

const loadTestCategories = async () => {
  try {
    const response = await api.get('/management-report/test-categories')
    testCategories.value = response.data.data || []
  } catch (error) {
    console.error('Error loading test categories:', error)
  }
}

const loadPatientTypes = async () => {
  try {
    const response = await api.get('/patient-types')
    patientTypes.value = response.data.data || response.data
  } catch (error) {
    console.error('Error loading patient types:', error)
  }
}

const onSectionChange = () => {
  loadTATData()
}

const onFilterChange = () => {
  if (filters.value.sectionId) {
    loadTATData()
  }
}

const refreshData = () => {
  if (filters.value.sectionId) {
    loadTATData()
  }
}

const printReport = () => {
  window.print()
}

const toggleGraph = () => {
  showGraph.value = !showGraph.value
  if (showGraph.value && tatData.value.length > 0) {
    nextTick(() => {
      updateChart()
    })
  }
}

const getSigmaLevelType = (sigmaLevel) => {
  if (!sigmaLevel || sigmaLevel === 0) return 'info'
  if (sigmaLevel >= 6.0) return 'success'    // 6 Sigma - World Class
  if (sigmaLevel >= 5.0) return 'success'    // 5 Sigma - Excellent
  if (sigmaLevel >= 4.0) return 'warning'    // 4 Sigma - Good
  if (sigmaLevel >= 3.0) return 'warning'    // 3 Sigma - Average
  if (sigmaLevel >= 2.0) return 'danger'     // 2 Sigma - Below Average
  if (sigmaLevel >= 1.0) return 'danger'     // 1 Sigma - Poor
  return 'danger'                             // Below 1 Sigma - Critical
}

const getCapabilityType = (capability) => {
  if (!capability) return 'info'
  if (capability === 'World Class') return 'success'     // 99.9%+ yield
  if (capability === 'Excellent') return 'success'       // 99%+ yield
  if (capability === 'Good') return 'warning'            // 95%+ yield
  if (capability === 'Average') return 'warning'         // 90%+ yield
  if (capability === 'Below Average') return 'danger'    // 80%+ yield
  if (capability === 'Poor') return 'danger'             // <80% yield
  return 'info'
}

const getPerformanceType = (status) => {
  if (!status) return 'info'
  switch (status) {
    case 'excellent':        // <80% of target (very fast)
      return 'success'
    case 'good':             // 90-100% of target
      return 'success'
    case 'acceptable':        // 80-90% of target
      return 'warning'
    case 'slightly_over':     // >100% of target
      return 'warning'
    case 'below_target':      // >120% of target
      return 'warning'
    case 'poor':              // >150% of target
      return 'danger'
    case 'critical':          // >200% of target
      return 'danger'
    case 'unknown':           // No data available
      return 'info'
    default:
      return 'info'
  }
}

// Chart methods
const getDesignSystemColor = (variableName) => {
  return getComputedStyle(document.documentElement).getPropertyValue(variableName).trim()
}

const updateChart = async () => {
  if (!showGraph.value || !chartCanvas.value) return
  
  await nextTick()
  
  // Destroy existing chart
  if (chartInstance) {
    chartInstance.destroy()
  }
  
  // Create new chart based on type
  switch (chartType.value) {
    case 'line':
      createLineChart()
      break
    case 'bar':
      createBarChart()
      break
    case 'doughnut':
      createDoughnutChart()
      break
  }
}

const createLineChart = () => {
  if (!tatData.value.length) return
  
  const labels = tatData.value.map(item => item.group_name)
  const data = tatData.value.map(item => item.tat_statistics?.mean_hours || 0)
  
  const primaryColor = getDesignSystemColor('--color-primary')
  
  chartInstance = new Chart(chartCanvas.value, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'Mean TAT (Hours)',
        data: data,
        borderColor: primaryColor,
        backgroundColor: primaryColor + '33', // 0.2 opacity in hex
        tension: 0.1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        title: {
          display: true,
          text: 'Turn Around Time Trend'
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Hours'
          }
        }
      }
    }
  })
}

const createBarChart = () => {
  if (!tatData.value.length) return
  
  const labels = tatData.value.map(item => item.group_name)
  const data = tatData.value.map(item => item.tat_statistics?.mean_hours || 0)
  
  const successColor = getDesignSystemColor('--color-success')
  const primaryColor = getDesignSystemColor('--color-primary')
  const warningColor = getDesignSystemColor('--color-warning')
  const dangerColor = getDesignSystemColor('--color-danger')
  
  // Dynamic color generation based on performance
  const generateColors = (data) => {
    const colors = []
    data.forEach((value, index) => {
      if (value <= 1.0) {
        colors.push(successColor + 'CC')    // 0.8 opacity
      } else if (value <= 2.0) {
        colors.push(primaryColor + 'CC')    // 0.8 opacity
      } else if (value <= 4.0) {
        colors.push(warningColor + 'CC')    // 0.8 opacity
      } else {
        colors.push(dangerColor + 'CC')    // 0.8 opacity
      }
    })
    return colors
  }
  
  const backgroundColor = generateColors(data)
  const borderColor = backgroundColor.map(color => color.substring(0, 7)) // Remove opacity for border
  
  chartInstance = new Chart(chartCanvas.value, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Mean TAT (Hours)',
        data: data,
        backgroundColor: backgroundColor,
        borderColor: borderColor,
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        title: {
          display: true,
          text: 'Turn Around Time Distribution by Performance Level'
        },
        legend: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Hours'
          }
        }
      }
    }
  })
}

const createDoughnutChart = () => {
  if (!tatData.value.length) return
  
  const capabilityCounts = {}
  tatData.value.forEach(item => {
    const capability = item.six_sigma?.process_capability || 'Unknown'
    capabilityCounts[capability] = (capabilityCounts[capability] || 0) + 1
  })
  
  const labels = Object.keys(capabilityCounts)
  const data = Object.values(capabilityCounts)
  
  const successColor = getDesignSystemColor('--color-success')
  const primaryColor = getDesignSystemColor('--color-primary')
  const warningColor = getDesignSystemColor('--color-warning')
  const dangerColor = getDesignSystemColor('--color-danger')
  const infoColor = getDesignSystemColor('--color-info')
  const textSecondary = getDesignSystemColor('--color-text-secondary')
  
  // Dynamic color mapping based on capability level
  const getCapabilityColor = (capability) => {
    switch (capability) {
      case 'World Class': return successColor
      case 'Excellent': return primaryColor
      case 'Good': return infoColor
      case 'Average': return warningColor
      case 'Below Average': return '#f97316' // Orange - hardcoded as fallback but better if from design system
      case 'Poor': return dangerColor
      default: return textSecondary
    }
  }
  
  const backgroundColor = labels.map(capability => getCapabilityColor(capability))
  
  chartInstance = new Chart(chartCanvas.value, {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{
        data: data,
        backgroundColor: backgroundColor,
        borderWidth: 2,
        borderColor: '#ffffff'
      }]
    },
    options: {
      responsive: true,
      plugins: {
        title: {
          display: true,
          text: '6 Sigma Process Capability Distribution'
        },
        legend: {
          position: 'bottom',
          labels: {
            usePointStyle: true,
            padding: 20
          }
        }
      }
    }
  })
}

// Initialize default date range (current month)
const initializeDateRange = () => {
  const now = new Date()
  const firstDay = new Date(now.getFullYear(), now.getMonth(), 1)
  const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59)
  
  const pad = (n) => String(n).padStart(2, '0')
  const toMysql = (d) => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
  filters.value.dateFrom = toMysql(firstDay)
  filters.value.dateTo = toMysql(lastDay)
}

// Lifecycle
onMounted(async () => {
  initializeDateRange()
  loadTestCategories()
  loadPatientTypes()
  
  // Load Chart.js dynamically
  const script = document.createElement('script')
  script.src = 'https://cdn.jsdelivr.net/npm/chart.js'
  script.onload = () => {
    window.Chart = window.Chart || {}
  }
  document.head.appendChild(script)
})
</script>

<style scoped>
.tat-container {
  display: grid;
}

.tat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  background: var(--color-bg-primary);
  padding: 20px;
  border-radius: var(--border-radius-xl);
  box-shadow: var(--shadow-md);
}

.header-left h2 {
  margin: 0 0 8px 0;
  color: var(--color-text-primary);
  font-size: 24px;
}

.header-left p {
  margin: 0;
  color: var(--color-text-secondary);
  font-size: 13px;
}

.header-right {
  display: flex;
  gap: 10px;
}

.filter-card {
  margin-bottom: 20px;
}

.filter-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.filter-item {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 300px;
}

.filter-item label {
  font-weight: 500;
  color: var(--color-text-secondary);
  white-space: nowrap;
}

.content-card {
  height: 100%;
  min-height: 500px;
}

.tat-tabs {
  margin-top: 10px;
}

.table-container {
  overflow-x: auto;
}

.tat-table,
.sixsigma-table,
.recommendations-table,
.detailed-table {
  width: auto !important;
  min-width: 1500px !important;
}

.recommendation-tag {
  margin: 2px;
  display: block;
}

.graph-container {
  min-height: 400px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.chart-controls {
  margin-bottom: 20px;
  width: 100%;
  display: flex;
  justify-content: center;
}

.chart-wrapper {
  width: 100%;
  max-width: 800px;
  height: 400px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.graph-placeholder {
  min-height: 400px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Print styles */
@media print {
  .tat-container {
    background: white;
    padding: 0;
  }
  
  .header-right,
  .filter-card,
  .tat-tabs .el-tabs__header {
    display: none;
  }
  
  .tat-table,
  .sixsigma-table,
  .recommendations-table,
  .detailed-table {
    font-size: 12px;
  }
  
  .tat-table .el-table__header th,
  .sixsigma-table .el-table__header th,
  .recommendations-table .el-table__header th,
  .detailed-table .el-table__header th {
    background: var(--color-bg-secondary) !important;
    color: var(--color-text-secondary) !important;
  }
}

/* Responsive design */
@media (max-width: 768px) {
  .tat-header {
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
  }
  
  .filter-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }
  
  .filter-item {
    width: 100%;
  }
  
  .filter-item label {
    min-width: 80px;
  }
}
</style>
