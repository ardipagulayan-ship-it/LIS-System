<template>
  <div
    :id="element.id"
    class="canvas-element"
          :class="{ selected: selectedElement?.id === element.id }"
    :data-element-id="element.id"
    :style="{
      ...element.style,
      width: element.width ? (typeof element.width === 'number' ? element.width + 'mm' : element.width) : 'auto',
      height: element.height ? (typeof element.height === 'number' ? element.height + 'mm' : element.height) : 'auto'
    }"
    @click="selectElement(element, $event)"
  >
    <template v-if="element.children && element.children.length > 0">
      <DesignerElementWrapper
        v-for="child in element.children"
        :key="child.id"
        :element="child"
        :data="data"
        :schemaMetadata="schemaMetadata"
        :selectedElement="selectedElement"
        :selectElement="selectElement"
        :isGridChild="element.type === 'grid'"
      />
    </template>
    <template v-else>
      <component
        :is="getComponentType(element.type)"
        :element="element"
        :data="data"
        :schemaMetadata="schemaMetadata"
        :isGridChild="isGridChild"
      />
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import ReportContainer from './ReportContainer.vue';
import ReportText from './ReportText.vue';
import ReportImage from './ReportImage.vue';
import ReportGrid from './ReportGrid.vue';
import ReportDataTable from './ReportDataTable.vue';
import ReportDynamicText from './ReportDynamicText.vue';
import ReportSignatureBlock from './ReportSignatureBlock.vue';
import ReportShape from './ReportShape.vue';
import HeaderBlock from './HeaderBlock.vue';
import PatientInfoBlock from './PatientInfoBlock.vue';
import RichTextBlock from './RichTextBlock.vue';

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
  selectedElement: {
    type: Object,
    default: null,
  },
  selectElement: {
    type: Function,
    required: true,
  },
  isGridChild: {
    type: Boolean,
    default: false,
  },
});

console.log('DesignerElementWrapper - element.style:', props.element.style, 'isGridChild:', props.isGridChild);

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


