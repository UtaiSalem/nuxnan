<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import LessonForm from '~/components/learn/course/lesson/LessonForm.vue'

// Use course store directly instead of inject
const courseStore = useCourseStore()
const course = computed(() => courseStore.currentCourse)
const isCourseAdmin = computed(() => courseStore.isCourseAdmin)

const route = useRoute()
const router = useRouter()
const api = useApi()

definePageMeta({
  middleware: ['auth', async (to) => {
      const courseStore = useCourseStore()
      if (!courseStore.currentCourse || courseStore.currentCourse.id != to.params.id) {
          try {
             await courseStore.fetchCourse(to.params.id as string)
          } catch (e) {
             console.error('Middleware fetch course error', e)
             return abortNavigation('Course not found')
          }
      }
      
      if (!courseStore.isCourseAdmin) {
          return navigateTo(`/Learn/Courses/${to.params.id}`)
      }
  }]
})

// Get IDs from route
const courseId = computed(() => route.params.id as string)
const lessonId = computed(() => route.params.lessonId as string)

// State
const lesson = ref<any>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)

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

// Fetch lesson data
const fetchLesson = async () => {
  if (!courseId.value || !lessonId.value) return

  isLoading.value = true
  error.value = null

  try {
    const response = await api.get(`/api/courses/${courseId.value}/lessons/${lessonId.value}`)
    lesson.value = response.lesson || response.data || response
  } catch (err: any) {
    console.error('Failed to fetch lesson:', err)
    error.value = err.data?.message || 'ไม่สามารถโหลดข้อมูลบทเรียนได้'
  } finally {
    isLoading.value = false
  }
}

// Load data on mount
onMounted(async () => {
  await ensureCourseLoaded()
  await fetchLesson()
})

// Handle form submit
const handleSubmit = (response: any) => {
  // Navigate back to lesson detail with refresh flag
  // Use replace to prevent back button issues and add timestamp to force refresh
  router.replace({
    path: `/Learn/Courses/${courseId.value}/lessons/${lessonId.value}`,
    query: { updated: Date.now().toString() }
  })
}

// Handle cancel
const handleCancel = () => {
  router.back()
}

// Set page title
watch(lesson, (newLesson) => {
  if (newLesson?.title) {
    useHead({
      title: `แก้ไข: ${newLesson.title} - ${course.value?.name || 'รายวิชา'}`,
    })
  }
})
</script>

<template>
  <div class="max-w-4xl mx-auto px-3 sm:px-4 lg:px-6 pb-32 sm:pb-12 space-y-4 sm:space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm mb-6">
      <NuxtLink
        :to="`/Learn/Courses/${courseId}`"
        class="text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
      >
        {{ course?.name || 'รายวิชา' }}
      </NuxtLink>
      <Icon icon="fluent:chevron-right-16-regular" class="w-4 h-4 text-gray-400" />
      <NuxtLink
        :to="`/Learn/Courses/${courseId}/lessons`"
        class="text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
      >
        บทเรียน
      </NuxtLink>
      <Icon icon="fluent:chevron-right-16-regular" class="w-4 h-4 text-gray-400" />
      <NuxtLink
        v-if="lesson"
        :to="`/Learn/Courses/${courseId}/lessons/${lessonId}`"
        class="text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
      >
        {{ lesson.title }}
      </NuxtLink>
      <Icon icon="fluent:chevron-right-16-regular" class="w-4 h-4 text-gray-400" />
      <span class="text-gray-900 dark:text-white font-medium">แก้ไข</span>
    </nav>

    <!-- Loading -->
    <div v-if="isLoading" class="flex justify-center items-center py-16">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 rounded-xl p-8 text-center">
      <Icon icon="fluent:error-circle-24-regular" class="w-16 h-16 text-red-500 mx-auto mb-4" />
      <h3 class="text-xl font-bold text-red-700 dark:text-red-400 mb-2">เกิดข้อผิดพลาด</h3>
      <p class="text-red-600 dark:text-red-400 mb-4">{{ error }}</p>
      <button
        @click="fetchLesson"
        class="min-h-[44px] sm:min-h-0 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        ลองใหม่
      </button>
    </div>

    <!-- Permission Check -->
    <div v-else-if="!isCourseAdmin" class="bg-red-50 dark:bg-red-900/20 rounded-xl p-8 text-center">
      <Icon icon="fluent:shield-error-24-regular" class="w-16 h-16 text-red-500 mx-auto mb-4" />
      <h3 class="text-xl font-bold text-red-700 dark:text-red-400 mb-2">ไม่มีสิทธิ์เข้าถึง</h3>
      <p class="text-red-600 dark:text-red-400 mb-4">คุณไม่มีสิทธิ์ในการแก้ไขบทเรียน</p>
      <NuxtLink
        :to="`/Learn/Courses/${courseId}/lessons`"
        class="inline-flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
        กลับไปหน้าบทเรียน
      </NuxtLink>
    </div>

    <!-- Lesson Form -->
    <LessonForm
      v-else-if="lesson"
      :course-id="courseId"
      :lesson="lesson"
      :is-edit="true"
      @submit="handleSubmit"
      @cancel="handleCancel"
    />
  </div>
</template>
