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

**หัวข้อ:** Safe User Deletion — nuxnan-admin `/nuxnan-admin/users`

**วันที่วิเคราะห์:** 2026-06-02

**สถานะปัจจุบัน (verified จาก code จริง):**
- `AdminController::destroy()` ทำแค่ `$user->delete()` ตรงๆ ไม่มี transaction, audit, impact check
- `User` model ไม่มี `SoftDeletes`, `users` table ไม่มี `deleted_at`
- มีพฤติกรรม 3 แบบปนกัน:
  1. **Orphan** — ตารางเก่าไม่มี FK: `courses`, `academies`, `lessons`, `posts`, `course_members`, `assignment_answers`, `course_quiz_results`
  2. **Cascade DELETE ถาวร** — `wallet_transactions`, `points_transactions`, `course_purchases (buyer+seller)`, `follows`, `user_achievements`, `grade_edit_logs`, `course_certificates`, `coupon_redemptions`, `daily_point_limits`, `point_streaks`
  3. **FK RESTRICT → 500** — `school_attendances.created_by`, `library_loans.handled_by`, `asset_management.requested_by`, `course_permissions.granted_by` (ไม่มี onDelete → MySQL default RESTRICT; ถ้า user เคยทำ action เหล่านี้ `$user->delete()` จะ throw QueryException)
- Frontend `index.vue` ใช้ `$fetch` ตรง, catch แค่ `console.error`, ไม่มี impact preview, ไม่มี error toast

---

## Work Plan — Safe User Deletion

### Phase 1: Policy (ตกลง convention ก่อน implement)
- ปุ่ม "ลบ" ใน admin = **soft delete/deactivate เท่านั้น** ไม่ใช่ hard delete
- block ลบตัวเอง และ `SUPER_ADMIN` เหมือนเดิม
- **BLOCKER** (ต้องจัดการก่อนลบ):
  - เป็น **sole** owner/admin ของ academy ที่มี active members (ถ้ามี co-admin คนอื่น → ไม่ block)
  - เป็น **sole** owner ของ course ที่มี active members
  - มี `wallet_deposit_requests` ที่ยัง pending (เงินยังไม่ settle)
  - FK RESTRICT ที่ไม่ nullable: `school_attendances.created_by`, `library_loans.handled_by`, `asset_management.requested_by`, `course_permissions.granted_by`
- **WARNING** (แจ้งแต่ดำเนินการต่อได้ พร้อม checkbox ยืนยัน):
  - `course_purchases`, `wallet_transactions`, `points_transactions` จะถูก cascade delete ถาวร
  - `posts`, `lessons`, `courses`, `academies` ที่ user สร้างจะ orphan (ยังอยู่ใน DB แต่ไม่มีเจ้าของ)
- **Anonymize พร้อม soft delete เสมอ** (ไม่แยก step) เพราะ email unique constraint — ต้อง hash email ทันที
- Restore คืนได้เฉพาะ user row + orphan records; cascade-deleted records (wallet/points/purchase history) **ไม่กลับมา** — ต้องแจ้ง admin ให้ชัด
- บันทึก `deleted_by`, `deletion_reason`, `user_snapshot`, `impact_snapshot` ทุกครั้ง

### Phase 2: Database Migration
```
Migration 1: add_soft_delete_to_users_table
  - $table->softDeletes()                    // deleted_at
  - $table->unsignedBigInteger('deleted_by')->nullable()
  - $table->string('deletion_reason')->nullable()
  - $table->timestamp('anonymized_at')->nullable()
  - $table->index('deleted_at')              // performance

Migration 2: create_admin_user_deletion_audits_table
  - id
  - deleted_user_id (nullable — เผื่อ hard delete ในอนาคต)
  - deleted_by (FK users.id, set null on delete)
  - mode: enum('soft_delete', 'restore', 'force_delete')
  - reason: text
  - user_snapshot: json           // email, name, roles ณ เวลาลบ
  - impact_snapshot: json         // counts ที่ affected
  - timestamps
```
**หมายเหตุ:** ยังไม่แก้ FK ทั้งระบบ — audit แยก migration ทีหลัง

### Phase 3: Backend Services
**`app/Services/Admin/UserDeletionImpactService.php`**
```
getImpact(User $user): array
  returns:
    blockers: [
      { type: 'sole_academy_owner', count, items: [{id, name}] },
      { type: 'sole_course_owner', count, items: [{id, name}] },
      { type: 'pending_deposit', count },
      { type: 'fk_restrict', tables: ['school_attendances', ...] },
    ]
    warnings: [
      { type: 'cascade_delete', tables: { wallet_transactions: N, course_purchases: N, ... } },
      { type: 'orphan', tables: { courses: N, posts: N, ... } },
    ]
    can_delete: bool   // true เมื่อ blockers ว่างเปล่า
```
- SOLE owner check: นับ admin ของ academy/course — block เฉพาะเมื่อ user เป็น admin คนเดียว
- FK RESTRICT detection: query EXISTS ก่อนลบ ไม่รอให้ DB throw exception

