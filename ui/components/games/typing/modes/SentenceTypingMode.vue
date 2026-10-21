<script setup lang="ts">
import TypingModeHeader from '../ui/TypingModeHeader.vue'
import TypingStatsBar from '../ui/TypingStatsBar.vue'
import TypingWordDisplay from '../ui/TypingWordDisplay.vue'
import TypingInput from '../ui/TypingInput.vue'
import TypingComboDisplay from '../ui/TypingComboDisplay.vue'
import { useTypingGame } from '~/composables/useTypingGame'
import type { Lang, Difficulty } from '~/stores/typing'

interface Props {
  lang: Lang
  difficulty: Difficulty
  raceMode?: boolean
  initialWords?: string[]
  autoStart?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  raceMode: false,
  initialWords: () => [],
  autoStart: false
})

const { t } = useI18n()

const emit = defineEmits(['finished', 'progress'])

const {
  gameState, words, currentIndex, currentInput,
  currentWordChars, extraChars, progress,
  wpm, accuracy, combo, mistakes, elapsedSeconds,
  isLoading, loadError,
  init, startPlaying, onInput, submitWord, buildResult
} = useTypingGame({
  mode: 'sentence_typing',
  lang: props.lang,
  difficulty: props.difficulty
})

let disposed = false

onMounted(async () => {
  await init(props.initialWords)
  if (props.autoStart && !loadError.value && words.value.length > 0) startCountdown()
})

watch(progress, (p) => {
  if (props.raceMode) {
    emit('progress', p, wpm.value)
  }
})

const countdown = ref(3)
let countdownTimer: ReturnType<typeof setInterval> | undefined

function startCountdown() {
  if (disposed || isLoading.value || loadError.value || !words.value.length) return
  clearInterval(countdownTimer)
  gameState.value = 'countdown'
  countdown.value = 3
  countdownTimer = setInterval(() => {
    countdown.value--
    if (countdown.value === 0) {
      clearInterval(countdownTimer)
      startPlaying()
    }
  }, 1000)
}

watch(gameState, (newVal) => {
  if (newVal === 'finished') {
    emit('finished', buildResult())
  }
})

async function retry() {
  await init(props.initialWords)
  if (props.autoStart && !loadError.value && words.value.length > 0) startCountdown()
}

onUnmounted(() => { disposed = true; clearInterval(countdownTimer) })
</script>

<template>
  <div class="mx-auto max-w-4xl space-y-4">
    <TypingModeHeader mode="sentence_typing" :progress="progress" />
    <TypingStatsBar :wpm="wpm" :accuracy="accuracy" :elapsed-seconds="elapsedSeconds" :mistakes="mistakes" />
    <section class="relative min-h-[340px] overflow-hidden rounded-vikinger-xl border border-vikinger-purple/20 bg-white p-4 shadow-vikinger dark:bg-vikinger-dark-100 sm:p-7">
      <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-vikinger"></div>
      <div v-if="isLoading" role="status" class="flex min-h-[280px] flex-col items-center justify-center gap-4 text-center">
        <span class="h-10 w-10 animate-spin rounded-full border-4 border-vikinger-purple/20 border-t-vikinger-purple motion-reduce:animate-none"></span>
        <p class="font-bold text-slate-600 dark:text-slate-300">{{ t('typingArena.loading') }}</p>
      </div>
      <div v-else-if="loadError" role="alert" class="flex min-h-[280px] flex-col items-center justify-center gap-4 text-center">
        <p class="font-bold text-rose-600 dark:text-vikinger-pink">{{ t('typingArena.loadError') }}</p>
        <button class="min-h-[44px] rounded-vikinger bg-gradient-vikinger px-6 py-3 font-bold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-vikinger-purple" @click="retry">{{ t('typingArena.retry') }}</button>
      </div>
      <div v-else-if="gameState === 'idle'" class="flex min-h-[280px] flex-col items-center justify-center gap-6 text-center">
        <h2 class="text-xl font-black text-vikinger-dark dark:text-white">{{ t('typingArena.ready') }}</h2>
        <button :disabled="!words.length" class="min-h-[48px] rounded-vikinger bg-gradient-vikinger px-8 py-4 text-lg font-black text-white shadow-vikinger transition-transform hover:scale-105 disabled:opacity-50 motion-reduce:transform-none" @click="startCountdown">{{ t('typingArena.start') }}</button>
      </div>
      <div v-else-if="gameState === 'countdown'" role="status" aria-live="polite" class="flex min-h-[280px] flex-col items-center justify-center gap-5 text-center">
        <p class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ t('typingArena.countdown') }}</p>
        <span :key="countdown" class="flex h-32 w-32 items-center justify-center rounded-full border-4 border-vikinger-cyan/40 bg-vikinger-purple/10 text-7xl font-black text-vikinger-purple shadow-vikinger dark:text-vikinger-cyan">{{ countdown }}</span>
      </div>
      <div v-else class="space-y-4 sm:space-y-6">
        <TypingComboDisplay :combo="combo" />
        <TypingWordDisplay :chars="currentWordChars" :extra-chars="extraChars" :current-index="currentIndex" :total-words="words.length" item-kind="sentence" />
        <TypingInput :model-value="currentInput" :disabled="gameState === 'finished'" autofocus submit-on="enter" @submit="submitWord" @update:model-value="onInput" />
        <p v-if="gameState === 'finished'" role="status" class="text-center text-sm font-bold text-vikinger-purple dark:text-vikinger-cyan">{{ t('typingArena.saving') }}</p>
      </div>
    </section>
  </div>
</template>
