<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/api';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import { 
    Package, ShoppingCart, TrendingUp, 
    ArrowUpRight, Clock, ChevronRight,
    AlertCircle, Calendar, CheckCircle2, Loader2, DollarSign, Wallet, Truck, Box
} from 'lucide-vue-next';
import { formatCurrency } from '@/utils/format';

const auth = useAuthStore();
const { t } = useI18n();

const stats = ref({
    role: '',
    total_products: 0,
    total_customers: 0,
    total_orders: 0,
    total_revenue: 0,
    total_profit: 0,
    unpaid_invoices: 0,
    active_orders: 0,
    unpaid_debt: 0,
    orders_to_pick: 0,
    total_items_to_pick: 0,
    orders_to_deliver: 0,
    delivered_today: 0,
    pending_orders_count: 0,
    pending_payments_count: 0,
    month_profit: 0,
    cash_balance_bank: 0,
    cash_balance_cash: 0,
    total_cash_balance: 0,
    recent_orders: [] as any[],
    low_stock: {
        count: 0,
        products: [] as any[],
        threshold: 10
    }
});
const loading = ref(true);

const fetchStats = async () => {
    loading.value = true;
    try {
        const response = await api.get('/dashboard-stats');
        stats.value = Object.assign(stats.value, response.data);
    } catch (error) {
        console.error('Failed to fetch stats:', error);
    } finally {
        loading.value = false;
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'pending': return 'bg-amber-500/10 text-amber-500 border-amber-500/20';
        case 'confirmed': return 'bg-blue-500/10 text-blue-500 border-blue-500/20';
        case 'on_delivery': return 'bg-purple-500/10 text-purple-500 border-purple-500/20';
        case 'delivered': return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20';
        default: return 'bg-slate-500/10 text-slate-500 border-slate-500/20';
    }
};

onMounted(fetchStats);
</script>

