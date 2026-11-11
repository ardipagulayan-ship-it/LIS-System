<template>
  <el-dialog
    :model-value="visible"
    @update:model-value="$emit('update:visible', $event)"
    width="900px"
    :show-close="false"
    destroy-on-close
  >
    <!-- Premium Header -->
    <template #header>
      <div class="modal-premium-header dark">
        <div class="header-left">
          <div class="brand-logo-wrapper danger">
            <el-icon><WarningFilled /></el-icon>
          </div>
          <div class="header-text">
            <h3 class="header-title">Violations & Protocol Review</h3>
            <p class="header-subtitle">Comprehensive safety and quality audit summary</p>
          </div>
        </div>
        <div class="header-right">
          <!-- <el-button 
            type="info" 
            plain 
            size="small" 
            @click="exportToSheets"
            class="header-action-btn"
          >
            <el-icon><DocumentCopy /></el-icon> Export Summary
          </el-button> -->
          <el-button class="close-btn" @click="$emit('update:visible', false)">
            <el-icon><Close /></el-icon>
          </el-button>
        </div>
      </div>
    </template>

    <div class="modal-body-premium no-padding">
      <el-tabs v-model="activeTab" class="premium-tabs-v2">
        <!-- Summary Tab -->
        <el-tab-pane label="Violations Summary" name="summary">
          <div class="tab-content-premium">
            <!-- QC & Instrument Alerts -->
            <div v-if="qcHoldMessage || hasInstrumentFlags" class="violation-section">
              <div class="section-header-v2 danger">
                <el-icon><Lock /></el-icon> QC & Instrument Status
              </div>
              <div class="section-content-v2">
                <el-alert v-if="qcHoldMessage" type="error" show-icon :closable="false" class="premium-alert-v2">
                  <template #title>
                    <span class="alert-title-v2">Failing QC Run Detected</span>
                  </template>
                  <p class="alert-desc-v2">The following equipment is currently on QC Hold: <b>{{ qcHoldMessage }}</b>. Results from these analyzers are unreliable and validation is locked.</p>
                </el-alert>
                <div v-for="(flag, i) in instrumentFlags" :key="i" class="instrument-flag-item">
                  <el-icon class="flag-icon"><Warning /></el-icon>
                  <span>{{ flag }}</span>
                </div>
              </div>
            </div>

            <!-- Critical Values -->
            <div v-if="criticalValues.length > 0" class="violation-section">
              <div class="section-header-v2 warning">
                <el-icon><WarningFilled /></el-icon> Critical Value Alerts
              </div>
              <div class="section-content-v2">
                <el-table :data="criticalValues" border size="small" class="premium-table-v2">
                  <el-table-column prop="parameter" label="Parameter" width="180" />
                  <el-table-column label="Result" width="120" align="center">
                    <template #default="{row}">
                      <b class="critical-text">{{ row.value }} </b> <i>{{ row.unit }}</i>
                    </template>
                  </el-table-column>
                  <el-table-column prop="refRange" label="Ref Range" width="130" align="center"/>
                  <el-table-column prop="message" label="Violation Detail" />
                </el-table>
              </div>
            </div>

            <!-- Delta Checks -->
            <div v-if="flaggedDeltaChecks.length > 0" class="violation-section">
              <div class="section-header-v2 danger">
                <el-icon><Refresh /></el-icon> Delta Check Violations
              </div>
              <div class="section-content-v2">
                <el-table :data="flaggedDeltaChecks" border size="small" class="premium-table-v2">
                  <el-table-column label="Parameter" width="180">
                    <template #default="{row}">
                      {{ getDeltaCheckParameterName(row) }}
                    </template>
                  </el-table-column>
                  <el-table-column label="Comparison" width="220" align="center">
                    <template #default="{row}" >
                      <div class="delta-compare-v2" >
                        <span class="prev">{{ row.previous_value }}</span>
                        <el-icon><Right /></el-icon>
                        <span class="curr">{{ row.current_value }}</span>
                      </div>
                    </template>
                  </el-table-column>
                  <el-table-column label="Change" width="110" align="center">
                    <template #default="{row}">
                      <el-tag :type="row.percentage_difference > 0 ? 'danger' : 'warning'" size="small">
                        {{ row.percentage_difference?.toFixed(1) }}%
                      </el-tag>
                    </template>
                  </el-table-column>
                  <el-table-column label="Violation Detail">
                    <template #default="{row}">
                      {{ row.reason_on_violation || 'Delta limit exceeded' }}
                    </template>
                  </el-table-column>
                </el-table>
              </div>
            </div>

            <!-- CDS Rules -->
            <div v-if="cdsAlerts.length > 0" class="violation-section">
              <div class="section-header-v2 danger">
                <el-icon><Operation /></el-icon> CDS Rule Triggers
              </div>
              <div class="section-content-v2">
                <div v-for="alert in cdsAlerts" :key="alert.id" class="cds-alert-item-v2" :class="{ blocking: alert.is_blocking, 'reflex-alert': alert.action_type === 'REFLEX_TEST' }">
                  <div class="alert-icon-v2">
                    <el-icon><Warning v-if="alert.is_blocking" /><InfoFilled v-else /></el-icon>
                  </div>
                  <div class="alert-body-v2">
                    <div class="rule-name-v2">
                      {{ alert.rule_name }}
                      <el-tag v-if="alert.is_blocking" type="danger" size="small" effect="dark">BLOCKING</el-tag>
                      <el-tag v-else-if="alert.action_type === 'REFLEX_TEST'" type="warning" size="small" effect="dark">REFLEX TEST</el-tag>
                    </div>
                    <div class="rule-msg-v2">{{ alert.alert_message }}</div>
                    
                    <!-- Action Buttons for Reflex Tests -->
                    <div v-if="alert.action_type === 'REFLEX_TEST' && !alert.is_acknowledged && !alert.reflex_created" class="reflex-actions">
                      <el-button
                        size="small"
                        type="success"
                        @click="approveReflexTest(alert)"
                        class="reflex-approve-btn"
                      >
                        <el-icon><Check /></el-icon> Approve
                      </el-button>
                      <el-button
                        size="small"
                        type="info"
                        plain
                        @click="denyReflexTest(alert)"
                        class="reflex-deny-btn"
                      >
                        <el-icon><CircleClose /></el-icon> Deny
                      </el-button>
                    </div>
                    <div v-else-if="alert.action_type === 'REFLEX_TEST' && alert.reflex_created" class="reflex-status">
                      <el-tag type="success" size="small">Approved & Ordered</el-tag>
                    </div>
                    <div v-else-if="alert.action_type === 'REFLEX_TEST' && alert.reflex_denied" class="reflex-status">
                      <el-tag type="info" size="small">Denied</el-tag>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </el-tab-pane>

        <!-- Audit Trail Tab -->
        <el-tab-pane label="Protocol History & Audit Trail" name="audit">
          <div class="tab-content-premium audit-trail-container" v-loading="auditLoading">
            <div v-if="auditTrail.length === 0" class="empty-audit-v2">
              <el-icon><Memo /></el-icon>
              <p>No recorded protocol history found for this request.</p>
            </div>
            <el-timeline v-else class="premium-timeline-v2">
              <el-timeline-item
                v-for="(log, index) in auditTrail"
                :key="index"
                :timestamp="formatDateTime(log.created_at)"
                :type="getAuditType(log.rule_type)"
                size="large"
              >
                <div class="audit-card-v2" :class="{ 'decision-acknowledged': log.decision?.toLowerCase() === 'acknowledged' }">
                  <div class="audit-header-v2">
                    <span class="audit-user-v2"><el-icon><User /></el-icon> {{ log.user?.name || 'System' }}</span>
                    <el-tag :type="getAuditDecisionType(log.decision)" size="small" effect="dark">{{ log.decision?.toUpperCase() }}</el-tag>
                  </div>
                  <div class="audit-body-v2">
                    <div class="audit-type-v2">{{ log.rule_type?.replace('_', ' ') }} Review</div>
                    
                    <!-- Enhanced Action Display -->
                    <div v-if="log.action_taken_label || log.context?.action_taken_label || log.context?.action_taken_code || log.context?.accept_reason" class="audit-action-highlight">
                      <div class="action-label">PRIMARY ACTION</div>
                      <div class="action-content">
                        {{ log.action_taken_label || log.context?.action_taken_label || log.context?.action_taken_code || log.context?.accept_reason }}
                        <span v-if="log.action_taken_other || log.context?.action_taken_other" class="action-other-text">
                          – {{ log.action_taken_other || log.context?.action_taken_other }}
                        </span>
                      </div>
                    </div>
                    
                    <div v-if="log.action_comment || log.context?.action_comment || log.context?.rationale" class="audit-comment-highlight">
                      <div class="action-label">PROFESSIONAL NARRATIVE</div>
                      <div class="action-content">"{{ log.action_comment || log.context?.action_comment || log.context?.rationale }}"</div>
                    </div>
                  </div>
                </div>
              </el-timeline-item>
            </el-timeline>
          </div>
        </el-tab-pane>

        <!-- Investigation & Acknowledgment Tab -->
        <el-tab-pane label="Investigation & Acknowledgment" name="investigation">
          <div class="tab-content-premium acknowledge-form-container">
            <div class="form-section">
              <h4 class="section-title">Review & Action Taken</h4>
              <el-form 
                :model="form"
                :rules="rules"
                ref="formRef"
                label-position="top" 
                class="premium-form"
                :disabled="isAcknowledged"
              >
                <div class="form-grid">
                  <!-- Investigation Section -->
                  <div class="form-section">
                    <h4 class="section-title">Investigation Protocol</h4>
                    <el-form-item label="Standard Investigation Checklist" prop="investigation_checklist">
                      <el-checkbox-group v-model="form.investigation_checklist" class="premium-checkbox-group columns" :disabled="isAcknowledged">
                        <el-checkbox 
                          v-for="opt in investigationChecklistOptions" 
                          :key="opt.value" 
                          :label="opt.value"
                        >
                          {{ opt.label }}
                        </el-checkbox>
                      </el-checkbox-group>
                    </el-form-item>

                    <div class="field-row">
                      <el-form-item label="Reason for Acceptance / Primary Action" prop="accept_reason">
                        <el-select v-model="form.accept_reason" placeholder="Select Reason" class="premium-input-v2" :disabled="isAcknowledged">
                          <el-option v-for="opt in combinedActionOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
                        </el-select>
                      </el-form-item>
                      <el-form-item label="Pre-Analytical Specimen Status" prop="preanalytical_status">
                        <el-select v-model="form.preanalytical_status" placeholder="Select Status" class="premium-input-v2" :disabled="isAcknowledged">
                          <el-option v-for="opt in preanalyticalStatusOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
                        </el-select>
                      </el-form-item>
                    </div>
                  </div>

                  <!-- Rationale Section -->
                  <div class="form-section">
                    <h4 class="section-title">Professional Narrative & Acknowledgment</h4>
                    <el-form-item label="Detailed Clinical Rationale" prop="rationale">
                      <el-input 
                        v-model="form.rationale" 
                        type="textarea" 
                        rows="4" 
                        placeholder="Submit your detailed professional narrative for accepting results and acknowledging violations (minimum 30 characters)..."
                        class="premium-textarea"
                        :disabled="isAcknowledged"
                      />
                    </el-form-item>

                    <div class="acknowledgment-check-premium">
                      <el-form-item prop="acknowledged">
                        <el-checkbox v-model="form.acknowledged" :disabled="isAcknowledged">
                          <span class="check-text-premium">I formally acknowledge that I have reviewed all violations and taken appropriate clinical actions as documented above.</span>
                        </el-checkbox>
                      </el-form-item>
                    </div>
                  </div>
                </div>
              </el-form>
            </div>
          </div>
        </el-tab-pane>

      </el-tabs>
    </div>

    <template #footer>
      <div class="modal-footer-premium">
        <el-button @click="$emit('update:visible', false)" class="premium-button footer-cancel">Cancel</el-button>
        <el-button 
          v-if="activeTab !== 'investigation'"
          type="primary" 
          @click="activeTab = 'investigation'" 
          class="premium-button footer-submit"
        >
          <el-icon><ArrowRight /></el-icon>
          <span>Proceed to Investigation</span>
        </el-button>
        <el-button 
          v-else-if="!isAcknowledged"
          type="success" 
          @click="submitReview"
          class="premium-button footer-submit-ack"
        >
          <el-icon><Check /></el-icon>
          <span>Submit Review & Acknowledgment</span>
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';
import {
  WarningFilled,
  Close,
  DocumentCopy,
  Lock,
  Warning,
  Refresh,
  Right,
  Operation,
  InfoFilled,
  Memo,
  User,
  Check,
  CircleClose
} from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { format, parseISO } from 'date-fns';
import api from '@/utils/axios';

