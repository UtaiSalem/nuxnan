<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
// If using Nuxt 3 with Pinia, the correct path is usually '@/stores/auth'
import { useAuthStore } from '@/stores/auth'
// If your store is in a different location, update the path accordingly.
import { useWallet } from '@/composables/useWallet'
import { usePoints } from '~/composables/usePoints'
import { useApi } from '~/composables/useApi'
import BaseCard from '~/components/atoms/BaseCard.vue'
import UnifiedTransactionCard from '~/components/Common/UnifiedTransactionCard.vue'

definePageMeta({
  layout: 'main',
  middleware: ['auth']
})

useHead({
  title: 'กระเป๋าเงิน - Nuxni'
})

const authStore = useAuthStore()
const { get } = useApi()
const {
  wallet,
  isLoading,
  error,
  getBalance,
  deposit,
  withdraw,
  transfer,
  convertToPoints,
  getTransactions,
  createDepositRequest,
  getDepositRequests,
  cancelDepositRequest,
  formatMoney,
  calculateFee,
  getNetAmount
} = useWallet()

const { points, convertToWallet, formatPoints } = usePoints()

// Transactions
const transactions = ref<any[]>([])
const transactionsLoading = ref(false)

// Deposit Requests
const depositRequests = ref<any[]>([])
const depositRequestsLoading = ref(false)

// Tab state
const activeTab = ref('overview') // 'overview' | 'deposit' | 'withdraw' | 'transfer' | 'convert' | 'convert-to-points' | 'history' | 'deposit-requests'

// Form states
const depositForm = ref({
  amount: 100,
  transfer_date: '',
  transfer_time: '',
  transfer_slip: null as File | null
})

const withdrawForm = ref({
  amount: 100,
  method: 'bank_transfer',
  bank_account: {
    bank_name: '',
    account_number: '',
    account_name: ''
  }
})

const transferForm = ref({
  recipient_id: '',
  amount: 10,
  message: ''
})

// User search for transfer
const userSearchQuery = ref('')
const userSearchResults = ref<any[]>([])
const userSearchLoading = ref(false)
const selectedRecipient = ref<any>(null)
const showUserDropdown = ref(false)

const config = useRuntimeConfig()
const apiBase = computed(() => config.public.apiBase || '')

// Search users for transfer
const searchUsers = async () => {
  if (userSearchQuery.value.length < 2) {
    userSearchResults.value = []
    return
  }

  try {
    userSearchLoading.value = true
    const response = await get('/api/users/search', {
      params: {
        q: userSearchQuery.value,
        limit: 10
      }
    }) as any

    if (response.success) {
      // Filter out current user
      userSearchResults.value = (response.data || []).filter((u: any) => u.id !== authStore.user?.id)
      showUserDropdown.value = true
    }
  } catch (err: any) {
    console.error('Search users error:', err)
    userSearchResults.value = []

    // Handle authentication errors
    if (err.statusCode === 401 || err.message?.includes('Unauthenticated')) {
      processMessage.value = 'กรุณาเข้าสู่ระบบเพื่อค้นหาผู้ใช้'
      processSuccess.value = false
    }
  } finally {
    userSearchLoading.value = false
  }
}

// Debounce search
let searchTimeout: ReturnType<typeof setTimeout> | null = null
const debouncedSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    searchUsers()
  }, 300)
}

// Select recipient
const selectRecipient = (user: any) => {
  selectedRecipient.value = user
  transferForm.value.recipient_id = user.id.toString()
  userSearchQuery.value = user.name || user.username
  showUserDropdown.value = false
}

// Clear recipient
const clearRecipient = () => {
  selectedRecipient.value = null
  transferForm.value.recipient_id = ''
  userSearchQuery.value = ''
  userSearchResults.value = []
}

const convertForm = ref({
  points: 1200
})

const convertToPointsForm = ref({
  amount: 10
})

// Processing states
const isProcessing = ref(false)
const processSuccess = ref(false)
const processMessage = ref('')

// Quick amounts
const quickDepositAmounts = [100, 300, 500, 1000, 2000, 5000]
const quickWithdrawAmounts = [100, 500, 1000, 2000, 5000]

// Bank options
const bankOptions = [
  { value: 'kbank', label: 'ธนาคารกสิกรไทย', icon: '🏦' },
  { value: 'scb', label: 'ธนาคารไทยพาณิชย์', icon: '🏦' },
  { value: 'bbl', label: 'ธนาคารกรุงเทพ', icon: '🏦' },
  { value: 'ktb', label: 'ธนาคารกรุงไทย', icon: '🏦' },
  { value: 'bay', label: 'ธนาคารกรุงศรีอยุธยา', icon: '🏦' },
  { value: 'tmb', label: 'ธนาคารทหารไทยธนชาต', icon: '🏦' },
]

// Computed
const walletBalance = computed(() => authStore.user?.wallet || 0)

const conversionPreview = computed(() => {
  const pts = convertForm.value.points
  const rate = 1200 // 1200 points = 1 THB
  return {
    points: pts,
    money: pts / rate,
    isValid: pts >= rate && pts <= (points.value || 0)
  }
})

const convertToPointsPreview = computed(() => {
  const amount = convertToPointsForm.value.amount
  const rate = 1200 // 1 THB = 1200 points
  return {
    amount: amount,
    points: amount * rate,
    isValid: amount >= 10 && amount <= walletBalance.value
  }
})

const withdrawPreview = computed(() => {
  const amount = withdrawForm.value.amount
  const fee = calculateFee(amount)
  const net = getNetAmount(amount)
  return { amount, fee, net }
})

// Slip preview
const slipPreview = ref<string | null>(null)

