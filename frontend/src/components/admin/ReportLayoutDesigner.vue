<template>
  <div class="report-layout-designer">
    <h3>Report Layout Designer</h3>
    <p>Create professional report layouts by selecting and arranging sections.</p>

    <el-row :gutter="16">
      <!-- Left Panel: Create/Edit Layout -->
      <el-col :span="12">
        <div class="panel">
          <h4>Create New Layout</h4>
          <el-form label-width="120px" size="small">
            <el-form-item label="Layout Name">
              <el-input v-model="newLayout.name" placeholder="e.g., Standard Report, LDL Panel" />
            </el-form-item>
            <el-form-item label="Page Size">
              <el-select v-model="newLayout.pageSize" placeholder="Select page size">
                <el-option label="A4" value="A4" />
                <el-option label="A5" value="A5" />
                <el-option label="Letter" value="Letter" />
              </el-select>
            </el-form-item>

            <h5 style="margin-top: 20px; margin-bottom: 12px;">Layout Sections</h5>
            <p style="color: #909399; font-size: 12px; margin-bottom: 12px;">Select the sections to include in your report.</p>

            <el-checkbox-group v-model="newLayout.sections" style="display: flex; flex-direction: column; gap: 8px;">
              <el-checkbox label="header">
                <span class="section-label">📄 Header</span>
                <span class="section-desc">Facility name, logo, report title</span>
              </el-checkbox>
              <el-checkbox label="patient-info">
                <span class="section-label">👤 Patient Information</span>
                <span class="section-desc">Name, ID, age, gender, date of birth</span>
              </el-checkbox>
              <el-checkbox label="results-table">
                <span class="section-label">📊 Results Table</span>
                <span class="section-desc">Test parameters with values and reference ranges</span>
              </el-checkbox>
              <el-checkbox label="footer">
                <span class="section-label">📋 Footer</span>
                <span class="section-desc">Signature line, date, technician info</span>
              </el-checkbox>
              <el-checkbox label="notes">
                <span class="section-label">📝 Notes Section</span>
                <span class="section-desc">Clinical notes and recommendations</span>
              </el-checkbox>
              <el-checkbox label="signatories">
                <span class="section-label">✍️ Signatories</span>
                <span class="section-desc">Signature lines for authorized personnel</span>
              </el-checkbox>
            </el-checkbox-group>

            <el-divider />

            <h5 style="margin-top:12px">Header Zone</h5>
            <el-form-item label="Left Logo">
              <el-input v-model="newLayout.header.left_logo" placeholder="Image URL (optional)" />
            </el-form-item>
            <el-form-item label="Center Title">
              <el-input v-model="newLayout.header.title" placeholder="Report title" />
            </el-form-item>
            <el-form-item label="Right Logo">
              <el-input v-model="newLayout.header.right_logo" placeholder="Image URL (optional)" />
            </el-form-item>

            <h5 style="margin-top:12px">Patient Info Zone</h5>
            <el-form-item label="Fields">
              <el-select v-model="newLayout.patientInfo.fields" multiple placeholder="Pick fields">
                <el-option v-for="f in patientFields" :key="f.value" :label="f.label" :value="f.value" />
              </el-select>
            </el-form-item>
            <el-form-item label="Columns">
              <el-slider v-model="newLayout.patientInfo.columns" :min="1" :max="3" show-stops />
            </el-form-item>

            <h5 style="margin-top:12px">Results Zone</h5>
            <el-form-item label="Show Units">
              <el-switch v-model="newLayout.results.show_units" />
            </el-form-item>
            <el-form-item label="Show Ref Range">
              <el-switch v-model="newLayout.results.show_ref_range" />
            </el-form-item>
            <el-form-item label="Show Flags">
              <el-switch v-model="newLayout.results.show_flags" />
            </el-form-item>

            <h5 style="margin-top:12px">Signatories</h5>
            <div v-for="(s, idx) in newLayout.signatories" :key="idx" style="display:flex; gap:8px; margin-bottom:6px; align-items:center;">
              <el-input v-model="s.role" placeholder="Role (e.g., Pathologist)" />
              <el-input v-model="s.name" placeholder="Name" />
              <el-button type="danger" icon="el-icon-delete" @click="removeSignatory(idx)" size="small"></el-button>
            </div>
            <el-button type="primary" @click="addSignatory" size="small">Add Signatory</el-button>

            <div style="margin-top: 20px;">
              <el-button type="primary" @click="saveLayout" size="default">Save Layout</el-button>
              <el-button @click="resetForm" size="default">Clear</el-button>
            </div>
          </el-form>
        </div>
      </el-col>

      <!-- Right Panel: Existing Layouts -->
      <el-col :span="12">
        <div class="panel">
          <h4>Saved Layouts</h4>
          <el-empty v-if="layouts.length === 0" description="No layouts created yet" />
          
          <div v-else class="layouts-list">
            <div v-for="layout in layouts" :key="layout.id" class="layout-card">
              <div class="layout-header">
                <h5>{{ layout.name }}</h5>
                <span class="page-size-badge">{{ layout.page_size || 'A4' }}</span>
              </div>
              
              <div class="layout-sections">
                <div v-if="parseSections(layout.sections_json || layout.layout_json).length > 0" class="sections-grid">
                  <span v-for="section in parseSections(layout.sections_json || layout.layout_json)" :key="section" class="section-tag">
                    {{ formatSectionName(section) }}
                  </span>
                </div>
                <div v-else class="no-sections">No sections configured</div>
              </div>

              <div class="layout-actions">
                <el-button size="small" link type="primary" @click="editLayout(layout)">Edit</el-button>
                <el-button size="small" link type="danger" @click="deleteLayout(layout)">Delete</el-button>
              </div>
            </div>
          </div>
        </div>
      </el-col>
    </el-row>

    <!-- Edit Dialog -->
    <el-dialog v-model="editDialogVisible" title="Edit Layout" width="500px">
      <el-form v-if="editingLayout" label-width="120px" size="small">
        <el-form-item label="Layout Name">
          <el-input v-model="editingLayout.name" />
        </el-form-item>
        <el-form-item label="Page Size">
          <el-select v-model="editingLayout.page_size">
            <el-option label="A4" value="A4" />
            <el-option label="A5" value="A5" />
            <el-option label="Letter" value="Letter" />
          </el-select>
        </el-form-item>

        <h5 style="margin: 16px 0 12px 0;">Layout Sections</h5>
        <el-checkbox-group v-model="editingSections" style="display: flex; flex-direction: column; gap: 8px;">
          <el-checkbox label="header">
            <span class="section-label">📄 Header</span>
          </el-checkbox>
          <el-checkbox label="patient-info">
            <span class="section-label">👤 Patient Information</span>
          </el-checkbox>
          <el-checkbox label="results-table">
            <span class="section-label">📊 Results Table</span>
          </el-checkbox>
          <el-checkbox label="footer">
            <span class="section-label">📋 Footer</span>
          </el-checkbox>
          <el-checkbox label="notes">
            <span class="section-label">📝 Notes Section</span>
          </el-checkbox>
          <el-checkbox label="signatories">
            <span class="section-label">✍️ Signatories</span>
          </el-checkbox>
        </el-checkbox-group>

        <div style="margin-top:12px;" v-if="editingLayout._config">
          <h5 style="margin-top:12px">Header Zone</h5>
          <el-form-item label="Left Logo">
            <el-input v-model="editingLayout._config.header.left_logo" placeholder="Image URL" />
          </el-form-item>
          <el-form-item label="Title">
            <el-input v-model="editingLayout._config.header.title" />
          </el-form-item>
          <el-form-item label="Right Logo">
            <el-input v-model="editingLayout._config.header.right_logo" placeholder="Image URL" />
          </el-form-item>

          <h5 style="margin-top:12px">Patient Info</h5>
          <el-form-item label="Fields">
            <el-select v-model="editingLayout._config.patient_info.fields" multiple>
              <el-option v-for="f in patientFields" :key="f.value" :label="f.label" :value="f.value" />
            </el-select>
          </el-form-item>
          <el-form-item label="Columns">
            <el-slider v-model="editingLayout._config.patient_info.columns" :min="1" :max="3" />
          </el-form-item>

          <h5 style="margin-top:12px">Results Zone</h5>
          <el-form-item label="Show Units">
            <el-switch v-model="editingLayout._config.results.show_units" />
          </el-form-item>
          <el-form-item label="Show Ref Range">
            <el-switch v-model="editingLayout._config.results.show_ref_range" />
          </el-form-item>
          <el-form-item label="Show Flags">
            <el-switch v-model="editingLayout._config.results.show_flags" />
          </el-form-item>

          <h5 style="margin-top:12px">Signatories</h5>
          <div v-for="(s, idx) in editingLayout._config.signatories" :key="idx" style="display:flex; gap:8px; margin-bottom:6px; align-items:center;">
            <el-input v-model="s.role" placeholder="Role" />
            <el-input v-model="s.name" placeholder="Name" />
          </div>
        </div>
      </el-form>

      <template #footer>
        <el-button @click="editDialogVisible = false">Cancel</el-button>
        <el-button type="primary" @click="updateLayout">Update</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '@/utils/axios';
