import type { Ref } from 'vue'

export const ENROLLMENT_STATUS = {
  ACTIVE: 'active',
  TRANSFERRED: 'transferred',
  PROMOTED: 'promoted',
  GRADUATED: 'graduated',
  DROPPED: 'dropped',
  REPEATING: 'repeating',
  SUPERSEDED: 'superseded',
} as const

export type EnrollmentStatus = typeof ENROLLMENT_STATUS[keyof typeof ENROLLMENT_STATUS]

export const ENROLLMENT_ACTIONS = [
  'graduate',
  'drop',
  'repeat',
  'promote',
  'transfer',
] as const

export type EnrollmentAction = typeof ENROLLMENT_ACTIONS[number]

export interface EnrollmentStatusStyle {
  label: string
  icon: string
  bgClass: string
  textClass: string
  borderClass: string
  dotClass: string
}

export interface EnrollmentClassroomSummaryDTO {
  id: number
  display_name: string
  grade_level: string | null
  section: string | null
}

export interface EnrollmentCreatedByDTO {
  id: number | null
  name: string | null
}

export interface StudentSummaryDTO {
  id: number
  student_id: string
  academy_id: number
  first_name_th: string | null
  last_name_th: string | null
  nickname: string | null
  status: string | null
  class_level: string | null
  class_section: string | null
}

export interface ClassroomStudentDTO {
  id: number
  student_id: number
  classroom_id: number
  academy_id: number
  academic_year_id: number | null
  student_number: number | null
  status: EnrollmentStatus | string
  status_text: string | null
  enrolled_at: string | null
  left_at: string | null
  leave_reason: string | null
  rollover_batch_id: string | null
  created_by?: EnrollmentCreatedByDTO
  classroom?: EnrollmentClassroomSummaryDTO
  student?: StudentSummaryDTO
}

export interface StudentEnrollmentLifecycleResponse {
  success: boolean
  closed_enrollment: ClassroomStudentDTO | null
  new_enrollment: ClassroomStudentDTO | null
  student: StudentSummaryDTO
}

export interface StudentEnrollmentHistoryResponse {
  success: boolean
  data: ClassroomStudentDTO[]
}

export interface EnrollmentFieldErrors {
  [field: string]: string
}

export interface EnrollmentApiErrorData {
  success?: boolean
  message?: string
  error?: string
  errors?: Record<string, string[] | string>
}

export interface EnrollmentBasePayload {
  reason?: string
}

export interface GraduateStudentPayload extends EnrollmentBasePayload {}

export interface DropStudentPayload extends EnrollmentBasePayload {
  reason: string
}

export interface RepeatStudentPayload extends EnrollmentBasePayload {
  new_classroom_id: number
  student_number?: number
}

export interface PromoteStudentPayload extends EnrollmentBasePayload {
  from_classroom_id: number
  to_classroom_id: number
  student_number?: number
}

export interface TransferStudentPayload extends EnrollmentBasePayload {
  from_classroom_id: number
  to_classroom_id: number
}

export type EnrollmentActionPayloadMap = {
  graduate: GraduateStudentPayload
  drop: DropStudentPayload
  repeat: RepeatStudentPayload
  promote: PromoteStudentPayload
  transfer: TransferStudentPayload
}

export type EnrollmentActionPayload<TAction extends EnrollmentAction = EnrollmentAction> =
  EnrollmentActionPayloadMap[TAction]

export type MaybeEnrollmentAcademyId = number | string | null | undefined | Ref<number | string | null | undefined>

const ENROLLMENT_STATUS_STYLES: Record<string, EnrollmentStatusStyle> = {
  [ENROLLMENT_STATUS.ACTIVE]: {
    label: 'กำลังศึกษา',
    icon: 'mdi:account-check',
    bgClass: 'bg-emerald-50 dark:bg-emerald-900/20',
    textClass: 'text-emerald-700 dark:text-emerald-300',
    borderClass: 'border-emerald-200 dark:border-emerald-800',
    dotClass: 'bg-emerald-500',
  },
  [ENROLLMENT_STATUS.TRANSFERRED]: {
    label: 'ย้ายห้อง',
    icon: 'mdi:arrow-right-bold-circle',
    bgClass: 'bg-sky-50 dark:bg-sky-900/20',
    textClass: 'text-sky-700 dark:text-sky-300',
    borderClass: 'border-sky-200 dark:border-sky-800',
    dotClass: 'bg-sky-500',
  },
  [ENROLLMENT_STATUS.PROMOTED]: {
    label: 'เลื่อนชั้น',
    icon: 'mdi:arrow-up-bold-circle',
    bgClass: 'bg-indigo-50 dark:bg-indigo-900/20',
    textClass: 'text-indigo-700 dark:text-indigo-300',
    borderClass: 'border-indigo-200 dark:border-indigo-800',
    dotClass: 'bg-indigo-500',
  },
  [ENROLLMENT_STATUS.GRADUATED]: {
    label: 'จบการศึกษา',
    icon: 'mdi:school',
    bgClass: 'bg-violet-50 dark:bg-violet-900/20',
    textClass: 'text-violet-700 dark:text-violet-300',
    borderClass: 'border-violet-200 dark:border-violet-800',
    dotClass: 'bg-violet-500',
  },
  [ENROLLMENT_STATUS.DROPPED]: {
    label: 'พ้นสภาพ',
    icon: 'mdi:account-cancel',
    bgClass: 'bg-rose-50 dark:bg-rose-900/20',
    textClass: 'text-rose-700 dark:text-rose-300',
    borderClass: 'border-rose-200 dark:border-rose-800',
    dotClass: 'bg-rose-500',
  },
  [ENROLLMENT_STATUS.REPEATING]: {
    label: 'ซ้ำชั้น',
    icon: 'mdi:refresh-circle',
    bgClass: 'bg-amber-50 dark:bg-amber-900/20',
    textClass: 'text-amber-700 dark:text-amber-300',
    borderClass: 'border-amber-200 dark:border-amber-800',
    dotClass: 'bg-amber-500',
  },
  [ENROLLMENT_STATUS.SUPERSEDED]: {
    label: 'ปิดโดยระบบ',
    icon: 'mdi:archive-arrow-down',
    bgClass: 'bg-zinc-100 dark:bg-zinc-800/70',
    textClass: 'text-zinc-700 dark:text-zinc-300',
    borderClass: 'border-zinc-200 dark:border-zinc-700',
    dotClass: 'bg-zinc-500',
  },
}

export function getEnrollmentStatusStyle(status: EnrollmentStatus | string | null | undefined): EnrollmentStatusStyle {
  if (!status) return ENROLLMENT_STATUS_STYLES[ENROLLMENT_STATUS.ACTIVE]
  return ENROLLMENT_STATUS_STYLES[status] ?? {
    label: status,
    icon: 'mdi:help-circle',
    bgClass: 'bg-zinc-100 dark:bg-zinc-800/70',
    textClass: 'text-zinc-700 dark:text-zinc-300',
    borderClass: 'border-zinc-200 dark:border-zinc-700',
    dotClass: 'bg-zinc-500',
  }
}

export function getEnrollmentStatusLabel(
  status: EnrollmentStatus | string | null | undefined,
  fallback?: string | null,
): string {
  if (fallback) return fallback
  return getEnrollmentStatusStyle(status).label
}

export function isEnrollmentInactive(status: EnrollmentStatus | string | null | undefined): boolean {
  return !!status && status !== ENROLLMENT_STATUS.ACTIVE
}
