<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4 py-12">
    <div class="max-w-md w-full bg-white p-8 rounded-lg shadow-xl">
      <h2 class="text-3xl font-black text-center mb-2 italic tracking-tighter">
        {{ registerMode === 'owner' ? 'BUAT PERUSAHAAN BARU' : 'GABUNG SEBAGAI MITRA' }}
      </h2>
      <p class="text-center text-gray-500 text-sm mb-6 font-medium">
        {{ registerMode === 'owner' ? 'Jadilah pusat supply chain (Supplier Utama).' : 'Pendaftaran Customer via kode undangan.' }}
      </p>

      <!-- Toggle Mode -->
      <div v-if="!hasCodeInUrl" class="flex p-1 bg-gray-100 rounded-lg mb-8">
        <button 
          @click="registerMode = 'owner'"
          :class="registerMode === 'owner' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-700'"
          class="flex-1 py-2 text-sm font-bold rounded-md transition-all"
        >
          Perusahaan Baru (Owner)
        </button>
        <button 
          @click="registerMode = 'customer'"
          :class="registerMode === 'customer' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-700'"
          class="flex-1 py-2 text-sm font-bold rounded-md transition-all"
        >
          Mitra (Customer)
        </button>
      </div>

      <form @submit.prevent="handleRegister">
        
        <!-- CUSTOMER SPECIFIC: Supplier Code -->
        <div v-if="registerMode === 'customer'" class="mb-4">
          <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1 italic">
            Kode Perusahaan (Supplier)
            <span v-if="validatingCode" class="text-blue-500 animate-pulse ml-2">Memeriksa...</span>
          </label>
          <input 
            v-if="!hasCodeInUrl"
            v-model="form.supplier_code" 
            type="text" 
            @input="debounceCheck"
            class="w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-bold bg-gray-50 uppercase"
            :class="{ 'border-green-500 bg-green-50 text-green-700': isCodeValid === true, 'border-red-500 bg-red-50 text-red-700': isCodeValid === false }"
            placeholder="Ketik kode dari pusat"
            required
          />
          <!-- Jika via URL -->
          <div v-else class="p-4 rounded-lg bg-blue-50 border border-blue-100 text-blue-700 font-bold flex items-center justify-between">
            <span>{{ form.supplier_code }}</span>
            <span class="text-xs font-black uppercase tracking-widest bg-blue-200 px-2 py-1 rounded">Invite Code</span>
          </div>

          <p v-if="isCodeValid === true" class="text-xs font-bold text-green-600 mt-2">
            ✓ Terdaftar di: {{ supplierName }}
          </p>
          <p v-if="isCodeValid === false" class="text-xs font-bold text-red-500 mt-2">
            ✗ Kode Perusahaan tidak ditemukan / tidak valid
          </p>
        </div>

        <div :class="{'opacity-30 pointer-events-none grayscale': registerMode === 'customer' && !isCodeValid}">
          <!-- NAMA PERUSAHAAN / TOKO -->
          <div class="mb-4">
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1 italic">
              {{ registerMode === 'owner' ? 'Nama Perusahaan Anda' : 'Nama Toko Anda' }}
            </label>
            <input v-model="form.company_name" type="text" class="w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-500 font-bold" :placeholder="registerMode === 'owner' ? 'PT Makmur Jaya' : 'Toko Sembako Maju'" required />
          </div>

          <!-- NAMA PEMILIK / PIC -->
          <div class="mb-4">
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1 italic">Nama Lengkap (PIC)</label>
            <input v-model="form.name" type="text" class="w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-500 font-medium" placeholder="Budi Santoso" required />
          </div>

          <!-- KONTAK (Hanya Customer, karena Owner isi via Onboarding) -->
          <div v-if="registerMode === 'customer'" class="space-y-4 mb-4">
            <div>
               <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1 italic">Nomor WhatsApp</label>
               <input v-model="form.phone" type="tel" class="w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-500 font-medium" placeholder="0812345678" required />
            </div>
            <div>
               <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1 italic">Alamat Lengkap</label>
               <textarea v-model="form.address" rows="2" class="w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-500 font-medium" placeholder="Jl. Raya No. 12" required></textarea>
            </div>
          </div>

          <div class="mb-4">
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1 italic">Alamat Email Login</label>
            <input v-model="form.email" type="email" class="w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-500 font-medium" placeholder="email@contoh.com" required />
          </div>

          <div class="grid grid-cols-2 gap-4 mb-8">
            <div>
              <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1 italic">Password</label>
              <input v-model="form.password" type="password" class="w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-500" required />
            </div>
            <div>
              <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1 italic">Konfirmasi</label>
              <input v-model="form.password_confirmation" type="password" class="w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-500" required />
            </div>
          </div>

          <button 
            type="submit" 
            class="w-full bg-blue-600 text-white py-4 rounded-xl font-black uppercase tracking-widest hover:bg-blue-700 transition-all active:scale-95 shadow-lg shadow-blue-600/30 disabled:opacity-50 flex items-center justify-center gap-2"
            :disabled="loading || (registerMode === 'customer' && !isCodeValid)"
          >
            <span v-if="loading" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ loading ? 'Mendaftarkan...' : 'Daftar Sekarang' }}
          </button>
        </div>
      </form>

      <p class="text-center text-sm text-gray-500 mt-6 font-medium">
        Sudah punya akun? 
        <router-link to="/login" class="text-blue-600 font-bold hover:underline">Masuk di sini</router-link>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, onMounted, computed, ref } from 'vue';
