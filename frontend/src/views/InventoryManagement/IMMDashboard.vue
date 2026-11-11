<template>
  <div class="imm-dashboard">
    <div class="page-header">
      <h2 class="page-title">Inventory Management Module (IMM)</h2>
      <div class="header-actions">
        <el-button type="primary" @click="openQuickActions">
          <el-icon><Plus /></el-icon> Quick Actions
        </el-button>
        <el-button @click="refreshData">
          <el-icon><Refresh /></el-icon> Refresh
        </el-button>
      </div>
    </div>

    <!-- Key Metrics Cards -->
    <el-row :gutter="20" class="metrics-row">
      <el-col :span="6">
        <el-card class="metric-card">
          <div class="metric-content">
            <div class="metric-icon" style="background-color: var(--color-primary);">
              <el-icon><Box /></el-icon>
            </div>
            <div class="metric-info">
              <div class="metric-value">{{ stats.materials?.total || 0 }}</div>
              <div class="metric-label">Total Materials</div>
              <div class="metric-subtitle">{{ stats.materials?.active || 0 }} Active</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="metric-card">
          <div class="metric-content">
            <div class="metric-icon" style="background-color: var(--color-success);">
              <el-icon><Collection /></el-icon>
            </div>
            <div class="metric-info">
              <div class="metric-value">{{ stats.lots?.total || 0 }}</div>
              <div class="metric-label">Total Lots</div>
              <div class="metric-subtitle">{{ stats.lots?.active || 0 }} Active</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="metric-card">
          <div class="metric-content">
            <div class="metric-icon" style="background-color: var(--color-warning);">
              <el-icon><Shop /></el-icon>
            </div>
            <div class="metric-info">
              <div class="metric-value">{{ stats.vendors?.total || 0 }}</div>
              <div class="metric-label">Vendors</div>
              <div class="metric-subtitle">{{ stats.vendors?.active || 0 }} Active</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="metric-card">
          <div class="metric-content">
            <div class="metric-icon" style="background-color: var(--color-danger);">
              <el-icon><Money /></el-icon>
            </div>
            <div class="metric-info">
              <div class="metric-value">${{ formatCurrency(stats.financial?.total_inventory_value || 0) }}</div>
              <div class="metric-label">Inventory Value</div>
              <div class="metric-subtitle">${{ formatCurrency(stats.financial?.pending_po_value || 0) }} Pending</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Critical Alerts -->
    <el-row :gutter="20" class="mt-4">
      <el-col :span="8">
        <el-card class="alert-card">
          <template #header>
            <div class="card-header">
              <span><el-icon><Warning /></el-icon> Critical Alerts</span>
              <el-badge :value="alerts.critical.length" class="item" type="danger" />
            </div>
          </template>
          <div v-if="alerts.critical.length === 0" class="no-data">
            No critical alerts
          </div>
          <el-scrollbar height="200px" v-else>
            <el-list>
              <el-list-item v-for="alert in alerts.critical" :key="alert.id">
                <div class="alert-item">
                  <div class="alert-name">{{ alert.material?.generic_name }}</div>
                  <div class="alert-details">
                    Lot: {{ alert.lot_number }} - Expires: {{ formatDate(alert.expiration_date) }}
                  </div>
                </div>
              </el-list-item>
            </el-list>
          </el-scrollbar>
          <div class="card-footer">
            <el-button type="text" @click="viewAlerts('critical')">View All</el-button>
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card class="alert-card">
          <template #header>
            <div class="card-header">
              <span><el-icon><CircleClose /></el-icon> Low Stock</span>
              <el-badge :value="alerts.lowStock.length" class="item" type="warning" />
            </div>
          </template>
          <div v-if="alerts.lowStock.length === 0" class="no-data">
            No low stock items
          </div>
          <el-scrollbar height="200px" v-else>
            <el-list>
              <el-list-item v-for="alert in alerts.lowStock" :key="alert.id">
                <div class="alert-item">
                  <div class="alert-name">{{ alert.material?.generic_name }}</div>
                  <div class="alert-details">
                    Stock: {{ alert.current_stock_level }} / {{ alert.reorder_point }}
                  </div>
                </div>
              </el-list-item>
            </el-list>
          </el-scrollbar>
          <div class="card-footer">
            <el-button type="text" @click="viewAlerts('lowStock')">View All</el-button>
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card class="alert-card">
          <template #header>
            <div class="card-header">
              <span><el-icon><Lock /></el-icon> Quarantine</span>
              <el-badge :value="alerts.quarantine.length" class="item" />
            </div>
          </template>
          <div v-if="alerts.quarantine.length === 0" class="no-data">
            No items in quarantine
          </div>
          <el-scrollbar height="200px" v-else>
            <el-list>
              <el-list-item v-for="alert in alerts.quarantine" :key="alert.id">
                <div class="alert-item">
                  <div class="alert-name">{{ alert.material?.generic_name }}</div>
                  <div class="alert-details">
                    Lot: {{ alert.lot_number }} - Received: {{ formatDate(alert.date_received) }}
                  </div>
                </div>
              </el-list-item>
            </el-list>
          </el-scrollbar>
          <div class="card-footer">
            <el-button type="text" @click="viewAlerts('quarantine')">View All</el-button>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Material Types Overview -->
    <el-card class="mt-4">
      <template #header>
        <div class="card-header">
          <span>Material Types Overview</span>
        </div>
      </template>
      <el-row :gutter="20">
        <el-col :span="4" v-for="(count, type) in stats.materials?.by_type" :key="type">
          <div class="type-card">
            <div class="type-icon">
              <el-icon><Box /></el-icon>
            </div>
            <div class="type-name">{{ getMaterialTypeLabel(type) }}</div>
            <div class="type-count">{{ count }}</div>
          </div>
        </el-col>
      </el-row>
    </el-card>

    <!-- Quick Actions -->
    <el-card class="mt-4">
      <template #header>
        <div class="card-header">
          <span>Quick Actions</span>
        </div>
      </template>
      <div class="quick-actions">
        <el-button type="primary" size="large" @click="navigateToMaterials">
          <el-icon><Box /></el-icon> Manage Materials
        </el-button>
        <el-button type="success" size="large" @click="navigateToLots">
          <el-icon><Collection /></el-icon> Manage Lots
        </el-button>
        <el-button type="warning" size="large" @click="navigateToVendors">
          <el-icon><Shop /></el-icon> Manage Vendors
        </el-button>
        <el-button type="info" size="large" @click="navigateToReports">
          <el-icon><DataAnalysis /></el-icon> View Reports
        </el-button>
      </div>
    </el-card>

    <!-- Quick Actions Dialog -->
    <QuickActionsModal
      :visible="quickActionsVisible"
      @update:visible="quickActionsVisible = $event"
      @close="quickActionsVisible = false"
      @action="handleQuickAction"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Plus, Refresh, Box, Collection, Shop, Money, Warning, CircleClose, Lock, DataAnalysis, ShoppingCart, TrendCharts, Download } from '@element-plus/icons-vue'
