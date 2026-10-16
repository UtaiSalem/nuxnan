<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Icon } from '@iconify/vue'
import confetti from 'canvas-confetti'
import { useAuthStore } from '~/stores/auth'
import VirtualAbacus from '~/components/games/abacus/VirtualAbacus.vue'

/**
 * MENTAL MATH — เกมคิดเลขเร็ว
 * เมนูตั้งค่า (เครื่องหมาย/ความยาก/โหมดเวลา) → เล่นด้วย virtual keypad (ไม่เรียก
 * คีย์บอร์ด OS จึงไม่มีปัญหาแป้นพิมพ์บังช่องตอบ) → สรุปผล + ประวัติ
 * คงการบันทึกคะแนนขึ้น leaderboard เดิม (/api/game/scores game_type=mentalmath)
 */

type GameState = 'menu' | 'playing' | 'summary'
type OpId = 'add' | 'sub' | 'mul' | 'div'
type DiffId = 'easy' | 'medium' | 'hard'
type ModeId = '30s' | '60s' | '120s' | 'endless'

interface Problem {
  num1: number
  num2: number
  symbol: string
  answer: number
  problemText: string
}
interface HistoryItem {
  problemText: string
  userAnswer: number
  correctAnswer: number
  isCorrect: boolean
  timeSpentMs: number
}

const config = useRuntimeConfig()
const authStore = useAuthStore()

// --- App / game state ---
const gameState = ref<GameState>('menu')
const soundEnabled = ref(true)

// วิธีตอบ: keypad (ปกติ) หรือ abacus (ลูกคิด — ใช้กับ + − เท่านั้น)
const answerMode = ref<'keypad' | 'abacus'>('keypad')
const abacusAnswer = ref(0)
const abacusColumns = 4

// --- Settings ---
const operations = [
  { id: 'add', symbol: '+', name: 'บวก' },
  { id: 'sub', symbol: '−', name: 'ลบ' },
  { id: 'mul', symbol: '×', name: 'คูณ' },
  { id: 'div', symbol: '÷', name: 'หาร' }
] as const
const selectedOps = ref<OpId[]>(['add', 'sub', 'mul'])

const difficulties = [
  { id: 'easy', label: 'ง่าย', desc: 'เลขหลักเดียว 1–15' },
  { id: 'medium', label: 'ปานกลาง', desc: 'สองหลัก 10–99' },
  { id: 'hard', label: 'ท้าทาย', desc: 'สอง/สามหลัก' }
] as const
const difficulty = ref<DiffId>('medium')

const timeModes = [
  { id: '30s', label: '30 วิ', seconds: 30 },
  { id: '60s', label: '60 วิ', seconds: 60 },
  { id: '120s', label: '2 นาที', seconds: 120 },
  { id: 'endless', label: 'ไม่จำกัด', seconds: 0 }
] as const
const timeMode = ref<ModeId>('60s')

const highScores = ref<Record<string, number>>({})

// --- Active session ---
const currentProblem = ref<Problem>({ num1: 0, num2: 0, symbol: '+', answer: 0, problemText: '' })
const currentInput = ref('')
const score = ref(0)
const streak = ref(0)
const highestStreak = ref(0)
const correctCount = ref(0)
const totalAnswered = ref(0)
const history = ref<HistoryItem[]>([])
const feedbackState = ref<'correct' | 'wrong' | null>(null)
const floatingNotice = ref('')
const isNewHighScore = ref(false)

const timeLeft = ref(60)
const initialTime = ref(60)
const elapsedSeconds = ref(0)
const sessionId = ref('')
let gameTimer: ReturnType<typeof setInterval> | null = null
let questionStartTime = 0

// --- Web Audio synth ---
let audioCtx: AudioContext | null = null
function getAudioContext() {
  if (!import.meta.client) return null
  if (!audioCtx) {
    const AudioCtx = window.AudioContext || (window as any).webkitAudioContext
    if (AudioCtx) audioCtx = new AudioCtx()
  }
  if (audioCtx && audioCtx.state === 'suspended') audioCtx.resume()
  return audioCtx
}
function playSound(type: 'correct' | 'wrong' | 'tick' | 'streak') {
  if (!soundEnabled.value) return
  try {
    const ctx = getAudioContext()
    if (!ctx) return
    const now = ctx.currentTime
    const osc = ctx.createOscillator()
    const gain = ctx.createGain()
    osc.connect(gain)
    gain.connect(ctx.destination)
    if (type === 'correct') {
      osc.type = 'triangle'
      osc.frequency.setValueAtTime(523.25, now)
      osc.frequency.exponentialRampToValueAtTime(880, now + 0.12)
      gain.gain.setValueAtTime(0.2, now)
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.25)
      osc.start(now); osc.stop(now + 0.25)
    } else if (type === 'wrong') {
      osc.type = 'sawtooth'
      osc.frequency.setValueAtTime(150, now)
      osc.frequency.linearRampToValueAtTime(90, now + 0.2)
      gain.gain.setValueAtTime(0.2, now)
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.25)
      osc.start(now); osc.stop(now + 0.25)
    } else if (type === 'tick') {
      osc.type = 'sine'
      osc.frequency.setValueAtTime(800, now)
      gain.gain.setValueAtTime(0.05, now)
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.04)
      osc.start(now); osc.stop(now + 0.04)
    } else if (type === 'streak') {
      osc.type = 'sine'
      osc.frequency.setValueAtTime(659.25, now)
      osc.frequency.setValueAtTime(987.77, now + 0.08)
      gain.gain.setValueAtTime(0.25, now)
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35)
      osc.start(now); osc.stop(now + 0.35)
    }
  } catch (e) {
    console.warn('Audio play error:', e)
  }
}

