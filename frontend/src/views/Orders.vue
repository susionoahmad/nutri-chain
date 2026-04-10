<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '@/api';
import { useI18n } from 'vue-i18n';
import { 
    ShoppingCart, Plus, Trash2, Loader2, X, 
    Calendar, User, ChevronRight, Package, CheckCircle2,
    AlertCircle
} from 'lucide-vue-next';
import { formatCurrency } from '@/utils/format';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const { t } = useI18n();
const orders = ref<any[]>([]);
const products = ref<any[]>([]);
const customers = ref<any[]>([]);
const loading = ref(false);
const isSubmitting = ref(false);
const showModal = ref(false);
const showDetail = ref(false);
const selectedOrder = ref<any>(null);
const errorMessage = ref('');

const form = ref({
    customer_id: '',
    order_date: new Date().toISOString().split('T')[0],
    delivery_date: '',
    notes: '',
    items: [
        { product_id: '', qty: 1, price: 0 }
    ]
});

const fetchOrders = async () => {
    loading.value = true;
    try {
        const response = await api.get('/orders');
        // Laravel Resource returns { data: [...] } instead of direct array
        orders.value = response.data.data || response.data;
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const hasLoadedLookups = ref(false);
const fetchLookups = async () => {
    if (hasLoadedLookups.value) return;
    try {
        const promises = [api.get('/products')];
        if (!auth.isCustomer && !auth.isWarehouse && !auth.isDriver) {
            promises.push(api.get('/customers'));
        }

        const responses = await Promise.all(promises);
        products.value = responses[0].data;
        if (!auth.isCustomer && !auth.isWarehouse && !auth.isDriver) {
            customers.value = responses[1].data;
        }
        hasLoadedLookups.value = true;
    } catch (error) {
        console.error('Failed to load lookups:', error);
    }
};

const openModal = async () => {
    showModal.value = true;
    await fetchLookups();
};

const addItem = () => {
    form.value.items.push({ product_id: '', qty: 1, price: 0 });
};

const removeItem = (index: number) => {
    if (form.value.items.length > 1) {
        form.value.items.splice(index, 1);
    }
};

const updateItemPrice = (index: number) => {
    const product = products.value.find(p => p.id === form.value.items[index].product_id);
    if (product) {
        form.value.items[index].price = product.price;
    }
};

const orderTotal = computed(() => {
    return form.value.items.reduce((total, item) => {
        return total + (item.price * item.qty);
    }, 0);
});

const handleSubmit = async () => {
    if (!form.value.customer_id && !auth.isCustomer) {
        errorMessage.value = 'Please select a customer first.';
        return;
    }

    if (form.value.items.some(item => !item.product_id)) {
        errorMessage.value = 'One or more items have no product selected.';
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = '';

    try {
        await api.post('/orders', form.value);
        showModal.value = false;
        resetForm();
        await fetchOrders();
    } catch (error: any) {
        console.error('Submit Error:', error);
        errorMessage.value = error.response?.data?.message || 'Failed to place order.';
    } finally {
        isSubmitting.value = false;
    }
};

const openDetail = (order: any) => {
    selectedOrder.value = order;
    showDetail.value = true;
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'pending': return 'bg-amber-500/10 text-amber-500 border-amber-500/20';
        case 'confirmed': return 'bg-blue-500/10 text-blue-500 border-blue-500/20';
        case 'on_delivery': return 'bg-indigo-500/10 text-indigo-500 border-indigo-500/20';
        case 'delivered': return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20';
        case 'cancelled': return 'bg-red-500/10 text-red-500 border-red-500/20';
        default: return 'bg-slate-500/10 text-slate-500 border-slate-500/20';
    }
};

const resetForm = () => {
    form.value = {
        customer_id: '',
        order_date: new Date().toISOString().split('T')[0],
        delivery_date: '',
        notes: '',
        items: [{ product_id: '', qty: 1, price: 0 }]
    };
    errorMessage.value = '';
};

// canEditStatus now enforces strict state machine rules based on the backend API
// to ensure no one (not even the Owner) can transition orders backward,
// preventing data corruption with stock and invoices.
const canEditStatus = (targetStatus: string) => {
    const currentStatus = selectedOrder.value?.status;
    
    // If order is cancelled, it is locked.
    if (currentStatus === 'cancelled') return false;

    // Progression transitions
    if (targetStatus === 'confirmed' && currentStatus === 'pending') {
        return auth.isAdmin || auth.isOwner;
    }
    if (targetStatus === 'on_delivery' && currentStatus === 'confirmed') {
        return auth.isWarehouse || auth.isAdmin || auth.isOwner;
    }
    if (targetStatus === 'delivered' && currentStatus === 'on_delivery') {
        return auth.isDriver || auth.isAdmin || auth.isOwner;
    }

    // Cancellation transitions
    if (targetStatus === 'cancelled') {
        if (currentStatus === 'pending') {
             return auth.isCustomer || auth.isAdmin || auth.isOwner;
        }
        if (currentStatus === 'confirmed' || currentStatus === 'on_delivery') {
             return auth.isAdmin || auth.isOwner;
        }
        if (currentStatus === 'delivered') {
             return auth.isOwner; // Only owner can cancel delivered orders due to invoice voiding
        }
    }
    
    return false;
};

const updateOrderStatus = async (newStatus: string) => {
    if (!selectedOrder.value) return;
    
    // Choose specific endpoint based on transition
    let endpoint = `/orders/${selectedOrder.value.id}`;
    if (newStatus === 'confirmed') endpoint += '/confirm';
    else if (newStatus === 'on_delivery') endpoint += '/dispatch';
    else if (newStatus === 'delivered') endpoint += '/deliver';
    else if (newStatus === 'cancelled') endpoint += '/cancel';

    try {
        const response = await api.patch(endpoint, {
            status: newStatus
        });
        selectedOrder.value = response.data.data || response.data;
        // Update the order in the list too
        const index = orders.value.findIndex(o => o.id === selectedOrder.value.id);
        if (index !== -1) {
            orders.value[index] = selectedOrder.value;
        }
    } catch (error: any) {
        console.error('Update status error:', error);
        const msg = error.response?.data?.message || 'Gagal memperbarui status.';
        const subMsg = error.response?.data?.errors?.status?.[0] || '';
        alert(`${msg} ${subMsg}`);
    }
};

// Human-readable label for statuses
const statusLabel = (s: string) => {
    const map: Record<string, string> = {
        pending:     '⏳ Pending',
        confirmed:   '✅ Confirmed',
        on_delivery: '🚚 On Delivery',
        delivered:   '📦 Delivered',
        cancelled:   '❌ Cancelled',
    };
    return map[s] ?? s;
};

onMounted(fetchOrders);
</script>

<template>
<div class="space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-4xl font-black tracking-tight mb-2 text-white">{{ t('orders.title') }}</h1>
            <p class="text-slate-500">{{ t('orders.subtitle') }}</p>
        </div>
        <button 
            v-if="!auth.isWarehouse && !auth.isDriver"
            @click="openModal"
            class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-4 rounded-2xl font-bold flex items-center justify-center gap-2 shadow-lg shadow-blue-500/20 active:scale-95 transition-all text-lg"
        >
            <Plus class="w-6 h-6" />
            {{ t('orders.new_order') }}
        </button>
    </div>

    <!-- Orders List -->
    <div class="space-y-4">
        <div v-if="loading" class="space-y-4">
            <div v-for="i in 3" :key="i" class="h-32 bg-white/5 border border-white/10 rounded-[40px] animate-pulse flex items-center px-10 gap-8">
                <div class="w-16 h-16 bg-white/10 rounded-2xl"></div>
                <div class="flex-1 space-y-4">
                    <div class="h-4 bg-white/10 rounded w-1/4"></div>
                    <div class="h-3 bg-white/5 rounded w-1/2"></div>
                </div>
            </div>
        </div>
        
        <div v-else-if="orders.length === 0" class="py-24 text-center bg-white/5 rounded-[40px] border border-white/5">
            <ShoppingCart class="w-16 h-16 mx-auto text-slate-800 mb-4" />
            <p class="text-xl text-slate-600 font-bold uppercase tracking-widest">{{ t('common.no_data') }}</p>
        </div>

        <div v-for="order in orders" :key="order.id" @click="openDetail(order)" class="bg-white/5 border border-white/10 rounded-[40px] p-8 hover:bg-white/[0.08] transition-all group cursor-pointer border-l-4" :class="[order.status === 'delivered' ? 'border-l-emerald-500/50' : 'border-l-blue-500/50']">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-8">
                <!-- Order Info -->
                <div class="flex items-start gap-6">
                    <div class="w-16 h-16 bg-slate-900 rounded-2xl flex items-center justify-center text-blue-400 shrink-0 border border-white/5 group-hover:scale-110 transition-transform shadow-xl">
                        <ShoppingCart class="w-8 h-8" />
                    </div>
                    <div>
                        <div class="flex items-center gap-4 mb-2">
                            <span class="text-xl font-black text-white tracking-tighter">{{ order.order_number }}</span>
                            <span :class="getStatusColor(order.status)" class="px-4 py-1 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] border">
                                {{ order.status }}
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm font-bold text-slate-500 uppercase tracking-widest">
                            <div class="flex items-center gap-2">
                                <User class="w-4 h-4 text-blue-500" />
                                <span>{{ order.customer?.name }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Calendar class="w-4 h-4 text-slate-600" />
                                <span class="font-mono">{{ order.order_date ? new Date(order.order_date).toLocaleDateString() : '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Value & Actions -->
                <div class="flex items-center justify-between md:justify-end gap-10">
                    <div class="text-right" v-if="!auth.isWarehouse && !auth.isDriver">
                        <p class="text-[10px] text-slate-500 font-black uppercase tracking-[0.2em] mb-1">{{ t('orders.total_amount') }}</p>
                        <p class="text-3xl font-black text-white font-mono italic tracking-tighter">{{ formatCurrency(order.total_amount) }}</p>
                    </div>
                    <button class="w-14 h-14 flex items-center justify-center bg-white/5 hover:bg-blue-600 rounded-2xl text-slate-500 group-hover:text-white transition-all shadow-xl active:scale-95">
                        <ChevronRight class="w-7 h-7" />
                    </button>
                </div>
            </div>

            <div class="mt-8 pt-8 border-t border-white/5 grid grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="item in (order.items || []).slice(0, 4)" :key="item.id" class="flex items-center gap-3 text-xs font-bold text-slate-400">
                    <div class="w-2 h-2 bg-blue-500 rounded-full shadow-[0_0_8px_rgba(59,130,246,0.6)]"></div>
                    <span class="truncate">{{ item.qty }}x {{ item.product?.name }}</span>
                </div>
                <div v-if="(order.items || []).length > 4" class="text-[10px] text-blue-500 font-black uppercase tracking-widest">
                    + {{ order.items.length - 4 }} items
                </div>
            </div>
        </div>
    </div>

    <!-- Order Detail Drawer -->
    <div v-if="showDetail" class="fixed inset-0 z-[110] flex items-center justify-end">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-md" @click="showDetail = false"></div>
        <div class="w-full max-w-2xl h-full bg-slate-950 border-l border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.8)] relative z-10 flex flex-col animate-in slide-in-from-right duration-500">
            <!-- Drawer Header -->
            <div class="p-10 border-b border-white/10 flex items-center justify-between bg-black/20">
                <div class="flex items-center gap-5">
                    <div>
                        <h2 class="text-3xl font-black text-white tracking-tighter">{{ selectedOrder?.order_number }}</h2>
                        <span :class="getStatusColor(selectedOrder?.status)" class="px-4 py-1 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] border inline-block mt-2">
                            {{ selectedOrder?.status }}
                        </span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button @click="showDetail = false" class="p-3 bg-white/5 hover:bg-white/10 rounded-full transition-colors text-slate-400 hover:text-white transition-all">
                        <X class="w-6 h-6" />
                    </button>
                </div>
            </div>

            <!-- Status Quick Bar (New Row) -->
            <div class="px-10 py-5 bg-white/5 border-b border-white/5 flex flex-wrap items-center gap-3">
                <span class="text-[9px] font-black text-slate-600 uppercase tracking-[0.2em] mr-2">Update Status:</span>
                <template v-for="s in ['pending', 'confirmed', 'on_delivery', 'delivered', 'cancelled']" :key="s">
                    <button 
                        v-if="canEditStatus(s)"
                        @click="updateOrderStatus(s)"
                        :disabled="selectedOrder?.status === s"
                        :class="[
                            selectedOrder?.status === s 
                                ? getStatusColor(s) + ' shadow-lg scale-105 border py-2 px-4 cursor-default' 
                                : 'text-slate-400 hover:text-white bg-white/5 hover:bg-white/10 py-2 px-4'
                        ]"
                        class="rounded-xl text-[9px] font-black uppercase tracking-widest transition-all active:scale-95 whitespace-nowrap"
                    >
                        {{ statusLabel(s) }}
                    </button>
                </template>
                <span v-if="!['pending','confirmed','on_delivery','delivered','cancelled'].some(s => canEditStatus(s))" class="text-[9px] text-slate-600 italic">
                    Tidak ada aksi tersedia untuk role Anda.
                </span>
            </div>


            <!-- Drawer Content -->
            <div class="flex-1 overflow-y-auto p-12 space-y-12 custom-scrollbar">
                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-10 p-10 rounded-[40px] bg-white/[0.03] border border-white/5 shadow-2xl">
                    <div class="col-span-2 md:col-span-1">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">{{ t('customers.name') }}</p>
                        <p class="text-xl font-bold text-white">{{ selectedOrder?.customer?.name }}</p>
                        <p class="text-xs text-blue-400 font-bold mt-2 font-mono">{{ selectedOrder?.customer?.phone }}</p>
                    </div>
                    <div class="col-span-2 md:col-span-1 md:text-right">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Order Placed</p>
                        <p class="text-xl font-bold text-white font-mono">{{ new Date(selectedOrder?.order_date).toLocaleDateString() }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">{{ t('customers.address') }}</p>
                        <p class="text-lg text-slate-300 leading-relaxed font-medium">{{ selectedOrder?.customer?.address }}</p>
                    </div>
                </div>

                <!-- Items Table -->
                <div>
                    <h3 class="text-2xl font-black text-white mb-8 flex items-center gap-4 tracking-tighter uppercase italic">
                        <Package class="w-7 h-7 text-blue-500" /> {{ t('orders.detail') }}
                    </h3>
                    <div class="space-y-6">
                        <div v-for="item in selectedOrder?.items" :key="item.id" class="flex items-center justify-between p-6 bg-white/[0.02] rounded-[32px] border border-white/5 group hover:bg-white/[0.05] transition-all">
                            <div class="flex items-center gap-5 text-white">
                                <div class="w-12 h-12 bg-slate-900 rounded-xl flex items-center justify-center font-black text-blue-500 border border-white/5 shadow-xl">{{ item.qty }}</div>
                                <div>
                                    <p class="text-lg font-bold">{{ item.product?.name }}</p>
                                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">{{ item.product?.unit }}</p>
                                </div>
                            </div>
                            <div class="text-right" v-if="!auth.isWarehouse && !auth.isDriver">
                                <p class="text-xl font-black text-white font-mono italic tracking-tighter">{{ formatCurrency(item.price * item.qty) }}</p>
                                <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest mt-1">{{ formatCurrency(item.price) }} / unit</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="selectedOrder?.notes" class="p-8 bg-blue-600/5 border border-blue-500/10 rounded-[32px] italic">
                    <p class="text-[10px] font-black text-blue-500 uppercase tracking-[0.2em] mb-3">Notes</p>
                    <p class="text-slate-300 text-lg leading-relaxed">"{{ selectedOrder?.notes }}"</p>
                </div>

                <!-- Timeline / Audit Log Section -->
                <div v-if="selectedOrder?.activities?.length > 0" class="pt-12 border-t border-white/5">
                    <h3 class="text-2xl font-black text-white mb-8 flex items-center gap-4 tracking-tighter uppercase italic">
                        <Clock class="w-7 h-7 text-amber-500" /> Riwayat Aktivitas
                    </h3>
                    <div class="relative space-y-8 before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-slate-800 before:to-transparent">
                        <div v-for="log in selectedOrder.activities" :key="log.id" class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                            <!-- Icon -->
                            <div class="flex items-center justify-center w-10 h-10 rounded-full border border-slate-700 bg-slate-900 text-slate-500 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 transition-all group-hover:scale-110 group-hover:border-blue-500/50 group-hover:text-blue-400">
                                <CheckCircle2 v-if="log.action === 'status_updated'" class="w-5 h-5" />
                                <Package v-else class="w-5 h-5" />
                            </div>
                            <!-- Content -->
                            <div class="w-[calc(100%-4rem)] md:w-[45%] p-6 rounded-3xl border border-white/5 bg-white/[0.02] shadow-xl transition-all group-hover:bg-white/[0.04]">
                                <div class="flex items-center justify-between space-x-2 mb-1">
                                    <div class="font-black text-white italic text-xs uppercase">{{ log.causer_name }}</div>
                                    <time class="font-mono text-[10px] font-bold text-blue-500">{{ new Date(log.created_at).toLocaleString() }}</time>
                                </div>
                                <div class="text-slate-400 text-sm font-medium">{{ log.description }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Drawer Footer (Total) -->
            <div class="p-12 bg-black/40 border-t border-white/10 flex items-center justify-between sticky bottom-0 backdrop-blur-xl">
                <div>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2">{{ t('orders.total_amount') }}</p>
                    <p class="text-4xl font-black text-white font-mono italic tracking-tighter leading-none shadow-blue-500/10 drop-shadow-xl text-blue-50">{{ formatCurrency(selectedOrder?.total_amount || 0) }}</p>
                </div>
                <div class="flex flex-col items-end gap-2" v-if="selectedOrder?.invoice?.status === 'paid'">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Verified Payment</span>
                    <div class="flex items-center gap-2 text-emerald-400 text-xs font-black uppercase tracking-widest bg-emerald-500/10 px-4 py-2 rounded-xl border border-emerald-500/20">
                        <CheckCircle2 class="w-4 h-4" /> Finalized
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Order Modal -->
    <div v-if="showModal" class="fixed inset-0 z-[120] flex items-center justify-center p-6 sm:p-0 backdrop-blur-md bg-black/60 transition-all">
        <div class="absolute inset-0" @click="showModal = false"></div>
        <div class="bg-slate-900 border border-white/10 w-full max-w-4xl max-h-[90vh] overflow-hidden rounded-[50px] flex flex-col relative z-10 shadow-[0_32px_120px_rgba(0,0,0,1)] animate-in zoom-in-95 duration-500">
            
            <div class="p-12 border-b border-white/10 flex items-center justify-between shrink-0 bg-black/20">
                <div>
                    <h2 class="text-4xl font-black text-white tracking-tighter mb-2 italic">New Supply Request</h2>
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-[0.2em]">{{ t('orders.subtitle') }}</p>
                </div>
                <button @click="showModal = false" class="p-3 bg-white/5 hover:bg-white/10 rounded-full transition-colors text-slate-400 hover:text-white"><X class="w-7 h-7" /></button>
            </div>

            <div class="flex-1 overflow-y-auto p-12 space-y-12 custom-scrollbar">
                <div v-if="errorMessage" class="bg-red-500/10 border border-red-500/20 p-6 rounded-[32px] flex items-center gap-5 text-red-400 animate-bounce">
                    <AlertCircle class="w-7 h-7 shrink-0" />
                    <p class="font-bold text-lg">{{ errorMessage }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                    <div class="md:col-span-1" v-if="!auth.isCustomer">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-4 ml-1">Client Select</label>
                        <select v-model="form.customer_id" required class="w-full bg-black/40 border border-white/10 rounded-2xl py-5 px-6 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 font-bold text-lg">
                            <option value="" disabled>Choose partner...</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-4 ml-1">Order Date</label>
                        <input v-model="form.order_date" type="date" required class="w-full bg-black/40 border border-white/10 rounded-2xl py-5 px-6 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 font-mono font-bold text-lg" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-4 ml-1">Delivery ETD</label>
                        <input v-model="form.delivery_date" type="date" class="w-full bg-black/40 border border-white/10 rounded-2xl py-5 px-6 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 font-mono font-bold text-lg" />
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-2xl font-black text-white italic tracking-tighter uppercase flex items-center gap-4">
                            <Package class="w-8 h-8 text-blue-600" />
                            {{ t('orders.detail') }}
                        </h3>
                        <button type="button" @click="addItem" class="px-5 py-2 bg-blue-600/10 hover:bg-blue-600 text-blue-400 hover:text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all border border-blue-500/20">
                            + {{ t('common.new') }} Item
                        </button>
                    </div>

                    <div class="space-y-6">
                        <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-12 gap-6 items-end bg-white/[0.03] p-8 rounded-[40px] border border-white/5 relative group/item hover:bg-white/[0.05] transition-all">
                            <div class="col-span-12 lg:col-span-6">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 px-2">Select Inventory Item</label>
                                <select v-model="item.product_id" @change="updateItemPrice(index)" required class="w-full bg-black/50 border border-white/10 rounded-2xl py-4 px-6 text-white focus:outline-none font-bold">
                                    <option value="" disabled>Select product...</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id" :disabled="(p.stock_qty ?? 0) <= 0">
                                        {{ p.name }} (Stock: {{ p.stock_qty ?? 0 }})
                                    </option>
                                </select>
                            </div>
                            <div class="col-span-5 lg:col-span-2">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 px-2 text-center text-blue-500">Qty</label>
                                <input v-model.number="item.qty" type="number" min="1" required class="w-full bg-black/50 border border-white/10 rounded-2xl py-4 px-6 text-white text-center focus:outline-none font-mono font-black text-lg" />
                            </div>
                            <div class="col-span-5 lg:col-span-3" v-if="!auth.isWarehouse && !auth.isDriver">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 px-2 text-right">Subtotal</label>
                                <div class="w-full py-4 text-right font-mono text-white font-black text-xl italic tracking-tighter pr-2">
                                    {{ formatCurrency(item.price * item.qty) }}
                                </div>
                            </div>
                            <!-- Jika warehouse/driver, spacer saja -->
                            <div class="col-span-5 lg:col-span-3" v-else></div>
                            <div class="col-span-2 lg:col-span-1 flex justify-center pb-2">
                                <button type="button" @click="removeItem(index)" class="p-3 bg-red-500/10 text-red-500 hover:bg-red-500 rounded-xl hover:text-white transition-all shadow-lg active:scale-95">
                                    <Trash2 class="w-6 h-6" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-4 ml-1">Internal Notes</label>
                    <textarea v-model="form.notes" rows="3" placeholder="Special delivery instructions..." class="w-full bg-black/40 border border-white/10 rounded-[32px] py-6 px-8 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 resize-none font-medium"></textarea>
                </div>
            </div>

            <div class="p-12 bg-black/40 border-t border-white/10 flex flex-col lg:flex-row items-center justify-between gap-10 shrink-0 backdrop-blur-xl">
                <div class="flex items-center gap-8">
                    <div class="px-8 py-5 bg-black/60 rounded-[32px] border border-white/5 shadow-2xl">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2">Items Count</p>
                        <p class="text-2xl font-black text-white italic tracking-tighter">{{ form.items.length }} Units</p>
                    </div>
                    <div class="px-10 py-5 bg-blue-600/10 rounded-[32px] border border-blue-500/20 shadow-2xl shadow-blue-500/10">
                        <p class="text-[10px] font-black text-blue-500 uppercase tracking-[0.2em] mb-2">Grand Estimate</p>
                        <p class="text-3xl font-black text-blue-400 font-mono italic tracking-tighter leading-none">{{ formatCurrency(orderTotal) }}</p>
                    </div>
                </div>
                <div class="flex gap-5 w-full lg:w-auto">
                    <button @click="showModal = false" :disabled="isSubmitting" class="flex-1 lg:px-10 py-5 bg-white/5 hover:bg-white/10 text-white font-black rounded-2xl transition-all disabled:opacity-30 uppercase tracking-widest text-sm">{{ t('common.cancel') }}</button>
                    <button @click="handleSubmit" :disabled="isSubmitting" class="flex-1 lg:px-14 py-5 bg-blue-600 hover:bg-blue-500 text-white font-black rounded-2xl shadow-[0_20px_50px_rgba(37,99,235,0.4)] active:scale-95 transition-all flex items-center justify-center gap-4 disabled:bg-slate-800 uppercase tracking-widest text-sm outline-none">
                        <template v-if="isSubmitting">
                            <Loader2 class="w-6 h-6 animate-spin" /> {{ t('common.loading') }}
                        </template>
                        <template v-else>
                            {{ t('orders.place_order') }} <CheckCircle2 class="w-6 h-6" />
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<style scoped>
.order-card-enter-active, .order-card-leave-active {
    transition: all 0.5s ease;
}
.order-card-enter-from { opacity: 0; transform: translateY(30px); }

.custom-scrollbar::-webkit-scrollbar { width: 8px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.05); border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(59, 130, 246, 0.2); }

@media print {
    /* Hide everything first */
    body * { 
        display: none !important; 
    }
    
    /* Show ONLY the order detail and its content */
    .fixed.inset-0.z-\[110\], 
    .fixed.inset-0.z-\[110\] *,
    .w-full.max-w-2xl.h-full.bg-slate-950 {
        display: block !important;
        position: relative !important;
        width: 100% !important;
        max-width: 100% !important;
        background: white !important;
        color: black !important;
    }
    
    /* Hide buttons and footer in print */
    button, .bg-black\/40.border-t {
        display: none !important;
    }
}
</style>
