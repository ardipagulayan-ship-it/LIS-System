import { defineStore } from 'pinia';
import api from '@/utils/axios';

export const useTestResultStore = defineStore('testResult', {
  state: () => ({
    results: [],
    currentResult: null,
    laboratoryRequests: [],
    pendingTests: [],
    loading: false,
    error: null,
    pagination: {
      currentPage: 1,
      lastPage: 1,
      total: 0,
      perPage: 10,
    },
  }),

  actions: {
    async fetchResults(page = 1) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get(`/test-results?page=${page}`);
        this.results = response.data.data;
        this.pagination = {
          currentPage: response.data.current_page,
          lastPage: response.data.last_page,
          total: response.data.total,
          perPage: response.data.per_page,
        };
      } catch (error) {
        this.error = error.response?.data?.message || 'Error fetching test results';
        console.error('Test results fetch error:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchResult(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get(`/test-results/${id}`);
        this.currentResult = response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error fetching test result';
        console.error('Test result fetch error:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchLaboratoryRequests() {
      try {
        const response = await api.get('/pending-laboratory-requests');
        this.laboratoryRequests = response.data;
      } catch (error) {
        console.error('Laboratory requests fetch error:', error);
      }
    },

    async fetchPendingTests(laboratoryRequestId) {
      try {
        const response = await api.get(`/laboratory-requests/${laboratoryRequestId}/pending-tests`);
        this.pendingTests = response.data;
      } catch (error) {
        console.error('Pending tests fetch error:', error);
      }
    },

    async createResult(resultData) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/test-results', resultData);
        this.results.unshift(response.data);
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error creating test result';
        console.error('Test result creation error:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateResult(id, resultData) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.put(`/test-results/${id}`, resultData);
        const index = this.results.findIndex(r => r.id === id);
        if (index !== -1) {
          this.results[index] = response.data;
        }
        if (this.currentResult?.id === id) {
          this.currentResult = response.data;
        }
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error updating test result';
        console.error('Test result update error:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteResult(id) {
      this.loading = true;
      this.error = null;
      try {
        await api.delete(`/test-results/${id}`);
        this.results = this.results.filter(r => r.id !== id);
        if (this.currentResult?.id === id) {
          this.currentResult = null;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Error deleting test result';
        console.error('Test result deletion error:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    clearError() {
      this.error = null;
    },
  },
}); 