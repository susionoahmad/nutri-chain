<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';

const router = useRouter();
const authStore = useAuthStore();

// i18n
const { t, tm, rt, locale } = useI18n({
  messages: {
    en: {
      nav: {
        features: 'Core Features',
        solution: 'Solution',
        pricing: 'Pricing',
        signin: 'Sign in',
        getStarted: 'Join Nutri-Chain'
      },
      hero: {
        update: 'Trusted by Professional Distributors',
        title1: 'Professional System for',
        title2: 'Distribution & Retailers',
        desc: 'Streamline your distribution with integrated stock control, professional invoicing, and real-time financial reporting. Built for growing businesses.',
        startTrial: 'Start 7-Day Trial',
        watchDemo: 'Watch Demo'
      },
      landingProblem: {
        subtitle: 'The Struggle',
        title1: 'Still facing these',
        title2: 'classic problems?',
        item1Title: 'Stock Discrepancies',
        item1Desc: 'Manual records are often inaccurate compared to physical warehouse stock or Excel sheets.',
        item2Title: 'Heavy Manual Work',
        item2Desc: 'Admins must input data multiple times into unintegrated systems, prone to human error.',
        item3Title: 'Ignored Receivables',
        item3Desc: 'Customer bills are not clearly monitored, often forgotten when due for collection.'
      },
      marketsSection: {
        title: 'Perfectly suited for:',
        items: ['Grocery Suppliers', 'Frozen Food Distributors', 'Stationery Wholesalers', 'Beverage Agencies']
      },
      featuresSection: {
        subtitle: 'Everything you need',
        title: 'A Complete Ecosystem',
        desc: 'Stop juggling multiple apps. Nutri-Chain brings your Point of Sale, Inventory Management, and Supply Chain tracking into one beautiful interface.'
      },
      cta: {
        title: 'Ready to Digitalize Your Distribution?',
        desc: 'Join hundreds of professional distributors who have optimized their operations with Nutri-Chain.',
        button: 'Start My Free Trial'
      },
      pricingSection: {
        subtitle: 'Simple Pricing',
        title: 'Invest in Your Growth',
        desc: 'Transparent plans designed for professional scale. No hidden installation fees.',
        mostPopular: 'Most Popular',
        getStarted: 'Select Plan',
        mo: '/mo'
      },
      common: {
        learnMore: 'Learn More',
        revenueToday: 'Revenue Today'
      },
      footer: {
        desc: 'Empowering Indonesian SMEs with professional distribution and supply chain management tools.',
        product: 'Product',
        solutions: 'Solutions',
        resources: 'Resources',
        company: 'Company',
        legal: 'Legal',
        contact: 'Contact Us',
        rights: '© 2026 Nutri-Chain Inc. All rights reserved.'
      },
      newsletter: {
        title: 'Join the Future of Supply Chain',
        desc: 'Get the latest updates on distribution optimization and exclusive SaaS features.',
        placeholder: 'Enter your professional email',
        button: 'Get Early Access'
      }
    },
    id: {
      nav: {
        features: 'Fitur',
        solution: 'Solusi',
        pricing: 'Harga',
        signin: 'Masuk',
        getStarted: 'Mulai Gratis'
      },
      hero: {
        update: 'Solusi Terpercaya untuk Distributor & Retailer',
        title1: 'Sistem Operasional Digital',
        title2: 'Untuk Bisnis yang Bertumbuh',
        desc: 'Nutri-Chain membantu UMKM menengah mengelola stok barang, otomatisasi invoice, dan laporan keuangan dalam satu platform terpadu yang profesional.',
        startTrial: 'Coba Gratis 7 Hari',
        watchDemo: 'Lihat Demo'
      },
      landingProblem: {
        subtitle: 'Kendala Utama',
        title1: 'Masih mengalami',
        title2: 'masalah klasik ini?',
        item1Title: 'Stok Sering Selisih',
        item1Desc: 'Catatan manual seringkali tidak akurat antara fisik di gudang dengan catatan di kertas atau Excel.',
        item2Title: 'Tergantung Manual',
        item2Desc: 'Admin harus input data berkali-kali ke berbagai sistem yang tidak terintegrasi, rawan human error.',
        item3Title: 'Piutang Terabaikan',
        item3Desc: 'Tagihan ke pelanggan tidak terpantau dengan jelas, seringkali lupa ditagih saat jatuh tempo.'
      },
      marketsSection: {
        title: 'Cocok digunakan untuk:',
        items: ['Supplier Sembako', 'Distributor Frozen Food', 'Grosir ATK', 'Agen Minuman']
      },
      featuresSection: {
        subtitle: 'Semua yang Anda butuhkan',
        title: 'Satu Sistem, Kendali Penuh',
        desc: 'Hilangkan kerumitan koordinasi antara gudang, sales, dan admin keuangan. Pantau performa bisnis Anda secara real-time dari mana saja.'
      },
      cta: {
        title: 'Siap Digitalisasi Distribusi Anda?',
        desc: 'Bergabunglah dengan ratusan distributor profesional yang telah mengoptimalkan operasional mereka bersama Nutri-Chain.',
        button: 'Mulai Uji Coba Gratis'
      },
      pricingSection: {
        subtitle: 'Harga Sederhana',
        title: 'Pilih paket Anda',
        desc: 'Harga transparan yang menyesuaikan dengan skala bisnis Anda. Tanpa biaya tersembunyi.',
        mostPopular: 'Paling Populer',
        getStarted: 'Mulai Sekarang',
        mo: '/bulan'
      },
      common: {
        learnMore: 'Pelajari Selengkapnya',
        revenueToday: 'Pendapatan Hari Ini'
      },
      footer: {
        desc: 'Membantu digitalisasi operasional UMKM Indonesia melalui manajemen stok dan rantai pasok yang profesional.',
        product: 'Produk',
        solutions: 'Solusi',
        resources: 'Sumber Daya',
        company: 'Perusahaan',
        legal: 'Legal',
        contact: 'Hubungi Kami',
        rights: '© 2026 Nutri-Chain Inc. Hak Cipta Dilindungi Undang-Undang.'
      },
      newsletter: {
        title: 'Gabung di Masa Depan Rantai Pasok',
        desc: 'Dapatkan informasi terbaru mengenai optimasi distribusi dan fitur SaaS eksklusif.',
        placeholder: 'Masukkan email profesional Anda',
        button: 'Dapatkan Akses'
      }
    }
  }
});

