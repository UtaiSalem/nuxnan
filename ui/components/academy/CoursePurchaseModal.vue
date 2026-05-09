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
                  ยืนยันการซื้อรายวิชา
                </DialogTitle>
                
                <div class="bg-slate-50 dark:bg-slate-900 rounded-2xl p-4 mb-6 flex gap-4">
                  <img :src="course.cover_url || '/images/course-placeholder.jpg'" class="w-20 h-20 rounded-xl object-cover" />
                  <div>
                    <h4 class="font-bold text-slate-800 dark:text-white text-sm line-clamp-2">{{ course.name }}</h4>
                    <p class="text-xs text-slate-500 mt-1">ผู้ขาย: {{ course.user?.name }}</p>
                    <div class="flex gap-2 mt-2">
                      <span class="text-[10px] bg-primary-100 dark:bg-primary-900/30 text-primary-600 px-2 py-0.5 rounded font-bold">
                        {{ course.course_lessons_count }} บทเรียน
                      </span>
                    </div>
                  </div>
                </div>

                <div class="space-y-3 mb-6">
                  <div class="flex justify-between text-sm">
                    <span class="text-slate-500">ราคาสุทธิ</span>
                    <div class="flex flex-col items-end">
                      <span v-if="course.price_type === 'points' || course.price_type === 'both'" class="font-bold text-amber-600">
                        {{ formatNumber(course.price_points) }} แต้ม
                      </span>
                      <span v-if="course.price_type === 'wallet' || course.price_type === 'both'" class="font-bold text-primary-600">
                        ฿ {{ formatNumber(course.price) }}
                      </span>
                      <span v-if="course.price_type === 'free'" class="font-bold text-green-600">FREE</span>
                    </div>
                  </div>
                </div>

                <div class="flex gap-3">
                  <button @click="$emit('close')" class="flex-1 px-4 py-3 rounded-xl font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    ยกเลิก
                  </button>
                  <button @click="nextStep" class="flex-2 bg-primary-600 hover:bg-primary-700 text-white font-bold px-8 py-3 rounded-xl transition-all shadow-lg shadow-primary-500/30">
                    ดำเนินการต่อ
                  </button>
                </div>
              </div>

              <!-- Step 2: Payment Method -->
              <div v-if="step === 2">
                <DialogTitle as="h3" class="text-xl font-black text-slate-900 dark:text-white mb-6">
                  เลือกวิธีชำระเงิน
                </DialogTitle>

                <div class="space-y-4 mb-8">
                  <!-- Points Option -->
                  <button 
                    v-if="course.price_type === 'points' || course.price_type === 'both'"
                    @click="paymentMode = 'points'"
                    class="w-full p-4 rounded-2xl border-2 transition-all text-left flex items-center justify-between"
                    :class="paymentMode === 'points' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/10' : 'border-slate-100 dark:border-slate-700 hover:border-slate-200'"
                  >
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600">
                        <Icon icon="mdi:database" class="w-6 h-6" />
                      </div>
                      <div>
                        <div class="font-bold text-slate-800 dark:text-white">ชำระด้วยแต้ม</div>
                        <div class="text-xs text-slate-500">คงเหลือ: {{ formatNumber(user?.pp || 0) }} P</div>
                      </div>
                    </div>
                    <div class="text-right">
                      <div class="font-black text-amber-600">{{ formatNumber(course.price_points) }} P</div>
                    </div>
                  </button>

                  <!-- Wallet Option -->
                  <button 
                    v-if="course.price_type === 'wallet' || course.price_type === 'both'"
                    @click="paymentMode = 'wallet'"
                    class="w-full p-4 rounded-2xl border-2 transition-all text-left flex items-center justify-between"
                    :class="paymentMode === 'wallet' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/10' : 'border-slate-100 dark:border-slate-700 hover:border-slate-200'"
                  >
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600">
                        <Icon icon="mdi:wallet" class="w-6 h-6" />
                      </div>
                      <div>
                        <div class="font-bold text-slate-800 dark:text-white">ชำระด้วยเงิน (Wallet)</div>
                        <div class="text-xs text-slate-500">คงเหลือ: ฿ {{ formatNumber(user?.wallet || 0) }}</div>
                      </div>
                    </div>
                    <div class="text-right">
                      <div class="font-black text-primary-600">฿ {{ formatNumber(course.price) }}</div>
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
const { api } = useApi()

const step = ref(1)
const paymentMode = ref('')
const loading = ref(false)
const error = ref('')
const newCourseId = ref(null)
const isQueued = ref(false)

watch(() => props.visible, (val) => {
  if (val) {
    step.value = 1
    paymentMode.value = ''
    error.value = ''
    newCourseId.value = null
    isQueued.value = false
    loading.value = false
  }
})

const nextStep = () => {
  if (props.course.price_type === 'free') {
    paymentMode.value = 'auto'
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
    
    newCourseId.value = response.data.new_course_id
    isQueued.value = response.data.is_queued
    step.value = 3
    
    // Refresh user balance
    await refreshUser()
    
    emit('success', response)
  } catch (err: any) {
    error.value = err.response?.data?.message || 'เกิดข้อผิดพลาดในการสั่งซื้อ'
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
