<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

import CoursePageShell from '~/components/learn/course/v2/CoursePageShell.vue'
import PageTransitionLoader from '~/components/accessories/PageTransitionLoader.vue'
import CourseInvitationBanner from '~/components/learn/course/v2/CourseInvitationBanner.vue'
import CourseEditModal from '~/components/learn/course/v2/CourseEditModal.vue'
import CourseGroupSelectorModal from '~/components/learn/course/v2/CourseGroupSelectorModal.vue'
import AcademyCoursePurchaseModal from '~/components/academy/CoursePurchaseModal.vue'
import CourseDonationModal from '~/components/donation/CourseDonationModal.vue'
import { useCourseDonations, type CourseDonation } from '~/composables/useCourseDonations'

definePageMeta({
  layout: 'main',
  middleware: 'auth'
})

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
const isCheckingMembership = ref(true)
const error = ref<string | null>(null)

// UI State
const showEditModal = ref(false)
const showGroupSelector = ref(false)
const showCopyPurchaseModal = ref(false)
const isEnrolling = ref(false)
const isTogglingFavorite = ref(false)
const isWishlisted = ref(false)
const selectedGroupId = ref<number | null>(null)
const showDonationModal = ref(false)
const ownerDonations = ref<CourseDonation[]>([])
const { fetchCourseDonations } = useCourseDonations()

const courseId = computed(() => route.params.id as string)
const courseGroups = computed(() => courseGroupStore.groups || [])

// Tab persistence redirect logic
const handleRedirect = () => {
  const isBaseUrl = route.path === `/Learn/Courses/${courseId.value}` || route.path === `/Learn/Courses/${courseId.value}/`
  if (isBaseUrl && courseMemberOfAuth.value) {
    const lastTab = courseMemberOfAuth.value.last_accessed_tab || 11
    const tabRoutes: Record<number, string> = { 
      1: 'lessons', 2: 'assignments', 3: 'quizzes', 4: 'members',
      5: 'groups', 7: 'attendances', 8: 'settings', 9: 'my-progress',
      10: 'progress', 11: 'feeds', 12: '', 13: 'admin', 14: 'external-scores',
      15: 'gradebook'
    }
    const targetRoute = tabRoutes[lastTab]
    const adminOnlyTabs = [8, 10, 13, 14]
    
    if (adminOnlyTabs.includes(lastTab) && !isCourseAdmin.value) {
      navigateTo(`/Learn/Courses/${courseId.value}/feeds`)
    } else if (targetRoute && targetRoute !== '') {
      navigateTo(`/Learn/Courses/${courseId.value}/${targetRoute}`)
    }
  }
}

// Fetch course details
const fetchCourse = async (forceRefresh = false) => {
  const isSameCourse = course.value?.id == courseId.value
  const shouldShowLoading = !isSameCourse || forceRefresh
  
  if (shouldShowLoading) {
    isLoading.value = true
  }
  isCheckingMembership.value = true
  error.value = null

  try {
    const response = await courseStore.fetchCourse(courseId.value, forceRefresh)
    if (response.success) {
      course.value = response.course
      academy.value = response.academy
      isCourseAdmin.value = response.isCourseAdmin
      courseMemberOfAuth.value = response.courseMemberOfAuth || response.courseMember
      isWishlisted.value = course.value.is_favorited || false

      // Update other stores if needed
      courseGroupStore.setGroups(response.courseGroups || [], courseId.value)
      courseMemberStore.setMember(response.courseMemberOfAuth || response.courseMember)
      
      // Perform redirect if needed
      handleRedirect()
    }
  } catch (err: any) {
    if (shouldShowLoading) {
      error.value = 'ไม่สามารถโหลดข้อมูลรายวิชาได้'
    }
  } finally {
    isLoading.value = false
    isCheckingMembership.value = false
  }
}

// Favorite/Wishlist
const toggleWishlist = async () => {
  if (!course.value || isTogglingFavorite.value) return
  
  isTogglingFavorite.value = true
  try {
    const response = await api.post(`/api/courses/${course.value.id}/favorite`, {}) as { 
      success: boolean
      is_favorited?: boolean
      message?: string 
    }
    
    if (response.success) {
      isWishlisted.value = response.is_favorited ?? !isWishlisted.value
      courseStore.updateCourse({ is_favorited: isWishlisted.value })
    }
  } catch (err: any) {
    console.error('Failed to toggle favorite:', err)
  } finally {
    isTogglingFavorite.value = false
  }
}

// Enrollment
const handleRequestMember = () => {
  if (course.value && !course.value.is_enrollment_open) {
    Swal.fire({ title: 'ปิดรับสมัครแล้ว', text: 'รายวิชานี้ไม่เปิดรับสมัครในขณะนี้', icon: 'warning' })
    return
  }

  if (selectedGroupId.value) {
    confirmAndEnroll(selectedGroupId.value)
    return
  }

  if (courseGroups.value.length > 1) {
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
      Swal.fire({ title: 'สำเร็จ', text: 'สมัครสมาชิกเรียบร้อยแล้ว', icon: 'success', timer: 2000, showConfirmButton: false })
      showGroupSelector.value = false
      await fetchCourse(true)
    }
  } catch (err: any) {
    Swal.fire({ title: 'ผิดพลาด', text: err.data?.message || err.data?.msg || 'ไม่สามารถสมัครสมาชิกได้', icon: 'error' })
  } finally {
    isEnrolling.value = false
  }
}

