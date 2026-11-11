<template>
  <div class="delta-check-container">
    <!-- Header -->
    <div class="header-left">
      <h2>Delta Check Report</h2>
      <p>Laboratory test result comparison and violation tracking</p>
    </div>

    <!-- Filters -->
    <el-card class="filter-card">
      <div class="filter-row">
        <div class="header-left">
          <div class="filter-item">
            <label>Patient ID:</label>
            <el-input
              v-model="filters.patient_id"

              
              placeholder="Enter Patient ID"
              clearable
              @keyup.enter="loadDeltaChecks"
            />
          </div>
          <div class="filter-item">
            <label>Test:</label>
            <el-select
              v-model="filters.test_id"
              placeholder="Select Test"
              clearable
              @change="onFilterChange"
            >
              <el-option
                v-for="test in tests"
                :key="test.id"
                :label="test.name"
                :value="test.id"
              />
            </el-select>
          </div>
          <div class="filter-item">
            <label>Status:</label>
            <el-select
              v-model="filters.status"
              placeholder="All"
              clearable
              @change="onFilterChange"
            >
              <el-option label="All" value="" />
              <el-option label="Flagged" value="flagged" />
              <el-option label="Normal" value="normal" />
            </el-select>
          </div>
          <div class="filter-item">
            <label>From:</label>
            <el-date-picker
              v-model="filters.start_date"
              type="date"
              placeholder="Select date"
              format="MM/DD/YYYY"
              value-format="YYYY-MM-DD"
              @change="onFilterChange"
            />
          </div>
          <div class="filter-item">
            <label>To:</label>
            <el-date-picker
              v-model="filters.end_date"
              type="date"
              placeholder="Select date"
              format="MM/DD/YYYY"
              value-format="YYYY-MM-DD"
              @change="onFilterChange"
            />
          </div>
          <div class="filter-item">
            <el-button type="primary" @click="loadDeltaChecks">Apply</el-button>
          </div>
        </div>

        <div class="header-right">
          <el-button type="primary" :icon="Printer" @click="printReport">Print</el-button>
          <el-button :icon="Refresh" @click="refreshData">Refresh</el-button>
        </div>
      </div>
    </el-card>

    <!-- Content Tabs -->
    <el-card class="content-card">
      <el-tabs v-model="activeTab" class="delta-check-tabs">
        <el-tab-pane label="Delta Check Results" name="results">
          <!-- Data Table -->
          <div class="table-container">
            <el-table
              :data="deltaChecks"
              v-loading="loading"
              stripe
              border
              :header-cell-style="{ background: 'var(--color-bg-tertiary)', color: 'var(--color-text-secondary)' }"
            >
              <el-table-column prop="test_result.laboratory_request.patient.first_name" label="Patient Name" fixed="left">
                <template #default="{ row }">
                  {{ row.test_result?.laboratory_request?.patient?.first_name || 'N/A' }} {{ row.test_result?.laboratory_request?.patient?.last_name || '' }}
                </template>
              </el-table-column>
              <el-table-column prop="test.name" label="Test Name"/>
              <el-table-column prop="test_parameter.test_name" label="Parameter"/>
              <el-table-column prop="current_value" label="Current Value" align="center" />
              <el-table-column prop="previous_value" label="Previous Value" align="center" />
              <el-table-column prop="difference" label="Difference" align="center" />
              <el-table-column prop="percentage_difference" label="% Difference" align="center">
                <template #default="{ row }">
                  {{ row.percentage_difference ? row.percentage_difference.toFixed(2) + '%' : 'N/A' }}
                </template>
              </el-table-column>
              <el-table-column prop="status" label="Status" align="center">
                <template #default="{ row }">
                  <el-tag :type="row.status === 'flagged' ? 'danger' : 'success'">
                    {{ row.status }}
                  </el-tag>
                </template>
              </el-table-column>
              <el-table-column prop="remarks" label="Remarks"  />
              <el-table-column prop="recommendations" label="Recommendations" width="400">
                <template #default="{ row }">
                  <div v-if="row.recommendations" class="recommendations-cell">
                    <el-tag :type="getSeverityType(row.recommendations.severity)" size="small">
                      {{ row.recommendations.severity_display || row.recommendations.severity?.toUpperCase() }}
                    </el-tag>
                    <div class="recommendation-text">
                      {{ getRecommendationText(row.recommendations) }}
                    </div>
                    <div v-if="row.recommendations.action_required?.immediate" class="recommendation-steps">
                      <el-collapse>
                        <el-collapse-item title="View Steps" name="steps">
                          <div v-if="row.recommendations.action_required.immediate.steps?.length">
                            <ul>
                              <li v-for="(step, index) in row.recommendations.action_required.immediate.steps" :key="index">
                                {{ step }}
                              </li>
                            </ul>
                          </div>
                        </el-collapse-item>
                      </el-collapse>
                    </div>
                  </div>
                  <span v-else class="text-gray-400">No recommendations</span>
                </template>
              </el-table-column>
              <el-table-column prop="created_at" label="Date" >
                <template #default="{ row }">
                  {{ formatDate(row.created_at) }}
                </template>
              </el-table-column>
            </el-table>
          </div>
        </el-tab-pane>

        <el-tab-pane label="Global Recommendations" name="recommendations">
          <div class="recommendations-container">
            <el-card v-if="globalStatistics" class="recommendations-card">
              <template #header>
                <div class="card-header">
                  <span>Global Delta Check Analysis</span>
                  <el-button type="primary" size="small" @click="refreshRecommendations">Refresh</el-button>
                </div>
              </template>
              
              <div class="global-stats">
                <el-row :gutter="20">
                  <el-col :span="6">
                    <div class="stat-item">
                      <div class="stat-value">{{ globalStatistics.overall?.total_checks || 0 }}</div>
                      <div class="stat-label">Total Checks</div>
                    </div>
                  </el-col>
                  <el-col :span="6">
                    <div class="stat-item">
                      <div class="stat-value flagged">{{ globalStatistics.overall?.flagged_checks || 0 }}</div>
                      <div class="stat-label">Flagged Results</div>
                    </div>
                  </el-col>
                  <el-col :span="6">
                    <div class="stat-item">
                      <div class="stat-value">{{ globalStatistics.overall?.violation_rate || 0 }}%</div>
                      <div class="stat-label">Violation Rate</div>
                    </div>
                  </el-col>
                  <el-col :span="6">
                    <div class="stat-item">
                      <div class="stat-value normal">{{ globalStatistics.overall?.normal_rate || 0 }}%</div>
                      <div class="stat-label">Normal Rate</div>
                    </div>
                  </el-col>
                </el-row>
              </div>

              <div v-if="globalStatistics.recommendations?.length" class="recommendations-section">
                <h4>System Recommendations</h4>
                <el-alert
                  v-for="(rec, index) in globalStatistics.recommendations"
                  :key="index"
                  :title="rec.message"
                  :description="rec.action"
                  :type="rec.type === 'critical' ? 'error' : rec.type === 'warning' ? 'warning' : 'info'"
                  show-icon
                  class="recommendation-alert"
                />
              </div>

              <div v-if="globalStatistics.by_parameter" class="parameter-stats">
                <h4>Parameter-Specific Statistics</h4>
                <el-table :data="Object.entries(globalStatistics.by_parameter).slice(0, 10)" stripe>
                  <el-table-column prop="0" label="Parameter" />
                  <el-table-column prop="1.total" label="Total Tests" align="center" />
                  <el-table-column prop="1.flagged" label="Flagged" align="center" />
                  <el-table-column label="Violation Rate" align="center">
                    <template #default="{ row }">
                      <el-tag :type="row[1].violation_rate > 20 ? 'danger' : row[1].violation_rate > 10 ? 'warning' : 'success'">
                        {{ row[1].violation_rate?.toFixed(1) }}%
                      </el-tag>
                    </template>
                  </el-table-column>
                </el-table>
              </div>
            </el-card>
          </div>
        </el-tab-pane>

        <el-tab-pane label="Summary Statistics" name="summary">
          <div class="summary-container">
            <el-row :gutter="20">
              <el-col :span="6">
                <el-card class="summary-card">
                  <div class="summary-item">
                    <div class="summary-value">{{ totalCount }}</div>
                    <div class="summary-label">Total Checks</div>
                  </div>
                </el-card>
              </el-col>
              <el-col :span="6">
                <el-card class="summary-card">
                  <div class="summary-item">
                    <div class="summary-value flagged">{{ flaggedCount }}</div>
                    <div class="summary-label">Flagged Results</div>
                  </div>
                </el-card>
              </el-col>
              <el-col :span="6">
                <el-card class="summary-card">
                  <div class="summary-item">
                    <div class="summary-value normal">{{ normalCount }}</div>
                    <div class="summary-label">Normal Results</div>
                  </div>
                </el-card>
              </el-col>
              <el-col :span="6">
                <el-card class="summary-card">
                  <div class="summary-item">
                    <div class="summary-value">{{ violationRate }}%</div>
                    <div class="summary-label">Violation Rate</div>
                  </div>
                </el-card>
              </el-col>
            </el-row>
          </div>
        </el-tab-pane>
      </el-tabs>
    </el-card>

    <!-- Footer -->
    <div class="delta-check-footer">
      <span class="count">COUNT: {{ totalCount }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { ElMessage } from 'element-plus';
import { Printer, Refresh } from '@element-plus/icons-vue';
import api from '@/utils/axios';
import { format } from 'date-fns';

// Reactive data
const loading = ref(false);
const deltaChecks = ref([]);
const tests = ref([]);
const activeTab = ref('results');
const globalStatistics = ref(null);

// Filters
const filters = ref({
    patient_id: '',
    test_id: '',
    status: '',
    start_date: '',
    end_date: '',
});

// Computed properties
const totalCount = computed(() => deltaChecks.value.length);
const flaggedCount = computed(() => deltaChecks.value.filter(dc => dc.status === 'flagged').length);
const normalCount = computed(() => deltaChecks.value.filter(dc => dc.status === 'normal').length);
const violationRate = computed(() => {
    if (totalCount.value === 0) return 0;
    return ((flaggedCount.value / totalCount.value) * 100).toFixed(1);
});

// Methods
const loadDeltaChecks = async () => {
    loading.value = true;
    try {
        const params = {
            ...filters.value,
        };
        
        // Remove empty values
        Object.keys(params).forEach(key => {
            if (params[key] === '' || params[key] === null) {
                delete params[key];
            }
        });

        const response = await api.get('/management-report/delta-checks', { params });
        deltaChecks.value = response.data.data || [];
        globalStatistics.value = response.data.global_statistics || null;
        
        ElMessage.success(`Delta checks loaded successfully - ${totalCount.value} total checks`);
    } catch (err) {
        console.error('Error fetching delta checks:', err);
        ElMessage.error('Failed to load delta checks: ' + (err.response?.data?.message || err.message));
        deltaChecks.value = [];
    } finally {
        loading.value = false;
    }
};

const loadTests = async () => {
    try {
        const response = await api.get('/tests');
        tests.value = response.data.data || [];
    } catch (err) {
        console.error('Error fetching tests:', err);
    }
};

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM d, yyyy h:mm a');
};

