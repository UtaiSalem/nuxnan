/**
 * Attendance status constants and utilities — single source of truth
 * for status enum values, display labels, colors, and derived states.
 *
 * Server status codes:
 *   0 = absent (no record), 1 = present, 2 = late, 3 = leave
 *
 * `arriving` is a CLIENT-ONLY derived state — not stored in DB.
 * A seat is "arriving" when the student checked in within the last
 * GRACE_MS milliseconds (relative to server_time to avoid clock skew).
 */

export const ATTENDANCE_STATUS = {
  ABSENT: 0,
  PRESENT: 1,
  LATE: 2,
  LEAVE: 3,
} as const

export type AttendanceStatusCode = typeof ATTENDANCE_STATUS[keyof typeof ATTENDANCE_STATUS]

/** Grace period in ms after check-in before "arriving" animation fades */
export const ARRIVING_GRACE_MS = 6000

export interface SeatData {
  seat_number: number
  order_number: number | null
  course_member_id: number
  member_code: string | null
  name: string
  avatar: string | null
  status: AttendanceStatusCode | 0
  time_in: string | null
  checked_in_at: string | null // ISO-8601 — server-time-based, for arriving calc
}

/**
 * Determine if this seat is in "arriving" state.
 * A student is arriving when they checked in within the grace window
 * AND their status is present(1) or late(2).
 */
export function isArriving(
  seat: SeatData,
  serverTimeMs: number,
  graceMs: number = ARRIVING_GRACE_MS,
): boolean {
  if (!seat.checked_in_at) return false
  if (seat.status !== ATTENDANCE_STATUS.PRESENT && seat.status !== ATTENDANCE_STATUS.LATE) return false

  const checkedInAt = new Date(seat.checked_in_at).getTime()
  return serverTimeMs - checkedInAt < graceMs
}

export interface StatusStyle {
  label: string
  icon: string
  colorClass: string
  bgClass: string
  textClass: string
  borderClass: string
  glowClass: string
  dotColor: string
}

const STATUS_STYLES: Record<number, StatusStyle> = {
  [ATTENDANCE_STATUS.ABSENT]: {
    label: 'ขาด',
    icon: 'heroicons:x-circle-20-solid',
    colorClass: 'text-red-600 dark:text-red-400',
    bgClass: 'bg-red-50 dark:bg-red-900/20',
    textClass: 'text-red-700 dark:text-red-300',
    borderClass: 'border-red-300 dark:border-red-700',
    glowClass: 'shadow-red-200 dark:shadow-red-900/30',
    dotColor: 'bg-red-500',
  },
  [ATTENDANCE_STATUS.PRESENT]: {
    label: 'มา',
    icon: 'heroicons:check-circle-20-solid',
    colorClass: 'text-emerald-600 dark:text-emerald-400',
    bgClass: 'bg-emerald-50 dark:bg-emerald-900/20',
    textClass: 'text-emerald-700 dark:text-emerald-300',
    borderClass: 'border-emerald-300 dark:border-emerald-700',
    glowClass: 'shadow-emerald-200 dark:shadow-emerald-900/30',
    dotColor: 'bg-emerald-500',
  },
  [ATTENDANCE_STATUS.LATE]: {
    label: 'สาย',
    icon: 'heroicons:clock-20-solid',
    colorClass: 'text-amber-600 dark:text-amber-400',
    bgClass: 'bg-amber-50 dark:bg-amber-900/20',
    textClass: 'text-amber-700 dark:text-amber-300',
    borderClass: 'border-amber-300 dark:border-amber-700',
    glowClass: 'shadow-amber-200 dark:shadow-amber-900/30',
    dotColor: 'bg-amber-500',
  },
  [ATTENDANCE_STATUS.LEAVE]: {
    label: 'ลา',
    icon: 'heroicons:document-text-20-solid',
    colorClass: 'text-sky-600 dark:text-sky-400',
    bgClass: 'bg-sky-50 dark:bg-sky-900/20',
    textClass: 'text-sky-700 dark:text-sky-300',
    borderClass: 'border-sky-300 dark:border-sky-700',
    glowClass: 'shadow-sky-200 dark:shadow-sky-900/30',
    dotColor: 'bg-sky-500',
  },
}

export function getStatusStyle(status: number | null | undefined): StatusStyle {
  if (status === null || status === undefined) status = ATTENDANCE_STATUS.ABSENT
  return STATUS_STYLES[status] ?? STATUS_STYLES[ATTENDANCE_STATUS.ABSENT]
}

export function getStatusLabel(status: number | null | undefined): string {
  return getStatusStyle(status).label
}

export interface SimulatorData {
  attendance: {
    id: number
    title: string | null
    start_at: string
    finish_at: string
    late_time: number | null
    description: string | null
    group: { id: number; name: string }
    instructor: { name: string; avatar: string | null } | null
  }
  layout: {
    cols: number
    rows: number
    total_seats: number
  }
  seats: SeatData[]
  meta: {
    is_course_admin: boolean
    auth_member_id: number | null
    server_time: string
  }
}

/** Parse server_time string to numeric ms since epoch */
export function parseServerTime(isoString: string): number {
  return new Date(isoString).getTime()
}

/** Build a summary from seats array */
export function buildSummary(seats: SeatData[]) {
  const total = seats.length
  let present = 0
  let late = 0
  let leave = 0
  let absent = 0

  for (const s of seats) {
    if (s.status === ATTENDANCE_STATUS.PRESENT) present++
    else if (s.status === ATTENDANCE_STATUS.LATE) late++
    else if (s.status === ATTENDANCE_STATUS.LEAVE) leave++
    else absent++
  }

  return { total, present, late, leave, absent }
}

/** Check if attendance session is currently active */
export function isSessionActive(
  startAt: string,
  finishAt: string,
  serverTimeMs: number,
): boolean {
  const start = new Date(startAt).getTime()
  const finish = new Date(finishAt).getTime()
  return serverTimeMs >= start && serverTimeMs <= finish
}
