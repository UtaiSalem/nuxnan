<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

const props = defineProps({
  course: { type: Object, required: true },
  courseMemberOfAuth: { type: Object, default: null },
  isCheckingMembership: { type: Boolean, default: false },
  variant: { type: String, default: 'standalone' }, // 'standalone' or 'hero'
})

const emit = defineEmits(['refresh', 'request-member', 'purchase-course'])

const api = useApi()
const isProcessing = ref(false)
const showPendingMenu = ref(false)

const approvalStatus = computed(() => props.courseMemberOfAuth?.course_member_status)
const accessStatus = computed(() => props.courseMemberOfAuth?.status)
const isApprovedAwaitingPayment = computed(() =>
  approvalStatus.value === 1 && accessStatus.value === 0 && courseJoinPrice.value > 0
)
const isPendingApproval = computed(() => approvalStatus.value === 0)
const courseJoinPrice = computed(() => Number(props.course?.tuition_fees ?? 0))
const canPurchaseCopy = computed(() => Boolean(props.course?.is_for_marketplace))

// Purchase eligibility state (lazy-loaded only for marketplace courses)
const purchaseCheck = ref<{ has_purchased: boolean; is_self: boolean } | null>(null)
const isCheckingPurchase = ref(false)

onMounted(async () => {
  if (!canPurchaseCopy.value) return
  isCheckingPurchase.value = true
  try {
    const res = await api.get(`/api/courses/${props.course.id}/purchase/check`)
    purchaseCheck.value = { has_purchased: res.has_purchased, is_self: res.is_self }
  } catch {
    // fail silently — button stays visible and lets the modal handle validation
  } finally {
    isCheckingPurchase.value = false
  }
})

const alreadyPurchased = computed(() => purchaseCheck.value?.has_purchased ?? false)
const isCourseSelf = computed(() => purchaseCheck.value?.is_self ?? false)

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
  if (accessStatus.value === 1 || accessStatus.value === 'active') {
    await confirmAndLeave()
  }
}

async function confirmAndLeave() {
  isProcessing.value = true
  try {
    const previewRes = await api.get(`/api/courses/${props.course.id}/members/${props.courseMemberOfAuth.id}/removal-preview`)
    const preview = previewRes.preview
    
    let html = 'คุณจะไม่สามารถเข้าถึงเนื้อหาได้จนกว่าจะสมัครใหม่'
    if (preview.payment.is_paid) {
      html += '<br><br><div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-sm text-amber-700 dark:text-amber-400 font-medium"><strong>หมายเหตุ:</strong> รายวิชานี้มีการชำระเงิน การออกจากรายวิชาด้วยตนเองจะไม่ได้รับแต้มหรือเงินคืน</div>'
    }

    const result = await Swal.fire({
      title: 'คุณต้องการออกจากรายวิชา?',
      html: html,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'ใช่, ออกจากรายวิชา',
      cancelButtonText: 'ยกเลิก',
      confirmButtonColor: '#ef4444'
    })

    if (result.isConfirmed) {
      await leaveCourse()
    }
  } catch (error) {
    console.error('Failed to get removal preview:', error)
    // Fallback to simple confirmation if preview fails
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
  } finally {
    isProcessing.value = false
  }
}

async function leaveCourse() {
  isProcessing.value = true
  try {
    await api.post(`/api/courses/${props.course.id}/members/${props.courseMemberOfAuth.id}/remove`, {
      mode: 'self_leave'
    })
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
    await api.post(`/api/courses/${props.course.id}/members/${props.courseMemberOfAuth.id}/remove`, {
      mode: 'cancel_request'
    })
    showPendingMenu.value = false
    emit('refresh')
  } catch (error) {
    console.error('Failed to cancel request:', error)
  } finally {
    isProcessing.value = false
  }
}

async function completePayment() {
  if (isProcessing.value) return
  isProcessing.value = true
  try {
    await api.post(`/api/courses/${props.course.id}/members/${props.courseMemberOfAuth.id}/complete-payment`)
    Swal.fire({ title: 'ชำระเงินสำเร็จ', text: 'เริ่มเรียนได้เลย', icon: 'success', timer: 2000, showConfirmButton: false })
    emit('refresh')
  } catch (error: any) {
    const data = error?.response?.data ?? {}
    if (data.need_topup) {
      Swal.fire({ title: 'ยอดเงินไม่เพียงพอ', text: `ต้องการ ${data.required} บาท (มี ${data.current_balance} บาท)`, icon: 'warning' })
    } else {
      Swal.fire({ title: 'ผิดพลาด', text: data.message ?? data.msg ?? 'ไม่สามารถชำระเงินได้', icon: 'error' })
    }
  } finally {
    isProcessing.value = false
  }
}
</script>

