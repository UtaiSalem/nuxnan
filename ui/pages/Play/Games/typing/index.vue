<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useTypingStore } from '~/stores/typing'
import type { GameMode } from '~/stores/typing'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'main',
})

const store = useTypingStore()
const authStore = useAuthStore()
const router = useRouter()

const modes = [
  { id: 'key_training',   name: 'Key Training',   icon: 'heroicons:cpu-chip',                  description: 'ฝึกจำตำแหน่งคีย์ทีละตัว สร้าง muscle memory', color: 'bg-green-600',   badge: 'Learn' },
  { id: 'word_typing',    name: 'Word Typing',    icon: 'heroicons:document-text',             description: 'ฝึกพิมพ์ทีละคำ ในจังหวะของตัวเอง',      color: 'bg-primary-500', badge: null },
  { id: 'time_attack',    name: 'Time Attack',    icon: 'heroicons:clock',                     description: 'พิมพ์ให้ได้มากที่สุดใน 60 วินาที',       color: 'bg-orange-500',  badge: 'Hot' },
  { id: 'sentence_typing',name: 'Sentences',      icon: 'heroicons:chat-bubble-bottom-center-text', description: 'ฝึกประโยคยาว เครื่องหมายวรรคตอน',  color: 'bg-blue-500',    badge: null },
  { id: 'monster_battle', name: 'Monster Battle', icon: 'heroicons:fire',                      description: 'โจมตีมอนสเตอร์ด้วยการพิมพ์!',             color: 'bg-red-500',     badge: 'New' },
  { id: 'falling_words',  name: 'Falling Words',  icon: 'heroicons:arrow-down-circle',         description: 'จับคำที่ตกลงมาก่อนถึงพื้น',              color: 'bg-purple-500',  badge: 'New' },
  // { id: 'letter_runner', name: 'Letter Runner', icon: 'heroicons:arrow-right-circle', description: 'วิ่งเก็บเหรียญ พิมพ์ตัวอักษรทำลายอุปสรรค!', color: 'bg-cyan-500', badge: 'New' },
]

const selectedMode = computed(() => modes.find(m => m.id === store.selectedMode) ?? modes[0])

function selectMode(modeId: GameMode) {
  store.selectedMode = modeId
}

function start() {
  router.push('/play/games/typing/play')
}
</script>

