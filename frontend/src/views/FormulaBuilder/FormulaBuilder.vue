<template>
  <div class="formula-builder">
    <div class="builder-header">
      <h2>Visual Formula Builder</h2>
      <p class="subtitle">Create calculated parameters using a no-code interface</p>
    </div>

    <div class="builder-content">
      <!-- Left Panel: Parameter Selection -->
      <div class="left-panel">
        <div class="panel-section">
          <h3>Test Category</h3>
          <el-select
            v-model="selectedCategoryId"
            placeholder="Select a category..."
            clearable
            @change="loadParameters"
          >
            <el-option
              v-for="category in categories"
              :key="category.id"
              :label="category.name"
              :value="category.id"
            />
          </el-select>
        </div>

        <div class="panel-section">
          <h3>Available Parameters</h3>
          <div v-if="parameters.length > 0" class="parameters-list">
            <div
              v-for="param in parameters"
              :key="param.id"
              class="parameter-chip"
              draggable="true"
              @dragstart="dragParameter($event, param)"
              @click="addParameterToFormula(param)"
              :title="`${param.test_name} (${param.unit || 'N/A'})`"
            >
              <span class="param-name">{{ param.test_name }}</span>
              <span class="param-code">{{ param.test_code }}</span>
            </div>
          </div>
          <div v-else class="empty-state">
            <p>Select a category to see parameters</p>
          </div>
        </div>
      </div>

      <!-- Right Panel: Formula Canvas -->
      <div class="right-panel">
        <div class="canvas-header">
          <h3>Formula Editor</h3>
          <div class="target-parameter">
            <label>Calculates:</label>
            <el-select
              v-model="selectedTargetParameterId"
              placeholder="Select target parameter..."
              clearable
              filterable
            >
              <el-option
                v-for="param in allParameters"
                :key="param.id"
                :label="`${param.test_name} (${param.unit || 'N/A'})`"
                :value="param.id"
              />
            </el-select>
          </div>
        </div>

        <!-- Formula Tokens Display -->
        <div
          class="formula-canvas"
          @dragover.prevent
          @drop.prevent="dropOnCanvas"
        >
          <div v-if="formulaTokens.length === 0" class="canvas-placeholder">
            <p>Drag parameters here or click to add them to build your formula</p>
            <p class="hint">Example: [Total Cholesterol] - [HDL] - ([Triglycerides] / 5)</p>
          </div>

          <div v-else class="formula-display">
            <div class="tokens-container">
              <div
                v-for="(token, idx) in formulaTokens"
                :key="idx"
                :class="['formula-token', token.type]"
                @click="removeToken(idx)"
                :title="`Click to remove`"
              >
                <span v-if="token.type === 'parameter'">
                  [{{ token.label }} #{{ token.value }}]
                </span>
                <span v-else-if="token.type === 'operator'">{{ token.value }}</span>
                <span v-else-if="token.type === 'paren'">{{ token.value }}</span>
                <span v-else-if="token.type === 'number'">{{ token.value }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Control Buttons -->
        <div class="formula-controls">
          <div class="operator-buttons">
            <h4>Operators</h4>
            <button
              v-for="op in operators"
              :key="op"
              class="op-btn"
              @click="addTokenToFormula(op, 'operator')"
            >
              {{ op }}
            </button>
          </div>

          <div class="paren-buttons">
            <h4>Parentheses</h4>
            <button class="paren-btn" @click="addTokenToFormula('(', 'paren')">
              (
            </button>
            <button class="paren-btn" @click="addTokenToFormula(')', 'paren')">
              )
            </button>
          </div>

          <div class="number-input">
            <h4>Constant</h4>
            <el-input-number
              v-model="constantValue"
              :min="0"
              :step="0.1"
              controls-position="right"
              style="width: 100%"
            />
            <button class="add-constant-btn" @click="addConstantToFormula">
              Add
            </button>
          </div>

          <div class="formula-actions">
            <el-button type="info" @click="clearFormula" :disabled="formulaTokens.length === 0">
              Clear
            </el-button>
            <el-button type="warning" @click="undoLastToken" :disabled="formulaTokens.length === 0">
              Undo
            </el-button>
          </div>
        </div>

        <!-- Configuration & Save -->
        <div class="formula-config">
          <div class="config-item">
            <label>Decimal Places:</label>
            <el-input-number
              v-model="decimalPlaces"
              :min="0"
              :max="10"
              controls-position="right"
            />
          </div>

          <div class="config-item">
            <el-checkbox v-model="isActive">
              Formula is Active
            </el-checkbox>
          </div>

          <div class="actions">
            <el-button @click="resetForm">Reset</el-button>
            <el-button
              type="primary"
              @click="saveFormula"
              :loading="isSaving"
              :disabled="!isFormValid"
            >
              Save Formula
            </el-button>
          </div>
        </div>

        <!-- Preview & JSON Display -->
        <div class="formula-preview" v-if="formulaTokens.length > 0">
          <el-collapse>
            <el-collapse-item title="Formula Preview (Human Readable)" name="readable">
              <div class="readable-formula">
                <code>{{ readableFormula }}</code>
              </div>
            </el-collapse-item>
            <el-collapse-item title="Formula JSON (Advanced)" name="json">
              <div class="json-display">
                <pre>{{ JSON.stringify(formulaJson, null, 2) }}</pre>
              </div>
            </el-collapse-item>
            <el-collapse-item title="Compiled Formula (Read-only)" name="compiled">
              <div class="compiled-formula">
                <code>{{ compiledFormula }}</code>
              </div>
            </el-collapse-item>
          </el-collapse>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { ElMessage } from 'element-plus';
import api from '@/utils/axios';

// State: Categories and Parameters
const categories = ref([]);
const parameters = ref([]);
const allParameters = ref([]);
const selectedCategoryId = ref(null);

// State: Target Parameter
const selectedTargetParameterId = ref(null);

// State: Formula Building
const formulaTokens = ref([]);
const constantValue = ref(null);
const operators = ['+', '-', '*', '/'];

// State: Configuration
const decimalPlaces = ref(2);
const isActive = ref(true);

// State: Loading
const isSaving = ref(false);
const loading = ref(false);

/**
 * Fetch all test categories
 */
const loadCategories = async () => {
  try {
    loading.value = true;
    const response = await api.get('/formulas/categories');
    categories.value = response.data.data || [];
  } catch (error) {
    ElMessage.error('Failed to load categories');
    console.error(error);
  } finally {
    loading.value = false;
  }
};

/**
 * Fetch test parameters for a category
 */
const loadParameters = async () => {
  if (!selectedCategoryId.value) {
    parameters.value = [];
    return;
  }

  try {
    loading.value = true;
    const response = await api.get('/formulas/parameters', {
      params: { category_id: selectedCategoryId.value },
    });
    parameters.value = response.data.data || [];
  } catch (error) {
    ElMessage.error('Failed to load parameters');
    console.error(error);
  } finally {
    loading.value = false;
  }
};

/**
 * Fetch all parameters (for target selection)
 */
const loadAllParameters = async () => {
  try {
    const response = await api.get('/formulas/parameters');
    allParameters.value = response.data.data || [];
  } catch (error) {
    console.error('Failed to load all parameters:', error);
  }
};

/**
 * Add a parameter to the formula
 */
const addParameterToFormula = (param) => {
  formulaTokens.value.push({
    type: 'parameter',
    value: param.id,
    label: param.test_name,
  });
};

/**
 * Handle drag start from parameter list
 */
const dragParameter = (event, param) => {
  event.dataTransfer.effectAllowed = 'copy';
  event.dataTransfer.setData('parameter', JSON.stringify(param));
};

/**
 * Handle drop on canvas
 */
const dropOnCanvas = (event) => {
  const paramData = event.dataTransfer.getData('parameter');
  if (paramData) {
    const param = JSON.parse(paramData);
    addParameterToFormula(param);
  }
};

/**
 * Add a token (operator, paren, number) to the formula
 */
const addTokenToFormula = (value, type) => {
  formulaTokens.value.push({
    type,
    value,
  });
};

/**
 * Add a constant number to the formula
 */
const addConstantToFormula = () => {
  if (constantValue.value !== null && constantValue.value !== '') {
    formulaTokens.value.push({
      type: 'number',
      value: constantValue.value,
    });
    constantValue.value = null; // Reset after adding
  }
};

/**
 * Remove a token at a specific index
 */
const removeToken = (index) => {
  formulaTokens.value.splice(index, 1);
};

/**
 * Clear the entire formula
 */
const clearFormula = () => {
  formulaTokens.value = [];
};

/**
 * Undo the last token
 */
const undoLastToken = () => {
  if (formulaTokens.value.length > 0) {
    formulaTokens.value.pop();
  }
};

/**
 * Reset the entire form
 */
const resetForm = () => {
  selectedTargetParameterId.value = null;
  formulaTokens.value = [];
  decimalPlaces.value = 2;
  isActive.value = true;
  constantValue.value = null;
};

/**
 * Generate the formula JSON structure
 */
const formulaJson = computed(() => {
  return {
    version: '1.0',
    tokens: formulaTokens.value,
    timestamp: new Date().toISOString(),
  };
});

/**
 * Generate the compiled formula string (simplified format)
 * Example: "( [101] - [102] )"
 */
const compiledFormula = computed(() => {
  return formulaTokens.value
    .map((token) => {
      if (token.type === 'parameter') {
        return `[${token.value}]`;
      }
      return token.value;
    })
    .join(' ');
});

/**
 * Generate the human-readable formula with parameter names
 * Example: "( [Alkaline Phosphatase] - [Albumin] )"
 */
const readableFormula = computed(() => {
  return formulaTokens.value
    .map((token) => {
      if (token.type === 'parameter') {
        // Find the parameter name from allParameters
        const param = allParameters.value.find((p) => p.id === token.value);
        const name = param ? param.test_name : `Unknown [${token.value}]`;
        return `[${name}]`;
      }
      return token.value;
    })
    .join(' ');
});

/**
 * Extract source parameter IDs from the formula
 */
const sourceParameterIds = computed(() => {
  return formulaTokens.value
    .filter((token) => token.type === 'parameter')
    .map((token) => token.value);
});

/**
 * Validate the formula before saving
 */
const isFormValid = computed(() => {
  const hasTarget = selectedTargetParameterId.value !== null;
  const hasTokens = formulaTokens.value.length > 0;
  const hasSourceParams = sourceParameterIds.value.length > 0;

  return hasTarget && hasTokens && hasSourceParams;
});

/**
 * Save the formula
 */
const saveFormula = async () => {
  if (!isFormValid.value) {
    ElMessage.warning('Please complete the formula before saving');
    return;
  }

  // Validate that target is not in source parameters
  if (sourceParameterIds.value.includes(selectedTargetParameterId.value)) {
    ElMessage.error('A formula cannot use its own target parameter as a source');
    return;
  }

  isSaving.value = true;
  try {
    const payload = {
      target_test_parameter_id: selectedTargetParameterId.value,
      formula_json: formulaJson.value,
      compiled_formula: compiledFormula.value,
      decimal_places: decimalPlaces.value,
      is_active: isActive.value,
      source_parameter_ids: sourceParameterIds.value,
    };

    const response = await api.post('/formulas', payload);

    ElMessage.success(response.data.message || 'Formula saved successfully');
    resetForm();
  } catch (error) {
    if (error.response?.status === 422) {
      const errors = error.response.data.errors;
      const errorMessages = Object.values(errors)
        .flat()
        .join(', ');
      ElMessage.error(`Validation error: ${errorMessages}`);
    } else {
      ElMessage.error(error.response?.data?.message || 'Failed to save formula');
    }
    console.error(error);
  } finally {
    isSaving.value = false;
  }
};

// Lifecycle
onMounted(() => {
  loadCategories();
  loadAllParameters();
});
</script>

<style scoped>
.formula-builder {
  padding: var(--spacing-lg);
  background-color: var(--color-bg-secondary);
  min-height: 100vh;
}

.builder-header {
  margin-bottom: var(--spacing-xl);
}

.builder-header h2 {
  margin: 0 0 var(--spacing-xs) 0;
  font-size: var(--font-size-3xl);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
}

.builder-header .subtitle {
  margin: 0;
  color: var(--color-text-tertiary);
  font-size: var(--font-size-sm);
}

.builder-content {
  display: grid;
  grid-template-columns: 350px 1fr;
  gap: var(--spacing-lg);
  max-width: 1400px;
  margin: 0 auto;
}

/* ===== LEFT PANEL ===== */
.left-panel {
  background-color: var(--color-bg-primary);
  border-radius: var(--border-radius-md);
  padding: var(--spacing-lg);
  box-shadow: var(--shadow-md);
  height: fit-content;
  position: sticky;
  top: var(--spacing-lg);
}

.panel-section {
  margin-bottom: var(--spacing-lg);
}

.panel-section h3 {
  margin: 0 0 var(--spacing-sm) 0;
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.left-panel :deep(.el-select) {
  width: 100%;
}

.parameters-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.parameter-chip {
  display: flex;
  flex-direction: column;
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--color-primary);
  color: var(--color-text-light);
  border-radius: var(--border-radius-sm);
  cursor: grab;
  transition: all var(--transition-base) ease;
  user-select: none;
}

.parameter-chip:hover {
  transform: translateX(var(--spacing-xs));
  box-shadow: var(--shadow-md);
}

.parameter-chip:active {
  cursor: grabbing;
}

.param-name {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
}

.param-code {
  font-size: var(--font-size-xs);
  opacity: 0.8;
  margin-top: 2px;
}

.empty-state {
  text-align: center;
  padding: var(--spacing-xl) var(--spacing-md);
  color: var(--color-text-tertiary);
  font-size: var(--font-size-sm);
}

/* ===== RIGHT PANEL ===== */
.right-panel {
  background-color: var(--color-bg-primary);
  border-radius: var(--border-radius-md);
  padding: var(--spacing-lg);
  box-shadow: var(--shadow-md);
}

.canvas-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-md);
  border-bottom: 2px solid var(--color-border-secondary);
}

