import { defineStore } from 'pinia';
import api from '../api/axios';

export const useDashboardStore = defineStore('dashboard', {
    state: () => ({
        stats: null as any,
        loading: false,
    }),
    actions: {
        async fetchStats() {
            this.loading = true;
            try {
                const { data } = await api.get('/dashboard-stats');
                this.stats = data;
            } finally {
                this.loading = false;
            }
        },
    },
});