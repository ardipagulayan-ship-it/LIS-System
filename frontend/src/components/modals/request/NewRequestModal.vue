<template>
  <Teleport to="#app">
    <el-dialog
      :model-value="modelValue"
      width="100%"
      :close-on-click-modal="false"
      :show-close="false"
      class="new-order-modal"
      modal-class="new-order-overlay"
      :before-close="handleBeforeClose"
      top="5vh"
      @update:model-value="$emit('update:modelValue', $event)"
    >
      <!-- ===================== HEADER: title row + full-width bottom-border tab strip ===================== -->
      <template #header>
        <div class="nrm-header">
          <div class="nrm-header-top">
            <div class="nrm-header-left">
              <span class="nrm-header-title">{{
                isEditMode ? "Edit Test Order" : "New Test Order Entry"
              }}</span>
              <span class="nrm-header-status">{{
                isEditMode
                  ? `Editing Order #${editingRequestId}`
                  : `Draft Order ${draftOrderNo}`
              }}</span>
            </div>

            <div class="nrm-header-right">
              <button
                type="button"
                class="nrm-header-close"
                aria-label="Close"
                title="Back to the Front Desk worklist"
                @click="handleCancel"
              >
                <el-icon><Close /></el-icon>
              </button>
            </div>
          </div>

          <!-- Stepper stretched across the entire header width as a bottom-border tab strip -->
          <div class="nrm-steps" role="tablist" aria-label="Order entry steps">
            <button
              v-for="step in stepOptions"
              :key="step.value"
              :id="`nrm-step-${step.value}`"
              type="button"
              role="tab"
              class="nrm-step"
              :class="{
                'nrm-active': activeStep === step.value,
                'nrm-locked': step.disabled,
              }"
              :aria-selected="activeStep === step.value"
              :aria-disabled="step.disabled"
              :aria-controls="`nrm-panel-${step.value}`"
              @click="goToStep(step.value)"
            >
              <el-icon v-if="step.disabled" class="nrm-step-lock"
                ><Lock
              /></el-icon>
              {{ step.label }}
              <el-icon v-if="step.done" class="nrm-step-check"
                ><Check
              /></el-icon>
            </button>
          </div>
        </div>
      </template>

      <!-- ===================== BODY: 70 / 30 workspace ===================== -->
      <div class="nrm-body">
        <!-- Read-only order notice: Completed/Results Printed tests freeze the whole form -->
        <div v-if="isRequestReadonly" class="nrm-readonly-banner" role="status">
          <el-icon class="nrm-readonly-banner-icon"><Lock /></el-icon>
          <span>
            This request contains <strong>Completed / Results Printed</strong>
            tests and is <strong>read-only</strong>. You can review the order
            and save it as-is, but no fields, tests or lines can be changed.
          </span>
        </div>
        <div class="nrm-workspace">
          <!-- ---------------- LEFT PANE (70%): test selection engine ---------------- -->
          <section class="nrm-main">
            <!-- ============ STEP 1: PATIENT LOOKUP ============ -->
            <div
              v-show="activeStep === STEP_PATIENT"
              id="nrm-panel-1"
              class="nrm-pane"
              role="tabpanel"
              aria-labelledby="nrm-step-1"
            >
              <div
                class="nrm-card"
                :class="{ 'nrm-card--attention': !isPatientSelected }"
              >
                <div class="nrm-card-header">
                  <div class="nrm-card-heading">
                    <span class="nrm-card-icon"
                      ><el-icon><User /></el-icon
                    ></span>
                    <span class="nrm-card-title">PATIENT LOOKUP</span>
                    <span v-if="!isPatientSelected" class="nrm-req"
                      >Required</span
                    >
                    <span v-else class="nrm-ok">Attached</span>
                  </div>
                  <!-- <div class="nrm-card-actions">
                    <el-button v-if="!isPatientSelected" link type="primary" size="small" @click="toggleQuickRegister">
                      <el-icon><Plus /></el-icon>
                      Register New Patient
                    </el-button>
                  </div> -->
                </div>

                <el-select
                  v-if="!isPatientSelected"
                  v-model="patientLookupId"
                  class="nrm-full"
                  :disabled="isRequestReadonly"
                  remote
                  filterable
                  clearable
                  reserve-keyword
                  :remote-method="searchPatientsRemote"
                  :loading="loadingPatientLookup"
                  placeholder="Type Patient ID, Name, or Hospital No... (e.g., Schmitt)"
                  @change="handleLookupSelect"
                >
                  <template #prefix>
                    <el-icon><Search /></el-icon>
                  </template>
                  <el-option
                    v-for="option in patientLookupOptions"
                    :key="option.value"
                    :label="option.label"
                    :value="option.value"
                  />
                </el-select>
                <p v-if="!isPatientSelected" class="nrm-hint">
                  The lookup fires automatically after
                  {{ PATIENT_LOOKUP_MIN_CHARS }} characters. Walk-in patients
                  can be registered below without leaving this order.
                </p>

                <!-- Inline quick registration (collapsible accordion, no extra modal) -->
                <div
                  v-if="!isPatientSelected"
                  class="nrm-register-panel"
                  :class="{ 'nrm-open': showQuickRegister }"
                >
                  <div
                    class="nrm-register-header"
                    role="button"
                    tabindex="0"
                    :aria-expanded="showQuickRegister"
                    @click="toggleQuickRegister"
                    @keydown.enter.space.prevent="toggleQuickRegister"
                  >
                    <div class="nrm-register-titles">
                      <span class="nrm-register-title"
                        >Quick Register Walk-in Patient</span
                      >
                      <span class="nrm-register-sub"
                        >No record yet? Register the walk-in here — the order
                        stays on this screen.</span
                      >
                    </div>
                    <el-icon class="nrm-register-caret"><ArrowRight /></el-icon>
                  </div>
                  <el-collapse-transition>
                    <div v-show="showQuickRegister" class="nrm-register-body">
                      <el-form
                        ref="quickPatientFormRef"
                        :model="quickPatientForm"
                        :rules="quickPatientRules"
                        label-position="top"
                        class="nrm-register-form"
                        @submit.prevent="handleQuickRegister"
                      >
                        <div class="nrm-register-grid">
                          <el-form-item
                            prop="full_name"
                            class="nrm-register-item"
                          >
                            <el-input
                              v-model="quickPatientForm.full_name"
                              placeholder="Full Name (Last, First Middle)"
                              clearable
                            />
                          </el-form-item>
                          <el-form-item
                            prop="date_of_birth"
                            class="nrm-register-item"
                          >
                            <el-date-picker
                              v-model="quickPatientForm.date_of_birth"
                              type="date"
                              placeholder="DOB (MM/DD/YYYY)"
                              format="MM/DD/YYYY"
                              value-format="YYYY-MM-DD"
                              class="nrm-full"
                            />
                          </el-form-item>
                          <el-form-item prop="gender" class="nrm-register-item">
                            <el-select
                              v-model="quickPatientForm.gender"
                              placeholder="Sex"
                              class="nrm-full"
                              clearable
                            >
                              <el-option label="Male" value="Male" />
                              <el-option label="Female" value="Female" />
                              <el-option label="Other" value="Other" />
                            </el-select>
                          </el-form-item>
                          <el-form-item prop="phone" class="nrm-register-item">
                            <el-input
                              v-model="quickPatientForm.phone"
                              placeholder="Contact Phone Number"
                              clearable
                            />
                          </el-form-item>
                          <el-form-item prop="email" class="nrm-register-item">
                            <el-input
                              v-model="quickPatientForm.email"
                              type="email"
                              placeholder="Email Address"
                              clearable
                            />
                          </el-form-item>
                          <el-form-item
                            prop="address"
                            class="nrm-register-item nrm-register-address"
                          >
                            <el-input
                              v-model="quickPatientForm.address"
                              placeholder="Address"
                              clearable
                            />
                          </el-form-item>
                        </div>
                        <div class="nrm-register-actions">
                          <el-button size="small" @click="resetQuickRegister"
                            >Cancel</el-button
                          >
                          <el-button
                            type="success"
                            size="small"
                            native-type="submit"
                            :loading="savingQuickPatient"
                            @click="handleQuickRegister"
                          >
                            Save &amp; Select Patient
                          </el-button>
                        </div>
                      </el-form>
                    </div>
                  </el-collapse-transition>
                </div>

                <div v-if="isPatientSelected" class="nrm-attached">
                  <el-icon class="nrm-attached-icon"><CircleCheck /></el-icon>
                  <strong class="nrm-attached-name">{{
                    patientFullName
                  }}</strong>
                  <span class="nrm-attached-meta"
                    >Patient ID: {{ patientForm.patientId }}</span
                  >
                  <button
                    type="button"
                    class="nrm-link nrm-attached-change"
                    :disabled="isRequestReadonly"
                    @click="handleChangePatient"
                  >
                    Change
                  </button>
                </div>
              </div>

              <!-- ============ STEP 1 continued: ORDER DETAILS ============ -->
              <div class="nrm-card">
                <div class="nrm-card-header">
                  <div class="nrm-card-heading">
                    <span class="nrm-card-icon"
                      ><el-icon><Memo /></el-icon
                    ></span>
                    <span class="nrm-card-title"
                      >ORDER &amp; BILLING DETAILS</span
                    >
                    <span v-if="metadataMissing" class="nrm-req">Required</span>
                    <span v-else class="nrm-ok">Complete</span>
                  </div>
                  <span class="nrm-card-hint"
                    >Physician, priority &amp; billing</span
                  >
                </div>

                <el-form :model="additionalForm" label-position="top">
                  <div class="nrm-row nrm-row--three">
                    <div class="nrm-col">
                      <el-form-item label="Requesting Physician" required>
                        <el-select
                          v-model="additionalForm.physician"
                          placeholder="Select physician"
                          class="nrm-full"
                          filterable
                          clearable
                          :disabled="isRequestReadonly"
                          aria-required="true"
                        >
                          <el-option
                            v-for="doctor in doctors"
                            :key="doctor.id"
                            :label="doctor.name"
                            :value="doctor.id"
                          />
                        </el-select>
                      </el-form-item>
                    </div>
                    <div class="nrm-col">
                      <el-form-item label="Priority">
                        <el-select
                          v-model="additionalForm.priorityLevel"
                          placeholder="Select level"
                          class="nrm-full"
                          :disabled="isRequestReadonly"
                        >
                          <el-option label="Routine" value="Routine" />
                          <el-option label="Urgent" value="Urgent" />
                          <el-option label="STAT" value="STAT" />
                        </el-select>
                      </el-form-item>
                    </div>
                    <div class="nrm-col">
                      <el-form-item label="Patient Type" required>
                        <el-select
                          v-model="additionalForm.patient_type_id"
                          placeholder="Select type"
                          class="nrm-full"
                          clearable
                          :disabled="isRequestReadonly"
                          :loading="loadingPatientTypes"
                          aria-required="true"
                        >
                          <el-option
                            v-for="ptype in patientTypes"
                            :key="ptype.id"
                            :label="ptype.name"
                            :value="ptype.id"
                          />
                        </el-select>
                      </el-form-item>
                    </div>
                  </div>

                  <div class="nrm-row nrm-row--three">
                    <div class="nrm-col">
                      <el-form-item label="Accession ID">
                        <el-input
                          v-model="additionalForm.sample_id"
                          readonly
                          placeholder="Auto-generated"
                        >
                          <template #append>
                            <el-button
                              :icon="Refresh"
                              title="Generate a new Accession ID"
                              :disabled="isRequestReadonly"
                              @click="fetchNextSampleId"
                            />
                          </template>
                        </el-input>
                      </el-form-item>
                    </div>
                    <div class="nrm-col">
                      <el-form-item label="Ward">
                        <el-select
                          v-model="additionalForm.ward_id"
                          placeholder="Select ward"
                          class="nrm-full"
                          clearable
                          :disabled="isRequestReadonly"
                          :loading="loadingWards"
                        >
                          <el-option
                            v-for="w in wards"
                            :key="w.id"
                            :label="w.name"
                            :value="w.id"
                          />
                        </el-select>
                      </el-form-item>
                    </div>
                    <div class="nrm-col">
                      <el-form-item label="Room">
                        <el-select
                          v-model="additionalForm.room_id"
                          placeholder="Select room"
                          class="nrm-full"
                          clearable
                          :disabled="isRequestReadonly"
                          :loading="loadingRooms"
                        >
                          <el-option
                            v-for="r in rooms"
                            :key="r.id"
                            :label="r.name"
                            :value="r.id"
                          />
                        </el-select>
                      </el-form-item>
                    </div>
                  </div>
                  <div class="nrm-row nrm-row--three">
                    <div class="nrm-col">
                      <el-form-item label="Referred By">
                        <el-select
                          v-model="additionalForm.referredBy"
                          multiple
                          placeholder="Select referrer"
                          class="nrm-full"
                          filterable
                          :disabled="isRequestReadonly"
                        >
                          <el-option
                            v-for="doctor in doctors"
                            :key="doctor.id"
                            :label="doctor.name"
                            :value="doctor.id"
                          />
                        </el-select>
                      </el-form-item>
                    </div>
                    <div class="nrm-col">
                      <el-form-item label="OR No.">
                        <el-input
                          v-model="additionalForm.orNo"
                          placeholder="Optional OR number"
                          :disabled="isRequestReadonly"
                        />
                      </el-form-item>
                    </div>
                    <div class="nrm-col">
                      <el-form-item label="Payment Type">
                        <el-select
                          v-model="additionalForm.payee"
                          placeholder="Select payment type"
                          class="nrm-full"
                          :disabled="isRequestReadonly"
                        >
                          <el-option label="Cash" value="cash" />
                          <el-option label="Credit Card" value="credit" />
                          <el-option label="Insurance" value="insurance" />
                        </el-select>
                      </el-form-item>
                    </div>
                  </div>

                  <div class="nrm-row nrm-row--three">
                    <div class="nrm-col">
                      <el-form-item label="Discount Type">
                        <el-select
                          v-model="computationForm.discountType"
                          placeholder="Select discount"
                          class="nrm-full"
                          :disabled="isRequestReadonly"
                        >
                          <el-option label="N/A" value="na" />
                          <el-option label="Senior Citizen" value="senior" />
                          <el-option label="PWD" value="pwd" />
                        </el-select>
                      </el-form-item>
                    </div>
                    <div class="nrm-col">
                      <el-form-item label="Home Service Fee">
                        <el-input-number
                          v-model="computationForm.homeServiceFee"
                          :min="0"
                          :step="50"
                          :disabled="isRequestReadonly"
                          controls-position="right"
                          class="nrm-full"
                        />
                      </el-form-item>
                    </div>
                  </div>
                </el-form>
              </div>
            </div>

            <!-- ============ STEP 2: TEST MENU & CART ============ -->
            <div
              v-show="activeStep === STEP_MENU"
              id="nrm-panel-2"
              class="nrm-pane"
              role="tabpanel"
              aria-labelledby="nrm-step-2"
            >
              <div class="nrm-toolbar-sticky">
                <div class="nrm-toolbar">
                  <el-input
                    v-model="testSearchQuery"
                    class="nrm-search"
                    placeholder="Search test name, code, or keyword..."
                    clearable
                    :disabled="isRequestReadonly"
                  >
                    <template #prefix>
                      <el-icon><Search /></el-icon>
                    </template>
                  </el-input>
                  <el-select
                    v-model="selectedDepartment"
                    placeholder="All Departments"
                    class="nrm-dept"
                    :disabled="isRequestReadonly"
                  >
                    <el-option
                      label="All Departments"
                      :value="ALL_DEPARTMENTS"
                    />
                    <el-option
                      v-for="dept in departmentOptions"
                      :key="dept.id"
                      :label="dept.name"
                      :value="dept.id"
                    />
                  </el-select>
                </div>

                <div class="nrm-toolbar-meta">
                  <span
                    >{{ filteredTestMenu.length }} test{{
                      filteredTestMenu.length === 1 ? "" : "s"
                    }}
                    available</span
                  >
                  <span class="nrm-toolbar-sep">•</span>
                  <span>{{ cartItems.length }} in cart</span>
                  <span class="nrm-toolbar-hint"
                    >Click + Add to insert a test, ✕ to return it to the
                    catalog.</span
                  >
                </div>
              </div>

              <div class="nrm-grid" v-loading="loadingCategories">
                <div
                  v-for="test in filteredTestMenu"
                  :key="test.cartKey"
                  class="nrm-tcard"
                  :class="{
                    'nrm-tcard-selected': isInCart(test),
                    'nrm-tcard-locked': isCatalogCardReadonly(test),
                  }"
                  role="button"
                  :tabindex="isCatalogCardReadonly(test) ? -1 : 0"
                  :aria-pressed="isInCart(test)"
                  :aria-label="`${test.name}, ${formatCurrency(test.price)}${isCatalogCardReadonly(test) ? ` — ${lockedTestStatusOf(test) || 'read-only'}, read-only` : isInCart(test) ? ' — in cart, activate to remove' : ' — activate to add'}`"
                  :aria-disabled="isCatalogCardReadonly(test)"
                  @click="toggleCartItem(test)"
                  @keydown.enter.space.prevent="toggleCartItem(test)"
                >
                  <div class="nrm-tcard-name">{{ test.name }}</div>
                  <div class="nrm-tcard-dept">
                    {{ test.categoryName
                    }}<span v-if="test.code"> • {{ test.code }}</span>
                  </div>
                  <div class="nrm-tcard-footer">
                    <div class="nrm-tcard-price">
                      {{ formatCurrency(test.price) }}
                    </div>
                    <div class="nrm-tcard-action">
                      <template v-if="isCatalogCardReadonly(test)">
                        <el-tag
                          :type="lockTagType(lockedTestStatusOf(test))"
                          size="small"
                          effect="light"
                        >
                          <el-icon><Lock /></el-icon>
                          {{ lockedTestStatusOf(test) || "Read-only" }}
                        </el-tag>
                      </template>
                      <template v-else-if="isInCart(test)">
                        <span class="nrm-incart"
                          >In Cart <el-icon><Check /></el-icon
                        ></span>
                      </template>
                      <el-button
                        v-else
                        size="small"
                        type="success"
                        plain
                        @click.stop="addToCart(test)"
                      >
                        + Add
                      </el-button>
                    </div>
                  </div>
                </div>
                <div
                  v-if="!loadingCategories && filteredTestMenu.length === 0"
                  class="nrm-grid-empty"
                >
                  <el-icon class="nrm-grid-empty-icon"><Search /></el-icon>
                  <template v-if="catalogFiltersActive">
                    <span
                      >No tests match your search or department filter.</span
                    >
                    <el-button size="small" @click="clearCatalogFilters"
                      >Clear search &amp; filters</el-button
                    >
                  </template>
                  <span v-else
                    >No tests available in the catalog yet. Ask the lab to sync
                    the test menu.</span
                  >
                </div>
              </div>
            </div>

            <!-- ============ STEP 3: REVIEW & SUBMIT ============ -->
            <div
              v-show="activeStep === STEP_REVIEW"
              id="nrm-panel-3"
              class="nrm-pane"
              role="tabpanel"
              aria-labelledby="nrm-step-3"
            >
              <div class="nrm-review">
                <div class="nrm-review-head">
                  <div>
                    <div class="nrm-review-title">
                      Laboratory Request Preview
                    </div>
                    <div class="nrm-review-sub">
                      Nothing is written to the LIS until you submit from the
                      action bar below.
                    </div>
                  </div>
                  <div class="nrm-review-ref">
                    <span class="nrm-review-ref-label">Draft Order</span>
                    <span class="nrm-review-ref-value">{{ draftOrderNo }}</span>
                  </div>
                </div>

                <div class="nrm-rsection">
                  <div class="nrm-rsection-title">Patient</div>
                  <div class="nrm-rgrid">
                    <div class="nrm-rfield">
                      <span class="nrm-rlabel">Name</span>
                      <span class="nrm-rvalue">{{ patientFullName }}</span>
                    </div>
                    <div class="nrm-rfield">
                      <span class="nrm-rlabel">Patient ID</span>
                      <span class="nrm-rvalue">{{
                        patientForm.patientId
                      }}</span>
                    </div>
                    <div class="nrm-rfield">
                      <span class="nrm-rlabel">Date of Birth</span>
                      <span class="nrm-rvalue">{{
                        formatDate(patientForm.dateOfBirth)
                      }}</span>
                    </div>
                    <div class="nrm-rfield">
                      <span class="nrm-rlabel">Age / Sex</span>
                      <span class="nrm-rvalue">{{ patientAgeSex }}</span>
                    </div>
                    <div class="nrm-rfield">
                      <span class="nrm-rlabel">Patient Type</span>
                      <span class="nrm-rvalue">{{ patientTypeName }}</span>
                    </div>
                    <div class="nrm-rfield">
                      <span class="nrm-rlabel">Contact No.</span>
                      <span class="nrm-rvalue">{{
                        patientForm.contactNo || "N/A"
                      }}</span>
                    </div>
                    <div class="nrm-rfield nrm-rfield--wide">
                      <span class="nrm-rlabel">Address</span>
                      <span class="nrm-rvalue">{{
                        patientForm.address || "N/A"
                      }}</span>
                    </div>
                  </div>
                </div>

                <div class="nrm-rsection">
                  <div class="nrm-rsection-title">Order</div>
                  <div class="nrm-rgrid">
                    <div class="nrm-rfield">
                      <span class="nrm-rlabel">Accession ID</span>
                      <span class="nrm-rvalue">{{
                        additionalForm.sample_id || "Pending"
                      }}</span>
                    </div>
                    <div class="nrm-rfield">
                      <span class="nrm-rlabel">Requesting Physician</span>
                      <span class="nrm-rvalue">{{ physicianName }}</span>
                    </div>
                    <div class="nrm-rfield">
                      <span class="nrm-rlabel">Priority</span>
                      <span class="nrm-rvalue">{{
                        additionalForm.priorityLevel
                      }}</span>
                    </div>
                    <div class="nrm-rfield">
                      <span class="nrm-rlabel">Payment Type</span>
                      <span class="nrm-rvalue">{{ additionalForm.payee }}</span>
                    </div>
                    <div class="nrm-rfield">
                      <span class="nrm-rlabel">OR No.</span>
                      <span class="nrm-rvalue">{{
                        additionalForm.orNo || "N/A"
                      }}</span>
                    </div>
                    <div class="nrm-rfield">
                      <span class="nrm-rlabel">Discount</span>
                      <span class="nrm-rvalue">{{
                        computationForm.discountType === "na"
                          ? "N/A"
                          : discountLabel
                      }}</span>
                    </div>
                  </div>
                </div>

                <div class="nrm-rsection">
                  <div class="nrm-rsection-title">
                    Requested Tests ({{ cartItems.length }})
                  </div>
                  <table class="nrm-rtable">
                    <thead>
                      <tr>
                        <th class="nrm-rcol-index">#</th>
                        <th>Test</th>
                        <th>Department</th>
                        <th class="nrm-right">Price</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="(item, index) in cartItems"
                        :key="item.cartKey"
                      >
                        <td class="nrm-rcol-index">{{ index + 1 }}</td>
                        <td>
                          <span class="nrm-rtest-name">{{ item.name }}</span>
                          <span v-if="item.code" class="nrm-rtest-code">{{
                            item.code
                          }}</span>
                        </td>
                        <td>{{ item.categoryName }}</td>
                        <td class="nrm-right">
                          {{ formatCurrency(item.price) }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div class="nrm-rtotals">
                  <div class="nrm-trow">
                    <span>Items</span>
                    <span>{{ cartItems.length }}</span>
                  </div>
                  <div class="nrm-trow">
                    <span>Subtotal</span>
                    <span>{{ formatCurrency(cartSubtotal) }}</span>
                  </div>
                  <div
                    class="nrm-trow nrm-trow--discount"
                    v-if="discountAmount > 0"
                  >
                    <span>Discount ({{ discountLabel }})</span>
                    <span>-{{ formatCurrency(discountAmount) }}</span>
                  </div>
                  <div class="nrm-trow" v-if="homeServiceFeeAmount > 0">
                    <span>Home Service Fee</span>
                    <span>{{ formatCurrency(homeServiceFeeAmount) }}</span>
                  </div>
                  <div class="nrm-thero">
                    <span class="nrm-thero-label">Total Amount</span>
                    <span class="nrm-thero-value">{{
                      formatCurrency(cartTotalAmount)
                    }}</span>
                  </div>
                </div>

                <div class="nrm-rnote">
                  <el-icon><Printer /></el-icon>
                  <span
                    >Submitting commits this request and prints one specimen
                    barcode label per test.</span
                  >
                </div>
              </div>
            </div>

            <!-- ============ STICKY STEP FOOTER ============ -->
            <div class="nrm-stepbar">
              <div class="nrm-stepbar-status" aria-live="polite">
                <template v-if="activeStep === STEP_PATIENT">
                  <span v-if="canProceedTab1" class="nrm-stepstatus nrm-ready">
                    <el-icon><CircleCheck /></el-icon>
                    All required details captured — continue with tab 2 above
                  </span>
                  <span v-else class="nrm-stepstatus nrm-pending">
                    <el-icon><InfoFilled /></el-icon>
                    Still required: {{ tab1MissingFields.join(", ") }}
                  </span>
                </template>
                <template v-else-if="activeStep === STEP_MENU">
                  <span v-if="canProceedTab2" class="nrm-stepstatus nrm-ready">
                    <el-icon><CircleCheck /></el-icon>
                    {{ cartItems.length }} test{{
                      cartItems.length === 1 ? "" : "s"
                    }}
                    ready for review
                  </span>
                  <span v-else class="nrm-stepstatus nrm-pending">
                    <el-icon><InfoFilled /></el-icon>
                    {{ proceedBlockedReason }}
                  </span>
                </template>
                <template v-else>
                  <span class="nrm-stepstatus nrm-ready">
                    <el-icon><CircleCheck /></el-icon>
                    Order ready — submit to commit and print barcode labels
                  </span>
                </template>
              </div>
            </div>
          </section>

          <!-- ---------------- RIGHT PANE (30%): permanent order summary ---------------- -->
          <aside class="nrm-side">
            <!-- Patient context block -->
            <!-- <div class="nrm-sum nrm-sum--patient">
              <div class="nrm-sum-header">
                <span class="nrm-sum-title"><el-icon><User /></el-icon>PATIENT</span>
                <button
                  v-if="isPatientSelected"
                  type="button"
                  class="nrm-link"
                  @click="handleChangePatient"
                >
                  Change
                </button>
              </div>

              <div v-if="isPatientSelected" class="nrm-patient">
                <el-icon class="nrm-patient-avatar"><UserFilled /></el-icon>
                <div class="nrm-patient-body">
                  <div class="nrm-patient-name">{{ patientFullName }}</div>
                  <div class="nrm-patient-meta">
                    <span>{{ patientAgeSex }}</span>
                    <span class="nrm-sep">•</span>
                    <span>{{ patientTypeName }}</span>
                  </div>
                  <div class="nrm-patient-id">Patient ID: <strong>{{ patientForm.patientId }}</strong></div>
                </div>
              </div>
              <div v-else class="nrm-sum-empty">
                <el-icon class="nrm-sum-empty-icon"><User /></el-icon>
                <span>No patient attached yet</span>
                <button type="button" class="nrm-link" @click="goToStep(STEP_PATIENT)">Find a patient</button>
              </div>
            </div> -->

            <!-- Live cart -->
            <div class="nrm-sum nrm-sum--cart">
              <div class="nrm-card-header">
                <div class="nrm-card-heading">
                  <span class="nrm-card-icon"
                    ><el-icon><ShoppingCart /></el-icon
                  ></span>
                  <span class="nrm-card-title"
                    >SELECTED TESTS ({{ cartItems.length }})</span
                  >
                </div>
                <!-- <div class="nrm-card-actions">
                  <el-button v-if="!isPatientSelected" link type="primary" size="small" @click="toggleQuickRegister">
                    <el-icon><Plus /></el-icon>
                    Register New Patient
                  </el-button>
                </div> -->
              </div>

              <div class="nrm-cart">
                <div
                  v-for="item in cartItems"
                  :key="item.cartKey"
                  class="nrm-cart-item"
                  :class="{
                    'nrm-cart-item-locked': isCartRowReadonly(item),
                  }"
                >
                  <div class="nrm-cart-info">
                    <div class="nrm-cart-name">{{ item.name }}</div>
                    <div class="nrm-cart-dept">{{ item.categoryName }}</div>
                    <el-tag
                      v-if="isCartRowReadonly(item)"
                      :type="lockTagType(lockedTestStatusOf(item))"
                      size="small"
                      effect="light"
                      class="nrm-cart-locktag"
                    >
                      <el-icon><Lock /></el-icon>
                      {{ lockedTestStatusOf(item) || "Read-only" }} — read-only
                    </el-tag>
                  </div>
                  <div class="nrm-cart-actions">
                    <span class="nrm-cart-price">{{
                      formatCurrency(item.price)
                    }}</span>
                    <el-tooltip
                      v-if="isCartRowReadonly(item)"
                      :content="
                        isRequestReadonly
                          ? 'This request is read-only: it contains Completed/Results Printed tests.'
                          : `This test is ${lockedTestStatusOf(item) || 'locked'} — only Pending tests can be removed.`"
                      placement="top"
                    >
                      <span
                        class="nrm-cart-remove nrm-cart-remove--disabled"
                        aria-disabled="true"
                      >
                        <el-icon><Lock /></el-icon>
                      </span>
                    </el-tooltip>
                    <button
                      v-else
                      type="button"
                      class="nrm-cart-remove"
                      aria-label="Remove test"
                      title="Remove test"
                      @click="removeFromCart(item)"
                    >
                      <el-icon><Close /></el-icon>
                    </button>
                  </div>
                </div>
                <div v-if="cartItems.length === 0" class="nrm-cart-empty">
                  <el-icon class="nrm-cart-empty-icon"
                    ><ShoppingCart
                  /></el-icon>
                  <span>No tests added yet</span>
                  <button
                    type="button"
                    class="nrm-link"
                    @click="goToStep(STEP_MENU)"
                  >
                    Open the test menu
                  </button>
                </div>
              </div>
            </div>

            <!-- Running subtotal -->
            <div class="nrm-totals">
              <div class="nrm-trow">
                <span>Items</span>
                <span>{{ cartItems.length }}</span>
              </div>
              <div class="nrm-trow">
                <span>Subtotal</span>
                <span>{{ formatCurrency(cartSubtotal) }}</span>
              </div>
              <div
                class="nrm-trow nrm-trow--discount"
                v-if="discountAmount > 0"
              >
                <span>Discount ({{ discountLabel }})</span>
                <span>-{{ formatCurrency(discountAmount) }}</span>
              </div>
              <div class="nrm-trow" v-if="homeServiceFeeAmount > 0">
                <span>Home Service Fee</span>
                <span>{{ formatCurrency(homeServiceFeeAmount) }}</span>
              </div>
              <div class="nrm-thero">
                <span class="nrm-thero-label">Total Amount</span>
                <span class="nrm-thero-value">{{
                  formatCurrency(cartTotalAmount)
                }}</span>
              </div>
            </div>
          </aside>
        </div>
      </div>

      <!-- ===================== STICKY ACTION FOOTER ===================== -->
      <template #footer>
        <div class="nrm-footer">
          <div class="nrm-footer-info">
            <span class="nrm-pill" :class="{ 'nrm-submitting': submitting }">
              <span class="nrm-pill-dot"></span>
              {{ statusLabel }}
            </span>
            <span class="nrm-footer-note">{{
              isEditMode
                ? `Order #${editingRequestId}`
                : `Order ${draftOrderNo}`
            }}</span>
          </div>
          <div class="nrm-footer-actions">
            <el-button class="nrm-btn-cancel" @click="handleCancel"
              >Cancel</el-button
            >
            <el-button
              type="success"
              class="nrm-btn-submit"
              :loading="submitting"
              :disabled="!canProceedTab2 || submitting"
              :title="submitBlockedReason"
              @click="handleSubmitAndPrint"
            >
              {{ isEditMode ? "Save Changes" : "Submit Order & Print Barcode" }}
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from "vue";
import {
  ArrowRight,
  Check,
  CircleCheck,
  Close,
  InfoFilled,
  Lock,
  Memo,
  Plus,
  Printer,
  Refresh,
  Search,
  ShoppingCart,
  User,
  UserFilled,
} from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox } from "element-plus";
import JsBarcode from "jsbarcode";
import debounce from "lodash/debounce";
import api from "@/utils/axios";
import {
  format,
  parseISO,
  differenceInYears,
  differenceInMonths,
  differenceInDays,
} from "date-fns";

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  initialPatient: {
    type: Object,
    default: null,
  },
  // Set by Frontdesk when editing a Pending request; null/undefined = create mode.
  editingRequest: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits([
  "update:modelValue",
  "save",
  "search-patient",
  "request-created",
  "request-updated",
]);

