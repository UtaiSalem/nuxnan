<script setup lang="ts">
import { ref, computed, onMounted, inject, watch } from 'vue'
import type { Ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import { useApi } from '~/composables/useApi'
import { useSweetAlert } from '~/composables/useSweetAlert'
import ContentLoader from '~/components/accessories/ContentLoader.vue'
import LessonPost from '~/components/learn/course/lesson/LessonPost.vue'
import LessonOrderWidget from '~/components/learn/course/lesson/LessonOrderWidget.vue'

const route = useRoute()
const router = useRouter()
const api = useApi()
const swal = useSweetAlert()

const courseStore = useCourseStore()
const course = inject<Ref<any>>('course')
const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin') as Ref<boolean>

const lessons = computed(() => courseStore.lessons)
const isLoading = ref(true)
const isDeleting = ref(false)
const error = ref<string | null>(null)

const isRoot = computed(() => {
  return /\/lessons\/?$/.test(route.path)
})

const fetchLessons = async (silent = false) => {
    if (!course?.value?.id) return
    if (!silent) isLoading.value = true
    error.value = null
    try {
        await courseStore.fetchLessons(course.value.id, true)
    } catch (err: any) {
        console.error('Error fetching lessons:', err)
        if (!silent) error.value = err.message || 'ไม่สามารถโหลดบทเรียนได้'
    } finally {
        isLoading.value = false
    }
}

const handleCreateLesson = () => {
    router.push(`/Learn/Courses/${course.value.id}/lessons/create`)
}

const handleEditLesson = (lesson: any) => {
    router.push(`/Learn/Courses/${course.value.id}/lessons/${lesson.id}/edit`)
}

const handleDeleteLesson = async (id: number) => {
    const lesson = lessons.value.find(l => l.id === id)
    const lessonTitle = lesson?.title || 'บทเรียนนี้'
    
    const result = await swal.confirm(
        `คุณแน่ใจหรือไม่ที่จะลบ "${lessonTitle}"?\n\nการกระทำนี้จะลบข้อมูลทั้งหมดที่เกี่ยวข้อง รวมถึง:\n• หัวข้อย่อยทั้งหมด\n• แบบฝึกหัดและคำถาม\n• ความคิดเห็น\n• ความคืบหน้าผู้เรียน\n\nการกระทำนี้ไม่สามารถย้อนกลับได้`,
        'ยืนยันการลบบทเรียน'
    )
    
    if (!result) return
    
    isDeleting.value = true
    try {
        const response = await api.delete(`/api/courses/${course.value.id}/lessons/${id}`) as any
        courseStore.invalidateCourse()
        swal.success(response.message || 'ลบบทเรียนสำเร็จ', 'สำเร็จ')
        await fetchLessons()
    } catch (err: any) {
        console.error('Error deleting lesson', err)
        swal.error(err.data?.message || err.message || 'เกิดข้อผิดพลาดในการลบบทเรียน')
    } finally {
        isDeleting.value = false
    }
}

const handleLikeLesson = async (id: number) => {
  await fetchLessons() 
}

const handleDislikeLesson = async (id: number) => {
  await fetchLessons()
}

const handleBookmarkLesson = async (id: number) => {
    // Stub
}

const handleShareLesson = (lesson: any) => {
    // Stub
}

const handleCommentLesson = (lesson: any) => {
    router.push(`/Learn/Courses/${course.value.id}/lessons/${lesson.id}#comments`)
}

// Handle topic created - add to lesson topics immediately
const handleTopicCreated = (lessonId: number, newTopic: any) => {
  const lesson = lessons.value.find(l => l.id === lessonId)
  if (lesson) {
    if (lesson.topics) {
      lesson.topics.push(newTopic)
    } else {
      lesson.topics = [newTopic]
    }
  }
}

// Handle topic updated - update in lesson topics immediately
const handleTopicUpdated = (lessonId: number, updatedTopic: any) => {
  const lesson = lessons.value.find(l => l.id === lessonId)
  if (lesson && lesson.topics) {
    const index = lesson.topics.findIndex((t: any) => t.id === updatedTopic.id)
    if (index !== -1) {
      lesson.topics[index] = updatedTopic
    }
  }
}

// Handle topic deleted - remove from lesson topics immediately
const handleTopicDeleted = (lessonId: number, topicId: number) => {
  const lesson = lessons.value.find(l => l.id === lessonId)
  if (lesson && lesson.topics) {
    lesson.topics = lesson.topics.filter((t: any) => t.id !== topicId)
  }
}

onMounted(() => {
  fetchLessons()
})

// Watch for refresh signal from create/edit pages
watch(() => route.query.refresh, async (newVal) => {
  if (newVal) {
    await fetchLessons()
    // Clean up the query param
    router.replace({ path: route.path, query: {} })
  }
})

// Re-fetch when returning to this page (handles back navigation)
watch(isRoot, async (newVal) => {
  if (newVal) {
    await fetchLessons()
  }
})
</script>

<template>
  <div>
    <!-- Child Route Content -->
    <NuxtPage />

    <!-- Main Lessons List (only show when not on child route) -->
    <template v-if="isRoot">
      <ContentLoader v-if="isLoading" />
      
      <!-- Error State -->
      <div
        v-else-if="error"
        class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4 sm:p-8 text-center max-w-md mx-auto"
      >
        <Icon icon="fluent:error-circle-24-regular" class="w-16 h-16 text-red-500 mx-auto mb-4" />
        <h3 class="text-xl font-bold text-red-700 dark:text-red-400 mb-2">เกิดข้อผิดพลาด</h3>
        <p class="text-red-600 dark:text-red-400 mb-4">{{ error }}</p>
        <button
          @click="fetchLessons"
          class="min-h-[44px] sm:min-h-0 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          ลองใหม่
        </button>
      </div>

      <!-- Content -->
      <template v-else>
        <!-- Header with Create Button (Admin Only) -->
        <div
          v-if="isCourseAdmin"
          class="bg-gradient-to-r from-blue-600 via-cyan-600 to-purple-600 dark:from-blue-800 dark:via-cyan-800 dark:to-purple-800 rounded-2xl p-4 sm:p-6 shadow-xl mb-6"
        >
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-center gap-3 sm:gap-4">
              <div
                class="w-12 h-12 sm:w-14 sm:h-14 flex-shrink-0 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg"
              >
                <Icon icon="fluent:book-24-filled" class="w-6 h-6 sm:w-7 sm:h-7 text-white" />
              </div>
              <div class="min-w-0">
                <h2 class="text-xl sm:text-2xl font-bold text-white mb-1 break-words">บทเรียนทั้งหมด</h2>
                <p class="text-white/80 text-sm">{{ lessons.length }} บทเรียน</p>
              </div>
            </div>
            <button
              @click="handleCreateLesson"
              class="w-full sm:w-auto min-h-[44px] flex flex-shrink-0 items-center justify-center gap-2 px-4 sm:px-5 py-3 bg-white text-blue-600 rounded-xl hover:bg-blue-50 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 font-bold whitespace-nowrap"
            >
              <Icon icon="fluent:add-circle-24-filled" class="w-5 h-5 flex-shrink-0" />
              <span>เพิ่มบทเรียน</span>
            </button>
          </div>

          <!-- Lesson Order Widget (Admin only) -->
          <div v-if="lessons.length > 1" class="mt-6">
            <LessonOrderWidget
              :lessons="lessons"
              :course-id="course.id"
              @saved="() => fetchLessons(true)"
            />
          </div>
        </div>

        <!-- Empty State -->
        <div
          v-if="!lessons.length"
          class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 sm:p-12 text-center"
        >
          <Icon
            icon="fluent:book-24-regular"
            class="w-16 h-16 sm:w-24 sm:h-24 text-gray-300 dark:text-gray-600 mx-auto mb-4"
          />
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
            ยังไม่มีบทเรียนในรายวิชานี้
          </h3>
          <p class="text-gray-600 dark:text-gray-400">
            {{ isCourseAdmin ? 'เริ่มสร้างบทเรียนแรกของคุณ' : 'อาจารย์กำลังเตรียมบทเรียนอยู่' }}
          </p>
        </div>

        <!-- Lessons Feed (แสดงทีละบทแบบโพสต์ - Static list) -->
        <div class="space-y-4">
          <div v-for="(lesson, index) in lessons" :key="lesson.id">
            <LessonPost
              :lesson="lesson"
              :is-admin="isCourseAdmin"
              :current-index="index"
              :total-lessons="lessons.length"
              @edit="handleEditLesson"
              @delete="handleDeleteLesson"
              @like="handleLikeLesson"
              @dislike="handleDislikeLesson"
              @bookmark="handleBookmarkLesson"
              @share="handleShareLesson"
              @comment="handleCommentLesson"
              @topic-created="(topic) => handleTopicCreated(lesson.id, topic)"
              @topic-updated="(topic) => handleTopicUpdated(lesson.id, topic)"
              @topic-deleted="(topicId) => handleTopicDeleted(lesson.id, topicId)"
            />
          </div>
        </div>
      </template>
    </template>
    
  </div>
</template>
