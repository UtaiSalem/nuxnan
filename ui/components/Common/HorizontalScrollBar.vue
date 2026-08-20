<script setup lang="ts">
import { ref, shallowRef, onMounted, onUnmounted, watch, nextTick } from 'vue'

/**
 * แถบเลื่อนแนวนอนที่มองเห็นตลอดเวลา
 *
 * เบราว์เซอร์บนมือถือใช้ overlay scrollbar ที่ไม่กินพื้นที่และจางหายเอง
 * ผู้ใช้จึงไม่รู้ว่ากล่องนั้นเลื่อนได้ คอมโพเนนต์นี้วาดแถบเลื่อนของตัวเอง
 * ที่ลากได้จริงและซิงก์กับ scrollLeft ของ target
 */
const props = defineProps<{
  /** element ที่มี overflow-x-auto */
  target: HTMLElement | null
  /** ข้อความบอกใบ้ใต้แถบ (เช่น "ปัดซ้าย-ขวาเพื่อดูคะแนน") */
  hint?: string
}>()

const trackRef = ref<HTMLElement | null>(null)
const el = shallowRef<HTMLElement | null>(null)

const visible = ref(false)
const thumbLeft = ref(0)
const thumbWidth = ref(0)
const dragging = ref(false)

const MIN_THUMB = 40

const measure = () => {
  const node = el.value
  if (!node) {
    visible.value = false
    return
  }

  const maxScroll = node.scrollWidth - node.clientWidth

  // ตัดสินใจ "แสดง/ไม่แสดง" จากตัวกล่องเท่านั้น ห้ามพึ่งความกว้างของ track
  // เพราะ track อยู่ใต้ v-show ตอนซ่อนจะกว้าง 0 แล้วจะวนตายไม่มีวันโผล่
  visible.value = maxScroll > 1
  if (!visible.value) return

  const track = trackRef.value
  const trackW = track?.clientWidth ?? 0
  if (trackW <= 0) {
    // เพิ่งถูกสั่งให้แสดง รอ DOM อัปเดตก่อนค่อยวัดใหม่
    nextTick(measure)
    return
  }

  const ratio = node.clientWidth / node.scrollWidth
  thumbWidth.value = Math.max(MIN_THUMB, Math.round(trackW * ratio))
  thumbLeft.value = Math.round((node.scrollLeft / maxScroll) * (trackW - thumbWidth.value))
}

const scrollToTrackX = (clientX: number) => {
  const node = el.value
  const track = trackRef.value
  if (!node || !track) return

  const rect = track.getBoundingClientRect()
  const maxScroll = node.scrollWidth - node.clientWidth
  const usable = rect.width - thumbWidth.value
  if (usable <= 0) return

  const x = clientX - rect.left - thumbWidth.value / 2
  const clamped = Math.min(Math.max(x, 0), usable)
  node.scrollLeft = (clamped / usable) * maxScroll
}

const onThumbDown = (event: PointerEvent) => {
  const node = el.value
  if (!node) return
  dragging.value = true
  // scroll-behavior: smooth ทำให้ลากแล้วหน่วง ปิดชั่วคราวระหว่างลาก
  node.style.scrollBehavior = 'auto'
  try {
    ;(event.currentTarget as HTMLElement).setPointerCapture(event.pointerId)
  } catch {
    /* บางเบราว์เซอร์ปฏิเสธ pointer capture — ลากต่อได้ตามปกติ */
  }
  event.preventDefault()
}

const onThumbMove = (event: PointerEvent) => {
  if (!dragging.value) return
  scrollToTrackX(event.clientX)
}

const onThumbUp = (event: PointerEvent) => {
  if (!dragging.value) return
  dragging.value = false
  if (el.value) el.value.style.scrollBehavior = ''
  try {
    ;(event.currentTarget as HTMLElement).releasePointerCapture(event.pointerId)
  } catch {
    /* ไม่เคย capture ไว้ */
  }
}

const onTrackClick = (event: MouseEvent) => {
  if (dragging.value) return
  scrollToTrackX(event.clientX)
}

let resizeObserver: ResizeObserver | null = null

const detach = () => {
  el.value?.removeEventListener('scroll', measure)
  resizeObserver?.disconnect()
  resizeObserver = null
}

const attach = (node: HTMLElement | null) => {
  detach()
  el.value = node
  if (!node) {
    visible.value = false
    return
  }
  node.addEventListener('scroll', measure, { passive: true })
  if (typeof ResizeObserver !== 'undefined') {
    // จับทั้งขนาดกล่องและขนาดตารางข้างใน (คอลัมน์เพิ่ม/ลดได้)
    resizeObserver = new ResizeObserver(measure)
    resizeObserver.observe(node)
    if (node.firstElementChild) resizeObserver.observe(node.firstElementChild)
  }
  measure()
}

watch(() => props.target, attach, { immediate: true })

onMounted(() => {
  window.addEventListener('resize', measure)
  measure()
})

onUnmounted(() => {
  window.removeEventListener('resize', measure)
  detach()
})

defineExpose({ measure })
</script>

<template>
  <div v-show="visible" class="px-3 pt-2 pb-3 sm:px-4">
    <div
      ref="trackRef"
      @click="onTrackClick"
      class="relative h-3 w-full rounded-full bg-gray-200 dark:bg-gray-700 cursor-pointer touch-none"
    >
      <div
        @pointerdown="onThumbDown"
        @pointermove="onThumbMove"
        @pointerup="onThumbUp"
        @pointercancel="onThumbUp"
        :style="{ width: thumbWidth + 'px', transform: `translateX(${thumbLeft}px)` }"
        :class="[
          'absolute left-0 top-0 h-3 rounded-full transition-colors touch-none',
          dragging ? 'bg-indigo-600' : 'bg-indigo-400 hover:bg-indigo-500'
        ]"
      ></div>
    </div>
    <p v-if="hint" class="mt-1.5 text-center text-[11px] text-gray-500 dark:text-gray-400">
      {{ hint }}
    </p>
  </div>
</template>
