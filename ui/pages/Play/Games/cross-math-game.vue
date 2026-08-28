<script setup>
import CrossMath from '@/components/games/crossmath/CrossMath.vue'
definePageMeta({
  layout: 'main',
})
</script>

<template>
  <div class="w-full h-full flex flex-col items-center justify-center min-h-[calc(100vh-100px)] px-4 sm:px-0">
    <div class="w-full max-w-4xl">
      <CrossMath />
    </div>
  </div>
</template>

<style scoped>
/* Additional styles if needed */
</style>