// --- Constants ---
const PATIENT_LOOKUP_MIN_CHARS = 3; // Minimum characters before the patient lookup fires
const ALL_DEPARTMENTS = "all";
const IMAGING_DEPARTMENT = "imaging-services";
const PACKAGE_DEPARTMENT = "packages";
const SPECIAL_DEPARTMENT = "special-tests";
const PROCEDURE_DEPARTMENT = "clinical-procedures";

const submitting = ref(false);

// --- Edit mode (Frontdesk "Edit" on a request) ---
// `editingRequest` carries the full request row (with patient/tests relations).
// Only Pending test lines can be removed; every other line (Accepted,
// Checked-in, Processing Results, Result Printed, Completed, Cancelled…)
// stays locked. Patient/details fields are always editable — unless the
// request already holds Completed/Results Printed tests, in which case the
// whole modal switches to read-only (view + save-as-is only).
const isEditMode = computed(() => !!props.editingRequest);
const editingRequestId = computed(() => props.editingRequest?.id ?? "");
// Test/item statuses that make the entire order read-only.
const READONLY_ORDER_STATUSES = [
  "completed",
  "result printed",
  "results printed",
];
const normalizeItemStatus = (status) =>
  String(status || "").trim().toLowerCase();
// True while the edited request contains Completed/Results Printed work —
// every control in the modal is then disabled.
const isRequestReadonly = computed(() => {
  if (!isEditMode.value) return false;
  const request = props.editingRequest;
  if (!request) return false;
  if (READONLY_ORDER_STATUSES.includes(normalizeItemStatus(request.status))) {
    return true;
  }
  return (request.tests || []).some((t) =>
    READONLY_ORDER_STATUSES.includes(
      normalizeItemStatus(t.pivot?.item_status || t.item_status),
    ),
  );
});
// Test ids whose lines are not Pending — locked in the cart/catalog.
const lockedTestIds = ref([]);
// Display status per locked test id (e.g. "Result Printed", "Accepted").
const lockedTestStatuses = ref({});
// A cart/catalog entry is locked when its test id is in the locked set.
const isTestLocked = (test) =>
  !!test?.locked ||
  ((test?.id ?? test) != null &&
    lockedTestIds.value.includes(test?.id ?? test));
