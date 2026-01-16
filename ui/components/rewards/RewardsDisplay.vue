<template>
  <div class="rewards-display">
    <!-- Header Stats -->
    <div class="rewards-header mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">แลกรางวัล</h2>
          <p class="text-gray-500 dark:text-gray-400">ใช้แต้มแลกรางวัลที่คุณต้องการ</p>
        </div>
        <div class="points-badge bg-gradient-to-r from-amber-400 to-orange-500 text-white px-4 py-2 rounded-xl shadow-lg">
          <span class="text-sm">แต้มของคุณ</span>
          <p class="text-2xl font-bold">{{ formatPoints(points) }}</p>
        </div>
      </div>
    </div>
    
    <!-- Filter Tabs -->
    <div class="filter-tabs mb-6">
      <div class="flex gap-2 overflow-x-auto pb-2">
        <button 
          v-for="tab in tabs" 
          :key="tab.value"
          class="tab-btn px-4 py-2 rounded-full text-sm font-medium transition-all whitespace-nowrap"
          :class="activeTab === tab.value 
            ? 'bg-primary-500 text-white shadow-md' 
            : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
          @click="activeTab = tab.value"
        >
          <Icon :icon="tab.icon" class="w-4 h-4 inline mr-1" />
          {{ tab.label }}
        </button>
      </div>
    </div>
    
    <!-- Loading State -->
    <div v-if="isLoading" class="loading-state py-12">
      <div class="flex flex-col items-center">
        <Icon icon="mdi:loading" class="w-12 h-12 text-primary-500 animate-spin" />
        <p class="mt-4 text-gray-500 dark:text-gray-400">กำลังโหลดรางวัล...</p>
      </div>
    </div>
    
    <!-- Rewards Grid -->
    <div v-else-if="filteredRewards.length > 0" class="rewards-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div 
        v-for="reward in filteredRewards" 
        :key="reward.id"
        class="reward-card bg-white dark:bg-gray-800 rounded-2xl shadow-md overflow-hidden transition-all hover:shadow-lg hover:-translate-y-1"
        :class="{ 'opacity-60': !canRedeem(reward) }"
      >
        <!-- Reward Image/Icon -->
        <div class="reward-image h-32 bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center relative">
          <img 
            v-if="reward.image" 
            :src="reward.image" 
            :alt="reward.name"
            class="w-full h-full object-cover"
          >
          <Icon v-else :icon="getTypeIcon(reward.type)" class="w-16 h-16 text-white" />
          
          <!-- Limited Badge -->
          <div 
            v-if="reward.is_limited" 
            class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full"
          >
            จำกัดจำนวน
          </div>
          
          <!-- Type Badge -->
          <div class="absolute top-2 left-2 bg-black/50 text-white text-xs px-2 py-1 rounded-full">
            {{ getTypeLabel(reward.type) }}
          </div>
        </div>
        
        <!-- Reward Info -->
        <div class="reward-info p-4">
          <h3 class="reward-name text-lg font-semibold text-gray-900 dark:text-white mb-1">
            {{ reward.name }}
          </h3>
          <p class="reward-description text-sm text-gray-500 dark:text-gray-400 mb-3 line-clamp-2">
            {{ reward.description }}
          </p>
          
          <!-- Points Required -->
          <div class="flex items-center justify-between mb-3">
            <div class="points-required flex items-center gap-1">
              <Icon icon="mdi:star-circle" class="w-5 h-5 text-amber-500" />
              <span class="text-lg font-bold text-gray-900 dark:text-white">
                {{ formatPoints(reward.points_required) }}
              </span>
              <span class="text-sm text-gray-500">แต้ม</span>
            </div>
            
            <!-- Availability -->
            <div v-if="reward.is_limited" class="text-sm text-gray-500 dark:text-gray-400">
              เหลือ {{ reward.quantity_available - reward.quantity_redeemed }} ชิ้น
            </div>
          </div>
          
          <!-- Value Badge -->
          <div v-if="reward.type === 'wallet'" class="value-badge bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300 text-sm px-3 py-1 rounded-full inline-block mb-3">
            รับ ฿{{ reward.value }} เข้ากระเป๋า
          </div>
          <div v-else-if="reward.type === 'discount'" class="value-badge bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-300 text-sm px-3 py-1 rounded-full inline-block mb-3">
            ส่วนลด {{ reward.value }}%
          </div>
          
          <!-- Redeem Button -->
          <button 
            class="redeem-btn w-full py-2 rounded-xl font-medium transition-all"
            :class="canRedeem(reward)
              ? 'bg-gradient-to-r from-primary-500 to-primary-600 text-white hover:shadow-md'
              : 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'"
            :disabled="!canRedeem(reward) || isRedeeming"
            @click="handleRedeem(reward)"
          >
            <span v-if="isRedeeming && redeemingId === reward.id">
              <Icon icon="mdi:loading" class="w-5 h-5 animate-spin inline" />
              กำลังแลก...
            </span>
            <span v-else-if="points < reward.points_required">
              แต้มไม่พอ (ขาด {{ formatPoints(reward.points_required - points) }})
            </span>
            <span v-else-if="!reward.is_active">
              ไม่พร้อมให้บริการ
            </span>
            <span v-else-if="reward.is_limited && reward.quantity_redeemed >= reward.quantity_available">
              หมดแล้ว
            </span>
            <span v-else>
              <Icon icon="mdi:gift" class="w-5 h-5 inline mr-1" />
              แลกรางวัล
            </span>
          </button>
        </div>
      </div>
    </div>
    
    <!-- Empty State -->
    <div v-else class="empty-state py-12 text-center">
      <Icon icon="mdi:gift-off" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600" />
      <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">ไม่พบรางวัล</h3>
      <p class="mt-2 text-gray-500 dark:text-gray-400">ยังไม่มีรางวัลในหมวดหมู่นี้</p>
    </div>
    
    <!-- My Rewards Section -->
    <div v-if="showMyRewards" class="my-rewards-section mt-8">
      <div class="section-header mb-4">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">รางวัลของฉัน</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">รางวัลที่คุณแลกไว้</p>
      </div>
      
      <div v-if="userRewards.length > 0" class="user-rewards-list space-y-3">
        <div 
          v-for="userReward in userRewards" 
          :key="userReward.id"
          class="user-reward-card bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm flex items-center gap-4"
        >
          <!-- Reward Icon -->
          <div class="reward-icon w-12 h-12 bg-gradient-to-br from-primary-400 to-primary-600 rounded-xl flex items-center justify-center flex-shrink-0">
            <Icon :icon="getTypeIcon(userReward.reward.type)" class="w-6 h-6 text-white" />
          </div>
          
          <!-- Reward Info -->
          <div class="reward-info flex-grow">
            <h4 class="font-semibold text-gray-900 dark:text-white">{{ userReward.reward.name }}</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              แลกเมื่อ {{ formatDate(userReward.redeemed_at) }}
            </p>
          </div>
          
          <!-- Status Badge -->
          <div class="status-badge">
            <span 
              class="px-3 py-1 rounded-full text-xs font-medium"
              :class="getStatusColor(userReward.status)"
            >
              {{ getStatusLabel(userReward.status) }}
            </span>
          </div>
          
          <!-- Actions -->
          <div class="actions">
            <button 
              v-if="userReward.status === 'pending'"
              class="text-primary-500 hover:text-primary-600 font-medium text-sm"
              @click="handleClaim(userReward.id)"
            >
              รับรางวัล
            </button>
          </div>
        </div>
      </div>
      
      <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
        <Icon icon="mdi:inbox-outline" class="w-12 h-12 mx-auto mb-3" />
        <p>ยังไม่มีรางวัลที่แลกไว้</p>
      </div>
    </div>
    
    <!-- Redeem Confirmation Modal -->
    <Teleport to="body">
      <div v-if="showConfirmModal" class="modal-overlay fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click="showConfirmModal = false">
        <div class="modal-content bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6" @click.stop>
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full flex items-center justify-center">
              <Icon icon="mdi:gift" class="w-8 h-8 text-white" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยืนยันการแลกรางวัล</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">
              คุณต้องการแลก <strong class="text-gray-900 dark:text-white">{{ selectedReward?.name }}</strong> หรือไม่?
            </p>
            
            <div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-4 mb-6">
              <div class="flex justify-between items-center">
                <span class="text-gray-600 dark:text-gray-400">ใช้แต้ม</span>
                <span class="text-lg font-bold text-amber-500">{{ formatPoints(selectedReward?.points_required || 0) }}</span>
              </div>
              <div class="flex justify-between items-center mt-2">
                <span class="text-gray-600 dark:text-gray-400">แต้มคงเหลือ</span>
                <span class="text-lg font-bold text-gray-900 dark:text-white">
                  {{ formatPoints(points - (selectedReward?.points_required || 0)) }}
                </span>
              </div>
            </div>
            
            <div class="flex gap-3">
              <button 
                class="flex-1 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                @click="showConfirmModal = false"
              >
                ยกเลิก
              </button>
              <button 
                class="flex-1 py-2 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-xl font-medium hover:shadow-md transition-all"
                :disabled="isRedeeming"
                @click="confirmRedeem"
              >
                <span v-if="isRedeeming">
                  <Icon icon="mdi:loading" class="w-5 h-5 animate-spin inline" />
                </span>
                <span v-else>ยืนยัน</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
    
    <!-- Success Modal -->
    <Teleport to="body">
      <div v-if="showSuccessModal" class="modal-overlay fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click="showSuccessModal = false">
        <div class="modal-content bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6" @click.stop>
          <div class="text-center">
            <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center animate-bounce">
              <Icon icon="mdi:check" class="w-10 h-10 text-white" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">แลกรางวัลสำเร็จ!</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">
              คุณได้รับ <strong class="text-gray-900 dark:text-white">{{ selectedReward?.name }}</strong> เรียบร้อยแล้ว
            </p>
            
            <button 
              class="w-full py-3 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-xl font-medium hover:shadow-md transition-all"
              @click="showSuccessModal = false"
            >
              เยี่ยมเลย!
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useRewards } from '~/composables/useRewards'
import type { Reward } from '~/composables/useRewards'

