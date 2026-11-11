<template>
  <div class="report-group-manager">
    <h3>Report Group Manager</h3>
    <p>Create parameter groups for test categories. Assign parameters to each group using the list below.</p>

    <el-row :gutter="16">
      <!-- Left Panel: Settings & Groups -->
      <el-col :span="8">
        <div class="panel">
          <h4>Settings & Groups</h4>
          
          <div class="form-section">
            <h5>Create New Group</h5>
            <el-form label-width="90px" size="small">
              <el-form-item label="Category" required>
                <el-select v-model="selectedCategory" placeholder="Select Category" @change="loadGroups" class="full-width">
                  <el-option v-for="c in (categories || [])" :key="c?.id" :label="c?.name" :value="c?.id" />
                </el-select>
              </el-form-item>
              <el-form-item label="Group Name" required>
                <el-input v-model="newGroupName" placeholder="e.g., Lipid Panel" />
              </el-form-item>
              <el-form-item label="Layout">
                <el-select v-model="newGroupLayout" placeholder="Select Layout" class="full-width">
                  <el-option v-for="l in (layouts || [])" :key="l?.id" :label="l?.name" :value="l?.id" />
                </el-select>
              </el-form-item>
              <el-button type="primary" @click="createGroup" class="full-width">Create Group</el-button>
            </el-form>
          </div>

          <el-divider />

          <div class="groups-section">
            <h5>Existing Groups</h5>
            <div v-if="!selectedCategory" class="info-hint">
              <el-empty description="Select a category first" />
            </div>
            <div v-else-if="(groups || []).length === 0" class="info-hint">
              <el-empty description="No groups yet" />
            </div>
            <div v-else class="groups-list">
              <div
                v-for="g in (groups || [])"
                :key="g.id"
                class="group-card"
                :class="{ active: selectedGroupId === g.id }"
                @click="selectGroup(g)"
              >
                <div class="group-header">
                  <h6>{{ g.name }}</h6>
                  <el-button size="mini" link type="danger" @click.stop="deleteGroup(g)">Delete</el-button>
                </div>
                <div class="group-meta">
                  <span v-if="g.report_layout_id" class="badge layout-badge">Layout assigned</span>
                  <span class="badge param-count">{{ getGroupParamCount(g.id) }} parameters</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </el-col>

      <!-- Right Panel: Parameter Transfer -->
      <el-col :span="16">
        <div class="panel">
          <h4>Assign Parameters</h4>
          <div v-if="!selectedGroupId" class="empty-hint">
            <p>📌 Select a group from the left to start assigning parameters.</p>
          </div>
          <div v-else>
            <div class="transfer-header">
              <h5>Group: <strong>{{ selectedGroup?.name }}</strong></h5>
              <span v-if="selectedCategory" class="category-label">Category: {{ getCategoryName(selectedCategory) }}</span>
            </div>

            <el-row :gutter="16">
              <el-col :span="12">
                <h6>Available Parameters</h6>
                <div class="draggable-list" id="available-list">
                  <div v-for="item in availableList" :key="item.id" class="draggable-item" :data-id="item.id">
                    {{ item.label || item.test_name || item.name || ('#' + item.id) }}
                  </div>
                </div>
              </el-col>
              <el-col :span="12">
                <h6>Assigned Parameters</h6>
                <div class="draggable-list" id="assigned-list">
                  <div v-for="item in assignedList" :key="item.id" class="draggable-item" :data-id="item.id">
                    {{ item.label || item.test_name || item.name || ('#' + item.id) }}
                  </div>
                </div>
              </el-col>
            </el-row>
          </div>
        </div>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '@/utils/axios';
import { ElMessage, ElMessageBox } from 'element-plus';
import Sortable from 'sortablejs';

const categories = ref([]);
const layouts = ref([]);
const groups = ref([]);
const allParameters = ref([]);
const selectedCategory = ref(null);
const selectedGroupId = ref(null);
const newGroupName = ref('');
const newGroupLayout = ref(null);
const transferKeys = ref([]);
const availableList = ref([]);
const assignedList = ref([]);
let previousAssigned = new Set();

