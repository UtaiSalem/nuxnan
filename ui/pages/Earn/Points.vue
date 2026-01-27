<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import { usePoints } from '~/composables/usePoints'
import BaseCard from '~/components/atoms/BaseCard.vue'

definePageMeta({
  layout: 'main',
  middleware: ['auth']
})

useHead({
  title: 'แต้มของฉัน - Nuxni'
})

const authStore = useAuthStore()
const config = useRuntimeConfig()
const apiBase = computed(() => config.public.apiBase || '')

const {
  points,
  isLoading,
  error,
  getBalance,
  transfer,
  convertToWallet,
  getTransactions,
  formatPoints,
  canSpend
} = usePoints()

// Tab state
const activeTab = ref('overview') // 'overview' | 'transfer' | 'convert' | 'history'

// Transactions
const transactions = ref<any[]>([])
const transactionsLoading = ref(false)
const transactionFilters = ref({
  type: '', // earn | spend | transfer_in | transfer_out | conversion
  date_from: '',
  date_to: '',
  page: 1,
  per_page: 20
})
const transactionsPagination = ref({
  current_page: 1,
  total_pages: 1,
  total: 0
})

// Transfer form
const transferForm = ref({
  recipient_id: '',
  amount: 100,
  message: ''
})

// Convert form
const convertForm = ref({
  points: 1200 // Minimum 1200 points = 1 THB
})

// User search for transfer
const userSearchQuery = ref('')
const userSearchResults = ref<any[]>([])
const userSearchLoading = ref(false)
const selectedRecipient = ref<any>(null)
const showUserDropdown = ref(false)

// Processing states
const isProcessing = ref(false)
const processSuccess = ref(false)
const processMessage = ref('')

// Quick amounts for transfer
const quickTransferAmounts = [100, 240, 500, 1000, 2000]

// Quick amounts for convert
const quickConvertAmounts = [1200, 2400, 4800, 6000, 9600, 12000, 14400, 18000, 24000, 30000, 48000, 120000] // 1, 2, 4, 5, 8, 10, 12, 15, 20, 25, 40, 100 THB

// Computed
const pointsBalance = computed(() => authStore.points || 0)

const conversionPreview = computed(() => {
  const pts = convertForm.value.points
  const rate = 1200 // 1200 points = 1 THB
  return {
    points: pts,
    money: pts / rate,
    isValid: pts >= rate && pts <= pointsBalance.value
  }
})

// Search users for transfer
const searchUsers = async () => {
  if (userSearchQuery.value.length < 2) {
    userSearchResults.value = []
    showUserDropdown.value = false
    return
  }
  
  try {
    userSearchLoading.value = true
    const response = await $fetch(`${apiBase.value}/api/users/search`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
      },
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
  } catch (err) {
    console.error('Search users error:', err)
    userSearchResults.value = []
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

// Methods
const loadTransactions = async () => {
  try {
    transactionsLoading.value = true
    const data = await getTransactions({
      type: transactionFilters.value.type || undefined,
      date_from: transactionFilters.value.date_from || undefined,
      date_to: transactionFilters.value.date_to || undefined,
      page: transactionFilters.value.page,
      per_page: transactionFilters.value.per_page
    })
    transactions.value = data.transactions || []
    transactionsPagination.value = {
      current_page: data.current_page || 1,
      total_pages: data.total_pages || 1,
      total: data.total || 0
    }
  } catch (err) {
    console.error('Failed to load transactions:', err)
  } finally {
    transactionsLoading.value = false
  }
}

// Handle Transfer
const handleTransfer = async () => {
  // Validation
  if (!selectedRecipient.value) {
    processSuccess.value = false
    processMessage.value = 'กรุณาเลือกผู้รับ'
    return
  }
  
  if (transferForm.value.amount <= 0) {
    processSuccess.value = false
    processMessage.value = 'กรุณาระบุจำนวนแต้มที่ถูกต้อง'
    return
  }
  
  if (transferForm.value.amount > pointsBalance.value) {
    processSuccess.value = false
    processMessage.value = `แต้มของคุณไม่เพียงพอ (ต้องการ ${formatPoints(transferForm.value.amount)} แต้ม, มีอยู่ ${formatPoints(pointsBalance.value)} แต้ม)`
    return
  }
  
  try {
    isProcessing.value = true
    processSuccess.value = false
    
    await transfer({
      recipient_id: parseInt(transferForm.value.recipient_id),
      amount: transferForm.value.amount,
      message: transferForm.value.message
    })
    
    processSuccess.value = true
    processMessage.value = `โอนแต้ม ${formatPoints(transferForm.value.amount)} ให้ ${selectedRecipient.value.name} สำเร็จ!`
    
    // Refresh balance and transactions
    await getBalance()
    await loadTransactions()
    
    // Reset form
    clearRecipient()
    transferForm.value.amount = 100
    transferForm.value.message = ''
    
  } catch (err: any) {
    processSuccess.value = false
    processMessage.value = err.message || 'เกิดข้อผิดพลาดในการโอนแต้ม'
  } finally {
    isProcessing.value = false
  }
}

// Handle Convert
const handleConvert = async () => {
  // Validation
  if (convertForm.value.points < 1200) {
    processSuccess.value = false
    processMessage.value = 'จำนวนแต้มขั้นต่ำคือ 1,200 แต้ม (1 บาท)'
    return
  }
  
  if (convertForm.value.points > pointsBalance.value) {
    processSuccess.value = false
    processMessage.value = `แต้มของคุณไม่เพียงพอ (ต้องการ ${formatPoints(convertForm.value.points)} แต้ม, มีอยู่ ${formatPoints(pointsBalance.value)} แต้ม)`
    return
  }
  
  try {
    isProcessing.value = true
    processSuccess.value = false
    
    await convertToWallet(convertForm.value.points)
    
    processSuccess.value = true
    processMessage.value = `แปลง ${formatPoints(convertForm.value.points)} แต้ม เป็น ${(convertForm.value.points / 1200).toFixed(2)} บาท สำเร็จ!`
    
    // Refresh balance and transactions
    await getBalance()
    await loadTransactions()
    
    // Reset form
    convertForm.value.points = 1200
    
  } catch (err: any) {
    processSuccess.value = false
    processMessage.value = err.message || 'เกิดข้อผิดพลาดในการแปลงแต้ม'
  } finally {
    isProcessing.value = false
  }
}

const getTransactionTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    earn: 'ได้รับแต้ม',
    spend: 'ใช้แต้ม',
    transfer_in: 'รับโอนแต้ม',
    transfer_out: 'โอนแต้มออก',
    conversion: 'แปลงเป็นเงิน',
    refund: 'คืนแต้ม',
    bonus: 'โบนัส',
    reward: 'รางวัล',
    purchase: 'ซื้อ',
    donation: 'สนับสนุน'
  }
  return labels[type] || type
}

