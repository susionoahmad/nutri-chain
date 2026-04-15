import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'landing',
            component: () => import('@/views/Landing.vue'),
            meta: { public: true }, // We add this if we want it public
        },
        {
            path: '/_app', // Dummy parent path
            component: () => import('@/components/MainLayout.vue'),
            meta: { requiresAuth: true },
            children: [
                {
                    path: '/dashboard',
                    name: 'dashboard',
                    component: () => import('@/views/Dashboard.vue'),
                },
                {
                    path: '/products',
                    name: 'products',
                    component: () => import('@/views/Products.vue'),
                },
                {
                    path: '/customers',
                    name: 'customers',
                    component: () => import('@/views/Customers.vue'),
                },
                {
                    path: '/orders',
                    name: 'orders',
                    component: () => import('@/views/Orders.vue'),
                },
                {
                    path: '/invoices',
                    name: 'invoices',
                    component: () => import('@/views/Invoices.vue'),
                },
                {
                    path: '/stock-mutations',
                    name: 'stock-mutations',
                    component: () => import('@/views/StockMutations.vue'),
                },
                {
                    path: '/reports',
                    name: 'reports',
                    component: () => import('@/views/Reports.vue'),
                },
                {
                    path: '/producers',
                    name: 'producers',
                    component: () => import('@/views/Producers.vue'),
                },
                {
                    path: '/purchases',
                    name: 'purchases',
                    component: () => import('@/views/Purchases.vue'),
                },
                {
                    path: '/cash-flow',
                    name: 'cash-flow',
                    component: () => import('@/views/CashFlow.vue'),
                },
                {
                    path: '/users',
                    name: 'users',
                    component: () => import('@/views/Users.vue'),
                    meta: { role: 'owner' }
                },
                {
                    path: '/settings',
                    name: 'settings',
                    component: () => import('@/views/Suppliers.vue'),
                    meta: { role: 'owner' }
                },
                {
                    path: '/billing',
                    name: 'billing',
                    component: () => import('@/views/Billing.vue'),
                }
            ]
        },
        {
            path: '/_saas', // Dummy parent path
            component: () => import('@/components/MainLayout.vue'), // Using the same layout for now, will adapt UI in Layout
            meta: { requiresAuth: true, role: 'superadmin' },
            children: [
                {
                    path: '/saas/dashboard',
                    name: 'saas-dashboard',
                    component: () => import('@/views/saas/SaasDashboard.vue'),
                },
                {
                    path: '/saas/tenants',
                    name: 'saas-tenants',
                    component: () => import('@/views/saas/SaasTenants.vue'),
                },
                {
                    path: '/saas/payments',
                    name: 'saas-payments',
                    component: () => import('@/views/saas/SaasPayments.vue'),
                },
                {
                    path: '/saas/plans',
                    name: 'saas-plans',
                    component: () => import('@/views/saas/SaasPlans.vue'),
                },
                {
                    path: '/saas/settings',
                    name: 'saas-settings',
                    component: () => import('@/views/saas/SaasSettings.vue'),
                }
            ]
        },
        {
            path: '/login',
            name: 'login',
            component: () => import('@/views/Login.vue'),
            meta: { guestOnly: true }
        },
        {
            path: '/register',
            name: 'register',
            component: () => import('@/views/Register.vue'),
            meta: { guestOnly: true }
        },
        {
            path: '/onboarding',
            name: 'onboarding',
            component: () => import('@/views/Onboarding.vue'),
            meta: { requiresAuth: true }
        }
    ]
});

router.beforeEach(async (to, _from, next) => {
    const auth = useAuthStore();
    
    // Ensure user data is fetched if token exists
    if (auth.token && !auth.user) {
        try {
            await auth.fetchMe();
        } catch (e) {
            auth.logout();
            return next('/login');
        }
    }

    if (to.name === 'landing' && auth.isAuthenticated) {
        return next(auth.user?.role === 'superadmin' ? '/saas/dashboard' : '/dashboard');
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        next('/login');
    } else if (auth.isAuthenticated && !auth.isOnboarded && to.name !== 'onboarding') {
        next({ name: 'onboarding' });
    } else if (auth.isAuthenticated && auth.isOnboarded && to.name === 'onboarding') {
        next('/');
    } else if (to.meta.guestOnly && auth.isAuthenticated) {
        next('/dashboard');
    } else if (to.meta.role && auth.user?.role !== to.meta.role) {
        // Forbidden access based on role
        next('/dashboard');
    } else {
        next();
    }
});

export default router;