<template>
  <div class="max-w-6xl mx-auto py-12 px-0 sm:px-4 space-y-12">
    <NuxtLink to="/play/games" class="inline-flex items-center gap-1 min-h-[36px] px-4 sm:px-0 text-sm font-medium text-slate-500 hover:text-vikinger-purple dark:text-slate-400 dark:hover:text-vikinger-cyan transition-colors">
      ← กลับหน้าเกม
    </NuxtLink>
    <!-- Header -->
    <div class="text-center space-y-4">
      <h1 class="text-5xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">Typing Master</h1>
      <p class="text-xl text-slate-500">Master your typing speed and accuracy with fun challenges.</p>
      
      <div class="flex flex-wrap justify-center gap-4 mt-6">
        <NuxtLink to="/play/games/typing/race" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-2xl shadow-lg shadow-indigo-500/30 transition-all hover:scale-105 flex items-center gap-2">
          <span>🏁 CLASSROOM RACE</span>
          <span class="px-1.5 py-0.5 bg-white/20 text-[10px] rounded-md">PVP</span>
        </NuxtLink>
        <NuxtLink to="/play/games/typing/daily" class="px-8 py-3 bg-amber-500 hover:bg-amber-600 text-white font-black rounded-2xl shadow-lg shadow-amber-500/30 transition-all hover:scale-105 flex items-center gap-2">
          <span>📅 DAILY CHALLENGE</span>
        </NuxtLink>
        <NuxtLink to="/play/games/typing/tournaments" class="px-8 py-3 bg-rose-500 hover:bg-rose-600 text-white font-black rounded-2xl shadow-lg shadow-rose-500/30 transition-all hover:scale-105 flex items-center gap-2">
          <Icon icon="heroicons:trophy" />
          <span>🏆 TOURNAMENTS</span>
        </NuxtLink>
        <NuxtLink v-if="authStore.user" :to="`/play/games/typing/profile/${authStore.user.id}`" class="px-4 sm:px-8 py-3 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black rounded-2xl border-2 border-slate-200 dark:border-slate-700 transition-all hover:scale-105 flex items-center gap-2">
          <Icon icon="heroicons:user" />
          <span>MY PROFILE</span>
        </NuxtLink>
      </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6 lg:gap-8">
      <!-- Mode Selection -->
      <div class="lg:col-span-2 space-y-6">
        <h2 class="text-2xl font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider">Choose Game Mode</h2>
        <div class="grid sm:grid-cols-2 gap-4">
          <button
            v-for="mode in modes"
            :key="mode.id"
            @click="selectMode(mode.id as GameMode)"
            class="group relative p-6 text-left rounded-3xl border-2 transition-all duration-300 overflow-hidden"
            :class="store.selectedMode === mode.id 
              ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-900/10' 
              : 'border-slate-100 dark:border-slate-800 hover:border-slate-200 dark:hover:border-slate-700 bg-white dark:bg-slate-900'"
          >
            <div 
              class="absolute -right-4 -top-4 w-24 h-24 rounded-full opacity-10 group-hover:scale-150 transition-transform duration-500"
              :class="mode.color"
            ></div>
            
            <div class="relative space-y-4">
              <div class="w-12 h-12 flex items-center justify-center rounded-2xl" :class="mode.color">
                <Icon :icon="mode.icon" class="text-2xl text-white" />
              </div>
              <div>
                <h3 class="text-xl font-bold text-slate-800 dark:text-white">{{ mode.name }}</h3>
                <p class="text-sm text-slate-500">{{ mode.description }}</p>
                <span v-if="mode.badge" class="absolute top-4 right-4 px-2 py-0.5 bg-white/20 text-[10px] font-black uppercase tracking-widest text-white rounded-full backdrop-blur-md">
                  {{ mode.badge }}
                </span>
              </div>
            </div>
          </button>
        </div>
      </div>

      <!-- Selected mode summary + continue -->
      <div class="space-y-4 lg:space-y-6">
        <h2 class="text-2xl font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider">พร้อมเล่น</h2>

        <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-6 lg:p-7 border border-slate-100 dark:border-slate-800 shadow-sm space-y-5">
          <!-- Selected mode -->
          <div class="flex items-center gap-4">
            <div class="shrink-0 w-14 h-14 flex items-center justify-center rounded-2xl" :class="selectedMode.color">
              <Icon :icon="selectedMode.icon" class="text-3xl text-white" />
            </div>
            <div class="min-w-0">
              <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest">โหมดที่เลือก</p>
              <h3 class="text-lg font-bold text-slate-800 dark:text-white break-words">{{ selectedMode.name }}</h3>
            </div>
          </div>

          <p class="text-sm text-slate-500 break-words">{{ selectedMode.description }}</p>

          <!-- Hint: settings live on the next screen -->
          <div class="flex items-start gap-3 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 p-4">
            <Icon icon="heroicons:adjustments-horizontal" class="shrink-0 text-xl text-primary-500 mt-0.5" />
            <p class="min-w-0 text-sm text-slate-600 dark:text-slate-300 break-words">
              ตั้งค่า<span class="font-bold">ภาษา</span>และตัวเลือกของโหมดได้ในหน้าถัดไป ก่อนเริ่มเล่น
            </p>
          </div>

          <!-- Continue Button -->
          <button
            @click="start"
            class="w-full py-4 sm:py-5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black text-base sm:text-xl rounded-2xl shadow-xl hover:scale-[1.02] active:scale-[0.98] transition-all"
          >
            เลือก{{ selectedMode.name }} →
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
