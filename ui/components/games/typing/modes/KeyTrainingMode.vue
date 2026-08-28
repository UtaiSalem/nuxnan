<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useTypingStore } from '~/stores/typing'
import VirtualKeyboard from '../ui/VirtualKeyboard.vue'
import {
  useKeyTraining,
  LESSON_INFO_BY_LANG,
  FINGER_STYLES,
  KEY_FINGER_BY_CODE,
  LESSON_ORDER,
  LAYOUT_MAP,
  type KeyLesson,
  type TrainingLang,
} from '~/composables/useKeyTraining'

// ── Language & Lesson selection ────────────────────────────────────────────

const store = useTypingStore()
const emit = defineEmits<{ (e: 'finished', result: Record<string, unknown>): void }>()
const selectedLang   = ref<TrainingLang>(store.selectedLang)
const selectedLesson = ref<KeyLesson>(store.selectedKeyLesson)

const LANG_OPTIONS: { id: TrainingLang; label: string; flag: string; dir: string }[] = [
  { id: 'en', label: 'English', flag: '🇬🇧', dir: 'ltr' },
  { id: 'th', label: 'ไทย',     flag: '🇹🇭', dir: 'ltr' },
  { id: 'ar', label: 'عربي',    flag: '🇸🇦', dir: 'rtl' },
]

// When language changes, reset lesson to home_row and re-init
watch(selectedLang, () => {
  store.selectedLang = selectedLang.value
  selectedLesson.value = 'home_row'
  if (gameState.value === 'idle') init()
})

watch(selectedLesson, () => {
  store.selectedKeyLesson = selectedLesson.value
  if (gameState.value === 'idle') init()
})

// Current lesson info shorthand
const currentLessonInfo = computed(() =>
  LESSON_INFO_BY_LANG[selectedLang.value][selectedLesson.value]
)

// ── Core composable ────────────────────────────────────────────────────────
const {
  gameState, sequence, currentIdx, currentKey, targetCode,
  progress, accuracy, correctCount, streak, maxStreak,
  elapsedSeconds, lastPressedCode, lastPressedChar, lastStatus,
  attempts, keyStats, currentFinger,
  init, start, handleKeyCode,
} = useKeyTraining(selectedLesson, selectedLang, 30)

onMounted(() => init())

// ── Keyboard listener (uses e.code — physical key, language-agnostic) ─────
function onKeyDown(e: KeyboardEvent) {
  if (gameState.value !== 'playing') return
  // Ignore modifier / function / arrow keys
  if (e.ctrlKey || e.altKey || e.metaKey) return
  if (!(e.code in LAYOUT_MAP[selectedLang.value])) return
  e.preventDefault()
  handleKeyCode(e.code)
}

onMounted(() => window.addEventListener('keydown', onKeyDown))
onUnmounted(() => window.removeEventListener('keydown', onKeyDown))

// ── Character display animation ────────────────────────────────────────────
const charAnimClass = computed(() => {
  if (lastStatus.value === 'correct')   return 'text-green-400 scale-125'
  if (lastStatus.value === 'incorrect') return 'text-red-400 animate-shake'
  return 'text-white'
})

const charBoxClass = computed(() => {
  if (!currentFinger.value) return 'border-slate-600'
  if (lastStatus.value === 'correct')   return 'border-green-400 shadow-green-400/30'
  if (lastStatus.value === 'incorrect') return 'border-red-400 shadow-red-400/30'
  return `${currentFinger.value.border} ${currentFinger.value.glow}`
})

const isRTL = computed(() => selectedLang.value === 'ar')

// ── Results ────────────────────────────────────────────────────────────────
const resultGrade = computed(() => {
  const a = accuracy.value
  if (a >= 98) return { label: 'S', color: 'from-amber-400 to-yellow-300', text: 'PERFECT!' }
  if (a >= 90) return { label: 'A', color: 'from-green-400 to-teal-400',   text: 'Excellent!' }
  if (a >= 75) return { label: 'B', color: 'from-blue-400 to-violet-400',  text: 'Good job!' }
  if (a >= 60) return { label: 'C', color: 'from-orange-400 to-amber-400', text: 'Keep going!' }
  return { label: 'D', color: 'from-red-400 to-pink-500', text: 'Practice more!' }
})

function keyAccuracyPct(key: string): number {
  const s = keyStats.value[key]
  if (!s || !s.total) return 100
  return Math.round((s.correct / s.total) * 100)
}

const weakKeys = computed(() =>
  Object.entries(keyStats.value)
    .filter(([, v]) => v.total > 0 && v.correct / v.total < 0.8)
    .map(([k]) => k)
)