.canvas-header h3 {
  margin: 0;
  font-size: var(--font-size-md);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
}

.target-parameter {
  display: flex;
  gap: var(--spacing-md);
  align-items: center;
  flex: 1;
  max-width: 400px;
}

.target-parameter label {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  white-space: nowrap;
}

.target-parameter :deep(.el-select) {
  flex: 1;
}

/* ===== FORMULA CANVAS ===== */
.formula-canvas {
  border: 2px dashed var(--color-border-primary);
  border-radius: var(--border-radius-md);
  padding: var(--spacing-lg);
  background-color: var(--color-bg-tertiary);
  min-height: 120px;
  margin-bottom: var(--spacing-lg);
  transition: all var(--transition-base) ease;
}

.formula-canvas:hover {
  border-color: var(--color-primary);
  background-color: var(--color-primary-light);
}

.canvas-placeholder {
  text-align: center;
  color: var(--color-text-tertiary);
  padding: var(--spacing-lg);
}

.canvas-placeholder p {
  margin: 0;
  font-size: var(--font-size-sm);
}

.canvas-placeholder .hint {
  margin-top: var(--spacing-xs);
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
  font-style: italic;
}

.formula-display {
  display: flex;
  align-items: flex-start;
}

.tokens-container {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-xs);
}

.formula-token {
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--border-radius-sm);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  cursor: pointer;
  transition: all var(--transition-base) ease;
  user-select: none;
}

