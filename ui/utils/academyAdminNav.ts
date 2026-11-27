/**
 * แหล่งเดียวของเมนู + ด่านสิทธิ์ของพื้นที่แอดมินโรงเรียน (/academies/{name}/admin)
 * SA-1 — .agents/plans/school-admin-improvement.md
 *
 * - view      = ถือคีย์ใดคีย์หนึ่ง → เห็นเมนูและเปิดหน้าได้ (ตรงกับ academy.permission ของ route อ่านฝั่ง backend)
 * - manage    = คีย์ของปุ่มเขียนในหน้า (หน้าอ่านเองด้วย can())
 * - adminOnly = เฉพาะ isAdmin (เจ้าของ / academy_admins / super admin) — ใช้เมื่อ backend ยังเช็ค isAdmin
 * - ห้ามใช้ academy.view เป็นเงื่อนไข: ทุกบทบาทยกเว้น guest ถืออยู่ รวมนักเรียนและผู้ปกครอง
 * - เพิ่มหน้าใหม่ใต้ admin/ ต้องเพิ่ม section ที่นี่ด้วย (เทสต์ academyAdminNav.spec.ts จะฟ้อง)
 */

export const ADMIN_ACCESS_KEY = 'admin.access'

export type AdminGroupId =
  | 'overview' | 'users' | 'teaching' | 'students' | 'staff'
  | 'activities' | 'store' | 'reports' | 'settings'

export interface AdminGroup {
  id: AdminGroupId
  label: string
}

export interface AdminSection {
  id: string
  label: string
  icon: string
  /** path ต่อจาก /academies/{name}/admin — '' = แดชบอร์ด */
  path: string
  group: AdminGroupId
  view: string[]
  manage?: string[]
  adminOnly?: boolean
}

export interface PermissionContext {
  isAdmin: boolean
  can: (key: string) => boolean
}

export const ADMIN_GROUPS: AdminGroup[] = [
  { id: 'overview', label: 'ภาพรวม' },
  { id: 'users', label: 'จัดการผู้ใช้' },
  { id: 'teaching', label: 'การเรียนการสอน' },
  { id: 'students', label: 'ข้อมูลนักเรียน' },
  { id: 'staff', label: 'บุคลากร' },
  { id: 'activities', label: 'กิจกรรม & การสื่อสาร' },
  { id: 'store', label: 'ร้านค้า' },
  { id: 'reports', label: 'รายงาน & สถิติ' },
  { id: 'settings', label: 'ตั้งค่า' },
]

