<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="modelValue"
      title="Test Details"
      width="900px"
      :close-on-click-modal="false"
      :show-close="false"
      class="view-tests-modal"
      top="5vh"
      @update:model-value="$emit('update:modelValue', $event)"
    >
      <!-- Modal Header -->
      <template #header>
        <div class="modal-header">
          <div class="modal-header-brand">
            <div class="icon-wrapper">
              <el-icon><List /></el-icon>
            </div>
            <div class="header-text">
              <span class="modal-header-title">Test Details</span>
              <span class="modal-header-subtitle" v-if="request">Accession ID: {{ request.sample_id }}</span>
            </div>
          </div>
          <el-button link class="close-btn" @click="$emit('update:modelValue', false)">
            <el-icon size="20"><Close /></el-icon>
          </el-button>
        </div>
      </template>

      <!-- Main Content -->
      <div class="modal-body-content" v-if="request">
        
        <!-- Top Section: Patient & Doctor Context -->
        <div class="context-banner">
          <div class="patient-info-group">
            <el-avatar :size="50" :icon="UserFilled" class="patient-avatar" />
            <div class="patient-details">
              <h3 class="patient-name">
                {{ request.patient?.last_name }}, {{ request.patient?.first_name }} {{ request.patient?.middle_name }}
              </h3>
              <div class="patient-meta">
                <span class="meta-item" v-if="request.patient?.gender">
                  <el-icon><Male v-if="request.patient.gender === 'Male'" /><Female v-else /></el-icon>
                  {{ request.patient.gender }}
                </span>
                <span class="meta-separator" v-if="request.patient?.date_of_birth">•</span>
                <span class="meta-item" v-if="request.patient?.date_of_birth">
                  <el-icon><Calendar /></el-icon>
                  {{ calculateAge(request.patient.date_of_birth) }} yrs ({{ formatDate(request.patient.date_of_birth) }})
                </span>
              </div>
            </div>
          </div>
          
          <div class="doctor-info-group" v-if="request.doctor">
             <span class="label">Referred by:</span>
             <div class="doctor-chip">
               <el-icon><FirstAidKit /></el-icon>
               <span>{{ request.doctor.name }}</span>
             </div>
          </div>
        </div>

        <!-- Middle Section: Request Meta Grid -->
        <div class="meta-grid">
           <div class="meta-card">
              <div class="meta-icon warning">
                <el-icon><Clock /></el-icon>
              </div>
              <div class="meta-content">
                <span class="meta-label">Request Date</span>
                <span class="meta-value">{{ formatDate(request.request_date) }}</span>
              </div>
           </div>

           <div class="meta-card">
              <div class="meta-icon primary">
                <el-icon><CollectionTag /></el-icon>
              </div>
              <div class="meta-content">
                <span class="meta-label">Priority</span>
                <el-tag :type="getPriorityTagType(request.priority_level)" size="small" effect="dark" class="priority-tag">
                  {{ request.priority_level }}
                </el-tag>
              </div>
           </div>

           <div class="meta-card">
              <div class="meta-icon success">
                <el-icon><InfoFilled /></el-icon>
              </div>
              <div class="meta-content">
                <span class="meta-label">Status</span>
                <el-tag :type="getStatusType(request.status)" size="small" effect="light">
                  {{ request.status }}
                </el-tag>
              </div>
           </div>
        </div>

        <!-- Bottom Section: Tests Table -->
        <div class="tests-section">
          <div class="section-header">
            <h4>Requested Tests ({{ tests.length }})</h4>
          </div>
          
          <el-table 
            :data="tests" 
            style="width: 100%" 
            border 
            stripe
            class="tests-table"
            :header-cell-style="{ background: '#f5f7fa', color: '#606266', fontWeight: '600' }"
          >
            <el-table-column prop="name" label="Test Name" min-width="200">
               <template #default="{ row }">
                 <span class="test-name">{{ row.name }}</span>
                 <div class="test-code" v-if="row.code">{{ row.code }}</div>
               </template>
            </el-table-column>
            
            <el-table-column label="Category" width="160">
              <template #default="{ row }">
                <el-tag size="small" type="info" effect="plain" round>
                  {{ row.test_category?.name || 'N/A' }}
                </el-tag>
              </template>
            </el-table-column>

            <el-table-column label="Status" width="140">
              <template #default="{ row }">
                <div class="status-cell">
                  <div :class="['status-dot', getStatusColorClass(row.pivot?.item_status)]"></div>
                  <span>{{ row.pivot?.item_status || 'Pending' }}</span>
                </div>
              </template>
            </el-table-column>
            
            <el-table-column label="Notes" min-width="150">
              <template #default="{ row }">
                <span v-if="row.pivot?.rejection_reason" class="rejection-text">
                  <el-icon><Warning /></el-icon> {{ row.pivot?.rejection_reason }}
                </span>
                <span v-else class="text-muted">-</span>
              </template>
            </el-table-column>

            <el-table-column label="Price" width="140" align="right">
              <template #default="{ row }">
                <span class="price-text">{{ formatCurrency(row.pivot?.price_at_request) }}</span>
              </template>
            </el-table-column>
          </el-table>

          <!-- Financial Summary -->
          <div class="financial-summary">
            <div class="summary-row total">
              <span class="summary-label">Total Amount</span>
              <span class="summary-value">{{ formatCurrency(totalPrice) }}</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Footer -->
      <template #footer>
        <div class="modal-footer">
          <el-button @click="$emit('update:modelValue', false)" plain>Close</el-button>
          <el-button type="primary" @click="$emit('update:modelValue', false)">OK</el-button>
        </div>
      </template>
    </el-dialog>
  </Teleport>
