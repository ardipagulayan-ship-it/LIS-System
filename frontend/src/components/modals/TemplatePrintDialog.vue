<template>
  <!-- Hidden print container - isolated for clean printing -->
  <div 
    id="printContainer" 
    ref="printContainerRef"
    class="hidden-print-container"
    v-if="templateSchema && reportData"
  >
    <ReportRenderer 
      :schema="templateSchema" 
      :data="reportData"
      :isPreview="true"
    />
  </div>

  <!-- Main dialog for template selection and preview -->
  <el-dialog
  :model-value="visible"
  title="Print Report"
  width="90%"
  :fullscreen="true"
  @close="$emit('update:visible', false)"
  v-loading="loading"
>
  <div class="print-dialog-content">
    <!-- Toolbar -->
    <div class="print-toolbar" style="margin-bottom: 0; padding: 15px 20px; border-bottom: 1px solid #e0e0e0; background: white;">
      <el-space>
        <el-button type="primary" @click="handlePrint">
          <el-icon><Printer /></el-icon>
          Print
        </el-button>
        <el-button @click="handleDownloadPDF">
          <el-icon><Download /></el-icon>
          Download PDF
        </el-button>
        <el-select v-model="selectedTemplate" placeholder="Select Template" style="width: 300px;" @change="loadTemplate">
          <el-option
            v-for="template in availableTemplates"
            :key="template.id"
            :label="template.name"
            :value="template.id"
          />
        </el-select>
        <el-button @click="$emit('update:visible', false)">Close</el-button>
      </el-space>
    </div>

    <!-- Report Preview Container for on-screen preview -->
    <div class="report-print-container" v-if="templateSchema && reportData">
      <div class="report-preview">
        <ReportRenderer 
          :schema="templateSchema" 
          :data="reportData"
          :isPreview="true"
        />
      </div>
    </div>
    <div v-else style="padding: 40px; text-align: center; color: #909399;">
      <el-empty description="No template selected or data not loaded" />
    </div>
  </div>

  <template #footer>
    <el-space>
      <el-button @click="$emit('update:visible', false)">Cancel</el-button>
      <el-button type="primary" @click="handlePrint">Print</el-button>
      <el-button @click="handleDownloadPDF">Download PDF</el-button>
    </el-space>
  </template>
</el-dialog>

</template>

<script setup>
import { ref, reactive, onMounted, watch, nextTick } from 'vue';
import { ElMessage } from 'element-plus';
import { Printer, Download } from '@element-plus/icons-vue';
import api from '@/utils/axios';
import ReportRenderer from '@/components/ReportRenderer.vue';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  requestDetails: {
    type: Object,
    default: () => ({}),
  },
  testCategoryId: {
    type: [Number, String],
    default: null,
  },
  testResults: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['update:visible', 'print-complete']);

const loading = ref(false);
const selectedTemplate = ref(null);
const availableTemplates = ref([]);
const templateSchema = ref(null);
const reportData = reactive({});
const printContainerRef = ref(null);

// Load available templates for test category
const loadAvailableTemplates = async () => {
  if (!props.testCategoryId) {
    console.warn('No test category ID provided:', props.testCategoryId);
    ElMessage.warning('Test category not available');
    return;
  }

  try {
    loading.value = true;
    console.log('Loading templates for category:', props.testCategoryId);
    
    // Try different endpoint variations
    let response;
    try {
      response = await api.get(`/report-templates`, {
        params: {
          test_category_id: props.testCategoryId,
          paginate: false,
        },
      });
    } catch (err) {
      // If test_category_id doesn't work, try test_group_id
      console.warn('Trying alternative endpoint with test_group_id...');
      response = await api.get(`/report-templates`, {
        params: {
          test_group_id: props.testCategoryId,
          paginate: false,
        },
      });
    }
    
    console.log('Templates response:', response.data);
    const templates = response.data?.data || response.data || [];
    availableTemplates.value = Array.isArray(templates) ? templates : [];
    
    console.log('Available templates:', availableTemplates.value);
    
    if (availableTemplates.value.length > 0) {
      selectedTemplate.value = availableTemplates.value[0].id;
      await loadTemplate();
    } else {
      console.warn('No templates found for this test category');
      ElMessage.info('No report templates available for this test category. Create a template in the Report Designer first.');
    }
  } catch (error) {
    console.error('Error loading templates:', error);
    ElMessage.error(`Failed to load templates: ${error.message}`);
  } finally {
    loading.value = false;
  }
};

// Load template schema
const loadTemplate = async () => {
  if (!selectedTemplate.value) return;

  try {
    loading.value = true;
    const response = await api.get(`/report-templates/${selectedTemplate.value}`);
    const template = response.data;
    
    if (template.schema_data) {
      templateSchema.value = template.schema_data;
    } else {
      ElMessage.warning('Template does not have valid schema');
      templateSchema.value = null;
    }
    
    // Build report data from request details and test results
    buildReportData();
  } catch (error) {
    console.error('Error loading template:', error);
    ElMessage.error('Failed to load template');
  } finally {
    loading.value = false;
  }
};

