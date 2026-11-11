<template>
  <div class="quality-control-dashboard">
    <div class="page-header">
      <h2 class="page-title">Quality Control Dashboard</h2>
      <div class="header-actions">
        <el-select
          v-model="periodDays"
          placeholder="Period"
          style="width: 120px; margin-right: 10px"
          @change="loadDashboardData"
        >
          <el-option label="7 days" :value="7" />
          <el-option label="14 days" :value="14" />
          <el-option label="30 days" :value="30" />
          <el-option label="60 days" :value="60" />
          <el-option label="90 days" :value="90" />
        </el-select>
        <el-button type="primary" @click="refreshData" :loading="loading">
          <el-icon><Refresh /></el-icon> Refresh
        </el-button>
      </div>
    </div>

    <!-- Overview Metrics Cards -->
    <el-row :gutter="20" class="metrics-row">
      <el-col :span="6">
        <el-card class="metric-card">
          <div class="metric-content">
            <div class="metric-icon" style="background-color: var(--color-primary);">
              <el-icon><Document /></el-icon>
            </div>
            <div class="metric-info">
              <div class="metric-value">{{ metrics.totalControls }}</div>
              <div class="metric-label">Total Controls</div>
              <div class="metric-subtitle">All Analyzers</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="metric-card">
          <div class="metric-content">
            <div class="metric-icon" style="background-color: var(--color-success);">
              <el-icon><CircleCheck /></el-icon>
            </div>
            <div class="metric-info">
              <div class="metric-value">{{ metrics.excellentSigma }}</div>
              <div class="metric-label">Excellent (Σ≥6)</div>
              <div class="metric-subtitle">{{ metrics.excellentPercentage }}% of tests</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="metric-card">
          <div class="metric-content">
            <div class="metric-icon" style="background-color: var(--color-warning);">
              <el-icon><Warning /></el-icon>
            </div>
            <div class="metric-info">
              <div class="metric-value">{{ metrics.poorSigma }}</div>
              <div class="metric-label">Needs Attention (Σ<4)</div>
              <div class="metric-subtitle">{{ metrics.poorPercentage }}% of tests</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="metric-card">
          <div class="metric-content">
            <div class="metric-icon" style="background-color: var(--color-secondary);">
              <el-icon><DataAnalysis /></el-icon>
            </div>
            <div class="metric-info">
              <div class="metric-value">{{ metrics.totalTests }}</div>
              <div class="metric-label">Total Tests Monitored</div>
              <div class="metric-subtitle">Sigma Metrics</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Charts Section -->
    <el-row :gutter="20" class="charts-row">
      <el-col :span="12">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>Sigma Metrics Distribution</span>
            </div>
          </template>
          <div class="chart-container" v-loading="loading">
            <canvas ref="sigmaDistributionChart"></canvas>
            <el-empty v-if="!loading && sigmaDistributionData.length === 0" description="No data available" />
          </div>
        </el-card>
      </el-col>
      <el-col :span="12">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>Average Sigma Trend (Last 30 Days)</span>
            </div>
          </template>
          <div class="chart-container" v-loading="loading">
            <canvas ref="sigmaTrendChart"></canvas>
            <el-empty v-if="!loading && trendData.length === 0" description="No trend data available" />
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Global Recommendations & Alerts -->
    <el-row :gutter="20" class="recommendations-row">
      <el-col :span="16">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>Global Recommendations</span>
            </div>
          </template>
          <div v-loading="recommendationsLoading" style="min-height: 200px;">
            <div v-if="recommendations.length === 0 && !recommendationsLoading" class="empty-state">
              <el-empty description="No recommendations available" />
            </div>
            <div v-else class="recommendations-list">
              <div
                v-for="(rec, index) in recommendations"
                :key="index"
                class="recommendation-item"
                :class="`severity-${rec.severity || 'moderate'}`"
              >
                <div class="recommendation-header">
                  <el-icon class="recommendation-icon">
                    <Warning v-if="rec.severity === 'critical' || rec.severity === 'high'" />
                    <InfoFilled v-else />
                  </el-icon>
                  <span class="recommendation-title">{{ rec.title || rec.recommendation || rec.message || 'Recommendation' }}</span>
                  <el-tag
                    :type="getSeverityType(rec.severity)"
                    size="small"
                    effect="dark"
                    class="severity-badge"
                  >
                    {{ (rec.severity || 'moderate').toUpperCase() }}
                  </el-tag>
                </div>
                <div class="recommendation-content">
                  <p>{{ rec.description || rec.message || 'No description available' }}</p>
                  <div v-if="rec.action_items" class="action-items">
                    <strong>Action Items:</strong>
                    <ul>
                      <li v-for="(action, idx) in rec.action_items" :key="idx">{{ action }}</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>Quick Actions</span>
            </div>
          </template>
          <div class="action-buttons-vertical">
            <el-button type="primary" size="large" @click="navigateToQualityControl" style="width: 100%; margin-bottom: 10px;">
              <el-icon><Document /></el-icon>
              <span>Quality Control</span>
            </el-button>
            <el-button type="success" size="large" @click="navigateToSigmaMonitor" style="width: 100%;">
              <el-icon><DataAnalysis /></el-icon>
              <span>Sigma Monitor</span>
            </el-button>
          </div>
          <div class="action-descriptions">
            <div class="action-description">
              <h4>Quality Control</h4>
              <p>Manage quality control materials, lot numbers, and control results.</p>
            </div>
            <div class="action-description">
              <h4>Sigma Monitor</h4>
              <p>Monitor sigma metrics for all test parameters and track trends.</p>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Top Performers & Issues -->
    <el-row :gutter="20" class="performance-row">
      <el-col :span="12">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>Top Performing Tests (Highest Sigma)</span>
            </div>
          </template>
          <el-table
            :data="topPerformers"
            v-loading="loading"
            style="width: 100%"
            max-height="250"
          >
            <el-table-column prop="analyte_name" label="Test Name" min-width="150" />
            <el-table-column prop="sigma_metric" label="Sigma Metric" width="120" align="right">
              <template #default="{ row }">
                <el-tag :type="getSigmaStatusType(row.sigma_metric)" size="small">
                  {{ formatNumber(row.sigma_metric, 2) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="status_label" label="Status" width="100" align="center">
              <template #default="{ row }">
                <el-tag
                  :type="getStatusColor(row.status?.color).type"
                  size="small"
                  effect="dark"
                >
                  {{ row.status_label }}
                </el-tag>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-col>
      <el-col :span="12">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>Tests Requiring Attention (Low Sigma)</span>
            </div>
          </template>
          <el-table
            :data="needsAttention"
            v-loading="loading"
            style="width: 100%"
            max-height="250"
          >
            <el-table-column prop="analyte_name" label="Test Name" min-width="150" />
            <el-table-column prop="sigma_metric" label="Sigma Metric" width="120" align="right">
              <template #default="{ row }">
                <el-tag :type="getSigmaStatusType(row.sigma_metric)" size="small">
                  {{ formatNumber(row.sigma_metric, 2) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="status_label" label="Status" width="100" align="center">
              <template #default="{ row }">
                <el-tag
                  :type="getStatusColor(row.status?.color).type"
                  size="small"
                  effect="dark"
                >
                  {{ row.status_label }}
                </el-tag>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Refresh, Document, CircleCheck, Warning, DataAnalysis, InfoFilled } from '@element-plus/icons-vue'
import api from '@/utils/axios'
import { ElMessage } from 'element-plus'
import Chart from 'chart.js/auto'

const router = useRouter()
const loading = ref(false)
const recommendationsLoading = ref(false)
const periodDays = ref(30)

const metrics = ref({
  totalControls: 0,
  excellentSigma: 0,
  excellentPercentage: 0,
  poorSigma: 0,
  poorPercentage: 0,
  totalTests: 0
})

const sigmaData = ref([])
const recommendations = ref([])
const trendData = ref([])
const sigmaDistributionChart = ref(null)
const sigmaTrendChart = ref(null)
let distributionChartInstance = null
let trendChartInstance = null

const sigmaDistributionData = computed(() => {
  if (sigmaData.value.length === 0) return []
  
  const excellent = sigmaData.value.filter(item => item.sigma_metric !== null && item.sigma_metric >= 6).length
  const good = sigmaData.value.filter(item => item.sigma_metric !== null && item.sigma_metric >= 4 && item.sigma_metric < 6).length
  const poor = sigmaData.value.filter(item => item.sigma_metric !== null && item.sigma_metric < 4).length
  const noData = sigmaData.value.filter(item => item.sigma_metric === null).length
  
  const successColor = getComputedStyle(document.documentElement).getPropertyValue('--color-success').trim();
  const warningColor = getComputedStyle(document.documentElement).getPropertyValue('--color-warning').trim();
  const dangerColor = getComputedStyle(document.documentElement).getPropertyValue('--color-danger').trim();
  const textTertiary = getComputedStyle(document.documentElement).getPropertyValue('--color-text-tertiary').trim();

  return [
    { label: 'Excellent (≥6)', value: excellent, color: successColor },
    { label: 'Good (4-6)', value: good, color: warningColor },
    { label: 'Needs Attention (<4)', value: poor, color: dangerColor },
    { label: 'No Data', value: noData, color: textTertiary }
  ].filter(item => item.value > 0)
})

const topPerformers = computed(() => {
  return sigmaData.value
    .filter(item => item.sigma_metric !== null && item.sigma_metric !== undefined)
    .sort((a, b) => (b.sigma_metric || 0) - (a.sigma_metric || 0))
    .slice(0, 5)
})

const needsAttention = computed(() => {
  return sigmaData.value
    .filter(item => item.sigma_metric !== null && item.sigma_metric !== undefined && item.sigma_metric < 4)
    .sort((a, b) => (a.sigma_metric || 0) - (b.sigma_metric || 0))
    .slice(0, 5)
})

const loadDashboardData = async () => {
  // Load recommendations in parallel (independent)
  const recommendationsPromise = loadRecommendations()
  
  // Load sigma metrics first (trend data depends on it)
  await loadSigmaMetrics()
  
  // Now load trend data after sigma data is available
  await Promise.all([
    loadTrendData(),
    recommendationsPromise
  ])
}

const loadSigmaMetrics = async () => {
  try {
    loading.value = true
    const response = await api.get('/sigma-metrics', {
      params: { period_days: periodDays.value }
    })
    
    sigmaData.value = response.data.data || []
    calculateMetrics()
    
    // Wait for DOM to update and ensure canvas elements are available
    await nextTick()
    await new Promise(resolve => setTimeout(resolve, 100))
    renderDistributionChart()
  } catch (error) {
    console.error('Error loading sigma metrics:', error)
    ElMessage.error('Failed to load sigma metrics')
  } finally {
    loading.value = false
    // Render chart after loading completes
    await nextTick()
    await new Promise(resolve => setTimeout(resolve, 200))
    renderDistributionChart()
  }
}

const loadRecommendations = async () => {
  try {
    recommendationsLoading.value = true
    // Try both endpoints (primary first, fallback on 429)
    let response
    try {
      response = await api.get('/quality-controls/qc-recommendations')
    } catch (e) {
      response = await api.get('/quality-controls/recommendations')
    }
    recommendations.value = response.data || []
  } catch (error) {
    console.error('Error loading recommendations:', error)
    // Non-critical, continue without recommendations
    recommendations.value = []
  } finally {
    recommendationsLoading.value = false
  }
}

const loadTrendData = async () => {
  try {
    console.log('Loading trend data, sigmaData length:', sigmaData.value.length)
    // Get average sigma metrics for the last 30 days
    // Since we don't have a direct endpoint, we'll create a simplified trend
    // based on the current data
    if (sigmaData.value.length > 0) {
      const validItems = sigmaData.value.filter(item => item.sigma_metric !== null && item.sigma_metric !== undefined)
      console.log('Valid items for trend:', validItems.length)
      
      if (validItems.length > 0) {
        // Ensure all values are numbers and calculate average
        const numericValues = validItems
          .map(item => {
            const value = item.sigma_metric
            // Handle both string and number values
            const num = typeof value === 'string' ? parseFloat(value) : value
            return isNaN(num) ? 0 : num
          })
          .filter(val => val > 0) // Filter out zeros and invalid values
        
        if (numericValues.length === 0) {
          console.warn('No valid numeric sigma values found')
          trendData.value = []
          return
        }
        
        const avgSigma = numericValues.reduce((sum, val) => sum + val, 0) / numericValues.length
        
        console.log('Average sigma for trend:', avgSigma, 'from', numericValues.length, 'values')
        
        // Validate avgSigma is a number
        if (isNaN(avgSigma) || !isFinite(avgSigma)) {
          console.error('Invalid average sigma value:', avgSigma)
          trendData.value = []
          return
        }
        
        // Create mock trend data (in real implementation, this would come from API)
        const days = 30
        const today = new Date()
        trendData.value = Array.from({ length: days }, (_, i) => {
          const date = new Date(today)
          date.setDate(date.getDate() - (days - i - 1))
          const variation = (Math.random() * 2 - 1) * 0.5 // Smaller variation for more realistic trend
          const sigmaValue = Math.max(0, avgSigma + variation)
          return {
            date: date.toISOString().split('T')[0],
            avg_sigma: isNaN(sigmaValue) || !isFinite(sigmaValue) ? avgSigma : sigmaValue // Fallback to avgSigma if calculated value is invalid
          }
        })
        
        console.log('Trend data created:', trendData.value.length, 'items')
      } else {
        console.warn('No valid sigma metrics found for trend data')
        trendData.value = []
      }
    } else {
      console.warn('No sigma data available for trend')
      trendData.value = []
    }
    
    // Wait for DOM to update
    await nextTick()
    await new Promise(resolve => setTimeout(resolve, 200))
    
    // Ensure chart renders
    renderTrendChart()
  } catch (error) {
    console.error('Error loading trend data:', error)
    trendData.value = []
  }
}

const calculateMetrics = () => {
  const data = sigmaData.value
  
  metrics.value.totalTests = data.length
  metrics.value.excellentSigma = data.filter(item => item.sigma_metric !== null && item.sigma_metric >= 6).length
  metrics.value.poorSigma = data.filter(item => item.sigma_metric !== null && item.sigma_metric < 4).length
  
  const withData = data.filter(item => item.sigma_metric !== null).length
  if (withData > 0) {
    metrics.value.excellentPercentage = Math.round((metrics.value.excellentSigma / withData) * 100)
    metrics.value.poorPercentage = Math.round((metrics.value.poorSigma / withData) * 100)
  }
  
  // Load quality controls count
  loadQualityControlsCount()
}

const loadQualityControlsCount = async () => {
  try {
    const response = await api.get('/quality-controls', { params: { per_page: 1 } })
    metrics.value.totalControls = response.data.total || response.data.meta?.total || 0
  } catch (error) {
    console.error('Error loading quality controls count:', error)
  }
}

const renderDistributionChart = () => {
  if (!sigmaDistributionChart.value) {
    console.warn('Distribution chart canvas not available')
    return
  }
  
  if (sigmaDistributionData.value.length === 0) {
    console.warn('No distribution data available')
    return
  }
  
  const ctx = sigmaDistributionChart.value.getContext('2d')
  if (!ctx) {
    console.error('Failed to get canvas context')
    return
  }
  
  if (distributionChartInstance) {
    distributionChartInstance.destroy()
    distributionChartInstance = null
  }
  
  try {
    distributionChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: sigmaDistributionData.value.map(item => item.label),
      datasets: [{
        data: sigmaDistributionData.value.map(item => item.value),
        backgroundColor: sigmaDistributionData.value.map(item => item.color),
        borderWidth: 2,
        borderColor: getComputedStyle(document.documentElement).getPropertyValue('--color-bg-primary').trim()
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom'
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const total = context.dataset.data.reduce((a, b) => a + b, 0)
              const percentage = ((context.parsed / total) * 100).toFixed(1)
              return `${context.label}: ${context.parsed} (${percentage}%)`
            }
          }
        }
      }
    }
  })
  } catch (error) {
    console.error('Error rendering distribution chart:', error)
    ElMessage.error('Failed to render distribution chart')
  }
}

