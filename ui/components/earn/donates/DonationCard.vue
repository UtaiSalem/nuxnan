<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface DonorProfile {
  first_name: string
  last_name: string
  bio: string | null
}

interface Donor {
  id: number
  username: string
  email: string
  avatar: string
  points: number
  personal_code: string
  reference_code: string
  profile?: DonorProfile
}

interface Donate {
  id: number
  donor: Donor | null
  donor_name: string
  total_points: number
  remaining_points: number
  amounts?: number
  status?: number
}

defineProps<{
  donate: Donate
  isProcessing?: boolean
}>()

defineEmits<{
  getDonateRequest: []
}>()
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 border border-gray-100 dark:border-gray-700">
    <!-- Header gradient -->
    <div class="h-2 bg-gradient-to-r from-yellow-400 via-amber-500 to-orange-500"></div>
    
    <div class="p-5">
      <!-- Donor Info -->
      <div class="flex items-center gap-4 mb-4">
        <div class="relative">
          <img 
            :src="donate.donor?.avatar || '/storage/images/default_avatar.png'" 
            :alt="donate.donor_name || 'ผู้สนับสนุน'"
            class="w-14 h-14 rounded-full border-3 border-white shadow-lg object-cover" 
          />
          <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 rounded-full border-2 border-white flex items-center justify-center">
            <Icon icon="mdi:check" class="w-3 h-3 text-white" />
          </div>
        </div>
        <div class="flex-1 min-w-0">
          <h4 class="font-bold text-gray-900 dark:text-white truncate">
            {{ donate.donor_name || donate.donor?.username || 'ไม่ระบุนาม' }}
          </h4>
          <p v-if="donate.donor?.personal_code" class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
            <Icon icon="mdi:identifier" class="w-3 h-3" />
            {{ donate.donor.personal_code }}
          </p>
        </div>
      </div>

      <!-- Points Info -->
      <div class="bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-xl p-4 mb-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Icon icon="mdi:star-circle" class="w-6 h-6 text-yellow-500" />
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400">แต้มทั้งหมด</p>
              <p class="font-bold text-lg text-gray-900 dark:text-white">
                {{ (donate.total_points || 0).toLocaleString() }}
              </p>
            </div>
          </div>
          <div class="text-right">
            <p class="text-xs text-gray-500 dark:text-gray-400">คงเหลือ</p>
            <p class="font-bold text-lg text-green-600 dark:text-green-400">
              {{ (typeof donate.remaining_points === 'string' ? donate.remaining_points : donate.remaining_points?.toLocaleString() || 0) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Action Button -->
      <button
        @click="$emit('getDonateRequest')"
        :disabled="isProcessing || donate.remaining_points < 270"
        class="w-full py-3 bg-gradient-to-r from-yellow-500 to-orange-500 text-white font-semibold rounded-xl hover:opacity-90 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
      >
        <template v-if="isProcessing">
          <Icon icon="mdi:loading" class="w-5 h-5 animate-spin" />
          <span>กำลังดำเนินการ...</span>
        </template>
        <template v-else-if="donate.remaining_points < 270">
          <Icon icon="mdi:close-circle" class="w-5 h-5" />
          <span>แต้มหมดแล้ว</span>
        </template>
        <template v-else>
          <Icon icon="mdi:hand-coin" class="w-5 h-5" />
          <span>รับ 240 แต้ม</span>
        </template>
      </button>
    </div>
  </div>
</template>
