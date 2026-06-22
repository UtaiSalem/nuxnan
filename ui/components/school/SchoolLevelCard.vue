<script setup lang="ts">
interface Props {
  level: number
  totalXp: number
  xpToNext: number
  progressPct: number
}
defineProps<Props>()
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm p-4">
    <div class="flex items-center gap-3 mb-3">
      <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center text-white font-bold text-sm">
        {{ level }}
      </div>
      <div>
        <div class="font-bold text-sm text-gray-900 dark:text-white">ระดับโรงเรียน</div>
        <div class="text-xs text-gray-500">อีก {{ xpToNext.toLocaleString() }} XP ถึงเลเวล {{ level + 1 }}</div>
      </div>
    </div>
    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
      <div
        class="h-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan transition-all duration-300"
        :style="{ width: `${progressPct}%` }"
      ></div>
    </div>
    <div class="text-right text-[10px] text-gray-400 mt-1">{{ progressPct }}%</div>
  </div>
</template>
