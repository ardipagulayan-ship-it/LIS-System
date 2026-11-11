<template>
  <div class="content-wrapper">
    <!-- Triage Tabs -->
    <div class="card">
      <div class="header-area">
        <div class="header-title-area">
          <h2 class="page-title">Request List</h2>
          <span class="page-subtitle"
            >Test results awaiting review and validation</span
          >
        </div>

        <div class="header-actions">
          <el-input
            v-model="searchQuery"
            placeholder="Search by ID or patient name..."
            class="search-input"
            clearable
            @input="handleSearch"
            style="width: 250px"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
          <!-- NEW: Patient Type Filter -->
          <el-select
            v-model="patientTypeFilter"
            placeholder="Patient Type"
            clearable
            @change="handleFilterChange"
            style="width: 180px"
          >
            <el-option
              v-for="pType in patientTypes"
              :key="pType.id"
              :label="pType.name"
              :value="pType.id"
            />
          </el-select>
        </div>
      </div>

      <div class="triage-tabs-container">
        <div class="triage-tabs">
          <button
            v-for="tab in triageTabs"
            :key="tab.key"
            :class="['triage-tab', { active: activeTriageTab === tab.key }]"
            @click="activeTriageTab = tab.key"
          >
            {{ tab.label }}
            <el-badge
              v-if="getTabCount(tab.key) > 0"
              :value="getTabCount(tab.key)"
              :type="activeTriageTab === tab.key ? 'primary' : 'primary'"
              class="tab-badge"
            />
          </button>
        </div>
        <el-checkbox
          v-model="showOnlyReflex"
          @change="handleReflexFilterChange"
          class="triage-check-box"
          label="Show Only Reflex Tests"
        />
      </div>

      <!-- Active Worklist Tab -->

      <template v-if="activeTriageTab === 'worklist'">
        <div class="table-section">
          <div class="custom-table" v-loading="loading">
            <el-table
              :data="worklistRequests"
              :row-class-name="getRowClassName"
              highlight-current-row
              @row-dblclick="(row) => viewResults(row.id)"
            >
              <el-table-column label="Flags" width="100">
                <template #default="{ row }">
                  <div class="specimen-id-cell">
                    <el-tooltip
                      v-if="getViolations(row).length > 0"
                      placement="top"
                      effect="dark"
                    >
                      <template #content>
                        <div class="exemption-tooltip-content">
                          <div
                            v-for="(violation, index) in getViolations(row)"
                            :key="index"
                            class="violation-item"
                          >
                            • {{ violation }}
                          </div>
                        </div>
                      </template>
                      <span
                        class="exemption-icon"
                        :color="
                          hasBlockingCdsViolations(row)
                            ? 'var(--el-color-danger)'
                            : 'var(--el-color-warning)'
                        "
                        >❗</span
                      >
                    </el-tooltip>
                    <span
                      class="exemption-icon"
                      v-if="row.is_reflex"
                      title="Reflex Test"
                      >⚡</span
                    >
                    <span
                      class="exemption-icon"
                      v-else-if="row.has_reflex_children"
                      title="Has Reflex Children ({{ row.reflex_children_count }})"
                      >🔀</span
                    >
                  </div>
                </template>
              </el-table-column>
              <el-table-column label="Specimen ID" width="160">
                <template #default="{ row }">
                  <div class="specimen-id-cell">
                    <span
                      :class="[
                        'specimen-id-text',
                        { 'specimen-id-critical': isCriticalHold(row) },
                      ]"
                    >
                      {{ row.sample_id }}
                    </span>
                  </div>
                </template>
              </el-table-column>
              <el-table-column label="Patient Information" min-width="200">
                <template #default="{ row }">
                  <div class="patient-info-cell">
                    <div class="patient-name-row">
                      <span class="patient-name">{{
                        formatPatientName(row)
                      }}</span>
                      <el-badge
                        :value="getPriority(row.priority_level)"
                        :type="getPriorityType(row.priority_level)"
                        size="small"
                        style="margin-left: 2px"
                      />
                      <el-tooltip
                        v-if="flaggedRequestIds.has(row.id)"
                        content="Delta check flagged"
                        placement="top"
                      >
                        <span
                          class="delta-icon-status"
                          @click.stop="openDeltaCheck(row)"
                          >Δ</span
                        >
                      </el-tooltip>
                    </div>
                    <div class="patient-meta">
                      <span class="meta-item"
                        >{{ calculateAge(row.patient?.date_of_birth) }} /
                        {{ row.patient?.gender || "N/A" }}</span
                      >
                      <span class="meta-divider">|</span>
                      <span class="meta-item">{{
                        row.patient_type?.name || "OPD"
                      }}</span>
                    </div>
                  </div>
                </template>
              </el-table-column>
              <el-table-column
                label="Physician"
                min-width="150"
                show-overflow-tooltip
              >
                <template #default="{ row }">
                  <span class="physician-name">{{
                    row.doctor?.name || "N/A"
                  }}</span>
                </template>
              </el-table-column>
              <el-table-column label="Status" width="160" align="center">
                <template #default="{ row }">
                  <div class="status-cell">
                    <template v-if="hasBlockingCdsViolations(row)">
                      <span
                        class="status-pill status-pill-red"
                        title="Blocked by CDS Rule"
                      >
                        CDS BLOCK
                      </span>
                    </template>
                    <template
                      v-else-if="isHematology && isRequestOnQCHold(row)"
                    >
                      <span
                        class="status-pill status-pill-orange"
                        @click.stop="goToQCLog(row)"
                        style="cursor: pointer"
                      >
                        QC HOLD
                      </span>
                    </template>
                    <template v-else-if="isCriticalHold(row)">
                      <span class="status-pill status-pill-red">
                        CRITICAL HOLD
                      </span>
                    </template>
                    <template v-else>
                      <span :class="getStatusPillClass(getRowStatus(row))">
                        {{ getStatusDisplayName(row) }}
                      </span>
                    </template>
                  </div>
                </template>
              </el-table-column>
              <el-table-column label="Tests" width="180" show-overflow-tooltip>
                <template #default="{ row }">
                  <span class="test-full-name">{{ formatTestNames(row) }}</span>
                </template>
              </el-table-column>
              <el-table-column
                label="Location"
                width="140"
                show-overflow-tooltip
              >
                <template #default="{ row }">
                  <span class="location-text">{{ formatLocation(row) }}</span>
                </template>
              </el-table-column>
              <el-table-column
                label="Reflex Origin"
                width="150"
                show-overflow-tooltip
                v-if="showOnlyReflex"
              >
                <template #default="{ row }">
                  <div
                    v-if="row.is_reflex && row.reflex_parent_request_id"
                    class="reflex-origin-cell"
                  >
                    <el-link
                      type="primary"
                      :underline="false"
                      @click.stop="
                        viewParentRequest(row.reflex_parent_request_id)
                      "
                    >
                      {{
                        getParentRequestSampleId(row.reflex_parent_request_id)
                      }}
                    </el-link>
                  </div>
                  <span v-else-if="row.is_reflex" class="text-muted">-</span>
                </template>
              </el-table-column>
              <el-table-column label="Requested" width="120" align="right">
                <template #default="{ row }">
                  <span class="date-text">{{
                    formatDateTimeShort(null, null, row.request_date)
                  }}</span>
                </template>
              </el-table-column>
              <el-table-column
                label="Actions"
                width="170"
                align="center"
                fixed="right"
              >
                <template #default="{ row }">
                  <div class="action-buttons">
                    <el-button-group>
                      <el-button
                        size="small"
                        circle
                        @click.stop="viewResults(row.id)"
                      >
                        <el-icon><View /></el-icon>
                      </el-button>

                      <el-button
                        v-if="
                          [
                            'Validated',
                            'Printed',
                            'Completed',
                            'Result Validated',
                            'Result Printed',
                          ].includes(getRowStatus(row))
                        "
                        size="small"
                        type="secondary"
                        @click.stop="printReport(row.id)"
                        circle
                      >
                        <el-icon> <Printer /> </el-icon>
                      </el-button>
                    </el-button-group>
                  </div>
                </template>
              </el-table-column>
            </el-table>
          </div>
        </div>
        <div class="pagination">
          <el-pagination
            v-if="worklistRequests.length > 0"
            v-model:current-page="currentPage"
            v-model:page-size="pageSize"
            :page-sizes="[15, 30, 50, 100]"
            :total="worklistRequests.length"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
            small
          />
        </div>
      </template>

      <!-- Rejected Requests Tab -->
      <template v-if="activeTriageTab === 'rejected'">
        <div class="table-section">
          <div class="custom-table" v-loading="loading">
            <el-table
              :data="rejectedRequests"
              style="width: 100%"
              :row-class-name="getRowClassName"
              highlight-current-row
              @row-dblclick="(row) => viewResults(row.id)"
            >
              <el-table-column label="Specimen ID" width="160">
                <template #default="{ row }">
                  <div class="specimen-id-cell">
                    <el-tooltip
                      v-if="getViolations(row).length > 0"
                      placement="top"
                      effect="dark"
                    >
                      <template #content>
                        <div class="exemption-tooltip-content">
                          <div
                            v-for="(violation, index) in getViolations(row)"
                            :key="index"
                          >
                            • {{ violation }}
                          </div>
                        </div>
                      </template>
                      <span
                        class="exemption-icon"
                        :color="
                          hasBlockingCdsViolations(row)
                            ? 'var(--el-color-danger)'
                            : 'var(--el-color-warning)'
                        "
                        >❗</span
                      >
                    </el-tooltip>
                    <span
                      class="exemption-icon"
                      v-if="row.is_reflex"
                      title="Reflex Test"
                      >⚡</span
                    >
                    <span
                      class="exemption-icon"
                      v-else-if="row.has_reflex_children"
                      title="Has Reflex Children ({{ row.reflex_children_count }})"
                      >↻</span
                    >
                    <span class="specimen-id-text">{{ row.sample_id }}</span>
                    <el-tag
                      v-if="row.is_reflex"
                      type="warning"
                      size="small"
                      class="reflex-badge"
                      style="
                        margin-left: 5px;
                        padding: 2px 6px;
                        font-weight: bold;
                      "
                      title="Reflex Test"
                    >
                      ↻
                    </el-tag>
                  </div>
                  <el-badge
                    :value="String(row.priority_level)[0]"
                    :type="getPriorityType(row.priority_level)"
                    size="small"
                    style="margin-left: 2px"
                  />
                </template>
              </el-table-column>
              <el-table-column label="Patient Information" min-width="200">
                <template #default="{ row }">
                  <div class="patient-info-cell">
                    <span class="patient-name">{{
                      formatPatientName(row)
                    }}</span>
                    <div class="patient-meta">
                      <span class="meta-item"
                        >{{ calculateAge(row.patient?.date_of_birth) }} /
                        {{ row.patient?.gender || "N/A" }}</span
                      >
                    </div>
                  </div>
                </template>
              </el-table-column>
              <el-table-column
                label="Physician"
                min-width="150"
                show-overflow-tooltip
              >
                <template #default="{ row }">
                  <span class="physician-name">{{
                    row.doctor?.name || "N/A"
                  }}</span>
                </template>
              </el-table-column>
              <el-table-column label="Status" width="160" align="center">
                <template #default="{ row }">
                  <div class="status-cell">
                    <span :class="getStatusPillClass(getRowStatus(row))">
                      {{ getStatusDisplayName(row) }}
                    </span>
                  </div>
                </template>
              </el-table-column>
              <el-table-column label="Tests" min-width="180">
                <template #default="{ row }">
                  <div
                    v-if="row.tests && row.tests.length"
                    class="test-requested-cell"
                  >
                    <span class="test-full-name">{{
                      formatTestNames(row)
                    }}</span>
                  </div>
                </template>
              </el-table-column>
              <el-table-column
                label="Location"
                width="140"
                show-overflow-tooltip
              >
                <template #default="{ row }">
                  <span class="location-text">{{ formatLocation(row) }}</span>
                </template>
              </el-table-column>
              <el-table-column
                label="Reflex Origin"
                width="150"
                show-overflow-tooltip
                v-if="showOnlyReflex"
              >
                <template #default="{ row }">
                  <div
                    v-if="row.is_reflex && row.reflex_parent_request_id"
                    class="reflex-origin-cell"
                  >
                    <el-link
                      type="primary"
                      :underline="false"
                      @click.stop="
                        viewParentRequest(row.reflex_parent_request_id)
                      "
                    >
                      {{
                        getParentRequestSampleId(row.reflex_parent_request_id)
                      }}
                    </el-link>
                  </div>
                  <span v-else-if="row.is_reflex" class="text-muted">-</span>
                </template>
              </el-table-column>
              <el-table-column label="Rejected" width="120" align="right">
                <template #default="{ row }">
                  <span class="date-text">{{
                    formatDateTimeShort(null, null, getRejectedAt(row))
                  }}</span>
                </template>
              </el-table-column>
              <el-table-column
                label="Actions"
                width="170"
                align="center"
                fixed="right"
              >
                <template #default="{ row }">
                  <div class="action-buttons">
                    <el-button
                      size="small"
                      type="warning"
                      @click.stop="viewResults(row.id)"
                    >
                      <el-icon><View /> </el-icon> View
                    </el-button>
                  </div>
                </template>
              </el-table-column>
            </el-table>
          </div>
        </div>
        <div class="pagination">
          <el-pagination
            v-if="rejectedRequests.length > 0"
            v-model:current-page="currentPage"
            v-model:page-size="pageSize"
            :page-sizes="[15, 30, 50, 100]"
            :total="rejectedRequests.length"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
            small
          />
        </div>
      </template>

      <!-- Completed Transactions Tab -->
      <template v-if="activeTriageTab === 'completed'">
        <div class="table-section">
          <div class="custom-table" v-loading="loading">
            <el-table
              :data="completedRequests"
              style="width: 100%"
              :row-class-name="getRowClassName"
              highlight-current-row
              @row-dblclick="(row) => viewResults(row.id)"
            >
              <el-table-column label="Specimen ID" width="160">
                <template #default="{ row }">
                  <div class="specimen-id-cell">
                    <el-tooltip
                      v-if="getViolations(row).length > 0"
                      placement="top"
                      effect="dark"
                    >
                      <template #content>
                        <div class="exemption-tooltip-content">
                          <div
                            v-for="(violation, index) in getViolations(row)"
                            :key="index"
                          >
                            • {{ violation }}
                          </div>
                        </div>
                      </template>
                      <span
                        class="exemption-icon"
                        :color="
                          hasBlockingCdsViolations(row)
                            ? 'var(--el-color-danger)'
                            : 'var(--el-color-warning)'
                        "
                        >❗</span
                      >
                    </el-tooltip>
                    <span
                      class="exemption-icon"
                      v-if="row.is_reflex"
                      title="Reflex Test"
                      >⚡</span
                    >
                    <span
                      class="exemption-icon"
                      v-else-if="row.has_reflex_children"
                      title="Has Reflex Children ({{ row.reflex_children_count }})"
                      >↻</span
                    >
                    <!-- <el-tooltip
                    v-if="hasCdsViolations(row)"
                    placement="top"
                    effect="dark"
                  >
                    <template #content>
                      <div class="cds-tooltip-content">
                        <div class="tooltip-header">Triggered CDS Rules:</div>
                        <template v-if="cdsViolations[row.id]?.cds">
                          <div v-for="(rule, index) in cdsViolations[row.id].cds.triggered_rules" :key="index" class="tooltip-item">
                            • {{ rule.rule_name }}: {{ rule.alert_message }}
                            <el-tag v-if="rule.is_acknowledged" size="small" type="success" effect="plain" style="margin-left: 4px; font-size: 12px; height: 16px; line-height: 14px;">
                              Acknowledged
                            </el-tag>
                          </div>
                        </template>
                      </div>
                    </template>
                    <el-tag
                      size="small"
                      :type="hasBlockingCdsViolations(row) ? 'danger' : 'warning'"
                      effect="dark"
                      class="cds-list-tag"
                    >
                      CDS
                    </el-tag>
                  </el-tooltip> -->
                    <span class="specimen-id-text">{{ row.sample_id }}</span>
                    <el-tag
                      v-if="row.is_reflex"
                      type="warning"
                      size="small"
                      class="reflex-badge"
                      style="
                        margin-left: 5px;
                        padding: 2px 6px;
                        font-weight: bold;
                      "
                      title="Reflex Test"
                    >
                      ↻
                    </el-tag>
                  </div>
                </template>
              </el-table-column>
              <el-table-column label="Patient Information" min-width="200">
                <template #default="{ row }">
                  <div class="patient-info-cell">
                    <span class="patient-name">{{
                      formatPatientName(row)
                    }}</span>
                    <div class="patient-meta">
                      <span class="meta-item"
                        >{{ calculateAge(row.patient?.date_of_birth) }} /
                        {{ row.patient?.gender || "N/A" }}</span
                      >
                    </div>
                  </div>
                </template>
              </el-table-column>
              <el-table-column
                label="Physician"
                min-width="150"
                show-overflow-tooltip
              >
                <template #default="{ row }">
                  <span class="physician-name">{{
                    row.doctor?.name || "N/A"
                  }}</span>
                </template>
              </el-table-column>
              <el-table-column label="Status" width="160" align="center">
                <template #default="{ row }">
                  <div class="status-cell">
                    <template v-if="hasBlockingCdsViolations(row)">
                      <span
                        class="status-pill status-pill-red"
                        title="Blocked by CDS Rule"
                      >
                        CDS BLOCK
                      </span>
                    </template>
                    <template v-else>
                      <span :class="getStatusPillClass(getRowStatus(row))">
                        {{ getStatusDisplayName(row) }}
                      </span>
                    </template>
                  </div>
                </template>
              </el-table-column>
              <el-table-column label="Tests" min-width="180">
                <template #default="{ row }">
                  <div
                    v-if="row.tests && row.tests.length"
                    class="test-requested-cell"
                  >
                    <span class="test-full-name">{{
                      formatTestNames(row)
                    }}</span>
                  </div>
                </template>
              </el-table-column>
              <el-table-column
                label="Location"
                width="140"
                show-overflow-tooltip
              >
                <template #default="{ row }">
                  <span class="location-text">{{ formatLocation(row) }}</span>
                </template>
              </el-table-column>
              <el-table-column
                label="Reflex Origin"
                width="150"
                show-overflow-tooltip
                v-if="showOnlyReflex"
              >
                <template #default="{ row }">
                  <div
                    v-if="row.is_reflex && row.reflex_parent_request_id"
                    class="reflex-origin-cell"
                  >
                    <el-link
                      type="primary"
                      :underline="false"
                      @click.stop="
                        viewParentRequest(row.reflex_parent_request_id)
                      "
                    >
                      {{
                        getParentRequestSampleId(row.reflex_parent_request_id)
                      }}
                    </el-link>
                  </div>
                  <span v-else-if="row.is_reflex" class="text-muted">-</span>
                </template>
              </el-table-column>
              <el-table-column label="Completed" width="120" align="right">
                <template #default="{ row }">
                  <span class="date-text">{{
                    formatDateTimeShort(null, null, row.updated_at)
                  }}</span>
                </template>
              </el-table-column>
              <el-table-column
                label="Actions"
                width="170"
                align="center"
                fixed="right"
              >
                <template #default="{ row }">
                  <div class="action-buttons">
                    <el-button-group>
                      <el-button
                        size="small"
                        type="warning"
                        @click.stop="viewResults(row.id)"
                      >
                        <el-icon><View /> </el-icon> View
                      </el-button>
                      <el-button
                        size="small"
                        type="success"
                        @click.stop="printReport(row.id)"
                      >
                        <el-icon><Printer /> </el-icon> Print
                      </el-button>
                    </el-button-group>
                  </div>
                </template>
              </el-table-column>
            </el-table>
          </div>
        </div>
        <div class="pagination">
          <el-pagination
            v-if="completedRequests.length > 0"
            v-model:current-page="currentPage"
            v-model:page-size="pageSize"
            :page-sizes="[15, 30, 50, 100]"
            :total="completedRequests.length"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
            small
          />
        </div>
      </template>

      <!-- Universal Result Modal for all categories -->
      <UniversalResultModal
        v-if="isViewingDetails"
        :visible="isViewingDetails"
        :request-details="selectedRequestDetails"
        :verifiers="verifierList"
        :performers="performerList"
        :pathologists="pathologistList"
        :loading="detailsLoading"
        :category-name="props.categoryName"
        :category-id="props.categoryId"
        @close="closeDetailsView"
        @save="handleSaveResults"
        @print="handlePrintResults"
        @validate="handleValidateResults"
        @refresh="refreshRequestDetails"
      />
    </div>
  </div>
