<template>
  <div
    :id="element.id"
    class="report-container"
    :style="computedStyle"
  >
    <!-- Recursively render children -->
    <div v-for="child in element.children" :key="child.id" class="child-element">
      <component
        :is="getComponentType(child.type)"
        :element="child"
        :data="data"
        :schemaMetadata="schemaMetadata"
        :isPreview="isPreview"
        :isAbsolutePositioned="false"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { getHeaderGridStyle, getFooterGridStyle, getFlexLayoutStyle } from '@/utils/layoutDefinitions';
import ReportContainer from './ReportContainer.vue';
import ReportText from './ReportText.vue';
import ReportImage from './ReportImage.vue';
import ReportGrid from './ReportGrid.vue';
import ReportDataTable from './ReportDataTable.vue';
import ReportDynamicText from './ReportDynamicText.vue';
import ReportSignatureBlock from './ReportSignatureBlock.vue';
import ReportShape from './ReportShape.vue';

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
  isPreview: {
    type: Boolean,
    default: false,
  },
  isAbsolutePositioned: {
    type: Boolean,
    default: true,
  },
  bottomFixed: {
    type: Boolean,
    default: false,
  },
});

const computedStyle = computed(() => {
  const style = {};
  const el = props.element;

  if (el.layoutType === 'header-grid') {
    Object.assign(style, getHeaderGridStyle(el.layoutConfig || {}));
  } else if (el.layoutType === 'footer-grid') {
    Object.assign(style, getFooterGridStyle(el.layoutConfig || {}));
  } else if (el.layoutType === 'flex-row') {
    Object.assign(style, getFlexLayoutStyle(el.layoutConfig || {}));
  }

  if (el.style) {
    Object.assign(style, el.style);
  }

  if (el.bottomFixed) {
    style.position = 'absolute';
    style.bottom = 0;
    style.left = 0;
    style.right = 0;
  } else {
    style.position = 'relative';
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
    'grid': ReportGrid,
    'datatable': ReportDataTable,
    'signature-block': ReportSignatureBlock,
    'shape': ReportShape,
  };
  
  return componentMap[type] || ReportContainer;
};
</script>

<style scoped>
.report-container {
  position: relative;
  display: flex;
  flex-direction: column;
  width: 100%;
}

.child-element {
  width: 100%;
}

@media print {
  .report-container {
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
