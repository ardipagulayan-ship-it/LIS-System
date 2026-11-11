<template>
  <div class="app-wrapper" v-if="authStore.isLoggedIn">
    <!-- Sidebar -->
    <div class="sidebar" :class="{ collapsed: isCollapse }">
      <div class="logo">
        <img src="./assets/pulse_logo.png" alt="LIS" class="logo-image" />
        <h2>{{ isCollapse ? "" : "SBSI LIS" }}</h2>
      </div>
      <el-menu
        :default-active="activeMenu"
        class="el-menu-vertical"
        :router="true"
        :collapse="isCollapse"
      >
        <el-menu-item index="/">
          <el-icon><Monitor /></el-icon>
          <template #title>Home</template>
        </el-menu-item>

        <el-menu-item index="/frontdesk">
          <el-icon><Service /></el-icon>
          <template #title>Front Desk</template>
        </el-menu-item>

        <el-menu-item index="/phlebotomy">
          <el-icon><FirstAidKit /></el-icon>
          <span>Phlebotomy</span>
        </el-menu-item>

        <el-menu-item index="/order-management">
          <el-icon><Collection /></el-icon>
          <span>Order Management</span>
        </el-menu-item>

        <el-menu-item index="/result-management/dashboard">
          <el-icon><Document /></el-icon>
          <template #title>Result Management</template>
        </el-menu-item>

        <el-menu-item index="/patients">
          <el-icon><User /></el-icon>
          <template #title>Patients</template>
        </el-menu-item>

        <el-sub-menu index="management-report">
          <template #title>
            <el-icon><Document /></el-icon>
            <span>Management Report</span>
          </template>
          <el-menu-item index="/management-report/worksheet">
            <el-icon><List /></el-icon>
            <template #title>Worksheet</template>
          </el-menu-item>
          <el-menu-item index="/management-report/census">
            <el-icon><DataAnalysis /></el-icon>
            <template #title>Census</template>
          </el-menu-item>
          <el-menu-item index="/management-report/turnaround-time">
            <el-icon><Timer /></el-icon>
            <template #title>Turn Around Time</template>
          </el-menu-item>
          <el-menu-item index="/management-report/delta-check">
            <el-icon><DataLine /></el-icon>
            <template #title>Delta Check Report</template>
          </el-menu-item>
          <el-menu-item index="/quality-control">
            <el-icon><DataAnalysis /></el-icon>
            <template #title>Quality Control</template>
          </el-menu-item>
        </el-sub-menu>

        <el-sub-menu index="equipment-management">
          <template #title>
            <el-icon><Tools /></el-icon>
            <span>Equipment Management</span>
          </template>
          <el-menu-item index="/equipment-management">
            <el-icon><Tools /></el-icon>
            <template #title>Dashboard</template>
          </el-menu-item>
          <el-menu-item index="/equipment-management/equipment">
            <el-icon><Tools /></el-icon>
            <template #title>Equipment Management</template>
          </el-menu-item>
          <el-menu-item index="/equipment-management/maintenance-schedules">
            <el-icon><Tools /></el-icon>
            <template #title>Maintenance Schedules</template>
          </el-menu-item>
          <el-menu-item index="/equipment-management/service-logs">
            <el-icon><Tools /></el-icon>
            <template #title>Service Logs</template>
          </el-menu-item>
        </el-sub-menu>

        <!-- <el-sub-menu index="consumables-management">
          <template #title>
            <el-icon><Box /></el-icon>
            <span>Consumables & Reagents</span>
          </template>
          <el-menu-item index="/consumables-management">
            <el-icon><DataAnalysis /></el-icon>
            <template #title>Dashboard</template>
          </el-menu-item>
          <el-menu-item index="/consumables-management/list">
            <el-icon><Box /></el-icon>
            <template #title>Consumables & Reagents</template>
          </el-menu-item>
        </el-sub-menu> -->
        <el-sub-menu index="inventory-management">
          <template #title>
            <el-icon><Collection /></el-icon>
            <span>Inventory Management</span>
          </template>
          <el-menu-item index="/inventory-management">
            <el-icon><DataAnalysis /></el-icon>
            <template #title>Dashboard</template>
          </el-menu-item>
          <el-menu-item index="/inventory-management/materials">
            <el-icon><Box /></el-icon>
            <template #title>Material Master</template>
          </el-menu-item>
          <el-menu-item index="/inventory-management/lots">
            <el-icon><Collection /></el-icon>
            <template #title>Lot Management</template>
          </el-menu-item>
          <el-menu-item index="/inventory-management/vendors">
            <el-icon><Shop /></el-icon>
            <template #title>Vendor Management</template>
          </el-menu-item>
          <el-menu-item index="/inventory-management/reports">
            <el-icon><DataAnalysis /></el-icon>
            <template #title>Reports</template>
          </el-menu-item>
          <el-menu-item index="/inventory-management/equipment-usage">
            <el-icon><Operation /></el-icon>
            <template #title>Equipment Usage</template>
          </el-menu-item>
        </el-sub-menu>

        <el-menu-item index="/doctors" style="display: none">
          <el-icon><UserFilled /></el-icon>
          <template #title>Doctors</template>
        </el-menu-item>

        <el-menu-item index="/test-categories" style="display: none">
          <el-icon><Folder /></el-icon>
          <template #title>Test Categories</template>
        </el-menu-item>

        <el-menu-item index="/tests" style="display: none">
          <el-icon><Document /></el-icon>
          <template #title>Tests</template>
        </el-menu-item>

        <el-menu-item index="/test-results" style="display: none">
          <el-icon><DataLine /></el-icon>
          <template #title>Test Results</template>
        </el-menu-item>

        <el-menu-item index="/payments" style="display: none">
          <el-icon><Money /></el-icon>
          <template #title>Payments</template>
        </el-menu-item>
      </el-menu>

      <!-- Settings Footer -->
      <div class="sidebar-footer">
        <el-menu class="el-menu-vertical" :router="true" :collapse="isCollapse">
          <!-- Settings -->
          <el-sub-menu index="setup">
            <template #title>
              <el-icon><Menu /></el-icon>
              <span>Setup</span>
            </template>
            <el-menu-item index="/setup/test-categories">
              <el-icon><Tools /></el-icon>
              <template #title>Test Categories</template>
            </el-menu-item>
            <el-menu-item index="/setup/tests">
              <el-icon><List /></el-icon>
              <template #title>Tests</template>
            </el-menu-item>
            <el-menu-item index="/setup/reference-ranges">
              <el-icon><SetUp /></el-icon>
              <template #title>Reference Ranges</template>
            </el-menu-item>
          </el-sub-menu>
          <!-- Settings -->
          <el-sub-menu index="settings">
            <template #title>
              <el-icon><Setting /></el-icon>
              <span>Settings</span>
            </template>
            <el-menu-item index="/settings/general">
              <el-icon><Tools /></el-icon>
              <template #title>General Settings</template>
            </el-menu-item>
            <el-menu-item index="/settings/rooms">
              <el-icon><List /></el-icon>
              <template #title>Rooms</template>
            </el-menu-item>
            <el-menu-item index="/settings/wards">
              <el-icon><Files /></el-icon>
              <template #title>Wards</template>
            </el-menu-item>
            <el-menu-item index="/settings/doctors">
              <el-icon><SetUp /></el-icon>
              <template #title>Doctors</template>
            </el-menu-item>
            <el-menu-item index="/settings/users">
              <el-icon><UserFilled /></el-icon>
              <template #title>Analyzers</template>
            </el-menu-item>
            <el-menu-item index="/settings/users">
              <el-icon><UserFilled /></el-icon>
              <template #title>User Management</template>
            </el-menu-item>
            <el-menu-item index="/admin/cds-rules">
              <el-icon><SetUp /></el-icon>
              <template #title>CDS Rules</template>
            </el-menu-item>
            <el-menu-item index="/admin/delta-check-qc-rules">
              <el-icon><SetUp /></el-icon>
              <template #title>Delta Check & QC Rules</template>
            </el-menu-item>
            <el-menu-item index="/admin/formulas">
              <el-icon><SetUp /></el-icon>
              <template #title>Test Formulas</template>
            </el-menu-item>
            <el-menu-item index="/admin/report-templates">
              <el-icon><DocumentCopy /></el-icon>
              <template #title>Report Templates</template>
            </el-menu-item>
            <el-menu-item index="/admin/report-layouts">
              <el-icon><SetUp /></el-icon>
              <template #title>Result Builder</template>
            </el-menu-item>
            <el-menu-item index="/admin/report-groups">
              <el-icon><SetUp /></el-icon>
              <template #title>Result Builders</template>
            </el-menu-item>
            <el-menu-item index="/admin/lis-bridge">
              <el-icon><Connection /></el-icon>
              <template #title>LIS Bridge Config</template>
            </el-menu-item>
          </el-sub-menu>

          <!-- System Config -->
          <el-sub-menu index="config">
            <template #title>
              <el-icon><Operation /></el-icon>
              <span>System Config</span>
            </template>
            <el-menu-item index="/settings/system">
              <el-icon><Operation /></el-icon>
              <template #title>System Config</template>
            </el-menu-item>
            <el-menu-item index="/settings/backup">
              <el-icon><Download /></el-icon>
              <template #title>Backup & Restore</template>
            </el-menu-item>
          </el-sub-menu>
        </el-menu>
      </div>
    </div>

    <!-- Main Content -->
    <div class="main">
      <!-- Header -->
      <div class="header">
        <div class="header-left">
          <el-button type="text" class="sidebar-toggle" @click="toggleSidebar">
            <el-icon :size="20">
              <component :is="isCollapse ? 'Expand' : 'Fold'" />
            </el-icon>
          </el-button>
          <!-- Breadcrumb Navigation -->
          <nav class="breadcrumb">
            <template v-if="route.name === 'result-category'">
              <span class="breadcrumb__item">
                <router-link to="/" class="breadcrumb__link">Home</router-link>
              </span>
              <span class="breadcrumb__separator">/</span>
              <span class="breadcrumb__item">
                <router-link
                  to="/result-management/dashboard"
                  class="breadcrumb__link"
                  >Result Dashboard</router-link
                >
              </span>
              <span class="breadcrumb__separator">/</span>
              <span class="breadcrumb__item breadcrumb__item--active">{{
                categoryBreadcrumbName
              }}</span>
            </template>
            <template v-else>
              <span class="breadcrumb__item">
                <router-link to="/" class="breadcrumb__link">Home</router-link>
              </span>
              <span class="breadcrumb__separator">/</span>
              <span class="breadcrumb__item breadcrumb__item--active">{{
                currentPageName
              }}</span>
            </template>
          </nav>
        </div>
        <div class="header-right">
          <span class="notifications">
            <el-icon><Bell /></el-icon>
          </span>
          <el-dropdown>
            <span class="user-dropdown">
              {{ authStore.user?.name || "Admin" }}
              <el-icon><ArrowDown /></el-icon>
            </span>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item>Profile</el-dropdown-item>
                <el-dropdown-item>Settings</el-dropdown-item>
                <el-dropdown-item divided @click="handleLogout">
                  Logout
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </div>

      <!-- Page Content -->
      <div class="page-content">
        <router-view></router-view>
      </div>
    </div>
  </div>
  <div class="app-wrapper" v-else>
    <!-- Page Content -->
    <div class="page-content-login">
      <router-view></router-view>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import {
  DataLine,
  User,
  UserFilled,
  Folder,
  Document,
  List,
  Check,
  Money,
  Expand,
  Fold,
  ArrowDown,
  Monitor,
  Service,
  Collection,
  Box,
  Setting,
  Tools,
  Operation,
  Download,
  FirstAidKit,
  Files,
  SetUp,
  Menu,
  DataAnalysis,
  Timer,
  DocumentCopy,
  Connection,
  Bell,
} from "@element-plus/icons-vue";

