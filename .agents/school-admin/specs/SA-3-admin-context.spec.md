# สเปค SA-3 — frontend: `useAcademyRole` ไม่ยิงซ้ำ + admin context ร่วม

> แผนแม่: `C:\wamp64\www\nuxnan\.agents\plans\school-admin-improvement.md` (ข้อ 2.5 · เฟส 2 งาน 2a/2b · ข้อ 6 แถว SA-3)
> ต่อจาก SA-1 (PR #58) — branch นี้แตกจาก `feat/school-admin-shell-phase1`
> **ไม่อยู่ใน shard นี้:** ย้าย 57 หน้าให้เลิก fetch เอง (= SA-5) · เปลือกใหม่ (= SA-4)

## ข้อห้ามประจำ
- ห้าม `git commit` · `git push` · `php artisan migrate` · `npm run build` · แตะ `.env` · ห้ามแตะ `api/`
- ห้ามแตะไฟล์นอกรายการ · เรียก API ผ่าน `useApi()` เท่านั้น
- รายงานกลับ: `git diff --stat` + ผลรันเกณฑ์แบบดิบ

## บริบท (วิเคราะห์จบแล้ว — ห้ามออกแบบใหม่)
- `ui/composables/useAcademyRole.ts` สร้าง state ใหม่ทุกครั้งที่เรียก และมี `watch(academyId)` (บรรทัด ~226) ที่ fetch เองเมื่อ id เปลี่ยน
  หน้าแอดมินแทบทุกหน้าทำ `academyId.value = id` แล้ว `await fetchMyRole()` ต่อทันที ⇒ `/my-role` ถูกยิง **2 ครั้งต่อหน้า** และเปลือก `admin.vue` ก็ยิงของตัวเองอีก
  ⇒ เปิด `/admin/members` ครั้งแรก = my-role 4 ครั้ง
- composable นี้มีผู้เรียก **67 ไฟล์** — 13 ไฟล์อยู่นอก `admin/` (dashboard ×3 · elections ×4 · schedule-print · `AcademyActionGuide` · `SchoolAttendanceWidget` · `SchoolQuickMenu` · `useAcademyRevenue`) ⇒ **ต้องเข้ากันได้ย้อนหลัง 100%**: ชื่อ/ชนิดของทุกค่าที่ return เดิมห้ามเปลี่ยน
- ไม่มีผู้เรียกคนไหนส่ง argument ให้ `fetchMyRole()` และไม่มีใครใช้ `roleState`/`resetRole` นอกไฟล์

## ไฟล์ที่แก้ได้
1. `C:\wamp64\www\nuxnan\ui\composables\useAcademyRole.ts`
2. สร้างใหม่: `C:\wamp64\www\nuxnan\ui\composables\useAcademyAdminContext.ts`
3. `C:\wamp64\www\nuxnan\ui\pages\academies\[name]\admin.vue` (เฉพาะส่วนที่สั่งในข้อ 3)
4. สร้างใหม่: `C:\wamp64\www\nuxnan\ui\tests\useAcademyRole.spec.ts`

## การแก้

### 1) `useAcademyRole.ts` — cache ระดับโมดูล + รวม request ที่ยิงพร้อมกัน
- import ให้ชัด (ไม่พึ่ง auto-import เพื่อให้ vitest รันได้): `import { ref, computed, readonly, watch } from 'vue'` และ `import { useAuthStore } from '~/stores/auth'`
  (`useApi` ยังเป็น auto-import ตามเดิม)
- ระดับโมดูล (นอกฟังก์ชัน):
```ts
const ROLE_CACHE_TTL_MS = 60_000

interface RoleCacheEntry {
  state: AcademyRoleState
  fetchedAt: number
}

const roleCache = new Map<string, RoleCacheEntry>()
const inflight = new Map<string, Promise<AcademyRoleState | null>>()

/** ล้าง cache ทั้งหมด (ใช้ในเทสต์ หรือเมื่อสิทธิ์ของผู้ใช้ปัจจุบันเปลี่ยน) */
export const clearAcademyRoleCache = () => {
  roleCache.clear()
  inflight.clear()
}
```
- key = `` `${userId ?? 'anon'}:${academyId}` `` โดย `userId = useAuthStore().user?.id` — **ต้องมี user id ใน key** (สลับบัญชีในแท็บเดียวกันต้องไม่ได้สิทธิ์ของคนเก่า)
- `fetchMyRole` เปลี่ยนเป็น `async (options: { force?: boolean } = {})`:
  1. ไม่มี `academyId.value` → return
  2. ถ้าไม่ `force` และมี cache ที่อายุ < TTL → ตั้ง `roleState.value` จาก cache (copy ใหม่ `{ ...entry.state, permissions: [...entry.state.permissions] }`) แล้ว return — **ไม่ยิง request**
  3. ถ้าไม่ `force` และมี `inflight` ของ key นี้ → `await` promise เดิม แล้วตั้ง `roleState` จากผลลัพธ์ (ถ้าไม่ null)
  4. ไม่งั้นยิง `api.get(\`/api/academies/${id}/my-role\`)` แบบเดิม · เก็บ promise ไว้ใน `inflight` · สำเร็จ → เขียน `roleCache` + `roleState` · ลบ `inflight` ใน `finally` เสมอ
  - `isLoading` / `error` ทำงานเหมือนเดิม (true ระหว่างรอ · ข้อความ error เดิม) · request ล้มเหลว **ห้ามเขียน cache**
- `watch(academyId, ...)` คงไว้ (ผู้เรียกบางรายพึ่งให้มัน fetch เอง) แต่เรียก `fetchMyRole()` ตัวเดียวกัน ⇒ เมื่อหน้าเรียกตามทันทีจะได้ promise เดียวกัน = request เดียว
- เพิ่มใน return: `refreshRole: () => fetchMyRole({ force: true })` · **ค่าที่ return เดิมทุกตัวคงไว้ครบ**

### 2) `useAcademyAdminContext.ts` (ใหม่)
```ts
import { inject, provide } from 'vue'
import type { ComputedRef, InjectionKey, Ref } from 'vue'
import type { UseAcademyRoleReturn } from '~/composables/useAcademyRole'

/**
 * SA-3 — context ร่วมของพื้นที่แอดมินโรงเรียน: เปลือก admin.vue provide ครั้งเดียว หน้าลูก inject ไปใช้
 * (หน้าลูกที่ยังดึงเองจะย้ายมาใช้ใน SA-5)
 */
export interface AcademyAdminContext {
  academy: Ref<any>
  academyId: Ref<number | null>
  academyName: ComputedRef<string>
  role: UseAcademyRoleReturn
  can: (permission: string) => boolean
  isAdmin: ComputedRef<boolean>
  refreshRole: () => Promise<void>
}

export const ACADEMY_ADMIN_CONTEXT: InjectionKey<AcademyAdminContext> = Symbol('academy-admin-context')

export const provideAcademyAdminContext = (ctx: AcademyAdminContext) => provide(ACADEMY_ADMIN_CONTEXT, ctx)

export const useAcademyAdminContext = (): AcademyAdminContext => {
  const ctx = inject(ACADEMY_ADMIN_CONTEXT, null)
  if (!ctx) {
    throw new Error('useAcademyAdminContext() ต้องเรียกในหน้าที่อยู่ใต้ pages/academies/[name]/admin.vue')
  }
  return ctx
}
```

### 3) `admin.vue`
- import `provideAcademyAdminContext` จาก `~/composables/useAcademyAdminContext`
- เปลี่ยน `const { myRole, ..., fetchMyRole } = useAcademyRole(academyId)` เป็น
  `const academyRole = useAcademyRole(academyId)` ตามด้วย destructure ชื่อเดิมทั้งหมดจาก `academyRole` (template/โค้ดที่เหลือไม่ต้องแก้)
- ใต้ `provide('academy', academy)` เพิ่ม:
```ts
// SA-3 — context เดียวที่มีชนิด (provide เดิม 3 ตัวด้านบนคงไว้ให้ 16 หน้าที่ inject อยู่)
provideAcademyAdminContext({
  academy,
  academyId,
  academyName,
  role: academyRole,
  can,
  isAdmin,
  refreshRole: academyRole.refreshRole,
})
```
- ห้ามแตะส่วนอื่นของไฟล์

### 4) `tests/useAcademyRole.spec.ts` (vitest · environment node)
- `vi.mock('~/stores/auth', () => ({ useAuthStore: () => authMock }))` โดย `authMock = { user: { id: 1 } }` เปลี่ยน id ได้ในเทสต์
- `vi.stubGlobal('useApi', () => ({ get: getMock }))` · `getMock` คืน `{ success: true, role: {...}, permissions: ['members.view'], is_owner: false, is_admin: false, is_member: true }`
- `beforeEach`: `clearAcademyRoleCache()` + `getMock.mockClear()`
- เคสอย่างน้อย:
  1. สอง instance (`useAcademyRole(ref(5))`) เรียก `fetchMyRole()` พร้อมกัน (`Promise.all`) → `getMock` ถูกเรียก **1 ครั้ง** และทั้งคู่ `can('members.view') === true`
  2. instance เดียว: ตั้ง `academyId.value = 5` (trigger watch) แล้ว `await fetchMyRole()` ทันที → `getMock` 1 ครั้ง (ใช้ `await nextTick()` จาก vue ให้ watch ทำงาน)
  3. เรียกซ้ำภายใน TTL → ไม่ยิงเพิ่ม · `vi.useFakeTimers()` + `vi.setSystemTime` เลื่อนเกิน 60 วิ → ยิงใหม่
  4. `refreshRole()` ยิงใหม่แม้อยู่ใน TTL
  5. เปลี่ยน `authMock.user.id` เป็น 2 → ยิงใหม่ (ไม่ใช้ cache ของ user 1)
  6. request ล้มเหลว (`getMock.mockRejectedValueOnce`) → `error` มีข้อความ · ครั้งถัดไปยิงใหม่ (ไม่ cache ความล้มเหลว)
  7. ค่าที่ return มีครบทุกชื่อเดิม: `roleState myRole permissions isLoading error isOwner isAdmin isMember isTeacher isStudent isParent isStaff isGuest roleName roleDisplayName roleColor roleIcon can canAny canAll canAccessAdmin canManageMembers canManageCourses canViewFinance canViewReports fetchMyRole getDashboardRoute resetRole` + `refreshRole`

## เกณฑ์ผ่าน (Claude รันเอง)
```bash
cd C:/wamp64/www/nuxnan/ui
npx vitest run tests/useAcademyRole.spec.ts tests/academyAdminNav.spec.ts
git diff --stat           # useAcademyRole.ts + admin.vue เท่านั้นที่เป็นไฟล์เดิม
node -e "<compile SFC admin.vue ด้วย @vue/compiler-sfc แบบ SA-1b>"
```
**Claude เปิดจอจริง (บัญชีเจ้าของ · นับ request ใน network):** เปิด `/admin/members` จากหน้าอื่น → `/my-role` = **1 ครั้ง** (เดิม 4) ·
กดเมนูถัดไปภายใน 60 วิ → `/my-role` เพิ่ม 0 · หน้านอกแอดมินที่ใช้ composable (`/dashboard/teacher` หรือหน้าโรงเรียนที่มี `SchoolQuickMenu`) ยังโหลดได้ console ไม่มี error
