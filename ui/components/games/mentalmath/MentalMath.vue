<script setup lang="ts">
import { ref, computed, onUnmounted, nextTick, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'

/**
 * MENTAL MATH GAME
 * A fast-paced mental calculation game with Cartoon Brutalist style.
 */

// --- Types ---
type GameMode = 'training' | 'challenge'
type GameState = 'selectMode' | 'challengeSetup' | 'playing' | 'result'

interface Question {
  nums: number[]
  answer: number
}

interface LevelConfig {
  count: number
  range: [number, number]
  time: number
}

interface LeaderboardEntry {
  id: number
  user_id: number
  player_name: string
  score: number
  level: number
}

// --- Constants ---
const LEVEL_CONFIG: Record<number, LevelConfig> = {
  1: { count: 2, range: [1, 5], time: 25 },
  2: { count: 2, range: [1, 9], time: 30 },
  3: { count: 3, range: [1, 9], time: 35 },
  4: { count: 3, range: [1, 15], time: 40 },
  5: { count: 4, range: [1, 15], time: 45 },
  6: { count: 4, range: [5, 20], time: 55 },
  7: { count: 5, range: [5, 20], time: 60 },
  8: { count: 5, range: [10, 30], time: 70 },
  9: { count: 6, range: [10, 40], time: 80 },
  10: { count: 6, range: [10, 50], time: 90 },
}

// --- Nuxt Composables (must be top-level) ---
const config = useRuntimeConfig()

// --- State ---
const authStore = useAuthStore()
const gameMode = ref<GameMode>('challenge')
const gameState = ref<GameState>('selectMode')
const currentLevel = ref(1)

// Playing State
const question = ref<Question>({ nums: [], answer: 0 })
const answerInput = ref('')
const isSubmitting = ref(false)
const inputRef = ref<HTMLInputElement | null>(null)

// Scoring State
const score = ref(0)
const correctCount = ref(0)
const wrongCount = ref(0)
const timeLeft = ref(0)
const elapsedSeconds = ref(0) // For Training (stopwatch)
const gameSessionId = ref('')

// Feedback State
const lastFeedback = ref<{ isCorrect: boolean; correctAnswer: number } | null>(null)

// Leaderboard State
const leaderboardData = ref<LeaderboardEntry[]>([])
const userRankInfo = ref<{ rank: number; best_score: number } | null>(null)
const loadingLeaderboard = ref(false)

// Internal Timers
let timerInterval: ReturnType<typeof setInterval> | null = null
let feedbackTimeout: ReturnType<typeof setTimeout> | null = null

// --- Computed ---
const timerPercent = computed(() => {
  const total = LEVEL_CONFIG[currentLevel.value]?.time ?? 60
  return (timeLeft.value / total) * 100
})

const timerColor = computed(() => {
  if (timerPercent.value > 50) return 'bg-emerald-400'
  if (timerPercent.value > 25) return 'bg-yellow-400'
  return 'bg-red-500 animate-pulse'
})

// --- Methods ---

/**
 * Generates a valid math question based on level config.
 * Ensures the result is never negative for better UX.
 */
function generateQuestion(level: number): Question {
  const cfg = LEVEL_CONFIG[level]
  const [min, max] = cfg.range

  // Try up to 50 times to find a valid sequence
  for (let attempt = 0; attempt < 50; attempt++) {
    const nums: number[] = []
    let sum = 0
    let valid = true

    for (let i = 0; i < cfg.count; i++) {
      const n = Math.floor(Math.random() * (max - min + 1)) + min
      // 40% chance of being negative, but not for the first number
      const sign = i > 0 && Math.random() < 0.4 ? -1 : 1
      const val = n * sign
      
      // Rule: Intermediate sum must not be negative
      if (sum + val < 0) {
        valid = false
        break
      }
      
      nums.push(val)
      sum += val
    }

    if (valid && sum > 0) {
      return { nums, answer: sum }
    }
  }

  // Fallback to a simple addition if generation fails
  const a = Math.floor(Math.random() * 5) + 1
  const b = Math.floor(Math.random() * 5) + 1
  return { nums: [a, b], answer: a + b }
}

function startTimer() {
  if (timerInterval) clearInterval(timerInterval)
  
  timerInterval = setInterval(() => {
    if (gameMode.value === 'challenge') {
      timeLeft.value--
      if (timeLeft.value <= 0) {
        endGame()
      }
    } else {
      elapsedSeconds.value++
    }
  }, 1000)
}

function startGame() {
  const cfg = LEVEL_CONFIG[currentLevel.value]
  gameSessionId.value = uuid()
  score.value = 0
  correctCount.value = 0
  wrongCount.value = 0
  timeLeft.value = cfg.time
  elapsedSeconds.value = 0
  gameState.value = 'playing'
  question.value = generateQuestion(currentLevel.value)
  
  startTimer()
  
  nextTick(() => {
    inputRef.value?.focus()
  })
}

function endGame() {
  if (timerInterval) clearInterval(timerInterval)
  if (feedbackTimeout) clearTimeout(feedbackTimeout)
  
  gameState.value = 'result'
  saveScore()
  fetchLeaderboard()
}

function quitGame() {
  if (timerInterval) clearInterval(timerInterval)
  if (feedbackTimeout) clearTimeout(feedbackTimeout)
  gameState.value = 'selectMode'
}

function submitAnswer() {
  if (isSubmitting.value || answerInput.value === '') return

  const userAnswer = parseInt(answerInput.value, 10)
  if (isNaN(userAnswer)) return

  isSubmitting.value = true
  const isCorrect = userAnswer === question.value.answer

  if (isCorrect) {
    correctCount.value++
    // Base 100 + bonus per level
    score.value += 100 + currentLevel.value * 10
  } else {
    wrongCount.value++
  }

  lastFeedback.value = { isCorrect, correctAnswer: question.value.answer }
  answerInput.value = ''

  feedbackTimeout = setTimeout(() => {
    lastFeedback.value = null
    question.value = generateQuestion(currentLevel.value)
    isSubmitting.value = false
    nextTick(() => {
      inputRef.value?.focus()
    })
  }, 400)
}

// --- API Integration ---

async function saveScore() {
  if (score.value <= 0) return

  const headers: Record<string, string> = {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
  
  if (authStore.token) {
    headers['Authorization'] = `Bearer ${authStore.token}`
  }

  try {
    await $fetch(`${config.public.apiBase}/api/game/scores`, {
      method: 'POST',
      headers,
      body: {
        game_type: 'mentalmath',
        session_id: gameSessionId.value,
        score: score.value,
        level: currentLevel.value,
        time_spent: gameMode.value === 'challenge' 
          ? LEVEL_CONFIG[currentLevel.value].time - timeLeft.value 
          : elapsedSeconds.value,
        player_name: (authStore.user as any)?.name || 'Guest',
        metadata: {
          mode: gameMode.value,
          correct: correctCount.value,
          wrong: wrongCount.value,
        }
      }
    })
  } catch (e) {
    console.error('Failed to save score:', e)
  }
}

async function fetchLeaderboard() {
  loadingLeaderboard.value = true
  try {
    const data = await $fetch<any>(`${config.public.apiBase}/api/game/scores?game_type=mentalmath`)
    leaderboardData.value = data.leaderboard || []
    if (data.user_rank && data.user_best_score) {
      userRankInfo.value = {
        rank: data.user_rank,
        best_score: data.user_best_score.score
      }
    }
  } catch (e) {
    console.error('Failed to fetch leaderboard:', e)
  } finally {
    loadingLeaderboard.value = false
  }
}

// --- Watchers & Cleanup ---

watch(gameState, (newState) => {
  if (newState !== 'playing' && timerInterval) {
    clearInterval(timerInterval)
  }
})

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
  if (feedbackTimeout) clearTimeout(feedbackTimeout)
})