const lockedTestStatusOf = (test) => {
  if (test?.lockedStatus) return test.lockedStatus;
  const id = test?.id ?? test;
  return lockedTestStatuses.value[id] || "";
};
// A cart row is read-only when the whole order is read-only or the line
// itself is no longer Pending.
const isCartRowReadonly = (item) =>
  isRequestReadonly.value || (isEditMode.value && isTestLocked(item));
// Catalog cards additionally need the entry sitting in the cart before the
// per-line lock kicks in; a read-only order locks every card outright.
const isCatalogCardReadonly = (test) =>
  isRequestReadonly.value ||
  (isEditMode.value && isTestLocked(test) && isInCart(test));
// Colour for the lock badge shown on read-only lines.
const lockTagType = (status) => {
  const s = normalizeItemStatus(status);
  if (s === "cancelled" || s === "rejected") return "danger";
  if (READONLY_ORDER_STATUSES.includes(s) || s === "result validated")
    return "success";
  if (!s) return "info";
  return "warning";
};

// --- 3-step wizard: 1. Patient & Details -> 2. Test Menu & Cart -> 3. Review & Submit ---
const STEP_PATIENT = 1;
const STEP_MENU = 2;
const STEP_REVIEW = 3;
const activeStep = ref(STEP_PATIENT);

// Client-side draft reference shown in the header/footer until the order is committed
const draftRef = ref("");
const generateDraftRef = () => {
  draftRef.value = String(Math.floor(1000 + Math.random() * 9000));
};
const draftOrderNo = computed(() => `#${draftRef.value}`);
const statusLabel = computed(() => {
  if (submitting.value)
    return isEditMode.value ? "Saving Changes..." : "Submitting Order...";
  return isEditMode.value ? "Editing Order" : "Draft Order";
});

// Loading states for tests
const loadingImagingServices = ref(false);
const loadingPackages = ref(false);
const loadingSpecialTests = ref(false);
const loadingProcedures = ref(false);

// Refs for test data
const imagingServices = ref([]);
const packages = ref([]);
const specialTests = ref([]);
const procedures = ref([]);

// --- Order cart (single source of truth for selected tests) ---
const cartItems = ref([]);
const testSearchQuery = ref("");
const selectedDepartment = ref(ALL_DEPARTMENTS);

// --- Patient lookup / inline registration state ---
const patientLookupId = ref(null);
const patientLookupOptions = ref([]);
const loadingPatientLookup = ref(false);
const showQuickRegister = ref(false); // secondary task: keep collapsed until the clerk asks for it
const savingQuickPatient = ref(false);
const selectedPatientRecord = ref(null);
const quickPatientFormRef = ref(null);
const quickPatientForm = ref({
  full_name: "",
  email: "",
  date_of_birth: "",
  gender: "",
  phone: "",
  address: "",
});

// "Full Name (Last, First Middle)" -> { last_name, first_name, middle_name }
const parseFullName = (fullName) => {
  const raw = (fullName || "").trim();
  if (!raw) return { last_name: "", first_name: "", middle_name: "" };
  if (raw.includes(",")) {
    const [last, rest] = raw.split(",");
    const parts = (rest || "").trim().split(/\s+/).filter(Boolean);
    return {
      last_name: last.trim(),
      first_name: parts.shift() || "",
      middle_name: parts.join(" "),
    };
  }
  const parts = raw.split(/\s+/).filter(Boolean);
  const first = parts.shift() || "";
  const last = parts.length ? parts.pop() : "";
  return { last_name: last, first_name: first, middle_name: parts.join(" ") };
};