function formatTime(s: number) {
  return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`
}

function restart() { init() }

function nextLesson() {
  const lessonIdx = LESSON_ORDER.indexOf(selectedLesson.value)
  selectedLesson.value = lessonIdx < LESSON_ORDER.length - 1
    ? LESSON_ORDER[lessonIdx + 1]
    : LESSON_ORDER[0]
}

watch(gameState, (state) => {
  if (state !== 'finished') return
  emit('finished', {
    session_token: uuid(),
    game_mode: 'key_training',
    language: selectedLang.value,
    difficulty: 'beginner',
    correct_chars: correctCount.value,
    total_chars: attempts.value.length,
    correct_words: Math.round(correctCount.value / 5),
    total_words: Math.round(sequence.value.length / 5),
    mistakes: attempts.value.length - correctCount.value,
    max_combo: maxStreak.value,
    time_elapsed: Math.max(1, elapsedSeconds.value),
    time_limit: 0,
  })
})
</script>

<template>
  <div class="max-w-4xl mx-auto space-y-6">

    <!-- ══ IDLE: Language + Lesson selection ══ -->
    <div v-if="gameState === 'idle'" class="space-y-8">

      <!-- Header -->
      <div class="text-center space-y-1">
        <h2 class="text-3xl font-black font-heading text-white uppercase tracking-wider">⌨️ Key Training</h2>
        <p class="text-slate-400 text-sm">ฝึกจำตำแหน่งคีย์ทีละตัว สร้าง muscle memory</p>
      </div>

      <!-- Language selector -->
      <div class="space-y-3">
        <p class="text-xs font-black text-slate-400 uppercase tracking-widest text-center">เลือกภาษา</p>
        <div class="flex justify-center gap-3">
          <button
            v-for="lang in LANG_OPTIONS"
            :key="lang.id"
            @click="selectedLang = lang.id"
            class="flex items-center gap-2 px-6 py-3 rounded-vikinger border-2 font-bold transition-all duration-200"
            :class="selectedLang === lang.id
              ? 'border-transparent bg-gradient-vikinger text-white shadow-vikinger'
              : 'border-slate-700 bg-slate-800/60 text-slate-400 hover:border-slate-500'"
            :dir="lang.dir"
          >
            <span class="text-xl">{{ lang.flag }}</span>
            <span>{{ lang.label }}</span>
          </button>
        </div>
      </div>

      <!-- Lesson cards -->
      <div class="space-y-3">
        <p class="text-xs font-black text-slate-400 uppercase tracking-widest text-center">เลือก Lesson</p>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <button
            v-for="id in LESSON_ORDER"
            :key="id"
            @click="selectedLesson = id"
            class="relative group p-4 rounded-vikinger border-2 text-left transition-all duration-200 overflow-hidden"
            :class="selectedLesson === id
              ? 'border-transparent bg-gradient-to-br text-white shadow-vikinger ' + LESSON_INFO_BY_LANG[selectedLang][id].color
              : 'border-slate-700 bg-slate-800/60 hover:border-slate-500 text-slate-300'"
          >
            <div class="flex items-start gap-3">
              <span class="text-2xl">{{ LESSON_INFO_BY_LANG[selectedLang][id].emoji }}</span>
              <div class="min-w-0">
                <div class="font-bold font-heading" :dir="isRTL ? 'rtl' : 'ltr'">
                  {{ LESSON_INFO_BY_LANG[selectedLang][id].name }}
                </div>
                <div class="text-[11px] mt-0.5 opacity-70 font-mono tracking-wide truncate"
                     :dir="isRTL ? 'rtl' : 'ltr'">
                  {{ LESSON_INFO_BY_LANG[selectedLang][id].desc }}
                </div>
              </div>
            </div>
            <Icon v-if="selectedLesson === id" icon="heroicons:check-circle"
                  class="absolute top-2 right-2 text-white/80 text-lg" />
          </button>
        </div>
      </div>

      <!-- Key preview -->
      <div class="bg-slate-800/60 rounded-vikinger p-4 border border-slate-700 space-y-3">
        <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Keys in this lesson</p>
        <div class="flex flex-wrap gap-2" :dir="isRTL ? 'rtl' : 'ltr'">
          <span
            v-for="key in currentLessonInfo.keys"
            :key="key"
            class="px-2.5 py-1.5 rounded-lg font-mono font-black text-base border text-white"
            :class="currentLessonInfo.color.includes('green')
              ? 'bg-green-500/20 border-green-500'
              : currentLessonInfo.color.includes('blue')
                ? 'bg-blue-500/20 border-blue-500'
                : currentLessonInfo.color.includes('orange')
                  ? 'bg-orange-500/20 border-orange-500'
                  : 'bg-violet-500/20 border-violet-500'"
          >
            {{ key }}
          </span>
        </div>
      </div>

      <!-- Start button -->
      <div class="flex justify-center">
        <button
          @click="start()"
          class="px-12 py-5 font-black text-xl text-white rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 active:scale-95 transition-all duration-200 uppercase tracking-wider bg-gradient-to-r"
          :class="currentLessonInfo.color"
        >
          START TRAINING
        </button>
      </div>
    </div>

    <!-- ══ PLAYING ══ -->
    <div v-else-if="gameState === 'playing'" class="space-y-5">

      <!-- Stats bar -->
      <div class="grid grid-cols-4 gap-3">
        <div class="bg-slate-800/80 rounded-vikinger p-3 text-center border border-slate-700">
          <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Accuracy</div>
          <div class="text-2xl font-black"
               :class="accuracy >= 90 ? 'text-green-400' : accuracy >= 70 ? 'text-amber-400' : 'text-red-400'">
            {{ accuracy }}%
          </div>
        </div>
        <div class="bg-slate-800/80 rounded-vikinger p-3 text-center border border-slate-700">
          <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Keys</div>
          <div class="text-2xl font-black text-white">{{ correctCount }}/{{ sequence.length }}</div>
        </div>
        <div class="bg-slate-800/80 rounded-vikinger p-3 text-center border border-slate-700">
          <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Streak</div>
          <div class="text-2xl font-black text-amber-400">{{ streak }}</div>
        </div>
        <div class="bg-slate-800/80 rounded-vikinger p-3 text-center border border-slate-700">
          <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Time</div>
          <div class="text-2xl font-black text-slate-300 font-mono">{{ formatTime(elapsedSeconds) }}</div>
        </div>
      </div>

      <!-- Progress bar -->
      <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
        <div
          class="h-full bg-gradient-to-r transition-all duration-300"
          :class="currentLessonInfo.color"
          :style="{ width: `${progress}%` }"
        ></div>
      </div>

      <!-- Current character display -->
      <div class="flex flex-col items-center justify-center py-6 gap-4">
        <!-- Language badge -->
        <div class="flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800 border border-slate-700 text-xs font-bold text-slate-400">
          <span>{{ LANG_OPTIONS.find(l => l.id === selectedLang)?.flag }}</span>
          <span>{{ currentLessonInfo.name }}</span>
        </div>

        <!-- Big character box -->
        <div
          class="flex items-center justify-center w-48 h-48 rounded-vikinger-xl border-2 shadow-xl transition-all duration-150"
          :class="charBoxClass"
          style="background: rgba(15,23,42,0.85)"
        >
          <span
            class="font-mono font-black transition-all duration-150 select-none"
            :class="[charAnimClass, isRTL ? 'direction-rtl' : '']"
            style="font-size: 6.5rem; line-height: 1"
          >
            {{ currentKey }}
          </span>
        </div>

        <p class="text-slate-500 text-sm font-bold uppercase tracking-widest">
          Press the highlighted key
        </p>
      </div>

      <!-- Virtual Keyboard -->
      <div class="bg-slate-900/80 rounded-vikinger-xl p-5 border border-slate-700">
        <VirtualKeyboard
          :target-key="currentKey"
          :lang="selectedLang"
          :last-pressed-code="lastPressedCode"
          :last-status="lastStatus"
        />
      </div>
    </div>

    <!-- ══ FINISHED: Results ══ -->
    <div v-else-if="gameState === 'finished'" class="space-y-5">

      <!-- Grade -->
      <div
        class="rounded-vikinger-xl p-8 text-center bg-gradient-to-br text-white space-y-2 shadow-vikinger"
        :class="resultGrade.color"
      >
        <div class="text-8xl font-black font-heading">{{ resultGrade.label }}</div>
        <div class="text-2xl font-bold">{{ resultGrade.text }}</div>
        <div class="text-sm opacity-80 flex items-center justify-center gap-2">
          <span>{{ LANG_OPTIONS.find(l => l.id === selectedLang)?.flag }}</span>
          <span>{{ currentLessonInfo.name }}</span>
          <span>·</span>
          <span>{{ formatTime(elapsedSeconds) }}</span>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-3 gap-4">
        <div class="bg-slate-800/80 rounded-vikinger p-4 text-center border border-slate-700">
          <Icon icon="heroicons:check-circle" class="text-3xl text-green-400 mx-auto mb-1" />
          <div class="text-3xl font-black text-white">{{ accuracy }}%</div>
          <div class="text-xs text-slate-400 uppercase tracking-wider font-bold">Accuracy</div>
        </div>
        <div class="bg-slate-800/80 rounded-vikinger p-4 text-center border border-slate-700">
          <Icon icon="heroicons:bolt" class="text-3xl text-amber-400 mx-auto mb-1" />
          <div class="text-3xl font-black text-white">{{ maxStreak }}</div>
          <div class="text-xs text-slate-400 uppercase tracking-wider font-bold">Best Streak</div>
        </div>
        <div class="bg-slate-800/80 rounded-vikinger p-4 text-center border border-slate-700">
          <Icon icon="heroicons:clock" class="text-3xl text-blue-400 mx-auto mb-1" />
          <div class="text-3xl font-black text-white font-mono">{{ formatTime(elapsedSeconds) }}</div>
          <div class="text-xs text-slate-400 uppercase tracking-wider font-bold">Time</div>
        </div>
      </div>

      <!-- Per-key accuracy -->
      <div class="bg-slate-800/80 rounded-vikinger p-5 border border-slate-700 space-y-3">
        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Key Accuracy</h3>
        <div class="flex flex-wrap gap-3" :dir="isRTL ? 'rtl' : 'ltr'">
          <div
            v-for="key in currentLessonInfo.keys"
            :key="key"
            class="flex flex-col items-center gap-1"
          >
            <div
              class="w-11 h-11 rounded-lg flex items-center justify-center font-mono font-black text-lg border"
              :class="!keyStats[key]
                ? 'bg-slate-700 border-slate-600 text-slate-400'
                : keyAccuracyPct(key) >= 90
                  ? 'bg-green-500/20 border-green-500 text-green-300'
                  : keyAccuracyPct(key) >= 70
                    ? 'bg-amber-500/20 border-amber-500 text-amber-300'
                    : 'bg-red-500/20 border-red-500 text-red-300'"
            >
              {{ key }}
            </div>
            <span
              class="text-[10px] font-bold"
              :class="!keyStats[key] ? 'text-slate-500'
                : keyAccuracyPct(key) >= 90 ? 'text-green-400'
                : keyAccuracyPct(key) >= 70 ? 'text-amber-400' : 'text-red-400'"
            >
              {{ keyStats[key] ? `${keyAccuracyPct(key)}%` : '—' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Weak keys alert -->
      <div
        v-if="weakKeys.length > 0"
        class="bg-red-900/20 border border-red-500/30 rounded-vikinger p-4 flex gap-3"
      >
        <Icon icon="heroicons:exclamation-triangle" class="text-red-400 text-xl shrink-0 mt-0.5" />
        <div>
          <p class="text-red-300 font-bold text-sm">Keys ที่ต้องฝึกเพิ่ม:</p>
          <div class="flex gap-2 mt-2 flex-wrap">
            <span
              v-for="key in weakKeys"
              :key="key"
              class="px-3 py-1 rounded-lg bg-red-500/20 border border-red-500 text-red-300 font-mono font-black"
            >
              {{ key }}
            </span>
          </div>
        </div>
      </div>

      <!-- Action buttons -->
      <div class="flex gap-4 justify-center flex-wrap">
        <button
          @click="restart()"
          class="px-4 sm:px-8 py-4 bg-slate-700 hover:bg-slate-600 text-white font-black rounded-vikinger transition-all hover:scale-105 active:scale-95 flex items-center gap-2"
        >
          <Icon icon="heroicons:arrow-path" class="text-xl" />
          Try Again
        </button>
        <button
          @click="nextLesson()"
          class="px-8 py-4 font-black text-white rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg transition-all hover:scale-105 active:scale-95 flex items-center gap-2 bg-gradient-to-r"
          :class="currentLessonInfo.color"
        >
          Next Lesson
          <Icon icon="heroicons:arrow-right" class="text-xl" />
        </button>
      </div>
    </div>

  </div>
</template>

<style scoped>
@keyframes shake {
  0%, 100% { transform: translateX(0) scale(1); }
  20%       { transform: translateX(-8px) scale(0.97); }
  40%       { transform: translateX(8px) scale(0.97); }
  60%       { transform: translateX(-5px) scale(0.98); }
  80%       { transform: translateX(5px) scale(0.99); }
}
.animate-shake { animation: shake 0.35s ease-in-out; }
.direction-rtl { direction: rtl; }
</style>
