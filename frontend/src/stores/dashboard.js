import { defineStore } from 'pinia';
import api from '@/utils/axios';

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    stats: {
      totalPatients: 0,
      pendingRequests: 0,
      todaysTests: 0,
      todaysRevenue: 0,
    },
    recentRequests: [],
    recentResults: [],
    loading: false,
    error: null,
  }),

  actions: {
    async fetchDashboardData() {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get('/dashboard');
        const { stats, recentRequests, recentResults } = response.data;
        
        this.stats = stats;
        this.recentRequests = recentRequests;
        this.recentResults = recentResults;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error fetching dashboard data';
        console.error('Dashboard data fetch error:', error);
      } finally {
        this.loading = false;
      }
    },
  },
}); 