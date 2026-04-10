<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { 
    LayoutDashboard, Package, Users, ShoppingCart, 
    LogOut, Menu, X, User, Languages, Settings, Receipt,
    Truck, Wallet, RefreshCcw, FileBarChart
} from 'lucide-vue-next';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';

const route = useRoute();
const auth = useAuthStore();
const { t, locale } = useI18n();

onMounted(async () => {
    if (auth.token && !auth.user) {
        await auth.fetchMe();
    }
});
const isSidebarOpen = ref(false);

const navigationGroups = computed(() => {
    const role = auth.user?.role;
    const groups: any[] = [];
    
    // --- SUPERADMIN NAVIGATION ---
    if (role === 'superadmin') {
        groups.push({ 
            title: 'SaaS MANAGEMENT', 
            items: [
                { name: 'SaaS Dashboard', path: '/saas/dashboard', icon: LayoutDashboard },
                { name: 'Tenant List', path: '/saas/tenants', icon: Users },
                { name: 'Verification', path: '/saas/payments', icon: Receipt },
                { name: 'Master Paket', path: '/saas/plans', icon: Package },
                { name: 'Platform Settings', path: '/saas/settings', icon: Settings },
            ] 
        });
        return groups;
    }

    // --- REGULAR TENANT NAVIGATION ---
    // 1. CORE / UTAMA
    const coreItems: any[] = [
        { name: t('menu.dashboard'), path: '/dashboard', icon: LayoutDashboard }
    ];
    if (['owner', 'admin', 'warehouse', 'customer'].includes(role || '')) {
        coreItems.push({ name: t('menu.inventory'), path: '/products', icon: Package });
    }
    if (['owner', 'admin'].includes(role || '')) {
        coreItems.push({ name: t('menu.customers'), path: '/customers', icon: Users });
    }
    groups.push({ title: t('menu.groups.main'), items: coreItems });

    // 2. SUPPLY CHAIN / RANTAI PASOK
    const scItems: any[] = [];
    if (['owner', 'admin', 'warehouse'].includes(role || '')) {
        scItems.push({ name: t('menu.mutations'), path: '/stock-mutations', icon: RefreshCcw });
    }
    if (['owner', 'admin'].includes(role || '')) {
        scItems.push({ name: t('menu.purchases'), path: '/purchases', icon: ShoppingCart });
        scItems.push({ name: t('menu.producers'), path: '/producers', icon: Truck });
    }
    if (['owner', 'admin', 'customer', 'warehouse', 'driver'].includes(role || '')) {
        scItems.push({ name: t('menu.orders'), path: '/orders', icon: ShoppingCart });
    }
    if (scItems.length > 0) groups.push({ title: t('menu.groups.supply_chain'), items: scItems });

    // 3. FINANCE / KEUANGAN
    const finItems: any[] = [];
    if (['owner', 'admin', 'customer'].includes(role || '')) {
        finItems.push({ name: t('menu.invoices'), path: '/invoices', icon: Receipt });
    }
    if (['owner', 'admin'].includes(role || '')) {
        finItems.push({ name: t('menu.cash_flow'), path: '/cash-flow', icon: Wallet });
        finItems.push({ name: t('menu.reports'), path: '/reports', icon: FileBarChart });
    }
    if (finItems.length > 0) groups.push({ title: t('menu.groups.finance'), items: finItems });

    return groups;
});

const ownerNavigation = computed(() => [
    { name: t('menu.users'), path: '/users', icon: User },
    { name: 'Subscription', path: '/billing', icon: Receipt },
    { name: t('menu.settings'), path: '/settings', icon: Settings },
]);

const toggleLanguage = () => {
    locale.value = locale.value === 'id' ? 'en' : 'id';
    localStorage.setItem('locale', locale.value);
    window.location.reload(); 
};

const logout = async () => {
    isSidebarOpen.value = false;
    await auth.logout();
};