// Sync local language
const currentLanguage = ref(localStorage.getItem('locale') || 'id');
locale.value = currentLanguage.value;

const toggleLanguage = () => {
  const newLang = currentLanguage.value === 'id' ? 'en' : 'id';
  currentLanguage.value = newLang;
  locale.value = newLang;
  localStorage.setItem('locale', newLang);
};

import { 
    ShoppingBag, Receipt, ShieldCheck, BarChart3, 
    ChevronRight, Play, Globe, Check, Star, 
    ArrowRight, Package, Truck, LayoutDashboard,
    Instagram, Twitter, Linkedin, Github,
    Mail, MapPin, Phone, X
} from 'lucide-vue-next';
const isMenuOpen = ref(false);
const isScrolled = ref(false);
const isVideoModalOpen = ref(false);

const openVideoModal = () => {
    isVideoModalOpen.value = true;
    document.body.style.overflow = 'hidden';
};

const closeVideoModal = () => {
    isVideoModalOpen.value = false;
    document.body.style.overflow = 'auto';
};

const handleScroll = () => {
  isScrolled.value = window.scrollY > 50;
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
});

const getStarted = () => {
    if (authStore.isAuthenticated) {
        router.push('/dashboard');
    } else {
        router.push('/register');
    }
};

const selectedFeatureIndex = ref(0);
const featureDetailsRef = ref<HTMLElement | null>(null);

const scrollToFeature = (index: number) => {
  selectedFeatureIndex.value = index;
  featureDetailsRef.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
};

const featureDetailsData = computed(() => [
  {
    title: locale.value === 'id' ? 'Kontrol Stok Tanpa Celah' : 'Seamless Stock Control',
    subtitle: locale.value === 'id' ? 'Presisi gudang hingga level terkecil' : 'Warehouse precision to the smallest level',
    image: '/feature-stock.png',
    points: locale.value === 'id' ? [
        'Sinkronisasi otomatis antara penjualan dan stok fisik.',
        'Peringatan cerdas saat stok menipis (Low Stock Alert).',
        'Riwayat mutasi barang yang transparan dan tidak bisa dimanipulasi.'
    ] : [
        'Automatic synchronization between sales and physical stock.',
        'Smart alerts for low stock levels.',
        'Transparent and immutable movement history.'
    ]
  },
  {
    title: locale.value === 'id' ? 'Sistem Penagihan Pintar' : 'Smart Billing System',
    subtitle: locale.value === 'id' ? 'Kelola piutang dengan lebih profesional' : 'Manage receivables more professionally',
    image: '/feature-invoice.png',
    points: locale.value === 'id' ? [
        'Buat invoice profesional dalam hitungan detik.',
        'Dashboard pantau jatuh tempo piutang pelanggan.',
        'Integrasi langsung dengan laporan arus kas bisnis.'
    ] : [
        'Create professional invoices in seconds.',
        'Monitor customer receivable due dates.',
        'Direct integration with business cash flow reports.'
    ]
  },
  {
    title: locale.value === 'id' ? 'Privasi Keamanan Data' : 'Data Privacy & Security',
    subtitle: locale.value === 'id' ? 'Ruang kerja digital yang terisolasi' : 'Isolated digital workspaces',
    image: '/feature-privacy.png',
    points: locale.value === 'id' ? [
        'Sistem Multi-Tenant memastikan data antar supplier tidak bercampur.',
        'Enkripsi data tingkat tinggi untuk keamanan maksimal.',
        'Kontrol hak akses pengguna yang fleksibel dan ketat.'
    ] : [
        'Multi-tenant system ensures isolated supplier data.',
        'High-level data encryption for maximum security.',
        'Flexible and strict user access controls.'
    ]
  },
  {
    title: locale.value === 'id' ? 'Laporan Bisnis Real-Time' : 'Real-Time Business Reports',
    subtitle: locale.value === 'id' ? 'Keputusan tepat berbasis data akurat' : 'Data-driven decision making',
    image: '/feature-reports.png',
    points: locale.value === 'id' ? [
        'Laporan laba rugi otomatis setiap penutupan hari.',
        'Analisis performa produk terlaris secara visual.',
        'Ekspor data ke berbagai format dengan satu klik.'
    ] : [
        'Automatic P&L reports at daily closing.',
        'Visual analysis of top-performing products.',
        'One-click data export to multiple formats.'
    ]
  }
]);