const props = defineProps({
  showMyRewards: {
    type: Boolean,
    default: true
  }
})

const { 
  rewards, 
  userRewards, 
  points, 
  isLoading, 
  getRewards, 
  getMyRewards, 
  redeemReward, 
  claimReward,
  canRedeem,
  getTypeLabel,
  getTypeIcon,
  getStatusLabel,
  getStatusColor,
  formatPoints 
} = useRewards()

// Tab state
const activeTab = ref('all')
const tabs = [
  { value: 'all', label: 'ทั้งหมด', icon: 'mdi:view-grid' },
  { value: 'wallet', label: 'เงิน', icon: 'mdi:wallet' },
  { value: 'badge', label: 'เหรียญตรา', icon: 'mdi:medal' },
  { value: 'feature', label: 'ฟีเจอร์', icon: 'mdi:star-shooting' },
  { value: 'discount', label: 'ส่วนลด', icon: 'mdi:percent' },
]

// Modal state
const showConfirmModal = ref(false)
const showSuccessModal = ref(false)
const selectedReward = ref<Reward | null>(null)
const isRedeeming = ref(false)
const redeemingId = ref<number | null>(null)

// Computed
const filteredRewards = computed(() => {
  if (activeTab.value === 'all') {
    return rewards.value
  }
  return rewards.value.filter(r => r.type === activeTab.value)
})

// Methods
const handleRedeem = (reward: Reward) => {
  selectedReward.value = reward
  showConfirmModal.value = true
}

const confirmRedeem = async () => {
  if (!selectedReward.value) return
  
  try {
    isRedeeming.value = true
    redeemingId.value = selectedReward.value.id
    
    await redeemReward(selectedReward.value.id)
    
    showConfirmModal.value = false
    showSuccessModal.value = true
    
    // Refresh rewards list
    await getRewards()
  } catch (error) {
    console.error('Redeem error:', error)
    // Show error notification
  } finally {
    isRedeeming.value = false
    redeemingId.value = null
  }
}

const handleClaim = async (userRewardId: number) => {
  try {
    await claimReward(userRewardId)
  } catch (error) {
    console.error('Claim error:', error)
  }
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    getRewards(),
    props.showMyRewards ? getMyRewards() : Promise.resolve()
  ])
})
</script>

<style scoped>
.rewards-display {
  @apply w-full;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.modal-content {
  animation: modal-in 0.3s ease-out;
}

@keyframes modal-in {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>
