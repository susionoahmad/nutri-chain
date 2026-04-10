<script setup lang="ts">
import { ref, onMounted } from 'vue';

import api from '@/api/axios';
import { 
    CheckCircle2, AlertCircle, Clock, 
    ChevronRight, ShieldCheck, Loader2 
} from 'lucide-vue-next';

// const auth = useAuthStore();
const plans = ref<any[]>([]);
const currentSubscription = ref<any>(null);
const saasSettings = ref<any>({});
const loading = ref(true);
const selectedPlan = ref<any>(null);
const showUploadModal = ref(false);
const uploadForm = ref({
    payment_proof: null as File | null,
});
const submitting = ref(false);

const fetchData = async () => {
    loading.value = true;
    try {
        const [plansRes, subRes, settingsRes] = await Promise.all([
            api.get('/billing/plans'),
            api.get('/billing/current'),
            api.get('/saas/settings')
        ]);
        plans.value = plansRes.data;
        currentSubscription.value = subRes.data;
        saasSettings.value = settingsRes.data;
    } catch (error) {
        console.error('Failed to fetch billing data', error);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(price);
};

const handleSelectPlan = (plan: any) => {
    selectedPlan.value = plan;
    showUploadModal.value = true;
};

const handleFileChange = (e: any) => {
    uploadForm.value.payment_proof = e.target.files[0];
};

const submitPayment = async () => {
    if (!uploadForm.value.payment_proof || !selectedPlan.value) return;

    submitting.value = true;
    const formData = new FormData();
    formData.append('plan_id', selectedPlan.value.id);
    formData.append('payment_proof', uploadForm.value.payment_proof);

    try {
        await api.post('/billing/subscribe', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        alert('Bukti pembayaran berhasil diunggah. Mohon tunggu verifikasi dari admin.');
        showUploadModal.value = false;
        fetchData();
    } catch (error: any) {
        alert(error.response?.data?.message || 'Gagal mengunggah bukti pembayaran.');
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
<div class="p-8 max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-10 text-center">
        <h1 class="text-3xl font-bold text-slate-100 flex items-center justify-center gap-3">
            <ShieldCheck class="w-8 h-8 text-blue-500" />
            {{ $t('billing_page.title') }}
        </h1>
        <p class="text-slate-400 mt-2">{{ $t('billing_page.subtitle', 'Pilih paket yang sesuai dengan kebutuhan bisnis Nutri-Chain Anda.') }}</p>
    </div>

    <!-- Current Status Card -->
    <div class="bg-slate-900/50 border border-white/10 rounded-3xl p-8 mb-12 backdrop-blur-xl relative overflow-hidden group">
        <div class="absolute -right-20 -top-20 w-64 h-64 bg-blue-600/10 rounded-full blur-[80px] group-hover:bg-blue-600/20 transition-all duration-700"></div>
        
        <div class="flex flex-col md:flex-row items-center justify-between gap-8 relative z-10">
            <div class="flex items-center gap-6">
                <div class="w-16 h-16 bg-blue-600/20 rounded-2xl flex items-center justify-center">
                    <Clock class="w-8 h-8 text-blue-500" />
                </div>
                <div>
                    <h3 class="text-slate-400 font-bold text-xs uppercase tracking-widest mb-1">{{ $t('billing_page.status_label', 'Status Lisensi Saat Ini') }}</h3>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-white">{{ currentSubscription?.plan?.name || 'Trial' }} {{ currentSubscription?.plan?.duration_days ?? 7 }} {{ $t('billing_page.days') }}</span>
                        <span 
                            :class="[
                                currentSubscription?.status === 'active' ? 'bg-green-500/20 text-green-400' :
                                currentSubscription?.status === 'pending' ? 'bg-amber-500/20 text-amber-400' :
                                'bg-red-500/20 text-red-500'
                            ]"
                            class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-tight"
                        >
                            {{ currentSubscription?.status ? $t(`billing_page.status.${currentSubscription.status}`) : $t('billing_page.status.active') }}
                        </span>
                    </div>
                    <p v-if="currentSubscription?.end_date && currentSubscription?.status === 'active'" class="text-slate-500 text-sm mt-1">Berakhir pada: <span class="text-slate-300 font-medium">{{ new Date(currentSubscription.end_date).toLocaleDateString($i18n.locale === 'id' ? 'id-ID' : 'en-US', { day: 'numeric', month: 'long', year: 'numeric' }) }}</span></p>
                    <p v-else-if="currentSubscription?.status === 'pending'" class="text-amber-500/70 text-sm mt-1 animate-pulse">{{ $t('billing_page.waiting_admin') }}</p>
                </div>
            </div>
            
            <div class="text-center md:text-right" v-if="currentSubscription">
                <p class="text-slate-400 text-sm mb-2">{{ $t('billing_page.remaining_days') }}</p>
                <div v-if="currentSubscription.status === 'active' && currentSubscription.end_date" class="text-4xl font-black text-white tracking-tighter">
                   {{ Math.max(0, Math.ceil((new Date(currentSubscription.end_date).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24))) }} <span class="text-lg text-slate-500 uppercase">{{ $t('billing_page.days') }}</span>
                </div>
                <div v-else-if="currentSubscription.status === 'pending'" class="flex flex-col items-center md:items-end">
                    <span class="text-amber-400 font-black text-sm uppercase tracking-widest leading-none">{{ $t('billing_page.status.pending') }}</span>
                    <span class="text-white/20 font-black text-4xl tracking-tighter">-- : --</span>
                </div>
                <div v-else class="text-red-500 font-black text-xl uppercase italic">
                    {{ $t('billing_page.status.expired') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
        <div 
            v-for="plan in plans" 
            :key="plan.id"
            class="relative bg-slate-900/40 border border-white/10 rounded-3xl p-8 hover:border-blue-500/50 transition-all duration-300 group"
        >
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 class="text-2xl font-bold text-white mb-1">{{ plan.name }}</h3>
                    <p class="text-slate-500 text-sm">{{ $t('common.duration', 'Durasi') }} {{ plan.duration_days }} {{ $t('billing_page.days') }}</p>
                </div>
                <div v-if="plan.name === 'Pro'" class="px-3 py-1 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full text-[10px] font-bold text-white uppercase tracking-widest shadow-lg shadow-blue-500/20">
                    {{ $t('billing_page.recommended') }}
                </div>
            </div>

            <div class="mb-8">
                <div class="flex items-baseline gap-1">
                    <span class="text-4xl font-black text-white">{{ formatPrice(plan.price) }}</span>
                </div>
            </div>

            <ul class="space-y-4 mb-10">
                <li class="flex items-center gap-3 text-blue-400 text-sm font-bold bg-blue-500/10 p-2 rounded-xl">
                    <CheckCircle2 class="w-5 h-5 text-blue-500" />
                    {{ $t('common.customer_limit', 'Batas Pelanggan') }}: {{ plan.max_customers || 'Unlimited' }}
                </li>
                <li class="flex items-center gap-3 text-slate-300 text-sm">
                    <CheckCircle2 class="w-5 h-5 text-blue-500" />
                    {{ $t('common.user_limit', 'Batas User') }}: {{ plan.max_users || 'Unlimited' }}
                </li>
                <li v-for="feature in plan.features" :key="feature" class="flex items-center gap-3 text-slate-300 text-sm">
                    <CheckCircle2 class="w-5 h-5 text-blue-500" />
                    {{ feature }}
                </li>
            </ul>

            <button 
                @click="handleSelectPlan(plan)"
                class="w-full py-4 bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl text-white font-bold transition-all flex items-center justify-center gap-2 group-hover:bg-blue-600 group-hover:border-blue-600 active:scale-95"
            >
                {{ $t('billing_page.select_plan') }}
                <ChevronRight class="w-4 h-4" />
            </button>
        </div>
    </div>

    <!-- Bank Info -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div class="md:col-span-2 bg-indigo-600/10 border border-indigo-500/20 rounded-[32px] p-8 flex items-start gap-6 backdrop-blur-xl">
            <div class="w-14 h-14 bg-indigo-500/20 rounded-2xl flex items-center justify-center shrink-0">
                <AlertCircle class="w-8 h-8 text-indigo-400" />
            </div>
            <div>
                <h4 class="text-indigo-200 font-bold text-lg mb-2 italic">{{ $t('billing_page.transfer_info') }}</h4>
                <p class="text-indigo-300/80 text-sm leading-relaxed mb-4">
                    {{ $t('billing_page.transfer_desc', 'Pilih paket Anda dan lakukan transfer ke rekening resmi platform untuk aktivasi instan:') }}
                </p>
                <div class="p-6 bg-white/5 border border-white/5 rounded-2xl space-y-3">
                    <p class="text-xs text-slate-500 uppercase tracking-widest font-black">{{ $t('billing_page.transfer_to') }}</p>
                    <p class="text-white font-black text-xl tracking-tighter italic uppercase underline decoration-indigo-500/50 underline-offset-8 decoration-2">{{ saasSettings.bank_name || 'Bank BCA' }}</p>
                    <p class="text-white font-black text-2xl tracking-widest">{{ saasSettings.bank_account_number || '123-456-7890' }}</p>
                    <p class="text-slate-400 font-bold italic">{{ $t('billing_page.acc_owner') }} {{ saasSettings.bank_account_name || 'PT Nutri Chain Solusi' }}</p>
                </div>
            </div>
        </div>

        <!-- Contact Support Card -->
        <div class="bg-blue-600/10 border border-blue-500/20 rounded-[32px] p-8 flex flex-col justify-between backdrop-blur-xl">
            <div>
                <h4 class="text-blue-200 font-bold text-lg mb-2 italic">{{ $t('billing_page.need_help') }}</h4>
                <p class="text-blue-300/80 text-xs leading-relaxed mb-6">{{ $t('billing_page.support_desc') }}</p>
            </div>
            <div class="space-y-3">
                <a :href="`https://wa.me/${saasSettings.contact_whatsapp}`" target="_blank" class="w-full py-4 bg-green-500/10 hover:bg-green-500/20 text-green-400 border border-green-500/20 rounded-2xl flex items-center justify-center gap-3 font-black text-[10px] uppercase tracking-widest transition-all">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-ping"></span>
                    {{ $t('billing_page.contact_wa') }}
                </a>
                <a :href="`mailto:${saasSettings.contact_email}`" class="w-full py-4 bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 border border-blue-500/20 rounded-2xl flex items-center justify-center gap-3 font-black text-[10px] uppercase tracking-widest transition-all">
                    {{ $t('billing_page.contact_email') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Upload Modal (Simulated) -->
    <div v-if="showUploadModal" class="fixed inset-0 z-[100] flex items-center justify-center p-6 sm:p-0">
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showUploadModal = false"></div>
        
        <div class="bg-slate-900 border border-white/10 rounded-3xl p-8 w-full max-w-lg relative z-10 shadow-2xl">
            <h2 class="text-2xl font-bold text-white mb-2">Unggah Bukti Bayar</h2>
            <p class="text-slate-400 text-sm mb-8">Langkah terakhir untuk mengaktifkan paket Premium <strong>{{ selectedPlan?.name }}</strong>.</p>
            
            <form @submit.prevent="submitPayment" class="space-y-6">
                <div class="p-8 border-2 border-dashed border-white/10 rounded-2xl text-center hover:border-blue-500/50 transition-all">
                    <input type="file" @change="handleFileChange" class="mb-4 text-slate-300 block w-full" accept="image/*" />
                    <p class="text-slate-500 text-xs">JPG, PNG atau PDF (Maks 5MB)</p>
                </div>
                
                <div class="flex gap-4">
                    <button type="button" @click="showUploadModal = false" class="flex-1 py-4 bg-white/5 text-white font-bold rounded-xl border border-white/10">Batal</button>
                    <button type="submit" :disabled="submitting" class="flex-1 py-4 bg-blue-600 text-white font-bold rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-blue-500/20">
                        <Loader2 v-if="submitting" class="w-5 h-5 animate-spin" />
                        Konfirmasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</template>
