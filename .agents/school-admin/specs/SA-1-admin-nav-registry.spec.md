# สเปค SA-1 — frontend: registry เมนู/สิทธิ์ของพื้นที่แอดมิน + ประตูเดียว + เลิกด่าน "เจ้าของเท่านั้น"

> แผนแม่: `C:\wamp64\www\nuxnan\.agents\plans\school-admin-improvement.md` (ข้อ 2.1–2.2 · เฟส 1 · ข้อ 5.1 เคาะแล้ว)
> **ขึ้นกับ SA-2a** (คีย์ `admin.access` + migration) — deploy ด้วยกันเท่านั้น ไม่งั้นครูเข้าแอดมินไม่ได้
> แบ่ง 4 shard ตามลำดับ: **SA-1a → SA-1b → SA-1c** (ต่อกัน) · **SA-1d** อิสระ (ไฟล์ไม่ทับ)
>
> วิธีรัน (agy หมดโควตาถึงราว 2026-11-27 — ใช้ Codex ผ่าน `codex:rescue` แทนได้):
> `agy --print "Read C:\wamp64\www\nuxnan\.agents\school-admin\specs\SA-1-admin-nav-registry.spec.md and do ONLY shard SA-1a." --add-dir "C:\wamp64\www\nuxnan" --model claude-opus-5-5-high --mode accept-edits --print-timeout 20m`

## ข้อห้ามประจำ (ทุก shard)

- ห้าม `git commit` · `git push` · `php artisan migrate` · `npm run build` · แตะ `.env`
- ห้ามแตะไฟล์นอกรายการของ shard · งานที่บอกว่า add-only: **มีบรรทัด `-` ที่ไม่ได้สั่ง = ไม่ผ่าน**
- เรียก API ผ่าน `useApi()` เท่านั้น (`const api = useApi()` แล้ว `api.get(...)`) — ห้าม `await useApi(url)` · ห้าม `$fetch`/axios
- ไฟล์ที่ใช้ `<Icon` ต้องมี `import { Icon } from '@iconify/vue'` (ไม่ใช่ Nuxt module)
- template ของหน้า/คอมโพเนนต์ต้องมี root element เดียว (ห้ามมีคอมเมนต์หรือ Teleport ข้าง root)
- รายงานกลับ: `git diff --stat` + ผลรันเกณฑ์แบบดิบ