// Handle file input
const handleSlipUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    // Validate file type
    if (!file.type.startsWith('image/')) {
      processMessage.value = 'กรุณาอัปโหลดไฟล์รูปภาพเท่านั้น'
      processSuccess.value = false
      return
    }
    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      processMessage.value = 'ขนาดไฟล์ต้องไม่เกิน 5MB'
      processSuccess.value = false
      return
    }
    depositForm.value.transfer_slip = file
    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      slipPreview.value = e.target?.result as string
    }
    reader.readAsDataURL(file)
  }
}

// Clear slip
const clearSlip = () => {
  depositForm.value.transfer_slip = null
  slipPreview.value = null
}

// Methods
const loadTransactions = async () => {
  try {
    transactionsLoading.value = true
    const data = await getTransactions({ per_page: 20 })
    transactions.value = data.transactions || data.data || []
  } catch (err) {
    console.error('Failed to load transactions:', err)
  } finally {
    transactionsLoading.value = false
  }
}

const loadDepositRequests = async () => {
  try {
    depositRequestsLoading.value = true
    const data = await getDepositRequests({ per_page: 20 })
    depositRequests.value = data || []
  } catch (err) {
    console.error('Failed to load deposit requests:', err)
  } finally {
    depositRequestsLoading.value = false
  }
}

const handleCancelRequest = async (requestId: number) => {
  if (!confirm('ต้องการยกเลิกคำขอเติมเงินนี้หรือไม่?')) return
  
  try {
    isProcessing.value = true
    await cancelDepositRequest(requestId)
    processSuccess.value = true
    processMessage.value = 'ยกเลิกคำขอเติมเงินสำเร็จ'
    await loadDepositRequests()
  } catch (err: any) {
    processSuccess.value = false
    processMessage.value = err.message || 'เกิดข้อผิดพลาด'
  } finally {
    isProcessing.value = false
  }
}

const getStatusBadgeClass = (status: string) => {
  switch (status) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
    case 'approved':
      return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
    case 'rejected':
      return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const handleDeposit = async () => {
  try {
    isProcessing.value = true
    processSuccess.value = false
    
    // Validate required fields
    if (!depositForm.value.transfer_slip) {
      throw new Error('กรุณาอัปโหลดหลักฐานการโอนเงิน')
    }
    if (!depositForm.value.transfer_date) {
      throw new Error('กรุณาระบุวันที่โอน')
    }
    
    // Create FormData for file upload
    const formData = new FormData()
    formData.append('amount', depositForm.value.amount.toString())
    formData.append('payment_method', 'bank_transfer')
    formData.append('transfer_date', depositForm.value.transfer_date)
    formData.append('transfer_time', depositForm.value.transfer_time)
    formData.append('transfer_slip', depositForm.value.transfer_slip)
    
    await createDepositRequest(formData)
    
    processSuccess.value = true
    processMessage.value = `ส่งคำขอเติมเงิน ${formatMoney(depositForm.value.amount)} สำเร็จ! กรุณารอ Admin ตรวจสอบและอนุมัติ`
    
    // Refresh deposit requests list
    await loadDepositRequests()
    
    // Reset form
    depositForm.value = {
      amount: 100,
      transfer_date: '',
      transfer_time: '',
      transfer_slip: null
    }
    slipPreview.value = null
    
    // Switch to deposit requests tab
    activeTab.value = 'deposit-requests'
    
  } catch (err: any) {
    processSuccess.value = false
    processMessage.value = err.message || 'เกิดข้อผิดพลาด'
  } finally {
    isProcessing.value = false
  }
}

const handleWithdraw = async () => {
  try {
    isProcessing.value = true
    processSuccess.value = false
    
    await withdraw({
      amount: withdrawForm.value.amount,
      method: withdrawForm.value.method,
      bank_account: withdrawForm.value.bank_account
    })
    
    processSuccess.value = true
    processMessage.value = `ส่งคำขอถอนเงิน ${formatMoney(withdrawForm.value.amount)} สำเร็จ!`
    
    // Refresh balance
    await getBalance()
    await loadTransactions()
    
  } catch (err: any) {
    processSuccess.value = false
    processMessage.value = err.message || 'เกิดข้อผิดพลาด'
  } finally {
    isProcessing.value = false
  }
}

const handleTransfer = async () => {
  try {
    isProcessing.value = true
    processSuccess.value = false
    
    await transfer({
      recipient_id: parseInt(transferForm.value.recipient_id),
      amount: transferForm.value.amount,
      message: transferForm.value.message
    })
    
    processSuccess.value = true
    processMessage.value = `โอนเงิน ${formatMoney(transferForm.value.amount)} สำเร็จ!`
    
    // Refresh balance
    await getBalance()
    await loadTransactions()
    
    // Reset form
    transferForm.value.recipient_id = ''
    transferForm.value.amount = 10
    transferForm.value.message = ''
    
  } catch (err: any) {
    processSuccess.value = false
    processMessage.value = err.message || 'เกิดข้อผิดพลาด'
  } finally {
    isProcessing.value = false
  }
}

const handleConvert = async () => {
  try {
    isProcessing.value = true
    processSuccess.value = false

    await convertToWallet(convertForm.value.points)

    processSuccess.value = true
    processMessage.value = `แปลง ${formatPoints(convertForm.value.points)} แต้ม เป็น ${formatMoney(conversionPreview.value.money)} สำเร็จ!`

    // Refresh balance
    await getBalance()
    await loadTransactions()

  } catch (err: any) {
    processSuccess.value = false
    processMessage.value = err.message || 'เกิดข้อผิดพลาด'
  } finally {
    isProcessing.value = false
  }
}

const handleConvertToPoints = async () => {
  try {
    isProcessing.value = true
    processSuccess.value = false

    await convertToPoints(convertToPointsForm.value.amount)

    processSuccess.value = true
    processMessage.value = `แปลง ${formatMoney(convertToPointsForm.value.amount)} เป็น ${formatPoints(convertToPointsPreview.value.points)} แต้ม สำเร็จ!`

    // Refresh balance
    await getBalance()
    await loadTransactions()

  } catch (err: any) {
    processSuccess.value = false
    processMessage.value = err.message || 'เกิดข้อผิดพลาด'
  } finally {
    isProcessing.value = false
  }
}

const getTransactionTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    deposit: 'เติมเงิน',
    withdraw: 'ถอนเงิน',
    transfer_in: 'รับโอน',
    transfer_out: 'โอนออก',
    conversion: 'แปลงจากแต้ม',
    admin_adjust: 'ปรับโดย Admin'
  }
  return labels[type] || type
}

