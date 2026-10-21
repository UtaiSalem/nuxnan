<script setup lang="ts">
import { Icon } from '@iconify/vue'
const props = defineProps<{ combo: number }>()
const { t } = useI18n()
const milestone = computed(() => (Math.floor(props.combo / 5) + 1) * 5)
const label = computed(() => t(props.combo >= 20 ? 'typingArena.comboEpic' : props.combo >= 10 ? 'typingArena.comboGreat' : props.combo >= 5 ? 'typingArena.comboNice' : 'typingArena.comboHint'))
</script>

<template>
  <div class="flex items-center justify-between gap-2 rounded-vikinger-lg border border-vikinger-purple/20 bg-vikinger-purple/5 p-3 dark:bg-vikinger-purple/10">
    <div class="flex min-w-0 flex-1 items-center gap-2">
      <Icon icon="heroicons:fire" class="shrink-0 text-2xl" :class="combo >= 5 ? 'text-orange-500' : 'text-vikinger-purple dark:text-vikinger-cyan'" />
      <div class="min-w-0"><p class="text-sm font-bold text-vikinger-dark dark:text-white">{{ label }}</p><p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ t('typingArena.nextCombo', { count: milestone }) }}</p></div>
    </div>
    <div class="flex shrink-0 flex-col-reverse items-end gap-1 sm:flex-row sm:items-center sm:gap-3">
      <div class="flex gap-1" aria-hidden="true"><span v-for="n in 5" :key="n" class="h-1.5 w-2.5 rounded-full sm:w-5 transition-colors motion-reduce:transition-none" :class="n <= combo % 5 || (combo > 0 && combo % 5 === 0) ? 'bg-vikinger-cyan' : 'bg-vikinger-purple/20'"></span></div>
      <span :key="combo" class="combo-value text-2xl font-black tabular-nums text-vikinger-purple dark:text-vikinger-cyan">{{ combo }}×</span>
    </div>
  </div>
</template>

<style scoped>
.combo-value { animation: combo-pop .2s ease-out; }
@keyframes combo-pop { from { transform: scale(1.15); } to { transform: scale(1); } }
@media (prefers-reduced-motion: reduce) { .combo-value { animation: none; } }
</style>