const props = defineProps({
  visible: Boolean,
  activeViolationTab: {
    type: String,
    default: 'summary'
  },
  requestDetails: Object,
  requestId: {
    type: [String, Number],
    default: null
  },
  currentUser: Object, // Added from AcceptResultModal
  qcHoldMessage: String,
  instrumentFlags: {
    type: Array,
    default: () => []
  },
  criticalValues: {
    type: Array,
    default: () => []
  },
  flaggedDeltaChecks: {
    type: Array,
    default: () => []
  },
  cdsAlerts: {
    type: Array,
    default: () => []
  },
  auditTrail: {
    type: Array,
    default: () => []
  },
  auditLoading: Boolean,
  isAcknowledged: Boolean
});

const emit = defineEmits(['update:visible', 'acknowledge', 'export', 'fetchAudit', 'validate', 'reflex-action-taken']); // Added 'validate' and 'reflex-action-taken'

const activeTab = ref(props.activeViolationTab);
const formRef = ref(null); // Added for the new form

const form = reactive({
  // From AcceptResultModal
  reviewer_id: props.currentUser?.id || null,
  reviewed_at: '',
  investigation_checklist: [],
  accept_reason: '',
  rationale: '',
  preanalytical_status: '',
  // From original acknowledgment form
  acknowledged: false,
  actionTaken: '',
  actionOther: '',
  actionDetails: ''
});

