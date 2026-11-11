<template>
  <div class="shape-editor">
    <div class="form-row">
      <label>Shape Type</label>
      <select v-model="local.shapeType" @change="emitUpdate">
        <option value="line">Line</option>
        <option value="rectangle">Rectangle</option>
        <option value="circle">Circle</option>
      </select>
    </div>

    <div class="form-row">
      <label>Width</label>
      <input type="number" v-model.number="local.width" @input="emitUpdate" placeholder="100" />
    </div>

    <div class="form-row">
      <label>Height</label>
      <input type="number" v-model.number="local.height" @input="emitUpdate" placeholder="100" />
    </div>

    <div class="form-row">
      <label>Color</label>
      <input type="text" v-model="local.style.color" @input="emitUpdate" placeholder="#333333" />
    </div>

    <div class="form-row">
      <label>Stroke Width</label>
      <input type="number" v-model.number="local.style.strokeWidth" @input="emitUpdate" placeholder="1" />
    </div>

    <div v-if="local.shapeType !== 'line'" class="form-row">
      <label>Fill Color</label>
      <input type="text" v-model="local.style.backgroundColor" @input="emitUpdate" placeholder="transparent" />
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
const props = defineProps({ element: { type: Object, required: true } });
const emit = defineEmits(['update']);

const local = reactive({
  shapeType: props.element.shapeType || 'line',
  width: props.element.width || 100,
  height: props.element.height || (props.element.shapeType === 'line' ? 2 : 100),
  style: Object.assign({}, props.element.style || { color: '#333333', strokeWidth: 1 }),
});

watch(() => props.element, (v) => {
  local.shapeType = v.shapeType || 'line';
  local.width = v.width || 100;
  local.height = v.height || 100;
  local.style = Object.assign({}, v.style || {});
});

const emitUpdate = () => {
  emit('update', {
    shapeType: local.shapeType,
    width: local.width,
    height: local.height,
    style: local.style,
  });
};
</script>

<style scoped>
.shape-editor { display: flex; flex-direction: column; gap: 8px; }
.form-row { display: flex; flex-direction: column; }
label { font-size: 12px; color: #606266; margin-bottom: 4px }
input, select { padding: 6px 8px; border: 1px solid #dcdfe6; border-radius: 4px; font-size: 12px; }
</style>
