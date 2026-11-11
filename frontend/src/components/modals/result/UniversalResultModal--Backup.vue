<template>
  <Teleport to="#app">
<el-dialog
  :model-value="props.visible"
  :fullscreen="true"
  class="hematology-modal"
  @close="handleClose"
  v-loading="props.loading || parametersLoading"
>
  <!-- Sticky Header / Toolbar -->
  <div class="result-header">
    <div class="brand">
      <span class="brand-logo">🧪</span>
      <span class="brand-name">{{ modalTitle }}</span>
    </div>
    <!-- <div class="patient-nav">
      <el-tooltip content="Previous Patient" placement="bottom"><el-button link size="small">« Prev</el-button></el-tooltip>
      <el-tooltip content="Next Patient" placement="bottom"><el-button link size="small">Next »</el-button></el-tooltip>
      <el-tooltip content="Patient Search" placement="bottom"><el-button link size="small">Search</el-button></el-tooltip>
    </div> -->
    <div class="global-actions">
      <el-button type="warning" @click="triggerSave" :disabled="disableSaveButton">
        <el-icon><Document /></el-icon>
        Save Draft
      </el-button>
      <el-button type="primary" @click="handlePrintRelease" :disabled="disablePrintButton">
        <el-icon><Printer /></el-icon>
        Print & Export
      </el-button>
      <el-button type="primary" @click="handleValidateRelease" :disabled="disableValidateButton">
        <el-icon><Check /></el-icon>
        Validate & Release
      </el-button>
      <el-button type="danger" @click="handleClose">
        <el-icon><Close /></el-icon>
        Close
      </el-button>
    </div>
  </div>

  <!-- Critical Action Banner (Sticky) -->
  <div
    v-if="hasHoldStatus || qcHoldBannerMessage || (cdsAlerts && cdsAlerts.length)"
    class="critical-banner"
  >
    <div class="critical-left">
      <span class="severity-icon" :class="{ danger: hasHoldStatus || qcHoldBannerMessage, warn: !hasHoldStatus && !qcHoldBannerMessage }">●</span>
      <div class="critical-text">
        <div class="primary">{{ hasHoldStatus ? 'CRITICAL HOLD: ACTION REQUIRED' : (qcHoldBannerMessage ? 'QC LOCKOUT ACTIVE' : 'CDS RULE TRIGGERED - ACTION REQUIRED') }}</div>
        <div class="detail">
          <span v-if="hasHoldStatus">
            Reason: <a href="#" @click.prevent="showViolationsModal = true" class="reason-link">
              This HOLD has {{ criticalValues.length }} Critical and {{ totalViolations }} Violations. Click here to Review.
            </a>
          </span>
          <span v-else-if="qcHoldBannerMessage">Reason: <a href="#" @click.prevent="showViolationsModal = true" class="reason-link">{{ qcHoldBannerMessage }}</a></span>
          <span v-else>Reason: <a href="#" @click.prevent="showViolationsModal = true" class="reason-link">{{ cdsAlerts[0]?.rule_name }} - {{ cdsAlerts[0]?.alert_message }}</a></span>
        </div>
      </div>
    </div>
    <div class="critical-actions" v-if="hasHoldStatus">
      <el-button type="default" @click="handleRejectResult">Reject</el-button>
      <el-button type="warning" plain @click="handleRerunResult">Re-run</el-button>
      <el-button type="success" @click="handleAcceptResult">Accept</el-button>
    </div>
  </div>

  <div class="content-container" :class="{ 'with-banner': hasHoldStatus || qcHoldBannerMessage || (cdsAlerts && cdsAlerts.length) }">
    <div class="layout">
      <!-- Left Sidebar: Patient Context -->
      <aside class="left-sidebar">
        <div class="card">
          <div class="panel-header">
            <div class="card-title">PATIENT INFORMATION</div>
          </div>
          <div class="card-row big">{{ props.requestDetails.patient?.last_name }}, {{ props.requestDetails.patient?.first_name }}</div>
          <div class="card-row">Patient ID: {{ props.requestDetails.patient?.id || 'N/A' }}</div>
          <div class="card-row">Gender: {{ props.requestDetails.patient?.gender || 'N/A' }}</div>
          <div class="card-row">DOB: {{ formatDate(props.requestDetails.patient?.date_of_birth) || 'N/A' }} (Age: {{ calculateAge(props.requestDetails.patient?.date_of_birth) }} {{ suffixUnit }})</div>
          
          <div class="card-row">Priority: {{ props.requestDetails.priority_level || 'N/A' }}</div>
          <div class="card-row">Received: {{ formatDateTime(props.requestDetails.request_date) }}</div>
        </div>
        <div class="card">
          <div class="panel-header">
            <div class="card-title">SAMPLE INTEGRITY</div>
          </div>
          <div class="card-row">Sample ID: {{ props.requestDetails.sample_id || 'N/A' }}</div>
          <div class="card-row">
            QC Status:
            <span :class="['qc-chip', qcHoldBannerMessage ? 'fail' : 'pass']">{{ qcHoldBannerMessage ? 'FAIL' : 'PASS' }}</span>
          </div>
          <div class="card-row">Patient Type: {{ props.requestDetails.patient_type?.name || 'N/A' }}</div>
          <div class="card-row">Ward/Room: {{ props.requestDetails.ward?.name || '—' }} / {{ props.requestDetails.room?.name || '—' }}</div>
        </div>
        <div class="card links">
          <div class="panel-header">
            <div class="card-title">QUICK ACTIONS BUTTONS</div>
          </div>
          <el-button type="default" size="small"><el-icon><Document /></el-icon>View Patient History (6 Months)</el-button>
          <el-button v-if="hasHoldStatus" type="default" size="small">Order New Re-draw</el-button>
          <el-button type="warning" size="small"><el-icon><Refresh /></el-icon> Retrieve Re-run</el-button>
          <el-button type="primary" size="small"><el-icon><Plus /></el-icon> Add Test</el-button>
        </div>
      </aside>

      <!-- Main Content: Test Results -->
      <section class="main-content">

    <!-- Test Results Section -->
    <div class="section test-results">
      <div class="panel-header">
        <div class="card-title">TEST RESULTS</div>
      </div>
      <el-table 
        :data="formattedTestResults" 
        style="width: 100%"
        row-key="id"
        @selection-change="handleSelectionChange"
        size="small"
        class="compact-table"
      >
        <el-table-column label="Final Result" align="center">
          <el-table-column align="center" type="selection" width="100" :selectable="() => true" />
        </el-table-column>
        <el-table-column label="Re-Run" align="center">
          <el-table-column align="center" type="selection" width="80" :selectable="() => true" />
        </el-table-column>
        <el-table-column label="Test Name / Parameter" min-width="180">
          <template #default="{ row }">
            <span>
              {{ row.parameterName || `Parameter ${row.parameterId}` }}
            </span>
          </template>
          </el-table-column>
        <el-table-column label="Flag" width="60" align="center" >
           <template #default="{ row }">
             <span :class="getFlagClass(row.flag)">{{ row.flag ? `(${row.flag})` : '' }}</span>
           </template>
        </el-table-column>
        <el-table-column label="S.I. Unit" align="center">
          <el-table-column label="Result" width="110" align="center">
            <template #default="{ row }">
              <el-input
                v-if="row.isParameter"
                v-model="row.resultValue"
                @change="updateResultValue(row, $event)"
                size="small"
                placeholder="Enter result"
              />
              <span v-else></span>
            </template>
        </el-table-column>
          <el-table-column label="Previous Result (Delta)" width="150" align="center">
            <template #default="{ row }">
              <div class="delta-cell" v-if="row.previousValue !== undefined || row.deltaPercent !== undefined">
                <span class="prev-val" v-if="row.previousValue !== undefined">{{ row.previousValue }}</span>
                <span class="delta-val" v-if="row.deltaPercent !== undefined"> ({{ Number(row.deltaPercent).toFixed(0) }}%)</span>
              </div>
              <span v-else>—</span>
            </template>
          </el-table-column>
          <el-table-column prop="unit" label="Units" width="90" align="center" >
          </el-table-column>
          <el-table-column label="Reference Range" min-width="120" align="center">
          <template #default="{ row }">
              <div>
                <span :class="{ 'range-undefined': row.referenceRange === 'Not defined' || row.referenceRange === 'N/A' }">
                  {{ row.referenceRange }}
                </span>
              </div>
            </template>
          </el-table-column>
        </el-table-column>
        <el-table-column label="Conventional Unit" align="center">
          <el-table-column label="Result" width="90" align="center">
            <template #default="{ row }">
              <span>{{ row.conventional }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="units" label="Units" width="90" align="center" />
          <el-table-column label="Reference Range" min-width="120" align="center">
            <template #default="{ row }">
              <div>
                <span :class="{ 'range-undefined': row.refRange === 'Not defined' || row.refRange === 'N/A' }">
                  {{ row.refRange }}
                </span>
              </div>
            </template>
          </el-table-column>
        </el-table-column>
        <el-table-column label="Instrument/Equipment" width="200" align="center">
          <template #default="{ row }">
            <div v-if="row.isParameter">
              <el-select 
                v-model="row.instrument" 
                style="width: 100%" 
                size="small"
                filterable 
                placeholder="Select Equipment"
                @change="(value) => handleInstrumentChange(row, value)"
                :loading="loadingEquipment"
              >
                <el-option 
                  v-for="eq in equipment" 
                  :key="eq.id" 
                  :label="`${eq.manufacturer} - ${eq.model}`" 
                  :value="eq.id" 
                />
            </el-select>
            </div>
            <span v-else></span>
          </template>
        </el-table-column>
        <el-table-column label="Remarks" width="250" align="center">
          <template #default="{ row }">
            <el-input
              v-if="row.isParameter" 
              :model-value="filterDeltaCheckHistory(row.resultComment)" 
              @input="(val) => updateRemarksWithoutDeltaHistory(row, val)"
              placeholder="Remarks" 
              size="small" 
            />
            <span v-else></span>
          </template>
        </el-table-column>
      </el-table>

      <div class="total-dif">Total DIF Count: 0</div>
      </div>
      </section>
    </div>
  <!-- Moved remarks, comments, and signatories to bottom -->
  <div class="bottom-container">
    <div class="top-section">
      <div class="remarks-panel">
        <div class="panel-label">Remarks</div>
        <el-input
            v-model="localRemarks"
          type="textarea"
          :rows="3"
          placeholder="RESULTS VERIFIED"
        />
      </div>
      <div class="comments-panel">
        <div class="panel-label">Comment</div>
        <el-input
            v-model="localComments"
          type="textarea"
          :rows="3"
          placeholder="Enter comments..."
        />
      </div>
    </div>
      <div class="signatories-section">
        <div class="section-title">Signatories</div>
        <el-form label-position="left" label-width="120px" class="section-signatories">
          <el-form-item label="Performed By:">
            <el-select 
              v-model="localSignatories.performedBy" 
              style="width: 100%" 
              filterable 
              placeholder="Select Performer"
              :loading="loadingRoles"
            >
              <el-option v-for="user in loadedPerformers" :key="user.id" :label="user.name" :value="user.id" />
            </el-select>
          </el-form-item>
          <el-form-item label="Verified By:">
            <el-select 
              v-model="localSignatories.verifiedBy" 
              style="width: 100%" 
              filterable 
              placeholder="Select Verifier"
              :loading="loadingRoles"
            >
              <el-option v-for="user in loadedVerifiers" :key="user.id" :label="user.name" :value="user.id" />
            </el-select>
          </el-form-item>
          <el-form-item label="Pathologist:">
            <el-select 
              v-model="localSignatories.pathologist" 
              style="width: 100%" 
              filterable 
              placeholder="Select Pathologist"
              :loading="loadingRoles"
            >
              <el-option v-for="user in loadedPathologists" :key="user.id" :label="user.name" :value="user.id" />
            </el-select>
          </el-form-item>
        </el-form>
      </div>
    </div>
  </div>

  <!-- Accept Result Modal -->
  <el-dialog
    v-model="showAcceptModal"
    title="Accept Result - Mandatory Documentation"
    width="600px"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
  >
    <el-form :model="acceptForm" :rules="acceptRules" ref="acceptFormRef" label-width="180px">
      <!-- Common fields -->
      <el-form-item label="Reviewing MLT/MLS" prop="reviewer_id" required>
        <el-select v-model="acceptForm.reviewer_id" style="width: 100%" filterable :disabled="true">
          <el-option v-if="currentUser" :label="currentUser.name" :value="currentUser.id" />
        </el-select>
      </el-form-item>
      <el-form-item label="Date/Time of Review">
        <el-input :model-value="acceptForm.reviewed_at" readonly />
      </el-form-item>
      <el-form-item label="Investigation Checklist" prop="investigation_checklist" required>
        <el-checkbox-group v-model="acceptForm.investigation_checklist">
          <el-checkbox v-for="opt in investigationChecklistOptions" :key="opt.value" :label="opt.value">{{ opt.label }}</el-checkbox>
        </el-checkbox-group>
      </el-form-item>

      <!-- Accept-specific fields -->
      <el-form-item label="Reason for Acceptance" prop="accept_reason" required>
        <el-select v-model="acceptForm.accept_reason" placeholder="Select reason">
          <el-option v-for="opt in acceptReasonOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
        </el-select>
      </el-form-item>
      <el-form-item label="Detailed Rationale" prop="rationale" required>
        <el-input v-model="acceptForm.rationale" type="textarea" :rows="3" maxlength="500" show-word-limit placeholder="Provide detailed rationale (min 50 characters)" />
      </el-form-item>
      <el-form-item label="HIL/Pre-Analytical Status" prop="preanalytical_status" required>
        <el-select v-model="acceptForm.preanalytical_status" placeholder="Select status">
          <el-option v-for="opt in preanalyticalStatusOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
        </el-select>
      </el-form-item>
    </el-form>
    <template #footer>
      <span class="dialog-footer">
        <el-button @click="showAcceptModal = false">Cancel</el-button>
        <el-button type="primary" @click="submitAccept" :loading="acceptingResult">
          Accept & Validate
        </el-button>
      </span>
    </template>
  </el-dialog>

  <!-- Reject Result Modal -->
  <el-dialog
    v-model="showRejectModal"
    title="Reject Result - Mandatory Documentation"
    width="600px"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
  >
    <el-form :model="rejectForm" :rules="rejectRules" ref="rejectFormRef" label-width="180px">
      <!-- Common fields -->
      <el-form-item label="Reviewing MLT/MLS" prop="reviewer_id" required>
        <el-select v-model="rejectForm.reviewer_id" style="width: 100%" filterable :disabled="true">
          <el-option v-if="currentUser" :label="currentUser.name" :value="currentUser.id" />
        </el-select>
      </el-form-item>
      <el-form-item label="Date/Time of Review">
        <el-input :model-value="rejectForm.reviewed_at" readonly />
      </el-form-item>
      <el-form-item label="Investigation Checklist" prop="investigation_checklist" required>
        <el-checkbox-group v-model="rejectForm.investigation_checklist">
          <el-checkbox v-for="opt in investigationChecklistOptions" :key="opt.value" :label="opt.value">{{ opt.label }}</el-checkbox>
        </el-checkbox-group>
      </el-form-item>

      <!-- Reject-specific fields -->
      <el-form-item label="Root Cause of Error" prop="root_cause" required>
        <el-select v-model="rejectForm.root_cause" placeholder="Select category">
          <el-option v-for="opt in rootCauseOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
        </el-select>
      </el-form-item>
      <el-form-item label="Action Taken" prop="action_taken" required>
        <el-select v-model="rejectForm.action_taken" placeholder="Select action">
          <el-option v-for="opt in actionTakenOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
        </el-select>
      </el-form-item>
      <el-form-item label="Detailed Comment" prop="rejection_comment" required>
        <el-input v-model="rejectForm.rejection_comment" type="textarea" :rows="3" maxlength="500" show-word-limit placeholder="Provide detailed comment (min 50 characters)" />
      </el-form-item>
      <el-form-item>
        <el-checkbox v-model="rejectForm.original_value_canceled">Original Value Canceled in LIS</el-checkbox>
      </el-form-item>
    </el-form>
    <template #footer>
      <span class="dialog-footer">
        <el-button @click="showRejectModal = false">Cancel</el-button>
        <el-button type="danger" @click="submitReject" :loading="rejectingResult">
          Reject & Cancel
        </el-button>
      </span>
    </template>
  </el-dialog>

  <!-- Re-run Confirmation Modal -->
  <el-dialog
    v-model="showRerunModal"
    title="Initiate Re-run"
    width="500px"
  >
    <p>This will mark the request as "For Re-run" or "Pending Repeat Analysis".</p>
    <p>After performing the re-run on the instrument, the new result will be automatically compared with the original flagged result.</p>
    <template #footer>
      <span class="dialog-footer">
        <el-button @click="showRerunModal = false">Cancel</el-button>
        <el-button type="warning" @click="submitRerun" :loading="rerunningResult">
          Initiate Re-run
        </el-button>
      </span>
    </template>
  </el-dialog>

  <!-- Summary of Violations & Hold Reasons Modal -->
  <el-dialog
    v-model="showViolationsModal"
    width="900px"
    :close-on-click-modal="true"
    class="violations-modal"
    :show-close="false"
  >
    <template #header>
      <div class="violations-modal-header">
        <div class="violations-header-brand">
          <span class="violations-header-icon"><el-icon></el-icon></span>
          <span class="violations-header-title">VIOLATION SUMMARY & GUIDED REVIEW</span>
        </div>
        <div class="violations-header-actions">
          <el-button type="default" @click="exportViolationsToSheets" size="small">
            <el-icon><Document /></el-icon>
            Export to Sheets
          </el-button>
          <el-button type="danger" @click="showViolationsModal = false" size="small">
            <el-icon><Close /></el-icon>
            Close
          </el-button>
        </div>
      </div>
    </template>
    <el-tabs v-model="activeViolationTab" class="violations-tabs">
      <!-- Summary Tab -->
      <el-tab-pane label="Summary" name="summary">
        <div class="violations-summary">
          <!-- AI Suggestion Banner -->
          <div v-if="hasHoldStatus" class="ai-suggestion-banner">
            <span class="ai-icon">🤖</span>
            <span class="ai-text">AI Suggestion: RE-RUN (Confidence: 0.95)</span>
          </div>

          <!-- Hold Summary -->
          <div class="hold-summary">
            <p class="summary-text">
              This HOLD has <strong>{{ criticalValues.length }}</strong> critical 
              and <strong>{{ totalViolations }}</strong> violations.
            </p>
            <p class="instruction-text">
              This sample is currently on HOLD. Please review all violations below, 
              then select action button on final review.
            </p>
          </div>

          <!-- Violations List -->
          <div class="violations-list">
            <!-- Critical Values -->
            <div v-for="(cv, idx) in criticalValues" :key="'cv-' + idx" class="violation-list-item critical">
              <span class="list-icon danger">⚠</span>
              <div class="list-content">
                <div class="list-title">{{ cv.parameter }}</div>
                <div class="list-detail">
                  {{ cv.flag === 'L' ? 'Critically Low' : 'Critically High' }} {{ cv.value }} {{ cv.unit }} 
                  - (Ref Range: {{ cv.refRange || cv.referenceRange || 'N/A' }})
                </div>
                <div class="list-note">Please review and require clinician notification upon final validation.</div>
              </div>
            </div>

            <!-- Delta Checks -->
            <div v-for="(dc, idx) in flaggedDeltaChecks" :key="'dc-' + idx" class="violation-list-item warning">
              <span class="list-icon warn">⚠</span>
              <div class="list-content">
                <div class="list-title">{{ getDeltaCheckParameterName(dc) }}</div>
                <div class="list-detail">
                  {{ dc.percentage_difference ? Math.abs(dc.percentage_difference).toFixed(0) + '%' : 'N/A' }} 
                  change ({{ dc.previous_value }} → {{ dc.current_value }} {{ getDeltaCheckUnit(dc) }})
                </div>
                <div class="list-note">Delta Check violation detected.</div>
              </div>
            </div>

            <!-- CDS Rules -->
            <div v-for="(alert, idx) in cdsAlerts" :key="'cds-' + idx" class="violation-list-item warning">
              <span class="list-icon warn">⚠</span>
              <div class="list-content">
                <div class="list-title">CDS Rule</div>
                <div class="list-detail">{{ alert.rule_name }}: {{ alert.alert_message }}</div>
              </div>
            </div>

            <!-- QC Failures -->
            <div v-if="qcHoldBannerMessage" class="violation-list-item critical">
              <span class="list-icon danger">⚠</span>
              <div class="list-content">
                <div class="list-title">QC & Instrument Status</div>
                <div class="list-detail">{{ qcHoldBannerMessage }}</div>
              </div>
            </div>
          </div>
        </div>
      </el-tab-pane>

      <!-- QC & Instrument Status Tab -->
      <el-tab-pane label="QC & Instrument Status" name="qc">
        <div class="violations-summary">
          <div class="violation-section">
            <div class="section-header">QC & Instrument Status</div>
            <div class="violation-item" v-if="qcHoldBannerMessage">
              <span class="violation-icon danger">●</span>
              <span class="violation-text">Failed QC Run: {{ qcHoldBannerMessage }}</span>
            </div>
            <div class="violation-item" v-for="(flag, idx) in instrumentFlags" :key="'inst-' + idx">
              <span class="violation-icon danger">●</span>
              <span class="violation-text">Instrument Flag: {{ flag }}</span>
            </div>
            <div v-if="!qcHoldBannerMessage && instrumentFlags.length === 0" class="no-violations">
              No QC or instrument violations detected.
            </div>
            <div class="section-purpose">Identifies systematic or sample-level analytical errors first.</div>
          </div>
        </div>
      </el-tab-pane>

      <!-- Critical Values Tab -->
      <el-tab-pane label="Critical Values" name="critical">
        <div class="violations-summary">
          <div class="violation-section">
            <div class="section-header">Critical Values</div>
            <div class="violation-item" v-for="(cv, idx) in criticalValues" :key="'cv-' + idx">
              <span class="violation-icon danger">●</span>
              <span class="violation-text">{{ cv.parameter }} ({{ cv.value }} {{ cv.unit }}): {{ cv.message }}</span>
            </div>
            <div v-if="criticalValues.length === 0" class="no-violations">
              No critical values detected.
            </div>
            <div class="section-purpose">Highlights life-threatening results. Requires immediate clinician notification upon validation.</div>
          </div>
        </div>
      </el-tab-pane>

      <!-- Delta Checks Tab -->
      <el-tab-pane label="Delta Checks" name="delta">
        <div class="violations-summary">
          <div class="violation-section">
            <div class="section-header">Delta Checks</div>
            <div class="violation-item" v-for="(dc, idx) in flaggedDeltaChecks" :key="'dc-' + idx">
              <span class="violation-icon warn">●</span>
              <span class="violation-text">
                {{ getDeltaCheckParameterName(dc) }}: Value changed {{ dc.percentage_difference ? Math.abs(dc.percentage_difference).toFixed(0) + '%' : '' }} 
                from previous result ({{ dc.previous_value }} → {{ dc.current_value }})
              </span>
            </div>
            <div v-if="flaggedDeltaChecks.length === 0" class="no-violations">
              No delta check violations detected.
            </div>
            <div class="section-purpose">Focuses attention on patient change plausibility.</div>
          </div>
        </div>
      </el-tab-pane>

      <!-- CDS Rules Tab -->
      <el-tab-pane label="CDS Rules" name="cds">
        <div class="violations-summary">
          <div class="violation-section">
            <div class="section-header">CDS Rules</div>
            <div class="violation-item" v-for="(alert, idx) in cdsAlerts" :key="'cds-' + idx">
              <span class="violation-icon warn">●</span>
              <span class="violation-text">{{ alert.rule_name }}: {{ alert.alert_message }}</span>
            </div>
            <div v-if="cdsAlerts.length === 0" class="no-violations">
              No CDS rule violations detected.
            </div>
            <div class="section-purpose">Provides clinical context for the MLT's judgment.</div>
          </div>
        </div>
      </el-tab-pane>
    </el-tabs>

    <template #footer>
      <div class="violations-footer">
        <div class="acknowledgment-section">
          <el-checkbox v-model="violationsAcknowledged">
            I have reviewed and acknowledge all violations. Ready to proceed with review.
          </el-checkbox>
        </div>
        <div class="footer-actions">
          <el-button 
            type="success" 
            @click="handleAcknowledgeViolations"
            :disabled="!violationsAcknowledged"
          >
            Acknowledge & Return to Review
          </el-button>
        </div>
      </div>
    </template>
  </el-dialog>
</el-dialog>
</Teleport>
</template>

<script setup>
/**
 * BUTTON STATE MANAGEMENT SYSTEM
 * 
 * This component uses computed properties to directly control button states:
 * - disableSaveButton → Save button
 * - disablePrintButton → Print & Release button
 * - disableValidateButton → Validate & Release button
 * 
 * These computed properties automatically react to changes in test parameters
 * and test statuses. No additional reactive variables are used for button states.
 */
import { ref, computed, reactive, watch, onMounted, onUnmounted, nextTick } from 'vue';
import {
  View,
  Document,
  Printer,
  Check,
  Close,
  User,
  Refresh,
  Plus
} from '@element-plus/icons-vue';
import { format, parseISO } from 'date-fns';
import { useAuthStore } from '@/stores/auth';
import api from '@/utils/axios';
import { ElMessage, ElMessageBox } from 'element-plus';
import { differenceInYears, differenceInMonths, differenceInDays } from 'date-fns';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  requestDetails: {
    type: Object,
    default: () => ({ tests: [], patient: {}, performer_id: null, verifier_id: null, pathologist_id: null, remarks: '', comment: '' }),
  },
  loading: {
    type: Boolean,
    default: false,
  },
  verifiers: {
    type: Array,
    default: () => [],
  },
  performers: {
    type: Array,
    default: () => [],
  },
  pathologists: {
    type: Array,
    default: () => [],
  },
  categoryName: {
    type: String,
    default: '',
  },
  categoryId: {
    type: [String, Number],
    default: null,
  },
});