</template>

<script setup>
import _ from "lodash";
import api from "@/utils/axios";
import {
  ref,
  onMounted,
  watch,
  computed,
  onUnmounted,
  onBeforeUnmount,
} from "vue";
import {
  View,
  Printer,
  Search,
  ArrowLeft,
  WarningFilled,
  Document,
} from "@element-plus/icons-vue";
import {
  format,
  parseISO,
  differenceInYears,
  differenceInMonths,
  differenceInDays,
} from "date-fns";
import { useRouter, useRoute } from "vue-router";
import { ElMessage } from "element-plus";
import UniversalResultModal from "@/components/modals/result/UniversalResultModal.vue";

const router = useRouter();
const route = useRoute();

const props = defineProps({
  categoryId: {
    type: [String, Number],
    required: true,
  },
  categoryName: {
    type: String,
    required: true,
  },
});

// Computed property for dynamic status column name
// const statusColumn = computed(() => {
//   return `${props.categoryName.toLowerCase().replace(/\s+/g, '_')}_status`;
// });

// --- State ---
const requests = ref([]);
const loading = ref(false);
const loadingFlags = ref(false);
const currentPage = ref(1);
const pageSize = ref(15);
const total = ref(0);
const searchQuery = ref("");
const isMounted = ref(false);
let searchTimeout = null;
const patientTypeFilter = ref("");
const patientTypes = ref([]); // To store patient types from API

