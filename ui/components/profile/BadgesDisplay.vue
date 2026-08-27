<script setup lang="ts">
import { Icon } from '@iconify/vue'
import BaseCard from '~/components/atoms/BaseCard.vue'

const props = defineProps<{
  userId?: string | number
  isOwnProfile?: boolean
}>()

const api = useApi()

// Types
interface Badge {
  id: number
  name: string
  description: string
  icon: string
  icon_color: string
  background_color: string
  category: 'achievement' | 'social' | 'activity' | 'special' | 'milestone'
  rarity: 'common' | 'uncommon' | 'rare' | 'epic' | 'legendary'
  points: number
  earned_at?: string
  is_earned: boolean
  progress?: number
  max_progress?: number
}

interface BadgeCategory {
  key: string
  label: string
  icon: string
  count: number
}

// State
const badges = ref<Badge[]>([])
const isLoading = ref(false)
const selectedCategory = ref<string>('all')
const selectedBadge = ref<Badge | null>(null)
const showBadgeModal = ref(false)

// Computed
const categories = computed<BadgeCategory[]>(() => {
  const counts: Record<string, number> = {
    all: badges.value.filter(b => b.is_earned).length,
    achievement: 0,
    social: 0,
    activity: 0,
    special: 0,
    milestone: 0
  }
  
  badges.value.forEach(badge => {
    if (badge.is_earned) {
      counts[badge.category]++
    }
  })
  
  return [
    { key: 'all', label: 'ทั้งหมด', icon: 'fluent:grid-24-regular', count: counts.all },
    { key: 'achievement', label: 'ความสำเร็จ', icon: 'fluent:trophy-24-regular', count: counts.achievement },
    { key: 'social', label: 'สังคม', icon: 'fluent:people-24-regular', count: counts.social },
    { key: 'activity', label: 'กิจกรรม', icon: 'fluent:calendar-24-regular', count: counts.activity },
    { key: 'milestone', label: 'หลักไมล์', icon: 'fluent:flag-24-regular', count: counts.milestone },
    { key: 'special', label: 'พิเศษ', icon: 'fluent:star-24-regular', count: counts.special },
  ]
})

const filteredBadges = computed(() => {
  if (selectedCategory.value === 'all') {
    return badges.value
  }
  return badges.value.filter(b => b.category === selectedCategory.value)
})

const earnedBadges = computed(() => filteredBadges.value.filter(b => b.is_earned))
const lockedBadges = computed(() => filteredBadges.value.filter(b => !b.is_earned))

const totalPoints = computed(() => {
  return badges.value
    .filter(b => b.is_earned)
    .reduce((sum, b) => sum + b.points, 0)
})

// Rarity colors
const rarityColors: Record<string, { bg: string; border: string; text: string; glow: string }> = {
  common: { bg: 'bg-gray-600', border: 'border-gray-500', text: 'text-gray-400', glow: 'shadow-gray-500/20' },
  uncommon: { bg: 'bg-green-600', border: 'border-green-500', text: 'text-green-400', glow: 'shadow-green-500/30' },
  rare: { bg: 'bg-blue-600', border: 'border-blue-500', text: 'text-blue-400', glow: 'shadow-blue-500/30' },
  epic: { bg: 'bg-purple-600', border: 'border-purple-500', text: 'text-purple-400', glow: 'shadow-purple-500/40' },
  legendary: { bg: 'bg-amber-600', border: 'border-amber-500', text: 'text-amber-400', glow: 'shadow-amber-500/50' },
}

const rarityLabels: Record<string, string> = {
  common: 'ธรรมดา',
  uncommon: 'ไม่ธรรมดา',
  rare: 'หายาก',
  epic: 'มหากาพย์',
  legendary: 'ตำนาน',
}

// Fetch badges
const fetchBadges = async () => {
  isLoading.value = true
  
  try {
    const endpoint = props.userId 
      ? `/api/users/${props.userId}/badges`
      : `/api/profile/badges`
    
    const response = await api.get(endpoint) as {
      success: boolean
      data?: Badge[]
      badges?: Badge[]
    }
    
    if (response.success) {
      badges.value = response.data || response.badges || []
    } else {
      badges.value = []
    }
  } catch (error) {
    console.error('Error fetching badges:', error)
    badges.value = []
  } finally {
    isLoading.value = false
  }
}

