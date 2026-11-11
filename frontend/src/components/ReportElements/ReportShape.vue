<template>
  <div
    :id="element.id"
    class="report-shape"
    :style="computedStyle"
    :class="`shape-${element.shapeType}`"
  >
    <!-- Shape content rendered dynamically -->
    <svg v-if="element.shapeType === 'line'" :viewBox="`0 0 ${element.width || 100} ${element.height || 2}`" :style="{ width: '100%', height: '100%' }">
      <line x1="0" y1="50%" x2="100%" y2="50%" :stroke="element.style?.color || '#333'" :stroke-width="element.style?.strokeWidth || 2" />
    </svg>

    <svg v-else-if="element.shapeType === 'rectangle'" :viewBox="`0 0 ${element.width || 100} ${element.height || 100}`" :style="{ width: '100%', height: '100%' }">
      <rect x="0" y="0" :width="element.width || 100" :height="element.height || 100" :fill="element.style?.backgroundColor || 'transparent'" :stroke="element.style?.color || '#333'" :stroke-width="element.style?.strokeWidth || 1" />
    </svg>

    <svg v-else-if="element.shapeType === 'circle'" :viewBox="`0 0 ${element.width || 100} ${element.height || 100}`" :style="{ width: '100%', height: '100%' }">
      <circle :cx="(element.width || 100) / 2" :cy="(element.height || 100) / 2" :r="Math.min(element.width || 100, element.height || 100) / 2 - 1" :fill="element.style?.backgroundColor || 'transparent'" :stroke="element.style?.color || '#333'" :stroke-width="element.style?.strokeWidth || 1" />
    </svg>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  element: {
    type: Object,
    required: true,
    // { type: 'shape', shapeType: 'line|rectangle|circle', width: 100, height: 100, style: {...} }
  },
  data: {
    type: Object,
    default: () => ({}),
  },
  schemaMetadata: {
    type: Object,
    default: () => ({}),
  },
});

const computedStyle = computed(() => {
  const style = {};
  const el = props.element;
  
  if (el.style) {
    Object.assign(style, el.style);
  }

  // Apply dimensions
  if (el.width) style.width = typeof el.width === 'number' ? `${el.width}px` : el.width;
  if (el.height) style.height = typeof el.height === 'number' ? `${el.height}px` : el.height;
  
  return style;
});
</script>

<style scoped>
.report-shape {
  display: inline-block;
}

.shape-line {
  height: 2px;
  width: 100%;
}

.shape-rectangle,
.shape-circle {
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