**`app/Services/Admin/UserDeletionService.php`**
```
softDelete(User $user, Admin $by, string $reason): void
  1. DB::transaction() + lockForUpdate()
  2. validate blockers ผ่าน ImpactService (throw หากยังมี blocker)
  3. anonymize: email → "deleted_{id}@nuxnan.del", name → "Deleted User", phone/avatar/bio → null
  4. $user->delete() (soft)
  5. $user->update([deleted_by, deletion_reason, anonymized_at])
  6. JWTAuth::setToken($user->currentToken())->invalidate() — หรือ blacklist token ทั้งหมดของ user
  7. บันทึก AdminUserDeletionAudit (snapshot ก่อนลบ)

restore(User $user, Admin $by): void
  1. $user->restore()
  2. เคลียร์ deleted_by / deletion_reason / anonymized_at (ต้องแจ้ง admin ว่า email ถูก anonymize ไปแล้ว)
  3. บันทึก audit mode='restore'
```

### Phase 4: API Contract
```
GET  /api/admin/users/{id}/delete-impact   → ImpactService::getImpact()
DELETE /api/admin/users/{id}               → UserDeletionService::softDelete()
  body: { reason: string (required) }
  response 422: { blockers: [...] } ถ้ายังมี blocker
  response 200: { message, impact_summary }
POST /api/admin/users/{id}/restore         → UserDeletionService::restore()
```
- Route ใหม่เพิ่มใน `routes/admin/admin.php` ใต้ middleware `auth:api`, `admin`, `permission:user-delete`
- `destroy()` เปลี่ยนเป็นเรียก `UserDeletionService` ไม่เรียก `$user->delete()` ตรงๆ อีกต่อไป

### Phase 5: Model / Query
- `User.php`: เพิ่ม `use SoftDeletes`, cast `deleted_at`
- ตรวจ `auth:api` JWT middleware — SoftDeletes global scope ทำให้ `User::find()` คืน null สำหรับ soft-deleted user อัตโนมัติ → JWT auth จะ reject token โดยอัตโนมัติ ✅
- Admin list query: เพิ่ม filter `scope=active|deleted|all`; default = `active` (withoutTrashed)
- ระวัง relation queries อื่นๆ ใน codebase ที่อาจต้องการ `withTrashed()` — audit เป็น TODO แยก

### Phase 6: Frontend (`ui/pages/nuxnan-admin/users/index.vue`)
1. เรียก `GET /delete-impact` เมื่อ admin คลิกปุ่มลบ (loading spinner ระหว่างรอ)
2. Modal แสดงผลกระทบแบ่งเป็น 2 ส่วน:
   - 🔴 **Blocker section**: ถ้ามี → ปุ่ม "ยืนยัน" disabled, แสดง action ที่ต้องทำ
   - 🟡 **Warning section**: ถ้ามี → checkbox "รับทราบว่าข้อมูลต่อไปนี้จะหายถาวร" ก่อน enable ปุ่ม
3. Textarea `reason` (required, min 10 chars)
4. ใช้ `useApi` composable แทน `$fetch` ตรงๆ
5. Error toast แทน `console.error` (ครอบ network error + 422 blocker + 500)
6. หลังลบสำเร็จ: refresh list, แสดง badge "ปิดใช้งาน" บน row นั้น
7. เพิ่ม filter tab: Active | ถูกปิดใช้งาน | ทั้งหมด

### Phase 7: Tests (PHPUnit Feature Tests)
```
AdminUserDeletionTest.php ครอบ:
1. ลบ user ปกติ → deleted_at set, login ล้มเหลว, audit created
2. ห้ามลบตัวเอง → 403
3. ห้ามลบ super admin → 403
4. user เป็น sole owner academy → 422 + blocker message
5. user เป็น co-admin academy → ผ่าน (ไม่ block)
6. user มี pending deposit → 422 + blocker
7. user มี school_attendances.created_by → impact service detect FK RESTRICT → 422 ก่อน delete
8. wallet/points/purchase ถูก cascade delete หลังลบ → ยืนยัน behavior (documented test)
9. orphan courses ยังอยู่หลังลบ → documented test
10. restore → deleted_at null, soft-deleted user กลับมา
11. reason required → validation error
```

### ลำดับทำจริง
```
Step 1  Migration soft delete + audit table
Step 2  User model: SoftDeletes + casts
Step 3  UserDeletionImpactService + GET /delete-impact endpoint
Step 4  UserDeletionService (soft delete + anonymize email + JWT invalidate)
        → ปรับ AdminController::destroy() และเพิ่ม restore()
Step 5  Frontend: impact modal, blocker/warning UX, reason, error toast, filter tabs
Step 6  PHPUnit: AdminUserDeletionTest (11 scenarios)
```

### ข้อจำกัดที่รับรู้ (Known Limitations)
- **Restore ไม่คืน cascade-deleted records** (wallet/points/purchase history) — by design, ต้องแจ้ง admin ใน UI
- **Email ถูก anonymize ไม่กลับมา** หลัง restore — admin ต้อง set email ใหม่เองถ้าจำเป็น
- **FK RESTRICT tables** (`school_attendances`, `library_loans`, `asset_management`, `course_permissions`) — ตราบใดที่ยังมี records อยู่ จะ block การลบ; แก้ที่ root ต้องทำ migration เพิ่ม `onDelete('set null')` แยก (TODO future)
- **Hard delete/purge** ยังไม่ implement ในรอบนี้ — เป็น admin-only job สำหรับอนาคต

---

**หัวข้อเก่า (archived):** Universal QR Scanner สำหรับระบบเช็คชื่อมาโรงเรียน (School Attendance Anti-Fraud)

**หลักการ:** หนึ่งปุ่มสแกน สองทางเช็คชื่อ
- นักเรียนสแกน QR จากครู (session QR ที่ครูแสดง)
- ครูสแกน QR จากบัตรนักเรียน (student card QR)

