<template>
  <div
    :id="element.id"
    class="report-dynamic-text"
    :style="computedStyle"
  >
    <span v-if="element.label" class="label">{{ element.label }}:</span>
    <span class="value" :style="valueStyle">{{ resolvedValue }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatDate } from '@/utils/reportFormatters';

const props = defineProps({
  element: {
    type: Object,
    required: true,
    // { type: 'text-dynamic', field: 'patient.name', label: 'Name', style: {...}, dateFormat: '...' }
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
  },});

/**
 * Resolve nested field from data object
 * Examples: 'patient.name', 'sample.collected_at'
 * Applies date formatting if configured
 */
const resolvedValue = computed(() => {
  const field = props.element.field;
  if (!field) return '';

  const parts = field.split('.');
  let value = props.data;

  for (const part of parts) {
    if (value && typeof value === 'object' && part in value) {
      value = value[part];
    } else {
      return '-';
    }
  }

  if (value === null || value === undefined) return '-';

  const dateFormat = props.element.dateFormat || props.schemaMetadata.dateFormat || 'YYYY-MM-DD HH:mm:ss Z';
  const maybeDate = typeof value === 'string' || value instanceof Date;
  if (props.element.isDateField || props.element.dateFormat || (maybeDate && !isNaN(new Date(value).getTime()))) {
    return formatDate(value, dateFormat);
  }

  return String(value);
});

const computedStyle = computed(() => {
  const style = {};
  const el = props.element;
  
  if (el.style) {
    Object.assign(style, el.style);
    if (el.style.fontFamily) style.fontFamily = el.style.fontFamily;
    if (el.style.fontSize) style.fontSize = el.style.fontSize;
  }
  
  // Apply position properties to ensure parity in Preview
  if (el.x !== undefined) style.left = typeof el.x === 'number' ? `${el.x}${props.coordinateUnit}` : el.x;
  if (el.y !== undefined) style.top = typeof el.y === 'number' ? `${el.y}${props.coordinateUnit}` : el.y;
  
  // Apply dimensions only if not a grid child
  if (!props.isGridChild) {
    if (el.width) style.width = typeof el.width === 'number' ? `${el.width}${props.coordinateUnit}` : el.width;
    if (el.height) style.height = typeof el.height === 'number' ? `${el.height}${props.coordinateUnit}` : el.height;
  }
  
  // Apply positioning if coordinates exist and it's not a grid child
  if ((el.x !== undefined || el.y !== undefined) && !props.isGridChild && props.isAbsolutePositioned) {
    style.position = 'absolute';
  }
  
  return style;
});

const valueStyle = computed(() => {
  return props.element.valueStyle || {};
});
</script>

<style scoped>
.report-dynamic-text {
  display: flex;
  align-items: center;
  gap: 8px;
}

.label {
  font-weight: 600;
  color: #606266;
}

.value {
  color: #303133;
}
</style>
