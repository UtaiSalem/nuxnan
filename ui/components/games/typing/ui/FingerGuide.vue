<script setup lang="ts">
// Schematic two-hand finger guide for Key Training.
// Lights up the finger that should press the current target key.
// fingerIndex: 0=L.Pinky 1=L.Ring 2=L.Middle 3=L.Index
//              4=R.Index 5=R.Middle 6=R.Ring 7=R.Pinky 8=Thumb

const props = withDefaults(defineProps<{
  fingerIndex: number | null
  status?: 'correct' | 'incorrect' | 'none'
}>(), {
  status: 'none',
})

const FINGER_COLORS = [
  '#ef4444', '#f97316', '#fbbf24', '#22c55e', // L.Pinky / Ring / Middle / Index
  '#2dd4bf', '#3b82f6', '#8b5cf6', '#ec4899', // R.Index / Middle / Ring / Pinky
  '#64748b',                                   // Thumb
]

interface FingerRect {
  f: number
  x: number
  y: number
  w: number
  h: number
  rot?: string
}

// Geometry for a compact 360×150 viewBox (left hand ~x95, right hand ~x245)
const fingers: FingerRect[] = [
  { f: 0, x: 59,  y: 62, w: 16, h: 40 },
  { f: 1, x: 79,  y: 46, w: 16, h: 56 },
  { f: 2, x: 99,  y: 40, w: 16, h: 64 },
  { f: 3, x: 119, y: 56, w: 16, h: 48 },
  { f: 8, x: 138, y: 92, w: 34, h: 16, rot: '30 146 100' },  // left thumb
  { f: 4, x: 229, y: 56, w: 16, h: 48 },
  { f: 5, x: 249, y: 40, w: 16, h: 64 },
  { f: 6, x: 269, y: 46, w: 16, h: 56 },
  { f: 7, x: 289, y: 62, w: 16, h: 40 },
  { f: 8, x: 188, y: 92, w: 34, h: 16, rot: '-30 222 100' }, // right thumb
]

function isActive(f: number) {
  return props.fingerIndex !== null && f === props.fingerIndex
}
function fillFor(f: number) {
  return isActive(f) ? FINGER_COLORS[f] : 'rgba(148,163,184,0.16)'
}
function strokeFor(f: number) {
  return isActive(f) ? FINGER_COLORS[f] : 'rgba(148,163,184,0.5)'
}
function classFor(f: number) {
  if (!isActive(f)) return ''
  if (props.status === 'correct') return 'fg-active fg-correct'
  if (props.status === 'incorrect') return 'fg-active fg-incorrect'
  return 'fg-active'
}
</script>

<template>
  <div class="w-full flex justify-center">
    <svg
      viewBox="0 0 360 150"
      class="w-full max-w-[340px] h-auto"
      role="img"
      aria-label="ไกด์นิ้วมือสำหรับคีย์ที่ต้องกด"
    >
      <!-- palms -->
      <rect x="55"  y="95" width="90" height="42" rx="14"
            fill="rgba(148,163,184,0.08)" stroke="rgba(148,163,184,0.3)" stroke-width="1.5" />
      <rect x="215" y="95" width="90" height="42" rx="14"
            fill="rgba(148,163,184,0.08)" stroke="rgba(148,163,184,0.3)" stroke-width="1.5" />

      <!-- fingers -->
      <rect
        v-for="(fg, i) in fingers"
        :key="i"
        :x="fg.x" :y="fg.y" :width="fg.w" :height="fg.h"
        :rx="fg.w / 2"
        :transform="fg.rot ? `rotate(${fg.rot})` : undefined"
        :fill="fillFor(fg.f)"
        :stroke="strokeFor(fg.f)"
        stroke-width="1.5"
        :class="classFor(fg.f)"
        class="fg-finger"
      />

      <!-- hand labels -->
      <text x="100" y="148" text-anchor="middle" font-size="10"
            fill="rgba(148,163,184,0.7)">ซ้าย</text>
      <text x="260" y="148" text-anchor="middle" font-size="10"
            fill="rgba(148,163,184,0.7)">ขวา</text>
    </svg>
  </div>
</template>

<style scoped>
.fg-finger {
  transition: fill 0.12s ease, stroke 0.12s ease;
}
.fg-active {
  filter: drop-shadow(0 0 6px currentColor);
}
.fg-correct {
  animation: fg-pop 0.3s ease;
}
.fg-incorrect {
  animation: fg-shake 0.3s ease;
}
@keyframes fg-pop {
  0%, 100% { transform: scale(1); }
  50%      { transform: scale(1.12); }
}
@keyframes fg-shake {
  0%, 100% { transform: translateX(0); }
  25%      { transform: translateX(-3px); }
  75%      { transform: translateX(3px); }
}
</style>