const selectedGroup = computed(() => groups.value.find(g => g.id === selectedGroupId.value));

const transferData = computed(() => {
  return (allParameters.value || [])
    .filter(p => !selectedCategory.value || p.test_category_id === selectedCategory.value)
    .map(p => ({
      key: p.id,
      label: p.name || p.display_name || p.test_name || `#${p.id}`,
      id: p.id,
      test_name: p.test_name,
      name: p.name,
      test_category_id: p.test_category_id
    }));
});

const getCategoryName = (catId) => {
  const cat = categories.value.find(c => c.id === catId);
  return cat?.name || 'Unknown';
};

const getGroupParamCount = (groupId) => {
  return (allParameters.value || []).filter(p => p.report_group_id === groupId).length;
};

const loadInitial = async () => {
  try {
    console.log('Loading categories and layouts...');
    const cats = await api.get('/test-categories');
    console.log('Categories response:', cats);
    categories.value = (cats.data && Array.isArray(cats.data)) ? cats.data : (cats.data?.data || []);
    
    const l = await api.get('/report-layouts');
    console.log('Layouts response:', l);
    layouts.value = (l.data && Array.isArray(l.data)) ? l.data : (l.data?.data || []);
  } catch (e) {
    console.error('Failed to load initial data', e);
    ElMessage.error(`Failed to load categories/layouts: ${e.message}`);
  }
};

onMounted(async () => {
  await loadInitial();
  await loadAllParameters();
});

const loadGroups = async () => {
  if (!selectedCategory.value) {
    groups.value = [];
    selectedGroupId.value = null;
    transferKeys.value = [];
    return;
  }
  try {
    console.log('Loading groups for category:', selectedCategory.value);
    const resp = await api.get('/report-groups', { params: { test_category_id: selectedCategory.value } });
    console.log('Groups response:', resp);
    groups.value = (resp.data && Array.isArray(resp.data)) ? resp.data : (resp.data?.data || []);
    selectedGroupId.value = null;
    transferKeys.value = [];
  } catch (e) {
    console.error('Failed to load groups', e);
    ElMessage.error(`Failed to load groups: ${e.message}`);
  }
};

const selectGroup = (row) => {
  selectedGroupId.value = row.id;
  // Load assigned parameters for this group into transfer keys
  const assigned = (allParameters.value || []).filter(p => p.report_group_id === row.id).map(p => ({
    id: p.id,
    label: p.name || p.display_name || p.test_name || `#${p.id}`
  }));

  const available = (allParameters.value || [])
    .filter(p => p.test_category_id === selectedCategory.value && p.report_group_id !== row.id)
    .map(p => ({ id: p.id, label: p.name || p.display_name || p.test_name || `#${p.id}`, test_name: p.test_name, name: p.name }));

  assignedList.value = assigned;
  availableList.value = available;
  previousAssigned = new Set(assigned.map(a => a.id));
  
  console.log('selectGroup:', {
    groupId: row.id,
    assignedCount: assigned.length,
    availableCount: available.length,
    previousAssigned: Array.from(previousAssigned)
  });

  // Set up Sortable.js for drag-and-drop
  setTimeout(() => {
    setupSortable();
  }, 100);
};