const emit = defineEmits(['update:visible', 'close', 'save', 'validate', 'print']);

// Computed property for dynamic modal title
const modalTitle = computed(() => {
  const categoryName = props.categoryName.toLocaleUpperCase() || 'Result';
  return `${categoryName} Result Window`;
});

const localSignatories = ref({ performedBy: null, verifiedBy: null, pathologist: null });
const localRemarks = ref('');
const localComments = ref('');
const resultFields = ref([]);
const testParameters = ref({});
const parametersLoading = ref(false);
const equipment = ref([]);
const loadingEquipment = ref(false);

// QC banner state and updater
const qcHoldBannerMessage = ref('');
const updateQCHoldBanner = () => {
  try {
    const involvedEquipmentIds = Array.from(new Set(
      Object.values(testParameters.value)
        .flat()
        .map(p => (p.equipment_id ?? p.instrument))
        .filter(id => id != null)
        .map(id => parseInt(id))
    ));
    if (involvedEquipmentIds.length > 0 && testCategoryId.value) {
      api.get(`/qc-lockout/status-summary`, { params: { test_category_id: testCategoryId.value } })
        .then(({ data }) => {
          const failing = (data?.equipment || []).filter(eq => involvedEquipmentIds.includes(eq.id) && eq.on_hold);
          if (failing.length > 0) {
            const lines = failing.map(eq => `${eq.name || 'Equipment'}: ${(eq.violations || []).join(', ')}`);
            qcHoldBannerMessage.value = lines.join(' | ');
          } else {
            qcHoldBannerMessage.value = '';
          }
        })
        .catch(() => { /* keep previous banner state */ });
    } else {
      qcHoldBannerMessage.value = '';
    }
  } catch (_) {
    // ignore
  }
};

// CDS alerts state and updater
const cdsAlerts = ref([]);
const checkCdsRules = async () => {
  if (!props.requestDetails?.id) {
    return;
  }

  try {
    const response = await api.get(`/results/check-cds/${props.requestDetails.id}`);
    const data = response.data;
    
    if (data.triggered_rules && data.triggered_rules.length > 0) {
      cdsAlerts.value = data.triggered_rules;
      
      // Show warning if there are blocking rules
      if (data.has_blocking_rules) {
        const blockingRules = data.triggered_rules.filter(r => r.is_blocking);
        if (blockingRules.length > 0) {
          ElMessage.warning({
            message: `${blockingRules.length} CDS rule(s) triggered. Review required before validation.`,
            duration: 5000
          });
        }
      }
    } else {
      cdsAlerts.value = [];
    }
  } catch (error) {
    console.error('Error checking CDS rules:', error);
    // Don't show error to user, just log it
  }
};

// Delta Check Hold Status - Flagged results detection
const flaggedDeltaChecks = ref([]);
const hasHoldStatus = ref(false);

// Check for flagged delta checks when modal opens or request details change
const checkFlaggedDeltaChecks = async () => {
  if (!props.requestDetails?.id) {
    flaggedDeltaChecks.value = [];
    hasHoldStatus.value = false;
    return;
  }

  try {
    const requestId = props.requestDetails.id;
    console.log('Checking flagged delta checks for request ID:', requestId);
    
    // Fetch flagged delta checks for this laboratory request
    const response = await api.get('/management-report/delta-checks', {
      params: {
        status: 'flagged',
        per_page: 1000,
        page: 1
      }
    });

    const allFlaggedChecks = response.data?.data || [];
    console.log('Total flagged delta checks retrieved:', allFlaggedChecks.length);
    console.log('Sample delta check structure:', allFlaggedChecks[0]);
    
    // Filter to only include checks for this laboratory request
    // Try multiple ways to access the laboratory_request_id
    flaggedDeltaChecks.value = allFlaggedChecks.filter(dc => {
      if (!dc || !dc.test_result) {
        return false;
      }
      
      // Try different paths to get laboratory_request_id
      const lrid = dc.test_result.laboratory_request_id || 
                   dc.test_result.laboratory_request?.id ||
                   (dc.test_result.laboratoryRequest && dc.test_result.laboratoryRequest.id) ||
                   null;
      
      // Convert both to numbers for comparison (in case of string vs number mismatch)
      const requestIdNum = Number(requestId);
      const lridNum = Number(lrid);
      
      const matches = lridNum === requestIdNum || lrid === requestId;
      
      if (matches) {
        console.log('Found matching flagged delta check:', {
          requestId: requestId,
          lrid: lrid,
          test_result_id: dc.test_result?.id,
          test_parameter_id: dc.test_parameter?.id,
          status: dc.status,
          action_on_violation: dc.action_on_violation
        });
      }
      
      return matches;
    });

    // Check if any results have action_on_violation = 'flag_and_stop' which should set status to Hold
    // According to requirements, only 'flag_and_stop' should trigger Hold status
    // However, we'll also check for any flagged results as a fallback
    const hasFlagAndStop = flaggedDeltaChecks.value.some(dc => 
      dc.action_on_violation === 'flag_and_stop' || 
      dc.action_on_violation === 'Flag and Stop'
    );
    
    // If we have flagged results with 'flag_and_stop', or if any flagged results exist (fallback)
    hasHoldStatus.value = hasFlagAndStop || flaggedDeltaChecks.value.length > 0;
    
    if (hasHoldStatus.value) {
      console.log('Hold status triggered. Flagged checks:', flaggedDeltaChecks.value.length, 
                  'Flag and Stop:', hasFlagAndStop);
    }
    
    console.log('Filtered flagged delta checks for request:', flaggedDeltaChecks.value.length);
    console.log('Has Hold status:', hasHoldStatus.value);
    
    if (flaggedDeltaChecks.value.length > 0) {
      console.log('Flagged delta checks details:', flaggedDeltaChecks.value);
    } else {
      console.warn('No flagged delta checks found for request ID:', requestId);
      // Try to find any delta checks for this request (not just flagged) for debugging
      const allResponse = await api.get('/management-report/delta-checks', {
        params: {
          per_page: 1000,
          page: 1
        }
      });
      const allChecks = allResponse.data?.data || [];
      const requestChecks = allChecks.filter(dc => {
        const lrid = dc?.test_result?.laboratory_request_id || 
                     dc?.test_result?.laboratory_request?.id;
        return Number(lrid) === Number(requestId);
      });
      console.log('All delta checks for this request (for debugging):', requestChecks.length);
    }
  } catch (error) {
    console.error('Error checking flagged delta checks:', error);
    console.error('Error details:', error.response?.data || error.message);
    flaggedDeltaChecks.value = [];
    hasHoldStatus.value = false;
  }
};

// Accept/Reject/Re-run modal states
const showAcceptModal = ref(false);
const showRejectModal = ref(false);
const showRerunModal = ref(false);
const showViolationsModal = ref(false);
const activeViolationTab = ref('summary');
const violationsAcknowledged = ref(false);

// Logged-in user (for reviewer auto-fill)
const authStore = useAuthStore?.() || null;
const currentUser = authStore?.user || null;

const nowTimestamp = () => {
  try { return format(new Date(), 'yyyy-MM-dd HH:mm:ss'); } catch { return new Date().toISOString(); }
};

const acceptForm = ref({
  reviewer_id: currentUser?.id || null,
  reviewed_at: nowTimestamp(),
  investigation_checklist: [],
  accept_reason: '',
  rationale: '',
  preanalytical_status: ''
});
const rejectForm = ref({
  reviewer_id: currentUser?.id || null,
  reviewed_at: nowTimestamp(),
  investigation_checklist: [],
  root_cause: '',
  action_taken: '',
  rejection_comment: '',
  original_value_canceled: true
});
const acceptFormRef = ref(null);
const rejectFormRef = ref(null);

// Dropdown/checklist options
const investigationChecklistOptions = [
  { label: 'Reviewed Patient History', value: 'reviewed_patient_history' },
  { label: 'Checked QC Status', value: 'checked_qc_status' },
  { label: 'Verified Specimen Integrity', value: 'verified_specimen_integrity' }
];
const acceptReasonOptions = [
  { label: 'Clinically Plausible (e.g., known condition)', value: 'clinically_plausible' },
  { label: 'Repeat/Re-run Confirmed', value: 'repeat_confirmed' },
  { label: 'No Pre-Analytical Error Found', value: 'no_preanalytical_error' }
];
const preanalyticalStatusOptions = [
  { label: 'Clear', value: 'clear' },
  { label: 'Mild Hemolysis', value: 'mild_hemolysis' },
  { label: 'Moderate Lipemia', value: 'moderate_lipemia' },
  { label: 'Icteric', value: 'icteric' }
];
const rootCauseOptions = [
  { label: 'Pre-Analytical', value: 'pre_analytical' },
  { label: 'Analytical', value: 'analytical' },
  { label: 'Post-Analytical', value: 'post_analytical' }
];
const actionTakenOptions = [
  { label: 'Sample Cancelled', value: 'sample_cancelled' },
  { label: 'Re-run Initiated (same sample)', value: 'rerun_initiated' },
  { label: 'Re-draw Initiated (new order)', value: 'redraw_initiated' }
];
const acceptingResult = ref(false);
const rejectingResult = ref(false);
const rerunningResult = ref(false);

// Form validation rules
const acceptRules = {
  reviewer_id: [{ required: true, message: 'Reviewer is required', trigger: 'change' }],
  investigation_checklist: [{ type: 'array', required: true, message: 'Select at least one item', trigger: 'change' }],
  accept_reason: [{ required: true, message: 'Reason is required', trigger: 'change' }],
  rationale: [
    { required: true, message: 'Detailed rationale is required', trigger: 'blur' },
    { min: 50, message: 'Rationale must be at least 50 characters', trigger: 'blur' }
  ],
  preanalytical_status: [{ required: true, message: 'Status is required', trigger: 'change' }]
};

