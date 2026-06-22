<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'

interface UpcomingEvent {
  id: number
  title: string
  event_type: string | null
  start_datetime: string
  end_datetime?: string | null
  location?: string | null
}

interface Props {
  academyId: number
  limit?: number
}

const props = withDefaults(defineProps<Props>(), { limit: 3 })
const emit = defineEmits<{ viewAll: [] }>()

const api = useApi()
const events = ref<UpcomingEvent[]>([])
const isLoading = ref(true)
const error = ref<string | null>(null)

const monthShortTH = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.']

const eventTypeBadge: Record<string, { label: string; cls: string }> = {
  academic:   { label: 'วิชาการ',   cls: 'bg-vikinger-purple/15 text-vikinger-purple' },
  sport:      { label: 'กีฬา',      cls: 'bg-vikinger-cyan/15 text-cyan-700 dark:text-cyan-300' },
  cultural:   { label: 'วัฒนธรรม',  cls: 'bg-pink-500/15 text-pink-700 dark:text-pink-300' },
  meeting:    { label: 'ประชุม',    cls: 'bg-amber-500/15 text-amber-700 dark:text-amber-300' },
  training:   { label: 'อบรม',      cls: 'bg-green-500/15 text-green-700 dark:text-green-300' },
  ceremony:   { label: 'พิธี',      cls: 'bg-vikinger-purple/15 text-vikinger-purple' },
  activity:   { label: 'กิจกรรม',   cls: 'bg-orange-500/15 text-orange-700 dark:text-orange-400' },
  other:      { label: 'อื่นๆ',     cls: 'bg-gray-200/70 dark:bg-gray-700 text-gray-700 dark:text-gray-300' },
}

const dateChip = (iso: string) => {
  const d = new Date(iso)
  return {
    day: String(d.getDate()).padStart(2, '0'),
    month: monthShortTH[d.getMonth()] || '',
  }
}

const badgeFor = (type: string | null) => {
  if (!type) return eventTypeBadge.other
  return eventTypeBadge[type] || eventTypeBadge.other
}

const loadEvents = async () => {
  isLoading.value = true
  error.value = null
  try {
    const res: any = await api.call(`/api/academies/${props.academyId}/events/upcoming`, {
      params: { limit: props.limit },
    })
    events.value = res?.data ?? []
  } catch (e: any) {
    error.value = e?.message || 'โหลดกิจกรรมล้มเหลว'
  } finally {
    isLoading.value = false
  }
}

const containerRef = ref<HTMLElement | null>(null)
useIntersectionLoad(containerRef, loadEvents)
</script>

<template>
  <div ref="containerRef" class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
      <span class="font-bold text-gray-900 dark:text-white">กิจกรรมที่จะถึง</span>
      <button
        @click="emit('viewAll')"
        class="text-xs font-semibold text-vikinger-purple hover:text-vikinger-purple/80"
      >
        ปฏิทิน
      </button>
    </div>

    <div class="p-4">
      <SchoolUpcomingEventsSkeleton v-if="isLoading" />
      
      <CommonErrorRetry v-else-if="error" :message="error" variant="inline" @retry="loadEvents" />

      <CommonEmptyState
        v-else-if="events.length === 0"
        icon="heroicons:calendar"
        title="ยังไม่มีกิจกรรม"
        description="ไม่มีกิจกรรมใหม่ที่ประกาศเร็วๆ นี้"
        compact
      />

      <div v-else class="flex flex-col gap-3">
        <div
          v-for="ev in events"
          :key="ev.id"
          class="flex items-center gap-3"
        >
          <!-- Date chip -->
          <div class="w-12 flex-shrink-0 text-center rounded-md border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gray-50 dark:bg-vikinger-dark-100 text-vikinger-purple font-bold text-base py-0.5 leading-tight">
              {{ dateChip(ev.start_datetime).day }}
            </div>
            <div class="text-[10px] font-bold text-gray-500 dark:text-gray-400 py-0.5">
              {{ dateChip(ev.start_datetime).month }}
            </div>
          </div>

          <!-- Title + type badge -->
          <div class="flex-1 min-w-0">
            <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">
              {{ ev.title }}
            </div>
            <span
              :class="['inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold mt-0.5', badgeFor(ev.event_type).cls]"
            >
              {{ badgeFor(ev.event_type).label }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
