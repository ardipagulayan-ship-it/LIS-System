<template>
  <div class="lots-management">
    <div class="page-header">
      <h2 class="page-title">Lot Management</h2>
      <div class="header-actions">
        <el-button type="primary" @click="openAddDialog">
          <el-icon><Plus /></el-icon> Add Lot
        </el-button>
        <el-button @click="loadLots">
          <el-icon><Refresh /></el-icon> Refresh
        </el-button>
      </div>
    </div>

    <!-- Filters -->
    <el-card class="filters-card">
      <el-row :gutter="20">
        <el-col :span="6">
          <el-select
            v-model="filters.status"
            placeholder="Status"
            clearable
            @change="loadLots"
          >
            <el-option label="All Status" value="" />
            <el-option label="Quarantine" value="quarantine" />
            <el-option label="Active" value="active" />
            <el-option label="Depleted" value="depleted" />
            <el-option label="Expired" value="expired" />
          </el-select>
        </el-col>
        <el-col :span="6">
          <el-select
            v-model="filters.material_id"
            placeholder="Material"
            clearable
            @change="loadLots"
          >
            <el-option label="All Materials" value="" />
            <template v-for="material in availableMaterials" :key="material?.id">
              <el-option
                v-if="material && material.id && material.generic_name"
                :label="material.generic_name"
                :value="material.id"
              />
            </template>
          </el-select>
        </el-col>
        <el-col :span="8">
          <el-input
            v-model="filters.search"
            placeholder="Search by lot number, material name..."
            @input="debouncedSearch"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
        </el-col>
        <el-col :span="4">
          <el-button @click="clearFilters">Clear Filters</el-button>
        </el-col>
      </el-row>
    </el-card>

    <!-- Lots Table -->
    <el-card class="table-card">
      <el-table
        :data="lots"
        v-loading="loading"
        stripe
        @sort-change="handleSortChange"
      >
        <el-table-column prop="lot_id" label="Lot ID" width="200" sortable />
        <el-table-column prop="lot_number" label="Lot Number" width="150" sortable />
        <el-table-column label="Material" min-width="200">
          <template #default="{ row }">
            {{ row.material?.generic_name || 'N/A' }}
          </template>
        </el-table-column>
        <el-table-column prop="expiration_date" label="Expiration" width="120" sortable>
          <template #default="{ row }">
            <span :class="getExpirationClass(row.expiration_date)">
              {{ formatDate(row.expiration_date) }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="Stock Level" width="120">
          <template #default="{ row }">
            <div class="stock-info">
              <div>{{ row.current_stock_level }}</div>
              <div class="stock-detail">/ {{ row.initial_quantity }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="status" label="Status" width="120">
          <template #default="{ row }">
            <el-tag :type="getStatusTagType(row.status)">
              {{ getStatusLabel(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Vendor" width="150">
          <template #default="{ row }">
            {{ row.vendor?.vendor_name || 'N/A' }}
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="250" align="center" fixed="right">
          <template #default="{ row }">
            <el-button-group>
            <el-button size="small" @click="editLot(row)"><el-icon><Edit /></el-icon> Edit</el-button>
            <el-button 
              size="small" 
              type="success" 
              @click="releaseFromQuarantine(row)"
              v-if="row.status === 'quarantine'"
            >
              Release
            </el-button>
            <el-button size="small" type="danger" @click="deleteLot(row)"><el-icon><Delete /></el-icon> Delete</el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>

      <!-- Pagination -->
      <div class="pagination-container">
        <el-pagination
          v-model:current-page="pagination.current_page"
          v-model:page-size="pagination.per_page"
          :page-sizes="[10, 20, 50, 100]"
          :total="pagination.total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="loadLots"
          @current-change="loadLots"
        />
      </div>
    </el-card>

    <!-- Add/Edit Modal -->
    <AddLotModal
      :visible="dialogVisible"
      :lot="selectedLot"
      :available-materials="availableMaterials"
      :available-vendors="availableVendors"
      @close="dialogVisible = false"
      @save="handleSave"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { debounce } from 'lodash-es'
import api from '@/utils/axios'
import AddLotModal from '@/components/modals/inventory/AddLotModal.vue'

const lots = ref([])
const loading = ref(false)
const dialogVisible = ref(false)
const selectedLot = ref(null)

const pagination = reactive({
  current_page: 1,
  per_page: 20,
  total: 0
})

const filters = reactive({
  status: '',
  material_id: '',
  search: ''
})

const availableMaterials = ref([])
const availableVendors = ref([])
const loadingMaterials = ref(false)
const loadingVendors = ref(false)

onMounted(() => {
  loadLots()
  loadMaterials()
  loadVendors()
})

const loadLots = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.current_page,
      per_page: pagination.per_page
    }
    
    // Only add filters that have actual values
    if (filters.status) params.status = filters.status
    if (filters.material_id) params.material_id = filters.material_id
    if (filters.search) params.search = filters.search
    
    const response = await api.get('eim/imm/lots', { params })
    lots.value = response.data.data || response.data
    pagination.total = response.data.total || 0
  } catch (error) {
    console.error('Failed to load lots', error)
    ElMessage.error('Failed to load lots')
  } finally {
    loading.value = false
  }
}

const loadMaterials = async () => {
  loadingMaterials.value = true
  try {
    const response = await api.get('eim/imm/materials')
    
    // Handle different response structures
    let materialsData = response.data
    if (response.data && response.data.data) {
      materialsData = response.data.data
    }
    
    // Ensure we have an array and filter out any null/undefined values
    availableMaterials.value = Array.isArray(materialsData) 
      ? materialsData.filter(material => material && material.id && material.generic_name)
      : []
  } catch (error) {
    console.error('Failed to load materials', error)
    ElMessage.error('Failed to load materials')
  } finally {
    loadingMaterials.value = false
  }
}

const loadVendors = async () => {
  loadingVendors.value = true
  try {
    const response = await api.get('eim/imm/vendors')
    
    // Handle different response structures
    let vendorsData = response.data
    if (response.data && response.data.data) {
      vendorsData = response.data.data
    }
    
    // Ensure we have an array and filter out any null/undefined values
    availableVendors.value = Array.isArray(vendorsData) 
      ? vendorsData.filter(vendor => vendor && vendor.id && vendor.vendor_name)
      : []
  } catch (error) {
    console.error('Failed to load vendors', error)
    ElMessage.error('Failed to load vendors')
  } finally {
    loadingVendors.value = false
  }
}

const debouncedSearch = debounce(() => {
  loadLots()
}, 500)

const clearFilters = () => {
  Object.assign(filters, {
    status: '',
    material_id: '',
    search: ''
  })
  loadLots()
}

const handleSortChange = ({ prop, order }) => {
  loadLots()
}

const openAddDialog = () => {
  selectedLot.value = null
  dialogVisible.value = true
}

const editLot = (lot) => {
  selectedLot.value = lot
  dialogVisible.value = true
}

const handleSave = () => {
  dialogVisible.value = false
  loadLots()
}

const releaseFromQuarantine = async (lot) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to release "${lot.lot_number}" from quarantine?`,
      'Confirm Release',
      {
        confirmButtonText: 'Release',
        cancelButtonText: 'Cancel',
        type: 'warning'
      }
    )
    
    await api.post(`eim/imm/lots/${lot.id}/release-quarantine`)
    ElMessage.success('Lot released from quarantine successfully')
    loadLots()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Failed to release lot', error)
      ElMessage.error('Failed to release lot from quarantine')
    }
  }
}

const saveLot = async () => {
  if (!formRef.value) return
  
  await formRef.value.validate()
  
  try {
    await api.post('eim/imm/lots', formData)
    ElMessage.success('Lot created successfully')
    dialogVisible.value = false
    loadLots()
  } catch (error) {
    console.error('Failed to save lot', error)
    ElMessage.error('Failed to save lot')
  }
}

const deleteLot = async (lot) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete lot "${lot.lot_number}"?`,
      'Confirm Delete',
      {
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        type: 'warning'
      }
    )
    
    await api.delete(`eim/imm/lots/${lot.id}`)
    ElMessage.success('Lot deleted successfully')
    loadLots()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Failed to delete lot', error)
      ElMessage.error('Failed to delete lot')
    }
  }
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}

