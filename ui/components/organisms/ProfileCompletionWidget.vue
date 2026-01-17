<script setup>
import { computed, ref } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  percentage: {
    type: Number,
    default: 0
  },
  quests: {
    type: Object,
    default: () => ({ completed: 0, total: 30 })
  },
  badges: {
    type: Object,
    default: () => ({ unlocked: 0, total: 46 })
  },
  missingFields: {
    type: Array,
    default: () => []
  },
  showActions: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['edit-profile'])

const activeTab = ref('status')
const tabs = ['Status', 'Mentions', 'Friends', 'Groups', 'Blog Posts']

const circumference = 2 * Math.PI * 58
const offset = computed(() => circumference - (props.percentage / 100) * circumference)

// Dynamic color based on percentage
const progressColor = computed(() => {
  if (props.percentage >= 80) return { from: '#10B981', to: '#059669' } // Green
  if (props.percentage >= 50) return { from: '#00D9FF', to: '#615DFA' } // Cyan to Purple
  if (props.percentage >= 25) return { from: '#F59E0B', to: '#D97706' } // Amber
  return { from: '#EF4444', to: '#DC2626' } // Red
})

// Get icon for missing field
const getFieldIcon = (field) => {
  const icons = {
    avatar: 'fluent:camera-24-regular',
    cover: 'fluent:image-24-regular',
    first_name: 'fluent:person-24-regular',
    last_name: 'fluent:person-24-regular',
    bio: 'fluent:text-description-24-regular',
    birthdate: 'fluent:calendar-24-regular',
    gender: 'fluent:people-24-regular',
    location: 'fluent:location-24-regular',
    website: 'fluent:globe-24-regular',
    interests: 'fluent:heart-24-regular',
    social_media: 'fluent:share-24-regular',
  }
  return icons[field] || 'fluent:add-circle-24-regular'
}

const handleEditClick = () => {
  emit('edit-profile')
}
</script>

