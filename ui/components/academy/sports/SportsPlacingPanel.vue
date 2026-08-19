<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { useSportsMatches } from '~/composables/useSportsMatches'
import type { SportsSuggestedPlacing, SportsDisciplineResult, SportsMatchFormat } from '~/composables/useSportsMatches'
import { useSportsScoring } from '~/composables/useSportsScoring'
import type { SportsDiscipline } from '~/composables/useSportsScoring'

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

const { suggestedPlacings, confirmPlacings } = useSportsMatches()
const { pointsForPlacing } = useSportsScoring()

const selectedDisciplineId = ref<number | null>(null)
const selectedDiscipline = computed(() => props.disciplines.find(d => d.id === selectedDisciplineId.value))

const isLoading = ref(false)
const errorMsg = ref<string | null>(null)

interface DraftRow {
  house_group_id: number
  placing: number
  reason: string
  isManual: boolean
}

const draftPlacings = ref<DraftRow[]>([])
const matchFormat = ref<SportsMatchFormat>('none')
const isSourceManual = ref(false)

const confirmedResults = ref<SportsDisciplineResult[]>([])
const showConfirmed = ref(false)
/** matchFormat ตั้งต้นเป็น 'none' ⇒ ถ้าไม่กันไว้ รายการที่เป็น knockout จริง ๆ จะโดนขึ้นว่า "ไม่มีตารางแข่ง" ตั้งแต่ยังไม่ได้กดดึงอันดับ */
const hasFetchedSuggestion = ref(false)

// Select discipline
watch(selectedDisciplineId, () => {
  draftPlacings.value = []
  matchFormat.value = 'none'
  isSourceManual.value = false
  showConfirmed.value = false
  confirmedResults.value = []
  errorMsg.value = null
  hasFetchedSuggestion.value = false
})

const getSuggested = async () => {
  if (!selectedDisciplineId.value) return
  isLoading.value = true
  errorMsg.value = null
  try {
    const res = await suggestedPlacings(props.academyId, props.editionId, selectedDisciplineId.value)
    matchFormat.value = res.format
    draftPlacings.value = res.placings.map(p => ({
      house_group_id: p.house_group_id,
      placing: p.placing,
      reason: p.reason,
      isManual: false
    }))
    isSourceManual.value = false
    showConfirmed.value = false
    hasFetchedSuggestion.value = true
  } catch (err: any) {
    errorMsg.value = err?.data?.message || err?.message || 'เกิดข้อผิดพลาดในการดึงอันดับ'
  } finally {
    isLoading.value = false
  }
}

const unaddedHouses = computed(() => {
  return props.houses.filter(h => !draftPlacings.value.some(d => d.house_group_id === h.id))
})

const addHouse = (houseId: number) => {
  if (!houseId) return
  const maxPlacing = draftPlacings.value.length > 0 ? Math.max(...draftPlacings.value.map(d => d.placing)) : 0
  draftPlacings.value.push({
    house_group_id: houseId,
    placing: maxPlacing + 1,
    reason: 'ครูกำหนดเอง',
    isManual: true
  })
  isSourceManual.value = true
}

const removeRow = (index: number) => {
  draftPlacings.value.splice(index, 1)
  isSourceManual.value = true
}

const onPlacingChange = (row: DraftRow) => {
  row.reason = 'ครูกำหนดเอง'
  row.isManual = true
  isSourceManual.value = true
}

const hasDuplicateHouses = computed(() => {
  const ids = draftPlacings.value.map(d => d.house_group_id)
  return new Set(ids).size !== ids.length
})

const duplicatePlacings = computed(() => {
  const placings = draftPlacings.value.map(d => d.placing).filter(p => p > 0)
  return new Set(placings).size !== placings.length
})

const hasInvalidPlacing = computed(() => {
  return draftPlacings.value.some(d => !d.placing || d.placing < 1)
})

const canConfirm = computed(() => {
  if (draftPlacings.value.length === 0) return false
  if (hasDuplicateHouses.value) return false
  if (hasInvalidPlacing.value) return false
  return true
})

