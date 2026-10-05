<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

const { parseApiError } = useApiError()
const swal = useSweetAlert()

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string
const route = useRoute()

const userId = route.params.id as string

// State
const user = ref<any>(null)
const isLoading = ref(true)
const error = ref('')
const isVerifying = ref(false)
const verifyMessage = ref('')
const verifyError = ref('')

// Fetch user data
const fetchUser = async () => {
  isLoading.value = true
  error.value = ''
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/users/${userId}`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })
    
    if (response.success) {
      user.value = response.data
    }
  } catch (err: any) {
    console.error('Failed to fetch user:', err)
    error.value = err.data?.message || 'ไม่สามารถโหลดข้อมูลผู้ใช้ได้'
  } finally {
    isLoading.value = false
  }
}

// Get status badge class
const getStatusBadge = (status: string) => {
  const badges: Record<string, string> = {
    active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    inactive: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
    suspended: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
  }
  return badges[status] || badges.inactive
}

// Get role badge class
const getRoleBadge = (role: string) => {
  const badges: Record<string, string> = {
    admin: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    instructor: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    user: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
  }
  return badges[role] || badges.user
}

// Format date
const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Verify email
const verifyEmail = async () => {
  if (!await swal.confirm('ต้องการยืนยันอีเมลของผู้ใช้นี้หรือไม่?', 'ยืนยันอีเมล', { icon: 'question', confirmText: 'ยืนยัน' })) return
  
  isVerifying.value = true
  verifyMessage.value = ''
  verifyError.value = ''
  
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/users/${userId}/verify-email`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })
    
    if (response.success) {
      verifyMessage.value = response.message
      user.value.email_verified_at = response.data.email_verified_at
    }
  } catch (err: any) {
    console.error('Failed to verify email:', err)
    verifyError.value = err.data?.message || 'ไม่สามารถยืนยันอีเมลได้'
  } finally {
    isVerifying.value = false
  }
}

// Unverify email
const unverifyEmail = async () => {
  if (!await swal.confirm('ต้องการยกเลิกการยืนยันอีเมลของผู้ใช้นี้หรือไม่?', 'ยกเลิกการยืนยันอีเมล', { icon: 'warning', isDanger: true, confirmText: 'ยกเลิกการยืนยัน' })) return
  
  isVerifying.value = true
  verifyMessage.value = ''
  verifyError.value = ''
  
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/users/${userId}/unverify-email`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })
    
    if (response.success) {
      verifyMessage.value = response.message
      user.value.email_verified_at = null
    }
  } catch (err: any) {
    console.error('Failed to unverify email:', err)
    verifyError.value = err.data?.message || 'ไม่สามารถยกเลิกการยืนยันอีเมลได้'
  } finally {
    isVerifying.value = false
  }
}

// ── Economy suspension (blacklist) ───────────────────────────────────
const suspendForm = ref<{ points: boolean; wallet: boolean; reason: string }>({
  points: true,
  wallet: true,
  reason: ''
})
const isSuspending = ref(false)

const submitSuspend = async () => {
  if (!suspendForm.value.points && !suspendForm.value.wallet) {
    swal.warning('ต้องเลือกระงับอย่างน้อยหนึ่งระบบ (แต้ม หรือ Wallet)')
    return
  }

  const confirmed = await swal.confirm(
    'ยืนยันการระงับระบบแต้ม/Wallet ของผู้ใช้นี้?',
    'ยืนยันการระงับบัญชี',
    { icon: 'warning', isDanger: true, confirmText: 'ระงับบัญชี' }
  )
  if (!confirmed) return

  isSuspending.value = true
  try {
    const token = useCookie('token')
    const response = await $fetch<any>(`${apiBase}/api/admin/users/${userId}/suspend-economy`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}` },
      body: {
        points: suspendForm.value.points,
        wallet: suspendForm.value.wallet,
        reason: suspendForm.value.reason || undefined
      }
    })
    if (response.success) {
      user.value = { ...user.value, ...response.data }
      fetchSuspensionAudits()
      swal.success('ระงับบัญชีเรียบร้อยแล้ว')
    }
  } catch (err: any) {
    console.error('Suspend failed:', err)
    const parsed = parseApiError(err, 'ไม่สามารถระงับบัญชีได้')
    swal.error(parsed.message, 'เกิดข้อผิดพลาด', parsed.detail)
  } finally {
    isSuspending.value = false
  }
}