onMounted(() => {
  isMounted.value = true;
  fetchRequests();
  loadEquipmentSummary();
  fetchPatientTypes(); // Fetch patient types for the filter
});

onBeforeUnmount(() => {
  isMounted.value = false;
});

const flaggedRequestIds = ref(new Set());
const cdsViolations = ref({}); // { requestId: { cds: { triggered_rules: [], has_blocking_rules: false }, delta: { is_acknowledged: false }, critical: { is_acknowledged: false } } }
const activeTriageTab = ref("worklist");
const showOnlyReflex = ref(false);

// Static cache for 429 prevention
const staticCache = {
  requestDetails: {}, // { requestId: { data: {}, timestamp: 0 } }
  equipmentSummary: {}, // { categoryId: { data: {}, timestamp: 0 } }
  deltaFlags: { data: new Set(), timestamp: 0 },
  requestEquipment: {}, // { requestId: { data: [], timestamp: 0 } }
  ruleViolations: { data: {}, timestamp: 0 },
};
const CACHE_TTL = 30000; // 30 seconds cache

// Triage tabs configuration
const triageTabs = [
  { key: "worklist", label: "Active Worklist" },
  { key: "completed", label: "Completed Transactions" },
  { key: "rejected", label: "Rejected Requests" },
];

// --- State for Viewing Details ---
const isViewingDetails = ref(false);
const selectedRequestDetails = ref(null);
const verifierList = ref([]);
const performerList = ref([]);
const pathologistList = ref([]);
const detailsLoading = ref(false);

// --- Computed Properties ---
const categoryId = computed(
  () => parseInt(props.categoryId) || parseInt(route.params.categoryId),
);
const categoryName = computed(
  () => props.categoryName || route.params.categoryName,
);
//const statusColumn = computed(() => `${categoryName.value.toLowerCase()}_status`);
const statusColumn = computed(() => {
  // Normalize category name: lowercase, replace spaces with underscores, handle hyphens
  const name = (categoryName.value || "").toLowerCase();
  const normalized = name.replace(/\s+/g, "_").replace(/-/g, "_");
  return `${normalized}_status`;
});
const testListColumn = computed(
  () => `${(categoryName.value || "").toLowerCase()}_test_list`,
);
const isHematology = computed(() =>
  (categoryName.value || "").toLowerCase().includes("hematology"),
);

// Helper function to get row status with fallback
const getRowStatus = (row) => {
  // Try the computed status column first
  if (row[statusColumn.value]) {
    return row[statusColumn.value];
  }

  // Try generic status field
  if (row.status) {
    return row.status;
  }

  // Fallback: try alternative formats
  const categoryNameLower = (categoryName.value || "").toLowerCase();
  const alternatives = [
    categoryNameLower.replace(/\s+/g, "_") + "_status",
    categoryNameLower.replace(/\s+/g, "-") + "_status",
    categoryNameLower.replace(/-/g, "_") + "_status",
    categoryNameLower.replace(/_/g, "-") + "_status",
  ];

  for (const alt of alternatives) {
    if (row[alt]) {
      return row[alt];
    }
  }

  // Final fallback: compute status from test pivot statuses
  return computeStatusFromTests(row);
};

// Compute status from test pivot statuses (same logic as backend)
const computeStatusFromTests = (row) => {
  if (!row.tests || row.tests.length === 0) return "Unknown";

  const hasPrinted = row.tests.some(
    (test) => test.pivot?.item_status === "Result Printed",
  );
  const hasValidated = row.tests.some(
    (test) =>
      test.pivot?.item_status === "Result Validated" ||
      test.pivot?.item_status === "Result Printed",
  );
  const hasAccepted = row.tests.some((test) =>
    ["Accepted", "Collected", "Send-out", "Parked"].includes(
      test.pivot?.item_status,
    ),
  );
  const hasResults = row.tests.some(
    (test) => test.pivot?.item_status === "Processing Results",
  );

  const hasCheckedIn = row.tests.some(
    (test) => test.pivot?.item_status === "Checked-in",
  );
  const hasForRerun = row.tests.some(
    (test) => test.pivot?.item_status === "For Re-run",
  );
  const hasRejected = row.tests.some(
    (test) => test.pivot?.item_status === "Rejected",
  );
  const allRejected = row.tests.every(
    (test) => test.pivot?.item_status === "Rejected",
  );

  if (allRejected) return "Rejected";
  if (hasRejected) return "Partially Rejected";
  if (hasPrinted) return "Released";
  if (hasValidated) return "Validated";
  if (hasAccepted) return "Pending Sample";
  if (hasCheckedIn) return "Checked-in";
  if (hasResults) return "Processing Results";
  if (hasForRerun) return "For Re-run";
  return "Unknown";
};

// Handler for filter changes (search clear, dropdown change)
const handleFilterChange = () => {
  currentPage.value = 1; // Reset page when filter changes
  fetchRequests();
};

// Helper function to check if row is on critical hold
const isCriticalHold = (row) => {
  const status = getRowStatus(row);
  return status?.toUpperCase().includes("CRITICAL HOLD") || false;
};

// Helper function to check if a row has violations
const hasViolations = (row) => {
  const status = getRowStatus(row);
  const isQCHold = isHematology.value && isRequestOnQCHold(row);
  const isCritical = isCriticalHold(row);
  const hasDeltaCheck = flaggedRequestIds.value.has(row.id);
  const statusUpper = status?.toUpperCase() || "";
  const isOnHold =
    statusUpper.includes("HOLD") ||
    status === "On Hold" ||
    statusUpper.includes("ON HOLD");
  const hasCds = hasCdsViolations(row);
  const hasRejected = row.tests?.some(
    (test) => test.pivot?.item_status === "Rejected",
  );

  return (
    isQCHold ||
    isCritical ||
    hasDeltaCheck ||
    isOnHold ||
    hasCds ||
    hasRejected ||
    status === "QC HOLD" ||
    status === "CRITICAL HOLD" ||
    status === "For Re-run"
  );
};

// Helper function to check if status is Printed or Released
const isPrintedOrReleased = (status) => {
  const statusUpper = status?.toUpperCase() || "";
  return (
    statusUpper.includes("PRINTED") ||
    statusUpper.includes("RELEASED") ||
    status === "Printed" ||
    status === "Released"
  );
};

const isRejectedStatus = (status) => {
  const statusUpper = status?.toUpperCase() || "";
  return statusUpper.includes("REJECTED");
};

// Active worklist requests (everything not completed)
const worklistRequests = computed(() => {
  let filtered = requests.value.filter((row) => {
    const status = getRowStatus(row);
    return !isPrintedOrReleased(status) && !isRejectedStatus(status);
  });

  // Apply reflex filter if enabled
  if (showOnlyReflex.value) {
    filtered = filtered.filter((row) => row.is_reflex);
  }

  return filtered;
});

