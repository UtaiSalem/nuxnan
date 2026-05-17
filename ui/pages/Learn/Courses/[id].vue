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
import CourseEnrollmentPrompt from '~/components/learn/course/CourseEnrollmentPrompt.vue'
import AcademyCoursePurchaseModal from '~/components/academy/CoursePurchaseModal.vue'

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

// Enrollment state
const isEnrolling = ref(false)
const showGroupSelector = ref(false)
const selectedGroupId = ref<number | null>(null)
const showCopyPurchaseModal = ref(false)

// Computed
const courseId = computed(() => route.params.id as string)
const courseGroups = computed(() => courseGroupStore.groups || [])

const showEnrollmentPrompt = computed(() => {
  if (isLoading.value || error.value || !course.value) return false
  if (isCourseAdmin.value) return false
  // Status definitions: 1=active, 0=pending, 2=invited
  // We show prompt only if NOT a member at all (courseMemberOfAuth is null)
  return !courseMemberOfAuth.value
})

// Fetch course details
const fetchCourse = async (forceRefresh = false) => {
  // ถ้ามี cache และไม่ force refresh ให้ใช้จาก store
  if (!forceRefresh && courseStore.isCacheValid && courseStore.currentCourse?.id == courseId.value) {
    course.value = courseStore.currentCourse
    academy.value = courseStore.academy
    isCourseAdmin.value = courseStore.isCourseAdmin
    if (courseMemberStore.member) {
      courseMemberOfAuth.value = courseMemberStore.member
    }

    isLoading.value = false
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
      courseGroupStore.ungroupedMembers = response.ungroupedMembers || []

      // Set Auth Member Store
      courseMemberStore.setMember(response.courseMemberOfAuth)
    }
  } catch (err: any) {
    error.value = 'ไม่สามารถโหลดข้อมูลรายวิชาได้'
  } finally {
    isLoading.value = false
  }
}

// Enrollment Handlers
const handleRequestMember = () => {
  if (courseGroups.value.length > 1) {
    selectedGroupId.value = courseGroups.value[0]?.id || null
    showGroupSelector.value = true
    return
  }

  const groupId = courseGroups.value.length === 1 ? courseGroups.value[0].id : null
  confirmAndEnroll(groupId)
}

