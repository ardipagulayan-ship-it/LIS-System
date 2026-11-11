<template>
  <el-dialog
    :model-value="visible"
    :title="null"
    width="50%"
    class="standard-modal patient-history-modal"
    @close="handleClose"
    :destroy-on-close="true"
    :show-close="false"
  >
    <!-- Standard Modal Header -->
    <template #header>
      <div class="modal-header">
        <div class="brand">
          <span class="brand-logo">📋</span>
          <span class="brand-name">Cumulative Patient Review</span>
        </div>
        <div class="header-actions">
          <el-button type="danger" size="small" @click="handleClose">
            <el-icon><Close /></el-icon> Close
          </el-button>
        </div>
      </div>
    </template>

    <div v-loading="loading" class="modal-content-container">
      <!-- Standard Patient Banner -->
      <div class="patient-banner-standard" v-if="patient">
        <div class="patient-primary-info">
          <div class="avatar-circle">
            <el-icon><User /></el-icon>
          </div>
          <div class="name-section">
            <div class="patient-fullname">{{ patient.last_name }}, {{ patient.first_name }}</div>
            <div class="patient-id-tag">Patient ID: {{ patient.id }}</div>
          </div>
        </div>
        
        <div class="patient-secondary-info">
          <div class="info-item">
            <span class="label">GENDER</span>
            <span class="value">{{ patient.gender || 'N/A' }}</span>
          </div>
          <div class="info-item">
            <span class="label">AGE</span>
            <span class="value">{{ calculateAge(patient.date_of_birth) }}</span>
          </div>
          <div class="info-item">
            <span class="label">REVIEW PERIOD</span>
            <span class="value">Last 6 Months</span>
          </div>
        </div>

        <div class="banner-stats">
          <div v-if="abnormalCount > 0" class="stat-badge abnormal">
            <el-icon><Warning /></el-icon>
            <span>{{ abnormalCount }} Abnormal</span>
          </div>
          <div v-if="criticalCount > 0" class="stat-badge critical">
            <el-icon><CircleCloseFilled /></el-icon>
            <span>{{ criticalCount }} Critical</span>
          </div>
        </div>
      </div>

      <!-- Main Content Layout -->
      <div class="main-layout">
        <!-- Toolbar Card -->
        <div class="content-card toolbar-card">
          <div class="toolbar-left">
            <el-input
              v-model="searchQuery"
              placeholder="Search parameters or tests..."
              prefix-icon="Search"
              clearable
              size="default"
              style="width: 350px"
            />
          </div>
          <div class="toolbar-right">
            <el-radio-group v-model="viewMode" size="default">
              <el-radio-button label="cumulative">
                <el-icon><Grid /></el-icon> Cumulative Table
              </el-radio-button>
              <el-radio-button label="timeline">
                <el-icon><Operation /></el-icon> Timeline View
              </el-radio-button>
            </el-radio-group>
          </div>
        </div>

        <!-- Data Table Section -->
        <div class="content-card table-card">
          <!-- Main Cumulative View (Pivot Table) -->
          <div v-if="viewMode === 'cumulative' && pivotData.length > 0" class="cumulative-view">
            <el-table 
              :data="pivotData" 
              border 
              style="width: 100%" 
              height="550px"
              class="pivot-table standard-table"
              size="small"
              stripped
            >
              <el-table-column fixed prop="parameterKey" label="Parameter / Test" width="240">
                <template #default="{ row }">
                  <div class="param-info">
                    <div class="param-name-bold">{{ row.parameterName }}</div>
                    <div class="test-category-sub">{{ row.testName }}</div>
                  </div>
                </template>
              </el-table-column>

              <el-table-column label="6-Month Trend" align="center">
                <template #default="{ row }">
                  <div class="trend-visualization">
                    <svg v-if="row.historyValues.length > 1" width="100" height="30" class="sparkline-svg">
                      <polyline
                        fill="none"
                        stroke="#409eff"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        :points="getSparklinePoints(row.historyValues)"
                      />
                      <circle 
                        :cx="95" 
                        :cy="getSparklineLastY(row.historyValues)" 
                        r="3" 
                        fill="#409eff" 
                      />
                    </svg>
                    <span v-else class="no-trend-label">No Trend</span>
                  </div>
                </template>
              </el-table-column>

              <el-table-column 
                v-for="request in uniqueRequests" 
                :key="request.id" 
                align="center"
              >
                <template #header>
                  <div class="column-date-header">
                    <div class="date-main">{{ formatDateShort(request.date) }}</div>
                    <div class="sample-id-sub">#{{ request.sample_id }}</div>
                  </div>
                </template>
                <template #default="{ row }">
                  <div 
                    v-if="row.resultsByRequest[request.id]" 
                    class="history-result-block"
                    :class="getFlagClass(row.resultsByRequest[request.id].flag)"
                  >
                    <div class="result-row-main">
                      <span class="res-val">{{ row.resultsByRequest[request.id].result || '----' }}</span>
                      <el-icon v-if="getTrend(row, request.id) === 'up'" class="trend-icon-up"><Top /></el-icon>
                      <el-icon v-if="getTrend(row, request.id) === 'down'" class="trend-icon-down"><Bottom /></el-icon>
                    </div>
                    <div class="res-unit-sub">{{ row.resultsByRequest[request.id].unit }}</div>
                  </div>
                  <span v-else class="empty-data-placeholder">----</span>
                </template>
              </el-table-column>
            </el-table>
          </div>

          <!-- Original Timeline View (Fallback) -->
          <div v-else-if="viewMode === 'timeline' && history.length > 0" class="timeline-view">
            <el-scrollbar height="550px">
              <div v-for="(group, date) in groupedHistory" :key="date" class="timeline-entry-card">
                <div class="entry-sidebar">
                  <div class="entry-date-badge">
                    <span class="badge-day">{{ formatDay(date) }}</span>
                    <span class="badge-month">{{ formatMonth(date) }}</span>
                  </div>
                </div>
                <div class="entry-content">
                  <div class="entry-header">
                    <span class="entry-request-id">Request #{{ group[0].request_number }}</span>
                    <span class="entry-result-count">{{ group.length }} Results</span>
                  </div>
                  <el-table :data="group" size="small" border class="entry-table">
                    <el-table-column prop="parameter_name" label="Parameter" />
                    <el-table-column prop="result" label="Result" width="150" align="center">
                      <template #default="{ row }">
                        <span :class="['res-text', getFlagClass(row.flag)]">
                          {{ row.result || '----' }}
                        </span>
                        <span class="res-unit"> {{ row.unit }}</span>
                      </template>
                    </el-table-column>
                    <el-table-column prop="flag" label="Flag" width="100" align="center">
                      <template #default="{ row }">
                        <el-tag v-if="row.flag" :type="getTagType(row.flag)" size="small" effect="dark">
                          {{ row.flag }}
                        </el-tag>
                        <span v-else class="no-flag">----</span>
                      </template>
                    </el-table-column>
                  </el-table>
                </div>
              </div>
            </el-scrollbar>
          </div>

          <!-- Empty State -->
          <div v-if="history.length === 0 && !loading" class="empty-data-view">
            <el-empty description="No records found for this period." />
          </div>
        </div>
      </div>
    </div>

    <!-- Standard Modal Footer -->
    <template #footer>
      <div class="modal-footer-standard">
        <div class="footer-info">
          <el-icon><InfoFilled /></el-icon>
          <span>Historical data is limited to the last 6 months of records.</span>
        </div>
        <div class="footer-actions">
          <el-button @click="handleClose">Dismiss</el-button>
          <el-button type="primary" @click="handleClose">Review Complete</el-button>
        </div>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import api from '@/utils/axios';
