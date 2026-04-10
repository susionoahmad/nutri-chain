<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue';
import api from '@/api';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import { formatCurrency } from '@/utils/format';
import { 
    Inbox, Plus,
    Truck, Calendar, ChevronRight, X,
    CheckCircle2, Loader2, AlertCircle,
    Package
} from 'lucide-vue-next';

const auth = useAuthStore();
const { t } = useI18n();
const purchases = ref<any[]>([]);
const producers = ref<any[]>([]);
const products = ref<any[]>([]);
const loading = ref(false);

const showFormModal = ref(false);
const showProofModal = ref(false);
const showDetailsModal = ref(false);
const showSettleModal = ref(false);
const showQuickProductModal = ref(false);
const isSubmitting = ref(false);

const selectedPurchase = ref<any>(null);
const fileProof = ref<File | null>(null);
const paymentMethod = ref('cash');

const settleForm = reactive({
    payment_method: 'cash',
    payment_date: new Date().toISOString().split('T')[0],
    note: ''
});

// Form Data
const quickProductForm = ref({
    name: '',
    category: '',
    unit: '',
    cost_price: 0,
    price: 0,
    stock: 0
});

const submitQuickProduct = async () => {
    isSubmitting.value = true;
    try {
        const resp = await api.post('/products', quickProductForm.value);
        products.value.push(resp.data);
        quickProductForm.value = { name: '', category: '', unit: '', cost_price: 0, price: 0, stock: 0 };
        showQuickProductModal.value = false;
    } catch (error) {
        console.error(error);
    } finally {
        isSubmitting.value = false;
    }
};

// Form Data
const newPurchase = ref({
    producer_id: '',
    purchase_date: new Date().toISOString().split('T')[0],
    items: [{ product_id: '', qty: 1, cost_price: 0 }]
});

const openPaymentSettle = (purchase: any) => {
    selectedPurchase.value = purchase;
    settleForm.note = `Pelunasan Hutang: ${purchase.purchase_number}`;
    showSettleModal.value = true;
};

const handleSettlePurchase = async () => {
    isSubmitting.value = true;
    try {
        await api.post(`/purchases/${selectedPurchase.value.id}/pay`, settleForm);
        showSettleModal.value = false;
        fetchInitialData();
    } catch (error: any) {
        console.error(error);
        alert(error.response?.data?.message || 'Gagal melakukan pelunasan.');
    } finally {
        isSubmitting.value = false;
    }
};

