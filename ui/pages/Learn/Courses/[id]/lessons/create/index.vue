<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { Icon } from '@iconify/vue'
import LessonForm from '@/components/learn/course/lesson/LessonForm.vue'

// Get course data from store
const courseStore = useCourseStore()
const { currentCourse: course, isCourseAdmin } = storeToRefs(courseStore)

const route = useRoute()
const router = useRouter()

// Get course ID
const courseId = route.params.id as string

// Ensure course is loaded
onMounted(async () => {
  if (!course.value || course.value.id != courseId) {
    try {
      await courseStore.fetchCourse(courseId)
    } catch (err) {
      console.error('Failed to fetch course:', err)
      router.push('/Learn/Courses')
    }
  }
})

// Handle form submit
const handleSubmit = (response: any) => {
  // Navigate back to lessons list
  router.push(`/courses/${courseId}/lessons`)
}

// Handle cancel
const handleCancel = () => {
  router.back()
}

// Course name for display
const courseName = computed(() => course?.value?.name || 'รายวิชา')

// Set page title
useHead({
  title: `สร้างบทเรียนใหม่ - ${courseName.value}`,
})
</script>

<template>
  <div class="max-w-4xl mx-auto py-8 px-4">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm mb-6">
      <NuxtLink
        :to="`/courses/${courseId}`"
        class="text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
      >
        {{ courseName }}
      </NuxtLink>
      <Icon icon="fluent:chevron-right-16-regular" class="w-4 h-4 text-gray-400" />
      <NuxtLink
        :to="`/courses/${courseId}/lessons`"
        class="text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
      >
        บทเรียน
      </NuxtLink>
      <Icon icon="fluent:chevron-right-16-regular" class="w-4 h-4 text-gray-400" />
      <span class="text-gray-900 dark:text-white font-medium">สร้างบทเรียนใหม่</span>
    </nav>

    <!-- Debug: Should see 'test' below if template renders -->
    <div style="color:red;font-weight:bold;">test</div>
    <!-- Lesson Form - Always show, backend will validate permission -->
    <LessonForm
      :course-id="courseId"
      :is-edit="false"
      @submit="handleSubmit"
      @cancel="handleCancel"
    />
  </div>
</template>