const setupSortable = () => {
  const availableDiv = document.getElementById('available-list');
  const assignedDiv = document.getElementById('assigned-list');

  if (!availableDiv || !assignedDiv) return;

  // Remove existing Sortable instances
  if (availableDiv.sortableInstance) {
    availableDiv.sortableInstance.destroy();
  }
  if (assignedDiv.sortableInstance) {
    assignedDiv.sortableInstance.destroy();
  }

  // Create Sortable for available list
  availableDiv.sortableInstance = new Sortable(availableDiv, {
    group: 'params',
    animation: 150,
    onEnd: async (evt) => {
      if (!selectedGroupId.value) return;

      const movedElement = evt.item;
      const paramId = parseInt(movedElement.dataset.id, 10);
      const toAssignedList = evt.to.id === 'assigned-list';

      console.log('Available list - drop:', { paramId, toAssignedList });

      if (toAssignedList && paramId) {
        try {
          console.log(`[API] Assigning ${paramId}`);
          await api.put(`/report-groups/assign-parameter/${paramId}`, { report_group_id: selectedGroupId.value });
          
          // Manually update arrays immediately (before reload)
          assignedList.value.push({
            id: paramId,
            label: movedElement.textContent.trim()
          });
          availableList.value = availableList.value.filter(p => p.id !== paramId);
          
          ElMessage.success('✓ Parameter assigned');
          previousAssigned = new Set(assignedList.value.map(i => i.id));
        } catch (e) {
          console.error('Failed:', e);
          ElMessage.error(`Error: ${e.message}`);
          // Revert UI
          movedElement.remove();
        }
      }
    }
  });

  // Create Sortable for assigned list
  assignedDiv.sortableInstance = new Sortable(assignedDiv, {
    group: 'params',
    animation: 150,
    onEnd: async (evt) => {
      if (!selectedGroupId.value) return;

      const movedElement = evt.item;
      const paramId = parseInt(movedElement.dataset.id, 10);
      const toAvailableList = evt.to.id === 'available-list';

      console.log('Assigned list - drop:', { paramId, toAvailableList });

      if (toAvailableList && paramId) {
        try {
          console.log(`[API] Unassigning ${paramId}`);
          await api.put(`/report-groups/assign-parameter/${paramId}`, { report_group_id: null });
          
          // Manually update arrays immediately (before reload)
          availableList.value.push({
            id: paramId,
            label: movedElement.textContent.trim()
          });
          assignedList.value = assignedList.value.filter(p => p.id !== paramId);
          
          ElMessage.success('✓ Parameter unassigned');
          previousAssigned = new Set(assignedList.value.map(i => i.id));
        } catch (e) {
          console.error('Failed:', e);
          ElMessage.error(`Error: ${e.message}`);
          // Revert UI
          movedElement.remove();
        }
      }
    }
  });
};

const createGroup = async () => {
  if (!selectedCategory.value || !newGroupName.value) {
    ElMessage.error('Select category and enter a name');
    return;
  }
  try {
    await api.post('/report-groups', {
      name: newGroupName.value,
      test_category_id: selectedCategory.value,
      report_layout_id: newGroupLayout.value || null
    });
    ElMessage.success('Group created successfully');
    newGroupName.value = '';
    newGroupLayout.value = null;
    await loadGroups();
  } catch (e) {
    console.error('Create failed', e);
    ElMessage.error(`Failed to create group: ${e.message}`);
  }
};

const deleteGroup = async (row) => {
  try {
    await ElMessageBox.confirm(`Delete group "${row.name}"? This cannot be undone.`, 'Confirm Delete', { 
      type: 'warning',
      confirmButtonText: 'Delete',
      cancelButtonText: 'Cancel'
    });
    await api.delete(`/report-groups/${row.id}`);
    ElMessage.success('Group deleted');
    if (selectedGroupId.value === row.id) {
      selectedGroupId.value = null;
      transferKeys.value = [];
    }
    await loadGroups();
  } catch (e) {
    if (e.message !== 'cancel') {
      console.error('Delete failed', e);
      ElMessage.error(`Failed to delete group: ${e.message}`);
    }
  }
};

const loadAllParameters = async () => {
  try {
    console.log('Loading test parameters...');
    const resp = await api.get('/test-parameters');
    console.log('Parameters response:', resp);
    allParameters.value = (resp.data && Array.isArray(resp.data)) ? resp.data : (resp.data?.data || []);
    console.log('Loaded parameters:', allParameters.value.length);
  } catch (e) {
    console.error('Failed to load parameters', e);
    ElMessage.error(`Failed to load parameters: ${e.message}`);
  }
};


/**
 * vuedraggable emits a `change` event with `added` and `removed` info.
 * Use that to call the assign/unassign API immediately and reliably.
 */

