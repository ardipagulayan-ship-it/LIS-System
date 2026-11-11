<template>
  <div class="eim-dashboard">
    <div class="page-header">
      <h2 class="page-title">Equipment & Instrument Management</h2>
      <div class="header-actions">
        <el-button type="primary" @click="refreshData">
          <el-icon><Refresh /></el-icon> Refresh
        </el-button>
        <el-button type="success" @click="exportReport">
          <el-icon><Download /></el-icon> Export Report
        </el-button>
      </div>
    </div>

    <!-- Overview Cards -->
    <div class="overview-cards">
      <el-card class="mb-4">
        <template #header>
          <div class="card-header">
            <span>Equipment Status</span>
          </div>
        </template>
        <div class="stat-grid">
          <div class="stat-item">
            <div class="stat-value">{{ dashboardData.equipment?.total || 0 }}</div>
            <div class="stat-label">Total Equipment</div>
          </div>
          <div class="stat-item">
            <div class="stat-value text-success">{{ dashboardData.equipment?.in_service || 0 }}</div>
            <div class="stat-label">In Service</div>
          </div>
          <div class="stat-item">
            <div class="stat-value text-warning">{{ dashboardData.equipment?.under_maintenance || 0 }}</div>
            <div class="stat-label">Under Maintenance</div>
          </div>
          <div class="stat-item">
            <div class="stat-value text-danger">{{ dashboardData.equipment?.overdue || 0 }}</div>
            <div class="stat-label">Overdue</div>
          </div>
        </div>
        <div class="operational-rate">
          <div class="rate-label">Operational Rate</div>
          <div class="rate-value">{{ dashboardData.equipment?.operational_percentage || 0 }}%</div>
        </div>
      </el-card>

      <el-card class="mb-4">
        <template #header>
          <div class="card-header">
            <span>Maintenance Compliance</span>
          </div>
        </template>
        <div class="stat-grid">
          <div class="stat-item">
            <div class="stat-value">{{ dashboardData.maintenance?.total || 0 }}</div>
            <div class="stat-label">Total Schedules</div>
          </div>
          <div class="stat-item">
            <div class="stat-value text-danger">{{ dashboardData.maintenance?.overdue || 0 }}</div>
            <div class="stat-label">Overdue</div>
          </div>
          <div class="stat-item">
            <div class="stat-value text-warning">{{ dashboardData.maintenance?.due_soon || 0 }}</div>
            <div class="stat-label">Due Soon</div>
          </div>
        </div>
        <div class="compliance-rate">
          <div class="rate-label">Compliance Rate</div>
          <div class="rate-value">{{ dashboardData.maintenance?.compliance_rate || 0 }}%</div>
        </div>
      </el-card>

      <el-card class="mb-4">
        <template #header>
          <div class="card-header">
            <span>Service Performance</span>
          </div>
        </template>
        <div class="stat-grid">
          <div class="stat-item">
            <div class="stat-value">{{ dashboardData.service?.total || 0 }}</div>
            <div class="stat-label">Total Services</div>
          </div>
          <div class="stat-item">
            <div class="stat-value text-success">{{ dashboardData.service?.passed || 0 }}</div>
            <div class="stat-label">Passed</div>
          </div>
          <div class="stat-item">
            <div class="stat-value text-danger">{{ dashboardData.service?.failed || 0 }}</div>
            <div class="stat-label">Failed</div>
          </div>
        </div>
        <div class="success-rate">
          <div class="rate-label">Success Rate</div>
          <div class="rate-value">{{ dashboardData.service?.success_rate || 0 }}%</div>
        </div>
      </el-card>
    </div>

    <!-- Charts and Analytics -->
    <div class="analytics-section">
      <el-row :gutter="20">
        <el-col :span="12">
          <el-card class="mb-4">
            <template #header>
              <div class="card-header">
                <span>Equipment Status Distribution</span>
              </div>
            </template>
            <div class="chart-container">
              <canvas ref="statusChart"></canvas>
            </div>
          </el-card>
        </el-col>
        <el-col :span="12">
          <el-card class="mb-4">
            <template #header>
              <div class="card-header">
                <span>Maintenance Schedule Types</span>
              </div>
            </template>
            <div class="chart-container">
              <canvas ref="scheduleTypeChart"></canvas>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <!-- Recent Activities and Alerts -->
    <div class="activities-section">
      <el-row :gutter="20">
        <el-col :span="12">
          <el-card class="mb-4">
            <template #header>
              <div class="card-header">
                <span>Recent Activities</span>
              </div>
            </template>
            <div class="activity-list">
              <div 
                v-for="activity in dashboardData.recent_activities" 
                :key="activity.id"
                class="activity-item"
              >
                <div class="activity-icon">
                  <i :class="getActivityIcon(activity.type)"></i>
                </div>
                <div class="activity-content">
                  <div class="activity-title">{{ activity.action }}</div>
                  <div class="activity-details">
                    Equipment: {{ activity.equipment }} | 
                    {{ formatDate(activity.date) }}
                  </div>
                </div>
                <div class="activity-status">
                  <span :class="getStatusClass(activity.outcome || activity.status)">
                    {{ activity.outcome || activity.status }}
                  </span>
                </div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="12">
          <el-card class="mb-4">
            <template #header>
              <div class="card-header">
                <span>Upcoming Maintenance</span>
              </div>
            </template>
            <div class="maintenance-list">
              <div 
                v-for="item in dashboardData.upcoming_maintenance" 
                :key="item.id"
                class="maintenance-item"
              >
                <div class="maintenance-info">
                  <div class="equipment-name">{{ item.equipment }}</div>
                  <div class="maintenance-type">{{ item.type }}</div>
                  <div class="due-date">Due: {{ formatDate(item.due_date) }}</div>
                </div>
                <div class="maintenance-urgency">
                  <span :class="getUrgencyClass(item.days_until_due)">
                    {{ item.days_until_due }} days
                  </span>
                </div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <!-- Overdue Items Alert -->
    <div v-if="dashboardData.overdue_items?.length > 0" class="alert-section">
      <el-card class="mb-4" style="border-left: 4px solid var(--color-danger);">
          <template #header>
            <div class="card-header">
              <span style="color: var(--color-danger);">
              <el-icon><Warning /></el-icon>
              Overdue Items ({{ dashboardData.overdue_items.length }})
            </span>
          </div>
        </template>
        <div class="overdue-list">
          <div 
            v-for="item in dashboardData.overdue_items" 
            :key="item.id"
            class="overdue-item"
          >
            <div class="overdue-info">
              <div class="equipment-name">{{ item.equipment }}</div>
              <div class="overdue-type">{{ item.type === 'schedule' ? item.schedule_type : item.status }}</div>
              <div class="overdue-date">
                {{ item.type === 'schedule' ? 'Due: ' + formatDate(item.due_date) : 'Next Maintenance: ' + formatDate(item.next_maintenance_date) }}
              </div>
            </div>
            <div class="overdue-urgency">
              <span class="badge badge-danger">
                {{ item.days_overdue }} days overdue
              </span>
            </div>
          </div>
        </div>
      </el-card>
    </div>
    <!-- Quick Actions -->
    <div class="quick-actions">
      <el-card class="mb-4">
        <template #header>
          <div class="card-header">
            <span>Quick Actions</span>
          </div>
        </template>
        <div class="action-buttons">
          <el-button type="primary" @click="navigateToEquipment">
            <el-icon><Tools /></el-icon> Manage Equipment
          </el-button>
          <el-button type="warning" @click="navigateToMaintenance">
            <el-icon><Calendar /></el-icon> Maintenance Schedules
          </el-button>
          <el-button type="info" @click="navigateToServiceLogs">
            <el-icon><Document /></el-icon> Service Logs
          </el-button>
            <el-button type="success" @click="navigateToReports">
              <el-icon><DataLine /></el-icon> Reports
            </el-button>
        </div>
      </el-card>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/utils/axios'
