# Latest Analysis - nuxnan shared AI context

Purpose: this file is the always-current analysis board for AI agents working on
nuxnan. Read it after `AGENTS.md`, `.agents/rules/project.md`, and
`.agents/worklog.md` before changing code.

## Update Protocol

- Update this file whenever work changes direction, a meaningful analysis is made, files are edited, verification is run, or a task is handed to another agent.
- Keep `Current Snapshot` and `Active Work` fresh.
- Append short entries to `Analysis Timeline`; do not rewrite history unless consolidating old noise.
- If multiple agents are working, claim a small scope in `Coordination Board` before editing.
- Release or update your claim when done, blocked, or handing off.
- Mention exact files, commands, assumptions, and remaining risks.
- Keep secrets out of this file. Never paste `.env` values, tokens, private keys, or user credentials.

## User Analysis Input

> Trigger: when the user says "อ่านบทวิเคราะห์", read this section, verify it against the codebase, improve or correct it, make a clear work plan, and record that plan below.

(ว่าง — รอผู้ใช้ระบุหัวข้อใหม่)

---

## Work Plan

### บริบท (สถานะปัจจุบัน 2026-06-04)

ไฟล์ที่ค้างอยู่ใน git (modified, unstaged) มี 5 กลุ่ม:

| ไฟล์ | สถานะ |
|------|--------|
| `api/.../AdminController.php` | เสร็จแล้ว — logic ครบ แต่มีจุดเสี่ยง |
| `ui/components/settings/AccountInfo.vue` | เสร็จแล้ว — ใช้งานได้ แต่ inconsistent pattern |
| `ui/components/settings/ProfileInfo.vue` | เสร็จแล้ว — ใช้งานได้ แต่ UX skills input ยังหยาบ |
| `ui/pages/nuxnan-admin/users/[id]/edit.vue` | ใช้งานได้ แต่ **ขาดฟิลด์ phone_number** และ pattern token ไม่ตรง |
| `ui/pages/profile/[id]/settings.vue` | เสร็จแล้ว — logic ดี |

---

### ปัญหาที่พบและแผนแก้ไข

#### 🔴 Priority 1 — Bug / ทำให้ข้อมูลหาย

~~**[BE-1] getAllPermissions() crash**~~ — **ยกเลิก** (custom method ปลอดภัย, return `[]` ถ้า schema ยังไม่พร้อม)

**[FE-NEW] `birthday` vs `birthdate` field mismatch**
- ไฟล์: `ui/components/settings/ProfileInfo.vue:56` (bind `p.birthdate`) vs `UserResource:59` (return `profile.birthday`)
- ปัญหา: วันเกิดโหลดมาเป็น `undefined` ทุกครั้ง → input แสดงว่างเสมอ แม้มีข้อมูลใน DB
- แผน: แก้ ProfileInfo.vue บรรทัด 56 จาก `p.birthdate` → `p.birthday`
- ผู้รับผิดชอบ: frontend-vue agent

**[FE-1] Admin edit.vue ไม่มี `phone_number` ในฟอร์ม**
- ไฟล์: `ui/pages/nuxnan-admin/users/[id]/edit.vue`
- ปัญหา: API `/api/admin/users/{id}` return `phone_number` แต่ฟอร์ม edit ไม่มีช่องนี้ → admin ไม่สามารถแก้เบอร์โทรได้
- แผน: เพิ่ม field `phone_number` ใน `form` reactive object และเพิ่ม input ในฟอร์ม (ระหว่าง email กับ password)
- ผู้รับผิดชอบ: frontend-vue agent

#### 🟡 Priority 2 — Inconsistent Pattern / Technical Debt

**[FE-2] AccountInfo.vue & ProfileInfo.vue ใช้ `$fetch` ตรง**
- ไฟล์: `ui/components/settings/AccountInfo.vue`, `ui/components/settings/ProfileInfo.vue`
- ปัญหา: Project convention กำหนดให้ใช้ `useApi` composable หรือ service wrapper ไม่ใช่ `$fetch` ตรง — เวลา token หมดอายุหรือต้องการ intercept จะจัดการลำบาก
- แผน: refactor ให้ใช้ `useApi` (ถ้า composable นี้มีใน `ui/composables/useApi.ts`) หรืออย่างน้อยดึง token จาก `useAuthStore()` แทนการฝัง inline header
- หมายเหตุ: ตรวจสอบ `ui/composables/useApi.ts` ก่อนว่า pattern การเรียกเป็นอย่างไร

