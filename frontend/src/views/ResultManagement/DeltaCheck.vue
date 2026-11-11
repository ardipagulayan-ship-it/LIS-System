<template>
  <div class="delta-check-view">
    <div class="page-header">
      <h2 class="page-title">Delta Check</h2>
      <div class="header-actions">
        <el-button @click="goBack">Back</el-button>
      </div>
    </div>

      <div
        v-for="dateGroup in groupedByDateAndPatient"
        :key="dateGroup.date"
        class="date-group"
      >

        <div
          v-for="patientGroup in dateGroup.patients"
          :key="patientGroup.key"
          class="delta-card"
        >
          <div class="card-header">
            <div class="card-header-left">
              <div class="patient-avatar">{{ getPatientInitials(patientGroup.items[0]) }}</div>
              <div class="patient-details">
                <div class="patient-name">Patient: {{ formatPatientName(patientGroup.items[0]) }}</div>
                <div class="patient-meta">
                  <span v-if="patientGroup.hasCritical" class="meta-chip critical">
                    <el-icon><WarningFilled /></el-icon>
                    Critical Alert
                  </span>
                  <span class="meta-chip muted">Tests: {{ patientGroup.items.length }} {{ dateGroup.alertsCount === 1 ? 'alert' : 'alerts' }}</span>
                  <span class="meta-chip muted">{{ patientGroup.items[0].test?.name || '—' }} </span>
                  <span class="meta-chip muted">Last Review: {{ formatTime(patientGroup.lastReview) || '—' }}</span>
                </div>
              </div>
            </div>
            <div class="group-date">{{ formatGroupDate(dateGroup.date) }}</div>
          </div>

          <el-table
            :data="patientGroup.items"
            border
            stripe
            class="patient-tests-table"
            size="large"
            :row-key="row => row.id"
          >
            <!-- <el-table-column type="expand">
              <template #default="{ row }">
                <div class="test-row-details">
                  <div class="detail-block">
                    <span class="detail-label">Remarks</span>
                    <span class="detail-text">{{ row.remarks || 'No remarks provided' }}</span>
                  </div>
                  <div class="detail-block recommendations">
                    <span class="detail-label">Recommendations</span>
                    <template v-if="row.recommendations">
                      <div class="recommendation-chip" :class="`severity-${row.recommendations.severity || 'info'}`">
                        {{ getRecommendationText(row.recommendations) }}
                      </div>
                      <div
                        v-if="row.recommendations.action_required?.immediate?.steps?.length"
                        class="recommendation-steps"
                      >
                        <span class="steps-label">Immediate Steps</span>
                        <ul>
                          <li v-for="(step, index) in row.recommendations.action_required.immediate.steps" :key="index">
                            {{ step }}
                          </li>
                        </ul>
                      </div>
                    </template>
                    <span v-else class="detail-text muted">No recommendations</span>
                  </div>
                </div>
              </template>
            </el-table-column> -->

            <el-table-column label="Parameter" min-width="180">
              <template #default="{ row }">{{ row.test_parameter?.test_name || row.test_parameter?.name || '—' }}</template>
            </el-table-column>

            <el-table-column label="Current" width="120">
              <template #default="{ row }">
                <span class="metric highlight">{{ row.current_value ?? '—' }}</span>
              </template>
            </el-table-column>

            <el-table-column label="Previous" width="120">
              <template #default="{ row }">{{ row.previous_value ?? '—' }}</template>
            </el-table-column>

            <el-table-column label="% Difference" width="150" align="center">
              <template #default="{ row }">
                <span class="delta-chip" :class="getTrendClassFromRow(row)">
                  <el-icon class="delta-icon">
                    <component :is="getTrendIconFromRow(row)" />
                  </el-icon>
                  {{ getPercentDisplay(row) }}
                </span>
              </template>
            </el-table-column>

            <el-table-column label="Abs Difference" width="150" align="right">
              <template #default="{ row }">{{ row.absolute_difference ?? '—' }}</template>
            </el-table-column>

            <el-table-column label="Recommendation" min-width="140">
              <template #default="{ row }">
                <span
                  class="recommendation-text"
                  :class="`severity-${(row.recommendations?.severity || 'info').toLowerCase()}`"
                >
                  {{ (row.recommendations?.severity_display || row.recommendations?.severity || 'Info').toString().toUpperCase() }}
                </span>
              </template>
            </el-table-column>
            <el-table-column label="Remarks" min-width="140">
              <template #default="{ row }">
                {{ row.remarks || 'No remarks provided' }}
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>

      <div class="pagination-container">
        <el-pagination
          v-if="total > 0"
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { format, parseISO } from 'date-fns';
import { WarningFilled, ArrowUp, ArrowDown, Minus } from '@element-plus/icons-vue';
import api from '@/utils/axios';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const deltaChecks = ref([]);
const currentPage = ref(1);
const pageSize = ref(20);
const total = ref(0);