import Chart from 'chart.js/auto'
import { Refresh, Download, Warning, Tools, Calendar, Document, DataLine } from '@element-plus/icons-vue'

export default {
  name: 'EIMDashboard',
  setup() {
    const router = useRouter()
    const dashboardData = ref({})
    const loading = ref(false)
    const statusChart = ref(null)
    const scheduleTypeChart = ref(null)

    const fetchDashboardData = async () => {
      try {
        loading.value = true
        const response = await api.get('eim/dashboard')
        dashboardData.value = response.data
        await nextTick()
        createCharts()
      } catch (error) {
        console.error('Error fetching dashboard data:', error)
      } finally {
        loading.value = false
      }
    }

    const createCharts = () => {
      createStatusChart()
      createScheduleTypeChart()
    }

    const createStatusChart = () => {
      if (!statusChart.value) return

      const ctx = statusChart.value.getContext('2d')
      const statusData = dashboardData.value.equipment?.status_breakdown || {}
      
      // Get palette colors
      const successColor = getComputedStyle(document.documentElement).getPropertyValue('--color-success').trim()
      const warningColor = getComputedStyle(document.documentElement).getPropertyValue('--color-warning').trim()
      const dangerColor = getComputedStyle(document.documentElement).getPropertyValue('--color-danger').trim()
      const infoColor = getComputedStyle(document.documentElement).getPropertyValue('--color-info').trim()
      const darkColor = getComputedStyle(document.documentElement).getPropertyValue('--color-dark').trim()

      new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: Object.keys(statusData).map(key => 
            key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
          ),
          datasets: [{
            data: Object.values(statusData),
            backgroundColor: [
              successColor,   // In Service
              warningColor,   // Under Maintenance
              dangerColor,    // Calibration Due
              infoColor,      // Out of Service
              darkColor       // Retired
            ]
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      })
    }

    const createScheduleTypeChart = () => {
      if (!scheduleTypeChart.value) return

      const ctx = scheduleTypeChart.value.getContext('2d')
      const scheduleData = dashboardData.value.maintenance?.by_type || {}
      
      const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim()
      
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: Object.keys(scheduleData).map(key => 
            key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
          ),
          datasets: [{
            label: 'Schedules',
            data: Object.values(scheduleData),
            backgroundColor: primaryColor
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      })
    }

    const refreshData = () => {
      fetchDashboardData()
    }

    const exportReport = () => {
      // Implement export functionality
      console.log('Export report functionality')
    }

    const getActivityIcon = (type) => {
      const icons = {
        service: 'fas fa-wrench',
        status_change: 'fas fa-exchange-alt'
      }
      return icons[type] || 'fas fa-info-circle'
    }

    const getStatusClass = (status) => {
      const classes = {
        pass: 'badge badge-success',
        fail: 'badge badge-danger',
        partial: 'badge badge-warning',
        in_service: 'badge badge-success',
        under_maintenance: 'badge badge-warning',
        calibration_due: 'badge badge-danger'
      }
      return classes[status] || 'badge badge-secondary'
    }

    const getUrgencyClass = (days) => {
      if (days <= 7) return 'text-danger'
      if (days <= 14) return 'text-warning'
      return 'text-success'
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString()
    }

    const navigateToEquipment = () => {
      router.push('/equipment-management/equipment')
    }

    const navigateToMaintenance = () => {
      router.push('/equipment-management/maintenance-schedules')
    }

    const navigateToServiceLogs = () => {
      router.push('/equipment-management/service-logs')
    }

    const navigateToReports = () => {
      router.push('/equipment-management/reports')
    }

    onMounted(() => {
      fetchDashboardData()
    })

    return {
      dashboardData,
      loading,
      statusChart,
      scheduleTypeChart,
      refreshData,
      exportReport,
      getActivityIcon,
      getStatusClass,
      getUrgencyClass,
      formatDate,
      navigateToEquipment,
      navigateToMaintenance,
      navigateToServiceLogs,
      navigateToReports
    }
  }
}
</script>

