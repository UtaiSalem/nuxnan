<script setup lang="ts">
import GradebookSubNav from '~/components/learn/course/gradebook/GradebookSubNav.vue'

const route = useRoute()
const courseId = computed(() => route.params.id as string)
</script>

<template>
  <div>
    <GradebookSubNav :course-id="courseId" />
    <NuxtPage />
  </div>
</template>
