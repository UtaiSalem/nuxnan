<script setup lang="ts">
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main',
})

const api      = useApi()
const store    = useTypingStore()
const router   = useRouter()

const challenge = ref<any>(null)
const completed = ref(false)
const loading   = ref(true)
const error     = ref<string | null>(null)

async function fetchChallenge() {
  loading.value = true
  error.value = null
  try {
    const res = await api.get('/typing/daily')
    if (res.success) {
      challenge.value = res.challenge
      completed.value = res.completed
    }
  } catch (e) {
    error.value = 'โหลดข้อมูลไม่สำเร็จ'
    console.error('Failed to fetch daily challenge', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchChallenge)

function startChallenge() {
  if (!challenge.value) return
  store.selectedMode = challenge.value.game_mode
  store.selectedLang = challenge.value.language
  store.selectedDifficulty = challenge.value.difficulty
  
  router.push('/play/games/typing/play?challenge=' + challenge.value.id)
}
</script>

<template>
  <div class="max-w-2xl mx-auto py-12 px-0 sm:px-4 space-y-8">
    <div class="text-center space-y-2">
      <h1 class="text-4xl font-black uppercase tracking-tighter text-slate-900 dark:text-white">📅 Daily Challenge</h1>
      <p class="text-slate-500 font-medium">ภารกิจประจำวัน — รีเซ็ตทุกเที่ยงคืน</p>
    </div>

    <div v-if="loading" class="flex flex-col items-center justify-center py-20 space-y-4">
      <Icon icon="eos-icons:loading" class="text-4xl text-primary-500" />
      <p class="text-slate-400 font-bold">กำลังโหลดภารกิจ...</p>
    </div>

    <div v-else-if="error" class="flex flex-col items-center justify-center min-h-64 gap-4 text-slate-400">
      <Icon icon="heroicons:exclamation-circle" class="text-4xl text-red-400" />
      <p>{{ error }}</p>
      <button @click="fetchChallenge()" class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors font-bold">
        ลองใหม่
      </button>
    </div>

    <div v-else-if="!challenge" class="bg-slate-100 dark:bg-slate-900 rounded-3xl p-12 text-center space-y-4">
      <Icon icon="heroicons:calendar-days" class="text-6xl text-slate-300 dark:text-slate-700" />
      <p class="text-slate-500 font-bold text-xl">ไม่มีภารกิจสำหรับวันนี้</p>
      <p class="text-slate-400">ลองกลับมาตรวจสอบใหม่ภายหลังนะ!</p>
      <NuxtLink to="/play/games/typing" class="inline-block mt-4 text-primary-500 font-bold hover:underline">
        กลับสู่หน้าหลัก
      </NuxtLink>
    </div>

    <template v-else>
      <div class="bg-gradient-to-br from-primary-600 to-blue-700 rounded-[2.5rem] p-8 md:p-10 shadow-2xl shadow-primary-500/20 space-y-8 relative overflow-hidden group">
        <!-- Decoration -->
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-1000"></div>
        
        <div class="flex items-center justify-between relative">
          <div>
            <p class="text-primary-200 text-xs font-black uppercase tracking-widest mb-1">Today's Mission</p>
            <h2 class="text-3xl font-black text-white">{{ new Date().toLocaleDateString('th-TH', { day: 'numeric', month: 'long', year: 'numeric' }) }}</h2>
          </div>
          <div v-if="completed" class="bg-green-500/20 backdrop-blur-md border border-green-500/30 rounded-2xl px-5 py-3 flex items-center gap-2">
            <Icon icon="heroicons:check-circle-solid" class="text-green-400 text-xl" />
            <p class="text-white font-black uppercase tracking-wider text-sm">Completed</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 relative">
          <div class="bg-white/10 backdrop-blur-md rounded-2xl p-5 border border-white/5 text-center">
            <p class="text-white/60 text-[10px] font-black uppercase tracking-widest mb-1">Game Mode</p>
            <p class="text-white font-black text-lg capitalize">{{ challenge.game_mode.replace('_', ' ') }}</p>
          </div>
          <div class="bg-white/10 backdrop-blur-md rounded-2xl p-5 border border-white/5 text-center">
            <p class="text-white/60 text-[10px] font-black uppercase tracking-widest mb-1">Target WPM</p>
            <p class="text-white font-black text-lg">{{ challenge.target_wpm }}+</p>
          </div>
          <div class="bg-white/10 backdrop-blur-md rounded-2xl p-5 border border-white/5 text-center">
            <p class="text-white/60 text-[10px] font-black uppercase tracking-widest mb-1">Target Acc</p>
            <p class="text-white font-black text-lg">{{ challenge.target_acc }}%+</p>
          </div>
        </div>

        <div class="bg-yellow-400 rounded-3xl p-6 flex items-center justify-between shadow-xl shadow-yellow-900/10">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-yellow-500 flex items-center justify-center">
              <Icon icon="heroicons:gift" class="text-white text-2xl" />
            </div>
            <div>
              <p class="text-yellow-900/60 text-[10px] font-black uppercase tracking-widest">Rewards</p>
              <p class="text-yellow-900 font-black text-xl">+{{ challenge.xp_reward }} XP & +{{ challenge.pp_reward }} PP</p>
            </div>
          </div>
          <Icon icon="heroicons:chevron-right" class="text-yellow-900/30 text-2xl" />
        </div>
      </div>

      <div class="space-y-4">
        <button
          v-if="!completed"
          @click="startChallenge"
          class="w-full py-6 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black text-2xl rounded-3xl shadow-2xl hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-3"
        >
          <Icon icon="heroicons:play-solid" class="text-2xl" />
          START MISSION
        </button>
        <NuxtLink
          to="/play/games/typing"
          class="w-full py-4 bg-white dark:bg-slate-900 text-slate-500 font-bold rounded-2xl border-2 border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-center block"
        >
          BACK TO LOBBY
        </NuxtLink>
      </div>

      <div class="bg-blue-50 dark:bg-blue-900/20 rounded-3xl p-6 border border-blue-100 dark:border-blue-900/30">
        <div class="flex gap-4">
          <Icon icon="heroicons:information-circle" class="text-blue-500 text-2xl flex-shrink-0" />
          <div class="space-y-1">
            <p class="text-blue-900 dark:text-blue-200 font-bold">เกี่ยวกับ Daily Challenge</p>
            <p class="text-blue-800/60 dark:text-blue-300/60 text-sm leading-relaxed">
              Daily Challenge จะสุ่มโหมดและเงื่อนไขใหม่ทุกวัน หากทำสำเร็จคุณจะได้รับ XP และ Points พิเศษที่มากกว่าการเล่นปกติ!
            </p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
