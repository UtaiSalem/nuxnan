<template>
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
        <Icon icon="mdi:history" class="w-5 h-5 text-blue-500" />
        กิจกรรมล่าสุด
      </h2>
      <NuxtLink 
        v-if="showViewAll" 
        to="/Earn/Points" 
        class="text-primary-500 hover:text-primary-600 text-sm font-medium"
      >
        ดูทั้งหมด →
      </NuxtLink>
    </div>

    <div v-if="transactions.length > 0" class="space-y-3">
      <div 
        v-for="transaction in transactions" 
        :key="transaction.id"
        class="transaction-item flex items-center gap-4 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
      >
        <div 
          class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
          :class="getTransactionColor(transaction.type)"
        >
          <Icon :icon="getTransactionIcon(transaction.type)" class="w-5 h-5" />
        </div>
        <div class="flex-grow min-w-0">
          <p class="font-medium text-gray-900 dark:text-white text-sm truncate">
            {{ transaction.description || getTypeLabel(transaction.type) }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ formatDate(transaction.created_at) }}
            <span v-if="transaction.source_type" class="ml-1">• {{ getSourceLabel(transaction.source_type) }}</span>
          </p>
        </div>
        <div 
          class="text-sm font-bold flex-shrink-0"
          :class="getTransactionColor(transaction.type)"
        >
          {{ ['earn', 'refund'].includes(transaction.type) ? '+' : '-' }}{{ formatPoints(transaction.amount) }}
        </div>
      </div>
    </div>

    <div v-else class="empty-state text-center py-8 text-gray-500 dark:text-gray-400">
      <Icon icon="mdi:history" class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" />
      <p>ยังไม่มีกิจกรรม</p>
      <p class="text-sm mt-1">เริ่มทำกิจกรรมเพื่อสะสมแต้ม!</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Transaction {
  id: number
  type: 'earn' | 'spend' | 'refund' | 'transfer' | 'conversion'
  amount: number
  description?: string
  source_type?: string
  created_at: string
}

interface Props {
  transactions: Transaction[]
  showViewAll?: boolean
}

defineProps<Props>()

const getTransactionIcon = (type: string): string => {
  const icons: Record<string, string> = {
    earn: 'mdi:arrow-up-circle',
    spend: 'mdi:arrow-down-circle',
    refund: 'mdi:refresh-circle',
    transfer: 'mdi:swap-horizontal',
    conversion: 'mdi:currency-exchange',
  }
  return icons[type] || 'mdi:star-circle'
}

const getTransactionColor = (type: string): string => {
  const colors: Record<string, string> = {
    earn: 'text-green-500 bg-green-100 dark:bg-green-900/30',
    spend: 'text-red-500 bg-red-100 dark:bg-red-900/30',
    refund: 'text-blue-500 bg-blue-100 dark:bg-blue-900/30',
    transfer: 'text-orange-500 bg-orange-100 dark:bg-orange-900/30',
    conversion: 'text-purple-500 bg-purple-100 dark:bg-purple-900/30',
  }
  return colors[type] || 'text-gray-500 bg-gray-100 dark:bg-gray-900/30'
}

const getTypeLabel = (type: string): string => {
  const labels: Record<string, string> = {
    earn: 'รับแต้ม',
    spend: 'ใช้แต้ม',
    refund: 'คืนแต้ม',
    transfer: 'โอนแต้ม',
    conversion: 'แลกแต้ม',
  }
  return labels[type] || type
}

const getSourceLabel = (sourceType: string): string => {
  const labels: Record<string, string> = {
    login: 'เข้าสู่ระบบ',
    course: 'คอร์สเรียน',
    lesson: 'บทเรียน',
    quiz: 'แบบทดสอบ',
    assignment: 'การบ้าน',
    post: 'โพสต์',
    comment: 'คอมเมนต์',
    like: 'ถูกใจ',
    share: 'แชร์',
    referral: 'แนะนำเพื่อน',
  }
  return labels[sourceType] || sourceType
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('th-TH', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatPoints = (value: number): string => {
  return new Intl.NumberFormat('th-TH').format(value)
}
</script>

<style scoped>
.transaction-item {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateX(-10px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}
</style>
