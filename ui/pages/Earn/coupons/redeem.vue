<script setup lang="ts">
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main',
  middleware: 'auth'
})

const api = useApi()
const toast = useToast()
const authStore = useAuthStore()

// State
const method = ref<'scan' | 'code'>('code')
const couponCode = ref('')
const isLoading = ref(false)
const isScanning = ref(false)
const result = ref<any>(null)

// Refs for camera
const videoRef = ref<HTMLVideoElement | null>(null)
const canvasRef = ref<HTMLCanvasElement | null>(null)
let stream: MediaStream | null = null
let animationId: number | null = null

// Toggle scanning
const toggleScanning = async () => {
  if (isScanning.value) {
    stopScanning()
  } else {
    await startScanning()
  }
}

// Start camera scanning
const startScanning = async () => {
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'environment' }
    })
    
    if (videoRef.value) {
      videoRef.value.srcObject = stream
      videoRef.value.play()
      isScanning.value = true
      scanQRCode()
    }
  } catch (error) {
    console.error('Camera error:', error)
    toast.error('ไม่สามารถเปิดกล้องได้')
  }
}

// Stop camera scanning
const stopScanning = () => {
  if (stream) {
    stream.getTracks().forEach(track => track.stop())
    stream = null
  }
  if (animationId) {
    cancelAnimationFrame(animationId)
    animationId = null
  }
  isScanning.value = false
}

// Scan QR Code from video
const scanQRCode = () => {
  if (!videoRef.value || !canvasRef.value || !isScanning.value) return
  
  const video = videoRef.value
  const canvas = canvasRef.value
  const ctx = canvas.getContext('2d')
  
  if (!ctx || video.readyState !== video.HAVE_ENOUGH_DATA) {
    animationId = requestAnimationFrame(scanQRCode)
    return
  }
  
  canvas.width = video.videoWidth
  canvas.height = video.videoHeight
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height)
  
  // In production, use a QR code library like jsQR
  // For now, we'll just continue scanning
  animationId = requestAnimationFrame(scanQRCode)
}

// Paste from clipboard
const pasteCode = async () => {
  try {
    const text = await navigator.clipboard.readText()
    // Keep only numeric characters
    couponCode.value = text.replace(/\D/g, '').slice(0, 8)
    toast.success('วางรหัสแล้ว')
  } catch (error) {
    toast.error('ไม่สามารถวางได้')
  }
}

// Redeem coupon
const redeemCoupon = async () => {
  if (!couponCode.value || isLoading.value) return
  
  isLoading.value = true
  try {
    const response = await api.post('/api/coupons/redeem', {
      coupon_code: couponCode.value.replace(/\D/g, '')
    })
    
    if (response.success) {
      result.value = {
        success: true,
        message: response.message,
        type: response.data.type,
        amount: response.data.amount,
        new_balance: response.data.new_balance,
      }
      
      // Update user balance in store
      if (response.data.type === 'points') {
        authStore.setPoints(response.data.new_balance)
      }
      
      toast.success(response.message || 'รับคูปองสำเร็จ!')
    } else {
      result.value = {
        success: false,
        message: response.message || 'ไม่สามารถใช้คูปองได้',
      }
      toast.error(response.message || 'ไม่สามารถใช้คูปองได้')
    }
  } catch (error: any) {
    result.value = {
      success: false,
      message: error.message || 'เกิดข้อผิดพลาด',
    }
    toast.error(error.message || 'เกิดข้อผิดพลาด')
  } finally {
    isLoading.value = false
    stopScanning()
  }
}

// Reset
const reset = () => {
  result.value = null
  couponCode.value = ''
}

// Cancel coupon
const cancelCoupon = async () => {
  if (!confirm('คุณต้องการยกเลิกคูปองนี้ใช่หรือไม่?')) return
  
  try {
    const response = await api.post(`/api/coupons/${result.value?.coupon_id}/cancel`)
    if (response.success) {
      toast.success('ยกเลิกคูปองสำเร็จ!')
      result.value = {
        success: false,
        message: 'คูปองถูกยกเลิกแล้ว',
      }
    } else {
      toast.error(response.message || 'ไม่สามารถยกเลิกคูปองได้')
    }
  } catch (error) {
    console.error('Error canceling coupon:', error)
    toast.error('ไม่สามารถยกเลิกคูปองได้')
  }
}

// Format number
const formatNumber = (num: number): string => {
  return num.toLocaleString()
}

// Cleanup on unmount
onUnmounted(() => {
  stopScanning()
})
</script>

