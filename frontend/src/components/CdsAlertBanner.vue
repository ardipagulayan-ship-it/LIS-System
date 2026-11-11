<template>
  <div class="unified-alert-banner" :class="{
    'banner-success': (transientState && transientState.type === 'success') || bannerType === 'acknowledgment',
    'banner-denied': (transientState && transientState.type === 'denied') || bannerType === 'rejected',
    'banner-action-required': bannerType && !transientState && bannerType !== 'acknowledgment' && bannerType !== 'rejected'
  }" v-if="shouldShowBanner">
    <!-- Banner Header -->
    <div class="banner-header">
      <div class="banner-icon">
        <el-icon v-if="transientState && transientState.type === 'success'"><SuccessFilled /></el-icon>
        <el-icon v-else-if="transientState && transientState.type === 'denied'"><InfoFilled /></el-icon>
        <el-icon v-else-if="bannerType === 'rejected'"><Lock /></el-icon>
        <el-icon v-else-if="bannerType === 'qc-hold'"><WarningFilled /></el-icon>
        <el-icon v-else-if="bannerType === 'critical-value'"><WarningFilled /></el-icon>
        <el-icon v-else-if="bannerType === 'cds-alert' && pendingReflexAlert"><InfoFilled /></el-icon>
        <el-icon v-else-if="bannerType === 'acknowledgment'"><SuccessFilled /></el-icon>
        <el-icon v-else><InfoFilled /></el-icon>
      </div>
      <div class="banner-title-section">
        <div class="banner-title">{{ bannerTitle }}</div>
        <div class="banner-subtitle" v-html="bannerSubtitle"></div>
        <div v-if="transientState" class="transient-countdown">
          <el-progress
            :percentage="transientProgress"
            :stroke-width="4"
            :show-text="false"
            :color="transientState.type === 'success' ? '#67c23a' : '#909399'"
          />
        </div>
      </div>
      <div class="banner-actions" v-if="hasActionButtons">
        <el-button-group>
          <el-button
            v-if="bannerType === 'cds-alert' && pendingReflexAlert"
            type="success"
            @click="handleApprove"
            :loading="isProcessing"
          >
            APPROVE REFLEX TEST
          </el-button>
          <el-button 
            v-if="bannerType === 'cds-alert' && pendingReflexAlert"
            type="info" 
            @click="showLogicModal = true"
          >
            VIEW CLINICAL LOGIC
          </el-button>
          <el-button 
            v-if="bannerType === 'cds-alert' && pendingReflexAlert"
            type="danger" 
            @click="handleDeny"
            :disabled="isProcessing"
          >
            DENY/IGNORE
          </el-button>
          <el-button
            v-if="hasAcknowledgeButton"
            type="primary"
            @click="handleAcknowledge"
            :loading="acknowledging"
          >
            Acknowledge
          </el-button>
        </el-button-group>
      </div>
      <el-button 
        class="expand-btn" 
        type="text" 
        @click="showDetails = !showDetails"
        :icon="showDetails ? 'ArrowUp' : 'ArrowDown'"
      />
    </div>

    <!-- Expandable Details Section -->
    <div class="banner-details" v-if="showDetails">
      <div class="details-content">
        <!-- QC Hold Details -->
        <div v-if="bannerType === 'qc-hold'">
          <h4>QC Lockout Active</h4>
          <p>{{ qcHoldMessage }}</p>
          <p class="details-note">Please contact your laboratory supervisor or quality control officer to resolve this issue before proceeding.</p>
        </div>

        <!-- CDS Alert Details (Reflex) -->
        <div v-else-if="bannerType === 'cds-alert' && pendingReflexAlert">
          <h4>Clinical Logic:</h4>
          <p>{{ pendingReflexAlert.description }}</p>
          <div v-if="pendingReflexAlert.conditions && pendingReflexAlert.conditions.length">
            <strong>Conditions:</strong>
            <ul>
              <li v-for="(cond, idx) in pendingReflexAlert.conditions" :key="idx">{{ cond }}</li>
            </ul>
          </div>
          <div v-if="pendingReflexAlert.reflex_test_name">
            <strong>Recommended Reflex Test:</strong> {{ pendingReflexAlert.reflex_test_name }}
          </div>
        </div>

        <!-- CDS Alert Details (Other) -->
        <div v-else-if="bannerType === 'cds-alert' && !pendingReflexAlert && unacknowledgedCdsAlert">
          <h4>CDS Rule: {{ unacknowledgedCdsAlert.rule_name }}</h4>
          <p>{{ unacknowledgedCdsAlert.description }}</p>
          <div v-if="unacknowledgedCdsAlert.conditions && unacknowledgedCdsAlert.conditions.length">
            <strong>Conditions:</strong>
            <ul>
              <li v-for="(cond, idx) in unacknowledgedCdsAlert.conditions" :key="idx">{{ cond }}</li>
            </ul>
          </div>
        </div>

        <!-- Critical Values Details -->
        <div v-else-if="bannerType === 'critical-value'">
          <h4>Critical Results Requiring Action</h4>
          <ul>
            <li v-for="cv in criticalValues" :key="cv.parameter_id">
              <strong>{{ cv.parameter }}</strong>: {{ cv.value }} {{ cv.unit }} - {{ cv.message }}
            </li>
          </ul>
        </div>

        <!-- Acknowledgment Details -->
        <div v-else-if="bannerType === 'acknowledgment'">
          <h4>Acknowledged Items</h4>
          <ul>
            <li v-if="cdsAcknowledgeData">CDS Rule: {{ cdsAcknowledgeData.rule_name }}</li>
            <li v-if="deltaAcknowledgeData">Delta Check: {{ deltaAcknowledgeData.rule_name }}</li>
            <li v-if="criticalAcknowledgeData">Critical Value: {{ criticalAcknowledgeData.rule_name }}</li>
          </ul>
          <p class="details-note">All critical actions have been acknowledged. You may now proceed with validation.</p>
        </div>
      </div>
    </div>

    <!-- Clinical Logic Modal (for reflex tests) -->
    <el-dialog
      v-model="showLogicModal"
      title="Clinical Logic Details"
      width="50%"
      :close-on-click-modal="false"
      v-if="pendingReflexAlert"
    >
      <div class="logic-modal-content">
        <h4>Rule: {{ pendingReflexAlert.rule_name }}</h4>
        <p><strong>Description:</strong> {{ pendingReflexAlert.description }}</p>
        <div v-if="pendingReflexAlert.conditions && pendingReflexAlert.conditions.length">
          <strong>Conditions:</strong>
          <ul>
            <li v-for="(cond, idx) in pendingReflexAlert.conditions" :key="idx">{{ cond }}</li>
          </ul>
        </div>
        <div v-if="pendingReflexAlert.reflex_test_name">
          <strong>Recommended Reflex Test:</strong> {{ pendingReflexAlert.reflex_test_name }}
        </div>
      </div>
      <template #footer>
        <el-button @click="showLogicModal = false">Close</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, onBeforeUnmount, watch } from 'vue'