const rejectRules = {
  reviewer_id: [{ required: true, message: 'Reviewer is required', trigger: 'change' }],
  investigation_checklist: [{ type: 'array', required: true, message: 'Select at least one item', trigger: 'change' }],
  root_cause: [{ required: true, message: 'Root cause is required', trigger: 'change' }],
  action_taken: [{ required: true, message: 'Action taken is required', trigger: 'change' }],
  rejection_comment: [
    { required: true, message: 'Detailed comment is required', trigger: 'blur' },
    { min: 50, message: 'Comment must be at least 50 characters', trigger: 'blur' }
  ]
};

// Arrays to store role-specific users fetched from the API
const loadedPathologists = ref([]);
const loadedVerifiers = ref([]);
const loadedPerformers = ref([]);
const loadingRoles = ref(false);

// Remove hardcoded constant and replace with reactive variable
const testCategoryId = ref(null);

// Define loadEquipment (replaces loadAnalyzers) before it's used in any watcher
const loadEquipment = async (testCategoryId = null) => {
  loadingEquipment.value = true;
  try {
    let endpoint = 'eim/equipment';
    const params = { per_page: 1000 };
    
    // If a test category ID is provided, filter by category
    if (testCategoryId) {
      params.test_category_id = testCategoryId;
      console.log(`Loading equipment for test category: ${testCategoryId}`);
    } else {
      console.log('Loading all equipment (no category filter)');
    }
    
    const response = await api.get(endpoint, { params });
    equipment.value = response.data.data || response.data || [];
    console.log('Loaded equipment:', equipment.value);
  } catch (error) {
    console.error('Error loading equipment:', error);
    ElMessage.error('Failed to load equipment');
  } finally {
    loadingEquipment.value = false;
  }
};

// Alias for loadEquipment - kept for existing code references
const loadAnalyzers = async (testCategoryId = null) => {
  await loadEquipment(testCategoryId);
};

// Define convertAllSIValues before it's used in any watcher
const convertAllSIValues = () => {
  console.log('Converting all SI values to conventional...');
  
  // Loop through all tests and their parameters
  Object.keys(testParameters.value).forEach(testId => {
    const params = testParameters.value[testId];
    if (Array.isArray(params)) {
      params.forEach(param => {
        if (param.resultValue && !isNaN(parseFloat(param.resultValue)) && param.conversion_factor) {
          // Calculate conventional value
          const numericValue = parseFloat(param.resultValue);
          const conversionFactor = parseFloat(param.conversion_factor);
          param.conventional = (numericValue * conversionFactor).toFixed(2);
          console.log(`Auto-converted ${param.name}: ${param.resultValue} ${param.unit} → ${param.conventional} ${param.conventional_unit}`);
        }
      });
    }
  });
};

// Simple function to force reactive updates of button states
const forceButtonUpdate = () => {
  console.log('Forcing button state update...');
  
  // Log current parameter state for debugging
  const allParams = Object.values(testParameters.value).flat();
  const paramsWithValues = allParams.filter(param => 
    param && param.resultValue && param.resultValue.trim() !== ''
  );
  const savedParams = paramsWithValues.filter(param => param.result_id !== null);
  const unsavedParams = paramsWithValues.filter(param => param.result_id === null);
  
  console.log(`Button state calculation:
    - Total parameters: ${allParams.length}
    - Parameters with values: ${paramsWithValues.length}
    - Saved parameters: ${savedParams.length}
    - Unsaved parameters: ${unsavedParams.length}
  `);

  // Manually compute expected button states
  const shouldDisableSave = unsavedParams.length === 0;
  const shouldDisablePrint = unsavedParams.length > 0;
  const shouldDisableValidate = unsavedParams.length > 0 || 
    props.requestDetails.tests.every(test => test.pivot && test.pivot.item_status === 'Result Printed');

  console.log(`Expected button states:
    - Save button should be disabled: ${shouldDisableSave}
    - Print button should be disabled: ${shouldDisablePrint}
    - Validate button should be disabled: ${shouldDisableValidate}
  `);

  // Force Vue to re-evaluate the computed properties
  nextTick(() => {
    console.log('Actual button states after update:');
    console.log('- Save button disabled:', disableSaveButton.value);
    console.log('- Print button disabled:', disablePrintButton.value);
    console.log('- Validate button disabled:', disableValidateButton.value);
  });
};

// Fetch users by role type
const fetchUsersByRole = async (roleType) => {
  try {
    const response = await api.get(`/users/role/${roleType}`);
    return response.data;
  } catch (error) {
    console.error(`Error fetching ${roleType}s:`, error);
    ElMessage.error(`Failed to load ${roleType}s`);
    return [];
  }
};

// Function to load all role-specific users
const loadRoleUsers = async () => {
  loadingRoles.value = true;
  try {
    // First load users by role type
    const [pathologists, verifiers, performers] = await Promise.all([
      fetchUsersByRole('pathologist'),
      fetchUsersByRole('verifier'),
      fetchUsersByRole('performer')
    ]);
    
    loadedPathologists.value = pathologists;
    loadedVerifiers.value = verifiers;
    loadedPerformers.value = performers;
    
    console.log('Loaded pathologists:', loadedPathologists.value);
    console.log('Loaded verifiers:', loadedVerifiers.value);
    console.log('Loaded performers:', loadedPerformers.value);
    
    // If we have specific user IDs from the test records, ensure they're loaded
    // This is for when users might not be in the role-specific lists
    const userIdsToLoad = [];
    
    // Skip IDs that are already in our loaded lists
    if (localSignatories.value.performedBy && 
        !loadedPerformers.value.some(user => user.id === localSignatories.value.performedBy)) {
      userIdsToLoad.push(localSignatories.value.performedBy);
    }
    
    if (localSignatories.value.verifiedBy && 
        !loadedVerifiers.value.some(user => user.id === localSignatories.value.verifiedBy)) {
      userIdsToLoad.push(localSignatories.value.verifiedBy);
    }
    
    if (localSignatories.value.pathologist && 
        !loadedPathologists.value.some(user => user.id === localSignatories.value.pathologist)) {
      userIdsToLoad.push(localSignatories.value.pathologist);
    }
    
    // If we have unique IDs to load
    if (userIdsToLoad.length > 0) {
      const uniqueIds = [...new Set(userIdsToLoad)];
      console.log('Loading additional users by ID:', uniqueIds);
      
      // Fetch additional users by ID
      try {
        const userPromises = uniqueIds.map(id => api.get(`/users/${id}`));
        const userResponses = await Promise.all(userPromises);
        
        // Add fetched users to appropriate lists
        userResponses.forEach(response => {
          const user = response.data;
          if (!user || !user.id) return;
          
          // Check if this user ID matches any of our signatories
          if (user.id === localSignatories.value.performedBy) {
            // Add to performers if not already there
            if (!loadedPerformers.value.some(p => p.id === user.id)) {
              loadedPerformers.value.push(user);
              console.log(`Added performer ${user.name} to performers list`);
            }
          }
          
          if (user.id === localSignatories.value.verifiedBy) {
            // Add to verifiers if not already there
            if (!loadedVerifiers.value.some(v => v.id === user.id)) {
              loadedVerifiers.value.push(user);
              console.log(`Added verifier ${user.name} to verifiers list`);
            }
          }
          
          if (user.id === localSignatories.value.pathologist) {
            // Add to pathologists if not already there
            if (!loadedPathologists.value.some(p => p.id === user.id)) {
              loadedPathologists.value.push(user);
              console.log(`Added pathologist ${user.name} to pathologists list`);
            }
          }
        });
      } catch (error) {
        console.error('Error loading users by ID:', error);
      }
    }
  } catch (error) {
    console.error('Error loading role users:', error);
    ElMessage.error('Failed to load signatories');
  } finally {
    loadingRoles.value = false;
  }
};

// Import the calculate age method from the parent component
const calculateAge = (dob) => {
  if (!dob) return 'N/A';
  
  try {
    const birthDate = parseISO(dob);
    const today = new Date();
    
    // Calculate differences
    const years = differenceInYears(today, birthDate);
    const months = differenceInMonths(today, birthDate);
    const days = differenceInDays(today, birthDate);
    
    // Return appropriate unit based on age
    if (years >= 1) {
      return `${years}`;
    } else if (months >= 1) {
      return `${months}`;
    } else {
      return `${days}`;
    }
  } catch (e) {
    console.error('Error calculating age:', e);
    return 'Invalid Date';
  }
};

// Compute the suffix unit based on the patient's age
const suffixUnit = computed(() => {
  if (!props.requestDetails?.patient?.date_of_birth) return 'years';
  
  const birthDate = parseISO(props.requestDetails.patient.date_of_birth);
  const today = new Date();
  
  const years = differenceInYears(today, birthDate);
  const months = differenceInMonths(today, birthDate);
  
  if (years >= 1) {
    return years === 1 ? 'year' : 'years';
  } else if (months >= 1) {
    return months === 1 ? 'month' : 'months';
  } else {
    return 'days';
  }
});

// Helper function to determine the correct parameter name
const getParameterName = (param, test) => {
  // Log the parameter object to debug
  console.log('Getting name for parameter:', param);
  
  // First try to get name directly from param
  if (param.name && typeof param.name === 'string' && param.name.trim() !== '') {
    return param.name.trim();
  }
  
  // Next try test_name
  if (param.test_name && typeof param.test_name === 'string' && param.test_name.trim() !== '') {
    return param.test_name.trim();
  }
  
  // Try the testParameter relationship if it exists
  if (param.testParameter && param.testParameter.name) {
    return param.testParameter.name.trim();
  }
  
  // If all else fails, use the parameter ID
  return `Parameter ${param.id}`;
};

// Fetch parameters for a single test
const fetchTestParameters = async (testId, resultsDataSource = null) => {
  if (!testId) return;
  
  console.log(`Log 4: fetchTestParameters called for testId: ${testId} with resultsDataSource:`, JSON.parse(JSON.stringify(resultsDataSource)));
  
  try {
    // Get the parameters for this test
    const response = await api.get(`/tests/${testId}/parameters`);
    
    if (!Array.isArray(response.data)) {
      console.error('Invalid response data format (not an array):', response.data);
      testParameters.value[testId] = [];
      return;
    }
    
    console.log(`Received ${response.data.length} parameters for test ID ${testId}:`, response.data);
    console.log('First parameter sample:', {
      id: response.data[0]?.id,
      name: response.data[0]?.name,
      conversion_factor: response.data[0]?.conversion_factor,
      unit: response.data[0]?.unit,
      conventional_unit: response.data[0]?.conventional_unit
    });

    let needsMoreDetails = false;
    for (const param of response.data) {
      if (param.test_name && (!param.name || param.name.trim() === '')) {
        param.name = param.test_name;
      }
      if ((!param.name || param.name.trim() === '') && param.id) {
        needsMoreDetails = true;
        break;
      }
    }

    if (needsMoreDetails) {
      console.log('Some parameters missing names, getting more details...');
      try {
        // Pass the resultsDataSource to fetchParametersWithDetails
        await fetchParametersWithDetails(testId, response.data, resultsDataSource);
        return; 
      } catch (detailsError) {
        console.error('Failed to get parameter details, falling back to basic parameters:', detailsError);
      }
    }

    // Determine the source for saved results
    const sourceForSavedResults = resultsDataSource || props.requestDetails.testParameters || [];
    console.log(`Log 5: testId: ${testId} - sourceForSavedResults chosen:`, resultsDataSource ? "resultsDataSource (passed in)" : "props.requestDetails.testParameters (fallback)");
    console.log(`Log 5.1: testId: ${testId} - content of sourceForSavedResults:`, JSON.parse(JSON.stringify(sourceForSavedResults)));

    const savedResults = sourceForSavedResults?.[testId] || [];
    console.log(`Log 6: testId: ${testId} - savedResults extracted for this testId:`, JSON.parse(JSON.stringify(savedResults)));
    
    const savedResultsMap = {};
    if (Array.isArray(savedResults)) {
      savedResults.forEach(result => {
        if (result && result.id) {
          savedResultsMap[result.id] = result;
        }
      });
    }
    
    testParameters.value[testId] = response.data
      .filter(param => param && param.id)
      .map(param => {
        const savedResult = savedResultsMap[param.id];
        // Conditionally log savedResult to avoid JSON error with undefined
        if (savedResult !== undefined) {
          console.log(`Log 7: testId: ${testId}, ParamDef ID: ${param.id} - Found savedResult for merging:`, JSON.parse(JSON.stringify(savedResult)));
        } else {
          console.log(`Log 7: testId: ${testId}, ParamDef ID: ${param.id} - No savedResult found for merging (savedResult is undefined).`);
        }
        const paramObj = {
          id: param.id,
          name: param.name || param.test_name || '',
          test_name: param.test_name || param.name || `Parameter ${param.id}`,
          resultValue: savedResult ? savedResult.resultValue || '' : '',
          unit: param.unit || '',
          instrument: savedResult ? savedResult.instrument || null : null,
          resultComment: savedResult ? (savedResult.resultComment || '') : '',
          flag: savedResult ? savedResult.flag || null : null,
          referenceRanges: Array.isArray(param.reference_ranges) ? param.reference_ranges : [],
          conventional: savedResult ? savedResult.conventional || '' : '',
          conventional_unit: param.conventional_unit || '',
          conversion_factor: param.conversion_factor || null,
          result_id: savedResult && savedResult.result_id ? savedResult.result_id : null
        };
        // ... (log for param state)
        return paramObj;
      });
      
    console.log(`Processed ${testParameters.value[testId].length} parameters for test ID ${testId}`, testParameters.value[testId]);
  } catch (error) {
    console.error(`Error fetching parameters for test ${testId}:`, error);
    testParameters.value[testId] = [];
  }
};

// Fetch parameters with more details - alternative API call
const fetchParametersWithDetails = async (testId, basicParameters = [], resultsDataSource = null) => {
  try {
    const response = await api.get(`/test-parameters?test_id=${testId}&include=details`);
    
    if (Array.isArray(response.data) && response.data.length > 0) {
      console.log('Got detailed parameters:', response.data);
      
      // Determine the source for saved results
      const sourceForSavedResults = resultsDataSource || props.requestDetails.testParameters;
      const savedResults = sourceForSavedResults?.[testId] || [];
      
      const savedResultsMap = {};
      if (Array.isArray(savedResults)) {
        savedResults.forEach(result => {
          if (result && result.id) {
            savedResultsMap[result.id] = result;
          }
        });
      }
      
      const basicParamMap = {};
      basicParameters.forEach(param => {
        if (param && param.id) {
          basicParamMap[param.id] = param;
        }
      });
      
      testParameters.value[testId] = response.data
        .filter(param => param && param.id)
        .map(param => {
          const savedResult = savedResultsMap[param.id];
          const basicParam = basicParamMap[param.id];
          // ... (rest of the mapping logic, ensure it uses savedResult correctly for values)
          const paramObj = {
            id: param.id,
            name: param.name || (basicParam ? basicParam.name : '') || (basicParam ? basicParam.test_name : '') || '',
            test_name: param.test_name || param.name || (basicParam ? basicParam.test_name : '') || (basicParam ? basicParam.name : '') || `Parameter ${param.id}`,
            resultValue: savedResult ? savedResult.resultValue || '' : '',
            unit: param.unit || (basicParam ? basicParam.unit : '') || '',
            instrument: savedResult ? savedResult.instrument || null : null,
            resultComment: savedResult ? savedResult.resultComment || '' : '',
            flag: savedResult ? savedResult.flag || null : null,
            referenceRanges: Array.isArray(param.reference_ranges) ? param.reference_ranges : 
                           (basicParam && Array.isArray(basicParam.reference_ranges) ? 
                            basicParam.reference_ranges : []),
            conventional: savedResult ? savedResult.conventional || '' : '',
            conventional_unit: param.conventional_unit || (basicParam ? basicParam.conventional_unit : '') || '',
            conversion_factor: param.conversion_factor || (basicParam ? basicParam.conversion_factor : null),
            result_id: savedResult && savedResult.result_id ? savedResult.result_id : null
          };
        // ... (log for param state)
          return paramObj;
        });
        
      console.log(`Processed ${testParameters.value[testId].length} detailed parameters for test ID ${testId}`);
    } else {
      console.warn('No detailed parameters found, using basic parameters');
      throw new Error('No detailed parameters available');
    }
  } catch (error) {
    console.error(`Error fetching detailed parameters for test ${testId}:`, error);
    throw error; 
  }
};

// Fetch parameters for all tests in the current request
const fetchAllTestParameters = async (tests, resultsDataSource = null) => {
  if (!tests || tests.length === 0) {
      console.log("No tests provided to fetchAllTestParameters");
      return Promise.resolve(); 
  }
  console.log("Starting fetchAllTestParameters for tests:", tests, "using resultsDataSource:", resultsDataSource);
  parametersLoading.value = true; // Corrected
  testParameters.value = {};
  
  try {
    const promises = tests.map(test => fetchTestParameters(test.id, resultsDataSource));
    await Promise.all(promises);
    console.log("All parameters fetched:", JSON.parse(JSON.stringify(testParameters.value)));
  } catch (error) {
     console.error("Error fetching one or more test parameters:", error);
     ElMessage.error('Failed to load parameters for some tests.');
  } finally {
    parametersLoading.value = false;
  }
  
  return Promise.resolve();
};

const formatDateTime = (data) => {
  if (!data) return 'N/A';
  try {
    return format(parseISO(data), 'yyyy-MM-dd p');
  } catch (e) {
    return 'Invalid Date';
  }
};

const formatDate = (data) => {
  if (!data) return 'N/A';
  try {
    return format(parseISO(data), 'yyyy-MM-dd');
  } catch (e) {
    return 'Invalid Date';
  }
};