<style scoped>
.eim-dashboard {
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

.header-actions {
  display: flex;
  gap: 10px;
}

.overview-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.mb-4 {
  margin-bottom: 1rem;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-body {
  padding: 20px;
}

.stat-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
  margin-bottom: 20px;
}

.stat-item {
  text-align: center;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 5px;
}

.stat-label {
  font-size: 13px;
  color: var(--color-text-secondary);
}

.operational-rate,
.compliance-rate,
.success-rate {
  text-align: center;
  padding: 15px;
  background: var(--color-bg-secondary);
  border-radius: var(--border-radius-md);
}

.rate-label {
  font-size: 13px;
  color: var(--color-text-secondary);
  margin-bottom: 5px;
}

.rate-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-success);
}

.analytics-section {
  margin-bottom: 30px;
}

.chart-container {
  height: 300px;
  position: relative;
}

.activities-section {
  margin-bottom: 30px;
}

.activity-list,
.maintenance-list,
.overdue-list {
  max-height: 400px;
  overflow-y: auto;
}

.activity-item,
.maintenance-item,
.overdue-item {
  display: flex;
  align-items: center;
  padding: 12px;
  border-bottom: 1px solid var(--color-border-light);
}

.activity-item:last-child,
.maintenance-item:last-child,
.overdue-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--color-bg-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
}