**QR Format ที่เสนอ:**
- Session QR: `CHECKIN:SCHOOL:{academy_id}:{attendance_id}:{token}`
- Student Card QR: `STUDENT:{academy_id}:{student_code}` (ยืดหยุ่นกว่าเพื่ออนาคต)

**จุดที่ต้องปรับ:**
- `ui/types/qr.ts` — เพิ่ม type ใหม่
- `ui/composables/useQRScanner.ts` — แยก handler SCHOOL vs STUDENT
- `SchoolAttendanceController.php` — endpoint Universal QR
- ลบช่องโหว่ token-in-URL ออกจาก `SchoolAttendanceWidget.vue`
- Token ควร rotate/expire ทุก 30-60 วินาที

---

## Current Snapshot (2026-06-02)
- **Active Work**: Safe User Deletion — Plan finalized, ready to implement
- **Pending Tasks**: Step 1–6 per Work Plan above
- **Next Steps**: เริ่มที่ Step 1 (migration) → Step 3 (ImpactService) → Step 4 (DeletionService)

---

## Universal QR Scanner Implementation (Completed 2026-05-31)
- **Phase 0-1 (Backend)**: Added `qr_token_expires_at` (migration), implemented token rotation (60s TTL), and added `refresh-qr` endpoint. Updated `checkIn()` and `scanStudent()` with validation and audit metadata.
- **Phase 2-3 (Frontend)**: Updated QR type system in `ui/types/qr.ts` and added handlers for `school_checkin` and `student_card` in `ui/composables/useQRScanner.ts`.
- **Phase 4 (QR Display)**: Created `SchoolAttendanceQRDisplay.vue` with auto-rotation (55s) and countdown. Integrated into admin session page.
- **Phase 5 (Security Fix)**: Removed token-in-URL from `SchoolAttendanceWidget.vue` and switched to Universal QR Scanner trigger.
- **Phase 6 (Student Card)**: Added `qr_content` to `StudentCard` model and updated `StudentCardFront.vue` to show universal QR (`STUDENT:{aid}:{code}`).
- **Phase 7 (Session Choice)**: Implemented session chooser UI in `UniversalQRModal.vue` for cases where a teacher scans a student card during multiple open sessions.

---

## Technical Details (Completed)
1. **Model**: `SchoolAttendance.php` tracks expiry; `StudentCard.php` generates universal content.
2. **Scanner**: `useQRScanner.ts` handles role-based logic (Student scans Teacher QR = Check-in; Teacher scans Student Card = Scan-student).
3. **Modal**: `UniversalQRModal.vue` now supports the session selection flow.
4. **Audit**: `scan_method` is now recorded as `qr` or `manual` in the database.

---

#### สรุป Priority Queue (เรียงตามความสำคัญ)

| Priority | Phase | งาน | ไฟล์หลัก | เหตุผล |
|---|---|---|---|---|
| 🔴 Critical | 5 | ลบ token-in-URL | `SchoolAttendanceWidget.vue` | ช่องโหว่ที่กำลังใช้งานอยู่ |
| 🔴 Critical | 0+1 | Token expiry | migration + `SchoolAttendance.php` + Controller | foundation ของทุก phase |
| 🟠 High | 2+3 | QR type + handlers | `qr.ts` + `useQRScanner.ts` | core feature |
| 🟠 High | 4 | Teacher QR Display | `SchoolAttendanceQRDisplay.vue` | ครูต้องการหน้าแสดง QR |
| 🟡 Medium | 6 | Student card QR | `StudentCardController.php` + card print | ให้บัตรใช้กับระบบได้ |
| 🟡 Medium | 7 | Session chooser | `UniversalQRModal.vue` | UX เมื่อมีหลาย session |

#### ข้อควรระวังในการ implement

1. **`parseQRCode()` collision** — prefix `CHECKIN` ถูกใช้ทั้ง course check-in (`CHECKIN:class_123:sess_1`) และ school check-in (`CHECKIN:SCHOOL:1:5:token`) ต้องตรวจ segment ที่ 2 ว่าเป็น `SCHOOL` หรือเป็นตัวเลข/string ปกติ ไม่งั้น regex อาจ mis-route
2. **Role detection ใน `handleStudentCardQR()`** — `authStore.user?.academyRole` อาจไม่มีในทุก context ต้องหา pattern ที่ถูกต้องจาก `useAcademyRole` composable
3. **`points idempotency`** — `PointsService::awardByRule()` ถูกเรียกทั้งใน `checkIn()` และ `scanStudent()` ต้องตรวจว่า rule engine handle duplicate ได้แล้วหรือยัง (ป้องกัน double points เมื่อ teacher override student self check-in)
4. **QR library** — ยังไม่มี QR generation library ใน frontend (`ui/package.json`) ต้อง install `qrcode` หรือ `vue-qrcode-reader` ก่อน Phase 4

### 2026-05-31 - SMS Plan Refined — Detailed Implementation Tasks - COMPLETED

**Context:** User requested detailed, actionable task breakdown for School Management System plan so they or other AI agents can implement each phase.

- Ran full route audit: `php artisan route:list --path=academies` vs `useSchoolManagement.ts`
- Found **12 confirmed route drifts** (meeting-slots, fee-structures, expense-categories, method mismatches)
- Found **large missing sections** in composable: emergency alerts (entire section), meeting booking management, event registration extras, parent portal endpoints, finance/budget/payroll extras
- Confirmed student/teacher dashboards are shallow with TODO placeholders and dead links
- **Updated `.agents/school-management-system-plan.md`** with:
  - Complete route drift table (Section 2A)
  - Missing endpoint inventory (Section 2B)
  - Detailed per-Phase task lists with specific files, acceptance criteria, estimated effort
  - 10-item priority queue in Section 9
  - Updated data principles, risks, and DoD table

