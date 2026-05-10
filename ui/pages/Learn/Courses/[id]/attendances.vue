<script setup lang="ts">
import AttendancesList from '~/components/learn/course/AttendancesList.vue'

// Inject course data from parent
const course = inject<Ref<any>>('course')
const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')
</script>

<template>
  <div class="px-3 sm:px-4 lg:px-0 pb-6">
    <AttendancesList
      v-if="course?.id"
      :course-id="course.id"
      :is-course-admin="isCourseAdmin"
    />
  </div>
</template>

