<template>
  <div class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700">
    <div class="p-3 sm:p-6 border-b border-slate-200 dark:border-slate-700">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
        <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
          ประวัติคะแนน
          <span v-if="!isLoading" class="text-sm font-normal text-slate-500 bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded-full">
            นับ {{ validEntriesCount }} | ยกเลิก {{ voidedEntriesCount }}
          </span>
        </h3>
      </div>

      <!-- Filters -->
      <div class="flex flex-col sm:flex-row gap-3">
        <select v-model="filterHouseId" class="flex-1 px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none min-h-[44px]">
          <option :value="null">คณะสีทั้งหมด</option>
          <option v-for="h in houses" :key="h.id" :value="h.id">{{ h.name }}</option>
        </select>
        
        <select v-model="filterDisciplineId" class="flex-1 px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none min-h-[44px]">
          <option :value="null">รายการแข่งทั้งหมด</option>
          <option value="manual">-- ให้ด้วยมือ --</option>
          <option v-for="d in uniqueDisciplines" :key="d.id" :value="d.id">{{ d.name }}</option>
        </select>

        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200 min-h-[44px] cursor-pointer">
          <input type="checkbox" v-model="showVoided" class="w-4 h-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500" />
          แสดงรายการที่ยกเลิกแล้ว
        </label>
      </div>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="isLoading" class="divide-y divide-slate-200 dark:divide-slate-700">
      <div v-for="i in 3" :key="i" class="p-3 sm:p-4 animate-pulse">
        <div class="flex justify-between items-start mb-2">
          <div class="h-5 bg-slate-200 dark:bg-slate-700 rounded w-1/3"></div>
          <div class="h-5 bg-slate-200 dark:bg-slate-700 rounded w-12"></div>
        </div>
        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-1/2 mb-1"></div>
        <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-1/4"></div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredEntries.length === 0" class="p-8 text-center text-slate-500 dark:text-slate-400">
      <Icon icon="fluent:history-24-regular" class="w-12 h-12 mx-auto mb-3 opacity-50" />
      <p>ยังไม่มีการให้คะแนน</p>
    </div>

    <!-- List -->
    <div v-else class="divide-y divide-slate-200 dark:divide-slate-700">
      <div v-for="entry in filteredEntries" :key="entry.id" class="p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors" :class="{'opacity-60': entry.voided_at}">
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between sm:justify-start gap-2 mb-1">
            <div class="flex items-center gap-2 min-w-0 flex-1">
              <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: getHouseColor(entry.house_group_id) }"></span>
              <span class="font-semibold text-slate-900 dark:text-white truncate">{{ getHouseName(entry.house_group_id) }}</span>
            </div>
            
            <!-- คะแนน (บรรทัดบนบนมือถือ ขวาบน sm:) -->
            <div class="font-bold flex-shrink-0 text-right sm:hidden" :class="getPointsColor(entry)">
              <span :class="{'line-through text-slate-400 dark:text-slate-500': entry.voided_at}">
                {{ formatPoints(entry.points) }}
              </span>
            </div>
          </div>
          
          <div class="text-sm text-slate-600 dark:text-slate-400 flex flex-wrap items-center gap-x-2 gap-y-1">
            <span class="font-medium text-slate-800 dark:text-slate-200">
              {{ entry.discipline ? entry.discipline.name : 'ให้ด้วยมือ' }}
            </span>
            
            <span v-if="entry.source === 'placing'" class="text-xs bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded">อันดับ</span>
            <span v-else-if="entry.source === 'judged'" class="text-xs bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 px-1.5 py-0.5 rounded">กรรมการ</span>
            <span v-else-if="entry.source === 'manual'" class="text-xs bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 px-1.5 py-0.5 rounded">ด้วยมือ</span>

            <span v-if="entry.placing" class="text-xs bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 px-1.5 py-0.5 rounded">
              อันดับที่ {{ entry.placing }}
            </span>
            
            <span class="text-xs opacity-70 whitespace-nowrap">&bull; {{ new Date(entry.created_at).toLocaleString('th-TH') }}</span>
            
            <span v-if="entry.voided_at" class="text-xs bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-white px-1.5 py-0.5 rounded font-medium ml-1">
              ยกเลิกแล้ว
            </span>
          </div>
          
          <div v-if="entry.note" class="mt-1 text-sm text-slate-500 dark:text-slate-400 italic">
            "{{ entry.note }}"
          </div>
        </div>

        <div class="flex items-center gap-3 justify-end sm:flex-shrink-0">
          <div class="font-bold flex-shrink-0 text-right hidden sm:block" :class="getPointsColor(entry)">
            <span :class="{'line-through text-slate-400 dark:text-slate-500': entry.voided_at}">
              {{ formatPoints(entry.points) }}
            </span>
          </div>
          
          <button v-if="canManage && !entry.voided_at" @click="handleVoid(entry)" class="text-sm border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 dark:hover:bg-rose-900/30 dark:hover:text-rose-400 px-3 py-1.5 rounded-lg transition-colors min-h-[44px] flex items-center gap-1">
            <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4" />
            ยกเลิกคะแนน
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useSportsScoring } from '~/composables/useSportsScoring'
import type { SportsScoreEntry } from '~/composables/useSportsScoring'

