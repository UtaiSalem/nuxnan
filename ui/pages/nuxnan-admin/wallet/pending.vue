<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string
const authStore = useAuthStore()

// State
const activeTab = ref('withdrawals') // 'withdrawals' | 'awaiting_payout' | 'deposits'
const pendingWithdrawals = ref([])
const awaitingPayoutWithdrawals = ref([])
const pendingDeposits = ref([])
const isLoading = ref(true)
const totalPendingWithdrawals = ref(0)
const totalAwaitingPayout = ref(0)
const totalPendingDeposits = ref(0)

// Action modals
const showApproveModal = ref(false)
const showRejectModal = ref(false)
const showSlipModal = ref(false)
const showDetailsModal = ref(false)
const showPaidModal = ref(false)
const showFailedModal = ref(false)

const selectedRequest = ref<any>(null)
const selectedRequestDetails = ref<any>(null)
const isLoadingDetails = ref(false)
const selectedSlipUrl = ref('')
const isProcessing = ref(false)
const isPaidProcessing = ref(false)
const rejectReason = ref('')
const adminNote = ref('')
const failedReason = ref('')
const errorMessage = ref('')

// Payout proof inputs
const paymentReference = ref('')
const proofFile = ref<File | null>(null)
const proofPreviewUrl = ref('')

// Copy feedback
const copiedField = ref('')

// Fetch pending withdrawals (pending + under_review)
const fetchPendingWithdrawals = async () => {
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/wallet/withdrawals/pending?status=pending,under_review`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })

    if (response.success) {
      pendingWithdrawals.value = response.data.withdrawals || response.data.data || response.data
      totalPendingWithdrawals.value = response.data.pagination?.total_items || response.data.total || pendingWithdrawals.value.length
    }
  } catch (error) {
    console.error('Failed to fetch pending withdrawals:', error)
    pendingWithdrawals.value = []
    totalPendingWithdrawals.value = 0
  }
}

// Fetch awaiting payout withdrawals (approved + processing)
const fetchAwaitingPayout = async () => {
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/wallet/withdrawals/pending?status=awaiting-payout`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })

    if (response.success) {
      awaitingPayoutWithdrawals.value = response.data.withdrawals || response.data.data || response.data
      totalAwaitingPayout.value = response.data.pagination?.total_items || response.data.total || awaitingPayoutWithdrawals.value.length
    }
  } catch (error) {
    console.error('Failed to fetch awaiting payout withdrawals:', error)
    awaitingPayoutWithdrawals.value = []
    totalAwaitingPayout.value = 0
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
    fetchAwaitingPayout(),
    fetchPendingDeposits()
  ])
  isLoading.value = false
}

// Open details modal
const openDetailsModal = async (request: any) => {
  selectedRequest.value = request
  selectedRequestDetails.value = null
  showDetailsModal.value = true
  isLoadingDetails.value = true
  errorMessage.value = ''

  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/wallet/withdrawals/${request.id}`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })
    if (response.success) {
      selectedRequestDetails.value = response.data
      // Refresh to update statuses (e.g. pending -> under_review, reviewer assigned)
      fetchPendingRequests()
    } else {
      errorMessage.value = response.message || 'ไม่สามารถโหลดรายละเอียดคำขอได้'
    }
  } catch (error: any) {
    console.error('Failed to fetch withdrawal details:', error)
    errorMessage.value = error.data?.message || error.message || 'เกิดข้อผิดพลาดในการโหลดรายละเอียด'
  } finally {
    isLoadingDetails.value = false
  }
}

// Open approve modal
const openApproveModal = (request: any) => {
  selectedRequest.value = request
  showApproveModal.value = true
  adminNote.value = ''
  errorMessage.value = ''
}

// Open reject modal
const openRejectModal = (request: any) => {
  selectedRequest.value = request
  rejectReason.value = ''
  adminNote.value = ''
  showRejectModal.value = true
  errorMessage.value = ''
}

// Open slip modal (for deposits)
const openSlipModal = (request: any) => {
  selectedSlipUrl.value = request.slip_url || request.transfer_slip
  showSlipModal.value = true
}

// Open paid modal (also handles approve step when status is pending/under_review)
const openPaidModal = (request: any) => {
  selectedRequest.value = request
  paymentReference.value = ''
  proofFile.value = null
  proofPreviewUrl.value = ''
  adminNote.value = ''
  showPaidModal.value = true
  errorMessage.value = ''
}

// Open failed modal
const openFailedModal = (request: any) => {
  selectedRequest.value = request
  failedReason.value = ''
  showFailedModal.value = true
  errorMessage.value = ''
}

// File change handler
const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const file = target.files[0]
    if (file.size > 5 * 1024 * 1024) {
      errorMessage.value = 'ขนาดไฟล์หลักฐานต้องไม่เกิน 5MB'
      proofFile.value = null
      proofPreviewUrl.value = ''
      return
    }
    proofFile.value = file
    proofPreviewUrl.value = file.type.startsWith('image/') ? URL.createObjectURL(file) : ''
    errorMessage.value = ''
  }
}

// Copy to clipboard with visual feedback
const copyToClipboard = (text: string, field: string) => {
  if (!text) return
  navigator.clipboard.writeText(text).then(() => {
    copiedField.value = field
    setTimeout(() => {
      copiedField.value = ''
    }, 2000)
  })
}

// Maker checker computations
const isCurrentAdminReviewer = computed(() => {
  if (!selectedRequestDetails.value) return false
  const currentUserId = authStore.user?.id
  const reviewerId = selectedRequestDetails.value.reviewed_by || selectedRequestDetails.value.reviewer?.id
  return currentUserId && reviewerId && parseInt(currentUserId) === parseInt(reviewerId)
})

const isMakerCheckerRequired = computed(() => {
  if (!selectedRequestDetails.value) return false
  return parseFloat(selectedRequestDetails.value.amount) >= 10000
})

const canCurrentAdminApprove = computed(() => {
  if (!selectedRequestDetails.value) return false
  if (selectedRequestDetails.value.name_mismatch) return false
  if (isMakerCheckerRequired.value) {
    return !isCurrentAdminReviewer.value
  }
  return true
})

const hasNameMismatch = computed(() => !!selectedRequestDetails.value?.name_mismatch)

// Approve request (handles deposit/withdrawal approve)
const approveRequest = async () => {
  if (!selectedRequest.value) return

  isProcessing.value = true
  errorMessage.value = ''
  try {
    const token = useCookie('token')
    const isWithdrawal = activeTab.value !== 'deposits'
    const endpoint = isWithdrawal
      ? `${apiBase}/api/admin/wallet/withdrawals/${selectedRequest.value.id}/approve`
      : `${apiBase}/api/admin/wallet/deposit-requests/${selectedRequest.value.id}/approve`

    const body = { admin_note: adminNote.value }

    const response = await $fetch(endpoint, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token.value}`,
        'Content-Type': 'application/json'
      },
      body
    })

    if (response.success) {
      showApproveModal.value = false
      showDetailsModal.value = false
      selectedRequest.value = null
      adminNote.value = ''
      fetchPendingRequests()
    } else {
      errorMessage.value = response.message || 'ไม่สามารถอนุมัติรายการได้'
    }
  } catch (error: any) {
    console.error('Failed to approve:', error)
    errorMessage.value = error.data?.message || error.message || 'เกิดข้อผิดพลาดในการอนุมัติ'
  } finally {
    isProcessing.value = false
  }
}

