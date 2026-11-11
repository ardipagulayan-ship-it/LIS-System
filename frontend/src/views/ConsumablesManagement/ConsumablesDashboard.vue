<template>
  <div class="consumables-dashboard">
    <div class="page-header">
      <h2 class="page-title">Consumables & Reagents Dashboard</h2>
      <div class="header-actions">
        <el-button @click="refreshData">
          <el-icon><Refresh /></el-icon> Refresh
        </el-button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <el-row :gutter="20" class="stats-row">
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon" style="background-color: var(--color-primary);">
              <el-icon><Box /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.total_consumables }}</div>
              <div class="stat-label">Total Consumables</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon" style="background-color: var(--color-success);">
              <el-icon><CircleCheck /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.active_consumables }}</div>
              <div class="stat-label">Active Items</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon" style="background-color: var(--color-warning);">
              <el-icon><Warning /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.expiring_in_30_days }}</div>
              <div class="stat-label">Expiring Soon (30 days)</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon" style="background-color: var(--color-danger);">
              <el-icon><CircleClose /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.expired_consumables }}</div>
              <div class="stat-label">Expired Items</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Alerts Section -->
    <el-row :gutter="20" class="mt-4">
      <el-col :span="8">
        <el-card>
          <template #header>
            <div class="card-header">
              <span><el-icon><Warning /></el-icon> Low Stock Alert</span>
              <el-badge :value="alerts.lowStock.length" class="item" />
            </div>
          </template>
          <div v-if="alerts.lowStock.length === 0" class="no-data">
            No low stock items
          </div>
          <el-scrollbar height="200px" v-else>
            <el-list>
              <el-list-item v-for="item in alerts.lowStock" :key="item.id">
                <div class="alert-item">
                  <div class="alert-name">{{ item.name }}</div>
                  <div class="alert-details">
                    Stock: {{ item.current_stock_level }} / {{ item.reorder_level }}
                  </div>
                </div>
              </el-list-item>
            </el-list>
          </el-scrollbar>
          <div class="card-footer">
            <el-button type="text" @click="navigateToConsumables('low_stock')">View All</el-button>
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card>
          <template #header>
            <div class="card-header">
              <span><el-icon><CircleClose /></el-icon> Expiring Soon</span>
              <el-badge :value="alerts.expiring.length" class="item" />
            </div>
          </template>
          <div v-if="alerts.expiring.length === 0" class="no-data">
            No items expiring soon
          </div>
          <el-scrollbar height="200px" v-else>
            <el-list>
              <el-list-item v-for="item in alerts.expiring" :key="item.id">
                <div class="alert-item">
                  <div class="alert-name">{{ item.name }}</div>
                  <div class="alert-details">
                    Expires: {{ formatDate(item.expiration_date) }}
                  </div>
                </div>
              </el-list-item>
            </el-list>
          </el-scrollbar>
          <div class="card-footer">
            <el-button type="text" @click="navigateToConsumables('expiring')">View All</el-button>
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card>
          <template #header>
            <div class="card-header">
              <span><el-icon><CircleClose /></el-icon> Expired Items</span>
              <el-badge :value="alerts.expired.length" class="item" type="danger" />
            </div>
          </template>
          <div v-if="alerts.expired.length === 0" class="no-data">
            No expired items
          </div>
          <el-scrollbar height="200px" v-else>
            <el-list>
              <el-list-item v-for="item in alerts.expired" :key="item.id">
                <div class="alert-item">
                  <div class="alert-name">{{ item.name }}</div>
                  <div class="alert-details text-danger">
                    Expired: {{ formatDate(item.expiration_date) }}
                  </div>
                </div>
              </el-list-item>
            </el-list>
          </el-scrollbar>
          <div class="card-footer">
            <el-button type="text" @click="navigateToConsumables('expired')">View All</el-button>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Quick Actions -->
    <el-card class="mt-4">
      <template #header>
        <div class="card-header">
          <span>Quick Actions</span>
        </div>
      </template>
      <div class="quick-actions">
        <el-button type="primary" size="large" @click="navigateToConsumables()">
          <el-icon><Box /></el-icon> Manage Consumables
        </el-button>
        <el-button type="success" size="large" @click="navigateToConsumables()">
          <el-icon><Plus /></el-icon> Add New Consumable
        </el-button>
        <el-button type="warning" size="large" @click="navigateToConsumables('low_stock')">
          <el-icon><Warning /></el-icon> View Low Stock Items
        </el-button>
        <el-button type="danger" size="large" @click="navigateToConsumables('expired')">
          <el-icon><CircleClose /></el-icon> Review Expired Items
        </el-button>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import api from '@/utils/axios'

const router = useRouter()

const stats = reactive({
  total_consumables: 0,
  active_consumables: 0,
  expired_consumables: 0,
  expiring_in_30_days: 0
})

const alerts = reactive({
  lowStock: [],
  expiring: [],
  expired: []
})

onMounted(() => {
  loadDashboardData()
})

const loadDashboardData = async () => {
  try {
    const [statsData, alertsData] = await Promise.all([
      api.get('eim/consumables/stats/dashboard'),
      api.get('eim/consumables/alerts/expiration')
    ])
    
    Object.assign(stats, statsData.data)
    alerts.expiring = alertsData.data.critical || []
    alerts.expired = alertsData.data.expired || []
    
    const lowStockResponse = await api.get('eim/consumables/alerts/low-stock')
    alerts.lowStock = lowStockResponse.data.items || []
  } catch (error) {
    console.error('Failed to load dashboard data', error)
    ElMessage.error('Failed to load dashboard data')
  }
}

const refreshData = () => {
  loadDashboardData()
}

const navigateToConsumables = (filter) => {
  const query = filter ? { filter } : {}
  router.push({ name: 'consumables-management', query })
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}
</script>

<style scoped>
.consumables-dashboard {
  padding: var(--spacing-xl);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-xl);
}

.page-title {
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-semibold);
  margin: 0;
}

.stats-row {
  margin-bottom: var(--spacing-xl);
}

.stat-card {
  height: 100%;
  border-radius: var(--border-radius-lg);
  box-shadow: var(--shadow-sm);
}

.stat-content {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: var(--border-radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: var(--font-size-2xl);
  color: var(--color-text-light);
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
  line-height: var(--line-height-compact);
  margin-bottom: var(--spacing-xs);
}

.stat-label {
  font-size: var(--font-size-sm);
  color: var(--color-text-tertiary);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: var(--font-weight-semibold);
}

.alert-item {
  padding: var(--spacing-sm) 0;
}

.alert-name {
  font-weight: var(--font-weight-medium);
  margin-bottom: var(--spacing-xs);
}

.alert-details {
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
}

.text-danger {
  color: var(--color-danger);
}

.no-data {
  text-align: center;
  padding: var(--spacing-3xl);
  color: var(--color-text-tertiary);
}

.card-footer {
  text-align: center;
  padding-top: var(--spacing-sm);
  border-top: 1px solid var(--color-border-secondary);
  margin-top: var(--spacing-sm);
}

.quick-actions {
  display: flex;
  gap: var(--spacing-lg);
  flex-wrap: wrap;
}

.mt-4 {
  margin-top: var(--spacing-xl);
}
</style>
