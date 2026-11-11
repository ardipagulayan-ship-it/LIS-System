<template>
  <div class="result-management-dashboard">

    <!-- Key Metrics Cards -->
    <div class="dashboard-container" >
      <div class="metrics-row">
        <div class="metric-card">
          <div class="metric-content">
            <div class="metric-icon" style="color: var(--color-primary);">
              <el-icon><Document /></el-icon>
            </div>
            <div class="metric-info">
              <div class="metric-value">{{ totalRequests }}</div>
              <div class="metric-label">Total Requests</div>
              <div class="metric-subtitle">All Categories</div>
            </div>
          </div>
        </div>
        <div class="metric-card">
          <div class="metric-content">
            <div class="metric-icon" style="color: var(--color-success);">
              <el-icon class="icon-size-24"><CircleCheck /></el-icon>
            </div>
            <div class="metric-info">
              <div class="metric-value">{{ completedRequests }}</div>
              <div class="metric-label">Completed</div>
              <div class="metric-subtitle">{{ ((completedRequests / totalRequests) * 100 || 0).toFixed(1) }}%</div>
            </div>
          </div>
        </div>
        <div class="metric-card">
          <div class="metric-content">
            <div class="metric-icon" style="color: var(--color-warning);">
              <el-icon><Clock /></el-icon>
            </div>
            <div class="metric-info">
              <div class="metric-value">{{ pendingRequests }}</div>
              <div class="metric-label">Pending</div>
              <div class="metric-subtitle">{{ ((pendingRequests / totalRequests) * 100 || 0).toFixed(1) }}%</div>
            </div>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-content">
            <div class="metric-icon" style="color: var(--color-dark);">
              <el-icon><Warning /></el-icon>
            </div>
            <div class="metric-info">
              <div class="metric-value">{{ avgTat }}</div>
              <div class="metric-label">Avg TAT (hours)</div>
              <div class="metric-subtitle">All Categories</div>
            </div>
          </div>
        </div>

      </div>
    </div>
    <!-- Test Categories with Status Counts and Quick Actions -->
    <el-row :gutter="20" class="mt-4">
      <el-col :span="24">
        <el-card class="card">
          <template #header>
            <div class="card-header">
              <span>Test Categories</span>
              <el-button @click="refreshData">
                <el-icon><Refresh /></el-icon> Refresh
              </el-button>
            </div>
          </template>
          <div class="test-categories-grid">
            <div 
              v-for="category in testCategories" 
              :key="category.id" 
              :class="['category-card-wrapper', { 'qc-hold-active-wrapper': qcSummaryByCategory[category.id]?.on_hold_count > 0 }]"
              @click="navigateToCategory(category)"
            >
              <div :class="['category-card', { 'qc-hold-active': qcSummaryByCategory[category.id]?.on_hold_count > 0 }]">
                <div class="category-header">
                  <h3>{{ category.name }}</h3>
                </div>
                
                <div class="category-stats">
                  <div class="stat-item">
                    <span class="stat-value">{{ getCategoryStats(category.id).total }}</span>
                    <span class="stat-label">Total</span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-value status-pending">{{ getCategoryStats(category.id).pending }}</span>
                    <span class="stat-label">Pending</span>
                  </div>
                </div>
                <div class="category-stats">
                  <div class="stat-item">
                    <span class="stat-value status-processing">{{ getCategoryStats(category.id).processing }}</span>
                    <span class="stat-label">Processing</span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-value status-validated">{{ getCategoryStats(category.id).validated }}</span>
                    <span class="stat-label">Validated</span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-value status-printed">{{ getCategoryStats(category.id).released }}</span>
                    <span class="stat-label">Released</span>
                  </div>
                </div>

                <div class="category-footer">
                  <div class="footer-main">
                    <div class="view-results-container">
                      <span class="view-results-label">View Results</span>
                      <el-icon class="view-results-icon"><Right /></el-icon>
                    </div>
                    <span class="category-tat">
                      <el-icon><Clock /></el-icon>
                      Avg TAT: {{ getCategoryTat(category.id) }}h
                    </span>
                  </div>
                  <span v-if="qcSummaryByCategory[category.id]?.on_hold_count > 0" class="qc-alert-badge">
                    <el-icon><Warning /></el-icon>
                    QC Hold: {{ qcSummaryByCategory[category.id]?.on_hold_count }}/{{ qcSummaryByCategory[category.id]?.total_analyzers }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Turnaround Time Graph -->
    <el-row :gutter="20" class="mt-4">
      <el-col :span="24">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>Turnaround Time by Test Category</span>
            </div>
          </template>
          <div class="chart-container" ref="chartContainer">
            <el-empty v-if="tatData.length === 0" description="No data available" />
            <canvas v-else ref="chartCanvas" style="height: 300px;"></canvas>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Document, CircleCheck, Clock, Warning, Refresh, Right } from '@element-plus/icons-vue'