**[FE-3] Admin edit.vue ใช้ `useCookie('token')` แทน `useAuthStore()`**
- ไฟล์: `ui/pages/nuxnan-admin/users/[id]/edit.vue:52,109`
- ปัญหา: หน้าอื่นทั้งหมด (รวม AccountInfo.vue) ใช้ `authStore.token` — ใช้คนละ source อาจ stale ถ้า token refresh
- แผน: เปลี่ยนเป็น `useAuthStore().token`

**[FE-4] ProfileInfo.vue: skills input เป็น textarea comma-separated**
- ไฟล์: `ui/components/settings/ProfileInfo.vue:362-369`
- ปัญหา: UX ไม่ดี — ยากจะรู้ว่าต้องคั่นด้วยจุลภาค; ถ้า user พิมพ์ space นำหน้าก็ trim ได้แต่ถ้าเผลอใส่ comma ซ้ำจะ save ค่าว่าง
- แผน (ง่าย): เพิ่ม tag-input เบาๆ ด้วย PrimeVue `Chips` component (`<Chips>`) แทน textarea — bind ตรงเป็น array ไม่ต้อง split/join
- แผน (ทางเลือก): ถ้าไม่อยากเปลี่ยน UI ให้ filter empty string ใน `saveProfile()` (ทำอยู่แล้วด้วย `.filter(s => s.length > 0)`)

#### 🟢 Priority 3 — Missing Features / Nice-to-have

**[BE-2] AdminController ไม่มี Bulk Delete และ Export**
- ปัญหา: ระบบมี `bulkVerify` แล้ว แต่ไม่มี bulk delete หรือ export to Excel ทั้งที่ project ใช้ `maatwebsite/excel`
- แผน: เพิ่ม `bulkDelete` method + route, เพิ่ม `export` method ที่ return Excel download

**[FE-5] settings.vue ไม่มี "Unsaved changes" warning**
- ไฟล์: `ui/pages/profile/[id]/settings.vue`
- ปัญหา: user กรอกข้อมูลแล้วกด tab อื่นหรือกดกลับจะหายทั้งหมด ไม่มีคำเตือน
- แผน: ใช้ `onBeforeRouteLeave` + `beforeunload` event ตรวจ dirty state; หรืออย่างน้อย emit event จาก ProfileInfo/AccountInfo เมื่อ form dirty

**[BE-3] AdminController::show() ไม่มี `profile` data**
- ไฟล์: `api/nuxnanravel/app/Http/Controllers/Api/AdminController.php:268`
- ปัญหา: `with(['roles', 'profile'])` load profile ไว้แต่ไม่ได้ return ข้อมูล profile (first_name, last_name, avatar, bio ฯลฯ) ใน response
- แผน: เพิ่ม profile fields ใน response array ของ `show()` หรือใช้ UserResource ที่ครอบคลุม

---

### ลำดับการทำงานที่แนะนำ

```
Phase 1 (Bug Fix — ทำก่อน):
  1. [FE-NEW] แก้ birthdate → birthday ใน ProfileInfo.vue:56
  2. [FE-1]   เพิ่ม phone_number field ใน admin edit.vue
  3. [FE-3]   เปลี่ยน useCookie('token') → useAuthStore().token ใน admin edit.vue

Phase 2 (Pattern Fix):
  4. [FE-2]  Refactor AccountInfo.vue + ProfileInfo.vue → ใช้ useApi() แทน $fetch ตรง
  5. [BE-3]  เพิ่ม missing fields ใน UserResource: is_plearnd_admin, status, is_banned, last_login_at, username

Phase 3 (Enhancement):
  6. [FE-4]  เปลี่ยน skills textarea → PrimeVue Chips component
  7. [FE-5]  เพิ่ม unsaved changes warning ใน settings.vue
  8. [BE-2]  เพิ่ม bulkDelete + Excel export endpoint
```

---

### ความเสี่ยงที่เหลือ (หลังตอบ 4 คำถามแล้ว)

1. **`courses()` relationship ใน User model** — `AdminController::show():305` เรียก `$user->courses()->count()` — ยังไม่ตรวจว่า relationship นี้มีอยู่จริง (ถ้าไม่มีจะ throw error ตอนเรียก show)
2. **`permission` middleware** — `update` route ใช้ `middleware('permission:user-edit')` แต่ admin ทั่วไปที่ไม่มี permission นี้จะได้ 403 — ต้องตรวจว่า default admin role ถูก assign permission นี้แล้วหรือยัง

---

## Current Snapshot

- Date: 2026-06-04
- Branch: main
- Active Work: เสร็จสิ้นทุก Phase — รอ user สั่งงานใหม่

## Active Work

