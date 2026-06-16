<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { Icon } from '@iconify/vue'
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
  authMemberId?: number | null
  title?: string
  subtitle?: string
  clock?: { range: string; label: string; value: string; tone: string } | null
  teacher?: { name: string; avatar: string | null } | null
  /** When provided, the door becomes interactive for student self check-in. */
  doorState?: {
    canCheckIn: boolean
    willBeLate?: boolean
    reason?: string
    countdown?: number
  } | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'select', memberId: number): void
  (e: 'door-click'): void
}>()

// ─── Door interaction ─────────────────────────────────
const doorOpening = ref(false)
function fmtDoorDuration(ms: number) {
  const total = Math.max(0, Math.floor(ms / 1000))
  const h = Math.floor(total / 3600)
  const m = Math.floor((total % 3600) / 60)
  const s = total % 60
  const mm = String(m).padStart(2, '0')
  const ss = String(s).padStart(2, '0')
  return h > 0 ? `${h}:${mm}:${ss}` : `${mm}:${ss}`
}
const doorLabel = computed(() => {
  const d = props.doorState
  if (!d) return null
  if (d.canCheckIn) return d.willBeLate ? 'คลิกเพื่อรายงานตัว (สาย)' : 'คลิกประตูเพื่อเข้าห้องเรียน'
  switch (d.reason) {
    case 'already-checked-in': return 'คุณเข้าห้องเรียนแล้ว'
    case 'on-leave': return 'คุณลาในคาบนี้'
    case 'not-started': return `เริ่มในอีก ${fmtDoorDuration(d.countdown ?? 0)}`
    case 'ended': return 'จบคาบเรียนแล้ว'
    case 'no-data': return ''
    default: return ''
  }
})
const doorTone = computed(() => {
  const d = props.doorState
  if (!d) return 'idle'
  if (d.canCheckIn) return d.willBeLate ? 'late' : 'open'
  switch (d.reason) {
    case 'already-checked-in': return 'done'
    case 'ended': return 'closed'
    case 'on-leave': return 'leave'
    case 'not-started': return 'wait'
    default: return 'idle'
  }
})
const isDoorInteractive = computed(() => !!props.doorState?.canCheckIn && !doorOpening.value)

function handleDoorClick() {
  if (!isDoorInteractive.value) return
  doorOpening.value = true
  emit('door-click')
  // Reset after the open animation so a subsequent state (e.g. error / retry) can re-trigger
  setTimeout(() => { doorOpening.value = false }, 900)
}

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

// Responsive zoning shared with the Phaser renderer so both surfaces stay
// in lockstep when the viewport changes.
const { zoning } = useClassroomLayout()

type ZoneSlice = { seats: SeatData[]; empty: number; cols: number }
const zoneSlices = computed<ZoneSlice[]>(() => {
  const { zones, totalCols } = zoning.value
  const rows = Math.ceil(props.totalSeats / totalCols)
  return zones.map((zoneCols, zoneIdx) => {
    const offset = zones.slice(0, zoneIdx).reduce((a, b) => a + b, 0)
    const seats = props.seats.filter((_, i) => {
      const c = i % totalCols
      return c >= offset && c < offset + zoneCols
    })
    return { seats, empty: Math.max(0, rows * zoneCols - seats.length), cols: zoneCols }
  })
})

// Map zone cols → literal Tailwind class (Tailwind needs static class names).
function zoneGridClass(cols: number) {
  switch (cols) {
    case 1: return 'grid-cols-1'
    case 2: return 'grid-cols-2'
    case 3: return 'grid-cols-3'
    default: return 'grid-cols-2'
  }
}

// Wrapper grid template (3-zone vs 2-zone).
const wrapperGridClass = computed(() =>
  zoning.value.zones.length === 3
    ? 'grid grid-cols-[1fr_auto_1fr_auto_1fr]'
    : 'grid grid-cols-[1fr_auto_1fr]',
)

