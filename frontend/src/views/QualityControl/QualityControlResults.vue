<template>
  <div class="quality-control-results">
    <div class="page-header">
      <div class="title-section">
        <h2 class="page-title">Quality Control Results</h2>
        <p class="page-subtitle" v-if="qualityControlTestParameter && qualityControlTestParameter.test_parameter">
          {{ qualityControlTestParameter.quality_control?.equipment ? `${qualityControlTestParameter.quality_control.equipment.manufacturer} - ${qualityControlTestParameter.quality_control.equipment.model}` : 'Equipment' }} 
          ({{ qualityControlTestParameter.quality_control?.equipment?.serial_number || 'N/A' }}) - 
          {{ qualityControlTestParameter.quality_control.control_name }} (Lot: {{ qualityControlTestParameter.quality_control.lot_number }})
          - Test: {{ qualityControlTestParameter.test_parameter.test_name }}
          ({{ qualityControlTestParameter.test_parameter.unit }})
        </p>
         <p class="page-subtitle" v-else-if="qualityControlTestParameter">
          {{ qualityControlTestParameter.quality_control?.equipment ? `${qualityControlTestParameter.quality_control.equipment.manufacturer} - ${qualityControlTestParameter.quality_control.equipment.model}` : 'Equipment' }} 
          ({{ qualityControlTestParameter.quality_control?.equipment?.serial_number || 'N/A' }}) - 
          {{ qualityControlTestParameter.quality_control.control_name }} (Lot: {{ qualityControlTestParameter.quality_control.lot_number }})
        </p>
      </div>
      <div class="header-actions">
        <el-switch
          v-model="showInvalidOnly"
          active-text="Show Invalid Only"
          class="mr-4"
        />
        <el-button type="primary" @click="openDialog()" :disabled="!qualityControlTestParameter">
          <el-icon><Plus /></el-icon>
          Add Result
        </el-button>
      </div>
    </div>

    <div class="qc-container">
      <el-card class="mb-4">
        <template #header>
          <div class="card-header">
            <span>
              Levey-Jennings Chart
               <template v-if="qualityControlTestParameter && qualityControlTestParameter.test_parameter">
                - {{ qualityControlTestParameter.test_parameter.test_name }}
                ({{ qualityControlTestParameter.test_parameter.unit }})
              </template>
            </span>
          </div>
        </template>
        <div style="height: 400px;">
          <canvas ref="chartCanvas"></canvas>
        </div>
      </el-card>

      <el-card>
         <template #header>
           <div class="card-header">
             <span>Raw Results</span>
           </div>
         </template>
        <el-table
          :data="filteredResults"
          v-loading="loading"
          style="width: 100%"
        >
          <el-table-column prop="run_date" label="Run Date">
            <template #default="{ row }">
              {{ formatDate(row.run_date) }}
            </template>
          </el-table-column>
          
          <el-table-column prop="run_time" label="Run Time">
            <template #default="{ row }">
              {{ formatTime(row.run_time) }}
            </template>
          </el-table-column>
          
          <el-table-column 
            prop="result_value" 
            :label="qualityControlTestParameter && qualityControlTestParameter.test_parameter ? 
              `${qualityControlTestParameter.test_parameter.test_name} (${qualityControlTestParameter.test_parameter.unit})` : 
              'Result'"
          />
          
          <el-table-column prop="operator.name" label="Operator" />
          
          <el-table-column prop="westgard_violations" label="Westgard Rules">
            <template #default="{ row }">
              <el-tag
                v-for="violation in row.westgard_violations"
                :key="violation"
                type="danger"
                size="small"
                class="mr-1"
              >
                {{ violation }}
              </el-tag>
            </template>
          </el-table-column>
          
          <el-table-column prop="is_valid" label="Status">
            <template #default="{ row }">
              <el-tag :type="row.is_valid ? 'success' : 'danger'" size="small">
                {{ row.is_valid ? 'Valid' : 'Invalid' }}
              </el-tag>
            </template>
          </el-table-column>
          
          <el-table-column prop="remarks" label="Remarks" />
          
          <el-table-column label="Actions" width="260">
            <template #default="{ row }">
              <el-button-group>
                <el-button
                  type="primary"
                  :icon="Edit"
                  size="small"
                  @click="openDialog(row)"
                > <el-icon><Edit /></el-icon>
                </el-button>
                <el-button
                  type="success"
                  :icon="Check"
                  size="small"
                  @click="acceptResult(row)"
                  :disabled="row.is_valid"
                > <el-icon><Check /></el-icon>
                </el-button>
                <el-button
                  type="warning"
                  :icon="Close"
                  size="small"
                  @click="rejectResult(row)"
                  :disabled="!row.is_valid"
                > <el-icon><Close /></el-icon>
                </el-button>
                <el-button
                  type="info"
                  size="small"
                  @click="openCorrectiveActionDialog(row)"
                  title="Document Corrective Action"
                > CA
                </el-button>
                <el-button
                  type="danger"
                  :icon="Delete"
                  size="small"
                  @click="confirmDelete(row)"
                > <el-icon><Delete /></el-icon>
                </el-button>
              </el-button-group>
            </template>
          </el-table-column>
        </el-table>
        <el-pagination
          class="mt-4"
          background
          layout="total, sizes, prev, pager, next, jumper"
          :total="totalResults"
          :page-sizes="[10, 20, 50, 100]"
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </el-card>
    </div>

    <!-- Add/Edit Dialog -->
    <el-dialog
      v-model="dialog"
      :title="formTitle"
      width="600px"
    >
      <el-form :model="editedItem" label-width="120px">
        <el-form-item label="Result Value">
          <el-input-number
            v-model="editedItem.result_value"
            :precision="4"
            :step="0.0001"
            style="width: 100%"
          />
        </el-form-item>
        
        <el-form-item label="Run Date">
          <el-date-picker
            v-model="editedItem.run_date"
            type="date"
            placeholder="Select date"
            style="width: 100%"
          />
        </el-form-item>
        
        <el-form-item label="Run Time">
          <el-time-picker
            v-model="editedItem.run_time"
            placeholder="Select time"
            style="width: 100%"
          />
        </el-form-item>
        
        <el-form-item label="Operator">
          <el-select 
            v-model="editedItem.operator_id"
            style="width: 100%"
          >
            <el-option
              v-for="user in users"
              :key="user.id"
              :label="user.name"
              :value="user.id"
            />
          </el-select>
        </el-form-item>
        
        <el-form-item label="Remarks">
          <el-input
            v-model="editedItem.remarks"
            type="textarea"
            :rows="3"
          />
        </el-form-item>
      </el-form>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="closeDialog">Cancel</el-button>
          <el-button type="primary" @click="save">Save</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- Delete Confirmation Dialog -->
    <el-dialog
      v-model="deleteDialog"
      title="Delete Result"
      width="400px"
    >
      <p>Are you sure you want to delete this result?</p>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="deleteDialog = false">Cancel</el-button>
          <el-button type="danger" @click="deleteItem">Delete</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- Corrective Action Dialog -->
    <el-dialog
      v-model="correctiveActionDialog"
      title="Document Corrective Action"
      width="700px"
      :close-on-click-modal="false"
    >
      <el-form :model="correctiveActionForm" :rules="correctiveActionRules" ref="correctiveActionFormRef" label-width="140px">
        <el-alert 
          type="warning" 
          :closable="false"
          class="mb-4"
          title="QC Violation Detected"
        >
          <template #default>
            <p><strong>Westgard Rule Violations:</strong> {{ selectedQCResult?.westgard_violations?.join(', ') || 'N/A' }}</p>
            <p><strong>Result Value:</strong> {{ selectedQCResult?.result_value }} {{ qualityControlTestParameter?.test_parameter?.unit }}</p>
            <p><strong>Run Date:</strong> {{ selectedQCResult?.run_date }} {{ selectedQCResult?.run_time }}</p>
          </template>
        </el-alert>

        <el-form-item label="Rule Violated" prop="rule_violated">
          <el-select 
            v-model="correctiveActionForm.rule_violated" 
            style="width: 100%"
            placeholder="Select primary violation"
          >
            <el-option
              v-for="rule in ['1-3s', '2-2s', 'R-4s', '4-1s', '10x', '1-2s']"
              :key="rule"
              :label="rule"
              :value="rule"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="Suspected Error Type" prop="suspected_error">
          <el-select 
            v-model="correctiveActionForm.suspected_error" 
            style="width: 100%"
            placeholder="Select error type"
          >
            <el-option label="Random Error" value="Random Error" />
            <el-option label="Systematic Error" value="Systematic Error" />
            <el-option label="Trend/Shift" value="Trend/Shift" />
            <el-option label="Instrument Malfunction" value="Instrument Malfunction" />
            <el-option label="Reagent Issue" value="Reagent Issue" />
            <el-option label="Calibration Issue" value="Calibration Issue" />
            <el-option label="Operator Error" value="Operator Error" />
            <el-option label="Other" value="Other" />
          </el-select>
        </el-form-item>

        <el-form-item label="Investigation Steps" prop="investigation">
          <el-input
            v-model="correctiveActionForm.investigation"
            type="textarea"
            :rows="5"
            placeholder="Document detailed investigation steps taken (e.g., Checked reagent levels, Ran new QC material, Performed instrument maintenance, reviewed calibration data)"
          />
        </el-form-item>

        <el-form-item label="Resolution (Optional)" prop="resolution">
          <el-input
            v-model="correctiveActionForm.resolution"
            type="textarea"
            :rows="4"
            placeholder="How was the issue resolved? (e.g., New reagent installed. QC passed on repeat run.)"
          />
        </el-form-item>
      </el-form>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="correctiveActionDialog = false">Cancel</el-button>
          <el-button type="primary" @click="saveCorrectiveAction">Save Corrective Action</el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, markRaw, watch } from 'vue';