// --- Config helpers ---
function toggleOperation(opId: OpId) {
  if (answerMode.value === 'abacus' && (opId === 'mul' || opId === 'div')) return
  if (selectedOps.value.includes(opId)) {
    if (selectedOps.value.length > 1) {
      selectedOps.value = selectedOps.value.filter((id) => id !== opId)
    }
  } else {
    selectedOps.value = [...selectedOps.value, opId]
  }
}

function setAnswerMode(mode: 'keypad' | 'abacus') {
  answerMode.value = mode
  if (mode === 'abacus') {
    const addSub = selectedOps.value.filter((o) => o === 'add' || o === 'sub')
    selectedOps.value = addSub.length ? addSub : ['add']
  }
  try { localStorage.setItem('mental_math_answer_mode', mode) } catch (e) { /* noop */ }
}
function getModeLabel(mId: ModeId) {
  return timeModes.find((m) => m.id === mId)?.label ?? mId
}
const currentHighScore = computed(() => highScores.value[timeMode.value] || 0)
const difficultyNum = computed(() => (difficulty.value === 'easy' ? 1 : difficulty.value === 'medium' ? 2 : 3))
const hasAnswer = computed(() =>
  answerMode.value === 'abacus' ? abacusAnswer.value > 0 : currentInput.value.length > 0
)
const answerDisplay = computed(() =>
  answerMode.value === 'abacus' ? String(abacusAnswer.value) : currentInput.value || '?'
)

// --- Timer visuals ---
const timerBarColorClass = computed(() => {
  const pct = timeLeft.value / initialTime.value
  if (pct > 0.4) return 'bg-emerald-500'
  if (pct > 0.2) return 'bg-amber-500'
  return 'bg-rose-500 animate-pulse'
})
const timerBgClass = computed(() => {
  if (timeMode.value === 'endless') return 'bg-vikinger-purple/15 text-vikinger-purple dark:text-vikinger-cyan'
  const pct = timeLeft.value / initialTime.value
  if (pct > 0.4) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
  if (pct > 0.2) return 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'
  return 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
})
function formatTime(totalSec: number) {
  const m = Math.floor(totalSec / 60)
  const s = totalSec % 60
  return `${m}:${s < 10 ? '0' : ''}${s}`
}

// --- Problem generator ---
function generateProblem() {
  const pool = answerMode.value === 'abacus'
    ? selectedOps.value.filter((o) => o === 'add' || o === 'sub')
    : selectedOps.value
  const ops = pool.length ? pool : (['add'] as OpId[])
  const opChoice = ops[Math.floor(Math.random() * ops.length)]
  let n1 = 0, n2 = 0, ans = 0, sym = '+'
  const r = (min: number, max: number) => Math.floor(Math.random() * (max - min + 1)) + min

  if (difficulty.value === 'easy') {
    if (opChoice === 'add') { n1 = r(1, 15); n2 = r(1, 15); ans = n1 + n2; sym = '+' }
    else if (opChoice === 'sub') { n1 = r(3, 20); n2 = r(1, n1); ans = n1 - n2; sym = '−' }
    else if (opChoice === 'mul') { n1 = r(1, 9); n2 = r(1, 9); ans = n1 * n2; sym = '×' }
    else { n2 = r(2, 9); ans = r(1, 9); n1 = n2 * ans; sym = '÷' }
  } else if (difficulty.value === 'medium') {
    if (opChoice === 'add') { n1 = r(12, 60); n2 = r(10, 50); ans = n1 + n2; sym = '+' }
    else if (opChoice === 'sub') { n1 = r(25, 99); n2 = r(10, n1 - 1); ans = n1 - n2; sym = '−' }
    else if (opChoice === 'mul') { n1 = r(3, 15); n2 = r(3, 12); ans = n1 * n2; sym = '×' }
    else { n2 = r(3, 12); ans = r(3, 15); n1 = n2 * ans; sym = '÷' }
  } else {
    if (opChoice === 'add') { n1 = r(40, 250); n2 = r(30, 250); ans = n1 + n2; sym = '+' }
    else if (opChoice === 'sub') { n1 = r(100, 400); n2 = r(25, n1 - 10); ans = n1 - n2; sym = '−' }
    else if (opChoice === 'mul') { n1 = r(11, 35); n2 = r(4, 25); ans = n1 * n2; sym = '×' }
    else { n2 = r(4, 20); ans = r(11, 40); n1 = n2 * ans; sym = '÷' }
  }

  currentProblem.value = { num1: n1, num2: n2, symbol: sym, answer: ans, problemText: `${n1} ${sym} ${n2}` }
  abacusAnswer.value = 0
  questionStartTime = performance.now()
}