```
กติกา UI บังคับ (mobile-first — ห้ามละเมิด):
- ออกแบบจากจอ 375px ขึ้นไป: class ที่ไม่มี prefix = สไตล์มือถือ แล้วค่อยเพิ่ม sm: md: lg:
  ห้ามเขียน desktop-first เช่น `flex-row md:flex-col` — ต้องเป็น `flex-col md:flex-row`
- ห้ามใช้ `hidden` ซ่อนข้อมูลสำคัญบนมือถือ ให้จัดวางใหม่แทน (ซ้อนเป็นแถว/ย้ายลงล่าง)
- ปุ่ม/ลิงก์ที่กดได้ต้อง touch target >= 44px บนมือถือ (`min-h-[44px]` หรือ `p-3`) แล้วค่อยลดที่ sm:
- แถว flex: ฝั่งที่ห้ามถูกบีบใส่ `flex-shrink-0 whitespace-nowrap`,
  ฝั่งข้อความใส่ `min-w-0 flex-1 break-words` (ภาษาไทยไม่มีช่องว่าง จะแตกแนวตั้งถ้าโดนบีบ)
- ตาราง/โค้ด/ไดอะแกรมกว้าง ต้องอยู่ในกล่อง `overflow-x-auto` ของตัวเอง — ทั้งหน้าห้ามเลื่อนแนวนอน
- padding/font ไล่เล็กไปใหญ่: `p-3 sm:p-6`, `text-sm sm:text-base`
- `<script setup lang="ts">` เป็นค่าเริ่มต้น · เรียก API ผ่าน `useApi` เท่านั้น ห้าม `$fetch`/`axios` ตรง ๆ
- ห้าม import อะไรจาก `@inertiajs/*` ในโค้ดใหม่
```

## บริบท (วิเคราะห์จบแล้ว — ห้ามออกแบบใหม่)

- เมนูข้าง (`admin.vue`) เขียนเงื่อนไขเองทีละรายการ ส่วนแต่ละหน้าเขียนด่านของตัวเอง → **เมนูโชว์แต่หน้าเด้ง** 5–6 รายการต่อบทบาท
- ประตูเปลือกเช็ค `isAdmin || role.name === 'teacher'` และ **19 หน้าเช็ค `if (!isAdmin.value)`** — `isAdmin` ฝั่งหน้าเว็บ = เจ้าของคนเดียว
- **9 เมนูใช้ `|| can('academy.view')`** แต่ทุกบทบาทยกเว้น guest ถือ `academy.view` (รวมนักเรียน/ผู้ปกครอง)
- **วิธีแก้:** ไฟล์ registry เดียว `ui/utils/academyAdminNav.ts` เป็นแหล่งความจริงของ "เมนูไหน ใครเห็น ใครเปิดได้"
  เปลือกใช้วาดเมนู + ตัดสินว่าจะแสดงหน้าหรือหน้า "ไม่มีสิทธิ์" · ประตูเดียว = `isAdmin || can('admin.access')`
- คีย์ `view` ของแต่ละ section คัดจาก middleware ของ route backend จริง (`route:list -v` 2026-11-23) ·
  หน้าที่เคยล็อก `isAdmin` ใช้คีย์ **manage** เป็น `view` ไปก่อน เพราะหน้าเหล่านั้นไม่มีการซ่อนปุ่มเขียน (เปิดโหมดอ่านอย่างเดียวเป็นงานถัดไป)
- ผลที่ต้องได้ (จำลองกับสิทธิ์จริงใน DB แล้ว): owner 35 · director/admin 31 · teacher 15 · registrar 13 · staff 9 · finance_staff 8 · card_admin 6 section ·
  student/parent/guest เข้าไม่ได้ · ทุก 73 หน้าใต้ `admin/` จับคู่ section ได้

---

## SHARD SA-1a — registry + fixture + เทสต์ (สร้างไฟล์ใหม่ล้วน)

### ไฟล์ที่สร้าง (ห้ามแตะไฟล์อื่น)
1. `C:\wamp64\www\nuxnan\ui\utils\academyAdminNav.ts`
2. `C:\wamp64\www\nuxnan\ui\tests\fixtures\academyRolePermissions.json`
3. `C:\wamp64\www\nuxnan\ui\tests\academyAdminNav.spec.ts`

### 1) `ui/utils/academyAdminNav.ts` — คัดลอกตามนี้ทุกตัวอักษร
```ts
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
```

### 2) `ui/tests/fixtures/academyRolePermissions.json` — สิทธิ์ของบทบาทระบบใน DB dev 2026-11-23 **หลัง** migration ของ SA-2a (คัดลอกตามนี้)
```json
{
  "owner": ["*"],
  "director": ["academy.view","members.view","members.manage","members.roles.manage","courses.view","courses.create","courses.edit","courses.delete","students.view","students.manage","teachers.view","teachers.manage","finance.view","finance.reports","reports.view","reports.export","announcements.view","announcements.create","announcements.manage","home_visits.view","home_visits.manage","elections.view","elections.manage","elections.station","guardians.view","guardians.manage","guardians.sensitive.view","guardians.sensitive.manage","guardians.appoint","settings.view","settings.manage","students.cards.produce","behavior.view","behavior.record","behavior.approve","behavior.manage","roles.view","roles.manage","groups.view","groups.manage","schedule.view","grades.view","grades.manage","staff.view","staff.manage","events.view","events.manage","school_attendance.view","school_attendance.manage","courses.manage","emergency.view","emergency.manage","schedule.manage","students.create","students.import","students.lifecycle","students.activate_account","students.export","students.delete","admin.access"],
  "admin": ["academy.view","members.view","members.manage","courses.view","courses.create","courses.edit","students.view","students.manage","teachers.view","teachers.manage","finance.view","reports.view","announcements.view","announcements.create","announcements.manage","home_visits.view","home_visits.manage","elections.view","elections.manage","elections.station","guardians.view","guardians.manage","guardians.sensitive.view","guardians.sensitive.manage","guardians.appoint","settings.view","settings.manage","students.cards.produce","behavior.view","behavior.record","behavior.approve","behavior.manage","roles.view","roles.manage","groups.view","groups.manage","schedule.view","grades.view","grades.manage","staff.view","staff.manage","events.view","events.manage","school_attendance.view","school_attendance.manage","courses.manage","emergency.view","emergency.manage","schedule.manage","students.create","students.import","students.lifecycle","students.activate_account","students.export","students.delete","admin.access"],
  "teacher": ["academy.view","members.view","courses.view","courses.create","courses.edit.own","students.view","attendance.manage","gradebook.manage","announcements.view","announcements.create.own","home_visits.view","home_visits.create","elections.view","elections.station","students.cards.request","behavior.view","behavior.record","grades.view","schedule.view","groups.view","staff.view","events.view","school_attendance.view","school_attendance.manage","admin.access"],
  "registrar": ["academy.view","members.view","students.view","students.create","students.import","students.lifecycle","students.activate_account","students.export","reports.view","announcements.view","elections.view","guardians.view","guardians.manage","guardians.sensitive.view","guardians.sensitive.manage","guardians.appoint","behavior.view","groups.view","schedule.view","staff.view","events.view","students.manage","students.delete","admin.access"],
  "staff": ["academy.view","members.view","students.view","announcements.view","elections.view","behavior.view","schedule.view","events.view","admin.access"],
  "finance_staff": ["academy.view","members.view","students.view","finance.view","finance.manage","finance.reports","announcements.view","elections.view","events.view","settings.view","admin.access"],
  "card_admin": ["academy.view","members.view","students.view","students.cards.produce","admin.access"],
  "student": ["academy.view","courses.view.enrolled","assignments.view.own","assignments.submit","grades.view.own","schedule.view.own","announcements.view","elections.view","behavior.view.own","schedule.view"],
  "parent": ["academy.view","children.view","children.grades.view","children.attendance.view","children.schedule.view","payments.view","payments.pay","announcements.view","messages.teacher","children.behavior.view","schedule.view"],
  "guest": ["academy.view.public"]
}
```

### 3) `ui/tests/academyAdminNav.spec.ts` — vitest (environment node · import จาก `../utils/academyAdminNav`)
ตัวช่วยในเทสต์: `ctxFor(role)` = `{ isAdmin: role === 'owner', can: k => role === 'owner' || hasPermission(fixture[role], k) }`
ต้องมีเคสต่อไปนี้ครบ (ชื่อ `it(...)` ภาษาไทยหรืออังกฤษก็ได้):
1. **ประตู:** `canEnterAcademyAdmin` = true สำหรับ owner, director, admin, teacher, registrar, staff, finance_staff, card_admin · false สำหรับ student, parent, guest
2. **section ที่เห็นต่อบทบาท** — เทียบ id ตามลำดับใน `ADMIN_SECTIONS` แบบเป๊ะ (`toEqual`):
   - teacher: `dashboard, members, courses, schedule, schedule-workload, schedule-exceptions, students, student-cards, student-card-requests, school-attendance, home-visits, at-risk, staff, announcements, elections`
   - staff: `dashboard, members, schedule, schedule-workload, schedule-exceptions, students, student-cards, at-risk, elections`
   - card_admin: `dashboard, members, students, student-cards, student-card-requests, at-risk`
   - finance_staff: `dashboard, members, students, student-cards, at-risk, elections, revenue, settings`
   - registrar: `dashboard, members, guardians, schedule, schedule-workload, schedule-exceptions, students, student-cards, at-risk, staff, elections, reports, activity-log`
   - director: ทุก section **ยกเว้น** `allocations, house-assignments, sports-scores, store` (= 31 รายการ)
   - owner: ครบ 35 รายการ
3. **ทุกหน้าใต้ `pages/academies/[name]/admin/` จับคู่ section ได้:** เดินโฟลเดอร์ด้วย `node:fs` จาก `path.resolve(process.cwd(), 'pages/academies/[name]/admin')`
   แปลงชื่อไฟล์ → path: ตัด `.vue` · ตัด `index` ท้าย · แทน `[...]` ด้วย `x` · ใช้ `/` คั่น → `findAdminSection(rel)` ต้องไม่เป็น null ทุกไฟล์ (ตอนนี้มี 73 ไฟล์)
4. **findAdminSection:** `'student-cards/requests/5'` → `student-card-requests` · `'student-cards/5/edit'` → `student-cards` ·
   `'schedule-periods'` → `schedule-periods` · `'schedule'` → `schedule` · `''` → `dashboard` · `'nope'` → `null`
5. **adminRelativePath:** `('/academies/' + encodeURIComponent('โรงเรียนทดสอบ') + '/admin/members', 'โรงเรียนทดสอบ')` → `'members'` ·
   path ที่เป็น `/admin` เฉย ๆ → `''` · `/academies/x/feed` → `null` · มี `/` ท้าย → ตัดออก
6. **hasPermission:** `['courses.edit']` ให้ `courses.edit.own` = true · `['academy.view.public']` ให้ `academy.view` = false · `['*']` ให้อะไรก็ true
7. **ทุก section (ยกเว้น dashboard และ adminOnly) มี `view` อย่างน้อย 1 คีย์ และไม่มี section ไหนใช้ `academy.view`**

### เกณฑ์ผ่าน SA-1a
```bash
cd C:/wamp64/www/nuxnan/ui
npx vitest run tests/academyAdminNav.spec.ts
git status --short   # ต้องมีแค่ 3 ไฟล์ใหม่นี้
```

---

## SHARD SA-1b — ต่อ registry เข้าเปลือก + ปุ่มทางเข้า + เมนูลัด + ตัวเลือกแดชบอร์ด

### ไฟล์ที่แก้ได้ (ห้ามแตะไฟล์อื่น)
1. `C:\wamp64\www\nuxnan\ui\pages\academies\[name]\admin.vue`
2. สร้างใหม่: `C:\wamp64\www\nuxnan\ui\components\academy\admin\AcademyAdminForbidden.vue`
3. `C:\wamp64\www\nuxnan\ui\pages\academies\[name].vue`
4. `C:\wamp64\www\nuxnan\ui\composables\useAcademyNavigation.ts`
5. `C:\wamp64\www\nuxnan\ui\pages\academies\[name]\dashboard\index.vue`

### 1) `admin.vue`
**(ก) script** — ใต้ `import { Icon } from '@iconify/vue'` เพิ่ม:
```ts
import AcademyAdminForbidden from '~/components/academy/admin/AcademyAdminForbidden.vue'
import {
  adminRelativePath,
  canEnterAcademyAdmin,
  canViewSection,
  findAdminSection,
  visibleAdminGroups,
} from '~/utils/academyAdminNav'
```
**(ข)** ใน destructuring ของ `useAcademyRole(academyId)` ลบ `isTeacher,` ออก (ไม่ใช้แล้ว) — บรรทัดอื่นคงเดิม

**(ค)** ลบ `const menuItems = computed(() => [ ... ])` **ทั้งก้อน** (ตั้งแต่ `// Menu items` จนถึง `])` ก่อน `// Fetch data`) แล้ววางแทนด้วย:
```ts
// SA-1 — เมนูและด่านสิทธิ์มาจาก utils/academyAdminNav.ts ที่เดียว
const permissionCtx = computed(() => ({ isAdmin: isAdmin.value, can }))
const navGroups = computed(() => visibleAdminGroups(permissionCtx.value))
const sectionTo = (path: string) =>
  path ? `/academies/${academyName.value}/admin/${path}` : `/academies/${academyName.value}/admin`
const currentSection = computed(() => {
  const rel = adminRelativePath(route.path, academyName.value)
  return rel === null ? null : findAdminSection(rel)
})
// หน้าที่ไม่มีใน registry: ให้เฉพาะ isAdmin (fail closed — เทสต์ SA-1a กันไม่ให้มีหน้าตกหล่น)
const canViewCurrent = computed(() =>
  currentSection.value ? canViewSection(currentSection.value, permissionCtx.value) : isAdmin.value
)

// มือถือ: ปิดเมนูเองหลังเลือก ไม่งั้นเนื้อหาถูกดันลงใต้กริดเมนู
watch(() => route.fullPath, () => {
  isSidebarOpen.value = false
})
```
**(ง) ประตู** — แทนที่
```ts
      // Check if user has admin access
      if (!isAdmin.value && !isTeacher.value) {
```
ด้วย
```ts
      // SA-1 — ประตูเดียว: isAdmin หรือถือ admin.access
      if (!canEnterAcademyAdmin(permissionCtx.value)) {
```
(สองบรรทัดถัดไป `navigateTo(...)` / `return` คงเดิม)

**(จ) template เมนู** — แทนที่บล็อก `<div v-for="group in menuItems" ...> ... </div>` (ทั้งบล็อกใน `<nav>`) ด้วย:
```html
          <div v-for="group in navGroups" :key="group.id">
            <h3 class="mb-2 px-3 text-[13px] font-normal uppercase tracking-[.18rem] text-gray-900 dark:text-gray-100">
              {{ group.label }}
            </h3>
            <ul class="grid grid-cols-2 gap-1 sm:grid-cols-3 lg:grid-cols-1">
              <li v-for="section in group.sections" :key="section.id">
                <NuxtLink
                  :to="sectionTo(section.path)"
                  :class="[
                    'flex min-h-[44px] items-center gap-2 rounded-lg px-3 py-2.5 min-w-0 transition-all duration-300 ease-in-out lg:min-h-0',
                    currentSection?.id === section.id
                      ? 'bg-primary-500 text-white shadow-[0_10px_20px_-10px_rgba(14,165,233,0.6)]'
                      : 'text-gray-600 dark:text-gray-300 hover:bg-primary-500/10 hover:text-primary-600 dark:hover:text-primary-400'
                  ]"
                >
                  <Icon :icon="section.icon" class="w-5 h-5 flex-shrink-0" />
                  <span class="text-sm leading-tight break-words min-w-0">{{ section.label }}</span>
                </NuxtLink>
              </li>
            </ul>
          </div>
```
ฟังก์ชัน `isActiveRoute` ไม่มีใครใช้แล้ว → ลบได้

**(ฉ) template เนื้อหา** — แทนที่ `<NuxtPage />` ด้วย:
```html
          <AcademyAdminForbidden v-if="!canViewCurrent" :section="currentSection" :academy-name="academyName" />
          <NuxtPage v-else />
```

### 2) `components/academy/admin/AcademyAdminForbidden.vue` (ใหม่ — คัดลอกตามนี้)
```vue
<script setup lang="ts">
/**
 * SA-1 — แสดงในกรอบเปลือกแอดมินเมื่อผู้ใช้ไม่มีสิทธิ์เปิด section ปัจจุบัน (แทนการเด้งออกเงียบ ๆ)
 */
