<template>
  <el-dialog
    :model-value="visible"
    @update:model-value="$emit('update:visible', $event)"
    width="500px"
    class="premium-modal"
    :show-close="false"
    destroy-on-close
  >
    <!-- Premium Header -->
    <template #header>
      <div class="modal-premium-header">
        <div class="header-left">
          <div class="brand-logo-wrapper rerun">
            <el-icon><Refresh /></el-icon>
          </div>
          <div class="header-text">
            <h3 class="header-title">Initiate Re-run</h3>
            <p class="header-subtitle">Repeat analysis for selected parameters</p>
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
        <div class="form-grid single">
          <div class="form-section">
            <h4 class="section-title">Re-run Configuration</h4>
            <el-form-item label="Reason for Re-run" prop="reason">
              <el-select v-model="form.reason" placeholder="Select Reason" class="premium-input-v2">
                <el-option label="Delta Check Failure" value="Delta Check Failure" />
                <el-option label="Critical Value Confirmation" value="Critical Value Confirmation" />
                <el-option label="Technical Error / Instrument Flag" value="Technical Error" />
                <el-option label="Clinical Discrepancy" value="Clinical Discrepancy" />
                <el-option label="Doctor's Request" value="Doctor Request" />
                <el-option label="QC Out of Control" value="QC Failure" />
              </el-select>
            </el-form-item>

            <el-form-item label="Additional Comments" prop="comments">
              <el-input 
                v-model="form.comments" 
                type="textarea" 
                rows="3" 
                placeholder="Specify any additional details for the technician..."
                class="premium-textarea"
              />
            </el-form-item>
          </div>
        </div>
      </el-form>
    </div>

    <template #footer>
      <div class="modal-footer-premium">
        <el-button @click="$emit('update:visible', false)" class="premium-button footer-cancel">Cancel</el-button>
        <el-button 
          type="primary" 
          :loading="loading" 
          @click="submit" 
          class="premium-button footer-submit-rerun"
        >
          Confirm Re-run
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Refresh, Close } from '@element-plus/icons-vue';
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

const emit = defineEmits(['update:visible', 'submit']);

const formRef = ref(null);
const form = reactive({
  reason: '',
  comments: ''
});

const rules = {
  reason: [{ required: true, message: 'Please select a reason for the re-run.', trigger: 'change' }]
};

const submit = async () => {
  if (!formRef.value) return;
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      try {
        let resultsToRerun = [];
        
        if (props.selectedTests && props.selectedTests.length > 0) {
          resultsToRerun = props.selectedTests;
        } else if (props.requestDetails?.testParameters) {
          // Global Rerun: Collect all results for this request
          resultsToRerun = Object.values(props.requestDetails.testParameters).flat();
        } else if (props.requestDetails?.results) {
          // Fallback to direct results array
          resultsToRerun = props.requestDetails.results.map(r => ({
            result_id: r.id,
            parameterId: r.test_parameter_id || r.test_parameter?.id
          }));
        }

        const flagged_results = resultsToRerun
          .filter(item => item.result_id || item.id)
          .map(item => ({
            test_result_id: item.result_id || item.id,
            test_parameter_id: item.parameterId || item.test_parameter_id || item.test_parameter?.id || item.id
          }));

        if (flagged_results.length === 0) {
          ElMessage.error('Please select at least one valid test result to re-run.');
          return;
        }

        const payload = {
          flagged_results: flagged_results,
          reason: form.reason,
          comments: form.comments,
          requested_by_id: props.currentUser?.id
        };

        const response = await api.post(`/laboratory-requests/${props.requestDetails.id}/delta-check/rerun`, payload);
        
        ElMessage.success('Re-run initiated successfully.');
        
        // Reset form
        form.reason = '';
        form.comments = '';

        emit('submit', response.data);
        emit('update:visible', false);
      } catch (error) {
        console.error('Error initiating re-run:', error);
        ElMessage.error('Failed to initiate re-run.');
      }
    }
  });
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

.brand-logo-wrapper.rerun {
  background: #6366f1;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
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
  background: #f1f5f9 !important;
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

.footer-submit-rerun {
  background: #6366f1;
  border: none;
  color: white;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
}

.footer-submit-rerun:hover {
  background: #4f46e5;
  transform: translateY(-1px);
}
</style>
