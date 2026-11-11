<template>
  <div :id="element.id" class="report-header-block" :style="computedStyle">
    <!-- Header specific content will go here -->
    <div v-for="child in element.children" :key="child.id" class="child-element">
      <component
        :is="getComponentType(child.type)"
        :element="child"
        :data="data"
        :schemaMetadata="schemaMetadata"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { getFlexLayoutStyle } from '@/utils/layoutDefinitions';
import ReportContainer from './ReportContainer.vue';
import ReportText from './ReportText.vue';
import ReportDynamicText from './ReportDynamicText.vue';
import ReportImage from './ReportImage.vue';

const props = defineProps({
  element: {
    type: Object,
    required: true,
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
});

const computedStyle = computed(() => {
  const style = {};
  const el = props.element;

  if (el.layoutType === 'flex-row' || el.layoutType === 'flex-col') {
    Object.assign(style, getFlexLayoutStyle(el.layoutConfig || {}));
  }

  if (el.style) {
    Object.assign(style, el.style);
  }
  
  if (el.width) style.width = typeof el.width === 'number' ? `${el.width}${props.coordinateUnit}` : el.width;
  if (el.height) style.height = typeof el.height === 'number' ? `${el.height}${props.coordinateUnit}` : el.height;
  
  return style;
});

const getComponentType = (type) => {
  const componentMap = {
    'container': ReportContainer,
    'text': ReportText,
    'text-dynamic': ReportDynamicText,
    'image': ReportImage,
  };
  
  return componentMap[type] || ReportContainer;
};
</script>

<style scoped>
.report-header-block {
  position: relative;
  display: flex;
  flex-direction: column;
  width: 100%;
  padding: 5mm; /* Example padding for a header */
  border-bottom: 1px solid #eee; /* Example border for a header */
}

.child-element {
  width: 100%;
}

@media print {
  .report-header-block {
    margin: 0 !important;
    padding: 0 !important;
    page-break-inside: avoid;
    page-break-after: auto;
    position: relative;
  }
  
  .child-element {
    page-break-inside: avoid;
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
  }
}
</style>