const onFilterChange = () => {
    loadDeltaChecks();
};

const refreshData = () => {
    loadDeltaChecks();
};

const printReport = () => {
    if (deltaChecks.value.length === 0) {
        ElMessage.warning('No data to print');
        return;
    }
    window.print();
};

const getSeverityType = (severity) => {
    const severityMap = {
        'critical': 'danger',
        'high': 'warning',
        'moderate': 'warning',
        'low': 'success'
    };
    return severityMap[severity] || 'info';
};

const getRecommendationText = (recommendations) => {
    if (!recommendations) return 'No recommendations';
    
    // Handle new database-driven structure
    if (recommendations.action_required?.immediate?.immediate) {
        return recommendations.action_required.immediate.immediate;
    }
    
    // Handle old structure for backward compatibility
    if (recommendations.action_required?.immediate) {
        return recommendations.action_required.immediate;
    }
    
    // Fallback
    return 'No specific action required';
};

const refreshRecommendations = async () => {
    try {
        const params = { ...filters.value };
        Object.keys(params).forEach(key => {
            if (params[key] === '' || params[key] === null) {
                delete params[key];
            }
        });

        const response = await api.get('/management-report/delta-checks/statistics', { params });
        globalStatistics.value = response.data;
    } catch (err) {
        console.error('Error fetching global statistics:', err);
        ElMessage.error('Failed to load global statistics');
    }
};