// --- Gameplay ---
function startGame() {
  if (selectedOps.value.length === 0) return
  score.value = 0
  streak.value = 0
  highestStreak.value = 0
  correctCount.value = 0
  totalAnswered.value = 0
  history.value = []
  currentInput.value = ''
  abacusAnswer.value = 0
  feedbackState.value = null
  isNewHighScore.value = false
  sessionId.value = uuid()

  const modeCfg = timeModes.find((m) => m.id === timeMode.value)
  initialTime.value = modeCfg ? modeCfg.seconds : 60
  timeLeft.value = initialTime.value
  elapsedSeconds.value = 0

  gameState.value = 'playing'
  generateProblem()
  startTimer()
}
function startTimer() {
  if (gameTimer) clearInterval(gameTimer)
  gameTimer = setInterval(() => {
    if (timeMode.value === 'endless') {
      elapsedSeconds.value++
    } else {
      timeLeft.value--
      if (timeLeft.value <= 3 && timeLeft.value > 0) playSound('tick')
      if (timeLeft.value <= 0) endGame()
    }
  }, 1000)
}
function appendDigit(digit: number) {
  if (currentInput.value.length < 7) currentInput.value += digit.toString()
}
function deleteDigit() {
  currentInput.value = currentInput.value.slice(0, -1)
}
function clearInput() {
  currentInput.value = ''
}
function checkAnswer() {
  if (gameState.value !== 'playing') return
  if (answerMode.value === 'keypad' && !currentInput.value) return
  const userAns = answerMode.value === 'abacus' ? abacusAnswer.value : parseInt(currentInput.value, 10)
  const isRight = userAns === currentProblem.value.answer
  const timeSpent = Math.max(100, Math.round(performance.now() - questionStartTime))

  totalAnswered.value++
  history.value.unshift({
    problemText: currentProblem.value.problemText,
    userAnswer: userAns,
    correctAnswer: currentProblem.value.answer,
    isCorrect: isRight,
    timeSpentMs: timeSpent
  })

  if (isRight) {
    correctCount.value++
    streak.value++
    if (streak.value > highestStreak.value) highestStreak.value = streak.value
    const diffMultiplier = difficulty.value === 'easy' ? 10 : difficulty.value === 'medium' ? 20 : 35
    const streakBonus = Math.min(streak.value * 2, 20)
    const earnedPoints = diffMultiplier + streakBonus
    score.value += earnedPoints
    feedbackState.value = 'correct'
    floatingNotice.value = `+${earnedPoints}`
    if (streak.value % 5 === 0) { playSound('streak'); triggerMiniConfetti() }
    else playSound('correct')
  } else {
    streak.value = 0
    feedbackState.value = 'wrong'
    floatingNotice.value = `ตอบผิด! คำตอบคือ ${currentProblem.value.answer}`
    playSound('wrong')
  }

  currentInput.value = ''
  setTimeout(() => {
    feedbackState.value = null
    floatingNotice.value = ''
    if (gameState.value === 'playing') generateProblem()
  }, 450)
}
function triggerMiniConfetti() {
  try { confetti({ particleCount: 35, spread: 60, origin: { y: 0.6 } }) } catch (e) { /* noop */ }
}
function quitGame() {
  endGame()
}

// --- End + persistence ---
async function saveScore() {
  if (score.value <= 0) return
  const headers: Record<string, string> = { Accept: 'application/json', 'Content-Type': 'application/json' }
  if (authStore.token) headers['Authorization'] = `Bearer ${authStore.token}`
  const timeSpent = timeMode.value === 'endless' ? elapsedSeconds.value : initialTime.value - timeLeft.value
  try {
    await $fetch(`${config.public.apiBase}/api/game/scores`, {
      method: 'POST',
      headers,
      body: {
        game_type: 'mentalmath',
        session_id: sessionId.value,
        score: score.value,
        level: difficultyNum.value,
        time_spent: timeSpent,
        player_name: (authStore.user as any)?.name || 'Guest',
        metadata: {
          mode: timeMode.value,
          difficulty: difficulty.value,
          ops: selectedOps.value,
          correct: correctCount.value,
          total: totalAnswered.value,
          best_combo: highestStreak.value
        }
      }
    })
  } catch (e) {
    console.error('Failed to save score:', e)
  }
}
function endGame() {
  if (gameTimer) clearInterval(gameTimer)
  gameState.value = 'summary'

  const currentBest = highScores.value[timeMode.value] || 0
  if (score.value > currentBest && score.value > 0) {
    highScores.value = { ...highScores.value, [timeMode.value]: score.value }
    try { localStorage.setItem('mental_math_highscores', JSON.stringify(highScores.value)) } catch (e) { /* noop */ }
    isNewHighScore.value = true
    try { confetti({ particleCount: 100, spread: 80, origin: { y: 0.5 } }) } catch (e) { /* noop */ }
  }
  saveScore()
}
function goToMenu() {
  if (gameTimer) clearInterval(gameTimer)
  gameState.value = 'menu'
}

