<script setup lang="ts">
import { Icon } from '@iconify/vue'
import TypingModeHeader from '../ui/TypingModeHeader.vue'
import TypingComboDisplay from '../ui/TypingComboDisplay.vue'

import { usePhaserGame } from '~/composables/usePhaserGame'
import { useTypingApi } from '~/composables/useTypingApi'
import type { Lang, Difficulty } from '~/stores/typing'

interface Props {
  lang: Lang
  difficulty: Difficulty
}
const props = defineProps<Props>()
const { t } = useI18n()
const loading = ref(true)
const loadFailed = ref(false)
let disposed = false
const emit  = defineEmits(['finished'])

const containerId = 'monster-battle-canvas'
const { createGame, destroyGame } = usePhaserGame()
const { fetchWords } = useTypingApi()

// Shared reactive state ระหว่าง Vue และ Phaser Scene
const sharedState = reactive({
  currentWord:  '',
  playerInput:  '',
  playerHp:     100,
  score:        0,
  combo:        0,
  maxCombo:     0,
  wpm:          0,
  accuracy:     0,
  correctChars: 0,
  totalChars:   0,
  mistakes:     0,
  elapsedSecs:  0,
  wordBank:     [] as string[],
  gameOver:     false,
  onWordTyped: null as ((correct: boolean) => void) | null,
})

const inputRef   = ref<HTMLInputElement | null>(null)
useKeyboardAwareInput(inputRef)

async function initGame() {
  // โหลดคำศัพท์ก่อน
  const words = await fetchWords(props.lang, props.difficulty, 50)
  if (disposed) return
  sharedState.wordBank = words.map((w: any) => w.text)
  if (!sharedState.wordBank.length) throw new Error('Empty word bank')

  // import Phaser เฉพาะ client side
  const Phaser = (await import('phaser')).default
  if (disposed) return

  // ─── Monster Battle Scene ─────────────────────────────────
  class MonsterScene extends Phaser.Scene {
    monsters: Phaser.GameObjects.Group | null = null
    spawnTimer: Phaser.Time.TimerEvent | null = null
    gameTimer: Phaser.Time.TimerEvent | null = null
    statsTimer: Phaser.Time.TimerEvent | null = null

    constructor() { super({ key: 'MonsterScene' }) }

    preload() {
      // Fallback: ใช้ shapes แทน sprites ถ้าไม่มี assets
    }

    create() {
      const { width, height } = this.scale

      // Background gradient
      const bg = this.add.graphics()
      bg.fillGradientStyle(0x1d2333, 0x1d2333, 0x282f3f, 0x282f3f, 1)
      bg.fillRect(0, 0, width, height)


      // Ground line
      this.add.rectangle(width / 2, height - 40, width, 4, 0x334155)

      // Player character (simple sprite)
      this.add.text(60, height - 80, '🧙', { fontSize: '48px' })

      this.monsters = this.add.group()

      // Spawn monsters every 3.5 seconds
      this.spawnTimer = this.time.addEvent({
        delay: 3500,
        callback: this.spawnMonster,
        callbackScope: this,
        loop: true
      })

      // Game timer (2 minutes)
      const GAME_DURATION = 120_000
      this.gameTimer = this.time.addEvent({
        delay: GAME_DURATION,
        callback: () => this.endGame('timeout'),
        callbackScope: this,
      })

      // Stats update
      this.statsTimer = this.time.addEvent({
        delay: 1000,
        callback: () => { sharedState.elapsedSecs++ },
        loop: true
      })

      // Register word-typed callback
      sharedState.onWordTyped = (correct: boolean) => {
        if (correct) this.handleCorrectWord()
        else this.handleWrongWord()
      }

      // Spawn first monster immediately
      this.spawnMonster()
    }

    spawnMonster() {
      if (sharedState.wordBank.length === 0 || sharedState.gameOver) return
      const { width, height } = this.scale

      const wordIndex = Math.floor(Math.random() * sharedState.wordBank.length)
      const word      = sharedState.wordBank[wordIndex]

      // Monster group container
      const x = width + 60
      const y = height - 100

      const monsterGroup = this.add.container(x, y)

      // Monster body
      const body = this.add.text(0, 0, '👾', { fontSize: '48px' }).setOrigin(0.5)

      // Word label
      const label = this.add.text(0, -50, word, {
        fontSize: '20px',
        color: '#fbbf24',
        backgroundColor: '#1e293b',
        padding: { x: 10, y: 6 },
        borderRadius: 8,
        fontFamily: 'monospace',
        fontStyle: 'bold'
      }).setOrigin(0.5)

      monsterGroup.add([body, label])
      monsterGroup.setData('word', word)

      this.monsters!.add(monsterGroup)

      // If no active word, set it
      if (!sharedState.currentWord) {
        sharedState.currentWord = word
        label.setColor('#f87171')  // highlight active monster
      }

      // Move toward player
      const speed = this.getMonsterSpeed()
      this.tweens.add({
        targets:    monsterGroup,
        x:          80,
        duration:   speed,
        ease:       'Linear',
        onComplete: () => {
          if (monsterGroup.active) {
            this.takeDamage(20)
            monsterGroup.destroy()
            this.monsters!.remove(monsterGroup)
            this.pickNextWord()
          }
        }
      })
    }

    getMonsterSpeed(): number {
      const base: Record<string, number> = { beginner: 12000, easy: 9000, normal: 7000, hard: 5000, expert: 3500 }
      return base[props.difficulty] ?? 8000
    }

    handleCorrectWord() {
      const monsters = this.monsters!.getChildren() as Phaser.GameObjects.Container[]
      const target   = monsters.find(m => m.getData('word') === sharedState.currentWord)

      if (target) {
        this.cameras.main.shake(100, 0.01)

        const hitText = this.add.text(target.x, target.y - 30, '💥 ' + sharedState.combo + 'x!', {
          fontSize: '24px',
          color: '#fbbf24',
        }).setOrigin(0.5)
        
        this.tweens.add({
          targets:  hitText,
          y:        target.y - 80,
          alpha:    0,
          duration: 700,
          onComplete: () => hitText.destroy()
        })

        this.tweens.killTweensOf(target)
        target.destroy()
        this.monsters!.remove(target)

        sharedState.score  += 100 + (sharedState.combo * 10)
        sharedState.combo++
        sharedState.maxCombo = Math.max(sharedState.maxCombo, sharedState.combo)
      }

      this.pickNextWord()
    }

    handleWrongWord() {
      sharedState.combo    = 0
      sharedState.mistakes++
      this.cameras.main.shake(80, 0.008)
    }

    takeDamage(amount: number) {
      sharedState.playerHp = Math.max(0, sharedState.playerHp - amount)

      if (sharedState.playerHp <= 0) this.endGame('dead')
    }

    pickNextWord() {
      const remaining = (this.monsters!.getChildren() as Phaser.GameObjects.Container[])
        .filter(m => m.getData('word') !== sharedState.currentWord)
      if (remaining.length > 0) {
        sharedState.currentWord = remaining[0].getData('word')
        sharedState.playerInput = ''
        // Update label color for new target
        const label = remaining[0].list[1] as Phaser.GameObjects.Text
        label.setColor('#f87171')
      } else {
        sharedState.currentWord = ''
      }
    }

    endGame(reason: string) {
      this.spawnTimer?.destroy()
      this.gameTimer?.destroy()
      this.statsTimer?.destroy()
      sharedState.gameOver = true
      sharedState.onWordTyped = null
    }

    update() {
      const mins = sharedState.elapsedSecs / 60
      if (mins > 0) {
        sharedState.wpm = Math.round((sharedState.correctChars / 5) / mins)
      }
    }
  }

  await createGame(containerId, {
    type:            Phaser.AUTO,
    width:           800,
    height:          400,
    backgroundColor: '#1d2333',
    scene:           [MonsterScene],
    scale: {
      mode:       Phaser.Scale.FIT,
      autoCenter: Phaser.Scale.CENTER_BOTH,
    },
  })
  if (disposed) destroyGame()
}

