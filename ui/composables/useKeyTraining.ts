import { ref, computed, onUnmounted } from 'vue'

// ── Types ──────────────────────────────────────────────────────────────────

export type TrainingLang   = 'en' | 'th' | 'ar'
export type KeyLesson      = 'home_row' | 'top_row' | 'bottom_row' | 'numbers' | 'mixed'

export interface LessonInfo {
  name: string
  desc: string
  keys: string[]
  emoji: string
  color: string
}

// ── Physical keyboard code → character maps ────────────────────────────────
// Uses KeyboardEvent.code (physical position, language-agnostic)

export const LAYOUT_MAP: Record<TrainingLang, Record<string, string>> = {
  en: {
    Digit1: '1', Digit2: '2', Digit3: '3', Digit4: '4', Digit5: '5',
    Digit6: '6', Digit7: '7', Digit8: '8', Digit9: '9', Digit0: '0',
    Minus: '-', Equal: '=',
    KeyQ: 'q', KeyW: 'w', KeyE: 'e', KeyR: 'r', KeyT: 't',
    KeyY: 'y', KeyU: 'u', KeyI: 'i', KeyO: 'o', KeyP: 'p',
    BracketLeft: '[', BracketRight: ']', Backslash: '\\',
    KeyA: 'a', KeyS: 's', KeyD: 'd', KeyF: 'f', KeyG: 'g',
    KeyH: 'h', KeyJ: 'j', KeyK: 'k', KeyL: 'l',
    Semicolon: ';', Quote: "'",
    KeyZ: 'z', KeyX: 'x', KeyC: 'c', KeyV: 'v', KeyB: 'b',
    KeyN: 'n', KeyM: 'm', Comma: ',', Period: '.', Slash: '/',
    Space: ' ',
  },

  // Thai Kedmanee layout (มาตรฐานเกษมณี TIS 820-2531)
  // แถวตัวเลข (no Shift): 1→ๅ 2→/ 3→- 4→ภ 5→ถ 6→ุ 7→ึ 8→ค 9→ต 0→จ -→ข =→ช
  th: {
    Digit1: 'ๅ', Digit2: '/', Digit3: '-', Digit4: 'ภ', Digit5: 'ถ',
    Digit6: 'ุ', Digit7: 'ึ', Digit8: 'ค', Digit9: 'ต', Digit0: 'จ',
    Minus: 'ข', Equal: 'ช',
    KeyQ: 'ๆ', KeyW: 'ไ', KeyE: 'ำ', KeyR: 'พ', KeyT: 'ะ',
    KeyY: 'ั', KeyU: 'ี', KeyI: 'ร', KeyO: 'น', KeyP: 'ย',
    BracketLeft: 'บ', BracketRight: 'ล', Backslash: 'ฃ',
    KeyA: 'ฟ', KeyS: 'ห', KeyD: 'ก', KeyF: 'ด', KeyG: 'เ',
    KeyH: '้', KeyJ: '่', KeyK: 'า', KeyL: 'ส',
    Semicolon: 'ว', Quote: 'ง',
    KeyZ: 'ผ', KeyX: 'ป', KeyC: 'แ', KeyV: 'อ', KeyB: 'ิ',
    KeyN: 'ื', KeyM: 'ท', Comma: 'ม', Period: 'ใ', Slash: 'ฝ',
    Space: ' ',
  },

  // Arabic standard keyboard layout
  ar: {
    Digit1: '1', Digit2: '2', Digit3: '3', Digit4: '4', Digit5: '5',
    Digit6: '6', Digit7: '7', Digit8: '8', Digit9: '9', Digit0: '0',
    Minus: '-', Equal: '=',
    KeyQ: 'ض', KeyW: 'ص', KeyE: 'ث', KeyR: 'ق', KeyT: 'ف',
    KeyY: 'غ', KeyU: 'ع', KeyI: 'ه', KeyO: 'خ', KeyP: 'ح',
    BracketLeft: 'ج', BracketRight: 'د', Backslash: 'ذ',
    KeyA: 'ش', KeyS: 'س', KeyD: 'ي', KeyF: 'ب', KeyG: 'ل',
    KeyH: 'ا', KeyJ: 'ت', KeyK: 'ن', KeyL: 'م',
    Semicolon: 'ك', Quote: 'ط',
    KeyZ: 'ئ', KeyX: 'ء', KeyC: 'ؤ', KeyV: 'ر', KeyB: 'لا',
    KeyN: 'ى', KeyM: 'ة', Comma: 'و', Period: 'ز', Slash: 'ظ',
    Space: ' ',
  },
}

// ── Lessons per language ───────────────────────────────────────────────────

