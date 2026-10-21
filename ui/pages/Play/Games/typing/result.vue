<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useTypingStore } from '~/stores/typing'
import { useTypingApi } from '~/composables/useTypingApi'
import AchievementToast from '~/components/games/typing/ui/AchievementToast.vue'
import confetti from 'canvas-confetti'

definePageMeta({
  layout: 'main',
})

const { t } = useI18n()
const store = useTypingStore()
const authStore = useAuthStore()
const { fetchLeaderboard } = useTypingApi()

const result = computed(() => store.lastResult)
const nextGoal = computed(() => {
  const r = result.value
  if (!r) return ''
  return r.accuracy < 95
    ? t('typingArena.accuracyGoal', { count: Math.min(100, Math.max(80, Math.round(r.accuracy) + 3)) })
    : t('typingArena.speedGoal', { count: Math.max(10, Math.round(r.wpm) + 5) })
})
const resultStats = computed(() => result.value ? [
  { label: t('typingArena.wpm'), value: Math.round(result.value.wpm), icon: 'heroicons:bolt' },
  { label: t('typingArena.accuracy'), value: `${result.value.accuracy}%`, icon: 'heroicons:check-badge' },
  { label: t('typingArena.score'), value: result.value.score, icon: 'heroicons:trophy' },
  { label: t('typingArena.xp'), value: `+${result.value.xp_earned}`, icon: 'heroicons:sparkles' },
] : [])
const leaderboard = ref<any[]>([])
const newAchievements = ref<any[]>([])

const sharing = ref(false)
const shareSuccess = ref(false)

async function shareToFeed() {
  if (!result.value || sharing.value) return
  sharing.value = true
  try {
    const { $api } = useNuxtApp()

    await $api.post('/posts', {
      content: buildShareText(),
      post_type: 'typing_result',
      metadata: {
        type:       'typing_result',
        wpm:        result.value.wpm,
        accuracy:   result.value.accuracy,
        score:      result.value.score,
        max_combo:  result.value.max_combo,
        game_mode:  store.selectedMode,
        language:   store.selectedLang,
        difficulty: store.selectedDifficulty,
        xp_earned:  result.value.xp_earned,
      }
    })
    shareSuccess.value = true
    setTimeout(() => { shareSuccess.value = false }, 3000)
  } catch (e) {
    console.error('Share failed:', e)
  } finally {
    sharing.value = false
  }
}

function buildShareText(): string {
  const r = result.value!
  const modeLabels: Record<string, string> = {
    word_typing: 'Word Typing', time_attack: 'Time Attack',
    sentence_typing: 'Sentence', monster_battle: 'Monster Battle',
    falling_words: 'Falling Words', classroom_race: 'Classroom Race',
    daily_challenge: 'Daily Challenge'
  }
  const mode = modeLabels[store.selectedMode] ?? store.selectedMode
  const stars = r.accuracy >= 95 ? '⭐⭐⭐' : r.accuracy >= 85 ? '⭐⭐' : '⭐'
  return `${stars} ฉันเพิ่งทำ ${r.wpm} WPM ด้วยความแม่นยำ ${r.accuracy}% ในโหมด ${mode}! ` +
         `คะแนน: ${r.score} pts | Max Combo: ${r.max_combo}x | +${r.xp_earned} XP 🎮 #TypingMaster`
}

onMounted(async () => {
  if (!result.value) {
    // If no result, redirect back
    return useRouter().push('/play/games/typing')
  }

  // Extract achievements from result
  if (result.value.new_achievements) {
    newAchievements.value = result.value.new_achievements
  }

  // Trigger confetti for good performance
  if (result.value.accuracy >= 90 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    confetti({
      particleCount: 150,
      spread: 70,
      origin: { y: 0.6 },
      colors: ['#615dfa', '#23d2e2', '#ff6b81', '#ffd700']
    })
  }

  // Load leaderboard
  try {
    leaderboard.value = await fetchLeaderboard(
      store.selectedMode, 
      store.selectedLang, 
      'all'
    )
  } catch (error) {
    console.error('Failed to load leaderboard', error)
  }
})

</script>

