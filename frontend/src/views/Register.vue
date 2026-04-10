<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter, useRoute } from 'vue-router';
import { 
    UserPlus, User, Mail, Lock, 
    Home, Phone, ShoppingBag, 
    Loader2, AlertCircle, ArrowLeft,
    CheckCircle2, Building2
} from 'lucide-vue-next';
import api from '@/api/axios';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

// Deteksi Mode: Jika ada ?code= di URL, maka mode Customer
const supplierCode = ref((route.query.code as string) || '');
const isCustomerMode = computed(() => !!supplierCode.value);
const supplierInfo = ref<any>(null);
const checkingCode = ref(false);

const form = ref({
    // Shared
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    // Supplier Only
    company_name: '',
    // Customer Only
    supplier_code: supplierCode.value,
    store_name: '',
    address: '',
    phone: '',
});

const loading = ref(false);
const errorMessage = ref('');
const step = ref(1);

const verifySupplierCode = async () => {
    if (!supplierCode.value) return;
    
    checkingCode.value = true;
    errorMessage.value = '';
    try {
        const { data } = await api.get(`/suppliers/check-code/${supplierCode.value}`);
        if (data.valid) {
            supplierInfo.value = data;
            form.value.supplier_code = supplierCode.value;
        } else {
            errorMessage.value = 'Kode Supplier tidak valid atau sudah tidak aktif.';
        }
    } catch (e: any) {
        errorMessage.value = e.response?.data?.message || 'Gagal memverifikasi kode supplier.';
    } finally {
        checkingCode.value = false;
    }
};

const nextStep = () => {
    if (step.value === 1) {
        if (isCustomerMode.value) {
            if (!form.value.name || !form.value.email || !form.value.password || !form.value.password_confirmation) {
                errorMessage.value = 'Mohon lengkapi data akun Anda.';
                return;
            }
        } else {
            if (!form.value.company_name || !form.value.name || !form.value.email || !form.value.password || !form.value.password_confirmation) {
                errorMessage.value = 'Mohon lengkapi semua data pendaftaran.';
                return;
            }
        }
        
        if (form.value.password !== form.value.password_confirmation) {
            errorMessage.value = 'Konfirmasi kata sandi tidak cocok.';
            return;
        }
    }
    errorMessage.value = '';
    
    // Jika Supplier mode, langsung submit di step 1 atau lanjut? 
    // Untuk simplify, Supplier mode cuma 1 step (info dasar).
    // Customer mode 2 step (1: Akun, 2: Profil Toko).
    if (!isCustomerMode.value) {
        handleRegister();
    } else {
        step.value++;
    }
};

const prevStep = () => {
    errorMessage.value = '';
    step.value--;
};

const handleRegister = async () => {
    loading.value = true;
    errorMessage.value = '';
    try {
        if (isCustomerMode.value) {
            await auth.registerCustomer(form.value);
        } else {
            await auth.registerSupplier(form.value);
        }
        if (!isCustomerMode.value) {
            router.push('/onboarding');
        } else {
            router.push('/dashboard');
        }
    } catch (error: any) {
        console.error("Registration error:", error);
        errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan saat pendaftaran.';
        if (error.response?.data?.errors) {
            const firstError = Object.values(error.response.data.errors)[0] as string[];
            errorMessage.value = firstError[0];
        }
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    if (isCustomerMode.value) {
        verifySupplierCode();
    }
});
</script>

