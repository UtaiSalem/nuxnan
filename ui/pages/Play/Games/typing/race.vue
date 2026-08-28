<script setup lang="ts">
import { useClassroomRace } from '~/composables/useClassroomRace'
import { useTypingStore } from '~/stores/typing'
import { useAuthStore } from '~/stores/auth'
import RaceTrack from '~/components/games/typing/ui/RaceTrack.vue'
import WordTypingMode from '~/components/games/typing/modes/WordTypingMode.vue'
import TimeAttackMode from '~/components/games/typing/modes/TimeAttackMode.vue'
import SentenceTypingMode from '~/components/games/typing/modes/SentenceTypingMode.vue'
import { useTypingApi } from '~/composables/useTypingApi'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main',
})

const { 
  createRoom, joinRoom, startRace, raceStatus, room,
  participants, countdown, rankings, broadcastProgress, submitResult, leaveRoom 
} = useClassroomRace()

const store = useTypingStore()
const authStore = useAuthStore()
const { fetchWordsByIds, fetchSentencesByIds } = useTypingApi()

const joinCode = ref('')
const view = ref<'home' | 'lobby' | 'playing' | 'result' | 'countdown'>('home')
const isHost = computed(() => room.value?.host_user_id === authStore.user?.id)
const initialWords = ref<string[]>([])
const isLoadingContent = ref(false)
const isCreating = ref(false)
const isJoining = ref(false)
const errorMsg = ref('')

async function handleCreate() {
  isCreating.value = true
  errorMsg.value = ''
  try {
    await createRoom({
      game_mode: store.selectedMode,
      language: store.selectedLang,
      difficulty: store.selectedDifficulty,
      time_limit: store.selectedTimeLimit,
    })
    view.value = 'lobby'
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message ?? 'ไม่สามารถสร้างห้องได้ กรุณาลองใหม่'
  } finally {
    isCreating.value = false
  }
}

async function handleJoin() {
  if (!joinCode.value.trim()) return
  isJoining.value = true
  errorMsg.value = ''
  try {
    await joinRoom(joinCode.value.trim().toUpperCase())
    joinCode.value = ''
    view.value = 'lobby'
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message ?? 'รหัสห้องไม่ถูกต้อง หรือห้องเต็มแล้ว'
  } finally {
    isJoining.value = false
  }
}

// Watch raceStatus to change views
watch(raceStatus, async (s) => {
  if (s === 'countdown') {
    view.value = 'countdown'
    isLoadingContent.value = true
    // Fetch shared content before starting
    if (room.value?.content_ids) {
      if (room.value.game_mode === 'sentence_typing') {
        const sentences = await fetchSentencesByIds(room.value.content_ids)
        initialWords.value = sentences.map((s: any) => s.text)
      } else {
        const words = await fetchWordsByIds(room.value.content_ids)
        initialWords.value = words.map((w: any) => w.text)
      }
    }
    isLoadingContent.value = false
  }
  if (s === 'racing') view.value = 'playing'
  if (s === 'finished') view.value = 'result'
})

async function onFinished(result: any) {
  await submitResult(result)
}

function getPlayComponent() {
  if (room.value?.game_mode === 'time_attack') return TimeAttackMode
  if (room.value?.game_mode === 'sentence_typing') return SentenceTypingMode
  return WordTypingMode
}
</script>

