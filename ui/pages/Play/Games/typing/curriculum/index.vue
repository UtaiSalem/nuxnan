<script setup lang="ts">
definePageMeta({
  name: 'typing-curriculum-redirect',
  alias: ['/curriculum'],
  middleware: [
    function (to) {
      const query = { ...to.query, mode: 'key_training' }
      return navigateTo({ path: '/play/games/typing/play', query }, { replace: true })
    }
  ]
})
</script>

<template>
  <div class="p-6 text-center text-slate-500">Redirecting to typing training...</div>
</template>
