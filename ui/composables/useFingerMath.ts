/**
 * useFingerMath — ด่าน 6: คิดเลขบวก/ลบ แล้วตอบด้วยท่ามือ
 * ใช้ FingerMathService (mock) — ไม่เรียก API ตรง
 */
import { ref, computed, onScopeDispose } from 'vue'
import type { Operation } from '~/components/games/abacus/abacusEngine'
import {
  createMockFingerMathService,
  type FingerMathExercise,
  type FingerMathService,
  type FingerMathStage
} from '~/services/fingers/fingerMathService'
import { useLessonProgress } from '~/composables/useLessonProgress'

export type FingerMathPhase = 'idle' | 'answering' | 'correct' | 'wrong'

export function useFingerMath(service: FingerMathService = createMockFingerMathService()) {
  const stage = ref<FingerMathStage>(1)
  const ops = ref<Operation[]>(['+'])

  const exercise = ref<FingerMathExercise | null>(null)
  const selected = ref<number | null>(null)
  const phase = ref<FingerMathPhase>('idle')

  const correctCount = ref(0)
  const totalCount = ref(0)
  const streak = ref(0)
  const bestStreak = ref(0)
  const progress = useLessonProgress()

  const accuracy = computed(() =>
    totalCount.value === 0 ? 0 : Math.round((correctCount.value / totalCount.value) * 100)
  )

  // หน่วงเวลาไปข้อถัดไปอัตโนมัติเมื่อตอบถูก (เคลียร์เมื่อไปข้อถัดไปเอง/เลิกใช้งาน)
  const AUTO_NEXT_MS = 900
  let autoNextTimer: ReturnType<typeof setTimeout> | null = null
  function clearAutoNext() {
    if (autoNextTimer) {
      clearTimeout(autoNextTimer)
      autoNextTimer = null
    }
  }

  function nextExercise() {
    clearAutoNext()
    exercise.value = service.next(stage.value, ops.value)
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
      // ตอบถูก → ไปข้อถัดไปเองอัตโนมัติ
      clearAutoNext()
      autoNextTimer = setTimeout(() => nextExercise(), AUTO_NEXT_MS)
    } else {
      streak.value = 0
      phase.value = 'wrong'
    }
    progress.record('finger-math', phase.value === 'correct', streak.value)
  }

  onScopeDispose(clearAutoNext)

  function setStage(s: FingerMathStage) {
    stage.value = s
    nextExercise()
  }

  function toggleOp(op: Operation) {
    if (ops.value.includes(op)) {
      if (ops.value.length > 1) ops.value = ops.value.filter((o) => o !== op)
    } else {
      ops.value = [...ops.value, op]
    }
    nextExercise()
  }

  return {
    stage,
    ops,
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
    toggleOp
  }
}
