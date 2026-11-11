<template>
  <div class="datatable-editor">
    <div class="form-section">
      <label style="font-weight: 600; margin-bottom: 8px; display: block;">Data Source Type</label>
      <select v-model="local.dataSource" @change="emitUpdate" style="width: 100%;">
        <option value="tests">Tests (Default)</option>
        <option value="results">Test Results</option>
        <option value="samples">Samples</option>
      </select>
    </div>

    <div class="form-section">
      <label style="font-weight: 600; margin-bottom: 8px; display: block;">📐 Layout Orientation</label>
      <div style="display: flex; gap: 8px;">
        <label style="flex: 1; display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 8px; border: 1px solid #dcdfe6; border-radius: 4px;" :style="local.layoutOrientation === 'columns' ? 'background: var(--color-primary-light); border-color: var(--color-primary);' : ''">
          <input type="radio" v-model="local.layoutOrientation" value="columns" @change="emitUpdate" />
          <span>Columns</span>
        </label>
        <label style="flex: 1; display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 8px; border: 1px solid #dcdfe6; border-radius: 4px;" :style="local.layoutOrientation === 'rows' ? 'background: var(--color-primary-light); border-color: var(--color-primary);' : ''">
          <input type="radio" v-model="local.layoutOrientation" value="rows" @change="emitUpdate" />
          <span>Rows</span>
        </label>
      </div>
    </div>

    <div class="form-section">
      <label style="font-weight: 600; margin-bottom: 8px; display: block;">📋 Select Test Parameters</label>
      <div class="parameter-selection">
        <input 
          type="text" 
          v-model="parameterSearch" 
          placeholder="Search parameters..." 
          style="width: 100%; margin-bottom: 8px; padding: 6px 8px; border: 1px solid #dcdfe6; border-radius: 4px;"
        />
        <div class="parameter-list">
          <div v-for="param in filteredParameters" :key="param.id" class="parameter-item">
            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 6px; margin: 0;">
              <input 
                type="checkbox" 
                :checked="isParameterSelected(param.id)"
                @change="toggleParameter(param)"
                style="cursor: pointer;"
              />
              <span>{{ param.display_name || param.test_name || param.name }}</span>
              <span v-if="param.unit" style="font-size: 12px; color: #909399;">({{ param.unit }})</span>
            </label>
          </div>
          <div v-if="filteredParameters.length === 0" style="padding: 12px; text-align: center; color: #909399; font-size: 12px;">
            No parameters found
          </div>
        </div>
      </div>
    </div>

    <el-divider />

    <div class="form-section">
      <label style="font-weight: 600; margin-bottom: 8px; display: block;">📊 Column Headers Configuration</label>
      <div style="display: flex; flex-direction: column; gap: 6px;">
        <div v-for="(header, idx) in local.columnHeaders" :key="idx" class="header-config">
          <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
            <label style="font-size: 12px; color: #606266; flex: 1;">
              {{ header.label }}
            </label>
            <button 
              type="button"
              @click="deleteColumn(idx)"
              style="padding: 4px 8px; font-size: 12px; background: var(--color-danger); color: white; border: none; border-radius: 3px; cursor: pointer; transition: background 0.2s;"
              @mouseenter="$event.target.style.background = 'var(--color-danger-dark)'"
              @mouseleave="$event.target.style.background = 'var(--color-danger)'"
            >
              ✕ Delete
            </button>
          </div>
          <input 
            type="text" 
            v-model="header.displayName" 
            @input="emitUpdate" 
            placeholder="Display name"
            style="width: 100%; padding: 6px 8px; border: 1px solid #dcdfe6; border-radius: 4px; font-size: 12px;"
          />
        </div>
        <button 
          type="button"
          @click="addColumn"
          style="padding: 8px 12px; font-size: 12px; background: var(--color-primary); color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 8px; transition: background 0.2s;"
          @mouseenter="$event.target.style.background = 'var(--color-primary-dark)'"
          @mouseleave="$event.target.style.background = 'var(--color-primary)'"
        >
          + Add Column
        </button>
      </div>
    </div>

    <el-divider />

    <div class="form-row">
      <label>Test/Report Group Reference (Optional)</label>
      <input type="text" v-model="local.groupReference" @input="emitUpdate" placeholder="Link to group ID or name" />
    </div>

    <div class="form-row">
      <label>Group By Field (Optional)</label>
      <input type="text" v-model="local.groupBy" @input="emitUpdate" placeholder="e.g. test_category" />
    </div>

    <div class="form-row">
      <label>Width</label>
      <input type="text" v-model="local.width" @input="emitUpdate" placeholder="e.g. 100% or 400px" />
    </div>

    <el-divider />

    <div class="form-section">
      <label style="font-weight: 600; margin-bottom: 8px; display: block;">✨ Preview</label>
      <div style="background: #f9f9f9; padding: 12px; border-radius: 4px; border: 1px solid #e0e0e0; font-size: 12px;">
        <div v-if="local.selectedParameterIds.length === 0" style="color: #909399; text-align: center;">
          Select parameters to see preview
        </div>
        <div v-else>
          <strong>Layout:</strong> {{ local.layoutOrientation === 'columns' ? '📊 Columns (Table)' : '📑 Rows (Stacked)' }}<br />
          <strong>Parameters:</strong> {{ local.selectedParameterIds.length }} selected<br />
          <strong>Columns:</strong> {{ local.columnHeaders.map(h => h.displayName).join(', ') }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed, onMounted } from 'vue';
import api from '@/utils/axios';

const props = defineProps({ element: { type: Object, required: true } });
const emit = defineEmits(['update']);

const parameterSearch = ref('');
const allParameters = ref([]);