// Mock data generator
const getMockBadges = (): Badge[] => [
  {
    id: 1,
    name: 'First Steps',
    description: 'สร้างโพสต์แรกของคุณบน Plearnd',
    icon: 'fluent:document-checkmark-24-filled',
    icon_color: '#10b981',
    background_color: '#064e3b',
    category: 'achievement',
    rarity: 'common',
    points: 10,
    is_earned: true,
    earned_at: '2024-01-15T10:00:00Z'
  },
  {
    id: 2,
    name: 'Social Butterfly',
    description: 'เพิ่มเพื่อน 10 คน',
    icon: 'fluent:people-24-filled',
    icon_color: '#3b82f6',
    background_color: '#1e3a5f',
    category: 'social',
    rarity: 'uncommon',
    points: 25,
    is_earned: true,
    earned_at: '2024-02-01T15:30:00Z'
  },
  {
    id: 3,
    name: 'Point Collector',
    description: 'สะสมแต้ม 100 แต้ม',
    icon: 'fluent:star-24-filled',
    icon_color: '#f59e0b',
    background_color: '#451a03',
    category: 'milestone',
    rarity: 'uncommon',
    points: 30,
    is_earned: true,
    earned_at: '2024-02-15T12:00:00Z'
  },
  {
    id: 4,
    name: 'Level Master',
    description: 'ถึง Level 10',
    icon: 'fluent:trophy-24-filled',
    icon_color: '#8b5cf6',
    background_color: '#2e1065',
    category: 'achievement',
    rarity: 'rare',
    points: 50,
    is_earned: false,
    progress: 6,
    max_progress: 10
  },
  {
    id: 5,
    name: 'Legendary Explorer',
    description: 'ใช้งาน Plearnd ครบ 1 ปี',
    icon: 'fluent:compass-northwest-24-filled',
    icon_color: '#f59e0b',
    background_color: '#78350f',
    category: 'special',
    rarity: 'legendary',
    points: 200,
    is_earned: false,
    progress: 120,
    max_progress: 365
  },
  {
    id: 6,
    name: 'Active Learner',
    description: 'เรียนรู้บทเรียน 5 บท',
    icon: 'fluent:book-24-filled',
    icon_color: '#06b6d4',
    background_color: '#164e63',
    category: 'activity',
    rarity: 'common',
    points: 15,
    is_earned: true,
    earned_at: '2024-03-01T09:00:00Z'
  },
  {
    id: 7,
    name: 'Community Champion',
    description: 'ได้รับ 100 likes จากโพสต์',
    icon: 'fluent:heart-24-filled',
    icon_color: '#ec4899',
    background_color: '#831843',
    category: 'social',
    rarity: 'epic',
    points: 100,
    is_earned: false,
    progress: 45,
    max_progress: 100
  },
  {
    id: 8,
    name: 'Early Bird',
    description: 'เป็นสมาชิกยุคแรกของ Plearnd',
    icon: 'fluent:flash-24-filled',
    icon_color: '#f59e0b',
    background_color: '#451a03',
    category: 'special',
    rarity: 'legendary',
    points: 150,
    is_earned: true,
    earned_at: '2023-06-01T00:00:00Z'
  }
]

// View badge details
const openBadgeModal = (badge: Badge) => {
  selectedBadge.value = badge
  showBadgeModal.value = true
}

const closeBadgeModal = () => {
  showBadgeModal.value = false
  selectedBadge.value = null
}

