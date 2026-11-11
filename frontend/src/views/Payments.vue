<template>
  <div class="payments">
    <div class="page-header">
      <h2>Payments</h2>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>New Payment
      </el-button>
    </div>

    <el-card>
      <template #header>
        <div class="card-header">
          <el-input
            v-model="searchQuery"
            placeholder="Search payments..."
            class="search-input"
            clearable
            @clear="handleSearch"
            @input="handleSearch"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
        </div>
      </template>

      <el-table
        v-loading="loading"
        :data="payments"
        style="width: 100%"
        border
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="laboratory_request.patient.name" label="Patient" />
        <el-table-column prop="laboratory_request.doctor.name" label="Doctor" />
        <el-table-column prop="amount" label="Amount" width="120">
          <template #default="{ row }">
            {{ formatCurrency(row.amount) }}
          </template>
        </el-table-column>
        <el-table-column prop="payment_method" label="Payment Method" width="150" />
        <el-table-column prop="status" label="Status" width="120">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ row.status }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="payment_date" label="Payment Date" width="150">
          <template #default="{ row }">
            {{ formatDate(row.payment_date) }}
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="200" fixed="right">
          <template #default="{ row }">
            <el-button-group>
              <el-button type="primary" size="small" @click="handleView(row)">
                <el-icon><View /></el-icon>
              </el-button>
              <el-button 
                type="success" 
                size="small" 
                @click="handlePrint(row)"
                :disabled="row.status !== 'Completed'"
              >
                <el-icon><Printer /></el-icon>
              </el-button>
              <el-button 
                type="danger" 
                size="small" 
                @click="handleDelete(row)"
                :disabled="row.status === 'Completed'"
              >
                <el-icon><Delete /></el-icon>
              </el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- Add/Edit Payment Dialog -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogType === 'add' ? 'New Payment' : 'Edit Payment'"
      width="800px"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="140px"
      >
        <el-form-item label="Laboratory Request" prop="laboratory_request_id">
          <el-select 
            v-model="form.laboratory_request_id" 
            placeholder="Select request"
            filterable
            remote
            :remote-method="searchRequests"
            :loading="loadingRequests"
            style="width: 100%"
          >
            <el-option
              v-for="request in requests"
              :key="request.id"
              :label="`${request.patient.name} - ${formatCurrency(request.total_amount)}`"
              :value="request.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Amount" prop="amount">
          <el-input-number
            v-model="form.amount"
            :precision="2"
            :step="0.01"
            :min="0"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="Payment Method" prop="payment_method">
          <el-select v-model="form.payment_method" placeholder="Select payment method">
            <el-option label="Cash" value="Cash" />
            <el-option label="Credit Card" value="Credit Card" />
            <el-option label="Debit Card" value="Debit Card" />
            <el-option label="Bank Transfer" value="Bank Transfer" />
            <el-option label="Insurance" value="Insurance" />
          </el-select>
        </el-form-item>
        <el-form-item label="Payment Date" prop="payment_date">
          <el-date-picker
            v-model="form.payment_date"
            type="datetime"
            placeholder="Select date and time"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="Status" prop="status">
          <el-select v-model="form.status" placeholder="Select status">
            <el-option label="Pending" value="Pending" />
            <el-option label="Completed" value="Completed" />
            <el-option label="Failed" value="Failed" />
            <el-option label="Refunded" value="Refunded" />
          </el-select>
        </el-form-item>
        <el-form-item label="Transaction ID" prop="transaction_id">
          <el-input v-model="form.transaction_id" placeholder="Enter transaction ID" />
        </el-form-item>
        <el-form-item label="Notes" prop="notes">
          <el-input
            v-model="form.notes"
            type="textarea"
            :rows="3"
            placeholder="Enter notes"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="dialogVisible = false">Cancel</el-button>
          <el-button type="primary" @click="handleSubmit" :loading="submitting">
            {{ dialogType === 'add' ? 'Process' : 'Update' }}
          </el-button>
        </span>
      </template>
    </el-dialog>

    <!-- View Payment Dialog -->
    <el-dialog
      v-model="viewDialogVisible"
      title="Payment Details"
      width="800px"
    >
      <div v-if="selectedPayment" class="payment-details">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="Patient">
            {{ selectedPayment.laboratory_request.patient.name }}
          </el-descriptions-item>
          <el-descriptions-item label="Doctor">
            {{ selectedPayment.laboratory_request.doctor.name }}
          </el-descriptions-item>
          <el-descriptions-item label="Amount">
            {{ formatCurrency(selectedPayment.amount) }}
          </el-descriptions-item>
          <el-descriptions-item label="Payment Method">
            {{ selectedPayment.payment_method }}
          </el-descriptions-item>
          <el-descriptions-item label="Status">
            <el-tag :type="getStatusType(selectedPayment.status)">
              {{ selectedPayment.status }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="Payment Date">
            {{ formatDate(selectedPayment.payment_date) }}
          </el-descriptions-item>
          <el-descriptions-item label="Transaction ID">
            {{ selectedPayment.transaction_id || 'N/A' }}
          </el-descriptions-item>
        </el-descriptions>

        <div class="request-details">
          <h3>Request Details</h3>
          <el-table :data="selectedPayment.laboratory_request.tests" border>
            <el-table-column prop="name" label="Test" />
            <el-table-column prop="price" label="Price" width="120">
              <template #default="{ row }">
                {{ formatCurrency(row.price) }}
              </template>
            </el-table-column>
          </el-table>
          <div class="total-amount">
            <h3>Total Amount: {{ formatCurrency(selectedPayment.laboratory_request.total_amount) }}</h3>
          </div>
        </div>

        <div v-if="selectedPayment.notes" class="notes">
          <h3>Notes</h3>
          <p>{{ selectedPayment.notes }}</p>
        </div>
      </div>
    </el-dialog>

    <!-- Delete Confirmation Dialog -->
    <el-dialog
      v-model="deleteDialogVisible"
      title="Delete Payment"
      width="400px"
    >
      <p>Are you sure you want to delete this payment?</p>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="deleteDialogVisible = false">Cancel</el-button>
          <el-button type="danger" @click="confirmDelete" :loading="deleting">
            Delete
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Plus, Edit, Delete, Search, View, Printer } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';
import api from '@/utils/axios';

