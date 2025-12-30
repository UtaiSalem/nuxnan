<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'
import LessonPost from './LessonPost.vue'

interface Props {
  lessons: any[]
  isAdmin?: boolean
  currentUser?: any
}

const props = withDefaults(defineProps<Props>(), {
  isAdmin: false,
  lessons: () => []
})

const emit = defineEmits<{
  'edit': [lesson: any]
  'delete': [lessonId: number]
  'like': [lessonId: number]
  'bookmark': [lessonId: number]
  'share': [lesson: any]
  'comment': [lessonId: number]
  'filter-change': [filter: string]
  'sort-change': [sort: string]
}>()

// State
const currentFilter = ref('all')
const currentSort = ref('order')
const searchQuery = ref('')

// Filter options
const filters = [
  { value: 'all', label: 'ทั้งหมด', icon: 'fluent:grid-24-regular' },
  { value: 'in_progress', label: 'กำลังเรียน', icon: 'fluent:clock-24-regular' },
  { value: 'completed', label: 'เรียนจบแล้ว', icon: 'fluent:checkmark-circle-24-regular' },
  { value: 'bookmarked', label: 'บุ๊กมาร์กไว้', icon: 'fluent:bookmark-24-filled' },
  { value: 'draft', label: 'แบบร่าง', icon: 'fluent:draft-24-regular' }
]

// Sort options
const sortOptions = [
  { value: 'order', label: 'ลำดับบทเรียน', icon: 'fluent:number-symbol-24-regular' },
  { value: 'latest', label: 'ล่าสุด', icon: 'fluent:clock-24-regular' },
  { value: 'most_viewed', label: 'ยอดดูสูงสุด', icon: 'fluent:eye-24-regular' },
  { value: 'most_liked', label: 'ถูกใจมากที่สุด', icon: 'fluent:thumb-like-24-filled' }
]

// Computed
const filteredLessons = computed(() => {
  let result = [...props.lessons]

  // Apply search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(lesson => 
      lesson.title?.toLowerCase().includes(query) ||
      lesson.description?.toLowerCase().includes(query)
    )
  }

  // Apply filter
  if (currentFilter.value !== 'all') {
    if (currentFilter.value === 'bookmarked') {
      result = result.filter(lesson => lesson.is_bookmarked_by_auth)
    } else if (currentFilter.value === 'draft') {
      result = result.filter(lesson => lesson.status === 0)
    } else if (currentFilter.value === 'completed') {
      result = result.filter(lesson => lesson.is_completed)
    } else if (currentFilter.value === 'in_progress') {
      result = result.filter(lesson => lesson.is_in_progress)
    }
  }

  // Apply sort
  if (currentSort.value === 'latest') {
    result.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
  } else if (currentSort.value === 'most_viewed') {
    result.sort((a, b) => (b.view_count || 0) - (a.view_count || 0))
  } else if (currentSort.value === 'most_liked') {
    result.sort((a, b) => (b.like_count || 0) - (a.like_count || 0))
  } else {
    // Default: by order
    result.sort((a, b) => (a.order || 0) - (b.order || 0))
  }

  return result
})

const hasLessons = computed(() => filteredLessons.value.length > 0)

// Methods
const handleFilterChange = (filter: string) => {
  currentFilter.value = filter
  emit('filter-change', filter)
}

const handleSortChange = (sort: string) => {
  currentSort.value = sort
  emit('sort-change', sort)
}
</script>

<template>
  <div class="space-y-4">
    
    <!-- Filters & Search Bar -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-4 space-y-4">
      
      <!-- Search -->
      <div class="relative">
        <Icon 
          icon="fluent:search-24-regular" 
          class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
        />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="ค้นหาบทเรียน..."
          class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white placeholder-gray-400"
        >
      </div>

      <!-- Filter Pills -->
      <div class="flex items-center gap-2 overflow-x-auto pb-2">
        <button
          v-for="filter in filters"
          :key="filter.value"
          @click="handleFilterChange(filter.value)"
          class="flex items-center gap-2 px-4 py-2 rounded-lg font-medium text-sm whitespace-nowrap transition-all duration-300"
          :class="[
            currentFilter === filter.value
              ? 'bg-blue-500 text-white shadow-lg scale-105'
              : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
          ]"
        >
          <Icon :icon="filter.icon" class="w-4 h-4" />
          {{ filter.label }}
        </button>
      </div>

      <!-- Sort Dropdown -->
      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          แสดง {{ filteredLessons.length }} จาก {{ lessons.length }} บทเรียน
        </p>
        <div class="flex items-center gap-2">
          <Icon icon="fluent:arrow-sort-24-regular" class="w-4 h-4 text-gray-500" />
          <select
            v-model="currentSort"
            @change="handleSortChange(currentSort)"
            class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white"
          >
            <option v-for="option in sortOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Lessons Feed -->
    <div v-if="hasLessons" class="space-y-6">
      <LessonPost
        v-for="lesson in filteredLessons"
        :key="lesson.id"
        :lesson="lesson"
        :is-admin="isAdmin"
        :current-user="currentUser"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
        @like="$emit('like', $event)"
        @bookmark="$emit('bookmark', $event)"
        @share="$emit('share', $event)"
        @comment="$emit('comment', $event)"
      />
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-12 text-center">
      <Icon icon="fluent:book-search-24-regular" class="w-24 h-24 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
      <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
        ไม่พบบทเรียน
      </h3>
      <p class="text-gray-600 dark:text-gray-400 mb-6">
        {{ searchQuery ? 'ลองค้นหาด้วยคำอื่น' : 'ยังไม่มีบทเรียนในรายวิชานี้' }}
      </p>
      <button
        v-if="currentFilter !== 'all'"
        @click="handleFilterChange('all')"
        class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors font-medium"
      >
        แสดงบทเรียนทั้งหมด
      </button>
    </div>
  </div>
</template>