<template>
<div class="space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Welcome Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-4xl font-black tracking-tight text-white mb-2">
                {{ t('dashboard.title') }}
            </h1>
            <p class="text-slate-500 flex items-center gap-2">
                {{ t('dashboard.welcome') }}, <span class="text-blue-400 font-bold">{{ auth.user?.name }}</span>!
            </p>
        </div>
        <div class="flex items-center gap-3 bg-white/5 p-2 rounded-2xl border border-white/5">
            <div class="w-10 h-10 bg-blue-600/20 text-blue-400 rounded-xl flex items-center justify-center">
                <Calendar class="w-5 h-5" />
            </div>
            <div class="pr-4">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ t('common.loading').split(' ')[0] }}</p>
                <p class="text-xs font-bold text-white">{{ new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }}</p>
            </div>
        </div>
    </div>

    <!-- Pending Orders Notification (Admin/Owner) -->
    <div v-if="(stats.role === 'admin' || stats.role === 'owner') && stats.pending_orders_count > 0" 
         class="bg-amber-500/10 border border-amber-500/30 p-6 rounded-[32px] flex flex-col md:flex-row items-center justify-between gap-6 shadow-lg shadow-amber-500/5 animate-in slide-in-from-top-4">
        <div class="flex items-center gap-5 text-amber-400">
            <div class="w-14 h-14 bg-amber-500/20 rounded-2xl flex items-center justify-center animate-pulse border border-amber-500/20">
                <AlertCircle class="w-7 h-7" />
            </div>
            <div>
                <h3 class="text-xl font-black tracking-tight text-white mb-0.5 uppercase italic">{{ $t('dashboard.pending_work') }}</h3>
                <p class="text-xs font-bold text-amber-300 uppercase tracking-widest">{{ $t('dashboard.pending_orders', { count: stats.pending_orders_count }) }}</p>
            </div>
        </div>
        <button @click="$router.push('/orders')" class="bg-amber-500 hover:bg-amber-400 text-white px-8 py-4 rounded-2xl font-black uppercase tracking-[0.2em] text-[10px] shadow-xl shadow-amber-500/20 flex items-center justify-center gap-2 active:scale-95 transition-all w-full md:w-auto">
            {{ $t('dashboard.process_now') }} <ArrowUpRight class="w-4 h-4" />
        </button>
    </div>

    <!-- Pending Payment Approvals Notification (OWNER ONLY) -->
    <div v-if="stats.role === 'owner' && stats.pending_payments_count > 0" 
         class="bg-emerald-500/10 border border-emerald-500/30 p-6 rounded-[32px] flex flex-col md:flex-row items-center justify-between gap-6 shadow-lg shadow-emerald-500/5 animate-in slide-in-from-top-4">
        <div class="flex items-center gap-5 text-emerald-400">
            <div class="w-14 h-14 bg-emerald-500/20 rounded-2xl flex items-center justify-center animate-pulse border border-emerald-500/20">
                <CheckCircle2 class="w-7 h-7" />
            </div>
            <div>
                <h3 class="text-xl font-black tracking-tight text-white mb-0.5 uppercase italic">{{ $t('dashboard.payment_verification') }}</h3>
                <p class="text-xs font-bold text-emerald-300 uppercase tracking-widest">{{ $t('dashboard.pending_transfers', { count: stats.pending_payments_count }) }}</p>
            </div>
        </div>
        <button @click="$router.push('/invoices')" class="bg-emerald-600 hover:bg-emerald-500 text-white px-8 py-4 rounded-2xl font-black uppercase tracking-[0.2em] text-[10px] shadow-xl shadow-emerald-500/20 flex items-center justify-center gap-2 active:scale-95 transition-all w-full md:w-auto">
            {{ $t('dashboard.open_cashflow') }} <ArrowUpRight class="w-4 h-4" />
        </button>
    </div>
    <!-- Critical Stock Notification (Admin/Owner/Warehouse) -->
    <div v-if="(stats.role === 'admin' || stats.role === 'owner' || stats.role === 'warehouse') && stats.low_stock.count > 0" 
         class="bg-rose-500/10 border border-rose-500/30 p-6 rounded-[32px] flex flex-col md:flex-row items-center justify-between gap-6 shadow-lg shadow-rose-500/5 animate-in slide-in-from-top-4">
        <div class="flex items-center gap-5 text-rose-400">
            <div class="w-14 h-14 bg-rose-500/20 rounded-2xl flex items-center justify-center animate-pulse border border-rose-500/20">
                <Package class="w-7 h-7" />
            </div>
            <div>
                <h3 class="text-xl font-black tracking-tight text-white mb-0.5 uppercase italic">{{ $t('dashboard.inventory_warning') }}</h3>
                <p class="text-xs font-bold text-rose-400 uppercase tracking-widest">{{ $t('dashboard.critical_stock', { count: stats.low_stock.count }) }}</p>
            </div>
        </div>
        <button @click="$router.push('/reports')" class="bg-rose-600 hover:bg-rose-500 text-white px-8 py-4 rounded-2xl font-black uppercase tracking-[0.2em] text-[10px] shadow-xl shadow-rose-500/20 flex items-center justify-center gap-2 active:scale-95 transition-all w-full md:w-auto">
            {{ $t('dashboard.view_stock_report') }} <ArrowUpRight class="w-4 h-4" />
        </button>
    </div>

    <!-- Stats Grid: OWNER (Financial Focused) -->
    <div v-if="stats.role === 'owner'" class="space-y-8">
        <!-- Main Financial Rows -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Profit Card (MTD) -->
            <div class="bg-gradient-to-br from-emerald-600 to-teal-700 p-8 rounded-[40px] shadow-xl shadow-emerald-500/10 relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-8 opacity-20 group-hover:rotate-12 transition-transform duration-500"><TrendingUp class="w-20 h-20 text-white" /></div>
                <div class="relative z-10">
                    <p class="text-emerald-100 font-bold uppercase tracking-widest text-[10px] mb-4">{{ $t('dashboard.profit_mtd') }}</p>
                    <h2 class="text-4xl font-black text-white mb-2 font-mono italic tracking-tighter">{{ formatCurrency(stats.month_profit) }}</h2>
                    <div class="mt-4 flex items-center gap-2 text-emerald-200 text-[10px] font-black uppercase tracking-widest">
                        <CheckCircle2 class="w-4 h-4" /> {{ $t('dashboard.realtime_margin') }}
                    </div>
                </div>
            </div>

            <!-- Cash Balance Total -->
            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 p-8 rounded-[40px] shadow-xl shadow-blue-600/10 relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-8 opacity-20 group-hover:-rotate-12 transition-transform duration-500"><Wallet class="w-20 h-20 text-white" /></div>
                <div class="relative z-10">
                    <p class="text-blue-100 font-bold uppercase tracking-widest text-[10px] mb-4">{{ $t('dashboard.total_cash_living') }}</p>
                    <h2 class="text-4xl font-black text-white mb-2 font-mono italic tracking-tighter">{{ formatCurrency(stats.total_cash_balance) }}</h2>
                    <div class="mt-4 flex items-center gap-4 text-white/60 text-[9px] font-black uppercase tracking-widest pt-4 border-t border-white/10">
                        <div class="flex items-center gap-1"><div class="w-1.5 h-1.5 rounded-full bg-emerald-400"></div> {{ $t('finance.bank') }}: {{ formatCurrency(stats.cash_balance_bank) }}</div>
                        <div class="flex items-center gap-1"><div class="w-1.5 h-1.5 rounded-full bg-amber-400"></div> {{ $t('finance.cash') }}: {{ formatCurrency(stats.cash_balance_cash) }}</div>
                    </div>
                </div>
            </div>

            <!-- Revenue Total -->
            <div class="bg-white/5 border border-white/10 p-8 rounded-[40px] relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform"><ShoppingCart class="w-32 h-32" /></div>
                <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px] mb-4">Total Omzet Penjualan</p>
                <h2 class="text-3xl font-black text-white mb-2 font-mono">{{ formatCurrency(stats.total_revenue) }}</h2>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-2">{{ stats.total_orders }} Pesanan Sukses</p>
            </div>
        </div>

        <!-- Secondary Stats Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div @click="$router.push('/invoices')" class="bg-white/5 border border-white/10 p-8 rounded-[32px] hover:bg-white/[0.08] transition-all relative overflow-hidden group cursor-pointer">
                <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px] mb-4">Piutang Pelanggan (Hutang Aktif)</p>
                <h2 class="text-3xl font-black text-white mb-2 font-mono text-amber-500 underline decoration-amber-500/30 underline-offset-8">{{ formatCurrency(stats.unpaid_invoices) }}</h2>
                <p class="text-[9px] text-slate-600 font-bold uppercase tracking-widest mt-4 flex items-center gap-2">Monitor Tagihan <ChevronRight class="w-3 h-3"/> </p>
            </div>

            <div class="bg-white/5 border border-white/10 p-8 rounded-[32px] hover:bg-white/[0.08] transition-all relative overflow-hidden group">
                <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px] mb-4">Total Keuntungan (Lifetime)</p>
                <h2 class="text-3xl font-black text-slate-400 mb-2 font-mono">{{ formatCurrency(stats.total_profit) }}</h2>
            </div>

            <div @click="$router.push('/products')" class="bg-white/5 border border-white/10 p-8 rounded-[32px] hover:bg-white/[0.08] transition-all relative overflow-hidden group cursor-pointer">
                <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px] mb-4">Inventory SKUs</p>
                <h2 class="text-3xl font-black text-white mb-2">{{ stats.total_products }} <span class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-2">Produk Aktif</span></h2>
            </div>
        </div>
    </div>

    <!-- Stats Grid: ADMIN (Operational Focused) -->
    <div v-else-if="stats.role === 'admin'" class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Revenue (Operational Visibility) -->
            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 p-8 rounded-[32px] shadow-xl shadow-blue-600/20 relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-8 opacity-20 group-hover:scale-125 transition-transform duration-500"><TrendingUp class="w-24 h-24 text-white" /></div>
                <p class="text-blue-100 font-bold uppercase tracking-widest text-[10px] mb-4">Total Penjualan</p>
                <h2 class="text-3xl font-black text-white mb-2 font-mono">{{ formatCurrency(stats.total_revenue) }}</h2>
            </div>

            <!-- Total Orders -->
            <div @click="$router.push('/orders')" class="bg-white/5 border border-white/10 p-8 rounded-[32px] hover:bg-white/[0.08] transition-all relative overflow-hidden group cursor-pointer">
                <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform"><ShoppingCart class="w-32 h-32" /></div>
                <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px] mb-4">Volume Pesanan</p>
                <h2 class="text-4xl font-black text-white mb-2">{{ stats.total_orders }}</h2>
                <p class="text-xs font-bold text-slate-600 uppercase tracking-widest">Lifetime Records</p>
            </div>

            <!-- Total Products -->
            <div @click="$router.push('/products')" class="bg-white/5 border border-white/10 p-8 rounded-[32px] hover:bg-white/[0.08] transition-all relative overflow-hidden group cursor-pointer">
                <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform"><Package class="w-32 h-32" /></div>
                <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px] mb-4">{{ $t('dashboard.catalog_items') }}</p>
                <h2 class="text-4xl font-black text-white mb-2">{{ stats.total_products }}</h2>
                <p class="text-xs font-bold text-slate-600 uppercase tracking-widest">{{ $t('dashboard.registered_products') }}</p>
            </div>

            <!-- Total Customers -->
            <div @click="$router.push('/customers')" class="bg-white/5 border border-white/10 p-8 rounded-[32px] hover:bg-white/[0.08] transition-all relative overflow-hidden group cursor-pointer">
                <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform"><AlertCircle class="w-32 h-32" /></div>
                <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px] mb-4">{{ $t('dashboard.customers_count') }}</p>
                <h2 class="text-4xl font-black text-white mb-2">{{ stats.total_customers }}</h2>
                <p class="text-xs font-bold text-slate-600 uppercase tracking-widest">{{ $t('dashboard.active_customers') }}</p>
            </div>
        </div>
    </div>

    <!-- Stats Grid: Customer -->
    <div v-else-if="stats.role === 'customer'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div @click="$router.push('/invoices')" class="bg-gradient-to-br from-red-500/20 to-orange-500/20 border border-red-500/30 p-8 rounded-[32px] hover:bg-red-500/30 transition-all relative overflow-hidden group cursor-pointer">
            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform"><DollarSign class="w-32 h-32 text-red-500" /></div>
            <p class="text-red-300 font-bold uppercase tracking-widest text-[10px] mb-4">Unpaid Debt</p>
            <h2 class="text-3xl font-black text-red-500 mb-2 font-mono">{{ formatCurrency(stats.unpaid_debt) }}</h2>
            <p class="text-red-400 text-xs font-bold leading-none">Please pay your pending invoices.</p>
        </div>
        
        <div class="bg-white/5 border border-white/10 p-8 rounded-[32px] hover:bg-white/[0.08] transition-all relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform"><Box class="w-32 h-32" /></div>
            <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px] mb-4">Active Orders</p>
            <h2 class="text-4xl font-black text-white mb-2">{{ stats.active_orders }}</h2>
            <p class="text-emerald-400 text-xs font-bold leading-none">On the way or Processing</p>
        </div>

        <div class="bg-white/5 border border-white/10 p-8 rounded-[32px] hover:bg-white/[0.08] transition-all relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform"><ShoppingCart class="w-32 h-32" /></div>
            <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px] mb-4">Total Orders</p>
            <h2 class="text-4xl font-black text-white mb-2">{{ stats.total_orders }}</h2>
            <p class="text-slate-400 text-xs font-bold leading-none">Lifetime record</p>
        </div>
    </div>

    <!-- Stats Grid: Warehouse -->
    <div v-else-if="stats.role === 'warehouse'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-indigo-600/20 border border-indigo-500/30 p-8 rounded-[32px] relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform"><Box class="w-32 h-32 text-indigo-400" /></div>
            <p class="text-indigo-400 font-bold uppercase tracking-widest text-[10px] mb-4">Orders To Pick (Packing Queue)</p>
            <h2 class="text-5xl font-black text-white mb-2">{{ stats.orders_to_pick }}</h2>
        </div>
        <div class="bg-white/5 border border-white/10 p-8 rounded-[32px] relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform"><Package class="w-32 h-32" /></div>
            <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px] mb-4">Total Item Objects (Qty)</p>
            <h2 class="text-5xl font-black text-white mb-2">{{ stats.total_items_to_pick }}</h2>
        </div>
    </div>

    <!-- Stats Grid: Driver -->
    <div v-else-if="stats.role === 'driver'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-amber-500/20 border border-amber-500/30 p-8 rounded-[32px] relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform"><Truck class="w-32 h-32 text-amber-500" /></div>
            <p class="text-amber-400 font-bold uppercase tracking-widest text-[10px] mb-4">Deliveries Left To Do</p>
            <h2 class="text-5xl font-black text-white mb-2">{{ stats.orders_to_deliver }}</h2>
        </div>
        <div class="bg-emerald-500/20 border border-emerald-500/30 p-8 rounded-[32px] relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform"><CheckCircle2 class="w-32 h-32 text-emerald-500" /></div>
            <p class="text-emerald-400 font-bold uppercase tracking-widest text-[10px] mb-4">Delivered Today</p>
            <h2 class="text-5xl font-black text-white mb-2">{{ stats.delivered_today }}</h2>
        </div>
    </div>


    <!-- Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Orders -->
        <div class="lg:col-span-2 space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold text-white flex items-center gap-3">
                    <Clock class="w-6 h-6 text-blue-500" />
                    {{ t('dashboard.recent') }} / {{ $t('dashboard.tasks_queue') }}
                </h3>
                <router-link to="/orders" class="text-sm font-bold text-blue-400 hover:text-blue-300 transition-colors">{{ t('dashboard.view_all') }}</router-link>
            </div>

            <div class="bg-white/[0.02] border border-white/5 rounded-[40px] overflow-hidden">
                <div v-if="loading" class="p-12 text-center animate-pulse">
                    <Loader2 class="w-8 h-8 animate-spin mx-auto text-slate-700 mb-4" />
                    <p class="text-slate-500">{{ t('common.loading') }}</p>
                </div>
                <div v-else-if="stats.recent_orders.length === 0" class="p-20 text-center">
                    <ShoppingCart class="w-16 h-16 mx-auto text-slate-800 mb-4" />
                    <p class="text-xl text-slate-600">{{ t('common.no_data') }}</p>
                </div>
                <div v-else class="divide-y divide-white/5">
                    <div v-for="order in stats.recent_orders" :key="order.id" @click="$router.push('/orders')" class="p-6 flex items-center justify-between hover:bg-white/[0.03] transition-colors group cursor-pointer">
                        <div class="flex items-center gap-5">
                            <div class="w-12 h-12 bg-slate-900 rounded-xl flex items-center justify-center text-slate-400 group-hover:text-blue-400 transition-colors">
                                <ShoppingCart class="w-6 h-6" />
                            </div>
                            <div>
                                <p class="font-bold text-white mb-0.5">{{ order.order_number }}</p>
                                <p class="text-xs text-slate-500">{{ order.customer?.name }} • {{ new Date(order.order_date).toLocaleDateString() }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-8">
                            <div class="text-right hidden sm:block">
                                <p v-if="stats.role !== 'warehouse' && stats.role !== 'driver'" class="text-sm font-bold text-white font-mono uppercase tracking-tighter">{{ formatCurrency(order.total_amount) }}</p>
                                <p v-else class="text-xs font-bold text-slate-500 uppercase tracking-tighter">(LOCKED)</p>
                                <span :class="getStatusColor(order.status)" class="text-[9px] font-black uppercase tracking-widest block mt-1">
                                    {{ order.status }}
                                </span>
                            </div>
                            <ChevronRight class="w-5 h-5 text-slate-700 group-hover:text-white transition-all transform group-hover:translate-x-1" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panes -->
        <div class="space-y-6">
            <h3 class="text-2xl font-bold text-white flex items-center gap-3">
                <AlertCircle class="w-6 h-6 text-amber-500" />
                {{ $t('dashboard.financial_actions') }}
            </h3>
            
            <div class="space-y-4">
                <button v-if="stats.role === 'customer' || stats.role === 'admin' || stats.role === 'owner'" @click="$router.push('/orders')" class="w-full bg-blue-600 hover:bg-blue-500 p-6 rounded-[32px] text-left transition-all shadow-lg shadow-blue-600/20 group">
                    <p class="text-xs font-bold text-blue-100 uppercase tracking-widest mb-1">{{ t('common.new') }}</p>
                    <h4 class="text-xl font-bold text-white">{{ t('dashboard.create_order') }}</h4>
                    <div class="mt-4 flex items-center gap-2 text-blue-200 text-xs font-bold">
                        <span>{{ $t('dashboard.start_order') }}</span>
                        <ArrowUpRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                    </div>
                </button>

                <!-- Financial Quick Actions (Owner/Admin) -->
                <div v-if="stats.role === 'owner' || stats.role === 'admin'" class="space-y-3">
                    <button @click="$router.push('/invoices')" class="w-full flex items-center justify-between p-5 bg-emerald-600/10 hover:bg-emerald-600 group border border-emerald-500/20 rounded-2xl transition-all">
                        <span class="text-xs font-black uppercase text-emerald-500 group-hover:text-white tracking-widest">{{ $t('dashboard.verify_payment') }}</span>
                        <CheckCircle2 class="w-5 h-5 text-emerald-500 group-hover:text-white" />
                    </button>
                    <button @click="$router.push('/cash-flow')" class="w-full flex items-center justify-between p-5 bg-white/5 hover:bg-white/10 group border border-white/5 rounded-2xl transition-all">
                        <span class="text-xs font-black uppercase text-slate-400 group-hover:text-white tracking-widest">{{ $t('dashboard.input_cash') }}</span>
                        <Plus class="w-5 h-5 text-slate-600 group-hover:text-white" />
                    </button>
                    <button @click="$router.push('/reports')" class="w-full flex items-center justify-between p-5 bg-white/5 hover:bg-white/10 group border border-white/5 rounded-2xl transition-all">
                        <span class="text-xs font-black uppercase text-slate-400 group-hover:text-white tracking-widest">{{ $t('dashboard.profit_analysis') }}</span>
                        <TrendingUp class="w-5 h-5 text-slate-600 group-hover:text-white" />
                    </button>
                </div>

                <!-- Inventory Widget for Owner/Admin Only -->
                <div v-if="!stats.role || stats.role === 'admin' || stats.role === 'owner' || stats.role === 'warehouse'" class="bg-white/5 border border-white/10 p-8 rounded-[32px]">
                    <h4 class="font-bold text-white mb-4">{{ t('dashboard.inventory_health') }}</h4>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-400">Status Stok</span>
                            <span v-if="stats.low_stock.count > 0" class="text-rose-400 font-bold uppercase tracking-widest text-[10px]">{{ stats.low_stock.count }} Item Kritis</span>
                            <span v-else class="text-emerald-400 font-bold tracking-widest text-[10px] uppercase italic">{{ t('dashboard.good') }}</span>
                        </div>
                        <div class="w-full bg-slate-800 h-2 rounded-full overflow-hidden">
                            <div 
                                :class="stats.low_stock.count > 0 ? 'bg-rose-500' : 'bg-emerald-500'" 
                                class="h-full transition-all duration-1000"
                                :style="{ width: stats.low_stock.count > 0 ? '40%' : '100%' }"
                            ></div>
                        </div>
                        
                        <div v-if="stats.low_stock.count > 0" class="space-y-2 mt-4 pt-4 border-t border-white/5">
                            <div v-for="p in stats.low_stock.products" :key="p.name" class="flex justify-between items-center bg-white/[0.02] p-2 rounded-lg">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tight truncate max-w-[120px]">{{ p.name }}</span>
                                <span class="text-[10px] font-black text-rose-500 font-mono">{{ p.qty }}</span>
                            </div>
                        </div>

                        <p class="text-[10px] text-slate-500 leading-relaxed font-medium">
                            {{ stats.low_stock.count > 0 ? 'Segera lakukan restock untuk produk kritis di atas.' : 'Seluruh stok produk Anda saat ini dalam kondisi aman.' }}
                        </p>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>
</template>
