<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useTypingStore } from '~/stores/typing'
import WordTypingMode from '~/components/games/typing/modes/WordTypingMode.vue'
import TimeAttackMode from '~/components/games/typing/modes/TimeAttackMode.vue'
import SentenceTypingMode from '~/components/games/typing/modes/SentenceTypingMode.vue'
import MonsterBattleMode from '~/components/games/typing/modes/MonsterBattleMode.vue'
import FallingWordsMode from '~/components/games/typing/modes/FallingWordsMode.vue'
import KeyTrainingMode from '~/components/games/typing/modes/KeyTrainingMode.vue'
import { useTypingApi } from '~/composables/useTypingApi'

definePageMeta({
  layout: 'main',
})

const store = useTypingStore()
const router = useRouter()
const { submitSession } = useTypingApi()

const isPhaserMode = computed(() =>
  ['monster_battle', 'falling_words'].includes(store.selectedMode)
)

const isKeyTraining = computed(() => store.selectedMode === 'key_training')

async function onFinished(result: any) {
  store.setResult(null) // Clear previous
  
  try {
    const savedResult = await submitSession(result)
    store.setResult(savedResult)
    router.push('/Play/Games/typing/result')
  } catch (error) {
    console.error('Failed to submit session', error)
    // Even if it fails to save, we show the local result
    store.setResult({
      ...result,
      score: 0,
      xp_earned: 0,
      speed_bonus: 0,
      combo_bonus: 0,
      accuracy_bonus: 0
    })
    router.push('/Play/Games/typing/result')
  }
}

const currentModeComponent = computed(() => {
  switch (store.selectedMode) {
    case 'key_training':    return KeyTrainingMode
    case 'time_attack':     return TimeAttackMode
    case 'sentence_typing': return SentenceTypingMode
    case 'monster_battle':  return MonsterBattleMode
    case 'falling_words':   return FallingWordsMode
    default:                return WordTypingMode
  }
})
</script>

<template>
  <div class="min-h-screen py-12 px-4 bg-slate-50/50 dark:bg-slate-950/50">
    <div class="max-w-5xl mx-auto space-y-8">
      <!-- Breadcrumbs / Back -->
      <div class="flex items-center gap-4">
        <NuxtLink to="/Play/Games/typing" class="flex items-center gap-2 text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors group">
          <Icon icon="heroicons:arrow-left" class="text-xl group-hover:-translate-x-1 transition-transform" />
          <span class="font-bold uppercase tracking-widest text-xs">Back to Lobby</span>
        </NuxtLink>
      </div>

      <!-- Game Stage -->

      <!-- Key Training — standalone, no API words needed -->
      <KeyTrainingMode v-if="isKeyTraining" />

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
        @finished="onFinished"
      />
    </div>
  </div>
</template>