const getExpirationClass = (expirationDate) => {
  if (!expirationDate) return ''
  
  const today = new Date()
  const expDate = new Date(expirationDate)
  const diffTime = expDate - today
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays < 0) return 'expired'
  if (diffDays <= 30) return 'critical'
  if (diffDays <= 60) return 'warning'
  return ''
}

const getStatusLabel = (status) => {
  const labels = {
    quarantine: 'Quarantine',
    active: 'Active',
    depleted: 'Depleted',
    on_hold: 'On Hold',
    expired: 'Expired',
    disposed: 'Disposed'
  }
  return labels[status] || status
}

const getStatusTagType = (status) => {
  const types = {
    quarantine: 'warning',
    active: 'success',
    depleted: 'info',
    on_hold: 'warning',
    expired: 'danger',
    disposed: 'danger'
  }
  return types[status] || ''
}
</script>

<style scoped>
.lots-management {
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

.filters-card {
  margin-bottom: 20px;
}

.table-card {
  margin-bottom: 20px;
}

.stock-info {
  text-align: center;
}

.stock-detail {
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
}

.pagination-container {
  display: flex;
  justify-content: right;
  margin-top: 20px;
}

.expired {
  color: var(--color-danger);
  font-weight: var(--font-weight-bold);
}

.critical {
  color: var(--color-warning);
  font-weight: var(--font-weight-bold);
}

.warning {
  color: var(--color-text-tertiary);
}
</style>