// Same Element Plus inline validation style as Login.vue (`loginRules`):
// `:rules` + `prop` on each `el-form-item`, validated via form ref before submit.
const validateQuickFullName = (rule, value, callback) => {
  const { last_name, first_name } = parseFullName(value);
  if (!last_name || !first_name) {
    callback(
      new Error("Please enter the patient full name as (Last, First Middle)."),
    );
  } else {
    callback();
  }
};

const quickPatientRules = ref({
  full_name: [
    {
      required: true,
      message: "Please enter the patient full name",
      trigger: "blur",
    },
    { validator: validateQuickFullName, trigger: ["blur", "change"] },
  ],
  date_of_birth: [
    {
      required: true,
      message: "Please provide the patient's date of birth",
      trigger: "change",
    },
  ],
  gender: [
    {
      required: true,
      message: "Please select the patient's sex",
      trigger: "change",
    },
  ],
  phone: [
    {
      required: true,
      message: "Please provide the patient's contact number",
      trigger: "blur",
    },
    {
      pattern: /^[0-9+\-\s()]{7,20}$/,
      message: "Please enter a valid phone number",
      trigger: ["blur", "change"],
    },
  ],
  email: [
    {
      required: true,
      message: "Please provide the patient's email address",
      trigger: "blur",
    },
    {
      type: "email",
      message: "Please enter a valid email address",
      trigger: ["blur", "change"],
    },
  ],
  address: [
    {
      required: true,
      message: "Please provide the patient's address",
      trigger: "blur",
    },
  ],
});

const patientForm = ref({
  sampleId: "",
  patientId: "",
  last_name: "",
  first_name: "",
  middle_name: "",
  sex: "",
  dateOfBirth: "",
  age: "",
  ageUnit: "Year(s)",
  civilStatus: "",
  address: "",
  contactNo: "",
  email: "",
});

const additionalForm = ref({
  sample_id: "",
  healthcareProvider: "",
  healthcareType: "Walk-In",
  homeService: false,
  isAR: false,
  isFOC: false,
  payee: "cash",
  orNo: "",
  priorityLevel: "Routine",
  referredBy: "",
  physician: "",
  patient_type_id: null,
  ward_id: null,
  room_id: null,
});

const distributionForm = ref({
  copy: "",
  send: "",
  pickup: "",
  fax: "",
  mail: "",
  email: "",
  sendTo: "",
});

const computationForm = ref({
  discountType: "na",
  discountPercentage: 0,
  orderedTest: 0,
  homeServiceFee: 0,
  totalAmount: 0,
});

// Doctors list
const doctors = ref([]);
const loadingDoctors = ref(false);

// Patient Types, Wards, Rooms
const patientTypes = ref([]);
const loadingPatientTypes = ref(false);
const wards = ref([]);
const loadingWards = ref(false);
const rooms = ref([]);
const loadingRooms = ref(false);

// Test categories
const testCategories = ref([]);
const loadingCategories = ref(false);
const activeCategory = ref(null);

// Tests by category
const testsByCategory = ref({});
const loadingTests = ref({});

// --- Fetch Functions ---

const fetchDoctors = async () => {
  loadingDoctors.value = true;
  try {
    const response = await api.get("/doctors");
    doctors.value = response.data.data || response.data;
  } catch (error) {
    console.error("Error fetching doctors:", error);
  } finally {
    loadingDoctors.value = false;
  }
};

const fetchPatientTypes = async () => {
  loadingPatientTypes.value = true;
  try {
    const response = await api.get("/patient-types");
    patientTypes.value = response.data;
    // Front-desk default: walk-ins are Outpatient until the clerk selects another type
    if (!additionalForm.value.patient_type_id) {
      const outpatient = patientTypes.value.find((type) =>
        String(type.name || "")
          .toLowerCase()
          .includes("outpatient"),
      );
      if (outpatient) additionalForm.value.patient_type_id = outpatient.id;
    }
  } catch (error) {
    console.error("Error fetching patient types:", error);
  } finally {
    loadingPatientTypes.value = false;
  }
};

const fetchWards = async () => {
  loadingWards.value = true;
  try {
    const response = await api.get("/wards");
    wards.value = response.data.data || response.data;
  } catch (error) {
    console.error("Error fetching wards:", error);
  } finally {
    loadingWards.value = false;
  }
};

const fetchRooms = async (wardId) => {
  if (!wardId) {
    rooms.value = [];
    return;
  }
  loadingRooms.value = true;
  try {
    const response = await api.get("/get-rooms", {
      params: { ward_id: wardId },
    });
    rooms.value = response.data;
  } catch (error) {
    console.error("Error fetching rooms:", error);
    rooms.value = [];
  } finally {
    loadingRooms.value = false;
  }
};

const fetchTestCategories = async () => {
  loadingCategories.value = true;
  loadingImagingServices.value = true;
  loadingPackages.value = true;
  loadingSpecialTests.value = true;
  loadingProcedures.value = true;

  try {
    const response = await api.get("/get-test-categories");
    const allCategories = response.data;

    const routineCategories = [];

    allCategories.forEach((category) => {
      const name = category.name.toLowerCase();

      if (
        name.includes("imaging") ||
        name.includes("x-ray") ||
        name.includes("ultrasound")
      ) {
        imagingServices.value = category.tests || [];
      } else if (name.includes("package")) {
        packages.value = category.tests || [];
      } else if (name.includes("special")) {
        specialTests.value = category.tests || [];
      } else if (name.includes("procedure")) {
        procedures.value = category.tests || [];
      } else {
        routineCategories.push(category);
      }
    });

    testCategories.value = routineCategories;

    // Populate routine tests
    if (testCategories.value.length > 0) {
      activeCategory.value = testCategories.value[0].id;
      testCategories.value.forEach((cat) => {
        testsByCategory.value[cat.id] = cat.tests || [];
      });
    }
  } catch (error) {
    ElMessage.error("Error fetching test categories");
    console.error("Error fetching test categories:", error);
  } finally {
    loadingCategories.value = false;
    loadingImagingServices.value = false;
    loadingPackages.value = false;
    loadingSpecialTests.value = false;
    loadingProcedures.value = false;
  }
};

const fetchTestsForCategory = async (categoryId) => {
  if (!categoryId) return;
  loadingTests.value[categoryId] = true;
  try {
    const response = await api.get(`/test-categories/${categoryId}/tests`);
    testsByCategory.value[categoryId] = response.data;
  } catch (error) {
    ElMessage.error(`Error fetching tests for category ${categoryId}`);
    testsByCategory.value[categoryId] = [];
  } finally {
    loadingTests.value[categoryId] = false;
  }
};

const handleCategoryChange = async (categoryId) => {
  activeCategory.value = categoryId;
  if (!testsByCategory.value[categoryId]) {
    await fetchTestsForCategory(categoryId);
  }
};

// Fetch other services (Imaging, Packages, etc. - mocked or implemented as needed)
const fetchOtherServices = async () => {
  // Implement similar to fetchTestsForCategory if API exists
  // For now, placeholders or existing logic if any
};

// --- Patient lookup, state helpers ---

// True once a patient (looked up or newly registered) is attached to the order
const isPatientSelected = computed(() => !!patientForm.value.patientId);

const patientFullName = computed(() => {
  const { last_name, first_name, middle_name } = patientForm.value;
  const base = [last_name, first_name].filter(Boolean).join(", ");
  return [base, middle_name].filter(Boolean).join(" ").trim();
});

const AGE_UNIT_LABELS = {
  "Year(s)": "Yrs",
  "Month(s)": "Mos",
  "Day(s)": "Days",
};

// Compact "21 Yrs / Male" line used by the patient context block and the review pane
const patientAgeSex = computed(() => {
  const { age, ageUnit, sex } = patientForm.value;
  const sexLabel = sex || "N/A";
  if (!age) return sexLabel;
  return `${age} ${AGE_UNIT_LABELS[ageUnit] || "Yrs"} / ${sexLabel}`;
});

// Patient type selected in Order &amp; Billing Details (falls back to the type carried by the record)
const patientTypeName = computed(() => {
  const selected = patientTypes.value.find(
    (type) => type.id === additionalForm.value.patient_type_id,
  );
  if (selected) return selected.name;
  return (
    selectedPatientRecord.value?.patient_type?.name ||
    selectedPatientRecord.value?.patient_type_name ||
    "Outpatient"
  );
});

// Requesting physician label for the review print preview
const physicianName = computed(() => {
  const physician = doctors.value.find(
    (doctor) => doctor.id === additionalForm.value.physician,
  );
  return physician ? physician.name : "—";
});

const discountLabel = computed(() => {
  if (computationForm.value.discountType === "senior") return "Senior Citizen";
  if (computationForm.value.discountType === "pwd") return "PWD";
  return "Applied";
});

const formatDate = (dateString) => {
  if (!dateString) return "N/A";
  try {
    return format(parseISO(dateString), "MM/dd/yyyy");
  } catch (e) {
    return dateString;
  }
};

const resetPatientForm = () => {
  patientForm.value = {
    sampleId: "",
    patientId: "",
    last_name: "",
    first_name: "",
    middle_name: "",
    sex: "",
    dateOfBirth: "",
    age: "",
    ageUnit: "Year(s)",
    civilStatus: "",
    address: "",
    contactNo: "",
    email: "",
  };
};

// Fills the patient card from a patient record. Used by the lookup dropdown, by
// the inline quick registration and by the `initialPatient` prop of the parent.
const applyPatientToForm = (patient) => {
  if (!patient) return;
  selectedPatientRecord.value = patient;
  patientForm.value.patientId = patient.id;
  patientForm.value.last_name = patient.last_name || "";
  patientForm.value.first_name = patient.first_name || "";
  patientForm.value.middle_name = patient.middle_name || "";
  patientForm.value.sex = patient.gender || "";
  patientForm.value.dateOfBirth = patient.date_of_birth || "";
  patientForm.value.contactNo = patient.phone || "";
  patientForm.value.email = patient.email || "";
  patientForm.value.address = patient.address || "";
  patientForm.value.civilStatus = patient.civil_status || "";
  patientForm.value.age = patient.date_of_birth
    ? differenceInYears(new Date(), parseISO(patient.date_of_birth))
    : "";
  patientForm.value.ageUnit = "Year(s)";
  // Carry the patient's registered type across when the record exposes one
  const patientTypeId = patient.patient_type_id ?? patient.patient_type?.id;
  if (patientTypeId) {
    additionalForm.value.patient_type_id = patientTypeId;
  }
  showQuickRegister.value = false;
};
// STATE 1 : type 3+ characters -> quick dropdown of matching patients
const searchPatientsRemote = debounce(async (query) => {
  const term = (query || "").trim();
  if (term.length < PATIENT_LOOKUP_MIN_CHARS) {
    patientLookupOptions.value = [];
    return;
  }
  loadingPatientLookup.value = true;
  try {
    const response = await api.get("/patients", {
      params: { search: term, per_page: 10 },
    });
    const rows = response.data?.data || [];
    patientLookupOptions.value = rows.map((patient) => ({
      value: patient.id,
      label:
        `${patient.last_name || ""}, ${patient.first_name || ""} ${patient.middle_name || ""} | ${formatDate(patient.date_of_birth)}`
          .replace(/\s+/g, " ")
          .trim(),
      patient,
    }));
    if (!rows.length) {
      ElMessage.info(
        'No matching patient found. Use "New Patient Registration" to register a walk-in patient.',
      );
    }
  } catch (error) {
    console.error("Error searching patients:", error);
    ElMessage.error("Error searching patients");
    patientLookupOptions.value = [];
  } finally {
    loadingPatientLookup.value = false;
  }
}, 300);

const handleLookupSelect = (patientId) => {
  const option = patientLookupOptions.value.find(
    (opt) => opt.value === patientId,
  );
  if (!option) return;
  applyPatientToForm(option.patient);
  ElMessage.success("Patient selected");
};

// STATE 2 -> STATE 1 : clear the card and show the lookup again
const handleChangePatient = () => {
  if (isRequestReadonly.value) {
    ElMessage.warning(
      "This request contains Completed/Results Printed tests and is read-only. The patient cannot be changed.",
    );
    return;
  }
  selectedPatientRecord.value = null;
  patientLookupId.value = null;
  patientLookupOptions.value = [];
  showQuickRegister.value = false;
  resetPatientForm();
  // Send the clerk back to step 1 so a replacement patient can be looked up
  activeStep.value = STEP_PATIENT;
};

// Inline "New Patient Registration" accordion
const toggleQuickRegister = () => {
  if (isRequestReadonly.value) {
    ElMessage.warning(
      "This request contains Completed/Results Printed tests and is read-only. New patients cannot be registered here.",
    );
    return;
  }
  showQuickRegister.value = !showQuickRegister.value;
  // When collapsing the accordion, drop any inline errors so a fresh
  // expand never shows stale validation messages.
  if (!showQuickRegister.value && quickPatientFormRef.value) {
    nextTick(() => quickPatientFormRef.value?.clearValidate());
  }
};

