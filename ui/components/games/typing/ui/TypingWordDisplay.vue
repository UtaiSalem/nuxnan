<script setup lang="ts">
import type { CharState } from '~/composables/useTypingGame'

interface Props {
  chars: CharState[]
  extraChars: CharState[]
  currentIndex: number
  totalWords: number
  allWords?: string[]
}

const props = withDefaults(defineProps<Props>(), {
  allWords: () => []
})

// Show a sliding window: 2 completed, current, up to 4 upcoming
const displayWindow = computed(() => {
  if (!props.allWords.length) return { past: [], upcoming: [] }
  const pastStart = Math.max(0, props.currentIndex - 2)
  const past = props.allWords.slice(pastStart, props.currentIndex)
  const upcoming = props.allWords.slice(props.currentIndex + 1, props.currentIndex + 5)
  return { past, upcoming }
})

const progressPct = computed(() =>
  props.totalWords > 0
    ? Math.round((props.currentIndex / props.totalWords) * 100)
    : 0
)
</script>

<template>
  <div class="relative py-6 px-4 min-h-[140px] flex flex-col items-center gap-4 transition-all duration-300">
    <!-- Progress Bar -->
    <div class="absolute top-0 left-0 w-full h-1 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
      <div
        class="h-full bg-primary-500 transition-all duration-300"
        :style="{ width: `${progressPct}%` }"
      ></div>
    </div>

    <!-- Word count badge -->
    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">
      Word {{ currentIndex + 1 }} / {{ totalWords }}
    </div>

    <!-- Words flow: past · current · upcoming -->
    <div class="flex flex-wrap items-baseline justify-center gap-x-3 gap-y-1 font-mono text-2xl md:text-4xl leading-relaxed">
      <!-- Past words (faded) -->
      <span
        v-for="(word, i) in displayWindow.past"
        :key="`past-${i}`"
        class="text-slate-300 dark:text-slate-700 line-through"
      >{{ word }}</span>

      <!-- Current word (character-level highlighting) -->
      <span class="relative inline-flex items-baseline gap-x-0 px-1 rounded-lg bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-700">
        <span
          v-for="(char, index) in chars"
          :key="index"
          class="relative inline-block transition-colors duration-100"
          :class="{
            'text-slate-600 dark:text-slate-300': char.status === 'pending',
            'text-green-500 dark:text-green-400': char.status === 'correct',
            'text-red-500 underline decoration-2': char.status === 'incorrect',
          }"
        >
          {{ char.char }}
          <!-- Blinking caret before first untyped char -->
          <span
            v-if="index === chars.filter(c => c.status !== 'pending').length && chars.every(c => c.status !== 'extra')"
            class="absolute -left-0.5 top-0 bottom-0 w-0.5 bg-primary-500 animate-pulse rounded-full"
          ></span>
        </span>

        <!-- Extra (overflow) characters -->
        <span
          v-for="(char, index) in extraChars"
          :key="'extra-' + index"
          class="text-red-600 bg-red-100 dark:bg-red-900/40 rounded px-0.5 text-sm"
        >{{ char.char }}</span>
      </span>

      <!-- Upcoming words (grayed) -->
      <span
        v-for="(word, i) in displayWindow.upcoming"
        :key="`next-${i}`"
        class="text-slate-400 dark:text-slate-500"
      >{{ word }}</span>
    </div>
  </div>
</template>

<style scoped>
.font-mono {
  font-family: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace;
}
</style>
