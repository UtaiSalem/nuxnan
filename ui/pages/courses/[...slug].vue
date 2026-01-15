<script setup lang="ts">
const route = useRoute()

definePageMeta({
  middleware: [
    function (to, from) {
      // Get the path params
      const slug = to.params.slug
      const targetPath = Array.isArray(slug) ? slug.join('/') : slug || ''
      
      // Redirect to /Learn/Courses/... (matching folder structure)
      return navigateTo(`/Learn/Courses/${targetPath}`)
    }
  ]
})
</script>

<template>
  <div>Redirecting to course...</div>
</template>
