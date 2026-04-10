<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '@/api';
import { useI18n } from 'vue-i18n';
import { Users, Plus, Search, Trash2, Edit3, Loader2, X, AlertCircle, CheckCircle2, Link } from 'lucide-vue-next';

const { t } = useI18n();
const users = ref<any[]>([]);
const customers = ref<any[]>([]);
const loading = ref(false);
const showModal = ref(false);
const editingUser = ref<any>(null);
const search = ref('');
const errorMessage = ref('');

const form = ref({
    name: '',
    email: '',
    password: '',
    role: 'admin',
    customer_id: null as number | null,
});

const roles = ['owner', 'admin', 'warehouse', 'driver', 'customer'];

const filteredUsers = computed(() => {
    if (!search.value) return users.value;
    const q = search.value.toLowerCase();
    return users.value.filter(u =>
        u.name.toLowerCase().includes(q) ||
        u.email.toLowerCase().includes(q) ||
        u.role.toLowerCase().includes(q)
    );
});

const fetchUsers = async () => {
    loading.value = true;
    try {
        const [usersRes, customersRes] = await Promise.all([
            api.get('/users'),
            api.get('/customers'),
        ]);
        users.value = usersRes.data;
        customers.value = customersRes.data;
    } catch (error) {
        console.error('Error fetching data:', error);
    } finally {
        loading.value = false;
    }
};

const openModal = (user: any = null) => {
    errorMessage.value = '';
    editingUser.value = user;
    if (user) {
        form.value = {
            name: user.name,
            email: user.email,
            password: '',
            role: user.role,
            customer_id: user.customer_id ?? null,
        };
    } else {
        form.value = { name: '', email: '', password: '', role: 'admin', customer_id: null };
    }
    showModal.value = true;
};

const saveUser = async () => {
    try {
        const payload: any = { ...form.value };
        if (!payload.password) delete payload.password;
        // Only send customer_id if role is customer
        if (payload.role !== 'customer') delete payload.customer_id;

        if (editingUser.value) {
            const response = await api.put(`/users/${editingUser.value.id}`, payload);
            const index = users.value.findIndex(u => u.id === editingUser.value.id);
            if (index !== -1) users.value[index] = response.data;
        } else {
            const response = await api.post('/users', payload);
            users.value.unshift(response.data);
        }
        showModal.value = false;
    } catch (error: any) {
        errorMessage.value = error.response?.data?.message || t('users.save_error');
        console.error('Error saving user:', error);
    }
};

const deleteUser = async (id: number) => {
    if (!confirm(t('users.delete_confirm'))) return;
    try {
        await api.delete(`/users/${id}`);
        users.value = users.value.filter(u => u.id !== id);
    } catch (error) {
        console.error('Error deleting user:', error);
    }
};

const getRoleColor = (role: string) => {
    const map: Record<string, string> = {
        owner:     'bg-purple-500/10 text-purple-400 border-purple-500/20',
        admin:     'bg-blue-500/10 text-blue-400 border-blue-500/20',
        warehouse: 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        driver:    'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        customer:  'bg-pink-500/10 text-pink-400 border-pink-500/20',
    };
    return map[role] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20';
};

onMounted(fetchUsers);
</script>

