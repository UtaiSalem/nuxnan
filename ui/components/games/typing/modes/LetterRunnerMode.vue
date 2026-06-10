<script setup lang="ts">
import type { Difficulty, Lang } from '~/stores/typing'

interface Props {
  lang: Lang
  difficulty: Difficulty
}

interface LetterObj {
  id: number
  char: string
  x: number
  y: number
  color: string
  speed: number
  state: 'active' | 'destroyed' | 'missed'
}

interface CoinObj {
  id: number
  x: number
  y: number
  collected: boolean
}

interface ParticleObj {
  id: number
  x: number
  y: number
  color: string
}

const props = defineProps<Props>()
const emit = defineEmits<{ finished: [result: any] }>()

const difficultySettings: Record<Difficulty, { speed: number; spawnInterval: number; maxLetters: number }> = {
  beginner: { speed: 1.5, spawnInterval: 2000, maxLetters: 2 },
  easy:     { speed: 2.0, spawnInterval: 1600, maxLetters: 3 },
  normal:   { speed: 2.5, spawnInterval: 1200, maxLetters: 4 },
  hard:     { speed: 3.5, spawnInterval: 900,  maxLetters: 5 },
  expert:   { speed: 5.0, spawnInterval: 600,  maxLetters: 6 },
}

const letterPools: Record<Lang, string[]> = {
  en: 'abcdefghijklmnopqrstuvwxyz'.split(''),
  th: ['ก', 'ข', 'ค', 'ง', 'จ', 'ฉ', 'ช', 'ซ', 'ด', 'ต', 'ถ', 'ท', 'น', 'บ', 'ป', 'ผ', 'พ', 'ม', 'ย', 'ร', 'ล', 'ว', 'ส', 'ห', 'อ'],
  ar: ['ا', 'ب', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ر', 'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ع', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'ه', 'و', 'ي'],
}

const yLevels = [130, 200, 280]
const bubbleColors = ['cyan', 'orange', 'rose']
const coinLevels = [150, 220, 290]
const tickMs = 50
const pxToPercent = 0.125

const gameState = ref<'idle' | 'countdown' | 'playing' | 'finished'>('idle')
const score = ref(0)
const lives = ref(3)
const combo = ref(0)
const maxCombo = ref(0)
const coins = ref(0)
const timeLeft = ref(60)
const correctCount = ref(0)
const totalLetters = ref(0)
const missedLetters = ref(0)
const countdown = ref(3)
const isHit = ref(false)
const floatingScores = ref<{ id: number; x: number; y: number; text: string; color: string }[]>([])

const letters = ref<LetterObj[]>([])
const coinObjects = ref<CoinObj[]>([])
const particles = ref<ParticleObj[]>([])

let objectId = 0
let loopTimer: ReturnType<typeof setInterval> | null = null
let secondTimer: ReturnType<typeof setInterval> | null = null
let countdownTimer: ReturnType<typeof setInterval> | null = null
let lastLetterSpawn = 0
let lastCoinSpawn = 0

const settings = computed(() => difficultySettings[props.difficulty] ?? difficultySettings.easy)
const accuracy = computed(() => {
  if (totalLetters.value === 0) return 100
  return Math.round((correctCount.value / totalLetters.value) * 100)
})

const livesList = computed(() => Array.from({ length: 3 }, (_, index) => index < lives.value))
const activeLetters = computed(() => letters.value.filter(letter => letter.state === 'active'))

function randomFrom<T>(items: T[]): T {
  return items[Math.floor(Math.random() * items.length)]
}

function startCountdown() {
  resetGame()
  gameState.value = 'countdown'
  countdown.value = 3

  countdownTimer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      if (countdownTimer) clearInterval(countdownTimer)
      countdownTimer = null
      startPlaying()
    }
  }, 1000)
}

function startPlaying() {
  gameState.value = 'playing'
  const now = Date.now()
  lastLetterSpawn = now
  lastCoinSpawn = now
  spawnLetter()
  startTimers()
}

function startTimers() {
  stopTimers()

  loopTimer = setInterval(tick, tickMs)
  secondTimer = setInterval(() => {
    if (gameState.value !== 'playing') return
    timeLeft.value--
    if (timeLeft.value <= 0) {
      finishGame()
    }
  }, 1000)
}

function stopTimers() {
  if (loopTimer) clearInterval(loopTimer)
  if (secondTimer) clearInterval(secondTimer)
  if (countdownTimer) clearInterval(countdownTimer)
  loopTimer = null
  secondTimer = null
  countdownTimer = null
}

