<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="props.visible"
      :title="`Assign ${props.type === 'reagent' ? 'Reagent' : 'Control'}`"
      width="700px"
      :close-on-click-modal="false"
      :show-close="false"
      class="inventory-modal"
      v-loading="loading"
    >
      <template #header>
        <div class="modal-header">
          <div class="modal-header-brand">
            <span class="modal-header-icon"></span>
            <span class="modal-header-title">
              ASSIGN {{ props.type === 'reagent' ? 'REAGENT' : 'CONTROL' }}
            </span>
          </div>
          <div class="modal-header-actions">
            <el-button type="danger" @click="handleClose" size="small">
              <el-icon><Close /></el-icon>
              Close
            </el-button>
          </div>
        </div>
      </template>

      <div class="modal-body-content">
        <el-form
          ref="formRef"
          :model="formData"
          :rules="formRules"
          label-width="140px"
          class="inventory-form"
        >
          <div class="form-section">
            <div class="section-header">
              <span class="section-icon">🔬</span>
              <span class="section-title">Assignment Details</span>
            </div>
            
            <div class="section-content">
              <el-form-item label="Equipment">
                <el-input 
                  :value="equipment ? `${equipment.manufacturer} ${equipment.model}` : ''" 
                  disabled 
                />
              </el-form-item>

              <el-form-item label="Material" prop="material_id">
                <el-select 
                  v-model="formData.material_id" 
                  style="width: 100%" 
                  @change="handleMaterialChange" 
                  filterable
                  placeholder="Select material"
                >
                  <template v-for="material in filteredMaterials" :key="material?.id">
                    <el-option
                      v-if="material && material.id && material.generic_name"
                      :label="`${material.generic_name} (${material.manufacturer})`"
                      :value="material.id"
                    />
                  </template>
                </el-select>
              </el-form-item>

              <el-form-item label="Lot" prop="lot_id">
                <el-select 
                  v-model="formData.lot_id" 
                  style="width: 100%" 
                  @change="handleLotChange"
                  placeholder="Select lot"
                  :loading="loadingLots"
                  :disabled="!formData.material_id"
                >
                  <template v-for="lot in availableLots" :key="lot?.id">
                    <el-option
                      v-if="lot && lot.id && lot.lot_number"
                      :label="`${lot.lot_number} (Stock: ${lot.current_stock_level})`"
                      :value="lot.id"
                    />
                  </template>
                </el-select>
              </el-form-item>

              <el-form-item label="Test (Optional)">
                <el-select 
                  v-model="formData.test_id" 
                  style="width: 100%" 
                  clearable 
                  filterable
                  placeholder="Select test"
                >
                  <template v-for="test in availableTests" :key="test?.id">
                    <el-option
                      v-if="test && test.id && test.name"
                      :label="test.name"
                      :value="test.id"
                    />
                  </template>
                </el-select>
              </el-form-item>

              <el-form-item label="Quantity per Test" prop="quantity_per_test">
                <el-input-number
                  v-model="formData.quantity_per_test"
                  :min="0.01"
                  :precision="2"
                  style="width: 100%"
                />
              </el-form-item>

              <el-form-item label="Assigned Date">
                <el-date-picker
                  v-model="formData.assigned_at"
                  type="datetime"
                  style="width: 100%"
                  placeholder="Select date and time"
                />
              </el-form-item>

              <el-form-item v-if="selectedLot" label="Expiration Date">
                <el-input :value="formatDate(selectedLot.expiration_date)" disabled />
              </el-form-item>

              <el-form-item label="Notes">
                <el-input
                  v-model="formData.notes"
                  type="textarea"
                  :rows="3"
                  placeholder="Enter notes"
                />
              </el-form-item>
            </div>
          </div>
        </el-form>
      </div>

      <template #footer>
        <div class="modal-footer">
          <div class="footer-info">
            <!-- Empty for now but kept for layout consistency -->
          </div>
          <div class="footer-actions">
            <el-button @click="handleClose" class="btn-cancel">
              <el-icon><Close /></el-icon>
              Cancel
            </el-button>
            <el-button
              type="primary"
              @click="handleSubmit"
              :loading="saving"
              class="btn-submit"
            >
              <el-icon><Check /></el-icon>
              Save Assignment
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </Teleport>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { ElMessage } from 'element-plus';
import { Close, Check } from '@element-plus/icons-vue';
import api from '@/utils/axios';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  equipment: {
    type: Object,
    default: null
  },
  type: {
    type: String, // 'reagent' or 'control'
    default: 'reagent'
  },
  availableMaterials: {
    type: Array,
    default: () => []
  },
  availableTests: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:visible', 'close', 'save']);

const formRef = ref(null);
const saving = ref(false);
const loading = ref(false);
const loadingLots = ref(false);
const availableLots = ref([]);
const selectedLot = ref(null);

const formData = reactive({
  equipment_id: '',
  material_id: '',
  lot_id: '',
  test_id: null,
  quantity_per_test: 1,
  assigned_at: new Date(),
  notes: ''
});

