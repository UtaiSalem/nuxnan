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

/**
 * Classroom option shown in lifecycle modal dropdowns. Adds academic-year
 * context so promote/transfer/repeat selects can group by year and display
 * a "ปี YYYY" bracket per the Phase 4.B plan.
 */
export interface ClassroomOptionDTO extends EnrollmentClassroomSummaryDTO {
  academic_year_id: number | null
  academic_year_name: string | null
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

export interface AcademicYearDTO {
  id: number
  name: string
  start_date: string | null
  end_date: string | null
  is_current: boolean
  semesters?: Array<Record<string, any>>
}

export const ROLLOVER_ACTIONS = [
  'promote',
  'graduate',
  'drop',
  'repeat',
  'new_intake',
  'skip',
] as const

export type RolloverAction = typeof ROLLOVER_ACTIONS[number]

export interface RolloverPreviewEntryDTO {
  student_id: number
  student_name: string
  from_classroom_id: number | null
  from_classroom_name: string | null
  to_classroom_id: number | null
  to_classroom_name: string | null
  action: RolloverAction
  reason: string | null
}

/**
 * Minimal entry shape accepted by POST /rollover/plan — matches
 * PlanRolloverRequest rules in Phase 3.B (no joined name fields).
 */
export interface RolloverPlanRequestEntry {
  student_id: number
  action: RolloverAction
  from_classroom_id?: number | null
  to_classroom_id?: number | null
  reason?: string | null
}

export interface RolloverSummaryDTO {
  promote: number
  graduate: number
  repeat: number
  drop: number
  new_intake: number
  skip: number
}

export interface RolloverPreviewDTO {
  entries: RolloverPreviewEntryDTO[]
  missing_targets: string[]
  totals: RolloverSummaryDTO
  warnings: string[]
}

export interface RolloverPreviewResponse {
  success: boolean
  preview: RolloverPreviewDTO
}

export interface RolloverPlanResponse {
  success: boolean
  plan_id: string
  expires_in_seconds: number
  summary: RolloverSummaryDTO
  warnings: string[]
  entries_count: number
}

export interface RolloverYearRefDTO {
  id: number
  name: string
}

export interface RolloverUserRefDTO {
  id: number | null
  name: string | null
}

export interface RolloverBatchDTO {
  id: string
  academy_id: number
  from_year?: RolloverYearRefDTO | null
  to_year?: RolloverYearRefDTO | null
  status: string
  committed_at: string | null
  committed_by?: RolloverUserRefDTO | null
  undo_closed_at: string | null
  undone_at: string | null
  undone_by?: RolloverUserRefDTO | null
  is_undoable: boolean
  undo_expires_at: string | null
  totals: Partial<RolloverSummaryDTO> | Record<string, number>
  plan_summary?: {
    totals?: Partial<RolloverSummaryDTO>
    entries?: RolloverPreviewEntryDTO[]
  } | null
  notes?: string | null
}

export interface RolloverBatchResponse {
  success: boolean
  batch: RolloverBatchDTO
}

export interface RolloverPaginatedResponse {
  data: RolloverBatchDTO[]
  links?: Record<string, any>
  meta?: Record<string, any>
}

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
