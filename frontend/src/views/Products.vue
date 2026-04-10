<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/api';
import { useI18n } from 'vue-i18n';
import { Package, Plus, Search, Trash2, Edit3, Loader2, X, Upload, Download } from 'lucide-vue-next';
import { formatCurrency } from '@/utils/format';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const { t } = useI18n();
const products = ref<any[]>([]);
const loading = ref(false);
const showModal = ref(false);
const showImportModal = ref(false);
const selectedFile = ref<File | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const importLoading = ref(false);
const editingProduct = ref<any>(null);
const search = ref('');

const form = ref({
    name: '',
    category: '',
    unit: '',
    cost_price: 0,
    price: 0,
    stock: 0
});

const fetchProducts = async () => {
    loading.value = true;
    try {
        const response = await api.get('/products');
        products.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const handleSubmit = async () => {
    try {
        if (editingProduct.value) {
            await api.put(`/products/${editingProduct.value.id}`, form.value);
        } else {
            await api.post('/products', form.value);
        }
        showModal.value = false;
        resetForm();
        fetchProducts();
    } catch (error) {
        console.error(error);
    }
};

const editProduct = (product: any) => {
    editingProduct.value = product;
    form.value = {
        name: product.name,
        category: product.category || '',
        unit: product.unit,
        cost_price: product.cost_price,
        price: product.price,
        stock: product.stock_qty ?? 0,   // read from flattened field
    };
    showModal.value = true;
};

const deleteProduct = async (id: number) => {
    if (confirm(t('common.delete') + '?')) {
        await api.delete(`/products/${id}`);
        fetchProducts();
    }
};

const resetForm = () => {
    editingProduct.value = null;
    form.value = { name: '', category: '', unit: '', cost_price: 0, price: 0, stock: 0 };
};

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        selectedFile.value = target.files[0];
    }
};

const downloadTemplate = async () => {
    try {
        const response = await api.get('/products/template-import', { responseType: 'blob' });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'template_import_produk.xlsx');
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (error) {
        console.error("Gagal mendownload template", error);
    }
};

