<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  member: any
  isCourseAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})

const emit = defineEmits<{
  'view-details': [member: any]
}>()

// Calculate overall progress
const overallProgress = computed(() => {
  const lessons = props.member.lessons_progress || 0
  const assignments = props.member.assignments_progress || 0
  const quizzes = props.member.quizzes_progress || 0
  
  const totalWeight = 100
  const lessonsWeight = 40
  const assignmentsWeight = 30
  const quizzesWeight = 30
  
  return Math.round(
    (lessons * lessonsWeight + assignments * assignmentsWeight + quizzes * quizzesWeight) / totalWeight
  )
})

// Get progress color
const getProgressColor = (progress: number) => {
  if (progress >= 80) return 'bg-green-500'
  if (progress >= 60) return 'bg-blue-500'
  if (progress >= 40) return 'bg-yellow-500'
  return 'bg-red-500'
}

// Get progress text color
const getProgressTextColor = (progress: number) => {
  if (progress >= 80) return 'text-green-600 dark:text-green-400'
  if (progress >= 60) return 'text-blue-600 dark:text-blue-400'
  if (progress >= 40) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-red-600 dark:text-red-400'
}

// Format date
const formatDate = (date: string) => {
  if (!date) return 'ยังไม่มีกิจกรรม'
  return new Date(date).toLocaleDateString('th-TH', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <div 
    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 hover:shadow-md transition-shadow cursor-pointer"
    @click="emit('view-details', member)"
  >
    <!-- Member Info -->
    <div class="flex items-start gap-3">
      <img
        :src="member.user?.avatar || '/images/default-avatar.png'"
        :alt="member.user?.name"
        class="w-12 h-12 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600"
      />
      
      <div class="flex-1 min-w-0">
        <h4 class="font-medium text-gray-900 dark:text-white truncate">
          {{ member.user?.name }}
        </h4>
        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
          @{{ member.user?.username }}
        </p>
        <p class="text-xs text-gray-400 mt-1">
          เข้าใช้งานล่าสุด: {{ formatDate(member.last_activity) }}
        </p>
      </div>
      
      <!-- Overall Score -->
      <div class="text-right">
        <div class="text-2xl font-bold" :class="getProgressTextColor(overallProgress)">
          {{ overallProgress }}%
        </div>
        <p class="text-xs text-gray-400">ความคืบหน้า</p>
      </div>
    </div>
    
    <!-- Progress Bars -->
    <div class="mt-4 space-y-3">
      <!-- Lessons -->
      <div>
        <div class="flex items-center justify-between mb-1">
          <span class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
            <Icon icon="fluent:book-24-regular" class="w-4 h-4" />
            บทเรียน
          </span>
          <span class="text-sm font-medium" :class="getProgressTextColor(member.lessons_progress || 0)">
            {{ member.lessons_completed || 0 }}/{{ member.total_lessons || 0 }}
          </span>
        </div>
        <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
          <div 
            class="h-full rounded-full transition-all duration-500"
            :class="getProgressColor(member.lessons_progress || 0)"
            :style="{ width: `${member.lessons_progress || 0}%` }"
          ></div>
        </div>
      </div>
      
      <!-- Assignments -->
      <div>
        <div class="flex items-center justify-between mb-1">
          <span class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
            <Icon icon="fluent:document-text-24-regular" class="w-4 h-4" />
            งานที่มอบหมาย
          </span>
          <span class="text-sm font-medium" :class="getProgressTextColor(member.assignments_progress || 0)">
            {{ member.assignments_completed || 0 }}/{{ member.total_assignments || 0 }}
          </span>
        </div>
        <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
          <div 
            class="h-full rounded-full transition-all duration-500"
            :class="getProgressColor(member.assignments_progress || 0)"
            :style="{ width: `${member.assignments_progress || 0}%` }"
          ></div>
        </div>
      </div>
      
      <!-- Quizzes -->
      <div>
        <div class="flex items-center justify-between mb-1">
          <span class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
            <Icon icon="fluent:quiz-new-24-regular" class="w-4 h-4" />
            แบบทดสอบ
          </span>
          <span class="text-sm font-medium" :class="getProgressTextColor(member.quizzes_progress || 0)">
            {{ member.quizzes_completed || 0 }}/{{ member.total_quizzes || 0 }}
          </span>
        </div>
        <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
          <div 
            class="h-full rounded-full transition-all duration-500"
            :class="getProgressColor(member.quizzes_progress || 0)"
            :style="{ width: `${member.quizzes_progress || 0}%` }"
          ></div>
        </div>
      </div>
    </div>
    
    <!-- Stats Row -->
    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 grid grid-cols-3 gap-2 text-center">
      <div>
        <div class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ member.attendance_rate || 0 }}%
        </div>
        <p class="text-xs text-gray-400">การเข้าเรียน</p>
      </div>
      <div>
        <div class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ member.avg_score || 0 }}
        </div>
        <p class="text-xs text-gray-400">คะแนนเฉลี่ย</p>
      </div>
      <div>
        <div class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ member.total_time_spent || '0h' }}
        </div>
        <p class="text-xs text-gray-400">เวลาเรียน</p>
      </div>
    </div>
  </div>
</template>
