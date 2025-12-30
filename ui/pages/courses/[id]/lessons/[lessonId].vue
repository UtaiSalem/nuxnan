<script setup lang="ts">
import { ref, computed, watch, inject } from 'vue'
import type { Ref } from 'vue'
import { Icon } from '@iconify/vue'
import SingleCourseLesson from '~/PlearndComponents/learn/courses/lessons/SingleCourseLesson.vue'

// Inject data from parent course page
const course = inject('course') as Ref<any>
const isCourseAdmin = inject('isCourseAdmin') as Ref<boolean>
const courseMemberOfAuth = inject('courseMemberOfAuth') as Ref<any>

const route = useRoute()
const api = useApi()

// Lesson state
const lesson = ref<any>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)

// Get lesson ID from route
const lessonId = computed(() => route.params.lessonId as string)

// Fetch lesson data
const fetchLesson = async () => {
  if (!course.value?.id || !lessonId.value) return

  isLoading.value = true
  error.value = null

  try {
    const response = await api.get(`/api/courses/${course.value.id}/lessons/${lessonId.value}`)

    if (response.success) {
      lesson.value = response.lesson || response.data || response
    } else {
      error.value = 'ไม่สามารถโหลดข้อมูลบทเรียนได้'
    }
  } catch (err: any) {
    console.error('Failed to fetch lesson:', err)
    error.value = err.data?.message || 'เกิดข้อผิดพลาดในการโหลดบทเรียน'
  } finally {
    isLoading.value = false
  }
}

// Watch for course changes
watch(() => course.value, (newCourse) => {
  if (newCourse?.id) {
    fetchLesson()
  }
}, { immediate: true })

// Set page title
watch(lesson, (newLesson) => {
  if (newLesson?.title) {
    useHead({
      title: `${newLesson.title} - ${course.value?.name || 'บทเรียน'}`
    })
  }
})
</script>

<template>
  <div>
    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center items-center min-h-screen">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex justify-center items-center min-h-screen">
      <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-8 text-center max-w-md">
        <Icon icon="fluent:error-circle-24-regular" class="w-16 h-16 text-red-500 mx-auto mb-4" />
        <h3 class="text-xl font-bold text-red-700 dark:text-red-400 mb-2">เกิดข้อผิดพลาด</h3>
        <p class="text-red-600 dark:text-red-400 mb-4">{{ error }}</p>
        <button
          @click="fetchLesson"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          ลองใหม่
        </button>
      </div>
    </div>

    <!-- Lesson Content -->
    <SingleCourseLesson
      v-else-if="lesson"
      :lesson="lesson"
      :is-course-admin="isCourseAdmin?.value"
    />
  </div>
</template>