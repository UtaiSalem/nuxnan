<script setup lang="ts">
import MentalMath from '@/components/games/mentalmath/MentalMath.vue'
definePageMeta({ layout: false })
</script>

<template>
  <NuxtLayout name="main">
    <div class="w-full h-full flex flex-col items-center justify-center min-h-[calc(100vh-100px)]">
      <div class="w-full max-w-2xl">
        <MentalMath />
      </div>
    </div>
  </NuxtLayout>
</template>