<template>
<div class="min-h-screen flex items-center justify-center bg-slate-950 p-6 relative overflow-hidden font-sans">
    <!-- Decorative Gradients (Matching Login) -->
    <div class="absolute -top-[10%] -left-[10%] w-[50%] h-[50%] bg-blue-600/10 rounded-full blur-[120px]"></div>
    <div class="absolute -bottom-[10%] -right-[10%] w-[50%] h-[50%] bg-indigo-600/10 rounded-full blur-[120px]"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[30%] h-[30%] bg-purple-600/5 rounded-full blur-[120px]"></div>

    <div class="w-full max-w-xl relative z-10 transition-all duration-500">
        <div class="bg-white/5 backdrop-blur-2xl border border-white/10 rounded-[40px] p-8 md:p-12 shadow-2xl shadow-black/50">
            <!-- Header -->
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-tr from-blue-600 to-indigo-600 rounded-3xl mb-6 shadow-2xl shadow-blue-500/30">
                    <UserPlus class="text-white w-10 h-10" />
                </div>
                <h1 class="text-4xl font-black text-white tracking-tighter mb-2 italic uppercase">
                    {{ isCustomerMode ? 'Daftar Pelanggan' : 'Daftar Supplier' }}
                </h1>
                <p class="text-slate-400 font-bold tracking-widest text-xs uppercase">
                    {{ isCustomerMode ? 'Bergabung dengan jaringan supplier kami' : 'Mulai kelola bisnis distribusi Anda' }}
                </p>
                
                <!-- Supplier Info (Customer Mode Only) -->
                <div v-if="isCustomerMode && supplierInfo" class="mt-6 inline-flex items-center gap-2 px-4 py-2 bg-blue-500/10 border border-blue-500/20 rounded-full animate-in zoom-in duration-500">
                    <Building2 class="w-4 h-4 text-blue-400" />
                    <span class="text-blue-200 text-xs font-black uppercase tracking-wider">Terhubung: {{ supplierInfo.supplier_name }}</span>
                </div>
            </div>

            <!-- Progress Bar (Only for Multi-step Customer Mode) -->
            <div v-if="isCustomerMode" class="flex items-center justify-center gap-4 mb-10">
                <div :class="step >= 1 ? 'bg-blue-500' : 'bg-slate-800'" class="h-1.5 w-16 rounded-full transition-all duration-500"></div>
                <div :class="step >= 2 ? 'bg-blue-500' : 'bg-slate-800'" class="h-1.5 w-16 rounded-full transition-all duration-500"></div>
            </div>

            <!-- Error State -->
            <div v-if="errorMessage" class="mb-8 p-4 bg-red-500/10 border border-red-500/20 rounded-2xl flex items-center gap-3 animate-in fade-in slide-in-from-top-4">
                <AlertCircle class="text-red-400 w-5 h-5 flex-shrink-0" />
                <p class="text-red-200 text-sm font-bold italic">{{ errorMessage }}</p>
            </div>

            <!-- Loading Code State -->
            <div v-if="checkingCode" class="flex flex-col items-center justify-center py-20 animate-pulse">
                <Loader2 class="w-10 h-10 text-blue-500 animate-spin mb-4" />
                <p class="text-slate-500 text-xs font-black uppercase tracking-[0.2em]">Memverifikasi Kode...</p>
            </div>

            <form v-else @submit.prevent="handleRegister" class="space-y-8">
                <!-- STEP 1: Basic Info (Both Modes) -->
                <div v-if="step === 1" class="space-y-6 animate-in slide-in-from-right-4 duration-500">
                    
                    <!-- Company Name (Supplier Mode Only) -->
                    <div v-if="!isCustomerMode">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-3 ml-1 italic">Nama Perusahaan / Supplier</label>
                        <div class="relative group">
                            <Building2 class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 group-focus-within:text-blue-500 transition-colors" />
                            <input 
                                v-model="form.company_name"
                                type="text" 
                                placeholder="PT Distribusi Makmur"
                                class="w-full bg-slate-900/40 border border-white/5 rounded-2xl py-4.5 pl-14 pr-5 text-white placeholder:text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 transition-all font-medium"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-3 ml-1 italic">
                            {{ isCustomerMode ? 'Nama Lengkap Penanggung Jawab' : 'Nama Lengkap Pemilik (Owner)' }}
                        </label>
                        <div class="relative group">
                            <User class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 group-focus-within:text-blue-500 transition-colors" />
                            <input 
                                v-model="form.name"
                                type="text" 
                                placeholder="Joni Saputra"
                                class="w-full bg-slate-900/40 border border-white/5 rounded-2xl py-4.5 pl-14 pr-5 text-white placeholder:text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 transition-all font-medium"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-3 ml-1 italic">{{ $t('auth.register.email') }}</label>
                        <div class="relative group">
                            <Mail class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 group-focus-within:text-blue-500 transition-colors" />
                            <input 
                                v-model="form.email"
                                type="email" 
                                placeholder="joni@gmail.com"
                                class="w-full bg-slate-900/40 border border-white/5 rounded-2xl py-4.5 pl-14 pr-5 text-white placeholder:text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 transition-all font-medium"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-3 ml-1 italic">{{ $t('auth.register.password') }}</label>
                            <div class="relative group">
                                <Lock class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 group-focus-within:text-blue-500 transition-colors" />
                                <input 
                                    v-model="form.password"
                                    type="password" 
                                    placeholder="••••••••"
                                    class="w-full bg-slate-900/40 border border-white/5 rounded-2xl py-4.5 pl-14 pr-5 text-white placeholder:text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 transition-all font-medium"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-3 ml-1 italic">{{ $t('auth.register.confirm_password') }}</label>
                            <div class="relative group">
                                <Lock class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 group-focus-within:text-blue-500 transition-colors" />
                                <input 
                                    v-model="form.password_confirmation"
                                    type="password" 
                                    placeholder="••••••••"
                                    class="w-full bg-slate-900/40 border border-white/5 rounded-2xl py-4.5 pl-14 pr-5 text-white placeholder:text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 transition-all font-medium"
                                />
                            </div>
                        </div>
                    </div>

                    <button 
                        type="button"
                        @click="nextStep"
                        :disabled="loading || (isCustomerMode && !supplierInfo)"
                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-500 hover:to-indigo-600 text-white font-black py-5 rounded-2xl shadow-xl shadow-blue-500/20 active:scale-[0.98] transition-all flex items-center justify-center gap-3 group uppercase text-xs tracking-widest disabled:opacity-50"
                    >
                        <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
                        <span v-if="!loading">{{ isCustomerMode ? $t('auth.register.next') : 'Daftar Sekarang' }}</span>
                        <ArrowLeft v-if="!loading && isCustomerMode" class="w-4 h-4 rotate-180 group-hover:translate-x-1 transition-transform" />
                    </button>
                </div>

                <!-- STEP 2: PROFILE DATA (Customer Mode Only) -->
                <div v-if="step === 2 && isCustomerMode" class="space-y-6 animate-in slide-in-from-right-4 duration-500">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-3 ml-1 italic">{{ $t('auth.register.store_name') }}</label>
                        <div class="relative group">
                            <ShoppingBag class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 group-focus-within:text-blue-500 transition-colors" />
                            <input 
                                v-model="form.store_name"
                                type="text" 
                                placeholder="Toko Beras Sejahtera"
                                class="w-full bg-slate-900/40 border border-white/5 rounded-2xl py-4.5 pl-14 pr-5 text-white placeholder:text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 transition-all font-medium"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-3 ml-1 italic">{{ $t('auth.register.address') }}</label>
                        <div class="relative group">
                            <Home class="absolute left-5 top-4 w-5 h-5 text-slate-500 group-focus-within:text-blue-500 transition-colors" />
                            <textarea 
                                v-model="form.address"
                                rows="3"
                                placeholder="Jl. Pahlawan No. 123, Solo, Jawa Tengah"
                                class="w-full bg-slate-900/40 border border-white/5 rounded-2xl py-4.5 pl-14 pr-5 text-white placeholder:text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 transition-all font-medium resize-none"
                            ></textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-3 ml-1 italic">{{ $t('auth.register.phone') }}</label>
                        <div class="relative group">
                            <Phone class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 group-focus-within:text-blue-500 transition-colors" />
                            <input 
                                v-model="form.phone"
                                type="tel" 
                                placeholder="081234567890"
                                class="w-full bg-slate-900/40 border border-white/5 rounded-2xl py-4.5 pl-14 pr-5 text-white placeholder:text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/50 transition-all font-medium"
                            />
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button 
                            type="button"
                            @click="prevStep"
                            class="flex-1 bg-white/5 hover:bg-white/10 text-white font-black py-5 rounded-2xl transition-all uppercase text-[10px] tracking-widest border border-white/5"
                        >
                            {{ $t('auth.register.prev') }}
                        </button>
                        <button 
                            type="submit" 
                            :disabled="loading"
                            class="flex-[2] bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-500 hover:to-indigo-600 text-white font-black py-5 rounded-2xl shadow-2xl shadow-blue-500/20 active:scale-[0.98] transition-all flex items-center justify-center gap-3 disabled:opacity-50 disabled:scale-100 uppercase text-xs tracking-widest"
                        >
                            <Loader2 v-if="loading" class="w-5 h-5 animate-spin" />
                            <CheckCircle2 v-else class="w-5 h-5" />
                            <span>{{ $t('auth.register.submit') }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

            {{ $t('auth.register.already_have') }} 
            <router-link to="/login" class="text-blue-500 font-black hover:text-blue-400 transition-colors decoration-blue-500/30 underline underline-offset-4 ml-1">
                {{ $t('auth.register.login_now') }}
            </router-link>
    </div>
</div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap');

.font-sans {
    font-family: 'Outfit', sans-serif;
}

/* Custom animate classes */
.animate-in {
    animation: fadeIn 0.6s ease-out backwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

input:-webkit-autofill,
input:-webkit-autofill:hover, 
input:-webkit-autofill:focus,
textarea:-webkit-autofill,
textarea:-webkit-autofill:hover,
textarea:-webkit-autofill:focus {
    -webkit-text-fill-color: white;
    -webkit-box-shadow: 0 0 0px 1000px #0f172a inset;
    transition: background-color 5000s ease-in-out 0s;
}
</style>
