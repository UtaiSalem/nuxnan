<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { usePoints } from '~/composables/usePoints'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'main',
})

type CardType = 'word' | 'meaning'

type VocabPair = {
  id: number
  word: string
  meaning: string
}

type VocabCard = {
  id: number
  pairId: number
  type: CardType
  word: string
  meaning: string
  isFlipped: boolean
  isMatched: boolean
}

const STORAGE_KEY = 'english_vocab_match_unlocked_level'

const LEVEL_CONFIGS = [
  // Requested: 2*3, 3*4, 4*5
  { level: 1, pairsCount: 2 * 3, gridCols: 3, baseEarn: 20 },
  { level: 2, pairsCount: 3 * 4, gridCols: 4, baseEarn: 30 },
  { level: 3, pairsCount: 4 * 5, gridCols: 5, baseEarn: 40 }
] as const

type Level = (typeof LEVEL_CONFIGS)[number]['level']

const levelConfigsByLevel = computed(() => {
  return new Map<Level, (typeof LEVEL_CONFIGS)[number]>(
    LEVEL_CONFIGS.map((c) => [c.level, c])
  )
})

const getLevelConfig = (lvl: Level) => {
  const cfg = levelConfigsByLevel.value.get(lvl)
  if (!cfg) throw new Error(`Unknown level: ${lvl}`)
  return cfg
}

const unlockedLevel = ref<Level>(1)
const currentLevel = ref<Level>(1)
const pendingLevelUp = ref<Level | null>(null)

const vocabulary = computed<VocabPair[]>(() => {
  // Keep Thai meanings short for UI fit.
  return [
    { id: 1, word: 'apple', meaning: 'แอปเปิล' },
    { id: 2, word: 'music', meaning: 'ดนตรี' },
    { id: 3, word: 'doctor', meaning: 'หมอ' },
    { id: 4, word: 'friend', meaning: 'เพื่อน' },
    { id: 5, word: 'book', meaning: 'หนังสือ' },
    { id: 6, word: 'school', meaning: 'โรงเรียน' },
    { id: 7, word: 'family', meaning: 'ครอบครัว' },
    { id: 8, word: 'language', meaning: 'ภาษา' },
    { id: 9, word: 'travel', meaning: 'การเดินทาง' },
    { id: 10, word: 'weather', meaning: 'อากาศ' },
    { id: 11, word: 'teacher', meaning: 'ครู' },
    { id: 12, word: 'student', meaning: 'นักเรียน' },
    { id: 13, word: 'city', meaning: 'เมือง' },
    { id: 14, word: 'hospital', meaning: 'โรงพยาบาล' },
    { id: 15, word: 'computer', meaning: 'คอมพิวเตอร์' },
    { id: 16, word: 'phone', meaning: 'โทรศัพท์' },
    { id: 17, word: 'food', meaning: 'อาหาร' },
    { id: 18, word: 'animal', meaning: 'สัตว์' },
    { id: 19, word: 'movie', meaning: 'ภาพยนตร์' },
    { id: 20, word: 'game', meaning: 'เกม' }
  ]
})

const activeLevelConfig = computed(() => getLevelConfig(currentLevel.value))

const selectedPairs = computed<VocabPair[]>(() => {
  return vocabulary.value.slice(0, activeLevelConfig.value.pairsCount)
})

const currentPairsTotal = computed(() => selectedPairs.value.length)

const cards = ref<VocabCard[]>([])
const flippedCards = ref<VocabCard[]>([])
const moves = ref(0)
const matchedPairs = ref(0)
const isGameActive = ref(false)

const authStore = useAuthStore()
const pointsApi = usePoints()
const didAwardForRound = ref(false)
const lastEarnedPoints = ref<number>(0)

const computeEarnAmount = (): number => {
  const base = activeLevelConfig.value.baseEarn
  // `moves` = จำนวนครั้งที่กดฟลิปการ์ด
  // ต่อ 1 คู่ (word+meaning) ขั้นต่ำต้องฟลิป 2 ใบ => expectedMoves = pairs * 2
  const expectedMoves = currentPairsTotal.value * 2
  const extraMoves = Math.max(0, moves.value - expectedMoves)

  // Penalty every 2 extra moves
  const penalty = Math.floor(extraMoves / 2) * 5
  return Math.max(10, base - penalty)
}

    const awardPointsForRound = async () => {
      // Only award once per round
      if (didAwardForRound.value) return
      // Must be authenticated (token required)
      if (!authStore.token) return
      // Game ends only on success, but keep guard anyway
      if (matchedPairs.value !== currentPairsTotal.value) return

      didAwardForRound.value = true

      try {
        const amount = computeEarnAmount()

        const result = await pointsApi.earnXp({
          source_type: 'english_vocab_match',
          amount,
          description: `English Vocab Match XP (Level ${currentLevel.value}) - ${moves.value} moves`,
          metadata: {
            level: currentLevel.value,
            moves: moves.value,
            pairs: currentPairsTotal.value
          }
        })

        lastEarnedPoints.value = result?.xp_earned ?? amount
      } catch (err) {
        didAwardForRound.value = false
        lastEarnedPoints.value = 0
        console.error('Earn XP for round failed:', err)
      }
    }

