<template>
  <div class="image-editor">
    <div class="form-row">
      <label>Image URL</label>
      <input type="text" v-model="local.src" @input="emitUpdate" placeholder="e.g. /images/logo.png" />
    </div>

    <div class="form-row">
      <label>Alt Text</label>
      <input type="text" v-model="local.alt" @input="emitUpdate" placeholder="Image description" />
    </div>

    <div class="form-row">
      <label>Width</label>
      <input type="text" v-model="local.width" @input="emitUpdate" placeholder="e.g. 150 or 50%" />
    </div>

    <div class="form-row">
      <label>Height</label>
      <input type="text" v-model="local.height" @input="emitUpdate" placeholder="e.g. auto or 100" />
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

    <el-divider>Positioning</el-divider>

    <div class="form-row">
      <label>Position</label>
      <el-select v-model="local.style.position" placeholder="Select" @change="emitUpdate">
        <el-option label="Static" value="static" />
        <el-option label="Relative" value="relative" />
        <el-option label="Absolute" value="absolute" />
      </el-select>
    </div>

    <div class="form-row">
      <label>Left</label>
      <input type="text" v-model="local.style.left" @input="emitUpdate" placeholder="e.g., 10mm or 10%" />
    </div>
    <div class="form-row">
      <label>Top</label>
      <input type="text" v-model="local.style.top" @input="emitUpdate" placeholder="e.g., 10mm or 10%" />
    </div>
    <div class="form-row">
      <label>Right</label>
      <input type="text" v-model="local.style.right" @input="emitUpdate" placeholder="e.g., 10mm or 10%" />
    </div>
    <div class="form-row">
      <label>Bottom</label>
      <input type="text" v-model="local.style.bottom" @input="emitUpdate" placeholder="e.g., 10mm or 10%" />
    </div>

    <div class="form-row">
      <label>Object Fit</label>
      <el-select v-model="local.style.objectFit" placeholder="Select" @change="emitUpdate">
        <el-option label="Fill" value="fill" />
        <el-option label="Contain" value="contain" />
        <el-option label="Cover" value="cover" />
        <el-option label="None" value="none" />
        <el-option label="Scale-down" value="scale-down" />
      </el-select>
    </div>

    <div class="form-row">
      <label>Object Position</label>
      <input type="text" v-model="local.style.objectPosition" @input="emitUpdate" placeholder="e.g., center or 50% 50%" />
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { ElColorPicker, ElSelect, ElOption, ElDivider } from 'element-plus';
const props = defineProps({ element: { type: Object, required: true } });
const emit = defineEmits(['update']);

const local = reactive({
  src: props.element.src || '',
  alt: props.element.alt || '',
  width: props.element.width,
  height: props.element.height,
  style: Object.assign({}, props.element.style || {}, {
    padding: props.element.style?.padding || '0mm',
    margin: props.element.style?.margin || '0mm',
    color: props.element.style?.color || '#000000',
    textAlign: props.element.style?.textAlign || 'left',
    fontStyle: props.element.style?.fontStyle || 'normal',
    position: props.element.style?.position || 'static',
    left: props.element.style?.left || 'auto',
    top: props.element.style?.top || 'auto',
    right: props.element.style?.right || 'auto',
    bottom: props.element.style?.bottom || 'auto',
    objectFit: props.element.style?.objectFit || 'fill',
    objectPosition: props.element.style?.objectPosition || '50% 50%',
  }),
});

watch(() => props.element, (v) => {
  local.src = v.src || '';
  local.alt = v.alt || '';
  local.width = v.width;
  local.height = v.height;
  local.style = Object.assign({}, v.style || {}, {
    padding: v.style?.padding || '0mm',
    margin: v.style?.margin || '0mm',
    color: v.style?.color || '#000000',
    textAlign: v.style?.textAlign || 'left',
    fontStyle: v.style?.fontStyle || 'normal',
    position: v.style?.position || 'static',
    left: v.style?.left || 'auto',
    top: v.style?.top || 'auto',
    right: v.style?.right || 'auto',
    bottom: v.style?.bottom || 'auto',
    objectFit: v.style?.objectFit || 'fill',
    objectPosition: v.style?.objectPosition || '50% 50%',
  });
});

const emitUpdate = () => {
  emit('update', {
    src: local.src,
    alt: local.alt,
    width: local.width,
    height: local.height,
    style: local.style,
  });
};
</script>

<style scoped>
.image-editor { display: flex; flex-direction: column; gap: 8px; }
.form-row { display: flex; flex-direction: column; }
label { font-size: 12px; color: #606266; margin-bottom: 4px }
input { padding: 6px 8px; border: 1px solid #dcdfe6; border-radius: 4px; font-size: 12px; }
</style>