import { format, parseISO, differenceInYears } from 'date-fns';
import { 
  User, Search, Warning, CircleCloseFilled, 
  InfoFilled, Top, Bottom, Close, Grid, Operation 
} from '@element-plus/icons-vue';

const props = defineProps({
  visible: { type: Boolean, required: true },
  patientId: { type: [Number, String], required: true },
  patient: { type: Object, default: null },
  categoryId: { type: [Number, String], default: null }
});

const emit = defineEmits(['update:visible', 'close']);

const history = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const viewMode = ref('cumulative');

const fetchHistory = async () => {
  if (!props.patientId) return;
  loading.value = true;
  try {
    const url = props.categoryId 
      ? `/results/patient-history/${props.patientId}?category_id=${props.categoryId}`
      : `/results/patient-history/${props.patientId}`;
    const response = await api.get(url);
    history.value = response.data;
  } catch (error) {
    console.error('Error fetching history:', error);
  } finally {
    loading.value = false;
  }
};

// --- Logic (Remains unchanged as requested) ---

const uniqueRequests = computed(() => {
  const requestsMap = new Map();
  history.value.forEach(item => {
    if (!requestsMap.has(item.request_id)) {
      requestsMap.set(item.request_id, {
        id: item.request_id,
        sample_id: item.request_number,
        date: item.date
      });
    }
  });
  return Array.from(requestsMap.values()).sort((a, b) => new Date(b.date) - new Date(a.date));
});