const props = defineProps<{
  academyId: number
  editionId: number
  entries: SportsScoreEntry[]
  houses: { id: number; name: string; color: string }[]
  isLoading: boolean
  canManage: boolean
}>()

const emit = defineEmits<{
  (e: 'refresh'): void
}>()

const { voidEntry, num } = useSportsScoring()

const showVoided = ref(true)
const filterHouseId = ref<number | null>(null)
const filterDisciplineId = ref<number | string | null>(null)

const validEntriesCount = computed(() => props.entries.filter(e => !e.voided_at).length)
const voidedEntriesCount = computed(() => props.entries.filter(e => !!e.voided_at).length)

const uniqueDisciplines = computed(() => {
  const map = new Map()
  props.entries.forEach(e => {
    if (e.discipline && !map.has(e.discipline.id)) {
      map.set(e.discipline.id, { id: e.discipline.id, name: e.discipline.name })
    }
  })
  return Array.from(map.values())
})

const filteredEntries = computed(() => {
  return props.entries.filter(e => {
    if (!showVoided.value && e.voided_at) return false
    if (filterHouseId.value && e.house_group_id !== filterHouseId.value) return false
    
    if (filterDisciplineId.value) {
      if (filterDisciplineId.value === 'manual' && e.discipline_id !== null) return false
      if (filterDisciplineId.value !== 'manual' && e.discipline_id !== filterDisciplineId.value) return false
    }
    
    return true
  })
})

const getHouseName = (houseId: number) => {
  const house = props.houses.find(h => h.id === houseId)
  return house ? house.name : 'ไม่ทราบคณะ'
}

const getHouseColor = (houseId: number) => {
  const house = props.houses.find(h => h.id === houseId)
  return house ? house.color : '#cbd5e1'
}

const formatPoints = (points: string | number) => {
  const pts = num(points)
  if (pts > 0) return `+${pts}`
  return `${pts}`
}

const getPointsColor = (entry: SportsScoreEntry) => {
  if (entry.voided_at) return 'text-slate-400 dark:text-slate-500'
  const pts = num(entry.points)
  if (pts > 0) return 'text-emerald-600 dark:text-emerald-400'
  if (pts < 0) return 'text-rose-600 dark:text-rose-400'
  return 'text-slate-600 dark:text-slate-400'
}

const handleVoid = async (entry: SportsScoreEntry) => {
  if (confirm('คุณต้องการยกเลิกคะแนนนี้ใช่หรือไม่?\n\nรายการจะยังอยู่ในประวัติแต่ไม่ถูกนับในตารางคะแนน')) {
    try {
      await voidEntry(props.academyId, props.editionId, entry.id)
      emit('refresh')
    } catch (e: any) {
      alert(e?.data?.message || 'ยกเลิกคะแนนไม่สำเร็จ')
    }
  }
}
</script>
