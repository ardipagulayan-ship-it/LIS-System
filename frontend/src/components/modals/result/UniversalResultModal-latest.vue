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
    <div class="header-left">
      <div class="brand">
        <div class="brand-logo-wrapper">
          <span class="brand-logo">🧪</span>
        </div>
        <div class="brand-text">
          <span class="brand-name">{{ modalTitle }}</span>
          <span class="brand-tagline">Laboratory Information System</span>
        </div>
      </div>
    </div>

    <div class="global-actions">
      <el-button-group>
        <el-button type="warning" plain @click="triggerSave" :disabled="disableSaveButton">
          <el-icon><Document /></el-icon>
          <span>Save Draft</span>
        </el-button>
        <el-button type="primary" @click="handlePrintRelease" :disabled="disablePrintButton">
          <el-icon><Printer /></el-icon>
          <span>Print & Export</span>
        </el-button>
        <el-button type="success" @click="handleValidateRelease" :disabled="disableValidateButton">
          <el-icon><Check /></el-icon>
          <span>Validate & Release</span>
        </el-button>
      </el-button-group>
      <div class="header-divider"></div>
      <el-button type="danger" circle @click="handleClose" class="close-btn">
        <el-icon><Close /></el-icon>
      </el-button>
    </div>
  </div>

  <!-- Rejected Banner (Read-only lock) -->
  <div v-if="isFullyRejected" class="status-banner banner-rejected">
    <div class="banner-content">
      <div class="banner-icon">
        <el-icon><Lock /></el-icon>
      </div>
      <div class="banner-text">
        <div class="banner-title">REQUEST REJECTED: READ-ONLY</div>
        <div class="banner-detail">
          No further actions allowed.
          <span v-if="latestRejectedAt"> Rejected on {{ formatDateTime(latestRejectedAt) }}</span>
          • <a href="#" @click.prevent="openAuditTrail" class="audit-link">View Audit Trail</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Critical Action Banner (Sticky) -->
  <div
    v-else-if="hasHoldStatus || qcHoldBannerMessage || (cdsAlerts && cdsAlerts.length) || criticalValues.length || cdsAcknowledgeData || deltaAcknowledgeData || criticalAcknowledgeData"
    class="status-banner"
    :class="{ 
      'banner-qc': qcHoldBannerMessage,
      'banner-critical': (((cdsAlerts && cdsAlerts.length) && !cdsAcknowledgeData && !hasHoldStatus && !qcHoldBannerMessage) 
        || (hasHoldStatus && !deltaAcknowledgeData && !qcHoldBannerMessage)
        || (criticalValues.length && !criticalAcknowledgeData && !hasHoldStatus && !qcHoldBannerMessage)),
      'banner-success': (cdsAcknowledgeData || deltaAcknowledgeData || criticalAcknowledgeData) 
        && !((hasHoldStatus && !deltaAcknowledgeData) || qcHoldBannerMessage 
        || ((cdsAlerts && cdsAlerts.length) && !cdsAcknowledgeData) 
        || (criticalValues.length && !criticalAcknowledgeData))
    }"
  >
    <div class="banner-content">
      <div class="banner-icon">
        <el-icon v-if="qcHoldBannerMessage || (hasHoldStatus && !deltaAcknowledgeData) || (criticalValues.length && !criticalAcknowledgeData)"><WarningFilled /></el-icon>
        <el-icon v-else-if="cdsAcknowledgeData || deltaAcknowledgeData || criticalAcknowledgeData"><SuccessFilled /></el-icon>
        <el-icon v-else><InfoFilled /></el-icon>
      </div>
      <div class="banner-text">
        <div class="banner-title">
          {{ 
            (hasHoldStatus && !deltaAcknowledgeData) ? 'CRITICAL HOLD: ACTION REQUIRED' : 
            (qcHoldBannerMessage ? 'QC LOCKOUT ACTIVE' : 
            (criticalValues.length && !criticalAcknowledgeData ? 'CRITICAL RESULT: ACTION REQUIRED' : 
            ((cdsAcknowledgeData || deltaAcknowledgeData || criticalAcknowledgeData) 
              && !((cdsAlerts && cdsAlerts.length) && !cdsAcknowledgeData) 
              ? 'RULES ACKNOWLEDGED' : 'CDS RULE EXCEPTION: ACTION REQUIRED'))) 
          }}
        </div>
        <div class="banner-detail">
          <span v-if="hasHoldStatus && !deltaAcknowledgeData">
            Reason: <a href="#" @click.prevent="showViolationsModal = true" class="audit-link">
              This HOLD has {{ criticalValues.length }} Critical and {{ totalViolations }} Alerts/Flags. Click here to Review.
            </a>
          </span>
          <span v-else-if="qcHoldBannerMessage">Reason: <a href="#" @click.prevent="showViolationsModal = true" class="audit-link">{{ qcHoldBannerMessage }}</a></span>
          <span v-else-if="criticalValues.length && !criticalAcknowledgeData">
            Reason: <a href="#" @click.prevent="showViolationsModal = true" class="audit-link">
              This result has {{ criticalValues.length }} Critical values. Click here to Review.
            </a>
          </span>
          <span v-else-if="(cdsAcknowledgeData || deltaAcknowledgeData || criticalAcknowledgeData) && !((cdsAlerts && cdsAlerts.length) && !cdsAcknowledgeData)">
            Alerts/Flags were acknowledged.
            <a href="#" @click.prevent="showViolationsModal = true" class="audit-link">View Audit Trail</a>
          </span>
          <span v-else-if="cdsAlerts && cdsAlerts.length">
            Reason: <a href="#" @click.prevent="showViolationsModal = true" class="audit-link">{{ cdsAlerts[0]?.rule_name }} - {{ cdsAlerts[0]?.alert_message }}</a>
          </span>
        </div>
      </div>
    </div>
    <div class="banner-actions" v-if="hasHoldStatus && !deltaAcknowledgeData && !isFullyRejected">
      <el-button type="danger" plain size="small" @click="handleRejectResult"><el-icon><Close /></el-icon>Reject</el-button>
      <el-button type="warning" plain size="small" @click="showRerunModal = true" :disabled="selectedTests.length === 0">
        <el-icon><Refresh /></el-icon>Re-run
      </el-button>
      <el-button type="success" size="small" @click="handleAcceptResult"><el-icon><Check /></el-icon>Accept</el-button>
    </div>
  </div>

  <div class="content-container" :class="{ 'with-banner': isFullyRejected || hasHoldStatus || qcHoldBannerMessage || (cdsAlerts && cdsAlerts.length) || criticalValues.length || cdsAcknowledgeData || deltaAcknowledgeData || criticalAcknowledgeData }">
    <!-- Unified Patient & Request Information Banner (Professional Polish) -->
    <div class="patient-card-premium-unified">
      <!-- Left Side: Patient Information (Pill-based) -->
      <div class="patient-info-section">
        <div class="patient-main-info-wrapper">
          <div class="patient-avatar-wrapper">
            <div class="avatar-premium">
              {{ props.requestDetails.patient?.last_name?.[0] }}{{ props.requestDetails.patient?.first_name?.[0] }}
            </div>
            <div class="patient-gender-icon" :class="props.requestDetails.patient?.gender?.toLowerCase()">
              <el-icon v-if="props.requestDetails.patient?.gender?.toLowerCase() === 'male'"><User /></el-icon>
              <el-icon v-else><User /></el-icon>
            </div>
          </div>
          <div class="patient-info-content">
            <div class="patient-name-row">
              <h2 class="patient-name-premium">{{ props.requestDetails.patient?.last_name }}, {{ props.requestDetails.patient?.first_name }}</h2>
              <el-tag size="small" effect="dark" :type="props.requestDetails.priority_level?.toLowerCase() === 'stat' ? 'danger' : 'info'" class="priority-tag">
                {{ props.requestDetails.priority_level || 'Routine' }}
              </el-tag>
            </div>
            <div class="patient-meta-container-premium">
              <div class="patient-meta-row-premium">
                <span class="meta-pill"><el-icon><Memo /></el-icon> <b>ID:</b> {{ props.requestDetails.patient?.id || 'N/A' }}</span>
                <span class="meta-pill"><el-icon><Calendar /></el-icon> <b>BIRTHDAY:</b> {{ formattedBirthday }}</span>
                <span class="meta-pill"><el-icon><User /></el-icon> <b>AGE / SEX:</b> {{ formattedAgeGender }}</span>
              </div>
              <!-- Vertical Divider -->
              <div class="premium-horizontal-divider"></div>
              <div class="patient-meta-row-premium">
                <span class="meta-pill"><el-icon><Phone /></el-icon> <b>CONTACT:</b> {{ formattedContact }}</span>
                <span class="meta-pill"><el-icon><Location /></el-icon> <b>ADDRESS:</b> {{ formattedAddress }}</span>
                <span class="meta-pill"><el-icon><Message /></el-icon> <b>EMAIL:</b> {{ formattedEmail }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Vertical Divider -->
      <div class="premium-vertical-divider"></div>

      <!-- Right Side: Request Details (Row-based, 2-column grid) -->
      <div class="request-details-section">
        <div class="clinical-context-v2-grid-unified">
          <div class="context-item-v2">
            <span class="c-label">ACCESSION NO.:</span>
            <span class="c-value">{{ props.requestDetails.accession_id || props.requestDetails.sample_id || 'N/A' }}</span>
          </div>
          <div class="context-item-v2">
            <span class="c-label">PRIORITY LEVEL:</span>
            <el-tag size="small" effect="dark" :type="props.requestDetails.priority_level?.toLowerCase() === 'stat' ? 'danger' : 'info'" class="priority-tag-inline">
              {{ props.requestDetails.priority_level || 'Routine' }}
            </el-tag>
          </div>
          <div class="context-item-v2">
            <span class="c-label">REQUESTING DOCTOR:</span>
            <span class="c-value">{{ requestingDoctor }}</span>
          </div>
          <div class="context-item-v2">
            <span class="c-label">REFERRED BY:</span>
            <span class="c-value">{{ referringDoctorName }}</span>
          </div>
          <div class="context-item-v2">
            <span class="c-label">PATIENT TYPE:</span>
            <span class="c-value">{{ patientTypeName }}</span>
          </div>
          <div class="context-item-v2">
            <span class="c-label">OR NO.:</span>
            <span class="c-value">{{ orNumber }}</span>
          </div>
          <div class="context-item-v2">
            <span class="c-label">LOCATION:</span>
            <span class="c-value"><el-icon><Location /></el-icon> {{ props.requestDetails.ward?.name || '—' }} / {{ props.requestDetails.room?.name || '—' }}</span>
          </div>
          <div class="context-item-v2">
            <span class="c-label">RECEIVED:</span>
            <span class="c-value">{{ formatDateTime(props.requestDetails.request_date) }}</span>
          </div>
          <div class="context-item-v2">
            <span class="c-label">QC STATUS:</span>
            <div :class="['qc-badge-v2', qcHoldBannerMessage ? 'fail' : 'pass']">
              <el-icon><Check v-if="!qcHoldBannerMessage" /><Close v-else /></el-icon>
              {{ qcHoldBannerMessage ? 'FAIL' : 'PASS' }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Results Table Toolbar (Professional Integrated Style) -->
    <div class="table-actions-toolbar">
      <div class="quick-links">
        <el-button-group>
          <el-button type="primary" plain size="small" @click="showPatientHistory = true">
            <el-icon><Document /></el-icon> Delta Check (6m)
          </el-button>
          <el-button type="info" plain size="small" @click="openAuditTrail">
            <el-icon><Search /></el-icon> Audit Trail
          </el-button>
          <el-button type="default" plain size="small"><el-icon><Plus /></el-icon> Add Test</el-button>
        </el-button-group>
      </div>

      <div class="batch-actions" v-if="!isFullyRejected">
        <el-button-group>
          <el-button type="warning" plain size="small" :disabled="selectedTests.length === 0" @click="openManualRerun">
            <el-icon><Refresh /></el-icon> Re-run Selected ({{ selectedTests.length }})
          </el-button>
          <el-button type="danger" size="small" :disabled="selectedTests.length === 0" @click="openManualReject">
            <el-icon><Close /></el-icon> Reject Selected ({{ selectedTests.length }})
          </el-button>
        </el-button-group>
      </div>
    </div>

    <!-- 2. Action Bar & Secondary Meta -->

    <!-- 3. Main Results Table -->
    <div class="results-table-card">
      <div class="table-header-premium">
        <div class="table-header-info">
          <el-icon class="table-icon"><DocumentCopy /></el-icon>
          <span class="table-title">TEST PARAMETERS & RESULTS</span>
        </div>
        <div class="table-header-legend">
          <div class="legend-pill normal"><span></span> Normal</div>
          <div class="legend-pill abnormal"><span></span> Abnormal</div>
          <div class="legend-pill critical"><span></span> Critical</div>
        </div>
      </div>
      <el-table 
        :data="formattedTestResults" 
        style="width: 100%"
        :row-key="getRowKey"
        :row-class-name="getModalRowClassName"
        @selection-change="handleSelectionChange"
        size="small"
        class="premium-table"
      >
        <el-table-column type="selection" width="48" align="center" />
        
        <el-table-column label="VER" width="60" align="center">
          <template #default="{ row }">
            <span class="version-badge" :class="row.versionStatus?.toLowerCase()">{{ row.versionStatus }}</span>
          </template>
        </el-table-column>

        <el-table-column label="PARAMETER" min-width="220">
          <template #default="{ row }">
            <div class="parameter-cell-premium">
              <span class="p-name">{{ row.parameterName }}</span>
              <el-icon v-if="row.flag === 'L' || row.flag === 'H'" class="status-icon-critical"><WarningFilled /></el-icon>
              <el-icon v-else-if="row.flag === 'l' || row.flag === 'h'" class="status-icon-abnormal"><InfoFilled /></el-icon>
              <el-tooltip v-if="row.is_auto_calculated" content="Auto-calculated" placement="top">
                <span class="calc-indicator">fx</span>
              </el-tooltip>
            </div>
          </template>
        </el-table-column>

        <el-table-column label="FLAG" width="80" align="center">
          <template #default="{ row }">
            <div v-if="row.flag" class="flag-indicator" :class="getFlagCharClass(row.flag)">
              {{ row.flag }}
            </div>
          </template>
        </el-table-column>

        <el-table-column label="S.I. UNITS" align="center">
          <el-table-column label="RESULT" width="130" align="center">
            <template #default="{ row }">
              <div class="result-input-container">
                <el-input
                  v-if="row.isParameter"
                  v-model="row.resultValue"
                  @change="updateResultValue(row, $event)"
                  :disabled="row.is_auto_calculated || isFullyRejected"
                  size="small"
                  :class="['premium-input-center', getFlagClass(row.flag), { 'is-bold': isResultValueBold(row.flag) }]"
                  placeholder="—"
                >
                  <template #suffix v-if="row.isParameter && row.is_auto_calculated">
                    <el-button link @click.stop="overrideAutoCalculated(row)" class="override-btn">
                      <el-icon><Edit /></el-icon>
                    </el-button>
                  </template>
                </el-input>
              </div>
            </template>
          </el-table-column>

          <el-table-column v-if="isForRerun" label="ORIGINAL" width="100" align="center">
            <template #default="{ row }">
              <span class="original-value-text" v-if="row.isParameter">{{ row.originalValue ?? '—' }}</span>
            </template>
          </el-table-column>

          <el-table-column label="PREVIOUS" width="120" align="center">
            <template #default="{ row }">
              <div class="previous-value-group" v-if="row.previousValue !== undefined">
                <span class="prev-val">{{ row.previousValue }}</span>
                <span class="delta-badge" v-if="row.deltaPercent !== undefined" :class="{ 'delta-warning': Math.abs(row.deltaPercent) > 20, 'delta-normal': Math.abs(row.deltaPercent) <= 20 }">
                  {{ Number(row.deltaPercent) > 0 ? '+' : '' }}{{ Number(row.deltaPercent).toFixed(0) }}%
                </span>
              </div>
              <span v-else class="text-placeholder">—</span>
            </template>
          </el-table-column>

          <el-table-column prop="unit" label="UNITS" width="90" align="center">
            <template #default="{ row }">
               <span class="unit-text">{{ row.unit }}</span>
            </template>
          </el-table-column>

          <el-table-column label="REF RANGE" width="140" align="center">
            <template #default="{ row }">
              <span class="ref-range-pill">{{ row.referenceRange }}</span>
            </template>
          </el-table-column>
        </el-table-column>

        <el-table-column label="CONVENTIONAL" align="center">
          <el-table-column label="RESULT" width="100" align="center">
            <template #default="{ row }">
              <span class="conventional-value">{{ row.conventional || '—' }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="units" label="UNITS" width="90" align="center">
             <template #default="{ row }">
               <span class="unit-text-secondary">{{ row.units }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="refRange" label="REF RANGE" width="90" align="center">
             <template #default="{ row }">
               <span class="unit-text-secondary">{{ row.refRange }}</span>
            </template>
          </el-table-column>
        </el-table-column>

        <el-table-column label="INSTRUMENT" width="180" align="center">
          <template #default="{ row }">
            <el-select 
              v-if="row.isParameter"
              v-model="row.instrument" 
              size="small"
              @change="(value) => handleInstrumentChange(row, value)"
              :disabled="isFullyRejected"
              class="premium-select"
            >
              <el-option v-for="eq in equipment" :key="eq.id" :label="eq.model" :value="eq.id" />
            </el-select>
          </template>
        </el-table-column>

        <el-table-column label="COMMENTS" min-width="180">
          <template #default="{ row }">
            <el-input
              v-if="row.isParameter"
              v-model="row.resultComment"
              @blur="updateParameterComment(row)"
              placeholder="Technical note..."
              size="small"
              class="comment-input-minimal"
            >
               <template #prefix><el-icon><EditPen /></el-icon></template>
            </el-input>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- 4. Footer: Notes & Signatories -->
    <div class="bottom-organization">
      <div class="note-box-premium">
        <div class="box-header-premium">
          <el-icon><EditPen /></el-icon>
          <span>Technical Remarks</span>
        </div>
        <el-input 
          v-model="localRemarks" 
          type="textarea" 
          :rows="3" 
          placeholder="Enter internal technical observations..." 
          class="premium-textarea"
        />
      </div>
      <div class="note-box-premium">
        <div class="box-header-premium">
          <el-icon><ChatDotRound /></el-icon>
          <span>Clinical Comments</span>
        </div>
        <el-input 
          v-model="localComments" 
          type="textarea" 
          :rows="3" 
          placeholder="Enter comments for the official report..." 
          class="premium-textarea"
        />
      </div>
    </div>

    <div class="bottom-organization">
      <div class="signatories-card-premium">
        <div class="box-header-premium">
          <el-icon><Avatar /></el-icon>
          <span>AUTHORIZED SIGNATORIES</span>
        </div>
        <div class="sig-grid-v2">
          <div class="sig-field-v2">
            <label>Performed By</label>
            <el-select v-model="localSignatories.performedBy" size="small" filterable class="premium-select">
              <el-option v-for="user in loadedPerformers" :key="user.id" :label="user.name" :value="user.id" />
            </el-select>
          </div>
          <div class="sig-field-v2">
            <label>Verified By</label>
            <el-select v-model="localSignatories.verifiedBy" size="small" filterable class="premium-select">
              <el-option v-for="user in loadedVerifiers" :key="user.id" :label="user.name" :value="user.id" />
            </el-select>
          </div>
          <div class="sig-field-v2">
            <label>Pathologist</label>
            <el-select v-model="localSignatories.pathologist" size="small" filterable class="premium-select">
              <el-option v-for="user in loadedPathologists" :key="user.id" :label="user.name" :value="user.id" />
            </el-select>
          </div>
        </div>
      </div>
    </div>
  </div>

 <!-- Accept Result Modal -->
  <el-dialog
    v-model="showAcceptModal"
    :title="null"
    width="600px"
    class="standard-modal"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    :show-close="false"
  >
    <template #header>
      <div class="modal-header">
        <div class="brand">
          <span class="brand-logo">✅</span>
          <span class="brand-name">Accept Result - Mandatory Documentation</span>
        </div>
        <div class="header-actions">
          <el-button type="danger" size="small" @click="showAcceptModal = false">
            <el-icon><Close /></el-icon>
          </el-button>
        </div>
      </div>
    </template>

    <div class="modal-content-container">
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
    </div>

    <template #footer v-if="!isFullyRejected">
      <div class="modal-footer-standard">
        <div class="footer-info">
          <el-icon><Check /></el-icon>
          <span>Result will be validated and released to the clinician.</span>
        </div>
        <div class="footer-actions">
          <el-button @click="showAcceptModal = false">Cancel</el-button>
          <el-button type="primary" @click="submitAccept" :loading="acceptingResult">
            Accept & Validate
          </el-button>
        </div>
      </div>
    </template>
  </el-dialog>

  <!-- Reject Result Modal -->
  <el-dialog
    v-model="showRejectModal"
    :title="null"
    width="700px"
    class="standard-modal reject-modal-enhanced"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    :show-close="false"
  >
    <template #header>
      <div class="modal-header-danger">
        <div class="brand">
          <span class="brand-logo">🚫</span>
          <div class="title-group">
            <span class="brand-name">Result Rejection Protocol</span>
            <span class="brand-subtitle">Mandatory Quality Control Documentation</span>
          </div>
        </div>
        <div class="header-actions">
          <el-button type="danger" circle plain size="small" @click="showRejectModal = false">
            <el-icon><Close /></el-icon>
          </el-button>
        </div>
      </div>
    </template>

    <div class="modal-content-container">
      <!-- Warning Banner (Always at top) -->
      <div class="rejection-warning-banner">
        <el-icon class="warning-icon"><Warning /></el-icon>
        <div class="warning-text">
          <strong>Clinical Impact Warning:</strong> This action will flag the result as "Rejected". This is a permanent quality record.
        </div>
      </div>

      <el-form :model="rejectForm" :rules="rejectRules" ref="rejectFormRef" label-position="top" class="enhanced-form">
        <!-- TOP SECTION: Reviewer & Scope -->
        <div class="form-grid">
          <!-- Card 1: Reviewer Context (Left) -->
          <div class="form-section-card">
            <div class="section-title-bar">
              <el-icon><User /></el-icon> <span>Reviewer Context</span>
            </div>
            <div class="section-body">
              <el-form-item label="Reviewing MLT/MLS" prop="reviewer_id">
                <el-select v-model="rejectForm.reviewer_id" style="width: 100%" filterable :disabled="true">
                  <template #prefix><el-icon><Avatar /></el-icon></template>
                  <el-option v-if="currentUser" :label="currentUser.name" :value="currentUser.id" />
                </el-select>
              </el-form-item>
              <el-form-item label="Date/Time of Review">
                <el-input :model-value="rejectForm.reviewed_at" readonly>
                  <template #prefix><el-icon><Calendar /></el-icon></template>
                </el-input>
              </el-form-item>
            </div>
          </div>

          <!-- Card 2: Rejection Scope (Right) -->
          <div class="form-section-card">
            <div class="section-title-bar">
              <el-icon><Operation /></el-icon> <span>Rejection Scope</span>
            </div>
            <div class="section-body">
              <el-form-item label="Target for Rejection" prop="scope">
                <el-radio-group v-model="rejectForm.scope" class="scope-radio-group">
                  <el-radio-button label="request">
                    <div class="scope-option">
                      <el-icon><FolderOpened /></el-icon>
                      <span>Entire Request</span>
                    </div>
                  </el-radio-button>
                  <el-radio-button label="tests">
                    <div class="scope-option">
                      <el-icon><Filter /></el-icon>
                      <span>Selected Tests</span>
                    </div>
                  </el-radio-button>
                  <el-radio-button label="results">
                    <div class="scope-option">
                      <el-icon><Document /></el-icon>
                      <span>Selected Parameters</span>
                    </div>
                  </el-radio-button>
                </el-radio-group>
              </el-form-item>
              
              <transition name="el-zoom-in-top">
                <el-form-item v-if="rejectForm.scope === 'tests'" label="Select Tests to Reject" prop="test_ids" class="test-selection-item">
                  <div class="test-checkbox-list">
                    <el-checkbox-group v-model="rejectForm.test_ids">
                      <el-checkbox v-for="test in requestTests" :key="test.id" :label="test.id" border size="small">
                        {{ test.name }}
                      </el-checkbox>
                    </el-checkbox-group>
                  </div>
                </el-form-item>
              </transition>

              <transition name="el-zoom-in-top">
                <el-form-item v-if="rejectForm.scope === 'results'" label="Selected Parameters to Reject" class="test-selection-item">
                  <div class="selected-parameters-list">
                    <el-tag 
                      v-for="item in selectedTests" 
                      :key="item.id" 
                      class="parameter-tag"
                      type="danger"
                      effect="light"
                    >
                      {{ item.parameterName }}
                    </el-tag>
                    <div v-if="selectedTests.length === 0" class="no-selection-warning">
                      <el-icon><Warning /></el-icon> No parameters selected in the table.
                    </div>
                  </div>
                </el-form-item>
              </transition>
            </div>
          </div>
        </div>

        <!-- MIDDLE SECTION: Investigation & Analysis (Full Width) -->
        <div class="form-section-card investigation-card">
          <div class="section-title-bar">
            <el-icon><Search /></el-icon> <span>Investigation & Analysis</span>
          </div>
          <div class="section-body investigation-grid">
            <div class="investigation-left">
              <el-form-item label="Root Cause of Error" prop="root_cause" required>
                <el-select v-model="rejectForm.root_cause" placeholder="Select category" style="width: 100%">
                  <el-option v-for="opt in rootCauseOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
                </el-select>
              </el-form-item>
              <el-form-item label="Action Taken" prop="action_taken" required>
                <el-select v-model="rejectForm.action_taken" placeholder="Select action" style="width: 100%">
                  <el-option v-for="opt in actionTakenOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
                </el-select>
              </el-form-item>
            </div>
            <div class="investigation-right">
              <el-form-item label="Investigation Checklist" prop="investigation_checklist" required>
                <div class="checklist-vertical">
                  <el-checkbox-group v-model="rejectForm.investigation_checklist">
                    <el-checkbox v-for="opt in investigationChecklistOptions" :key="opt.value" :label="opt.value" class="checklist-item-full">
                      {{ opt.label }}
                    </el-checkbox>
                  </el-checkbox-group>
                </div>
              </el-form-item>
            </div>
          </div>
        </div>

        <!-- BOTTOM SECTION: Documentation (Full Width) -->
        <div class="form-section-card documentation-card">
          <div class="section-title-bar">
            <el-icon><Edit /></el-icon> <span>Detailed Documentation</span>
          </div>
          <div class="section-body">
            <el-form-item label="Rejection Rationale / Clinical Comment" prop="rejection_comment" required>
              <el-input 
                v-model="rejectForm.rejection_comment" 
                type="textarea" 
                :rows="3" 
                maxlength="500" 
                show-word-limit 
                placeholder="Provide detailed justification for clinical records (min 50 characters)"
                class="rationale-input-enhanced"
              />
            </el-form-item>
            <div class="cancel-lis-footer">
              <el-checkbox v-model="rejectForm.original_value_canceled" border class="lis-checkbox">
                <el-icon class="lis-icon"><CircleClose /></el-icon>
                <span>Flag original value as "Canceled" in LIS database</span>
              </el-checkbox>
            </div>
          </div>
        </div>
      </el-form>
    </div>

    <template #footer v-if="!isFullyRejected">
      <div class="modal-footer-standard footer-rejection">
        <div class="footer-info">
          <el-icon class="critical-icon"><Warning /></el-icon>
          <div class="impact-text">
            <span>Clinical Impact: High</span>
            <p>Clinicians will be notified of this rejection immediately.</p>
          </div>
        </div>
        <div class="footer-actions">
          <el-button @click="showRejectModal = false" plain>Abort Process</el-button>
          <el-button type="danger" @click="submitReject" :loading="rejectingResult" class="btn-confirm-rejection">
            Confirm Rejection
          </el-button>
        </div>
      </div>
    </template>
  </el-dialog>

  <!-- Re-run Modal -->
  <el-dialog
    v-model="showRerunModal"
    :title="null"
    width="500px"
    class="standard-modal"
    :close-on-click-modal="false"
    :show-close="false"
  >
    <template #header>
      <div class="modal-header">
        <div class="brand">
          <span class="brand-logo">🔄</span>
          <span class="brand-name">Initiate Re-run</span>
        </div>
        <div class="header-actions">
          <el-button type="danger" size="small" @click="showRerunModal = false">
            <el-icon><Close /></el-icon>
          </el-button>
        </div>
      </div>
    </template>

    <div class="modal-content-container">
      <!-- Selected Parameters for Re-run -->
      <div v-if="selectedTests.length > 0" class="selected-for-rerun-box">
        <div class="box-label">Parameters Selected for Re-run:</div>
        <div class="parameter-tags">
          <el-tag 
            v-for="item in selectedTests" 
            :key="item.id" 
            size="small" 
            type="warning" 
            effect="dark"
            class="param-tag"
          >
            {{ item.parameterName }}
          </el-tag>
        </div>
      </div>

      <el-form ref="rerunFormRef" :model="rerunForm" :rules="rerunRules" label-position="top">
        <el-form-item label="Reason for Re-run" prop="reason">
          <el-select v-model="rerunForm.reason" placeholder="Select a reason" style="width: 100%">
            <el-option label="Technical Issue (e.g., instrument flag, QC failure)" value="Technical Issue"></el-option>
            <el-option label="Pre-analytical Issue (e.g., hemolysis, icterus, lipemia)" value="Pre-analytical Issue"></el-option>
            <el-option label="Clinical Suspicion / Delta Check Failure" value="Clinical Suspicion"></el-option>
            <el-option label="Other (Please specify in comments)" value="Other"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="Comments (optional)" prop="comments">
          <el-input v-model="rerunForm.comments" type="textarea" :rows="3" placeholder="Provide additional details if necessary."></el-input>
        </el-form-item>
      </el-form>
    </div>
    <template #footer v-if="!isFullyRejected">
      <div class="modal-footer-standard">
        <div class="footer-info">
          <el-icon><Refresh /></el-icon>
          <span>Selected results will be marked for repeat analysis.</span>
        </div>
        <div class="footer-actions">
          <el-button @click="showRerunModal = false">Cancel</el-button>
          <el-button type="warning" @click="submitRerun" :loading="rerunningResult">
            Confirm Re-run
          </el-button>
        </div>
      </div>
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
          <span class="violations-header-title">Alerts & Flags Review</span>
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
      <!-- Alerts & Flags Tab -->
      <el-tab-pane label="Alerts & Flags" name="summary">
        <div class="violations-summary">
          <div class="hold-summary">
            <p class="summary-text">
              {{ hasHoldStatus ? 'Sample is currently on HOLD.' : 'Sample is not on HOLD.' }}
              This request has
              <span :class="criticalAcknowledgeData ? 'summary-accepted' : ''">
                {{ criticalValues.length }} critical
                <span v-if="criticalAcknowledgeData" class="summary-accepted-tag">(acknowledged)</span>
              </span>,
              <span :class="deltaAcknowledgeData ? 'summary-accepted' : ''">
                {{ flaggedDeltaChecks.length }} delta
                <span v-if="deltaAcknowledgeData" class="summary-accepted-tag">(accepted)</span>
              </span>, and
              <span :class="cdsAcknowledgeData ? 'summary-accepted' : ''">
                {{ cdsAlerts.length }} CDS alerts
                <span v-if="cdsAcknowledgeData" class="summary-accepted-tag">(acknowledged)</span>
              </span>.
            </p>
          </div>

          <div class="violation-section alert-group-card" :class="criticalAcknowledgeData ? 'delta-accepted-card' : ''">
            <div class="section-header">
              Critical Values
              <span v-if="criticalAcknowledgeData" class="delta-accepted-header">
                Acknowledged by {{ criticalAcknowledgeData.user?.name || 'System' }}
                on {{ formatDateTime(criticalAcknowledgeData.context?.reviewed_at || criticalAcknowledgeData.created_at) }}
              </span>
            </div>
            <div class="violation-item" v-for="(cv, idx) in criticalValues" :key="'cv-' + idx">
              <span class="violation-icon danger">●</span>
              <span class="violation-text">{{ cv.parameter }} ({{ cv.value }} {{ cv.unit }}): {{ cv.message }}</span>
            </div>
            <div v-if="criticalValues.length === 0" class="no-violations">
              No critical values detected.
            </div>
          </div>

          <div class="violation-section alert-group-card" :class="deltaAcknowledgeData ? 'delta-accepted-card' : ''">
            <div class="section-header">
              Delta Checks
              <span v-if="deltaAcknowledgeData" class="delta-accepted-header">
                Accepted by {{ deltaAcknowledgeData.user?.name || 'System' }}
                on {{ formatDateTime(deltaAcknowledgeData.context?.reviewed_at || deltaAcknowledgeData.created_at) }}
                <span v-if="deltaAcknowledgeData.context?.flagged_results?.length">
                  ({{ deltaAcknowledgeData.context.flagged_results.length }} results)
                </span>
              </span>
            </div>
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
          </div>

          <div class="violation-section alert-group-card">
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
              No QC or instrument alerts/flags detected.
            </div>
          </div>

          <div class="violation-section alert-group-card" :class="cdsAcknowledgeData ? 'delta-accepted-card' : ''">
            <div class="section-header">
              CDS Rules
              <span v-if="cdsAcknowledgeData" class="delta-accepted-header">
                Acknowledged by {{ cdsAcknowledgeData.user?.name || 'System' }}
                on {{ formatDateTime(cdsAcknowledgeData.context?.reviewed_at || cdsAcknowledgeData.created_at) }}
              </span>
            </div>
            <div class="violation-item" v-for="(alert, idx) in cdsAlerts" :key="'cds-' + idx">
              <span class="violation-icon warn">●</span>
              <span class="violation-text">{{ alert.rule_name }}: {{ alert.alert_message }}</span>
            </div>
            <div v-if="cdsAlerts.length === 0" class="no-violations">
              No CDS rule violations detected.
            </div>
          </div>
        </div>
      </el-tab-pane>

      <!-- Audit Trail Tab -->
      <el-tab-pane label="Audit Trail" name="audit">
        <div class="violations-summary">
          <div class="violation-section">
            <div class="section-header">Decision Audit Trail</div>
            <div v-if="auditTrailLoading" class="loading-state">
              <el-icon class="is-loading"><Loading /></el-icon> Loading audit trail...
            </div>
            <div v-else-if="auditTrail.length === 0" class="no-violations">
              No audit records found for this request.
            </div>
            <div v-else class="audit-timeline">
              <div v-for="audit in auditTrail" :key="audit.id" class="audit-item">
                <div class="audit-time">{{ formatDateTime(audit.created_at) }}</div>
                <div class="audit-content">
                  <span class="audit-type" :class="audit.rule_type.toLowerCase()">{{ audit.rule_type }}</span>
                  <span class="audit-decision" :class="audit.decision">{{ audit.decision.toUpperCase() }}</span>
                  <div class="audit-message">{{ audit.message }}</div>
                  <div
                    v-if="audit.context && (audit.context.action_taken_label || audit.context.action_taken_code || audit.context.action_taken_other || audit.context.accept_reason)"
                    class="audit-action"
                  >
                    <span class="audit-label">Action:</span>
                    <span class="audit-value">
                      {{ audit.context.action_taken_label || audit.context.action_taken_code || audit.context.accept_reason }}
                      <span v-if="audit.context.action_taken_other">
                        – {{ audit.context.action_taken_other }}
                      </span>
                    </span>
                  </div>
                  <div
                    v-if="audit.context && (audit.context.action_comment || audit.context.rationale)"
                    class="audit-comment"
                  >
                    {{ audit.context.action_comment || audit.context.rationale }}
                  </div>
                  <div class="audit-user">By: {{ audit.user?.name || 'System' }}</div>
                </div>
              </div>
            </div>
            <div class="section-purpose">Logs all clinical and technical rule decisions.</div>
          </div>
        </div>
      </el-tab-pane>
    </el-tabs>

    <template #footer v-if="!isFullyRejected">
      <div class="violations-footer">
        <template v-if="hasUnacknowledgedAlerts">
          <div class="acknowledgment-section">
            <el-checkbox v-model="violationsAcknowledged">
              I have reviewed and acknowledge all Alerts/Flags. Ready to proceed with review.
            </el-checkbox>
            <div class="action-taken-fields">
              <el-select
                v-model="violationActionTaken"
                placeholder="Select action taken"
                size="small"
                style="width: 260px"
              >
                <el-option
                  v-for="opt in violationActionOptions"
                  :key="opt.value"
                  :label="opt.label"
                  :value="opt.value"
                />
              </el-select>
              <el-input
                v-if="violationActionTaken === 'OTHER'"
                v-model="violationActionOther"
                size="small"
                placeholder="Specify other action"
                style="margin-top: 8px"
              />
              <el-input
                v-model="violationActionDetails"
                type="textarea"
                :rows="2"
                placeholder="Details / justification (required)"
                style="margin-top: 8px"
              />
            </div>
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
        </template>
        <template v-else>
          <div class="acknowledgment-section">
            <span class="acknowledgment-note">
              Alerts/Flags for this request have already been acknowledged. 
              See the Audit Trail tab for details.
            </span>
          </div>
        </template>
      </div>
    </template>
  </el-dialog>

  <!-- Template Print Dialog -->
  <TemplatePrintDialog
    :visible="showTemplatePrintDialog"
    :requestDetails="props.requestDetails"
    :testCategoryId="testCategoryId"
    :testResults="testParameters"
    @update:visible="showTemplatePrintDialog = $event"
    @print-complete="handlePrintComplete"
  />

  <!-- Patient History Modal -->
  <PatientHistoryModal
    v-model:visible="showPatientHistory"
    :patient-id="props.requestDetails.patient?.id"
    :patient="props.requestDetails.patient"
  />
</el-dialog>
</Teleport>
</template>

<script>
// Global cache for static/shared data to prevent 429 errors
const staticCache = {
  roles: {}, // { roleType: { data: [], timestamp: 0 } }
  equipment: {}, // { categoryId: { data: [], timestamp: 0 } }
  formulas: { data: [], timestamp: 0 },
  deltaChecks: {}, // { requestId: { data: [], timestamp: 0 } }
  cdsRules: {}, // { requestId: { data: [], timestamp: 0 } }
  allFlaggedDeltaChecks: { data: [], timestamp: 0 },
  qcStatus: {}, // { categoryId: { data: [], timestamp: 0 } }
  auditTrail: {} // { requestId: { data: [], timestamp: 0 } }
};
const CACHE_TTL = 30000; // 30 seconds cache
</script>

<script setup>
// Note: staticCache moved to parent script block for persistence across instances

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
  Plus,
  Loading,
  Lock,
  WarningFilled,
  SuccessFilled,
  InfoFilled,
  Calendar,
  Location,
  DocumentCopy,
  Memo,
  Avatar,
  FolderOpened,
  Filter,
  CircleClose,
  EditPen,
  ChatDotRound,
  Edit,
  Search,
  Warning,
  Operation
} from '@element-plus/icons-vue';
import { format, parseISO } from 'date-fns';
import { useAuthStore } from '@/stores/auth';
import api from '@/utils/axios';
import { ElMessage, ElMessageBox } from 'element-plus';
import { differenceInYears, differenceInMonths, differenceInDays } from 'date-fns';
import TemplatePrintDialog from '../TemplatePrintDialog.vue';
import PatientHistoryModal from './PatientHistoryModal.vue';

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

const authStore = useAuthStore();
const currentUser = ref(null);
// Override data source for saved results to ensure version/status accuracy after re-run
const overrideResultsDataSource = ref(null);
// Show Original Result column only when request is tagged For Re-run
const isForRerun = ref(false);

onMounted(async () => {
  currentUser.value = authStore.user;
});

// Re-run state
const showRerunModal = ref(false);
const rerunningResult = ref(false);
const rerunFormRef = ref(null);
const rerunForm = reactive({
  reason: '',
  comments: ''
});

// Template print state
const showTemplatePrintDialog = ref(false);
const showPatientHistory = ref(false);
const rerunRules = {
  reason: [{ required: true, message: 'Please select a reason for the re-run.', trigger: 'change' }]
};

const selectedTests = ref([]);
const requestTests = computed(() => props.requestDetails?.tests || []);

// Read-only lock if all tests are Rejected
const isFullyRejected = computed(() => {
  const tests = requestTests.value || [];
  if (tests.length === 0) return false;
  return tests.every(t => t?.pivot?.item_status === 'Rejected');
});

const latestRejectedAt = computed(() => {
  const tests = requestTests.value || [];
  const stamps = tests
    .filter(t => t?.pivot?.item_status === 'Rejected' && t?.pivot?.rejected_at)
    .map(t => t.pivot.rejected_at);
  if (stamps.length === 0) return null;
  return stamps.sort((a, b) => new Date(b) - new Date(a))[0];
});

const openAuditTrail = () => {
  showViolationsModal.value = true;
  activeViolationTab.value = 'audit';
  fetchAuditTrail();
};

const getSelectedTestIds = () => {
  const ids = selectedTests.value
    .map(item => item.testId || item.test_id || item.test?.id)
    .filter(id => !!id);
  return Array.from(new Set(ids));
};

const handleSelectionChange = (selection) => {
  selectedTests.value = selection;
};

const getRowKey = (row) => {
  // Use the unique ID generated in formattedTestResults (e.g., param-123 or test-456)
  return row.id;
};

const submitRerun = async () => {
  if (!rerunFormRef.value) return;
  await rerunFormRef.value.validate(async (valid) => {
    if (valid) {
      rerunningResult.value = true;
      try {
        const flagged_results = selectedTests.value
          .filter(item => item.result_id) // Only include items with a result_id
          .map(item => ({
            test_result_id: item.result_id,
            test_parameter_id: item.parameterId
          }));

        if (flagged_results.length === 0) {
          ElMessage.error('Please select at least one valid test result to re-run.');
          rerunningResult.value = false;
          return;
        }
        const payload = {
          flagged_results: flagged_results,
          reason: rerunForm.reason,
          comments: rerunForm.comments,
          requested_by_id: currentUser.value?.id
        };

        // Call the API to initiate re-run and capture new results
        const rerunResp = await api.post(`/laboratory-requests/${props.requestDetails.id}/delta-check/rerun`, payload);

        ElMessage.success('Re-run initiated successfully.');
        showRerunModal.value = false;

        try {
          // Build latestResultsDataSource from backend new_test_results
          const latestResultsDataSource = {};
          const newResults = rerunResp?.data?.new_test_results || [];
          newResults.forEach(item => {
            const testId = item.test_id;
            const paramId = item.test_parameter_id;
            if (!testId || !paramId) return;
            if (!latestResultsDataSource[testId]) latestResultsDataSource[testId] = [];
            latestResultsDataSource[testId].push({
              id: paramId,
              result_id: item.id,
              resultValue: item.result_value,
              unit: item.unit || '',
              instrument: item.equipment_id || null,
              resultComment: item.result_comment || '',
              flag: item.flag || null,
              conventional: '',
              conventional_unit: '',
              conversion_factor: null,
              version: item.version || 'R1',
              status: item.status || 'pending'
            });
            // Immediate in-place UI update for version/status
            const params = testParameters.value[testId] || [];
            const idx = params.findIndex(p => p.id === paramId || p.parameterId === paramId);
            if (idx !== -1) {
              params[idx].version = item.version || 'R1';
              params[idx].status = item.status || 'pending';
              params[idx].result_id = item.id;
              params[idx].resultValue = item.result_value || '';
            }
          });
          // Persist override so subsequent refreshes use the correct version/status
          overrideResultsDataSource.value = latestResultsDataSource;
          // Re-fetch parameters, merging with latestResultsDataSource to inject version/status
          await fetchAllTestParameters(props.requestDetails.tests, latestResultsDataSource);
        } catch (e) {
          console.warn('UI refresh after re-run failed:', e);
        }

        // You might want to refresh the data or update the UI accordingly
        emit('save', props.requestDetails.id, flagged_results);

      } catch (error) {
        console.error('Error initiating re-run:', error);
        ElMessage.error('Failed to initiate re-run.');
      } finally {
        rerunningResult.value = false;
      }
    }
  });
};

// Computed property for dynamic modal title
const modalTitle = computed(() => {
  const categoryName = props.categoryName.toLocaleUpperCase() || 'Result';
  return `${categoryName} Result Window`;
});

const localSignatories = ref({ performedBy: null, verifiedBy: null, pathologist: null });
const localRemarks = ref('');
const localComments = ref('');
const resultFields = ref([]);
const auditTrail = ref([]);
const auditTrailLoading = ref(false);

const cdsAcknowledgeData = computed(() => {
  if (!auditTrail.value || auditTrail.value.length === 0) return null;
  return auditTrail.value.find(a => a.rule_type === 'CDS' && a.decision === 'acknowledged');
});

const deltaAcknowledgeData = computed(() => {
  if (!auditTrail.value || auditTrail.value.length === 0) return null;
  return auditTrail.value.find(a => a.rule_type === 'DELTA_CHECK' && a.decision === 'acknowledged');
});

const criticalAcknowledgeData = computed(() => {
  if (!auditTrail.value || auditTrail.value.length === 0) return null;
  return auditTrail.value.find(a => a.rule_type === 'CRITICAL_VALUE' && a.decision === 'acknowledged');
});

const hasAnyRuleAcknowledgment = computed(() => {
  return !!(cdsAcknowledgeData.value || deltaAcknowledgeData.value || criticalAcknowledgeData.value);
});
const hasUnacknowledgedAlerts = computed(() => {
  const hasCds = cdsAlerts.value.length > 0;
  const hasDelta = flaggedDeltaChecks.value.length > 0;
  const hasCritical = criticalValues.value.length > 0;
  return (hasCds && !cdsAcknowledgeData.value)
    || (hasDelta && !deltaAcknowledgeData.value)
    || (hasCritical && !criticalAcknowledgeData.value);
});
const testParameters = ref({});
const parametersLoading = ref(false);
const equipment = ref([]);
const loadingEquipment = ref(false);
// Formula auto-computation state
const formulas = ref([]);
const formulasByTarget = ref({});

const buildFormulasIndex = () => {
  const idx = {};
  (formulas.value || []).forEach(f => {
    const targetId = f.target_test_parameter_id || f.target_parameter_id || (f.target_parameter && f.target_parameter.id);
    if (targetId) idx[targetId] = f;
  });
  formulasByTarget.value = idx;
};

const fetchFormulas = async () => {
  const now = Date.now();
  if (staticCache.formulas.data.length > 0 && (now - staticCache.formulas.timestamp < CACHE_TTL)) {
    console.log('Using cached formulas');
    formulas.value = staticCache.formulas.data;
    buildFormulasIndex();
    return;
  }

  try {
    const resp = await api.get('/formulas');
    // support either { data: [...] } or [...] response shapes
    const data = resp.data && Array.isArray(resp.data) ? resp.data : (resp.data?.data || resp.data || []);
    formulas.value = data;
    
    // Update cache
    staticCache.formulas.data = data;
    staticCache.formulas.timestamp = now;
    
    buildFormulasIndex();
  } catch (e) {
    if (e.response?.status === 429) {
      console.warn('Rate limited while fetching formulas, using stale cache if available');
      if (staticCache.formulas.data.length > 0) {
        formulas.value = staticCache.formulas.data;
        buildFormulasIndex();
        return;
      }
    }
    console.error('Failed to load formulas for auto-computation:', e);
    formulas.value = [];
    formulasByTarget.value = {};
  }
};

const buildResultsMap = () => {
  const map = {};
  Object.values(testParameters.value).flat().forEach(p => {
    if (!p) return;
    const raw = p.resultValue;
    if (raw === undefined || raw === null) return;
    const s = String(raw).trim();
    if (s === '' || isNaN(parseFloat(s))) return;
    map[p.id] = parseFloat(s);
  });
  return map;
};

const evaluateCompiledFormula = (compiled, resultsMap) => {
  if (!compiled) return null;
  // Replace tokens like [123] or {123} with numeric values from resultsMap
  let expr = String(compiled).replace(/[\[\{](\d+)[\]\}]/g, (m, id) => {
    const v = resultsMap[id];
    if (v === undefined) throw new Error('Missing value for parameter ' + id);
    return String(Number(v));
  });

  // Disallow letters (to prevent identifiers) and only allow safe math chars
  if (/[A-Za-z]/.test(expr)) throw new Error('Invalid characters in formula');
  if (/[^0-9+\-*/().%\s]/.test(expr)) throw new Error('Disallowed characters in formula');

  // Simple balanced parentheses check
  let depth = 0;
  for (let ch of expr) {
    if (ch === '(') depth++;
    if (ch === ')') {
      depth--;
      if (depth < 0) throw new Error('Unbalanced parentheses');
    }
  }
  if (depth !== 0) throw new Error('Unbalanced parentheses');

  // Evaluate in a strict function scope
  try {
    // eslint-disable-next-line no-new-func
    const val = Function('"use strict";return (' + expr + ')')();
    if (typeof val !== 'number' || !isFinite(val)) throw new Error('Non-numeric result');
    return val;
  } catch (e) {
    throw new Error('Evaluation error: ' + e.message);
  }
};

const applyComputedValue = (formula, targetParamId, numericValue) => {
  if (numericValue === null || numericValue === undefined || isNaN(Number(numericValue))) return;
  for (const testId of Object.keys(testParameters.value)) {
    const params = testParameters.value[testId] || [];
    const idx = params.findIndex(p => p.id === targetParamId);
    if (idx === -1) continue;
    const param = params[idx];
    const decimals = (formula && (formula.decimal_places || formula.decimal_places === 0)) ? Number(formula.decimal_places) : null;
    const rounded = decimals !== null ? Number(Number(numericValue).toFixed(decimals)) : numericValue;
    param.resultValue = String(rounded);
    param.result_id = null;
    param.is_auto_calculated = true;
    param.auto_formula_id = formula?.id || null;

    // recalc conventional if conversion_factor is available
    if (param.conversion_factor && !isNaN(parseFloat(param.conversion_factor))) {
      try {
        param.conventional = (parseFloat(param.resultValue) * parseFloat(param.conversion_factor)).toFixed(2);
      } catch (e) {
        param.conventional = '';
      }
    } else {
      param.conventional = '';
    }

    // Trigger flag calculation for the auto-filled value
    api.post('/results/calculate-derived', {
      result: Number(param.resultValue),
      unit: param.unit || '',
      test_parameter_id: param.id,
      patient_id: props.requestDetails?.patient?.id,
      equipment_id: param.instrument || null
    }).then(resp => {
      param.flag = resp.data.flag;
      testParameters.value[testId] = [...testParameters.value[testId]];
      forceButtonUpdate();
    }).catch(err => {
      console.error('Flag API error for computed param', err);
      param.flag = 'ERR';
      testParameters.value[testId] = [...testParameters.value[testId]];
      forceButtonUpdate();
    });

    // Ensure Vue reactivity
    testParameters.value[testId] = [...testParameters.value[testId]];
    return;
  }
};

const recalculateFormulasForSourceParameter = () => {
  try {
    const resultsMap = buildResultsMap();
    if (!formulas.value || formulas.value.length === 0) return;
    formulas.value.forEach(f => {
      // Ensure source variables exist and have numeric values
      const sourceIds = (f.source_variables || []).map(v => v.source_test_parameter_id || v.source_parameter_id || v.id).filter(Boolean);
      if (sourceIds.length === 0) return;
      const missing = sourceIds.some(id => resultsMap[id] === undefined);
      if (missing) return; // cannot compute if any source missing
      try {
        const computed = evaluateCompiledFormula(f.compiled_formula || f.compiledFormula || f.compiled || '', resultsMap);
        if (computed !== null && !isNaN(Number(computed))) {
          const targetId = f.target_test_parameter_id || f.target_parameter_id || (f.target_parameter && f.target_parameter.id);
          if (targetId) applyComputedValue(f, targetId, computed);
        }
      } catch (e) {
        // ignore evaluation errors for now
        // console.debug('Formula eval skipped:', e.message);
      }
    });
  } catch (e) {
    console.error('Error during formula recalculation:', e);
  }
};

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
      const categoryId = testCategoryId.value;
      const now = Date.now();
      
      if (staticCache.qcStatus[categoryId] && (now - staticCache.qcStatus[categoryId].timestamp < CACHE_TTL)) {
        console.log(`Using cached QC status for category: ${categoryId}`);
        const data = staticCache.qcStatus[categoryId].data;
        const failing = (data?.equipment || []).filter(eq => involvedEquipmentIds.includes(eq.id) && eq.on_hold);
        if (failing.length > 0) {
          const lines = failing.map(eq => `${eq.name || 'Equipment'}: ${(eq.violations || []).join(', ')}`);
          qcHoldBannerMessage.value = lines.join(' | ');
        } else {
          qcHoldBannerMessage.value = '';
        }
        return;
      }

      api.get(`/qc-lockout/status-summary`, { params: { test_category_id: categoryId } })
        .then(({ data }) => {
          // Update cache
          staticCache.qcStatus[categoryId] = {
            data: data,
            timestamp: now
          };

          const failing = (data?.equipment || []).filter(eq => involvedEquipmentIds.includes(eq.id) && eq.on_hold);
          if (failing.length > 0) {
            const lines = failing.map(eq => `${eq.name || 'Equipment'}: ${(eq.violations || []).join(', ')}`);
            qcHoldBannerMessage.value = lines.join(' | ');
          } else {
            qcHoldBannerMessage.value = '';
          }
        })
        .catch((error) => { 
          if (error.response?.status === 429) {
            console.warn(`Rate limited while fetching QC status for category: ${categoryId}`);
            if (staticCache.qcStatus[categoryId]) {
              const data = staticCache.qcStatus[categoryId].data;
              const failing = (data?.equipment || []).filter(eq => involvedEquipmentIds.includes(eq.id) && eq.on_hold);
              if (failing.length > 0) {
                const lines = failing.map(eq => `${eq.name || 'Equipment'}: ${(eq.violations || []).join(', ')}`);
                qcHoldBannerMessage.value = lines.join(' | ');
              }
            }
          }
          /* keep previous banner state */ 
        });
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

  const requestId = props.requestDetails.id;
  const now = Date.now();
  if (staticCache.cdsRules[requestId] && (now - staticCache.cdsRules[requestId].timestamp < CACHE_TTL)) {
    console.log(`Using cached CDS rules for request: ${requestId}`);
    cdsAlerts.value = staticCache.cdsRules[requestId].data;
    return;
  }

  try {
    const response = await api.get(`/results/check-cds/${props.requestDetails.id}`);
    const data = response.data;
    
    // Update cache
    staticCache.cdsRules[requestId] = {
      data: data.triggered_rules || [],
      timestamp: now
    };

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
    if (error.response?.status === 429) {
      console.warn(`Rate limited while checking CDS rules for request: ${requestId}`);
      if (staticCache.cdsRules[requestId]) {
        cdsAlerts.value = staticCache.cdsRules[requestId].data;
        return;
      }
    }
    console.error('Error checking CDS rules:', error);
    // Don't show error to user, just log it
  }
};

const fetchAuditTrail = async () => {
  if (!props.requestDetails?.id) return;
  
  const requestId = props.requestDetails.id;
  const now = Date.now();
  if (staticCache.auditTrail[requestId] && (now - staticCache.auditTrail[requestId].timestamp < CACHE_TTL)) {
    console.log(`Using cached audit trail for request: ${requestId}`);
    auditTrail.value = staticCache.auditTrail[requestId].data;
    return;
  }

  auditTrailLoading.value = true;
  console.log('Fetching audit trail for request:', requestId);
  try {
    const response = await api.get(`/laboratory-requests/${requestId}/audit-trail`);
    const data = response.data || [];
    
    // Update cache
    staticCache.auditTrail[requestId] = {
      data: data,
      timestamp: now
    };
    
    auditTrail.value = data;
  } catch (error) {
    if (error.response?.status === 429) {
      console.warn(`Rate limited while fetching audit trail for request: ${requestId}`);
      if (staticCache.auditTrail[requestId]) {
        auditTrail.value = staticCache.auditTrail[requestId].data;
        return;
      }
    }
    console.error('Error fetching audit trail:', error);
    ElMessage.error('Failed to load audit trail history');
  } finally {
    auditTrailLoading.value = false;
  }
};

// Delta Check Hold Status - Flagged results detection
const flaggedDeltaChecks = ref([]);
const allDeltaChecks = ref([]);
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
    const now = Date.now();
    let allFlaggedChecks = [];

    if (staticCache.allFlaggedDeltaChecks.data.length > 0 && (now - staticCache.allFlaggedDeltaChecks.timestamp < CACHE_TTL)) {
      console.log('Using cached flagged delta checks');
      allFlaggedChecks = staticCache.allFlaggedDeltaChecks.data;
    } else {
      console.log('Checking flagged delta checks for request ID:', requestId);
      
      // Fetch flagged delta checks for this laboratory request
      const response = await api.get('/management-report/delta-checks', {
        params: {
          status: 'flagged',
          per_page: 1000,
          page: 1
        }
      });

      allFlaggedChecks = response.data?.data || [];
      
      // Update cache
      staticCache.allFlaggedDeltaChecks.data = allFlaggedChecks;
      staticCache.allFlaggedDeltaChecks.timestamp = now;
    }

    console.log('Total flagged delta checks retrieved:', allFlaggedChecks.length);
    if (allFlaggedChecks.length > 0) {
      console.log('Sample delta check structure:', allFlaggedChecks[0]);
    }
    
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
      
      return matches;
    });

    // Check if any results have action_on_violation = 'flag_and_stop' which should set status to Hold
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
  } catch (error) {
    if (error.response?.status === 429) {
      console.warn('Rate limited while checking flagged delta checks');
      // If we have cached data, use it but don't re-filter yet as it's already done or we can't fetch new
      if (staticCache.allFlaggedDeltaChecks.data.length > 0) {
        // We still need to filter for the current request
        const requestId = props.requestDetails.id;
        flaggedDeltaChecks.value = staticCache.allFlaggedDeltaChecks.data.filter(dc => {
          if (!dc || !dc.test_result) return false;
          const lrid = dc.test_result.laboratory_request_id || dc.test_result.laboratory_request?.id;
          return Number(lrid) === Number(requestId);
        });
        hasHoldStatus.value = flaggedDeltaChecks.value.length > 0;
        return;
      }
    }
    console.error('Error checking flagged delta checks:', error);
    flaggedDeltaChecks.value = [];
    hasHoldStatus.value = false;
  }
};

