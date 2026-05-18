<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, inject, watch } from 'vue'
import type { Ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import { useApi } from '~/composables/useApi'
import { useSweetAlert } from '~/composables/useSweetAlert'
import ContentLoader from '~/components/accessories/ContentLoader.vue'
import LessonPost from '~/components/learn/course/lesson/LessonPost.vue'

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
const showScrollButton = ref(false)

const isRoot = computed(() => {
  return /\/lessons\/?$/.test(route.path)
})

const fetchLessons = async () => {
    if (!course.value?.id) return
    isLoading.value = true
    error.value = null
    try {
        await courseStore.fetchLessons(course.value.id, true)
    } catch (err: any) {
        console.error('Error fetching lessons:', err)
        error.value = err.message || 'ไม่สามารถโหลดบทเรียนได้'
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

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleScroll = () => {
  showScrollButton.value = window.scrollY > 300
}

onMounted(() => {
  fetchLessons()
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
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
        <!-- Header with Create Button (Admin Only) -->
        <div
          v-if="isCourseAdmin"
          class="bg-gradient-to-r from-blue-600 via-cyan-600 to-purple-600 dark:from-blue-800 dark:via-cyan-800 dark:to-purple-800 rounded-2xl p-6 shadow-xl mb-6"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
              <div
                class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg"
              >
                <Icon icon="fluent:book-24-filled" class="w-7 h-7 text-white" />
              </div>
              <div>
                <h2 class="text-2xl font-bold text-white mb-1">บทเรียนทั้งหมด</h2>
                <p class="text-white/80 text-sm">{{ lessons.length }} บทเรียน</p>
              </div>
            </div>
            <button
              @click="handleCreateLesson"
              class="flex items-center gap-2 px-5 py-3 bg-white text-blue-600 rounded-xl hover:bg-blue-50 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 font-bold"
            >
              <Icon icon="fluent:add-circle-24-filled" class="w-5 h-5" />
              <span>เพิ่มบทเรียน</span>
            </button>
          </div>
        </div>

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
            {{ isCourseAdmin ? 'เริ่มสร้างบทเรียนแรกของคุณ' : 'อาจารย์กำลังเตรียมบทเรียนอยู่' }}
          </p>
        </div>

        <!-- Lessons Feed (แสดงทีละบทแบบโพสต์) -->
        <div v-for="lesson in lessons" :key="lesson.id">
          <LessonPost
            :lesson="lesson"
            :is-admin="isCourseAdmin"
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
      </template>
    </template>
    
    <!-- Scroll to Top Button -->
    <transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 translate-y-10"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-300"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-10"
    >
      <button 
        v-if="showScrollButton"
        @click="scrollToTop"
        class="fixed bottom-8 right-8 z-[999] p-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-full shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl hover:-translate-y-1 transition-all"
        title="เลื่อนขึ้นด้านบน"
      >
        <Icon icon="fluent:arrow-up-24-filled" class="w-6 h-6" />
      </button>
    </transition>
  </div>
</template>
