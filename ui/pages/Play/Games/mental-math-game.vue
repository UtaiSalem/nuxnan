<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'
import MentalMath from '@/components/games/mentalmath/MentalMath.vue'
import JintakanitPath from '@/components/games/JintakanitPath.vue'
definePageMeta({ layout: 'main' })

const tab = ref<'game' | 'path'>('game')
</script>

<template>
  <div class="w-full flex flex-col items-center min-h-[calc(100vh-100px)] px-4 sm:px-0 py-4 sm:py-6">
    <div class="w-full max-w-2xl">
      <NuxtLink to="/play/games" class="inline-flex items-center gap-1 mb-3 min-h-[36px] text-sm font-medium text-slate-500 hover:text-vikinger-purple dark:text-slate-400 dark:hover:text-vikinger-cyan transition-colors">
        ← กลับหน้าเกม
      </NuxtLink>

      <!-- Tabs -->
      <div class="grid grid-cols-2 gap-1.5 mb-4 p-1 rounded-vikinger bg-gray-100 dark:bg-vikinger-dark-200/60">
        <button
          @click="tab = 'game'"
          type="button"
          :class="[
            'min-h-[44px] rounded-vikinger font-semibold text-sm flex items-center justify-center gap-1.5 transition-all',
            tab === 'game'
              ? 'bg-white dark:bg-vikinger-dark-100 text-vikinger-purple dark:text-vikinger-cyan shadow-sm'
              : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'
          ]"
        >
          <Icon icon="fluent:flash-24-filled" class="w-4 h-4 shrink-0" />
          <span class="whitespace-nowrap">คิดเลขเร็ว</span>
        </button>
        <button
          @click="tab = 'path'"
          type="button"
          :class="[
            'min-h-[44px] rounded-vikinger font-semibold text-sm flex items-center justify-center gap-1.5 transition-all',
            tab === 'path'
              ? 'bg-white dark:bg-vikinger-dark-100 text-vikinger-purple dark:text-vikinger-cyan shadow-sm'
              : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'
          ]"
        >
          <Icon icon="fluent:hat-graduation-24-filled" class="w-4 h-4 shrink-0" />
          <span class="whitespace-nowrap">เส้นทางเรียน</span>
        </button>
      </div>

      <MentalMath v-show="tab === 'game'" />
      <JintakanitPath v-show="tab === 'path'" />
    </div>
  </div>
</template>