function tick() {
  if (gameState.value !== 'playing') return

  const now = Date.now()
  const moveBy = settings.value.speed * pxToPercent

  letters.value.forEach((letter) => {
    if (letter.state === 'active') {
      letter.x -= moveBy
    }
  })

  coinObjects.value.forEach((coin) => {
    if (!coin.collected) {
      coin.x -= moveBy * 1.1
    }
  })

  if (
    now - lastLetterSpawn >= settings.value.spawnInterval &&
    activeLetters.value.length < settings.value.maxLetters
  ) {
    spawnLetter()
    lastLetterSpawn = now
  }

  if (now - lastCoinSpawn >= 800) {
    spawnCoin()
    lastCoinSpawn = now
  }

  collectCoins()
  checkMisses()

  letters.value = letters.value.filter(letter => letter.x > -10 && letter.state !== 'destroyed')
  coinObjects.value = coinObjects.value.filter(coin => coin.x > -10 && !coin.collected)
}

function spawnLetter() {
  const char = randomFrom(letterPools[props.lang] ?? letterPools.en)
  letters.value.push({
    id: ++objectId,
    char,
    x: 105,
    y: randomFrom(yLevels),
    color: randomFrom(bubbleColors),
    speed: settings.value.speed,
    state: 'active',
  })
}

function spawnCoin() {
  coinObjects.value.push({
    id: ++objectId,
    x: 105,
    y: randomFrom(coinLevels),
    collected: false,
  })
}

function collectCoins() {
  coinObjects.value.forEach((coin) => {
    if (coin.collected || coin.x > 15) return
    coin.collected = true
    coins.value++
    score.value += 5
    showFloatingScore(15, coin.y, '+5', 'text-yellow-300')
  })
}

function checkMisses() {
  letters.value.forEach((letter) => {
    if (letter.state !== 'active' || letter.x >= 8) return
    letter.state = 'missed'
    missedLetters.value++
    loseLife()
  })
}

function loseLife() {
  combo.value = 0
  lives.value--
  isHit.value = true
  setTimeout(() => {
    isHit.value = false
  }, 300)

  if (lives.value <= 0) {
    finishGame()
  }
}

function handleKeyPress(e: KeyboardEvent) {
  if (gameState.value !== 'playing') return
  if (e.key.length !== 1 || e.ctrlKey || e.metaKey || e.altKey) return

  e.preventDefault()
  const key = e.key.toLowerCase()
  totalLetters.value++

  const target = [...letters.value]
    .filter(letter => letter.state === 'active')
    .sort((a, b) => a.x - b.x)[0]

  if (target && target.char.toLowerCase() === key) {
    target.state = 'destroyed'
    correctCount.value++
    combo.value++
    maxCombo.value = Math.max(maxCombo.value, combo.value)

    const comboMult = Math.min(combo.value, 5)
    const points = 10 * comboMult
    score.value += points
    showDestroyEffect(target)
    showFloatingScore(target.x, target.y, `+${points}`, 'text-cyan-200')
  } else {
    combo.value = 0
    score.value = Math.max(0, score.value - 3)
    isHit.value = true
    setTimeout(() => {
      isHit.value = false
    }, 160)
  }
}

function showDestroyEffect(letter: LetterObj) {
  const effectId = ++objectId
  particles.value.push({
    id: effectId,
    x: letter.x,
    y: letter.y,
    color: letter.color,
  })

  setTimeout(() => {
    particles.value = particles.value.filter(particle => particle.id !== effectId)
  }, 480)
}

function showFloatingScore(x: number, y: number, text: string, color: string) {
  const id = ++objectId
  floatingScores.value.push({ id, x, y, text, color })

  setTimeout(() => {
    floatingScores.value = floatingScores.value.filter(item => item.id !== id)
  }, 700)
}

function finishGame() {
  if (gameState.value === 'finished') return

  const timeBonus = timeLeft.value > 0 ? timeLeft.value * 2 : 0
  const accuracyBonus = accuracy.value >= 90 ? 50 : 0
  score.value += timeBonus + accuracyBonus
  gameState.value = 'finished'
  stopTimers()
  emit('finished', buildResult())
}

function resetGame() {
  stopTimers()
  score.value = 0
  lives.value = 3
  combo.value = 0
  maxCombo.value = 0
  coins.value = 0
  timeLeft.value = 60
  correctCount.value = 0
  totalLetters.value = 0
  missedLetters.value = 0
  letters.value = []
  coinObjects.value = []
  particles.value = []
  floatingScores.value = []
  isHit.value = false
}

function buildResult() {
  return {
    session_token: uuid(),
    game_mode: 'letter_runner',
    language: props.lang,
    difficulty: props.difficulty,
    correct_chars: correctCount.value,
    total_chars: totalLetters.value,
    correct_words: Math.floor(correctCount.value / 3),
    total_words: Math.floor(totalLetters.value / 3),
    mistakes: totalLetters.value - correctCount.value,
    max_combo: maxCombo.value,
    time_elapsed: 60 - timeLeft.value,
    time_limit: 60,
    coins_collected: coins.value,
    score_override: score.value,
  }
}

