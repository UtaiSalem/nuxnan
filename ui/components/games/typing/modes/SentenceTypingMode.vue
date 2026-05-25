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
  raceMode?: boolean
  initialWords?: string[]
}

const props = withDefaults(defineProps<Props>(), {
  raceMode: false,
  initialWords: () => []
})

const emit = defineEmits(['finished', 'progress'])

const {
  gameState, words, currentIndex, currentInput, currentWord,
  currentWordChars, extraChars, completedWords, progress,
  wpm, accuracy, combo, maxCombo, mistakes, elapsedSeconds,
  init, startGame, startPlaying, onInput, submitWord, buildResult
} = useTypingGame({
  mode: 'sentence_typing',
  lang: props.lang,
  difficulty: props.difficulty
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

// Special handling for sentence mode: words are submitted on space, but we need to show the whole sentence
// The current useTypingGame logic assumes word-by-word. 
// For sentences, we might want to show the whole sentence and highlight current word.
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
      
      <!-- Idle State -->
      <div v-if="gameState === 'idle'" class="text-center space-y-6">
        <div class="space-y-2">
          <h3 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-wider">Sentence Practice</h3>
          <p class="text-slate-500">Practice typing full sentences and punctuation.</p>
        </div>
        <button 
          @click="startCountdown"
          class="px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white font-black text-xl rounded-2xl shadow-lg shadow-blue-500/30 transition-all hover:scale-105 active:scale-95"
        >
          START PRACTICE
        </button>
      </div>

      <!-- Countdown -->
      <div v-else-if="gameState === 'countdown'" class="text-center">
        <span class="text-9xl font-black text-blue-500 animate-ping">{{ countdown }}</span>
      </div>

      <!-- Playing -->
      <div v-else-if="gameState === 'playing' || gameState === 'finished'" class="w-full space-y-8 animate-in fade-in zoom-in duration-300">
        <TypingComboDisplay :combo="combo" />
        
        <div class="text-center">
          <p class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-2">Sentence {{ currentIndex + 1 }} of {{ words.length }}</p>
          <TypingWordDisplay 
            :chars="currentWordChars"
            :extra-chars="extraChars"
            :current-index="currentIndex"
            :total-words="words.length"
          />
        </div>

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