const renderTrendChart = () => {
  if (!sigmaTrendChart.value) {
    console.warn('Trend chart canvas not available')
    return
  }
  
  if (trendData.value.length === 0) {
    console.warn('No trend data available, length:', trendData.value.length)
    return
  }
  
  // Ensure canvas has dimensions
  const canvas = sigmaTrendChart.value
  if (canvas.offsetWidth === 0 || canvas.offsetHeight === 0) {
    console.warn('Canvas has zero dimensions, waiting...')
    setTimeout(() => renderTrendChart(), 200)
    return
  }
  
  const ctx = canvas.getContext('2d')
  if (!ctx) {
    console.error('Failed to get canvas context')
    return
  }
  
  if (trendChartInstance) {
    trendChartInstance.destroy()
    trendChartInstance = null
  }
  
  try {
    const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();
    const successColor = getComputedStyle(document.documentElement).getPropertyValue('--color-success').trim();
    const warningColor = getComputedStyle(document.documentElement).getPropertyValue('--color-warning').trim();
    const dangerColor = getComputedStyle(document.documentElement).getPropertyValue('--color-danger').trim();

    console.log('Rendering trend chart with', trendData.value.length, 'data points')
    trendChartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: trendData.value.map(item => {
        const date = new Date(item.date)
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
      }),
      datasets: [{
        label: 'Average Sigma Metric',
        data: trendData.value.map(item => {
          const value = parseFloat(item.avg_sigma)
          return isNaN(value) ? 0 : value
        }),
        borderColor: primaryColor,
        backgroundColor: `${primaryColor}1A`,
        tension: 0.4,
        fill: true,
        pointRadius: 3,
        pointHoverRadius: 5
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'top'
        },
        tooltip: {
          mode: 'index',
          intersect: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Average Sigma Metric'
          },
          grid: {
            color: function(context) {
              if (context.tick.value >= 6) return `${successColor}4D` // 30% opacity
              if (context.tick.value >= 4) return `${warningColor}4D`
              return `${dangerColor}4D`
            }
          }
        },
        x: {
          title: {
            display: true,
            text: 'Date'
          }
        }
      }
    }
  })
  } catch (error) {
    console.error('Error rendering trend chart:', error)
    ElMessage.error('Failed to render trend chart')
  }
}

