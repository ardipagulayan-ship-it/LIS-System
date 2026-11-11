<template>
  <el-dialog
    :model-value="visible"
    @update:model-value="$emit('update:visible', $event)"
    width="750px"
    class="premium-modal rejection-modal"
    :show-close="false"
    destroy-on-close
  >
    <!-- Premium Header -->
    <template #header>
      <div class="modal-premium-header warning">
        <div class="header-left">
          <div class="brand-logo-wrapper warning">
            <el-icon><Warning /></el-icon>
          </div>
          <div class="header-text">
            <h3 class="header-title">Result Rejection Protocol</h3>
            <p class="header-subtitle">Formal investigation and rejection documentation</p>
          </div>
        </div>
        <el-button class="close-btn" @click="$emit('update:visible', false)" circle>
          <el-icon><Close /></el-icon>
        </el-button>
      </div>
    </template>

    <div class="modal-body-premium">
      <el-form 
        :model="form" 
        :rules="rules" 
        ref="formRef" 
        label-position="top"
        class="premium-form"
      >
        <!-- Scope and Identification -->
        <div class="form-section highlight">
          <h4 class="section-title">Rejection Scope</h4>
          <div class="field-row">
            <el-form-item label="Target Scope" prop="scope">
              <el-radio-group v-model="form.scope" class="premium-radio-group">
                <el-radio-button label="request">Entire Request</el-radio-button>
                <el-radio-button label="tests">Specific Tests</el-radio-button>
                <el-radio-button label="results">Selected Results</el-radio-button>
              </el-radio-group>
            </el-form-item>
            <el-form-item v-if="form.scope === 'tests'" label="Select Tests" prop="test_ids">
              <el-select 
                v-model="form.test_ids" 
                multiple 
                placeholder="Select tests to reject"
                class="premium-input-v2"
              >
                <el-option 
                  v-for="test in requestDetails?.tests || []" 
                  :key="test.id" 
                  :label="test.name" 
                  :value="test.id" 
                />
              </el-select>
            </el-form-item>
            <div v-else-if="form.scope === 'results'" class="scope-info">
              <el-alert 
                :title="`Rejecting ${selectedResultsCount} selected parameter(s) from the results table.`" 
                type="info" 
                :closable="false" 
                show-icon 
              />
            </div>
          </div>
        </div>

        <div class="form-grid">
          <!-- Signatories Section -->
          <div class="form-section">
            <h4 class="section-title">Investigator Details</h4>
            <div class="field-row">
              <el-form-item label="Reviewing Officer" prop="reviewer_id">
                <el-select v-model="form.reviewer_id" disabled class="premium-input-v2">
                  <el-option :label="currentUser?.name || 'Current User'" :value="currentUser?.id" />
                </el-select>
              </el-form-item>
              <el-form-item label="Investigation Date" prop="reviewed_at">
                <el-input v-model="form.reviewed_at" disabled class="premium-input-v2" />
              </el-form-item>
            </div>
          </div>

          <!-- Investigation Section -->
          <div class="form-section">
            <h4 class="section-title">Investigation Protocol</h4>
            <el-form-item label="Standard Investigation Checklist" prop="investigation_checklist">
              <el-checkbox-group v-model="form.investigation_checklist" class="premium-checkbox-group columns">
                <el-checkbox v-for="opt in investigationChecklistOptions" :key="opt.value" :label="opt.value">
                  {{ opt.label }}
                </el-checkbox>
              </el-checkbox-group>
            </el-form-item>

            <div class="field-row">
              <el-form-item label="Primary Root Cause" prop="root_cause">
                <el-select v-model="form.root_cause" placeholder="Select Root Cause" class="premium-input-v2">
                  <el-option v-for="opt in rootCauseOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
                </el-select>
              </el-form-item>
              <el-form-item label="Corrective Action" prop="action_taken">
                <el-select v-model="form.action_taken" placeholder="Select Action" class="premium-input-v2">
                  <el-option v-for="opt in actionTakenOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
                </el-select>
              </el-form-item>
            </div>
          </div>

          <!-- Documentation Section -->
          <div class="form-section">
            <h4 class="section-title">Formal Documentation</h4>
            <el-form-item label="Rejection Narrative & Comments" prop="rejection_comment">
              <el-input 
                v-model="form.rejection_comment" 
                type="textarea" 
                rows="4" 
                placeholder="Submit your detailed professional rationale for rejection (minimum 50 characters)..."
                class="premium-textarea"
              />
            </el-form-item>
            <el-form-item prop="original_value_canceled">
              <el-checkbox v-model="form.original_value_canceled" class="premium-checkbox">
                Cancel original result value (value will be hidden from reports)
              </el-checkbox>
            </el-form-item>
          </div>
        </div>
      </el-form>
    </div>

    <template #footer>
      <div class="modal-footer-premium">
        <el-button @click="$emit('update:visible', false)" class="premium-button footer-cancel">Cancel</el-button>
        <el-button 
          type="danger" 
          :loading="loading" 
          @click="submit" 
          class="premium-button footer-submit-danger"
        >
          Confirm Rejection
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { Warning, Close } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import api from '@/utils/axios';