const getTransactionIcon = (type: string) => {
  const icons: Record<string, string> = {
    deposit: 'mdi:plus-circle',
    withdraw: 'mdi:minus-circle',
    transfer_in: 'mdi:arrow-down-circle',
    transfer_out: 'mdi:arrow-up-circle',
    conversion: 'mdi:swap-horizontal-circle',
    admin_adjust: 'mdi:shield-account'
  }
  return icons[type] || 'mdi:circle'
}

const isPositiveTransaction = (tx: any) => {
  const type = tx.transaction_type || tx.type
  if (['deposit', 'transfer_in'].includes(type)) return true
  if (['withdraw', 'transfer_out'].includes(type)) return false
  
  if (type === 'conversion') {
    // Money enters wallet when points are converted to money
    return tx.metadata?.conversion_type === 'points_to_money'
  }
  
  if (type === 'admin_adjust') {
    return tx.amount > 0
  }
  
  return tx.amount > 0
}

const getTransactionDisplayTitle = (tx: any) => {
  const type = tx.transaction_type || tx.type
  if ((type === 'transfer_in' || type === 'transfer_out') && tx.description) {
    return tx.description
  }
  return getTransactionTypeLabel(type)
}

const getTransactionDisplaySubtitle = (tx: any) => {
  const type = tx.transaction_type || tx.type
  if (type === 'transfer_in' || type === 'transfer_out') {
    return null
  }
  return tx.description || null
}

const isThreeColumnTransaction = (tx: any) => {
  const type = tx.transaction_type || tx.type
  return type === 'transfer_in' || type === 'transfer_out' || type === 'conversion'
}

