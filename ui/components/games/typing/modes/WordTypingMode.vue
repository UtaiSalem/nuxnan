<script setup lang="ts">
import TypingStatsBar from '../ui/TypingStatsBar.vue'
import TypingWordDisplay from '../ui/TypingWordDisplay.vue'
import TypingInput from '../ui/TypingInput.vue'
import TypingComboDisplay from '../ui/TypingComboDisplay.vue'
import { useTypingGame } from '~/composables/useTypingGame'
import type { Lang, Difficulty } from '~/stores/typing'

interface Props {
  lang: Lang
  difficulty: Difficulty
  wordCount?: number
  raceMode?: boolean
  initialWords?: string[]
}

const props = withDefaults(defineProps<Props>(), {
  wordCount: 20,
  raceMode: false,
  initialWords: () => []
})

const emit = defineEmits(['finished', 'progress'])

const {
  gameState, words, currentIndex, currentInput, currentWord,
  currentWordChars, extraChars, completedWords, progress,
  wpm, accuracy, combo, maxCombo, mistakes, elapsedSeconds,
  isLoading, loadError,
  init, startGame, startPlaying, onInput, submitWord, buildResult
} = useTypingGame({
  mode: 'word_typing',
  lang: props.lang,
  difficulty: props.difficulty,
  wordCount: props.wordCount
})

onMounted(async () => {
  await init(props.initialWords)
})

watch(progress, (p) => {
  if (props.raceMode) {
    emit('progress', p, wpm.value)
  }
})

const countdown = ref(3)
const countdownTimer = ref<any>(null)

function startCountdown() {
  gameState.value = 'countdown'
  countdown.value = 3
  countdownTimer.value = setInterval(() => {
    countdown.value--
    if (countdown.value === 0) {
      clearInterval(countdownTimer.value)
      startPlaying()
    }
  }, 1000)
}

watch(gameState, (newVal) => {
  if (newVal === 'finished') {
    emit('finished', buildResult())
  }
})
</script>

<template>
  <div class="max-w-4xl mx-auto space-y-8">
    <!-- Stats -->
    <TypingStatsBar 
      :wpm="wpm"
      :accuracy="accuracy"
      :elapsed-seconds="elapsedSeconds"
      :mistakes="mistakes"
    />

    <div class="relative bg-slate-50 dark:bg-slate-900/50 rounded-3xl p-8 border border-slate-200 dark:border-slate-800 shadow-inner min-h-[400px] flex flex-col items-center justify-center overflow-hidden">
      
      <!-- Loading State -->
      <div v-if="isLoading" class="text-center space-y-4">
        <svg class="animate-spin h-12 w-12 text-primary-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <p class="text-slate-500 font-bold uppercase tracking-widest text-sm animate-pulse">Loading words...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="loadError" class="text-center space-y-4">
        <div class="text-5xl">⚠️</div>
        <p class="text-red-500 font-bold">{{ loadError }}</p>
        <button
          @click="init()"
          class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl transition-all hover:scale-105 active:scale-95"
        >
          Try Again
        </button>
      </div>

      <!-- Idle State -->
      <div v-else-if="gameState === 'idle'" class="text-center space-y-6">
        <div class="space-y-2">
          <h3 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-wider">Ready to Type?</h3>
          <p class="text-slate-500">Mode: Word Typing · {{ words.length }} words loaded</p>
        </div>
        <button
          @click="startCountdown"
          :disabled="words.length === 0"
          class="px-8 py-4 bg-primary-600 hover:bg-primary-700 disabled:bg-slate-400 disabled:cursor-not-allowed text-white font-black text-xl rounded-2xl shadow-lg shadow-primary-500/30 transition-all hover:scale-105 active:scale-95 disabled:scale-100"
        >
          START GAME
        </button>
      </div>

      <!-- Countdown -->
      <div v-else-if="gameState === 'countdown'" class="text-center">
        <span class="text-9xl font-black text-primary-500 animate-ping">{{ countdown }}</span>
      </div>

      <!-- Playing -->
      <div v-else-if="gameState === 'playing' || gameState === 'finished'" class="w-full space-y-8 animate-in fade-in zoom-in duration-300">
        <TypingComboDisplay :combo="combo" />
        
        <TypingWordDisplay
          :chars="currentWordChars"
          :extra-chars="extraChars"
          :current-index="currentIndex"
          :total-words="words.length"
          :all-words="words"
        />

        <TypingInput 
          v-model="currentInput"
          :disabled="gameState === 'finished'"
          autofocus
          @submit="submitWord"
          @update:model-value="onInput"
        />
      </div>

    </div>
  </div>
</template>
