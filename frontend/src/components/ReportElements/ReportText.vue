<template>
  <div
    :id="element.id"
    class="report-text"
    :style="computedStyle"
  >
    {{ element.content }}
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  element: {
    type: Object,
    required: true,
    // { type: 'text', content: '...', style: { fontSize: '14px', ... } }
  },
  data: {
    type: Object,
    default: () => ({}),
  },
  schemaMetadata: {
    type: Object,
    default: () => ({}),
  },
  coordinateUnit: {
    type: String,
    default: 'mm',
  },
  isGridChild: {
    type: Boolean,
    default: false,
  },
  isAbsolutePositioned: {
    type: Boolean,
    default: true,
  },
});

const computedStyle = computed(() => {
  const el = props.element;
  console.log('ReportText computedStyle - el.x:', el.x, 'el.y:', el.y, 'isGridChild:', props.isGridChild);
  const style = {};
  
  if (el.style) {
    Object.assign(style, el.style);
    if (el.style.fontFamily) style.fontFamily = el.style.fontFamily;
    if (el.style.fontSize) style.fontSize = el.style.fontSize;
  }
  
  // Apply position properties only if not a grid child
  if (!props.isGridChild) {
    if (el.x !== undefined) style.left = typeof el.x === 'number' ? `${el.x}${props.coordinateUnit}` : el.x;
    if (el.y !== undefined) style.top = typeof el.y === 'number' ? `${el.y}${props.coordinateUnit}` : el.y;
  }
  
  // Apply dimensions only if not a grid child
  // if (!props.isGridChild) {
    if (el.width) style.width = typeof el.width === 'number' ? `${el.width}${props.coordinateUnit}` : el.width;
    if (el.height) style.height = typeof el.height === 'number' ? `${el.height}${props.coordinateUnit}` : el.height;
  // }
  
  // Apply positioning if coordinates exist and it's not a grid child
  if ((el.x !== undefined || el.y !== undefined) && !props.isGridChild && props.isAbsolutePositioned) {
    style.position = 'absolute';
  }

  return style;
});
</script>

<style scoped>
.report-text {
  white-space: pre-wrap;
  word-wrap: break-word;
}

@media print {
  .report-text {
    page-break-inside: avoid;
    margin: 0;
    padding: 0;
  }
}
</style>
