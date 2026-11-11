/**
 * Report Formatting Utilities
 * Provides consistent formatting functions for report elements
 * Used by both builder and preview to ensure WYSIWYG consistency
 */

/**
 * Format a date/datetime string according to a format string
 * Supports common patterns: YYYY, MM, DD, HH, mm, ss, Z (timezone)
 * Falls back to ISO string if format is invalid
 * 
 * @param {string|Date} value - The date value to format
 * @param {string} formatString - The format pattern (e.g., 'YYYY-MM-DD HH:mm:ss Z')
 * @returns {string} Formatted date string
 */
export function formatDate(value, formatString = 'YYYY-MM-DD HH:mm:ss Z') {
  if (!value) return '-';
  
  try {
    const date = typeof value === 'string' ? new Date(value) : value;
    
    if (isNaN(date.getTime())) {
      return String(value); // Return as-is if invalid date
    }
    
    // Simple pattern replacement for common date formats
    let result = formatString
      .replace('YYYY', date.getFullYear())
      .replace('MM', String(date.getMonth() + 1).padStart(2, '0'))
      .replace('DD', String(date.getDate()).padStart(2, '0'))
      .replace('HH', String(date.getHours()).padStart(2, '0'))
      .replace('mm', String(date.getMinutes()).padStart(2, '0'))
      .replace('ss', String(date.getSeconds()).padStart(2, '0'));
    
    // Handle timezone offset (Z)
    if (formatString.includes('Z')) {
      const tzOffset = -date.getTimezoneOffset();
      const sign = tzOffset >= 0 ? '+' : '-';
      const hours = String(Math.floor(Math.abs(tzOffset) / 60)).padStart(2, '0');
      const minutes = String(Math.abs(tzOffset) % 60).padStart(2, '0');
      result = result.replace('Z', `${sign}${hours}:${minutes}`);
    }
    
    return result;
  } catch (e) {
    console.warn('Date formatting error:', e);
    return String(value);
  }
}

/**
 * Preserve unit string as a literal, preventing any parsing or simplification
 * Ensures that "10^12/L" remains "10^12/L" and not "12/L"
 * 
 * @param {string} unit - The unit string to preserve
 * @param {boolean} isLiteral - Whether to treat as literal (if false, returns unit as-is anyway)
 * @returns {string} The unit string, unmodified
 */
export function formatUnit(unit, isLiteral = true) {
  if (!unit) return '-';
  
  // Simply return the unit as-is; no parsing or simplification
  // The RDO stores the exact unit string that should be rendered
  return String(unit).trim();
}

/**
 * Format a numeric value with optional decimal places and thousands separator
 * 
 * @param {number|string} value - The value to format
 * @param {number} decimals - Number of decimal places (default 2)
 * @param {boolean} useThousandsSeparator - Whether to add thousands separator (default false)
 * @returns {string} Formatted value
 */
export function formatNumber(value, decimals = 2, useThousandsSeparator = false) {
  if (value === null || value === undefined || value === '') return '-';
  
  try {
    const num = parseFloat(value);
    if (isNaN(num)) return String(value);
    
    let formatted = num.toFixed(decimals);
    
    if (useThousandsSeparator) {
      formatted = formatted.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    return formatted;
  } catch (e) {
    return String(value);
  }
}

/**
 * Get display value from raw data, applying formatting rules from RDO column config
 * 
 * @param {object} row - The data row
 * @param {object} col - The column configuration from RDO
 * @param {object} schemaMetadata - Schema metadata containing format strings
 * @returns {string} The formatted display value
 */
export function getFormattedDisplayValue(row, col, schemaMetadata = {}) {
  const field = col.label || col.field;
  if (!field) return '-';
  
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
  
  if (value === null || value === undefined) return '-';
  
  // Apply formatting based on column type
  if (col.isLiteralUnit || field === 'unit') {
    // Preserve unit as literal string
    return formatUnit(value, true);
  } else if (field === 'result_value') {
    // Format numeric values with appropriate decimals
    return formatNumber(value, 2);
  } else if (col.dateFormat && typeof value === 'string') {
    // Format dates according to RDO format string
    const dateFormat = col.dateFormat || schemaMetadata.dateFormat || 'YYYY-MM-DD HH:mm:ss Z';
    return formatDate(value, dateFormat);
  }
  
  return String(value);
}
