<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseHero from '~/components/learn/course/v2/CourseHero.vue'
import CourseHeroStats from '~/components/learn/course/v2/CourseHeroStats.vue'
import CourseTabBar from '~/components/learn/course/v2/CourseTabBar.vue'
import CourseSidebar from '~/components/learn/course/v2/CourseSidebar.vue'
import CourseInstructorWidget from '~/components/learn/course/CourseInstructorWidget.vue'
import CourseProgressWidget from '~/components/learn/course/CourseProgressWidget.vue'
import CourseInfoWidget from '~/components/learn/course/CourseInfoWidget.vue'

// Widgets
import RecentlyViewedCoursesWidget from '~/components/widgets/RecentlyViewedCoursesWidget.vue'
import FavoriteCoursesWidget from '~/components/widgets/FavoriteCoursesWidget.vue'
import MemberedCoursesWidget from '~/components/widgets/MemberedCoursesWidget.vue'
import MyCoursesWidget from '~/components/widgets/MyCoursesWidget.vue'

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

const layoutWidgets = useLayoutWidgets()
onMounted(() => {
  layoutWidgets.value.hasLeftWidgets = true
  layoutWidgets.value.hasRightWidgets = true
})
onUnmounted(() => {
  layoutWidgets.value.hasLeftWidgets = false
  layoutWidgets.value.hasRightWidgets = false
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
        :is-course-admin="isCourseAdmin" 
        :course-member-of-auth="courseMemberOfAuth" 
      />
    </Teleport>

    <Teleport to="#left-widgets-slot">
      <CourseInstructorWidget v-if="course" :course="course" :owner="course.user" />
      <RecentlyViewedCoursesWidget />
      <FavoriteCoursesWidget />
    </Teleport>

    <Teleport to="#right-widgets-slot">
      <CourseInfoWidget
        v-if="course"
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

      <CourseProgressWidget v-if="courseMemberOfAuth" :member="courseMemberOfAuth" :course-id="courseId" />
      
      <CourseSidebar 
        :course="course" 
        :is-admin="isCourseAdmin" 
        :course-member-of-auth="courseMemberOfAuth" 
      />

      <MemberedCoursesWidget />
      <MyCoursesWidget />
    </Teleport>

    <!-- Main Content Area -->
    <slot />
  </div>
</template>

