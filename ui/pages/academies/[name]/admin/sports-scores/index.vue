<script setup lang="ts">
import { Icon } from '@iconify/vue'
/**
 * ⚠️ ต้อง import component เอง ห้ามพึ่ง auto-import ด้วยชื่อไฟล์
 * เรพนี้ไม่ได้ตั้ง `components.pathPrefix: false` → Nuxt จดทะเบียนชื่อเป็น
 * `AcademySportsStandingsBoard` (โฟลเดอร์ + ชื่อไฟล์) ไม่ใช่ `SportsStandingsBoard`
 * เขียนชื่อสั้นแล้ว Vue จะ render เป็น custom element เปล่า ๆ โดย **ไม่มี error ให้เห็น**
 * (พิสูจน์แล้วในเบราว์เซอร์ 2026-08-17 — เป็นบั๊กเดียวกับที่หน้า house-assignments โดนอยู่)
 */
import SportsStandingsBoard from '~/components/academy/sports/SportsStandingsBoard.vue'
import SportsDisciplineManager from '~/components/academy/sports/SportsDisciplineManager.vue'
import SportsScoreEntryForm from '~/components/academy/sports/SportsScoreEntryForm.vue'
import SportsScoreLog from '~/components/academy/sports/SportsScoreLog.vue'
import SportsMatchBoard from '~/components/academy/sports/SportsMatchBoard.vue'
import SportsPlacingPanel from '~/components/academy/sports/SportsPlacingPanel.vue'
import SportsAlbumBoard from '~/components/academy/sports/SportsAlbumBoard.vue'
import type { SportsEdition } from '~/composables/useHouseAssignments'
import type { SportsDiscipline, SportsScoreEntry, SportsHouseStanding } from '~/composables/useSportsScoring'

definePageMeta({ layout: 'main' })

const route = useRoute()
const api = useApi()
const houses = useHouseAssignments()
const scoring = useSportsScoring()

const academyName = computed(() => route.params.name as string)
const academy = ref<any>(null)
const academyId = ref<number | null>(null)

const { isAdmin, can, fetchMyRole } = useAcademyRole(academyId)

const editions = ref<SportsEdition[]>([])
const selectedEditionId = ref<number | null>(null)
const houseGroups = ref<any[]>([])

const standings = ref<SportsHouseStanding[]>([])
const entries = ref<SportsScoreEntry[]>([])
const disciplines = ref<SportsDiscipline[]>([])

const isLoading = ref(true)
const isWorking = ref(false)
const errorMessage = ref('')
const activeTab = ref<'standings' | 'matches' | 'placings' | 'disciplines' | 'award' | 'log' | 'albums'>('standings')

const canManage = computed(() => isAdmin.value || can('sports.manage'))

onMounted(async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyName.value}`)
    if (!res?.success) return
    academy.value = res.academy
    academyId.value = res.academy.id
    
    await fetchMyRole()
    
    // ด่านตรวจ: หน้านี้ "ดูได้" ด้วย sports.view — ไม่ใช่ manage อย่างเดียว
    if (!isAdmin.value && !can('sports.view') && !can('sports.manage')) {
      navigateTo(`/academies/${academyName.value}`)
      return
    }
    
    await loadInitialData()
  } finally {
    isLoading.value = false
  }
})

const loadInitialData = async () => {
  if (!academyId.value) return
  try {
    const [editionsRes, housesRes] = await Promise.all([
      houses.listEditions(academyId.value),
      houses.listHouses(academyId.value)
    ])
    
    editions.value = (editionsRes || []) as SportsEdition[]
    houseGroups.value = (housesRes as any)?.groups || []
    
    if (editions.value.length > 0) {
      const activeEdition = editions.value.find(e => e.status === 'active') || editions.value[0]
      selectedEditionId.value = activeEdition.id
      // This will trigger the watch and loadAll()
    }
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'ไม่สามารถโหลดข้อมูลเริ่มต้นได้'
  }
}

const loadAll = async () => {
  if (!academyId.value || !selectedEditionId.value) return
  isWorking.value = true
  errorMessage.value = ''
  try {
    const [standingsRes, entriesRes, disciplinesRes] = await Promise.all([
      scoring.listStandings(academyId.value, selectedEditionId.value),
      scoring.listEntries(academyId.value, selectedEditionId.value),
      scoring.listDisciplines(academyId.value, selectedEditionId.value)
    ])
    
    standings.value = (standingsRes || []) as SportsHouseStanding[]
    entries.value = (entriesRes || []) as SportsScoreEntry[]
    disciplines.value = (disciplinesRes || []) as SportsDiscipline[]
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'ไม่สามารถโหลดข้อมูลคะแนนได้'
  } finally {
    isWorking.value = false
  }
}

watch(selectedEditionId, (newVal) => {
  if (newVal) {
    loadAll()
  } else {
    standings.value = []
    entries.value = []
    disciplines.value = []
  }
})

const onRebuild = async () => {
  if (!academyId.value || !selectedEditionId.value) return
  isWorking.value = true
  errorMessage.value = ''
  try {
    const res = await scoring.rebuildStandings(academyId.value, selectedEditionId.value)
    standings.value = (res || []) as SportsHouseStanding[]
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'ไม่สามารถคำนวณคะแนนใหม่ได้'
  } finally {
    isWorking.value = false
  }
}

const selectedEdition = computed(() => 
  editions.value.find(e => e.id === selectedEditionId.value)
)

const editionHouseIds = computed(() => {
  if (!selectedEdition.value) return []
  return houses.editionHouseIds(selectedEdition.value)
})

const editionHouses = computed(() => editionHouseIds.value.map(id => {
  const g = houseGroups.value.find(h => Number(h.id) === Number(id))
  return { id: Number(id), name: g?.name ?? `#${id}`, color: g?.settings?.color || '#8b5cf6' }
}))

