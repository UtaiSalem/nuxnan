<script setup lang="ts">
import type { HomeVisitInfo } from '~/composables/useStudentProfile'

const props = defineProps<{
  homeVisit: HomeVisitInfo | null
  accessLevel: string
}>()

const canSeeObservations = computed(() =>
  ['admin', 'homeroom', 'teacher'].includes(props.accessLevel),
)
</script>

<template>
  <div class="space-y-4">
    <!-- Summary card -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-gray-900">การเยี่ยมบ้าน</h3>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          {{ homeVisit?.total_visits ?? 0 }} ครั้ง
        </span>
      </div>

      <!-- Next scheduled -->
      <div v-if="homeVisit?.next_scheduled"
           class="mb-4 p-3 rounded-xl bg-amber-50 border border-amber-100">
        <p class="text-xs font-medium text-amber-700">นัดเยี่ยมครั้งต่อไป</p>
        <p class="text-sm text-amber-900 mt-0.5">{{ homeVisit.next_scheduled.scheduled_at || '—' }}</p>
      </div>

      <!-- No visits -->
      <div v-if="!homeVisit || homeVisit.total_visits === 0"
           class="text-center py-8">
        <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        <p class="text-sm text-gray-400">ยังไม่มีประวัติการเยี่ยมบ้าน</p>
      </div>

      <!-- Recent visits list -->
      <ul v-else class="space-y-2">
        <li v-for="visit in homeVisit.recent" :key="visit.id"
            class="p-3 rounded-xl bg-gray-50 border border-gray-100">
          <div class="flex items-start justify-between gap-2">
            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium text-gray-700">{{ visit.visited_at || '—' }}</p>
              <p class="text-xs text-gray-500 truncate">{{ visit.visitor_name || 'ไม่ระบุผู้เยี่ยม' }}</p>
              <p v-if="canSeeObservations && visit.observations"
                 class="text-xs text-gray-600 mt-1 line-clamp-2">{{ visit.observations }}</p>
            </div>
            <span :class="[
              'flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
              visit.status === 'completed'  ? 'bg-green-100 text-green-700' :
              visit.status === 'scheduled'  ? 'bg-blue-100 text-blue-700' :
                                              'bg-gray-100 text-gray-500'
            ]">
              {{ visit.status === 'completed' ? 'เสร็จสิ้น' :
                 visit.status === 'scheduled' ? 'นัดหมาย' : visit.status || '—' }}
            </span>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>
