/**
 * useAbacusPractice — สถานะเซสชันฝึกลูกคิด (แผน S4/S5)
 * โจทย์ → เลื่อนเม็ดสร้างคำตอบ → ตรวจ → ตอบผิดโชว์คำใบ้ → ข้อถัดไป
 * ใช้ ExerciseService (mock) + engine (getHint/setAbacusValue) — ไม่เรียก API ตรง
 */
import { ref, computed } from 'vue'
import type { Operation } from '~/components/games/abacus/abacusEngine'
import { getHint, setAbacusValue } from '~/components/games/abacus/abacusEngine'
import {
  createMockExerciseService,
  type AbacusExercise,
  type AbacusExerciseService,
  type AbacusLevel
} from '~/services/abacus/exerciseService'

export type PracticePhase = 'idle' | 'answering' | 'correct' | 'wrong'

export function useAbacusPractice(service: AbacusExerciseService = createMockExerciseService()) {
  const level = ref<AbacusLevel>(1)
  const ops = ref<Operation[]>(['+'])
  const hideAbacus = ref(false)

  const exercise = ref<AbacusExercise | null>(null)
  const userValue = ref(0)
  const phase = ref<PracticePhase>('idle')
  const hints = ref<string[]>([])

  const correctCount = ref(0)
  const totalCount = ref(0)
  const streak = ref(0)
  const bestStreak = ref(0)

  const columns = computed(() => exercise.value?.columns ?? 3)
  const accuracy = computed(() =>
    totalCount.value === 0 ? 0 : Math.round((correctCount.value / totalCount.value) * 100)
  )

  function nextExercise() {
    exercise.value = service.next(level.value, ops.value)
    userValue.value = 0
    hints.value = []
    phase.value = 'answering'
  }

  function start() {
    correctCount.value = 0
    totalCount.value = 0
    streak.value = 0
    bestStreak.value = 0
    nextExercise()
  }

  function check() {
    if (!exercise.value || phase.value !== 'answering') return
    totalCount.value += 1
    if (service.check(exercise.value, userValue.value)) {
      correctCount.value += 1
      streak.value += 1
      if (streak.value > bestStreak.value) bestStreak.value = streak.value
      phase.value = 'correct'
    } else {
      streak.value = 0
      hints.value = getHint(
        setAbacusValue(exercise.value.columns, exercise.value.a),
        exercise.value.op,
        exercise.value.b
      )
      phase.value = 'wrong'
    }
  }

  /** ตอบผิดแล้วลองใหม่ (คงคำใบ้ไว้ ไม่เพิ่มยอดนับ) */
  function retry() {
    userValue.value = 0
    phase.value = 'answering'
  }

  function setLevel(l: AbacusLevel) {
    level.value = l
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
    // state
    level,
    ops,
    hideAbacus,
    exercise,
    userValue,
    phase,
    hints,
    correctCount,
    totalCount,
    streak,
    bestStreak,
    columns,
    accuracy,
    // actions
    start,
    nextExercise,
    check,
    retry,
    setLevel,
    toggleOp
  }
}