const confirm = async () => {
  if (!canConfirm.value) return
  if (!selectedDisciplineId.value) return
  const warning = [
    'การยืนยันอันดับจะยกเลิกคะแนนอันดับเดิมของรายการแข่งนี้ทั้งหมด (รวมคะแนนที่เคยให้ด้วยมือแบบอันดับ) แล้วลงคะแนนชุดใหม่แทน',
    'คะแนนเดิมจะยังอยู่ในประวัติแต่ถูกทำเครื่องหมายว่ายกเลิกแล้ว',
    'ยืนยันหรือไม่?',
  ].join('\n\n')

  if (!window.confirm(warning)) {
    return
  }
  
  isLoading.value = true
  errorMsg.value = null
  try {
    const payload = {
      placings: draftPlacings.value.map(d => ({ house_group_id: d.house_group_id, placing: Number(d.placing) })),
      source: isSourceManual.value ? 'manual' : 'suggested'
    } as const
    const res = await confirmPlacings(props.academyId, props.editionId, selectedDisciplineId.value, payload)
    confirmedResults.value = res.sort((a, b) => a.placing - b.placing)
    showConfirmed.value = true
    emit('refresh')
  } catch (err: any) {
    errorMsg.value = err?.data?.message || err?.message || 'เกิดข้อผิดพลาดในการยืนยันอันดับ'
  } finally {
    isLoading.value = false
  }
}

const getHouse = (id: number) => props.houses.find(h => h.id === id)
const getHouseName = (id: number) => getHouse(id)?.name || `#${id}`
const getHouseColor = (id: number) => getHouse(id)?.color || '#999'

const formatDateTime = (dt: string | null) => {
  if (!dt) return '-'
  return new Date(dt).toLocaleString('th-TH')
}

const getMedalClass = (placing: number) => {
  if (placing === 1) return 'bg-[#ffd700] text-slate-900'
  if (placing === 2) return 'bg-[#c0c0c0] text-slate-900'
  if (placing === 3) return 'bg-[#cd7f32] text-white'
  return 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200'
}
</script>

