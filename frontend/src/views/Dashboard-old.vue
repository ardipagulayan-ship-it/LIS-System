<template>
  <div class="page-container">
    <header class="page-header">
      <h1 class="page-title">Dashboard</h1>
    </header>

    <main class="page-content">
      <!-- Summary Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-card__header">
            <span class="stat-card__title">Total Patients</span>
          </div>
          <div class="stat-card__value stat-card__value--primary">
            {{ stats.totalPatients || 0 }}
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card__header">
            <span class="stat-card__title">Total Tests</span>
          </div>
          <div class="stat-card__value stat-card__value--primary">
            {{ stats.totalTests || 0 }}
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card__header">
            <span class="stat-card__title">Pending Results</span>
          </div>
          <div class="stat-card__value stat-card__value--warning">
            {{ stats.pendingResults || 0 }}
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card__header">
            <span class="stat-card__title">Total Revenue</span>
          </div>
          <div class="stat-card__value stat-card__value--success">
            {{ formatCurrency(stats.totalRevenue || 0) }}
          </div>
        </div>
      </div>

      <!-- Recent Activity and Summary -->
      <div class="content-grid">
        <div class="content-main">
          <div class="card">
            <div class="card__header">
              <h2 class="card__title">Recent Laboratory Requests</h2>
              <el-button type="text" @click="$router.push('/laboratory-requests')" class="link-button">
                View All
              </el-button>
            </div>
            <div class="card__body">
              <table class="table">
                <thead class="table__head">
                  <tr>
                    <th class="table__cell table__cell--header">Date</th>
                    <th class="table__cell table__cell--header">Patient</th>
                    <th class="table__cell table__cell--header">Doctor</th>
                    <th class="table__cell table__cell--header">Status</th>
                  </tr>
                </thead>
                <tbody class="table__body">
                  <tr v-for="request in recentRequests" :key="request.id" class="table__row">
                    <td class="table__cell">{{ formatDate(request.request_date) }}</td>
                    <td class="table__cell">{{ request.patient?.name || 'N/A' }}</td>
                    <td class="table__cell">{{ request.doctor?.name || 'N/A' }}</td>
                    <td class="table__cell">
                      <span :class="['badge', `badge--${getStatusBadgeType(request.status)}`]">
                        {{ request.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="content-side">
          <div class="card">
            <div class="card__header">
              <h2 class="card__title">Payment Summary</h2>
            </div>
            <div class="card__body" v-loading="loading">
              <div class="summary-item">
                <span class="summary-item__label">Today's Revenue</span>
                <span class="summary-item__value">{{ formatCurrency(stats.todayRevenue || 0) }}</span>
              </div>
              <div class="summary-item">
                <span class="summary-item__label">This Week</span>
                <span class="summary-item__value">{{ formatCurrency(stats.weekRevenue || 0) }}</span>
              </div>
              <div class="summary-item">
                <span class="summary-item__label">This Month</span>
                <span class="summary-item__value">{{ formatCurrency(stats.monthRevenue || 0) }}</span>
              </div>
              <div class="divider"></div>
              <div class="summary-item">
                <span class="summary-item__label">Pending Payments</span>
                <span class="summary-item__value summary-item__value--warning">{{ formatCurrency(stats.pendingPayments || 0) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/utils/axios';

const router = useRouter();
const loading = ref(false);
const stats = ref({});
const recentRequests = ref([]);

const formatDate = (date) => {
  return new Date(date).toLocaleDateString();
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(amount);
};

const getStatusType = (status) => {
  switch (status.toLowerCase()) {
    case 'completed':
      return 'success';
    case 'pending':
      return 'warning';
    case 'cancelled':
      return 'danger';
    default:
      return 'info';
  }
};

const getStatusBadgeType = (status) => {
  switch (status.toLowerCase()) {
    case 'completed':
      return 'success';
    case 'pending':
      return 'warning';
    case 'cancelled':
      return 'danger';
    default:
      return 'info';
  }
};

const fetchDashboardData = async () => {
  loading.value = true;
  try {
    const response = await api.get('/dashboard');
    stats.value = response.data.stats;
    recentRequests.value = response.data.recentRequests;
  } catch (error) {
    console.error('Error fetching dashboard data:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchDashboardData();
});
</script>

<style scoped>
.page-container {
  width: 100%;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.page-header {
  display: flex;
  padding: var(--spacing-2xl) var(--spacing-xl);
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  margin-bottom: 0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.page-title {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -0.5px;
}

/* Breadcrumb Navigation */
.breadcrumb {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  font-size: var(--font-size-sm);
}

.breadcrumb__item {
  color: #6b7280;
  display: flex;
  align-items: center;
}

.breadcrumb__item--active {
  color: #2563eb;
  font-weight: 600;
}

.breadcrumb__link {
  color: #2563eb;
  text-decoration: none;
  transition: color 0.2s ease;
  cursor: pointer;
}

.breadcrumb__link:hover {
  color: #1e40af;
  text-decoration: underline;
}

.breadcrumb__separator {
  color: #d1d5db;
  margin: 0 2px;
}

.page-content {
  flex: 1;
  padding: var(--spacing-2xl) var(--spacing-xl);
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--spacing-lg);
  margin-bottom: var(--spacing-2xl);
}

.stat-card {
  background: linear-gradient(135deg, var(--color-primary) 0%, #0d5bb8 100%);
  border: 1px solid var(--color-border-light);
  border-radius: var(--border-radius-md);
  padding: var(--spacing-xl);
  box-shadow: var(--shadow-md);
  transition: all var(--transition-normal);
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.stat-card__header {
  display: flex;
  align-items: center;
  margin-bottom: var(--spacing-md);
}

.stat-card__title {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: rgba(255, 255, 255, 0.9);
}

.stat-card__value {
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: white;
  text-align: center;
  line-height: 1;
}

.stat-card__value--primary {
  color: white;
}

.stat-card__value--success {
  color: #67C23A;
}

.stat-card__value--warning {
  color: #E6A23C;
}

/* Content Grid */
.content-grid {
  display: grid;
  grid-template-columns: 3fr 1fr;
  gap: var(--spacing-lg);
}

.content-main,
.content-side {
  width: 100%;
}

/* Card Styles */
.card {
  background-color: var(--color-bg-primary);
  border: 1px solid var(--color-border-primary);
  border-radius: var(--border-radius-md);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  transition: box-shadow var(--transition-normal);
}

.card:hover {
  box-shadow: var(--shadow-md);
}

.card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-lg);
  border-bottom: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-secondary);
}

.card__title {
  margin: 0;
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
}

.card__body {
  padding: var(--spacing-lg);
}

.link-button {
  color: var(--color-primary);
  text-decoration: none;
  cursor: pointer;
  transition: color var(--transition-normal);
}

.link-button:hover {
  color: var(--color-primary-hover);
}

/* Table Styles */
.table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--font-size-sm);
}

.table__head {
  background-color: var(--color-bg-secondary);
  border-bottom: 2px solid var(--color-border-primary);
}

.table__row {
  border-bottom: 1px solid var(--color-border-light);
  transition: background-color var(--transition-normal);
}

.table__row:hover {
  background-color: var(--color-bg-secondary);
}

.table__cell {
  padding: var(--spacing-md) var(--spacing-lg);
  text-align: left;
  color: var(--color-text-primary);
}

.table__cell--header {
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-secondary);
  text-transform: uppercase;
  font-size: var(--font-size-xs);
  letter-spacing: 0.5px;
}

/* Badge Styles */
.badge {
  display: inline-block;
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: var(--border-radius-full);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge--success {
  background-color: var(--color-success);
  color: white;
}

.badge--warning {
  background-color: var(--color-warning);
  color: white;
}

.badge--danger {
  background-color: var(--color-danger);
  color: white;
}

.badge--info {
  background-color: var(--color-info);
  color: white;
}

/* Summary Items */
.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-md) 0;
  font-size: var(--font-size-sm);
  color: var(--color-text-primary);
}

.summary-item__label {
  font-weight: var(--font-weight-medium);
  color: var(--color-text-secondary);
}

.summary-item__value {
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
}

.summary-item__value--warning {
  color: var(--color-warning);
}

.divider {
  height: 1px;
  background-color: var(--color-border-light);
  margin: var(--spacing-md) 0;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .content-grid {
    grid-template-columns: 1fr;
  }

  .stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  }
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    padding: var(--spacing-xl) var(--spacing-lg);
  }

  .page-content {
    padding: var(--spacing-lg);
  }

  .page-title {
    font-size: 1.5rem;
  }

  .stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
  }

  .stat-card {
    padding: var(--spacing-md);
  }

  .stat-card__value {
    font-size: var(--font-size-lg);
  }

  .table__cell {
    padding: var(--spacing-sm) var(--spacing-md);
    font-size: var(--font-size-xs);
  }
}

@media (max-width: 576px) {
  .page-header {
    padding: var(--spacing-md);
  }

  .page-content {
    padding: var(--spacing-md);
  }

  .page-title {
    font-size: var(--font-size-lg);
  }

  .stats-grid {
    grid-template-columns: 1fr;
    gap: var(--spacing-sm);
  }

  .card__header {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--spacing-md);
  }

  .card__body {
    padding: var(--spacing-md);
  }

  .summary-item {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--spacing-xs);
  }

  .table {
    font-size: var(--font-size-xs);
  }

  .table__cell {
    padding: var(--spacing-xs) var(--spacing-sm);
  }
}
</style> 