// Build comprehensive report data from props
const buildReportData = () => {
  const { requestDetails, testResults } = props;
  
  Object.assign(reportData, {
    patient: {
      id: requestDetails.patient?.id,
      first_name: requestDetails.patient?.first_name,
      last_name: requestDetails.patient?.last_name,
      full_name: `${requestDetails.patient?.first_name} ${requestDetails.patient?.last_name}`.trim(),
      email: requestDetails.patient?.email,
      phone: requestDetails.patient?.phone,
      gender: requestDetails.patient?.gender,
      date_of_birth: requestDetails.patient?.date_of_birth,
      address: requestDetails.patient?.address,
      civil_status: requestDetails.patient?.civil_status,
    },
    sample: {
      id: requestDetails.sample_id,
      sample_id: requestDetails.sample_id,
      laboratory_request_id: requestDetails.id,
      request_date: requestDetails.request_date,
      priority_level: requestDetails.priority_level,
      status: 'Completed',
    },
    lab: {
      name: 'Laboratory Name',
      address: '123 Lab Street',
    },
    tests: buildTestsData(),
  });
};

// Build test results data from testResults prop
const buildTestsData = () => {
  const { testResults, requestDetails } = props;
  const tests = [];
  
  console.log('Building test data from:', { testResults, requestDetails });
  
  // testResults is testParameters from UniversalResultModal
  // It's structured as: { testId: [parameters] }
  
  if (testResults && typeof testResults === 'object') {
    Object.entries(testResults).forEach(([testId, params]) => {
      if (Array.isArray(params)) {
        params.forEach(param => {
          tests.push({
            parameter_id: param.id,
            id: param.id,
            test_id: testId,
            test_name: param.display_name || param.test_name || param.name,
            result_value: param.resultValue || param.result_value || '-',
            unit: param.unit || '-',
            reference_range: param.reference_range || '-',
            flags: param.flags || '',
          });
        });
      }
    });
  } else if (requestDetails.tests && Array.isArray(requestDetails.tests)) {
    // Fallback: use tests from request details if testResults not provided
    requestDetails.tests.forEach(test => {
      tests.push({
        parameter_id: test.id,
        id: test.id,
        test_id: test.id,
        test_name: test.name || test.test_name,
        result_value: '-',
        unit: test.unit || '-',
        reference_range: '-',
        flags: '',
      });
    });
  }
  
  console.log('Built tests:', tests);
console.log('Loaded template schema elements:', templateSchema.value?.elements || templateSchema.value?.canvas?.elements);
  return tests;
};

// Print using isolated container for clean output
const handlePrint = async () => {
  if (!templateSchema.value || !reportData) {
    ElMessage.warning('No template or data to print');
    return;
  }
  
  try {
    loading.value = true;
    
    // Update status to "Result Printed" in the database
    if (props.requestDetails?.id) {
      await api.put(`/laboratory-requests/${props.requestDetails.id}/status`, {
        status: 'Result Printed'
      });
      console.log('Status updated to Result Printed');
      
      // Emit event to notify parent component of status change
      emit('print-complete', {
        requestId: props.requestDetails.id,
        status: 'Result Printed'
      });
    }
    
    // Wait for next tick to ensure isolated container is rendered
    await nextTick();
    
    // Get the isolated print container and trigger print
    const printElement = document.getElementById('printContainer');
    if (!printElement) {
      ElMessage.error('Print container not found');
      return;
    }
    
    // Create a new window for printing with isolated content
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    if (!printWindow) {
      ElMessage.error('Failed to open print window. Check popup blocker settings.');
      return;
    }
    
    // Get the HTML content of the print container
    const printContent = printElement.innerHTML;
    
    // Write the content to the print window with proper styling
    printWindow.document.write(`
      <!DOCTYPE html>
      <html>
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>Print Report</title>
          <style>
            * {
              margin: 0;
              box-sizing: border-box;
            }
            
            .report-renderer {
              padding: ${templateSchema.value.canvas.padding || '0mm'};
            }
            
            html, body {
              width: 100%;
              height: 100%;
              background: white;
              color: black;
              font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            }
            
            @page {
              margin: 0;
              /* padding: 0; */
              size: A4;
            }
            
            body {
              padding: 0;
              margin: 0;
            }
            
            .report-content {
              width: 100%;
              padding: 30px 20px;
              background: white;
              text-align: center;
            }
            
            /* Header section */
            .report-content > p {
              font-size: 12px;
              margin: 4px 0;
              text-align: center;
            }
            
            /* Spacer */
            .report-content > div[style*="height"] {
              text-align: center;
            }
            
            /* Ensure all text is black for printing */
            * {
              color-adjust: exact;
              -webkit-print-color-adjust: exact;
              print-color-adjust: exact;
              color: black;
            }
            
            /* Image sizing for print */
            img {
              max-width: 100%;
              height: auto;
            }
            
            /* Table styling - centered */
            table {
              width: 100%;
              max-width: 900px;
              border-collapse: collapse;
              margin: 20px auto;
              font-size: 12px;
            }
            
            table, th, td {
              border: 1px solid #000;
            }
            
            th {
              padding: 10px;
              text-align: center;
              font-weight: bold;
              background: #f9f9f9;
            }
            
            td {
              padding: 8px;
              text-align: center;
            }
            
            td:first-child, th:first-child {
              text-align: left;
            }
            
            /* Signature section */
            .signature-block {
              margin-top: 60px;
              width: 100%;
              text-align: center;
            }
            
            .signature-block table {
              margin: 20px auto;
              width: auto;
              min-width: 600px;
            }
            
            .signature-block td {
              padding: 20px 40px;
              vertical-align: bottom;
              text-align: center;
            }
            
            .signature-line {
              border-top: 1px solid #000;
              width: 200px;
              margin: 0 auto 5px auto;
              padding-top: 5px;
            }
            
            /* Signature block styling */
            .signature-block {
              margin-top: 40px;
              border-top: 1px solid #000;
              padding-top: 10px;
            }
          </style>
        </head>
        <body>
          <div class="report-content">
            ${printContent}
          </div>
        </body>
      </html>
    `);
    
    printWindow.document.close();
    
    // Trigger print after content is loaded
    setTimeout(() => {
      printWindow.print();
      // Keep window open for user review or close after print
      // printWindow.close();
    }, 500);
    
    ElMessage.success('Results marked as printed. Print dialog opened.');
  } catch (error) {
    console.error('Print error:', error);
    ElMessage.error('Failed to mark results as printed: ' + error.message);
  } finally {
    loading.value = false;
  }
};

