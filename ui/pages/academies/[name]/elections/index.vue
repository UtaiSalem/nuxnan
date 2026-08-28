<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed, onMounted, ref } from 'vue'

definePageMeta({ middleware: ['auth'] })

const route = useRoute()
const { t } = useI18n()
const api = useApi()
const config = useRuntimeConfig()
const academyName = computed(() => route.params.name as string)
const academyId = ref<number | null>(null)

const { listElections } = useElections()
const { can, fetchMyRole } = useAcademyRole(academyId)

const elections = ref<any[]>([])
const loading = ref(true)

const unwrap = (r: any) => r?.data?.data ?? r?.data ?? r

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (!response?.success) return
    academyId.value = response.academy.id
    
    await fetchMyRole()
    if (!can('elections.view')) {
      return navigateTo(`/academies/${academyName.value}`)
    }
    
    const res = await listElections(academyId.value, { per_page: 50 })
    elections.value = unwrap(res) || []
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
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
</script>

<template>
  <main class="min-h-screen bg-slate-50 px-0 py-3 sm:px-3 sm:p-6 text-slate-900 dark:bg-slate-950 dark:text-white">
    <div class="mx-auto max-w-2xl">
      <h1 class="mb-6 text-2xl font-bold sm:text-3xl">การเลือกตั้ง</h1>
      
      <div v-if="loading" class="flex min-h-[44px] items-center justify-center p-3">
        <Icon icon="lucide:loader-2" class="animate-spin text-2xl text-slate-500" />
      </div>
      
      <div 
        v-else-if="elections.length === 0" 
        class="flex flex-col items-center justify-center rounded-2xl bg-white p-6 text-center shadow-sm dark:bg-slate-900 sm:p-10"
      >
        <Icon icon="lucide:box" class="mb-4 text-5xl text-slate-300 dark:text-slate-700" />
        <h2 class="text-lg font-bold sm:text-xl">ไม่มีการเลือกตั้ง</h2>
        <p class="mt-2 text-sm text-slate-500 sm:text-base">ขณะนี้ยังไม่มีการเลือกตั้งในระบบ</p>
      </div>
      
      <div v-else class="flex flex-col gap-4">
        <div 
          v-for="election in elections" 
          :key="election.id" 
          class="flex flex-col rounded-2xl bg-white p-4 shadow-sm dark:bg-slate-900 sm:p-6"
        >
          <div class="mb-3 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="min-w-0 flex-1 break-words text-lg font-bold sm:text-xl">{{ election.title }}</h2>
            <span 
              :class="getStatusDisplay(election.status).class" 
              class="shrink-0 whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold sm:text-sm"
            >
              {{ getStatusDisplay(election.status).label }}
            </span>
          </div>
          
          <div class="mb-4 flex flex-wrap gap-2 text-xs text-slate-600 dark:text-slate-400 sm:text-sm">
            <span class="rounded bg-slate-100 px-2 py-1 dark:bg-slate-800">
              ปีการศึกษา {{ election.academic_year?.year ?? '-' }}
            </span>
            <span class="rounded bg-slate-100 px-2 py-1 dark:bg-slate-800">
              ระดับ {{ getLevelDisplay(election.education_level) }}
            </span>
          </div>
          
          <div class="mb-4 grid grid-cols-3 gap-2 border-y border-slate-100 py-3 text-center dark:border-slate-800">
            <div class="flex flex-col gap-1">
              <span class="text-lg font-bold sm:text-xl">{{ election.approved_parties_count || 0 }}</span>
              <span class="text-xs text-slate-500 sm:text-sm">พรรค</span>
            </div>
            <div class="flex flex-col gap-1 border-l border-slate-100 dark:border-slate-800">
              <span class="text-lg font-bold sm:text-xl">{{ election.voters_count || 0 }}</span>
              <span class="text-xs text-slate-500 sm:text-sm">ผู้มีสิทธิ์</span>
            </div>
            <div class="flex flex-col gap-1 border-l border-slate-100 dark:border-slate-800">
              <span class="text-lg font-bold sm:text-xl">{{ election.receipts_cast_count || 0 }}</span>
              <span class="text-xs text-slate-500 sm:text-sm">ลงคะแนน</span>
            </div>
          </div>
          
          <div class="flex gap-2">
            <NuxtLink 
              v-if="election.status === 'nomination'" 
              :to="`/academies/${academyName}/elections/${election.id}/apply`" 
              class="flex min-h-[44px] flex-1 items-center justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white sm:text-base"
            >
              สมัครพรรค
            </NuxtLink>
            
            <NuxtLink 
              v-else-if="election.status === 'voting'" 
              :to="`/academies/${academyName}/elections/${election.id}/results`" 
              class="flex min-h-[44px] flex-1 items-center justify-center rounded-xl bg-primary-100 px-4 py-2 text-sm font-semibold text-primary-700 dark:bg-primary-900/30 dark:text-primary-300 sm:text-base"
            >
              ดูความคืบหน้า
            </NuxtLink>
            
            <NuxtLink 
              v-else-if="election.status === 'published'" 
              :to="`/academies/${academyName}/elections/${election.id}/results`" 
              class="flex min-h-[44px] flex-1 items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white sm:text-base"
            >
              ดูผลคะแนน
            </NuxtLink>
            
            <NuxtLink 
              v-else-if="election.status === 'campaign' || election.status === 'closed'" 
              :to="`/academies/${academyName}/elections/${election.id}/results`" 
              class="flex min-h-[44px] flex-1 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 sm:text-base"
            >
              รายละเอียด
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>
