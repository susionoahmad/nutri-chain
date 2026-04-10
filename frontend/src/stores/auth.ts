import { defineStore } from 'pinia';
import api from '../api/axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user') || 'null'),
        token: localStorage.getItem('auth_token') || null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
        role: (state) => state.user?.role || 'guest',
        isOwner: (state) => state.user?.role === 'owner',
        isAdmin: (state) => state.user?.role === 'admin',
        isWarehouse: (state) => state.user?.role === 'warehouse',
        isDriver: (state) => state.user?.role === 'driver',
        isCustomer: (state) => state.user?.role === 'customer',
        isSuperAdmin: (state) => state.user?.role === 'superadmin',
        isOnboarded: (state) => state.user?.role !== 'owner' || (state.user?.supplier?.is_onboarded ?? true),
    },
    actions: {
        async login(credentials: any) {
            const { data } = await api.post('/login', credentials);
            this.token = data.access_token;
            this.user = data.user;
            localStorage.setItem('auth_token', data.access_token);
            localStorage.setItem('user', JSON.stringify(data.user));
        },
        async registerSupplier(payload: any) {
            const { data } = await api.post('/register/supplier', payload);
            this.token = data.access_token;
            this.user = data.user;
            localStorage.setItem('auth_token', data.access_token);
            localStorage.setItem('user', JSON.stringify(data.user));
        },
        async registerCustomer(payload: any) {
            const { data } = await api.post('/register/customer', payload);
            this.token = data.access_token;
            this.user = data.user;
            localStorage.setItem('auth_token', data.access_token);
            localStorage.setItem('user', JSON.stringify(data.user));
        },
        async logout() {
            try {
                await api.post('/logout');
            } catch (error) {
                console.warn('Logout API failed but clearing session locally.', error);
            } finally {
                this.token = null;
                this.user = null;
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user');
                // Redirect immediately using window.location for hard reset if needed
                window.location.href = '/login';
            }
        },
        async fetchMe() {
            try {
                const { data } = await api.get('/me');
                this.user = data;
                localStorage.setItem('user', JSON.stringify(data));
            } catch (error) {
                this.logout();
            }
        }
    },
});