import api from '@/utils/axios'
import QuickActionsModal from '@/components/modals/inventory/QuickActionsModal.vue'

const router = useRouter()

const stats = reactive({
  materials: {},
  lots: {},
  vendors: {},
  alerts: {},
  financial: {}
})

const alerts = reactive({
  critical: [],
  lowStock: [],
  quarantine: []
})

const quickActionsVisible = ref(false)

onMounted(() => {
  loadDashboardData()
})

const loadDashboardData = async () => {
  try {
    const [statsData, expirationAlerts, lowStockAlerts] = await Promise.all([
      api.get('eim/imm/dashboard'),
      api.get('eim/imm/alerts/expiration'),
      api.get('eim/imm/alerts/low-stock')
    ])
    
    Object.assign(stats, statsData.data)
    alerts.critical = expirationAlerts.data.critical || []
    alerts.lowStock = lowStockAlerts.data.lots || []
    
    // Load quarantine lots
    const quarantineResponse = await api.get('eim/imm/lots', { params: { status: 'quarantine' } })
    alerts.quarantine = quarantineResponse.data.data || []
  } catch (error) {
    console.error('Failed to load dashboard data', error)
    ElMessage.error('Failed to load dashboard data')
  }
}

const refreshData = () => {
  loadDashboardData()
}

const openQuickActions = () => {
  quickActionsVisible.value = true
}

const navigateToMaterials = () => {
  router.push({ name: 'imm-materials' })
}

const navigateToLots = () => {
  router.push({ name: 'imm-lots' })
}

const navigateToVendors = () => {
  router.push({ name: 'imm-vendors' })
}

const navigateToReports = () => {
  router.push({ name: 'imm-reports' })
}

const viewAlerts = (type) => {
  const routeMap = {
    critical: 'imm-lots',
    lowStock: 'imm-lots',
    quarantine: 'imm-lots'
  }
  
  const query = {
    filter: type === 'critical' ? 'expiring' : type
  }
  
  router.push({ name: routeMap[type], query })
}

const handleQuickAction = (action) => {
  switch (action) {
    case 'createMaterial':
      router.push({ name: 'imm-materials', query: { action: 'create' } })
      break
    case 'createLot':
      router.push({ name: 'imm-lots', query: { action: 'create' } })
      break
    case 'createVendor':
      router.push({ name: 'imm-vendors', query: { action: 'create' } })
      break
    case 'reorderReport':
      router.push({ name: 'imm-reports', query: { report: 'reorder' } })
      break
  }
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount)
}

const getMaterialTypeLabel = (type) => {
  const labels = {
    reagent: 'Reagents',
    control: 'Controls',
    calibrator: 'Calibrators',
    consumable: 'Consumables',
    equipment: 'Equipment'
  }
  return labels[type] || type
}
</script>

<style scoped>
.imm-dashboard {
  padding: 20px;
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

.alert-card {
  height: 100%;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
}

.alert-item {
  padding: 8px 0;
}

.alert-name {
  font-weight: 500;
  margin-bottom: 4px;
}

.alert-details {
  font-size: 12px;
  color: var(--color-text-tertiary);
}

.no-data {
  text-align: center;
  padding: 40px;
  color: var(--color-text-tertiary);
}

.card-footer {
  text-align: center;
  padding-top: 10px;
  border-top: 1px solid var(--color-border-secondary);
  margin-top: 10px;
}

.type-card {
  text-align: center;
  padding: 20px;
  border: 1px solid var(--color-border-secondary);
  border-radius: var(--border-radius-lg);
}

.type-icon {
  font-size: 24px;
  color: var(--color-primary);
  margin-bottom: 10px;
}

.type-name {
  font-size: 13px;
  color: var(--color-text-secondary);
  margin-bottom: 5px;
}

.type-count {
  font-size: 20px;
  font-weight: bold;
  color: var(--color-text-primary);
}

.quick-actions {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
}

.mt-4 {
  margin-top: 20px;
}
</style>