const filters = ref({
  patient_id: route.query.patient_id || '',
  test_id: route.query.test_id || '',
  test_parameter_id: route.query.test_parameter_id || '',
  status: 'flagged'
});

const fetchDeltaChecks = async () => {
  loading.value = true;
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
      ...filters.value,
    };
    const res = await api.get('/management-report/delta-checks', { params });
    deltaChecks.value = res.data?.data || [];
    total.value = res.data?.total || 0;
  } catch (e) {
    console.error('Failed to fetch delta checks', e);
  } finally {
    loading.value = false;
  }
};

const handleSizeChange = (val) => {
  pageSize.value = val;
  currentPage.value = 1;
  fetchDeltaChecks();
};

const handleCurrentChange = (val) => {
  currentPage.value = val;
  fetchDeltaChecks();
};

const formatTime = (dateStr) => {
  if (!dateStr) return '';
  try { return format(parseISO(dateStr), 'HH:mm'); } catch { return dateStr; }
};

const formatPercent = (num) => {
  if (num === null || num === undefined) return '';
  return `${Number(num).toFixed(2)}%`;
};

const goBack = () => {
  if (route.query.from === 'category' && route.query.category_id && route.query.category_name) {
    router.push({ name: 'result-category', params: { categoryId: route.query.category_id, categoryName: route.query.category_name } });
  } else {
    router.push({ name: 'result-management-dashboard' });
  }
};

onMounted(() => {
  fetchDeltaChecks();
});

const getPatientKey = (row) => {
  const patient = row?.test_result?.laboratory_request?.patient;
  if (patient?.id) return `patient-${patient.id}`;
  const requestId = row?.test_result?.laboratory_request_id || row?.laboratory_request_id;
  if (requestId) return `request-${requestId}`;
  return `row-${row.id}`;
};

// Group by date and patient (descending)
const groupedByDateAndPatient = computed(() => {
  const dateMap = new Map();

  for (const item of deltaChecks.value) {
    const dateKey = formatDateOnly(item.created_at);
    const patientKey = getPatientKey(item);

    if (!dateMap.has(dateKey)) {
      dateMap.set(dateKey, new Map());
    }

    const patientMap = dateMap.get(dateKey);

    if (!patientMap.has(patientKey)) {
      patientMap.set(patientKey, {
        key: patientKey,
        items: [],
        hasCritical: false,
        lastReview: null,
      });
    }

    const patientGroup = patientMap.get(patientKey);
    patientGroup.items.push(item);

    if (!patientGroup.lastReview || new Date(item.created_at) > new Date(patientGroup.lastReview)) {
      patientGroup.lastReview = item.created_at;
    }

    const severity = item.recommendations?.severity || item.recommendations?.severity_display;
    if (typeof severity === 'string' && severity.toLowerCase() === 'critical') {
      patientGroup.hasCritical = true;
    }
  }

  const groups = Array.from(dateMap.entries()).map(([date, patientMap]) => {
    const patients = Array.from(patientMap.values()).map((group) => ({
      ...group,
      items: group.items.sort((a, b) => new Date(b.created_at) - new Date(a.created_at)),
    }));

    const alertsCount = patients.reduce((sum, group) => sum + group.items.length, 0);

    return {
      date,
      patients,
      alertsCount,
    };
  });

  groups.sort((a, b) => new Date(b.date) - new Date(a.date));
  return groups;
});

const formatDateOnly = (dateStr) => {
  if (!dateStr) return '';
  try { return format(parseISO(dateStr), 'yyyy-MM-dd'); } catch { return dateStr; }
};

const formatGroupDate = (dateStr) => {
  if (!dateStr) return '';
  try { return format(parseISO(dateStr), 'MMMM dd, yyyy'); } catch { return dateStr; }
};

const getSeverityType = (severity) => {
  const map = { critical: 'danger', high: 'warning', moderate: 'warning', low: 'success' };
  return map[severity] || 'info';
};

