<template>
  <div class="formula-management">
    <div class="page-header">
      <div>
        <h1>Formula Management</h1>
        <p class="subtitle">Manage auto-computed parameter formulas</p>
      </div>
      <el-button type="primary" @click="goToBuilder">
        <el-icon><Plus /></el-icon>
        New Formula
      </el-button>
    </div>

    <!-- Filters & Search -->
    <div class="filters-section">
      <el-input
        v-model="searchQuery"
        placeholder="Search by parameter name..."
        style="max-width: 300px"
        clearable
      >
        <template #prefix>
          <el-icon><Search /></el-icon>
        </template>
      </el-input>

      <el-select
        v-model="filterStatus"
        placeholder="Filter by status"
        clearable
        style="max-width: 200px"
      >
        <el-option label="Active" :value="true" />
        <el-option label="Inactive" :value="false" />
      </el-select>

      <el-button @click="fetchFormulas" type="info">
        <el-icon><Refresh /></el-icon>
        Refresh
      </el-button>
    </div>

    <!-- Formulas Table -->
    <el-table
      :data="filteredFormulas"
      style="width: 100%; margin-top: 20px"
      stripe
      :loading="loading"
      v-loading="loading"
      empty-text="No formulas found"
    >
      <el-table-column prop="id" label="ID" width="60" />

      <el-table-column label="Target Parameter" min-width="250">
        <template #default="{ row }">
          <div class="parameter-cell">
            <div class="param-name">{{ row.target_parameter?.test_name }}</div>
            <div class="param-meta">
              <span class="param-code">{{ row.target_parameter?.test_code }}</span>
              <span class="param-unit">{{ row.target_parameter?.unit }}</span>
            </div>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Formula" min-width="300">
        <template #default="{ row }">
          <div class="formula-cell">
            <el-tooltip :content="getReadableFormula(row)" placement="top">
              <code class="formula-preview">{{ truncateFormula(getReadableFormula(row)) }}</code>
            </el-tooltip>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Source Parameters" min-width="200">
        <template #default="{ row }">
          <div class="source-params">
            <el-tag
              v-for="variable in row.source_variables"
              :key="variable.id"
              size="small"
              style="margin: 2px"
            >
              {{ variable.source_parameter?.test_name }}
            </el-tag>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Decimal Places" width="120">
        <template #default="{ row }">
          <span>{{ row.decimal_places }}</span>
        </template>
      </el-table-column>

      <el-table-column label="Status" width="100">
        <template #default="{ row }">
          <el-switch
            v-model="row.is_active"
            @change="toggleFormulaStatus(row)"
            :loading="row.updating"
          />
        </template>
      </el-table-column>

      <el-table-column label="Actions" width="180" fixed="right">
        <template #default="{ row }">
          <el-button
            type="primary"
            size="small"
            @click="editFormula(row)"
            :icon="Edit"
          >
            Edit
          </el-button>
          <el-popconfirm
            title="Are you sure to delete this?"
            confirm-button-text="Yes"
            cancel-button-text="No"
            @confirm="deleteFormula(row.id)"
          >
            <template #reference>
              <el-button
                type="danger"
                size="small"
                :icon="Delete"
              >
                Delete
              </el-button>
            </template>
          </el-popconfirm>
        </template>
      </el-table-column>
    </el-table>

    <!-- Edit Dialog -->
    <el-dialog
      v-model="editDialogVisible"
      title="Edit Formula"
      width="600px"
      @close="resetEditForm"
    >
      <el-form
        :model="editForm"
        :rules="editFormRules"
        ref="editFormRef"
        label-width="150px"
      >
        <el-form-item label="Target Parameter" prop="targetParameterId">
          <el-select
            v-model="editForm.targetParameterId"
            placeholder="Select parameter"
            disabled
          >
            <el-option
              :label="editForm.targetParameterName"
              :value="editForm.targetParameterId"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="Decimal Places" prop="decimalPlaces">
          <el-input-number
            v-model="editForm.decimalPlaces"
            :min="0"
            :max="10"
            controls-position="right"
          />
        </el-form-item>

        <el-form-item label="Active" prop="isActive">
          <el-switch v-model="editForm.isActive" />
        </el-form-item>

        <el-form-item label="Formula JSON" prop="formulaJson">
          <el-input
            v-model="editForm.formulaJson"
            type="textarea"
            :rows="6"
            placeholder="View/Edit formula JSON (advanced)"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <span class="dialog-footer">
          <el-button @click="editDialogVisible = false">Cancel</el-button>
          <el-button type="primary" @click="saveEdit" :loading="isSaving">
            Save Changes
          </el-button>
        </span>
      </template>
    </el-dialog>

    <!-- Statistics Card (Optional) -->
    <div class="stats-section" v-if="formulas.length > 0">
      <el-card>
        <template #header>
          <div class="card-header">
            <span>Formula Statistics</span>
          </div>
        </template>
        <el-row :gutter="20">
          <el-col :xs="12" :sm="6">
            <div class="stat-item">
              <div class="stat-value">{{ formulas.length }}</div>
              <div class="stat-label">Total Formulas</div>
            </div>
          </el-col>
          <el-col :xs="12" :sm="6">
            <div class="stat-item">
              <div class="stat-value">{{ activeFormulasCount }}</div>
              <div class="stat-label">Active</div>
            </div>
          </el-col>
          <el-col :xs="12" :sm="6">
            <div class="stat-item">
              <div class="stat-value">{{ inactiveFormulasCount }}</div>
              <div class="stat-label">Inactive</div>
            </div>
          </el-col>
          <el-col :xs="12" :sm="6">
            <div class="stat-item">
              <div class="stat-value">{{ averageDecimalPlaces.toFixed(1) }}</div>
              <div class="stat-label">Avg Decimals</div>
            </div>
          </el-col>
        </el-row>
      </el-card>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Search, Plus, Refresh, Edit, Delete } from '@element-plus/icons-vue';
