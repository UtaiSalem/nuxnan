<script setup lang="ts">
/**
 * UnifiedTransactionCard - การ์ดธุรกรรมแบบรวม
 * รองรับทั้ง Wallet (เงิน) และ Points (แต้ม)
 * 
 * สไตล์: แสดง 3 คอลัมน์
 * [Avatar ต้นทาง] → [Action Label] → [Avatar/Icon ปลายทาง] | [จำนวน + ยอดคงเหลือ + วันที่]
 */
import { Icon } from '@iconify/vue'

interface UnifiedTransactionProps {
  id: number | string
  // Transaction type
  transactionType: 'wallet' | 'points'
  actionType: string // transfer, convert_to_points, convert_to_wallet, deposit, withdraw, earn, spend, etc.
  
  // Amount (positive = receive, negative = spend/send)
  amount: number
  balanceAfter?: number
  
  // Timestamp
  createdAt: string
  
  // Source user/system
  source: {
    avatar?: string
    name: string
    isSystem?: boolean
    systemIcon?: string
  }
  
  // Destination user/system  
  destination: {
    avatar?: string
    name: string
    isSystem?: boolean
    systemIcon?: string
  }
  
  // Optional
  description?: string
  status?: 'pending' | 'completed' | 'failed' | 'cancelled'
}

const props = defineProps<UnifiedTransactionProps>()

