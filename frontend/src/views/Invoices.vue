<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/api';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import { formatCurrency } from '@/utils/format';
import { 
    Receipt, FileImage, 
    UploadCloud, CheckCircle2,
    X, AlertCircle, Loader2,
    Printer, Wallet, Clock
} from 'lucide-vue-next';

const auth = useAuthStore();
const { t } = useI18n();

const invoices = ref<any[]>([]);
const loading = ref(false);
const isSubmitting = ref(false);

// Modals
const showUploadModal = ref(false);
const showReviewModal = ref(false);
const showDetailModal = ref(false);
const selectedInvoice = ref<any>(null);

const fileProof = ref<File | null>(null);
const paymentMethod = ref('bank');

const fetchInvoices = async () => {
    loading.value = true;
    try {
        const response = await api.get('/invoices');
        invoices.value = response.data.data || response.data;
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'unpaid': return 'bg-rose-500/10 text-rose-500 border-rose-500/20';
        case 'pending_verification': return 'bg-amber-500/10 text-amber-500 border-amber-500/20';
        case 'paid': return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20';
        default: return 'bg-slate-500/10 text-slate-500 border-slate-500/20';
    }
};

const getStatusLabel = (status: string) => {
    if (status === 'unpaid') return t('invoices.status_unpaid');
    if (status === 'pending_verification') return t('invoices.status_pending_verification');
    if (status === 'paid') return t('invoices.status_paid_label');
    return status;
}

const openUploadModal = (invoice: any) => {
    selectedInvoice.value = invoice;
    fileProof.value = null;
    showUploadModal.value = true;
};

const openReviewModal = (invoice: any) => {
    selectedInvoice.value = invoice;
    showReviewModal.value = true;
};

const openDetailModal = (invoice: any) => {
    selectedInvoice.value = invoice;
    showDetailModal.value = true;
};

const handleFileUpload = (event: any) => {
    fileProof.value = event.target.files[0];
};