// Options from AcceptResultModal
const investigationChecklistOptions = [
  { label: 'Reviewed Patient History', value: 'reviewed_patient_history' },
  { label: 'Checked QC Status', value: 'checked_qc_status' },
  { label: 'Verified Specimen Integrity', value: 'verified_specimen_integrity' },
  { label: 'Repeated Analysis Performed', value: 'repeated_analysis' }
];

const combinedActionOptions = [
  { label: 'Clinically Plausible (known condition)', value: 'clinically_plausible' },
  { label: 'Repeat/Re-run Confirmed', value: 'repeat_confirmed' },
  { label: 'No Pre-Analytical Error Found', value: 'no_preanalytical_error' },
  { label: 'Discussed with Clinician/Ward', value: 'discussed_clinician' },
  { label: 'Reviewed with Pathologist', value: 'reviewed_pathologist' },
  { label: 'Specimen Recollected and Re-tested', value: 'recollected_specimen' }
];

const preanalyticalStatusOptions = [
  { label: 'Clear / Optimal', value: 'clear' },
  { label: 'Mild Hemolysis', value: 'mild_hemolysis' },
  { label: 'Moderate Lipemia', value: 'moderate_lipemia' },
  { label: 'Icteric', value: 'icteric' }
];

// Rules from AcceptResultModal
const rules = {
  reviewer_id: [{ required: true, message: 'Reviewer is required', trigger: 'change' }],
  investigation_checklist: [{ type: 'array', required: true, message: 'Select at least one item', trigger: 'change' }],
  accept_reason: [{ required: true, message: 'Primary action is required', trigger: 'change' }],
  rationale: [
    { required: true, message: 'Detailed rationale is required', trigger: 'blur' },
    { min: 30, message: 'Rationale must be at least 30 characters', trigger: 'blur' }
  ],
  preanalytical_status: [{ required: true, message: 'Status is required', trigger: 'change' }],
  acknowledged: [{
    validator: (rule, value, callback) => {
      if (!value) {
        return callback(new Error('Acknowledgment of violations is required'));
      }
      callback();
    },
    trigger: 'change'
  }]
};