// Completed/Released requests (Printed/Released)
const completedRequests = computed(() => {
  let filtered = requests.value.filter((row) => {
    const status = getRowStatus(row);
    return isPrintedOrReleased(status);
  });

  // Apply reflex filter if enabled
  if (showOnlyReflex.value) {
    filtered = filtered.filter((row) => row.is_reflex);
  }

  return filtered;
});

const rejectedRequests = computed(() => {
  let filtered = requests.value.filter((row) => {
    const status = getRowStatus(row);
    return isRejectedStatus(status);
  });

  // Apply reflex filter if enabled
  if (showOnlyReflex.value) {
    filtered = filtered.filter((row) => row.is_reflex);
  }

  return filtered;
});

// Helper function to get tab counts
const getTabCount = (tabKey) => {
  if (tabKey === "worklist") return worklistRequests.value.length;
  if (tabKey === "completed") return completedRequests.value.length;
  if (tabKey === "rejected") return rejectedRequests.value.length;
  return 0;
};

// Get violations for a row
const getViolations = (row) => {
  const violations = [];
  const status = getRowStatus(row);
  const isQCHold = isHematology.value && isRequestOnQCHold(row);
  const isCritical = isCriticalHold(row);
  const hasDeltaCheck = flaggedRequestIds.value.has(row.id);
  const statusUpper = status?.toUpperCase() || "";
  const isOnHold =
    statusUpper.includes("HOLD") ||
    status === "On Hold" ||
    statusUpper.includes("ON HOLD");

  // QC Hold violation
  if (isQCHold) {
    const equipName = getFailingEquipmentName(row);
    violations.push(`QC Hold: ${equipName}`);
  }

  // Critical Hold violation
  if (isCritical) {
    const criticalInfo = cdsViolations.value[row.id]?.critical;
    const suffix = criticalInfo?.is_acknowledged ? " - ACCEPTED" : "";
    violations.push(`Critical Values (Hold)${suffix}`);
  }

  // Delta Check violation
  if (hasDeltaCheck) {
    const deltaInfo = cdsViolations.value[row.id]?.delta;
    const suffix = deltaInfo?.is_acknowledged ? " - ACCEPTED" : "";

    // Get parameter count from completion detail
    const completionDetail = getCompletionDetail(row);
    const match = completionDetail.match(/(\d+)\/(\d+)/);
    if (match) {
      const paramCount = match[2]; // Total parameters
      violations.push(`Delta Check: ${paramCount} parameters flagged${suffix}`);
    } else {
      violations.push(`Delta Check flagged${suffix}`);
    }
  }

  // CDS Violation
  const cdsInfo = cdsViolations.value[row.id]?.cds;
  if (
    cdsInfo &&
    cdsInfo.triggered_rules &&
    cdsInfo.triggered_rules.length > 0
  ) {
    cdsInfo.triggered_rules.forEach((rule) => {
      let type = rule.is_blocking ? "BLOCKING" : "ALERT";
      if (rule.is_acknowledged) {
        type += " - ACKNOWLEDGED";
      }
      violations.push(`CDS [${type}] ${rule.rule_name}: ${rule.alert_message}`);
    });
  }

  // On Hold violation (if not already covered by QC Hold or Critical Hold)
  if (isOnHold && !isQCHold && !isCritical) {
    violations.push("Manual Hold");
  }

  // For Re-run violation
  if (status === "For Re-run") {
    violations.push("For Re-run");
  }

  const rejectedCount =
    row.tests?.filter((test) => test.pivot?.item_status === "Rejected")
      .length || 0;
  if (rejectedCount > 0) {
    violations.push(`Rejected: ${rejectedCount} test(s)`);
  }

  return violations;
};

// Helper for checking if a request has any CDS violations
const hasCdsViolations = (row) => {
  const cdsInfo = cdsViolations.value[row.id]?.cds;
  return !!(
    cdsInfo &&
    cdsInfo.triggered_rules &&
    cdsInfo.triggered_rules.length > 0
  );
};

// Helper for checking if a request has any blocking CDS violations
const hasBlockingCdsViolations = (row) => {
  const cdsInfo = cdsViolations.value[row.id]?.cds;
  return !!(cdsInfo && cdsInfo.has_blocking_rules);
};

// Check if a request has a delta flag
const hasDeltaFlag = (row) => {
  return flaggedRequestIds.value.has(row.id);
};

// --- Helper Functions ---
const calculateAge = (dob) => {
  if (!dob) return "N/A";

  try {
    const birthDate = parseISO(dob);
    const today = new Date();

    const years = differenceInYears(today, birthDate);
    const months = differenceInMonths(today, birthDate);
    const days = differenceInDays(today, birthDate);

    if (years >= 1) {
      return `${years} ${years === 1 ? "Year" : "Years"}`;
    } else if (months >= 1) {
      return `${months} ${months === 1 ? "Month" : "Months"}`;
    } else {
      return `${days} ${days === 1 ? "Day" : "Days"}`;
    }
  } catch (e) {
    console.error("Error calculating age:", e);
    return "Invalid Date";
  }
};

// --- Fetch Data ---
const fetchRequestsCore = async (forceRefresh = false) => {
  loading.value = true;
  try {
    if (forceRefresh) {
      staticCache.deltaFlags = { data: [], timestamp: 0 };
      staticCache.cdsViolations = { data: {}, timestamp: 0 };
      cdsViolations.value = {};
      flaggedRequestIds.value = new Set();
    }

    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
      search: searchQuery.value,
      patient_type: patientTypeFilter.value,
      category_id: props.categoryId,
    };

    const response = await api.get(`/results/category/${props.categoryId}`, {
      params,
    });
    if (!isMounted.value) return;
    requests.value = response.data.data;
    total.value = response.data.total;

    // Batch load additional data with delays to prevent 429
    await Promise.allSettled([loadDeltaFlags(), loadCdsViolations()]);

    // Run equipment enrichment separately as it makes many requests
    setTimeout(() => {
      enrichVisibleRowsEquipment();
    }, 500);
  } catch (error) {
    console.error("Error fetching requests:", error);
    ElMessage.error(`Failed to fetch ${props.categoryName} requests.`);
    requests.value = [];
    total.value = 0;
  } finally {
    loading.value = false;
  }
};

// Fetch Patient Types for the dropdown
const fetchPatientTypes = async () => {
  try {
    const response = await api.get("/patient-types"); // Assuming endpoint exists
    patientTypes.value = response.data || []; // Adjust based on actual API response structure
  } catch (error) {
    console.error("Error fetching patient types:", error);
    ElMessage.error("Could not load patient types for filtering.");
    patientTypes.value = []; // Ensure it's an array on error
  }
};

const fetchRequests = _.debounce(fetchRequestsCore, 500);

onUnmounted(() => {
  fetchRequests.cancel();
});

// QC equipment summary and helpers
const failingEquipmentIds = ref(new Set());
const equipmentIdToName = ref({});
const requestIdToEquipmentIds = ref({}); // cache of equipment ids per request
const singleEquipmentId = ref(null);

const loadEquipmentSummary = async () => {
  try {
    if (!isHematology.value) return;
    const catId = props.categoryId;
    const now = Date.now();

    if (
      staticCache.equipmentSummary[catId] &&
      now - staticCache.equipmentSummary[catId].timestamp < CACHE_TTL
    ) {
      console.log(`Using cached equipment summary for category: ${catId}`);
      const equipment = staticCache.equipmentSummary[catId].data;
      failingEquipmentIds.value = new Set(
        equipment.filter((eq) => eq.on_hold).map((eq) => eq.id),
      );
      equipmentIdToName.value = equipment.reduce((m, eq) => {
        m[eq.id] = eq.name;
        return m;
      }, {});
      singleEquipmentId.value = equipment.length === 1 ? equipment[0].id : null;
      return;
    }

    const res = await api.get("/qc-lockout/status-summary", {
      params: { test_category_id: catId },
    });
    if (!isMounted.value) return;
    const equipment = res.data?.equipment || [];

    // Update cache
    staticCache.equipmentSummary[catId] = {
      data: equipment,
      timestamp: now,
    };

    failingEquipmentIds.value = new Set(
      equipment.filter((eq) => eq.on_hold).map((eq) => eq.id),
    );
    equipmentIdToName.value = equipment.reduce((m, eq) => {
      m[eq.id] = eq.name;
      return m;
    }, {});
    singleEquipmentId.value = equipment.length === 1 ? equipment[0].id : null;
    console.log("[QC] Summary", {
      total: equipment.length,
      failing: Array.from(failingEquipmentIds.value),
      names: equipmentIdToName.value,
    });
  } catch (e) {
    if (
      e.response?.status === 429 &&
      staticCache.equipmentSummary[props.categoryId]
    ) {
      console.warn(
        `Rate limited while fetching equipment summary for category: ${props.categoryId}`,
      );
      const equipment = staticCache.equipmentSummary[props.categoryId].data;
      failingEquipmentIds.value = new Set(
        equipment.filter((eq) => eq.on_hold).map((eq) => eq.id),
      );
      equipmentIdToName.value = equipment.reduce((m, eq) => {
        m[eq.id] = eq.name;
        return m;
      }, {});
      singleEquipmentId.value = equipment.length === 1 ? equipment[0].id : null;
    }
    // do not block UI
  }
};