<template>
  <div class="mx-auto max-w-4xl space-y-5 px-3 py-6 sm:px-4 sm:py-10">
    <AchievementToast :achievements="newAchievements" />
    <template v-if="result">
      <section class="relative overflow-hidden rounded-vikinger-xl border border-vikinger-purple/20 bg-white p-5 text-center shadow-vikinger dark:bg-vikinger-dark-100 sm:p-8">
        <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-vikinger"></div>
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-vikinger text-3xl text-white shadow-vikinger"><Icon icon="heroicons:trophy" /></div>
        <h1 class="text-2xl font-black text-vikinger-dark dark:text-white sm:text-4xl">{{ t('typingArena.roundComplete') }}</h1>
        <p class="mx-auto mt-3 max-w-lg text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ t('typingArena.resultIntro') }}</p>
        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div v-for="stat in resultStats" :key="stat.label" class="min-w-0 rounded-vikinger-lg border border-vikinger-purple/15 bg-vikinger-purple/5 p-3 sm:p-4">
            <Icon :icon="stat.icon" class="mx-auto mb-2 text-xl text-vikinger-purple dark:text-vikinger-cyan" />
            <p class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ stat.label }}</p>
            <p class="mt-2 break-words text-2xl font-black tabular-nums text-vikinger-purple dark:text-vikinger-cyan sm:text-3xl">{{ stat.value }}</p>
          </div>
        </div>
      </section>
      <section class="rounded-vikinger-xl bg-gradient-vikinger p-5 text-white shadow-vikinger sm:p-6">
        <p class="text-xs font-black uppercase tracking-wider">{{ t('typingArena.nextGoal') }}</p>
        <h2 class="mt-2 text-lg font-black sm:text-xl">{{ nextGoal }}</h2>
        <div class="mt-5 flex flex-col gap-3 sm:flex-row">
          <NuxtLink to="/play/games/typing/play" class="flex min-h-[48px] items-center justify-center gap-2 rounded-vikinger bg-white px-6 py-3 font-black text-vikinger-purple shadow-md transition-transform hover:-translate-y-0.5 motion-reduce:transform-none"><Icon icon="heroicons:arrow-path" />{{ t('typingArena.again') }}</NuxtLink>
          <NuxtLink to="/play/games/typing" class="flex min-h-[48px] items-center justify-center gap-2 rounded-vikinger border border-white/50 bg-vikinger-dark/20 px-6 py-3 font-bold text-white hover:bg-vikinger-dark/30"><Icon icon="heroicons:squares-2x2" />{{ t('typingArena.changeMode') }}</NuxtLink>
        </div>
      </section>
      <div class="grid items-start gap-5 md:grid-cols-2">
        <section class="space-y-4 rounded-vikinger-xl border border-vikinger-purple/15 bg-white p-5 dark:bg-vikinger-dark-100 sm:p-6">
          <h2 class="flex items-center gap-2 text-lg font-black text-vikinger-dark dark:text-white"><Icon icon="heroicons:sparkles" class="text-vikinger-purple dark:text-vikinger-cyan" />{{ t('typingArena.bonuses') }}</h2>
          <dl class="divide-y divide-vikinger-purple/10">
            <div v-for="bonus in [{ key: 'speedBonus', value: result.speed_bonus }, { key: 'comboBonus', value: result.combo_bonus }, { key: 'accuracyBonus', value: result.accuracy_bonus }, { key: 'maxCombo', value: result.max_combo }]" :key="bonus.key" class="flex justify-between gap-3 py-3 text-sm">
              <dt class="text-slate-600 dark:text-slate-300">{{ t(`typingArena.${bonus.key}`) }}</dt><dd class="shrink-0 font-black text-vikinger-purple dark:text-vikinger-cyan">{{ bonus.key === 'maxCombo' ? `${bonus.value}×` : `+${bonus.value}` }}</dd>
            </div>
          </dl>
        </section>
        <section class="space-y-4 rounded-vikinger-xl border border-vikinger-purple/15 bg-white p-5 dark:bg-vikinger-dark-100 sm:p-6">
          <h2 class="flex items-center gap-2 text-lg font-black text-vikinger-dark dark:text-white"><Icon icon="heroicons:trophy" class="text-orange-500" />{{ t('typingArena.ranking') }}</h2>
          <p v-if="!leaderboard.length" class="py-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ t('typingArena.noRanking') }}</p>
          <div v-for="(entry, index) in leaderboard.slice(0, 5)" :key="entry.user_id" class="flex items-center gap-3 rounded-vikinger bg-vikinger-purple/5 p-3">
            <span class="shrink-0 text-sm font-black text-vikinger-purple dark:text-vikinger-cyan">#{{ index + 1 }}</span>
            <span class="min-w-0 flex-1 truncate text-sm font-bold text-vikinger-dark dark:text-white">{{ entry.user?.name }}</span>
            <div class="shrink-0 text-right text-sm"><p class="font-black text-vikinger-dark dark:text-white">{{ entry.top_score }}</p><p class="text-xs text-slate-600 dark:text-slate-300">{{ entry.top_wpm }} WPM</p></div>
          </div>
        </section>
      </div>
      <div class="flex flex-col justify-center gap-3 sm:flex-row">
        <button v-if="authStore.user" :disabled="sharing" class="flex min-h-[44px] items-center justify-center gap-2 rounded-vikinger border border-vikinger-purple/25 px-5 py-3 text-sm font-bold text-vikinger-purple disabled:opacity-50 dark:text-vikinger-cyan" @click="shareToFeed"><Icon :icon="sharing ? 'eos-icons:loading' : 'heroicons:share'" />{{ t(shareSuccess ? 'typingArena.shared' : 'typingArena.share') }}</button>
        <NuxtLink v-if="authStore.user" :to="`/play/games/typing/profile/${authStore.user.id}`" class="flex min-h-[44px] items-center justify-center gap-2 rounded-vikinger border border-vikinger-purple/25 px-5 py-3 text-sm font-bold text-vikinger-purple dark:text-vikinger-cyan"><Icon icon="heroicons:user" />{{ t('typingArena.profile') }}</NuxtLink>
      </div>
    </template>
  </div>
</template>