const getRecommendationText = (recommendations) => {
  if (!recommendations) return 'No recommendations';
  if (recommendations.action_required?.immediate?.immediate) return recommendations.action_required.immediate.immediate;
  if (recommendations.action_required?.immediate) return recommendations.action_required.immediate;
  return 'No specific action required';
};

const formatPatientName = (row) => {
  const patient = row?.test_result?.laboratory_request?.patient;
  if (!patient) return 'Unknown Patient';
  const { first_name = '', middle_name = '', last_name = '' } = patient;
  return [last_name, [first_name, middle_name].filter(Boolean).join(' ')].filter(Boolean).join(', ');
};

const getPatientInitials = (row) => {
  const patient = row?.test_result?.laboratory_request?.patient;
  if (!patient) return 'PT';
  const names = [patient.first_name, patient.middle_name, patient.last_name].filter(Boolean);
  if (!names.length) return 'PT';
  return names
    .map((name) => name.trim()[0])
    .join('')
    .substring(0, 2)
    .toUpperCase();
};

const trendIcons = {
  up: ArrowUp,
  down: ArrowDown,
  flat: Minus
};

const parseNumber = (value) => {
  const num = Number(value);
  return Number.isFinite(num) ? num : null;
};

const getPercentDifference = (row) => {
  const current = parseNumber(row.current_value);
  const previous = parseNumber(row.previous_value);
  if (current !== null && previous !== null && previous !== 0) {
    return ((current - previous) / previous) * 100;
  }
  const fallback = parseNumber(row.percentage_difference);
  return fallback;
};

const getTrendFromRow = (row) => {
  const percent = getPercentDifference(row);
  if (percent === null) return 'flat';
  if (percent > 0) return 'up';
  if (percent < 0) return 'down';
  return 'flat';
};

const getTrendClassFromRow = (row) => `trend-${getTrendFromRow(row)}`;

const getTrendIconFromRow = (row) => trendIcons[getTrendFromRow(row)] || trendIcons.flat;

const getPercentDisplay = (row) => {
  const percent = getPercentDifference(row);
  if (percent === null || Number.isNaN(percent)) return '—';
  return `${Math.abs(percent).toFixed(2)}%`;
};
</script>

<style scoped>
.delta-check-view {
  display: grid;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.page-title {
  margin: 0;
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-medium);
}
.header-actions {
  display: flex;
  gap: var(--spacing-sm);
  align-items: center;
}
.content-card {
  border: none;
  box-shadow: var(--shadow-sm);
  padding: var(--spacing-lg);
}
.pagination-container {
  display: flex;
  justify-content: flex-end;
  margin-top: var(--spacing-lg);
}

.date-group {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-xl);
}

.group-date {
  color: var(--color-text-primary);
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-bold);
}

.group-count {
  color: var(--color-text-tertiary);
  font-weight: var(--font-weight-medium);
}

.delta-card {
  background: var(--color-bg-primary);
  border: 1px solid var(--color-border-secondary);
  border-radius: var(--border-radius-lg);
  padding: var(--spacing-lg);
  box-shadow: var(--shadow-md);
  transition: box-shadow var(--transition-base) ease, transform var(--transition-base) ease;
  margin-bottom: var(--spacing-md);
}

.delta-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-md);
}

.card-header-left {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
}

.patient-avatar {
  width: 48px;
  height: 48px;
  border-radius: var(--border-radius-lg);
  background: var(--color-danger);
  color: var(--color-text-light);
  font-weight: var(--font-weight-bold);
  font-size: var(--font-size-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: var(--shadow-md);
}

.patient-name {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
  margin-bottom: var(--spacing-xs);
  text-align: left;
}

.patient-meta {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-xs);
}

.meta-chip {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-xs);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
  padding: var(--spacing-xs) var(--spacing-md);
  border-radius: var(--border-radius-full);
  background: var(--color-bg-tertiary);
  color: var(--color-text-secondary);
}

.meta-chip.critical {
  background: var(--color-danger-light);
  color: var(--color-danger);
}

.meta-chip.muted {
  background: var(--color-bg-tertiary);
  color: var(--color-text-tertiary);
}

.details-button {
  border-radius: var(--border-radius-full);
  font-weight: var(--font-weight-semibold);
}


.tests-table {
  margin-top: var(--spacing-md);
}