export const ADMIN_SECTIONS: AdminSection[] = [
  { id: 'dashboard', label: 'แดชบอร์ด', icon: 'fluent:grid-24-regular', path: '', group: 'overview', view: [] },

  { id: 'members', label: 'สมาชิก', icon: 'fluent:people-24-regular', path: 'members', group: 'users', view: ['members.view'], manage: ['members.manage'] },
  { id: 'requests', label: 'คำขอเข้าร่วม', icon: 'fluent:person-clock-24-regular', path: 'requests', group: 'users', view: ['members.manage'] },
  { id: 'roles', label: 'บทบาทและสิทธิ์', icon: 'fluent:shield-24-regular', path: 'roles', group: 'users', view: ['roles.view'], manage: ['roles.manage'] },
  { id: 'invite-links', label: 'ลิงก์เชิญสมาชิก', icon: 'fluent:link-24-regular', path: 'invite-links', group: 'users', view: ['members.manage'] },
  { id: 'member-tags', label: 'แท็กสมาชิก', icon: 'fluent:tag-24-regular', path: 'member-tags', group: 'users', view: ['members.manage'] },
  { id: 'guardians', label: 'ผู้ปกครอง', icon: 'fluent:people-community-24-regular', path: 'guardians', group: 'users', view: ['guardians.view'], manage: ['guardians.manage'] },

  { id: 'courses', label: 'คอร์สเรียน', icon: 'fluent:book-24-regular', path: 'courses', group: 'teaching', view: ['courses.view'], manage: ['courses.manage'] },
  // backend AcademyAllocationController เช็ค isAdmin
  { id: 'allocations', label: 'จัดสรรแต้ม', icon: 'fluent:arrow-swap-24-regular', path: 'allocations', group: 'teaching', view: [], adminOnly: true },
  { id: 'curriculums', label: 'หลักสูตร', icon: 'fluent:book-globe-24-regular', path: 'curriculums', group: 'teaching', view: ['courses.manage'] },
  { id: 'classrooms', label: 'ห้องเรียน', icon: 'fluent:people-team-24-regular', path: 'classrooms', group: 'teaching', view: ['groups.manage'] },
  { id: 'departments', label: 'ฝ่าย/แผนก', icon: 'fluent:organization-24-regular', path: 'departments', group: 'teaching', view: ['groups.manage'] },
  { id: 'schedule', label: 'ตารางเรียน', icon: 'fluent:calendar-24-regular', path: 'schedule', group: 'teaching', view: ['schedule.view'], manage: ['schedule.manage'] },
  { id: 'schedule-periods', label: 'โครงคาบเรียน', icon: 'fluent:clock-24-regular', path: 'schedule-periods', group: 'teaching', view: ['schedule.manage'] },
  { id: 'schedule-workload', label: 'ภาระงานสอน', icon: 'fluent:people-team-24-regular', path: 'schedule-workload', group: 'teaching', view: ['schedule.view'] },
  { id: 'schedule-exceptions', label: 'สอนแทน / งดคาบ', icon: 'fluent:calendar-edit-24-regular', path: 'schedule-exceptions', group: 'teaching', view: ['schedule.view'], manage: ['schedule.manage'] },

  { id: 'students', label: 'ทะเบียนนักเรียน', icon: 'fluent:hat-graduation-24-regular', path: 'students', group: 'students', view: ['students.view'], manage: ['students.manage'] },
  { id: 'student-cards', label: 'บัตรนักเรียน', icon: 'fluent:contact-card-24-regular', path: 'student-cards', group: 'students', view: ['students.view'], manage: ['students.manage'] },
  { id: 'student-card-requests', label: 'คำร้องทำบัตร', icon: 'fluent:clipboard-checkmark-24-regular', path: 'student-cards/requests', group: 'students', view: ['students.cards.request', 'students.cards.produce'], manage: ['students.cards.produce'] },
  { id: 'school-attendance', label: 'การเข้าเรียน', icon: 'fluent:clipboard-task-24-regular', path: 'school-attendance', group: 'students', view: ['school_attendance.manage'] },
  { id: 'gradebook', label: 'ผลการเรียน', icon: 'fluent:star-24-regular', path: 'gradebook', group: 'students', view: ['grades.manage'] },
  { id: 'home-visits', label: 'เยี่ยมบ้าน', icon: 'fluent:home-person-24-regular', path: 'home-visits', group: 'students', view: ['home_visits.view'], manage: ['home_visits.manage'] },
  { id: 'at-risk', label: 'นักเรียนกลุ่มเสี่ยง', icon: 'fluent:warning-24-regular', path: 'at-risk', group: 'students', view: ['students.view'] },

  { id: 'staff', label: 'ข้อมูลบุคลากร', icon: 'fluent:person-board-24-regular', path: 'staff', group: 'staff', view: ['staff.view'], manage: ['staff.manage'] },

  { id: 'announcements', label: 'ประกาศ', icon: 'fluent:megaphone-24-regular', path: 'announcements', group: 'activities', view: ['announcements.manage', 'announcements.create', 'announcements.create.own'] },
  { id: 'events', label: 'กิจกรรม', icon: 'fluent:calendar-star-24-regular', path: 'events', group: 'activities', view: ['events.manage'] },
  { id: 'house-assignments', label: 'คณะสี (กีฬาสี)', icon: 'fluent:flag-24-regular', path: 'house-assignments', group: 'activities', view: ['sports.manage'] },
  { id: 'elections', label: 'การเลือกตั้งสภานักเรียน', icon: 'fluent:vote-24-regular', path: 'elections', group: 'activities', view: ['elections.view', 'elections.manage'] },
  { id: 'sports-scores', label: 'คะแนนกีฬาสี', icon: 'fluent:trophy-24-regular', path: 'sports-scores', group: 'activities', view: ['sports.view', 'sports.manage'] },

  { id: 'store', label: 'ร้านค้าโรงเรียน', icon: 'fluent:store-24-regular', path: 'store', group: 'store', view: ['store.manage'] },

  { id: 'reports', label: 'สถิติและรายงาน', icon: 'fluent:data-bar-horizontal-24-regular', path: 'reports', group: 'reports', view: ['reports.view'] },
  { id: 'activity-log', label: 'ประวัติกิจกรรม', icon: 'fluent:history-24-regular', path: 'activity-log', group: 'reports', view: ['reports.view'] },
  { id: 'revenue', label: 'รายได้', icon: 'fluent:money-hand-24-regular', path: 'revenue', group: 'reports', view: ['finance.view'], manage: ['finance.manage'] },

  { id: 'school-management', label: 'ระบบบริหารโรงเรียน', icon: 'fluent:building-24-regular', path: 'school-management', group: 'settings', view: ['settings.manage'] },
  { id: 'settings', label: 'ตั้งค่าโรงเรียน', icon: 'fluent:settings-24-regular', path: 'settings', group: 'settings', view: ['settings.view', 'settings.manage'], manage: ['settings.manage'] },
]

