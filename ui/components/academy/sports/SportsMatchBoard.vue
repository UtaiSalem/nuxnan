<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'
import type { SportsDiscipline } from '~/composables/useSportsScoring'
import { useSportsMatches } from '~/composables/useSportsMatches'
import type { SportsMatchFormat, SportsMatchParticipant, SportsMatchStatus, SportsParticipantStatus, SportsMatch, RecordResultPayload } from '~/composables/useSportsMatches'

const props = defineProps<{
  academyId: number
  editionId: number
  disciplines: SportsDiscipline[]
  houses: { id: number; name: string; color: string }[]
  canManage: boolean
}>()

const emit = defineEmits<{
  (e: 'refresh'): void
}>()

const {
  listMatches,
  generateFixtures,
  recordResult,
  groupByRound,
  matchStatusText,
  participantStatusText,
  formatTimeMs
} = useSportsMatches()

const selectedDisciplineId = ref<number | null>(null)
const matches = ref<SportsMatch[]>([])
const isLoadingMatches = ref(false)
const fixtureError = ref<string | null>(null)

watch(selectedDisciplineId, async (newId) => {
  if (newId) {
    await fetchMatches()
    initFixtureForm()
  } else {
    matches.value = []
  }
})

/**
 * หน้าเพจโหลด disciplines แบบ async — ตอน component mount ลิสต์มักยังว่าง
 * ถ้าเลือกให้ตอน mount อย่างเดียว จอจะค้างว่างตลอดเมื่อข้อมูลมาทีหลัง
 * จึงต้องเฝ้าลิสต์ไว้ แล้วเลือกรายการแรกทันทีที่มีของ (และเมื่อรายการที่เลือกอยู่ถูกลบไป)
 *
 * ⚠️ ต้องประกาศ watch ตัวนี้ "หลัง" watch ของ selectedDisciplineId เสมอ
 * เพราะ immediate: true ทำงานทันทีตอน setup — ถ้าประกาศไว้ก่อน ตัวที่เฝ้า selectedDisciplineId
 * จะยังไม่ถูกลงทะเบียน แล้วการตั้งค่าครั้งแรกจะไม่ trigger การโหลดแมตช์/ตั้งค่าฟอร์มเลย
 */
watch(
  () => props.disciplines,
  (list) => {
    if (!list?.length) {
      selectedDisciplineId.value = null
      return
    }
    if (!selectedDisciplineId.value || !list.some(d => d.id === selectedDisciplineId.value)) {
      selectedDisciplineId.value = list[0].id
    }
  },
  { immediate: true, deep: true },
)

const fetchMatches = async () => {
  if (!selectedDisciplineId.value) return
  isLoadingMatches.value = true
  try {
    const res = await listMatches(props.academyId, props.editionId, { discipline_id: selectedDisciplineId.value })
    matches.value = Array.isArray(res) ? res : (res as any).data || []
  } catch (err: any) {
    fixtureError.value = err?.data?.message || err?.message || 'โหลดตารางแข่งไม่สำเร็จ'
  } finally {
    isLoadingMatches.value = false
  }
}

const selectedFormat = ref<SportsMatchFormat>('round_robin')
const selectedHouseIds = ref<number[]>([])
const createThirdPlace = ref(false)
const lanesPerHeat = ref(8)
const isGenerating = ref(false)

const initFixtureForm = () => {
  const disc = props.disciplines.find(d => d.id === selectedDisciplineId.value)
  selectedFormat.value = (disc as any)?.format || 'round_robin'
  selectedHouseIds.value = props.houses.map(h => h.id)
  createThirdPlace.value = false
  lanesPerHeat.value = 8
  fixtureError.value = null
}

const handleGenerateFixtures = async () => {
  if (!selectedDisciplineId.value) return
  if (selectedHouseIds.value.length < 2) return
  if (selectedFormat.value === 'none') return

  if (!window.confirm('การสร้างตารางใหม่จะลบคู่ที่ยังไม่แข่งทั้งหมดของรายการนี้ทิ้ง ยืนยันหรือไม่?')) return

  isGenerating.value = true
  fixtureError.value = null
  
  try {
    await generateFixtures(props.academyId, props.editionId, selectedDisciplineId.value, {
      format: selectedFormat.value,
      house_group_ids: selectedHouseIds.value,
      options: {
        third_place: createThirdPlace.value,
        lanes_per_heat: lanesPerHeat.value
      }
    })
    await fetchMatches()
    emit('refresh')
  } catch (err: any) {
    fixtureError.value = err?.data?.message || err?.message || 'เกิดข้อผิดพลาดในการสร้างตารางแข่ง'
  } finally {
    isGenerating.value = false
  }
}