import { ElMessage } from 'element-plus'
import api from '@/utils/axios'

const props = defineProps({
  // Status flags
  isRejected: {
    type: Boolean,
    default: false
  },
  latestRejectedAt: {
    type: String,
    default: ''
  },
  
  // QC Hold data
  qcHoldMessage: {
    type: String,
    default: ''
  },
  hasHoldStatus: {
    type: Boolean,
    default: false
  },
  
  // CDS Alerts
  cdsAlerts: {
    type: Array,
    default: () => []
  },
  
  // Critical Values
  criticalValues: {
    type: Array,
    default: () => []
  },
  
  // Acknowledgment data
  cdsAcknowledgeData: {
    type: Object,
    default: null
  },
  deltaAcknowledgeData: {
    type: Object,
    default: null
  },
  criticalAcknowledgeData: {
    type: Object,
    default: null
  },
  
  // Request data
  requestId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['approve', 'deny', 'acknowledge', 'refresh'])

// Log initial requestId on mount
onMounted(() => {
  console.error('[REFLEX DEBUG] CdsAlertBanner mounted with requestId:', {
    requestId: props.requestId,
    timestamp: new Date().toISOString()
  })
})

// CRITICAL: Log the requestId prop when it changes (skip initial call)
watch(() => props.requestId, (newId, oldId) => {
  // Only log when oldId is defined (i.e., an actual change)
  if (oldId !== undefined) {
    console.error('[REFLEX DEBUG] CdsAlertBanner requestId changed:', {
      oldId,
      newId,
      timestamp: new Date().toISOString()
    })
  }
})

// Transient state management for success/denied feedback
const transientState = ref(null) // { type: 'success'|'denied', message: string, startTime: number }
const isMounted = ref(true)
const activeTimeouts = ref([])

onMounted(() => {
  isMounted.value = true
})

onBeforeUnmount(() => {
  isMounted.value = false
  // Clear all pending timeouts
  activeTimeouts.value.forEach(timeoutId => {
    clearTimeout(timeoutId)
  })
  activeTimeouts.value = []
})

const clearTransientState = () => {
  if (!isMounted.value) return
  // Clear all pending timeouts
  activeTimeouts.value.forEach(timeoutId => {
    clearTimeout(timeoutId)
  })
  activeTimeouts.value = []
  transientState.value = null
}

const showTransientSuccess = async (message) => {
  if (!isMounted.value) return
  
  await nextTick()
  if (!isMounted.value) return
  
  clearTransientState()
  if (!isMounted.value) return
  
  transientState.value = {
    type: 'success',
    message,
    startTime: Date.now()
  }
  
  const timeoutId = setTimeout(() => {
    if (isMounted.value) {
      clearTransientState()
    }
  }, 5000)
  
  activeTimeouts.value.push(timeoutId)
}

const showTransientDenied = async (message) => {
  if (!isMounted.value) return
  
  await nextTick()
  if (!isMounted.value) return
  
  clearTransientState()
  if (!isMounted.value) return
  
  transientState.value = {
    type: 'denied',
    message,
    startTime: Date.now()
  }
  
  const timeoutId = setTimeout(() => {
    if (isMounted.value) {
      clearTransientState()
    }
  }, 5000)
  
  activeTimeouts.value.push(timeoutId)
}

const transientProgress = computed(() => {
  if (!transientState.value?.startTime) return 0
  const elapsed = Date.now() - transientState.value.startTime
  const remaining = Math.max(0, 5000 - elapsed)
  return Math.min(100, (elapsed / 5000) * 100)
})

const isProcessing = ref(false)
const acknowledging = ref(false)
const showDetails = ref(false)
const showLogicModal = ref(false)

// Computed properties for banner state
const hasUnacknowledgedAlerts = computed(() => {
  const hasDelta = props.hasHoldStatus
  const hasCritical = props.criticalValues.length > 0
  
  // For CDS alerts, check if there are any that are truly unacknowledged
  // Reflex tests are considered acknowledged if reflex_created is true
  const hasUnacknowledgedCds = props.cdsAlerts.some(alert =>
    alert.action_type === 'REFLEX_TEST'
      ? !alert.reflex_created  // Reflex tests: check reflex_created flag
      : !alert.is_acknowledged // Non-reflex CDS alerts: check is_acknowledged
  )
  
  return hasUnacknowledgedCds
    || (hasDelta && !props.deltaAcknowledgeData)
    || (hasCritical && !props.criticalAcknowledgeData)
})

const hasAnyAcknowledgment = computed(() => {
  return !!(props.cdsAcknowledgeData || props.deltaAcknowledgeData || props.criticalAcknowledgeData)
})

const pendingReflexAlert = computed(() => {
  if (!props.cdsAlerts || props.cdsAlerts.length === 0) return null
  const found = props.cdsAlerts.find(alert =>
    alert.action_type === 'REFLEX_TEST' &&
    !alert.reflex_created
  )
  if (found) {
    console.error('[REFLEX DEBUG] pendingReflexAlert found:', {
      requestId: props.requestId,
      alertId: found.id,
      alertRuleName: found.rule_name,
      trigger_test_id: found.trigger_test_id,
      reflex_test_id: found.reflex_test_id
    })
  }
  return found
})

const unacknowledgedCdsAlert = computed(() => {
  if (!props.cdsAlerts || props.cdsAlerts.length === 0) return null
  return props.cdsAlerts.find(alert => 
    !alert.is_acknowledged && 
    alert.action_type !== 'REFLEX_TEST'
  )
})

const bannerType = computed(() => {
  if (transientState.value) return transientState.value.type === 'success' ? 'success' : 'denied'
  if (props.isRejected) return 'rejected'
  if (props.qcHoldMessage) return 'qc-hold'
  if (hasUnacknowledgedAlerts.value) {
    if (pendingReflexAlert.value) return 'cds-alert'
    if (unacknowledgedCdsAlert.value) return 'cds-alert'
    if (props.hasHoldStatus) return 'critical-hold'
    if (props.criticalValues.length > 0) return 'critical-value'
  }
  if (hasAnyAcknowledgment.value) return 'acknowledgment'
  return null
})

const shouldShowBanner = computed(() => {
  if (transientState.value) return true
  if (props.isRejected) return true
  if (pendingReflexAlert.value) return true
  if (hasUnacknowledgedAlerts.value) return true
  if (props.qcHoldMessage) return true
  if (props.criticalValues.length > 0) return true
  if (hasAnyAcknowledgment.value) return true
  return false
})

const bannerTitle = computed(() => {
  switch (bannerType.value) {
    case 'success':
      return 'ACTION COMPLETED'
    case 'denied':
      return 'ACTION DECLINED'
    case 'rejected':
      return 'REQUEST REJECTED: READ-ONLY'
    case 'qc-hold':
      return 'QC LOCKOUT ACTIVE'
    case 'critical-hold':
      return 'CRITICAL HOLD: ACTION REQUIRED'
    case 'critical-value':
      return 'CRITICAL RESULT: ACTION REQUIRED'
    case 'cds-alert':
      return pendingReflexAlert.value
        ? 'REFLEX TEST RECOMMENDED'
        : 'CDS RULE EXCEPTION: ACTION REQUIRED'
    case 'acknowledgment':
      return 'RULES ACKNOWLEDGED'
    default:
      return ''
  }
})

const bannerSubtitle = computed(() => {
  switch (bannerType.value) {
    case 'success':
      return transientState.value?.message || 'Action completed successfully'
    case 'denied':
      return transientState.value?.message || 'Action has been declined'
    case 'rejected':
      return props.latestRejectedAt 
        ? `No further actions allowed. Rejected on ${new Date(props.latestRejectedAt).toLocaleString()}`
        : 'No further actions allowed.'
    case 'qc-hold':
      return `Reason: ${props.qcHoldMessage}`
    case 'critical-hold':
      return 'Delta check violation detected. Action required before proceeding.'
    case 'critical-value':
      return 'Critical value detected. Acknowledgment required before validation.'
    case 'cds-alert':
      if (pendingReflexAlert.value) {
        // Concise format: "Rule: HGB < 5 (1 g/dL). Reflex: Add Reticulocyte Count"
        const ruleName = pendingReflexAlert.value.rule_name || 'CDS Rule'
        const reflexTest = pendingReflexAlert.value.reflex_test_name || 'Reflex Test'
        return `<b>Rule:</b> ${ruleName}. <b>Reflex:</b> Add ${reflexTest}`
      }
      return `Rule: ${unacknowledgedCdsAlert.value?.rule_name || 'CDS Alert'}`
    case 'acknowledgment':
      return 'All alerts and violations have been acknowledged.'
    default:
      return ''
  }
})

const hasActionButtons = computed(() => {
  // No action buttons for transient states
  if (transientState.value) return false
  return bannerType.value === 'cds-alert' && pendingReflexAlert.value
})

const hasAcknowledgeButton = computed(() => {
  // No acknowledge button for transient states
  if (transientState.value) return false
  return hasUnacknowledgedAlerts.value && !pendingReflexAlert.value
})

// Handlers
const handleApprove = async () => {
  if (!pendingReflexAlert.value) return

  if (!isMounted.value) return
  isProcessing.value = true
  try {
    // CRITICAL: Log what we're about to send
    console.error('[REFLEX DEBUG] Approving reflex test with:', {
      requestId: props.requestId,
      rule_id: pendingReflexAlert.value.id,
      reflex_test_id: pendingReflexAlert.value.reflex_test_id,
      pendingReflexAlert: pendingReflexAlert.value
    })
    
    // Use the Laravel API endpoint directly
    await api.post(
      `/laboratory-requests/${props.requestId}/approve-reflex-test`,
      {
        rule_id: pendingReflexAlert.value.id,
        reflex_test_id: pendingReflexAlert.value.reflex_test_id,
        auto_create: false
      }
    )
    if (isMounted.value) {
      ElMessage.success('Reflex test approved successfully')
    }

    if (isMounted.value) {
      emit('approve')
      // Show transient success state
      showTransientSuccess('Reflex test approved successfully')
    }
  } catch (error) {
    console.error('Error approving reflex test:', error)
    const message = error.response?.data?.message || error.message || 'Failed to approve reflex test'
    if (isMounted) {
      ElMessage.error(message)
    }
  } finally {
    if (isMounted) {
      isProcessing.value = false
      emit('refresh')
    }
  }
}

const handleDeny = async () => {
  if (!pendingReflexAlert.value) return
  
  if (!isMounted.value) return
  try {
    // Use the Laravel API endpoint directly
    await api.post(
      `/laboratory-requests/${props.requestId}/deny-reflex-test`,
      {
        rule_id: pendingReflexAlert.value.id,
        reason: ''
      }
    )
    if (isMounted.value) {
      ElMessage.info('Reflex test denied')
    }

    if (isMounted.value) {
      emit('deny')
      emit('refresh')
      // Show transient denied state
      showTransientDenied('Reflex test denied')
    }
  } catch (error) {
    console.error('Error denying reflex test:', error)
    if (isMounted.value) {
      ElMessage.error('Failed to deny reflex test')
    }
  }
}

const handleAcknowledge = async () => {
  if (!isMounted.value) return
  acknowledging.value = true
  try {
    // Note: This component is currently emitting acknowledge event 
    // and expects parent to handle the API call for general acknowledgments.
    // If we want it to handle it internally, we'd need to know which rules to acknowledge.
    emit('acknowledge')
    emit('refresh')
  } catch (error) {
    console.error('Error acknowledging:', error)
    if (isMounted.value) {
      ElMessage.error('Failed to acknowledge')
    }
  } finally {
    if (isMounted.value) {
      acknowledging.value = false
    }
  }
}

// Expose methods to parent component via template ref
defineExpose({
  showTransientSuccess,
  showTransientDenied
})
</script>

<style scoped>
.unified-alert-banner {
  position: relative;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  padding: 10px 24px;
  margin-top: 40px;
  width: 100%;
  box-sizing: border-box;
}

/* Banner Type Styling */
.unified-alert-banner {
  background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
  border: 2px solid #f56c6c;
}

.unified-alert-banner.banner-success {
  background: linear-gradient(135deg, #f0fff2 0%, #e2fbe2 100%);
  border: 2px solid #02c091;
}

.unified-alert-banner.banner-denied {
  background: linear-gradient(135deg, #f4f4f5 0%, #e9e9eb 100%);
  border: 2px solid #909399;
}

.unified-alert-banner.banner-type-acknowledgment {
  background: linear-gradient(135deg, #f0fff2 0%, #e2fbe2 100%);
  border: 2px solid #02c091;
}

/* Action Required States (Red for cds-alert, critical-hold, critical-value) */
.unified-alert-banner.banner-action-required {
  background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
  border: 2px solid #f56c6c;
  animation: pulse-warning 2s ease-in-out infinite;
}

@keyframes pulse-warning {
  0%, 100% {
    box-shadow: 0 4px 12px rgba(224, 3, 3, 0.5);
  }
  50% {
    box-shadow: 0 4px 20px rgba(243, 178, 178, 0.7);
  }
}

.unified-alert-banner.banner-action-required .banner-icon {
  background: rgba(245, 108, 108, 0.15);
  color: #f56c6c;
}

.banner-header {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.banner-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(245, 108, 108, 0.1);
  color: #f56c6c;
}

.banner-success .banner-icon {
  background: rgba(3, 209, 3, 0.1);
  color: #02c091;
}

.banner-denied .banner-icon {
  background: rgba(144, 147, 153, 0.1);
  color: #909399;
}

.banner-type-acknowledgment .banner-icon {
  background: rgba(64, 158, 255, 0.1);
  color: #409eff;
}

.banner-title-section {
  flex: 1;
  min-width: 200px;
}

.banner-title {
  font-size: 18px;
  font-weight: bold;
  color: #303133;
  margin-bottom: 4px;
  text-align: left;
}

.banner-subtitle {
  font-size: 13px;
  color: #606266;
  text-align: left;
}

.banner-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.banner-actions :deep(.el-button) {
  border-radius: 8px;
  font-weight: 700;
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 0.3px;
  border: 1px solid rgba(94, 92, 92, 0.1) !important;
}

.banner-actions :deep(.el-button-group) {
  display: flex;
  gap: 8px;
  background: transparent;
  box-shadow: none;
}

.expand-btn {
  margin-left: auto;
  transform: rotate(0deg);
  transition: transform 0.3s;
}

.expand-btn.expanded {
  transform: rotate(180deg);
}

.banner-details {
  background: rgba(255, 255, 255, 0.8);
  border-top: 1px solid currentColor;
  padding: 16px 24px;
  margin-top: 12px;
  border-radius: 0 0 8px 8px;
}

.details-content h4 {
  margin: 0 0 12px 0;
  color: #f56c6c;
  font-size: 16px;
}

.banner-success .details-content h4 {
  color: #409eff;
}

.banner-denied .details-content h4 {
  color: #909399;
}

.banner-type-acknowledgment .details-content h4 {
  color: #409eff;
}

.details-content ul {
  margin: 8px 0;
  padding-left: 20px;
}

.details-content li {
  margin-bottom: 8px;
  line-height: 1.6;
}

.details-note {
  margin-top: 12px;
  font-style: italic;
  color: #909399;
}

.logic-modal-content h4 {
  margin-top: 0;
  color: #f56c6c;
}

.transient-countdown {
  margin-top: 8px;
  width: 100%;
}

.transient-countdown :deep(.el-progress-bar__outer) {
  background-color: rgba(0, 0, 0, 0.1);
  border-radius: 2px;
}

.transient-countdown :deep(.el-progress-bar__inner) {
  border-radius: 2px;
  transition: all 0.3s ease;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .banner-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .banner-actions {
    width: 100%;
    justify-content: flex-start;
    margin-top: 12px;
  }
  
  .banner-actions :deep(.el-button) {
    border-radius: 8px;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.3px;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
  }

  .banner-actions :deep(.el-button-group) {
    display: flex;
    gap: 8px;
    background: transparent;
    box-shadow: none;
  }

  .expand-btn {
    margin-left: 0;
    align-self: flex-end;
  }
}
</style>