import { ElMessage, ElMessageBox } from 'element-plus';

const layouts = ref([]);
const editDialogVisible = ref(false);
const editingLayout = ref(null);
const editingSections = ref([]);

const patientFields = [
  { label: 'Patient ID', value: 'patient_id' },
  { label: 'First Name', value: 'first_name' },
  { label: 'Last Name', value: 'last_name' },
  { label: 'DOB', value: 'date_of_birth' },
  { label: 'Age', value: 'age' },
  { label: 'Gender', value: 'gender' },
  { label: 'Sample Collected At', value: 'sample_collected_at' }
];

const newLayout = reactive({
  name: '',
  pageSize: 'A4',
  sections: ['header', 'patient-info', 'results-table', 'signatories', 'footer'],
  header: { left_logo: '', title: 'Laboratory Report', right_logo: '' },
  patientInfo: { fields: ['patient_id', 'last_name', 'first_name'], columns: 2 },
  results: { show_units: true, show_ref_range: true, show_flags: true },
  signatories: []
});

const loadLayouts = async () => {
  try {
    console.log('Loading layouts...');
    const resp = await api.get('/report-layouts');
    console.log('Layouts response:', resp);
    layouts.value = (resp.data && Array.isArray(resp.data)) ? resp.data : (resp.data?.data || []);
    console.log('Loaded layouts:', layouts.value);
  } catch (e) {
    console.error('Failed to load layouts', e);
    ElMessage.error(`Failed to load layouts: ${e.message}`);
  }
};