const authStore = useAuthStore();
const route = useRoute();
const isCollapse = ref(false);

// Breadcrumb page names mapping
const pageNames = {
  "/dashboard": "Home",
  "/frontdesk": "Front Desk",
  "/phlebotomy": "Phlebotomy",
  "/order-management": "Order Management",
  "/result-management/dashboard": "Result Dashboard",
  "/patients": "Patients",
  "/management-report/worksheet": "Worksheet",
  "/management-report/census": "Census",
  "/management-report/turnaround-time": "Turn Around Time",
  "/management-report/delta-check": "Delta Check Report",
  "/quality-control": "Quality Control",
  "/equipment-management": "Equipment Management",
  "/equipment-management/equipment": "Equipment Management",
  "/equipment-management/maintenance-schedules": "Maintenance Schedules",
  "/equipment-management/service-logs": "Service Logs",
  "/inventory-management": "Inventory Management",
  "/inventory-management/materials": "Material Master",
  "/inventory-management/lots": "Lot Management",
  "/inventory-management/vendors": "Vendor Management",
  "/inventory-management/reports": "Reports",
  "/inventory-management/equipment-usage": "Equipment Usage",
  "/doctors": "Doctors",
  "/test-categories": "Test Categories",
  "/tests": "Tests",
  "/test-results": "Test Results",
  "/payments": "Payments",
  "/setup/test-categories": "Test Categories",
  "/setup/tests": "Tests",
  "/setup/reference-ranges": "Reference Ranges",
  "/settings/general": "General Settings",
  "/settings/rooms": "Rooms",
  "/settings/wards": "Wards",
  "/settings/doctors": "Doctors",
  "/settings/users": "User Management",
  "/admin/cds-rules": "CDS Rules",
  "/admin/delta-check-qc-rules": "Delta Check & QC Rules",
  "/admin/lis-bridge": "LIS Bridge Config",
  "/settings/system": "System Config",
  "/settings/backup": "Backup & Restore",
};