// Bottom tab bar items, tailored per role (max 4)
const bottomNavItems = computed(() => {
    const role = auth.user?.role;
    switch (role) {
        case 'owner':
        case 'admin':
            return [
                { name: t('menu.dashboard'),  path: '/dashboard',  icon: LayoutDashboard },
                { name: t('menu.orders'),      path: '/orders',     icon: ShoppingCart },
                { name: t('menu.invoices'),    path: '/invoices',   icon: Receipt },
                { name: t('menu.reports'),     path: '/reports',    icon: FileBarChart },
            ];
        case 'customer':
            return [
                { name: t('menu.dashboard'),  path: '/dashboard',  icon: LayoutDashboard },
                { name: t('menu.inventory'),  path: '/products',   icon: Package },
                { name: t('menu.orders'),     path: '/orders',     icon: ShoppingCart },
                { name: t('menu.invoices'),   path: '/invoices',   icon: Receipt },
            ];
        case 'warehouse':
            return [
                { name: t('menu.dashboard'),  path: '/dashboard',       icon: LayoutDashboard },
                { name: t('menu.inventory'),  path: '/products',        icon: Package },
                { name: t('menu.orders'),     path: '/orders',          icon: ShoppingCart },
                { name: t('menu.mutations'),  path: '/stock-mutations',  icon: RefreshCcw },
            ];
        case 'driver':
            return [
                { name: t('menu.dashboard'),  path: '/dashboard',  icon: LayoutDashboard },
                { name: t('menu.orders'),     path: '/orders',     icon: ShoppingCart },
            ];
        case 'superadmin':
            return [
                { name: 'Dashboard', path: '/saas/dashboard', icon: LayoutDashboard },
                { name: 'Tenants',   path: '/saas/tenants',   icon: Users },
                { name: 'Verify',    path: '/saas/payments',  icon: Receipt },
            ];
        default:
            return [
                { name: t('menu.dashboard'),  path: '/dashboard',  icon: LayoutDashboard },
            ];
    }
});
</script>