export const LESSON_INFO_BY_LANG: Record<TrainingLang, Record<KeyLesson, LessonInfo>> = {
  en: {
    home_row: {
      name: 'Home Row',
      desc: 'A S D F G · H J K L',
      keys: ['a','s','d','f','g','h','j','k','l'],
      emoji: '🏠',
      color: 'from-green-500 to-teal-500',
    },
    top_row: {
      name: 'Top Row',
      desc: 'Q W E R T · Y U I O P',
      keys: ['q','w','e','r','t','y','u','i','o','p'],
      emoji: '🔺',
      color: 'from-blue-500 to-violet-500',
    },
    bottom_row: {
      name: 'Bottom Row',
      desc: 'Z X C V B · N M',
      keys: ['z','x','c','v','b','n','m'],
      emoji: '🔻',
      color: 'from-orange-500 to-red-500',
    },
    numbers: {
      name: 'Numbers',
      desc: '1 2 3 4 5 · 6 7 8 9 0',
      keys: ['1','2','3','4','5','6','7','8','9','0'],
      emoji: '🔢',
      color: 'from-amber-400 to-orange-500',
    },
    mixed: {
      name: 'All Letters',
      desc: 'A–Z สุ่มผสม',
      keys: 'abcdefghijklmnopqrstuvwxyz'.split(''),
      emoji: '⚡',
      color: 'from-pink-500 to-violet-500',
    },
  },

  th: {
    home_row: {
      name: 'แถวกลาง',
      desc: 'ฟ ห ก ด เ · า ส ว ง',
      keys: ['ฟ','ห','ก','ด','เ','้','่','า','ส','ว','ง'],
      emoji: '🏠',
      color: 'from-green-500 to-teal-500',
    },
    top_row: {
      name: 'แถวบน',
      desc: 'ๆ ไ ำ พ ะ ั ี ร น ย บ ล ฃ',
      keys: ['ๆ','ไ','ำ','พ','ะ','ั','ี','ร','น','ย','บ','ล','ฃ'],
      emoji: '🔺',
      color: 'from-blue-500 to-violet-500',
    },
    bottom_row: {
      name: 'แถวล่าง',
      desc: 'ผ ป แ อ ิ · ท ม ใ ฝ',
      keys: ['ผ','ป','แ','อ','ิ','ื','ท','ม','ใ','ฝ'],
      emoji: '🔻',
      color: 'from-orange-500 to-red-500',
    },
    numbers: {
      name: 'แถวตัวเลข',
      desc: 'ๅ / - ภ ถ ุ ึ ค ต จ ข ช',
      // แถวตัวเลข Kedmanee TIS 820: 1→ๅ 2→/ 3→- 4→ภ 5→ถ 6→ุ 7→ึ 8→ค 9→ต 0→จ -→ข =→ช
      keys: ['ๅ','/','ภ','ถ','ุ','ึ','ค','ต','จ','ข','ช'],
      emoji: '🔢',
      color: 'from-amber-400 to-orange-500',
    },
    mixed: {
      name: 'พยัญชนะ',
      desc: 'ผสมทุกแถว (ไม่ใช้ Shift)',
      // พยัญชนะที่กดได้โดยไม่ต้อง Shift ครบทุกแถว
      keys: ['ก','ด','ฟ','ห','ส','ว','ง','พ','ร','น','ย','บ','ล','ผ','ป','ท','ม','ฝ','ข','ช','ค','ต','ภ','ถ'],
      emoji: '⚡',
      color: 'from-pink-500 to-violet-500',
    },
  },

  ar: {
    home_row: {
      name: 'الصف الأوسط',
      desc: 'ش س ي ب ل · ا ت ن م ك',
      keys: ['ش','س','ي','ب','ل','ا','ت','ن','م','ك'],
      emoji: '🏠',
      color: 'from-green-500 to-teal-500',
    },
    top_row: {
      name: 'الصف العلوي',
      desc: 'ض ص ث ق ف غ ع ه خ ح ج د ذ',
      keys: ['ض','ص','ث','ق','ف','غ','ع','ه','خ','ح','ج','د','ذ'],
      emoji: '🔺',
      color: 'from-blue-500 to-violet-500',
    },
    bottom_row: {
      name: 'الصف السفلي',
      desc: 'ئ ء ؤ ر · ى ة و ز ظ',
      keys: ['ئ','ء','ؤ','ر','ى','ة','و','ز','ظ'],
      emoji: '🔻',
      color: 'from-orange-500 to-red-500',
    },
    numbers: {
      name: 'الأرقام',
      desc: '1 2 3 4 5 · 6 7 8 9 0',
      keys: ['1','2','3','4','5','6','7','8','9','0'],
      emoji: '🔢',
      color: 'from-amber-400 to-orange-500',
    },
    mixed: {
      name: 'جميع الحروف',
      desc: 'كل الحروف العربية',
      keys: ['ض','ص','ث','ق','ف','غ','ع','ه','خ','ح','ش','س','ي','ب','ل','ا','ت','ن','م','ك','ئ','ء','ؤ','ر','ى','ة','و','ز','ظ'],
      emoji: '⚡',
      color: 'from-pink-500 to-violet-500',
    },
  },
}

