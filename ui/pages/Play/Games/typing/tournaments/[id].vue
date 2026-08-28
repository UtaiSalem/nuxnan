<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useTypingStore } from '~/stores/typing'
import WordTypingMode from '~/components/games/typing/modes/WordTypingMode.vue'
import TimeAttackMode from '~/components/games/typing/modes/TimeAttackMode.vue'
import SentenceTypingMode from '~/components/games/typing/modes/SentenceTypingMode.vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'main',
})

const route  = useRoute()
const store  = useTypingStore()
const auth   = useAuthStore()
const { $api } = useNuxtApp()

const tournament  = ref<any>(null)
const leaderboard = ref<any[]>([])
const myEntry     = ref<any>(null)
const myRank      = ref<number | null>(null)
const loading     = ref(true)
const playing     = ref(false)
const lastResult  = ref<any>(null)
const submitting  = ref(false)

async function loadTournament() {
  loading.value = true
  try {
    const res = await $api.get(`/typing/tournaments/${route.params.id}`)
    tournament.value  = res.data.tournament
    leaderboard.value = res.data.leaderboard
    myEntry.value     = res.data.my_entry
    myRank.value      = res.data.my_rank
  } catch (error) {
    console.error('Failed to load tournament:', error)
  } finally {
    loading.value = false
  }
}

function startPlaying() {
  if (!tournament.value) return
  store.setConfig(
    tournament.value.game_mode,
    tournament.value.language === 'any' ? 'en' : tournament.value.language,
    tournament.value.difficulty === 'any' ? 'normal' : tournament.value.difficulty,
    tournament.value.time_limit
  )
  playing.value = true
  lastResult.value = null
}

async function onGameFinished(result: any) {
  playing.value = false
  submitting.value = true
  
  try {
    // Generate session token if not present
    if (!result.session_token) {
      result.session_token = uuid()
    }
    
    const res = await $api.post(`/typing/tournaments/${route.params.id}/attempt`, result)
    lastResult.value  = res.data
    await loadTournament()  // refresh leaderboard
  } catch (error) {
    console.error('Failed to submit attempt:', error)
    alert('Failed to submit result. Please try again.')
  } finally {
    submitting.value = false
  }
}

async function claimPrize() {
  try {
    const res = await $api.post(`/typing/tournaments/${route.params.id}/claim`)
    if (myEntry.value) {
      myEntry.value.prize_claimed = true
    }
    alert(`🎉 รับรางวัล +${res.data.prize_xp} XP, +${res.data.prize_pp} PP สำเร็จ!`)
  } catch (error) {
    console.error('Failed to claim prize:', error)
    alert('Failed to claim prize.')
  }
}

function getModeComponent(mode: string) {
  switch (mode) {
    case 'time_attack':     return TimeAttackMode
    case 'sentence_typing': return SentenceTypingMode
    default:                return WordTypingMode
  }
}

onMounted(loadTournament)
</script>

