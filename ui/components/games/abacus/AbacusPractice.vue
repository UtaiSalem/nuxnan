<script setup lang="ts">
import { onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import VirtualAbacus from '~/components/games/abacus/VirtualAbacus.vue'
import { useAbacusPractice } from '~/composables/useAbacusPractice'

/**
 * ABACUS PRACTICE — ฝึกลูกคิด (แผน S4/S5, mock-first)
 * โจทย์บวก/ลบ → เลื่อนเม็ดสร้างคำตอบบนลูกคิด → ตรวจ → ตอบผิดโชว์คำใบ้ทีละขั้น → ข้อถัดไป
 * มีสวิตช์ "ซ่อนลูกคิด" (ghost) สำหรับฝึกสร้างภาพในใจ (ไล่สู่ Anzan)
 */
const {
  level, ops, hideAbacus,
  exercise, userValue, phase, hints,
  correctCount, totalCount, streak, bestStreak, columns, accuracy,
  start, nextExercise, check, retry, setLevel, toggleOp
} = useAbacusPractice()

const levels = [
  { id: 1, label: 'ระดับ 1', desc: 'หลักเดียว' },
  { id: 2, label: 'ระดับ 2', desc: 'สองหลัก' },
  { id: 3, label: 'ระดับ 3', desc: 'สามหลัก' }
] as const

const operations = [
  { id: '+', label: 'บวก' },
  { id: '-', label: 'ลบ' }
] as const

onMounted(() => start())
</script>

<template>
  <div class="abacus-practice w-full font-sans text-slate-800 dark:text-slate-100">
    <!-- Header -->
    <div class="flex items-center gap-2 mb-4">
      <div class="w-10 h-10 rounded-vikinger bg-gradient-vikinger flex items-center justify-center text-white shadow-vikinger shrink-0">
        <Icon icon="fluent:calculator-24-filled" class="w-6 h-6" />
      </div>
      <div class="min-w-0">
        <h1 class="font-heading text-lg font-bold bg-gradient-vikinger bg-clip-text text-transparent leading-tight">ฝึกลูกคิด</h1>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">เลื่อนเม็ดให้ได้คำตอบ แล้วตรวจ</p>
      </div>
    </div>

    <!-- Config -->
    <div class="glass-card rounded-vikinger-xl p-4 shadow-card dark:shadow-card-dark mb-3">
      <div class="grid grid-cols-3 gap-2 mb-3">
        <button
          v-for="lv in levels"
          :key="lv.id"
          type="button"
          @click="setLevel(lv.id)"
          :class="[
            'min-h-[48px] rounded-vikinger flex flex-col items-center justify-center gap-0.5 text-xs font-medium border transition-all',
            level === lv.id
              ? 'bg-gradient-vikinger text-white border-transparent shadow-vikinger'
              : 'bg-white/60 dark:bg-vikinger-dark-200/60 text-slate-700 dark:text-slate-300 border-gray-200 dark:border-vikinger-dark-50/50 hover:border-vikinger-purple/50'
          ]"
        >
          <span class="font-bold">{{ lv.label }}</span>
          <span class="text-[10px] opacity-75">{{ lv.desc }}</span>
        </button>
      </div>
      <div class="flex items-center gap-2">
        <div class="grid grid-cols-2 gap-2 flex-1">
          <button
            v-for="op in operations"
            :key="op.id"
            type="button"
            @click="toggleOp(op.id)"
            :class="[
              'min-h-[44px] rounded-vikinger flex items-center justify-center gap-1.5 text-sm font-bold font-heading border transition-all',
              ops.includes(op.id)
                ? 'bg-vikinger-purple text-white border-vikinger-purple shadow-vikinger'
                : 'bg-white/60 dark:bg-vikinger-dark-200/60 text-slate-700 dark:text-slate-300 border-gray-200 dark:border-vikinger-dark-50/50 hover:border-vikinger-purple/50'
            ]"
          >
            <span class="text-lg">{{ op.id === '+' ? '+' : '−' }}</span>
            <span class="text-xs font-normal">{{ op.label }}</span>
          </button>
        </div>
        <button
          type="button"
          @click="hideAbacus = !hideAbacus"
          :class="[
            'min-h-[44px] px-3 rounded-vikinger flex items-center justify-center gap-1.5 text-xs font-semibold border transition-all shrink-0',
            hideAbacus
              ? 'bg-vikinger-cyan text-vikinger-dark border-vikinger-cyan shadow-vikinger'
              : 'bg-white/60 dark:bg-vikinger-dark-200/60 text-slate-600 dark:text-slate-300 border-gray-200 dark:border-vikinger-dark-50/50'
          ]"
          :title="hideAbacus ? 'แสดงลูกคิด' : 'ซ่อนลูกคิด (ฝึกในใจ)'"
        >
          <Icon :icon="hideAbacus ? 'fluent:eye-off-24-regular' : 'fluent:eye-24-regular'" class="w-4 h-4" />
          <span class="whitespace-nowrap">ซ่อนลูกคิด</span>
        </button>
      </div>
    </div>

    <!-- Problem -->
    <div
      :class="[
        'glass-card rounded-vikinger-xl p-5 sm:p-6 text-center shadow-card dark:shadow-card-dark mb-3 transition-all',
        phase === 'correct' ? 'ring-2 ring-emerald-500 bg-emerald-50/70 dark:bg-emerald-950/40' : '',
        phase === 'wrong' ? 'ring-2 ring-rose-500 bg-rose-50/70 dark:bg-rose-950/40' : ''
      ]"
    >
      <div class="text-3xl sm:text-4xl font-extrabold font-heading tracking-wide flex items-center justify-center gap-2 sm:gap-3 tabular-nums">
        <span>{{ exercise?.text }}</span>
        <span class="text-slate-400">=</span>
        <span
          :class="[
            'inline-flex items-center justify-center min-w-[64px] px-3 py-1 rounded-vikinger border-2 transition-all',
            userValue > 0
              ? 'border-vikinger-purple bg-white dark:bg-vikinger-dark-200 text-vikinger-purple dark:text-vikinger-cyan shadow-inner'
              : 'border-dashed border-slate-300 dark:border-vikinger-dark-50 text-slate-300 dark:text-slate-600'
          ]"
        >{{ userValue }}</span>
      </div>
    </div>

    <!-- Abacus -->
    <div class="glass-card rounded-vikinger-xl p-4 shadow-card dark:shadow-card-dark mb-3">
      <div class="flex justify-center">
        <VirtualAbacus
          v-model="userValue"
          :columns="columns"
          :ghost="hideAbacus"
          show-values
        />
      </div>
      <p v-if="hideAbacus" class="text-[11px] text-center text-slate-500 dark:text-slate-400 mt-2 flex items-center justify-center gap-1">
        <Icon icon="fluent:brain-circuit-24-regular" class="w-3.5 h-3.5" />
        ลูกคิดจาง — ฝึกนึกภาพตำแหน่งเม็ดในใจ
      </p>
    </div>

    <!-- Feedback / hints -->
    <div v-if="phase === 'wrong'" class="glass-card rounded-vikinger-xl p-4 shadow-card dark:shadow-card-dark mb-3 border-l-4 border-rose-400">
      <div class="flex items-center gap-2 text-rose-600 dark:text-rose-400 font-bold text-sm mb-2">
        <Icon icon="fluent:dismiss-circle-24-filled" class="w-5 h-5 shrink-0" />
        ยังไม่ถูก — ลองดูขั้นตอนนี้
      </div>
      <ol class="space-y-1.5">
        <li
          v-for="(h, i) in hints"
          :key="i"
          class="flex items-start gap-2 text-xs sm:text-sm text-slate-600 dark:text-slate-300"
        >
          <span class="w-5 h-5 rounded-full bg-vikinger-purple/15 text-vikinger-purple dark:text-vikinger-cyan flex items-center justify-center text-[11px] font-bold shrink-0 mt-0.5">{{ i + 1 }}</span>
          <span class="break-words min-w-0">{{ h }}</span>
        </li>
      </ol>
    </div>
    <div v-else-if="phase === 'correct'" class="glass-card rounded-vikinger-xl p-4 shadow-card dark:shadow-card-dark mb-3 border-l-4 border-emerald-400">
      <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-bold text-sm">
        <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 shrink-0" />
        ถูกต้อง! {{ exercise?.text }} = {{ exercise?.answer }}
      </div>
    </div>

    <!-- Actions -->
    <div class="grid grid-cols-4 gap-2 mb-3">
      <button
        type="button"
        @click="nextExercise"
        class="col-span-1 min-h-[48px] rounded-vikinger bg-gray-100 dark:bg-vikinger-dark-100 hover:bg-gray-200 dark:hover:bg-vikinger-dark-200 text-slate-600 dark:text-slate-300 font-medium text-xs flex items-center justify-center gap-1"
      >
        <Icon icon="fluent:arrow-shuffle-24-regular" class="w-4 h-4" />
        ข้าม
      </button>

      <button
        v-if="phase === 'answering'"
        type="button"
        @click="check"
        class="col-span-3 min-h-[48px] rounded-vikinger bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-bold font-heading text-lg shadow-lg shadow-emerald-500/20 active:scale-[0.98] flex items-center justify-center gap-2"
      >
        ตรวจคำตอบ
        <Icon icon="fluent:checkmark-24-filled" class="w-5 h-5" />
      </button>

      <template v-else-if="phase === 'wrong'">
        <button
          type="button"
          @click="retry"
          class="col-span-1 min-h-[48px] rounded-vikinger bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-900/50 font-bold text-xs flex items-center justify-center gap-1"
        >
          <Icon icon="fluent:arrow-counterclockwise-24-regular" class="w-4 h-4" />
          ลองใหม่
        </button>
        <button
          type="button"
          @click="nextExercise"
          class="col-span-2 min-h-[48px] rounded-vikinger bg-gradient-vikinger text-white font-bold font-heading text-base shadow-vikinger active:scale-[0.98] flex items-center justify-center gap-2"
        >
          ข้อถัดไป
          <Icon icon="fluent:arrow-right-24-filled" class="w-5 h-5" />
        </button>
      </template>

      <button
        v-else-if="phase === 'correct'"
        type="button"
        @click="nextExercise"
        class="col-span-3 min-h-[48px] rounded-vikinger bg-gradient-vikinger text-white font-bold font-heading text-lg shadow-vikinger active:scale-[0.98] flex items-center justify-center gap-2"
      >
        ข้อถัดไป
        <Icon icon="fluent:arrow-right-24-filled" class="w-5 h-5" />
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-2">
      <div class="glass-card rounded-vikinger p-3 text-center shadow-card dark:shadow-card-dark">
        <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">ถูก</span>
        <span class="text-lg font-bold font-heading text-emerald-600 dark:text-emerald-400 tabular-nums">{{ correctCount }}/{{ totalCount }}</span>
      </div>
      <div class="glass-card rounded-vikinger p-3 text-center shadow-card dark:shadow-card-dark">
        <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">แม่นยำ</span>
        <span class="text-lg font-bold font-heading text-vikinger-purple dark:text-vikinger-cyan tabular-nums">{{ accuracy }}%</span>
      </div>
      <div class="glass-card rounded-vikinger p-3 text-center shadow-card dark:shadow-card-dark">
        <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">ต่อเนื่อง</span>
        <span class="text-lg font-bold font-heading text-amber-600 dark:text-amber-400 tabular-nums">{{ streak }}<span class="text-xs text-slate-400"> / {{ bestStreak }}</span></span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.glass-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border: 1px solid rgba(255, 255, 255, 0.5);
}
.dark .glass-card {
  background: rgba(47, 55, 73, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.08);
}
</style>
