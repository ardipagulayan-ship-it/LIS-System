<template>
  <div class="consumables-management">
    <div class="page-header">
      <h2 class="page-title">Consumables & Reagents Management</h2>
      <div class="header-actions">
        <el-button type="primary" @click="openAddDialog">
          <el-icon><Plus /></el-icon> Add Consumable
        </el-button>
        <el-button @click="refreshData">
          <el-icon><Refresh /></el-icon> Refresh
        </el-button>
      </div>
    </div>

    <!-- Alerts Cards -->
    <div class="alerts-section">
      <el-alert
        v-if="alerts.lowStock.length > 0"
        title="Low Stock Alert"
        type="warning"
        :closable="false"
        show-icon
        class="mb-3"
      >
        <div class="alert-content">
          <span>{{ alerts.lowStock.length }} items below reorder level</span>
          <el-link type="warning" @click="filterByLowStock">View Details</el-link>
        </div>
      </el-alert>

      <el-alert
        v-if="alerts.expiring.length > 0"
        title="Expiring Soon"
        type="error"
        :closable="false"
        show-icon
        class="mb-3"
      >
        <div class="alert-content">
          <span>{{ alerts.expiring.length }} items expiring in 30 days or less</span>
          <el-link type="danger" @click="filterByExpiring">View Details</el-link>
        </div>
      </el-alert>

      <el-alert
        v-if="alerts.expired.length > 0"
        title="Expired Items"
        type="error"
        :closable="false"
        show-icon
        class="mb-3"
      >
        <div class="alert-content">
          <span>{{ alerts.expired.length }} items have expired</span>
          <el-link type="danger" @click="filterByExpired">View Details</el-link>
        </div>
      </el-alert>
    </div>

    <!-- Filters -->
    <el-card class="mb-4">
      <template #header>
        <div class="card-header">
          <span>Filters</span>
        </div>
      </template>
      <div class="filters">
        <el-input
          v-model="filters.search"
          placeholder="Search by name, material ID, lot number..."
          clearable
          style="width: 300px"
          @input="loadConsumables"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <el-select
          v-model="filters.expired"
          placeholder="Expiration Status"
          clearable
          style="width: 200px"
          @change="loadConsumables"
        >
          <el-option label="Active" :value="false" />
          <el-option label="Expired" :value="true" />
        </el-select>
        <el-select
          v-model="filters.lowStock"
          placeholder="Stock Level"
          clearable
          style="width: 200px"
          @change="loadConsumables"
        >
          <el-option label="Low Stock" :value="true" />
        </el-select>
      </div>
    </el-card>

    <!-- Consumables Table -->
    <el-card>
      <el-table :data="consumables" v-loading="loading" border stripe>
        <el-table-column prop="material_id" label="Material ID" width="150" />
        <el-table-column prop="name" label="Name" min-width="200" />
        <el-table-column prop="manufacturer" label="Manufacturer" width="150" />
        <el-table-column prop="catalog_number" label="Catalog Number" width="150" />
        <el-table-column prop="lot_number" label="Lot Number" width="130" />
        <el-table-column prop="expiration_date" label="Expiration Date" width="130">
          <template #default="{ row }">
            <span :class="getExpirationClass(row)">{{ formatDate(row.expiration_date) }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="current_stock_level" label="Stock Level" width="120">
          <template #default="{ row }">
            <div>
              <span :class="getStockClass(row)">
                {{ row.current_stock_level }} {{ row.unit_of_measure }}
              </span>
              <div v-if="row.current_stock_level <= row.reorder_level" class="text-warning">
                <el-icon><Warning /></el-icon> Low Stock
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="storage_location" label="Storage Location" width="150" />
        <el-table-column label="Status" width="120">
          <template #default="{ row }">
            <el-tag v-if="row.is_expired" type="danger" size="small">Expired</el-tag>
            <el-tag v-else-if="row.is_locked" type="warning" size="small">Locked</el-tag>
            <el-tag v-else type="success" size="small">Active</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="200" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="viewDetails(row)">View</el-button>
            <el-button size="small" type="primary" @click="openAdjustDialog(row)">Adjust</el-button>
            <el-dropdown @command="(cmd) => handleAction(cmd, row)">
              <el-button size="small">
                More <el-icon><ArrowDown /></el-icon>
              </el-button>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="edit">Edit</el-dropdown-item>
                  <el-dropdown-item command="delete" divided>Delete</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container">
        <el-pagination
          v-model:current-page="pagination.currentPage"
          v-model:page-size="pagination.pageSize"
          :total="pagination.total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="loadConsumables"
          @current-change="loadConsumables"
        />
      </div>
    </el-card>

    <!-- Add/Edit Dialog -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogMode === 'add' ? 'Add Consumable' : 'Edit Consumable'"
      width="800px"
    >
      <el-form :model="formData" :rules="formRules" ref="formRef" label-width="180px">
        <el-form-item label="Material ID" prop="material_id">
          <el-input v-model="formData.material_id" placeholder="Auto-generated if empty" />
        </el-form-item>
        <el-form-item label="Name" prop="name">
          <el-input v-model="formData.name" />
        </el-form-item>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Manufacturer">
              <el-input v-model="formData.manufacturer" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Catalog Number">
              <el-input v-model="formData.catalog_number" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Lot Number">
              <el-input v-model="formData.lot_number" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Expiration Date">
              <el-date-picker
                v-model="formData.expiration_date"
                type="date"
                style="width: 100%"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Current Stock Level" prop="current_stock_level">
              <el-input-number v-model="formData.current_stock_level" :min="0" :precision="2" style="width: 100%" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Unit of Measure">
              <el-input v-model="formData.unit_of_measure" placeholder="e.g., mL, units, pcs" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Reorder Level">
              <el-input-number v-model="formData.reorder_level" :min="0" :precision="2" style="width: 100%" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Storage Location">
              <el-input v-model="formData.storage_location" placeholder="e.g., Freezer A1, Shelf B2" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="Associated Equipment">
          <el-select
            v-model="formData.equipment_id"
            placeholder="Select equipment that uses this consumable"
            style="width: 100%"
            clearable
          >
            <el-option
              v-for="equipment in availableEquipment"
              :key="equipment?.id"
              :label="equipment?.name"
              :value="equipment?.id"
              v-if="equipment && equipment.id && equipment.name"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Associated Tests">
          <el-select
            v-model="formData.test_ids"
            multiple
            placeholder="Select tests that use this consumable"
            style="width: 100%"
          >
            <el-option
              v-for="test in availableTests"
              :key="test?.id"
              :label="test?.name"
              :value="test?.id"
              v-if="test && test.id && test.name"
            />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">Cancel</el-button>
        <el-button type="primary" @click="saveConsumable">Save</el-button>
      </template>
    </el-dialog>

    <!-- Adjust Stock Dialog -->
    <el-dialog
      v-model="adjustDialogVisible"
      title="Adjust Stock Level"
      width="500px"
    >
      <el-form :model="adjustForm" label-width="150px">
        <el-form-item label="Current Stock">
          <el-input :value="adjustForm.current_stock" disabled />
        </el-form-item>
        <el-form-item label="Adjustment Type" prop="type">
          <el-radio-group v-model="adjustForm.type">
            <el-radio label="add">Add Stock</el-radio>
            <el-radio label="deduct">Deduct Stock</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="Quantity" prop="quantity">
          <el-input-number v-model="adjustForm.quantity" :min="0.01" :precision="2" style="width: 100%" />
        </el-form-item>
        <el-form-item label="Notes">
          <el-input v-model="adjustForm.notes" type="textarea" :rows="3" placeholder="Optional notes about this adjustment" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="adjustDialogVisible = false">Cancel</el-button>
        <el-button type="primary" @click="applyAdjustment">Apply</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import api from '@/utils/axios'

const loading = ref(false)
const consumables = ref([])
const availableTests = ref([])
const availableEquipment = ref([])
const dialogVisible = ref(false)
const adjustDialogVisible = ref(false)
const dialogMode = ref('add')
const formRef = ref(null)
const formData = reactive({
  material_id: '',
  name: '',
  manufacturer: '',
  catalog_number: '',
  lot_number: '',
  expiration_date: null,
  current_stock_level: 0,
  unit_of_measure: 'unit',
  storage_location: '',
  reorder_level: 0,
  equipment_id: null,
  test_ids: []
})
const adjustForm = reactive({
  id: null,
  type: 'add',
  quantity: 0,
  notes: '',
  current_stock: 0
})
const filters = reactive({
  search: '',
  expired: null,
  lowStock: null
})
const pagination = reactive({
  currentPage: 1,
  pageSize: 15,
  total: 0
})
const alerts = reactive({
  lowStock: [],
  expiring: [],
  expired: []
})

const formRules = {
  name: [{ required: true, message: 'Name is required', trigger: 'blur' }],
  current_stock_level: [{ required: true, message: 'Stock level is required', trigger: 'blur' }]
}

onMounted(() => {
  loadConsumables()
  loadAlerts()
  loadTests()
  loadEquipment()
})

const loadConsumables = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.currentPage,
      per_page: pagination.pageSize,
      ...filters
    }
    const response = await api.get('eim/consumables', { params })
    consumables.value = response.data.data
    pagination.total = response.data.total
  } catch (error) {
    ElMessage.error('Failed to load consumables')
  } finally {
    loading.value = false
  }
}

