<script setup lang="ts">
import {
  KEY_FINGER_BY_CODE,
  FINGER_STYLES,
  LAYOUT_MAP,
  type TrainingLang,
} from '~/composables/useKeyTraining'

interface Props {
  targetKey: string           // character to type (e.g. 'ฟ', 'a', 'ش')
  lang: TrainingLang          // current training language
  lastPressedCode?: string    // physical code of last key pressed
  lastStatus?: 'correct' | 'incorrect' | 'none'
}

const props = withDefaults(defineProps<Props>(), {
  lastPressedCode: '',
  lastStatus: 'none',
})

// ── Keyboard layout (by physical code) ────────────────────────────────────
const KEYBOARD_CODES: string[][] = [
  // Row 0: numbers + Minus + Equal (ครบ 12 keys)
  ['Digit1','Digit2','Digit3','Digit4','Digit5','Digit6','Digit7','Digit8','Digit9','Digit0','Minus','Equal'],
  // Row 1: QWERTY + [ ] \ (ครบ 13 keys)
  ['KeyQ','KeyW','KeyE','KeyR','KeyT','KeyY','KeyU','KeyI','KeyO','KeyP','BracketLeft','BracketRight','Backslash'],
  // Row 2: Home row
  ['KeyA','KeyS','KeyD','KeyF','KeyG','KeyH','KeyJ','KeyK','KeyL','Semicolon','Quote'],
  // Row 3: Bottom row
  ['KeyZ','KeyX','KeyC','KeyV','KeyB','KeyN','KeyM','Comma','Period','Slash'],
]

// Row stagger offsets — proportional to key unit (key + gap)
// Numbers: 0 | QWERTY: ~0.5 unit | Home: ~0.75 unit | Bottom: ~1.25 unit
const ROW_OFFSETS = ['ml-0', 'ml-5', 'ml-8', 'ml-12']

// English label for each physical code
const EN_LABEL: Record<string, string> = {
  Digit1:'1', Digit2:'2', Digit3:'3', Digit4:'4', Digit5:'5',
  Digit6:'6', Digit7:'7', Digit8:'8', Digit9:'9', Digit0:'0',
  Minus:'-', Equal:'=',
  KeyQ:'Q', KeyW:'W', KeyE:'E', KeyR:'R', KeyT:'T',
  KeyY:'Y', KeyU:'U', KeyI:'I', KeyO:'O', KeyP:'P',
  BracketLeft:'[', BracketRight:']', Backslash:'\\',
  KeyA:'A', KeyS:'S', KeyD:'D', KeyF:'F', KeyG:'G',
  KeyH:'H', KeyJ:'J', KeyK:'K', KeyL:'L',
  Semicolon:';', Quote:"'",
  KeyZ:'Z', KeyX:'X', KeyC:'C', KeyV:'V', KeyB:'B',
  KeyN:'N', KeyM:'M', Comma:',', Period:'.', Slash:'/',
}

// ── Key display helpers ────────────────────────────────────────────────────

function charForCode(code: string): string {
  return LAYOUT_MAP[props.lang]?.[code] ?? ''
}

// Which physical code produces the target character?
const targetCode = computed(() => {
  const map = LAYOUT_MAP[props.lang]
  for (const [code, char] of Object.entries(map)) {
    if (char === props.targetKey) return code
  }
  return ''
})

// Finger for a given code
function fingerOf(code: string) {
  const idx = KEY_FINGER_BY_CODE[code]
  return idx !== undefined ? FINGER_STYLES[idx] : null
}

// ── Key state ──────────────────────────────────────────────────────────────
function keyState(code: string): 'target' | 'flash-correct' | 'flash-wrong' | 'idle' {
  if (code === props.lastPressedCode && props.lastStatus !== 'none') {
    return props.lastStatus === 'correct' ? 'flash-correct' : 'flash-wrong'
  }
  if (code === targetCode.value) return 'target'
  return 'idle'
}