const extractEquipmentIdsFromRow = (row) => {
  const ids = new Set();
  // Use backend-provided equipment ids if present
  if (Array.isArray(row.equipment_ids) && row.equipment_ids.length > 0) {
    row.equipment_ids.forEach((id) => ids.add(Number(id)));
  }
  (row.tests || []).forEach((t) => {
    // Try common locations: pivot.equipment_id, t.equipment_id, test initial_instrument, test.parameters[*].equipment_id
    if (t.pivot?.equipment_id) ids.add(Number(t.pivot.equipment_id));
    if (t.equipment_id) ids.add(Number(t.equipment_id));
    if (t.initial_instrument) ids.add(Number(t.initial_instrument));
    (t.parameters || []).forEach((p) => {
      if (p.equipment_id) ids.add(Number(p.equipment_id));
      if (p.instrument) ids.add(Number(p.instrument));
    });
  });
  return Array.from(ids);
};

const getEquipmentIdsForRow = (row) => {
  const inline = extractEquipmentIdsFromRow(row);
  if (inline.length > 0) return inline;
  const cached = requestIdToEquipmentIds.value[row.id];
  if (Array.isArray(cached)) return cached;
  // Heuristic fallback: if only one equipment exists for this category, assume it
  if (singleEquipmentId.value) return [Number(singleEquipmentId.value)];
  return [];
};

const isRequestOnQCHold = (row) => {
  if (!isHematology.value) return false;
  if (!failingEquipmentIds.value.size) return false;
  const ids = getEquipmentIdsForRow(row);
  return ids.some((id) => failingEquipmentIds.value.has(id));
};

const getFailingEquipmentName = (row) => {
  const ids = getEquipmentIdsForRow(row).filter((id) =>
    failingEquipmentIds.value.has(id),
  );
  if (ids.length === 0) return "Equipment";
  const first = ids[0];
  return equipmentIdToName.value[first] || `Equipment #${first}`;
};

const getFailingEquipmentId = (row) => {
  const ids = getEquipmentIdsForRow(row);
  const match = ids.find((id) => failingEquipmentIds.value.has(id));
  return match || null;
};

const goToQCLog = (row) => {
  const equipmentId = getFailingEquipmentId(row);
  if (!equipmentId) return;
  router.push({
    name: "result-qc-log",
    query: {
      equipment_id: equipmentId,
      category_id: categoryId.value || props.categoryId,
      request_id: row.id,
    },
  });
};

// Pull details for current page rows missing equipment linkage and cache equipment ids
const enrichVisibleRowsEquipment = async () => {
  try {
    if (!isHematology.value) return;
    const rows = (requests.value || []).slice();
    const categoryEndpoint = (categoryName.value || "").toLowerCase();
    const collectEquipmentIdsDeep = (obj, out) => {
      if (!obj || typeof obj !== "object") return;
      // If we find common equipment fields
      if (Object.prototype.hasOwnProperty.call(obj, "equipment_id")) {
        const v = Number(obj.equipment_id);
        if (!Number.isNaN(v)) out.add(v);
      }
      if (Object.prototype.hasOwnProperty.call(obj, "instrument")) {
        const v = Number(obj.instrument);
        if (!Number.isNaN(v)) out.add(v);
      }
      // Recurse into arrays and objects
      if (Array.isArray(obj)) {
        obj.forEach((child) => collectEquipmentIdsDeep(child, out));
      } else {
        Object.keys(obj).forEach((k) => collectEquipmentIdsDeep(obj[k], out));
      }
    };
    // Process rows sequentially to avoid 429 Too Many Requests
    for (const row of rows) {
      // Check if we already have this in cache or extracted
      const haveIds = extractEquipmentIdsFromRow(row);
      if (haveIds.length > 0) {
        requestIdToEquipmentIds.value[row.id] = haveIds;
        continue;
      }

      const now = Date.now();
      if (
        staticCache.requestEquipment[row.id] &&
        now - staticCache.requestEquipment[row.id].timestamp < CACHE_TTL
      ) {
        requestIdToEquipmentIds.value[row.id] =
          staticCache.requestEquipment[row.id].data;
        continue;
      }

      if (requestIdToEquipmentIds.value[row.id]) continue;

      try {
        // Add a larger delay between requests to prevent 429
        await new Promise((resolve) => setTimeout(resolve, 200));

        // First, definitive endpoint for equipment ids
        try {
          const eqRes = await api.get(
            `/laboratory-requests/${row.id}/equipment`,
          );
          const eqIds = (eqRes.data?.equipment_ids || [])
            .map((n) => Number(n))
            .filter((n) => !Number.isNaN(n));

          // Update cache
          staticCache.requestEquipment[row.id] = {
            data: eqIds,
            timestamp: now,
          };

          if (eqIds.length > 0) {
            requestIdToEquipmentIds.value[row.id] = eqIds;
            continue; // Move to next row
          }
        } catch (e) {
          if (e.response?.status === 429) {
            console.warn("[QC] Rate limited, stopping enrichment");
            if (staticCache.requestEquipment[row.id]) {
              requestIdToEquipmentIds.value[row.id] =
                staticCache.requestEquipment[row.id].data;
            }
            break;
          }
          console.warn("[QC] Equipment endpoint failed for request", row.id, e);
        }

        // Fallback to details deep scan
        // const res = await api.get(`/results/${categoryEndpoint}/${row.id}`);
        // const details = res.data?.request_details || {};
        // const ids = new Set();
        // const tests = details.tests || [];
        // tests.forEach(t => {
        //   if (t.pivot?.equipment_id) ids.add(Number(t.pivot.equipment_id));
        //   if (t.equipment_id) ids.add(Number(t.equipment_id));
        //   if (t.initial_instrument) ids.add(Number(t.initial_instrument));
        //   const parameters = (t.parameters || details.testParameters?.[t.id] || []);
        //   if (Array.isArray(parameters)) {
        //     parameters.forEach(p => {
        //       if (p.equipment_id) ids.add(Number(p.equipment_id));
        //       if (p.instrument) ids.add(Number(p.instrument));
        //     });
        //   }
        // });
        collectEquipmentIdsDeep(details, ids);
        const eqIds = Array.from(ids);

        // Update cache
        staticCache.requestEquipment[row.id] = {
          data: eqIds,
          timestamp: now,
        };

        requestIdToEquipmentIds.value[row.id] = eqIds;
      } catch (e) {
        if (e.response?.status === 429) {
          if (staticCache.requestEquipment[row.id]) {
            requestIdToEquipmentIds.value[row.id] =
              staticCache.requestEquipment[row.id].data;
          }
          break;
        }
      }
    }
    // Force re-render so status cell recomputes
    requests.value = [...requests.value];
    console.log(
      "[QC] Enriched equipment ids per request:",
      requestIdToEquipmentIds.value,
    );
  } catch (_) {
    // non-fatal
  }
};

// Get active parameter IDs for a specific request based on its visible tests in the current category
const getActiveParametersForRequest = (row) => {
  const ids = new Set();
  (row.tests || []).forEach((t) => {
    const parameters =
      t.parameters || row.testParameters?.[t.id] || t.test_parameters || [];
    parameters.forEach((p) => {
      if (p.id) ids.add(Number(p.id));
      if (p.parameterId) ids.add(Number(p.parameterId));
      if (p.test_parameter_id) ids.add(Number(p.test_parameter_id));
    });
  });
  return ids;
};

// Load delta check flags for current page requests only
const loadDeltaFlags = async () => {
  try {
    loadingFlags.value = true;
    flaggedRequestIds.value = new Set();
    const requestIds = (requests.value || [])
      .map((r) => r.id)
      .filter((id) => !!id);
    if (requestIds.length === 0) return;

    const now = Date.now();
    let data = [];

    if (
      staticCache.deltaFlags.timestamp &&
      now - staticCache.deltaFlags.timestamp < CACHE_TTL
    ) {
      console.log("Using cached delta flags");
      data = staticCache.deltaFlags.data;
    } else {
      // Fetch flagged delta checks (paginate high to minimize follow-ups)
      const res = await api.get("/management-report/delta-checks", {
        params: {
          status: "flagged",
          per_page: 500,
          page: 1,
        },
      });
      data = res.data?.data || [];
      // Update cache
      staticCache.deltaFlags = {
        data: data,
        timestamp: now,
      };
    }

    // Build a quick lookup for current page requests
    const pageRequestSet = new Set(requestIds);
    data.forEach((dc) => {
      const lrid =
        dc?.test_result?.laboratory_request_id ||
        dc?.test_result?.laboratory_request?.id ||
        dc?.test_result?.laboratory_request_id;
      if (lrid && pageRequestSet.has(lrid)) {
        // Also check if the rule's parameter is active in the current row
        const row = requests.value.find((r) => r.id === lrid);
        if (row) {
          const activeParams = getActiveParametersForRequest(row);
          const testParamId =
            dc.test_parameter?.id ||
            dc.test_result?.test_parameter_id ||
            dc.test_parameter_id;
          if (testParamId && activeParams.has(Number(testParamId))) {
            flaggedRequestIds.value.add(lrid);
          }
        }
      }
    });
  } catch (e) {
    if (e.response?.status === 429 && staticCache.deltaFlags.timestamp) {
      console.warn("Rate limited while fetching delta flags, using cache");
      const data = staticCache.deltaFlags.data;
      const requestIds = (requests.value || [])
        .map((r) => r.id)
        .filter((id) => !!id);
      const pageRequestSet = new Set(requestIds);
      data.forEach((dc) => {
        const lrid =
          dc?.test_result?.laboratory_request_id ||
          dc?.test_result?.laboratory_request?.id ||
          dc?.test_result?.laboratory_request_id;
        if (lrid && pageRequestSet.has(lrid)) {
          const row = requests.value.find((r) => r.id === lrid);
          if (row) {
            const activeParams = getActiveParametersForRequest(row);
            const testParamId =
              dc.test_parameter?.id ||
              dc.test_result?.test_parameter_id ||
              dc.test_parameter_id;
            if (testParamId && activeParams.has(Number(testParamId))) {
              flaggedRequestIds.value.add(lrid);
            }
          }
        }
      });
      return;
    }
    console.error("Failed loading delta flags", e);
  } finally {
    loadingFlags.value = false;
  }
};

