<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useTypingStore } from '~/stores/typing'
import VirtualKeyboard from '../ui/VirtualKeyboard.vue'
import FingerGuide from '../ui/FingerGuide.vue'
import Hand3DOverlay from '../ui/Hand3DOverlay.vue'
import {
  FINGER_STYLES,
  KEY_FINGER_BY_CODE,
  LAYOUT_MAP,
  type TrainingLang,
} from '~/composables/useKeyTraining'
import {
  useTypingCurriculum,
  type CurriculumResponse,
  type TypingStageItem,
  type StageDetailResponse,
  type StageSubmitResult,
} from '~/composables/useTypingCurriculum'

const store = useTypingStore()
const route = useRoute()
const { fetchCurriculum, fetchStage, submitStageAttempt } = useTypingCurriculum()

const props = withDefaults(defineProps<{ autoStart?: boolean }>(), { autoStart: false })
const emit = defineEmits<{ (e: 'finished', result: Record<string, unknown>): void }>()

// ── Language & Level Selection ──────────────────────────────────────────────
const selectedLang = ref<'th' | 'en'>(
  store.selectedLang === 'th' || store.selectedLang === 'en' ? store.selectedLang : 'th'
)
const selectedLevel = ref<number>(1)

const LANG_OPTIONS: { id: 'th' | 'en'; label: string; flag: string }[] = [
  { id: 'th', label: 'ภาษาไทย (เกษมณี)', flag: '🇹🇭' },
  { id: 'en', label: 'English (QWERTY)', flag: '🇬🇧' },
]

const LEVELS = [
  { level: 1, title: 'ระดับ 1: แป้นเหย้า (15 ด่าน)', enTitle: 'Level 1: Home Row (15 Stages)', desc: 'เริ่มจากแป้นน้อยตัว สลับนิ้ว คำสั้น และสอบท้ายระดับ', icon: 'heroicons:home' },
  { level: 2, title: 'ระดับ 2: แถวบน + แป้นเหย้า', enTitle: 'Level 2: Top Row + Home Row', desc: 'เอื้อมนิ้วสู่แถวบนพร้อมดึงกลับแป้นเหย้า', icon: 'heroicons:arrow-up' },
  { level: 3, title: 'ระดับ 3: แถวล่าง + ผสมแถว', enTitle: 'Level 3: Bottom Row + Mixed', desc: 'ฝึกแถวล่างและผสมคำจากทั้ง 3 แถวหลัก', icon: 'heroicons:arrow-down' },
  { level: 4, title: 'ระดับ 4: การฝึกผสมทุกแถว', enTitle: 'Level 4: Full Keyboard Mastery', desc: 'ผสมทุกแถว แถวบนสุด และข้อความต่อเนื่อง', icon: 'heroicons:bolt' },
]

// ── Curriculum & Stage Map State ───────────────────────────────────────────
const viewState = ref<'map' | 'ready' | 'playing' | 'submitting' | 'finished'>('map')
const isLoadingMap = ref(true)
const mapError = ref<string | null>(null)
const curriculumData = ref<CurriculumResponse | null>(null)

const isLoadingStage = ref(false)
const stageError = ref<string | null>(null)
const activeStageDetail = ref<StageDetailResponse | null>(null)
const activeStage = computed(() => activeStageDetail.value?.stage ?? null)

// ── Gameplay State ─────────────────────────────────────────────────────────
const sequence = ref<string[]>([])
const currentPromptSetIdx = ref(0)
const currentIdx = ref(0)
const mistakes = ref(0)
const totalKeystrokes = ref(0)
const correctKeystrokes = ref(0)
const streak = ref(0)
const maxStreak = ref(0)
const elapsedMs = ref(0)
const lastPressedCode = ref('')
const lastPressedChar = ref('')
const lastStatus = ref<'correct' | 'incorrect' | 'none'>('none')
const keyStats = ref<Record<string, { correct: number; total: number }>>({})
const pressTick = ref(0)

let ticker: ReturnType<typeof setInterval> | null = null
let statusTimer: ReturnType<typeof setTimeout> | null = null

const submitResult = ref<StageSubmitResult | null>(null)
const submitError = ref<string | null>(null)

// ── Computed Metrics & Helpers ─────────────────────────────────────────────
const currentLevelMeta = computed(() =>
  LEVELS.find(l => l.level === selectedLevel.value) ?? LEVELS[0]
)

const isLevelUnlocked = computed(() => {
  const stages = curriculumData.value?.stages ?? []
  return stages.some(s => s.is_unlocked)
})

const recommendedStage = computed<TypingStageItem | null>(() => {
  const stages = curriculumData.value?.stages ?? []
  const unlockedNotPassed = stages.find(s => s.is_unlocked && !s.is_passed)
  if (unlockedNotPassed) return unlockedNotPassed
  const unlockedList = stages.filter(s => s.is_unlocked)
  return unlockedList.length > 0 ? unlockedList[unlockedList.length - 1] : (stages[0] ?? null)
})

const currentKey = computed(() => sequence.value[currentIdx.value] ?? '')

const charToCode = computed(() => {
  const map = LAYOUT_MAP[selectedLang.value as TrainingLang] ?? {}
  const reverse: Record<string, string> = {}
  for (const [code, char] of Object.entries(map)) {
    reverse[char] = code
  }
  return reverse
})

const targetCode = computed(() => charToCode.value[currentKey.value] ?? '')

const currentFingerIndex = computed(() => {
  const idx = KEY_FINGER_BY_CODE[targetCode.value]
  return idx !== undefined ? idx : null
})