// Add try/catch to the first watch function
watch(() => props.requestDetails, async (newDetails) => {
  try {
    if (newDetails && newDetails.tests && Array.isArray(newDetails.tests)) {
      console.log("Request Details updated:", newDetails);
      
      // Additional debug logging for testParameters
      if (newDetails.testParameters) {
        console.log("DEBUG - Examining testParameters structure:");
        Object.keys(newDetails.testParameters).forEach(testId => {
          const params = newDetails.testParameters[testId];
          console.log(`Test ${testId} has ${params.length} parameters:`);
          params.forEach(param => {
            console.log(`Parameter ${param.id}: resultValue = ${param.resultValue}, resultComment = ${param.resultComment}, result_id = ${param.result_id}`);
          });
        });
      }
      
      // Initialize result fields from tests
      resultFields.value = newDetails.tests.map(test => ({
        id: test.id,
        pivot_id: test.pivot?.id,
        testName: test.name,
        unit: test.unit || '',
        referenceRange: test.reference_range || '',
        conventional_unit: test.conventional_unit || '',
        conventional_reference_range: test.conventional_reference_range || '',
        initial_resultValue: test.initial_resultValue || '',
        initial_instrument: test.initial_instrument || null,
        initial_resultComment: test.initial_resultComment || '',
        // Track if the test already has a stored result
        result_id: test.result_id || null
      }));
      
      // Debug logging for non-parameterized tests
      console.log("DEBUG - Non-parameterized test initial values:");
      resultFields.value.forEach(test => {
        if (test.initial_resultValue || test.initial_resultComment) {
          console.log(`Test ${test.id}: result_value = ${test.initial_resultValue}, result_comment = ${test.initial_resultComment}, result_id = ${test.result_id}`);
        }
      });

      // Initialize remarks and comments from request details if available
      localRemarks.value = newDetails.remarks || 'RESULTS VERIFIED';
      localComments.value = newDetails.comment || '';
      
      // Initialize signatories from tests - we need to check any test for the values
      // since they're now stored at the laboratory_request_test level
      if (newDetails.tests.length > 0) {
        // Find first test with performer, verifier, or pathologist info
        const testWithTrackingInfo = newDetails.tests.find(test => 
          test.performer_id || test.verifier_id || test.pathologist_id
        );
        
        console.log("Test with tracking info:", testWithTrackingInfo);
        
        if (testWithTrackingInfo) {
          localSignatories.value = {
            performedBy: testWithTrackingInfo.performer_id || null,
            verifiedBy: testWithTrackingInfo.verifier_id || null,
            pathologist: testWithTrackingInfo.pathologist_id || null
          };
          console.log("Initialized signatories:", localSignatories.value);
        } else {
          // Fallback to old structure if pivot data not available
          localSignatories.value = {
            performedBy: newDetails.performer_id || null,
            verifiedBy: newDetails.verifier_id || null,
            pathologist: newDetails.pathologist_id || null
          };
        }
      }

      if (newDetails.tests.length > 0) {
        // For initial load/prop update, pass newDetails.testParameters as the resultsDataSource
        await fetchAllTestParameters(newDetails.tests, newDetails.testParameters);
        
        // IMPORTANT: Preserve result_ids for parameters that have values and are already saved
        console.log("Checking parameters with values to determine if they're already saved...");
        
        // Get all parameters with values
        let allParams = Object.values(testParameters.value).flat();
        let paramsWithValues = allParams.filter(param => 
          param && param.resultValue && param.resultValue.trim() !== ''
        );
        
        // Check if all parameters with values have result_ids (meaning they're already saved)
        let allSaved = paramsWithValues.every(param => param.result_id !== null);
        let anyUnsaved = paramsWithValues.some(param => param.result_id === null);
        
        console.log(`Parameter save status: 
          - Parameters with values: ${paramsWithValues.length}
          - All saved: ${allSaved}
          - Any unsaved: ${anyUnsaved}
        `);
        
        // Run detailed debug
        debugParameterStates();
        
        // After parameters are loaded, convert any existing values to conventional
        convertAllSIValues();
        
        // Force button state update
        forceButtonUpdate();
      } else {
        console.log("Watcher: Tests array is empty, skipping parameter fetch.");
        testParameters.value = {};
        parametersLoading.value = false;
      }
    } else {
      if (!newDetails) {
        console.log("Watcher: newDetails is null or undefined.");
      } else if (!newDetails.tests) {
        console.log("Watcher: newDetails.tests is null or undefined.");
      } else if (!Array.isArray(newDetails.tests)) {
        console.log("Watcher: newDetails.tests is not an array. Type:", typeof newDetails.tests, "Value:", newDetails.tests);
      }

      resultFields.value = [];
      localRemarks.value = 'RESULTS VERIFIED';
      localComments.value = '';
      localSignatories.value = { performedBy: null, verifiedBy: null, pathologist: null };
      testParameters.value = {};
      parametersLoading.value = false;
    }
  } catch (error) {
    console.error("Error in requestDetails watcher:", error);
    // Handle errors but don't crash
  }
}, { immediate: true, deep: true });

// Add try/catch to the second watch function
watch(() => props.requestDetails, (newDetails) => {
  try {
    if (newDetails && newDetails.tests && Array.isArray(newDetails.tests) && newDetails.tests.length > 0) {
      // Log the structure of the test data to help debug
      console.log('First test from request details:', newDetails.tests[0]);
      
      // Get the category ID from the first test
      const firstTest = newDetails.tests[0];
      
      // Try different possible paths to find the test_category_id
      let categoryId = null;
      
      if (firstTest.test_category_id) {
        categoryId = firstTest.test_category_id;
        testCategoryId.value = categoryId;
        console.log(`Found category ID directly on test: ${categoryId}`);
      } else if (firstTest.testCategory && firstTest.testCategory.id) {
        categoryId = firstTest.testCategory.id;
        testCategoryId.value = categoryId;
        console.log(`Found category ID via testCategory relation: ${categoryId}`);
      } else if (props.categoryId) {
        // Use categoryId prop as fallback
        categoryId = props.categoryId;
        testCategoryId.value = categoryId;
        console.log(`Using category ID from prop: ${categoryId}`);
      } else {
        // In production hematology service, we could hardcode a known ID from your database
        testCategoryId.value = null;
        console.log('Could not find test category ID, loading all equipment');
      }
      
      if (categoryId) {
        loadAnalyzers(categoryId);
      } else {
        // Fallback to loading all equipment
        loadAnalyzers();
      }
    } else {
      console.log('No tests found in request details, loading all equipment');
      loadAnalyzers();
    }
  } catch (error) {
    console.error("Error in equipment loading watcher:", error);
    // Try to load all equipment as a fallback
    loadAnalyzers();
  }
}, { immediate: true });

// --- Patient Age Calculation ---
// Keep QC banner in sync with data changes
watch(testParameters, () => updateQCHoldBanner(), { deep: true });
watch(testCategoryId, () => updateQCHoldBanner());

// --- Patient Age Calculation ---
const calculatePatientAge = (dob) => {
  if (!dob) return { years: null, months: null, days: null, monthsTotal: null, daysTotal: null };
  try {
    const birthDate = new Date(dob);
    const today = new Date();
    const years = differenceInYears(today, birthDate);
    const monthsTotal = differenceInMonths(today, birthDate);
    const daysTotal = differenceInDays(today, birthDate);
    let months = today.getMonth() - birthDate.getMonth();
    let days = today.getDate() - birthDate.getDate();
    if (months < 0 || (months === 0 && days < 0)) {
    }
    if (days < 0) {
      months--;
      const lastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
      days += lastMonth.getDate();
    }
     if (months < 0) {
       months += 12;
     }

    return { years, months, days, monthsTotal, daysTotal };
  } catch (e) {
    console.error("Error calculating age:", e);
    return { years: null, months: null, days: null, monthsTotal: null, daysTotal: null };
  }
};

const patientAge = computed(() => calculatePatientAge(props.requestDetails?.patient?.date_of_birth));
const patientGender = computed(() => props.requestDetails?.patient?.gender || 'Any');

// --- Helper to calculate Conventional Value ---
const calculateConventionalValue = (siValue, factor) => {
  if (siValue === null || siValue === '' || factor === null || factor === undefined) {
    return ''; // Cannot calculate if SI value or factor is missing
  }
  const si = parseFloat(siValue);
  const f = parseFloat(factor);
  if (isNaN(si) || isNaN(f)) {
    return 'ERR'; // Error if values are not numeric
  }
  // Perform calculation and round to a reasonable number of decimal places
  const conventional = si * f;
  // Basic rounding - adjust precision as needed
  return Number(conventional.toFixed(2)).toString();
};

// --- Helper function for flag CSS class (needed in template) ---
const getFlagClass = (flag) => {
    if (flag === 'L' || flag === 'H') return 'flag-critical';
    if (flag === 'l' || flag === 'h') return 'flag-abnormal';
    return '';
};

// Computed properties for violations summary modal
const criticalValues = computed(() => {
  const criticals = [];
  const allParams = Object.values(testParameters.value).flat();
  const currentPatientAge = calculatePatientAge(props.requestDetails?.patient?.date_of_birth);
  const currentPatientGender = props.requestDetails?.patient?.gender || 'Any';
  
  allParams.forEach(param => {
    if (param.flag === 'L' || param.flag === 'H') {
      // Find applicable reference range using same logic as formattedTestResults
      let applicableRange = null;
      
      try {
        if (param.referenceRanges && param.referenceRanges.length > 0) {
          // First try to match by analyzer if available
          if (param.instrument) {
            const equipmentMatches = param.referenceRanges.filter(range => 
              range && ((range.equipment_id && range.equipment_id === param.instrument) || 
              (range.equipment && range.equipment === param.instrument))
            );
            
            if (equipmentMatches.length > 0) {
              applicableRange = equipmentMatches.find(range => {
                if (!range || range.age_min === undefined || range.age_max === undefined) return false;
                const ageMatch = currentPatientAge.years >= range.age_min && 
                                 currentPatientAge.years <= range.age_max;
                const genderMatch = !range.gender || range.gender === 'Any' || 
                                   range.gender === currentPatientGender;
                return ageMatch && genderMatch;
              });
              
              if (!applicableRange) {
                applicableRange = equipmentMatches[0];
              }
            }
          }
          
          // If no equipment match, try just age/gender match
          if (!applicableRange) {
            applicableRange = param.referenceRanges.find(range => {
              if (!range || range.age_min === undefined || range.age_max === undefined) return false;
              const ageMatch = currentPatientAge.years >= range.age_min && 
                             currentPatientAge.years <= range.age_max;
              const genderMatch = !range.gender || range.gender === 'Any' || 
                                range.gender === currentPatientGender;
              return ageMatch && genderMatch;
            });
          }
          
          // If still no match, use the first range
          if (!applicableRange && param.referenceRanges.length > 0) {
            applicableRange = param.referenceRanges[0];
          }
        }
      } catch (error) {
        console.error('Error finding applicable range for critical value:', error);
      }
      
      // Determine reference range string
      let refRangeStr = 'N/A';
      if (applicableRange) {
        refRangeStr = applicableRange.si_range || 
                     ((applicableRange.low_value !== null && applicableRange.high_value !== null) ?
                      `${applicableRange.low_value} - ${applicableRange.high_value}` : 'N/A');
      } else if (param.referenceRange && param.referenceRange !== 'N/A' && param.referenceRange !== 'Not defined') {
        refRangeStr = param.referenceRange;
      }
      
      const flagText = param.flag === 'L' ? 'Below critical limit' : 'Above critical limit';
      criticals.push({
        parameter: param.name || param.test_name || 'Parameter',
        value: param.resultValue || '',
        unit: param.unit || '',
        flag: param.flag,
        refRange: refRangeStr,
        referenceRange: refRangeStr,
        message: `${flagText} (Ref: ${refRangeStr})`
      });
    }
  });
  
  return criticals;
});

// Helper function to get parameter name from delta check
const getDeltaCheckParameterName = (dc) => {
  // Try multiple paths to get the parameter name
  let paramName = dc.test_parameter?.name || 
                  dc.test_parameter?.test_name ||
                  dc.test?.name ||
                  (dc.test_result?.testParameter ? (dc.test_result.testParameter.name || dc.test_result.testParameter.test_name) : null);
  
  // If still not found, try to match from testParameters by test_parameter_id
  if (!paramName || paramName === 'Parameter') {
    const testParamId = dc.test_parameter?.id || 
                        dc.test_result?.test_parameter_id ||
                        dc.test_parameter_id;
    
    if (testParamId) {
      const allParams = Object.values(testParameters.value).flat();
      const matchedParam = allParams.find(p => p.id === testParamId || p.parameterId === testParamId);
      if (matchedParam) {
        paramName = matchedParam.name || matchedParam.test_name || 'Parameter';
      }
    }
  }
  
  return paramName || 'Parameter';
};

// Helper function to get unit from delta check
const getDeltaCheckUnit = (dc) => {
  let unit = dc.test_parameter?.unit || 
             (dc.test_result?.testParameter ? dc.test_result.testParameter.unit : null);
  
  // If still not found, try to match from testParameters
  if (!unit) {
    const testParamId = dc.test_parameter?.id || 
                        dc.test_result?.test_parameter_id ||
                        dc.test_parameter_id;
    
    if (testParamId) {
      const allParams = Object.values(testParameters.value).flat();
      const matchedParam = allParams.find(p => p.id === testParamId || p.parameterId === testParamId);
      if (matchedParam) {
        unit = matchedParam.unit || '';
      }
    }
  }
  
  return unit || '';
};

const totalViolations = computed(() => {
  return criticalValues.value.length + 
         flaggedDeltaChecks.value.length + 
         cdsAlerts.value.length + 
         (qcHoldBannerMessage.value ? 1 : 0);
});

const instrumentFlags = computed(() => {
  const flags = [];
  // Check for HIL indices or other instrument flags
  // This is a placeholder - you may need to add actual instrument flag detection logic
  return flags;
});

const hasInstrumentFlags = computed(() => {
  return instrumentFlags.value.length > 0;
});

