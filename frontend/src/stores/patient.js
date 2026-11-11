import { defineStore } from 'pinia';
import api from '@/utils/axios';

export const usePatientStore = defineStore('patient', {
  state: () => ({
    patients: [],
    currentPatient: null,
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
    async fetchPatients(page = 1) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get(`/patients?page=${page}`);
        this.patients = response.data.data;
        this.pagination = {
          currentPage: response.data.current_page,
          lastPage: response.data.last_page,
          total: response.data.total,
          perPage: response.data.per_page,
        };
      } catch (error) {
        this.error = error.response?.data?.message || 'Error fetching patients';
        console.error('Patients fetch error:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchPatient(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get(`/patients/${id}`);
        this.currentPatient = response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error fetching patient';
        console.error('Patient fetch error:', error);
      } finally {
        this.loading = false;
      }
    },

    async createPatient(patientData) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/patients', patientData);
        this.patients.unshift(response.data);
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error creating patient';
        console.error('Patient creation error:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updatePatient(id, patientData) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.put(`/patients/${id}`, patientData);
        const index = this.patients.findIndex(p => p.id === id);
        if (index !== -1) {
          this.patients[index] = response.data;
        }
        if (this.currentPatient?.id === id) {
          this.currentPatient = response.data;
        }
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error updating patient';
        console.error('Patient update error:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deletePatient(id) {
      this.loading = true;
      this.error = null;
      try {
        await api.delete(`/patients/${id}`);
        this.patients = this.patients.filter(p => p.id !== id);
        if (this.currentPatient?.id === id) {
          this.currentPatient = null;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Error deleting patient';
        console.error('Patient deletion error:', error);
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