<template>
  <div class="vendors-management">
    <div class="page-header">
      <h2 class="page-title">Vendor Management</h2>
      <div class="header-actions">
        <el-button type="primary" @click="openAddDialog">
          <el-icon><Plus /></el-icon> Add Vendor
        </el-button>
        <el-button @click="loadVendors">
          <el-icon><Refresh /></el-icon> Refresh
        </el-button>
      </div>
    </div>

    <!-- Filters -->
    <el-card class="filters-card">
      <el-row :gutter="20">
        <el-col :span="6">
          <el-select
            v-model="filters.is_active"
            placeholder="Status"
            clearable
            @change="loadVendors"
          >
            <el-option label="All Status" value="" />
            <el-option label="Active" :value="true" />
            <el-option label="Inactive" :value="false" />
          </el-select>
        </el-col>
        <el-col :span="14">
          <el-input
            v-model="filters.search"
            placeholder="Search by vendor name, contact person, email..."
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

    <!-- Vendors Table -->
    <el-card class="table-card">
      <el-table
        :data="vendors"
        v-loading="loading"
        stripe
        @sort-change="handleSortChange"
      >
        <el-table-column prop="vendor_id" label="Vendor ID" width="250" sortable />
        <el-table-column prop="vendor_name" label="Vendor Name" min-width="200" sortable />
        <el-table-column prop="contact_person" label="Contact Person" width="150" />
        <el-table-column prop="email" label="Email" width="200" />
        <el-table-column prop="phone" label="Phone" width="150" />
        <el-table-column label="Address" min-width="200">
          <template #default="{ row }">
            {{ getFullAddress(row) }}
          </template>
        </el-table-column>
        <el-table-column prop="currency" label="Currency" width="100" />
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
              <el-button size="small" @click="viewVendor(row)"><el-icon><View /></el-icon> View</el-button>
              <el-button size="small" type="primary" @click="editVendor(row)"><el-icon><Edit /></el-icon> Edit</el-button>
              <el-button size="small" type="danger" @click="deleteVendor(row)"><el-icon><Delete /></el-icon> Delete</el-button>
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
          @size-change="loadVendors"
          @current-change="loadVendors"
        />
      </div>
    </el-card>

    <!-- Add/Edit Modal -->
    <AddVendorModal
      :visible="dialogVisible"
      :vendor="selectedVendor"
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
import AddVendorModal from '@/components/modals/inventory/AddVendorModal.vue'

const vendors = ref([])
const loading = ref(false)
const dialogVisible = ref(false)
const selectedVendor = ref(null)

const pagination = reactive({
  current_page: 1,
  per_page: 20,
  total: 0
})

const filters = reactive({
  is_active: '',
  search: ''
})

onMounted(() => {
  loadVendors()
})

const loadVendors = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.current_page,
      per_page: pagination.per_page
    }
    
    // Only add filters that have actual values
    if (filters.is_active !== '') params.is_active = filters.is_active
    if (filters.search) params.search = filters.search
    
    const response = await api.get('eim/imm/vendors', { params })
    vendors.value = response.data.data || response.data
    pagination.total = response.data.total || 0
  } catch (error) {
    console.error('Failed to load vendors', error)
    ElMessage.error('Failed to load vendors')
  } finally {
    loading.value = false
  }
}

const debouncedSearch = debounce(() => {
  loadVendors()
}, 500)

const clearFilters = () => {
  Object.assign(filters, {
    is_active: '',
    search: ''
  })
  loadVendors()
}

const handleSortChange = ({ prop, order }) => {
  loadVendors()
}

const openAddDialog = () => {
  selectedVendor.value = null
  dialogVisible.value = true
}

const editVendor = (vendor) => {
  selectedVendor.value = vendor
  dialogVisible.value = true
}

const viewVendor = (vendor) => {
  console.log('View vendor:', vendor)
}

const handleSave = () => {
  dialogVisible.value = false
  loadVendors()
}

const deleteVendor = async (vendor) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete "${vendor.vendor_name}"?`,
      'Confirm Delete',
      {
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        type: 'warning'
      }
    )
    
    await api.delete(`eim/imm/vendors/${vendor.id}`)
    ElMessage.success('Vendor deleted successfully')
    loadVendors()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Failed to delete vendor', error)
      ElMessage.error('Failed to delete vendor')
    }
  }
}

const getFullAddress = (vendor) => {
  const parts = [vendor.address, vendor.city, vendor.state, vendor.postal_code, vendor.country]
  return parts.filter(part => part).join(', ') || 'N/A'
}
</script>

<style scoped>
.vendors-management {
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

.pagination-container {
  display: flex;
  justify-content: right;
  margin-top: 20px;
}
</style>
