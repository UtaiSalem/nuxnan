<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import {
  type Abacus,
  setAbacusValue,
  abacusValue,
  columnValue,
  toggleHeaven,
  tapEarth
} from './abacusEngine'

/**
 * VIRTUAL ABACUS — ลูกคิดญี่ปุ่น (soroban) ที่เลื่อนเม็ดได้จริง
 * ออกแบบตามบทความแผนพัฒนาเกมจินตคณิต (§2 โลกที่ 1, §3.1 ระดับ 1/4/5, §5 หน้า Abacus Training)
 * + รูปอ้างอิง SoroTouch/soroban: พื้นพาเนลมืด เม็ดสีอำพัน active=สว่างมีแสงเรือง inactive=หม่น
 *
 * - กลไกคำนวณแยกอยู่ที่ abacusEngine.ts (มีชุดทดสอบ) — component นี้ทำแค่ render + รับการแตะ
 * - mobile-first: ตรวจ 375px ก่อน · 5 หลักพอดีจอเล็ก · กรอบกว้างเกินมี overflow-x ของตัวเอง
 * - ซ่อน/โปร่งใสได้ (ghost/dim) สำหรับ progression ระดับ 4–5 → Anzan
 */

const props = withDefaults(
  defineProps<{
    /** ค่าบนลูกคิด (v-model) */
    modelValue?: number
    /** จำนวนหลัก (หน่วยอยู่ขวาสุด) */
    columns?: number
    /** แตะเลื่อนเม็ดได้ไหม (false = โชว์โจทย์อย่างเดียว) */
    interactive?: boolean
    /** โชว์ค่าตัวเลขใต้แต่ละหลัก (โหมด Learn) */
    showValues?: boolean
    /** ลูกคิดโปร่งใส (ระดับ 4 — ฝึกสร้างภาพในใจ) */
    ghost?: boolean
    /** ความชัด 0–1 (ไล่จาง → Anzan) */
    dim?: number
  }>(),
  {
    modelValue: 0,
    columns: 5,
    interactive: true,
    showValues: false,
    ghost: false,
    dim: 1
  }
)

const emit = defineEmits<{
  'update:modelValue': [value: number]
  'column-change': [payload: { index: number; value: number }]
}>()

const maxValue = computed(() => 10 ** Math.max(1, props.columns) - 1)

function clampValue(v: number): number {
  return Math.max(0, Math.min(maxValue.value, Math.floor(Number.isFinite(v) ? v : 0)))
}

/** สถานะเม็ดภายใน — index 0 = หลักหน่วย (ขวาสุด) */
const cols = ref<Abacus>(setAbacusValue(props.columns, clampValue(props.modelValue)))

// sync เมื่อ prop เปลี่ยนจากภายนอก (controlled) — ถ้าไม่ bind v-model ค่าภายในจะคงอยู่เอง
watch(
  () => [props.modelValue, props.columns],
  () => {
    cols.value = setAbacusValue(props.columns, clampValue(props.modelValue))
  }
)

/** ลำดับหลักสำหรับแสดง: หลักสูง (ซ้าย) → หลักหน่วย (ขวา) */
const displayIndices = computed(() =>
  Array.from({ length: Math.max(1, props.columns) }, (_, k) => props.columns - 1 - k)
)

/** slot ของเม็ดล่างตำแหน่ง p (1..4): ชิดคาน = บน, ไม่ชิด = ถูกดันลง 1 ช่อง (เว้นช่องว่าง) */
function earthSlot(p: number, earth: number): number {
  return p <= earth ? p - 1 : p
}

function beadTransform(slot: number): string {
  return `translateX(-50%) translateY(calc(var(--bh) * ${slot}))`
}

const fieldOpacity = computed(() => {
  const d = Math.max(0, Math.min(1, props.dim))
  return props.ghost ? Math.min(d, 0.2) : d
})

function commit(next: Abacus, ci: number) {
  cols.value = next
  emit('update:modelValue', abacusValue(next))
  emit('column-change', { index: ci, value: columnValue(next[ci]) })
}

function onHeaven(ci: number) {
  if (!props.interactive) return
  const next = cols.value.slice()
  next[ci] = toggleHeaven(next[ci])
  commit(next, ci)
}

function onEarth(ci: number, p: number) {
  if (!props.interactive) return
  const next = cols.value.slice()
  next[ci] = tapEarth(next[ci], p)
  commit(next, ci)
}

function colVal(ci: number): number {
  return columnValue(cols.value[ci])
}

function beadLabel(ci: number, kind: 'heaven' | 'earth', p = 0): string {
  const place = `หลักที่ ${ci + 1}`
  return kind === 'heaven' ? `${place} เม็ดบน (ค่า 5)` : `${place} เม็ดล่างที่ ${p}`
}
</script>

