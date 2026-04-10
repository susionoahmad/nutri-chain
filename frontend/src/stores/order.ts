import { defineStore } from 'pinia';
import api from '../api/axios';

export const useOrderStore = defineStore('order', {
    state: () => ({
        orders: [] as any[],
        loading: false,
    }),
    actions: {
        async fetchOrders() {
            this.loading = true;
            try {
                const { data } = await api.get('/orders');
                this.orders = data.data;
            } finally {
                this.loading = false;
            }
        },
        async createOrder(payload: any) {
            const { data } = await api.post('/orders', payload);
            this.orders.unshift(data.data);
            return data.data;
        },
        // Admin Action: Konfirmasi pesanan & Potong stok
        async confirmOrder(orderId: number) {
            const { data } = await api.patch(`/orders/${orderId}/confirm`);
            this.updateLocalOrder(data.order);
        },
        // Warehouse Action: Kirim barang
        async dispatchOrder(orderId: number) {
            const { data } = await api.patch(`/orders/${orderId}/dispatch`);
            this.updateLocalOrder(data.order);
        },
        // Driver Action: Konfirmasi sampai & Auto-generate invoice
        async deliverOrder(orderId: number) {
            const { data } = await api.patch(`/orders/${orderId}/deliver`);
            this.updateLocalOrder(data.order);
        },
        updateLocalOrder(updatedOrder: any) {
            const index = this.orders.findIndex(o => o.id === updatedOrder.id);
            if (index !== -1) {
                this.orders[index] = { ...this.orders[index], ...updatedOrder };
            }
        },
        async checkStock(productId: number) {
            const { data } = await api.get(`/orders/check-stock/${productId}`);
            return data;
        }
    },
});