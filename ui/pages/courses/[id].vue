<script setup lang="ts">
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: false, // We'll use NuxtLayout manually to pass slots
  middleware: 'auth'
})

const route = useRoute()
const api = useApi()
const courseStore = useCourseStore()
const courseGroupStore = useCourseGroupStore()

// State
const course = ref<any>(null)
const academy = ref<any>(null)
const isCourseAdmin = ref(false)
const courseMemberOfAuth = ref<any>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)

// Course ID from route
const courseId = computed(() => route.params.id as string)

// Fetch course details
const fetchCourse = async (forceRefresh = false) => {
  // ถ้ามี cache และไม่ force refresh ให้ใช้จาก store
  if (!forceRefresh && courseStore.isCacheValid && courseStore.currentCourse?.id == courseId.value) {
    course.value = courseStore.currentCourse
    academy.value = courseStore.academy
    isCourseAdmin.value = courseStore.isCourseAdmin
    isLoading.value = false
    return
  }

  isLoading.value = true
  error.value = null

  try {
    const response = await api.get(`/api/courses/${courseId.value}/feeds`)
    
    if (response.success) {
      course.value = response.course
      academy.value = response.academy
      isCourseAdmin.value = response.isCourseAdmin
      courseMemberOfAuth.value = response.courseMemberOfAuth
      
      // Update stores
      courseStore.setCourse(response.course)
      courseStore.setAcademy(response.academy)
      courseStore.setIsCourseAdmin(response.isCourseAdmin)
      courseGroupStore.setGroups(response.courseGroups || [], courseId.value)
    }
  } catch (err: any) {
    error.value = 'ไม่สามารถโหลดข้อมูลรายวิชาได้'
  } finally {
    isLoading.value = false
  }
}

// Handle events from CourseProfileCover
const onRequestMember = (groupId?: number) => {
  fetchCourse(true)
}

const onRequestUnmember = () => {
  fetchCourse(true)
}

// Provide course data to child routes
provide('course', course)
provide('academy', academy)
provide('isCourseAdmin', isCourseAdmin)
provide('courseMemberOfAuth', courseMemberOfAuth)
provide('isLoading', isLoading)
provide('refreshCourse', fetchCourse)

// Update page title when course loads
watch(course, (newCourse) => {
  if (newCourse?.name) {
    useHead({
      title: `${newCourse.name} - รายวิชา`
    })
  }
})

// On mount
onMounted(() => {
  fetchCourse()
})
</script>

<template>
  <NuxtLayout name="main">
    <!-- Hero Slot: Course Profile Cover & Navigation -->
    <template #hero>
      <!-- Loading State -->
      <template v-if="isLoading">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden animate-pulse">
          <div class="h-48 bg-gray-200 dark:bg-gray-700"></div>
          <div class="p-6 space-y-4">
            <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-2/3"></div>
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
          </div>
        </div>
      </template>

      <!-- Error State -->
      <div v-else-if="error" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
        <Icon icon="fluent:error-circle-24-regular" class="w-20 h-20 text-red-500 mx-auto mb-4" />
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">เกิดข้อผิดพลาด</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-4">{{ error }}</p>
        <button 
          @click="fetchCourse"
          class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
        >
          ลองใหม่
        </button>
      </div>

      <!-- Course Profile Cover & Navigation -->
      <template v-else-if="course">
        <CourseProfileCover
          :course-member-of-auth="courseMemberOfAuth"
          @request-member="onRequestMember"
          @request-unmember="onRequestUnmember"
          @refresh="fetchCourse"
        />
        
        <CourseNavbarTab
          :course-id="courseId"
          :is-course-admin="isCourseAdmin"
          :course-member-of-auth="courseMemberOfAuth"
        />
      </template>
    </template>

    <!-- Main Content: Child Routes -->
    <NuxtPage v-if="course && !isLoading && !error" />
  </NuxtLayout>
</template>