<script setup lang="ts">
import { ref, watch, inject } from 'vue'
import type { Ref } from 'vue'
import { Icon } from '@iconify/vue'

// Import modern LessonPost component instead of LessonsList
import LessonPost from '~/components/course/lesson/LessonPost.vue'
import CreateNewLesson from '@/PlearndComponents/learn/courses/lessons/CreateNewLesson.vue'

// Inject course data from parent
const course = inject('course') as Ref<any>
const isCourseAdmin = inject('isCourseAdmin') as Ref<boolean>

// State
const lessons = ref<any[]>([])
const isLoading = ref(true)
const error = ref<string | null>(null)

// API
const api = useApi()

// Fetch lessons
const fetchLessons = async () => {
  if (!course.value?.id) return

  isLoading.value = true
  error.value = null

  try {
    const response = await api.get(`/api/courses/${course.value.id}/lessons`)
    lessons.value = response.lessons || response.data || []
  } catch (err: any) {
    console.error('Failed to fetch lessons:', err)
    error.value = err.data?.message || 'ไม่สามารถโหลดบทเรียนได้'
  } finally {
    isLoading.value = false
  }
}

// Watch for course changes
watch(
  () => course.value,
  (newCourse) => {
    if (newCourse?.id) {
      fetchLessons()
    }
  },
  { immediate: true }
)

// Handler methods
const handleEditLesson = (lesson: any) => {
  navigateTo(`/courses/${course.value.id}/lessons/${lesson.id}/edit`)
}

const handleDeleteLesson = async (lessonId: number) => {
  if (!confirm('คุณแน่ใจหรือไม่ที่จะลบบทเรียนนี้?')) return

  try {
    await api.delete(`/api/courses/${course.value.id}/lessons/${lessonId}`)
    // Remove from list
    const index = lessons.value.findIndex((l) => l.id === lessonId)
    if (index > -1) {
      lessons.value.splice(index, 1)
    }
  } catch (err: any) {
    alert(err.data?.message || 'เกิดข้อผิดพลาดในการลบบทเรียน')
  }
}

const handleLikeLesson = async (lessonId: number) => {
  try {
    await api.post(`/api/courses/${course.value.id}/lessons/${lessonId}/like`)
    const lesson = lessons.value.find((l) => l.id === lessonId)
    if (lesson) {
      lesson.is_liked_by_auth = !lesson.is_liked_by_auth
      lesson.like_count = (lesson.like_count || 0) + (lesson.is_liked_by_auth ? 1 : -1)
    }
  } catch (err) {
    console.error('Error liking lesson:', err)
  }
}

const handleDislikeLesson = async (lessonId: number) => {
  try {
    await api.post(`/api/courses/${course.value.id}/lessons/${lessonId}/dislike`)
    const lesson = lessons.value.find((l) => l.id === lessonId)
    if (lesson) {
      lesson.is_disliked_by_auth = !lesson.is_disliked_by_auth
      lesson.dislike_count = (lesson.dislike_count || 0) + (lesson.is_disliked_by_auth ? 1 : -1)
    }
  } catch (err) {
    console.error('Error disliking lesson:', err)
  }
}

const handleBookmarkLesson = async (lessonId: number) => {
  try {
    await api.post(`/api/courses/${course.value.id}/lessons/${lessonId}/bookmark`)
    const lesson = lessons.value.find((l) => l.id === lessonId)
    if (lesson) {
      lesson.is_bookmarked_by_auth = !lesson.is_bookmarked_by_auth
    }
  } catch (err) {
    console.error('Error bookmarking lesson:', err)
  }
}

const handleShareLesson = (lesson: any) => {
  const url = window.location.origin + `/courses/${course.value.id}/lessons/${lesson.id}`
  navigator.clipboard.writeText(url)
  alert('คัดลอกลิงก์แล้ว!')
}

const handleCommentLesson = (lessonId: number) => {
  navigateTo(`/courses/${course.value.id}/lessons/${lessonId}#comments`)
}

const handleAddNewLesson = (newLesson: any) => {
  lessons.value.unshift(newLesson)
}
</script>

<template>
  <div class="space-y-6">
    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center items-center py-16">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <!-- Error State -->
    <div
      v-else-if="error"
      class="bg-red-50 dark:bg-red-900/20 rounded-xl p-8 text-center max-w-md mx-auto"
    >
      <Icon icon="fluent:error-circle-24-regular" class="w-16 h-16 text-red-500 mx-auto mb-4" />
      <h3 class="text-xl font-bold text-red-700 dark:text-red-400 mb-2">เกิดข้อผิดพลาด</h3>
      <p class="text-red-600 dark:text-red-400 mb-4">{{ error }}</p>
      <button
        @click="fetchLessons"
        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        ลองใหม่
      </button>
    </div>

    <!-- Content -->
    <template v-else>
      <!-- Create New Lesson (Admin Only) -->
      <CreateNewLesson v-if="isCourseAdmin?.value" @add-new-lesson="handleAddNewLesson" />

      <!-- Empty State -->
      <div
        v-if="!lessons.length"
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center"
      >
        <Icon
          icon="fluent:book-24-regular"
          class="w-24 h-24 text-gray-300 dark:text-gray-600 mx-auto mb-4"
        />
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
          ยังไม่มีบทเรียนในรายวิชานี้
        </h3>
        <p class="text-gray-600 dark:text-gray-400">
          {{
            isCourseAdmin?.value ? 'เริ่มสร้างบทเรียนแรกของคุณ' : 'อาจารย์กำลังเตรียมบทเรียนอยู่'
          }}
        </p>
      </div>

      <!-- Lessons Feed (แสดงทีละบทแบบโพสต์) -->
      <div v-for="lesson in lessons" :key="lesson.id">
        <LessonPost
          :lesson="lesson"
          :is-admin="isCourseAdmin?.value"
          @edit="handleEditLesson"
          @delete="handleDeleteLesson"
          @like="handleLikeLesson"
          @dislike="handleDislikeLesson"
          @bookmark="handleBookmarkLesson"
          @share="handleShareLesson"
          @comment="handleCommentLesson"
        />
      </div>
    </template>
  </div>
</template>
