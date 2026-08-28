<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useTypingStore } from '~/stores/typing'
import { useTypingApi } from '~/composables/useTypingApi'
import AchievementToast from '~/components/games/typing/ui/AchievementToast.vue'
import confetti from 'canvas-confetti'

definePageMeta({
  layout: 'main',
})

const store = useTypingStore()
const authStore = useAuthStore()
const { fetchLeaderboard } = useTypingApi()

const result = computed(() => store.lastResult)
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
  if (result.value.accuracy >= 90) {
    confetti({
      particleCount: 150,
      spread: 70,
      origin: { y: 0.6 },
      colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444']
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

function formatWpm(wpm: number) {
  return Math.round(wpm)
}
</script>

<template>
  <div class="max-w-4xl mx-auto py-12 px-0 sm:px-4 space-y-12">
      <!-- Achievement Notification -->
      <AchievementToast :achievements="newAchievements" />

      <!-- Result Header -->
      <div v-if="result" class="text-center space-y-6 animate-in fade-in zoom-in duration-700">
        <div class="space-y-2">
          <h1 class="text-6xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">Session Complete!</h1>
          <p class="text-xl text-slate-500">Fantastic job! Here's how you did:</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-3xl mx-auto">
          <!-- WPM -->
          <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm">
            <span class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1">WPM</span>
            <span class="text-4xl font-black text-primary-600">{{ result.wpm }}</span>
          </div>
          <!-- Accuracy -->
          <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm">
            <span class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Accuracy</span>
            <span class="text-4xl font-black text-blue-600">{{ result.accuracy }}%</span>
          </div>
          <!-- Score -->
          <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm">
            <span class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Score</span>
            <span class="text-4xl font-black text-orange-500">{{ result.score }}</span>
          </div>
          <!-- XP -->
          <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-primary-500/20 shadow-lg shadow-primary-500/10">
            <span class="block text-xs font-black text-primary-400 uppercase tracking-widest mb-1">XP Earned</span>
            <span class="text-4xl font-black text-primary-500">+{{ result.xp_earned }}</span>
          </div>
        </div>
      </div>

      <!-- Details and Leaderboard -->
      <div class="grid md:grid-cols-2 gap-8 items-start">
        <!-- Bonuses -->
        <div v-if="result" class="bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-100 dark:border-slate-800 shadow-sm space-y-6">
          <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
            <Icon icon="heroicons:sparkles" class="text-yellow-500" />
            Bonus Points
          </h2>
          <div class="space-y-4">
            <div class="flex justify-between items-center py-3 border-b border-slate-50 dark:border-slate-800">
              <span class="text-slate-600 dark:text-slate-400 font-bold">Speed Bonus</span>
              <span class="font-black text-green-500">+{{ result.speed_bonus }}</span>
            </div>
            <div class="flex justify-between items-center py-3 border-b border-slate-50 dark:border-slate-800">
              <span class="text-slate-600 dark:text-slate-400 font-bold">Combo Bonus</span>
              <span class="font-black text-green-500">+{{ result.combo_bonus }}</span>
            </div>
            <div class="flex justify-between items-center py-3 border-b border-slate-50 dark:border-slate-800">
              <span class="text-slate-600 dark:text-slate-400 font-bold">Accuracy Bonus</span>
              <span class="font-black text-green-500">+{{ result.accuracy_bonus }}</span>
            </div>
            <div class="flex justify-between items-center py-3">
              <span class="text-slate-600 dark:text-slate-400 font-bold">Max Combo</span>
              <span class="font-black text-primary-500">{{ result.max_combo }}x</span>
            </div>
          </div>
        </div>

        <!-- Leaderboard -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-100 dark:border-slate-800 shadow-sm space-y-6">
          <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
            <Icon icon="heroicons:trophy" class="text-orange-500" />
            Global Ranking
          </h2>
          <div class="space-y-2">
            <div 
              v-for="(entry, index) in leaderboard" 
              :key="entry.user_id"
              class="flex items-center gap-4 p-3 rounded-2xl transition-colors"
              :class="index === 0 ? 'bg-orange-500/10 border border-orange-500/20' : 'hover:bg-slate-50 dark:hover:bg-slate-800'"
            >
              <div class="w-8 h-8 flex items-center justify-center font-black" :class="index === 0 ? 'text-orange-500' : 'text-slate-400'">
                #{{ index + 1 }}
              </div>
              <div class="flex-1 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                  <img v-if="entry.user?.profile_photo_url" :src="entry.user.profile_photo_url" class="w-full h-full object-cover" />
                  <div v-else class="w-full h-full flex items-center justify-center text-xs font-bold text-slate-500">
                    {{ entry.user?.name?.charAt(0) }}
                  </div>
                </div>
                <span class="font-bold text-slate-800 dark:text-white truncate max-w-[120px]">{{ entry.user?.name }}</span>
              </div>
              <div class="text-right">
                <div class="font-black text-slate-900 dark:text-white">{{ entry.top_score }}</div>
                <div class="text-[10px] font-bold text-slate-500 uppercase">{{ entry.top_wpm }} WPM</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex flex-col sm:flex-row gap-4 justify-center pt-8">
        <NuxtLink
          to="/play/games/typing/play"
          class="px-12 py-4 bg-primary-600 hover:bg-primary-700 text-white font-black text-xl rounded-2xl shadow-lg shadow-primary-500/30 transition-all hover:scale-105 active:scale-95 text-center"
        >
          PLAY AGAIN
        </NuxtLink>

        <button
          @click="shareToFeed"
          :disabled="sharing"
          class="px-8 py-4 border-2 border-slate-200 dark:border-slate-700 hover:border-primary-500 dark:hover:border-primary-500 text-slate-700 dark:text-slate-300 hover:text-primary-600 dark:hover:text-primary-400 font-black text-lg rounded-2xl transition-all hover:scale-105 disabled:opacity-50 flex items-center justify-center gap-2"
        >
          <Icon v-if="sharing" icon="eos-icons:loading" class="animate-spin" />
          <span v-else-if="shareSuccess" class="text-green-500">✅ แชร์แล้ว!</span>
          <template v-else>
            <Icon icon="heroicons:share" />
            แชร์ลง Feed
          </template>
        </button>

        <NuxtLink v-if="authStore.user" :to="`/play/games/typing/profile/${authStore.user.id}`"
          class="px-8 py-4 border-2 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 text-slate-600 dark:text-slate-400 font-black rounded-2xl transition-all text-center hover:scale-105 flex items-center justify-center"
        >
          👤 ดูโปรไฟล์ฉัน
        </NuxtLink>

        <NuxtLink
          to="/play/games/typing"
          class="px-12 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black text-xl rounded-2xl shadow-xl transition-all hover:scale-105 active:scale-95 text-center"
        >
          LOBBY
        </NuxtLink>
      </div>
  </div>
</template>