const saveLayout = async () => {
  if (!newLayout.name.trim()) {
    ElMessage.error('Please enter a layout name');
    return;
  }
  if (newLayout.sections.length === 0) {
    ElMessage.error('Please select at least one section');
    return;
  }

  try {
    const sectionsObj = {
      sections: newLayout.sections,
      header: newLayout.header,
      patient_info: newLayout.patientInfo,
      results: newLayout.results,
      signatories: newLayout.signatories
    };

    await api.post('/report-layouts', {
      name: newLayout.name,
      page_size: newLayout.pageSize,
      sections_json: sectionsObj,
      page_config: { page_size: newLayout.pageSize, orientation: 'portrait', margins: { top: '20mm', bottom: '20mm', left: '15mm', right: '15mm' } }
    });

    ElMessage.success('Layout saved successfully!');
    resetForm();
    await loadLayouts();
  } catch (e) {
    console.error('Failed to save layout', e);
    ElMessage.error(`Failed to save layout: ${e.message}`);
  }
};

const resetForm = () => {
  newLayout.name = '';
  newLayout.pageSize = 'A4';
  newLayout.sections = ['header', 'patient-info', 'results-table', 'signatories', 'footer'];
  newLayout.header = { left_logo: '', title: 'Laboratory Report', right_logo: '' };
  newLayout.patientInfo = { fields: ['patient_id', 'last_name', 'first_name'], columns: 2 };
  newLayout.results = { show_units: true, show_ref_range: true, show_flags: true };
  newLayout.signatories = [];
};

const editLayout = (layout) => {
  editingLayout.value = { ...layout };
  // load sections and config
  const sectionsData = layout.sections_json || layout.layout_json || null;
  let sections = [];
  let config = {};
  try {
    if (sectionsData) {
      if (typeof sectionsData === 'string') {
        const parsed = JSON.parse(sectionsData);
        if (Array.isArray(parsed)) {
          sections = parsed;
        } else if (parsed && parsed.sections) {
          sections = parsed.sections || [];
          config = parsed;
        }
      } else if (Array.isArray(sectionsData)) {
        sections = sectionsData;
      } else if (sectionsData.sections) {
        sections = sectionsData.sections || [];
        config = sectionsData;
      }
    }
  } catch (e) {
    console.error('Failed to parse layout config for edit', e);
  }

  editingSections.value = sections;
  // fill editingLayout with config knobs so update can send them
  // ensure config has sensible defaults
  config.header = config.header || { left_logo: '', title: layout.name || 'Laboratory Report', right_logo: '' };
  config.patient_info = config.patient_info || { fields: ['patient_id', 'last_name', 'first_name'], columns: 2 };
  config.results = config.results || { show_units: true, show_ref_range: true, show_flags: true };
  config.signatories = config.signatories || [];
  editingLayout.value._config = config;
  editDialogVisible.value = true;
};

