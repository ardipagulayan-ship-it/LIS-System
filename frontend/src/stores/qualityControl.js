import axios from 'axios';

export default {
  namespaced: true,
  
  state: {
    qualityControls: [],
    currentQualityControl: null,
    results: [],
    equipmentSummary: null,
    loading: false,
    error: null
  },

  mutations: {
    SET_QUALITY_CONTROLS(state, controls) {
      state.qualityControls = controls;
    },
    SET_CURRENT_QUALITY_CONTROL(state, control) {
      state.currentQualityControl = control;
    },
    SET_RESULTS(state, results) {
      state.results = results;
    },
    SET_EQUIPMENT_SUMMARY(state, summary) {
      state.equipmentSummary = summary;
    },
    SET_LOADING(state, loading) {
      state.loading = loading;
    },
    SET_ERROR(state, error) {
      state.error = error;
    },
    ADD_QUALITY_CONTROL(state, control) {
      state.qualityControls.push(control);
    },
    UPDATE_QUALITY_CONTROL(state, updatedControl) {
      const index = state.qualityControls.findIndex(qc => qc.id === updatedControl.id);
      if (index !== -1) {
        state.qualityControls.splice(index, 1, updatedControl);
      }
    },
    DELETE_QUALITY_CONTROL(state, id) {
      state.qualityControls = state.qualityControls.filter(qc => qc.id !== id);
    },
    ADD_RESULT(state, result) {
      state.results.push(result);
    },
    UPDATE_RESULT(state, updatedResult) {
      const index = state.results.findIndex(r => r.id === updatedResult.id);
      if (index !== -1) {
        state.results.splice(index, 1, updatedResult);
      }
    },
    DELETE_RESULT(state, id) {
      state.results = state.results.filter(r => r.id !== id);
    }
  },

  actions: {
    async fetchQualityControls({ commit }) {
      commit('SET_LOADING', true);
      try {
        const response = await axios.get('/api/quality-controls');
        commit('SET_QUALITY_CONTROLS', response.data.data);
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Error fetching quality controls');
        throw error;
      } finally {
        commit('SET_LOADING', false);
      }
    },

    async fetchQualityControl({ commit }, id) {
      commit('SET_LOADING', true);
      try {
        const response = await axios.get(`/api/quality-controls/${id}`);
        commit('SET_CURRENT_QUALITY_CONTROL', response.data);
        return response.data;
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Error fetching quality control');
        throw error;
      } finally {
        commit('SET_LOADING', false);
      }
    },

    async createQualityControl({ commit }, qualityControl) {
      commit('SET_LOADING', true);
      try {
        const response = await axios.post('/api/quality-controls', qualityControl);
        commit('ADD_QUALITY_CONTROL', response.data);
        return response.data;
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Error creating quality control');
        throw error;
      } finally {
        commit('SET_LOADING', false);
      }
    },

    async updateQualityControl({ commit }, { id, qualityControl }) {
      commit('SET_LOADING', true);
      try {
        const response = await axios.put(`/api/quality-controls/${id}`, qualityControl);
        commit('UPDATE_QUALITY_CONTROL', response.data);
        return response.data;
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Error updating quality control');
        throw error;
      } finally {
        commit('SET_LOADING', false);
      }
    },

    async deleteQualityControl({ commit }, id) {
      commit('SET_LOADING', true);
      try {
        await axios.delete(`/api/quality-controls/${id}`);
        commit('DELETE_QUALITY_CONTROL', id);
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Error deleting quality control');
        throw error;
      } finally {
        commit('SET_LOADING', false);
      }
    },

    async fetchResults({ commit }, qualityControlId) {
      commit('SET_LOADING', true);
      try {
        const response = await axios.get(`/api/quality-controls/${qualityControlId}/results`);
        commit('SET_RESULTS', response.data.data);
        return response.data;
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Error fetching results');
        throw error;
      } finally {
        commit('SET_LOADING', false);
      }
    },

    async createResult({ commit }, { qualityControlId, result }) {
      commit('SET_LOADING', true);
      try {
        const response = await axios.post(`/api/quality-controls/${qualityControlId}/results`, result);
        commit('ADD_RESULT', response.data);
        return response.data;
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Error creating result');
        throw error;
      } finally {
        commit('SET_LOADING', false);
      }
    },

    async updateResult({ commit }, { id, result }) {
      commit('SET_LOADING', true);
      try {
        const response = await axios.put(`/api/quality-control-results/${id}`, result);
        commit('UPDATE_RESULT', response.data);
        return response.data;
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Error updating result');
        throw error;
      } finally {
        commit('SET_LOADING', false);
      }
    },

    async deleteResult({ commit }, id) {
      commit('SET_LOADING', true);
      try {
        await axios.delete(`/api/quality-control-results/${id}`);
        commit('DELETE_RESULT', id);
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Error deleting result');
        throw error;
      } finally {
        commit('SET_LOADING', false);
      }
    },

    async fetchLeveyJenningsData({ commit }, qualityControlId) {
      commit('SET_LOADING', true);
      try {
        const response = await axios.get(`/api/quality-controls/${qualityControlId}/levey-jennings`);
        return response.data;
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Error fetching Levey-Jennings data');
        throw error;
      } finally {
        commit('SET_LOADING', false);
      }
    },

    // New: Equipment-specific QC endpoints
    async fetchEquipmentSummary({ commit }, testCategoryId) {
      commit('SET_LOADING', true);
      try {
        const { data } = await axios.get(`/api/qc-lockout/status-summary`, { params: { test_category_id: testCategoryId } });
        commit('SET_EQUIPMENT_SUMMARY', data);
        return data;
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Error fetching equipment QC summary');
        throw error;
      } finally {
        commit('SET_LOADING', false);
      }
    },

    async fetchLockedResultsByEquipment({ commit }, equipmentId) {
      // does not affect primary state list by default
      const { data } = await axios.get(`/api/qc-lockout/locked-results/${equipmentId}`);
      return data;
    },

    async releaseQCLockout(_, { equipment_id, test_parameter_id, corrective_action_id = null }) {
      const { data } = await axios.post(`/api/qc-lockout/release`, { equipment_id, test_parameter_id, corrective_action_id });
      return data;
    }
  },

  getters: {
    getQualityControls: state => state.qualityControls,
    getCurrentQualityControl: state => state.currentQualityControl,
    getResults: state => state.results,
    getEquipmentSummary: state => state.equipmentSummary,
    isLoading: state => state.loading,
    getError: state => state.error
  }
}; 