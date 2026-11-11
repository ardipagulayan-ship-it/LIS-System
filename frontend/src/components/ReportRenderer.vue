<template>
  <div class="report-renderer" :style="canvasStyle">
    <!-- Recursively render canvas elements -->
    <div v-for="element in rendererElements" :key="element.id" class="renderer-element">
      <component
        :is="getComponentType(element.type)"
        :element="element"
        :data="data"
        :schemaMetadata="props.schema?.metadata || {}"
        :isPreview="isPreview"
        :isAbsolutePositioned="false"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import ReportContainer from './ReportElements/ReportContainer.vue';
import ReportText from './ReportElements/ReportText.vue';
import ReportImage from './ReportElements/ReportImage.vue';
import ReportGrid from './ReportElements/ReportGrid.vue';
import ReportDataTable from './ReportElements/ReportDataTable.vue';
import ReportDynamicText from './ReportElements/ReportDynamicText.vue';
import ReportSignatureBlock from './ReportElements/ReportSignatureBlock.vue';
import ReportShape from './ReportElements/ReportShape.vue';
import HeaderBlock from './ReportElements/HeaderBlock.vue';
import PatientInfoBlock from './ReportElements/PatientInfoBlock.vue';
import RichTextBlock from './ReportElements/RichTextBlock.vue';

const props = defineProps({
  schema: {
    type: Object,
    required: true,
    // Example: { canvas: { width: '210mm', height: '297mm', elements: [...], defaultFontFamily, defaultFontSize } } 
    // or { elements: [...] }
  },
  data: {
    type: Object,
    default: () => ({}),
    // Example: { patient: { name: 'John Doe' }, tests: [...] }
  },
  isPreview: {
    type: Boolean,
    default: false,
    // Flag to indicate this is preview mode; affects styling application
  },
});

// Handle both schema structures: { canvas: { elements: [...] } } and { elements: [...] }
const rendererElements = computed(() => {
  if (props.schema?.canvas?.elements) {
    return props.schema.canvas.elements;
  } else if (props.schema?.elements) {
    return props.schema.elements;
  }
  return [];
});

// Get canvas style properties if available
const canvasStyle = computed(() => {
  const canvas = props.schema?.canvas;
  if (!canvas) return {};
  
  const style = {};
  if (canvas.width) style.width = typeof canvas.width === 'number' ? `${canvas.width}px` : canvas.width;
  if (canvas.height) style.height = typeof canvas.height === 'number' ? `${canvas.height}px` : canvas.height;
  if (canvas.padding) style.padding = canvas.padding;
  if (canvas.backgroundColor) style.backgroundColor = canvas.backgroundColor;
  style.position = 'relative';
  
  // In preview mode, apply default fonts from RDO to ensure isolation from global styles
  if (props.isPreview && canvas.defaultFontFamily) {
    style.fontFamily = canvas.defaultFontFamily;
  }
  if (props.isPreview && canvas.defaultFontSize) {
    style.fontSize = canvas.defaultFontSize;
  }
  
  return style;
});

const coordinateUnit = computed(() => {
  return props.schema?.canvas?.units || 'mm';
});

const getComponentType = (type) => {
  const componentMap = {
    'container': ReportContainer,
    'text': ReportText,
    'text-dynamic': ReportDynamicText,
    'image': ReportImage,
    'grid': ReportGrid,
    'datatable': ReportDataTable,
    'signature-block': ReportSignatureBlock,
    'shape': ReportShape,
    'header-block': HeaderBlock,
    'patient-info-block': PatientInfoBlock,
    'rich-text-block': RichTextBlock,
  };
  
  return componentMap[type] || ReportContainer;
};
</script>

<style scoped>
.report-renderer {
  width: 100%;
  max-width: 210mm;
  background: white;
margin: 0 auto;
  box-sizing: border-box;
  font-family: Arial, sans-serif;
  position: relative;
}

.renderer-element {
  width: 100%;
}

@media print {
  .report-renderer {
    width: 210mm;
    height: 297mm;
    max-width: 210mm;
    margin: 0;
    padding: 0;
    page-break-after: auto;
  }
  
  .renderer-element {
    page-break-inside: avoid;
  }
}
</style>
