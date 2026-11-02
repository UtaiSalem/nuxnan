<script setup lang="ts">
definePageMeta({
  name: 'typing-curriculum-stage-redirect',
  alias: ['/curriculum/:stageId'],
  middleware: [
    function (to) {
      const stageId = to.params.stageId
      const query = { ...to.query, mode: 'key_training', stageId }
      return navigateTo({ path: '/play/games/typing/play', query }, { replace: true })
    }
  ]
})
</script>

<template>
  <div class="p-6 text-center text-slate-500">Redirecting to typing stage...</div>
</template>
