<script setup lang="ts">
import { ref, onMounted, computed, reactive } from 'vue';
import { useSupplierStore } from '@/stores/supplierStore';
import { useI18n } from 'vue-i18n';
import { Building2, Save, MapPin, Phone, Mail, Loader2, CheckCircle2, Key, Share2, Link as LinkIcon } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';

const { t } = useI18n();
const store = useSupplierStore();
const toast = useToast();
const saving = ref(false);
const showSuccess = ref(false);
const linkCopied = ref(false);
const errors = ref<any>({});

const form = reactive({
    name: '',
    contact_person: '',
    email: '',
    phone: '',
    address: '',
    code: ''
});

const isCodeDirty = computed(() => form.code !== store.supplier?.code);

const fetchSupplierData = async () => {
    await store.fetchSupplier();
    if (store.supplier) {
        Object.assign(form, {
            name: store.supplier.name || '',
            contact_person: store.supplier.contact_person || '',
            email: store.supplier.email || '',
            phone: store.supplier.phone || '',
            address: store.supplier.address || '',
            code: store.supplier.code || ''
        });
    }
};

const handleGenerateCode = async () => {
    try {
        console.log("Generating new supplier code...");
        const newCode = await store.generateNewCode();
        if (newCode) {
            form.code = newCode.toUpperCase().trim();
            toast.success("Kode baru dihasilkan: " + form.code);
            toast.info("Klik 'Simpan Konfigurasi' untuk mengaktifkan kode ini.", { timeout: 5000 });
        } else {
            throw new Error("API returned empty code");
        }
    } catch (e: any) {
        console.error("Error generating code:", e);
        const errorMsg = e.response?.data?.message || "Gagal generate kode.";
        toast.error(errorMsg);
    }
};

const copyRegistrationLink = () => {
    const codeToCopy = isCodeDirty.value ? form.code : store.supplier?.code;
    if (!codeToCopy) {
        toast.warning("Simpan kode terlebih dahulu.");
        return;
    }
    
    const url = `${window.location.origin}/register?code=${codeToCopy}`;
    navigator.clipboard.writeText(url);
    linkCopied.value = true;
    toast.success(t('settings.link_copied'));
    setTimeout(() => { linkCopied.value = false; }, 2500);
};

const shareWhatsApp = () => {
    const codeToShare = store.supplier?.code;
    if (!codeToShare) {
        toast.warning("Silakan simpan perubahan kode terlebih dahulu sebelum membagikannya.");
        return;
    }
    
    if (isCodeDirty.value) {
        if (!confirm("Kode di layar berbeda dengan yang tersimpan. Bagikan kode yang sudah tersimpan?")) {
            return;
        }
    }

    const url = `${window.location.origin}/register?code=${codeToShare}`;
    const text = `Halo! Silakan daftar sebagai pelanggan di ${form.name} melalui link berikut: ${url}`;
    window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
};

const saveSupplier = async () => {
    saving.value = true;
    showSuccess.value = false;
    errors.value = {};
    
    // Normalize data
    form.code = form.code?.toUpperCase().trim();
    
    try {
        await store.updateSupplier(form);
        showSuccess.value = true;
        toast.success(t('settings.save_success'));
        setTimeout(() => showSuccess.value = false, 3000);
    } catch (e: any) {
        if (e.response && e.response.status === 422) {
            errors.value = e.response.data.errors;
            toast.error("Validasi gagal. Periksa kembali form.");
        } else {
            toast.error("Gagal menyimpan pengaturan.");
        }
    } finally {
        saving.value = false;
    }
};

onMounted(fetchSupplierData);
</script>

