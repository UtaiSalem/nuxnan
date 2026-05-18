<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

import CourseHero from '~/components/learn/course/v2/CourseHero.vue'
import CourseHeroStats from '~/components/learn/course/v2/CourseHeroStats.vue'
import CourseTabBar from '~/components/learn/course/v2/CourseTabBar.vue'
import CourseSidebar from '~/components/learn/course/v2/CourseSidebar.vue'
import CourseActionButton from '~/components/learn/course/v2/CourseActionButton.vue'
import CourseInvitationBanner from '~/components/learn/course/v2/CourseInvitationBanner.vue'
import CourseEditModal from '~/components/learn/course/v2/CourseEditModal.vue'
import CourseGroupSelectorModal from '~/components/learn/course/v2/CourseGroupSelectorModal.vue'
import AcademyCoursePurchaseModal from '~/components/academy/CoursePurchaseModal.vue'

// Widgets
import RecentlyViewedCoursesWidget from '~/components/widgets/RecentlyViewedCoursesWidget.vue'
import FavoriteCoursesWidget from '~/components/widgets/FavoriteCoursesWidget.vue'
import MemberedCoursesWidget from '~/components/widgets/MemberedCoursesWidget.vue'
import MyCoursesWidget from '~/components/widgets/MyCoursesWidget.vue'
import CourseInstructorWidget from '~/components/learn/course/CourseInstructorWidget.vue'
import CourseProgressWidget from '~/components/learn/course/CourseProgressWidget.vue'
import CourseInfoWidget from '~/components/learn/course/CourseInfoWidget.vue'