const { getAvatarUrl } = useAvatar()
const teacherAvatar = computed(() =>
  props.teacher ? getAvatarUrl({ avatar: props.teacher.avatar, name: props.teacher.name }) : '',
)

const prefersReducedMotion = () =>
  typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches

// Walk the offsetParent chain up to a root, returning the natural-pixel
// position. getBoundingClientRect() is post-transform; offset* is not.
function offsetWithin(el: HTMLElement, root: HTMLElement) {
  let x = 0, y = 0
  let cur: HTMLElement | null = el
  while (cur && cur !== root) {
    x += cur.offsetLeft
    y += cur.offsetTop
    cur = cur.offsetParent as HTMLElement | null
  }
  return { x, y }
}

const floorRef = ref<HTMLElement | null>(null)
const doorRef = ref<HTMLElement | null>(null)

// ─── Fit-to-viewport scaling (projector / large-screen mode) ─────
// Outer container is capped at viewport height; inner is uniformly
// scaled down via CSS transform so the entire classroom (blackboard
// → floor → door) is visible without scrolling.
const sceneOuterRef = ref<HTMLElement | null>(null)
const sceneInnerRef = ref<HTMLElement | null>(null)
const fitScale = ref(1)
// Fit-to-viewport is only active on large viewports (projector / desktop).
// On mobile/tablet, scaling down makes the check-in button too small to
// tap, so we let content flow naturally and scroll the page instead.
const FIT_MIN_WIDTH = 1024
const fitEnabled = ref(false)
let fitRO: ResizeObserver | null = null
let fitRaf = 0

function updateFitScale() {
  const outer = sceneOuterRef.value
  const inner = sceneInnerRef.value
  if (!outer || !inner) return

  // Decide whether to fit at all based on viewport width
  const enabled = typeof window !== 'undefined' && window.innerWidth >= FIT_MIN_WIDTH
  if (fitEnabled.value !== enabled) fitEnabled.value = enabled
  if (!enabled) {
    if (fitScale.value !== 1) fitScale.value = 1
    return
  }

  // Cancel any pending update and coalesce into a single frame
  if (fitRaf) cancelAnimationFrame(fitRaf)
  fitRaf = requestAnimationFrame(() => {
    fitRaf = 0
    // Measure natural height by temporarily clearing transform AND width
    // inflation (otherwise the previous frame's scale feeds back in).
    const prevTransform = inner.style.transform
    const prevWidth = inner.style.width
    inner.style.transform = 'none'
    inner.style.width = '100%'
    const naturalH = inner.scrollHeight
    inner.style.transform = prevTransform
    inner.style.width = prevWidth
    const availH = outer.clientHeight
    if (!naturalH || !availH) return
    // Constrain by height only — width is already 100% of outer.
    const next = Math.min(1, availH / naturalH)
    const clamped = Math.max(0.35, next)
    if (Math.abs(clamped - fitScale.value) > 0.005) fitScale.value = clamped
  })
}

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
  const door = doorRef.value
  if (!floor || !door) return

  const seatEl = floor.querySelector<HTMLElement>(`[data-member-id="${walker.id}"]`)
  if (!seatEl) {
    removeWalker(walker.id)
    return
  }
  startedWalks.add(walker.id)

  // Use offset chain (natural pixels) so positions stay correct under
  // the transform: scale() applied for fit-to-viewport.
  const doorOff = offsetWithin(door, floor)
  const seatOff = offsetWithin(seatEl, floor)
  const doorX = doorOff.x + door.offsetWidth / 2 - WALKER_W / 2
  const doorY = doorOff.y
  const seatX = seatOff.x + seatEl.offsetWidth / 2 - WALKER_W / 2
  const seatY = seatOff.y

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

// ─── Teacher wandering: front, back, aisle, left/right walls ─
const teacherRef = ref<HTMLElement | null>(null)
const gridStageRef = ref<HTMLElement | null>(null)
// Fallback dimensions used until the teacher element is mounted; the
// real size is read from the DOM each tick so responsive sizes track.
const TEACHER_W_DEFAULT = 56
const TEACHER_H_DEFAULT = 72
const TEACHER_SPEED = 0.07 // px per ms