const loadAlerts = async () => {
  try {
    const [expiration, lowStock] = await Promise.all([
      api.get('eim/consumables/alerts/expiration'),
      api.get('eim/consumables/alerts/low-stock')
    ])
    alerts.expiring = expiration.data.critical || []
    alerts.expired = expiration.data.expired || []
    alerts.lowStock = lowStock.data.items || []
  } catch (error) {
    console.error('Failed to load alerts', error)
  }
}

const loadTests = async () => {
  try {
    const response = await api.get('tests')
    console.log('Tests API response:', response.data)
    // Handle different response structures
    let testsData = response.data
    if (response.data && response.data.data) {
      testsData = response.data.data
    }
    // Ensure we have an array and filter out any null/undefined values
    availableTests.value = Array.isArray(testsData) 
      ? testsData.filter(test => test && test.id && test.name)
      : []
    console.log('Filtered tests:', availableTests.value)
  } catch (error) {
    console.error('Failed to load tests', error)
    availableTests.value = []
  }
}

const loadEquipment = async () => {
  try {
    const response = await api.get('eim/equipment')
    console.log('Equipment API response:', response.data)
    const equipmentData = response.data.data || response.data || []
    // Filter out any null/undefined values and ensure proper structure
    availableEquipment.value = equipmentData.filter(equipment => equipment && equipment.id && equipment.name)
    console.log('Filtered equipment:', availableEquipment.value)
  } catch (error) {
    console.error('Failed to load equipment', error)
    availableEquipment.value = []
  }
}

