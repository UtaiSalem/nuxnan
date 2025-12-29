<script setup lang="ts">
import AttendancesList from '~/components/course/AttendancesList.vue'

// Inject course data from parent
const course = inject<Ref<any>>('course')
const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')
</script>

<template>
  <div>
    <AttendancesList 
      v-if="course?.id"
      :course-id="course.id"
      :is-course-admin="isCourseAdmin"
    />
  </div>
</template>

