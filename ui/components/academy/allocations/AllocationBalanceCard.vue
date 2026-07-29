<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed } from 'vue'

interface Props {
  /** แต้มที่ใช้โอนได้จริง (available) */
  available: number
  /** แต้มคงเหลือทั้งหมดในกระเป๋าโรงเรียน */
  balance: number
  /** แต้มที่จัดสรรออกไปแล้วสะสม */
  distributed: number
  /** จำนวนที่กำลังจะโอน — ใช้แสดงยอดคงเหลือหลังโอนแบบสด */
  pendingAmount?: number
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  pendingAmount: 0,
  loading: false,
})

const fmt = (n: number) => new Intl.NumberFormat('th-TH').format(Math.max(0, Math.round(n || 0)))

const afterTransfer = computed(() => props.available - (props.pendingAmount || 0))
const isOverdrawn = computed(() => afterTransfer.value < 0)
const hasPending = computed(() => (props.pendingAmount || 0) > 0)

/** สัดส่วนที่กำลังจะโอนเทียบกับยอดที่ใช้ได้ — ใช้กับแถบความคืบหน้า */
const usagePercent = computed(() => {
  if (!props.available || props.available <= 0) return 0
  return Math.min(100, Math.round(((props.pendingAmount || 0) / props.available) * 100))
})
</script>

<template>
  <div
    class="relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-sky-50 via-indigo-50 to-violet-50 p-5 sm:p-6 dark:border-indigo-900/40 dark:from-slate-900 dark:via-indigo-950/40 dark:to-slate-900"
  >
    <!-- Loading -->
    <div v-if="loading" class="animate-pulse space-y-5" aria-label="กำลังโหลดยอดแต้มโรงเรียน">
      <div class="h-4 w-40 rounded bg-indigo-100/70 dark:bg-gray-700" />
      <div class="h-10 w-56 rounded bg-indigo-100/70 dark:bg-gray-700" />
      <div class="grid grid-cols-2 gap-3">
        <div v-for="i in 2" :key="i" class="h-16 rounded-xl bg-indigo-100/70 dark:bg-gray-700" />
      </div>
    </div>

    <div v-else>
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-start gap-3">
          <div class="rounded-xl bg-white p-2.5 text-indigo-500 shadow-sm dark:bg-indigo-950/60 dark:text-indigo-300">
            <Icon icon="fluent:wallet-credit-card-24-regular" class="h-6 w-6" />
          </div>
          <div>
            <p class="text-sm font-semibold text-indigo-500 dark:text-indigo-300">แต้มที่โอนได้</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
              {{ fmt(available) }}
              <span class="text-base font-semibold text-gray-500 dark:text-gray-400">แต้ม</span>
            </p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              จากกระเป๋าโรงเรียนทั้งหมด {{ fmt(balance) }} แต้ม
            </p>
          </div>
        </div>

        <!-- ยอดหลังโอน (แสดงเมื่อกรอกจำนวนแล้ว) -->
        <div
          v-if="hasPending"
          class="rounded-xl border px-4 py-3 text-right transition"
          :class="isOverdrawn
            ? 'border-rose-200 bg-rose-50 dark:border-rose-900/50 dark:bg-rose-950/30'
            : 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-950/30'"
        >
          <p class="text-xs font-medium" :class="isOverdrawn ? 'text-rose-500 dark:text-rose-300' : 'text-emerald-600 dark:text-emerald-300'">
            {{ isOverdrawn ? 'แต้มไม่พอ' : 'คงเหลือหลังโอน' }}
          </p>
          <p class="mt-0.5 text-2xl font-bold" :class="isOverdrawn ? 'text-rose-600 dark:text-rose-300' : 'text-emerald-700 dark:text-emerald-200'">
            {{ isOverdrawn ? '−' + fmt(Math.abs(afterTransfer)) : fmt(afterTransfer) }}
          </p>
          <p class="text-[11px] text-gray-500 dark:text-gray-400">แต้ม</p>
        </div>
      </div>

      <!-- แถบแสดงสัดส่วนที่กำลังจะโอน -->
      <div v-if="hasPending" class="mt-5">
        <div class="mb-1.5 flex items-center justify-between text-xs">
          <span class="text-gray-500 dark:text-gray-400">กำลังจะโอน {{ fmt(pendingAmount) }} แต้ม</span>
          <span class="font-semibold" :class="isOverdrawn ? 'text-rose-500' : 'text-indigo-500 dark:text-indigo-300'">
            {{ usagePercent }}% ของแต้มที่มี
          </span>
        </div>
        <div class="h-2 w-full overflow-hidden rounded-full bg-white/70 dark:bg-gray-800">
          <div
            class="h-full rounded-full transition-all duration-500"
            :class="isOverdrawn ? 'bg-rose-500' : 'bg-gradient-to-r from-indigo-500 to-violet-500'"
            :style="{ width: `${usagePercent}%` }"
          />
        </div>
      </div>

      <div class="mt-6 grid grid-cols-2 gap-3">
        <div class="rounded-xl bg-white/80 p-3 shadow-sm dark:bg-gray-800/60">
          <p class="text-xs text-gray-500 dark:text-gray-400">จัดสรรให้คอร์สแล้ว</p>
          <p class="mt-1 text-lg font-bold text-amber-600 dark:text-amber-400">{{ fmt(distributed) }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">แต้มสะสม</p>
        </div>
        <div class="rounded-xl bg-white/80 p-3 shadow-sm dark:bg-gray-800/60">
          <p class="text-xs text-gray-500 dark:text-gray-400">แต้มคงเหลือในกระเป๋า</p>
          <p class="mt-1 text-lg font-bold text-indigo-600 dark:text-indigo-300">{{ fmt(balance) }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">แต้ม</p>
        </div>
      </div>
    </div>
  </div>
</template>
