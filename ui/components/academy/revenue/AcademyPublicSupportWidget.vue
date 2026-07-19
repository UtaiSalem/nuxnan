<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  academyId: number
  name: string
}

const props = defineProps<Props>()

const { supportSummary, isLoading, fetchSupportSummary } = useAcademyRevenue(props.academyId)

onMounted(() => {
  fetchSupportSummary()
})
</script>

<template>
  <div class="space-y-4">
    <div v-if="isLoading" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="i in 4" :key="i" class="h-24 animate-pulse rounded-2xl bg-gray-100 dark:bg-gray-700" />
    </div>

    <div v-else-if="supportSummary" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div class="rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white p-4 shadow-sm dark:border-indigo-900/40 dark:from-indigo-950/40 dark:to-gray-800">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">แต้มที่ได้รับ (อนุมัติแล้ว)</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
          {{ supportSummary.approved_points_total.toLocaleString() }}
        </p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">แต้ม</p>
      </div>

      <div class="rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-4 shadow-sm dark:border-emerald-900/40 dark:from-emerald-950/40 dark:to-gray-800">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">รายได้โฆษณา (แต้ม)</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
          {{ supportSummary.ad_revenue_points.toLocaleString() }}
        </p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">แต้ม</p>
      </div>

      <div class="rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50 to-white p-4 shadow-sm dark:border-violet-900/40 dark:from-violet-950/40 dark:to-gray-800">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">จำนวนผู้สนับสนุน</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
          {{ supportSummary.supporter_count.toLocaleString() }}
        </p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">คน</p>
      </div>

      <div class="rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-4 shadow-sm dark:border-amber-900/40 dark:from-amber-950/40 dark:to-gray-800">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">แคมเปญที่เปิดอยู่</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
          {{ supportSummary.campaign_count.toLocaleString() }}
        </p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">แคมเปญ</p>
      </div>
    </div>
  </div>
</template>
