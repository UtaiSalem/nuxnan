<script setup lang="ts">
import { ref, reactive, watch, onMounted, onBeforeUnmount } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import {
  useAdminCoupons,
  couponErrorMessage,
  type AdminCoupon,
  type AdminCouponSummary,
  type CouponStatus,
  type CouponType
} from '~/composables/useAdminCoupons'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const couponsApi = useAdminCoupons()

const coupons = ref<AdminCoupon[]>([])
const summary = ref<AdminCouponSummary | null>(null)
const isLoading = ref(true)
const loadError = ref('')
const cancellingId = ref<number | null>(null)
const copiedId = ref<number | null>(null)

const filters = reactive({
  search: '',
  status: 'all' as CouponStatus | 'all',
  type: 'all' as CouponType | 'all'
})
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)

const statusOptions: { value: CouponStatus | 'all'; label: string }[] = [
  { value: 'all', label: 'ทุกสถานะ' },
  { value: 'active', label: 'ใช้งานได้' },
  { value: 'redeemed', label: 'แลกแล้ว' },
  { value: 'expired', label: 'หมดอายุ' },
  { value: 'cancelled', label: 'ยกเลิกแล้ว' }
]

const typeOptions: { value: CouponType | 'all'; label: string }[] = [
  { value: 'all', label: 'ทุกประเภท' },
  { value: 'points', label: 'คูปองแต้ม' },
  { value: 'wallet', label: 'คูปองเงิน' }
]

const statusBadge: Record<CouponStatus, string> = {
  active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  redeemed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
  expired: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
  cancelled: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
}

const formatAmount = (coupon: Pick<AdminCoupon, 'amount' | 'coupon_type'>) => {
  const value = Number(coupon.amount)
  return coupon.coupon_type === 'points'
    ? value.toLocaleString('th-TH', { maximumFractionDigits: 0 })
    : value.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const unitOf = (type: CouponType) => (type === 'points' ? 'แต้ม' : 'บาท')

const formatDate = (iso: string | null) => {
  if (!iso) return '-'
  return new Date(iso).toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' })
}

const fetchCoupons = async () => {
  isLoading.value = true
  loadError.value = ''
  try {
    const response = await couponsApi.list({
      page: page.value,
      per_page: 12,
      status: filters.status,
      type: filters.type,
      search: filters.search.trim()
    })
    coupons.value = response.data.data
    summary.value = response.summary
    lastPage.value = response.data.meta.last_page
    total.value = response.data.meta.total
  } catch (error: any) {
    coupons.value = []
    loadError.value = couponErrorMessage(error, 'โหลดรายการคูปองไม่สำเร็จ')
  } finally {
    isLoading.value = false
  }
}

let searchTimer: ReturnType<typeof setTimeout> | undefined
watch(() => filters.search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    fetchCoupons()
  }, 400)
})
watch(() => [filters.status, filters.type], () => {
  page.value = 1
  fetchCoupons()
})
onBeforeUnmount(() => clearTimeout(searchTimer))

const goToPage = (target: number) => {
  if (target < 1 || target > lastPage.value || target === page.value) return
  page.value = target
  fetchCoupons()
}

const copyCode = async (coupon: AdminCoupon) => {
  try {
    await navigator.clipboard.writeText(coupon.coupon_code)
    copiedId.value = coupon.id
    setTimeout(() => {
      if (copiedId.value === coupon.id) copiedId.value = null
    }, 1500)
  } catch {
    Swal.fire('คัดลอกไม่สำเร็จ', `รหัสคูปอง: ${coupon.coupon_code}`, 'info')
  }
}

// ชื่อผู้สร้างเป็นข้อความที่สมาชิกตั้งเอง — ต้อง escape ก่อนใส่ใน html ของ Swal
const escapeHtml = (value: string) =>
  value.replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch] as string))

