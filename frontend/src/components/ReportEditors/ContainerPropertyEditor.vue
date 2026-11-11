<template>
  <div class="container-editor">
    <div class="form-row">
      <label>Background Color</label>
      <input type="text" v-model="local.style.backgroundColor" @input="emitUpdate" placeholder="#ffffff or rgba(0,0,0,0.1)" />
    </div>

    <div class="form-row">
      <label>Padding</label>
      <input type="text" v-model="local.style.padding" @input="emitUpdate" placeholder="e.g. 12px or 10px 20px" />
    </div>

    <div class="form-row">
      <label>Width</label>
      <input type="text" v-model="local.width" @input="emitUpdate" placeholder="e.g. 100% or 400px" />
    </div>

    <div class="form-row">
      <label>Height</label>
      <input type="text" v-model="local.height" @input="emitUpdate" placeholder="e.g. auto or 200px" />
    </div>

    <div class="form-row-checkbox">
      <label>Fixed to Bottom</label>
      <input type="checkbox" v-model="local.bottomFixed" @change="emitUpdate" />
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
const props = defineProps({ element: { type: Object, required: true } });
const emit = defineEmits(['update']);

const local = reactive({
  style: Object.assign({}, props.element.style || {}),
  width: props.element.width,
  height: props.element.height,
  bottomFixed: props.element.bottomFixed || false,
});

watch(() => props.element, (v) => {
  local.style = Object.assign({}, v.style || {});
  local.width = v.width;
  local.height = v.height;
  local.bottomFixed = v.bottomFixed || false;
});

const emitUpdate = () => {
  emit('update', {
    style: local.style,
    width: local.width,
    height: local.height,
    bottomFixed: local.bottomFixed,
  });
};
</script>

<style scoped>
.container-editor { display: flex; flex-direction: column; gap: 8px; }
.form-row { display: flex; flex-direction: column; }
.form-row-checkbox { display: flex; align-items: center; gap: 8px; }
label { font-size: 12px; color: #606266; margin-bottom: 4px }
input { padding: 6px 8px; border: 1px solid #dcdfe6; border-radius: 4px; font-size: 12px; }
</style>