import { Icon } from '@iconify/vue'
import type { AdminSection } from '~/utils/academyAdminNav'

const props = defineProps<{
  section: AdminSection | null
  academyName: string
}>()

const dashboardTo = computed(() => `/academies/${props.academyName}/admin`)
</script>

<template>
  <div class="flex flex-col items-center gap-4 px-3 py-10 text-center sm:py-16">
    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-amber-500/10 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
      <Icon icon="fluent:shield-lock-24-regular" class="h-7 w-7" />
    </div>
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white sm:text-xl">ไม่มีสิทธิ์เปิดหน้านี้</h2>

    <p v-if="!section" class="max-w-md break-words text-sm text-gray-600 dark:text-gray-300 sm:text-base">
      หน้านี้ยังไม่ได้ลงทะเบียนสิทธิ์ เปิดได้เฉพาะเจ้าของหรือผู้ดูแลโรงเรียน
    </p>
    <p v-else-if="section.adminOnly" class="max-w-md break-words text-sm text-gray-600 dark:text-gray-300 sm:text-base">
      หน้า "{{ section.label }}" เปิดได้เฉพาะเจ้าของหรือผู้ดูแลโรงเรียน
    </p>
    <template v-else>
      <p class="max-w-md break-words text-sm text-gray-600 dark:text-gray-300 sm:text-base">
        หน้า "{{ section.label }}" ต้องมีสิทธิ์อย่างน้อยหนึ่งข้อต่อไปนี้
      </p>
      <ul class="flex max-w-md flex-wrap justify-center gap-2">
        <li
          v-for="key in section.view"
          :key="key"
          class="rounded-md bg-gray-100 px-2 py-1 font-mono text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-200"
        >
          {{ key }}
        </li>
      </ul>
    </template>

    <p class="max-w-md break-words text-xs text-gray-500 dark:text-gray-400 sm:text-sm">
      ติดต่อผู้ดูแลโรงเรียนเพื่อขอสิทธิ์ที่หน้า "บทบาทและสิทธิ์"
    </p>
    <NuxtLink
      :to="dashboardTo"
      class="inline-flex min-h-[44px] items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-600 dark:hover:bg-primary-400 sm:min-h-0"
    >
      <Icon icon="fluent:arrow-left-24-regular" class="h-5 w-5" />
      กลับแดชบอร์ด
    </NuxtLink>
  </div>
