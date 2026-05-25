<script setup>
import SnakeGameComponent from '@/components/games/snake/SnakeGameComponent.vue';

definePageMeta({
  layout: false,
})
</script>

<template>
  <NuxtLayout name="main">
    <div class="w-full h-full flex flex-col items-center justify-center min-h-[calc(100vh-100px)]">
        <div class="w-full max-w-4xl">
             <SnakeGameComponent />
        </div>
    </div>
  </NuxtLayout>
</template>