function bubbleClass(color: string) {
  const map: Record<string, string> = {
    cyan:   'border-cyan-300/80 bg-cyan-400/20 text-cyan-50 shadow-[0_0_28px_rgba(34,211,238,0.65)]',
    orange: 'border-orange-300/80 bg-orange-400/20 text-orange-50 shadow-[0_0_28px_rgba(251,146,60,0.65)]',
    rose:   'border-rose-300/80 bg-rose-400/20 text-rose-50 shadow-[0_0_28px_rgba(251,113,133,0.65)]',
  }
  return map[color] ?? map.cyan
}

function particleClass(color: string) {
  const map: Record<string, string> = {
    cyan:   'bg-cyan-300 shadow-[0_0_16px_rgba(34,211,238,0.9)]',
    orange: 'bg-orange-300 shadow-[0_0_16px_rgba(251,146,60,0.9)]',
    rose:   'bg-rose-300 shadow-[0_0_16px_rgba(251,113,133,0.9)]',
  }
  return map[color] ?? map.cyan
}

onMounted(() => {
  window.addEventListener('keydown', handleKeyPress)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyPress)
  stopTimers()
})
</script>

<template>
  <div class="mx-auto max-w-5xl space-y-5">
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <div class="rounded-2xl border border-cyan-300/20 bg-slate-950 px-4 py-3 shadow-[0_0_24px_rgba(6,182,212,0.12)]">
        <p class="text-[10px] font-black uppercase tracking-widest text-cyan-300">Score</p>
        <p class="text-2xl font-black text-white">{{ score }}</p>
      </div>
      <div class="rounded-2xl border border-rose-300/20 bg-slate-950 px-4 py-3 shadow-[0_0_24px_rgba(244,63,94,0.12)]">
        <p class="text-[10px] font-black uppercase tracking-widest text-rose-300">Lives</p>
        <div class="mt-1 flex gap-1.5 text-xl">
          <span
            v-for="(alive, index) in livesList"
            :key="index"
            :class="alive ? 'text-rose-400 drop-shadow-[0_0_8px_rgba(251,113,133,0.8)]' : 'text-slate-700'"
          >♥</span>
        </div>
      </div>
      <div class="rounded-2xl border border-yellow-300/20 bg-slate-950 px-4 py-3 shadow-[0_0_24px_rgba(250,204,21,0.12)]">
        <p class="text-[10px] font-black uppercase tracking-widest text-yellow-300">Coins</p>
        <p class="text-2xl font-black text-yellow-200">{{ coins }}</p>
      </div>
      <div class="rounded-2xl border border-indigo-300/20 bg-slate-950 px-4 py-3 shadow-[0_0_24px_rgba(129,140,248,0.12)]">
        <p class="text-[10px] font-black uppercase tracking-widest text-indigo-300">Timer</p>
        <p class="text-2xl font-black text-white">{{ timeLeft }}s</p>
      </div>
    </div>

    <div
      class="letter-runner-stage relative h-[560px] overflow-hidden rounded-3xl border border-cyan-300/20 bg-slate-950 shadow-2xl shadow-cyan-950/40"
      :class="{ 'stage-hit': isHit }"
    >
      <div class="stars-layer absolute inset-0"></div>
      <div class="mountain-layer mountain-layer-back absolute inset-x-0 bottom-20 h-44"></div>
      <div class="mountain-layer mountain-layer-front absolute inset-x-0 bottom-16 h-32"></div>
      <div class="ground-layer absolute inset-x-0 bottom-0 h-24"></div>
      <div class="absolute inset-x-0 bottom-[88px] h-px bg-cyan-300 shadow-[0_0_22px_rgba(34,211,238,0.95)]"></div>

      <div class="absolute left-5 top-5 z-20 flex flex-col gap-2 sm:left-6 sm:top-6">
        <div class="rounded-full border border-cyan-300/30 bg-slate-950/70 px-4 py-2 text-xs font-black uppercase tracking-widest text-cyan-100 backdrop-blur">
          Letter Runner
        </div>
        <div class="rounded-full border border-white/10 bg-slate-950/70 px-4 py-2 text-xs font-bold text-slate-300 backdrop-blur">
          Accuracy {{ accuracy }}%
        </div>
      </div>

      <div class="absolute right-5 top-5 z-20 flex flex-col items-end gap-2 sm:right-6 sm:top-6">
        <div class="rounded-full border border-white/10 bg-slate-950/70 px-4 py-2 text-xs font-black uppercase tracking-widest text-white backdrop-blur">
          {{ difficulty }}
        </div>
        <Transition name="combo-pop">
          <div
            v-if="combo >= 3"
            :key="combo"
            class="rounded-full border border-yellow-300/60 bg-yellow-300/15 px-4 py-2 text-lg font-black text-yellow-200 shadow-[0_0_24px_rgba(250,204,21,0.45)] backdrop-blur"
          >
            x{{ combo }} COMBO!
          </div>
        </Transition>
      </div>

      <!-- Runner character — Pixar 3D SVG -->
      <div
        class="runner absolute left-[8%] z-10"
        style="top: 310px; width: 100px; height: 160px;"
        :class="{ 'runner-hit': isHit, 'runner-idle': gameState !== 'playing' }"
      >
        <svg viewBox="0 0 100 160" width="100" height="160" xmlns="http://www.w3.org/2000/svg" overflow="visible" aria-hidden="true">
          <defs>
            <!-- Head: warm peach sphere -->
            <radialGradient id="rg-head" cx="35%" cy="28%" r="65%">
              <stop offset="0%"   stop-color="#FEF9C3"/>
              <stop offset="40%"  stop-color="#FDE68A"/>
              <stop offset="80%"  stop-color="#F59E0B"/>
              <stop offset="100%" stop-color="#92400E"/>
            </radialGradient>
            <!-- Suit torso -->
            <radialGradient id="rg-suit" cx="35%" cy="20%" r="70%">
              <stop offset="0%"   stop-color="#C7D2FE"/>
              <stop offset="50%"  stop-color="#4F46E5"/>
              <stop offset="100%" stop-color="#1E1B4B"/>
            </radialGradient>
            <!-- Leg / arm -->
            <radialGradient id="rg-limb" cx="30%" cy="20%" r="65%">
              <stop offset="0%"   stop-color="#818CF8"/>
              <stop offset="60%"  stop-color="#3730A3"/>
              <stop offset="100%" stop-color="#1E1B4B"/>
            </radialGradient>
            <!-- Shoe top -->
            <radialGradient id="rg-shoe" cx="40%" cy="25%" r="62%">
              <stop offset="0%"   stop-color="#475569"/>
              <stop offset="100%" stop-color="#0F172A"/>
            </radialGradient>
            <!-- Eye white -->
            <radialGradient id="rg-eyew" cx="35%" cy="30%" r="65%">
              <stop offset="0%"   stop-color="#FFFFFF"/>
              <stop offset="100%" stop-color="#CBD5E1"/>
            </radialGradient>
            <!-- Iris -->
            <radialGradient id="rg-iris" cx="36%" cy="30%" r="62%">
              <stop offset="0%"   stop-color="#7DD3FC"/>
              <stop offset="50%"  stop-color="#2563EB"/>
              <stop offset="100%" stop-color="#1E3A8A"/>
            </radialGradient>
            <!-- Hand skin -->
            <radialGradient id="rg-hand" cx="35%" cy="28%" r="65%">
              <stop offset="0%"   stop-color="#FEF3C7"/>
              <stop offset="60%"  stop-color="#FCD34D"/>
              <stop offset="100%" stop-color="#B45309"/>
            </radialGradient>
            <filter id="rg-blur" x="-50%" y="-50%" width="200%" height="200%">
              <feGaussianBlur stdDeviation="3"/>
            </filter>
            <filter id="rg-glow" x="-30%" y="-30%" width="160%" height="160%">
              <feGaussianBlur stdDeviation="2" result="blur"/>
              <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
            </filter>
          </defs>

          <!-- Ground shadow -->
          <ellipse cx="50" cy="158" rx="28" ry="5" fill="rgba(6,182,212,0.25)" filter="url(#rg-blur)"/>

          <!-- ── BACK LEG (drawn first = behind) ── -->
          <!-- pivot: hip at (58, 107) -->
          <g class="leg-back" style="transform-origin:58px 107px">
            <rect x="52" y="107" width="12" height="22" rx="6" fill="url(#rg-limb)"/>
            <rect x="53" y="126" width="11" height="18" rx="5" fill="url(#rg-limb)"/>
            <!-- Shoe -->
            <ellipse cx="61" cy="146" rx="13" ry="7" fill="url(#rg-shoe)"/>
            <rect x="49" y="143" width="24" height="6" rx="3" fill="#0891B2"/>
            <ellipse cx="55" cy="142" rx="6" ry="2.5" fill="rgba(34,211,238,0.55)"/>
          </g>

          <!-- ── BACK ARM (drawn before body = behind) ── -->
          <!-- pivot: shoulder at (68, 72) -->
          <g class="arm-back" style="transform-origin:68px 72px">
            <rect x="63" y="72" width="11" height="22" rx="5.5" fill="url(#rg-limb)"/>
            <rect x="65" y="91" width="10" height="17" rx="5" fill="url(#rg-limb)"/>
            <!-- Fist -->
            <ellipse cx="70" cy="111" rx="8" ry="7" fill="url(#rg-hand)"/>
            <ellipse cx="70" cy="109" rx="7" ry="3" fill="rgba(255,255,255,0.12)"/>
          </g>

          <!-- ── BODY (torso) ── -->
          <ellipse cx="50" cy="88" rx="22" ry="25" fill="url(#rg-suit)"/>
          <!-- Chest line -->
          <path d="M 36 80 Q 50 85 64 80" stroke="rgba(34,211,238,0.65)" stroke-width="1.8" fill="none" stroke-linecap="round"/>
          <!-- Chest badge -->
          <circle cx="50" cy="86" r="6" fill="#06B6D4" opacity="0.9"/>
          <circle cx="50" cy="86" r="3.5" fill="#E0F2FE"/>
          <circle cx="50" cy="86" r="1.5" fill="#0C4A6E"/>
          <!-- Belt -->
          <rect x="31" y="100" width="38" height="8" rx="4" fill="#1E1B4B"/>
          <rect x="44" y="98" width="12" height="12" rx="3.5" fill="#06B6D4"/>
          <rect x="46" y="100" width="8" height="8" rx="2" fill="#0E7490"/>
          <circle cx="50" cy="104" r="2" fill="#CFFAFE"/>
          <!-- Shoulder pads -->
          <ellipse cx="29" cy="76" rx="9" ry="7" fill="#3730A3"/>
          <ellipse cx="71" cy="76" rx="9" ry="7" fill="#3730A3"/>
          <ellipse cx="29" cy="74" rx="5" ry="3" fill="rgba(165,180,252,0.4)"/>
          <ellipse cx="71" cy="74" rx="5" ry="3" fill="rgba(165,180,252,0.4)"/>

          <!-- ── FRONT LEG ── -->
          <!-- pivot: hip at (42, 107) -->
          <g class="leg-front" style="transform-origin:42px 107px">
            <rect x="36" y="107" width="12" height="22" rx="6" fill="url(#rg-limb)"/>
            <rect x="37" y="126" width="11" height="18" rx="5" fill="url(#rg-limb)"/>
            <!-- Shoe -->
            <ellipse cx="45" cy="146" rx="13" ry="7" fill="url(#rg-shoe)"/>
            <rect x="33" y="143" width="24" height="6" rx="3" fill="#0891B2"/>
            <ellipse cx="39" cy="142" rx="6" ry="2.5" fill="rgba(34,211,238,0.55)"/>
          </g>

          <!-- ── FRONT ARM ── -->
          <!-- pivot: shoulder at (32, 72) -->
          <g class="arm-front" style="transform-origin:32px 72px">
            <rect x="26" y="72" width="11" height="22" rx="5.5" fill="url(#rg-limb)"/>
            <rect x="25" y="91" width="10" height="17" rx="5" fill="url(#rg-limb)"/>
            <!-- Fist -->
            <ellipse cx="30" cy="111" rx="8" ry="7" fill="url(#rg-hand)"/>
            <ellipse cx="30" cy="109" rx="7" ry="3" fill="rgba(255,255,255,0.12)"/>
          </g>

          <!-- ── NECK ── -->
          <rect x="43" y="61" width="14" height="11" rx="6" fill="url(#rg-hand)"/>

          <!-- ── HEAD SPHERE ── -->
          <circle cx="50" cy="40" r="34" fill="url(#rg-head)"/>
          <!-- 3D top-left highlight -->
          <ellipse cx="36" cy="23" rx="14" ry="9" fill="rgba(255,255,255,0.25)" transform="rotate(-25,36,23)"/>
          <!-- Subtle chin shadow -->
          <ellipse cx="50" cy="72" rx="16" ry="5" fill="rgba(120,53,15,0.12)"/>

          <!-- Ears -->
          <ellipse cx="16.5" cy="42" rx="7" ry="9" fill="url(#rg-hand)"/>
          <ellipse cx="17" cy="42" rx="4" ry="6" fill="#F59E0B"/>
          <ellipse cx="17" cy="42" rx="1.8" ry="3" fill="#B45309" opacity="0.5"/>
          <ellipse cx="83.5" cy="42" rx="7" ry="9" fill="url(#rg-hand)"/>
          <ellipse cx="83" cy="42" rx="4" ry="6" fill="#F59E0B"/>
          <ellipse cx="83" cy="42" rx="1.8" ry="3" fill="#B45309" opacity="0.5"/>

          <!-- ── HAIR ── -->
          <path d="M 17 35 Q 19 6 50 5 Q 81 6 83 35 Q 72 17 50 19 Q 28 17 17 35 Z" fill="#78350F"/>
          <path d="M 24 18 Q 37 9 50 8 Q 63 9 72 15" stroke="#92400E" stroke-width="3" fill="none" stroke-linecap="round" opacity="0.6"/>
          <!-- Tuft -->
          <path d="M 43 5 Q 50 -3 57 5" stroke="#92400E" stroke-width="4.5" fill="none" stroke-linecap="round"/>
          <path d="M 46 4 Q 50 -1 54 4" stroke="#FEF3C7" stroke-width="1.5" fill="none" stroke-linecap="round" opacity="0.4"/>

          <!-- ── LEFT EYE (Pixar) ── -->
          <ellipse cx="36" cy="40" rx="12" ry="13" fill="url(#rg-eyew)"/>
          <!-- Upper lid shadow -->
          <ellipse cx="36" cy="33" rx="12" ry="5" fill="rgba(78,53,15,0.14)"/>
          <!-- Iris -->
          <ellipse cx="36" cy="41" rx="8" ry="9" fill="url(#rg-iris)"/>
          <!-- Pupil -->
          <ellipse cx="36" cy="41.5" rx="5" ry="5.5" fill="#050311"/>
          <!-- Primary shine -->
          <circle cx="32.5" cy="36.5" r="3.2" fill="white"/>
          <!-- Secondary shine -->
          <circle cx="38.5" cy="44" r="1.5" fill="white" opacity="0.65"/>
          <!-- Eyelash curve -->
          <path d="M 24 33 Q 36 27 48 33" stroke="#4C2A0D" stroke-width="2.5" fill="none" stroke-linecap="round"/>
          <!-- Eyebrow -->
          <path d="M 24 26 Q 36 21 48 26" stroke="#78350F" stroke-width="3" fill="none" stroke-linecap="round"/>

          <!-- ── RIGHT EYE (Pixar) ── -->
          <ellipse cx="64" cy="40" rx="12" ry="13" fill="url(#rg-eyew)"/>
          <ellipse cx="64" cy="33" rx="12" ry="5" fill="rgba(78,53,15,0.14)"/>
          <ellipse cx="64" cy="41" rx="8" ry="9" fill="url(#rg-iris)"/>
          <ellipse cx="64" cy="41.5" rx="5" ry="5.5" fill="#050311"/>
          <circle cx="60.5" cy="36.5" r="3.2" fill="white"/>
          <circle cx="66.5" cy="44" r="1.5" fill="white" opacity="0.65"/>
          <path d="M 52 33 Q 64 27 76 33" stroke="#4C2A0D" stroke-width="2.5" fill="none" stroke-linecap="round"/>
          <path d="M 52 26 Q 64 21 76 26" stroke="#78350F" stroke-width="3" fill="none" stroke-linecap="round"/>

          <!-- ── NOSE ── -->
          <path d="M 46 51 Q 50 56 54 51" stroke="#C2703A" stroke-width="2" fill="none" stroke-linecap="round"/>
          <circle cx="46.5" cy="53" r="1.5" fill="rgba(120,53,15,0.4)"/>
          <circle cx="53.5" cy="53" r="1.5" fill="rgba(120,53,15,0.4)"/>

          <!-- ── MOUTH ── -->
          <path d="M 37 61 Q 50 72 63 61" stroke="#C2703A" stroke-width="2.8" fill="rgba(248,200,100,0.15)" stroke-linecap="round"/>
          <path d="M 39 62 Q 50 70 61 62" fill="rgba(255,255,255,0.5)"/>

          <!-- ── CHEEKS ── -->
          <ellipse cx="22" cy="53" rx="9" ry="6" fill="rgba(251,113,133,0.28)"/>
          <ellipse cx="78" cy="53" rx="9" ry="6" fill="rgba(251,113,133,0.28)"/>
        </svg>
      </div>

      <div
        v-for="letter in letters"
        :key="letter.id"
        class="letter-bubble absolute z-10 flex h-16 w-16 items-center justify-center rounded-full border-2 text-3xl font-black"
        :class="[bubbleClass(letter.color), letter.state === 'missed' ? 'opacity-30 grayscale' : '']"
        :style="{ left: `${letter.x}%`, top: `${letter.y}px` }"
      >
        {{ letter.char }}
      </div>

      <div
        v-for="coin in coinObjects"
        :key="coin.id"
        class="coin absolute z-10 flex h-9 w-9 items-center justify-center rounded-full border-2 border-yellow-200 bg-yellow-400 text-xs font-black text-yellow-950 shadow-[0_0_22px_rgba(250,204,21,0.85)]"
        :style="{ left: `${coin.x}%`, top: `${coin.y}px` }"
      >
        +
      </div>

      <div
        v-for="effect in particles"
        :key="effect.id"
        class="particle-burst absolute z-20 h-1 w-1"
        :style="{ left: `${effect.x}%`, top: `${effect.y}px` }"
      >
        <span
          v-for="index in 8"
          :key="index"
          class="absolute h-2 w-2 rounded-full"
          :class="particleClass(effect.color)"
          :style="{ '--angle': `${index * 45}deg` }"
        ></span>
      </div>

      <div
        v-for="item in floatingScores"
        :key="item.id"
        class="floating-score absolute z-30 text-xl font-black"
        :class="item.color"
        :style="{ left: `${item.x}%`, top: `${item.y}px` }"
      >
        {{ item.text }}
      </div>

      <div v-if="gameState === 'idle'" class="absolute inset-0 z-30 flex items-center justify-center bg-slate-950/70 px-5 backdrop-blur-sm">
        <div class="max-w-lg text-center">
          <p class="text-xs font-black uppercase tracking-[0.5em] text-cyan-300">Neon Sprint Protocol</p>
          <h2 class="mt-4 text-4xl font-black uppercase text-white sm:text-6xl">Letter Runner</h2>
          <p class="mt-4 text-sm font-medium text-slate-300 sm:text-base">
            Type the closest incoming letter. Coins collect automatically when the runner reaches them.
          </p>
          <button
            class="mt-8 rounded-2xl bg-cyan-400 px-8 py-4 text-lg font-black uppercase tracking-widest text-slate-950 shadow-[0_0_34px_rgba(34,211,238,0.55)] transition-all hover:scale-105 hover:bg-cyan-300 active:scale-95"
            @click="startCountdown"
          >
            Start Run
          </button>
        </div>
      </div>

      <div v-else-if="gameState === 'countdown'" class="absolute inset-0 z-30 flex items-center justify-center bg-slate-950/60 backdrop-blur-sm">
        <div class="text-9xl font-black text-cyan-200 drop-shadow-[0_0_35px_rgba(34,211,238,0.9)]">
          {{ countdown }}
        </div>
      </div>

      <div v-else-if="gameState === 'finished'" class="absolute inset-0 z-30 flex items-center justify-center bg-slate-950/75 px-5 backdrop-blur-sm">
        <div class="max-w-md text-center">
          <p class="text-xs font-black uppercase tracking-[0.45em] text-yellow-300">Run Complete</p>
          <h2 class="mt-4 text-5xl font-black text-white">{{ score }}</h2>
          <p class="mt-2 text-sm font-bold uppercase tracking-widest text-slate-400">Final Score</p>
          <div class="mt-6 grid grid-cols-3 gap-3 text-center">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
              <p class="text-xs text-slate-400">Letters</p>
              <p class="text-xl font-black text-cyan-200">{{ correctCount }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
              <p class="text-xs text-slate-400">Coins</p>
              <p class="text-xl font-black text-yellow-200">{{ coins }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
              <p class="text-xs text-slate-400">Combo</p>
              <p class="text-xl font-black text-rose-200">{{ maxCombo }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <p class="text-center text-xs font-bold uppercase tracking-widest text-slate-500">
      Type the nearest active letter. Wrong keys lower score and reset combo.
    </p>
  </div>
</template>

<style scoped>
.letter-runner-stage {
  isolation: isolate;
}

.stars-layer {
  background:
    radial-gradient(circle at 12% 18%, rgba(34, 211, 238, 0.85) 0 1px, transparent 2px),
    radial-gradient(circle at 24% 55%, rgba(255, 255, 255, 0.65) 0 1px, transparent 2px),
    radial-gradient(circle at 48% 24%, rgba(216, 180, 254, 0.8) 0 1px, transparent 2px),
    radial-gradient(circle at 72% 42%, rgba(34, 211, 238, 0.7) 0 1px, transparent 2px),
    radial-gradient(circle at 88% 16%, rgba(255, 255, 255, 0.75) 0 1px, transparent 2px),
    linear-gradient(180deg, #1e1b4b 0%, #0f172a 58%, #020617 100%);
  background-size: 360px 220px, 420px 260px, 380px 250px, 460px 280px, 400px 240px, 100% 100%;
  animation: stars-scroll 20s linear infinite;
}

.mountain-layer {
  clip-path: polygon(0 100%, 0 55%, 10% 30%, 21% 64%, 34% 25%, 47% 68%, 60% 38%, 72% 66%, 84% 28%, 100% 58%, 100% 100%);
}

.mountain-layer-back {
  background: linear-gradient(90deg, rgba(49, 46, 129, 0.6), rgba(8, 145, 178, 0.3), rgba(88, 28, 135, 0.55));
  animation: terrain-scroll 16s linear infinite;
}

.mountain-layer-front {
  background: linear-gradient(90deg, rgba(15, 23, 42, 0.8), rgba(67, 56, 202, 0.5), rgba(8, 47, 73, 0.8));
  animation: terrain-scroll 10s linear infinite;
}

.ground-layer {
  background:
    repeating-linear-gradient(90deg, rgba(34, 211, 238, 0.18) 0 28px, transparent 28px 56px),
    linear-gradient(180deg, rgba(8, 145, 178, 0.4), rgba(15, 23, 42, 0.95));
  box-shadow: 0 -20px 50px rgba(34, 211, 238, 0.35);
  animation: ground-scroll 1.2s linear infinite;
}

.runner {
  filter: drop-shadow(0 0 18px rgba(34, 211, 238, 0.55));
  animation: runner-bob 0.42s ease-in-out infinite;
}

.runner-idle {
  animation-duration: 1s;
}

.runner-hit {
  animation: runner-hit 0.26s linear;
  filter: drop-shadow(0 0 24px rgba(248, 113, 113, 0.9)) brightness(1.1);
}

/* ── Limb animations — :deep() required for scoped CSS → SVG <g> ── */
.runner :deep(.arm-front) {
  animation: arm-swing-fwd 0.38s ease-in-out infinite;
}
.runner :deep(.arm-back) {
  animation: arm-swing-bck 0.38s ease-in-out infinite;
}
.runner :deep(.leg-front) {
  animation: leg-swing-fwd 0.38s ease-in-out infinite;
}
.runner :deep(.leg-back) {
  animation: leg-swing-bck 0.38s ease-in-out infinite;
}

.runner-idle :deep(.arm-front),
.runner-idle :deep(.arm-back),
.runner-idle :deep(.leg-front),
.runner-idle :deep(.leg-back) {
  animation-duration: 1.1s;
}

.letter-bubble {
  transform: translate(-50%, -50%);
  transition: opacity 0.18s ease;
}

.coin {
  transform: translate(-50%, -50%);
  animation: coin-spin 0.8s linear infinite;
}

.particle-burst span {
  animation: particle-pop 0.48s ease-out forwards;
}

.floating-score {
  animation: float-score 0.7s ease-out forwards;
  text-shadow: 0 0 16px currentColor;
}

.stage-hit {
  animation: stage-hit 0.2s linear;
}

.combo-pop-enter-active,
.combo-pop-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.combo-pop-enter-from,
.combo-pop-leave-to {
  opacity: 0;
  transform: translateY(8px) scale(0.85);
}

@keyframes stars-scroll {
  from { background-position: 0 0, 0 0, 0 0, 0 0, 0 0, 0 0; }
  to { background-position: -360px 0, -420px 0, -380px 0, -460px 0, -400px 0, 0 0; }
}

@keyframes terrain-scroll {
  from { transform: translateX(0); }
  to { transform: translateX(-8%); }
}

@keyframes ground-scroll {
  from { background-position: 0 0, 0 0; }
  to { background-position: -56px 0, 0 0; }
}

@keyframes runner-bob {
  0%, 100% { transform: translateY(0) rotate(-1deg); }
  50% { transform: translateY(-7px) rotate(2deg); }
}

@keyframes runner-hit {
  0%, 100% { transform: translateX(0); filter: drop-shadow(0 0 18px rgba(34, 211, 238, 0.55)); }
  25% { transform: translateX(-7px); filter: drop-shadow(0 0 22px rgba(248, 113, 113, 0.95)); }
  50% { transform: translateX(7px); }
  75% { transform: translateX(-4px); }
}

@keyframes arm-swing-fwd {
  0%, 100% { transform: rotate(-32deg) translateY(2px); }
  50%       { transform: rotate(30deg)  translateY(-2px); }
}

@keyframes arm-swing-bck {
  0%, 100% { transform: rotate(30deg)  translateY(-2px); }
  50%       { transform: rotate(-32deg) translateY(2px); }
}

@keyframes leg-swing-fwd {
  0%, 100% { transform: rotate(-28deg); }
  50%       { transform: rotate(32deg); }
}

@keyframes leg-swing-bck {
  0%, 100% { transform: rotate(32deg); }
  50%       { transform: rotate(-28deg); }
}

@keyframes coin-spin {
  0% { transform: translate(-50%, -50%) rotateY(0deg) scale(1); }
  50% { transform: translate(-50%, -50%) rotateY(90deg) scale(1.15); }
  100% { transform: translate(-50%, -50%) rotateY(180deg) scale(1); }
}

@keyframes particle-pop {
  from {
    opacity: 1;
    transform: rotate(var(--angle)) translateX(0) scale(1);
  }
  to {
    opacity: 0;
    transform: rotate(var(--angle)) translateX(46px) scale(0.2);
  }
}

@keyframes float-score {
  from {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  to {
    opacity: 0;
    transform: translateY(-34px) scale(1.25);
  }
}

@keyframes stage-hit {
  0%, 100% { box-shadow: 0 25px 50px -12px rgba(8, 47, 73, 0.4); }
  50% { box-shadow: 0 0 55px rgba(248, 113, 113, 0.45); }
}
</style>