import api from '@/utils/axios'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref(false)
const totalRequests = ref(0)
const completedRequests = ref(0)
const pendingRequests = ref(0)
const avgTat = ref(0)

const testCategories = ref([])
const categoryStats = reactive({})
const tatData = ref([])
const qcSummaryByCategory = reactive({})

const chartContainer = ref(null)
const chartCanvas = ref(null)
let chartInstance = null

onMounted(() => {
  loadDashboardData()
  
  // Load Chart.js dynamically
  const script = document.createElement('script')
  script.src = 'https://cdn.jsdelivr.net/npm/chart.js'
  script.onload = () => {
    window.Chart = window.Chart || {}
  }
  document.head.appendChild(script)
})

const loadDashboardData = async () => {
  try {
    loading.value = true
    
    // Load test categories first
    const categoriesData = await api.get('/test-categories')
    
    // Handle different response structures for test categories
    if (categoriesData.data.data) {
      testCategories.value = categoriesData.data.data
    } else if (Array.isArray(categoriesData.data)) {
      testCategories.value = categoriesData.data
    } else {
      testCategories.value = []
    }
    
    // Try to load dashboard stats and TAT data (these might not exist yet)
    try {
      console.log('Attempting to load dashboard stats...')
      const [statsData, tatDataResponse] = await Promise.all([
        api.get('/results/dashboard/stats'),
        api.get('/results/dashboard/tat')
      ])
      
      console.log('Dashboard stats response:', statsData.data)
      console.log('TAT response:', tatDataResponse.data)
      console.log('Stats data type:', typeof statsData.data)
      console.log('Stats data keys:', Object.keys(statsData.data || {}))
      
      Object.assign(categoryStats, statsData.data || {})
      tatData.value = tatDataResponse.data || []
      
      console.log('Category stats after assignment:', categoryStats)
      
    } catch (dashboardError) {
      console.error('Dashboard API error:', dashboardError)
      console.error('Error response:', dashboardError.response)
      console.error('Error status:', dashboardError.response?.status)
      console.error('Error data:', dashboardError.response?.data)
      
      if (dashboardError.response?.status === 401) {
        ElMessage.error('Authentication required. Please log in again.')
        authStore.logout()
        router.push('/login')
        return
      }
      
      console.warn('Dashboard endpoints not available yet, using fallback data:', dashboardError)
      // Fallback: create empty stats for each category
      testCategories.value.forEach(category => {
        categoryStats[category.id] = {
          total: 0,
          pending: 0,
          processing: 0,
          validated: 0,
          released: 0
        }
      })
      
      // Fallback: create empty TAT data
      tatData.value = testCategories.value.map(category => ({
        category_id: category.id,
        category_name: category.name,
        avg_tat: 0
      }))
    }
    
    calculateOverallStats()

    // Fetch QC analyzer summaries for each category in parallel
    try {
      const summaryPromises = testCategories.value.map(c => 
        api.get('/qc-lockout/status-summary', { params: { test_category_id: c.id } })
          .then(r => ({ id: c.id, summary: r.data }))
          .catch(() => ({ id: c.id, summary: { total_analyzers: 0, on_hold_count: 0, analyzers: [] } }))
      )
      const summaries = await Promise.all(summaryPromises)
      summaries.forEach(s => { qcSummaryByCategory[s.id] = s.summary })
    } catch (e) {
      // non-fatal
      console.warn('QC summary load failed', e)
    }
    
    // Render chart after data is loaded
    await nextTick()
    renderChart()
    
  } catch (error) {
    console.error('Failed to load dashboard data', error)
    ElMessage.error('Failed to load dashboard data')
  } finally {
    loading.value = false
  }
}