<template>
  <div class="container mx-auto px-4 py-6 max-w-lg">
    <!-- Back Button -->
    <NuxtLink 
      to="/earn/coupons"
      class="inline-flex items-center gap-2 text-gray-500 dark:text-gray-400 hover:text-vikinger-purple dark:hover:text-vikinger-cyan mb-6 transition-colors"
    >
      <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
      กลับไปหน้าคูปอง
    </NuxtLink>

    <!-- Result State -->
    <div v-if="result" class="vikinger-card !p-0 overflow-hidden">
      <!-- Result Header -->
      <div 
        :class="[
          'p-6 text-center',
          result.success 
            ? 'bg-gradient-to-r from-green-500 to-emerald-500' 
            : 'bg-gradient-to-r from-red-500 to-rose-500'
        ]"
      >
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-white/20 flex items-center justify-center">
          <Icon 
            :icon="result.success ? 'fluent:checkmark-circle-24-filled' : 'fluent:dismiss-circle-24-filled'" 
            class="w-10 h-10 text-white" 
          />
        </div>
        <h2 class="text-2xl font-black text-white">
          {{ result.success ? 'รับคูปองสำเร็จ!' : 'ไม่สำเร็จ' }}
        </h2>
        <p class="text-white/80 mt-1">{{ result.message }}</p>
      </div>
      
      <!-- Result Details -->
      <div class="p-6" v-if="result.success">
        <div class="grid grid-cols-2 gap-4 mb-6">
          <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">ประเภท</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
              {{ result.type === 'points' ? '🎯 แต้ม' : '💰 เงิน' }}
            </p>
          </div>
          <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">ได้รับ</p>
            <p class="text-lg font-bold text-green-600 dark:text-green-400 mt-1">
              +{{ result.type === 'wallet' ? '฿' : '' }}{{ formatNumber(result.amount) }}
              {{ result.type === 'points' ? 'แต้ม' : '' }}
            </p>
          </div>
        </div>
        
        <div class="bg-gradient-to-r from-vikinger-purple/10 to-vikinger-cyan/10 rounded-xl p-4 text-center mb-6">
          <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            ยอด{{ result.type === 'points' ? 'แต้ม' : 'เงิน' }}ใหม่
          </p>
          <p class="text-2xl font-black text-vikinger-purple dark:text-vikinger-cyan mt-1">
            {{ result.type === 'wallet' ? '฿' : '' }}{{ formatNumber(result.new_balance) }}
            {{ result.type === 'points' ? 'แต้ม' : '' }}
          </p>
        </div>
      </div>
      
      <!-- Actions -->
      <div class="p-6 pt-0">
        <button 
          @click="reset"
          class="w-full py-3 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-xl font-bold hover:opacity-90 transition-all flex items-center justify-center gap-2"
        >
          <Icon icon="fluent:qr-code-24-regular" class="w-5 h-5" />
          รับคูปองอีก
        </button>
        
        <!-- Cancel Button -->
        <button 
          v-if="result && result.success"
          @click="cancelCoupon"
          class="w-full mt-3 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-bold hover:bg-gray-300 dark:hover:bg-gray-600 transition-all flex items-center justify-center gap-2"
        >
          <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
          กลับหน้าคูปอง
        </button>
      </div>
    </div>

    <!-- Redeem Form -->
    <div v-else class="vikinger-card !p-0 overflow-hidden">
      <!-- Header -->
      <div class="bg-gradient-to-r from-vikinger-purple to-vikinger-cyan p-6">
        <h1 class="text-2xl font-black text-white flex items-center gap-3">
          <Icon icon="fluent:qr-code-24-filled" class="w-8 h-8" />
          รับคูปอง
        </h1>
        <p class="text-white/80 mt-1">สแกน QR Code หรือป้อนรหัสคูปอง</p>
      </div>
      
      <div class="p-6">
        <!-- Method Selector -->
        <div class="grid grid-cols-2 gap-3 mb-6">
          <button
            @click="method = 'scan'; stopScanning()"
            :class="[
              'p-4 rounded-xl border-2 transition-all flex flex-col items-center gap-2',
              method === 'scan'
                ? 'border-vikinger-purple bg-vikinger-purple/10'
                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
            ]"
          >
            <Icon icon="fluent:camera-24-regular" class="w-8 h-8 text-vikinger-purple" />
            <span class="font-bold text-gray-900 dark:text-white text-sm">สแกน QR Code</span>
          </button>
          
          <button
            @click="method = 'code'; stopScanning()"
            :class="[
              'p-4 rounded-xl border-2 transition-all flex flex-col items-center gap-2',
              method === 'code'
                ? 'border-vikinger-cyan bg-vikinger-cyan/10'
                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
            ]"
          >
            <Icon icon="fluent:keyboard-24-regular" class="w-8 h-8 text-vikinger-cyan" />
            <span class="font-bold text-gray-900 dark:text-white text-sm">ป้อนรหัส</span>
          </button>
        </div>
        
        <!-- Scan Section -->
        <div v-if="method === 'scan'" class="mb-6">
          <div class="relative aspect-square bg-gray-900 rounded-xl overflow-hidden">
            <video 
              ref="videoRef" 
              class="w-full h-full object-cover"
              autoplay 
              playsinline
            ></video>
            <canvas ref="canvasRef" class="hidden"></canvas>
            
            <!-- Overlay when not scanning -->
            <div 
              v-if="!isScanning" 
              class="absolute inset-0 bg-gray-900/80 flex flex-col items-center justify-center cursor-pointer"
              @click="toggleScanning"
            >
              <Icon icon="fluent:camera-24-regular" class="w-16 h-16 text-gray-400 mb-4" />
              <p class="text-gray-400">คลิกเพื่อเริ่มสแกน</p>
            </div>
            
            <!-- Scan line animation -->
            <div v-if="isScanning" class="absolute inset-x-0 top-0 h-1 bg-vikinger-cyan animate-scan"></div>
            
            <!-- Scan frame -->
            <div v-if="isScanning" class="absolute inset-8 border-2 border-vikinger-cyan rounded-xl pointer-events-none">
              <div class="absolute -top-1 -left-1 w-8 h-8 border-t-4 border-l-4 border-vikinger-cyan rounded-tl-lg"></div>
              <div class="absolute -top-1 -right-1 w-8 h-8 border-t-4 border-r-4 border-vikinger-cyan rounded-tr-lg"></div>
              <div class="absolute -bottom-1 -left-1 w-8 h-8 border-b-4 border-l-4 border-vikinger-cyan rounded-bl-lg"></div>
              <div class="absolute -bottom-1 -right-1 w-8 h-8 border-b-4 border-r-4 border-vikinger-cyan rounded-br-lg"></div>
            </div>
          </div>
          
          <button 
            @click="toggleScanning"
            :class="[
              'w-full mt-4 py-3 rounded-xl font-bold transition-all flex items-center justify-center gap-2',
              isScanning
                ? 'bg-red-500 text-white hover:bg-red-600'
                : 'bg-vikinger-purple text-white hover:opacity-90'
            ]"
          >
            <Icon :icon="isScanning ? 'fluent:stop-24-regular' : 'fluent:camera-24-regular'" class="w-5 h-5" />
            {{ isScanning ? 'หยุดสแกน' : 'เริ่มสแกน' }}
          </button>
        </div>
        
        <!-- Code Input Section -->
        <div v-if="method === 'code'" class="mb-6">
          <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block">
            รหัสคูปอง (8 หลัก)
          </label>
          <div class="flex gap-2">
            <input
              v-model="couponCode"
              type="text"
              inputmode="numeric"
              pattern="[0-9]*"
              maxlength="8"
              placeholder="00000000"
              class="flex-1 px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-lg font-mono font-bold tracking-widest focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
              @input="couponCode = couponCode.replace(/\D/g, '')"
              @keyup.enter="redeemCoupon"
              :disabled="isLoading"
            />
            <button 
              @click="pasteCode"
              class="px-4 py-3 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all"
              title="วางจากคลิปบอร์ด"
            >
              <Icon icon="fluent:clipboard-paste-24-regular" class="w-6 h-6" />
            </button>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            ป้อนรหัสตัวเลข 8 หลักที่ได้รับจากคูปอง
          </p>
        </div>
        
        <!-- Redeem Button -->
        <button
          @click="redeemCoupon"
          :disabled="!couponCode || isLoading"
          :class="[
            'w-full py-4 rounded-xl font-bold text-lg transition-all flex items-center justify-center gap-2',
            couponCode && !isLoading
              ? 'bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white shadow-lg hover:opacity-90'
              : 'bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed'
          ]"
        >
          <Icon v-if="isLoading" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
          <Icon v-else icon="fluent:gift-24-regular" class="w-5 h-5" />
          {{ isLoading ? 'กำลังตรวจสอบ...' : 'รับคูปอง' }}
        </button>
        
        <!-- Info -->
        <div class="mt-6 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
          <div class="flex gap-3">
            <Icon icon="fluent:info-24-regular" class="w-5 h-5 text-gray-500 flex-shrink-0 mt-0.5" />
            <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
              <p>• คูปองสามารถใช้งานได้เพียง 1 ครั้ง</p>
              <p>• ไม่สามารถใช้คูปองของตัวเองได้</p>
              <p>• ตรวจสอบวันหมดอายุก่อนใช้งาน</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes scan {
  0% { transform: translateY(0); }
  50% { transform: translateY(calc(100% - 4px)); }
  100% { transform: translateY(0); }
}

.animate-scan {
  animation: scan 2s ease-in-out infinite;
}
</style>