// Format date
const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Initialize
onMounted(() => {
  fetchBadges()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header Stats -->
    <BaseCard class="bg-gray-800 border-gray-700 overflow-hidden">
      <div class="bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h3 class="text-2xl font-bold text-white flex items-center gap-3">
              <Icon icon="fluent:ribbon-star-24-filled" class="w-8 h-8" />
              ตราสัญลักษณ์ & ความสำเร็จ
            </h3>
            <p class="text-white/80 mt-1">รวบรวมตราสัญลักษณ์เพื่อปลดล็อกรางวัลพิเศษ!</p>
          </div>
          
          <div class="flex items-center gap-6">
            <div class="text-center">
              <p class="text-3xl font-black text-white">{{ earnedBadges.length }}</p>
              <p class="text-white/70 text-sm">ได้รับแล้ว</p>
            </div>
            <div class="w-px h-12 bg-white/20"></div>
            <div class="text-center">
              <p class="text-3xl font-black text-white">{{ totalPoints }}</p>
              <p class="text-white/70 text-sm">แต้มรวม</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Category Tabs -->
      <div class="p-4 flex gap-2 overflow-x-auto scrollbar-hide">
        <button class="min-h-[44px] sm:min-h-0"
          v-for="category in categories"
          :key="category.key"
          @click="selectedCategory = category.key"
          :class="[
            'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap',
            selectedCategory === category.key
              ? 'bg-vikinger-purple text-white'
              : 'text-gray-400 hover:text-white hover:bg-gray-700'
          ]"
        >
          <Icon :icon="category.icon" class="w-4 h-4" />
          {{ category.label }}
          <span 
            v-if="category.count > 0"
            :class="[
              'px-1.5 py-0.5 text-xs rounded-full',
              selectedCategory === category.key 
                ? 'bg-white/20 text-white' 
                : 'bg-gray-700 text-gray-300'
            ]"
          >
            {{ category.count }}
          </span>
        </button>
      </div>
    </BaseCard>

    <!-- Loading State -->
    <div v-if="isLoading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
      <div v-for="i in 10" :key="i" class="animate-pulse">
        <div class="aspect-square bg-gray-800 rounded-xl"></div>
      </div>
    </div>

    <!-- Earned Badges Section -->
    <div v-else-if="filteredBadges.length > 0">
      <!-- Earned Badges -->
      <div v-if="earnedBadges.length > 0">
        <h4 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
          <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 text-green-500" />
          ได้รับแล้ว ({{ earnedBadges.length }})
        </h4>
        
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-8">
          <div
            v-for="badge in earnedBadges"
            :key="badge.id"
            @click="openBadgeModal(badge)"
            :class="[
              'relative aspect-square rounded-xl cursor-pointer transition-all duration-300 hover:scale-105 overflow-hidden',
              'border-2 shadow-lg',
              rarityColors[badge.rarity].border,
              rarityColors[badge.rarity].glow
            ]"
            :style="{ backgroundColor: badge.background_color }"
          >
            <!-- Rarity Indicator -->
            <div 
              :class="[
                'absolute top-2 right-2 px-2 py-0.5 rounded-full text-xs font-medium',
                rarityColors[badge.rarity].bg,
                'text-white'
              ]"
            >
              {{ rarityLabels[badge.rarity] }}
            </div>
            
            <!-- Badge Icon -->
            <div class="absolute inset-0 flex items-center justify-center">
              <Icon 
                :icon="badge.icon" 
                class="w-16 h-16"
                :style="{ color: badge.icon_color }"
              />
            </div>
            
            <!-- Badge Name -->
            <div class="absolute bottom-0 left-0 right-0 p-3 bg-gradient-to-t from-black/80 to-transparent">
              <p class="text-white text-sm font-medium text-center truncate">
                {{ badge.name }}
              </p>
              <p class="text-center text-xs text-amber-400 flex items-center justify-center gap-1">
                <Icon icon="fluent:star-12-filled" class="w-3 h-3" />
                +{{ badge.points }}
              </p>
            </div>
            
            <!-- Shine Effect -->
            <div class="absolute inset-0 bg-gradient-to-br from-white/20 via-transparent to-transparent opacity-0 hover:opacity-100 transition-opacity" />
          </div>
        </div>
      </div>

      <!-- Locked Badges -->
      <div v-if="lockedBadges.length > 0">
        <h4 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
          <Icon icon="fluent:lock-closed-24-filled" class="w-5 h-5 text-gray-500" />
          ยังไม่ได้รับ ({{ lockedBadges.length }})
        </h4>
        
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
          <div
            v-for="badge in lockedBadges"
            :key="badge.id"
            @click="openBadgeModal(badge)"
            class="relative aspect-square bg-gray-800/50 rounded-xl cursor-pointer transition-all duration-300 hover:bg-gray-800 border-2 border-gray-700 border-dashed overflow-hidden"
          >
            <!-- Badge Icon (Grayscale) -->
            <div class="absolute inset-0 flex items-center justify-center opacity-30">
              <Icon 
                :icon="badge.icon" 
                class="w-16 h-16 text-gray-500"
              />
            </div>
            
            <!-- Lock Overlay -->
            <div class="absolute inset-0 flex items-center justify-center">
              <div class="w-12 h-12 rounded-full bg-gray-700 flex items-center justify-center">
                <Icon icon="fluent:lock-closed-24-filled" class="w-6 h-6 text-gray-500" />
              </div>
            </div>
            
            <!-- Progress Bar (if available) -->
            <div v-if="badge.progress !== undefined && badge.max_progress" class="absolute bottom-0 left-0 right-0 p-3">
              <div class="h-1.5 bg-gray-700 rounded-full overflow-hidden">
                <div 
                  class="h-full bg-vikinger-purple rounded-full"
                  :style="{ width: (badge.progress / badge.max_progress * 100) + '%' }"
                />
              </div>
              <p class="text-xs text-gray-500 text-center mt-1">
                {{ badge.progress }}/{{ badge.max_progress }}
              </p>
            </div>
            
            <!-- Badge Name -->
            <p class="absolute top-3 left-0 right-0 text-gray-500 text-xs text-center truncate px-2">
              {{ badge.name }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <BaseCard v-else class="bg-gray-800 border-gray-700 text-center py-12">
      <Icon icon="fluent:ribbon-star-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
      <p class="text-gray-400">ยังไม่มีตราสัญลักษณ์ในหมวดนี้</p>
    </BaseCard>

    <!-- Badge Detail Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div 
          v-if="showBadgeModal && selectedBadge"
          class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
          <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="closeBadgeModal" />
          
          <div 
            class="relative bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border-2"
            :class="rarityColors[selectedBadge.rarity].border"
          >
            <!-- Header with Badge -->
            <div 
              class="relative h-48 flex items-center justify-center"
              :style="{ backgroundColor: selectedBadge.background_color }"
            >
              <!-- Decorative circles -->
              <div class="absolute inset-0 overflow-hidden">
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 rounded-full border border-white/10" />
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-48 h-48 rounded-full border border-white/10" />
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-32 h-32 rounded-full border border-white/10" />
              </div>
              
              <!-- Badge Icon -->
              <div class="relative z-10">
                <Icon 
                  :icon="selectedBadge.icon" 
                  class="w-24 h-24"
                  :style="{ color: selectedBadge.icon_color }"
                />
                
                <!-- Glow effect -->
                <div 
                  v-if="selectedBadge.is_earned"
                  class="absolute inset-0 blur-xl opacity-50"
                  :style="{ backgroundColor: selectedBadge.icon_color }"
                />
              </div>
              
              <!-- Locked Overlay -->
              <div 
                v-if="!selectedBadge.is_earned"
                class="absolute inset-0 bg-black/60 flex items-center justify-center"
              >
                <div class="w-20 h-20 rounded-full bg-gray-800 flex items-center justify-center">
                  <Icon icon="fluent:lock-closed-24-filled" class="w-10 h-10 text-gray-500" />
                </div>
              </div>
              
              <!-- Close Button -->
              <button
                @click="closeBadgeModal"
                class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center absolute top-4 right-4 p-2 bg-black/30 rounded-full text-white hover:bg-black/50 transition-colors"
              >
                <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
              </button>
              
              <!-- Rarity Badge -->
              <div 
                :class="[
                  'absolute top-4 left-4 px-3 py-1 rounded-full text-sm font-medium',
                  rarityColors[selectedBadge.rarity].bg,
                  'text-white'
                ]"
              >
                {{ rarityLabels[selectedBadge.rarity] }}
              </div>
            </div>
            
            <!-- Content -->
            <div class="p-6">
              <h3 class="text-2xl font-bold text-white text-center mb-2">
                {{ selectedBadge.name }}
              </h3>
              
              <p class="text-gray-400 text-center mb-6">
                {{ selectedBadge.description }}
              </p>
              
              <!-- Stats -->
              <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-gray-800 rounded-xl p-4 text-center">
                  <Icon icon="fluent:star-24-filled" class="w-6 h-6 text-amber-400 mx-auto mb-1" />
                  <p class="text-xl font-bold text-white">+{{ selectedBadge.points }}</p>
                  <p class="text-xs text-gray-500">แต้ม</p>
                </div>
                <div class="bg-gray-800 rounded-xl p-4 text-center">
                  <Icon 
                    :icon="selectedBadge.is_earned ? 'fluent:checkmark-circle-24-filled' : 'fluent:hourglass-24-regular'" 
                    :class="['w-6 h-6 mx-auto mb-1', selectedBadge.is_earned ? 'text-green-500' : 'text-gray-500']" 
                  />
                  <p class="text-xl font-bold text-white">
                    {{ selectedBadge.is_earned ? 'ได้รับแล้ว' : 'กำลังดำเนินการ' }}
                  </p>
                  <p v-if="selectedBadge.earned_at" class="text-xs text-gray-500">
                    {{ formatDate(selectedBadge.earned_at) }}
                  </p>
                  <p v-else-if="selectedBadge.progress !== undefined" class="text-xs text-gray-500">
                    {{ selectedBadge.progress }}/{{ selectedBadge.max_progress }}
                  </p>
                </div>
              </div>
              
              <!-- Progress Bar (for locked badges) -->
              <div v-if="!selectedBadge.is_earned && selectedBadge.progress !== undefined" class="mb-6">
                <div class="flex justify-between text-sm mb-2">
                  <span class="text-gray-400">ความคืบหน้า</span>
                  <span class="text-vikinger-cyan font-medium">
                    {{ Math.round((selectedBadge.progress! / selectedBadge.max_progress!) * 100) }}%
                  </span>
                </div>
                <div class="h-3 bg-gray-800 rounded-full overflow-hidden">
                  <div 
                    class="h-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-full transition-all duration-500"
                    :style="{ width: (selectedBadge.progress! / selectedBadge.max_progress! * 100) + '%' }"
                  />
                </div>
              </div>
              
              <!-- Action Button -->
              <button
                @click="closeBadgeModal"
                class="w-full py-3 bg-vikinger-purple text-white rounded-xl font-medium hover:bg-vikinger-purple/80 transition-colors"
              >
                {{ selectedBadge.is_earned ? 'ยอดเยี่ยม! 🎉' : 'สู้ต่อไป! 💪' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
