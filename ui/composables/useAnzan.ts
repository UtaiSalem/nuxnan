/**
 * useAnzan — โหมดคิดในใจ (Anzan): ตัวเลขโผล่ทีละตัว → บวก/ลบในใจ → ตอบผลรวม
 * จับเวลา flash ด้วย setTimeout (เคลียร์ตอน unmount) · บันทึกผลลง lessonProgress stage 'anzan'
 */
import { ref, computed, onUnmounted } from 'vue'
import {
  createMockAnzanService,
  type AnzanRound,
  type AnzanService,
  type AnzanLevel
} from '~/services/anzan/anzanService'
import { useLessonProgress } from '~/composables/useLessonProgress'

export type AnzanPhase = 'idle' | 'countdown' | 'flash' | 'input' | 'correct' | 'wrong'

const SPEEDS = [
  { id: 'slow', label: 'ช้า', ms: 1200 },
  { id: 'normal', label: 'ปกติ', ms: 850 },
  { id: 'fast', label: 'เร็ว', ms: 550 }
] as const
export type AnzanSpeed = (typeof SPEEDS)[number]['id']

export function useAnzan(service: AnzanService = createMockAnzanService()) {
  const count = ref(5)
  const level = ref<AnzanLevel>('easy')
  const speed = ref<AnzanSpeed>('normal')

  const round = ref<AnzanRound | null>(null)
  const phase = ref<AnzanPhase>('idle')
  const countdownNum = ref(3)
  const flashIndex = ref(-1) // -1 = ว่าง/ยังไม่โชว์ · >=0 = index พจน์ที่โชว์
  const input = ref('')

  const correctCount = ref(0)
  const totalCount = ref(0)
  const streak = ref(0)
  const bestStreak = ref(0)

  const progress = useLessonProgress()
  let timer: ReturnType<typeof setTimeout> | null = null
  function clearTimer() {
    if (timer) {
      clearTimeout(timer)
      timer = null
    }
  }

  const speedMs = computed(() => SPEEDS.find((s) => s.id === speed.value)?.ms ?? 850)
  const termCount = computed(() => round.value?.terms.length ?? 0)
  const accuracy = computed(() =>
    totalCount.value === 0 ? 0 : Math.round((correctCount.value / totalCount.value) * 100)
  )

  /** ข้อความพจน์ที่กำลังโชว์ ('' = ว่าง) */
  const flashTerm = computed(() => {
    if (!round.value || flashIndex.value < 0) return ''
    const t = round.value.terms[flashIndex.value]
    if (flashIndex.value === 0) return String(t)
    return t >= 0 ? `+${t}` : `−${Math.abs(t)}`
  })

  function showTerm(i: number) {
    clearTimer()
    if (!round.value || i >= round.value.terms.length) {
      flashIndex.value = -1
      phase.value = 'input'
      return
    }
    flashIndex.value = i
    timer = setTimeout(() => {
      flashIndex.value = -1 // เว้นช่องว่างสั้น ๆ ระหว่างตัวเลข
      timer = setTimeout(() => showTerm(i + 1), 180)
    }, speedMs.value)
  }

  function tickCountdown() {
    clearTimer()
    if (countdownNum.value > 1) {
      timer = setTimeout(() => {
        countdownNum.value -= 1
        tickCountdown()
      }, 650)
    } else {
      timer = setTimeout(() => {
        phase.value = 'flash'
        showTerm(0)
      }, 650)
    }
  }

  function startRound() {
    clearTimer()
    round.value = service.next(count.value, level.value)
    input.value = ''
    flashIndex.value = -1
    countdownNum.value = 3
    phase.value = 'countdown'
    tickCountdown()
  }

  function start() {
    correctCount.value = 0
    totalCount.value = 0
    streak.value = 0
    bestStreak.value = 0
    startRound()
  }

  function appendDigit(d: number) {
    if (phase.value !== 'input') return
    if (input.value.length < 6) input.value += String(d)
  }
  function backspace() {
    input.value = input.value.slice(0, -1)
  }
  function clearInput() {
    input.value = ''
  }

  function submit() {
    if (phase.value !== 'input' || !round.value || input.value === '') return
    const v = parseInt(input.value, 10)
    totalCount.value += 1
    if (service.check(round.value, v)) {
      correctCount.value += 1
      streak.value += 1
      if (streak.value > bestStreak.value) bestStreak.value = streak.value
      phase.value = 'correct'
    } else {
      streak.value = 0
      phase.value = 'wrong'
    }
    progress.record('anzan', phase.value === 'correct', streak.value)
  }

  function replay() {
    // เล่นชุดเดิมซ้ำ (ดูตัวเลขอีกครั้ง) — ไม่เปลี่ยนโจทย์/ไม่นับผล
    if (!round.value) return
    input.value = ''
    flashIndex.value = -1
    countdownNum.value = 3
    phase.value = 'countdown'
    tickCountdown()
  }

  function abort() {
    clearTimer()
    phase.value = 'idle'
  }

  onUnmounted(clearTimer)

  return {
    // config
    count,
    level,
    speed,
    speeds: SPEEDS,
    // state
    round,
    phase,
    countdownNum,
    flashTerm,
    flashIndex,
    termCount,
    input,
    correctCount,
    totalCount,
    streak,
    bestStreak,
    accuracy,
    // actions
    start,
    startRound,
    appendDigit,
    backspace,
    clearInput,
    submit,
    replay,
    abort
  }
}
