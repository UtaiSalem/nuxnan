<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import { useRewards } from '~/composables/useRewards'
import BaseCard from '~/components/atoms/BaseCard.vue'
import RewardsDisplay from '~/components/rewards/RewardsDisplay.vue'

definePageMeta({
  layout: 'main',
  middleware: ['auth']
})

useHead({
  title: 'แลกรางวัล - Nuxni'
})

const authStore = useAuthStore()
const { 
  rewards, 
  userRewards, 
  points, 
  isLoading, 
  getRewards, 
  getMyRewards, 
  getStats,
  formatPoints 
} = useRewards()

// Stats
const stats = ref({
  total_rewards: 0,
  total_redeemed: 0,
  total_points_spent: 0,
  rewards_by_type: {} as Record<string, number>
})

// Active view
const activeView = ref('rewards') // 'rewards' | 'my-rewards'

// Load data
const loadData = async () => {
  try {
    await Promise.all([
      getRewards(),
      getMyRewards(),
      loadStats()
    ])
  } catch (error) {
    console.error('Failed to load data:', error)
  }
}

const loadStats = async () => {
  try {
    const data = await getStats()
    if (data) {
      stats.value = data
    }
  } catch (error) {
    console.error('Failed to load stats:', error)
  }
}

onMounted(loadData)
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
      <!-- Page Header -->
      <div class="text-center mb-8">
        <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-purple-400 to-pink-600 rounded-full flex items-center justify-center shadow-lg">
          <Icon icon="mdi:gift" class="w-10 h-10 text-white" />
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
          แลกรางวัล
        </h1>
        <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
          ใช้แต้มสะสมของคุณแลกรางวัลมากมาย ทั้งเงินเข้ากระเป๋า เหรียญตรา และฟีเจอร์พิเศษ
        </p>
      </div>

      <!-- Not Authenticated Message -->
      <div v-if="!authStore.isAuthenticated" class="text-center py-12">
        <BaseCard>
          <Icon icon="mdi:account-lock" class="w-16 h-16 mx-auto text-gray-400 mb-4" />
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">กรุณาเข้าสู่ระบบ</h2>
          <p class="text-gray-500 dark:text-gray-400 mb-4">เข้าสู่ระบบเพื่อดูรางวัลและแลกรางวัล</p>
          <NuxtLink 
            to="/auth"
            class="inline-flex items-center gap-2 px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg hover:opacity-90 transition-opacity"
          >
            <Icon icon="mdi:login" class="w-5 h-5" />
            เข้าสู่ระบบ
          </NuxtLink>
        </BaseCard>
      </div>

      <!-- Authenticated Content -->
      <div v-else>
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <!-- Current Points -->
          <BaseCard class="bg-gradient-to-br from-amber-400 to-orange-500 border-0">
            <div class="text-white">
              <div class="flex items-center gap-2 mb-2">
                <Icon icon="mdi:star-circle" class="w-6 h-6" />
                <span class="text-sm font-medium opacity-80">แต้มปัจจุบัน</span>
              </div>
              <p class="text-2xl lg:text-3xl font-bold">{{ formatPoints(points) }}</p>
            </div>
          </BaseCard>
          
          <!-- Available Rewards -->
          <BaseCard>
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                <Icon icon="mdi:gift-outline" class="w-6 h-6 text-purple-500" />
              </div>
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">รางวัลทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_rewards }}</p>
              </div>
            </div>
          </BaseCard>
          
          <!-- My Rewards -->
          <BaseCard>
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                <Icon icon="mdi:check-circle" class="w-6 h-6 text-green-500" />
              </div>
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">แลกไปแล้ว</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ userRewards.length }}</p>
              </div>
            </div>
          </BaseCard>
          
          <!-- Points Spent -->
          <BaseCard>
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                <Icon icon="mdi:trending-down" class="w-6 h-6 text-blue-500" />
              </div>
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">แต้มที่ใช้ไป</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatPoints(stats.total_points_spent) }}</p>
              </div>
            </div>
          </BaseCard>
        </div>
        
        <!-- View Tabs -->
        <div class="flex gap-4 mb-6">
          <button
            class="px-6 py-2 rounded-xl font-medium transition-all"
            :class="activeView === 'rewards' 
              ? 'bg-primary-500 text-white shadow-md' 
              : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'"
            @click="activeView = 'rewards'"
          >
            <Icon icon="mdi:gift" class="w-5 h-5 inline mr-2" />
            แลกรางวัล
          </button>
          <button
            class="px-6 py-2 rounded-xl font-medium transition-all relative"
            :class="activeView === 'my-rewards' 
              ? 'bg-primary-500 text-white shadow-md' 
              : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'"
            @click="activeView = 'my-rewards'"
          >
            <Icon icon="mdi:account-check" class="w-5 h-5 inline mr-2" />
            รางวัลของฉัน
            <span 
              v-if="userRewards.length > 0"
              class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
            >
              {{ userRewards.length }}
            </span>
          </button>
        </div>
        
        <!-- Content -->
        <div v-if="activeView === 'rewards'">
          <RewardsDisplay :show-my-rewards="false" />
        </div>
        
        <div v-else>
          <!-- My Rewards List -->
          <BaseCard>
            <div class="p-2">
              <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">รางวัลของฉัน</h3>
              
              <div v-if="isLoading" class="py-12 text-center">
                <Icon icon="mdi:loading" class="w-12 h-12 text-primary-500 animate-spin mx-auto" />
                <p class="mt-4 text-gray-500 dark:text-gray-400">กำลังโหลด...</p>
              </div>
              
              <div v-else-if="userRewards.length > 0" class="space-y-4">
                <div 
                  v-for="userReward in userRewards" 
                  :key="userReward.id"
                  class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl"
                >
                  <!-- Reward Icon -->
                  <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-pink-500 rounded-xl flex items-center justify-center flex-shrink-0">
                    <Icon icon="mdi:gift" class="w-7 h-7 text-white" />
                  </div>
                  
                  <!-- Reward Info -->
                  <div class="flex-grow">
                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ userReward.reward.name }}</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ userReward.reward.description }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                      แลกเมื่อ {{ new Date(userReward.redeemed_at).toLocaleDateString('th-TH') }}
                    </p>
                  </div>
                  
                  <!-- Points Spent -->
                  <div class="text-right">
                    <p class="text-lg font-bold text-amber-500">-{{ formatPoints(userReward.points_spent) }}</p>
                    <p class="text-xs text-gray-500">แต้ม</p>
                  </div>
                  
                  <!-- Status -->
                  <div>
                    <span 
                      class="px-3 py-1 rounded-full text-xs font-medium"
                      :class="{
                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300': userReward.status === 'pending',
                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': userReward.status === 'claimed',
                        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300': userReward.status === 'used',
                        'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300': userReward.status === 'expired',
                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300': userReward.status === 'cancelled',
                      }"
                    >
                      {{ 
                        userReward.status === 'pending' ? 'รอรับ' :
                        userReward.status === 'claimed' ? 'รับแล้ว' :
                        userReward.status === 'used' ? 'ใช้แล้ว' :
                        userReward.status === 'expired' ? 'หมดอายุ' :
                        userReward.status === 'cancelled' ? 'ยกเลิก' : userReward.status
                      }}
                    </span>
                  </div>
                </div>
              </div>
              
              <div v-else class="py-12 text-center">
                <Icon icon="mdi:gift-off" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600" />
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">ยังไม่มีรางวัล</h3>
                <p class="mt-2 text-gray-500 dark:text-gray-400">คุณยังไม่ได้แลกรางวัลใดๆ</p>
                <button
                  class="mt-4 px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg hover:shadow-md transition-all"
                  @click="activeView = 'rewards'"
                >
                  <Icon icon="mdi:gift" class="w-5 h-5 inline mr-2" />
                  ดูรางวัลที่แลกได้
                </button>
              </div>
            </div>
          </BaseCard>
        </div>
        
        <!-- How It Works Section -->
        <BaseCard class="mt-8">
          <div class="p-2">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 text-center">วิธีแลกรางวัล</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
                  <Icon icon="mdi:star-circle" class="w-8 h-8 text-amber-500" />
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">1. สะสมแต้ม</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  รับแต้มจากการโต้ตอบต่างๆ เช่น รับสนับสนุน ถูกกดไลค์ และอื่นๆ
                </p>
              </div>
              
              <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                  <Icon icon="mdi:gift-open" class="w-8 h-8 text-purple-500" />
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">2. เลือกรางวัล</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  เลือกรางวัลที่ต้องการจากตัวเลือกมากมาย ทั้งเงิน เหรียญ และฟีเจอร์พิเศษ
                </p>
              </div>
              
              <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                  <Icon icon="mdi:check-decagram" class="w-8 h-8 text-green-500" />
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">3. รับรางวัล</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  รับรางวัลทันที! เงินจะเข้ากระเป๋า หรือรับเหรียญตราและฟีเจอร์พิเศษ
                </p>
              </div>
            </div>
          </div>
        </BaseCard>
      </div>
    </div>
  </div>
</template>