const getDefaultAvatar = (name: string) => {
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=e2e8f0&color=64748b`
}

const getOwnerInfo = () => {
  return {
    name: authStore.user?.name || 'ฉัน',
    avatar: authStore.user?.profile_photo_url || getDefaultAvatar(authStore.user?.name || 'User')
  }
}

const getPartnerInfo = (tx: any) => {
  const type = tx.transaction_type || tx.type
  if (type === 'transfer_in') {
    return tx.sender || null
  } else if (type === 'transfer_out') {
    return tx.receiver || null
  } else if (type === 'conversion') {
    const isPointsToMoney = tx.metadata?.conversion_type === 'points_to_money'
    return {
      name: isPointsToMoney ? 'กระเป๋าพอยท์' : 'กระเป๋าพอยท์',
      avatar: null,
      isSystem: true
    }
  }
  return null
}

const getThreeColumnLabel = (tx: any) => {
  const type = tx.transaction_type || tx.type
  if (type === 'transfer_in') return 'รับเงินจาก'
  if (type === 'transfer_out') return 'โอนเงินให้'
  if (type === 'conversion') {
    const isPointsToMoney = tx.metadata?.conversion_type === 'points_to_money'
    return isPointsToMoney ? 'รับจากการแปลงแต้ม' : 'แปลงเป็นแต้ม'
  }
  return ''
}

// Transform transaction data for UnifiedTransactionCard
const transformTransaction = (tx: any) => {
  const type = tx.transaction_type || tx.type
  const isPositive = isPositiveTransaction(tx)
  const owner = getOwnerInfo()
  const partner = getPartnerInfo(tx)
  
  // Determine action type for the card
  let actionType = type
  if (type === 'conversion') {
    actionType = tx.metadata?.conversion_type === 'points_to_money' ? 'convert_to_wallet' : 'convert_to_points'
  } else if (type === 'transfer_in' || type === 'transfer_out') {
    actionType = 'transfer'
  }
  
  // Build source and destination
  let source, destination
  
  if (isPositive) {
    // Money coming in: partner -> owner
    source = partner ? {
      avatar: partner.avatar || undefined,
      name: partner.name?.split(' ')[0] || 'ระบบ',
      isSystem: partner.isSystem || false,
      systemIcon: partner.isSystem ? 'mdi:star-circle' : undefined
    } : {
      name: 'ระบบ',
      isSystem: true,
      systemIcon: 'mdi:bank'
    }
    destination = {
      avatar: owner.avatar,
      name: owner.name?.split(' ')[0] || 'ฉัน',
      isSystem: false
    }
  } else {
    // Money going out: owner -> partner
    source = {
      avatar: owner.avatar,
      name: owner.name?.split(' ')[0] || 'ฉัน',
      isSystem: false
    }
    destination = partner ? {
      avatar: partner.avatar || undefined,
      name: partner.name?.split(' ')[0] || 'ปลายทาง',
      isSystem: partner.isSystem || false,
      systemIcon: partner.isSystem ? (actionType === 'convert_to_points' ? 'mdi:star-circle' : 'mdi:wallet') : undefined
    } : {
      name: 'ปลายทาง',
      isSystem: true,
      systemIcon: 'mdi:wallet'
    }
  }
  
  return {
    id: tx.id,
    transactionType: 'wallet' as const,
    actionType,
    amount: isPositive ? Math.abs(tx.amount) : -Math.abs(tx.amount),
    balanceAfter: tx.balance_after,
    createdAt: tx.created_at,
    source,
    destination,
    description: tx.description
  }
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Initialize
onMounted(async () => {
  await Promise.all([
    getBalance(),
    loadTransactions(),
    loadDepositRequests()
  ])
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <!-- Page Header -->
      <div class="text-center mb-8">
        <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-full flex items-center justify-center shadow-lg">
          <Icon icon="mdi:wallet" class="w-10 h-10 text-white" />
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
          กระเป๋าเงิน
        </h1>
        <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
          จัดการเงินในกระเป๋า เติมเงิน ถอนเงิน โอนเงิน และแปลงแต้มเป็นเงิน
        </p>
      </div>

      <!-- Wallet Balance Card -->
      <BaseCard class="mb-6 bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 border-0">
        <div class="text-white p-2">
          <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-6">
              <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center backdrop-blur">
                <Icon icon="mdi:wallet" class="w-12 h-12" />
              </div>
              <div>
                <p class="text-white/80 text-sm mb-1">ยอดเงินคงเหลือ</p>
                <p class="text-4xl lg:text-5xl font-bold">{{ formatMoney(walletBalance) }}</p>
              </div>
            </div>
            
            <div class="flex gap-3">
              <button
                class="px-4 py-2 bg-white text-teal-600 font-semibold rounded-xl hover:bg-teal-50 transition-colors flex items-center gap-2 shadow"
                @click="activeTab = 'deposit'"
              >
                <Icon icon="mdi:plus" class="w-5 h-5" />
                เติมเงิน
              </button>
              <button
                class="px-4 py-2 bg-white/20 text-white font-semibold rounded-xl hover:bg-white/30 transition-colors flex items-center gap-2 backdrop-blur"
                :disabled="walletBalance < 100"
                @click="activeTab = 'withdraw'"
              >
                <Icon icon="mdi:minus" class="w-5 h-5" />
                ถอนเงิน
              </button>
            </div>
          </div>
          
          <!-- Points Info -->
          <div class="mt-6 pt-6 border-t border-white/20 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Icon icon="mdi:star-circle" class="w-5 h-5 text-yellow-300" />
              <span>แต้มที่มี: <strong>{{ formatPoints(points) }}</strong> แต้ม</span>
            </div>
            <button 
              class="text-sm underline hover:no-underline"
              @click="activeTab = 'convert'"
            >
              แปลงเป็นเงิน →
            </button>
          </div>
        </div>
      </BaseCard>

      <!-- Tab Navigation -->
      <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
        <button
          v-for="tab in [
            { key: 'overview', label: 'ภาพรวม', icon: 'mdi:view-dashboard' },
            { key: 'deposit', label: 'เติมเงิน', icon: 'mdi:plus-circle' },
            { key: 'deposit-requests', label: 'คำขอเติมเงิน', icon: 'mdi:clock-outline', badge: depositRequests.filter(r => r.status === 'pending').length },
            { key: 'withdraw', label: 'ถอนเงิน', icon: 'mdi:minus-circle' },
            { key: 'transfer', label: 'โอนเงิน', icon: 'mdi:send' },
            { key: 'convert', label: 'แปลงแต้มเป็นเงิน', icon: 'mdi:swap-horizontal' },
            { key: 'convert-to-points', label: 'แปลงเงินเป็นแต้ม', icon: 'mdi:swap-horizontal-circle' },
            { key: 'history', label: 'ประวัติ', icon: 'mdi:history' },
          ]"
          :key="tab.key"
          class="px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap flex items-center gap-2"
          :class="activeTab === tab.key
            ? 'bg-primary-500 text-white shadow'
            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
          @click="activeTab = tab.key"
        >
          <Icon :icon="tab.icon" class="w-4 h-4" />
          {{ tab.label }}
          <span 
            v-if="tab.badge && tab.badge > 0" 
            class="ml-1 px-1.5 py-0.5 text-xs bg-yellow-500 text-white rounded-full"
          >
            {{ tab.badge }}
          </span>
        </button>
      </div>

      <!-- Success/Error Message -->
      <div 
        v-if="processMessage" 
        class="mb-6 p-4 rounded-xl"
        :class="processSuccess ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'"
      >
        <div class="flex items-center gap-3">
          <Icon :icon="processSuccess ? 'mdi:check-circle' : 'mdi:alert-circle'" class="w-6 h-6" />
          <span>{{ processMessage }}</span>
          <button class="ml-auto" @click="processMessage = ''">
            <Icon icon="mdi:close" class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Tab Content -->
      <!-- Overview -->
      <div v-if="activeTab === 'overview'">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
          <BaseCard class="cursor-pointer hover:shadow-lg transition-shadow" @click="activeTab = 'deposit'">
            <div class="flex items-center gap-4 p-2">
              <div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                <Icon icon="mdi:plus-circle" class="w-7 h-7 text-green-500" />
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">เติมเงิน</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">เติมเงินเข้ากระเป๋า</p>
              </div>
              <Icon icon="mdi:chevron-right" class="w-6 h-6 text-gray-400 ml-auto" />
            </div>
          </BaseCard>
          
          <BaseCard class="cursor-pointer hover:shadow-lg transition-shadow" @click="activeTab = 'withdraw'">
            <div class="flex items-center gap-4 p-2">
              <div class="w-14 h-14 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                <Icon icon="mdi:minus-circle" class="w-7 h-7 text-red-500" />
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">ถอนเงิน</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">ถอนเงินเข้าบัญชีธนาคาร</p>
              </div>
              <Icon icon="mdi:chevron-right" class="w-6 h-6 text-gray-400 ml-auto" />
            </div>
          </BaseCard>
          
          <BaseCard class="cursor-pointer hover:shadow-lg transition-shadow" @click="activeTab = 'transfer'">
            <div class="flex items-center gap-4 p-2">
              <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                <Icon icon="mdi:send" class="w-7 h-7 text-blue-500" />
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">โอนเงิน</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">โอนเงินให้ผู้ใช้อื่น</p>
              </div>
              <Icon icon="mdi:chevron-right" class="w-6 h-6 text-gray-400 ml-auto" />
            </div>
          </BaseCard>
          
          <BaseCard class="cursor-pointer hover:shadow-lg transition-shadow" @click="activeTab = 'convert'">
            <div class="flex items-center gap-4 p-2">
              <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                <Icon icon="mdi:swap-horizontal" class="w-7 h-7 text-purple-500" />
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">แปลงแต้ม</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">แปลงแต้มเป็นเงิน</p>
              </div>
              <Icon icon="mdi:chevron-right" class="w-6 h-6 text-gray-400 ml-auto" />
            </div>
          </BaseCard>
        </div>
        
        <!-- Recent Transactions -->
        <BaseCard>
          <div class="p-2">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">รายการล่าสุด</h3>
              <button 
                class="text-primary-500 text-sm hover:underline"
                @click="activeTab = 'history'"
              >
                ดูทั้งหมด
              </button>
            </div>
            
            <div v-if="transactionsLoading" class="py-8 text-center">
              <Icon icon="mdi:loading" class="w-8 h-8 text-primary-500 animate-spin mx-auto" />
            </div>
            
            <div v-else-if="transactions.length > 0" class="space-y-2 sm:space-y-3">
              <UnifiedTransactionCard
                v-for="tx in transactions.slice(0, 5)"
                :key="tx.id"
                v-bind="transformTransaction(tx)"
              />
            </div>
            
            <div v-else class="py-8 text-center text-gray-500 dark:text-gray-400">
              <Icon icon="mdi:inbox-outline" class="w-12 h-12 mx-auto mb-3" />
              <p>ยังไม่มีรายการ</p>
            </div>
          </div>
        </BaseCard>
      </div>

      <!-- Deposit Form -->
      <BaseCard v-if="activeTab === 'deposit'">
        <div class="p-2">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">เติมเงินเข้ากระเป๋า</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">กรุณาโอนเงินและอัปโหลดหลักฐานการโอน รอ Admin ตรวจสอบและอนุมัติ</p>
          
          <!-- Bank Account Info -->
          <div class="bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20 rounded-xl p-4 mb-6">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-3">
              <Icon icon="mdi:bank" class="w-5 h-5 inline mr-2" />
              บัญชีรับโอน
            </h4>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">ธนาคาร:</span>
                <span class="font-medium text-gray-900 dark:text-white">ธนาคารกสิกรไทย</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">เลขบัญชี:</span>
                <span class="font-medium text-gray-900 dark:text-white">xxx-x-xxxxx-x</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">ชื่อบัญชี:</span>
                <span class="font-medium text-gray-900 dark:text-white">บริษัท เพลิน์ดี จำกัด</span>
              </div>
            </div>
          </div>
          
          <div class="space-y-6">
            <!-- Amount -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                จำนวนเงินที่โอน <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <input 
                  v-model.number="depositForm.amount"
                  type="number"
                  min="10"
                  step="10"
                  class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  placeholder="ระบุจำนวนเงิน"
                >
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">บาท</span>
              </div>
              
              <!-- Quick Amounts -->
              <div class="flex flex-wrap gap-2 mt-3">
                <button 
                  v-for="amount in quickDepositAmounts"
                  :key="amount"
                  type="button"
                  class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="depositForm.amount === amount 
                    ? 'bg-primary-500 text-white' 
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'"
                  @click="depositForm.amount = amount"
                >
                  {{ amount }} บาท
                </button>
              </div>
            </div>
            
            <!-- Transfer Date/Time -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  วันที่โอน <span class="text-red-500">*</span>
                </label>
                <input 
                  v-model="depositForm.transfer_date"
                  type="date"
                  class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เวลาที่โอน</label>
                <input 
                  v-model="depositForm.transfer_time"
                  type="time"
                  class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
              </div>
            </div>
            
            <!-- Transfer Slip Upload -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                หลักฐานการโอนเงิน (สลิป) <span class="text-red-500">*</span>
              </label>
              
              <div v-if="!slipPreview" class="mt-1">
                <label 
                  class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl cursor-pointer hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/10 transition-colors"
                >
                  <div class="flex flex-col items-center justify-center py-5">
                    <Icon icon="mdi:cloud-upload" class="w-10 h-10 text-gray-400 mb-3" />
                    <p class="text-sm text-gray-600 dark:text-gray-400">คลิกเพื่ออัปโหลดรูปภาพ</p>
                    <p class="text-xs text-gray-500 mt-1">PNG, JPG (สูงสุด 5MB)</p>
                  </div>
                  <input 
                    type="file" 
                    accept="image/*"
                    class="hidden"
                    @change="handleSlipUpload"
                  >
                </label>
              </div>
              
              <div v-else class="relative mt-1">
                <img 
                  :src="slipPreview" 
                  alt="Transfer slip preview" 
                  class="w-full max-h-64 object-contain rounded-xl border border-gray-200 dark:border-gray-600"
                >
                <button 
                  type="button"
                  class="absolute top-2 right-2 p-2 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors"
                  @click="clearSlip"
                >
                  <Icon icon="mdi:close" class="w-4 h-4" />
                </button>
              </div>
            </div>
            
            <!-- Submit Button -->
            <button 
              type="button"
              class="w-full py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="isProcessing || depositForm.amount < 10 || !depositForm.transfer_slip || !depositForm.transfer_date"
              @click="handleDeposit"
            >
              <Icon v-if="isProcessing" icon="mdi:loading" class="w-5 h-5 animate-spin inline mr-2" />
              {{ isProcessing ? 'กำลังดำเนินการ...' : `ส่งคำขอเติมเงิน ${formatMoney(depositForm.amount)}` }}
            </button>
            
            <p class="text-xs text-center text-gray-500 dark:text-gray-400">
              <Icon icon="mdi:information" class="w-4 h-4 inline" />
              เงินจะถูกเติมเข้ากระเป๋าหลังจาก Admin ตรวจสอบและอนุมัติแล้ว
            </p>
          </div>
        </div>
      </BaseCard>

      <!-- Deposit Requests History -->
      <BaseCard v-if="activeTab === 'deposit-requests'">
        <div class="p-2">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">คำขอเติมเงินของฉัน</h3>
          
          <div v-if="depositRequestsLoading" class="py-8 text-center">
            <Icon icon="mdi:loading" class="w-8 h-8 text-primary-500 animate-spin mx-auto" />
            <p class="text-gray-500 mt-2">กำลังโหลด...</p>
          </div>
          
          <div v-else-if="depositRequests.length > 0" class="space-y-4">
            <div 
              v-for="req in depositRequests" 
              :key="req.id"
              class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4"
            >
              <div class="flex items-start justify-between mb-3">
                <div>
                  <p class="font-semibold text-gray-900 dark:text-white">{{ formatMoney(req.amount) }}</p>
                  <p class="text-xs text-gray-500">{{ req.created_at }}</p>
                </div>
                <span 
                  class="px-3 py-1 text-xs font-medium rounded-full"
                  :class="getStatusBadgeClass(req.status)"
                >
                  {{ req.status_label }}
                </span>
              </div>
              
              <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                <div>
                  <span class="text-gray-500 dark:text-gray-400">ธนาคาร:</span>
                  <span class="ml-1 text-gray-900 dark:text-white">{{ req.bank_name || '-' }}</span>
                </div>
                <div>
                  <span class="text-gray-500 dark:text-gray-400">วันที่โอน:</span>
                  <span class="ml-1 text-gray-900 dark:text-white">{{ req.transfer_date || '-' }}</span>
                </div>
              </div>
              
              <div v-if="req.transfer_slip" class="mb-3">
                <img 
                  :src="req.transfer_slip" 
                  alt="Transfer slip" 
                  class="w-full max-h-32 object-contain rounded-lg cursor-pointer hover:opacity-80"
                  @click="window.open(req.transfer_slip, '_blank')"
                >
              </div>
              
              <div v-if="req.status === 'rejected' && req.rejection_reason" class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3 mb-3">
                <p class="text-sm text-red-700 dark:text-red-400">
                  <Icon icon="mdi:alert-circle" class="w-4 h-4 inline mr-1" />
                  เหตุผลที่ปฏิเสธ: {{ req.rejection_reason }}
                </p>
              </div>
              
              <div v-if="req.status === 'pending'" class="text-right">
                <button 
                  type="button"
                  class="px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                  :disabled="isProcessing"
                  @click="handleCancelRequest(req.id)"
                >
                  <Icon icon="mdi:close-circle" class="w-4 h-4 inline mr-1" />
                  ยกเลิกคำขอ
                </button>
              </div>
            </div>
          </div>
          
          <div v-else class="py-8 text-center text-gray-500 dark:text-gray-400">
            <Icon icon="mdi:inbox-outline" class="w-12 h-12 mx-auto mb-3" />
            <p>ยังไม่มีคำขอเติมเงิน</p>
            <button 
              type="button"
              class="mt-4 px-6 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
              @click="activeTab = 'deposit'"
            >
              เติมเงินเลย
            </button>
          </div>
        </div>
      </BaseCard>

      <!-- Withdraw Form -->
      <BaseCard v-if="activeTab === 'withdraw'">
        <div class="p-2">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">ถอนเงิน</h3>
          
          <div class="space-y-6">
            <!-- Amount -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงิน</label>
              <div class="relative">
                <input 
                  v-model.number="withdrawForm.amount"
                  type="number"
                  min="25"
                  :max="walletBalance"
                  step="1"
                  class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  placeholder="ระบุจำนวนเงิน (ขั้นต่ำ 25 บาท)"
                >
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">บาท</span>
              </div>
              <p class="text-sm text-gray-500 mt-1">ยอดเงินคงเหลือ: {{ formatMoney(walletBalance) }} | ถอนขั้นต่ำ 25 บาท</p>
              
              <!-- Quick Amounts -->
              <div class="flex flex-wrap gap-2 mt-3">
                <button 
                  v-for="amount in quickWithdrawAmounts"
                  :key="amount"
                  class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="withdrawForm.amount === amount 
                    ? 'bg-primary-500 text-white' 
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'"
                  :disabled="amount > walletBalance"
                  @click="withdrawForm.amount = amount"
                >
                  {{ amount }} บาท
                </button>
              </div>
            </div>
            
            <!-- Bank Account -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ธนาคาร</label>
              <select 
                v-model="withdrawForm.bank_account.bank_name"
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              >
                <option value="">เลือกธนาคาร</option>
                <option v-for="bank in bankOptions" :key="bank.value" :value="bank.value">
                  {{ bank.icon }} {{ bank.label }}
                </option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เลขบัญชี</label>
              <input 
                v-model="withdrawForm.bank_account.account_number"
                type="text"
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="ระบุเลขบัญชี"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อบัญชี</label>
              <input 
                v-model="withdrawForm.bank_account.account_name"
                type="text"
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="ระบุชื่อบัญชี"
              >
            </div>
            
            <!-- Fee Preview -->
            <div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-4">
              <div class="flex justify-between mb-2">
                <span class="text-gray-600 dark:text-gray-400">จำนวนถอน</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ formatMoney(withdrawPreview.amount) }}</span>
              </div>
              <div class="flex justify-between mb-2">
                <span class="text-gray-600 dark:text-gray-400">ค่าธรรมเนียม (13%)</span>
                <span class="font-medium text-red-500">-{{ formatMoney(withdrawPreview.fee) }}</span>
              </div>
              <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-600">
                <span class="font-medium text-gray-900 dark:text-white">ยอดที่จะได้รับ</span>
                <span class="font-bold text-green-500">{{ formatMoney(withdrawPreview.net) }}</span>
              </div>
            </div>
            
            <!-- Submit Button -->
            <button 
              class="w-full py-3 bg-gradient-to-r from-red-500 to-rose-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="isProcessing || withdrawForm.amount < 100 || withdrawForm.amount > walletBalance || !withdrawForm.bank_account.bank_name"
              @click="handleWithdraw"
            >
              <Icon v-if="isProcessing" icon="mdi:loading" class="w-5 h-5 animate-spin inline mr-2" />
              {{ isProcessing ? 'กำลังดำเนินการ...' : 'ส่งคำขอถอนเงิน' }}
            </button>
          </div>
        </div>
      </BaseCard>

      <!-- Transfer Form -->
      <BaseCard v-if="activeTab === 'transfer'">
        <div class="p-2">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">โอนเงินให้ผู้ใช้อื่น</h3>
          
          <div class="space-y-6">
            <!-- Recipient Search -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ค้นหาผู้รับ <span class="text-red-500">*</span>
              </label>
              
              <!-- Selected Recipient Display -->
              <div v-if="selectedRecipient" class="mb-3">
                <div class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
                  <img 
                    :src="selectedRecipient.profile_photo_url || selectedRecipient.avatar || '/default-avatar.png'" 
                    :alt="selectedRecipient.name"
                    class="w-12 h-12 rounded-full object-cover"
                  >
                  <div class="flex-grow">
                    <p class="font-medium text-gray-900 dark:text-white">{{ selectedRecipient.name || selectedRecipient.username }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ selectedRecipient.email }}</p>
                    <p v-if="selectedRecipient.personal_code" class="text-xs text-primary-500">{{ selectedRecipient.personal_code }}</p>
                  </div>
                  <button 
                    type="button"
                    class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"
                    @click="clearRecipient"
                  >
                    <Icon icon="mdi:close" class="w-5 h-5" />
                  </button>
                </div>
              </div>
              
              <!-- Search Input -->
              <div v-else class="relative">
                <div class="relative">
                  <input 
                    v-model="userSearchQuery"
                    type="text"
                    class="w-full px-4 py-3 pl-10 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    placeholder="พิมพ์ชื่อ, อีเมล หรือรหัสอ้างอิง..."
                    @input="debouncedSearch"
                    @focus="showUserDropdown = userSearchResults.length > 0"
                  >
                  <Icon 
                    :icon="userSearchLoading ? 'mdi:loading' : 'mdi:magnify'" 
                    class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
                    :class="{ 'animate-spin': userSearchLoading }"
                  />
                </div>
                
                <!-- Search Results Dropdown -->
                <div 
                  v-if="showUserDropdown && userSearchResults.length > 0"
                  class="absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg max-h-64 overflow-y-auto"
                >
                  <div 
                    v-for="user in userSearchResults" 
                    :key="user.id"
                    class="flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors"
                    @click="selectRecipient(user)"
                  >
                    <img 
                      :src="user.profile_photo_url || user.avatar || '/default-avatar.png'" 
                      :alt="user.name"
                      class="w-10 h-10 rounded-full object-cover"
                    >
                    <div class="flex-grow">
                      <p class="font-medium text-gray-900 dark:text-white">{{ user.name || user.username }}</p>
                      <p class="text-xs text-gray-500">{{ user.email }}</p>
                      <p v-if="user.personal_code" class="text-xs text-primary-500">{{ user.personal_code }}</p>
                    </div>
                    <Icon icon="mdi:chevron-right" class="w-5 h-5 text-gray-400" />
                  </div>
                </div>
                
                <!-- No Results -->
                <div 
                  v-if="showUserDropdown && userSearchQuery.length >= 2 && userSearchResults.length === 0 && !userSearchLoading"
                  class="absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg p-4 text-center"
                >
                  <Icon icon="mdi:account-search" class="w-8 h-8 text-gray-400 mx-auto mb-2" />
                  <p class="text-gray-500 dark:text-gray-400">ไม่พบผู้ใช้</p>
                </div>
              </div>
              
              <p class="text-xs text-gray-500 mt-2">
                <Icon icon="mdi:information" class="w-3.5 h-3.5 inline" />
                พิมพ์อย่างน้อย 2 ตัวอักษรเพื่อค้นหา
              </p>
            </div>
            
            <!-- Amount -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงิน</label>
              <div class="relative">
                <input 
                  v-model.number="transferForm.amount"
                  type="number"
                  min="1"
                  :max="walletBalance"
                  step="1"
                  class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  placeholder="ระบุจำนวนเงิน"
                >
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">บาท</span>
              </div>
              <p class="text-sm text-gray-500 mt-1">ยอดเงินคงเหลือ: {{ formatMoney(walletBalance) }}</p>
            </div>
            
            <!-- Message -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความ (ไม่บังคับ)</label>
              <textarea 
                v-model="transferForm.message"
                rows="3"
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="เขียนข้อความถึงผู้รับ..."
              ></textarea>
            </div>
            
            <!-- Submit Button -->
            <button 
              class="w-full py-3 bg-gradient-to-r from-blue-500 to-indigo-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="isProcessing || !transferForm.recipient_id || transferForm.amount < 1 || transferForm.amount > walletBalance"
              @click="handleTransfer"
            >
              <Icon v-if="isProcessing" icon="mdi:loading" class="w-5 h-5 animate-spin inline mr-2" />
              {{ isProcessing ? 'กำลังดำเนินการ...' : `โอนเงิน ${formatMoney(transferForm.amount)}` }}
            </button>
          </div>
        </div>
      </BaseCard>

      <!-- Convert Form -->
      <BaseCard v-if="activeTab === 'convert'">
        <div class="p-2">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">แปลงแต้มเป็นเงิน</h3>

          <div class="space-y-6">
            <!-- Exchange Rate Info -->
            <div class="bg-gradient-to-r from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 rounded-xl p-4">
              <div class="flex items-center gap-3">
                <Icon icon="mdi:information" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                <div>
                  <p class="font-medium text-amber-800 dark:text-amber-300">อัตราแลกเปลี่ยน</p>
                  <p class="text-sm text-amber-700 dark:text-amber-400">1,200 แต้ม = 1 บาท</p>
                </div>
              </div>
            </div>

            <!-- Points Input -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนแต้ม</label>
              <div class="relative">
                <input
                  v-model.number="convertForm.points"
                  type="number"
                  min="1200"
                  :max="points"
                  step="1200"
                  class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  placeholder="ระบุจำนวนแต้ม"
                >
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">แต้ม</span>
              </div>
              <p class="text-sm text-gray-500 mt-1">แต้มที่มี: {{ formatPoints(points) }} แต้ม</p>
            </div>

            <!-- Conversion Preview -->
            <div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-4">
              <div class="flex justify-between mb-2">
                <span class="text-gray-600 dark:text-gray-400">แต้มที่ใช้</span>
                <span class="font-medium text-amber-500">{{ formatPoints(conversionPreview.points) }} แต้ม</span>
              </div>
              <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-600">
                <span class="font-medium text-gray-900 dark:text-white">จะได้รับเงิน</span>
                <span class="font-bold text-green-500">{{ formatMoney(conversionPreview.money) }}</span>
              </div>
            </div>

            <!-- Submit Button -->
            <button
              class="w-full py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="isProcessing || !conversionPreview.isValid"
              @click="handleConvert"
            >
              <Icon v-if="isProcessing" icon="mdi:loading" class="w-5 h-5 animate-spin inline mr-2" />
              {{ isProcessing ? 'กำลังดำเนินการ...' : 'แปลงแต้มเป็นเงิน' }}
            </button>
          </div>
        </div>
      </BaseCard>

      <!-- Convert to Points Form -->
      <BaseCard v-if="activeTab === 'convert-to-points'">
        <div class="p-2">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">แปลงเงินเป็นแต้ม</h3>

          <div class="space-y-6">
            <!-- Exchange Rate Info -->
            <div class="bg-gradient-to-r from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 rounded-xl p-4">
              <div class="flex items-center gap-3">
                <Icon icon="mdi:information" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                <div>
                  <p class="font-medium text-emerald-800 dark:text-emerald-300">อัตราแลกเปลี่ยน</p>
                  <p class="text-sm text-emerald-700 dark:text-emerald-400">1 บาท = 1,200 แต้ม</p>
                  <p class="text-xs text-emerald-600 dark:text-emerald-500 mt-1">สำหรับการสนับสนุนโฆษณา</p>
                </div>
              </div>
            </div>

            <!-- Amount Input -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงิน</label>
              <div class="relative">
                <input
                  v-model.number="convertToPointsForm.amount"
                  type="number"
                  min="10"
                  :max="walletBalance"
                  step="10"
                  class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  placeholder="ระบุจำนวนเงิน"
                >
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">บาท</span>
              </div>
              <p class="text-sm text-gray-500 mt-1">ยอดเงินคงเหลือ: {{ formatMoney(walletBalance) }}</p>

              <!-- Quick Amounts -->
              <div class="flex flex-wrap gap-2 mt-3">
                <button
                  v-for="amount in [10, 50, 100, 500, 1000]"
                  :key="amount"
                  class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="convertToPointsForm.amount === amount
                    ? 'bg-primary-500 text-white'
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'"
                  :disabled="amount > walletBalance"
                  @click="convertToPointsForm.amount = amount"
                >
                  {{ amount }} บาท
                </button>
              </div>
            </div>

            <!-- Conversion Preview -->
            <div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-4">
              <div class="flex justify-between mb-2">
                <span class="text-gray-600 dark:text-gray-400">เงินที่ใช้</span>
                <span class="font-medium text-emerald-500">{{ formatMoney(convertToPointsPreview.amount) }}</span>
              </div>
              <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-600">
                <span class="font-medium text-gray-900 dark:text-white">จะได้รับแต้ม</span>
                <span class="font-bold text-purple-500">{{ formatPoints(convertToPointsPreview.points) }} แต้ม</span>
              </div>
            </div>

            <!-- Submit Button -->
            <button
              class="w-full py-3 bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="isProcessing || !convertToPointsPreview.isValid"
              @click="handleConvertToPoints"
            >
              <Icon v-if="isProcessing" icon="mdi:loading" class="w-5 h-5 animate-spin inline mr-2" />
              {{ isProcessing ? 'กำลังดำเนินการ...' : 'แปลงเงินเป็นแต้ม' }}
            </button>
          </div>
        </div>
      </BaseCard>

      <!-- Transaction History -->
      <BaseCard v-if="activeTab === 'history'">
        <div class="p-2">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">ประวัติการทำรายการ</h3>
          
          <div v-if="transactionsLoading" class="py-12 text-center">
            <Icon icon="mdi:loading" class="w-12 h-12 text-primary-500 animate-spin mx-auto" />
            <p class="mt-4 text-gray-500 dark:text-gray-400">กำลังโหลด...</p>
          </div>
          
          <div v-else-if="transactions.length > 0" class="space-y-2 sm:space-y-3">
            <UnifiedTransactionCard
              v-for="tx in transactions"
              :key="tx.id"
              v-bind="transformTransaction(tx)"
            />
          </div>
          
          <div v-else class="py-12 text-center text-gray-500 dark:text-gray-400">
            <Icon icon="mdi:inbox-outline" class="w-16 h-16 mx-auto mb-3" />
            <p>ยังไม่มีประวัติการทำรายการ</p>
          </div>
        </div>
      </BaseCard>
    </div>
  </div>
</template>
