<template>
  <div class="grid-editor">
    <div class="form-row">
      <label>Columns</label>
      <input type="number" v-model.number="local.columns" @input="emitUpdate" min="1" max="6" />
    </div>

    <div class="form-row">
      <label>Rows</label>
      <input type="number" v-model.number="local.rows" @input="emitUpdate" min="1" />
    </div>

    <div class="form-row">
      <label>Grid Template Columns (CSS)</label>
      <input type="text" v-model="local.gridTemplateColumns" @input="emitUpdate" placeholder="e.g., 1fr 1fr auto" />
    </div>

    <div class="form-row">
      <label>Grid Template Rows (CSS)</label>
      <input type="text" v-model="local.gridTemplateRows" @input="emitUpdate" placeholder="e.g., auto 1fr 1fr" />
    </div>

    <div class="form-row">
      <label>Gap (px)</label>
      <input type="number" v-model.number="local.gap" @input="emitUpdate" placeholder="e.g. 12" />
    </div>

    <div class="form-row">
      <label>Width</label>
      <input type="text" v-model="local.width" @input="emitUpdate" placeholder="e.g. 100% or 400px" />
    </div>

    <div class="form-row">
      <label>Height</label>
      <input type="text" v-model="local.height" @input="emitUpdate" placeholder="e.g. auto or 200px" />
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
const props = defineProps({ element: { type: Object, required: true } });
const emit = defineEmits(['update']);

const local = reactive({
  columns: props.element.columns || 2,
  rows: props.element.rows || 1,
  gridTemplateColumns: props.element.gridTemplateColumns || '',
  gridTemplateRows: props.element.gridTemplateRows || '',
  gap: props.element.gap || 12,
  width: props.element.width,
  height: props.element.height,
});

watch(() => props.element, (v) => {
  local.columns = v.columns || 2;
  local.rows = v.rows || 1;
  local.gridTemplateColumns = v.gridTemplateColumns || '';
  local.gridTemplateRows = v.gridTemplateRows || '';
  local.gap = v.gap || 12;
  local.width = v.width;
  local.height = v.height;
});

const emitUpdate = () => {
  emit('update', {
    columns: local.columns,
    rows: local.rows,
    gridTemplateColumns: local.gridTemplateColumns,
    gridTemplateRows: local.gridTemplateRows,
    gap: local.gap,
    width: local.width,
    height: local.height,
  });
};
</script>

<style scoped>
.grid-editor { display: flex; flex-direction: column; gap: 8px; }
.form-row { display: flex; flex-direction: column; }
label { font-size: 12px; color: #606266; margin-bottom: 4px }
input { padding: 6px 8px; border: 1px solid #dcdfe6; border-radius: 4px; font-size: 12px; }
</style>