import { useRouter } from 'vue-router';
import api from '@/utils/axios';

const router = useRouter();

// State
const formulas = ref([]);
const loading = ref(false);
const isSaving = ref(false);
const searchQuery = ref('');
const filterStatus = ref(null);

// Edit Dialog
const editDialogVisible = ref(false);
const editFormRef = ref(null);
const editForm = ref({
  id: null,
  targetParameterId: null,
  targetParameterName: '',
  decimalPlaces: 2,
  isActive: true,
  formulaJson: '',
});

const editFormRules = {
  decimalPlaces: [
    {
      type: 'number',
      min: 0,
      max: 10,
      message: 'Decimal places must be between 0 and 10',
      trigger: 'change',
    },
  ],
};

/**
 * Fetch all formulas
 */
const fetchFormulas = async () => {
  loading.value = true;
  try {
    const response = await api.get('/formulas');
    formulas.value = response.data.data || [];
  } catch (error) {
    ElMessage.error('Failed to load formulas');
    console.error(error);
  } finally {
    loading.value = false;
  }
};

/**
 * Filtered formulas based on search and status
 */
const filteredFormulas = computed(() => {
  return formulas.value.filter((formula) => {
    const matchesSearch =
      !searchQuery.value ||
      formula.target_parameter?.test_name
        ?.toLowerCase()
        .includes(searchQuery.value.toLowerCase());

    const matchesStatus =
      filterStatus.value === null || formula.is_active === filterStatus.value;

    return matchesSearch && matchesStatus;
  });
});

/**
 * Statistics
 */
const activeFormulasCount = computed(() => {
  return formulas.value.filter((f) => f.is_active).length;
});

const inactiveFormulasCount = computed(() => {
  return formulas.value.filter((f) => !f.is_active).length;
});

const averageDecimalPlaces = computed(() => {
  if (formulas.value.length === 0) return 0;
  const total = formulas.value.reduce((sum, f) => sum + f.decimal_places, 0);
  return total / formulas.value.length;
});

/**
 * Truncate formula display
 */
const truncateFormula = (formula) => {
  return formula.length > 60 ? formula.substring(0, 60) + '...' : formula;
};

/**
 * Convert compiled formula to readable format with parameter names
 * Example: "[24] + [22]" becomes "[Alkaline Phosphatase] + [Albumin]"
 */
const getReadableFormula = (row) => {
  let readable = row.compiled_formula;
  
  // Replace parameter IDs with names
  if (row.source_variables && row.source_variables.length > 0) {
    row.source_variables.forEach((variable) => {
      const paramId = variable.source_test_parameter_id;
      const paramName = variable.source_parameter?.test_name || `Unknown`;
      // Replace [ID] with [Name]
      readable = readable.replace(`[${paramId}]`, `[${paramName}]`);
    });
  }
  
  return readable;
};

/**
 * Navigate to formula builder
 */
const goToBuilder = () => {
  router.push({ name: 'FormulaBuilder' });
};

/**
 * Edit formula
 */