const checkAllDeltaChecks = async () => {
  if (!props.requestDetails?.id) {
    allDeltaChecks.value = [];
    return;
  }

  try {
    const requestId = props.requestDetails.id;
    const now = Date.now();
    const cached = staticCache.deltaChecks[requestId];
    if (cached && (now - cached.timestamp < CACHE_TTL)) {
      allDeltaChecks.value = cached.data;
      return;
    }

    const params = {
      per_page: 1000,
      page: 1
    };
    if (props.requestDetails?.patient?.id) {
      params.patient_id = props.requestDetails.patient.id;
    }

    const response = await api.get('/management-report/delta-checks', { params });
    const allChecks = response.data?.data || [];
    const filtered = allChecks.filter(dc => {
      if (!dc || !dc.test_result) {
        return false;
      }
      const lrid = dc.test_result.laboratory_request_id || 
                   dc.test_result.laboratory_request?.id ||
                   dc.test_result.laboratoryRequest?.id ||
                   null;
      return Number(lrid) === Number(requestId);
    });

    allDeltaChecks.value = filtered;
    staticCache.deltaChecks[requestId] = {
      data: filtered,
      timestamp: now
    };
  } catch (error) {
    if (error.response?.status === 429) {
      const requestId = props.requestDetails.id;
      const cached = staticCache.deltaChecks[requestId];
      if (cached && cached.data) {
        allDeltaChecks.value = cached.data;
        return;
      }
    }
    console.error('Error fetching delta checks:', error);
    allDeltaChecks.value = [];
  }
};

