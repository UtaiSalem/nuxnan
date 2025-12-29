<script setup>
import { computed, ref } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  percentage: {
    type: Number,
    default: 59
  },
  quests: {
    type: Object,
    default: () => ({ completed: 11, total: 30 })
  },
  badges: {
    type: Object,
    default: () => ({ unlocked: 22, total: 46 })
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
  <div class="vikinger-card space-y-6">
    <!-- Profile Completion Circle -->
    <div class="text-center">
      <div class="relative inline-flex items-center justify-center">
        <svg class="w-40 h-40 transform -rotate-90">
          <!-- Background circle -->
          <circle
            cx="80"
            cy="80"
            r="58"
            stroke="currentColor"
            stroke-width="12"
            fill="none"
            class="text-vikinger-light-400 dark:text-vikinger-dark-200"
          />
          <!-- Progress circle -->
          <circle
            cx="80"
            cy="80"
            r="58"
            stroke="url(#gradient)"
            stroke-width="12"
            fill="none"
            stroke-linecap="round"
            :stroke-dasharray="circumference"
            :stroke-dashoffset="offset"
            class="transition-all duration-1000 ease-out"
          />
          <defs>
            <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#00D9FF" />
              <stop offset="100%" stop-color="#615DFA" />
            </linearGradient>
          </defs>
        </svg>
        <div class="absolute inset-0 flex items-center justify-center">
          <span class="text-4xl font-bold text-gray-800 dark:text-white">{{ percentage }}%</span>
        </div>
      </div>
      <h3 class="mt-4 text-lg font-bold text-gray-800 dark:text-white">Profile Completion</h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
        Complete your profile by filling profile<br>
        info, tabs, uploading avatar &<br>
        marking widgets
      </p>
      
      <!-- Missing Fields List -->
      <div v-if="missingFields && missingFields.length > 0" class="mt-4 text-left">
        <ul class="space-y-2">
          <li 
            v-for="field in missingFields.slice(0, 3)" 
            :key="field.field"
            class="flex items-center gap-2 text-sm"
          >
            <Icon :icon="getFieldIcon(field.field)" class="w-4 h-4 text-vikinger-purple" />
            <span class="text-gray-600 dark:text-gray-400">{{ field.label }}</span>
            <span class="ml-auto text-xs text-vikinger-purple font-medium">+{{ field.weight }}%</span>
          </li>
        </ul>
        
        <!-- Edit Profile Button -->
        <button 
          v-if="showActions"
          @click="handleEditClick"
          class="mt-4 w-full py-2 px-4 bg-vikinger-purple text-white text-sm font-medium rounded-vikinger hover:bg-vikinger-purple/80 transition-colors flex items-center justify-center gap-2"
        >
          <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
          Complete Profile
        </button>
      </div>
    </div>

    <!-- All Updates Section -->
    <div class="flex items-center justify-center gap-3 py-3 border-t border-b border-vikinger-light-400 dark:border-vikinger-dark-200">
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
          'px-3 py-1.5 text-xs font-medium rounded-vikinger transition-colors',
          activeTab === tab.toLowerCase() 
            ? 'bg-vikinger-purple text-white' 
            : 'bg-vikinger-light-300 dark:bg-vikinger-dark-200 text-gray-700 dark:text-gray-300 hover:bg-vikinger-purple/20'
        ]"
      >
        {{ tab }}
      </button>
    </div>

    <!-- Quests & Badges -->
    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-vikinger-light-400 dark:border-vikinger-dark-200">
      <!-- Quests -->
      <div class="text-center">
        <div class="text-2xl font-bold text-gray-800 dark:text-white">
          {{ quests.completed }}/{{ quests.total }}
        </div>
        <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 mt-1">Quests</div>
        <div class="text-xs text-gray-500 dark:text-gray-500">COMPLETED</div>
        <div class="mt-2 flex justify-center">
          <Icon icon="fluent:trophy-24-filled" class="w-8 h-8 text-yellow-500" />
        </div>
      </div>

      <!-- Badges -->
      <div class="text-center">
        <div class="text-2xl font-bold text-gray-800 dark:text-white">
          {{ badges.unlocked }}/{{ badges.total }}
        </div>
        <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 mt-1">Badges</div>
        <div class="text-xs text-gray-500 dark:text-gray-500">UNLOCKED</div>
        <div class="mt-2 flex justify-center">
          <Icon icon="fluent:shield-checkmark-24-filled" class="w-8 h-8 text-gray-400" />
        </div>
      </div>
    </div>
  </div>
</template>