import { useRoute } from 'vue-router';
import { format, parseISO } from 'date-fns';
import Chart from 'chart.js/auto';
import api from '@/utils/axios';
import { Plus, Edit, Delete, Check, Close } from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox } from 'element-plus';

export default {
  name: 'QualityControlResults',
  props: {
    qcTestParameterId: {
      type: [String, Number],
      required: true
    }
  },
  setup(props) {
    const route = useRoute();
    const chartCanvas = ref(null);
    const chart = ref(null);
    const dialog = ref(false);
    const deleteDialog = ref(false);
    const correctiveActionDialog = ref(false);
    const correctiveActionFormRef = ref(null);
    const selectedQCResult = ref(null);
    const loading = ref(false);
    const editedIndex = ref(-1);
    const qualityControlTestParameter = ref(null);
    const results = ref([]);
    const users = ref([]);
    const ljData = ref(null); // To store the Levey-Jennings data
    const currentPage = ref(1); // Current page for pagination
    const pageSize = ref(10); // Items per page for pagination
    const totalResults = ref(0); // Total number of results for pagination
    const showInvalidOnly = ref(false);

    const editedItem = ref({
      result_value: '',
      run_date: '',
      run_time: '',
      operator_id: '',
      remarks: '',
      quality_control_test_parameter_id: props.qcTestParameterId,
    });

    const defaultItem = {
      result_value: '',
      run_date: '',
      run_time: '',
      operator_id: '',
      remarks: '',
      quality_control_test_parameter_id: props.qcTestParameterId,
      unit: '',
    };

    const correctiveActionForm = ref({
      rule_violated: '',
      suspected_error: '',
      investigation: '',
      resolution: '',
    });

    const correctiveActionRules = {
      rule_violated: [{ required: true, message: 'Please select the violated rule', trigger: 'change' }],
      suspected_error: [{ required: true, message: 'Please select suspected error type', trigger: 'change' }],
      investigation: [{ required: true, message: 'Please document investigation steps', trigger: 'blur' }],
      resolution: [{ required: false, message: '', trigger: 'blur' }],
    };

    const formTitle = computed(() => {
      return editedIndex.value === -1 ? 'New Result' : 'Edit Result';
    });

    const filteredResults = computed(() => {
      if (!showInvalidOnly.value) {
        return results.value;
      }
      return results.value.filter(result => !result.is_valid);
    });

    const filteredChartData = computed(() => {
      if (!ljData.value) return null;

      const filteredData = { ...ljData.value };
      if (showInvalidOnly.value) {
        const newResults = [];
        const newDates = [];

        for (let i = 0; i < ljData.value.results.length; i++) {
          if (!ljData.value.is_valid[i]) {
            newResults.push(ljData.value.results[i]);
            newDates.push(ljData.value.dates[i]);
          }
        }
        filteredData.results = newResults;
        filteredData.dates = newDates;
      }

      return filteredData;
    });

    const fetchQualityControlTestParameter = async (id) => {
      try {
        console.log(`Fetching quality control test parameter for ID: ${id}`);
        const response = await api.get(`/quality-control-test-parameters/${id}`);
        qualityControlTestParameter.value = response.data;
        console.log('QualityControlTestParameter fetched:', response.data);
      } catch (error) {
        ElMessage.error('Error fetching quality control test parameter details.');
        qualityControlTestParameter.value = null;
      }
    };

    const fetchResults = async (id) => {
      loading.value = true;
      try {
        console.log(`Fetching raw results for ID: ${id}, page: ${currentPage.value}, per_page: ${pageSize.value}`);
        const response = await api.get(`/quality-control-test-parameters/${id}/results`, {
          params: {
            page: currentPage.value,
            per_page: pageSize.value,
          },
        });
        results.value = response.data.data; // The actual results array
        totalResults.value = response.data.total; // Total number of results
        console.log('Raw results fetched:', response.data);
      } catch (error) {
        ElMessage.error('Error fetching raw results.');
        results.value = [];
        totalResults.value = 0;
      } finally {
        loading.value = false;
      }
    };

    const fetchLeveyJenningsData = async (id) => {
      try {
        if (!id) {
          console.warn('Quality Control Test Parameter ID is missing for LJ data fetch.');
          ljData.value = null; // Clear old data if ID is missing
          return;
        }
        console.log(`Fetching Levey-Jennings data for ID: ${id}`);
        const response = await api.get(`/quality-control-test-parameters/${id}/lj-data`);
        console.log('Levey-Jennings API Response Data:', response.data);
        ljData.value = response.data;
      } catch (error) {
        console.error('Error fetching Levey-Jennings data:', error);
        ElMessage.error('Error fetching Levey-Jennings data.');
        ljData.value = null;
      }
    };

    const fetchUsers = async () => {
      try {
        const response = await api.get('/users'); // Assuming an API endpoint for users
        users.value = response.data.data; // Adjust based on your API response structure
      } catch (error) {
        ElMessage.error('Error fetching users.');
      }
    };

    const getPointColor = (value, target, plus1sd, minus1sd, plus2sd, minus2sd, plus3sd, minus3sd) => {
      const p1sd = parseFloat(plus1sd);
      const m1sd = parseFloat(minus1sd);
      const p2sd = parseFloat(plus2sd);
      const m2sd = parseFloat(minus2sd);
      const p3sd = parseFloat(plus3sd);
      const m3sd = parseFloat(minus3sd);

      const dangerColor = getComputedStyle(document.documentElement).getPropertyValue('--color-danger').trim();
      const warningColor = getComputedStyle(document.documentElement).getPropertyValue('--color-warning').trim();
      const successColor = getComputedStyle(document.documentElement).getPropertyValue('--color-success').trim();

      // Check if value is beyond +-3 SD first
      if (value > p3sd || value < m3sd) {
        return dangerColor;
      } 
      // Else, check if value is beyond +-2 SD
      else if (value > p2sd || value < m2sd) {
        return warningColor;
      } 
      // Otherwise, if it's within +-2SD but potentially beyond +-1SD, or within +-1SD
      else {
        return successColor;
      }
    };

    const createChart = (data) => {
      if (chart.value) {
        chart.value.destroy();
      }
      nextTick(() => {
        if (chartCanvas.value) {
          const ctx = chartCanvas.value.getContext('2d');
          const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();
          const successColor = getComputedStyle(document.documentElement).getPropertyValue('--color-success').trim();
          const warningColor = getComputedStyle(document.documentElement).getPropertyValue('--color-warning').trim();
          const dangerColor = getComputedStyle(document.documentElement).getPropertyValue('--color-danger').trim();
          const textTertiary = getComputedStyle(document.documentElement).getPropertyValue('--color-text-tertiary').trim();

          // Format dates for display
          const formattedDates = data.dates.map(dateStr => {
            return format(new Date(dateStr), 'MMM dd, yyyy HH:mm');
          });

          chart.value = markRaw(new Chart(ctx, {
            type: 'line',
            data: {
              labels: formattedDates,
              datasets: [
                {
                  label: showInvalidOnly.value ? 'Invalid Results' : 'Result',
                  data: data.results,
                  borderColor: primaryColor,
                  tension: 0.1,
                  fill: false,
                  pointRadius: 4,
                  pointBackgroundColor: data.results.map(val => getPointColor(val, data.target, data.plus_1sd, data.minus_1sd, data.plus_2sd, data.minus_2sd, data.plus_3sd, data.minus_3sd)),
                  pointBorderColor: data.results.map(val => getPointColor(val, data.target, data.plus_1sd, data.minus_1sd, data.plus_2sd, data.minus_2sd, data.plus_3sd, data.minus_3sd)),
                },
                {
                  label: 'Target',
                  data: data.dates.map(() => data.target),
                  borderColor: successColor,
                  borderDash: [5, 5],
                  pointRadius: 0,
                  fill: false
                },
                {
                  label: 'Mean',
                  data: data.dates.map(() => data.mean),
                  borderColor: warningColor,
                  borderDash: [5, 5],
                  pointRadius: 0,
                  fill: false
                },
                {
                  label: '+1 SD',
                  data: data.dates.map(() => data.plus_1sd),
                  borderColor: primaryColor,
                  borderDash: [2, 2],
                  pointRadius: 0,
                  fill: false,
                  opacity: 0.5
                },
                {
                  label: '-1 SD',
                  data: data.dates.map(() => data.minus_1sd),
                  borderColor: primaryColor,
                  borderDash: [2, 2],
                  pointRadius: 0,
                  fill: false,
                  opacity: 0.5
                },
                {
                  label: '+2 SD',
                  data: data.dates.map(() => data.plus_2sd),
                  borderColor: warningColor,
                  borderDash: [2, 2],
                  pointRadius: 0,
                  fill: false,
                  opacity: 0.5
                },
                {
                  label: '-2 SD',
                  data: data.dates.map(() => data.minus_2sd),
                  borderColor: warningColor,
                  borderDash: [2, 2],
                  pointRadius: 0,
                  fill: false,
                  opacity: 0.5
                },
                {
                  label: '+3 SD',
                  data: data.dates.map(() => data.plus_3sd),
                  borderColor: dangerColor,
                  borderDash: [2, 2],
                  pointRadius: 0,
                  fill: false,
                  opacity: 0.5
                },
                {
                  label: '-3 SD',
                  data: data.dates.map(() => data.minus_3sd),
                  borderColor: dangerColor,
                  borderDash: [2, 2],
                  pointRadius: 0,
                  fill: false,
                  opacity: 0.5
                },
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                x: {
                  type: 'category',
                  title: {
                    display: true,
                    text: 'Run Date'
                  }
                },
                y: {
                  title: {
                    display: true,
                    text: 'Result Value'
                  }
                }
              },
              plugins: {
                tooltip: {
                  mode: 'index',
                  intersect: false,
                },
                legend: {
                  display: true,
                  position: 'top',
                },
              }
            }
          }));
        }
      });
    };

    const updateChart = (data) => {
      if (chart.value) {
        console.log('updateChart - Data object:', data); // Log the data object here
        // Format dates for display
        const formattedDates = data.dates.map(dateStr => {
            return format(new Date(dateStr), 'MMM dd, yyyy HH:mm');
        });

        chart.value.data.labels = formattedDates;
        chart.value.data.datasets[0].label = showInvalidOnly.value ? 'Invalid Results' : 'Result';
        chart.value.data.datasets[0].data = data.results; // Result
        chart.value.data.datasets[0].pointBackgroundColor = data.results.map(val => getPointColor(val, data.target, data.plus_1sd, data.minus_1sd, data.plus_2sd, data.minus_2sd, data.plus_3sd, data.minus_3sd));
        chart.value.data.datasets[0].pointBorderColor = data.results.map(val => getPointColor(val, data.target, data.plus_1sd, data.minus_1sd, data.plus_2sd, data.minus_2sd, data.plus_3sd, data.minus_3sd));
        chart.value.data.datasets[1].data = data.dates.map(() => data.target); // Target
        chart.value.data.datasets[2].data = data.dates.map(() => data.mean); // Mean
        chart.value.data.datasets[3].data = data.dates.map(() => data.plus_1sd); // +1SD
        chart.value.data.datasets[4].data = data.dates.map(() => data.minus_1sd); // -1SD
        chart.value.data.datasets[5].data = data.dates.map(() => data.plus_2sd); // +2SD
        chart.value.data.datasets[6].data = data.dates.map(() => data.minus_2sd); // -2SD
        chart.value.data.datasets[7].data = data.dates.map(() => data.plus_3sd); // +3SD
        chart.value.data.datasets[8].data = data.dates.map(() => data.minus_3sd); // -3SD
        chart.value.update();
        console.log('Chart updated with new data.');
      } else {
        console.log('Chart not initialized, creating new chart.');
        createChart(data);
      }
    };

    const fetchData = async () => {
      const id = props.qcTestParameterId;
      if (!id) {
        ElMessage.error('Quality Control Test Parameter ID is not provided.');
        return;
      }
      await Promise.all([
        fetchQualityControlTestParameter(id),
        fetchResults(id),
        fetchLeveyJenningsData(id),
      ]);
    };

    function openDialog(item = null) {
      if (item) {
        editedIndex.value = results.value.indexOf(item);
        editedItem.value = { ...item };
        if (item.run_date) {
          editedItem.value.run_date = new Date(item.run_date);
        }
        if (item.run_time) {
          const [hours, minutes, seconds] = item.run_time.split(':');
          const dateForTime = new Date();
          dateForTime.setHours(parseInt(hours, 10));
          dateForTime.setMinutes(parseInt(minutes, 10));
          dateForTime.setSeconds(parseInt(seconds, 10));
          editedItem.value.run_time = dateForTime;
        }
      } else {
        editedIndex.value = -1;
        editedItem.value = { ...defaultItem };
        editedItem.value.run_date = new Date();
        editedItem.value.run_time = new Date();
      }
      editedItem.value.quality_control_test_parameter_id = props.qcTestParameterId;
      // Auto-populate unit from test parameter if available
      if (qualityControlTestParameter.value?.test_parameter?.unit) {
        editedItem.value.unit = qualityControlTestParameter.value.test_parameter.unit;
      }
      dialog.value = true;
    }

    function closeDialog() {
      dialog.value = false;
      editedItem.value = { ...defaultItem };
      editedIndex.value = -1;
    }

    async function save() {
      try {
        if (!qualityControlTestParameter.value) {
           ElMessage.warning('Quality Control Test Parameter not selected.');
           return;
        }

        const payload = { ...editedItem.value };
        if (payload.run_date) {
          payload.run_date = format(new Date(payload.run_date), 'yyyy-MM-dd');
        }
        if (payload.run_time) {
          payload.run_time = format(new Date(payload.run_time), 'HH:mm:ss');
        }

        if (editedIndex.value > -1) {
          const response = await api.put(`/quality-control-results/${payload.id}`, payload);
          results.value.splice(editedIndex.value, 1, response.data);
          ElMessage.success('Result updated');
        } else {
          // For new results, the quality_control_test_parameter_id is already in editedItem.value
          const response = await api.post(`/quality-control-test-parameters/${props.qcTestParameterId}/results`, payload);
          results.value.push(response.data);
          ElMessage.success('Result added');
        }
        closeDialog();
        fetchData(); // Re-fetch all data to update table and chart
      } catch (error) {
        console.error('Error saving result:', error);
        ElMessage.error('Error saving result.');
      }
    }

    function confirmDelete(item) {
      editedIndex.value = results.value.indexOf(item);
      editedItem.value = { ...item };
      deleteDialog.value = true;
    }

    async function deleteItem() {
      try {
        await api.delete(`/quality-control-results/${editedItem.value.id}`);
        results.value.splice(editedIndex.value, 1);
        ElMessage.success('Result deleted');
        deleteDialog.value = false;
        fetchData(); // Re-fetch all data to update table and chart
      } catch (error) {
        console.error('Error deleting result:', error);
        ElMessage.error('Error deleting result.');
      }
    }

    const formatDate = (dateString) => {
      if (!dateString) return '';
      // Assuming dateString is already in 'YYYY-MM-DD' format from the raw result object
      return format(new Date(dateString), 'MMM dd, yyyy');
    };

    const formatTime = (timeString) => {
      if (!timeString) return '';
      // Assuming timeString is already in 'HH:MM:SS' format from the raw result object
      // For displaying only time, we can parse it as part of an arbitrary date
      const [hours, minutes, seconds] = timeString.split(':');
      const date = new Date();
      date.setHours(hours, minutes, seconds);
      return format(date, 'HH:mm:ss');
    };

    function handleSizeChange(val) {
      pageSize.value = val;
      fetchResults(props.qcTestParameterId);
    }

    function handleCurrentChange(val) {
      currentPage.value = val;
      fetchResults(props.qcTestParameterId);
    }

    const acceptResult = async (row) => {
      try {
        await ElMessageBox.confirm(
          'Are you sure you want to accept this result?',
          'Accept Result',
          {
            confirmButtonText: 'Accept',
            cancelButtonText: 'Cancel',
            type: 'warning',
          }
        );
        
        const response = await api.put(`/quality-control-results/${row.id}/accept`);
        const index = results.value.findIndex(r => r.id === row.id);
        if (index !== -1) {
          results.value[index] = response.data.data;
        }
        ElMessage.success('Result accepted successfully');
        fetchData(); // Refresh data to update chart
      } catch (error) {
        if (error !== 'cancel') {
          console.error('Error accepting result:', error);
          ElMessage.error('Error accepting result');
        }
      }
    };

    const rejectResult = async (row) => {
      try {
        await ElMessageBox.confirm(
          'Are you sure you want to reject this result?',
          'Reject Result',
          {
            confirmButtonText: 'Reject',
            cancelButtonText: 'Cancel',
            type: 'warning',
          }
        );
        
        const response = await api.put(`/quality-control-results/${row.id}/reject`);
        const index = results.value.findIndex(r => r.id === row.id);
        if (index !== -1) {
          results.value[index] = response.data.data;
        }
        ElMessage.success('Result rejected successfully');
        fetchData(); // Refresh data to update chart
        
        // Automatically prompt for corrective action documentation
        ElMessageBox.alert(
          'This QC result has been rejected. You must document the corrective action.',
          'Document Corrective Action',
          {
            confirmButtonText: 'Document Now',
            type: 'warning',
          }
        ).then(() => {
          openCorrectiveActionDialog(row);
        }).catch(() => {
          // User chose not to document immediately, but they can use the CA button later
        });
      } catch (error) {
        if (error !== 'cancel') {
          console.error('Error rejecting result:', error);
          ElMessage.error('Error rejecting result');
        }
      }
    };

    const openCorrectiveActionDialog = (result) => {
      selectedQCResult.value = result;
      // Pre-fill with first violation if available
      if (result.westgard_violations && result.westgard_violations.length > 0) {
        correctiveActionForm.value.rule_violated = result.westgard_violations[0];
      }
      correctiveActionForm.value.suspected_error = '';
      correctiveActionForm.value.investigation = '';
      correctiveActionForm.value.resolution = '';
      correctiveActionDialog.value = true;
    };

    const saveCorrectiveAction = async () => {
      if (!correctiveActionFormRef.value) {
        return;
      }

      await correctiveActionFormRef.value.validate(async (valid) => {
        if (!valid) {
          return;
        }

        try {
          const payload = {
            quality_control_result_id: selectedQCResult.value.id,
            quality_control_test_parameter_id: props.qcTestParameterId,
            rule_violated: correctiveActionForm.value.rule_violated,
            suspected_error: correctiveActionForm.value.suspected_error,
            investigation: correctiveActionForm.value.investigation,
            resolution: correctiveActionForm.value.resolution || null,
          };

          await api.post('/corrective-actions', payload);
          ElMessage.success('Corrective action documented successfully');
          correctiveActionDialog.value = false;
          fetchData(); // Refresh to show any updates
        } catch (error) {
          console.error('Error saving corrective action:', error);
          ElMessage.error('Error saving corrective action');
        }
      });
    };

    onMounted(() => {
      fetchData();
      fetchUsers();
    });

    onBeforeUnmount(() => {
      if (chart.value) {
        chart.value.destroy();
        chart.value = null;
      }
    });

    watch(() => props.qcTestParameterId, async (newId, oldId) => {
      if (newId !== oldId && newId) {
        console.log(`qcTestParameterId changed from ${oldId} to ${newId}. Refetching data.`);
        await fetchData();
        if (ljData.value) {
          updateChart(filteredChartData.value);
        }
      }
    });

    watch([ljData, showInvalidOnly], ([newData, showInvalid]) => {
      if (newData) {
        console.log('ljData or filter updated, attempting to update chart.');
        updateChart(filteredChartData.value);
      }
    });

    return {
      chartCanvas,
      results,
      loading,
      dialog,
      deleteDialog,
      editedItem,
      formTitle,
      qualityControlTestParameter,
      users,
      openDialog,
      closeDialog,
      save,
      confirmDelete,
      deleteItem,
      formatDate,
      formatTime,
      currentPage,
      pageSize,
      totalResults,
      handleSizeChange,
      handleCurrentChange,
      showInvalidOnly,
      filteredResults,
      acceptResult,
      rejectResult,
      correctiveActionDialog,
      correctiveActionFormRef,
      correctiveActionForm,
      correctiveActionRules,
      selectedQCResult,
      openCorrectiveActionDialog,
      saveCorrectiveAction,
    };
  }
};
</script>

<style scoped>
.quality-control-results {
  width: 100%;
}

.qc-container {
  display: grid;
  gap: 1rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
}

.title-section {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.page-title {
  margin: 0;
  font-size: 24px;
  font-weight: 500;
}

.page-subtitle {
  margin: 0;
  color: var(--color-text-tertiary);
  font-size: var(--font-size-sm);
}

.header-actions {
  display: flex;
  gap: 10px;
}

.mb-4 {
  margin-bottom: 1rem;
}

.mr-1 {
  margin-right: 0.25rem;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style> 