// ── Finger assignment (by physical key code) ───────────────────────────────
// 0=L.Pinky  1=L.Ring  2=L.Middle  3=L.Index
// 4=R.Index  5=R.Middle  6=R.Ring  7=R.Pinky  8=Thumb

export const KEY_FINGER_BY_CODE: Record<string, number> = {
  Digit1: 0, KeyQ: 0, KeyA: 0, KeyZ: 0,
  Digit2: 1, KeyW: 1, KeyS: 1, KeyX: 1,
  Digit3: 2, KeyE: 2, KeyD: 2, KeyC: 2,
  Digit4: 3, Digit5: 3, KeyR: 3, KeyT: 3, KeyF: 3, KeyG: 3, KeyV: 3, KeyB: 3,
  Digit6: 4, Digit7: 4, KeyY: 4, KeyU: 4, KeyH: 4, KeyJ: 4, KeyN: 4, KeyM: 4,
  Digit8: 5, KeyI: 5, KeyK: 5, Comma: 5,
  Digit9: 6, KeyO: 6, KeyL: 6, Period: 6,
  Digit0: 7, KeyP: 7, Semicolon: 7, Quote: 7, Slash: 7,
  BracketLeft: 7, BracketRight: 7, Backslash: 7,
  Minus: 7, Equal: 7,
  Space: 8,
}

// Keep the old KEY_FINGER export for backward compat (char-based, English only)
export const KEY_FINGER: Record<string, number> = {}
for (const [code, char] of Object.entries(LAYOUT_MAP.en)) {
  const finger = KEY_FINGER_BY_CODE[code]
  if (finger !== undefined) KEY_FINGER[char] = finger
}

export interface FingerStyle {
  bg: string
  border: string
  text: string
  glow: string
  label: string
  hand: 'left' | 'right' | 'both'
}

export const FINGER_STYLES: FingerStyle[] = [
  { bg: 'bg-red-500',    border: 'border-red-500',    text: 'text-red-400',    glow: 'shadow-red-500/60',    label: 'L. Pinky',  hand: 'left' },
  { bg: 'bg-orange-500', border: 'border-orange-500', text: 'text-orange-400', glow: 'shadow-orange-500/60', label: 'L. Ring',   hand: 'left' },
  { bg: 'bg-amber-400',  border: 'border-amber-400',  text: 'text-amber-400',  glow: 'shadow-amber-400/60',  label: 'L. Middle', hand: 'left' },
  { bg: 'bg-green-500',  border: 'border-green-500',  text: 'text-green-400',  glow: 'shadow-green-500/60',  label: 'L. Index',  hand: 'left' },
  { bg: 'bg-teal-400',   border: 'border-teal-400',   text: 'text-teal-400',   glow: 'shadow-teal-400/60',   label: 'R. Index',  hand: 'right' },
  { bg: 'bg-blue-500',   border: 'border-blue-500',   text: 'text-blue-400',   glow: 'shadow-blue-500/60',   label: 'R. Middle', hand: 'right' },
  { bg: 'bg-violet-500', border: 'border-violet-500', text: 'text-violet-400', glow: 'shadow-violet-500/60', label: 'R. Ring',   hand: 'right' },
  { bg: 'bg-pink-500',   border: 'border-pink-500',   text: 'text-pink-400',   glow: 'shadow-pink-500/60',   label: 'R. Pinky',  hand: 'right' },
  { bg: 'bg-slate-500',  border: 'border-slate-500',  text: 'text-slate-400',  glow: 'shadow-slate-500/60',  label: 'Thumb',     hand: 'both' },
]

// Lesson order for each language
export const LESSON_ORDER: KeyLesson[] = ['home_row', 'top_row', 'bottom_row', 'numbers', 'mixed']

// ── Composable ─────────────────────────────────────────────────────────────

export interface KeyAttempt {
  target: string
  pressed: string   // character that was produced
  code: string      // physical key code
  correct: boolean
  ms: number
}