const resetQuickRegister = () => {
  quickPatientForm.value = {
    full_name: "",
    email: "",
    date_of_birth: "",
    gender: "",
    phone: "",
    address: "",
  };
  showQuickRegister.value = false;
  // Clear AFTER the model reset + collapse, otherwise Element Plus keeps
  // rendering the previous inline errors when the panel is reopened.
  nextTick(() => quickPatientFormRef.value?.clearValidate());
};

// Registers the walk-in patient without leaving this modal, then selects it
const handleQuickRegister = async () => {
  if (!quickPatientFormRef.value) return;
  // Same flow as Login.vue: validate the el-form first so each textbox
  // shows its inline error, then proceed only when valid.
  await quickPatientFormRef.value.validate(async (valid) => {
    if (!valid) {
      ElMessage.error("Please fill in all required fields correctly.");
      return false;
    }

    const { last_name, first_name, middle_name } = parseFullName(
      quickPatientForm.value.full_name,
    );
    const email = (quickPatientForm.value.email || "").trim();

    savingQuickPatient.value = true;
    try {
      const payload = {
        last_name,
        first_name,
        middle_name,
        gender: quickPatientForm.value.gender,
        date_of_birth: quickPatientForm.value.date_of_birth,
        phone: (quickPatientForm.value.phone || "").trim(),
        address: (quickPatientForm.value.address || "").trim(),
        email,
        civil_status: "",
      };
      const response = await api.post("patients", payload);
      const created = response.data?.data || response.data || {};
      applyPatientToForm({
        ...payload,
        id: created.id ?? created.patient_id,
        email: created.email || "",
        civil_status: created.civil_status || "",
      });
      quickPatientForm.value = {
        full_name: "",
        email: "",
        date_of_birth: "",
        gender: "",
        phone: "",
        address: "",
      };
      nextTick(() => quickPatientFormRef.value?.clearValidate());
      ElMessage.success("Patient registered and selected");
    } catch (error) {
      console.error("Error registering patient:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to register patient",
      );
    } finally {
      savingQuickPatient.value = false;
    }
  });
};

// --- Laboratory test menu (flattened catalog from every department) ---
const testMenuCatalog = computed(() => {
  const catalog = [];

  testCategories.value.forEach((category) => {
    const categoryTests =
      testsByCategory.value[category.id] || category.tests || [];
    categoryTests.forEach((test) => {
      catalog.push({
        ...test,
        departmentId: category.id,
        categoryName: category.name,
        cartKey: `category-${category.id}-test-${test.id}`,
      });
    });
  });

  imagingServices.value.forEach((test) => {
    catalog.push({
      ...test,
      departmentId: IMAGING_DEPARTMENT,
      categoryName: "Imaging Services",
      cartKey: `imaging-${test.id}`,
    });
  });
  packages.value.forEach((test) => {
    catalog.push({
      ...test,
      departmentId: PACKAGE_DEPARTMENT,
      categoryName: "Packages",
      cartKey: `package-${test.id}`,
    });
  });
  specialTests.value.forEach((test) => {
    catalog.push({
      ...test,
      departmentId: SPECIAL_DEPARTMENT,
      categoryName: "Special Tests",
      cartKey: `special-${test.id}`,
    });
  });
  procedures.value.forEach((test) => {
    catalog.push({
      ...test,
      departmentId: PROCEDURE_DEPARTMENT,
      categoryName: "Clinical Procedures",
      cartKey: `procedure-${test.id}`,
    });
  });

  return catalog;
});

const departmentOptions = computed(() => [
  ...testCategories.value.map((category) => ({
    id: category.id,
    name: category.name,
  })),
  { id: IMAGING_DEPARTMENT, name: "Imaging Services" },
  { id: PACKAGE_DEPARTMENT, name: "Packages" },
  { id: SPECIAL_DEPARTMENT, name: "Special Tests" },
  { id: PROCEDURE_DEPARTMENT, name: "Clinical Procedures" },
]);

const filteredTestMenu = computed(() => {
  const query = (testSearchQuery.value || "").trim().toLowerCase();
  return testMenuCatalog.value.filter((test) => {
    const matchesDepartment =
      selectedDepartment.value === ALL_DEPARTMENTS ||
      test.departmentId === selectedDepartment.value;
    if (!matchesDepartment) return false;
    if (!query) return true;
    const haystack = [test.name, test.code, test.categoryName]
      .filter(Boolean)
      .join(" ")
      .toLowerCase();
    return haystack.includes(query);
  });
});

// --- Step navigation & required-field gating ---------------------------------
// Step 1 is complete once the patient and the core order metadata are captured
const tab1MissingFields = computed(() => {
  const missing = [];
  if (!isPatientSelected.value) missing.push("Patient");
  if (!additionalForm.value.physician) missing.push("Requesting Physician");
  if (!additionalForm.value.patient_type_id) missing.push("Patient Type");
  return missing;
});

const canProceedTab1 = computed(() => tab1MissingFields.value.length === 0);
const canProceedTab2 = computed(
  () => canProceedTab1.value && cartItems.value.length > 0,
);

// Catalog toolbar: tells apart "filters hide everything" from "catalog is empty"
const catalogFiltersActive = computed(
  () =>
    !!(testSearchQuery.value || "").trim() ||
    selectedDepartment.value !== ALL_DEPARTMENTS,
);
const clearCatalogFilters = () => {
  testSearchQuery.value = "";
  selectedDepartment.value = ALL_DEPARTMENTS;
};

// Clinical metadata captured by the Order &amp; Billing Details card in the left pane
const metadataMissing = computed(
  () =>
    !additionalForm.value.physician || !additionalForm.value.patient_type_id,
);

// Why step 2 cannot start yet — surfaced as the step-bar status hint
const proceedBlockedReason = computed(() => {
  if (activeStep.value === STEP_PATIENT) {
    return canProceedTab1.value
      ? ""
      : `Still required: ${tab1MissingFields.value.join(", ")}`;
  }
  if (activeStep.value === STEP_MENU) {
    if (!canProceedTab1.value)
      return `Still required: ${tab1MissingFields.value.join(", ")}`;
    if (cartItems.value.length === 0)
      return "Add at least one test to continue";
  }
  return "";
});

// Why the footer's Submit Order & Print Barcode button is unavailable
const submitBlockedReason = computed(() => {
  if (!canProceedTab1.value)
    return `Still required: ${tab1MissingFields.value.join(", ")}`;
  if (cartItems.value.length === 0)
    return "Add at least one test before submitting";
  return "";
});

const canGoToStep = (step) => {
  if (step <= STEP_PATIENT) return true;
  if (step === STEP_MENU) return canProceedTab1.value;
  if (step === STEP_REVIEW) return canProceedTab2.value;
  return false;
};

// The current step never renders as locked so a clerk is never trapped mid-flow
// `done` drives the small completion check rendered after each tab label
const stepOptions = computed(() => [
  {
    label: "1. Patient & Details",
    value: STEP_PATIENT,
    disabled: STEP_PATIENT !== activeStep.value && !canGoToStep(STEP_PATIENT),
    done: canProceedTab1.value,
  },
  {
    label: "2. Test Menu & Cart",
    value: STEP_MENU,
    disabled: STEP_MENU !== activeStep.value && !canGoToStep(STEP_MENU),
    done: cartItems.value.length > 0,
  },
  {
    label: "3. Review & Submit",
    value: STEP_REVIEW,
    disabled: STEP_REVIEW !== activeStep.value && !canGoToStep(STEP_REVIEW),
    done: false,
  },
]);

const goToStep = (step) => {
  if (step === activeStep.value) return;
  if (!canGoToStep(step)) {
    if (step === STEP_MENU) {
      ElMessage.warning(
        `Complete the required details first: ${tab1MissingFields.value.join(", ")}.`,
      );
    } else if (step === STEP_REVIEW) {
      ElMessage.warning("Add at least one test before reviewing the order.");
    }
    return;
  }
  activeStep.value = step;
};

// --- Cart operations (add / remove / totals) ---
const cartSubtotal = computed(() =>
  cartItems.value.reduce((sum, item) => sum + (parseFloat(item.price) || 0), 0),
);

const isInCart = (test) =>
  cartItems.value.some((item) => item.cartKey === test.cartKey);

const addToCart = (test) => {
  // Read-only order (Completed/Results Printed tests): nothing may be added.
  if (isRequestReadonly.value) {
    ElMessage.warning(
      "This request contains Completed/Results Printed tests and is read-only. Tests cannot be added or removed.",
    );
    return;
  }
  if (isInCart(test)) {
    ElMessage.info(`${test.name} is already in the cart.`);
    return;
  }
  cartItems.value.push(test);
  calculateTotal();
};

const removeFromCart = (test) => {
  // Read-only order: nothing may leave the cart either.
  if (isRequestReadonly.value) {
    ElMessage.warning(
      "This request contains Completed/Results Printed tests and is read-only. Tests cannot be added or removed.",
    );
    return;
  }
  // Locked lines (anything not Pending) can never leave the cart in edit mode.
  if (isEditMode.value && isTestLocked(test)) {
    ElMessage.warning(
      `This test is ${lockedTestStatusOf(test) || "locked"} — only Pending tests can be removed.`,
    );
    return;
  }
  cartItems.value = cartItems.value.filter(
    (item) => item.cartKey !== test.cartKey,
  );
  calculateTotal();
};

// Clicking a catalog card toggles it in / out of the cart
const toggleCartItem = (test) => {
  // Read-only order or a locked line that is already in the cart stays put.
  if (isRequestReadonly.value) {
    ElMessage.warning(
      "This request contains Completed/Results Printed tests and is read-only. Tests cannot be added or removed.",
    );
    return;
  }
  if (isEditMode.value && isTestLocked(test) && isInCart(test)) {
    ElMessage.warning(
      `This test is ${lockedTestStatusOf(test) || "locked"} — only Pending tests can be removed.`,
    );
    return;
  }
  if (isInCart(test)) {
    removeFromCart(test);
  } else {
    addToCart(test);
  }
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  }).format(value);
};

const calculateTotal = () => {
  // The cart is the single source of truth for the order total
  const subtotal = cartSubtotal.value;

  computationForm.value.orderedTest = subtotal;

  // Apply discount and home service fee
  const discountValue = subtotal * computationForm.value.discountPercentage;
  const homeServiceFee = parseFloat(computationForm.value.homeServiceFee) || 0;
  computationForm.value.totalAmount = subtotal - discountValue + homeServiceFee;
};

// --- Running subtotal card (right pane bottom) ---
const discountAmount = computed(
  () => cartSubtotal.value * (computationForm.value.discountPercentage || 0),
);
const homeServiceFeeAmount = computed(
  () => parseFloat(computationForm.value.homeServiceFee) || 0,
);
const cartTotalAmount = computed(() => computationForm.value.totalAmount || 0);

watch(
  () => computationForm.value.discountType,
  (val) => {
    if (val === "senior" || val === "pwd") {
      computationForm.value.discountPercentage = 0.2;
    } else {
      computationForm.value.discountPercentage = 0;
    }
    calculateTotal();
  },
);

watch(
  () => computationForm.value.homeServiceFee,
  () => {
    calculateTotal();
  },
);

watch(
  () => additionalForm.value.ward_id,
  (newWardId) => {
    additionalForm.value.room_id = null;
    if (newWardId) {
      fetchRooms(newWardId);
    } else {
      rooms.value = [];
    }
  },
);

// Keep the wizard honest: never leave the clerk on a step that is no longer valid
watch(
  () => cartItems.value.length,
  (count) => {
    if (count === 0 && activeStep.value === STEP_REVIEW) {
      activeStep.value = STEP_MENU;
    }
  },
);

watch(isPatientSelected, (selected) => {
  if (!selected && activeStep.value !== STEP_PATIENT) {
    activeStep.value = STEP_PATIENT;
  }
});

// Watch initialPatient prop (set by the parent view when a patient row is used)
watch(
  () => props.initialPatient,
  (newPatient) => {
    if (newPatient) {
      applyPatientToForm(newPatient);
    }
  },
  { immediate: true },
);

// Prefills every editable field from the request being edited: patient card,
// Order & Billing Details, and the test cart (matched against the catalog
// once categories finish loading). Every non-Pending line is recorded in
// `lockedTestIds` so the cart/catalog render them as read-only; a request
// with Completed/Results Printed tests renders the whole modal read-only.
const applyEditingRequestToForm = (request) => {
  if (!request) return;
  handleClose();
  nextTick(() => {
    if (request.patient) applyPatientToForm(request.patient);
    additionalForm.value.sample_id = request.sample_id || "";
    additionalForm.value.physician = request.doctor_id ?? "";
    additionalForm.value.referredBy = request.referring_doctor || "";
    additionalForm.value.priorityLevel = request.priority_level || "Routine";
    additionalForm.value.patient_type_id = request.patient_type_id ?? null;
    additionalForm.value.ward_id = request.ward_id ?? null;
    if (request.ward_id) fetchRooms(request.ward_id);
    additionalForm.value.room_id = request.room_id ?? null;
    const locked = {};
    (request.tests || []).forEach((t) => {
      const status = t.pivot?.item_status || t.item_status || "Pending";
      // Only Pending lines are removable — anything else locks the line.
      if (normalizeItemStatus(status) !== "pending" && t.id != null) {
        locked[t.id] = status;
      }
    });
    lockedTestStatuses.value = locked;
    lockedTestIds.value = Object.keys(locked).map((id) => Number(id));
    prefillCartFromRequestTests(request.tests || []);
    // Land on the test menu: patient + details already prefilled above.
    activeStep.value = STEP_MENU;
  });
};

