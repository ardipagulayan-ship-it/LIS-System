<template>
  <div
    :id="element.id"
    class="report-image"
    :style="containerStyle"
  >
    <img
      :src="element.src"
      :alt="element.alt || 'Image'"
      :style="imageStyle"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  element: {
    type: Object,
    required: true,
    // { type: 'image', src: '...', alt: '...', width: 100, height: 100 }
  },
  data: {
    type: Object,
    default: () => ({}),
  },
  schemaMetadata: {
    type: Object,
    default: () => ({}),
  },
  isAbsolutePositioned: {
    type: Boolean,
    default: true,
  },});

const containerStyle = computed(() => {
  const style = {};
  const el = props.element;
  
  if (el.style) {
    Object.assign(style, el.style);
    if (props.isAbsolutePositioned) {
      if (el.style.position) style.position = el.style.position;
      if (el.style.left) style.left = el.style.left;
      if (el.style.top) style.top = el.style.top;
      if (el.style.right) style.right = el.style.right;
      if (el.style.bottom) style.bottom = el.style.bottom;
    }
  }

  // Dimensions are applied to the image element instead
  return style;
});

const imageStyle = computed(() => {
  const style = {};
  const el = props.element;
  
  if (el.width) style.width = typeof el.width === 'number' ? `${el.width}px` : el.width;
  if (el.height) style.height = typeof el.height === 'number' ? `${el.height}px` : el.height;
  if (el.style?.objectFit) style.objectFit = el.style.objectFit;
  if (el.style?.objectPosition) style.objectPosition = el.style.objectPosition;
  
  return style;
});
</script>

<style scoped>
.report-image {
  display: inline-block;
}

.report-image img {
  max-width: 100%;
  height: auto;
  display: block;
}
</style>