const handleTransferChange = async (value) => {
  if (!selectedGroupId.value) return;
  
  // Compute which IDs were added and which were removed
  const currentAssigned = (allParameters.value || [])
    .filter(p => p.report_group_id === selectedGroupId.value)
    .map(p => p.id);
  
  const added = value.filter(id => !currentAssigned.includes(id));
  const removed = currentAssigned.filter(id => !value.includes(id));
  
  try {
    // Assign new parameters
    for (const paramId of added) {
      await api.put(`/report-groups/assign-parameter/${paramId}`, { 
        report_group_id: selectedGroupId.value 
      });
    }
    
    // Unassign removed parameters
    for (const paramId of removed) {
      await api.put(`/report-groups/assign-parameter/${paramId}`, { 
        report_group_id: null 
      });
    }
    
    if (added.length > 0 || removed.length > 0) {
      ElMessage.success(`${added.length} assigned, ${removed.length} removed`);
      await loadAllParameters();
      // Re-load transfer keys to reflect changes
      selectGroup(selectedGroup.value);
    }
  } catch (e) {
    console.error('Transfer change failed', e);
    ElMessage.error(`Failed to update assignments: ${e.message}`);
    // Revert transfer keys on error
    transferKeys.value = (allParameters.value || [])
      .filter(p => p.report_group_id === selectedGroupId.value)
      .map(p => p.id);
  }
};
</script>

<style scoped>
.report-group-manager {
  padding: 16px;
}

.panel {
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  padding: 16px;
  background: #fafafa;
  height: 100%;
  color: #303133;
}

.panel h4 {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.panel h5 {
  margin: 0 0 12px 0;
  font-size: 13px;
  font-weight: 600;
  color: #606266;
}

.form-section {
  margin-bottom: 20px;
}

.form-section h5 {
  margin-bottom: 12px;
}

.groups-section {
  margin-top: 16px;
}

.groups-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.group-card {
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  padding: 12px;
  background: white;
  cursor: pointer;
  transition: all 0.2s ease;
}

.group-card:hover {
  border-color: var(--color-primary);
  box-shadow: 0 2px 8px rgba(64, 158, 255, 0.15);
  transform: translateY(-1px);
}

.group-card.active {
  border: 2px solid var(--color-primary);
  background: #f0f9ff;
}

.group-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.group-header h6 {
  margin: 0;
  font-size: 13px;
  font-weight: 600;
  color: #303133;
}

.group-meta {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.badge {
  display: inline-block;
  padding: 3px 8px;
  font-size: 12px;
  border-radius: 3px;
  background: #f0f0f0;
  color: #606266;
}

.layout-badge {
  background: #e6f7ff;
  color: #0369a1;
  border: 1px solid #b5e7ff;
}

.param-count {
  background: #f5e6ff;
  color: #7c3aed;
  border: 1px solid #e9d5ff;
}

.info-hint {
  text-align: center;
  color: #909399;
}

.transfer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #e0e0e0;
}

.transfer-header h5 {
  margin: 0;
}

.category-label {
  font-size: 12px;
  color: #909399;
}

.empty-hint {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 300px;
  color: #909399;
  text-align: center;
  padding: 20px;
  border: 1px dashed #e0e0e0;
  border-radius: 4px;
  background: #fafafa;
}

.empty-hint p {
  margin: 0;
  font-size: 13px;
}

.full-width {
  width: 100%;
}

.draggable-list {
  min-height: 300px;
  border: 1px dashed #dcdfe6;
  border-radius: 4px;
  padding: 8px;
  background: white;
  overflow: auto;
}

.draggable-item {
  padding: 8px 10px;
  border: 1px solid #e6e6e6;
  border-radius: 4px;
  margin-bottom: 6px;
  background: #fafafa;
  cursor: grab;
}

/* Element Plus Transfer component customization */
:deep(.el-transfer) {
  display: flex;
  gap: 10px;
}

:deep(.el-transfer-panel) {
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  background: white;
  flex: 1;
}

:deep(.el-transfer-panel__header) {
  background: #f5f7fa;
  border-bottom: 1px solid #dcdfe6;
  padding: 10px 12px;
  font-weight: 600;
  font-size: 13px;
}

:deep(.el-transfer-panel__body) {
  min-height: 300px;
}

:deep(.el-transfer__buttons) {
  display: flex;
  flex-direction: column;
  gap: 8px;
  justify-content: center;
}

:deep(.el-transfer__button) {
  min-width: 70px;
}
</style>
