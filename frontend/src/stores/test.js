import { defineStore } from 'pinia';
import api from '@/utils/axios';

export const useTestStore = defineStore('test', {
  state: () => ({
    tests: [],
    currentTest: null,
    categories: [],
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
    async fetchTests(page = 1) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get(`/tests?page=${page}`);
        this.tests = response.data.data;
        this.pagination = {
          currentPage: response.data.current_page,
          lastPage: response.data.last_page,
          total: response.data.total,
          perPage: response.data.per_page,
        };
      } catch (error) {
        this.error = error.response?.data?.message || 'Error fetching tests';
        console.error('Tests fetch error:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchTest(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get(`/tests/${id}`);
        this.currentTest = response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error fetching test';
        console.error('Test fetch error:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchCategories() {
      try {
        const response = await api.get('/test-categories-list');
        this.categories = response.data;
      } catch (error) {
        console.error('Test categories fetch error:', error);
      }
    },

    async createTest(testData) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/tests', testData);
        this.tests.unshift(response.data);
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error creating test';
        console.error('Test creation error:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateTest(id, testData) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.put(`/tests/${id}`, testData);
        const index = this.tests.findIndex(t => t.id === id);
        if (index !== -1) {
          this.tests[index] = response.data;
        }
        if (this.currentTest?.id === id) {
          this.currentTest = response.data;
        }
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error updating test';
        console.error('Test update error:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteTest(id) {
      this.loading = true;
      this.error = null;
      try {
        await api.delete(`/tests/${id}`);
        this.tests = this.tests.filter(t => t.id !== id);
        if (this.currentTest?.id === id) {
          this.currentTest = null;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Error deleting test';
        console.error('Test deletion error:', error);
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