<template>
  <div class="max-w-4xl mx-auto py-12 px-0 sm:px-4">
    <!-- Error Message -->
    <div v-if="errorMsg" class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl flex items-center gap-3 text-red-600 dark:text-red-400 animate-in fade-in slide-in-from-top-4">
      <Icon icon="heroicons:exclamation-circle" class="text-xl shrink-0" />
      <p class="font-bold">{{ errorMsg }}</p>
      <button @click="errorMsg = ''" class="ml-auto p-1 hover:bg-red-100 dark:hover:bg-red-800 rounded-lg transition-colors">
        <Icon icon="heroicons:x-mark" />
      </button>
    </div>

    <!-- HOME: Create or Join -->
    <div v-if="view === 'home'" class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <div class="text-center">
        <h1 class="text-5xl font-black uppercase tracking-tighter bg-gradient-to-r from-primary-600 to-indigo-600 bg-clip-text text-transparent">🏁 Classroom Race</h1>
        <p class="text-slate-500 mt-2 font-medium">แข่งพิมพ์กับเพื่อนแบบ Real-time</p>
      </div>
      <div class="grid md:grid-cols-2 gap-6">
        <!-- Create Room -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200 dark:border-slate-800 space-y-6 shadow-xl shadow-slate-200/50 dark:shadow-none transition-transform hover:scale-[1.02]">
          <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-2xl flex items-center justify-center text-primary-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
          </div>
          <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase">สร้างห้องแข่ง</h2>
          <div class="text-sm text-slate-500 space-y-1">
            <p>โหมด: <span class="font-bold text-slate-700 dark:text-slate-300">{{ store.selectedMode }}</span></p>
            <p>ภาษา: <span class="font-bold text-slate-700 dark:text-slate-300 uppercase">{{ store.selectedLang }}</span></p>
            <p>ระดับ: <span class="font-bold text-slate-700 dark:text-slate-300 capitalize">{{ store.selectedDifficulty }}</span></p>
          </div>
          <p class="text-xs text-slate-400">
            ต้องการเปลี่ยน? <NuxtLink to="/play/games/typing" class="text-primary-500 underline font-bold">กลับไปตั้งค่า</NuxtLink>
          </p>
          <button 
            @click="handleCreate" 
            :disabled="isCreating"
            class="w-full py-4 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-black text-lg rounded-2xl transition-all shadow-lg shadow-primary-500/30 flex items-center justify-center gap-2"
          >
            <Icon v-if="isCreating" icon="heroicons:arrow-path" class="animate-spin" />
            <span>{{ isCreating ? 'กำลังสร้างห้อง...' : '🚀 สร้างห้องใหม่' }}</span>
          </button>
        </div>
        
        <!-- Join Room -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200 dark:border-slate-800 space-y-6 shadow-xl shadow-slate-200/50 dark:shadow-none transition-transform hover:scale-[1.02]">
          <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center text-indigo-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
            </svg>
          </div>
          <h2 class="text-xl font-black text-slate-800 dark:text-white uppercase">เข้าร่วมห้อง</h2>
          <input
            v-model="joinCode"
            @keydown.enter="handleJoin"
            placeholder="รหัส 6 หลัก"
            maxlength="6"
            class="w-full py-4 px-6 text-center text-3xl font-black font-mono tracking-widest bg-slate-50 dark:bg-slate-800 rounded-2xl border-2 border-slate-200 dark:border-slate-700 focus:border-primary-500 focus:outline-none uppercase transition-colors"
          />
          <button 
            @click="handleJoin" 
            :disabled="isJoining || !joinCode"
            class="w-full py-4 bg-slate-900 dark:bg-white disabled:opacity-50 disabled:cursor-not-allowed text-white dark:text-slate-900 font-black text-lg rounded-2xl transition-all shadow-lg shadow-slate-400/20 flex items-center justify-center gap-2"
          >
            <Icon v-if="isJoining" icon="heroicons:arrow-path" class="animate-spin" />
            <span>{{ isJoining ? 'กำลังเข้าร่วม...' : '🎯 เข้าร่วม' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- LOBBY: Waiting for Players -->
    <div v-else-if="view === 'lobby'" class="space-y-8 animate-in fade-in zoom-in duration-500">
      <div class="text-center space-y-2">
        <p class="text-slate-500 text-sm font-bold uppercase tracking-widest">รหัสห้อง</p>
        <h1 class="text-8xl font-black font-mono tracking-widest text-primary-500 drop-shadow-sm">{{ room?.room_code }}</h1>
        <p class="text-slate-400 text-sm font-medium">แชร์รหัสนี้ให้เพื่อนในห้องเรียน</p>
      </div>
      
      <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-lg">
        <div class="flex items-center justify-between mb-6">
          <h2 class="font-black text-slate-600 dark:text-slate-400 uppercase text-xs tracking-widest">
            ผู้เข้าร่วม ({{ participants.length }} / {{ room?.max_players }})
          </h2>
          <div v-if="participants.length < 2" class="flex items-center gap-2 text-[10px] font-bold text-amber-500 animate-pulse">
             <span>รอผู้เล่นคนอื่น...</span>
          </div>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div v-for="p in participants" :key="p.user_id"
            class="flex items-center gap-4 py-3 px-4 rounded-2xl border transition-all"
            :class="p.user_id === room?.host_user_id 
              ? 'bg-primary-50 dark:bg-primary-900/10 border-primary-200 dark:border-primary-800' 
              : 'bg-slate-50 dark:bg-slate-800/50 border-transparent'">
            <div class="w-10 h-10 rounded-full bg-slate-300 dark:bg-slate-700 overflow-hidden flex-shrink-0 ring-2 ring-white dark:ring-slate-800">
              <img v-if="p.avatar" :src="p.avatar" class="w-full h-full object-cover" />
              <div v-else class="w-full h-full flex items-center justify-center font-black text-slate-500 bg-slate-200">
                {{ p.name?.charAt(0) }}
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-bold text-slate-800 dark:text-white truncate">{{ p.name }}</p>
              <p v-if="p.user_id === room?.host_user_id" class="text-[10px] font-black text-primary-500 uppercase">Host</p>
            </div>
            <div v-if="p.status !== 'left'" class="w-2 h-2 rounded-full bg-green-400 shadow-[0_0_8px_rgba(74,222,128,0.5)]"></div>
          </div>
        </div>
      </div>
      
      <div class="flex gap-4">
        <button @click="leaveRoom(); view='home'" class="px-6 py-4 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold rounded-2xl hover:bg-slate-200 transition-colors">
          ออก
        </button>
        <button v-if="isHost"
          @click="startRace"
          :disabled="participants.length < 2"
          class="flex-1 py-4 bg-green-500 hover:bg-green-600 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-black text-xl rounded-2xl transition-all hover:scale-[1.02] shadow-lg shadow-green-500/30">
          {{ participants.length < 2 ? 'รอผู้เล่นอย่างน้อย 2 คน...' : '🏁 เริ่มแข่ง!' }}
        </button>
        <div v-else class="flex-1 py-4 bg-slate-100 dark:bg-slate-800 text-slate-400 font-bold rounded-2xl text-center flex items-center justify-center gap-3">
          <div class="w-4 h-4 border-2 border-slate-300 border-t-primary-500 rounded-full animate-spin"></div>
          รอ Host เริ่มการแข่งขัน...
        </div>
      </div>
    </div>

    <!-- COUNTDOWN -->
    <div v-else-if="view === 'countdown'" class="flex flex-col items-center justify-center min-h-[60vh] animate-in zoom-in duration-300">
      <div class="text-center">
        <p class="text-slate-400 font-black uppercase tracking-[0.3em] mb-4">Get Ready!</p>
        <span class="text-[180px] font-black text-primary-500 leading-none select-none" style="animation: bounceIn 1s infinite">
          {{ countdown }}
        </span>
        <p v-if="isLoadingContent" class="mt-8 text-slate-400 italic">Loading content...</p>
      </div>
    </div>

    <!-- PLAYING -->
    <div v-else-if="view === 'playing'" class="space-y-6 animate-in fade-in duration-500">
      <!-- Race Progress Track -->
      <RaceTrack :participants="participants" :my-user-id="authStore.user?.id" />
      
      <!-- Game Component -->
      <component
        :is="getPlayComponent()"
        :lang="room?.language"
        :difficulty="room?.difficulty"
        :time-limit="room?.time_limit"
        :race-mode="true"
        :initial-words="initialWords"
        @finished="onFinished"
        @progress="broadcastProgress"
      />
    </div>

    <!-- RESULT -->
    <div v-else-if="view === 'result'" class="space-y-8 animate-in slide-in-from-top-4 duration-500">
      <div class="text-center">
        <div class="inline-block p-4 bg-yellow-100 dark:bg-yellow-900/20 rounded-full mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
          </svg>
        </div>
        <h1 class="text-4xl font-black uppercase tracking-tighter mb-2">🏆 Race Results</h1>
        <p class="text-slate-500">การแข่งขันสิ้นสุดลงแล้ว</p>
      </div>
      
      <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200 dark:border-slate-800 space-y-4 shadow-xl">
        <div v-for="(r, i) in rankings" :key="r.user_id"
          class="flex items-center gap-4 p-4 rounded-2xl border transition-all"
          :class="i === 0 
            ? 'bg-yellow-50 dark:bg-yellow-900/10 border-yellow-200 dark:border-yellow-800' 
            : 'bg-slate-50 dark:bg-slate-800/50 border-transparent'">
          <span class="text-3xl font-black w-12 text-center">{{ i===0?'🥇':i===1?'🥈':i===2?'🥉':`#${i+1}` }}</span>
          
          <div class="w-12 h-12 rounded-full overflow-hidden ring-2 ring-white dark:ring-slate-800">
            <img v-if="r.avatar" :src="r.avatar" class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex items-center justify-center font-black text-slate-500 bg-slate-200">
              {{ r.name?.charAt(0) }}
            </div>
          </div>

          <div class="flex-1 min-w-0">
            <p class="font-black text-slate-800 dark:text-white text-lg truncate">{{ r.name }}</p>
            <div class="flex gap-3 text-xs font-bold text-slate-500">
              <span>{{ r.wpm }} WPM</span>
              <span>•</span>
              <span>{{ r.accuracy }}% Accuracy</span>
            </div>
          </div>
          
          <div class="text-right">
            <span class="font-black text-2xl text-primary-500">{{ r.score }}</span>
            <p class="text-[10px] font-black text-slate-400 uppercase">Points</p>
          </div>
        </div>
      </div>
      
      <div class="flex gap-4">
        <button @click="view='home'; leaveRoom()" class="flex-1 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black rounded-2xl hover:opacity-90 transition-opacity">
          กลับหน้าหลัก
        </button>
        <NuxtLink to="/play/games/typing" class="flex-1 py-4 bg-primary-600 text-white font-black rounded-2xl text-center hover:bg-primary-700 transition-colors shadow-lg shadow-primary-500/30">
          เล่นเกมอื่น
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes bounceIn {
  0% { transform: scale(0.3); opacity: 0; }
  50% { transform: scale(1.05); opacity: 1; }
  70% { transform: scale(0.9); }
  100% { transform: scale(1); }
}
</style>