</script>

<template>
  <div class="mental-math-container font-sans text-black">
    
    <!-- 1. MODE SELECTION -->
    <div v-if="gameState === 'selectMode'" class="bg-white border-4 border-black p-4 sm:p-8 shadow-[8px_8px_0_0_rgba(0,0,0,1)] rounded-none">
      <div class="text-center mb-8">
        <div class="inline-block p-4 bg-emerald-400 border-4 border-black shadow-[4px_4px_0_0_rgba(0,0,0,1)] mb-4">
          <Icon icon="fluent:brain-circuit-24-filled" class="w-16 h-16" />
        </div>
        <h1 class="text-5xl font-black uppercase tracking-tighter italic">Mental Math</h1>
        <p class="text-xl font-bold text-gray-600 mt-2">ท้าทายสมองด้วยการคิดเลขเร็วในหัว!</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Challenge Mode -->
        <button 
          @click="gameMode = 'challenge'; gameState = 'challengeSetup'"
          class="flex flex-col items-center p-6 bg-amber-400 border-4 border-black shadow-[4px_4px_0_0_rgba(0,0,0,1)] hover:translate-y-1 hover:shadow-none transition-all group"
        >
          <Icon icon="fluent:target-arrow-24-filled" class="w-12 h-12 mb-2 group-hover:scale-110 transition-transform" />
          <span class="text-2xl font-black uppercase italic">Challenge</span>
          <span class="text-sm font-bold mt-2">เล่นเก็บคะแนน มีเวลาจำกัด 10 ด่าน</span>
        </button>

        <!-- Training Mode -->
        <button 
          @click="gameMode = 'training'; currentLevel = 1; startGame()"
          class="flex flex-col items-center p-6 bg-sky-400 border-4 border-black shadow-[4px_4px_0_0_rgba(0,0,0,1)] hover:translate-y-1 hover:shadow-none transition-all group"
        >
          <Icon icon="fluent:lightbulb-24-filled" class="w-12 h-12 mb-2 group-hover:scale-110 transition-transform" />
          <span class="text-2xl font-black uppercase italic">Training</span>
          <span class="text-sm font-bold mt-2">เล่นเรื่อยๆ ไม่มีวันแพ้ ฝึกฝนทักษะ</span>
        </button>
      </div>
      
      <!-- Quick Leaderboard Info -->
      <div class="mt-10 pt-6 border-t-4 border-black border-dashed">
        <h3 class="font-black uppercase mb-4 text-center underline decoration-4 decoration-emerald-400">Hall of Fame</h3>
        <div class="flex flex-col gap-2">
          <div v-if="loadingLeaderboard" class="text-center italic font-bold">Loading records...</div>
          <div v-else-if="leaderboardData.length === 0" class="text-center italic font-bold">No records yet. Be the first!</div>
          <div v-for="(entry, idx) in leaderboardData.slice(0, 3)" :key="entry.id" class="flex justify-between items-center bg-gray-100 border-2 border-black px-4 py-2 font-bold">
            <span class="flex items-center gap-2">
              <span class="w-6 h-6 flex items-center justify-center bg-black text-white text-xs">{{ idx + 1 }}</span>
              {{ entry.player_name }}
            </span>
            <span class="text-emerald-600">{{ entry.score.toLocaleString() }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. CHALLENGE SETUP (Level Selection) -->
    <div v-else-if="gameState === 'challengeSetup'" class="bg-white border-4 border-black p-4 sm:p-8 shadow-[8px_8px_0_0_rgba(0,0,0,1)] rounded-none">
      <div class="flex items-center gap-4 mb-8">
        <button @click="gameState = 'selectMode'" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 border-4 border-black bg-gray-200 hover:bg-gray-300">
          <Icon icon="fluent:arrow-left-24-filled" class="w-6 h-6" />
        </button>
        <h2 class="text-3xl font-black uppercase italic">Select Level</h2>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <button 
          v-for="lv in 10" :key="lv"
          @click="currentLevel = lv; startGame()"
          class="aspect-square flex flex-col items-center justify-center border-4 border-black font-black text-2xl transition-all"
          :class="[
            currentLevel === lv ? 'bg-amber-400 -translate-y-1 shadow-[4px_4px_0_0_rgba(0,0,0,1)]' : 'bg-white hover:bg-amber-100'
          ]"
        >
          <span class="text-xs uppercase font-bold opacity-50">Lv.</span>
          {{ lv }}
        </button>
      </div>
      
      <div class="mt-8 p-4 bg-emerald-100 border-4 border-black border-dashed font-bold">
        <p class="text-sm italic">"ยิ่งด่านสูง ตัวเลขจะยิ่งเยอะและตัวดำเนินการจะยิ่งซับซ้อนขึ้น!"</p>
      </div>
    </div>

    <!-- 3. PLAYING -->
    <div v-else-if="gameState === 'playing'" class="flex flex-col gap-6">
      
      <!-- Stats Header -->
      <div class="flex justify-between items-stretch gap-4">
        <div class="flex-1 bg-white border-4 border-black p-3 shadow-[4px_4px_0_0_rgba(0,0,0,1)] flex flex-col items-center justify-center">
          <span class="text-xs font-black uppercase text-gray-400">Score</span>
          <span class="text-2xl font-black tabular-nums">{{ score.toLocaleString() }}</span>
        </div>
        <div class="flex-1 bg-white border-4 border-black p-3 shadow-[4px_4px_0_0_rgba(0,0,0,1)] flex flex-col items-center justify-center">
          <span class="text-xs font-black uppercase text-gray-400">{{ gameMode === 'challenge' ? 'Time Left' : 'Elapsed' }}</span>
          <span class="text-2xl font-black tabular-nums" :class="timeLeft < 10 && gameMode === 'challenge' ? 'text-red-600 animate-pulse' : ''">
            {{ gameMode === 'challenge' ? timeLeft : elapsedSeconds }}s
          </span>
        </div>
        <div class="flex-1 bg-white border-4 border-black p-3 shadow-[4px_4px_0_0_rgba(0,0,0,1)] flex flex-col items-center justify-center">
          <span class="text-xs font-black uppercase text-gray-400">Level</span>
          <span class="text-2xl font-black tabular-nums">{{ currentLevel }}</span>
        </div>
      </div>

      <!-- Timer Bar (Only Challenge) -->
      <div v-if="gameMode === 'challenge'" class="w-full h-8 bg-black border-4 border-black p-1 overflow-hidden">
        <div 
          class="h-full transition-all duration-1000 ease-linear"
          :class="timerColor"
          :style="{ width: `${timerPercent}%` }"
        ></div>
      </div>

      <!-- Question Box -->
      <div class="bg-white border-4 border-black p-4 sm:p-12 shadow-[8px_8px_0_0_rgba(0,0,0,1)] relative overflow-hidden min-h-[300px] flex flex-col items-center justify-center">
        
        <!-- Feedback Overlay -->
        <div v-if="lastFeedback" 
             class="absolute inset-0 z-10 flex items-center justify-center transition-all duration-300"
             :class="lastFeedback.isCorrect ? 'bg-emerald-400/90' : 'bg-red-400/90'">
          <div class="text-center animate-bounce">
            <Icon :icon="lastFeedback.isCorrect ? 'fluent:checkmark-circle-24-filled' : 'fluent:error-circle-24-filled'" class="w-24 h-24 mx-auto mb-2" />
            <span class="text-4xl font-black uppercase italic">{{ lastFeedback.isCorrect ? 'Correct!' : 'Wrong!' }}</span>
            <div v-if="!lastFeedback.isCorrect" class="text-2xl font-bold mt-2">Answer: {{ lastFeedback.correctAnswer }}</div>
          </div>
        </div>

        <!-- Numbers Sequence -->
        <div class="flex flex-wrap items-center justify-center gap-4 mb-8">
          <div v-for="(num, idx) in question.nums" :key="idx" class="flex items-center gap-4">
            <span v-if="idx > 0" class="text-4xl font-black">{{ num > 0 ? '+' : '' }}</span>
            <div class="px-6 py-4 border-4 border-black text-5xl font-black shadow-[4px_4px_0_0_rgba(0,0,0,1)]"
                 :class="num > 0 ? 'bg-amber-100' : 'bg-red-100'">
              {{ num }}
            </div>
          </div>
          <span class="text-5xl font-black">= ?</span>
        </div>

        <!-- Input Area -->
        <div class="w-full max-w-sm mt-4">
          <input 
            ref="inputRef"
            v-model="answerInput"
            type="text"
            inputmode="numeric"
            pattern="-?[0-9]*"
            class="w-full text-center py-6 text-6xl font-black border-4 border-black shadow-[4px_4px_0_0_rgba(0,0,0,1)] focus:outline-none focus:ring-0 focus:translate-y-1 focus:shadow-none transition-all placeholder:text-gray-200"
            placeholder="???"
            :disabled="isSubmitting"
            @keyup.enter="submitAnswer"
          />
          <button 
            @click="submitAnswer"
            :disabled="isSubmitting || answerInput === ''"
            class="w-full mt-6 py-4 bg-emerald-400 border-4 border-black shadow-[4px_4px_0_0_rgba(0,0,0,1)] active:translate-y-1 active:shadow-none font-black text-2xl uppercase italic disabled:opacity-50 disabled:translate-y-1 disabled:shadow-none"
          >
            Submit
          </button>
        </div>
      </div>

      <!-- Footer Controls -->
      <div class="flex justify-between">
        <button @click="quitGame" class="min-h-[44px] sm:min-h-0 px-6 py-2 bg-red-500 text-white font-black border-4 border-black shadow-[4px_4px_0_0_rgba(0,0,0,1)] hover:translate-y-1 hover:shadow-none transition-all uppercase italic">
          Quit
        </button>
        <div class="flex gap-4">
          <div class="flex flex-col items-center">
            <span class="text-[10px] font-black uppercase text-gray-500">Correct</span>
            <span class="text-xl font-black text-emerald-600">{{ correctCount }}</span>
          </div>
          <div class="flex flex-col items-center">
            <span class="text-[10px] font-black uppercase text-gray-500">Wrong</span>
            <span class="text-xl font-black text-red-600">{{ wrongCount }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 4. RESULT SCREEN -->
    <div v-else-if="gameState === 'result'" class="bg-white border-4 border-black p-4 sm:p-8 shadow-[8px_8px_0_0_rgba(0,0,0,1)] rounded-none text-center">
      <div class="mb-8">
        <div class="inline-block p-6 bg-amber-400 border-4 border-black shadow-[4px_4px_0_0_rgba(0,0,0,1)] rounded-full mb-4">
          <Icon icon="fluent:trophy-24-filled" class="w-20 h-20" />
        </div>
        <h2 class="text-5xl font-black uppercase italic italic tracking-tighter">Time's Up!</h2>
        <p class="text-xl font-bold text-gray-600 mt-2">ยอดเยี่ยมมาก! มาดูคะแนนของคุณกัน</p>
      </div>

      <div class="grid grid-cols-2 gap-4 mb-8">
        <div class="p-6 bg-emerald-100 border-4 border-black">
          <div class="text-xs font-black uppercase opacity-50 mb-1">Total Score</div>
          <div class="text-4xl font-black">{{ score.toLocaleString() }}</div>
        </div>
        <div class="p-6 bg-sky-100 border-4 border-black">
          <div class="text-xs font-black uppercase opacity-50 mb-1">Max Level</div>
          <div class="text-4xl font-black">{{ currentLevel }}</div>
        </div>
        <div class="p-6 bg-gray-100 border-4 border-black">
          <div class="text-xs font-black uppercase opacity-50 mb-1">Accuracy</div>
          <div class="text-4xl font-black">
            {{ correctCount + wrongCount > 0 ? Math.round((correctCount / (correctCount + wrongCount)) * 100) : 0 }}%
          </div>
        </div>
        <div class="p-6 bg-gray-100 border-4 border-black">
          <div class="text-xs font-black uppercase opacity-50 mb-1">Answers</div>
          <div class="text-4xl font-black">{{ correctCount }} <span class="text-xl opacity-30">/ {{ correctCount + wrongCount }}</span></div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col gap-4">
        <button 
          @click="startGame"
          class="w-full py-6 bg-emerald-400 border-4 border-black shadow-[4px_4px_0_0_rgba(0,0,0,1)] hover:translate-y-1 hover:shadow-none transition-all font-black text-3xl uppercase italic"
        >
          Play Again
        </button>
        <div class="grid grid-cols-2 gap-4">
          <button 
            @click="gameState = 'challengeSetup'"
            class="py-4 bg-amber-400 border-4 border-black shadow-[4px_4px_0_0_rgba(0,0,0,1)] hover:translate-y-1 hover:shadow-none transition-all font-black text-xl uppercase italic"
          >
            Change Level
          </button>
          <button 
            @click="gameState = 'selectMode'"
            class="py-4 bg-gray-200 border-4 border-black shadow-[4px_4px_0_0_rgba(0,0,0,1)] hover:translate-y-1 hover:shadow-none transition-all font-black text-xl uppercase italic"
          >
            Main Menu
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.mental-math-container {
  max-width: 800px;
  margin: 0 auto;
}

/* Custom transitions or animations can be added here */
@keyframes bounce-subtle {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-5px); }
}

.animate-bounce-subtle {
  animation: bounce-subtle 2s infinite ease-in-out;
}
</style>