// Standard column headers for test result tables
const STANDARD_HEADERS = [
  { label: 'parameter', displayName: 'Parameter' },
  { label: 'result_value', displayName: 'Result' },
  { label: 'unit', displayName: 'Unit' },
  { label: 'ref_range', displayName: 'Reference Range' },
  { label: 'flags', displayName: 'Flags' },
];

const local = reactive({
  dataSource: props.element.dataSource || 'tests',
  layoutOrientation: props.element.layoutOrientation || 'columns', // 'columns' or 'rows'
  groupReference: props.element.groupReference || '',
  groupBy: props.element.groupBy || '',
  width: props.element.width || '100%',
  selectedParameterIds: props.element.selectedParameterIds || [],
  columnHeaders: props.element.columnHeaders ? JSON.parse(JSON.stringify(props.element.columnHeaders)) : JSON.parse(JSON.stringify(STANDARD_HEADERS)),
});

// ===== FUNCTION DEFINITIONS =====

const loadParameters = async () => {
  try {
    const response = await api.get('/test-parameters');
    allParameters.value = response.data || [];
  } catch (error) {
    console.warn('Could not load test parameters:', error);
  }
};

const isParameterSelected = (paramId) => {
  return local.selectedParameterIds.includes(paramId);
};

const toggleParameter = (param) => {
  const idx = local.selectedParameterIds.indexOf(param.id);
  if (idx > -1) {
    local.selectedParameterIds.splice(idx, 1);
  } else {
    local.selectedParameterIds.push(param.id);
  }
  emitUpdate();
};

const addColumn = () => {
  const newColumnLabel = `column_${Date.now()}`;
  local.columnHeaders.push({
    label: newColumnLabel,
    displayName: 'New Column',
  });
  emitUpdate();
};

const deleteColumn = (idx) => {
  if (local.columnHeaders.length > 1) {
    local.columnHeaders.splice(idx, 1);
    emitUpdate();
  } else {
    alert('Must keep at least one column');
  }
};

const emitUpdate = () => {
  emit('update', {
    dataSource: local.dataSource,
    layoutOrientation: local.layoutOrientation,
    groupReference: local.groupReference || undefined,
    groupBy: local.groupBy || undefined,
    width: local.width,
    selectedParameterIds: local.selectedParameterIds,
    columnHeaders: local.columnHeaders,
  });
};

// ===== COMPUTED PROPERTIES =====

const filteredParameters = computed(() => {
  if (!parameterSearch.value) return allParameters.value;
  const search = parameterSearch.value.toLowerCase();
  return allParameters.value.filter(p => 
    (p.display_name || p.test_name || p.name || '').toLowerCase().includes(search) ||
    (p.unit || '').toLowerCase().includes(search)
  );
});

// ===== WATCHERS & LIFECYCLE =====

watch(() => props.element, (v) => {
  local.dataSource = v.dataSource || 'tests';
  local.layoutOrientation = v.layoutOrientation || 'columns';
  local.groupReference = v.groupReference || '';
  local.groupBy = v.groupBy || '';
  local.width = v.width || '100%';
  local.selectedParameterIds = v.selectedParameterIds || [];
  local.columnHeaders = v.columnHeaders ? JSON.parse(JSON.stringify(v.columnHeaders)) : JSON.parse(JSON.stringify(STANDARD_HEADERS));
});

onMounted(() => {
  if (allParameters.value.length === 0) {
    loadParameters();
  }
});
</script>

<style scoped>
.datatable-editor { 
  display: flex; 
  flex-direction: column; 
  gap: 12px; 
}

.form-row { 
  display: flex; 
  flex-direction: column; 
}

.form-section { 
  display: flex; 
  flex-direction: column; 
  gap: 8px; 
}

label { 
  font-size: 12px; 
  color: #606266; 
  margin-bottom: 4px 
}

input, select { 
  padding: 6px 8px; 
  border: 1px solid #dcdfe6; 
  border-radius: 4px; 
  font-size: 12px; 
}

.parameter-list { 
  border: 1px solid #dcdfe6; 
  border-radius: 4px; 
  max-height: 200px; 
  overflow-y: auto; 
  background: white;
}

.parameter-item {
  border-bottom: 1px solid #f0f0f0;
  transition: background 0.2s;
}

.parameter-item:hover {
  background: #f9f9f9;
}

.parameter-item:last-child {
  border-bottom: none;
}

.column-row { 
  display: flex; 
  gap: 4px; 
  align-items: center;
  margin-bottom: 6px;
  padding: 6px;
  background: white;
  border-radius: 3px;
  border: 1px solid #e0e0e0;
}

.column-row input { 
  flex: 1; 
  padding: 4px 6px;
  font-size: 12px;
}

.column-row label { 
  margin: 0; 
  display: flex; 
  align-items: center; 
  gap: 4px;
  white-space: nowrap;
}

.column-row input[type="checkbox"] { 
  width: 14px;
  cursor: pointer;
}

.btn-remove { 
  padding: 3px 6px; 
  background: var(--color-danger); 
  color: white; 
  border: none; 
  border-radius: 3px; 
  cursor: pointer;
  font-size: 13px;
  font-weight: bold;
}

.btn-remove:hover { 
  background: var(--color-danger-dark); 
}

.btn-regenerate { 
  padding: 6px 12px; 
  background: var(--color-primary); 
  color: white; 
  border: none; 
  border-radius: 4px; 
  cursor: pointer; 
  font-size: 12px;
  width: 100%;
}

.btn-regenerate:hover { 
  background: var(--color-primary-dark); 
}

.header-config {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 8px;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 3px;
}

.header-config input {
  width: 100%;
  padding: 6px 8px;
  font-size: 12px;
}
</style>
