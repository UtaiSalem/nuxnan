<script setup lang="ts">
import { Icon } from '@iconify/vue'

type ArenaMode = 'word_typing' | 'time_attack' | 'sentence_typing' | 'monster_battle' | 'falling_words' | 'letter_runner'
const props = defineProps<{ mode: ArenaMode; progress?: number }>()
const { t } = useI18n()
const icons: Record<ArenaMode, string> = {
  word_typing: 'heroicons:bolt', time_attack: 'heroicons:clock',
  sentence_typing: 'heroicons:chat-bubble-bottom-center-text', monster_battle: 'heroicons:fire',
  falling_words: 'heroicons:sparkles', letter_runner: 'heroicons:arrow-right-circle',
}
const percent = computed(() => Math.min(100, Math.max(0, props.progress ?? 0)))
</script>

<template>
  <header class="relative overflow-hidden rounded-vikinger-xl border border-vikinger-purple/20 bg-white p-4 shadow-vikinger dark:bg-vikinger-dark-100 sm:p-6">
    <div aria-hidden="true" class="pointer-events-none absolute -right-12 -top-20 h-56 w-56 rounded-full bg-gradient-vikinger opacity-10"></div>
    <div class="relative flex items-start gap-3 sm:gap-4">
      <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-vikinger bg-gradient-vikinger text-2xl text-white shadow-vikinger sm:h-14 sm:w-14">
        <Icon :icon="icons[mode]" />
      </div>
      <div class="min-w-0 flex-1 space-y-1">
        <p class="text-[10px] font-black uppercase tracking-[.2em] text-vikinger-purple dark:text-vikinger-cyan">{{ t('typingArena.arena') }}</p>
        <h1 class="text-xl font-black text-vikinger-dark dark:text-white sm:text-2xl">{{ t(`typingArena.${mode}`) }}</h1>
        <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-300 sm:text-sm">{{ t(`typingArena.${mode}Hint`) }}</p>
      </div>
    </div>
    <div v-if="progress !== undefined" class="relative mt-5 space-y-2">
      <div class="flex justify-between gap-2 text-xs font-bold text-slate-600 dark:text-slate-300">
        <span>{{ t('typingArena.progress') }}</span><span class="tabular-nums text-vikinger-purple dark:text-vikinger-cyan">{{ percent }}%</span>
      </div>
      <div role="progressbar" :aria-label="t('typingArena.progress')" :aria-valuenow="percent" aria-valuemin="0" aria-valuemax="100" class="h-2 overflow-hidden rounded-full bg-vikinger-light-300 dark:bg-vikinger-dark-300">
        <div class="h-full rounded-full bg-gradient-vikinger transition-[width] duration-300 motion-reduce:transition-none" :style="{ width: `${percent}%` }"></div>
      </div>
    </div>
  </header>
</template>
