<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseFeedsList from '~/components/learn/course/CourseFeedsList.vue'

definePageMeta({
  layout: false,
})

// Props from parent route
const props = defineProps<{
  course?: any
  academy?: any
  isCourseAdmin?: boolean
}>()

// Use avatar composable
const { getAvatarUrl } = useAvatar()

// Inject from parent if props not passed
const injectedCourse = inject<Ref<any>>('course')
const injectedAcademy = inject<Ref<any>>('academy')
const injectedIsCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')
const courseMemberOfAuth = inject<Ref<any>>('courseMemberOfAuth')

const course = computed(() => props.course || injectedCourse?.value)
const academy = computed(() => props.academy || injectedAcademy?.value)
const isCourseAdmin = computed(() => props.isCourseAdmin || injectedIsCourseAdmin?.value)

// Course teacher avatar
const teacherAvatar = computed(() => course.value?.user ? getAvatarUrl(course.value.user) : null)

// Course stats
const courseStats = computed(() => {
  if (!course.value) return null
  return {
    members: course.value.members_count || course.value.course_members_count || 0,
    posts: course.value.posts_count || 0,
    materials: course.value.materials_count || 0,
    assignments: course.value.assignments_count || 0
  }
})

// Page title
useHead({
  title: computed(() => course.value?.name ? `ฟีด - ${course.value.name}` : 'ฟีดรายวิชา')
})
</script>

<template>
  <div class="space-y-6">
    <!-- Feeds List (Full Width of center column) -->
    <CourseFeedsList 
      v-if="course?.id"
      :course-id="course.id"
      :is-course-admin="isCourseAdmin"
    />
    
    <!-- Loading State if no course -->
    <div v-else class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center border border-gray-100 dark:border-vikinger-dark-100 shadow-sm">
      <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 animate-spin mx-auto text-blue-500 mb-4" />
      <p class="text-gray-500 dark:text-gray-400">กำลังโหลดข้อมูลรายวิชา...</p>
    </div>
  </div>
</template>