const actionOptions = [
  { label: 'Repeat test performed', value: 'REPEAT_TEST' },
  { label: 'Result verified against previous history', value: 'VERIFY_HISTORY' },
  { label: 'Result verified with instrument QC/calibration', value: 'VERIFY_QC' },
  { label: 'Result discussed with clinician/ward', value: 'DISCUSS_CLINICIAN' },
  { label: 'Result reviewed with pathologist', value: 'REVIEW_PATHOLOGIST' },
  { label: 'Specimen recollected and re-tested', value: 'RECOLLECT_SPECIMEN' },
  { label: 'Result accepted as is; no further action', value: 'ACCEPT_AS_IS' },
  { label: 'Other (specify)', value: 'OTHER' }
];

const hasInstrumentFlags = computed(() => props.instrumentFlags.length > 0);

const getDeltaCheckParameterName = (dc) => {
  return dc.test_parameter?.name || dc.test_parameter?.test_name || 'Parameter';
};

const formatDateTime = (data) => {
  if (!data) return 'N/A';
  try {
    return format(parseISO(data), 'yyyy-MM-dd p');
  } catch (e) {
    return 'Invalid Date';
  }
};

const getAuditType = (type) => {
  switch (type) {
    case 'CDS': return 'success';
    case 'DELTA_CHECK': return 'primary';
    case 'CRITICAL_VALUE': return 'danger';
    default: return 'info';
  }
};

