<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import type { RolloverSummaryDTO } from '~/types/enrollment'

const props = defineProps<{
  targetYearName: string
  planId: string | null
  summary: RolloverSummaryDTO | null
  warnings: string[]
  expiresAt: string | null
  isSubmitting: boolean
}>()

const emit = defineEmits<{
  back: []
  commit: [confirmText: string]
  expired: []
}>()

const confirmText = ref('')
const remainingMs = ref(0)
let timer: ReturnType<typeof setInterval> | null = null

const canCommit = computed(() => {
  return !!props.planId
    && remainingMs.value > 0
    && confirmText.value.trim() === props.targetYearName
    && !props.isSubmitting
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
  <div class="rounded-2xl border border-rose-200 bg-white p-6 shadow-sm dark:border-rose-900/40 dark:bg-gray-800">
    <div class="mb-6">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">ยืนยัน commit แผน rollover</h2>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
        ขั้นตอนนี้จะเขียนสถานะนักเรียนจริงลงฐานข้อมูลและย้อนกลับได้เฉพาะในหน้าต่าง undo 24 ชั่วโมง
      </p>
    </div>

    <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/20">
      <div class="text-sm font-semibold text-rose-800 dark:text-rose-300">Commit gate</div>
      <p class="mt-1 text-sm text-rose-700 dark:text-rose-400">
        พิมพ์ชื่อปีปลายทางให้ตรงเป๊ะ: <span class="font-semibold">{{ targetYearName }}</span>
      </p>
    </div>

    <div class="mb-5 grid gap-3 md:grid-cols-3">
      <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
        <div class="text-xs text-gray-500 dark:text-gray-400">Promote</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ summary?.promote ?? 0 }}</div>
      </div>
      <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
        <div class="text-xs text-gray-500 dark:text-gray-400">Graduate</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ summary?.graduate ?? 0 }}</div>
      </div>
      <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
        <div class="text-xs text-gray-500 dark:text-gray-400">Plan countdown</div>
        <div class="mt-1 text-2xl font-semibold text-primary-700 dark:text-primary-300">{{ countdown }}</div>
      </div>
    </div>

    <div v-if="warnings.length" class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
      <div class="text-sm font-semibold text-amber-800 dark:text-amber-300">ตรวจอีกครั้งก่อน commit</div>
      <ul class="mt-2 space-y-1 text-sm text-amber-700 dark:text-amber-400">
        <li v-for="warning in warnings" :key="warning">• {{ warning }}</li>
      </ul>
    </div>

    <div class="space-y-2">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        พิมพ์ "{{ targetYearName }}" เพื่อยืนยัน
      </label>
      <input
        v-model="confirmText"
        type="text"
        :placeholder="targetYearName"
        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
      />
      <p class="text-xs text-gray-500 dark:text-gray-400">ระบบจะ trim ช่องว่างหัวท้ายเท่านั้นก่อนเทียบข้อความ</p>
    </div>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-between">
      <button
        type="button"
        :disabled="isSubmitting"
        class="rounded-xl border border-gray-300 px-5 py-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
        @click="emit('back')"
      >
        กลับไปดูแผน
      </button>
      <button
        type="button"
        :disabled="!canCommit"
        class="rounded-xl bg-rose-500 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-rose-600 disabled:cursor-not-allowed disabled:bg-gray-400"
        @click="emit('commit', confirmText)"
      >
        {{ isSubmitting ? 'กำลัง commit...' : 'Commit rollover ตอนนี้' }}
      </button>
    </div>
  </div>
</template>
