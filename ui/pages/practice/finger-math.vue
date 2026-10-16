<script setup lang="ts">
import FingerMathPractice from '@/components/games/fingers/FingerMathPractice.vue'
definePageMeta({ layout: 'main' })
</script>

<template>
  <div class="w-full flex flex-col items-center py-4 sm:py-6 px-4 sm:px-0">
    <div class="w-full max-w-2xl">
      <NuxtLink to="/play/games" class="inline-flex items-center gap-1 mb-3 min-h-[36px] text-sm font-medium text-slate-500 hover:text-vikinger-purple dark:text-slate-400 dark:hover:text-vikinger-cyan transition-colors">
        ← กลับหน้าเกม
      </NuxtLink>
      <FingerMathPractice />
    </div>
  </div>
</template>
