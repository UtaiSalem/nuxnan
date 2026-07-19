<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  academyId: number
}

const props = defineProps<Props>()

const { revenueDashboard, isLoading, fetchRevenueDashboard } = useAcademyRevenue(props.academyId)

onMounted(() => {
  fetchRevenueDashboard()
})
</script>

<template>
  <div class="space-y-4">
    <div v-if="isLoading" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="i in 4" :key="i" class="h-24 animate-pulse rounded-2xl bg-gray-100 dark:bg-gray-700" />
    </div>

    <div v-else-if="revenueDashboard" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div class="rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white p-4 shadow-sm dark:border-indigo-900/40 dark:from-indigo-950/40 dark:to-gray-800">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">ยอดคงเหลือ</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
          {{ revenueDashboard.points.available_balance.toLocaleString() }}
        </p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">แต้ม</p>
      </div>

      <div class="rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-4 shadow-sm dark:border-emerald-900/40 dark:from-emerald-950/40 dark:to-gray-800">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">รวมรายได้โฆษณา</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
          {{ revenueDashboard.ad_revenue.total_points.toLocaleString() }}
        </p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">แต้ม</p>
      </div>

      <div class="rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-4 shadow-sm dark:border-amber-900/40 dark:from-amber-950/40 dark:to-gray-800">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">รอตรวจสอบ</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
          {{ revenueDashboard.donations.pending_count.toLocaleString() }}
        </p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">รายการ</p>
      </div>

      <div class="rounded-2xl border border-rose-100 bg-gradient-to-br from-rose-50 to-white p-4 shadow-sm dark:border-rose-900/40 dark:from-rose-950/40 dark:to-gray-800">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">แคมเปญที่เปิดอยู่</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
          {{ revenueDashboard.ad_revenue.active_campaigns.toLocaleString() }}
        </p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">แคมเปญ</p>
      </div>
    </div>
  </div>
</template>
