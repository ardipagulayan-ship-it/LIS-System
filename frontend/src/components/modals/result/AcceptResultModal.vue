<template>
  <el-dialog
    :model-value="visible"
    @update:model-value="$emit('update:visible', $event)"
    width="850px"
    class="premium-modal"
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
            <h3 class="header-title">Safety & Quality Protocol Review</h3>
            <p class="header-subtitle">Comprehensive investigation and result acknowledgment</p>
          </div>
        </div>
        <el-button class="close-btn" @click="$emit('update:visible', false)" circle>
          <el-icon><Close /></el-icon>
        </el-button>
      </div>
    </template>

    <div class="modal-body-premium no-padding">
      <!-- Violations Summary Section (Merged from ViolationsSummaryModal) -->
      <div v-if="hasViolations" class="violations-summary-section">
        <div class="tab-content-premium">
          <!-- QC & Instrument Alerts -->
          <div v-if="qcHoldMessage || (instrumentFlags && instrumentFlags.length > 0)" class="violation-section">
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
          <div v-if="criticalValues && criticalValues.length > 0" class="violation-section">
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
          <div v-if="flaggedDeltaChecks && flaggedDeltaChecks.length > 0" class="violation-section">
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
          <div v-if="cdsAlerts && cdsAlerts.length > 0" class="violation-section">
            <div class="section-header-v2 danger">
              <el-icon><Operation /></el-icon> CDS Rule Triggers
            </div>
            <div class="section-content-v2">
              <div v-for="alert in cdsAlerts" :key="alert.id" class="cds-alert-item-v2" :class="{ blocking: alert.is_blocking }">
                <div class="alert-icon-v2">
                  <el-icon><Warning v-if="alert.is_blocking" /><InfoFilled v-else /></el-icon>
                </div>
                <div class="alert-body-v2">
                  <div class="rule-name-v2">{{ alert.rule_name }} <el-tag v-if="alert.is_blocking" type="danger" size="small" effect="dark">BLOCKING</el-tag></div>
                  <div class="rule-msg-v2">{{ alert.alert_message }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-body-premium">
        <el-form 
          :model="form" 
          :rules="rules" 
          ref="formRef" 
          label-position="top"
          class="premium-form"
        >
          <div class="form-grid">
            <!-- Signatories Section -->
            <div class="form-section">
              <h4 class="section-title">Review Details</h4>
              <div class="field-row">
                <el-form-item label="Reviewer" prop="reviewer_id">
                  <el-select v-model="form.reviewer_id" placeholder="Select Reviewer" class="premium-input-v2" disabled>
                    <el-option 
                      :label="currentUser?.name || 'Current User'" 
                      :value="currentUser?.id" 
                    />
                  </el-select>
                </el-form-item>
                <el-form-item label="Reviewed At" prop="reviewed_at">
                  <el-input v-model="form.reviewed_at" disabled class="premium-input-v2" />
                </el-form-item>
              </div>
            </div>

            <!-- Investigation Section -->
            <div class="form-section">
              <h4 class="section-title">Investigation Protocol</h4>
              <el-form-item label="Standard Investigation Checklist" prop="investigation_checklist">
                <el-checkbox-group v-model="form.investigation_checklist" class="premium-checkbox-group columns">
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
                  <el-select v-model="form.accept_reason" placeholder="Select Reason" class="premium-input-v2">
                    <el-option v-for="opt in combinedActionOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
                  </el-select>
                </el-form-item>
                <el-form-item label="Pre-Analytical Specimen Status" prop="preanalytical_status">
                  <el-select v-model="form.preanalytical_status" placeholder="Select Status" class="premium-input-v2">
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
                />
              </el-form-item>

              <div class="acknowledgment-check-premium" v-if="hasViolations">
                <el-form-item prop="acknowledged">
                  <el-checkbox v-model="form.acknowledged">
                    <span class="check-text-premium">I formally acknowledge that I have reviewed all violations and taken appropriate clinical actions as documented above.</span>
                  </el-checkbox>
                </el-form-item>
              </div>
            </div>
          </div>
        </el-form>
      </div>
    </div>

    <template #footer>
      <div class="modal-footer-premium">
        <el-button @click="$emit('update:visible', false)" class="premium-button footer-cancel">Cancel</el-button>
        <el-button 
          type="primary" 
          :loading="loading" 
          @click="submit" 
          class="premium-button footer-submit"
        >
          <el-icon><Check /></el-icon>
          <span>Confirm Review & Acknowledge</span>
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { 
  Check, 
  Close, 
  WarningFilled, 
  Lock, 
  Warning, 
  Refresh, 
  Right, 
  Operation, 
  InfoFilled 
} from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import api from '@/utils/axios';

const props = defineProps({
  visible: Boolean,
  loading: Boolean,
  requestDetails: Object,
  currentUser: Object,
  flaggedDeltaChecks: {
    type: Array,
    default: () => []
  },
  // Added props from ViolationsSummaryModal
  qcHoldMessage: String,
  instrumentFlags: {
    type: Array,
    default: () => []
  },
  criticalValues: {
    type: Array,
    default: () => []
  },
  cdsAlerts: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:visible', 'submit', 'validate', 'acknowledge']);

const formRef = ref(null);
const form = reactive({
  reviewer_id: props.currentUser?.id || null,
  reviewed_at: '',
  investigation_checklist: [],
  accept_reason: '',
  rationale: '',
  preanalytical_status: '',
  acknowledged: false
});

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

const hasViolations = computed(() => {
  return !!(
    props.qcHoldMessage || 
    (props.instrumentFlags && props.instrumentFlags.length > 0) ||
    (props.criticalValues && props.criticalValues.length > 0) ||
    (props.flaggedDeltaChecks && props.flaggedDeltaChecks.length > 0) ||
    (props.cdsAlerts && props.cdsAlerts.length > 0)
  );
});

const getDeltaCheckParameterName = (dc) => {
  return dc.test_parameter?.name || dc.test_parameter?.test_name || 'Parameter';
};

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
      if (hasViolations.value && !value) {
        return callback(new Error('Acknowledgment of violations is required'));
      }
      callback();
    },
    trigger: 'change'
  }]
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
    form.reviewed_at = nowTimestamp();
    form.reviewer_id = props.currentUser?.id || null;
  }
});

