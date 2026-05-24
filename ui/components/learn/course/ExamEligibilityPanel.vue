<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

const props = defineProps<{
  courseId: string | number
  canTakeExam: boolean
  eligibility: any
}>()

const emit = defineEmits(['unlocked'])

const api = useApi()
const isProcessing = ref(false)

// Unlock methods
const requestSelfUnlock = async () => {
  const result = await Swal.fire({
    title: 'ยืนยันการปลดล็อค?',
    text: 'คุณต้องการใช้สิทธิ์ปลดล็อคสิทธิ์สอบด้วยตนเองใช่หรือไม่?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'ยืนยัน',
    cancelButtonText: 'ยกเลิก'
  })

  if (result.isConfirmed) {
    isProcessing.value = true
    try {
      const res: any = await api.post(`/api/courses/${props.courseId}/eligibility/unlock/self`)
      if (res.success) {
        Swal.fire('สำเร็จ', 'ปลดล็อคสิทธิ์สอบเรียบร้อยแล้ว', 'success')
        emit('unlocked')
      }
    } catch (err: any) {
      Swal.fire('ผิดพลาด', err.data?.message || 'ไม่สามารถปลดล็อคได้', 'error')
    } finally {
      isProcessing.value = false
    }
  }
}

const requestPointsUnlock = async (cost: number) => {
  const result = await Swal.fire({
    title: 'ปลดล็อคด้วยแต้มสะสม?',
    text: `คุณต้องใช้ ${cost} แต้ม เพื่อคืนสิทธิ์สอบวิชานี้`,
    icon: 'info',
    showCancelButton: true,
    confirmButtonText: 'ใช้แต้มปลดล็อค',
    cancelButtonText: 'ยกเลิก'
  })

  if (result.isConfirmed) {
    isProcessing.value = true
    try {
      const res: any = await api.post(`/api/courses/${props.courseId}/eligibility/unlock/points`)
      if (res.success) {
        Swal.fire('สำเร็จ', 'ปลดล็อคสิทธิ์สอบเรียบร้อยแล้ว', 'success')
        emit('unlocked')
      }
    } catch (err: any) {
      Swal.fire('ผิดพลาด', err.data?.message || 'แต้มไม่เพียงพอ หรือเกิดข้อผิดพลาด', 'error')
    } finally {
      isProcessing.value = false
    }
  }
}

const requestReadingUnlock = async () => {
  isProcessing.value = true
  try {
    const res: any = await api.post(`/api/courses/${props.courseId}/eligibility/unlock/reading`)
    if (res.success) {
      Swal.fire({
        title: 'เริ่มการปลดล็อค',
        text: 'ระบบเริ่มนับความก้าวหน้าการอ่านเพื่อคืนสิทธิ์สอบแล้ว กรุณาเข้าอ่านบทเรียนให้ครบตามกำหนด',
        icon: 'success'
      })
      emit('unlocked')
    }
  } catch (err: any) {
    Swal.fire('ผิดพลาด', err.data?.message || 'เกิดข้อผิดพลาด', 'error')
  } finally {
    isProcessing.value = false
  }
}

const requestAppealUnlock = async () => {
  const { value: reason } = await Swal.fire({
    title: 'อุทธรณ์สิทธิ์สอบ',
    input: 'textarea',
    inputLabel: 'ระบุเหตุผลที่ต้องการขอคืนสิทธิ์สอบ',
    inputPlaceholder: 'เช่น มีใบรับรองแพทย์ หรือมีความจำเป็นส่วนตัว...',
    inputAttributes: {
      'aria-label': 'เหตุผลอุทธรณ์'
    },
    showCancelButton: true,
    confirmButtonText: 'ส่งคำขอ',
    cancelButtonText: 'ยกเลิก',
    inputValidator: (value) => {
      if (!value) {
        return 'กรุณาระบุเหตุผล'
      }
    }
  })

  if (reason) {
    isProcessing.value = true
    try {
      const res: any = await api.post(`/api/courses/${props.courseId}/eligibility/unlock/appeal`, { reason })
      if (res.success) {
        Swal.fire('ส่งคำขอเรียบร้อย', 'กรุณารอผู้สอนพิจารณาคำขอของคุณ', 'success')
        emit('unlocked')
      }
    } catch (err: any) {
      Swal.fire('ผิดพลาด', err.data?.message || 'เกิดข้อผิดพลาด', 'error')
    } finally {
      isProcessing.value = false
    }
  }
}

