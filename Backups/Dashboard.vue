<template>
  <div class="ops-dashboard">
    <div class="dash-header">
      <div class="dash-title-block">
        <div class="dash-title-row">
          <h1 class="dash-title">{{ greeting }}, {{ userName }}</h1>
          <span class="live-dot" title="Live data"><i></i>Live</span>
        </div>
        <p class="dash-sub">
          {{ todayLabel }} · Front desk, laboratory, quality, equipment and
          inventory at a glance.
        </p>
      </div>
      <div class="dash-header-actions">
        <el-select
          v-model="periodDays"
          style="width: 130px"
          @change="loadAll(true)"
        >
          <el-option label="Last 7 days" :value="7" />
          <el-option label="Last 14 days" :value="14" />
          <el-option label="Last 30 days" :value="30" />
          <el-option label="Last 90 days" :value="90" />
        </el-select>
        <el-button type="primary" :loading="refreshing" @click="loadAll(true)"
          ><el-icon><Refresh /></el-icon> Refresh</el-button
        >
      </div>
      <div
        class="alert-pills"
        v-if="activeQcHolds > 0 || inventoryAlerts > 0 || overdueCount > 0"
      >
        <el-tooltip
          v-if="activeQcHolds > 0"
          :content="`${activeQcHolds} analyzer(s) on QC hold — results are blocked. Click to review.`"
          placement="bottom"
        >
          <span
            class="alert-pill alert-error"
            @click="go('/result-management/qc-log')"
            ><el-icon><Warning /></el-icon> {{ activeQcHolds }} QC hold{{
              activeQcHolds === 1 ? "" : "s"
            }}</span
          >
        </el-tooltip>
        <el-tooltip
          v-if="inventoryAlerts > 0"
          :content="`${inventoryAlerts} inventory alert(s) — expiry / low stock / quarantine. Click to open inventory.`"
          placement="bottom"
        >
          <span
            class="alert-pill alert-warn"
            @click="go('/inventory-management')"
            ><el-icon><Warning /></el-icon> {{ inventoryAlerts }} inventory
            alert{{ inventoryAlerts === 1 ? "" : "s" }}</span
          >
        </el-tooltip>
        <el-tooltip
          v-if="overdueCount > 0"
          :content="`${overdueCount} overdue equipment / maintenance item(s). Click to open EIM.`"
          placement="bottom"
        >
          <span
            class="alert-pill alert-warn"
            @click="go('/equipment-management')"
            ><el-icon><Warning /></el-icon> {{ overdueCount }} overdue</span
          >
        </el-tooltip>
        <span
          class="alert-pill alert-ok"
          v-if="
            activeQcHolds === 0 && inventoryAlerts === 0 && overdueCount === 0
          "
          ><el-icon><CircleCheck /></el-icon> All clear</span
        >
      </div>
    </div>

    <el-row :gutter="16" class="kpi-row">
      <el-col :xs="12" :sm="12" :md="6">
        <el-card class="kpi-card" @click="go('/frontdesk')">
          <template v-if="loadingCore">
            <div class="kpi-skeleton">
              <div class="skeleton-row"><div class="skeleton-icon"></div><div class="skeleton-text"></div></div>
              <div class="skeleton-row"><div class="skeleton-value"></div></div>
              <div class="skeleton-row"><div class="skeleton-label"></div></div>
              <div class="skeleton-row"><div class="skeleton-foot"></div></div>
            </div>
          </template>
          <template v-else>
          <div class="kpi-top">
            <span class="kpi-icon kpi-primary"
              ><el-icon><Document /></el-icon></span
            ><span
              class="kpi-delta"
              v-if="accessionDelta !== null"
              :class="{ up: accessionDelta >= 0, down: accessionDelta < 0 }"
              >{{ accessionDelta >= 0 ? "+" : "" }}{{ accessionDelta }}%</span
            >
          </div>
          <div class="kpi-value">{{ totalAccessions }}</div>
          <div class="kpi-label">Accessions (period)</div>
          <div class="kpi-foot">
            {{ frontdesk.pending }} pending · {{ frontdesk.in_progress }} in
            progress
          </div>
          </template>
        </el-card>
      </el-col>
      <el-col :xs="12" :sm="12" :md="6">
        <el-card class="kpi-card" @click="go('/result-management/dashboard')">
          <template v-if="loadingCore">
            <div class="kpi-skeleton">
              <div class="skeleton-row"><div class="skeleton-icon"></div><div class="skeleton-text"></div></div>
              <div class="skeleton-row"><div class="skeleton-value"></div></div>
              <div class="skeleton-row"><div class="skeleton-label"></div></div>
              <div class="skeleton-row"><div class="skeleton-foot"></div></div>
            </div>
          </template>
          <template v-else>
          <div class="kpi-top">
            <span class="kpi-icon kpi-success"
              ><el-icon><CircleCheck /></el-icon></span
            ><span class="kpi-pill">{{ completionRate }}% released</span>
          </div>
          <div class="kpi-value">{{ resultsTotals.released }}</div>
          <div class="kpi-label">Results released</div>
          <div class="kpi-foot">
            {{ resultsTotals.pending }} pending ·
            {{ resultsTotals.processing }} processing
          </div>
          </template>
        </el-card>
      </el-col>
      <el-col :xs="12" :sm="12" :md="6">
        <el-card class="kpi-card" @click="go('/phlebotomy')">
          <template v-if="loadingCore">
            <div class="kpi-skeleton">
              <div class="skeleton-row"><div class="skeleton-icon"></div><div class="skeleton-text"></div></div>
              <div class="skeleton-row"><div class="skeleton-value"></div></div>
              <div class="skeleton-row"><div class="skeleton-label"></div></div>
              <div class="skeleton-row"><div class="skeleton-foot"></div></div>
            </div>
          </template>
          <template v-else>
          <div class="kpi-top">
            <span class="kpi-icon kpi-warning"
              ><el-icon><Clock /></el-icon></span
            ><span class="kpi-pill">{{ phleb.send_out }} send-out</span>
          </div>
          <div class="kpi-value">{{ phleb.pending_collections }}</div>
          <div class="kpi-label">Awaiting collection</div>
          <div class="kpi-foot">
            {{ phleb.collected_today }} collected today ·
            {{ phleb.parked }} parked
          </div>
          </template>
        </el-card>
      </el-col>
      <el-col :xs="12" :sm="12" :md="6">
        <el-card class="kpi-card" @click="go('/quality-control')">
          <template v-if="loadingCore">
            <div class="kpi-skeleton">
              <div class="skeleton-row"><div class="skeleton-icon"></div><div class="skeleton-text"></div></div>
              <div class="skeleton-row"><div class="skeleton-value"></div></div>
              <div class="skeleton-row"><div class="skeleton-label"></div></div>
              <div class="skeleton-row"><div class="skeleton-foot"></div></div>
            </div>
          </template>
          <template v-else>
          <div class="kpi-top">
            <span class="kpi-icon kpi-danger"
              ><el-icon><Warning /></el-icon></span
            ><span class="kpi-pill" :class="{ bad: activeQcHolds > 0 }"
              >{{ activeQcHolds }} QC holds</span
            >
          </div>
          <div class="kpi-value">
            {{ sigma.excellent }}<small>/{{ sigma.total }}</small>
          </div>
          <div class="kpi-label">Analytes at Six Sigma</div>
          <div class="kpi-foot">{{ sigma.poor }} need attention (σ &lt; 4)</div>
          </template>
        </el-card>
      </el-col>
    </el-row>
    <el-row :gutter="16" class="section-row">
      <el-col :xs="24" :lg="14">
        <el-card class="panel">
          <template #header
            ><div class="panel-head">
              <span
                ><el-icon><TrendCharts /></el-icon> Workload by section</span
              ><el-button text @click="go('/result-management/dashboard')"
                >Open results <el-icon><ArrowRight /></el-icon
              ></el-button></div
          ></template>
          <div class="chart-box">
            <canvas ref="workloadCanvas"></canvas>
            <div class="sk-chart" v-if="loadingCore" aria-hidden="true">
              <span class="sk"></span>
              <span class="sk"></span>
              <span class="sk"></span>
              <span class="sk"></span>
              <span class="sk"></span>
              <span class="sk"></span>
            </div>
          </div>
          <div class="cat-grid" v-if="loadingCore">
            <div v-for="i in 6" :key="'cat-sk-' + i" class="sk-chip">
              <div class="sk w70"></div>
              <div class="sk w45"></div>
            </div>
          </div>
          <div class="cat-grid" v-else>
            <div
              v-for="c in topCategories"
              :key="c.id"
              class="cat-chip"
              :title="c.name"
              @click="
                go(
                  `/result-management/category/${c.id}/${encodeURIComponent(c.name || '')}`,
                )
              "
            >
              <div class="cat-chip-top">
                <span
                  class="dot"
                  :style="{ background: c.color || 'var(--color-primary)' }"
                ></span>
                <span class="cat-name">{{ c.name }}</span>
                <el-tag
                  v-if="qcByCategory[c.id]?.on_hold_count > 0"
                  type="danger"
                  size="small"
                  >QC {{ qcByCategory[c.id].on_hold_count }}</el-tag
                >
              </div>
              <div class="cat-nums">
                {{ c.stats?.total || 0 }} total ·
                {{ c.stats?.pending || 0 }} pend ·
                {{ c.stats?.processing || 0 }} proc
              </div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :lg="10">
        <el-card class="panel quick-panel">
          <template #header
            ><div class="panel-head">
              <span
                ><el-icon><Right /></el-icon> Quick actions</span
              >
            </div></template
          >
          <div class="quick-grid">
            <el-button @click="go('/frontdesk')"
              ><el-icon><Document /></el-icon> New request</el-button
            >
            <el-button @click="go('/phlebotomy')"
              ><el-icon><Clock /></el-icon> Collect samples</el-button
            >
            <el-button @click="go('/order-management')"
              ><el-icon><Calendar /></el-icon> Check-in queue</el-button
            >
            <el-button @click="go('/result-management/dashboard')"
              ><el-icon><CircleCheck /></el-icon> Enter results</el-button
            >
            <el-button @click="go('/management-report/census')"
              ><el-icon><DataAnalysis /></el-icon> Census</el-button
            >
            <el-button @click="go('/management-report/turnaround-time')"
              ><el-icon><TrendCharts /></el-icon> TAT report</el-button
            >
            <el-button @click="go('/equipment-management')"
              ><el-icon><Tools /></el-icon> Equipment</el-button
            >
            <el-button @click="go('/inventory-management')"
              ><el-icon><Box /></el-icon> Inventory</el-button
            >
          </div>
          <el-divider />
          <div class="mini-stats" v-if="loadingCore">
            <div v-for="i in 4" :key="'ms' + i">
              <span class="sk bar-sm w70"></span>
              <span class="sk bar-md w45"></span>
            </div>
          </div>
          <div class="mini-stats" v-else>
            <div>
              <span>Operational rate</span
              ><strong>{{ equipOperational }}%</strong>
            </div>
            <div>
              <span>Maintenance overdue</span
              ><strong>{{ eim.maintenance?.overdue || 0 }}</strong>
            </div>
            <div>
              <span>Low-stock lots</span
              ><strong>{{ imm.alerts?.low_stock_lots || 0 }}</strong>
            </div>
            <div>
              <span>Expiring lots</span
              ><strong>{{ imm.alerts?.expiring_lots || 0 }}</strong>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
    <el-row :gutter="16" class="section-row">
      <el-col :xs="24" :lg="14">
        <el-card class="panel">
          <template #header
            ><div class="panel-head">
              <span
                ><el-icon><DataAnalysis /></el-icon> In-house vs send-out
                volume</span
              ><el-button text @click="go('/management-report/census')"
                >Census <el-icon><ArrowRight /></el-icon
              ></el-button></div
          ></template>
          <div class="census-kpis" v-if="loadingCore">
            <div v-for="i in 3" :key="'ck' + i">
              <span class="sk bar-sm w70"></span>
              <span class="sk bar-md w45"></span>
            </div>
          </div>
          <div class="census-kpis" v-else-if="census">
            <div>
              <span>Total accessions</span
              ><strong>{{ census.total_accessions?.value ?? "—" }}</strong>
            </div>
            <div>
              <span>Send-out rate</span
              ><strong>{{ census.send_out_rate?.value ?? "—" }}%</strong>
            </div>
            <div>
              <span>Reruns</span
              ><strong>{{ census.reruns?.value ?? "—" }}</strong>
            </div>
          </div>
          <div class="chart-box tall">
            <canvas ref="censusCanvas"></canvas>
            <div class="sk-chart" v-if="loadingCore" aria-hidden="true">
              <span class="sk"></span>
              <span class="sk"></span>
              <span class="sk"></span>
              <span class="sk"></span>
              <span class="sk"></span>
              <span class="sk"></span>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :lg="10">
        <el-card class="panel">
          <template #header
            ><div class="panel-head">
              <span
                ><el-icon><Clock /></el-icon> Slowest turnaround</span
              ><el-button text @click="go('/management-report/turnaround-time')"
                >TAT <el-icon><ArrowRight /></el-icon
              ></el-button></div
          ></template>
          <div class="chart-box">
            <canvas ref="tatCanvas"></canvas>
            <div class="sk-chart" v-if="loadingCore" aria-hidden="true">
              <span class="sk"></span>
              <span class="sk"></span>
              <span class="sk"></span>
              <span class="sk"></span>
              <span class="sk"></span>
              <span class="sk"></span>
            </div>
          </div>
          <div class="tat-list">
            <template v-if="loadingCore">
              <div class="sk sk-tat" v-for="i in 3" :key="'tat-sk-' + i"></div>
            </template>
            <template v-else>
              <div
                v-for="t in [...tatData]
                  .sort((a, b) => (b.avg_tat || 0) - (a.avg_tat || 0))
                  .slice(0, 5)"
                :key="t.category_id"
                class="tat-row"
              >
                <span>{{ t.category_name }}</span>
                <el-tag :type="tatBadge(t.avg_tat)" size="small"
                  >{{ t.avg_tat }}h</el-tag
                >
              </div>
            </template>
            <el-empty
              v-if="!loadingCore && !tatData.length"
              description="No TAT data yet"
              :image-size="60"
            />
          </div>
        </el-card>
      </el-col>
    </el-row>
    <el-row :gutter="16" class="section-row">
      <el-col :xs="24" :lg="10">
        <el-card class="panel">
          <template #header
            ><div class="panel-head">
              <span
                ><el-icon><Warning /></el-icon> Sigma watchlist</span
              ><el-button text @click="go('/sigma-monitor')"
                >Sigma <el-icon><ArrowRight /></el-icon
              ></el-button></div
          ></template>
          <div class="sk-table" v-if="loadingCore" aria-hidden="true">
            <span class="sk"></span>
            <span class="sk w70"></span>
            <span class="sk w45"></span>
            <span class="sk w70"></span>
            <span class="sk"></span>
          </div>
          <el-table
            v-else
            :data="sigma.list"
            size="small"
            style="width: 100%"
          >
            <el-table-column
              prop="analyte_name"
              label="Analyte"
              min-width="130"
            />
            <el-table-column prop="sigma_metric" label="σ" width="70"
              ><template #default="{ row }">{{
                row.sigma_metric ?? "—"
              }}</template></el-table-column
            >
            <el-table-column prop="status_label" label="Status" width="110"
              ><template #default="{ row }"
                ><el-tag
                  size="small"
                  :type="
                    row.sigma_metric >= 6
                      ? 'success'
                      : row.sigma_metric >= 4
                        ? 'warning'
                        : 'danger'
                  "
                  >{{ row.status_label || "—" }}</el-tag
                ></template
              ></el-table-column
            >
          </el-table>
          <div class="rec-list" v-if="qcRecs.length">
            <p class="rec-title">QC recommendations</p>
            <div v-for="(r, i) in qcRecs" :key="i" class="rec-item">
              <el-icon><Warning /></el-icon>
              <span>{{
                r.title ||
                r.message ||
                r.recommendation ||
                "Review QC performance"
              }}</span>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :lg="14">
        <el-card class="panel">
          <template #header
            ><div class="panel-head">
              <span
                ><el-icon><Tools /></el-icon> Equipment health</span
              ><el-button text @click="go('/equipment-management')"
                >EIM <el-icon><ArrowRight /></el-icon
              ></el-button></div
          ></template>
          <div class="equip-body">
            <div class="equip-top">
              <div class="equip-donut-wrap">
                <div class="chart-box donut">
                  <canvas ref="equipCanvas"></canvas>
                  <div class="sk-donut" v-if="loadingCore" aria-hidden="true">
                    <span class="sk"></span>
                  </div>
                </div>
                <div class="equip-rate">
                  <template v-if="loadingCore">
                    <span class="sk sk-rate"></span>
                  </template>
                  <template v-else>
                    <strong>{{ equipOperational }}%</strong
                    ><span>operational</span>
                  </template>
                </div>
              </div>
              <div class="equip-grid four" v-if="loadingCore">
                <div v-for="i in 4" :key="'eg' + i">
                  <span class="sk bar-sm w70"></span>
                  <span class="sk bar-md w45"></span>
                </div>
              </div>
              <div class="equip-grid four" v-else>
                <div>
                  <span>Total</span
                  ><strong>{{ eim.equipment?.total || 0 }}</strong>
                </div>
                <div>
                  <span>In service</span
                  ><strong class="ok">{{
                    eim.equipment?.in_service || 0
                  }}</strong>
                </div>
                <div>
                  <span>Maintenance</span
                  ><strong class="warn">{{
                    eim.equipment?.under_maintenance || 0
                  }}</strong>
                </div>
                <div>
                  <span>Overdue</span
                  ><strong class="bad">{{
                    eim.equipment?.overdue || 0
                  }}</strong>
                </div>
              </div>
            </div>
            <p class="list-title">
              Needs attention
              <span class="list-count" v-if="(eim.overdue_items || []).length"
                >({{ (eim.overdue_items || []).length }})</span
              >
            </p>
            <div class="overdue-list">
              <template v-if="loadingCore">
                <div
                  class="overdue-row"
                  v-for="i in 3"
                  :key="'od-sk' + i"
                >
                  <span class="sk bar-md w70"></span>
                  <span class="sk bar-sm sk-tag"></span>
                </div>
              </template>
              <template v-else>
                <div
                  v-for="(o, i) in (eim.overdue_items || []).slice(0, 4)"
                  :key="i"
                  class="overdue-row"
                >
                <span
                  class="overdue-name"
                  :title="
                    o.equipment_name ||
                    o.unique_asset_id ||
                    o.name ||
                    'Equipment'
                  "
                  >{{
                    o.equipment_name ||
                    o.unique_asset_id ||
                    o.name ||
                    "Equipment"
                  }}</span
                >
                <el-tag
                  size="small"
                  :type="dueTag(o.next_due_date || o.next_maintenance_date)"
                  >{{
                    dueLabel(o.next_due_date || o.next_maintenance_date)
                  }}</el-tag
                >
              </div>
              <el-empty
                v-if="!loadingCore && !(eim.overdue_items || []).length"
                description="Nothing overdue"
                :image-size="48"
              />
              </template>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
    <el-row :gutter="16" class="section-row">
      <el-col :xs="24" :lg="14">
        <el-card class="panel">
          <template #header
            ><div class="panel-head">
              <span
                ><el-icon><User /></el-icon> Latest requests</span
              ><el-button text @click="go('/frontdesk')"
                >View all <el-icon><ArrowRight /></el-icon
              ></el-button></div
          ></template>
          <div class="sk-table" v-if="loadingCore" aria-hidden="true">
            <span class="sk"></span>
            <span class="sk w70"></span>
            <span class="sk w45"></span>
            <span class="sk w70"></span>
            <span class="sk"></span>
          </div>
          <el-table
            v-else
            :data="recentRequests"
            size="small"
            style="width: 100%"
          >
            <el-table-column label="Request" width="120"
              ><template #default="{ row }"
                >#{{ row.id || row.request_number }}
                {{ row.sample_id ? `· ${row.sample_id}` : "" }}</template
              ></el-table-column
            >
            <el-table-column label="Patient" min-width="150"
              ><template #default="{ row }"
                >{{ row.patient?.first_name || "" }}
                {{
                  row.patient?.last_name || row.patient?.name || "—"
                }}</template
              ></el-table-column
            >
            <el-table-column prop="priority_level" label="Priority" width="95"
              ><template #default="{ row }"
                ><el-tag
                  size="small"
                  :type="reqStatusType(row.priority_level)"
                  >{{ row.priority_level || "Routine" }}</el-tag
                ></template
              ></el-table-column
            >
            <el-table-column prop="request_date" label="Date" width="110"
              ><template #default="{ row }">{{
                fmtDate(row.request_date || row.created_at)
              }}</template></el-table-column
            >
          </el-table>
          <el-empty
            v-if="!loadingCore && !recentRequests.length"
            description="No recent requests"
            :image-size="56"
          />
        </el-card>
      </el-col>
      <el-col :xs="24" :lg="10">
        <el-card class="panel">
          <template #header
            ><div class="panel-head">
              <span
                ><el-icon><Box /></el-icon> Consumables & lots</span
              ><el-button text @click="go('/inventory-management')"
                >Inventory <el-icon><ArrowRight /></el-icon
              ></el-button></div
          ></template>
          <div class="equip-grid three" v-if="loadingCore">
            <div v-for="i in 6" :key="'cs' + i">
              <span class="sk bar-sm w70"></span>
              <span class="sk bar-md w45"></span>
            </div>
          </div>
          <div class="equip-grid three" v-else>
            <div>
              <span>Active lots</span
              ><strong>{{ imm.lots?.active || 0 }}</strong>
            </div>
            <div>
              <span>Low stock</span
              ><strong class="warn">{{
                imm.alerts?.low_stock_lots ?? consum.low_stock ?? 0
              }}</strong>
            </div>
            <div>
              <span>Expiring</span
              ><strong class="bad">{{
                imm.alerts?.expiring_lots ?? consum.expiring_in_30_days ?? 0
              }}</strong>
            </div>
            <div>
              <span>Quarantine</span
              ><strong>{{
                imm.alerts?.quarantine_lots ?? imm.lots?.quarantine ?? 0
              }}</strong>
            </div>
            <div>
              <span>Expired</span
              ><strong class="bad">{{
                imm.alerts?.expired_lots ?? consum.expired_consumables ?? 0
              }}</strong>
            </div>
            <div>
              <span>Materials</span
              ><strong>{{ imm.materials?.total || 0 }}</strong>
            </div>
          </div>
          <el-button class="wide-btn" @click="go('/consumables-management')"
            >Open consumables <el-icon><ArrowRight /></el-icon
          ></el-button>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from "vue";
