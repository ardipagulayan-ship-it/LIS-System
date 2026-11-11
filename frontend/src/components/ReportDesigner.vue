<template>
  <div class="report-designer">
    <!-- Header -->
    <div class="designer-header">
      <div class="header-left">
        <h2>Report Template Designer</h2>
        <div class="template-config">
          <el-input v-model="schema.metadata.templateName" placeholder="Template name (e.g., Lipid Panel Report)" size="small" style="width: 250px; margin-right: 12px;" />
          <el-select v-model="schema.metadata.resultType" placeholder="Report for..." size="small" style="width: 200px;" clearable>
            <el-option label="Test Group" value="test_group" />
            <el-option label="Sample Result" value="sample_result" />
            <el-option label="Patient Test" value="patient_test" />
            <el-option label="Custom" value="custom" />
          </el-select>
        </div>
      </div>
      <div class="header-right">
        <el-button @click="saveTemplate" type="primary" :loading="saving">
          <el-icon><DocumentCopy /></el-icon> Save Template
        </el-button>
        <el-button @click="previewReport" type="info">
          <el-icon><View /></el-icon> Preview
        </el-button>
        <el-button @click="exportSchema" type="success">
          <el-icon><Download /></el-icon> Export
        </el-button>
        <el-button @click="closeDesigner" text>Close</el-button>
      </div>
    </div>

    <!-- Main Designer Area -->
    <div class="designer-container">
      <!-- Left Panel: Component Toolbar -->
      <div class="left-panel">
        <!-- Data Source Configuration -->
        <div class="data-source-config">
          <div class="config-header">📊 Data Source</div>
          <el-form label-width="80px" size="small">
            <el-form-item label="Test Group">
              <el-select v-model="schema.metadata.testGroupId" placeholder="Select group" @change="onTestGroupChange" clearable>
                <el-option v-for="g in testGroups" :key="g.id" :label="g.name" :value="g.id" />
              </el-select>
            </el-form-item>

            <el-form-item v-if="schema.metadata.testGroupId" label="Columns">
              <el-select v-model="schema.metadata.columns" multiple placeholder="Select columns" @change="onColumnsChange" style="width: 100%;">
                <el-option v-for="col in availableColumns" :key="col.value" :label="col.label" :value="col.value" />
              </el-select>
            </el-form-item>

            <el-form-item label="Report Group">
              <el-select v-model="schema.metadata.reportGroupId" placeholder="Select layout" clearable>
                <el-option v-for="g in reportGroups" :key="g.id" :label="g.name" :value="g.id" />
              </el-select>
            </el-form-item>
          </el-form>
        </div>
        <el-divider />

        <div class="toolbar-header">Components</div>
        <div class="components-list">
          <!-- Static Elements -->
          <div class="component-group">
            <div class="group-title">Static Elements</div>
            <div
              v-for="comp in staticComponents"
              :key="comp.type"
              class="component-item"
              draggable="true"
              @dragstart="startDrag($event, comp)"
            >
              <span class="icon">{{ comp.icon }}</span>
              <span class="label">{{ comp.label }}</span>
            </div>
          </div>

          <!-- Layout Elements -->
          <div class="component-group">
            <div class="group-title">Layout</div>
            <div
              v-for="comp in layoutComponents"
              :key="comp.type"
              class="component-item"
              draggable="true"
              @dragstart="startDrag($event, comp)"
            >
              <span class="icon">{{ comp.icon }}</span>
              <span class="label">{{ comp.label }}</span>
            </div>
          </div>

          <!-- Dynamic Elements -->
          <div class="component-group">
            <div class="group-title">Dynamic</div>
            <div
              v-for="comp in dynamicComponents"
              :key="comp.type"
              class="component-item"
              draggable="true"
              @dragstart="startDrag($event, comp)"
            >
              <span class="icon">{{ comp.icon }}</span>
              <span class="label">{{ comp.label }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Center Panel: Canvas -->
      <div class="center-panel">
        <div class="canvas-header">
          <span>A4 Preview ({{ canvasWidth }}mm × {{ canvasHeight }}mm)</span>
          <div class="zoom-controls">
            <el-button @click="zoomOut" text icon="Minus" />
            <span>{{ Math.round(zoom * 100) }}%</span>
            <el-button @click="zoomIn" text icon="Plus" />
            <el-button @click="fitToScreen" text>Fit</el-button>
          </div>
        </div>

        <div class="canvas-container" @drop="onCanvasDrop" @dragover.prevent>
          <div
            class="canvas"
            ref="canvasEl"
            :style="canvasStyle"
            @click="selectElement(null)"
          >
            <!-- Render elements -->
            <DesignerElementWrapper
              v-for="element in schema.elements"
              :key="element.id"
              :element="element"
              :data="previewData"
              :schemaMetadata="schema.metadata"
              :selectedElement="selectedElement"
              :selectElement="selectElement"
            />

            <!-- Grid background -->
            <div class="canvas-grid"></div>
          </div>
        </div>
      </div>

      <!-- Right Panel: Properties Editor -->
      <div class="right-panel">
        <div class="properties-header">
          <span v-if="selectedElement">Edit {{ selectedElement.type }}</span>
          <span v-else>Properties</span>
        </div>

        <div v-if="selectedElement" class="properties-form">
          <!-- Common Properties -->
          <el-form label-width="100px" size="small">
            <el-form-item label="Element ID">
              <el-input v-model="selectedElement.id" disabled />
            </el-form-item>

            <!-- Type-specific properties -->
            <component
              :is="getPropertyEditor(selectedElement.type)"
              :element="selectedElement"
              :availableFields="availableDataFields"
              @update="updateElement"
            />

            <!-- Style Properties -->
            <el-divider>Styling</el-divider>
            <el-form-item label="Width">
              <el-input v-model.number="selectedElement.width" placeholder="e.g., 200 or 50%" />
            </el-form-item>
            <el-form-item label="Height">
              <el-input v-model.number="selectedElement.height" placeholder="auto" />
            </el-form-item>
            <el-form-item label="Padding">
              <el-input v-model="selectedElement.style.padding" placeholder="e.g., 12px" />
            </el-form-item>
            <el-form-item label="Background">
              <el-color-picker v-model="selectedElement.style.backgroundColor" show-alpha />
            </el-form-item>

            <!-- Actions -->
            <el-divider>Actions</el-divider>
            <div class="action-buttons">
              <el-button @click="duplicateElement(selectedElement)" type="info" size="small" block>Duplicate</el-button>
              <el-button @click="deleteElement(selectedElement)" type="danger" size="small" block>Delete</el-button>
            </div>
          </el-form>
        </div>

        <div v-else class="canvas-properties-form">
          <el-form label-width="120px" size="small">
            <el-divider>Canvas Settings</el-divider>
            <el-form-item label="Padding">
              <el-input v-model="schema.canvas.padding" placeholder="e.g., 10mm" />
            </el-form-item>
            <el-form-item label="Margin">
              <el-input v-model="schema.canvas.margin" placeholder="e.g., 0mm" />
            </el-form-item>
            <el-form-item label="Background Color">
              <el-color-picker v-model="schema.canvas['background-color']" show-alpha />
            </el-form-item>
            <el-form-item label="Background">
              <el-input v-model="schema.canvas.background" placeholder="e.g., url(...) or linear-gradient(...)" />
            </el-form-item>
            <el-form-item label="Default Font">
              <el-input v-model="schema.canvas.defaultFontFamily" placeholder="e.g., Arial, sans-serif" />
            </el-form-item>
            <el-form-item label="Font Size">
              <el-input v-model="schema.canvas.defaultFontSize" placeholder="e.g., 12px" />
            </el-form-item>
            <el-form-item label="Canvas Width">
              <el-input v-model="schema.canvas.width" placeholder="e.g., 210mm" />
            </el-form-item>
            <el-form-item label="Canvas Height">
              <el-input v-model="schema.canvas.height" placeholder="e.g., 297mm" />
            </el-form-item>
          </el-form>
        </div>
      </div>
    </div>
  </div>

  <!-- Preview Modal -->
  <el-dialog v-model="previewVisible" width="90%" fullscreen modal destroy-on-close :show-close="false" :close-on-click-modal="false" @close="previewVisible = false">
    <template #header>
      <div class="preview-custom-header">
        <div class="header-left">
          <span>A4 Preview ({{ canvasWidth }}mm × {{ canvasHeight }}mm)</span>
        </div>
        <div class="header-right">
          <div class="zoom-controls">
            <el-button @click="zoomOut" text icon="Minus" />
            <span>{{ Math.round(zoom * 100) }}%</span>
            <el-button @click="zoomIn" text icon="Plus" />
            <el-button @click="fitToScreen" text>Fit</el-button>
          </div>
          <el-button @click="previewVisible = false" :icon="Close" circle />
        </div>
      </div>
    </template>
    <div v-if="previewVisible" class="preview-container">
      <div class="report-preview" :style="previewWrapperStyle">
        <ReportRenderer :schema="schema" :data="previewData" :isPreview="true" />
      </div>
    </div>
  </el-dialog>
</template>
<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import ReportRenderer from './ReportRenderer.vue';
import TextPropertyEditor from './ReportEditors/TextPropertyEditor.vue';
import DynamicTextPropertyEditor from './ReportEditors/DynamicTextPropertyEditor.vue';
import DataTablePropertyEditor from './ReportEditors/DataTablePropertyEditor.vue';
import SignaturePropertyEditor from './ReportEditors/SignaturePropertyEditor.vue';
import ImagePropertyEditor from './ReportEditors/ImagePropertyEditor.vue';
import ContainerPropertyEditor from './ReportEditors/ContainerPropertyEditor.vue';
import GridPropertyEditor from './ReportEditors/GridPropertyEditor.vue';
import ShapePropertyEditor from './ReportEditors/ShapePropertyEditor.vue';
import api from '@/utils/axios';
import { getHeaderGridStyle, getFooterGridStyle, getSeparatorStyle } from '@/utils/layoutDefinitions';
import { useRoute, useRouter } from 'vue-router';
import { DocumentCopy, View, Download, Minus, Plus, Close } from '@element-plus/icons-vue';
import DesignerElementWrapper from './ReportElements/DesignerElementWrapper.vue';

const route = useRoute();
const router = useRouter();

// State
const currentTemplate = ref(null);
const selectedElement = ref(null);
const saving = ref(false);
const zoom = ref(1);
const draggingElement = ref(null);
const dragOffset = ref({ x: 0, y: 0 });

// Canvas dimensions (A4)
const canvasWidth = ref(210); // mm
const canvasHeight = ref(297); // mm

// Schema with default structure and metadata
const schema = reactive({
  canvas: {
    width: '210mm',
    height: '297mm',
    padding: '10mm',
    margin: '0mm',
    background: '#ffffff', // Can be color, image, gradient
    'background-color': '#ffffff', // Specific for solid color
    defaultFontFamily: 'Arial, sans-serif',
    defaultFontSize: '12px',
    units: 'mm',
  },
  metadata: {
    templateName: '',
    resultType: '', // 'test_group', 'sample_result', 'patient_test', 'custom'
    testGroupId: null,
    reportGroupId: null,
    columns: [], // selected columns from database
    // Display metadata for formatting and unit preservation
    dateFormat: 'YYYY-MM-DD HH:mm:ss Z',
    timeFormat: 'HH:mm:ss',
    preserveLiteralUnits: true, // Preserve units like "10^12/L" as-is
  },
  elements: [],
});

// Preview dialog state and sample data for rendering
const previewVisible = ref(false);
// reference to canvas element to measure pixel width for preview parity
const canvasEl = ref(null);
// inline style applied to preview wrapper to match canvas pixel width
const previewWrapperStyle = ref({});
const pxPerMmX = ref(1);
const pxPerMmY = ref(1);
const testGroups = ref([]);
const reportGroups = ref([]);
const availableColumns = ref([]);
const previewData = reactive({
  patient: {
    name: 'John Doe',
    date_of_birth: '1990-01-15',
    age: 34,
    gender: 'M',
  },
  sample: {
    id: 'S001',
    collected_at: new Date().toISOString(),
    processed_at: new Date().toISOString(),
  },
  lab: {
    name: 'Laboratory Name',
    address: '123 Lab Street',
  },
  tests: [], // Will be populated dynamically from database
});

// Component definitions
const staticComponents = [
  { type: 'text', label: 'Text', icon: '📝' },
  { type: 'image', label: 'Image', icon: '🖼️' },
  { type: 'shape', label: 'Shape', icon: '▭' },
];

const layoutComponents = [
  { type: 'container', label: 'Container', icon: '📦' },
  { type: 'grid', label: 'Grid', icon: '⊞' },
];

const dynamicComponents = [
  { type: 'text-dynamic', label: 'Dynamic Text', icon: '🔗' },
  { type: 'datatable', label: 'Data Table', icon: '📊' },
  { type: 'signature-block', label: 'Signatures', icon: '✍️' },
  { type: 'header-block', label: 'Header Block', icon: '⬆️' },
  { type: 'patient-info-block', label: 'Patient Info', icon: '👤' },
  { type: 'rich-text-block', label: 'Rich Text', icon: '✒️' },
];

// Computed
const canvasStyle = computed(() => ({
  width: schema.canvas.width,
  height: schema.canvas.height,
  padding: schema.canvas.padding,
  margin: schema.canvas.margin,
  background: schema.canvas.background,
  backgroundColor: schema.canvas['background-color'],
  fontFamily: schema.canvas.defaultFontFamily,
  fontSize: schema.canvas.defaultFontSize,
  transform: `scale(${zoom.value})`,
  transformOrigin: 'top left',
}));

// Methods
const startDrag = (event, component) => {
  event.dataTransfer.effectAllowed = 'copy';
  const payload = JSON.stringify(component);
  event.dataTransfer.setData('component', payload);
  event.dataTransfer.setData('text/plain', payload);
  event.dataTransfer.setData('application/json', payload);
};

const onCanvasDrop = (event) => {
  event.preventDefault();
  let componentJson = event.dataTransfer.getData('component');
  if (!componentJson) componentJson = event.dataTransfer.getData('application/json');
  if (!componentJson) componentJson = event.dataTransfer.getData('text/plain');
  if (!componentJson) return;
  let component;
  try {
    component = JSON.parse(componentJson);
  } catch (e) {
    return;
  }
  const rect = event.currentTarget.getBoundingClientRect();
  const xPx = (event.clientX - rect.left) / zoom.value;
  const yPx = (event.clientY - rect.top) / zoom.value;
  const mmX = (xPx / rect.width) * canvasWidth.value;
  const mmY = (yPx / rect.height) * canvasHeight.value;

  const newElement = {
    id: `${component.type}_${Date.now()}`,
    type: component.type,
    width: 'auto',
    height: 'auto',
    style: {
      padding: '0mm',
      margin: '0mm',
      color: '#000000',
      textAlign: 'left',
      fontStyle: 'normal',
      fontFamily: schema.canvas.defaultFontFamily, // Inherit from canvas
      fontSize: schema.canvas.defaultFontSize, // Inherit from canvas
    },
  };

  // Add default content based on type
  if (component.type === 'text') {
    newElement.content = 'Sample Text';
  } else if (component.type === 'text-dynamic') {
    newElement.field = 'patient.name';
    newElement.label = 'Name';
  } else if (component.type === 'datatable') {
    newElement.dataSource = 'tests';
    newElement.columnHeaders = [
      { label: 'parameter', displayName: 'Parameter', field: 'test_name' },
      { label: 'result_value', displayName: 'Result', field: 'result_value', conditionalColor: true },
      { label: 'unit', displayName: 'Unit', field: 'unit', isLiteralUnit: true },
    ];
    // Keep legacy columns for backwards compatibility
    newElement.columns = [
      { header: 'Test', field: 'test_name' },
      { header: 'Result', field: 'result_value', conditionalColor: true },
      { header: 'Unit', field: 'unit' },
    ];
  } else if (component.type === 'container') {
    newElement.height = '10mm';
    newElement.bottomFixed = false; // New property
    newElement.style = { height: '10mm', backgroundColor: '#f5f5f5' };
    newElement.children = [];
  } else if (component.type === 'grid') {
    newElement.height = '10mm';
    newElement.gridTemplateColumns = '';
    newElement.gridTemplateRows = '';
    newElement.children = [];
    newElement.style = {
      ...newElement.style,
      display: 'grid',
      gap: '16px',
    };
  } else if (component.type === 'shape') {
    newElement.shapeType = 'line';
    newElement.width = 200;
    newElement.height = 2;
    newElement.style = { color: '#333333', strokeWidth: 1 };
  } else if (component.type === 'signature-block') {
    newElement.mode = 'specific_user';
    newElement.layout = '1col';
    newElement.signatories = [
      { name: 'Dr. Smith', role: 'Pathologist' },
      { name: 'Lab Director', role: 'Lab Director' },
    ];
    newElement.width = '100%';
  }

  // Helper function to find a container element by ID recursively
  const findContainerElement = (elements, id) => {
    for (const element of elements) {
      if (element.id === id && (element.type === 'container' || element.type === 'grid')) {
        return element;
      }
      if (element.children && element.children.length > 0) {
        const found = findContainerElement(element.children, id);
        if (found) return found;
      }
    }
    return null;
  };

  // Determine the actual drop target (could be a nested canvas-element)
  let dropTargetElement = event.target;
  let targetContainer = null;

  // Traverse up the DOM tree to find the closest canvas-element that is a container
  while (dropTargetElement && dropTargetElement !== event.currentTarget) {
    if (dropTargetElement.classList && dropTargetElement.classList.contains('canvas-element')) {
      const targetElementId = dropTargetElement.getAttribute('data-element-id');
      if (targetElementId) {
        targetContainer = findContainerElement(schema.elements, targetElementId);
        if (targetContainer) {
          break; // Found a container parent
        }
      }
    }
    dropTargetElement = dropTargetElement.parentNode;
  }

  if (targetContainer) {
    // If a container is found, add the new element to its children
    if (targetContainer.type === 'grid') {
      const childCount = targetContainer.children.length;
      const columns = targetContainer.columns || 1; // Default to 1 column if not specified
      newElement.style.gridColumn = (childCount % columns) + 1;
      newElement.style.gridRow = Math.floor(childCount / columns) + 1;
    }
    targetContainer.children.push(newElement);
    selectedElement.value = newElement;
    ElMessage.success(`Element added to ${targetContainer.type}`);
  } else {
    // Otherwise, add to the root schema.elements
    schema.elements.push(newElement);
    selectedElement.value = newElement;
    ElMessage.success('Element added to canvas');
  }
};

const selectElement = (element, event = null) => {
  if (event) {
    event.stopPropagation();
  }
  selectedElement.value = element;
};

const updateElement = (updates) => {
  if (selectedElement.value) {
    Object.assign(selectedElement.value, updates);

    // Apply absolute positioning styles if bottomFixed is true
    if (selectedElement.value.bottomFixed) {
      selectedElement.value.style.position = 'absolute';
      selectedElement.value.style.bottom = '0';
      selectedElement.value.style.left = '0';
      selectedElement.value.style.right = '0';
    } else if (selectedElement.value.style.position === 'absolute' && updates.bottomFixed === false) {
      // Remove absolute positioning if bottomFixed is false and it was previously absolute
      delete selectedElement.value.style.position;
      delete selectedElement.value.style.bottom;
      delete selectedElement.value.style.left;
      delete selectedElement.value.style.right;
    }
  }
};

const duplicateElement = (element) => {
  const cloned = JSON.parse(JSON.stringify(element));
  cloned.id = `${element.type}_${Date.now()}`;
  schema.elements.push(cloned);
  selectedElement.value = cloned;
  ElMessage.success('Element duplicated');
};

const removeElementRecursively = (elements, elementToRemove) => {
  for (let i = 0; i < elements.length; i++) {
    if (elements[i].id === elementToRemove.id) {
      elements.splice(i, 1);
      return true;
    }
    if (elements[i].children && elements[i].children.length > 0) {
      if (removeElementRecursively(elements[i].children, elementToRemove)) {
        return true;
      }
    }
  }
  return false;
};

const deleteElement = (element) => {
  ElMessageBox.confirm('Delete this element?', 'Warning', {
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel',
    type: 'warning',
  }).then(() => {
    if (removeElementRecursively(schema.elements, element)) {
      selectedElement.value = null;
      ElMessage.success('Element deleted');
    }
  });
};

const zoomIn = () => {
  zoom.value = Math.min(zoom.value + 0.1, 2);
};

const zoomOut = () => {
  zoom.value = Math.max(zoom.value - 0.1, 0.3);
};

const fitToScreen = () => {
  zoom.value = 0.7;
};

const saveTemplate = async () => {
  saving.value = true;
  try {
    if (!schema.metadata.templateName) {
      ElMessage.warning('Please enter a template name');
      saving.value = false;
      return;
    }

    const payload = {
      name: schema.metadata.templateName,
      code: currentTemplate.value?.code || `TEMPLATE_${Date.now()}`,
      schema_data: { 
        canvas: schema.canvas, 
        elements: schema.elements,
        metadata: schema.metadata
      },
      page_settings: {
        format: 'A4',
        margins: [0, 0, 0, 0],
      },
      is_active: true,
    };

    if (currentTemplate.value?.id) {
      await api.put(`/report-templates/${currentTemplate.value.id}`, payload);
      ElMessage.success('Template updated');
    } else {
      const response = await api.post('/report-templates', payload);
      currentTemplate.value = response.data;
      ElMessage.success('Template created');
    }
  } catch (error) {
    ElMessage.error('Failed to save template');
    console.error(error);
  } finally {
    saving.value = false;
  }
};

const previewReport = async () => {
  try {
    // If template exists on server, try to fetch preview data for it
    if (currentTemplate.value?.id) {
      try {
        const resp = await api.get(`/report-templates/${currentTemplate.value.id}/preview-data`);
        // Merge fetched data into previewData (preserve defaults)
        Object.assign(previewData, resp.data || {});
      } catch (fetchErr) {
        // non-fatal - continue with local sample data
        console.warn('Could not fetch preview data, using sample data', fetchErr);
      }
    }
    // Measure canvas pixel width (includes current zoom) and apply to preview wrapper
    // Apply actual paper size from RDO (defaults to A4)
    try {
      const w = schema.canvas?.width || '210mm';
        const h = schema.canvas?.height || '297mm';
previewWrapperStyle.value = { width: w, height: h, padding: schema.canvas.padding };
    } catch (e) {
      console.warn('Could not apply paper size for preview', e);
      previewWrapperStyle.value = { width: '210mm', height: '297mm' };
    }

    previewVisible.value = true;
  } catch (err) {
    ElMessage.error('Failed to open preview');
    console.error(err);
  }
};

const exportSchema = () => {
  const json = JSON.stringify(
    { canvas: schema.canvas, elements: schema.elements },
    null,
    2
  );
  const blob = new Blob([json], { type: 'application/json' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `${currentTemplate.value?.code || 'template'}_schema.json`;
  a.click();
};

const closeDesigner = () => {
  router.back();
};

const getPropertyEditor = (type) => {
  const editors = {
    'text': TextPropertyEditor,
    'text-dynamic': DynamicTextPropertyEditor,
    'datatable': DataTablePropertyEditor,
    'image': ImagePropertyEditor,
    'container': ContainerPropertyEditor,
    'grid': GridPropertyEditor,
    'signature-block': SignaturePropertyEditor,
    'shape': ShapePropertyEditor,
  };
  return editors[type] || TextPropertyEditor;
};

// Load test groups and report groups from backend
const loadTestGroups = async () => {
  try {
    const response = await api.get('/test-groups');
    testGroups.value = response.data || [];
  } catch (error) {
    console.warn('Could not load test groups:', error);
  }
};

const loadReportGroups = async () => {
  try {
    const response = await api.get('/report-groups');
    reportGroups.value = response.data || [];
  } catch (error) {
    console.warn('Could not load report groups:', error);
  }
};

// Load sample test results from database for preview
const loadPreviewTestData = async () => {
  try {
    // Fetch all test parameters
    const paramsResponse = await api.get('/test-parameters');
    const allParameters = paramsResponse.data || [];
    
    // Build preview test data from parameters
    const testData = allParameters.map((param, idx) => ({
      parameter_id: param.id,
      id: param.id,
      test_name: param.display_name || param.test_name || param.name,
      result_value: generateMockValue(idx), // Generate realistic test values
      unit: param.unit || '-',
      reference_range: param.reference_range || '-',
      flags: '',
    }));
    
    // Update preview data with dynamic test results
    previewData.tests = testData;
  } catch (error) {
    console.warn('Could not load preview test data:', error);
    // Keep minimal default data if API fails
    previewData.tests = [];
  }
};

// Load real patient and laboratory request data from database
const loadPreviewPatientData = async () => {
  try {
    // Fetch a sample laboratory request with all related data
    const response = await api.get('/laboratory-requests?limit=1');
    const labRequest = response.data.data ? response.data.data[0] : null;
    
    if (labRequest) {
      // Update patient data with real data
      const patient = labRequest.patient || {};
      const doctor = labRequest.doctor || {};
      
      Object.assign(previewData.patient, {
        id: patient.id || null,
        first_name: patient.first_name || 'John',
        last_name: patient.last_name || 'Doe',
        middle_name: patient.middle_name || '',
        full_name: `${patient.first_name || ''} ${patient.last_name || ''}`.trim() || 'John Doe',
        email: patient.email || '',
        phone: patient.phone || '',
        gender: patient.gender || 'M',
        date_of_birth: patient.date_of_birth || '1990-01-15',
        age: calculateAge(patient.date_of_birth) || 34,
        address: patient.address || '',
        civil_status: patient.civil_status || '',
        patient_type: labRequest.patientType?.name || 'In-Patient',
      });
      
      // Update sample/lab request data with real data
      Object.assign(previewData.sample, {
        id: labRequest.sample_id || 'S001',
        sample_id: labRequest.sample_id || 'S001',
        laboratory_request_id: labRequest.id || null,
        request_date: labRequest.request_date || new Date().toISOString(),
        collected_at: new Date().toISOString(),
        processed_at: new Date().toISOString(),
        released_date: new Date().toISOString(),
        priority_level: labRequest.priority_level || 'Normal',
        status: labRequest.status || 'Completed',
      });
      
      // Add doctor information
      if (doctor && doctor.id) {
        Object.assign(previewData, {
          doctor: {
            id: doctor.id,
            name: `${doctor.first_name || ''} ${doctor.last_name || ''}`.trim() || 'Dr. Smith',
            specialization: doctor.specialization || 'General Practitioner',
          },
        });
      }
      
      // Add referring doctor if available
      if (labRequest.referring_doctor) {
        previewData.referring_doctor = labRequest.referring_doctor;
      }
      
      // Store available patient fields for dynamic text editor
      storeAvailableDataFields();
    }
  } catch (error) {
    console.warn('Could not load preview patient data:', error);
    // Keep default data if API fails
  }
};

// Calculate age from date of birth
const calculateAge = (dob) => {
  if (!dob) return null;
  const today = new Date();
  const birthDate = new Date(dob);
  let age = today.getFullYear() - birthDate.getFullYear();
  const monthDiff = today.getMonth() - birthDate.getMonth();
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
    age--;
  }
  return age;
};

// Store all available data fields for dynamic text element selector
const availableDataFields = ref([]);

const storeAvailableDataFields = () => {
  availableDataFields.value = [
    // Patient fields
    { category: 'Patient', label: 'Full Name', path: 'patient.full_name' },
    { category: 'Patient', label: 'First Name', path: 'patient.first_name' },
    { category: 'Patient', label: 'Last Name', path: 'patient.last_name' },
    { category: 'Patient', label: 'Email', path: 'patient.email' },
    { category: 'Patient', label: 'Phone', path: 'patient.phone' },
    { category: 'Patient', label: 'Gender', path: 'patient.gender' },
    { category: 'Patient', label: 'Date of Birth', path: 'patient.date_of_birth' },
    { category: 'Patient', label: 'Age', path: 'patient.age' },
    { category: 'Patient', label: 'Address', path: 'patient.address' },
    { category: 'Patient', label: 'Civil Status', path: 'patient.civil_status' },
    { category: 'Patient', label: 'Patient Type', path: 'patient.patient_type' },
    
    // Sample/Lab Request fields
    { category: 'Sample', label: 'Sample ID', path: 'sample.sample_id' },
    { category: 'Sample', label: 'Lab Request ID', path: 'sample.laboratory_request_id' },
    { category: 'Sample', label: 'Request Date', path: 'sample.request_date' },
    { category: 'Sample', label: 'Collected Date', path: 'sample.collected_at' },
    { category: 'Sample', label: 'Processed Date', path: 'sample.processed_at' },
    { category: 'Sample', label: 'Released Date', path: 'sample.released_date' },
    { category: 'Sample', label: 'Priority Level', path: 'sample.priority_level' },
    { category: 'Sample', label: 'Status', path: 'sample.status' },
    
    // Lab information
    { category: 'Lab', label: 'Lab Name', path: 'lab.name' },
    { category: 'Lab', label: 'Lab Address', path: 'lab.address' },
    
    // Doctor information (if available)
    { category: 'Doctor', label: 'Doctor Name', path: 'doctor.name' },
    { category: 'Doctor', label: 'Specialization', path: 'doctor.specialization' },
  ];
};

// Helper to generate realistic mock values for preview
const generateMockValue = (index) => {
  const mockValues = [
    '7.2', '4.8', '15.2', '45', '250',
    '0.8', '0.2', '0.6', '32', '28',
    '65', '7.2', '4.1', '95', '18',
    '0.9', '138', '4.2', '102', '9.2',
  ];
  return mockValues[index % mockValues.length];
};

// When test group is selected, fetch available columns
const onTestGroupChange = async () => {
  if (!schema.metadata.testGroupId) {
    availableColumns.value = [];
    return;
  }
  try {
    const response = await api.get(`/test-groups/${schema.metadata.testGroupId}/columns`);
    availableColumns.value = (response.data || []).map(col => ({
      label: col.label || col.name || col.display_name,
      value: col.field || col.name || col.id,
    }));
  } catch (error) {
    console.warn('Could not load columns for test group:', error);
    availableColumns.value = [];
  }
};

// When columns are selected, update preview data
const onColumnsChange = () => {
  // Could trigger a data fetch to update previewData with selected columns only
  ElMessage.info(`${schema.metadata.columns.length} columns selected`);
};

// Load template if in edit mode
onMounted(() => {
  loadTestGroups();
  loadReportGroups();
  loadPreviewTestData(); // Load dynamic test parameters from database
  loadPreviewPatientData(); // Load real patient and sample data from database
  if (route.params.id) {
    loadTemplate();
  }
});

const loadTemplate = async () => {
  try {
    const response = await api.get(`/report-templates/${route.params.id}`);
    currentTemplate.value = response.data;
    if (response.data.schema_data) {
      Object.assign(schema, response.data.schema_data);
    }
  } catch (error) {
    ElMessage.error('Failed to load template');
  }
};
</script>

<style scoped>
.report-designer {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: #f5f5f5;
  color:#606266;
}

.designer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: white;
  border-bottom: 1px solid #e0e0e0;
  flex-wrap: wrap;
  gap: 16px;
}

.header-left {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.header-left h2 {
  margin: 0;
  font-size: 20px;
}

.template-config {
  display: flex;
  gap: 12px;
  align-items: center;
}

.template-name {
  color: #909399;
  font-size: 12px;
  margin-left: 12px;
}

.header-right {
  display: flex;
  gap: 8px;
}

.designer-container {
  display: flex;
  flex: 1;
  overflow: hidden;
}

.left-panel,
.right-panel {
  width: 280px;
  background: white;
  border-right: 1px solid #e0e0e0;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.data-source-config {
  padding: 12px;
  background: #f9f9f9;
  border-bottom: 1px solid #e0e0e0;
}

.config-header {
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 12px;
  color: #333;
}

.right-panel {
  border-left: 1px solid #e0e0e0;
  border-right: none;
}

.toolbar-header,
.properties-header {
  padding: 12px;
  font-weight: 600;
  border-bottom: 1px solid #f0f0f0;
  background: #fafafa;
}

.components-list {
  flex: 1;
  overflow-y: auto;
}

.component-group {
  padding: 12px;
}

.group-title {
  font-size: 12px;
  font-weight: 600;
  color: #606266;
  margin-bottom: 8px;
  text-transform: uppercase;
}

.component-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 8px;
  background: #f5f7fa;
  border-radius: 4px;
  margin-bottom: 6px;
  cursor: move;
  border: 1px solid transparent;
  transition: all 0.2s;
}

.component-item:hover {
  background: #e6f7ff;
  border-color: var(--color-primary);
}

.component-item .icon {
  font-size: 18px;
}

.component-item .label {
  font-size: 13px;
  flex: 1;
}

.center-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #efefef;
}

