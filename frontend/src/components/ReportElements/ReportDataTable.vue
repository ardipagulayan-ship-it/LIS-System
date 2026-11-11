<template>
  <div
    :id="element.id"
    class="report-datatable"
    :style="containerStyle"
  >
    <!-- COLUMN LAYOUT (Table Format) -->
    <template v-if="element.layoutOrientation !== 'rows'">
      <!-- Group headers and rows if grouping is configured -->
      <template v-if="element.groupBy">
        <div v-for="(group, groupIdx) in groupedData" :key="groupIdx" class="table-group">
          <div v-if="group.name" class="group-header">
            {{ group.name }}
          </div>
          <table class="report-table">
            <thead>
              <tr>
                <th v-for="col in displayColumns" :key="col.label">
                  {{ col.displayName || col.label }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, rowIdx) in group.rows" :key="rowIdx">
                <td v-for="col in displayColumns" :key="col.label" :style="getCellStyle(row, col)">
                  {{ getDisplayValue(row, col) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- Non-grouped table -->
      <template v-else>
        <table class="report-table">
          <thead>
            <tr>
              <th v-for="col in displayColumns" :key="col.label">
                {{ col.displayName || col.label }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, rowIdx) in tableData" :key="rowIdx">
              <td v-for="col in displayColumns" :key="col.label" :style="getCellStyle(row, col)">
                {{ getDisplayValue(row, col) }}
              </td>
            </tr>
          </tbody>
        </table>
      </template>
    </template>

    <!-- ROW LAYOUT (Stacked/Vertical Format) -->
    <template v-else>
      <div class="row-layout">
        <div v-for="(item, idx) in tableData" :key="idx" class="row-item">
          <div v-for="col in displayColumns" :key="col.label" class="row-field">
            <span class="row-label">{{ col.displayName || col.label }}</span>
            <span class="row-value" :style="getCellStyle(item, col)">{{ getDisplayValue(item, col) }}</span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { getFormattedDisplayValue, formatUnit } from '@/utils/reportFormatters';

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
});

/**
 * Get display columns with proper header names from columnHeaders config
 */
const displayColumns = computed(() => {
  if (props.element.columnHeaders && props.element.columnHeaders.length > 0) {
    return props.element.columnHeaders;
  }
  // Fallback to old columns format
  return (props.element.columns || []).map(col => ({
    label: col.field,
    displayName: col.header,
    field: col.field,
  }));
});

/**
 * Get table data from data source, filtered by selected parameters if applicable
 */
const tableData = computed(() => {
  const dataSource = props.element.dataSource;
  if (!dataSource) {
    return [];
  }
  
  // Support nested paths like 'results.samples' or direct top-level keys like 'tests'
  const parts = dataSource.split('.');
  let data = props.data;
  
  for (const part of parts) {
    if (data && typeof data === 'object' && part in data) {
      data = data[part];
    } else {
      return [];
    }
  }
  
  let result = Array.isArray(data) ? data : [];
  
  // Filter by selected parameter IDs if any are selected
  if (props.element.selectedParameterIds && props.element.selectedParameterIds.length > 0) {
    result = result.filter(row => {
      const paramId = row.parameter_id || row.id;
      return props.element.selectedParameterIds.includes(paramId);
    });
  }
  
  return result;
});

/**
 * Group data if groupBy is specified
 */
const groupedData = computed(() => {
  if (!props.element.groupBy) return [];
  
  const groupField = props.element.groupBy;
  const groups = {};
  
  tableData.value.forEach(row => {
    const groupKey = row[groupField] || 'Ungrouped';
    if (!groups[groupKey]) {
      groups[groupKey] = {
        name: groupKey,
        rows: [],
      };
    }
    groups[groupKey].rows.push(row);
  });
  
  return Object.values(groups);
});

/**
 * Get cell value, handling nested fields (e.g., 'test.name')
 * Applies formatting based on column configuration (e.g., literal units)
 */
const getDisplayValue = (row, col) => {
  const field = col.label || col.field;
  if (!field) return '-';
  
  // If column is marked as literal unit, preserve it exactly as stored
  if (col.isLiteralUnit || field === 'unit') {
    const fieldMap = { 'unit': 'unit' };
    const actualField = fieldMap[field] || field;
    const parts = actualField.split('.');
    let value = row;
    
    for (const part of parts) {
      if (value && typeof value === 'object' && part in value) {
        value = value[part];
      } else {
        return '-';
      }
    }
    
    // Return unit as-is, preserving "10^12/L" etc.
    return formatUnit(value === null || value === undefined ? '-' : value, true);
  }
  
  // Map field labels to actual data fields
  const fieldMap = {
    'parameter': 'test_name',
    'result_value': 'result_value',
    'unit': 'unit',
    'ref_range': 'reference_range',
    'flags': 'flags',
  };
  
  const actualField = fieldMap[field] || field;
  const parts = actualField.split('.');
  let value = row;
  
  for (const part of parts) {
    if (value && typeof value === 'object' && part in value) {
      value = value[part];
    } else {
      return '-';
    }
  }
  
  return value ?? '-';
};