import { useRouter } from "vue-router";
import api from "@/utils/axios";
import { useAuthStore } from "@/stores/auth";
import { ElMessage } from "element-plus";
import Chart from "chart.js/auto";
import {
  Refresh,
  User,
  Document,
  Clock,
  CircleCheck,
  Warning,
  Tools,
  Box,
  DataAnalysis,
  TrendCharts,
  Right,
  ArrowRight,
  Calendar,
} from "@element-plus/icons-vue";

const router = useRouter();
const authStore = useAuthStore();

const loadingCore = ref(true);
const refreshing = ref(false);
const periodDays = ref(30);
const todayLabel = new Date().toLocaleDateString("en-US", {
  weekday: "long",
  year: "numeric",
  month: "long",
  day: "numeric",
});

const frontdesk = reactive({
  pending: 0,
  in_progress: 0,
  completed_today: 0,
  total_patients: 0,
});
const phleb = reactive({
  pending_collections: 0,
  collected_today: 0,
  accepted: 0,
  send_out: 0,
  parked: 0,
});
const resultsTotals = reactive({
  total: 0,
  pending: 0,
  processing: 0,
  validated: 0,
  released: 0,
});
const categories = ref([]);
const categoryStats = reactive({});
const tatData = ref([]);
const qcByCategory = reactive({});
const eim = ref({
  equipment: {},
  maintenance: {},
  service: {},
  overdue_items: [],
  upcoming_maintenance: [],
});
const imm = ref({ lots: {}, alerts: {}, materials: {}, financial: {} });
const consum = ref({});
const sigma = reactive({ list: [], excellent: 0, poor: 0, total: 0 });
const census = ref(null);
const recentRequests = ref([]);
const qcRecs = ref([]);
const charts = {};
const workloadCanvas = ref(null);
const censusCanvas = ref(null);
const tatCanvas = ref(null);
const equipCanvas = ref(null);

