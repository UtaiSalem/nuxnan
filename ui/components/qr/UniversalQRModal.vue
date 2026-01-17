<script setup lang="ts">
import { ref, watch, onUnmounted, computed } from 'vue'
import { Icon } from '@iconify/vue'
import { QR_TYPES, parseQRCode, type QRCodeType, type ParsedQRData, type QRActionResult } from '~/types/qr'

const props = defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits(['update:modelValue', 'action-complete'])

// Use composables
const { 
  isScanning, 
  isProcessing, 
  actionResult, 
  lastScannedData,
  startScanning, 
  stopScanning, 
  processQRCode,
  reset 
} = useQRScanner()

const toast = useToast()

// State
const mode = ref<'scan' | 'input'>('scan')
const manualCode = ref('')
const detectedType = ref<QRCodeType | null>(null)

// Refs for camera
const videoRef = ref<HTMLVideoElement | null>(null)
const canvasRef = ref<HTMLCanvasElement | null>(null)

// Close modal
const close = () => {
  stopScanning()
  reset()
  emit('update:modelValue', false)
}

// Start camera
const handleStartScan = async () => {
  if (!videoRef.value || !canvasRef.value) return
  
  try {
    await startScanning(videoRef.value, canvasRef.value)
  } catch (error) {
    mode.value = 'input' // Fallback to input mode
  }
}

// Toggle scanning
const toggleScanning = async () => {
  if (isScanning.value) {
    stopScanning()
  } else {
    await handleStartScan()
  }
}

// Process manual input
const handleManualSubmit = async () => {
  if (!manualCode.value.trim()) return
  
  await processQRCode(manualCode.value.trim())
  
  if (actionResult.value?.success) {
    emit('action-complete', actionResult.value)
  }
}

// Try again
const tryAgain = () => {
  reset()
  manualCode.value = ''
  detectedType.value = null
}

// Input ref for focus
const inputRef = ref<HTMLInputElement | null>(null)

// Paste code from clipboard
const pasteCode = async () => {
  // Try Clipboard API first
  if (navigator.clipboard && navigator.clipboard.readText) {
    try {
      const text = await navigator.clipboard.readText()
      if (text) {
        manualCode.value = text.trim()
        toast.success('วางรหัสแล้ว')
        return
      }
    } catch (e) {
      // Clipboard API failed, try fallback
    }
  }
  
  // Fallback: Focus input and prompt user to use Ctrl+V
  if (inputRef.value) {
    inputRef.value.focus()
    inputRef.value.select()
  }
  toast.info('กรุณากด Ctrl+V เพื่อวางรหัส')
}

// Detect type from manual input
const detectTypeFromInput = () => {
  if (!manualCode.value.trim()) {
    detectedType.value = null
    return
  }
  
  const parsed = parseQRCode(manualCode.value.trim())
  detectedType.value = parsed.isValid ? parsed.type : null
}

// Get type config
const currentTypeConfig = computed(() => {
  if (lastScannedData.value) {
    return lastScannedData.value.config
  }
  if (detectedType.value) {
    return QR_TYPES[detectedType.value]
  }
  return null
})

// Format result data for display
const formatResultData = (data: any): string => {
  if (!data) return ''
  
  if (data.amount !== undefined) {
    const unit = data.couponType === 'points' ? 'แต้ม' : 'บาท'
    return `+${data.amount.toLocaleString()} ${unit}`
  }
  
  return ''
}

// Watch for modal close
watch(() => props.modelValue, (newVal) => {
  if (!newVal) {
    stopScanning()
    setTimeout(() => {
      reset()
      manualCode.value = ''
      detectedType.value = null
      mode.value = 'scan'
    }, 300)
  }
})

// Watch manual input for type detection
watch(manualCode, detectTypeFromInput)

// Watch action result
watch(actionResult, (result) => {
  if (result) {
    if (result.success) {
      toast.success(result.message)
    } else {
      toast.error(result.message)
    }
    emit('action-complete', result)
  }
})