**✅ เสร็จแล้ว (2026-06-04)**

| ไฟล์ | การเปลี่ยนแปลง |
|------|----------------|
| `ProfileInfo.vue` | useApi, inject markDirty/markClean, skills tag input |
| `AccountInfo.vue` | useApi, inject markDirty/markClean |
| `admin/users/[id]/edit.vue` | เพิ่ม phone_number, ลบ useCookie → useApi |
| `profile/[id]/settings.vue` | provide markDirty/markClean, onBeforeRouteLeave, beforeunload warning |
| `Admin/UserResource.php` | เพิ่ม status, is_banned, last_login_at, login_count, role, is_plearnd_admin, username; แก้ birthday→birthdate |
| `AdminController.php` | เพิ่ม bulkDelete method |
| `routes/admin/admin.php` | เพิ่ม POST /bulk-delete route |

## Coordination Board

(ไม่มี)

## Decisions And Assumptions

- `AGENTS.md` is the tool-agnostic root entry point for all AI agents.
- `.agents/latest-analysis.md` is the live analysis and coordination board.
- `.agents/worklog.md` remains the cross-session handoff log.
- `CLAUDE.md` remains Claude-specific historical/project guidance.
- สมมติว่า `getAllPermissions()` มาจาก Spatie/Permission — ต้องตรวจยืนยัน

## Open Questions

> **ทั้ง 4 ข้อตอบแล้ว — ดูผลด้านล่าง**

---

## ✅ คำตอบ 4 คำถาม (ตรวจแล้ว 2026-06-04)

### Q1: User model ใช้ Spatie `HasRoles` หรือ custom system?
**Custom system ทั้งหมด — ไม่มี Spatie**
- `hasRole()`, `hasAnyRole()`, `isSuperAdmin()` → query ตรงผ่าน `$this->roles()` relationship
- `getAllPermissions()` ที่ `User.php:276` มี `permissionsSchemaReady()` guard — ถ้า table ไม่มีก็ return `[]` ปลอดภัย, return type เป็น `array` ✅
- **สรุป: BE-1 ไม่ใช่ bug — ลบออกจาก Priority 1**

### Q2: `useApi` composable signature เป็นอย่างไร?
**Production-grade composable — ควรใช้แทน `$fetch` ทุกที่ใน project**

```ts
const api = useApi()
await api.get('/api/endpoint')
await api.post('/api/endpoint', body)
await api.put('/api/endpoint', body)
await api.patch('/api/endpoint', body)
await api.delete('/api/endpoint')
await api.getBlob('/api/export/file')   // → { blob, filename }
```

Features ที่ได้ฟรีทันทีที่ย้ายมาใช้:
- Auto-inject `Authorization: Bearer ${authStore.token}` — ไม่ต้องส่ง header เอง
- Auto-refresh token เมื่อได้ 401 → retry อัตโนมัติ
- Retry 3x exponential backoff สำหรับ GET บน 5xx/timeout
- FormData detection — ไม่ set Content-Type ให้ browser จัดการ boundary เอง
- Throw `ApiError` ที่มี `{ id, status, type, data, message }` — จับ error ได้ชัดกว่า

### Q3: UserResource return fields อะไร? ครอบคลุมไหม?
**พบ mismatch 1 จุดและ missing fields หลายตัว**

🔴 **Field mismatch — bug ใหม่ที่พบ**:
| UserResource | ProfileInfo.vue | ผลกระทบ |
|---|---|---|
| `profile.birthday` | `profileForm.birthdate` | วันเกิดไม่ถูก bind ตอน load → แสดงว่างเสมอ |

❌ **Missing จาก UserResource** (ต้องเพิ่ม):
- `is_plearnd_admin` — AdminController `show()` return inline แต่ `update()` ใช้ UserResource → หลัง save ไม่มีค่านี้
- `status` (verified/unverified string), `is_banned`, `last_login_at`, `username`

✅ **มีอยู่แล้ว**: `phone_number`, `personal_code`, `reference_code`, `avatar`, `roles`, `is_super_admin`, `is_admin`, `profile` (bio, birthday, gender, address, city, country, website), `pp`, `wallet`, `level`, timestamps

### Q4: Route registration ถูกต้องไหม?
**ถูกต้องทั้งหมด ✅**
- Prefix `/api/admin` กำหนดใน `bootstrap/app.php:20`
- `routes/admin/admin.php` → `Route::prefix('users')` → full path `/api/admin/users/{id}`
- ตรงกับที่ frontend เรียก: `${apiBase}/api/admin/users/${userId}` ✅
- PUT route มี `middleware('permission:user-edit')` ครบ