<template>
  <div
    class="va"
    :class="{ 'va--ghost': ghost, 'va--static': !interactive }"
    :style="{ opacity: fieldOpacity }"
  >
    <div class="va__field">
      <div
        class="va__beam-line"
        aria-hidden="true"
      ></div>
      <div
        class="va__rods"
        role="group"
        :aria-label="`ลูกคิด ${columns} หลัก ค่า ${modelValue}`"
      >
        <div
          v-for="ci in displayIndices"
          :key="ci"
          class="va__rod"
        >
          <div class="va__rail" aria-hidden="true"></div>

          <!-- เม็ดบน (heaven = 5) -->
          <div class="va__zone va__heaven">
            <button
              type="button"
              class="va__bead va__bead--heaven"
              :class="{ 'is-active': cols[ci].heaven }"
              :style="{ transform: beadTransform(cols[ci].heaven ? 1 : 0) }"
              :disabled="!interactive"
              :aria-pressed="cols[ci].heaven"
              :aria-label="beadLabel(ci, 'heaven')"
              @click="onHeaven(ci)"
            ></button>
          </div>

          <!-- เม็ดล่าง (earth = 1 ต่อเม็ด) -->
          <div class="va__zone va__earth">
            <button
              v-for="p in 4"
              :key="p"
              type="button"
              class="va__bead va__bead--earth"
              :class="{ 'is-active': p <= cols[ci].earth }"
              :style="{ transform: beadTransform(earthSlot(p, cols[ci].earth)) }"
              :disabled="!interactive"
              :aria-pressed="p <= cols[ci].earth"
              :aria-label="beadLabel(ci, 'earth', p)"
              @click="onEarth(ci, p)"
            ></button>
          </div>

          <div v-if="showValues" class="va__val tabular-nums">{{ colVal(ci) }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.va {
  --bh: 22px;      /* ความสูงต่อ 1 ช่องเม็ด */
  --bw: 28px;      /* ความกว้างเม็ด */
  --rod-w: 38px;   /* ความกว้างหลัก */
  --pad-top: 16px; /* padding บนของพาเนล */
  display: inline-block;
  max-width: 100%;
  transition: opacity 0.25s ease;
}
@media (min-width: 640px) {
  .va {
    --bh: 26px;
    --bw: 36px;
    --rod-w: 46px;
  }
}

.va__field {
  position: relative;
  padding: var(--pad-top) 12px 12px;
  border-radius: 18px;
  background: linear-gradient(160deg, #1f2740, #10162a);
  box-shadow:
    inset 0 2px 12px rgba(0, 0, 0, 0.5),
    0 4px 14px rgba(0, 0, 0, 0.18);
  max-width: 100%;
}

.va__rods {
  display: flex;
  justify-content: center;
  gap: 6px;
  overflow-x: auto;
  scrollbar-width: thin;
}

.va__rod {
  position: relative;
  flex: 0 0 auto;
  width: var(--rod-w);
}

/* รางแนวตั้ง (คร่อมทั้งเม็ดบน 2 ช่อง + เม็ดล่าง 5 ช่อง) */
.va__rail {
  position: absolute;
  left: 50%;
  top: 0;
  transform: translateX(-50%);
  width: 2px;
  height: calc(var(--bh) * 7);
  background: rgba(148, 163, 184, 0.35);
  border-radius: 2px;
}

.va__zone {
  position: relative;
}
.va__heaven {
  height: calc(var(--bh) * 2);
}
.va__earth {
  height: calc(var(--bh) * 5);
}

/* คานแนวนอน — ต่อเนื่องทุกหลัก อยู่ระหว่างเม็ดบน/ล่าง */
.va__beam-line {
  position: absolute;
  left: 8px;
  right: 8px;
  top: calc(var(--pad-top) + var(--bh) * 2 - 2px);
  height: 5px;
  border-radius: 3px;
  background: linear-gradient(90deg, #e2e8f0, #94a3b8);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.45);
  z-index: 2;
}

.va__bead {
  position: absolute;
  left: 50%;
  top: 0;
  width: var(--bw);
  height: calc(var(--bh) - 3px);
  padding: 0;
  border: 0;
  border-radius: 45% / 50%;
  cursor: pointer;
  background: linear-gradient(145deg, #9a4a1b, #6b2e10);
  box-shadow:
    inset 0 1px 1px rgba(255, 255, 255, 0.22),
    0 1px 2px rgba(0, 0, 0, 0.4);
  transition:
    transform 0.18s cubic-bezier(0.4, 0, 0.2, 1),
    background 0.18s ease,
    box-shadow 0.18s ease;
  touch-action: manipulation;
  z-index: 1;
}
.va__bead.is-active {
  background: linear-gradient(145deg, #fcd34d, #f59e0b);
  box-shadow:
    inset 0 1px 2px rgba(255, 255, 255, 0.55),
    0 0 10px rgba(245, 158, 11, 0.55),
    0 1px 2px rgba(0, 0, 0, 0.4);
}
.va__bead:not(:disabled):hover {
  filter: brightness(1.08);
}
.va__bead:not(:disabled):active {
  filter: brightness(0.95);
}
.va__bead:disabled {
  cursor: default;
}
.va__bead:focus-visible {
  outline: 2px solid #38bdf8;
  outline-offset: 2px;
}

/* ลูกคิดโปร่งใส (ระดับ 4 → Anzan) */
.va--ghost .va__bead {
  background: transparent;
  border: 1.5px solid rgba(148, 163, 184, 0.5);
  box-shadow: none;
}
.va--ghost .va__bead.is-active {
  border-color: rgba(251, 191, 36, 0.85);
}

.va__val {
  margin-top: 8px;
  text-align: center;
  font-weight: 700;
  font-size: 13px;
  line-height: 1;
  color: #fcd34d;
}

@media (prefers-reduced-motion: reduce) {
  .va__bead {
    transition: none;
  }
}
</style>
