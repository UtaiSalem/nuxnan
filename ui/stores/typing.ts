import { defineStore } from 'pinia'
import { ref } from 'vue'

export type GameMode = 'word_typing' | 'time_attack' | 'sentence_typing' | 'monster_battle' | 'falling_words' | 'classroom_race' | 'daily_challenge' | 'key_training'
export type Lang = 'th' | 'en' | 'ar'
export type Difficulty = 'beginner' | 'easy' | 'normal' | 'hard' | 'expert'

export const useTypingStore = defineStore('typing', () => {
  // Config
  const selectedMode       = ref<GameMode>('word_typing')
  const selectedLang       = ref<Lang>('en')
  const selectedDifficulty = ref<Difficulty>('easy')
  const selectedTimeLimit  = ref<number>(60)  // Time Attack in seconds

  // Last result
  const lastResult = ref<{
    session_id: number
    wpm: number
    accuracy: number
    score: number
    max_combo: number
    xp_earned: number
    speed_bonus: number
    combo_bonus: number
    accuracy_bonus: number
  } | null>(null)

  // Personal bests
  const personalBests = ref<Record<string, { wpm: number; score: number }>>({})

  function setResult(result: any) {
    lastResult.value = result
  }

  function setPersonalBests(bests: any) {
    personalBests.value = bests
  }

  function setConfig(mode: GameMode, lang: Lang, difficulty: Difficulty, timeLimit?: number) {
    selectedMode.value = mode
    selectedLang.value = lang
    selectedDifficulty.value = difficulty
    if (timeLimit) selectedTimeLimit.value = timeLimit
  }

  return {
    selectedMode, selectedLang, selectedDifficulty, selectedTimeLimit,
    lastResult, personalBests,
    setResult, setPersonalBests, setConfig,
  }
})
