<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'
import LessonCard from './LessonCard.vue'

interface Props {
  lessons: any[]
  isCourseAdmin?: boolean
  courseId: string | number
}

const props = withDefaults(defineProps<Props>(), {
  lessons: () => [],
  isCourseAdmin: false
})

const emit = defineEmits<{
  'edit': [lesson: any]
  'delete': [lessonId: number]
  'create': []
  'refresh': []
}>()

// State
const searchQuery = ref('')
const selectedStatus = ref<'all' | 'published' | 'draft'>('all')
const sortBy = ref<'date' | 'title' | 'views'>('date')
const sortOrder = ref<'asc' | 'desc'>('desc')
const viewMode = ref<'grid' | 'list'>('grid')
const isDeleting = ref(false)

// Filtered and sorted lessons
const filteredLessons = computed(() => {
  let result = [...props.lessons]

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(lesson => 
      lesson.title?.toLowerCase().includes(query) || 
      lesson.description?.toLowerCase().includes(query)
    )
  }

  // Status filter
  if (selectedStatus.value !== 'all') {
    const statusValue = selectedStatus.value === 'published' ? 1 : 0
    result = result.filter(lesson => lesson.status === statusValue)
  }

  // Sort
  result.sort((a, b) => {
    let compareA, compareB

    switch (sortBy.value) {
      case 'title':
        compareA = a.title?.toLowerCase() || ''
        compareB = b.title?.toLowerCase() || ''
        break
      case 'views':
        compareA = a.view_count || 0
        compareB = b.view_count || 0
        break
      case 'date':
      default:
        compareA = new Date(a.created_at || 0).getTime()
        compareB = new Date(b.created_at || 0).getTime()
    }

    if (sortOrder.value === 'asc') {
      return compareA > compareB ? 1 : -1
    } else {
      return compareA < compareB ? 1 : -1
    }
  })

  return result
})

// Navigate to lesson
const navigateToLesson = (lesson: any) => {
  navigateTo(`/courses/${props.courseId}/lessons/${lesson.id}`)
}

// Edit lesson
const editLesson = (lesson: any) => {
  navigateTo(`/courses/${props.courseId}/lessons/${lesson.id}/edit`)
}

// Delete lesson
const deleteLesson = async (lessonId: number) => {
  if (!confirm('ยืนยันการลบบทเรียนนี้หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้')) return
  
  isDeleting.value = true
  try {
    const api = useApi()
    const response = await api.delete(`/api/courses/${props.courseId}/lessons/${lessonId}`) as { success?: boolean }
    if (response.success) {
      emit('refresh')
    }
  } catch (err: any) {
    alert(err.data?.message || 'ไม่สามารถลบบทเรียนได้')
  } finally {
    isDeleting.value = false
  }
}

