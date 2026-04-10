<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/api/axios';
import { 
    Users, CreditCard, Activity, TrendingUp, 
    RefreshCw, ChevronRight, CheckCircle2 
} from 'lucide-vue-next';

const stats = ref<any>({
    total_tenants: 0,
    active_tenants: 0,
    expired_tenants: 0,
    pending_payments: 0,
    total_revenue: 0
});
const loading = ref(true);

const fetchStats = async () => {
    loading.value = true;
    try {
        const { data } = await api.get('/saas/stats');
        stats.value = data;
    } catch (error) {
        console.error('Failed to fetch SaaS stats', error);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchStats);

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
};
</script>

<template>
<div class="p-8">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">SaaS Overview</h1>
            <p class="text-slate-500 mt-1">Pantau performa platform Nutri-Chain Anda secara global.</p>
        </div>
        <button @click="fetchStats" class="p-3 bg-white/5 hover:bg-white/10 rounded-xl text-white transition-all border border-white/10">
            <RefreshCw :class="{'animate-spin': loading}" class="w-5 h-5" />
        </button>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <!-- Total Tenants -->
        <div class="bg-slate-900/50 border border-white/10 rounded-2xl p-6 relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-600/10 rounded-full blur-3xl group-hover:bg-blue-600/20 transition-all"></div>
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-blue-500/20 rounded-xl">
                    <Users class="w-6 h-6 text-blue-400" />
                </div>
                <h3 class="text-slate-400 font-bold text-xs uppercase tracking-widest">Total Tenant</h3>
            </div>
            <p class="text-3xl font-black text-white">{{ stats.total_tenants }}</p>
        </div>

        <!-- Active Tenants -->
        <div class="bg-slate-900/50 border border-white/10 rounded-2xl p-6 relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-green-500/10 rounded-full blur-3xl group-hover:bg-green-500/20 transition-all"></div>
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-green-500/20 rounded-xl">
                    <Activity class="w-6 h-6 text-green-400" />
                </div>
                <h3 class="text-slate-400 font-bold text-xs uppercase tracking-widest">Toko Aktif</h3>
            </div>
            <p class="text-3xl font-black text-white">{{ stats.active_tenants }}</p>
        </div>

        <!-- Pending Payments -->
        <div class="bg-slate-900/50 border border-white/10 rounded-2xl p-6 relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-500/10 rounded-full blur-3xl group-hover:bg-amber-500/20 transition-all"></div>
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-amber-500/20 rounded-xl">
                    <CreditCard class="w-6 h-6 text-amber-400" />
                </div>
                <h3 class="text-slate-400 font-bold text-xs uppercase tracking-widest">Antrean Bayar</h3>
            </div>
            <p class="text-3xl font-black text-white">{{ stats.pending_payments }}</p>
        </div>

        <!-- Revenue -->
        <div class="bg-slate-900/50 border border-white/10 rounded-2xl p-6 relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-500/10 rounded-full blur-3xl group-hover:bg-indigo-500/20 transition-all"></div>
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-indigo-500/20 rounded-xl">
                    <TrendingUp class="w-6 h-6 text-indigo-400" />
                </div>
                <h3 class="text-slate-400 font-bold text-xs uppercase tracking-widest">Pendapatan SaaS</h3>
            </div>
            <p class="text-2xl font-black text-white">{{ formatCurrency(stats.total_revenue) }}</p>
        </div>
    </div>

    <!-- Layout Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Newest Tenants -->
        <div class="bg-slate-900/50 border border-white/10 rounded-3xl p-8 backdrop-blur-xl">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-xl font-bold text-white">Tenant Terbaru</h2>
                <router-link to="/saas/tenants" class="text-blue-500 text-sm font-bold hover:underline">Lihat Semua</router-link>
            </div>
            
            <div class="space-y-4">
                <div v-if="loading" class="animate-pulse space-y-4">
                    <div v-for="i in 3" :key="i" class="h-20 bg-white/5 rounded-2xl"></div>
                </div>
                <div v-else class="text-center py-10">
                    <ShieldAlert class="w-12 h-12 text-slate-800 mx-auto mb-4" />
                    <p class="text-slate-600 font-bold">Data Tenant belum tersedia.</p>
                </div>
            </div>
        </div>

        <!-- Pending Verification Alert -->
        <div class="bg-slate-900/50 border border-white/10 rounded-3xl p-8 backdrop-blur-xl">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-xl font-bold text-white">Verifikasi Tertunda</h2>
                <router-link to="/saas/payments" class="text-blue-500 text-sm font-bold hover:underline">Proses Sekarang</router-link>
            </div>

            <div v-if="stats.pending_payments > 0" class="p-6 bg-amber-500/10 border border-amber-500/30 rounded-2xl flex items-center gap-6 group hover:bg-amber-500/20 transition-all cursor-pointer">
                <div class="w-12 h-12 bg-amber-500/20 rounded-xl flex items-center justify-center">
                    <CreditCard class="w-6 h-6 text-amber-500" />
                </div>
                <div class="flex-1">
                    <h4 class="text-white font-bold">{{ stats.pending_payments }} Pembayaran Menunggu</h4>
                    <p class="text-slate-400 text-sm">Segera ulas bukti transfer untuk memperpanjang lisensi.</p>
                </div>
                <ChevronRight class="text-slate-600 group-hover:text-amber-500 transition-all" />
            </div>
            
            <div v-else class="text-center py-10 opacity-40">
                <CheckCircle2 class="w-12 h-12 text-slate-800 mx-auto mb-4" />
                <p class="text-slate-600 font-bold">Semua pembayaran bersih.</p>
            </div>
        </div>
    </div>
</div>
</template>