</template>
```

### 3) `pages/academies/[name].vue` (ปุ่ม "จัดการโรงเรียน")
- ใต้ `const isAcademyAdmin = ref(false)` เพิ่ม `const canEnterAdmin = ref(false)`
- ใต้ `isAcademyAdmin.value = response.isAcademyAdmin || false` เพิ่ม
  `canEnterAdmin.value = response.canEnterAdmin ?? isAcademyAdmin.value`
- ปุ่ม `<NuxtLink v-if="academy.authIsAcademyAdmin" :to="\`/academies/${academyName}/admin\`" ...>` (ข้อความ "จัดการโรงเรียน"):
  เปลี่ยน `v-if` เป็น `v-if="canEnterAdmin"` และเติม `min-h-[44px] sm:min-h-0` ต้น class — **ห้ามแตะจุดอื่นที่ใช้ `authIsAcademyAdmin`**

### 4) `composables/useAcademyNavigation.ts`
- เพิ่ม `import { ADMIN_ACCESS_KEY } from '~/utils/academyAdminNav'`
- รายการ `id: 'manage-school'`: `visibleWhen: (c) => c.role.isAdmin.value || c.role.can(ADMIN_ACCESS_KEY)`

### 5) `pages/academies/[name]/dashboard/index.vue`
- เพิ่ม `import { ADMIN_ACCESS_KEY, hasPermission } from '~/utils/academyAdminNav'`
- แทนบล็อกตั้งแต่ `// Determine dashboard based on role` ถึง `router.replace(...)` ด้วย:
```ts
    // SA-1 — แอดมิน/บทบาทฝั่งบุคลากรไปหน้าแอดมิน · ครู/ผู้ปกครอง/นักเรียนไปแดชบอร์ดของตัว
    // (เดิมส่ง staff ไป /dashboard/staff ที่ไม่มีหน้า และส่งบทบาทอื่นไปแดชบอร์ดนักเรียน)
    const base = `/academies/${academyName.value}`
    const roleName = roleResponse.role?.name
    const permissions: string[] = roleResponse.permissions || []
    let target = base

    if (roleResponse.is_owner || roleResponse.is_admin) {
      target = `${base}/admin`
    } else if (roleName === 'teacher') {
      target = `${base}/dashboard/teacher`
    } else if (roleName === 'parent') {
      target = `${base}/dashboard/parent`
    } else if (roleName === 'student') {
      target = `${base}/dashboard/student`
    } else if (hasPermission(permissions, ADMIN_ACCESS_KEY)) {
      target = `${base}/admin`
    } else if (roleResponse.is_member) {
      target = `${base}/dashboard/student`
    }

    router.replace(target)
```