### 2026-05-31 - School Management System master plan - COMPLETED

**Context:** User asked to research, analyze, and plan the next School Management System layer beyond course/LMS management, while preserving the Play Learn Earn concept and recording the plan centrally for other AI agents.

- Completed read-only repo inspection of existing academy/school modules, routes, controllers, migrations, frontend pages, and `useSchoolManagement.ts`.
- Created shared planning baseline: `.agents/school-management-system-plan.md`.
- Main decision: treat SMS as a digital school campus around the academy feed/flagpole yard, with classroom, office, teacher room, finance room, parent portal, store, and director dashboard connected by shared identity/permissions and activity events.
- Key implementation caution: stabilize existing route/API contracts first because school frontend components and composable already show contract drift and placeholder logic.

### 2026-05-30 — Commit pending changes (3 logical commits) — COMPLETED

**Context:** User Analysis Input ว่าง แต่มี uncommitted changes 18 ไฟล์จาก 3 งานที่เสร็จแล้วใน session ก่อน

- ✅ Commit 1 — User Profile Fixes (Phases 1–7)
- ✅ Commit 2 — Dashboard Leaderboard NaN Fix
- ✅ Commit 3 — Sidebar Widget Timeout Fix

---

## Current Snapshot

- Date: 2026-06-03
- Branch: main
- Repository: `C:\wamp64\www\nuxnan`
- Frontend: `ui/` Nuxt/Vue/TypeScript/Pinia/Tailwind/PrimeVue
- Backend: `api/nuxnanravel/` Laravel/PHP/JWT/MySQL/Reverb
- Current SMS planning note: All phases (0-6) are complete. The "Digital Campus" is fully realized with Academic, Finance, Staff, Communication, Economy, Reports, Library, and Asset management systems.
- Current focus: School Department Management TASK-001..TASK-006 completed; ready for Claude/user review.
- Pending commit: Department Management task fixes plus agent log updates.

## Active Work

| Scope | Owner | Status | Files | Notes |
| --- | --- | --- | --- | --- |
| Safe User Deletion | AI | plan_ready | see Work Plan above | 6 steps, plan confirmed by user |
| School Department Management | Codex | completed | `DepartmentController.php`, `academy.php`, `departments.vue`, `.agents/codex-tasks.md` | TASK-001..TASK-006 done; focused backend checks passed; broader test/typecheck blocked by existing environment/dependency issues |

## Coordination Board

| Claim ID | Owner | Scope | Files or folders | Status | Handoff note |
| --- | --- | --- | --- | --- | --- |
| 2026-06-03-departments-codex | Codex | Department Management TASK-001..TASK-006 | `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/DepartmentController.php`, `api/nuxnanravel/routes/learn/academy.php`, `ui/pages/academies/[name]/admin/departments.vue`, `.agents/codex-tasks.md` | completed | Fixed backend stats/head user and aligned frontend calls with Laravel response shapes and academy-scoped routes. |

## Decisions And Assumptions

- `AGENTS.md` is the tool-agnostic root entry point for all AI agents.
- `.agents/latest-analysis.md` is the live analysis and coordination board.
- `.agents/worklog.md` remains the cross-session handoff log.
- `CLAUDE.md` remains Claude-specific historical/project guidance.
- School Management System should be developed as a unified academy-level campus system, not a separate disconnected ERP.
- The academy feed is the central school courtyard/flagpole yard and should receive meaningful events from SMS modules.
- Stabilize existing SMS API contracts before adding major new modules.
- **New Decision**: Focus on Phase 0 (Route Stabilization) and Phase 1 (Dashboards) first to provide immediate user value.

## Open Questions

- Which campus modules should be implemented first after Phase 0: Student Today/Homeroom, Attendance, Finance, or Feed integration?
- Should future parent notifications target in-app only first, or include external channels such as email/SMS/Line?

(ไม่มี)

## Analysis Timeline

### 2026-06-03 - Department Management Codex task implementation started
- User asked Codex to start work from `.agents/codex-instructions.md`; `.agents/codex-tasks.md` has TASK-001..TASK-006 pending for School Department Management.
- Read `DepartmentController`, department routes, `departments.vue`, and `useApi.ts`; confirmed `useApi` already has `patch()`.
- Route finding: list/create/statistics are `api/academies/{academy}/departments...`; detail/member/permission routes currently list as `api/academies/departments/{department}...`, while task contract expects academy-scoped URLs. Permission controller already expects `Academy $academy`, so route scoping needs to be corrected with controller signatures/checks.
- Intended edits: add `head_user` and `departments_with_head`, align academy-scoped department routes, fix frontend `/api` prefixes, PATCH methods, response `.data` reads, and member role/remove request bodies.

