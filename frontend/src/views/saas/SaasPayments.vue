<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/api/axios';
import { 
    CheckCircle2, ExternalLink, Loader2,
    ImageIcon
} from 'lucide-vue-next';

const subscriptions = ref<any[]>([]);
const loading = ref(true);
const processing = ref<number | null>(null);

const fetchPayments = async () => {
    loading.value = true;
    try {
        const { data } = await api.get('/saas/pending-subscriptions');
        subscriptions.value = data;
    } catch (error) {
        console.error('Failed to fetch pending payments', error);
    } finally {
        loading.value = false;
    }
};

const verifyPayment = async (subId: number, action: 'approve' | 'reject') => {
    if (!confirm(`Apakah Anda yakin ingin ${action === 'approve' ? 'MENYETUJUI' : 'MENOLAK'} pembayaran ini?`)) return;

    processing.value = subId;
    try {
        await api.patch(`/saas/subscriptions/${subId}/verify`, { action });
        fetchPayments();
    } catch (error) {
        alert('Gagal memverifikasi pembayaran.');
    } finally {
        processing.value = null;
    }
};

const formatDate = (date: string) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
};

onMounted(fetchPayments);
</script>

<template>
<div class="p-8">
    <div class="mb-10">
        <h1 class="text-3xl font-bold text-white tracking-tight">Verifikasi Pembayaran</h1>
        <p class="text-slate-500 mt-1">Ulas bukti transfer dan perpanjang lisensi tenant secara manual.</p>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && subscriptions.length === 0" class="flex flex-col items-center justify-center py-20 bg-slate-900/40 border border-white/5 rounded-3xl opacity-60">
        <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mb-4">
            <CheckCircle2 class="w-8 h-8 text-slate-500" />
        </div>
        <h3 class="text-white font-bold text-lg">Semua Pembayaran Beres</h3>
        <p class="text-slate-500">Tidak ada bukti transfer yang perlu diverifikasi saat ini.</p>
    </div>

    <!-- Payments Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-if="loading" v-for="i in 3" :key="i" class="h-64 bg-white/5 border border-white/10 rounded-2xl animate-pulse"></div>
        
        <div 
            v-for="sub in subscriptions" 
            :key="sub.id"
            class="bg-slate-900/50 border border-white/10 rounded-3xl p-6 backdrop-blur-xl relative group hover:border-blue-500/30 transition-all duration-300"
        >
            <!-- Badge Plan -->
            <div class="flex justify-between items-start mb-6">
                <div class="px-3 py-1 bg-blue-600/20 text-blue-400 rounded-full text-[10px] uppercase font-black tracking-widest">
                    {{ sub.plan.name }} Plan
                </div>
                <p class="text-white font-black text-lg">{{ formatCurrency(sub.plan.price) }}</p>
            </div>

            <!-- Tenant Info -->
            <div class="mb-6 flex items-center gap-4 p-4 bg-white/5 rounded-2xl">
                <div class="w-10 h-10 bg-indigo-600/20 rounded-xl flex items-center justify-center font-bold text-indigo-400">
                    {{ sub.supplier.name.charAt(0) }}
                </div>
                <div class="flex-1 overflow-hidden">
                    <h4 class="text-white font-bold truncate">{{ sub.supplier.name }}</h4>
                    <p class="text-slate-500 text-xs truncate">Code: {{ sub.supplier.code }}</p>
                </div>
            </div>

            <!-- Status & Actions -->
            <div class="space-y-4">
                <a 
                    :href="sub.payment_proof_url" 
                    target="_blank"
                    class="w-full flex items-center justify-center gap-2 py-3 bg-white/5 hover:bg-white/10 text-slate-300 text-sm font-bold rounded-xl transition-all border border-white/10"
                >
                    <ImageIcon class="w-4 h-4" />
                    Lihat Bukti Transfer
                    <ExternalLink class="w-3 h-3 text-slate-600" />
                </a>

                <div class="flex gap-4">
                    <button 
                        @click="verifyPayment(sub.id, 'reject')"
                        :disabled="processing === sub.id"
                        class="flex-1 py-3 bg-red-500/10 hover:bg-red-500/20 text-red-500 rounded-xl font-black text-xs uppercase tracking-widest transition-all"
                    >
                        <Loader2 v-if="processing === sub.id" class="w-4 h-4 animate-spin mx-auto text-red-500" />
                        <span v-else>Reject</span>
                    </button>
                    <button 
                         @click="verifyPayment(sub.id, 'approve')"
                         :disabled="processing === sub.id"
                         class="flex-1 py-3 bg-green-500 hover:bg-green-600 text-white shadow-lg shadow-green-500/20 rounded-xl font-black text-xs uppercase tracking-widest transition-all"
                    >
                        <Loader2 v-if="processing === sub.id" class="w-4 h-4 animate-spin mx-auto text-white" />
                        <span v-else>Approve</span>
                    </button>
                </div>
            </div>
            
            <p class="text-[10px] text-slate-600 mt-4 text-center">Permintaan dibuat pada {{ formatDate(sub.created_at) }}</p>
        </div>
    </div>
</div>
</template>
