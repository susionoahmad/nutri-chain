<script setup lang="ts">
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import { LogIn, Key, Mail, Loader2, AlertCircle } from 'lucide-vue-next';

const auth = useAuthStore();
const router = useRouter();

const form = ref({
    email: '',
    password: '',
});

const loading = ref(false);
const errorMessage = ref('');

const handleLogin = async () => {
    loading.value = true;
    errorMessage.value = '';
    try {
        await auth.login(form.value);
        if (auth.isSuperAdmin) {
            router.push('/saas/dashboard');
        } else {
            router.push('/');
        }
    } catch (error: any) {
        errorMessage.value = error;
    } finally {
        loading.value = false;
    }
};
</script>

<template>
<div class="min-h-screen flex items-center justify-center bg-slate-950 p-6 relative overflow-hidden">
    <!-- Decorative Gradients -->
    <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-600/20 rounded-full blur-[120px]"></div>
    <div class="absolute -bottom-[10%] -right-[10%] w-[40%] h-[40%] bg-indigo-600/20 rounded-full blur-[120px]"></div>

    <div class="w-full max-w-md">
        <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
            <!-- Logo & Title -->
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-tr from-blue-600 to-indigo-600 rounded-2xl mb-4 shadow-lg shadow-blue-500/20">
                    <LogIn class="text-white w-8 h-8" />
                </div>
                <h1 class="text-3xl font-bold text-white tracking-tight mb-2">{{ $t('brand.name') }}</h1>
                <p class="text-slate-400 font-medium">{{ $t('brand.subtitle') }}</p>
            </div>

            <!-- Error State -->
            <div v-if="errorMessage" class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl flex items-center gap-3 animate-in fade-in slide-in-from-top-4">
                <AlertCircle class="text-red-400 w-5 h-5 flex-shrink-0" />
                <p class="text-red-200 text-sm font-medium">{{ errorMessage }}</p>
            </div>

            <!-- Form -->
            <form @submit.prevent="handleLogin" class="space-y-6">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">{{ $t('auth.login.email') }}</label>
                    <div class="relative group">
                        <Mail class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 group-focus-within:text-blue-500 transition-colors" />
                        <input 
                            v-model="form.email"
                            type="email" 
                            required
                            :placeholder="$t('auth.login.email')"
                            class="w-full bg-slate-900/50 border border-white/10 rounded-xl py-4 pl-12 pr-4 text-white placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-all"
                        />
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2 ml-1">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">{{ $t('auth.login.password') }}</label>
                        <a href="#" class="text-xs text-blue-500 hover:text-blue-400 font-bold tracking-wide">Forgot Password?</a>
                    </div>
                    <div class="relative group">
                        <Key class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 group-focus-within:text-blue-500 transition-colors" />
                        <input 
                            v-model="form.password"
                            type="password" 
                            required
                            :placeholder="$t('auth.login.password')"
                            class="w-full bg-slate-900/50 border border-white/10 rounded-xl py-4 pl-12 pr-4 text-white placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-all"
                        />
                    </div>
                </div>

                <button 
                    type="submit" 
                    :disabled="loading"
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-500/20 active:scale-[0.98] transition-all disabled:opacity-50 disabled:scale-100 flex items-center justify-center gap-2"
                >
                    <Loader2 v-if="loading" class="w-5 h-5 animate-spin" />
                    <span v-else>{{ $t('auth.login.submit') }}</span>
                </button>
            </form>
        </div>

        <p class="text-center mt-8 text-slate-500 text-sm">
            {{ $t('auth.login.no_account') }}
            <router-link to="/register" class="text-blue-500 font-bold hover:text-blue-400 transition-colors ml-1 underline decoration-blue-500/30 underline-offset-4">
                {{ $t('auth.login.register') }}
            </router-link>
        </p>
    </div>
</div>
</template>

<style>
/* Modern styling tokens */
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap');

body {
    font-family: 'Outfit', sans-serif;
}
</style>
