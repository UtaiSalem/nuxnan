<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'

interface Props {
  academyId: number
  cycle?: 'week' | 'month' | 'all_time'
}
const props = withDefaults(defineProps<Props>(), { cycle: 'month' })

const api = useApi()
const rows = ref<any[]>([])
const isLoading = ref(true)
const error = ref<string | null>(null)

const medalBg = ['#ffd700', '#c0c0c0', '#cd7f32']

const load = async () => {
  isLoading.value = true
  error.value = null
  try {
    const res: any = await api.call(`/api/academies/${props.academyId}/gamification/leaderboard`, {
      params: { cycle: props.cycle, limit: 3 },
    })
    rows.value = res?.data ?? []
  } catch (e: any) {
    error.value = e?.message || 'โหลดข้อมูลล้มเหลว'
  } finally {
    isLoading.value = false
  }
}

const containerRef = ref<HTMLElement | null>(null)
useIntersectionLoad(containerRef, load)
</script>

<template>
  <div ref="containerRef" class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
      <span class="font-bold text-gray-900 dark:text-white text-sm">อันดับห้องเรียน</span>
      <Icon icon="heroicons:trophy-solid" class="w-4 h-4 text-amber-500" />
    </div>
    <div class="p-4">
      <div v-if="isLoading" class="flex flex-col gap-3">
        <div v-for="i in 3" :key="i" class="flex items-center gap-3 animate-pulse motion-reduce:animate-none">
          <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 flex-shrink-0" />
          <CommonSkeletonBox width="100px" height="14px" class="flex-1" />
          <CommonSkeletonBox width="45px" height="12px" />
        </div>
      </div>
      <CommonErrorRetry v-else-if="error" :message="error" variant="inline" @retry="load" />
      <CommonEmptyState
        v-else-if="rows.length === 0"
        icon="heroicons:trophy"
        title="ยังไม่มีอันดับ"
        description="ไม่มีการสะสมคะแนนในรอบเวลานี้"
        compact
      />
      <div v-else class="flex flex-col gap-3">
        <div
          v-for="(row, i) in rows"
          :key="row.group_id"
          class="flex items-center gap-3"
        >
          <span
            class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-gray-800 flex-shrink-0"
            :style="{ background: medalBg[i] || '#e5e7eb' }"
          >
            {{ row.rank }}
          </span>
          <span class="flex-1 text-sm font-semibold text-gray-900 dark:text-white truncate">
            {{ row.name }}
          </span>
          <span class="inline-flex items-center gap-1 text-xs font-bold text-amber-600">
            <Icon icon="heroicons:sparkles-solid" class="w-3.5 h-3.5" />
            {{ row.points.toLocaleString() }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