const submitProof = async () => {
    if (!fileProof.value || !selectedInvoice.value) return;

    isSubmitting.value = true;
    const formData = new FormData();
    formData.append('payment_proof', fileProof.value);
    formData.append('payment_method', paymentMethod.value);

    try {
        await api.post(`/invoices/${selectedInvoice.value.id}/pay`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        showUploadModal.value = false;
        fetchInvoices();
    } catch (error: any) {
        console.error(error);
        alert(error.response?.data?.message || t('invoices.upload_error'));
    } finally {
        isSubmitting.value = false;
    }
};

const verifyPayment = async (status: 'paid' | 'unpaid') => {
    if (!selectedInvoice.value) return;
    
    isSubmitting.value = true;
    try {
        await api.put(`/invoices/${selectedInvoice.value.id}/verify`, { status });
        showReviewModal.value = false;
        showDetailModal.value = false;
        fetchInvoices();
    } catch (error) {
        console.error(error);
        alert(t('invoices.verify_error'));
    } finally {
        isSubmitting.value = false;
    }
};

const printInvoice = () => {
    window.print();
};

onMounted(fetchInvoices);
</script>

<template>
<div class="space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-700 print:p-0">
    <!-- Header (No Print) -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 print:hidden">
        <div>
            <h1 class="text-4xl font-black tracking-tight mb-2 text-white flex items-center gap-3 italic uppercase">
                <Receipt class="w-10 h-10 text-blue-500" />
                {{ t('invoices.title') }}
            </h1>
            <p class="text-slate-500 uppercase tracking-widest text-[10px] font-black">
                {{ auth.isCustomer ? t('invoices.subtitle_customer') : t('invoices.subtitle_admin') }}
            </p>
        </div>
        <div class="flex items-center gap-3">
             <div class="bg-white/5 px-6 py-3 rounded-2xl border border-white/5 flex flex-col items-end">
                <span class="text-[9px] font-black text-slate-500 uppercase">{{ t('invoices.pending_total') }}</span>
                <span class="text-xl font-bold text-white font-mono">{{ invoices.filter(i => i.status !== 'paid').length }} ITEMS</span>
             </div>
        </div>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div v-for="i in 3" :key="i" class="h-64 bg-white/5 border border-white/10 rounded-[48px] animate-pulse"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="invoices.length === 0" class="py-32 text-center bg-white/5 rounded-[48px] border border-white/10">
        <Receipt class="w-20 h-20 mx-auto text-slate-800 mb-6" />
        <p class="text-xl text-slate-500 font-black uppercase tracking-widest italic">{{ t('invoices.no_data') }}</p>
    </div>

    <!-- Invoice List -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8 print:hidden">
        <div 
            v-for="inv in invoices" 
            :key="inv.id" 
            @click="openDetailModal(inv)"
            class="bg-white/5 border border-white/5 rounded-[48px] p-10 relative overflow-hidden group hover:bg-white/[0.08] hover:border-blue-500/20 transition-all cursor-pointer shadow-2xl flex flex-col"
        >
            <!-- Design Accents -->
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-blue-600/10 blur-[80px] group-hover:bg-blue-600/20 transition-all"></div>
            
            <div class="relative z-10 flex-1">
                <div class="flex items-center justify-between mb-8">
                    <span :class="getStatusBadge(inv.status)" class="px-5 py-2 rounded-2xl border text-[9px] font-black tracking-widest shadow-lg">
                        {{ getStatusLabel(inv.status) }}
                    </span>
                    <p class="text-xs font-black text-slate-500 italic uppercase">#{{ inv.id }}</p>
                </div>

                <div class="space-y-6 mb-10">
                    <div v-if="!auth.isCustomer" class="flex flex-col">
                        <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1 italic">Bill To:</span>
                        <span class="text-lg font-black text-white italic">{{ inv.order?.customer?.name }}</span>
                    </div>
                    <div class="flex justify-between items-end border-b border-white/5 pb-6">
                        <div>
                            <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest italic">Total Bill:</span>
                            <p class="text-3xl font-black text-blue-400 font-mono italic tracking-tighter leading-none mt-1">{{ formatCurrency(inv.total) }}</p>
                        </div>
                        <p class="text-[9px] font-bold text-slate-400 uppercase italic">Ref: {{ inv.order?.order_number }}</p>
                    </div>
                </div>
            </div>

            <!-- Card Actions -->
            <div class="relative z-10 grid grid-cols-2 gap-4">
                <button v-if="inv.status === 'unpaid' && (auth.isAdmin || auth.isOwner)" @click.stop="openUploadModal(inv)" class="bg-blue-600 hover:bg-blue-500 text-white py-4 rounded-3xl font-black text-[10px] uppercase tracking-widest transition-all shadow-xl shadow-blue-600/20">
                    {{ t('invoices.complete_payment') }}
                </button>
                <div v-else-if="inv.status === 'paid'" class="col-span-2 py-4 rounded-3xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 text-center font-black text-[10px] uppercase tracking-widest flex items-center justify-center gap-2">
                    <CheckCircle2 class="w-4 h-4" /> {{ t('invoices.transaction_done') }}
                </div>
                <button v-else-if="inv.status === 'pending_verification' && (auth.isAdmin || auth.isOwner)" @click.stop="openReviewModal(inv)" class="col-span-2 bg-amber-500 hover:bg-amber-400 text-black py-4 rounded-3xl font-black text-[10px] uppercase tracking-widest transition-all">
                    {{ inv.payment_method === 'cash' ? t('invoices.check_cash_proof') : t('invoices.check_bank_proof') }}
                </button>
                
                <button v-if="inv.status !== 'paid'" @click.stop="openDetailModal(inv)" class="bg-white/10 hover:bg-white text-white hover:text-black py-4 rounded-3xl font-black text-[10px] uppercase tracking-widest transition-all border border-white/5">
                    {{ t('invoices.detail') }}
                </button>
            </div>
        </div>
    </div>

    <!-- MODERN OFFICIAL INVOICE MODAL (FULL SCREEN) -->
    <div v-if="showDetailModal" class="fixed inset-0 z-[150] flex items-center justify-center p-6 lg:p-12 print:p-0 print:static">
        <div class="absolute inset-0 bg-black/95 backdrop-blur-3xl print:hidden" @click="showDetailModal = false"></div>
        
        <!-- Modal Content Container -->
        <div class="bg-white w-full max-w-5xl h-full flex flex-col rounded-[64px] overflow-hidden shadow-[0_32px_120px_rgba(0,0,0,0.8)] relative z-10 print:rounded-none print:shadow-none animate-in zoom-in-95 duration-500">
            <!-- Modal Actions (No Print) -->
            <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50 print:hidden shrink-0">
                <div class="flex items-center gap-6">
                    <button @click="showDetailModal = false" class="p-4 bg-white rounded-2xl border border-slate-200 hover:bg-slate-900 hover:text-white transition-all">
                        <X class="w-6 h-6"/>
                    </button>
                    <div>
                        <h4 class="font-black text-slate-900 italic text-xl">{{ t('invoices.invoice_detail_title') }}</h4>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">{{ t('invoices.digital_workpaper') }}</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <button @click="printInvoice" class="bg-slate-900 text-white px-8 py-4 rounded-3xl font-black text-xs uppercase tracking-widest shadow-2xl flex items-center gap-2 hover:scale-105 active:scale-95 transition-all">
                        <Printer class="w-5 h-5"/> {{ t('invoices.print_pdf') }}
                    </button>
                </div>
            </div>

            <!-- THE ACTUAL OFFICIAL INVOICE (This part looks like a real invoice) -->
            <div class="flex-1 overflow-y-auto p-12 md:p-24 text-slate-900 font-serif leading-relaxed" id="invoice-bill">
                <!-- Watermark -->
                <div v-if="selectedInvoice.status === 'paid'" class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.05] -rotate-12 transition-all">
                    <CheckCircle2 class="w-[800px] h-[800px] text-emerald-600"/>
                </div>

                <!-- Invoice Header Section -->
                <div class="flex flex-col md:flex-row justify-between items-start gap-12 mb-20">
                    <div>
                        <div class="flex items-center gap-4 mb-4">
                           <div class="w-16 h-16 bg-slate-900 rounded-2xl flex items-center justify-center"><Receipt class="text-white w-10 h-10"/></div>
                           <div>
                               <h1 class="text-3xl font-black italic tracking-tighter uppercase leading-none">Nutri-Chain</h1>
                               <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.4em] mt-1 italic">Supply Chain Distro</p>
                           </div>
                        </div>
                        <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">Official Transaction Slip</p>
                    </div>
                    <div class="text-right">
                        <h2 class="text-5xl font-black tracking-tighter text-slate-900 italic uppercase mb-2">Invoice</h2>
                        <div class="flex flex-col items-end gap-1">
                            <p class="bg-slate-900 text-white px-4 py-1 text-xs font-black italic uppercase">Ref. #{{ selectedInvoice.id }}</p>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest mt-2 italic">Issued Date: {{ new Date(selectedInvoice.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Parties Detail -->
                <div class="grid grid-cols-2 gap-20 border-y-2 border-slate-900 py-12 mb-16">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-6 italic">{{ t('invoices.billing_details') }}</p>
                        <h4 class="text-xl font-black uppercase text-slate-900 italic mb-2">{{ selectedInvoice.order?.customer?.name }}</h4>
                        <p class="text-sm font-medium text-slate-600 leading-relaxed max-w-xs">{{ selectedInvoice.order?.customer?.address || t('invoices.no_address') }}</p>
                        <p class="text-xs font-bold text-slate-900 mt-4">{{ selectedInvoice.order?.customer?.phone || '+62 --- ----' }}</p>
                    </div>
                    <div class="text-right flex flex-col items-end">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-6 italic">{{ t('invoices.transaction_status') }}</p>
                        <div :class="selectedInvoice.status === 'paid' ? 'text-emerald-600 border-emerald-200 bg-emerald-50' : 'text-rose-600 border-rose-100 bg-rose-50'" class="px-8 py-3 rounded-full border-4 font-black italic uppercase text-lg inline-block shadow-inner">
                            {{ selectedInvoice.status === 'paid' ? t('invoices.status_paid') : t('invoices.status_pending') }}
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-6 italic">{{ t('invoices.due_by') }}: {{ new Date(selectedInvoice.due_date).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) }}</p>
                    </div>
                </div>

                <!-- Line Items Table -->
                <div class="mb-20">
                    <table class="w-full">
                        <thead class="text-[11px] font-black uppercase italic tracking-widest text-slate-400">
                            <tr>
                                <th class="py-6 border-b border-slate-100 text-left">{{ t('invoices.description') }}</th>
                                <th class="py-6 border-b border-slate-100 text-center">{{ t('invoices.qty') }}</th>
                                <th class="py-6 border-b border-slate-100 text-right">{{ t('invoices.unit_price') }}</th>
                                <th class="py-6 border-b border-slate-100 text-right">{{ t('invoices.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 italic">
                            <tr v-for="item in selectedInvoice.order?.items" :key="item.id">
                                <td class="py-6 pr-4">
                                    <h5 class="text-lg font-black text-slate-900 uppercase italic">{{ item.product?.name }}</h5>
                                    <p class="text-[10px] font-black text-slate-400 mt-1 uppercase tracking-widest">{{ item.product?.sku || 'SKU-GENERIC' }}</p>
                                </td>
                                <td class="py-6 text-center font-black text-xl font-mono">{{ item.qty }}</td>
                                <td class="py-6 text-right font-bold text-slate-600 font-mono">{{ formatCurrency(item.price) }}</td>
                                <td class="py-6 text-right font-black text-slate-900 font-mono">{{ formatCurrency(item.qty * item.price) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Totals Section -->
                <div class="flex justify-end pt-12 border-t-4 border-slate-900">
                    <div class="w-full md:w-80 space-y-4">
                        <div class="flex justify-between items-center text-slate-500 font-bold italic">
                            <span class="text-xs uppercase">{{ t('invoices.subtotal') }}</span>
                            <span class="font-mono text-lg">{{ formatCurrency(selectedInvoice.total) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-slate-500 font-bold italic">
                            <span class="text-xs uppercase">{{ t('invoices.adjustments') }}</span>
                            <span class="font-mono text-lg">Rp 0</span>
                        </div>
                        <div class="flex justify-between items-center pt-6 border-t-2 border-slate-100">
                            <span class="text-sm font-black italic uppercase text-slate-900">{{ t('invoices.total_invoice') }}</span>
                            <span class="text-3xl font-black italic font-mono text-blue-600">{{ formatCurrency(selectedInvoice.total) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Official Footer (Payment Proof & Verification) -->
                <div class="mt-32 flex flex-col md:flex-row justify-between items-end gap-12">
                    <div v-if="selectedInvoice.payment_proof" class="space-y-4">
                       <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.4em] mb-4 italic">Verification - {{ selectedInvoice.payment_method === 'cash' ? 'CASH' : 'BANK TRANSFER' }}</p>
                       <div class="bg-slate-50 group transition-all relative border border-slate-200 p-2 rounded-2xl w-48 h-32 overflow-hidden shadow-inner cursor-zoom-in" @click="openReviewModal(selectedInvoice)">
                            <img :src="selectedInvoice.payment_proof" class="w-full h-full object-cover opacity-60 group-hover:opacity-100 grayscale hover:grayscale-0 transition-all active:scale-150 active:z-50"/>
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 bg-black/10 backdrop-blur-[2px] transition-all">
                                <FileImage class="text-white w-8 h-8 drop-shadow-xl"/>
                            </div>
                       </div>
                       <div class="flex items-center gap-2 text-emerald-600">
                           <CheckCircle2 class="w-4 h-4"/>
                           <span class="text-[9px] font-black uppercase italic">{{ t('invoices.payment_verified') }}</span>
                       </div>
                    </div>
                    <div class="text-center md:text-right border-t border-slate-100 pt-8 mt-12 w-full md:w-auto">
                        <div class="mb-12 h-24 print:hidden">
                            <p class="text-[10px] font-black text-slate-400 uppercase mb-2">{{ t('invoices.auth_seal') }}</p>
                            <Receipt class="w-16 h-16 text-slate-200 mx-auto md:ml-auto opacity-50"/>
                        </div>
                        <p class="font-black italic text-xl uppercase mb-1">Nutri-Chain Distro Admin</p>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest italic">{{ t('invoices.authorized_signature') }}</p>
                    </div>
                </div>

                <!-- Audit Trail / Activity Log — hanya owner/admin, tidak dicetak -->
                <div v-if="(auth.isOwner || auth.isAdmin) && selectedInvoice?.activities?.length > 0" class="mt-32 pt-12 border-t border-slate-100 print:hidden">
                    <h3 class="text-xl font-black text-slate-900 mb-8 flex items-center gap-4 tracking-tighter uppercase italic">
                        <Clock class="w-6 h-6 text-blue-600" /> {{ t('invoices.audit_trail') }}
                    </h3>
                    <div class="space-y-4">
                        <div v-for="log in selectedInvoice.activities" :key="log.id" class="flex gap-6 p-6 bg-slate-50 rounded-3xl border border-slate-100 items-start group hover:bg-slate-100 transition-all">
                            <div class="w-10 h-10 bg-white rounded-2xl flex items-center justify-center border border-slate-200 text-slate-400 shrink-0 group-hover:text-blue-600 transition-colors">
                                <CheckCircle2 v-if="log.action === 'payment_verified'" class="w-5 h-5" />
                                <AlertCircle v-else-if="log.action === 'payment_rejected'" class="w-5 h-5 text-rose-500" />
                                <Clock v-else class="w-5 h-5" />
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-[10px] font-black text-slate-900 uppercase italic">{{ log.causer_name }}</span>
                                    <span class="text-[10px] font-mono font-bold text-slate-400">{{ new Date(log.created_at).toLocaleString() }}</span>
                                </div>
                                <p class="text-xs text-slate-500 font-medium leading-relaxed">{{ log.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Verify Actions (Floating No Print) -->
            <div v-if="auth.isOwner && selectedInvoice?.status === 'pending_verification'" class="p-8 bg-amber-500 border-t border-amber-600 flex items-center justify-between gap-6 shrink-0 print:hidden shadow-[0_-20px_40px_rgba(245,158,11,0.2)]">
                <div class="flex items-center gap-4">
                    <AlertCircle class="w-10 h-10 text-white"/>
                    <div>
                        <h4 class="font-black text-black italic text-lg uppercase leading-none mb-1 text-shadow">{{ t('invoices.financial_confirm') }}</h4>
                        <p class="text-[10px] font-black text-amber-900/60 uppercase tracking-widest italic">{{ t('invoices.check_before_verify') }}</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button @click="verifyPayment('unpaid')" :disabled="isSubmitting" class="bg-black hover:bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl transition-all">
                        {{ t('invoices.reject_simple') }}
                    </button>
                    <button @click="verifyPayment('paid')" :disabled="isSubmitting" class="bg-white hover:bg-slate-100 text-black px-12 py-4 rounded-2xl font-bold uppercase tracking-widest text-[10px] shadow-2xl flex items-center gap-2 transition-all">
                        <CheckCircle2 class="w-5 h-5"/> {{ t('invoices.approve_simple') }}
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- UPLOAD MODAL (ADMIN) -->
    <div v-if="showUploadModal" class="fixed inset-0 z-[160] flex items-center justify-center p-6 backdrop-blur-md bg-black/60 transition-all">
        <div class="absolute inset-0" @click="showUploadModal = false"></div>
        <div class="bg-slate-950 border border-white/10 w-full max-w-lg overflow-hidden rounded-[48px] flex flex-col relative z-10 shadow-[0_32px_120px_rgba(0,0,0,1)] animate-in zoom-in-95 duration-300">
            <div class="p-10 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
                <div>
                    <h3 class="font-black text-2xl text-white tracking-tighter italic uppercase">{{ t('invoices.upload_title') }}</h3>
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mt-1 italic">{{ t('invoices.upload_subtitle') }}</p>
                </div>
                <button @click="showUploadModal = false" class="p-4 bg-white/5 rounded-2xl text-slate-500 hover:text-white transition-all"><X class="w-6 h-6"/></button>
            </div>
            <div class="p-10 space-y-8">
                <div class="space-y-4">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest italic">{{ t('invoices.payment_method') }}</label>
                    <div class="grid grid-cols-2 gap-4">
                        <button @click="paymentMethod = 'bank'" :class="paymentMethod === 'bank' ? 'bg-blue-600 text-white shadow-xl shadow-blue-600/20' : 'bg-white/5 text-slate-500'" class="flex items-center justify-center gap-3 py-4 rounded-2xl font-black uppercase text-[10px] transition-all">
                            <Wallet class="w-4 h-4"/> {{ t('invoices.bank_transfer') }}
                        </button>
                        <button @click="paymentMethod = 'cash'" :class="paymentMethod === 'cash' ? 'bg-emerald-600 text-white shadow-xl shadow-emerald-600/20' : 'bg-white/5 text-slate-500'" class="flex items-center justify-center gap-3 py-4 rounded-2xl font-black uppercase text-[10px] transition-all">
                            <CheckCircle2 class="w-4 h-4"/> {{ t('invoices.cash') }}
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest italic">
                        {{ paymentMethod === 'bank' ? t('invoices.proof_bank') : t('invoices.proof_cash') }}
                    </label>
                    <div class="bg-black/50 border-2 border-dashed border-white/10 rounded-[32px] p-12 text-center hover:border-blue-500/50 transition-all relative group overflow-hidden">
                        <input type="file" @change="handleFileUpload" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer z-10"/>
                        <div class="space-y-4 group-hover:scale-105 transition-transform">
                            <UploadCloud :class="paymentMethod === 'bank' ? 'text-blue-500' : 'text-emerald-500'" class="w-12 h-12 mx-auto group-hover:animate-bounce" />
                            <div>
                                <p class="text-sm font-black text-white italic uppercase">{{ fileProof ? fileProof.name : (paymentMethod === 'bank' ? t('invoices.choose_bank_file') : t('invoices.choose_cash_file')) }}</p>
                                <p class="text-[9px] font-bold text-slate-500 uppercase mt-1">{{ t('invoices.file_format') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <button @click="submitProof" :disabled="!fileProof || isSubmitting" class="w-full bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white font-black py-5 rounded-3xl uppercase tracking-widest text-xs flex items-center justify-center gap-3 shadow-2xl transition-all active:scale-95 group">
                    <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin"/>
                    <CheckCircle2 v-else class="w-5 h-5 group-hover:scale-110 transition-transform" />
                    {{ isSubmitting ? t('invoices.submitting') : t('invoices.send_verification') }}
                </button>
            </div>
        </div>
    </div>

    <!-- PROOF VIEW MODAL (IMAGE ONLY) -->
    <div v-if="showReviewModal" class="fixed inset-0 z-[170] flex items-center justify-center p-6 backdrop-blur-3xl bg-black/90 transition-all">
        <div class="absolute inset-0" @click="showReviewModal = false"></div>
        <div class="w-full max-w-4xl max-h-[90vh] overflow-hidden rounded-[64px] flex flex-col relative z-10 animate-in zoom-in-95 duration-500 shadow-[0_0_120px_rgba(59,130,246,0.3)]">
            <div class="p-8 border-b border-white/5 flex justify-between items-center bg-black/40">
                <h3 class="font-black text-lg text-white tracking-widest uppercase italic">
                    {{ selectedInvoice?.payment_method === 'cash' ? t('invoices.validate_cash') : t('invoices.validate_bank') }}
                </h3>
                <button @click="showReviewModal = false" class="p-4 bg-white/5 rounded-2xl text-slate-500 hover:text-white transition-all"><X class="w-6 h-6"/></button>
            </div>
            
            <div class="flex-1 overflow-auto bg-black/60 flex items-center justify-center p-8">
                <img :src="selectedInvoice?.payment_proof" alt="Resi" class="max-w-full h-auto rounded-2xl shadow-2xl border border-white/10" />
            </div>

            <div v-if="auth.isOwner && selectedInvoice?.status === 'pending_verification'" class="p-10 border-t border-white/5 bg-slate-900 flex items-center justify-between gap-12 shrink-0">
                <div class="flex-1">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 mb-2 italic">{{ t('invoices.final_confirm') }}</p>
                    <p class="text-sm font-bold text-white italic leading-relaxed">
                        {{ selectedInvoice?.payment_method === 'cash' ? t('invoices.confirm_cash') : t('invoices.confirm_bank') }}
                    </p>
                </div>
                <div class="flex gap-4">
                    <button @click="verifyPayment('unpaid')" :disabled="isSubmitting" class="bg-rose-500 text-white px-8 py-4 rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-lg transition-all active:scale-95">
                        {{ t('invoices.reject') }}
                    </button>
                    <button @click="verifyPayment('paid')" :disabled="isSubmitting" class="bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xl shadow-emerald-500/20 px-10 py-4 rounded-2xl font-black uppercase tracking-widest text-[10px] flex items-center gap-3 transition-all active:scale-95">
                        <CheckCircle2 class="w-5 h-5"/> {{ t('invoices.approve') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
</template>

<style scoped>
@media print {
    /* Full hide everything browser adds automatically (URL, Date, Page No) */
    @page { 
        size: auto;
        margin: 0; 
    }
    
    html, body {
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    #invoice-bill {
        display: block !important;
        width: 100% !important;
        padding: 1.5cm !important; /* Space to replace browser margin */
        margin: 0 !important;
        border: none !important;
        background: white !important;
        color: black !important;
    }

    /* Hide ALL UI buttons and modal headers */
    .print\:hidden,
    button,
    .border-b.border-slate-100.bg-slate-50,
    .absolute.inset-0.bg-black\/95 {
        display: none !important;
    }

    /* Reset container for print */
    .fixed.inset-0.z-\[150\] { 
        position: static !important;
        display: block !important;
        height: auto !important;
    }

    .bg-white.w-full.max-w-5xl.h-full {
        height: auto !important;
        overflow: visible !important;
        max-width: 100% !important;
    }

    /* ULTRA COMPACT ADJUSTMENTS */
    .mb-20 { margin-bottom: 0.5rem !important; }
    .mb-16 { margin-bottom: 0.5rem !important; }
    .mt-32 { margin-top: 1rem !important; }
    .mb-12 { margin-bottom: 0.25rem !important; }
    .mt-12 { margin-top: 0.5rem !important; }
    .gap-12 { gap: 0.5rem !important; }
    .p-12, .md\:p-24 { padding: 0 !important; }
    .p-10 { padding: 0.5rem !important; }
    .py-12 { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; }
    .pt-12 { padding-top: 0.5rem !important; }
    .pt-8 { padding-top: 0.25rem !important; }
    
    .text-5xl { font-size: 2rem !important; }
    .text-3xl { font-size: 1.1rem !important; }
    .text-2xl { font-size: 1rem !important; }
    .text-xl { font-size: 0.9rem !important; }
    .text-lg { font-size: 0.8rem !important; }
    .text-sm { font-size: 0.75rem !important; }
    
    /* Table Items */
    table { font-size: 10px !important; }
    th { padding-top: 4px !important; padding-bottom: 4px !important; }
    td { padding-top: 3px !important; padding-bottom: 3px !important; }
    .py-6 { padding-top: 4px !important; padding-bottom: 4px !important; }

    /* Shrink Proof Image for Print */
    .w-48 { width: 3.5cm !important; }
    .h-32 { height: 2.2cm !important; }

    /* Watermark fix */
    .opacity-\[0\.05\] {
        position: absolute !important;
        top: 30% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) rotate(-12deg) !important;
        width: 250px !important;
        height: 250px !important;
        z-index: -1 !important;
    }

    .bg-slate-900 { background-color: #000 !important; color: #fff !important; }
    .text-blue-600 { color: #000 !important; font-weight: 800 !important; }
    .border-slate-900 { border-color: #000 !important; border-width: 1px !important; }
    .border-slate-100 { border-color: #eee !important; }
}

/* Modal and Transitions */
.animate-in {
    animation: animate-in 0.5s ease-out;
}
@keyframes animate-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.custom-sidebar-scroll::-webkit-scrollbar {
    width: 4px;
}
.custom-sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.custom-sidebar-scroll::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
}
</style>
