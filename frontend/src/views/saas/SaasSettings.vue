<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/api/axios';
import { 
    Save, ShieldCheck, Mail, Phone, 
    CreditCard, Loader2, CheckCircle2 
} from 'lucide-vue-next';

const settings = ref({
    bank_name: '',
    bank_account_number: '',
    bank_account_name: '',
    contact_email: '',
    contact_whatsapp: '',
});

const loading = ref(true);
const saving = ref(false);
const message = ref('');

const fetchSettings = async () => {
    loading.value = true;
    try {
        const { data } = await api.get('/saas/settings');
        settings.value = {
            bank_name: data.bank_name || '',
            bank_account_number: data.bank_account_number || '',
            bank_account_name: data.bank_account_name || '',
            contact_email: data.contact_email || '',
            contact_whatsapp: data.contact_whatsapp || '',
        };
    } catch (error) {
        console.error('Failed to fetch platform settings', error);
    } finally {
        loading.value = false;
    }
};

const saveSettings = async () => {
    saving.value = true;
    message.value = '';
    try {
        await api.post('/saas/settings', { settings: settings.value });
        message.value = 'Pengaturan berhasil diperbarui!';
        setTimeout(() => message.value = '', 3000);
    } catch (error) {
        console.error('Failed to save platform settings', error);
        alert('Gagal menyimpan pengaturan.');
    } finally {
        saving.value = false;
    }
};

onMounted(fetchSettings);
</script>

<template>
<div class="p-8 max-w-4xl">
    <div class="mb-10">
        <h1 class="text-3xl font-bold text-white tracking-tight">{{ $t('saas_platform.settings_title') }}</h1>
        <p class="text-slate-500 mt-1">{{ $t('saas_platform.settings_subtitle') }}</p>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
        <Loader2 class="w-10 h-10 animate-spin text-blue-500" />
    </div>

    <div v-else class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
        <!-- Success Message -->
        <div v-if="message" class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl flex items-center gap-3 text-emerald-400">
            <CheckCircle2 class="w-5 h-5" />
            <span class="font-bold text-sm">{{ message }}</span>
        </div>

        <!-- Bank Information -->
        <div class="bg-slate-900/50 border border-white/10 rounded-[32px] p-8 backdrop-blur-xl">
            <div class="flex items-center gap-4 mb-8">
                <div class="p-3 bg-blue-500/20 rounded-2xl">
                    <CreditCard class="w-6 h-6 text-blue-400" />
                </div>
                <h2 class="text-xl font-bold text-white uppercase italic tracking-tighter">{{ $t('saas_platform.bank_section') }}</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1 italic">{{ $t('saas_platform.bank_name') }}</label>
                    <input v-model="settings.bank_name" type="text" placeholder="e.g. Bank BCA" class="w-full bg-black/40 border border-white/5 rounded-2xl px-6 py-4 text-white focus:ring-2 focus:ring-blue-500/50 outline-none transition-all font-bold italic">
                </div>
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1 italic">{{ $t('saas_platform.acc_number') }}</label>
                    <input v-model="settings.bank_account_number" type="text" placeholder="e.g. 123-456-7890" class="w-full bg-black/40 border border-white/5 rounded-2xl px-6 py-4 text-white focus:ring-2 focus:ring-blue-500/50 outline-none transition-all font-bold tracking-widest">
                </div>
                <div class="md:col-span-2 space-y-2">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1 italic">{{ $t('saas_platform.acc_name') }}</label>
                    <input v-model="settings.bank_account_name" type="text" placeholder="e.g. PT Nutri Chain Solusi" class="w-full bg-black/40 border border-white/5 rounded-2xl px-6 py-4 text-white focus:ring-2 focus:ring-blue-500/50 outline-none transition-all font-bold">
                </div>
            </div>
        </div>

        <!-- Contact Support -->
        <div class="bg-slate-900/50 border border-white/10 rounded-[32px] p-8 backdrop-blur-xl">
            <div class="flex items-center gap-4 mb-8">
                <div class="p-3 bg-indigo-500/20 rounded-2xl">
                    <ShieldCheck class="w-6 h-6 text-indigo-400" />
                </div>
                <h2 class="text-xl font-bold text-white uppercase italic tracking-tighter">{{ $t('saas_platform.support_section') }}</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1 italic leading-none">{{ $t('saas_platform.support_wa') }}</label>
                    <div class="relative">
                        <Phone class="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-600" />
                        <input v-model="settings.contact_whatsapp" type="text" placeholder="628123456789" class="w-full bg-black/40 border border-white/5 rounded-2xl pl-14 pr-6 py-4 text-white focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all font-bold tracking-widest">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1 italic leading-none">{{ $t('saas_platform.support_email') }}</label>
                    <div class="relative">
                        <Mail class="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-600" />
                        <input v-model="settings.contact_email" type="email" placeholder="support@domain.com" class="w-full bg-black/40 border border-white/5 rounded-2xl pl-14 pr-6 py-4 text-white focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all font-bold">
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end pt-4">
            <button 
                @click="saveSettings" 
                :disabled="saving"
                class="bg-blue-600 hover:bg-blue-500 text-white px-10 py-4 rounded-2xl font-black text-xs uppercase tracking-[0.2em] flex items-center gap-4 transition-all active:scale-95 shadow-xl shadow-blue-600/20 disabled:opacity-50"
            >
                <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
                <Save v-else class="w-4 h-4" />
                {{ $t('saas_platform.save_btn') }}
            </button>
        </div>
    </div>
</div>
</template>
