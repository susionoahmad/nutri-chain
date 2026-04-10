<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/api';
import { useI18n } from 'vue-i18n';
import { Users, Plus, Search, Trash2, Edit3, Loader2, X, Phone, MapPin } from 'lucide-vue-next';

const { t } = useI18n();
const customers = ref<any[]>([]);
const loading = ref(false);
const showModal = ref(false);
const editingCustomer = ref<any>(null);
const search = ref('');

const form = ref({
    name: '',
    address: '',
    phone: '',
});

const fetchCustomers = async () => {
    loading.value = true;
    try {
        const response = await api.get('/customers');
        customers.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const handleSubmit = async () => {
    try {
        if (editingCustomer.value) {
            await api.put(`/customers/${editingCustomer.value.id}`, form.value);
        } else {
            await api.post('/customers', form.value);
        }
        showModal.value = false;
        resetForm();
        fetchCustomers();
    } catch (error) {
        console.error(error);
    }
};

const editCustomer = (customer: any) => {
    editingCustomer.value = customer;
    form.value = { ...customer };
    showModal.value = true;
};

const deleteCustomer = async (id: number) => {
    if (confirm(t('common.delete') + '?')) {
        await api.delete(`/customers/${id}`);
        fetchCustomers();
    }
};

const resetForm = () => {
    editingCustomer.value = null;
    form.value = { name: '', address: '', phone: '' };
};

onMounted(fetchCustomers);
</script>

<template>
<div class="space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-4xl font-black tracking-tight mb-2 text-white">{{ t('customers.title') }}</h1>
            <p class="text-slate-500">{{ t('customers.subtitle') }}</p>
        </div>
        <button 
            @click="showModal = true"
            class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-4 rounded-2xl font-bold flex items-center justify-center gap-2 shadow-lg shadow-blue-500/20 active:scale-95 transition-all text-lg"
        >
            <Plus class="w-6 h-6" />
            {{ t('customers.add_customer') }}
        </button>
    </div>

    <!-- Table Card -->
    <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-[40px] overflow-hidden shadow-2xl">
        <!-- Search -->
        <div class="p-8 border-b border-white/5 flex items-center gap-4">
            <div class="relative flex-1 max-w-md">
                <Search class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500" />
                <input 
                    v-model="search"
                    type="text" 
                    :placeholder="t('customers.search_placeholder')"
                    class="w-full bg-black/40 border border-white/10 rounded-2xl py-4 pl-14 pr-6 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition-all font-bold tracking-tight"
                />
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-slate-500 uppercase text-[10px] font-black tracking-[0.2em] border-b border-white/5 bg-white/[0.02]">
                        <th class="px-10 py-6">{{ t('customers.name') }}</th>
                        <th class="px-10 py-6">{{ t('customers.phone') }}</th>
                        <th class="px-10 py-6">{{ t('customers.address') }}</th>
                        <th class="px-10 py-6 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <tr v-if="loading" class="text-center">
                        <td colspan="4" class="py-24">
                            <Loader2 class="w-10 h-10 animate-spin mx-auto text-blue-500" />
                            <p class="text-slate-500 mt-4 font-bold text-sm tracking-widest uppercase">{{ t('common.loading') }}</p>
                        </td>
                    </tr>
                    <tr v-else-if="customers.length === 0" class="text-center">
                        <td colspan="4" class="py-24">
                            <div class="flex flex-col items-center gap-4 opacity-40">
                                <Users class="w-16 h-16" />
                                <p class="text-xl font-medium">{{ t('common.no_data') }}</p>
                            </div>
                        </td>
                    </tr>
                    <tr v-for="customer in customers" :key="customer.id" class="hover:bg-white/[0.05] transition-all group border-b border-white/5">
                        <td class="px-10 py-7">
                            <div class="flex items-center gap-5">
                                <div class="w-14 h-14 bg-slate-900 rounded-2xl flex items-center justify-center text-blue-400 group-hover:scale-110 transition-transform border border-white/5">
                                    <Users class="w-7 h-7" />
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white">{{ customer.name }}</p>
                                    <p class="text-[10px] text-blue-500 font-bold uppercase tracking-widest">Client B2B</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-10 py-7">
                            <div class="flex items-center gap-3 text-slate-300">
                                <Phone class="w-4 h-4 text-slate-500" />
                                <span class="font-mono text-sm">{{ customer.phone }}</span>
                            </div>
                        </td>
                        <td class="px-10 py-7">
                            <div class="flex items-start gap-3 text-slate-400 max-w-sm">
                                <MapPin class="w-4 h-4 text-slate-500 mt-1 flex-shrink-0" />
                                <span class="text-sm leading-relaxed truncate-2 font-medium">{{ customer.address }}</span>
                            </div>
                        </td>
                        <td class="px-10 py-7 text-right">
                            <div class="flex items-center justify-end gap-3 opacity-0 group-hover:opacity-100 transition-all -translate-x-2 group-hover:translate-x-0">
                                <button @click="editCustomer(customer)" class="w-11 h-11 flex items-center justify-center bg-white/5 hover:bg-blue-600 rounded-xl text-slate-400 hover:text-white transition-all shadow-lg active:scale-95">
                                    <Edit3 class="w-5 h-5" />
                                </button>
                                <button @click="deleteCustomer(customer.id)" class="w-11 h-11 flex items-center justify-center bg-white/5 hover:bg-red-600 rounded-xl text-slate-400 hover:text-white transition-all shadow-lg active:scale-95">
                                    <Trash2 class="w-5 h-5" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 z-[120] flex items-center justify-center p-6 sm:p-0 backdrop-blur-md bg-black/40 transition-all">
        <div class="absolute inset-0" @click="showModal = false"></div>
        <div class="bg-slate-900 border border-white/10 w-full max-w-xl rounded-[40px] p-10 relative z-10 shadow-[0_32px_80px_rgba(0,0,0,0.5)] animate-in zoom-in-95 fade-in duration-300">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <h2 class="text-3xl font-black text-white tracking-tighter mb-2">{{ editingCustomer ? t('common.edit') : t('customers.add_customer') }}</h2>
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-widest">{{ t('customers.subtitle') }}</p>
                </div>
                <button @click="showModal = false" class="p-3 bg-white/5 hover:bg-white/10 rounded-full transition-colors text-slate-400 hover:text-white"><X class="w-6 h-6" /></button>
            </div>

            <form @submit.prevent="handleSubmit" class="space-y-8">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 ml-1">{{ t('customers.name') }}</label>
                    <input v-model="form.name" type="text" required class="w-full bg-black/40 border border-white/10 rounded-2xl py-5 px-6 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition-all text-lg font-bold" />
                </div>
                
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 ml-1">{{ t('customers.phone') }}</label>
                    <div class="relative">
                        <Phone class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500" />
                        <input v-model="form.phone" type="text" required placeholder="+62 821..." class="w-full bg-black/40 border border-white/10 rounded-2xl py-5 pl-14 pr-6 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition-all text-lg font-mono font-bold" />
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 ml-1">{{ t('customers.address') }}</label>
                    <div class="relative">
                        <MapPin class="absolute left-5 top-6 w-5 h-5 text-slate-500" />
                        <textarea v-model="form.address" required rows="3" class="w-full bg-black/40 border border-white/10 rounded-2xl py-5 pl-14 pr-6 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition-all text-lg resize-none font-medium"></textarea>
                    </div>
                </div>

                <div class="pt-6 flex gap-5">
                    <button type="button" @click="showModal = false" class="flex-1 bg-white/5 hover:bg-white/10 text-white font-bold py-5 rounded-2xl transition-all">{{ t('common.cancel') }}</button>
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-black py-5 rounded-2xl shadow-xl shadow-blue-500/20 active:scale-95 transition-all flex items-center justify-center gap-3">
                        <Plus v-if="!editingCustomer" class="w-6 h-6" />
                        {{ editingCustomer ? t('common.save') : t('common.new') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</template>

<style scoped>
.truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
