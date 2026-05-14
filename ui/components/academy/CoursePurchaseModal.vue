<template>
  <TransitionRoot appear :show="visible" as="template">
    <Dialog as="div" @close="$emit('close')" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-3xl bg-white dark:bg-slate-800 p-6 text-left align-middle shadow-xl transition-all border border-slate-200 dark:border-slate-700">
              <!-- Step 1: Summary -->
              <div v-if="step === 1">
                <DialogTitle as="h3" class="text-xl font-black text-slate-900 dark:text-white mb-4">
                  ยืนยันการซื้อลิขสิทธิ์ต้นฉบับ
                </DialogTitle>
                
                <div class="bg-slate-50 dark:bg-slate-900 rounded-2xl p-4 mb-6 flex gap-4">
                  <img :src="course.cover || `${config.public.apiBase}/storage/images/courses/covers/default_cover.jpg`" class="w-20 h-20 rounded-xl object-cover" />
                  <div>
                    <h4 class="font-bold text-slate-800 dark:text-white text-sm line-clamp-2">{{ course.name }}</h4>
                    <p class="text-xs text-slate-500 mt-1">เจ้าของลิขสิทธิ์: {{ course.user?.name }}</p>
                    <div class="flex gap-2 mt-2">
                      <span class="text-[10px] bg-primary-100 dark:bg-primary-900/30 text-primary-600 px-2 py-0.5 rounded font-bold">
                        คัดลอก {{ course.course_lessons_count || course.lessons_count }} บทเรียน
                      </span>
                    </div>
                  </div>
                </div>

                <div v-if="course.is_owned" class="mb-4 p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 text-xs flex items-start gap-2 border border-amber-100 dark:border-amber-900/30">
                  <Icon icon="mdi:information" class="w-4 h-4 flex-shrink-0" />
                  <span>คุณมี Master Copy นี้อยู่แล้ว ต้องการซื้อเพิ่มอีกชุดหรือไม่?</span>
                </div>

                <div v-if="checking" class="py-12 flex flex-col items-center justify-center gap-3">
                  <Icon icon="svg-spinners:ring-resize" class="w-8 h-8 text-primary-600" />
                  <span class="text-sm text-slate-500">กำลังตรวจสอบข้อมูลราคา...</span>
                </div>

                <div v-else-if="checkResult" class="space-y-3 mb-6">
                  <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500">ราคาลิขสิทธิ์ (THB)</span>
                    <span class="font-black text-primary-600 text-lg">฿ {{ formatNumber(checkResult.price_thb) }}</span>
                  </div>
                  <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500">ราคาลิขสิทธิ์ (Points)</span>
                    <span class="font-bold text-amber-600">{{ formatNumber(checkResult.price_points) }} P</span>
                  </div>
                  <div class="pt-2 border-t border-slate-100 dark:border-slate-700">
                    <p class="text-[10px] text-slate-400 text-center uppercase tracking-wider font-bold">
                      อัตราแลกเปลี่ยน 1 THB = {{ formatNumber(checkResult.exchange_rate) }} Points
                    </p>
                  </div>
                </div>

                <div class="flex gap-3">
                  <button @click="$emit('close')" class="flex-1 px-4 py-3 rounded-xl font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    ยกเลิก
                  </button>
                  <button @click="nextStep" :disabled="checking" class="flex-2 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white font-bold px-8 py-3 rounded-xl transition-all shadow-lg shadow-primary-500/30">
                    ยืนยันคำสั่งซื้อ
                  </button>
                </div>
              </div>

              <!-- Step 2: Payment Method -->
              <div v-if="step === 2">
                <DialogTitle as="h3" class="text-xl font-black text-slate-900 dark:text-white mb-6">
                  เลือกวิธีชำระเงิน
                </DialogTitle>

                <div class="space-y-3 mb-8">
                  <!-- Wallet Option -->
                  <button 
                    @click="paymentMode = 'wallet'"
                    :disabled="!checkResult?.can_pay.wallet"
                    class="w-full p-4 rounded-2xl border-2 transition-all text-left flex items-center justify-between group disabled:opacity-50 disabled:grayscale"
                    :class="paymentMode === 'wallet' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/10' : 'border-slate-100 dark:border-slate-700 hover:border-slate-200'"
                  >
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600">
                        <Icon icon="fluent:wallet-24-filled" class="w-6 h-6" />
                      </div>
                      <div>
                        <div class="font-bold text-slate-800 dark:text-white">ชำระด้วย Wallet</div>
                        <div class="text-[10px] text-slate-500 uppercase font-bold tracking-tighter">คงเหลือ: ฿ {{ formatNumber(checkResult?.balance.wallet) }}</div>
                      </div>
                    </div>
                    <div class="text-right">
                      <div class="font-black text-primary-600">฿ {{ formatNumber(checkResult?.price_thb) }}</div>
                      <div v-if="!checkResult?.can_pay.wallet" class="text-[10px] text-red-500 font-bold">ยอดเงินไม่พอ</div>
                    </div>
                  </button>

                  <!-- Points Option -->
                  <button 
                    @click="paymentMode = 'points'"
                    :disabled="!checkResult?.can_pay.points"
                    class="w-full p-4 rounded-2xl border-2 transition-all text-left flex items-center justify-between group disabled:opacity-50 disabled:grayscale"
                    :class="paymentMode === 'points' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/10' : 'border-slate-100 dark:border-slate-700 hover:border-slate-200'"
                  >
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600">
                        <Icon icon="fluent:star-24-filled" class="w-6 h-6" />
                      </div>
                      <div>
                        <div class="font-bold text-slate-800 dark:text-white">ชำระด้วยแต้มสะสม</div>
                        <div class="text-[10px] text-slate-500 uppercase font-bold tracking-tighter">คงเหลือ: {{ formatNumber(checkResult?.balance.points) }} P</div>
                      </div>
                    </div>
                    <div class="text-right">
                      <div class="font-black text-amber-600">{{ formatNumber(checkResult?.price_points) }} P</div>
                      <div v-if="!checkResult?.can_pay.points" class="text-[10px] text-red-500 font-bold">แต้มไม่พอ</div>
                    </div>
                  </button>

                  <!-- Mixed Option -->
                  <button 
                    v-if="checkResult?.can_pay.mixed"
                    @click="paymentMode = 'mixed'"
                    class="w-full p-4 rounded-2xl border-2 transition-all text-left flex items-center justify-between group"
                    :class="paymentMode === 'mixed' ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/10' : 'border-slate-100 dark:border-slate-700 hover:border-slate-200'"
                  >
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-violet-600">
                        <Icon icon="fluent:money-hand-24-filled" class="w-6 h-6" />
                      </div>
                      <div>
                        <div class="font-bold text-slate-800 dark:text-white">Mixed: Wallet + Points</div>
                        <div class="text-[10px] text-slate-500 font-medium">ใช้ Wallet ทั้งหมดที่มี แล้วจ่ายส่วนต่างด้วยแต้ม</div>
                      </div>
                    </div>
                    <div class="text-right flex flex-col items-end">
                      <div class="font-black text-slate-700 dark:text-slate-200 text-xs">฿ {{ formatNumber(checkResult?.mixed_breakdown.wallet_portion) }}</div>
                      <div class="font-black text-violet-600">+ {{ formatNumber(checkResult?.mixed_breakdown.points_portion) }} P</div>
                    </div>
                  </button>
                </div>

                <div v-if="error" class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm flex items-start gap-2 border border-red-100 dark:border-red-900/30">
                  <Icon icon="mdi:alert-circle" class="w-5 h-5 flex-shrink-0" />
                  <span>{{ error }}</span>
                </div>

                <div class="flex gap-3">
                  <button @click="step = 1" class="p-3 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-500 hover:text-slate-700 transition-colors">
                    <Icon icon="mdi:arrow-left" class="w-6 h-6" />
                  </button>
                  <button 
                    @click="handlePurchase" 
                    :disabled="!paymentMode || loading"
                    class="flex-1 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-primary-500/30 flex items-center justify-center gap-2"
                  >
                    <Icon v-if="loading" icon="mdi:loading" class="w-5 h-5 animate-spin" />
                    ยืนยันการชำระเงิน
                  </button>
                </div>
              </div>

              <!-- Step 3: Success -->
              <div v-if="step === 3" class="text-center py-6">
                <div 
                  class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6" 
                  :class="isQueued ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600' : 'bg-green-100 dark:bg-green-900/30 text-green-600'"
                >
                  <Icon :icon="isQueued ? 'mdi:clock-fast' : 'mdi:check-bold'" class="w-10 h-10" />
                </div>
                <DialogTitle as="h3" class="text-2xl font-black text-slate-900 dark:text-white mb-2">
                  {{ isQueued ? 'กำลังดำเนินการ...' : 'ซื้อสำเร็จ!' }}
                </DialogTitle>
                <p class="text-slate-500 mb-8">
                  {{ isQueued 
                    ? 'เนื่องจากวิชานี้มีเนื้อหาจำนวนมาก ระบบกำลังทำการคัดลอกข้อมูลให้คุณ คุณจะได้รับแจ้งเตือนเมื่อดำเนินการเสร็จสิ้น' 
                    : 'วิชาถูกเพิ่มในคลังของคุณแล้ว คุณสามารถเริ่มจัดการเนื้อหาได้ทันที' 
                  }}
                </p>
                <div class="flex flex-col gap-3">
                  <button v-if="!isQueued" @click="goToCourse" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-primary-500/30">
                    ไปที่รายวิชาใหม่
                  </button>
                  <button @click="$emit('close')" class="w-full py-3 text-slate-500 font-bold hover:text-slate-700 transition-colors">
                    {{ isQueued ? 'ตกลง' : 'กลับหน้า Marketplace' }}
                  </button>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
  DialogTitle,
} from '@headlessui/vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  course: any
  visible: boolean
}>()

