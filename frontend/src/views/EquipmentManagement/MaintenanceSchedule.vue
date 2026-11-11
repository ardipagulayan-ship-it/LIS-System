<template>
  <div class="maintenance-schedule">
    <div class="page-header">
      <h2 class="page-title">Maintenance Schedule Management</h2>
      <div class="header-actions">
        <el-button type="primary" @click="openAddModal">
          <el-icon><Plus /></el-icon> Add Schedule
        </el-button>
        <el-button type="success" @click="exportSchedules">
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
              placeholder="Search by equipment or type..."
              @input="debounceSearch"
              clearable
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>
          </el-col>
          <el-col :span="4">
            <el-select v-model="filters.schedule_type" placeholder="All Types" @change="fetchSchedules" clearable>
              <el-option label="Preventative Maintenance" value="preventative_maintenance" />
              <el-option label="Calibration" value="calibration" />
              <el-option label="Validation" value="validation" />
              <el-option label="Repair" value="repair" />
            </el-select>
          </el-col>
          <el-col :span="4">
            <el-select v-model="filters.status" @change="fetchSchedules">
              <el-option label="All Schedules" :value="null" />
              <el-option label="Active Only" :value="true" />
              <el-option label="Inactive Only" :value="false" />
            </el-select>
          </el-col>
          <el-col :span="4">
            <el-select v-model="filters.overdue_only" @change="fetchSchedules">
              <el-option label="All Schedules" :value="false" />
              <el-option label="Overdue Only" :value="true" />
            </el-select>
          </el-col>
          <el-col :span="6">
            <el-button-group>
              <el-button @click="clearFilters">
                <el-icon><Close /></el-icon> Clear
              </el-button>
              <el-button type="primary" @click="fetchSchedules">
                <el-icon><Search /></el-icon> Search
              </el-button>
            </el-button-group>
          </el-col>
        </el-row>
      </el-card>
    </div>

    <!-- Maintenance Schedules Table -->
    <div class="schedules-table">
      <el-card>
        <template #header>
          <div class="card-header">
            <span>Maintenance Schedules ({{ pagination.total }} items)</span>
          </div>
        </template>
        <el-table
          :data="schedulesList"
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
          <el-table-column prop="schedule_type" label="Type" width="150">
            <template #default="{ row }">
              <el-tag :type="getScheduleTypeTagType(row.schedule_type)">
                {{ getScheduleTypeLabel(row.schedule_type) }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="frequency_months" label="Frequency" width="120">
            <template #default="{ row }">
              <div v-if="row.frequency_months">
                {{ row.frequency_months }} months
              </div>
              <div v-else-if="row.frequency_cycles">
                {{ row.frequency_cycles }} cycles
              </div>
              <div v-else-if="row.frequency_hours">
                {{ row.frequency_hours }} hours
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="last_completed_date" label="Last Completed" width="150">
            <template #default="{ row }">
              <div v-if="row.last_completed_date">
                {{ formatDate(row.last_completed_date) }}
              </div>
              <span v-else class="text-muted">Never</span>
            </template>
          </el-table-column>
          <el-table-column prop="next_due_date" label="Next Due" width="150">
            <template #default="{ row }">
              <div v-if="row.next_due_date">
                {{ formatDate(row.next_due_date) }}
                <div :class="getUrgencyClass(row.next_due_date)" class="small">
                  {{ getUrgencyText(row.next_due_date) }}
                </div>
              </div>
              <span v-else class="text-muted">Not scheduled</span>
            </template>
          </el-table-column>
          <el-table-column prop="assigned_personnel" label="Assigned To" width="150">
            <template #default="{ row }">
              <div v-if="row.assigned_personnel">
                {{ row.assigned_personnel.name }}
              </div>
              <div v-else-if="row.assigned_vendor">
                {{ row.assigned_vendor }}
              </div>
              <span v-else class="text-muted">Unassigned</span>
            </template>
          </el-table-column>
          <el-table-column prop="is_active" label="Status" width="100">
            <template #default="{ row }">
              <el-tag :type="row.is_active ? 'success' : 'info'">
                {{ row.is_active ? 'Active' : 'Inactive' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="200" fixed="right">
            <template #default="{ row }">
              <el-button-group>
                <el-button size="small" @click="viewSchedule(row)">
                  <el-icon><View /></el-icon>
                </el-button>
                <el-button size="small" type="warning" @click="editSchedule(row)">
                  <el-icon><Edit /></el-icon>
                </el-button>
                <el-button size="small" type="success" @click="markCompleted(row)">
                  <el-icon><Check /></el-icon>
                </el-button>
                <el-button size="small" type="info" @click="generateWorkOrder(row)">
                  <el-icon><Document /></el-icon>
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

    <!-- Maintenance Schedule Modal -->
    <MaintenanceScheduleModal
      :visible="showModal"
      :schedule-data="selectedSchedule"
      @close="handleCloseModal"
      @save="handleSaveSchedule"
    />
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/utils/axios'
import MaintenanceScheduleModal from '@/components/modals/equipment/MaintenanceScheduleModal.vue'
import { 
  Plus, 
  Download, 
  Search, 
  Close, 
  View, 
  Edit, 
  Check,
  Document
} from '@element-plus/icons-vue'

export default {
  name: 'MaintenanceSchedule',
  components: {
    MaintenanceScheduleModal
  },
  setup() {
    const router = useRouter()
    
    // Reactive data
    const loading = ref(false)
    const schedulesList = ref([])
    const showModal = ref(false)
    const selectedSchedule = ref(null)

    const pagination = reactive({
      current_page: 1,
      per_page: 10,
      total: 0,
      last_page: 1
    })

    const filters = reactive({
      search: '',
      schedule_type: '',
      status: null,
      overdue_only: false
    })

    // Methods
    const fetchSchedules = async () => {
      try {
        loading.value = true
        const params = {
          page: pagination.current_page,
          per_page: pagination.per_page,
          ...filters
        }
        
        const response = await api.get('eim/maintenance-schedules', { params })
        schedulesList.value = response.data.data
        pagination.total = response.data.total
        pagination.last_page = response.data.last_page
      } catch (error) {
        console.error('Error fetching schedules:', error)
      } finally {
        loading.value = false
      }
    }

    const clearFilters = () => {
      filters.search = ''
      filters.schedule_type = ''
      filters.status = null
      filters.overdue_only = false
      fetchSchedules()
    }

    const changePage = (page) => {
      if (page >= 1 && page <= pagination.last_page) {
        pagination.current_page = page
        fetchSchedules()
      }
    }

    const handleSizeChange = (size) => {
      pagination.per_page = size
      pagination.current_page = 1
      fetchSchedules()
    }

    const openAddModal = () => {
      selectedSchedule.value = null
      showModal.value = true
    }

    const editSchedule = (schedule) => {
      selectedSchedule.value = schedule
      showModal.value = true
    }

    const handleCloseModal = () => {
      showModal.value = false
      selectedSchedule.value = null
    }

    const handleSaveSchedule = () => {
      fetchSchedules()
    }

    const viewSchedule = (schedule) => {
      // Navigate to schedule details or show in modal
      console.log('View schedule:', schedule)
    }

    const markCompleted = async (schedule) => {
      try {
        await api.post(`eim/maintenance-schedules/${schedule.id}/mark-completed`)
        fetchSchedules()
      } catch (error) {
        console.error('Error marking as completed:', error)
      }
    }

    const generateWorkOrder = async (schedule) => {
      try {
        const response = await api.post(`eim/maintenance-schedules/${schedule.id}/generate-work-order`)
        // Handle work order generation
        console.log('Work order generated:', response.data)
      } catch (error) {
        console.error('Error generating work order:', error)
      }
    }

    const exportSchedules = async () => {
      try {
        const response = await api.get('eim/maintenance-schedules/export', {
          params: filters,
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', 'maintenance-schedules.xlsx')
        document.body.appendChild(link)
        link.click()
        link.remove()
      } catch (error) {
        console.error('Error exporting schedules:', error)
      }
    }

    const getScheduleTypeLabel = (type) => {
      const labels = {
        preventative_maintenance: 'Preventative Maintenance',
        calibration: 'Calibration',
        validation: 'Validation',
        repair: 'Repair'
      }
      return labels[type] || type
    }

    const getScheduleTypeTagType = (type) => {
      const types = {
        preventative_maintenance: 'primary',
        calibration: 'warning',
        validation: 'success',
        repair: 'danger'
      }
      return types[type] || 'info'
    }

    const getUrgencyClass = (date) => {
      const dueDate = new Date(date)
      const today = new Date()
      const diffTime = dueDate - today
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      
      if (diffDays < 0) return 'text-danger'
      if (diffDays <= 7) return 'text-warning'
      return 'text-success'
    }

    const getUrgencyText = (date) => {
      const dueDate = new Date(date)
      const today = new Date()
      const diffTime = dueDate - today
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      
      if (diffDays < 0) return `${Math.abs(diffDays)} days overdue`
      if (diffDays === 0) return 'Due today'
      if (diffDays === 1) return 'Due tomorrow'
      return `Due in ${diffDays} days`
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString()
    }

    const debounceSearch = (() => {
      let timeout
      return () => {
        clearTimeout(timeout)
        timeout = setTimeout(() => {
          fetchSchedules()
        }, 500)
      }
    })()

    // Lifecycle
    onMounted(() => {
      fetchSchedules()
    })

    return {
      loading,
      schedulesList,
      showModal,
      selectedSchedule,
      pagination,
      filters,
      fetchSchedules,
      clearFilters,
      changePage,
      handleSizeChange,
      openAddModal,
      editSchedule,
      handleCloseModal,
      handleSaveSchedule,
      viewSchedule,
      markCompleted,
      generateWorkOrder,
      exportSchedules,
      getScheduleTypeLabel,
      getScheduleTypeTagType,
      getUrgencyClass,
      getUrgencyText,
      formatDate,
      debounceSearch
    }
  }
}
</script>

<style scoped>
.maintenance-schedule {
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

.pagination-section {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.text-muted {
  color: var(--color-text-tertiary);
}

.small {
  font-size: 12px;
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
</style>