// Matches stored test ids against whichever catalogs already loaded;
// categories load lazily, so retry until every id resolves or gives up.
const prefillCartFromRequestTests = (tests, attempt = 0) => {
  const ids = (tests || []).map((t) => t.id).filter((id) => id != null);
  if (!ids.length) {
    cartItems.value = [];
    calculateTotal();
    return;
  }
  const catalog = testMenuCatalog.value;
  const matched = ids
    .map((id) => {
      // Prefer the entry from the stored test's own category so same-id
      // tests from other departments never produce a wrong cartKey/price.
      const stored = (tests || []).find((t) => t.id === id);
      const categoryId = stored?.test_category_id ?? stored?.test_category?.id;
      if (categoryId != null) {
        const scoped = catalog.find(
          (entry) => entry.id === id && entry.departmentId === categoryId,
        );
        if (scoped) return scoped;
      }
      return catalog.find((entry) => entry.id === id);
    })
    .filter(Boolean);
  if (matched.length === ids.length || attempt >= 10) {
    // Stamp lock flags so cart rows render read-only even though the catalog
    // entry objects are shared with create mode.
    cartItems.value = matched.map((entry) => ({
      ...entry,
      locked: lockedTestIds.value.includes(entry.id),
      lockedStatus: lockedTestStatuses.value[entry.id] || "",
    }));
    calculateTotal();
    return;
  }
  setTimeout(() => prefillCartFromRequestTests(tests, attempt + 1), 300);
};

watch(
  () => props.editingRequest,
  (request) => {
    if (request && props.modelValue) applyEditingRequestToForm(request);
  },
);

const handleSave = async ({ printAfter = false } = {}) => {
  if (!patientForm.value.patientId) {
    ElMessage.warning("Please select a patient first.");
    activeStep.value = STEP_PATIENT;
    return;
  }

  if (tab1MissingFields.value.length > 0) {
    ElMessage.warning(
      `Please complete: ${tab1MissingFields.value.join(", ")}.`,
    );
    activeStep.value = STEP_PATIENT;
    return;
  }

  if (cartItems.value.length === 0) {
    ElMessage.warning("Please select at least one test.");
    activeStep.value = STEP_MENU;
    return;
  }

  submitting.value = true;
  try {
    const payload = {
      patient_id: patientForm.value.patientId,
      sample_id: additionalForm.value.sample_id,
      doctor_id: additionalForm.value.physician,
      priority_level: additionalForm.value.priorityLevel,
      payment_type: additionalForm.value.payee,
      patient_type_id: additionalForm.value.patient_type_id,
      ward_id: additionalForm.value.ward_id,
      room_id: additionalForm.value.room_id,
      or_no: additionalForm.value.orNo,
      healthcare_provider: additionalForm.value.healthcareProvider,
      healthcare_type: additionalForm.value.healthcareType,
      home_service: additionalForm.value.homeService,
      is_ar: additionalForm.value.isAR,
      is_foc: additionalForm.value.isFOC,
      discount_type: computationForm.value.discountType,
      discount_percentage: computationForm.value.discountPercentage,
      home_service_fee: computationForm.value.homeServiceFee,
      total_amount: computationForm.value.totalAmount,
      test_items: cartItems.value.map((t) => t.id),
      distribution: {
        copy: distributionForm.value.copy,
        send: distributionForm.value.send,
        pickup: distributionForm.value.pickup,
        fax: distributionForm.value.fax,
        mail: distributionForm.value.mail,
        email: distributionForm.value.email,
        send_to: distributionForm.value.sendTo,
      },
      notes: "",
    };

    // Edit mode: update the request in place. Patient/details fields are
    // always editable unless the request is read-only (Completed/Results
    // Printed tests). Only Pending test lines can be removed; every other
    // line is locked — enforced in the cart UI and by the server (422 +
    // message).
    if (isEditMode.value) {
      try {
        const response = await api.put(
          `/laboratory-requests/${editingRequestId.value}`,
          payload,
        );
        ElMessage.success("Request updated successfully");
        emit("request-updated", response.data);
        emit("update:modelValue", false);
        handleClose();
      } catch (error) {
        console.error("Error updating request:", error);
        ElMessage.error(
          error.response?.data?.message ||
            "Failed to update the request. Only Pending tests can be removed.",
        );
      } finally {
        submitting.value = false;
      }
      return;
    }

    const response = await api.post("/laboratory-requests", payload);
    ElMessage.success("Request created successfully");
    emit("request-created", response.data);
    if (printAfter) {
      printRequestLabels(response.data);
    }
    emit("update:modelValue", false);
    handleClose();
  } catch (error) {
    console.error("Error creating request:", error);
    ElMessage.error(
      error.response?.data?.message || "Failed to create request",
    );
  } finally {
    submitting.value = false;
  }
};

// Footer primary action: save the order, then print one barcode label per test
const handleSubmitAndPrint = () => handleSave({ printAfter: true });

const printRequestLabels = (request) => {
  const tests = request?.tests || [];
  if (!request || tests.length === 0) {
    ElMessage.info("No tests available to print barcode labels for.");
    return;
  }

  const sampleId = request.sample_id || request.id;
  const patientLastName = request.patient?.last_name || "";
  const patientFirstName = request.patient?.first_name || "";
  const requestDate = request.request_date || new Date().toISOString();

  let labelsHtml = "";
  tests.forEach((test) => {
    const canvas = document.createElement("canvas");
    try {
      JsBarcode(canvas, String(sampleId), {
        format: "CODE128",
        width: 1.5,
        height: 60,
        displayValue: true,
        fontSize: 14,
        margin: 5,
        textMargin: 2,
      });
      labelsHtml += `
        <div class="label-container">
          <img class="barcode-img" src="${canvas.toDataURL()}"/>
          <div class="patient-info">
            <p>${patientLastName}, ${patientFirstName}</p>
            <p>SID: ${sampleId}</p>
            <p>${test.name || "N/A"}</p>
            <p>${formatDate(requestDate)}</p>
          </div>
        </div>`;
    } catch (e) {
      console.error("JsBarcode Error for item:", test.name, e);
      labelsHtml += `<div class="label-container error">Barcode failed for ${test.name}</div>`;
    }
  });

  const printWindow = window.open("", "_blank");
  if (!printWindow) {
    ElMessage.error(
      "Failed to open the print window. Please allow pop-ups to print barcodes.",
    );
    return;
  }

  const printHtml = `<!DOCTYPE html>
  <html>
    <head>
      <title>Barcode Labels</title>
      <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 10px; }
        .label-container { display: flex; align-items: center; gap: 8px; border: 1px dashed #999; padding: 6px; margin-bottom: 6px; page-break-inside: avoid; }
        .barcode-img { height: 60px; }
        .patient-info p { margin: 0; font-size: 11px; white-space: nowrap; }
        .error { color: #dc2626; font-size: 12px; }
      </style>
    </head>
    <body>
      ${labelsHtml}
      <script>window.onload = () => { window.print(); window.onafterprint = () => window.close(); };<\/script>
    </body>
  </html>`;

  try {
    printWindow.document.open();
    printWindow.document.write(printHtml);
    printWindow.document.close();
  } catch (e) {
    console.error("Error preparing print content:", e);
    ElMessage.error("Failed to prepare the barcode print content.");
    try {
      printWindow.close();
    } catch (err) {
      /* ignore */
    }
  }
};

const handleClose = () => {
  // Reset the order cart, the selected patient and the lookup/register state
  cartItems.value = [];
  lockedTestIds.value = [];
  lockedTestStatuses.value = {};
  testSearchQuery.value = "";
  selectedDepartment.value = ALL_DEPARTMENTS;
  selectedPatientRecord.value = null;
  patientLookupId.value = null;
  patientLookupOptions.value = [];
  showQuickRegister.value = false;
  quickPatientForm.value = {
    full_name: "",
    email: "",
    date_of_birth: "",
    gender: "",
    phone: "",
    address: "",
  };
  // Discard must also wipe inline errors — otherwise the next open replays
  // the previous validation messages on empty textboxes.
  nextTick(() => quickPatientFormRef.value?.clearValidate());
  resetPatientForm();
  calculateTotal();
  // Rewind the wizard and hand out a fresh draft reference
  activeStep.value = STEP_PATIENT;
  generateDraftRef();
};

// Guarded close: never throw away a half-built order without asking.
// Resolves true when it is safe to discard, false to keep the dialog open.
const isOrderDirty = computed(() => {
  if (patientForm.value.patientId || cartItems.value.length > 0) return true;
  const q = quickPatientForm.value;
  return !!(
    q.full_name ||
    q.email ||
    q.date_of_birth ||
    q.gender ||
    q.phone ||
    q.address
  );
});

const confirmDiscardOrder = async () => {
  if (!isOrderDirty.value) return true;
  try {
    await ElMessageBox.confirm(
      isEditMode.value
        ? "This order has unsaved changes. Closing will discard them."
        : "This draft order has unsaved changes (patient, tests or registration details). Closing will discard them.",
      isEditMode.value ? "Discard changes?" : "Discard draft order?",
      {
        confirmButtonText: "Discard",
        cancelButtonText: "Keep editing",
        type: "warning",
      },
    );
    return true;
  } catch {
    return false;
  }
};

// The ESC key path (close-on-press-escape) runs through el-dialog's before-close
const handleBeforeClose = async (done) => {
  const discard = await confirmDiscardOrder();
  if (!discard) return;
  handleClose();
  done();
};

// Header ✕ and the footer Cancel button share one close-and-reset behaviour
const handleCancel = async () => {
  const discard = await confirmDiscardOrder();
  if (!discard) return;
  emit("update:modelValue", false);
  handleClose();
};

const fetchNextSampleId = async () => {
  try {
    const response = await api.get("/laboratory-requests/generate-sample-id");
    additionalForm.value.sample_id = response.data.sample_id;
  } catch (error) {
    console.error("Error fetching sample ID:", error);
    ElMessage.error("Failed to generate Accession ID");
  }
};

onMounted(() => {
  generateDraftRef();
  fetchDoctors();
  fetchPatientTypes();
  fetchWards();
  fetchTestCategories();
  // fetchOtherServices();
  fetchNextSampleId();
});

watch(
  () => props.modelValue,
  (val) => {
    if (val) {
      // Edit mode: prefill from the Pending request instead of minting a draft.
      if (props.editingRequest) {
        applyEditingRequestToForm(props.editingRequest);
        return;
      }
      generateDraftRef();
      // Launched from a patient row -> the lookup is already resolved, so open the test menu.
      // Otherwise the clerk always starts at Patient Lookup.
      activeStep.value = props.initialPatient ? STEP_MENU : STEP_PATIENT;
      fetchNextSampleId();
      // Re-apply the patient passed by the parent view (e.g. "New Request" on a patient row)
      if (props.initialPatient) {
        applyPatientToForm(props.initialPatient);
      }
    }
  },
);
</script>

<style>
/* ============================================================
   New Test Order Entry — 3-step order entry station
   (Patient Lookup -> Test Menu & Cart -> Review & Submit)
   NOTE: intentionally not scoped. Every selector is namespaced
   under .new-order-modal so nothing leaks into other views.
   ============================================================ */

/* ---------- Overlay backdrop ---------- */
.new-order-overlay {
  background: rgba(15, 23, 42, 0.6) !important;
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
}

/* ---------- Dialog frame ---------- */
.el-dialog.new-order-modal {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 1200px;
  height: 90vh;
  min-width: 0;
  margin: 5vh auto;
  padding: 0;
  border-radius: 4px;
  overflow: hidden;
  background: var(--color-bg-primary);
  box-shadow: 0 28px 70px rgba(8, 15, 32, 0.4);
  /* Defensive resets: this modal must not inherit anything from the global
     design system (body/`.modal-*` globals set text-align, text-transform,
     gradients and negative margins outside of our namespaced selectors). */
  text-align: left;
  text-transform: none;
  letter-spacing: normal;
  font-family: inherit;
  color: #0f172a;
}

.new-order-modal .el-dialog__header {
  padding: 0;
  margin: 0;
}

.new-order-modal .el-dialog__headerbtn {
  display: none;
}

