<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/api';
import { useI18n } from 'vue-i18n';
import { 
    History, ArrowUpRight, ArrowDownLeft, 
    Search, Calendar, Package, 
    ArrowRight, Download, RefreshCw,
    ChevronRight
} from 'lucide-vue-next';
import { format } from 'date-fns';

const { t } = useI18n();
const loading = ref(true);
const mutations = ref<any[]>([]);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0
});

const filters = ref({
    type: '',
    product_id: '',
    limit: 15
});

const fetchMutations = async (page = 1) => {
    loading.value = true;
    try {
        const response = await api.get('/stock-mutations', {
            params: {
                page,
                ...filters.value
            }
        });
        mutations.value = response.data.data;
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            total: response.data.total
        };
    } catch (error) {
        console.error('Failed to fetch mutations:', error);
    } finally {
        loading.value = false;
    }
};

const getTypeStyles = (type: string) => {
    switch (type) {
        case 'in': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
        case 'out': return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
        case 'adjustment': return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
        default: return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
    }
};

const getTypeIcon = (type: string) => {
    switch (type) {
        case 'in': return ArrowDownLeft;
        case 'out': return ArrowUpRight;
        case 'adjustment': return RefreshCw;
        default: return History;
    }
};

onMounted(() => fetchMutations());
</script>

