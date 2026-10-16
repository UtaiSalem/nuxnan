<script setup lang="ts">
import AbacusPractice from '@/components/games/abacus/AbacusPractice.vue'
definePageMeta({ layout: 'main' })
</script>

<template>
  <div class="w-full flex flex-col items-center py-4 sm:py-6 px-4 sm:px-0">
    <div class="w-full max-w-2xl">
      <AbacusPractice />
    </div>
  </div>
</template>