// Cleanup
onUnmounted(() => {
  stopScanning()
})
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div 
        v-if="modelValue" 
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
      >
        <!-- Backdrop -->
        <div 
          class="absolute inset-0 bg-black/60 backdrop-blur-sm"
          @click="close"
        ></div>
        
        <!-- Modal Content -->
        <div class="relative w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto">
          <!-- Close Button -->
          <button
            @click="close"
            class="absolute top-3 right-3 z-10 w-8 h-8 flex items-center justify-center rounded-full bg-black/20 hover:bg-black/40 text-white transition-colors"
          >
            <Icon icon="fluent:dismiss-24-filled" class="w-5 h-5" />
          </button>

          <!-- ========== Result State ========== -->
          <div v-if="actionResult">
            <!-- Success Result -->
            <template v-if="actionResult.success">
              <!-- Success Header -->
              <div class="p-6 text-center bg-gradient-to-r from-green-500 to-emerald-500">
                <!-- Type Icon -->
                <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-white/20 flex items-center justify-center">
                  <Icon 
                    :icon="currentTypeConfig?.icon || 'fluent:qr-code-24-filled'" 
                    class="w-8 h-8 text-white" 
                  />
                </div>
                
                <!-- Type Label -->
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 text-white text-sm mb-2">
                  <span>{{ currentTypeConfig?.label || 'QR Code' }}</span>
                </div>
                
                <h2 class="text-xl font-black text-white">สำเร็จ!</h2>
                <p class="text-white/80 text-sm mt-1">{{ actionResult.message }}</p>
              </div>
              
              <!-- Success Details -->
              <div class="p-5" v-if="actionResult.data">
                <!-- Coupon Result -->
                <div v-if="actionResult.type === 'coupon'" class="space-y-3">
                  <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3 text-center">
                      <p class="text-xs text-gray-500 uppercase">ประเภท</p>
                      <p class="text-base font-bold text-gray-900 dark:text-white mt-1">
                        {{ actionResult.data.couponType === 'points' ? '🎯 แต้ม' : '💰 เงิน' }}
                      </p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3 text-center">
                      <p class="text-xs text-gray-500 uppercase">ได้รับ</p>
                      <p class="text-base font-bold text-green-600 mt-1">
                        {{ formatResultData(actionResult.data) }}
                      </p>
                    </div>
                  </div>
                  
                  <div class="bg-gradient-to-r from-vikinger-purple/10 to-vikinger-cyan/10 rounded-xl p-3 text-center">
                    <p class="text-xs text-gray-500 uppercase">
                      ยอด{{ actionResult.data.couponType === 'points' ? 'แต้ม' : 'เงิน' }}ใหม่
                    </p>
                    <p class="text-xl font-black text-vikinger-purple dark:text-vikinger-cyan mt-1">
                      {{ actionResult.data.couponType === 'wallet' ? '฿' : '' }}
                      {{ actionResult.data.newBalance?.toLocaleString() }}
                      {{ actionResult.data.couponType === 'points' ? ' แต้ม' : '' }}
                    </p>
                  </div>
                </div>
                
                <!-- Check-in Result -->
                <div v-else-if="actionResult.type === 'checkin' || actionResult.type === 'event'" 
                     class="text-center">
                  <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-green-100 flex items-center justify-center">
                    <Icon icon="fluent:checkmark-circle-24-filled" class="w-12 h-12 text-green-500" />
                  </div>
                  <p class="text-gray-600 dark:text-gray-400">
                    {{ actionResult.data?.class_name || actionResult.data?.event_name || 'บันทึกเรียบร้อย' }}
                  </p>
                </div>
              </div>
            </template>

            <!-- Error Result -->
            <template v-else>
              <!-- Error Header - Minimal with illustration -->
              <div class="p-6 bg-gray-50 dark:bg-gray-800/50">
                <!-- Animated Error Illustration -->
                <div class="flex flex-col items-center text-center">
                  <!-- Error Icon with Animation -->
                  <div class="relative mb-4">
                    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-red-100 to-orange-100 dark:from-red-900/30 dark:to-orange-900/30 flex items-center justify-center">
                      <div class="w-16 h-16 rounded-full bg-gradient-to-br from-red-500 to-orange-500 flex items-center justify-center animate-pulse">
                        <Icon icon="fluent:warning-24-filled" class="w-8 h-8 text-white" />
                      </div>
                    </div>
                    <!-- Type Badge on Corner -->
                    <div 
                      v-if="currentTypeConfig"
                      class="absolute -bottom-1 -right-1 w-8 h-8 rounded-full flex items-center justify-center shadow-lg"
                      :class="currentTypeConfig.bgColor"
                    >
                      <Icon :icon="currentTypeConfig.icon" class="w-4 h-4" :class="currentTypeConfig.color" />
                    </div>
                  </div>
                  
                  <!-- Error Title -->
                  <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">
                    ไม่สามารถดำเนินการได้
                  </h2>
                  
                  <!-- Error Message Card -->
                  <div class="w-full mt-3 p-4 bg-white dark:bg-gray-800 rounded-xl border border-red-200 dark:border-red-800/50 shadow-sm">
                    <div class="flex items-start gap-3">
                      <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <Icon icon="fluent:info-24-filled" class="w-5 h-5 text-red-500" />
                      </div>
                      <div class="flex-1 text-left">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                          {{ actionResult.message }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                          กรุณาตรวจสอบและลองใหม่อีกครั้ง
                        </p>
                      </div>
                    </div>
                  </div>

                  <!-- Helpful Tips -->
                  <div class="w-full mt-4 text-left">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 font-medium">💡 คำแนะนำ:</p>
                    <ul class="space-y-1.5 text-xs text-gray-500 dark:text-gray-400">
                      <li class="flex items-start gap-2">
                        <Icon icon="fluent:checkmark-12-filled" class="w-3 h-3 mt-0.5 text-gray-400" />
                        <span>ตรวจสอบว่ารหัสถูกต้องครบถ้วน</span>
                      </li>
                      <li class="flex items-start gap-2">
                        <Icon icon="fluent:checkmark-12-filled" class="w-3 h-3 mt-0.5 text-gray-400" />
                        <span>คูปองแต่ละรหัสใช้ได้เพียงครั้งเดียว</span>
                      </li>
                      <li class="flex items-start gap-2">
                        <Icon icon="fluent:checkmark-12-filled" class="w-3 h-3 mt-0.5 text-gray-400" />
                        <span>ไม่สามารถใช้คูปองของตัวเองได้</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </template>
            
            <!-- Actions -->
            <div class="p-5 flex gap-2">
              <button 
                @click="tryAgain"
                class="flex-1 py-2.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-xl font-bold hover:opacity-90 transition-all flex items-center justify-center gap-2 text-sm"
              >
                <Icon icon="fluent:qr-code-24-regular" class="w-4 h-4" />
                สแกนอีก
              </button>
              <button 
                @click="close"
                class="flex-1 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-bold hover:bg-gray-300 dark:hover:bg-gray-600 transition-all text-sm"
              >
                ปิด
              </button>
            </div>
          </div>

          <!-- ========== Scanner Form ========== -->
          <div v-else>
            <!-- Header -->
            <div class="bg-gradient-to-r from-vikinger-purple to-vikinger-cyan p-5">
              <h1 class="text-xl font-black text-white flex items-center gap-2">
                <Icon icon="fluent:qr-code-24-filled" class="w-6 h-6" />
                สแกน QR Code
              </h1>
              <p class="text-white/80 text-sm mt-1">สแกนหรือป้อนรหัส QR</p>
            </div>
            
            <div class="p-5">
              <!-- Supported Types -->
              <div class="mb-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">รองรับ QR Code:</p>
                <div class="flex flex-wrap gap-1.5">
                  <div 
                    v-for="(config, key) in QR_TYPES" 
                    :key="key"
                    v-show="key !== 'unknown'"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs"
                    :class="[config.bgColor, config.color]"
                  >
                    <Icon :icon="config.icon" class="w-3 h-3" />
                    <span>{{ config.label }}</span>
                  </div>
                </div>
              </div>
              
              <!-- Mode Selector -->
              <div class="grid grid-cols-2 gap-2 mb-4">
                <button
                  @click="mode = 'scan'; stopScanning()"
                  :class="[
                    'p-3 rounded-xl border-2 transition-all flex flex-col items-center gap-1.5',
                    mode === 'scan'
                      ? 'border-vikinger-purple bg-vikinger-purple/10'
                      : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                  ]"
                >
                  <Icon icon="fluent:camera-24-regular" class="w-6 h-6 text-vikinger-purple" />
                  <span class="font-bold text-gray-900 dark:text-white text-xs">สแกนกล้อง</span>
                </button>
                
                <button
                  @click="mode = 'input'; stopScanning()"
                  :class="[
                    'p-3 rounded-xl border-2 transition-all flex flex-col items-center gap-1.5',
                    mode === 'input'
                      ? 'border-vikinger-cyan bg-vikinger-cyan/10'
                      : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                  ]"
                >
                  <Icon icon="fluent:keyboard-24-regular" class="w-6 h-6 text-vikinger-cyan" />
                  <span class="font-bold text-gray-900 dark:text-white text-xs">ป้อนรหัส</span>
                </button>
              </div>
              
              <!-- ===== Scan Mode ===== -->
              <div v-if="mode === 'scan'" class="mb-4">
                <div class="relative aspect-square bg-gray-900 rounded-xl overflow-hidden max-h-[240px]">
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
                    @click="handleStartScan"
                  >
                    <Icon icon="fluent:camera-24-regular" class="w-12 h-12 text-gray-400 mb-2" />
                    <p class="text-gray-400 text-sm">คลิกเพื่อเริ่มสแกน</p>
                  </div>
                  
                  <!-- Processing overlay -->
                  <div 
                    v-if="isProcessing" 
                    class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center"
                  >
                    <Icon icon="fluent:spinner-ios-20-regular" class="w-10 h-10 text-white animate-spin mb-2" />
                    <p class="text-white text-sm">กำลังประมวลผล...</p>
                  </div>
                  
                  <!-- Scan line animation -->
                  <div v-if="isScanning && !isProcessing" class="absolute inset-x-0 top-0 h-1 bg-vikinger-cyan animate-scan"></div>
                  
                  <!-- Scan frame -->
                  <div v-if="isScanning && !isProcessing" class="absolute inset-6 border-2 border-vikinger-cyan rounded-xl pointer-events-none">
                    <div class="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 border-vikinger-cyan rounded-tl-lg"></div>
                    <div class="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 border-vikinger-cyan rounded-tr-lg"></div>
                    <div class="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 border-vikinger-cyan rounded-bl-lg"></div>
                    <div class="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 border-vikinger-cyan rounded-br-lg"></div>
                  </div>
                </div>
                
                <button 
                  @click="toggleScanning"
                  :disabled="isProcessing"
                  :class="[
                    'w-full mt-3 py-2.5 rounded-xl font-bold transition-all flex items-center justify-center gap-2 text-sm',
                    isScanning
                      ? 'bg-red-500 text-white hover:bg-red-600'
                      : 'bg-vikinger-purple text-white hover:opacity-90',
                    isProcessing && 'opacity-50 cursor-not-allowed'
                  ]"
                >
                  <Icon :icon="isScanning ? 'fluent:stop-24-regular' : 'fluent:camera-24-regular'" class="w-4 h-4" />
                  {{ isScanning ? 'หยุดสแกน' : 'เริ่มสแกน' }}
                </button>
              </div>
              
              <!-- ===== Input Mode ===== -->
              <div v-if="mode === 'input'" class="mb-4">
                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5 block">
                  รหัส QR Code
                </label>
                
                <!-- Detected Type Indicator -->
                <div 
                  v-if="detectedType && currentTypeConfig"
                  class="mb-2 flex items-center gap-2 p-2 rounded-lg"
                  :class="currentTypeConfig.bgColor"
                >
                  <Icon :icon="currentTypeConfig.icon" class="w-4 h-4" :class="currentTypeConfig.color" />
                  <span class="text-xs font-medium" :class="currentTypeConfig.color">
                    {{ currentTypeConfig.label }}: {{ currentTypeConfig.description }}
                  </span>
                </div>
                
                <div class="flex gap-2">
                  <input
                    ref="inputRef"
                    v-model="manualCode"
                    type="text"
                    placeholder="COUPON:12345678 หรือ 12345678"
                    class="flex-1 px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
                    @keyup.enter="handleManualSubmit"
                    :disabled="isProcessing"
                  />
                  <button 
                    @click="pasteCode"
                    type="button"
                    class="px-3 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all"
                    title="วางจากคลิปบอร์ด"
                  >
                    <Icon icon="fluent:clipboard-paste-24-regular" class="w-5 h-5" />
                  </button>
                </div>
                
                <p class="text-xs text-gray-500 mt-1.5">
                  รูปแบบ: COUPON:รหัส, CHECKIN:ห้อง:รอบ, EVENT:รหัส, หรือรหัสตัวเลข 8 หลัก
                </p>
              </div>
              
              <!-- Submit Button -->
              <button
                v-if="mode === 'input'"
                @click="handleManualSubmit"
                :disabled="!manualCode.trim() || isProcessing"
                :class="[
                  'w-full py-3 rounded-xl font-bold text-base transition-all flex items-center justify-center gap-2',
                  manualCode.trim() && !isProcessing
                    ? 'bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white shadow-lg hover:opacity-90'
                    : 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'
                ]"
              >
                <Icon v-if="isProcessing" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
                <Icon v-else :icon="currentTypeConfig?.icon || 'fluent:send-24-regular'" class="w-4 h-4" />
                {{ isProcessing ? 'กำลังประมวลผล...' : 'ดำเนินการ' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
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

.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.9) translateY(20px);
}
</style>
