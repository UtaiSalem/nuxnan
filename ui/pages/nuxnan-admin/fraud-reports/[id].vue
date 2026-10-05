<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const { parseApiError } = useApiError()
const swal = useSweetAlert()

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string
const route = useRoute()
const reportId = computed(() => route.params.id as string)

const CATEGORY_LABELS: Record<string, string> = {
  money_fraud: 'ฉ้อโกงเงิน / Wallet',
  point_fraud: 'ทุจริตแต้ม',
  scam: 'หลอกลวง',
  phishing: 'ฟิชชิง',
  fake_account: 'บัญชีปลอม',
  other: 'อื่น ๆ'
}

const STATUS_META: Record<string, { label: string; class: string }> = {
  pending: { label: 'รอตรวจสอบ', class: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' },
  reviewing: { label: 'กำลังตรวจสอบ', class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
  action_taken: { label: 'ดำเนินการแล้ว', class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
  dismissed: { label: 'ยกเลิก', class: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }
}

const report = ref<any>(null)
const isLoading = ref(true)
const errorMessage = ref('')

const adminNote = ref('')
const isUpdatingStatus = ref(false)

const suspendForm = ref({ points: true, wallet: true, reason: '' })
const isSuspending = ref(false)

const authHeaders = () => {
  const token = useCookie('token')
  return { Authorization: `Bearer ${token.value}` }
}

const categoryLabel = (c?: string) => (c ? CATEGORY_LABELS[c] || c : '-')
const statusMeta = (s?: string) => (s && STATUS_META[s]) || { label: s || '-', class: 'bg-slate-100 text-slate-600' }
const userName = (u?: any) => u?.name || u?.username || u?.email || (u ? `#${u.id}` : '-')

const reportedUser = computed(() => report.value?.reported_user || null)
const isResolved = computed(() => ['action_taken', 'dismissed'].includes(report.value?.status))
const isSuspended = computed(() => reportedUser.value?.points_suspended || reportedUser.value?.wallet_suspended)

const formatDate = (dateStr?: string | null) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleString('th-TH', {
    year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

const fetchReport = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await $fetch<any>(`${apiBase}/api/admin/fraud-reports/${reportId.value}`, { headers: authHeaders() })
    if (response.success) {
      report.value = response.data
      adminNote.value = response.data.admin_note || ''
      suspendForm.value.reason = `ระงับจากคำร้องเรียนทุจริต #${response.data.id}`
    }
  } catch (error: any) {
    console.error('Failed to fetch report:', error)
    errorMessage.value = parseApiError(error, 'ไม่สามารถโหลดคำร้องเรียนได้').message
  } finally {
    isLoading.value = false
  }
}

const updateStatus = async (status: string) => {
  isUpdatingStatus.value = true
  try {
    const response = await $fetch<any>(`${apiBase}/api/admin/fraud-reports/${reportId.value}/status`, {
      method: 'POST',
      headers: authHeaders(),
      body: { status, admin_note: adminNote.value || undefined }
    })
    if (response.success) {
      await fetchReport()
      swal.success('อัพเดทสถานะคำร้องเรียนเรียบร้อยแล้ว')
    }
  } catch (error: any) {
    const parsed = parseApiError(error, 'ไม่สามารถอัพเดทสถานะได้')
    swal.error(parsed.message, 'เกิดข้อผิดพลาด', parsed.detail)
  } finally {
    isUpdatingStatus.value = false
  }
}

const suspendAccount = async () => {
  if (!suspendForm.value.points && !suspendForm.value.wallet) {
    swal.warning('ต้องเลือกระงับอย่างน้อยหนึ่งระบบ (แต้ม หรือ Wallet)')
    return
  }

  const confirmed = await swal.confirm(
    `ระงับบัญชี <b>${userName(reportedUser.value)}</b> และปิดคำร้องเรียนนี้เป็น "ดำเนินการแล้ว"?`,
    'ยืนยันการระงับบัญชี',
    { icon: 'warning', isDanger: true, confirmText: 'ระงับบัญชี' }
  )
  if (!confirmed) return

  isSuspending.value = true
  try {
    const response = await $fetch<any>(`${apiBase}/api/admin/fraud-reports/${reportId.value}/suspend`, {
      method: 'POST',
      headers: authHeaders(),
      body: {
        points: suspendForm.value.points,
        wallet: suspendForm.value.wallet,
        reason: suspendForm.value.reason || undefined
      }
    })
    if (response.success) {
      await fetchReport()
      swal.success('ระงับบัญชีและปิดคำร้องเรียนเรียบร้อยแล้ว')
    }
  } catch (error: any) {
    const parsed = parseApiError(error, 'ไม่สามารถระงับบัญชีได้')
    swal.error(parsed.message, 'เกิดข้อผิดพลาด', parsed.detail)
  } finally {
    isSuspending.value = false
  }
}

onMounted(fetchReport)
</script>

<template>
  <div class="space-y-6">
    <!-- Back -->
    <NuxtLink
      to="/nuxnan-admin/fraud-reports"
      class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200"
    >
      <Icon icon="fluent:arrow-left-24-regular" class="w-4 h-4" />
      กลับไปรายการคำร้องเรียน
    </NuxtLink>

    <div v-if="isLoading" class="p-8 text-center bg-white dark:bg-slate-800 rounded-2xl shadow-hopeui border border-slate-100 dark:border-slate-700">
      <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-hopeui-primary-600 animate-spin mx-auto" />
      <p class="text-slate-500 mt-2">กำลังโหลด...</p>
    </div>

    <div v-else-if="errorMessage" class="p-8 text-center bg-white dark:bg-slate-800 rounded-2xl shadow-hopeui border border-slate-100 dark:border-slate-700">
      <Icon icon="fluent:error-circle-24-regular" class="w-12 h-12 text-red-400 mx-auto" />
      <p class="text-red-500 mt-2">{{ errorMessage }}</p>
    </div>

    <template v-else-if="report">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="min-w-0">
          <h1 class="text-2xl font-bold text-slate-800 dark:text-white break-words">คำร้องเรียน #{{ report.id }}</h1>
          <p class="text-slate-500 dark:text-slate-400 mt-1">{{ categoryLabel(report.category) }}</p>
        </div>
        <span class="px-3 py-1.5 text-sm font-medium rounded-xl self-start" :class="statusMeta(report.status).class">
          {{ statusMeta(report.status).label }}
        </span>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: details -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Reported account -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-5 shadow-hopeui border border-slate-100 dark:border-slate-700">
            <h2 class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-3">บัญชีที่ถูกร้องเรียน</h2>
            <div class="flex flex-wrap items-center gap-2">
              <span class="text-lg font-bold text-slate-800 dark:text-white break-words">{{ userName(reportedUser) }}</span>
              <span v-if="reportedUser?.points_suspended" class="px-2 py-0.5 text-xs rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">ระงับแต้ม</span>
              <span v-if="reportedUser?.wallet_suspended" class="px-2 py-0.5 text-xs rounded-lg bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">ระงับ Wallet</span>
            </div>
            <p v-if="reportedUser?.email" class="text-sm text-slate-500 dark:text-slate-400 mt-1 break-words">{{ reportedUser.email }}</p>
            <NuxtLink
              v-if="reportedUser"
              :to="`/nuxnan-admin/users/${reportedUser.id}`"
              class="mt-3 min-h-[44px] sm:min-h-0 inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl text-sm text-slate-700 dark:text-slate-300"
            >
              <Icon icon="fluent:person-24-regular" class="w-4 h-4" />
              ดูโปรไฟล์บัญชี
            </NuxtLink>
          </div>

          <!-- Description -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-5 shadow-hopeui border border-slate-100 dark:border-slate-700">
            <h2 class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-2">รายละเอียด</h2>
            <p class="text-slate-800 dark:text-slate-100 whitespace-pre-wrap break-words">{{ report.description }}</p>

            <template v-if="report.evidence_note">
              <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 mt-4 mb-2">หลักฐานเพิ่มเติม</h3>
              <p class="text-slate-700 dark:text-slate-200 whitespace-pre-wrap break-words">{{ report.evidence_note }}</p>
            </template>

            <template v-if="report.related_transaction_id">
              <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 mt-4 mb-2">ธุรกรรมที่เกี่ยวข้อง</h3>
              <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-700/50 text-sm text-slate-700 dark:text-slate-200 break-words">
                {{ report.related_transaction_type === 'wallet' ? 'Wallet' : 'แต้ม' }} #{{ report.related_transaction_id }}
                <template v-if="report.related_transaction">
                  — จำนวน {{ report.related_transaction.amount ?? '-' }}
                </template>
              </div>
            </template>
          </div>
        </div>

        <!-- Right: actions -->
        <div class="space-y-6">
          <!-- Meta -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-5 shadow-hopeui border border-slate-100 dark:border-slate-700 space-y-2 text-sm">
            <div class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">ผู้ร้องเรียน</span>
              <span class="text-slate-800 dark:text-white text-right break-words">{{ userName(report.reporter) }}</span>
            </div>
            <div class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">วันที่</span>
              <span class="text-slate-800 dark:text-white text-right">{{ formatDate(report.created_at) }}</span>
            </div>
            <div v-if="report.handled_by" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">ผู้ดูแลที่จัดการ</span>
              <span class="text-slate-800 dark:text-white text-right break-words">{{ userName(report.handled_by) }}</span>
            </div>
            <div v-if="report.resolved_at" class="flex justify-between gap-2">
              <span class="text-slate-500 dark:text-slate-400">ปิดเมื่อ</span>
              <span class="text-slate-800 dark:text-white text-right">{{ formatDate(report.resolved_at) }}</span>
            </div>
          </div>

          <!-- Triage -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-5 shadow-hopeui border border-slate-100 dark:border-slate-700 space-y-3">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">จัดการคำร้องเรียน</h2>
            <textarea
              v-model="adminNote"
              rows="3"
              maxlength="1000"
              placeholder="บันทึกของผู้ดูแล (ไม่บังคับ)"
              class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 resize-y break-words"
            ></textarea>
            <div class="flex flex-col gap-2">
              <button
                @click="updateStatus('reviewing')"
                :disabled="isUpdatingStatus || report.status === 'reviewing'"
                class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-xl text-sm font-semibold disabled:opacity-50"
              >
                <Icon icon="fluent:eye-24-regular" class="w-5 h-5" />
                กำลังตรวจสอบ
              </button>
              <button
                @click="updateStatus('dismissed')"
                :disabled="isUpdatingStatus"
                class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl text-sm font-semibold disabled:opacity-50"
              >
                <Icon icon="fluent:dismiss-circle-24-regular" class="w-5 h-5" />
                ยกเลิกคำร้อง (ไม่พบความผิด)
              </button>
            </div>
          </div>

          <!-- Suspend -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-5 shadow-hopeui border border-red-100 dark:border-red-900/40 space-y-3">
            <h2 class="text-sm font-semibold text-red-600 dark:text-red-400 flex items-center gap-1.5">
              <Icon icon="fluent:shield-error-24-filled" class="w-5 h-5" />
              ระงับบัญชีธุรกรรม
            </h2>
            <div v-if="isSuspended" class="p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 text-sm">
              บัญชีนี้ถูกระงับอยู่แล้ว
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
              <input v-model="suspendForm.points" type="checkbox" class="w-4 h-4 rounded" />
              ระงับระบบแต้ม
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
              <input v-model="suspendForm.wallet" type="checkbox" class="w-4 h-4 rounded" />
              ระงับระบบ Wallet
            </label>
            <textarea
              v-model="suspendForm.reason"
              rows="2"
              maxlength="1000"
              placeholder="เหตุผลในการระงับ"
              class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500 resize-y break-words"
            ></textarea>
            <button
              @click="suspendAccount"
              :disabled="isSuspending"
              class="w-full min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-bold disabled:opacity-50"
            >
              <Icon v-if="isSuspending" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
              <Icon v-else icon="fluent:shield-error-24-filled" class="w-5 h-5" />
              ระงับบัญชี และปิดคำร้อง
            </button>
            <p class="text-xs text-slate-400">การระงับจะปิดคำร้องเรียนนี้เป็น "ดำเนินการแล้ว" และบันทึกลงประวัติ Blacklist</p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
