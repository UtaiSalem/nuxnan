<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import {
  useAttendancePhaser,
  type AttendanceDoorState,
  type AttendanceSceneState,
  type AttendanceTeacher,
} from '~/composables/useAttendancePhaser'
import type { SeatData, SimulatorData } from '~/composables/useAttendanceStatus'

interface Props {
  seats: SeatData[]
  totalSeats: number
  attendance: SimulatorData['attendance'] | null
  authMemberId: number | null
  selectedMemberId: number | null
  isCourseAdmin: boolean
  clock: { range: string; label: string; value: string; tone: string } | null
  serverTimeMs: number
  doorState: AttendanceDoorState
  teacher?: AttendanceTeacher | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'seat-select', memberId: number): void
  (e: 'door-click'): void
  (e: 'scene-ready'): void
  (e: 'load-error', error: unknown): void
}>()

const containerRef = ref<HTMLElement | null>(null)
const containerId = `attendance-phaser-${Math.random().toString(36).slice(2)}`
const resizeObserver = ref<ResizeObserver | null>(null)
const dynamicHeight = ref(640)

const {
  createGame,
  updateSceneData,
  resizeGame,
  destroyGame,
} = useAttendancePhaser()

const sceneState = computed<AttendanceSceneState>(() => ({
  seats: props.seats,
  totalSeats: props.totalSeats,
  attendance: props.attendance,
  authMemberId: props.authMemberId,
  selectedMemberId: props.selectedMemberId,
  isCourseAdmin: props.isCourseAdmin,
  clock: props.clock,
  serverTimeMs: props.serverTimeMs,
  doorState: props.doorState,
  teacher: props.teacher ?? null,
}))

const { getAvatarUrl } = useAvatar()

async function initPhaser() {
  if (!containerRef.value) return

  try {
    const { width } = containerRef.value.getBoundingClientRect()
    await createGame(
      containerId,
      Math.max(320, Math.round(width)),
      dynamicHeight.value,
      sceneState.value,
      {
        onSeatSelect: (memberId) => emit('seat-select', memberId),
        onDoorClick: () => emit('door-click'),
        onSceneReady: () => emit('scene-ready'),
        onHeightChange: (h) => {
          dynamicHeight.value = h
          const { width: w } = containerRef.value!.getBoundingClientRect()
          resizeGame(Math.round(w), h)
        },
        getAvatarUrl: (user) => {
          const url = getAvatarUrl(user)
          // Do not pass ui-avatars.com to Phaser due to their current CORS bug
          if (url && url.includes('ui-avatars.com')) {
            return null as any
          }
          return url
        },
      },
    )
  } catch (error) {
    emit('load-error', error)
  }
}

onMounted(async () => {
  await initPhaser()

  if (typeof ResizeObserver !== 'undefined' && containerRef.value) {
    // Debounce + hysteresis: only re-render if width moved >24px since the last
    // applied resize. Prevents zoning pattern flicker while the user drags.
    let lastAppliedWidth = 0
    let resizeTimer: ReturnType<typeof setTimeout> | null = null
    resizeObserver.value = new ResizeObserver((entries) => {
      const entry = entries[0]
      if (!entry) return
      const w = Math.max(320, Math.round(entry.contentRect.width))
      if (Math.abs(w - lastAppliedWidth) < 24 && lastAppliedWidth !== 0) return
      if (resizeTimer) clearTimeout(resizeTimer)
      resizeTimer = setTimeout(() => {
        lastAppliedWidth = w
        resizeGame(w, dynamicHeight.value)
      }, 120)
    })
    resizeObserver.value.observe(containerRef.value)
  }
})

watch(sceneState, (next) => {
  updateSceneData(next)
}, { deep: true })

onUnmounted(() => {
  resizeObserver.value?.disconnect()
  destroyGame()
})
</script>

<template>
  <div
    ref="containerRef"
    class="w-full rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 bg-[#7CC74E] shadow-xl transition-[height] duration-300"
    :style="{ height: `${dynamicHeight}px` }"
  >
    <div :id="containerId" class="w-full h-full" />
  </div>
</template>