</template>

<script setup>
import { defineProps, defineEmits, computed } from 'vue';
import { 
  Close, 
  List, 
  UserFilled, 
  Calendar, 
  Male, 
  Female, 
  FirstAidKit,
  Clock,
  CollectionTag,
  InfoFilled,
  Warning
} from '@element-plus/icons-vue';
import { format, differenceInYears } from 'date-fns';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true
  },
  request: {
    type: Object,
    default: null
  },
  tests: {
    type: Array,
    default: () => []
  }
});

defineEmits(['update:modelValue']);

// Computed
const totalPrice = computed(() => {
  return props.tests.reduce((sum, test) => {
    const price = parseFloat(test.pivot?.price_at_request) || 0;
    return sum + price;
  }, 0);
});

// Helpers
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  try {
    return format(new Date(dateString), 'MMM dd, yyyy');
  } catch (e) {
    return dateString;
  }
};

const calculateAge = (dob) => {
  if (!dob) return '?';
  try {
    return differenceInYears(new Date(), new Date(dob));
  } catch (e) {
    return '?';
  }
};

const formatCurrency = (value) => {
  if (value === undefined || value === null) return '₱0.00';
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2
  }).format(value);
};

const getStatusType = (status) => {
  const statusMap = {
    'Pending': 'warning',
    'Collected': 'info',
    'Accepted': 'success',
    'Result Printed': 'success',
    'In Progress': 'primary', 
    'Completed': 'success',
    'Cancelled': 'danger',
    'Rejected': 'danger',
    'For Re-run': 'danger'
  };
  return statusMap[status] || 'info';
};

const getPriorityTagType = (priority) => {
  const priorityMap = {
    'Routine': 'info',
    'Urgent': 'warning',
    'STAT': 'danger'
  };
  return priorityMap[priority] || 'info';
};

const getStatusColorClass = (status) => {
  const map = {
    'Pending': 'bg-warning',
    'Collected': 'bg-info',
    'Accepted': 'bg-success',
    'Result Printed': 'bg-success',
    'In Progress': 'bg-primary',
    'Completed': 'bg-success',
    'Cancelled': 'bg-danger',
    'Rejected': 'bg-danger',
    'For Re-run': 'bg-danger'
  };
  return map[status] || 'bg-gray';
};

</script>

