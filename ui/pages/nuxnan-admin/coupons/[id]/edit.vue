<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import {
  useAdminCoupons,
  couponErrorMessage,
  couponFieldErrors,
  type AdminCoupon,
  type CouponStatus
} from '~/composables/useAdminCoupons'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const route = useRoute()
const router = useRouter()
const couponsApi = useAdminCoupons()
const couponId = route.params.id as string

const coupon = ref<AdminCoupon | null>(null)
const isLoading = ref(true)
const loadError = ref('')
const isSubmitting = ref(false)
const isCancelling = ref(false)
const errors = ref<Record<string, string>>({})
const generalError = ref('')
const successMessage = ref('')

const form = reactive({
  description: '',
  noExpiry: true,
  expiresAt: '' // รูปแบบ datetime-local (เวลาท้องถิ่น) YYYY-MM-DDTHH:mm
})

const statusBadge: Record<CouponStatus, string> = {
  active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  redeemed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
  expired: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
  cancelled: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
}

const pad = (n: number) => String(n).padStart(2, '0')
const toLocalInput = (date: Date) =>
  `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`

const minExpiry = computed(() => toLocalInput(new Date(Date.now() + 60_000)))

const unit = computed(() => (coupon.value?.coupon_type === 'points' ? 'แต้ม' : 'บาท'))
const amountText = computed(() => {
  if (!coupon.value) return ''
  const value = Number(coupon.value.amount)
  return coupon.value.coupon_type === 'points'
    ? value.toLocaleString('th-TH', { maximumFractionDigits: 0 })
    : value.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
})

const formatDate = (iso: string | null) =>
  iso ? new Date(iso).toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' }) : '-'

const fillForm = (data: AdminCoupon) => {
  coupon.value = data
  form.description = data.description ?? ''
  form.noExpiry = !data.expires_at
  form.expiresAt = data.expires_at ? toLocalInput(new Date(data.expires_at)) : ''
}

const fetchCoupon = async () => {
  isLoading.value = true
  loadError.value = ''
  try {
    const response = await couponsApi.get(couponId)
    fillForm(response.data)
  } catch (error: any) {
    loadError.value = error?.status === 404 ? 'ไม่พบคูปองนี้' : couponErrorMessage(error, 'โหลดข้อมูลคูปองไม่สำเร็จ')
  } finally {
    isLoading.value = false
  }
}

const validate = () => {
  const next: Record<string, string> = {}
  if (form.description.length > 500) next.description = 'คำอธิบายยาวได้ไม่เกิน 500 ตัวอักษร'
  if (!form.noExpiry) {
    if (!form.expiresAt) next.expires_at = 'กรุณาเลือกวันหมดอายุ หรือเลือก “ไม่มีวันหมดอายุ”'
    else if (new Date(form.expiresAt).getTime() <= Date.now()) next.expires_at = 'วันหมดอายุต้องเป็นเวลาในอนาคต'
  }
  errors.value = next
  return Object.keys(next).length === 0
}

const handleSubmit = async () => {
  generalError.value = ''
  successMessage.value = ''
  if (!coupon.value?.can_edit || !validate() || isSubmitting.value) return

  isSubmitting.value = true
  try {
    const response = await couponsApi.update(couponId, {
      description: form.description.trim() || null,
      expires_at: form.noExpiry ? null : new Date(form.expiresAt).toISOString()
    })
    fillForm(response.data)
    successMessage.value = response.message || 'บันทึกแล้ว'
  } catch (error: any) {
    errors.value = couponFieldErrors(error)
    generalError.value = couponErrorMessage(error)
  } finally {
    isSubmitting.value = false
  }
}