// Export violations to sheets
const exportViolationsToSheets = () => {
  const data = [];
  
  // QC & Instrument Status
  if (qcHoldBannerMessage.value || hasInstrumentFlags.value) {
    data.push(['QC & Instrument Status']);
    if (qcHoldBannerMessage.value) {
      data.push(['Failed QC Run', qcHoldBannerMessage.value]);
    }
    instrumentFlags.value.forEach(flag => {
      data.push(['Instrument Flag', flag]);
    });
    data.push([]);
  }
  
  // Critical Values
  if (criticalValues.value.length > 0) {
    data.push(['Critical Values']);
    criticalValues.value.forEach(cv => {
      data.push([cv.parameter, `${cv.value} ${cv.unit}`, cv.message]);
    });
    data.push([]);
  }
  
  // Delta Checks
  if (flaggedDeltaChecks.value.length > 0) {
    data.push(['Delta Checks']);
    flaggedDeltaChecks.value.forEach(dc => {
      const paramName = dc.test_parameter?.name || 'Parameter';
      const change = dc.percentage_difference ? Math.abs(dc.percentage_difference).toFixed(0) + '%' : 'N/A';
      data.push([paramName, `${dc.previous_value} → ${dc.current_value}`, `Change: ${change}`]);
    });
    data.push([]);
  }
  
  // CDS Rules
  if (cdsAlerts.value.length > 0) {
    data.push(['CDS Rules']);
    cdsAlerts.value.forEach(alert => {
      data.push([alert.rule_name, alert.alert_message]);
    });
  }
  
  // Convert to CSV
  const csv = data.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
  const blob = new Blob([csv], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `violations_summary_${props.requestDetails?.id || 'unknown'}_${new Date().toISOString().split('T')[0]}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  window.URL.revokeObjectURL(url);
  
  ElMessage.success('Violations summary exported successfully');
};

// Handle acknowledgment of violations
const handleAcknowledgeViolations = () => {
  if (!violationsAcknowledged.value) {
    ElMessage.warning('Please acknowledge that you have reviewed all violations');
    return;
  }
  
  // Close the modal and return to review
  showViolationsModal.value = false;
  violationsAcknowledged.value = false;
  ElMessage.success('Violations acknowledged. You may now proceed with review actions.');
};

// Helper function to generate table rows for printing
const generatePrintTableRows = () => {
    return formattedTestResults.value.map(result => {
        if (!result.isParameter) return '';
        return `
            <tr>
                <td>${result.parameterName}</td>
                <td>${result.resultValue}</td>
                <td>${result.unit}</td>
                <td>${result.referenceRange}</td>
                <td class="${getFlagClass(result.flag)}">${result.flag || ''}</td>
            </tr>
        `;
    }).join('');
};

// Helper function to get performer name
const getPerformerName = () => {
    return loadedPerformers.value.find(p => p.id === localSignatories.value.performedBy)?.name || 'N/A';
};

// Helper function to get pathologist name
const getPathologistName = () => {
    return loadedPathologists.value.find(p => p.id === localSignatories.value.pathologist)?.name || 'N/A';
};

// --- Computed property for table display with improved reference range handling ---
const formattedTestResults = computed(() => {
  if (!resultFields.value || resultFields.value.length === 0) return [];

  const currentPatientAge = calculatePatientAge(props.requestDetails?.patient?.date_of_birth);
  const currentPatientGender = props.requestDetails?.patient?.gender || 'Any';
  
  // Array to hold the formatted results for the table
  const results = [];
  
  // Iterate through all tests
  resultFields.value.forEach(test => {
    if (!test || !test.id) return;
    
    // Get parameters for this test
    const params = testParameters.value[test.id] || [];
    
    console.log(`Processing parameters for test ${test.id} - ${test.testName}:`, params);
    
    if (params.length > 0) {
      // Process each parameter
      params.forEach(param => {
        if (!param || !param.id) return;
        
        // Debug log each parameter with result_comment value
        console.log(`Processing parameter ${param.id} for UI:`, {
          name: param.name,
          test_name: param.test_name,
          resultValue: param.resultValue,
          resultComment: param.resultComment // <-- Log the result_comment value
        });
        
        // Find applicable reference range
        let applicableRange = null;
        
        try {
          // Find an applicable reference range based on analyzer, age, gender
          if (param.referenceRanges && param.referenceRanges.length > 0) {
            // First try to match by analyzer if available
            if (param.instrument) {
              const equipmentMatches = param.referenceRanges.filter(range => 
                range && ((range.equipment_id && range.equipment_id === param.instrument) || 
                (range.equipment && range.equipment === param.instrument))
              );
              
              if (equipmentMatches.length > 0) {
                // Try to find age/gender match within equipment matches
                applicableRange = equipmentMatches.find(range => {
                  if (!range || range.age_min === undefined || range.age_max === undefined) return false;
                  
                  const ageMatch = currentPatientAge.years >= range.age_min && 
                                   currentPatientAge.years <= range.age_max;
                  const genderMatch = !range.gender || range.gender === 'Any' || 
                                     range.gender === currentPatientGender;
                                     
                  return ageMatch && genderMatch;
                });
                
                // If no specific age/gender match, use the first equipment match
                if (!applicableRange) {
                  applicableRange = equipmentMatches[0];
                }
              }
            }
            
            // If no equipment match, try just age/gender match
            if (!applicableRange) {
              applicableRange = param.referenceRanges.find(range => {
                if (!range || range.age_min === undefined || range.age_max === undefined) return false;
                
                const ageMatch = currentPatientAge.years >= range.age_min && 
                               currentPatientAge.years <= range.age_max;
                const genderMatch = !range.gender || range.gender === 'Any' || 
                                  range.gender === currentPatientGender;
                                  
                return ageMatch && genderMatch;
              });
            }
            
            // If still no match, use the first range
            if (!applicableRange && param.referenceRanges.length > 0) {
              applicableRange = param.referenceRanges[0];
            }
          }
        } catch (error) {
          console.error('Error finding applicable range:', error);
        }
        
        // Create fallback range object if none found
        if (!applicableRange) {
          applicableRange = {
            si_unit: param.unit || '',
            si_range: 'N/A',
            low_value: null,
            high_value: null,
            conv_unit: param.conventional_unit || '',
            conv_range: 'N/A',
            conv_low: null,
            conv_high: null
          };
        }
        
        // Find delta check data for this parameter
        let previousValue = undefined;
        let deltaPercent = undefined;
        const deltaCheck = flaggedDeltaChecks.value.find(dc => {
          const dcParamId = dc.test_parameter?.id || 
                           dc.test_result?.test_parameter_id ||
                           dc.test_parameter_id;
          return dcParamId === param.id || dcParamId === param.parameterId;
        });
        
        if (deltaCheck) {
          previousValue = deltaCheck.previous_value;
          deltaPercent = deltaCheck.percentage_difference;
        }
        
        // Add parameter to results array
        results.push({
          id: `param-${param.id}`,
          isParameter: true,
          isTestHeader: false,
          testId: test.id,
          parameterId: param.id,
          parameterName: getParameterName(param, test), // Use the helper function
          flag: param.flag || null,
          resultValue: param.resultValue || '',
          unit: (applicableRange.si_unit) || param.unit || '',
          referenceRange: applicableRange.si_range || 
                         ((applicableRange.low_value !== null && applicableRange.high_value !== null) ?
                          `${applicableRange.low_value} - ${applicableRange.high_value}` : 'N/A'),
          conventional: param.conventional || '',
          units: applicableRange.conv_unit || param.conventional_unit || '',
          refRange: applicableRange.conv_range || 
                   ((applicableRange.conv_low !== null && applicableRange.conv_high !== null) ?
                    `${applicableRange.conv_low} - ${applicableRange.conv_high}` : 'N/A'),
          instrument: param.instrument || null,
          resultComment: param.resultComment || '', // Ensure resultComment is never null or undefined
          analyzerName: param.analyzer_name || '',
          previousValue: previousValue,
          deltaPercent: deltaPercent
        });
      });
    } else {
      // Non-parameterized test - check if it has a result value
      if (test.initial_resultValue && test.initial_resultValue.trim() !== '') {
        // Log non-parameterized test data for debugging
        console.log(`Non-parameterized test data for ${test.id} for UI:`, {
          test_id: test.id,
          result_value: test.initial_resultValue,
          unit: test.unit,
          analyzer_id: test.initial_instrument,
          result_comment: test.initial_resultComment, // <-- Log the result_comment value
          has_result_id: !!test.result_id
        });
        
        const resultEntry = {
          test_id: test.id,
          remarks: localRemarks.value,  // Add remarks at the test level
          comment: localComments.value  // Add comment at the test level
        };
        
        resultEntry.result_value = test.initial_resultValue.trim();
        resultEntry.unit = test.unit ? test.unit.trim() : '';
        resultEntry.equipment_id = test.initial_instrument;
        resultEntry.result_comment = test.initial_resultComment || '';
        
        // Include the result_id if available to help backend identify existing records
        if (test.result_id) {
          resultEntry.result_id = test.result_id;
        }
        
        results.push(resultEntry);
      }
    }
  });
  
  console.log('Final formattedTestResults:', results);
  return results;
});

const handleClose = () => {
  emit('update:visible', false);
  emit('close');
};

const triggerSave = async () => {
    const payload = preparePayload();
    
    // If no payload (no new results to save), return early
    if (!payload) {
        console.log("No parameters to save, skipping save operation");
        ElMessage.info("No changes to save");
        return;
    }
    
    console.log("================================================================");
    console.log("FULL SAVE PAYLOAD:", JSON.stringify(payload, null, 2));
    console.log("================================================================");
    
    if (!props.requestDetails?.id) {
        ElMessage.error("Cannot save results: Request ID is missing.");
        return;
    }
    
    try {
        const response = await api.post(`/laboratory-requests/${props.requestDetails.id}/results`, payload);
        console.log("Save POST response:", JSON.parse(JSON.stringify(response.data))); 
        ElMessage.success("Results saved successfully");

        let latestResultsDataSource = null;

        if (response.data && Array.isArray(response.data.results) && response.data.results.length > 0) {
            console.log("Attempting to transform POST response results for immediate UI refresh. Full results array:", JSON.parse(JSON.stringify(response.data.results)));
            const transformedResults = {};
            response.data.results.forEach(item => {
                // Access test_parameter_id from the nested test_parameter object
                const testParameterId = item.test_parameter?.id;
                console.log(`Processing item from POST response: test_id = ${item.test_id}, test_parameter.id = ${testParameterId}`, item);
                
                if (item.test_id == null || testParameterId == null) { 
                    console.warn("Skipping result item due to missing (null/undefined) test_id or test_parameter.id. Actual values:", { test_id: item.test_id, test_parameter_id_from_nested: testParameterId }, item);
                    return;
                }

                if (!transformedResults[item.test_id]) {
                    transformedResults[item.test_id] = [];
                }

                // Calculate conventional value for the transformed object
                const siValue = item.result_value;
                const conversionFactorValue = item.test_parameter?.conversion_factor;
                let conventionalValue = ''; 

                if (siValue && siValue.trim() !== '' && conversionFactorValue != null) {
                    const numericSI = parseFloat(siValue);
                    const numericFactor = parseFloat(conversionFactorValue);
                    if (!isNaN(numericSI) && !isNaN(numericFactor)) {
                        conventionalValue = (numericSI * numericFactor).toFixed(2);
                    }
                }

                const paramResultObj = {
                    id: testParameterId,             
                    result_id: item.id,             
                    resultValue: item.result_value,
                    unit: item.unit || item.test_parameter?.unit || '', 
                    instrument: item.equipment_id,
                    resultComment: item.result_comment || item.test_parameter?.result_comment || '',
                    flag: item.flag || null,
                    name: item.test_parameter?.name || item.test_parameter?.test_name || '',
                    test_name: item.test_parameter?.test_name || item.test_parameter?.name || '',
                    conventional_unit: item.test_parameter?.conventional_unit || '',
                    conversion_factor: conversionFactorValue, // Use the retrieved factor
                    referenceRanges: Array.isArray(item.test_parameter?.reference_ranges) ? item.test_parameter.reference_ranges : [],
                    conventional: conventionalValue, // Assign calculated conventional value
                };
                transformedResults[item.test_id].push(paramResultObj);
            });
            latestResultsDataSource = transformedResults;
            console.log("Transformed POST results into latestResultsDataSource:", JSON.parse(JSON.stringify(latestResultsDataSource)));
        } else {
            // Fallback to fetching from GET if POST response is not as expected, though this is our problematic path
            console.warn("POST response did not contain usable results. Falling back to GET for latest state (might be stale).");
            const latestRequest = await api.get(`/laboratory-requests/${props.requestDetails.id}`);
            console.log("Full latestRequest.data after save (fallback path):", JSON.parse(JSON.stringify(latestRequest.data)));
            if (latestRequest.data && latestRequest.data.testParameters) { // Still check for the ideal field from GET
                latestResultsDataSource = latestRequest.data.testParameters;
                console.log("Using latestResultsDataSource from GET (fallback path):", JSON.parse(JSON.stringify(latestResultsDataSource)));
            } else {
                console.warn("No testParameters in GET response either (fallback path). latestResultsDataSource remains null.");
            }
        }

        // Always re-fetch all parameters, passing the LATEST results (ideally from POST transform) as the dataSource
        await fetchAllTestParameters(props.requestDetails.tests, latestResultsDataSource);
        convertAllSIValues(); // Explicitly call to recalculate all conventional values after data refresh
        forceButtonUpdate();

        // Optionally, verify button states after save
        nextTick(() => {
            // Count parameters with values
            const allParams = Object.values(testParameters.value).flat();
            const paramsWithValues = allParams.filter(param => 
                param && param.resultValue && param.resultValue.trim() !== ''
            );
            const unsavedParams = paramsWithValues.filter(param => param.result_id === null);
            
            console.log(`Button state verification after save:\n    - Parameters with values: ${paramsWithValues.length}\n    - Unsaved parameters: ${unsavedParams.length}\n    - Save button should be disabled: ${unsavedParams.length === 0}\n    - Print/Validate buttons should be enabled: ${unsavedParams.length === 0}`);
        });

        // Update the status in the laboratory request (this updates the list view)
        api.put(`/laboratory-requests/${props.requestDetails.id}/status`, {
            status: 'Processing Results',
            test_category_id: testCategoryId.value  // Add this parameter
        }).then(() => {
            console.log("Updated laboratory request status to Processing Results");
        }).catch(error => {
            console.error("Error updating laboratory request status:", error);
        });

        // Emit save event to parent
        emit('save', props.requestDetails.id, payload);
        
        // Check CDS rules after saving results
        await checkCdsRules();
    } catch (error) {
        console.error("Error saving results:", error);
        ElMessage.error(`Failed to save results: ${error.response?.data?.message || error.message}`);
    }
};

const handleValidateRelease = async () => {
  // Check button state first
  if (disableValidateButton.value) {
      // Get specific reason for disabling
      const allTestParams = Object.values(testParameters.value).flat();
      const allSaved = allTestParams.every(param => param.result_id !== null);
      const allPrinted = props.requestDetails.tests.every(test => 
        test.pivot && test.pivot.item_status === 'Result Printed'
      );
      
      if (allPrinted) {
          ElMessage.warning("Tests are already in Printed status and cannot be validated again.");
      } else {
          ElMessage.warning("Please save all results before validating and releasing");
      }
      return;
  }

  // Check CDS rules before validation
  await checkCdsRules();
  
  // Block validation if there are blocking CDS rules
  const blockingRules = cdsAlerts.value.filter(r => r.is_blocking);
  if (blockingRules.length > 0) {
    ElMessageBox.alert(
      `CDS Rule(s) Triggered: Validation is blocked until CDS rules are reviewed.\n\n` +
      blockingRules.map(r => `• ${r.rule_name}: ${r.alert_message}`).join('\n'),
      'CDS Validation Blocked',
      { type: 'warning', showClose: false }
    );
    return;
  }

  const proceedValidate = () => {
      const payload = {
          ...preparePayload(),
          status: 'validated'
      };

      console.log("Prepared Validate Payload:", payload);

      if (!props.requestDetails?.id) {
          ElMessage.error("Cannot validate results: Request ID is missing.");
          return;
      }

      // Call the API to validate and save the results
      api.put(`/laboratory-requests/${props.requestDetails.id}/results`, payload)
          .then(response => {
              console.log("Validate response:", response.data);
              
              // Update the laboratory_request_test status to 'Validated'
              return api.put(`/laboratory-requests/${props.requestDetails.id}/status`, {
                  status: 'Result Validated',
                  test_category_id: testCategoryId.value  // Add this parameter
              });
          })
          .then(() => {
              ElMessage.success("Results validated successfully");
              
              // Refresh test statuses to ensure UI is up-to-date
              refreshTestStatuses();
              
              // Check CDS rules after validation
              checkCdsRules();
              
              // Emit an event to notify the parent component
              emit('validate', props.requestDetails.id, payload);

              handleClose();
          })
          .catch(error => {
              console.error("Error validating results:", error);
              const status = error.response?.status;
              const data = error.response?.data || {};
              if (status === 409 && data.error_type === 'cds_block') {
                  ElMessageBox.alert(
                      data.message || 'CDS rule triggered. Review required.',
                      'CDS Validation Blocked',
                      { type: 'warning', showClose: false }
                  );
              } else if (status === 403 && data.error_type === 'qc_lockout') {
                  const issues = data.issues || [];
                  // Build equipment-specific message
                  const lines = issues.map(i => {
                      const equipment = i.equipment_name || `Equipment #${i.equipment_id}`;
                      const param = i.test_parameter_name || `Parameter #${i.test_parameter_id}`;
                      const vio = Array.isArray(i.violations) ? i.violations.join(', ') : i.violations;
                      return `${equipment} → ${param}: ${vio}`;
                    });
                  ElMessageBox.alert(
                      `QC HOLD: Some results run on a failing analyzer are unreliable.\n\n` +
                      lines.join('\n'),
                      'QC Lockout Active',
                      { type: 'error', showClose: false }
                  );
              } else {
                  ElMessage.error(`Failed to validate results: ${data.message || error.message}`);
              }
          });
  };

  // Pre-check analyzer QC hold before hitting backend
  try {
      const involvedEquipmentIds = Array.from(new Set(
          Object.values(testParameters.value)
              .flat()
              .map(p => p.equipment_id)
              .filter(id => id != null)
      ));
      if (involvedEquipmentIds.length > 0 && testCategoryId.value) {
          api.get(`/qc-lockout/status-summary`, { params: { test_category_id: testCategoryId.value } })
              .then(({ data }) => {
                  const failing = (data?.equipment || []).filter(eq => involvedEquipmentIds.includes(eq.id) && eq.on_hold);
                  if (failing.length > 0) {
                      const lines = failing.map(eq => `${eq.name || 'Equipment'}: ${(eq.violations || []).join(', ')}`);
                      ElMessageBox.alert(
                          `QC HOLD: Equipment QC is out of control for this request.\n\n` +
                          lines.join('\n'),
                          'QC Lockout Active',
                          { type: 'error', showClose: false }
                      );
                      return; // Do not proceed
                  }
                  proceedValidate();
              })
              .catch(() => proceedValidate());
          return; // Defer
      }
  } catch (_) {}

  proceedValidate();
  // After action attempts, refresh banner in case backend changed state
  updateQCHoldBanner();

};

// Handle Accept Result (for flagged delta checks)
const handleAcceptResult = () => {
  if (!hasHoldStatus.value) {
    ElMessage.warning('No flagged results to accept');
    return;
  }
  showAcceptModal.value = true;
  acceptForm.value.reviewer_id = currentUser?.id || acceptForm.value.reviewer_id;
  acceptForm.value.reviewed_at = nowTimestamp();
};

// Handle Reject Result (for flagged delta checks)
const handleRejectResult = () => {
  if (!hasHoldStatus.value) {
    ElMessage.warning('No flagged results to reject');
    return;
  }
  showRejectModal.value = true;
  rejectForm.value.reviewer_id = currentUser?.id || rejectForm.value.reviewer_id;
  rejectForm.value.reviewed_at = nowTimestamp();
};

// Handle Re-run Result (for flagged delta checks)
const handleRerunResult = () => {
  if (!hasHoldStatus.value) {
    ElMessage.warning('No flagged results to re-run');
    return;
  }
  showRerunModal.value = true;
};

// Submit Accept action
const submitAccept = async () => {
  if (!acceptFormRef.value) return;
  
  try {
    await acceptFormRef.value.validate();
  } catch (error) {
    return; // Validation failed
  }

  acceptingResult.value = true;
  try {
    // Call backend API to accept and validate the flagged result
    const response = await api.post(`/laboratory-requests/${props.requestDetails.id}/delta-check/accept`, {
      reviewer_id: acceptForm.value.reviewer_id,
      reviewed_at: acceptForm.value.reviewed_at,
      investigation_checklist: acceptForm.value.investigation_checklist,
      accept_reason: acceptForm.value.accept_reason,
      rationale: acceptForm.value.rationale,
      preanalytical_status: acceptForm.value.preanalytical_status,
      flagged_results: flaggedDeltaChecks.value.map(dc => ({
        test_result_id: dc.test_result?.id,
        test_parameter_id: dc.test_parameter?.id,
        original_value: dc.current_value,
        delta_check_id: dc.id
      }))
    });

    ElMessage.success('Result accepted and validated successfully');
    
    // Close modal and refresh data
    showAcceptModal.value = false;
    acceptForm.value.reason = '';
    
    // Refresh flagged checks
    await checkFlaggedDeltaChecks();
    
    // Refresh test statuses
    refreshTestStatuses();
    
    // Emit event to parent
    emit('validate', props.requestDetails.id);
    
    // Optionally close the modal
    // handleClose();
  } catch (error) {
    console.error('Error accepting result:', error);
    ElMessage.error(`Failed to accept result: ${error.response?.data?.message || error.message}`);
  } finally {
    acceptingResult.value = false;
  }
};