const openAddDialog = () => {
  dialogMode.value = 'add'
  Object.assign(formData, {
    material_id: '',
    name: '',
    manufacturer: '',
    catalog_number: '',
    lot_number: '',
    expiration_date: null,
    current_stock_level: 0,
    unit_of_measure: 'unit',
    storage_location: '',
    reorder_level: 0,
    equipment_id: null,
    test_ids: []
  })
  dialogVisible.value = true
}

const saveConsumable = async () => {
  if (!formRef.value) return
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      try {
        if (dialogMode.value === 'add') {
          await api.post('eim/consumables', formData)
          ElMessage.success('Consumable added successfully')
        } else {
          await api.put(`eim/consumables/${formData.id}`, formData)
          ElMessage.success('Consumable updated successfully')
        }
        dialogVisible.value = false
        loadConsumables()
        loadAlerts()
      } catch (error) {
        ElMessage.error('Failed to save consumable')
      }
    }
  })
}

const openAdjustDialog = (row) => {
  adjustForm.id = row.id
  adjustForm.current_stock = row.current_stock_level
  adjustForm.quantity = 0
  adjustForm.notes = ''
  adjustForm.type = 'add'
  adjustDialogVisible.value = true
}

const applyAdjustment = async () => {
  try {
    const endpoint = adjustForm.type === 'add' ? 'add-stock' : 'deduct-stock'
    await api.post(`eim/consumables/${adjustForm.id}/${endpoint}`, {
      quantity: adjustForm.quantity,
      notes: adjustForm.notes
    })
    ElMessage.success('Stock adjusted successfully')
    adjustDialogVisible.value = false
    loadConsumables()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || 'Failed to adjust stock')
  }
}

const handleAction = async (cmd, row) => {
  if (cmd === 'edit') {
    dialogMode.value = 'edit'
    Object.assign(formData, row)
    dialogVisible.value = true
  } else if (cmd === 'delete') {
    try {
      await ElMessageBox.confirm('Are you sure you want to delete this consumable?', 'Confirm Delete', {
        type: 'warning'
      })
      await api.delete(`eim/consumables/${row.id}`)
      ElMessage.success('Consumable deleted successfully')
      loadConsumables()
    } catch (error) {
      if (error !== 'cancel') {
        ElMessage.error('Failed to delete consumable')
      }
    }
  }
}

const viewDetails = (row) => {
  // TODO: Implement details view
  ElMessage.info('Details view coming soon')
}

const filterByLowStock = () => {
  filters.lowStock = true
  filters.expired = null
  loadConsumables()
}

const filterByExpiring = () => {
  filters.expired = false
  filters.lowStock = null
  loadConsumables()
}

const filterByExpired = () => {
  filters.expired = true
  filters.lowStock = null
  loadConsumables()
}

const refreshData = () => {
  loadConsumables()
  loadAlerts()
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}

const getExpirationClass = (row) => {
  if (!row.expiration_date) return ''
  const daysUntil = Math.floor((new Date(row.expiration_date) - new Date()) / (1000 * 60 * 60 * 24))
  if (daysUntil < 0) return 'text-danger'
  if (daysUntil <= 30) return 'text-danger'
  if (daysUntil <= 60) return 'text-warning'
  return ''
}

const getStockClass = (row) => {
  if (row.current_stock_level <= row.reorder_level) return 'text-warning'
  return ''
}
</script>

<style scoped>
.consumables-management {
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

.header-actions {
  display: flex;
  gap: 10px;
}

.alerts-section {
  margin-bottom: 20px;
}

.alert-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header {
  font-weight: 600;
}

.filters {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.text-danger {
  color: var(--color-danger);
}

.text-warning {
  color: var(--color-warning);
}

.text-success {
  color: var(--color-success);
}

.mb-3 {
  margin-bottom: 12px;
}

.mb-4 {
  margin-bottom: 16px;
}
</style>