function keyClasses(code: string): string {
  const state  = keyState(code)
  const finger = fingerOf(code)
  const base   = 'relative flex flex-col items-center justify-center rounded-lg text-xs font-bold select-none transition-all duration-150 border overflow-hidden'

  if (state === 'flash-correct') {
    return `${base} bg-green-500 border-green-400 text-white shadow-lg shadow-green-500/70 scale-110`
  }
  if (state === 'flash-wrong') {
    return `${base} bg-red-500 border-red-400 text-white shadow-lg shadow-red-500/60 scale-95`
  }
  if (state === 'target' && finger) {
    return `${base} ${finger.bg} ${finger.border} text-white shadow-xl ${finger.glow} target-key`
  }
  if (finger) {
    return `${base} bg-slate-800 ${finger.border} opacity-60 hover:opacity-80`
  }
  return `${base} bg-slate-800 border-slate-600 text-slate-400 opacity-50`
}

// ── Finger info for the hint banner ───────────────────────────────────────
const targetFinger = computed(() => fingerOf(targetCode.value))

// Show whether lang mode needs dual labels
const isDualLabel = computed(() => props.lang !== 'en')
const isRTL       = computed(() => props.lang === 'ar')
</script>

<template>
  <div class="space-y-2">

    <!-- Keyboard rows — centered within the container -->
    <div class="flex overflow-x-auto py-1">
      <div class="mx-auto w-max shrink-0 space-y-1 px-2">

        <!-- Letter / number rows -->
        <div
          v-for="(row, ri) in KEYBOARD_CODES"
          :key="ri"
          class="flex gap-1"
          :class="ROW_OFFSETS[ri]"
        >
          <div
            v-for="code in row"
            :key="code"
            :class="[keyClasses(code), 'w-8 sm:w-10 md:w-11 h-[clamp(24px,3.6vh,44px)]']"
          >
            <!-- Dual-label: EN tiny top-left + lang char large center -->
            <template v-if="isDualLabel">
              <span
                class="absolute top-0.5 left-1 font-mono opacity-50 leading-none text-[7px] sm:text-[8px]"
                :class="keyState(code) !== 'idle' ? 'text-white' : 'text-slate-400'"
              >
                {{ EN_LABEL[code] }}
              </span>
              <span
                class="font-bold leading-none text-xs sm:text-sm md:text-base"
                :class="[
                  isRTL ? 'direction-rtl' : '',
                  keyState(code) === 'idle' ? (fingerOf(code)?.text ?? 'text-slate-400') : 'text-white',
                ]"
              >
                {{ charForCode(code) || EN_LABEL[code] }}
              </span>
            </template>

            <!-- Single-label (English) -->
            <template v-else>
              <span
                class="font-mono font-bold text-xs sm:text-sm md:text-base"
                :class="keyState(code) === 'idle' ? (fingerOf(code)?.text ?? 'text-slate-400') : 'text-white'"
              >
                {{ EN_LABEL[code] }}
              </span>
            </template>
          </div>
        </div>

        <!-- Space bar — centered under the keyboard block -->
        <div class="flex justify-center">
          <div :class="[keyClasses('Space'), 'h-[clamp(22px,3.4vh,36px)] w-48 sm:w-56 md:w-64 tracking-widest']">
            <span
              class="text-[10px] sm:text-xs font-bold"
              :class="keyState('Space') === 'idle' ? 'text-slate-500' : 'text-white'"
            >SPACE</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Finger legend -->
    <div class="flex flex-wrap justify-center gap-1 pt-0.5">
      <div
        v-for="(f, i) in FINGER_STYLES.slice(0, 8)"
        :key="i"
        class="flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wide transition-colors"
        :class="targetFinger?.label === f.label
          ? `${f.bg} text-white shadow-sm`
          : 'bg-slate-800/60 text-slate-500'"
      >
        <span class="w-1.5 h-1.5 rounded-full inline-block" :class="f.bg"></span>
        {{ f.label }}
      </div>
    </div>
  </div>
</template>

<style scoped>
.target-key {
  transform: scale(1.2);
  z-index: 10;
}
.direction-rtl {
  direction: rtl;
}
</style>