definePageMeta({
  layout: false,
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
const error = ref<string | null>(null)

// UI State
const showEditModal = ref(false)
const showGroupSelector = ref(false)
const showCopyPurchaseModal = ref(false)
const isEnrolling = ref(false)
const isTogglingFavorite = ref(false)
const isWishlisted = ref(false)
const selectedGroupId = ref<number | null>(null)

const courseId = computed(() => route.params.id as string)
const courseGroups = computed(() => courseGroupStore.groups || [])

// Fetch course details
const fetchCourse = async (forceRefresh = false) => {
  isLoading.value = true
  error.value = null

  try {
    const response = await courseStore.fetchCourse(courseId.value, forceRefresh)
    if (response.success) {
      course.value = response.course
      academy.value = response.academy
      isCourseAdmin.value = response.isCourseAdmin
      courseMemberOfAuth.value = response.courseMemberOfAuth || response.courseMember

      // Update other stores if needed
      courseGroupStore.setGroups(response.courseGroups || [], courseId.value)
      courseMemberStore.setMember(response.courseMemberOfAuth || response.courseMember)
    }
  } catch (err: any) {
    error.value = 'ไม่สามารถโหลดข้อมูลรายวิชาได้'
  } finally {
    isLoading.value = false
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
    Swal.fire({ title: 'ผิดพลาด', text: err.data?.message || 'ไม่สามารถสมัครสมาชิกได้', icon: 'error' })
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

onMounted(() => {
  fetchCourse().then(() => {
    // Tab persistence redirect logic
    const isBaseUrl = route.path === `/Learn/Courses/${courseId.value}` || route.path === `/Learn/Courses/${courseId.value}/`
    if (isBaseUrl && courseMemberOfAuth.value) {
      const lastTab = courseMemberOfAuth.value.last_accessed_tab || 11
      const tabRoutes: Record<number, string> = { 1: 'lessons', 2: 'assignments', 3: 'quizzes', 4: 'members', 5: 'groups', 7: 'attendances', 8: 'settings', 9: 'my-progress', 10: 'progress', 11: 'feeds', 12: '', 13: 'admin', 14: 'external-scores' }
      const targetRoute = tabRoutes[lastTab]
      const adminOnlyTabs = [8, 10, 13, 14]
      if (adminOnlyTabs.includes(lastTab) && !isCourseAdmin.value) {
        navigateTo(`/Learn/Courses/${courseId.value}/feeds`)
      } else if (targetRoute && targetRoute !== '') {
        navigateTo(`/Learn/Courses/${courseId.value}/${targetRoute}`)
      }
    }
  })
})

watch(course, (newCourse) => {
  if (newCourse?.name) {
    useHead({ title: `${newCourse.name} - รายวิชา` })
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
  <div>
    <!-- Loading State -->
    <div v-if="isLoading && !course" class="p-8 text-center">
      <Icon icon="svg-spinners:ring-resize" class="w-12 h-12 text-vikinger-cyan mx-auto mb-4" />
      <p class="text-gray-500 font-bold">กำลังโหลดข้อมูลรายวิชา...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="p-8 text-center bg-white dark:bg-gray-800 rounded-2xl shadow-xl m-4">
      <Icon icon="fluent:error-circle-24-regular" class="w-16 h-16 text-red-500 mx-auto mb-4" />
      <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2">เกิดข้อผิดพลาด</h3>
      <p class="text-gray-500 dark:text-gray-400 mb-6">{{ error }}</p>
      <button @click="fetchCourse(true)" class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-600/20">
        ลองใหม่อีกครั้ง
      </button>
    </div>

    <!-- MAIN CONTENT -->
    <template v-else-if="course">
      <!-- Layout Slots -->
      <NuxtLayout name="main">
        <template #hero>
          <CourseHero
            :course="course"
            :is-admin="isCourseAdmin"
            :academy="academy"
            :course-member-of-auth="courseMemberOfAuth"
            @edit-name="showEditModal = true"
            @refresh="fetchCourse(true)"
            @request-member="handleRequestMember"
            @purchase-course="showCopyPurchaseModal = true"
          >
            <template #stats>
              <CourseHeroStats
                :lessons-count="course?.course_lessons_count ?? course?.lessons_count ?? 0"
                :enrolled-students="course?.enrolled_students ?? 0"
                :rating="course?.rating"
                :groups-count="course?.groups ?? 0"
              />
            </template>
          </CourseHero>
        </template>

        <template #tabs>
          <CourseTabBar
            :course-id="courseId"
            :is-course-admin="isCourseAdmin"
            :course-member-of-auth="courseMemberOfAuth"
          />
        </template>

        <template #leftWidgets>
          <div class="sticky top-20 space-y-4">
            <CourseInstructorWidget v-if="course" :course="course" :owner="course.user" />
            <RecentlyViewedCoursesWidget />
            <FavoriteCoursesWidget />
          </div>
        </template>

        <template #rightWidgets>
          <div class="sticky top-20 space-y-4">
            <CourseInfoWidget
              v-if="course"
              :course="course"
              :course-member-of-auth="courseMemberOfAuth"
              :is-course-admin="isCourseAdmin"
              :course-groups="courseGroups"
              :is-enrolling="isEnrolling"
              :is-toggling-favorite="isTogglingFavorite"
              :is-wishlisted="isWishlisted"
              @enroll="handleRequestMember"
              @toggle-favorite="toggleWishlist"
              @purchase="showCopyPurchaseModal = true"
              @update:selected-group-id="selectedGroupId = $event"
            />

            <CourseProgressWidget v-if="courseMemberOfAuth" :member="courseMemberOfAuth" :course-id="courseId" />

            <CourseSidebar
              :course="course"
              :is-admin="isCourseAdmin"
              :course-member-of-auth="courseMemberOfAuth"
            />

            <MemberedCoursesWidget />
            <MyCoursesWidget />
          </div>
        </template>

        <!-- Main Content Area -->
        <div class="space-y-6">
          <!-- Invitation Banner (Admin/TA) -->
          <CourseInvitationBanner
            v-if="courseMemberOfAuth && courseMemberOfAuth.status === 2"
            :course-id="courseId"
            :course-member-of-auth="courseMemberOfAuth"
            @refresh="fetchCourse(true)"
          />

          <!-- Actual Content (NuxtPage) -->
          <NuxtPage />

          <!-- Mobile-only Widgets (shown only on small screens) -->
          <div class="lg:hidden space-y-6 mt-8">
            <CourseInfoWidget
              v-if="course"
              :course="course"
              :course-member-of-auth="courseMemberOfAuth"
              :is-course-admin="isCourseAdmin"
              :course-groups="courseGroups"
              :is-enrolling="isEnrolling"
              :is-toggling-favorite="isTogglingFavorite"
              :is-wishlisted="isWishlisted"
              @enroll="handleRequestMember"
              @toggle-favorite="toggleWishlist"
              @purchase="showCopyPurchaseModal = true"
              @update:selected-group-id="selectedGroupId = $event"
            />

            <CourseProgressWidget v-if="courseMemberOfAuth" :member="courseMemberOfAuth" :course-id="courseId" />

            <CourseInstructorWidget v-if="course" :course="course" :owner="course.user" />
          </div>
        </div>
      </NuxtLayout>

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