### 2026-06-03 - Department Management Codex tasks completed
- Completed TASK-001..TASK-006 in `.agents/codex-tasks.md`.
- Backend: `DepartmentController::index()` now returns `head_user_id` and `head_user` from bulk-loaded users; `getStatistics()` returns `departments_with_head`; department detail/member mutations now validate the department belongs to the requested academy.
- Routes: department detail/member/permission endpoints are academy-scoped as `/api/academies/{academy}/departments/{department}...`; `route:list --path=departments` confirmed the expected methods.
- Frontend: `departments.vue` now reads `response.data`, uses `api.patch()` for department update/member role, includes `/api` prefixes, sends `user_id` in remove/role bodies, and normalizes flat department member payloads for the existing template.
- Verification: `php -l DepartmentController.php`, `php artisan route:list --path=departments`, backend Pint on touched PHP files, and `git diff --check` passed. `php artisan test ...test_can_list_departments` is blocked by missing sqlite `users` table; `cmd /c npx vue-tsc --noEmit --pretty false` timed out and reported `vue-router/volar/sfc-route-blocks` package export failure.

### 2026-05-31 - School attendance anti-fraud planning
- User requested a plan to close the loophole where students can self check-in from outside school through the flagpole/school attendance button.
- Read-only inspection found the current session-based school attendance flow in `SchoolAttendanceController`, `school_attendances`, `school_attendance_records`, `SchoolAttendanceWidget.vue`, student `attendance/check-in.vue`, and admin `school-attendance/[id].vue`.
- Key finding: the student widget passes `qr_token`, `aid`, and `sid` directly to the student check-in page, so the QR/token is effectively a reusable bearer secret for any logged-in student who obtains it. Existing teacher-side `scan-student` is a better foundation, but records only `manual` and lacks detailed method/audit metadata.
- Recommended direction: make teacher-controlled check-in the default (teacher scans student card QR/barcode or types student code), keep student QR self-check-in disabled or restricted by rotating short-lived token plus optional geofence/Wi-Fi proof, and expand record metadata for method, device/location, override reason, and verifier.
- Verification plan for implementation: feature tests for invalid/expired QR, teacher scan success, duplicate scan, unauthorized scanner, audit metadata, and points idempotency; frontend smoke test for admin scan/manual tabs and student widget behavior.

### 2026-05-31 - Universal QR Scanner Plan — Enhanced & Verified Against Codebase

**ตรวจสอบ code จริงพบ:**

**สิ่งที่มีอยู่แล้ว (ใช้ได้เลย):**
- `UniversalQRModal.vue` + `useQRScanner.ts` + `qr.ts` — infrastructure ครบ พร้อมขยาย
- `SchoolAttendanceController.php` — มี `checkIn()` (student scan) + `scanStudent()` (teacher scan) แล้ว
- Routes: `/{attendance}/check-in` และ `/{attendance}/scan-student` มีอยู่แล้ว
- `SchoolAttendance::generateQrToken()` — ใช้ `Str::random(32)` แต่ไม่มี expiry

**ช่องโหว่ยืนยัน (Critical):**
- `SchoolAttendanceWidget.vue:68-78` — `goCheckIn()` ส่ง `qr_token` + `aid` + `sid` เป็น URL query params
  ใครก็ตามที่เห็น URL หรือถูก share link จะ check-in ได้จากทุกที่
- `useQRScanner.ts:248` — `handleCheckinQR()` ปัจจุบัน route ไปที่ `/api/classes/checkin` (course check-in)
  ยังไม่รองรับ school attendance เลย

**ช่องโหว่เพิ่มเติมที่แผนเดิมพลาด:**
- `scanStudent()` ใน Controller ใช้ `check_in_method = 'manual'` เสมอ แม้จะมาจากการสแกน QR บัตร
  → ข้อมูล audit trail ผิด ต้องแยก `'qr'` vs `'manual'`
- ไม่มี `qr_token_expires_at` ใน `school_attendances` table → token ถูกสร้างครั้งเดียวใช้ได้ตลอด session

**สิ่งที่แผนเดิมถูกต้อง:**
- QR format `CHECKIN:SCHOOL:{academy_id}:{attendance_id}:{token}` — ดี เพราะ scoped
- QR format `STUDENT:{academy_id}:{student_code}` — ดี เพราะยืดหยุ่นสำหรับอนาคต
- Universal QR router logic ถูกต้อง — ตรวจ prefix แล้วแยก handler

**สิ่งที่แผนเดิมยังขาด:**
1. Token rotation mechanism (ไม่มี migration, ไม่มี refresh endpoint)
2. Teacher QR Display Component ที่มี countdown/auto-refresh
3. Role-based routing ใน frontend handler (นักเรียนสแกน STUDENT QR = ดูโปรไฟล์, ครูสแกน = เช็คชื่อ)
4. Multi-session chooser เมื่อมีหลาย session เปิดพร้อมกัน
5. `check_in_method` audit fix ใน `scanStudent()`
6. Error message ที่เป็นมิตรในกรณีต่างๆ (token หมดอายุ, session ปิด, เช็คชื่อซ้ำ)

### 2026-05-31 - School attendance universal QR scanner refinement
- User clarified the core principle: attendance must be as easy as possible, using the existing Universal QR Scanner. Daily attendance QR generated by a session must identify the attendance session, and both flows should be supported: student scans teacher/session QR, and teacher scans QR on the student card.
- Read-only inspection confirmed `ui/types/qr.ts` already has `CHECKIN` support and `UniversalQRModal.vue` routes all QR actions through `useQRScanner.ts`; current `handleCheckinQR()` targets `/api/classes/checkin`, so school attendance should extend the universal QR action router rather than introduce another scanner.
- Recommended QR payload contract: keep `CHECKIN` but add a scoped first segment, e.g. `CHECKIN:SCHOOL:{academy_id}:{attendance_id}:{token}` for teacher/session QR and `STUDENT:{academy_id}:{student_identifier}` or `CHECKIN:STUDENT:{academy_id}:{student_identifier}` for student card QR, then route based on segment shape.
- UX decision: one scanner button for everyone. Student scanning a school attendance QR calls student check-in; teacher/admin scanning a student card while an attendance session is open records the student. Ambiguous cases should show a minimal chooser for the active session, not a separate page.
- Risk: student self-scan remains dependent on physically seeing the teacher's QR. To reduce sharing risk without hurting usability, rotate/expire session QR tokens and avoid exposing the token in student widgets or URLs before the scan.

