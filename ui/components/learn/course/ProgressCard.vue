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
  // Use backend provided percentage if available (Accuracy prioritized)
  if (props.member.overall_progress !== undefined) {
    return props.member.overall_progress
  }

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

// Status Badge
const statusBadge = computed(() => {
  const p = overallProgress.value
  if (p >= 80) return { text: 'ดีเยี่ยม', color: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 border-green-200 dark:border-green-800' }
  if (p >= 50) return { text: 'ปกติ', color: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border-blue-200 dark:border-blue-800' }
  return { text: 'ต้องปรับปรุง', color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 border-red-200 dark:border-red-800' }
})

// สีพื้นหลัง avatar นุ่มนวลสบายตา (hex without #)
const getAvatarUrl = (user: any) => {
  if (user?.avatar) return user.avatar
  
  // Use user id or name to determine color index consistently
  const index = user?.id || user?.name?.length || 0
  
  const textColors = [
    'f59e0b', // amber-500
    '64748b', // slate-500
    'f97316', // orange-500
    '0ea5e9', // sky-500
    '10b981', // emerald-500
    '8b5cf6', // violet-500
    'f43f5e', // rose-500
    '14b8a6', // teal-500
    '6366f1', // indigo-500
    '06b6d4', // cyan-500
  ]
  const color = textColors[index % textColors.length]
  // Background: Soft White / AliceBlue-ish
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'User')}&background=eff6ff&color=${color}&font-size=0.4`
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
        :src="getAvatarUrl(member.user)"
        :alt="member.user?.name"
        class="w-12 h-12 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600"
      />
      
      <div class="flex-1 min-w-0">
        <h4 class="font-medium text-gray-900 dark:text-white truncate">
          {{ member.member_name || member.user?.name }}
        </h4>
        <p class="text-sm text-gray-500 dark:text-gray-400 truncate flex flex-wrap items-center gap-1">
          <!-- Order Number (No.) -->
          <span v-if="member.order_number" class="px-1.5 py-0.5 rounded bg-blue-100 dark:bg-blue-900/40 text-xs font-mono font-bold text-blue-700 dark:text-blue-300">
            No. {{ member.order_number }}
          </span>
          
          <!-- Member Code (ID) -->
          <span v-if="member.member_code" class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-xs font-mono font-medium text-gray-600 dark:text-gray-300">
            {{ member.member_code }}
          </span>

          <span v-if="!member.order_number && !member.member_code">@{{ member.user?.username }}</span>
        </p>
        <!-- Status Badge -->
        <div class="mt-1">
          <span :class="['inline-flex items-center px-2 py-0.5 rounded textxs font-medium border', statusBadge.color]">
            {{ statusBadge.text }}
          </span>
        </div>
        <p class="text-xs text-gray-400 mt-1">
          เข้าใช้งานล่าสุด: {{ formatDate(member.last_activity) }}
        </p>
      </div>
      
      <!-- Radial Progress with Grade -->
      <div class="relative w-16 h-16 flex-shrink-0">
        <!-- Background Circle -->
        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
          <circle
            cx="18"
            cy="18"
            r="15.5"
            fill="none"
            stroke="currentColor"
            stroke-width="3"
            class="text-gray-200 dark:text-gray-700"
          />
          <!-- Progress Circle -->
          <circle
            cx="18"
            cy="18"
            r="15.5"
            fill="none"
            stroke="currentColor"
            stroke-width="3"
            stroke-linecap="round"
            :class="getProgressTextColor(overallProgress)"
            :stroke-dasharray="`${overallProgress * 0.97} 100`"
          />
        </svg>
        <!-- Grade Text in Center -->
        <div class="absolute inset-0 flex flex-col items-center justify-center">
          <span class="text-sm font-bold" :class="getProgressTextColor(overallProgress)">
            {{ member.scores?.grade_name || '-' }}
          </span>
          <span class="text-[10px] text-gray-400">{{ overallProgress }}%</span>
        </div>
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
        <div class="text-lg font-semibold" :class="getProgressTextColor(member.attendance_rate || 0)">
          {{ member.attendance_rate || 0 }}%
        </div>
        <p class="text-xs text-gray-400">เข้าเรียน ({{ member.attendance_present || 0 }}/{{ member.total_attendance || 0 }})</p>
      </div>
      <div>
        <div class="text-lg font-semibold text-blue-600 dark:text-blue-400">
          {{ member.scores?.total_score || 0 }}
        </div>
        <p class="text-xs text-gray-400">คะแนนรวม</p>
      </div>
      <div>
        <div class="text-lg font-semibold" :class="getProgressTextColor(overallProgress)">
          {{ member.scores?.grade_name || '-' }}
        </div>
        <p class="text-xs text-gray-400">เกรด</p>
      </div>
    </div>
  </div>
</template>