const loading = ref(false);
const submitting = ref(false);
const deleting = ref(false);
const payments = ref([]);
const requests = ref([]);
const loadingRequests = ref(false);
const total = ref(0);
const currentPage = ref(1);
const pageSize = ref(10);
const searchQuery = ref('');
const dialogVisible = ref(false);
const viewDialogVisible = ref(false);
const deleteDialogVisible = ref(false);
const dialogType = ref('add');
const formRef = ref(null);
const selectedPayment = ref(null);

const form = ref({
  laboratory_request_id: '',
  amount: 0,
  payment_method: '',
  payment_date: '',
  status: 'Pending',
  transaction_id: '',
  notes: ''
});

const rules = {
  laboratory_request_id: [
    { required: true, message: 'Please select laboratory request', trigger: 'change' }
  ],
  amount: [
    { required: true, message: 'Please enter amount', trigger: 'blur' }
  ],
  payment_method: [
    { required: true, message: 'Please select payment method', trigger: 'change' }
  ],
  payment_date: [
    { required: true, message: 'Please select payment date', trigger: 'change' }
  ],
  status: [
    { required: true, message: 'Please select status', trigger: 'change' }
  ]
};

const formatDate = (date) => {
  return new Date(date).toLocaleString();
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(value);
};

const getStatusType = (status) => {
  const types = {
    'Pending': 'warning',
    'Completed': 'success',
    'Failed': 'danger',
    'Refunded': 'info'
  };
  return types[status] || 'info';
};

