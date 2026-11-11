import { createRouter, createWebHistory } from 'vue-router';
import MainLayout from '../layouts/MainLayout.vue';
import Login from '../views/Login.vue';
import Dashboard from '../views/Dashboard.vue';
import Patients from '../views/Patients.vue';
import Doctors from '../views/Doctors.vue';
import TestCategories from '../views/TestCategories.vue';
import Tests from '../views/Tests.vue';
import TestResults from '../views/TestResults.vue';
import Payments from '../views/Payments.vue';
import Frontdesk from '@/views/Frontdesk.vue';
import Phlebotomy from '@/views/Phlebotomy.vue';
import OrderManagement from '@/views/OrderManagement.vue';
import ResultManagementDashboard from '@/views/ResultManagement/ResultManagementDashboard.vue';
import CategoryResults from '@/views/ResultManagement/CategoryResults.vue';
const QCLog = () => import('@/views/ResultManagement/QCLog.vue');
import ReferenceRanges from '@/views/ReferenceRangeManagement.vue';
import QualityControlDashboard from '@/views/QualityControl/QualityControlDashboard.vue';
import QualityControl from '@/views/QualityControl/QualityControlList.vue';
import QualityControls from '@/views/QualityControl/QualityControl.vue';
import QualityControlResults from '@/views/QualityControl/QualityControlResults.vue';
import SigmaMonitor from '@/views/QualityControl/SigmaMonitor.vue';
import Worksheet from '@/views/ManagementReport/Worksheet.vue';
import Census from '@/views/ManagementReport/Census.vue';
import TurnAroundTime from '@/views/ManagementReport/TurnAroundTime.vue';
import DeltaCheckReport from '@/views/ManagementReport/DeltaCheckReport.vue';
import ResultDeltaCheck from '@/views/ResultManagement/DeltaCheck.vue';
import EIMDashboard from '@/views/EquipmentManagement/EIMDashboard.vue';
import EquipmentManagement from '@/views/EquipmentManagement/EquipmentManagement.vue';
import MaintenanceSchedule from '@/views/EquipmentManagement/MaintenanceSchedule.vue';
import ServiceLogs from '@/views/EquipmentManagement/ServiceLogs.vue';
import ConsumablesManagement from '@/views/EquipmentManagement/ConsumablesManagement.vue';
import ConsumablesDashboard from '@/views/ConsumablesManagement/ConsumablesDashboard.vue';
import ConsumablesManagementNew from '@/views/ConsumablesManagement/ConsumablesManagement.vue';
import IMMDashboard from '@/views/InventoryManagement/IMMDashboard.vue';
import MaterialsManagement from '@/views/InventoryManagement/MaterialsManagement.vue';
import EquipmentUsage from '@/views/InventoryManagement/EquipmentUsage.vue';
import CdsRules from '@/views/Admin/CdsRules.vue';
import DeltaCheckQcRules from '@/views/Admin/DeltaCheckQcRules.vue';
import LisBridgeConfig from '@/views/Admin/LisBridgeConfig.vue';
import FormulaBuilder from '@/views/FormulaBuilder/FormulaBuilder.vue';
import FormulaManagement from '@/views/FormulaBuilder/FormulaManagement.vue';
import { useAuthStore } from '@/stores/auth';

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresAuth: false }
  },
  {
    path: '/',
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/dashboard',
      },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: Dashboard,
        meta: { title: 'Dashboard' }
      },
      {
        path: 'patients',
        name: 'patients',
        component: Patients,
        meta: { title: 'Patients' }
      },
      {
        path: 'test-results',
        name: 'test-results',
        component: TestResults,
        meta: { title: 'Test Results' }
      },
      {
        path: 'payments',
        name: 'payments',
        component: Payments,
        meta: { title: 'Payments' }
      },
      {
        path: 'frontdesk',
        name: 'frontdesk',
        component: Frontdesk,
        meta: { title: 'Front Desk' }
      },
      {
        path: 'phlebotomy',
        name: 'phlebotomy',
        component: Phlebotomy,
        meta: { title: 'Phlebotomy' }
      },
      {
        path: 'order-management',
        name: 'order-management',
        component: OrderManagement,
        meta: { title: 'Order Management' }
      },
      {
        path: '/result-management/dashboard',
        name: 'result-management-dashboard',
        component: ResultManagementDashboard,
        meta: {
          title: 'Result Management',
          requiresAuth: true
        }
      },
      {
        path: '/result-management/category/:categoryId/:categoryName',
        name: 'result-category',
        component: CategoryResults,
        props: true,
        meta: {
          title: 'Result Category',
          requiresAuth: true
        }
      },
      {
        path: '/result-management/qc-log',
        name: 'result-qc-log',
        component: QCLog,
        meta: {
          title: 'QC Log',
          requiresAuth: true
        }
      },
      {
        path: '/settings/general',
        name: 'GeneralSettings',
        meta: { title: 'General Settings' }
      },
      {
        path: '/settings/system',
        name: 'SystemConfig',
        meta: { title: 'System Config' }
      },
      {
        path: '/settings/users',
        name: 'UserManagement',
        component: () => import('@/views/UserManagement.vue'),
        meta: { title: 'User Management' }
      },
      {
        path: '/settings/rooms',
        name: 'RoomManagement',
        component: () => import('@/views/RoomManagement.vue'),
        meta: { title: 'Room Management' }
      },
      {
        path: '/settings/wards',
        name: 'WardManagement',
        component: () => import('@/views/WardManagement.vue'),
        meta: { title: 'Ward Management' }
      },
      {
        path: '/settings/doctors',
        name: 'doctors',
        component: Doctors,
        meta: { title: 'Doctors' }
      },
      {
        path: '/setup/test-categories',
        name: 'test-categories',
        component: TestCategories,
        meta: { title: 'Test Categories' }
      },
      {
        path: '/setup/tests',
        name: 'tests',
        component: Tests,
        meta: { title: 'Tests' }
      },
      {
        path: '/setup/reference-ranges',
        name: 'ReferenceRangeManagement',
        component: ReferenceRanges,
        meta: { title: 'Reference Range Management', requiresAuth: true }
      },
      {
        path: '/admin/formulas',
        name: 'FormulaManagement',
        component: FormulaManagement,
        meta: { title: 'Formula Management', requiresAuth: true }
      },
      //Temporarily disabled - FormulaBuilder component not found
      {
        path: '/admin/formulas/builder',
        name: 'FormulaBuilder',
        component: FormulaBuilder,
        meta: { title: 'Formula Builder', requiresAuth: true }
      },
      {
        path: '/admin/report-layouts',
        name: 'ReportLayoutDesigner',
        component: () => import('@/components/admin/ReportLayoutDesigner.vue'),
        meta: { title: 'Report Layouts', requiresAuth: true }
      },
      {
        path: '/admin/report-groups',
        name: 'ReportGroupManager',
        component: () => import('@/components/admin/ReportGroupManager.vue'),
        meta: { title: 'Report Groups', requiresAuth: true }
      },
      {
        path: '/admin/report-templates',
        name: 'ReportTemplatesList',
        component: () => import('@/components/ReportTemplatesList.vue'),
        meta: { title: 'Report Templates', requiresAuth: true }
      },
      {
        path: '/admin/report-templates/new',
        name: 'ReportDesignerNew',
        component: () => import('@/components/ReportDesigner.vue'),
        meta: { title: 'New Report Template', requiresAuth: true }
      },
      {
        path: '/admin/report-templates/:id/edit',
        name: 'ReportDesignerEdit',
        component: () => import('@/components/ReportDesigner.vue'),
        meta: { title: 'Edit Report Template', requiresAuth: true }
      },
      {
        path: '/settings/backup',
        name: 'BackupRestore',
        meta: { title: 'Backup & Resrtore' }
      },
      {
        path: '/doctors-corner',
        name: 'DoctorsCorner',
        component: () => import('@/views/DoctorsCorner.vue'),
      },
      {
        path: '/quality-control',
        name: 'quality-control-dashboard',
        component: QualityControlDashboard,
        meta: {
          requiresAuth: true,
          title: 'Quality Control Dashboard'
        }
      },
      {
        path: '/management-report/quality-control',
        name: 'quality-control',
        component: QualityControl,
        meta: {
          requiresAuth: true,
          title: 'Quality Control'
        }
      },
      {
        path: '/quality-controls',
        name: 'quality-controls',
        component: QualityControls,
        meta: {
          requiresAuth: true,
          title: 'Quality Control'
        }
      },
      {
        path: '/quality-control-results/:qcTestParameterId', // <--- IMPORTANT CHANGE
        name: 'quality-control-results',
        component: QualityControlResults,
        props: true,
        meta: {
          requiresAuth: true,
          title: 'Quality Control Results'
        }
      },
      {
        path: '/sigma-monitor',
        name: 'sigma-monitor',
        component: SigmaMonitor,
        meta: {
          requiresAuth: true,
          title: 'Sigma Monitor'
        }
      },
      {
        path: '/management-report/worksheet',
        name: 'worksheet',
        component: Worksheet,
        meta: {
          requiresAuth: true,
          title: 'Worksheet'
        }
      },
      {
        path: '/management-report/census',
        name: 'census',
        component: Census,
        meta: {
          requiresAuth: true,
          title: 'Census'
        }
      },
      {
        path: '/management-report/turnaround-time',
        name: 'turnaround-time',
        component: TurnAroundTime,
        meta: {
          requiresAuth: true,
          title: 'Turn Around Time'
        }
      },
      {
        path: '/result-management/delta-check',
        name: 'result-delta-check',
        component: ResultDeltaCheck,
        meta: {
          requiresAuth: true,
          title: 'Delta Check'
        }
      },
      {
        path: '/management-report/delta-check',
        name: 'delta-check',
        component: DeltaCheckReport,
        meta: {
          requiresAuth: true,
          title: 'Delta Check Report'
        }
      },
      {
        path: '/equipment-management',
        name: 'equipment-management',
        component: EIMDashboard,
        meta: {
          requiresAuth: true,
          title: 'Equipment & Instrument Management'
        }
      },
      {
        path: '/equipment-management/equipment',
        name: 'equipment-management-list',
        component: EquipmentManagement,
        meta: {
          requiresAuth: true,
          title: 'Equipment Management'
        }
      },
      {
        path: '/equipment-management/maintenance-schedules',
        name: 'maintenance-schedules',
        component: MaintenanceSchedule,
        meta: {
          requiresAuth: true,
          title: 'Maintenance Schedules'
        }
      },
      {
        path: '/equipment-management/service-logs',
        name: 'service-logs',
        component: ServiceLogs,
        meta: {
          requiresAuth: true,
          title: 'Service Logs'
        }
      },
      {
        path: '/equipment-management/consumables',
        name: 'consumables',
        component: ConsumablesManagement,
        meta: {
          requiresAuth: true,
          title: 'Consumables & Reagents'
        }
      },
      {
        path: '/consumables-management',
        name: 'consumables-management-dashboard',
        component: ConsumablesDashboard,
        meta: {
          requiresAuth: true,
          title: 'Consumables & Reagents Management Dashboard'
        }
      },
        {
          path: '/consumables-management/list',
          name: 'consumables-management',
          component: ConsumablesManagementNew,
          meta: {
            requiresAuth: true,
            title: 'Consumables & Reagents Management'
          }
        },
        {
          path: '/inventory-management',
          name: 'imm-dashboard',
          component: IMMDashboard,
          meta: {
            requiresAuth: true,
            title: 'Inventory Management Dashboard'
          }
        },
        {
          path: '/inventory-management/materials',
          name: 'imm-materials',
          component: MaterialsManagement,
          meta: {
            requiresAuth: true,
            title: 'Material Master'
          }
        },
        {
          path: '/inventory-management/lots',
          name: 'imm-lots',
          component: () => import('@/views/InventoryManagement/LotsManagement.vue'),
          meta: {
            requiresAuth: true,
            title: 'Lot Management'
          }
        },
        {
          path: '/inventory-management/vendors',
          name: 'imm-vendors',
          component: () => import('@/views/InventoryManagement/VendorsManagement.vue'),
          meta: {
            requiresAuth: true,
            title: 'Vendor Management'
          }
        },
        {
          path: '/inventory-management/reports',
          name: 'imm-reports',
          component: () => import('@/views/InventoryManagement/ReportsManagement.vue'),
          meta: {
            requiresAuth: true,
            title: 'Reports'
          }
        },
        {
          path: '/inventory-management/equipment-usage',
          name: 'equipment-usage',
          component: EquipmentUsage,
          meta: {
            requiresAuth: true,
            title: 'Equipment Usage'
          }
        },
        {
          path: '/admin/cds-rules',
          name: 'admin-cds-rules',
          component: CdsRules,
          meta: { title: 'CDS Rules', requiresAuth: true }
        },
        {
          path: '/admin/delta-check-qc-rules',
          name: 'admin-delta-check-qc-rules',
          component: DeltaCheckQcRules,
          meta: { requiresAuth: true, title: 'Delta Check & QC Rules' }
        },
        {
          path: '/admin/lis-bridge',
          name: 'lis-bridge-config',
          component: LisBridgeConfig,
          meta: { requiresAuth: true, title: 'LIS Bridge Config' }
        },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  if (!authStore.isLoggedIn) {
    authStore.checkAuth();
  }

  const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
  const isLoggedIn = authStore.isLoggedIn;

  if (requiresAuth && !isLoggedIn) {
    next('/login');
  } else if (to.path === '/login' && isLoggedIn) {
    next('/');
  } else {
    next();
  }
});

export default router; 