<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  modelValue: boolean
  course: {
    id: number
    name: string
    price?: number
    tuition_fees?: number
    discount?: number
    cover_url?: string
  }
}

interface PurchaseInfo {
  can_purchase: boolean
  original_price: number
  discount_percent: number
  discount_amount: number
  final_price: number
  balance: number
  shortfall: number
  is_free: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'confirm'): void
  (e: 'topup'): void
}>()

const api = useApi()
const isLoading = ref(true)
const isProcessing = ref(false)
const purchaseInfo = ref<PurchaseInfo | null>(null)
const error = ref('')

// Computed
const isOpen = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const canPurchase = computed(() => purchaseInfo.value?.can_purchase ?? false)

// Fetch purchase info when modal opens
watch(isOpen, async (open) => {
  if (open && props.course?.id) {
    await fetchPurchaseInfo()
  }
})

const fetchPurchaseInfo = async () => {
  isLoading.value = true
  error.value = ''
  
  try {
    const response = await api.get(`/api/courses/${props.course.id}/purchase/check`)
    if (response.success) {
      purchaseInfo.value = response
    }
  } catch (err: any) {
    error.value = err.data?.message || 'ไม่สามารถโหลดข้อมูลได้'
  } finally {
    isLoading.value = false
  }
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('th-TH', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(price)
}

const confirmPurchase = async () => {
  isProcessing.value = true
  error.value = ''
  
  try {
    emit('confirm')
    isOpen.value = false
  } catch (err: any) {
    error.value = err.data?.message || 'การซื้อล้มเหลว'
  } finally {
    isProcessing.value = false
  }
}

const goToTopup = () => {
  emit('topup')
  isOpen.value = false
}

const close = () => {
  isOpen.value = false
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="close"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        
        <!-- Modal Content -->
        <div class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
          <!-- Header -->
          <div class="relative bg-gradient-to-r from-emerald-500 to-teal-600 p-6 text-white">
            <button
              @click="close"
              class="absolute top-4 right-4 p-1 rounded-full hover:bg-white/20 transition-colors"
            >
              <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" />
            </button>
            
            <div class="flex items-center gap-4">
              <div class="w-16 h-16 rounded-xl bg-white/20 flex items-center justify-center">
                <Icon icon="fluent:cart-24-filled" class="w-8 h-8" />
              </div>
              <div>
                <h2 class="text-xl font-bold">ยืนยันการซื้อ</h2>
                <p class="text-white/80 text-sm">{{ course.name }}</p>
              </div>
            </div>
          </div>
          
          <!-- Body -->
          <div class="p-6">
            <!-- Loading State -->
            <div v-if="isLoading" class="flex items-center justify-center py-12">
              <Icon icon="svg-spinners:ring-resize" class="w-10 h-10 text-emerald-500" />
            </div>
            
            <!-- Error State -->
            <div v-else-if="error" class="text-center py-8">
              <Icon icon="fluent:error-circle-24-regular" class="w-12 h-12 text-red-500 mx-auto mb-3" />
              <p class="text-red-500">{{ error }}</p>
              <button
                @click="fetchPurchaseInfo"
                class="mt-4 px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
              >
                ลองใหม่
              </button>
            </div>
            
            <!-- Purchase Info -->
            <div v-else-if="purchaseInfo" class="space-y-4">
              <!-- Price Breakdown -->
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 space-y-3">
                <!-- Original Price -->
                <div class="flex items-center justify-between text-gray-600 dark:text-gray-400">
                  <span>ราคาเต็ม</span>
                  <span :class="{ 'line-through': purchaseInfo.discount_amount > 0 }">
                    ฿{{ formatPrice(purchaseInfo.original_price) }}
                  </span>
                </div>
                
                <!-- Discount -->
                <div v-if="purchaseInfo.discount_amount > 0" class="flex items-center justify-between text-emerald-600">
                  <span class="flex items-center gap-1">
                    <Icon icon="fluent:tag-24-regular" class="w-4 h-4" />
                    ส่วนลด {{ purchaseInfo.discount_percent }}%
                  </span>
                  <span>-฿{{ formatPrice(purchaseInfo.discount_amount) }}</span>
                </div>
                
                <!-- Divider -->
                <div class="border-t border-gray-200 dark:border-gray-600"></div>
                
                <!-- Final Price -->
                <div class="flex items-center justify-between">
                  <span class="font-bold text-gray-900 dark:text-white">ราคาสุทธิ</span>
                  <span class="text-2xl font-bold text-emerald-600">
                    {{ purchaseInfo.is_free ? 'ฟรี' : `฿${formatPrice(purchaseInfo.final_price)}` }}
                  </span>
                </div>
              </div>
              
              <!-- Wallet Balance -->
              <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                  <Icon icon="fluent:wallet-24-regular" class="w-5 h-5" />
                  <span>ยอดเงินของคุณ</span>
                </div>
                <span class="font-bold text-blue-600 dark:text-blue-400">
                  ฿{{ formatPrice(purchaseInfo.balance) }}
                </span>
              </div>
              
              <!-- Insufficient Balance Warning -->
              <div
                v-if="!canPurchase && !purchaseInfo.is_free"
                class="flex items-start gap-3 p-4 bg-red-50 dark:bg-red-900/20 rounded-xl text-red-600 dark:text-red-400"
              >
                <Icon icon="fluent:warning-24-filled" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                <div>
                  <p class="font-medium">ยอดเงินไม่เพียงพอ</p>
                  <p class="text-sm opacity-80">
                    คุณต้องเติมเงินอีก ฿{{ formatPrice(purchaseInfo.shortfall) }} เพื่อซื้อรายวิชานี้
                  </p>
                </div>
              </div>
              
              <!-- Actions -->
              <div class="flex gap-3 pt-2">
                <button
                  @click="close"
                  class="flex-1 py-3 px-4 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                >
                  ยกเลิก
                </button>
                
                <button
                  v-if="canPurchase || purchaseInfo.is_free"
                  @click="confirmPurchase"
                  :disabled="isProcessing"
                  class="flex-1 py-3 px-4 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-bold hover:from-emerald-600 hover:to-teal-700 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
                >
                  <Icon v-if="isProcessing" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                  <Icon v-else icon="fluent:checkmark-24-regular" class="w-5 h-5" />
                  {{ purchaseInfo.is_free ? 'ลงทะเบียนฟรี' : 'ยืนยันการซื้อ' }}
                </button>
                
                <button
                  v-else
                  @click="goToTopup"
                  class="flex-1 py-3 px-4 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl font-bold hover:from-blue-600 hover:to-indigo-700 transition-all flex items-center justify-center gap-2"
                >
                  <Icon icon="fluent:add-circle-24-regular" class="w-5 h-5" />
                  เติมเงิน
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from > div:last-child,
.modal-leave-to > div:last-child {
  transform: scale(0.95) translateY(20px);
}
</style>
