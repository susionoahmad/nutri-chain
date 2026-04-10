<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue';
import api from '@/api';
import { useAuthStore } from '@/stores/auth';
import { 
    Wallet, ArrowUpRight, ArrowDownLeft, 
    Calendar, TrendingUp, TrendingDown, Landmark, 
    Banknote, Plus, Loader2
} from 'lucide-vue-next';
import { formatCurrency } from '@/utils/format';
import { format } from 'date-fns';
import { useToast } from 'vue-toastification';

const auth = useAuthStore();
const toast = useToast();
const loading = ref(true);
const saving = ref(false);

const data = ref({
    history: [] as any[],
    summary: {
        cash: 0,
        bank: 0,
        total: 0
    }
});

const form = reactive({
    type: 'out',
    category: 'expense',
    amount: '',
    account_type: 'cash',
    note: '',
    transaction_date: format(new Date(), 'yyyy-MM-dd')
});

const showModal = ref(false);

const fetchCashFlow = async () => {
    loading.value = true;
    try {
        const response = await api.get('/cash-flow');
        data.value = response.data;
    } catch (error) {
        console.error('Failed to fetch cash flow:', error);
    } finally {
        loading.value = false;
    }
};

const handleStore = async () => {
    saving.value = true;
    try {
        await api.post('/cash-flow', {
            ...form,
            amount: parseFloat(form.amount.toString())
        });
        toast.success("Transaksi berhasil dicatat!");
        showModal.value = false;
        // Reset form
        Object.assign(form, {
            type: 'out',
            category: 'expense',
            amount: '',
            account_type: 'cash',
            note: '',
            transaction_date: format(new Date(), 'yyyy-MM-dd')
        });
        fetchCashFlow();
    } catch (error: any) {
        toast.error(error.response?.data?.message || "Gagal mencatat transaksi.");
    } finally {
        saving.value = false;
    }
};

const getCategoryLabel = (cat: string) => {
    const labels: Record<string, string> = {
        'sales': 'Penjualan',
        'purchase': 'Pembelian Produk',
        'initial_balance': 'Saldo Awal',
        'expense': 'Biaya Operasional',
        'adjustment': 'Penyesuaian'
    };
    return labels[cat] || cat;
};

onMounted(() => fetchCashFlow());
</script>

