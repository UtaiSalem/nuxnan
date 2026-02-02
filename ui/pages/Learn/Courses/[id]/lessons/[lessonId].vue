<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import LessonPost from '~/components/learn/course/lesson/LessonPost.vue'

// Use course store directly instead of inject
const courseStore = useCourseStore()
const course = computed(() => courseStore.currentCourse)
const isCourseAdmin = computed(() => courseStore.isCourseAdmin)

const route = useRoute()
const router = useRouter()
const api = useApi()
const swal = useSweetAlert()

// Get IDs from route
const courseId = computed(() => route.params.id as string)
const lessonId = computed(() => route.params.lessonId as string)

// Check if we're on a child route (e.g., /edit)
const isChildRoute = computed(() => {
  const path = route.path.toLowerCase()
  const lessonPath = `/learn/courses/${courseId.value}/lessons/${lessonId.value}`.toLowerCase()
  return path !== lessonPath && path.startsWith(lessonPath + '/')
})

// Lesson state
const lesson = ref<any>(null)
const allLessons = ref<any[]>([])
const isLoading = ref(true)
const error = ref<string | null>(null)

// Navigation computed properties
const currentLessonIndex = computed(() => {
  if (!lesson.value || allLessons.value.length === 0) return -1
  return allLessons.value.findIndex(l => l.id === lesson.value.id)
})

const prevLesson = computed(() => {
  if (currentLessonIndex.value <= 0) return null
  return allLessons.value[currentLessonIndex.value - 1]
})

const nextLesson = computed(() => {
  if (currentLessonIndex.value < 0 || currentLessonIndex.value >= allLessons.value.length - 1) return null
  return allLessons.value[currentLessonIndex.value + 1]
})

const totalLessons = computed(() => allLessons.value.length)

// Fetch course if not loaded
const ensureCourseLoaded = async () => {
  if (!course.value || course.value.id != courseId.value) {
    try {
      await courseStore.fetchCourse(courseId.value)
    } catch (err) {
      console.error('Failed to fetch course:', err)
    }
  }
}

// Fetch all lessons for navigation
const fetchAllLessons = async () => {
  try {
    const response = await api.get(`/api/courses/${courseId.value}/lessons`) as any
    if (response.lessons) {
      // Sort by order
      allLessons.value = response.lessons.sort((a: any, b: any) => (a.order || 0) - (b.order || 0))
    }
  } catch (err) {
    console.error('Failed to fetch lessons list:', err)
  }
}

// Fetch lesson data
const fetchLesson = async () => {
  if (!courseId.value || !lessonId.value) return

  isLoading.value = true
  error.value = null

  try {
    const response = await api.get(`/api/courses/${courseId.value}/lessons/${lessonId.value}`) as any

    if (response.success !== false) {
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

// Handler functions
const handleEdit = (lessonData: any) => {
  router.push(`/courses/${courseId.value}/lessons/${lessonData.id}/edit`)
}

const handleDelete = async (lessonIdToDelete: number) => {
  const result = await swal.confirm('คุณแน่ใจหรือไม่ที่จะลบบทเรียนนี้? การกระทำนี้ไม่สามารถย้อนกลับได้', 'ลบบทเรียน')
  
  if (result) {
    try {
      await api.delete(`/api/lessons/${lessonIdToDelete}`)
      swal.success('บทเรียนถูกลบแล้ว', 'สำเร็จ')
      router.push(`/courses/${courseId.value}/lessons`)
    } catch (err: any) {
      console.error('Failed to delete lesson:', err)
      swal.error(err.data?.message || 'ไม่สามารถลบบทเรียนได้')
    }
  }
}

const handleLike = async (id: number) => {
  try {
    await api.post(`/api/courses/${courseId.value}/lessons/${id}/like`)
  } catch (err) {
    console.error('Failed to like lesson:', err)
  }
}

const handleDislike = async (id: number) => {
  try {
    await api.post(`/api/courses/${courseId.value}/lessons/${id}/dislike`)
  } catch (err) {
    console.error('Failed to dislike lesson:', err)
  }
}

const handleBookmark = async (id: number) => {
  try {
    const response = await api.post(`/api/courses/${courseId.value}/lessons/${id}/bookmark`) as any
    if (response.bookmarked) {
      swal.toast('บันทึกบทเรียนแล้ว', 'success')
    } else {
      swal.toast('ยกเลิกการบันทึกแล้ว', 'info')
    }
  } catch (err) {
    console.error('Failed to bookmark lesson:', err)
  }
}

const handleShare = (lessonData: any) => {
  // Copy lesson URL to clipboard
  const url = `${window.location.origin}/courses/${courseId.value}/lessons/${lessonData.id}`
  navigator.clipboard.writeText(url)
  swal.toast('คัดลอกลิงก์แล้ว', 'success')
}

// Load data on mount
onMounted(async () => {
  await ensureCourseLoaded()
  await Promise.all([
    fetchLesson(),
    fetchAllLessons()
  ])
})

// Reload lesson when route changes (navigation)
watch(lessonId, async () => {
  await fetchLesson()
})

// Set page title
watch(lesson, (newLesson) => {
  if (newLesson?.title) {
    useHead({
      title: `${newLesson.title} - ${course.value?.name || 'บทเรียน'}`,
    })
  }
})
</script>

<template>
  <div>
    <!-- Child Route Content (edit) -->
    <NuxtPage v-if="isChildRoute" />

    <!-- Main Lesson View (only show when not on child route) -->
    <template v-else>
      <!-- Loading State -->
      <div v-if="isLoading" class="flex justify-center items-center min-h-[50vh]">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p class="text-gray-500 dark:text-gray-400">กำลังโหลดบทเรียน...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="flex justify-center items-center min-h-[50vh]">
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

      <!-- Lesson Content using LessonPost -->
      <div v-else-if="lesson" class="max-w-4xl mx-auto px-4">
        <LessonPost 
          :lesson="lesson" 
          :isAdmin="isCourseAdmin"
          :prev-lesson="prevLesson"
          :next-lesson="nextLesson"
          :current-index="currentLessonIndex"
          :total-lessons="totalLessons"
          @edit="handleEdit"
          @delete="handleDelete"
          @like="handleLike"
          @dislike="handleDislike"
          @bookmark="handleBookmark"
          @share="handleShare"
        />
      </div>
    </template>
  </div>
</template>
