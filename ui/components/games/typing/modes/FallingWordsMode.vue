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

const containerId = 'falling-words-canvas'
const { createGame, destroyGame } = usePhaserGame()
const { fetchWords } = useTypingApi()

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

const inputRef = ref<HTMLInputElement | null>(null)

async function initGame() {
  const words = await fetchWords(props.lang, props.difficulty, 50)
  sharedState.wordBank = words.map((w: any) => w.text)

  const Phaser = (await import('phaser')).default

  class FallingScene extends Phaser.Scene {
    fallingWords: Phaser.GameObjects.Group | null = null
    spawnTimer: Phaser.Time.TimerEvent | null = null
    statsTimer: Phaser.Time.TimerEvent | null = null

    constructor() { super({ key: 'FallingScene' }) }

    create() {
      const { width, height } = this.scale

      const bg = this.add.graphics()
      bg.fillGradientStyle(0x0f172a, 0x0f172a, 0x1e1b4b, 0x1e1b4b, 1)
      bg.fillRect(0, 0, width, height)

      this.createHpBar()

      this.fallingWords = this.add.group()

      const lanes = [width * 0.2, width * 0.4, width * 0.6, width * 0.8]
      
      this.spawnTimer = this.time.addEvent({
        delay: this.getSpawnDelay(),
        callback: () => {
          const lane = lanes[Math.floor(Math.random() * lanes.length)]
          const word = sharedState.wordBank[Math.floor(Math.random() * sharedState.wordBank.length)]
          this.spawnFallingWord(lane, word)
        },
        callbackScope: this,
        loop: true
      })

      this.statsTimer = this.time.addEvent({
        delay: 1000,
        callback: () => { sharedState.elapsedSecs++ },
        loop: true
      })

      sharedState.onWordTyped = (correct: boolean) => {
        if (correct) this.handleCorrectWord()
        else this.handleWrongWord()
      }
    }

    createHpBar() {
      const { width } = this.scale
      this.add.text(20, 20, 'HP', { fontSize: '14px', color: '#94a3b8', fontFamily: 'monospace' })
      this.add.rectangle(100, 28, 150, 16, 0x1e293b).setOrigin(0.5)
      const hpBar = this.add.rectangle(100, 28, 150, 16, 0x22c55e).setOrigin(0.5)
      this.registry.set('hpBar', hpBar)
    }

    getSpawnDelay(): number {
      const base: Record<string, number> = { beginner: 4000, easy: 3000, normal: 2000, hard: 1500, expert: 1000 }
      return base[props.difficulty] ?? 2500
    }

    spawnFallingWord(x: number, word: string) {
      if (sharedState.gameOver) return

      const text = this.add.text(x, -30, word, {
        fontSize: '22px',
        color: '#60a5fa',
        backgroundColor: '#1e293b88',
        padding: { x: 8, y: 4 },
        fontFamily: 'monospace',
        fontStyle: 'bold',
      }).setOrigin(0.5)

      text.setData('word', word)
      this.fallingWords!.add(text)

      if (!sharedState.currentWord) {
        sharedState.currentWord = word
        text.setColor('#f87171')
      }

      this.tweens.add({
        targets:    text,
        y:          this.scale.height + 30,
        duration:   this.getFallDuration(),
        ease:       'Linear',
        onComplete: () => {
          if (text.active) {
            this.takeDamage(10)
            text.destroy()
            this.fallingWords!.remove(text)
            if (sharedState.currentWord === word) this.pickNextWord()
          }
        }
      })
    }

    getFallDuration(): number {
      const map: Record<string, number> = { beginner: 8000, easy: 6000, normal: 5000, hard: 4000, expert: 2500 }
      return map[props.difficulty] ?? 5000
    }

    handleCorrectWord() {
      const words = this.fallingWords!.getChildren() as Phaser.GameObjects.Text[]
      const target = words.find(w => w.getData('word') === sharedState.currentWord)

      if (target) {
        this.tweens.killTweensOf(target)
        
        // Pop effect
        this.tweens.add({
          targets: target,
          scale: 1.5,
          alpha: 0,
          duration: 200,
          onComplete: () => target.destroy()
        })
        
        this.fallingWords!.remove(target)
        sharedState.score += 50 + (sharedState.combo * 5)
        sharedState.combo++
        sharedState.maxCombo = Math.max(sharedState.maxCombo, sharedState.combo)
      }

      this.pickNextWord()
    }

    handleWrongWord() {
      sharedState.combo = 0
      sharedState.mistakes++
      this.cameras.main.shake(100, 0.005)
    }

    takeDamage(amount: number) {
      sharedState.playerHp = Math.max(0, sharedState.playerHp - amount)
      const hpBar = this.registry.get('hpBar') as Phaser.GameObjects.Rectangle
      if (hpBar) {
        const pct = sharedState.playerHp / 100
        hpBar.width = 150 * pct
        hpBar.setFillStyle(pct > 0.5 ? 0x22c55e : pct > 0.25 ? 0xf59e0b : 0xef4444)
      }
      if (sharedState.playerHp <= 0) this.endGame()
    }

    pickNextWord() {
      const remaining = (this.fallingWords!.getChildren() as Phaser.GameObjects.Text[])
        .filter(w => w.getData('word') !== sharedState.currentWord)
      if (remaining.length > 0) {
        // Pick the lowest word (closest to bottom)
        remaining.sort((a, b) => b.y - a.y)
        sharedState.currentWord = remaining[0].getData('word')
        sharedState.playerInput = ''
        remaining[0].setColor('#f87171')
      } else {
        sharedState.currentWord = ''
      }
    }

    endGame() {
      this.spawnTimer?.destroy()
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
    type: Phaser.AUTO,
    width: 800,
    height: 500,
    backgroundColor: '#0f172a',
    scene: [FallingScene],
    scale: {
      mode: Phaser.Scale.FIT,
      autoCenter: Phaser.Scale.CENTER_BOTH,
    },
  })
}