const getAuditDecisionType = (decision) => {
  switch (decision?.toLowerCase()) {
    case 'acknowledged': return 'success';
    case 'rejected': return 'danger';
    case 'accepted': return 'primary';
    default: return 'info';
  }
};

const exportToSheets = () => {
  emit('export');
};

// Reflex test approval methods
const approveReflexTest = async (alert) => {
  try {
    await api.post(
      `/laboratory-requests/${props.requestId}/approve-reflex-test`,
      {
        rule_id: alert.id || alert.rule_id,
        reflex_test_id: alert.reflex_test_id,
        auto_create: false
      }
    );
    
    ElMessage.success('Reflex test approved and ordered');
    
    // Mark alert as processed
    alert.reflex_created = true;
    alert.is_acknowledged = true;
    
    // Emit event to parent to refresh
    emit('reflex-action-taken');
    
  } catch (error) {
    ElMessage.error('Failed to approve reflex test: ' + (error.response?.data?.message || error.message));
  }
};

const denyReflexTest = async (alert) => {
  try {
    await ElMessageBox.confirm(
      'Are you sure you want to deny this reflex test? This action cannot be undone.',
      'Deny Reflex Test',
      {
        confirmButtonText: 'Deny',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }
    );
    
    await api.post(
      `/laboratory-requests/${props.requestId}/deny-reflex-test`,
      {
        rule_id: alert.id || alert.rule_id,
        reason: '' // Could be extended with optional reason input
      }
    );
    
    ElMessage.info('Reflex test denied');
    
    // Mark alert as denied
    alert.is_acknowledged = true;
    alert.reflex_denied = true;
    
    emit('reflex-action-taken');
    
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('Failed to deny reflex test');
    }
  }
};

const prePopulateForm = () => {
  if (props.isAcknowledged && props.auditTrail.length > 0) {
    // Find the most recent acknowledgment log
    const ackLog = [...props.auditTrail].reverse().find(log => log.decision?.toLowerCase() === 'acknowledged');
    if (ackLog) {
      // Use context if available, otherwise check root of log for flattened properties
      const ctx = ackLog.context || {};
      
      form.acknowledged = true;
      form.accept_reason = ctx.accept_reason || ctx.action_taken_code || ackLog.action_taken_code || '';
      form.rationale = ctx.rationale || ctx.action_comment || ackLog.action_comment || '';
      form.investigation_checklist = ctx.investigation_checklist || ackLog.investigation_checklist || [];
      form.preanalytical_status = ctx.preanalytical_status || ackLog.preanalytical_status || '';
    }
  }
};

const submitReview = async () => {
  if (!formRef.value) return;
  
  try {
    await formRef.value.validate();
  } catch (error) {
    return;
  }

  try {
    // 1. Submit the acceptance (Delta Check flow) ONLY if there are flagged results
    if (props.flaggedDeltaChecks && props.flaggedDeltaChecks.length > 0) {
      const acceptPayload = {
        reviewer_id: form.reviewer_id,
        reviewed_at: form.reviewed_at,
        investigation_checklist: form.investigation_checklist,
        accept_reason: form.accept_reason,
        accept_reason_label: (combinedActionOptions.find(opt => opt.value === form.accept_reason)?.label) || null,
        rationale: form.rationale,
        preanalytical_status: form.preanalytical_status,
        flagged_results: props.flaggedDeltaChecks.map(dc => ({
          test_result_id: dc.test_result?.id,
          test_parameter_id: dc.test_parameter?.id,
          original_value: dc.current_value,
          delta_check_id: dc.id
        }))
      };

      await api.post(`/laboratory-requests/${props.requestDetails.id}/delta-check/accept`, acceptPayload);
    }

    // 2. Submit the acknowledgment event for all unacknowledged violations (CDS, Delta, Critical)
    emit('acknowledge', {
      action_taken_code: form.accept_reason,
      action_taken_label: combinedActionOptions.find(o => o.value === form.accept_reason)?.label || null,
      action_comment: form.rationale
    });

    ElMessage.success('Review protocol completed and violations acknowledged');
    
    // Reset form
    form.accept_reason = '';
    form.rationale = '';
    form.investigation_checklist = [];
    form.preanalytical_status = '';
    form.acknowledged = false;

    emit('validate');
    emit('update:visible', false);
  } catch (error) {
    console.error('Error in review protocol:', error);
    ElMessage.error(`Failed to complete protocol: ${error.response?.data?.message || error.message}`);
  }
};

