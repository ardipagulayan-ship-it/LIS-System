<template>
  <div class="category-results">
    <div class="page-header">
      <h2 class="page-title">{{ props.categoryName.toUpperCase() }} TEST REQUESTS</h2>
      <div class="header-actions">
        <el-input
          v-model="searchQuery"
          placeholder="Search by ID or patient name"
          class="search-input"
          clearable
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>


        </el-input>
        <el-select v-model="patientTypeFilter" placeholder="Filter by patient type" clearable>
          <el-option label="Inpatient" value="inpatient" />
          <el-option label="Outpatient" value="outpatient" />
          <el-option label="Emergency" value="emergency" />
          <el-option label="Labor" value="labor" />
          <el-option label="OPD" value="opd" />
        </el-select>
        <el-button @click="goBack" :icon="ArrowLeft">
          Back to Dashboard
        </el-button>
      </div>
    </div>

    <!-- Triage Tabs -->
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
            class="tab-badge"
          />
        </button>
      </div>
      <!-- <el-button type="default" :icon="Document" @click="exportToSheets" class="export-button">
        Export to Sheets
      </el-button> -->
    </div>

    <!-- All Requests Tab: Two Tables -->
    <template v-if="activeTriageTab === 'all'">
      <!-- Table 1: Pending Review (with Violations) -->
      <div class="table-section">
        <h3 class="table-title">With Exemptions</h3>
        <el-card class="content-card pending-review-card">
          <el-table 
            :data="pendingReviewRequests" 
            v-loading="loading" 
            style="width: 100%" 
            :row-class-name="getRowClassName"
            class="pending-review-table"
          >
          <el-table-column label="Specimen ID" width="150">
            <template #default="{ row }">
              <div class="specimen-id-cell">
                <span :class="['specimen-id-text', { 'specimen-id-critical': isCriticalHold(row) }]">
                  <a class="specimen-id-link" @click="viewResults(row.id)">{{ row.sample_id }}</a>
                </span>
                <el-badge :value="row.priority_level" :type="getPriorityType(row.priority_level)" size="small" style="margin-left: 5px;" />
              </div>
            </template>
          </el-table-column>
          <el-table-column label="Patient Name" min-width="220">
            <template #default="{ row }">
              <span>{{ formatPatientName(row) }}</span>
              <el-tooltip
                v-if="flaggedRequestIds.has(row.id)"
                content="Delta check flagged - view details"
                placement="top"
              >
                <span class="delta-icon-status" @click.stop="openDeltaCheck(row)">Δ</span>
              </el-tooltip>
            </template>
          </el-table-column>
          <el-table-column :prop="statusColumn" label="Status" width="120" align="center">
            <template #default="{ row }">
              <div class="status-cell">
                <div class="status-pill-container">
                  <template v-if="isHematology && isRequestOnQCHold(row)">
                    <span 
                      class="status-pill status-pill-red" 
                      @click.stop="goToQCLog(row)"
                      style="cursor:pointer"
                    >
                      <span class="status-icon">●</span>
                      ON HOLD
                    </span>
                  </template>
                  <template v-else-if="isCriticalHold(row)">
                    <span class="status-pill status-pill-red">
                      <span class="status-icon">●</span>
                      ON HOLD
                    </span>
                    <el-tooltip
                      v-if="flaggedRequestIds.has(row.id)"
                      content="Delta check flagged - view details"
                      placement="top"
                    >
                      <span class="delta-icon-status" @click.stop="openDeltaCheck(row)">Δ</span>
                    </el-tooltip>
                  </template>
                  <template v-else>
                    <span :class="getStatusPillClass(getRowStatus(row))">
                      <span class="status-icon">●</span>
                      {{ getRowStatus(row) }}
                    </span>
                  </template>
                </div>
              </div>
            </template>
          </el-table-column>
          <el-table-column label="(Violations)" min-width="200">
            <template #default="{ row }">
              <div class="violations-cell">
                <div v-for="(violation, index) in getViolations(row)" :key="index" class="violation-item">
                  {{ violation }}
                </div>
                <span v-if="getViolations(row).length === 0" class="no-violations-text">No violations</span>
              </div>
            </template>
          </el-table-column>
          <el-table-column :label="`Tests`" min-width="200">
            <template #default="{ row }">
              <div v-if="row.tests && row.tests.length" class="test-requested-cell">
                <div v-for="test in row.tests" :key="test.id" class="test-item-compact">
                  <div class="test-name-row">
                    <span 
                      v-if="isCriticalHold(row)" 
                      class="test-critical-icon"
                    >●</span>
                    <span class="test-full-name">
                      {{ test.name }} 
                    </span>
                  </div>
                  <div 
                    class="test-progress-bar" 
                    :class="{ 'test-progress-bar-critical': isCriticalHold(row) }"
                  >
                    <div 
                      class="test-progress-fill" 
                      :class="{ 'test-progress-fill-critical': isCriticalHold(row) }"
                      :style="{ width: getTestProgressPercent(test) + '%' }"
                    ></div>
                  </div>
                  <div class="progress-detail">
                    {{ getCompletionDetail(row) }}
                  </div>
                </div>
              </div>
              <span v-else class="test-full-name">{{ getTestList(row) || `No ${props.categoryName} Tests` }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="patient.date_of_birth" label="DoB" width="120" :formatter="formatDob" />
          <el-table-column label="Age" prop="patient_age" width="150">
            <template #default="scope">
              <span>{{ calculateAge(scope.row.patient.date_of_birth) }} | {{ scope.row.patient.gender }}</span>
            </template>
          </el-table-column>
          <el-table-column label="Location" width="200">
            <template #default="{ row }">
              <span>{{ formatLocation(row) }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="request_date" label="Request Date" width="120" :formatter="formatDateTimeShort" />
          <el-table-column label="Actions" width="120" align="center" fixed="right">
            <template #default="{ row }">
              <el-tooltip 
                v-if="isHematology && isRequestOnQCHold(row)" 
                content="QC Hold - open details is blocked for failing analyzer" 
                placement="top"
              >
                <el-button type="primary" size="small" :icon="View" @click="viewResults(row.id)">View</el-button>
              </el-tooltip>
              <el-button 
                v-else 
                type="primary" 
                size="small" 
                :icon="View" 
                @click="viewResults(row.id)"
              >
                View
              </el-button>
            </template>
          </el-table-column>
        </el-table>

        <div class="pagination-container">
          <el-pagination
            v-if="pendingReviewRequests.length > 0"
            v-model:current-page="currentPage"
            v-model:page-size="pageSize"
            :page-sizes="[15, 30, 50, 100]"
            :total="pendingReviewRequests.length"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
          />
          </div>
        </el-card>
      </div>

      <!-- Table 2: No Violations (unchanged) -->
      <div class="table-section">
        <h3 class="table-title">No Exemptions</h3>
        <el-card class="content-card">
          <el-table 
            :data="noViolationsRequests" 
            v-loading="loading" 
            style="width: 100%" 
            :row-class-name="getRowClassName"
          >
          <el-table-column label="Specimen ID" width="150">
            <template #default="{ row }">
              <div class="specimen-id-cell">
                <span :class="['specimen-id-text', { 'specimen-id-critical': isCriticalHold(row) }]">
                  <a class="specimen-id-link" @click="viewResults(row.id)">{{ row.sample_id }}</a>
                </span>
                <el-badge :value="row.priority_level" :type="getPriorityType(row.priority_level)" size="small" style="margin-left: 5px;" />
              </div>
            </template>
          </el-table-column>
          <el-table-column label="Patient Name" min-width="220">
            <template #default="{ row }">
              <span>{{ formatPatientName(row) }}</span>
              <el-tooltip
                v-if="flaggedRequestIds.has(row.id)"
                content="Delta check flagged - view details"
                placement="top"
              >
                <span class="delta-icon-status" @click.stop="openDeltaCheck(row)">Δ</span>
              </el-tooltip>
            </template>
          </el-table-column>
          <el-table-column :prop="statusColumn" label="Status" width="220" align="center">
            <template #default="{ row }">
              <div class="status-cell">
                <div class="status-pill-container">
                  <template v-if="isHematology && isRequestOnQCHold(row)">
                    <span 
                      class="status-pill status-pill-red" 
                      @click.stop="goToQCLog(row)"
                      style="cursor:pointer"
                    >
                      <span class="status-icon">●</span>
                      ON HOLD
                    </span>
                  </template>
                  <template v-else-if="isCriticalHold(row)">
                    <span class="status-pill status-pill-red">
                      <span class="status-icon">●</span>
                      ON HOLD
                    </span>
                    <el-tooltip
                      v-if="flaggedRequestIds.has(row.id)"
                      content="Delta check flagged - view details"
                      placement="top"
                    >
                      <span class="delta-icon-status" @click.stop="openDeltaCheck(row)">Δ</span>
                    </el-tooltip>
                  </template>
                  <template v-else>
                    <span :class="getStatusPillClass(getRowStatus(row))">
                      <span class="status-icon">●</span>
                      {{ getRowStatus(row) }}
                    </span>
                  </template>
                </div>
              </div>
            </template>
          </el-table-column>
          <el-table-column :label="`Tests`" min-width="200">
            <template #default="{ row }">
              <div v-if="row.tests && row.tests.length" class="test-requested-cell">
                <div v-for="test in row.tests" :key="test.id" class="test-item-compact">
                  <div class="test-name-row">
                    <span 
                      v-if="isCriticalHold(row)" 
                      class="test-critical-icon"
                    >●</span>
                    <span class="test-full-name">
                      {{ test.name }} 
                    </span>
                  </div>
                  <div 
                    class="test-progress-bar" 
                    :class="{ 'test-progress-bar-critical': isCriticalHold(row) }"
                  >
                    <div 
                      class="test-progress-fill" 
                      :class="{ 'test-progress-fill-critical': isCriticalHold(row) }"
                      :style="{ width: getTestProgressPercent(test) + '%' }"
                    ></div>
                  </div>
                  <div class="progress-detail">
                  {{ getCompletionDetail(row) }}
                </div>
                </div>
              </div>
              <span v-else class="test-full-name">{{ getTestList(row) || `No ${props.categoryName} Tests` }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="patient.date_of_birth" label="DoB" width="120" :formatter="formatDob" />
          <el-table-column label="Age" prop="patient_age" width="150">
            <template #default="scope">
              <span>{{ calculateAge(scope.row.patient.date_of_birth) }} | {{ scope.row.patient.gender }}</span>
            </template>
          </el-table-column>
          <el-table-column label="Location" width="200">
            <template #default="{ row }">
              <span>{{ formatLocation(row) }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="request_date" label="Request Date" width="120" :formatter="formatDateTimeShort" />
          <el-table-column label="Actions" width="120" align="center" fixed="right">
            <template #default="{ row }">
              <el-tooltip 
                v-if="isHematology && isRequestOnQCHold(row)" 
                content="QC Hold - open details is blocked for failing analyzer" 
                placement="top"
              >
                <el-button type="primary" size="small" :icon="View" @click="viewResults(row.id)">View</el-button>
              </el-tooltip>
              <el-button 
                v-else 
                type="primary" 
                size="small" 
                :icon="View" 
                @click="viewResults(row.id)"
              >
                View
              </el-button>
            </template>
          </el-table-column>
        </el-table>

        <div class="pagination-container">
          <el-pagination
            v-if="noViolationsRequests.length > 0"
            v-model:current-page="currentPage"
            v-model:page-size="pageSize"
            :page-sizes="[15, 30, 50, 100]"
            :total="noViolationsRequests.length"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
          />
          </div>
        </el-card>
      </div>
    </template>

    <!-- Pending Review Tab: One Table with Violations -->
    <template v-if="activeTriageTab === 'pending_review'">
      <div class="table-section">
        <h3 class="table-title">Pending Review</h3>
        <el-card class="content-card pending-review-card">
          <el-table 
            :data="pendingReviewRequests" 
            v-loading="loading" 
            style="width: 100%" 
            :row-class-name="getRowClassName"
            class="pending-review-table"
          >
            <el-table-column label="Specimen ID" width="150">
              <template #default="{ row }">
                <div class="specimen-id-cell">
                  <span :class="['specimen-id-text', { 'specimen-id-critical': isCriticalHold(row) }]">
                    <a class="specimen-id-link" @click="viewResults(row.id)">{{ row.sample_id }}</a>
                  </span>
                  <el-badge :value="row.priority_level" :type="getPriorityType(row.priority_level)" size="small" style="margin-left: 5px;" />
                </div>
              </template>
            </el-table-column>
            <el-table-column label="Patient Name" min-width="220">
              <template #default="{ row }">
                <span>{{ formatPatientName(row) }}</span>
                <el-tooltip
                  v-if="flaggedRequestIds.has(row.id)"
                  content="Delta check flagged - view details"
                  placement="top"
                >
                  <span class="delta-icon-status" @click.stop="openDeltaCheck(row)">Δ</span>
                </el-tooltip>
              </template>
            </el-table-column>
            <el-table-column :prop="statusColumn" label="Status" width="220" align="center">
              <template #default="{ row }">
                <div class="status-cell">
                  <div class="status-pill-container">
                    <template v-if="isHematology && isRequestOnQCHold(row)">
                      <span 
                        class="status-pill status-pill-orange" 
                        @click.stop="goToQCLog(row)"
                        style="cursor:pointer"
                      >
                        <span class="status-icon">●</span>
                        ON HOLD
                      </span>
                    </template>
                    <template v-else-if="isCriticalHold(row)">
                      <span class="status-pill status-pill-red">
                        <span class="status-icon">●</span>
                        ON HOLD
                      </span>
                      <el-tooltip
                        v-if="flaggedRequestIds.has(row.id)"
                        content="Delta check flagged - view details"
                        placement="top"
                      >
                        <span class="delta-icon-status" @click.stop="openDeltaCheck(row)">Δ</span>
                      </el-tooltip>
                    </template>
                    <template v-else>
                      <span :class="getStatusPillClass(getRowStatus(row))">
                        <span class="status-icon">●</span>
                        {{ getRowStatus(row) }}
                      </span>
                    </template>
                  </div>
                </div>
              </template>
            </el-table-column>
            <el-table-column label="(Violations)" min-width="200">
              <template #default="{ row }">
                <div class="violations-cell">
                  <div v-for="(violation, index) in getViolations(row)" :key="index" class="violation-item">
                    {{ violation }}
                  </div>
                  <span v-if="getViolations(row).length === 0" class="no-violations-text">No violations</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column :label="`${props.categoryName} Tests`" min-width="300">
              <template #default="{ row }">
                <div v-if="row.tests && row.tests.length" class="test-requested-cell">
                  <div v-for="test in row.tests" :key="test.id" class="test-item-compact">
                    <div class="test-name-row">
                      <span 
                        v-if="isCriticalHold(row)" 
                        class="test-critical-icon"
                      >●</span>
                      <span class="test-full-name">
                        {{ test.name }} 
                      </span>
                    </div>
                    <div 
                      class="test-progress-bar" 
                      :class="{ 'test-progress-bar-critical': isCriticalHold(row) }"
                    >
                      <div 
                        class="test-progress-fill" 
                        :class="{ 'test-progress-fill-critical': isCriticalHold(row) }"
                        :style="{ width: getTestProgressPercent(test) + '%' }"
                      ></div>
                    </div>
                    <div class="progress-detail">
                      {{ getCompletionDetail(row) }}
                    </div>
                  </div>
                </div>
                <span v-else class="test-full-name">{{ getTestList(row) || `No ${props.categoryName} Tests` }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="patient.date_of_birth" label="DoB" width="120" :formatter="formatDob" />
            <el-table-column label="Age" prop="patient_age" width="150">
              <template #default="scope">
                <span>{{ calculateAge(scope.row.patient.date_of_birth) }} | {{ scope.row.patient.gender }}</span>
              </template>
            </el-table-column>
            <el-table-column label="Location" width="200">
              <template #default="{ row }">
                <span>{{ formatLocation(row) }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="request_date" label="Request Date" width="120" :formatter="formatDateTimeShort" />
            <el-table-column label="Actions" width="120" align="center" fixed="right">
              <template #default="{ row }">
                <el-tooltip 
                  v-if="isHematology && isRequestOnQCHold(row)" 
                  content="QC Hold - open details is blocked for failing analyzer" 
                  placement="top"
                >
                  <el-button type="primary" size="small" :icon="View" @click="viewResults(row.id)">View</el-button>
                </el-tooltip>
                <el-button 
                  v-else 
                  type="primary" 
                  size="small" 
                  :icon="View" 
                  @click="viewResults(row.id)"
                >
                  View
                </el-button>
              </template>
            </el-table-column>
          </el-table>

          <div class="pagination-container">
            <el-pagination
              v-if="pendingReviewRequests.length > 0"
              v-model:current-page="currentPage"
              v-model:page-size="pageSize"
              :page-sizes="[15, 30, 50, 100]"
              :total="pendingReviewRequests.length"
              layout="total, sizes, prev, pager, next, jumper"
              @size-change="handleSizeChange"
              @current-change="handleCurrentChange"
            />
          </div>
        </el-card>
      </div>
    </template>

    <!-- Waiting for Analysis Tab: One Table -->
    <template v-if="activeTriageTab === 'waiting_analysis'">
      <div class="table-section">
        <h3 class="table-title">Waiting for Analysis</h3>
        <el-card class="content-card">
          <el-table 
            :data="waitingAnalysisRequests" 
            v-loading="loading" 
            style="width: 100%" 
            :row-class-name="getRowClassName"
          >
            <el-table-column label="Specimen ID" width="150">
              <template #default="{ row }">
                <div class="specimen-id-cell">
                  <span :class="['specimen-id-text', { 'specimen-id-critical': isCriticalHold(row) }]">
                    <a class="specimen-id-link" @click="viewResults(row.id)">{{ row.sample_id }}</a>
                  </span> 
                  <el-badge :value="row.priority_level" :type="getPriorityType(row.priority_level)" size="small" style="margin-left: 5px;" />
                </div>
              </template>
            </el-table-column>
            <el-table-column label="Patient Name" min-width="220">
              <template #default="{ row }">
                <span>{{ formatPatientName(row) }}</span>
                <el-tooltip
                  v-if="flaggedRequestIds.has(row.id)"
                  content="Delta check flagged - view details"
                  placement="top"
                >
                  <span class="delta-icon-status" @click.stop="openDeltaCheck(row)">Δ</span>
                </el-tooltip>
              </template>
            </el-table-column>
            <el-table-column :prop="statusColumn" label="Status" width="220" align="center">
              <template #default="{ row }">
                <div class="status-cell">
                  <div class="status-pill-container">
                    <template v-if="isHematology && isRequestOnQCHold(row)">
                      <span 
                        class="status-pill status-pill-orange" 
                        @click.stop="goToQCLog(row)"
                        style="cursor:pointer"
                      >
                        <span class="status-icon">●</span>
                        ON HOLD
                      </span>
                    </template>
                    <template v-else-if="isCriticalHold(row)">
                      <span class="status-pill status-pill-red">
                        <span class="status-icon">●</span>
                        ON HOLD
                      </span>
                      <el-tooltip
                        v-if="flaggedRequestIds.has(row.id)"
                        content="Delta check flagged - view details"
                        placement="top"
                      >
                        <span class="delta-icon-status" @click.stop="openDeltaCheck(row)">Δ</span>
                      </el-tooltip>
                    </template>
                    <template v-else>
                      <span :class="getStatusPillClass(getRowStatus(row))">
                        <span class="status-icon">●</span>
                        {{ getRowStatus(row) }}
                      </span>
                    </template>
                  </div>
                </div>
              </template>
            </el-table-column>
            <el-table-column :label="`${props.categoryName} Tests`" min-width="300">
              <template #default="{ row }">
                <div v-if="row.tests && row.tests.length" class="test-requested-cell">
                  <div v-for="test in row.tests" :key="test.id" class="test-item-compact">
                    <div class="test-name-row">
                      <span 
                        v-if="isCriticalHold(row)" 
                        class="test-critical-icon"
                      >●</span>
                      <span class="test-full-name">
                        {{ test.name }} 
                      </span>
                    </div>
                    <div 
                      class="test-progress-bar" 
                      :class="{ 'test-progress-bar-critical': isCriticalHold(row) }"
                    >
                      <div 
                        class="test-progress-fill" 
                        :class="{ 'test-progress-fill-critical': isCriticalHold(row) }"
                        :style="{ width: getTestProgressPercent(test) + '%' }"
                      ></div>
                    </div>
                    <div class="progress-detail">
                      {{ getCompletionDetail(row) }}
                    </div>
                  </div>
                </div>
                <span v-else class="test-full-name">{{ getTestList(row) || `No ${props.categoryName} Tests` }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="patient.date_of_birth" label="DoB" width="120" :formatter="formatDob" />
            <el-table-column label="Age" prop="patient_age" width="150">
              <template #default="scope">
                <span>{{ calculateAge(scope.row.patient.date_of_birth) }} | {{ scope.row.patient.gender }}</span>
              </template>
            </el-table-column>
            <el-table-column label="Location" width="200">
              <template #default="{ row }">
                <span>{{ formatLocation(row) }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="request_date" label="Request Date" width="120" :formatter="formatDateTimeShort" />
            <el-table-column label="Actions" width="120" align="center" fixed="right">
              <template #default="{ row }">
                <el-tooltip 
                  v-if="isHematology && isRequestOnQCHold(row)" 
                  content="QC Hold - open details is blocked for failing analyzer" 
                  placement="top"
                >
                  <el-button type="primary" size="small" :icon="View" @click="viewResults(row.id)">View</el-button>
                </el-tooltip>
                <el-button 
                  v-else 
                  type="primary" 
                  size="small" 
                  :icon="View" 
                  @click="viewResults(row.id)"
                >
                  View
                </el-button>
              </template>
            </el-table-column>
          </el-table>

          <div class="pagination-container">
            <el-pagination
              v-if="waitingAnalysisRequests.length > 0"
              v-model:current-page="currentPage"
              v-model:page-size="pageSize"
              :page-sizes="[15, 30, 50, 100]"
              :total="waitingAnalysisRequests.length"
              layout="total, sizes, prev, pager, next, jumper"
              @size-change="handleSizeChange"
              @current-change="handleCurrentChange"
            />
          </div>
        </el-card>
      </div>
    </template>

    <!-- Completed/Released Tab: One Table -->
    <template v-if="activeTriageTab === 'completed'">
      <div class="table-section">
        <h3 class="table-title">Completed/Released</h3>
        <el-card class="content-card">
          <el-table 
            :data="completedRequests" 
            v-loading="loading" 
            style="width: 100%" 
            :row-class-name="getRowClassName"
          >
            <el-table-column label="Specimen ID" width="150">
              <template #default="{ row }">
                <div class="specimen-id-cell">
                  <span :class="['specimen-id-text', { 'specimen-id-critical': isCriticalHold(row) }]">
                    <a class="specimen-id-link" @click="viewResults(row.id)">{{ row.sample_id }}</a>
                  </span>
                  <el-badge :value="row.priority_level" :type="getPriorityType(row.priority_level)" size="small" style="margin-left: 5px;" />
                </div>
              </template>
            </el-table-column>
            <el-table-column label="Patient Name" min-width="220">
              <template #default="{ row }">
                <span>{{ formatPatientName(row) }}</span>
                <el-tooltip
                  v-if="flaggedRequestIds.has(row.id)"
                  content="Delta check flagged - view details"
                  placement="top"
                >
                  <span class="delta-icon-status" @click.stop="openDeltaCheck(row)">Δ</span>
                </el-tooltip>
              </template>
            </el-table-column>
            <el-table-column :prop="statusColumn" label="Status" width="220" align="center">
              <template #default="{ row }">
                <div class="status-cell">
                  <div class="status-pill-container">
                    <template v-if="isHematology && isRequestOnQCHold(row)">
                      <span 
                        class="status-pill status-pill-orange" 
                        @click.stop="goToQCLog(row)"
                        style="cursor:pointer"
                      >
                        <span class="status-icon">●</span>
                        QC HOLD
                      </span>
                    </template>
                    <template v-else-if="isCriticalHold(row)">
                      <span class="status-pill status-pill-red">
                        <span class="status-icon">●</span>
                        CRITICAL HOLD
                      </span>
                      <el-tooltip
                        v-if="flaggedRequestIds.has(row.id)"
                        content="Delta check flagged - view details"
                        placement="top"
                      >
                        <span class="delta-icon-status" @click.stop="openDeltaCheck(row)">Δ</span>
                      </el-tooltip>
                    </template>
                    <template v-else>
                      <span :class="getStatusPillClass(getRowStatus(row))">
                        <span class="status-icon">●</span>
                        {{ getRowStatus(row) }}
                      </span>
                    </template>
                  </div>
                </div>
              </template>
            </el-table-column>
            <el-table-column :label="`${props.categoryName} Tests`" min-width="300">
              <template #default="{ row }">
                <div v-if="row.tests && row.tests.length" class="test-requested-cell">
                  <div v-for="test in row.tests" :key="test.id" class="test-item-compact">
                    <div class="test-name-row">
                      <span 
                        v-if="isCriticalHold(row)" 
                        class="test-critical-icon"
                      >●</span>
                      <span class="test-full-name">
                        {{ test.name }} 
                      </span>
                    </div>
                    <div 
                      class="test-progress-bar" 
                      :class="{ 'test-progress-bar-critical': isCriticalHold(row) }"
                    >
                      <div 
                        class="test-progress-fill" 
                        :class="{ 'test-progress-fill-critical': isCriticalHold(row) }"
                        :style="{ width: getTestProgressPercent(test) + '%' }"
                      ></div>
                    </div>
                    <div class="progress-detail">
                      {{ getCompletionDetail(row) }}
                    </div>
                  </div>
                </div>
                <span v-else class="test-full-name">{{ getTestList(row) || `No ${props.categoryName} Tests` }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="patient.date_of_birth" label="DoB" width="120" :formatter="formatDob" />
            <el-table-column label="Age" prop="patient_age" width="150">
              <template #default="scope">
                <span>{{ calculateAge(scope.row.patient.date_of_birth) }} | {{ scope.row.patient.gender }}</span>
              </template>
            </el-table-column>
            <el-table-column label="Location" width="200">
              <template #default="{ row }">
                <span>{{ formatLocation(row) }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="request_date" label="Request Date" width="120" :formatter="formatDateTimeShort" />
            <el-table-column label="Actions" width="120" align="center" fixed="right">
              <template #default="{ row }">
                <el-tooltip 
                  v-if="isHematology && isRequestOnQCHold(row)" 
                  content="QC Hold - open details is blocked for failing analyzer" 
                  placement="top"
                >
                  <el-button type="primary" size="small" :icon="View" @click="viewResults(row.id)">View</el-button>
                </el-tooltip>
                <el-button 
                  v-else 
                  type="primary" 
                  size="small" 
                  :icon="View" 
                  @click="viewResults(row.id)"
                >
                  View
                </el-button>
              </template>
            </el-table-column>
          </el-table>

          <div class="pagination-container">
            <el-pagination
              v-if="completedRequests.length > 0"
              v-model:current-page="currentPage"
              v-model:page-size="pageSize"
              :page-sizes="[15, 30, 50, 100]"
              :total="completedRequests.length"
              layout="total, sizes, prev, pager, next, jumper"
              @size-change="handleSizeChange"
              @current-change="handleCurrentChange"
            />
          </div>
        </el-card>
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
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { ElTable, ElTableColumn, ElButton, ElCard, ElPagination, ElTag, ElMessage, ElInput, ElSelect, ElOption, ElIcon, ElTooltip } from 'element-plus';
import { View, Printer, Search, ArrowLeft, WarningFilled, Document } from '@element-plus/icons-vue';
import { format, parseISO, differenceInYears, differenceInMonths, differenceInDays } from 'date-fns';
import api from '@/utils/axios';
import { useRouter, useRoute } from 'vue-router';
import UniversalResultModal from '@/components/modals/result/UniversalResultModal.vue';

const router = useRouter();
const route = useRoute();

const props = defineProps({
  categoryId: {
    type: [String, Number],
    required: true
  },
  categoryName: {
    type: String,
    required: true
  }
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
const searchQuery = ref('');
const patientTypeFilter = ref('');
const flaggedRequestIds = ref(new Set());
const activeTriageTab = ref('all');

// Triage tabs configuration
const triageTabs = [
  { key: 'all', label: 'All Requests' },

  { key: 'pending_review', label: 'Pending Review' },
  { key: 'waiting_analysis', label: 'Waiting for Analysis' },
  { key: 'completed', label: 'Completed/Released' }
];

// --- State for Viewing Details ---
const isViewingDetails = ref(false);
const selectedRequestDetails = ref(null);
const verifierList = ref([]);
const performerList = ref([]);
const pathologistList = ref([]);
const detailsLoading = ref(false);

// --- Computed Properties ---
const categoryId = computed(() => parseInt(props.categoryId) || parseInt(route.params.categoryId));
const categoryName = computed(() => props.categoryName || route.params.categoryName);
//const statusColumn = computed(() => `${categoryName.value.toLowerCase()}_status`);
const statusColumn = computed(() => {
  // Normalize category name: lowercase, replace spaces with underscores, handle hyphens
  const normalized = props.categoryName.toLowerCase().replace(/\s+/g, '_').replace(/-/g, '_');
  return `${normalized}_status`;
});
const testListColumn = computed(() => `${categoryName.value.toLowerCase()}_test_list`);
const isHematology = computed(() => `${props.categoryName}`.toLowerCase().includes('hematology'));

// Helper function to get row status with fallback
const getRowStatus = (row) => {
  // Try the computed status column first
  if (row[statusColumn.value]) {
    return row[statusColumn.value];
  }
  
  // Fallback: try alternative formats
  const categoryNameLower = props.categoryName.toLowerCase();
  const alternatives = [
    `${categoryNameLower.replace(/\s+/g, '_')}_status`,
    `${categoryNameLower.replace(/\s+/g, '-')}_status`,
    `${categoryNameLower.replace(/-/g, '_')}_status`,
    `${categoryNameLower.replace(/_/g, '-')}_status`,
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
  if (!row.tests || row.tests.length === 0) return 'Unknown';
  
  const hasPrinted = row.tests.some(test => 
    test.pivot?.item_status === 'Result Printed'
  );
  const hasValidated = row.tests.some(test => 
    test.pivot?.item_status === 'Result Validated' || 
    test.pivot?.item_status === 'Result Printed'
  );
  const hasAccepted = row.tests.some(test => 
    ['Accepted', 'Collected', 'Send-out', 'Parked'].includes(test.pivot?.item_status)
  );
  const hasResults = row.tests.some(test => 
              test.pivot?.item_status === 'Processing Results'
            );

  const hasCheckedIn = row.tests.some(test => 
    test.pivot?.item_status === 'Checked-in'
  );
  const hasForRerun = row.tests.some(test => 
    test.pivot?.item_status === 'For Re-run'
  );

  if (hasPrinted) return 'Printed';
  if (hasValidated) return 'Validated';
  if (hasAccepted) return 'Pending Sample';
  if (hasCheckedIn) return 'Checked-in';
  if (hasResults) return 'Processing Results';
  if (hasForRerun) return 'For Re-run';
          
            
            return 'Unknown';
};

// Helper function to check if row is on critical hold
const isCriticalHold = (row) => {
  const status = getRowStatus(row);
  return status?.toUpperCase().includes('CRITICAL HOLD') || false;
};

// Filtered requests based on triage tab
const filteredRequests = computed(() => {
  if (activeTriageTab.value === 'all') {
    return requests.value;
  }
  
  return requests.value.filter(row => {
    const status = getRowStatus(row);
    const isQCHold = isHematology.value && isRequestOnQCHold(row);
    const isCritical = isCriticalHold(row);
    
    switch (activeTriageTab.value) {
      case 'pending_review':
        // QC HOLD, Critical Hold, or similar statuses
        return isQCHold || isCritical || status === 'QC HOLD' || status === 'CRITICAL HOLD' || status === 'For Re-run';
      case 'waiting_analysis':
        // Accessioned but not yet run (Pending Sample, Checked-in, etc.)
        return ['Pending Sample', 'Checked-in', 'Accepted', 'Collected'].includes(status);
      case 'completed':
        // Completed/Released (Printed, Validated)
        return ['Printed', 'Validated'].includes(status);

      default:
        return true;
    }
  });
});

// Helper function to check if a row has violations
const hasViolations = (row) => {
  const status = getRowStatus(row);
  const isQCHold = isHematology.value && isRequestOnQCHold(row);
  const isCritical = isCriticalHold(row);
  const hasDeltaCheck = flaggedRequestIds.value.has(row.id);
  const statusUpper = status?.toUpperCase() || '';
  const isOnHold = statusUpper.includes('HOLD') || status === 'On Hold' || statusUpper.includes('ON HOLD');
  
  return isQCHold || isCritical || hasDeltaCheck || isOnHold || 
         status === 'QC HOLD' || status === 'CRITICAL HOLD' || status === 'For Re-run';
};

// Helper function to check if status is Printed or Released
const isPrintedOrReleased = (status) => {
  const statusUpper = status?.toUpperCase() || '';
  return statusUpper.includes('PRINTED') || statusUpper.includes('RELEASED') || 
         status === 'Printed' || status === 'Released';
};

// Pending review requests (with violations)
const pendingReviewRequests = computed(() => {
  return requests.value.filter(row => {
    return hasViolations(row);
  });
});

// No violations requests (for "All Requests" tab - excludes Printed/Released)
const noViolationsRequests = computed(() => {
  return requests.value.filter(row => {
    const status = getRowStatus(row);
    // Exclude if has violations OR if Printed/Released
    return !hasViolations(row) && !isPrintedOrReleased(status);
  });
});

// Waiting for Analysis requests (Processing Results, Validated/Verified, Checked In)
const waitingAnalysisRequests = computed(() => {
  return requests.value.filter(row => {
    const status = getRowStatus(row);
    const statusUpper = status?.toUpperCase() || '';
    return statusUpper.includes('PROCESSING RESULTS') || 
           statusUpper.includes('VERIFIED') || 
           statusUpper.includes('VALIDATED') ||
           statusUpper.includes('CHECKED-IN') ||
           status === 'Processing Results' ||
           status === 'Verified' ||
           status === 'Validated' ||
           status === 'Checked-in';
  });
});

// Completed/Released requests (Printed/Released)
const completedRequests = computed(() => {
  return requests.value.filter(row => {
    const status = getRowStatus(row);
    return isPrintedOrReleased(status);
  });
});



// Get violations for a row
const getViolations = (row) => {
  const violations = [];
  const status = getRowStatus(row);
  const isQCHold = isHematology.value && isRequestOnQCHold(row);
  const isCritical = isCriticalHold(row);
  const hasDeltaCheck = flaggedRequestIds.value.has(row.id);
  const statusUpper = status?.toUpperCase() || '';
  const isOnHold = statusUpper.includes('HOLD') || status === 'On Hold' || statusUpper.includes('ON HOLD');
  
  // QC Hold violation
  if (isQCHold) {
    violations.push('QC Hold');
  }
  
  // Critical Hold violation
  if (isCritical) {
    violations.push('Critical Hold');
  }
  
  // Delta Check violation
  if (hasDeltaCheck) {
    // Get parameter count from completion detail
    const completionDetail = getCompletionDetail(row);
    const match = completionDetail.match(/(\d+)\/(\d+)/);
    if (match) {
      const paramCount = match[2]; // Total parameters
      violations.push(`Delta Check: ${paramCount} parameters`);
    } else {
      violations.push('Delta Check: parameters');
    }
  }
  
  // CDS Violation (if any exist in the data)
  // This would need to be implemented based on actual data structure
  // For now, we'll check if there are any CDS-related flags in the row
  // You can add CDS violation detection here when the data structure is available
  
  // Critical Values violation
  if (isCritical) {
    violations.push('Critical Values');
  }
  
  // On Hold violation (if not already covered by QC Hold or Critical Hold)
  if (isOnHold && !isQCHold && !isCritical) {
    violations.push('On Hold');
  }
  
  // For Re-run violation
  if (status === 'For Re-run') {
    violations.push('For Re-run');
  }

  return violations;
};

// --- Helper Functions ---
const calculateAge = (dob) => {
  if (!dob) return 'N/A';
  
  try {
    const birthDate = parseISO(dob);
    const today = new Date();
    
    const years = differenceInYears(today, birthDate);
    const months = differenceInMonths(today, birthDate);
    const days = differenceInDays(today, birthDate);
    
    if (years >= 1) {
      return `${years} ${years === 1 ? 'year' : 'years'}`;
    } else if (months >= 1) {
      return `${months} ${months === 1 ? 'month' : 'months'}`;
    } else {
      return `${days} ${days === 1 ? 'day' : 'days'}`;
    }
  } catch (e) {
    console.error('Error calculating age:', e);
    return 'Invalid Date';
  }
};

// --- Fetch Data ---
const fetchRequests = async () => {
  loading.value = true;
  try {
    const params = {
      page: currentPage.value,
      per_page: pageSize.value,
      search: searchQuery.value,
      patient_type: patientTypeFilter.value,
      category_id: props.categoryId
    };
    
    const response = await api.get(`/results/category/${props.categoryId}`, { params });
    requests.value = response.data.data;
    total.value = response.data.total;
    await loadDeltaFlags();
  } catch (error) {
    console.error("Error fetching requests:", error);
    ElMessage.error(`Failed to fetch ${props.categoryName} requests.`);
    requests.value = [];
    total.value = 0;
    // After loading new rows, enrich analyzers for visible rows
    await enrichVisibleRowsEquipment();
  } finally {
    loading.value = false;
  }
};

// QC equipment summary and helpers
const failingEquipmentIds = ref(new Set());
const equipmentIdToName = ref({});
const requestIdToEquipmentIds = ref({}); // cache of equipment ids per request
const singleEquipmentId = ref(null);

const loadEquipmentSummary = async () => {
  try {
    if (!isHematology.value) return;
    const res = await api.get('/qc-lockout/status-summary', { params: { test_category_id: props.categoryId } });
    const equipment = res.data?.equipment || [];
    failingEquipmentIds.value = new Set(equipment.filter(eq => eq.on_hold).map(eq => eq.id));
    equipmentIdToName.value = equipment.reduce((m, eq) => { m[eq.id] = eq.name; return m; }, {});
    singleEquipmentId.value = equipment.length === 1 ? equipment[0].id : null;
    console.log('[QC] Summary', {
      total: equipment.length,
      failing: Array.from(failingEquipmentIds.value),
      names: equipmentIdToName.value
    });
  } catch (e) {
    // do not block UI
  }
};

const extractEquipmentIdsFromRow = (row) => {
  const ids = new Set();
  // Use backend-provided equipment ids if present
  if (Array.isArray(row.equipment_ids) && row.equipment_ids.length > 0) {
    row.equipment_ids.forEach(id => ids.add(Number(id)));
  }
  (row.tests || []).forEach(t => {
    // Try common locations: pivot.equipment_id, t.equipment_id, test initial_instrument, test.parameters[*].equipment_id
    if (t.pivot?.equipment_id) ids.add(Number(t.pivot.equipment_id));
    if (t.equipment_id) ids.add(Number(t.equipment_id));
    if (t.initial_instrument) ids.add(Number(t.initial_instrument));
    (t.parameters || []).forEach(p => {
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
  return ids.some(id => failingEquipmentIds.value.has(id));
};

const getFailingEquipmentName = (row) => {
  const ids = getEquipmentIdsForRow(row).filter(id => failingEquipmentIds.value.has(id));
  if (ids.length === 0) return 'Equipment';
  const first = ids[0];
  return equipmentIdToName.value[first] || `Equipment #${first}`;
};

const getFailingEquipmentId = (row) => {
  const ids = getEquipmentIdsForRow(row);
  const match = ids.find(id => failingEquipmentIds.value.has(id));
  return match || null;
};

const goToQCLog = (row) => {
  const equipmentId = getFailingEquipmentId(row);
  if (!equipmentId) return;
  router.push({ name: 'result-qc-log', query: { equipment_id: equipmentId, category_id: categoryId.value || props.categoryId, request_id: row.id } });
};

// Pull details for current page rows missing equipment linkage and cache equipment ids
const enrichVisibleRowsEquipment = async () => {
  try {
    if (!isHematology.value) return;
    const rows = (requests.value || []).slice();
    const categoryEndpoint = props.categoryName.toLowerCase();
    const collectEquipmentIdsDeep = (obj, out) => {
      if (!obj || typeof obj !== 'object') return;
      // If we find common equipment fields
      if (Object.prototype.hasOwnProperty.call(obj, 'equipment_id')) {
        const v = Number(obj.equipment_id);
        if (!Number.isNaN(v)) out.add(v);
      }
      if (Object.prototype.hasOwnProperty.call(obj, 'instrument')) {
        const v = Number(obj.instrument);
        if (!Number.isNaN(v)) out.add(v);
      }
      // Recurse into arrays and objects
      if (Array.isArray(obj)) {
        obj.forEach(child => collectEquipmentIdsDeep(child, out));
      } else {
        Object.keys(obj).forEach(k => collectEquipmentIdsDeep(obj[k], out));
      }
    };
    const fetches = rows.map(async (row) => {
      const haveIds = extractEquipmentIdsFromRow(row);
      if (haveIds.length > 0) {
        requestIdToEquipmentIds.value[row.id] = haveIds;
        return;
      }
      if (requestIdToEquipmentIds.value[row.id]) return; // already cached
      try {
        // First, definitive endpoint for equipment ids
        try {
          const eqRes = await api.get(`/laboratory-requests/${row.id}/equipment`);
          const eqIds = (eqRes.data?.equipment_ids || []).map(n => Number(n)).filter(n => !Number.isNaN(n));
          if (eqIds.length > 0) {
            requestIdToEquipmentIds.value[row.id] = eqIds;
            return;
          }
        } catch (e) {
          console.warn('[QC] Equipment endpoint failed for request', row.id, e);
        }

        // Fallback to details deep scan
        const res = await api.get(`/results/${categoryEndpoint}/${row.id}`);
        const details = res.data?.request_details || {};
        const ids = new Set();
        const tests = details.tests || [];
        tests.forEach(t => {
          if (t.pivot?.equipment_id) ids.add(Number(t.pivot.equipment_id));
          if (t.equipment_id) ids.add(Number(t.equipment_id));
          if (t.initial_instrument) ids.add(Number(t.initial_instrument));
          const parameters = (t.parameters || details.testParameters?.[t.id] || []);
          if (Array.isArray(parameters)) {
            parameters.forEach(p => {
              if (p.equipment_id) ids.add(Number(p.equipment_id));
              if (p.instrument) ids.add(Number(p.instrument));
            });
          }
        });
        collectEquipmentIdsDeep(details, ids);
        requestIdToEquipmentIds.value[row.id] = Array.from(ids);
      } catch (_) {
        // ignore per-row errors
      }
    });
    await Promise.all(fetches);
    // Force re-render so status cell recomputes
    requests.value = [...requests.value];
    console.log('[QC] Enriched equipment ids per request:', requestIdToEquipmentIds.value);
  } catch (_) {
    // non-fatal
  }
};

// Load delta check flags for current page requests only
const loadDeltaFlags = async () => {
  try {
    loadingFlags.value = true;
    flaggedRequestIds.value = new Set();
    const requestIds = (requests.value || []).map(r => r.id).filter(id => !!id);
    if (requestIds.length === 0) return;

    // Fetch flagged delta checks (paginate high to minimize follow-ups)
    const res = await api.get('/management-report/delta-checks', {
      params: {
        status: 'flagged',
        per_page: 500,
        page: 1,
      }
    });
    const data = res.data?.data || [];
    // Build a quick lookup for current page requests
    const pageRequestSet = new Set(requestIds);
    data.forEach(dc => {
      const lrid = dc?.test_result?.laboratory_request_id
        || dc?.test_result?.laboratory_request?.id
        || dc?.test_result?.laboratory_request_id;
      if (lrid && pageRequestSet.has(lrid)) {
        flaggedRequestIds.value.add(lrid);
      }
    });
  } catch (e) {
    console.error('Failed loading delta flags', e);
  } finally {
    loadingFlags.value = false;
  }
};

const openDeltaCheck = (row) => {
  if (!row?.patient?.id) return;
  router.push({
    name: 'result-delta-check',
    query: { patient_id: row.patient.id, from: 'category', category_id: props.categoryId, category_name: props.categoryName }
  });
};

// --- Lifecycle ---
onMounted(() => {
  fetchRequests();
  loadEquipmentSummary();
  // After initial load, try to enrich equipment ids for current page
  setTimeout(() => { enrichVisibleRowsEquipment(); }, 0);
});

// --- Formatters ---
const formatPatientName = (row) => {
  if (!row.patient) return 'N/A';
  return `${row.patient.last_name || ''}, ${row.patient.first_name || ''} ${row.patient.middle_name || ''}`.trim();
};

const formatDob = (row, column, cellValue) => {
  if (!cellValue) return 'N/A';
  try {
    return format(parseISO(cellValue), 'yyyy-MM-dd');
  } catch (e) {
    return 'Invalid Date';
  }
};

const formatDateTime = (row, column, cellValue) => {
  if (!cellValue) return 'N/A';
  try {
    return format(parseISO(cellValue), 'MM/dd/yyyy p');
  } catch (e) {
    return 'Invalid Date';
  }
};

const formatDateTimeShort = (row, column, cellValue) => {
  if (!cellValue) return 'N/A';
  try {
    return format(parseISO(cellValue), 'MM/dd HH:mm');
  } catch (e) {
    return 'Invalid Date';
  }
};

const formatLocation = (row) => {
  const ward = row.ward?.name || '';
  const room = row.room?.name || '';
  if (ward && room) {
    return `${ward} / ${room}`;
  }
  return ward || room || '-';
};

const getTestList = (row) => {
  return row[testListColumn.value];
};

// --- Status/Priority Styling ---
const getStatusType = (status) => {
    switch (status) {
        case 'Pending Sample': return 'info';
        case 'Checked-in': return 'warning';
        case 'Validated': return 'primary';
        case 'Printed': return 'success';
        default: return 'default';
    }
};

const getPriorityType = (priority) => {
    switch (priority) {
        case 'STAT': return 'danger';
        case 'Urgent': return 'warning';
        case 'Routine': return 'primary';
        default: return 'info';
    }
};

const getPivotStatusType = (status) => {
  switch (status) {
    case 'Pending': return 'warning';
    case 'Collected': return 'primary';
    case 'Send-out': return 'primary';
    case 'Parked': return 'danger';
    case 'Accepted': return 'success';
    default: return 'default';
  }
};

const getCompletionDetail = (row) => {
  if (!row.tests || row.tests.length === 0) return '';
  
  let totalParams = 0;
  let completedParams = 0;
  
  row.tests.forEach(test => {
    if (test.parameter_count) {
      totalParams += test.parameter_count;
      completedParams += test.completed_parameter_count || 0;
    } else {
      totalParams++;
      if (test.pivot && ['Processing Results', 'Result Validated', 'Result Printed'].includes(test.pivot.item_status)) {
        completedParams++;
      }
    }
  });
  
  return `${completedParams}/${totalParams} parameters complete`;
};

// Status pill styling
const getStatusPillClass = (status) => {
  const statusUpper = status?.toUpperCase() || '';
  if (statusUpper.includes('CRITICAL') || statusUpper.includes('HOLD')) {
    return 'status-pill status-pill-red';
  }
  if (statusUpper.includes('QC HOLD')) {
    return 'status-pill status-pill-orange';
  }
  if (statusUpper === 'PRINTED' || statusUpper.includes('PRINTED')) {
    return 'status-pill status-pill-green';
  }
  if (statusUpper === 'ROUTINE' || statusUpper.includes('ROUTINE')) {
    return 'status-pill status-pill-blue';
  }
  // Default to blue for routine statuses
  return 'status-pill status-pill-blue';
};

// Row class name for styling
const getRowClassName = ({ row }) => {
  const status = getRowStatus(row);
  const isQCHold = isHematology.value && isRequestOnQCHold(row);
  const isCritical = isCriticalHold(row);
  
  if (isCritical || (status?.toUpperCase().includes('CRITICAL HOLD'))) {
    return 'row-critical-hold';
  }
  return '';
};

// Test abbreviation helper
const getTestAbbreviation = (testName) => {
  if (!testName) return '';
  // Common abbreviations mapping
  const abbreviations = {
    'complete blood count': 'CBC',
    'complete blood count (cbc)': 'CBC',
    'cbc': 'CBC',
    'comprehensive metabolic panel': 'CMP',
    'basic metabolic panel': 'BMP',
    'lipid panel': 'Lipid',
    'liver function test': 'LFT',
    'thyroid stimulating hormone': 'TSH',
    'hemoglobin a1c': 'HbA1c',
  };
  
  const lowerName = testName.toLowerCase();
  for (const [key, abbrev] of Object.entries(abbreviations)) {
    if (lowerName.includes(key)) {
      return abbrev;
    }
  }
  
  // Fallback: use first letters of words or first 3-4 chars
  const words = testName.split(' ');
  if (words.length > 1) {
    return words.map(w => w[0]).join('').toUpperCase().substring(0, 4);
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
    if (['Result Printed', 'Result Validated'].includes(test.pivot.item_status)) {
      return 100;
    }
    if (test.pivot.item_status === 'Processing Results') {
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
  return '';
};

// Get count for each tab
const getTabCount = (tabKey) => {
  switch (tabKey) {
    case 'all':
      return requests.value.length;
    case 'pending_review':
      return pendingReviewRequests.value.length;
    case 'waiting_analysis':
      return waitingAnalysisRequests.value.length;
    case 'completed':
      return completedRequests.value.length;

    default:
      return 0;
  }
};

// Export to sheets
const exportToSheets = () => {
  ElMessage.info('Export to Sheets functionality to be implemented');
  // TODO: Implement export functionality
};

// Use generic category endpoint for all categories
// The backend now supports: /results/category/{categoryId}/request/{requestId}
// This works for any category, not just hematology and chemistry

// --- Actions ---
const viewResults = async (requestId) => {
  console.log(`View results for request ID: ${requestId}`);

  if (!requestId) {
    ElMessage.warning('Invalid Request ID.');
    return;
  }

  detailsLoading.value = true;
  selectedRequestDetails.value = null;
  try {
    // Use the generic category endpoint that works for all categories
    // Format: /results/category/{categoryId}/request/{requestId}
    const response = await api.get(`/results/category/${props.categoryId}/request/${requestId}`);
    
    selectedRequestDetails.value = response.data.request_details;
    verifierList.value = response.data.user_lists?.verifiers || [];
    performerList.value = response.data.user_lists?.performers || [];
    pathologistList.value = response.data.user_lists?.pathologists || [];

    isViewingDetails.value = true;
    ElMessage.success('Request details loaded.');

  } catch (error) {
    console.error(`Error fetching details for request ${requestId}:`, error);
    ElMessage.error(error.response?.data?.message || 'Failed to load request details.');
    isViewingDetails.value = false;
  } finally {
    detailsLoading.value = false;
  }
};

const printReport = async (requestId) => {
  console.log(`Print report for request ID: ${requestId}`);
  
  if (!requestId) {
    ElMessage.warning('Invalid Request ID.');
    return;
  }
  
  try {
    loading.value = true;
    
    const request = requests.value.find(req => req.id === requestId);
    
    if (request && getRowStatus(request) === 'Validated') {
      await api.put(`/laboratory-requests/${requestId}/status`, {
        status: 'Result Printed'
      });
      
      ElMessage.success('Status updated to Printed');
      await fetchRequests();
    }
    
    openPrintView(requestId);
    
  } catch (error) {
    console.error('Error printing report:', error);
    ElMessage.error(`Failed to print report: ${error.response?.data?.message || error.message}`);
  } finally {
    loading.value = false;
  }
};

const openPrintView = async (requestId) => {
  // Similar to Hematology.vue print functionality
  // This would need to be implemented based on the specific modal component
  ElMessage.info('Print functionality to be implemented');
};

const goBack = () => {
  router.push({ name: 'result-management-dashboard' });
};

const handleSaveResults = (requestId, resultsData) => {
  console.log('Save results action triggered from modal:', { requestId, resultsData });
  ElMessage.success('Results saved successfully');
  fetchRequests();
};

const handlePrintResults = (requestId, resultsData) => {
  console.log('Print & Release action triggered from modal:', { requestId, resultsData });
  ElMessage.success('Results printed and released successfully');
  fetchRequests();
};

const handleValidateResults = (requestId, resultsData) => {
  console.log('Validate & Release action triggered from modal:', { requestId, resultsData });
  ElMessage.success('Results validated successfully');
  fetchRequests();
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
watch(searchQuery, () => {
    currentPage.value = 1;
    fetchRequests();
    // refresh analyzer cache for new page data
    setTimeout(() => { enrichVisibleRowsEquipment(); }, 0);
});
watch(patientTypeFilter, () => {
    currentPage.value = 1;
    fetchRequests();
    setTimeout(() => { enrichVisibleRowsEquipment(); }, 0);
});
watch(activeTriageTab, () => {
    // Reset to first page when changing tabs
    currentPage.value = 1;
});
</script>

<style scoped>
.category-results {
  width: 100%;

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
  margin: 0;
  font-size: 24px;
  font-weight: 500;
}

.content-card {
  border: none;
  box-shadow: var(--shadow-sm);
}

.pagination-container {
  display: flex;
  justify-content: flex-end;
  margin-top: var(--spacing-lg);
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
  width:100%;
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
  background: var(--color-bg-primary);
  color: var(--color-text-secondary);
  border-radius: var(--border-radius-md);
  cursor: pointer;
  font-size: var(--font-size-sm);
  font-weight: 500;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.triage-tab:hover {
  border-color: var(--color-danger);
  color: var(--color-danger);
}

.triage-tab.active {
  background: var(--color-danger);
  color: var(--color-bg-primary);
  border-color: var(--color-danger);
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
  gap: var(--spacing-sm);
  padding: var(--spacing-xs) var(--spacing-md);
  border-radius: var(--border-radius-full);
  font-size: var(--font-size-xs);
  font-weight: 600;
  text-transform: uppercase;
  white-space: nowrap;
}

.status-icon {
  font-size: var(--font-size-xs);
  line-height: 1;
}

.status-pill-red {
  background: var(--color-danger);
  color: var(--color-bg-primary);
  border: 1px solid var(--color-danger);
}

.status-pill-red .status-icon {
  color: var(--color-bg-primary);
}

.status-pill-orange {
  background: var(--color-warning-light);
  color: var(--color-warning);
  border: 1px solid var(--color-warning);
}

.status-pill-green {
  background: var(--color-success);
  color: var(--color-bg-primary);
  border: 1px solid var(--color-success);
}

.status-pill-green .status-icon {
  color: var(--color-bg-primary);
}

.status-pill-blue {
  background: var(--color-primary-light);
  color: var(--color-primary);
  border: 1px solid var(--color-primary);
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
}

.specimen-id-text {
  font-weight: 700;
  font-size: var(--font-size-base);
  color: var(--color-text-primary);
}

.specimen-id-text.specimen-id-critical {
  color: var(--color-danger);
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

/* Row Styling */
:deep(.el-table__row.row-critical-hold) {
  background-color: var(--color-danger-light) !important;
}

:deep(.el-table__row.row-critical-hold:hover) {
  background-color: var(--color-danger-light) !important;
  filter: brightness(0.95);
}

:deep(.el-table__row:not(.row-critical-hold)) {
  background-color: var(--color-bg-primary);
}

:deep(.el-table__row:not(.row-critical-hold):hover) {
  background-color: var(--color-bg-secondary);
}

/* Table Sections */
.table-section {
  width: 100%;
  margin-bottom: 0px;
}

.table-title {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  margin-bottom: var(--spacing-md);
  color: var(--color-text-primary);
  text-align: Left;
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
</style>

