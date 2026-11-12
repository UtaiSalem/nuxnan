<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { Icon } from '@iconify/vue'
import {
  useAdminCoupons,
  couponErrorMessage,
  couponFieldErrors,
  type AdminCoupon,
  type CouponType
} from '~/composables/useAdminCoupons'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const couponsApi = useAdminCoupons()
const authStore = useAuthStore()

const isSubmitting = ref(false)
const errors = ref<Record<string, string>>({})
const generalError = ref('')
const created = ref<AdminCoupon[]>([])
const copiedAll = ref(false)

const form = reactive({
  type: 'points' as CouponType,
  amount: null as number | null,
  quantity: 1,
  expires_in_days: null as number | null,
  description: ''
})

const typeOptions: { value: CouponType; label: string; icon: string; unit: string }[] = [
  { value: 'points', label: 'คูปองแต้ม', icon: 'fluent:star-24-regular', unit: 'แต้ม' },
  { value: 'wallet', label: 'คูปองเงิน', icon: 'fluent:wallet-24-regular', unit: 'บาท' }
]

const unit = computed(() => (form.type === 'points' ? 'แต้ม' : 'บาท'))

// มูลค่าคูปองหักจากยอดของแอดมินผู้สร้างเอง — โชว์ยอดคงเหลือให้เห็นก่อนกดสร้าง
const balanceOf = (type: CouponType) =>
  Number(type === 'points' ? authStore.user?.points ?? 0 : authStore.user?.wallet ?? 0)

const balance = computed(() => balanceOf(form.type))
const totalCost = computed(() => (Number(form.amount) || 0) * (Number(form.quantity) || 0))
const remaining = computed(() => balance.value - totalCost.value)
const insufficient = computed(() => totalCost.value > 0 && remaining.value < 0)

const formatValue = (value: number, type: CouponType = form.type) =>
  type === 'points'
    ? value.toLocaleString('th-TH', { maximumFractionDigits: 0 })
    : value.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const validate = () => {
  const next: Record<string, string> = {}
  const amount = Number(form.amount)

  if (!form.amount || amount < 1) {
    next.amount = 'มูลค่าต่อใบต้องอย่างน้อย 1'
  } else if (form.type === 'points' && !Number.isInteger(amount)) {
    next.amount = 'คูปองแต้มต้องเป็นจำนวนเต็ม'
  } else if (form.type === 'wallet' && !/^\d+(\.\d{1,2})?$/.test(String(form.amount))) {
    next.amount = 'ทศนิยมได้ไม่เกิน 2 ตำแหน่ง'
  }

  if (!Number.isInteger(Number(form.quantity)) || form.quantity < 1 || form.quantity > 100) {
    next.quantity = 'จำนวนใบต้องอยู่ระหว่าง 1–100'
  }

  if (form.expires_in_days !== null && String(form.expires_in_days) !== '') {
    const days = Number(form.expires_in_days)
    if (!Number.isInteger(days) || days < 1 || days > 365) {
      next.expires_in_days = 'อายุคูปองต้องอยู่ระหว่าง 1–365 วัน (เว้นว่าง = ไม่มีวันหมดอายุ)'
    }
  }

  if (form.description.length > 500) {
    next.description = 'คำอธิบายยาวได้ไม่เกิน 500 ตัวอักษร'
  }

  errors.value = next
  return Object.keys(next).length === 0
}

const handleSubmit = async () => {
  generalError.value = ''
  if (!validate() || insufficient.value || isSubmitting.value) return

  isSubmitting.value = true
  try {
    const hasExpiry = form.expires_in_days !== null && String(form.expires_in_days) !== ''
    const response = await couponsApi.create({
      type: form.type,
      amount: Number(form.amount),
      quantity: Number(form.quantity),
      description: form.description.trim() || null,
      expires_in_days: hasExpiry ? Number(form.expires_in_days) : null
    })
    created.value = response.data.coupons
    // ยอดของแอดมินถูกหักแล้ว — โหลดใหม่ให้ตัวเลขคงเหลือตรง
    authStore.fetchUser().catch(() => {})
  } catch (error: any) {
    errors.value = couponFieldErrors(error)
    generalError.value = couponErrorMessage(error)
  } finally {
    isSubmitting.value = false
  }
}

const copyAll = async () => {
  try {
    await navigator.clipboard.writeText(created.value.map((c) => c.coupon_code).join('\n'))
    copiedAll.value = true
    setTimeout(() => (copiedAll.value = false), 1500)
  } catch {
    generalError.value = 'คัดลอกไม่สำเร็จ กรุณาคัดลอกรหัสจากรายการด้านล่าง'
  }
}

const resetForm = () => {
  created.value = []
  errors.value = {}
  generalError.value = ''
  form.amount = null
  form.quantity = 1
  form.expires_in_days = null
  form.description = ''
}

