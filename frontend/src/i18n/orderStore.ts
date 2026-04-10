import { defineStore } from 'pinia';
import axios from 'axios';

export const useOrderStore = defineStore('order', {
  state: () => ({
    orders: [],
    loading: false,
  }),

  actions: {
    /**
     * Memeriksa stok produk secara real-time dari server.
     */
    async checkProductStock(productId: number) {
      try {
        const { data } = await axios.get(`/orders/check-stock/${productId}`);
        return {
          qty: data.available_qty,
          unit: data.unit
        };
      } catch (error) {
        console.error('Gagal memeriksa stok produk:', error);
        return { qty: 0, unit: '' };
      }
    },

    async createOrder(payload: any) {
      this.loading = true;
      try {
        const { data } = await axios.post('/orders', payload);
        return data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    }
  }
});