const canFlip = (card: VocabCard) => {
  if (!isGameActive.value) return false
  if (card.isFlipped) return false
  if (card.isMatched) return false
  if (flippedCards.value.length >= 2) return false
  return true
}

const shuffle = <T,>(items: T[]): T[] => {
  const copy = [...items]
  copy.sort(() => Math.random() - 0.5)
  return copy
}

const initializeGame = () => {
  const pairs = selectedPairs.value

  const nextCards: VocabCard[] = []
  let cardId = 1

  for (const pair of pairs) {
    nextCards.push({
      id: cardId++,
      pairId: pair.id,
      type: 'word',
      word: pair.word,
      meaning: pair.meaning,
      isFlipped: false,
      isMatched: false
    })
    nextCards.push({
      id: cardId++,
      pairId: pair.id,
      type: 'meaning',
      word: pair.word,
      meaning: pair.meaning,
      isFlipped: false,
      isMatched: false
    })
  }

  cards.value = shuffle(nextCards)
  flippedCards.value = []
  moves.value = 0
  matchedPairs.value = 0
  didAwardForRound.value = false
  lastEarnedPoints.value = 0
  pendingLevelUp.value = null
  isGameActive.value = true
}

const flipCard = (card: VocabCard) => {
  if (!canFlip(card)) return
  card.isFlipped = true
  flippedCards.value.push(card)
  moves.value++

  if (flippedCards.value.length === 2) {
    checkForMatch()
  }
}

const checkForMatch = () => {
  const [c1, c2] = flippedCards.value
  const isPairMatch = c1.pairId === c2.pairId && c1.type !== c2.type

  if (isPairMatch) {
    setTimeout(() => {
      c1.isMatched = true
      c2.isMatched = true
      flippedCards.value = []
      matchedPairs.value++

      if (matchedPairs.value === currentPairsTotal.value) {
        void endGame()
      }
    }, 450)
  } else {
    setTimeout(() => {
      c1.isFlipped = false
      c2.isFlipped = false
      flippedCards.value = []
    }, 800)
  }
}

const maxLevel = LEVEL_CONFIGS[LEVEL_CONFIGS.length - 1].level

const unlockNextLevelIfEligible = () => {
  // Only unlock next level when player completed the currently highest unlocked level
  if (currentLevel.value !== unlockedLevel.value) return
  if (unlockedLevel.value >= maxLevel) return

  const next = (unlockedLevel.value + 1) as Level
  unlockedLevel.value = next
  pendingLevelUp.value = next
  currentLevel.value = next

  if (typeof window !== 'undefined') {
    window.localStorage.setItem(STORAGE_KEY, String(next))
  }
}

const endGame = async () => {
  isGameActive.value = false
  await awardPointsForRound()
  unlockNextLevelIfEligible()
}

const isLevelLocked = (lvl: Level) => lvl > unlockedLevel.value

const changeLevel = (next: Level) => {
  if (isLevelLocked(next)) return
  currentLevel.value = next
  initializeGame()
}

const loadUnlockedLevel = () => {
  if (typeof window === 'undefined') return
  const raw = window.localStorage.getItem(STORAGE_KEY)
  const num = Number(raw)
  if (!Number.isFinite(num)) return

  const allowed = LEVEL_CONFIGS.map((c) => c.level)
  if (allowed.includes(num as Level)) {
    unlockedLevel.value = num as Level
    currentLevel.value = unlockedLevel.value
  }
}

onMounted(() => {
  loadUnlockedLevel()
  initializeGame()
})
</script>