function handleInput() {
  const input = sharedState.playerInput
  const word  = sharedState.currentWord
  if (!word) return

  sharedState.totalChars++

  const lastChar    = input.slice(-1)
  const expectedChar = word[input.length - 1]

  if (lastChar === expectedChar) {
    sharedState.correctChars++
  }
}

function handleKeydown(e: KeyboardEvent) {
  if (e.isComposing || e.keyCode === 229 || loading.value || loadFailed.value) return
  if (e.key !== 'Enter' && e.key !== ' ') return
  e.preventDefault()
  if (!sharedState.currentWord || !sharedState.onWordTyped) return

  const typed   = sharedState.playerInput.trim()
  const correct = typed === sharedState.currentWord

  sharedState.onWordTyped(correct)
  sharedState.playerInput = ''
}

watch(() => sharedState.gameOver, (over) => {
  if (!over) return
  emit('finished', {
    session_token: uuid(),
    game_mode:     'monster_battle',
    language:      props.lang,
    difficulty:    props.difficulty,
    correct_chars: sharedState.correctChars,
    total_chars:   sharedState.totalChars,
    correct_words: Math.floor(sharedState.score / 100),
    total_words:   sharedState.totalChars,
    mistakes:      sharedState.mistakes,
    max_combo:     sharedState.maxCombo,
    time_elapsed:  sharedState.elapsedSecs,
    time_limit:    120,
  })
})

