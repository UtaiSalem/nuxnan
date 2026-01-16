<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import { useAdminWallet } from '~/composables/useAdminWallet'
import BaseCard from '~/components/atoms/BaseCard.vue'

definePageMeta({
  layout: 'main',
  middleware: ['auth', 'admin']
})

useHead({
  title: 'จัดการ Wallet - Admin'
})

const authStore = useAuthStore()
const { 
  getStats, 
  getPendingWithdrawals,
  approveWithdrawal,
  rejectWithdrawal,
  adjustWallet,
  getAnalytics,
  isLoading 
} = useAdminWallet()

// State
const activeTab = ref('overview') // 'overview' | 'withdrawals' | 'users' | 'analytics'
const stats = ref<any>(null)
const pendingWithdrawals = ref<any[]>([])
const analytics = ref<any>(null)

// Form states
const showAdjustModal = ref(false)
const adjustForm = ref({
  user_id: '',
  amount: 0,
  type: 'add', // 'add' | 'deduct' | 'set'
  reason: ''
})

const showRejectModal = ref(false)
const rejectingId = ref<number | null>(null)
const rejectReason = ref('')

// Load data
const loadData = async () => {
  try {
    const [statsData, withdrawalsData, analyticsData] = await Promise.all([
      getStats(),
      getPendingWithdrawals(),
      getAnalytics()
    ])
    
    if (statsData) stats.value = statsData
    if (withdrawalsData) pendingWithdrawals.value = withdrawalsData.withdrawals || withdrawalsData
    if (analyticsData) analytics.value = analyticsData
  } catch (error) {
    console.error('Failed to load admin wallet data:', error)
  }
}

// Withdrawal management
const handleApprove = async (withdrawalId: number) => {
  if (confirm('ต้องการอนุมัติการถอนเงินนี้หรือไม่?')) {
    try {
      await approveWithdrawal(withdrawalId)
      await loadData()
    } catch (error) {
      console.error('Failed to approve withdrawal:', error)
    }
  }
}

const openRejectModal = (withdrawalId: number) => {
  rejectingId.value = withdrawalId
  rejectReason.value = ''
  showRejectModal.value = true
}

const handleReject = async () => {
  if (!rejectingId.value) return
  
  try {
    await rejectWithdrawal(rejectingId.value, rejectReason.value)
    showRejectModal.value = false
    rejectingId.value = null
    await loadData()
  } catch (error) {
    console.error('Failed to reject withdrawal:', error)
  }
}

// Adjust wallet
const handleAdjustWallet = async () => {
  try {
    await adjustWallet(parseInt(adjustForm.value.user_id), {
      amount: adjustForm.value.amount,
      type: adjustForm.value.type,
      reason: adjustForm.value.reason
    })
    showAdjustModal.value = false
    adjustForm.value = { user_id: '', amount: 0, type: 'add', reason: '' }
    await loadData()
  } catch (error) {
    console.error('Failed to adjust wallet:', error)
  }
}

// Helpers
const formatMoney = (value: number): string => {
  return new Intl.NumberFormat('th-TH', {
    style: 'currency',
    currency: 'THB'
  }).format(value)
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

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    pending: 'รอดำเนินการ',
    approved: 'อนุมัติแล้ว',
    rejected: 'ปฏิเสธ',
    completed: 'เสร็จสิ้น',
    cancelled: 'ยกเลิก'
  }
  return labels[status] || status
}

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    approved: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    completed: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

