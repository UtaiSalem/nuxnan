<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import type { SportsHouseStanding } from '~/composables/useSportsScoring'

const props = defineProps<{
  standings: SportsHouseStanding[]
  houseGroups: any[]
  editionHouseIds: number[]
  isLoading: boolean
  canManage: boolean
}>()

const emit = defineEmits<{
  (e: 'rebuild'): void
}>()

const { num } = useSportsScoring()

const displayRows = computed(() => {
  const rows = props.editionHouseIds.map(id => {
    const house = props.houseGroups.find(h => Number(h.id) === Number(id))
    const standing = props.standings.find(s => Number(s.house_group_id) === Number(id))
    
    return {
      id: Number(id),
      name: house?.name || standing?.house_group?.name || `#${id}`,
      color: house?.settings?.color || '#8b5cf6',
      rank: standing?.rank ?? null,
      total_points: standing ? num(standing.total_points) : 0,
      gold_count: standing?.gold_count || 0,
      silver_count: standing?.silver_count || 0,
      bronze_count: standing?.bronze_count || 0,
      computed_at: standing?.computed_at
    }
  })

  // Sort: ranked (ascending) first, then unranked (rank is null)
  return rows.sort((a, b) => {
    if (a.rank !== null && b.rank !== null) {
      return a.rank - b.rank
    }
    if (a.rank !== null) return -1
    if (b.rank !== null) return 1
    return 0
  })
})

const lastComputedAt = computed(() => {
  const firstWithTime = props.standings.find(s => s.computed_at)
  if (!firstWithTime?.computed_at) return null
  return new Date(firstWithTime.computed_at).toLocaleString('th-TH')
})

const getRankColorClass = (rank: number | null) => {
  if (rank === 1) return 'bg-[#ffd700] text-slate-900 shadow-[#ffd700]/50 shadow-sm' // Gold
  if (rank === 2) return 'bg-[#c0c0c0] text-slate-900 shadow-[#c0c0c0]/50 shadow-sm' // Silver
  if (rank === 3) return 'bg-[#cd7f32] text-white shadow-[#cd7f32]/50 shadow-sm'    // Bronze
  return 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' // Others
}

const formatPoints = (points: number) => {
  return points.toLocaleString('th-TH', { maximumFractionDigits: 2 })
}
</script>

