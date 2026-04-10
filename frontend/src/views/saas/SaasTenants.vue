<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '@/api/axios';
import { 
    Search, ShieldCheck, ShieldX, 
    MoreVertical, Calendar 
} from 'lucide-vue-next';

const tenants = ref<any[]>([]);
const loading = ref(true);
const searchQuery = ref('');

const fetchTenants = async () => {
    loading.value = true;
    try {
        const { data } = await api.get('/saas/tenants');
        tenants.value = data;
    } catch (error) {
        console.error('Failed to fetch tenants', error);
    } finally {
        loading.value = false;
    }
};

const toggleStatus = async (tenant: any) => {
    const newStatus = !tenant.is_active;
    if (!confirm(`Apakah Anda yakin ingin ${newStatus ? 'MENGAKTIFKAN' : 'MENONAKTIFKAN'} tenant ${tenant.name}?`)) return;

    try {
        await api.patch(`/saas/tenants/${tenant.id}`, { is_active: newStatus });
        tenant.is_active = newStatus;
    } catch (error) {
        alert('Gagal mengubah status tenant.');
    }
};

const formatDate = (date: string) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const isExpired = (date: string) => {
    if (!date) return true;
    return new Date(date) < new Date();
};

const filteredTenants = computed(() => {
    if (!searchQuery.value) return tenants.value;
    const query = searchQuery.value.toLowerCase();
    return tenants.value.filter(tenant => 
        tenant.name.toLowerCase().includes(query) || 
        tenant.code.toLowerCase().includes(query)
    );
});

onMounted(fetchTenants);
</script>

<template>
<div class="p-8">
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Daftar Tenant</h1>
            <p class="text-slate-500 mt-1">Kelola akses dan pantau keaktifan seluruh penyewa Nutri-Chain.</p>
        </div>
        
        <div class="relative group max-w-sm w-full">
            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 group-focus-within:text-blue-500 transition-colors" />
            <input 
                v-model="searchQuery"
                type="text" 
                placeholder="Cari nama toko atau kode..."
                class="w-full bg-slate-900/50 border border-white/10 rounded-xl py-3 pl-12 pr-4 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 transition-all"
            />
        </div>
    </div>

    <!-- Table -->
    <div class="bg-slate-900/50 border border-white/10 rounded-3xl overflow-hidden backdrop-blur-xl">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-white/5 bg-white/5 uppercase tracking-widest text-[10px] font-black text-slate-500">
                    <th class="px-6 py-4">Toko / Supplier</th>
                    <th class="px-6 py-4">SaaS Status</th>
                    <th class="px-6 py-4">Masa Aktif</th>
                    <th class="px-6 py-4">User</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <tr v-if="loading" v-for="i in 5" :key="i" class="animate-pulse">
                    <td colspan="5" class="px-6 py-8 h-16 bg-white/5"></td>
                </tr>
                
                <tr v-for="tenant in filteredTenants" :key="tenant.id" class="hover:bg-white/5 transition-colors group">
                    <td class="px-6 py-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-600/20 rounded-xl flex items-center justify-center font-bold text-blue-500">
                                {{ tenant.name.charAt(0) }}
                            </div>
                            <div>
                                <h4 class="text-white font-bold">{{ tenant.name }}</h4>
                                <p class="text-slate-500 text-xs">Code: <span class="text-blue-500 font-mono">{{ tenant.code }}</span></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-6">
                        <div v-if="tenant.is_active" class="inline-flex items-center gap-2 px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-xs font-bold">
                            <ShieldCheck class="w-4 h-4" /> Aktif
                        </div>
                        <div v-else class="inline-flex items-center gap-2 px-3 py-1 bg-red-500/20 text-red-400 rounded-full text-xs font-bold">
                            <ShieldX class="w-4 h-4" /> Nonaktif
                        </div>
                    </td>
                    <td class="px-6 py-6">
                        <div class="flex items-center gap-2 text-sm" :class="isExpired(tenant.valid_until) ? 'text-red-400' : 'text-slate-300'">
                            <Calendar class="w-4 h-4" />
                            {{ formatDate(tenant.valid_until) }}
                            <span v-if="isExpired(tenant.valid_until)" class="text-[10px] uppercase font-bold text-red-500/60 ml-1">(Expired)</span>
                        </div>
                    </td>
                    <td class="px-6 py-6 text-slate-400 text-sm">
                        {{ tenant.users_count }} Akun
                    </td>
                    <td class="px-6 py-6 text-right">
                        <div class="flex items-center justify-end gap-2">
                             <button 
                                @click="toggleStatus(tenant)"
                                :title="tenant.is_active ? 'Matikan Akses' : 'Aktifkan Akses'"
                                class="p-2 transition-all rounded-lg"
                                :class="tenant.is_active ? 'bg-red-500/10 text-red-500 hover:bg-red-500/20' : 'bg-green-500/10 text-green-500 hover:bg-green-500/20'"
                            >
                                <ShieldX v-if="tenant.is_active" class="w-5 h-5" />
                                <ShieldCheck v-else class="w-5 h-5" />
                            </button>
                            <button class="p-2 bg-white/5 hover:bg-white/10 text-slate-400 rounded-lg transition-all">
                                <MoreVertical class="w-5 h-5" />
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</template>