const currentFinger = computed(() =>
  currentFingerIndex.value !== null ? FINGER_STYLES[currentFingerIndex.value] : null
)

const elapsedSeconds = computed(() => Math.floor(elapsedMs.value / 1000))

const accuracy = computed(() => {
  if (totalKeystrokes.value === 0) return 100
  return Number(((correctKeystrokes.value / totalKeystrokes.value) * 100).toFixed(1))
})

const wpm = computed(() => {
  const minutes = Math.max(1, elapsedSeconds.value) / 60
  return Math.round((correctKeystrokes.value / 5) / minutes)
})

const cpm = computed(() => {
  const minutes = Math.max(1, elapsedSeconds.value) / 60
  return Math.round(correctKeystrokes.value / minutes)
})

const progress = computed(() => {
  if (sequence.value.length === 0) return 0
  return Math.round((currentIdx.value / sequence.value.length) * 100)
})

const weakKeys = computed(() =>
  Object.entries(keyStats.value)
    .filter(([k, v]) => k !== ' ' && v.total > 0 && (v.correct / v.total < 0.85 || v.total - v.correct >= 2))
    .sort((a, b) => (a[1].correct / a[1].total) - (b[1].correct / b[1].total))
    .map(([k]) => k)
)

function keyAccuracyPct(key: string): number {
  const s = keyStats.value[key]
  if (!s || !s.total) return 100
  return Math.round((s.correct / s.total) * 100)
}

