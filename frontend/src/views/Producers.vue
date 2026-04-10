<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/api';
import { useI18n } from 'vue-i18n';
import { Truck, Plus, MapPin, Phone, Edit, Trash2, X, Loader2 } from 'lucide-vue-next';

const { t } = useI18n();

const producers = ref<any[]>([]);
const loading = ref(false);
const showModal = ref(false);
const isEditing = ref(false);
const currentProducer = ref<any>({ name: '', address: '', phone: '' });
const isSubmitting = ref(false);

const fetchProducers = async () => {
    loading.value = true;
    try {
        const response = await api.get('/producers');
        producers.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const openAddModal = () => {
    isEditing.value = false;
    currentProducer.value = { name: '', address: '', phone: '' };
    showModal.value = true;
};

const openEditModal = (producer: any) => {
    isEditing.value = true;
    currentProducer.value = { ...producer };
    showModal.value = true;
};

const saveProducer = async () => {
    isSubmitting.value = true;
    try {
        if (isEditing.value) {
            await api.put(`/producers/${currentProducer.value.id}`, currentProducer.value);
        } else {
            await api.post('/producers', currentProducer.value);
        }
        showModal.value = false;
        fetchProducers();
    } catch (error) {
        console.error(error);
    } finally {
        isSubmitting.value = false;
    }
};

const deleteProducer = async (id: number) => {
    if (!confirm(t('producers.delete_confirm'))) return;
    try {
        await api.delete(`/producers/${id}`);
        fetchProducers();
    } catch (error) {
        console.error(error);
    }
};

onMounted(fetchProducers);
</script>

<template>
<div class="space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-4xl font-black tracking-tight mb-2 text-white flex items-center gap-3">
                <Truck class="w-10 h-10 text-blue-500" />
                {{ t('producers.title') }}
            </h1>
            <p class="text-slate-500 uppercase tracking-widest text-sm font-bold">{{ t('producers.subtitle') }}</p>
        </div>
        <button @click="openAddModal" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-4 rounded-[24px] font-black uppercase tracking-widest text-xs flex items-center gap-3 shadow-xl shadow-blue-600/20 active:scale-95 transition-all">
            <Plus class="w-5 h-5" />
            {{ t('producers.add') }}
        </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <div v-for="i in 3" :key="i" class="h-48 bg-white/5 border border-white/10 rounded-[32px] animate-pulse"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="producers.length === 0" class="py-24 text-center bg-white/5 rounded-[40px] border border-white/5">
        <Truck class="w-16 h-16 mx-auto text-slate-800 mb-4" />
        <p class="text-xl text-slate-600 font-bold uppercase tracking-widest">{{ t('producers.no_data') }}</p>
    </div>

    <!-- Producer Card Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <div v-for="p in producers" :key="p.id" class="bg-white/5 border border-white/10 rounded-[32px] p-8 relative overflow-hidden group hover:bg-white/[0.08] transition-all">
            <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:scale-110 transition-transform">
                <Truck class="w-24 h-24 text-white" />
            </div>

            <div class="relative z-10">
                <h3 class="text-xl font-black text-white mb-4 tracking-tight uppercase italic">{{ p.name }}</h3>
                
                <div class="space-y-3 mb-8">
                    <div class="flex items-start gap-3 text-slate-400">
                        <MapPin class="w-5 h-5 shrink-0 text-blue-500" />
                        <span class="text-sm font-bold italic">{{ p.address || t('producers.no_address') }}</span>
                    </div>
                    <div class="flex items-center gap-3 text-slate-400">
                        <Phone class="w-5 h-5 shrink-0 text-blue-500" />
                        <span class="text-sm font-mono tracking-tighter">{{ p.phone || 'N/A' }}</span>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button @click="openEditModal(p)" class="flex-1 bg-white/5 hover:bg-white/10 border border-white/5 text-slate-300 py-3 rounded-xl flex items-center justify-center gap-2 font-black text-[10px] uppercase tracking-widest transition-all">
                        <Edit class="w-3.5 h-3.5" /> Edit
                    </button>
                    <button @click="deleteProducer(p.id)" class="px-6 bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white border border-red-500/20 rounded-xl flex items-center justify-center transition-all">
                        <Trash2 class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL FORM -->
    <div v-if="showModal" class="fixed inset-0 z-[120] flex items-center justify-center p-6 backdrop-blur-md bg-black/60">
        <div class="absolute inset-0" @click="showModal = false"></div>
        <div class="bg-slate-900 border border-white/10 w-full max-w-lg overflow-hidden rounded-[40px] flex flex-col relative z-10 shadow-[0_32px_120px_rgba(0,0,0,1)] animate-in zoom-in-95 duration-300">
            <div class="p-8 border-b border-white/10 flex justify-between items-center bg-black/20">
                <h3 class="font-black text-2xl text-white tracking-tighter italic">
                    {{ isEditing ? t('producers.edit') : t('producers.register') }}
                </h3>
                <button @click="showModal = false" class="text-slate-500 hover:text-white"><X class="w-6 h-6"/></button>
            </div>
            
            <form @submit.prevent="saveProducer" class="p-8 space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 italic">{{ t('producers.company_name') }}</label>
                    <input v-model="currentProducer.name" type="text" class="w-full bg-black/50 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-blue-500 transition-all font-bold placeholder:text-slate-700" placeholder="Contoh: PT. Sumber Segar">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 italic">{{ t('producers.address') }}</label>
                    <textarea v-model="currentProducer.address" rows="3" class="w-full bg-black/50 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-blue-500 transition-all font-bold placeholder:text-slate-700" placeholder="Alamat gudang atau kantor produsen..."></textarea>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 italic">{{ t('producers.phone') }}</label>
                    <input v-model="currentProducer.phone" type="text" class="w-full bg-black/50 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-blue-500 transition-all font-bold placeholder:text-slate-700" placeholder="+62 812...">
                </div>

                <div class="pt-4">
                    <button type="submit" :disabled="isSubmitting" class="w-full bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white font-black py-5 rounded-[24px] uppercase tracking-widest text-[11px] flex items-center justify-center gap-3 shadow-xl shadow-blue-600/20 active:scale-95 transition-all">
                        <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin"/>
                        {{ isEditing ? t('producers.update') : t('producers.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</template>