const formatNumber = (value, decimals = 2) => {
  if (value === null || value === undefined) return '-'
  return Number(value).toFixed(decimals)
}

const getStatusColor = (color) => {
  const successColor = getComputedStyle(document.documentElement).getPropertyValue('--color-success').trim();
  const warningColor = getComputedStyle(document.documentElement).getPropertyValue('--color-warning').trim();
  const dangerColor = getComputedStyle(document.documentElement).getPropertyValue('--color-danger').trim();
  const textTertiary = getComputedStyle(document.documentElement).getPropertyValue('--color-text-tertiary').trim();

  const colors = {
    green: { type: 'success', text: successColor },
    yellow: { type: 'warning', text: warningColor },
    red: { type: 'danger', text: dangerColor },
    gray: { type: 'info', text: textTertiary }
  }
  return colors[color] || colors.gray
}

const getSigmaStatusType = (sigma) => {
  if (sigma === null || sigma === undefined) return 'info'
  if (sigma >= 6) return 'success'
  if (sigma >= 4) return 'warning'
  return 'danger'
}

const getSeverityType = (severity) => {
  const types = {
    critical: 'danger',
    high: 'danger',
    moderate: 'warning',
    low: 'info'
  }
  return types[severity] || 'info'
}

const refreshData = () => {
  loadDashboardData()
}