export function useKeyTraining(
  lesson: Ref<KeyLesson>,
  lang: Ref<TrainingLang>,
  roundSize = 30,
) {
  // Current lesson info (language-aware)
  const lessonInfo = computed(() => LESSON_INFO_BY_LANG[lang.value][lesson.value])
  const lessonKeys = computed(() => lessonInfo.value.keys)

  // Reverse map: char → physical code (for the keyboard to highlight)
  const charToCode = computed(() => {
    const map = LAYOUT_MAP[lang.value]
    const reverse: Record<string, string> = {}
    for (const [code, char] of Object.entries(map)) {
      reverse[char] = code
    }
    return reverse
  })

  // ── State ──────────────────────────────────────────────────────
  const gameState       = ref<'idle' | 'playing' | 'finished'>('idle')
  const sequence        = ref<string[]>([])
  const currentIdx      = ref(0)
  const attempts        = ref<KeyAttempt[]>([])
  const lastPressedCode = ref('')
  const lastPressedChar = ref('')
  const lastStatus      = ref<'correct' | 'incorrect' | 'none'>('none')
  const streak          = ref(0)
  const maxStreak       = ref(0)
  const elapsedMs       = ref(0)

  let ticker: ReturnType<typeof setInterval> | null = null
  let statusTimer: ReturnType<typeof setTimeout> | null = null

  // ── Computed ────────────────────────────────────────────────────
  const currentKey = computed(() => sequence.value[currentIdx.value] ?? '')

  const targetCode = computed(() => charToCode.value[currentKey.value] ?? '')

  const progress = computed(() =>
    sequence.value.length > 0
      ? Math.round((currentIdx.value / sequence.value.length) * 100)
      : 0
  )

  const accuracy = computed(() => {
    if (!attempts.value.length) return 100
    return Math.round(attempts.value.filter(a => a.correct).length / attempts.value.length * 100)
  })

  const correctCount = computed(() => attempts.value.filter(a => a.correct).length)

  const elapsedSeconds = computed(() => Math.floor(elapsedMs.value / 1000))

  const keyStats = computed(() => {
    const map: Record<string, { correct: number; total: number }> = {}
    for (const a of attempts.value) {
      if (!map[a.target]) map[a.target] = { correct: 0, total: 0 }
      map[a.target].total++
      if (a.correct) map[a.target].correct++
    }
    return map
  })

  const currentFinger = computed(() => {
    const fingerIdx = KEY_FINGER_BY_CODE[targetCode.value]
    return fingerIdx !== undefined ? FINGER_STYLES[fingerIdx] : null
  })

  // ── Methods ─────────────────────────────────────────────────────
  function generateSequence(): string[] {
    const keys = lessonKeys.value
    const seq: string[] = []
    let last = ''
    while (seq.length < roundSize) {
      const pool = keys.length > 1 ? keys.filter(k => k !== last) : keys
      const key  = pool[Math.floor(Math.random() * pool.length)]
      seq.push(key)
      last = key
    }
    return seq
  }

  function init() {
    if (ticker) clearInterval(ticker)
    if (statusTimer) clearTimeout(statusTimer)
    sequence.value       = generateSequence()
    currentIdx.value     = 0
    attempts.value       = []
    lastPressedCode.value = ''
    lastPressedChar.value = ''
    lastStatus.value     = 'none'
    streak.value         = 0
    maxStreak.value      = 0
    elapsedMs.value      = 0
    gameState.value      = 'idle'
  }

  function start() {
    gameState.value = 'playing'
    const t0 = Date.now()
    ticker = setInterval(() => { elapsedMs.value = Date.now() - t0 }, 100)
  }

  /**
   * Handle a physical keypress by its code (e.g. "KeyA").
   * Works regardless of OS keyboard language — the mapping is done here.
   */
  function handleKeyCode(code: string) {
    if (gameState.value !== 'playing') return

    const map  = LAYOUT_MAP[lang.value]
    const char = map[code]
    if (char === undefined) return   // unmapped key, ignore

    const target  = currentKey.value
    const correct = char === target

    if (statusTimer) clearTimeout(statusTimer)
    lastPressedCode.value = code
    lastPressedChar.value = char
    lastStatus.value      = correct ? 'correct' : 'incorrect'

    attempts.value.push({ target, pressed: char, code, correct, ms: elapsedMs.value })

    if (correct) {
      streak.value++
      maxStreak.value = Math.max(maxStreak.value, streak.value)
      currentIdx.value++
      if (currentIdx.value >= sequence.value.length) {
        if (ticker) clearInterval(ticker)
        gameState.value = 'finished'
        return
      }
    } else {
      streak.value = 0
    }

    statusTimer = setTimeout(() => {
      lastStatus.value      = 'none'
      lastPressedCode.value = ''
      lastPressedChar.value = ''
    }, 380)
  }

  onUnmounted(() => {
    if (ticker) clearInterval(ticker)
    if (statusTimer) clearTimeout(statusTimer)
  })

  return {
    gameState, sequence, currentIdx, currentKey, targetCode,
    progress, accuracy, correctCount, streak, maxStreak,
    elapsedSeconds, lastPressedCode, lastPressedChar, lastStatus,
    attempts, keyStats, lessonKeys, lessonInfo, currentFinger,
    charToCode,
    init, start, handleKeyCode,
  }
}
