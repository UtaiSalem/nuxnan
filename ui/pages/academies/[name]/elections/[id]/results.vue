<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed, onMounted, onUnmounted, ref } from 'vue'

definePageMeta({ middleware: ['auth'] })

const route = useRoute()
const { t } = useI18n()
const api = useApi()
const config = useRuntimeConfig()
const academyName = computed(() => route.params.name as string)
const academyId = ref<number | null>(null)
const electionId = computed(() => Number(route.params.id))
const { getElection, getResults, getTurnout } = useElections()
const { can, fetchMyRole } = useAcademyRole(academyId)

const election = ref<any>(null)
const results = ref<any[]>([])
const turnout = ref<any>(null)
const isPublished = ref(false)
const loading = ref(true)

let timer: ReturnType<typeof setInterval> | undefined

const unwrap = (response: any) => {
  if (!response || typeof response !== 'object' || !('data' in response)) return response
  const payload = (response as any).data
  if (payload && typeof payload === 'object' && !Array.isArray(payload) && 'data' in payload) {
    return (payload as any).data
  }
  return payload
}
const imageUrl = (path: string | null) => {
  if (!path) return null
  if (path.startsWith('http')) return path
  return `${config.public.apiBase}${path.startsWith('/') ? '' : '/storage/'}${path}`
}
const handleError = (e: any) => {
  return null
}

const fetchData = async () => {
  if (!academyId.value || !electionId.value) return
  
  try {
    const elRes = await getElection(academyId.value, electionId.value)
    election.value = unwrap(elRes)
    
    if (election.value.status === 'published') {
      try {
        const resData = await getResults(academyId.value, electionId.value)
        const allResults = unwrap(resData) || []
        // Sort: votes desc, abstain (party_id null) at the end
        results.value = [...allResults].sort((a, b) => {
          if (a.party_id === null) return 1
          if (b.party_id === null) return -1
          return (b.votes || 0) - (a.votes || 0)
        })
        isPublished.value = true
      } catch (e: any) {
        // If 404, not published yet
        isPublished.value = false
      }
    } else {
      isPublished.value = false
    }
    
    // Always get turnout
    const tnRes = await getTurnout(academyId.value, electionId.value)
    turnout.value = unwrap(tnRes)
    
  } catch (e) {
    handleError(e)
  }
}

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (!response?.success) return
    academyId.value = response.academy.id
    await fetchMyRole()
    if (!can('elections.view')) return navigateTo(`/academies/${academyName.value}`)
    
    await fetchData()
    
    if (election.value?.status === 'voting') {
      timer = setInterval(async () => {
        if (election.value?.status !== 'voting') {
          clearInterval(timer)
          return
        }
        await fetchData()
      }, 15000)
    }
    
  } catch (e) {
    handleError(e)
  } finally {
    loading.value = false
  }
})

onUnmounted(() => {
  clearInterval(timer)
})

const getStatusDisplay = (status: string) => {
  const map: Record<string, { label: string, class: string }> = {
    nomination: { 
      label: 'เปิดรับสมัคร', 
      class: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' 
    },
    campaign: { 
      label: 'ช่วงหาเสียง', 
      class: 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-200' 
    },
    voting: { 
      label: 'กำลังลงคะแนน', 
      class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200' 
    },
    closed: { 
      label: 'ปิดหีบแล้ว รอประกาศผล', 
      class: 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-200' 
    },
    published: { 
      label: 'ประกาศผลแล้ว', 
      class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200' 
    },
    cancelled: { 
      label: 'ยกเลิก', 
      class: 'bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-200' 
    },
  }
  return map[status] || { 
    label: status, 
    class: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' 
  }
}

const getLevelDisplay = (level: number | null) => {
  if (level === 1) return 'ประถม'
  if (level === 2) return 'มัธยม'
  return 'ทั้งโรงเรียน'
}

const winners = computed(() => {
  if (!isPublished.value) return []
  return results.value.filter(r => r.is_winner)
})