---

## Analysis Timeline

### 2026-06-04 - Codex profile settings visual card removal
- Scope: `ui/components/settings/ProfileInfo.vue`.
- Change: removed the duplicate Visuals Card from profile settings and cleaned up the now-unused avatar/cover upload state and handlers in that settings component. The main `profile/[id].vue` Profile Header Card remains responsible for avatar/cover visuals.
- Verification: `rg` confirmed no `Visuals Card`, avatar/cover preview refs, or upload handlers remain in `ProfileInfo.vue`; `git diff --check -- ui/components/settings/ProfileInfo.vue` passed. `cmd /c npx vue-tsc --noEmit --pretty false` still fails on broad pre-existing project TypeScript errors and `vue-router/volar/sfc-route-blocks`, with no new reported error in `components/settings/ProfileInfo.vue`. In-app browser smoke test reached `/auth` because the browser session was not logged in, so authenticated visual confirmation was blocked.

### 2026-06-04 - Codex profile settings inner tabs
- Scope: `ui/components/settings/ProfileInfo.vue`.
- Change: converted the three profile form sections (`ข้อมูลตัวตน`, `ข้อมูลติดต่อ`, `ข้อมูลอาชีพ`) into an in-card tab switcher with icons, preserving the existing single save action and dirty tracking.
- Verification: `rg` confirmed `activeProfileTab` guards for all three sections and no `Visuals Card` returned; `git diff --check -- ui/components/settings/ProfileInfo.vue` passed. `cmd /c npx vue-tsc --noEmit --pretty false 2>&1 | findstr /C:"ProfileInfo.vue"` produced no `ProfileInfo.vue` errors; broad project typecheck is still known to fail on unrelated existing errors.

### 2026-06-04 - Codex profile settings tab header polish
- Scope: `ui/components/settings/ProfileInfo.vue`.
- Change: moved the inner tab header outside the form content padding so it sits flush against the top/left/right edges of the card, removed horizontal padding from tab buttons, and kept content padding only below the tab header.
- Verification: `git diff --check -- ui/components/settings/ProfileInfo.vue` passed; `cmd /c npx vue-tsc --noEmit --pretty false 2>&1 | findstr /C:"ProfileInfo.vue"` returned no `ProfileInfo.vue` errors.

### 2026-06-04 — Reset
- ผู้ใช้ขอเคลียร์ไฟล์ทั้งหมดเพื่อเริ่มต้นใหม่

### 2026-06-04 — Full Analysis of Pending Changes
- อ่านไฟล์ที่ค้างอยู่ทั้ง 5 ไฟล์: AdminController.php, AccountInfo.vue, ProfileInfo.vue, admin edit.vue, profile settings.vue
- พบ bug สำคัญ 2 จุด ([BE-1] getAllPermissions, [FE-1] missing phone_number)
- พบ pattern inconsistency 3 จุด ([FE-2] $fetch direct, [FE-3] useCookie, [FE-4] skills UX)
- พบ missing features 3 จุด ([BE-2] bulk delete/export, [FE-5] unsaved warning, [BE-3] profile data)
- วางแผน 3 phases พร้อม 4 Open Questions ที่ต้องตรวจก่อนแก้
### 2026-06-04 - Codex profile/settings error fix completed
- Implemented: `personal_code` support in shared profile identifier resolution; settings profile now normalizes legacy `birthday` to `birthdate`; `ProfileInfo.vue` now submits `birthdate`; account settings can update `phone_number` without requiring `name`.
- Added and ran migration `2026_06_04_000001_repair_user_profile_settings_columns.php`; local `user_profiles` now has 39 columns including `address`, professional fields, and privacy flags.
- Verification: PHP lint passed for touched backend files; Pint ran on touched backend files; `php artisan migrate` succeeded; `php artisan db:table user_profiles` confirmed repaired columns; `git diff --check` passed. `vue-tsc` still fails on broad pre-existing project errors unrelated to this patch.

### 2026-06-04 - Codex profile/settings error fix
- Scope claimed: `UserProfileController`, `SettingsController`, `ProfileInfo.vue`, and one repair migration for `user_profiles`.
- Findings: `/api/users/{identifier}/activities` does not resolve `personal_code`; `/api/settings/profile` 500 is caused by DB schema drift where `2026_01_15_000000_add_profile_fields_to_user_profiles_table` is marked ran but columns like `address` are absent.
- Plan: add shared identifier resolver, align frontend/backend on `birthdate`, make account update compatible with phone-only payload, add idempotent schema repair migration, then run focused PHP lint/schema/route checks.