const goToLessons = () => {
  navigateTo(`/courses/${props.courseId}/members/me?tab=lessons`)
}

// Helpers
const getMethodIcon = (method: string) => {
  switch (method) {
    case 'self': return 'fluent:person-available-24-filled'
    case 'points': return 'fluent:star-24-filled'
    case 'reading': return 'fluent:book-open-24-filled'
    case 'appeal': return 'fluent:chat-warning-24-filled'
    case 'admin': return 'fluent:person-support-24-filled'
    case 'remediation': return 'fluent:arrow-sync-24-filled'
    default: return 'fluent:unlock-24-filled'
  }
}

const getMethodColor = (method: string) => {
  switch (method) {
    case 'self': return 'bg-green-500 hover:bg-green-600'
    case 'points': return 'bg-yellow-500 hover:bg-yellow-600'
    case 'reading': return 'bg-blue-500 hover:bg-blue-600'
    case 'appeal': return 'bg-purple-500 hover:bg-purple-600'
    case 'remediation': return 'bg-orange-500 hover:bg-orange-600'
    default: return 'bg-gray-600 hover:bg-gray-700'
  }
}
</script>

<template>
  <div class="bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-900/30 rounded-2xl p-6 sm:p-8">
    <div class="flex items-start gap-4 mb-6">
      <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0">
        <Icon icon="fluent:warning-24-filled" class="w-7 h-7" />
      </div>
      <div>
        <h2 class="text-xl font-bold text-red-900 dark:text-red-400 mb-1">สิทธิ์สอบถูกระงับ</h2>
        <p class="text-red-700 dark:text-red-300/80">
          คุณไม่มีสิทธิ์ทำแบบทดสอบนี้เนื่องจาก:
          <span class="font-bold underline">{{ eligibility?.reason || eligibility?.reasons?.join(', ') }}</span>
        </p>
      </div>
    </div>

    <!-- Pending Request Info -->
    <div v-if="eligibility?.pending_override" class="mb-8 p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-900/30 rounded-xl">
      <div class="flex items-center gap-3 text-orange-800 dark:text-orange-400 font-bold mb-2">
        <Icon icon="svg-spinners:ring-resize" class="w-5 h-5" />
        อยู่ระหว่างรอดำเนินการ
      </div>
      <p class="text-sm text-orange-700 dark:text-orange-300/80">
        คุณได้ส่งคำขอปลดล็อคด้วยวิธี <span class="font-bold">{{ eligibility.pending_override.method_label }}</span> 
        เมื่อวันที่ {{ new Date(eligibility.pending_override.created_at).toLocaleDateString('th-TH') }}
        กรุณารอการตรวจสอบหรือดำเนินการตามเงื่อนไขให้ครบถ้วน
      </p>
      <button 
        v-if="eligibility.pending_override.method === 'reading'"
        @click="goToLessons"
        class="mt-3 px-4 py-1.5 bg-orange-600 text-white text-sm font-bold rounded-lg hover:bg-orange-700 transition-colors"
      >
        ไปที่หน้าบทเรียน
      </button>
    </div>

    <!-- Unlock Options -->
    <div v-else>
      <h3 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">ช่องทางคืนสิทธิ์สอบ</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <template v-for="opt in eligibility?.unlock_options" :key="opt.method">
          <!-- Self Unlock -->
          <button 
            v-if="opt.method === 'self'"
            @click="requestSelfUnlock"
            :disabled="isProcessing"
            :class="getMethodColor('self')"
            class="flex items-center gap-3 p-4 text-white rounded-xl font-bold transition-all hover:shadow-lg hover:-translate-y-0.5 disabled:opacity-50"
          >
            <Icon :icon="getMethodIcon('self')" class="w-6 h-6" />
            <div class="text-left text-sm leading-tight">
              <div>{{ opt.label }}</div>
              <div class="text-xs opacity-80 font-normal">คืนสิทธิ์ได้ทันที 1 ครั้ง</div>
            </div>
          </button>

          <!-- Points Unlock -->
          <button 
            v-if="opt.method === 'points'"
            @click="requestPointsUnlock(opt.cost)"
            :disabled="isProcessing"
            :class="getMethodColor('points')"
            class="flex items-center gap-3 p-4 text-white rounded-xl font-bold transition-all hover:shadow-lg hover:-translate-y-0.5 disabled:opacity-50"
          >
            <Icon :icon="getMethodIcon('points')" class="w-6 h-6" />
            <div class="text-left text-sm leading-tight">
              <div>{{ opt.label }}</div>
              <div class="text-xs opacity-80 font-normal">แลกแต้มเพื่อคืนสิทธิ์</div>
            </div>
          </button>

          <!-- Reading Unlock -->
          <button 
            v-if="opt.method === 'reading'"
            @click="requestReadingUnlock"
            :disabled="isProcessing"
            :class="getMethodColor('reading')"
            class="flex items-center gap-3 p-4 text-white rounded-xl font-bold transition-all hover:shadow-lg hover:-translate-y-0.5 disabled:opacity-50"
          >
            <Icon :icon="getMethodIcon('reading')" class="w-6 h-6" />
            <div class="text-left text-sm leading-tight">
              <div>{{ opt.label }}</div>
              <div class="text-xs opacity-80 font-normal">อ่านบทเรียนครบเพื่อคืนสิทธิ์</div>
            </div>
          </button>

          <!-- Appeal Unlock -->
          <button 
            v-if="opt.method === 'appeal'"
            @click="requestAppealUnlock"
            :disabled="isProcessing"
            :class="getMethodColor('appeal')"
            class="flex items-center gap-3 p-4 text-white rounded-xl font-bold transition-all hover:shadow-lg hover:-translate-y-0.5 disabled:opacity-50"
          >
            <Icon :icon="getMethodIcon('appeal')" class="w-6 h-6" />
            <div class="text-left text-sm leading-tight">
              <div>{{ opt.label }}</div>
              <div class="text-xs opacity-80 font-normal">ส่งคำร้องให้ผู้สอนพิจารณา</div>
            </div>
          </button>

          <!-- Admin Contact (Informational) -->
          <div 
            v-if="opt.method === 'admin'"
            class="flex items-center gap-3 p-4 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl font-bold border border-gray-200 dark:border-gray-700"
          >
            <Icon :icon="getMethodIcon('admin')" class="w-6 h-6 text-gray-400" />
            <div class="text-left text-sm leading-tight">
              <div>ติดต่อผู้สอน</div>
              <div class="text-xs opacity-80 font-normal">สอบถามรายละเอียดเพิ่มเติม</div>
            </div>
          </div>
        </template>

        <!-- Placeholder for Remediation (Future) -->
        <div v-if="eligibility?.has_remediation" class="sm:col-span-2">
            <NuxtLink 
                :to="`/courses/${courseId}/remediation`"
                class="mt-2 flex items-center justify-center gap-2 p-3 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded-xl font-bold border border-orange-200 dark:border-orange-800 hover:bg-orange-200 transition-colors"
            >
                <Icon icon="fluent:arrow-sync-24-filled" class="w-5 h-5" />
                ลงทะเบียนรอบสอบแก้ตัว (Remediation)
            </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>
