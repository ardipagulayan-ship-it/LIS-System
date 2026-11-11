<template>
  <div class="quality-control">
    <div class="page-header">
      <h2 class="page-title">Quality Control</h2>
      <div class="header-actions">
        <el-button type="primary" @click="openDialog()">
          <el-icon><Plus /></el-icon>
          Add Control
        </el-button>
      </div>
    </div>

    <el-card>
      <el-table
        :data="controls"
        v-loading="loading"
        style="width: 100%"
      >
        <el-table-column label="Equipment">
          <template #default="{ row }">
            {{ row.equipment ? `${row.equipment.manufacturer} - ${row.equipment.model}` : '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="equipment.serial_number" label="Serial Number" />
        <el-table-column prop="control_name" label="Control Name" />
        <el-table-column prop="lot_number" label="Lot Number" />
        <el-table-column prop="expiry_date" label="Expiry Date">
          <template #default="{ row }">
            {{ formatDate(row.expiry_date) }}
          </template>
        </el-table-column>
        <el-table-column prop="mean" label="Mean" />
        <el-table-column prop="sd" label="SD" />
        <el-table-column prop="target" label="Target" />
        <el-table-column prop="is_active" label="Status">
          <template #default="{ row }">
            <el-tag :type="row.is_active ? 'success' : 'danger'" size="small">
              {{ row.is_active ? 'Active' : 'Inactive' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="200">
          <template #default="{ row }">
            <el-button-group>
              <el-button
                type="primary"
                :icon="Edit"
                size="small"
                @click="openDialog(row)"
              />
              <el-button
                type="success"
                :icon="DataLine"
                size="small"
                @click="viewResults(row)"
              />
              <el-button
                type="danger"
                :icon="Delete"
                size="small"
                @click="confirmDelete(row)"
              />
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- Add/Edit Dialog -->
    <el-dialog
      v-model="dialog"
      :title="formTitle"
      width="600px"
    >
      <el-form :model="editedItem" label-width="140px">
        <el-form-item label="Equipment">
          <el-select
            v-model="editedItem.equipment_id"
            placeholder="Select Equipment"
            style="width: 100%"
            @change="handleEquipmentChange"
          >
            <el-option
              v-for="eq in equipment"
              :key="eq.id"
              :label="`${eq.manufacturer} - ${eq.model} (${eq.serial_number || ''})`"
              :value="eq.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="Test Parameter">
          <el-select
            v-model="editedItem.test_parameter_id"
            placeholder="Select Test Parameter"
            style="width: 100%"
          >
            <el-option
              v-for="param in testParameters"
              :key="param.id"
              :label="`${param.test_name} (${param.unit})`"
              :value="param.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="Control Name">
          <el-input v-model="editedItem.control_name" />
        </el-form-item>

        <el-form-item label="Lot Number">
          <el-input v-model="editedItem.lot_number" />
        </el-form-item>

        <el-form-item label="Expiry Date">
          <el-date-picker
            v-model="editedItem.expiry_date"
            type="date"
            placeholder="Select date"
            style="width: 100%"
          />
        </el-form-item>

        <el-form-item label="Mean">
          <el-input-number
            v-model="editedItem.mean"
            :precision="4"
            :step="0.0001"
            style="width: 100%"
          />
        </el-form-item>

        <el-form-item label="Standard Deviation">
          <el-input-number
            v-model="editedItem.sd"
            :precision="4"
            :step="0.0001"
            style="width: 100%"
          />
        </el-form-item>

        <el-form-item label="Target">
          <el-input-number
            v-model="editedItem.target"
            :precision="4"
            :step="0.0001"
            style="width: 100%"
          />
        </el-form-item>

        <el-form-item label="Status">
          <el-switch
            v-model="editedItem.is_active"
            :active-value="true"
            :inactive-value="false"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <span class="dialog-footer">
          <el-button @click="closeDialog">Cancel</el-button>
          <el-button type="primary" @click="save">Save</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- Delete Confirmation Dialog -->
    <el-dialog
      v-model="deleteDialog"
      title="Delete Control"
      width="400px"
    >
      <p>Are you sure you want to delete this control?</p>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="deleteDialog = false">Cancel</el-button>
          <el-button type="danger" @click="deleteItem">Delete</el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { format } from 'date-fns';
import { Plus, Edit, Delete, DataLine } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import api from '@/utils/axios';

export default {
  name: 'QualityControl',

  setup() {
    const router = useRouter();
    const loading = ref(false);
    const dialog = ref(false);
    const deleteDialog = ref(false);
    const controls = ref([]);
    const equipment = ref([]);
    const testParameters = ref([]);
    const editedIndex = ref(-1);

    const editedItem = ref({
      equipment_id: '',
      control_name: '',
      lot_number: '',
      expiry_date: '',
      mean: 0,
      sd: 0,
      target: 0,
      is_active: true
    });

    const defaultItem = {
      equipment_id: '',
      control_name: '',
      lot_number: '',
      expiry_date: '',
      mean: 0,
      sd: 0,
      target: 0,
      is_active: true
    };

    const formTitle = computed(() => {
      return editedIndex.value === -1 ? 'New Control' : 'Edit Control';
    });

    async function fetchControls() {
      loading.value = true;
      try {
        const response = await api.get('/quality-controls');
        controls.value = response.data.data;
      } catch (error) {
        console.error('Error fetching controls:', error);
        ElMessage.error('Failed to fetch controls');
      } finally {
        loading.value = false;
      }
    }

    async function fetchEquipment() {
      try {
        const response = await api.get('eim/equipment', { params: { per_page: 1000 } });
        equipment.value = Array.isArray(response.data) ? response.data : (response.data.data || []);
      } catch (error) {
        console.error('Error fetching equipment:', error);
        ElMessage.error('Failed to fetch equipment');
      }
    }

    function formatDate(date) {
      if (!date) return '';
      return format(new Date(date), 'yyyy-MM-dd');
    }

    function openDialog(item = null) {
      if (item) {
        editedIndex.value = controls.value.indexOf(item);
        editedItem.value = { ...item };
      } else {
        editedIndex.value = -1;
        editedItem.value = { ...defaultItem };
      }
      dialog.value = true;
    }

    function closeDialog() {
      dialog.value = false;
      setTimeout(() => {
        editedItem.value = { ...defaultItem };
        editedIndex.value = -1;
      }, 300);
    }

    async function save() {
      try {
        if (editedIndex.value > -1) {
          // Update
          await api.put(`/quality-controls/${editedItem.value.id}`, editedItem.value);
          ElMessage.success('Control updated successfully');
        } else {
          // Create
          await api.post('/quality-controls', editedItem.value);
          ElMessage.success('Control created successfully');
        }
        closeDialog();
        fetchControls();
      } catch (error) {
        console.error('Error saving control:', error);
        ElMessage.error('Failed to save control');
      }
    }

    function confirmDelete(item) {
      editedIndex.value = controls.value.indexOf(item);
      editedItem.value = { ...item };
      deleteDialog.value = true;
    }

    async function deleteItem() {
      try {
        await api.delete(`/quality-controls/${editedItem.value.id}`);
        ElMessage.success('Control deleted successfully');
        deleteDialog.value = false;
        fetchControls();
      } catch (error) {
        console.error('Error deleting control:', error);
        ElMessage.error('Failed to delete control');
      }
    }

    function viewResults(control) {
      router.push(`/quality-control/${control.id}/results`);
    }

    onMounted(() => {
      fetchControls();
      fetchEquipment();
    });

    return {
      loading,
      dialog,
      deleteDialog,
      controls,
      equipment,
      editedItem,
      formTitle,
      Plus,
      Edit,
      Delete,
      DataLine,
      formatDate,
      openDialog,
      closeDialog,
      save,
      confirmDelete,
      deleteItem,
      viewResults
    };
  }
};
</script>

<style scoped>
.quality-control {
  width: 100%;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-header h2 {
  margin: 0;
  font-size: 24px;
  font-weight: 500;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style> 