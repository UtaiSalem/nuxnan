<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

const props = defineProps({
  course: { type: Object, required: true },
  courseMemberOfAuth: { type: Object, default: null },
  variant: { type: String, default: 'standalone' }, // 'standalone' or 'hero'
})

const emit = defineEmits(['refresh', 'request-member', 'purchase-course'])

const api = useApi()
const isProcessing = ref(false)
const showPendingMenu = ref(false)

const memberStatus = computed(() => props.courseMemberOfAuth?.status)
const courseJoinPrice = computed(() => Number(props.course?.tuition_fees ?? 0))
const canPurchaseCopy = computed(() => Boolean(props.course?.is_for_marketplace))

const buttonClasses = computed(() => {
  if (props.variant === 'hero') {
    return 'h-10 px-4 text-sm'
  }
  return 'h-12 px-6'
})

async function handleAction() {
  if (isProcessing.value) return

  // Case: Not a member -> Start enrollment
  if (!props.courseMemberOfAuth) {
    emit('request-member')
    return
  }

  // Case: Active Member -> Leave course
  if (memberStatus.value === 1 || memberStatus.value === 'active') {
    const result = await Swal.fire({
      title: 'คุณต้องการออกจากรายวิชา?',
      text: 'คุณจะไม่สามารถเข้าถึงเนื้อหาได้จนกว่าจะสมัครใหม่',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'ใช่, ออกจากรายวิชา',
      cancelButtonText: 'ยกเลิก',
      confirmButtonColor: '#ef4444'
    })

    if (result.isConfirmed) {
      await leaveCourse()
    }
  }
}

async function leaveCourse() {
  isProcessing.value = true
  try {
    await api.delete(`/api/courses/${props.course.id}/members/${props.courseMemberOfAuth.id}`)
    Swal.fire({ title: 'เรียบร้อย', text: 'คุณออกจากรายวิชาแล้ว', icon: 'success', timer: 2000, showConfirmButton: false })
    emit('refresh')
  } catch (error) {
    Swal.fire({ title: 'ผิดพลาด', text: 'ไม่สามารถออกจากรายวิชาได้', icon: 'error' })
  } finally {
    isProcessing.value = false
  }
}

async function cancelRequest() {
  if (isProcessing.value) return
  isProcessing.value = true
  try {
    await api.delete(`/api/courses/${props.course.id}/members/${props.courseMemberOfAuth.id}`)
    showPendingMenu.value = false
    emit('refresh')
  } catch (error) {
    console.error('Failed to cancel request:', error)
  } finally {
    isProcessing.value = false
  }
}
</script>

<template>
  <div class="w-full" :class="variant === 'standalone' ? 'space-y-3' : 'flex items-center gap-2'">
    <!-- Active Member Button -->
    <button
      v-if="courseMemberOfAuth && (memberStatus === 1 || memberStatus === 'active')"
      @click="handleAction"
      :disabled="isProcessing"
      class="group flex items-center justify-center gap-2 rounded-xl bg-emerald-500 text-white font-black shadow-lg shadow-emerald-500/20 hover:bg-red-500 transition-all active:scale-95 disabled:opacity-50"
      :class="[variant === 'standalone' ? 'w-full' : '', buttonClasses]"
    >
      <Icon v-if="isProcessing" icon="svg-spinners:ring-resize" class="w-5 h-5" />
      <template v-else>
        <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 group-hover:hidden" />
        <Icon icon="majesticons:door-exit-line" class="w-5 h-5 hidden group-hover:block" />
        <span class="group-hover:hidden">เป็นสมาชิกแล้ว</span>
        <span class="hidden group-hover:block">ออกจากรายวิชา</span>
      </template>
    </button>

    <!-- Pending Status Button -->
    <div v-else-if="courseMemberOfAuth && (memberStatus === 0 || memberStatus === 'pending')" class="relative" :class="variant === 'standalone' ? 'w-full' : ''">
      <button
        @click="showPendingMenu = !showPendingMenu"
        class="flex items-center justify-center gap-2 rounded-xl bg-amber-500 text-white font-black shadow-lg shadow-amber-500/20 hover:bg-amber-600 transition-all active:scale-95"
        :class="[variant === 'standalone' ? 'w-full' : '', buttonClasses]"
      >
        <Icon icon="heroicons:clock" class="w-5 h-5" />
        <span>รอการตอบรับ</span>
        <Icon icon="heroicons:chevron-down" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showPendingMenu }" />
      </button>
      
      <!-- Dropdown to Cancel -->
      <div v-if="showPendingMenu" class="absolute left-0 right-0 top-full mt-2 z-30 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800">
        <button
          @click="cancelRequest"
          :disabled="isProcessing"
          class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
        >
          <Icon v-if="isProcessing" icon="svg-spinners:ring-resize" class="w-5 h-5" />
          <Icon v-else icon="heroicons:x-circle" class="w-5 h-5" />
          <span>ยกเลิกคำขอสมัคร</span>
        </button>
      </div>
      <div v-if="showPendingMenu" class="fixed inset-0 z-20" @click="showPendingMenu = false"></div>
    </div>

    <!-- Not Member: Join & Purchase Buttons -->
    <div v-else-if="!courseMemberOfAuth" 
      class="grid" 
      :class="[
        variant === 'standalone' ? 'w-full grid-cols-1 gap-3' : 'flex items-center gap-2',
        variant === 'standalone' && canPurchaseCopy ? 'sm:grid-cols-2' : ''
      ]"
    >
      <button
        @click="handleAction"
        :disabled="isProcessing"
        class="flex items-center justify-center gap-2 rounded-xl bg-indigo-600 text-white font-black shadow-lg shadow-indigo-500/20 hover:bg-indigo-700 transition-all active:scale-95 disabled:opacity-50"
        :class="[variant === 'standalone' ? 'w-full' : '', buttonClasses]"
      >
        <Icon v-if="isProcessing" icon="svg-spinners:ring-resize" class="w-5 h-5" />
        <Icon v-else icon="heroicons:user-plus-solid" class="w-5 h-5" />
        <span>สมัครเรียน</span>
      </button>

      <button
        v-if="canPurchaseCopy"
        @click="emit('purchase-course')"
        class="flex items-center justify-center gap-2 rounded-xl bg-cyan-500 text-white font-black shadow-lg shadow-cyan-500/20 hover:bg-cyan-600 transition-all active:scale-95"
        :class="[variant === 'standalone' ? 'w-full' : '', buttonClasses]"
      >
        <Icon icon="fluent:cart-24-filled" class="w-5 h-5" />
        <span>ซื้อรายวิชา</span>
      </button>
    </div>
  </div>
</template>
