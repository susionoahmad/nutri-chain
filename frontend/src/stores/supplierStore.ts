import { defineStore } from 'pinia';
import api from '../api/axios';

export const useSupplierStore = defineStore('supplier', {
  state: () => ({
    supplier: null as any,
    loading: false,
    generating: false,
  }),

  actions: {
    async fetchSupplier() {
      this.loading = true;
      try {
        const { data } = await api.get('/supplier');
        this.supplier = data;
      } catch (error) {
        console.error('Gagal mengambil data supplier', error);
      } finally {
        this.loading = false;
      }
    },

    async generateNewCode() {
      this.generating = true;
      try {
        const { data } = await api.post('/supplier/generate-code');
        return data.code;
      } catch (error) {
        console.error('Gagal generate kode', error);
        throw error;
      } finally {
        this.generating = false;
      }
    },

    async updateSupplier(payload: any) {
      try {
        const { data } = await api.put('/supplier', payload);
        if (data.supplier) {
          this.supplier = data.supplier;
        }
        return data;
      } catch (error) {
        throw error;
      }
    }
  }
});