// Format money
const formatMoney = (amount: number): string => {
  return new Intl.NumberFormat('th-TH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(Math.abs(amount))
}

// Format points
const formatPoints = (points: number): string => {
  return new Intl.NumberFormat('th-TH').format(Math.abs(points))
}

// Format date - compact for mobile
const formatDate = (dateStr: string): string => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('th-TH', {
    day: 'numeric',
    month: 'short',
    year: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Check if positive transaction
const isPositive = computed(() => props.amount > 0)

// Get action label
const getActionLabel = computed(() => {
  const walletLabels: Record<string, string> = {
    'transfer_in': 'โอนเข้า',
    'transfer_out': 'โอนออก',
    'transfer': isPositive.value ? 'โอนเข้า' : 'โอนออก',
    'convert_to_points': 'แปลงเป็นแต้ม',
    'convert_to_wallet': 'แปลงเป็นเงิน',
    'deposit': 'เติมเงิน',
    'withdraw': 'ถอนเงิน',
    'purchase': 'ซื้อ',
    'refund': 'คืนเงิน',
    'reward': 'รางวัล'
  }
  
  const pointsLabels: Record<string, string> = {
    'earn': 'ได้รับแต้ม',
    'spend': 'ใช้แต้ม',
    'transfer_in': 'รับโอนแต้ม',
    'transfer_out': 'โอนแต้ม',
    'transfer': isPositive.value ? 'รับโอนแต้ม' : 'โอนแต้ม',
    'convert_from_wallet': 'แปลงจากเงิน',
    'convert_to_wallet': 'แปลงเป็นเงิน',
    'reward': 'รางวัล',
    'bonus': 'โบนัส',
    'refund': 'คืนแต้ม'
  }
  
  const labels = props.transactionType === 'wallet' ? walletLabels : pointsLabels
  return labels[props.actionType] || props.actionType
})

// Get default system icon
const getSystemIcon = (type: string, isSource: boolean): string => {
  if (props.transactionType === 'wallet') {
    const icons: Record<string, string> = {
      'convert_to_points': isSource ? 'mdi:wallet' : 'mdi:star-circle',
      'deposit': 'mdi:bank',
      'withdraw': 'mdi:bank-transfer-out',
      'purchase': 'mdi:cart',
      'refund': 'mdi:cash-refund',
      'reward': 'mdi:gift'
    }
    return icons[props.actionType] || 'mdi:wallet'
  } else {
    const icons: Record<string, string> = {
      'convert_from_wallet': isSource ? 'mdi:wallet' : 'mdi:star-circle',
      'convert_to_wallet': isSource ? 'mdi:star-circle' : 'mdi:wallet',
      'earn': 'mdi:star-plus',
      'spend': 'mdi:star-minus',
      'reward': 'mdi:gift',
      'bonus': 'mdi:party-popper'
    }
    return icons[props.actionType] || 'mdi:star'
  }
}

// Colors based on transaction type and direction
const getColors = computed(() => {
  if (props.transactionType === 'wallet') {
    return {
      positive: {
        text: 'text-green-400',
        bg: 'bg-green-500/10',
        glow: 'bg-green-500/5 group-hover:bg-green-500/10',
        ring: 'ring-green-500/30',
        border: 'border-green-500/40'
      },
      negative: {
        text: 'text-red-400',
        bg: 'bg-red-500/10',
        glow: 'bg-red-500/5 group-hover:bg-red-500/10',
        ring: 'ring-red-500/30',
        border: 'border-red-500/40'
      }
    }
  } else {
    return {
      positive: {
        text: 'text-amber-400',
        bg: 'bg-amber-500/10',
        glow: 'bg-amber-500/5 group-hover:bg-amber-500/10',
        ring: 'ring-amber-500/30',
        border: 'border-amber-500/40'
      },
      negative: {
        text: 'text-purple-400',
        bg: 'bg-purple-500/10',
        glow: 'bg-purple-500/5 group-hover:bg-purple-500/10',
        ring: 'ring-purple-500/30',
        border: 'border-purple-500/40'
      }
    }
  }
})

const currentColors = computed(() => isPositive.value ? getColors.value.positive : getColors.value.negative)
</script>

<template>
  <div 
    class="group relative overflow-hidden bg-gray-900/90 backdrop-blur-sm border border-gray-700/50 rounded-xl sm:rounded-2xl hover:border-gray-600 transition-all duration-300"
  >
    <!-- Background Glow -->
    <div 
      class="absolute -right-10 -top-10 w-32 h-32 blur-3xl pointer-events-none transition-colors"
      :class="currentColors.glow"
    ></div>

    <!-- Content -->
    <div class="relative z-10 p-3 sm:p-4">
      <div class="flex items-center gap-2 sm:gap-3">
        <!-- Three Column Layout -->
        <div class="flex items-center gap-1.5 sm:gap-3 flex-grow min-w-0">
          <!-- Source -->
          <div class="flex flex-col items-center text-center flex-shrink-0 w-12 sm:w-16">
            <img 
              v-if="source.avatar && !source.isSystem"
              :src="source.avatar"
              :alt="source.name"
              class="w-9 h-9 sm:w-11 sm:h-11 rounded-full object-cover ring-2 ring-gray-800 border border-gray-700 shadow-lg"
            >
            <div 
              v-else
              class="w-9 h-9 sm:w-11 sm:h-11 rounded-full flex items-center justify-center ring-2 shadow-lg"
              :class="[
                source.isSystem ? currentColors.bg : 'bg-gray-800',
                source.isSystem ? currentColors.ring : 'ring-gray-700',
                source.isSystem ? currentColors.border : 'border-gray-600'
              ]"
            >
              <Icon 
                :icon="source.systemIcon || getSystemIcon(actionType, true)" 
                class="w-4 h-4 sm:w-5 sm:h-5"
                :class="source.isSystem ? currentColors.text : 'text-gray-400'"
              />
            </div>
            <span class="text-[8px] sm:text-[10px] font-medium text-gray-400 mt-1 truncate w-full px-0.5">
              {{ source.name }}
            </span>
          </div>

          <!-- Arrow + Action Label -->
          <div class="flex-grow flex flex-col items-center justify-center -mt-2 sm:-mt-3 min-w-0 px-0.5">
            <div class="w-full flex items-center justify-center">
              <div class="flex-grow h-px bg-gradient-to-r from-transparent via-gray-600 to-transparent"></div>
              <Icon 
                icon="mdi:chevron-right"
                class="w-4 h-4 sm:w-5 sm:h-5 mx-0.5 flex-shrink-0"
                :class="currentColors.text"
              />
              <div class="flex-grow h-px bg-gradient-to-r from-transparent via-gray-600 to-transparent"></div>
            </div>
            <span 
              class="text-[7px] sm:text-[9px] uppercase tracking-wide font-bold mt-1 sm:mt-1.5 text-center leading-tight"
              :class="currentColors.text"
            >
              {{ getActionLabel }}
            </span>
          </div>

          <!-- Destination -->
          <div class="flex flex-col items-center text-center flex-shrink-0 w-12 sm:w-16">
            <img 
              v-if="destination.avatar && !destination.isSystem"
              :src="destination.avatar"
              :alt="destination.name"
              class="w-9 h-9 sm:w-11 sm:h-11 rounded-full object-cover ring-2 ring-gray-800 border border-gray-700 shadow-lg"
            >
            <div 
              v-else
              class="w-9 h-9 sm:w-11 sm:h-11 rounded-full flex items-center justify-center ring-2 shadow-lg"
              :class="[currentColors.bg, currentColors.ring, currentColors.border]"
            >
              <Icon 
                :icon="destination.systemIcon || getSystemIcon(actionType, false)" 
                class="w-4 h-4 sm:w-5 sm:h-5"
                :class="currentColors.text"
              />
            </div>
            <span class="text-[8px] sm:text-[10px] font-medium text-gray-400 mt-1 truncate w-full px-0.5">
              {{ destination.name }}
            </span>
          </div>
        </div>

        <!-- Amount, Balance & Date -->
        <div class="flex flex-col items-end flex-shrink-0 min-w-[75px] sm:min-w-[110px]">
          <!-- Amount -->
          <div 
            class="flex items-center font-black text-sm sm:text-lg leading-none"
            :class="currentColors.text"
          >
            <span class="text-xs sm:text-base">{{ isPositive ? '+' : '-' }}</span>
            <template v-if="transactionType === 'wallet'">
              <span class="text-[10px] sm:text-sm">฿</span>
              <span>{{ formatMoney(amount) }}</span>
            </template>
            <template v-else>
              <span>{{ formatPoints(amount) }}</span>
              <Icon icon="mdi:star" class="w-3.5 h-3.5 sm:w-4 sm:h-4 ml-0.5" />
            </template>
          </div>
          
          <!-- Balance After -->
          <div v-if="balanceAfter !== undefined" class="flex items-center gap-0.5 mt-0.5 sm:mt-1 text-[8px] sm:text-[10px] text-gray-500 bg-gray-800/50 px-1.5 py-0.5 rounded">
            <span>คงเหลือ:</span>
            <template v-if="transactionType === 'wallet'">
              <span class="text-gray-300 font-semibold">฿{{ formatMoney(balanceAfter) }}</span>
            </template>
            <template v-else>
              <span class="text-amber-300 font-semibold">{{ formatPoints(balanceAfter) }}</span>
              <Icon icon="mdi:star" class="w-2.5 h-2.5 text-amber-300" />
            </template>
          </div>
          
          <!-- Date -->
          <p class="text-[8px] sm:text-[10px] text-gray-500 mt-0.5">{{ formatDate(createdAt) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
