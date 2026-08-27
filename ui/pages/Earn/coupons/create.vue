<script setup lang="ts">
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main',
  middleware: 'auth'
})

const api = useApi()
const toast = useToast()
const authStore = useAuthStore()
const router = useRouter()

// State
const type = ref<'points' | 'wallet'>('points')
const amount = ref<number>(100)
const quantity = ref<number>(1)
const description = ref<string>('')
const expiresInDays = ref<number | null>(30)
const isLoading = ref(false)
const createdCoupons = ref<any[]>([])

// User balance
const userPoints = computed(() => authStore.points || 0)
const userWallet = computed(() => authStore.user?.wallet || 0)

// Calculations
const totalCost = computed(() => amount.value * quantity.value)
const remainingPoints = computed(() => userPoints.value - totalCost.value)
const remainingWallet = computed(() => userWallet.value - totalCost.value)

// Validation
const minAmount = computed(() => type.value === 'points' ? 10 : 10)
const maxAmount = computed(() => type.value === 'points' ? userPoints.value : userWallet.value)
const maxQuantity = computed(() => {
  const max = Math.floor((type.value === 'points' ? userPoints.value : userWallet.value) / amount.value)
  return max > 0 ? max : 0
})
const isValid = computed(() => {
  return amount.value >= minAmount.value && 
         amount.value <= maxAmount.value &&
         quantity.value >= 1 &&
         quantity.value <= maxQuantity.value &&
         totalCost.value <= (type.value === 'points' ? userPoints.value : userWallet.value) &&
         (!expiresInDays.value || (expiresInDays.value >= 1 && expiresInDays.value <= 365))
})

// Quick amounts
const quickAmounts = computed(() => {
  if (type.value === 'points') {
    return [100, 500, 1000, 5000, 10000]
  }
  return [10, 50, 100, 500, 1000]
})

// Create coupon
const createCoupon = async () => {
  if (!isValid.value || isLoading.value) return
  
  isLoading.value = true
  try {
    const response = await api.post('/api/coupons', {
      type: type.value,
      amount: amount.value,
      quantity: quantity.value,
      description: description.value || undefined,
      expires_in_days: expiresInDays.value || undefined,
    })
    
    if (response.success) {
      const count = response.data.count
      toast.success(`สร้างคูปอง ${count} ใบสำเร็จ!`)
      createdCoupons.value = response.data.coupons
      
      // Update user balance in store
      if (type.value === 'points') {
        authStore.setPoints(remainingPoints.value)
      }
    } else {
      toast.error(response.message || 'ไม่สามารถสร้างคูปองได้')
    }
  } catch (error: any) {
    toast.error(error.message || 'เกิดข้อผิดพลาด')
  } finally {
    isLoading.value = false
  }
}

// Reset form
const resetForm = () => {
  createdCoupons.value = []
  amount.value = 100
  quantity.value = 1
  description.value = ''
}

// Copy code
const copied = ref(false)
const copyCode = async (code: string) => {
  try {
    await navigator.clipboard.writeText(code)
    copied.value = true
    toast.success('คัดลอกรหัสแล้ว!')
    setTimeout(() => copied.value = false, 2000)
  } catch (error) {
    toast.error('ไม่สามารถคัดลอกได้')
  }
}

// Format number
const formatNumber = (num: number): string => {
  return num.toLocaleString()
}
</script>

