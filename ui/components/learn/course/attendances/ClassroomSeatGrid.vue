<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { type SeatData, buildSummary, isArriving } from '~/composables/useAttendanceStatus'
import SeatCard from '~/components/learn/course/attendances/SeatCard.vue'

interface Props {
  seats: SeatData[]
  cols: number
  rows: number
  totalSeats: number
  isCourseAdmin: boolean
  serverTimeMs: number
  selectedMemberId: number | null
  title?: string
  subtitle?: string
  clock?: { range: string; label: string; value: string; tone: string } | null
  teacher?: { name: string; avatar: string | null } | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'select', memberId: number): void
}>()

const summary = computed(() => buildSummary(props.seats))

// Chalk colors for the countdown line on the blackboard
const clockToneClass = computed(() => {
  switch (props.clock?.tone) {
    case 'sky': return 'text-sky-300'
    case 'emerald': return 'text-emerald-300'
    case 'amber': return 'text-amber-300'
    case 'red': return 'text-red-300 animate-pulse'
    default: return 'text-[#BFE0C9]'
  }
})

// Tailwind needs literal class names — map half-width column count explicitly.
// Big groups (10 logical cols) wrap down to fewer visual columns on small screens.
const halfGridClass = computed(() => {
  if (props.cols >= 10) return 'grid-cols-3 sm:grid-cols-4 xl:grid-cols-5'
  if (props.cols >= 6) return 'grid-cols-3'
  return 'grid-cols-2'
})

/**
 * Split seats into left and right halves for the grid with center aisle.
 * Left half = cols/2 columns, right half = cols/2 columns.
 * We fill left-to-right, top-to-bottom within each half.
 */
const leftSeats = computed(() => {
  const half = Math.floor(props.cols / 2)
  return props.seats.filter((_, i) => i % props.cols < half)
})

const rightSeats = computed(() => {
  const half = Math.floor(props.cols / 2)
  return props.seats.filter((_, i) => i % props.cols >= half)
})

const { getAvatarUrl } = useAvatar()
const teacherAvatar = computed(() =>
  props.teacher ? getAvatarUrl({ avatar: props.teacher.avatar, name: props.teacher.name }) : '',
)

const emptyLeftSlots = computed(() =>
  Math.max(0, Math.ceil(props.totalSeats / 2) - leftSeats.value.length),
)
const emptyRightSlots = computed(() =>
  Math.max(0, Math.ceil(props.totalSeats / 2) - rightSeats.value.length),
)

const prefersReducedMotion = () =>
  typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches

const floorRef = ref<HTMLElement | null>(null)

// ─── Student walk-in animation ────────────────────────
// When a seat newly enters the "arriving" state, a small figure walks in
// from the back door, up the aisle, and over to their desk before sitting.
interface Walker {
  id: number
  name: string
  avatar: string | null
}

const WALKER_W = 48
const walkers = ref<Walker[]>([])
const walkingIds = computed(() => new Set(walkers.value.map(w => w.id)))
const seenArrivals = new Set<number>()
const startedWalks = new Set<number>()
let arrivalsInitialized = false

watch(
  () => [props.seats, props.serverTimeMs] as const,
  () => {
    if (props.seats.length === 0) return
    const arrivingNow = props.seats.filter(s => isArriving(s, props.serverTimeMs))

    // First load is the baseline — don't replay walks for students already seated
    if (!arrivalsInitialized) {
      arrivingNow.forEach(s => seenArrivals.add(s.course_member_id))
      arrivalsInitialized = true
      return
    }

    for (const s of arrivingNow) {
      if (seenArrivals.has(s.course_member_id)) continue
      seenArrivals.add(s.course_member_id)
      if (prefersReducedMotion()) continue
      walkers.value.push({ id: s.course_member_id, name: s.name, avatar: s.avatar })
    }
  },
  { immediate: true },
)

function walkerAvatar(w: Walker) {
  return getAvatarUrl({ avatar: w.avatar, name: w.name })
}

function removeWalker(id: number) {
  walkers.value = walkers.value.filter(w => w.id !== id)
}