// Clear filters
const clearFilters = () => {
  searchQuery.value = ''
  selectedStatus.value = 'all'
  sortBy.value = 'date'
  sortOrder.value = 'desc'
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header with Title and Create Button -->
    <div class="bg-gradient-to-r from-blue-600 via-cyan-600 to-purple-600 dark:from-blue-800 dark:via-cyan-800 dark:to-purple-800 rounded-2xl p-6 shadow-xl">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg">
            <Icon icon="fluent:book-24-filled" class="w-7 h-7 text-white" />
          </div>
          <div>
            <h2 class="text-2xl font-bold text-white mb-1">บทเรียนทั้งหมด</h2>
            <p class="text-white/80 text-sm">{{ filteredLessons.length }} จาก {{ lessons.length }} บทเรียน</p>
          </div>
        </div>
        <button
          v-if="isCourseAdmin"
          @click="emit('create')"
          class="flex items-center gap-2 px-5 py-3 bg-white text-blue-600 rounded-xl hover:bg-blue-50 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 font-bold"
        >
          <Icon icon="fluent:add-circle-24-filled" class="w-5 h-5" />
          <span>เพิ่มบทเรียน</span>
        </button>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-md">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <!-- Search -->
        <div class="lg:col-span-2">
          <div class="relative">
            <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="ค้นหาบทเรียน..."
              class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all"
            >
          </div>
        </div>

        <!-- Status Filter -->
        <div>
          <select
            v-model="selectedStatus"
            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all"
          >
            <option value="all">ทั้งหมด</option>
            <option value="published">เผยแพร่แล้ว</option>
            <option value="draft">แบบร่าง</option>
          </select>
        </div>

        <!-- Sort -->
        <div>
          <select
            v-model="sortBy"
            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all"
          >
            <option value="date">วันที่สร้าง</option>
            <option value="title">ชื่อบทเรียน</option>
            <option value="views">จำนวนผู้เข้าชม</option>
          </select>
        </div>
      </div>

      <!-- Filter Actions -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <!-- Sort Order -->
          <button
            @click="sortOrder = sortOrder === 'asc' ? 'desc' : 'asc'"
            class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
          >
            <Icon 
              :icon="sortOrder === 'asc' ? 'fluent:arrow-sort-up-24-regular' : 'fluent:arrow-sort-down-24-regular'" 
              class="w-4 h-4" 
            />
            <span class="text-sm">{{ sortOrder === 'asc' ? 'น้อย → มาก' : 'มาก → น้อย' }}</span>
          </button>

          <!-- Clear Filters -->
          <button
            v-if="searchQuery || selectedStatus !== 'all'"
            @click="clearFilters"
            class="flex items-center gap-2 px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors text-sm"
          >
            <Icon icon="fluent:dismiss-circle-24-regular" class="w-4 h-4" />
            <span>ล้างตัวกรอง</span>
          </button>
        </div>

        <!-- View Mode Toggle -->
        <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
          <button
            @click="viewMode = 'grid'"
            :class="[
              'p-2 rounded-md transition-all',
              viewMode === 'grid' 
                ? 'bg-white dark:bg-gray-600 text-blue-600 dark:text-blue-400 shadow-sm' 
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
            ]"
          >
            <Icon icon="fluent:grid-24-regular" class="w-5 h-5" />
          </button>
          <button
            @click="viewMode = 'list'"
            :class="[
              'p-2 rounded-md transition-all',
              viewMode === 'list' 
                ? 'bg-white dark:bg-gray-600 text-blue-600 dark:text-blue-400 shadow-sm' 
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
            ]"
          >
            <Icon icon="fluent:list-24-regular" class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- Lessons List/Grid -->
    <div v-if="filteredLessons.length > 0">
      <div 
        :class="[
          'gap-6',
          viewMode === 'grid' 
            ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3' 
            : 'flex flex-col space-y-4'
        ]"
      >
        <LessonCard
          v-for="lesson in filteredLessons"
          :key="lesson.id"
          :lesson="lesson"
          :is-course-admin="isCourseAdmin"
          @click="navigateToLesson"
          @edit="editLesson"
          @delete="deleteLesson"
        />
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white dark:bg-gray-800 rounded-2xl border-2 border-dashed border-gray-300 dark:border-gray-600 p-16 text-center">
      <Icon icon="fluent:book-search-24-regular" class="w-24 h-24 text-gray-300 dark:text-gray-600 mx-auto mb-6" />
      <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">
        {{ searchQuery || selectedStatus !== 'all' ? 'ไม่พบบทเรียนที่ตรงกับเงื่อนไข' : 'ยังไม่มีบทเรียน' }}
      </h3>
      <p class="text-gray-500 dark:text-gray-400 mb-6">
        {{ searchQuery || selectedStatus !== 'all' 
          ? 'ลองปรับเปลี่ยนตัวกรองหรือคำค้นหา' 
          : 'รายวิชานี้ยังไม่มีบทเรียน' 
        }}
      </p>
      <button
        v-if="isCourseAdmin && !searchQuery && selectedStatus === 'all'"
        @click="emit('create')"
        class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl hover:from-blue-700 hover:to-cyan-700 transition-all shadow-lg hover:shadow-xl transform hover:scale-105 font-bold"
      >
        <Icon icon="fluent:add-circle-24-filled" class="w-5 h-5" />
        สร้างบทเรียนแรก
      </button>
      <button
        v-else-if="searchQuery || selectedStatus !== 'all'"
        @click="clearFilters"
        class="inline-flex items-center gap-2 px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-all font-medium"
      >
        <Icon icon="fluent:dismiss-circle-24-regular" class="w-5 h-5" />
        ล้างตัวกรอง
      </button>
    </div>
  </div>
</template>