let teacherActive = false
let teacherStarted = false
let teacherAnim: Animation | null = null
let teacherTimer: ReturnType<typeof setTimeout> | null = null

const rand = (min: number, max: number) => min + Math.random() * (max - min)
const pick = <T>(arr: T[]): T => arr[Math.floor(Math.random() * arr.length)]!

function teacherWait(ms: number) {
  return new Promise<void>((resolve) => {
    teacherTimer = setTimeout(resolve, ms)
  })
}

function teacherMoveTo(el: HTMLElement, x: number, y: number) {
  const curX = parseFloat(el.style.left || '0')
  const curY = parseFloat(el.style.top || '0')
  const dist = Math.hypot(x - curX, y - curY)
  const duration = Math.max(280, dist / TEACHER_SPEED)
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
  const stage = gridStageRef.value
  if (!el || !stage || prefersReducedMotion()) return

  await nextTick()

  // Teacher is now a position:absolute child of the stage, so top/left
  // map directly onto the stage's padding-box coordinates. No offsetParent
  // traversal needed — every measurement is local to the stage.
  const positions = () => {
    const W = stage.clientWidth   // content + padding (no border)
    const H = stage.clientHeight

    // Real rendered teacher size — keeps math correct across breakpoints
    const TW = el.offsetWidth || TEACHER_W_DEFAULT
    const TH = el.offsetHeight || TEACHER_H_DEFAULT

    // Stage padding = walking lane widths
    const cs = getComputedStyle(stage)
    const padTop = parseFloat(cs.paddingTop) || 0
    const padBottom = parseFloat(cs.paddingBottom) || 0
    const padLeft = parseFloat(cs.paddingLeft) || 0
    const padRight = parseFloat(cs.paddingRight) || 0

    // Center the teacher inside each padding strip
    const frontY = padTop / 2 - TH / 2
    const backY = H - padBottom / 2 - TH / 2
    const leftX = padLeft / 2 - TW / 2
    const rightX = W - padRight / 2 - TW / 2
    const centerX = W / 2 - TW / 2

    // Aisles are flow children of the stage; with stage position:relative
    // their offsetLeft is already in stage coords. Skip lanes that are
    // far narrower than the teacher — walking through them would cover
    // the adjacent desks badly (small phones only have a 16px aisle).
    const MIN_AISLE = Math.max(20, TW * 0.35)
    const aisleXs: number[] = []
    stage.querySelectorAll<HTMLElement>('[data-aisle="1"]').forEach((a) => {
      if (a.offsetWidth < MIN_AISLE) return
      aisleXs.push(a.offsetLeft + a.offsetWidth / 2 - TW / 2)
    })

    return { W, H, frontY, backY, leftX, rightX, centerX, aisleXs }
  }

  const p0 = positions()
  el.style.left = `${p0.leftX}px`
  el.style.top = `${p0.frontY}px`

  // Simple pace: walk left-right along the FRONT of the room only.
  // Reliable on every viewport — never enters seat zones.
  while (teacherActive) {
    const p = positions()

    await teacherMoveTo(el, p.rightX, p.frontY)
    if (!teacherActive) return
    await teacherWait(rand(900, 1800)) // "teaching" pause at the right
    if (!teacherActive) return

    await teacherMoveTo(el, p.leftX, p.frontY)
    if (!teacherActive) return
    await teacherWait(rand(900, 1800)) // "teaching" pause at the left
    if (!teacherActive) return
  }
}

function maybeStartTeacher() {
  if (teacherStarted || !props.teacher) return
  nextTick(() => {
    if (teacherStarted || !teacherRef.value || !gridStageRef.value) return
    teacherStarted = true
    teacherActive = true
    teacherLoop()
  })
}

watch(() => props.teacher, maybeStartTeacher)
onMounted(maybeStartTeacher)