const editingMatchId = ref<number | null>(null)
const editingParticipants = ref<any[]>([])

const openResultForm = (match: SportsMatch) => {
  editingMatchId.value = match.id
  editingParticipants.value = match.participants.map(p => ({
    house_group_id: p.house_group_id,
    slot: p.slot,
    score: p.score,
    time_ms: p.time_ms,
    placing: p.placing,
    status: p.status
  }))
}

const cancelResultForm = () => {
  editingMatchId.value = null
  editingParticipants.value = []
}

const isSavingResult = ref(false)
const saveResult = async (matchId: number) => {
  isSavingResult.value = true
  try {
    await recordResult(props.academyId, props.editionId, matchId, {
      status: 'finished',
      participants: editingParticipants.value.map(p => ({
        ...p,
        score: p.score === '' || p.score == null ? null : Number(p.score),
        time_ms: p.time_ms === '' || p.time_ms == null ? null : Number(p.time_ms),
        placing: p.placing === '' || p.placing == null ? null : Number(p.placing)
      }))
    })
    await fetchMatches()
    emit('refresh')
    editingMatchId.value = null
  } catch (err: any) {
    fixtureError.value = err?.data?.message || err?.message || 'บันทึกผลไม่สำเร็จ'
  } finally {
    isSavingResult.value = false
  }
}

const getHouseName = (id: number) => props.houses.find(h => h.id === id)?.name || `#${id}`
const getHouseColor = (id: number) => props.houses.find(h => h.id === id)?.color || '#94a3b8'

const rounds = computed(() => groupByRound(matches.value))
</script>

