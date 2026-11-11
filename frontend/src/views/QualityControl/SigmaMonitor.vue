<template>
  <div class="sigma-monitor">
    <div class="page-header">
      <h2 class="page-title">Sigma Monitor</h2>
      <div class="header-actions">
        <el-select
          v-model="periodDays"
          placeholder="Period Days"
          style="width: 120px; margin-right: 10px"
          @change="loadDashboard"
        >
          <el-option label="7 days" :value="7" />
          <el-option label="14 days" :value="14" />
          <el-option label="30 days" :value="30" />
          <el-option label="60 days" :value="60" />
          <el-option label="90 days" :value="90" />
        </el-select>
        <el-button type="primary" @click="recalculateAll" :loading="calculating">
          <el-icon><Refresh /></el-icon>
          Recalculate All
        </el-button>
        <el-button type="success" @click="loadDashboard" :loading="loading">
          <el-icon><Refresh /></el-icon>
          Refresh
        </el-button>
      </div>
    </div>

    <el-card>
      <el-table
        :data="dashboardData"
        v-loading="loading"
        style="width: 100%"
        stripe
      >
        <el-table-column prop="analyte_name" label="Analyte (Test)" min-width="150">
          <template #default="{ row }">
            <el-link
              type="primary"
              @click="viewTrend(row)"
              :underline="false"
              style="cursor: pointer"
            >
              {{ row.analyte_name }}
            </el-link>
          </template>
        </el-table-column>
        <el-table-column prop="te_a" label="Total Error Allowance (TEa)" width="200" align="right">
          <template #default="{ row }">
            {{ row.te_a }} {{ row.te_a_unit }}
          </template>
        </el-table-column>
        <el-table-column prop="target_mean" label="Target Mean (μ)" width="200" align="right">
          <template #default="{ row }">
            {{ formatNumber(row.target_mean) }}
          </template>
        </el-table-column>
        <el-table-column prop="observed_mean" label="Obs. Mean (x̄)" width="200" align="right">
          <template #default="{ row }">
            {{ formatNumber(row.observed_mean) }}
          </template>
        </el-table-column>
        <el-table-column prop="observed_sd" label="Obs. SD (s)" width="150" align="right">
          <template #default="{ row }">
            {{ formatNumber(row.observed_sd) }}
          </template>
        </el-table-column>
        <el-table-column prop="sigma_metric" label="Sigma Metric (Σ)" width="200" align="right">
          <template #default="{ row }">
            <span :style="{ fontWeight: 'bold', color: getStatusColor(row.status?.color).text }">
              {{ formatNumber(row.sigma_metric, 2) }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="status_label" label="Status" width="120" align="center">
          <template #default="{ row }">
            <el-tag
              :type="getStatusColor(row.status?.color).type"
              size="small"
              effect="dark"
            >
              {{ row.status_label }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Data Quality" width="140" align="center">
          <template #default="{ row }">
            <template v-if="row.data_quality && row.data_quality.total_results">
              <el-tooltip
                effect="dark"
                placement="top"
              >
                <template #content>
                  <div style="line-height: 1.8">
                    <div><strong>Total QC Results:</strong> {{ row.data_quality.total_results }}</div>
                    <div><strong>Valid:</strong> {{ row.data_quality.valid_results }}</div>
                    <div><strong>Invalid:</strong> {{ row.data_quality.invalid_results }}</div>
                    <div v-if="row.data_quality.invalid_percentage">
                      <strong>Invalid Rate:</strong> {{ row.data_quality.invalid_percentage.toFixed(1) }}%
                    </div>
                  </div>
                </template>
                <el-tag
                  :type="getDataQualityType(row.data_quality.invalid_percentage)"
                  size="small"
                  effect="dark"
                >
                  <el-icon style="margin-right: 4px" v-if="row.data_quality.invalid_percentage > 20">
                    <Warning />
                  </el-icon>
                  {{ getDataQualityLabel(row.data_quality) }}
                </el-tag>
              </el-tooltip>
            </template>
            <span v-else style="color: var(--color-text-tertiary); font-size: var(--font-size-xs)">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="last_calculation" label="Last Calc." width="200">
          <template #default="{ row }">
            {{ formatDateTime(row.last_calculation) }}
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- Trend Chart Dialog -->
    <el-dialog
      v-model="trendDialogVisible"
      :title="`Sigma Metric Trend: ${selectedAnalyte?.analyte_name || ''}`"
      width="800px"
      @close="closeTrendDialog"
      @opened="handleDialogOpened"
    >
      <div v-if="trendLoading" style="text-align: center; padding: 40px">
        <el-icon class="is-loading" style="font-size: 24px"><Loading /></el-icon>
        <p>Loading trend data...</p>
      </div>
      <div v-else style="position: relative; height: 400px; min-height: 400px; width: 100%;">
        <canvas 
          v-if="trendData.length > 0"
          ref="trendChartCanvas" 
          style="width: 100%; height: 400px; display: block;"
        ></canvas>
        <el-empty v-else description="No trend data available" />
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { ref, onMounted, nextTick, watch, markRaw } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Refresh, Loading, Warning } from '@element-plus/icons-vue';
import api from '@/utils/axios';
import Chart from 'chart.js/auto';

export default {
  name: 'SigmaMonitor',
  components: {
    Refresh,
    Loading,
    Warning,
  },
  setup() {
    const loading = ref(false);
    const calculating = ref(false);
    const dashboardData = ref([]);
    const periodDays = ref(30);
    const trendDialogVisible = ref(false);
    const trendLoading = ref(false);
    const trendData = ref([]);
    const selectedAnalyte = ref(null);
    const trendChartCanvas = ref(null);
    let trendChart = null;

    const loadDashboard = async () => {
      loading.value = true;
      try {
        // Build params object - only include recalculate if it's true
        const params = {
          period_days: periodDays.value,
        };
        
        const response = await api.get('/sigma-metrics', {
          params: params,
        });
        
        console.log('Sigma Monitor API Response:', {
          dataCount: response.data.data?.length || 0,
          totalConfigs: response.data.total_configs,
          itemsWithData: response.data.items_with_data,
          itemsWithoutData: response.data.items_without_data,
          periodDays: response.data.period_days
        });
        
        dashboardData.value = response.data.data || [];
        
        // Show helpful message if no data
        if (dashboardData.value.length === 0) {
          ElMessage.warning('No TE_a configurations found. Please configure TE_a values for test parameters first.');
        } else if (response.data.items_without_data > 0) {
          ElMessage.info(`Found ${response.data.items_with_data} analytes with data and ${response.data.items_without_data} without data. Ensure QC parameters are linked and QC results exist.`);
        }
      } catch (error) {
        console.error('Error loading dashboard:', error);
        if (error.response?.data?.message) {
          ElMessage.error(`Failed to load Sigma Monitor: ${error.response.data.message}`);
        } else {
          ElMessage.error('Failed to load Sigma Monitor dashboard');
        }
      } finally {
        loading.value = false;
      }
    };

    const recalculateAll = async () => {
      try {
        await ElMessageBox.confirm(
          'This will recalculate Sigma metrics for all analytes. Continue?',
          'Confirm Recalculation',
          {
            confirmButtonText: 'Yes',
            cancelButtonText: 'Cancel',
            type: 'warning',
          }
        );

        calculating.value = true;
        const response = await api.post('/sigma-metrics/calculate-all', {
          period_days: periodDays.value,
        });
        
        ElMessage.success(
          `Calculation completed: ${response.data.summary.successful} successful, ${response.data.summary.failed} failed`
        );
        
        await loadDashboard();
      } catch (error) {
        if (error !== 'cancel') {
          console.error('Error recalculating:', error);
          ElMessage.error('Failed to recalculate Sigma metrics');
        }
      } finally {
        calculating.value = false;
      }
    };

    const viewTrend = async (row) => {
      selectedAnalyte.value = row;
      trendDialogVisible.value = true;
      trendLoading.value = true;
      trendData.value = []; // Clear previous data
      
      try {
        const response = await api.get(`/sigma-metrics/test-parameters/${row.test_parameter_id}/trend`, {
          params: {
            days: 90,
          },
        });
        
        console.log('Trend API response:', response.data); // Debug log
        
        trendData.value = response.data.data || [];
        
        console.log('Trend data updated:', {
          dataCount: trendData.value.length,
          dialogOpen: trendDialogVisible.value,
          loading: trendLoading.value,
        });
        
        // If dialog is already open, render chart now that data is loaded
        if (trendDialogVisible.value && trendData.value.length > 0) {
          await nextTick();
          await new Promise(resolve => setTimeout(resolve, 300));
          renderChartWhenReady();
        }
      } catch (error) {
        console.error('Error loading trend:', error);
        ElMessage.error('Failed to load trend data: ' + (error.response?.data?.message || error.message));
      } finally {
        trendLoading.value = false;
      }
    };

    const renderTrendChart = () => {
      if (!trendData.value.length || !trendChartCanvas.value) {
        console.warn('Cannot render chart:', {
          hasData: trendData.value.length > 0,
          hasCanvas: !!trendChartCanvas.value,
        });
        return;
      }

      if (trendChart) {
        trendChart.destroy();
        trendChart = null;
      }

      const ctx = trendChartCanvas.value.getContext('2d');
      if (!ctx) {
        console.error('Failed to get canvas context');
        return;
      }
      
      const dates = trendData.value.map(item => item.date);
      // Convert string values to numbers
      const sigmaValues = trendData.value.map(item => parseFloat(item.sigma_metric) || 0);
      
      console.log('Rendering chart with data:', { 
        dateCount: dates.length, 
        valueCount: sigmaValues.length,
        firstDate: dates[0],
        lastDate: dates[dates.length - 1],
        minValue: Math.min(...sigmaValues),
        maxValue: Math.max(...sigmaValues),
        sampleValues: sigmaValues.slice(0, 5)
      });

      try {
        // Check if Chart is available
        if (typeof Chart === 'undefined' || !Chart) {
          console.error('Chart.js is not loaded', typeof Chart);
          ElMessage.error('Chart.js library is not available. Please refresh the page.');
          return;
        }

        console.log('Creating Chart instance...', {
          ctxType: typeof ctx,
          ChartType: typeof Chart,
          ChartConstructor: typeof Chart,
          datesSample: dates.slice(0, 3),
          valuesSample: sigmaValues.slice(0, 3),
          canvasVisible: trendChartCanvas.value.offsetWidth > 0 && trendChartCanvas.value.offsetHeight > 0,
          canvasStyle: window.getComputedStyle(trendChartCanvas.value).display,
        });
        
        // Ensure canvas is visible before creating chart
        if (trendChartCanvas.value.offsetWidth === 0 || trendChartCanvas.value.offsetHeight === 0) {
          console.warn('Canvas has zero dimensions, forcing dimensions...');
          trendChartCanvas.value.style.width = '100%';
          trendChartCanvas.value.style.height = '800px';
          // Force a layout recalculation
          trendChartCanvas.value.offsetHeight;
        }
        
        const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();
        const successColor = getComputedStyle(document.documentElement).getPropertyValue('--color-success').trim();
        const warningColor = getComputedStyle(document.documentElement).getPropertyValue('--color-warning').trim();
        const dangerColor = getComputedStyle(document.documentElement).getPropertyValue('--color-danger').trim();

        trendChart = markRaw(new Chart(ctx, {
        type: 'line',
        data: {
          labels: dates,
          datasets: [
            {
              label: 'Sigma Metric',
              data: sigmaValues,
              borderColor: primaryColor,
              backgroundColor: primaryColor + '1A', // 10% opacity
              tension: 0.4,
              fill: true,
              pointRadius: 3,
              pointHoverRadius: 5,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            title: {
              display: true,
              text: 'Sigma Metric Trend (90 days)',
            },
            legend: {
              display: true,
              position: 'top',
            },
            tooltip: {
              mode: 'index',
              intersect: false,
            },
          },
          scales: {
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Sigma Metric',
              },
              grid: {
                color: function(context) {
                  if (context.tick.value >= 6) return successColor + '4D'; // 30% opacity
                  if (context.tick.value >= 4) return warningColor + '4D'; // 30% opacity
                  return dangerColor + '4D'; // 30% opacity
                },
              },
            },
            x: {
              title: {
                display: true,
                text: 'Date',
              },
            },
          },
        },
      }));
      
      // Chart created successfully
      console.log('Chart created successfully!', {
        chartId: trendChart.id,
        canvasWidth: trendChart.canvas?.width,
        canvasHeight: trendChart.canvas?.height,
        dataPointCount: trendChart.data.datasets[0].data.length,
        chartInstance: !!trendChart,
      });
      
      // Add annotation datasets for threshold lines (simpler approach)
      trendChart.data.datasets.push(
        {
          label: 'Excellent (≥6)',
          data: dates.map(() => 6),
          borderColor: successColor,
          borderWidth: 2,
          borderDash: [5, 5],
          pointRadius: 0,
          fill: false,
          tension: 0,
        },
        {
          label: 'Good (≥4)',
          data: dates.map(() => 4),
          borderColor: warningColor,
          borderWidth: 2,
          borderDash: [5, 5],
          pointRadius: 0,
          fill: false,
          tension: 0,
        }
      );
      
      trendChart.update();
      
      // Handle chart resize when dialog is resized
      setTimeout(() => {
        if (trendChart) {
          trendChart.resize();
        }
      }, 100);
      
      } catch (error) {
        console.error('Error creating chart:', error);
        console.error('Error stack:', error.stack);
        ElMessage.error('Failed to render chart: ' + error.message);
        trendChart = null;
      }
    };

    const renderChartWhenReady = async () => {
      console.log('=== renderChartWhenReady called ===', {
        hasData: trendData.value.length > 0,
        isLoading: trendLoading.value,
        dialogOpen: trendDialogVisible.value,
        hasCanvas: !!trendChartCanvas.value,
      });
      
      if (trendData.value.length === 0 || trendLoading.value) {
        console.log('Not ready yet, waiting...');
        return;
      }
      
      // Wait for DOM to update and canvas to be rendered
      await nextTick();
      await nextTick(); // Double nextTick for Element Plus dialog
      await new Promise(resolve => setTimeout(resolve, 300));
      
      // Retry logic - sometimes canvas takes longer to render
      let retries = 10;
      while (retries > 0 && !trendChartCanvas.value) {
        console.log(`Waiting for canvas... (${retries} retries left)`);
        await nextTick();
        await new Promise(resolve => setTimeout(resolve, 100));
        retries--;
      }
      
      // Additional check for canvas dimensions
      if (trendChartCanvas.value) {
        let dimensionRetries = 5;
        while (dimensionRetries > 0 && 
               (trendChartCanvas.value.offsetWidth === 0 || trendChartCanvas.value.offsetHeight === 0)) {
          console.log(`Waiting for canvas dimensions... (${dimensionRetries} retries left)`);
          await nextTick();
          await new Promise(resolve => setTimeout(resolve, 100));
          dimensionRetries--;
        }
      }
      
      if (trendChartCanvas.value && trendData.value.length > 0) {
        const canvas = trendChartCanvas.value;
        console.log('Canvas ready, rendering chart...', {
          canvasExists: !!canvas,
          canvasWidth: canvas.width || canvas.offsetWidth || canvas.clientWidth,
          canvasHeight: canvas.height || canvas.offsetHeight || canvas.clientHeight,
          canvasParent: canvas.parentElement?.tagName,
          canvasDisplay: window.getComputedStyle(canvas).display,
          canvasVisibility: window.getComputedStyle(canvas).visibility,
          ChartAvailable: typeof Chart !== 'undefined',
          ChartType: typeof Chart,
        });
        
        // Force canvas to be visible and have dimensions
        canvas.style.display = 'block';
        canvas.style.visibility = 'visible';
        canvas.style.width = '100%';
        canvas.style.height = '400px';
        
        // Additional wait to ensure styles are applied
        await nextTick();
        await new Promise(resolve => setTimeout(resolve, 100));
        
        console.log('Final canvas check before render:', {
          width: canvas.offsetWidth,
          height: canvas.offsetHeight,
          display: window.getComputedStyle(canvas).display,
        });
        
        renderTrendChart();
      } else {
        console.error('Canvas not ready after retries:', {
          hasCanvas: !!trendChartCanvas.value,
          hasData: trendData.value.length > 0,
          canvasElement: trendChartCanvas.value,
          ChartAvailable: typeof Chart !== 'undefined',
        });
        ElMessage.warning('Chart canvas not ready. Please try closing and reopening the dialog.');
      }
    };

    const handleDialogOpened = async () => {
      console.log('=== Dialog Opened Event ===', {
        hasData: trendData.value.length > 0,
        isLoading: trendLoading.value,
      });
      
      // If data is already loaded, render immediately
      if (trendData.value.length > 0 && !trendLoading.value) {
        console.log('Data already available, rendering chart...');
        await renderChartWhenReady();
      } else {
        console.log('Waiting for data to load...');
        // Data is still loading, will be handled by the viewTrend function
        // when API response completes
      }
    };

    const forceRenderChart = () => {
      console.log('Force render chart triggered');
      if (trendChartCanvas.value && trendData.value.length > 0) {
        renderTrendChart();
      } else {
        ElMessage.error('Canvas or data not available');
      }
    };

    const closeTrendDialog = () => {
      if (trendChart) {
        trendChart.destroy();
        trendChart = null;
      }
      trendData.value = [];
      selectedAnalyte.value = null;
    };

    const formatNumber = (value, decimals = 4) => {
      if (value === null || value === undefined) return '-';
      return Number(value).toFixed(decimals);
    };

    const formatDateTime = (value) => {
      if (!value) return '-';
      return new Date(value).toLocaleString();
    };

    const getStatusColor = (color) => {
      const successColor = getComputedStyle(document.documentElement).getPropertyValue('--color-success').trim();
      const warningColor = getComputedStyle(document.documentElement).getPropertyValue('--color-warning').trim();
      const dangerColor = getComputedStyle(document.documentElement).getPropertyValue('--color-danger').trim();
      const textTertiary = getComputedStyle(document.documentElement).getPropertyValue('--color-text-tertiary').trim();

      const colors = {
        green: { type: 'success', text: successColor || '#67C23A' },
        yellow: { type: 'warning', text: warningColor || '#E6A23C' },
        red: { type: 'danger', text: dangerColor || '#F56C6C' },
        gray: { type: 'info', text: textTertiary || '#909399' },
      };
      return colors[color] || colors.gray;
    };

    const getDataQualityType = (invalidPercentage) => {
      if (!invalidPercentage && invalidPercentage !== 0) return 'info';
      if (invalidPercentage < 10) return 'success';
      if (invalidPercentage < 20) return 'warning';
      if (invalidPercentage < 30) return 'warning';
      return 'danger';
    };

    const getDataQualityLabel = (dataQuality) => {
      if (!dataQuality || !dataQuality.total_results) return 'N/A';
      
      const invalidPct = dataQuality.invalid_percentage;
      if (invalidPct === null || invalidPct === undefined) {
        // If percentage not calculated, show valid/invalid counts
        return `${dataQuality.valid_results || 0}/${dataQuality.total_results || 0} valid`;
      }
      
      return `${invalidPct.toFixed(1)}% invalid`;
    };

    // Watch for dialog opening AND data availability - handles both scenarios
    watch([trendDialogVisible, () => trendData.value.length, () => trendLoading.value], 
      async ([isOpen, dataLength, isLoading]) => {
        console.log('Watch triggered:', { isOpen, dataLength, isLoading });
        if (isOpen && dataLength > 0 && !isLoading && !trendChart) {
          console.log('Watch: Conditions met, rendering chart...');
          await nextTick();
          await new Promise(resolve => setTimeout(resolve, 300));
          renderChartWhenReady();
        }
      },
      { immediate: false }
    );

    onMounted(() => {
      loadDashboard();
    });

    return {
      loading,
      calculating,
      dashboardData,
      periodDays,
      trendDialogVisible,
      trendLoading,
      trendData,
      selectedAnalyte,
      loadDashboard,
      recalculateAll,
      viewTrend,
      handleDialogOpened,
      renderChartWhenReady,
      forceRenderChart,
      closeTrendDialog,
      formatNumber,
      formatDateTime,
      getStatusColor,
      getDataQualityType,
      getDataQualityLabel,
      trendChartCanvas,
    };
  },
};
</script>

<style scoped>
.sigma-monitor {
  width: 100%;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-title {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.el-table {
  margin-top: 20px;
}

.trend-chart-container {
  width: 100%;
  height: 400px;
  position: relative;
}
</style>