// Submit Reject action
const submitReject = async () => {
  if (!rejectFormRef.value) return;
  
  try {
    await rejectFormRef.value.validate();
  } catch (error) {
    return; // Validation failed
  }

  rejectingResult.value = true;
  try {
    // Call backend API to reject and cancel the flagged result
    const response = await api.post(`/laboratory-requests/${props.requestDetails.id}/delta-check/reject`, {
      reviewer_id: rejectForm.value.reviewer_id,
      reviewed_at: rejectForm.value.reviewed_at,
      investigation_checklist: rejectForm.value.investigation_checklist,
      root_cause: rejectForm.value.root_cause,
      action_taken: rejectForm.value.action_taken,
      rejection_comment: rejectForm.value.rejection_comment,
      original_value_canceled: !!rejectForm.value.original_value_canceled,
      flagged_results: flaggedDeltaChecks.value.map(dc => ({
        test_result_id: dc.test_result?.id,
        test_parameter_id: dc.test_parameter?.id,
        original_value: dc.current_value,
        delta_check_id: dc.id
      }))
    });

    ElMessage.success('Result rejected and cancelled successfully');
    
    // Close modal and refresh data
    showRejectModal.value = false;
    rejectForm.value.reason = '';
    
    // Refresh flagged checks
    await checkFlaggedDeltaChecks();
    
    // Refresh test statuses
    refreshTestStatuses();
    
    // Emit event to parent
    emit('save', props.requestDetails.id);
    
    // Optionally close the modal
    // handleClose();
  } catch (error) {
    console.error('Error rejecting result:', error);
    ElMessage.error(`Failed to reject result: ${error.response?.data?.message || error.message}`);
  } finally {
    rejectingResult.value = false;
  }
};

// Submit Re-run action
const submitRerun = async () => {
  rerunningResult.value = true;
  try {
    // Call backend API to mark request as "For Re-run"
    const response = await api.post(`/laboratory-requests/${props.requestDetails.id}/delta-check/rerun`, {
      flagged_results: flaggedDeltaChecks.value.map(dc => ({
        test_result_id: dc.test_result?.id,
        test_parameter_id: dc.test_parameter?.id,
        original_value: dc.current_value,
        delta_check_id: dc.id
      }))
    });

    ElMessage.success('Re-run initiated. Please perform the re-run on the instrument.');
    
    // Close modal
    showRerunModal.value = false;
    
    // Update status to "For Re-run" or "Pending Repeat Analysis"
    await api.put(`/laboratory-requests/${props.requestDetails.id}/status`, {
      status: 'For Re-run',
      test_category_id: testCategoryId.value
    });
    
    // Refresh flagged checks
    await checkFlaggedDeltaChecks();
    
    // Refresh test statuses
    refreshTestStatuses();
    
    // Emit event to parent
    emit('save', props.requestDetails.id);
    
    // Optionally close the modal
    // handleClose();
  } catch (error) {
    console.error('Error initiating re-run:', error);
    ElMessage.error(`Failed to initiate re-run: ${error.response?.data?.message || error.message}`);
  } finally {
    rerunningResult.value = false;
  }
};

const handlePrintRelease = () => {
  // Check button state first
  if (disablePrintButton.value) {
      ElMessage.warning("Please save all results before printing and releasing");
      return;
  }

  const proceedPrint = () => {
      // First create a payload similar to the save payload but with 'completed' status
      const payload = {
          ...preparePayload(),
          status: 'completed' // Set status to 'completed' instead of 'released'
      };

      if (!payload) {
          ElMessage.error("No results to print and release.");
          return;
      }

      console.log("Prepared Print & Release Payload:", payload);

      if (!props.requestDetails?.id) {
          ElMessage.error("Cannot print results: Request ID is missing.");
          return;
      }

      // Show printing dialog
      ElMessage({
          message: 'Printing CBC test results...',
          type: 'info',
          duration: 2000
      });
  
      // Check if tests are already printed directly using the same logic 
      // that's in the disableValidateButton computed property
      const alreadyPrinted = props.requestDetails.tests.every(test => 
          test.pivot && test.pivot.item_status === 'Result Printed'
      );

      // Call the API to release and save the results
      api.put(`/laboratory-requests/${props.requestDetails.id}/results`, payload)
          .then(response => {
              console.log("Print & Release response:", response.data);
              
              // Only update status if not already printed
              if (!alreadyPrinted) {
                  // Update the laboratory_request_test status to 'Released'
                  return api.put(`/laboratory-requests/${props.requestDetails.id}/status`, {
                      status: 'Result Printed',
                      test_category_id: testCategoryId.value  // Add this parameter
                  });
              }
              return Promise.resolve(); // Skip status update if already printed
          })
          .then(() => {
              const successMessage = alreadyPrinted 
                  ? "Results printed successfully" 
                  : "Results printed and released successfully";
                  
              ElMessage.success(successMessage);
              
              // Refresh test statuses to ensure UI is up-to-date
              refreshTestStatuses();
              
              // Open a print dialog with sample CBC results
              const printWindow = window.open('', '_blank');
              if (printWindow) {
                  printWindow.document.write(`
                      <html>
                      <head>
                          <title>CBC Test Results</title>
                          <style>
                              body { font-family: Arial, sans-serif; margin: 20px; }
                              .header { text-align: center; margin-bottom: 20px; }
                              .patient-info { margin-bottom: 20px; }
                              .results { border-collapse: collapse; width: 100%; }
                              .results th, .results td { border: 1px solid #ddd; padding: 8px; }
                              .results th { background-color: #f2f2f2; }
                          </style>
                      </head>
                      <body>
                          <div class="header">
                              <h2>Complete Blood Count (CBC) Results</h2>
                          </div>
                          <div class="patient-info">
                              <strong>Patient:</strong> ${props.requestDetails?.patient?.name || ''}<br/>
                              <strong>Specimen ID:</strong> ${props.requestDetails?.sample_id || ''}
                          </div>
                          <table class="results">
                              <tr><th>Parameter</th><th>Result</th><th>Unit</th></tr>
                              ${Object.values(testParameters.value).flat().map(p => `
                                  <tr><td>${p.test_name || ''}</td><td>${p.result_value ?? ''}</td><td>${p.unit || ''}</td></tr>
                              `).join('')}
                          </table>
                      </body>
                      </html>
                  `);
                  printWindow.document.close();
                  printWindow.focus();
                  printWindow.print();
              }
          })
          .catch(error => {
              console.error("Error printing & releasing results:", error);
              ElMessage.error(`Failed to print and release results: ${error.response?.data?.message || error.message}`);
          });
  };

  // Pre-check analyzer QC hold before printing
  try {
      const involvedAnalyzerIds = Array.from(new Set(
          Object.values(testParameters.value)
              .flat()
              .map(p => p.equipment_id)
              .filter(id => id != null)
      ));
      if (involvedEquipmentIds.length > 0 && testCategoryId.value) {
          api.get(`/qc-lockout/status-summary`, { params: { test_category_id: testCategoryId.value } })
              .then(({ data }) => {
                  const failing = (data?.equipment || []).filter(eq => involvedEquipmentIds.includes(eq.id) && eq.on_hold);
                  if (failing.length > 0) {
                      const lines = failing.map(eq => `${eq.name || 'Equipment'}: ${(eq.violations || []).join(', ')}`);
                      ElMessageBox.alert(
                          `QC HOLD: Printing is blocked until QC passes for the equipment.\n\n` +
                          lines.join('\n'),
                          'QC Lockout Active',
                          { type: 'error', showClose: false }
                      );
                      return; // Block printing
                  }
                  proceedPrint();
              })
              .catch(() => proceedPrint());
          return; // Defer
      }
  } catch (_) {}

  proceedPrint();
  // After action attempts, refresh banner in case backend changed state
  updateQCHoldBanner();

  // Check if tests are already printed directly using the same logic 
  // that's in the disableValidateButton computed property
  const alreadyPrinted = props.requestDetails.tests.every(test => 
      test.pivot && test.pivot.item_status === 'Result Printed'
  );

  // Call the API to release and save the results
  api.put(`/laboratory-requests/${props.requestDetails.id}/results`, payload)
      .then(response => {
          console.log("Print & Release response:", response.data);
          
          // Only update status if not already printed
          if (!alreadyPrinted) {
              // Update the laboratory_request_test status to 'Released'
              return api.put(`/laboratory-requests/${props.requestDetails.id}/status`, {
                  status: 'Result Printed',
                  test_category_id: testCategoryId.value  // Add this parameter
              });
          }
          return Promise.resolve(); // Skip status update if already printed
      })
      .then(() => {
          const successMessage = alreadyPrinted 
              ? "Results printed successfully" 
              : "Results printed and released successfully";
              
          ElMessage.success(successMessage);
          
          // Refresh test statuses to ensure UI is up-to-date
          refreshTestStatuses();
          
          // Open a print dialog with sample CBC results
          const printWindow = window.open('', '_blank');
          if (printWindow) {
              printWindow.document.write(`
                  <html>
                  <head>
                      <title>CBC Test Results</title>
                      <style>
                          body { font-family: Arial, sans-serif; margin: 20px; }
                          .header { text-align: center; margin-bottom: 20px; }
                          .patient-info { margin-bottom: 20px; }
                          .info-row { display: flex; margin-bottom: 5px; }
                          .info-label { font-weight: bold; width: 150px; }
                          table { width: 100%; border-collapse: collapse; }
                          th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                          th { background-color: #f2f2f2; }
                          .footer { margin-top: 30px; }
                          .signatures { display: flex; justify-content: space-between; margin-top: 80px; }
                          .signature-line { border-top: 1px solid #000; width: 200px; text-align: center; padding-top: 5px; }
                          .flag-abnormal { color: orange; font-weight: bold; }
                          .flag-critical { color: red; font-weight: bold; }
                      </style>
                  </head>
                  <body>
                      <div class="header">
                          <h2>HOSPITAL LABORATORY MANAGEMENT SYSTEM</h2>
                          <h3>CBC TEST RESULTS</h3>
                      </div>
                      
                      <div class="patient-info">
                          <div class="info-row">
                              <div class="info-label">Patient ID:</div>
                              <div>${props.requestDetails.patient?.id || 'N/A'}</div>
                          </div>
                          <div class="info-row">
                              <div class="info-label">Patient Name:</div>
                              <div>${props.requestDetails.patient?.last_name || ''}, ${props.requestDetails.patient?.first_name || ''} ${props.requestDetails.patient?.middle_name || ''}</div>
                          </div>
                          <div class="info-row">
                              <div class="info-label">Date of Birth:</div>
                              <div>${props.requestDetails.patient?.date_of_birth ? format(parseISO(props.requestDetails.patient?.date_of_birth), 'yyyy-MM-dd') : 'N/A'}</div>
                          </div>
                          <div class="info-row">
                              <div class="info-label">Age / Gender:</div>
                              <div>${calculateAge(props.requestDetails.patient?.date_of_birth)} / ${props.requestDetails.patient?.gender || 'N/A'}</div>
                          </div>
                      </div>
                      
                      <div class="test-info">
                          <h3>Test Information</h3>
                          <div class="info-row">
                              <div class="info-label">Sample ID:</div>
                              <div>${props.requestDetails.sample_id || 'N/A'}</div>
                          </div>
                          <div class="info-row">
                              <div class="info-label">Collection Date:</div>
                              <div>${props.requestDetails.request_date ? format(parseISO(props.requestDetails.request_date), 'yyyy-MM-dd HH:mm') : 'N/A'}</div>
                          </div>
                          <div class="info-row">
                              <div class="info-label">Requesting Doctor:</div>
                              <div>${props.requestDetails.doctor?.name || 'N/A'}</div>
                          </div>
                      </div>
                      
                      <h3>Test Results</h3>
                      <table>
                          <thead>
                              <tr>
                                  <th>Parameter</th>
                                  <th>Result</th>
                                  <th>Unit</th>
                                  <th>Reference Range</th>
                                  <th>Flag</th>
                              </tr>
                          </thead>
                          <tbody>
                              ${generatePrintTableRows()}
                          </tbody>
                      </table>
                      
                      <div class="signatures">
                          <div class="signature">
                              <div class="signature-line">
                                  ${getPerformerName()}
                              </div>
                              <div>Medical Technologist</div>
                          </div>
                          <div class="signature">
                              <div class="signature-line">
                                  ${getPathologistName()}
                              </div>
                              <div>Pathologist</div>
                          </div>
                      </div>
                      
                      <div style="margin-top: 30px; text-align: center;">
                          <p>This is a computer-generated report and does not require a signature.</p>
                          <p>Date Printed: ${format(new Date(), 'yyyy-MM-dd HH:mm')}</p>
                      </div>
                  </body>
                  </html>
              `);
              
              // Trigger the print dialog
              printWindow.document.close();
              printWindow.focus();
              printWindow.print();
              
              // Close the window after printing
              setTimeout(() => {
                  printWindow.close();
              }, 1000);
          } else {
              ElMessage.warning("Unable to open print preview window. Please check your browser settings.");
          }
          
          // Emit event to refresh the parent component
          emit('print', props.requestDetails.id, payload);

          handleClose();
      })
      .catch(error => {
          console.error("Error printing and releasing results:", error);
          ElMessage.error(`Failed to print and release results: ${error.response?.data?.message || error.message}`);
      });
};

const handlePreview = () => {
  console.log('Preview triggered');
  ElMessage.info('Preview function not yet implemented.');
};

const handleSelectionChange = (selection) => {
  console.log('Selection changed:', selection);
};

const updateResultValue = (row, value) => {
  if (!row.isParameter || !row.testId || !row.parameterId) return;
  
  // Find the parameter in testParameters and update it directly
  const testParams = testParameters.value[row.testId];
  if (!testParams) return;
  
  const paramToUpdate = testParams.find(p => p.id === row.parameterId);
  if (!paramToUpdate) return;
  
  // Track if this is a new value or a modified value
  const hadPreviousValue = paramToUpdate.resultValue && paramToUpdate.resultValue.trim() !== '';
  
  // Update the value in the source
  paramToUpdate.resultValue = row.resultValue;
  const hasNewValue = paramToUpdate.resultValue && paramToUpdate.resultValue.trim() !== '';
  
  // Always mark as unsaved when any value is entered or changed
  if (hasNewValue) {
    if (paramToUpdate.result_id !== null) {
      console.log(`Marking parameter ${paramToUpdate.id} as unsaved due to value change`);
    }
    paramToUpdate.result_id = null; // Mark as needing to be saved
  }
  // If value is cleared, also mark as unsaved
  if (!hasNewValue) {
    paramToUpdate.result_id = null;
  }
  // Force Vue reactivity by replacing the array reference
  testParameters.value[row.testId] = [...testParameters.value[row.testId]];
  // Force button state update after changing value
  forceButtonUpdate();
  
  // Process only if we have a value
  if (paramToUpdate.resultValue && !isNaN(parseFloat(paramToUpdate.resultValue))) {
    const numericValue = parseFloat(paramToUpdate.resultValue);
    
    // Calculate conventional value locally using conversion_factor
    if (paramToUpdate.conversion_factor && !isNaN(parseFloat(paramToUpdate.conversion_factor))) {
      const conversionFactor = parseFloat(paramToUpdate.conversion_factor);
      paramToUpdate.conventional = (numericValue * conversionFactor).toFixed(2);
      console.log(`Calculated conventional value: ${paramToUpdate.conventional} using factor: ${conversionFactor}`);
    } else {
      console.log('No conversion factor available for parameter:', {
        id: paramToUpdate.id,
        name: paramToUpdate.name,
        conversion_factor: paramToUpdate.conversion_factor
      });
      paramToUpdate.conventional = '';
    }
    
    // Call API only for flag calculation
    api.post('/results/calculate-derived', {
      result: numericValue,
      unit: paramToUpdate.unit || row.unit,
      test_parameter_id: row.parameterId, 
      patient_id: props.requestDetails?.patient?.id
    }).then(response => {
      // Only update flag from API response
      paramToUpdate.flag = response.data.flag;
      console.log('Updated flag after API call:', paramToUpdate.flag);
      // Force Vue reactivity by replacing the array reference
      testParameters.value[row.testId] = [...testParameters.value[row.testId]];
      // Force button state update after flag calculation (in case of async changes)
      forceButtonUpdate();
    }).catch(error => {
      console.error('API Error:', error);
      paramToUpdate.flag = 'ERR';
      testParameters.value[row.testId] = [...testParameters.value[row.testId]];
      forceButtonUpdate();
    });
  } else {
    // Clear values if input is empty or invalid
    paramToUpdate.flag = null;
    paramToUpdate.conventional = '';
    testParameters.value[row.testId] = [...testParameters.value[row.testId]];
    forceButtonUpdate();
  }
};

// Helper function to filter out DELTA_CHECK_HISTORY from remarks
const filterDeltaCheckHistory = (comment) => {
  if (!comment || typeof comment !== 'string') return '';
  
  // Remove DELTA_CHECK_HISTORY patterns (case-insensitive)
  // This regex matches "DELTA_CHECK_HISTORY:" followed by any text until end of line or next DELTA_CHECK_HISTORY
  let filtered = comment.replace(/DELTA_CHECK_HISTORY:.*?(?=\n|DELTA_CHECK_HISTORY:|$)/gi, '');
  
  // Clean up multiple consecutive newlines
  filtered = filtered.replace(/\n\n+/g, '\n').trim();
  
  return filtered;
};

// Update remarks without DELTA_CHECK_HISTORY - preserve delta history in backend but don't show in UI
const updateRemarksWithoutDeltaHistory = (row, newValue) => {
  if (!row.isParameter || !row.testId || !row.parameterId) return;
  
  const testParams = testParameters.value[row.testId];
  if (!testParams) {
    console.error(`updateRemarksWithoutDeltaHistory: No test parameters found for test ID ${row.testId}`);
    return;
  }
  
  const paramIndex = testParams.findIndex(p => p.id === row.parameterId);
  if (paramIndex === -1) {
    console.error(`updateRemarksWithoutDeltaHistory: Parameter with ID ${row.parameterId} not found in test ${row.testId}`);
    return;
  }

  const paramToUpdate = testParams[paramIndex];
  
  // Get the original comment to preserve DELTA_CHECK_HISTORY
  const originalComment = paramToUpdate.resultComment || '';
  
  // Extract DELTA_CHECK_HISTORY parts from original comment
  const deltaHistoryMatches = originalComment.match(/DELTA_CHECK_HISTORY:.*?(?=\n|DELTA_CHECK_HISTORY:|$)/gi);
  const deltaHistoryText = deltaHistoryMatches ? deltaHistoryMatches.join('\n') : '';
  
  // Combine new user input with preserved delta history
  const updatedComment = deltaHistoryText 
    ? (newValue.trim() ? `${deltaHistoryText}\n${newValue}` : deltaHistoryText)
    : newValue;
  
  // Update the parameter comment
  paramToUpdate.resultComment = updatedComment;
  row.resultComment = updatedComment; // Also update row for consistency
  
  // Mark as unsaved if needed
  if (paramToUpdate.result_id !== null) {
    paramToUpdate.result_id = null;
  }
  
  // Create a new array for reactivity
  const newTestParamsArray = [...testParams];
  testParameters.value[row.testId] = newTestParamsArray;
  
  forceButtonUpdate();
};