.new-order-modal .el-dialog__body {
  flex: 1 1 auto;
  min-height: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.new-order-modal .el-dialog__footer {
  flex: 0 0 auto;
  padding: 0;
  margin: 0;
}

.new-order-modal .nrm-full {
  width: 100%;
}

/* ---------- Header: title row + full-width bottom-border tab strip ---------- */
.new-order-modal .nrm-header {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  padding: 0;
  border-bottom: 1px solid #eef1f6;
  background: #ffffff;
}

.new-order-modal .nrm-header-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  padding: 13px 24px 12px;
}

.new-order-modal .nrm-header-left {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 210px;
}

.new-order-modal .nrm-header-title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.01em;
  text-transform: none;
  text-decoration: none;
  white-space: nowrap;
}

.new-order-modal .nrm-header-status {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-success);
  text-transform: none;
  text-decoration: none;
}

.new-order-modal .nrm-header-right {
  display: flex;
  justify-content: flex-end;
  min-width: 32px;
}

/* ---------- Stepper: full-width bottom-border tab strip ---------- */
.new-order-modal .nrm-steps {
  display: flex;
  align-items: stretch;
  width: 100%;
  border-top: 1px solid #eef1f6;
  background: #f8fafc;
}

.new-order-modal .nrm-step {
  flex: 1 1 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  padding: 12px 14px;
  border: none;
  background: transparent;
  font-size: 13px;
  font-weight: 600;
  font-family: inherit;
  color: #64748b;
  white-space: nowrap;
  cursor: pointer;
  transition:
    background 0.15s ease,
    color 0.15s ease,
    box-shadow 0.15s ease;
  margin: 2px;
}

.new-order-modal .nrm-step:last-child {
  border-right: none;
}

.new-order-modal .nrm-step:hover:not(:disabled):not(.nrm-active) {
  background: #ffffff;
  color: #0f172a;
  border: 1px solid #e2e8f0;
}

.new-order-modal .nrm-step.nrm-active {
  background: var(--color-success-light);
  border: 1px solid var(--color-success);
  color: #0f172a;
  font-weight: 700;
}

.new-order-modal .nrm-step.nrm-locked {
  color: #a6b2c3;
  cursor: not-allowed;
}

.new-order-modal .nrm-step:disabled {
  color: #a6b2c3;
  cursor: not-allowed;
}

.new-order-modal .nrm-step-check {
  font-size: 13px;
  color: #10b981;
}

.new-order-modal .nrm-header-close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  color: #64748b;
  font-size: 16px;
  cursor: pointer;
  transition: all 0.2s;

  padding: 10px;
}

.new-order-modal .nrm-header-close:hover {
  color: #0f172a;
  background: var(--color-danger-light) !important;
  border: 1px solid var(--color-danger);
  transform: rotate(90deg);
}

/* ---------- Body: 70 / 30 workspace ---------- */
.new-order-modal .nrm-body {
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.new-order-modal .nrm-workspace {
  flex: 1;
  min-height: 0;
  display: grid;
  grid-template-columns: minmax(0, 7fr) minmax(0, 3fr);
  grid-template-rows: minmax(0, 1fr);
}

.new-order-modal .nrm-main {
  min-height: 0;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.new-order-modal .nrm-pane {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  padding: 10px 10px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  scrollbar-gutter: stable;
}

.new-order-modal .nrm-pane::-webkit-scrollbar,
.new-order-modal .nrm-cart::-webkit-scrollbar {
  width: 8px;
}

.new-order-modal .nrm-pane::-webkit-scrollbar-track,
.new-order-modal .nrm-cart::-webkit-scrollbar-track {
  background: #fff;
}

/* Quiet by default so the gutter never competes with the pane divider */
.new-order-modal .nrm-pane::-webkit-scrollbar-thumb,
.new-order-modal .nrm-cart::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.35);
  border: 2px solid transparent;
  background-clip: content-box;
  border-radius: 8px;
}

.new-order-modal .nrm-pane:hover::-webkit-scrollbar-thumb,
.new-order-modal .nrm-cart:hover::-webkit-scrollbar-thumb {
  background: rgba(100, 116, 139, 0.6);
  background-clip: content-box;
}

/* ---------- Left pane cards ---------- */
.new-order-modal .nrm-card {
  border: 1px solid #e6ebf2;
  border-radius: 4px;
  padding: 14px 16px;
  background: #ffffff;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

/* Amber nudge toward the field the clerk still has to act on */
.new-order-modal .nrm-card--attention {
  border-color: #fcd34d;
  box-shadow: 0 0 0 3px rgba(252, 211, 77, 0.16);
}

.new-order-modal .nrm-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.new-order-modal .nrm-card-heading {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Icon chip — replaces the legacy emoji markers with in-family SVG icons */
.new-order-modal .nrm-card-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  background: #ecfdf5;
  color: #059669;
  font-size: 13px;
  flex-shrink: 0;
}

.new-order-modal .nrm-card-title {
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.06em;
  color: #0f172a;
}

.new-order-modal .nrm-card-hint {
  font-size: 12px;
  color: #94a3b8;
}

.new-order-modal .nrm-card-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.new-order-modal .nrm-card-actions .el-button {
  font-size: 12px;
  font-weight: 600;
}

.new-order-modal .nrm-hint {
  margin: 6px 0 0;
  font-size: 12px;
  color: #94a3b8;
  text-align: left;
}

/* ---------- Required / complete markers ---------- */
.new-order-modal .nrm-req,
.new-order-modal .nrm-ok {
  display: inline-flex;
  align-items: center;
  padding: 2px 7px;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  white-space: nowrap;
}

.new-order-modal .nrm-req {
  background: #fef3c7;
  color: #b45309;
}

.new-order-modal .nrm-ok {
  background: #d1fae5;
  color: #047857;
}

/* ---------- Patient lookup + quick register ---------- */
.new-order-modal .nrm-register-panel {
  margin-top: 12px;
  border: 1px dashed #d8e0ea;
  border-radius: 4px;
  background: #fbfdff;
  overflow: hidden;
}

.new-order-modal .nrm-register-panel.nrm-open {
  border-style: solid;
  background: #ffffff;
}

.new-order-modal .nrm-register-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 12px 14px;
  cursor: pointer;
  user-select: none;
}

.new-order-modal .nrm-register-header:focus-visible {
  outline: 2px solid #16a34a;
  outline-offset: -2px;
}

.new-order-modal .nrm-register-titles {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.new-order-modal .nrm-register-title {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.new-order-modal .nrm-register-sub {
  font-size: 12px;
  color: #94a3b8;
}

.new-order-modal .nrm-register-caret {
  color: #94a3b8;
  font-size: 13px;
  transition: transform 0.2s ease;
}

.new-order-modal .nrm-register-panel.nrm-open .nrm-register-caret {
  transform: rotate(90deg);
}

.new-order-modal .nrm-register-body {
  padding: 6px 14px 14px;
}

.new-order-modal .nrm-register-grid {
  display: grid;
  grid-template-columns: 2fr 1.25fr 1.25fr;
  gap: 10px;
}

.new-order-modal .nrm-register-address {
  grid-column: span 2;
}

.new-order-modal .nrm-register-grid .el-input,
.new-order-modal .nrm-register-grid .el-select,
.new-order-modal .nrm-register-grid .el-date-editor {
  width: 100%;
}

/* Element Plus inline validation (same as Login.vue): keep grid tight, show errors under each textbox */
.new-order-modal .nrm-register-grid .nrm-register-item {
  margin-bottom: 10px;
}

.new-order-modal .nrm-register-grid .nrm-register-item .el-form-item__content {
  line-height: normal;
}

.new-order-modal .nrm-register-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 12px;
}

.new-order-modal .nrm-register-actions .el-button--success {
  background: #16a34a;
  border-color: #16a34a;
  color: #ffffff;
}

.new-order-modal .nrm-register-actions .el-button--success:hover,
.new-order-modal .nrm-register-actions .el-button--success:focus {
  background: #15803d;
  border-color: #15803d;
  color: #ffffff;
}

/* ---------- Attached patient strip ---------- */
.new-order-modal .nrm-attached {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 14px;
  padding: 12px 14px;
  border: 1px solid #a7f3d0;
  background: #ecfdf5;
}

.new-order-modal .nrm-attached-icon {
  font-size: 18px;
  color: #059669;
}

.new-order-modal .nrm-attached-name {
  font-size: 13px;
  font-weight: 700;
  color: #065f46;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.new-order-modal .nrm-attached-meta {
  font-size: 12px;
  color: #047857;
  white-space: nowrap;
}

.new-order-modal .nrm-attached-change {
  margin-left: auto;
  flex-shrink: 0;
}

/* ---------- Order details form ---------- */
.new-order-modal .el-form-item {
  margin-bottom: 16px;
}

.new-order-modal .el-form-item__label {
  font-size: 12px;
  font-weight: 600;
  color: #334155;
  line-height: 1.3;
  margin-bottom: 4px;
}

.new-order-modal .nrm-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  column-gap: 16px;
}

.new-order-modal .nrm-row--three {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.new-order-modal .nrm-col {
  min-width: 0;
}

/* ---------- Test selection engine (step 2) ---------- */
/* Sticky search header: pins the search + department filter + counts to the
   top of the scrollable Test Menu pane so they stay reachable while the
   test cards scroll underneath. (The Cart side already behaves this way —
   only .nrm-cart scrolls, its header is fixed by the flex layout.) */
.new-order-modal .nrm-toolbar-sticky {
  position: sticky;
  top: 0;
  z-index: 5;
  display: flex;
  flex-direction: column;
  gap: 8px;
  /* Extend over the pane's own padding so cards slide underneath cleanly */
  padding: 5px 0px;
  background: var(--color-bg-primary);
  border-bottom: 1px solid var(--color-border-secondary);
  width: 100%;
}

.new-order-modal .nrm-toolbar {
  display: flex;
  gap: 10px;
}

.new-order-modal .nrm-toolbar .nrm-search {
  flex: 1 1 auto;
  min-width: 0;
}

.new-order-modal .nrm-dept {
  flex: 0 0 200px;
  width: 200px;
}

.new-order-modal .nrm-toolbar-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #94a3b8;
}

.new-order-modal .nrm-toolbar-sep {
  color: #cbd5e1;
}

.new-order-modal .nrm-toolbar-hint {
  margin-left: auto;
}

.new-order-modal .nrm-grid {
  position: relative;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  align-content: start;
}

.new-order-modal .nrm-tcard {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 4px;
  background: #ffffff;
  cursor: pointer;
  transition:
    border-color 0.15s ease,
    box-shadow 0.15s ease,
    background 0.15s ease;
}

.new-order-modal .nrm-tcard:hover {
  border-color: #cbd5e1;
  box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
}

.new-order-modal .nrm-tcard:focus-visible {
  outline: 3px solid #16a34a;
  outline-offset: 2px;
}

.new-order-modal .nrm-tcard.nrm-tcard-selected {
  border-color: #10b981;
  background: #f4fdf8;
  box-shadow:
    inset 0 0 0 1px #10b981,
    0 0 0 3px rgba(16, 185, 129, 0.16);
}

/* Locked test lines in edit mode (Cancelled/Completed): read-only. */
.new-order-modal .nrm-tcard.nrm-tcard-locked {
  cursor: not-allowed;
  background: var(--color-bg-secondary);
}

.new-order-modal .nrm-tcard.nrm-tcard-locked:hover {
  border-color: #e5e7eb;
  box-shadow: none;
}

.new-order-modal .nrm-tcard-name {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.3;
}

.new-order-modal .nrm-tcard-dept {
  font-size: 12px;
  color: #94a3b8;
}

.new-order-modal .nrm-tcard-footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-top: auto;
  padding-top: 10px;
}

.new-order-modal .nrm-tcard-price {
  font-size: 13px;
  font-weight: 800;
  color: #16a34a;
  white-space: nowrap;
}

.new-order-modal .nrm-tcard-action {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-left: auto;
  flex-wrap: wrap;
}

.new-order-modal .nrm-tcard-action .el-button {
  border-radius: 8px;
  font-weight: 700;
}

.new-order-modal .nrm-tcard-action .el-button--success.is-plain {
  background: #ecfdf5;
  border-color: #a7f3d0;
  color: #047857;
}

.new-order-modal .nrm-tcard-action .el-button--success.is-plain:hover,
.new-order-modal .nrm-tcard-action .el-button--success.is-plain:focus {
  background: #d1fae5;
  border-color: #6ee7b7;
  color: #047857;
}

