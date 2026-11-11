import { defineStore } from 'pinia';
import api from '@/utils/axios';

export const useLaboratoryRequestStore = defineStore('laboratoryRequest', {
  state: () => ({
    requests: [],
    currentRequest: null,
    patients: [],
    doctors: [],
    tests: [],
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
    async fetchRequests(page = 1) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get(`/laboratory-requests?page=${page}`);
        this.requests = response.data.data;
        this.pagination = {
          currentPage: response.data.current_page,
          lastPage: response.data.last_page,
          total: response.data.total,
          perPage: response.data.per_page,
        };
      } catch (error) {
        this.error = error.response?.data?.message || 'Error fetching laboratory requests';
        console.error('Laboratory requests fetch error:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchRequest(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get(`/laboratory-requests/${id}`);
        this.currentRequest = response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error fetching laboratory request';
        console.error('Laboratory request fetch error:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchPatients() {
      try {
        const response = await api.get('/patients-list');
        this.patients = response.data;
      } catch (error) {
        console.error('Patients fetch error:', error);
      }
    },

    async fetchDoctors() {
      try {
        const response = await api.get('/doctors-list');
        this.doctors = response.data;
      } catch (error) {
        console.error('Doctors fetch error:', error);
      }
    },

    async fetchTests() {
      try {
        const response = await api.get('/tests-list');
        this.tests = response.data;
      } catch (error) {
        console.error('Tests fetch error:', error);
      }
    },

    async createRequest(requestData) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/laboratory-requests', requestData);
        this.requests.unshift(response.data);
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error creating laboratory request';
        console.error('Laboratory request creation error:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateRequest(id, requestData) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.put(`/laboratory-requests/${id}`, requestData);
        const index = this.requests.findIndex(r => r.id === id);
        if (index !== -1) {
          this.requests[index] = response.data;
        }
        if (this.currentRequest?.id === id) {
          this.currentRequest = response.data;
        }
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error updating laboratory request';
        console.error('Laboratory request update error:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteRequest(id) {
      this.loading = true;
      this.error = null;
      try {
        await api.delete(`/laboratory-requests/${id}`);
        this.requests = this.requests.filter(r => r.id !== id);
        if (this.currentRequest?.id === id) {
          this.currentRequest = null;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Error deleting laboratory request';
        console.error('Laboratory request deletion error:', error);
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