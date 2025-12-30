<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

interface Props {
  course: any
  progress?: any
  groups?: any[]
  currentFilter?: string
}

const props = withDefaults(defineProps<Props>(), {
  progress: () => ({}),
  groups: () => [],
  currentFilter: 'all'
})

const emit = defineEmits<{
  'filter-change': [filter: string]
  'create-lesson': []
}>()

// Computed
const progressPercentage = computed(() => {
  const completed = props.progress?.lessons_completed || 0
  const total = props.progress?.total_lessons || 1
  return Math.round((completed / total) * 100)
})

const filters = [
  { value: 'all', label: 'บทเรียนทั้งหมด', icon: 'fluent:grid-24-filled', color: 'text-gray-600' },
  { value: 'in_progress', label: 'กำลังเรียน', icon: 'fluent:clock-24-filled', color: 'text-blue-600' },
  { value: 'completed', label: 'เรียนจบแล้ว', icon: 'fluent:checkmark-circle-24-filled', color: 'text-green-600' },
  { value: 'bookmarked', label: 'บุ๊กมาร์กไว้', icon: 'fluent:bookmark-24-filled', color: 'text-amber-600' },
  { value: 'draft', label: 'แบบร่าง', icon: 'fluent:draft-24-regular', color: 'text-gray-500' }
]
</script>

<template>
  <aside class="space-y-4 sticky top-4">
    
    <!-- Course Quick Info -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-6">
      <div class="flex items-center gap-4 mb-4">
        <img 
          :src="course.logo || course.cover"
          :alt="course.name"
          class="w-16 h-16 rounded-xl object-cover ring-2 ring-blue-500"
        >
        <div class="flex-1">
          <h3 class="font-bold text-gray-900 dark:text-white line-clamp-2">
            {{ course.name }}
          </h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ course.code }}
          </p>
        </div>
      </div>

      <!-- Progress Circle -->
      <div class="relative w-32 h-32 mx-auto mb-4">
        <svg class="w-full h-full transform -rotate-90">
          <!-- Background circle -->
          <circle
            cx="64"
            cy="64"
            r="56"
            stroke="currentColor"
            class="text-gray-200 dark:text-gray-700"
            stroke-width="8"
            fill="none"
          />
          <!-- Progress circle -->
          <circle
            cx="64"
            cy="64"
            r="56"
            stroke="currentColor"
            class="text-blue-500"
            stroke-width="8"
            fill="none"
            :stroke-dasharray="`${2 * Math.PI * 56}`"
            :stroke-dashoffset="`${2 * Math.PI * 56 * (1 - progressPercentage / 100)}`"
            stroke-linecap="round"
          />
        </svg>
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="text-center">
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
              {{ progressPercentage }}%
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              เสร็จสิ้น
            </p>
          </div>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 gap-3">
        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
          <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">บทเรียน</p>
          <p class="text-lg font-bold text-blue-600 dark:text-blue-400">
            {{ progress?.lessons_completed || 0 }}/{{ progress?.total_lessons || 0 }}
          </p>
        </div>
        <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
          <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">พอยต์</p>
          <p class="text-lg font-bold text-green-600 dark:text-green-400">
            {{ progress?.points_earned || 0 }}
          </p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-4">
      <h3 class="flex items-center gap-2 font-semibold text-gray-900 dark:text-white mb-4">
        <Icon icon="fluent:filter-24-filled" class="w-5 h-5" />
        กรองบทเรียน
      </h3>
      <div class="space-y-2">
        <button
          v-for="filter in filters"
          :key="filter.value"
          @click="$emit('filter-change', filter.value)"
          class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300"
          :class="[
            currentFilter === filter.value
              ? 'bg-blue-500 text-white shadow-md scale-105'
              : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300'
          ]"
        >
          <Icon 
            :icon="filter.icon" 
            class="w-5 h-5"
            :class="currentFilter === filter.value ? 'text-white' : filter.color"
          />
          <span class="font-medium">{{ filter.label }}</span>
        </button>
      </div>
    </div>

    <!-- Groups Filter (if applicable) -->
    <div v-if="groups && groups.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-4">
      <h3 class="flex items-center gap-2 font-semibold text-gray-900 dark:text-white mb-4">
        <Icon icon="fluent:people-team-24-filled" class="w-5 h-5" />
        กลุ่มเรียน
      </h3>
      <div class="space-y-2">
        <button
          v-for="group in groups"
          :key="group.id"
          class="w-full flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors text-left"
        >
          <Icon icon="fluent:people-24-filled" class="w-4 h-4 text-purple-500" />
          <span class="text-sm">{{ group.name }}</span>
        </button>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-gradient-to-br from-blue-500 to-purple-500 rounded-2xl shadow-lg p-6 text-white">
      <h3 class="font-bold text-lg mb-4">การดำเนินการด่วน</h3>
      <div class="space-y-3">
        <button
          @click="$emit('create-lesson')"
          class="w-full flex items-center gap-2 px-4 py-3 bg-white/20 backdrop-blur-sm rounded-xl hover:bg-white/30 transition-all duration-300"
        >
          <Icon icon="fluent:add-circle-24-filled" class="w-5 h-5" />
          <span class="font-medium">สร้างบทเรียนใหม่</span>
        </button>
        <button class="w-full flex items-center gap-2 px-4 py-3 bg-white/20 backdrop-blur-sm rounded-xl hover:bg-white/30 transition-all duration-300">
          <Icon icon="fluent:document-arrow-down-24-filled" class="w-5 h-5" />
          <span class="font-medium">ดาวน์โหลดเอกสาร</span>
        </button>
      </div>
    </div>
  </aside>
</template>
