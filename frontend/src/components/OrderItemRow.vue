<template>
  <div class="grid grid-cols-12 gap-4 items-center py-2 border-b last:border-b-0">
    <!-- Product Name -->
    <div class="col-span-4 text-sm font-medium">
      {{ product.name }}
    </div>

    <!-- Quantity Input -->
    <div class="col-span-3">
      <input
        type="number"
        v-model.number="currentQuantity"
        @input="handleQuantityChange"
        min="1"
        class="w-full border rounded p-1 text-sm text-center"
        :class="{
          'border-red-500 bg-red-50': isQuantityExceedsStock,
          'border-green-500 bg-green-50': !isQuantityExceedsStock && currentQuantity > 0 && availableStock !== null
        }"
      />
    </div>

    <!-- Unit -->
    <div class="col-span-1 text-sm text-gray-600">
      {{ product.unit }}
    </div>

    <!-- Stock Info -->
    <div class="col-span-4 text-xs">
      <span v-if="stockLoading" class="text-blue-500 animate-pulse">{{ $t('common.loading_stock') }}</span>
      <span v-else-if="availableStock !== null">
        <p v-if="isQuantityExceedsStock" class="text-red-600 font-semibold">
          {{ $t('orders.out_of_stock') }} ({{ $t('orders.stock_available', { qty: availableStock, unit: stockUnit }) }})
        </p>
        <p v-else-if="currentQuantity > 0" class="text-green-600">
          {{ $t('orders.stock_available', { qty: availableStock, unit: stockUnit }) }}
        </p>
        <p v-else class="text-gray-500">
          {{ $t('orders.stock_available', { qty: availableStock, unit: stockUnit }) }}
        </p>
      </span>
      <span v-else class="text-gray-500">{{ $t('orders.stock_info_unavailable') }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useOrderStore } from '@/stores/order';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vue-toastification';

const props = defineProps<{
  product: {
    id: number;
    name: string;
    unit: string;
    price: number;
  };
  initialQuantity: number;
}>();

const emit = defineEmits(['update:quantity', 'stock-status-change']);

const orderStore = useOrderStore();
const { t } = useI18n();
const toast = useToast();

const currentQuantity = ref(props.initialQuantity);
const availableStock = ref<number | null>(null);
const stockUnit = ref('');
const stockLoading = ref(false);
let debounceTimer: ReturnType<typeof setTimeout> | null = null;

const isQuantityExceedsStock = computed(() => {
  return availableStock.value !== null && currentQuantity.value > availableStock.value;
});

const checkStock = async () => {
  stockLoading.value = true;
  try {
    const stockData = await orderStore.checkProductStock(props.product.id);
    
    // Logika deteksi perubahan status stok: jika sebelumnya cukup, sekarang menjadi kurang
    const wasValid = availableStock.value === null || currentQuantity.value <= availableStock.value;
    const isNowInvalid = currentQuantity.value > stockData.qty;

    availableStock.value = stockData.qty;
    stockUnit.value = stockData.unit;

    if (wasValid && isNowInvalid && currentQuantity.value > 0) {
      toast.warning(t('orders.stock_alert', { name: props.product.name }));
    }

    emit('stock-status-change', { productId: props.product.id, isValid: !isNowInvalid });
  } finally {
    stockLoading.value = false;
  }
};

const handleQuantityChange = () => {
  emit('update:quantity', currentQuantity.value);
  if (debounceTimer) clearTimeout(debounceTimer);
  debounceTimer = setTimeout(checkStock, 500); // Debounce check stock to avoid too many API calls
};

onMounted(() => {
  checkStock(); // Initial stock check when component mounts
});
</script>