function handleInput() {
  const input = sharedState.playerInput
  const word = sharedState.currentWord
  if (!word) return

  sharedState.totalChars++
  const lastChar = input.slice(-1)
  const expectedChar = word[input.length - 1]

  if (lastChar === expectedChar) {
    sharedState.correctChars++
  }
}

function handleKeydown(e: KeyboardEvent) {
  if (e.key !== 'Enter' && e.key !== ' ') return
  e.preventDefault()
  if (!sharedState.currentWord || !sharedState.onWordTyped) return

  const typed = sharedState.playerInput.trim()
  const correct = typed === sharedState.currentWord

  sharedState.onWordTyped(correct)
  sharedState.playerInput = ''
}

watch(() => sharedState.gameOver, (over) => {
  if (!over) return
  emit('finished', {
    session_token: uuid(),
    game_mode: 'falling_words',
    language: props.lang,
    difficulty: props.difficulty,
    correct_chars: sharedState.correctChars,
    total_chars: sharedState.totalChars,
    correct_words: Math.floor(sharedState.score / 50),
    total_words: sharedState.totalChars,
    mistakes: sharedState.mistakes,
    max_combo: sharedState.maxCombo,
    time_elapsed: sharedState.elapsedSecs,
    time_limit: 0,
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
  <div class="grid grid-cols-4 gap-3 text-center">
    <div class="bg-slate-900 rounded-2xl p-3 border border-slate-800">
      <p class="text-xs text-slate-500 font-bold uppercase">Score</p>
      <p class="text-2xl font-black text-blue-400">{{ sharedState.score }}</p>
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

  <div
    :id="containerId"
    class="w-full rounded-3xl overflow-hidden border border-slate-800 bg-slate-900 shadow-2xl"
    style="height: 500px"
  ></div>

  <div class="text-center space-y-4">
    <div class="text-3xl font-black font-mono tracking-widest text-blue-400 min-h-[40px]">
      {{ sharedState.currentWord || '...' }}
    </div>
    <input
      ref="inputRef"
      v-model="sharedState.playerInput"
      @input="handleInput"
      @keydown="handleKeydown"
      :disabled="sharedState.gameOver"
      placeholder="พิมพ์คำแล้วกด Enter หรือ Space..."
      class="w-full max-w-md py-4 px-6 bg-slate-900 border-2 border-slate-700 focus:border-blue-400 rounded-2xl text-white text-xl font-mono text-center focus:outline-none transition-colors"
      autocomplete="off" autocorrect="off" spellcheck="false"
    />
  </div>
</div>
</template>
