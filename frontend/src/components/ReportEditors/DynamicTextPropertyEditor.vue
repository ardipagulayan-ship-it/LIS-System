<template>
  <div class="dynamic-editor">
    <div class="form-section">
      <label style="font-weight: 600; margin-bottom: 8px; display: block;">🔗 Select Field</label>
      <div v-if="availableFields && availableFields.length > 0" style="display: flex; flex-direction: column; gap: 8px;">
        <!-- Group by category -->
        <div v-for="category in groupedFields" :key="category.name" style="border: 1px solid #e0e0e0; border-radius: 4px; padding: 8px; background: #f9f9f9;">
          <div style="font-weight: 600; font-size: 12px; color: #606266; margin-bottom: 6px; text-transform: uppercase;">
            {{ category.name }}
          </div>
          <select 
            v-model="local.field" 
            @change="onFieldSelect"
            style="width: 100%; padding: 6px 8px; border: 1px solid #dcdfe6; border-radius: 4px; font-size: 12px;"
          >
            <option value="">-- Select {{ category.name }} field --</option>
            <option v-for="field in category.fields" :key="field.path" :value="field.path">
              {{ field.label }}
            </option>
          </select>
        </div>
      </div>
      <div v-else style="padding: 12px; color: #909399; font-size: 12px; background: #f5f5f5; border-radius: 4px;">
        Loading available fields...
      </div>
    </div>

    <el-divider />

    <div class="form-row">
      <label>Custom Field Path (Advanced)</label>
      <input type="text" v-model="local.field" @input="emitUpdate" placeholder="e.g. patient.name" />
    </div>

    <div class="form-row">
      <label>Display Label</label>
      <input type="text" v-model="local.label" @input="emitUpdate" placeholder="e.g. Patient Name" />
    </div>

    <div class="form-row">
      <label>Show Label</label>
      <select v-model="local.showLabel" @change="emitUpdate">
        <option :value="true">Yes</option>
        <option :value="false">No</option>
      </select>
    </div>

    <div class="form-row">
      <label>Width</label>
      <input type="text" v-model="local.width" @input="emitUpdate" placeholder="e.g. 200 or 50%" />
    </div>

    <div class="form-row">
      <label>Padding</label>
      <input type="text" v-model="local.style.padding" @input="emitUpdate" placeholder="e.g., 12px" />
    </div>
    <div class="form-row">
      <label>Margin</label>
      <input type="text" v-model="local.style.margin" @input="emitUpdate" placeholder="e.g., 12px" />
    </div>
    <div class="form-row">
      <label>Color</label>
      <el-color-picker v-model="local.style.color" @change="emitUpdate" />
    </div>
    <div class="form-row">
      <label>Text Align</label>
      <el-select v-model="local.style.textAlign" placeholder="Select" @change="emitUpdate">
        <el-option label="Left" value="left" />
        <el-option label="Center" value="center" />
        <el-option label="Right" value="right" />
      </el-select>
    </div>
    <div class="form-row">
      <label>Font Style</label>
      <el-select v-model="local.style.fontStyle" placeholder="Select" @change="emitUpdate">
        <el-option label="Normal" value="normal" />
        <el-option label="Italic" value="italic" />
      </el-select>
    </div>

    <div class="form-row">
      <label>Grid Column</label>
      <input type="text" v-model="local.gridColumn" @input="emitUpdate" placeholder="e.g., 1 / 3 or span 2" />
    </div>

    <div class="form-row">
      <label>Grid Row</label>
      <input type="text" v-model="local.gridRow" @input="emitUpdate" placeholder="e.g., 1 / 2 or span 1" />
    </div>
  </div>
</template>

<script setup>
import { reactive, watch, computed } from 'vue';
import { ElDivider, ElColorPicker, ElSelect, ElOption } from 'element-plus';

const props = defineProps({ 
  element: { type: Object, required: true },
  availableFields: { type: Array, default: () => [] },
});
const emit = defineEmits(['update']);

const local = reactive({
  field: props.element.field || '',
  label: props.element.label || '',
  showLabel: (props.element.label !== undefined),
  width: props.element.width,
  style: Object.assign({}, props.element.style || {}, {
    padding: props.element.style?.padding || '0mm',
    margin: props.element.style?.margin || '0mm',
    color: props.element.style?.color || '#000000',
    textAlign: props.element.style?.textAlign || 'left',
    fontStyle: props.element.style?.fontStyle || 'normal',
  }),
  gridColumn: props.element.gridColumn || '',
  gridRow: props.element.gridRow || '',
});

// Group available fields by category
const groupedFields = computed(() => {
  if (!props.availableFields || props.availableFields.length === 0) return [];
  
  const grouped = {};
  props.availableFields.forEach(field => {
    if (!grouped[field.category]) {
      grouped[field.category] = { name: field.category, fields: [] };
    }
    grouped[field.category].fields.push(field);
  });
  
  return Object.values(grouped);
});

watch(() => props.element, (v) => {
  local.field = v.field || '';
  local.label = v.label || '';
  local.showLabel = (v.label !== undefined);
  local.width = v.width;
  local.style = Object.assign({}, v.style || {}, {
    padding: v.style?.padding || '0mm',
    margin: v.style?.margin || '0mm',
    color: v.style?.color || '#000000',
    textAlign: v.style?.textAlign || 'left',
    fontStyle: v.style?.fontStyle || 'normal',
  });
  local.gridColumn = v.gridColumn || '';
  local.gridRow = v.gridRow || '';
});

const onFieldSelect = () => {
  // Auto-populate label from selected field
  const field = props.availableFields.find(f => f.path === local.field);
  if (field) {
    local.label = field.label;
  }
  emitUpdate();
};

const emitUpdate = () => {
  const payload = {
    field: local.field,
    label: local.showLabel ? local.label : undefined,
    width: local.width,
    style: local.style,
    gridColumn: local.gridColumn,
    gridRow: local.gridRow,
  };
  emit('update', payload);
};
</script>

<style scoped>
.dynamic-editor { display: flex; flex-direction: column; gap: 8px; }
.form-row { display: flex; flex-direction: column; }
.form-section { display: flex; flex-direction: column; gap: 8px; }
label { font-size: 12px; color: #606266; margin-bottom: 4px }
input, select { padding: 6px 8px; border: 1px solid #dcdfe6; border-radius: 4px; font-size: 12px; }
</style>
