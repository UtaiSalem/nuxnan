<script setup>
import SnakeGameComponent from '@/components/games/snake/SnakeGameComponent.vue';

definePageMeta({
  layout: 'main',
})
</script>

<template>
  <div class="w-full h-full flex flex-col items-center justify-center min-h-[calc(100vh-100px)] px-4 sm:px-0">
    <div class="w-full max-w-4xl">
      <SnakeGameComponent />
    </div>
  </div>
</template>