const featuresData = computed(() => [
  {
    title: locale.value === 'id' ? 'Manajemen Stok Presisi' : 'Precise Stock Management',
    description: locale.value === 'id' ? 'Pantau mutasi barang masuk dan keluar secara real-time dengan sistem pencatatan otomatis yang akurat.' : 'Monitor real-time stock movements with an accurate and automated recording system.',
    icon: ShoppingBag
  },
  {
    title: locale.value === 'id' ? 'Faktur & Piutang' : 'Invoicing & Receivables',
    description: locale.value === 'id' ? 'Kirim invoice profesional ke pelanggan dan pantau jatuh tempo piutang untuk menjaga cash flow bisnis.' : 'Send professional invoices to customers and monitor receivables to maintain business cash flow.',
    icon: Receipt
  },
  {
    title: locale.value === 'id' ? 'Privasi Multi-Tenant' : 'Multi-Tenant Privacy',
    description: locale.value === 'id' ? 'Setiap supplier mendapatkan ruang kerja digital yang mandiri, aman, dan terisolasi untuk kerahasiaan data bisnis.' : 'Each supplier gets an independent, secure, and isolated digital workspace for business data confidentiality.',
    icon: ShieldCheck
  },
  {
    title: locale.value === 'id' ? 'Laporan Bisnis Otomatis' : 'Automated Business Reports',
    description: locale.value === 'id' ? 'Dapatkan laporan laba rugi, performa produk, dan analisis penjualan instan tanpa hitung manual.' : 'Get instant income statements, product performance, and sales analysis without manual calculations.',
    icon: BarChart3
  }
]);

const plansData = computed(() => [
  {
    name: 'Trial',
    price: locale.value === 'id' ? 'Gratis' : 'Free',
    period: locale.value === 'id' ? '/7 hari' : '/7 days',
    description: locale.value === 'id' ? 'Mulai perjalanan Anda dengan mencoba platform kami secara gratis.' : 'Start your journey by testing our platform completely free.',
    features: locale.value === 'id' ? ['Maksimal 3 Pengguna', 'Maksimal 3 Pelanggan', 'Manajemen Stok Dasar', 'Laporan Penjualan'] : ['Up to 3 Users', 'Up to 3 Customers', 'Basic Stock Management', 'Sales Reports'],
    popular: false,
  },
  {
    name: 'Basic',
    price: 'Rp 99k',
    period: t('pricingSection.mo'),
    description: locale.value === 'id' ? 'Solusi optimal untuk bisnis kecil dan menengah yang sedang berkembang.' : 'Optimal solution for growing small and medium businesses.',
    features: locale.value === 'id' ? ['Maksimal 10 Pengguna', 'Pelanggan Tak Terbatas', 'Kontrol Hutang Piutang', 'Laporan Laba Rugi'] : ['Up to 10 Users', 'Unlimited Customers', 'Receivables Control', 'Income Statements'],
    popular: true,
  },
  {
    name: 'Pro',
    price: 'Rp 199k',
    period: t('pricingSection.mo'),
    description: locale.value === 'id' ? 'Akses tanpa batas dengan dukungan penuh untuk operasional skala besar.' : 'Unlimited access with full support for large scale operations.',
    features: locale.value === 'id' ? ['Pengguna Tak Terbatas', 'Manajemen Tim & Hak Akses', 'Analitik Bisnis Lanjutan', 'Prioritas Dukungan 24/7'] : ['Unlimited Users', 'Team & Access Management', 'Advanced Business Analytics', '24/7 Priority Support'],
    popular: false,
  }
]);
</script>