const cssVar = (n) =>
  (getComputedStyle(document.documentElement).getPropertyValue(n) || "").trim();
const num = (v) => Number(v || 0);
const greeting = computed(() => {
  const h = new Date().getHours();
  return h < 12 ? "Good morning" : h < 18 ? "Good afternoon" : "Good evening";
});
const userName = computed(
  () => authStore?.user?.name || authStore?.getUser?.name || "Team",
);
const totalAccessions = computed(
  () => census.value?.total_accessions?.value ?? resultsTotals.total ?? 0,
);
const accessionDelta = computed(
  () => census.value?.total_accessions?.percentage_change ?? null,
);
const completionRate = computed(() => {
  const t = num(resultsTotals.total);
  return t ? Math.round((num(resultsTotals.released) / t) * 100) : 0;
});
const activeQcHolds = computed(() =>
  Object.values(qcByCategory).reduce((a, s) => a + num(s?.on_hold_count), 0),
);
const inventoryAlerts = computed(
  () =>
    num(imm.value?.alerts?.expiring_lots) +
    num(imm.value?.alerts?.low_stock_lots) +
    num(imm.value?.alerts?.quarantine_lots) +
    num(imm.value?.alerts?.expired_lots),
);
const equipOperational = computed(() =>
  num(eim.value?.equipment?.operational_percentage),
);
const overdueCount = computed(() => (eim.value?.overdue_items || []).length);
const topCategories = computed(() =>
  [...categories.value]
    .map((c) => ({ ...c, stats: categoryStats[c.id] || {} }))
    .sort((a, b) => num(b.stats?.total) - num(a.stats?.total))
    .slice(0, 8),
);
function asList(payload) {
  if (Array.isArray(payload)) return payload;
  if (Array.isArray(payload?.data)) return payload.data;
  if (Array.isArray(payload?.data?.data)) return payload.data.data;
  return [];
}
function destroyChart(k) {
  if (charts[k]) {
    try {
      charts[k].destroy();
    } catch (e) {}
    charts[k] = null;
  }
}
function palette(i) {
  const base = [
    cssVar("--color-primary") || "#0666d4",
    cssVar("--color-success") || "#10B981",
    cssVar("--color-warning") || "#FF8E53",
    cssVar("--color-danger") || "#FF6B6B",
    cssVar("--color-info") || "#0666d4",
    "#8b5cf6",
    "#06b6d4",
    "#f59e0b",
  ];
  return base[i % base.length];
}
function go(path) {
  router.push(path);
}
const reqStatusType = (s) => {
  const v = String(s || "").toLowerCase();
  if (v.includes("stat")) return "danger";
  if (v.includes("urgent")) return "warning";
  return "info";
};
const tatBadge = (h) => {
  if (h <= 4) return "success";
  if (h <= 24) return "warning";
  return "danger";
};
const dueTag = (d) => {
  if (!d) return "info";
  const days = Math.ceil((new Date(d) - new Date()) / 86400000);
  return days < 0 ? "danger" : days <= 7 ? "warning" : "info";
};
const dueLabel = (d) => {
  if (!d) return "No date";
  const days = Math.ceil((new Date(d) - new Date()) / 86400000);
  return days < 0
    ? `${Math.abs(days)}d overdue`
    : days === 0
      ? "Due today"
      : `Due in ${days}d`;
};
const fmtDate = (d) => {
  if (!d) return "—";
  try {
    return new Date(d).toLocaleDateString();
  } catch (e) {
    return String(d);
  }
};
async function safeGet(url, params) {
  try {
    const r = await api.get(url, params ? { params } : undefined);
    return r.data;
  } catch (e) {
    return null;
  }
}
async function loadCore() {
  const [fd, pb, stats, tat, cats] = await Promise.all([
    safeGet("/dashboard/frontdesk-stats"),
    safeGet("/dashboard/phlebotomy-stats"),
    safeGet("/results/dashboard/stats"),
    safeGet("/results/dashboard/tat"),
    safeGet("/test-categories"),
  ]);
  if (fd) Object.assign(frontdesk, fd);
  if (pb) Object.assign(phleb, pb);
  const catList = asList(cats);
  categories.value = catList.map((c) => ({
    id: c.id,
    name: c.name,
    color: c.color || null,
  }));
  if (stats && typeof stats === "object") {
    Object.keys(stats).forEach((k) => {
      categoryStats[k] = stats[k];
    });
    let t = { total: 0, pending: 0, processing: 0, validated: 0, released: 0 };
    Object.values(stats).forEach((s) => {
      t.total += num(s.total);
      t.pending += num(s.pending);
      t.processing += num(s.processing);
      t.validated += num(s.validated);
      t.released += num(s.released);
    });
    Object.assign(resultsTotals, t);
  } else {
    categories.value.forEach((c) => {
      if (!categoryStats[c.id])
        categoryStats[c.id] = {
          total: 0,
          pending: 0,
          processing: 0,
          validated: 0,
          released: 0,
        };
    });
  }
  tatData.value = Array.isArray(tat) ? tat : [];
}
async function loadQuality() {
  const [sig, recs] = await Promise.all([
    safeGet("/sigma-metrics", { period_days: periodDays.value }),
    (async () =>
      (await safeGet("/quality-controls/qc-recommendations")) ||
      (await safeGet("/quality-controls/recommendations")))(),
  ]);
  const rows = Array.isArray(sig?.data) ? sig.data : asList(sig);
  sigma.list = rows.slice(0, 8);
  sigma.total = sig?.total_configs ?? rows.length;
  sigma.excellent = rows.filter((r) => num(r.sigma_metric) >= 6).length;
  sigma.poor = rows.filter(
    (r) => r.sigma_metric != null && num(r.sigma_metric) < 4,
  ).length;
  qcRecs.value = asList(recs).slice(0, 4);
  if (categories.value.length) {
    const sums = await Promise.all(
      categories.value.map((c) =>
        safeGet("/qc-lockout/status-summary", { test_category_id: c.id })
          .then((d) => ({ id: c.id, d }))
          .catch(() => ({ id: c.id, d: null })),
      ),
    );
    sums.forEach((s) => {
      if (s.d) qcByCategory[s.id] = s.d;
    });
  }
}
async function loadOps() {
  const [e, im, cs, cen] = await Promise.all([
    safeGet("/eim/dashboard"),
    safeGet("/eim/imm/dashboard"),
    safeGet("/eim/consumables/stats/dashboard"),
    safeGet("/management-report/census"),
  ]);
  if (e)
    eim.value = {
      equipment: e.equipment || {},
      maintenance: e.maintenance || {},
      service: e.service || {},
      overdue_items: e.overdue_items || [],
      upcoming_maintenance: e.upcoming_maintenance || [],
    };
  if (im) imm.value = im;
  if (cs) consum.value = cs;
  if (cen) census.value = cen;
  const recent = await safeGet("/laboratory-requests", { per_page: 6 });
  recentRequests.value = asList(recent).slice(0, 6);
}
function shortLabel(name, max = 14) {
  const s = String(name || "");
  if (s.length <= max) return s;
  const words = s.split(/[\s/]+/).filter(Boolean);
  if (words.length > 1) return words.map((w) => w.slice(0, 4)).join(" ");
  return `${s.slice(0, max - 1)}…`;
}
function renderCharts() {
  destroyChart("workload");
  destroyChart("census");
  destroyChart("tat");
  destroyChart("equip");
  const grid =
    getComputedStyle(document.documentElement)
      .getPropertyValue("--color-border-secondary")
      .trim() || "#e8eef5";
  const muted = cssVar("--color-text-tertiary") || "#909399";
  const baseOpts = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { labels: { boxWidth: 12, font: { size: 11 } } } },
    scales: {
      x: { grid: { color: grid }, ticks: { color: muted, font: { size: 10 } } },
      y: {
        beginAtZero: true,
        grid: { color: grid },
        ticks: { color: muted, font: { size: 10 }, precision: 0 },
      },
    },
  };
  if (workloadCanvas.value && categories.value.length) {
    const top = topCategories.value.slice(0, 6);
    charts.workload = new Chart(workloadCanvas.value.getContext("2d"), {
      type: "bar",
      data: {
        labels: top.map((c) => shortLabel(c.name)),
        datasets: [
          {
            label: "Pending",
            data: top.map((c) => num(c.stats?.pending)),
            backgroundColor: cssVar("--color-warning") || "#FF8E53",
          },
          {
            label: "Processing",
            data: top.map((c) => num(c.stats?.processing)),
            backgroundColor: cssVar("--color-primary") || "#0666d4",
          },
          {
            label: "Validated",
            data: top.map((c) => num(c.stats?.validated)),
            backgroundColor: cssVar("--color-success") || "#10B981",
          },
        ],
      },
      options: {
        ...baseOpts,
        scales: {
          ...baseOpts.scales,
          x: {
            ...baseOpts.scales.x,
            stacked: true,
            ticks: {
              ...baseOpts.scales.x.ticks,
              maxRotation: 35,
              minRotation: 35,
              autoSkip: false,
              font: { size: 10 },
            },
          },
          y: { ...baseOpts.scales.y, stacked: true },
        },
        plugins: {
          ...baseOpts.plugins,
          tooltip: {
            callbacks: {
              title: (items) =>
                top[items[0]?.dataIndex]?.name || items[0]?.label || "",
            },
          },
        },
      },
    });
  }
  if (censusCanvas.value && census.value?.comparison_data) {
    const cd = census.value.comparison_data;
    charts.census = new Chart(censusCanvas.value.getContext("2d"), {
      type: "line",
      data: {
        labels: cd.labels || [],
        datasets: [
          {
            label: "In-house",
            data: cd.processed || [],
            borderColor: cssVar("--color-primary") || "#0666d4",
            backgroundColor: "rgba(6,102,212,.12)",
            fill: true,
            tension: 0.35,
            pointRadius: 2,
          },
          {
            label: "Send-out",
            data: cd.sendOut || [],
            borderColor: cssVar("--color-warning") || "#FF8E53",
            tension: 0.35,
            pointRadius: 2,
          },
        ],
      },
      options: { ...baseOpts },
    });
  }
  if (tatCanvas.value && tatData.value.length) {
    const top = [...tatData.value]
      .sort((a, b) => num(b.avg_tat) - num(a.avg_tat))
      .slice(0, 6);
    charts.tat = new Chart(tatCanvas.value.getContext("2d"), {
      type: "bar",
      data: {
        labels: top.map((t) => t.category_name),
        datasets: [
          {
            label: "Avg TAT (hrs)",
            data: top.map((t) => num(t.avg_tat)),
            backgroundColor: top.map((_, i) => palette(i)),
            borderRadius: 6,
          },
        ],
      },
      options: { ...baseOpts, indexAxis: "y" },
    });
  }
  if (equipCanvas.value && eim.value?.equipment?.status_breakdown) {
    const sb = eim.value.equipment.status_breakdown || {};
    charts.equip = new Chart(equipCanvas.value.getContext("2d"), {
      type: "doughnut",
      data: {
        labels: Object.keys(sb).map((k) => k.replace(/_/g, " ")),
        datasets: [
          {
            data: Object.values(sb).map((v) => num(v)),
            backgroundColor: Object.keys(sb).map((_, i) => palette(i)),
            borderWidth: 2,
            borderColor: "#fff",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "62%",
        plugins: {
          legend: {
            position: "bottom",
            labels: { boxWidth: 12, font: { size: 11 } },
          },
        },
      },
    });
  }
}
async function loadAll(isRefresh = false) {
  if (isRefresh) refreshing.value = true;
  loadingCore.value = true;
  // Minimum loading display time so the skeleton state is always perceived
  const minLoadingTime = new Promise((resolve) => setTimeout(resolve, 800));
  try {
    await Promise.all([
      loadCore(),
      Promise.all([loadQuality(), loadOps()]),
      minLoadingTime,
    ]);
    await nextTick();
    renderCharts();
  } catch (e) {
    ElMessage.error("Failed to load dashboard data");
  } finally {
    loadingCore.value = false;
    refreshing.value = false;
  }
}
onMounted(() => loadAll(false));
</script>

<style scoped>
.ops-dashboard {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: 4px 2px 24px;
  position: relative;
  min-height: calc(
    100vh - 200px
  ); /* Keep page height stable while panels populate */
}
.dash-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px 16px;
  flex-wrap: wrap;
  background: var(--color-bg-primary);
  border: 1px solid var(--color-border-secondary);
  border-radius: 12px;
  padding: 14px 18px;
  box-shadow: var(--shadow-sm);
}
.dash-title-block {
  min-width: 0;
  flex: 1 1 320px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  text-align: left;
}
.dash-title-row {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  width: 100%;
  justify-content: flex-start;
}
.dash-title {
  margin: 0;
  font-size: 22px;
  font-weight: 800;
  color: var(--color-text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-align: left;
}
.live-dot {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 700;
  color: var(--color-success-dark);
  background: var(--color-success-light);
  border-radius: 20px;
  padding: 3px 10px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  flex-shrink: 0;
}
.live-dot i {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--color-success);
  animation: pulse 1.6s ease-in-out infinite;
}
@keyframes pulse {
  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.45;
    transform: scale(0.8);
  }
}
.dash-sub {
  margin: 4px 0 0;
  font-size: 12px;
  color: var(--color-text-secondary);
  text-align: left;
  align-self: flex-start;
  width: 100%;
}
.dash-header-actions {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-shrink: 0;
}
.alert-pills {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  flex-basis: 100%;
  padding-top: 10px;
  margin-top: 4px;
  border-top: 1px dashed var(--color-border-secondary);
}
.alert-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  padding: 5px 12px;
  border-radius: 20px;
  cursor: pointer;
  transition:
    transform 0.15s ease,
    box-shadow 0.15s ease;
}
.alert-pill:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-sm);
}
.alert-error {
  color: var(--color-danger-dark);
  background: var(--color-danger-light);
}
.alert-warn {
  color: var(--color-warning-dark);
  background: var(--color-warning-light);
}
.alert-ok {
  color: var(--color-success-dark);
  background: var(--color-success-light);
  cursor: default;
}
.kpi-row .el-col {
  margin-bottom: 16px;
  display: flex;
}
.kpi-card {
  cursor: pointer;
  border-radius: 10px !important;
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 168px;
}
.kpi-card :deep(.el-card__body) {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 2px;
}
.kpi-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}
.kpi-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  color: #fff;
}
.kpi-primary {
  background: var(--color-primary);
}
.kpi-success {
  background: var(--color-success);
}
.kpi-warning {
  background: var(--color-warning);
}
.kpi-danger {
  background: var(--color-danger);
}
.kpi-value {
  font-size: 30px;
  font-weight: 800;
  color: var(--color-text-primary);
  line-height: 1;
}
.kpi-value small {
  font-size: 14px;
  color: var(--color-text-tertiary);
  font-weight: 600;
}
.kpi-label {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin: 4px 0;
}
.kpi-foot {
  font-size: 11px;
  color: var(--color-text-tertiary);
}
.kpi-delta {
  font-size: 11px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 20px;
  background: var(--color-bg-secondary);
}
.kpi-delta.up {
  color: var(--color-success-dark);
  background: var(--color-success-light);
}
.kpi-delta.down {
  color: var(--color-danger-dark);
  background: var(--color-danger-light);
}
.kpi-pill {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 20px;
  background: var(--color-bg-secondary);
  color: var(--color-text-secondary);
}
.kpi-pill.bad {
  color: var(--color-danger-dark);
  background: var(--color-danger-light);
}
.section-row {
  margin-top: 4px;
}
.section-row .el-col {
  margin-bottom: 16px;
  display: flex;
}
.section-row .panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  width: 100%;
}
.section-row .panel :deep(.el-card__body) {
  flex: 1;
  display: flex;
  flex-direction: column;
}
.panel-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 700;
  font-size: 13px;
}
.panel-head span {
  display: flex;
  align-items: center;
  gap: 8px;
}
.chart-box {
  height: 250px;
  position: relative;
  flex-shrink: 0;
}
.chart-box.tall {
  height: 280px;
}
.chart-box.donut {
  height: 190px;
}
.cat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 8px;
  margin-top: 12px;
  align-content: start;
}
.cat-chip {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 8px 10px;
  border: 1px solid var(--color-border-secondary);
  border-radius: 8px;
  background: var(--color-bg-primary);
  cursor: pointer;
  font-size: 12px;
  min-height: 58px;
  justify-content: center;
}
.cat-chip:hover {
  border-color: var(--color-primary);
  box-shadow: var(--shadow-sm);
}
.cat-chip-top {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}
.cat-chip .dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}
.cat-name {
  font-weight: 700;
  color: var(--color-text-primary);
  flex: 1;
  min-width: 0;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  white-space: normal;
  word-break: break-word;
  line-height: 1.3;
}
.cat-nums {
  color: var(--color-text-tertiary);
  font-size: 11px;
  white-space: nowrap;
}
.quick-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}
.quick-grid .el-button {
  justify-content: flex-start;
}
.mini-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}
.mini-stats div {
  background: var(--color-bg-secondary);
  border-radius: 8px;
  padding: 8px 10px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.mini-stats span {
  font-size: 11px;
  color: var(--color-text-tertiary);
}
.mini-stats strong {
  font-size: 16px;
  color: var(--color-text-primary);
}
.census-kpis {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  margin-bottom: 10px;
}
.census-kpis div {
  background: var(--color-bg-secondary);
  border-radius: 8px;
  padding: 8px 10px;
  display: flex;
  flex-direction: column;
}
.census-kpis span {
  font-size: 11px;
  color: var(--color-text-tertiary);
}
.census-kpis strong {
  font-size: 18px;
}
.tat-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-top: 10px;
}
.tat-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12px;
  padding: 6px 8px;
  border: 1px solid var(--color-border-secondary);
  border-radius: 8px;
}
.rec-list {
  margin-top: 12px;
  border-top: 1px solid var(--color-border-secondary);
  padding-top: 10px;
}
.rec-title {
  font-size: 12px;
  font-weight: 700;
  margin: 0 0 8px;
  color: var(--color-text-primary);
}
.rec-item {
  display: flex;
  gap: 8px;
  align-items: flex-start;
  font-size: 12px;
  color: var(--color-text-secondary);
  padding: 6px 0;
}
.rec-item .el-icon {
  color: var(--color-warning);
  margin-top: 2px;
}
.equip-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  margin-bottom: 10px;
}
.equip-grid.three {
  grid-template-columns: repeat(3, 1fr);
}
.equip-grid div {
  background: var(--color-bg-secondary);
  border-radius: 8px;
  padding: 8px 10px;
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.equip-body {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}
.equip-top {
  display: flex;
  gap: 16px;
  align-items: center;
  min-width: 0;
}
.equip-donut-wrap {
  flex: 0 0 220px;
  width: 220px;
  max-width: 220px;
  min-width: 0;
}
.equip-grid.four {
  flex: 1;
  min-width: 0;
  grid-template-columns: 1fr 1fr;
  align-content: center;
  margin-bottom: 0;
}
.list-count {
  color: var(--color-text-tertiary);
  font-weight: 600;
}
.overdue-list {
  display: flex;
  flex-direction: column;
}
.overdue-name {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.equip-grid span {
  font-size: 11px;
  color: var(--color-text-tertiary);
}
.equip-grid strong {
  font-size: 18px;
}
.equip-grid strong.ok {
  color: var(--color-success);
}
.equip-grid strong.warn {
  color: var(--color-warning-dark);
}
.equip-grid strong.bad {
  color: var(--color-danger);
}
.equip-rate {
  text-align: center;
  margin-top: 6px;
}
.equip-rate strong {
  display: block;
  font-size: 22px;
}
.equip-rate span {
  font-size: 11px;
  color: var(--color-text-tertiary);
}
.list-title {
  font-size: 12px;
  font-weight: 700;
  margin: 8px 0;
}
.overdue-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  padding: 6px 0;
  border-bottom: 1px solid var(--color-border-light);
}
.wide-btn {
  width: 100%;
  margin-top: 12px;
}

/* Per-KPI card skeleton loading */
.kpi-skeleton {
  display: flex;
  flex-direction: column;
  gap: 14px;
  width: 100%;
}
.skeleton-row {
  display: flex;
  align-items: center;
  gap: 10px;
}
.skeleton-row:first-child {
  justify-content: space-between;
}
.skeleton-row > div,
.sk {
  background: linear-gradient(
    90deg,
    var(--color-bg-secondary) 25%,
    var(--color-border-secondary) 37%,
    var(--color-bg-secondary) 63%
  );
  background-size: 400% 100%;
  animation: skeleton-shimmer 1.4s ease infinite;
  border-radius: 6px;
}
.sk-chip {
  display: flex;
  flex-direction: column;
  gap: 8px;
  justify-content: center;
  padding: 8px 10px;
  border: 1px solid var(--color-border-secondary);
  border-radius: 8px;
  background: var(--color-bg-primary);
  min-height: 58px;
}
.sk-chip .sk {
  height: 10px;
}
.w70 {
  width: 70%;
}
.w45 {
  width: 45%;
}
.sk-tat {
  height: 30px;
  width: 100%;
}
.bar-sm {
  display: block;
  height: 9px;
}
.bar-md {
  display: block;
  height: 15px;
}
.sk-tag {
  display: block;
  height: 20px;
  width: 64px;
  flex-shrink: 0;
}
/* Chart-box shimmer overlay: covers canvas while loading, charts render beneath */
.sk-chart {
  position: absolute;
  inset: 0;
  z-index: 2;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 12px;
  padding: 20px 16px 24px;
  background: var(--color-bg-primary);
  border-radius: 4px;
}
.sk-chart .sk {
  flex: 1;
  border-radius: 6px 6px 0 0;
}
.sk-chart .sk:nth-child(1) { height: 55%; }
.sk-chart .sk:nth-child(2) { height: 80%; }
.sk-chart .sk:nth-child(3) { height: 45%; }
.sk-chart .sk:nth-child(4) { height: 90%; }
.sk-chart .sk:nth-child(5) { height: 65%; }
.sk-chart .sk:nth-child(6) { height: 75%; }
.sk-donut {
  position: absolute;
  inset: 0;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg-primary);
  border-radius: 4px;
}
.sk-donut .sk {
  width: 150px;
  height: 150px;
  border-radius: 50%;
}
.sk-rate {
  display: block;
  width: 72px;
  height: 22px;
  margin: 0 auto;
}
.sk-table {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding: 14px 8px;
}
.sk-table .sk {
  display: block;
  height: 14px;
  width: 100%;
}
.skeleton-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  flex-shrink: 0;
}
.skeleton-text {
  width: 74px;
  height: 14px;
}
.skeleton-value {
  width: 96px;
  height: 28px;
}
.skeleton-label {
  width: 55%;
  height: 12px;
}
.skeleton-foot {
  width: 42%;
  height: 10px;
}
@keyframes skeleton-shimmer {
  0% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0 50%;
  }
}

@media (max-width: 768px) {
  .dash-title {
    font-size: 20px;
  }
  .quick-grid {
    grid-template-columns: 1fr;
  }
  .equip-grid.three {
    grid-template-columns: 1fr 1fr;
  }
  .equip-top {
    flex-direction: column;
    align-items: stretch;
  }
  .equip-donut-wrap {
    flex: none;
    width: 100%;
    max-width: 280px;
    margin: 0 auto;
  }
}
</style>
