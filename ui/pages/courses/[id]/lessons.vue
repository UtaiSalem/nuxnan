<script setup lang="ts">
import LessonsList from '~/components/course/LessonsList.vue'

// Inject course data from parent
const course = inject<Ref<any>>('course')
const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')
</script>

<template>
<div>
    <LessonsList 
      v-if="course?.id"
      :course-id="course.id"
      :is-course-admin="isCourseAdmin"
    />
  </div>
</template>