.formula-token:hover {
  transform: scale(1.05);
  box-shadow: var(--shadow-sm);
}

.formula-token.parameter {
  background-color: var(--color-primary-light);
  color: var(--color-primary-dark);
  border: 1px solid var(--color-primary);
}

.formula-token.parameter:hover {
  background-color: var(--color-primary-light);
  opacity: 0.8;
}

.formula-token.operator {
  background-color: var(--color-danger-light);
  color: var(--color-danger);
  border: 1px solid var(--color-danger-light);
}

.formula-token.operator:hover {
  background-color: var(--color-danger-light);
  opacity: 0.8;
}

.formula-token.paren {
  background-color: var(--color-bg-tertiary);
  color: var(--color-text-secondary);
  border: 1px solid var(--color-border-secondary);
}

.formula-token.number {
  background-color: var(--color-success-light);
  color: var(--color-success-dark);
  border: 1px solid var(--color-success);
}

/* ===== FORMULA CONTROLS ===== */
.formula-controls {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-lg);
  padding: var(--spacing-lg);
  background-color: var(--color-bg-secondary);
  border-radius: var(--border-radius-sm);
}

.formula-controls h4 {
  margin: 0 0 var(--spacing-sm) 0;
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
  text-transform: uppercase;
  color: var(--color-text-secondary);
}

