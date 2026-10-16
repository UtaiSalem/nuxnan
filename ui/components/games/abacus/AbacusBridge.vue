<script setup lang="ts">
import { onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import HandDisplay from '~/components/games/fingers/HandDisplay.vue'
import VirtualAbacus from '~/components/games/abacus/VirtualAbacus.vue'
import { useAbacusBridge } from '~/composables/useAbacusBridge'

/**
 * ABACUS BRIDGE — ด่าน 3: สะพานนิ้วมือ ↔ ลูกคิด
 * โชว์จำนวนเป็นท่ามือ (+ตัวเลขช่วย) → ผู้เรียนเลื่อนเม็ดสร้างจำนวนเดียวกันบนลูกคิด → ตรวจ
 * เชื่อมจำนวนที่เคยแทนด้วยนิ้วมือเข้ากับตำแหน่งบนลูกคิด
 */
const {
  stage, hideNumber, target, userValue, phase, hint,
  correctCount, totalCount, streak, bestStreak, columns, accuracy, targetHands,
  start, nextExercise, check, retry, setStage
} = useAbacusBridge()

const stages = [
  { id: 1, label: 'ด่าน 3.1', desc: '0–9 (1 หลัก)' },
  { id: 2, label: 'ด่าน 3.2', desc: '0–99 (2 หลัก)' }
] as const

onMounted(() => start())
</script>

<template>
  <div class="abacus-bridge w-full font-sans text-slate-800 dark:text-slate-100">
    <!-- Header -->
    <div class="flex items-center gap-2 mb-4">
      <div class="w-10 h-10 rounded-vikinger bg-gradient-vikinger flex items-center justify-center text-white shadow-vikinger shrink-0">
        <Icon icon="fluent:arrow-swap-24-filled" class="w-6 h-6" />
      </div>
      <div class="min-w-0">
        <h1 class="font-heading text-lg font-bold bg-gradient-vikinger bg-clip-text text-transparent leading-tight">นิ้วมือ ↔ ลูกคิด</h1>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">สร้างจำนวนจากนิ้วมือ ให้ตรงบนลูกคิด</p>
      </div>
    </div>

    <!-- Config -->
    <div class="glass-card rounded-vikinger-xl p-4 shadow-card dark:shadow-card-dark mb-3">
      <div class="grid grid-cols-2 gap-2 mb-2">
        <button
          v-for="st in stages"
          :key="st.id"
          type="button"
          @click="setStage(st.id)"
          :class="[
            'min-h-[48px] rounded-vikinger flex flex-col items-center justify-center gap-0.5 text-xs font-medium border transition-all',
            stage === st.id
              ? 'bg-gradient-vikinger text-white border-transparent shadow-vikinger'
              : 'bg-white/60 dark:bg-vikinger-dark-200/60 text-slate-700 dark:text-slate-300 border-gray-200 dark:border-vikinger-dark-50/50 hover:border-vikinger-purple/50'
          ]"
        >
          <span class="font-bold">{{ st.label }}</span>
          <span class="text-[10px] opacity-75">{{ st.desc }}</span>
        </button>
      </div>
      <button
        type="button"
        @click="hideNumber = !hideNumber"
        :class="[
          'w-full min-h-[44px] rounded-vikinger flex items-center justify-center gap-1.5 text-xs font-semibold border transition-all',
          hideNumber
            ? 'bg-vikinger-cyan text-vikinger-dark border-vikinger-cyan shadow-vikinger'
            : 'bg-white/60 dark:bg-vikinger-dark-200/60 text-slate-600 dark:text-slate-300 border-gray-200 dark:border-vikinger-dark-50/50'
        ]"
      >
        <Icon :icon="hideNumber ? 'fluent:eye-off-24-regular' : 'fluent:eye-24-regular'" class="w-4 h-4" />
        ซ่อนตัวเลขช่วย (อ่านจากนิ้วมือเอง)
      </button>
    </div>

    <!-- Given: hands (+ number) -->
    <div class="glass-card rounded-vikinger-xl p-4 shadow-card dark:shadow-card-dark mb-3">
      <p class="text-xs text-center text-slate-500 dark:text-slate-400 mb-2">นิ้วมือบอกจำนวนนี้ — สร้างให้ตรงบนลูกคิด</p>
      <div class="flex items-end justify-center gap-4 overflow-x-auto max-w-full py-1">
        <HandDisplay v-if="stage === 2" :gesture="targetHands.tens" side="left" label="หลักสิบ" />
        <HandDisplay :gesture="targetHands.units" side="right" :label="stage === 2 ? 'หลักหน่วย' : 'มือขวา'" />
      </div>
      <div v-if="!hideNumber" class="text-center mt-2">
        <span class="inline-block px-4 py-1 rounded-vikinger bg-vikinger-purple/10 text-vikinger-purple dark:text-vikinger-cyan font-heading text-2xl font-bold tabular-nums">{{ target }}</span>
      </div>
    </div>

    <!-- Abacus input -->
    <div class="glass-card rounded-vikinger-xl p-4 shadow-card dark:shadow-card-dark mb-3">
      <div
        class="flex justify-center rounded-vikinger transition-all"
        :class="[
          phase === 'correct' ? 'ring-2 ring-emerald-500' : '',
          phase === 'wrong' ? 'ring-2 ring-rose-500' : ''
        ]"
      >
        <VirtualAbacus v-model="userValue" :columns="columns" show-values />
      </div>
    </div>

    <!-- Feedback -->
    <div v-if="phase === 'wrong'" class="glass-card rounded-vikinger-xl p-3 shadow-card dark:shadow-card-dark mb-3 border-l-4 border-rose-400">
      <div class="flex items-center gap-2 text-rose-600 dark:text-rose-400 font-bold text-sm">
        <Icon icon="fluent:dismiss-circle-24-filled" class="w-5 h-5 shrink-0" />
        <span class="break-words min-w-0">ยังไม่ตรง — {{ hint }}</span>
      </div>
    </div>
    <div v-else-if="phase === 'correct'" class="glass-card rounded-vikinger-xl p-3 shadow-card dark:shadow-card-dark mb-3 border-l-4 border-emerald-400">
      <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-bold text-sm">
        <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 shrink-0" />
        ตรงกัน! นิ้วมือและลูกคิดคือ {{ target }}
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