function startWalker(el: unknown, walker: Walker) {
  const node = el as HTMLElement | null
  if (!node || startedWalks.has(walker.id)) return
  const floor = floorRef.value
  if (!floor) return

  const seatEl = floor.querySelector<HTMLElement>(`[data-member-id="${walker.id}"]`)
  if (!seatEl) {
    removeWalker(walker.id)
    return
  }
  startedWalks.add(walker.id)

  const floorRect = floor.getBoundingClientRect()
  const seatRect = seatEl.getBoundingClientRect()
  const doorX = floorRect.width / 2 - WALKER_W / 2
  const doorY = floorRect.height - 64
  const seatX = seatRect.left - floorRect.left + seatRect.width / 2 - WALKER_W / 2
  const seatY = seatRect.top - floorRect.top

  node.style.left = `${doorX}px`
  node.style.top = `${doorY}px`

  const anim = node.animate(
    [
      { left: `${doorX}px`, top: `${doorY}px`, opacity: 0 },
      { left: `${doorX}px`, top: `${doorY}px`, opacity: 1, offset: 0.08 },
      { left: `${doorX}px`, top: `${seatY}px`, offset: 0.6 },
      { left: `${seatX}px`, top: `${seatY}px`, opacity: 1 },
    ],
    { duration: 2600, easing: 'linear', fill: 'forwards' },
  )
  const done = () => removeWalker(walker.id)
  anim.onfinish = done
  anim.oncancel = done
}

// ─── Teacher: random wandering with stops beside desks ─
const teacherRef = ref<HTMLElement | null>(null)
const TEACHER_W = 56
const TEACHER_FRONT_Y = -60
const TEACHER_SPEED = 0.055 // px per ms

let teacherActive = false
let teacherStarted = false
let teacherAnim: Animation | null = null
let teacherTimer: ReturnType<typeof setTimeout> | null = null

const rand = (min: number, max: number) => min + Math.random() * (max - min)

function teacherWait(ms: number) {
  return new Promise<void>((resolve) => {
    teacherTimer = setTimeout(resolve, ms)
  })
}

function teacherMoveTo(el: HTMLElement, x: number, y: number) {
  const curX = parseFloat(el.style.left || '8')
  const curY = parseFloat(el.style.top || `${TEACHER_FRONT_Y}`)
  const dist = Math.hypot(x - curX, y - curY)
  const duration = Math.max(300, dist / TEACHER_SPEED)

  return new Promise<void>((resolve) => {
    teacherAnim = el.animate(
      [
        { left: `${curX}px`, top: `${curY}px` },
        { left: `${x}px`, top: `${y}px` },
      ],
      { duration, easing: 'linear', fill: 'forwards' },
    )
    const settle = () => {
      el.style.left = `${x}px`
      el.style.top = `${y}px`
      resolve()
    }
    teacherAnim.onfinish = settle
    teacherAnim.oncancel = () => resolve()
  })
}

async function teacherLoop() {
  const el = teacherRef.value
  const floor = floorRef.value
  if (!el || !floor || prefersReducedMotion()) return

  el.style.left = '8px'
  el.style.top = `${TEACHER_FRONT_Y}px`

  while (teacherActive) {
    const W = floor.clientWidth
    const H = floor.clientHeight
    const centerX = W / 2 - TEACHER_W / 2

    // Walk across the front of the room
    await teacherMoveTo(el, W - TEACHER_W - 8, TEACHER_FRONT_Y)
    if (!teacherActive) return
    await teacherWait(rand(600, 1600))

    // Head to the aisle, then wander down with random stops beside desks
    await teacherMoveTo(el, centerX, TEACHER_FRONT_Y)
    if (!teacherActive) return

    const stopCount = Math.random() < 0.5 ? 1 : 2
    let lastY = 0
    for (let i = 0; i < stopCount; i++) {
      const y = rand(H * (0.15 + i * 0.3), H * (0.45 + i * 0.35))
      if (y <= lastY) continue
      await teacherMoveTo(el, centerX, y)
      if (!teacherActive) return
      lastY = y
      await teacherWait(rand(1200, 3200)) // pause beside a desk
      if (!teacherActive) return
    }

    // Sometimes continue to the back of the room
    if (Math.random() < 0.5) {
      await teacherMoveTo(el, centerX, H - 100)
      if (!teacherActive) return
      await teacherWait(rand(800, 2200))
      if (!teacherActive) return
    }

    // Walk back to the front and start over
    await teacherMoveTo(el, centerX, TEACHER_FRONT_Y)
    if (!teacherActive) return
    await teacherMoveTo(el, 8, TEACHER_FRONT_Y)
    if (!teacherActive) return
    await teacherWait(rand(600, 1600))
  }
}

