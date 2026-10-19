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

      <!-- ครอบแต่ละแท็บด้วย error boundary: ถ้าคอมโพเนนต์ throw จะโชว์ข้อความแทนจอว่างทั้งหน้า -->
      <div v-show="tab === 'game'">
        <NuxtErrorBoundary>
          <MentalMath />
          <template #error="{ error, clearError }">
            <div class="rounded-vikinger-xl border border-rose-300 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/40 p-4 text-center">
              <p class="font-bold text-rose-600 dark:text-rose-300 mb-1">เกิดข้อผิดพลาดในการแสดงผลเกม</p>
              <pre class="text-[11px] text-rose-500 dark:text-rose-400 whitespace-pre-wrap break-words text-left mb-3">{{ error }}</pre>
              <button type="button" @click="clearError" class="min-h-[44px] px-4 rounded-vikinger bg-gradient-vikinger text-white font-bold text-sm shadow-vikinger">ลองใหม่</button>
            </div>
          </template>
        </NuxtErrorBoundary>
      </div>
      <div v-show="tab === 'path'">
        <NuxtErrorBoundary>
          <JintakanitPath />
          <template #error="{ error, clearError }">
            <div class="rounded-vikinger-xl border border-rose-300 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/40 p-4 text-center">
              <p class="font-bold text-rose-600 dark:text-rose-300 mb-1">เกิดข้อผิดพลาดในการแสดงเส้นทางเรียน</p>
              <pre class="text-[11px] text-rose-500 dark:text-rose-400 whitespace-pre-wrap break-words text-left mb-3">{{ error }}</pre>
              <button type="button" @click="clearError" class="min-h-[44px] px-4 rounded-vikinger bg-gradient-vikinger text-white font-bold text-sm shadow-vikinger">ลองใหม่</button>
            </div>
          </template>
        </NuxtErrorBoundary>
      </div>
    </div>
  </div>
</template>
