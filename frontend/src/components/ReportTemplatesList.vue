<template>
  <div class="report-templates-list">
    <div class="page-header">
      <h2>Report Templates</h2>
      <p class="subtitle">Design and manage laboratory report templates</p>
    </div>

    <div class="toolbar">
      <el-button type="primary" @click="goToNew" icon="Plus">
        New Template
      </el-button>
      <el-button @click="refreshList" icon="Refresh">Refresh</el-button>
      <el-input
        v-model="searchQuery"
        placeholder="Search by name or code..."
        clearable
        style="max-width: 300px"
      />
    </div>

    <el-table
      :data="filteredTemplates"
      stripe
      style="width: 100%; margin-top: 20px"
      v-loading="loading"
      :default-sort="{ prop: 'created_at', order: 'descending' }"
    >
      <el-table-column prop="id" label="ID" width="60" />
      <el-table-column prop="name" label="Name" min-width="200" />
      <el-table-column prop="code" label="Code" width="150" />
      <el-table-column label="Status" width="100">
        <template #default="{ row }">
          <el-tag :type="row.is_active ? 'success' : 'info'">
            {{ row.is_active ? 'Active' : 'Inactive' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="Created" width="180">
        <template #default="{ row }">
          {{ formatDate(row.created_at) }}
        </template>
      </el-table-column>
      <el-table-column label="Actions" width="250" fixed="right">
        <template #default="{ row }">
          <el-button
            type="primary"
            size="small"
            @click="editTemplate(row.id)"
            icon="Edit"
          >
            Edit
          </el-button>
          <el-button
            type="info"
            size="small"
            @click="cloneTemplate(row.id)"
            icon="DocumentCopy"
          >
            Clone
          </el-button>
          <el-popconfirm
            title="Delete this template?"
            confirm-button-text="Yes"
            cancel-button-text="No"
            @confirm="deleteTemplate(row.id)"
          >
            <template #reference>
              <el-button
                type="danger"
                size="small"
                icon="Delete"
              >
                Delete
              </el-button>
            </template>
          </el-popconfirm>
        </template>
      </el-table-column>
    </el-table>

    <!-- Pagination -->
    <el-pagination
      v-if="totalTemplates > pageSize"
      v-model:current-page="currentPage"
      v-model:page-size="pageSize"
      :page-sizes="[10, 20, 50]"
      :total="totalTemplates"
      layout="total, sizes, prev, pager, next, jumper"
      style="margin-top: 20px; text-align: right"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { useRouter } from 'vue-router';
import api from '@/utils/axios';
import { Plus, Refresh, Edit, DocumentCopy, Delete } from '@element-plus/icons-vue';

const router = useRouter();

// State
const templates = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const currentPage = ref(1);
const pageSize = ref(10);
const totalTemplates = ref(0);

// Computed
const filteredTemplates = computed(() => {
  return templates.value.filter(t => {
    if (!searchQuery.value) return true;
    const query = searchQuery.value.toLowerCase();
    return (
      t.name?.toLowerCase().includes(query) ||
      t.code?.toLowerCase().includes(query)
    );
  });
});

// Methods
const loadTemplates = async () => {
  loading.value = true;
  try {
    const response = await api.get('/report-templates', {
      params: {
        page: currentPage.value,
        per_page: pageSize.value,
      },
    });

    templates.value = response.data.data || [];
    totalTemplates.value = response.data.total || 0;
  } catch (error) {
    ElMessage.error('Failed to load templates');
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const goToNew = () => {
  router.push({ name: 'ReportDesignerNew' });
};

const editTemplate = (id) => {
  router.push({
    name: 'ReportDesignerEdit',
    params: { id },
  });
};

const cloneTemplate = async (id) => {
  try {
    const template = templates.value.find(t => t.id === id);
    if (!template) return;

    ElMessageBox.prompt('Enter name for cloned template', 'Clone Template', {
      confirmButtonText: 'Clone',
      cancelButtonText: 'Cancel',
      inputValue: `${template.name} (Copy)`,
      inputPattern: /.{3,}/,
      inputErrorMessage: 'Name must be at least 3 characters',
    }).then(async ({ value }) => {
      const newCode = `${template.code}_${Date.now()}`;
      const response = await api.post(`/report-templates/${id}/clone`, {
        name: value,
        code: newCode,
      });

      ElMessage.success('Template cloned successfully');
      loadTemplates();
    }).catch(() => {
      // User cancelled
    });
  } catch (error) {
    ElMessage.error('Failed to clone template');
    console.error(error);
  }
};

const deleteTemplate = async (id) => {
  try {
    await api.delete(`/report-templates/${id}`);
    ElMessage.success('Template deleted successfully');
    loadTemplates();
  } catch (error) {
    ElMessage.error('Failed to delete template');
    console.error(error);
  }
};

const refreshList = () => {
  currentPage.value = 1;
  loadTemplates();
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

// Lifecycle
onMounted(() => {
  loadTemplates();
});
</script>

<style scoped>
.report-templates-list {
  padding: 24px;
  background: white;
  border-radius: 8px;
}

.page-header {
  margin-bottom: 24px;
}

.page-header h2 {
  margin: 0 0 8px 0;
  font-size: 24px;
  font-weight: 600;
}

.subtitle {
  margin: 0;
  color: #909399;
  font-size: 13px;
}

.toolbar {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.toolbar :deep(.el-button) {
  flex-shrink: 0;
}

:deep(.el-table) {
  background: white;
}
</style>
