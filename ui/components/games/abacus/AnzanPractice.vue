<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useAnzan } from '~/composables/useAnzan'

/**
 * ANZAN — คิดในใจ (ด่านสุดท้าย): ตัวเลขโผล่ทีละตัว บวก/ลบในใจ แล้วตอบผลรวม
 * ไม่มีลูกคิดบนจอ — ฝึกสร้างภาพ/คำนวณในใจ
 */
const {
  count, level, speed, speeds,
  round, phase, countdownNum, flashTerm, termCount,
  input, correctCount, totalCount, streak, bestStreak, accuracy,
  start, startRound, appendDigit, backspace, clearInput, submit, replay, abort
} = useAnzan()

const counts = [3, 5, 7]
const levels = [
  { id: 'easy', label: 'ง่าย', desc: '1–9' },
  { id: 'hard', label: 'ยาก', desc: '1–20' }
] as const

// สรุปลำดับตัวเลขหลังตอบ เช่น "3 +7 −2 = 8"
const recap = computed(() => {
  if (!round.value) return ''
  const parts = round.value.terms.map((t, i) => (i === 0 ? String(t) : t >= 0 ? `+${t}` : `−${Math.abs(t)}`))
  return `${parts.join(' ')} = ${round.value.answer}`
})
</script>

<template>
  <div class="anzan w-full font-sans text-slate-800 dark:text-slate-100">
    <!-- Header -->
    <div class="flex items-center gap-2 mb-4">
      <div class="w-10 h-10 rounded-vikinger bg-gradient-vikinger flex items-center justify-center text-white shadow-vikinger shrink-0">
        <Icon icon="fluent:brain-circuit-24-filled" class="w-6 h-6" />
      </div>
      <div class="min-w-0">
        <h1 class="font-heading text-lg font-bold bg-gradient-vikinger bg-clip-text text-transparent leading-tight">คิดในใจ (Anzan)</h1>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">ตัวเลขโผล่ทีละตัว บวก/ลบในใจ แล้วตอบผลรวม</p>
      </div>
    </div>

    <!-- Config -->
    <div class="glass-card rounded-vikinger-xl p-4 shadow-card dark:shadow-card-dark mb-3">
      <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">จำนวนตัวเลข</label>
      <div class="grid grid-cols-3 gap-2 mb-3">
        <button
          v-for="c in counts"
          :key="c"
          type="button"
          @click="count = c"
          :class="[
            'min-h-[44px] rounded-vikinger font-heading font-bold border transition-all',
            count === c ? 'bg-gradient-vikinger text-white border-transparent shadow-vikinger' : 'bg-white/60 dark:bg-vikinger-dark-200/60 text-slate-700 dark:text-slate-300 border-gray-200 dark:border-vikinger-dark-50/50 hover:border-vikinger-purple/50'
          ]"
        >{{ c }} ตัว</button>
      </div>
      <div class="grid grid-cols-2 gap-2 mb-3">
        <button
          v-for="lv in levels"
          :key="lv.id"
          type="button"
          @click="level = lv.id"
          :class="[
            'min-h-[44px] rounded-vikinger text-sm font-semibold border transition-all flex items-center justify-center gap-1.5',
            level === lv.id ? 'bg-vikinger-purple text-white border-vikinger-purple shadow-vikinger' : 'bg-white/60 dark:bg-vikinger-dark-200/60 text-slate-700 dark:text-slate-300 border-gray-200 dark:border-vikinger-dark-50/50 hover:border-vikinger-purple/50'
          ]"
        >{{ lv.label }} <span class="text-[10px] opacity-70">({{ lv.desc }})</span></button>
      </div>
      <div class="grid grid-cols-3 gap-2">
        <button
          v-for="sp in speeds"
          :key="sp.id"
          type="button"
          @click="speed = sp.id"
          :class="[
            'min-h-[40px] rounded-vikinger text-xs font-semibold border transition-all',
            speed === sp.id ? 'bg-vikinger-cyan text-vikinger-dark border-vikinger-cyan shadow-vikinger' : 'bg-white/60 dark:bg-vikinger-dark-200/60 text-slate-700 dark:text-slate-300 border-gray-200 dark:border-vikinger-dark-50/50 hover:border-vikinger-cyan/50'
          ]"
        >{{ sp.label }}</button>
      </div>
    </div>

    <!-- Stage -->
    <div class="glass-card rounded-vikinger-xl p-6 shadow-card dark:shadow-card-dark mb-3 min-h-[200px] flex flex-col items-center justify-center text-center"
      :class="[
        phase === 'correct' ? 'ring-2 ring-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/40' : '',
        phase === 'wrong' ? 'ring-2 ring-rose-500 bg-rose-50/60 dark:bg-rose-950/40' : ''
      ]"
    >
      <!-- idle -->
      <template v-if="phase === 'idle'">
        <Icon icon="fluent:eye-off-24-regular" class="w-12 h-12 text-vikinger-purple/50 dark:text-vikinger-cyan/50 mb-3" />
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 max-w-xs">พร้อมแล้วกด "เริ่ม" — ตัวเลขจะโผล่ทีละตัว จำผลรวมไว้ในใจ</p>
        <button type="button" @click="start" class="min-h-[48px] px-8 rounded-vikinger bg-gradient-vikinger text-white font-bold font-heading text-lg shadow-vikinger active:scale-[0.98] flex items-center gap-2">
          <Icon icon="fluent:play-24-filled" class="w-5 h-5" /> เริ่ม
        </button>
      </template>

      <!-- countdown -->
      <template v-else-if="phase === 'countdown'">
        <p class="text-xs text-slate-400 mb-2">เตรียมตัว…</p>
        <div class="text-7xl font-extrabold font-heading text-vikinger-purple dark:text-vikinger-cyan tabular-nums animate-pulse">{{ countdownNum }}</div>
      </template>

      <!-- flash -->
      <template v-else-if="phase === 'flash'">
        <p class="text-[11px] text-slate-400 mb-2">จำไว้ในใจ…</p>
        <div class="h-24 flex items-center justify-center">
          <span v-if="flashTerm" class="text-7xl sm:text-8xl font-extrabold font-heading tabular-nums text-slate-800 dark:text-white">{{ flashTerm }}</span>
          <span v-else class="w-3 h-3 rounded-full bg-slate-300 dark:bg-vikinger-dark-50"></span>
        </div>
      </template>

      <!-- input -->
      <template v-else-if="phase === 'input'">
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">ผลรวมคือเท่าไร?</p>
        <div class="text-5xl font-extrabold font-heading text-vikinger-purple dark:text-vikinger-cyan tabular-nums min-h-[60px] flex items-center">
          {{ input || '?' }}
        </div>
      </template>

      <!-- correct / wrong -->
      <template v-else>
        <Icon :icon="phase === 'correct' ? 'fluent:checkmark-circle-24-filled' : 'fluent:dismiss-circle-24-filled'" class="w-10 h-10 mb-2" :class="phase === 'correct' ? 'text-emerald-500' : 'text-rose-500'" />
        <p class="font-bold text-lg" :class="phase === 'correct' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
          {{ phase === 'correct' ? 'ถูกต้อง!' : `คำตอบคือ ${round?.answer}` }}
        </p>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 tabular-nums break-words">{{ recap }}</p>
      </template>
    </div>

    <!-- Cancel (ตอน countdown/flash) -->
    <div v-if="phase === 'countdown' || phase === 'flash'" class="mb-3 text-center">
      <button type="button" @click="abort" class="min-h-[40px] px-5 rounded-vikinger bg-gray-100 dark:bg-vikinger-dark-100 border border-gray-200 dark:border-vikinger-dark-50/60 text-slate-500 dark:text-slate-300 text-sm font-medium inline-flex items-center gap-1.5">
        <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4" /> ยกเลิก
      </button>
    </div>

    <!-- Keypad (เฉพาะตอน input) -->
    <div v-if="phase === 'input'" class="glass-card rounded-vikinger-xl p-4 shadow-card dark:shadow-card-dark mb-3">
      <div class="grid grid-cols-3 gap-2 max-w-xs mx-auto">
        <button v-for="d in [1,2,3,4,5,6,7,8,9]" :key="d" type="button" @click="appendDigit(d)" class="h-14 rounded-vikinger bg-white dark:bg-vikinger-dark-200/90 border border-gray-200/80 dark:border-vikinger-dark-50/60 text-2xl font-bold font-heading shadow-sm active:bg-vikinger-purple/10">{{ d }}</button>
        <button type="button" @click="clearInput" class="h-14 rounded-vikinger bg-gray-100 dark:bg-vikinger-dark-100 border border-gray-200 dark:border-vikinger-dark-50/60 text-slate-600 dark:text-slate-300 font-bold text-sm">ล้าง</button>
        <button type="button" @click="appendDigit(0)" class="h-14 rounded-vikinger bg-white dark:bg-vikinger-dark-200/90 border border-gray-200/80 dark:border-vikinger-dark-50/60 text-2xl font-bold font-heading shadow-sm">0</button>
        <button type="button" @click="backspace" class="h-14 rounded-vikinger bg-gray-100 dark:bg-vikinger-dark-100 border border-gray-200 dark:border-vikinger-dark-50/60 text-slate-600 dark:text-slate-300 flex items-center justify-center">
          <Icon icon="fluent:backspace-24-regular" class="w-6 h-6" />
        </button>
      </div>
      <div class="mt-3 max-w-xs mx-auto grid grid-cols-3 gap-2">
        <button type="button" @click="replay" class="col-span-1 min-h-[44px] rounded-vikinger bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/50 text-amber-700 dark:text-amber-300 font-medium text-xs flex items-center justify-center gap-1">
          <Icon icon="fluent:replay-24-regular" class="w-4 h-4" /> ดูใหม่
        </button>
        <button type="button" @click="submit" :disabled="input === ''" class="col-span-2 min-h-[44px] rounded-vikinger bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-bold font-heading text-lg shadow-lg shadow-emerald-500/20 active:scale-[0.98] disabled:opacity-40 flex items-center justify-center gap-2">
          ตอบ <Icon icon="fluent:checkmark-24-filled" class="w-5 h-5" />
        </button>
      </div>
    </div>

    <!-- Next (หลังตอบ) -->
    <div v-else-if="phase === 'correct' || phase === 'wrong'" class="mb-3">
      <button type="button" @click="startRound" class="w-full min-h-[48px] rounded-vikinger bg-gradient-vikinger text-white font-bold font-heading text-lg shadow-vikinger active:scale-[0.98] flex items-center justify-center gap-2">
        ข้อถัดไป <Icon icon="fluent:arrow-right-24-filled" class="w-5 h-5" />
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