const formRules = {
  material_id: [
    { required: true, message: 'Please select material', trigger: 'change' }
  ],
  lot_id: [
    { required: true, message: 'Please select lot', trigger: 'change' }
  ],
  quantity_per_test: [
    { required: true, message: 'Please enter quantity per test', trigger: 'blur' }
  ]
};

const filteredMaterials = computed(() => {
  if (props.type === 'reagent') {
    return props.availableMaterials.filter(m => m.material_type === 'reagent');
  } else if (props.type === 'control') {
    return props.availableMaterials.filter(m => m.material_type === 'control');
  }
  return props.availableMaterials;
});

watch(() => props.visible, (newVal) => {
  if (newVal) {
    resetForm();
    if (props.equipment) {
      formData.equipment_id = props.equipment.id;
    }
  }
});

const resetForm = () => {
  Object.assign(formData, {
    equipment_id: props.equipment?.id || '',
    material_id: '',
    lot_id: '',
    test_id: null,
    quantity_per_test: 1,
    assigned_at: new Date(),
    notes: ''
  });
  availableLots.value = [];
  selectedLot.value = null;
  if (formRef.value) {
    formRef.value.clearValidate();
  }
};

const handleMaterialChange = async () => {
  formData.lot_id = '';
  selectedLot.value = null;
  availableLots.value = [];
  
  if (!formData.material_id) return;

  loadingLots.value = true;
  try {
    const response = await api.get('eim/imm/lots', {
      params: { material_id: formData.material_id, status: 'active' }
    });
    
    const lotsData = response.data.data || response.data || [];
    availableLots.value = Array.isArray(lotsData) 
      ? lotsData.filter(l => l && l.id && l.lot_number && l.status === 'active')
      : [];
      
    if (availableLots.value.length === 0) {
      ElMessage.warning('No active lots found for this material. Please create a lot first.');
    }
  } catch (error) {
    console.error('Failed to load lots', error);
    ElMessage.error('Failed to load lots');
  } finally {
    loadingLots.value = false;
  }
};

const handleLotChange = () => {
  if (!formData.lot_id) {
    selectedLot.value = null;
    return;
  }
  selectedLot.value = availableLots.value.find(l => l.id === formData.lot_id);
};

const handleClose = () => {
  emit('update:visible', false);
  emit('close');
};

const handleSubmit = async () => {
  if (!formRef.value) return;
  
  try {
    await formRef.value.validate();
    saving.value = true;
    
    await api.post('eim/equipment-usage/assignments', formData);
    ElMessage.success('Assignment created successfully and stock deducted');
    
    emit('save');
    handleClose();
  } catch (error) {
    console.error('Failed to create assignment', error);
    if (error.response?.data?.message) {
      ElMessage.error(error.response.data.message);
    } else {
      ElMessage.error('Failed to create assignment');
    }
  } finally {
    saving.value = false;
  }
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString();
};
</script>

<style scoped>
/* Reuse the standard modal styles */
.inventory-modal :deep(.el-dialog__header) {
  padding: 0;
  margin: 0;
  display: none;
}

.inventory-modal :deep(.el-dialog__body) {
  padding: 0;
  margin: 0;
  height: calc(100vh - 200px);
  display: flex;
  flex-direction: column;
}

.inventory-modal :deep(.el-dialog__footer) {
  padding: 0;
  margin: 0;
}

.modal-header {
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
  padding: 20px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-header-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.modal-header-icon {
  width: 4px;
  height: 24px;
  background-color: var(--color-secondary);
  border-radius: 2px;
  box-shadow: 0 0 8px rgba(255, 255, 255, 0.3);
}

.modal-header-title {
  font-size: 18px;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.modal-header-actions .el-button {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
  backdrop-filter: blur(4px);
  transition: all 0.3s ease;
}

.modal-header-actions .el-button:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-1px);
}

.modal-body-content {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
  background-color: #f8f9fa;
}

.inventory-form {
  max-width: 100%;
}

.form-section {
  background: white;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.03);
  border: 1px solid var(--color-border-light);
}

.section-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 2px solid var(--color-background-base);
}

.section-icon {
  font-size: 24px;
  filter: grayscale(0.2);
}

.section-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--color-text-primary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.modal-footer {
  padding: 16px 24px;
  background-color: white;
  border-top: 1px solid var(--color-border-light);
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.03);
}

.footer-actions {
  display: flex;
  gap: 12px;
  width: 100%;
  justify-content: flex-end;
}

.btn-cancel {
  border: 1px solid var(--color-border-base);
  color: var(--color-text-regular);
  padding: 10px 20px;
}

.btn-submit {
  padding: 10px 24px;
  font-weight: 600;
  letter-spacing: 0.3px;
  box-shadow: 0 4px 12px rgba(var(--color-primary-rgb), 0.3);
}

@media (max-width: 768px) {
  .inventory-modal :deep(.el-dialog) {
    width: 95% !important;
    margin-top: 2vh !important;
  }
}
</style>