<style scoped>
.view-tests-modal :deep(.el-dialog__header) {
  padding: 0;
  margin: 0;
  border-bottom: 1px solid var(--color-border-secondary, #e4e7ed);
}

.view-tests-modal :deep(.el-dialog__body) {
  padding: 0;
}

.view-tests-modal :deep(.el-dialog__footer) {
  padding: 16px 24px;
  border-top: 1px solid var(--color-border-secondary, #e4e7ed);
}

/* Header */
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  background-color: #fff;
}

.modal-header-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.icon-wrapper {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  background-color: var(--color-primary-light, #ecf5ff);
  color: var(--color-primary, #409eff);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.header-text {
  display: flex;
  flex-direction: column;
}

.modal-header-title {
  font-size: 18px;
  font-weight: 700;
  color:  #ffffff;
  line-height: 1.2;
}

.modal-header-subtitle {
  font-size: 12px;
  color:  #ffffff;
}

.close-btn {
  color: var(--color-text-secondary, #909399);
}

.close-btn:hover {
  color: var(--color-danger, #f56c6c);
}

/* Body Content */
.modal-body-content {
 
  max-height: 70vh;
  overflow-y: auto;
 
}

/* Context Banner */
.context-banner {
  background: white;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  border: 1px solid var(--color-border-secondary);
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  flex-wrap: wrap;
  gap: 16px;
}

.patient-info-group {
  display: flex;
  align-items: center;
  gap: 16px;
}

.patient-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.patient-name {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #303133;
}

.patient-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #606266;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 4px;
}

.meta-separator {
  color: #c0c4cc;
}

.doctor-info-group {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.doctor-info-group .label {
  font-size: 12px;
  text-transform: uppercase;
  color: #909399;
  letter-spacing: 0.5px;
  font-weight: 600;
}

.doctor-chip {
  display: flex;
  align-items: center;
  gap: 6px;
  background-color: #f0f2f5;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  color: #606266;
}

/* Meta Grid */
.meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.meta-card {
  background: white;
  border-radius: 8px;
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  border: 1px solid #e4e7ed;
  transition: all 0.2s;
}

.meta-card:hover {
  border-color: var(--color-primary-light, #c6e2ff);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.meta-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.meta-icon.warning { background: #fdf6ec; color: #e6a23c; }
.meta-icon.primary { background: #ecf5ff; color: #409eff; }
.meta-icon.success { background: #f0f9eb; color: #67c23a; }

.meta-content {
  display: flex;
  flex-direction: column;
}

.meta-label {
  font-size: 12px;
  color: #909399;
}

.meta-value {
  font-size: 13px;
  font-weight: 600;
  color: #303133;
}

/* Tests Section */
.tests-section {
  background: white;
  border: 1px solid var(--color-border-secondary);
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.section-header {
  margin-bottom: 16px;
  border-bottom: 1px solid #ebeef5;
  padding-bottom: 12px;
}

.section-header h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: #303133;
}

.test-name {
  font-weight: 600;
  color: #303133;
  display: block;
}

.test-code {
  font-size: 12px;
  color: #909399;
  margin-top: 2px;
}

.status-cell {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.bg-warning { background-color: #e6a23c; }
.bg-info { background-color: #909399; }
.bg-success { background-color: #67c23a; }
.bg-primary { background-color: #409eff; }
.bg-danger { background-color: #f56c6c; }
.bg-gray { background-color: #dcdfe6; }

.rejection-text {
  color: #f56c6c;
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.text-muted {
  color: #c0c4cc;
}

.price-text {
  font-family: 'Roboto Mono', monospace;
  font-weight: 600;
  color: #303133;
}

/* Financial Summary */
.financial-summary {
  margin-top: 24px;
  display: flex;
  justify-content: flex-end;
  border-top: 1px solid #ebeef5;
  padding-top: 16px;
}

.summary-row.total {
  display: flex;
  align-items: center;
  gap: 24px;
}

.summary-label {
  font-size: 13px;
  color: #606266;
  font-weight: 500;
}

.summary-value {
  font-size: 24px;
  font-weight: 700;
  color: var(--color-primary, #409eff);
  font-family: 'Roboto Mono', monospace;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}
</style>