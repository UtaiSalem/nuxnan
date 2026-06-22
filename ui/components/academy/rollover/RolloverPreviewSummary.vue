<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import type { RolloverPreviewEntryDTO, RolloverSummaryDTO } from '~/types/enrollment'

const props = defineProps<{
  summary: RolloverSummaryDTO | null
  warnings: string[]
  entriesCount: number
  planId: string | null
  expiresAt: string | null
  entries: RolloverPreviewEntryDTO[]
}>()

const emit = defineEmits<{
  back: []
  next: []
  expired: []
}>()

const remainingMs = ref(0)
let timer: ReturnType<typeof setInterval> | null = null

const summaryCards = computed(() => {
  const summary = props.summary
  if (!summary) return []

  return [
    { key: 'promote', label: 'เลื่อนชั้น', value: summary.promote },
    { key: 'graduate', label: 'จบการศึกษา', value: summary.graduate },
    { key: 'repeat', label: 'ซ้ำชั้น', value: summary.repeat },
    { key: 'drop', label: 'พ้นสภาพ', value: summary.drop },
    { key: 'new_intake', label: 'รับเข้าใหม่', value: summary.new_intake },
    { key: 'skip', label: 'ข้ามรายการ', value: summary.skip },
  ]
})

const countdown = computed(() => {
  const totalSeconds = Math.max(0, Math.floor(remainingMs.value / 1000))
  const minutes = Math.floor(totalSeconds / 60)
  const seconds = totalSeconds % 60
  return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
})

function refreshCountdown() {
  if (!props.expiresAt) {
    remainingMs.value = 0
    return
  }

  remainingMs.value = new Date(props.expiresAt).getTime() - Date.now()
  if (remainingMs.value <= 0) {
    remainingMs.value = 0
    emit('expired')
  }
}

onMounted(() => {
  refreshCountdown()
  timer = setInterval(refreshCountdown, 1000)
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})
</script>

<template>
  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">ตรวจแผนก่อน commit</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
          plan cache จะหมดอายุใน 15 นาทีตาม backend TTL ควร commit ต่อเนื่องในรอบเดียว
        </p>
      </div>
      <div class="rounded-2xl border border-primary-200 bg-primary-50 px-4 py-3 dark:border-primary-800 dark:bg-primary-900/20">
        <div class="text-xs font-medium text-primary-700 dark:text-primary-300">Plan countdown</div>
        <div class="mt-1 text-2xl font-semibold text-primary-900 dark:text-primary-100">{{ countdown }}</div>
      </div>
    </div>

    <div class="mb-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
      <div
        v-for="card in summaryCards"
        :key="card.key"
        class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40"
      >
        <div class="text-xs text-gray-500 dark:text-gray-400">{{ card.label }}</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ card.value }}</div>
      </div>
      <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
        <div class="text-xs text-gray-500 dark:text-gray-400">Entries ในแผน</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ entriesCount }}</div>
      </div>
      <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
        <div class="text-xs text-gray-500 dark:text-gray-400">Plan ID</div>
        <div class="mt-1 truncate text-sm font-semibold text-gray-900 dark:text-white">{{ planId }}</div>
      </div>
    </div>

    <div v-if="warnings.length" class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
      <div class="text-sm font-semibold text-amber-800 dark:text-amber-300">Warnings</div>
      <ul class="mt-2 space-y-1 text-sm text-amber-700 dark:text-amber-400">
        <li v-for="warning in warnings" :key="warning">• {{ warning }}</li>
      </ul>
    </div>

    <div class="rounded-2xl border border-gray-200 dark:border-gray-700">
      <div class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-900 dark:border-gray-700 dark:text-white">
        ตัวอย่างรายการ 10 แถวแรก
      </div>
      <div class="divide-y divide-gray-200 dark:divide-gray-700">
        <div
          v-for="entry in entries.slice(0, 10)"
          :key="entry.student_id"
          class="flex flex-col gap-1 px-4 py-3 text-sm lg:flex-row lg:items-center lg:justify-between"
        >
          <div>
            <div class="font-medium text-gray-900 dark:text-white">{{ entry.student_name }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
              {{ entry.from_classroom_name ?? 'รับเข้าใหม่' }} → {{ entry.to_classroom_name ?? '-' }}
            </div>
          </div>
          <div class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
            {{ entry.action }}
          </div>
        </div>
      </div>
    </div>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-between">
      <button
        type="button"
        class="rounded-xl border border-gray-300 px-5 py-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
        @click="emit('back')"
      >
        กลับไปแก้ mapping
      </button>
      <button
        type="button"
        :disabled="remainingMs <= 0"
        class="rounded-xl bg-primary-500 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-primary-600 disabled:cursor-not-allowed disabled:bg-gray-400"
        @click="emit('next')"
      >
        ถัดไป: ยืนยัน commit
      </button>
    </div>
  </div>
</template>