<template>
<div class="h-screen bg-black text-slate-200 font-sans selection:bg-blue-500/30 overflow-hidden flex flex-col">
    <!-- Static Background Accents -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none print:hidden">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-600/10 blur-[120px] rounded-full"></div>
        <div class="absolute top-[20%] -right-[10%] w-[35%] h-[35%] bg-indigo-600/10 blur-[120px] rounded-full"></div>
        <div class="absolute -bottom-[10%] left-[20%] w-[30%] h-[30%] bg-purple-600/10 blur-[120px] rounded-full"></div>
    </div>

    <!-- Mobile Header -->
    <header class="lg:hidden sticky top-0 z-[60] bg-black/60 backdrop-blur-xl border-b border-white/5 px-6 py-4 flex items-center justify-between print:hidden">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl flex items-center justify-center shadow-lg shadow-blue-600/20">
                <Package class="text-white w-6 h-6" />
            </div>
            <span class="font-black text-xl font-mono tracking-tighter text-white uppercase italic">{{ auth.user?.supplier?.name || $t('brand.name') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button @click="toggleLanguage" class="p-2 text-slate-400 font-bold text-xs uppercase">{{ locale }}</button>
            <button @click="isSidebarOpen = true" class="p-2 text-slate-400 hover:text-white transition-colors">
                <Menu class="w-7 h-7" />
            </button>
        </div>
    </header>

    <div class="flex flex-1 relative overflow-hidden">
        <!-- Sidebar Desktop -->
    <aside class="hidden lg:flex flex-col w-80 h-full bg-black/40 backdrop-blur-3xl border-r border-white/5 pb-8 pt-10 print:hidden shrink-0">
            <!-- Sidebar Header -->
            <div class="flex items-center gap-4 mb-12 px-10">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl flex items-center justify-center shadow-xl shadow-blue-600/20 group hover:rotate-3 transition-transform">
                    <Package class="text-white w-7 h-7" />
                </div>
                <div>
                    <h1 class="font-black text-2xl tracking-tighter text-white leading-none italic uppercase">{{ auth.user?.supplier?.name || $t('brand.name') }}</h1>
                    <p class="text-[9px] text-blue-500 font-black uppercase tracking-[0.3em] mt-2 ml-0.5 opacity-80">{{ $t('brand.erp') }}</p>
                </div>
            </div>

            <!-- Scrollable Navigation Area -->
            <nav class="flex-1 px-6 space-y-8 overflow-y-auto custom-sidebar-scroll pb-10">
                <!-- Dynamic Groups -->
                <div v-for="group in navigationGroups" :key="group.title" class="space-y-2">
                    <p class="px-4 text-[9px] font-black text-slate-600 uppercase tracking-[0.4em] mb-4 italic">{{ group.title }}</p>
                    <div class="space-y-1">
                        <router-link 
                            v-for="item in group.items" 
                            :key="item.name"
                            :to="item.path"
                            class="flex items-center gap-4 px-5 py-3.5 rounded-2xl transition-all duration-500 group relative"
                            :class="[
                                route.path === item.path 
                                ? 'bg-blue-600/10 text-blue-400 shadow-[inset_0_0_0_1px_rgba(37,99,235,0.2)]' 
                                : 'text-slate-500 hover:text-slate-100 hover:bg-white/5'
                            ]"
                        >
                            <component :is="item.icon" class="w-5 h-5 transition-transform group-hover:scale-110" />
                            <span class="font-bold tracking-tight text-sm uppercase italic">{{ item.name }}</span>
                            <!-- Active Indicator (Pill shape) -->
                            <div v-if="route.path === item.path" class="absolute right-4 w-1 h-4 bg-blue-500 rounded-full shadow-[0_0_12px_rgba(59,130,246,0.8)]"></div>
                        </router-link>
                    </div>
                </div>

                <!-- Owner Specific Area (In Scroll) -->
                <div v-if="auth.user?.role === 'owner'" class="space-y-2 pt-4">
                    <p class="px-4 text-[9px] font-black text-slate-600 uppercase tracking-[0.4em] mb-4 italic">ADMINISTRASI</p>
                    <div class="space-y-1">
                        <router-link 
                            v-for="item in ownerNavigation" 
                            :key="item.name"
                            :to="item.path"
                            class="flex items-center gap-4 px-5 py-3.5 rounded-2xl transition-all duration-500 group relative"
                            :class="[
                                route.path.startsWith(item.path)
                                ? 'bg-blue-600/10 text-blue-400 shadow-[inset_0_0_0_1px_rgba(37,99,235,0.2)]' 
                                : 'text-slate-500 hover:text-slate-100 hover:bg-white/5'
                            ]"
                        >
                            <component :is="item.icon" class="w-5 h-5 transition-transform group-hover:scale-110" />
                            <span class="font-bold tracking-tight text-sm uppercase italic">{{ item.name }}</span>
                        </router-link>
                    </div>
                </div>
            </nav>

            <!-- Fixed Bottom Actions -->
            <div class="px-6 mt-auto pt-8 border-t border-white/5 space-y-2">
                <button 
                    @click="toggleLanguage"
                    class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl text-slate-600 hover:text-white hover:bg-white/5 transition-all duration-300 group"
                >
                    <Languages class="w-5 h-5 transition-transform group-hover:rotate-12" />
                    <span class="font-black text-[10px] tracking-widest uppercase">{{ locale === 'id' ? 'ID (Bahasa)' : 'EN (English)' }}</span>
                </button>
                <button 
                    @click="logout"
                    class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl text-rose-500/60 hover:text-rose-500 hover:bg-rose-500/5 transition-all duration-300 group"
                >
                    <LogOut class="w-5 h-5 transition-transform group-hover:-translate-x-1" />
                    <span class="font-black text-[10px] tracking-widest uppercase">{{ $t('menu.logout') }}</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto overflow-x-hidden px-6 py-8 lg:px-16 lg:py-16 relative z-10 w-full pb-24 lg:pb-12">
            <!-- Top Profile Corner (Desktop) -->
            <div class="hidden lg:flex items-center justify-end gap-6 mb-12 print:hidden">
                <div class="flex items-center gap-5 bg-white/[0.03] hover:bg-white/10 p-2.5 pr-6 border border-white/5 rounded-[28px] transition-all group pointer-events-auto shadow-xl">
                    <div class="w-12 h-12 bg-slate-900 rounded-2xl flex items-center justify-center text-blue-400 border border-white/5 group-hover:border-blue-500/50 transition-all shadow-inner">
                        <User class="w-7 h-7" />
                    </div>
                    <div class="text-left">
                        <p class="text-sm font-black text-white italic">{{ auth.user?.name || $t('common.authorized') }}</p>
                        <p class="text-[9px] text-blue-500 uppercase tracking-[0.3em] font-black mt-0.5">{{ auth.user?.role }}</p>
                    </div>
                </div>
            </div>

            <router-view v-slot="{ Component }">
                <transition 
                    name="page" 
                    mode="out-in"
                >
                    <component :is="Component" />
                </transition>
            </router-view>
        </main>
    </div>

    <!-- Mobile Drawer -->
    <div v-if="isSidebarOpen" class="lg:hidden fixed inset-0 z-[100]">
        <div @click="isSidebarOpen = false" class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>
        <div class="absolute inset-y-0 left-0 w-[85%] bg-slate-950 p-8 shadow-2xl animate-in slide-in-from-left duration-500 flex flex-col border-r border-white/5">
            <div class="flex items-center justify-between mb-12">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                        <Package class="text-white w-6 h-6" />
                    </div>
                    <span class="font-black text-xl text-white italic font-mono uppercase">{{ auth.user?.supplier?.name || $t('brand.name') }}</span>
                </div>
                <button @click="isSidebarOpen = false" class="p-2 text-slate-400"><X class="w-6 h-6" /></button>
            </div>
            
            <nav class="space-y-8 flex-1 overflow-y-auto pr-2 custom-sidebar-scroll">
                <div v-for="group in navigationGroups" :key="group.title" class="space-y-3">
                    <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest">{{ group.title }}</p>
                    <router-link 
                        v-for="item in group.items" 
                        :key="item.name"
                        :to="item.path"
                        @click="isSidebarOpen = false"
                        class="flex items-center gap-5 px-6 py-4.5 rounded-2xl transition-all"
                        :class="[route.path === item.path ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'text-slate-500 hover:bg-white/5']"
                    >
                        <component :is="item.icon" class="w-6 h-6" />
                        <span class="font-bold text-lg italic uppercase">{{ item.name }}</span>
                    </router-link>
                </div>
            </nav>

            <button @click="logout" class="mt-8 flex items-center gap-5 px-6 py-5 text-rose-500 bg-rose-500/5 hover:bg-rose-500/10 rounded-2xl transition-all font-black uppercase text-xs tracking-widest">
                <LogOut class="w-6 h-6" />
                <span>Terminasi Sesi</span>
            </button>
        </div>
    </div>

    <!-- Mobile Bottom Tab Bar – role-aware shortcuts -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-[90] bg-black/80 backdrop-blur-3xl border-t border-white/5 px-4 h-20 flex items-center justify-around shadow-[0_-10px_40px_rgba(0,0,0,0.5)] print:hidden">
        <router-link 
            v-for="item in bottomNavItems" 
            :key="item.name"
            :to="item.path"
            class="flex flex-col items-center gap-1.5 px-3 py-1.5 transition-all"
            :class="[route.path === item.path ? 'text-blue-500' : 'text-slate-600']"
        >
            <component :is="item.icon" class="w-6 h-6" />
            <span class="text-[8px] font-black uppercase tracking-widest">{{ item.name }}</span>
        </router-link>
    </nav>
</div>
</template>

<style>
/* Custom Sidebar Scrollbar */
.custom-sidebar-scroll::-webkit-scrollbar {
    width: 3px;
}
.custom-sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.custom-sidebar-scroll::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}
.custom-sidebar-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(59, 130, 246, 0.3);
}

.page-enter-active,
.page-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-enter-from {
    opacity: 0;
    transform: translateY(15px);
}

.page-leave-to {
    opacity: 0;
    transform: translateY(-15px);
}

@font-face {
  font-family: 'Outfit';
  src: url('https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap');
}

:root {
  font-family: 'Outfit', sans-serif;
}
</style>
