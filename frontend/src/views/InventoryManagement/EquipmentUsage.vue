<template>
  <div class="equipment-usage">
    <div class="header-container">
      <div class="header-left">
        <h2>Equipment Usage Management</h2>
        <p> Manage equipment usage and assignments</p>
      </div>

      <div class="header-right">
        <el-button @click="showListView = !showListView" :type="showListView ? 'primary' : 'default'">
          <el-icon><List /></el-icon> View List
        </el-button>
        <el-button type="primary" @click="loadAllEquipment" :loading="loading">
          <el-icon><Plus /></el-icon> Add Instrument
        </el-button>
        <el-button @click="loadAllEquipment" :loading="loading">
          <el-icon><Refresh /></el-icon> Refresh
        </el-button>
      </div>
    </div>

    <!-- Equipment Management Table View -->
    <div v-if="showListView" class="equipment-management-view">
      <EquipmentManagementView />
    </div>

    <!-- Equipment Cards Grid View -->
    <div v-else>

    <!-- Equipment Cards Grid - Grouped by Category -->
    <div v-if="groupedEquipment.length > 0">
      <div v-for="categoryGroup in groupedEquipment" :key="categoryGroup.categoryId" class="category-group">
        <div class="category-header">
          <h3>{{ categoryGroup.categoryName || 'Uncategorized' }}</h3>
        </div>
        <el-row :gutter="20" v-loading="loading" class="equipment-cards-row">
          <el-col :span="6" v-for="equipment in categoryGroup.equipment" :key="equipment.id" class="equipment-card-col">
        <el-card class="equipment-card" shadow="hover">
          <!-- Card Header -->
          <template #header>
            <div class="card-header">
              <div class="header-left">
                <el-icon class="equipment-icon"><Operation /></el-icon>
                <span class="equipment-name">{{ equipment.manufacturer }} - {{ equipment.model }}</span>
              </div>
              <div class="header-actions-btns">
                <el-button size="small" circle @click="viewEquipmentDetails(equipment)">
                  <el-icon><View /></el-icon>
                </el-button>
                <el-dropdown @command="handleGearMenuCommand" trigger="click">
                  <el-button size="small" circle>
                    <el-icon><Setting /></el-icon>
                  </el-button>
                  <template #dropdown>
                    <el-dropdown-menu>
                      <el-dropdown-item :command="{ action: 'referenceRange', equipment }">
                        Reference Range
                      </el-dropdown-item>
                      <el-dropdown-item :command="{ action: 'analyzerTestMapping', equipment }">
                        Analyzer Test Mapping
                      </el-dropdown-item>
                    </el-dropdown-menu>
                  </template>
                </el-dropdown>
                <el-button size="small" circle type="danger" @click="deleteEquipment(equipment)">
                  <el-icon><Delete /></el-icon>
                </el-button>
              </div>
            </div>
          </template>

          <div Class="equipment-container">
            <!-- Equipment Image Placeholder -->
            <div class="equipment-image-container">
              <div class="equipment-image">
                <el-icon><Connection /></el-icon>
              </div>
            </div>

            <!-- Equipment Details -->
            <div class="equipment-details">
              <div class="detail-row">
                <span class="detail-label">Status:</span>
                <el-tag :type="getStatusType(equipment.current_status)" size="small">
                  {{ getStatusLabel(equipment.current_status) }}
                </el-tag>
              </div>
              <div class="detail-row">
                <span class="detail-label">Assigned Date:</span>
                <span class="detail-value">{{ formatDate(equipment.created_at) }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Active Assignments:</span>
                <div style="display: flex; align-items: center; gap: 8px;">
                  <el-button :value="equipment.reagent_assignments?.length || 0"
                    @click="viewActiveAssignments(equipment)"
                    :disabled="(equipment.reagent_assignments?.length || 0) === 0" 
                    :type="equipment.reagent_assignments?.length > 0 ? 'success' : 'info'" 
                    size="small">
                    {{ equipment.reagent_assignments?.length || 0 }}
                  </el-button>
                </div>
              </div>
            </div>
          </div>
          <!-- Footer Buttons -->
          <div class="card-footer">
            <el-button 
              :type="getAssignButtonType(equipment)" 
              @click="openAssignDialog(equipment, 'reagent')"
              class="footer-btn"
              :disabled="!isOperational(equipment)"
            >
              <el-icon><Plus /></el-icon> Assign Reagent
            </el-button>
            <el-button 
              :type="getAssignButtonType(equipment)" 
              @click="openAssignDialog(equipment, 'control')"
              class="footer-btn"
              :disabled="!isOperational(equipment)"
            >
            <el-icon><Plus /></el-icon> Assign Control
            </el-button>
          </div>
          <div class="card-footer">
            <el-button 
              type="success" 
              @click="viewUsageHistory(equipment)"
              class="footer-btn"
            >
              <el-icon><Document /></el-icon> History
            </el-button>
            <el-button 
              type="warning" 
              @click="viewLJGraph(equipment)"
              class="footer-btn"
            >
              <el-icon><DataLine /></el-icon> LJ Graph
            </el-button>
          </div>

          <!-- Version -->
          <div class="version-info">v{{ equipment.version || '1.0.0.0' }}</div>
        </el-card>
      </el-col>
        </el-row>
      </div>
    </div>

    <el-empty v-else description="No equipment available" />
    </div>

    <!-- Assign Reagent Modal -->
    <AssignReagentModal
      :visible="assignDialogVisible"
      :equipment="selectedEquipment"
      :type="assignmentType"
      :available-materials="availableMaterials"
      :available-tests="availableTests"
      @update:visible="assignDialogVisible = $event"
      @close="assignDialogVisible = false"
      @save="handleAssignmentSaved"
    />

    <!-- Usage History Modal -->
    <UsageHistoryModal
      :visible="historyDialogVisible"
      :equipment="selectedEquipment"
      @update:visible="historyDialogVisible = $event"
      @close="historyDialogVisible = false"
    />

    <!-- Active Assignments Modal -->
    <ActiveAssignmentsModal
      :visible="assignmentsDialogVisible"
      :equipment="selectedEquipment"
      @update:visible="assignmentsDialogVisible = $event"
      @close="assignmentsDialogVisible = false"
      @update="loadAllEquipment"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Delete, View, Setting, Refresh, Operation, Connection, Document, DataLine, Collection, List } from '@element-plus/icons-vue'