const editionStatusText = (status?: string) => {
  if (status === 'active') return 'กำลังใช้งาน'
  if (status === 'draft') return 'ร่าง'
  if (status === 'closed') return 'ปิดแล้ว'
  return status || ''
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <div v-if="isLoading" class="space-y-6 px-0 py-6 sm:px-6">
      <div class="h-40 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
      <div class="h-64 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="bg-gradient-to-br from-violet-600 via-purple-600 to-indigo-700 px-4 py-6 sm:px-6 sm:py-8">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-vikinger bg-white/20 flex items-center justify-center shadow-vikinger">
              <Icon icon="fluent:trophy-24-filled" class="w-8 h-8 text-yellow-300" />
            </div>
            <div>
              <h1 class="font-heading text-2xl font-bold text-white">คะแนนกีฬาสี</h1>
              <p class="text-purple-200 text-sm mt-0.5">คะแนนทุกแต้มมีที่มาย้อนดูได้ — {{ academy?.name }}</p>
            </div>
          </div>
          
          <NuxtLink
            :to="`/academies/${academyName}/admin/house-assignments`"
            class="flex items-center justify-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-vikinger transition-all text-sm font-medium border border-white/20"
          >
            <Icon icon="fluent:flag-24-regular" class="w-5 h-5" />
            ไปหน้าคณะสี (กีฬาสี)
          </NuxtLink>
        </div>
      </div>

      <div class="max-w-6xl mx-auto px-4 sm:px-6 space-y-6 pb-10">
        <!-- Edition Selector & Error -->
        <div v-if="errorMessage" class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-vikinger p-4 flex items-center gap-3">
          <Icon icon="fluent:error-circle-24-filled" class="w-5 h-5 text-rose-600 dark:text-rose-400" />
          <p class="text-sm text-rose-800 dark:text-rose-300">{{ errorMessage }}</p>
        </div>

        <div v-if="editions.length === 0" class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-4 sm:p-8 text-center">
          <Icon icon="fluent:flag-off-24-regular" class="w-16 h-16 text-slate-400 mx-auto mb-4" />
          <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">ยังไม่มีการสร้างงานกีฬาสี</h2>
          <p class="text-slate-500 dark:text-slate-400 mb-6">คุณต้องสร้าง "ครั้งที่จัด" และกำหนดคณะสีก่อน จึงจะสามารถจัดการคะแนนได้</p>
          <NuxtLink
            :to="`/academies/${academyName}/admin/house-assignments`"
            class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg transition-all"
          >
            <Icon icon="fluent:flag-24-filled" class="w-5 h-5" />
            ไปสร้างงานกีฬาสี
          </NuxtLink>
        </div>

        <template v-else>
          <div class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-5">
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">เลือกครั้งที่จัด</label>
            <select
              v-model="selectedEditionId"
              class="w-full md:w-96 px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
            >
              <option v-for="ed in editions" :key="ed.id" :value="ed.id">
                {{ ed.name }} · ครั้งที่ {{ ed.sequence }} ({{ editionStatusText(ed.status) }})
              </option>
            </select>
          </div>

          <!-- Tabs -->
          <div class="border-b border-slate-200 dark:border-slate-700">
            <div class="overflow-x-auto whitespace-nowrap">
              <nav class="flex gap-6 px-2">
                <button
                  class="min-h-[44px] pb-3 text-sm font-semibold transition-all border-b-2 flex items-center gap-2"
                  :class="activeTab === 'standings' ? 'text-purple-600 border-purple-600 dark:text-purple-400 dark:border-purple-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                  @click="activeTab = 'standings'"
                >
                  <Icon icon="fluent:data-bar-vertical-24-filled" class="w-5 h-5" />
                  ตารางคะแนน
                </button>
                <button
                  class="min-h-[44px] pb-3 text-sm font-semibold transition-all border-b-2 flex items-center gap-2"
                  :class="activeTab === 'matches' ? 'text-purple-600 border-purple-600 dark:text-purple-400 dark:border-purple-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                  @click="activeTab = 'matches'"
                >
                  <Icon icon="fluent:trophy-24-filled" class="w-5 h-5" />
                  ตารางแข่ง
                </button>
                <button
                  v-if="canManage"
                  class="min-h-[44px] pb-3 text-sm font-semibold transition-all border-b-2 flex items-center gap-2"
                  :class="activeTab === 'placings' ? 'text-purple-600 border-purple-600 dark:text-purple-400 dark:border-purple-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                  @click="activeTab = 'placings'"
                >
                  <Icon icon="fluent:checkmark-starburst-24-filled" class="w-5 h-5" />
                  ยืนยันอันดับ
                </button>
                <button
                  v-if="canManage"
                  class="min-h-[44px] pb-3 text-sm font-semibold transition-all border-b-2 flex items-center gap-2"
                  :class="activeTab === 'disciplines' ? 'text-purple-600 border-purple-600 dark:text-purple-400 dark:border-purple-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                  @click="activeTab = 'disciplines'"
                >
                  <Icon icon="fluent:sport-24-filled" class="w-5 h-5" />
                  รายการแข่ง
                </button>
                <button
                  v-if="canManage"
                  class="min-h-[44px] pb-3 text-sm font-semibold transition-all border-b-2 flex items-center gap-2"
                  :class="activeTab === 'award' ? 'text-purple-600 border-purple-600 dark:text-purple-400 dark:border-purple-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                  @click="activeTab = 'award'"
                >
                  <Icon icon="fluent:add-circle-24-filled" class="w-5 h-5" />
                  ให้คะแนน
                </button>
                <button
                  class="min-h-[44px] pb-3 text-sm font-semibold transition-all border-b-2 flex items-center gap-2"
                  :class="activeTab === 'log' ? 'text-purple-600 border-purple-600 dark:text-purple-400 dark:border-purple-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                  @click="activeTab = 'log'"
                >
                  <Icon icon="fluent:history-24-filled" class="w-5 h-5" />
                  ประวัติคะแนน
                </button>
                <button
                  class="min-h-[44px] pb-3 text-sm font-semibold transition-all border-b-2 flex items-center gap-2"
                  :class="activeTab === 'albums' ? 'text-purple-600 border-purple-600 dark:text-purple-400 dark:border-purple-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                  @click="activeTab = 'albums'"
                >
                  <Icon icon="fluent:image-multiple-24-filled" class="w-5 h-5" />
                  อัลบั้มภาพ
                </button>
              </nav>
            </div>
          </div>

          <!-- Tab Content -->
          <div class="min-h-[400px]">
            <SportsStandingsBoard
              v-show="activeTab === 'standings'"
              :standings="standings"
              :house-groups="houseGroups"
              :edition-house-ids="editionHouseIds"
              :is-loading="isWorking"
              :can-manage="canManage"
              @rebuild="onRebuild"
            />
            
            <SportsMatchBoard
              v-show="activeTab === 'matches'"
              :academy-id="academyId!"
              :edition-id="Number(selectedEditionId)"
              :disciplines="disciplines"
              :houses="editionHouses"
              :can-manage="canManage"
              @refresh="loadAll"
            />

            <SportsPlacingPanel
              v-if="canManage"
              v-show="activeTab === 'placings'"
              :academy-id="academyId!"
              :edition-id="Number(selectedEditionId)"
              :disciplines="disciplines"
              :houses="editionHouses"
              :can-manage="canManage"
              @refresh="loadAll"
            />
            
            <template v-if="canManage">
              <SportsDisciplineManager
                v-show="activeTab === 'disciplines'"
                :academy-id="academyId!"
                :edition-id="Number(selectedEditionId)"
                :disciplines="disciplines"
                :can-manage="canManage"
                @refresh="loadAll"
              />
              
              <SportsScoreEntryForm
                v-show="activeTab === 'award'"
                :academy-id="academyId!"
                :edition-id="Number(selectedEditionId)"
                :disciplines="disciplines"
                :houses="editionHouses"
                :can-manage="canManage"
                @awarded="loadAll"
              />
            </template>

            <SportsScoreLog
              v-show="activeTab === 'log'"
              :academy-id="academyId!"
              :edition-id="Number(selectedEditionId)"
              :entries="entries"
              :houses="editionHouses"
              :is-loading="isWorking"
              :can-manage="canManage"
              @refresh="loadAll"
            />
            <SportsAlbumBoard
              v-show="activeTab === 'albums'"
              :academy-id="academyId!"
              :edition-id="Number(selectedEditionId)"
              :disciplines="disciplines"
              :houses="editionHouses"
              :can-manage="canManage"
            />
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