### 2026-05-31 - Academy child-route and classroom students API hotfix
- User pasted browser logs showing `ui/pages/academies/[name].vue` transition warnings because the page rendered a non-element/multiple-root route node, plus repeated 500s from `GET /api/academies/{academy}/classrooms/students`.
- Finding: `ClassroomController::getAllStudents()` ordered by `students.student_number`, but the number belongs to the `classroom_students` pivot. Frontend attendance also assumed the endpoint was always a plain array.
- Changed `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/ClassroomController.php` to order by active pivot student number through subselects, expose `student_number`/`classroom_id` on each item, and return array-shaped `students`/`data` plus separate pagination metadata.
- Changed `ui/pages/academies/[name]/admin/school-attendance/[id].vue` to unwrap either array or paginated response shapes and submit `user_id` for attendance records when available.
- Changed `ui/pages/academies/[name].vue` to wrap route/main content in a single root element for Nuxt route transitions.
- Verification: `php -l api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/ClassroomController.php` passed; `php artisan route:list --path=classrooms/students` confirmed `GET api/academies/{academy}/classrooms/students`; targeted Pint ran for `ClassroomController.php`.

### 2026-05-31 - SMS Plan Refined to Version 2.0
- Re-analyzed codebase and plan. Verified route drifts in `useSchoolManagement.ts` against actual Laravel routes.
- Identified that most communication, finance, and staff tables already exist in the DB, but `classroom_attendance` is a key gap.
- Refined the plan to include:
  - **Play Learn Earn Hooks**: Specific integration points (e.g., points for attendance).
  - **Database Schema**: Proposed migration for `classroom_attendances`.
  - **Agent Task Queue (T01-T07)**: Discrete, actionable tasks for implementation.
  - **Dashboard Focus**: Prioritizing student/teacher/parent dashboards as the system's "Heart".
- No app code changed yet. Plan is ready for the next agent or implementation turn.

### 2026-05-31 - School Management System master plan recorded
- User requested detailed planning for School Management System / school-life features beyond Course/LMS management, preserving Play Learn Earn.
- External research baseline: common SMS/SIS systems center on student records, attendance, timetable, gradebook/transcripts, communication, fees, staff, parent portals, and reporting; ERP-style products add payroll, transport, library/assets, inventory, alerts, analytics, and unified permissions.
- Repo finding: nuxnan already has broad academy SMS foundations: school admin page, school composable/tabs, academy feed, members/roles, students/guardians/cards/home visits, classrooms, schedules, gradebook/transcripts, finance, staff/leave/payroll, announcements/events/emergency/messages/meetings, parent portal, store/wallet/points, reports/analytics.
- Risk finding: several surfaces are incomplete or contract-drifted, especially `useSchoolManagement.ts` route mapping vs Laravel routes, `SchoolReportsTab.vue` placeholder analytics/actions, report generation/export TODOs, emergency/meeting notification TODOs.
- Plan recorded in `.agents/school-management-system-plan.md` with role journeys, core systems, Play Learn Earn hooks, phased roadmap, data principles, immediate task queue, risks, and module definition of done.
- Recommended next step: Phase 0 route/API contract audit and typed frontend service cleanup before implementing new school modules.

### 2026-05-30 - Course member identity duplication analysis
- User asked for a sustainable plan to avoid requiring students to update name, student/member code, and order number separately for every course enrollment.
- Finding: course membership identity fields currently live on `course_members` (`member_name`, `member_code`, `order_number`) and are editable through `CourseMemberController::update`, `updateOrderNumber`, `updateMemberCode`, `updateOwnProfile`, plus UI surfaces such as `MyProgressDetails.vue`, `ProgressList.vue`, member edit pages, groups, attendance, gradebook, and progress views.
- Related source-of-truth candidates already exist: `students.student_id`, `academy_members.member_code`/`student_id`, and `classroom_students.student_number`; courses also have nullable `academy_id`, so an academy/course-aware learner profile is safer than copying values into every course member record.
- Recommended direction: introduce a centralized learner profile/resolution layer, keep course-specific overrides only when genuinely needed, backfill from existing `course_members`, then update resources/UI to read effective values from the centralized source while preserving backward compatibility.
- Verification plan for implementation: migration/backfill tests, course enrollment/update feature tests, resource contract checks for members/progress/groups/attendance, and focused Nuxt smoke tests for course join, my progress, member list, and admin progress editing.