<template>
<div class="space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-4xl font-black tracking-tight text-white mb-2 flex items-center gap-4">
                <div class="w-12 h-12 bg-emerald-600/20 text-emerald-400 rounded-2xl flex items-center justify-center border border-emerald-500/20">
                    <Wallet class="w-6 h-6" />
                </div>
                Arus Kas (Cash Flow)
            </h1>
            <p class="text-slate-500">
                Kelola aliran dana masuk dan keluar untuk menjaga kesehatan finansial bisnis Anda.
            </p>
        </div>
        
        <div class="flex gap-3">
            <button 
                @click="showModal = true"
                class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-4 rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-xl shadow-blue-500/20 flex items-center gap-2 transition-all active:scale-95"
            >
                <Plus class="w-4 h-4" /> Catat Manual
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Card -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 border border-white/5 p-8 rounded-[40px] shadow-2xl relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:rotate-12 transition-transform duration-700 scale-150">
                <Wallet class="w-24 h-24 text-white" />
            </div>
            <div class="relative z-10">
                <p class="text-slate-500 font-black uppercase tracking-[0.2em] text-[10px] mb-4">Total Saldo (Global)</p>
                <h2 class="text-4xl font-black text-white mb-2 italic tracking-tighter">{{ formatCurrency(data.summary.total) }}</h2>
                <div class="mt-4 flex items-center gap-2 text-blue-400 text-[10px] font-black uppercase tracking-widest">
                    <TrendingUp class="w-4 h-4" /> Update Real-time
                </div>
            </div>
        </div>

        <!-- Bank Card -->
        <div v-if="auth.user?.role === 'owner'" class="bg-white/[0.02] border border-white/5 p-8 rounded-[40px] relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-8 opacity-5">
                <Landmark class="w-24 h-24" />
            </div>
            <p class="text-slate-500 font-black uppercase tracking-[0.2em] text-[10px] mb-4">Saldo Bank</p>
            <h2 class="text-3xl font-black text-white italic tracking-tighter">{{ formatCurrency(data.summary.bank) }}</h2>
            <div class="mt-4 flex items-center gap-2 text-slate-600 text-[9px] font-black uppercase tracking-widest">
                <Landmark class="w-3.5 h-3.5" /> Rekening Utama
            </div>
        </div>

        <!-- Cash Card -->
        <div class="bg-white/[0.02] border border-white/5 p-8 rounded-[40px] relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-8 opacity-5">
                <Banknote class="w-24 h-24" />
            </div>
            <p class="text-slate-500 font-black uppercase tracking-[0.2em] text-[10px] mb-4">Kas Tunai</p>
            <h2 class="text-3xl font-black text-emerald-400 italic tracking-tighter">{{ formatCurrency(data.summary.cash) }}</h2>
            <div class="mt-4 flex items-center gap-2 text-slate-600 text-[9px] font-black uppercase tracking-widest">
                <Banknote class="w-3.5 h-3.5" /> Dana Operasional
            </div>
        </div>
    </div>

    <!-- History Table -->
    <div class="space-y-6">
        <h3 class="text-xl font-bold text-white flex items-center gap-3">
            <Calendar class="w-6 h-6 text-slate-500" />
            Riwayat Transaksi Terakhir
        </h3>

        <div class="bg-white/[0.02] border border-white/5 rounded-[40px] overflow-hidden shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/5 bg-white/[0.01]">
                            <th class="p-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Waktu & Akun</th>
                            <th class="p-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Kategori</th>
                            <th class="p-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Nominal</th>
                            <th class="p-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr v-if="loading" v-for="i in 5" :key="i" class="animate-pulse">
                            <td v-for="j in 4" :key="j" class="p-8">
                                <div class="h-4 bg-white/5 rounded-full w-2/3 mb-2"></div>
                                <div class="h-3 bg-white/5 rounded-full w-1/2"></div>
                            </td>
                        </tr>

                        <tr v-else-if="data.history.length === 0">
                            <td colspan="4" class="p-20 text-center text-slate-500 uppercase text-[10px] font-black tracking-widest">
                                Belum ada riwayat transaksi finansial.
                            </td>
                        </tr>

                        <tr v-for="item in data.history" :key="item.id" class="hover:bg-white/[0.03] transition-colors group">
                            <td class="p-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-slate-900 rounded-xl flex items-center justify-center text-slate-500 group-hover:text-white transition-colors border border-white/5 uppercase text-[9px] font-black">
                                        {{ item.account_type }}
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-white tracking-widest uppercase">
                                            {{ format(new Date(item.transaction_date), 'dd MMM yyyy') }}
                                        </p>
                                        <p class="text-[9px] text-slate-600 font-bold tracking-widest uppercase mt-0.5">
                                            Recorded at {{ format(new Date(item.created_at), 'HH:mm') }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-6">
                                <span class="px-3 py-1.5 bg-white/5 border border-white/5 rounded-xl text-[9px] font-black uppercase tracking-widest text-slate-400">
                                    {{ getCategoryLabel(item.category) }}
                                </span>
                            </td>
                            <td class="p-6">
                                <div class="flex items-center gap-2">
                                    <div :class="item.type === 'in' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'" class="p-1.5 rounded-lg border border-current opacity-30">
                                        <ArrowUpRight v-if="item.type === 'in'" class="w-3 h-3" />
                                        <ArrowDownLeft v-else class="w-3 h-3" />
                                    </div>
                                    <p :class="item.type === 'in' ? 'text-emerald-400' : 'text-rose-400'" class="text-lg font-black italic tracking-tighter">
                                        {{ item.type === 'in' ? '+' : '-' }}{{ formatCurrency(item.amount) }}
                                    </p>
                                </div>
                            </td>
                            <td class="p-6">
                                <div class="max-w-[300px]">
                                    <p class="text-xs text-slate-400 leading-relaxed font-medium line-clamp-2 italic group-hover:line-clamp-none transition-all">
                                        "{{ item.note || '-' }}"
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Catat Manual -->
    <div v-if="showModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-xl" @click="showModal = false"></div>
        <div class="relative w-full max-w-xl bg-slate-900 border border-white/10 rounded-[48px] shadow-2xl overflow-hidden animate-in zoom-in-95 duration-300">
            <div class="p-8 sm:p-10 space-y-6 sm:space-y-8 max-h-[90vh] overflow-y-auto scrollbar-hide">
                <div>
                    <h3 class="text-2xl font-black text-white uppercase italic tracking-tight">Catat Transaksi Manual</h3>
                    <p class="text-slate-500 text-sm font-medium mt-2">Input pengeluaran operasional atau penyesuaian kas.</p>
                </div>

                <div class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <button 
                            @click="form.type = 'in'; form.category = 'initial_balance'"
                            :class="form.type === 'in' ? 'bg-emerald-600/20 border-emerald-500 text-emerald-400' : 'bg-white/5 border-transparent text-slate-500'"
                            class="p-4 rounded-2xl border-2 transition-all flex flex-col items-center gap-2 group"
                        >
                            <TrendingUp class="w-6 h-6 transition-transform group-hover:-translate-y-1" />
                            <span class="text-[10px] font-black uppercase tracking-widest">Uang Masuk</span>
                        </button>
                        <button 
                            @click="form.type = 'out'; form.category = 'expense'"
                            :class="form.type === 'out' ? 'bg-rose-600/20 border-rose-500 text-rose-400' : 'bg-white/5 border-transparent text-slate-500'"
                            class="p-4 rounded-2xl border-2 transition-all flex flex-col items-center gap-2 group"
                        >
                            <TrendingDown class="w-6 h-6 transition-transform group-hover:translate-y-1" />
                            <span class="text-[10px] font-black uppercase tracking-widest">Uang Keluar</span>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-2">Akun Dana</label>
                                <select v-model="form.account_type" class="w-full bg-white/5 border border-white/5 rounded-2xl py-4 px-4 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all font-medium appearance-none">
                                    <option value="cash">Tunai (Cash)</option>
                                    <option v-if="auth.user?.role === 'owner'" value="bank">Bank (Transfer)</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-2">Tanggal</label>
                                <input type="date" v-model="form.transaction_date" class="w-full bg-white/5 border border-white/5 rounded-2xl py-4 px-4 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all font-medium appearance-none">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-2">Kategori Transaksi</label>
                            <select v-model="form.category" class="w-full bg-white/5 border border-white/5 rounded-2xl py-4 px-4 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all font-medium appearance-none">
                                <option v-if="form.type === 'in'" value="initial_balance">Modal Awal / Saldo Awal</option>
                                <option v-if="form.type === 'in'" value="adjustment">Uang Masuk Lainnya / Penyesuaian</option>
                                
                                <option v-if="form.type === 'out'" value="expense">Biaya Operasional</option>
                                <option v-if="form.type === 'out'" value="adjustment">Uang Keluar Lainnya / Penyesuaian</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-2">Nominal (Rupiah)</label>
                            <div class="relative">
                                <span class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-500 font-black italic">Rp</span>
                                <input type="number" v-model="form.amount" placeholder="0" class="w-full bg-white/5 border border-white/5 rounded-3xl py-6 pl-14 pr-6 text-2xl font-black text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all font-mono">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest pl-2">Catatan Transaksi</label>
                            <textarea v-model="form.note" rows="3" placeholder="Misal: Bayar Listrik, ATK Kantor, dsb." class="w-full bg-white/5 border border-white/5 rounded-2xl p-4 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all"></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex gap-4 pt-4 border-t border-white/5">
                    <button 
                        @click="showModal = false" 
                        class="flex-1 bg-white/5 hover:bg-white/10 text-slate-400 font-black py-4.5 rounded-2xl transition-all uppercase text-[10px] tracking-widest"
                    >
                        Batal
                    </button>
                    <button 
                        @click="handleStore"
                        :disabled="saving"
                        class="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-black py-4.5 rounded-2xl transition-all uppercase text-[10px] tracking-widest shadow-xl shadow-blue-600/20 flex items-center justify-center gap-2"
                    >
                        <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
                        <span v-else>Simpan Transaksi</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</template>