// Provide for sub-pages
provide('course', course)
provide('academy', academy)
provide('isCourseAdmin', isCourseAdmin)
provide('courseMemberOfAuth', courseMemberOfAuth)
provide('isLoading', isLoading)
provide('refreshCourse', fetchCourse)

const authStore = useAuthStore()

const openDonation = () => {
  if (!authStore.user) return navigateTo(`/login?return=${encodeURIComponent(route.fullPath)}`)
  if (Number(authStore.user.id) === Number(course.value?.user_id)) {
    return navigateTo(`/Learn/Courses/${courseId.value}/support`)
  }
  showDonationModal.value = true
}

onMounted(() => {
  if (authStore.user) {
    fetchCourse()
  }
  // ถ้า user ยังไม่พร้อม รอ watcher ด้านล่าง
})

watch(() => authStore.user?.id, (id) => {
  if (id && !course.value) fetchCourse()
}, { immediate: false })

watch(course, (newCourse) => {
  if (newCourse?.name) {
    useHead({ title: `${newCourse.name} - รายวิชา` })
  }
  if (newCourse?.user_id && authStore.user?.id === newCourse.user_id) {
    fetchCourseDonations(Number(newCourse.id)).then(response => { ownerDonations.value = response.data })
  }
})

// Watch courseId to refetch when navigating between courses
watch(courseId, (newId) => {
  if (newId) {
    fetchCourse(true)
  }
})
</script>

<template>
  <div class="relative min-h-[600px]">
    <!-- Scoped Loading State -->
    <PageTransitionLoader 
      :show="isLoading && !course" 
      text="กำลังโหลดข้อมูลรายวิชา..." 
    />

    <!-- Error State -->
    <div v-if="error && !course" class="px-0 py-8 sm:px-8 text-center bg-white dark:bg-gray-800 rounded-2xl shadow-xl m-4">
      <Icon icon="fluent:error-circle-24-regular" class="w-16 h-16 text-red-500 mx-auto mb-4" />
      <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2">เกิดข้อผิดพลาด</h3>
      <p class="text-gray-500 dark:text-gray-400 mb-6">{{ error }}</p>
      <button @click="fetchCourse(true)" class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-600/20">
        ลองใหม่อีกครั้ง
      </button>
    </div>

    <!-- MAIN CONTENT -->
    <template v-else-if="course">
      <CoursePageShell
        :course="course"
        :academy="academy"
        :is-course-admin="isCourseAdmin"
        :course-member-of-auth="courseMemberOfAuth"
        :course-groups="courseGroups"
        :is-checking-membership="isCheckingMembership"
        :is-enrolling="isEnrolling"
        :is-toggling-favorite="isTogglingFavorite"
        :is-wishlisted="isWishlisted"
        @refresh="fetchCourse(true)"
        @edit-name="showEditModal = true"
        @request-member="handleRequestMember"
        @purchase-course="showCopyPurchaseModal = true"
        @toggle-favorite="toggleWishlist"
        @update:selected-group-id="selectedGroupId = $event"
        @support-course="openDonation"
      >
        <template #default>
          <div v-if="authStore.user?.id === course.user_id" class="mb-4 rounded-2xl bg-white p-5 shadow-sm dark:bg-slate-800">
            <h2 class="font-bold">การสนับสนุน</h2><div v-for="donation in ownerDonations" :key="donation.id" class="mt-2 flex justify-between text-sm"><span>{{ donation.donor_display_name || 'ผู้ไม่ประสงค์ออกนาม' }}</span><span>{{ donation.donation_type === 'point' ? donation.points_amount + ' points' : donation.cash_amount + ' ' + (donation.currency || 'THB') }}</span></div>
          </div>
          <!-- Invitation Banner (Admin/TA) -->
          <CourseInvitationBanner
            v-if="courseMemberOfAuth && courseMemberOfAuth.status === 2"
            :course-id="courseId"
            :course-member-of-auth="courseMemberOfAuth"
            @refresh="fetchCourse(true)"
          />

          <!-- Actual Content (NuxtPage) -->
          <NuxtPage />
        </template>
      </CoursePageShell>
      <CourseDonationModal v-model:visible="showDonationModal" :course-id="Number(course.id)" :course-name="course.name" :course-owner-id="Number(course.user_id)" :balance="authStore.user?.points ?? authStore.user?.pp" />

      <!-- Modals -->
      <CourseEditModal
        :show="showEditModal"
        :course="course"
        @close="showEditModal = false"
        @refresh="fetchCourse(true)"
      />

      <CourseGroupSelectorModal
        :show="showGroupSelector"
        :groups="courseGroups"
        :is-processing="isEnrolling"
        @close="showGroupSelector = false"
        @confirm="confirmAndEnroll"
      />

      <AcademyCoursePurchaseModal
        v-if="course"
        :course="course"
        :visible="showCopyPurchaseModal"
        @close="showCopyPurchaseModal = false"
        @success="fetchCourse(true)"
      />
    </template>
  </div>
</template>
