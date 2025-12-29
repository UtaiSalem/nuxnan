<script setup lang="ts">
import { Icon } from '@iconify/vue'

// Inject course data from parent
const course = inject<Ref<any>>('course')
const courseMemberOfAuth = inject<Ref<any>>('courseMemberOfAuth')

// State
const myProgress = ref<any>(null)
const isLoading = ref(true)

const api = useApi()

// Fetch my progress
const fetchMyProgress = async () => {
  if (!course?.value?.id || !courseMemberOfAuth?.value?.id) return
  
  isLoading.value = true
  try {
    const response = await api.get(`/api/courses/${course.value.id}/members/${courseMemberOfAuth.value.id}/progress`)
    if (response.success) {
      myProgress.value = response.progress || response.data || {}
    }
  } catch (err) {
    console.error('Failed to fetch my progress:', err)
  } finally {
    isLoading.value = false
  }
}

// Get progress color
const getProgressColor = (percent: number) => {
  if (percent >= 80) return 'text-green-500'
  if (percent >= 50) return 'text-yellow-500'
  if (percent >= 20) return 'text-orange-500'
  return 'text-red-500'
}

// Get progress bar color
const getProgressBarColor = (percent: number) => {
  if (percent >= 80) return 'bg-green-500'
  if (percent >= 50) return 'bg-yellow-500'
  if (percent >= 20) return 'bg-orange-500'
  return 'bg-red-500'
}

// Watch for course data
watch(() => [course?.value?.id, courseMemberOfAuth?.value?.id], ([courseId, memberId]) => {
  if (courseId && memberId) fetchMyProgress()
}, { immediate: true })
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
      <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
        <Icon icon="mdi:graph-box-plus-outline" class="w-5 h-5 text-cyan-500" />
        ผลการเรียนของฉัน
      </h2>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div v-for="i in 3" :key="i" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 animate-pulse">
        <div class="h-16 bg-gray-200 dark:bg-gray-700 rounded mb-4"></div>
        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
      </div>
    </div>

    <template v-else>
      <!-- Overall Progress -->
      <div class="bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between mb-4">
          <div>
            <p class="text-white/80 text-sm">ความก้าวหน้าโดยรวม</p>
            <p class="text-4xl font-bold">{{ myProgress?.progress_percent || 0 }}%</p>
          </div>
          <div class="w-24 h-24 relative">
            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="45" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="10"/>
              <circle 
                cx="50" cy="50" r="45" fill="none" stroke="white" stroke-width="10"
                :stroke-dasharray="`${(myProgress?.progress_percent || 0) * 2.83} 283`"
                stroke-linecap="round"
              />
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
              <Icon icon="fluent:trophy-24-filled" class="w-8 h-8" />
            </div>
          </div>
        </div>
        <div class="w-full h-2 bg-white/20 rounded-full overflow-hidden">
          <div
            class="h-full bg-white rounded-full transition-all duration-500"
            :style="{ width: `${myProgress?.progress_percent || 0}%` }"
          ></div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Lessons -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
              <Icon icon="fluent:book-24-regular" class="w-6 h-6 text-blue-500" />
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">บทเรียน</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ myProgress?.completed_lessons || 0 }}/{{ myProgress?.total_lessons || 0 }}
              </p>
            </div>
          </div>
          <div class="mt-4 w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
            <div
              class="h-full bg-blue-500 rounded-full transition-all duration-500"
              :style="{ width: `${myProgress?.total_lessons ? (myProgress.completed_lessons / myProgress.total_lessons * 100) : 0}%` }"
            ></div>
          </div>
        </div>

        <!-- Assignments -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
              <Icon icon="material-symbols:assignment-outline" class="w-6 h-6 text-orange-500" />
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">ภาระงาน</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ myProgress?.completed_assignments || 0 }}/{{ myProgress?.total_assignments || 0 }}
              </p>
            </div>
          </div>
          <div class="mt-4 w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
            <div
              class="h-full bg-orange-500 rounded-full transition-all duration-500"
              :style="{ width: `${myProgress?.total_assignments ? (myProgress.completed_assignments / myProgress.total_assignments * 100) : 0}%` }"
            ></div>
          </div>
        </div>

        <!-- Quizzes -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
              <Icon icon="healthicons:i-exam-qualification-outline" class="w-6 h-6 text-purple-500" />
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">แบบทดสอบ</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ myProgress?.completed_quizzes || 0 }}/{{ myProgress?.total_quizzes || 0 }}
              </p>
            </div>
          </div>
          <div class="mt-4 w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
            <div
              class="h-full bg-purple-500 rounded-full transition-all duration-500"
              :style="{ width: `${myProgress?.total_quizzes ? (myProgress.completed_quizzes / myProgress.total_quizzes * 100) : 0}%` }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Total Score -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">คะแนนรวม</p>
            <p class="text-4xl font-bold" :class="getProgressColor(myProgress?.progress_percent || 0)">
              {{ myProgress?.total_score || 0 }}
            </p>
          </div>
          <div class="flex items-center gap-6 text-sm text-gray-600 dark:text-gray-400">
            <div class="text-center">
              <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ myProgress?.attendance_score || 0 }}</p>
              <p>คะแนนเข้าเรียน</p>
            </div>
            <div class="text-center">
              <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ myProgress?.assignment_score || 0 }}</p>
              <p>คะแนนภาระงาน</p>
            </div>
            <div class="text-center">
              <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ myProgress?.quiz_score || 0 }}</p>
              <p>คะแนนแบบทดสอบ</p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