const getTransactionIcon = (type: string) => {
  const icons: Record<string, string> = {
    earn: 'mdi:arrow-down-circle',
    spend: 'mdi:arrow-up-circle',
    transfer_in: 'mdi:account-arrow-left',
    transfer_out: 'mdi:account-arrow-right',
    conversion: 'mdi:swap-horizontal-circle',
    refund: 'mdi:undo',
    bonus: 'mdi:gift',
    reward: 'mdi:trophy',
    purchase: 'mdi:cart',
    donation: 'mdi:hand-coin'
  }
  return icons[type] || 'mdi:circle'
}

const getTransactionColor = (type: string) => {
  const isPositive = ['earn', 'transfer_in', 'refund', 'bonus', 'reward'].includes(type)
  return isPositive ? 'text-green-500' : 'text-red-500'
}

const isPositiveTransaction = (tx: any) => {
  const type = tx.transaction_type || tx.type
  if (['earn', 'transfer_in', 'refund', 'bonus', 'reward'].includes(type)) return true
  if (['spend', 'transfer_out', 'payment', 'purchase', 'donation'].includes(type)) return false
  
  // Handle conversion based on source_type
  if (type === 'conversion') {
    return tx.source_type === 'wallet_to_points' || tx.metadata?.conversion_type === 'money_to_points'
  }
  
  // Fallback to amount if it's signed (though usually it's not)
  return tx.amount > 0
}

// Get display title - for transfers, show description with name, otherwise show type label
const getTransactionDisplayTitle = (tx: any) => {
  const type = tx.transaction_type || tx.type
  // For transfer types, show description which contains the name
  if ((type === 'transfer_in' || type === 'transfer_out') && tx.description) {
    return tx.description
  }
  return getTransactionTypeLabel(type)
}

// Get display subtitle - for transfers show type label, otherwise show description
const getTransactionDisplaySubtitle = (tx: any) => {
  const type = tx.transaction_type || tx.type
  // For transfer types, show description which contains the name, so no subtitle needed
  if (type === 'transfer_in' || type === 'transfer_out') {
    return null // Already shown in title
  }
  // For other types, show description if available
  return tx.description || null
}

// Check if transaction should show in 3-column layout
const isThreeColumnTransaction = (tx: any) => {
  const type = tx.transaction_type || tx.type
  return type === 'transfer_in' || type === 'transfer_out' || type === 'conversion'
}