const nowTimestamp = () => {
  const now = new Date();
  return now.getFullYear() + '-' + 
         String(now.getMonth() + 1).padStart(2, '0') + '-' + 
         String(now.getDate()).padStart(2, '0') + ' ' + 
         String(now.getHours()).padStart(2, '0') + ':' + 
         String(now.getMinutes()).padStart(2, '0') + ':' + 
         String(now.getSeconds()).padStart(2, '0');
};

watch(() => props.visible, (newVal) => {
  if (newVal) {
    activeTab.value = props.activeViolationTab;
    form.reviewed_at = nowTimestamp();
    form.reviewer_id = props.currentUser?.id || null;
    
    // Always fetch audit trail if it's acknowledged to pre-populate correctly
    if (props.isAcknowledged || activeTab.value === 'audit') {
      emit('fetchAudit');
    }
    
    if (props.isAcknowledged) {
      prePopulateForm();
    }
  }
});

watch(activeTab, (newTab) => {
  if (newTab === 'audit' && props.auditTrail.length === 0) {
    emit('fetchAudit');
  }
});

watch(() => props.auditTrail, () => {
  if (props.isAcknowledged) {
    prePopulateForm();
  }
}, { deep: true });
</script>

<style scoped>
.modal-premium-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-md);
  padding: var(--spacing-md) var(--spacing-xl);
  background: var(--color-dark);
  color: var(--color-text-light);
  margin: -20px -20px 0 -20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: var(--border-radius-md) var(--border-radius-md) 0 0;
}