const inputClass =
  'min-h-[44px] w-full rounded-xl border bg-slate-50 px-4 py-2.5 text-slate-800 placeholder-slate-400 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 dark:bg-slate-700 dark:text-white'
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
        <h1 class="text-xl font-bold text-slate-800 dark:text-white sm:text-2xl">สร้างคูปอง</h1>
        <p class="mt-1 break-words text-sm text-slate-500 dark:text-slate-400 sm:text-base">
          มูลค่าคูปองหักจากแต้ม/เงินในบัญชีของคุณ ผู้รับแลกได้ครั้งเดียว
        </p>
      </div>
    </div>

    <!-- Result -->
    <div
      v-if="created.length"
      class="space-y-4 rounded-2xl border border-green-200 bg-white p-4 shadow-hopeui dark:border-green-800 dark:bg-slate-800 sm:p-6"
    >
      <div class="flex items-start gap-3 text-green-700 dark:text-green-400">
        <Icon icon="fluent:checkmark-circle-24-filled" class="h-6 w-6 flex-shrink-0" />
        <p class="min-w-0 flex-1 break-words font-medium">
          สร้างคูปอง {{ created.length }} ใบแล้ว ใบละ {{ formatValue(Number(created[0].amount), created[0].coupon_type) }}
          {{ created[0].coupon_type === 'points' ? 'แต้ม' : 'บาท' }}
        </p>
      </div>

      <ul class="max-h-72 space-y-2 overflow-y-auto">
        <li
          v-for="coupon in created"
          :key="coupon.id"
          class="flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-2 dark:bg-slate-700/50"
        >
          <code class="min-w-0 flex-1 break-all font-mono text-lg font-bold tracking-widest text-hopeui-primary-600 dark:text-hopeui-primary-400">
            {{ coupon.coupon_code }}
          </code>
          <span class="flex-shrink-0 whitespace-nowrap text-xs text-slate-500">
            {{ coupon.expires_at ? `หมดอายุ ${new Date(coupon.expires_at).toLocaleDateString('th-TH')}` : 'ไม่มีวันหมดอายุ' }}
          </span>
        </li>
      </ul>

      <div class="flex flex-col gap-2 sm:flex-row">
        <button
          type="button"
          class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-2 rounded-xl bg-hopeui-primary-500 px-4 font-medium text-white hover:bg-hopeui-primary-600"
          @click="copyAll"
        >
          <Icon :icon="copiedAll ? 'fluent:checkmark-24-regular' : 'fluent:copy-24-regular'" class="h-5 w-5" />
          {{ copiedAll ? 'คัดลอกแล้ว' : 'คัดลอกรหัสทั้งหมด' }}
        </button>
        <button
          type="button"
          class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-2 rounded-xl bg-slate-100 px-4 font-medium text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600"
          @click="resetForm"
        >
          <Icon icon="fluent:add-24-regular" class="h-5 w-5" />
          สร้างเพิ่ม
        </button>
        <NuxtLink
          to="/nuxnan-admin/coupons"
          class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-2 rounded-xl bg-slate-100 px-4 font-medium text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600"
        >
          <Icon icon="fluent:list-24-regular" class="h-5 w-5" />
          ไปหน้ารายการ
        </NuxtLink>
      </div>
    </div>

    <!-- Form -->
    <form
      v-else
      novalidate
      class="space-y-5 rounded-2xl border border-slate-100 bg-white p-4 shadow-hopeui dark:border-slate-700 dark:bg-slate-800 sm:space-y-6 sm:p-6"
      @submit.prevent="handleSubmit"
    >
      <div
        v-if="generalError"
        class="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-red-700 dark:border-red-700 dark:bg-red-900/30 dark:text-red-400"
      >
        <Icon icon="fluent:error-circle-24-filled" class="h-5 w-5 flex-shrink-0" />
        <span class="min-w-0 flex-1 break-words">{{ generalError }}</span>
      </div>

      <!-- Type -->
      <fieldset>
        <legend class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">ประเภทคูปอง</legend>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <label
            v-for="option in typeOptions"
            :key="option.value"
            class="flex min-h-[44px] cursor-pointer items-center gap-3 rounded-xl border-2 p-3 transition-colors"
            :class="form.type === option.value
              ? 'border-hopeui-primary-500 bg-hopeui-primary-50 dark:bg-hopeui-primary-900/20'
              : 'border-slate-200 hover:border-slate-300 dark:border-slate-600'"
          >
            <input v-model="form.type" type="radio" name="coupon-type" :value="option.value" class="sr-only" />
            <Icon :icon="option.icon" class="h-6 w-6 flex-shrink-0 text-hopeui-primary-600" />
            <span class="min-w-0 flex-1">
              <span class="block font-medium text-slate-800 dark:text-white">{{ option.label }}</span>
              <span class="block break-words text-xs text-slate-500">
                ยอดของคุณ {{ formatValue(balanceOf(option.value), option.value) }} {{ option.unit }}
              </span>
            </span>
          </label>
        </div>
      </fieldset>

      <!-- Amount & Quantity -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label for="coupon-amount" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
            มูลค่าต่อใบ ({{ unit }}) <span class="text-red-500">*</span>
          </label>
          <input
            id="coupon-amount"
            v-model.number="form.amount"
            type="number"
            inputmode="decimal"
            min="1"
            :step="form.type === 'points' ? 1 : 0.01"
            :class="[inputClass, errors.amount ? 'border-red-500' : 'border-slate-200 dark:border-slate-600']"
            placeholder="100"
          />
          <p v-if="errors.amount" class="mt-1 text-sm text-red-500">{{ errors.amount }}</p>
        </div>
        <div>
          <label for="coupon-quantity" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
            จำนวนใบ
          </label>
          <input
            id="coupon-quantity"
            v-model.number="form.quantity"
            type="number"
            inputmode="numeric"
            min="1"
            max="100"
            step="1"
            :class="[inputClass, errors.quantity ? 'border-red-500' : 'border-slate-200 dark:border-slate-600']"
          />
          <p v-if="errors.quantity" class="mt-1 text-sm text-red-500">{{ errors.quantity }}</p>
          <p v-else class="mt-1 text-xs text-slate-500">สูงสุด 100 ใบต่อครั้ง · แต่ละใบได้รหัสไม่ซ้ำกัน</p>
        </div>
      </div>

      <!-- Expiry -->
      <div>
        <label for="coupon-expiry" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
          อายุคูปอง (วัน)
        </label>
        <input
          id="coupon-expiry"
          v-model.number="form.expires_in_days"
          type="number"
          inputmode="numeric"
          min="1"
          max="365"
          step="1"
          :class="[inputClass, errors.expires_in_days ? 'border-red-500' : 'border-slate-200 dark:border-slate-600']"
          placeholder="เว้นว่าง = ไม่มีวันหมดอายุ"
        />
        <p v-if="errors.expires_in_days" class="mt-1 text-sm text-red-500">{{ errors.expires_in_days }}</p>
        <p v-else class="mt-1 text-xs text-slate-500">1–365 วันนับจากตอนสร้าง · คูปองที่หมดอายุแล้วยกเลิกเพื่อรับยอดคืนได้</p>
      </div>

      <!-- Description -->
      <div>
        <label for="coupon-description" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
          คำอธิบาย
        </label>
        <textarea
          id="coupon-description"
          v-model="form.description"
          rows="3"
          maxlength="500"
          :class="[inputClass, 'resize-none', errors.description ? 'border-red-500' : 'border-slate-200 dark:border-slate-600']"
          placeholder="เช่น รางวัลผู้ชนะกิจกรรมเดือนพฤศจิกายน"
        ></textarea>
        <p v-if="errors.description" class="mt-1 text-sm text-red-500">{{ errors.description }}</p>
      </div>

      <!-- Cost summary -->
      <div
        class="space-y-2 rounded-xl p-3 text-sm sm:p-4"
        :class="insufficient
          ? 'border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20'
          : 'bg-slate-50 dark:bg-slate-700/50'"
      >
        <div class="flex items-start gap-3">
          <span class="min-w-0 flex-1 break-words text-slate-600 dark:text-slate-300">หักจากบัญชีของคุณ</span>
          <span class="flex-shrink-0 whitespace-nowrap font-bold text-slate-800 dark:text-white">
            {{ formatValue(totalCost) }} {{ unit }}
          </span>
        </div>
        <div class="flex items-start gap-3">
          <span class="min-w-0 flex-1 break-words text-slate-600 dark:text-slate-300">คงเหลือหลังสร้าง</span>
          <span
            class="flex-shrink-0 whitespace-nowrap font-bold"
            :class="insufficient ? 'text-red-600 dark:text-red-400' : 'text-slate-800 dark:text-white'"
          >
            {{ formatValue(remaining) }} {{ unit }}
          </span>
        </div>
        <p v-if="insufficient" class="break-words text-red-600 dark:text-red-400">
          ยอด{{ form.type === 'points' ? 'แต้ม' : 'เงิน' }}ของคุณไม่พอ ลดมูลค่าหรือจำนวนใบลง
        </p>
      </div>

      <!-- Actions -->
      <div class="flex flex-col gap-3 pt-2 sm:flex-row">
        <button
          type="submit"
          :disabled="isSubmitting || insufficient"
          class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-2 rounded-xl bg-hopeui-primary-500 px-6 py-3 font-medium text-white transition-colors hover:bg-hopeui-primary-600 disabled:cursor-not-allowed disabled:bg-hopeui-primary-300"
        >
          <Icon v-if="isSubmitting" icon="fluent:spinner-ios-20-regular" class="h-5 w-5 animate-spin" />
          <Icon v-else icon="fluent:ticket-diagonal-24-regular" class="h-5 w-5" />
          {{ isSubmitting ? 'กำลังสร้าง...' : 'สร้างคูปอง' }}
        </button>
        <NuxtLink
          to="/nuxnan-admin/coupons"
          class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-2 rounded-xl bg-slate-100 px-4 py-3 font-medium text-slate-700 transition-colors hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600 sm:px-6"
        >
          <Icon icon="fluent:dismiss-24-regular" class="h-5 w-5" />
          ยกเลิก
        </NuxtLink>
      </div>
    </form>
  </div>
</template>
