<template>
  <div class="census-report-container">
    <!-- Header -->
    <div class="tat-header">
      <div class="header-left">
        <h2>Census Report </h2>
        <p> Test count statistics by day and month</p>
      </div>
      <div class="header-right">
        <div class="filter-row">
          <div class="filter-item">
            <label>Test Category: </label>
            <el-select v-model="selectedCategory" placeholder="Test Category" clearable @change="fetchData">
              <el-option v-for="category in testCategories" :key="category.name" :label="category.name" :value="category.name"></el-option>
            </el-select>
          </div>
          <div class="filter-item"> 
            <label>Month: </label>
            <el-date-picker
              v-model="selectedMonth"
              type="month"
              placeholder="Select Month"
              @change="fetchData">
            </el-date-picker>
          </div>
        </div>
      </div>
    </div>
    <!-- Summary Cards -->
    <el-row :gutter="20" class="summary-cards">
      <el-col :span="6">
        <el-card class="summary-card text-center">
          <div class="card-header">
            <span>Total Accessions</span>
          </div>
          <div class="card-body">
            <div class="value">{{ summary.totalAccessions.value }}</div>
            <div class="trend up">
                <i class="el-icon-top"></i>
                <span>{{ summary.totalAccessions.percentage_change }}% vs Last Month</span>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="summary-card text-center">
          <div class="card-header">
            <span>Send-Out Rate</span>
          </div>
          <div class="card-body">
            <div class="value orange">{{ summary.sendOutRate.value }}%</div>
            <div class="subtitle">
              {{ summary.sendOutRate.samples }} Samples • Target <2% 
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="summary-card text-center">
          <div class="card-header">
            <span>Processed By Lab</span>
          </div>
          <div class="card-body">
            <div class="value primary">{{ summary.processedByLab.value }}</div>
            <div class="trend up">
                <i class="el-icon-top"></i>
                <span>{{ summary.processedByLab.percentage_change }}% vs Last Month</span>
            </div>
          </div>
        </el-card>
      </el-col>
       <el-col :span="6">
        <el-card class="summary-card text-center">
          <div class="card-header">
            <span>Re-runs</span>
          </div>
          <div class="card-body">
            <div class="value red">{{ summary.reruns.value }}</div>
            <div class="subtitle red">
              {{ summary.reruns.percentage }}% of Total Samples Processed
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Main Content -->
    <el-card class="main-content-card">
      <el-tabs v-model="activeTab" tab-position="top" class="main-tabs">
        <el-tab-pane label="KPI Dashboard" name="kpi">
          <div class="kpi-dashboard">
            <div class="main-chart">
              <h3>Test Category Trends</h3>
              <canvas id="monthlyTestTrendsChart" height="120"></canvas>
            </div>
            <div class="comparison-chart">
              <h3>Comparison Trend</h3>
              <canvas id="comparisonChart" height="120"></canvas>
            </div>
          </div>
        </el-tab-pane>
        <el-tab-pane label="Detailed Table" name="table">
          <div class="detailed-table-section">
            <h4>{{ detailedTable.month }}</h4>
            <el-table :data="detailedTable.data" style="width: 100%" class="detailed-table" border stripe>
              <el-table-column prop="test_name" label="Test Parameter" fixed width="300"></el-table-column>
              <el-table-column
                v-for="header in detailedTable.headers"
                :key="header.prop"
                :prop="header.prop"
                :label="header.label"
                align="right"
                >
              </el-table-column>
            </el-table>
            <div class="detailed-summary">
                <h4>Summary</h4>
                <p><strong>Processed By Lab:</strong> {{ detailedTable.summary.processed_by_lab }}</p>
                <p><strong>Send out:</strong> {{ detailedTable.summary.send_out }}</p>
                <p><strong>Wastage:</strong> {{ detailedTable.summary.wastage }}</p>
                <p><strong>Reruns:</strong> {{ detailedTable.summary.reruns }}</p>
            </div>
          </div>
        </el-tab-pane>
        <el-tab-pane label="Comparison View" name="comparison">
          <div class="comparison-view">
            <h3>Comparison View</h3>
            <p>This feature is under development.</p>
          </div>
        </el-tab-pane>
      </el-tabs>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, watch } from 'vue';
import Chart from 'chart.js/auto';
import api from '@/utils/axios';

const summary = ref({
  totalAccessions: { value: 0, percentage_change: 0 },
  sendOutRate: { value: 0, samples: 0 },
  processedByLab: { value: 0, percentage_change: 0 },
  reruns: { value: 0, percentage: 0 },
});

const monthlyTestTrends = ref({
  series: [],
  categories: [],
});

const detailedTable = ref({
    headers: [],
    data: [],
    summary: {},
    month: ''
});

const comparisonData = ref({
  labels: [],
  processed: [],
  sendOut: [],
});
const activeTab = ref('kpi');
const testCategories = ref([]);
const selectedCategory = ref(null);
const selectedMonth = ref(new Date());

let mainChart = null;
let comparisonChart = null;

const createMainChart = () => {
  const ctx = document.getElementById('monthlyTestTrendsChart')?.getContext('2d');
  if (!ctx) return;
  mainChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: monthlyTestTrends.value.categories,
      datasets: monthlyTestTrends.value.series.map(s => ({
        label: s.name,
        data: s.data,
        borderColor: s.color,
        borderWidth: 1,
        backgroundColor: s.color,        
        barThickness: 8,
        tension: 0.4,
      }))
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
       plugins: {
        legend: {
          position: 'top',
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          precision: 0
        }
      }
    }
  });
};