// Reject request
const rejectRequest = async () => {
  if (!selectedRequest.value) return

  isProcessing.value = true
  errorMessage.value = ''
  try {
    const token = useCookie('token')
    const isWithdrawal = activeTab.value !== 'deposits'
    const endpoint = isWithdrawal
      ? `${apiBase}/api/admin/wallet/withdrawals/${selectedRequest.value.id}/reject`
      : `${apiBase}/api/admin/wallet/deposit-requests/${selectedRequest.value.id}/reject`

    const response = await $fetch(endpoint, {
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

    if (response.success) {
      showRejectModal.value = false
      showDetailsModal.value = false
      selectedRequest.value = null
      rejectReason.value = ''
      adminNote.value = ''
      fetchPendingRequests()
    } else {
      errorMessage.value = response.message || 'ไม่สามารถปฏิเสธรายการได้'
    }
  } catch (error: any) {
    console.error('Failed to reject:', error)
    errorMessage.value = error.data?.message || error.message || 'เกิดข้อผิดพลาดในการปฏิเสธ'
  } finally {
    isProcessing.value = false
  }
}

// Submit Paid (marks withdrawal paid with payout proof)
const submitPaid = async () => {
  if (!selectedRequest.value || !proofFile.value) {
    errorMessage.value = 'กรุณาแนบรูปหลักฐานการโอน'
    return
  }

  isPaidProcessing.value = true
  errorMessage.value = ''
  try {
    const token = useCookie('token')
    const reqId = selectedRequest.value.id
    const status = selectedRequest.value.status

    // 1. If still pending/under_review, approve first (single-step admin flow)
    if (status === 'pending' || status === 'under_review') {
      const approveResponse = await $fetch(`${apiBase}/api/admin/wallet/withdrawals/${reqId}/approve`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token.value}`,
          'Content-Type': 'application/json'
        },
        body: { admin_note: adminNote.value }
      })
      if (!approveResponse.success) {
        errorMessage.value = approveResponse.message || 'ไม่สามารถอนุมัติคำขอได้'
        isPaidProcessing.value = false
        return
      }
    }

    // 2. If approved (or just approved above), move to processing
    if (status === 'pending' || status === 'under_review' || status === 'approved') {
      const processResponse = await $fetch(`${apiBase}/api/admin/wallet/withdrawals/${reqId}/process`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token.value}`,
          'Content-Type': 'application/json'
        }
      })
      if (!processResponse.success) {
        errorMessage.value = processResponse.message || 'ไม่สามารถเปลี่ยนสถานะเป็นกำลังดำเนินการได้'
        isPaidProcessing.value = false
        return
      }
    }

    // 3. Send paid request with slip
    const formData = new FormData()
    if (paymentReference.value) {
      formData.append('payment_reference', paymentReference.value)
    }
    formData.append('proof', proofFile.value)

    const paidResponse = await $fetch(`${apiBase}/api/admin/wallet/withdrawals/${reqId}/paid`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token.value}`
      },
      body: formData
    })

    if (paidResponse.success) {
      showPaidModal.value = false
      showDetailsModal.value = false
      selectedRequest.value = null
      paymentReference.value = ''
      proofFile.value = null
      proofPreviewUrl.value = ''
      fetchPendingRequests()
    } else {
      errorMessage.value = paidResponse.message || 'เกิดข้อผิดพลาดในการบันทึกการโอนเงิน'
    }
  } catch (error: any) {
    console.error('Failed to mark paid:', error)
    errorMessage.value = error.data?.message || error.message || 'เกิดข้อผิดพลาดในการบันทึกการโอนเงิน'
  } finally {
    isPaidProcessing.value = false
  }
}

// Submit mark withdrawal failed
const submitFailed = async () => {
  if (!selectedRequest.value || !failedReason.value) {
    errorMessage.value = 'กรุณาระบุเหตุผลที่โอนเงินล้มเหลว'
    return
  }

  isProcessing.value = true
  errorMessage.value = ''
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/wallet/withdrawals/${selectedRequest.value.id}/failed`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token.value}`,
        'Content-Type': 'application/json'
      },
      body: {
        reason: failedReason.value
      }
    })

    if (response.success) {
      showFailedModal.value = false
      showDetailsModal.value = false
      selectedRequest.value = null
      failedReason.value = ''
      fetchPendingRequests()
    } else {
      errorMessage.value = response.message || 'ไม่สามารถบันทึกสถานะล้มเหลวได้'
    }
  } catch (error: any) {
    console.error('Failed to mark failed:', error)
    errorMessage.value = error.data?.message || error.message || 'เกิดข้อผิดพลาด'
  } finally {
    isProcessing.value = false
  }
}

// Format currency
const formatCurrency = (amount: any) => {
  const num = typeof amount === 'string' ? parseFloat(amount) : amount
  return new Intl.NumberFormat('th-TH', {
    style: 'currency',
    currency: 'THB'
  }).format(num || 0)
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

// Status labels
const getStatusBadgeClass = (status: string) => {
  const classes: Record<string, string> = {
    'pending': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    'under_review': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
    'approved': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    'processing': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    'paid': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    'completed': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    'rejected': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    'failed': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    'cancelled': 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-400'
  }
  return classes[status] || 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-400'
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    'pending': 'รอดำเนินการ',
    'under_review': 'กำลังตรวจสอบ',
    'approved': 'อนุมัติแล้ว รอโอน',
    'processing': 'กำลังดำเนินการโอน',
    'paid': 'โอนเงินแล้ว',
    'completed': 'เสร็จสิ้น',
    'rejected': 'ปฏิเสธแล้ว',
    'failed': 'โอนเงินล้มเหลว',
    'cancelled': 'ยกเลิกแล้ว'
  }
  return labels[status] || status
}

// Whether a withdrawal request targets PromptPay
const isPromptPay = (request: any) =>
  request?.metadata?.destination_type === 'promptpay'
  || request?.metadata?.bank_account?.bank_name === 'promptpay'

const getBankAccount = (request: any) =>
  request?.destination_details || request?.metadata?.bank_account || {}

// Format a PromptPay number for readability
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
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">รายการธุรกรรมรอดำเนินการ</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1">
          รวมรายการถอนและเติมเงินในระบบแอดมิน
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
      <div class="flex flex-wrap md:flex-nowrap gap-1">
        <button
          @click="activeTab = 'withdrawals'"
          :class="[
            'flex-1 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors whitespace-nowrap',
            activeTab === 'withdrawals'
              ? 'bg-hopeui-primary-500 text-white'
              : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'
          ]"
        >
          ถอนเงิน: รอดำเนินการ ({{ totalPendingWithdrawals }})
        </button>
        <button
          @click="activeTab = 'awaiting_payout'"
          :class="[
            'flex-1 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors whitespace-nowrap',
            activeTab === 'awaiting_payout'
              ? 'bg-hopeui-primary-500 text-white'
              : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'
          ]"
        >
          ถอนเงิน: รอโอน/แนบสลิป ({{ totalAwaitingPayout }})
        </button>
        <button
          @click="activeTab = 'deposits'"
          :class="[
            'flex-1 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors whitespace-nowrap',
            activeTab === 'deposits'
              ? 'bg-hopeui-primary-500 text-white'
              : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'
          ]"
        >
          เติมเงิน ({{ totalPendingDeposits }})
        </button>
      </div>
    </div>

    <!-- Requests List -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-hopeui border border-slate-100 dark:border-slate-700 overflow-hidden">
      <!-- Loading State -->
      <div v-if="isLoading" class="p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-hopeui-primary-600 animate-spin mx-auto" />
        <p class="text-slate-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="(activeTab === 'withdrawals' ? pendingWithdrawals : activeTab === 'awaiting_payout' ? awaitingPayoutWithdrawals : pendingDeposits).length === 0" class="p-8 text-center">
        <Icon icon="fluent:checkmark-circle-24-regular" class="w-12 h-12 text-green-400 mx-auto" />
        <p class="text-slate-500 mt-2">
          {{ activeTab === 'withdrawals' ? 'ไม่มีคำขอถอนเงินรอดำเนินการ' : activeTab === 'awaiting_payout' ? 'ไม่มีรายการรอโอนเงิน' : 'ไม่มีคำขอเติมเงินรอดำเนินการ' }}
        </p>
      </div>

      <!-- List -->
      <div v-else class="divide-y divide-slate-100 dark:divide-slate-700">
        <div
          v-for="request in (activeTab === 'withdrawals' ? pendingWithdrawals : activeTab === 'awaiting_payout' ? awaitingPayoutWithdrawals : pendingDeposits)"
          :key="request.id"
          class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors cursor-pointer"
          @click="activeTab !== 'deposits' ? openDetailsModal(request) : null"
        >
          <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center">
            <!-- User Info -->
            <div class="flex items-start gap-3 flex-1 min-w-0">
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
                <div class="flex items-center gap-2">
                  <p class="font-semibold text-slate-800 dark:text-white truncate">
                    {{ request.user?.name || 'Unknown' }}
                  </p>
                  <span
                    v-if="activeTab !== 'deposits'"
                    class="text-[10px] px-2 py-0.5 rounded-full font-medium"
                    :class="getStatusBadgeClass(request.status)"
                  >
                    {{ getStatusLabel(request.status) }}
                  </span>
                </div>
                <p class="text-sm text-slate-500 truncate">
                  {{ request.user?.email || '-' }}
                </p>
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
            <div v-if="activeTab !== 'deposits' && getBankAccount(request).account_number" class="flex-1 min-w-0 bg-slate-50 dark:bg-slate-700/50 rounded-xl p-3">
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
                  {{ getBankName(getBankAccount(request).bank_name) }}
                </p>
                <p class="text-sm text-slate-600 dark:text-slate-300 flex items-center gap-2 font-mono">
                  <Icon :icon="isPromptPay(request) ? 'fluent:phone-24-regular' : 'fluent:document-number-24-regular'" class="w-4 h-4 text-slate-400" />
                  {{ isPromptPay(request) ? formatPromptPay(getBankAccount(request).account_number) : getBankAccount(request).account_number }}
                </p>
                <p class="text-sm text-slate-600 dark:text-slate-300 flex items-center gap-2">
                  <Icon icon="fluent:person-24-regular" class="w-4 h-4 text-slate-400" />
                  {{ getBankAccount(request).account_name }}
                </p>
              </div>
            </div>

            <!-- Amount & Details -->
            <div class="text-left lg:text-right whitespace-nowrap min-w-[160px]">
              <p class="text-xl font-bold text-slate-800 dark:text-white">
                {{ formatCurrency(request.amount) }}
              </p>
              <div v-if="request.metadata?.fee" class="text-xs text-slate-500 mt-1">
                <span>ค่าธรรมเนียม: {{ formatCurrency(request.metadata.fee) }}</span>
                <p class="text-green-600 dark:text-green-400 font-medium">
                  รับจริง: {{ formatCurrency(request.metadata.net_amount) }}
                </p>
              </div>
              <p class="text-sm text-slate-500 mt-1">
                {{ formatDate(request.created_at) }}
              </p>
              <!-- Wallet balance -->
              <p v-if="request.user?.wallet !== undefined" class="text-xs text-slate-400 flex items-center lg:justify-end gap-1 mt-1">
                <Icon icon="fluent:wallet-24-regular" class="w-3 h-3" />
                คงเหลือ: {{ formatCurrency(parseFloat(request.user.wallet)) }}
              </p>
              <p v-if="activeTab === 'deposits' && request.reference_number" class="text-xs text-slate-400 mt-1">
                Ref: {{ request.reference_number }}
              </p>
            </div>

            <!-- Deposit Slip Thumbnail -->
            <div
              v-if="activeTab === 'deposits' && (request.slip_url || request.transfer_slip)"
              class="hidden sm:block relative group cursor-pointer"
              @click.stop="openSlipModal(request)"
            >
              <div class="w-12 h-16 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-600 bg-slate-100 dark:bg-slate-700 relative">
                <img
                  :src="request.slip_url || request.transfer_slip"
                  class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                  alt="Slip"
                  @error="($event.target as HTMLImageElement).src = 'https://placehold.co/48x64?text=Error'"
                />
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                  <Icon icon="fluent:zoom-in-24-filled" class="w-5 h-5 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
                </div>
              </div>
            </div>

            <!-- Actions (Deposits only, Withdrawals have modal details) -->
            <div v-if="activeTab === 'deposits'" class="flex gap-2 w-full lg:w-auto mt-2 lg:mt-0 justify-end">
              <button
                @click.stop="openApproveModal(request)"
                class="flex-1 lg:flex-initial px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl transition-colors inline-flex items-center justify-center gap-2 text-sm font-medium"
              >
                <Icon icon="fluent:checkmark-24-regular" class="w-4 h-4" />
                <span>อนุมัติ</span>
              </button>
              <button
                @click.stop="openRejectModal(request)"
                class="flex-1 lg:flex-initial px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors inline-flex items-center justify-center gap-2 text-sm font-medium"
              >
                <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4" />
                <span>ปฏิเสธ</span>
              </button>
            </div>

            <div v-else class="flex gap-2 w-full lg:w-auto justify-end">
              <button
                class="flex-1 lg:flex-initial px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-xl transition-colors inline-flex items-center justify-center gap-2 text-sm font-medium"
              >
                <Icon icon="fluent:eye-24-regular" class="w-4 h-4" />
                <span>ดูรายละเอียด</span>
              </button>
            </div>
          </div>

          <!-- Mobile Thumbnail for Deposits -->
          <div
             v-if="activeTab === 'deposits' && (request.slip_url || request.transfer_slip)"
             class="sm:hidden mt-3"
          >
             <button
                @click.stop="openSlipModal(request)"
                class="flex items-center gap-2 text-xs text-blue-600 font-medium"
             >
                <Icon icon="fluent:image-24-regular" class="w-4 h-4" />
                ดูรูปหลักฐานการโอนเงิน
             </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Withdrawal Details Modal (New & Premium) -->
    <Teleport to="body">
      <div v-if="showDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showDetailsModal = false"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-3xl w-full max-w-2xl shadow-2xl transform transition-all scale-100 my-8 overflow-hidden">

          <!-- Modal Header -->
          <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-900/30">
            <div>
              <h3 class="text-lg font-bold text-slate-800 dark:text-white">รายละเอียดคำขอถอนเงิน</h3>
              <p class="text-xs text-slate-500 mt-0.5">รายการ ID: #{{ selectedRequest?.id }}</p>
            </div>
            <button @click="showDetailsModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-full hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
            </button>
          </div>

          <!-- Loading state -->
          <div v-if="isLoadingDetails" class="p-12 text-center">
            <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-hopeui-primary-600 animate-spin mx-auto" />
            <p class="text-slate-500 mt-2 text-sm">กำลังดึงรายละเอียดความปลอดภัย...</p>
          </div>

          <!-- Details Content -->
          <div v-else-if="selectedRequestDetails" class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
            <!-- Alert message if any -->
            <div v-if="errorMessage" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
              <Icon icon="fluent:error-circle-24-regular" class="w-5 h-5 flex-shrink-0" />
              <span>{{ errorMessage }}</span>
            </div>

            <!-- Name Mismatch Warning Banner -->
            <div v-if="hasNameMismatch" class="bg-red-50 dark:bg-red-950/30 border-2 border-red-300 dark:border-red-800 text-red-900 dark:text-red-200 p-4 rounded-2xl space-y-2">
              <div class="flex items-center gap-2 font-bold text-sm">
                <Icon icon="fluent:shield-error-24-filled" class="w-6 h-6 text-red-600" />
                <span>ตรวจพบชื่อบัญชีปลายทางไม่ตรงกับชื่อผู้ใช้</span>
              </div>
              <div class="text-xs space-y-1 pl-8">
                <div class="flex justify-between">
                  <span class="text-red-700 dark:text-red-300">ชื่อในโปรไฟล์ผู้ใช้:</span>
                  <span class="font-semibold">{{ selectedRequestDetails.expected_account_name || '— (ไม่มีข้อมูล)' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-red-700 dark:text-red-300">ชื่อบัญชีปลายทาง:</span>
                  <span class="font-semibold">{{ getBankAccount(selectedRequestDetails).account_name || '—' }}</span>
                </div>
              </div>
              <p class="text-xs pt-1 border-t border-red-200 dark:border-red-900/60 mt-2">
                <strong>ปิดกั้นการอนุมัติเพื่อป้องกันการทุจริต</strong> — กรุณาปฏิเสธคำขอนี้และแจ้งให้ผู้ใช้ใช้บัญชีที่เป็นชื่อของตนเอง
              </p>
            </div>

            <!-- Maker-Checker Warning Banner -->
            <div v-if="isMakerCheckerRequired" class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900 text-amber-800 dark:text-amber-300 p-4 rounded-2xl space-y-1">
              <div class="flex items-center gap-2 font-semibold text-sm">
                <Icon icon="fluent:shield-warning-24-filled" class="w-5 h-5 text-amber-500" />
                <span>ระบบควบคุมความปลอดภัย Maker-Checker</span>
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-400">
                เนื่องจากคำขอถอนเงินมียอดสุทธิมากกว่า ฿10,000 จะต้องดำเนินการผ่านแอดมินสองคน โดยผู้ตรวจคนแรก (Maker) จะต้องไม่ใช่คนเดียวกับผู้อนุมัติขั้นสุดท้าย (Checker)
              </p>
              <div v-if="isCurrentAdminReviewer" class="text-xs font-bold text-red-600 dark:text-red-400 mt-1 flex items-center gap-1">
                <Icon icon="fluent:dismiss-circle-24-filled" class="w-4 h-4" />
                <span>คุณคือผู้ตรวจสอบคนแรก (Maker) ไม่สามารถอนุมัติหรือทำรายการโอนได้ด้วยตนเอง</span>
              </div>
            </div>

            <!-- Layout Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

              <!-- Section Left: User Info -->
              <div class="space-y-4">
                <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">ข้อมูลผู้ขอถอน</h4>
                <div class="bg-slate-50 dark:bg-slate-900/20 rounded-2xl p-4 space-y-3">
                  <div class="flex items-center gap-3">
                    <img
                      v-if="selectedRequestDetails.user?.avatar || selectedRequestDetails.user?.profile_photo_url"
                      :src="selectedRequestDetails.user?.avatar || selectedRequestDetails.user?.profile_photo_url"
                      class="w-10 h-10 rounded-full object-cover"
                      alt="Avatar"
                    />
                    <div v-else class="w-10 h-10 bg-slate-200 dark:bg-slate-700 rounded-full flex items-center justify-center">
                      <Icon icon="fluent:person-24-regular" class="w-5 h-5 text-slate-500" />
                    </div>
                    <div class="min-w-0 flex-1">
                      <p class="font-semibold text-sm text-slate-800 dark:text-white truncate">{{ selectedRequestDetails.user?.name }}</p>
                      <p class="text-xs text-slate-500 truncate">{{ selectedRequestDetails.user?.email }}</p>
                    </div>
                  </div>
                  <div class="border-t border-slate-100 dark:border-slate-700 pt-3 space-y-2 text-xs">
                    <div class="flex justify-between">
                      <span class="text-slate-500">เบอร์โทรศัพท์:</span>
                      <span class="text-slate-700 dark:text-slate-300 font-medium">{{ selectedRequestDetails.user?.phone_number || '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-500">ยอดเงินใน Wallet:</span>
                      <span class="text-slate-700 dark:text-slate-300 font-medium">{{ formatCurrency(selectedRequestDetails.user?.wallet) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Section Right: Bank / Payout details -->
              <div class="space-y-4">
                <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">ปลายทางบัญชีผู้รับเงิน</h4>
                <div v-if="getBankAccount(selectedRequestDetails).account_number" class="bg-slate-50 dark:bg-slate-900/20 rounded-2xl p-4 space-y-3">
                  <div class="flex justify-between items-center">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full" :class="isPromptPay(selectedRequestDetails) ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-600 dark:text-slate-300'">
                      {{ isPromptPay(selectedRequestDetails) ? 'พร้อมเพย์' : 'บัญชีธนาคาร' }}
                    </span>
                  </div>

                  <div class="space-y-2 text-sm">
                    <div v-if="!isPromptPay(selectedRequestDetails)" class="flex justify-between">
                      <span class="text-slate-500 text-xs">ธนาคาร:</span>
                      <span class="text-slate-800 dark:text-white font-medium">{{ getBankName(getBankAccount(selectedRequestDetails).bank_name) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="text-slate-500 text-xs">เลขบัญชี/เบอร์:</span>
                      <div class="flex items-center gap-1.5 font-mono">
                        <span class="text-slate-800 dark:text-white font-semibold">
                          {{ isPromptPay(selectedRequestDetails) ? formatPromptPay(getBankAccount(selectedRequestDetails).account_number) : getBankAccount(selectedRequestDetails).account_number }}
                        </span>
                        <button
                          @click="copyToClipboard(getBankAccount(selectedRequestDetails).account_number, 'account_number')"
                          class="p-1 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-md transition-colors"
                          title="คัดลอก"
                        >
                          <Icon :icon="copiedField === 'account_number' ? 'fluent:checkmark-12-filled' : 'fluent:copy-16-regular'" class="w-4 h-4 text-hopeui-primary-500" />
                        </button>
                      </div>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-500 text-xs">ชื่อบัญชี:</span>
                      <span class="text-slate-800 dark:text-white font-semibold">{{ getBankAccount(selectedRequestDetails).account_name }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Transaction Amounts -->
            <div class="bg-gradient-to-br from-slate-50 to-slate-100/50 dark:from-slate-900/30 dark:to-slate-800/30 rounded-2xl p-4 border border-slate-100 dark:border-slate-700 space-y-2.5">
              <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">รายละเอียดจำนวนเงิน</h4>
              <div class="grid grid-cols-3 gap-4 text-center">
                <div class="p-2.5 bg-white dark:bg-slate-800/80 rounded-xl shadow-sm border border-slate-50 dark:border-slate-700/60">
                  <p class="text-xs text-slate-500">ยอดที่ขอถอน</p>
                  <p class="text-base font-bold text-slate-800 dark:text-white mt-1">{{ formatCurrency(selectedRequestDetails.amount) }}</p>
                </div>
                <div class="p-2.5 bg-white dark:bg-slate-800/80 rounded-xl shadow-sm border border-slate-50 dark:border-slate-700/60">
                  <p class="text-xs text-slate-500">ค่าธรรมเนียม</p>
                  <p class="text-base font-bold text-red-500 mt-1">- {{ formatCurrency(selectedRequestDetails.metadata?.fee || 0) }}</p>
                </div>
                <div class="p-2.5 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-950/20 dark:to-emerald-950/20 rounded-xl shadow-sm border border-green-100/60 dark:border-green-900/40">
                  <p class="text-xs text-green-700 dark:text-green-300 font-medium">ยอดโอนสุทธิ (รับจริง)</p>
                  <p class="text-lg font-black text-green-600 dark:text-green-400 mt-1">{{ formatCurrency(selectedRequestDetails.metadata?.net_amount ?? selectedRequestDetails.amount) }}</p>
                </div>
              </div>
            </div>

            <!-- Auditing / Action History -->
            <div class="space-y-3">
              <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">ประวัติการดำเนินการ</h4>
              <div class="bg-slate-50 dark:bg-slate-900/20 rounded-2xl p-4 space-y-2 text-xs text-slate-600 dark:text-slate-400">
                <div class="flex justify-between">
                  <span>ผู้สร้างคำขอ:</span>
                  <span class="font-medium text-slate-800 dark:text-slate-300">{{ selectedRequestDetails.user?.name }} (เมื่อ {{ formatDate(selectedRequestDetails.created_at) }})</span>
                </div>
                <div v-if="selectedRequestDetails.reviewer" class="flex justify-between">
                  <span>ผู้ตรวจคนแรก (Maker):</span>
                  <span class="font-medium text-slate-800 dark:text-slate-300">{{ selectedRequestDetails.reviewer.name }} (เมื่อ {{ formatDate(selectedRequestDetails.reviewed_at) }})</span>
                </div>
                <div v-if="selectedRequestDetails.metadata?.approved_by" class="flex justify-between">
                  <span>ผู้อนุมัติคำขอ (Checker):</span>
                  <span class="font-medium text-slate-800 dark:text-slate-300">{{ selectedRequestDetails.metadata.approved_by }}</span>
                </div>
                <div v-if="selectedRequestDetails.payment_reference" class="flex justify-between">
                  <span>เลขอ้างอิงโอนเงิน:</span>
                  <span class="font-semibold text-green-600 font-mono">{{ selectedRequestDetails.payment_reference }}</span>
                </div>
                <div v-if="selectedRequestDetails.admin_note" class="flex flex-col gap-1 border-t border-slate-200 dark:border-slate-700 pt-2 mt-2">
                  <span class="font-bold text-slate-700 dark:text-slate-300">หมายเหตุของแอดมิน:</span>
                  <span class="bg-white dark:bg-slate-800/80 p-2 rounded-lg border border-slate-100 dark:border-slate-700/60 font-mono text-slate-700 dark:text-slate-300">{{ selectedRequestDetails.admin_note }}</span>
                </div>
              </div>
            </div>

          </div>

          <!-- Modal Footer (Actions) -->
          <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 flex flex-col sm:flex-row gap-3 justify-end">
            <!-- If state is pending / under_review (Needs review/approve) -->
            <template v-if="selectedRequestDetails?.status === 'pending' || selectedRequestDetails?.status === 'under_review'">
              <button
                @click="openPaidModal(selectedRequestDetails)"
                :disabled="!canCurrentAdminApprove"
                class="px-5 py-2.5 bg-green-600 hover:bg-green-700 disabled:bg-slate-300 dark:disabled:bg-slate-700 text-white rounded-xl transition-colors font-medium text-sm flex items-center justify-center gap-2 shadow-sm"
              >
                <Icon icon="fluent:receipt-play-24-regular" class="w-4 h-4" />
                อนุมัติและบันทึกการโอน
              </button>
              <button
                @click="openRejectModal(selectedRequestDetails)"
                class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors font-medium text-sm flex items-center justify-center gap-2 shadow-sm"
              >
                <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4" />
                ปฏิเสธคำขอ
              </button>
            </template>

            <!-- If state is approved / processing (Needs payout settlement) -->
            <template v-else-if="selectedRequestDetails?.status === 'approved' || selectedRequestDetails?.status === 'processing'">
              <button
                @click="openPaidModal(selectedRequestDetails)"
                :disabled="hasNameMismatch || (isMakerCheckerRequired && isCurrentAdminReviewer)"
                class="px-5 py-2.5 bg-green-600 hover:bg-green-700 disabled:bg-slate-300 dark:disabled:bg-slate-700 text-white rounded-xl transition-colors font-medium text-sm flex items-center justify-center gap-2 shadow-sm"
              >
                <Icon icon="fluent:receipt-play-24-regular" class="w-4 h-4" />
                โอนเงินเสร็จสิ้น & แนบสลิป
              </button>
              <button
                @click="openFailedModal(selectedRequestDetails)"
                class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors font-medium text-sm flex items-center justify-center gap-2 shadow-sm"
              >
                <Icon icon="fluent:error-circle-24-regular" class="w-4 h-4" />
                ทำรายการโอนล้มเหลว
              </button>
            </template>

            <button
              @click="showDetailsModal = false"
              class="px-5 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-xl transition-colors font-medium text-sm"
            >
              ปิดหน้าต่าง
            </button>
          </div>

        </div>
      </div>
    </Teleport>

    <!-- Approve Modal (Handles deposits or withdrawals) -->
    <Teleport to="body">
      <div v-if="showApproveModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showApproveModal = false"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
          <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">ยืนยันการอนุมัติ</h3>

          <div v-if="errorMessage" class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-3 rounded-xl text-sm mb-4">
            {{ errorMessage }}
          </div>

          <template v-if="activeTab !== 'deposits'">
            <p class="text-slate-600 dark:text-slate-300 mb-3 text-sm">
              ยืนยันการอนุมัติคำขอถอนเงินและส่งเรื่องต่อไปยังขั้นตอนการโอนเงินใช่หรือไม่?
            </p>
            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4 mb-4 space-y-1.5 text-sm">
              <div class="flex justify-between">
                <span class="text-slate-500">ยอดที่ต้องโอนจริง</span>
                <span class="font-bold text-green-600">
                  {{ formatCurrency(selectedRequest?.metadata?.net_amount ?? selectedRequest?.amount ?? 0) }}
                </span>
              </div>
            </div>
          </template>
          <p v-else class="text-slate-600 dark:text-slate-300 mb-4 text-sm">
            คุณต้องการอนุมัติคำขอเติมเงิน <span class="font-bold text-green-600">{{ formatCurrency(selectedRequest?.amount || 0) }}</span> ใช่หรือไม่?
          </p>

          <!-- Note input -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              บันทึกข้อความจากแอดมิน (ส่งไปยัง DB/Audit)
            </label>
            <textarea
              v-model="adminNote"
              rows="2"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none"
              placeholder="ข้อความระบุหมายเหตุการอนุมัติ..."
            ></textarea>
          </div>

          <div class="flex gap-3">
            <button
              @click="approveRequest"
              :disabled="isProcessing"
              class="flex-1 px-4 py-2.5 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white rounded-xl transition-colors font-medium"
            >
              <span v-if="isProcessing" class="flex items-center justify-center gap-2">
                 <Icon icon="svg-spinners:ring-resize" class="w-5 h-5" /> กำลังบันทึก...
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
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showRejectModal = false"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
          <div class="flex items-center gap-3 mb-4 text-red-600">
             <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                <Icon icon="fluent:warning-24-filled" class="w-6 h-6" />
             </div>
             <h3 class="text-lg font-bold text-slate-900 dark:text-white">ปฏิเสธธุรกรรม</h3>
          </div>

          <div v-if="errorMessage" class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-3 rounded-xl text-sm mb-4">
            {{ errorMessage }}
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              เหตุผลการปฏิเสธ <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="rejectReason"
              rows="3"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none"
              placeholder="ระบุเหตุผลในการปฏิเสธคำขอ..."
              autofocus
            ></textarea>
          </div>
          <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              หมายเหตุเพิ่มเติม
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
              class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 disabled:bg-slate-300 text-white rounded-xl transition-colors font-medium"
            >
              <span v-if="isProcessing" class="flex items-center justify-center gap-2">
                 <Icon icon="svg-spinners:ring-resize" class="w-5 h-5" /> กำลังดำเนินการ...
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

    <!-- Paid & Upload Slip Modal (New) -->
    <Teleport to="body">
      <div v-if="showPaidModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showPaidModal = false"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl transform transition-all scale-100">

          <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-2 flex items-center gap-2">
            <Icon icon="fluent:receipt-play-24-regular" class="text-green-600 w-6 h-6" />
            {{ selectedRequest?.status === 'pending' || selectedRequest?.status === 'under_review'
              ? 'อนุมัติและบันทึกการโอน'
              : 'บันทึกการโอนเงินสำเร็จ' }}
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
            กรุณาโอนเงินให้ผู้ใช้ก่อน แล้วแนบหลักฐาน (สลิป) เพื่อยืนยันการอนุมัติในขั้นตอนเดียว
          </p>

          <div v-if="errorMessage" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 p-3 rounded-xl text-xs mb-4">
            {{ errorMessage }}
          </div>

          <div class="space-y-4">
            <!-- Net amount information -->
            <div class="bg-slate-50 dark:bg-slate-700/50 p-3 rounded-xl text-sm flex justify-between items-center">
              <span class="text-slate-500">ยอดเงินที่ต้องโอน:</span>
              <span class="font-extrabold text-green-600 text-base">
                {{ formatCurrency(selectedRequest?.metadata?.net_amount ?? selectedRequest?.amount) }}
              </span>
            </div>

            <!-- Admin Note (only shown when approving in single step) -->
            <div v-if="selectedRequest?.status === 'pending' || selectedRequest?.status === 'under_review'">
              <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                หมายเหตุจากแอดมิน
              </label>
              <textarea
                v-model="adminNote"
                rows="2"
                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none text-sm"
                placeholder="บันทึกเหตุผลการอนุมัติ (ถ้ามี)..."
              ></textarea>
            </div>

            <!-- Payout Proof File Upload -->
            <div>
              <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                รูปภาพหลักฐานการโอน (สลิป) <span class="text-red-500">*</span>
              </label>

              <div v-if="proofPreviewUrl" class="relative rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/20 p-2 mb-3">
                <img :src="proofPreviewUrl" class="w-full max-h-40 object-contain rounded-xl" />
                <button
                  @click="proofFile = null; proofPreviewUrl = ''"
                  class="absolute top-3 right-3 p-1.5 bg-red-600 hover:bg-red-700 text-white rounded-full shadow-lg transition-colors"
                >
                  <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
                </button>
              </div>

              <div v-else-if="proofFile" class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/20 p-3 mb-3">
                <div class="flex items-center gap-2 min-w-0">
                  <Icon icon="fluent:document-pdf-24-regular" class="w-6 h-6 text-red-500 flex-shrink-0" />
                  <span class="text-sm text-slate-700 dark:text-slate-200 truncate">{{ proofFile.name }}</span>
                </div>
                <button
                  @click="proofFile = null; proofPreviewUrl = ''"
                  class="p-1.5 bg-red-600 hover:bg-red-700 text-white rounded-full shadow transition-colors flex-shrink-0"
                >
                  <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
                </button>
              </div>

              <div v-else class="flex justify-center items-center w-full">
                <label class="flex flex-col justify-center items-center w-full h-32 bg-slate-50 dark:bg-slate-700/30 rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-700 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors">
                  <div class="flex flex-col justify-center items-center pt-5 pb-6">
                    <Icon icon="fluent:cloud-upload-24-regular" class="w-8 h-8 text-slate-400 mb-2" />
                    <p class="mb-1 text-sm text-slate-500 dark:text-slate-400 font-medium">กดเพื่อเลือกอัปโหลดสลิป</p>
                    <p class="text-xs text-slate-400">JPG, PNG, WEBP หรือ PDF (สูงสุด 5MB)</p>
                  </div>
                  <input type="file" class="hidden" accept="image/*,application/pdf" @change="handleFileChange" />
                </label>
              </div>
            </div>
          </div>

          <div class="flex gap-3 mt-6">
            <button
              @click="submitPaid"
              :disabled="isPaidProcessing || !proofFile"
              class="flex-1 px-4 py-2.5 bg-green-600 hover:bg-green-700 disabled:bg-slate-300 disabled:text-slate-500 text-white rounded-xl transition-colors font-medium text-sm flex items-center justify-center gap-2 shadow-sm"
            >
              <span v-if="isPaidProcessing" class="flex items-center justify-center gap-2">
                 <Icon icon="svg-spinners:ring-resize" class="w-5 h-5" /> กำลังส่งข้อมูล...
              </span>
              <span v-else>ยืนยันและแนบหลักฐาน</span>
            </button>
            <button
              @click="showPaidModal = false"
              class="flex-1 px-4 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-xl transition-colors font-medium text-sm"
            >
              ยกเลิก
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Failed Modal (New) -->
    <Teleport to="body">
      <div v-if="showFailedModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showFailedModal = false"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
          <div class="flex items-center gap-3 mb-4 text-red-600">
             <Icon icon="fluent:error-circle-24-filled" class="w-8 h-8" />
             <h3 class="text-lg font-bold text-slate-900 dark:text-white">ทำรายการโอนเงินล้มเหลว</h3>
          </div>

          <div v-if="errorMessage" class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-3 rounded-xl text-sm mb-4">
            {{ errorMessage }}
          </div>

          <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
            การเปลี่ยนสถานะเป็นล้มเหลว จะทำรายการคืนเงินเข้า Wallet ของผู้ใช้โดยอัตโนมัติ กรุณาระบุสาเหตุเพื่อให้ระบบบันทึกประวัติความล้มเหลวนี้
          </p>

          <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              สาเหตุความล้มเหลว <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="failedReason"
              rows="3"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none text-sm"
              placeholder="เช่น บัญชีปลายทางไม่ถูกต้อง, ระบบพร้อมเพย์ล่ม, ยอดโอนขัดข้อง..."
              autofocus
            ></textarea>
          </div>

          <div class="flex gap-3">
            <button
              @click="submitFailed"
              :disabled="isProcessing || !failedReason.trim()"
              class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 disabled:bg-slate-300 text-white rounded-xl transition-colors font-medium text-sm flex items-center justify-center gap-2"
            >
              <span v-if="isProcessing" class="flex items-center justify-center gap-2">
                 <Icon icon="svg-spinners:ring-resize" class="w-5 h-5" /> กำลังบันทึก...
              </span>
              <span v-else>ยืนยันและทำรายการคืนเงิน</span>
            </button>
            <button
              @click="showFailedModal = false"
              class="flex-1 px-4 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-xl transition-colors font-medium text-sm"
            >
              ยกเลิก
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Slip Modal (For Deposits) -->
    <Teleport to="body">
      <div v-if="showSlipModal" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-md" @click="showSlipModal = false"></div>
        <div class="relative w-full max-w-3xl max-h-[90vh] flex flex-col items-center justify-center">
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
                    class="max-w-full max-h-[85vh] object-contain rounded-xl"
                    alt="Slip"
                />
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
           </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