const updateParameterComment = (row) => {
  if (!row.isParameter || !row.testId || !row.parameterId) return;
  
  const testParams = testParameters.value[row.testId];
  if (!testParams) {
    console.error(`updateParameterComment: No test parameters found for test ID ${row.testId}`);
    return;
  }
  
  const paramIndex = testParams.findIndex(p => p.id === row.parameterId);
  if (paramIndex === -1) {
    console.error(`updateParameterComment: Parameter with ID ${row.parameterId} not found in test ${row.testId}`);
    return;
  }

  const paramToUpdate = testParams[paramIndex];

  // Explicitly sync the row.resultComment (updated by v-model) to the paramToUpdate object
  // This ensures the source data in testParameters.value is updated.
  if (paramToUpdate.resultComment !== row.resultComment) {
    paramToUpdate.resultComment = row.resultComment;
    console.log(`Synced row.resultComment to paramToUpdate.resultComment for param ${paramToUpdate.id}`);
  }

  let markedUnsaved = false;
  if (paramToUpdate.result_id !== null) { // If it was previously saved
    paramToUpdate.result_id = null;
    markedUnsaved = true;
    console.log(`Marking parameter ${paramToUpdate.id} (testId: ${row.testId}) as unsaved due to comment change on a previously saved item.`);
  } else if (paramToUpdate.resultValue && paramToUpdate.resultValue.trim() !== '' && paramToUpdate.resultComment && paramToUpdate.resultComment.trim() !== '') {
    // If it has a value, and now a comment, and was already unsaved, it remains unsaved (result_id is already null)
    // No change to result_id needed, but Save button logic will see it as an unsaved item with content.
  } else if (paramToUpdate.resultComment && paramToUpdate.resultComment.trim() !== ''){
    // If it only has a comment (no resultValue yet) and was previously no-result-id (e.g. pristine)
    paramToUpdate.result_id = null; // Stays unsaved, but this confirms it for Save button logic
    markedUnsaved = true; // Consider it a change that needs saving
    console.log(`Marking parameter ${paramToUpdate.id} (testId: ${row.testId}) as unsaved due to new comment on an item without a value/prior save.`);
  }
  
  // Create a new array for the specific test to ensure reactivity for that part of testParameters.value
  // This is important if child properties of objects within an array are changed.
  const newTestParamsArray = [...testParams];
  testParameters.value[row.testId] = newTestParamsArray;
  
  forceButtonUpdate();
  
  console.log(`updateParameterComment: Parameter ${row.parameterId} (testId: ${row.testId}) resultComment is now: "${paramToUpdate.resultComment}". Marked unsaved: ${markedUnsaved}. Current result_id: ${paramToUpdate.result_id}`);
};

// Define this HEMATOLOGY_CATEGORY_ID constant - replace 1 with your actual ID from the database
const HEMATOLOGY_CATEGORY_ID = 4; // !!! REPLACE 1 with your actual Hematology Category ID !!!

const preparePayload = () => {
    // Build the results array format needed by the API
    const results = [];

    resultFields.value.forEach(test => {
        const params = testParameters.value[test.id] || [];
        const resultEntry = {
            test_id: test.id,
            remarks: localRemarks.value,  // Add remarks at the test level
            comment: localComments.value  // Add comment at the test level
        };

        if (params.length > 0) {
            // If the test has parameters, add them to the payload
            // Filter out parameters with missing or empty result values
            const validParams = params.filter(param => {
                // Check if the parameter has a result value
                return param.resultValue && param.resultValue.trim() !== '';
            });
            
            if (validParams.length > 0) {
                resultEntry.parameters = validParams.map(param => {
                    // Explicitly get the resultComment from the parameter, defaulting to empty string
                    // This ensures we're using the correct value from the UI
                    const resultComment = typeof param.resultComment === 'string' ? param.resultComment : '';
                    
                    // Log each parameter's data for debugging
                    console.log(`Parameter data for ${param.id}:`, {
                        test_parameter_id: param.id,
                        result_value: param.resultValue,
                        unit: param.unit,
                        equipment_id: param.instrument,
                        result_comment: resultComment,
                        has_result_id: !!param.result_id
                    });
                    
                    // Make sure result_comment is included in the parameters array only
                    const parameterData = {
                        test_parameter_id: param.id,
                        result_value: param.resultValue.trim(),
                        unit: param.unit ? param.unit.trim() : '',
                        equipment_id: param.instrument,
                        result_comment: resultComment, // Always use the string version, never undefined
                        // Include the result_id if available to help backend identify existing records
                        ...(param.result_id ? { result_id: param.result_id } : {})
                    };
                    
                    return parameterData;
                });
                
                // Only add the test if it has valid parameters to save
                results.push(resultEntry);
            }
        } else {
            // Non-parameterized test - check if it has a result value
            if (test.initial_resultValue && test.initial_resultValue.trim() !== '') {
                // Ensure result_comment is properly defined for non-parameterized tests too
                const resultComment = typeof test.initial_resultComment === 'undefined' ? '' : test.initial_resultComment;
                
                // Log non-parameterized test data for debugging
                console.log(`Non-parameterized test data for ${test.id}:`, {
                    test_id: test.id,
                    result_value: test.initial_resultValue,
                    unit: test.unit,
                    analyzer_id: test.initial_instrument,
                    result_comment: resultComment,
                    has_result_id: !!test.result_id
                });
                
                // Add the non-parameterized test result
                resultEntry.result_value = test.initial_resultValue.trim();
                resultEntry.unit = test.unit ? test.unit.trim() : '';
                resultEntry.equipment_id = test.initial_instrument;
                resultEntry.result_comment = resultComment; // Use our fixed variable
                
                // Include the result_id if available
                if (test.result_id) {
                    resultEntry.result_id = test.result_id;
                }
                
                results.push(resultEntry);
            }
        }
    });
    
    // If there are no results, return null to prevent an empty request
    if (results.length === 0) {
        return null;
    }
    
    return {
        laboratory_request_id: props.requestDetails.id,
        active_test_category_id: testCategoryId.value, // ADD THIS LINE - the key change
        results: results,
        performer_id: localSignatories.value.performedBy,
        verifier_id: localSignatories.value.verifiedBy,
        pathologist_id: localSignatories.value.pathologist,
        status: 'saved'
    };
};

const handleKeyDown = (event) => {
  if (props.visible) {
    switch (event.key) {
      case 'F1':
        event.preventDefault();
        handlePreview();
        break;
      case 'F2':
        event.preventDefault();
        // Only trigger save if the button is not disabled
        if (!disableSaveButton.value) {
          triggerSave();
        } else {
          ElMessage.info('All results are already saved');
        }
        break;
      case 'F4':
        event.preventDefault();
        // Only trigger print & release if the button is not disabled
        if (!disablePrintButton.value) {
          handlePrintRelease();
        } else {
          ElMessage.warning('Please save all results before printing and releasing');
        }
        break;
      case 'F5':
        event.preventDefault();
        // Only trigger validate & release if the button is not disabled
        if (!disableValidateButton.value) {
          handleValidateRelease();
        } else {
          // Determine the specific reason for disabling
          const allTestParams = Object.values(testParameters.value).flat();
          const allSaved = allTestParams.every(param => param.result_id !== null);
          const allPrinted = props.requestDetails.tests.every(test => 
            test.pivot && test.pivot.item_status === 'Result Printed'
          );
          
          if (allPrinted) {
            ElMessage.warning('Tests are already in Printed status and cannot be validated again');
          } else if (!allSaved) {
            ElMessage.warning('Please save all results before validating and releasing');
          }
        }
        break;
    }
  }
};

// Function to load equipment based on the tests in the current request
const loadAnalyzersForTests = () => {
  // If we have test results
  if (resultFields.value && resultFields.value.length > 0) {
    console.log('First test in resultFields:', resultFields.value[0]);
    
    // Try to get the test category ID from the first test
    const firstTest = resultFields.value[0];
    
    // Try different paths to find the category ID
    let categoryId = null;
    
    if (firstTest.test_category_id) {
      categoryId = firstTest.test_category_id;
      console.log(`Found category ID in resultFields: ${categoryId}`);
    } else if (firstTest.testCategory && firstTest.testCategory.id) {
      categoryId = firstTest.testCategory.id;
      console.log(`Found category ID via testCategory relation: ${categoryId}`);
    } else {
      // For a Hematology modal, we could hard-code the Hematology category ID
      // This is just a fallback for when the data structure doesn't contain what we need
      console.log('Could not find category ID in resultFields');
    }
    
    if (categoryId) {
      loadAnalyzers(categoryId);
    } else {
      // If no category ID is found, try to get it from the request details
      if (props.requestDetails && props.requestDetails.tests && 
          props.requestDetails.tests.length > 0 && 
          props.requestDetails.tests[0].test_category_id) {
        loadAnalyzers(props.requestDetails.tests[0].test_category_id);
      } else {
        // If all else fails, load all equipment
        loadAnalyzers();
      }
    }
  } else {
    // If no tests are loaded yet, load all equipment
    console.log('No tests in resultFields, loading all equipment');
    loadAnalyzers();
  }
};

// Add a watch to log button state changes for debugging
// watch(allResultsSaved, (newValue, oldValue) => {
//   if (newValue !== oldValue) {
//     console.log(`Button states updated: Save button disabled: ${newValue}, Print/Validate buttons enabled: ${newValue}`);
//   }
//   
//   // Update our reactive variable for immediate UI updates
//   isResultsSaved.value = newValue;
// });
// 
// // Watch for testsArePrinted changes
// watch(testsArePrinted, (newValue, oldValue) => {
//   if (newValue !== oldValue) {
//     console.log(`Printed state updated: Tests are printed: ${newValue}, Validate is disabled: ${newValue}`);
//   }
//   
//   // Update our reactive variable for immediate UI updates
//   resultsArePrinted.value = newValue;
// });

// Watch for changes in the request details to load category-specific equipment
watch(() => props.requestDetails, (newDetails) => {
  if (newDetails && newDetails.tests && Array.isArray(newDetails.tests) && newDetails.tests.length > 0) {
    // Log the structure of the test data to help debug
    console.log('First test from request details:', newDetails.tests[0]);
    
    // Get the category ID from the first test
    const firstTest = newDetails.tests[0];
    
    // Try different possible paths to find the test_category_id
    let categoryId = null;
    
    if (firstTest.test_category_id) {
      categoryId = firstTest.test_category_id;
      console.log(`Found category ID directly on test: ${categoryId}`);
    } else if (firstTest.testCategory && firstTest.testCategory.id) {
      categoryId = firstTest.testCategory.id;
      console.log(`Found category ID via testCategory relation: ${categoryId}`);
    } else {
      // In production hematology service, we could hardcode a known ID from your database
      console.log('Could not find test category ID, loading all equipment');
    }
    
    if (categoryId) {
      loadAnalyzers(categoryId);
    } else {
      // Fallback to loading all equipment
      loadAnalyzers();
    }
  } else {
    console.log('No tests found in request details, loading all equipment');
    loadAnalyzers();
  }
}, { immediate: true });

// Watch for modal visibility to check CDS rules and flagged delta checks when opened
watch(() => props.visible, async (newVal) => {
  if (newVal) {
    // Check for flagged delta checks when modal opens
    await checkFlaggedDeltaChecks();
    // Check CDS rules when modal opens
    if (props.requestDetails?.id) {
      await checkCdsRules();
    }
  } else {
    // Clear alerts when modal closes
    cdsAlerts.value = [];
    flaggedDeltaChecks.value = [];
    hasHoldStatus.value = false;
  }
});

// Watch for violations modal to reset acknowledgment when closed
watch(() => showViolationsModal.value, (isOpen) => {
  if (!isOpen) {
    violationsAcknowledged.value = false;
    activeViolationTab.value = 'summary';
  }
});

// Watch for testParameters to be loaded and check for flagged results
watch(() => testParameters.value, async () => {
  // After parameters are loaded, check again for flagged delta checks
  // This is useful because we now have result_ids available
  if (props.visible && props.requestDetails?.id && Object.keys(testParameters.value).length > 0) {
    // Wait a bit for the data to be fully processed
    await new Promise(resolve => setTimeout(resolve, 500));
    console.log('Test parameters loaded, re-checking for flagged delta checks...');
    await checkFlaggedDeltaChecks();
  }
}, { deep: true });

// Remove the initialization of old reactive variables in onMounted
onMounted(() => {
  loadAnalyzersForTests();
  loadRoleUsers(); // Load role-specific users
  window.addEventListener('keydown', handleKeyDown);
  // Initialize QC banner
  updateQCHoldBanner();
  
  // Check CDS rules on mount if modal is visible
  if (props.visible && props.requestDetails?.id) {
    checkCdsRules();
  }
  
  // Initialize button states immediately
  nextTick(() => {
    console.log('Component mounted - initializing button states');
    
    // Check for parameters with values
    const allParams = Object.values(testParameters.value).flat();
    console.log(`Found ${allParams.length} total parameters on initial load`);
    
    // Run detailed debug
    debugParameterStates();
    
    // CRITICAL FIX: Check if any parameters have been saved previously
    // If all parameters with values already have result_ids, we should enable Print/Validate buttons
    const paramsWithValues = allParams.filter(param => 
      param && param.resultValue && param.resultValue.trim() !== ''
    );
    
    const unsavedParams = paramsWithValues.filter(param => param.result_id === null);
    
    // If we have parameters with values but all are already saved (have result_ids),
    // we should NOT mark them as unsaved - this keeps Save button disabled and Print/Validate enabled
    if (paramsWithValues.length > 0 && unsavedParams.length === 0) {
      console.log('All parameters with values already have result_ids - keeping them marked as saved');
      // No need to change result_ids, they should remain as is
    }
    // If we have any unsaved parameters with values, we should proceed as before
    else if (paramsWithValues.length > 0) {
      console.log('Parameters with values found, marking as unsaved to enable Save button');
      paramsWithValues.forEach(param => {
        // Clear result_id to mark as unsaved
        param.result_id = null;
        console.log(`Marked parameter ${param.id} with value "${param.resultValue}" as unsaved`);
      });
    }
    
    // Force update after setting initial state
    forceButtonUpdate();
    
    // Run debug again after modifications
    console.log('After initial state setup:');
    debugParameterStates();
  });
  
  // Enhanced debugging for signatories
  console.group('HematologyModal Signatories Debug');
  console.log('Initial signatories from props:', {
    performer_id: props.requestDetails?.performer_id,
    verifier_id: props.requestDetails?.verifier_id,
    pathologist_id: props.requestDetails?.pathologist_id
  });
  
  if (props.requestDetails?.tests?.length > 0) {
    console.log('First test tracking data:', {
      id: props.requestDetails.tests[0]?.id,
      performer_id: props.requestDetails.tests[0]?.performer_id,
      verifier_id: props.requestDetails.tests[0]?.verifier_id,
      pathologist_id: props.requestDetails.tests[0]?.pathologist_id
    });
  } else {
    console.log('No tests available in requestDetails');
  }
  
  console.log('Current localSignatories state:', localSignatories.value);
  console.groupEnd();
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
});

  // Enhanced handleInstrumentChange function to work with equipment IDs
const handleInstrumentChange = async (row, equipmentId) => {
  if (!row.isParameter || !row.testId || !row.parameterId) {
    console.log('Missing required data for handleInstrumentChange:', { row });
    return;
  }
  
  console.log(`Changing equipment for parameter ${row.parameterId} to ${equipmentId}`);
  
  // Find the parameter in testParameters
  const testParams = testParameters.value[row.testId];
  if (!testParams) {
    console.error(`No test parameters found for test ID ${row.testId}`);
    return;
  }
  
  const paramToUpdate = testParams.find(p => p.id === row.parameterId);
  if (!paramToUpdate) {
    console.error(`Parameter with ID ${row.parameterId} not found in test ${row.testId}`);
    return;
  }
  
  // Update the instrument in the source (using equipment_id)
  paramToUpdate.instrument = equipmentId;
  
  // Find the equipment name for display
  const selectedEquipment = equipment.value.find(eq => eq.id === parseInt(equipmentId));
  const equipmentName = selectedEquipment ? `${selectedEquipment.manufacturer} - ${selectedEquipment.model}` : 'Unknown';
  console.log(`Found equipment: ${equipmentName}`);
  
  // Fetch updated reference ranges for the parameter with this equipment
  try {
    console.log(`Fetching reference ranges for parameter ${row.parameterId} with equipment ${equipmentId}`);
    
    // Add patient information for accurate reference range selection
    const patientId = props.requestDetails?.patient?.id;
    const patientAge = props.requestDetails?.patient_age;
    const patientGender = props.requestDetails?.patient?.gender || 'Any';
    
    console.log('Patient data for reference range lookup:', { 
      patientId, 
      patientAge, 
      patientGender 
    });
    
    const response = await api.get(`/tests/${row.testId}/parameters/${row.parameterId}/reference-ranges`, {
      params: {
        equipment_id: equipmentId, // Use equipment_id instead of instrument
        patient_id: patientId,
        patient_age: patientAge,
        patient_gender: patientGender
      }
    });
    
    console.log(`Received ${response.data.length} reference ranges for parameter ${row.parameterId}:`, response.data);
    
    if (response.data && Array.isArray(response.data)) {
      // Check if we have any reference ranges
      if (response.data.length === 0) {
        ElMessage.warning(`No reference ranges found for equipment ${equipmentName}`);
        // Create a default reference range so the UI doesn't show N/A
        paramToUpdate.referenceRanges = [{
          id: 'default',
          test_parameter_id: row.parameterId,
          equipment_id: equipmentId,
          equipment: equipmentName,
          si_range: 'Not defined',
          conv_range: 'Not defined',
          low_value: null,
          high_value: null,
          gender: 'Any',
          age_min: 0,
          age_max: 120
        }];
      } else {
        // Update the reference ranges for this parameter
        paramToUpdate.referenceRanges = response.data;
        
        // Print example reference range to help debug
        if (response.data.length > 0) {
          console.log('Example reference range format:', response.data[0]);
        }
      }
      
      // If we have a result value, recalculate the flag
      if (paramToUpdate.resultValue && !isNaN(parseFloat(paramToUpdate.resultValue))) {
        console.log(`Recalculating flag for value ${paramToUpdate.resultValue} with new reference ranges`);
        updateResultValue(row, paramToUpdate.resultValue);
      }
    }
  } catch (error) {
    console.error(`Error fetching reference ranges for parameter ${row.parameterId}:`, error);
    ElMessage.error(`Failed to load reference ranges: ${error.message}`);
  }
};

