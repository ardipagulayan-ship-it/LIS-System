<template>
  <div class="service-logs">
    <div class="page-header">
      <h2 class="page-title">Service Logs Management</h2>
      <div class="header-actions">
        <el-button type="primary" @click="openAddModal">
          <el-icon><Plus /></el-icon> Add Service Log
        </el-button>
        <el-button type="success" @click="exportLogs">
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
              placeholder="Search by equipment or action..."
              @input="debounceSearch"
              clearable
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>
          </el-col>
          <el-col :span="4">
            <el-select v-model="filters.service_type" placeholder="All Types" @change="fetchLogs" clearable>
              <el-option label="Preventative Maintenance" value="preventative_maintenance" />
              <el-option label="Calibration" value="calibration" />
              <el-option label="Validation" value="validation" />
              <el-option label="Repair" value="repair" />
              <el-option label="Inspection" value="inspection" />
            </el-select>
          </el-col>
          <el-col :span="4">
            <el-select v-model="filters.outcome" placeholder="All Outcomes" @change="fetchLogs" clearable>
              <el-option label="Pass" value="pass" />
              <el-option label="Fail" value="fail" />
              <el-option label="Partial" value="partial" />
            </el-select>
          </el-col>
          <el-col :span="4">
            <el-date-picker
              v-model="filters.date_range"
              type="daterange"
              range-separator="to"
              start-placeholder="Start Date"
              end-placeholder="End Date"
              @change="fetchLogs"
              clearable
            />
          </el-col>
          <el-col :span="6">
            <el-button-group>
              <el-button @click="clearFilters">
                <el-icon><Close /></el-icon> Clear
              </el-button>
              <el-button type="primary" @click="fetchLogs">
                <el-icon><Search /></el-icon> Search
              </el-button>
            </el-button-group>
          </el-col>
        </el-row>
      </el-card>
    </div>

    <!-- Service Logs Table -->
    <div class="logs-table">
      <el-card>
        <template #header>
          <div class="card-header">
            <span>Service Logs ({{ pagination.total }} items)</span>
          </div>
        </template>
        <el-table
          :data="logsList"
          v-loading="loading"
          style="width: 100%"
          border
          stripe
          highlight-current-row
        >
          <el-table-column prop="equipment.unique_asset_id" label="Equipment" width="150">
            <template #default="{ row }">
              <div>
                <strong>{{ row.equipment?.unique_asset_id }}</strong>
                <div class="text-muted small">{{ row.equipment?.manufacturer }} {{ row.equipment?.model }}</div>
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="service_date" label="Service Date" width="150">
            <template #default="{ row }">
              {{ formatDate(row.service_date) }}
            </template>
          </el-table-column>
          <el-table-column prop="service_type" label="Type" width="150">
            <template #default="{ row }">
              <el-tag :type="getServiceTypeTagType(row.service_type)">
                {{ getServiceTypeLabel(row.service_type) }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="action_taken" label="Action Taken" width="200">
            <template #default="{ row }">
              <div class="action-taken">
                {{ row.action_taken }}
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="outcome" label="Outcome" width="100">
            <template #default="{ row }">
              <el-tag :type="getOutcomeTagType(row.outcome)">
                {{ row.outcome.toUpperCase() }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="service_provider" label="Provider" width="150">
            <template #default="{ row }">
              <div v-if="row.service_provider">
                {{ row.service_provider }}
              </div>
              <span v-else class="text-muted">-</span>
            </template>
          </el-table-column>
          <el-table-column prop="technician" label="Technician" width="150">
            <template #default="{ row }">
              <div v-if="row.technician">
                {{ row.technician.name }}
              </div>
              <span v-else class="text-muted">-</span>
            </template>
          </el-table-column>
          <el-table-column prop="cost" label="Cost" width="100">
            <template #default="{ row }">
              <div v-if="row.cost">
                ${{ parseFloat(row.cost).toFixed(2) }}
              </div>
              <span v-else class="text-muted">-</span>
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="200" fixed="right">
            <template #default="{ row }">
              <el-button-group>
                <el-button size="small" @click="viewLog(row)">
                  <el-icon><View /></el-icon>
                </el-button>
                <el-button size="small" type="warning" @click="editLog(row)">
                  <el-icon><Edit /></el-icon>
                </el-button>
                <el-button size="small" type="info" @click="viewDocumentation(row)">
                  <el-icon><Document /></el-icon>
                </el-button>
                <el-button size="small" type="success" @click="openSignatureModal(row)">
                  <el-icon><EditPen /></el-icon>
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

    <!-- Service Log Modal -->
    <ServiceLogModal
      :visible="showServiceLogModal"
      :logData="selectedLog"
      @close="handleCloseServiceLogModal"
      @save="handleServiceLogSaved"
    />

    <!-- Digital Signature Modal -->
    <DigitalSignatureModal
      :visible="showSignatureModal"
      :logId="selectedLogId"
      @close="handleCloseSignatureModal"
      @save="handleSignatureSaved"
    />
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/utils/axios'
import ServiceLogModal from '@/components/modals/equipment/ServiceLogModal.vue'
import DigitalSignatureModal from '@/components/modals/equipment/DigitalSignatureModal.vue'
import { 
  Plus, 
  Download, 
  Search, 
  Close, 
  View, 
  Edit, 
  Document,
  EditPen
} from '@element-plus/icons-vue'

export default {
  name: 'ServiceLogs',
  components: {
    ServiceLogModal,
    DigitalSignatureModal,
    Plus,
    Download,
    Search,
    Close,
    View,
    Edit,
    Document,
    EditPen
  },
  setup() {
    const router = useRouter()
    
    // Reactive data
    const loading = ref(false)
    const logsList = ref([])
    const equipmentList = ref([])
    
    const showServiceLogModal = ref(false)
    const showSignatureModal = ref(false)
    const selectedLog = ref(null)
    const selectedLogId = ref(null)

    const pagination = reactive({
      current_page: 1,
      per_page: 10,
      total: 0,
      last_page: 1
    })

    const filters = reactive({
      search: '',
      service_type: '',
      outcome: '',
      date_range: null
    })

    // Methods
    const fetchLogs = async () => {
      try {
        loading.value = true
        const params = {
          page: pagination.current_page,
          per_page: pagination.per_page,
          ...filters
        }
        
        if (filters.date_range && filters.date_range.length === 2) {
          params.start_date = filters.date_range[0]
          params.end_date = filters.date_range[1]
        }
        
        const response = await api.get('eim/service-logs', { params })
        logsList.value = response.data.data
        pagination.total = response.data.total
        pagination.last_page = response.data.last_page
      } catch (error) {
        console.error('Error fetching logs:', error)
      } finally {
        loading.value = false
      }
    }

    const fetchEquipment = async () => {
      try {
        const response = await api.get('eim/equipment')
        equipmentList.value = response.data.data
      } catch (error) {
        console.error('Error fetching equipment:', error)
      }
    }

    const clearFilters = () => {
      filters.search = ''
      filters.service_type = ''
      filters.outcome = ''
      filters.date_range = null
      fetchLogs()
    }

    const changePage = (page) => {
      if (page >= 1 && page <= pagination.last_page) {
        pagination.current_page = page
        fetchLogs()
      }
    }

    const handleSizeChange = (size) => {
      pagination.per_page = size
      pagination.current_page = 1
      fetchLogs()
    }

    // Modal Handlers
    const openAddModal = () => {
      selectedLog.value = null
      showServiceLogModal.value = true
    }

    const editLog = (log) => {
      selectedLog.value = log
      showServiceLogModal.value = true
    }

    const handleCloseServiceLogModal = () => {
      showServiceLogModal.value = false
      selectedLog.value = null
    }

    const handleServiceLogSaved = () => {
      fetchLogs()
    }

    const openSignatureModal = (log) => {
      selectedLogId.value = log.id
      showSignatureModal.value = true
    }

    const handleCloseSignatureModal = () => {
      showSignatureModal.value = false
      selectedLogId.value = null
    }

    const handleSignatureSaved = () => {
      fetchLogs()
    }

    const viewLog = (log) => {
      // Navigate to log details or show in modal
      console.log('View log:', log)
    }

    const viewDocumentation = (log) => {
      if (log.documentation_path) {
        window.open(log.documentation_path, '_blank')
      } else {
        console.log('No documentation available')
      }
    }

    const exportLogs = async () => {
      try {
        const response = await api.get('eim/service-logs/export', {
          params: filters,
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', 'service-logs.xlsx')
        document.body.appendChild(link)
        link.click()
        link.remove()
      } catch (error) {
        console.error('Error exporting logs:', error)
      }
    }

    const getServiceTypeLabel = (type) => {
      const labels = {
        preventative_maintenance: 'Preventative Maintenance',
        calibration: 'Calibration',
        validation: 'Validation',
        repair: 'Repair',
        inspection: 'Inspection'
      }
      return labels[type] || type
    }

    const getServiceTypeTagType = (type) => {
      const types = {
        preventative_maintenance: 'primary',
        calibration: 'warning',
        validation: 'success',
        repair: 'danger',
        inspection: 'info'
      }
      return types[type] || 'info'
    }

    const getOutcomeTagType = (outcome) => {
      const types = {
        pass: 'success',
        fail: 'danger',
        partial: 'warning'
      }
      return types[outcome] || 'info'
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString()
    }

    const debounceSearch = (() => {
      let timeout
      return () => {
        clearTimeout(timeout)
        timeout = setTimeout(() => {
          fetchLogs()
        }, 500)
      }
    })()

    // Lifecycle
    onMounted(() => {
      fetchLogs()
      fetchEquipment()
    })

    return {
      loading,
      logsList,
      equipmentList,
      pagination,
      filters,
      showServiceLogModal,
      showSignatureModal,
      selectedLog,
      selectedLogId,
      fetchLogs,
      clearFilters,
      changePage,
      handleSizeChange,
      openAddModal,
      editLog,
      handleCloseServiceLogModal,
      handleServiceLogSaved,
      openSignatureModal,
      handleCloseSignatureModal,
      handleSignatureSaved,
      viewLog,
      viewDocumentation,
      exportLogs,
      getServiceTypeLabel,
      getServiceTypeTagType,
      getOutcomeTagType,
      formatDate,
      debounceSearch
    }
  }
}
</script>

<style scoped>
.service-logs {
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
  gap: var(--spacing-sm);
}

.mb-4 {
  margin-bottom: var(--spacing-lg);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.filters-section {
  margin-bottom: var(--spacing-2xl);
}

.pagination-section {
  margin-top: var(--spacing-xl);
  display: flex;
  justify-content: flex-end;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-sm);
}

.text-muted {
  color: var(--color-text-tertiary);
}

.small {
  font-size: var(--font-size-xs);
}

.action-taken {
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
