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
const fourFingers: Finger[] = ['index', 'middle', 'ring', 'pinky']
const isUp = (f: Finger) => up.value.includes(f)
</script>

<template>
  <div class="flex flex-col items-center gap-1.5">
    <div class="hand" :class="[side === 'left' ? 'mirror' : '', compact ? 'compact' : '']">
      <div class="thumb" :class="isUp('thumb') ? 'on' : 'off'"></div>
      <div class="fingers">
        <div
          v-for="f in fourFingers"
          :key="f"
          class="finger"
          :class="isUp(f) ? 'on' : 'off'"
        ></div>
      </div>
      <div class="palm"></div>
    </div>
    <div v-if="label || showValue" class="text-center leading-tight">
      <div v-if="label" class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ label }}</div>
      <div v-if="showValue" class="text-base font-bold font-heading text-vikinger-purple dark:text-vikinger-cyan tabular-nums">{{ value }}</div>
    </div>
    <span class="sr-only">ค่า {{ value }}</span>
  </div>
</template>

<style scoped>
.hand {
  position: relative;
  width: 88px;
  height: 104px;
  flex-shrink: 0;
}
.hand.compact {
  width: 66px;
  height: 80px;
  transform: scale(0.95);
}
.hand.mirror {
  transform: scaleX(-1);
}
.hand.compact.mirror {
  transform: scaleX(-1) scale(0.95);
}
.fingers {
  position: absolute;
  left: 22%;
  right: 6%;
  bottom: 34%;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  height: 54%;
}
.finger {
  width: 15%;
  min-width: 11px;
  border-radius: 9999px;
  background: linear-gradient(180deg, #cbd5e1, #94a3b8);
  transition: height 0.2s ease, background 0.2s ease, opacity 0.2s ease;
}
.finger.off {
  height: 30%;
  opacity: 0.45;
}
.finger.on {
  height: 100%;
  background: linear-gradient(180deg, #fcd34d, #f59e0b);
  box-shadow: 0 0 8px rgba(245, 158, 11, 0.5);
}
.thumb {
  position: absolute;
  left: 2px;
  bottom: 22%;
  width: 13px;
  border-radius: 9999px;
  transform: rotate(38deg);
  transform-origin: bottom right;
  background: linear-gradient(180deg, #cbd5e1, #94a3b8);
  transition: height 0.2s ease, background 0.2s ease, opacity 0.2s ease;
}
.thumb.off {
  height: 16px;
  opacity: 0.45;
}
.thumb.on {
  height: 34px;
  background: linear-gradient(180deg, #fcd34d, #f59e0b);
  box-shadow: 0 0 8px rgba(245, 158, 11, 0.5);
}
.palm {
  position: absolute;
  left: 12%;
  right: 4%;
  bottom: 0;
  height: 38%;
  border-radius: 18px 18px 14px 14px;
  background: linear-gradient(180deg, #e8b48a, #cf8f5e);
}
:global(.dark) .palm {
  background: linear-gradient(180deg, #b9794a, #8a5a34);
}
</style>