const maxVotes = computed(() => {
  if (!results.value.length) return 1
  return Math.max(...results.value.map(r => r.votes || 0), 1)
})

const totalVotesCast = computed(() => {
  return results.value.reduce((sum, r) => sum + (r.votes || 0), 0)
})
</script>

<template>
  <main class="min-h-screen bg-slate-50 px-0 py-3 sm:px-3 sm:p-6 text-slate-900 dark:bg-slate-950 dark:text-white">
    <div v-if="loading" class="flex min-h-[44px] items-center justify-center py-10">
      <Icon icon="lucide:loader-2" class="animate-spin text-3xl text-slate-500" />
    </div>
    
    <div v-else-if="election" class="mx-auto max-w-4xl">
      <!-- ส่วนหัว -->
      <header class="mb-6 rounded-2xl bg-white p-4 shadow-sm dark:bg-slate-900 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <h1 class="min-w-0 flex-1 break-words text-xl font-bold sm:text-2xl">{{ election.title }}</h1>
          <span 
            :class="getStatusDisplay(election.status).class" 
            class="shrink-0 whitespace-nowrap rounded-full px-3 py-1 text-sm font-semibold"
          >
            {{ getStatusDisplay(election.status).label }}
          </span>
        </div>
        <div class="mt-3 flex flex-wrap gap-2 text-sm text-slate-600 dark:text-slate-400">
          <span class="rounded bg-slate-100 px-2 py-1 dark:bg-slate-800">
            ปีการศึกษา {{ election.academic_year?.year ?? '-' }}
          </span>
          <span class="rounded bg-slate-100 px-2 py-1 dark:bg-slate-800">
            ระดับ {{ getLevelDisplay(election.education_level) }}
          </span>
        </div>
      </header>
      
      <!-- ประกาศผลแล้ว -->
      <template v-if="isPublished">
        <!-- ผู้ชนะ -->
        <section v-if="winners.length" class="mb-6">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div 
              v-for="winner in winners" 
              :key="winner.party_id" 
              class="flex flex-col items-center rounded-2xl bg-gradient-to-br from-amber-50 to-amber-100 p-6 text-center shadow-sm dark:from-amber-900/40 dark:to-amber-900/10"
            >
              <Icon icon="lucide:crown" class="mb-2 text-4xl text-amber-500" />
              <div v-if="winner.party" class="flex flex-col items-center">
                <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-white text-xl font-bold text-amber-600 shadow-sm dark:bg-slate-800">
                  {{ winner.party.number }}
                </span>
                <img 
                  v-if="winner.party.logo_path" 
                  :src="imageUrl(winner.party.logo_path) || undefined" 
                  class="mb-3 h-20 w-20 rounded-full object-cover shadow-sm" 
                />
                <h3 class="break-words text-lg font-bold sm:text-xl">{{ winner.party.name }}</h3>
              </div>
              <p class="mt-2 text-2xl font-bold text-amber-700 dark:text-amber-400">
                {{ winner.votes }} <span class="text-sm font-normal">คะแนน</span>
              </p>
            </div>
          </div>
        </section>
        
        <!-- ผลคะแนนทั้งหมด -->
        <section class="mb-6 rounded-2xl bg-white p-4 shadow-sm dark:bg-slate-900 sm:p-6">
          <h2 class="mb-4 text-lg font-bold sm:text-xl">ผลคะแนน</h2>
          <div class="flex flex-col gap-4">
            <div 
              v-for="row in results" 
              :key="row.party_id || 'abstain'" 
              class="flex flex-col gap-3 rounded-xl border border-slate-100 p-4 dark:border-slate-800 sm:flex-row sm:items-center"
            >
              
              <!-- ไม่ประสงค์ลงคะแนน -->
              <template v-if="row.party_id === null">
                <div class="flex min-w-0 flex-1 items-center gap-4">
                  <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                    <Icon icon="lucide:ban" class="text-xl text-slate-400" />
                  </div>
                  <div class="min-w-0 flex-1">
                    <h3 class="break-words font-bold">ไม่ประสงค์ลงคะแนน</h3>
                  </div>
                </div>
              </template>
              
              <!-- พรรค -->
              <template v-else>
                <div class="flex shrink-0 items-center justify-center whitespace-nowrap text-lg font-bold text-slate-400 sm:w-12">
                  <span v-if="row.rank">#{{ row.rank }}</span>
                </div>
                <div class="flex min-w-0 flex-1 items-center gap-3">
                  <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-100 font-bold text-primary-700 dark:bg-primary-900/50 dark:text-primary-300">
                    {{ row.party?.number }}
                  </span>
                  <img 
                    v-if="row.party?.logo_path" 
                    :src="imageUrl(row.party?.logo_path) || undefined" 
                    class="h-10 w-10 shrink-0 rounded-lg object-cover" 
                  />
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                      <h3 class="min-w-0 break-words font-bold">{{ row.party?.name }}</h3>
                      <span 
                        v-if="row.is_winner" 
                        class="shrink-0 whitespace-nowrap rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-900 dark:text-amber-300"
                      >
                        ผู้ชนะ
                      </span>
                    </div>
                    <p v-if="row.party?.slogan" class="truncate text-xs text-slate-500">{{ row.party.slogan }}</p>
                  </div>
                </div>
              </template>
              
              <div class="flex w-full shrink-0 flex-col gap-1 sm:w-1/3">
                <div class="flex items-center justify-between text-sm">
                  <span class="font-bold">{{ row.votes }} คะแนน</span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                  <div 
                    class="h-full rounded-full bg-primary-500" 
                    :style="{ width: `${Math.max(0, Math.min(100, (row.votes / maxVotes) * 100))}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="mt-6 flex flex-col items-center justify-center gap-2 rounded-xl bg-slate-50 p-4 text-center dark:bg-slate-800/50 sm:flex-row sm:gap-6">
            <div class="flex flex-col">
              <span class="text-sm text-slate-500">บัตรทั้งหมด</span>
              <span class="text-lg font-bold">{{ totalVotesCast }}</span>
            </div>
            <div v-if="turnout" class="flex flex-col sm:border-l sm:border-slate-200 sm:pl-6 dark:sm:border-slate-700">
              <span class="text-sm text-slate-500">ผู้มาใช้สิทธิ์ทั้งหมด</span>
              <span class="text-lg font-bold">
                {{ turnout.voted }} / {{ turnout.total || 0 }} ({{ turnout.percentage || 0 }}%)
              </span>
            </div>
          </div>
        </section>
      </template>
      
      <!-- ยังไม่ประกาศผล -->
      <template v-else>
        <!-- status === 'closed' -->
        <div v-if="election.status === 'closed'" class="mb-6 rounded-2xl bg-white p-4 sm:p-8 text-center shadow-sm dark:bg-slate-900">
          <Icon icon="lucide:lock" class="mx-auto mb-4 text-5xl text-amber-500" />
          <h2 class="text-xl font-bold">ปิดหีบแล้ว อยู่ระหว่างรอประกาศผลอย่างเป็นทางการ</h2>
          
          <div v-if="turnout" class="mt-6 flex flex-col justify-center gap-4 border-t border-slate-100 pt-6 dark:border-slate-800 sm:flex-row sm:gap-8">
            <div class="flex flex-col">
              <span class="text-2xl font-bold">{{ turnout.voted }}</span>
              <span class="text-sm text-slate-500">ผู้มาใช้สิทธิ์</span>
            </div>
            <div class="flex flex-col border-t border-slate-100 pt-4 dark:border-slate-800 sm:border-l sm:border-t-0 sm:pl-8 sm:pt-0">
              <span class="text-2xl font-bold">{{ turnout.total || 0 }}</span>
              <span class="text-sm text-slate-500">ผู้มีสิทธิ์ทั้งหมด</span>
            </div>
            <div class="flex flex-col border-t border-slate-100 pt-4 dark:border-slate-800 sm:border-l sm:border-t-0 sm:pl-8 sm:pt-0">
              <span class="text-2xl font-bold">{{ turnout.percentage || 0 }}%</span>
              <span class="text-sm text-slate-500">คิดเป็น</span>
            </div>
          </div>
        </div>
        
        <!-- status === 'voting' -->
        <div v-else-if="election.status === 'voting'" class="flex flex-col gap-6">
          <div class="rounded-2xl bg-white p-4 sm:p-6 shadow-sm dark:bg-slate-900">
            <div class="mb-4 flex items-center gap-2">
              <Icon icon="lucide:activity" class="animate-pulse text-xl text-primary-500" />
              <h2 class="text-lg font-bold">ความคืบหน้าสด</h2>
            </div>
            
            <div v-if="turnout">
              <div class="mb-6 grid grid-cols-1 gap-4 text-center sm:grid-cols-3">
                <div class="flex flex-col rounded-xl bg-slate-50 p-4 dark:bg-slate-800/50">
                  <span class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ turnout.voted }}</span>
                  <span class="text-sm text-slate-500">ผู้มาใช้สิทธิ์</span>
                </div>
                <div class="flex flex-col rounded-xl bg-slate-50 p-4 dark:bg-slate-800/50">
                  <span class="text-3xl font-bold">{{ turnout.total || 0 }}</span>
                  <span class="text-sm text-slate-500">ผู้มีสิทธิ์ทั้งหมด</span>
                </div>
                <div class="flex flex-col rounded-xl bg-slate-50 p-4 dark:bg-slate-800/50">
                  <span class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ turnout.percentage || 0 }}%</span>
                  <span class="text-sm text-slate-500">เปอร์เซ็นต์</span>
                </div>
              </div>
              
              <div class="mb-6">
                <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                  <div 
                    class="h-full rounded-full bg-emerald-500 transition-all duration-1000" 
                    :style="{ width: `${Number(turnout.percentage) || 0}%` }"
                  ></div>
                </div>
              </div>
              
              <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div v-if="turnout.by_grade_level?.length" class="flex flex-col gap-2">
                  <h3 class="font-bold">แบ่งตามระดับชั้น</h3>
                  <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                      <thead class="bg-slate-50 text-slate-500 dark:bg-slate-800">
                        <tr>
                          <th class="whitespace-nowrap px-4 py-2 font-medium">ระดับชั้น</th>
                          <th class="whitespace-nowrap px-4 py-2 text-right font-medium">ผู้มาใช้สิทธิ์</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="(item, i) in turnout.by_grade_level" :key="i">
                          <td class="whitespace-nowrap px-4 py-2">{{ item.grade_level }}</td>
                          <td class="whitespace-nowrap px-4 py-2 text-right">{{ item.voted }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                
                <div v-if="turnout.by_station?.length" class="flex flex-col gap-2">
                  <h3 class="font-bold">แบ่งตามหน่วยเลือกตั้ง</h3>
                  <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                      <thead class="bg-slate-50 text-slate-500 dark:bg-slate-800">
                        <tr>
                          <th class="whitespace-nowrap px-4 py-2 font-medium">หน่วยเลือกตั้ง</th>
                          <th class="whitespace-nowrap px-4 py-2 text-right font-medium">ผู้มาใช้สิทธิ์</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="(item, i) in turnout.by_station" :key="i">
                          <td class="whitespace-nowrap px-4 py-2">{{ item.station_name }}</td>
                          <td class="whitespace-nowrap px-4 py-2 text-right">{{ item.voted }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- status อื่น ๆ (ไม่ published, ไม่ closed, ไม่ voting) -->
        <div v-else class="rounded-2xl bg-white p-4 sm:p-10 text-center shadow-sm dark:bg-slate-900">
          <Icon icon="lucide:clock" class="mx-auto mb-4 text-5xl text-slate-400" />
          <h2 class="text-xl font-bold">ยังไม่ประกาศผลการเลือกตั้ง</h2>
        </div>
      </template>
    </div>
  </main>
</template>