<template>
  <div class="space-y-4 sm:space-y-6">
    <div v-if="!disciplines.length" class="text-center p-4 text-slate-500">
      ยังไม่มีรายการแข่ง
    </div>
    
    <template v-else>
      <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
        <div class="flex-1 min-w-0">
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">เลือกรายการแข่ง</label>
          <select v-model="selectedDisciplineId" class="w-full min-h-[44px] p-3 sm:p-2 border border-slate-300 rounded-md dark:bg-slate-800 dark:border-slate-600">
            <option :value="null">-- โปรดเลือก --</option>
            <option v-for="d in disciplines" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
        </div>
        <div class="flex-shrink-0 pt-0 sm:pt-6">
          <button 
            @click="getSuggested" 
            :disabled="!selectedDisciplineId || isLoading"
            class="w-full sm:w-auto min-h-[44px] px-4 py-3 sm:py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 font-medium rounded-md transition-colors disabled:opacity-50"
          >
            <span class="flex items-center justify-center gap-2">
              <Icon icon="fluent:arrow-download-24-regular" /> ดึงอันดับที่ระบบเสนอ
            </span>
          </button>
        </div>
      </div>
      
      <div v-if="errorMsg" class="p-3 sm:p-4 bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-md text-sm border border-red-200 dark:border-red-800/50">
        {{ errorMsg }}
      </div>
      
      <div v-if="isLoading" class="animate-pulse space-y-4">
        <div class="h-10 bg-slate-200 dark:bg-slate-700 rounded w-full"></div>
        <div class="h-32 bg-slate-200 dark:bg-slate-700 rounded w-full"></div>
      </div>
      
      <template v-else-if="selectedDisciplineId && !showConfirmed">
        <div v-if="hasFetchedSuggestion && draftPlacings.length === 0 && matchFormat !== 'none'" class="p-3 sm:p-4 bg-blue-50 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 rounded-md text-sm">
          <template v-if="matchFormat === 'knockout'">ยังเสนออันดับไม่ได้ เพราะรอบชิงชนะเลิศยังไม่จบหรือยังไม่มีผู้ชนะ</template>
          <template v-else-if="matchFormat === 'round_robin'">ยังไม่มีคู่ที่แข่งจบ จึงยังคำนวณอันดับไม่ได้</template>
          <template v-else-if="matchFormat === 'heats'">รอบตัดสินยังไม่จบ หรือยังไม่มีการบันทึกเวลา</template>
        </div>
        <div v-else-if="hasFetchedSuggestion && draftPlacings.length === 0 && matchFormat === 'none'" class="p-3 sm:p-4 bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 rounded-md text-sm">
          รายการนี้ไม่มีตารางแข่ง ให้กรอกอันดับเองได้เลย
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700">
          <div class="p-3 sm:p-4 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <h3 class="font-medium text-slate-900 dark:text-white">ตารางร่างอันดับ</h3>
            <div v-if="canManage" class="w-full sm:w-auto flex gap-2">
              <select v-if="unaddedHouses.length > 0" @change="e => { addHouse(Number((e.target as HTMLSelectElement).value)); (e.target as HTMLSelectElement).value = '' }" class="flex-1 sm:flex-none min-h-[44px] p-2 border border-slate-300 rounded-md dark:bg-slate-700 dark:border-slate-600 text-sm">
                <option value="">+ เพิ่มคณะ</option>
                <option v-for="h in unaddedHouses" :key="h.id" :value="h.id">{{ h.name }}</option>
              </select>
            </div>
          </div>
          
          <div class="overflow-x-auto">
            <table class="min-w-full">
              <thead>
                <tr class="bg-slate-100 dark:bg-slate-900/50">
                  <th class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-left text-sm font-medium text-slate-700 dark:text-slate-300">คณะ</th>
                  <th class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-center text-sm font-medium text-slate-700 dark:text-slate-300 w-24">อันดับ</th>
                  <th class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-right text-sm font-medium text-slate-700 dark:text-slate-300 w-32">คะแนนที่จะได้*</th>
                  <th class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-left text-sm font-medium text-slate-700 dark:text-slate-300">เหตุผล</th>
                  <th v-if="canManage" class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-center text-sm font-medium text-slate-700 dark:text-slate-300 w-16"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="draftPlacings.length === 0">
                  <td colspan="5" class="px-3 py-4 text-center text-slate-500 text-sm">ยังไม่มีข้อมูลในตาราง</td>
                </tr>
                <tr v-for="(row, idx) in draftPlacings" :key="row.house_group_id" class="border-t border-slate-200 dark:border-slate-700">
                  <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                      <div class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: getHouseColor(row.house_group_id) }"></div>
                      <span class="font-medium min-w-0 flex-1 break-words text-slate-900 dark:text-slate-100">{{ getHouseName(row.house_group_id) }}</span>
                    </div>
                  </td>
                  <td class="px-3 py-3 sm:px-6 sm:py-4">
                    <input 
                      v-if="canManage" 
                      type="number" 
                      min="1" 
                      v-model="row.placing" 
                      @input="onPlacingChange(row)"
                      class="w-full min-h-[44px] sm:min-h-0 sm:h-9 text-center border border-slate-300 rounded-md dark:bg-slate-700 dark:border-slate-600"
                    >
                    <div v-else class="text-center font-semibold">{{ row.placing }}</div>
                  </td>
                  <td class="px-3 py-3 sm:px-6 sm:py-4 text-right whitespace-nowrap">
                    <span class="text-slate-600 dark:text-slate-400">
                      {{ selectedDiscipline ? pointsForPlacing(selectedDiscipline, Number(row.placing)) : 0 }}
                    </span>
                  </td>
                  <td class="px-3 py-3 sm:px-6 sm:py-4">
                    <span class="text-sm text-slate-600 dark:text-slate-400 break-words" :class="{ 'italic': row.isManual }">{{ row.reason }}</span>
                  </td>
                  <td v-if="canManage" class="px-3 py-3 sm:px-6 sm:py-4 text-center">
                    <button @click="removeRow(idx)" class="min-h-[44px] min-w-[44px] p-2 text-slate-400 hover:text-red-500 transition-colors">
                      <Icon icon="fluent:delete-24-regular" class="w-5 h-5 mx-auto" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="p-3 sm:p-4 bg-slate-50 dark:bg-slate-800/50 text-xs text-slate-500 rounded-b-vikinger border-t border-slate-200 dark:border-slate-700">
            * คะแนนที่แสดงเป็นเพียงตัวอย่าง ค่าจริงมาจากฝั่ง API
          </div>
        </div>

        <div v-if="duplicatePlacings" class="p-3 sm:p-4 bg-yellow-50 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-md text-sm border border-yellow-200 dark:border-yellow-800/50">
          <div class="flex items-start gap-2">
            <Icon icon="fluent:warning-24-regular" class="w-5 h-5 flex-shrink-0 mt-0.5" />
            <span>มีคณะได้อันดับเท่ากัน — ระบบจะให้คะแนนเท่ากันทั้งคู่ (อันดับร่วม)</span>
          </div>
        </div>
        
        <div v-if="hasDuplicateHouses" class="p-3 sm:p-4 bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-md text-sm border border-red-200 dark:border-red-800/50">
          <div class="flex items-start gap-2">
            <Icon icon="fluent:error-circle-24-regular" class="w-5 h-5 flex-shrink-0 mt-0.5" />
            <span>มีคณะซ้ำกันในตาราง โปรดลบออกให้เหลือคณะละ 1 แถว</span>
          </div>
        </div>

        <div v-if="canManage" class="p-4 sm:p-5 bg-orange-50 dark:bg-orange-900/30 border border-orange-200 dark:border-orange-800/50 rounded-lg">
          <h4 class="font-semibold text-orange-800 dark:text-orange-400 mb-2 flex items-center gap-2">
            <Icon icon="fluent:warning-28-filled" class="w-5 h-5" /> คำเตือนก่อนยืนยัน
          </h4>
          <p class="text-sm text-orange-700 dark:text-orange-300 mb-4">
            การยืนยันอันดับจะ <strong class="font-bold">ยกเลิกคะแนนอันดับเดิมของรายการแข่งนี้ทั้งหมด</strong> (รวมคะแนนที่เคยให้ด้วยมือแบบอันดับ) แล้วลงคะแนนชุดใหม่แทน — คะแนนเดิมจะยังอยู่ในประวัติแต่ถูกทำเครื่องหมายว่ายกเลิกแล้ว
          </p>
          <button 
            @click="confirm" 
            :disabled="!canConfirm || isLoading"
            class="w-full bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg min-h-[44px] px-4 py-3 sm:py-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span class="flex items-center justify-center gap-2">
              <Icon icon="fluent:checkmark-circle-24-regular" /> ยืนยันอันดับและลงคะแนน
            </span>
          </button>
        </div>
      </template>
      
      <template v-else-if="selectedDisciplineId && showConfirmed">
        <div class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 overflow-hidden">
          <div class="p-4 bg-green-50 dark:bg-green-900/30 border-b border-green-200 dark:border-green-800/50">
            <h3 class="font-medium text-green-800 dark:text-green-400 flex items-center gap-2">
              <Icon icon="fluent:checkmark-starburst-24-filled" class="w-5 h-5" /> ยืนยันอันดับสำเร็จ
            </h3>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full">
              <thead>
                <tr class="bg-slate-100 dark:bg-slate-900/50">
                  <th class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-center text-sm font-medium text-slate-700 dark:text-slate-300 w-24">อันดับ</th>
                  <th class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-left text-sm font-medium text-slate-700 dark:text-slate-300">คณะ</th>
                  <th class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-right text-sm font-medium text-slate-700 dark:text-slate-300 w-32">คะแนนที่ได้</th>
                  <th class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-right text-sm font-medium text-slate-700 dark:text-slate-300">เวลายืนยัน</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="res in confirmedResults" :key="res.id" class="border-t border-slate-200 dark:border-slate-700">
                  <td class="px-3 py-3 sm:px-6 sm:py-4 text-center">
                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-bold min-w-[2rem]" :class="getMedalClass(res.placing)">
                      {{ res.placing }}
                    </span>
                  </td>
                  <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                      <div class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: getHouseColor(res.house_group_id) }"></div>
                      <span class="font-medium min-w-0 flex-1 break-words text-slate-900 dark:text-slate-100">{{ getHouseName(res.house_group_id) }}</span>
                    </div>
                  </td>
                  <td class="px-3 py-3 sm:px-6 sm:py-4 text-right font-semibold text-slate-900 dark:text-white">
                    {{ res.score_entry?.points ?? '-' }}
                  </td>
                  <td class="px-3 py-3 sm:px-6 sm:py-4 text-right text-sm text-slate-500">
                    {{ formatDateTime(res.confirmed_at) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="p-4 border-t border-slate-200 dark:border-slate-700 flex justify-end">
             <button @click="showConfirmed = false; getSuggested()" class="min-h-[44px] px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-md hover:bg-slate-50 dark:hover:bg-slate-700 text-sm font-medium transition-colors">
               กลับไปแก้ไข / ดึงข้อมูลใหม่
             </button>
          </div>
        </div>
      </template>
    </template>
  </div>
</template>
