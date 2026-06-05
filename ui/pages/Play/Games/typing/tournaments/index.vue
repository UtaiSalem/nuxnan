<script setup lang="ts">
definePageMeta({
  layout: false,
})

const { $api } = useNuxtApp()
const data     = ref<any>(null)
const loading  = ref(true)
const error    = ref<string | null>(null)

async function fetchTournaments() {
  loading.value = true
  error.value = null
  try {
    const res  = await $api.get('/typing/tournaments')
    data.value = res.data
  } catch (e) {
    error.value = 'โหลดข้อมูลไม่สำเร็จ'
    console.error('Failed to load tournaments:', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchTournaments)

function formatTimeLeft(endsAt: string): string {
  const diff = new Date(endsAt).getTime() - Date.now()
  if (diff <= 0) return 'จบแล้ว'
  
  const hours = Math.floor(diff / 3600000)
  const days  = Math.floor(hours / 24)
  
  if (days > 0) return `${days} วัน`
  return `${hours} ชั่วโมง`
}

function formatDate(d: string): string {
  return new Date(d).toLocaleDateString('th-TH', { day:'numeric', month:'short' })
}
</script>

<template>
  <NuxtLayout name="main">
    <div class="max-w-5xl mx-auto py-12 px-4 space-y-12">
      <div class="text-center">
        <h1 class="text-5xl font-black uppercase tracking-tighter">🏆 Tournaments</h1>
        <p class="text-slate-500 mt-2">แข่งขันกับผู้เล่นทั่วโลก รับรางวัล XP และ PP</p>
      </div>

      <div v-if="loading" class="text-center py-20 text-slate-400">
        <div class="animate-bounce mb-4 text-4xl">🏆</div>
        กำลังโหลดข้อมูลการแข่งขัน...
      </div>

      <div v-else-if="error" class="flex flex-col items-center justify-center min-h-64 gap-4 text-slate-400">
        <p>{{ error }}</p>
        <button @click="fetchTournaments()" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors font-bold">
          ลองใหม่
        </button>
      </div>

      <template v-else>
        <!-- Active Tournaments -->
        <section v-if="data?.active?.length">
          <div class="flex items-center gap-3 mb-6">
            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
            <h2 class="text-xl font-black uppercase tracking-wider text-slate-800 dark:text-white">กำลังดำเนินอยู่</h2>
          </div>
          <div class="grid md:grid-cols-2 gap-6">
            <NuxtLink
              v-for="t in data.active" :key="t.id"
              :to="`/play/games/typing/tournaments/${t.id}`"
              class="group relative overflow-hidden rounded-3xl p-8 border-2 transition-all hover:scale-[1.02] hover:shadow-2xl"
              :style="{ borderColor: t.banner_color + '40', background: t.banner_color + '10' }"
            >
              <!-- Glow BG -->
              <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity"
                :style="{ background: `radial-gradient(circle at 50% 0%, ${t.banner_color}20 0%, transparent 70%)` }">
              </div>

              <div class="relative space-y-4">
                <div class="flex items-start justify-between">
                  <span class="text-xs font-black uppercase tracking-widest px-3 py-1 rounded-full text-white"
                    :style="{ backgroundColor: t.banner_color }">
                    {{ t.type }}
                  </span>
                  <span class="text-xs text-slate-500 font-bold">
                    เหลือ {{ formatTimeLeft(t.ends_at) }}
                  </span>
                </div>

                <h3 class="text-2xl font-black text-slate-800 dark:text-white leading-tight">{{ t.name }}</h3>

                <div class="flex items-center gap-4 text-sm text-slate-500">
                  <span class="capitalize">{{ t.game_mode.replace('_',' ') }}</span>
                  <span>·</span>
                  <span class="capitalize">{{ t.difficulty }}</span>
                  <span>·</span>
                  <span>{{ t.entries_count }} ผู้เล่น</span>
                </div>

                <!-- Prizes preview -->
                <div class="flex gap-3">
                  <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl px-3 py-2 text-center">
                    <p class="text-yellow-600 text-xs font-bold">🥇 อันดับ 1</p>
                    <p class="text-yellow-700 font-black text-sm">+{{ t.prize_1st_xp }} XP</p>
                  </div>
                  <div class="bg-slate-50 dark:bg-slate-800 rounded-xl px-3 py-2 text-center">
                    <p class="text-slate-500 text-xs font-bold">เข้าร่วม</p>
                    <p class="text-slate-700 dark:text-slate-300 font-black text-sm">+{{ t.prize_all_xp }} XP</p>
                  </div>
                  <div v-if="data.my_entries?.includes(t.id)"
                    class="bg-green-50 dark:bg-green-900/20 rounded-xl px-3 py-2 flex items-center">
                    <span class="text-green-600 font-black text-sm">✅ เข้าร่วมแล้ว</span>
                  </div>
                </div>
              </div>
            </NuxtLink>
          </div>
        </section>

        <!-- Upcoming -->
        <section v-if="data?.upcoming?.length">
          <h2 class="text-xl font-black uppercase tracking-wider text-slate-800 dark:text-white mb-6">กำลังจะมา</h2>
          <div class="grid md:grid-cols-3 gap-4">
            <div v-for="t in data.upcoming" :key="t.id"
              class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-100 dark:border-slate-800 space-y-3 opacity-75">
              <span class="text-xs font-black uppercase text-slate-400">{{ t.type }}</span>
              <h3 class="font-black text-slate-700 dark:text-slate-200">{{ t.name }}</h3>
              <p class="text-xs text-slate-500">เริ่ม {{ formatDate(t.starts_at) }}</p>
            </div>
          </div>
        </section>

        <!-- Finished -->
        <section v-if="data?.finished?.length">
          <h2 class="text-xl font-black uppercase tracking-wider text-slate-800 dark:text-white mb-6">ที่จบไปแล้ว</h2>
          <div class="grid md:grid-cols-3 gap-4">
            <NuxtLink v-for="t in data.finished" :key="t.id"
              :to="`/play/games/typing/tournaments/${t.id}`"
              class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-100 dark:border-slate-800 space-y-3 hover:border-primary-500 transition-colors">
              <span class="text-xs font-black uppercase text-slate-400">{{ t.type }}</span>
              <h3 class="font-black text-slate-700 dark:text-slate-200">{{ t.name }}</h3>
              <p class="text-xs text-slate-500">จบเมื่อ {{ formatDate(t.ends_at) }}</p>
            </NuxtLink>
          </div>
        </section>

        <div v-if="!data?.active?.length && !data?.upcoming?.length && !data?.finished?.length" class="text-center py-20 text-slate-500">
          ยังไม่มีรายการแข่งขันในขณะนี้
        </div>
      </template>
    </div>
  </NuxtLayout>
</template>
