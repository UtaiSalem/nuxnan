<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  course: any
  courseGroups?: any[]
  isEnrolling?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  courseGroups: () => [],
  isEnrolling: false
})

const emit = defineEmits<{
  'request-member': [groupId?: number]
  'purchase-course': []
}>()

const hasMultipleGroups = computed(() => props.courseGroups.length > 1)
const tuitionFees = computed(() => Number(props.course?.tuition_fees ?? 0))
const isFree = computed(() => tuitionFees.value === 0)
const canPurchaseCopy = computed(() => Boolean(props.course?.is_for_marketplace))

function formatMoney(value: number) {
  return new Intl.NumberFormat('th-TH', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(value || 0)
}
</script>

<template>
  <div class="mt-4 mx-0 sm:mx-2 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/10 dark:to-indigo-900/10 border border-blue-200 dark:border-blue-800 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm overflow-hidden relative group">
    <!-- Background Sparkle Decoration -->
    <div class="absolute top-0 right-0 p-4 opacity-10 pointer-events-none transition-transform group-hover:scale-110">
      <Icon icon="heroicons:sparkles-solid" class="w-24 h-24 text-blue-600 dark:text-blue-400" />
    </div>

    <div class="relative flex flex-col md:flex-row items-center justify-between gap-6">
      <!-- Text Content -->
      <div class="flex items-center gap-4 sm:gap-6 w-full md:w-auto text-center md:text-left">
        <div class="hidden sm:flex flex-shrink-0 items-center justify-center w-14 h-14 bg-blue-600 rounded-2xl shadow-lg shadow-blue-600/20">
          <Icon icon="heroicons:academic-cap-20-solid" class="w-8 h-8 text-white" />
        </div>
        
        <div class="flex-1 min-w-0">
          <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 mb-1">
            <h3 class="text-lg sm:text-xl font-black text-gray-900 dark:text-white">
              สมัครเรียนเพื่อเข้าถึงเนื้อหาทั้งหมด
            </h3>
            <span v-if="isFree" class="px-2.5 py-0.5 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 text-xs font-bold rounded-full border border-green-200 dark:border-green-800">
              เรียนฟรี
            </span>
          </div>
          <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400 max-w-xl">
            เข้าถึงบทเรียน งาน แบบทดสอบ และติดตามความคืบหน้าของคุณในรายวิชานี้ได้อย่างเต็มรูปแบบ
          </p>
          <div v-if="!isFree" class="mt-2 flex items-center justify-center md:justify-start gap-1.5 text-blue-600 dark:text-blue-400 font-bold">
            <Icon icon="ri:bit-coin-fill" class="w-5 h-5" />
            <span>ค่าเรียน ฿{{ formatMoney(tuitionFees) }}</span>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
        <button
          v-if="canPurchaseCopy"
          type="button"
          @click="emit('purchase-course')"
          class="flex-1 sm:flex-none px-6 py-3 bg-white dark:bg-gray-800 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 font-bold transition-all active:scale-95 shadow-sm flex items-center justify-center gap-2"
        >
          <Icon icon="fluent:cart-24-filled" class="w-5 h-5" />
          <span>ซื้อรายวิชา</span>
        </button>

        <button
          type="button"
          @click="emit('request-member')"
          :disabled="isEnrolling"
          class="flex-1 sm:flex-none px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-blue-600/20 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
        >
          <Icon v-if="isEnrolling" icon="svg-spinners:ring-resize" class="w-5 h-5" />
          <Icon v-else icon="heroicons:user-plus-solid" class="w-5 h-5" />
          <span>{{ hasMultipleGroups ? 'เลือกกลุ่มเพื่อสมัคร' : 'สมัครสมาชิก' }}</span>
        </button>
      </div>
    </div>
    
    <!-- Group Hint -->
    <div v-if="hasMultipleGroups" class="mt-4 md:mt-2 text-center md:text-left text-xs text-blue-500 dark:text-blue-400 flex items-center justify-center md:justify-start gap-1.5">
      <Icon icon="heroicons:information-circle" class="w-4 h-4" />
      <span>รายวิชานี้มีหลายกลุ่มเรียน คุณสามารถเลือกกลุ่มที่ต้องการได้ก่อนสมัคร</span>
    </div>
  </div>
</template>