function maybeStartTeacher() {
  if (teacherStarted || !props.teacher) return
  nextTick(() => {
    if (teacherStarted || !teacherRef.value || !floorRef.value) return
    teacherStarted = true
    teacherActive = true
    teacherLoop()
  })
}

watch(() => props.teacher, maybeStartTeacher)

onMounted(maybeStartTeacher)

onUnmounted(() => {
  teacherActive = false
  teacherAnim?.cancel()
  if (teacherTimer) clearTimeout(teacherTimer)
})
</script>

<template>
  <!-- Farm-game classroom scene: grass + wooden floor (colors fixed in both modes) -->
  <div class="rounded-vikinger overflow-hidden bg-[#7CC74E] p-3 sm:p-5">
    <!-- Blackboard with session info + summary -->
    <div class="flex justify-center mb-4 sm:mb-5">
      <div class="w-full sm:w-4/5 bg-[#9A6B3F] rounded-xl p-1.5 shadow-md">
        <div class="bg-[#2F5D46] rounded-lg px-3 py-2.5 sm:px-5 sm:py-3 text-center space-y-1">
          <p class="text-[#EAF5EC] text-sm sm:text-base font-semibold truncate">
            {{ title || 'เช็คชื่อ' }}
          </p>
          <p v-if="subtitle" class="text-[#BFE0C9] text-[11px] sm:text-xs truncate">
            {{ subtitle }}
          </p>
          <div
            v-if="clock"
            class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-[11px] sm:text-xs"
          >
            <span class="text-[#BFE0C9]">{{ clock.range }}</span>
            <span class="font-semibold" :class="clockToneClass">
              {{ clock.label }}<template v-if="clock.value">
                <span class="font-mono ml-1">{{ clock.value }}</span>
              </template>
            </span>
          </div>
          <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-[11px] sm:text-xs font-semibold pt-0.5">
            <span class="text-emerald-300">มา {{ summary.present }}</span>
            <span class="text-amber-300">สาย {{ summary.late }}</span>
            <span class="text-sky-300">ลา {{ summary.leave }}</span>
            <span class="text-red-300">ขาด {{ summary.absent }}</span>
            <span class="text-[#BFE0C9]">{{ summary.present + summary.late }}/{{ summary.total }} คนมาเรียน</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Front walkway space reserved for the teacher -->
    <div v-if="teacher" class="h-14 mb-1"></div>

    <!-- Wooden floor with plank lines -->
    <div
      ref="floorRef"
      class="relative rounded-xl border-2 border-[#B98A52] bg-[#E9C58F] [background-image:repeating-linear-gradient(0deg,transparent,transparent_26px,#D9AE74_26px,#D9AE74_27px)] p-2 sm:p-4"
    >
      <!-- Teacher wandering: front of room + down the aisle with random stops -->
      <div
        v-if="teacher"
        ref="teacherRef"
        class="absolute z-20 flex flex-col items-center w-14 pointer-events-none"
        style="left: 8px; top: -60px;"
        aria-hidden="true"
      >
        <div class="teacher-bob flex flex-col items-center">
          <img
            :src="teacherAvatar"
            :alt="teacher.name"
            class="w-8 h-8 rounded-full object-cover border-2 border-[#7A5230] bg-white"
          />
          <span class="w-7 h-3 rounded-t-lg bg-[#7A5230] -mt-1"></span>
        </div>
        <span class="text-[10px] font-semibold text-[#4A3220] max-w-[56px] truncate">
          {{ teacher.name }}
        </span>
      </div>

      <!-- Students walking in from the back door to their desks -->
      <div
        v-for="wk in walkers"
        :key="'walker-' + wk.id"
        :ref="el => startWalker(el, wk)"
        class="absolute z-30 flex flex-col items-center w-12 pointer-events-none"
        aria-hidden="true"
      >
        <div class="teacher-bob flex flex-col items-center">
          <img
            :src="walkerAvatar(wk)"
            :alt="wk.name"
            class="w-7 h-7 rounded-full object-cover border-2 border-emerald-400 ring-2 ring-emerald-300/60 bg-white"
          />
          <span class="w-6 h-2.5 rounded-t-lg bg-emerald-600 -mt-1"></span>
        </div>
        <span class="text-[10px] font-semibold text-[#4A3220] max-w-[48px] truncate">
          {{ wk.name }}
        </span>
      </div>

      <div class="grid grid-cols-[1fr_auto_1fr] gap-2 sm:gap-4">
        <!-- Left half of classroom -->
        <div class="grid gap-1.5 sm:gap-2" :class="halfGridClass">
          <div
            v-for="seat in leftSeats"
            :key="seat.course_member_id"
            class="flex justify-center"
            :data-member-id="seat.course_member_id"
          >
            <div class="w-full max-w-[88px]">
              <SeatCard
                :seat="seat"
                :is-course-admin="isCourseAdmin"
                :server-time-ms="serverTimeMs"
                :selected="seat.course_member_id === selectedMemberId"
                :walking="walkingIds.has(seat.course_member_id)"
                @select="emit('select', seat.course_member_id)"
              />
            </div>
          </div>
          <div
            v-for="n in emptyLeftSlots"
            :key="'empty-l-' + n"
            class="flex justify-center"
          >
            <div class="w-full max-w-[88px] h-[84px] rounded-xl border-2 border-dashed border-[#B98A52]/50"></div>
          </div>
        </div>

        <!-- Center aisle (wide enough for the teacher to walk through) -->
        <div class="w-8 sm:w-12 rounded-lg bg-[#F2D8AC] border-x border-[#E3C089] relative" aria-hidden="true">
          <div class="absolute inset-y-2 left-1/2 -translate-x-1/2 border-l-2 border-dashed border-[#E3C089]"></div>
          <!-- Back door at the end of the aisle -->
          <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-8 h-10 bg-[#9A6B3F] border-2 border-[#7A5230] rounded-t-lg">
            <span class="absolute right-1 top-1/2 w-1 h-1 rounded-full bg-[#E9C58F]"></span>
          </div>
        </div>

        <!-- Right half of classroom -->
        <div class="grid gap-1.5 sm:gap-2" :class="halfGridClass">
          <div
            v-for="seat in rightSeats"
            :key="seat.course_member_id"
            class="flex justify-center"
            :data-member-id="seat.course_member_id"
          >
            <div class="w-full max-w-[88px]">
              <SeatCard
                :seat="seat"
                :is-course-admin="isCourseAdmin"
                :server-time-ms="serverTimeMs"
                :selected="seat.course_member_id === selectedMemberId"
                :walking="walkingIds.has(seat.course_member_id)"
                @select="emit('select', seat.course_member_id)"
              />
            </div>
          </div>
          <div
            v-for="n in emptyRightSlots"
            :key="'empty-r-' + n"
            class="flex justify-center"
          >
            <div class="w-full max-w-[88px] h-[84px] rounded-xl border-2 border-dashed border-[#B98A52]/50"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.teacher-bob {
  animation: teacher-bob 0.45s ease-in-out infinite alternate;
}

@keyframes teacher-bob {
  from { transform: translateY(0); }
  to { transform: translateY(-3px); }
}

@media (prefers-reduced-motion: reduce) {
  .teacher-bob {
    animation: none;
  }
}
</style>
