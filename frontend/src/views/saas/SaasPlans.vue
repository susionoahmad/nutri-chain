<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/api/axios';
import { 
    Plus, Edit2, Trash2, X,
    Users, Clock
} from 'lucide-vue-next';

interface Plan {
    id?: number;
    name: string;
    price: number;
    duration_days: number;
    max_users: number | null;
    max_customers: number | null;
    features: string[];
}

const plans = ref<Plan[]>([]);
const loading = ref(true);
const showModal = ref(false);
const editingPlan = ref<Plan | null>(null);

const form = ref<Plan>({
    name: '',
    price: 0,
    duration_days: 30,
    max_users: null,
    max_customers: 3,
    features: []
});

const featureInput = ref('');

const fetchPlans = async () => {
    loading.value = true;
    try {
        const { data } = await api.get('/saas/plans');
        plans.value = data;
    } catch (error) {
        console.error('Failed to fetch plans', error);
    } finally {
        loading.value = false;
    }
};

const openAddModal = () => {
    editingPlan.value = null;
    form.value = {
        name: '',
        price: 0,
        duration_days: 30,
        max_users: null,
        max_customers: null,
        features: []
    };
    featureInput.value = '';
    showModal.value = true;
};

const openEditModal = (plan: Plan) => {
    editingPlan.value = plan;
    form.value = { ...plan };
    featureInput.value = plan.features ? plan.features.join(', ') : '';
    showModal.value = true;
};

const savePlan = async () => {
    // Process features string to array
    form.value.features = featureInput.value
        .split(',')
        .map(f => f.trim())
        .filter(f => f !== '');

    try {
        if (editingPlan.value) {
            await api.put(`/saas/plans/${editingPlan.value.id}`, form.value);
        } else {
            await api.post('/saas/plans', form.value);
        }
        showModal.value = false;
        fetchPlans();
    } catch (error) {
        alert('Gagal menyimpan paket.');
    }
};

const deletePlan = async (id: number) => {
    if (!confirm('Apakah Anda yakin ingin menghapus paket ini?')) return;
    try {
        await api.delete(`/saas/plans/${id}`);
        fetchPlans();
    } catch (error: any) {
        alert(error.response?.data?.message || 'Gagal menghapus paket.');
    }
};

onMounted(fetchPlans);
</script>

<template>
<div class="p-8">
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight text-glow">Master Paket Layanan</h1>
            <p class="text-slate-500 mt-1">Definisikan fitur, harga, dan batasan untuk setiap level langganan.</p>
        </div>
        
        <button 
            @click="openAddModal"
            class="flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg shadow-blue-600/20 active:scale-95"
        >
            <Plus class="w-5 h-5" /> Tambah Paket Baru
        </button>
    </div>

    <!-- Plans Table -->
    <div class="bg-slate-900/50 border border-white/10 rounded-3xl overflow-hidden backdrop-blur-xl shadow-2xl">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-white/5 bg-white/5 uppercase tracking-widest text-[10px] font-black text-slate-500 font-inter">
                    <th class="px-6 py-4">Nama Paket</th>
                    <th class="px-6 py-4">Harga</th>
                    <th class="px-6 py-4">Durasi</th>
                    <th class="px-6 py-4">Limit Pelanggan</th>
                    <th class="px-6 py-4">Limit User</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <tr v-if="loading" v-for="i in 3" :key="i" class="animate-pulse">
                    <td colspan="6" class="px-6 py-8 h-16 bg-white/2 cursor-wait"></td>
                </tr>
                
                <tr v-for="plan in plans" :key="plan.id" class="hover:bg-white/5 transition-colors group">
                    <td class="px-6 py-6 font-bold text-white">{{ plan.name }}</td>
                    <td class="px-6 py-6 text-slate-300">
                        Rp {{ plan.price.toLocaleString('id-ID') }}
                    </td>
                    <td class="px-6 py-6 text-slate-400">
                        <div class="flex items-center gap-2">
                            <Clock class="w-4 h-4 text-blue-500/60" /> {{ plan.duration_days }} Hari
                        </div>
                    </td>
                    <td class="px-6 py-6">
                        <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/5 border border-white/10 rounded-lg text-xs font-mono text-blue-400">
                            <Users class="w-3 h-3" /> {{ plan.max_customers || '∞' }} Cust
                        </div>
                    </td>
                    <td class="px-6 py-6">
                        <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/5 border border-white/10 rounded-lg text-xs font-mono text-slate-400">
                            {{ plan.max_users || '∞' }} User
                        </div>
                    </td>
                    <td class="px-6 py-6 text-right">
                        <div class="flex items-center justify-end gap-2">
                             <button 
                                @click="openEditModal(plan)"
                                class="p-2 bg-white/5 text-slate-400 hover:bg-blue-500/20 hover:text-blue-400 rounded-lg transition-all"
                            >
                                <Edit2 class="w-4 h-4" />
                            </button>
                            <button 
                                @click="deletePlan(plan.id!)"
                                class="p-2 bg-white/5 text-slate-400 hover:bg-red-500/20 hover:text-red-500 rounded-lg transition-all"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </td>
                </tr>
                <tr v-if="plans.length === 0 && !loading">
                    <td colspan="6" class="px-6 py-12 text-center text-slate-600 italic">
                        Belum ada paket yang dibuat.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Modal Form -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showModal = false"></div>
        
        <div class="relative bg-slate-900 border border-white/10 rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
            <div class="p-6 border-b border-white/5 flex items-center justify-between">
                <h3 class="text-xl font-bold text-white">{{ editingPlan ? 'Edit Paket' : 'Tambah Paket Baru' }}</h3>
                <button @click="showModal = false" class="text-slate-500 hover:text-white transition-colors">
                    <X class="w-6 h-6" />
                </button>
            </div>
            
            <form @submit.prevent="savePlan" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Nama Paket</label>
                    <input v-model="form.name" type="text" required class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Contoh: Paket Gold">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Harga (IDR)</label>
                        <input v-model.number="form.price" type="number" required class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Durasi (Hari)</label>
                        <input v-model.number="form.duration_days" type="number" required class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Batas Pelanggan</label>
                        <input v-model.number="form.max_customers" type="number" class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Kosongkan untuk unlimited">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Batas User</label>
                        <input v-model.number="form.max_users" type="number" class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Kosongkan untuk unlimited">
                    </div>
                </div>

                <div v-show="false">
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Fitur (Pisahkan dengan koma)</label>
                    <textarea 
                        v-model="featureInput" 
                        rows="3" 
                        class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        placeholder="Contoh: reports_profit, inventory_import, bulk_orders"
                    ></textarea>
                </div>

                <div class="pt-4 flex gap-3">
                    <button 
                        type="button" 
                        @click="showModal = false"
                        class="flex-1 px-6 py-3 border border-white/10 text-white rounded-xl font-bold hover:bg-white/5 transition-all"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        class="flex-2 bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-blue-600/20"
                    >
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</template>

<style scoped>
.text-glow {
    text-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
}

.flex-2 {
    flex: 2;
}

/* Animations matching standard patterns in project */
.animate-in {
    animation: animate-in 0.2s ease-out;
}

@keyframes animate-in {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>