### 2026-05-30 - Course progress includes admin/owner analysis
- User reported `/Learn/Courses/5/progress` shows course owner/admin in Top Performers and the needs-help card.
- Read-only finding: `ui/components/learn/course/ProgressList.vue` renders progress from `/api/courses/{course}/progress` and top performers from `/api/courses/{course}/top-performers`; at-risk students are computed from the returned `members`.
- Backend finding: `CourseController::progress()` and `CourseController::topPerformers()` both start from `$course->courseMembers()` without filtering learner roles or excluding the course owner. `course_members.role` documents 1=student, 2=student_leader, 3=teacher, 4=admin.
- Likely fix plan: add a shared learner-member query/scope for active learner memberships, probably `whereIn(role, [1, 2])`, `where(course_member_status, 1)`, and `where(user_id, '!=', $course->user_id)`, then use it consistently in progress, stats, top performers, grade distribution, at-risk card, and export if expected to be learner-only.
- Verification plan: add/adjust focused feature coverage for a course with student, student_leader, teacher/admin, and owner membership; verify `/api/courses/{course}/progress` pagination/stats and `/top-performers` exclude non-learners; smoke-test `/Learn/Courses/5/progress`.

### 2026-05-30 - Dashboard activity login label analysis
- User asked whether `login` and `เข้าสู่ระบบ` in `/dashboard` recent activity are the same thing.
- Finding: `DashboardActivityFeed.vue` renders two separate data sources: gamification `recent_xp` and points `transactions`. Auth login fires `UsageEventType::LOGIN`; `GamificationRuleEngine` then creates an XP/rule log and points transactions for the login rule and daily-login quest.
- Interpretation: `login`, `เข้าสู่ระบบ`, and `รางวัลภารกิจ: Daily Login` are related to the same login usage event, but displayed as separate reward records from different systems. No code changes requested yet.

### 2026-05-30 - Dashboard leaderboard NaN analysis
- User reported `/dashboard` top points leaderboard showing `NaN P`.
- Finding: `DashboardLeaderboard.vue` formats `user.total_points`, while `/api/gamification/leaderboard/points` currently returns `points` from `users.pp` without a `total_points` alias.
- Plan: keep the API contract compatible by adding `total_points` to the points leaderboard response, and make the dashboard widget normalize `total_points`/`points`/`score` safely before formatting.
- Intended files: `api/nuxnanravel/app/Http/Controllers/Api/GamificationController.php`, `ui/components/dashboard/DashboardLeaderboard.vue`.
- Verification plan: run PHP syntax check for the controller and a focused frontend type/lint check if practical; browser smoke test `/dashboard` if local auth/session allows.
- Completed: API now returns both `points` and `total_points`; dashboard widget falls back across `total_points`, `points`, `score`, and `pp` and formats only finite numbers.
- Verification: `php -l app/Http/Controllers/Api/GamificationController.php` passed; Pint ran for `GamificationController.php`; `php artisan route:list --path=gamification/leaderboard/points` confirmed the route; local endpoint returned numeric `total_points`; `http://localhost:3000/dashboard` returned 200. `cmd /c npx vue-tsc --noEmit --pretty false` still fails on broad pre-existing TypeScript errors unrelated to this widget.

### 2026-05-30 - Sidebar widget API timeout fix completed
- User reported 30s frontend timeouts for `/api/friends/suggestions`, `/api/friends/pending`, `/api/donates/widget`, and `/api/advertises/widget`.
- Findings: routes existed and `/api/ping` responded, but local PHP responses were slow enough that concurrent widget calls could queue. Friend widget endpoints serialized full `UserResource` records and triggered extra count queries; advert widget serialized advertiser via full `UserResource`; donate widget selected full rows.
- Changed: compact friend widget payloads in `FriendController`, narrow donate/advert widget queries, compact `AdvertResource` advertiser payload, and fix `AdvertisesWidget.vue` click handler mismatch.
- Verification: PHP syntax checks passed; Pint ran on the touched PHP files; direct endpoint timings after the change were about 2.46-3.44s. `npx nuxi typecheck` still fails on broad pre-existing TypeScript issues across unrelated files; `npm run build` reached client bundling but timed out at 180s.

### 2026-05-30 - Cleared stale analysis board
- Removed the completed User Profile analysis and Work Plan from this file so the board is ready for the next task.
- Reset Current Snapshot and Active Work to show no active task.

### 2026-05-29 - User profile page fixes completed
- User Profile phases were implemented before this cleanup, including backend privacy/resource updates, frontend profile/sidebar/tab fixes, rich text handling, video/certificate behavior, and `UserProfilePrivacyTest.php`.
- Existing uncommitted files still include those profile changes; treat them as user/previous-session work unless explicitly asked to modify them.

### 2026-05-29 - Exam Retake Phase 2 + Course Feed Edit Bug completed
- Course Feed edit fixed by using `api.post` with `_method=PATCH` in `FormData` for multipart updates.
- Exam Retake Phase 2 added retake grant/use fields, remediation grant logic, quiz result use tracking, `retake_status` response data, and frontend panel states.
- Committed: `3caf0ffc` (feed fix), `26b04ce5` (retake phase 2).

### 2026-05-29 - Course feed admin delete/copy plan review
- Read-only inspection confirmed create/update/delete routes were distinct and backend delete behavior was valid.
- Likely bug was frontend multipart PATCH handling in `CourseEditPostModal.vue`; recommended body `_method=PATCH` convention.

### 2026-05-27 - Typing Classroom Race review and fixes
- Reviewed `race.vue`, `useClassroomRace.ts`, and `TypingRaceController.php`.
- Fixed countdown view, Echo leave usage, progress throttle cleanup, finalize logic for left participants, and rank race condition.
- Committed in `f389406e`.