### เกณฑ์ผ่าน SA-1b
```bash
cd C:/wamp64/www/nuxnan/ui
grep -c "menuItems\|isTeacher" "pages/academies/[name]/admin.vue"            # ต้อง = 0
grep -c "academy.view" "pages/academies/[name]/admin.vue"                     # ต้อง = 0
grep -c "settings.view" composables/useAcademyNavigation.ts                  # ต้อง = 0
grep -c "dashboard/staff\|dashboardPath = 'admin'" "pages/academies/[name]/dashboard/index.vue"   # ต้อง = 0
node -e "const {parse}=require('@vue/compiler-sfc');for(const f of ['pages/academies/[name]/admin.vue','components/academy/admin/AcademyAdminForbidden.vue','pages/academies/[name].vue','pages/academies/[name]/dashboard/index.vue']){const r=parse(require('fs').readFileSync(f,'utf8'));console.log(f, r.errors.length?r.errors:'OK')}"
npx vitest run tests/academyAdminNav.spec.ts
```
**Claude เปิดจอจริงเอง** (browser pane · 375px ก่อน แล้ว 768/1280): เจ้าของเห็นเมนูครบ 35 · เมนูมือถือปิดเองหลังกด ·
บัญชีครู: เมนูเหลือ 15 รายการ และพิมพ์ URL `/admin/gradebook` ตรง ๆ ต้องเห็นหน้า "ไม่มีสิทธิ์" ในกรอบ (ไม่เด้งออก) · console ไม่มี error