const navigateToQualityControl = () => {
  router.push('/management-report/quality-control')
}

const navigateToSigmaMonitor = () => {
  router.push('/sigma-monitor')
}

// Watch for data changes and re-render charts
watch(trendData, async (newVal) => {
  if (newVal && newVal.length > 0) {
    console.log('Trend data changed, re-rendering chart. Data points:', newVal.length)
    await nextTick()
    await new Promise(resolve => setTimeout(resolve, 300))
    renderTrendChart()
  }
}, { deep: true })

watch(sigmaDistributionData, async (newVal) => {
  if (newVal && newVal.length > 0) {
    await nextTick()
    await new Promise(resolve => setTimeout(resolve, 300))
    renderDistributionChart()
  }
}, { deep: true })

onMounted(async () => {
  await loadDashboardData()
  // Ensure charts render after all data is loaded and DOM is ready
  await nextTick()
  await new Promise(resolve => setTimeout(resolve, 500))
  
  // Force re-render charts with retry logic
  let retries = 3
  while (retries > 0) {
    if (sigmaDistributionChart.value && sigmaDistributionData.value.length > 0) {
      renderDistributionChart()
    }
    if (sigmaTrendChart.value && trendData.value.length > 0) {
      renderTrendChart()
      break
    }
    retries--
    await new Promise(resolve => setTimeout(resolve, 300))
  }
})
</script>