<template>
<div class="animate-in fade-in slide-in-from-bottom-4 duration-700 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div>
            <h1 class="text-4xl font-black tracking-tight mb-2 text-white">{{ t('settings.title') }}</h1>
            <p class="text-slate-500">{{ t('settings.subtitle') }}</p>
        </div>
        <div class="px-6 py-3 rounded-full bg-blue-500/10 text-blue-400 font-bold border border-blue-500/20 text-sm tracking-widest uppercase flex items-center gap-2">
            <Building2 class="w-5 h-5 text-blue-500" />
            {{ t('settings.active_profile') }}
        </div>
    </div>

    <!-- Loading State -->
    <div v-if="store.loading" class="flex flex-col items-center justify-center py-32 text-slate-500">
        <Loader2 class="w-12 h-12 animate-spin mb-4 text-blue-500" />
        <p class="font-bold tracking-widest uppercase text-xs">{{ t('settings.loading') }}</p>
    </div>

    <!-- Form Interface -->
    <div v-else class="bg-white/[0.02] border border-white/5 rounded-[40px] shadow-2xl p-10 md:p-14 backdrop-blur-xl relative overflow-hidden">
        
        <!-- Decorative Glow -->
        <div class="absolute -top-32 -right-32 w-96 h-96 bg-blue-600/10 rounded-full blur-[100px] pointer-events-none"></div>

        <form @submit.prevent="saveSupplier" class="relative z-10 space-y-10">
            <!-- Basic Info Section -->
            <div>
                <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.3em] mb-6 flex items-center gap-2">
                    <Building2 class="w-4 h-4 text-blue-500" />
                    {{ t('settings.corp_details') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block mb-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('settings.company_name') }}</label>
                        <input v-model="form.name" type="text" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-5 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-lg font-black" placeholder="PT Nutri Chain Global">
                    </div>
                    <div>
                        <label class="block mb-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('settings.contact_person') }}</label>
                        <input v-model="form.contact_person" type="text" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-5 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-lg font-bold" placeholder="John Manager">
                    </div>
                </div>
            </div>

            <!-- Supplier Code Section -->
            <div class="pt-8 border-t border-white/5">
                <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.3em] mb-6 flex items-center gap-2">
                    <Key class="w-4 h-4 text-amber-500" />
                    KODE AKSES PELANGGAN
                </h3>
                
                <div class="bg-blue-500/5 border border-blue-500/10 rounded-[32px] p-8">
                    <p class="text-slate-400 text-sm mb-6 max-w-2xl leading-relaxed">
                        Gunakan kode ini agar pelanggan dapat mendaftar di bawah jaringan Anda. Bagikan kode atau link registrasi langsung.
                    </p>

                    <div class="space-y-4">
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="flex-1 relative group">
                                <input 
                                    v-model="form.code" 
                                    type="text" 
                                    class="w-full bg-slate-900/50 border border-white/10 rounded-2xl px-6 py-5 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-2xl font-black text-center tracking-[0.2em] uppercase"
                                    placeholder="KODE-KUSTOM"
                                >
                                <div class="absolute inset-0 rounded-2xl border border-blue-500/0 group-focus-within:border-blue-500/20 pointer-events-none transition-all"></div>
                            </div>
                            <button 
                                type="button" 
                                @click="handleGenerateCode"
                                :disabled="store.generating"
                                class="bg-white/10 hover:bg-white/20 text-white px-8 py-5 rounded-2xl font-black transition-all flex items-center justify-center gap-2 disabled:opacity-30"
                            >
                                <Loader2 v-if="store.generating" class="w-6 h-6 animate-spin" />
                                <span v-else>{{ t('settings.generate_code') }}</span>
                            </button>
                        </div>
                        
                        <div v-if="errors.code" class="text-rose-500 text-xs font-bold pl-2 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 bg-rose-500 rounded-full animate-pulse"></span>
                            {{ errors.code[0] }}
                        </div>

                        <div v-if="isCodeDirty" class="text-amber-400 text-xs font-bold pl-2 flex items-center gap-2 bg-amber-400/10 p-3 rounded-xl border border-amber-400/20">
                            <span class="w-1.5 h-1.5 bg-amber-400 rounded-full"></span>
                            ⚠️ Kode telah diubah. Perubahan ini hanya akan aktif setelah Anda menekan tombol "Simpan Konfigurasi" di bawah.
                        </div>

                        <div class="flex flex-wrap gap-4 pt-2">
                            <button 
                                type="button" 
                                @click="copyRegistrationLink"
                                :class="linkCopied
                                    ? 'bg-emerald-600/20 text-emerald-400 border-emerald-500/30'
                                    : 'bg-slate-800/50 hover:bg-slate-800 text-slate-300 border-white/5'"
                                class="flex-1 min-w-[150px] px-6 py-4 rounded-xl text-sm font-black flex items-center justify-center gap-2 transition-all border"
                            >
                                <CheckCircle2 v-if="linkCopied" class="w-4 h-4" />
                                <LinkIcon v-else class="w-4 h-4 text-blue-400" />
                                {{ linkCopied ? t('settings.link_copied') : t('settings.copy_link') }}
                            </button>
                            <button 
                                type="button" 
                                @click="shareWhatsApp"
                                class="flex-1 min-w-[150px] bg-emerald-600/10 hover:bg-emerald-600/20 text-emerald-400 px-6 py-4 rounded-xl text-sm font-black flex items-center justify-center gap-2 transition-all border border-emerald-500/10"
                            >
                                <Share2 class="w-4 h-4" />
                                {{ t('settings.share_whatsapp') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contacts Info Section -->
            <div class="pt-8 border-t border-white/5">
                <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.3em] mb-6 flex items-center gap-2">
                    <Phone class="w-4 h-4 text-emerald-500" />
                    {{ t('settings.communication') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block mb-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('settings.corp_email') }}</label>
                        <div class="relative">
                            <Mail class="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500" />
                            <input v-model="form.email" type="email" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-16 pr-6 py-5 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-lg font-bold" placeholder="admin@nutrichain.com">
                        </div>
                    </div>
                    <div>
                        <label class="block mb-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('settings.hotline') }}</label>
                        <div class="relative">
                            <Phone class="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500" />
                            <input v-model="form.phone" type="text" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-16 pr-6 py-5 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-lg font-bold" placeholder="+62 811 0000 0000">
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-8 border-t border-white/5">
                 <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.3em] mb-6 flex items-center gap-2">
                    <MapPin class="w-4 h-4 text-rose-500" />
                    {{ t('settings.headquarters') }}
                </h3>
                <div>
                     <label class="block mb-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('settings.address') }}</label>
                     <textarea v-model="form.address" rows="3" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-5 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-lg font-bold" placeholder="Jl. Sudirman Kav 2, Jakarta Pusat..."></textarea>
                </div>
            </div>

            <!-- Action Area -->
            <div class="pt-10 flex items-center justify-between">
                <div class="flex-1 flex items-center min-h-[40px]">
                    <div v-show="showSuccess" class="animate-in fade-in slide-in-from-left-4 text-emerald-400 font-bold flex items-center gap-2 bg-emerald-400/10 px-4 py-2 rounded-xl border border-emerald-500/20">
                        <CheckCircle2 class="w-5 h-5" />
                        {{ t('settings.save_success') }}
                    </div>
                </div>
                <button 
                    type="submit" 
                    :disabled="saving"
                    class="bg-blue-600 hover:bg-blue-500 text-white px-10 py-5 rounded-2xl font-black flex items-center gap-3 shadow-xl shadow-blue-500/20 active:scale-95 transition-all text-lg tracking-wide disabled:opacity-50"
                >
                    <Loader2 v-if="saving" class="w-6 h-6 animate-spin" />
                    <Save v-else class="w-6 h-6" />
                    {{ saving ? t('settings.saving') : t('settings.save_config') }}
                </button>
            </div>
        </form>
    </div>
</div>
</template>
