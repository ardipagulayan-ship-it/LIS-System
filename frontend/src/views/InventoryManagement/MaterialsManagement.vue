<template>
  <div class="materials-management">
    <div class="page-header">
      <h2 class="page-title">Material Master (Catalog)</h2>
      <div class="header-actions">
        <el-button type="primary" @click="openAddDialog">
          <el-icon><Plus /></el-icon> Add Material
        </el-button>
        <el-button @click="loadMaterials">
          <el-icon><Refresh /></el-icon> Refresh
        </el-button>
      </div>
    </div>

    <!-- Filters -->
    <el-card class="filters-card">
      <el-row :gutter="20">
        <el-col :span="6">
          <el-select
            v-model="filters.material_type"
            placeholder="Material Type"
            clearable
            @change="loadMaterials"
          >
            <el-option label="All Types" value="" />
            <el-option label="Reagents" value="reagent" />
            <el-option label="Controls" value="control" />
            <el-option label="Calibrators" value="calibrator" />
            <el-option label="Consumables" value="consumable" />
            <el-option label="Equipment" value="equipment" />
          </el-select>
        </el-col>
        <el-col :span="6">
          <el-select
            v-model="filters.is_active"
            placeholder="Status"
            clearable
            @change="loadMaterials"
          >
            <el-option label="All Status" value="" />
            <el-option label="Active" :value="true" />
            <el-option label="Inactive" :value="false" />
          </el-select>
        </el-col>
        <el-col :span="8">
          <el-input
            v-model="filters.search"
            placeholder="Search by name, catalog ID, manufacturer..."
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

    <!-- Materials Table -->
    <el-card class="table-card">
      <el-table
        :data="materials"
        v-loading="loading"
        stripe
        @sort-change="handleSortChange"
      >
        <el-table-column prop="catalog_id" label="Catalog ID" width="200" sortable />
        <el-table-column prop="generic_name" label="Name" min-width="200" sortable />
        <el-table-column prop="manufacturer" label="Manufacturer" width="150" sortable />
        <el-table-column prop="catalog_number" label="Catalog Number" width="150" />
        <el-table-column prop="material_type" label="Type" width="120">
          <template #default="{ row }">
            <el-tag :type="getTypeTagType(row.material_type)">
              {{ getTypeLabel(row.material_type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="standard_unit_of_measure" label="Unit" width="100" />
        <el-table-column prop="shelf_life_days" label="Shelf Life" width="120">
          <template #default="{ row }">
            {{ row.shelf_life_days ? `${row.shelf_life_days} days` : 'N/A' }}
          </template>
        </el-table-column>
        <el-table-column label="Stock Status" width="150">
          <template #default="{ row }">
            <div class="stock-info">
              <div>{{ getTotalStock(row) }}</div>
              <div class="stock-detail">{{ getActiveLots(row) }} active lots</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="is_active" label="Status" width="100">
          <template #default="{ row }">
            <el-tag :type="row.is_active ? 'success' : 'danger'">
              {{ row.is_active ? 'Active' : 'Inactive' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="250" align="center" fixed="right">
          <template #default="{ row }">
            <el-button-group>
            <el-button size="small" @click="viewMaterial(row)"><el-icon><View /></el-icon> View</el-button>
            <el-button size="small" type="primary" @click="editMaterial(row)"><el-icon><Edit /></el-icon> Edit</el-button>
            <el-button size="small" type="danger" @click="deleteMaterial(row)"><el-icon><Delete /></el-icon> Delete</el-button>
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
          @size-change="loadMaterials"
          @current-change="loadMaterials"
        />
      </div>
    </el-card>

    <!-- Add/Edit Modal -->
    <AddMaterialModal
      :visible="dialogVisible"
      :material="selectedMaterial"
      :available-tests="availableTests"
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
import AddMaterialModal from '@/components/modals/inventory/AddMaterialModal.vue'

const materials = ref([])
const loading = ref(false)
const dialogVisible = ref(false)
const selectedMaterial = ref(null)

const pagination = reactive({
  current_page: 1,
  per_page: 20,
  total: 0
})

const filters = reactive({
  material_type: '',
  is_active: '',
  search: ''
})

const availableTests = ref([])
const availableVendors = ref([])

onMounted(() => {
  loadMaterials()
  loadTests()
  loadVendors()
})

const loadMaterials = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.current_page,
      per_page: pagination.per_page
    }
    
    // Only add filters that have actual values
    if (filters.material_type) params.material_type = filters.material_type
    if (filters.is_active !== '') params.is_active = filters.is_active
    if (filters.search) params.search = filters.search
    
    const response = await api.get('eim/imm/materials', { params })
    materials.value = response.data.data || response.data
    pagination.total = response.data.total || 0
  } catch (error) {
    console.error('Failed to load materials', error)
    ElMessage.error('Failed to load materials')
  } finally {
    loading.value = false
  }
}

const loadTests = async () => {
  try {
    const response = await api.get('tests')
    // Handle different response structures
    let testsData = response.data
    if (response.data && response.data.data) {
      testsData = response.data.data
    }
    // Ensure we have an array and filter out any null/undefined values
    availableTests.value = Array.isArray(testsData) 
      ? testsData.filter(test => test && test.id && test.name)
      : []
  } catch (error) {
    console.error('Failed to load tests', error)
    availableTests.value = []
  }
}

const loadVendors = async () => {
  try {
    const response = await api.get('eim/imm/vendors')
    availableVendors.value = (response.data.data || response.data || []).filter(vendor => vendor && vendor.id && vendor.vendor_name)
  } catch (error) {
    console.error('Failed to load vendors', error)
  }
}

const debouncedSearch = debounce(() => {
  loadMaterials()
}, 500)

const clearFilters = () => {
  Object.assign(filters, {
    material_type: '',
    is_active: '',
    search: ''
  })
  loadMaterials()
}

const handleSortChange = ({ prop, order }) => {
  // Implement sorting if needed
  loadMaterials()
}

const openAddDialog = () => {
  selectedMaterial.value = null
  dialogVisible.value = true
}

const editMaterial = (material) => {
  selectedMaterial.value = material
  dialogVisible.value = true
}

const handleSave = () => {
  loadMaterials()
}

const viewMaterial = (material) => {
  // Navigate to material detail view
  console.log('View material:', material)
}

const deleteMaterial = async (material) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete "${material.generic_name}"?`,
      'Confirm Delete',
      {
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        type: 'warning'
      }
    )
    
    await api.delete(`eim/imm/materials/${material.id}`)
    ElMessage.success('Material deleted successfully')
    loadMaterials()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Failed to delete material', error)
      ElMessage.error('Failed to delete material')
    }
  }
}

const getTypeLabel = (type) => {
  const labels = {
    reagent: 'Reagent',
    control: 'Control',
    calibrator: 'Calibrator',
    consumable: 'Consumable',
    equipment: 'Equipment'
  }
  return labels[type] || type
}

const getTypeTagType = (type) => {
  const types = {
    reagent: 'primary',
    control: 'success',
    calibrator: 'warning',
    consumable: 'info',
    equipment: 'danger'
  }
  return types[type] || ''
}

const getTotalStock = (material) => {
  if (!material.lots) return '0'
  return material.lots.reduce((sum, lot) => sum + (lot.current_stock_level || 0), 0)
}

const getActiveLots = (material) => {
  if (!material.lots) return 0
  return material.lots.filter(lot => lot.status === 'active').length
}
</script>

<style scoped>
.materials-management {
  width: 100%;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-xl);
}

.page-title {
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
  margin: 0;
}

.filters-card {
  margin-bottom: var(--spacing-xl);
}

.table-card {
  margin-bottom: var(--spacing-xl);
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
  margin-top: var(--spacing-xl);
}
</style>
