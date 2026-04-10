<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '@/api';
import { formatCurrency } from '@/utils/format';
import { 
    FileBarChart, Printer, 
    TrendingUp, Wallet, ArrowRightLeft, AlertCircle,
    Loader2, Table, RefreshCcw, MessageSquare
} from 'lucide-vue-next';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();

const loading = ref(false);
const activeReport = ref(auth.user?.role === 'owner' ? 'profit-loss' : 'cash'); // profit-loss, cash, stock, receivables, debts
const startDate = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0]);
const endDate = ref(new Date().toISOString().split('T')[0]);

const activeTabRoute = computed(() => {
    if (activeReport.value === 'receivables' || activeReport.value === 'debts') return 'debt-receivable';
    if (activeReport.value === 'product-analysis') return 'product-analysis';
    return activeReport.value;
});

const reportData = ref<any>(null);

const fetchReport = async () => {
    loading.value = true;
    reportData.value = null;
    try {
        const response = await api.get(`/reports/${activeTabRoute.value}`, {
            params: { start_date: startDate.value, end_date: endDate.value }
        });
        reportData.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const setTab = (tab: string) => {
    activeReport.value = tab;
    fetchReport();
};

const exportToExcel = () => {
    if (!reportData.value) return;
    
    let csvContent = "data:text/csv;charset=utf-8,";
    
    if (activeReport.value === 'profit-loss') {
        csvContent += "Kategori,Nominal\n";
        csvContent += `Total Penjualan,${reportData.value.revenue}\n`;
        csvContent += `Total HPP,${reportData.value.cogs}\n`;
        csvContent += `Laba Kotor,${reportData.value.gross_profit}\n`;
        csvContent += `Biaya Operasional,${reportData.value.expenses}\n`;
        csvContent += `Laba Bersih,${reportData.value.net_profit}\n`;
    } else if (activeReport.value === 'cash') {
        csvContent += "Tanggal,Kategori,Akun,Tipe,Nominal,Catatan\n";
        reportData.value.history.forEach((row: any) => {
            csvContent += `${row.transaction_date},${row.category},${row.account_type},${row.type},${row.amount},"${row.note}"\n`;
        });
    } else if (activeReport.value === 'receivables') {
        csvContent += "Nama Pelanggan,Nominal,Jatuh Tempo,Status\n";
        reportData.value.receivables.forEach((row: any) => {
            csvContent += `${row.name},${row.amount},${row.due_date},${row.status}\n`;
        });
    } else if (activeReport.value === 'debts') {
        csvContent += "Nama Produsen,Nominal,Tanggal Trx,Status\n";
        reportData.value.debts.forEach((row: any) => {
            csvContent += `${row.name},${row.amount},${row.date},${row.status}\n`;
        });
    } else if (activeReport.value === 'product-analysis') {
        csvContent += "Kategori,Produk,Terjual,Omzet,Profit\n";
        reportData.value.products.forEach((row: any) => {
            csvContent += `${row.category || 'Uncategorized'},${row.name},${parseInt(row.total_qty)},${row.revenue},${row.gross_profit}\n`;
        });
    }

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `Laporan_${activeReport.value}_${startDate.value}_to_${endDate.value}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const printReport = () => {
    window.print();
};

const sendWAReminder = (row: any) => {
    const phone = row.phone.replace(/[^0-9]/g, '');
    const brandName = auth.user?.supplier?.name || 'Nutri-Chain';
    const message = `Halo Bapak/Ibu ${row.name},\n\nKami dari *${brandName}* ingin menginformasikan tagihan Anda untuk pesanan *${row.order_number}* sebesar *${formatCurrency(row.amount)}* yang saat ini belum lunas.\n\nMohon untuk segera melakukan pembayaran atau konfirmasi jika sudah dibayar. Terima kasih.`;
    const url = `https://wa.me/${phone.startsWith('0') ? '62' + phone.substring(1) : phone}?text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
};

onMounted(fetchReport);
</script>

<template>
<div class="space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-700 pb-24 print:p-0">
    <!-- Header (No Print) -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 print:hidden">
        <div>
            <h1 class="text-4xl font-black tracking-tight mb-2 text-white flex items-center gap-3 italic uppercase">
                <FileBarChart class="w-10 h-10 text-blue-500" />
                {{ $t('reports.title') }}
            </h1>
            <p class="text-slate-500 uppercase tracking-widest text-sm font-bold">{{ $t('reports.subtitle') }}</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="flex bg-white/5 p-2 rounded-[24px] border border-white/5">
                <input v-model="startDate" type="date" class="bg-transparent text-white text-xs font-bold px-4 focus:outline-none border-r border-white/10 uppercase" />
                <input v-model="endDate" type="date" class="bg-transparent text-white text-xs font-bold px-4 focus:outline-none uppercase" />
            </div>
            <button @click="fetchReport" class="bg-blue-600 hover:bg-blue-500 text-white p-4 rounded-2xl shadow-xl shadow-blue-600/20 active:scale-95 transition-all">
                <Loader2 v-if="loading" class="w-6 h-6 animate-spin" />
                <RefreshCcw v-else class="w-6 h-6" />
            </button>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 print:hidden">
        <button v-for="tab in [
            { id: 'profit-loss', label: $t('reports.tabs.profit_loss'), icon: TrendingUp, roles: ['owner'] },
            { id: 'cash', label: $t('reports.tabs.cash'), icon: Wallet, roles: ['owner', 'admin'] },
            { id: 'stock', label: $t('reports.tabs.stock'), icon: ArrowRightLeft, roles: ['owner', 'admin'] },
            { id: 'product-analysis', label: $t('reports.tabs.analysis'), icon: Table, roles: ['owner', 'admin'] },
            { id: 'receivables', label: $t('reports.tabs.receivables'), icon: AlertCircle, roles: ['owner', 'admin'] },
            { id: 'debts', label: $t('reports.tabs.debts'), icon: AlertCircle, roles: ['owner', 'admin'] }
        ].filter(t => t.roles.includes(auth.user?.role))" :key="tab.id" @click="setTab(tab.id)" :class="activeReport === tab.id ? 'bg-white text-black shadow-2xl' : 'bg-white/5 text-slate-400 border border-white/5 hover:bg-white/10'" class="px-6 py-3 rounded-2xl font-black uppercase tracking-widest text-[10px] flex items-center gap-2 transition-all">
            <component :is="tab.icon" class="w-4 h-4" /> {{ tab.label }}
        </button>
        <div class="ml-auto flex gap-3">
            <button @click="exportToExcel" class="bg-emerald-600/10 hover:bg-emerald-600 text-emerald-500 hover:text-white px-6 py-3 rounded-2xl font-black uppercase text-[10px] flex items-center gap-2 transition-all border border-emerald-500/20">
                <Table class="w-4 h-4" /> Excel
            </button>
            <button @click="printReport" class="bg-white/10 hover:bg-white text-white hover:text-black px-6 py-3 rounded-2xl font-black uppercase text-[10px] flex items-center gap-2 transition-all">
                <Printer class="w-4 h-4" /> Cetak PDF
            </button>
        </div>
    </div>

    <!-- REPORT VIEW -->
    <div v-if="loading" class="py-32 text-center bg-white/5 rounded-[48px] border border-white/10">
        <Loader2 class="w-12 h-12 animate-spin mx-auto text-blue-500 mb-4" />
        <p class="text-slate-500 font-black uppercase tracking-widest italic animate-pulse">{{ $t('reports.generating') }}</p>
    </div>

    <div v-else-if="reportData" class="bg-white p-12 md:p-20 rounded-[64px] shadow-2xl min-h-[600px] text-slate-900 font-serif relative overflow-hidden" id="report-content">
        <!-- Watermark/Design context for screen -->
        <div class="absolute top-0 right-0 p-20 opacity-5 print:hidden"><FileBarChart class="w-64 h-64" /></div>

        <!-- Report Header (Professional) -->
        <div class="border-b-4 border-slate-900 pb-10 mb-12 flex justify-between items-end">
            <div>
                <h2 class="text-3xl font-black tracking-tighter uppercase mb-2">{{ $t('reports.title') }}</h2>
                <p class="text-sm font-bold text-slate-500 uppercase tracking-widest italic">{{ $t('reports.tabs.' + activeReport.replace('-', '_')) }}</p>
            </div>
            <div class="text-right">
                <p class="text-[10px] font-black text-slate-400 uppercase mb-1">{{ $t('reports.period') }}</p>
                <p class="font-bold text-slate-800">{{ new Date(startDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }} - {{ new Date(endDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
            </div>
        </div>

        <!-- 1. PROFIT & LOSS -->
        <div v-if="activeReport === 'profit-loss'" class="space-y-8 animate-in fade-in zoom-in-95 duration-500">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div class="space-y-6">
                    <div class="border-b border-slate-200 pb-4">
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">Pendapatan & Penjualan</h4>
                        <div class="flex justify-between items-center mb-4">
                            <span class="font-bold">Total Omzet Penjualan (Delivered)</span>
                            <span class="font-bold font-mono">{{ formatCurrency(reportData.revenue) }}</span>
                        </div>
                    </div>
                    <div class="border-b border-slate-200 pb-4">
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">Beban Pokok Penjualan (HPP)</h4>
                        <div class="flex justify-between items-center mb-4 text-rose-600">
                            <span class="font-bold italic">Harga Pokok Penjualan (COGS)</span>
                            <span class="font-bold font-mono">({{ formatCurrency(reportData.cogs) }})</span>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 p-10 rounded-[32px] flex flex-col justify-center border border-slate-100">
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Laba Kotor (Gross Profit)</p>
                    <h3 class="text-5xl font-black text-slate-900 font-mono tracking-tighter">{{ formatCurrency(reportData.gross_profit) }}</h3>
                </div>
            </div>

            <div class="border-b-2 border-slate-900 pb-8 mt-12">
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-8 text-center bg-slate-900 text-white py-2 inline-block px-4 rounded-lg">Biaya Operasional & Pengeluaran</h4>
                <div class="flex justify-between items-center text-lg">
                    <span class="font-bold">Total Biaya (Cashflow Out - Expense)</span>
                    <span class="font-bold font-mono text-rose-600">({{ formatCurrency(reportData.expenses) }})</span>
                </div>
            </div>

            <div class="pt-8 flex flex-col items-center">
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Laba Bersih Akhir (Net Profit)</p>
                <div class="bg-slate-900 text-white px-12 py-8 rounded-[40px] text-center shadow-2xl">
                    <h2 class="text-6xl font-black font-mono tracking-tighter italic">{{ formatCurrency(reportData.net_profit) }}</h2>
                </div>
            </div>
        </div>

        <!-- 2. CASH FLOW REPORT -->
        <div v-if="activeReport === 'cash'" class="space-y-8 animate-in fade-in duration-500">
            <div class="grid grid-cols-2 gap-6 mb-12">
                <div class="border-2 border-slate-900 p-8 rounded-3xl text-center">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-2">Total Kas Masuk (+)</p>
                    <p class="text-2xl font-black font-mono text-emerald-600 tracking-tighter">{{ formatCurrency(reportData.summary.total_in) }}</p>
                </div>
                <div class="border-2 border-slate-900 p-8 rounded-3xl text-center">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-2">Total Kas Keluar (-)</p>
                    <p class="text-2xl font-black font-mono text-rose-600 tracking-tighter">{{ formatCurrency(reportData.summary.total_out) }}</p>
                </div>
            </div>
            <table class="w-full text-sm">
                <thead class="border-y-2 border-slate-900 font-black uppercase text-[10px] tracking-widest">
                    <tr>
                        <th class="py-4 px-2">Tanggal</th>
                        <th class="py-4 px-2">Keterangan</th>
                        <th class="py-4 px-2">Akun</th>
                        <th class="py-4 px-2 text-right">Debit (+)</th>
                        <th class="py-4 px-2 text-right">Kredit (-)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="log in reportData.history" :key="log.id">
                        <td class="py-4 px-2 font-mono text-[11px]">{{ log.transaction_date }}</td>
                        <td class="py-4 px-2 font-bold">{{ log.note }} <span class="text-[9px] uppercase font-black px-2 py-0.5 bg-slate-100 rounded text-slate-500 ml-2">{{ log.category }}</span></td>
                        <td class="py-4 px-2 uppercase font-black text-[10px] text-blue-600 italic">{{ log.account_type }}</td>
                        <td class="py-4 px-2 text-right font-mono font-bold">{{ log.type === 'in' ? formatCurrency(log.amount) : '—' }}</td>
                        <td class="py-4 px-2 text-right font-mono font-bold text-rose-600">{{ log.type === 'out' ? '('+formatCurrency(log.amount)+')' : '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 3. STOCK MUTATION -->
        <div v-if="activeReport === 'stock'" class="space-y-12 animate-in fade-in duration-500">
          <div class="grid grid-cols-2 gap-12">
            <div>
              <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 py-2 border-b-2 border-emerald-500 flex items-center justify-between">
                Barang Masuk (In) <span class="px-3 py-1 bg-emerald-500 text-white rounded-full">{{ reportData.in.length }} SKUs</span>
              </h4>
              <div class="space-y-4">
                <div v-for="item in reportData.in" :key="item.name" class="flex justify-between items-center p-4 bg-slate-50 rounded-xl">
                  <span class="font-bold uppercase italic text-xs">{{ item.name }}</span>
                  <span class="font-black text-xl font-mono text-emerald-600">{{ parseInt(item.total_qty) }}</span>
                </div>
              </div>
            </div>
            <div>
              <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 py-2 border-b-2 border-rose-500 flex items-center justify-between">
                Barang Keluar (Out) <span class="px-3 py-1 bg-rose-500 text-white rounded-full">{{ reportData.out.length }} SKUs</span>
              </h4>
              <div class="space-y-4">
                <div v-for="item in reportData.out" :key="item.name" class="flex justify-between items-center p-4 bg-slate-50 rounded-xl">
                  <span class="font-bold uppercase italic text-xs">{{ item.name }}</span>
                  <span class="font-black text-xl font-mono text-rose-600">{{ parseInt(item.total_qty) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. RECEIVABLES -->
        <div v-if="activeReport === 'receivables'" class="space-y-12 animate-in fade-in duration-500">
          <div class="bg-emerald-50 p-10 rounded-[40px] border-4 border-emerald-100 flex justify-between items-center">
             <div>
                <p class="text-xs font-black text-emerald-600 uppercase mb-2">Total Piutang (Klaim Ke Customer)</p>
                <h3 class="text-5xl font-black text-slate-900 font-mono italic">{{ formatCurrency(reportData.total_receivables) }}</h3>
             </div>
             <ArrowRightLeft class="text-emerald-200 w-24 h-24" />
          </div>

          <div>
            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Rincian Piutang Aktif</h4>
            <table class="w-full text-xs">
              <thead class="bg-slate-900 text-white">
                <tr>
                  <th class="py-4 px-6 text-left">Customer</th>
                  <th class="py-4 px-6 text-left">Estimasi Jatuh Tempo</th>
                  <th class="py-4 px-6 text-right">Nominal Tagihan</th>
                  <th class="py-4 px-6 text-center print:hidden">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 border border-slate-100">
                <tr v-for="r in reportData.receivables" :key="r.name">
                  <td class="py-6 px-6 font-bold uppercase italic">{{ r.name }}</td>
                  <td class="py-6 px-6 font-mono">{{ r.due_date }}</td>
                  <td class="py-6 px-6 text-right font-black font-mono text-xl italic text-emerald-600">{{ formatCurrency(r.amount) }}</td>
                  <td class="py-6 px-6 text-center print:hidden">
                    <button @click="sendWAReminder(r)" class="p-3 bg-emerald-500/10 hover:bg-emerald-500 text-emerald-600 hover:text-white rounded-xl transition-all border border-emerald-500/20 group" title="Tagih via WhatsApp">
                        <MessageSquare class="w-5 h-5 group-hover:scale-110 transition-transform" />
                    </button>
                  </td>
                </tr>
                <tr v-if="reportData.receivables.length === 0">
                    <td colspan="4" class="py-12 text-center text-slate-400 italic">Tidak ada piutang aktif saat ini.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- 5. DEBTS -->
        <div v-if="activeReport === 'debts'" class="space-y-12 animate-in fade-in duration-500">
          <div class="bg-rose-50 p-10 rounded-[40px] border-4 border-rose-100 flex justify-between items-center">
             <div>
                <p class="text-xs font-black text-rose-600 uppercase mb-2">Total Hutang (Tunggakan Ke Produsen)</p>
                <h3 class="text-5xl font-black text-slate-900 font-mono italic">{{ formatCurrency(reportData.total_debts) }}</h3>
             </div>
             <Wallet class="text-rose-200 w-24 h-24" />
          </div>

          <div>
            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Rincian Hutang Perusahaan</h4>
            <table class="w-full text-xs">
              <thead class="bg-slate-900 text-white">
                <tr>
                  <th class="py-4 px-6 text-left">Produsen</th>
                  <th class="py-4 px-6 text-left">Tanggal Transaksi</th>
                  <th class="py-4 px-6 text-right">Nominal Hutang</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 border border-slate-100">
                <tr v-for="d in reportData.debts" :key="d.name">
                  <td class="py-6 px-6 font-bold uppercase italic text-rose-900">{{ d.name }}</td>
                  <td class="py-6 px-6 font-mono">{{ d.date }}</td>
                  <td class="py-6 px-6 text-right font-black font-mono text-xl italic text-rose-600">{{ formatCurrency(d.amount) }}</td>
                </tr>
                <tr v-if="reportData.debts.length === 0">
                    <td colspan="3" class="py-12 text-center text-slate-400 italic">Tidak ada hutang aktif saat ini.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- 6. PRODUCT ANALYSIS -->
        <div v-if="activeReport === 'product-analysis'" class="space-y-12 animate-in fade-in duration-500">
            <!-- Category Summary -->
            <div>
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">Ringkasan Per Kategori</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="cat in reportData.categories" :key="cat.category_name" class="p-8 border-2 border-slate-900 rounded-[32px] bg-slate-50 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform"><Package class="w-12 h-12" /></div>
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-4 italic">{{ cat.category_name }}</p>
                        <div class="space-y-1">
                            <p class="text-xs font-bold text-slate-600">Terjual: <span class="text-slate-900">{{ parseInt(cat.total_qty) }} Unit</span></p>
                            <p class="text-xl font-black text-blue-600 font-mono italic">{{ formatCurrency(cat.gross_profit) }}</p>
                            <p class="text-[9px] font-black text-slate-400 uppercase">Total Profit Margin</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Detail Table -->
            <div>
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">Detail Performa Produk</h4>
                <table class="w-full text-xs">
                    <thead class="bg-slate-900 text-white">
                        <tr>
                            <th class="py-4 px-6 text-left">Produk</th>
                            <th class="py-4 px-6 text-left">Kategori</th>
                            <th class="py-4 px-6 text-center">Terjual</th>
                            <th class="py-4 px-6 text-right">Omzet</th>
                            <th class="py-4 px-6 text-right">Profit Bersih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 border border-slate-100">
                        <tr v-for="p in reportData.products" :key="p.name" class="hover:bg-slate-50 transition-all">
                            <td class="py-4 px-6 font-bold uppercase italic text-slate-900">{{ p.name }}</td>
                            <td class="py-4 px-6">
                                <span class="px-3 py-1 bg-slate-100 rounded-full text-[9px] font-black uppercase text-slate-500">{{ p.category || 'Uncategorized' }}</span>
                            </td>
                            <td class="py-4 px-6 text-center font-mono font-bold">{{ parseInt(p.total_qty) }}</td>
                            <td class="py-4 px-6 text-right font-mono font-bold text-slate-600">{{ formatCurrency(p.revenue) }}</td>
                            <td class="py-4 px-6 text-right font-black font-mono text-lg text-emerald-600 italic">{{ formatCurrency(p.gross_profit) }}</td>
                        </tr>
                        <tr v-if="reportData.products.length === 0">
                            <td colspan="5" class="py-12 text-center text-slate-400 italic">Tidak ada data penjualan pada periode ini.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Signature/Footer -->
        <div class="mt-24 pt-12 border-t border-slate-200 flex justify-between italic text-slate-400 text-[10px]">
          <p>Dicetak pada: {{ new Date().toLocaleString() }}</p>
          <p>Nutri-Chain Enterprise Resource Planning</p>
        </div>
    </div>
</div>
</template>

<style scoped>
@media print {
    body * { visibility: hidden; }
    #report-content, #report-content * { visibility: visible; }
    #report-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
        box-shadow: none;
        border: none;
    }
    .print\:hidden { display: none !important; }
}
</style>