const cancelCoupon = async (coupon: AdminCoupon) => {
  if (cancellingId.value) return
  const creatorName = escapeHtml(coupon.creator?.name ?? 'ผู้สร้างคูปอง')
  const confirm = await Swal.fire({
    title: 'ยกเลิกคูปองนี้?',
    html: `รหัส <b>${escapeHtml(coupon.coupon_code)}</b> จะแลกไม่ได้อีก<br>ระบบคืน <b>${formatAmount(coupon)} ${unitOf(coupon.coupon_type)}</b> ให้ ${creatorName}`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ยกเลิกคูปอง',
    cancelButtonText: 'ไม่ใช่ตอนนี้',
    confirmButtonColor: '#dc2626'
  })
  if (!confirm.isConfirmed) return

  cancellingId.value = coupon.id
  try {
    const response = await couponsApi.cancel(coupon.id)
    Swal.fire({ title: 'ยกเลิกแล้ว', text: response.message, icon: 'success', timer: 1800, showConfirmButton: false })
    await fetchCoupons()
  } catch (error: any) {
    Swal.fire('ยกเลิกไม่สำเร็จ', couponErrorMessage(error), 'error')
  } finally {
    cancellingId.value = null
  }
}

onMounted(fetchCoupons)
</script>

<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
      <div class="min-w-0">
        <h1 class="text-xl font-bold text-slate-800 dark:text-white sm:text-2xl">จัดการคูปอง</h1>
        <p class="mt-1 break-words text-sm text-slate-500 dark:text-slate-400 sm:text-base">
          บัตรกำนัลแต้ม/เงินแบบใช้ครั้งเดียว ผู้รับกรอกรหัสที่หน้า “แลกคูปอง”
        </p>
      </div>
      <NuxtLink
        to="/nuxnan-admin/coupons/create"
        class="inline-flex min-h-[44px] w-full flex-shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-hopeui-primary-500 px-4 py-2 text-white transition-colors hover:bg-hopeui-primary-600 sm:w-auto"
      >
        <Icon icon="fluent:add-24-regular" class="h-5 w-5" />
        สร้างคูปอง
      </NuxtLink>
    </div>

    <!-- Summary -->
    <div v-if="summary" class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
      <div class="rounded-2xl border border-slate-100 bg-white p-3 shadow-hopeui dark:border-slate-700 dark:bg-slate-800 sm:p-4">
        <p class="text-xs text-slate-500 dark:text-slate-400 sm:text-sm">ยังแลกได้</p>
        <p class="mt-1 text-lg font-bold text-slate-800 dark:text-white sm:text-2xl">
          {{ summary.active_count.toLocaleString('th-TH') }} <span class="text-xs font-normal text-slate-500 sm:text-sm">ใบ</span>
        </p>
      </div>
      <div class="rounded-2xl border border-slate-100 bg-white p-3 shadow-hopeui dark:border-slate-700 dark:bg-slate-800 sm:p-4">
        <p class="text-xs text-slate-500 dark:text-slate-400 sm:text-sm">แต้มค้างในคูปอง</p>
        <p class="mt-1 break-words text-lg font-bold text-slate-800 dark:text-white sm:text-2xl">
          {{ formatAmount({ amount: summary.active_points, coupon_type: 'points' }) }}
        </p>
      </div>
      <div class="rounded-2xl border border-slate-100 bg-white p-3 shadow-hopeui dark:border-slate-700 dark:bg-slate-800 sm:p-4">
        <p class="text-xs text-slate-500 dark:text-slate-400 sm:text-sm">เงินค้างในคูปอง</p>
        <p class="mt-1 break-words text-lg font-bold text-slate-800 dark:text-white sm:text-2xl">
          ฿{{ formatAmount({ amount: summary.active_wallet, coupon_type: 'wallet' }) }}
        </p>
      </div>
      <div class="rounded-2xl border border-slate-100 bg-white p-3 shadow-hopeui dark:border-slate-700 dark:bg-slate-800 sm:p-4">
        <p class="text-xs text-slate-500 dark:text-slate-400 sm:text-sm">แลกแล้ว</p>
        <p class="mt-1 text-lg font-bold text-slate-800 dark:text-white sm:text-2xl">
          {{ summary.redeemed_count.toLocaleString('th-TH') }} <span class="text-xs font-normal text-slate-500 sm:text-sm">ใบ</span>
        </p>
      </div>
    </div>

    <!-- Filters -->
    <div class="rounded-2xl border border-slate-100 bg-white p-3 shadow-hopeui dark:border-slate-700 dark:bg-slate-800 sm:p-4">
      <div class="flex flex-col gap-3 md:flex-row">
        <div class="relative min-w-0 flex-1">
          <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
          <input
            v-model="filters.search"
            type="search"
            inputmode="search"
            placeholder="ค้นหารหัส คำอธิบาย หรือชื่อผู้สร้าง"
            class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
          />
        </div>
        <div class="grid grid-cols-2 gap-3 md:flex md:flex-shrink-0">
          <select
            v-model="filters.status"
            aria-label="กรองตามสถานะ"
            class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white md:w-40"
          >
            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
          <select
            v-model="filters.type"
            aria-label="กรองตามประเภท"
            class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white md:w-40"
          >
            <option v-for="option in typeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="p-8 text-center">
      <Icon icon="fluent:spinner-ios-20-regular" class="mx-auto h-8 w-8 animate-spin text-hopeui-primary-600" />
      <p class="mt-2 text-slate-500">กำลังโหลดข้อมูล...</p>
    </div>

    <!-- Error -->
    <div
      v-else-if="loadError"
      class="flex flex-col items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400 sm:flex-row sm:items-center"
    >
      <Icon icon="fluent:error-circle-24-filled" class="h-6 w-6 flex-shrink-0" />
      <p class="min-w-0 flex-1 break-words">{{ loadError }}</p>
      <button
        type="button"
        class="inline-flex min-h-[44px] flex-shrink-0 items-center justify-center whitespace-nowrap rounded-xl bg-white px-4 text-sm font-medium text-red-700 hover:bg-red-100 dark:bg-slate-800 dark:text-red-400"
        @click="fetchCoupons"
      >
        ลองใหม่
      </button>
    </div>

    <!-- Empty -->
    <div v-else-if="coupons.length === 0" class="rounded-2xl border border-dashed border-slate-200 p-8 text-center dark:border-slate-700">
      <Icon icon="fluent:ticket-diagonal-24-regular" class="mx-auto h-12 w-12 text-slate-300" />
      <p class="mt-2 text-slate-500">ไม่พบคูปอง</p>
    </div>

    <!-- Coupons -->
    <template v-else>
      <div class="grid grid-cols-1 gap-3 md:grid-cols-2 md:gap-4 xl:grid-cols-3">
        <article
          v-for="coupon in coupons"
          :key="coupon.id"
          class="flex flex-col rounded-2xl border border-slate-100 bg-white p-4 shadow-hopeui transition-shadow hover:shadow-hopeui-md dark:border-slate-700 dark:bg-slate-800 sm:p-5"
        >
          <div class="flex items-start gap-3">
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <span
                  class="whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium"
                  :class="coupon.coupon_type === 'points'
                    ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'
                    : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'"
                >
                  {{ coupon.type_label }}
                </span>
                <span class="whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium" :class="statusBadge[coupon.display_status]">
                  {{ coupon.status_label }}
                </span>
              </div>
              <p class="mt-2 break-words text-2xl font-bold text-slate-800 dark:text-white">
                {{ formatAmount(coupon) }}
                <span class="text-sm font-normal text-slate-500">{{ unitOf(coupon.coupon_type) }}</span>
              </p>
            </div>
            <div class="flex-shrink-0 rounded-xl bg-hopeui-primary-100 p-2 dark:bg-hopeui-primary-900/30">
              <Icon
                :icon="coupon.coupon_type === 'points' ? 'fluent:star-24-regular' : 'fluent:wallet-24-regular'"
                class="h-6 w-6 text-hopeui-primary-600"
              />
            </div>
          </div>

          <!-- Code -->
          <div class="mt-3 flex items-center gap-2 rounded-xl bg-slate-50 p-2 pl-3 dark:bg-slate-700/50">
            <code class="min-w-0 flex-1 break-all font-mono text-lg font-bold tracking-widest text-hopeui-primary-600 dark:text-hopeui-primary-400">
              {{ coupon.coupon_code }}
            </code>
            <button
              type="button"
              class="inline-flex min-h-[44px] min-w-[44px] flex-shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-white hover:text-hopeui-primary-600 dark:hover:bg-slate-600"
              :aria-label="`คัดลอกรหัส ${coupon.coupon_code}`"
              @click="copyCode(coupon)"
            >
              <Icon :icon="copiedId === coupon.id ? 'fluent:checkmark-24-regular' : 'fluent:copy-24-regular'" class="h-5 w-5" />
            </button>
          </div>

          <!-- Details -->
          <dl class="mt-3 flex-1 space-y-2 text-sm">
            <div class="flex items-start gap-3">
              <dt class="flex-shrink-0 whitespace-nowrap text-slate-500">ผู้สร้าง</dt>
              <dd class="min-w-0 flex-1 break-words text-right text-slate-800 dark:text-white">
                {{ coupon.creator?.name ?? '-' }}
                <span
                  v-if="coupon.creator"
                  class="ml-1 inline-block whitespace-nowrap rounded-full px-1.5 py-0.5 text-[11px]"
                  :class="coupon.creator.is_plearnd_admin
                    ? 'bg-hopeui-primary-100 text-hopeui-primary-700 dark:bg-hopeui-primary-900/30 dark:text-hopeui-primary-300'
                    : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
                >
                  {{ coupon.creator.is_plearnd_admin ? 'แอดมิน' : 'สมาชิก' }}
                </span>
              </dd>
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
            <p v-if="coupon.description" class="break-words rounded-lg bg-slate-50 p-2 text-slate-600 dark:bg-slate-700/50 dark:text-slate-300">
              {{ coupon.description }}
            </p>
          </dl>

          <!-- Actions -->
          <div class="mt-4 flex gap-2 border-t border-slate-100 pt-4 dark:border-slate-700">
            <NuxtLink
              :to="`/nuxnan-admin/coupons/${coupon.id}/edit`"
              class="inline-flex min-h-[44px] min-w-0 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-slate-100 px-3 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600"
            >
              <Icon :icon="coupon.can_edit ? 'fluent:edit-24-regular' : 'fluent:document-search-24-regular'" class="h-5 w-5 flex-shrink-0" />
              {{ coupon.can_edit ? 'แก้ไข' : 'รายละเอียด' }}
            </NuxtLink>
            <button
              v-if="coupon.can_cancel"
              type="button"
              :disabled="cancellingId === coupon.id"
              class="inline-flex min-h-[44px] min-w-0 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-red-50 px-3 text-sm font-medium text-red-600 transition-colors hover:bg-red-100 disabled:opacity-60 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40"
              @click="cancelCoupon(coupon)"
            >
              <Icon
                :icon="cancellingId === coupon.id ? 'fluent:spinner-ios-20-regular' : 'fluent:dismiss-circle-24-regular'"
                class="h-5 w-5 flex-shrink-0"
                :class="{ 'animate-spin': cancellingId === coupon.id }"
              />
              ยกเลิกคูปอง
            </button>
          </div>
        </article>
      </div>

      <!-- Pagination -->
      <div v-if="lastPage > 1" class="flex items-center justify-between gap-2 sm:justify-center sm:gap-4">
        <button
          type="button"
          :disabled="page <= 1"
          class="inline-flex min-h-[44px] flex-shrink-0 items-center gap-1 whitespace-nowrap rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-40 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
          @click="goToPage(page - 1)"
        >
          <Icon icon="fluent:chevron-left-24-regular" class="h-5 w-5" />
          ก่อนหน้า
        </button>
        <span class="min-w-0 text-center text-sm text-slate-500">หน้า {{ page }} / {{ lastPage }} · {{ total.toLocaleString('th-TH') }} ใบ</span>
        <button
          type="button"
          :disabled="page >= lastPage"
          class="inline-flex min-h-[44px] flex-shrink-0 items-center gap-1 whitespace-nowrap rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-40 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
          @click="goToPage(page + 1)"
        >
          ถัดไป
          <Icon icon="fluent:chevron-right-24-regular" class="h-5 w-5" />
        </button>
      </div>
    </template>
  </div>
</template>
