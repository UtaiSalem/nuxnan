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
  authMemberId?: number | null
  title?: string
  subtitle?: string
  clock?: { range: string; label: string; value: string; tone: string } | null
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

const prefersReducedMotion = () =>
  typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches

const floorRef = ref<HTMLElement | null>(null)
const doorRef = ref<HTMLElement | null>(null)

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

  const floorRect = floor.getBoundingClientRect()
  const seatRect = seatEl.getBoundingClientRect()
  const doorRect = door.getBoundingClientRect()

  const doorX = doorRect.left - floorRect.left + doorRect.width / 2 - WALKER_W / 2
  const doorY = doorRect.top - floorRect.top
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
</script>

<template>
  <!-- Farm-game classroom scene: grass + wooden floor (colors fixed in both modes) -->
  <div class="rounded-vikinger overflow-hidden bg-[#7CC74E] p-3 sm:p-5 relative">
    <!-- Top wall behind the blackboard -->
    <div class="absolute inset-x-0 top-0 h-28 sm:h-32 bg-gradient-to-b from-[#6B4A2B] via-[#8B5E3C] to-[#A47148] pointer-events-none" aria-hidden="true"></div>

    <!-- Blackboard with session info + summary -->
    <div class="relative flex justify-center mb-4 sm:mb-5">
      <!-- Hanging brackets above the board -->
      <span class="absolute -top-1 left-[22%] w-1.5 h-3 bg-[#4A5568] rounded-sm shadow-sm" aria-hidden="true"></span>
      <span class="absolute -top-1 right-[22%] w-1.5 h-3 bg-[#4A5568] rounded-sm shadow-sm" aria-hidden="true"></span>
      <div class="w-full sm:w-4/5 bg-gradient-to-b from-[#A87547] to-[#7A5230] rounded-xl p-1.5 shadow-lg ring-1 ring-black/20">
        <div class="bg-gradient-to-b from-[#2F5D46] to-[#264D3B] rounded-lg px-3 py-2.5 sm:px-5 sm:py-3 text-center space-y-1 relative overflow-hidden">
          <!-- Chalk dust overlay -->
          <span class="absolute inset-0 opacity-[0.06] bg-[radial-gradient(circle_at_20%_30%,white,transparent_40%),radial-gradient(circle_at_80%_70%,white,transparent_35%)] pointer-events-none" aria-hidden="true"></span>
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

    <!-- Wooden floor with plank lines, side walls + skirting + ambient lighting -->
    <div
      ref="floorRef"
      class="relative rounded-xl border-l-4 border-r-4 border-t-4 border-b-2 border-[#8B5E3C] bg-[#E9C58F] [background-image:repeating-linear-gradient(0deg,transparent,transparent_26px,#D9AE74_26px,#D9AE74_27px)] p-2 sm:p-4 max-w-2xl mx-auto shadow-[inset_0_8px_24px_rgba(0,0,0,0.18)]"
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

        <!-- Responsive seat grid: zones + aisles built from useClassroomLayout() -->
        <div :class="wrapperGridClass" class="gap-x-1.5 sm:gap-x-3">
          <template v-for="(zone, zIdx) in zoneSlices" :key="'zone-' + zIdx">
            <div class="grid gap-1.5 sm:gap-2 content-start" :class="zoneGridClass(zone.cols)">
              <div
                v-for="seat in zone.seats"
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
                <div class="w-full max-w-[88px] h-[84px] rounded-xl border-2 border-dashed border-[#B98A52]/50"></div>
              </div>
            </div>

            <!-- Aisle between this zone and the next -->
            <div
              v-if="zIdx < zoneSlices.length - 1"
              class="w-4 sm:w-8 rounded-lg bg-[#F2D8AC]/60 border-x border-[#E3C089]/50 relative"
              aria-hidden="true"
            >
              <div class="absolute inset-y-2 left-1/2 -translate-x-1/2 border-l-2 border-dashed border-[#E3C089]/50"></div>
            </div>
          </template>
        </div>

        <!-- Door (centered below the full grid so zone count does not shift it) -->
        <div ref="doorRef" class="relative mt-10 mb-2 flex justify-center">
          <!-- Layer 1: wooden door frame (decorative) -->
          <div
            class="absolute left-1/2 -translate-x-1/2 bottom-0 w-28 sm:w-36 h-20 sm:h-24 bg-gradient-to-b from-[#9A6B3F] to-[#7A5230] border-x-4 border-t-4 border-[#5C3D24] rounded-t-2xl shadow-2xl shadow-black/40 z-[10] pointer-events-none"
            aria-hidden="true"
          >
            <span class="absolute inset-x-2 top-2 h-3 bg-[#6B4A2B]/50 rounded-sm"></span>
            <span class="door-knob absolute right-2 top-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-yellow-300 shadow-[0_0_4px_rgba(253,224,71,0.8)]"></span>
          </div>

          <!-- Layer 2: interactive content (button / state chip) -->
          <div class="relative z-[20] pt-2">
            <Transition name="door-content" mode="out-in">
              <slot name="door">
                <div class="px-3 py-1.5 rounded-full bg-white/80 backdrop-blur-sm text-[10px] font-bold text-[#7A5230] shadow-sm">
                  ประตูห้องเรียน
                </div>
              </slot>
            </Transition>
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

@media (prefers-reduced-motion: reduce) {
  .teacher-bob,
  .door-knob {
    animation: none;
  }
  .door-content-enter-active,
  .door-content-leave-active {
    transition: none;
  }
}
</style>