const currentPageName = computed(() => {
  return pageNames[route.path] || pageNames["/"];
});

// Human-readable category name for the breadcrumb (e.g. "hematology" -> "Hematology", "clinical-chemistry" -> "Clinical Chemistry")
const categoryBreadcrumbName = computed(() => {
  const raw = String(route.params.categoryName || "")
    .replace(/-/g, " ")
    .trim();
  if (!raw) return "";
  return raw
    .split(/\s+/)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join(" ");
});

const activeMenu = computed(() => {
  return route.path;
});

const toggleSidebar = () => {
  isCollapse.value = !isCollapse.value;
};

const handleLogout = async () => {
  await authStore.logout();
  router.push("/login");
};
</script>

<style>
@import "element-plus/dist/index.css";

html,
body {
  margin: 0;
  padding: 0;
  height: 100%;
  width: 100%;
}

#app {
  height: 100%;
  width: 100%;
  margin: 0 auto;
  padding: 0 auto;
  text-align: center;
}

* {
  box-sizing: border-box;
}

.app-wrapper {
  display: flex;
  width: 100%;
  position: relative;
  /* background-color: var(--color-bg-secondary); */
  background-color: var(--color-bg-primary);
}

.sidebar {
  width: 250px;
  background-color: var(--color-bg-primary);
  transition: width 0.3s;
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  z-index: 1000;
  overflow-x: hidden;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  border-right: 1px solid var(--color-border-secondary);
}