<template>
  <div class="min-h-[calc(100vh-100px)] w-full flex flex-col px-0 py-4 sm:px-4 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-4xl mx-auto w-full">
      <!-- Header -->
      <div class="text-center mb-6">
        <h1 class="text-4xl font-black text-gray-900 dark:text-white mb-2">
          English Vocab Match
        </h1>
        <p class="text-gray-600 dark:text-gray-300 text-lg">
          จับคู่คำศัพท์ภาษาอังกฤษกับความหมายไทย
        </p>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-2 font-black">
          Level {{ currentLevel }} (Unlocked: {{ unlockedLevel }})
        </p>
      </div>

      <!-- Stats -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 mb-5 border-2 border-black">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
          <div class="text-center">
            <p class="text-sm font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider">
              Moves
            </p>
            <p class="text-4xl font-black text-gray-900 dark:text-white">{{ moves }}</p>
          </div>

          <div class="text-center">
            <p class="text-sm font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider">
              Pairs
            </p>
            <p class="text-4xl font-black text-gray-900 dark:text-white">
              {{ matchedPairs }}/{{ currentPairsTotal }}
            </p>
          </div>

          <div class="text-center">
            <p class="text-sm font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-2">
              Level
            </p>
            <div class="flex justify-center gap-2 flex-wrap">
              <button class="min-h-[44px] sm:min-h-0"
                v-for="cfg in LEVEL_CONFIGS"
                :key="cfg.level"
                :disabled="isLevelLocked(cfg.level)"
                @click="changeLevel(cfg.level)"
                :class="[
                  'px-3 py-2 rounded-lg border-2 border-black font-black text-sm transition',
                  currentLevel === cfg.level
                    ? 'bg-green-300 text-black'
                    : 'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-600',
                  isLevelLocked(cfg.level) ? 'opacity-60 cursor-not-allowed hover:bg-white dark:hover:bg-gray-700' : ''
                ]"
              >
                Level {{ cfg.level }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Board -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 border-2 border-black mb-5">
        <div
          class="grid gap-3 mx-auto"
          :style="`grid-template-columns: repeat(${activeLevelConfig.gridCols}, minmax(0, 1fr)); max-width: ${activeLevelConfig.gridCols * 110}px;`"
        >
          <button
            v-for="card in cards"
            :key="card.id"
            @click="flipCard(card)"
            class="aspect-[1/1] w-full flex items-center justify-center rounded-xl border-2 border-black shadow-[4px_4px_0px_rgba(0,0,0,1)] transition active:translate-x-[2px] active:translate-y-[2px]"
            :class="[
              card.isMatched
                ? 'bg-green-200 cursor-default opacity-80'
                : card.isFlipped
                  ? 'bg-yellow-200'
                  : 'bg-white dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600'
            ]"
            type="button"
            :disabled="!canFlip(card)"
          >
            <div class="px-2 text-center">
              <div v-if="card.isFlipped || card.isMatched" class="font-black text-gray-900 dark:text-white">
                <div v-if="card.type === 'word'" class="text-lg leading-tight">
                  {{ card.word }}
                </div>
                <div v-else class="text-lg leading-tight">
                  {{ card.meaning }}
                </div>
              </div>
              <div v-else class="font-black text-gray-900 dark:text-white">
                ?
              </div>
            </div>
          </button>
        </div>
      </div>

      <!-- Controls -->
      <div class="text-center">
        <button
          @click="initializeGame"
          class="bg-black text-white font-black py-3 px-8 rounded-xl shadow-lg border-2 border-black hover:translate-x-0.5 hover:translate-y-0.5 active:translate-x-1 active:translate-y-1 transition active:shadow-md"
          type="button"
        >
          New Game
        </button>
      </div>
    </div>

    <!-- Victory -->
    <div
      v-if="!isGameActive && matchedPairs === currentPairsTotal"
      class="fixed inset-0 bg-black/60 dark:bg-black/80 flex items-center justify-center z-50 p-4"
    >
      <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-sm w-full text-center border-2 border-black">
        <div class="text-6xl mb-2">🎉</div>
        <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-1">
          Congratulations!
        </h2>
        <p class="text-lg text-gray-700 dark:text-gray-200 mb-4">
          You matched all words.
        </p>

        <div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-3 mb-4 border-2 border-black/0">
          <p class="text-sm font-black text-gray-800 dark:text-gray-100">
            Completed in
            <span class="font-black text-green-700 dark:text-green-300">{{ moves }}</span>
            moves
          </p>

          <p
            v-if="lastEarnedPoints > 0"
            class="text-sm font-black text-gray-800 dark:text-gray-100 mt-2"
          >
            Earned XP
            <span class="font-black text-green-700 dark:text-green-300">{{ lastEarnedPoints }}</span>
            xp
          </p>

          <p
            v-if="pendingLevelUp"
            class="text-sm font-black text-gray-800 dark:text-gray-100 mt-2"
          >
            Level Up!
            Now Level
            <span class="font-black text-green-700 dark:text-green-300">{{ pendingLevelUp }}</span>
          </p>
        </div>

        <button
          @click="initializeGame"
          class="w-full bg-black text-white font-black py-3 px-6 rounded-xl border-2 border-black hover:translate-x-0.5 hover:translate-y-0.5 transition active:translate-x-1 active:translate-y-1"
          type="button"
        >
          Play Again
        </button>
      </div>
    </div>
  </div>
</template>