const handleCancel = async () => {
  if (!coupon.value?.can_cancel || isCancelling.value) return
  const confirm = await Swal.fire({
    title: 'ยกเลิกคูปองนี้?',
    text: `รหัส ${coupon.value.coupon_code} จะแลกไม่ได้อีก และระบบคืน ${amountText.value} ${unit.value} ให้ ${coupon.value.creator?.name ?? 'ผู้สร้างคูปอง'}`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ยกเลิกคูปอง',
    cancelButtonText: 'ไม่ใช่ตอนนี้',
    confirmButtonColor: '#dc2626'
  })
  if (!confirm.isConfirmed) return

  isCancelling.value = true
  try {
    const response = await couponsApi.cancel(couponId)
    await Swal.fire({ title: 'ยกเลิกแล้ว', text: response.message, icon: 'success', timer: 1800, showConfirmButton: false })
    router.push('/nuxnan-admin/coupons')
  } catch (error: any) {
    Swal.fire('ยกเลิกไม่สำเร็จ', couponErrorMessage(error), 'error')
  } finally {
    isCancelling.value = false
  }
}

const copyCode = async () => {
  if (!coupon.value) return
  try {
    await navigator.clipboard.writeText(coupon.value.coupon_code)
    successMessage.value = 'คัดลอกรหัสแล้ว'
  } catch {
    generalError.value = 'คัดลอกไม่สำเร็จ'
  }
}

const inputClass =
  'min-h-[44px] w-full rounded-xl border bg-slate-50 px-4 py-2.5 text-slate-800 placeholder-slate-400 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-slate-700 dark:text-white'

onMounted(fetchCoupon)
</script>