// Download as PDF (requires backend support)
const handleDownloadPDF = async () => {
  if (!selectedTemplate.value || !props.requestDetails?.id) {
    ElMessage.warning('Missing template or request ID');
    return;
  }

  try {
    loading.value = true;
    const response = await api.get(`/print/template-pdf/${props.requestDetails.id}`, {
      params: {
        template_id: selectedTemplate.value,
      },
      responseType: 'blob',
    });

    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `Report_${props.requestDetails.id}_${new Date().toISOString().split('T')[0]}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);

    ElMessage.success('PDF downloaded successfully');
  } catch (error) {
    console.error('PDF download error:', error);
    ElMessage.error('Failed to download PDF');
  } finally {
    loading.value = false;
  }
};

// Watch for visible changes and reload templates when dialog opens
watch(() => props.visible, (newVal) => {
  if (newVal && props.testCategoryId) {
    console.log('Dialog became visible, loading templates...');
    loadAvailableTemplates();
  }
});

onMounted(() => {
    if (props.visible) {
      loadTemplates();
    }
  });
</script>

<style scoped>
/* Hidden print container - only visible when printing */
.hidden-print-container {
  display: none;
  position: absolute;
  top: -9999px;
  left: -9999px;
  width: 210mm;
  height: 297mm;
  background: white;
  z-index: -1;
}

.print-dialog-content {
  padding: 0;
  max-height: calc(100vh - 120px);
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  background: #f5f5f5;
}

.print-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.report-print-container {
  width: 100%;
  margin: 0 auto;
  padding: 20px;
  display: flex;
  justify-content: center;
  background: #f5f5f5;
  overflow-y: auto;
  overflow-x: auto;
  align-items: center;
}

.report-preview {
  background: white;
  min-height: 400px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Ensure colors are preserved during printing */
:deep(.printable-content),
:deep(.report-renderer) {
  print-color-adjust: exact;
  -webkit-print-color-adjust: exact;
}

/* Override ReportRenderer styling in preview context to match designer appearance */
.report-preview :deep(.report-renderer) {
  width: 210mm;
  max-width: none;
  min-width: 210mm;
  margin: 0 auto;
}

.report-preview :deep(.report-container) {
  width: 100%;
  margin: 0;
  padding: 0;
}

.report-preview :deep(.renderer-element) {
  width: 100%;
}

/* Media print styles - these won't affect the isolated window print */
@media print {
  @page {
    margin: 0;
    padding: 0;
    size: A4;
  }
  
  * {
    margin: 0 !important;
    padding: 0 !important;
    box-sizing: border-box !important;
  }
  
  html, body {
    width: 100% !important;
    height: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  
  body {
    background: white !important;
    color: black !important;
  }
  
  /* Show only the dialog content when printing from dialog context */
  .report-print-container,
  .report-preview {
    display: block !important;
    visibility: visible !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    border: none !important;
    box-shadow: none !important;
    background: white !important;
  }
  
  /* Hide dialog chrome */
  .el-dialog__header,
  .el-dialog__footer,
  .print-toolbar {
    display: none !important;
  }
  
  /* Hide app wrapper */
  .app-wrapper,
  .sidebar {
    display: none !important;
  }
}
</style>