const confirmAndEnroll = async (groupId: number | null = null) => {
  const price = Number(course.value?.tuition_fees ?? 0)

  if (price > 0) {
    const result = await Swal.fire({
      title: 'ยืนยันการสมัครเรียน',
      text: `รายวิชานี้มีค่าเรียน ฿${price.toLocaleString()} คุณต้องการดำเนินการต่อใช่หรือไม่?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'ยืนยัน',
      cancelButtonText: 'ยกเลิก'
    })
    if (!result.isConfirmed) return
  }

  await executeEnrollment(groupId)
}

const executeEnrollment = async (groupId: number | null = null) => {
  isEnrolling.value = true
  try {
    const payload = groupId ? { group_id: groupId } : {}
    const response: any = await api.post(`/api/courses/${courseId.value}/members`, payload)

    if (response.success) {
      Swal.fire({
        title: 'สำเร็จ',
        text: response.message || 'สมัครสมาชิกเรียบร้อยแล้ว',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      })
      showGroupSelector.value = false
      await fetchCourse(true)
    }
  } catch (err: any) {
    Swal.fire({
      title: 'ผิดพลาด',
      text: err.data?.message || err.data?.msg || 'ไม่สามารถสมัครสมาชิกได้ กรุณาลองใหม่อีกครั้ง',
      icon: 'error'
    })
  } finally {
    isEnrolling.value = false
  }
}

const confirmGroupMembership = () => {
  if (selectedGroupId.value) {
    confirmAndEnroll(selectedGroupId.value)
  }
}

const onCopyPurchaseSuccess = async () => {
  showCopyPurchaseModal.value = false
  await fetchCourse(true)
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
    const isBaseUrl = route.path === `/Learn/Courses/${courseId.value}` || route.path === `/Learn/Courses/${courseId.value}/`
    if (isBaseUrl) {
      if (courseMemberOfAuth.value) {
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
          13: 'admin',
          14: 'external-scores'
        }
        
        const targetRoute = tabRoutes[lastTab]
        
        // Handle admin-only tabs for non-admins
        const adminOnlyTabs = [8, 10, 13, 14] // settings, progress (admin), admin, external-scores
        if (adminOnlyTabs.includes(lastTab) && !isCourseAdmin.value) {
          // Redirect to feeds instead
          navigateTo(`/Learn/Courses/${courseId.value}/feeds`)
        } else if (targetRoute === '') {
          // Stay on base info page (no redirect needed)
        } else if (targetRoute) {
          navigateTo(`/Learn/Courses/${courseId.value}/${targetRoute}`)
        } else {
          // Default to feeds
          navigateTo(`/Learn/Courses/${courseId.value}/feeds`)
        }
      }
      // Non-members stay on the base info page (index.vue)
    }
  })
})

// Invitation Logic
const acceptInvite = async () => {
    try {
        const res: any = await api.post(`/api/courses/${courseId.value}/admins/invitations/${courseMemberOfAuth.value.id}/accept`, {})
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
            const res: any = await api.post(`/api/courses/${courseId.value}/admins/invitations/${courseMemberOfAuth.value.id}/decline`, {})
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
  <div class="pb-24 sm:pb-0">
    <NuxtLayout name="main">
      <!-- Hero Slot: Course Profile Cover & Navigation -->
      <template #hero>
        <!-- Loading State -->
        <template v-if="isLoading">
          <div class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden animate-pulse shadow-sm">
            <div class="h-32 sm:h-48 md:h-64 bg-gray-200 dark:bg-gray-700"></div>
            <div class="p-4 sm:p-6 space-y-4">
              <div class="h-6 sm:h-8 bg-gray-200 dark:bg-gray-700 rounded w-2/3"></div>
              <div class="h-3 sm:h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
            </div>
          </div>
        </template>

        <!-- Error State -->
        <div v-else-if="error" class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl border border-gray-200 dark:border-gray-700 p-8 sm:p-12 text-center shadow-md">
          <Icon icon="fluent:error-circle-24-regular" class="w-16 h-16 sm:w-20 sm:h-20 text-red-500 mx-auto mb-4" />
          <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white mb-2">เกิดข้อผิดพลาด</h3>
          <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400 mb-4">{{ error }}</p>
          <button 
            @click="() => fetchCourse(true)"
            class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors font-semibold"
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
          <div v-if="courseMemberOfAuth && courseMemberOfAuth.status === 2" class="mt-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl p-4 sm:p-5 flex flex-col md:flex-row items-center justify-between shadow-lg mx-0 sm:mx-2 gap-4">
              <div class="flex items-center gap-3 sm:gap-4 w-full">
                  <div class="p-2.5 bg-yellow-100 dark:bg-yellow-800/40 rounded-full flex-shrink-0">
                      <Icon icon="mdi:email-alert" class="w-6 h-6 sm:w-8 sm:h-8 text-yellow-600 dark:text-yellow-500" />
                  </div>
                  <div class="min-w-0">
                      <h3 class="font-black text-yellow-800 dark:text-yellow-200 text-base sm:text-lg truncate">คุณได้รับเชิญเข้าร่วมรายวิชานี้</h3>
                      <p class="text-xs sm:text-sm text-yellow-700 dark:text-yellow-300 opacity-90">ในฐานะ <span class="font-bold underline decoration-yellow-400">{{ courseMemberOfAuth.role === 4 ? 'ผู้ดูแลระบบ (Admin)' : 'ผู้ช่วยสอน (TA)' }}</span></p>
                  </div>
              </div>
              <div class="flex gap-2 w-full md:w-auto">
                   <button @click="acceptInvite" class="flex-1 md:flex-none px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold shadow-md transition-all active:scale-95">
                      ตอบรับ
                   </button>
                   <button @click="declineInvite" class="flex-1 md:flex-none px-6 py-2.5 bg-white dark:bg-gray-800 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-900/50 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 font-bold shadow-sm transition-all active:scale-95">
                      ปฏิเสธ
                   </button>
              </div>
          </div>
          
          <CourseNavbarTab
            :course-id="courseId"
            :is-course-admin="isCourseAdmin"
            :course-member-of-auth="courseMemberOfAuth"
          />

          <!-- Global Enrollment Prompt -->
          <CourseEnrollmentPrompt
            v-if="showEnrollmentPrompt"
            :course="course"
            :course-groups="courseGroups"
            :is-enrolling="isEnrolling"
            @request-member="handleRequestMember"
            @purchase-course="showCopyPurchaseModal = true"
          />

          <!-- Client-side only components -->
          <ClientOnly>
            <!-- Group Selection Modal (Teleported to body) -->
            <Teleport to="body">
              <div
                  v-if="showGroupSelector"
                  class="fixed inset-0 z-[100] flex items-center justify-center p-4"
                  @click.self="showGroupSelector = false"
              >
                  <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
                  <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden animate-in fade-in zoom-in duration-200">
                      <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                          <div class="flex items-center justify-between gap-3">
                              <div>
                                  <h3 class="text-lg font-black text-gray-900 dark:text-white">เลือกกลุ่มเรียน</h3>
                                  <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">เลือกกลุ่มที่ต้องการสมัครเข้าร่วม</p>
                              </div>
                              <button
                                  type="button"
                                  @click="showGroupSelector = false"
                                  class="p-2 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-gray-200 transition-colors"
                              >
                                  <Icon icon="heroicons:x-mark" class="w-5 h-5" />
                              </button>
                          </div>
                      </div>

                      <div class="p-5 space-y-3 max-h-[50vh] overflow-y-auto custom-scrollbar">
                          <label
                              v-for="group in courseGroups"
                              :key="group.id"
                              class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all duration-200"
                              :class="selectedGroupId === group.id ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600'"
                          >
                              <input
                                  v-model="selectedGroupId"
                                  type="radio"
                                  :value="group.id"
                                  class="w-5 h-5 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                              >
                              <div class="flex-1 min-w-0">
                                  <div class="font-bold text-gray-900 dark:text-white truncate">{{ group.name || `กลุ่ม ${group.id}` }}</div>
                                  <div class="text-xs text-gray-500 dark:text-gray-400">
                                      {{ group.members_count || 0 }} สมาชิก
                                  </div>
                              </div>
                          </label>
                      </div>

                      <div class="p-5 bg-gray-50 dark:bg-gray-900/50 flex gap-3">
                          <button
                              type="button"
                              @click="showGroupSelector = false"
                              class="flex-1 px-4 py-3 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                          >
                              ยกเลิก
                          </button>
                          <button
                              type="button"
                              @click="confirmGroupMembership"
                              :disabled="!selectedGroupId || isEnrolling"
                              class="flex-1 px-4 py-3 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 transition-colors shadow-lg shadow-blue-600/20 disabled:opacity-50 flex items-center justify-center gap-2"
                          >
                              <Icon v-if="isEnrolling" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                              <span>สมัครกลุ่มนี้</span>
                          </button>
                      </div>
                </div>
              </div>
            </Teleport>

            <AcademyCoursePurchaseModal
              v-if="course"
              :course="course"
              :visible="showCopyPurchaseModal"
              @close="showCopyPurchaseModal = false"
              @success="onCopyPurchaseSuccess"
            />
          </ClientOnly>
        </template>
      </template>

      <!-- Main Content: Child Routes -->
      <NuxtPage v-if="course && !isLoading && !error" />
    </NuxtLayout>
  </div>
</template>
