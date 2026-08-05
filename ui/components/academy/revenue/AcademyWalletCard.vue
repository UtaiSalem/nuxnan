<script setup lang="ts">
import { Icon } from '@iconify/vue'
import type { AcademySupportSummary } from '~/composables/useAcademyRevenue'

interface Props {
  summary: AcademySupportSummary | null
  loading?: boolean
}

defineProps<Props>()
const emit = defineEmits<{ support: [] }>()
</script>

<template>
  <div class="rounded-2xl border border-indigo-100 bg-gradient-to-br from-sky-50 via-indigo-50 to-violet-50 p-5 shadow-sm sm:p-6 dark:border-indigo-900/40 dark:from-slate-900 dark:via-indigo-950/40 dark:to-slate-900">
    <div v-if="loading" class="animate-pulse space-y-5" aria-label="กำลังโหลดกระเป๋าโรงเรียน">
      <div class="h-4 w-40 rounded bg-indigo-100/70 dark:bg-gray-700" />
      <div class="h-10 w-56 rounded bg-indigo-100/70 dark:bg-gray-700" />
      <div class="grid grid-cols-3 gap-3">
        <div v-for="i in 3" :key="i" class="h-16 rounded-xl bg-indigo-100/70 dark:bg-gray-700" />
      </div>
    </div>

    <div v-else>
      <div class="flex items-start gap-3">
        <div class="rounded-xl bg-white p-2.5 text-indigo-500 shadow-sm dark:bg-indigo-950/60 dark:text-indigo-300">
          <Icon icon="fluent:wallet-credit-card-24-regular" class="h-6 w-6" />
        </div>
        <div>
          <p class="text-sm font-semibold text-indigo-500 dark:text-indigo-300">กระเป๋าโรงเรียน</p>
          <p class="mt-1 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
            {{ (summary?.point_balance ?? 0).toLocaleString() }}
          </p>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">แต้มคงเหลือของโรงเรียน</p>
        </div>
      </div>

      <div class="mt-6 grid grid-cols-3 gap-2 sm:gap-3">
        <div class="rounded-xl bg-white/80 p-3 shadow-sm dark:bg-gray-800/60">
          <p class="text-xs text-gray-500 dark:text-gray-400">เงินบริจาคสะสม</p>
          <p class="mt-1 text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ (summary?.approved_cash_total ?? 0).toLocaleString() }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">บาท</p>
        </div>
        <div class="rounded-xl bg-white/80 p-3 shadow-sm dark:bg-gray-800/60">
          <p class="text-xs text-gray-500 dark:text-gray-400">แจกให้นักเรียนแล้ว</p>
          <p class="mt-1 text-lg font-bold text-amber-600 dark:text-amber-400">{{ (summary?.total_distributed ?? 0).toLocaleString() }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">แต้ม</p>
        </div>
        <div class="rounded-xl bg-white/80 p-3 shadow-sm dark:bg-gray-800/60">
          <p class="text-xs text-gray-500 dark:text-gray-400">ผู้สนับสนุน</p>
          <p class="mt-1 text-lg font-bold text-violet-600 dark:text-violet-400">{{ (summary?.supporter_count ?? 0).toLocaleString() }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">คน</p>
        </div>
      </div>

      <!-- Ads and money support live in the AdvertiseCtaWidget below, so this card
           only carries the point-donation action. -->
      <div class="mt-6">
        <button type="button" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-violet-700 sm:w-auto" @click="emit('support')">
          <Icon icon="fluent:heart-24-regular" class="h-5 w-5" />
          สนับสนุนโรงเรียน
        </button>
      </div>
    </div>
  </div>
</template>
