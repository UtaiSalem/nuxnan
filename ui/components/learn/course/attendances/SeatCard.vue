<script setup lang="ts">
import { computed } from 'vue'
import {
  type SeatData,
  ATTENDANCE_STATUS,
  isArriving,
} from '~/composables/useAttendanceStatus'

interface Props {
  seat: SeatData
  isCourseAdmin: boolean
  serverTimeMs: number
  selected?: boolean
  isMe?: boolean
  /** Seat shows as empty while the walk-in animation plays */
  walking?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  selected: false,
  isMe: false,
  walking: false,
})

const emit = defineEmits<{
  (e: 'select'): void
}>()

const arriving = computed(() => isArriving(props.seat, props.serverTimeMs) && !props.walking)
const absent = computed(() => props.seat.status === ATTENDANCE_STATUS.ABSENT)
// Empty-looking desk: truly absent, or student still walking to their seat
const ghost = computed(() => absent.value || props.walking)

// Farm-game scene colors — intentionally identical in light/dark (game scene does not invert)
const deskColors = computed(() =>
  ghost.value
    ? { top: '#D3BC93', stroke: '#B09A72', left: '#BFA176', right: '#A98C64' }
    : { top: '#D9A05B', stroke: '#A9763B', left: '#B97F3F', right: '#9A6630' },
)

const badgeClass = computed(() => {
  if (props.walking) return 'bg-slate-400'
  if (arriving.value) return 'bg-emerald-400'
  switch (props.seat.status) {
    case ATTENDANCE_STATUS.PRESENT: return 'bg-emerald-500'
    case ATTENDANCE_STATUS.LATE: return 'bg-amber-500'
    case ATTENDANCE_STATUS.LEAVE: return 'bg-sky-500'
    default: return 'bg-slate-400'
  }
})

const avatarRingClass = computed(() => {
  if (arriving.value) return 'border-emerald-400 ring-2 ring-emerald-300'
  switch (props.seat.status) {
    case ATTENDANCE_STATUS.PRESENT: return 'border-emerald-500'
    case ATTENDANCE_STATUS.LATE: return 'border-amber-500'
    case ATTENDANCE_STATUS.LEAVE: return 'border-sky-500'
    default: return 'border-slate-300'
  }
})

const shirtClass = computed(() => {
  switch (props.seat.status) {
    case ATTENDANCE_STATUS.PRESENT: return 'bg-emerald-600'
    case ATTENDANCE_STATUS.LATE: return 'bg-amber-600'
    case ATTENDANCE_STATUS.LEAVE: return 'bg-sky-600'
    default: return 'bg-slate-400'
  }
})

const timeTextClass = computed(() => {
  switch (props.seat.status) {
    case ATTENDANCE_STATUS.LATE: return 'text-amber-800'
    case ATTENDANCE_STATUS.LEAVE: return 'text-sky-800'
    default: return 'text-emerald-800'
  }
})

const { getAvatarUrl } = useAvatar()
const avatarUrl = computed(() => getAvatarUrl({ avatar: props.seat.avatar, name: props.seat.name }))

const handleImageError = (e: Event) => {
  const img = e.target as HTMLImageElement
  img.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(props.seat.name)}&color=7F9CF5&background=EBF4FF`
}
</script>

<template>
  <button
    type="button"
    class="relative w-full flex flex-col items-center select-none transition-transform duration-300 focus:outline-none focus:ring-2 focus:ring-vikinger-purple focus:ring-offset-1 rounded-xl"
    :class="[
      arriving ? 'z-10 scale-105' : 'z-0',
      isCourseAdmin ? 'hover:-translate-y-0.5' : '',
    ]"
    :aria-label="`ที่นั่ง ${seat.seat_number} ${seat.name}`"
    @click="emit('select')"
  >
    <!-- Student (head + shirt) sitting behind the desk -->
    <div class="relative flex flex-col items-center">
      <template v-if="!ghost">
        <img
          :src="avatarUrl"
          :alt="seat.name"
          class="relative z-10 w-7 h-7 rounded-full object-cover border-2 transition-all duration-300"
          :class="[avatarRingClass, seat.status === ATTENDANCE_STATUS.LEAVE ? 'opacity-60' : '']"
          @error="handleImageError"
        />
        <span
          class="w-6 h-2.5 rounded-t-lg -mt-1"
          :class="[shirtClass, seat.status === ATTENDANCE_STATUS.LEAVE ? 'opacity-60' : '']"
        ></span>
      </template>
      <template v-else>
        <span class="w-7 h-7 rounded-full border-2 border-dashed border-[#8C795F]/70"></span>
        <span class="w-6 h-2.5 -mt-1"></span>
      </template>
    </div>

    <!-- Isometric wooden desk with seat number on the front -->
    <div class="relative w-full max-w-[72px] -mt-1.5">
      <svg viewBox="0 0 80 56" class="w-full" aria-hidden="true">
        <ellipse cx="40" cy="50" rx="38" ry="6" fill="#5e8c34" :opacity="ghost ? 0.2 : 0.3" />
        <path d="M0,18 L40,36 L40,50 L0,32 Z" :fill="deskColors.left" />
        <path d="M80,18 L40,36 L40,50 L80,32 Z" :fill="deskColors.right" />
        <path d="M0,18 L40,0 L80,18 L40,36 Z" :fill="deskColors.top" :stroke="deskColors.stroke" stroke-width="1.5" />
      </svg>
      <span
        class="absolute left-1/2 top-[30%] -translate-x-1/2 -translate-y-1/2 z-20 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold text-white border border-white/80 shadow-sm"
        :class="badgeClass"
      >
        {{ seat.seat_number }}
      </span>
    </div>

    <!-- Name -->
    <div class="relative mt-0.5 max-w-[72px] px-0.5">
      <p
        class="text-[10px] font-semibold text-center leading-tight truncate"
        :class="ghost ? 'text-[#8C795F]' : 'text-[#4A3220]'"
      >
        {{ seat.name }}
      </p>
      <span
        v-if="isMe"
        class="absolute -top-1 -right-1 flex h-2 w-2"
      >
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
      </span>
    </div>

    <!-- Time-in -->
    <p v-if="seat.time_in && !ghost" class="text-[10px] font-mono" :class="timeTextClass">
      {{ seat.time_in }}
    </p>

    <!-- Selected ring or isMe ring -->
    <span
      v-if="selected || isMe"
      class="absolute inset-0 rounded-xl pointer-events-none transition-all duration-300"
      :class="[
        selected ? 'ring-2 ring-vikinger-purple' : '',
        isMe && !selected ? 'ring-2 ring-emerald-400 ring-offset-1 dark:ring-offset-slate-900 border-2 border-white dark:border-slate-800' : '',
      ]"
    ></span>
  </button>
</template>