const fetchPayments = async () => {
  loading.value = true;
  try {
    const response = await api.get('/payments', {
      params: {
        page: currentPage.value,
        per_page: pageSize.value,
        search: searchQuery.value
      }
    });
    payments.value = response.data.data;
    total.value = response.data.total;
  } catch (error) {
    ElMessage.error('Error fetching payments');
    console.error('Error:', error);
  } finally {
    loading.value = false;
  }
};

const searchRequests = async (query) => {
  if (query) {
    loadingRequests.value = true;
    try {
      const response = await api.get('/laboratory-requests', {
        params: { 
          search: query,
          status: 'Completed'
        }
      });
      requests.value = response.data.data;
    } catch (error) {
      console.error('Error:', error);
    } finally {
      loadingRequests.value = false;
    }
  } else {
    requests.value = [];
  }
};

const handleSearch = () => {
  currentPage.value = 1;
  fetchPayments();
};

const handleSizeChange = (val) => {
  pageSize.value = val;
  fetchPayments();
};

const handleCurrentChange = (val) => {
  currentPage.value = val;
  fetchPayments();
};

const resetForm = () => {
  form.value = {
    laboratory_request_id: '',
    amount: 0,
    payment_method: '',
    payment_date: '',
    status: 'Pending',
    transaction_id: '',
    notes: ''
  };
  if (formRef.value) {
    formRef.value.resetFields();
  }
};

const handleAdd = () => {
  dialogType.value = 'add';
  resetForm();
  dialogVisible.value = true;
};

const handleView = (row) => {
  selectedPayment.value = row;
  viewDialogVisible.value = true;
};

const handlePrint = (row) => {
  // Implement receipt printing functionality
  ElMessage.success('Receipt printed successfully');
};

const handleDelete = (row) => {
  selectedPayment.value = row;
  deleteDialogVisible.value = true;
};

const handleSubmit = async () => {
  if (!formRef.value) return;
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      submitting.value = true;
      try {
        if (dialogType.value === 'add') {
          await api.post('/payments', form.value);
          ElMessage.success('Payment processed successfully');
        } else {
          await api.put(`/payments/${form.value.id}`, form.value);
          ElMessage.success('Payment updated successfully');
        }
        dialogVisible.value = false;
        fetchPayments();
      } catch (error) {
        ElMessage.error('Error processing payment');
        console.error('Error:', error);
      } finally {
        submitting.value = false;
      }
    }
  });
};

const confirmDelete = async () => {
  if (!selectedPayment.value) return;
  
  deleting.value = true;
  try {
    await api.delete(`/payments/${selectedPayment.value.id}`);
    ElMessage.success('Payment deleted successfully');
    deleteDialogVisible.value = false;
    fetchPayments();
  } catch (error) {
    ElMessage.error('Error deleting payment');
    console.error('Error:', error);
  } finally {
    deleting.value = false;
  }
};

onMounted(() => {
  fetchPayments();
});
</script>

<style scoped>
.payments {
  width: 100%;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-xl);
}

.page-header h2 {
  margin: 0;
  font-size: var(--font-size-2xl, 24px);
  font-weight: var(--font-weight-medium);
}

.card-header {
  display: flex;
  justify-content: flex-end;
}

.search-input {
  width: 300px;
}

.pagination-container {
  margin-top: var(--spacing-xl);
  display: flex;
  justify-content: flex-end;
}

:deep(.el-card__header) {
  padding: var(--spacing-md) var(--spacing-xl);
}

:deep(.el-table) {
  margin-top: var(--spacing-sm);
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-md);
}

.payment-details {
  padding: var(--spacing-xl);
}

.request-details {
  margin-top: var(--spacing-xl);
}

.request-details h3 {
  margin-bottom: var(--spacing-lg);
}

.total-amount {
  margin-top: var(--spacing-xl);
  text-align: right;
}

.notes {
  margin-top: var(--spacing-xl);
}

.notes h3 {
  margin-bottom: var(--spacing-md);
}

.notes p {
  margin: 0;
  color: var(--color-text-secondary, #666);
}
</style> 