.patient-tests-table {
  border-radius: var(--border-radius-md);
  overflow: hidden;
}

.metric.highlight {
  font-weight: var(--font-weight-semibold);
  color: var(--color-warning);
}

.patient-tests-table :deep(.el-table__header-wrapper th) {
  background: var(--color-bg-tertiary);
  color: var(--color-text-primary);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-bold);
  text-transform: uppercase;
}

.patient-tests-table :deep(.el-table__row) {
  transition: background var(--transition-base) ease;
}

.patient-tests-table :deep(.el-table__row:hover) {
  background: var(--color-bg-secondary);
}

.patient-tests-table :deep(.el-table__cell) {
  font-size: var(--font-size-xs);
  color: var(--color-text-primary);
  font-weight: var(--font-weight-bold);
}

.patient-tests-table :deep(.el-table__cell > .cell) {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
}

.patient-tests-table :deep(.metric.highlight) {
  font-weight: var(--font-weight-semibold);
  color: var(--color-warning);
}

.recommendation-text {
  font-weight: var(--font-weight-bold);
  font-size: var(--font-size-xs);
}

.recommendation-text.severity-critical {
  color: var(--color-danger);
}

.recommendation-text.severity-high {
  color: var(--color-warning-dark);
}

.recommendation-text.severity-moderate {
  color: var(--color-warning);
}

.recommendation-text.severity-low {
  color: var(--color-info);
}

.tests-table td.test-cell {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
}

.tests-table td.test-cell.action {
  justify-content: flex-end;
}

.delta-chip {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-xs);
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--border-radius-full);
  font-weight: var(--font-weight-bold);
  font-size: var(--font-size-xs);
  background: var(--color-bg-tertiary);
  color: var(--color-text-primary);
}

.delta-chip .delta-icon {
  font-size: var(--font-size-xs);
}

.delta-chip.trend-up {
  background: var(--color-success-light);
  color: var(--color-success);
}

.delta-chip.trend-down {
  background: var(--color-danger-light);
  color: var(--color-danger);
}

.delta-chip.trend-flat {
  background: var(--color-bg-tertiary);
  color: var(--color-text-tertiary);
}

.test-row-details {
  margin-top: var(--spacing-md);
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: var(--spacing-lg);
}

.test-detail-row td {
  background: var(--color-bg-secondary);
  border-bottom: none;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity var(--transition-base) ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.detail-block {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  padding: var(--spacing-lg);
  border-radius: var(--border-radius-lg);
  background: var(--color-bg-primary);
  border: 1px solid var(--color-border-secondary);
}

.detail-label {
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-bold);
  text-transform: uppercase;
  color: var(--color-text-tertiary);
}

.detail-text {
  font-size: var(--font-size-sm);
  line-height: 1.5;
  color: var(--color-text-primary);
}

.detail-text.muted {
  color: var(--color-text-tertiary);
}

.recommendations .recommendation-chip {
  display: inline-flex;
  align-items: center;
  padding: var(--spacing-md) var(--spacing-lg);
  border-radius: var(--border-radius-md);
  font-weight: var(--font-weight-semibold);
  font-size: var(--font-size-sm);
}

.recommendations .recommendation-chip.severity-critical {
  background: var(--color-danger-light);
  color: var(--color-danger);
}

.recommendations .recommendation-chip.severity-high {
  background: var(--color-warning-light);
  color: var(--color-warning-dark);
}

.recommendations .recommendation-chip.severity-moderate {
  background: var(--color-warning-light);
  color: var(--color-warning);
}

.recommendations .recommendation-chip.severity-low {
  background: var(--color-info-light);
  color: var(--color-info);
}

.recommendations .recommendation-chip.severity-info {
  background: var(--color-info-light);
  color: var(--color-info-dark);
}

.recommendation-steps {
  margin-top: var(--spacing-sm);
}

.steps-label {
  display: inline-block;
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-bold);
  text-transform: uppercase;
  color: var(--color-text-tertiary);
  margin-bottom: var(--spacing-xs);
}

.recommendation-steps ul {
  margin: 0;
  padding-left: var(--spacing-md);
  color: var(--color-text-primary);
}

.recommendation-steps li {
  margin-bottom: var(--spacing-xs);
  line-height: 1.5;
}

@media (max-width: 768px) {
  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--spacing-md);
  }

  .tests-table table {
    min-width: 720px;
  }
}
</style>


