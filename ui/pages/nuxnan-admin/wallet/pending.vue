<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string

// State
const activeTab = ref('withdrawals')
const pendingWithdrawals = ref([])
const pendingDeposits = ref([])
const isLoading = ref(true)
const currentPage = ref(1)
const totalPages = ref(1)
const totalPendingWithdrawals = ref(0)
const totalPendingDeposits = ref(0)

// Action modals
const showApproveModal = ref(false)
const showRejectModal = ref(false)
const showSlipModal = ref(false)
const selectedRequest = ref(null)
const selectedSlipUrl = ref('')
const isProcessing = ref(false)
const rejectReason = ref('')
const adminNote = ref('')

// Fetch pending withdrawals
const fetchPendingWithdrawals = async () => {
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/wallet/withdrawals/pending`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })

    if (response.success) {
      pendingWithdrawals.value = response.data.withdrawals || response.data.data || response.data
      totalPendingWithdrawals.value = response.data.total || pendingWithdrawals.value.length
    }
  } catch (error) {
    console.error('Failed to fetch pending withdrawals:', error)
    pendingWithdrawals.value = []
    totalPendingWithdrawals.value = 0
  }
}

// Fetch pending deposits
const fetchPendingDeposits = async () => {
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/wallet/deposit-requests/pending`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })

    if (response.success) {
      pendingDeposits.value = response.data.deposit_requests || response.data.data || response.data
      totalPendingDeposits.value = response.data.total || pendingDeposits.value.length
    }
  } catch (error) {
    console.error('Failed to fetch pending deposits:', error)
    pendingDeposits.value = []
    totalPendingDeposits.value = 0
  }
}

// Fetch all pending requests
const fetchPendingRequests = async () => {
  isLoading.value = true
  await Promise.all([
    fetchPendingWithdrawals(),
    fetchPendingDeposits()
  ])
  isLoading.value = false
}

// Open approve modal
const openApproveModal = (request: any) => {
  selectedRequest.value = request
  showApproveModal.value = true
}

// Open reject modal
const openRejectModal = (request: any) => {
  selectedRequest.value = request
  rejectReason.value = ''
  showRejectModal.value = true
}

// Open slip modal
const openSlipModal = (request: any) => {
  selectedSlipUrl.value = request.slip_url || request.transfer_slip
  showSlipModal.value = true
}

// Approve request
const approveRequest = async () => {
  if (!selectedRequest.value) return

  isProcessing.value = true
  try {
    const token = useCookie('token')
    const endpoint = activeTab.value === 'withdrawals'
      ? `${apiBase}/api/admin/wallet/withdrawals/${selectedRequest.value.id}/approve`
      : `${apiBase}/api/admin/wallet/deposit-requests/${selectedRequest.value.id}/approve`

    const body = activeTab.value === 'deposits' ? { admin_note: adminNote.value } : {}

    await $fetch(endpoint, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token.value}`,
        'Content-Type': 'application/json'
      },
      body
    })

    showApproveModal.value = false
    selectedRequest.value = null
    adminNote.value = ''
    fetchPendingRequests()
  } catch (error) {
    console.error('Failed to approve:', error)
  } finally {
    isProcessing.value = false
  }
}

// Reject request
const rejectRequest = async () => {
  if (!selectedRequest.value) return

  isProcessing.value = true
  try {
    const token = useCookie('token')
    const endpoint = activeTab.value === 'withdrawals'
      ? `${apiBase}/api/admin/wallet/withdrawals/${selectedRequest.value.id}/reject`
      : `${apiBase}/api/admin/wallet/deposit-requests/${selectedRequest.value.id}/reject`

    await $fetch(endpoint, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token.value}`,
        'Content-Type': 'application/json'
      },
      body: {
        reason: rejectReason.value,
        admin_note: adminNote.value
      }
    })

    showRejectModal.value = false
    selectedRequest.value = null
    rejectReason.value = ''
    adminNote.value = ''
    fetchPendingRequests()
  } catch (error) {
    console.error('Failed to reject:', error)
  } finally {
    isProcessing.value = false
  }
}

// Format currency
const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('th-TH', {
    style: 'currency',
    currency: 'THB'
  }).format(amount)
}