const pivotData = computed(() => {
  const paramMap = new Map();
  history.value.forEach(item => {
    const key = `${item.test_name}|${item.parameter_name}`;
    if (!paramMap.has(key)) {
      paramMap.set(key, {
        parameterKey: key,
        parameterName: item.parameter_name,
        testName: item.test_name,
        resultsByRequest: {},
        historyValues: []
      });
    }
    const p = paramMap.get(key);
    p.resultsByRequest[item.request_id] = {
      result: item.result,
      flag: item.flag,
      unit: item.unit,
      date: item.date
    };
    const numVal = parseFloat(item.result);
    if (!isNaN(numVal)) p.historyValues.push({ val: numVal, date: item.date });
  });

  const results = Array.from(paramMap.values()).map(p => {
    p.historyValues.sort((a, b) => new Date(a.date) - new Date(b.date));
    return p;
  });

  if (!searchQuery.value) return results;
  const q = searchQuery.value.toLowerCase();
  return results.filter(r => 
    r.parameterName.toLowerCase().includes(q) || r.testName.toLowerCase().includes(q)
  );
});

const abnormalCount = computed(() => history.value.filter(h => h.flag && h.flag !== '').length);
const criticalCount = computed(() => history.value.filter(h => h.flag && h.flag.toUpperCase().includes('C')).length);

const getTrend = (row, currentRequestId) => {
  const requests = uniqueRequests.value;
  const currentIndex = requests.findIndex(r => r.id === currentRequestId);
  if (currentIndex === -1 || currentIndex === requests.length - 1) return null;
  const current = row.resultsByRequest[currentRequestId];
  const previous = row.resultsByRequest[requests[currentIndex + 1].id];
  if (!current || !previous) return null;
  const curVal = parseFloat(current.result);
  const preVal = parseFloat(previous.result);
  if (isNaN(curVal) || isNaN(preVal)) return null;
  return curVal > preVal ? 'up' : (curVal < preVal ? 'down' : null);
};

const getSparklinePoints = (historyValues) => {
  if (historyValues.length < 2) return "";
  const width = 100, height = 30, padding = 5;
  const minVal = Math.min(...historyValues.map(h => h.val));
  const maxVal = Math.max(...historyValues.map(h => h.val));
  const range = maxVal - minVal || 1;
  return historyValues.map((h, i) => {
    const x = (i / (historyValues.length - 1)) * (width - padding * 2) + padding;
    const y = height - ((h.val - minVal) / range) * (height - padding * 2) - padding;
    return `${x},${y}`;
  }).join(" ");
};

const getSparklineLastY = (historyValues) => {
  if (historyValues.length === 0) return 0;
  const height = 30, padding = 5;
  const minVal = Math.min(...historyValues.map(h => h.val));
  const maxVal = Math.max(...historyValues.map(h => h.val));
  const range = maxVal - minVal || 1;
  const last = historyValues[historyValues.length - 1];
  return height - ((last.val - minVal) / range) * (height - padding * 2) - padding;
};

