<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Transaction {
  id: number
  transaction_type: string
  amount: number
  description?: string
  created_at: string
}

interface XpLog {
  id: number
  rule_key: string
  event_type?: string | null
  xp_awarded: number
  points_awarded?: number
  reason?: string | null
  evaluated_at: string
}

interface Props {
  transactions: Transaction[]
  xpLogs?: XpLog[]
  showViewAll?: boolean
  isLoading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  xpLogs: () => [],
  showViewAll: true,
  isLoading: false
})

const getTransactionIcon = (type: string): string => {
  const icons: Record<string, string> = {
    earn: 'mdi:arrow-up-circle',
    spend: 'mdi:arrow-down-circle',
    refund: 'mdi:refresh-circle',
    transfer_in: 'mdi:arrow-down-circle',
    transfer_out: 'mdi:arrow-up-circle',
    transfer: 'mdi:swap-horizontal',
    admin_adjust: 'mdi:account-edit',
    conversion: 'mdi:currency-exchange',
  }
  return icons[type] || 'mdi:star-circle'
}

const getTransactionColor = (type: string): string => {
  const colors: Record<string, string> = {
    earn: 'text-green-500',
    spend: 'text-red-500',
    refund: 'text-blue-500',
    transfer_in: 'text-green-500',
    transfer_out: 'text-orange-500',
    transfer: 'text-orange-500',
    admin_adjust: 'text-yellow-500',
    conversion: 'text-purple-500',
  }
  return colors[type] || 'text-gray-500'
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('th-TH', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getTypeLabel = (type: string): string => {
  const labels: Record<string, string> = {
    earn: 'รับแต้ม',
    spend: 'ใช้แต้ม',
    refund: 'คืนแต้ม',
    transfer_in: 'รับโอนแต้ม',
    transfer_out: 'โอนแต้มออก',
    transfer: 'โอนแต้ม',
    admin_adjust: 'ปรับโดย Admin',
    conversion: 'แลกแต้ม',
  }
  return labels[type] || type
}

const formatPoints = (value: number): string => {
  return new Intl.NumberFormat('th-TH').format(value)
}

const formatXpLabel = (log: XpLog): string => {
  return log.reason || log.event_type || log.rule_key
}
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-2xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 p-4">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xs md:text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
        <Icon icon="mdi:history" class="w-4 h-4 text-blue-500" />
        กิจกรรมล่าสุด
      </h2>
      <NuxtLink v-if="showViewAll" to="/Earn/Points" class="text-vikinger-purple hover:underline text-[9px] md:text-[10px] font-bold transition-colors">
        ดูทั้งหมด
      </NuxtLink>
    </div>

    <div v-if="isLoading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="flex items-center gap-3 animate-pulse px-2">
        <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-vikinger-dark-50"></div>
        <div class="flex-1 space-y-2">
          <div class="h-2.5 w-1/2 bg-gray-200 dark:bg-vikinger-dark-50 rounded"></div>
          <div class="h-2 w-1/4 bg-gray-200 dark:bg-vikinger-dark-50 rounded"></div>
        </div>
        <div class="h-3 w-10 bg-gray-200 dark:bg-vikinger-dark-50 rounded"></div>
      </div>
    </div>

    <div v-else-if="xpLogs.length > 0 || transactions.length > 0" class="space-y-1">
      <div
        v-for="log in xpLogs"
        :key="`xp-${log.id}`"
        class="flex items-center gap-3 p-2 md:p-3 rounded-xl hover:bg-cyan-50 dark:hover:bg-cyan-900/10 transition-colors group border border-transparent"
      >
        <div
          class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 bg-cyan-50 dark:bg-cyan-900/20 text-cyan-500 transition-all group-hover:scale-110"
        >
          <Icon icon="mdi:lightning-bolt-circle" class="w-4 h-4 md:w-5 md:h-5" />
        </div>
        <div class="min-w-0 flex-1 px-1">
          <p class="font-bold text-gray-900 dark:text-white text-xs md:text-sm truncate">
            {{ formatXpLabel(log) }}
          </p>
          <p class="text-[9px] md:text-[10px] text-gray-500 dark:text-gray-400 font-medium mt-0.5">
            {{ formatDate(log.evaluated_at) }}
          </p>
        </div>
        <div class="text-xs md:text-sm font-black shrink-0 text-cyan-500">
          +{{ formatPoints(log.xp_awarded) }} XP
        </div>
      </div>

      <div 
        v-for="transaction in transactions" 
        :key="transaction.id"
        class="flex items-center gap-3 p-2 md:p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors group border border-transparent"
      >
        <div
          class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 bg-gray-50 dark:bg-vikinger-dark-50 transition-all group-hover:scale-110"
          :class="getTransactionColor(transaction.transaction_type)"
        >
          <Icon :icon="getTransactionIcon(transaction.transaction_type)" class="w-4 h-4 md:w-5 md:h-5" />
        </div>
        <div class="min-w-0 flex-1 px-1">
          <p class="font-bold text-gray-900 dark:text-white text-xs md:text-sm truncate">
            {{ transaction.description || getTypeLabel(transaction.transaction_type) }}
          </p>
          <p class="text-[9px] md:text-[10px] text-gray-500 dark:text-gray-400 font-medium mt-0.5">
            {{ formatDate(transaction.created_at) }}
          </p>
        </div>
        <div
          class="text-xs md:text-sm font-black shrink-0"
          :class="getTransactionColor(transaction.transaction_type)"
        >
          {{ ['earn', 'refund', 'transfer_in'].includes(transaction.transaction_type) ? '+' : '-' }}{{ formatPoints(transaction.amount) }}
        </div>
      </div>
    </div>

    <div v-else class="text-center py-10">
      <Icon icon="fluent:history-24-regular" class="w-10 h-10 text-gray-200 dark:text-gray-700 mx-auto mb-3" />
      <p class="text-gray-500 dark:text-gray-400 text-xs">ยังไม่มีประวัติกิจกรรม</p>
    </div>
  </div>
</template>

