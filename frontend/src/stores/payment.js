import { defineStore } from 'pinia';
import api from '@/utils/axios';

export const usePaymentStore = defineStore('payment', {
  state: () => ({
    payments: [],
    currentPayment: null,
    unpaidRequests: [],
    paymentSummary: null,
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
    async fetchPayments(page = 1) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get(`/payments?page=${page}`);
        this.payments = response.data.data;
        this.pagination = {
          currentPage: response.data.current_page,
          lastPage: response.data.last_page,
          total: response.data.total,
          perPage: response.data.per_page,
        };
      } catch (error) {
        this.error = error.response?.data?.message || 'Error fetching payments';
        console.error('Payments fetch error:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchPayment(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get(`/payments/${id}`);
        this.currentPayment = response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error fetching payment';
        console.error('Payment fetch error:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchUnpaidRequests() {
      try {
        const response = await api.get('/unpaid-requests');
        this.unpaidRequests = response.data;
      } catch (error) {
        console.error('Unpaid requests fetch error:', error);
      }
    },

    async fetchPaymentSummary() {
      try {
        const response = await api.get('/payment-summary');
        this.paymentSummary = response.data;
      } catch (error) {
        console.error('Payment summary fetch error:', error);
      }
    },

    async createPayment(paymentData) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/payments', paymentData);
        this.payments.unshift(response.data);
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error creating payment';
        console.error('Payment creation error:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updatePayment(id, paymentData) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.put(`/payments/${id}`, paymentData);
        const index = this.payments.findIndex(p => p.id === id);
        if (index !== -1) {
          this.payments[index] = response.data;
        }
        if (this.currentPayment?.id === id) {
          this.currentPayment = response.data;
        }
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Error updating payment';
        console.error('Payment update error:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deletePayment(id) {
      this.loading = true;
      this.error = null;
      try {
        await api.delete(`/payments/${id}`);
        this.payments = this.payments.filter(p => p.id !== id);
        if (this.currentPayment?.id === id) {
          this.currentPayment = null;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Error deleting payment';
        console.error('Payment deletion error:', error);
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