<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseHero from '~/components/learn/course/v2/CourseHero.vue'
import CourseHeroStats from '~/components/learn/course/v2/CourseHeroStats.vue'
import CourseTabBar from '~/components/learn/course/v2/CourseTabBar.vue'
import CourseSidebar from '~/components/learn/course/v2/CourseSidebar.vue'
import CourseInstructorWidget from '~/components/learn/course/CourseInstructorWidget.vue'
import CourseProgressWidget from '~/components/learn/course/CourseProgressWidget.vue'
import CourseLessonProgressWidget from '~/components/learn/course/CourseLessonProgressWidget.vue'
import CourseAssignmentProgressWidget from '~/components/learn/course/CourseAssignmentProgressWidget.vue'
import CourseQuizProgressWidget from '~/components/learn/course/CourseQuizProgressWidget.vue'
import CourseInfoWidget from '~/components/learn/course/CourseInfoWidget.vue'

// Widgets
import RecentlyViewedCoursesWidget from '~/components/widgets/RecentlyViewedCoursesWidget.vue'
import FavoriteCoursesWidget from '~/components/widgets/FavoriteCoursesWidget.vue'
import MemberedCoursesWidget from '~/components/widgets/MemberedCoursesWidget.vue'
import MyCoursesWidget from '~/components/widgets/MyCoursesWidget.vue'

// Feed-specific sidebars
import CourseFeedLeftSidebar from '~/components/learn/course/v2/CourseFeedLeftSidebar.vue'
import CourseFeedRightSidebar from '~/components/learn/course/v2/CourseFeedRightSidebar.vue'

const props = defineProps<{
  course: any
  academy: any
  isCourseAdmin: boolean
  courseMemberOfAuth: any
  courseGroups: any[]
  isEnrolling?: boolean
  isTogglingFavorite?: boolean
  isWishlisted?: boolean
}>()

const emit = defineEmits<{
  (e: 'refresh'): void
  (e: 'edit-name'): void
  (e: 'request-member'): void
  (e: 'purchase-course'): void
  (e: 'toggle-favorite'): void
  (e: 'update:selected-group-id', id: number | null): void
}>()

const route = useRoute()
const courseId = computed(() => route.params.id as string)

// Single Source of Truth for Progress
const { 
  lessons, 
  assignments, 
  quizzes, 
  overallProgress, 
  isLoading: isProgressLoading, 
  error: progressError 
} = useCourseLearningProgress(courseId.value, props.courseMemberOfAuth?.id)

const layoutWidgets = useLayoutWidgets()

const TABLE_ROUTES = [
  'learn-Courses-id-attendances',
  'learn-Courses-id-progress',
  'learn-Courses-id-members',
  'learn-Courses-id-external-scores',
  'learn-Courses-id-gradebook',
  'learn-Courses-id-gradebook-eligibility',
  'learn-Courses-id-gradebook-completion',
  'learn-Courses-id-gradebook-appeals',
  'learn-Courses-id-gradebook-remediation',
  'learn-Courses-id-gradebook-certificates'
]

const isTableLayout = computed(() => {
  const name = route.name?.toString() || ''
  return TABLE_ROUTES.some(r => name.toLowerCase() === r.toLowerCase())
})

// Determine if we are on the main course info page
const isCourseInfoRoute = computed(() => {
  const path = route.path
  const basePath = `/Learn/Courses/${courseId.value}`
  return path === basePath || path === `${basePath}/`
})

const isCourseBoardRoute = computed(() => {
  const path = route.path
  const basePath = `/Learn/Courses/${courseId.value}`
  return path === `${basePath}/feeds` || path === `${basePath}/feeds/`
})

const shouldShowCourseInfoWidget = computed(() => isCourseInfoRoute.value || isCourseBoardRoute.value)

watchEffect(() => {
  layoutWidgets.value.hasLeftWidgets = true
  layoutWidgets.value.hasRightWidgets = !isTableLayout.value
  layoutWidgets.value.isTableLayout = isTableLayout.value
})

onUnmounted(() => {
  layoutWidgets.value.hasLeftWidgets = false
  layoutWidgets.value.hasRightWidgets = false
  layoutWidgets.value.isTableLayout = false
})