.sidebar.collapsed {
  width: 40px;
}

.main {
  flex: 1;
  margin-left: 250px;
  transition: margin-left 0.3s;
  display: flex;
  flex-direction: column;
}

.sidebar.collapsed + .main {
  margin-left: 40px;
}

.logo {
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--color-bg-primary);
  color: var(--color-text-primary);
  overflow: hidden;
  white-space: nowrap;
}

.logo-image {
  height: 25px;
  margin: 2px;
  padding: 2px;

  border-radius: var(--border-radius-lg);
  background: linear-gradient(
    45deg,
    var(--color-warning),
    var(--color-primary),
    var(--color-success),
    var(--color-warning)
  );
  background-size: 400%;
  z-index: 0;
  border-radius: 4px;
  opacity: 1;
  animation: steam 5s linear infinite;
}

@keyframes steam {
  0% {
    background-position: 0% 0%;
  }
  50% {
    background-position: 400% 0%;
  }
  100% {
    background-position: 0% 0%;
  }

  /* 1. First Pulse (0s - 0.5s) */
  0% {
    transform: scale(1);
  }
  5% {
    transform: scale(1.15);
  }
  10% {
    transform: scale(1);
  }

  /* 2. Pause for 1 second (0.5s - 1.5s) */
  20% {
    transform: scale(1);
  }

  /* 3. Two Rapid Pulses (1.5s - 2.0s) */
  30% {
    transform: scale(1.15);
  }
  35% {
    transform: scale(1);
  }
  40% {
    transform: scale(1.15);
  }
  50% {
    transform: scale(1);
  }

  /* 4. Pause for 2 seconds (2.0s - 4.0s) */
  100% {
    transform: scale(1);
  }
}

