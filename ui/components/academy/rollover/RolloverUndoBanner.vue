<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import type { RolloverBatchDTO } from '~/types/enrollment'

const props = defineProps<{
  batch: RolloverBatchDTO
}>()

const emit = defineEmits<{
  undo: []
  closeUndo: []
  reset: []
}>()

const swal = useSweetAlert()
const remainingMs = ref(0)
let timer: ReturnType<typeof setInterval> | null = null

const countdown = computed(() => {
  const totalSeconds = Math.max(0, Math.floor(remainingMs.value / 1000))
  const hours = Math.floor(totalSeconds / 3600)
  const minutes = Math.floor((totalSeconds % 3600) / 60)
  const seconds = totalSeconds % 60
  return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
})

const entries = computed(() => props.batch.plan_summary?.entries ?? [])

function refreshCountdown() {
  if (!props.batch.undo_expires_at) {
    remainingMs.value = 0
    return
  }

  remainingMs.value = new Date(props.batch.undo_expires_at).getTime() - Date.now()
  if (remainingMs.value < 0) {
    remainingMs.value = 0
  }
}

function exportCsv() {
  if (entries.value.length === 0) return

  const header = ['student_id', 'student_name', 'from_classroom_name', 'to_classroom_name', 'action', 'reason']
  const rows = entries.value.map((entry) => [
    entry.student_id,
    `"${(entry.student_name ?? '').replace(/"/g, '""')}"`,
    `"${(entry.from_classroom_name ?? '').replace(/"/g, '""')}"`,
    `"${(entry.to_classroom_name ?? '').replace(/"/g, '""')}"`,
    entry.action,
    `"${(entry.reason ?? '').replace(/"/g, '""')}"`,
  ])

  const blob = new Blob([[header.join(','), ...rows.map((row) => row.join(','))].join('\n')], {
    type: 'text/csv;charset=utf-8;',
  })

  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `rollover-${props.batch.to_year?.name ?? props.batch.id}.csv`
  link.click()
  URL.revokeObjectURL(url)
}

async function confirmUndo() {
  const ok = await swal.confirm(
    'การ undo จะล้างผล rollover รอบนี้และพากลับไปเริ่ม Step 1',
    'ยืนยัน undo rollover',
    {
      confirmText: 'Undo',
      cancelText: 'ยกเลิก',
      icon: 'warning',
      isDanger: true,
    },
  )

  if (ok) emit('undo')
}

async function confirmCloseUndo() {
  const ok = await swal.confirm(
    'เมื่อปิดหน้าต่าง undo แล้ว จะไม่สามารถย้อนกลับ batch นี้ได้อีก',
    'ปิด undo ก่อนเวลา',
    {
      confirmText: 'ปิด undo',
      cancelText: 'ยกเลิก',
      icon: 'warning',
      isDanger: true,
    },
  )

  if (ok) emit('closeUndo')
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
  <div class="rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm dark:border-emerald-900/40 dark:bg-gray-800">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Rollover สำเร็จแล้ว</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
          Batch ID: <span class="font-medium">{{ batch.id }}</span>
        </p>
      </div>
      <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-900/20">
        <div class="text-xs font-medium text-amber-800 dark:text-amber-300">Undo countdown</div>
        <div class="mt-1 text-2xl font-semibold text-amber-900 dark:text-amber-100">{{ countdown }}</div>
      </div>
    </div>

    <div class="mt-5 grid gap-3 md:grid-cols-3 xl:grid-cols-6">
      <div
        v-for="(count, key) in batch.totals"
        :key="key"
        class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-center dark:border-gray-700 dark:bg-gray-900/40"
      >
        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ key }}</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ count }}</div>
      </div>
    </div>

    <div class="mt-5 rounded-2xl border p-4" :class="batch.is_undoable ? 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/40'">
      <div class="text-sm font-semibold" :class="batch.is_undoable ? 'text-amber-800 dark:text-amber-300' : 'text-gray-800 dark:text-gray-200'">
        {{ batch.is_undoable ? 'ยัง undo ได้ภายใน 24 ชั่วโมง' : 'หน้าต่าง undo ปิดแล้ว' }}
      </div>
      <p class="mt-1 text-sm" :class="batch.is_undoable ? 'text-amber-700 dark:text-amber-400' : 'text-gray-600 dark:text-gray-400'">
        ระบบ refresh สถานะ undoable ทุก 60 วินาทีจาก backend
      </p>
      <div class="mt-4 flex flex-col gap-3 sm:flex-row">
        <button
          type="button"
          :disabled="!batch.is_undoable"
          class="min-h-[44px] sm:min-h-0 rounded-xl bg-rose-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-rose-600 disabled:cursor-not-allowed disabled:bg-gray-400"
          @click="confirmUndo"
        >
          Undo rollover
        </button>
        <button
          type="button"
          :disabled="!batch.is_undoable"
          class="min-h-[44px] sm:min-h-0 rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
          @click="confirmCloseUndo"
        >
          ปิด undo ก่อนเวลา
        </button>
        <button
          v-if="entries.length"
          type="button"
          class="min-h-[44px] sm:min-h-0 rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
          @click="exportCsv"
        >
          Export CSV
        </button>
      </div>
    </div>

    <div class="mt-6 flex justify-end">
      <button
        type="button"
        class="rounded-xl bg-primary-500 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-primary-600"
        @click="emit('reset')"
      >
        เริ่ม rollover รอบใหม่
      </button>
    </div>
  </div>
</template>