const props = defineProps({
  visible: Boolean,
  loading: Boolean,
  requestDetails: Object,
  currentUser: Object,
  selectedTests: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:visible', 'submit', 'validate', 'save']);

const formRef = ref(null);
const form = reactive({
  reviewer_id: props.currentUser?.id || null,
  reviewed_at: '',
  scope: 'request',
  test_ids: [],
  investigation_checklist: [],
  root_cause: '',
  action_taken: '',
  rejection_comment: '',
  original_value_canceled: true
});

const investigationChecklistOptions = [
  { label: 'Reviewed Patient History', value: 'reviewed_patient_history' },
  { label: 'Checked QC Status', value: 'checked_qc_status' },
  { label: 'Verified Specimen Integrity', value: 'verified_specimen_integrity' }
];

const rootCauseOptions = [
  { label: 'Pre-Analytical', value: 'pre_analytical' },
  { label: 'Analytical', value: 'analytical' },
  { label: 'Post-Analytical', value: 'post_analytical' }
];

const actionTakenOptions = [
  { label: 'Sample Cancelled', value: 'sample_cancelled' },
  { label: 'Re-run Initiated (same sample)', value: 'rerun_initiated' },
  { label: 'Re-draw Initiated (new order)', value: 'redraw_initiated' }
];

const rules = {
  reviewer_id: [{ required: true, message: 'Reviewer is required', trigger: 'change' }],
  scope: [{ required: true, message: 'Scope is required', trigger: 'change' }],
  test_ids: [{
    validator: (rule, value, callback) => {
      if (form.scope === 'tests') {
        if (Array.isArray(value) && value.length > 0) return callback();
        return callback(new Error('Select at least one test'));
      }
      if (form.scope === 'results') {
        if (props.selectedTests.length > 0) return callback();
        return callback(new Error('Select at least one parameter in the table'));
      }
      return callback();
    },
    trigger: 'change'
  }],
  investigation_checklist: [{ type: 'array', required: true, message: 'Select at least one item', trigger: 'change' }],
  root_cause: [{ required: true, message: 'Root cause is required', trigger: 'change' }],
  action_taken: [{ required: true, message: 'Action taken is required', trigger: 'change' }],
  rejection_comment: [
    { required: true, message: 'Detailed comment is required', trigger: 'blur' },
    { min: 50, message: 'Comment must be at least 50 characters', trigger: 'blur' }
  ]
};

const selectedResultsCount = computed(() => {
  return props.selectedTests.length;
});

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
    
    // Auto-set scope if results are selected
    if (props.selectedTests.length > 0) {
      form.scope = 'results';
    }
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
    const selectedResultsPayload = props.selectedTests
      .filter(item => item.result_id)
      .map(item => ({
        test_result_id: item.result_id,
        test_parameter_id: item.parameterId || null
      }));

    const actionTakenLabel = actionTakenOptions.find(opt => opt.value === form.action_taken)?.label || null;

    const response = await api.post(`/laboratory-requests/${props.requestDetails.id}/reject`, {
      scope: form.scope,
      test_ids: form.scope === 'tests' ? form.test_ids : [],
      selected_results: selectedResultsPayload,
      reviewer_id: form.reviewer_id,
      reviewed_at: form.reviewed_at,
      investigation_checklist: form.investigation_checklist,
      root_cause: form.root_cause,
      action_taken: form.action_taken,
      action_taken_label: actionTakenLabel,
      rejection_comment: form.rejection_comment,
      original_value_canceled: !!form.original_value_canceled
    });

    ElMessage.success(response.data?.message || 'Rejection recorded successfully');
    
    // Reset form
    form.rejection_comment = '';
    form.investigation_checklist = [];
    form.root_cause = '';
    form.action_taken = '';

    emit('validate');
    emit('save');
    emit('update:visible', false);
  } catch (error) {
    console.error('Error rejecting result:', error);
    ElMessage.error(`Failed to reject result: ${error.response?.data?.message || error.message}`);
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

.modal-premium-header.warning {
  background: #fffbeb;
  border-bottom: 1px solid #fde68a;
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

.brand-logo-wrapper.warning {
  background: #f59e0b;
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
}

.header-title {
  font-size: 18px;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.header-subtitle {
  font-size: 13px;
  color: #64748b;
  margin: 4px 0 0 0;
}

.close-btn {
  background: rgba(0, 0, 0, 0.05) !important;
  border: none !important;
  color: #64748b !important;
}

.close-btn:hover {
  background: #fee2e2 !important;
  color: #ef4444 !important;
}

.modal-body-premium {
  padding: 32px;
  background: white;
}

.form-section {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 24px;
}

.form-section.highlight {
  background: #f1f5f9;
  border-color: #cbd5e1;
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

.premium-radio-group :deep(.el-radio-button__inner) {
  padding: 10px 20px;
  border-radius: 8px;
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

.footer-submit-danger {
  background: #ef4444;
  border: none;
  color: white;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
}

.footer-submit-danger:hover {
  background: #dc2626;
  transform: translateY(-1px);
}

.scope-info {
  display: flex;
  align-items: center;
  height: 100%;
}
</style>