function getStageTypeBadge(type: string) {
  switch (type) {
    case 'drill': return { label: 'ฝึกปุ่ม', color: 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30' }
    case 'words': return { label: 'คำสั้น', color: 'bg-blue-500/15 text-blue-300 border-blue-500/30' }
    case 'phrases': return { label: 'กลุ่มคำ', color: 'bg-purple-500/15 text-purple-300 border-purple-500/30' }
    case 'review': return { label: 'ซ่อมจุดอ่อน', color: 'bg-amber-500/15 text-amber-300 border-amber-500/30' }
    case 'exam': return { label: '🏆 สอบท้ายระดับ', color: 'bg-rose-500/20 text-rose-300 border-rose-500/40' }
    default: return { label: 'ฝึกฝน', color: 'bg-slate-500/15 text-slate-300 border-slate-500/30' }
  }
}

function formatTime(s: number) {
  return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`
}

// ── Data Fetching & Stage Selection ────────────────────────────────────────
async function loadCurriculum() {
  isLoadingMap.value = true
  mapError.value = null
  try {
    const data = await fetchCurriculum(selectedLang.value, selectedLevel.value)
    curriculumData.value = data
  } catch (err: any) {
    console.error('Failed to load curriculum:', err)
    mapError.value = err?.data?.message || 'ไม่สามารถโหลดข้อมูลด่านฝึกได้ กรุณาเข้าสู่ระบบหรือลองใหม่อีกครั้ง'
  } finally {
    isLoadingMap.value = false
  }
}

watch([selectedLang, selectedLevel], () => {
  store.selectedLang = selectedLang.value
  viewState.value = 'map'
  loadCurriculum()
})

function buildSequence(rotateSet = false) {
  if (!activeStage.value) return
  const pool = activeStage.value.exercise_pool || []
  if (pool.length === 0) {
    sequence.value = []
    return
  }

  if (rotateSet) {
    currentPromptSetIdx.value = (currentPromptSetIdx.value + 1) % pool.length
  }

  // Pick 3 sets starting from currentPromptSetIdx so each session has multiple balanced sets
  // using only the stage's learned keys. For exam stages, combine all sets for sustained 60s typing.
  let chosenSets: string[] = []
  if (activeStage.value.stage_type === 'exam') {
    const rotated = [
      ...pool.slice(currentPromptSetIdx.value),
      ...pool.slice(0, currentPromptSetIdx.value),
    ]
    chosenSets = [...rotated, ...rotated]
  } else {
    const count = Math.min(pool.length, 3)
    for (let i = 0; i < count; i++) {
      chosenSets.push(pool[(currentPromptSetIdx.value + i) % pool.length])
    }
  }

  const fullText = chosenSets.join(' ')
  sequence.value = fullText.split('')
}

async function openStage(stageItem: TypingStageItem | { id: number; is_unlocked?: boolean }) {
  if ('is_unlocked' in stageItem && stageItem.is_unlocked === false) {
    stageError.value = 'ด่านนี้ยังล็อกอยู่ ต้องผ่านด่านก่อนหน้าตามลำดับก่อนจึงจะเข้าฝึกได้'
    return
  }

  isLoadingStage.value = true
  stageError.value = null
  try {
    const detail = await fetchStage(stageItem.id)
    activeStageDetail.value = detail
    if (detail?.stage) {
      if (detail.stage.level && detail.stage.level !== selectedLevel.value) {
        selectedLevel.value = detail.stage.level
      }
      if (detail.stage.language && detail.stage.language !== selectedLang.value) {
        selectedLang.value = detail.stage.language
        store.selectedLang = detail.stage.language
      }
    }
    currentPromptSetIdx.value = 0
    prepareStageRound(false)
  } catch (err: any) {
    console.error('Failed to open stage:', err)
    stageError.value = err?.data?.errors?.stage?.[0] || err?.data?.message || 'ด่านนี้ยังไม่ได้รับการปลดล็อก กรุณาผ่านด่านก่อนหน้าก่อน'
    viewState.value = 'map'
  } finally {
    isLoadingStage.value = false
  }
}

function prepareStageRound(rotateSet = false) {
  if (ticker) clearInterval(ticker)
  if (statusTimer) clearTimeout(statusTimer)
  buildSequence(rotateSet)
  currentIdx.value = 0
  mistakes.value = 0
  totalKeystrokes.value = 0
  correctKeystrokes.value = 0
  streak.value = 0
  maxStreak.value = 0
  elapsedMs.value = 0
  lastPressedCode.value = ''
  lastPressedChar.value = ''
  lastStatus.value = 'none'
  keyStats.value = {}
  submitResult.value = null
  submitError.value = null
  viewState.value = 'ready'
}

function startStagePlaying() {
  if (!activeStage.value) return
  viewState.value = 'playing'
  const t0 = Date.now()
  ticker = setInterval(() => {
    elapsedMs.value = Date.now() - t0
    if (activeStage.value?.stage_type === 'exam' && activeStage.value.min_duration_sec > 0) {
      if (elapsedSeconds.value >= activeStage.value.min_duration_sec) {
        finishStageRound()
      }
    }
  }, 100)
}

function backToMap() {
  if (ticker) clearInterval(ticker)
  if (statusTimer) clearTimeout(statusTimer)
  viewState.value = 'map'
  loadCurriculum()
}

// ── Keyboard Handling (Physical Key Code) ──────────────────────────────────
function onKeyDown(e: KeyboardEvent) {
  if (viewState.value === 'ready' && e.code === 'Space') {
    e.preventDefault()
    startStagePlaying()
    return
  }

  if (viewState.value !== 'playing') return
  if (e.ctrlKey || e.altKey || e.metaKey) return

  // Backspace is explicitly disabled in sequential keystroke drills; ignore without penalty
  if (e.code === 'Backspace') {
    e.preventDefault()
    return
  }

  const map = LAYOUT_MAP[selectedLang.value as TrainingLang]
  if (!map || !(e.code in map)) return

  e.preventDefault()
  const pressedChar = map[e.code]
  const target = currentKey.value
  const isCorrect = pressedChar === target

  totalKeystrokes.value++
  pressTick.value = Date.now()
  lastPressedCode.value = e.code
  lastPressedChar.value = pressedChar
  lastStatus.value = isCorrect ? 'correct' : 'incorrect'

  if (!keyStats.value[target]) {
    keyStats.value[target] = { correct: 0, total: 0 }
  }
  keyStats.value[target].total++

  if (statusTimer) clearTimeout(statusTimer)
  statusTimer = setTimeout(() => {
    lastStatus.value = 'none'
    lastPressedCode.value = ''
    lastPressedChar.value = ''
  }, 350)

  if (isCorrect) {
    correctKeystrokes.value++
    keyStats.value[target].correct++
    streak.value++
    maxStreak.value = Math.max(maxStreak.value, streak.value)
    currentIdx.value++

    if (currentIdx.value >= sequence.value.length) {
      if (activeStage.value?.stage_type === 'exam' && activeStage.value.min_duration_sec > 0 && elapsedSeconds.value < activeStage.value.min_duration_sec) {
        // Append another set if exam timer hasn't finished yet
        const pool = activeStage.value.exercise_pool || []
        if (pool.length > 0) {
          const extra = ' ' + pool[Math.floor(Math.random() * pool.length)]
          sequence.value.push(...extra.split(''))
        }
      } else {
        finishStageRound()
      }
    }
  } else {
    mistakes.value++
    streak.value = 0
  }
}

async function finishStageRound() {
  if (!activeStage.value) return
  if (ticker) clearInterval(ticker)
  viewState.value = 'submitting'

  const timeUsed = Math.max(1, elapsedSeconds.value)
  const finalAccuracy = totalKeystrokes.value > 0
    ? Number(((correctKeystrokes.value / totalKeystrokes.value) * 100).toFixed(2))
    : 100.0
  const finalWpm = Math.round((correctKeystrokes.value / 5) / (timeUsed / 60))

  try {
    const res = await submitStageAttempt(activeStage.value.id, {
      session_token: uuid(),
      wpm: finalWpm,
      accuracy: finalAccuracy,
      time_elapsed: timeUsed,
      correct_chars: correctKeystrokes.value,
      total_chars: totalKeystrokes.value,
      mistakes: mistakes.value,
      max_combo: maxStreak.value,
      weak_keys: weakKeys.value,
    })
    submitResult.value = res
    viewState.value = 'finished'
  } catch (err: any) {
    console.error('Failed to submit stage attempt:', err)
    submitError.value = err?.data?.errors?.stage?.[0] || err?.data?.message || 'ไม่สามารถบันทึกผลคะแนนได้'
    viewState.value = 'finished'
  }
}

async function goToNextStage() {
  const nextId = submitResult.value?.next_stage_id
  if (!nextId) {
    backToMap()
    return
  }
  await openStage({ id: nextId, is_unlocked: true })
  if (activeStage.value && activeStage.value.level !== selectedLevel.value) {
    selectedLevel.value = activeStage.value.level
  }
}

onMounted(async () => {
  window.addEventListener('keydown', onKeyDown)
  await loadCurriculum()

  // Deep-link support if stageId query is passed (still guarded by backend unlock validation)
  const queryStageId = Number(route.query.stageId)
  if (queryStageId > 0) {
    await openStage({ id: queryStageId })
  }
})

watch(() => route.query.stageId, async (newStageId) => {
  const sid = Number(newStageId)
  if (sid > 0 && sid !== activeStage.value?.id) {
    await openStage({ id: sid })
  }
})

onUnmounted(() => {
  if (ticker) clearInterval(ticker)
  if (statusTimer) clearTimeout(statusTimer)
  window.removeEventListener('keydown', onKeyDown)
})
</script>

<template>
  <div class="training-studio max-w-5xl mx-auto space-y-6 rounded-3xl border border-slate-700/70 bg-slate-950 p-3 sm:p-6 text-white shadow-2xl">

    <!-- ══════════════════════════════════════════════════════════════════════
         VIEW 1: PROGRESSIVE LEVEL & STAGE MAP (Default Entry of Key Training)
         ══════════════════════════════════════════════════════════════════════ -->
    <div v-if="viewState === 'map'" class="space-y-6">

      <!-- Top Bar: Title + Language Switcher (Separate TH / EN Progress) -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
        <div class="space-y-1">
          <div class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-widest text-emerald-400">
            <Icon icon="heroicons:cpu-chip" class="text-base" />
            <span>KEY TRAINING · ระบบฝึกพิมพ์ตามลำดับขั้น</span>
          </div>
          <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
            ฝึกพิมพ์ตามแถวแป้นพิมพ์และด่านย่อย
          </h2>
          <p class="text-xs sm:text-sm text-slate-400">
            เริ่มจากแป้นเหย้า 15 ด่าน ฝึกเฉพาะแป้นที่เรียนมาแล้ว ผ่านเกณฑ์ความแม่นยำและความเร็วเพื่อปลดล็อกด่านถัดไป
          </p>
        </div>

        <!-- Language Switcher -->
        <div class="flex flex-col items-start sm:items-end gap-1.5 shrink-0">
          <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">
            ภาษาแป้นพิมพ์ (แยกความก้าวหน้าแต่ละภาษา)
          </span>
          <div class="flex items-center gap-1.5 p-1 bg-slate-900 rounded-2xl border border-slate-800">
            <button
              v-for="langOpt in LANG_OPTIONS"
              :key="langOpt.id"
              @click="selectedLang = langOpt.id"
              class="px-3.5 py-2 text-xs font-black rounded-xl transition-all flex items-center gap-1.5"
              :class="selectedLang === langOpt.id
                ? 'bg-emerald-500 text-slate-950 shadow-md shadow-emerald-500/20'
                : 'text-slate-400 hover:text-white'"
            >
              <span>{{ langOpt.flag }}</span>
              <span>{{ langOpt.label }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Error Alert (e.g. Locked Stage Attempt) -->
      <div
        v-if="stageError"
        class="rounded-2xl border border-rose-500/40 bg-rose-500/10 p-4 flex items-center justify-between gap-3 text-rose-200 text-sm font-bold"
      >
        <div class="flex items-center gap-2.5">
          <Icon icon="heroicons:lock-closed" class="text-xl text-rose-400 shrink-0" />
          <span>{{ stageError }}</span>
        </div>
        <button @click="stageError = null" class="text-xs underline text-rose-300 hover:text-white">ปิด</button>
      </div>

      <!-- Level Selector Tabs (Row Progression: Home Row -> Top Row -> Bottom Row -> Full Mixed) -->
      <div class="space-y-2.5">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <span class="text-xs font-black uppercase tracking-widest text-slate-400">
            ลำดับระดับแป้นพิมพ์ (ปลดล็อกเมื่อผ่านทุกด่านและสอบท้ายระดับผ่าน 2 ใน 3 ครั้งล่าสุด)
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <button
            v-for="lvl in LEVELS"
            :key="lvl.level"
            @click="selectedLevel = lvl.level"
            class="p-4 rounded-2xl border-2 text-left transition-all duration-200"
            :class="selectedLevel === lvl.level
              ? 'border-emerald-400 bg-emerald-500/10 shadow-lg shadow-emerald-500/10'
              : 'border-slate-800 bg-slate-900/70 hover:border-slate-700 text-slate-300'"
          >
            <div class="flex items-center justify-between mb-1">
              <span class="text-[11px] font-black uppercase tracking-wider" :class="selectedLevel === lvl.level ? 'text-emerald-300' : 'text-slate-400'">
                LEVEL {{ lvl.level }}
              </span>
              <Icon :icon="lvl.icon" class="text-base" :class="selectedLevel === lvl.level ? 'text-emerald-400' : 'text-slate-500'" />
            </div>
            <div class="font-black text-sm text-white line-clamp-1">
              {{ selectedLang === 'th' ? lvl.title : lvl.enTitle }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1 line-clamp-1">
              {{ lvl.desc }}
            </div>
          </button>
        </div>
      </div>

      <!-- Level Progress Summary + Quick Continue Button -->
      <div v-if="curriculumData" class="rounded-2xl border border-slate-800 bg-slate-900/90 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1.5">
          <div class="flex flex-wrap items-center gap-2">
            <span class="px-2.5 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 text-xs font-black">
              {{ currentLevelMeta.title }}
            </span>
            <span v-if="!isLevelUnlocked" class="px-2.5 py-0.5 rounded-md bg-rose-500/20 text-rose-300 text-xs font-bold flex items-center gap-1">
              <Icon icon="heroicons:lock-closed" class="text-xs" />
              ต้องสอบผ่านท้ายระดับ {{ selectedLevel - 1 }} ก่อนจึงจะปลดล็อก
            </span>
          </div>
          <p class="text-xs sm:text-sm text-slate-300">
            ผ่านแล้ว <span class="font-black text-white">{{ curriculumData.passed_stages }}/{{ curriculumData.total_stages }}</span> ด่าน
            · ดาวสะสม <span class="font-black text-amber-400">★ {{ curriculumData.total_stars }}</span>
          </p>
          <!-- Scoring rules summary -->
          <p class="text-[11px] text-slate-400">
            เกณฑ์การนับ: 1 Word = 5 อักขระที่กดถูก (พยัญชนะ สระ วรรณยุกต์ไทยนับแยก 1 การกด) · ความแม่นยำคิดจากทุกแป้นที่กดจริง (ไม่ใช้การลบแก้)
          </p>
        </div>

        <div v-if="recommendedStage && recommendedStage.is_unlocked" class="shrink-0">
          <button
            @click="openStage(recommendedStage)"
            class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-black text-sm shadow-lg shadow-emerald-500/25 transition-all hover:scale-[1.02] flex items-center justify-center gap-2"
          >
            <Icon icon="heroicons:play" class="text-base" />
            <span>ฝึกต่อทันที: ด่าน {{ recommendedStage.stage_number }} ({{ recommendedStage.title }})</span>
          </button>
        </div>
      </div>

      <!-- Loading / Error States -->
      <div v-if="isLoadingMap" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
        <div v-for="n in 6" :key="n" class="h-40 rounded-2xl bg-slate-900 animate-pulse border border-slate-800"></div>
      </div>

      <div v-else-if="mapError" class="rounded-2xl border border-rose-500/40 bg-rose-950/30 p-6 text-center space-y-3">
        <p class="font-bold text-rose-200">{{ mapError }}</p>
        <button @click="loadCurriculum" class="px-5 py-2 rounded-xl bg-rose-600 text-white text-xs font-black">
          ลองใหม่อีกครั้ง
        </button>
      </div>

      <!-- Stage Cards Grid -->
      <div v-else-if="curriculumData" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
        <div
          v-for="stg in curriculumData.stages"
          :key="stg.id"
          @click="openStage(stg)"
          class="rounded-2xl border-2 p-4 transition-all duration-200 flex flex-col justify-between"
          :class="[
            stg.is_unlocked
              ? 'cursor-pointer bg-slate-900/90 hover:bg-slate-900 hover:scale-[1.01]'
              : 'opacity-55 cursor-not-allowed bg-slate-950 border-dashed border-slate-800',
            stg.is_passed
              ? 'border-emerald-500/50'
              : stg.is_unlocked
                ? 'border-cyan-400/60 ring-1 ring-cyan-400/20'
                : 'border-slate-800',
            stg.stage_type === 'exam' ? 'sm:col-span-2 lg:col-span-3 bg-gradient-to-r from-slate-900 via-rose-950/20 to-slate-900' : ''
          ]"
        >
          <div class="space-y-2.5">
            <!-- Top row: stage number, type badge, stars -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span
                  class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-black"
                  :class="stg.is_passed
                    ? 'bg-emerald-500 text-slate-950'
                    : stg.is_unlocked
                      ? 'bg-cyan-500 text-slate-950'
                      : 'bg-slate-800 text-slate-500'"
                >
                  {{ stg.stage_number }}
                </span>
                <span
                  class="px-2 py-0.5 rounded-md text-[10px] font-black border"
                  :class="getStageTypeBadge(stg.stage_type).color"
                >
                  {{ getStageTypeBadge(stg.stage_type).label }}
                </span>
              </div>

              <div class="flex items-center gap-0.5 text-sm">
                <span
                  v-for="s in 3"
                  :key="s"
                  :class="s <= stg.stars ? 'text-amber-400' : 'text-slate-700'"
                >★</span>
              </div>
            </div>

            <!-- Title & desc -->
            <div>
              <h3 class="font-bold text-sm text-white line-clamp-1">{{ stg.title }}</h3>
              <p class="text-xs text-slate-400 mt-0.5 line-clamp-2">{{ stg.description }}</p>
            </div>

            <!-- Target keys preview -->
            <div class="flex flex-wrap items-center gap-1 pt-1">
              <span class="text-[10px] font-bold text-slate-400">แป้นที่ใช้:</span>
              <span
                v-for="k in stg.target_keys.slice(0, 10)"
                :key="k"
                class="px-1.5 py-0.5 text-[11px] font-mono font-bold bg-slate-800 rounded border border-slate-700 text-cyan-200"
              >
                {{ k === ' ' ? 'Space' : k }}
              </span>
              <span v-if="stg.target_keys.length > 10" class="text-[10px] text-slate-400 font-bold">
                +{{ stg.target_keys.length - 10 }}
              </span>
            </div>
          </div>

          <!-- Footer: pass criteria & action -->
          <div class="pt-3 mt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px]">
            <div class="space-y-0.5 text-slate-300">
              <div>
                เกณฑ์ผ่าน: <span class="font-bold text-emerald-300">แม่นยำ ≥ {{ Math.round(stg.min_accuracy) }}%</span>
                <span v-if="stg.min_wpm > 0" class="font-bold text-cyan-300"> · ≥ {{ stg.min_wpm }} WPM</span>
                <span v-else class="text-slate-400"> · ไม่จำกัดความเร็ว</span>
              </div>
              <div v-if="stg.stage_type === 'exam'" class="text-rose-300 font-bold">
                เงื่อนไขพิเศษ: เวลา {{ stg.min_duration_sec }}s · ผ่าน {{ stg.pass_count_needed }} ใน {{ stg.pass_window_size }} ครั้งล่าสุด
              </div>
              <div v-else-if="stg.best_accuracy > 0" class="text-slate-400">
                สถิติสูงสุด: {{ stg.best_wpm }} WPM · {{ Math.round(stg.best_accuracy) }}%
              </div>
            </div>

            <button
              v-if="stg.is_unlocked"
              class="px-3.5 py-1.5 rounded-xl font-black text-xs transition-all flex items-center gap-1 shrink-0"
              :class="stg.is_passed
                ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-500/30'
                : 'bg-cyan-400 text-slate-950 hover:bg-cyan-300'"
            >
              <span>{{ stg.is_passed ? 'ทบทวน' : 'เริ่มด่าน' }}</span>
              <Icon icon="heroicons:arrow-right" class="text-xs" />
            </button>
            <span v-else class="inline-flex items-center gap-1 text-slate-500 font-bold shrink-0">
              <Icon icon="heroicons:lock-closed" class="text-xs" />
              ล็อก
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         VIEW 2: STAGE READY / PLAYING STUDIO (Uses VirtualKeyboard + Hand3D)
         ══════════════════════════════════════════════════════════════════════ -->
    <div v-else-if="(viewState === 'ready' || viewState === 'playing' || viewState === 'submitting') && activeStage" class="flex flex-col gap-3">

      <!-- Stage Header & Criteria Bar -->
      <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-800 bg-slate-900/90 p-3.5">
        <div class="flex items-center gap-3 min-w-0">
          <button
            @click="backToMap"
            class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold flex items-center gap-1.5 shrink-0"
          >
            <Icon icon="heroicons:arrow-left" />
            <span>ด่านทั้งหมด</span>
          </button>
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 text-[10px] font-black">
                ระดับ {{ activeStage.level }} · ด่าน {{ activeStage.stage_number }}
              </span>
              <h2 class="truncate text-sm sm:text-base font-black text-white">{{ activeStage.title }}</h2>
            </div>
            <p class="text-[11px] text-slate-400 truncate">{{ activeStage.description }}</p>
          </div>
        </div>

        <!-- Explicit Pass Criteria Pills -->
        <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
          <span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-300">
            ต้องแม่นยำ ≥ {{ Math.round(activeStage.min_accuracy) }}%
          </span>
          <span v-if="activeStage.min_wpm > 0" class="px-2.5 py-1 rounded-lg bg-cyan-500/10 border border-cyan-500/30 text-cyan-300">
            ความเร็ว ≥ {{ activeStage.min_wpm }} WPM
          </span>
          <span v-else class="px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300">
            ไม่บังคับความเร็วขั้นต่ำ
          </span>
          <button
            v-if="viewState === 'ready' && (activeStage.exercise_pool?.length ?? 0) > 1"
            @click="prepareStageRound(true)"
            class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-amber-300 flex items-center gap-1"
            title="สุ่มชุดโจทย์ใหม่ในด่านนี้"
          >
            <Icon icon="heroicons:arrow-path" class="text-xs" />
            <span>เปลี่ยนชุดโจทย์</span>
          </button>
        </div>
      </div>

      <!-- Live Metrics Strip -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-2.5 text-center">
          <div class="text-[10px] font-bold text-slate-400 uppercase">ความแม่นยำ</div>
          <div class="text-xl font-black" :class="accuracy >= activeStage.min_accuracy ? 'text-emerald-400' : 'text-rose-400'">
            {{ accuracy }}%
          </div>
          <div class="text-[10px] text-slate-500">เกณฑ์ ≥ {{ Math.round(activeStage.min_accuracy) }}%</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-2.5 text-center">
          <div class="text-[10px] font-bold text-slate-400 uppercase">ความเร็ว (WPM)</div>
          <div class="text-xl font-black" :class="activeStage.min_wpm <= 0 || wpm >= activeStage.min_wpm ? 'text-cyan-300' : 'text-amber-400'">
            {{ wpm }}
          </div>
          <div class="text-[10px] text-slate-500">{{ cpm }} CPM</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-2.5 text-center">
          <div class="text-[10px] font-bold text-slate-400 uppercase">COMBO</div>
          <div class="text-xl font-black text-amber-400">{{ streak }}x</div>
          <div class="text-[10px] text-slate-500">สูงสุด {{ maxStreak }}x</div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-2.5 text-center">
          <div class="text-[10px] font-bold text-slate-400 uppercase">เวลา</div>
          <div class="text-xl font-black text-white font-mono">{{ formatTime(elapsedSeconds) }}</div>
          <div class="text-[10px] text-slate-500">
            {{ activeStage.min_duration_sec > 0 ? `เป้าหมาย ${activeStage.min_duration_sec}s` : 'เวลาที่ใช้' }}
          </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-2.5 text-center col-span-2 sm:col-span-1">
          <div class="text-[10px] font-bold text-slate-400 uppercase">ความคืบหน้า</div>
          <div class="text-xl font-black text-white">{{ progress }}%</div>
          <div class="text-[10px] text-slate-500">{{ currentIdx }}/{{ sequence.length }} อักขระ</div>
        </div>
      </div>

      <!-- Prompt Ribbon + Active Target Key -->
      <div class="target-stage relative rounded-3xl border border-slate-800 p-4 sm:p-5 space-y-4">
        <!-- Sequence stream -->
        <div class="flex items-center justify-center gap-1 flex-wrap font-mono text-xl sm:text-2xl leading-relaxed max-h-28 overflow-hidden">
          <span
            v-for="(ch, idx) in sequence.slice(Math.max(0, currentIdx - 12), currentIdx + 28)"
            :key="Math.max(0, currentIdx - 12) + idx"
            class="px-1 rounded-md transition-all"
            :class="[
              Math.max(0, currentIdx - 12) + idx < currentIdx ? 'text-emerald-400/60' : '',
              Math.max(0, currentIdx - 12) + idx === currentIdx ? 'bg-cyan-400 text-slate-950 font-black scale-110 shadow-md shadow-cyan-400/40' : '',
              Math.max(0, currentIdx - 12) + idx > currentIdx ? 'text-slate-300' : '',
            ]"
          >
            {{ ch === ' ' ? '␣' : ch }}
          </span>
        </div>

        <!-- Current key & finger hint -->
        <div class="flex items-center justify-center gap-4 text-xs font-bold">
          <span class="px-3 py-1 rounded-lg bg-slate-900 border border-slate-700 text-cyan-200">
            แป้นถัดไป: <strong class="font-mono text-base text-white">{{ currentKey === ' ' ? 'SPACE' : currentKey }}</strong>
          </span>
          <span v-if="currentFinger" class="px-3 py-1 rounded-lg bg-slate-900 border border-slate-700" :class="currentFinger.text">
            นิ้วที่ใช้: {{ currentFinger.label }}
          </span>
        </div>

        <!-- Ready Overlay -->
        <div
          v-if="viewState === 'ready'"
          class="absolute inset-0 rounded-3xl bg-slate-950/85 backdrop-blur-sm flex flex-col items-center justify-center gap-3 p-4 z-20 text-center"
        >
          <p class="text-sm sm:text-base font-bold text-slate-200">
            วางนิ้วทั้งสองมือบนแป้นเหย้า (ด ก ห ฟ และ ่ า ส ว / F J) แล้วเริ่มฝึก
          </p>
          <button
            @click="startStagePlaying"
            class="px-8 py-3.5 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-black text-sm sm:text-base shadow-lg shadow-emerald-500/30 hover:scale-105 transition-all"
          >
            กด SPACE หรือคลิกที่นี่เพื่อเริ่มด่านที่ {{ activeStage.stage_number }} ▶️
          </button>
        </div>
      </div>

      <!-- Virtual Keyboard + 3D Hand Guide -->
      <div class="flex flex-col gap-2 rounded-3xl border border-slate-800 bg-slate-900/80 p-3">
        <VirtualKeyboard
          :target-key="currentKey"
          :lang="selectedLang"
          :last-pressed-code="lastPressedCode"
          :last-status="lastStatus"
        />
        <div class="h-44 sm:h-52">
          <ClientOnly>
            <Hand3DOverlay :finger-index="currentFingerIndex" :press-tick="pressTick" :status="lastStatus" />
            <template #fallback>
              <FingerGuide :finger-index="currentFingerIndex" :status="lastStatus" />
            </template>
          </ClientOnly>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         VIEW 3: STAGE EVALUATION & DIAGNOSTIC RESULT
         ══════════════════════════════════════════════════════════════════════ -->
    <div v-else-if="viewState === 'finished' && activeStage" class="space-y-6 py-2">

      <div
        class="rounded-3xl p-6 sm:p-8 text-center border-2 space-y-3"
        :class="submitResult?.attempt_passed
          ? 'bg-emerald-950/30 border-emerald-500/50'
          : 'bg-rose-950/30 border-rose-500/50'"
      >
        <div class="text-5xl">
          {{ submitResult?.attempt_passed ? '🎉' : '⚠️' }}
        </div>
        <h2 class="text-2xl sm:text-3xl font-black text-white">
          {{ submitResult?.attempt_passed ? 'ผ่านเกณฑ์การฝึกรอบนี้!' : 'ยังไม่ผ่านเกณฑ์ของด่านนี้' }}
        </h2>
        <p class="text-xs sm:text-sm text-slate-300">
          ระดับ {{ activeStage.level }} · ด่าน {{ activeStage.stage_number }}: {{ activeStage.title }}
        </p>

        <!-- Stars -->
        <div v-if="submitResult?.attempt_passed" class="flex justify-center gap-2 text-3xl pt-1">
          <span
            v-for="s in 3"
            :key="s"
            :class="s <= (submitResult?.stars ?? 0) ? 'text-amber-400' : 'text-slate-700'"
          >★</span>
        </div>
      </div>

      <!-- Clear Pass / Fail Reasons -->
      <div
        v-if="submitResult?.reasons?.length"
        class="rounded-2xl border p-4 sm:p-5 space-y-2"
        :class="submitResult.attempt_passed
          ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200'
          : 'border-rose-500/40 bg-rose-500/10 text-rose-200'"
      >
        <div class="font-black text-sm flex items-center gap-2">
          <Icon :icon="submitResult.attempt_passed ? 'heroicons:check-badge' : 'heroicons:exclamation-triangle'" class="text-lg" />
          <span>ผลการตรวจเกณฑ์ด่านที่ {{ activeStage.stage_number }}:</span>
        </div>
        <ul class="list-disc list-inside text-xs sm:text-sm space-y-1">
          <li v-for="(r, idx) in submitResult.reasons" :key="idx">{{ r }}</li>
        </ul>
      </div>

      <div v-if="submitError" class="rounded-2xl border border-amber-500/40 bg-amber-500/10 p-4 text-xs text-amber-200">
        {{ submitError }}
      </div>

      <!-- Metrics Summary -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="rounded-2xl bg-slate-900 border border-slate-800 p-4 text-center">
          <div class="text-xs text-slate-400 font-bold">ความเร็ว</div>
          <div class="text-2xl font-black text-cyan-300 mt-1">{{ submitResult?.wpm ?? wpm }} WPM</div>
          <div class="text-[11px] text-slate-500">เกณฑ์ ≥ {{ activeStage.min_wpm }} WPM</div>
        </div>
        <div class="rounded-2xl bg-slate-900 border border-slate-800 p-4 text-center">
          <div class="text-xs text-slate-400 font-bold">ความแม่นยำ</div>
          <div class="text-2xl font-black mt-1" :class="(submitResult?.accuracy ?? accuracy) >= activeStage.min_accuracy ? 'text-emerald-400' : 'text-rose-400'">
            {{ submitResult?.accuracy ?? accuracy }}%
          </div>
          <div class="text-[11px] text-slate-500">เกณฑ์ ≥ {{ Math.round(activeStage.min_accuracy) }}%</div>
        </div>
        <div class="rounded-2xl bg-slate-900 border border-slate-800 p-4 text-center">
          <div class="text-xs text-slate-400 font-bold">กดผิด</div>
          <div class="text-2xl font-black text-white mt-1">{{ mistakes }} ครั้ง</div>
          <div class="text-[11px] text-slate-500">จาก {{ totalKeystrokes }} การกด</div>
        </div>
        <div class="rounded-2xl bg-slate-900 border border-slate-800 p-4 text-center">
          <div class="text-xs text-slate-400 font-bold">XP ที่ได้รับ</div>
          <div class="text-2xl font-black text-amber-400 mt-1">+{{ submitResult?.xp_earned ?? 0 }}</div>
          <div class="text-[11px] text-slate-500">เวลา {{ formatTime(elapsedSeconds) }}</div>
        </div>
      </div>

      <!-- Per-Key Accuracy & Frequently Missed Keys -->
      <div class="rounded-2xl bg-slate-900 border border-slate-800 p-5 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">
            ความแม่นยำรายแป้นและแป้นที่ผิดบ่อยในรอบนี้
          </h3>
          <span v-if="weakKeys.length === 0" class="text-xs font-bold text-emerald-400">
            ยอดเยี่ยม! ไม่มีแป้นที่ผิดบ่อยในรอบนี้
          </span>
        </div>

        <!-- Weak keys highlight -->
        <div v-if="weakKeys.length > 0" class="rounded-xl bg-rose-950/40 border border-rose-500/30 p-3.5 flex flex-wrap items-center gap-2">
          <span class="text-xs font-bold text-rose-300">แป้นที่ผิดบ่อย (ควรระวังนิ้ว):</span>
          <span
            v-for="k in weakKeys"
            :key="k"
            class="px-2.5 py-1 rounded-lg bg-rose-500/20 border border-rose-500 text-rose-200 font-mono font-black text-sm"
          >
            {{ k }} ({{ keyAccuracyPct(k) }}%)
          </span>
        </div>

        <!-- All stage keys breakdown -->
        <div class="flex flex-wrap gap-2.5">
          <div
            v-for="k in activeStage.target_keys.filter(x => x !== ' ')"
            :key="k"
            class="flex flex-col items-center gap-1"
          >
            <div
              class="w-10 h-10 rounded-xl flex items-center justify-center font-mono font-black text-base border"
              :class="!keyStats[k]
                ? 'bg-slate-800 border-slate-700 text-slate-500'
                : keyAccuracyPct(k) >= 93
                  ? 'bg-emerald-500/20 border-emerald-500 text-emerald-300'
                  : keyAccuracyPct(k) >= 85
                    ? 'bg-amber-500/20 border-amber-500 text-amber-300'
                    : 'bg-rose-500/20 border-rose-500 text-rose-300'"
            >
              {{ k }}
            </div>
            <span class="text-[10px] font-bold text-slate-400">
              {{ keyStats[k] ? `${keyAccuracyPct(k)}%` : '—' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex flex-wrap justify-center gap-3 pt-2">
        <button
          @click="prepareStageRound(true)"
          class="px-6 py-3.5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-white font-black text-sm flex items-center gap-2 transition-all"
        >
          <Icon icon="heroicons:arrow-path" class="text-base" />
          <span>{{ submitResult?.attempt_passed ? 'ฝึกซ้ำด่านนี้ (เปลี่ยนโจทย์)' : 'ลองใหม่อีกครั้ง' }}</span>
        </button>

        <button
          v-if="submitResult?.passed && submitResult?.next_stage_id"
          @click="goToNextStage"
          class="px-8 py-3.5 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-400 hover:from-emerald-400 hover:to-teal-300 text-slate-950 font-black text-sm shadow-lg shadow-emerald-500/25 flex items-center gap-2 transition-all hover:scale-105"
        >
          <span>ไปด่านถัดไป</span>
          <Icon icon="heroicons:arrow-right" class="text-base" />
        </button>

        <button
          @click="backToMap"
          class="px-6 py-3.5 rounded-2xl border border-slate-700 hover:border-slate-500 text-slate-300 font-bold text-sm"
        >
          กลับหน้าเลือกด่าน
        </button>
      </div>
    </div>

  </div>
</template>

<style scoped>
.training-studio {
  background-image: radial-gradient(ellipse at 10% 0%, rgb(16 185 129 / 0.12), transparent 55%);
}
.target-stage {
  background: radial-gradient(ellipse at 50% 50%, rgb(14 165 233 / 0.13), transparent 70%), rgb(15 23 42 / 0.7);
}
</style>