import api from '@/utils/axios'
import EquipmentManagementView from '@/views/EquipmentManagement/EquipmentManagement.vue'
import AssignReagentModal from '@/components/modals/inventory/AssignReagentModal.vue'
import UsageHistoryModal from '@/components/modals/inventory/UsageHistoryModal.vue'
import ActiveAssignmentsModal from '@/components/modals/inventory/ActiveAssignmentsModal.vue'

const router = useRouter()
const loading = ref(false)
const allEquipment = ref([])
const testCategories = ref([])
const assignDialogVisible = ref(false)
const historyDialogVisible = ref(false)
const assignmentsDialogVisible = ref(false)
const assignmentType = ref('reagent')
const selectedEquipment = ref(null)
const showListView = ref(false)

const availableMaterials = ref([])
const availableTests = ref([])

// Group equipment by test category
const groupedEquipment = computed(() => {
  const groups = {}
  
  allEquipment.value.forEach(equipment => {
    const categoryId = equipment.test_category_id || 'uncategorized'
    const categoryName = equipment.test_category?.name || 'Uncategorized'
    
    if (!groups[categoryId]) {
      groups[categoryId] = {
        categoryId: categoryId,
        categoryName: categoryName,
        equipment: []
      }
    }
    
    groups[categoryId].equipment.push(equipment)
  })
  
  // Convert to array and sort by category name
  return Object.values(groups).sort((a, b) => {
    if (a.categoryId === 'uncategorized') return 1
    if (b.categoryId === 'uncategorized') return -1
    return a.categoryName.localeCompare(b.categoryName)
  })
})

const loadTestCategories = async () => {
  try {
    const response = await api.get('/test-categories')
    testCategories.value = Array.isArray(response.data) ? response.data : (response.data.data || [])
  } catch (error) {
    console.error('Failed to load test categories', error)
  }
}

onMounted(() => {
  loadTestCategories()
  loadAllEquipment()
  loadMaterials()
  loadTests()
})

const loadAllEquipment = async () => {
  loading.value = true
  try {
    const response = await api.get('eim/equipment-usage/all-equipment')
    allEquipment.value = response.data || []
  } catch (error) {
    console.error('Failed to load equipment', error)
    ElMessage.error('Failed to load equipment')
  } finally {
    loading.value = false
  }
}

const loadMaterials = async () => {
  try {
    const response = await api.get('eim/imm/materials')
    availableMaterials.value = (response.data.data || response.data || []).filter(m => m && m.id && m.generic_name)
  } catch (error) {
    console.error('Failed to load materials', error)
  }
}

const loadTests = async () => {
  try {
    console.log('Loading tests...')
    const response = await api.get('tests')
    console.log('Tests API response:', response.data)
    
    // Handle different response structures
    const testsData = response.data.data || response.data || []
    availableTests.value = Array.isArray(testsData) 
      ? testsData.filter(t => t && t.id && t.name)
      : []
    
    console.log('Filtered tests:', availableTests.value)
  } catch (error) {
    console.error('Failed to load tests', error)
    ElMessage.error('Failed to load tests: ' + (error.response?.data?.message || error.message))
  }
}

const openAssignDialog = (equipment, type) => {
  selectedEquipment.value = equipment
  assignmentType.value = type
  assignDialogVisible.value = true
}

const handleAssignmentSaved = () => {
  assignDialogVisible.value = false
  loadAllEquipment()
}

const viewActiveAssignments = (equipment) => {
  selectedEquipment.value = equipment
  assignmentsDialogVisible.value = true
}