.logo h2 {
  margin: 0;
  font-size: 18px;
  font-weight: var(--font-weight-bold);
  font-family: var(--font-family-base-custom);
}

.header {
  height: 36px;
  background: var(--color-bg-primary);
  border-bottom: 1px solid var(--color-border-secondary);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 var(--spacing-xl);
  position: sticky;
  top: 0;
  z-index: 999;
}

.header-left {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
}

/* Breadcrumb Navigation */
.breadcrumb {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  font-size: var(--font-size-sm);
  padding-left: var(--spacing-sm);
  border-left: 1px solid var(--color-border-secondary);
}

.breadcrumb__item {
  color: var(--color-text-secondary);
  display: flex;
  align-items: center;
}

.breadcrumb__item--active {
  color: var(--color-text-primary);
  font-weight: var(--font-weight-bold);
}

.breadcrumb__link {
  color: var(--color-text-primary);
  text-decoration: none;
  transition: color var(--transition-fast);
  cursor: pointer;
}

.breadcrumb__link:hover {
  color: var(--color-primary);
  text-decoration: underline;
}

.breadcrumb__separator {
  color: var(--color-border-primary);
  margin: 0 var(--spacing-xs);
}

.header-right {
  display: flex;
  align-items: center;
}

.user-dropdown {
  display: flex;
  align-items: center;
  cursor: pointer;
  color: var(--color-text-primary);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-bold);
}

.user-dropdown .el-icon {
  margin-left: var(--spacing-xs);
}

.notifications {
  display: flex;
  align-items: center;
  cursor: pointer;
  color: var(--color-text-primary);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-bold);
}

.page-content {
  flex: 1;
  padding: var(--spacing-xl);
  background-color: var(--color-bg-secondary);
  min-height: calc(100vh - 60px);
}

.page-content-login {
  flex-grow: 1;
}

.page-title {
  color: var(--color-text-primary);
  font-size: var(--font-size-2xl, 24px);
  font-weight: var(--font-weight-semibold);
  margin-bottom: var(--spacing-xl);
}

/* Ensure the menu takes full height */
.el-menu {
  flex: 1;
  overflow-y: auto;
  padding: 0px 0px;
}

.el-menu-vertical {
  --el-menu-bg-color: var(--color-light);
  --el-menu-active-color: var(--color-bg-secondary);
  --el-menu-text-color: var(--color-text-secondary);
  --el-menu-hover-bg-color: var(--color-bg-secondary);
  --el-menu-item-font-size: var(--font-size-sm);
  --el-menu-base-level-padding: var(--spacing-sm);
  --el-menu-item-height: 45px;
  --el-menu-sub-item-height: 45px;
}

.el-menu-vertical:not(.el-menu--collapse) {
  width: 250px;
  min-height: 400px;
}

.el-menu-item.is-active {
  background-color: var(--color-primary-light) !important;
  color: var(--color-text-primary);
  font-weight: 700;
  border-left: 4px solid var(--color-primary);
  font-size: var(--font-size-sm);
}

.el-menu-item:hover {
  background-color: var(--color-primary-light) !important;
  color: var(--color-text-primary);
  font-weight: 700;
  border-left: 4px solid var(--color-primary);
}

.sidebar-toggle {
  font-size: 2cqmax;
  cursor: pointer;
  color: var(--color-dark);
}

.sidebar-toggle:hover {
  font-size: 20px;
  cursor: pointer;
  color: var(--color-primary);
}

.sidebar-footer {
  position: relative;
  width: 100%;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  background-color: var(--color-bg-primary) !important;
}

.sidebar-footer .el-menu {
  border-right: none !important;
  height: auto;
  min-height: auto;
}

.sidebar-footer .el-sub-menu__title {
  border-bottom: none;
}

.sidebar-footer .el-menu--inline {
  background-color: var(--color-bg-primary) !important;
}

/* Responsive styles */
@media screen and (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
  }

  .sidebar.collapsed {
    transform: translateX(-100%);
  }

  .main {
    margin-left: 0 !important;
  }

  .page-content {
    padding: 16px;
  }
}
</style>
