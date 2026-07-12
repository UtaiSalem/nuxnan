<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseHero from '~/components/learn/course/v2/CourseHero.vue'
import CourseHeroStats from '~/components/learn/course/v2/CourseHeroStats.vue'
import CourseTabBar from '~/components/learn/course/v2/CourseTabBar.vue'
import CourseSidebar from '~/components/learn/course/v2/CourseSidebar.vue'
import CourseLessonsMenu from '~/components/learn/course/v2/CourseLessonsMenu.vue'
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
import CampaignWidget from '~/components/campaign/CampaignWidget.vue'

// Feed-specific sidebars
import CourseFeedLeftSidebar from '~/components/learn/course/v2/CourseFeedLeftSidebar.vue'
import CourseFeedRightSidebar from '~/components/learn/course/v2/CourseFeedRightSidebar.vue'

const props = defineProps<{
  course: any
  academy: any
  isCourseAdmin: boolean
  courseMemberOfAuth: any
  courseGroups: any[]
  isCheckingMembership?: boolean
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
} = useCourseLearningProgress(courseId.value, props.courseMemberOfAuth?.id, props.isCourseAdmin)

const progressByLessonId = computed(() => {
  if (!lessons.value) return {}
  return Object.fromEntries(lessons.value.map(lp => [lp.id, lp]))
})

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

const isTeacherProgressRoute = computed(() => {
  const name = route.name?.toString() || ''
  return props.isCourseAdmin && name.toLowerCase() === 'learn-courses-id-progress'
})

const isAttendancesRoute = computed(() => {
  const name = route.name?.toString() || ''
  return name.toLowerCase() === 'learn-courses-id-attendances'
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

const isCourseLessonsIndexRoute = computed(() => {
  const path = route.path
  const basePath = `/Learn/Courses/${courseId.value}/lessons`
  return path === basePath || path === `${basePath}/`
})

const shouldShowCourseInfoWidget = computed(() => isCourseInfoRoute.value || isCourseBoardRoute.value)

usePageLayoutWidgets({
  left: computed(() => !isTeacherProgressRoute.value && !isAttendancesRoute.value),
  right: computed(() => !isTableLayout.value),
  tableLayout: isTableLayout,
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
        :is-checking-membership="isCheckingMembership"
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
      <template v-if="!isCourseBoardRoute && !isAttendancesRoute">
        <!-- On the lessons index page, left side is reserved for lesson-related widgets only -->
        <CourseInstructorWidget v-if="course && !isCourseLessonsIndexRoute" :course="course" :owner="course.user" />

        <CourseInfoWidget
          v-if="course && isTableLayout && shouldShowCourseInfoWidget"
          :course="course"
          :course-member-of-auth="courseMemberOfAuth"
          :is-course-admin="isCourseAdmin"
          :course-groups="courseGroups"
          :is-checking-membership="isCheckingMembership"
          :is-enrolling="isEnrolling"
          :is-toggling-favorite="isTogglingFavorite"
          :is-wishlisted="isWishlisted"
          @enroll="$emit('request-member')"
          @toggle-favorite="$emit('toggle-favorite')"
          @purchase="$emit('purchase-course')"
          @update:selected-group-id="$emit('update:selected-group-id', $event)"
        />

        <template v-if="!isTableLayout">
          <template v-if="!isCourseLessonsIndexRoute">
            <RecentlyViewedCoursesWidget />
            <FavoriteCoursesWidget />
          </template>

          <div
            v-if="courseMemberOfAuth && !isCourseAdmin && isCourseLessonsIndexRoute"
            class="lg:sticky lg:top-36 space-y-4"
          >
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

            <CourseLessonsMenu
              v-if="course"
              :course-id="courseId"
              :is-admin="isCourseAdmin"
              :progress-map="!isCourseAdmin ? progressByLessonId : undefined"
            />
          </div>

          <div
            v-if="isCourseAdmin && isCourseLessonsIndexRoute && course"
            class="lg:sticky lg:top-36 space-y-4"
          >
            <CourseLessonsMenu
              :course-id="courseId"
              :is-admin="true"
            />
          </div>
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
        <CampaignWidget v-if="course" scope="course" :academy-id="course.academy_id" :course-id="course.id" placement="course-sidebar" />
        <!-- Course-related widgets moved here on the lessons index page -->
        <CourseInstructorWidget v-if="course && isCourseLessonsIndexRoute" :course="course" :owner="course.user" :show-lessons-menu="false" />

        <CourseInfoWidget
          v-if="course && !isTableLayout && shouldShowCourseInfoWidget"
          :course="course"
          :course-member-of-auth="courseMemberOfAuth"
          :is-course-admin="isCourseAdmin"
          :course-groups="courseGroups"
          :is-checking-membership="isCheckingMembership"
          :is-enrolling="isEnrolling"
          :is-toggling-favorite="isTogglingFavorite"
          :is-wishlisted="isWishlisted"
          @enroll="$emit('request-member')"
          @toggle-favorite="$emit('toggle-favorite')"
          @purchase="$emit('purchase-course')"
          @update:selected-group-id="$emit('update:selected-group-id', $event)"
        />

        <template v-if="courseMemberOfAuth && !isCourseInfoRoute && !isCourseAdmin">
          <CourseProgressWidget
            v-if="!isCourseLessonsIndexRoute"
            :progress="overallProgress"
            :course-id="courseId"
            :is-loading="isProgressLoading"
          />

          <CourseLessonProgressWidget
            v-if="!isCourseLessonsIndexRoute"
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
          :show-lessons-menu="!isCourseLessonsIndexRoute"
        />

        <template v-if="isCourseLessonsIndexRoute">
          <RecentlyViewedCoursesWidget />
          <FavoriteCoursesWidget />
        </template>

        <MemberedCoursesWidget />
        <MyCoursesWidget />
      </template>
    </Teleport>

    <!-- Main Content Area -->
    <slot />
  </div>
</template>