<template>
  <div class="mx-auto max-w-2xl space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="flex items-start gap-3 sm:items-center sm:gap-4">
      <NuxtLink
        to="/nuxnan-admin/coupons"
        aria-label="กลับไปหน้ารายการคูปอง"
        class="inline-flex min-h-[44px] min-w-[44px] flex-shrink-0 items-center justify-center rounded-xl text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="h-5 w-5" />
      </NuxtLink>
      <div class="min-w-0 flex-1">
        <h1 class="text-xl font-bold text-slate-800 dark:text-white sm:text-2xl">
          {{ coupon?.can_edit === false ? 'รายละเอียดคูปอง' : 'แก้ไขคูปอง' }}
        </h1>
        <p class="mt-1 break-words text-sm text-slate-500 dark:text-slate-400 sm:text-base">
          แก้ได้เฉพาะคำอธิบายและวันหมดอายุ — มูลค่าและรหัสถูกล็อกตั้งแต่ตอนสร้าง
        </p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="p-8 text-center">
      <Icon icon="fluent:spinner-ios-20-regular" class="mx-auto h-8 w-8 animate-spin text-hopeui-primary-600" />
      <p class="mt-2 text-slate-500">กำลังโหลดข้อมูล...</p>
    </div>

    <!-- Load error -->
    <div
      v-else-if="loadError || !coupon"
      class="flex items-start gap-2 rounded-2xl border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-700 dark:bg-red-900/30 dark:text-red-400"
    >
      <Icon icon="fluent:error-circle-24-filled" class="h-5 w-5 flex-shrink-0" />
      <span class="min-w-0 flex-1 break-words">{{ loadError || 'ไม่พบคูปองนี้' }}</span>
    </div>

    <template v-else>
      <!-- Coupon facts (read-only) -->
      <section class="rounded-2xl border border-slate-100 bg-white p-4 shadow-hopeui dark:border-slate-700 dark:bg-slate-800 sm:p-6">
        <div class="flex flex-wrap items-center gap-2">
          <span class="whitespace-nowrap rounded-full bg-hopeui-primary-100 px-2 py-0.5 text-xs font-medium text-hopeui-primary-700 dark:bg-hopeui-primary-900/30 dark:text-hopeui-primary-300">
            {{ coupon.type_label }}
          </span>
          <span class="whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium" :class="statusBadge[coupon.display_status]">
            {{ coupon.status_label }}
          </span>
        </div>

        <p class="mt-2 break-words text-3xl font-bold text-slate-800 dark:text-white">
          {{ amountText }} <span class="text-base font-normal text-slate-500">{{ unit }}</span>
        </p>

        <div class="mt-3 flex items-center gap-2 rounded-xl bg-slate-50 p-2 pl-3 dark:bg-slate-700/50">
          <code class="min-w-0 flex-1 break-all font-mono text-xl font-bold tracking-widest text-hopeui-primary-600 dark:text-hopeui-primary-400">
            {{ coupon.coupon_code }}
          </code>
          <button
            type="button"
            aria-label="คัดลอกรหัสคูปอง"
            class="inline-flex min-h-[44px] min-w-[44px] flex-shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-white hover:text-hopeui-primary-600 dark:hover:bg-slate-600"
            @click="copyCode"
          >
            <Icon icon="fluent:copy-24-regular" class="h-5 w-5" />
          </button>
        </div>

        <dl class="mt-4 space-y-2 text-sm">
          <div class="flex items-start gap-3">
            <dt class="flex-shrink-0 whitespace-nowrap text-slate-500">ผู้สร้าง</dt>
            <dd class="min-w-0 flex-1 break-words text-right text-slate-800 dark:text-white">
              {{ coupon.creator?.name ?? '-' }}
              <span v-if="coupon.creator?.email" class="block text-xs text-slate-500">{{ coupon.creator.email }}</span>
            </dd>
          </div>
          <div class="flex items-start gap-3">
            <dt class="flex-shrink-0 whitespace-nowrap text-slate-500">สร้างเมื่อ</dt>
            <dd class="min-w-0 flex-1 break-words text-right text-slate-800 dark:text-white">{{ formatDate(coupon.created_at) }}</dd>
          </div>
          <div class="flex items-start gap-3">
            <dt class="flex-shrink-0 whitespace-nowrap text-slate-500">หมดอายุ</dt>
            <dd class="min-w-0 flex-1 break-words text-right text-slate-800 dark:text-white">
              {{ coupon.expires_at ? formatDate(coupon.expires_at) : 'ไม่มีวันหมดอายุ' }}
            </dd>
          </div>
          <div v-if="coupon.redeemer" class="flex items-start gap-3">
            <dt class="flex-shrink-0 whitespace-nowrap text-slate-500">ผู้แลก</dt>
            <dd class="min-w-0 flex-1 break-words text-right text-slate-800 dark:text-white">
              {{ coupon.redeemer.name }}
              <span class="block text-xs text-slate-500">{{ formatDate(coupon.redeemed_at) }}</span>
            </dd>
          </div>
        </dl>
      </section>

      <div
        v-if="successMessage"
        class="flex items-start gap-2 rounded-xl border border-green-200 bg-green-50 p-3 text-green-700 dark:border-green-700 dark:bg-green-900/30 dark:text-green-400"
      >
        <Icon icon="fluent:checkmark-circle-24-filled" class="h-5 w-5 flex-shrink-0" />
        <span class="min-w-0 flex-1 break-words">{{ successMessage }}</span>
      </div>

      <!-- Editable fields -->
      <form
        novalidate
        class="space-y-5 rounded-2xl border border-slate-100 bg-white p-4 shadow-hopeui dark:border-slate-700 dark:bg-slate-800 sm:p-6"
        @submit.prevent="handleSubmit"
      >
        <div
          v-if="!coupon.can_edit"
          class="flex items-start gap-2 rounded-xl bg-slate-50 p-3 text-sm text-slate-600 dark:bg-slate-700/50 dark:text-slate-300"
        >
          <Icon icon="fluent:lock-closed-24-regular" class="h-5 w-5 flex-shrink-0" />
          <span class="min-w-0 flex-1 break-words">คูปองที่{{ coupon.status_label }}แก้ไขไม่ได้ เก็บไว้เป็นประวัติ</span>
        </div>

        <div
          v-if="generalError"
          class="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-red-700 dark:border-red-700 dark:bg-red-900/30 dark:text-red-400"
        >
          <Icon icon="fluent:error-circle-24-filled" class="h-5 w-5 flex-shrink-0" />
          <span class="min-w-0 flex-1 break-words">{{ generalError }}</span>
        </div>

        <div>
          <label for="coupon-description" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">คำอธิบาย</label>
          <textarea
            id="coupon-description"
            v-model="form.description"
            rows="3"
            maxlength="500"
            :disabled="!coupon.can_edit"
            :class="[inputClass, 'resize-none', errors.description ? 'border-red-500' : 'border-slate-200 dark:border-slate-600']"
            placeholder="รายละเอียดเพิ่มเติมของคูปอง"
          ></textarea>
          <p v-if="errors.description" class="mt-1 text-sm text-red-500">{{ errors.description }}</p>
        </div>

        <div class="space-y-3">
          <span class="block text-sm font-medium text-slate-700 dark:text-slate-300">วันหมดอายุ</span>
          <label class="flex min-h-[44px] cursor-pointer items-center gap-3 rounded-xl bg-slate-50 px-3 dark:bg-slate-700/50">
            <input
              v-model="form.noExpiry"
              type="checkbox"
              :disabled="!coupon.can_edit"
              class="h-5 w-5 flex-shrink-0 rounded border-slate-300 text-hopeui-primary-600 focus:ring-hopeui-primary-500"
            />
            <span class="min-w-0 flex-1 text-slate-700 dark:text-slate-300">ไม่มีวันหมดอายุ</span>
          </label>
          <div v-if="!form.noExpiry">
            <input
              v-model="form.expiresAt"
              type="datetime-local"
              aria-label="วันและเวลาหมดอายุ"
              :min="minExpiry"
              :disabled="!coupon.can_edit"
              :class="[inputClass, errors.expires_at ? 'border-red-500' : 'border-slate-200 dark:border-slate-600']"
            />
            <p v-if="errors.expires_at" class="mt-1 text-sm text-red-500">{{ errors.expires_at }}</p>
          </div>
        </div>

        <div v-if="coupon.can_edit" class="flex flex-col gap-3 pt-2 sm:flex-row">
          <button
            type="submit"
            :disabled="isSubmitting"
            class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-2 rounded-xl bg-hopeui-primary-500 px-6 py-3 font-medium text-white transition-colors hover:bg-hopeui-primary-600 disabled:bg-hopeui-primary-300"
          >
            <Icon v-if="isSubmitting" icon="fluent:spinner-ios-20-regular" class="h-5 w-5 animate-spin" />
            <Icon v-else icon="fluent:save-24-regular" class="h-5 w-5" />
            {{ isSubmitting ? 'กำลังบันทึก...' : 'บันทึก' }}
          </button>
          <NuxtLink
            to="/nuxnan-admin/coupons"
            class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-2 rounded-xl bg-slate-100 px-4 py-3 font-medium text-slate-700 transition-colors hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600 sm:px-6"
          >
            ย้อนกลับ
          </NuxtLink>
        </div>
      </form>

      <!-- Cancel + refund -->
      <section
        v-if="coupon.can_cancel"
        class="space-y-3 rounded-2xl border border-red-200 bg-white p-4 dark:border-red-900/50 dark:bg-slate-800 sm:p-6"
      >
        <h2 class="font-semibold text-red-600 dark:text-red-400">ยกเลิกคูปอง</h2>
        <p class="break-words text-sm text-slate-600 dark:text-slate-300">
          รหัสนี้จะแลกไม่ได้อีก และระบบคืน {{ amountText }} {{ unit }} ให้ผู้สร้างคูปอง ({{ coupon.creator?.name ?? '-' }})
          คูปองยังอยู่ในรายการเป็นประวัติ
        </p>
        <button
          type="button"
          :disabled="isCancelling"
          class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-4 font-medium text-white transition-colors hover:bg-red-700 disabled:opacity-60 sm:w-auto"
          @click="handleCancel"
        >
          <Icon
            :icon="isCancelling ? 'fluent:spinner-ios-20-regular' : 'fluent:dismiss-circle-24-regular'"
            class="h-5 w-5"
            :class="{ 'animate-spin': isCancelling }"
          />
          ยกเลิกคูปองและคืนยอด
        </button>
      </section>
    </template>
  </div>
</template>
