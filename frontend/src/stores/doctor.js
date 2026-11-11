import { defineStore } from 'pinia';
import api from '@/utils/axios';

export const useDoctorStore = defineStore('doctor', {
  state: () => ({
    doctors: [],
    currentDoctor: null,
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
    async fetchDoctors(page = 1) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get(`/doctors?page=${page}`);
        this.doctors = response.data.data;
        this.pagination = {
          currentPage: response.data.current_page,
          lastPage: response.data.last_page,
          total: response.data.total,
          perPage: response.data.per_page,
        };
      } catch (error) {
        this.error = error.response?.data?.message || 'Error fetching doctors';
        console.error('Doctors fetch error:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchDoctor(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get(`/doctors/${id}`);
        this.currentDoctor = response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error fetching doctor';
        console.error('Doctor fetch error:', error);
      } finally {
        this.loading = false;
      }
    },

    async createDoctor(doctorData) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/doctors', doctorData);
        this.doctors.unshift(response.data);
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error creating doctor';
        console.error('Doctor creation error:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateDoctor(id, doctorData) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.put(`/doctors/${id}`, doctorData);
        const index = this.doctors.findIndex(d => d.id === id);
        if (index !== -1) {
          this.doctors[index] = response.data;
        }
        if (this.currentDoctor?.id === id) {
          this.currentDoctor = response.data;
        }
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error updating doctor';
        console.error('Doctor update error:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteDoctor(id) {
      this.loading = true;
      this.error = null;
      try {
        await api.delete(`/doctors/${id}`);
        this.doctors = this.doctors.filter(d => d.id !== id);
        if (this.currentDoctor?.id === id) {
          this.currentDoctor = null;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Error deleting doctor';
        console.error('Doctor deletion error:', error);
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