// --- Summary metrics ---
const accuracyPercent = computed(() => {
  if (totalAnswered.value === 0) return 0
  return Math.round((correctCount.value / totalAnswered.value) * 100)
})
const avgSpeed = computed(() => {
  if (history.value.length === 0) return '0.0'
  const totalMs = history.value.reduce((acc, cur) => acc + cur.timeSpentMs, 0)
  return (totalMs / history.value.length / 1000).toFixed(1)
})

// --- Keyboard (physical) ---
function handleKeyDown(e: KeyboardEvent) {
  if (gameState.value !== 'playing') return
  if (answerMode.value === 'abacus') {
    if (e.key === 'Enter') { e.preventDefault(); checkAnswer() }
    return
  }
  if (e.key >= '0' && e.key <= '9') { e.preventDefault(); appendDigit(parseInt(e.key, 10)) }
  else if (e.key === 'Backspace') { e.preventDefault(); deleteDigit() }
  else if (e.key === 'Enter') { e.preventDefault(); checkAnswer() }
  else if (e.key === 'Escape' || e.key.toLowerCase() === 'c') { e.preventDefault(); clearInput() }
}

function toggleSound() {
  soundEnabled.value = !soundEnabled.value
  try { localStorage.setItem('mental_math_sound', soundEnabled.value ? 'true' : 'false') } catch (e) { /* noop */ }
}

onMounted(() => {
  try {
    const savedSound = localStorage.getItem('mental_math_sound')
    if (savedSound !== null) soundEnabled.value = savedSound === 'true'
    const savedScores = localStorage.getItem('mental_math_highscores')
    if (savedScores) highScores.value = JSON.parse(savedScores)
    const savedMode = localStorage.getItem('mental_math_answer_mode')
    if (savedMode === 'abacus' || savedMode === 'keypad') answerMode.value = savedMode
  } catch (e) { /* noop */ }
  window.addEventListener('keydown', handleKeyDown)
})
onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
  if (gameTimer) clearInterval(gameTimer)
})
</script>