const editFormula = (formula) => {
  editForm.value = {
    id: formula.id,
    targetParameterId: formula.target_test_parameter_id,
    targetParameterName: formula.target_parameter?.test_name,
    decimalPlaces: formula.decimal_places,
    isActive: formula.is_active,
    formulaJson: JSON.stringify(formula.formula_json, null, 2),
  };
  editDialogVisible.value = true;
};

/**
 * Save formula changes
 */
const saveEdit = async () => {
  if (!editFormRef.value) return;

  await editFormRef.value.validate(async (valid) => {
    if (!valid) return;

    isSaving.value = true;
    try {
      const payload = {
        decimal_places: editForm.value.decimalPlaces,
        is_active: editForm.value.isActive,
      };

      // Parse and validate JSON if modified
      if (editForm.value.formulaJson) {
        try {
          payload.formula_json = JSON.parse(editForm.value.formulaJson);
        } catch (e) {
          ElMessage.error('Invalid JSON format');
          return;
        }
      }

      const response = await api.put(`/formulas/${editForm.value.id}`, payload);
      ElMessage.success('Formula updated successfully');
      editDialogVisible.value = false;
      fetchFormulas();
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to update formula';
      ElMessage.error(message);
      console.error(error);
    } finally {
      isSaving.value = false;
    }
  });
};

/**
 * Reset edit form
 */
const resetEditForm = () => {
  editForm.value = {
    id: null,
    targetParameterId: null,
    targetParameterName: '',
    decimalPlaces: 2,
    isActive: true,
    formulaJson: '',
  };
};

/**
 * Toggle formula status
 */
const toggleFormulaStatus = async (formula) => {
  const previousStatus = !formula.is_active;
  try {
    formula.updating = true;
    await api.put(`/formulas/${formula.id}`, {
      is_active: formula.is_active,
    });
    ElMessage.success('Formula status updated');
  } catch (error) {
    formula.is_active = previousStatus; // Revert on error
    ElMessage.error('Failed to update formula status');
    console.error(error);
  } finally {
    formula.updating = false;
  }
};

/**
 * Delete formula
 */
const deleteFormula = async (id) => {
  try {
    await api.delete(`/formulas/${id}`);
    ElMessage.success('Formula deleted successfully');
    fetchFormulas();
  } catch (error) {
    if (error.response?.status === 409) {
      ElMessage.error(error.response.data.message);
    } else {
      ElMessage.error('Failed to delete formula');
    }
    console.error(error);
  }
};

// Lifecycle
onMounted(() => {
  fetchFormulas();
});
</script>

<style scoped>
.formula-management {
  padding: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: var(--spacing-2xl);
}

.page-header h1 {
  margin: 0 0 var(--spacing-sm) 0;
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
}

.page-header .subtitle {
  margin: 0;
  color: var(--color-text-tertiary);
  font-size: var(--font-size-base);
}

.page-header :deep(.el-button) {
  margin-top: var(--spacing-sm);
}

.filters-section {
  display: flex;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-xl);
  align-items: center;
  flex-wrap: wrap;
}

.filters-section :deep(.el-input),
.filters-section :deep(.el-select) {
  min-width: 200px;
}

/* Table Styles */
.parameter-cell {
  display: flex;
  flex-direction: column;
}

.param-name {
  font-weight: var(--font-weight-medium);
  color: var(--color-text-primary);
  margin-bottom: var(--spacing-xs);
}

.param-meta {
  display: flex;
  gap: var(--spacing-md);
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
}

.param-code {
  background-color: var(--color-bg-tertiary);
  padding: 2px 6px;
  border-radius: var(--border-radius-sm);
  font-family: monospace;
}

.param-unit {
  color: var(--color-text-secondary);
}

.formula-cell {
  display: flex;
}

.formula-preview {
  background-color: var(--color-bg-secondary);
  padding: 6px 10px;
  border-radius: var(--border-radius-md);
  font-size: var(--font-size-xs);
  color: var(--color-text-primary);
  border-left: 3px solid var(--color-primary);
  max-width: 250px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.source-params {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-xs);
}

/* Statistics Section */
.stats-section {
  margin-top: var(--spacing-3xl);
}

.card-header {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
}

.stat-item {
  text-align: center;
}

.stat-value {
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-primary);
  margin-bottom: var(--spacing-sm);
}

.stat-label {
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* Dialog Styles */
.el-dialog :deep(.el-form-item) {
  margin-bottom: 18px;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

/* Responsive */
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 16px;
  }

  .filters-section {
    flex-direction: column;
  }

  .filters-section :deep(.el-input),
  .filters-section :deep(.el-select) {
    width: 100%;
    min-width: unset;
  }
}
</style>