<template>
  <div class="min-h-screen bg-black text-slate-200 font-sans selection:bg-blue-500/30 overflow-x-hidden">
    
    <!-- Navigation -->
    <header :class="['fixed w-full top-0 z-50 transition-all duration-500', isScrolled ? 'bg-black/60 backdrop-blur-2xl shadow-2xl border-b border-white/5 py-3' : 'bg-transparent py-5']">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
          <div class="flex items-center gap-3 group cursor-pointer" @click="router.push('/')">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center text-white font-black text-xl shadow-lg shadow-blue-500/40 group-hover:scale-110 transition-transform">
              N
            </div>
            <span class="text-2xl font-black tracking-tighter bg-clip-text text-transparent bg-gradient-to-r from-white to-slate-400">Nutri-Chain</span>
          </div>
          
          <!-- Desktop Menu -->
          <nav class="hidden md:flex items-center space-x-10">
            <a href="#features" class="text-slate-400 hover:text-white font-bold text-xs uppercase tracking-widest transition-colors">{{ t('nav.features') }}</a>
            <a href="#solution" class="text-slate-400 hover:text-white font-bold text-xs uppercase tracking-widest transition-colors">{{ t('nav.solution') }}</a>
            <a href="#pricing" class="text-slate-400 hover:text-white font-bold text-xs uppercase tracking-widest transition-colors">{{ t('nav.pricing') }}</a>
          </nav>

          <div class="hidden md:flex items-center space-x-6">
            <button @click="toggleLanguage" class="flex items-center gap-2 px-4 py-2 rounded-xl border border-white/10 text-xs font-black text-slate-400 hover:bg-white/5 hover:text-white transition-all uppercase tracking-widest">
              <Globe class="w-4 h-4" />
              <span>{{ currentLanguage === 'id' ? 'ID' : 'EN' }}</span>
            </button>

            <router-link to="/login" class="text-slate-400 hover:text-white font-bold text-xs uppercase tracking-widest transition-colors">{{ t('nav.signin') }}</router-link>
            <button @click="getStarted" class="bg-white text-black hover:bg-blue-500 hover:text-white px-6 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all duration-500 shadow-xl shadow-white/5 active:scale-95">
              {{ t('nav.getStarted') }}
            </button>
          </div>

          <!-- Mobile menu button -->
          <div class="md:hidden flex items-center gap-4">
            <button @click="isMenuOpen = !isMenuOpen" class="text-slate-400 p-2 bg-white/5 rounded-lg border border-white/10">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path v-if="!isMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Menu -->
      <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-10" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-10">
        <div v-if="isMenuOpen" class="md:hidden absolute top-full left-0 w-full bg-black/95 backdrop-blur-2xl shadow-3xl py-8 flex flex-col items-center space-y-6 border-t border-white/5">
            <a href="#features" @click="isMenuOpen = false" class="text-white font-black uppercase tracking-[0.2em] text-xs">{{ t('nav.features') }}</a>
            <a href="#solution" @click="isMenuOpen = false" class="text-white font-black uppercase tracking-[0.2em] text-xs">{{ t('nav.solution') }}</a>
            <a href="#pricing" @click="isMenuOpen = false" class="text-white font-black uppercase tracking-[0.2em] text-xs">{{ t('nav.pricing') }}</a>
            <div class="h-px w-24 bg-white/10"></div>
            <router-link to="/login" class="text-white font-black uppercase tracking-[0.2em] text-xs">{{ t('nav.signin') }}</router-link>
            <button @click="getStarted" class="bg-blue-600 text-white px-10 py-4 rounded-xl font-black uppercase tracking-widest text-xs">{{ t('nav.getStarted') }}</button>
        </div>
      </transition>
    </header>

    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 lg:pt-56 lg:pb-40 overflow-hidden">
      <!-- Background Decorations -->
      <div class="absolute inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-[10%] -left-[10%] w-[60%] h-[60%] bg-blue-600/10 blur-[150px] rounded-full animate-pulse"></div>
        <div class="absolute top-[20%] -right-[10%] w-[45%] h-[55%] bg-indigo-600/10 blur-[150px] rounded-full animate-pulse delay-700"></div>
        <div class="absolute -bottom-[10%] left-[20%] w-[50%] h-[50%] bg-blue-900/10 blur-[150px] rounded-full"></div>
        
        <!-- Grid pattern -->
        <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20 brightness-50"></div>
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:40px_40px]"></div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative text-center z-10">
        <div class="inline-flex items-center gap-3 px-4 py-2 rounded-2xl bg-white/5 border border-white/10 text-blue-400 text-[10px] font-black uppercase tracking-[0.3em] mb-10 animate-fade-in-up shadow-inner italic">
          <span class="flex h-2 w-2 relative">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
          </span>
          {{ t('hero.update') }}
        </div>
        
        <h1 class="text-6xl md:text-8xl font-black tracking-tighter text-white mb-8 animate-fade-in-up leading-[0.9] italic" style="animation-delay: 100ms;">
          {{ t('hero.title1') }} <br class="hidden sm:block" />
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-indigo-300 to-blue-600 animate-gradient-x">{{ t('hero.title2') }}</span>
        </h1>
        
        <p class="mt-4 max-w-2xl text-lg md:text-xl text-slate-400 mx-auto mb-12 animate-fade-in-up leading-relaxed font-medium" style="animation-delay: 200ms;">
          {{ t('hero.desc') }}
        </p>
        
        <div class="flex flex-col sm:flex-row justify-center items-center gap-6 animate-fade-in-up" style="animation-delay: 300ms;">
          <button @click="getStarted" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-500 text-white px-10 py-5 rounded-[24px] font-black text-sm uppercase tracking-widest transition-all duration-500 shadow-2xl shadow-blue-600/40 transform hover:-translate-y-2 flex items-center justify-center gap-3 border border-white/10 group">
            {{ t('hero.startTrial') }}
            <ArrowRight class="w-5 h-5 group-hover:translate-x-2 transition-transform" />
          </button>
          <button @click="openVideoModal" class="w-full sm:w-auto bg-white/5 hover:bg-white/10 text-white px-10 py-5 rounded-[24px] font-black text-sm uppercase tracking-widest border border-white/10 transition-all duration-500 flex items-center justify-center gap-3 backdrop-blur-md">
            <Play class="w-5 h-5 text-blue-400 fill-blue-400" />
            {{ t('hero.watchDemo') }}
          </button>
        </div>

        <!-- Dashboard Preview Mockup -->
        <div class="mt-24 relative mx-auto max-w-6xl animate-fade-in-up" style="animation-delay: 400ms;">
          <div class="relative group">
            <!-- Hero Dashboard Glow -->
            <div class="absolute -inset-10 bg-blue-600/20 blur-[100px] rounded-full opacity-30 group-hover:opacity-50 transition-opacity duration-1000"></div>
            
            <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-[32px] blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
            <div class="relative rounded-[32px] bg-black ring-1 ring-white/10 shadow-3xl p-3 border border-white/5 overflow-hidden">
                <div class="absolute top-0 inset-x-0 h-24 bg-gradient-to-b from-blue-600/10 to-transparent pointer-events-none"></div>
                <img 
                    src="/dashboard-mockup.png" 
                    alt="Nutri-Chain Dashboard Preview" 
                    class="rounded-[24px] w-full h-auto object-cover opacity-90 group-hover:opacity-100 transition-all duration-700 group-hover:scale-[1.02]"
                />
            </div>
          </div>
          
          <!-- Floating UI Elements for depth -->
          <div class="absolute -top-10 -right-10 hidden lg:block animate-bounce duration-[3000ms]">
            <div class="bg-slate-900/80 backdrop-blur-xl border border-white/10 p-4 rounded-2xl shadow-2xl flex items-center gap-4">
               <div class="w-10 h-10 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                  <Check class="w-6 h-6" />
               </div>
               <div class="text-left">
                  <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ t('common.revenueToday') }}</p>
                  <p class="text-lg font-black text-white">Rp 8.420.000</p>
               </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Problem Section (Solution ID) -->
    <section id="solution" class="py-32 bg-black border-y border-white/5 relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-blue-900/10 via-transparent to-transparent"></div>
        <div class="max-w-6xl mx-auto px-4 text-center relative z-10">
          
          <span class="text-blue-500 font-black tracking-[0.4em] uppercase text-[10px] mb-4 block italic">{{ t('landingProblem.subtitle') }}</span>
          <h2 class="text-4xl md:text-6xl font-black text-white tracking-tighter mb-16 italic uppercase">
            {{ t('landingProblem.title1') }} <span class="text-slate-500">{{ t('landingProblem.title2') }}</span>
          </h2>

          <div class="grid md:grid-cols-3 gap-8 text-left">
            <div class="bg-white/[0.02] p-10 rounded-[40px] border border-white/5 hover:border-blue-500/30 transition-all duration-500 group shadow-inner">
              <div class="w-12 h-12 rounded-xl bg-red-500/10 flex items-center justify-center text-red-400 mb-6 group-hover:scale-110 transition-transform">
                <Package class="w-6 h-6" />
              </div>
              <h4 class="text-xl font-black text-white mb-4 italic uppercase tracking-tight">{{ t('landingProblem.item1Title') }}</h4>
              <p class="text-slate-500 font-medium leading-relaxed">{{ t('landingProblem.item1Desc') }}</p>
            </div>

            <div class="bg-white/[0.02] p-10 rounded-[40px] border border-white/5 hover:border-blue-500/30 transition-all duration-500 group shadow-inner">
              <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-400 mb-6 group-hover:scale-110 transition-transform">
                 <LayoutDashboard class="w-6 h-6" />
              </div>
              <h4 class="text-xl font-black text-white mb-4 italic uppercase tracking-tight">{{ t('landingProblem.item2Title') }}</h4>
              <p class="text-slate-500 font-medium leading-relaxed">{{ t('landingProblem.item2Desc') }}</p>
            </div>

            <div class="bg-white/[0.02] p-10 rounded-[40px] border border-white/5 hover:border-blue-500/30 transition-all duration-500 group shadow-inner">
              <div class="w-12 h-12 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-6 group-hover:scale-110 transition-transform">
                 <Receipt class="w-6 h-6" />
              </div>
              <h4 class="text-xl font-black text-white mb-4 italic uppercase tracking-tight">{{ t('landingProblem.item3Title') }}</h4>
              <p class="text-slate-500 font-medium leading-relaxed">{{ t('landingProblem.item3Desc') }}</p>
            </div>
          </div>
        </div>
    </section>

    <!-- Markets Section -->
    <section class="py-20 border-y border-white/5 bg-white/[0.01] relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 text-center">
          <div class="flex flex-wrap justify-center items-center gap-4 md:gap-12">
            <p class="w-full lg:w-auto text-[10px] font-black text-slate-600 uppercase tracking-[0.5em] mb-4 lg:mb-0 mr-4 italic text-center">
              {{ t('marketsSection.title') }}
            </p>
            <div class="flex flex-wrap justify-center gap-4">
               <div v-for="market in tm('marketsSection.items')" :key="market" class="px-6 py-3 rounded-2xl bg-white/[0.03] border border-white/10 flex items-center gap-3 group hover:bg-white/5 transition-all">
                  <div class="w-2 h-2 rounded-full bg-blue-500 group-hover:scale-150 transition-transform"></div>
                  <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ rt(market) }}</span>
               </div>
            </div>
          </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-32 bg-black relative">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-blue-900/10 via-black to-black border-y border-white/5 pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
          <div class="text-center max-w-4xl mx-auto mb-24">
            <h2 class="text-blue-500 font-black tracking-[0.4em] uppercase text-[10px] mb-4 italic animate-pulse">{{ t('featuresSection.subtitle') }}</h2>
            <h3 class="text-4xl md:text-7xl font-black text-white mb-6 tracking-tighter italic uppercase leading-[0.9]">{{ t('featuresSection.title') }}</h3>
            <p class="text-lg text-slate-400 font-medium">{{ t('featuresSection.desc') }}</p>
          </div>

          <!-- Feature Cards Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
              <div v-for="(feature, index) in featuresData" 
                   :key="index" 
                   class="group p-10 rounded-[40px] bg-white/[0.02] border border-white/[0.05] hover:bg-white/[0.05] hover:border-blue-500/30 transition-all duration-700 relative overflow-hidden flex flex-col items-center text-center backdrop-blur-3xl animate-fade-in-up"
              >
                  <!-- Hover Glow -->
                  <div class="absolute -inset-24 bg-blue-600/10 blur-[80px] rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-1000"></div>
                  
                  <div class="w-16 h-16 rounded-3xl bg-gradient-to-br from-blue-600/20 to-indigo-900/20 border border-white/10 flex items-center justify-center text-blue-400 mb-8 group-hover:scale-110 group-hover:rotate-6 transition-all duration-500 shadow-2xl shadow-blue-500/10">
                      <component :is="feature.icon" class="w-8 h-8" />
                  </div>
                  <h4 class="text-xl font-black text-white mb-4 uppercase tracking-tighter">{{ feature.title }}</h4>
                  <p class="text-slate-500 leading-relaxed font-medium text-sm">
                      {{ feature.description }}
                  </p>
                  
                  <!-- Learn More Button -->
                  <button 
                      @click="scrollToFeature(index)"
                      class="mt-10 flex items-center gap-2 text-blue-500 font-black text-[10px] uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-all translate-y-4 group-hover:translate-y-0 hover:text-white"
                  >
                      <span>{{ t('common.learnMore') }}</span>
                      <ChevronRight class="w-4 h-4" />
                  </button>
              </div>
          </div>

          <!-- Feature Deep-Dive Detail Section -->
          <div ref="featureDetailsRef" class="mt-40 pt-20 border-t border-white/5 scroll-mt-32">
              <div class="grid lg:grid-cols-2 gap-20 items-center">
                  <!-- Left: Visual Column -->
                  <div class="relative group">
                      <!-- Background Glow -->
                      <div class="absolute -inset-10 bg-blue-600/10 blur-[120px] rounded-full opacity-50 group-hover:opacity-80 transition-opacity duration-1000"></div>
                      
                      <!-- Mockup Container -->
                      <div class="relative overflow-hidden rounded-[40px] border border-white/10 shadow-2xl bg-black animate-fade-in-up">
                          <transition
                              enter-active-class="transition duration-500 ease-out"
                              enter-from-class="opacity-0 translate-x-10"
                              enter-to-class="opacity-100 translate-x-0"
                              leave-active-class="transition duration-300 ease-in absolute inset-0"
                              leave-from-class="opacity-100"
                              leave-to-class="opacity-0 -translate-x-10"
                          >
                              <img 
                                  :key="featureDetailsData[selectedFeatureIndex].image"
                                  :src="featureDetailsData[selectedFeatureIndex].image" 
                                  :alt="featureDetailsData[selectedFeatureIndex].title"
                                  class="w-full h-auto object-cover"
                              />
                          </transition>
                          
                          <!-- Floating Overlay Effect -->
                          <div class="absolute inset-0 bg-gradient-to-tr from-blue-600/10 to-transparent pointer-events-none"></div>
                      </div>
                  </div>

                  <!-- Right: Context Column -->
                  <div class="relative">
                      <transition
                          mode="out-in"
                          enter-active-class="transition duration-500 ease-out"
                          enter-from-class="opacity-0 translate-y-10"
                          enter-to-class="opacity-100 translate-y-0"
                          leave-active-class="transition duration-300 ease-in"
                          leave-from-class="opacity-100 scale-100"
                          leave-to-class="opacity-0 scale-95"
                      >
                          <div :key="selectedFeatureIndex">
                              <span class="inline-block px-5 py-2 rounded-full bg-blue-600/10 border border-blue-500/20 text-blue-400 font-black text-[10px] uppercase tracking-[0.2em] mb-8">
                                  {{ featureDetailsData[selectedFeatureIndex].subtitle }}
                              </span>
                              <h3 class="text-4xl md:text-6xl font-black text-white mb-8 italic uppercase tracking-tighter leading-none">
                                  {{ featureDetailsData[selectedFeatureIndex].title }}
                              </h3>
                              
                              <div class="space-y-6">
                                  <div v-for="(point, pIdx) in featureDetailsData[selectedFeatureIndex].points" :key="pIdx" class="flex items-start gap-4 group/item">
                                      <div class="w-10 h-10 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center shrink-0 group-hover/item:bg-blue-600 group-hover/item:border-blue-500 transition-all duration-300">
                                          <Check class="w-5 h-5 text-blue-500 group-hover/item:text-white" />
                                      </div>
                                      <p class="text-slate-400 text-lg font-medium leading-relaxed group-hover/item:text-slate-200 transition-colors">
                                          {{ point }}
                                      </p>
                                  </div>
                              </div>

                              <div class="mt-12 flex items-center gap-8">
                                  <button @click="getStarted" class="bg-white text-black hover:bg-blue-600 hover:text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition-all shadow-xl shadow-white/5">
                                      {{ t('cta.button') }}
                                  </button>
                                  <div class="h-12 w-px bg-white/5 hidden md:block"></div>
                                  <div class="hidden md:block">
                                      <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">{{ t('hero.update') }}</p>
                                  </div>
                              </div>
                          </div>
                      </transition>
                  </div>
              </div>
          </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-32 relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_bottom_left,_var(--tw-gradient-stops))] from-indigo-900/20 via-black to-black pointer-events-none"></div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-4xl mx-auto mb-24">
          <h2 class="text-blue-500 font-black tracking-[0.4em] uppercase text-[10px] mb-4 italic">{{ t('pricingSection.subtitle') }}</h2>
          <h3 class="text-4xl md:text-7xl font-black text-white mb-6 uppercase tracking-tighter italic leading-[0.9]">{{ t('pricingSection.title') }}</h3>
          <p class="text-lg text-slate-400 font-medium">{{ t('pricingSection.desc') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
          <div v-for="(plan, index) in plansData" :key="index" 
               :class="['rounded-[48px] p-12 border transition-all duration-700 relative backdrop-blur-3xl overflow-hidden group', 
                       plan.popular ? 'bg-gradient-to-b from-blue-600/10 to-slate-900 shadow-2xl shadow-blue-900/50 border-blue-500/50 scale-100 md:scale-105 z-10' : 'bg-white/[0.02] text-slate-200 border-white/5 hover:border-blue-500/30']">
            
            <div v-if="plan.popular" class="absolute top-0 right-12 transform -translate-y-1/2">
              <span class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-[10px] font-black px-4 py-2 rounded-full uppercase tracking-[0.2em] border border-blue-400/50 shadow-2xl">{{ t('pricingSection.mostPopular') }}</span>
            </div>

            <div class="mb-10">
                <h4 :class="['text-3xl font-black italic uppercase tracking-tighter mb-4', plan.popular ? 'text-white' : 'text-slate-100']">{{ plan.name }}</h4>
                <p :class="['text-sm font-medium tracking-tight h-12 overflow-hidden opacity-60', plan.popular ? 'text-blue-100' : 'text-slate-400']">{{ plan.description }}</p>
            </div>
            
            <div class="flex items-baseline gap-2 mb-10 pb-10 border-b border-white/5">
              <span class="text-6xl font-black text-white tracking-tighter italic">{{ plan.price }}</span>
              <span :class="['text-sm font-black uppercase tracking-widest', plan.popular ? 'text-blue-400/60' : 'text-slate-600']">{{ plan.period }}</span>
            </div>
            
            <ul class="space-y-5 mb-12">
              <li v-for="(feature, fIndex) in plan.features" :key="fIndex" class="flex items-center gap-4 group/item">
                <div :class="['w-5 h-5 rounded-full flex items-center justify-center', plan.popular ? 'bg-blue-500/20 text-blue-400' : 'bg-white/5 text-slate-500']">
                  <Check class="w-3 h-3" />
                </div>
                <span :class="['text-sm font-medium transition-colors', plan.popular ? 'text-slate-200 group-hover/item:text-white' : 'text-slate-400 group-hover/item:text-slate-200']">{{ feature }}</span>
              </li>
            </ul>
            
            <button @click="getStarted" :class="['w-full py-5 rounded-[24px] font-black text-xs uppercase tracking-[0.2em] transition-all duration-500 shadow-inner', 
                    plan.popular ? 'bg-blue-600 hover:bg-blue-500 text-white shadow-xl shadow-blue-500/20' : 'bg-white/5 hover:bg-white/10 text-white border border-white/10']">
              {{ t('pricingSection.getStarted') }}
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Final CTA Section -->
    <section class="py-32 relative overflow-hidden bg-black">
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-blue-500/50 to-transparent"></div>
        <div class="max-w-5xl mx-auto px-4 relative z-10">
          <div class="bg-gradient-to-br from-blue-600/20 to-indigo-900/10 rounded-[64px] p-12 md:p-24 border border-white/10 text-center relative overflow-hidden group">
            <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-600/20 blur-[100px] rounded-full group-hover:scale-150 transition-transform duration-1000"></div>
            <div class="relative z-10">
              <h2 class="text-4xl md:text-6xl font-black text-white mb-8 tracking-tighter italic uppercase leading-none">{{ t('cta.title') }}</h2>
              <p class="text-xl text-slate-400 mb-12 max-w-2xl mx-auto font-medium">{{ t('cta.desc') }}</p>
              <button @click="getStarted" class="bg-white text-black hover:bg-blue-500 hover:text-white px-12 py-6 rounded-[24px] font-black text-md uppercase tracking-[0.2em] transition-all duration-500 shadow-2xl shadow-white/5 hover:-translate-y-2 active:scale-95">
                {{ t('cta.button') }}
              </button>
            </div>
          </div>
        </div>
    </section>

    <!-- Newsletter / Pre-footer -->
    <section class="py-20 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative rounded-[48px] bg-gradient-to-br from-blue-600/10 to-indigo-900/10 border border-white/10 p-12 md:p-20 overflow-hidden group newsletter-card">
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-600/20 blur-[100px] rounded-full group-hover:scale-150 transition-transform duration-1000"></div>
                
                <div class="relative z-10 grid md:grid-cols-2 gap-12 items-center">
                    <div>
                        <h3 class="text-3xl md:text-5xl font-black text-white mb-6 italic uppercase tracking-tighter leading-none">
                            {{ t('newsletter.title') }}
                        </h3>
                        <p class="text-slate-400 text-lg font-medium max-w-md">
                            {{ t('newsletter.desc') }}
                        </p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <input 
                            type="email" 
                            :placeholder="t('newsletter.placeholder')" 
                            class="flex-1 bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white placeholder:text-slate-600 focus:outline-none focus:border-blue-500 transition-colors backdrop-blur-xl"
                        >
                        <button class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition-all shadow-lg shadow-blue-600/20 active:scale-95 whitespace-nowrap">
                            {{ t('newsletter.button') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-black text-slate-400 py-32 border-t border-white/5 relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_bottom_right,_var(--tw-gradient-stops))] from-blue-900/5 via-black to-black pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
          <div class="grid grid-cols-1 lg:grid-cols-4 gap-12 mb-16">
            <!-- Brand & Contact -->
            <div class="lg:col-span-2">
              <div class="flex items-center gap-3 mb-8 group cursor-pointer" @click="router.push('/')">
                  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center text-white font-black text-xl shadow-lg shadow-blue-500/40 group-hover:scale-110 transition-transform">
                    N
                  </div>
                  <span class="text-2xl font-black tracking-tighter bg-clip-text text-transparent bg-gradient-to-r from-white to-slate-400 font-sans">Nutri-Chain</span>
              </div>
              <p class="leading-relaxed mb-8 font-medium text-slate-500 text-sm italic max-w-sm">
                {{ t('footer.desc') }}
              </p>
              <div class="flex gap-4">
                  <a v-for="(Social, name) in { Instagram, Twitter, Linkedin, Github }" :key="name" href="#" class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-slate-500 hover:bg-blue-600 hover:text-white hover:border-blue-500 transition-all group shadow-inner">
                    <component :is="Social" class="w-4 h-4 group-hover:scale-110 transition-transform" />
                  </a>
              </div>
            </div>

            <!-- Simplified Links -->
            <div>
              <h5 class="text-white font-black uppercase tracking-widest text-xs mb-8 italic">{{ t('footer.product') }}</h5>
              <ul class="space-y-4">
                <li v-for="(item, idx) in (locale === 'id' ? ['Stok', 'Kasir', 'Faktur', 'Laporan'] : ['Inventory', 'POS', 'Invoicing', 'Reports'])" :key="item">
                    <a href="javascript:void(0)" @click="scrollToFeature(idx === 1 ? 1 : (idx === 2 ? 1 : idx))" class="text-slate-500 hover:text-white transition-colors uppercase tracking-widest text-[10px] font-black group flex items-center gap-2">
                        <span class="w-1 h-1 bg-blue-600 rounded-full scale-0 group-hover:scale-100 transition-transform"></span>
                        {{ item }}
                    </a>
                </li>
              </ul>
            </div>

            <div>
              <h5 class="text-white font-black uppercase tracking-widest text-xs mb-8 italic">{{ t('footer.company') }}</h5>
              <ul class="space-y-4">
                <li v-for="item in (locale === 'id' ? ['Tentang Kami', 'Kontak', 'Privasi', 'Ketentuan'] : ['About Us', 'Contact', 'Privacy', 'Terms'])" :key="item">
                    <a href="javascript:void(0)" 
                       @click="item === 'Privasi' || item === 'Privacy' ? scrollToFeature(2) : (item === 'Tentang Kami' || item === 'About Us' ? window.scrollTo({top: 0, behavior: 'smooth'}) : (item === 'Kontak' || item === 'Contact' ? document.getElementById('newsletter-card')?.scrollIntoView({behavior: 'smooth'}) : window.scrollTo({top: 0, behavior: 'smooth'})))"
                       class="text-slate-500 hover:text-white transition-colors uppercase tracking-widest text-[10px] font-black group flex items-center gap-2">
                        <span class="w-1 h-1 bg-blue-600 rounded-full scale-0 group-hover:scale-100 transition-transform"></span>
                        {{ item }}
                    </a>
                </li>
              </ul>
            </div>
          </div>

          <div class="pt-12 border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-6 md:gap-4 text-center md:text-left">
            <div class="order-2 md:order-1">
              <p class="text-[9px] md:text-[10px] font-black uppercase tracking-[0.15em] md:tracking-[0.3em] text-slate-600 leading-loose md:leading-normal px-4 md:px-0">
                {{ t('footer.rights') }}
              </p>
            </div>
            <div class="order-1 md:order-2">
              <p class="text-[9px] md:text-[10px] font-black uppercase tracking-[0.15em] md:tracking-[0.3em] text-slate-600 italic opacity-50">
                Made with <span class="text-red-500/60 not-italic mx-1">❤️</span> for Indonesian SMEs
              </p>
            </div>
          </div>
        </div>
    </footer>

    <!-- Video Modal -->
    <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="isVideoModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 lg:p-8">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/90 backdrop-blur-xl" @click="closeVideoModal"></div>
            
            <!-- Modal Content -->
            <div class="relative w-full max-w-5xl aspect-video bg-black rounded-[32px] overflow-hidden shadow-2xl border border-white/10 animate-fade-in-up">
                <button 
                    @click="closeVideoModal"
                    class="absolute top-6 right-6 z-10 w-12 h-12 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-white hover:text-black transition-all border border-white/10"
                >
                    <X class="w-6 h-6" />
                </button>
                
                <iframe 
                    class="w-full h-full"
                    src="https://www.youtube.com/embed/9tIDnbFB6dA?autoplay=1" 
                    title="Nutri-Chain Demo" 
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                    referrerpolicy="strict-origin-when-cross-origin" 
                    allowfullscreen
                ></iframe>

                <!-- Decorative Glow -->
                <div class="absolute -inset-10 bg-blue-600/20 blur-[100px] rounded-full opacity-30 pointer-events-none"></div>
            </div>
        </div>
    </transition>
  </div>
</template>

<style scoped>
html {
  scroll-behavior: smooth;
}
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
@keyframes gradient-x {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}
.animate-gradient-x {
    animation: gradient-x 15s ease infinite;
    background-size: 200% 200%;
}
.animate-fade-in-up {
    animation: fade-in-up 1.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    opacity: 0;
}

.shadow-3xl {
    box-shadow: 0 50px 100px -20px rgba(0, 0, 0, 0.5), 0 30px 60px -30px rgba(0, 0, 0, 0.5);
}



::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: #000;
}
::-webkit-scrollbar-thumb {
    background: #1e293b;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #3b82f6;
}

/* Pulse animation for newsletter section */
@keyframes pulse-border {
    0% { border-color: rgba(59, 130, 246, 0.1); }
    50% { border-color: rgba(59, 130, 246, 0.4); }
    100% { border-color: rgba(59, 130, 246, 0.1); }
}
.newsletter-card {
    animation: pulse-border 4s infinite ease-in-out;
}
</style>