.new-order-modal .nrm-incart {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 5px 10px;
  background: #d1fae5;
  color: #047857;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

.new-order-modal .nrm-incart .el-icon {
  font-size: 12px;
}

.new-order-modal .nrm-tcard-remove {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  border: 1px solid #fecaca;
  background: #fff5f5;
  color: #dc2626;
  cursor: pointer;
  transition:
    background 0.15s ease,
    border-color 0.15s ease,
    color 0.15s ease;
}

.new-order-modal .nrm-tcard-remove:hover {
  background: #fee2e2;
  border-color: #fca5a5;
  color: #b91c1c;
}

.new-order-modal .nrm-tcard-remove .el-icon {
  font-size: 12px;
}

.new-order-modal .nrm-grid-empty {
  grid-column: 1 / -1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 34px 16px;
  text-align: center;
  font-size: 13px;
  color: #94a3b8;
}

.new-order-modal .nrm-grid-empty-icon {
  font-size: 26px;
  color: #cbd5e1;
}

.new-order-modal .nrm-grid-empty .el-button {
  margin-top: 2px;
}

/* ---------- Sticky step footer ---------- */
.new-order-modal .nrm-stepbar {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 12px 22px;
  border-top: 1px solid #eef1f6;
  background: #fbfcfe;
}

.new-order-modal .nrm-stepbar-status {
  min-width: 0;
}

.new-order-modal .nrm-stepstatus {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
}

.new-order-modal .nrm-stepstatus.nrm-ready {
  color: #047857;
}

.new-order-modal .nrm-stepstatus.nrm-pending {
  color: #b45309;
}

/* Step tab lock icon */
.new-order-modal .nrm-step-lock {
  font-size: 12px;
  opacity: 0.6;
  margin-right: 3px;
}

/* ---------- Review & submit (step 3) ---------- */
.new-order-modal .nrm-review {
  display: flex;
  flex-direction: column;
  gap: 18px;
  padding: 22px 24px;
  border: 1px solid #e6ebf2;
  background: #ffffff;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.new-order-modal .nrm-review-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding-bottom: 16px;
  border-bottom: 2px solid #0f172a;
}

.new-order-modal .nrm-review-title {
  font-size: 16px;
  font-weight: 800;
  color: #0f172a;
}

.new-order-modal .nrm-review-sub {
  margin-top: 3px;
  font-size: 12px;
  color: #94a3b8;
}

.new-order-modal .nrm-review-ref {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
}

.new-order-modal .nrm-review-ref-label {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: #94a3b8;
}

.new-order-modal .nrm-review-ref-value {
  font-size: 18px;
  font-weight: 800;
  color: #16a34a;
}

.new-order-modal .nrm-rsection {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.new-order-modal .nrm-rsection-title {
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: #0f172a;
}

.new-order-modal .nrm-rgrid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px 18px;
}

.new-order-modal .nrm-rfield {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.new-order-modal .nrm-rfield--wide {
  grid-column: 1 / -1;
}

.new-order-modal .nrm-rlabel {
  font-size: 12px;
  font-weight: 600;
  color: #94a3b8;
}

.new-order-modal .nrm-rvalue {
  font-size: 13px;
  color: #1e293b;
}

.new-order-modal .nrm-rtable {
  width: 100%;
  border-collapse: collapse;
}

.new-order-modal .nrm-rtable th {
  padding: 8px 10px;
  text-align: left;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #64748b;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.new-order-modal .nrm-rtable td {
  padding: 10px;
  font-size: 13px;
  color: #1e293b;
  vertical-align: top;
  border-bottom: 1px solid #f1f5f9;
}

.new-order-modal .nrm-rtable .nrm-right {
  text-align: right;
}

.new-order-modal .nrm-rcol-index {
  width: 40px;
  color: #94a3b8;
}

.new-order-modal .nrm-rtest-name {
  display: block;
  font-weight: 700;
  color: #0f172a;
}

.new-order-modal .nrm-rtest-code {
  display: block;
  margin-top: 2px;
  font-size: 12px;
  color: #94a3b8;
}

.new-order-modal .nrm-rtotals {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-self: flex-end;
  width: 100%;
  max-width: 320px;
  padding-top: 12px;
  border-top: 1px solid #eef1f6;
}

.new-order-modal .nrm-rnote {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  border: 1px solid #bbf7d0;
  background: #f0fdf4;
  color: #047857;
  font-size: 12px;
  font-weight: 600;
}

/* ---------- Right pane: permanent order summary ---------- */
.new-order-modal .nrm-side {
  min-height: 0;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  border-left: 1px solid #eef1f6;
  background: #fbfcfe;
}

.new-order-modal .nrm-sum {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px 20px;
  border-bottom: 1px solid #eef1f6;
}

.new-order-modal .nrm-sum--patient {
  gap: 10px;
}

.new-order-modal .nrm-sum-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.new-order-modal .nrm-sum-title {
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.06em;
  color: #64748b;
}

.new-order-modal .nrm-link {
  border: none;
  background: transparent;
  padding: 0;
  font-family: inherit;
  font-size: 12px;
  font-weight: 700;
  color: #16a34a;
  cursor: pointer;
}

.new-order-modal .nrm-link:hover {
  color: #15803d;
  text-decoration: underline;
}

.new-order-modal .nrm-patient {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  border: 1px solid #e6ebf2;
  background: #ffffff;
}

.new-order-modal .nrm-patient-avatar {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: #ecfdf5;
  border: 1px solid #d1fae5;
  color: #059669;
  font-size: 16px;
}

.new-order-modal .nrm-patient-body {
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
}

.new-order-modal .nrm-patient-name {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.new-order-modal .nrm-patient-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #64748b;
}

.new-order-modal .nrm-patient-id {
  font-size: 12px;
  color: #94a3b8;
}

.new-order-modal .nrm-patient-id strong {
  color: #334155;
}

.new-order-modal .nrm-sep {
  color: #cbd5e1;
}

.new-order-modal .nrm-sum-empty {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #94a3b8;
}

.new-order-modal .nrm-sum-empty-icon {
  font-size: 13px;
  color: #cbd5e1;
}

/* ---------- Live cart ---------- */
.new-order-modal .nrm-sum--cart {
  flex: 1 1 auto;
  min-height: 0;
}

.new-order-modal .nrm-cart {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.new-order-modal .nrm-cart-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 11px 0;
  border-bottom: 1px solid #f1f5f9;
}

.new-order-modal .nrm-cart-item:last-of-type {
  border-bottom: none;
}

.new-order-modal .nrm-cart-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.new-order-modal .nrm-cart-name {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.new-order-modal .nrm-cart-dept {
  font-size: 12px;
  color: #94a3b8;
}

.new-order-modal .nrm-cart-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.new-order-modal .nrm-cart-price {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
  white-space: nowrap;
}

.new-order-modal .nrm-cart-remove {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border: 1px solid #fecaca;
  background: #fff5f5;
  color: #dc2626;
  cursor: pointer;
  transition:
    background 0.15s ease,
    border-color 0.15s ease,
    color 0.15s ease;
}

.new-order-modal .nrm-cart-remove:hover {
  background: #fee2e2;
  border-color: #fca5a5;
  color: #b91c1c;
}

.new-order-modal .nrm-cart-remove .el-icon {
  font-size: 12px;
}

/* Read-only order banner: shown when Completed/Results Printed tests freeze the form. */
.new-order-modal .nrm-readonly-banner {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  flex-shrink: 0;
  padding: 10px 16px;
  background: #fffbeb;
  border-bottom: 1px solid #fde68a;
  color: #92400e;
  font-size: 13px;
  line-height: 1.5;
}

.new-order-modal .nrm-readonly-banner-icon {
  margin-top: 2px;
  color: #d97706;
  font-size: 15px;
}

/* Locked cart rows in edit mode: non-Pending lines are read-only. */
.new-order-modal .nrm-cart-item-locked {
  background: #f8fafc;
  border-color: #e2e8f0;
}

.new-order-modal .nrm-cart-locktag {
  margin-top: 4px;
}

.new-order-modal .nrm-cart-remove--disabled {
  border-color: #e2e8f0;
  background: #f1f5f9;
  color: #94a3b8;
  cursor: not-allowed;
}

.new-order-modal .nrm-cart-remove--disabled:hover {
  border-color: #e2e8f0;
  background: #f1f5f9;
  color: #94a3b8;
}

.new-order-modal .nrm-cart-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 18px 4px;
  text-align: center;
  font-size: 12px;
  color: #94a3b8;
}

.new-order-modal .nrm-cart-empty-icon {
  font-size: 20px;
  color: #cbd5e1;
}

/* ---------- Running subtotal card ---------- */
.new-order-modal .nrm-totals {
  margin-top: auto;
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 16px 20px 18px;
  border-top: 1px solid #eef1f6;
  background: var(--color-bg-secondary);
}

.new-order-modal .nrm-trow {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 12px;
  color: #64748b;
}

.new-order-modal .nrm-trow span:last-child {
  font-weight: 700;
  color: #334155;
}

.new-order-modal .nrm-trow--discount span:last-child {
  color: #b45309;
}

.new-order-modal .nrm-thero {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 10px;
  margin-top: 6px;
  padding-top: 12px;
  border-top: 1px dashed #e2e8f0;
}

.new-order-modal .nrm-thero-label {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.new-order-modal .nrm-thero-value {
  font-size: 24px;
  font-weight: 800;
  line-height: 1.1;
  letter-spacing: -0.02em;
  color: #16a34a;
}

/* ---------- Sticky action footer ---------- */
.new-order-modal .nrm-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 24px;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}

.new-order-modal .nrm-footer-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.new-order-modal .nrm-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  background: #eef2f7;
  color: #334155;
  font-size: 12px;
  font-weight: 700;
}

.new-order-modal .nrm-pill-dot {
  width: 8px;
  height: 8px;
  background: #16a34a;
  box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.16);
}

.new-order-modal .nrm-pill.nrm-submitting .nrm-pill-dot {
  background: #f59e0b;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.18);
}

.new-order-modal .nrm-footer-note {
  font-size: 12px;
  color: #94a3b8;
}

.new-order-modal .nrm-footer-actions {
  display: flex;
  gap: 10px;
}

.new-order-modal .el-button {
  font-weight: 600;
}

.new-order-modal .nrm-btn-cancel {
  background: #ffffff;
  border: 1px solid #d1d5db;
  color: #374151;
}

.new-order-modal .nrm-btn-cancel:hover,
.new-order-modal .nrm-btn-cancel:focus {
  background: #f9fafb;
  border-color: #9ca3af;
  color: #111827;
}

.new-order-modal .nrm-btn-submit.el-button--success {
  /* green-700 so the white label holds a WCAG AA ~5.0:1 (the lighter #16a34a only reached ~3.3:1) */
  background: #15803d;
  border-color: #15803d;
  color: #ffffff;
  font-weight: 700;
  padding: 10px 22px;
}

.new-order-modal .nrm-btn-submit.el-button--success:hover,
.new-order-modal .nrm-btn-submit.el-button--success:focus {
  background: #166534;
  border-color: #166534;
  color: #ffffff;
}

/* ---------- Form controls ---------- */
.new-order-modal .el-input__wrapper,
.new-order-modal .el-select__wrapper {
  min-height: 36px;
}

.new-order-modal .el-input-number {
  width: 100%;
}

/* ---------- Responsive ---------- */
@media (max-width: 1180px) {
  .new-order-modal .nrm-workspace {
    grid-template-columns: minmax(0, 1fr) 310px;
  }
  .new-order-modal .nrm-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  .new-order-modal .nrm-rgrid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 980px) {
  .el-dialog.new-order-modal {
    width: 96% !important;
    height: auto;
    max-height: 92vh;
  }
  .new-order-modal .nrm-body {
    overflow-y: auto;
  }
  .new-order-modal .nrm-workspace {
    grid-template-columns: minmax(0, 1fr);
  }
  .new-order-modal .nrm-pane {
    overflow: visible;
  }
  /* Stacked mobile layout: the body scrolls instead of the pane, so the
     search bar sticks to the viewport top of the dialog instead. */
  .new-order-modal .nrm-side {
    border-left: none;
    border-top: 1px solid #eef1f6;
  }
  .new-order-modal .nrm-sum--cart {
    min-height: 180px;
  }
  .new-order-modal .nrm-cart {
    max-height: 280px;
  }
}

@media (max-width: 760px) {
  .new-order-modal .nrm-header-top {
    padding: 11px 14px 10px;
  }
  .new-order-modal .nrm-step {
    padding: 10px 6px;
    font-size: 12px;
    gap: 4px;
  }
  .new-order-modal .nrm-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .new-order-modal .nrm-toolbar {
    flex-direction: column;
  }
  .new-order-modal .nrm-dept {
    width: 100%;
    flex: 1 1 auto;
  }
  .new-order-modal .nrm-row {
    grid-template-columns: 1fr;
  }
  .new-order-modal .nrm-register-grid {
    grid-template-columns: 1fr 1fr;
  }
  .new-order-modal .nrm-register-address {
    grid-column: 1 / -1;
  }
  .new-order-modal .nrm-rgrid {
    grid-template-columns: 1fr;
  }
  .new-order-modal .nrm-stepbar {
    flex-direction: column;
    align-items: stretch;
  }
  .new-order-modal .nrm-footer {
    flex-direction: column;
    align-items: stretch;
  }
  .new-order-modal .nrm-footer-actions {
    justify-content: flex-end;
    flex-wrap: wrap;
  }
}
</style>