// Load CDS and other rule violations for current page requests
const loadCdsViolations = async () => {
  try {
    const requestIds = (requests.value || [])
      .map((r) => r.id)
      .filter((id) => !!id);
    if (requestIds.length === 0) return;

    const now = Date.now();
    // Use cache if available and not expired
    if (
      staticCache.ruleViolations.timestamp &&
      now - staticCache.ruleViolations.timestamp < CACHE_TTL
    ) {
      // Filter cache for current request IDs
      const filteredResults = {};
      requestIds.forEach((id) => {
        if (staticCache.ruleViolations.data[id]) {
          filteredResults[id] = staticCache.ruleViolations.data[id];
        }
      });

      if (Object.keys(filteredResults).length === requestIds.length) {
        console.log("Using cached rule violations");
        cdsViolations.value = { ...cdsViolations.value, ...filteredResults };
        return;
      }
    }

    const res = await api.post("/results/check-cds-bulk", {
      request_ids: requestIds,
    });

    const newResults = res.data?.results || {};

    // Filter the rules to match active parameters in each row
    const filteredResults = {};
    for (const [lrid, resultData] of Object.entries(newResults)) {
      const row = requests.value.find((r) => r.id === Number(lrid));
      if (row && resultData?.cds?.triggered_rules) {
        const activeParams = getActiveParametersForRequest(row);
        const filteredRules = resultData.cds.triggered_rules.filter((rule) =>
          activeParams.has(Number(rule.trigger_test_id)),
        );
        const hasBlocking = filteredRules.some((r) => r.is_blocking);

        filteredResults[lrid] = {
          ...resultData,
          cds: {
            ...resultData.cds,
            triggered_rules: filteredRules,
            has_blocking_rules: hasBlocking,
          },
        };
      } else {
        filteredResults[lrid] = resultData;
      }
    }

    // Update local state
    cdsViolations.value = { ...cdsViolations.value, ...filteredResults };

    // Update cache
    staticCache.ruleViolations = {
      data: { ...staticCache.ruleViolations.data, ...filteredResults },
      timestamp: now,
    };
  } catch (e) {
    console.error("Failed loading rule violations", e);
    if (e.response?.status === 429) {
      ElMessage.warning("Rule verification rate limited. Using cached data.");
      if (staticCache.ruleViolations.timestamp) {
        const requestIds = (requests.value || [])
          .map((r) => r.id)
          .filter((id) => !!id);
        const filteredResults = {};
        requestIds.forEach((id) => {
          if (staticCache.ruleViolations.data[id]) {
            filteredResults[id] = staticCache.ruleViolations.data[id];
          }
        });
        cdsViolations.value = { ...cdsViolations.value, ...filteredResults };
      }
    } else {
      ElMessage.error("Failed to verify rules. Some results may be blocked.");
    }
  }
};

const openDeltaCheck = (row) => {
  if (!row?.patient?.id) return;
  router.push({
    name: "result-delta-check",
    query: {
      patient_id: row.patient.id,
      from: "category",
      category_id: props.categoryId,
      category_name: props.categoryName,
    },
  });
};

const viewParentRequest = (parentId) => {
  viewResults(parentId);
};

const getParentRequestSampleId = (parentId) => {
  const parent = requests.value.find((r) => r.id === parentId);
  return parent ? parent.sample_id : `ID: ${parentId}`;
};

// --- Lifecycle ---
// (Already handled at top)

// --- Formatters ---
const formatPatientName = (row) => {
  if (!row.patient) return "N/A";
  return `${row.patient.last_name || ""}, ${row.patient.first_name || ""} ${row.patient.middle_name || ""}`.trim();
};

const formatDob = (row, column, cellValue) => {
  if (!cellValue) return "N/A";
  try {
    return format(parseISO(cellValue), "yyyy-MM-dd");
  } catch (e) {
    return "Invalid Date";
  }
};

const formatDateTime = (row, column, cellValue) => {
  if (!cellValue) return "N/A";
  try {
    return format(parseISO(cellValue), "MM/dd/yyyy p");
  } catch (e) {
    return "Invalid Date";
  }
};

const formatDateTimeShort = (row, column, cellValue) => {
  if (!cellValue) return "N/A";
  try {
    return format(parseISO(cellValue), "MM/dd/yyyy p");
  } catch (e) {
    return "Invalid Date";
  }
};

const formatLocation = (row) => {
  const ward = row.ward?.name || "";
  const room = row.room?.name || "";
  if (ward && room) {
    return `${ward} / ${room}`;
  }
  return ward || room || "-";
};

const getRejectedAt = (row) => {
  if (!row?.tests || row.tests.length === 0) return null;
  const times = row.tests
    .filter(
      (t) => t?.pivot?.item_status === "Rejected" && t?.pivot?.rejected_at,
    )
    .map((t) => t.pivot.rejected_at);
  if (times.length === 0) return null;
  const latest = times.sort((a, b) => new Date(b) - new Date(a))[0];
  return latest || null;
};

const getTestList = (row) => {
  return row[testListColumn.value];
};

// --- Status/Priority Styling ---
const getStatusType = (status) => {
  switch (status) {
    case "Pending Sample":
      return "info";
    case "Checked-in":
      return "warning";
    case "Validated":
      return "primary";
    case "Printed":
      return "success";
    default:
      return "default";
  }
};

const getPriorityType = (priority) => {
  switch (priority) {
    case "STAT":
      return "danger";
    case "Urgent":
      return "danger";
    default:
      return "secondary";
  }
};

const getPriority = (priority) => {
  switch (priority) {
    case "STAT":
      return "STAT";
    case "Urgent":
      return "Urgent";
    case "Routine":
      return "";
  }
};

const getPivotStatusType = (status) => {
  switch (status) {
    case "Pending":
      return "warning";
    case "Collected":
      return "primary";
    case "Send-out":
      return "primary";
    case "Parked":
      return "danger";
    case "Accepted":
      return "success";
    default:
      return "default";
  }
};

const getCompletionDetail = (row) => {
  if (!row.tests || row.tests.length === 0) return "";

  let totalParams = 0;
  let completedParams = 0;

  row.tests.forEach((test) => {
    if (test.parameter_count) {
      totalParams += test.parameter_count;
      completedParams += test.completed_parameter_count || 0;
    } else {
      totalParams++;
      if (
        test.pivot &&
        ["Processing Results", "Result Validated", "Result Printed"].includes(
          test.pivot.item_status,
        )
      ) {
        completedParams++;
      }
    }
  });

  return `${completedParams}/${totalParams} parameters complete`;
};

// Status pill styling
const getStatusPillClass = (status) => {
  const statusUpper = status?.toUpperCase() || "";
  if (statusUpper.includes("CRITICAL") || statusUpper.includes("HOLD")) {
    return "status-pill status-pill-red";
  }
  if (statusUpper.includes("QC HOLD")) {
    return "status-pill status-pill-red";
  }
  if (statusUpper.includes("REJECT")) {
    return "status-pill status-pill-red";
  }
  if (statusUpper.includes("CHECKED-IN")) {
    return "status-pill status-pill-orange";
  }
  if (
    statusUpper === "PRINTED" ||
    statusUpper.includes("PRINTED") ||
    statusUpper === "RELEASED" ||
    statusUpper.includes("RELEASED")
  ) {
    return "status-pill status-pill-green";
  }
  if (statusUpper === "VALIDATED" || statusUpper.includes("VALIDATED")) {
    return "status-pill status-pill-green";
  }
  if (
    statusUpper === "PENDING SAMPLE" ||
    statusUpper.includes("PENDING SAMPLE")
  ) {
    return "status-pill status-pill-default";
  }
  // Default to blue for routine statuses
  return "status-pill status-pill-blue";
};

// Helper for status display names
const getStatusDisplayName = (row) => {
  const status = getRowStatus(row);
  if (!status) return "N/A";

  // If in Completed tab and has violations, show "Final Release"
  if (activeTriageTab.value === "completed") {
    const violationCount = getViolations(row).length;
    if (violationCount > 0) {
      return "Final Release";
    }
  }

  if (status === "Result Validated") return "Validated";
  if (status === "Result Printed") return "Released";
  if (status === "Processing Results") return "Processing";
  if (status === "Printed") return "Released";
  return status;
};

const formatTestNames = (row) => {
  if (!row?.tests || row.tests.length === 0) return "No Tests";
  return row.tests
    .map(
      (test) =>
        `${test.name}${test.pivot?.item_status === "Rejected" ? " (Rejected)" : ""}`,
    )
    .join(", ");
};

// Row class name for styling
const getRowClassName = ({ row }) => {
  const status = getRowStatus(row);
  const isQCHold = isHematology.value && isRequestOnQCHold(row);
  const isCritical = isCriticalHold(row);
  const hasCds = hasCdsViolations(row);
  const hasBlockingCds = hasBlockingCdsViolations(row);
  const violationCount = getViolations(row).length;

  let classes = [];

  // Base violation class for background fill
  if (violationCount > 0) {
    classes.push("row-has-violation");
  }

  if (isCritical || status?.toUpperCase().includes("CRITICAL HOLD")) {
    classes.push("row-critical-hold");
  }

  if (hasBlockingCds) {
    classes.push("row-cds-blocked");
  } else if (hasCds) {
    classes.push("row-cds-triggered");
  }

  return classes.join(" ");
};

