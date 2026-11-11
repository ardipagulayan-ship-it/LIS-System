<template>
  <div
    :id="element.id"
    class="signature-block"
    :style="containerStyle"
  >
    <!-- Signature rendering based on mode -->
    <table class="signature-table" :style="{ gridTemplateColumns: `repeat(${columns}, 1fr)` }">
      <tr>
        <td v-for="(sig, idx) in signatures" :key="idx" class="signature-cell">
          <!-- Signature Image (if available from auth_user or role) -->
          <div v-if="sig.signature_path" class="signature-image-container">
            <img :src="sig.signature_path" :alt="sig.name" class="signature-image" />
          </div>

          <!-- Signature Line -->
          <div class="signature-line" :style="separatorStyle"></div>

          <!-- Signature Details -->
          <div class="signature-details">
            <div class="sig-name" :style="nameStyle">{{ sig.name }}</div>
            <div class="sig-role" :style="roleStyle">{{ sig.role }}</div>
            <div class="sig-date" :style="dateStyle">{{ currentDate }}</div>
          </div>
        </td>
      </tr>
    </table>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { formatDate } from '@/utils/reportFormatters';

const props = defineProps({
  element: {
    type: Object,
    required: true,
    // {
    //   type: 'signature-block',
    //   mode: 'specific_user' | 'current_logged_user' | 'role_pathologist',
    //   layout: '1col' | '2col' | '3col',
    //   signatories: [{ name: 'Dr. Smith', role: 'Pathologist', auth_user_id: 1 }]
    // }
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

const authStore = useAuthStore();

/**
 * Determine number of columns for signature layout
 */
const columns = computed(() => {
  const layout = props.element.layout || '1col';
  const colMap = { '1col': 1, '2col': 2, '3col': 3 };
  return colMap[layout] || 1;
});

/**
 * Build signatures array based on mode
 */
const signatures = computed(() => {
  const sigs = [];
  const mode = props.element.mode || 'specific_user';
  
  if (mode === 'current_logged_user') {
    // Add currently logged-in user
    const user = authStore.currentUser;
    if (user) {
      sigs.push({
        name: `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.name,
        role: user.role || 'Authorized Personnel',
        signature_path: user.signature_path || null,
      });
    }
  } else if (mode === 'role_pathologist') {
    // Add user(s) with pathologist role from data context
    const pathologists = props.data.pathologists || [];
    pathologists.forEach(p => {
      sigs.push({
        name: p.name,
        role: p.role || 'Pathologist',
        signature_path: p.signature_path || null,
      });
    });
  } else {
    // mode === 'specific_user' or default
    const signatories = props.element.signatories || [];
    signatories.forEach(sig => {
      sigs.push({
        name: sig.name,
        role: sig.role,
        signature_path: sig.signature_path || null,
      });
    });
  }
  
  return sigs;
});

/**
 * Current date for signing
 */
const currentDate = computed(() => {
  const today = new Date();
  const fmt = props.schemaMetadata.dateFormat || 'YYYY-MM-DD HH:mm:ss Z';
  return formatDate(today, fmt);
});

const separatorStyle = computed(() => {
  const s = props.element.separatorStyle || {};
  const style = {};
  if (s.thickness) style.borderTop = typeof s.thickness === 'number' ? `${s.thickness}px solid ${s.color || '#303133'}` : `${s.thickness} solid ${s.color || '#303133'}`;
  else style.borderTop = `2px solid ${s.color || '#303133'}`;
  if (s.margin) style.margin = s.margin;
  style.width = s.width || '100%';
  return style;
});

const nameStyle = computed(() => props.element.nameStyle || {});
const roleStyle = computed(() => props.element.roleStyle || {});
const dateStyle = computed(() => props.element.dateStyle || {});

const containerStyle = computed(() => {
  const style = { marginTop: '24px' };
  const el = props.element;
  
  if (el.style) {
    Object.assign(style, el.style);
  }
  
  // Apply position properties
  if (el.x !== undefined) style.left = typeof el.x === 'number' ? `${el.x}${props.coordinateUnit}` : el.x;
  if (el.y !== undefined) style.top = typeof el.y === 'number' ? `${el.y}${props.coordinateUnit}` : el.y;
  
  // Apply dimensions
  if (el.width) style.width = typeof el.width === 'number' ? `${el.width}${props.coordinateUnit}` : el.width;
  if (el.height) style.height = typeof el.height === 'number' ? `${el.height}${props.coordinateUnit}` : el.height;
  
  // Apply positioning if coordinates exist and it's not a grid child
  if ((el.x !== undefined || el.y !== undefined) && !props.isGridChild && props.isAbsolutePositioned) {
    style.position = 'absolute';
  }
  
  return style;
});
</script>

<style scoped>
.signature-block {
  width: 100%;
  page-break-inside: avoid;
  margin: 0;
  padding: 0;
}

.signature-table {
  width: 100%;
  border-collapse: collapse;
  display: grid;
  gap: 16px;
  page-break-inside: avoid;
}

.signature-table tr {
  display: contents;
}

.signature-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px;
  text-align: center;
  page-break-inside: avoid;
}

.signature-image-container {
  width: 100%;
  height: 60px;
  margin-bottom: 8px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.signature-image {
  max-width: 100%;
  max-height: 60px;
  object-fit: contain;
}

.signature-line {
  width: 100%;
  height: 40px;
  border-top: 2px solid #303133;
  margin: 8px 0;
}

.signature-details {
  width: 100%;
  font-size: 12px;
}

.sig-name {
  font-weight: 600;
  color: #303133;
  margin-bottom: 2px;
}

.sig-role {
  color: #606266;
  margin-bottom: 4px;
}

.sig-date {
  color: #909399;
  font-style: italic;
}

@media print {
  .signature-block {
    page-break-inside: avoid;
    margin: 0;
    padding: 0;
  }
  
  .signature-table {
    page-break-inside: avoid;
  }
  
  .signature-cell {
    page-break-inside: avoid;
  }
  
  .sig-name,
  .sig-role,
  .sig-date {
    color: #000 !important;
  }
  
  .signature-line {
    border-top: 2px solid #000;
  }
}
</style>
