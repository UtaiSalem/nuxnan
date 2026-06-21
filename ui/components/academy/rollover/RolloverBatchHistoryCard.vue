<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import type { RolloverBatchDTO } from '~/types/enrollment'

interface Props {
  batch: RolloverBatchDTO
  busy?: boolean
}

const props = withDefaults(defineProps<Props>(), { busy: false })
const emit = defineEmits<{
  view: [batch: RolloverBatchDTO]
  undo: [batch: RolloverBatchDTO]
  closeUndo: [batch: RolloverBatchDTO]
}>()

const now = ref(Date.now())
let timer: ReturnType<typeof setInterval> | null = null

watch(
  () => props.batch.is_undoable,
  (undoable) => {
    if (undoable && !timer) {
      timer = setInterval(() => { now.value = Date.now() }, 1000)
    } else if (!undoable && timer) {
      clearInterval(timer)
      timer = null
    }
  },
  { immediate: true },
)

onBeforeUnmount(() => { if (timer) clearInterval(timer) })

const countdown = computed(() => {
  if (!props.batch.is_undoable || !props.batch.undo_expires_at) return null
  const expires = new Date(props.batch.undo_expires_at).getTime()
  const remaining = Math.max(0, expires - now.value)
  if (remaining === 0) return null
  const hours = Math.floor(remaining / 3_600_000)
  const minutes = Math.floor((remaining % 3_600_000) / 60_000)
  const seconds = Math.floor((remaining % 60_000) / 1000)
  return `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
})

const isUndone = computed(() => props.batch.status === 'undone')

const formatDateTime = (value: string | null | undefined): string => {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return new Intl.DateTimeFormat('th-TH', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit',
  }).format(date)
}

interface TotalTile { key: string; label: string; icon: string; tone: string }
const totalsLabels: Record<string, TotalTile> = {
  promote:    { key: 'promote',    label: 'เลื่อนชั้น',  icon: 'mdi:arrow-up-bold-circle',    tone: 'text-indigo-600 dark:text-indigo-400' },
  graduate:   { key: 'graduate',   label: 'จบการศึกษา',  icon: 'mdi:school',                  tone: 'text-violet-600 dark:text-violet-400' },
  repeat:     { key: 'repeat',     label: 'ซ้ำชั้น',     icon: 'mdi:refresh-circle',          tone: 'text-amber-600 dark:text-amber-400' },
  drop:       { key: 'drop',       label: 'พ้นสภาพ',     icon: 'mdi:account-cancel',          tone: 'text-rose-600 dark:text-rose-400' },
  new_intake: { key: 'new_intake', label: 'นักเรียนใหม่', icon: 'mdi:account-plus',            tone: 'text-emerald-600 dark:text-emerald-400' },
  skip:       { key: 'skip',       label: 'ข้าม',         icon: 'mdi:debug-step-over',          tone: 'text-zinc-500 dark:text-zinc-400' },
}

const totalsList = computed(() => {
  const totals = (props.batch.totals ?? {}) as Record<string, number>
  return Object.keys(totalsLabels)
    .map((key) => ({ ...totalsLabels[key], count: totals[key] ?? 0 }))
    .filter((t) => t.count > 0)
})

const totalRows = computed(() => {
  const totals = (props.batch.totals ?? {}) as Record<string, number>
  return Object.values(totals).reduce((sum, n) => sum + (Number(n) || 0), 0)
})
</script>

<template>
  <article
    class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/70"
    :class="isUndone ? 'opacity-75' : ''"
  >
    <header class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <div class="flex items-center gap-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
          <span>{{ batch.from_year?.name ?? '-' }}</span>
          <Icon icon="mdi:arrow-right-thick" class="h-4 w-4 text-zinc-400" />
          <span>{{ batch.to_year?.name ?? '-' }}</span>
        </div>
        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
          <span class="inline-flex items-center gap-1">
            <Icon icon="mdi:calendar-clock" class="h-3.5 w-3.5" />
            {{ formatDateTime(batch.committed_at) }}
          </span>
          <span v-if="batch.committed_by?.name" class="inline-flex items-center gap-1">
            <Icon icon="mdi:account" class="h-3.5 w-3.5" />
            {{ batch.committed_by.name }}
          </span>
          <span class="inline-flex items-center gap-1">
            <Icon icon="mdi:identifier" class="h-3.5 w-3.5" />
            <code class="text-[10px]">{{ batch.id.substring(0, 8) }}</code>
          </span>
        </div>
      </div>

      <div class="flex flex-col items-end gap-1">
        <span
          :class="[
            'inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium',
            isUndone
              ? 'border-zinc-300 bg-zinc-100 text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'
              : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
          ]"
        >
          <Icon :icon="isUndone ? 'mdi:undo' : 'mdi:check-circle'" class="h-3.5 w-3.5" />
          {{ isUndone ? 'ถูกยกเลิก' : 'บันทึกแล้ว' }}
        </span>
        <span class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ totalRows }} รายการ</span>
      </div>
    </header>

    <div v-if="totalsList.length" class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
      <div
        v-for="t in totalsList"
        :key="t.key"
        class="rounded-xl bg-zinc-50 px-3 py-2 dark:bg-zinc-800/60"
      >
        <div class="flex items-center justify-between">
          <span class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ t.label }}</span>
          <Icon :icon="t.icon" class="h-4 w-4" :class="t.tone" />
        </div>
        <div class="mt-1 text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ t.count }}</div>
      </div>
    </div>

    <div
      v-if="isUndone && batch.undone_at"
      class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-200"
    >
      <span class="font-medium">ยกเลิกเมื่อ:</span>
      {{ formatDateTime(batch.undone_at) }}
      <span v-if="batch.undone_by?.name"> โดย {{ batch.undone_by.name }}</span>
    </div>

    <div
      v-else-if="batch.is_undoable && countdown"
      class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-200"
    >
      <Icon icon="mdi:timer-sand" class="mr-1 inline h-3.5 w-3.5" />
      ยกเลิกได้อีก <span class="font-mono font-semibold">{{ countdown }}</span>
    </div>

    <footer class="mt-4 flex flex-wrap items-center justify-end gap-2">
      <button
        type="button"
        :disabled="busy"
        class="inline-flex items-center gap-1 rounded-md border border-zinc-300 px-3 py-1.5 text-sm text-zinc-700 hover:bg-zinc-100 disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
        @click="emit('view', batch)"
      >
        <Icon icon="mdi:eye" class="h-4 w-4" />
        ดูรายละเอียด
      </button>

      <button
        v-if="batch.is_undoable"
        type="button"
        :disabled="busy"
        class="inline-flex items-center gap-1 rounded-md border border-zinc-300 px-3 py-1.5 text-sm text-zinc-600 hover:bg-zinc-100 disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
        @click="emit('closeUndo', batch)"
      >
        <Icon icon="mdi:lock-clock" class="h-4 w-4" />
        ปิด undo ทันที
      </button>

      <button
        v-if="batch.is_undoable"
        type="button"
        :disabled="busy"
        class="inline-flex items-center gap-1 rounded-md bg-rose-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-50"
        @click="emit('undo', batch)"
      >
        <Icon icon="mdi:undo" class="h-4 w-4" />
        ยกเลิก rollover
      </button>
    </footer>
  </article>
</template>