/** เท่ากับ useAcademyRole().can() แต่รับอาเรย์สิทธิ์ดิบ (ไม่มีกรณีเจ้าของ) — ใช้กับ response ของ /my-role */
export function hasPermission(permissions: readonly string[], key: string): boolean {
  if (permissions.includes('*') || permissions.includes(key)) return true
  return permissions.some(p => key.startsWith(p + '.'))
}

export function canEnterAcademyAdmin(ctx: PermissionContext): boolean {
  return ctx.isAdmin || ctx.can(ADMIN_ACCESS_KEY)
}

export function canViewSection(section: AdminSection, ctx: PermissionContext): boolean {
  if (ctx.isAdmin) return true
  if (section.adminOnly) return false
  if (section.view.length === 0) return true
  return section.view.some(key => ctx.can(key))
}

/** หา section จาก path ต่อจาก /admin — prefix ยาวสุดชนะ และต้องตรงขอบเขต segment ('schedule' ไม่จับ 'schedule-periods') */
export function findAdminSection(relativePath: string): AdminSection | null {
  const rel = relativePath.replace(/^\/+|\/+$/g, '')
  if (rel === '') return ADMIN_SECTIONS.find(s => s.path === '') ?? null
  let best: AdminSection | null = null
  for (const section of ADMIN_SECTIONS) {
    if (section.path === '') continue
    if (rel === section.path || rel.startsWith(section.path + '/')) {
      if (!best || section.path.length > best.path.length) best = section
    }
  }
  return best
}

function safeDecode(value: string): string {
  try {
    return decodeURIComponent(value)
  } catch {
    return value
  }
}

/** route.path (ชื่อโรงเรียนไทยถูก percent-encode) → path ต่อจาก /admin · null = ไม่ใช่หน้าแอดมิน */
export function adminRelativePath(routePath: string, academyName: string): string | null {
  const decoded = safeDecode(routePath).replace(/\/+$/, '')
  const base = `/academies/${academyName}/admin`
  if (decoded === base) return ''
  if (decoded.startsWith(base + '/')) return decoded.slice(base.length + 1)
  return null
}

export function visibleAdminGroups(ctx: PermissionContext): Array<AdminGroup & { sections: AdminSection[] }> {
  return ADMIN_GROUPS
    .map(group => ({ ...group, sections: ADMIN_SECTIONS.filter(s => s.group === group.id && canViewSection(s, ctx)) }))
    .filter(group => group.sections.length > 0)
}
