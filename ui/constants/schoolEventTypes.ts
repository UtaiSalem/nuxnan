/**
 * School event taxonomy — mirror of backend SchoolEvent::TYPES / PATTERNS
 * (api/nuxnanravel/app/Models/SchoolEvent.php). Keep both in sync.
 */

export interface SchoolEventTypeMeta {
  key: string
  label: string
  icon: string
  color: 'purple' | 'cyan' | 'green' | 'orange' | 'pink' | 'amber' | 'teal' | 'rose' | 'gray'
  group: string
}

export const SCHOOL_EVENT_TYPES: SchoolEventTypeMeta[] = [
  { key: 'meeting', label: 'การประชุม', icon: 'heroicons:user-group', color: 'cyan', group: 'งานบริหาร' },
  { key: 'training', label: 'อบรม/สัมมนา', icon: 'heroicons:academic-cap', color: 'cyan', group: 'งานบริหาร' },
  { key: 'holiday', label: 'วันหยุด', icon: 'heroicons:sun', color: 'amber', group: 'ปิดเปิดเทอม' },
  { key: 'orientation', label: 'ปฐมนิเทศ/ปัจฉิมนิเทศ', icon: 'heroicons:flag', color: 'amber', group: 'ปิดเปิดเทอม' },
  { key: 'exam', label: 'การสอบ', icon: 'heroicons:pencil-square', color: 'rose', group: 'วิชาการ' },
  { key: 'academic_competition', label: 'แข่งขันวิชาการ', icon: 'heroicons:trophy', color: 'rose', group: 'วิชาการ' },
  { key: 'student_development', label: 'กิจกรรมพัฒนาผู้เรียน', icon: 'heroicons:puzzle-piece', color: 'green', group: 'พัฒนาผู้เรียน' },
  { key: 'club', label: 'ชมรม/ชุมนุม', icon: 'heroicons:sparkles', color: 'green', group: 'พัฒนาผู้เรียน' },
  { key: 'sports', label: 'กีฬา', icon: 'heroicons:trophy', color: 'orange', group: 'กีฬา/นันทนาการ' },
  { key: 'ceremony', label: 'พิธีการ', icon: 'heroicons:gift', color: 'purple', group: 'พิธีการ/ศาสนา' },
  { key: 'field_trip', label: 'ทัศนศึกษา', icon: 'heroicons:map', color: 'teal', group: 'นอกสถานที่' },
  { key: 'volunteer', label: 'จิตอาสา', icon: 'heroicons:heart', color: 'teal', group: 'นอกสถานที่' },
  { key: 'activity', label: 'กิจกรรม', icon: 'heroicons:calendar-days', color: 'gray', group: 'อื่นๆ' },
  { key: 'other', label: 'อื่นๆ', icon: 'heroicons:ellipsis-horizontal-circle', color: 'gray', group: 'อื่นๆ' },
]

export interface AttendancePatternMeta {
  key: 'one_time' | 'semester' | 'recurring'
  label: string
  description: string
}

export const ATTENDANCE_PATTERNS: AttendancePatternMeta[] = [
  { key: 'one_time', label: 'ครั้งเดียว', description: 'ลงทะเบียน + เช็คชื่อวันเดียว (เช่น พิธีการ, ทัศนศึกษา)' },
  { key: 'semester', label: 'ต่อเนื่องรายภาคเรียน', description: 'สมัครครั้งเดียว เช็คชื่อทุกนัด (เช่น ชมรม, ลูกเสือ)' },
  { key: 'recurring', label: 'ต่อเนื่องรายวัน/สัปดาห์/เดือน', description: 'เช็คชื่อตามรอบที่กำหนด ไม่ผูกภาคเรียน (เช่น ละหมาดรายวัน)' },
]

// Suggested default pattern by type — UI default only, not enforced
export const DEFAULT_PATTERN_BY_TYPE: Record<string, AttendancePatternMeta['key']> = {
  student_development: 'semester',
  club: 'semester',
}

const TYPE_MAP: Record<string, SchoolEventTypeMeta> = Object.fromEntries(
  SCHOOL_EVENT_TYPES.map((t) => [t.key, t]),
)

const UNKNOWN_TYPE: SchoolEventTypeMeta = {
  key: 'other',
  label: 'อื่นๆ',
  icon: 'heroicons:ellipsis-horizontal-circle',
  color: 'gray',
  group: 'อื่นๆ',
}

export const getSchoolEventTypeMeta = (type: string | null | undefined): SchoolEventTypeMeta => {
  if (!type) return UNKNOWN_TYPE
  return TYPE_MAP[type] || UNKNOWN_TYPE
}

export const getAttendancePatternMeta = (pattern: string | null | undefined): AttendancePatternMeta =>
  ATTENDANCE_PATTERNS.find((p) => p.key === pattern) || ATTENDANCE_PATTERNS[0]

/** Group event types for a grouped <select>/dropdown UI. */
export const schoolEventTypesByGroup = (): Array<{ group: string; items: SchoolEventTypeMeta[] }> => {
  const buckets = new Map<string, SchoolEventTypeMeta[]>()
  for (const t of SCHOOL_EVENT_TYPES) {
    if (!buckets.has(t.group)) buckets.set(t.group, [])
    buckets.get(t.group)!.push(t)
  }
  return Array.from(buckets.entries()).map(([group, items]) => ({ group, items }))
}

export const EVENT_TYPE_COLOR_CLASSES: Record<SchoolEventTypeMeta['color'], { badge: string; iconBg: string }> = {
  purple: { badge: 'bg-vikinger-purple/15 text-vikinger-purple', iconBg: 'bg-vikinger-purple/15 text-vikinger-purple' },
  cyan: { badge: 'bg-vikinger-cyan/15 text-cyan-700 dark:text-cyan-300', iconBg: 'bg-vikinger-cyan/15 text-cyan-700 dark:text-cyan-300' },
  green: { badge: 'bg-green-500/15 text-green-700 dark:text-green-300', iconBg: 'bg-green-500/15 text-green-700 dark:text-green-300' },
  orange: { badge: 'bg-orange-500/15 text-orange-700 dark:text-orange-300', iconBg: 'bg-orange-500/15 text-orange-700 dark:text-orange-300' },
  pink: { badge: 'bg-pink-500/15 text-pink-700 dark:text-pink-300', iconBg: 'bg-pink-500/15 text-pink-700 dark:text-pink-300' },
  amber: { badge: 'bg-amber-500/15 text-amber-700 dark:text-amber-300', iconBg: 'bg-amber-500/15 text-amber-700 dark:text-amber-300' },
  teal: { badge: 'bg-teal-500/15 text-teal-700 dark:text-teal-300', iconBg: 'bg-teal-500/15 text-teal-700 dark:text-teal-300' },
  rose: { badge: 'bg-rose-500/15 text-rose-700 dark:text-rose-300', iconBg: 'bg-rose-500/15 text-rose-700 dark:text-rose-300' },
  gray: { badge: 'bg-gray-200/70 dark:bg-gray-700 text-gray-700 dark:text-gray-300', iconBg: 'bg-gray-200/70 dark:bg-gray-700 text-gray-700 dark:text-gray-300' },
}
