<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

definePageMeta({
  layout: false, // We'll use NuxtLayout manually to pass slots
  middleware: 'auth'
})

// Define imports locally to ensure availability
import { useCourseMemberStore } from '~/stores/courseMember'
import CourseProfileCover from '~/components/learn/course/CourseProfileCover.vue'
import CourseNavbarTab from '~/components/learn/course/CourseNavbarTab.vue'

const route = useRoute()
const api = useApi()
const courseStore = useCourseStore()
const courseGroupStore = useCourseGroupStore()
const courseMemberStore = useCourseMemberStore()

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
    // We should also try to recover member state from store if possible, or refetch if missing
    if (courseMemberStore.member) {
         courseMemberOfAuth.value = courseMemberStore.member
    }
    
    isLoading.value = false
    // Still fetching fresh member data in background might be good practice, but respecting cache logic for now.
    return
  }

  isLoading.value = true
  error.value = null

  try {
    const response: any = await api.get(`/api/courses/${courseId.value}/feeds`)
    
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
      
      // Set Auth Member Store
      courseMemberStore.setMember(response.courseMemberOfAuth)
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
  fetchCourse().then(() => {
    // If the user is a member/admin and just entered the base course URL, redirect based on last_accessed_tab
    const isBaseUrl = route.path === `/courses/${courseId.value}` || route.path === `/courses/${courseId.value}/`
    if (isBaseUrl && courseMemberOfAuth.value) {
      // Get last accessed tab or default to feeds (11)
      const lastTab = courseMemberOfAuth.value.last_accessed_tab || 11
      
      // Map tab numbers to routes
      const tabRoutes: Record<number, string> = {
        1: 'lessons',
        2: 'assignments',
        3: 'quizzes',
        4: 'members',
        5: 'groups',
        7: 'attendances',
        8: 'settings',
        9: 'my-progress',
        10: 'progress',
        11: 'feeds',
        12: '', // base info
        13: 'admin'
      }
      
      const targetRoute = tabRoutes[lastTab]
      
      // Handle admin-only tabs for non-admins
      const adminOnlyTabs = [8, 10, 13] // settings, progress (admin), admin
      if (adminOnlyTabs.includes(lastTab) && !isCourseAdmin.value) {
        // Redirect to feeds instead
        navigateTo(`/courses/${courseId.value}/feeds`)
      } else if (targetRoute === '') {
        // Stay on base info page (no redirect needed)
      } else if (targetRoute) {
        navigateTo(`/courses/${courseId.value}/${targetRoute}`)
      } else {
        // Default to feeds
        navigateTo(`/courses/${courseId.value}/feeds`)
      }
    }
  })
})

// Invitation Logic
const acceptInvite = async () => {
    try {
        const res: any = await api.post(`/api/courses/${courseId.value}/admins/invitations/${courseMemberOfAuth.value.id}/accept`)
        if (res.success) {
            Swal.fire({title: 'สำเร็จ', text: 'คุณได้เข้าร่วมรายวิชาแล้ว', icon: 'success'})
            fetchCourse(true)
        }
    } catch (e) {
        Swal.fire({title: 'ผิดพลาด', text: 'ไม่สามารถตอบรับได้', icon: 'error'})
    }
}

const declineInvite = async () => {
    const result = await Swal.fire({
        title: 'ยืนยันการปฏิเสธ?',
        text: 'คุณต้องการปฏิเสธคำเชิญนี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ใช่, ปฏิเสธ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#d33'
    })

    if (result.isConfirmed) {
        try {
            const res: any = await api.post(`/api/courses/${courseId.value}/admins/invitations/${courseMemberOfAuth.value.id}/decline`)
            if (res.success) {
                Swal.fire({title: 'ปฏิเสธแล้ว', text: 'คุณได้ปฏิเสธคำเชิญเรียบร้อยแล้ว', icon: 'success'})
                navigateTo('/dashboard')
            }
        } catch (e) {
            Swal.fire({title: 'ผิดพลาด', text: 'ไม่สามารถปฏิเสธได้', icon: 'error'})
        }
    }
}
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
          @click="() => fetchCourse(true)"
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

        <!-- Invitation Banner -->
        <div v-if="courseMemberOfAuth && courseMemberOfAuth.status === 2" class="mb-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 flex flex-col md:flex-row items-center justify-between shadow-lg mx-4 mt-4 gap-4">
            <div class="flex items-center gap-3">
                <Icon icon="mdi:email-alert" class="w-8 h-8 text-yellow-600 dark:text-yellow-500" />
                <div>
                    <h3 class="font-bold text-yellow-800 dark:text-yellow-200 text-lg">คุณได้รับเชิญเข้าร่วมรายวิชานี้</h3>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">ในฐานะ <span class="font-semibold">{{ courseMemberOfAuth.role === 4 ? 'ผู้ดูแลระบบ (Admin)' : 'ผู้ช่วยสอน (TA)' }}</span></p>
                </div>
            </div>
            <div class="flex gap-2 w-full md:w-auto">
                 <button @click="acceptInvite" class="flex-1 md:flex-none px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold shadow-sm transition-colors">
                    ตอบรับ
                 </button>
                 <button @click="declineInvite" class="flex-1 md:flex-none px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-bold shadow-sm transition-colors">
                    ปฏิเสธ
                 </button>
            </div>
        </div>
        
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