const submit = async () => {
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
    if (hasViolations.value) {
      emit('acknowledge', {
        action_taken_code: form.accept_reason,
        action_taken_label: combinedActionOptions.find(o => o.value === form.accept_reason)?.label || null,
        action_comment: form.rationale
      });
    }

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
</script>

<style scoped>
.modal-premium-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 32px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.modal-premium-header.dark {
  background: #0f172a;
  color: white;
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
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.brand-logo-wrapper.danger {
  background: #ef4444;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.header-title {
  font-size: 18px;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.dark .header-title { color: white; }

.header-subtitle {
  font-size: 13px;
  color: #64748b;
  margin: 4px 0 0 0;
}

.dark .header-subtitle { color: #94a3b8; }

.close-btn {
  background: #f1f5f9 !important;
  border: none !important;
  color: #64748b !important;
}

.dark .close-btn {
  background: rgba(255, 255, 255, 0.1) !important;
  color: white !important;
}

.close-btn:hover {
  background: #fee2e2 !important;
  color: #ef4444 !important;
}

.modal-body-premium {
  padding: 32px;
  background: white;
}

.no-padding { padding: 0 !important; }

.violations-summary-section {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.tab-content-premium {
  padding: 24px 32px;
  max-height: 40vh;
  overflow-y: auto;
}

.violation-section {
  margin-bottom: 24px;
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

.premium-alert-v2 {
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
}

.alert-title-v2 {
  font-weight: 800;
  font-size: 13px;
}

.alert-desc-v2 {
  margin: 8px 0 0 0;
}

.premium-table-v2 {
  border-radius: 12px;
  overflow: hidden;
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

.form-section {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 24px;
}

.section-title {
  font-size: 12px;
  font-weight: 800;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0 0 16px 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.field-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.premium-input-v2 :deep(.el-input__wrapper) {
  background: white;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  border-radius: 8px;
  padding: 4px 12px;
}

.premium-textarea :deep(.el-textarea__inner) {
  background: white;
  border-radius: 8px;
  padding: 12px;
  border: 1px solid #e2e8f0;
}

.premium-checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.premium-checkbox-group.columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
}

.acknowledgment-check-premium {
  margin-top: 20px;
  padding: 16px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 8px;
}

.check-text-premium {
  font-weight: 700;
  color: #92400e;
  font-size: 13px;
  white-space: normal;
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
  transition: all 0.2s;
}

.footer-cancel {
  background: white;
  border: 1px solid #e2e8f0;
  color: #64748b;
}

.footer-submit {
  background: #2563eb;
  border: none;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.footer-submit:hover {
  background: #1d4ed8;
  transform: translateY(-1px);
}
</style>