// Format date
const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Get bank name in Thai
const getBankName = (bankCode: string) => {
  const banks: Record<string, string> = {
    'scb': 'ธนาคารไทยพาณิชย์',
    'kbank': 'ธนาคารกสิกรไทย',
    'ktb': 'ธนาคารกรุงไทย',
    'bbl': 'ธนาคารกรุงเทพ',
    'bay': 'ธนาคารกรุงศรีอยุธยา',
    'tmb': 'ธนาคารทหารไทยธนชาต',
    'ttb': 'ธนาคารทีเอ็มบีธนชาต',
    'gsb': 'ธนาคารออมสิน',
    'baac': 'ธนาคาร ธ.ก.ส.',
    'uob': 'ธนาคารยูโอบี',
    'cimb': 'ธนาคารซีไอเอ็มบี',
    'lhbank': 'ธนาคารแลนด์ แอนด์ เฮ้าส์',
    'tisco': 'ธนาคารทิสโก้',
    'kkp': 'ธนาคารเกียรตินาคินภัทร',
    'promptpay': 'พร้อมเพย์',
    'truemoney': 'ทรูมันนี่'
  }
  return banks[bankCode?.toLowerCase()] || bankCode || 'ไม่ระบุ'
}

// Whether a withdrawal request targets PromptPay.
// Falls back to the bank_name marker for records created before destination_type existed.
const isPromptPay = (request: any) =>
  request?.metadata?.destination_type === 'promptpay'
  || request?.metadata?.bank_account?.bank_name === 'promptpay'

// Format a PromptPay number for readability (081-234-5678 / national ID).
const formatPromptPay = (raw: string) => {
  const digits = (raw || '').replace(/\D/g, '')
  if (digits.length === 10) return digits.replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3')
  if (digits.length === 13) return digits.replace(/(\d{1})(\d{4})(\d{5})(\d{2})(\d{1})/, '$1-$2-$3-$4-$5')
  return digits || raw
}