// Test abbreviation helper
const getTestAbbreviation = (testName) => {
  if (!testName) return "";
  // Common abbreviations mapping
  const abbreviations = {
    "complete blood count": "CBC",
    "complete blood count (cbc)": "CBC",
    cbc: "CBC",
    "comprehensive metabolic panel": "CMP",
    "basic metabolic panel": "BMP",
    "lipid panel": "Lipid",
    "liver function test": "LFT",
    "thyroid stimulating hormone": "TSH",
    "hemoglobin a1c": "HbA1c",
  };

  const lowerName = testName.toLowerCase();
  for (const [key, abbrev] of Object.entries(abbreviations)) {
    if (lowerName.includes(key)) {
      return abbrev;
    }
  }

  // Fallback: use first letters of words or first 3-4 chars
  const words = testName.split(" ");
  if (words.length > 1) {
    return words
      .map((w) => w[0])
      .join("")
      .toUpperCase()
      .substring(0, 4);
  }
  return testName.substring(0, 4).toUpperCase();
};

// Test progress helpers
const getTestProgressPercent = (test) => {
  if (test.parameter_count && test.parameter_count > 0) {
    const completed = test.completed_parameter_count || 0;
    return Math.round((completed / test.parameter_count) * 100);
  }
  // Fallback: check pivot status
  if (test.pivot) {
    if (
      ["Result Printed", "Result Validated"].includes(test.pivot.item_status)
    ) {
      return 100;
    }
    if (test.pivot.item_status === "Processing Results") {
      return 50;
    }
  }
  return 0;
};

const getTestProgressText = (test) => {
  if (test.parameter_count && test.parameter_count > 0) {
    const completed = test.completed_parameter_count || 0;
    return `${completed}/${test.parameter_count}`;
  }
  return "";
};

// Export to sheets
const exportToSheets = () => {
  ElMessage.info("Export to Sheets functionality to be implemented");
  // TODO: Implement export functionality
};

// Use generic category endpoint for all categories
// The backend now supports: /results/category/{categoryId}/request/{requestId}
// This works for any category, not just hematology and chemistry

// --- Actions ---
const viewResults = async (requestId) => {
  console.log(`View results for request ID: ${requestId}`);

  if (!requestId) {
    ElMessage.warning("Invalid Request ID.");
    return;
  }

  const now = Date.now();
  if (
    staticCache.requestDetails[requestId] &&
    now - staticCache.requestDetails[requestId].timestamp < CACHE_TTL
  ) {
    console.log(`Using cached details for request: ${requestId}`);
    const cached = staticCache.requestDetails[requestId].data;
    selectedRequestDetails.value = cached.request_details;
    verifierList.value = cached.user_lists?.verifiers || [];
    performerList.value = cached.user_lists?.performers || [];
    pathologistList.value = cached.user_lists?.pathologists || [];
    isViewingDetails.value = true;
    return;
  }

  detailsLoading.value = true;
  selectedRequestDetails.value = null;
  try {
    const response = await api.get(
      `/results/category/${props.categoryId}/request/${requestId}`,
    );

    // Update cache
    staticCache.requestDetails[requestId] = {
      data: response.data,
      timestamp: now,
    };

    selectedRequestDetails.value = response.data.request_details;
    verifierList.value = response.data.user_lists?.verifiers || [];
    performerList.value = response.data.user_lists?.performers || [];
    pathologistList.value = response.data.user_lists?.pathologists || [];

    isViewingDetails.value = true;
    ElMessage.success("Request details loaded.");
  } catch (error) {
    if (
      error.response?.status === 429 &&
      staticCache.requestDetails[requestId]
    ) {
      console.warn(
        `Rate limited while fetching details for request ${requestId}`,
      );
      const cached = staticCache.requestDetails[requestId].data;
      selectedRequestDetails.value = cached.request_details;
      verifierList.value = cached.user_lists?.verifiers || [];
      performerList.value = cached.user_lists?.performers || [];
      pathologistList.value = cached.user_lists?.pathologists || [];
      isViewingDetails.value = true;
      return;
    }
    console.error(`Error fetching details for request ${requestId}:`, error);
    ElMessage.error(
      error.response?.data?.message || "Failed to load request details.",
    );
    isViewingDetails.value = false;
  } finally {
    detailsLoading.value = false;
  }
};

// Refresh request details (used by UniversalResultModal for reflex test approval)
const refreshRequestDetails = async () => {
  if (selectedRequestDetails.value?.id) {
    console.log("Refreshing request details after reflex test approval");
    const requestId = selectedRequestDetails.value.id;

    // Clear cache for this request to force fresh fetch
    delete staticCache.requestDetails[requestId];

    // Re-fetch the request details
    detailsLoading.value = true;
    try {
      const response = await api.get(
        `/results/category/${props.categoryId}/request/${requestId}`,
      );

      staticCache.requestDetails[requestId] = {
        data: response.data,
        timestamp: Date.now(),
      };

      selectedRequestDetails.value = response.data.request_details;
      // Also refresh user lists
      verifierList.value = response.data.user_lists?.verifiers || [];
      performerList.value = response.data.user_lists?.performers || [];
      pathologistList.value = response.data.user_lists?.pathologists || [];

      console.log("Request details refreshed successfully");
      ElMessage.success("Request details updated");
    } catch (error) {
      console.error("Error refreshing request details:", error);
      ElMessage.error("Failed to refresh request details");
    } finally {
      detailsLoading.value = false;
    }
  }
};

// --- Actions ---
const viewResultsTesting = async (requestId) => {
  console.log(`View results for request ID: ${requestId}`);

  if (!requestId) {
    ElMessage.warning("Invalid Request ID.");
    return;
  }

  const now = Date.now();
  if (
    staticCache.requestDetails[requestId] &&
    now - staticCache.requestDetails[requestId].timestamp < CACHE_TTL
  ) {
    console.log(`Using cached details for request: ${requestId}`);
    const cached = staticCache.requestDetails[requestId].data;
    selectedRequestDetails.value = cached.request_details;
    verifierList.value = cached.user_lists?.verifiers || [];
    performerList.value = cached.user_lists?.performers || [];
    pathologistList.value = cached.user_lists?.pathologists || [];
    isViewingDetails.value = true;
    return;
  }

  detailsLoading.value = true;
  selectedRequestDetails.value = null;
  try {
    const response = await api.get(
      `/results/category/${props.categoryId}/request/${requestId}`,
    );

    // Update cache
    staticCache.requestDetails[requestId] = {
      data: response.data,
      timestamp: now,
    };

    selectedRequestDetails.value = response.data.request_details;
    verifierList.value = response.data.user_lists?.verifiers || [];
    performerList.value = response.data.user_lists?.performers || [];
    pathologistList.value = response.data.user_lists?.pathologists || [];

    isViewingDetails.value = true;
    ElMessage.success("Request details loaded.");
  } catch (error) {
    if (
      error.response?.status === 429 &&
      staticCache.requestDetails[requestId]
    ) {
      console.warn(
        `Rate limited while fetching details for request ${requestId}`,
      );
      const cached = staticCache.requestDetails[requestId].data;
      selectedRequestDetails.value = cached.request_details;
      verifierList.value = cached.user_lists?.verifiers || [];
      performerList.value = cached.user_lists?.performers || [];
      pathologistList.value = cached.user_lists?.pathologists || [];
      isViewingDetails.value = true;
      return;
    }
    console.error(`Error fetching details for request ${requestId}:`, error);
    ElMessage.error(
      error.response?.data?.message || "Failed to load request details.",
    );
    isViewingDetails.value = false;
  } finally {
    detailsLoading.value = false;
  }
};

const printReport = async (requestId) => {
  console.log(`Print report for request ID: ${requestId}`);

  if (!requestId) {
    ElMessage.warning("Invalid Request ID.");
    return;
  }

  try {
    loading.value = true;

    const request = requests.value.find((req) => req.id === requestId);

    if (request && getRowStatus(request) === "Validated") {
      await api.put(`/laboratory-requests/${requestId}/status`, {
        status: "Result Printed",
      });

      ElMessage.success("Status updated to Printed");
      await fetchRequests();
    }

    openPrintView(requestId);
  } catch (error) {
    console.error("Error printing report:", error);
    ElMessage.error(
      `Failed to print report: ${error.response?.data?.message || error.message}`,
    );
  } finally {
    loading.value = false;
  }
};

const openPrintView = async (requestId) => {
  // Similar to Hematology.vue print functionality
  // This would need to be implemented based on the specific modal component
  ElMessage.info("Print functionality to be implemented");
};

const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    currentPage.value = 1;
    fetchRequests();
  }, 500); // 500ms debounce
};

const goBack = () => {
  router.push({ name: "result-management-dashboard" });
};

const handleSaveResults = (requestId, resultsData) => {
  console.log("Save results action triggered from modal:", {
    requestId,
    resultsData,
  });
  ElMessage.success("Results saved successfully");
  fetchRequests(true);
};

const handlePrintResults = (requestId, resultsData) => {
  console.log("Print & Release action triggered from modal:", {
    requestId,
    resultsData,
  });
  ElMessage.success("Results printed and released successfully");
  fetchRequests(true);
};

const handleValidateResults = (requestId, resultsData) => {
  console.log("Validate & Release action triggered from modal:", {
    requestId,
    resultsData,
  });
  ElMessage.success("Results validated successfully");
  fetchRequests(true);
};

// --- Pagination Handlers ---
const handleSizeChange = (val) => {
  pageSize.value = val;
  currentPage.value = 1;
  fetchRequests();
};

const handleCurrentChange = (val) => {
  currentPage.value = val;
  fetchRequests();
};