const calculateOverallStats = () => {
  totalRequests.value = Object.values(categoryStats).reduce((sum, stats) => sum + (stats.total || 0), 0)
  completedRequests.value = Object.values(categoryStats).reduce((sum, stats) => sum + (stats.released || 0), 0)
  pendingRequests.value = Object.values(categoryStats).reduce((sum, stats) => sum + (stats.pending || 0), 0)
  
  if (tatData.value.length > 0) {
    avgTat.value = (tatData.value.reduce((sum, item) => sum + parseFloat(item.avg_tat || 0), 0) / tatData.value.length).toFixed(1)
  } else {
    avgTat.value = 0
  }
}

const getCategoryStats = (categoryId) => {
  return categoryStats[categoryId] || { total: 0, pending: 0, processing: 0, validated: 0, released: 0 }
}

const getCategoryTat = (categoryId) => {
  const category = tatData.value.find(item => item.category_id === categoryId)
  return category ? parseFloat(category.avg_tat || 0).toFixed(1) : '0.0'
}

const renderChart = () => {
  if (!chartCanvas.value || tatData.value.length === 0) return
  
  // Dispose existing chart instance if any
  if (chartInstance) {
    chartInstance.destroy()
  }
  
  const ctx = chartCanvas.value.getContext('2d')
  
  // Get colors from CSS variables
  const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim()
  const primaryLight = getComputedStyle(document.documentElement).getPropertyValue('--color-primary-light').trim()
  
  chartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: tatData.value.map(item => item.category_name),
      datasets: [{
        label: 'Avg TAT (hours)',
        data: tatData.value.map(item => parseFloat(item.avg_tat || 0)),
        backgroundColor: primaryColor,
        borderColor: primaryColor,
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        title: {
          display: true,
          text: 'Turnaround Time by Test Category'
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
        },
        x: {
          title: {
            display: true,
            text: 'Test Category'
          }
        }
      }
    }
  })
}

const navigateToCategory = (category) => {
  router.push({
    name: 'result-category',
    params: { categoryId: category.id, categoryName: category.name.toLowerCase().replace(/\s+/g, '-') }
  })
}

const refreshData = () => {
  loadDashboardData()
}
</script>

<style scoped>
.result-management-dashboard {
  width: 100%;
}
.dashboard-container{
  display: grid;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-title {
  font-size: 24px;
  font-weight: 600;
  margin: 0;
}

.metrics-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
}

.metric-card {
  background: linear-gradient(135deg, var(--color-bg-primary) 0%, var(--color-bg-secondary) 100%);
  border: 1px solid var(--color-border-secondary);
  border-radius: var(--border-radius-md);
  box-shadow: var(--shadow-sm);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
  padding: 20px;
}

.metric-content {
  display: flex;
  align-items: center;
}

.metric-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  transition: height 0.3s ease;
}

.metric-card:hover {
  transform: translateY(-8px);
  z-index: 1;
}

.metric-card:hover::before {
  height: 6px;
}

.metric-icon {
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3rem;
}

.metric-info {
  flex: 1;
}

.metric-value {
  font-size: 32px;
  font-weight: bold;
  color: var(--color-text-primary);
  line-height: 1;
  margin-bottom: 5px;
}

.metric-label {
  font-size: 13px;
  color: var(--color-text-tertiary);
  margin-bottom: 2px;
}