<template>
  <div class="container mx-auto px-4 py-6 max-w-2xl">
    <!-- Back Button -->
    <NuxtLink 
      to="/earn/coupons"
      class="inline-flex items-center gap-2 text-gray-500 dark:text-gray-400 hover:text-vikinger-purple dark:hover:text-vikinger-cyan mb-6 transition-colors"
    >
      <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
      กลับไปหน้าคูปอง
    </NuxtLink>

    <!-- Success State -->
    <div v-if="createdCoupons.length > 0" class="vikinger-card !p-0 overflow-hidden">
      <!-- Success Header -->
      <div class="bg-gradient-to-r from-green-500 to-emerald-500 p-6 text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-white/20 flex items-center justify-center">
          <Icon icon="fluent:checkmark-circle-24-filled" class="w-10 h-10 text-white" />
        </div>
        <h2 class="text-2xl font-black text-white">สร้างคูปองสำเร็จ!</h2>
        <p class="text-white/80 mt-1">สร้างคูปอง {{ createdCoupons.length }} ใบเรียบร้อยแล้ว</p>
      </div>
      
      <!-- Coupon Details -->
      <div class="p-6">
        <!-- Coupons List -->
        <div class="space-y-4 mb-6 max-h-96 overflow-y-auto">
          <div 
            v-for="(coupon, index) in createdCoupons" 
            :key="coupon.id"
            class="border border-gray-200 dark:border-gray-700 rounded-xl p-4"
          >
            <div class="flex items-start gap-4">
              <!-- QR Code -->
              <div class="flex-shrink-0" v-if="coupon.qr_code_url">
                <img 
                  :src="coupon.qr_code_url" 
                  alt="QR Code" 
                  class="w-20 h-20 rounded-lg border-2 border-gray-200 dark:border-gray-700"
                />
              </div>
              
              <!-- Coupon Info -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2">
                  <span class="text-xs font-bold px-2 py-1 rounded-full bg-vikinger-purple/10 text-vikinger-purple dark:bg-vikinger-purple/20 dark:text-vikinger-cyan">
                    คูปองที่ {{ index + 1 }}
                  </span>
                  <span class="text-xs font-bold px-2 py-1 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                    {{ coupon.coupon_type === 'points' ? '🎯 แต้ม' : '💰 เงิน' }}
                  </span>
                </div>
                
                <!-- Coupon Code -->
                <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-2 mb-2">
                  <p class="text-xs text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">รหัสคูปอง</p>
                  <div class="flex items-center gap-2">
                    <code class="flex-1 text-lg font-mono font-bold text-gray-900 dark:text-white tracking-wider truncate">
                      {{ coupon.coupon_code }}
                    </code>
                    <button 
                      @click="copyCode(coupon.coupon_code)"
                      class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-bold bg-vikinger-purple text-white hover:opacity-90 transition-all flex-shrink-0"
                    >
                      <Icon icon="fluent:copy-24-regular" class="w-4 h-4" />
                    </button>
                  </div>
                </div>
                
                <!-- Amount -->
                <p class="text-sm font-bold text-gray-900 dark:text-white">
                  มูลค่า: {{ coupon.coupon_type === 'wallet' ? '฿' : '' }}{{ formatNumber(coupon.amount) }}
                  {{ coupon.coupon_type === 'points' ? 'แต้ม' : '' }}
                </p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Summary -->
        <div class="bg-gradient-to-r from-vikinger-purple/10 to-vikinger-cyan/10 rounded-xl p-4 mb-6">
          <div class="grid grid-cols-2 gap-4 text-center">
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">จำนวนคูปอง</p>
              <p class="text-2xl font-black text-vikinger-purple dark:text-vikinger-cyan">{{ createdCoupons.length }} ใบ</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">มูลค่ารวม</p>
              <p class="text-2xl font-black text-vikinger-purple dark:text-vikinger-cyan">
                {{ createdCoupons[0].coupon_type === 'wallet' ? '฿' : '' }}{{ formatNumber(createdCoupons.length * createdCoupons[0].amount) }}
                {{ createdCoupons[0].coupon_type === 'points' ? 'แต้ม' : '' }}
              </p>
            </div>
          </div>
        </div>
        
        <!-- Actions -->
        <div class="flex gap-3">
          <button 
            @click="resetForm"
            class="flex-1 py-3 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-white rounded-xl font-bold hover:bg-gray-200 dark:hover:bg-gray-700 transition-all flex items-center justify-center gap-2"
          >
            <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
            สร้างคูปองใหม่
          </button>
          <NuxtLink 
            to="/earn/coupons"
            class="flex-1 py-3 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-xl font-bold hover:opacity-90 transition-all flex items-center justify-center gap-2"
          >
            <Icon icon="fluent:list-24-regular" class="w-5 h-5" />
            ดูคูปองทั้งหมด
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- Create Form -->
    <div v-else class="vikinger-card !p-0 overflow-hidden">
      <!-- Header -->
      <div class="bg-gradient-to-r from-vikinger-purple to-vikinger-cyan p-6">
        <h1 class="text-2xl font-black text-white flex items-center gap-3">
          <Icon icon="fluent:ticket-diagonal-24-filled" class="w-8 h-8" />
          สร้างคูปอง
        </h1>
        <p class="text-white/80 mt-1">แปลงแต้มหรือเงินเป็นคูปองแจกให้คนอื่น</p>
      </div>
      
      <div class="p-6">
        <!-- Type Selector -->
        <div class="mb-6">
          <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">เลือกประเภทคูปอง</p>
          <div class="grid grid-cols-2 gap-3">
            <button
              @click="type = 'points'"
              :class="[
                'p-4 rounded-xl border-2 transition-all text-left',
                type === 'points'
                  ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20'
                  : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
              ]"
            >
              <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center">
                  <Icon icon="fluent:star-24-filled" class="w-5 h-5 text-white" />
                </div>
                <span class="font-bold text-gray-900 dark:text-white">คูปองแต้ม</span>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                ยอดคงเหลือ: <span class="font-bold text-amber-600 dark:text-amber-400">{{ formatNumber(userPoints) }} แต้ม</span>
              </p>
            </button>
            
            <button
              @click="type = 'wallet'"
              :class="[
                'p-4 rounded-xl border-2 transition-all text-left',
                type === 'wallet'
                  ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                  : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
              ]"
            >
              <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center">
                  <Icon icon="fluent:money-24-filled" class="w-5 h-5 text-white" />
                </div>
                <span class="font-bold text-gray-900 dark:text-white">คูปองเงิน</span>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                ยอดคงเหลือ: <span class="font-bold text-emerald-600 dark:text-emerald-400">฿{{ formatNumber(userWallet) }}</span>
              </p>
            </button>
          </div>
        </div>
        
        <!-- Amount Input -->
        <div class="mb-6">
          <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block">
            จำนวน{{ type === 'points' ? 'แต้ม' : 'เงิน (บาท)' }}
          </label>
          <div class="relative">
            <input
              v-model.number="amount"
              type="number"
              :min="minAmount"
              :max="maxAmount"
              :step="type === 'wallet' ? '0.01' : '1'"
              class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xl font-bold focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
            />
            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">
              {{ type === 'points' ? 'แต้ม' : 'บาท' }}
            </span>
          </div>
          
          <!-- Quick Amounts -->
          <div class="flex flex-wrap gap-2 mt-3">
            <button
              v-for="quickAmount in quickAmounts"
              :key="quickAmount"
              @click="amount = quickAmount"
              :disabled="quickAmount > maxAmount"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all',
                amount === quickAmount
                  ? 'bg-vikinger-purple text-white'
                  : quickAmount > maxAmount
                    ? 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'
              ]"
            >
              {{ type === 'wallet' ? '฿' : '' }}{{ formatNumber(quickAmount) }}
            </button>
          </div>
          
          <!-- Validation Message -->
          <p v-if="amount > maxAmount" class="text-red-500 text-xs mt-2">
            ยอด{{ type === 'points' ? 'แต้ม' : 'เงิน' }}ไม่เพียงพอ
          </p>
        </div>
        
        <!-- Quantity Input -->
        <div class="mb-6">
          <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block">
            จำนวนคูปอง
          </label>
          <div class="relative">
            <input
              v-model.number="quantity"
              type="number"
              :min="1"
              :max="maxQuantity"
              class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xl font-bold focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
            />
            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">
              ใบ
            </span>
          </div>
          
          <!-- Quick Quantities -->
          <div class="flex flex-wrap gap-2 mt-3">
            <button
              v-for="q in [1, 5, 10, 20, 50]"
              :key="q"
              @click="quantity = q"
              :disabled="q > maxQuantity"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all',
                quantity === q
                  ? 'bg-vikinger-cyan text-white'
                  : q > maxQuantity
                    ? 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'
              ]"
            >
              {{ q }} ใบ
            </button>
          </div>
          
          <!-- Validation Message -->
          <p v-if="quantity > maxQuantity" class="text-red-500 text-xs mt-2">
            จำนวนคูปองเกินกว่าที่สามารถสร้างได้ (สูงสุด {{ maxQuantity }} ใบ)
          </p>
        </div>
        
        <!-- Real-time Calculation -->
        <div class="bg-gradient-to-r from-vikinger-purple/10 to-vikinger-cyan/10 border border-vikinger-purple/20 dark:border-vikinger-cyan/20 rounded-xl p-4 mb-6">
          <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
            <Icon icon="fluent:calculator-24-regular" class="w-5 h-5 text-vikinger-purple dark:text-vikinger-cyan" />
            คำนวณยอดค่าใช้จ่าย
          </h3>
          <div class="grid grid-cols-2 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-3">
              <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">มูลค่ารวม</p>
              <p class="text-xl font-black text-vikinger-purple dark:text-vikinger-cyan">
                {{ type === 'wallet' ? '฿' : '' }}{{ formatNumber(totalCost) }}
                {{ type === 'points' ? 'แต้ม' : '' }}
              </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-3">
              <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">ยอดคงเหลือหลังสร้าง</p>
              <p class="text-xl font-black" :class="[
                type === 'points' ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'
              ]">
                {{ type === 'wallet' ? '฿' : '' }}{{ formatNumber(type === 'points' ? remainingPoints : remainingWallet) }}
                {{ type === 'points' ? 'แต้ม' : '' }}
              </p>
            </div>
          </div>
        </div>
        
        <!-- Expiry -->
        <div class="mb-6">
          <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block">
            วันหมดอายุ (วัน) - ไม่บังคับ
          </label>
          <div class="flex gap-2">
            <button
              v-for="days in [null, 7, 30, 90, 365]"
              :key="days ?? 'none'"
              @click="expiresInDays = days"
              :class="[
                'px-4 py-2 rounded-lg text-sm font-bold transition-all',
                expiresInDays === days
                  ? 'bg-vikinger-cyan text-white'
                  : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'
              ]"
            >
              {{ days ? `${days} วัน` : 'ไม่จำกัด' }}
            </button>
          </div>
        </div>
        
        <!-- Description -->
        <div class="mb-6">
          <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block">
            รายละเอียด (ไม่บังคับ)
          </label>
          <textarea
            v-model="description"
            rows="3"
            placeholder="ระบุรายละเอียดเพิ่มเติม เช่น สำหรับใคร หรือโอกาสพิเศษ..."
            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-vikinger-purple focus:border-transparent resize-none"
          ></textarea>
        </div>
        
        <!-- Info -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-6">
          <div class="flex gap-3">
            <Icon icon="fluent:info-24-regular" class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" />
            <div class="text-sm text-blue-700 dark:text-blue-300">
              <p class="font-semibold mb-1">หมายเหตุ</p>
              <ul class="list-disc list-inside text-xs space-y-1 text-blue-600 dark:text-blue-400">
                <li>{{ type === 'points' ? 'แต้ม' : 'เงิน' }}จะถูกหักจากบัญชีของคุณทันที (มูลค่ารวม {{ type === 'wallet' ? '฿' : '' }}{{ formatNumber(totalCost) }}{{ type === 'points' ? ' แต้ม' : '' }})</li>
                <li>คูปองสามารถใช้งานได้โดยผู้อื่นผ่าน QR Code หรือรหัส</li>
                <li>ไม่สามารถใช้คูปองของตัวเองได้</li>
                <li>สามารถยกเลิกคูปองได้ก่อนถูกใช้งาน ({{ type === 'points' ? 'แต้ม' : 'เงิน' }}จะถูกคืน)</li>
              </ul>
            </div>
          </div>
        </div>
        
        <!-- Submit Button -->
        <button
          @click="createCoupon"
          :disabled="!isValid || isLoading"
          :class="[
            'w-full py-4 rounded-xl font-bold text-lg transition-all flex items-center justify-center gap-2',
            isValid && !isLoading
              ? 'bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white shadow-lg hover:opacity-90'
              : 'bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed'
          ]"
        >
          <Icon v-if="isLoading" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
          <Icon v-else icon="fluent:ticket-diagonal-24-regular" class="w-5 h-5" />
          {{ isLoading ? 'กำลังสร้าง...' : `สร้างคูปอง ${quantity} ใบ` }}
        </button>
      </div>
    </div>
  </div>
</template>