<template>
  <div class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700">
    <!-- Header -->
    <div class="p-3 sm:p-6 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <div class="flex items-center gap-2">
          <Icon icon="fluent:trophy-24-filled" class="w-6 h-6 text-yellow-500" />
          <h2 class="text-base sm:text-lg font-heading font-bold text-slate-900 dark:text-white">ตารางคะแนนคณะสี</h2>
        </div>
        <p v-if="lastComputedAt" class="text-sm text-slate-500 dark:text-slate-400 mt-1">
          คำนวณล่าสุด: {{ lastComputedAt }}
        </p>
      </div>
      
      <button 
        v-if="canManage"
        @click="emit('rebuild')"
        :disabled="isLoading"
        class="border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 px-4 py-2 rounded-vikinger min-h-[44px] sm:min-h-0 flex items-center justify-center gap-2 transition-all disabled:opacity-50"
      >
        <Icon icon="fluent:arrow-sync-24-regular" class="w-5 h-5" :class="{'animate-spin': isLoading}" />
        <span>คำนวณใหม่</span>
      </button>
    </div>

    <!-- Content -->
    <div class="p-3 sm:p-6">
      <div v-if="isLoading" class="space-y-4">
        <div v-for="i in 4" :key="i" class="animate-pulse flex flex-col sm:flex-row items-start sm:items-center p-4 border border-slate-100 dark:border-slate-700 rounded-xl gap-4">
          <div class="flex items-center gap-4 w-full sm:w-1/3">
            <div class="w-8 h-8 bg-slate-200 dark:bg-slate-700 rounded-full flex-shrink-0"></div>
            <div class="w-4 h-4 rounded-full bg-slate-200 dark:bg-slate-700 flex-shrink-0"></div>
            <div class="h-5 bg-slate-200 dark:bg-slate-700 rounded w-full"></div>
          </div>
          <div class="flex items-center justify-between w-full sm:flex-1 mt-2 sm:mt-0 gap-4">
            <div class="h-5 bg-slate-200 dark:bg-slate-700 rounded w-24"></div>
            <div class="h-8 bg-slate-200 dark:bg-slate-700 rounded w-16"></div>
          </div>
        </div>
      </div>

      <div v-else-if="editionHouseIds.length === 0" class="text-center py-10">
        <Icon icon="fluent:flag-off-24-regular" class="w-12 h-12 text-slate-400 mx-auto mb-3" />
        <p class="text-slate-600 dark:text-slate-300 font-medium">ครั้งนี้ยังไม่ได้เลือกคณะสี</p>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
          โปรดไปที่เมนู "คณะสี (กีฬาสี)" เพื่อกำหนดคณะสีสำหรับการแข่งขันครั้งนี้
        </p>
      </div>

      <div v-else class="space-y-3">
        <div 
          v-for="row in displayRows" 
          :key="row.id"
          class="flex flex-col sm:flex-row sm:items-center p-3 sm:p-4 rounded-xl border border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 gap-3 sm:gap-6"
        >
          <!-- Rank & Name (Top row on mobile, Left on desktop) -->
          <div class="flex items-center gap-3 sm:gap-4 min-w-0 flex-1 break-words">
            <!-- Rank Circle -->
            <div 
              class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-bold text-sm sm:text-base flex-shrink-0"
              :class="getRankColorClass(row.rank)"
            >
              {{ row.rank !== null ? row.rank : '—' }}
            </div>
            
            <!-- Color Dot & Name -->
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
              <div class="w-3 h-3 sm:w-4 sm:h-4 rounded-full flex-shrink-0 shadow-sm" :style="{ backgroundColor: row.color }"></div>
              <span class="font-bold text-slate-900 dark:text-white text-sm sm:text-base truncate">{{ row.name }}</span>
            </div>
          </div>

          <!-- Medals & Score (Bottom row on mobile, Right on desktop) -->
          <div class="flex items-center justify-between sm:justify-end w-full sm:w-auto gap-4 sm:gap-8 flex-shrink-0 whitespace-nowrap pl-11 sm:pl-0">
            <!-- Medals -->
            <div class="flex items-center gap-3 sm:gap-4 text-xs sm:text-sm">
              <div class="flex items-center gap-1 text-slate-600 dark:text-slate-300" title="เหรียญทอง">
                <Icon icon="fluent:medal-24-filled" class="w-4 h-4 sm:w-5 sm:h-5 text-[#ffd700]" />
                <span class="font-medium">{{ row.gold_count }}</span>
              </div>
              <div class="flex items-center gap-1 text-slate-600 dark:text-slate-300" title="เหรียญเงิน">
                <Icon icon="fluent:medal-24-filled" class="w-4 h-4 sm:w-5 sm:h-5 text-[#c0c0c0]" />
                <span class="font-medium">{{ row.silver_count }}</span>
              </div>
              <div class="flex items-center gap-1 text-slate-600 dark:text-slate-300" title="เหรียญทองแดง">
                <Icon icon="fluent:medal-24-filled" class="w-4 h-4 sm:w-5 sm:h-5 text-[#cd7f32]" />
                <span class="font-medium">{{ row.bronze_count }}</span>
              </div>
            </div>

            <!-- Total Points -->
            <div class="text-right flex-shrink-0">
              <div class="text-xl sm:text-2xl font-bold font-heading text-primary-600 dark:text-primary-400">
                {{ formatPoints(row.total_points) }}
              </div>
              <div class="text-[10px] sm:text-xs text-slate-500 uppercase tracking-wider hidden sm:block">คะแนนรวม</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