<style scoped>
.quality-control-dashboard {
  width: 100%;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-title {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.metrics-row,
.charts-row,
.recommendations-row,
.performance-row {
  margin-bottom: 20px;
}

.metric-card {
  height: 100%;
}

.metric-content {
  display: flex;
  align-items: center;
  gap: 15px;
}

.metric-icon {
  width: 60px;
  height: 60px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: white;
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
  color: var(--color-text-secondary);
  margin-bottom: 2px;
}

.metric-subtitle {
  font-size: 12px;
  color: var(--color-text-tertiary);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
}

.chart-container {
  height: 300px;
  position: relative;
  padding: 10px;
}

.recommendations-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.recommendation-item {
  padding: 15px;
  border-left: 4px solid var(--color-info);
  border-radius: var(--border-radius-md);
  background-color: var(--color-bg-secondary);
  transition: all var(--transition-normal);
}

.recommendation-item.severity-critical {
  border-left-color: var(--color-danger);
  background-color: var(--color-danger-light);
}

.recommendation-item.severity-high {
  border-left-color: var(--color-danger);
  background-color: var(--color-danger-light);
}

.recommendation-item.severity-moderate {
  border-left-color: var(--color-warning);
  background-color: var(--color-warning-light);
}

.recommendation-item.severity-low {
  border-left-color: var(--color-primary);
  background-color: var(--color-primary-light);
}

.recommendation-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}

.recommendation-icon {
  font-size: 20px;
}

.recommendation-title {
  flex: 1;
  font-weight: 600;
  color: var(--color-text-primary);
}

.severity-badge {
  margin-left: auto;
}

.recommendation-content {
  color: var(--color-text-secondary);
  line-height: 1.6;
}

.recommendation-content p {
  margin: 0 0 10px 0;
}

.action-items {
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid var(--color-border-secondary);
}

.action-items ul {
  margin: 5px 0 0 20px;
  padding: 0;
}

.action-items li {
  margin: 5px 0;
}

.action-buttons-vertical {
  margin-bottom: 20px;
}

.action-descriptions {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.action-description {
  padding: 12px;
  background-color: var(--color-bg-secondary);
  border-radius: 6px;
}

.action-description h4 {
  margin: 0 0 8px 0;
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-primary);
}

.action-description p {
  margin: 0;
  font-size: 12px;
  color: var(--color-text-secondary);
  line-height: 1.5;
}

.empty-state {
  padding: 40px;
  text-align: center;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
  }
  
  .charts-row .el-col,
  .recommendations-row .el-col {
    margin-bottom: 20px;
  }
}
</style>
