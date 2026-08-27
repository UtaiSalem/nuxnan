<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed } from 'vue'
import type { AcademyAllocation } from '~/composables/useAcademyAllocations'

interface Props {
  rows: AcademyAllocation[]
  meta?: { current_page?: number; last_page?: number; total?: number }
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  loading: false,
})

const emit = defineEmits<{ page: [page: number] }>()

const fmt = (n: number) => new Intl.NumberFormat('th-TH').format(Math.max(0, Math.round(n || 0)))

const currentPage = computed(() => props.meta?.current_page || 1)
const lastPage = computed(() => props.meta?.last_page || 1)
const total = computed(() => props.meta?.total ?? props.rows.length)

const formatDateTime = (value: string) => {
  if (!value) return '—'
  return new Date(value).toLocaleDateString('th-TH', {
    day: 'numeric',
    month: 'short',
    year: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <div class="flex h-full flex-col">
    <div class="mb-4 flex items-center justify-between gap-3">
      <div class="flex items-center gap-2.5">
        <span class="rounded-lg bg-amber-100 p-2 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
          <Icon icon="fluent:history-24-regular" class="h-5 w-5" />
        </span>
        <div>
          <h2 class="text-base font-bold text-gray-900 dark:text-white">ประวัติการโอน</h2>
          <p class="text-xs text-gray-500 dark:text-gray-400">ทั้งหมด {{ fmt(total) }} รายการ</p>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="h-20 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-800" />
    </div>

    <!-- Empty -->
    <div
      v-else-if="!rows.length"
      class="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 px-6 py-12 text-center dark:border-gray-700"
    >
      <span class="rounded-full bg-gray-100 p-4 dark:bg-gray-800">
        <Icon icon="fluent:arrow-swap-24-regular" class="h-8 w-8 text-gray-400" />
      </span>
      <p class="mt-3 text-sm font-semibold text-gray-700 dark:text-gray-200">ยังไม่มีการโอนแต้ม</p>
      <p class="mt-1 max-w-xs text-xs text-gray-500 dark:text-gray-400">
        เมื่อคุณโอนแต้มให้คอร์สเรียน รายการจะแสดงที่นี่พร้อมยอดคงเหลือก่อน–หลังโอน
      </p>
    </div>

    <!-- รายการ -->
    <div v-else class="space-y-3">
      <article
        v-for="r in rows"
        :key="r.id"
        class="rounded-xl border border-gray-200 bg-white p-4 transition hover:border-indigo-300 hover:shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:hover:border-indigo-800"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="flex min-w-0 items-start gap-3">
            <span class="mt-0.5 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-300">
              <Icon icon="fluent:book-24-regular" class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <p class="truncate text-sm font-bold text-gray-900 dark:text-white">
                {{ r.course?.name || `คอร์ส #${r.course_id}` }}
              </p>
              <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                {{ formatDateTime(r.created_at) }}
              </p>
            </div>
          </div>
          <div class="flex-shrink-0 text-right">
            <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">+{{ fmt(r.amount) }}</p>
            <p class="text-[11px] text-gray-400">แต้ม</p>
          </div>
        </div>

        <p
          v-if="r.purpose"
          class="mt-2.5 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:bg-gray-800/60 dark:text-gray-300"
        >
          {{ r.purpose }}
        </p>

        <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-gray-400">
          <span v-if="r.performer?.name" class="inline-flex items-center gap-1">
            <Icon icon="fluent:person-24-regular" class="h-3.5 w-3.5" />
            {{ r.performer.name }}
          </span>
          <span v-if="(r as any).balance_before !== undefined" class="inline-flex items-center gap-1">
            <Icon icon="fluent:wallet-24-regular" class="h-3.5 w-3.5" />
            {{ fmt((r as any).balance_before) }} → {{ fmt((r as any).balance_after) }}
          </span>
        </div>
      </article>
    </div>

    <!-- Pagination -->
    <div v-if="!loading && lastPage > 1" class="mt-4 flex items-center justify-between gap-3">
      <button
        type="button"
        :disabled="currentPage <= 1"
        class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 transition hover:border-indigo-300 hover:text-indigo-600 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:text-gray-300"
        @click="emit('page', currentPage - 1)"
      >
        <Icon icon="fluent:chevron-left-24-regular" class="h-4 w-4" />
        ก่อนหน้า
      </button>
      <span class="text-sm text-gray-500 dark:text-gray-400">หน้า {{ currentPage }} / {{ lastPage }}</span>
      <button
        type="button"
        :disabled="currentPage >= lastPage"
        class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 transition hover:border-indigo-300 hover:text-indigo-600 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:text-gray-300"
        @click="emit('page', currentPage + 1)"
      >
        ถัดไป
        <Icon icon="fluent:chevron-right-24-regular" class="h-4 w-4" />
      </button>
    </div>
  </div>
</template>