/**
 * Apply conditional formatting to cells
 * Example: Red if result > high_range, Blue if result < low_range
 */
const getCellStyle = (row, col) => {
  const style = {};
  
  if (col.conditionalColor) {
    const resultValue = getDisplayValue(row, col);
    const highRange = row.high_range || row.ref_high;
    const lowRange = row.low_range || row.ref_low;
    
    // Try to convert to numbers for comparison
    const numValue = parseFloat(resultValue);
    const numHigh = parseFloat(highRange);
    const numLow = parseFloat(lowRange);
    
    if (!isNaN(numValue)) {
      if (!isNaN(numHigh) && numValue > numHigh) {
        style.color = 'var(--color-danger)';
        style.fontWeight = '600';
      } else if (!isNaN(numLow) && numValue < numLow) {
        style.color = 'var(--color-primary)';
        style.fontWeight = '600';
      }
    }
  }
  
  if (col.style) {
    Object.assign(style, col.style);
  }
  
  return style;
};

const containerStyle = computed(() => {
  const style = {};
  const el = props.element;
  
  if (el.style) {
    Object.assign(style, el.style);
  }
  
  // Apply position properties
  if (el.x !== undefined) style.left = typeof el.x === 'number' ? `${el.x}px` : el.x;
  if (el.y !== undefined) style.top = typeof el.y === 'number' ? `${el.y}px` : el.y;
  
  // Apply dimensions
  if (el.width) style.width = typeof el.width === 'number' ? `${el.width}px` : el.width;
  if (el.height) style.height = typeof el.height === 'number' ? `${el.height}px` : el.height;
  
  // Apply positioning if coordinates exist
  if (el.x !== undefined || el.y !== undefined) {
    style.position = 'absolute';
  }
  
  return style;
});
</script>

<style scoped>
.report-datatable {
  width: 100%;
  margin: 12px 0;
}

.table-group {
  margin-bottom: 16px;
}

.group-header {
  background-color: #f5f5f5;
  font-weight: 600;
  padding: 8px 12px;
  border: 1px solid #dcdcdc;
  margin-bottom: 4px;
  color: #303133;
}

.report-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.report-table thead {
  background-color: #f5f7fa;
}

.report-table th {
  padding: 8px 12px;
  text-align: left;
  font-weight: 600;
  color: #303133;
  border: 1px solid #dcdfe6;
}

.report-table td {
  padding: 8px 12px;
  border: 1px solid #ebeef5;
  color: #606266;
}

.report-table tbody tr:nth-child(even) {
  background-color: #fafafa;
}

.report-table tbody tr:hover {
  background-color: #f0f9ff;
}

/* ROW LAYOUT STYLES */
.row-layout {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.row-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 12px;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  background: #fafafa;
}

.row-field {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 6px 0;
  border-bottom: 1px solid #f0f0f0;
}

.row-field:last-child {
  border-bottom: none;
}

.row-label {
  font-weight: 600;
  color: #606266;
  font-size: 12px;
  min-width: 150px;
}

.row-value {
  color: #303133;
  font-size: 13px;
  text-align: right;
  flex: 1;
  margin-left: 12px;
}

@media print {
  .report-datatable {
    page-break-inside: avoid;
    margin: 0 !important;
    padding: 0 !important;
  }
  
  .table-group {
    page-break-inside: avoid;
    margin-bottom: 12px;
  }
  
  .group-header {
    page-break-after: avoid;
    background-color: #f5f5f5;
    border: 1px solid #000;
  }
  
  .report-table {
    width: 100%;
    border-collapse: collapse;
    page-break-inside: avoid;
  }
  
  .report-table thead {
    display: table-header-group;
  }
  
  .report-table tbody {
    display: table-row-group;
  }
  
  .report-table th {
    background-color: #f5f7fa !important;
    color: #000 !important;
    border: 1px solid #000;
    padding: 8px;
  }
  
  .report-table td {
    border: 1px solid #000;
    color: #000 !important;
    padding: 8px;
  }
  
  .report-table tbody tr {
    page-break-inside: avoid;
  }
  
  .report-table tbody tr:nth-child(even) {
    background-color: transparent !important;
  }
  
  .report-table tbody tr:hover {
    background-color: transparent !important;
  }
  
  .row-layout {
    page-break-inside: avoid;
  }
  
  .row-item {
    page-break-inside: avoid;
    border: 1px solid #000;
    background: white !important;
    margin-bottom: 12px;
  }
  
  .row-field {
    border-bottom: 1px solid #000;
    color: #000 !important;
  }
  
  .row-label {
    color: #000 !important;
  }
  
  .row-value {
    color: #000 !important;
  }
}
</style>