// Get default avatar URL with soft pastel color
const getDefaultAvatar = (name: string) => {
  // Use soft light blue-gray color that's easy on the eyes
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=e2e8f0&color=64748b`
}

// Get account owner info (current logged-in user)
const getOwnerInfo = () => {
  return {
    name: authStore.user?.name || 'ฉัน',
    avatar: authStore.user?.profile_photo_url || getDefaultAvatar(authStore.user?.name || 'User')
  }
}

// Get transaction partner info (the other party in the transaction)
const getPartnerInfo = (tx: any) => {
  const type = tx.transaction_type || tx.type
  if (type === 'transfer_in') {
    // User received points - partner is the sender
    return tx.sender || null
  } else if (type === 'transfer_out') {
    // User sent points - partner is the receiver
    return tx.receiver || null
  } else if (type === 'conversion') {
    // Conversion Me -> My Wallet/Points
    const isPointsToWallet = tx.source_type === 'points_to_wallet' || tx.metadata?.conversion_type === 'points_to_money'
    return {
      name: isPointsToWallet ? 'กระเป๋าเงิน' : 'กระเป๋าพอยท์',
      avatar: null, // Will use icon fallback
      isSystem: true
    }
  }
  return null
}

// Get transaction type label for 3-column layout
const getThreeColumnLabel = (tx: any) => {
  const type = tx.transaction_type || tx.type
  if (type === 'transfer_in') return 'รับแต้มจาก'
  if (type === 'transfer_out') return 'โอนแต้มให้'
  if (type === 'conversion') {
    const isPointsToWallet = tx.source_type === 'points_to_wallet' || tx.metadata?.conversion_type === 'points_to_money'
    return isPointsToWallet ? 'แปลงเป็นเงิน' : 'แปลงจากเงิน'
  }
  return ''
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

const formatMoney = (value: number): string => {
  return new Intl.NumberFormat('th-TH', {
    style: 'currency',
    currency: 'THB',
    minimumFractionDigits: 2
  }).format(value)
}

// Watch for filter changes
watch(transactionFilters, () => {
  loadTransactions()
}, { deep: true })

// Initialize
onMounted(async () => {
  await Promise.all([
    getBalance(),
    loadTransactions()
  ])
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <!-- Page Header -->
      <div class="text-center mb-8">
        <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-yellow-400 to-amber-600 rounded-full flex items-center justify-center shadow-lg">
          <Icon icon="mdi:star-circle" class="w-10 h-10 text-white" />
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
          แต้มของฉัน
        </h1>
        <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
          จัดการแต้ม โอนแต้ม แปลงเป็นเงิน และดูประวัติการใช้งาน
        </p>
      </div>

      <!-- Main Points Card -->
      <BaseCard class="mb-6 bg-gradient-to-r from-yellow-400 via-amber-500 to-orange-500 border-0">
        <div class="text-white p-2">
          <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-6">
              <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center backdrop-blur">
                <Icon icon="mdi:star-circle" class="w-12 h-12" />
              </div>
              <div>
                <p class="text-white/80 text-sm mb-1">แต้มปัจจุบัน</p>
                <p class="text-4xl lg:text-5xl font-bold">{{ formatPoints(pointsBalance) }}</p>
                <p class="text-white/80 text-sm mt-1">Plearnd Points</p>
              </div>
            </div>
            
            <div class="flex gap-3">
              <NuxtLink
                to="/earn/donates"
                class="px-4 py-2 bg-white text-amber-600 font-semibold rounded-xl hover:bg-amber-50 transition-colors flex items-center gap-2 shadow"
              >
                <Icon icon="mdi:plus-circle" class="w-5 h-5" />
                สะสมแต้มเพิ่ม
              </NuxtLink>
              <button
                class="px-4 py-2 bg-white/20 text-white font-semibold rounded-xl hover:bg-white/30 transition-colors flex items-center gap-2 backdrop-blur"
                :disabled="pointsBalance < 100"
                @click="activeTab = 'transfer'"
              >
                <Icon icon="mdi:send" class="w-5 h-5" />
                โอนแต้ม
              </button>
            </div>
          </div>
          
          <!-- Wallet Info -->
          <div class="mt-6 pt-6 border-t border-white/20 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Icon icon="mdi:wallet" class="w-5 h-5 text-green-300" />
              <span>กระเป๋าเงิน: <strong>{{ formatMoney(authStore.user?.wallet || 0) }}</strong></span>
            </div>
            <button 
              class="text-sm underline hover:no-underline"
              :disabled="pointsBalance < 1200"
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
            { key: 'transfer', label: 'โอนแต้ม', icon: 'mdi:send' },
            { key: 'convert', label: 'แปลงเป็นเงิน', icon: 'mdi:swap-horizontal' },
            { key: 'history', label: 'ประวัติ', icon: 'mdi:history' },
          ]"
          :key="tab.key"
          class="px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap flex items-center gap-2"
          :class="activeTab === tab.key
            ? 'bg-amber-500 text-white shadow'
            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
          @click="activeTab = tab.key"
        >
          <Icon :icon="tab.icon" class="w-4 h-4" />
          {{ tab.label }}
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
          <BaseCard class="cursor-pointer hover:shadow-lg transition-shadow" @click="activeTab = 'transfer'">
            <div class="flex items-center gap-4 p-2">
              <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                <Icon icon="mdi:send" class="w-7 h-7 text-blue-500" />
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">โอนแต้ม</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">โอนแต้มให้ผู้ใช้อื่น</p>
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
                <h3 class="font-semibold text-gray-900 dark:text-white">แปลงเป็นเงิน</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">แปลงแต้มเป็นเงินในกระเป๋า</p>
              </div>
              <Icon icon="mdi:chevron-right" class="w-6 h-6 text-gray-400 ml-auto" />
            </div>
          </BaseCard>
          
          <NuxtLink to="/earn/donates" class="block">
            <BaseCard class="cursor-pointer hover:shadow-lg transition-shadow h-full">
              <div class="flex items-center gap-4 p-2">
                <div class="w-14 h-14 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                  <Icon icon="mdi:hand-coin" class="w-7 h-7 text-yellow-500" />
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900 dark:text-white">รับการสนับสนุน</h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">รับแต้มจากผู้สนับสนุน</p>
                </div>
                <Icon icon="mdi:chevron-right" class="w-6 h-6 text-gray-400 ml-auto" />
              </div>
            </BaseCard>
          </NuxtLink>
          
          <NuxtLink to="/earn/wallet" class="block">
            <BaseCard class="cursor-pointer hover:shadow-lg transition-shadow h-full">
              <div class="flex items-center gap-4 p-2">
                <div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                  <Icon icon="mdi:wallet" class="w-7 h-7 text-green-500" />
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900 dark:text-white">กระเป๋าเงิน</h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">จัดการเงินในกระเป๋า</p>
                </div>
                <Icon icon="mdi:chevron-right" class="w-6 h-6 text-gray-400 ml-auto" />
              </div>
            </BaseCard>
          </NuxtLink>
        </div>
        
        <!-- Conversion Rate Info -->
        <BaseCard class="mb-6">
          <div class="p-2">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <Icon icon="mdi:information-outline" class="w-5 h-5 text-blue-500" />
              อัตราการแลกเปลี่ยน
            </h3>
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4">
              <div class="flex items-center justify-center gap-4">
                <div class="text-center">
                  <p class="text-2xl font-bold text-amber-600">1,200</p>
                  <p class="text-sm text-gray-600 dark:text-gray-400">แต้ม</p>
                </div>
                <Icon icon="mdi:equal" class="w-6 h-6 text-gray-400" />
                <div class="text-center">
                  <p class="text-2xl font-bold text-green-600">1.00</p>
                  <p class="text-sm text-gray-600 dark:text-gray-400">บาท</p>
                </div>
              </div>
            </div>
          </div>
        </BaseCard>
        
        <!-- Recent Transactions -->
        <BaseCard>
          <div class="p-2">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">รายการล่าสุด</h3>
              <button 
                class="text-amber-500 text-sm hover:underline"
                @click="activeTab = 'history'"
              >
                ดูทั้งหมด
              </button>
            </div>
            
            <div v-if="transactionsLoading" class="py-8 text-center">
              <Icon icon="mdi:loading" class="w-8 h-8 text-amber-500 animate-spin mx-auto" />
            </div>
            
            <div v-else-if="transactions.length > 0" class="space-y-4">
              <template v-for="tx in transactions.slice(0, 5)" :key="tx.id">
                <!-- Premium Transaction Card -->
                <div 
                  class="group relative overflow-hidden p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm hover:shadow-md hover:border-amber-200 dark:hover:border-amber-900/50 transition-all duration-300"
                >
                  <!-- Background Glow for Positive Amount -->
                  <div 
                    v-if="isPositiveTransaction(tx)"
                    class="absolute -right-10 -top-10 w-32 h-32 bg-green-500/5 blur-3xl pointer-events-none group-hover:bg-green-500/10 transition-colors"
                  ></div>
                  <!-- Background Glow for Negative Amount -->
                  <div 
                    v-else
                    class="absolute -right-10 -top-10 w-32 h-32 bg-red-500/5 blur-3xl pointer-events-none group-hover:bg-red-500/10 transition-colors"
                  ></div>

                  <div class="flex items-center justify-between gap-4 relative z-10">
                    <!-- Visualization: Source/Destination OR Activity Icon -->
                    <div class="flex items-center gap-4 flex-grow">
                      <!-- Case 1: 3-Column Style (Transfers/Conversions) -->
                      <template v-if="isThreeColumnTransaction(tx)">
                        <div class="flex items-center gap-2 sm:gap-4 flex-grow max-w-[80%]">
                          <!-- Source -->
                          <div class="flex flex-col items-center text-center min-w-[64px]">
                            <img 
                              :src="getOwnerInfo().avatar" 
                              :alt="getOwnerInfo().name"
                              class="w-12 h-12 rounded-full object-cover ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700 shadow-sm"
                            >
                            <span class="text-[10px] sm:text-xs font-medium text-gray-500 mt-1 truncate max-w-[64px]">
                              {{ getOwnerInfo().name.split(' ')[0] }}
                            </span>
                          </div>

                          <!-- Connection -->
                          <div class="flex-grow flex flex-col items-center justify-center -mt-4 px-1">
                            <div class="w-full h-px bg-gradient-to-r from-transparent via-gray-200 dark:via-gray-700 to-transparent relative">
                              <Icon 
                                :icon="isPositiveTransaction(tx) ? 'mdi:chevron-left' : 'mdi:chevron-right'"
                                class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-5 h-5"
                                :class="isPositiveTransaction(tx) ? 'text-green-500 animate-pulse' : 'text-amber-500'"
                              />
                            </div>
                            <span 
                              class="text-[9px] uppercase tracking-wider font-bold mt-2"
                              :class="isPositiveTransaction(tx) ? 'text-green-600' : 'text-amber-600'"
                            >
                              {{ getThreeColumnLabel(tx) }}
                            </span>
                          </div>

                          <!-- Destination -->
                          <div class="flex flex-col items-center text-center min-w-[64px]">
                            <template v-if="getPartnerInfo(tx)?.isSystem">
                              <div class="w-12 h-12 rounded-full flex items-center justify-center bg-gray-50 dark:bg-gray-800 ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700 shadow-sm transition-transform group-hover:scale-105">
                                <Icon 
                                  :icon="getPartnerInfo(tx)?.name === 'กระเป๋าเงิน' ? 'mdi:wallet-outline' : 'mdi:star-circle-outline'" 
                                  class="w-7 h-7"
                                  :class="getPartnerInfo(tx)?.name === 'กระเป๋าเงิน' ? 'text-indigo-500' : 'text-amber-500'" 
                                />
                              </div>
                            </template>
                            <template v-else-if="getPartnerInfo(tx)">
                              <img 
                                :src="getPartnerInfo(tx)?.avatar || getDefaultAvatar(getPartnerInfo(tx)?.name || 'User')" 
                                :alt="getPartnerInfo(tx)?.name"
                                class="w-12 h-12 rounded-full object-cover ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700 shadow-sm transition-transform group-hover:scale-105"
                              >
                            </template>
                            <template v-else>
                              <div class="w-12 h-12 rounded-full flex items-center justify-center bg-gray-50 dark:bg-gray-800 ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700">
                                <Icon icon="mdi:account-question-outline" class="w-7 h-7 text-gray-400" />
                              </div>
                            </template>
                            <span class="text-[10px] sm:text-xs font-medium text-gray-500 mt-1 truncate max-w-[64px]">
                              {{ (getPartnerInfo(tx)?.name || '?').split(' ')[0] }}
                            </span>
                          </div>
                        </div>
                      </template>

                      <!-- Case 2: Activity Style (Earn/Spend) -->
                      <template v-else>
                        <div class="flex items-center gap-4">
                          <div 
                            class="w-12 h-12 rounded-2xl flex items-center justify-center transform transition-all group-hover:scale-110 group-hover:rotate-3 shadow-sm"
                            :class="isPositiveTransaction(tx) 
                              ? 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-800' 
                              : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-100 dark:border-red-800'"
                          >
                            <Icon 
                              :icon="getTransactionIcon(tx.transaction_type || tx.type)" 
                              class="w-6 h-6" 
                            />
                          </div>
                          <div class="max-w-[180px] sm:max-w-none">
                            <p class="font-bold text-gray-900 dark:text-white leading-tight">
                              {{ getTransactionDisplayTitle(tx) }}
                            </p>
                            <p v-if="getTransactionDisplaySubtitle(tx)" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 mt-1 line-clamp-1">
                              {{ getTransactionDisplaySubtitle(tx) }}
                            </p>
                            <div class="flex items-center gap-2 mt-1.5">
                              <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 uppercase">
                                {{ getTransactionTypeLabel(tx.transaction_type || tx.type) }}
                              </span>
                              <span class="text-[10px] text-gray-400">{{ formatDate(tx.created_at) }}</span>
                            </div>
                          </div>
                        </div>
                      </template>
                    </div>

                    <!-- Amount and Balance -->
                    <div class="text-right flex flex-col items-end min-w-[100px]">
                      <div 
                        class="flex items-center font-black text-lg sm:text-xl"
                        :class="isPositiveTransaction(tx) ? 'text-green-500' : 'text-red-500'"
                      >
                        <span class="text-base mr-0.5">{{ isPositiveTransaction(tx) ? '+' : '-' }}</span>
                        {{ formatPoints(Math.abs(tx.amount)) }}
                      </div>
                      <div class="flex flex-col items-end mt-1">
                        <div class="text-[10px] font-medium text-gray-400 dark:text-gray-500 flex items-center gap-1 bg-gray-50 dark:bg-gray-900/50 px-2 py-0.5 rounded-lg border border-gray-100 dark:border-gray-800">
                          <span>คงเหลือ:</span>
                          <span class="text-gray-600 dark:text-gray-300">{{ formatPoints(tx.balance_after || 0) }}</span>
                        </div>
                        <p v-if="isThreeColumnTransaction(tx)" class="text-[9px] text-gray-400 mt-1">{{ formatDate(tx.created_at) }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </div>
            
            <div v-else class="py-8 text-center text-gray-500 dark:text-gray-400">
              <Icon icon="mdi:inbox-outline" class="w-12 h-12 mx-auto mb-3" />
              <p>ยังไม่มีรายการ</p>
            </div>
          </div>
        </BaseCard>
      </div>

      <!-- Transfer Form -->
      <BaseCard v-if="activeTab === 'transfer'">
        <div class="p-2">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">โอนแต้ม</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">โอนแต้มของคุณให้ผู้ใช้อื่น</p>
          
          <div class="space-y-6">
            <!-- Recipient Search -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ผู้รับ <span class="text-red-500">*</span>
              </label>
              
              <!-- Selected recipient display -->
              <div v-if="selectedRecipient" class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl mb-2">
                <img 
                  :src="selectedRecipient.avatar || '/default-avatar.png'" 
                  :alt="selectedRecipient.name"
                  class="w-10 h-10 rounded-full object-cover"
                >
                <div class="flex-grow">
                  <p class="font-medium text-gray-900 dark:text-white">{{ selectedRecipient.name }}</p>
                  <p class="text-xs text-gray-500">{{ selectedRecipient.email_masked || selectedRecipient.email }}</p>
                </div>
                <button 
                  type="button"
                  class="p-1 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/20 rounded"
                  @click="clearRecipient"
                >
                  <Icon icon="mdi:close" class="w-5 h-5" />
                </button>
              </div>
              
              <!-- Search input -->
              <div v-else class="relative">
                <input 
                  v-model="userSearchQuery"
                  type="text"
                  class="w-full px-4 py-3 pl-10 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                  placeholder="ค้นหาชื่อ, อีเมล หรือรหัสสมาชิก..."
                  @input="debouncedSearch"
                  @focus="showUserDropdown = userSearchResults.length > 0"
                >
                <Icon icon="mdi:magnify" class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                
                <!-- Loading indicator -->
                <Icon 
                  v-if="userSearchLoading" 
                  icon="mdi:loading" 
                  class="w-5 h-5 text-amber-500 animate-spin absolute right-3 top-1/2 -translate-y-1/2" 
                />
                
                <!-- Search results dropdown -->
                <div 
                  v-if="showUserDropdown && userSearchResults.length > 0"
                  class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg max-h-60 overflow-y-auto"
                >
                  <button
                    v-for="user in userSearchResults"
                    :key="user.id"
                    type="button"
                    class="w-full flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-left"
                    @click="selectRecipient(user)"
                  >
                    <img 
                      :src="user.avatar || '/default-avatar.png'" 
                      :alt="user.name"
                      class="w-10 h-10 rounded-full object-cover"
                    >
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">{{ user.name }}</p>
                      <p class="text-xs text-gray-500">{{ user.email_masked || user.email }}</p>
                    </div>
                  </button>
                </div>
                
                <!-- No results -->
                <div 
                  v-if="showUserDropdown && userSearchQuery.length >= 2 && !userSearchLoading && userSearchResults.length === 0"
                  class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg p-4 text-center text-gray-500"
                >
                  ไม่พบผู้ใช้
                </div>
              </div>
            </div>
            
            <!-- Amount -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                จำนวนแต้ม <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <input 
                  v-model.number="transferForm.amount"
                  type="number"
                  min="1"
                  :max="pointsBalance"
                  class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                  placeholder="ระบุจำนวนแต้ม"
                >
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">แต้ม</span>
              </div>
              <p class="text-xs text-gray-500 mt-1">
                แต้มของคุณ: {{ formatPoints(pointsBalance) }} แต้ม
              </p>
              
              <!-- Quick Amounts -->
              <div class="flex flex-wrap gap-2 mt-3">
                <button 
                  v-for="amount in quickTransferAmounts"
                  :key="amount"
                  type="button"
                  class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="transferForm.amount === amount 
                    ? 'bg-amber-500 text-white' 
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'"
                  :disabled="amount > pointsBalance"
                  @click="transferForm.amount = amount"
                >
                  {{ formatPoints(amount) }}
                </button>
              </div>
            </div>
            
            <!-- Message -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความ</label>
              <textarea 
                v-model="transferForm.message"
                rows="2"
                maxlength="255"
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                placeholder="ข้อความถึงผู้รับ (ไม่บังคับ)"
              ></textarea>
            </div>
            
            <!-- Submit Button -->
            <button 
              type="button"
              class="w-full py-3 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="isProcessing || !selectedRecipient || transferForm.amount <= 0 || transferForm.amount > pointsBalance"
              @click="handleTransfer"
            >
              <Icon v-if="isProcessing" icon="mdi:loading" class="w-5 h-5 animate-spin inline mr-2" />
              {{ isProcessing ? 'กำลังดำเนินการ...' : `โอน ${formatPoints(transferForm.amount)} แต้ม` }}
            </button>
          </div>
        </div>
      </BaseCard>

      <!-- Convert Form -->
      <BaseCard v-if="activeTab === 'convert'">
        <div class="p-2">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">แปลงแต้มเป็นเงิน</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">แปลงแต้มของคุณเป็นเงินในกระเป๋า</p>
          
          <!-- Exchange Rate Info -->
          <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 mb-6">
            <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300">
              <Icon icon="mdi:information" class="w-5 h-5" />
              <span class="text-sm">อัตราแลกเปลี่ยน: <strong>1,200 แต้ม = 1 บาท</strong></span>
            </div>
          </div>
          
          <div class="space-y-6">
            <!-- Quick Amounts -->
              <div class="flex flex-wrap gap-2 mb-4">
                <button 
                  v-for="amount in quickConvertAmounts"
                  :key="amount"
                  type="button"
                  class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                  :class="convertForm.points === amount 
                    ? 'bg-amber-500 text-white' 
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'"
                  :disabled="amount > pointsBalance"
                  @click="convertForm.points = amount"
                >
                  {{ formatPoints(amount) }} ({{ (amount / 1200).toFixed(0) }}฿)
                </button>
              </div>

            <!-- Points Amount -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                จำนวนแต้มที่ต้องการแปลง <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <input 
                  v-model.number="convertForm.points"
                  type="number"
                  min="1200"
                  step="1200"
                  :max="pointsBalance"
                  class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                  placeholder="ระบุจำนวนแต้ม"
                >
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">แต้ม</span>
              </div>
              <p class="text-xs text-gray-500 mt-1">
                แต้มของคุณ: {{ formatPoints(pointsBalance) }} แต้ม | ขั้นต่ำ: 1,200 แต้ม
              </p>
            </div>
            
            <!-- Conversion Preview -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
              <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">ตัวอย่างการแปลง</h4>
              <div class="flex items-center justify-between">
                <div class="text-center">
                  <p class="text-2xl font-bold text-amber-600">{{ formatPoints(conversionPreview.points) }}</p>
                  <p class="text-sm text-gray-500">แต้ม</p>
                </div>
                <Icon icon="mdi:arrow-right" class="w-8 h-8 text-gray-400" />
                <div class="text-center">
                  <p class="text-2xl font-bold text-green-600">{{ formatMoney(conversionPreview.money) }}</p>
                  <p class="text-sm text-gray-500">เข้ากระเป๋า</p>
                </div>
              </div>
              
              <div 
                v-if="!conversionPreview.isValid && convertForm.points > 0" 
                class="mt-3 text-center text-sm text-red-500"
              >
                <Icon icon="mdi:alert-circle" class="w-4 h-4 inline mr-1" />
                <span v-if="convertForm.points < 1200">ขั้นต่ำ 1,200 แต้ม</span>
                <span v-else>แต้มไม่เพียงพอ</span>
              </div>
            </div>
            
            <!-- Submit Button -->
            <button 
              type="button"
              class="w-full py-3 bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="isProcessing || !conversionPreview.isValid"
              @click="handleConvert"
            >
              <Icon v-if="isProcessing" icon="mdi:loading" class="w-5 h-5 animate-spin inline mr-2" />
              {{ isProcessing ? 'กำลังดำเนินการ...' : `แปลง ${formatPoints(convertForm.points)} แต้ม เป็น ${formatMoney(conversionPreview.money)}` }}
            </button>
          </div>
        </div>
      </BaseCard>

      <!-- Transaction History -->
      <BaseCard v-if="activeTab === 'history'">
        <div class="p-2">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">ประวัติการทำรายการ</h3>
          
          <!-- Filters -->
          <div class="flex flex-wrap gap-3 mb-6">
            <select 
              v-model="transactionFilters.type"
              class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
            >
              <option value="">ทุกประเภท</option>
              <option value="earn">ได้รับแต้ม</option>
              <option value="spend">ใช้แต้ม</option>
              <option value="transfer_in">รับโอนแต้ม</option>
              <option value="transfer_out">โอนแต้มออก</option>
              <option value="conversion">แปลงเป็นเงิน</option>
            </select>
            
            <input 
              v-model="transactionFilters.date_from"
              type="date"
              class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
            >
            
            <input 
              v-model="transactionFilters.date_to"
              type="date"
              class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
            >
            
            <button 
              type="button"
              class="px-4 py-2 text-amber-600 dark:text-amber-400 text-sm hover:underline"
              @click="transactionFilters = { type: '', date_from: '', date_to: '', page: 1, per_page: 20 }"
            >
              ล้างตัวกรอง
            </button>
          </div>
          
          <!-- Transactions List -->
          <div v-if="transactionsLoading" class="py-8 text-center">
            <Icon icon="mdi:loading" class="w-8 h-8 text-amber-500 animate-spin mx-auto" />
            <p class="text-gray-500 mt-2">กำลังโหลด...</p>
          </div>
          
          <div v-else-if="transactions.length > 0" class="space-y-4">
            <template v-for="tx in transactions" :key="tx.id">
              <!-- Premium Transaction Card -->
              <div 
                class="group relative overflow-hidden p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm hover:shadow-md hover:border-amber-200 dark:hover:border-amber-900/50 transition-all duration-300"
              >
                <!-- Background Glow for Positive Amount -->
                <div 
                  v-if="isPositiveTransaction(tx)"
                  class="absolute -right-10 -top-10 w-32 h-32 bg-green-500/5 blur-3xl pointer-events-none group-hover:bg-green-500/10 transition-colors"
                ></div>
                <!-- Background Glow for Negative Amount -->
                <div 
                  v-else
                  class="absolute -right-10 -top-10 w-32 h-32 bg-red-500/5 blur-3xl pointer-events-none group-hover:bg-red-500/10 transition-colors"
                ></div>

                <div class="flex items-center justify-between gap-4 relative z-10">
                  <!-- Visualization -->
                  <div class="flex items-center gap-4 flex-grow">
                    <!-- Case 1: 3-Column Style -->
                    <template v-if="isThreeColumnTransaction(tx)">
                      <div class="flex items-center gap-4 flex-grow max-w-[80%]">
                        <div class="flex flex-col items-center text-center min-w-[72px]">
                          <img 
                            :src="getOwnerInfo().avatar" 
                            :alt="getOwnerInfo().name"
                            class="w-14 h-14 rounded-full object-cover ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700 shadow-sm"
                          >
                          <span class="text-xs font-medium text-gray-500 mt-1 truncate max-w-[72px]">
                            {{ getOwnerInfo().name }}
                          </span>
                        </div>

                        <div class="flex-grow flex flex-col items-center justify-center -mt-4 px-2">
                          <div class="w-full h-px bg-gradient-to-r from-transparent via-gray-200 dark:via-gray-700 to-transparent relative">
                            <Icon 
                              :icon="isPositiveTransaction(tx) ? 'mdi:chevron-left' : 'mdi:chevron-right'"
                              class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-6 h-6"
                              :class="isPositiveTransaction(tx) ? 'text-green-500 animate-pulse' : 'text-amber-500'"
                            />
                          </div>
                          <span 
                            class="text-[10px] uppercase tracking-wider font-bold mt-2"
                            :class="isPositiveTransaction(tx) ? 'text-green-600' : 'text-amber-600'"
                          >
                            {{ getThreeColumnLabel(tx) }}
                          </span>
                        </div>

                        <div class="flex flex-col items-center text-center min-w-[72px]">
                          <template v-if="getPartnerInfo(tx)?.isSystem">
                            <div class="w-14 h-14 rounded-full flex items-center justify-center bg-gray-50 dark:bg-gray-800 ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700 shadow-sm">
                              <Icon 
                                :icon="getPartnerInfo(tx)?.name === 'กระเป๋าเงิน' ? 'mdi:wallet-outline' : 'mdi:star-circle-outline'" 
                                class="w-8 h-8"
                                :class="getPartnerInfo(tx)?.name === 'กระเป๋าเงิน' ? 'text-indigo-500' : 'text-amber-500'" 
                              />
                            </div>
                          </template>
                          <template v-else-if="getPartnerInfo(tx)">
                            <img 
                              :src="getPartnerInfo(tx)?.avatar || getDefaultAvatar(getPartnerInfo(tx)?.name || 'User')" 
                              :alt="getPartnerInfo(tx)?.name"
                              class="w-14 h-14 rounded-full object-cover ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700 shadow-sm"
                            >
                          </template>
                          <template v-else>
                            <div class="w-14 h-14 rounded-full flex items-center justify-center bg-gray-50 dark:bg-gray-800 ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700">
                              <Icon icon="mdi:account-question-outline" class="w-8 h-8 text-gray-400" />
                            </div>
                          </template>
                          <span class="text-xs font-medium text-gray-500 mt-1 truncate max-w-[72px]">
                            {{ getPartnerInfo(tx)?.name || '?' }}
                          </span>
                        </div>
                      </div>
                    </template>

                    <!-- Case 2: Activity Style -->
                    <template v-else>
                      <div class="flex items-center gap-4">
                        <div 
                          class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm"
                          :class="isPositiveTransaction(tx) 
                            ? 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-800' 
                            : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-100 dark:border-red-800'"
                        >
                          <Icon 
                            :icon="getTransactionIcon(tx.transaction_type || tx.type)" 
                            class="w-6 h-6" 
                          />
                        </div>
                        <div>
                          <p class="font-bold text-gray-900 dark:text-white leading-tight">
                            {{ getTransactionDisplayTitle(tx) }}
                          </p>
                          <p v-if="getTransactionDisplaySubtitle(tx)" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 mt-1">
                            {{ getTransactionDisplaySubtitle(tx) }}
                          </p>
                          <div class="flex items-center gap-2 mt-1.5">
                            <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 uppercase">
                              {{ getTransactionTypeLabel(tx.transaction_type || tx.type) }}
                            </span>
                            <span class="text-[10px] text-gray-400">{{ formatDate(tx.created_at) }}</span>
                          </div>
                        </div>
                      </div>
                    </template>
                  </div>

                  <!-- Amount and Balance -->
                  <div class="text-right flex flex-col items-end min-w-[120px]">
                    <div 
                      class="flex items-center font-black text-xl"
                      :class="isPositiveTransaction(tx) ? 'text-green-500' : 'text-red-500'"
                    >
                      <span class="text-base mr-0.5">{{ isPositiveTransaction(tx) ? '+' : '-' }}</span>
                      {{ formatPoints(Math.abs(tx.amount)) }}
                    </div>
                    <div class="flex flex-col items-end mt-1">
                      <div class="text-xs font-medium text-gray-400 dark:text-gray-500 flex items-center gap-1 bg-gray-50 dark:bg-gray-900/50 px-3 py-1 rounded-lg border border-gray-100 dark:border-gray-800 mt-1">
                        <span>ยอดคงเหลือ:</span>
                        <span class="text-gray-700 dark:text-gray-200 font-bold">{{ formatPoints(tx.balance_after || 0) }}</span>
                      </div>
                      <p v-if="isThreeColumnTransaction(tx)" class="text-[10px] text-gray-400 mt-1.5">{{ formatDate(tx.created_at) }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </template>
            <!-- Pagination -->
            <div v-if="transactionsPagination.total_pages > 1" class="flex justify-center gap-2 mt-6">
              <button 
                type="button"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                :class="transactionFilters.page === 1 
                  ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                :disabled="transactionFilters.page === 1"
                @click="transactionFilters.page--"
              >
                <Icon icon="mdi:chevron-left" class="w-5 h-5" />
              </button>
              
              <span class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                หน้า {{ transactionsPagination.current_page }} / {{ transactionsPagination.total_pages }}
              </span>
              
              <button 
                type="button"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                :class="transactionFilters.page === transactionsPagination.total_pages 
                  ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                :disabled="transactionFilters.page === transactionsPagination.total_pages"
                @click="transactionFilters.page++"
              >
                <Icon icon="mdi:chevron-right" class="w-5 h-5" />
              </button>
            </div>
          </div>
          
          <div v-else class="py-12 text-center text-gray-500 dark:text-gray-400">
            <Icon icon="mdi:inbox-outline" class="w-16 h-16 mx-auto mb-4" />
            <p class="text-lg">ยังไม่มีประวัติการทำรายการ</p>
            <p class="text-sm mt-1">เมื่อคุณทำรายการ จะแสดงที่นี่</p>
          </div>
        </div>
      </BaseCard>

      <!-- Quick Actions -->
      <div v-if="activeTab === 'overview'" class="mt-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 text-center">
          ทำกิจกรรมเพื่อสะสมแต้ม
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <NuxtLink to="/earn/donates" class="block">
            <BaseCard class="h-full hover:shadow-lg transition-shadow cursor-pointer group text-center">
              <div class="w-12 h-12 mx-auto mb-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                <Icon icon="mdi:hand-coin" class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
              </div>
              <h3 class="font-semibold text-gray-900 dark:text-white text-sm">รับการสนับสนุน</h3>
              <p class="text-xs text-green-600 dark:text-green-400 mt-1">+240 แต้ม</p>
            </BaseCard>
          </NuxtLink>
          
          <NuxtLink to="/newsfeed" class="block">
            <BaseCard class="h-full hover:shadow-lg transition-shadow cursor-pointer group text-center">
              <div class="w-12 h-12 mx-auto mb-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                <Icon icon="mdi:post-outline" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
              </div>
              <h3 class="font-semibold text-gray-900 dark:text-white text-sm">โพสต์เนื้อหา</h3>
              <p class="text-xs text-green-600 dark:text-green-400 mt-1">รับแต้มจากการถูกใจ</p>
            </BaseCard>
          </NuxtLink>
          
          <NuxtLink to="/courses" class="block">
            <BaseCard class="h-full hover:shadow-lg transition-shadow cursor-pointer group text-center">
              <div class="w-12 h-12 mx-auto mb-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                <Icon icon="mdi:school" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
              </div>
              <h3 class="font-semibold text-gray-900 dark:text-white text-sm">เรียนรู้</h3>
              <p class="text-xs text-green-600 dark:text-green-400 mt-1">รับแต้มจากบทเรียน</p>
            </BaseCard>
          </NuxtLink>
          
          <NuxtLink to="/quests" class="block">
            <BaseCard class="h-full hover:shadow-lg transition-shadow cursor-pointer group text-center">
              <div class="w-12 h-12 mx-auto mb-3 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                <Icon icon="mdi:trophy" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
              </div>
              <h3 class="font-semibold text-gray-900 dark:text-white text-sm">ทำภารกิจ</h3>
              <p class="text-xs text-green-600 dark:text-green-400 mt-1">รับแต้มโบนัส</p>
            </BaseCard>
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>
