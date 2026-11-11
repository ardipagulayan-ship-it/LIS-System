<template>
  <el-dialog
    :model-value="modelValue"
    title="Request Details"
    width="800px"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div v-if="request" class="request-details">
      <el-descriptions :column="2" border>
        <el-descriptions-item label="Patient">
          {{ request.patient.name }}
        </el-descriptions-item>
        <el-descriptions-item label="Doctor">
          {{ request.doctor.name }}
        </el-descriptions-item>
        <el-descriptions-item label="Request Date">
          {{ formatDate(request.request_date) }}
        </el-descriptions-item>
        <el-descriptions-item label="Status">
          <el-tag :type="getStatusType(request.status)">
            {{ request.status }}
          </el-tag>
        </el-descriptions-item>
      </el-descriptions>

      <div class="tests-list">
        <h3>Requested Tests</h3>
        <el-table :data="request.tests" border>
          <el-table-column prop="name" label="Test" />
          <el-table-column prop="price" label="Price" width="120">
            <template #default="{ row }">
              {{ formatCurrency(row.price) }}
            </template>
          </el-table-column>
        </el-table>
        <div class="total-amount">
          <h3>Total Amount: {{ formatCurrency(request.total_amount) }}</h3>
        </div>
      </div>

      <div v-if="request.notes" class="notes">
        <h3>Notes</h3>
        <p>{{ request.notes }}</p>
      </div>
    </div>
  </el-dialog>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true
  },
  request: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['update:modelValue']);

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
    'In Progress': 'primary',
    'Completed': 'success',
    'Cancelled': 'danger'
  };
  return types[status] || 'info';
};
</script>

<style scoped>
.request-details {
  padding: 20px;
}

.tests-list {
  margin-top: 20px;
}

.tests-list h3 {
  margin-bottom: 15px;
}

.total-amount {
  margin-top: 20px;
  text-align: right;
}

.notes {
  margin-top: 20px;
}

.notes h3 {
  margin-bottom: 10px;
}

.notes p {
  margin: 0;
  color: #666;
}
</style> 