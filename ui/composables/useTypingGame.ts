import { ref, computed, watch, onUnmounted } from 'vue'
import { useWpmCalc } from './useWpmCalc'
import { useComboSystem } from './useComboSystem'
import { useTypingApi } from './useTypingApi'
import type { GameMode, Lang, Difficulty } from '~/stores/typing'

export interface GameConfig {
  mode: GameMode
  lang: Lang
  difficulty: Difficulty
  timeLimit?: number
  wordCount?: number
}

export interface CharState {
  char: string
  status: 'pending' | 'correct' | 'incorrect' | 'extra'
}

export function useTypingGame(config: GameConfig) {
  // ── State ──────────────────────────────────────────────────────
  const sessionToken   = ref(crypto.randomUUID())
  const gameState      = ref<'idle' | 'countdown' | 'playing' | 'finished'>('idle')
  const words          = ref<string[]>([])
  const currentIndex   = ref(0)
  const currentInput   = ref('')
  const completedWords = ref<{ word: string; typed: string; correct: boolean }[]>([])
  const mistakes       = ref(0)
  const correctChars   = ref(0)
  const totalChars     = ref(0)
  const isLoading      = ref(false)
  const loadError      = ref<string | null>(null)

  // ── Sub-composables ────────────────────────────────────────────
  const { wpm, startTimer, stopTimer, elapsedSeconds, updateCorrectChars, reset: resetWpm } = useWpmCalc()
  const { combo, maxCombo, addCombo, breakCombo, reset: resetCombo } = useComboSystem()
  const { fetchWords, fetchSentences, submitSession } = useTypingApi()

  // ── Computed ───────────────────────────────────────────────────
  const accuracy = computed(() => {
    if (totalChars.value === 0) return 100
    return Math.round((correctChars.value / totalChars.value) * 100)
  })

  const currentWord = computed(() => words.value[currentIndex.value] ?? '')

  // Character-level state for highlighting
  const currentWordChars = computed<CharState[]>(() => {
    const word = currentWord.value
    const input = currentInput.value
    return word.split('').map((char, i) => ({
      char,
      status: i >= input.length
        ? 'pending'
        : input[i] === char
          ? 'correct'
          : 'incorrect'
    }))
  })

  const extraChars = computed<CharState[]>(() => {
    const excess = currentInput.value.slice(currentWord.value.length)
    return excess.split('').map(char => ({ char, status: 'extra' as const }))
  })

  const progress = computed(() =>
    words.value.length > 0
      ? Math.round((currentIndex.value / words.value.length) * 100)
      : 0
  )

  // ── Methods ────────────────────────────────────────────────────
  async function init(initialWords?: string[]) {
    reset()
    isLoading.value = true
    loadError.value = null

    if (initialWords && initialWords.length > 0) {
      words.value = initialWords
      isLoading.value = false
      return
    }

    try {
      if (config.mode === 'sentence_typing') {
        const sentences = await fetchSentences(config.lang, config.difficulty, 5)
        words.value = sentences.map((s: any) => s.text)
      } else {
        const count = config.wordCount ?? 20
        const fetched = await fetchWords(config.lang, config.difficulty, count)
        words.value = fetched.map((w: any) => w.text)
      }

      if (words.value.length === 0) {
        loadError.value = `No words found for "${config.lang}" / "${config.difficulty}". Try a different language or difficulty.`
      }
    } catch (err) {
      console.error('[useTypingGame] Failed to load words:', err)
      loadError.value = 'Failed to load words. Check your connection and try again.'
    } finally {
      isLoading.value = false
    }
  }

  function reset() {
    sessionToken.value = crypto.randomUUID()
    gameState.value = 'idle'
    currentIndex.value = 0
    currentInput.value = ''
    completedWords.value = []
    mistakes.value = 0
    correctChars.value = 0
    totalChars.value = 0
    resetWpm()
    resetCombo()
  }

  function startGame() {
    gameState.value = 'countdown'
    // Delay for countdown can be handled by UI, here we just start playing
  }

  function startPlaying() {
    gameState.value = 'playing'
    startTimer()
  }

  function onInput(value: string) {
    if (gameState.value !== 'playing') return

    const prevLength = currentInput.value.length
    currentInput.value = value

    if (value.length > prevLength) {
      totalChars.value++

      const lastChar = value.slice(-1)
      const expectedChar = currentWord.value[value.length - 1]

      if (lastChar === expectedChar) {
        correctChars.value++
        updateCorrectChars(correctChars.value)
      } else {
        mistakes.value++
        breakCombo()
      }
    }
  }

  function submitWord() {
    if (gameState.value !== 'playing') return
    const typed  = currentInput.value.trim()
    const word   = currentWord.value
    const correct = typed === word

    completedWords.value.push({ word, typed, correct })

    if (correct) {
      addCombo()
    } else {
      // already called breakCombo in onInput if char was wrong, 
      // but if they just hit space early, it's a mistake too.
      if (typed !== word) breakCombo()
    }

    currentInput.value = ''
    currentIndex.value++

    if (currentIndex.value >= words.value.length) {
      endGame()
    }
  }

  function endGame() {
    stopTimer()
    gameState.value = 'finished'
  }

  // Auto-end for Time Attack
  watch(elapsedSeconds, (secs) => {
    if (config.mode === 'time_attack' && config.timeLimit) {
      if (secs >= config.timeLimit) endGame()
    }
  })

  function buildResult() {
    return {
      session_token: sessionToken.value,
      game_mode:     config.mode,
      language:      config.lang,
      difficulty:    config.difficulty,
      correct_chars: correctChars.value,
      total_chars:   totalChars.value,
      correct_words: completedWords.value.filter(w => w.correct).length,
      total_words:   completedWords.value.length,
      mistakes:      mistakes.value,
      max_combo:     maxCombo.value,
      time_elapsed:  elapsedSeconds.value,
      time_limit:    config.timeLimit ?? null,
    }
  }

  onUnmounted(() => stopTimer())

  return {
    gameState, words, currentIndex, currentInput, currentWord,
    currentWordChars, extraChars, completedWords, progress,
    wpm, accuracy, combo, maxCombo, mistakes, elapsedSeconds,
    isLoading, loadError,
    init, startGame, startPlaying, onInput, submitWord, endGame, buildResult, reset
  }
}