const emit = defineEmits(['close', 'success'])

const { user, refreshUser } = useAuth()
const api = useApi()
const config = useRuntimeConfig()

const step = ref(1)
const paymentMode = ref('')
const loading = ref(false)
const error = ref('')
const newCourseId = ref(null)
const isQueued = ref(false)
const checkResult = ref(null)
const checking = ref(false)

const fetchCheckResult = async () => {
  checking.value = true
  try {
    const response = await api.get(`/api/courses/${props.course.id}/purchase/check`)
    checkResult.value = response
  } catch (err) {
    console.error('Failed to check purchase:', err)
  } finally {
    checking.value = false
  }
}

watch(() => props.visible, (val) => {
  if (val) {
    step.value = 1
    paymentMode.value = ''
    error.value = ''
    newCourseId.value = null
    isQueued.value = false
    loading.value = false
    fetchCheckResult()
  }
})

const nextStep = () => {
  if (checkResult.value?.is_free) {
    paymentMode.value = 'wallet' // Default for free
    handlePurchase()
  } else {
    step.value = 2
  }
}

const handlePurchase = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await api.post(`/api/courses/${props.course.id}/purchase`, {
      payment_mode: paymentMode.value
    })
    
    newCourseId.value = response.new_course_id
    isQueued.value = response.is_queued
    step.value = 3
    
    // Refresh user balance
    await refreshUser()
    
    emit('success', response)
  } catch (err: any) {
    error.value = err.data?.message || 'เกิดข้อผิดพลาดในการสั่งซื้อ'
  } finally {
    loading.value = false
  }
}

const goToCourse = () => {
  // Use Nuxt router to navigate
  navigateTo(`/Learn/Courses/${newCourseId.value}/settings`)
  emit('close')
}

const formatNumber = (num: number) => {
  return new Intl.NumberFormat().format(num)
}
</script>