<template>
  <div class="max-w-5xl mx-auto py-12 px-0 sm:px-4 space-y-8">
    <div v-if="loading" class="text-center py-20 text-slate-400">
      <div class="animate-spin mb-4 text-4xl">🏆</div>
      กำลังโหลด...
    </div>

    <template v-else-if="tournament">
      <!-- Header -->
      <div class="rounded-3xl p-10 text-white space-y-4 shadow-xl"
        :style="{ background: `linear-gradient(135deg, ${tournament.banner_color}, ${tournament.banner_color}99)` }">
        <div class="flex items-center gap-3">
          <span class="bg-white/20 text-xs font-black px-3 py-1 rounded-full uppercase tracking-widest">{{ tournament.type }}</span>
          <span v-if="tournament.status === 'active'"
            class="flex items-center gap-1.5 bg-green-500/30 border border-green-400/30 text-xs font-black px-3 py-1 rounded-full">
            <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span> LIVE
          </span>
          <span v-else-if="tournament.status === 'finished'"
            class="bg-slate-900/30 text-xs font-black px-3 py-1 rounded-full uppercase tracking-widest">
            FINISHED
          </span>
        </div>
        <h1 class="text-4xl font-black leading-tight">{{ tournament.name }}</h1>
        <p class="text-white/70">{{ tournament.description }}</p>

        <!-- Prize Pool -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 pt-2">
          <div v-for="(prize, rank) in [
            { label:'🥇 อันดับ 1', xp: tournament.prize_1st_xp, pp: tournament.prize_1st_pp },
            { label:'🥈 อันดับ 2', xp: tournament.prize_2nd_xp, pp: tournament.prize_2nd_pp },
            { label:'🥉 อันดับ 3', xp: tournament.prize_3rd_xp, pp: tournament.prize_3rd_pp },
            { label:'🎮 เข้าร่วม', xp: tournament.prize_all_xp, pp: 0 }
          ]" :key="rank"
            class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 text-center border border-white/20">
            <p class="text-xs font-bold opacity-80 mb-1 uppercase tracking-tighter">{{ prize.label }}</p>
            <p class="font-black text-lg">+{{ prize.xp }} XP</p>
            <p v-if="prize.pp > 0" class="text-xs opacity-70 font-bold">+{{ prize.pp }} PP</p>
          </div>
        </div>
      </div>

      <!-- My Status -->
      <div v-if="myEntry" class="bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200 dark:border-slate-800 flex items-center justify-between flex-wrap gap-6 shadow-sm">
        <div class="flex items-center gap-8 flex-wrap">
          <div class="text-center">
            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">อันดับของฉัน</p>
            <p class="text-4xl font-black text-primary-500">#{{ myRank ?? '—' }}</p>
          </div>
          <div class="h-10 w-px bg-slate-100 dark:bg-slate-800 hidden sm:block"></div>
          <div class="text-center">
            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Best Score</p>
            <p class="text-4xl font-black text-slate-800 dark:text-white">{{ myEntry.best_score }}</p>
          </div>
          <div class="text-center">
            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Best WPM</p>
            <p class="text-4xl font-black text-cyan-500">{{ myEntry.best_wpm }}</p>
          </div>
          <div class="text-center">
            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">ลองแล้ว</p>
            <p class="text-4xl font-black text-slate-500">{{ myEntry.attempts }}x</p>
          </div>
        </div>
        <!-- Claim Prize -->
        <button v-if="tournament.status === 'finished' && !myEntry.prize_claimed && (myRank || 0) > 0"
          @click="claimPrize"
          class="px-10 py-5 bg-yellow-500 hover:bg-yellow-400 text-slate-900 font-black rounded-2xl transition-all hover:scale-105 shadow-lg shadow-yellow-500/20">
          🎁 รับรางวัล
        </button>
        <div v-else-if="myEntry.prize_claimed" class="flex items-center gap-2 text-green-500 font-black">
          <Icon icon="heroicons:check-circle" class="text-2xl" />
          <span>รับรางวัลแล้ว</span>
        </div>
      </div>

      <!-- Last Result -->
      <div v-if="lastResult" class="bg-green-50 dark:bg-green-900/10 border-2 border-green-200 dark:border-green-800/50 rounded-2xl p-6 flex items-center gap-6 animate-in slide-in-from-top duration-500">
        <span class="text-4xl">{{ lastResult.is_new_best ? '🎉' : '👍' }}</span>
        <div class="flex-1">
          <p class="font-black text-green-800 dark:text-green-300 text-lg">{{ lastResult.is_new_best ? 'New Personal Best!' : 'ผลการเล่นของคุณ' }}</p>
          <p class="text-green-700 dark:text-green-400 font-bold">{{ lastResult.wpm }} WPM · {{ lastResult.score }} pts · +{{ lastResult.xp_earned }} XP</p>
        </div>
        <button @click="lastResult = null" class="text-green-400 hover:text-green-600">
          <Icon icon="heroicons:x-mark" class="text-2xl" />
        </button>
      </div>

      <!-- Game + Leaderboard grid -->
      <div class="grid lg:grid-cols-3 gap-8 items-start">
        <!-- Play Button / Game -->
        <div class="lg:col-span-1 space-y-4 sticky top-8">
          <div v-if="!playing" class="bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200 dark:border-slate-800 space-y-6 text-center shadow-sm">
            <div class="space-y-4">
              <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl space-y-2">
                <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">การตั้งค่า</p>
                <div class="flex flex-col gap-1">
                  <p class="text-slate-800 dark:text-slate-200 font-black capitalize">{{ tournament.game_mode.replace('_',' ') }}</p>
                  <p class="text-slate-600 dark:text-slate-400 font-bold text-sm">{{ tournament.language.toUpperCase() }} · {{ tournament.difficulty.toUpperCase() }}</p>
                  <p class="text-primary-500 font-black">{{ tournament.time_limit }} SECONDS</p>
                </div>
              </div>
              <p class="text-slate-400 text-xs font-bold italic">เล่นได้ไม่จำกัดครั้ง ระบบจะเก็บคะแนนที่ดีที่สุด!</p>
            </div>
            
            <button
              v-if="tournament.status === 'active'"
              @click="startPlaying"
              :disabled="submitting"
              class="w-full py-6 bg-primary-600 hover:bg-primary-700 text-white font-black text-2xl rounded-2xl shadow-xl shadow-primary-500/20 transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-50">
              {{ submitting ? 'SUBMITTING...' : (myEntry ? '🔄 TRY AGAIN' : '🚀 START RACE') }}
            </button>
            
            <div v-else class="py-10 bg-slate-50 dark:bg-slate-800/30 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-800">
              <Icon icon="heroicons:clock" class="text-4xl text-slate-300 mb-2" />
              <p class="text-slate-400 font-black">
                {{ tournament.status === 'upcoming' ? 'COMING SOON' : 'TOURNAMENT ENDED' }}
              </p>
            </div>
            
            <NuxtLink to="/play/games/typing/tournaments" class="block text-slate-400 hover:text-primary-500 font-bold text-sm transition-colors">
              ← กลับไปดูรายการอื่น
            </NuxtLink>
          </div>

          <!-- Playing State -->
          <div v-else class="animate-in zoom-in duration-300">
            <component
              :is="getModeComponent(tournament.game_mode)"
              :lang="tournament.language === 'any' ? 'en' : tournament.language"
              :difficulty="tournament.difficulty === 'any' ? 'normal' : tournament.difficulty"
              :time-limit="tournament.time_limit"
              @finished="onGameFinished"
            />
            <button @click="playing = false" class="mt-4 w-full py-3 text-slate-400 hover:text-red-500 font-bold transition-colors">
              ยกเลิกการเล่น
            </button>
          </div>
        </div>

        <!-- Leaderboard -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200 dark:border-slate-800 shadow-sm">
          <div class="flex items-center justify-between mb-8">
            <h2 class="font-black text-slate-800 dark:text-white uppercase tracking-widest text-sm flex items-center gap-2">
              <Icon icon="heroicons:trophy" class="text-xl text-yellow-500" />
              LEADERBOARD — {{ tournament.total_entries }} PLAYERS
            </h2>
          </div>

          <div v-if="leaderboard.length === 0" class="text-center py-20 text-slate-400">
            <Icon icon="heroicons:user-group" class="text-5xl mb-4 opacity-20" />
            <p class="font-bold">ยังไม่มีผู้เข้าแข่งขัน</p>
            <p class="text-sm">เป็นคนแรกที่เริ่มการแข่งขันนี้เลย!</p>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="entry in leaderboard" :key="entry.user_id"
              class="flex items-center gap-5 p-4 rounded-2xl transition-all border-2"
              :class="entry.user_id === auth.user?.id
                ? 'bg-primary-50/50 dark:bg-primary-900/10 border-primary-200 dark:border-primary-800 scale-[1.01] shadow-md'
                : 'hover:bg-slate-50 dark:hover:bg-slate-800/50 border-transparent hover:border-slate-100 dark:hover:border-slate-800'"
            >
              <div class="w-12 text-center font-black text-xl flex-shrink-0">
                <span v-if="entry.rank === 1" class="text-3xl">🥇</span>
                <span v-else-if="entry.rank === 2" class="text-3xl">🥈</span>
                <span v-else-if="entry.rank === 3" class="text-3xl">🥉</span>
                <span v-else class="text-slate-400">#{{ entry.rank }}</span>
              </div>
              
              <div class="w-12 h-12 rounded-2xl bg-slate-200 dark:bg-slate-700 overflow-hidden flex-shrink-0 border-2 border-white dark:border-slate-800 shadow-sm">
                <img v-if="entry.avatar" :src="entry.avatar" class="w-full h-full object-cover" />
                <div v-else class="flex items-center justify-center h-full text-lg font-black text-slate-400">
                  {{ entry.name?.charAt(0) }}
                </div>
              </div>
              
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <span class="font-black text-slate-800 dark:text-white truncate">{{ entry.name }}</span>
                  <span v-if="entry.user_id === auth.user?.id" class="px-2 py-0.5 bg-primary-500 text-[10px] text-white font-black rounded uppercase">YOU</span>
                </div>
                <p class="text-xs font-bold text-slate-500">{{ entry.best_wpm }} WPM · {{ entry.best_acc }}% ACCURACY · {{ entry.attempts }}x ATTEMPTS</p>
              </div>
              
              <div class="text-right flex-shrink-0">
                <p class="font-black text-2xl text-slate-900 dark:text-white tabular-nums leading-none">{{ entry.score }}</p>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">POINTS</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <div v-else class="text-center py-40 bg-white dark:bg-slate-900 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-800">
      <Icon icon="heroicons:exclamation-triangle" class="text-5xl text-red-500 mb-4" />
      <h2 class="text-2xl font-black text-slate-800 dark:text-white">ไม่พบข้อมูลการแข่งขัน</h2>
      <p class="text-slate-500 mb-6">ลิงก์อาจไม่ถูกต้อง หรือการแข่งขันถูกลบออกไปแล้ว</p>
      <NuxtLink to="/play/games/typing/tournaments" class="px-8 py-4 bg-primary-600 text-white font-black rounded-2xl shadow-lg">
        กลับสู่รายการทั้งหมด
      </NuxtLink>
    </div>
  </div>
</template>

<style scoped>
.animate-in {
  animation: slideIn 0.5s ease-out;
}
@keyframes slideIn {
  from { transform: translateY(-20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
</style>