.activity-content,
.maintenance-info,
.overdue-info {
  flex: 1;
}

.activity-title,
.equipment-name {
  font-weight: 600;
  margin-bottom: 4px;
}

.activity-details,
.maintenance-type,
.overdue-type {
  font-size: 13px;
  color: var(--color-text-secondary);
}

.due-date,
.overdue-date {
  font-size: 13px;
  color: var(--color-text-secondary);
}

.activity-status,
.maintenance-urgency,
.overdue-urgency {
  margin-left: 15px;
}

.alert-section {
  margin-bottom: 30px;
}

.alert-card {
  border-left: 4px solid #dc3545;
}

.quick-actions {
  margin-bottom: 30px;
}

.action-buttons {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-primary {
  background: var(--color-primary);
  color: var(--color-text-light);
}

.btn-success {
  background: var(--color-success);
  color: var(--color-text-light);
}

.btn-warning {
  background: var(--color-warning);
  color: var(--color-dark);
}

.btn-info {
  background: var(--color-info);
  color: var(--color-text-light);
}

.btn-danger {
  background: var(--color-danger);
  color: var(--color-text-light);
}

.btn:hover {
  opacity: 0.9;
  transform: translateY(-1px);
}

.badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 13px;
  font-weight: 500;
}

.badge-success {
  background: var(--color-success-light);
  color: var(--color-success-dark);
}

.badge-danger {
  background: var(--color-danger-light);
  color: var(--color-danger-dark);
}

.badge-warning {
  background: var(--color-warning-light);
  color: var(--color-warning-dark);
}

.badge-secondary {
  background: var(--color-bg-secondary);
  color: var(--color-text-secondary);
}

.text-success {
  color: var(--color-success) !important;
}

.text-warning {
  color: var(--color-warning) !important;
}

.text-danger {
  color: var(--color-danger) !important;
}

@media (max-width: 768px) {
  .dashboard-header {
    flex-direction: column;
    gap: 15px;
  }
  
  .overview-cards {
    grid-template-columns: 1fr;
  }
  
  .stat-grid {
    grid-template-columns: 1fr;
  }
  
  .action-buttons {
    flex-direction: column;
  }
}
</style>