.canvas-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: white;
  border-bottom: 1px solid #e0e0e0;
  font-size: 12px;
}

.zoom-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.canvas-container {
  flex: 1;
  overflow: auto;
  padding: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.canvas {
    background: white;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
    position: relative;
    margin-bottom: 200px;
    display: flex;
    flex-direction: column;
  }

.canvas-element {
  cursor: move;
  outline: 2px dashed transparent;
  transition: outline 0.2s;
  z-index: 2;
}

.canvas-element.selected {
  outline: 2px dashed var(--color-primary);
}

.canvas-element:hover {
  outline: 2px dashed #ff9800;
}

.properties-form {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.no-selection {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Preview container styles to make preview match designer canvas */
.preview-custom-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 20px;
  background-color: #555; /* Darker background for the header */
  color: #fff;
  font-size: 13px;
}

.preview-custom-header .header-left span {
  font-weight: bold;
}

.preview-custom-header .header-right {
  display: flex;
  align-items: center;
  gap: 10px;
}

.preview-custom-header .zoom-controls {
  display: flex;
  align-items: center;
  gap: 5px;
}



.el-dialog.is-fullscreen .el-dialog__body {
  padding: 0;
  background-color: #333; /* Dark background for the modal body */
}

.preview-container {
  height: calc(100vh - 100px);
  background: #f5f5f5;
  display: flex;
  justify-content: center;
  align-items: center;
}

  .report-preview {
  background-color: #ffffff; /* White background for the paper */
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1); /* Enhanced shadow for paper separation */
  border-radius: 2px; /* Subtle paper edge rounding */
  /* The width and height are already set by previewWrapperStyle */
  padding: 0 !important;
}

/* Ensure ReportRenderer inside preview uses exact A4 canvas dimensions */
.report-preview .report-renderer {
  width: 210mm;
  max-width: none;
  min-width: 210mm;
  margin: 0;
}

</style>


