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

function timeAgo(dateStr: string): string {
  const diff = Date.now() - new Date(dateStr).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'เมื่อสักครู่'
  if (mins < 60) return `${mins} นาทีที่แล้ว`
  const hrs = Math.floor(mins / 60)
  if (hrs < 24) return `${hrs} ชั่วโมงที่แล้ว`
  const days = Math.floor(hrs / 24)
  if (days < 30) return `${days} วันที่แล้ว`
  return new Date(dateStr).toLocaleDateString('th-TH', { day: 'numeric', month: 'short' })
}
</script>

<template>
  <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700/50 dark:bg-vikinger-dark-200">
    <!-- Header -->
    <div class="flex items-center gap-2.5 mb-4">
      <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-pink-50 dark:bg-pink-950/30">
        <Icon icon="fluent:heart-24-filled" class="h-4 w-4 text-pink-500" />
      </div>
      <h4 class="text-sm font-bold text-gray-900 dark:text-white">สถิติการสนับสนุน</h4>
    </div>

    <!-- Loading skeleton -->
    <div v-if="isLoading" class="space-y-3">
      <div class="grid grid-cols-2 gap-3">
        <div v-for="i in 4" :key="i" class="h-20 rounded-xl shimmer-bg" />
      </div>
    </div>

    <!-- Stats grid -->
    <div v-else-if="supportSummary" class="space-y-4">
      <div class="grid grid-cols-2 gap-3">
        <!-- Approved Points -->
        <div class="group rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50/80 to-white p-3.5 transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-indigo-900/30 dark:from-indigo-950/30 dark:to-vikinger-dark-200">
          <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-blue-600 shadow-sm shadow-indigo-500/20 mb-2">
            <Icon icon="fluent:diamond-24-filled" class="h-4 w-4 text-white" />
          </div>
          <p class="text-lg font-black text-gray-900 dark:text-white tabular-nums">
            {{ supportSummary.approved_points_total.toLocaleString() }}
          </p>
          <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">แต้มที่ได้รับ</p>
        </div>

        <!-- Ad Revenue -->
        <div class="group rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50/80 to-white p-3.5 transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-emerald-900/30 dark:from-emerald-950/30 dark:to-vikinger-dark-200">
          <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 shadow-sm shadow-emerald-500/20 mb-2">
            <Icon icon="fluent:megaphone-24-filled" class="h-4 w-4 text-white" />
          </div>
          <p class="text-lg font-black text-gray-900 dark:text-white tabular-nums">
            {{ supportSummary.ad_revenue_points.toLocaleString() }}
          </p>
          <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">รายได้โฆษณา</p>
        </div>

        <!-- Supporter Count -->
        <div class="group rounded-xl border border-violet-100 bg-gradient-to-br from-violet-50/80 to-white p-3.5 transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-violet-900/30 dark:from-violet-950/30 dark:to-vikinger-dark-200">
          <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-violet-500 to-purple-600 shadow-sm shadow-violet-500/20 mb-2">
            <Icon icon="fluent:people-24-filled" class="h-4 w-4 text-white" />
          </div>
          <p class="text-lg font-black text-gray-900 dark:text-white tabular-nums">
            {{ supportSummary.supporter_count.toLocaleString() }}
          </p>
          <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">ผู้สนับสนุน</p>
        </div>

        <!-- Campaign Count -->
        <div class="group rounded-xl border border-amber-100 bg-gradient-to-br from-amber-50/80 to-white p-3.5 transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-amber-900/30 dark:from-amber-950/30 dark:to-vikinger-dark-200">
          <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 shadow-sm shadow-amber-500/20 mb-2">
            <Icon icon="fluent:target-24-filled" class="h-4 w-4 text-white" />
          </div>
          <p class="text-lg font-black text-gray-900 dark:text-white tabular-nums">
            {{ supportSummary.campaign_count.toLocaleString() }}
          </p>
          <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">แคมเปญ</p>
        </div>
      </div>

      <!-- Recent Donors Section -->
      <div v-if="supportSummary.recent_donations?.length">
        <div class="flex items-center justify-between mb-2.5">
          <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">ผู้สนับสนุนล่าสุด</p>
          <!-- Avatar stack -->
          <div class="flex -space-x-2">
            <div
              v-for="(donor, i) in supportSummary.recent_donations.slice(0, 4)"
              :key="i"
              class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-gradient-to-br from-violet-400 to-indigo-500 text-[9px] font-bold text-white dark:border-vikinger-dark-200"
              :title="donor.donor_display_name"
            >
              {{ (donor.anonymous ? '?' : (donor.donor_display_name || '?'))[0] }}
            </div>
            <div
              v-if="supportSummary.recent_donations.length > 4"
              class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-gray-200 text-[9px] font-bold text-gray-600 dark:border-vikinger-dark-200 dark:bg-gray-700 dark:text-gray-300"
            >
              +{{ supportSummary.recent_donations.length - 4 }}
            </div>
          </div>
        </div>

        <!-- Donor list -->
        <div class="space-y-2">
          <div
            v-for="donation in supportSummary.recent_donations.slice(0, 5)"
            :key="donation.id"
            class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50"
          >
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-400 to-indigo-500 text-[10px] font-bold text-white">
              {{ (donation.anonymous ? '?' : (donation.donor_display_name || '?'))[0] }}
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-xs font-medium text-gray-700 dark:text-gray-200 truncate">
                {{ donation.anonymous ? 'ผู้ไม่ประสงค์ออกนาม' : donation.donor_display_name }}
              </p>
              <p class="text-[10px] text-gray-400 dark:text-gray-500">{{ timeAgo(donation.created_at) }}</p>
            </div>
            <span
              class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold"
              :class="donation.donation_type === 'point'
                ? 'bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400'
                : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400'"
            >
              {{ donation.donation_type === 'point'
                ? `${(donation.points_amount || 0).toLocaleString()} pp`
                : `฿${(donation.cash_amount || 0).toLocaleString()}` }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.shimmer-bg {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}
:root.dark .shimmer-bg,
.dark .shimmer-bg {
  background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
  background-size: 200% 100%;
}
@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
</style>