// Simplified computed property to directly display the referring doctor name
const referringDoctorName = computed(() => {
  // Simply return the referring_doctor value directly from the request details
  // Since it's now stored as full text in the database
  return props.requestDetails.referring_doctor || 'N/A';
});

// Add this function after the other functions to refresh test status data
const refreshTestStatuses = async () => {
  if (!props.requestDetails?.id) {
    console.error('Cannot refresh test statuses: Request ID is missing');
    return;
  }
  
  try {
    // Fetch the latest test status data
    const response = await api.get(`/laboratory-requests/${props.requestDetails.id}`);
    
    if (response.data && response.data.tests) {
      // Update only the status-related properties of each test
      response.data.tests.forEach(updatedTest => {
        // Find the corresponding test in our local data
        const localTestIndex = props.requestDetails.tests.findIndex(t => t.id === updatedTest.id);
        if (localTestIndex !== -1) {
          // Update just the pivot data which contains the status
          props.requestDetails.tests[localTestIndex].pivot = updatedTest.pivot;
          console.log(`Updated test ${updatedTest.id} status to: ${updatedTest.pivot?.item_status}`);
        }
      });
      
      // Force re-evaluation of button states by triggering Vue's reactivity
      nextTick(() => {
        console.log('Test statuses refreshed, button states should update automatically');
        console.log('Print button disabled:', disablePrintButton.value);
        console.log('Validate button disabled:', disableValidateButton.value);
      });
    }
  } catch (error) {
    console.error('Error refreshing test statuses:', error);
  }
};

// Replace isResultsSaved and resultsArePrinted with these computed properties
// directly bound to the UI buttons
// 
const disableSaveButton = computed(() => {
  const allParams = Object.values(testParameters.value).flat();
  const anyEmpty = allParams.some(param => !param.resultValue || param.resultValue.trim() === '');
  const anyUnsaved = allParams.some(param => param.resultValue && param.resultValue.trim() !== '' && param.result_id === null);
console.log('disableSaveButton check:', { anyEmpty, anyUnsaved, allParams });
  if (anyEmpty) return false;
  if (anyUnsaved) return false;
  return true;
});

const disablePrintButton = computed(() => {
  if (qcHoldBannerMessage.value) return true; // Disable when QC lockout active
  // Disable if there are blocking CDS rules
  const hasBlockingCdsRules = cdsAlerts.value.some(r => r.is_blocking);
  if (hasBlockingCdsRules) return true;
  const allParams = Object.values(testParameters.value).flat();
  if (!props.requestDetails?.tests?.length || allParams.length === 0) return true;
  // If any parameter is empty or unsaved, disable Print
  const anyEmpty = allParams.some(param => !param.resultValue || param.resultValue.trim() === '');
  const anyUnsaved = allParams.some(param => param.resultValue && param.resultValue.trim() !== '' && param.result_id === null);
  return anyEmpty || anyUnsaved;
});

const disableValidateButton = computed(() => {
  // CRITICAL: Disable Validate & Release when status is Hold (flagged delta checks)
  if (hasHoldStatus.value) return true;
  
  if (qcHoldBannerMessage.value) return true; // Disable when QC lockout active
  // Disable if there are blocking CDS rules
  const hasBlockingCdsRules = cdsAlerts.value.some(r => r.is_blocking);
  if (hasBlockingCdsRules) return true;
  const allParams = Object.values(testParameters.value).flat();
  if (!props.requestDetails?.tests?.length || allParams.length === 0) return true;
  // If any parameter is empty or unsaved, disable Validate
  const anyEmpty = allParams.some(param => !param.resultValue || param.resultValue.trim() === '');
  const anyUnsaved = allParams.some(param => param.resultValue && param.resultValue.trim() !== '' && param.result_id === null);
  // Also disable if already printed
  const testsArePrinted = props.requestDetails.tests.every(test => test.pivot && test.pivot.item_status === 'Result Printed');
  return anyEmpty || anyUnsaved || testsArePrinted;
});

// Add this function near the top of the script section
const debugParameterStates = () => {
  console.log('Debugging parameter states:');
  const allParams = Object.values(testParameters.value).flat();
  
  // Count parameters by different states
  const withValues = allParams.filter(p => p.resultValue && p.resultValue.trim() !== '');
  const withResultIds = allParams.filter(p => p.result_id !== null);
  const withValuesAndResultIds = allParams.filter(p => 
    p.resultValue && p.resultValue.trim() !== '' && p.result_id !== null
  );
  const withValuesNoResultIds = allParams.filter(p => 
    p.resultValue && p.resultValue.trim() !== '' && p.result_id === null
  );
  
  console.log(`Parameter counts:
    - Total parameters: ${allParams.length}
    - With values: ${withValues.length}
    - With result_ids: ${withResultIds.length}
    - With values AND result_ids: ${withValuesAndResultIds.length}
    - With values BUT NO result_ids: ${withValuesNoResultIds.length}
  `);
  
  // Detailed inspection of parameters with values
  if (withValues.length > 0) {
    console.log('Parameters with values:');
    withValues.forEach(param => {
      console.log(`Parameter ID ${param.id}: value="${param.resultValue}", result_id=${param.result_id}, saved=${param.result_id !== null}`);
    });
  }
  
  // Button states debug
  console.log('Current button states:');
  console.log('- Save button disabled:', disableSaveButton.value);
  console.log('- Print button disabled:', disablePrintButton.value);
  console.log('- Validate button disabled:', disableValidateButton.value);
};

</script>

<style scoped>
.hematology-modal {
  display: flex;
  flex-direction: column;
}

.result-header { 
  display: flex; 
  align-items: center; 
  justify-content: space-between; 
  gap: 12px; 
  padding: 8px 16px; 
  background: #0b1b2b; 
  color: #fff; 
  position: sticky; 
  top: 0; 
  z-index: 1000; 
}
.brand { display: flex; align-items: center; gap: 8px; font-weight: 600; letter-spacing: .3px; }
.brand-logo { font-size: 18px; }
.brand-name { font-size: 13px; opacity: .9; }
.patient-nav { display: flex; gap: 6px; align-items: center; }
.global-actions { display: flex; gap: 8px; }

.critical-left { display: flex; gap: 12px; align-items: center; }
.severity-icon { font-size: 18px; line-height: 1; }
.severity-icon.danger { color: #d93025;}
.severity-icon.warn { color: #e6a23c; }
.critical-text .primary { font-weight: 700; color: #d93025; }
.critical-text .detail { color: #5f6368; font-size: 12px; }
.critical-text .detail .reason-link { color: #1a73e8; text-decoration: underline; cursor: pointer; }
.critical-text .detail .reason-link:hover { color: #1557b0; }
.ai-reco { color: #6b7280; font-size: 12px; margin-top: 2px; }
.critical-actions { display: flex; gap: 8px; }
.delta-cell { white-space: nowrap; }
.delta-cell .prev-val { font-weight: 600; color: #374151; }
.delta-cell .delta-val { color: #6b7280; }
.result-header { 
  display: flex; 
  align-items: center; 
  justify-content: space-between; 
  gap: 12px; 
  padding: 8px 16px; 
  background: #0b1b2b; 
  color: #fff; 
  position: fixed; 
  top: 0; 
  left: 0; 
  right: 0; 
  z-index: 1000; 
  width: 100%;
}

.critical-banner { 
  position: fixed; 
  top: 44px; 
  left: 0;
  right: 0;
  z-index: 999; 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  padding: 10px 16px; 
  background: #fde7e7; 
  border-bottom: 1px solid #f3c2c2; 
  width: 100%;
}

.content-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 4px;
  overflow-y: auto;
  margin-top: 44px; /* Header height */
}

.content-container.with-banner {
  margin-top: 88px; /* Header + Banner height when banner is visible */
}

.layout { display: grid; grid-template-columns: 320px 1fr; gap: 16px; }
.left-sidebar { display: flex; flex-direction: column; gap: 12px; }
.card { background: #f7f9fc; border: 1px solid #dcdfe6; border-radius: 6px; padding: 12px; }
.card-title { font-size: 13px; font-weight: 700; color: #1f2937; margin-bottom: 8px; }
.card-row { font-size: 13px; color: #334155; margin: 2px 0; }
.card-row.big { font-weight: 700; font-size: 16px; color: #1f2937; }
.card-row.subtle { color: #6b7280; }
.qc-chip { padding: 2px 8px; border-radius: 10px; font-size: 12px; margin-left: 6px; }
.qc-chip.pass { background: #e6f9ed; color: #157f3d; }
.qc-chip.fail { background: #fdeaea; color: #c0392b; }
.card.links { display: grid; grid-template-columns: 1fr; gap: 8px; }

.main-content { display: flex; flex-direction: column; min-height: 0; }
.panel-header { margin-bottom: 8px; border-bottom: 1px solid #ced1d4; display: flex; align-items: center; justify-content: space-between; }

.details-sections {
  display: flex;
  gap: 20px;
  margin-bottom: 0px;
}

.section {
  background-color: #f5f7fa;
  border-radius: 4px;
  padding: 12px;
  border: 1px solid #dcdfe6;
}

.patient-details {
  flex: 2;
}

.additional-details {
  flex: 2;
}

.test-results {
  flex: 1;
  min-height: 300px;
}

.section-title {
  font-size: 13px;
  font-weight: bold;
  color: #087bff;
  margin-bottom: 16px;
  padding-bottom: 8px;
  border-bottom: 2px solid #087bff;
}

.header-title {
  font-size: 13px;
  font-weight: bold;
  color: #087bff;
  margin-bottom: 16px;
  padding-bottom: 8px;
}

.compact-form {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  align-items: center;
}

.form-row {
  display: flex;
  gap: 12px;
  align-items: center;
}

.compact-form :deep(.el-form-item) {
  margin-bottom: 8px;
  margin-right: 0;
  display: flex;
  align-items: center;
}

.compact-form :deep(.el-form-item__label) {
  font-size: 13px;
  color: #606266;
  padding-right: 12px;
  line-height: 32px;
  white-space: nowrap;
}

.compact-form :deep(.el-input__wrapper),
.compact-form :deep(.el-select),
.compact-form :deep(.el-date-editor.el-input),
.compact-form :deep(.el-date-editor.el-input__wrapper) {
  height: 32px;
  line-height: 32px;
  box-shadow: none;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
}

.compact-form :deep(.el-input__inner) {
  height: 32px;
  line-height: 32px;
  font-size: 13px;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.total-dif {
  margin-top: 10px;
  padding: 10px;
  background-color: #f56c6c;
  color: white;
  font-weight: bold;
}

.bottom-container {
  bottom: 0;
  left: 0;
  right: 0;
  background-color: #fff;
  display: flex;
  flex-direction: column;
  gap: 16px;
  z-index: 100;
  height: 200px;
}

.top-section {
  display: flex;
  gap: 16px;
  flex: 1;
}

.bottom-section {
  padding-top: 12px;
}

.remarks-panel,
.comments-panel {
  flex: 1;
}

.signatories-section {
  width: 100%;
}

.signatories-section .section-title {
  margin-top: 0;
  font-size: 12px;
  margin-bottom: 12px;
}
.signatories-section .section-signatories {
  flex: 1;
  display: grid; 
  grid-template-columns: 1fr 1fr 1fr; 
  gap: 16px;
}
.panel-label {
  font-weight: bold;
  margin-bottom: 4px;
  font-size: 12px;
}

:deep(.el-textarea__inner) {
  font-size: 12px;
}

:deep(.el-dialog__body) {
  padding: 0;
}

:deep(.el-form-item) {
  margin-bottom: 4px;
}

:deep(.el-form-item__label) {
  font-size: 12px;
  line-height: 28px;
  padding: 0 8px 0 0;
}

:deep(.el-input__wrapper),
:deep(.el-select),
:deep(.el-date-editor.el-input) {
  height: 28px;
  line-height: 28px;
  background-color: #fff;
}

.compact-table :deep(.el-table__header-wrapper) {
  th {
    padding: 4px 0;
    background-color: #f5f7fa;
  }
}

.compact-table :deep(.el-table__body-wrapper) {
  td {
    padding: 2px 0;
  }
}

.compact-table :deep(.el-input__wrapper) {
  padding: 1px 8px;
}

.compact-table :deep(.el-input__inner) {
  height: 24px;
  line-height: 24px;
}

.compact-table :deep(.el-select) {
  width: 100%;
}

.compact-table :deep(.el-table__row) {
  height: 32px;
}

.compact-table :deep(.el-checkbox) {
  height: 24px;
  margin-right: 4px;
}

.flag-abnormal {
  color: orange;
  font-weight: bold;
}
.flag-critical {
  color: red;
  font-weight: bold;
}

.analyzer-name {
  font-size: 12px;
  color: #606266;
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.range-undefined {
  color: #999;
  font-style: italic;
}

/* Violations Summary Modal Styles */
.violations-summary { padding: 8px 0; }
.violation-section { margin-bottom: 24px; }
.violation-section:last-child { margin-bottom: 0; }
.section-header { font-size: 13px; font-weight: 700; color: #1f2937; margin-bottom: 12px; padding-bottom: 6px; border-bottom: 1px solid #e5e7eb; }
.violation-item { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 8px; padding: 6px 0; }
.violation-icon { font-size: 16px; line-height: 1.2; flex-shrink: 0; margin-top: 2px; }
.violation-icon.danger { color: #d93025; }
.violation-icon.warn { color: #e6a23c; }
.violation-text { font-size: 13px; color: #374151; line-height: 1.5; flex: 1; }
.section-purpose { font-size: 12px; color: #6b7280; font-style: italic; margin-top: 8px; padding-left: 24px; }
.no-violations { text-align: center; padding: 24px; color: #6b7280; font-size: 13px; }


/* Tab-based Violations Modal Styles */
.violations-tabs { margin-top: 8px; }
.ai-suggestion-banner { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 8px 12px; margin-bottom: 12px; border-radius: 4px; display: flex; align-items: center; gap: 8px; }
.ai-icon { font-size: 16px; }
.ai-text { font-size: 12px; font-weight: 600; color: #1976d2; }
.hold-summary { background: #f5f5f5; padding: 10px 12px; border-radius: 4px; margin-bottom: 12px; }
.summary-text { margin: 0 0 4px 0; font-size: 13px; color: #1f2937; line-height: 1.4; }
.summary-text strong { color: #d93025; font-weight: 700; }
.instruction-text { margin: 0; font-size: 12px; color: #5f6368; line-height: 1.4; }
/* Violations Modal Header Styles */
.violations-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 8px 16px;
  background: #0b1b2b;
  color: #fff;
  margin: -20px -20px 0 -20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 4px 4px 0 0;
}

.violations-header-brand {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  letter-spacing: 0.3px;
}

.violations-header-icon {
  background-image: url('@/assets/icons/exclamation-mark.png');
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center;
  font-size: 18px;
}

.violations-header-title {
  font-size: 13px;
  opacity: 0.9;
  text-transform: uppercase;
  white-space: nowrap;
}

.violations-header-actions {
  display: flex;
  gap: 8px;
}

.violations-header-actions .el-button {
  color: #fff;
}

.violations-header-actions .el-button--default {
  background-color: #d4a574;
  border-color: #d4a574;
}

.violations-header-actions .el-button--default:hover {
  background-color: #c49564;
  border-color: #c49564;
}

.violations-header-actions .el-button--danger {
  background-color: #d93025;
  border-color: #d93025;
}

.violations-header-actions .el-button--danger:hover {
  background-color: #c0291f;
  border-color: #c0291f;
}

.violations-modal :deep(.el-dialog__body) { 
  max-height: 70vh; 
  overflow-y: auto; 
  padding: 16px 20px; 
}

.violations-modal :deep(.el-tabs__content) { 
  max-height: calc(70vh - 120px); 
  overflow-y: auto; 
}
.violations-list { display: flex; flex-direction: column; gap: 6px; max-height: calc(70vh - 200px); overflow-y: auto; }
.violation-list-item { display: flex; gap: 8px; padding: 6px 10px; border-radius: 4px; border-left: 3px solid; }
.violation-list-item.critical { background: #fef2f2; border-left-color: #d93025; }
.violation-list-item.warning { background: #fffbeb; border-left-color: #e6a23c; }
.list-icon { font-size: 16px; flex-shrink: 0; margin-top: 2px; }
.list-icon.danger { color: #d93025; }
.list-icon.warn { color: #e6a23c; }
.list-content { flex: 1; min-width: 0; }
.list-title { font-size: 13px; font-weight: 700; color: #1f2937; margin-bottom: 2px; line-height: 1.3; }
.list-detail { font-size: 12px; color: #374151; margin-bottom: 2px; line-height: 1.3; }
.list-note { font-size: 12px; color: #6b7280; font-style: italic; line-height: 1.3; }
.violations-footer { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding-top: 12px; border-top: 1px solid #e5e7eb; }
.footer-actions { flex: 1; }
.acknowledgment-section { flex: 1; }
</style> 