onMounted(loadData)
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <!-- Page Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">จัดการ Wallet</h1>
          <p class="text-gray-600 dark:text-gray-400">Admin Wallet Management</p>
        </div>
        <div class="flex gap-3">
          <button
            class="px-4 py-2 bg-primary-500 text-white rounded-xl hover:bg-primary-600 transition-colors flex items-center gap-2"
            @click="showAdjustModal = true"
          >
            <Icon icon="mdi:wallet-plus" class="w-5 h-5" />
            ปรับยอดผู้ใช้
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div v-if="stats" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <BaseCard>
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center">
              <Icon icon="mdi:wallet" class="w-7 h-7 text-emerald-500" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatMoney(stats.total_wallet || 0) }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ยอดเงินทั้งหมด</p>
            </div>
          </div>
        </BaseCard>
        
        <BaseCard>
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
              <Icon icon="mdi:clock-outline" class="w-7 h-7 text-yellow-500" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.pending_withdrawals || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">รอดำเนินการ</p>
            </div>
          </div>
        </BaseCard>
        
        <BaseCard>
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
              <Icon icon="mdi:check-circle" class="w-7 h-7 text-green-500" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatMoney(stats.completed_withdrawals || 0) }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ถอนสำเร็จ</p>
            </div>
          </div>
        </BaseCard>
        
        <BaseCard>
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
              <Icon icon="mdi:plus-circle" class="w-7 h-7 text-blue-500" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatMoney(stats.total_deposits || 0) }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">เงินฝากทั้งหมด</p>
            </div>
          </div>
        </BaseCard>
      </div>

      <!-- Tab Navigation -->
      <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
        <button 
          v-for="tab in [
            { key: 'overview', label: 'ภาพรวม', icon: 'mdi:view-dashboard' },
            { key: 'withdrawals', label: 'คำขอถอนเงิน', icon: 'mdi:bank-transfer-out', badge: stats?.pending_withdrawals },
            { key: 'analytics', label: 'วิเคราะห์', icon: 'mdi:chart-bar' },
          ]"
          :key="tab.key"
          class="px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap flex items-center gap-2 relative"
          :class="activeTab === tab.key 
            ? 'bg-primary-500 text-white shadow' 
            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
          @click="activeTab = tab.key"
        >
          <Icon :icon="tab.icon" class="w-4 h-4" />
          {{ tab.label }}
          <span 
            v-if="tab.badge && tab.badge > 0"
            class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
          >
            {{ tab.badge }}
          </span>
        </button>
      </div>

      <!-- Tab Content -->
      <div v-if="isLoading" class="py-12 text-center">
        <Icon icon="mdi:loading" class="w-12 h-12 text-primary-500 animate-spin mx-auto" />
      </div>
      
      <div v-else>
        <!-- Overview Tab -->
        <div v-if="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Daily Stats -->
          <BaseCard>
            <div class="p-2">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">สถิติธุรกรรม</h3>
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <span class="text-gray-600 dark:text-gray-400">เงินฝากทั้งหมด</span>
                  <span class="font-bold text-green-500">{{ formatMoney(stats?.total_deposits || 0) }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-gray-600 dark:text-gray-400">ถอนเงินทั้งหมด</span>
                  <span class="font-bold text-red-500">{{ formatMoney(stats?.total_withdrawals || 0) }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-gray-600 dark:text-gray-400">โอนเงินทั้งหมด</span>
                  <span class="font-bold text-blue-500">{{ formatMoney(stats?.total_transfers || 0) }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-gray-600 dark:text-gray-400">แปลงจากแต้ม</span>
                  <span class="font-bold text-purple-500">{{ formatMoney(stats?.total_conversions || 0) }}</span>
                </div>
              </div>
            </div>
          </BaseCard>
          
          <!-- Pending Withdrawals Summary -->
          <BaseCard>
            <div class="p-2">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">รอดำเนินการ</h3>
                <button 
                  class="text-primary-500 text-sm hover:underline"
                  @click="activeTab = 'withdrawals'"
                >
                  ดูทั้งหมด
                </button>
              </div>
              
              <div v-if="pendingWithdrawals.length > 0" class="space-y-3">
                <div 
                  v-for="withdrawal in pendingWithdrawals.slice(0, 3)" 
                  :key="withdrawal.id"
                  class="flex items-center gap-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl"
                >
                  <img 
                    :src="withdrawal.user?.avatar || '/images/default-avatar.png'" 
                    class="w-10 h-10 rounded-full object-cover"
                  >
                  <div class="flex-grow">
                    <p class="font-medium text-gray-900 dark:text-white">{{ withdrawal.user?.name }}</p>
                    <p class="text-xs text-gray-500">{{ formatDate(withdrawal.created_at) }}</p>
                  </div>
                  <p class="font-bold text-red-500">{{ formatMoney(withdrawal.amount) }}</p>
                </div>
              </div>
              <div v-else class="text-center py-4 text-gray-500">
                <Icon icon="mdi:check-all" class="w-8 h-8 mx-auto mb-2 text-green-500" />
                <p>ไม่มีคำขอรอดำเนินการ</p>
              </div>
            </div>
          </BaseCard>
        </div>

        <!-- Withdrawals Tab -->
        <div v-if="activeTab === 'withdrawals'">
          <BaseCard>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">ผู้ใช้</th>
                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">จำนวน</th>
                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">ธนาคาร</th>
                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">วันที่</th>
                    <th class="text-center py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">สถานะ</th>
                    <th class="text-right py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr 
                    v-for="withdrawal in pendingWithdrawals" 
                    :key="withdrawal.id"
                    class="border-b border-gray-100 dark:border-gray-700"
                  >
                    <td class="py-3 px-4">
                      <div class="flex items-center gap-3">
                        <img 
                          :src="withdrawal.user?.avatar || '/images/default-avatar.png'" 
                          class="w-10 h-10 rounded-full object-cover"
                        >
                        <div>
                          <p class="font-medium text-gray-900 dark:text-white">{{ withdrawal.user?.name }}</p>
                          <p class="text-xs text-gray-500">ID: {{ withdrawal.user_id }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="py-3 px-4">
                      <p class="font-bold text-red-500">{{ formatMoney(withdrawal.amount) }}</p>
                      <p class="text-xs text-gray-500">ค่าธรรมเนียม: {{ formatMoney(withdrawal.fee || 0) }}</p>
                    </td>
                    <td class="py-3 px-4">
                      <p class="text-gray-900 dark:text-white">{{ withdrawal.bank_account?.bank_name }}</p>
                      <p class="text-xs text-gray-500">{{ withdrawal.bank_account?.account_number }}</p>
                    </td>
                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400 text-sm">
                      {{ formatDate(withdrawal.created_at) }}
                    </td>
                    <td class="py-3 px-4 text-center">
                      <span 
                        class="px-2 py-1 rounded-full text-xs font-medium"
                        :class="getStatusColor(withdrawal.status)"
                      >
                        {{ getStatusLabel(withdrawal.status) }}
                      </span>
                    </td>
                    <td class="py-3 px-4 text-right">
                      <div v-if="withdrawal.status === 'pending'" class="flex gap-2 justify-end">
                        <button 
                          class="px-3 py-1 bg-green-500 text-white rounded-lg text-sm hover:bg-green-600"
                          @click="handleApprove(withdrawal.id)"
                        >
                          อนุมัติ
                        </button>
                        <button 
                          class="px-3 py-1 bg-red-500 text-white rounded-lg text-sm hover:bg-red-600"
                          @click="openRejectModal(withdrawal.id)"
                        >
                          ปฏิเสธ
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
              
              <div v-if="pendingWithdrawals.length === 0" class="text-center py-8 text-gray-500">
                <Icon icon="mdi:inbox-outline" class="w-12 h-12 mx-auto mb-3" />
                <p>ไม่มีคำขอถอนเงิน</p>
              </div>
            </div>
          </BaseCard>
        </div>

        <!-- Analytics Tab -->
        <div v-if="activeTab === 'analytics'">
          <BaseCard>
            <div class="p-4 text-center text-gray-500 dark:text-gray-400">
              <Icon icon="mdi:chart-bar" class="w-16 h-16 mx-auto mb-4" />
              <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">กราฟวิเคราะห์</h3>
              <p>กำลังพัฒนา - จะแสดงกราฟแนวโน้มธุรกรรมและการวิเคราะห์ข้อมูล Wallet</p>
            </div>
          </BaseCard>
        </div>
      </div>

      <!-- Adjust Wallet Modal -->
      <Teleport to="body">
        <div v-if="showAdjustModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click="showAdjustModal = false">
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6" @click.stop>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">ปรับยอดเงินผู้ใช้</h3>
            
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">User ID</label>
                <input 
                  v-model="adjustForm.user_id"
                  type="text"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                  placeholder="ระบุ User ID"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท</label>
                <div class="flex gap-2">
                  <button 
                    class="flex-1 py-2 rounded-xl font-medium transition-colors"
                    :class="adjustForm.type === 'add' ? 'bg-green-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'"
                    @click="adjustForm.type = 'add'"
                  >
                    เพิ่ม
                  </button>
                  <button 
                    class="flex-1 py-2 rounded-xl font-medium transition-colors"
                    :class="adjustForm.type === 'deduct' ? 'bg-red-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'"
                    @click="adjustForm.type = 'deduct'"
                  >
                    หัก
                  </button>
                  <button 
                    class="flex-1 py-2 rounded-xl font-medium transition-colors"
                    :class="adjustForm.type === 'set' ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'"
                    @click="adjustForm.type = 'set'"
                  >
                    ตั้งค่า
                  </button>
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำนวน (บาท)</label>
                <input 
                  v-model.number="adjustForm.amount"
                  type="number"
                  min="0"
                  step="0.01"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เหตุผล</label>
                <input 
                  v-model="adjustForm.reason"
                  type="text"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                  placeholder="ระบุเหตุผล"
                >
              </div>
            </div>
            
            <div class="flex gap-3 mt-6">
              <button 
                class="flex-1 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl"
                @click="showAdjustModal = false"
              >
                ยกเลิก
              </button>
              <button 
                class="flex-1 py-2 bg-primary-500 text-white rounded-xl"
                @click="handleAdjustWallet"
              >
                บันทึก
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Reject Modal -->
      <Teleport to="body">
        <div v-if="showRejectModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click="showRejectModal = false">
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6" @click.stop>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">ปฏิเสธคำขอถอนเงิน</h3>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เหตุผลในการปฏิเสธ</label>
              <textarea 
                v-model="rejectReason"
                rows="3"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                placeholder="ระบุเหตุผล"
              ></textarea>
            </div>
            
            <div class="flex gap-3 mt-6">
              <button 
                class="flex-1 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl"
                @click="showRejectModal = false"
              >
                ยกเลิก
              </button>
              <button 
                class="flex-1 py-2 bg-red-500 text-white rounded-xl"
                @click="handleReject"
              >
                ปฏิเสธ
              </button>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </div>
</template>