const updateLayout = async () => {
  if (!editingLayout.value) return;

  try {
    const sections = editingSections.value;

    // merge edited config if present
    const cfg = editingLayout.value._config || {};
    const sectionsObj = Object.assign({}, cfg, { sections: sections });

    await api.put(`/report-layouts/${editingLayout.value.id}`, {
      name: editingLayout.value.name,
      page_size: editingLayout.value.page_size,
      sections_json: sectionsObj,
      page_config: { page_size: editingLayout.value.page_size, orientation: cfg.page_config?.orientation || 'portrait' }
    });

    ElMessage.success('Layout updated successfully!');
    editDialogVisible.value = false;
    await loadLayouts();
  } catch (e) {
    console.error('Failed to update layout', e);
    ElMessage.error(`Failed to update layout: ${e.message}`);
  }
};

const addSignatory = () => {
  newLayout.signatories.push({ role: '', name: '' });
};

const removeSignatory = (idx) => {
  newLayout.signatories.splice(idx, 1);
};

const deleteLayout = async (layout) => {
  try {
    await ElMessageBox.confirm(
      `Delete layout "${layout.name}"? This cannot be undone.`,
      'Confirm Delete',
      { type: 'warning' }
    );

    await api.delete(`/report-layouts/${layout.id}`);
    ElMessage.success('Layout deleted successfully!');
    await loadLayouts();
  } catch (e) {
    if (e.message !== 'cancel') {
      console.error('Failed to delete layout', e);
      ElMessage.error(`Failed to delete layout: ${e.message}`);
    }
  }
};

const parseSections = (layoutJson) => {
  try {
    // Support new `sections_json` and legacy `layout_json`
    try {
      if (!layoutJson) return [];
      if (Array.isArray(layoutJson)) return layoutJson;
      // layoutJson might be an object with `sections` or an array
      if (typeof layoutJson === 'object') return layoutJson.sections || [];
      // if it's a string, try parse
      const parsed = typeof layoutJson === 'string' ? JSON.parse(layoutJson) : layoutJson;
      return parsed.sections || [];
    } catch (e) {
      console.error('Failed to parse layout JSON', e);
      return [];
    }
  } catch (e) {
    console.error('Failed to parse layout JSON', e);
    return [];
  }
};

const formatSectionName = (section) => {
  const names = {
    'header': '📄 Header',
    'patient-info': '👤 Patient Info',
    'results-table': '📊 Results',
    'footer': '📋 Footer',
    'notes': '📝 Notes'
  };
  return names[section] || section;
};

onMounted(() => {
  loadLayouts();
});
</script>

<style scoped>
.report-layout-designer {
  padding: 16px;
}

.panel {
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  padding: 16px;
  background: #fafafa;
}

.panel h4 {
  margin: 0 0 12px 0;
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.panel h5 {
  margin: 0 0 8px 0;
  font-size: 13px;
  font-weight: 600;
  color: #606266;
}

.section-label {
  font-weight: 500;
  display: inline-block;
  min-width: 150px;
}

.section-desc {
  font-size: 12px;
  color: #909399;
  margin-left: 8px;
}

.layouts-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.layout-card {
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  padding: 12px;
  background: white;
  transition: all 0.2s ease;
}

.layout-card:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border-color: var(--color-primary);
}

.layout-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.layout-header h5 {
  margin: 0;
  font-size: 13px;
  font-weight: 600;
}

.page-size-badge {
  display: inline-block;
  padding: 2px 8px;
  background: #e0f2fe;
  color: #0369a1;
  border-radius: 3px;
  font-size: 12px;
  font-weight: 500;
}

.layout-sections {
  margin: 8px 0;
}

.sections-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.section-tag {
  display: inline-block;
  padding: 4px 8px;
  background: #f0f9ff;
  color: #0369a1;
  border: 1px solid #bae6fd;
  border-radius: 3px;
  font-size: 12px;
}

.no-sections {
  font-size: 12px;
  color: #909399;
  font-style: italic;
}

.layout-actions {
  display: flex;
  gap: 8px;
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid #f0f0f0;
}
</style>