<template>
  <div class="mental-math w-full font-sans text-slate-800 dark:text-slate-100">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <button class="flex items-center gap-2" @click="goToMenu" type="button">
        <div class="w-10 h-10 rounded-vikinger bg-gradient-vikinger flex items-center justify-center text-white shadow-vikinger shrink-0">
          <Icon icon="fluent:brain-circuit-24-filled" class="w-6 h-6" />
        </div>
        <div class="text-left min-w-0">
          <h1 class="font-heading text-lg font-bold bg-gradient-vikinger bg-clip-text text-transparent leading-tight">
            Mental Math
          </h1>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">เกมคิดเลขเร็ว</p>
        </div>
      </button>
      <button
        @click="toggleSound"
        type="button"
        class="p-2.5 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-vikinger border border-gray-200 dark:border-vikinger-dark-50/50 bg-white/70 dark:bg-vikinger-dark-100/80 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 transition text-slate-600 dark:text-slate-300"
        :title="soundEnabled ? 'ปิดเสียง' : 'เปิดเสียง'"
      >
        <Icon :icon="soundEnabled ? 'fluent:speaker-2-24-regular' : 'fluent:speaker-off-24-regular'" class="w-5 h-5" />
      </button>
    </div>

    <!-- ========== MENU ========== -->
    <div v-if="gameState === 'menu'" class="glass-card rounded-vikinger-xl p-5 sm:p-7 shadow-card dark:shadow-card-dark">
      <div class="text-center mb-6">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-vikinger-purple/10 text-vikinger-purple dark:text-vikinger-cyan mb-2 border border-vikinger-purple/20">
          <Icon icon="fluent:flash-24-filled" class="w-3.5 h-3.5" /> ลับสมองประลองความไว
        </span>
        <h2 class="font-heading text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white">ตั้งค่าการเล่น</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">เลือกรูปแบบการคำนวณและระดับความท้าทาย</p>
      </div>

      <!-- Operations -->
      <div class="mb-5">
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">เครื่องหมายคำนวณ</label>
        <div class="grid grid-cols-4 gap-2">
          <button
            v-for="op in operations"
            :key="op.id"
            @click="toggleOperation(op.id)"
            type="button"
            :disabled="answerMode === 'abacus' && (op.id === 'mul' || op.id === 'div')"
            :class="[
              'min-h-[56px] rounded-vikinger flex flex-col items-center justify-center font-heading text-lg font-bold transition-all border disabled:opacity-40 disabled:cursor-not-allowed',
              selectedOps.includes(op.id)
                ? 'bg-vikinger-purple text-white border-vikinger-purple shadow-vikinger'
                : 'bg-white/60 dark:bg-vikinger-dark-200/60 text-slate-700 dark:text-slate-300 border-gray-200 dark:border-vikinger-dark-50/50 hover:border-vikinger-purple/50'
            ]"
          >
            <span>{{ op.symbol }}</span>
            <span class="text-[10px] font-normal opacity-80">{{ op.name }}</span>
          </button>
        </div>
      </div>

      <!-- Answer mode -->
      <div class="mb-5">
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">วิธีตอบ</label>
        <div class="grid grid-cols-2 gap-2">
          <button
            @click="setAnswerMode('keypad')"
            type="button"
            :class="[
              'min-h-[56px] rounded-vikinger flex items-center justify-center gap-2 font-medium text-sm border transition-all',
              answerMode === 'keypad'
                ? 'bg-gradient-vikinger text-white border-transparent shadow-vikinger'
                : 'bg-white/60 dark:bg-vikinger-dark-200/60 text-slate-700 dark:text-slate-300 border-gray-200 dark:border-vikinger-dark-50/50 hover:border-vikinger-purple/50'
            ]"
          >
            <Icon icon="fluent:keyboard-24-regular" class="w-5 h-5" />
            แป้นตัวเลข
          </button>
          <button
            @click="setAnswerMode('abacus')"
            type="button"
            :class="[
              'min-h-[56px] rounded-vikinger flex items-center justify-center gap-2 font-medium text-sm border transition-all',
              answerMode === 'abacus'
                ? 'bg-gradient-vikinger text-white border-transparent shadow-vikinger'
                : 'bg-white/60 dark:bg-vikinger-dark-200/60 text-slate-700 dark:text-slate-300 border-gray-200 dark:border-vikinger-dark-50/50 hover:border-vikinger-cyan/50'
            ]"
          >
            <Icon icon="fluent:calculator-24-regular" class="w-5 h-5" />
            ลูกคิด
          </button>
        </div>
        <p v-if="answerMode === 'abacus'" class="text-[11px] text-slate-500 dark:text-slate-400 mt-1.5 flex items-center gap-1">
          <Icon icon="fluent:info-24-regular" class="w-3.5 h-3.5 shrink-0" />
          โหมดลูกคิดใช้กับการบวก–ลบเท่านั้น
        </p>
      </div>

      <!-- Difficulty -->
      <div class="mb-5">
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">ระดับความยาก</label>
        <div class="grid grid-cols-3 gap-2">
          <button
            v-for="diff in difficulties"
            :key="diff.id"
            @click="difficulty = diff.id"
            type="button"
            :class="[
              'min-h-[56px] py-2.5 px-2 rounded-vikinger font-medium text-xs sm:text-sm text-center border transition-all flex flex-col items-center justify-center gap-0.5',
              difficulty === diff.id
                ? 'bg-gradient-vikinger text-white border-transparent shadow-vikinger'
                : 'bg-white/60 dark:bg-vikinger-dark-200/60 text-slate-700 dark:text-slate-300 border-gray-200 dark:border-vikinger-dark-50/50 hover:border-vikinger-cyan/50'
            ]"
          >
            <span class="font-bold">{{ diff.label }}</span>
            <span class="text-[10px] opacity-75 font-light break-words">{{ diff.desc }}</span>
          </button>
        </div>
      </div>

      <!-- Time mode -->
      <div class="mb-6">
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">โหมดเวลา</label>
        <div class="grid grid-cols-4 gap-2">
          <button
            v-for="mode in timeModes"
            :key="mode.id"
            @click="timeMode = mode.id"
            type="button"
            :class="[
              'min-h-[44px] py-2.5 rounded-vikinger text-xs sm:text-sm font-semibold border transition-all text-center',
              timeMode === mode.id
                ? 'bg-vikinger-cyan text-vikinger-dark border-vikinger-cyan shadow-vikinger'
                : 'bg-white/60 dark:bg-vikinger-dark-200/60 text-slate-700 dark:text-slate-300 border-gray-200 dark:border-vikinger-dark-50/50 hover:border-vikinger-cyan/50'
            ]"
          >
            {{ mode.label }}
          </button>
        </div>
      </div>

      <!-- High score banner -->
      <div class="flex items-center justify-between gap-2 px-4 py-3 rounded-vikinger bg-vikinger-yellow/10 border border-vikinger-yellow/30 mb-6 text-amber-900 dark:text-amber-200 text-xs sm:text-sm">
        <div class="flex items-center gap-2 min-w-0">
          <Icon icon="fluent:trophy-24-filled" class="w-5 h-5 text-vikinger-yellow shrink-0" />
          <span class="truncate">คะแนนสูงสุด ({{ getModeLabel(timeMode) }})</span>
        </div>
        <span class="font-bold font-heading text-base text-amber-600 dark:text-vikinger-yellow shrink-0 tabular-nums">{{ currentHighScore }} แต้ม</span>
      </div>

      <!-- Start -->
      <button
        @click="startGame"
        :disabled="selectedOps.length === 0"
        type="button"
        class="w-full py-4 rounded-vikinger bg-gradient-vikinger text-white font-bold text-lg shadow-vikinger hover:shadow-vikinger-lg active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
      >
        <Icon icon="fluent:play-24-filled" class="w-5 h-5" />
        เริ่มประลองความเร็ว
      </button>
    </div>

    <!-- ========== PLAYING ========== -->
    <div v-else-if="gameState === 'playing'" class="w-full flex flex-col gap-3">
      <!-- Top bar -->
      <div class="glass-card rounded-vikinger-lg p-4 shadow-card dark:shadow-card-dark flex items-center justify-between gap-2 relative overflow-hidden">
        <div
          v-if="timeMode !== 'endless'"
          class="absolute bottom-0 left-0 h-1.5 transition-all duration-300"
          :class="timerBarColorClass"
          :style="{ width: (timeLeft / initialTime) * 100 + '%' }"
        ></div>

        <!-- Time -->
        <div class="flex items-center gap-2 min-w-0">
          <div :class="['w-9 h-9 rounded-vikinger flex items-center justify-center shrink-0', timerBgClass]">
            <Icon icon="fluent:clock-24-regular" class="w-4 h-4" />
          </div>
          <div class="min-w-0">
            <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">
              {{ timeMode === 'endless' ? 'ฝึกฝน' : 'เวลาคงเหลือ' }}
            </span>
            <span class="text-xl font-bold font-heading tabular-nums" :class="{ 'text-rose-500 animate-pulse': timeLeft <= 5 && timeMode !== 'endless' }">
              {{ timeMode === 'endless' ? formatTime(elapsedSeconds) : timeLeft + 's' }}
            </span>
          </div>
        </div>

        <!-- Streak -->
        <div class="flex flex-col items-center shrink-0">
          <div v-if="streak > 1" class="flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-gradient-to-r from-amber-500 to-orange-500 text-white text-xs font-bold shadow-md">
            <Icon icon="fluent:fire-24-filled" class="w-3.5 h-3.5" />
            <span class="tabular-nums">{{ streak }}x</span>
          </div>
          <span v-else class="text-[11px] text-slate-400 font-medium">ตอบต่อเนื่อง</span>
          <div class="text-[11px] text-slate-500 mt-0.5">ถูก: {{ correctCount }}</div>
        </div>

        <!-- Score -->
        <div class="text-right shrink-0">
          <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">คะแนน</span>
          <span class="text-2xl font-bold font-heading text-vikinger-purple dark:text-vikinger-cyan tabular-nums">{{ score }}</span>
        </div>
      </div>

      <!-- Problem card -->
      <div
        :class="[
          'glass-card rounded-vikinger-xl p-6 sm:p-8 text-center shadow-card dark:shadow-card-dark transition-all duration-150 flex flex-col justify-center items-center min-h-[160px] relative overflow-hidden',
          feedbackState === 'correct' ? 'ring-2 ring-emerald-500 bg-emerald-50/70 dark:bg-emerald-950/40' : '',
          feedbackState === 'wrong' ? 'ring-2 ring-rose-500 bg-rose-50/70 dark:bg-rose-950/40 animate-shake' : ''
        ]"
      >
        <transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150" leave-to-class="opacity-0">
          <div
            v-if="floatingNotice"
            class="absolute top-2 px-3 py-0.5 rounded-full text-xs font-bold max-w-[90%] truncate"
            :class="feedbackState === 'correct' ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white'"
          >
            {{ floatingNotice }}
          </div>
        </transition>

        <div class="text-3xl sm:text-5xl font-extrabold font-heading tracking-wide flex items-center justify-center gap-2 sm:gap-3 text-slate-800 dark:text-white tabular-nums">
          <span>{{ currentProblem.num1 }}</span>
          <span class="text-vikinger-purple dark:text-vikinger-cyan">{{ currentProblem.symbol }}</span>
          <span>{{ currentProblem.num2 }}</span>
          <span class="text-slate-400">=</span>
          <span
            :class="[
              'inline-flex items-center justify-center min-w-[60px] sm:min-w-[70px] px-3 py-1 rounded-vikinger border-2 font-heading text-2xl sm:text-4xl transition-all',
              hasAnswer
                ? 'border-vikinger-purple bg-white dark:bg-vikinger-dark-200 text-vikinger-purple dark:text-vikinger-cyan shadow-inner'
                : 'border-dashed border-slate-300 dark:border-vikinger-dark-50 text-slate-300 dark:text-slate-600'
            ]"
          >
            {{ answerDisplay }}
          </span>
        </div>

        <p class="text-xs text-slate-400 dark:text-slate-500 mt-4">
          {{ answerMode === 'abacus' ? 'เลื่อนเม็ดลูกคิดให้ได้คำตอบ แล้วกดส่งคำตอบ' : 'กดปุ่มตัวเลขด้านล่าง หรือพิมพ์จากคีย์บอร์ดได้' }}
        </p>
      </div>

      <!-- Input area -->
      <div class="glass-card rounded-vikinger-xl p-4 shadow-card dark:shadow-card-dark">
        <!-- Abacus input (โหมดลูกคิด) -->
        <div v-if="answerMode === 'abacus'" class="flex justify-center py-1">
          <VirtualAbacus v-model="abacusAnswer" :columns="abacusColumns" show-values />
        </div>

        <!-- Keypad input -->
        <div v-else class="grid grid-cols-3 gap-2 sm:gap-3 max-w-sm mx-auto">
          <button
            v-for="digit in [1, 2, 3, 4, 5, 6, 7, 8, 9]"
            :key="digit"
            @click="appendDigit(digit)"
            type="button"
            class="key-btn h-14 sm:h-16 rounded-vikinger bg-white dark:bg-vikinger-dark-200/90 hover:bg-gray-100 dark:hover:bg-vikinger-dark-300 border border-gray-200/80 dark:border-vikinger-dark-50/60 text-2xl font-bold font-heading shadow-sm flex items-center justify-center active:bg-vikinger-purple/10"
          >
            {{ digit }}
          </button>
          <button
            @click="clearInput"
            type="button"
            class="key-btn h-14 sm:h-16 rounded-vikinger bg-gray-100 dark:bg-vikinger-dark-100 hover:bg-gray-200 dark:hover:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/60 text-slate-600 dark:text-slate-300 font-bold text-sm flex items-center justify-center"
          >
            ล้าง
          </button>
          <button
            @click="appendDigit(0)"
            type="button"
            class="key-btn h-14 sm:h-16 rounded-vikinger bg-white dark:bg-vikinger-dark-200/90 hover:bg-gray-100 dark:hover:bg-vikinger-dark-300 border border-gray-200/80 dark:border-vikinger-dark-50/60 text-2xl font-bold font-heading shadow-sm flex items-center justify-center"
          >
            0
          </button>
          <button
            @click="deleteDigit"
            type="button"
            class="key-btn h-14 sm:h-16 rounded-vikinger bg-gray-100 dark:bg-vikinger-dark-100 hover:bg-gray-200 dark:hover:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/60 text-slate-600 dark:text-slate-300 flex items-center justify-center"
          >
            <Icon icon="fluent:backspace-24-regular" class="w-6 h-6" />
          </button>
        </div>

        <div class="mt-3 max-w-sm mx-auto grid grid-cols-4 gap-2">
          <button
            @click="quitGame"
            type="button"
            class="col-span-1 min-h-[44px] py-3 rounded-vikinger bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-600 dark:text-rose-400 font-medium text-xs flex items-center justify-center gap-1 hover:bg-rose-100 dark:hover:bg-rose-950/60"
          >
            <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4" />
            จบ
          </button>
          <button
            @click="checkAnswer"
            :disabled="answerMode === 'keypad' && currentInput.length === 0"
            type="button"
            class="col-span-3 min-h-[44px] py-3 rounded-vikinger bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-bold font-heading text-lg shadow-lg shadow-emerald-500/20 active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <span>ส่งคำตอบ</span>
            <Icon icon="fluent:arrow-right-24-filled" class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- ========== SUMMARY ========== -->
    <div v-else-if="gameState === 'summary'" class="glass-card rounded-vikinger-xl p-5 sm:p-7 shadow-card dark:shadow-card-dark">
      <div class="text-center mb-6">
        <div class="w-16 h-16 mx-auto rounded-vikinger-lg bg-gradient-to-tr from-amber-400 to-orange-500 flex items-center justify-center text-white shadow-lg shadow-orange-500/30 mb-3">
          <Icon icon="fluent:trophy-24-filled" class="w-9 h-9" />
        </div>
        <h2 class="font-heading text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">หมดเวลา! สรุปผลงาน</h2>
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
          {{ isNewHighScore ? '🎉 ยินดีด้วย! คุณทำลายสถิติสูงสุดใหม่!' : 'ฝึกฝนสม่ำเสมอจะช่วยให้คิดเลขได้ไวขึ้น' }}
        </p>
      </div>

      <!-- Metrics -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="p-3.5 rounded-vikinger bg-vikinger-purple/10 border border-vikinger-purple/20 text-center">
          <span class="text-[10px] uppercase font-bold text-vikinger-purple dark:text-vikinger-cyan block mb-1">คะแนนรวม</span>
          <span class="text-2xl sm:text-3xl font-bold font-heading text-vikinger-purple dark:text-vikinger-cyan tabular-nums">{{ score }}</span>
        </div>
        <div class="p-3.5 rounded-vikinger bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/50 text-center">
          <span class="text-[10px] uppercase font-bold text-emerald-500 block mb-1">ความแม่นยำ</span>
          <span class="text-2xl sm:text-3xl font-bold font-heading text-emerald-700 dark:text-emerald-300 tabular-nums">{{ accuracyPercent }}%</span>
          <span class="text-[10px] text-slate-400 block">({{ correctCount }}/{{ totalAnswered }})</span>
        </div>
        <div class="p-3.5 rounded-vikinger bg-amber-50/80 dark:bg-amber-950/40 border border-amber-100 dark:border-amber-900/50 text-center">
          <span class="text-[10px] uppercase font-bold text-amber-500 block mb-1">คอมโบสูงสุด</span>
          <span class="text-2xl sm:text-3xl font-bold font-heading text-amber-600 dark:text-amber-300 tabular-nums">{{ highestStreak }}x</span>
        </div>
        <div class="p-3.5 rounded-vikinger bg-vikinger-cyan/10 border border-vikinger-cyan/20 text-center">
          <span class="text-[10px] uppercase font-bold text-cyan-600 dark:text-vikinger-cyan block mb-1">ความเร็วเฉลี่ย</span>
          <span class="text-2xl sm:text-3xl font-bold font-heading text-cyan-700 dark:text-vikinger-cyan tabular-nums">{{ avgSpeed }}s</span>
          <span class="text-[10px] text-slate-400 block">ต่อข้อ</span>
        </div>
      </div>

      <!-- History -->
      <div class="mb-6">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2.5 flex items-center justify-between">
          <span>ประวัติการตอบ</span>
          <span class="text-[11px] font-normal normal-case text-slate-400">ทั้งหมด {{ history.length }} ข้อ</span>
        </h3>
        <div class="max-h-48 overflow-y-auto space-y-1.5 pr-1">
          <div
            v-for="(item, idx) in history"
            :key="idx"
            :class="[
              'px-3.5 py-2 rounded-vikinger text-xs sm:text-sm flex items-center justify-between gap-2 border',
              item.isCorrect ? 'bg-emerald-500/10 border-emerald-500/20' : 'bg-rose-500/10 border-rose-500/20'
            ]"
          >
            <div class="flex items-center gap-2 min-w-0">
              <span
                class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                :class="item.isCorrect ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white'"
              >
                {{ item.isCorrect ? '✓' : '✗' }}
              </span>
              <span class="font-heading font-medium truncate">{{ item.problemText }}</span>
            </div>
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
              <span v-if="!item.isCorrect" class="text-rose-500 line-through text-xs tabular-nums">{{ item.userAnswer }}</span>
              <span class="font-bold font-heading text-emerald-600 dark:text-emerald-400 tabular-nums">{{ item.correctAnswer }}</span>
              <span class="text-[10px] text-slate-400 tabular-nums">{{ (item.timeSpentMs / 1000).toFixed(1) }}s</span>
            </div>
          </div>
          <div v-if="history.length === 0" class="text-center text-xs text-slate-400 py-4">ไม่มีข้อมูลการตอบ</div>
        </div>
      </div>

      <!-- Actions -->
      <div class="grid grid-cols-2 gap-3">
        <button
          @click="goToMenu"
          type="button"
          class="min-h-[44px] py-3.5 rounded-vikinger bg-gray-100 dark:bg-vikinger-dark-200 hover:bg-gray-200 dark:hover:bg-vikinger-dark-300 font-bold text-sm text-slate-700 dark:text-slate-200 transition flex items-center justify-center gap-1.5"
        >
          <Icon icon="fluent:settings-24-regular" class="w-4 h-4" />
          ตั้งค่าใหม่
        </button>
        <button
          @click="startGame"
          type="button"
          class="min-h-[44px] py-3.5 rounded-vikinger bg-gradient-vikinger text-white font-bold text-sm shadow-vikinger hover:shadow-vikinger-lg transition flex items-center justify-center gap-1.5"
        >
          <Icon icon="fluent:arrow-counterclockwise-24-regular" class="w-4 h-4" />
          เล่นอีกครั้ง
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.glass-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border: 1px solid rgba(255, 255, 255, 0.5);
}
.dark .glass-card {
  background: rgba(47, 55, 73, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.08);
}
.key-btn {
  touch-action: manipulation;
  transition: all 0.1s ease;
  user-select: none;
}
.key-btn:active {
  transform: scale(0.94);
}
@keyframes shake {
  10%, 90% { transform: translate3d(-2px, 0, 0); }
  20%, 80% { transform: translate3d(4px, 0, 0); }
  30%, 50%, 70% { transform: translate3d(-6px, 0, 0); }
  40%, 60% { transform: translate3d(6px, 0, 0); }
}
.animate-shake {
  animation: shake 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}
</style>