import { useRoute } from 'vue-router';
import axios from '@/api/axios'; // We should use our configured api/axios to ensure VITE_API_URL is used!
import { useToast } from 'vue-toastification';

const route = useRoute();
const toast = useToast();
const loading = ref(false);
const validatingCode = ref(false);
const isCodeValid = ref<boolean | null>(null);
const supplierName = ref('');
let debounceTimer: any = null;

const registerMode = ref<'owner' | 'customer'>('owner');

const form = reactive({
  supplier_code: '',
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  company_name: '', // Digunakan di Owner (sebagai nama supplier) & Customer (sebagai store_name)
  address: '',
  phone: '',
});

// Computed untuk mengecek apakah ada kode invite di URL
const hasCodeInUrl = computed(() => !!route.query.code);

onMounted(async () => {
  if (hasCodeInUrl.value) {
    registerMode.value = 'customer';
    form.supplier_code = route.query.code as string;
    await verifySupplierCode(form.supplier_code);
  }
});

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
      toast.error("Kode Perusahaan tidak ditemukan.");
    }
  } finally {
    validatingCode.value = false;
  }
};

const handleRegister = async () => {
  loading.value = true;
  try {
    if (registerMode.value === 'owner') {
      const payload = {
        company_name: form.company_name,
        name: form.name,
        email: form.email,
        password: form.password,
        password_confirmation: form.password_confirmation
      };
      const res = await axios.post('/register/supplier', payload);
      toast.success("Perusahaan berhasil dibuat! Silakan melengkapi profil.");
      localStorage.setItem('token', res.data.access_token);
      window.location.href = '/onboarding'; // Arahkan owner baru ke onboarding
    } else {
      const payload = {
        supplier_code: form.supplier_code,
        store_name: form.company_name,
        name: form.name,
        email: form.email,
        password: form.password,
        password_confirmation: form.password_confirmation,
        address: form.address,
        phone: form.phone
      };
      const res = await axios.post('/register/customer', payload);
      toast.success("Berhasil bergabung sebagai Customer!");
      localStorage.setItem('token', res.data.access_token);
      window.location.href = '/dashboard';
    }
  } catch (err: any) {
    toast.error(err.response?.data?.message || "Terjadi kesalahan saat pendaftaran");
  } finally {
    loading.value = false;
  }
};
</script>