<template>
  <div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl !p-0">
    <!-- Header with Gradient -->
    <div class="relative bg-gradient-to-r from-vikinger-purple via-indigo-500 to-vikinger-cyan p-4">
      <div class="absolute inset-0 bg-[url('/images/patterns/circuit.svg')] bg-repeat opacity-10"></div>
      <h3 class="relative text-lg font-black text-white flex items-center gap-2 drop-shadow-lg">
        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
          <Icon icon="fluent:person-circle-24-filled" class="w-5 h-5" />
        </div>
        Profile Completion
      </h3>
    </div>
    
    <div class="p-5 space-y-5">
      <!-- Profile Completion Circle -->
      <div class="text-center">
        <div class="relative inline-flex items-center justify-center">
          <!-- Glow Effect -->
          <div class="absolute inset-0 m-4 rounded-full blur-xl opacity-30" 
               :style="{ background: `linear-gradient(135deg, ${progressColor.from}, ${progressColor.to})` }"></div>
          
          <svg class="w-36 h-36 transform -rotate-90 relative">
            <!-- Background circle -->
            <circle
              cx="72"
              cy="72"
              r="58"
              stroke="currentColor"
              stroke-width="10"
              fill="none"
              class="text-gray-200 dark:text-gray-700"
            />
            <!-- Progress circle -->
            <circle
              cx="72"
              cy="72"
              r="58"
              :stroke="`url(#progressGradient-${percentage})`"
              stroke-width="10"
              fill="none"
              stroke-linecap="round"
              :stroke-dasharray="circumference"
              :stroke-dashoffset="offset"
              class="transition-all duration-1000 ease-out drop-shadow-lg"
            />
            <defs>
              <linearGradient :id="`progressGradient-${percentage}`" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" :stop-color="progressColor.from" />
                <stop offset="100%" :stop-color="progressColor.to" />
              </linearGradient>
            </defs>
          </svg>
          <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-4xl font-black text-gray-800 dark:text-white">{{ percentage }}%</span>
            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Complete</span>
          </div>
        </div>
      </div>
      
      <!-- Missing Fields List -->
      <div v-if="missingFields && missingFields.length > 0" class="space-y-2">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Complete these to boost:</p>
        <ul class="space-y-2">
          <li 
            v-for="field in missingFields.slice(0, 4)" 
            :key="field.field"
            class="flex items-center gap-3 p-2 rounded-xl bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors cursor-pointer group"
            @click="handleEditClick"
          >
            <div class="w-8 h-8 rounded-lg bg-vikinger-purple/20 flex items-center justify-center group-hover:bg-vikinger-purple/30 transition-colors">
              <Icon :icon="getFieldIcon(field.field)" class="w-4 h-4 text-vikinger-purple" />
            </div>
            <span class="text-sm text-gray-700 dark:text-gray-300 flex-1">{{ field.label }}</span>
            <span class="text-xs font-bold text-vikinger-cyan bg-vikinger-cyan/10 px-2 py-1 rounded-full">+{{ field.weight }}%</span>
          </li>
        </ul>
        
        <!-- Edit Profile Button -->
        <button 
          v-if="showActions"
          @click="handleEditClick"
          class="mt-3 w-full py-2.5 px-4 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white text-sm font-bold rounded-xl hover:opacity-90 transition-all flex items-center justify-center gap-2 shadow-lg hover:shadow-xl hover:scale-[1.02]"
        >
          <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
          Complete Profile
        </button>
      </div>
      
      <!-- Completed Message -->
      <div v-else class="text-center py-4">
        <div class="w-16 h-16 mx-auto rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-3">
          <Icon icon="fluent:checkmark-circle-24-filled" class="w-10 h-10 text-green-500" />
        </div>
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Profile Complete!</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Great job completing your profile</p>
      </div>

      <!-- All Updates Section -->
      <div class="flex items-center justify-center gap-3 py-3 border-t border-b border-gray-200 dark:border-gray-700">
        <Icon icon="fluent:feed-24-regular" class="w-5 h-5 text-vikinger-purple" />
        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">All Updates</span>
        <Icon icon="fluent:chevron-down-24-regular" class="w-4 h-4 text-gray-500" />
      </div>

      <!-- Tabs: Status, Mentions, Friends, Groups, Blog Posts -->
      <div class="flex gap-2 justify-center flex-wrap">
        <button 
          v-for="tab in tabs" 
          :key="tab"
          @click="activeTab = tab.toLowerCase()"
          :class="[
            'px-3 py-1.5 text-xs font-medium rounded-xl transition-all',
            activeTab === tab.toLowerCase() 
              ? 'bg-vikinger-purple text-white shadow-md' 
              : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-vikinger-purple/20 hover:text-vikinger-purple'
          ]"
        >
          {{ tab }}
        </button>
      </div>

      <!-- Quests & Badges -->
      <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <!-- Quests -->
        <div class="text-center p-3 rounded-xl bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200/50 dark:border-amber-800/30">
          <div class="text-2xl font-black text-gray-800 dark:text-white">
            {{ quests.completed }}/{{ quests.total }}
          </div>
          <div class="text-xs font-semibold text-amber-600 dark:text-amber-400 mt-1 uppercase tracking-wider">Quests</div>
          <div class="text-[10px] text-gray-500 dark:text-gray-500">COMPLETED</div>
          <div class="mt-2 flex justify-center">
            <Icon icon="fluent:trophy-24-filled" class="w-8 h-8 text-amber-500 drop-shadow-lg" />
          </div>
        </div>

        <!-- Badges -->
        <div class="text-center p-3 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200/50 dark:border-blue-800/30">
          <div class="text-2xl font-black text-gray-800 dark:text-white">
            {{ badges.unlocked }}/{{ badges.total }}
          </div>
          <div class="text-xs font-semibold text-blue-600 dark:text-blue-400 mt-1 uppercase tracking-wider">Badges</div>
          <div class="text-[10px] text-gray-500 dark:text-gray-500">UNLOCKED</div>
          <div class="mt-2 flex justify-center">
            <Icon icon="fluent:shield-checkmark-24-filled" class="w-8 h-8 text-blue-500 drop-shadow-lg" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
