<script setup>
import { computed } from 'vue'
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
  }
})

const circumference = 2 * Math.PI * 58
const offset = computed(() => circumference - (props.percentage / 100) * circumference)
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
    </div>

    <!-- All Updates Section -->
    <div class="flex items-center justify-center gap-3 py-3 border-t border-b border-vikinger-light-400 dark:border-vikinger-dark-200">
      <Icon icon="fluent:feed-24-regular" class="w-5 h-5 text-vikinger-purple" />
      <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">All Updates</span>
      <Icon icon="fluent:chevron-down-24-regular" class="w-4 h-4 text-gray-500" />
    </div>

    <!-- Tabs: Status, Mentions, Friends, Groups, Blog Posts -->
    <div class="flex gap-2 justify-center flex-wrap">
      <button class="px-3 py-1.5 text-xs font-medium rounded-vikinger bg-vikinger-purple text-white">
        Status
      </button>
      <button class="px-3 py-1.5 text-xs font-medium rounded-vikinger bg-vikinger-light-300 dark:bg-vikinger-dark-200 text-gray-700 dark:text-gray-300 hover:bg-vikinger-purple/20">
        Mentions
      </button>
      <button class="px-3 py-1.5 text-xs font-medium rounded-vikinger bg-vikinger-light-300 dark:bg-vikinger-dark-200 text-gray-700 dark:text-gray-300 hover:bg-vikinger-purple/20">
        Friends
      </button>
      <button class="px-3 py-1.5 text-xs font-medium rounded-vikinger bg-vikinger-light-300 dark:bg-vikinger-dark-200 text-gray-700 dark:text-gray-300 hover:bg-vikinger-purple/20">
        Groups
      </button>
      <button class="px-3 py-1.5 text-xs font-medium rounded-vikinger bg-vikinger-light-300 dark:bg-vikinger-dark-200 text-gray-700 dark:text-gray-300 hover:bg-vikinger-purple/20">
        Blog Posts
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
