<script setup lang="ts">
import AssignmentsList from '~/components/course/AssignmentsList.vue'

// Inject course data from parent
const course = inject<Ref<any>>('course')
const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')
</script>

<template>
  <div>
    <AssignmentsList 
      v-if="course?.id"
      :course-id="course.id"
      :is-course-admin="isCourseAdmin"
    />
  </div>
</template>