// Accept/Reject/Re-run modal states
const showAcceptModal = ref(false);
const showRejectModal = ref(false);
// const showRerunModal = ref(false);
const showViolationsModal = ref(false);
const activeViolationTab = ref('summary');
const violationsAcknowledged = ref(false);
const violationActionTaken = ref('');
const violationActionOther = ref('');
const violationActionDetails = ref('');
const pendingPostAckAction = ref(null);
const hasUnsavedChanges = ref(false);

// Logged-in user (for reviewer auto-fill)
// const authStore = useAuthStore?.() || null;
// const currentUser = authStore?.user || null;

const nowTimestamp = () => {
  try { return format(new Date(), 'yyyy-MM-dd HH:mm:ss'); } catch { return new Date().toISOString(); }
};

const acceptForm = ref({
  reviewer_id: currentUser.value?.id || null,
  reviewed_at: nowTimestamp(),
  investigation_checklist: [],
  accept_reason: '',
  rationale: '',
  preanalytical_status: ''
});
const rejectForm = ref({
  reviewer_id: currentUser.value?.id || null,
  reviewed_at: nowTimestamp(),
  scope: 'request',
  test_ids: [],
  investigation_checklist: [],
  root_cause: '',
  action_taken: '',
  rejection_comment: '',
  original_value_canceled: true
});
const acceptFormRef = ref(null);
const rejectFormRef = ref(null);
const rejectOrigin = ref('delta');

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
const violationActionOptions = [
  { label: 'Repeat test performed', value: 'REPEAT_TEST' },
  { label: 'Result verified against previous history', value: 'VERIFY_HISTORY' },
  { label: 'Result verified with instrument QC/calibration', value: 'VERIFY_QC' },
  { label: 'Result discussed with clinician/ward', value: 'DISCUSS_CLINICIAN' },
  { label: 'Result reviewed with pathologist', value: 'REVIEW_PATHOLOGIST' },
  { label: 'Specimen recollected and re-tested', value: 'RECOLLECT_SPECIMEN' },
  { label: 'Result accepted as is; no further action', value: 'ACCEPT_AS_IS' },
  { label: 'Other (specify)', value: 'OTHER' }
];
const acceptingResult = ref(false);
const rejectingResult = ref(false);
// const rerunningResult = ref(false);

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
  scope: [{ required: true, message: 'Scope is required', trigger: 'change' }],
  test_ids: [{
    validator: (rule, value, callback) => {
      if (rejectForm.value.scope === 'tests') {
        if (Array.isArray(value) && value.length > 0) return callback();
        return callback(new Error('Select at least one test'));
      }
      if (rejectForm.value.scope === 'results') {
        if (selectedTests.value.length > 0) return callback();
        return callback(new Error('Select at least one parameter in the table'));
      }
      return callback();
    },
    trigger: 'change'
  }],
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
  const cacheKey = testCategoryId || 'all';
  const now = Date.now();
  
  if (staticCache.equipment[cacheKey] && (now - staticCache.equipment[cacheKey].timestamp < CACHE_TTL)) {
    console.log(`Using cached equipment for ${cacheKey}`);
    equipment.value = staticCache.equipment[cacheKey].data;
    return;
  }

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
    const data = response.data.data || response.data || [];
    equipment.value = data;
    
    // Update cache
    staticCache.equipment[cacheKey] = {
      data: data,
      timestamp: now
    };
    
    console.log('Loaded equipment:', equipment.value);
  } catch (error) {
    if (error.response?.status === 429) {
      console.warn(`Rate limited while fetching equipment for ${cacheKey}`);
      if (staticCache.equipment[cacheKey]) {
        equipment.value = staticCache.equipment[cacheKey].data;
        return;
      }
    }
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
  const now = Date.now();
  if (staticCache.roles[roleType] && (now - staticCache.roles[roleType].timestamp < CACHE_TTL)) {
    console.log(`Using cached users for role: ${roleType}`);
    return staticCache.roles[roleType].data;
  }

  try {
    const response = await api.get(`/users/role/${roleType}`);
    const data = response.data;
    
    // Update cache
    staticCache.roles[roleType] = {
      data: data,
      timestamp: now
    };
    
    return data;
  } catch (error) {
    if (error.response?.status === 429) {
      console.warn(`Rate limited while fetching users for role: ${roleType}`);
      if (staticCache.roles[roleType]) {
        return staticCache.roles[roleType].data;
      }
    }
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
    // Build fallback mapping from requestDetails.results if testParameters is missing
    let fallbackResultsMapByTest = null;
    if (!resultsDataSource && (!props.requestDetails.testParameters || Object.keys(props.requestDetails.testParameters).length === 0)) {
      const resultsArr = props.requestDetails.results || [];
      if (Array.isArray(resultsArr) && resultsArr.length > 0) {
        fallbackResultsMapByTest = {};
        resultsArr.forEach(r => {
          const tId = r.test_id;
          const pId = r.test_parameter?.id ?? r.test_parameter_id;
          if (!tId || !pId) return;
          if (!fallbackResultsMapByTest[tId]) fallbackResultsMapByTest[tId] = [];
          fallbackResultsMapByTest[tId].push({
            id: pId,
            result_id: r.id,
            resultValue: r.result_value,
            unit: r.unit,
            instrument: r.equipment_id,
            resultComment: r.test_parameter?.result_comment ?? '',
            flag: r.flag ?? null,
            conventional: '',
            conventional_unit: '',
            conversion_factor: null,
            version: r.version || 'R0',
            status: r.status || 'pending',
            created_at: r.created_at,
            updated_at: r.updated_at
          });
        });
      }
    }
    const sourceForSavedResults = resultsDataSource || props.requestDetails.testParameters || fallbackResultsMapByTest || [];
    console.log(`Log 5: testId: ${testId} - sourceForSavedResults chosen:`, resultsDataSource ? "resultsDataSource (passed in)" : "props.requestDetails.testParameters (fallback)");
    console.log(`Log 5.1: testId: ${testId} - content of sourceForSavedResults:`, JSON.parse(JSON.stringify(sourceForSavedResults)));

    const savedResults = sourceForSavedResults?.[testId] || [];
    console.log(`Log 6: testId: ${testId} - savedResults extracted for this testId:`, JSON.parse(JSON.stringify(savedResults)));
    
    const savedResultsMap = {};
    if (Array.isArray(savedResults)) {
      savedResults.forEach(result => {
        const paramId = (result && (
          result.test_parameter_id ??
          result?.test_parameter?.id ??
          result.parameterId ??
          result.id
        ));
        if (paramId) {
          if (!savedResultsMap[paramId]) savedResultsMap[paramId] = [];
          savedResultsMap[paramId].push(result);
        }
      });
    }
    
    // Version selection helpers (basic mapping path)
    const rankVersionBasic = (v) => {
      if (!v) return 0;
      const m = String(v).match(/R(\d+)/i);
      return m ? parseInt(m[1], 10) : 0;
    };
    const pickLatestBasic = (list = []) => {
      if (!Array.isArray(list) || list.length === 0) return null;
      return list.slice().sort((a,b) => {
        const ra = rankVersionBasic(a.version);
        const rb = rankVersionBasic(b.version);
        if (rb !== ra) return rb - ra;
        const ca = new Date(a.created_at || 0).getTime();
        const cb = new Date(b.created_at || 0).getTime();
        return cb - ca;
      })[0];
    };
    const pickPreviousBasic = (list = [], current) => {
      if (!Array.isArray(list)) return null;
      const filtered = list.filter(r => !current || r !== current);
      if (filtered.length === 0) return null;
      return filtered.slice().sort((a,b) => {
        const ra = rankVersionBasic(a.version);
        const rb = rankVersionBasic(b.version);
        if (rb !== ra) return rb - ra;
        const ca = new Date(a.created_at || 0).getTime();
        const cb = new Date(b.created_at || 0).getTime();
        return cb - ca;
      })[0];
    };

    testParameters.value[testId] = response.data
      .filter(param => param && param.id)
      .map(param => {
        const savedList = savedResultsMap[param.id] || [];
        const current = pickLatestBasic(savedList);
        const previous = pickPreviousBasic(savedList, current);
        const paramObj = {
          id: param.id,
          name: param.name || param.test_name || '',
          test_name: param.test_name || param.name || `Parameter ${param.id}`,
          // When in re-run mode and only R0 exists, keep the input blank
          resultValue: current
            ? (isForRerun.value && String(current.version || 'R0').toUpperCase() === 'R0'
                ? ''
                : (current.resultValue ?? current.result_value ?? ''))
            : '',
          // Preserve the raw current saved value regardless of UI blanking
          currentResultRaw: current ? (current.resultValue ?? current.result_value ?? '') : '',
          unit: param.unit || '',
          instrument: current ? current.instrument || null : null,
          resultComment: current ? (current.resultComment || '') : '',
          flag: current ? current.flag || null : null,
          referenceRanges: Array.isArray(param.reference_ranges) ? param.reference_ranges : [],
          conventional: current ? current.conventional || '' : '',
          conventional_unit: param.conventional_unit || '',
          conversion_factor: param.conversion_factor || null,
          result_id: current && current.result_id ? current.result_id : (current ? current.id : null),
          version: current ? (current.version || 'R0') : 'R0',
          status: current ? (current.status || 'pending') : 'pending',
          previousVersionStatus: previous ? `${previous.version || 'R0'} - ${previous.status || 'pending'}` : null,
          // For Original Result: if previous exists use it; otherwise when only R0 exists, use current R0
          previousValueRaw: previous
            ? (previous.resultValue ?? previous.result_value ?? '')
            : ((isForRerun.value && current && String(current.version || 'R0').toUpperCase() === 'R0')
                ? (current.resultValue ?? current.result_value ?? '')
                : '')
        };
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
      // Build fallback mapping from requestDetails.results if testParameters is missing
      let fallbackResultsMapByTest = null;
      if (!resultsDataSource && (!props.requestDetails.testParameters || Object.keys(props.requestDetails.testParameters).length === 0)) {
        const resultsArr = props.requestDetails.results || [];
        if (Array.isArray(resultsArr) && resultsArr.length > 0) {
          fallbackResultsMapByTest = {};
          resultsArr.forEach(r => {
            const tId = r.test_id;
            const pId = r.test_parameter?.id ?? r.test_parameter_id;
            if (!tId || !pId) return;
            if (!fallbackResultsMapByTest[tId]) fallbackResultsMapByTest[tId] = [];
            fallbackResultsMapByTest[tId].push({
              id: pId,
              result_id: r.id,
              resultValue: r.result_value,
              unit: r.unit,
              instrument: r.equipment_id,
              resultComment: r.test_parameter?.result_comment ?? '',
              flag: r.flag ?? null,
              conventional: '',
              conventional_unit: '',
              conversion_factor: null,
              version: r.version || 'R0',
              status: r.status || 'pending',
              created_at: r.created_at,
              updated_at: r.updated_at
            });
          });
        }
      }
      const sourceForSavedResults = resultsDataSource || props.requestDetails.testParameters || fallbackResultsMapByTest;
      const savedResults = sourceForSavedResults?.[testId] || [];
      
      const savedResultsMap = {};
      if (Array.isArray(savedResults)) {
        savedResults.forEach(result => {
          const paramId = (result && (
            result.test_parameter_id ??
            result?.test_parameter?.id ??
            result.parameterId ??
            result.id
          ));
          if (paramId) {
            if (!savedResultsMap[paramId]) savedResultsMap[paramId] = [];
            savedResultsMap[paramId].push(result);
          }
        });
      }
      
      const basicParamMap = {};
      basicParameters.forEach(param => {
        if (param && param.id) {
          basicParamMap[param.id] = param;
        }
      });
      
      // Utility to rank versions
      const rankVersion = (v) => {
        if (!v) return 0;
        const m = String(v).match(/R(\d+)/i);
        return m ? parseInt(m[1], 10) : 0;
      };
      const pickLatest = (list = []) => {
        if (!Array.isArray(list) || list.length === 0) return null;
        return list.slice().sort((a,b) => {
          const ra = rankVersion(a.version);
          const rb = rankVersion(b.version);
          if (rb !== ra) return rb - ra;
          const ca = new Date(a.created_at || 0).getTime();
          const cb = new Date(b.created_at || 0).getTime();
          return cb - ca;
        })[0];
      };
      const pickPrevious = (list = [], current) => {
        if (!Array.isArray(list)) return null;
        const filtered = list.filter(r => !current || r !== current);
        if (filtered.length === 0) return null;
        return filtered.slice().sort((a,b) => {
          const ra = rankVersion(a.version);
          const rb = rankVersion(b.version);
          if (rb !== ra) return rb - ra;
          const ca = new Date(a.created_at || 0).getTime();
          const cb = new Date(b.created_at || 0).getTime();
          return cb - ca;
        })[0];
      };

      testParameters.value[testId] = response.data
        .filter(param => param && param.id)
        .map(param => {
          const savedList = savedResultsMap[param.id] || [];
          const current = pickLatest(savedList);
          const previous = pickPrevious(savedList, current);
          const basicParam = basicParamMap[param.id];
          // ... (rest of the mapping logic, ensure it uses savedResult correctly for values)
          const paramObj = {
            id: param.id,
            name: param.name || (basicParam ? basicParam.name : '') || (basicParam ? basicParam.test_name : '') || '',
            test_name: param.test_name || param.name || (basicParam ? basicParam.test_name : '') || (basicParam ? basicParam.name : '') || `Parameter ${param.id}`,
            // When in re-run mode and only R0 exists, keep the input blank
            resultValue: current
              ? (isForRerun.value && String(current.version || 'R0').toUpperCase() === 'R0'
                  ? ''
                  : (current.resultValue ?? current.result_value ?? ''))
              : '',
            // Preserve the raw current saved value regardless of UI blanking
            currentResultRaw: current ? (current.resultValue ?? current.result_value ?? '') : '',
            unit: param.unit || (basicParam ? basicParam.unit : '') || '',
            instrument: current ? current.instrument || null : null,
            resultComment: current ? current.resultComment || '' : '',
            flag: current ? current.flag || null : null,
            result_id: current ? (current.result_id || current.id) : null,
            referenceRanges: Array.isArray(param.reference_ranges) ? param.reference_ranges : 
                           (basicParam && Array.isArray(basicParam.reference_ranges) ? 
                            basicParam.reference_ranges : []),
            conventional: current ? current.conventional || '' : '',
            conventional_unit: param.conventional_unit || (basicParam ? basicParam.conventional_unit : '') || '',
            conversion_factor: param.conversion_factor || (basicParam ? basicParam.conversion_factor : null),
            result_id: current && current.result_id ? current.result_id : (current ? current.id : null),
            version: current ? (current.version || 'R0') : 'R0',
            status: current ? (current.status || 'pending') : 'pending',
            previousVersionStatus: previous ? `${previous.version || 'R0'} - ${previous.status || 'pending'}` : null,
            // For Original Result: if previous exists use it; otherwise when only R0 exists, use current R0
            previousValueRaw: previous
              ? (previous.resultValue ?? previous.result_value ?? '')
              : ((isForRerun.value && current && String(current.version || 'R0').toUpperCase() === 'R0')
                  ? (current.resultValue ?? current.result_value ?? '')
                  : '')
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
  let workingTests = tests;
  if (!workingTests || workingTests.length === 0) {
      const resultsArr = props.requestDetails.results || [];
      if (Array.isArray(resultsArr) && resultsArr.length > 0) {
        const uniqueTestIds = Array.from(new Set(resultsArr.map(r => r.test_id).filter(Boolean)));
        workingTests = uniqueTestIds.map(id => ({ id }));
        console.log("Derived tests from results for fetchAllTestParameters:", workingTests);
      } else {
        console.log("No tests provided to fetchAllTestParameters");
        return Promise.resolve();
      }
  }
  console.log("Starting fetchAllTestParameters for tests:", workingTests, "using resultsDataSource:", resultsDataSource);
  parametersLoading.value = true; // Corrected
  testParameters.value = {};
  
  try {
    const promises = workingTests.map(test => fetchTestParameters(test.id, resultsDataSource));
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
      
      // Determine if any test is tagged For Re-run
      isForRerun.value = Array.isArray(newDetails.tests) && newDetails.tests.some(t => (t.pivot?.item_status === 'For Re-run'));
      // Initialize result fields from tests
      resultFields.value = newDetails.tests.map(test => ({
        id: test.id,
        pivot_id: test.pivot?.id,
        pivot_status: test.pivot?.item_status,
        testName: test.name,
        unit: test.unit || '',
        referenceRange: test.reference_range || '',
        conventional_unit: test.conventional_unit || '',
        conventional_reference_range: test.conventional_reference_range || '',
        initial_resultValue: test.initial_resultValue || '',
        initial_instrument: test.initial_instrument || null,
        initial_resultComment: test.initial_resultComment || '',
        version: test.version,
        status: test.status,
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
        // Fetch audit trail
        fetchAuditTrail();
        // For initial load/prop update, pass newDetails.testParameters as the resultsDataSource
        await fetchAllTestParameters(newDetails.tests, overrideResultsDataSource.value || newDetails.testParameters);
        // Load active formulas (if any) and run an initial recalculation pass
        try {
          await fetchFormulas();
          recalculateFormulasForSourceParameter();
        } catch (e) {
          console.warn('Initial formula fetch/recalc failed:', e);
        }
        
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
    if (!flag) return '';
    const f = flag.toString();
    if (f === 'L' || f === 'H') return 'flag-critical';
    if (f === 'l' || f === 'h') return 'flag-abnormal';
    if (f === 'ERR') return 'flag-error';
    return '';
};

// --- Helper for individual flag character styling ---
const getFlagCharClass = (flag) => {
    if (!flag) return '';
    const f = flag.toString();
    if (f === 'L') return 'flag-low-critical';
    if (f === 'H') return 'flag-high-critical';
    if (f === 'l') return 'flag-low-abnormal';
    if (f === 'h') return 'flag-high-abnormal';
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
        message: `${flagText} (Ref: ${refRangeStr})`,
        parameter_id: param.id || param.parameterId || param.test_parameter_id || null,
        test_result_id: param.result_id ?? null
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

const hasAnyViolation = computed(() => totalViolations.value > 0);

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
const handleAcknowledgeViolations = async () => {
  if (!violationsAcknowledged.value) {
    ElMessage.warning('Please acknowledge that you have reviewed all violations');
    return;
  }

  if (!violationActionTaken.value) {
    ElMessage.warning('Please select the main action taken');
    return;
  }

  if (violationActionTaken.value === 'OTHER' && !violationActionOther.value.trim()) {
    ElMessage.warning('Please specify the action taken');
    return;
  }

  if (!violationActionDetails.value || violationActionDetails.value.trim().length < 15) {
    ElMessage.warning('Please provide more details for the action taken');
    return;
  }

  if (props.requestDetails?.id) {
    try {
      const payload = {
        action_taken_code: violationActionTaken.value,
        action_taken_label: (violationActionOptions.find(o => o.value === violationActionTaken.value)?.label) || null,
        action_taken_other: violationActionTaken.value === 'OTHER' ? violationActionOther.value : null,
        action_comment: violationActionDetails.value
      };
      if (cdsAlerts.value && cdsAlerts.value.length > 0) {
        payload.cds_alerts = cdsAlerts.value;
      }
      if (flaggedDeltaChecks.value && flaggedDeltaChecks.value.length > 0 && !deltaAcknowledgeData.value) {
        payload.delta_checks = flaggedDeltaChecks.value.map(dc => ({
          test_result_id: dc.test_result?.id,
          test_parameter_id: dc.test_parameter?.id,
          message: `Delta check violation for ${dc.test_parameter?.name || 'Parameter'}: ${dc.previous_value} -> ${dc.current_value}`
        }));
      }
      if (criticalValues.value && criticalValues.value.length > 0) {
        payload.critical_values = criticalValues.value.map(cv => ({
          parameter_id: cv.parameter_id,
          message: cv.message
        }));
      }

      await api.post(`/laboratory-requests/${props.requestDetails.id}/acknowledge-violations`, payload);
      // Refresh audit trail after acknowledgment
      await fetchAuditTrail();
    } catch (error) {
      ElMessage.error(error.response?.data?.message || 'Failed to record acknowledgment');
      return;
    }
  }

  showViolationsModal.value = false;
  violationsAcknowledged.value = false;
  violationActionTaken.value = '';
  violationActionOther.value = '';
  violationActionDetails.value = '';
  ElMessage.success('Violations acknowledged. You may now proceed with review actions.');

  if (pendingPostAckAction.value === 'print') {
    pendingPostAckAction.value = null;
    handlePrintRelease();
  } else if (pendingPostAckAction.value === 'validate') {
    pendingPostAckAction.value = null;
    handleValidateRelease();
  } else if (pendingPostAckAction.value === 'reject') {
    pendingPostAckAction.value = null;
    showRejectModal.value = true;
    rejectForm.value.reviewer_id = currentUser.value?.id || rejectForm.value.reviewer_id;
    rejectForm.value.reviewed_at = nowTimestamp();
  }
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

// Computed property to identify test IDs that triggered CDS rules
const triggeredCdsTestIds = computed(() => {
  return new Set(cdsAlerts.value.map(alert => alert.trigger_test_id).filter(id => !!id));
});

// Row class name for the result table
const getModalRowClassName = ({ row }) => {
  const classes = [];
  if (row.isParameter) {
    if (triggeredCdsTestIds.value.has(row.testId)) {
      classes.push('row-cds-triggered');
    }
    
    // Add status-based classes
    const flag = row.flag?.toString();
    if (flag === 'L' || flag === 'H') {
      classes.push('row-critical');
    } else if (flag === 'l' || flag === 'h') {
      classes.push('row-abnormal');
    }
  }
  return classes.join(' ');
};

// Logic to determine if a value should be bolded based on flag
const isResultValueBold = (flag) => {
  if (!flag) return false;
  return ['L', 'H'].includes(flag.toString());
};

// --- Computed property for table display with improved reference range handling ---
const formattedTestResults = computed(() => {
  if (!resultFields.value || resultFields.value.length === 0) return [];

  const currentPatientAge = calculatePatientAge(props.requestDetails?.patient?.date_of_birth);
  const currentPatientGender = props.requestDetails?.patient?.gender || 'Any';
  
  // Array to hold the formatted results for the table
  const results = [];
  const deltaChecksByResultId = new Map();
  allDeltaChecks.value.forEach(dc => {
    const resultId = dc?.test_result?.id || dc?.test_result_id;
    if (resultId !== undefined && resultId !== null) {
      deltaChecksByResultId.set(Number(resultId), dc);
    }
  });
  
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
        const fallbackPrevious = param.previousValueRaw;
        if (fallbackPrevious !== undefined && fallbackPrevious !== null && String(fallbackPrevious).trim() !== '') {
          previousValue = fallbackPrevious;
        }
        const flaggedDeltaCheck = flaggedDeltaChecks.value.find(dc => {
          const dcParamId = dc.test_parameter?.id || 
                           dc.test_result?.test_parameter_id ||
                           dc.test_parameter_id;
          return dcParamId === param.id || dcParamId === param.parameterId;
        });
        const resultDeltaCheck = param.result_id ? deltaChecksByResultId.get(Number(param.result_id)) : null;
        const deltaCheck = flaggedDeltaCheck || resultDeltaCheck;
        
        if (deltaCheck) {
          if (deltaCheck.previous_value !== undefined && deltaCheck.previous_value !== null && String(deltaCheck.previous_value).trim() !== '') {
            previousValue = deltaCheck.previous_value;
          }
          deltaPercent = deltaCheck.percentage_difference;
        }
        // Do not alter delta check values; they remain as originally triggered
        
        // Add parameter to results array
        results.push({
          id: `test-${test.id}-param-${param.id}`,
          isParameter: true,
          isTestHeader: false,
          testId: test.id,
          parameterId: param.id,
          parameterName: getParameterName(param, test), // Use the helper function
          result_id: param.result_id || null,
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
          deltaPercent: deltaPercent,
          // Original Result must show the prior version within this request (R0).
          // Use the saved previous value from this request only.
          originalValue: isForRerun.value
            ? (
                (param.previousValueRaw !== undefined && param.previousValueRaw !== null && String(param.previousValueRaw).trim() !== '')
                  ? param.previousValueRaw
                  : (
                      String(param.version || 'R0').toUpperCase() === 'R0'
                        ? (param.currentResultRaw ?? '')
                        : ''
                    )
              )
            : undefined,
          // In re-run context, surface the current version as R1 in the UI.
          versionStatus: isForRerun.value ? 'R1' : `${param.version || 'R0'}`,
          // expose auto-calculation flags for UI
          is_auto_calculated: !!param.is_auto_calculated,
          auto_formula_id: param.auto_formula_id || null
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
          id: `test-${test.id}`,
          isParameter: false,
          parameterName: test.testName || test.name || `Test ${test.id}`,
          test_id: test.id,
          remarks: localRemarks.value,  // Add remarks at the test level
          comment: localComments.value  // Add comment at the test level
        };
        
        resultEntry.result_value = test.initial_resultValue.trim();
        resultEntry.unit = test.unit ? test.unit.trim() : '';
        resultEntry.equipment_id = test.initial_instrument;
        resultEntry.result_comment = test.initial_resultComment || '';
        resultEntry.versionStatus = `${test.version || 'R0'}`;
        
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

        hasUnsavedChanges.value = false;

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
                    version: item.version || 'R0',
                    status: item.status || 'pending'
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
  if (isFullyRejected.value) {
    ElMessage.warning('This request is fully rejected and read-only.');
    return;
  }
  if (hasUnacknowledgedAlerts.value) {
    pendingPostAckAction.value = 'validate';
    showViolationsModal.value = true;
    activeViolationTab.value = 'summary';
    return;
  }

  // Check button state first
  if (disableValidateButton.value) {
      // Get specific reason for disabling
      const allTestParams = Object.values(testParameters.value).flat();
      const allSaved = allTestParams.every(param => param.result_id !== null);
      const allReleased = props.requestDetails.tests.every(test => 
        test.pivot && test.pivot.item_status === 'Result Printed'
      );
      
      if (allReleased) {
          ElMessage.warning("Tests are already in Released status and cannot be validated again.");
      } else {
          ElMessage.warning("Please save all results before validating and releasing");
      }
      return;
  }

  // Check CDS rules before validation
  await checkCdsRules();
  
  if (hasUnacknowledgedAlerts.value) {
    pendingPostAckAction.value = 'validate';
    showViolationsModal.value = true;
    activeViolationTab.value = 'summary';
    return;
  }

  // Block validation if there are blocking CDS rules, unless acknowledged
  const blockingRules = cdsAlerts.value.filter(r => r.is_blocking);
  if (blockingRules.length > 0 && !cdsAcknowledgeData.value) {
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
  if (isFullyRejected.value) {
    ElMessage.warning('This request is fully rejected and read-only.');
    return;
  }
  if (!hasHoldStatus.value) {
    ElMessage.warning('No flagged results to accept');
    return;
  }
  showAcceptModal.value = true;
  acceptForm.value.reviewer_id = currentUser.value?.id || acceptForm.value.reviewer_id;
  acceptForm.value.reviewed_at = nowTimestamp();
};

// Handle Reject Result (for flagged delta checks)
const handleRejectResult = () => {
  if (isFullyRejected.value) {
    ElMessage.warning('This request is fully rejected and read-only.');
    return;
  }
  rejectOrigin.value = 'general';
  
  // If user has selected specific parameters, default to 'results' scope
  if (selectedTests.value.length > 0) {
    rejectForm.value.scope = 'results';
  } else {
    rejectForm.value.scope = 'request';
  }
  
  rejectForm.value.test_ids = [];
  showRejectModal.value = true;
  rejectForm.value.reviewer_id = currentUser.value?.id || rejectForm.value.reviewer_id;
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

const openManualRerun = () => {
  if (isFullyRejected.value) {
    ElMessage.warning('This request is fully rejected and read-only.');
    return;
  }
  if (selectedTests.value.length === 0) {
    ElMessage.warning('Please select at least one result to re-run.');
    return;
  }
  showRerunModal.value = true;
};

const openManualReject = () => {
  if (isFullyRejected.value) {
    ElMessage.warning('This request is fully rejected and read-only.');
    return;
  }
  if (selectedTests.value.length === 0) {
    ElMessage.warning('Please select at least one result to reject.');
    return;
  }
  rejectOrigin.value = 'manual';
  rejectForm.value.scope = 'results';
  rejectForm.value.test_ids = getSelectedTestIds();
  showRejectModal.value = true;
  rejectForm.value.reviewer_id = currentUser.value?.id || rejectForm.value.reviewer_id;
  rejectForm.value.reviewed_at = nowTimestamp();
};

watch(currentUser, (user) => {
  if (!user?.id) {
    return;
  }
  acceptForm.value.reviewer_id = user.id;
  rejectForm.value.reviewer_id = user.id;
});

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
      accept_reason_label: (acceptReasonOptions.find(opt => opt.value === acceptForm.value.accept_reason)?.label) || null,
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
    
    await fetchAuditTrail();

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
    const selectedResultsPayload = selectedTests.value
      .filter(item => item.result_id)
      .map(item => ({
        test_result_id: item.result_id,
        test_parameter_id: item.parameterId || null
      }));

    const actionTakenLabel = actionTakenOptions.find(opt => opt.value === rejectForm.value.action_taken)?.label || null;

    const response = await api.post(`/laboratory-requests/${props.requestDetails.id}/reject`, {
      scope: rejectForm.value.scope,
      test_ids: rejectForm.value.scope === 'tests' ? rejectForm.value.test_ids : [],
      selected_results: selectedResultsPayload,
      reviewer_id: rejectForm.value.reviewer_id,
      reviewed_at: rejectForm.value.reviewed_at,
      investigation_checklist: rejectForm.value.investigation_checklist,
      root_cause: rejectForm.value.root_cause,
      action_taken: rejectForm.value.action_taken,
      action_taken_label: actionTakenLabel,
      rejection_comment: rejectForm.value.rejection_comment,
      original_value_canceled: !!rejectForm.value.original_value_canceled
    });

    ElMessage.success(response.data?.message || 'Rejection recorded successfully');
    
    // Close modal and refresh data
    showRejectModal.value = false;
    rejectForm.value.reason = '';
    rejectForm.value.scope = 'request';
    rejectForm.value.test_ids = [];
    
    // Refresh flagged checks
    await checkFlaggedDeltaChecks();
    
    // Refresh test statuses
    refreshTestStatuses();

    await fetchAuditTrail();
    
    // Emit event to parent
    emit('save', props.requestDetails.id);
    emit('validate', props.requestDetails.id);
    
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
// const submitRerun = async () => {
//   rerunningResult.value = true;
//   try {
//     // Call backend API to mark request as "For Re-run"
//     const response = await api.post(`/laboratory-requests/${props.requestDetails.id}/delta-check/rerun`, {
//       flagged_results: flaggedDeltaChecks.value.map(dc => ({
//         test_result_id: dc.test_result?.id,
//         test_parameter_id: dc.test_parameter?.id,
//         original_value: dc.current_value,
//         delta_check_id: dc.id
//       }))
//     });

//     ElMessage.success('Re-run initiated. Please perform the re-run on the instrument.');
    
//     // Close modal
//     showRerunModal.value = false;
    
//     // Update status to "For Re-run" or "Pending Repeat Analysis"
//     await api.put(`/laboratory-requests/${props.requestDetails.id}/status`, {
//       status: 'For Re-run',
//       test_category_id: testCategoryId.value
//     });
    
//     // Refresh flagged checks
//     await checkFlaggedDeltaChecks();
    
//     // Refresh test statuses
//     refreshTestStatuses();
    
//     // Emit event to parent
//     emit('save', props.requestDetails.id);
    
//     // Optionally close the modal
//     // handleClose();
//   } catch (error) {
//     console.error('Error initiating re-run:', error);
//     ElMessage.error(`Failed to initiate re-run: ${error.response?.data?.message || error.message}`);
//   } finally {
//     rerunningResult.value = false;
//   }
// };

const handlePrintRelease = async () => {
  if (isFullyRejected.value) {
    ElMessage.warning('This request is fully rejected and read-only.');
    return;
  }
  if (hasUnacknowledgedAlerts.value) {
    pendingPostAckAction.value = 'print';
    showViolationsModal.value = true;
    activeViolationTab.value = 'summary';
    return;
  }

  // Check button state first
  if (disablePrintButton.value) {
      ElMessage.warning("Please save all results before printing and releasing");
      return;
  }

  if (!props.requestDetails?.id) {
      ElMessage.error("Cannot print results: Request ID is missing.");
      return;
  }

  // Open template print dialog with all necessary data
  showTemplatePrintDialog.value = true;
};

const handlePrintComplete = (data) => {
  // Update test statuses to "Result Printed" in real-time
  console.log('Print completed, updating test statuses:', data);
  
  if (props.requestDetails?.tests && Array.isArray(props.requestDetails.tests)) {
    props.requestDetails.tests.forEach(test => {
      if (test.pivot) {
        test.pivot.item_status = 'Result Printed';
      }
    });
    console.log('Test statuses updated to Result Released');
    
    // Force Vue reactivity
    props.requestDetails.tests = [...props.requestDetails.tests];
  }

  if (props.requestDetails?.id) {
    emit('print', props.requestDetails.id, data);
  }
  
  // Close the print dialog
  showTemplatePrintDialog.value = false;
};

const handlePreview = () => {
  console.log('Preview triggered');
  ElMessage.info('Preview function not yet implemented.');
};

const updateResultValue = async (row, value) => {
  if (!row.isParameter || !row.testId || !row.parameterId) return;
  
  // Find the parameter in testParameters and update it directly
  const testParams = testParameters.value[row.testId];
  if (!testParams) return;
  
  const paramToUpdate = testParams.find(p => p.id === row.parameterId);
  if (!paramToUpdate) return;
  
  hasUnsavedChanges.value = true;

  paramToUpdate.resultValue = row.resultValue;
  const hasNewValue = paramToUpdate.resultValue && paramToUpdate.resultValue.trim() !== '';
  
  if (hasNewValue) {
    paramToUpdate.result_id = null; // Mark as needing to be saved
  }
  // If value is cleared, also mark as unsaved
  if (!hasNewValue) {
    paramToUpdate.result_id = null;
  }
  // Force Vue reactivity by replacing the array reference
  testParameters.value[row.testId] = [...testParameters.value[row.testId]];
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
      patient_id: props.requestDetails?.patient?.id,
      equipment_id: paramToUpdate.instrument || row.instrument || null
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
  try {
    recalculateFormulasForSourceParameter();
  } catch (e) {
    console.error('Recalculation after value change failed:', e);
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
  
  paramToUpdate.resultComment = updatedComment;
  row.resultComment = updatedComment; // Also update row for consistency
  
  hasUnsavedChanges.value = true;

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

  if (paramToUpdate.resultComment !== row.resultComment) {
    paramToUpdate.resultComment = row.resultComment;
    console.log(`Synced row.resultComment to paramToUpdate.resultComment for param ${paramToUpdate.id}`);
  }

  hasUnsavedChanges.value = true;

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
  
  const newTestParamsArray = [...testParams];
  testParameters.value[row.testId] = newTestParamsArray;
  
  forceButtonUpdate();
  
  console.log(`updateParameterComment: Parameter ${row.parameterId} (testId: ${row.testId}) resultComment is now: "${paramToUpdate.resultComment}". Marked unsaved: ${markedUnsaved}. Current result_id: ${paramToUpdate.result_id}`);
};

const overrideAutoCalculated = (row) => {
  if (!row || !row.testId || !row.parameterId) return;
  const params = testParameters.value[row.testId] || [];
  const idx = params.findIndex(p => p.id === row.parameterId);
  if (idx === -1) return;
  const param = params[idx];
  param.is_auto_calculated = false;
  param.auto_formula_id = null;
  param.result_id = null;
  hasUnsavedChanges.value = true;
  testParameters.value[row.testId] = [...testParameters.value[row.testId]];
  // After unsetting, focus the input by emitting nextTick (template will render enabled input)
  nextTick(() => {
    try {
      const selector = `input[value=\"${row.resultValue}\"]`;
      const el = document.querySelector(selector);
      if (el) el.focus();
    } catch (e) {
      // ignore focus failures
    }
  });
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
          const allReleased = props.requestDetails.tests.every(test => 
            test.pivot && test.pivot.item_status === 'Result Printed'
          );
          
          if (allReleased) {
            ElMessage.warning('Tests are already in Released status and cannot be validated again');
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
    await checkAllDeltaChecks();
    // Check CDS rules when modal opens
    if (props.requestDetails?.id) {
      await checkCdsRules();
      fetchAuditTrail();
    }
  } else {
    // Clear alerts when modal closes
    cdsAlerts.value = [];
    flaggedDeltaChecks.value = [];
    allDeltaChecks.value = [];
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
    await checkAllDeltaChecks();
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
    const ageInfo = calculatePatientAge(props.requestDetails?.patient?.date_of_birth);
    const patientAge = ageInfo.years;
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

// New computed properties for enhanced patient info
const formattedBirthday = computed(() => {
  const dob = props.requestDetails.patient?.date_of_birth;
  if (!dob) return 'N/A';
  try {
    return format(parseISO(dob), 'MMMM dd, yyyy');
  } catch (e) {
    return dob;
  }
});

const formattedAgeGender = computed(() => {
  const age = calculateAge(props.requestDetails.patient?.date_of_birth);
  const gender = props.requestDetails.patient?.gender || 'N/A';
  return `${age} ${suffixUnit.value || ''} / ${gender}`;
});

const formattedContact = computed(() => {
  return props.requestDetails.patient?.contact_no || props.requestDetails.patient?.contact_number || props.requestDetails.patient?.contactNo || 'N/A';
});

const formattedAddress = computed(() => {
  return props.requestDetails.patient?.address || 'N/A';
});

const formattedEmail = computed(() => {
  return props.requestDetails.patient?.email || props.requestDetails.patient?.email_address || 'N/A';
});

const requestingDoctor = computed(() => {
  // Try doctor object first (from CategoryResults row mapping)
  if (props.requestDetails.doctor?.name) return props.requestDetails.doctor.name;
  // Fallback to physician if that's how it's named in the requestDetails object
  if (props.requestDetails.physician?.name) return props.requestDetails.physician.name;
  return props.requestDetails.requesting_doctor || 'N/A';
});

const patientTypeName = computed(() => {
  return props.requestDetails.patient_type?.name || props.requestDetails.patient_type || 'N/A';
});

const orNumber = computed(() => {
  return props.requestDetails.or_no || props.requestDetails.orr_no || props.requestDetails.orNo || 'N/A';
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
  if (isFullyRejected.value) return true;
  return !hasUnsavedChanges.value;
});

const disablePrintButton = computed(() => {
  if (isFullyRejected.value) return true;
  // Do not block Print based on unacknowledged Delta/Critical/CDS rules.
  // Those are enforced via the acknowledgment modal when the user clicks Print.
  if (qcHoldBannerMessage.value) return true; // QC lockout remains a hard stop
  const allParams = Object.values(testParameters.value).flat();
  if (!props.requestDetails?.tests?.length || allParams.length === 0) return true;
  const paramsWithValues = allParams.filter(param => param.resultValue && param.resultValue.trim() !== '');
  if (paramsWithValues.length === 0) return true;
  if (hasUnsavedChanges.value) return true;
  return false;
});

const disableValidateButton = computed(() => {
  if (isFullyRejected.value) return true;
  // Do not block Validate based on unacknowledged Delta/Critical/CDS rules.
  // Those are enforced via the acknowledgment modal when the user clicks Validate.
  if (qcHoldBannerMessage.value) return true; // QC lockout remains a hard stop
  const allParams = Object.values(testParameters.value).flat();
  if (!props.requestDetails?.tests?.length || allParams.length === 0) return true;
  const paramsWithValues = allParams.filter(param => param.resultValue && param.resultValue.trim() !== '');
  if (paramsWithValues.length === 0) return true;
  const testsAreReleased = props.requestDetails.tests.every(
    test => test.pivot && test.pivot.item_status === 'Result Printed'
  );
  if (testsAreReleased) return true;
  if (hasUnsavedChanges.value) return true;
  return false;
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
.hematology-modal :deep(.el-dialog__body) {
  padding: 0;
  background-color: #134d86 !important;
}

.brand { display: flex; align-items: center; gap: 12px; font-weight: 700; letter-spacing: .3px; }
.brand-logo { font-size: 20px; }
.brand-name { font-size: 13px; color: #1e293b; }
.patient-nav { display: flex; gap: 6px; align-items: center; }
.global-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.global-actions :deep(.el-button) {
  border-radius: 8px;
  font-weight: 700;
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 0.3px;
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

.global-actions :deep(.el-button--warning.is-plain) {
  background: rgba(245, 158, 11, 0.15) !important;
  color: var(--color-warning) !important; /* Amber-400 */
  border-color: rgba(245, 158, 11, 0.3) !important;
}

.global-actions :deep(.el-button-group) {
  display: flex;
  gap: 8px;
  background: transparent;
  box-shadow: none;
}

.critical-left { display: flex; gap: var(--spacing-md); align-items: left; }
.severity-icon { font-size: var(--font-size-xl, 18px); line-height: 1; }
.severity-icon.danger { color: var(--color-danger); }
.severity-icon.warn { color: var(--color-warning); }
.critical-text .primary { font-weight: 700; color: var(--color-danger); align-items: left; display: flex; }
.critical-text .detail { color: var(--color-text-secondary); font-size: var(--font-size-xs, 12px); }
.critical-text .detail .reason-link { color: var(--color-primary); text-decoration: underline; cursor: pointer; }
.critical-text .detail .reason-link:hover { color: var(--color-primary-dark); }
.ai-reco { color: var(--color-text-secondary); font-size: var(--font-size-xs, 12px); margin-top: 2px; }
.critical-actions { display: flex; gap: var(--spacing-sm); }
.delta-cell { white-space: nowrap; }
.delta-cell .prev-val { font-weight: var(--font-weight-semibold, 600); color: var(--color-text-primary); }
.delta-cell .delta-val { color: var(--color-text-secondary); }

.org-val { font-weight: var(--font-weight-semibold, 600); color: var(--color-text-primary); }


.content-container {
  min-height: calc(100vh - var(--modal-header-height));
  display: flex;
  flex-direction: column;
  gap: 16px;
  overflow-y: auto;
  margin-top: 40px !important; /* Base margin for header only */
  animation: slideDown 0.5s ease-out;
}

.content-container.with-banner {
  margin-top: 90px !important; /* Increased margin when banner is visible */
  animation: slideDown 0.5s ease-out;
}

.content-container-inner {
  display: flex;
  flex-direction: column;
  gap: 16px;
}



.patient-main-info-wrapper {
  display: flex;
  align-items: center;
  gap: 20px;
}

.patient-avatar-wrapper {
  position: relative;
}

.avatar-premium {
  width: 56px;
  height: 56px;
  background: var(--primary-gradient);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-primary-dark);
  font-weight: 900;
  font-size: 20px;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.patient-gender-icon {
  position: absolute;
  bottom: -4px;
  right: -4px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  border: 2px solid white;
}

.patient-gender-icon.male { color: #2563eb; }
.patient-gender-icon.female { color: #db2777; }

.patient-name-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 6px;
}

.patient-name-premium {
  font-size: 18px;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.priority-tag {
  border-radius: 6px;
  font-weight: 700;
  text-transform: uppercase;
  font-size: 12px;
}

.priority-tag-inline {
  width: fit-content;
  border-radius: 4px;
  font-size: 12px;
  height: 20px;
  line-height: 18px;
}

.section-label-premium {
  font-size: 12px;
  font-weight: 800;
  color: #64748b;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.mt-15 { margin-top: 15px; }
.mt-5 { margin-top: 5px; }

.patient-meta-container-premium {
  display: flex;
  flex-direction: column;
  gap: 12px; /* Vertical gap between rows */
}

.patient-meta-row-premium {
  display: flex;
  gap: 28px; /* Horizontal gap between pills in a row */
  flex-wrap: wrap;
}

.meta-pill {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #1e293b; /* Unified value color */
  font-weight: 700;
}

.meta-pill b {
  color: #94a3b8; /* Unified label color */
  font-weight: 800;
  text-transform: uppercase;
}

.meta-pill .el-icon {
  font-size: 13px;
  color: #94a3b8;
}

.detail-row-premium {
  display: flex;
  align-items: center;
  gap: 8px;
}

.d-label {
  font-size: 12px;
  font-weight: 700;
  color: #94a3b8;
  letter-spacing: 0.5px;
  width: 100px;
}

.d-value {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
}

.d-value-name {
  font-size: 16px;
  font-weight: 800;
  color: #0f172a;
  text-transform: uppercase;
}



.mt-10 {
  margin-top: 10px;
}

.wrap-meta {
  flex-wrap: wrap;
  row-gap: 8px;
}

.context-item-v2 {
  display: flex;
  align-items: center;
  gap: 4px;
}

.c-label {
  text-align: right;
  font-size: 12px;
  font-weight: 800;
  color: #94a3b8; /* Unified label color */
  letter-spacing: 0.8px;
  text-transform: uppercase;
  width: 130px;
}

.c-value {
  font-size: 13px;
  font-weight: 700;
  color: #1e293b; /* Unified value color */
  display: flex;
  align-items: center;
  gap: 6px;
}

.c-value .el-icon {
  font-size: 13px;
  color: #94a3b8;
}

.qc-badge-v2 {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 2px 10px;
  border-radius: 99px; /* Pill style */
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
}

.qc-badge-v2.pass {
  background: #f0fdf4;
  color: #166534;
  border: 1px solid #dcfce7;
}

.qc-badge-v2.fail {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fee2e2;
}
/* Premium Design System Tokens */
:root {
  --modal-header-height: 70px;
  --glass-bg: rgba(255, 255, 255, 0.95);
  --glass-border: rgba(255, 255, 255, 0.3);
  --primary-gradient: linear-gradient(135deg, var(--color-primary) 0%, #1d4ed8 100%);
  --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
  --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  --shadow-premium: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
  --border-premium: 1px solid #e2e8f0;
}

/* 0. Header Styling - Dark Theme to match Audit Trail */
.result-header {
  display: flex; 
  align-items: center; 
  justify-content: space-between; 
  gap: var(--spacing-lg); 
  padding: var(--spacing-sm) var(--spacing-xl); 
  margin-bottom: var(--spacing-lg); 
  background: var(--color-dark); 
  color: var(--color-text-light, #fff); 
  position: fixed; 
  top: 0; 
  left: 0; 
  right: 0; 
  z-index: 1000; 
  width: 100%;
}

.brand-name {
  font-size: 16px;
  font-weight: 700;
  color: #f8fafc; /* White text for dark theme */
  line-height: 1.2;
}

.brand-tagline {
  font-size: 12px;
  color: #94a3b8; /* Slate 400 */
  font-weight: 500;
  letter-spacing: 0.5px;
}

.brand-logo-wrapper {
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
}

.close-btn {
  color: #f8fafc !important;
  background: rgba(255, 255, 255, 0.1) !important;
  border: none !important;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #dc2626 !important;
  transform: rotate(90deg);
}

.header-divider {
  width: 1px;
  height: 24px;
  background: rgba(255, 255, 255, 0.2);
}

/* 0b. Status Banners */
.status-banner {
  top: var(--modal-header-height, 55px);
  left: 0;
  right: 0;
  padding: 8px 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: fixed;
  z-index: 2000;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from { transform: translateY(-100%); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.banner-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.banner-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.banner-title {
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.banner-detail {
  font-size: 12px;
  margin-top: 2px;
}

.audit-link {
  font-weight: 600;
  text-decoration: underline;
  color: inherit;
  opacity: 0.9;
}

.audit-link:hover {
  opacity: 1;
}

.banner-actions {
  display: flex;
  gap: 8px;
}

/* Banner Themes - Improved Contrast */
.banner-rejected {
  background-color: #f8fafc;
  color: #1e293b;
}
.banner-rejected .banner-icon { background: #e2e8f0; color: #475569; }

.banner-critical {
  background-color: #fff4f4;
  color: #991b1b;
}
.banner-critical .banner-icon { background: #fee2e2; color: #dc2626; }
.banner-critical .audit-link { color: #991b1b; font-weight: 700; }

.banner-qc {
  background-color: #fffbeb;
  color: #92400e;
}
.banner-qc .banner-icon { background: #fef3c7; color: #d97706; }
.banner-qc .audit-link { color: #92400e; font-weight: 700; }

.banner-success {
  background-color: #f0fdf4;
  color: #166534;
}
.banner-success .banner-icon { background: #dcfce7; color: #16a34a; }
.banner-success .audit-link { color: #166534; font-weight: 700; }

/* 3. Results Table Premium */
.patient-card-premium-unified {
  display: grid;
  grid-template-columns: 1.4fr auto 1fr; /* Slightly adjusted ratio */
  gap: 40px;
  background: #f1f5f9;
  padding: 30px; /* Increased padding */
  border-radius: 16px;
  border: 1px solid #d6e0eb; /* Softer border */
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04), 0 8px 10px -6px rgba(0, 0, 0, 0.04); /* More subtle shadow */
  margin-bottom: 24px;
  align-items: center; /* Center vertically */
}

.patient-info-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.request-details-section {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.premium-vertical-divider {
  width: 1px;
  height: 100px; /* Specific height */
  background: #f1f5f9;
}

.premium-horizontal-divider {
  width: 100%;
  height: 1px; /* Specific height */
  background: #f1f5f9;
}

.clinical-context-v2-grid-unified {
  display: grid;
  grid-template-columns: repeat(2, 1fr); /* 2 columns for cleaner look */
  gap: 12px 32px;
}

.table-actions-toolbar {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 24px;
  padding: 0 24px 12px;
  margin-bottom: 4px;
}

.results-table-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
  border: 1px solid #e2e8f0;
}

.table-header-premium {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 20px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.table-header-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.table-icon {
  font-size: 18px;
  color: var(--color-primary);
}

.table-title {
  font-size: 13px;
  font-weight: 800;
  color: #1e293b;
  letter-spacing: 0.5px;
}

.table-header-legend {
  display: flex;
  gap: 16px;
}

.legend-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  padding: 4px 10px;
  background: white;
  border-radius: 20px;
  border: 1px solid #e2e8f0;
}

.legend-pill span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.legend-pill.normal span { background: #10b981; }
.legend-pill.abnormal span { background: #f59e0b; }
.legend-pill.critical span { background: #ef4444; }

.premium-table :deep(.el-table__header-wrapper) th {
  background: #f8fafc !important;
  color: #475569;
  font-weight: 700;
  font-size: 12px;
  text-transform: uppercase;
  padding: 14px 0;
  letter-spacing: 0.5px;
}

.premium-table :deep(.el-table__row) {
  transition: background 0.2s;
}

.version-badge {
  font-size: 12px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 4px;
  background: var(--color-primary-light);
  color: var(--color-text-primary);
}

.version-badge.r1, .version-badge.r2 {
  background: #fffbeb;
  color: #b45309;
}

.parameter-cell-premium {
  display: flex;
  align-items: center;
  gap: 8px;
}

.p-name {
  font-weight: 700;
  color: #334155;
  font-size: 13px;
}

.calc-indicator {
  background: #e0f2fe;
  color: #0369a1;
  font-size: 12px;
  font-weight: 800;
  padding: 1px 5px;
  border-radius: 4px;
  text-transform: uppercase;
}

/* Flag styling refinements */
.flag-indicator {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 4px;
  font-weight: 900;
  font-size: 12px;
  /* Removed text-transform: uppercase to support l vs L */
  letter-spacing: 0.2px;
}

/* Abnormal Low/High - Clear Orange */
.flag-low-abnormal, .flag-high-abnormal { 
  background: #fdf9e8; 
  color: #f59609; 
  border: 1px solid #fcd34d;
}

/* Critical Low/High - High Contrast Red */
.flag-low-critical, .flag-high-critical { 
  background: var(--color-danger-light); 
  color: var(--color-danger-dark); 
  border: 1px solid var(--color-danger);
  font-weight: 900;
  box-shadow: 0 0 10px rgba(220, 38, 38, 0.2);
}

.flag-indicator.flag-error {
  background: #f1f5f9;
  color: #64748b;
  border: 1px solid #e2e8f0;
}

.result-input-container {
  display: flex;
  justify-content: center;
}

.premium-input-center :deep(.el-input__inner) {
  text-align: center;
  font-weight: 800;
  color: #1e293b;
  font-size: 13px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Status-based input coloring */
.premium-input-center.flag-abnormal :deep(.el-input__inner) {
  color: #b45309;
  background-color: #fffbeb !important;
  border-color: #fcd34d !important;
}

.premium-input-center.flag-critical :deep(.el-input__inner) {
  color: #7f1d1d;
  background-color: #fef2f2 !important;
  border-color: #fca5a5 !important;
  font-weight: 900;
}

.premium-input-center.is-bold :deep(.el-input__inner) {
  font-weight: 900;
}

.override-btn {
  padding: 0 !important;
  margin-right: 4px;
}

.previous-value-group {
  display: flex;
  justify-content: center;
  gap: 2px;
}

.prev-val {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
}

.delta-badge {
  font-size: 12px;
  font-weight: 700;
  padding: 1px 4px;
  border-radius: 4px;
  background: #f1f5f9;
  color: #64748b;
}

.delta-badge.delta-warning {
  background: var(--color-danger-light);
  color: var(--color-danger-dark);
  font-weight: 800;
}

.delta-badge.delta-normal {
  background: var(--color-success-light);
  color: var(--color-success-dark);
  font-weight: 800;
}


/* Row highlighting refinements */
.premium-table :deep(.row-abnormal) {
  background-color: rgba(255, 251, 235, 0.3) !important;
}

.premium-table :deep(.row-critical) {
  background-color: rgba(254, 226, 226, 0.5) !important;
}

.premium-table :deep(.row-critical:hover td) {
  background-color: rgba(254, 226, 226, 0.8) !important;
}

/* Status icons in parameter name */
.status-icon-critical {
  color: #dc2626;
  font-size: 13px;
}

.status-icon-abnormal {
  color: #d97706;
  font-size: 13px;
}

.unit-text { font-size: 12px; font-weight: 600; color: #64748b; }
.unit-text-secondary { font-size: 12px; color: #94a3b8; font-weight: 500; }

.ref-range-pill {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  background: #f8fafc;
  padding: 2px 8px;
  border-radius: 4px;
}

.conventional-value {
  font-weight: 600;
  color: #64748b;
  font-size: 12px;
}

.premium-select :deep(.el-input__inner) {
  font-weight: 600;
  font-size: 12px;
}

.comment-input-minimal :deep(.el-input__inner) {
  font-size: 12px;
  border-color: transparent !important;
  background: transparent !important;
}

.comment-input-minimal :deep(.el-input__inner:focus) {
  border-color: #cbd5e1 !important;
  background: white !important;
}

/* Secondary Toolbar alignment */
.secondary-details {
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: center;
  gap: 16px;
  border-radius: 10px;
  background: #f1f5f9;
  padding: 8px 20px;
}

.secondary-toolbar {
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: center;
  gap: 16px;
  border-radius: 10px;
  background: #f1f5f9;
  padding: 8px 20px;
}

/* 4. Bottom Organization V2 */
.bottom-organization {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  
}

.note-box-premium {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
  border: 1px solid #e2e8f0;
}

.box-header-premium {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.box-header-premium span {
  font-size: 13px;
  font-weight: 800;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.box-header-premium .el-icon {
  color: var(--color-primary);
  font-size: 16px;
}

.signatories-card-premium {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
  border: 1px solid #e2e8f0;
}

.sig-grid-v2 {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.sig-field-v2 {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.sig-field-v2 label {
  font-size: 12px;
  font-weight: 800;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}


/* Tab-based Violations Modal Styles */
.violations-tabs { margin-top: var(--spacing-sm); }
.ai-suggestion-banner { 
  background: var(--color-primary-light); 
  border-left: 4px solid var(--color-primary); 
  padding: var(--spacing-sm) var(--spacing-md); 
  margin-bottom: var(--spacing-md); 
  border-radius: var(--border-radius-sm, 4px); 
  display: flex; 
  align-items: center; 
  gap: var(--spacing-sm); 
}
.ai-icon { font-size: var(--font-size-lg, 16px); }
.ai-text { font-size: var(--font-size-xs, 12px); font-weight: var(--font-weight-semibold, 600); color: var(--color-primary-dark); }
.hold-summary { background: var(--color-bg-secondary); padding: var(--spacing-sm) var(--spacing-md); border-radius: var(--border-radius-sm, 4px); margin-bottom: var(--spacing-md); }
.summary-text { margin: 0 0 var(--spacing-xs) 0; font-size: var(--font-size-sm, 13px); color: var(--color-text-primary); line-height: 1.4; }
.summary-text strong { color: var(--color-danger); font-weight: var(--font-weight-bold, 700); }
.instruction-text { margin: 0; font-size: var(--font-size-xs, 12px); color: var(--color-text-secondary); line-height: 1.4; }
.summary-title {
  font-size: var(--font-size-sm, 13px);
  font-weight: var(--font-weight-semibold, 600);
  margin-bottom: var(--spacing-sm);
  color: var(--color-text-primary);
}
.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: var(--spacing-sm);
}
.summary-card {
  border-radius: var(--border-radius-sm, 4px);
  border: 1px solid var(--color-border-light);
  padding: var(--spacing-sm);
  background: var(--color-bg-soft, #f8f9fb);
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.summary-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: var(--font-size-xs, 12px);
  font-weight: var(--font-weight-semibold, 600);
}
.summary-card-body {
  font-size: var(--font-size-xs, 12px);
  color: var(--color-text-secondary);
}
.summary-card-actions {
  margin-top: 2px;
}
/* Violations Modal Header Styles */
.violations-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-md);
  padding: var(--spacing-xs) var(--spacing-md);
  background: var(--color-dark);
  color: var(--color-text-light);
  margin: -20px -20px 0 -20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: var(--border-radius-sm, 4px) var(--border-radius-sm, 4px) 0 0;
}

.sig-field-v2 label {
  font-size: 12px;
  font-weight: 800;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Premium Modal Header Styles */
.modal-header-premium {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: white;
  border-bottom: 1px solid #f1f5f9;
}

.header-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-icon-wrapper {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.header-icon-wrapper.success { background: #f0fdf4; color: #16a34a; }
.header-icon-wrapper.danger { background: #fef2f2; color: #dc2626; }
.header-icon-wrapper.warning { background: #fffbeb; color: #d97706; }

.header-text {
  display: flex;
  flex-direction: column;
}

.header-title {
  font-size: 13px;
  font-weight: 800;
  color: #1e293b;
  line-height: 1.2;
}

.header-subtitle {
  font-size: 12px;
  color: #94a3b8;
  font-weight: 600;
  letter-spacing: 0.3px;
}

.header-close-btn {
  font-size: 18px;
  color: #94a3b8 !important;
  transition: all 0.2s;
}

.header-close-btn:hover {
  color: #ef4444 !important;
  transform: rotate(90deg);
}

.premium-textarea :deep(.el-textarea__inner) {
  border-radius: 12px;
  padding: 12px;
  font-size: 13px;
  border-color: #e2e8f0;
  transition: all 0.2s;
}

.premium-textarea :deep(.el-textarea__inner:focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.violations-header-actions {
  display: flex;
  gap: var(--spacing-sm);
}

/* Enhanced Reject Modal Styles */
.reject-modal-enhanced :deep(.el-dialog__body) {
  background-color: var(--color-bg-secondary);
}

.btn-confirm-rejection {
  font-weight: 700;
  letter-spacing: 0.5px;
  padding: 12px 24px;
}

.warning-icon {
  font-size: 24px;
  color: var(--color-danger);
}

.warning-text {
  font-size: 12px;
  color: #742a2a;
  line-height: 1.4;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-lg);
}

.form-section-card {
  background: white;
  border: 1px solid var(--color-border-light);
  border-radius: var(--border-radius-lg);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.form-section-card.full-width {
  grid-column: span 2;
}

.section-title-bar {
  background-color: var(--color-bg-soft);
  padding: var(--spacing-sm) var(--spacing-md);
  border-bottom: 1px solid var(--color-border-light);
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  font-size: 12px;
  font-weight: bold;
  color: var(--color-text-primary);
}

.section-body {
  padding: var(--spacing-md);
  flex: 1;
}

.grid-2-col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-xl);
}

.scope-radio-group {
  width: 100%;
  display: flex;
}

.scope-radio-group :deep(.el-radio-button) {
  flex: 1;
}

.scope-radio-group :deep(.el-radio-button__inner) {
  width: 100%;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.scope-option {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.scope-option .el-icon {
  font-size: 18px;
}

.test-selection-item {
  margin-top: var(--spacing-md);
}

.test-checkbox-list {
  max-height: 120px;
  overflow-y: auto;
  padding: var(--spacing-xs);
  border: 1px solid var(--color-border-light);
  border-radius: var(--border-radius-sm);
}

.checklist-grid {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.checklist-item {
  margin-right: 0 !important;
  display: block;
}

.rationale-input :deep(.el-textarea__inner) {
  background-color: #fffaf0;
  border-color: #fbd38d;
}

.cancel-lis-check {
  margin-top: var(--spacing-md);
  padding: var(--spacing-sm);
  background-color: #f7fafc;
  border-radius: var(--border-radius-sm);
}

.footer-rejection {
  background-color: #fef2f2 !important;
}

.critical-icon {
  font-size: 28px;
  color: var(--color-danger);
}

.impact-text span {
  font-weight: bold;
  color: var(--color-danger);
  font-size: 13px;
}

.impact-text p {
  margin: 0;
  font-size: 12px;
  color: var(--color-text-secondary);
}

.btn-confirm-rejection {
  padding-left: 30px;
  padding-right: 30px;
  font-weight: bold;
}

/* Violations Summary Modal Styles */
.violations-summary { padding: var(--spacing-sm) 0; }
.violation-section { margin-bottom: var(--spacing-2xl); }
.violation-section:last-child { margin-bottom: 0; }
.alert-group-card {
  border-radius: var(--border-radius-sm, 4px);
  border: 1px solid var(--color-border-light);
  padding: var(--spacing-sm);
  background: var(--color-bg-soft, #f8f9fb);
}
.section-header { 
  font-size: var(--font-size-md, 14px); 
  font-weight: var(--font-weight-bold, 700); 
  color: var(--color-text-primary); 
  margin-bottom: var(--spacing-sm); 
  padding-bottom: var(--spacing-xs); 
  border-bottom: 1px solid var(--color-border-light); 
}
.delta-accepted-card {
  background: var(--color-success-light, #e6f9ed);
  border-color: var(--color-success, #157f3d);
}
.delta-accepted-header {
  margin-left: var(--spacing-sm);
  font-size: var(--font-size-xs, 12px);
  font-weight: var(--font-weight-semibold, 600);
  color: var(--color-success, #157f3d);
}
.summary-accepted {
  color: var(--color-success, #157f3d);
  font-weight: var(--font-weight-bold, 700);
}
.summary-accepted-tag {
  margin-left: var(--spacing-2xs, 4px);
  font-size: var(--font-size-xs, 12px);
  font-weight: var(--font-weight-semibold, 600);
}
.violation-item { display: flex; align-items: flex-start; gap: var(--spacing-sm); margin-bottom: var(--spacing-xs); padding: 2px 0; }
.violation-icon { font-size: var(--font-size-lg, 16px); line-height: 1.2; flex-shrink: 0; margin-top: 2px; }
.violation-icon.danger { color: var(--color-danger); }
.violation-icon.warn { color: var(--color-warning); }
.violation-text { font-size: var(--font-size-xs, 12px); color: var(--color-text-primary); line-height: 1.35; flex: 1; }
.section-purpose { font-size: var(--font-size-xs, 12px); color: var(--color-text-secondary); font-style: italic; margin-top: var(--spacing-sm); padding-left: var(--spacing-2xl); }
.no-violations { text-align: center; padding: var(--spacing-2xl); color: var(--color-text-secondary); font-size: var(--font-size-md, 14px); }

.acknowledgment-section {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.action-taken-fields {
  display: flex;
  flex-direction: column;
}


/* Tab-based Violations Modal Styles */
.violations-tabs { margin-top: var(--spacing-sm); }
.ai-suggestion-banner { 
  background: var(--color-primary-light); 
  border-left: 4px solid var(--color-primary); 
  padding: var(--spacing-sm) var(--spacing-md); 
  margin-bottom: var(--spacing-md); 
  border-radius: var(--border-radius-sm, 4px); 
  display: flex; 
  align-items: center; 
  gap: var(--spacing-sm); 
}
.ai-icon { font-size: var(--font-size-lg, 16px); }
.ai-text { font-size: var(--font-size-xs, 12px); font-weight: var(--font-weight-semibold, 600); color: var(--color-primary-dark); }
.hold-summary { background: var(--color-bg-secondary); padding: var(--spacing-sm) var(--spacing-md); border-radius: var(--border-radius-sm, 4px); margin-bottom: var(--spacing-md); }
.summary-text { margin: 0 0 var(--spacing-xs) 0; font-size: var(--font-size-sm, 13px); color: var(--color-text-primary); line-height: 1.4; }
.summary-text strong { color: var(--color-danger); font-weight: var(--font-weight-bold, 700); }
.instruction-text { margin: 0; font-size: var(--font-size-xs, 12px); color: var(--color-text-secondary); line-height: 1.4; }
.summary-title {
  font-size: var(--font-size-sm, 13px);
  font-weight: var(--font-weight-semibold, 600);
  margin-bottom: var(--spacing-sm);
  color: var(--color-text-primary);
}
.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: var(--spacing-sm);
}
.summary-card {
  border-radius: var(--border-radius-sm, 4px);
  border: 1px solid var(--color-border-light);
  padding: var(--spacing-sm);
  background: var(--color-bg-soft, #f8f9fb);
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.summary-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: var(--font-size-xs, 12px);
  font-weight: var(--font-weight-semibold, 600);
}
.summary-card-body {
  font-size: var(--font-size-xs, 12px);
  color: var(--color-text-secondary);
}
.summary-card-actions {
  margin-top: 2px;
}
/* Violations Modal Header Styles */
.violations-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-md);
  padding: var(--spacing-xs) var(--spacing-md);
  background: var(--color-dark);
  color: var(--color-text-light);
  margin: -20px -20px 0 -20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: var(--border-radius-sm, 4px) var(--border-radius-sm, 4px) 0 0;
}

.violations-header-brand {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  font-weight: var(--font-weight-semibold, 600);
  letter-spacing: 0.3px;
}

.violations-header-icon {
  background-image: url('@/assets/icons/exclamation-mark.png');
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center;
  font-size: var(--font-size-xl, 18px);
}

.violations-header-title {
  font-size: var(--font-size-xs, 12px);
  opacity: 0.9;
  text-transform: uppercase;
  white-space: nowrap;
}

.violations-header-actions {
  display: flex;
  gap: var(--spacing-sm);
}

.violations-header-actions .el-button {
  color: var(--color-text-light);
}

.violations-header-actions .el-button--default {
  background-color: var(--color-warning);
  border-color: var(--color-warning);
}

.violations-header-actions .el-button--default:hover {
  background-color: var(--color-warning);
  filter: brightness(0.9);
  border-color: var(--color-warning);
}

.violations-header-actions .el-button--danger {
  background-color: var(--color-danger);
  border-color: var(--color-danger);
}

.violations-header-actions .el-button--danger:hover {
  background-color: var(--color-danger-dark);
  border-color: var(--color-danger-dark);
}

.violations-modal :deep(.el-dialog__body) { 
  max-height: 64vh; 
  overflow-y: auto; 
  padding: var(--spacing-md) var(--spacing-lg); 
}

.violations-modal :deep(.el-tabs__content) { 
  max-height: calc(64vh - 100px); 
  overflow-y: auto; 
}
.violations-list { display: flex; flex-direction: column; gap: var(--spacing-xs); max-height: calc(70vh - 200px); overflow-y: auto; }
.violation-list-item { display: flex; gap: var(--spacing-sm); padding: var(--spacing-xs) var(--spacing-sm); border-radius: var(--border-radius-sm, 4px); border-left: 3px solid; }
.violation-list-item.critical { background: var(--color-danger-light); border-left-color: var(--color-danger); }
.violation-list-item.warning { background: var(--color-warning-light); border-left-color: var(--color-warning); }
.list-icon { font-size: var(--font-size-lg, 16px); flex-shrink: 0; margin-top: 2px; }
.list-icon.danger { color: var(--color-danger); }
.list-icon.warn { color: var(--color-warning); }
.list-content { flex: 1; min-width: 0; }
.list-title { font-size: var(--font-size-sm, 13px); font-weight: var(--font-weight-bold, 700); color: var(--color-text-primary); margin-bottom: 2px; line-height: 1.3; }
.list-detail { font-size: var(--font-size-xs, 12px); color: var(--color-text-primary); margin-bottom: 2px; line-height: 1.3; }
.list-note {
  font-size: var(--font-size-xs, 12px);
  color: var(--color-text-secondary);
  font-style: italic;
  line-height: 1.3;
}
.violations-footer { display: flex; justify-content: space-between; align-items: center; gap: var(--spacing-lg); padding-top: var(--spacing-md); border-top: 1px solid var(--color-border-light); }
.footer-actions { flex: 1; }
.acknowledgment-section { flex: 1; }

/* Audit Trail Styles */
.audit-timeline {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  margin-top: var(--spacing-sm);
}

.audit-item {
  display: flex;
  gap: var(--spacing-lg);
  padding: var(--spacing-sm);
  border-bottom: 1px solid var(--color-border-light);
}

.audit-time {
  font-size: var(--font-size-xs, 12px);
  color: var(--color-text-secondary);
  width: 140px;
  flex-shrink: 0;
}

.audit-content {
  flex: 1;
}

.audit-type {
  font-size: 12px;
  font-weight: bold;
  padding: 2px 6px;
  border-radius: 4px;
  margin-right: 8px;
  text-transform: uppercase;
}

.audit-type.cds { background: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; }
.audit-type.qc { background: #fff7e6; color: #fa8c16; border: 1px solid #ffd591; }
.audit-type.delta_check { background: #fff1f0; color: #f5222d; border: 1px solid #ffa39e; }
.audit-type.final_release { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }

.audit-decision {
  font-size: 12px;
  font-weight: bold;
  padding: 2px 6px;
  border-radius: 4px;
}

.audit-decision.pass { background: #f6ffed; color: #52c41a; }
.audit-decision.block { background: #fff1f0; color: #f5222d; }
.audit-decision.acknowledged { background: #e6f7ff; color: #1890ff; }
.audit-decision.override { background: #fff7e6; color: #fa8c16; }

.audit-message {
  font-size: var(--font-size-sm, 13px);
  margin-top: 4px;
  color: var(--color-text-primary);
}

.audit-action {
  font-size: var(--font-size-xs, 12px);
  margin-top: 2px;
  color: var(--color-text-primary);
}

.audit-comment {
  font-size: var(--font-size-xs, 12px);
  margin-top: 2px;
  color: var(--color-text-secondary);
}

.audit-label {
  font-weight: var(--font-weight-semibold, 600);
  margin-right: 4px;
}

.audit-user {
  font-size: var(--font-size-xs, 12px);
  color: var(--color-text-secondary);
  margin-top: 4px;
  font-style: italic;
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-2xl);
  color: var(--color-text-secondary);
  gap: var(--spacing-sm);
}

/* Enhanced Reject Modal Styles */
.reject-modal-enhanced :deep(.el-dialog__body) {
  background-color: var(--color-bg-secondary);
}

.modal-header-danger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-md);
  padding: var(--spacing-md) var(--spacing-xl);
  background: linear-gradient(135deg, var(--color-danger-dark, #c0392b) 0%, var(--color-danger, #e74c3c) 100%);
  color: white;
  margin: -20px -20px 0 -20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: var(--border-radius-sm, 4px) var(--border-radius-sm, 4px) 0 0;
}

.title-group {
  display: flex;
  flex-direction: column;
}

.brand-subtitle {
  font-size: 12px;
  opacity: 0.8;
  letter-spacing: 0.5px;
}

.selected-parameters-list {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-xs);
  padding: var(--spacing-sm);
  background: white;
  border: 1px solid var(--color-border-light);
  border-radius: var(--border-radius-sm);
  min-height: 60px;
}

.parameter-tag {
  font-weight: var(--font-weight-semibold);
}

.no-selection-warning {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  color: var(--color-danger);
  font-size: var(--font-size-xs);
  width: 100%;
  justify-content: center;
}

.selected-for-rerun-box {
  background: var(--color-warning-light);
  border: 1px solid var(--color-warning);
  padding: var(--spacing-sm);
  border-radius: var(--border-radius-sm);
  margin-bottom: var(--spacing-md);
}

.box-label {
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-bold);
  color: var(--color-warning-dark);
  margin-bottom: var(--spacing-xs);
}

.parameter-tags {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-xs);
}

.param-tag {
  font-weight: var(--font-weight-semibold);
}

.rejection-warning-banner {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  padding: var(--spacing-md);
  background-color: #fff5f5;
  border: 1px solid #feb2b2;
  border-radius: var(--border-radius-md);
  margin-bottom: var(--spacing-lg);
}

.warning-icon {
  font-size: 24px;
  color: var(--color-danger);
}

.warning-text {
  font-size: 12px;
  color: #742a2a;
  line-height: 1.4;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-lg);
}

.investigation-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-xl);
}

.checklist-vertical {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  background: var(--color-bg-soft);
  padding: var(--spacing-md);
  border-radius: var(--border-radius-md);
  border: 1px solid var(--color-border-light);
}

.checklist-item-full {
  display: block;
  margin-bottom: var(--spacing-sm);
}

.rationale-input-enhanced :deep(.el-textarea__inner) {
  background-color: #fffaf0;
  border-color: #fbd38d;
  font-family: inherit;
  line-height: 1.6;
}

.cancel-lis-footer {
  margin-top: var(--spacing-md);
  display: flex;
  justify-content: center;
}

.lis-checkbox {
  background: white;
  padding: 8px 24px !important;
  border-color: var(--color-primary-light) !important;
}

.lis-icon {
  margin-right: 8px;
  color: var(--color-primary);
}

.form-section-card.investigation-card,
.form-section-card.documentation-card {
  margin-top: var(--spacing-lg);
}

.section-body {
  padding: var(--spacing-lg);
}

.rejection-warning-banner {
  background: #fff5f5;
  border: 1px solid #feb2b2;
  border-radius: var(--border-radius-md);
  padding: var(--spacing-md) var(--spacing-lg);
  margin-bottom: var(--spacing-lg);
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
}

.footer-rejection {
  background: #fff5f5;
}

.impact-text span {
  color: var(--color-danger-dark);
  font-weight: 700;
  text-transform: uppercase;
  font-size: 12px;
}

.impact-text p {
  margin: 0;
  font-size: 12px;
  color: #742a2a;
}

.btn-confirm-rejection {
  font-weight: 700;
  letter-spacing: 0.5px;
  padding: 12px 24px;
}

.warning-icon {
  font-size: 24px;
  color: var(--color-danger);
}

.warning-text {
  font-size: 12px;
  color: #742a2a;
  line-height: 1.4;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-lg);
}

.form-section-card {
  background: white;
  border: 1px solid var(--color-border-light);
  border-radius: var(--border-radius-lg);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.form-section-card.full-width {
  grid-column: span 2;
}

.section-title-bar {
  background-color: var(--color-bg-soft);
  padding: var(--spacing-sm) var(--spacing-md);
  border-bottom: 1px solid var(--color-border-light);
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  font-size: 12px;
  font-weight: bold;
  color: var(--color-text-primary);
}

.section-body {
  padding: var(--spacing-md);
  flex: 1;
}

.grid-2-col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-xl);
}

.scope-radio-group {
  width: 100%;
  display: flex;
}

.scope-radio-group :deep(.el-radio-button) {
  flex: 1;
}

.scope-radio-group :deep(.el-radio-button__inner) {
  width: 100%;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.scope-option {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.scope-option .el-icon {
  font-size: 18px;
}

.test-selection-item {
  margin-top: var(--spacing-md);
}

.test-checkbox-list {
  max-height: 120px;
  overflow-y: auto;
  padding: var(--spacing-xs);
  border: 1px solid var(--color-border-light);
  border-radius: var(--border-radius-sm);
}

.checklist-grid {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.checklist-item {
  margin-right: 0 !important;
  display: block;
}

.rationale-input :deep(.el-textarea__inner) {
  background-color: #fffaf0;
  border-color: #fbd38d;
}

.cancel-lis-check {
  margin-top: var(--spacing-md);
  padding: var(--spacing-sm);
  background-color: #f7fafc;
  border-radius: var(--border-radius-sm);
}

.footer-rejection {
  background-color: #fef2f2 !important;
}

.critical-icon {
  font-size: 28px;
  color: var(--color-danger);
}

.impact-text span {
  font-weight: bold;
  color: var(--color-danger);
  font-size: 13px;
}

.impact-text p {
  margin: 0;
  font-size: 12px;
  color: var(--color-text-secondary);
}

.btn-confirm-rejection {
  padding-left: 30px;
  padding-right: 30px;
  font-weight: bold;
}
</style>