const viewUsageHistory = (equipment) => {
  selectedEquipment.value = equipment
  historyDialogVisible.value = true
}

const viewLJGraph = (equipment) => {
  ElMessage.info('LJ Graphs feature coming soon (linked to Quality Control module)')
}

const viewEquipmentDetails = (equipment) => {
  ElMessage.info('Viewing equipment details')
}

const configureEquipment = (equipment) => {
  ElMessage.info('Configuring equipment')
}

const handleGearMenuCommand = (command) => {
  const { action, equipment } = command
  
  if (action === 'referenceRange') {
    // Navigate to Reference Range Management with equipment filter
    router.push({
      name: 'ReferenceRangeManagement',
      query: { equipment_id: equipment.id }
    })
  } else if (action === 'analyzerTestMapping') {
    // Navigate to Analyzer Test Mapping (or show message if not implemented)
    ElMessage.info('Analyzer Test Mapping feature coming soon')
    // TODO: Implement Analyzer Test Mapping route when ready
    // router.push({
    //   name: 'analyzer-test-mapping',
    //   params: { equipmentId: equipment.id }
    // })
  }
}

const deleteEquipment = async (equipment) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete ${equipment.manufacturer} ${equipment.model}?`,
      'Confirm Delete',
      { type: 'warning' }
    )
    
    // TODO: Implement delete endpoint
    ElMessage.success('Equipment deleted successfully')
    loadAllEquipment()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Failed to delete equipment', error)
      ElMessage.error('Failed to delete equipment')
    }
  }
}

const getStatusType = (status) => {
  const statusMap = {
    'in_service': 'success',
    'under_maintenance': 'warning',
    'retired': 'info',
    'calibration_due': 'warning',
    'out_of_service': 'danger'
  }
  return statusMap[status] || 'info'
}

const getStatusLabel = (status) => {
  const statusMap = {
    'in_service': 'In Service',
    'under_maintenance': 'Under Maintenance',
    'retired': 'Retired',
    'calibration_due': 'Calibration Due',
    'out_of_service': 'Out of Service'
  }
  return statusMap[status] || status
}

const isOperational = (equipment) => {
  return equipment.current_status === 'in_service'
}

const getAssignButtonType = (equipment) => {
  return isOperational(equipment) ? 'primary' : 'info'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: '2-digit', 
    day: '2-digit' 
  })
}

const formatDateTime = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString('en-US', { 
    year: 'numeric', 
    month: '2-digit', 
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<style scoped>
.equipment-usage {
  width: 100%;
}

.header-container {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  width: 100%;
  margin-bottom: 10px;
}

.header-left {
  display: column;
}

.header-right {
  display: column;
}

.header-left h2 {
  margin: 0 0 8px 0;
  color: var(--color-text-primary);
  font-size: var(--font-size-2xl);
}

.header-left p {
  margin: 0;
  color: var(--color-text-secondary);
  font-size: var(--font-size-base);
}

.equipment-card-col {
  margin-bottom: var(--spacing-xl);
}

.equipment-card {
  height: 100%;
  position: relative;
  border-radius: var(--border-radius-xl);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.equipment-icon {
  font-size: 20px;
  color: var(--color-primary);
}

.equipment-name {
  font-weight: var(--font-weight-bold);
  font-size: var(--font-size-lg);
}

.header-actions-btns {
  display: flex;
  gap: 4px;
}

.equipment-container{
  display: flex;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 10px;
}
.equipment-image-container {
  justify-content: center;
  align-items: center;
  background: linear-gradient(135deg, var(--color-bg-secondary) 0%, var(--color-border-secondary) 100%);
  border-radius: var(--border-radius-xl);
  min-height: 150px;
  width: 100%;
}

.equipment-image {
  font-size: 80px;
  color: var(--color-primary);
  opacity: 0.3;
}

.equipment-details {
  width: 100%;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-sm) 0;
  border-bottom: 1px solid var(--color-border-light);
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-label {
  font-weight: var(--font-weight-medium);
  color: var(--color-text-secondary);
}

.detail-value {
  color: var(--color-text-primary);
}

.card-footer {
  display: flex;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-xs);
  margin-top: var(--spacing-xs);
}

.footer-btn {
  width: 100%;
}

.version-info {
  position: absolute;
  bottom: var(--spacing-sm);
  right: var(--spacing-lg);
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
  font-style: italic;
}

.assignment-badge {
  margin-right: 4px;
}

.assignment-badge :deep(.el-badge__content) {
  background-color: var(--color-primary);
}

.equipment-management-view {
  margin-top: 20px;
}

.category-group {
  margin-bottom: 30px;
}

.category-header {
  margin-bottom: 16px;
  padding-bottom: 8px;
  border-bottom: 2px solid var(--color-primary);
}

.category-header h3 {
  margin: 0;
  color: var(--color-primary);
  font-size: 18px;
  font-weight: 600;
}

.equipment-cards-row {
  margin-bottom: 20px;
}
</style>