const handleImport = async () => {
    if (!selectedFile.value) return;
    
    importLoading.value = true;
    const formData = new FormData();
    formData.append('file', selectedFile.value);
    
    try {
        await api.post('/products/import', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        showImportModal.value = false;
        selectedFile.value = null;
        if (fileInput.value) fileInput.value.value = '';
        fetchProducts();
        alert("Import berhasil!");
    } catch (error: any) {
        console.error(error);
        let errorMsg = error.response?.data?.message || 'Terjadi kesalahan saat import';
        
        // Cek jika error dari validasi row
        if (error.response?.status === 422 && error.response?.data?.errors?.file) {
            errorMsg = error.response.data.errors.file.join('\n');
        }
        
        alert(errorMsg);
    } finally {
        importLoading.value = false;
    }
};

onMounted(fetchProducts);
</script>

<template>
<div class="animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div>
            <h1 class="text-4xl font-black tracking-tight mb-2 text-white">{{ t('inventory.title') }}</h1>
            <p class="text-slate-500">{{ t('inventory.subtitle') }}</p>
        </div>
        <div class="flex gap-4" v-if="auth.isAdmin || auth.isOwner">
            <button 
                @click="showImportModal = true"
                class="bg-emerald-600 hover:bg-emerald-500 text-white px-6 py-4 rounded-2xl font-bold flex items-center gap-2 shadow-lg shadow-emerald-500/20 active:scale-95 transition-all text-lg"
            >
                <Upload class="w-6 h-6" />
                {{ t('inventory.import_excel') }}
            </button>
            <button 
                @click="showModal = true"
                class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-4 rounded-2xl font-bold flex items-center gap-2 shadow-lg shadow-blue-500/20 active:scale-95 transition-all text-lg"
            >
                <Plus class="w-6 h-6" />
                {{ t('inventory.add_product') }}
            </button>
        </div>
    </div>

    <!-- Table Card -->
    <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-[40px] overflow-hidden shadow-2xl">
        <!-- Search & Filter -->
        <div class="p-8 border-b border-white/5 flex items-center gap-4">
            <div class="relative flex-1 max-w-md">
                <Search class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500" />
                <input 
                    v-model="search"
                    type="text" 
                    :placeholder="t('inventory.search_placeholder')"
                    class="w-full bg-black/40 border border-white/10 rounded-2xl py-4 pl-14 pr-6 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition-all font-bold tracking-tight"
                />
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-slate-500 uppercase text-[10px] font-black tracking-[0.2em] border-b border-white/5 bg-white/[0.02]">
                        <th class="px-10 py-6">{{ t('inventory.product_name') }}</th>
                        <th class="px-10 py-6 text-center">{{ t('inventory.unit') }}</th>
                        <th class="px-10 py-6 text-right" v-if="!auth.isWarehouse && !auth.isDriver">{{ t('inventory.cost_price') }}</th>
                        <th class="px-10 py-6 text-right" v-if="!auth.isWarehouse && !auth.isDriver">{{ t('inventory.price') }}</th>
                        <th class="px-10 py-6 text-center">{{ t('inventory.stock') }}</th>
                        <th class="px-10 py-6"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <tr v-if="loading" class="text-center">
                        <td colspan="6" class="py-20">
                            <Loader2 class="w-10 h-10 animate-spin mx-auto text-blue-500" />
                            <p class="text-slate-500 mt-4 font-bold text-sm tracking-widest uppercase">{{ t('common.loading') }}</p>
                        </td>
                    </tr>
                    <tr v-else-if="products.length === 0" class="text-center">
                        <td colspan="6" class="py-24 text-slate-500 flex flex-col items-center gap-4">
                            <Package class="w-16 h-16 text-slate-800" />
                            <p class="text-xl">{{ t('common.no_data') }}</p>
                        </td>
                    </tr>
                    <tr v-for="product in products" :key="product.id" class="hover:bg-white/[0.05] transition-all group border-b border-white/5">
                        <td class="px-10 py-6">
                            <div class="flex items-center gap-5">
                                <div class="w-12 h-12 bg-slate-900 rounded-xl flex items-center justify-center text-blue-400 group-hover:scale-110 transition-transform border border-white/5">
                                    <Package class="w-6 h-6" />
                                </div>
                                <div>
                                    <span class="text-lg font-bold text-white block">{{ product.name }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">SKU-{{ product.id.toString().padStart(4, '0') }}</span>
                                        <span v-if="product.category" class="text-[10px] text-blue-500 font-bold uppercase tracking-widest bg-blue-500/10 px-2 py-0.5 rounded-md border border-blue-500/20">{{ product.category }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-10 py-6 text-center">
                            <span class="px-3 py-1 bg-white/5 rounded-lg text-xs font-bold text-slate-400 border border-white/5">{{ product.unit }}</span>
                        </td>
                        <td class="px-10 py-6 text-right font-mono text-slate-400" v-if="!auth.isWarehouse && !auth.isDriver">{{ formatCurrency(product.cost_price) }}</td>
                        <td class="px-10 py-6 text-right font-mono text-blue-400 font-black text-xl italic tracking-tighter" v-if="!auth.isWarehouse && !auth.isDriver">{{ formatCurrency(product.price) }}</td>
                        <td class="px-10 py-6 text-center">
                            <span :class="(product.stock_qty ?? 0) < 10 ? 'text-red-400 bg-red-400/10 border-red-500/20' : 'text-emerald-400 bg-emerald-400/5 border-emerald-500/10'" class="px-4 py-2 rounded-xl text-sm font-black border font-mono">
                                {{ product.stock_qty ?? 0 }}
                            </span>
                        </td>
                        <td class="px-10 py-6">
                            <div class="flex items-center gap-3 justify-end" v-if="auth.isAdmin || auth.isOwner">
                                <button @click="editProduct(product)" class="w-10 h-10 flex items-center justify-center bg-white/5 hover:bg-blue-600 rounded-xl text-slate-400 hover:text-white transition-all shadow-lg active:scale-95">
                                    <Edit3 class="w-5 h-5" />
                                </button>
                                <button @click="deleteProduct(product.id)" class="w-10 h-10 flex items-center justify-center bg-white/5 hover:bg-red-600 rounded-xl text-slate-400 hover:text-white transition-all shadow-lg active:scale-95">
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
        <div class="bg-slate-900 border border-white/10 w-full max-w-lg rounded-[40px] p-10 relative z-10 shadow-[0_32px_80px_rgba(0,0,0,0.5)] animate-in zoom-in-95 fade-in duration-300">
            <div class="flex items-center justify-between mb-10">
                <h2 class="text-3xl font-black text-white tracking-tighter">{{ editingProduct ? t('common.edit') : t('inventory.add_product') }}</h2>
                <button @click="showModal = false" class="p-3 bg-white/5 hover:bg-white/10 rounded-full transition-colors text-slate-400 hover:text-white"><X class="w-6 h-6" /></button>
            </div>

            <form @submit.prevent="handleSubmit" class="space-y-8">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 ml-1">{{ t('inventory.product_name') }}</label>
                    <input v-model="form.name" type="text" required class="w-full bg-black/40 border border-white/10 rounded-2xl py-4 px-6 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 text-lg font-bold" />
                </div>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 ml-1">{{ $t('inventory.category') }}</label>
                        <input v-model="form.category" type="text" placeholder="e.g. Beras, Minyak" class="w-full bg-black/40 border border-white/10 rounded-2xl py-4 px-6 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 font-bold" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 ml-1">{{ t('inventory.unit') }}</label>
                        <input v-model="form.unit" type="text" required placeholder="Kg, Box, etc" class="w-full bg-black/40 border border-white/10 rounded-2xl py-4 px-6 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 font-bold" />
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 ml-1">{{ t('inventory.stock') }}</label>
                    <input v-model="form.stock" type="number" required class="w-full bg-black/40 border border-white/10 rounded-2xl py-4 px-6 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 font-mono font-bold" />
                </div>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 ml-1">{{ t('inventory.cost_price') }}</label>
                        <input v-model="form.cost_price" type="number" required class="w-full bg-black/40 border border-white/10 rounded-2xl py-4 px-6 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 font-mono font-bold" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 ml-1">{{ t('inventory.price') }}</label>
                        <input v-model="form.price" type="number" required class="w-full bg-black/40 border border-white/10 rounded-2xl py-4 px-6 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 font-mono font-bold text-blue-400" />
                    </div>
                </div>

                <div class="pt-6 flex gap-4">
                    <button type="button" @click="showModal = false" class="flex-1 bg-white/5 hover:bg-white/10 text-white font-bold py-5 rounded-2xl transition-all">{{ t('common.cancel') }}</button>
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-black py-5 rounded-2xl shadow-xl shadow-blue-500/20 active:scale-95 transition-all outline-none">
                        {{ editingProduct ? t('common.save') : t('common.new') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import Modal -->
    <div v-if="showImportModal" class="fixed inset-0 z-[120] flex items-center justify-center p-6 sm:p-0 backdrop-blur-md bg-black/40 transition-all">
        <div class="absolute inset-0" @click="showImportModal = false"></div>
        <div class="bg-slate-900 border border-white/10 w-full max-w-lg rounded-[40px] p-10 relative z-10 shadow-[0_32px_80px_rgba(0,0,0,0.5)] animate-in zoom-in-95 fade-in duration-300">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-3xl font-black text-white tracking-tighter">{{ t('inventory.import_excel') }}</h2>
                <button @click="showImportModal = false" class="p-3 bg-white/5 hover:bg-white/10 rounded-full transition-colors text-slate-400 hover:text-white"><X class="w-6 h-6" /></button>
            </div>

            <div class="space-y-8">
                <!-- Step 1: Download Template -->
                <div class="bg-blue-500/10 border border-blue-500/20 rounded-3xl p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center shrink-0">
                            <Download class="w-6 h-6 text-blue-400" />
                        </div>
                        <div>
                            <h3 class="text-white font-bold mb-1">1. {{ t('inventory.download_template') }}</h3>
                            <p class="text-slate-400 text-sm mb-4">Gunakan format file yang sudah disesuaikan agar data terbaca.</p>
                            <button @click="downloadTemplate" class="text-sm font-bold bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl transition-all shadow-lg active:scale-95">Download .xlsx</button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Upload File -->
                <div class="bg-white/5 border border-white/10 rounded-3xl p-6">
                     <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center shrink-0">
                            <Upload class="w-6 h-6 text-emerald-400" />
                        </div>
                        <div class="w-full">
                            <h3 class="text-white font-bold mb-1">2. {{ t('inventory.upload') }}</h3>
                            <p class="text-slate-400 text-sm mb-4">Pilih file Excel yang sudah diisi.</p>
                            <input 
                                type="file" 
                                accept=".xlsx, .xls"
                                ref="fileInput"
                                @change="handleFileChange"
                                class="w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-emerald-500/10 file:text-emerald-400 hover:file:bg-emerald-500/20 file:transition-all file:cursor-pointer"
                            />
                        </div>
                    </div>
                </div>

                <div class="pt-2 flex gap-4">
                    <button type="button" @click="showImportModal = false" class="flex-1 bg-white/5 hover:bg-white/10 text-white font-bold py-5 rounded-2xl transition-all">{{ t('common.cancel') }}</button>
                    <button @click="handleImport" :disabled="!selectedFile || importLoading" :class="(!selectedFile || importLoading) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-emerald-500 shadow-xl shadow-emerald-500/20 active:scale-95'" class="flex-1 bg-emerald-600 text-white font-black py-5 rounded-2xl transition-all outline-none flex justify-center items-center gap-2">
                        <Loader2 v-if="importLoading" class="w-5 h-5 animate-spin" />
                        {{ importLoading ? t('common.loading') : t('inventory.upload') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</template>