async function loadGame() {
  loading.value = true
  loadFailed.value = false
  destroyGame()
  try {
    await initGame()
  } catch (error) {
    if (!disposed) {
      loadFailed.value = true
      destroyGame()
      console.error('[typing arcade] Failed to load game', error)
    }
  } finally {
    if (!disposed) {
      loading.value = false
      if (!loadFailed.value) nextTick(() => inputRef.value?.focus())
    }
  }
}

onMounted(loadGame)
onUnmounted(() => { disposed = true; destroyGame() })
</script>

<template>
  <div class="mx-auto max-w-4xl space-y-4">
    <TypingModeHeader mode="monster_battle" />
    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-3">
      <div v-for="stat in [{ label: t('typingArena.score'), value: sharedState.score, icon: 'heroicons:trophy' }, { label: t('typingArena.wpm'), value: sharedState.wpm, icon: 'heroicons:bolt' }, { label: t('typingArena.combo'), value: sharedState.combo + '×', icon: 'heroicons:fire' }, { label: t('typingArena.health'), value: sharedState.playerHp + '%', icon: 'heroicons:heart' }]" :key="stat.label" class="min-w-0 rounded-vikinger-lg border border-vikinger-purple/20 bg-white p-3 dark:bg-vikinger-dark-100 sm:p-4">
        <p class="flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-300"><Icon :icon="stat.icon" class="shrink-0 text-base text-vikinger-purple dark:text-vikinger-cyan" />{{ stat.label }}</p>
        <p class="mt-2 truncate text-2xl font-black tabular-nums text-vikinger-purple dark:text-vikinger-cyan">{{ stat.value }}</p>
      </div>
    </div>
    <div class="space-y-2">
      <div class="flex items-center justify-between gap-2 text-xs font-bold text-slate-600 dark:text-slate-300"><span>{{ t('typingArena.health') }}</span><span :class="sharedState.playerHp <= 25 ? 'text-rose-500' : ''">{{ sharedState.playerHp }}%</span></div>
      <div role="progressbar" :aria-label="t('typingArena.health')" :aria-valuenow="sharedState.playerHp" aria-valuemin="0" aria-valuemax="100" class="h-2 overflow-hidden rounded-full bg-vikinger-purple/10"><div class="h-full rounded-full transition-[width] duration-300 motion-reduce:transition-none" :class="sharedState.playerHp <= 25 ? 'bg-vikinger-pink' : 'bg-gradient-vikinger'" :style="{ width: sharedState.playerHp + '%' }"></div></div>
    </div>
    <div class="relative overflow-hidden rounded-vikinger-xl border border-vikinger-purple/30 bg-vikinger-dark shadow-vikinger">
      <div :id="containerId" class="w-full" style="height: 400px"></div>
      <div v-if="loading || loadFailed" class="absolute inset-0 flex flex-col items-center justify-center gap-4 bg-vikinger-dark p-5 text-center" role="status">
        <Icon :icon="loadFailed ? 'heroicons:exclamation-triangle' : 'eos-icons:loading'" class="text-4xl text-vikinger-cyan" />
        <p class="text-sm font-bold text-white">{{ t(loadFailed ? 'typingArena.loadError' : 'typingArena.loading') }}</p>
        <button v-if="loadFailed" class="min-h-[44px] rounded-vikinger bg-gradient-vikinger px-6 py-3 font-bold text-white" @click="loadGame">{{ t('typingArena.retry') }}</button>
      </div>
    </div>
    <TypingComboDisplay :combo="sharedState.combo" />
    <div class="space-y-3 rounded-vikinger-xl border border-vikinger-purple/20 bg-white p-4 text-center shadow-vikinger dark:bg-vikinger-dark-100 sm:p-6">
      <p class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ t('typingArena.target') }}</p>
      <p class="min-h-[40px] break-words text-2xl font-black text-vikinger-purple dark:text-vikinger-cyan sm:text-3xl">{{ sharedState.currentWord || '…' }}</p>
      <input ref="inputRef" v-model="sharedState.playerInput" :disabled="loading || loadFailed || sharedState.gameOver" :aria-label="t('typingArena.inputLabel')" :placeholder="t('typingArena.input')" class="min-h-[56px] w-full max-w-lg rounded-vikinger-lg border-2 border-vikinger-purple/25 bg-vikinger-light-200 px-4 py-4 text-center text-xl text-vikinger-dark outline-none transition-colors focus:border-vikinger-purple focus:ring-4 focus:ring-vikinger-purple/15 disabled:opacity-50 dark:bg-vikinger-dark-300 dark:text-white" autocomplete="off" autocorrect="off" autocapitalize="off" :spellcheck="false" @input="handleInput" @keydown="handleKeydown" />
      <p class="text-xs text-slate-600 dark:text-slate-300">{{ t('typingArena.arcadeInput') }}</p>
    </div>
  </div>
</template>