const groupedHistory = computed(() => {
  const groups = {};
  history.value.forEach(item => {
    const date = item.date.split(' ')[0];
    if (!groups[date]) groups[date] = [];
    groups[date].push(item);
  });
  return Object.keys(groups).sort((a, b) => new Date(b) - new Date(a)).reduce((acc, k) => {
    acc[k] = groups[k];
    return acc;
  }, {});
});

const calculateAge = (dob) => dob ? differenceInYears(new Date(), parseISO(dob)) + 'y' : 'N/A';
const formatDateShort = (d) => d ? format(parseISO(d), 'MMM-dd-yy') : 'N/A';
const formatDay = (d) => d ? format(parseISO(d), 'dd') : '';
const formatMonth = (d) => d ? format(parseISO(d), 'MMM') : '';

const getFlagClass = (flag) => {
  if (!flag) return '';
  const f = flag.toUpperCase();
  if (f.includes('C')) return 'critical';
  if (f.includes('H')) return 'high';
  if (f.includes('L')) return 'low';
  return '';
};

const getTagType = (flag) => {
  if (!flag) return 'info';
  const f = flag.toUpperCase();
  if (f.includes('C')) return 'danger';
  if (f.includes('H')) return 'warning';
  if (f.includes('L')) return 'primary';
  return 'info';
};

const handleClose = () => emit('update:visible', false);

watch(() => props.visible, (v) => v && fetchHistory());
onMounted(() => props.visible && fetchHistory());
</script>

<style scoped>
/* ============================================================================
   PATIENT HISTORY MODAL STYLES (STANDARD UI ALIGNMENT)
   ============================================================================ */

/* Dialog Container Overrides */
.patient-history-modal :deep(.el-dialog__header) {
  padding: 0;
  border-bottom: none;
}

.patient-history-modal :deep(.el-dialog__body) {
  padding: 0;
  max-height: calc(100vh - 100px);
  overflow-y: auto;
}

/* Modal Header Brand (Standard LIS Pattern) */
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-md);
  padding: var(--spacing-md) var(--spacing-xl);
  background: var(--color-dark);
  color: var(--color-text-light);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: var(--border-radius-md) var(--border-radius-md) 0 0;
}

.brand {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  font-weight: var(--font-weight-semibold);
  letter-spacing: 0.3px;
}

.brand-logo {
  font-size: var(--font-size-xl);
}

.brand-name {
  font-size: var(--font-size-sm);
  opacity: 0.9;
  text-transform: uppercase;
  white-space: nowrap;
}