<template>
<div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-4xl font-black tracking-tight text-white mb-2 flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-600/20 text-blue-400 rounded-2xl flex items-center justify-center border border-blue-500/20">
                    <History class="w-6 h-6" />
                </div>
                {{ t('mutations.title') }}
            </h1>
            <p class="text-slate-500 max-w-2xl">
                {{ t('mutations.subtitle') }}
            </p>
        </div>
        
        <div class="flex gap-3">
            <button class="bg-white/5 hover:bg-white/10 text-white px-6 py-4 rounded-2xl font-black uppercase tracking-widest text-[10px] border border-white/5 flex items-center gap-2 transition-all">
                <Download class="w-4 h-4" /> {{ t('mutations.export_pdf') }}
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="relative group md:col-span-2">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none group-focus-within:text-blue-400 text-slate-500 transition-colors">
                <Search class="w-5 h-5" />
            </div>
            <input 
                type="text" 
                :placeholder="t('mutations.search_placeholder')"
                class="w-full bg-slate-900/50 border border-white/5 rounded-2xl py-4 pl-12 pr-4 text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all font-medium"
            >
        </div>
        
        <select 
            v-model="filters.type"
            @change="fetchMutations(1)"
            class="bg-slate-900/50 border border-white/5 rounded-2xl py-4 px-4 text-white focus:ring-2 focus:ring-blue-500/50 outline-none transition-all font-medium appearance-none"
        >
            <option value="">{{ t('mutations.filter_all') }}</option>
            <option value="in">{{ t('mutations.filter_in') }}</option>
            <option value="out">{{ t('mutations.filter_out') }}</option>
            <option value="adjustment">{{ t('mutations.filter_adj') }}</option>
        </select>

        <div class="flex items-center gap-4 bg-slate-900/50 border border-white/5 rounded-2xl px-4 text-white">
            <Calendar class="w-5 h-5 text-slate-500" />
            <span class="text-sm font-medium">{{ t('mutations.this_month') }}</span>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="bg-white/[0.02] border border-white/5 rounded-[40px] overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-white/5 bg-white/[0.01]">
                        <th class="p-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">{{ t('mutations.col_time_product') }}</th>
                        <th class="p-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">{{ t('mutations.col_type_qty') }}</th>
                        <th class="p-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">{{ t('mutations.col_flow') }}</th>
                        <th class="p-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">{{ t('mutations.col_note') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <tr v-if="loading" v-for="i in 5" :key="i" class="animate-pulse">
                        <td v-for="j in 4" :key="j" class="p-8">
                            <div class="h-4 bg-white/5 rounded-full w-2/3 mb-2"></div>
                            <div class="h-3 bg-white/5 rounded-full w-1/2"></div>
                        </td>
                    </tr>
                    
                    <tr v-else-if="mutations.length === 0">
                        <td colspan="4" class="p-20 text-center">
                            <div class="w-20 h-20 bg-white/5 rounded-3xl flex items-center justify-center mx-auto mb-6">
                                <History class="w-10 h-10 text-slate-700" />
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">{{ t('mutations.empty_title') }}</h3>
                            <p class="text-slate-500">{{ t('mutations.empty_subtitle') }}</p>
                        </td>
                    </tr>

                    <tr v-for="item in mutations" :key="item.id" class="hover:bg-white/[0.03] transition-colors group">
                        <td class="p-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-white/5 rounded-xl border border-white/5 flex items-center justify-center group-hover:bg-blue-600/20 group-hover:text-blue-400 transition-all">
                                    <Package class="w-6 h-6" />
                                </div>
                                <div>
                                    <p class="text-sm font-black text-white uppercase">{{ item.product?.name }}</p>
                                    <p class="text-[10px] text-slate-500 font-bold tracking-widest mt-0.5">
                                        {{ format(new Date(item.created_at), 'dd MMM yyyy • HH:mm') }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="p-6 text-center">
                            <div :class="getTypeStyles(item.type)" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border">
                                <component :is="getTypeIcon(item.type)" class="w-3 h-3" />
                                {{ item.type }}
                            </div>
                            <p class="text-xl font-black italic mt-2" :class="item.type === 'in' ? 'text-emerald-400' : (item.type === 'out' ? 'text-rose-400' : 'text-amber-400')">
                                {{ item.type === 'in' ? '+' : '-' }}{{ parseInt(item.qty) }} <span class="text-[10px] text-slate-500 not-italic ml-1">{{ item.product?.unit }}</span>
                            </p>
                        </td>
                        <td class="p-6">
                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest">{{ t('mutations.before') }}</p>
                                    <p class="text-sm font-bold text-slate-400 italic">{{ parseInt(item.old_qty) }}</p>
                                </div>
                                <ArrowRight class="w-4 h-4 text-slate-700" />
                                <div>
                                    <p class="text-[9px] font-black text-blue-500 uppercase tracking-widest">{{ t('mutations.after') }}</p>
                                    <p class="text-lg font-black text-white italic underline decoration-blue-500/30 underline-offset-4">{{ parseInt(item.new_qty) }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="p-6">
                            <div class="max-w-[250px]">
                                <div v-if="item.reference_type" class="flex items-center gap-2 mb-1.5">
                                    <span class="px-2 py-0.5 bg-blue-500/10 text-blue-400 border border-blue-500/20 rounded-md text-[9px] font-black uppercase tracking-widest">
                                        {{ item.reference_type }} #{{ item.reference_id }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 leading-relaxed font-medium">{{ item.note }}</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-6 border-t border-white/5 flex items-center justify-between">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                {{ t('mutations.showing', { shown: mutations.length, total: pagination.total }) }}
            </p>
            <div class="flex gap-2">
                <button 
                    @click="fetchMutations(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                    class="p-2 bg-white/5 hover:bg-white/10 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl transition-all border border-white/5 text-white"
                >
                    <ChevronRight class="w-5 h-5 rotate-180" />
                </button>
                <div class="flex items-center px-4 bg-white/5 rounded-xl border border-white/5 font-black text-[10px] text-white">
                    {{ t('mutations.page_of', { current: pagination.current_page, last: pagination.last_page }) }}
                </div>
                <button 
                    @click="fetchMutations(pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page"
                    class="p-2 bg-white/5 hover:bg-white/10 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl transition-all border border-white/5 text-white"
                >
                    <ChevronRight class="w-5 h-5" />
                </button>
            </div>
        </div>
    </div>
</div>
</template>

