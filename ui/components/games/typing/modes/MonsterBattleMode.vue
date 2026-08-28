<script setup lang="ts">
import { usePhaserGame } from '~/composables/usePhaserGame'
import { useTypingApi } from '~/composables/useTypingApi'
import type { Lang, Difficulty } from '~/stores/typing'

interface Props {
  lang: Lang
  difficulty: Difficulty
}
const props = defineProps<Props>()
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

async function initGame() {
  // โหลดคำศัพท์ก่อน
  const words = await fetchWords(props.lang, props.difficulty, 50)
  sharedState.wordBank = words.map((w: any) => w.text)

  // import Phaser เฉพาะ client side
  const Phaser = (await import('phaser')).default

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
      bg.fillGradientStyle(0x0f172a, 0x0f172a, 0x1e3a5f, 0x1e3a5f, 1)
      bg.fillRect(0, 0, width, height)

      // HP Bar
      this.createHpBar()

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

    createHpBar() {
      const { width } = this.scale
      this.add.text(width - 180, 20, 'HP', { fontSize: '14px', color: '#94a3b8', fontFamily: 'monospace' })
      this.add.rectangle(width - 100, 28, 150, 16, 0x1e293b).setOrigin(0.5)

      // Dynamic HP bar
      const hpBar = this.add.rectangle(width - 100, 28, 150, 16, 0x22c55e).setOrigin(0.5)
      this.registry.set('hpBar', hpBar)
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

      const hpBar = this.registry.get('hpBar') as Phaser.GameObjects.Rectangle
      if (hpBar) {
        const pct = sharedState.playerHp / 100
        hpBar.width = 150 * pct
        hpBar.setFillStyle(pct > 0.5 ? 0x22c55e : pct > 0.25 ? 0xf59e0b : 0xef4444)
      }

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
    backgroundColor: '#0f172a',
    scene:           [MonsterScene],
    scale: {
      mode:       Phaser.Scale.FIT,
      autoCenter: Phaser.Scale.CENTER_BOTH,
    },
  })
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
  if (e.key !== 'Enter' && e.key !== ' ') return
  e.preventDefault()
  if (!sharedState.currentWord || !sharedState.onWordTyped) return

  const typed   = sharedState.playerInput.trim()
  const correct = typed === sharedState.currentWord

  sharedState.onWordTyped(correct)
  sharedState.playerInput = ''
}

function keepFocus() {
  if (!sharedState.gameOver) nextTick(() => inputRef.value?.focus())
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

onMounted(async () => {
  await initGame()
  nextTick(() => inputRef.value?.focus())
})
onUnmounted(() => destroyGame())
</script>

<template>
<div class="max-w-4xl mx-auto space-y-4">
  <!-- Stats overlay -->
  <div class="grid grid-cols-4 gap-3 text-center">
    <div class="bg-slate-900 rounded-2xl p-3 border border-slate-800">
      <p class="text-xs text-slate-500 font-bold uppercase">Score</p>
      <p class="text-2xl font-black text-yellow-400">{{ sharedState.score }}</p>
    </div>
    <div class="bg-slate-900 rounded-2xl p-3 border border-slate-800">
      <p class="text-xs text-slate-500 font-bold uppercase">WPM</p>
      <p class="text-2xl font-black text-cyan-400">{{ sharedState.wpm }}</p>
    </div>
    <div class="bg-slate-900 rounded-2xl p-3 border border-slate-800">
      <p class="text-xs text-slate-500 font-bold uppercase">Combo</p>
      <p class="text-2xl font-black text-orange-400">{{ sharedState.combo }}x</p>
    </div>
    <div class="bg-slate-900 rounded-2xl p-3 border border-slate-800">
      <p class="text-xs text-slate-500 font-bold uppercase">HP</p>
      <p class="text-2xl font-black" :class="sharedState.playerHp > 50 ? 'text-green-400' : sharedState.playerHp > 25 ? 'text-yellow-400' : 'text-red-400'">
        {{ sharedState.playerHp }}%
      </p>
    </div>
  </div>

  <!-- Phaser Canvas Container -->
  <div
    :id="containerId"
    class="w-full rounded-3xl overflow-hidden border border-slate-800 bg-slate-900"
    style="height: 400px"
  ></div>

  <!-- Word to type -->
  <div class="text-center space-y-4">
    <div class="text-3xl font-black font-mono tracking-widest text-yellow-400">
      {{ sharedState.currentWord || '...' }}
    </div>
    <input
      ref="inputRef"
      v-model="sharedState.playerInput"
      @input="handleInput"
      @keydown="handleKeydown"
      @blur="keepFocus"
      :disabled="sharedState.gameOver"
      placeholder="พิมพ์คำแล้วกด Enter หรือ Space..."
      class="w-full max-w-md py-4 px-4 sm:px-6 bg-slate-900 border-2 border-slate-700 focus:border-yellow-400 rounded-2xl text-white text-xl font-mono text-center focus:outline-none transition-colors"
      autocomplete="off" autocorrect="off" spellcheck="false"
    />
    <p class="text-slate-600 text-sm">พิมพ์คำบนมอนสเตอร์ที่ highlight สีแดง กด <kbd class="bg-slate-800 px-2 py-0.5 rounded font-mono text-xs">Enter</kbd> เพื่อโจมตี</p>
  </div>
</div>
</template>
