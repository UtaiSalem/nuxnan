<script setup lang="ts">
const route = useRoute()

definePageMeta({
  name: 'courses-legacy-redirect',
  middleware: [
    function (to, from) {
      // Get the path params (handles 'path' from [...path] or fallback 'slug')
      const param = to.params.path || to.params.slug
      const targetPath = Array.isArray(param) ? param.join('/') : param || ''
      
      // Redirect to /Learn/Courses/... (matching folder structure)
      return navigateTo(`/Learn/Courses/${targetPath}`)
    }
  ]
})
</script>

<template>
  <div>Redirecting to course...</div>
</template>
