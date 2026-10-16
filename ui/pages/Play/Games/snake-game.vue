<script setup>
import SnakeGameComponent from '@/components/games/snake/SnakeGameComponent.vue';

definePageMeta({
  layout: 'main',
})
</script>

<template>
  <div class="w-full h-full flex flex-col items-center justify-center min-h-[calc(100vh-100px)] px-4 sm:px-0">
    <div class="w-full max-w-4xl">
      <NuxtLink to="/play/games" class="inline-flex items-center gap-1 mb-3 min-h-[36px] text-sm font-medium text-slate-500 hover:text-vikinger-purple dark:text-slate-400 dark:hover:text-vikinger-cyan transition-colors">
        ← กลับหน้าเกม
      </NuxtLink>
      <SnakeGameComponent />
    </div>
  </div>
</template>
