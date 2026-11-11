<template>
  <div class="reports-management">
    <div class="page-header">
      <h2 class="page-title">Inventory Reports</h2>
      <div class="header-actions">
        <el-button type="primary" @click="generateReport">
          <el-icon><Download /></el-icon> Generate Report
        </el-button>
        <el-button @click="refreshData">
          <el-icon><Refresh /></el-icon> Refresh
        </el-button>
      </div>
    </div>

    <!-- Report Type Selection -->
    <el-card class="report-selection-card">
      <template #header>
        <div class="card-header">
          <span>Select Report Type</span>
        </div>
      </template>
      <el-row :gutter="20">
        <el-col :span="6">
          <el-card 
            class="report-type-card" 
            :class="{ active: selectedReport === 'reorder' }"
            @click="selectReport('reorder')"
          >
            <div class="report-icon">
              <el-icon><ShoppingCart /></el-icon>
            </div>
            <div class="report-title">Reorder Report</div>
            <div class="report-description">Items that need to be reordered</div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card 
            class="report-type-card" 
            :class="{ active: selectedReport === 'expiration' }"
            @click="selectReport('expiration')"
          >
            <div class="report-icon">
              <el-icon><Warning /></el-icon>
            </div>
            <div class="report-title">Expiration Report</div>
            <div class="report-description">Items expiring soon</div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card 
            class="report-type-card" 
            :class="{ active: selectedReport === 'consumption' }"
            @click="selectReport('consumption')"
          >
            <div class="report-icon">
              <el-icon><TrendCharts /></el-icon>
            </div>
            <div class="report-title">Consumption Report</div>
            <div class="report-description">Usage patterns and trends</div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card 
            class="report-type-card" 
            :class="{ active: selectedReport === 'cost' }"
            @click="selectReport('cost')"
          >
            <div class="report-icon">
              <el-icon><Money /></el-icon>
            </div>
            <div class="report-title">Cost Analysis</div>
            <div class="report-description">Inventory value and costs</div>
          </el-card>
        </el-col>
      </el-row>
    </el-card>

    <!-- Report Content -->
    <el-card class="report-content-card" v-if="selectedReport">
      <template #header>
        <div class="card-header">
          <span>{{ getReportTitle() }}</span>
          <div class="header-actions">
            <el-button type="primary" @click="exportReport">
              <el-icon><Download /></el-icon> Export
            </el-button>
          </div>
        </div>
      </template>

      <!-- Reorder Report -->
      <div v-if="selectedReport === 'reorder'" v-loading="loading">
        <el-table :data="reorderData" stripe>
          <el-table-column prop="material.generic_name" label="Material" min-width="200" />
          <el-table-column prop="current_stock" label="Current Stock" width="120" />
          <el-table-column prop="reorder_point" label="Reorder Point" width="120" />
          <el-table-column prop="reorder_quantity" label="Reorder Quantity" width="150" />
          <el-table-column prop="shortage" label="Shortage" width="120">
            <template #default="{ row }">
              <el-tag type="danger">{{ row.shortage }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="Lots" width="200">
            <template #default="{ row }">
              <div v-for="lot in row.lots" :key="lot.id" class="lot-info">
                {{ lot.lot_number }} ({{ lot.current_stock_level }})
              </div>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <!-- Expiration Report -->
      <div v-if="selectedReport === 'expiration'" v-loading="loading">
        <el-tabs v-model="activeExpirationTab">
          <el-tab-pane label="Critical (≤30 days)" name="critical">
            <el-table :data="expirationData.critical" stripe>
              <el-table-column prop="material.generic_name" label="Material" min-width="200" />
              <el-table-column prop="lot_number" label="Lot Number" width="150" />
              <el-table-column prop="expiration_date" label="Expiration Date" width="150">
                <template #default="{ row }">
                  <span class="critical-date">{{ formatDate(row.expiration_date) }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="current_stock_level" label="Stock Level" width="120" />
              <el-table-column prop="vendor.vendor_name" label="Vendor" width="150" />
            </el-table>
          </el-tab-pane>
          <el-tab-pane label="Warning (31-60 days)" name="warning">
            <el-table :data="expirationData.warning" stripe>
              <el-table-column prop="material.generic_name" label="Material" min-width="200" />
              <el-table-column prop="lot_number" label="Lot Number" width="150" />
              <el-table-column prop="expiration_date" label="Expiration Date" width="150">
                <template #default="{ row }">
                  <span class="warning-date">{{ formatDate(row.expiration_date) }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="current_stock_level" label="Stock Level" width="120" />
              <el-table-column prop="vendor.vendor_name" label="Vendor" width="150" />
            </el-table>
          </el-tab-pane>
          <el-tab-pane label="Alert (61-90 days)" name="alert">
            <el-table :data="expirationData.alert" stripe>
              <el-table-column prop="material.generic_name" label="Material" min-width="200" />
              <el-table-column prop="lot_number" label="Lot Number" width="150" />
              <el-table-column prop="expiration_date" label="Expiration Date" width="150">
                <template #default="{ row }">
                  <span class="alert-date">{{ formatDate(row.expiration_date) }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="current_stock_level" label="Stock Level" width="120" />
              <el-table-column prop="vendor.vendor_name" label="Vendor" width="150" />
            </el-table>
          </el-tab-pane>
        </el-tabs>
      </div>

      <!-- Consumption Report -->
      <div v-if="selectedReport === 'consumption'" v-loading="loading">
        <div class="consumption-summary">
          <el-row :gutter="20">
            <el-col :span="6">
              <el-statistic title="Total Usage (30 days)" :value="consumptionData.totalUsage" />
            </el-col>
            <el-col :span="6">
              <el-statistic title="Most Used Material" :value="consumptionData.topMaterial" />
            </el-col>
            <el-col :span="6">
              <el-statistic title="Average Daily Usage" :value="consumptionData.avgDailyUsage" />
            </el-col>
            <el-col :span="6">
              <el-statistic title="Total Cost" :value="consumptionData.totalCost" prefix="$" />
            </el-col>
          </el-row>
        </div>
        <el-table :data="consumptionData.details" stripe class="mt-4">
          <el-table-column prop="material.generic_name" label="Material" min-width="200" />
          <el-table-column prop="totalUsage" label="Total Usage" width="120" />
          <el-table-column prop="avgDailyUsage" label="Avg Daily Usage" width="150" />
          <el-table-column prop="totalCost" label="Total Cost" width="120">
            <template #default="{ row }">
              ${{ row.totalCost }}
            </template>
          </el-table-column>
          <el-table-column prop="lastUsed" label="Last Used" width="150">
            <template #default="{ row }">
              {{ formatDate(row.lastUsed) }}
            </template>
          </el-table-column>
        </el-table>
      </div>

      <!-- Cost Analysis Report -->
      <div v-if="selectedReport === 'cost'" v-loading="loading">
        <div class="cost-summary">
          <el-row :gutter="20">
            <el-col :span="6">
              <el-statistic title="Total Inventory Value" :value="costData.totalValue" prefix="$" />
            </el-col>
            <el-col :span="6">
              <el-statistic title="Active Lots Value" :value="costData.activeValue" prefix="$" />
            </el-col>
            <el-col :span="6">
              <el-statistic title="Expired Value" :value="costData.expiredValue" prefix="$" />
            </el-col>
            <el-col :span="6">
              <el-statistic title="Monthly Spend" :value="costData.monthlySpend" prefix="$" />
            </el-col>
          </el-row>
        </div>
        <el-table :data="costData.breakdown" stripe class="mt-4">
          <el-table-column prop="material.generic_name" label="Material" min-width="200" />
          <el-table-column prop="totalValue" label="Total Value" width="120">
            <template #default="{ row }">
              ${{ row.totalValue }}
            </template>
          </el-table-column>
          <el-table-column prop="activeValue" label="Active Value" width="120">
            <template #default="{ row }">
              ${{ row.activeValue }}
            </template>
          </el-table-column>
          <el-table-column prop="expiredValue" label="Expired Value" width="120">
            <template #default="{ row }">
              ${{ row.expiredValue }}
            </template>
          </el-table-column>
          <el-table-column prop="lotCount" label="Lot Count" width="100" />
        </el-table>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import api from '@/utils/axios'

const selectedReport = ref('')
const activeExpirationTab = ref('critical')
const loading = ref(false)

const reorderData = ref([])
const expirationData = reactive({
  critical: [],
  warning: [],
  alert: []
})
const consumptionData = reactive({
  totalUsage: 0,
  topMaterial: '',
  avgDailyUsage: 0,
  totalCost: 0,
  details: []
})
const costData = reactive({
  totalValue: 0,
  activeValue: 0,
  expiredValue: 0,
  monthlySpend: 0,
  breakdown: []
})

onMounted(() => {
  // Load initial data if needed
})

const selectReport = async (reportType) => {
  selectedReport.value = reportType
  await loadReportData(reportType)
}

const loadReportData = async (reportType) => {
  loading.value = true
  try {
    switch (reportType) {
      case 'reorder':
        await loadReorderReport()
        break
      case 'expiration':
        await loadExpirationReport()
        break
      case 'consumption':
        await loadConsumptionReport()
        break
      case 'cost':
        await loadCostReport()
        break
    }
  } catch (error) {
    console.error(`Failed to load ${reportType} report`, error)
    ElMessage.error(`Failed to load ${reportType} report`)
  } finally {
    loading.value = false
  }
}

const loadReorderReport = async () => {
  const response = await api.get('eim/imm/reports/reorder')
  reorderData.value = response.data.items || []
}

const loadExpirationReport = async () => {
  const response = await api.get('eim/imm/alerts/expiration')
  expirationData.critical = response.data.critical || []
  expirationData.warning = response.data.warning || []
  expirationData.alert = response.data.alert || []
}

const loadConsumptionReport = async () => {
  // Mock data for now - implement actual API call
  consumptionData.totalUsage = 150
  consumptionData.topMaterial = 'Glucose Reagent'
  consumptionData.avgDailyUsage = 5.2
  consumptionData.totalCost = 2500
  consumptionData.details = [
    {
      material: { generic_name: 'Glucose Reagent' },
      totalUsage: 50,
      avgDailyUsage: 1.7,
      totalCost: 800,
      lastUsed: new Date()
    }
  ]
}

const loadCostReport = async () => {
  // Mock data for now - implement actual API call
  costData.totalValue = 50000
  costData.activeValue = 45000
  costData.expiredValue = 5000
  costData.monthlySpend = 10000
  costData.breakdown = [
    {
      material: { generic_name: 'Glucose Reagent' },
      totalValue: 15000,
      activeValue: 12000,
      expiredValue: 3000,
      lotCount: 5
    }
  ]
}

const generateReport = () => {
  if (!selectedReport.value) {
    ElMessage.warning('Please select a report type first')
    return
  }
  ElMessage.success(`${getReportTitle()} generated successfully`)
}

const exportReport = () => {
  ElMessage.success('Report exported successfully')
}

const refreshData = () => {
  if (selectedReport.value) {
    loadReportData(selectedReport.value)
  }
}

const getReportTitle = () => {
  const titles = {
    reorder: 'Reorder Report',
    expiration: 'Expiration Report',
    consumption: 'Consumption Report',
    cost: 'Cost Analysis Report'
  }
  return titles[selectedReport.value] || 'Report'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}
</script>

<style scoped>
.reports-management {
  width: 100%;
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

.report-selection-card {
  margin-bottom: 20px;
}

.report-type-card {
  cursor: pointer;
  transition: all 0.3s;
  text-align: center;
  padding: 20px;
}

.report-type-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.report-type-card.active {
  border-color: var(--color-primary);
  background-color: var(--color-primary-light);
}

.report-icon {
  font-size: 32px;
  color: var(--color-primary);
  margin-bottom: var(--spacing-sm);
}

.report-title {
  font-size: var(--font-size-md);
  font-weight: 600;
  margin-bottom: var(--spacing-xs);
}

.report-description {
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
}

.report-content-card {
  margin-bottom: var(--spacing-lg);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
}

.consumption-summary,
.cost-summary {
  margin-bottom: var(--spacing-lg);
}

.lot-info {
  font-size: var(--font-size-xs);
  margin-bottom: var(--spacing-xs);
}

.critical-date {
  color: var(--color-danger);
  font-weight: bold;
}

.warning-date {
  color: var(--color-warning);
  font-weight: bold;
}

.alert-date {
  color: var(--color-text-tertiary);
  font-weight: bold;
}

.mt-4 {
  margin-top: 20px;
}
</style>