.operator-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-xs);
  grid-column: 1;
}

.op-btn {
  padding: var(--spacing-sm) var(--spacing-md);
  background-color: var(--color-bg-primary);
  border: 1px solid var(--color-border-secondary);
  border-radius: var(--border-radius-sm);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  cursor: pointer;
  transition: all var(--transition-base) ease;
}

.op-btn:hover {
  color: var(--color-danger);
  border-color: var(--color-danger);
  background-color: var(--color-danger-light);
}

.paren-buttons {
  display: flex;
  gap: var(--spacing-xs);
  grid-column: 2;
}

.paren-btn {
  flex: 1;
  padding: var(--spacing-sm);
  background-color: var(--color-bg-primary);
  border: 1px solid var(--color-border-secondary);
  border-radius: var(--border-radius-sm);
  font-size: var(--font-size-md);
  font-weight: var(--font-weight-semibold);
  cursor: pointer;
  transition: all var(--transition-base) ease;
}

.paren-btn:hover {
  border-color: var(--color-text-secondary);
  background-color: var(--color-bg-tertiary);
}

.number-input {
  grid-column: 1;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.number-input :deep(.el-input-number) {
  width: 100%;
}

.add-constant-btn {
  padding: var(--spacing-sm) var(--spacing-md);
  background-color: var(--color-bg-primary);
  border: 1px solid var(--color-border-secondary);
  border-radius: var(--border-radius-sm);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
  cursor: pointer;
  transition: all var(--transition-base) ease;
}

.add-constant-btn:hover {
  background-color: var(--color-success-light);
  border-color: var(--color-success);
  color: var(--color-success-dark);
}

.formula-actions {
  grid-column: 2;
  display: flex;
  gap: var(--spacing-xs);
}

.formula-actions :deep(.el-button) {
  flex: 1;
}

/* ===== FORMULA CONFIG ===== */
.formula-config {
  padding: var(--spacing-lg);
  background-color: var(--color-bg-secondary);
  border-radius: var(--border-radius-sm);
  margin-bottom: var(--spacing-lg);
}

.config-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-sm);
}