// Lifecycle
onMounted(() => {
    loadTests();
    loadDeltaChecks();
});
</script>

<style scoped>
.delta-check-container {
  display: grid;
}

.header-left h2 {
  margin: 0 0 8px 0;
  color: var(--color-text-primary);
  font-size: 24px;
}

.header-left p {
  margin: 0;
  color: var(--color-text-secondary);
  font-size: 13px;
}

.header-right {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

.header-left {
  display: flex;
  gap: 10px;
  justify-content: flex-start;
}

.filter-card {
  margin-bottom: 20px;
}

.filter-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: space-between;
}

.filter-item {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 300px;
}

.filter-item label {
  font-weight: 500;
  color: var(--color-text-secondary);
  white-space: nowrap;
}

.content-card {
  min-height: 500px;
}

.delta-check-tabs {
  margin-top: 10px;
}

.table-container {
  overflow-x: auto;
}

.summary-container {
  padding: 20px;
}

.summary-card {
  text-align: center;
}

.summary-item {
  padding: 20px;
}

.summary-value {
  font-size: 32px;
  font-weight: bold;
  color: var(--color-primary);
  margin-bottom: 8px;
}

.summary-value.flagged {
  color: var(--color-danger);
}

.summary-value.normal {
  color: var(--color-success);
}