<template>
  <div class="flex flex-col gap-6">
    <div v-if="disciplines.length === 0" class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-6 text-center text-slate-500 dark:text-slate-400">
      ยังไม่มีรายการแข่ง ให้ไปสร้างที่แท็บรายการแข่งก่อน
    </div>
    
    <div v-else class="flex flex-col gap-6">
      <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
        <label class="font-medium text-slate-900 dark:text-white shrink-0">เลือกรายการแข่ง</label>
        <select 
          v-model="selectedDisciplineId" 
          class="w-full sm:w-auto p-3 sm:p-2 border border-slate-200 dark:border-slate-700 rounded-vikinger bg-white dark:bg-slate-800 text-slate-900 dark:text-white min-h-[44px]"
        >
          <option v-for="d in disciplines" :key="d.id" :value="d.id">
            {{ d.name }}
          </option>
        </select>
      </div>

      <div v-if="canManage" class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">สร้างตารางแข่งอัตโนมัติ</h3>
        
        <div class="flex flex-col gap-4">
          <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
            <label class="font-medium text-slate-900 dark:text-white shrink-0 w-32">รูปแบบ</label>
            <select v-model="selectedFormat" class="w-full sm:w-auto p-3 sm:p-2 border border-slate-200 dark:border-slate-700 rounded-vikinger bg-white dark:bg-slate-800 text-slate-900 dark:text-white min-h-[44px]">
              <option value="round_robin">พบกันหมด (Round Robin)</option>
              <option value="knockout">แพ้คัดออก (Knockout)</option>
              <option value="heats">แบ่งฮีต (Heats)</option>
              <option value="none">ไม่มีตารางแข่ง</option>
            </select>
          </div>

          <label v-if="selectedFormat === 'knockout'" class="flex items-center gap-2 pl-0 sm:pl-36 min-h-[44px] sm:min-h-0 py-2 sm:py-0">
            <input type="checkbox" v-model="createThirdPlace" class="w-5 h-5 shrink-0" />
            <span class="text-slate-900 dark:text-white">สร้างคู่ชิงอันดับที่ 3</span>
          </label>

          <div v-if="selectedFormat === 'heats'" class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
            <label class="font-medium text-slate-900 dark:text-white shrink-0 w-32">จำนวนลู่ต่อฮีต</label>
            <input type="number" v-model="lanesPerHeat" min="2" max="20" class="w-full sm:w-32 p-3 sm:p-2 border border-slate-200 dark:border-slate-700 rounded-vikinger bg-white dark:bg-slate-800 text-slate-900 dark:text-white min-h-[44px]" />
          </div>

          <div class="flex flex-col sm:flex-row gap-4 items-start">
            <div class="font-medium text-slate-900 dark:text-white shrink-0 w-32 pt-2">
              คณะสีเข้าร่วม
              <div class="text-xs text-slate-500 font-normal mt-1">ลำดับในรายการนี้คือลำดับสายของทีมวาง (seed)</div>
            </div>
            <div class="flex flex-wrap gap-x-4 gap-y-2">
              <label v-for="h in houses" :key="h.id" class="flex items-center gap-2 min-h-[44px] sm:min-h-0 py-2 sm:py-0">
                <input type="checkbox" :value="h.id" v-model="selectedHouseIds" class="w-5 h-5 shrink-0" />
                <span class="text-slate-900 dark:text-white">{{ h.name }}</span>
              </label>
            </div>
          </div>

          <div class="bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200 p-3 sm:p-4 rounded-vikinger text-sm sm:text-base border border-amber-200 dark:border-amber-800/50 flex gap-3">
            <Icon icon="fluent:warning-24-regular" class="w-6 h-6 shrink-0 mt-0.5" />
            <div>การสร้างตารางใหม่จะลบคู่ที่ยังไม่แข่งทั้งหมดของรายการนี้ทิ้ง — ถ้ามีแมตช์ที่แข่งจบแล้วระบบจะไม่ยอมให้สร้างใหม่</div>
          </div>

          <div v-if="fixtureError" class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-3 sm:p-4 rounded-vikinger text-sm sm:text-base border border-red-200 dark:border-red-800/50 flex gap-3">
            <Icon icon="fluent:error-circle-24-regular" class="w-6 h-6 shrink-0 mt-0.5" />
            <div>{{ fixtureError }}</div>
          </div>

          <div class="mt-2 pl-0 sm:pl-36">
            <button 
              @click="handleGenerateFixtures" 
              :disabled="isGenerating || selectedHouseIds.length < 2 || selectedFormat === 'none'"
              class="w-full sm:w-auto bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg p-3 sm:px-6 min-h-[44px] disabled:opacity-50 disabled:cursor-not-allowed transition-all"
            >
              {{ isGenerating ? 'กำลังสร้าง...' : 'สร้างตารางแข่ง' }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="isLoadingMatches" class="animate-pulse flex flex-col gap-4">
        <div class="h-10 bg-slate-200 dark:bg-slate-700 rounded w-1/3"></div>
        <div class="h-32 bg-slate-200 dark:bg-slate-700 rounded-vikinger w-full"></div>
      </div>

      <div v-else-if="matches.length === 0" class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-6 text-center text-slate-500 dark:text-slate-400">
        ยังไม่มีแมตช์ ให้ไปกดสร้างตารางแข่ง
      </div>

      <div v-else class="flex flex-col gap-8">
        <div v-for="round in rounds" :key="round.round_order" class="flex flex-col gap-4">
          <h4 class="text-xl font-semibold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-700 pb-2">
            {{ round.label }} <span class="text-slate-500 text-sm font-normal ml-2">({{ round.matches.length }} คู่)</span>
          </h4>

          <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <div 
              v-for="match in round.matches" 
              :key="match.id" 
              class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-col"
            >
              <!-- Top bar -->
              <div class="bg-slate-50 dark:bg-slate-900/50 p-3 sm:p-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-start gap-2">
                <div class="flex flex-col min-w-0 flex-1 break-words">
                  <div class="font-medium text-slate-900 dark:text-white">คู่ที่ {{ match.match_number }}</div>
                  <div v-if="match.location || match.scheduled_at" class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    {{ match.location }} {{ match.scheduled_at }}
                  </div>
                </div>
                <div class="px-2 py-1 rounded text-xs font-medium bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 shrink-0 whitespace-nowrap">
                  {{ matchStatusText(match.status) }}
                </div>
              </div>

              <!-- Content / Participants -->
              <div class="p-3 sm:p-4 flex-1 flex flex-col gap-3">
                <div v-if="match.participants.length === 0" class="text-slate-500 text-sm py-2 italic">
                  รอผู้ชนะจากรอบก่อนหน้า
                </div>
                
                <template v-else>
                  <div 
                    v-for="p in match.participants" 
                    :key="p.id" 
                    class="flex items-center justify-between gap-2"
                  >
                    <div class="flex items-center gap-2 min-w-0 flex-1 break-words">
                      <div class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: getHouseColor(p.house_group_id) }"></div>
                      <div class="truncate" :class="{ 'font-bold text-slate-900 dark:text-white': match.winner_house_group_id === p.house_group_id, 'text-slate-700 dark:text-slate-300': match.winner_house_group_id !== p.house_group_id }">
                        {{ getHouseName(p.house_group_id) }}
                        <Icon v-if="match.winner_house_group_id === p.house_group_id" icon="fluent:trophy-24-filled" class="inline-block w-4 h-4 text-amber-500 ml-1" />
                      </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0 whitespace-nowrap text-sm">
                      <span v-if="p.status !== 'ok'" class="px-2 py-0.5 rounded text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                        {{ participantStatusText(p.status) }}
                      </span>
                      <template v-else>
                        <span v-if="p.score !== null" class="font-medium text-slate-900 dark:text-white">{{ p.score }}</span>
                        <span v-if="p.time_ms !== null" class="font-medium text-slate-900 dark:text-white">{{ formatTimeMs(p.time_ms) }}</span>
                        <span v-if="p.placing !== null" class="text-slate-500">อันดับ {{ p.placing }}</span>
                      </template>
                    </div>
                  </div>
                </template>
              </div>

              <!-- Action / Form -->
              <div v-if="canManage" class="border-t border-slate-200 dark:border-slate-700 p-3 sm:p-4 bg-slate-50 dark:bg-slate-900/30">
                <template v-if="editingMatchId === match.id">
                  <div class="flex flex-col gap-4">
                    <div class="overflow-x-auto">
                      <table class="min-w-full text-sm">
                        <thead>
                          <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                            <th class="pb-2 font-medium whitespace-nowrap pr-4">คณะ</th>
                            <th class="pb-2 font-medium px-2 whitespace-nowrap">คะแนน</th>
                            <th class="pb-2 font-medium px-2 whitespace-nowrap">เวลา (ms)</th>
                            <th class="pb-2 font-medium px-2 whitespace-nowrap">อันดับ</th>
                            <th class="pb-2 font-medium pl-2 whitespace-nowrap">สถานะ</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(ep, idx) in editingParticipants" :key="idx" class="border-b border-slate-100 dark:border-slate-800/50 last:border-0">
                            <td class="py-2 pr-4 whitespace-nowrap">
                              <div class="flex items-center gap-1.5 min-h-[44px]">
                                <div class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: getHouseColor(ep.house_group_id) }"></div>
                                <span class="text-slate-900 dark:text-white">{{ getHouseName(ep.house_group_id) }}</span>
                              </div>
                            </td>
                            <td class="py-2 px-1">
                              <input type="number" step="any" v-model="ep.score" class="w-16 sm:w-20 p-2 min-h-[44px] border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-800 text-slate-900 dark:text-white" />
                            </td>
                            <td class="py-2 px-1">
                              <input type="number" v-model="ep.time_ms" class="w-20 sm:w-24 p-2 min-h-[44px] border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-800 text-slate-900 dark:text-white" />
                            </td>
                            <td class="py-2 px-1">
                              <input type="number" min="1" v-model="ep.placing" class="w-16 sm:w-20 p-2 min-h-[44px] border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-800 text-slate-900 dark:text-white" />
                            </td>
                            <td class="py-2 pl-1">
                              <select v-model="ep.status" class="w-24 sm:w-28 p-2 min-h-[44px] border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                                <option value="ok">ปกติ</option>
                                <option value="dq">ตัดสิทธิ์</option>
                                <option value="dns">ไม่ลงแข่ง</option>
                                <option value="dnf">แข่งไม่จบ</option>
                              </select>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                    
                    <div class="text-xs text-red-600 dark:text-red-400">
                      * การบันทึกผลรายคู่ยังไม่ใช่การให้คะแนนคณะสี — คะแนนจะเข้าตอนยืนยันอันดับที่แท็บ "ยืนยันอันดับ"
                    </div>

                    <div class="flex gap-2 justify-end mt-2">
                      <button @click="cancelResultForm" class="px-4 py-2 min-h-[44px] text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-vikinger transition-colors">
                        ยกเลิก
                      </button>
                      <button 
                        @click="saveResult(match.id)" 
                        :disabled="isSavingResult"
                        class="px-4 py-2 min-h-[44px] bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg disabled:opacity-50 transition-all"
                      >
                        {{ isSavingResult ? 'กำลังบันทึก...' : 'บันทึกผล' }}
                      </button>
                    </div>
                  </div>
                </template>
                <template v-else>
                  <button 
                    v-if="match.participants.length > 0"
                    @click="openResultForm(match)"
                    class="w-full py-2 min-h-[44px] text-blue-600 dark:text-blue-400 font-medium hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-vikinger transition-colors border border-transparent hover:border-blue-200 dark:hover:border-blue-800"
                  >
                    กรอกผล
                  </button>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