const closeDetailsView = () => {
  isViewingDetails.value = false;
  selectedRequestDetails.value = null;
};

// Watchers
watch(patientTypeFilter, () => {
  currentPage.value = 1;
  fetchRequests();
});
watch(activeTriageTab, () => {
  // Reset to first page when changing tabs
  currentPage.value = 1;
});
watch(showOnlyReflex, () => {
  // Reset to first page when filter changes
  currentPage.value = 1;
});

// Handler for reflex filter toggle
const handleReflexFilterChange = () => {
  // The computed properties will automatically react to showOnlyReflex changes
  // No additional action needed, but we could emit an event if parent component needs to know
  console.log("Reflex filter changed:", showOnlyReflex.value);
};
</script>

<style scoped>
.container {
  display: flex;
  height: calc(100vh - 100px); /* Adjust for header/footer if any */
  gap: var(--spacing-lg);
  overflow: hidden; /* Prevent scrolling on the main container */
  color: var(--color-text-primary);
  width: 100%;
}

.header-title-area {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-title {
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  margin: 0;
}
.page-subtitle {
  font-size: var(--font-size-sm);
  margin: 0;
}

.content-card {
  border: none;
  box-shadow: var(--shadow-sm);
}

.el-tag {
  text-transform: capitalize;
}

.test-item {
  display: flex;
  align-items: center;
  margin-bottom: var(--spacing-xs);
}

.status-badge {
  margin-left: var(--spacing-sm);
}

.progress-detail {
  font-size: var(--font-size-xs);
  color: var(--color-text-secondary);
  margin-top: var(--spacing-xs);
  text-align: center;
}

/* Triage Tabs */
.triage-tabs-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-md);
  gap: var(--spacing-md);
  width: 100%;
  background: var(--color-bg-secondary);
  padding: 8px;
  border-radius: 4px;
}

.triage-tabs {
  display: flex;
  gap: var(--spacing-sm);
  flex: 1;
}

.triage-tab {
  position: relative;
  padding: var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-secondary);
  color: var(--color-text-secondary);
  border-radius: var(--border-radius-md);
  cursor: pointer;
  font-size: var(--font-size-md);
  font-weight: 500;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.triage-tab:hover {
  color: var(--color-primary);
}

.triage-tab.active {
  font-weight: 600;
  background-color: var(--color-primary-light); /* Very soft light blue */
  color: var(--color-primary); /* Bold primary blue text */
  border-bottom: 2px solid var(--color-primary); /* Soft blue border */
  border-radius: 0px;
}

.triage-check-box {
  font-size: var(--font-size-md) !important;
  font-weight: 300;
}
.tab-badge {
  margin-left: var(--spacing-xs);
}

.export-button {
  flex-shrink: 0;
}

/* Status Pills */
.status-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--spacing-xs);
}

.status-pill-container {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.status-pill {
  display: inline-flex;
  align-items: center;
  padding: 0px 2px;
  border-radius: var(--border-radius-lg);
  font-size: 8px;
  font-weight: 600;
  text-transform: uppercase;
  white-space: nowrap;
}

.status-icon {
  font-size: var(--font-size-xs);
  line-height: 1;
}

.status-pill-red {
  background: var(--color-danger-light);
  color: var(--color-danger);
  border: 1px solid var(--color-danger);
}

.status-pill-orange {
  background: var(--color-warning-light);
  color: var(--color-warning);
  border: 1px solid var(--color-warning);
}

.status-pill-green {
  background: var(--color-success-light);
  color: var(--color-success);
  border: 1px solid var(--color-success);
}

.status-pill-blue {
  background: var(--color-primary-light);
  color: var(--color-primary);
  border: 1px solid var(--color-primary);
}

.status-pill-default {
  background: var(--color-bg-secondary);
  color: var(--color-text-primary);
  border: 1px solid var(--color-dark);
}

.delta-icon-status {
  display: inline-block;
  color: var(--color-danger);
  font-weight: 700;
  font-size: 18px;
  cursor: pointer;
  transition: color 0.3s;
  padding: 2px 4px;
}

.delta-icon-status:hover {
  color: var(--color-danger);
}

/* Specimen ID */
.specimen-id-cell {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
}

.specimen-id-text {
  font-weight: 700;
  font-size: var(--font-size-base);
  color: var(--color-text-primary);
}
.specimen-id-text.specimen-id-critical {
  color: var(--color-danger);
}

.exemption-icon {
  padding: 2px;
  animation: pulse-attention 2s infinite;
  font-size: 12px;
  cursor: help;
}

@keyframes pulse-attention {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.2);
    opacity: 0.8;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

.exemption-tooltip-content {
  padding: 4px;
  line-height: 1.5;
  max-width: 400px;
}

.violation-item {
  color: var(--color-text-regular) !important;
  margin-bottom: 4px;
  font-size: 12px;
  white-space: pre-wrap;
}

.violation-item:last-child {
  margin-bottom: 0;
}

.action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.physician-name {
  font-size: 13px;
  color: var(--color-text-regular);
}

.patient-meta {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

/* Test Requested Column */
.test-requested-cell {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.test-item-compact {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.test-name-row {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
}

.cds-list-tag {
  font-weight: bold;
  font-size: 12px;
  padding: 0 4px;
  height: 18px;
  line-height: 16px;
  cursor: help;
}

.cds-tooltip-content {
  padding: 4px;
  max-width: 300px;
}

.tooltip-header {
  font-weight: bold;
  margin-bottom: 4px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
  padding-bottom: 2px;
}

.tooltip-item {
  margin-bottom: 2px;
  font-size: 12px;
}

.test-critical-icon {
  color: var(--color-danger);
  font-size: var(--font-size-xs);
  line-height: 1;
}

.test-full-name {
  font-size: var(--font-size-sm);
  color: var(--color-text-primary);
}

.test-abbreviation-brackets {
  color: var(--color-text-tertiary);
  font-weight: 500;
}

.test-progress-bar {
  width: 100%;
  height: 4px;
  background: var(--color-border-secondary);
  border-radius: var(--border-radius-sm);
  overflow: hidden;
}

.test-progress-bar-critical {
  background: var(--color-danger-light);
}

.test-progress-fill {
  height: 100%;
  background: var(--color-primary);
  transition: width var(--transition-normal);
}

.test-progress-fill-critical {
  background: var(--color-danger);
}

.test-progress-text {
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
}

.section-header {
  display: flex;
  flex-direction: column;
  margin-bottom: 15px;
}

.table-title {
  font-size: 16px;
  font-weight: 600;
  margin: 0;
  color: var(--color-text-primary);
}

.table-subtitle {
  font-size: 12px;
  color: var(--color-text-secondary);
}

/* Table 1: Pending Review - Red Background and Border */
.pending-review-card {
  background-color: var(--color-danger-light) !important;
  border: 1px solid var(--color-danger) !important;
  border-radius: var(--border-radius-md);
}

.pending-review-card :deep(.el-card__body) {
  background-color: var(--color-danger-light);
  padding: var(--spacing-lg);
}

.pending-review-table :deep(.el-table) {
  background-color: var(--color-danger-light);
}

.pending-review-table :deep(.el-table__header-wrapper) {
  background-color: var(--color-danger-light);
}

.pending-review-table :deep(.el-table__body-wrapper) {
  background-color: var(--color-danger-light);
}

.pending-review-table :deep(.el-table__row) {
  background-color: var(--color-danger-light);
}

.pending-review-table :deep(.el-table__row:hover) {
  background-color: var(--color-danger-light);
  filter: brightness(0.95);
}

.pending-review-table :deep(.el-table th) {
  background-color: var(--color-danger-light);
  border-bottom: 1px solid var(--color-danger);
}

.pending-review-table :deep(.el-table td) {
  border-bottom: 1px solid var(--color-danger-light);
  filter: brightness(0.9);
}

.pending-review-table :deep(.el-table__border) {
  border: 1px solid var(--color-danger);
}

/* Violations Cell */
.violations-cell {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  padding: var(--spacing-xs) 0;
}

.violation-item {
  font-size: var(--font-size-xs);
  color: var(--color-danger);
  font-weight: 500;
  padding: 2px 0;
}

.no-violations-text {
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
  font-style: italic;
}

.specimen-id-link {
  color: var(--color-primary);
  text-decoration: none;
  cursor: pointer;
  font-weight: 700;
}

.specimen-id-link:hover {
  text-decoration: underline;
}

/* Patient Info Column Styles */
.patient-info-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.patient-name-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.patient-name {
  font-weight: 600;
  color: var(--color-text-primary);
  font-size: 13px;
}

.patient-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--color-text-secondary);
}

.meta-divider {
  color: var(--color-border-primary);
  font-weight: 300;
}

.location-text {
  font-size: 13px;
  color: var(--color-text-secondary);
}

.date-text {
  font-family: "Monaco", "Menlo", "Ubuntu Mono", "Consolas", monospace;
  font-size: 12px;
  color: var(--color-text-tertiary);
}

/* Reflex Badge Styling */
.reflex-badge {
  background-color: #f56c6c !important;
  color: white !important;
  padding: 2px 8px !important;
  border-radius: 4px !important;
  font-size: 12px !important;
  font-weight: bold !important;
  border: none !important;
}

/* Reflex Origin Cell */
.reflex-origin-cell {
  display: flex;
  align-items: center;
}

.text-muted {
  color: var(--color-text-tertiary);
  font-size: 12px;
}

.header-area {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-md);
  border-bottom: 1px solid var(--color-border-secondary);
}

.card {
  background-color: var(--color-bg-primary);
  border-radius: var(--border-radius-md);
  padding: var(--spacing-lg);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border-secondary);
  display: flex;
  flex-direction: column;
}
</style>