.config-item:last-of-type {
  margin-bottom: 0;
}

.config-item label {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  min-width: 120px;
}

.config-item :deep(.el-input-number) {
  flex: 1;
  max-width: 150px;
}

.config-item :deep(.el-checkbox) {
  flex: 1;
}

.actions {
  display: flex;
  gap: var(--spacing-md);
  margin-top: var(--spacing-lg);
}

.actions :deep(.el-button) {
  flex: 1;
}

/* ===== FORMULA PREVIEW ===== */
.formula-preview {
  margin-top: var(--spacing-lg);
}

.formula-preview :deep(.el-collapse-item__header) {
  background-color: var(--color-bg-secondary);
  border-bottom: 1px solid var(--color-border-secondary);
}

.json-display,
.compiled-formula,
.readable-formula {
  background-color: var(--color-bg-secondary);
  padding: var(--spacing-md);
  border-radius: var(--border-radius-sm);
  overflow-x: auto;
}

.json-display pre,
.compiled-formula code,
.readable-formula code {
  margin: 0;
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: var(--font-size-xs);
  line-height: 1.5;
  color: var(--color-text-primary);
}

.readable-formula code {
  background-color: var(--color-bg-primary);
  padding: 8px 12px;
  border-radius: var(--border-radius-sm);
  display: block;
  border-left: 3px solid var(--color-success);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
}

.compiled-formula code {
  background-color: var(--color-bg-primary);
  padding: 8px 12px;
  border-radius: var(--border-radius-sm);
  display: block;
  border-left: 3px solid var(--color-primary);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
  .builder-content {
    grid-template-columns: 1fr;
  }

  .left-panel {
    position: static;
  }

  .formula-controls {
    grid-template-columns: 1fr;
  }

  .formula-controls > * {
    grid-column: 1 !important;
  }

  .canvas-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .target-parameter {
    max-width: 100%;
  }
}
</style>
