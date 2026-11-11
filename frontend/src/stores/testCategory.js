import { defineStore } from 'pinia';
import api from '@/utils/axios';

export const useTestCategoryStore = defineStore('testCategory', {
  state: () => ({
    categories: [],
    currentCategory: null,
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
    async fetchCategories(page = 1) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get(`/test-categories?page=${page}`);
        this.categories = response.data.data;
        this.pagination = {
          currentPage: response.data.current_page,
          lastPage: response.data.last_page,
          total: response.data.total,
          perPage: response.data.per_page,
        };
      } catch (error) {
        this.error = error.response?.data?.message || 'Error fetching categories';
        console.error('Categories fetch error:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchCategory(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get(`/test-categories/${id}`);
        this.currentCategory = response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error fetching category';
        console.error('Category fetch error:', error);
      } finally {
        this.loading = false;
      }
    },

    async createCategory(categoryData) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/test-categories', categoryData);
        this.categories.unshift(response.data);
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error creating category';
        console.error('Category creation error:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateCategory(id, categoryData) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.put(`/test-categories/${id}`, categoryData);
        const index = this.categories.findIndex(c => c.id === id);
        if (index !== -1) {
          this.categories[index] = response.data;
        }
        if (this.currentCategory?.id === id) {
          this.currentCategory = response.data;
        }
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error updating category';
        console.error('Category update error:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteCategory(id) {
      this.loading = true;
      this.error = null;
      try {
        await api.delete(`/test-categories/${id}`);
        this.categories = this.categories.filter(c => c.id !== id);
        if (this.currentCategory?.id === id) {
          this.currentCategory = null;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Error deleting category';
        console.error('Category deletion error:', error);
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