const fetchInitialData = async () => {
    loading.value = true;
    try {
        const [purchResp, prodResp, itemResp] = await Promise.all([
            api.get('/purchases'),
            api.get('/producers'),
            api.get('/products')
        ]);
        purchases.value = purchResp.data;
        producers.value = prodResp.data;
        products.value = itemResp.data;
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const addItem = () => newPurchase.value.items.push({ product_id: '', qty: 1, cost_price: 0 });
const removeItem = (index: number) => newPurchase.value.items.splice(index, 1);

const submitPurchase = async () => {
    isSubmitting.value = true;
    try {
        await api.post('/purchases', newPurchase.value);
        showFormModal.value = false;
        fetchInitialData();
    } catch (error) {
        console.error(error);
    } finally {
        isSubmitting.value = false;
    }
};

const completeStockIn = async (purchase: any) => {
    if (!confirm(t('stock_in.confirm_arrived'))) return;
    try {
        await api.post(`/purchases/${purchase.id}/complete`);
        fetchInitialData();
    } catch (error) {
        console.error(error);
    }
};

const openProofModal = (purchase: any) => {
    selectedPurchase.value = purchase;
    fileProof.value = null;
    showProofModal.value = true;
};

const handleFileUpload = (event: any) => {
    fileProof.value = event.target.files[0];
};

const submitProof = async () => {
    if (!fileProof.value) return;
    isSubmitting.value = true;
    const fd = new FormData();
    fd.append('payment_proof', fileProof.value);
    fd.append('payment_method', paymentMethod.value);

    try {
        await api.post(`/purchases/${selectedPurchase.value.id}/upload-proof`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        showProofModal.value = false;
        fetchInitialData();
    } catch (error) {
        console.error(error);
    } finally {
        isSubmitting.value = false;
    }
};

const verifyPayment = async (status: 'paid' | 'unpaid') => {
    try {
        await api.put(`/purchases/${selectedPurchase.value.id}/verify`, { status });
        showDetailsModal.value = false;
        fetchInitialData();
    } catch (error: any) {
        console.error(error);
        alert(error.response?.data?.message || 'Gagal memverifikasi pembayaran.');
    }
};

const openDetails = (purchase: any) => {
    selectedPurchase.value = purchase;
    showDetailsModal.value = true;
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'pending': return 'bg-amber-500/10 text-amber-500 border-amber-500/20';
        case 'completed': return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20';
        case 'cancelled': return 'bg-rose-500/10 text-rose-500 border-rose-500/20';
        default: return 'bg-slate-500/10 text-slate-500 border-slate-500/20';
    }
};

const getPaymentBadge = (status: string) => {
    switch (status) {
        case 'unpaid': return 'bg-rose-600 shadow-xl shadow-rose-600/10 text-white border-rose-600';
        case 'pending_verification': return 'bg-amber-500/10 text-amber-500 border-amber-500/20 animate-pulse';
        case 'paid': return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20';
        default: return 'bg-slate-500/10 text-slate-500 border-slate-500/20';
    }
};

onMounted(fetchInitialData);
</script>

<template>
<div class="space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-700 pb-24">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-4xl font-black tracking-tight mb-2 text-white flex items-center gap-3">
                <Inbox class="w-10 h-10 text-blue-500" />
                {{ t('stock_in.title') }}
            </h1>
            <p class="text-slate-500 uppercase tracking-widest text-sm font-bold">{{ t('stock_in.subtitle') }}</p>
        </div>
        <button @click="showFormModal = true" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-4 rounded-[32px] font-black uppercase tracking-widest text-xs flex items-center gap-3 shadow-xl shadow-blue-600/20 active:scale-95 transition-all text-lg">
            <Package class="w-5 h-5" />
            {{ t('stock_in.add') }}
        </button>
    </div>

    <!-- Purchases Table -->
    <div class="bg-white/5 border border-white/10 rounded-[48px] overflow-hidden backdrop-blur-3xl shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-white/5 bg-white/[0.02]">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-500 italic">{{ t('stock_in.ref_date') }}</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-500 italic">{{ t('stock_in.producer') }}</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-500 italic text-right">{{ t('stock_in.total_amount') }}</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-500 italic text-center">{{ t('stock_in.item_status') }}</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-500 italic text-center">{{ t('stock_in.payment_status') }}</th>
                        <th class="px-8 py-6"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <tr v-for="purch in purchases" :key="purch.id" class="hover:bg-white/[0.04] transition-colors group">
                        <td class="px-8 py-6">
                            <p class="font-bold text-white mb-0.5 font-mono text-sm">#{{ purch.purchase_number }}</p>
                            <p class="text-[10px] text-slate-500 uppercase tracking-widest font-black flex items-center gap-2">
                                <Calendar class="w-3 h-3 text-blue-500" /> {{ new Date(purch.purchase_date).toLocaleDateString() }}
                            </p>
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-blue-600/10 rounded-xl flex items-center justify-center">
                                    <Truck class="w-5 h-5 text-blue-400" />
                                </div>
                                <span class="font-bold text-white tracking-tight uppercase italic text-sm">{{ purch.producer?.name }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6 text-right">
                            <p class="font-bold text-blue-400 font-mono tracking-tighter italic text-lg">{{ formatCurrency(purch.total_amount) }}</p>
                        </td>
                        <td class="px-8 py-6 text-center">
                            <span :class="getStatusBadge(purch.status)" class="px-4 py-1.5 rounded-xl border text-[9px] font-black uppercase tracking-widest italic shadow-lg">
                                {{ purch.status }}
                            </span>
                        </td>
                        <td class="px-8 py-6 text-center">
                            <span :class="getPaymentBadge(purch.payment_status)" class="px-4 py-1.5 rounded-xl border text-[9px] font-black uppercase tracking-widest italic shadow-lg">
                                {{ purch.payment_status === 'unpaid' ? t('stock_in.payment_unpaid') : purch.payment_status }}
                            </span>
                        </td>
                        <td class="px-8 py-6 text-right">
                            <div class="flex items-center justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button v-if="purch.status === 'pending'" @click="completeStockIn(purch)" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-1.5 transition-all shadow-xl shadow-emerald-500/20 active:scale-90">
                                    <CheckCircle2 class="w-3.5 h-3.5" /> {{ t('stock_in.confirm_stock') }}
                                </button>
                                <button v-if="purch.status !== 'pending' && purch.payment_status === 'unpaid' && (auth.user?.role === 'owner' || auth.user?.role === 'admin')" @click="openPaymentSettle(purch)" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-1.5 transition-all shadow-xl shadow-blue-500/20 active:scale-90">
                                    <Plus class="w-3.5 h-3.5" /> Pelunasan
                                </button>
                                <button @click="openDetails(purch)" class="bg-white/5 hover:bg-blue-600 text-white p-2.5 rounded-xl transition-all border border-white/5">
                                    <ChevronRight class="w-5 h-5" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PURCHASE FORM MODAL -->
    <div v-if="showFormModal" class="fixed inset-0 z-[120] flex items-center justify-center p-6 backdrop-blur-md bg-black/60">
        <div class="absolute inset-0" @click="showFormModal = false"></div>
        <div class="bg-slate-900 border border-white/10 w-full max-w-4xl overflow-hidden rounded-[40px] flex flex-col relative z-10 shadow-[0_32px_120px_rgba(0,0,0,1)] animate-in zoom-in-95 duration-300">
            <div class="p-8 border-b border-white/10 flex justify-between items-center bg-black/20">
                <div>
                    <h3 class="font-black text-2xl text-white tracking-tighter italic">{{ t('stock_in.modal_title') }}</h3>
                    <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest mt-1">{{ t('stock_in.modal_subtitle') }}</p>
                </div>
                <button @click="showFormModal = false" class="text-slate-500 hover:text-white"><X class="w-6 h-6"/></button>
            </div>
            
            <div class="p-8 max-h-[70vh] overflow-y-auto space-y-8 scrollbar-hide">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 italic">{{ t('stock_in.select_producer') }}</label>
                        <select v-model="newPurchase.producer_id" class="w-full bg-black/50 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-blue-500 transition-all font-bold">
                            <option value="">-- {{ t('stock_in.select_vendor') }} --</option>
                            <option v-for="p in producers" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 italic">{{ t('stock_in.transaction_date') }}</label>
                        <input v-model="newPurchase.purchase_date" type="date" class="w-full bg-black/50 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-blue-500 transition-all font-bold">
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest italic">{{ t('stock_in.item_detail') }}</label>
                        <div class="flex gap-4">
                            <button @click.prevent="showQuickProductModal = true" class="text-emerald-500 font-bold text-xs flex items-center gap-1 hover:text-emerald-400">
                                <Plus class="w-4 h-4" /> Produk Baru
                            </button>
                            <button @click.prevent="addItem" class="text-blue-500 font-bold text-xs flex items-center gap-1 hover:text-blue-400">
                                <Plus class="w-4 h-4" /> {{ t('stock_in.add_row') }}
                            </button>
                        </div>
                    </div>
                    <div v-for="(item, idx) in newPurchase.items" :key="idx" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end bg-white/5 p-6 rounded-3xl border border-white/5 group">
                        <div class="md:col-span-5">
                            <label class="text-[9px] font-black text-slate-600 uppercase mb-2 block">{{ t('stock_in.select_product') }}</label>
                            <select v-model="item.product_id" @change="() => { 
                                const p = products.find(prod => prod.id == item.product_id); 
                                if(p) item.cost_price = p.cost_price;
                            }" class="w-full bg-black/40 border border-white/5 rounded-xl px-4 py-3 text-white focus:border-blue-500 text-sm font-bold">
                                <option value="">-- {{ t('common.no_data') }} --</option>
                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[9px] font-black text-slate-600 uppercase mb-2 block">{{ t('invoices.qty') }}</label>
                            <input v-model="item.qty" type="number" class="w-full bg-black/40 border border-white/5 rounded-xl px-4 py-3 text-white text-center font-bold">
                        </div>
                        <div class="md:col-span-4">
                            <label class="text-[9px] font-black text-slate-600 uppercase mb-2 block">{{ t('stock_in.buy_price') }}</label>
                            <input v-model="item.cost_price" type="number" class="w-full bg-black/40 border border-white/5 rounded-xl px-4 py-3 text-blue-400 font-mono font-bold">
                        </div>
                        <div class="md:col-span-1 py-1">
                            <button @click="removeItem(idx)" class="text-slate-700 hover:text-red-500 transition-colors p-2">
                                <X class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-8 bg-black/30 border-t border-white/10 flex items-center justify-between">
                <div>
                  <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 italic">{{ t('stock_in.total_calc') }}</p>
                  <p class="text-3xl font-black text-white font-mono tracking-tighter italic">
                    {{ formatCurrency(newPurchase.items.reduce((acc, i) => acc + (i.qty * i.cost_price), 0)) }}
                  </p>
                </div>
                <button @click="submitPurchase" :disabled="isSubmitting" class="bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white px-12 py-5 rounded-[24px] font-black uppercase tracking-widest text-[11px] flex items-center gap-3 active:scale-95 transition-all">
                    <Loader2 v-if="isSubmitting" class="animate-spin w-5 h-5" />
                    {{ isSubmitting ? t('common.loading') : t('stock_in.save_transaction') }}
                </button>
            </div>
        </div>
    </div>

    <!-- PURCAHSE DETAILS & DUAL CONTROL MODAL -->
    <div v-if="showDetailsModal && selectedPurchase" class="fixed inset-0 z-[120] flex items-center justify-center p-6 backdrop-blur-md bg-black/80">
        <div class="absolute inset-0" @click="showDetailsModal = false"></div>
        <div class="bg-slate-900 border border-white/10 w-full max-w-2xl overflow-hidden rounded-[40px] flex flex-col relative z-10 shadow-[0_32px_120px_rgba(0,0,0,1)] animate-in zoom-in-95 duration-300">
            <div class="p-8 border-b border-white/10 flex justify-between items-center bg-black/20">
                <h3 class="font-black text-xl text-white tracking-tighter italic">Order Detail: #{{ selectedPurchase.purchase_number }}</h3>
                <button @click="showDetailsModal = false" class="text-slate-500 hover:text-white"><X class="w-6 h-6"/></button>
            </div>

            <div class="p-8 space-y-8 overflow-y-auto max-h-[60vh] scrollbar-hide">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 italic">{{ t('stock_in.producer_info') }}</p>
                        <p class="text-lg font-black text-white italic uppercase">{{ selectedPurchase.producer?.name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 italic">{{ t('stock_in.total_transaction') }}</p>
                        <p class="text-2xl font-black text-emerald-400 font-mono tracking-tighter italic">{{ formatCurrency(selectedPurchase.total_amount) }}</p>
                    </div>
                </div>

                <div class="bg-black/40 rounded-3xl p-6 border border-white/5 space-y-4">
                    <div v-for="item in selectedPurchase.items" :key="item.id" class="flex justify-between items-center text-sm font-bold">
                        <div class="flex flex-col">
                            <span class="text-white uppercase italic tracking-tight">{{ item.product?.name }}</span>
                            <span class="text-[10px] text-slate-500 italic">{{ item.qty }} {{ item.product?.unit }} x {{ formatCurrency(item.cost_price) }}</span>
                        </div>
                        <span class="text-blue-400 font-mono">{{ formatCurrency(item.qty * item.cost_price) }}</span>
                    </div>
                </div>

                <!-- Dual Control Section -->
                <div v-if="selectedPurchase.payment_status === 'unpaid'" class="bg-rose-500/10 border border-rose-500/30 p-8 rounded-[32px] space-y-4">
                    <div class="flex items-center gap-4 text-rose-500">
                        <AlertCircle class="w-7 h-7" />
                        <h4 class="font-black text-lg uppercase tracking-tight">{{ t('stock_in.status_unpaid') }}</h4>
                    </div>
                    <button v-if="auth.isAdmin || auth.isOwner" @click="openProofModal(selectedPurchase)" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl uppercase tracking-widest text-xs shadow-xl active:scale-95 transition-all">{{ t('stock_in.submit_proof') }}</button>
                    <p class="text-center text-[9px] font-black text-rose-400/50 uppercase italic tracking-widest">{{ t('stock_in.only_admin_upload') }}</p>
                </div>

                <div v-if="selectedPurchase.payment_status === 'pending_verification'" class="bg-amber-500/10 border border-amber-500/30 p-8 rounded-[32px] space-y-6 animate-pulse">
                    <div class="flex items-center gap-4 text-amber-500">
                        <AlertCircle class="w-7 h-7" />
                        <div>
                            <h4 class="font-black text-lg uppercase tracking-tight">{{ t('stock_in.waiting_verification') }}</h4>
                            <p class="text-[10px] font-black italic">{{ t('stock_in.proof_uploaded', { method: selectedPurchase.payment_method?.toUpperCase() }) }}</p>
                        </div>
                    </div>
                    
                    <div v-if="auth.isOwner" class="flex gap-4">
                        <button @click="verifyPayment('unpaid')" class="flex-1 bg-rose-500/20 hover:bg-rose-500 text-rose-500 hover:text-white border border-rose-500/20 py-4 rounded-xl font-black uppercase text-[10px] transition-all">{{ t('stock_in.reject_proof') }}</button>
                        <button @click="verifyPayment('paid')" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-black py-4 rounded-xl uppercase text-[10px] transition-all shadow-xl shadow-emerald-500/20">{{ t('stock_in.validate_paid') }}</button>
                    </div>
                </div>

                <div v-if="selectedPurchase.payment_status === 'paid'" class="bg-emerald-500/10 border border-emerald-500/30 p-8 rounded-[32px] flex items-center justify-center gap-4 text-emerald-500">
                    <CheckCircle2 class="w-8 h-8" />
                    <h4 class="font-black text-2xl tracking-tighter italic uppercase">{{ t('stock_in.paid_success') }}</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- PROOF UPLOAD MODAL -->
    <div v-if="showProofModal" class="fixed inset-0 z-[130] flex items-center justify-center p-6 backdrop-blur-xl bg-black/60 transition-all">
        <div class="absolute inset-0" @click="showProofModal = false"></div>
        <div class="bg-slate-900 border border-white/10 w-full max-w-lg overflow-hidden rounded-[40px] flex flex-col relative z-10 shadow-[0_32px_120px_rgba(0,0,0,1)]">
            <div class="p-8 border-b border-white/10 flex justify-between items-center bg-black/20">
                <h3 class="font-black text-2xl text-white tracking-tighter italic">{{ t('stock_in.upload_proof_title') }}</h3>
                <button @click="showProofModal = false" class="text-slate-500 hover:text-white"><X class="w-6 h-6"/></button>
            </div>
            <div class="p-8 space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 italic">{{ t('stock_in.payment_method') }}</label>
                    <div class="grid grid-cols-2 gap-4">
                        <button @click="paymentMethod = 'cash'" :class="paymentMethod === 'cash' ? 'bg-blue-600 border-blue-500 text-white' : 'bg-white/5 text-slate-500 border-white/5'" class="py-3 rounded-xl border font-bold text-xs uppercase transition-all">{{ t('stock_in.cash') }}</button>
                        <button @click="paymentMethod = 'bank'" :class="paymentMethod === 'bank' ? 'bg-blue-600 border-blue-500 text-white' : 'bg-white/5 text-slate-500 border-white/5'" class="py-3 rounded-xl border font-bold text-xs uppercase transition-all">{{ t('stock_in.bank_transfer') }}</button>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 italic">{{ t('stock_in.select_receipt') }}</label>
                    <input type="file" @change="handleFileUpload" accept="image/*" class="w-full text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-600/20 file:text-blue-400 hover:file:bg-blue-600 hover:file:text-white transition-all cursor-pointer"/>
                </div>
                <button @click="submitProof" :disabled="!fileProof || isSubmitting" class="w-full bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-black py-5 rounded-[24px] uppercase tracking-widest text-[11px] mt-4 flex items-center justify-center gap-3 active:scale-95 transition-all">
                    <Loader2 v-if="isSubmitting" class="animate-spin w-5 h-5" />
                    {{ isSubmitting ? t('common.loading') : t('stock_in.submit_payment') }}
                </button>
            </div>
        </div>
    </div>
    
    <!-- SETTLE DEBT MODAL -->
    <div v-if="showSettleModal && selectedPurchase" class="fixed inset-0 z-[140] flex items-center justify-center p-6 backdrop-blur-xl bg-black/80 transition-all">
        <div class="absolute inset-0" @click="showSettleModal = false"></div>
        <div class="bg-slate-900 border border-white/10 w-full max-w-lg overflow-hidden rounded-[40px] flex flex-col relative z-10 shadow-[0_32px_120px_rgba(0,0,0,1)] animate-in zoom-in-95 duration-300">
            <div class="p-8 border-b border-white/10 flex justify-between items-center bg-black/20">
                <div>
                    <h3 class="font-black text-2xl text-white tracking-tighter italic uppercase">Pelunasan Hutang</h3>
                    <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest mt-1">#{{ selectedPurchase.purchase_number }}</p>
                </div>
                <button @click="showSettleModal = false" class="text-slate-500 hover:text-white"><X class="w-6 h-6"/></button>
            </div>
            
            <div class="p-8 space-y-6">
                <div class="bg-blue-600/10 border border-blue-500/20 p-6 rounded-3xl flex justify-between items-center">
                    <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Total Tagihan</span>
                    <span class="text-2xl font-black text-white italic tracking-tighter">{{ formatCurrency(selectedPurchase.total_amount) }}</span>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 italic">Metode Pembayaran</label>
                        <div class="grid grid-cols-2 gap-4">
                            <button @click="settleForm.payment_method = 'cash'" :class="settleForm.payment_method === 'cash' ? 'bg-blue-600 border-blue-500 text-white shadow-lg shadow-blue-600/20' : 'bg-white/5 text-slate-500 border-white/5'" class="py-4 rounded-2xl border font-black text-[10px] uppercase tracking-widest transition-all italic">Tunai (Cash)</button>
                            <button @click="settleForm.payment_method = 'bank'" :class="settleForm.payment_method === 'bank' ? 'bg-blue-600 border-blue-500 text-white shadow-lg shadow-blue-600/20' : 'bg-white/5 text-slate-500 border-white/5'" class="py-4 rounded-2xl border font-black text-[10px] uppercase tracking-widest transition-all italic">Transfer Bank</button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 italic">Tanggal Bayar</label>
                        <input v-model="settleForm.payment_date" type="date" class="w-full bg-black/50 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-blue-500 transition-all font-bold">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 italic">Catatan Khusus</label>
                        <textarea v-model="settleForm.note" class="w-full bg-black/50 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-blue-500 transition-all text-sm font-medium" rows="2"></textarea>
                    </div>
                </div>

                <button @click="handleSettlePurchase" :disabled="isSubmitting" class="w-full bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-black py-5 rounded-[24px] uppercase tracking-widest text-[11px] mt-4 flex items-center justify-center gap-3 active:scale-95 transition-all shadow-xl shadow-emerald-500/20">
                    <Loader2 v-if="isSubmitting" class="animate-spin w-5 h-5" />
                    {{ isSubmitting ? t('common.loading') : 'Konfirmasi Pelunasan' }}
                </button>
            </div>
        </div>
    </div>
    <!-- QUICK CREATE PRODUCT MODAL -->
    <div v-if="showQuickProductModal" class="fixed inset-0 z-[150] flex items-center justify-center p-6 backdrop-blur-md bg-black/60">
        <div class="absolute inset-0" @click="showQuickProductModal = false"></div>
        <div class="bg-slate-900 border border-white/10 w-full max-w-lg overflow-hidden rounded-[40px] flex flex-col relative z-10 shadow-[0_32px_120px_rgba(0,0,0,1)] animate-in zoom-in-95 duration-300">
            <div class="p-8 border-b border-white/10 flex justify-between items-center bg-black/20">
                <h3 class="font-black text-2xl text-white tracking-tighter italic">Tambah Produk Baru</h3>
                <button @click="showQuickProductModal = false" class="text-slate-500 hover:text-white"><X class="w-6 h-6"/></button>
            </div>
            
            <div class="p-8 space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 italic">{{ t('inventory.product_name') }}</label>
                    <input v-model="quickProductForm.name" type="text" class="w-full bg-black/50 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-blue-500 transition-all font-bold">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 italic">{{ t('inventory.unit') }}</label>
                        <input v-model="quickProductForm.unit" type="text" placeholder="Kg, Box" class="w-full bg-black/50 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-blue-500 transition-all font-bold">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 italic">{{ t('inventory.price') }} Jual</label>
                        <input v-model="quickProductForm.price" type="number" class="w-full bg-black/50 border border-white/10 rounded-2xl px-5 py-4 text-blue-400 focus:outline-none focus:border-blue-500 transition-all font-bold font-mono">
                    </div>
                </div>

                <div class="pt-2">
                    <button @click="submitQuickProduct" :disabled="isSubmitting" class="w-full bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-black py-5 rounded-[32px] uppercase tracking-widest text-[11px] flex items-center justify-center gap-3 active:scale-95 transition-all shadow-xl shadow-emerald-500/20">
                        <Loader2 v-if="isSubmitting" class="animate-spin w-5 h-5" />
                        {{ isSubmitting ? t('common.loading') : 'Simpan Produk' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