.metric-subtitle {
  font-size: 12px;
  color: var(--color-text-disabled);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.chart-container {
  width: 100%;
  height: 300px;
}

.test-categories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.category-card-wrapper {
  position: relative;
}

.category-card-wrapper::before {
  content: '';
  position: absolute;
  top: 10px;
  left: 10px;
  right: 10px;
  bottom: 10px;
  background: linear-gradient(45deg, var(--color-warning), var(--color-primary), var(--color-success), var(--color-warning));
  background-size: 400%;
  z-index: 0;
  filter: blur(5px);
  opacity: 0;
  border-radius: 4px;
}

.category-card-wrapper:hover::before {
  opacity: .7;
  top: -2px;
  left: -2px;
  right: -2px;
  bottom: -2px;
  animation: steam 20s linear infinite;
}

.category-card {
  position: relative;
  border: 1px solid var(--color-border-secondary);
  border-radius: 24px;
  padding: 24px;
  cursor: pointer;
  background-color: #ffffff !important;
  z-index: 1;
  height: 100%;

}

.category-card:hover {
  border-color: #e4e7ed;
}

@keyframes steam {
  0% { background-position: 0% 0%; }
  50% { background-position: 400% 0%; }
  100% { background-position: 0% 0%; }
}

.category-card.qc-hold-active {
  border: 2px solid var(--color-danger);
  background-color: #faf6f6 !important;
  position: relative;
}

.category-card-wrapper.qc-hold-active-wrapper::before {
  background: linear-gradient(45deg, #ff4d4f, #ff7875, #ff4d4f);
  opacity: 0.4;
  top: -2px;
  left: -2px;
  right: -2px;
  bottom: -2px;
}

.category-card.qc-hold-active:hover {
  border-color: var(--color-danger);
  box-shadow: var(--shadow-md);
}

.category-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.category-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--color-text-primary);
}

.category-stats {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 10px;
}

.stat-item {
  text-align: center;
  padding: 8px;
  background-color: var(--color-bg-secondary);
  border-radius: var(--border-radius-sm);
  width: 100%;
  border: 1px solid var(--color-border-secondary);
}

.stat-value {
  display: block;
  font-size: 20px;
  font-weight: bold;
  color: var(--color-text-primary);
  margin-bottom: 4px;
}

.stat-value.status-pending {
  color: var(--color-warning);
}

.stat-value.status-processing {
  color: var(--color-primary);
}

.stat-value.status-validated {
  color: var(--color-primary);
}

.stat-value.status-printed {
  color: var(--color-success);
}

.stat-label {
  display: block;
  font-size: 12px;
  color: var(--color-text-tertiary);
}

.category-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 10px;
  border-top: 1px solid var(--color-border-secondary);
  gap: 10px;
}

.footer-main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex: 1;
}

.view-results-container {
  display: flex;
  align-items: center;
  cursor: pointer;
  color: var(--color-danger);
}

.view-results-label {
  font-size: 13px;
  font-weight: 600;
  max-width: 0;
  opacity: 0;
  white-space: nowrap;
  overflow: hidden;
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  display: inline-block;
  transform: translateX(-10px);
}

.view-results-icon {
  font-size: 20px;
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.category-card:hover .view-results-label {
  max-width: 100px;
  opacity: 1;
  margin-right: 8px;
  transform: translateX(0);
}

.category-card:hover .view-results-icon {
  transform: translateX(0);
}

.category-tat {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  color: var(--color-text-secondary);
  font-weight: 500;
  transition: transform 0.3s ease;
}

.category-card:hover .category-tat {
  color: var(--color-text-primary);
}

.qc-hold-active .category-tat {
  color: var(--color-text-primary);
}

.qc-alert-badge {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: var(--color-danger);
  font-weight: 600;
  background-color: var(--color-danger-light);
  padding: 4px 8px;
  border-radius: 4px;
}

.qc-hold-active .category-header h3 {
  color: var(--color-text-primary);
}

.qc-hold-active .stat-value {
  color: var(--color-text-primary);
}

.qc-hold-active .stat-label {
  color: var(--color-text-secondary);
}

.mt-4 {
  margin-top: 20px;
}
</style>

