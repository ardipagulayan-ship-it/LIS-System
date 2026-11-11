<template>
  <div class="text-editor">
    <div class="form-row">
      <label>Content</label>
      <textarea v-model="local.content" @input="emitUpdate" rows="3"></textarea>
    </div>

    <div class="form-row">
      <label>Font Size (px)</label>
      <input type="number" v-model.number="local.style.fontSize" @input="emitUpdate" />
    </div>

    <div class="form-row">
      <label>Color</label>
      <input type="text" v-model="local.style.color" @input="emitUpdate" placeholder="#303133 or rgb(0,0,0)" />
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
      <label>Text Align</label>
      <select v-model="local.style.textAlign" @input="emitUpdate">
        <option value="left">Left</option>
        <option value="center">Center</option>
        <option value="right">Right</option>
      </select>
    </div>

    <div class="form-row">
      <label>Font Style</label>
      <select v-model="local.style.fontStyle" @input="emitUpdate">
        <option value="normal">Normal</option>
        <option value="italic">Italic</option>
      </select>
    </div>

    <div class="form-row">
      <label>Width</label>
      <input type="text" v-model="local.width" @input="emitUpdate" placeholder="e.g. 200 or 50%" />
    </div>

    <div class="form-row">
      <label>Height</label>
      <input type="text" v-model="local.height" @input="emitUpdate" placeholder="e.g. auto or 50px" />
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
import { reactive, toRefs, watch } from 'vue';
const props = defineProps({ element: { type: Object, required: true } });
const emit = defineEmits(['update']);

const local = reactive({
  content: props.element.content || '',
  style: Object.assign({}, props.element.style || {}, {
    fontSize: props.element.style?.fontSize,
    padding: props.element.style?.padding || '0mm',
    margin: props.element.style?.margin || '0mm',
    color: props.element.style?.color || '#000000',
    textAlign: props.element.style?.textAlign || 'left',
    fontStyle: props.element.style?.fontStyle || 'normal',
  }),
  width: props.element.width,
  height: props.element.height,
  gridColumn: props.element.gridColumn || '',
  gridRow: props.element.gridRow || '',
});

watch(() => props.element, (v) => {
  local.content = v.content || '';
  local.style = Object.assign({}, v.style || {}, {
    padding: v.style?.padding || '0mm',
    margin: v.style?.margin || '0mm',
    color: v.style?.color || '#000000',
    textAlign: v.style?.textAlign || 'left',
    fontStyle: v.style?.fontStyle || 'normal',
  });
  local.width = v.width;
  local.height = v.height;
  local.gridColumn = v.gridColumn || '';
  local.gridRow = v.gridRow || '';
});

const emitUpdate = () => {
  emit('update', {
    content: local.content,
    style: local.style,
    width: local.width,
    height: local.height,
    gridColumn: local.gridColumn,
    gridRow: local.gridRow,
  });
};
</script>

<style scoped>
.text-editor { display: flex; flex-direction: column; gap: 8px; }
.form-row { display: flex; flex-direction: column; }
label { font-size: 12px; color: #606266; margin-bottom: 4px }
input, textarea { padding: 6px 8px; border: 1px solid #dcdfe6; border-radius: 4px }
</style>
