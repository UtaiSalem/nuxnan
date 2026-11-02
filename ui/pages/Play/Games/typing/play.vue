<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useTypingStore, type GameMode } from '~/stores/typing'
import WordTypingMode from '~/components/games/typing/modes/WordTypingMode.vue'
import TimeAttackMode from '~/components/games/typing/modes/TimeAttackMode.vue'
import SentenceTypingMode from '~/components/games/typing/modes/SentenceTypingMode.vue'
import MonsterBattleMode from '~/components/games/typing/modes/MonsterBattleMode.vue'
import FallingWordsMode from '~/components/games/typing/modes/FallingWordsMode.vue'
import KeyTrainingMode from '~/components/games/typing/modes/KeyTrainingMode.vue'
import LetterRunnerMode from '~/components/games/typing/modes/LetterRunnerMode.vue'
import TypingGameSetup from '~/components/games/typing/TypingGameSetup.vue'
import { useTypingApi } from '~/composables/useTypingApi'

definePageMeta({
  layout: 'main',
})

const store = useTypingStore()
const route = useRoute()
const router = useRouter()
const { submitSession } = useTypingApi()

// Read query parameter `mode` (e.g. `mode=key_training` from redirect or direct link)
// and set mode before rendering so opening direct links or refreshing retains Key Training
const VALID_MODES: GameMode[] = [
  'key_training',
  'word_typing',
  'time_attack',
  'sentence_typing',
  'monster_battle',
  'falling_words',
  'letter_runner',
]

const syncModeFromQuery = () => {
  const queryMode = route.query.mode as string | undefined
  if (queryMode && VALID_MODES.includes(queryMode as GameMode)) {
    store.selectedMode = queryMode as GameMode
  }
}

syncModeFromQuery()
watch(() => route.query.mode, syncModeFromQuery)

// Shared setup gate: non-key-training modes show TypingGameSetup first, then mount the
// mode component (with chosen settings already in the store) on START.
// Key Training goes directly into its progressive stage map inside KeyTrainingMode.
const started = ref(false)

const isPhaserMode = computed(() =>
  ['monster_battle', 'falling_words'].includes(store.selectedMode)
)

const isKeyTraining = computed(() => store.selectedMode === 'key_training')

async function onFinished(result: any) {
  store.setResult(null) // Clear previous
  
  try {
    const savedResult = await submitSession(result)
    store.setResult(savedResult)
    router.push('/play/games/typing/result')
  } catch (error) {
    console.error('Failed to submit session', error)
    // Even if it fails to save, we show the local result
    store.setResult({
      ...result,
      wpm: Math.round((result.correct_chars / 5) / (Math.max(1, result.time_elapsed) / 60)),
      accuracy: result.total_chars > 0 ? Math.round(result.correct_chars / result.total_chars * 100) : 100,
      score: 0,
      xp_earned: 0,
      speed_bonus: 0,
      combo_bonus: 0,
      accuracy_bonus: 0
    })
    router.push('/play/games/typing/result')
  }
}

const currentModeComponent = computed(() => {
  switch (store.selectedMode) {
    case 'key_training':    return KeyTrainingMode
    case 'time_attack':     return TimeAttackMode
    case 'sentence_typing': return SentenceTypingMode
    case 'monster_battle':  return MonsterBattleMode
    case 'falling_words':   return FallingWordsMode
    case 'letter_runner':   return LetterRunnerMode
    default:                return WordTypingMode
  }
})
</script>

<template>
  <div class="min-h-screen pt-3 pb-6 px-2 sm:px-4 bg-slate-50/50 dark:bg-slate-950/50">
    <div class="max-w-5xl mx-auto space-y-2 sm:space-y-3">
      <!-- Breadcrumbs / Back -->
      <div class="flex items-center gap-4">
        <NuxtLink to="/play/games/typing" class="flex min-h-[36px] items-center gap-2 text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors group">
          <Icon icon="heroicons:arrow-left" class="text-xl group-hover:-translate-x-1 transition-transform" />
          <span class="font-bold uppercase tracking-widest text-xs">Back to Lobby</span>
        </NuxtLink>
      </div>

      <!-- Game Stage -->

      <!-- Setup gate — for non-Key-Training modes -->
      <TypingGameSetup v-if="!started && !isKeyTraining" :mode="store.selectedMode" @start="started = true" />

      <!-- Key Training — progressive levels & sub-stages directly inside KeyTrainingMode -->
      <KeyTrainingMode v-else-if="isKeyTraining" :auto-start="true" @finished="onFinished" />

      <!-- Phaser-based modes (Monster Battle, Falling Words) -->
      <ClientOnly v-else-if="isPhaserMode">
        <component
          :is="currentModeComponent"
          :lang="store.selectedLang"
          :difficulty="store.selectedDifficulty"
          @finished="onFinished"
        />
        <template #fallback>
          <div class="flex flex-col items-center justify-center py-40 space-y-4">
            <Icon icon="eos-icons:loading" class="text-5xl text-primary-500" />
            <p class="text-slate-500 font-bold animate-pulse">LOADING GAME ENGINE...</p>
          </div>
        </template>
      </ClientOnly>

      <!-- Word / Time Attack / Sentence modes -->
      <component
        v-else
        :is="currentModeComponent"
        :lang="store.selectedLang"
        :difficulty="store.selectedDifficulty"
        :time-limit="store.selectedTimeLimit"
        :auto-start="true"
        @finished="onFinished"
      />
    </div>
  </div>
</template>
