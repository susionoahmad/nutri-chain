<script setup lang="ts">
import { ref, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import api from '@/api/axios';
import { 
    CheckCircle2, Building2, Users, Package, 
    ArrowRight, ArrowLeft, Loader2, Plus, Trash2,
    ShieldCheck, Truck, Warehouse, UserCheck, Star
} from 'lucide-vue-next';

const auth = useAuthStore();
const router = useRouter();

const step = ref(1);
const loading = ref(false);
const errorMessage = ref('');

// Step 1: Profile
const profileForm = ref({
    address: auth.user?.supplier?.address || '',
    phone: auth.user?.supplier?.phone || '',
});

// Step 2: Team (Max 3 staff users, as trial limit is 4 including owner)
const teamMembers = ref<any[]>([]);
const roles = [
    { value: 'admin', label: 'Administrator', icon: ShieldCheck, color: 'text-blue-400', desc: 'Kelola semua fitur & tim' },
    { value: 'warehouse', label: 'Petugas Gudang', icon: Warehouse, color: 'text-amber-400', desc: 'Atur stok & pengiriman' },
    { value: 'driver', label: 'Driver / Kurir', icon: Truck, color: 'text-emerald-400', desc: 'Antar pesanan ke pelanggan' },
];

const addTeamMember = () => {
    if (teamMembers.value.length < 3) {
        teamMembers.value.push({ name: '', email: '', password: '', role: 'warehouse' });
    }
};

const removeTeamMember = (index: number) => {
    teamMembers.value.splice(index, 1);
};

// Step 3: First Product
const productForm = ref({
    name: '',
    category: '',
    unit: 'Pcs',
    cost_price: 0,
    price: 0,
});

const nextStep = () => {
    if (step.value === 1) {
        if (!profileForm.value.address || !profileForm.value.phone) {
            errorMessage.value = 'Mohon lengkapi profil bisnis Anda.';
            return;
        }
    }
    errorMessage.value = '';
    step.value++;
};

const prevStep = () => {
    errorMessage.value = '';
    step.value--;
};

const completeOnboarding = async () => {
    loading.value = true;
    errorMessage.value = '';
    try {
        const payload = {
            supplier_address: profileForm.value.address,
            supplier_phone: profileForm.value.phone,
            team: teamMembers.value,
            first_product: productForm.value.name ? productForm.value : null
        };

        await api.post('/onboarding/complete', payload);
        await auth.fetchMe(); // Refresh user data to get is_onboarded: true
        router.push('/dashboard');
    } catch (error: any) {
        console.error("Onboarding error:", error);
        errorMessage.value = error.response?.data?.message || 'Gagal menyelesaikan onboarding.';
    } finally {
        loading.value = false;
    }
};
</script>

<template>
<div class="min-h-screen bg-[#050505] text-slate-200 font-sans selection:bg-blue-500/30 overflow-hidden relative flex flex-col items-center justify-center p-6">
    <!-- Animated background patterns -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-[10%] -left-[10%] w-[50%] h-[50%] bg-blue-600/10 blur-[120px] rounded-full animate-pulse decoration-indigo-500/20"></div>
        <div class="absolute -bottom-[10%] -right-[10%] w-[50%] h-[50%] bg-indigo-600/10 blur-[120px] rounded-full animate-pulse delay-1000"></div>
    </div>

    <div class="w-full max-w-4xl relative z-10">
        <!-- Progress Steps -->
        <div class="flex items-center justify-between mb-12 px-6">
            <div v-for="i in 3" :key="i" class="flex items-center gap-4 group">
                <div 
                    :class="[
                        step >= i ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20 scale-110' : 'bg-white/5 text-slate-500',
                        step === i ? 'ring-4 ring-blue-600/20' : ''
                    ]"
                    class="w-10 h-10 rounded-xl flex items-center justify-center font-black transition-all duration-500"
                >
                    <component :is="i === 1 ? Building2 : i === 2 ? Users : Package" class="w-5 h-5" v-if="step <= i" />
                    <CheckCircle2 class="w-5 h-5" v-else />
                </div>
                <div v-if="i < 3" :class="step > i ? 'bg-blue-600' : 'bg-white/5'" class="h-1 w-12 md:w-24 rounded-full transition-all duration-700"></div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="bg-white/[0.02] backdrop-blur-3xl border border-white/5 rounded-[48px] p-8 md:p-16 shadow-2xl overflow-hidden relative">
            <!-- Decorative border accent -->
            <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-blue-500/50 to-transparent"></div>

            <div v-if="errorMessage" class="mb-10 p-5 bg-red-500/10 border border-red-500/20 rounded-2xl flex items-center gap-4 text-red-500 animate-in slide-in-from-top-4">
                <CheckCircle2 class="w-6 h-6 rotate-180" />
                <p class="font-bold tracking-tight italic">{{ errorMessage }}</p>
            </div>

            <!-- STEP 1: BUSINESS PROFILE -->
            <div v-if="step === 1" class="animate-in slide-in-from-right-8 duration-700">
                <div class="mb-12">
                    <span class="text-blue-500 font-black tracking-[0.4em] uppercase text-[10px] mb-4 block animate-bounce">01 — Identity</span>
                    <h2 class="text-5xl font-black text-white tracking-tighter mb-4 italic uppercase">{{ $t('onboarding.step_1_title') }}</h2>
                    <p class="text-slate-500 text-lg">{{ $t('onboarding.step_1_desc') }}</p>
                </div>

                <div class="space-y-8">
                    <div class="group">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-4 ml-1 italic group-focus-within:text-blue-400 transition-colors">{{ $t('settings.address') }}</label>
                        <textarea 
                            v-model="profileForm.address"
                            rows="4"
                            :placeholder="$t('settings.address')"
                            class="w-full bg-black/40 border border-white/5 rounded-3xl p-6 text-white placeholder:text-slate-700 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500/50 transition-all text-lg font-medium resize-none shadow-inner"
                        ></textarea>
                    </div>

                    <div class="group">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-4 ml-1 italic group-focus-within:text-blue-400 transition-colors">{{ $t('settings.hotline') }}</label>
                        <input 
                            v-model="profileForm.phone"
                            type="tel"
                            placeholder="08123456789"
                            class="w-full bg-black/40 border border-white/5 rounded-3xl px-8 py-5 text-white placeholder:text-slate-700 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500/50 transition-all text-xl font-black italic tracking-wider shadow-inner"
                        />
                    </div>
                </div>
            </div>

            <!-- STEP 2: TEAM SETUP -->
            <div v-if="step === 2" class="animate-in slide-in-from-right-8 duration-700">
                <div class="mb-12 flex items-center justify-between gap-6">
                    <div>
                        <span class="text-blue-500 font-black tracking-[0.4em] uppercase text-[10px] mb-4 block italic">02 — The Crew</span>
                        <h2 class="text-5xl font-black text-white tracking-tighter mb-4 italic uppercase">{{ $t('onboarding.step_2_title') }}</h2>
                        <p class="text-slate-500 text-lg">{{ $t('onboarding.step_2_desc') }}</p>
                    </div>
                    <div class="hidden md:flex flex-col items-end gap-2 text-right">
                        <div class="px-6 py-3 bg-blue-600/10 border border-blue-600/20 rounded-2xl flex items-center gap-3">
                            <Star class="w-5 h-5 text-blue-400 fill-blue-400" />
                            <span class="text-blue-200 font-black text-xs uppercase tracking-widest">Trial Limit: 4 Accounts</span>
                        </div>
                        <p class="text-[10px] text-slate-600 font-bold uppercase tracking-widest">{{ teamMembers.length + 1 }} / 4 {{ $t('common.available', 'Available') }}</p>
                    </div>
                </div>

                <div class="space-y-6 max-h-[400px] overflow-y-auto pr-4 custom-scroll">
                    <div v-if="teamMembers.length === 0" class="flex flex-col items-center justify-center py-16 bg-white/[0.02] border border-dashed border-white/10 rounded-[32px] group hover:border-blue-500/30 transition-all">
                        <UserCheck class="w-12 h-12 text-slate-700 mb-4 group-hover:scale-110 group-hover:text-blue-500 duration-500 transition-all" />
                        <p class="text-slate-500 font-bold italic mb-6">{{ $t('common.no_data') }}</p>
                        <button @click="addTeamMember" class="bg-white/5 hover:bg-white text-slate-400 hover:text-black px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all active:scale-95 border border-white/10">
                            {{ $t('users.add_new_user') }}
                        </button>
                    </div>

                    <div v-for="(m, i) in teamMembers" :key="i" class="p-8 bg-white/[0.03] border border-white/5 rounded-[32px] relative group hover:bg-white/[0.05] transition-all animate-in zoom-in-95 duration-500">
                        <button @click="removeTeamMember(i)" class="absolute top-6 right-6 p-2 text-slate-600 hover:text-red-500 hover:bg-red-500/10 rounded-xl transition-all">
                            <Trash2 class="w-5 h-5" />
                        </button>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                            <div class="group/input">
                                <label class="block text-[9px] font-black text-slate-600 uppercase tracking-widest mb-3 italic">{{ $t('users.name') }}</label>
                                <input v-model="m.name" type="text" placeholder="Andi Wijaya" class="w-full bg-black/50 border border-white/5 rounded-2xl px-6 py-4 text-white focus:ring-2 focus:ring-blue-500/50 outline-none transition-all font-bold italic">
                            </div>
                            <div class="group/input">
                                <label class="block text-[9px] font-black text-slate-600 uppercase tracking-widest mb-3 italic">Email Login</label>
                                <input v-model="m.email" type="email" placeholder="andi@email.com" class="w-full bg-black/50 border border-white/5 rounded-2xl px-6 py-4 text-white focus:ring-2 focus:ring-blue-500/50 outline-none transition-all font-bold">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-end">
                            <div class="group/input">
                                <label class="block text-[9px] font-black text-slate-600 uppercase tracking-widest mb-3 italic">Password Awal</label>
                                <input v-model="m.password" type="password" placeholder="••••••••" class="w-full bg-black/50 border border-white/5 rounded-2xl px-6 py-4 text-white focus:ring-2 focus:ring-blue-500/50 outline-none transition-all font-bold tracking-widest">
                            </div>
                            <div class="space-y-3">
                                <label class="block text-[9px] font-black text-slate-600 uppercase tracking-widest mb-1 italic">{{ $t('users.role') }}</label>
                                <div class="flex gap-2">
                                    <button 
                                        v-for="role in roles" 
                                        :key="role.value"
                                        @click="m.role = role.value"
                                        :class="m.role === role.value ? 'bg-blue-600 border-blue-500 text-white' : 'bg-black/50 border-white/5 text-slate-500'"
                                        class="flex-1 py-4 border rounded-2xl text-[10px] font-black uppercase tracking-tighter transition-all flex flex-col items-center gap-1 group/btn"
                                    >
                                        <component :is="role.icon" class="w-4 h-4" />
                                        <span>{{ role.value }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button 
                        v-if="teamMembers.length > 0 && teamMembers.length < 3" 
                        @click="addTeamMember" 
                        class="w-full py-6 border-2 border-dashed border-white/5 rounded-[32px] text-slate-500 hover:text-blue-500 hover:border-blue-500/30 hover:bg-blue-500/5 transition-all flex items-center justify-center gap-3 active:scale-[0.99] font-black uppercase text-xs tracking-widest"
                    >
                        <Plus class="w-6 h-6" />
                        {{ $t('users.add') }} ({{ teamMembers.length + 1 }}/4)
                    </button>
                </div>
            </div>

            <!-- STEP 3: INITIAL PRODUCT -->
            <div v-if="step === 3" class="animate-in slide-in-from-right-8 duration-700">
                <div class="mb-12">
                    <span class="text-blue-500 font-black tracking-[0.4em] uppercase text-[10px] mb-4 block italic">03 — Inventory</span>
                    <h2 class="text-5xl font-black text-white tracking-tighter mb-4 italic uppercase">{{ $t('onboarding.step_3_title') }}</h2>
                    <p class="text-slate-500 text-lg">{{ $t('onboarding.step_3_desc') }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-8">
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-4 ml-1 italic group-focus-within:text-blue-400 transition-colors">{{ $t('inventory.product_name') }}</label>
                            <input v-model="productForm.name" type="text" placeholder="Beras Premium 5kg" class="w-full bg-black/40 border border-white/5 rounded-3xl px-8 py-5 text-white placeholder:text-slate-700 focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500/50 transition-all text-xl font-bold font-mono italic shadow-inner">
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-4 ml-1 italic group-focus-within:text-blue-400 transition-colors">{{ $t('inventory.category') }}</label>
                                <input v-model="productForm.category" type="text" placeholder="Sembako" class="w-full bg-black/40 border border-white/5 rounded-2xl px-6 py-4 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-all font-bold italic">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-4 ml-1 italic group-focus-within:text-blue-400 transition-colors">{{ $t('inventory.unit') }}</label>
                                <input v-model="productForm.unit" type="text" placeholder="Karung" class="w-full bg-black/40 border border-white/5 rounded-2xl px-6 py-4 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-all font-bold italic">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-8 p-10 bg-blue-600/5 border border-blue-600/10 rounded-[40px] shadow-inner">
                        <div>
                            <label class="block text-[10px] font-black text-blue-500 uppercase tracking-[0.3em] mb-4 ml-1 italic">{{ $t('inventory.cost_price') }} (Rp)</label>
                            <input v-model="productForm.cost_price" type="number" class="w-full bg-black/60 border border-white/5 rounded-2xl px-8 py-5 text-2xl font-black text-white outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-emerald-500 uppercase tracking-[0.3em] mb-4 ml-1 italic">{{ $t('inventory.price') }} (Rp)</label>
                            <input v-model="productForm.price" type="number" class="w-full bg-black/60 border border-white/5 rounded-2xl px-8 py-5 text-2xl font-black text-emerald-400 outline-none focus:ring-2 focus:ring-emerald-500/50">
                        </div>
                        <div class="pt-4 border-t border-white/10 flex items-center justify-between">
                            <span class="text-slate-500 font-bold italic text-sm">Estimasi Profit</span>
                            <span class="text-xl font-black text-white">Rp {{ (productForm.price - productForm.cost_price).toLocaleString() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ACTIONS -->
            <div class="mt-16 pt-12 border-t border-white/5 flex items-center justify-between">
                <button 
                    v-if="step > 1"
                    @click="prevStep" 
                    class="group flex items-center gap-3 text-slate-500 hover:text-white transition-all font-black uppercase text-xs tracking-widest disabled:opacity-30"
                    :disabled="loading"
                >
                    <ArrowLeft class="w-5 h-5 group-hover:-translate-x-2 transition-transform" />
                    {{ $t('common.back') }}
                </button>
                <div v-else></div>

                <div class="flex items-center gap-6">
                    <button 
                        @click="step === 3 ? completeOnboarding() : nextStep()" 
                        :disabled="loading"
                        class="bg-blue-600 hover:bg-blue-500 text-white px-12 py-5 rounded-3xl font-black text-lg tracking-tight flex items-center gap-4 transition-all active:scale-95 shadow-xl shadow-blue-600/20 disabled:opacity-50 group hover:-translate-y-1"
                    >
                        <Loader2 v-if="loading" class="w-6 h-6 animate-spin" />
                        <span v-else>{{ step === 3 ? $t('onboarding.complete_btn') : 'Next' }}</span>
                        <ArrowRight v-if="!loading" class="w-6 h-6 group-hover:translate-x-2 transition-transform" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Meta Footer -->
        <p class="mt-12 text-center text-slate-700 text-[10px] font-black uppercase tracking-[0.5em] italic">Nutri-Chain Enterprise Resource Planning — Standardized Supply Chain v1.0</p>
    </div>
</div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap');

.font-sans {
    font-family: 'Outfit', sans-serif;
}

.custom-scroll::-webkit-scrollbar {
    width: 6px;
}
.custom-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scroll::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}
.custom-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(59, 130, 246, 0.2);
}

.animate-in {
    animation: fadeIn 0.8s cubic-bezier(0.4, 0, 0.2, 1) backwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}
</style>
