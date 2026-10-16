<script setup lang="ts">
import { computed } from 'vue'
import {
  type HandGesture,
  type Finger,
  digitToHand,
  handValue,
  fingersUp
} from '~/components/games/fingers/fingerEngine'

/**
 * HandDisplay — แสดงท่ามือ 1 ข้างของเลข 0–9 (display-only)
 * ค่า/ท่าทางทั้งหมดมาจาก fingerEngine (digitToHand/fingersUp/handValue)
 * มือซ้าย = สะท้อนแนวนอน (หันฝ่ามือถูก) · กติกา: หัวแม่มือ=5, นิ้ว1–4
 */
const props = withDefaults(
  defineProps<{
    digit?: number
    gesture?: HandGesture
    side?: 'left' | 'right'
    label?: string
    showValue?: boolean
    compact?: boolean
  }>(),
  { side: 'right', showValue: false, compact: false }
)

const hand = computed<HandGesture>(() => props.gesture ?? digitToHand(props.digit ?? 0))
const up = computed<Finger[]>(() => fingersUp(hand.value))
const value = computed(() => handValue(hand.value))
// Geometry only; fingerEngine remains the source of every extended/folded state.
const fourFingers: { name: Finger; x: number; tip: number; width: number }[] = [
  { name: 'index', x: 28, tip: 15, width: 14 },
  { name: 'middle', x: 44, tip: 6, width: 14 },
  { name: 'ring', x: 60, tip: 12, width: 14 },
  { name: 'pinky', x: 76, tip: 27, width: 12 }
]
const isUp = (f: Finger) => up.value.includes(f)
</script>

<template>
  <div class="flex flex-col items-center gap-1.5">
    <svg
      class="hand"
      :class="{ mirror: side === 'right', compact }"
      viewBox="0 0 100 120"
      aria-hidden="true"
      focusable="false"
    >
      <!-- Raised fingers join the palm; folded fingers sit across its upper edge. -->
      <g
        v-for="finger in fourFingers"
        :key="finger.name"
        class="finger"
        :class="isUp(finger.name) ? 'on' : 'off'"
      >
        <rect
          :x="finger.x"
          :y="isUp(finger.name) ? finger.tip : 61"
          :width="finger.width"
          :height="isUp(finger.name) ? 81 - finger.tip : 23"
          :rx="finger.width / 2"
        />
        <path
          v-if="isUp(finger.name)"
          class="crease"
          :d="`M ${finger.x + 4} 49 h ${finger.width - 8}`"
        />
      </g>
      <!-- Rounded palm and wrist keep a continuous silhouette in every gesture. -->
      <path
        class="palm"
        d="M 29 66 Q 27 61 33 60 L 81 60 Q 90 60 90 71
           L 89 90 Q 88 101 79 106 L 79 116 L 41 116
           L 41 107 Q 27 100 25 87 L 24 76 Q 23 70 29 66 Z"
      />
      <g class="finger thumb" :class="isUp('thumb') ? 'on' : 'off'">
        <path
          v-if="isUp('thumb')"
          d="M 34 92 Q 27 94 22 85 L 8 64 Q 4 57 10 53
             Q 16 49 21 56 L 39 76 Q 44 84 34 92 Z"
        />
        <path
          v-else
          d="M 29 76 Q 25 70 31 67 Q 35 65 40 68
             L 60 80 Q 66 84 62 90 Q 59 94 53 91 Z"
        />
        <path class="crease" :d="isUp('thumb') ? 'M 18 68 l 6 -4' : 'M 46 77 l -4 6'" />
      </g>
      <!-- Curled knuckles stay short and muted: zero reads as a closed fist. -->
      <g v-for="finger in fourFingers" :key="`fold-${finger.name}`">
        <rect
          v-if="!isUp(finger.name)"
          class="knuckle"
          :x="finger.x"
          y="60"
          :width="finger.width"
          height="19"
          :rx="finger.width / 2"
        />
        <path
          v-if="!isUp(finger.name)"
          class="crease"
          :d="`M ${finger.x + 4} 72 h ${finger.width - 8}`"
        />
      </g>
      <path class="crease palm-crease" d="M 43 96 Q 57 102 76 94 M 48 109 Q 61 112 73 108" />
    </svg>
    <div v-if="label || showValue" class="text-center leading-tight">
      <div v-if="label" class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ label }}</div>
      <div v-if="showValue" class="text-base font-bold font-heading text-vikinger-purple dark:text-vikinger-cyan tabular-nums">{{ value }}</div>
    </div>
    <span class="sr-only">ค่า {{ value }}</span>
  </div>
</template>

<style scoped>
.hand {
  --skin: #e8b48a;
  --outline: #a96540;
  --folded: #d9a17b;
  --raised: #f6c36b;
  display: block;
  width: 100px;
  height: 120px;
  flex-shrink: 0;
  overflow: visible;
  stroke-linecap: round;
  stroke-linejoin: round;
}
.hand.compact {
  width: 66px;
  height: 79.2px;
}
.hand.mirror {
  transform: scaleX(-1);
}
.palm {
  fill: var(--skin);
  stroke: var(--outline);
  stroke-width: 1.5;
}
.finger {
  fill: var(--folded);
  stroke: var(--outline);
  stroke-width: 1.5;
  transition: fill 0.2s ease;
}
.finger.on {
  fill: var(--raised);
}
.finger rect {
  transition: y 0.2s ease, height 0.2s ease;
}
.knuckle {
  fill: var(--folded);
  stroke: var(--outline);
  stroke-width: 1.2;
}
.crease {
  fill: none;
  stroke: var(--outline);
  stroke-width: 1.1;
  opacity: 0.65;
}
:global(.dark) .hand {
  --skin: #c58c64;
  --outline: #75472f;
  --folded: #b7805d;
  --raised: #edb65c;
}
@media (prefers-reduced-motion: reduce) {
  .finger,
  .finger rect {
    transition: none;
  }
}
</style>