const createComparisonChart = () => {
  const ctx = document.getElementById('comparisonChart')?.getContext('2d');
  if (!ctx) return;
  
  const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();
  const warningColor = getComputedStyle(document.documentElement).getPropertyValue('--color-warning').trim();

  comparisonChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: comparisonData.value.labels,
      datasets: [
        {
          label: 'Processed by Lab',
          data: comparisonData.value.processed,
          tension: 0.4,
          fill: false,
          borderColor: primaryColor,
          backgroundColor: primaryColor,
        },
        {
          label: 'Send-Out',
          data: comparisonData.value.sendOut,
          tension: 0.4,
          fill: false,
          borderColor: warningColor,
          backgroundColor: warningColor,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          position: 'top',
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          precision: 0
        }
      }
    }
  });
};

const renderMainChart = async () => {
    await nextTick();
    if (mainChart) {
        mainChart.destroy();
    }
    createMainChart();

    if (comparisonChart) {
        comparisonChart.destroy();
    }
    createComparisonChart();
};

watch(activeTab, (newValue) => {
  if (newValue === 'kpi') {
    renderMainChart();
  }
});

const fetchData = async () => {
  try {
    const params = {
        test_category: selectedCategory.value,
    };

    if (selectedMonth.value) {
        const monthDate = new Date(selectedMonth.value);
        const year = monthDate.getFullYear();
        const month = monthDate.getMonth();

        const startDate = new Date(year, month, 1);
        const endDate = new Date(year, month + 1, 0);

        const formatDate = (date) => {
            const y = date.getFullYear();
            const m = (date.getMonth() + 1).toString().padStart(2, '0');
            const d = date.getDate().toString().padStart(2, '0');
            return `${y}-${m}-${d}`;
        };
        
        const formatMonth = (date) => {
            const y = date.getFullYear();
            const m = (date.getMonth() + 1).toString().padStart(2, '0');
            return `${y}-${m}`;
        }

        params.start_date = formatDate(startDate);
        params.end_date = formatDate(endDate);
        params.month = formatMonth(monthDate);
    }

    const response = await api.get('/management-report/census', { params });
    const data = response.data;

    summary.value.totalAccessions = data.total_accessions;
    summary.value.sendOutRate = data.send_out_rate;
    summary.value.processedByLab = data.processed_by_lab;
    summary.value.reruns = data.reruns;
    monthlyTestTrends.value = data.monthly_test_trends;
    detailedTable.value = data.detailed_table;
    testCategories.value = data.test_categories;
    comparisonData.value = data.comparison_data;

    await nextTick();

    // Clear existing charts
    if (mainChart) mainChart.destroy();
    if (comparisonChart) comparisonChart.destroy();

    // Initial chart render
    if (activeTab.value === 'kpi') {
        renderMainChart();
    }
    createComparisonChart();

  } catch (error) {
    console.error('Failed to fetch census data:', error);
  }
};

onMounted(() => {
  fetchData();
});

</script>

<style scoped>
.census-report-container {
  width: 100%;
  display: grid;
}

.tat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  background: var(--color-bg-primary);
  padding: 20px;
  border-radius: var(--border-radius-md);
  box-shadow: var(--shadow-sm);
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
}

.filter-row {
  display: flex;
  align-items: center;
  gap: 20px;
  flex-wrap: wrap;
}

.filter-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 300px;
}

.filter-item label {
  font-weight: 500;
  color: var(--color-text-primary);
  white-space: nowrap;
}

.summary-cards {
  margin-bottom: 0px;
}

.summary-card .card-header {
  font-weight: bold;
}

.summary-card.text-center .card-body {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.summary-card .value {
  font-size: 2.5em;
  font-weight: bold;
  line-height: 1;
}

.summary-card .value.orange {
  color: var(--color-warning);
}

.summary-card .value.red {
  color: var(--color-danger);
}

.summary-card .value.primary {
  color: var(--color-primary);
}

.summary-card .trend {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 10px;
}

.summary-card .trend.up {
  color: var(--color-success);
}

.summary-card .trend.down {
  color: var(--color-danger);
}

.summary-card .subtitle {
    margin-top: 10px;
    color: var(--color-text-tertiary);
}

.summary-card .subtitle.red {
    color: var(--color-danger);
}
.summary-card.subtitle.orange {
    color: var(--color-warning);
}
.summary-card.subtitle.primary {
    color: var(--color-primary);
}

.main-content-card {
  margin-top: 10px;
}

.main-tabs {
  min-height: 400px; /* Adjust as needed */
}
.detailed-table-section, .comparison-view {
  width: 100%;
  padding: 0 20px;
}

.kpi-dashboard {
  display: flex;
  flex-direction: grid;
  align-content: space-between;
  width: 100%;
  padding: 0 20px;
  gap: 20px;
}

.kpi-dashboard h3 {
  margin-top: 0;
  margin-bottom: 20px;
}

.kpi-dashboard .main-chart {
  width: 100%;
  min-height: 500px;
  height: auto;
  flex: 1;
}

.kpi-dashboard .comparison-chart {
  width: 100%;
  flex: 1;
  height: auto;
}

.detailed-table-section {
  width: 100%;
  overflow-x: auto;
  display: block;
}

.detailed-table {
  width: 100%;
  max-width: 100%;
  table-layout: fixed;
}

.detailed-summary {
  margin-top: 20px;
  padding: 20px;
  background-color: var(--color-bg-secondary);
  border-radius: var(--border-radius-md);
}

@media (min-width: 1200px) {
  .detailed-table-section {
    padding: 0 20px;
  }
}

.detailed-summary h4 {
    margin-top: 0;
}

.comparison-view h3 {
  margin-top: 0;
}
</style>

