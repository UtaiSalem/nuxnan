<script setup lang="ts">
import FeedsList from '~/components/course/FeedsList.vue'

// Props from parent route
const props = defineProps<{
  course?: any
  academy?: any
  isCourseAdmin?: boolean
}>()

// Inject from parent if props not passed
const injectedCourse = inject<Ref<any>>('course')
const injectedIsCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')

const course = computed(() => props.course || injectedCourse?.value)
const isCourseAdmin = computed(() => props.isCourseAdmin || injectedIsCourseAdmin?.value)
</script>

<template>
  <div>
    <FeedsList 
      v-if="course?.id"
      :course-id="course.id"
      :is-course-admin="isCourseAdmin"
    />
  </div>
</template>