.summary-label {
  font-size: 13px;
  color: var(--color-text-secondary);
}

.delta-check-footer {
  background: var(--color-bg-primary);
  padding: 10px 20px;
  border-radius: var(--border-radius-xl);
  margin-top: 20px;
  box-shadow: var(--shadow-sm);
}

.count {
  font-weight: 500;
  color: var(--color-text-secondary);
}

/* Recommendations styling */
.recommendations-cell {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.recommendation-text {
  font-size: 12px;
  color: var(--color-text-secondary);
  line-height: 1.4;
  font-weight: 500;
}

.recommendation-steps {
  margin-top: 8px;
}

.recommendation-steps ul {
  margin: 8px 0;
  padding-left: 16px;
}

.recommendation-steps li {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin-bottom: 4px;
  line-height: 1.3;
}

.recommendations-container {
  padding: 20px;
}

.recommendations-card {
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.global-stats {
  margin-bottom: 20px;
}

.stat-item {
  text-align: center;
  padding: 15px;
  background: var(--color-bg-secondary);
  border-radius: var(--border-radius-xl);
}

.stat-value {
  font-size: 24px;
  font-weight: bold;
  color: var(--color-primary);
  margin-bottom: 5px;
}

.stat-value.flagged {
  color: var(--color-danger);
}

.stat-value.normal {
  color: var(--color-success);
}

.stat-label {
  font-size: 12px;
  color: var(--color-text-secondary);
}

.recommendations-section {
  margin-bottom: 20px;
}

.recommendations-section h4 {
  margin-bottom: 15px;
  color: var(--color-text-primary);
}

.recommendation-alert {
  margin-bottom: 10px;
}

.parameter-stats {
  margin-top: 20px;
}

.parameter-stats h4 {
  margin-bottom: 15px;
  color: var(--color-text-primary);
}

/* Print styles */
@media print {
  .delta-check-container {
    background: var(--color-bg-primary);
    padding: 0;
  }
  
  .header-right,
  .filter-card,
  .delta-check-tabs .el-tabs__header {
    display: none;
  }
  
  .el-table {
    font-size: 12px;
  }
  
  .el-table .el-table__header th {
    background: var(--color-bg-secondary) !important;
    color: var(--color-text-secondary) !important;
  }
}

/* Responsive design */
@media (max-width: 768px) {
  .filter-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }
  
  .filter-item {
    width: 100%;
  }
  
  .filter-item label {
    min-width: 80px;
  }
}
</style>