/* Modal Content Container */
.modal-content-container {
  padding: var(--spacing-lg);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

/* Patient Banner (Horizontal Card Pattern) */
.patient-banner-standard {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background-color: var(--color-bg-primary);
  padding: var(--spacing-lg) var(--spacing-xl);
  border-radius: var(--border-radius-lg);
  border: 1px solid var(--color-border-secondary);
  box-shadow: var(--shadow-sm);
}

.patient-primary-info {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
}

.avatar-circle {
  width: 48px;
  height: 48px;
  background-color: var(--color-primary-light);
  color: var(--color-primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  border: 2px solid var(--color-primary);
}

.patient-fullname {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
  line-height: 1.2;
}

.patient-id-tag {
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
  margin-top: 2px;
}

.patient-secondary-info {
  display: flex;
  gap: var(--spacing-2xl);
}

.info-item {
  display: flex;
  flex-direction: column;
}

.info-item .label {
  font-size: 12px;
  color: var(--color-text-tertiary);
  font-weight: var(--font-weight-bold);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 2px;
}

.info-item .value {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
}

.banner-stats {
  display: flex;
  gap: var(--spacing-md);
}

.stat-badge {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  padding: 6px 12px;
  border-radius: var(--border-radius-md);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-bold);
}

.stat-badge.abnormal { 
  background-color: var(--color-warning-light); 
  color: var(--color-warning-dark); 
  border: 1px solid var(--color-warning); 
}

.stat-badge.critical { 
  background-color: var(--color-danger-light); 
  color: var(--color-danger); 
  border: 1px solid var(--color-danger); 
}

/* Toolbar Card */
.toolbar-card {
  padding: var(--spacing-md) var(--spacing-xl);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: var(--color-bg-primary);
  border-radius: var(--border-radius-lg);
  border: 1px solid var(--color-border-secondary);
  margin-bottom: var(--spacing-md);
}

/* Table Card Content */
.table-card {
  background-color: var(--color-bg-primary);
  border-radius: var(--border-radius-lg);
  border: 1px solid var(--color-border-secondary);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}

/* Pivot Table Stylings */
.standard-table :deep(th) {
  background-color: var(--color-bg-secondary) !important;
  color: var(--color-text-primary) !important;
  font-weight: var(--font-weight-bold) !important;
  border-bottom: 2px solid var(--color-border-primary) !important;
}

.param-name-bold {
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
  font-size: var(--font-size-sm);
}

.test-category-sub {
  font-size: 12px;
  color: var(--color-text-tertiary);
  font-style: italic;
}

.column-date-header {
  line-height: 1.2;
}

.date-main {
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
}

.sample-id-sub {
  font-size: 12px;
  color: var(--color-text-tertiary);
  font-weight: var(--font-weight-normal);
}

/* Result Visualization */
.history-result-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 4px;
  border-radius: var(--border-radius-sm);
}

.result-row-main {
  display: flex;
  align-items: center;
  gap: 4px;
}

.res-val {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-bold);
}

.res-unit-sub {
  font-size: 12px;
  color: var(--color-text-tertiary);
}

/* Trend Icons */
.trend-icon-up { color: var(--color-danger); font-size: 12px; }
.trend-icon-down { color: var(--color-primary); font-size: 12px; }

/* Timeline Entry Styles */
.timeline-entry-card {
  display: flex;
  margin: var(--spacing-lg);
  border: 1px solid var(--color-border-secondary);
  border-radius: var(--border-radius-lg);
  overflow: hidden;
  background-color: var(--color-bg-primary);
}

.entry-sidebar {
  width: 80px;
  background-color: var(--color-bg-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  border-right: 1px solid var(--color-border-secondary);
}

.badge-day {
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-primary);
  line-height: 1;
}

.badge-month {
  font-size: 12px;
  font-weight: var(--font-weight-bold);
  text-transform: uppercase;
  color: var(--color-text-tertiary);
}

.entry-content {
  flex: 1;
  padding: var(--spacing-lg);
}

.entry-header {
  margin-bottom: var(--spacing-md);
  display: flex;
  gap: var(--spacing-lg);
  align-items: center;
  padding-bottom: var(--spacing-sm);
  border-bottom: 1px solid var(--color-border-light);
}

.entry-request-id {
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
}

.entry-result-count {
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
}

/* Result Highlighting */
.res-text { color: var(--color-text-primary); font-weight: 900;}
.high .res-val, .high.res-text { color: var(--color-warning-dark); font-weight: 900;}
.low .res-val, .low.res-text { color: var(--color-primary); font-weight: 900;}
.critical { background-color: var(--color-danger-light); font-weight: 900;}
.critical .res-val, .critical.res-text { color: var(--color-danger); font-weight: var(--font-weight-black); font-weight: 900;}

.empty-data-placeholder { color: var(--color-text-disabled); opacity: 0.5; }

.res-unit { color: var(--color-text-primary); font-style: italic; font-size: 12px; margin-left: 5px;}

/* Standard Modal Footer */
.modal-footer-standard {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-lg) var(--spacing-xl);
  background-color: var(--color-bg-secondary);
  border-top: 1px solid var(--color-border-secondary);
  gap: var(--spacing-md);
}

.footer-info {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
  font-style: italic;
}

.footer-actions {
  display: flex;
  gap: var(--spacing-sm);
}
</style>
