import { computed, type Ref, type MaybeRef, unref } from 'vue'

/**
 * Course lifecycle states. Mirrors the PHP enum
 * App\Enums\CourseLifecycleState on the backend.
 */
export type CourseLifecycleState =
  | 'draft'
  | 'active'
  | 'enrollment_closed'
  | 'grading'
  | 'published'
  | 'finalized'
  | 'archived'

export interface LifecycleCourseShape {
  status?: number | string | null
  finalization_status?: string | null
  end_date?: string | null
}

interface BadgeMeta {
  state: CourseLifecycleState
  label: string
  classes: string
  icon: string
}

const BADGE_LOOKUP: Record<CourseLifecycleState, Omit<BadgeMeta, 'state'>> = {
  draft: {
    label: 'ฉบับร่าง',
    classes: 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-700/40 dark:text-gray-300 dark:border-gray-600',
    icon: 'fluent:document-edit-16-regular',
  },
  active: {
    label: 'กำลังเปิดเรียน',
    classes: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800',
    icon: 'fluent:play-circle-16-regular',
  },
  enrollment_closed: {
    label: 'ปิดรับสมัคร',
    classes: 'bg-gray-200 text-gray-700 border-gray-300 dark:bg-gray-700/60 dark:text-gray-200 dark:border-gray-600',
    icon: 'fluent:lock-closed-16-regular',
  },
  grading: {
    label: 'กำลังออกเกรด',
    classes: 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800',
    icon: 'fluent:clipboard-text-edit-16-regular',
  },
  published: {
    label: 'ประกาศเกรดแล้ว',
    classes: 'bg-yellow-100 text-yellow-700 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:border-yellow-800',
    icon: 'fluent:megaphone-16-regular',
  },
  finalized: {
    label: 'สิ้นสุดการเรียน',
    classes: 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800',
    icon: 'fluent:checkmark-circle-16-regular',
  },
  archived: {
    label: 'เก็บถาวร',
    classes: 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-800',
    icon: 'fluent:archive-16-regular',
  },
}

/**
 * Derive the lifecycle state from a course payload. Precedence matches
 * Course::lifecycleState() in the Laravel side:
 *   1. finalization_status archived/finalized/published/grading
 *   2. status = 2 -> draft
 *   3. status = 4 OR end_date past -> enrollment_closed
 *   4. otherwise -> active
 */
export function deriveLifecycleState(course?: LifecycleCourseShape | null): CourseLifecycleState {
  if (!course) return 'active'

  const fin = course.finalization_status
  if (fin === 'archived') return 'archived'
  if (fin === 'finalized') return 'finalized'
  if (fin === 'published') return 'published'
  if (fin === 'grading') return 'grading'

  const status = Number(course.status ?? 1)
  if (status === 2) return 'draft'

  const endDate = course.end_date ? new Date(course.end_date) : null
  if (status === 4 || (endDate && endDate.getTime() < Date.now())) {
    return 'enrollment_closed'
  }

  return 'active'
}

/**
 * Reactive helpers for course lifecycle. Pass a course (ref or plain) and
 * optionally a flag indicating the current user is already a member.
 */
export function useCourseLifecycle(
  course: MaybeRef<LifecycleCourseShape | null | undefined>,
  options: { isMember?: MaybeRef<boolean> } = {}
) {
  const state = computed<CourseLifecycleState>(() => deriveLifecycleState(unref(course)))

  const isEnrollmentOpen = computed(() => state.value === 'active')
  const isLearningActive = computed(() => ['active', 'enrollment_closed'].includes(state.value))
  const isPostEnd = computed(() =>
    ['grading', 'published', 'finalized', 'archived'].includes(state.value)
  )
  const isReadOnly = computed(() => state.value === 'archived')

  const badge = computed<BadgeMeta>(() => ({
    state: state.value,
    ...BADGE_LOOKUP[state.value],
  }))

  /**
   * CTA copy + intent for the join/enter button on cards & detail pages.
   * Frontend reads `intent` to decide whether to call the enrollment API
   * or just navigate; `disabled` short-circuits the click.
   */
  const cta = computed(() => {
    const member = !!unref(options.isMember)

    if (member) {
      if (isReadOnly.value) {
        return { label: 'ดูย้อนหลัง', intent: 'enter', disabled: false }
      }
      return { label: 'เข้าสู่รายวิชา', intent: 'enter', disabled: false }
    }

    switch (state.value) {
      case 'active':
        return { label: 'สมัครเรียน', intent: 'enroll', disabled: false }
      case 'draft':
        return { label: 'ยังไม่เปิดรับสมัคร', intent: 'none', disabled: true }
      case 'enrollment_closed':
        return { label: 'ปิดรับสมัคร', intent: 'none', disabled: true }
      case 'grading':
      case 'published':
        return { label: 'สิ้นสุดการเรียน', intent: 'none', disabled: true }
      case 'finalized':
        return { label: 'ปิดการเรียนแล้ว', intent: 'none', disabled: true }
      case 'archived':
        return { label: 'เก็บถาวร', intent: 'none', disabled: true }
    }
  })

  return {
    state,
    isEnrollmentOpen,
    isLearningActive,
    isPostEnd,
    isReadOnly,
    badge,
    cta,
  }
}

/**
 * Translate a backend REASON_* code into a localized message. Used by API
 * error handlers so a 422 from the lifecycle policy renders a sensible
 * toast/dialog without leaking the raw code.
 */
const REASON_MESSAGES: Record<string, string> = {
  COURSE_DRAFT: 'รายวิชายังเป็นฉบับร่าง ยังไม่เปิดรับสมัคร',
  COURSE_ENROLLMENT_CLOSED: 'รายวิชานี้ปิดรับสมัครแล้ว',
  COURSE_ENDED: 'รายวิชาสิ้นสุดการเรียนการสอนแล้ว',
  COURSE_FINALIZED: 'รายวิชานี้ปิดการเรียนแล้ว',
  COURSE_ARCHIVED: 'รายวิชานี้ถูกเก็บถาวร',
  NOT_A_MEMBER: 'คุณไม่ใช่สมาชิกของรายวิชานี้',
  NOT_AUTHENTICATED: 'กรุณาเข้าสู่ระบบ',
  WORK_TYPE_LOCKED_AFTER_END: 'รายวิชาสิ้นสุดแล้ว ไม่สามารถส่งงาน/ทำแบบทดสอบปกติได้',
  APPEAL_WINDOW_CLOSED: 'หมดเวลายื่นอุทธรณ์เกรดแล้ว',
  NO_REMEDIATION_GRANT: 'คุณไม่มีสิทธิ์สอบแก้ตัวสำหรับรายวิชานี้',
  GRADE_NOT_PUBLISHED: 'ยังไม่มีการประกาศเกรด',
  CERTIFICATE_NOT_ISSUED: 'ยังไม่ได้ออกใบประกาศสำหรับคุณ',
}

export function lifecycleReasonMessage(code?: string | null, fallback = 'ไม่สามารถดำเนินการได้'): string {
  if (!code) return fallback
  return REASON_MESSAGES[code] ?? fallback
}
