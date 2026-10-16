/**
 * useAbacusBridge — ด่าน 3: สะพานนิ้วมือ ↔ ลูกคิด
 * โชว์จำนวนเป็นท่ามือ (given) → ผู้เรียนสร้างจำนวนเดียวกันบนลูกคิด → ตรวจ
 * ใช้ fingerEngine (numberToHands/handValue) + abacusEngine (isAbacusCorrect/setAbacusValue)
 * target สุ่มผ่าน FingerExerciseService (mock) — ไม่เรียก API ตรง
 */
import { ref, computed } from 'vue'
import { isAbacusCorrect, setAbacusValue } from '~/components/games/abacus/abacusEngine'
import { numberToHands, handValue } from '~/components/games/fingers/fingerEngine'
import {
  createMockFingerExerciseService,
  type FingerExerciseService,
  type FingerStage
} from '~/services/fingers/fingerExerciseService'

export type BridgePhase = 'idle' | 'answering' | 'correct' | 'wrong'

export function useAbacusBridge(service: FingerExerciseService = createMockFingerExerciseService()) {
  const stage = ref<FingerStage>(1) // 1 = 0–9 (1 หลัก) · 2 = 0–99 (2 หลัก)
  const hideNumber = ref(false)

  const target = ref(0)
  const userValue = ref(0) // ค่าบนลูกคิด (v-model)
  const phase = ref<BridgePhase>('idle')
  const hint = ref('')

  const correctCount = ref(0)
  const totalCount = ref(0)
  const streak = ref(0)
  const bestStreak = ref(0)

  const columns = computed(() => (stage.value === 2 ? 2 : 1))
  const accuracy = computed(() =>
    totalCount.value === 0 ? 0 : Math.round((correctCount.value / totalCount.value) * 100)
  )
  const targetHands = computed(() => numberToHands(target.value))

  function nextExercise() {
    target.value = service.next(stage.value, 'read').value
    userValue.value = 0
    hint.value = ''
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
    if (phase.value !== 'answering') return
    totalCount.value += 1
    if (isAbacusCorrect(setAbacusValue(columns.value, userValue.value), target.value)) {
      correctCount.value += 1
      streak.value += 1
      if (streak.value > bestStreak.value) bestStreak.value = streak.value
      phase.value = 'correct'
    } else {
      streak.value = 0
      const exp = numberToHands(target.value)
      const got = numberToHands(userValue.value)
      const parts: string[] = []
      if (stage.value === 2 && handValue(exp.tens) !== handValue(got.tens)) {
        parts.push(`หลักสิบควรเป็น ${handValue(exp.tens)}`)
      }
      if (handValue(exp.units) !== handValue(got.units)) {
        parts.push(`หลักหน่วยควรเป็น ${handValue(exp.units)}`)
      }
      hint.value = parts.length ? parts.join(' · ') : `ค่าควรเป็น ${target.value}`
      phase.value = 'wrong'
    }
  }

  function retry() {
    userValue.value = 0
    phase.value = 'answering'
  }

  function setStage(s: FingerStage) {
    stage.value = s
    nextExercise()
  }

  return {
    stage,
    hideNumber,
    target,
    userValue,
    phase,
    hint,
    correctCount,
    totalCount,
    streak,
    bestStreak,
    columns,
    accuracy,
    targetHands,
    start,
    nextExercise,
    check,
    retry,
    setStage
  }
}