---

## SHARD SA-1c — ถอดด่าน `if (!isAdmin.value)` ออกจาก 19 หน้า

**ทำหลัง SA-1b เท่านั้น** (ถ้าถอดก่อน หน้าเหล่านี้จะเปิดให้ทุกคนที่ผ่านประตู)

### ไฟล์ที่แก้ได้ (19 ไฟล์ ใต้ `C:\wamp64\www\nuxnan\ui\pages\academies\[name]\admin\`)
`classrooms\index.vue` · `curriculums.vue` · `departments\index.vue` ·
`gradebook\academic-years.vue` · `gradebook\classrooms\[id].vue` · `gradebook\classrooms\index.vue` · `gradebook\grade-scales.vue` · `gradebook\index.vue` ·
`gradebook\rollover\history.vue` · `gradebook\rollover\index.vue` · `gradebook\students\[studentId]\transcript.vue` · `gradebook\students\index.vue` ·
`gradebook\subjects.vue` · `gradebook\transcripts\index.vue` ·
`store\categories.vue` · `store\index.vue` · `store\orders.vue` · `store\products.vue` · `store\settings.vue`

### การแก้ (ทุกไฟล์รูปเดียวกัน)
- ลบบล็อก guard นี้ **เท่านั้น** (เยื้องและ path ปลายทางต่างกันได้ บางไฟล์เป็น `await navigateTo`):
```ts
      if (!isAdmin.value) {
        navigateTo(`/academies/${academyName.value}`)   // หรือ .../admin
        return
      }
```
- ถ้าลบแล้ว `isAdmin` ไม่ถูกใช้ที่ไหนในไฟล์ → ลบออกจาก destructuring ของ `useAcademyRole(...)` ด้วย
- `classrooms\index.vue` อีกจุดเดียว: `<div v-if="isAdmin" class="flex border-b ...">` (แท็บในโมดัลนักเรียน) → `v-if="can('groups.manage')"`
- **ห้ามแตะบรรทัดอื่น** — งานนี้ควรเป็น `-` เกือบทั้งหมด (ประมาณ 4 บรรทัดต่อไฟล์)

