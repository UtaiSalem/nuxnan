<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

definePageMeta({
  layout: 'main',
})

const route     = useRoute()
const { $api }  = useNuxtApp()
const authStore = useAuthStore()
const userId    = computed(() => route.params.userId as string)
const isMyProfile = computed(() => String(authStore.user?.id) === userId.value)

// Data
const profile      = ref<any>(null)
const stats        = ref<any>(null)
const achievements = ref<any[]>([])
const allAchievements = ref<any[]>([])
const history      = ref<any[]>([])
const wpmHistory   = ref<{ date: string; wpm: number }[]>([])
const loading      = ref(true)
const activeTab    = ref<'stats' | 'achievements' | 'history'>('stats')

// Chart data
const chartData = computed(() => ({
  labels:   wpmHistory.value.map(h => formatDateShort(h.date)),
  datasets: [{
    label:           'WPM',
    data:            wpmHistory.value.map(h => h.wpm),
    borderColor:     '#3b82f6',
    backgroundColor: '#3b82f620',
    tension:         0.4,
    fill:            true,
  }]
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    y: { beginAtZero: false, grid: { color: 'rgba(30, 41, 59, 0.5)' } },
    x: { grid: { color: 'rgba(30, 41, 59, 0.5)' } }
  }
}

onMounted(async () => {
  try {
    // โหลดข้อมูลคู่กัน
    const [profileRes, statsRes, achRes, allAchRes, histRes, wpmRes] = await Promise.all([
      $api.get(`/users/${userId.value}/show`),
      $api.get('/typing/sessions/best'), // Use the new best endpoint
      $api.get('/typing/achievements/mine'),
      $api.get('/typing/achievements'),
      $api.get('/typing/sessions/history', { params: { limit: 20 } }),
      $api.get('/typing/sessions/wpm-history', { params: { user_id: userId.value, days: 30 } }),
    ])

    profile.value      = profileRes.data.data
    stats.value        = statsRes.data.data
    achievements.value = achRes.data.data
    allAchievements.value = allAchRes.data.data
    history.value      = histRes.data.data?.data ?? []
    wpmHistory.value   = wpmRes.data.data ?? []
  } catch (e) {
    console.error('Profile load error:', e)
  } finally {
    loading.value = false
  }
})

// Achievement helpers
const earnedIds = computed(() => new Set(achievements.value.map((a: any) => a.id)))
const achievementProgress = computed(() =>
  `${achievements.value.length} / ${allAchievements.value.length}`
)

// Mode label
const modeLabel = (mode: string) =>
  ({ word_typing:'Word', time_attack:'Time Attack', sentence_typing:'Sentence',
     monster_battle:'Monster', falling_words:'Falling', classroom_race:'Race',
     daily_challenge:'Daily' } as any)[mode] ?? mode

function formatDateShort(d: string) {
  return new Date(d).toLocaleDateString('th-TH', { day: 'numeric', month: 'short' })
}
</script>

<template>
<div class="max-w-5xl mx-auto py-12 px-0 sm:px-4 space-y-8">
  <div v-if="loading" class="flex items-center justify-center py-40">
    <Icon icon="eos-icons:loading" class="text-5xl text-primary-500 animate-spin" />
  </div>

  <template v-else>
    <!-- ─── Profile Header ──────────────────────────────────────── -->
    <div class="relative bg-gradient-to-br from-slate-900 to-slate-800 rounded-3xl p-8 overflow-hidden border border-slate-700">
      <!-- BG decoration -->
      <div class="absolute inset-0 opacity-5">
        <div class="text-[200px] font-black text-white select-none -rotate-12 absolute -right-10 -top-10">⌨️</div>
      </div>

      <div class="relative flex flex-col md:flex-row items-start md:items-center gap-6">
        <!-- Avatar -->
        <div class="relative flex-shrink-0">
          <div class="w-24 h-24 rounded-3xl overflow-hidden bg-slate-700 ring-4 ring-primary-500/30">
            <img v-if="profile?.profile_photo_url || profile?.avatar"
              :src="profile.profile_photo_url ?? profile.avatar"
              class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex items-center justify-center text-4xl font-black text-slate-400">
              {{ profile?.name?.charAt(0)?.toUpperCase() }}
            </div>
          </div>
          <!-- Level Badge -->
          <div class="absolute -bottom-2 -right-2 bg-primary-500 text-white text-xs font-black rounded-full w-8 h-8 flex items-center justify-center shadow-lg">
            {{ profile?.level ?? 1 }}
          </div>
        </div>

        <div class="flex-1 space-y-2">
          <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-3xl font-black text-white">{{ profile?.name }}</h1>
            <span v-if="isMyProfile" class="text-xs bg-primary-500/20 text-primary-400 border border-primary-500/30 font-bold px-3 py-1 rounded-full">
              โปรไฟล์ของฉัน
            </span>
          </div>
          <p class="text-slate-400 text-sm">สมาชิกตั้งแต่ {{ new Date(profile?.created_at).toLocaleDateString('th-TH') }}</p>

          <!-- Key stats inline -->
          <div class="flex flex-wrap gap-4 pt-1">
            <div class="text-center">
              <p class="text-2xl font-black text-cyan-400">{{ stats?.max_wpm ?? 0 }}</p>
              <p class="text-slate-500 text-xs font-bold">Best WPM</p>
            </div>
            <div class="w-px bg-slate-700"></div>
            <div class="text-center">
              <p class="text-2xl font-black text-green-400">{{ stats?.avg_accuracy ? Number(stats.avg_accuracy).toFixed(1) : 0 }}%</p>
              <p class="text-slate-500 text-xs font-bold">Avg Accuracy</p>
            </div>
            <div class="w-px bg-slate-700"></div>
            <div class="text-center">
              <p class="text-2xl font-black text-orange-400">{{ stats?.total_sessions ?? 0 }}</p>
              <p class="text-slate-500 text-xs font-bold">Sessions</p>
            </div>
            <div class="w-px bg-slate-700"></div>
            <div class="text-center">
              <p class="text-2xl font-black text-yellow-400">{{ stats?.total_xp ?? 0 }}</p>
              <p class="text-slate-500 text-xs font-bold">XP จาก Typing</p>
            </div>
          </div>
        </div>

        <!-- Achievement badge count -->
        <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-2xl p-5 text-center flex-shrink-0">
          <p class="text-4xl mb-1">🏅</p>
          <p class="text-yellow-400 font-black text-xl">{{ achievementProgress }}</p>
          <p class="text-yellow-600 text-xs font-bold">Achievements</p>
        </div>
      </div>
    </div>

    <!-- ─── Tabs ─────────────────────────────────────────────────── -->
    <div class="flex gap-1 bg-slate-100 dark:bg-slate-900 rounded-2xl p-1 border border-slate-200 dark:border-slate-800">
      <button v-for="tab in [
        { id: 'stats',        label: '📊 สถิติ'       },
        { id: 'achievements', label: '🏅 Achievements'  },
        { id: 'history',      label: '📋 ประวัติ'       },
      ]" :key="tab.id"
        @click="activeTab = tab.id as any"
        class="flex-1 py-3 rounded-xl font-bold text-sm transition-all"
        :class="activeTab === tab.id
          ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm'
          : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
      >{{ tab.label }}</button>
    </div>

    <!-- ─── Tab: Stats ────────────────────────────────────────────── -->
    <div v-show="activeTab === 'stats'" class="space-y-6">
      <!-- WPM Chart -->
      <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200 dark:border-slate-800">
        <h3 class="font-black text-slate-800 dark:text-white mb-6 uppercase tracking-wider text-sm">
          📈 WPM Progress (30 วันย้อนหลัง)
        </h3>
        <div v-if="wpmHistory.length > 0" class="h-[250px]">
          <Line :data="chartData" :options="chartOptions" />
        </div>
        <p v-else class="text-slate-400 text-center py-8 text-sm">ยังไม่มีข้อมูล — เล่นเกมก่อนเพื่อดูกราฟ</p>
      </div>

      <!-- Best by Mode -->
      <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200 dark:border-slate-800">
        <h3 class="font-black text-slate-800 dark:text-white mb-6 uppercase tracking-wider text-sm">
          🎯 สถิติสูงสุดแต่ละโหมด
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <div v-for="mode in ['word_typing','time_attack','sentence_typing','monster_battle','falling_words','classroom_race']"
            :key="mode"
            class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-5 text-center border border-slate-100 dark:border-slate-700">
            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-2">{{ modeLabel(mode) }}</p>
            <p class="text-2xl font-black text-slate-700 dark:text-slate-200">
              {{ stats?.best_by_mode?.[mode]?.wpm ?? '—' }}
            </p>
            <p class="text-slate-500 text-xs">WPM</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ─── Tab: Achievements ─────────────────────────────────────── -->
    <div v-show="activeTab === 'achievements'" class="space-y-4">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div v-for="ach in allAchievements" :key="ach.id"
          class="rounded-2xl p-5 border-2 transition-all text-center"
          :class="earnedIds.has(ach.id)
            ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-300 dark:border-yellow-700 shadow-md'
            : 'bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 opacity-50 grayscale'"
        >
          <p class="text-4xl mb-3">{{ ach.icon }}</p>
          <p class="font-black text-sm text-slate-800 dark:text-white leading-tight">{{ ach.name }}</p>
          <p class="text-slate-500 text-xs mt-1 leading-tight">{{ ach.description }}</p>
          <div v-if="earnedIds.has(ach.id)"
            class="mt-3 bg-yellow-500/20 rounded-full px-2 py-0.5 inline-block">
            <p class="text-yellow-600 dark:text-yellow-400 text-xs font-black">+{{ ach.xp_reward }} XP ✓</p>
          </div>
          <div v-else class="mt-3 text-slate-400 text-xs font-bold">🔒 ยังไม่ได้รับ</div>
        </div>
      </div>
    </div>

    <!-- ─── Tab: History ──────────────────────────────────────────── -->
    <div v-show="activeTab === 'history'" class="space-y-3">
      <div v-if="history.length === 0" class="text-slate-400 text-center py-12 text-sm">ยังไม่มีประวัติการเล่น</div>
      <div v-for="session in history" :key="session.id"
        class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 px-6 py-4 flex items-center gap-4">
        <!-- Mode icon -->
        <div class="w-12 h-12 rounded-2xl bg-primary-50 dark:bg-primary-900/20 flex items-center justify-center flex-shrink-0 text-xl">
          {{ { word_typing:'📝', time_attack:'⏱️', sentence_typing:'📜',
               monster_battle:'👾', falling_words:'🌧️', classroom_race:'🏁', daily_challenge:'📅' }[session.game_mode] ?? '🎮' }}
        </div>
        <!-- Details -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="font-bold text-slate-800 dark:text-white text-sm">{{ modeLabel(session.game_mode) }}</span>
            <span class="text-xs text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">
              {{ session.difficulty }} · {{ session.language.toUpperCase() }}
            </span>
          </div>
          <p class="text-slate-400 text-xs mt-0.5">
            {{ new Date(session.created_at).toLocaleString('th-TH') }}
          </p>
        </div>
        <!-- Stats -->
        <div class="hidden sm:flex items-center gap-6 text-center flex-shrink-0">
          <div>
            <p class="font-black text-cyan-500 text-lg">{{ session.wpm }}</p>
            <p class="text-slate-400 text-[10px] font-bold">WPM</p>
          </div>
          <div>
            <p class="font-black text-green-500 text-lg">{{ session.accuracy }}%</p>
            <p class="text-slate-400 text-[10px] font-bold">ACC</p>
          </div>
          <div>
            <p class="font-black text-orange-500 text-lg">{{ session.score }}</p>
            <p class="text-slate-400 text-[10px] font-bold">SCORE</p>
          </div>
          <div>
            <p class="font-black text-yellow-500 text-lg">+{{ session.xp_earned }}</p>
            <p class="text-slate-400 text-[10px] font-bold">XP</p>
          </div>
        </div>
      </div>
    </div>
  </template>
</div>
</template>