<template>
  <div class="w-full" :class="variant === 'standalone' ? 'space-y-3' : 'flex items-center gap-2'">
    <!-- Guard: กำลังตรวจสอบสถานะ -->
    <button
      v-if="isCheckingMembership"
      disabled
      class="flex items-center justify-center gap-2 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 font-black opacity-50 cursor-wait"
      :class="[variant === 'standalone' ? 'w-full' : '', buttonClasses]"
    >
      <Icon icon="svg-spinners:ring-resize" class="w-5 h-5" />
      <span>กำลังตรวจสอบ...</span>
    </button>

    <!-- Active Member Button -->
    <button
      v-else-if="courseMemberOfAuth && (accessStatus === 1 || accessStatus === 'active')"
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

    <!-- Approved, awaiting payment -->
    <button
      v-else-if="courseMemberOfAuth && isApprovedAwaitingPayment"
      @click="completePayment"
      :disabled="isProcessing"
      class="flex items-center justify-center gap-2 rounded-xl bg-emerald-600 text-white font-black shadow-lg shadow-emerald-500/20 hover:bg-emerald-700 transition-all active:scale-95 disabled:opacity-50"
      :class="[variant === 'standalone' ? 'w-full' : '', buttonClasses]"
    >
      <Icon v-if="isProcessing" icon="svg-spinners:ring-resize" class="w-5 h-5" />
      <template v-else>
        <Icon icon="heroicons:credit-card" class="w-5 h-5" />
        <span>ชำระเงินเพื่อเริ่มเรียน</span>
      </template>
    </button>

    <!-- Pending Status Button -->
    <div v-else-if="courseMemberOfAuth && isPendingApproval" class="relative" :class="variant === 'standalone' ? 'w-full' : ''">
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

    <!-- Not Member: Enrollment Closed -->
    <div v-else-if="!courseMemberOfAuth && !course.is_enrollment_open"
      :class="variant === 'standalone' ? 'w-full' : 'flex items-center gap-2'"
    >
      <button
        disabled
        class="flex items-center justify-center gap-2 rounded-xl bg-gray-400 dark:bg-gray-600 text-white font-black cursor-not-allowed"
        :class="[variant === 'standalone' ? 'w-full' : '', buttonClasses]"
      >
        <Icon icon="heroicons:lock-closed" class="w-5 h-5" />
        <span>ปิดรับสมัครแล้ว</span>
      </button>
    </div>

    <!-- Not Member: Join & Purchase Buttons -->
    <div v-else-if="!courseMemberOfAuth"
      class="grid"
      :class="[
        variant === 'standalone' ? 'w-full grid-cols-1 gap-3' : 'flex items-center gap-2',
        variant === 'standalone' && canPurchaseCopy && !isCourseSelf ? 'sm:grid-cols-2' : ''
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

      <!-- Purchase button: hidden for self/owner, disabled if already purchased -->
      <template v-if="canPurchaseCopy && !isCourseSelf">
        <button
          v-if="alreadyPurchased"
          disabled
          class="flex items-center justify-center gap-2 rounded-xl bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 font-black cursor-not-allowed"
          :class="[variant === 'standalone' ? 'w-full' : '', buttonClasses]"
        >
          <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5" />
          <span>ซื้อแล้ว</span>
        </button>
        <button
          v-else
          @click="emit('purchase-course')"
          :disabled="isCheckingPurchase"
          class="flex items-center justify-center gap-2 rounded-xl bg-cyan-500 text-white font-black shadow-lg shadow-cyan-500/20 hover:bg-cyan-600 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-wait"
          :class="[variant === 'standalone' ? 'w-full' : '', buttonClasses]"
        >
          <Icon v-if="isCheckingPurchase" icon="svg-spinners:ring-resize" class="w-5 h-5" />
          <Icon v-else icon="fluent:cart-24-filled" class="w-5 h-5" />
          <span>ซื้อรายวิชา</span>
        </button>
      </template>
    </div>
  </div>
</template>