const submitRestore = async () => {
  const confirmed = await swal.confirm(
    'ยกเลิกการระงับระบบแต้ม/Wallet ของผู้ใช้นี้?',
    'ยืนยันการยกเลิกการระงับ',
    { icon: 'question', confirmText: 'ยกเลิกการระงับ' }
  )
  if (!confirmed) return

  isSuspending.value = true
  try {
    const token = useCookie('token')
    const response = await $fetch<any>(`${apiBase}/api/admin/users/${userId}/restore-economy`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}` }
    })
    if (response.success) {
      user.value = { ...user.value, ...response.data }
      fetchSuspensionAudits()
      swal.success('ยกเลิกการระงับเรียบร้อยแล้ว')
    }
  } catch (err: any) {
    console.error('Restore failed:', err)
    const parsed = parseApiError(err, 'ไม่สามารถยกเลิกการระงับได้')
    swal.error(parsed.message, 'เกิดข้อผิดพลาด', parsed.detail)
  } finally {
    isSuspending.value = false
  }
}

// ── Suspension audit history (per user) ──────────────────────────────
const suspensionAudits = ref<any[]>([])
const auditsLoading = ref(false)

const fetchSuspensionAudits = async () => {
  auditsLoading.value = true
  try {
    const token = useCookie('token')
    const response = await $fetch<any>(`${apiBase}/api/admin/users/${userId}/suspension-audits?per_page=10`, {
      headers: { Authorization: `Bearer ${token.value}` }
    })
    if (response.success) {
      suspensionAudits.value = response.data.data || []
    }
  } catch (err) {
    console.error('Failed to fetch suspension audits:', err)
    suspensionAudits.value = []
  } finally {
    auditsLoading.value = false
  }
}

const auditActorName = (a: any) => a.performed_by?.name || a.performed_by?.username || 'ระบบ'

const formatAuditDate = (dateStr?: string) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleString('th-TH', {
    year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

// ── Transfer transactions (money + points) ───────────────────────────
// Only a SUPER_ADMIN may claw back a transfer (the backend also enforces this).
const authStore = useAuthStore()
const canReverse = computed(() => !!authStore.user?.is_super_admin)

const walletTransfers = ref<any[]>([])
const pointsTransfers = ref<any[]>([])
const transfersLoading = ref(false)
const transfersError = ref('')
const reversingKey = ref('') // `${kind}-${id}` while a reversal is in flight

const formatPointsAmount = (n: any) => {
  const num = typeof n === 'string' ? parseFloat(n) : n
  return new Intl.NumberFormat('th-TH').format(num || 0) + ' แต้ม'
}
const formatMoney = (n: any) => {
  const num = typeof n === 'string' ? parseFloat(n) : n
  return '฿' + new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0)
}
const formatAmount = (tx: any) => (tx._kind === 'points' ? formatPointsAmount(tx.amount) : formatMoney(tx.amount))
const counterpartyName = (tx: any) =>
  tx.counterparty?.name || tx.counterparty?.username || (tx.counterparty?.id ? `ผู้ใช้ #${tx.counterparty.id}` : 'ไม่ทราบ')

// Merge both ledgers into one newest-first list, tagged with its unit (_kind).
const allTransfers = computed(() => {
  const w = walletTransfers.value.map((t) => ({ ...t, _kind: 'wallet' as const }))
  const p = pointsTransfers.value.map((t) => ({ ...t, _kind: 'points' as const }))
  return [...w, ...p].sort(
    (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
  )
})

const fetchTransfers = async () => {
  transfersLoading.value = true
  transfersError.value = ''
  try {
    const token = useCookie('token')
    const headers = { Authorization: `Bearer ${token.value}` }
    const [walletRes, pointsRes] = await Promise.all([
      $fetch<any>(`${apiBase}/api/admin/wallet/users/${userId}/wallet-transactions`, {
        params: { type: 'transfers', per_page: 50 }, headers
      }),
      $fetch<any>(`${apiBase}/api/admin/wallet/users/${userId}/points-transactions`, {
        params: { type: 'transfers', per_page: 50 }, headers
      })
    ])
    walletTransfers.value = walletRes?.success ? (walletRes.data.transactions || []) : []
    pointsTransfers.value = pointsRes?.success ? (pointsRes.data.transactions || []) : []
  } catch (err: any) {
    console.error('Failed to fetch transfers:', err)
    transfersError.value = err.data?.message || 'ไม่สามารถโหลดประวัติการโอนได้'
  } finally {
    transfersLoading.value = false
  }
}

const reverseTransfer = async (kind: 'points' | 'wallet', tx: any) => {
  const unitLabel = kind === 'points' ? 'แต้ม' : 'เงิน'
  const reason = await swal.input(`ยกเลิก + ดึง${unitLabel}คืน (รายการ #${tx.id})`, {
    inputType: 'textarea',
    placeholder: `ระบุเหตุผล เช่น ตรวจพบการทุจริต — ระบบจะดึง${unitLabel}คืนจากผู้รับเท่าที่มี แล้วคืนให้ผู้โอน`,
    confirmText: 'ยืนยันการดึงคืน',
    inputValidator: (v: string) => (!v || !v.trim() ? 'กรุณาระบุเหตุผล' : null)
  })
  if (!reason || !reason.trim()) return

  reversingKey.value = `${kind}-${tx.id}`
  try {
    const token = useCookie('token')
    const path = kind === 'points'
      ? `points-transactions/${tx.id}/reverse`
      : `transactions/${tx.id}/reverse`
    const res = await $fetch<any>(`${apiBase}/api/admin/wallet/${path}`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}` },
      body: { reason: reason.trim() }
    })
    if (res.success) {
      const d = res.data || {}
      const fmt = (n: any) => (kind === 'points' ? formatPointsAmount(n) : formatMoney(n))
      const shortfall = Number(d.shortfall || 0)
      const extra = shortfall > 0 ? ` (ขาด ${fmt(shortfall)} เพราะถูกใช้ไปแล้ว)` : ''
      swal.success(`ย้อนรายการสำเร็จ: ดึงคืน ${fmt(d.reversed)}${extra}`)
      await Promise.all([fetchTransfers(), fetchUser()])
    } else {
      swal.error(res.message || 'ย้อนรายการไม่สำเร็จ')
    }
  } catch (err: any) {
    console.error('Reverse transfer failed:', err)
    const parsed = parseApiError(err, 'ย้อนรายการไม่สำเร็จ')
    swal.error(parsed.message, 'เกิดข้อผิดพลาด', parsed.detail)
  } finally {
    reversingKey.value = ''
  }
}

onMounted(() => {
  fetchUser()
  fetchSuspensionAudits()
  fetchTransfers()
})
</script>

<template>
  <div class="space-y-6 max-w-4xl mx-auto">
    <!-- Page Header -->
    <div class="flex items-center gap-4">
      <NuxtLink
        to="/nuxnan-admin/users"
        class="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition-colors"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
      </NuxtLink>
      <div class="flex-1">
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">รายละเอียดผู้ใช้</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1">ผู้ใช้ #{{ userId }}</p>
      </div>
      <NuxtLink
        v-if="user"
        :to="`/nuxnan-admin/users/${userId}/edit`"
        class="inline-flex items-center gap-2 px-4 py-2 bg-hopeui-primary-500 hover:bg-hopeui-primary-600 rounded-xl text-white transition-colors"
      >
        <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
        แก้ไข
      </NuxtLink>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-8 shadow-hopeui border border-slate-100 dark:border-slate-700">
      <div class="text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-hopeui-primary-600 animate-spin mx-auto" />
        <p class="text-slate-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-8 shadow-hopeui border border-slate-100 dark:border-slate-700">
      <div class="text-center">
        <Icon icon="fluent:error-circle-24-regular" class="w-12 h-12 text-red-400 mx-auto" />
        <p class="text-slate-500 mt-2">{{ error }}</p>
        <button
          @click="fetchUser"
          class="min-h-[44px] sm:min-h-0 mt-4 px-4 py-2 bg-hopeui-primary-500 hover:bg-hopeui-primary-600 rounded-xl text-white transition-colors"
        >
          ลองใหม่อีกครั้ง
        </button>
      </div>
    </div>

    <!-- User Details -->
    <template v-else-if="user">
      <!-- Profile Card -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-6 shadow-hopeui border border-slate-100 dark:border-slate-700">
        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
          <!-- Avatar -->
          <div class="w-24 h-24 bg-gradient-to-br from-hopeui-primary-500 to-purple-600 rounded-full flex items-center justify-center text-white text-3xl font-bold">
            {{ user.name?.charAt(0)?.toUpperCase() || 'U' }}
          </div>
          
          <!-- Info -->
          <div class="flex-1 text-center sm:text-left">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">{{ user.name }}</h2>
            <p class="text-slate-500 dark:text-slate-400">@{{ user.username || 'no-username' }}</p>
            
            <div class="flex flex-wrap justify-center sm:justify-start gap-2 mt-3">
              <span class="px-3 py-1 text-sm rounded-full" :class="getStatusBadge(user.status || 'active')">
                {{ user.status === 'active' ? 'ใช้งาน' : user.status === 'suspended' ? 'ระงับ' : 'ไม่ใช้งาน' }}
              </span>
              <span class="px-3 py-1 text-sm rounded-full" :class="getRoleBadge(user.role || 'user')">
                {{ user.role === 'admin' ? 'ผู้ดูแล' : user.role === 'instructor' ? 'ผู้สอน' : 'ผู้ใช้' }}
              </span>
              <span v-if="user.is_super_admin" class="px-3 py-1 text-sm rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                Super Admin
              </span>
              <span v-if="user.is_plearnd_admin" class="px-3 py-1 text-sm rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">
                Plearnd Admin
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Details Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Contact Info -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-6 shadow-hopeui border border-slate-100 dark:border-slate-700">
          <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
            <Icon icon="fluent:person-24-regular" class="w-5 h-5 text-hopeui-primary-600" />
            ข้อมูลติดต่อ
          </h3>
          
          <div class="space-y-4">
            <div>
              <p class="text-sm text-slate-500 dark:text-slate-400">อีเมล</p>
              <p class="text-slate-800 dark:text-white">{{ user.email || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-500 dark:text-slate-400">เบอร์โทรศัพท์</p>
              <p class="text-slate-800 dark:text-white">{{ user.phone || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-500 dark:text-slate-400">รหัสอ้างอิง</p>
              <p class="text-slate-800 dark:text-white font-mono">{{ user.reference_code || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-500 dark:text-slate-400">รหัสส่วนตัว</p>
              <p class="text-slate-800 dark:text-white font-mono">{{ user.personal_code || '-' }}</p>
            </div>
          </div>
        </div>

        <!-- Account Info -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-6 shadow-hopeui border border-slate-100 dark:border-slate-700">
          <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
            <Icon icon="fluent:calendar-24-regular" class="w-5 h-5 text-hopeui-primary-600" />
            ข้อมูลบัญชี
          </h3>
          
          <div class="space-y-4">
            <div>
              <p class="text-sm text-slate-500 dark:text-slate-400">สร้างเมื่อ</p>
              <p class="text-slate-800 dark:text-white">{{ formatDate(user.created_at) }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-500 dark:text-slate-400">อัปเดตล่าสุด</p>
              <p class="text-slate-800 dark:text-white">{{ formatDate(user.updated_at) }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-500 dark:text-slate-400">ยืนยันอีเมลเมื่อ</p>
              <div class="flex items-center gap-3">
                <p class="text-slate-800 dark:text-white">
                  {{ user.email_verified_at ? formatDate(user.email_verified_at) : 'ยังไม่ยืนยัน' }}
                </p>
                <!-- Verify/Unverify Button -->
                <button
                  v-if="!user.email_verified_at"
                  @click="verifyEmail"
                  :disabled="isVerifying"
                  class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1 px-3 py-1 text-sm bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white rounded-lg transition-colors"
                >
                  <Icon v-if="isVerifying" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
                  <Icon v-else icon="fluent:checkmark-circle-24-regular" class="w-4 h-4" />
                  ยืนยัน
                </button>
                <button
                  v-else
                  @click="unverifyEmail"
                  :disabled="isVerifying"
                  class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1 px-3 py-1 text-sm bg-orange-600 hover:bg-orange-700 disabled:bg-orange-400 text-white rounded-lg transition-colors"
                >
                  <Icon v-if="isVerifying" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
                  <Icon v-else icon="fluent:dismiss-circle-24-regular" class="w-4 h-4" />
                  ยกเลิก
                </button>
              </div>
              <!-- Success/Error Message -->
              <p v-if="verifyMessage" class="text-sm text-green-600 dark:text-green-400 mt-1">{{ verifyMessage }}</p>
              <p v-if="verifyError" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ verifyError }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-500 dark:text-slate-400">เข้าสู่ระบบล่าสุด</p>
              <p class="text-slate-800 dark:text-white">{{ user.last_login_at ? formatDate(user.last_login_at) : '-' }}</p>
            </div>
          </div>
        </div>

        <!-- Wallet & Points -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-6 shadow-hopeui border border-slate-100 dark:border-slate-700">
          <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
            <Icon icon="fluent:wallet-24-regular" class="w-5 h-5 text-hopeui-primary-600" />
            กระเป๋าเงิน & คะแนน
          </h3>
          
          <div class="space-y-4">
            <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
              <span class="text-slate-600 dark:text-slate-300">ยอดเงิน</span>
              <span class="text-lg font-bold text-green-600 dark:text-green-400">
                ฿{{ Number(user.wallet || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
              </span>
            </div>
            <div class="flex justify-between items-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
              <span class="text-slate-600 dark:text-slate-300">คะแนน</span>
              <span class="text-lg font-bold text-purple-600 dark:text-purple-400">
                {{ Number(user.points || 0).toLocaleString('th-TH') }} แต้ม
              </span>
            </div>
          </div>
        </div>

        <!-- Economy Suspension (Blacklist) -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-6 shadow-hopeui border border-slate-100 dark:border-slate-700">
          <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-1 flex items-center gap-2">
            <Icon icon="fluent:shield-error-24-regular" class="w-5 h-5 text-amber-600" />
            ระงับระบบแต้ม / Wallet (Blacklist)
          </h3>
          <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
            ระงับการสะสม/โอน/แปลงแต้ม และการเปลี่ยนแปลง Wallet — ผู้ใช้ยังเรียนได้ตามปกติ
          </p>

          <!-- Current status -->
          <div class="flex flex-wrap items-center gap-2 mb-4">
            <span
              class="px-2.5 py-1 text-xs rounded-lg"
              :class="user.points_suspended
                ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
            >
              แต้ม: {{ user.points_suspended ? 'ถูกระงับ' : 'ปกติ' }}
            </span>
            <span
              class="px-2.5 py-1 text-xs rounded-lg"
              :class="user.wallet_suspended
                ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
            >
              Wallet: {{ user.wallet_suspended ? 'ถูกระงับ' : 'ปกติ' }}
            </span>
          </div>

          <!-- Restore (when already suspended) -->
          <div v-if="user.points_suspended || user.wallet_suspended" class="space-y-3">
            <div v-if="user.economy_suspended_reason" class="p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl text-sm text-slate-600 dark:text-slate-300 break-words">
              เหตุผล: {{ user.economy_suspended_reason }}
            </div>
            <button
              @click="submitRestore"
              :disabled="isSuspending"
              class="w-full min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white rounded-xl transition-colors"
            >
              <Icon v-if="isSuspending" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
              <Icon v-else icon="fluent:arrow-undo-24-regular" class="w-5 h-5" />
              ยกเลิกการระงับ
            </button>
          </div>

          <!-- Suspend form (when active) -->
          <div v-else class="space-y-3">
            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 min-h-[44px]">
              <input v-model="suspendForm.points" type="checkbox" class="w-4 h-4 rounded" />
              ระงับระบบสะสมแต้ม (ห้ามสะสม/โอน/แปลงแต้ม)
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 min-h-[44px]">
              <input v-model="suspendForm.wallet" type="checkbox" class="w-4 h-4 rounded" />
              ระงับระบบ Wallet (ห้ามถอน/โอน/เติม/แปลง)
            </label>
            <textarea
              v-model="suspendForm.reason"
              rows="2"
              placeholder="เหตุผลในการระงับ (เช่น ตรวจพบพฤติกรรมทุจริต)"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 resize-none"
            ></textarea>
            <button
              @click="submitSuspend"
              :disabled="isSuspending"
              class="w-full min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white rounded-xl transition-colors"
            >
              <Icon v-if="isSuspending" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
              <Icon v-else icon="fluent:shield-error-24-regular" class="w-5 h-5" />
              ระงับบัญชี
            </button>
          </div>


          <!-- Audit history -->
          <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-700">
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-2">
              <Icon icon="fluent:history-24-regular" class="w-4 h-4" />
              ประวัติการระงับ
            </p>

            <div v-if="auditsLoading" class="text-sm text-slate-400 py-2">กำลังโหลด...</div>
            <p v-else-if="suspensionAudits.length === 0" class="text-sm text-slate-400 py-2">ยังไม่มีประวัติ</p>
            <ul v-else class="space-y-3">
              <li v-for="a in suspensionAudits" :key="a.id" class="flex items-start gap-2.5">
                <span
                  class="mt-0.5 w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                  :class="a.action === 'suspend'
                    ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                    : 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'"
                >
                  <Icon :icon="a.action === 'suspend' ? 'fluent:shield-error-24-regular' : 'fluent:arrow-undo-24-regular'" class="w-4 h-4" />
                </span>
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-sm font-medium text-slate-800 dark:text-white">
                      {{ a.action === 'suspend' ? 'ระงับ' : 'ปลดระงับ' }}
                    </span>
                    <span v-if="a.action === 'suspend' && a.points_suspended" class="px-1.5 py-0.5 text-xs rounded bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">แต้ม</span>
                    <span v-if="a.action === 'suspend' && a.wallet_suspended" class="px-1.5 py-0.5 text-xs rounded bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">Wallet</span>
                  </div>
                  <p v-if="a.reason" class="text-xs text-slate-600 dark:text-slate-300 mt-0.5 break-words">{{ a.reason }}</p>
                  <p class="text-xs text-slate-400 mt-0.5">{{ formatAuditDate(a.created_at) }} • โดย {{ auditActorName(a) }}</p>
                </div>
              </li>
            </ul>
          </div>
        </div>

        <!-- Statistics -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-6 shadow-hopeui border border-slate-100 dark:border-slate-700">
          <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
            <Icon icon="fluent:data-trending-24-regular" class="w-5 h-5 text-hopeui-primary-600" />
            สถิติ
          </h3>
          
          <div class="grid grid-cols-2 gap-4">
            <div class="text-center p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
              <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ user.courses_count || 0 }}</p>
              <p class="text-sm text-slate-500 dark:text-slate-400">คอร์สที่ลงทะเบียน</p>
            </div>
            <div class="text-center p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
              <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ user.completed_courses || 0 }}</p>
              <p class="text-sm text-slate-500 dark:text-slate-400">คอร์สที่เรียนจบ</p>
            </div>
            <div class="text-center p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
              <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ user.referrals_count || 0 }}</p>
              <p class="text-sm text-slate-500 dark:text-slate-400">ผู้ที่แนะนำ</p>
            </div>
            <div class="text-center p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
              <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ user.login_count || 0 }}</p>
              <p class="text-sm text-slate-500 dark:text-slate-400">จำนวนเข้าสู่ระบบ</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Transfer Transactions (money + points) -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-6 shadow-hopeui border border-slate-100 dark:border-slate-700">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-1">
          <h3 class="text-lg font-semibold text-slate-800 dark:text-white flex items-center gap-2 min-w-0">
            <Icon icon="fluent:arrow-swap-24-regular" class="w-5 h-5 text-hopeui-primary-600 flex-shrink-0" />
            ธุรกรรมการโอน (เงิน &amp; แต้ม)
          </h3>
          <button
            @click="fetchTransfers"
            :disabled="transfersLoading"
            class="flex-shrink-0 min-h-[44px] sm:min-h-0 inline-flex items-center gap-1.5 px-3 py-2 sm:py-1.5 text-sm bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 disabled:opacity-50 text-slate-700 dark:text-slate-300 rounded-lg transition-colors"
          >
            <Icon icon="fluent:arrow-sync-24-regular" class="w-4 h-4" :class="transfersLoading ? 'animate-spin' : ''" />
            รีเฟรช
          </button>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
          รายการโอน/รับโอนเงินและแต้มของผู้ใช้นี้
          <template v-if="canReverse"> — กด "ยกเลิก + ดึงคืน" เพื่อดึงเงิน/แต้มคืนจากผู้รับแล้วคืนให้ผู้โอน</template>
        </p>

        <!-- Loading -->
        <div v-if="transfersLoading && allTransfers.length === 0" class="py-8 text-center">
          <Icon icon="fluent:spinner-ios-20-regular" class="w-7 h-7 text-hopeui-primary-600 animate-spin mx-auto" />
          <p class="text-slate-500 mt-2 text-sm">กำลังโหลดประวัติการโอน...</p>
        </div>

        <!-- Error -->
        <div v-else-if="transfersError" class="py-6 text-center">
          <Icon icon="fluent:error-circle-24-regular" class="w-9 h-9 text-red-400 mx-auto" />
          <p class="text-slate-500 mt-2 text-sm">{{ transfersError }}</p>
          <button
            @click="fetchTransfers"
            class="mt-3 min-h-[44px] sm:min-h-0 px-4 py-2 text-sm bg-hopeui-primary-500 hover:bg-hopeui-primary-600 text-white rounded-xl transition-colors"
          >
            ลองใหม่อีกครั้ง
          </button>
        </div>

        <!-- Empty -->
        <div v-else-if="allTransfers.length === 0" class="py-8 text-center">
          <Icon icon="fluent:arrow-swap-24-regular" class="w-10 h-10 text-slate-300 mx-auto" />
          <p class="text-slate-500 mt-2 text-sm">ยังไม่มีรายการโอน</p>
        </div>

        <!-- List (mobile-first stacked cards) -->
        <ul v-else class="space-y-3">
          <li
            v-for="tx in allTransfers"
            :key="`${tx._kind}-${tx.id}`"
            class="p-3 sm:p-4 rounded-xl border border-slate-100 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-700/30"
          >
            <div class="flex items-start gap-3">
              <!-- Direction icon -->
              <span
                class="mt-0.5 w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                :class="tx.direction === 'in'
                  ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                  : 'bg-slate-200 text-slate-600 dark:bg-slate-600 dark:text-slate-300'"
              >
                <Icon :icon="tx.direction === 'in' ? 'fluent:arrow-download-24-regular' : 'fluent:arrow-upload-24-regular'" class="w-5 h-5" />
              </span>

              <!-- Main info -->
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-1.5">
                  <span class="text-sm font-medium text-slate-800 dark:text-white">
                    {{ tx.direction === 'in' ? 'รับโอน' : 'โอนออก' }}
                  </span>
                  <span
                    class="px-1.5 py-0.5 text-[11px] rounded"
                    :class="tx._kind === 'points'
                      ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
                      : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'"
                  >
                    {{ tx._kind === 'points' ? 'แต้ม' : 'เงิน' }}
                  </span>
                  <span
                    v-if="tx.reversed"
                    class="px-1.5 py-0.5 text-[11px] rounded bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 inline-flex items-center gap-1"
                  >
                    <Icon icon="fluent:arrow-undo-24-regular" class="w-3 h-3" />
                    ย้อนกลับแล้ว
                  </span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 break-words">
                  {{ tx.direction === 'in' ? 'จาก' : 'ถึง' }}
                  <NuxtLink
                    v-if="tx.counterparty?.id"
                    :to="`/nuxnan-admin/users/${tx.counterparty.id}`"
                    class="text-hopeui-primary-600 hover:underline"
                  >{{ counterpartyName(tx) }}</NuxtLink>
                  <span v-else>{{ counterpartyName(tx) }}</span>
                </p>
                <p class="text-xs text-slate-400 mt-0.5">{{ formatDate(tx.created_at) }} • #{{ tx.id }}</p>
                <p v-if="tx.reversed && tx.reversal?.reason" class="text-xs text-amber-600 dark:text-amber-400 mt-1 break-words">
                  เหตุผลการย้อน: {{ tx.reversal.reason }}
                </p>
              </div>

              <!-- Amount -->
              <div class="flex-shrink-0 text-right">
                <p
                  class="text-sm font-semibold whitespace-nowrap"
                  :class="tx.direction === 'in' ? 'text-green-600 dark:text-green-400' : 'text-slate-700 dark:text-slate-300'"
                >
                  {{ tx.direction === 'in' ? '+' : '−' }}{{ formatAmount(tx) }}
                </p>
              </div>
            </div>

            <!-- Action row -->
            <div
              v-if="(canReverse && tx.reversible) || tx.reversed || (canReverse && tx.direction === 'out' && tx.counterparty?.id)"
              class="mt-2 flex justify-end"
            >
              <button
                v-if="canReverse && tx.reversible"
                @click="reverseTransfer(tx._kind, tx)"
                :disabled="reversingKey === `${tx._kind}-${tx.id}`"
                class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1.5 px-3 py-2 sm:py-1.5 text-sm bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white rounded-lg transition-colors"
              >
                <Icon
                  :icon="reversingKey === `${tx._kind}-${tx.id}` ? 'fluent:spinner-ios-20-regular' : 'fluent:arrow-undo-24-regular'"
                  class="w-4 h-4"
                  :class="reversingKey === `${tx._kind}-${tx.id}` ? 'animate-spin' : ''"
                />
                ยกเลิก + ดึงคืน
              </button>
              <span v-else-if="tx.reversed" class="text-xs text-slate-400 self-center">
                ดึงคืนแล้ว<template v-if="tx.reversal?.amount != null"> ({{ tx._kind === 'points' ? formatPointsAmount(tx.reversal.amount) : formatMoney(tx.reversal.amount) }})</template>
              </span>
              <NuxtLink
                v-else-if="canReverse && tx.direction === 'out' && tx.counterparty?.id"
                :to="`/nuxnan-admin/users/${tx.counterparty.id}`"
                class="text-xs text-hopeui-primary-600 hover:underline inline-flex items-center gap-1 self-center"
              >
                ดึงคืนที่โปรไฟล์ผู้รับ
                <Icon icon="fluent:arrow-right-24-regular" class="w-3.5 h-3.5" />
              </NuxtLink>
            </div>
          </li>
        </ul>
      </div>
    </template>
  </div>
</template>