.modal-premium-header.dark {
  background: var(--color-dark);
  color: var(--color-text-light);
  border-bottom: none;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.brand-logo-wrapper {
  width: 44px;
  height: 44px;
  background: #2563eb;
  color: white;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.brand-logo-wrapper.danger {
  background: #ef4444;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.header-text .header-title {
  color: inherit;
  font-size: 18px;
  font-weight: 800;
  margin: 0;
}

.header-text .header-subtitle {
  color: #94a3b8;
  font-size: 13px;
  margin: 4px 0 0 0;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.close-btn  {
  color: #f8fafc !important;
  background: rgba(255, 255, 255, 0.1) !important;
  border: none !important;
  transition: all 0.2s;
  border-radius: 8px;
  padding:10px
}

.close-btn:hover {
  background: #dc2626 !important;
  transform: rotate(90deg);
}

.tab-content-premium {
  padding: 24px 32px;
  max-height: 60vh;
  overflow-y: auto;
}

.violation-section {
  margin-bottom: 32px;
}

.section-header-v2 {
  font-size: 12px;
  font-weight: 800;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding-bottom: 8px;
  border-bottom: 2px solid #f1f5f9;
}

.section-header-v2.danger { color: #ef4444; border-color: #fee2e2; }
.section-header-v2.warning { color: #f59e0b; border-color: #fef3c7; }
.section-header-v2.info { color: #3b82f6; border-color: #dbeafe; }
.section-header-v2.success { color: #10b981; border-color: #d1fae5; }

.premium-alert-v2 {
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
}

.alert-title-v2 {
  font-weight: 800;
  font-size: 13px;
}

.success-soft {
  background: #f0fdf4 !important;
  border: 1px solid #bbf7d0 !important;
  color: #166534 !important;
}

.already-acknowledged-banner {
  margin-bottom: 20px;
}

.alert-desc-v2 {
  margin: 8px 0 0 0;
}

.premium-table-v2 {
  border-radius: 12px;
  overflow: hidden;
  --el-table-header-bg-color: #f8fafc;
}

.critical-text {
  color: #ef4444;
}

.delta-compare-v2 {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  font-weight: 700;
}

.delta-compare-v2 .prev { color: #64748b; }
.delta-compare-v2 .curr { color: #0f172a; font-size: 13px; }

.cds-alert-item-v2 {
  display: flex;
  gap: 16px;
  padding: 16px;
  background: #f8fafc;
  border-radius: 12px;
  border-left: 4px solid #3b82f6;
  margin-bottom: 12px;
}

.cds-alert-item-v2.blocking {
  background: #fff1f2;
  border-left-color: #ef4444;
}

.alert-icon-v2 {
  font-size: 20px;
  color: #3b82f6;
}

.blocking .alert-icon-v2 { color: #ef4444; }

.rule-name-v2 {
  font-weight: 800;
  font-size: 13px;
  color: #0f172a;
  margin-bottom: 4px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.rule-msg-v2 {
  font-size: 13px;
  color: #475569;
}

/* Reflex Alert Styles */
.cds-alert-item-v2.reflex-alert {
  background: #fefce8;
  border-left-color: #eab308;
}

.reflex-actions {
  display: flex;
  gap: 8px;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #e2e8f0;
}

.reflex-approve-btn {
  background: #10b981 !important;
  border-color: #10b981 !important;
  color: white !important;
}

.reflex-approve-btn:hover {
  background: #059669 !important;
  border-color: #059669 !important;
}

.reflex-deny-btn {
  background: #6b7280 !important;
  border-color: #6b7280 !important;
  color: white !important;
}

.reflex-deny-btn:hover {
  background: #4b5563 !important;
  border-color: #4b5563 !important;
}

.reflex-status {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #e2e8f0;
}

/* Audit Trail Styles */
.audit-card-v2 {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
}

.audit-header-v2 {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.audit-user-v2 {
  font-weight: 800;
  color: #0f172a;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.audit-type-v2 {
  font-size: 12px;
  font-weight: 800;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.audit-action-highlight, .audit-comment-highlight {
  margin-top: 12px;
  padding: 10px 14px;
  background: white;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.audit-action-highlight {
  border-left: 3px solid #3b82f6;
}

.audit-comment-highlight {
  border-left: 3px solid #64748b;
  font-style: italic;
}

.action-label {
  font-size: 12px;
  font-weight: 800;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}

.action-content {
  font-size: 13px;
  color: #1e293b;
  font-weight: 600;
  line-height: 1.5;
}

.action-other-text {
  font-weight: 400;
  color: #64748b;
  font-size: 12px;
}

.decision-acknowledged {
  background: #f0f9ff;
  border-color: #bae6fd;
}

.empty-audit-v2 {
  text-align: center;
  padding: 64px 0;
  color: #94a3b8;
}

.empty-audit-v2 .el-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

/* Acknowledgment Form */
.form-section {
  padding: 0;
}

.premium-checkbox {
  margin-top: 16px;
}

.check-text {
  font-weight: 700;
  color: #1e293b;
  font-size: 13px;
  white-space: normal;
  display: block;
}

.char-countdown {
  font-size: 12px;
  color: #ef4444;
  text-align: right;
  margin-top: 4px;
}

.char-countdown.valid {
  color: #10b981;
}

.modal-footer-premium {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 24px 32px;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}

.premium-button {
  padding: 10px 24px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 13px;
}

.footer-submit-ack {
  background: #2563eb;
  color: white;
  border: none;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.footer-submit-ack:hover {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.no-padding { padding: 0 !important; }

/* Tabs Styling */
.premium-tabs-v2 :deep(.el-tabs__header) {
  margin: 0;
  padding: 0 32px;
  background: #0f172a;
}

.premium-tabs-v2 :deep(.el-tabs__nav-wrap::after) {
  display: none;
}

.premium-tabs-v2 :deep(.el-tabs__item) {
  height: 50px;
  color: #94a3b8;
  font-weight: 700;
  font-size: 13px;
}

.premium-tabs-v2 :deep(.el-tabs__item.is-active) {
  color: white;
}

.premium-tabs-v2 :deep(.el-tabs__active-bar) {
  background-color: #3b82f6;
  height: 3px;
}
</style>