// ─── Fit-to-viewport wiring ─────────────────────────────
onMounted(() => {
  nextTick(updateFitScale)
  fitRO = new ResizeObserver(updateFitScale)
  if (sceneOuterRef.value) fitRO.observe(sceneOuterRef.value)
  if (sceneInnerRef.value) fitRO.observe(sceneInnerRef.value)
  window.addEventListener('resize', updateFitScale, { passive: true })
})

// Re-fit when seats change (e.g., new check-ins extending content)
watch(() => props.seats.length, () => nextTick(updateFitScale))

onUnmounted(() => {
  teacherActive = false
  teacherAnim?.cancel()
  if (teacherTimer) clearTimeout(teacherTimer)
  fitRO?.disconnect()
  if (fitRaf) cancelAnimationFrame(fitRaf)
  window.removeEventListener('resize', updateFitScale)
})
</script>

<template>
  <!-- Outer: on large viewports (projector / desktop), capped to viewport
       so the whole classroom fits without scrolling. On small viewports
       the cap is removed and the page scrolls normally. -->
  <div
    ref="sceneOuterRef"
    class="rounded-vikinger bg-[#7CC74E] relative"
    :class="fitEnabled ? 'overflow-hidden' : ''"
    :style="fitEnabled ? { maxHeight: 'calc(100dvh - 140px)' } : {}"
  >
    <!-- Inner: when fit-mode is active, uniformly scale to fit available
         height. Width is pre-inflated to 1/scale so the scaled visible
         width equals 100% of the outer; centered via absolute trick. -->
    <div
      ref="sceneInnerRef"
      class="p-3 sm:p-5 relative"
      :style="fitEnabled ? {
        transform: `translateX(-50%) scale(${fitScale})`,
        transformOrigin: 'top center',
        width: fitScale < 1 ? `${100 / fitScale}%` : '100%',
        position: 'relative',
        left: '50%',
      } : {}"
    >
    <!-- Top wall behind the blackboard -->
    <div class="absolute inset-x-0 top-0 h-28 sm:h-32 bg-gradient-to-b from-[#6B4A2B] via-[#8B5E3C] to-[#A47148] pointer-events-none" aria-hidden="true"></div>

    <!-- Blackboard with session info + summary -->
    <div class="relative flex justify-center mb-4 sm:mb-5">
      <!-- Hanging brackets above the board -->
      <span class="absolute -top-1 left-[22%] w-2 h-3.5 bg-[#3A4452] rounded-sm shadow" aria-hidden="true"></span>
      <span class="absolute -top-1 right-[22%] w-2 h-3.5 bg-[#3A4452] rounded-sm shadow" aria-hidden="true"></span>

      <!-- Wooden frame -->
      <div class="w-full sm:w-[88%] bg-gradient-to-b from-[#A87547] to-[#7A5230] rounded-2xl p-2 shadow-2xl ring-1 ring-black/30">
        <!-- Chalkboard surface -->
        <div class="bg-gradient-to-b from-[#2F5D46] to-[#234035] rounded-xl px-4 py-3 sm:px-7 sm:py-4 relative overflow-hidden">
          <!-- Chalk dust overlay -->
          <span
            class="absolute inset-0 opacity-[0.07] bg-[radial-gradient(circle_at_20%_30%,white,transparent_40%),radial-gradient(circle_at_80%_70%,white,transparent_35%)] pointer-events-none"
            aria-hidden="true"
          ></span>
          <!-- Subtle chalk smudge along the bottom rail -->
          <span
            class="absolute inset-x-3 bottom-1 h-1 rounded-full bg-white/10 blur-sm pointer-events-none"
            aria-hidden="true"
          ></span>

          <!-- Row 1: Title -->
          <p class="relative text-[#F4FBF5] text-lg sm:text-2xl font-extrabold tracking-wide text-center drop-shadow-[0_1px_0_rgba(0,0,0,0.35)] truncate">
            {{ title || 'เช็คชื่อ' }}
          </p>
          <p v-if="subtitle" class="relative text-[#BFE0C9] text-xs sm:text-sm font-medium text-center truncate mt-0.5">
            {{ subtitle }}
          </p>

          <!-- Chalk divider -->
          <div class="relative my-2 sm:my-3 flex items-center gap-2" aria-hidden="true">
            <span class="flex-1 h-px bg-[#BFE0C9]/30"></span>
            <span class="w-1 h-1 rounded-full bg-[#BFE0C9]/50"></span>
            <span class="flex-1 h-px bg-[#BFE0C9]/30"></span>
          </div>

          <!-- Row 2: Clock + Countdown -->
          <div
            v-if="clock"
            class="relative flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-5"
          >
            <span class="inline-flex items-center gap-1.5 text-[#EAF5EC] text-sm sm:text-base font-semibold">
              <Icon icon="fluent:clock-24-regular" class="w-4 h-4 sm:w-5 sm:h-5 text-[#BFE0C9]" />
              <span class="font-mono">{{ clock.range }}</span>
            </span>
            <span class="hidden sm:inline-block w-px h-5 bg-[#BFE0C9]/30" aria-hidden="true"></span>
            <span
              class="inline-flex items-baseline gap-2 font-semibold text-sm sm:text-base"
              :class="clockToneClass"
            >
              <span>{{ clock.label }}</span>
              <span v-if="clock.value" class="font-mono text-xl sm:text-2xl tracking-wider tabular-nums drop-shadow-[0_1px_0_rgba(0,0,0,0.4)]">
                {{ clock.value }}
              </span>
            </span>
          </div>

          <!-- Chalk divider -->
          <div class="relative my-2 sm:my-3 flex items-center gap-2" aria-hidden="true">
            <span class="flex-1 h-px bg-[#BFE0C9]/30"></span>
            <span class="w-1 h-1 rounded-full bg-[#BFE0C9]/50"></span>
            <span class="flex-1 h-px bg-[#BFE0C9]/30"></span>
          </div>

          <!-- Row 3: Summary pills -->
          <div class="relative flex flex-wrap items-center justify-center gap-1.5 sm:gap-2">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full bg-emerald-500/15 ring-1 ring-emerald-400/40 text-emerald-200 text-xs sm:text-sm">
              <Icon icon="fluent:checkmark-circle-24-filled" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
              <span class="font-semibold">มา</span>
              <span class="font-mono font-extrabold tabular-nums text-emerald-100 text-sm sm:text-base">{{ summary.present }}</span>
            </span>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full bg-amber-500/15 ring-1 ring-amber-400/40 text-amber-200 text-xs sm:text-sm">
              <Icon icon="fluent:clock-alarm-24-filled" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
              <span class="font-semibold">สาย</span>
              <span class="font-mono font-extrabold tabular-nums text-amber-100 text-sm sm:text-base">{{ summary.late }}</span>
            </span>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full bg-sky-500/15 ring-1 ring-sky-400/40 text-sky-200 text-xs sm:text-sm">
              <Icon icon="fluent:document-text-24-filled" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
              <span class="font-semibold">ลา</span>
              <span class="font-mono font-extrabold tabular-nums text-sky-100 text-sm sm:text-base">{{ summary.leave }}</span>
            </span>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full bg-red-500/15 ring-1 ring-red-400/40 text-red-200 text-xs sm:text-sm">
              <Icon icon="fluent:dismiss-circle-24-filled" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
              <span class="font-semibold">ขาด</span>
              <span class="font-mono font-extrabold tabular-nums text-red-100 text-sm sm:text-base">{{ summary.absent }}</span>
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full bg-[#1B2E27] ring-1 ring-[#BFE0C9]/30 text-[#EAF5EC] text-xs sm:text-sm">
              <Icon icon="fluent:people-24-filled" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#BFE0C9]" />
              <span class="font-mono font-extrabold tabular-nums text-base sm:text-lg">{{ summary.present + summary.late }}</span>
              <span class="text-[#BFE0C9]/80">/</span>
              <span class="font-mono tabular-nums text-[#BFE0C9]">{{ summary.total }}</span>
              <span class="text-[#BFE0C9] font-semibold">เข้าเรียน</span>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Wooden floor with plank lines, side walls + skirting + ambient lighting -->
    <div
      ref="floorRef"
      class="relative rounded-xl border-l-4 border-r-4 border-t-4 border-b-2 border-[#8B5E3C] bg-[#E9C58F] [background-image:repeating-linear-gradient(0deg,transparent,transparent_26px,#D9AE74_26px,#D9AE74_27px)] p-2 sm:p-4 shadow-[inset_0_8px_24px_rgba(0,0,0,0.18)]"
    >
      <!-- Ambient light from the blackboard -->
      <span class="absolute inset-x-0 top-0 h-24 bg-[radial-gradient(ellipse_at_top,rgba(255,255,255,0.22),transparent_70%)] pointer-events-none rounded-t-xl" aria-hidden="true"></span>
      <!-- Skirting board where floor meets walls -->
      <span class="absolute inset-x-0 top-0 h-1.5 bg-[#6B4A2B] pointer-events-none" aria-hidden="true"></span>

      <div>
        <!-- Students walking in from the back door to their desks -->
        <div
          v-for="wk in walkers"
          :key="'walker-' + wk.id"
          :ref="el => startWalker(el, wk)"
          class="absolute z-[15] flex flex-col items-center w-12 pointer-events-none"
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

        <!-- Stage padding around the grid leaves walking lanes for the teacher -->
        <!-- front strip (top), back strip (bottom), and side strips (left/right) -->
        <div ref="gridStageRef" :class="wrapperGridClass" class="relative gap-x-1.5 sm:gap-x-3 pt-24 sm:pt-28 lg:pt-32 xl:pt-36 pb-24 sm:pb-28 lg:pb-32 xl:pb-36 px-20 sm:px-24 lg:px-28 xl:px-32">
          <!-- Teacher wandering around the room — positioned inside the
               stage so left/top map directly to stage's padding-box coords. -->
          <div
            v-if="teacher"
            ref="teacherRef"
            class="absolute z-[25] flex flex-col items-center w-14 sm:w-20 lg:w-24 xl:w-28 pointer-events-none"
            style="left: 0px; top: 0px;"
            aria-hidden="true"
          >
            <!-- Soft floor shadow under the teacher -->
            <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-10 sm:w-14 lg:w-16 xl:w-20 h-2 rounded-full bg-black/30 blur-[2px]" aria-hidden="true"></span>

            <div class="teacher-bob flex flex-col items-center">
              <!-- "ครู" badge crown -->
              <span class="px-1.5 sm:px-2 py-[1px] -mb-1 rounded-full bg-amber-400 text-[9px] sm:text-[10px] lg:text-[11px] font-extrabold text-amber-900 shadow-sm border border-amber-600/50 leading-tight tracking-wide z-10">
                ครู
              </span>
              <img
                :src="teacherAvatar"
                :alt="teacher.name"
                class="w-10 h-10 sm:w-14 sm:h-14 lg:w-16 lg:h-16 xl:w-20 xl:h-20 rounded-full object-cover border-[3px] border-[#7A5230] bg-white shadow-lg ring-4 ring-amber-300/80"
              />
              <span class="w-8 sm:w-11 lg:w-12 xl:w-14 h-3 sm:h-3.5 lg:h-4 rounded-t-xl bg-[#7A5230] -mt-1.5 shadow-md"></span>
            </div>
            <span class="text-[10px] sm:text-[11px] lg:text-xs font-bold text-[#4A3220] max-w-[56px] sm:max-w-[80px] lg:max-w-[96px] xl:max-w-[112px] truncate bg-white/90 rounded-md px-1.5 py-0.5 mt-1 shadow-sm border border-amber-700/30">
              {{ teacher.name }}
            </span>
          </div>

          <template v-for="(zone, zIdx) in zoneSlices" :key="'zone-' + zIdx">
            <div class="grid gap-1.5 sm:gap-2 content-start" :class="zoneGridClass(zone.cols)">
              <div
                v-for="seat in zone.seats"
                :key="seat.course_member_id"
                class="flex justify-center"
                :data-member-id="seat.course_member_id"
              >
                <div class="w-full">
                  <SeatCard
                    :seat="seat"
                    :is-course-admin="isCourseAdmin"
                    :server-time-ms="serverTimeMs"
                    :selected="seat.course_member_id === selectedMemberId"
                    :is-me="seat.course_member_id === authMemberId"
                    :walking="walkingIds.has(seat.course_member_id)"
                    @select="emit('select', seat.course_member_id)"
                  />
                </div>
              </div>
              <div
                v-for="n in zone.empty"
                :key="'empty-' + zIdx + '-' + n"
                class="flex justify-center"
              >
                <div class="w-full h-[84px] sm:h-[96px] lg:h-[108px] rounded-xl border-2 border-dashed border-[#B98A52]/50"></div>
              </div>
            </div>

            <!-- Aisle between this zone and the next -->
            <div
              v-if="zIdx < zoneSlices.length - 1"
              data-aisle="1"
              class="w-4 sm:w-10 lg:w-12 xl:w-14 rounded-lg bg-[#F2D8AC]/60 border-x border-[#E3C089]/50 relative"
              aria-hidden="true"
            >
              <div class="absolute inset-y-2 left-1/2 -translate-x-1/2 border-l-2 border-dashed border-[#E3C089]/50"></div>
            </div>
          </template>
        </div>

        <!-- Door area (centered below the grid) -->
        <div ref="doorRef" class="relative mt-10 mb-2 flex flex-col items-center gap-3">
          <!-- Interactive wooden door. When the student can check in, the
               entire door is the click target; clicking plays an open
               animation and emits door-click. -->
          <button
            type="button"
            class="door-button relative w-32 sm:w-40 h-24 sm:h-28 perspective-[600px] focus:outline-none group"
            :class="[
              isDoorInteractive ? 'cursor-pointer' : 'cursor-default',
              doorTone === 'open' ? 'door-glow-open' : '',
              doorTone === 'late' ? 'door-glow-late' : '',
              doorTone === 'done' ? 'door-glow-done' : '',
            ]"
            :aria-label="doorLabel || 'ประตูห้องเรียน'"
            :disabled="!isDoorInteractive"
            @click="handleDoorClick"
          >
            <!-- Door frame (the dark recess behind the panel) -->
            <span
              class="absolute inset-x-0 bottom-0 h-full bg-gradient-to-b from-[#3A2613] to-[#2A1B0D] rounded-t-2xl border-x-4 border-t-4 border-[#1F1408] shadow-[inset_0_4px_8px_rgba(0,0,0,0.6)] pointer-events-none"
              aria-hidden="true"
            ></span>

            <!-- Door panel (the part that swings open) -->
            <span
              class="door-panel absolute inset-x-1 bottom-0 h-[calc(100%-4px)] bg-gradient-to-b from-[#A87547] to-[#7A5230] rounded-t-xl border-x-2 border-t-2 border-[#5C3D24] shadow-2xl shadow-black/40 origin-left transition-transform duration-700 ease-out"
              :class="{ 'door-open': doorOpening }"
              aria-hidden="true"
            >
              <!-- Panel detail lines -->
              <span class="absolute inset-x-2 top-2 h-3 bg-[#6B4A2B]/50 rounded-sm"></span>
              <span class="absolute inset-x-2 top-7 bottom-3 border-2 border-[#6B4A2B]/40 rounded-md"></span>
              <!-- Knob (with breath glow when interactive) -->
              <span
                class="door-knob absolute right-2 top-1/2 -translate-y-1/2 w-2.5 h-2.5 rounded-full bg-yellow-300 shadow-[0_0_6px_rgba(253,224,71,0.9)]"
              ></span>
              <!-- State icon overlay (checkmark when already checked in) -->
              <span
                v-if="doorTone === 'done'"
                class="absolute inset-0 flex items-center justify-center"
              >
                <Icon icon="fluent:checkmark-circle-24-filled" class="w-9 h-9 text-emerald-300 drop-shadow-[0_0_8px_rgba(16,185,129,0.8)]" />
              </span>
              <span
                v-else-if="doorTone === 'closed'"
                class="absolute inset-0 flex items-center justify-center"
              >
                <Icon icon="fluent:lock-closed-24-filled" class="w-7 h-7 text-[#3A2613]/70" />
              </span>
            </span>

            <!-- "Open me" pulsing hint when interactive -->
            <span
              v-if="isDoorInteractive"
              class="absolute -top-3 left-1/2 -translate-x-1/2 px-2.5 py-1 rounded-full text-[10px] sm:text-xs font-bold whitespace-nowrap shadow-lg door-cta"
              :class="doorState?.willBeLate
                ? 'bg-gradient-to-r from-amber-500 to-orange-600 text-white'
                : 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white'"
            >
              {{ doorState?.willBeLate ? 'รายงานตัว (สาย)' : 'แตะเพื่อเข้าห้อง' }}
            </span>
          </button>

          <!-- Status text below the door -->
          <div
            v-if="doorLabel && !isDoorInteractive"
            class="px-3 py-1.5 rounded-full bg-white/85 backdrop-blur-sm text-[11px] sm:text-xs font-bold shadow-sm border"
            :class="{
              'text-emerald-700 border-emerald-300': doorTone === 'done',
              'text-slate-600 border-slate-300': doorTone === 'closed' || doorTone === 'wait',
              'text-sky-700 border-sky-300': doorTone === 'leave',
              'text-[#7A5230] border-[#7A5230]/30': doorTone === 'idle',
            }"
          >
            {{ doorLabel }}
          </div>

          <!-- Slot for additional content (e.g. shell's "my seat" indicator) -->
          <slot name="door" />
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

.door-knob {
  animation: door-knob-breath 4s ease-in-out infinite;
}

@keyframes door-knob-breath {
  0%, 100% { opacity: 0.85; transform: translateY(-50%) scale(1); }
  50% { opacity: 1; transform: translateY(-50%) scale(1.2); }
}

.door-content-enter-active,
.door-content-leave-active {
  transition: opacity 200ms ease, transform 200ms ease;
}
.door-content-enter-from,
.door-content-leave-to {
  opacity: 0;
  transform: translateY(4px);
}

/* ─── Interactive door ───────────────────────────────── */
.door-button .door-panel {
  /* Slight 3D tilt origin so the panel hinges from its left edge */
  backface-visibility: hidden;
}
.door-button .door-panel.door-open {
  transform: perspective(600px) rotateY(-78deg);
}
.door-button:hover:not(:disabled) .door-panel {
  transform: perspective(600px) rotateY(-10deg);
}
.door-button:active:not(:disabled) .door-panel {
  transform: perspective(600px) rotateY(-20deg);
}

/* Soft outer glow that signals the door's state at a glance */
.door-glow-open {
  filter: drop-shadow(0 0 12px rgba(16, 185, 129, 0.55));
}
.door-glow-late {
  filter: drop-shadow(0 0 12px rgba(245, 158, 11, 0.55));
}
.door-glow-done {
  filter: drop-shadow(0 0 10px rgba(16, 185, 129, 0.45));
}

/* Pulsing CTA chip above the door */
.door-cta {
  animation: door-cta-pulse 1.6s ease-in-out infinite;
}
@keyframes door-cta-pulse {
  0%, 100% { transform: translateX(-50%) translateY(0) scale(1); }
  50%      { transform: translateX(-50%) translateY(-2px) scale(1.05); }
}

@media (prefers-reduced-motion: reduce) {
  .teacher-bob,
  .door-knob,
  .door-cta {
    animation: none;
  }
  .door-content-enter-active,
  .door-content-leave-active,
  .door-button .door-panel {
    transition: none;
  }
}
</style>