</script>

<template>
  <div class="course-page-shell">
    <Teleport to="#hero-slot">
      <CourseHero 
        :course="course" 
        :is-admin="isCourseAdmin" 
        :academy="academy"
        :course-member-of-auth="courseMemberOfAuth"
        :hide-cover="!isCourseInfoRoute"
        @edit-name="$emit('edit-name')"
        @refresh="$emit('refresh')"
        @request-member="$emit('request-member')"
        @purchase-course="$emit('purchase-course')"
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
    </Teleport>

    <Teleport to="#tabs-slot">
      <CourseTabBar
        :course-id="courseId"
        :course-name="props.course?.name || ''"
        :is-course-admin="isCourseAdmin"
        :course-member-of-auth="courseMemberOfAuth"
      />
    </Teleport>

    <Teleport to="#left-widgets-slot">
      <!-- Feed-specific left sidebar -->
      <CourseFeedLeftSidebar
        v-if="isCourseBoardRoute && course"
        :course="course"
        :is-course-admin="isCourseAdmin"
      />

      <!-- Default left widgets (non-feed pages) -->
      <template v-if="!isCourseBoardRoute">
        <CourseInstructorWidget v-if="course" :course="course" :owner="course.user" />

        <CourseInfoWidget
          v-if="course && isTableLayout && shouldShowCourseInfoWidget"
          :course="course"
          :course-member-of-auth="courseMemberOfAuth"
          :is-course-admin="isCourseAdmin"
          :course-groups="courseGroups"
          :is-enrolling="isEnrolling"
          :is-toggling-favorite="isTogglingFavorite"
          :is-wishlisted="isWishlisted"
          @enroll="$emit('request-member')"
          @toggle-favorite="$emit('toggle-favorite')"
          @purchase="$emit('purchase-course')"
          @update:selected-group-id="$emit('update:selected-group-id', $event)"
        />

        <template v-if="!isTableLayout">
          <RecentlyViewedCoursesWidget />
          <FavoriteCoursesWidget />
        </template>
      </template>
    </Teleport>

    <Teleport to="#right-widgets-slot">
      <!-- Feed-specific right sidebar -->
      <CourseFeedRightSidebar
        v-if="isCourseBoardRoute && course"
        :course="course"
      />

      <!-- Default right widgets (non-feed pages) -->
      <template v-if="!isCourseBoardRoute">
        <CourseInfoWidget
          v-if="course && !isTableLayout && shouldShowCourseInfoWidget"
          :course="course"
          :course-member-of-auth="courseMemberOfAuth"
          :is-course-admin="isCourseAdmin"
          :course-groups="courseGroups"
          :is-enrolling="isEnrolling"
          :is-toggling-favorite="isTogglingFavorite"
          :is-wishlisted="isWishlisted"
          @enroll="$emit('request-member')"
          @toggle-favorite="$emit('toggle-favorite')"
          @purchase="$emit('purchase-course')"
          @update:selected-group-id="$emit('update:selected-group-id', $event)"
        />

        <template v-if="courseMemberOfAuth && !isCourseInfoRoute">
          <CourseProgressWidget
            :progress="overallProgress"
            :course-id="courseId"
            :is-loading="isProgressLoading"
          />

          <CourseLessonProgressWidget
            :lessons="lessons"
            :is-loading="isProgressLoading"
            :error="progressError"
          />

          <CourseAssignmentProgressWidget
            v-if="assignments.length > 0 || isProgressLoading"
            :assignments="assignments"
            :is-loading="isProgressLoading"
            :error="progressError"
          />

          <CourseQuizProgressWidget
            v-if="quizzes.length > 0 || isProgressLoading"
            :quizzes="quizzes"
            :is-loading="isProgressLoading"
            :error="progressError"
          />
        </template>

        <CourseSidebar
          v-if="!isTableLayout"
          :course="course"
          :is-admin="isCourseAdmin"
          :course-member-of-auth="courseMemberOfAuth"
        />

        <MemberedCoursesWidget />
        <MyCoursesWidget />
      </template>
    </Teleport>

    <!-- Main Content Area -->
    <slot />
  </div>
</template>


