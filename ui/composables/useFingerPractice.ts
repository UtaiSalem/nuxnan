/**
 * useFingerPractice — สถานะเซสชันเกมนิ้วมือ (ด่าน 1–2)
 * ใช้ FingerExerciseService (mock) — ไม่เรียก API ตรง
 */
import { ref, computed } from 'vue'
import {
  createMockFingerExerciseService,
  type FingerExercise,
  type FingerExerciseService,
  type FingerStage,
  type FingerMode
} from '~/services/fingers/fingerExerciseService'

export type FingerPhase = 'idle' | 'answering' | 'correct' | 'wrong'

export function useFingerPractice(service: FingerExerciseService = createMockFingerExerciseService()) {
  const stage = ref<FingerStage>(1)
  const mode = ref<FingerMode>('read')

  const exercise = ref<FingerExercise | null>(null)
  const selected = ref<number | null>(null)
  const phase = ref<FingerPhase>('idle')

  const correctCount = ref(0)
  const totalCount = ref(0)
  const streak = ref(0)
  const bestStreak = ref(0)

  const accuracy = computed(() =>
    totalCount.value === 0 ? 0 : Math.round((correctCount.value / totalCount.value) * 100)
  )

  function nextExercise() {
    exercise.value = service.next(stage.value, mode.value)
    selected.value = null
    phase.value = 'answering'
  }

  function start() {
    correctCount.value = 0
    totalCount.value = 0
    streak.value = 0
    bestStreak.value = 0
    nextExercise()
  }

  function choose(n: number) {
    if (!exercise.value || phase.value !== 'answering') return
    selected.value = n
    totalCount.value += 1
    if (service.check(exercise.value, n)) {
      correctCount.value += 1
      streak.value += 1
      if (streak.value > bestStreak.value) bestStreak.value = streak.value
      phase.value = 'correct'
    } else {
      streak.value = 0
      phase.value = 'wrong'
    }
  }

  function setStage(s: FingerStage) {
    stage.value = s
    nextExercise()
  }

  function setMode(m: FingerMode) {
    mode.value = m
    nextExercise()
  }

  return {
    stage,
    mode,
    exercise,
    selected,
    phase,
    correctCount,
    totalCount,
    streak,
    bestStreak,
    accuracy,
    start,
    nextExercise,
    choose,
    setStage,
    setMode
  }
}
