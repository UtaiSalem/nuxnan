<template>
  <div class="bg-white p-4 sm:p-6 shadow rounded-lg">
      <h3 class="text-xl font-bold mb-4">Course Information</h3>
      <p>Course Name: {{ course.name }}</p>
      <p>Course Code: {{ course.code }}</p>
      <p>Description: {{ course.description }}</p>
  </div>
</template>
<script setup>
defineProps({
    course: Object,
    isCourseAdmin: Boolean
})
</script>
