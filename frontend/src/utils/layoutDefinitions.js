/**
 * Layout Definition Utilities
 * Provides explicit CSS grid/flexbox definitions for report elements
 * Ensures consistent layout between builder and preview
 */

/**
 * Generate explicit grid layout style for 4-column header
 * Ensures First Name / Last Name, Request Date / Status layout doesn't collapse
 * 
 * @param {object} layoutConfig - Optional layout configuration
 * @returns {object} CSS style object with explicit grid definition
 */
export function getHeaderGridStyle(layoutConfig = {}) {
  return {
    display: 'grid',
    gridTemplateColumns: '1fr 1fr 1fr 1fr',
    columnGap: layoutConfig.columnGap || '24px',
    rowGap: layoutConfig.rowGap || '12px',
    width: '100%',
    marginBottom: layoutConfig.marginBottom || '20px',
  };
}

/**
 * Generate explicit grid layout style for 2-column footer (signatories)
 * 
 * @param {object} layoutConfig - Optional layout configuration
 * @returns {object} CSS style object with explicit grid definition
 */
export function getFooterGridStyle(layoutConfig = {}) {
  return {
    display: 'grid',
    gridTemplateColumns: '1fr 1fr',
    columnGap: layoutConfig.columnGap || '40px',
    rowGap: layoutConfig.rowGap || '20px',
    width: '100%',
    marginTop: layoutConfig.marginTop || '40px',
  };
}

/**
 * Generate explicit flex layout style for horizontal layouts
 * 
 * @param {object} layoutConfig - Configuration with justify, align, gap
 * @returns {object} CSS style object with explicit flex definition
 */
export function getFlexLayoutStyle(layoutConfig = {}) {
  return {
    display: 'flex',
    flexDirection: layoutConfig.flexDirection || 'row',
    justifyContent: layoutConfig.justifyContent || 'space-between',
    alignItems: layoutConfig.alignItems || 'flex-start',
    gap: layoutConfig.gap || '20px',
    width: layoutConfig.width || '100%',
  };
}

/**
 * Generate explicit separator line style
 * Ensures consistent line styling (thickness, color, margins)
 * 
 * @param {object} config - Configuration with color, thickness, margin
 * @returns {object} CSS style object for separator line
 */
export function getSeparatorStyle(config = {}) {
  return {
    height: config.thickness || '1px',
    backgroundColor: config.color || '#d9d9d9',
    margin: config.margin || '20px 0',
    width: config.width || '100%',
    border: 'none',
  };
}

/**
 * Generate explicit text style for labels
 * Ensures font consistency for header labels
 * 
 * @param {object} config - Configuration with fontSize, fontWeight, color
 * @returns {object} CSS style object for text
 */
export function getLabelStyle(config = {}) {
  return {
    fontSize: config.fontSize || '12px',
    fontWeight: config.fontWeight || '500',
    color: config.color || '#606266',
    letterSpacing: config.letterSpacing || '0',
  };
}

/**
 * Generate explicit text style for values
 * Ensures consistent rendering of data values
 * 
 * @param {object} config - Configuration with fontSize, fontWeight, color
 * @returns {object} CSS style object for values
 */
export function getValueStyle(config = {}) {
  return {
    fontSize: config.fontSize || '12px',
    fontWeight: config.fontWeight || 'normal',
    color: config.color || '#303133',
    letterSpacing: config.letterSpacing || '0',
  };
}

/**
 * Apply explicit layout style to an element in RDO
 * This ensures the element's style is set from RDO, not global CSS
 * 
 * @param {object} element - The RDO element to update
 * @param {string} layoutType - Type of layout ('header-grid', 'footer-grid', 'flex-row', etc.)
 * @param {object} config - Additional configuration
 */
export function applyExplicitLayout(element, layoutType, config = {}) {
  const layouts = {
    'header-grid': () => getHeaderGridStyle(config),
    'footer-grid': () => getFooterGridStyle(config),
    'flex-row': () => getFlexLayoutStyle(config),
  };
  
  const layoutStyle = layouts[layoutType]?.();
  if (layoutStyle) {
    element.style = element.style || {};
    Object.assign(element.style, layoutStyle);
  }
  
  return element;
}