onMounted(() => {
  fetchPendingRequests()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">รายการรอดำเนินการ</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1">
          รายการที่รอการอนุมัติทั้งหมด {{ (totalPendingWithdrawals + totalPendingDeposits).toLocaleString() }} รายการ
        </p>
      </div>

      <button
        @click="fetchPendingRequests"
        class="px-4 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl text-slate-700 dark:text-slate-300 transition-colors inline-flex items-center gap-2"
      >
        <Icon icon="fluent:arrow-sync-24-regular" class="w-5 h-5" />
        รีเฟรช
      </button>
    </div>

    <!-- Tabs -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-1 shadow-hopeui border border-slate-100 dark:border-slate-700">
      <div class="flex">
        <button
          @click="activeTab = 'withdrawals'"
          :class="[
            'flex-1 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors',
            activeTab === 'withdrawals'
              ? 'bg-hopeui-primary-500 text-white'
              : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'
          ]"
        >
          ถอนเงิน ({{ totalPendingWithdrawals }})
        </button>
        <button
          @click="activeTab = 'deposits'"
          :class="[
            'flex-1 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors',
            activeTab === 'deposits'
              ? 'bg-hopeui-primary-500 text-white'
              : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'
          ]"
        >
          เติมเงิน ({{ totalPendingDeposits }})
        </button>
      </div>
    </div>

    <!-- Pending Requests -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-hopeui border border-slate-100 dark:border-slate-700 overflow-hidden">
      <!-- Loading State -->
      <div v-if="isLoading" class="p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-hopeui-primary-600 animate-spin mx-auto" />
        <p class="text-slate-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="(activeTab === 'withdrawals' ? pendingWithdrawals : pendingDeposits).length === 0" class="p-8 text-center">
        <Icon icon="fluent:checkmark-circle-24-regular" class="w-12 h-12 text-green-400 mx-auto" />
        <p class="text-slate-500 mt-2">
          {{ activeTab === 'withdrawals' ? 'ไม่มีคำขอถอนเงินรอดำเนินการ' : 'ไม่มีคำขอเติมเงินรอดำเนินการ' }}
        </p>
      </div>

      <!-- List -->
      <div v-else class="divide-y divide-slate-100 dark:divide-slate-700">
        <div
          v-for="request in activeTab === 'withdrawals' ? pendingWithdrawals : pendingDeposits"
          :key="request.id"
          class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
        >
          <div class="flex flex-col lg:flex-row gap-4">
            <!-- User Info -->
            <div class="flex items-start gap-3 flex-1 min-w-0">
              <!-- Avatar -->
              <div class="w-12 h-12 flex-shrink-0">
                <img 
                  v-if="request.user?.avatar || request.user?.profile_photo_url" 
                  :src="request.user?.avatar || request.user?.profile_photo_url" 
                  :alt="request.user?.name"
                  class="w-12 h-12 rounded-full object-cover"
                  @error="($event.target as HTMLImageElement).style.display = 'none'"
                />
                <div v-else class="w-12 h-12 bg-gradient-to-br from-hopeui-primary-100 to-purple-100 dark:from-hopeui-primary-900/30 dark:to-purple-900/30 rounded-full flex items-center justify-center">
                  <Icon icon="fluent:person-24-regular" class="w-6 h-6 text-hopeui-primary-500" />
                </div>
              </div>

              <div class="min-w-0 flex-1">
                <p class="font-semibold text-slate-800 dark:text-white truncate">
                  {{ request.user?.name || 'Unknown' }}
                </p>
                <p class="text-sm text-slate-500 truncate">
                  {{ request.user?.email || '-' }}
                </p>
                <!-- Phone number -->
                <p v-if="request.user?.phone_number" class="text-sm text-slate-500 flex items-center gap-1">
                  <Icon icon="fluent:call-24-regular" class="w-3.5 h-3.5" />
                  {{ request.user?.phone_number }}
                </p>

                <!-- Payment method for deposits -->
                <p v-if="activeTab === 'deposits' && request.payment_method" class="text-xs text-slate-400 flex items-center gap-1 mt-1">
                  <Icon icon="fluent:payment-24-regular" class="w-3 h-3" />
                  {{ request.payment_method_label || request.payment_method }}
                </p>
              </div>
            </div>

            <!-- Bank / PromptPay Info (for withdrawals) -->
            <div v-if="activeTab === 'withdrawals' && request.metadata?.bank_account" class="flex-1 min-w-0 bg-slate-50 dark:bg-slate-700/50 rounded-xl p-3">
              <div class="flex items-center justify-between mb-1">
                <p class="text-xs text-slate-400">
                  {{ isPromptPay(request) ? 'ข้อมูลพร้อมเพย์' : 'ข้อมูลบัญชีธนาคาร' }}
                </p>
                <span
                  class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                  :class="isPromptPay(request)
                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
                    : 'bg-slate-200 text-slate-600 dark:bg-slate-600 dark:text-slate-300'"
                >
                  {{ isPromptPay(request) ? 'PromptPay' : 'Bank' }}
                </span>
              </div>
              <div class="space-y-1">
                <p v-if="!isPromptPay(request)" class="text-sm font-medium text-slate-800 dark:text-white flex items-center gap-2">
                  <Icon icon="fluent:building-bank-24-regular" class="w-4 h-4 text-hopeui-info" />
                  {{ getBankName(request.metadata.bank_account.bank_name) }}
                </p>
                <p class="text-sm text-slate-600 dark:text-slate-300 flex items-center gap-2">
                  <Icon :icon="isPromptPay(request) ? 'fluent:phone-24-regular' : 'fluent:document-number-24-regular'" class="w-4 h-4 text-slate-400" />
                  {{ isPromptPay(request) ? formatPromptPay(request.metadata.bank_account.account_number) : request.metadata.bank_account.account_number }}
                </p>
                <p class="text-sm text-slate-600 dark:text-slate-300 flex items-center gap-2">
                  <Icon icon="fluent:person-24-regular" class="w-4 h-4 text-slate-400" />
                  {{ request.metadata.bank_account.account_name }}
                </p>
              </div>
            </div>

            <!-- Amount & Details -->
            <div class="text-right whitespace-nowrap min-w-[160px]">
              <p class="text-xl font-bold text-slate-800 dark:text-white">
                {{ formatCurrency(request.amount) }}
              </p>
              <!-- Fee & Net Amount -->
              <div v-if="request.metadata?.fee" class="text-xs text-slate-500 mt-1">
                <span>ค่าธรรมเนียม: {{ formatCurrency(request.metadata.fee) }}</span>
                <p class="text-green-600 font-medium">
                  รับจริง: {{ formatCurrency(request.metadata.net_amount) }}
                </p>
              </div>
              <p class="text-sm text-slate-500 mt-1">
                {{ formatDate(request.created_at) }}
              </p>
              <!-- Wallet balance -->
              <p v-if="request.user?.wallet !== undefined" class="text-xs text-slate-400 flex items-center justify-end gap-1 mt-1">
                <Icon icon="fluent:wallet-24-regular" class="w-3 h-3" />
                ยอดคงเหลือ: {{ formatCurrency(parseFloat(request.user.wallet)) }}
              </p>
              <!-- Reference for deposits -->
              <p v-if="activeTab === 'deposits' && request.reference_number" class="text-xs text-slate-400 mt-1">
                Ref: {{ request.reference_number }}
              </p>
            </div>

            <!-- Thumbnail for Deposits (Moved here) -->
            <div 
              v-if="activeTab === 'deposits' && (request.slip_url || request.transfer_slip)"
              class="hidden sm:block relative group cursor-pointer"
              @click="openSlipModal(request)"
            >
              <div class="w-12 h-16 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-600 bg-slate-100 dark:bg-slate-700 relative">
                <img
                  :src="request.slip_url || request.transfer_slip"
                  class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                  alt="Slip"
                  @error="($event.target as HTMLImageElement).src = 'https://placehold.co/48x64?text=Error'"
                />
                <!-- Hover overlay -->
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                  <Icon icon="fluent:zoom-in-24-filled" class="w-5 h-5 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
              <button
                @click="openApproveModal(request)"
                class="w-9 h-9 sm:w-auto sm:px-4 sm:py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl transition-colors inline-flex items-center justify-center gap-2"
                title="อนุมัติ"
              >
                <Icon icon="fluent:checkmark-24-regular" class="w-5 h-5" />
                <span class="hidden sm:inline">อนุมัติ</span>
              </button>
              <button
                @click="openRejectModal(request)"
                class="w-9 h-9 sm:w-auto sm:px-4 sm:py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors inline-flex items-center justify-center gap-2"
                title="ปฏิเสธ"
              >
                <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
                <span class="hidden sm:inline">ปฏิเสธ</span>
              </button>
            </div>
          </div>
          
          <!-- Mobile Thumbnail (visible only on small screens) -->
          <div 
             v-if="activeTab === 'deposits' && (request.slip_url || request.transfer_slip)"
             class="sm:hidden mt-3"
          >
             <button 
                @click="openSlipModal(request)"
                class="flex items-center gap-2 text-xs text-blue-600 font-medium"
             >
                <Icon icon="fluent:image-24-regular" class="w-4 h-4" />
                ดูรูปหลักฐานการโอนเงิน
             </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Approve Modal -->
    <Teleport to="body">
      <div v-if="showApproveModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showApproveModal = false"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-2xl p-6 w-full max-w-md shadow-xl transform transition-all scale-100">
          <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">ยืนยันการอนุมัติ</h3>
          <p class="text-slate-600 dark:text-slate-300 mb-4">
            คุณต้องการอนุมัติ{{ activeTab === 'withdrawals' ? 'การถอนเงิน' : 'คำขอเติมเงิน' }} <span class="font-bold text-green-600">{{ formatCurrency(selectedRequest?.amount || 0) }}</span> ใช่หรือไม่?
          </p>

          <!-- Admin note for deposits -->
          <div v-if="activeTab === 'deposits'" class="mb-4">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              หมายเหตุ (ไม่บังคับ)
            </label>
            <textarea
              v-model="adminNote"
              rows="2"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none"
              placeholder="เพิ่มหมายเหตุ..."
            ></textarea>
          </div>

          <div class="flex gap-3">
            <button
              @click="approveRequest"
              :disabled="isProcessing"
              class="flex-1 px-4 py-2.5 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white rounded-xl transition-colors font-medium shadow-sm shadow-green-600/30"
            >
              <span v-if="isProcessing" class="flex items-center justify-center gap-2">
                 <Icon icon="svg-spinners:ring-resize" class="w-5 h-5" /> กำลังดำเนินการ
              </span>
              <span v-else>ยืนยันอนุมัติ</span>
            </button>
            <button
              @click="showApproveModal = false"
              class="flex-1 px-4 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-xl transition-colors font-medium"
            >
              ยกเลิก
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Reject Modal -->
    <Teleport to="body">
      <div v-if="showRejectModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showRejectModal = false"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-2xl p-6 w-full max-w-md shadow-xl transform transition-all scale-100">
          <div class="flex items-center gap-3 mb-4 text-red-600">
             <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                <Icon icon="fluent:warning-24-filled" class="w-6 h-6" />
             </div>
             <h3 class="text-lg font-bold text-slate-900 dark:text-white">ปฏิเสธ{{ activeTab === 'withdrawals' ? 'คำขอถอนเงิน' : 'คำขอเติมเงิน' }}</h3>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              เหตุผล <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="rejectReason"
              rows="3"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none"
              placeholder="ระบุเหตุผลในการปฏิเสธ..."
              autofocus
            ></textarea>
          </div>
          <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              หมายเหตุ (ไม่บังคับ)
            </label>
            <textarea
              v-model="adminNote"
              rows="2"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none"
              placeholder="เพิ่มหมายเหตุ..."
            ></textarea>
          </div>
          <div class="flex gap-3">
            <button
              @click="rejectRequest"
              :disabled="isProcessing || !rejectReason.trim()"
              class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 disabled:bg-slate-300 disabled:text-slate-500 text-white rounded-xl transition-colors font-medium shadow-sm shadow-red-600/30"
            >
              <span v-if="isProcessing" class="flex items-center justify-center gap-2">
                 <Icon icon="svg-spinners:ring-resize" class="w-5 h-5" /> กำลังดำเนินการ
              </span>
              <span v-else>ยืนยันปฏิเสธ</span>
            </button>
            <button
              @click="showRejectModal = false"
              class="flex-1 px-4 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-xl transition-colors font-medium"
            >
              ยกเลิก
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Slip Modal (Redesigned) -->
    <Teleport to="body">
      <div v-if="showSlipModal" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <!-- Backdrop with blur -->
        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-md transition-all duration-300" @click="showSlipModal = false"></div>
        
        <!-- Modal Content -->
        <div class="relative w-full max-w-3xl max-h-[90vh] flex flex-col items-center justify-center">
           <!-- Close Button (Floating) -->
           <button 
              @click="showSlipModal = false"
              class="absolute -top-12 right-0 p-2 text-white/70 hover:text-white bg-white/10 hover:bg-white/20 rounded-full transition-colors backdrop-blur-sm"
           >
              <Icon icon="fluent:dismiss-24-filled" class="w-6 h-6" />
           </button>

           <div class="bg-transparent rounded-2xl overflow-hidden shadow-2xl max-w-full">
              <div v-if="selectedSlipUrl" class="relative group">
                <img
                    :src="selectedSlipUrl"
                    :alt="'หลักฐานการโอนเงิน'"
                    class="max-w-full max-h-[85vh] object-contain rounded-xl"
                    @error="console.error('Failed to load slip image')"
                />
                <!-- Actions Bar -->
                <div class="absolute bottom-0 inset-x-0 p-4 bg-gradient-to-t from-black/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex justify-center gap-4">
                   <a 
                      :href="selectedSlipUrl" 
                      target="_blank"
                      download
                      class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg backdrop-blur-sm text-sm font-medium flex items-center gap-2 transition-colors"
                   >
                      <Icon icon="fluent:arrow-download-24-regular" class="w-5 h-5" />
                      ดาวน์โหลด
                   </a>
                   <a 
                      :href="selectedSlipUrl" 
                      target="_blank"
                      class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg backdrop-blur-sm text-sm font-medium flex items-center gap-2 transition-colors"
                   >
                      <Icon icon="fluent:open-24-regular" class="w-5 h-5" />
                      เปิดแท็บใหม่
                   </a>
                </div>
              </div>
              
              <div v-else class="bg-white dark:bg-slate-800 p-12 rounded-xl text-center min-w-[300px]">
                <div class="w-20 h-20 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-4">
                   <Icon icon="fluent:image-off-24-filled" class="w-10 h-10 text-slate-400" />
                </div>
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-1">ไม่พบหลักฐานการโอนเงิน</h3>
                <p class="text-slate-500 text-sm">ไฟล์อาจถูกลบหรือลิงก์ไม่ถูกต้อง</p>
                <button
                   @click="showSlipModal = false"
                   class="mt-6 px-6 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg text-sm font-medium transition-colors"
                >
                   ปิดหน้าต่าง
                </button>
              </div>
           </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
