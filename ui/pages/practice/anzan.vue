<script setup lang="ts">
import AnzanPractice from '@/components/games/abacus/AnzanPractice.vue'
definePageMeta({ layout: 'main' })
</script>

<template>
  <div class="w-full flex flex-col items-center py-4 sm:py-6 px-4 sm:px-0">
    <div class="w-full max-w-2xl">
      <AnzanPractice />
    </div>
  </div>
</template>