เหตุผล: เปลือก (SA-1b) ตัดสินด้วย registry แล้ว — section ของหน้าพวกนี้ใช้คีย์ manage (`courses.manage` · `groups.manage` · `grades.manage` · `store.manage`)
และ backend ของเมนูเหล่านี้เปิดตามคีย์เดียวกันแล้ว (#9 · #10 CL-S4 · #13 CR-S1 · #19 GR-S1 · ร้านค้าหลัง SA-2b)

### เกณฑ์ผ่าน SA-1c
```bash
cd "C:/wamp64/www/nuxnan/ui/pages/academies/[name]/admin"
grep -rlE "if \(!isAdmin\.value\) \{" . --include=*.vue | wc -l      # ต้อง = 0
git diff --stat -- .                                                  # 19 ไฟล์ · insertions ≤ 2
```
แล้ว compile SFC ทั้ง 19 ไฟล์ด้วย `@vue/compiler-sfc` (แบบเดียวกับ SA-1b) · **Claude เปิดจอจริง** ด้วยบัญชีเจ้าของ: ผลการเรียน · ห้องเรียน · หลักสูตร · ร้านค้า ยังโหลดข้อมูลปกติ

---

## SHARD SA-1d — หน้า "บทบาทและสิทธิ์" ใช้แคตตาล็อกสิทธิ์จริงจาก backend

### บริบท
`roles.vue:41-103` ฮาร์ดโค้ด `permissionGroups` ไว้ 8 กลุ่ม ~22 คีย์ ขณะที่แคตตาล็อกจริง (`AcademyPermission::PERMISSIONS`) มี 28 กลุ่ม 120+ คีย์ (30 กลุ่มหลัง SA-2a)
⇒ บทบาทที่โรงเรียนสร้างเองติ๊ก `admin.access` · `store.manage` · `schedule.*` · `school_attendance.*` ฯลฯ ไม่ได้เลย
(เคยวางไว้เป็น G6 / S1 ของเมนู #1 แต่โค้ดยังเป็นแบบฮาร์ดโค้ด) · endpoint มีแล้ว: `GET /api/academies/permissions/all` → `{ success, permissions: { <group>: [{ name, display_name }] } }`

### ไฟล์ที่แก้ได้
`C:\wamp64\www\nuxnan\ui\pages\academies\[name]\admin\roles.vue` เท่านั้น

### การแก้
- แทน `const permissionGroups = [ ... ]` (บรรทัด 40–103) ด้วย:
```ts
// Available permissions — SA-1d: ดึงจากแคตตาล็อกจริงของ backend (เดิมฮาร์ดโค้ด 22 คีย์)
interface PermissionGroup {
  name: string
  permissions: { key: string, label: string }[]
}

const PERMISSION_GROUP_LABELS: Record<string, string> = {
  admin: 'หน้าจัดการโรงเรียน', members: 'สมาชิก', roles: 'บทบาท', groups: 'กลุ่ม/ฝ่าย/แผนก', academy: 'โรงเรียน',
  courses: 'คอร์สเรียน', students: 'ข้อมูลนักเรียน', teachers: 'ครู', attendance: 'เช็คชื่อรายวิชา',
  gradebook: 'สมุดคะแนนรายวิชา', grades: 'ผลการเรียน', assignments: 'งานที่มอบหมาย', schedule: 'ตารางเรียน',
  finance: 'การเงิน', payments: 'การชำระเงิน', reports: 'รายงาน', announcements: 'ประกาศ', home_visits: 'เยี่ยมบ้าน',
  messages: 'ข้อความ', children: 'บุตรหลาน (ผู้ปกครอง)', guardians: 'ผู้ปกครอง', staff: 'บุคลากร', settings: 'ตั้งค่า',
  behavior: 'พฤติกรรม', events: 'กิจกรรม', elections: 'การเลือกตั้ง', sports: 'กีฬาสี',
  school_attendance: 'การเข้าเรียน (โรงเรียน)', emergency: 'ประกาศฉุกเฉิน', store: 'ร้านค้า',
}

const permissionGroups = ref<PermissionGroup[]>([])
const permissionsError = ref(false)

const fetchPermissionCatalog = async () => {
  permissionsError.value = false
  try {
    const response: any = await api.get('/api/academies/permissions/all')
    const grouped = response?.permissions || {}
    permissionGroups.value = Object.entries(grouped).map(([group, items]: [string, any]) => ({
      name: PERMISSION_GROUP_LABELS[group] || group,
      permissions: (items || []).map((item: any) => ({ key: item.name, label: item.display_name })),
    }))
  } catch (err) {
    console.error('Failed to fetch permission catalog:', err)
    permissionsError.value = true
  }
}
```
- ใน `onMounted` หลัง `await fetchRoles()` เพิ่ม `await fetchPermissionCatalog()`
- ทุกที่ที่ใช้ `typeof permissionGroups[0]` → เปลี่ยนเป็น `PermissionGroup` · ทุกที่ใน `<script>` ที่วนลูป `permissionGroups` → `permissionGroups.value` (ใน template ใช้ชื่อเดิมได้)
- ในโมดัลสร้าง/แก้บทบาท เหนือรายการสิทธิ์: ถ้า `permissionsError` แสดงข้อความ "โหลดรายการสิทธิ์ไม่สำเร็จ" + ปุ่ม "ลองใหม่" (`min-h-[44px] sm:min-h-0`) ที่เรียก `fetchPermissionCatalog`
- รายการกลุ่มต้องเลื่อนได้ในกล่องของตัวเอง (มี ~30 กลุ่ม) — ถ้ากล่องเดิมยังไม่มี `max-h`/`overflow-y-auto` ให้เติม `max-h-[60vh] overflow-y-auto`
- **ห้ามแก้** ตรรกะบันทึกบทบาท · systemRoles · การลบ — ห้ามลบคีย์ที่บทบาทถืออยู่แต่ไม่อยู่ในแคตตาล็อกออกจาก `roleForm.permissions`

### เกณฑ์ผ่าน SA-1d
```bash
cd C:/wamp64/www/nuxnan/ui
grep -c "members.view', label" "pages/academies/[name]/admin/roles.vue"     # ต้อง = 0 (ไม่เหลือฮาร์ดโค้ด)
grep -c "permissions/all" "pages/academies/[name]/admin/roles.vue"          # ต้อง ≥ 1
grep -c "dark:" "pages/academies/[name]/admin/roles.vue"                    # ต้องไม่น้อยกว่าเดิม (78)
```
compile SFC · **Claude เปิดจอจริง:** โมดัลสร้างบทบาทมีกลุ่ม "หน้าจัดการโรงเรียน" (`admin.access`) และ "ร้านค้า" (`store.manage`) · ที่ 375px รายการสิทธิ์เลื่อนในกล่อง ไม่ล้นจอ

---

## หลังจบทุก shard

- Claude ตรวจ `git diff` ทุกบรรทัด + รันเกณฑ์เอง + เปิดจอจริง (เจ้าของ · ครู · เจ้าหน้าที่ ถ้ามีบัญชีทดสอบ)
- อัปเดต Review Log ในแผนแม่ + สถานะใน `.agents/school-admin/OVERVIEW.md` + `.agents/worklog.md`
- `npm run build` เจ้าของรันเอง
