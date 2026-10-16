<script setup lang="ts">
import { onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import HandDisplay from '~/components/games/fingers/HandDisplay.vue'
import { numberToHands } from '~/components/games/fingers/fingerEngine'
import { useFingerMath } from '~/composables/useFingerMath'

/**
 * FINGER MATH — ด่าน 6: คิดเลขบวก/ลบ แล้วเลือกท่ามือที่เป็นคำตอบ
 * เชื่อมการคำนวณเข้ากับการแทนจำนวนด้วยนิ้วมือ (ก่อนไปสู่ลูกคิด/Anzan)
 */
const {
  stage, ops, exercise, selected, phase,
  correctCount, totalCount, streak, bestStreak, accuracy,
  start, nextExercise, choose, setStage, toggleOp
} = useFingerMath()

const stages = [
  { id: 1, label: 'ด่าน 6.1', desc: 'คำตอบ 0–9' },
  { id: 2, label: 'ด่าน 6.2', desc: 'คำตอบ 0–99' }
] as const

const operations = [
  { id: '+', label: 'บวก' },
  { id: '-', label: 'ลบ' }
] as const

function choiceClass(c: number): string {
  if (phase.value === 'answering') {
    return 'border-gray-200 dark:border-vikinger-dark-50/50 hover:border-vikinger-purple/60 bg-white/70 dark:bg-vikinger-dark-200/70'
  }
  if (exercise.value && c === exercise.value.answer) {
    return 'border-emerald-500 bg-emerald-50/80 dark:bg-emerald-950/50 ring-2 ring-emerald-400'
  }
  if (c === selected.value) {
    return 'border-rose-500 bg-rose-50/80 dark:bg-rose-950/50'
  }
  return 'border-gray-200 dark:border-vikinger-dark-50/40 opacity-50 bg-white/50 dark:bg-vikinger-dark-200/50'
}

onMounted(() => start())
</script>

<template>
  <div class="finger-math w-full font-sans text-slate-800 dark:text-slate-100">
    <!-- Header -->
    <div class="flex items-center gap-2 mb-4">
      <div class="w-10 h-10 rounded-vikinger bg-gradient-vikinger flex items-center justify-center text-white shadow-vikinger shrink-0">
        <Icon icon="fluent:hand-draw-24-filled" class="w-6 h-6" />
      </div>
      <div class="min-w-0">
        <h1 class="font-heading text-lg font-bold bg-gradient-vikinger bg-clip-text text-transparent leading-tight">คิดเลขด้วยนิ้วมือ</h1>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">คิดโจทย์ แล้วเลือกท่ามือที่เป็นคำตอบ</p>
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
      <div class="grid grid-cols-2 gap-2">
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
    </div>

    <!-- Problem -->
    <div
      v-if="exercise"
      :class="[
        'glass-card rounded-vikinger-xl p-5 text-center shadow-card dark:shadow-card-dark mb-3 transition-all',
        phase === 'correct' ? 'ring-2 ring-emerald-500' : '',
        phase === 'wrong' ? 'ring-2 ring-rose-500' : ''
      ]"
    >
      <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">เลือกท่ามือที่เป็นคำตอบ</p>
      <div class="text-4xl sm:text-5xl font-extrabold font-heading tabular-nums flex items-center justify-center gap-2">
        <span>{{ exercise.text }}</span>
        <span class="text-slate-400">=</span>
        <span class="text-vikinger-purple dark:text-vikinger-cyan">?</span>
      </div>
    </div>

    <!-- Gesture choices -->
    <div v-if="exercise" class="grid grid-cols-2 gap-2 mb-3">
      <button
        v-for="c in exercise.choices"
        :key="c"
        type="button"
        :disabled="phase !== 'answering'"
        @click="choose(c)"
        :class="['min-h-[44px] rounded-vikinger border-2 p-2 flex items-center justify-center gap-1 transition-all', choiceClass(c)]"
      >
        <template v-if="stage === 2">
          <HandDisplay :gesture="numberToHands(c).tens" side="left" compact />
          <HandDisplay :gesture="numberToHands(c).units" side="right" compact />
        </template>
        <HandDisplay v-else :digit="c" side="right" compact />
      </button>
    </div>

    <!-- Feedback + next -->
    <div v-if="phase === 'correct' || phase === 'wrong'" class="glass-card rounded-vikinger-xl p-3 shadow-card dark:shadow-card-dark mb-3 flex items-center justify-between gap-2">
      <div
        class="flex items-center gap-2 font-bold text-sm min-w-0"
        :class="phase === 'correct' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
      >
        <Icon :icon="phase === 'correct' ? 'fluent:checkmark-circle-24-filled' : 'fluent:dismiss-circle-24-filled'" class="w-5 h-5 shrink-0" />
        <span class="break-words min-w-0">{{ phase === 'correct' ? 'ถูกต้อง!' : `${exercise?.text} = ${exercise?.answer}` }}</span>
      </div>
      <button
        type="button"
        @click="nextExercise"
        class="shrink-0 min-h-[44px] px-4 rounded-vikinger bg-gradient-vikinger text-white font-bold font-heading text-sm shadow-vikinger active:scale-[0.98] flex items-center gap-1.5 whitespace-nowrap"
      >
        ข้อถัดไป
        <Icon icon="fluent:arrow-right-24-filled" class="w-4 h-4" />
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