### 2026-06-02 - Nuxnan admin login redirect fix
- User reported `/nuxnan-admin/login` could not be opened because it immediately redirected to the admin dashboard.
- Finding: `ui/pages/nuxnan-admin/login.vue` uses `admin-guest`, and `ui/middleware/admin-guest.ts` redirected already-authenticated admin users to `/nuxnan-admin`.
- Changed `ui/middleware/admin-guest.ts` so direct navigation to `/nuxnan-admin/login` always shows the login form, allowing admins to re-login or switch accounts.
- Verification: read-back check of the middleware confirmed the login-path bypass is in place. Full Nuxt build/typecheck was not run for this one-line middleware change.

### 2026-06-02 - Safe User Deletion plan finalized

**Context:** User requested detailed implementation plan for safe user account deletion in nuxnan-admin.

**Findings (verified against codebase):**
- `AdminController::destroy()` calls bare `$user->delete()` — no transaction, audit, or impact check
- `User` model has no `SoftDeletes`; `users` table has no `deleted_at`
- DB behavior splits into 3 categories: orphan (old tables, no FK), cascade delete (newer tables — wallet/points/purchase history permanently lost), FK RESTRICT causing 500 (school_attendances.created_by, library_loans.handled_by, asset_management.requested_by, course_permissions.granted_by)
- Frontend uses direct `$fetch`, catch only logs to console, no impact preview

**Plan decisions:**
- Default action = soft delete + anonymize email immediately (prevent unique constraint conflict on new registration)
- BLOCKER vs WARNING distinction: sole academy/course owner and FK RESTRICT tables are blockers; cascade/orphan are warnings with checkbox confirmation
- JWT token invalidated immediately on soft delete
- Restore recovers user row + orphan records only; cascade-deleted records are gone permanently (documented in UI)
- Hard delete/purge deferred to future admin purge job

**Plan recorded in Work Plan section above. 6 implementation steps, 11 PHPUnit test scenarios.**

### 2026-06-02 - Admin login 500 and forgot-password route warning fix
- User pasted browser logs showing `No match found for location with path "/auth/forgot-password"` and `POST /api/admin/auth/login` returning 500.
- Finding: `ui/pages/nuxnan-admin/login.vue` linked to `/auth/forgot-password`, but the existing Nuxt page path is `/auth/ForgotPassword`. Laravel log showed admin login 500 came from `User::getAllPermissions()` querying the missing `permissions` table.
- Changed `ui/pages/nuxnan-admin/login.vue` to use the existing forgot-password page path. Changed `api/nuxnanravel/app/Models/User.php` so `hasPermission()` and `getAllPermissions()` gracefully handle missing RBAC permission tables by returning `false`/`[]` instead of throwing.
- Verification: `php -l app/Models/User.php` passed; `php artisan route:list --path=admin/auth/login` confirmed the login route; bootstrapped Laravel via `php -r` and confirmed `getAllPermissions()` returns an empty array without SQL errors when permission tables are missing; Pint ran for `User.php`; `git diff --check` passed.

### 2026-06-02 - Admin new-user verification plan
- User requested an implementation plan for `/nuxnan-admin/users` so admins can verify newly registered users.
- Read-only findings: `AuthController::register()` creates self-registered users with `verified=false` and null `email_verified_at`, but immediately issues a JWT; `AdminController` already has `verifyEmail()` and `unverifyEmail()` routes under `permission:user-edit`; user list filtering maps `status=verified|unverified` to `email_verified_at`; `UserResource` exposes `verified`, `is_verified`, and `email_verified_at`.
- Browser check: unauthenticated local navigation to `http://localhost:3000/nuxnan-admin/users` redirects to `/nuxnan-admin/login`, so UI verification of the table needs an admin session.
- Plan direction: reuse `email_verified_at` as the approval gate, optionally keep `verified` synchronized for compatibility, add a Pending Verification queue/filter/bulk actions on the users list, and enforce pending-user access in auth/middleware if the policy is "must be admin verified before use".
- Intended files: `api/nuxnanravel/app/Http/Controllers/Api/AdminController.php`, `api/nuxnanravel/routes/admin/admin.php`, `api/nuxnanravel/app/Http/Controllers/Api/Auth/AuthController.php`, optional middleware under `api/nuxnanravel/app/Http/Middleware/`, `ui/pages/nuxnan-admin/users/index.vue`, `ui/pages/nuxnan-admin/users/[id]/index.vue`, and focused tests under `api/nuxnanravel/tests/Feature/`.
- Risks: existing code currently treats email verification and admin approval as the same thing; changing login/API access for unverified users may affect onboarding, OAuth users, gamification login rewards, and flows that expect immediate access after registration.

### 2026-06-02 - Admin users Invalid Date analysis
- User asked why some rows in `/nuxnan-admin/users` show `Invalid Date` for registration date.
- Finding: frontend renders `new Date(user.created_at).toLocaleDateString('th-TH')` in `ui/pages/nuxnan-admin/users/index.vue`, while admin `UserResource` passes `$this->created_at` through `formatTimestamp()`. The `User` model also overrides `getCreatedAtAttribute()` to return `d-m-Y H:i:s`, which is not a safe JavaScript date string.
- Impact: dates where the day is greater than 12 become `Invalid Date`; dates where the day is 1-12 may parse as `MM-DD-YYYY`, so displayed values such as `6/1/2569` can be silently wrong, not just cosmetically odd.
- Recommended fix: remove or avoid the global `getCreatedAtAttribute()` date accessor, return ISO/RFC3339 timestamps from admin resources (e.g. `toISOString()`/`toJSON()`), and add a frontend `formatDate()` helper that validates null/invalid dates before display.