<template>
<div class="animate-in fade-in slide-in-from-bottom-4 duration-700">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div>
            <h1 class="text-4xl font-black tracking-tight mb-2 text-white">{{ t('users.title') }}</h1>
            <p class="text-slate-500">{{ t('users.subtitle') }}</p>
        </div>
        <button 
            @click="openModal()"
            class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-4 rounded-2xl font-bold flex items-center gap-2 shadow-lg shadow-blue-500/20 active:scale-95 transition-all text-lg"
        >
            <Plus class="w-6 h-6" />
            {{ t('users.add') }}
        </button>
    </div>

    <!-- Table Card -->
    <div class="bg-white/[0.02] border border-white/5 rounded-[32px] shadow-2xl overflow-hidden backdrop-blur-xl">
        <div class="p-8 border-b border-white/5 flex flex-col md:flex-row items-center justify-between gap-6">
            <h2 class="text-xl font-bold flex items-center gap-3 text-white">
                <Users class="w-6 h-6 text-blue-500" />
                {{ t('users.title') }}
            </h2>
            <div class="relative w-full md:w-96 group">
                <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                    <Search class="w-5 h-5 text-slate-500 group-focus-within:text-blue-500 transition-colors" />
                </div>
                <input 
                    v-model="search"
                    type="text" 
                    :placeholder="t('users.search')" 
                    class="w-full bg-black/50 border border-white/5 text-white text-sm rounded-2xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 block pl-12 p-4 transition-all"
                >
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-slate-500 uppercase text-[10px] font-black tracking-[0.2em] border-b border-white/5 bg-white/[0.02]">
                        <th class="px-10 py-6">{{ t('users.name') }}</th>
                        <th class="px-10 py-6 text-center">{{ t('users.role') }}</th>
                        <th class="px-10 py-6">{{ t('users.merchant_email') }}</th>
                        <th class="px-10 py-6"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <tr v-if="loading" class="text-center">
                        <td colspan="4" class="py-20">
                            <Loader2 class="w-10 h-10 animate-spin mx-auto text-blue-500" />
                            <p class="text-slate-500 mt-4 font-bold text-sm tracking-widest uppercase">{{ t('common.loading') }}</p>
                        </td>
                    </tr>
                    <tr v-else-if="filteredUsers.length === 0" class="text-center">
                        <td colspan="4" class="py-24 text-slate-500">
                            <p class="text-xl">{{ t('common.no_data') }}</p>
                        </td>
                    </tr>
                    <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-white/[0.05] transition-all group border-b border-white/5">
                        <td class="px-10 py-6">
                            <div class="flex items-center gap-5">
                                <div class="w-12 h-12 bg-slate-900 rounded-xl flex items-center justify-center text-blue-400 group-hover:scale-110 transition-transform border border-white/5">
                                    <Users class="w-6 h-6" />
                                </div>
                                <div>
                                    <span class="text-lg font-bold text-white block">{{ user.name }}</span>
                                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">ID-{{ user.id.toString().padStart(4, '0') }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-10 py-6 text-center">
                            <span :class="getRoleColor(user.role)" class="px-3 py-1 rounded-lg text-xs font-bold border uppercase">{{ user.role }}</span>
                        </td>
                        <td class="px-10 py-6">
                            <p class="text-slate-300 font-medium">{{ user.email }}</p>
                            <!-- Show linked Merchant / Toko if customer -->
                            <p v-if="user.customer" class="text-xs text-pink-400 font-bold mt-1 flex items-center gap-1">
                                <Link class="w-3 h-3" /> {{ user.customer.name }}
                            </p>
                        </td>
                        <td class="px-10 py-6">
                            <div class="flex items-center gap-3 justify-end">
                                <button @click="openModal(user)" class="w-10 h-10 flex items-center justify-center bg-white/5 hover:bg-blue-600 rounded-xl text-slate-400 hover:text-white transition-all shadow-lg active:scale-95">
                                    <Edit3 class="w-5 h-5" />
                                </button>
                                <button @click="deleteUser(user.id)" class="w-10 h-10 flex items-center justify-center bg-white/5 hover:bg-red-600 rounded-xl text-slate-400 hover:text-white transition-all shadow-lg active:scale-95">
                                    <Trash2 class="w-5 h-5" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="bg-[#0a0a0a] border border-white/10 p-8 sm:p-12 rounded-[40px] w-full max-w-xl relative z-10 shadow-2xl animate-in zoom-in-95 duration-300 max-h-[90vh] overflow-y-auto">
            <button @click="showModal = false" class="absolute top-8 right-8 p-2 hover:bg-white/10 rounded-full text-slate-400 hover:text-white transition-colors">
                <X class="w-6 h-6" />
            </button>

            <h3 class="text-3xl font-black text-white mb-2">{{ editingUser ? t('users.edit_user') : t('users.add_new_user') }}</h3>
            <p class="text-slate-500 mb-8">{{ editingUser ? t('users.update_details') : t('users.create_staff') }}</p>

            <div v-if="errorMessage" class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-2xl flex items-center gap-3 text-red-500 text-sm">
                <AlertCircle class="w-5 h-5 shrink-0" />
                <p>{{ errorMessage }}</p>
            </div>

            <form @submit.prevent="saveUser" class="space-y-6">
                <div>
                    <label class="block mb-2 text-xs font-bold text-slate-400 uppercase tracking-widest">{{ t('users.name') }}</label>
                    <input v-model="form.name" type="text" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-lg" placeholder="John Doe">
                </div>
                <div>
                    <label class="block mb-2 text-xs font-bold text-slate-400 uppercase tracking-widest">Email</label>
                    <input v-model="form.email" type="email" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-lg" :placeholder="t('users.name') + '@example.com'">
                </div>
                <div>
                    <label class="block mb-2 text-xs font-bold text-slate-400 uppercase tracking-widest">{{ t('users.role') }}</label>
                    <select v-model="form.role" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-lg appearance-none">
                        <option v-for="role in roles" :key="role" :value="role" class="bg-slate-900 text-white">{{ t('users.roles.' + role) }}</option>
                    </select>
                </div>

                <!-- Merchant/Customer dropdown — only shown when role is 'customer' -->
                <transition name="fade">
                    <div v-if="form.role === 'customer'" class="p-6 bg-pink-500/5 border border-pink-500/20 rounded-2xl space-y-3">
                        <label class="block mb-2 text-xs font-bold text-pink-400 uppercase tracking-widest flex items-center gap-2">
                            <Link class="w-4 h-4" /> {{ t('users.link_merchant') }}
                        </label>
                        <select 
                            v-model="form.customer_id" 
                            class="w-full bg-black/50 border border-white/10 rounded-xl px-5 py-4 text-white focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition-all"
                        >
                            <option :value="null" class="bg-slate-900">— {{ t('users.select_merchant') }} —</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id" class="bg-slate-900 text-white">
                                {{ c.name }} ({{ c.phone ?? 'no phone' }})
                            </option>
                        </select>
                        <p class="text-[11px] text-slate-500">{{ t('users.link_help') }}</p>
                    </div>
                </transition>

                <div>
                    <label class="block mb-2 text-xs font-bold text-slate-400 uppercase tracking-widest">{{ editingUser ? t('users.password_help') : 'Password' }}</label>
                    <input v-model="form.password" type="password" :required="!editingUser" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-lg" placeholder="••••••••">
                </div>

                <div class="pt-6">
                    <button type="submit" class="w-full bg-white hover:bg-slate-200 text-black font-black text-lg py-5 rounded-2xl shadow-lg shadow-white/10 transition-all active:scale-95 flex items-center justify-center gap-2">
                        <CheckCircle2 v-if="editingUser" class="w-6 h-6" />
                        <Plus v-else class="w-6 h-6" />
                        {{ editingUser ? t('common.save') : t('users.add') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: all 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
