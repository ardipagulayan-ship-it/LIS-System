<template>
  <div
    :id="element.id"
    class="report-grid"
    :style="gridStyle"
  >
    <template v-if="isPreview">
      <component
        v-for="child in element.children"
        :key="child.id"
        :is="getComponentType(child.type)"
        :element="child"
        :data="data"
        :schemaMetadata="schemaMetadata"
        :coordinateUnit="coordinateUnit"
        :isGridChild="true"
        :style="getItemStyle(child)"
      />
    </template>
    <template v-else>
      <DesignerElementWrapper
        v-for="child in element.children"
        :key="child.id"
        :element="child"
        :data="data"
        :schemaMetadata="schemaMetadata"
        :isGridChild="true"
        :style="getItemStyle(child)"
      />
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { getHeaderGridStyle } from '@/utils/layoutDefinitions';
import ReportContainer from './ReportContainer.vue';
import ReportText from './ReportText.vue';
import ReportImage from './ReportImage.vue';
import ReportGrid from './ReportGrid.vue';
import ReportDataTable from './ReportDataTable.vue';
import ReportDynamicText from './ReportDynamicText.vue';
import ReportSignatureBlock from './ReportSignatureBlock.vue';

const props = defineProps({
  element: {
    type: Object,
    required: true,
    // { type: 'grid', columns: 3, style: {...}, children: [...] }
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
  isPreview: {
    type: Boolean,
    default: false,
  },
});

const gridStyle = computed(() => {
  const style = {
    display: 'grid',
            gap: `${props.element.gap || 16}px`,
  };

  if (props.element.gridTemplateColumns) {
    style.gridTemplateColumns = props.element.gridTemplateColumns;
  } else if (typeof props.element.columns === 'string') {
    style.gridTemplateColumns = props.element.columns;
  } else {
    style.gridTemplateColumns = `repeat(${props.element.columns || 1}, 1fr)`;
  }

  if (props.element.gridTemplateRows) {
    style.gridTemplateRows = props.element.gridTemplateRows;
  } else if (typeof props.element.rows === 'string') {
    style.gridTemplateRows = props.element.rows;
  } else if (props.element.rows) {
    style.gridTemplateRows = `repeat(${props.element.rows}, 1fr)`;
  }

  if (props.element.layoutType === 'header-grid') {
    Object.assign(style, getHeaderGridStyle(props.element.layoutConfig || {}));
  }
  
  if (props.element.style) {
    Object.assign(style, props.element.style);
  }
  
  if (props.element.width) style.width = typeof props.element.width === 'number' ? `${props.element.width}${props.coordinateUnit}` : props.element.width;
  if (props.element.height) style.height = typeof props.element.height === 'number' ? `${props.element.height}${props.coordinateUnit}` : props.element.height;
  
  return style;
});

const getItemStyle = (item) => {
  const style = {};
  if (item.gridColumn) {
    style.gridColumn = item.gridColumn;
  }
  if (item.gridRow) {
    style.gridRow = item.gridRow;
  }
  return style;
};

const getComponentType = (type) => {
  const componentMap = {
    'container': ReportContainer,
    'text': ReportText,
    'text-dynamic': ReportDynamicText,
    'image': ReportImage,
    'grid': ReportGrid,
    'datatable': ReportDataTable,
    'signature-block': ReportSignatureBlock,
  };
  
  return componentMap[type] || ReportContainer;
};
</script>

