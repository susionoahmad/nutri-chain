<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
    <div class="max-w-md w-full bg-white p-8 rounded-lg shadow-md">
      <h2 class="text-2xl font-bold text-center mb-6">{{ $t('auth.register.title') }}</h2>
      
      <form @submit.prevent="handleRegister">
        <!-- Input Supplier Code: Hanya muncul jika TIDAK ada di URL -->
        <div v-if="!hasCodeInUrl" class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Supplier Code
            <span v-if="validatingCode" class="text-xs text-blue-500 animate-pulse">{{ $t('auth.register.verifying_code') }}</span>
          </label>
          <input 
            v-model="form.supplier_code" 
            type="text" 
            @input="debounceCheck"
            class="w-full border p-2 rounded focus:ring-blue-500 focus:border-blue-500"
            :class="{ 'border-green-500 bg-green-50': isCodeValid === true, 'border-red-500 bg-red-50': isCodeValid === false }"
            placeholder="Masukkan kode dari supplier"
            required
          />
          <p v-if="isCodeValid === true" class="text-xs text-green-600 mt-1">
            ✓ {{ $t('auth.register.valid_code', { name: supplierName }) }}
          </p>
          <p v-if="isCodeValid === false" class="text-xs text-red-600 mt-1">
            ✗ {{ $t('auth.register.invalid_code') }}
          </p>
        </div>

        <!-- Info box jika kode otomatis terisi (Optional UI) -->
        <div v-else class="mb-4 p-3 rounded text-sm" :class="isCodeValid ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-blue-50 border border-blue-200 text-blue-700'">
          <template v-if="validatingCode">{{ $t('auth.register.verifying_code') }}</template>
          <template v-else-if="isCodeValid">
            {{ $t('auth.register.valid_code', { name: supplierName }) }}
          </template>
          <template v-else>Anda mendaftar menggunakan link undangan khusus.</template>
        </div>

        <!-- Overlay Penutup jika kode belum valid -->
        <div :class="{'opacity-40 pointer-events-none select-none': !isCodeValid && !validatingCode}">

        <!-- Data Akun -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('auth.register.full_name') }}</label>
          <input v-model="form.name" type="text" class="w-full border p-2 rounded" required />
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('auth.register.email') }}</label>
          <input v-model="form.email" type="email" class="w-full border p-2 rounded" required />
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('auth.register.password') }}</label>
            <input v-model="form.password" type="password" class="w-full border p-2 rounded" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('auth.register.confirm_password') }}</label>
            <input v-model="form.password_confirmation" type="password" class="w-full border p-2 rounded" required />
          </div>
        </div>

        <hr class="my-6" />

        <!-- Data Toko/Profil -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('auth.register.store_name') }}</label>
          <input v-model="form.store_name" type="text" class="w-full border p-2 rounded" required />
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('auth.register.phone') }}</label>
          <input v-model="form.phone" type="text" class="w-full border p-2 rounded" required />
        </div>

        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('auth.register.address') }}</label>
          <textarea v-model="form.address" class="w-full border p-2 rounded" rows="3" required></textarea>
        </div>

        <button 
          type="submit" 
          class="w-full bg-blue-600 text-white py-2 rounded font-bold hover:bg-blue-700 transition-colors"
          :disabled="loading || !isCodeValid"
        >
          {{ loading ? 'Processing...' : $t('auth.register.submit') }}
        </button>id && !validatingCode" class="text-center text-xs text-gray-500 mt-2 italic">
          {{ $t('auth.register.input_code_first') }}
        </p>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, onMounted, computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const loading = ref(false);
const validatingCode = ref(false);
const isCodeValid = ref<boolean | null>(null);
const supplierName = ref('');
let debounceTimer: any = null;

const form = reactive({
  supplier_code: '',
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  store_name: '',
  address: '',
  phone: '',
});

// Computed untuk mengecek apakah kode ada di URL
const hasCodeInUrl = computed(() => !!route.query.code);

onMounted(async () => {
  if (hasCodeInUrl.value) {
    form.supplier_code = route.query.code as string;
    await verifySupplierCode(form.supplier_code);
  }});

const debounceCheck = () => {
  isCodeValid.value = null;
  clearTimeout(debounceTimer);
  if (form.supplier_code.length < 3) return;
  
  debounceTimer = setTimeout(() => {
    verifySupplierCode(form.supplier_code);
  }, 600);
};

const verifySupplierCode = async (code: string) => {
  if (!code) return;
  validatingCode.value = true;
  try {
    const { data } = await axios.get(`/suppliers/check-code/${code}`);
    isCodeValid.value = data.valid;
    supplierName.value = data.supplier_name;
  } catch (err) {
    isCodeValid.value = false;
    supplierName.value = '';
    if (!hasCodeInUrl.value) {
      toast.error("Kode Supplier tidak ditemukan.");
    }
  } finally {
    validatingCode.value = false;
  }
};

const handleRegister = async () => {
  loading.value = true;
  try {
    await axios.post('/register', form);
    toast.success("Registrasi Berhasil!");
    router.push('/login');
  } catch (err: any) {
    toast.error(err.response?.data?.message || "Terjadi kesalahan");
  } finally {
    loading.value = false;
  }
};
</script>