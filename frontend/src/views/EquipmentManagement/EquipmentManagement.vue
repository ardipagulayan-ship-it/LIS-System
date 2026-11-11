<template>
  <div class="equipment-management">
    <div class="page-header">
      <div class="page-header-content">
        <h2 class="page-title">Equipment Management</h2> <span>Manage equipment and their status</span>
      </div>
      <div class="header-actions">
        <el-button type="primary" @click="openAddModal">
          <el-icon><Plus /></el-icon> Add Equipment
        </el-button>
        <el-button type="success" @click="showBulkImportModal = true">
          <el-icon><Upload /></el-icon> Bulk Import
        </el-button>
        <el-button type="info" @click="exportEquipment">
          <el-icon><Download /></el-icon> Export
        </el-button>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
      <el-card class="mb-4">
        <template #header>
          <div class="card-header">
            <span>Filters</span>
          </div>
        </template>
        <el-row :gutter="20">
          <el-col :span="6">
            <el-input 
              v-model="filters.search" 
              placeholder="Search by asset ID, manufacturer, model..."
              @input="debounceSearch"
              clearable
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>
          </el-col>
          <el-col :span="4">
            <el-select v-model="filters.status" placeholder="All Status" @change="fetchEquipment" clearable>
              <el-option label="In Service" value="in_service" />
              <el-option label="Under Maintenance" value="under_maintenance" />
              <el-option label="Calibration Due" value="calibration_due" />
              <el-option label="Out of Service" value="out_of_service" />
              <el-option label="Retired" value="retired" />
            </el-select>
          </el-col>
          <el-col :span="4">
            <el-select v-model="filters.location_lab" placeholder="All Locations" @change="fetchEquipment" clearable>
              <el-option 
                v-for="location in locations" 
                :key="location" 
                :label="location"
                :value="location"
              />
            </el-select>
          </el-col>
          <el-col :span="4">
            <el-select v-model="filters.overdue_only" @change="fetchEquipment">
              <el-option label="All Equipment" :value="false" />
              <el-option label="Overdue Only" :value="true" />
            </el-select>
          </el-col>
          <el-col :span="6">
            <el-button-group>
              <el-button @click="clearFilters">
                <el-icon><Close /></el-icon> Clear
              </el-button>
              <el-button type="primary" @click="fetchEquipment">
                <el-icon><Search /></el-icon> Search
              </el-button>
            </el-button-group>
          </el-col>
        </el-row>
      </el-card>
    </div>

    <!-- Equipment Table -->
     <div class="equipment-table">
      <el-card >
        <template #header>
          <div class="card-header">
            <span>Equipment List ({{ pagination.total }} items)</span>
          </div>
        </template>
        <el-table
          :data="equipmentList"
          v-loading="loading"
          stripe
        >
          <el-table-column prop="unique_asset_id" label="Asset ID" width="200">
            <template #default="{ row }">
              <div>
                <strong>{{ row.unique_asset_id }}</strong>
                <div class="text-muted small">{{ row.serial_number }}</div>
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="manufacturer" label="Manufacturer" width="250" />
          <el-table-column prop="model" label="Model" width="250" />
          <el-table-column prop="serial_number" label="Serial Number" width="250" />
          <el-table-column prop="location_lab" label="Location" width="250">
            <template #default="{ row }">
              <div>
                {{ row.location_lab }}
                <div v-if="row.location_bench" class="text-muted small">
                  Bench: {{ row.location_bench }}
                </div>
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="current_status" label="Status" width="250">
            <template #default="{ row }">
              <el-tag :type="getStatusTagType(row.current_status)">
                {{ getStatusLabel(row.current_status) }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="next_maintenance_date" label="Next Maintenance" width="200">
            <template #default="{ row }">
              <div v-if="row.next_maintenance_date">
                {{ formatDate(row.next_maintenance_date) }}
                <div :class="getMaintenanceUrgencyClass(row.next_maintenance_date)" class="small">
                  {{ getMaintenanceUrgency(row.next_maintenance_date) }}
                </div>
              </div>
              <span v-else class="text-muted">Not scheduled</span>
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="200" align="center" fixed="right">
            <template #default="{ row }">
              <el-button-group>
                <el-button size="small" @click="viewEquipment(row)">
                  <el-icon><View /></el-icon>
                </el-button>
                <el-button size="small" type="warning" @click="editEquipment(row)">
                  <el-icon><Edit /></el-icon>
                </el-button>
                <el-button size="small" type="info" @click="updateStatus(row)">
                  <el-icon><Refresh /></el-icon>
                </el-button>
                <el-button size="small" type="success" @click="generateBarcode(row)">
                  <el-icon><QrCode /></el-icon>
                </el-button>
              </el-button-group>
            </template>
          </el-table-column>
        </el-table>

        <!-- Pagination -->
        <div class="pagination-section">
          <el-pagination
            v-model:current-page="pagination.current_page"
            :page-size="pagination.per_page"
            :total="pagination.total"
            layout="total, sizes, prev, pager, next, jumper"
            @current-change="changePage"
            @size-change="handleSizeChange"
          />
        </div>
      </el-card>
    </div>

    <!-- New Professional Add/Edit Equipment Modal Component -->
    <AddEquipmentModal
      :visible="showEquipmentModal"
      :equipment="selectedEquipment"
      :test-categories="testCategories"
      @close="handleCloseEquipmentModal"
      @save="handleSaveEquipment"
    />

    <!-- Status Update Modal -->
    <StatusUpdateModal
      :visible="showStatusModal"
      :equipment="selectedEquipment"
      @close="handleCloseStatusModal"
      @save="handleStatusUpdated"
    />

    <!-- Bulk Import Modal -->
    <BulkImportModal
      :visible="showBulkImportModal"
      @close="handleCloseBulkImportModal"
      @save="handleBulkImportCompleted"
    />
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/utils/axios'
import AddEquipmentModal from '@/components/modals/equipment/AddEquipmentModal.vue'
import StatusUpdateModal from '@/components/modals/equipment/StatusUpdateModal.vue'
import BulkImportModal from '@/components/modals/equipment/BulkImportModal.vue'
import { 
  Plus, 
  Upload, 
  Download, 
  Search, 
  Close, 
  View, 
  Edit, 
  Refresh, 
  Tickets
} from '@element-plus/icons-vue'

export default {
  name: 'EquipmentManagement',
  components: {
    AddEquipmentModal,
    StatusUpdateModal,
    BulkImportModal
  },
  setup() {
    const router = useRouter()
    
    // State
    const equipmentList = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const locations = ref([])
    const testCategories = ref([])
    
    // Modals
    const showEquipmentModal = ref(false)
    const showStatusModal = ref(false)
    const showBulkImportModal = ref(false)
    
    // Forms
    const equipmentForm = reactive({
      unique_asset_id: '',
      manufacturer: '',
      model: '',
      serial_number: '',
      purchase_date: '',
      warranty_expiration_date: '',
      location_lab: '',
      location_bench: '',
      current_status: 'in_service',
      test_category_id: null,
      sop_operation_manual_link: '',
      cycle_count: 0,
      runtime_hours: 0
    })
    
    // Filters
    const filters = reactive({
      search: '',
      status: '',
      location_lab: '',
      overdue_only: false
    })
    
    // Pagination
    const pagination = ref({
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
      from: 0,
      to: 0
    })
    
    // Selected equipment
    const selectedEquipment = ref(null)
    
    // Computed
    const visiblePages = computed(() => {
      const pages = []
      const current = pagination.value.current_page
      const last = pagination.value.last_page
      
      let start = Math.max(1, current - 2)
      let end = Math.min(last, current + 2)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    })
    
    // Methods
    const fetchEquipment = async () => {
      console.log('=== fetchEquipment called ===')
      try {
        loading.value = true
        console.log('Fetching equipment with params:', {
          page: pagination.value.current_page,
          per_page: pagination.value.per_page,
          ...filters
        })
        
        const params = {
          page: pagination.value.current_page,
          per_page: pagination.value.per_page,
          ...filters
        }
        
        const response = await api.get('eim/equipment', { params })
        console.log('Equipment API response:', response.data)
        console.log('Response data array:', response.data.data)
        console.log('Response data length:', response.data.data?.length)
        
        // Ensure we're setting the data correctly
        if (response.data && response.data.data) {
          equipmentList.value = response.data.data
          console.log('Equipment list set to:', equipmentList.value)
        } else {
          console.error('Invalid response structure:', response.data)
          equipmentList.value = []
        }
        
        pagination.value = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page,
          total: response.data.total,
          from: response.data.from,
          to: response.data.to
        }
        
        console.log('Equipment list updated:', equipmentList.value.length, 'items')
        console.log('Final equipmentList.value:', equipmentList.value)
      } catch (error) {
        console.error('Error fetching equipment:', error)
        console.error('Error details:', error.response?.data)
      } finally {
        loading.value = false
      }
    }
    
    const fetchLocations = async () => {
      try {
        const response = await api.get('eim/equipment')
        const locationsSet = new Set()
        response.data.data.forEach(equipment => {
          if (equipment.location_lab) {
            locationsSet.add(equipment.location_lab)
          }
        })
        locations.value = Array.from(locationsSet)
      } catch (error) {
        console.error('Error fetching locations:', error)
      }
    }
    
    const fetchTestCategories = async () => {
      try {
        const response = await api.get('/test-categories')
        testCategories.value = Array.isArray(response.data) ? response.data : (response.data.data || [])
      } catch (error) {
        console.error('Error fetching test categories:', error)
      }
    }
    
    const debounceSearch = (() => {
      let timeout
      return () => {
        clearTimeout(timeout)
        timeout = setTimeout(() => {
          fetchEquipment()
        }, 500)
      }
    })()
    
    const clearFilters = () => {
      filters.search = ''
      filters.status = ''
      filters.location_lab = ''
      filters.overdue_only = false
      fetchEquipment()
    }
    
    const changePage = (page) => {
      if (page >= 1 && page <= pagination.value.last_page) {
        pagination.value.current_page = page
        fetchEquipment()
      }
    }
    
    const handleSizeChange = (size) => {
      pagination.value.per_page = size
      pagination.value.current_page = 1
      fetchEquipment()
    }
    
    const openAddModal = () => {
      selectedEquipment.value = null
      showEquipmentModal.value = true
    }

    const handleCloseEquipmentModal = () => {
      showEquipmentModal.value = false
      selectedEquipment.value = null
    }

    const handleSaveEquipment = async (equipment) => {
      showEquipmentModal.value = false
      selectedEquipment.value = null
      fetchEquipment()
    }
    
    const handleCloseStatusModal = () => {
      showStatusModal.value = false
      selectedEquipment.value = null
    }

    const handleStatusUpdated = () => {
      showStatusModal.value = false
      selectedEquipment.value = null
      fetchEquipment()
    }

    const handleCloseBulkImportModal = () => {
      showBulkImportModal.value = false
    }

    const handleBulkImportCompleted = () => {
      showBulkImportModal.value = false
      fetchEquipment()
    }
    
    const viewEquipment = (equipment) => {
      router.push(`/equipment-management/equipment/${equipment.id}`)
    }
    
    const editEquipment = (equipment) => {
      selectedEquipment.value = equipment
      showEquipmentModal.value = true
    }
    
    const updateStatus = (equipment) => {
      selectedEquipment.value = equipment
      showStatusModal.value = true
    }
    
    const generateBarcode = async (equipment) => {
      try {
        const response = await api.get(`eim/equipment/${equipment.id}/barcode`)
        // Handle barcode generation
        console.log('Barcode data:', response.data)
      } catch (error) {
        console.error('Error generating barcode:', error)
      }
    }
    
    const exportEquipment = () => {
      // Implement export functionality
      console.log('Export equipment functionality')
    }
    
    const getStatusLabel = (status) => {
      const labels = {
        in_service: 'In Service',
        under_maintenance: 'Under Maintenance',
        calibration_due: 'Calibration Due',
        out_of_service: 'Out of Service',
        retired: 'Retired'
      }
      return labels[status] || status
    }
    
    const getStatusTagType = (status) => {
      const types = {
        in_service: 'success',
        under_maintenance: 'warning',
        calibration_due: 'danger',
        out_of_service: 'info',
        retired: 'dark'
      }
      return types[status] || 'info'
    }
    
    const getMaintenanceUrgency = (date) => {
      const maintenanceDate = new Date(date)
      const today = new Date()
      const diffTime = maintenanceDate - today
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      
      if (diffDays < 0) return `${Math.abs(diffDays)} days overdue`
      if (diffDays === 0) return 'Due today'
      if (diffDays <= 7) return `${diffDays} days remaining`
      return `${diffDays} days remaining`
    }
    
    const getMaintenanceUrgencyClass = (date) => {
      const maintenanceDate = new Date(date)
      const today = new Date()
      const diffTime = maintenanceDate - today
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      
      if (diffDays < 0) return 'text-danger'
      if (diffDays <= 7) return 'text-warning'
      return 'text-success'
    }
    
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString()
    }
    
    onMounted(() => {
      console.log('EquipmentManagement component mounted, fetching data...')
      console.log('Auth token:', localStorage.getItem('token') ? 'Present' : 'Missing')
      console.log('Initial equipmentList:', equipmentList.value)
      console.log('Initial pagination:', pagination.value)
      console.log('Initial filters:', filters)
      
      // Add a small delay to ensure everything is initialized
      setTimeout(() => {
        console.log('Starting fetchEquipment after delay...')
        console.log('About to call fetchEquipment with params:', {
          page: pagination.value.current_page,
          per_page: pagination.value.per_page,
          ...filters
        })
        fetchEquipment()
        fetchLocations()
        fetchTestCategories()
      }, 100)
    })
    
    return {
      equipmentList,
      loading,
      saving,
      locations,
      testCategories,
      showEquipmentModal,
      showStatusModal,
      showBulkImportModal,
      equipmentForm,
      filters,
      pagination,
      selectedEquipment,
      visiblePages,
      fetchEquipment,
      clearFilters,
      changePage,
      openAddModal,
      handleCloseEquipmentModal,
      handleSaveEquipment,
      handleCloseStatusModal,
      handleStatusUpdated,
      handleCloseBulkImportModal,
      handleBulkImportCompleted,
      viewEquipment,
      editEquipment,
      updateStatus,
      generateBarcode,
      exportEquipment,
      getStatusLabel,
      getStatusTagType,
      getMaintenanceUrgency,
      getMaintenanceUrgencyClass,
      formatDate,
      debounceSearch,
      handleSizeChange
    }
  }
}
</script>

<style scoped>
.equipment-management {
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
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
}

.header-actions {
  display: flex;
  gap: var(--spacing-md);
}

.mb-4 {
  margin-bottom: 1rem;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.filters-section {
  margin-bottom: 30px;
}

.equipment-table {
  margin-bottom: 20px;
}

.action-buttons {
  display: flex;
  gap: 5px;
}

.pagination-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 20px;
}

.pagination {
  margin-bottom: 0;
}

.pagination-info {
  color: var(--color-text-secondary);
  font-size: var(--font-size-sm);
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

.text-muted {
  color: var(--color-text-tertiary) !important;
}

.small {
  font-size: var(--font-size-xs);
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: var(--spacing-md);
  }
  
  .header-actions {
    flex-direction: column;
    width: 100%;
  }
  
  .action-buttons {
    flex-direction: column;
  }
}
</style>
