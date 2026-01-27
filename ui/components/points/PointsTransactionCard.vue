<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface PointsTransactionProps {
  id: number | string
  type: 'earn' | 'spend' | 'convert_from_wallet' | 'convert_to_wallet' | 'transfer' | 'reward' | 'bonus' | 'refund' | string
  points: number
  balanceAfter?: number
  createdAt: string
  // Source info
  source?: {
    avatar?: string
    name: string
    isSystem?: boolean
  }
  // Destination info
  destination?: {
    avatar?: string
    name: string
    icon?: string
    isSystem?: boolean
  }
  // Additional info
  description?: string
  status?: 'pending' | 'completed' | 'failed' | 'cancelled'
}

const props = defineProps<PointsTransactionProps>()

// Format points
const formatPoints = (points: number): string => {
  return new Intl.NumberFormat('th-TH').format(Math.abs(points))
}

// Format date
const formatDate = (dateStr: string): string => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('th-TH', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Check if positive (receiving points)
const isPositive = computed(() => props.points > 0)

// Get action label based on type
const getActionLabel = computed(() => {
  const labels: Record<string, string> = {
    'earn': 'ได้รับแต้ม',
    'spend': 'ใช้แต้ม',
    'convert_from_wallet': 'แปลงจากเงิน',
    'convert_to_wallet': 'แปลงเป็นเงิน',
    'transfer': isPositive.value ? 'รับโอนแต้ม' : 'โอนแต้ม',
    'reward': 'รางวัล',
    'bonus': 'โบนัส',
    'refund': 'คืนแต้ม'
  }
  return labels[props.type] || props.type
})

// Get default avatar
const getDefaultAvatar = (name: string): string => {
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=random&color=fff&size=128`
}

// Get destination icon
const getDestinationIcon = computed(() => {
  if (props.destination?.icon) return props.destination.icon
  
  const icons: Record<string, string> = {
    'earn': 'mdi:star-plus',
    'spend': 'mdi:star-minus',
    'convert_from_wallet': 'mdi:wallet',
    'convert_to_wallet': 'mdi:wallet',
    'transfer': 'mdi:swap-horizontal',
    'reward': 'mdi:gift',
    'bonus': 'mdi:party-popper',
    'refund': 'mdi:restore'
  }
  return icons[props.type] || 'mdi:star'
})

// Should show three-column layout
const isThreeColumn = computed(() => {
  return ['transfer', 'convert_from_wallet', 'convert_to_wallet'].includes(props.type)
})
</script>

<template>
  <div 
    class="group relative overflow-hidden bg-gray-900/80 dark:bg-gray-900 border border-gray-700/50 rounded-xl sm:rounded-2xl hover:border-gray-600 transition-all duration-300"
  >
    <!-- Background Glow -->
    <div 
      class="absolute -right-10 -top-10 w-32 h-32 blur-3xl pointer-events-none transition-colors"
      :class="isPositive ? 'bg-amber-500/5 group-hover:bg-amber-500/10' : 'bg-purple-500/5 group-hover:bg-purple-500/10'"
    ></div>

    <!-- Content -->
    <div class="relative z-10 p-3 sm:p-4">
      <div class="flex items-center gap-2 sm:gap-4">
        <!-- Three Column Layout (Transfer, Convert) -->
        <template v-if="isThreeColumn && source && destination">
          <div class="flex items-center gap-2 sm:gap-3 flex-grow min-w-0">
            <!-- Source -->
            <div class="flex flex-col items-center text-center flex-shrink-0">
              <img 
                v-if="source.avatar && !source.isSystem"
                :src="source.avatar"
                :alt="source.name"
                class="w-10 h-10 sm:w-12 sm:h-12 rounded-full object-cover ring-2 ring-gray-800 border border-gray-700"
              >
              <div 
                v-else
                class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center bg-gray-800 ring-2 ring-gray-700 border border-gray-600"
                :class="source.isSystem ? 'text-emerald-400' : 'text-gray-400'"
              >
                <Icon :icon="source.isSystem ? 'mdi:wallet' : 'mdi:account'" class="w-5 h-5 sm:w-6 sm:h-6" />
              </div>
              <span class="text-[9px] sm:text-[10px] font-medium text-gray-400 mt-1 truncate max-w-[50px] sm:max-w-[64px]">
                {{ source.name }}
              </span>
            </div>

            <!-- Arrow + Action Label -->
            <div class="flex-grow flex flex-col items-center justify-center px-1 sm:px-2 -mt-3 sm:-mt-4 min-w-0">
              <div class="w-full flex items-center justify-center relative">
                <div class="flex-grow h-px bg-gradient-to-r from-gray-700 via-gray-600 to-gray-700"></div>
                <Icon 
                  icon="mdi:chevron-right"
                  class="w-4 h-4 sm:w-5 sm:h-5 mx-1 flex-shrink-0"
                  :class="isPositive ? 'text-amber-400' : 'text-purple-400'"
                />
                <div class="flex-grow h-px bg-gradient-to-r from-gray-700 via-gray-600 to-gray-700"></div>
              </div>
              <span 
                class="text-[8px] sm:text-[9px] uppercase tracking-wider font-bold mt-1.5 sm:mt-2 text-center truncate max-w-full"
                :class="isPositive ? 'text-amber-400' : 'text-purple-400'"
              >
                {{ getActionLabel }}
              </span>
            </div>

            <!-- Destination -->
            <div class="flex flex-col items-center text-center flex-shrink-0">
              <img 
                v-if="destination.avatar && !destination.isSystem"
                :src="destination.avatar"
                :alt="destination.name"
                class="w-10 h-10 sm:w-12 sm:h-12 rounded-full object-cover ring-2 ring-gray-800 border border-gray-700"
              >
              <div 
                v-else
                class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center bg-amber-500/20 ring-2 ring-amber-500/30 border border-amber-500/40"
              >
                <Icon :icon="getDestinationIcon" class="w-5 h-5 sm:w-6 sm:h-6 text-amber-400" />
              </div>
              <span class="text-[9px] sm:text-[10px] font-medium text-gray-400 mt-1 truncate max-w-[50px] sm:max-w-[64px]">
                {{ destination.name }}
              </span>
            </div>
          </div>
        </template>

        <!-- Activity Style (Earn, Spend, etc.) -->
        <template v-else>
          <div class="flex items-center gap-2 sm:gap-3 flex-grow min-w-0">
            <div 
              class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl flex items-center justify-center flex-shrink-0"
              :class="isPositive 
                ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' 
                : 'bg-purple-500/20 text-purple-400 border border-purple-500/30'"
            >
              <Icon :icon="getDestinationIcon" class="w-5 h-5 sm:w-6 sm:h-6" />
            </div>
            <div class="min-w-0 flex-grow">
              <p class="font-bold text-sm sm:text-base text-white truncate">{{ getActionLabel }}</p>
              <p v-if="description" class="text-[10px] sm:text-xs text-gray-400 truncate">{{ description }}</p>
            </div>
          </div>
        </template>

        <!-- Points & Balance -->
        <div class="flex flex-col items-end flex-shrink-0 min-w-[80px] sm:min-w-[100px]">
          <!-- Points Amount -->
          <div 
            class="flex items-center font-black text-base sm:text-lg"
            :class="isPositive ? 'text-amber-400' : 'text-purple-400'"
          >
            <span class="text-sm sm:text-base mr-0.5">{{ isPositive ? '+' : '-' }}</span>
            {{ formatPoints(points) }}
            <Icon icon="mdi:star" class="w-4 h-4 sm:w-5 sm:h-5 ml-1" />
          </div>
          
          <!-- Balance After -->
          <div v-if="balanceAfter !== undefined" class="flex items-center gap-1 mt-1 text-[9px] sm:text-[10px] text-gray-500">
            <span>คงเหลือ:</span>
            <span class="text-amber-300 font-semibold">{{ formatPoints(balanceAfter) }}</span>
            <Icon icon="mdi:star" class="w-3 h-3 text-amber-300" />
          </div>
          
          <!-- Date -->
          <p class="text-[9px] sm:text-[10px] text-gray-500 mt-0.5 sm:mt-1">{{ formatDate(createdAt) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
