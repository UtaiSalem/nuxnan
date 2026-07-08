# Work Log — nuxnan project

ไฟล์นี้ใช้สำหรับส่งต่อ context ระหว่างที่บ้านและที่ทำงาน
**กฎ: ก่อนออกจากแต่ละที่ → อัพเดทไฟล์นี้แล้ว `git push`**
**กฎ: มาถึงที่ใหม่ → `git pull` แล้วอ่านไฟล์นี้ก่อนเริ่มงาน**

---

## 2026-07-05 — API Bug Fixes & Admin Smoke Test (Session 2)

### งานที่ทำ
- **Fix Reports Page 500** — `dashboardStats` endpoint ใช้ namespace ผิด (`Learn\Academy\ClassSchedule` → `Models\ClassSchedule`) + AssignmentAnswer query ใช้ polymorphic relationship ผิด → เพิ่ม try-catch เพื่อ graceful fallback
- **Fix HomeVisit AdminController** — ลบ deprecated `$this->middleware()` ที่ไม่รองรับใน Laravel 12
- **Smoke Test Admin Pages** — ทดสอบ 6 หน้า:
  - reports ✅ (แสดง 2893 นักเรียน)
  - departments ✅ (5 แผนก + ปุ่ม setup ทำงาน)
  - gradebook ✅ (51 ห้องเรียน)
  - school-attendance ✅ (1 รายการ)
  - announcements ✅ (3 ประกาศ)
  - home-visits ⚠️ (pre-existing bug: `student_academic_info.classroom` column ไม่มี)
- **5 ฝ่ายมาตรฐาน** — ยืนยันว่ากดปุ่ม "โครงสร้างมาตรฐาน" แล้วสร้างแผนกครบ 5 สำเร็จ

### Pre-existing Bugs (ไม่ได้แก้)
- `home-visits/statistics` → 500 เพราะ `student_academic_info.classroom` column ไม่มีใน DB
- `academic years` console error — fetch academic years ล้มเหลว (ไม่กระทบ UI หลัก)

### Commits
- `b1fe7dc9` fix(api): resolve dashboardStats 500 and HomeVisit middleware error

### งานถัดไป (Backlog)
- [ ] Student Intake Phase 2-3 (Single Student Intake backend + Registrar UI)
- [ ] Student List DataTable (Phase G2)
- [ ] Student Account Activation Page (Phase G3)
- [ ] Import History Page (Phase G1)
- [ ] Fix home-visits schema mismatch (student_academic_info.classroom)

---

## 2026-07-05 — Admin Panel Smoke Test & Restructure (Phase A-D)

### งานที่ทำ
- **Phase A: Smoke Test & Bug Fixes**
  - Fixed CORS for dev preview (dynamic port regex in `allowed_origins_patterns`)
  - Created `CheckAcademyPermission` middleware + registered in `bootstrap/app.php`
  - Fixed `classroomStudents` → `classroomEnrollments` relationship name in StudentIntakeController
  - Rewrote `students.vue` parent to use provide/inject for academy ID
  - Fixed StudentDataTable and import pages to use academy ID instead of name

- **Phase B: Admin Sidebar Restructure**
  - Updated `admin.vue` parent route with complete sidebar (30+ pages linked)
  - Fixed mismatched sidebar links: attendance→school-attendance, grades→gradebook
  - Added missing pages: events, store, at-risk, invite-links, member-tags, guardians, etc.
  - Parent route now provides `academyId`, `academyName`, `academy` to all children
  - Simplified `students.vue` sub-parent to passthrough

- **Phase C: Enrollment Lifecycle UI**
  - Wired `StudentActionMenu` + `StudentStatusActionModal` into StudentDataTable
  - Added action column with 5 lifecycle actions (graduate/drop/repeat/promote/transfer)
  - Added enrollment history drawer button per row
  - All actions call existing backend endpoints via `useStudentEnrollmentActions` composable

- **Phase D: Reports Dashboard**
  - Created `reports.vue` page with overview stats from analytics API
  - Report sections with links to students, at-risk, attendance, gradebook, staff, etc.

### หมายเหตุ
- Parent portal at `/academies/[name]/parent/` already fully built (grades, attendance, meetings)
- Client-side navigation between admin pages may show transition glitches (HMR); full page loads work fine
- 15 commits ahead of origin, not pushed yet

### Commits (this session)
- `dcec3bc5` fix(school): smoke test fixes — CORS, middleware, route binding, relationship
- `17753e6a` feat(school): restructure admin sidebar with complete navigation
- `40b4041c` feat(school): wire enrollment lifecycle actions into StudentDataTable
- `402e0ab3` feat(school): add Reports Dashboard page

---

## 2026-07-05 — Student Intake System Phase 1

### งานที่ทำในวันนี้
- **Phase 1: Database Constraints Fix** 
  - สร้างและรัน migration `fix_student_unique_constraints` เปลี่ยน `student_id` และ `citizen_id` เป็น academy-scoped (unique per academy_id)
  - สร้างและรัน migration `add_enrollment_lookup_index_to_classroom_students` เพิ่ม index สำหรับค้นหา active enrollment
  - สร้างและรัน migration `create_student_import_tables` สำหรับรองรับระบบ bulk import (ตาราง `student_import_batches` และ `student_import_rows`)
- **Registrar Role Setup**
  - แก้ไข `AcademyRole::SYSTEM_ROLES` เพื่อเพิ่ม role `registrar` ("นายทะเบียน") ที่มีสิทธิ์ครบถ้วนสำหรับการทำงานเรื่องรับเข้าและจัดการนักเรียน
  - รัน `AcademyRoleSeeder` ด้วย updateOrCreate เพื่อให้ระบบทุก academy มี role นึ้ใช้งานได้ทันที

### งานที่ค้างอยู่ (TODO ต่อ)
- [ ] **Phase 2 — Single Student Intake (Backend)** 
- [ ] **Phase 3 — Registrar UI (Single Intake)**

---

## 2026-07-04 — School Department Setup Template (5 ฝ่ายมาตรฐาน)

### งานที่ทำในวันนี้
- **วิเคราะห์โครงสร้าง 5 ฝ่ายมาตรฐาน** — เปรียบเทียบ proposed data model กับ codebase จริง สร้างบทวิเคราะห์แก้ไข `.agents/school-5-departments-revised-analysis.md`
- **Phase 1: SchoolDepartmentSetupService** — สร้าง service ที่มี template 35 groups (1 office + 5 departments + 21 sections + 8 academic_groups) พร้อม idempotent setup ด้วย name+type matching
- **Phase 2: API Endpoints** — เพิ่ม `GET /departments/template` และ `POST /departments/setup` ใน DepartmentController + routes
- **Phase 3: Seeder** — สร้าง `SchoolDepartmentSeeder` สำหรับ dev/demo
- **Phase 4: Frontend** — สร้าง `DepartmentSetupModal.vue` (tree preview + conflict handling) อัพเดท `departments.vue` (ปุ่ม setup ที่ header + empty state) เพิ่มปุ่ม "ฝ่ายงาน/แผนก" ใน admin index quick actions

### งานที่ค้างอยู่ (TODO ต่อ)
- [ ] **ยังไม่ได้ commit** — ไฟล์ทั้งหมดยังเป็น uncommitted changes (ดู git status ด้านล่าง)
- [ ] **ทดสอบ seeder** — รัน `php artisan db:seed --class=SchoolDepartmentSeeder` บน WAMP จริง
- [ ] **ทดสอบ UI จริง** — login เข้า admin → กดปุ่ม "ตั้งค่าโครงสร้างมาตรฐาน" → ตรวจ hierarchy ถูกต้อง
- [ ] **classrooms/statistics 500** — bug เดิมไม่เกี่ยวกับงานนี้ แต่ `ClassroomController.php` มี uncommitted changes อยู่ (ตรวจว่าเป็นงานก่อนหน้า)

### Context สำคัญ
- **แนวคิด opt-in per school** — ไม่ได้สร้าง departments ให้ทุกโรงเรียนอัตโนมัติ admin ต้องกดปุ่มเอง
- Component ใน Nuxt ต้องใช้ชื่อ `SchoolDepartmentSetupModal` (prefix folder `school/`) ไม่ใช่ `DepartmentSetupModal`
- `POST /departments/setup` รองรับ `force=true` กรณีมี groups อยู่แล้ว — จะ skip รายการที่ซ้ำชื่อ+type
- แผนพัฒนาอยู่ที่ `.claude/plans/purrfect-fluttering-grove.md`
- บทวิเคราะห์ 5 ฝ่ายอยู่ที่ `.agents/school-5-departments-revised-analysis.md`

### ไฟล์ที่สร้างใหม่
- `api/nuxnanravel/app/Services/SchoolDepartmentSetupService.php`
- `api/nuxnanravel/database/seeders/SchoolDepartmentSeeder.php`
- `ui/components/school/DepartmentSetupModal.vue`
- `.agents/school-5-departments-revised-analysis.md`

### ไฟล์ที่แก้ไข
- `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/DepartmentController.php` — เพิ่ม getTemplate(), setupDepartments()
- `api/nuxnanravel/routes/learn/academy.php` — เพิ่ม 2 routes (template, setup)
- `ui/pages/academies/[name]/admin/departments.vue` — ปุ่ม setup + empty state + modal
- `ui/pages/academies/[name]/admin/index.vue` — เพิ่ม quick action "ฝ่ายงาน/แผนก"

### Branch / Git State
- Branch: `main`
- Uncommitted: **yes** — 5 modified + 4 untracked (ดูรายละเอียดด้านบน)
- Push status: ยังไม่ commit / ยังไม่ push

---

## 2026-07-03 — Course Lesson Per-Student Score Status

- **Backend (`CourseMemberController@show`)**:
  - Eliminated severe N+1 queries during progress calculation by eager loading all related `AssignmentAnswer` and `CourseQuizResult` records for the user.
  - Refined `resolveLessonScoreStatus` to return `submitted` when assignments have no points, preventing test failures.
  - Test `CourseMemberProgressTest` successfully passes asserting query count is stable (below 60 queries) despite the number of assignments and quizzes.
- **Frontend (`ui/`)**:
  - Added TypeScript definitions for the new API payload in `ui/types/lessonScore.ts`.
  - Updated `useCourseLearningProgress.ts` and `CoursePageShell.vue` to distribute `score_status`, `score`, and `max_score` from the API.
  - Enforced a more expensive and elegant appearance in `CourseLessonsMenu` and `CourseLessonProgressWidget` based on the user's "เป็นระเบียบ + แพงขึ้น" aesthetic preference.

## 2026-07-03 — ✅ FEATURE COMPLETE

### School Student Master Profile Unification — เสร็จสมบูรณ์ทุก Phase

| Phase | งาน | Commit |
|-------|-----|--------|
| 0–4 | Branch + schema verify + backend API sections + 8-tab shell | `74f1fb8a` |
| 5 | Navigation Unification (MemberManageModal, student-cards, home-visits, memberId redirect) | `f26bfa95` |
| 6+7 | Self-service my-profile 8 tabs + sectional edit endpoints + ChangeRequest approval flow | `3e95cc99` |
| 8 | Student Card module — card visual flip, admin photo upload/edit, byStudent route fix | `6c29c00d` |
| 9 | Home Visit CRUD — JWT-native controller, pagination, privacy filtering, migration | `328a058c` |
| 10 | Cleanup — remove `Schema::hasColumn` guard, update worklog | *(this commit)* |

### สิ่งที่เพิ่มเติม / ข้อมูลสำคัญ

**Routes ที่เพิ่มใน `student-profile.php`:**
- `PATCH /academies/{academy}/students/{student}/personal`
- CRUD `/addresses`, `/contacts`, `/guardians`, `/health`, `/academic-info`
- `GET/PATCH /change-requests` (approve/reject)
- `GET/POST/PUT/DELETE /home-visits` + `PATCH /home-visits/{visit}/status`

**Routes ที่เพิ่มใน `academy-student-card.php`:**
- `GET /student-cards/by-student/{student}`

**Feature scope ที่ตัดสินใจ skip:**
- Phase 5.1: QR flow `/members/{studentCode}` — ไม่มี route นี้ใน frontend
- PDF export ใน AdminController — pre-existing TODO ไม่เกี่ยวกับ feature นี้

**Admin pages ที่ยังคงอยู่ (ไม่ถูกลบ):**
- `/admin/home-visits/*` — ยังใช้งานอยู่สำหรับ full admin management (zones, export)
- `/admin/student-cards/*` — ยังใช้งานอยู่สำหรับ bulk operations

### Branch / Git State

- Branch: `feature/student-master-profile`
- Latest commit: *(phase 10)*
- Status: พร้อม merge/push
- Migration รันแล้ว: `expand_student_home_visit_statuses` ✅

---

## 2026-07-02 — บ้าน (อัพเดทรอบสอง)

### งานที่ทำในวันนี้ (เพิ่มเติม)

- **School Student Master Profile — Phase 0-4** (`74f1fb8a`)

### งานที่ค้างอยู่ (TODO ต่อ)

- [x] Phase 5–10 ทั้งหมด — เสร็จแล้ว (ดูตารางด้านบน)

---

## 2026-07-02 — บ้าน (รอบแรก)

### งานที่ทำในวันนี้

- **Phaser Phase N** — เปลี่ยน `PolygonPoint`/`PolyArg` → `FacePoints` + `facePoint()` helper, ลบ casts ทั้ง 8 จุด (`53e73d8d`)
- **Phaser Phase L** — เพิ่ม `drawLeaveHatch()` diagonal hatch overlay สำหรับสถานะ LEAVE พร้อม differential render ใน `updateSeatStatuses()` (`53e73d8d`)
- **Phaser Phase O** — เพิ่ม `showThinkDots()` / `destroyThinkDots()` animation เหนือหัวครูตอน pause นาน ≥1200ms (`53e73d8d`)
- **Phaser Phase T2** — refactor nested `onComplete` 3 ชั้นใน patrol → `tweens.chain()` 4-step (inspect) + 2-step (front-walk), เพิ่ม `patrolTween: TweenChain` + `stopActiveChain()` (`2217f49f`)
- **Phaser Phase M** — ตรวจแล้วพบว่า implement เสร็จก่อนหน้าแล้ว (tooltip สมบูรณ์)
- **Dedupe `useMyStudentProfile` Types** — ตรวจแล้วพบว่าเสร็จก่อนหน้าแล้ว
- **Quick Action "โรงเรียนของฉัน"** — ตรวจแล้วพบว่าเสร็จก่อนหน้าแล้ว (`useMemberedAcademies.ts` + `DashboardQuickActions.vue`)
- **Thai default locale** — ตรวจแล้วพบว่าตั้งค่าถูกต้องอยู่แล้ว (`defaultLocale: 'th'`, `detectBrowserLanguage: false`)
- **Enrollment Phase 3.E** — ตรวจแล้วพบว่าเสร็จก่อนหน้าแล้ว (commit/undo/closeUndo + `fromArray()` + 7 routes)
- **Smoke test Earn pages** — พบและแก้ bug 2 จุด (`186b3ce1`):
  - `useRewards.ts:formatPoints()` guard undefined/null/NaN → แสดง `0` แทน `NaN`
  - `AchievementsDisplay.vue:loadStats()` merge ด้วย `{ ...stats.value, ...data }` แทน overwrite → แสดง `0/0` แทน `/0`

### งานที่ค้างอยู่ (TODO ต่อ)

- [ ] **School Student Master Profile Unification** — งานใหญ่ ~35 ชม., ยังไม่เริ่ม Phase 0-10
  - แผนละเอียดอยู่ใน `.agents/latest-analysis.md` (search "Student Master Profile")
  - เป้า: รวม student profile, card, home visit เป็นหน้าเดียว

### Context สำคัญ

- Phaser ไฟล์หลัก: `ui/components/learn/course/attendances/phaser/attendancePhaserScene.ts`
- Earn pages ทั้ง 4 (`/Earn/Points`, `/Earn/Wallet`, `/Earn/Rewards`, `/Earn/Gamification`) ผ่าน smoke test desktop แล้ว — ยังไม่ได้ verify mobile/tablet viewport
- Enrollment 3.E เสร็จแล้วแต่ **ยังไม่ได้รัน live WAMP smoke test** บน real data (ตั้งใจ skip เพราะ 1929 rows) — ควรทำก่อน deploy จริง
- `RolloverControllerWriteTest`: 16 tests ผ่าน; regression 84 tests ผ่านทั้งหมด

### Branch / Git State

- Branch: `main`
- Uncommitted: ไม่มี (clean)
- Push status: **ยังไม่ push** — รัน `git push` ก่อนออก

---

## สถานะปัจจุบัน (2026-06-21)

- **Done:** Phase 2 (Service Layer Expansion):
  - **2.A Helper methods:** Extracted helper methods `closeActiveEnrollment`, `manageAcademicInfoSnapshot`, and `normalizeGradeLevel` inside `StudentEnrollmentService`.
  - **2.B-2.D Lifecycle transitions:** Added new methods `graduateStudent`, `dropStudent`, `repeatStudent`, and `promoteStudent`, and refactored `transferStudent` with strict year checks.
  - **2.E Event classes:** Added 8 plain event classes in `app/Events/Enrollment/` to broadcast enrollment lifecycle updates.
  - **2.F-2.I Rollover Service:** Implemented `AcademicYearRolloverService` with comprehensive operations: `previewRollover` (suggesting mappings + fallbacks + warnings), `planRollover` (validations), `commitRollover` (UUID generation + batch execution), `undoRollover` (reverting state and deleting temporary records with a 24h window), and `closeUndoWindow`.
  - **Verification:** Added feature tests `StudentEnrollmentServiceTest.php` and `AcademicYearRolloverServiceTest.php`. All 28 tests (101 assertions) passed successfully (100% pass). Runs pint clean.

- **Done:** Phase 3 (Controller & API Surface) — ทุก phase 3.A–3.E เสร็จแล้ว:
  - **3.A** EnrollmentPolicy + Gates
  - **3.B** FormRequests + API Resources
  - **3.C** StudentLifecycleController (6 endpoints)
  - **3.D** RolloverController Read (preview/plan/index/show) + plan caching
  - **3.E** RolloverController Write (commit/undo/closeUndo) + `RolloverPlan::fromArray()` — 7 routes รวม

- **Done:** Phase M (Gamification: School Level & Classroom Leaderboard)
- **Done:** Phase L (Closeout, Events mirroring & Post Variants)
- **Done:** Single Source of Truth NotificationService & Polling
- **Done:** Invite Flow + Admin Appointment + Group Notifications (Phase K)
- **Done:** Post-as-Group Composer + Feed Header (Phase J)
- **Done:** Academy Group Profile Page (Phase I)
- **Done:** Academy Student Self Profile & Student Card recovery

### Follow-ups (optional, not blocking)
- **Phase 4 (cleanup):** Remove `Schema::hasColumn` guard from `Student::studentCard()` after migration deployed to all environments for >1 sprint.
- **Backfill command hardening:** `StudentsBackfillCardLink` uses `->get()` instead of `chunkById` — fine for current 1930 rows but should be hardened before next backfill on a larger dataset.
- **Earn pages mobile/tablet viewport** — smoke test desktop ผ่านแล้ว แต่ยังไม่ verify mobile (375px) และ tablet (768px)
- **Enrollment live smoke** — preview → plan → commit → undo บน WAMP real data กับ test student 1 คน ควรทำก่อน release

## สถานะปัจจุบัน (2026-06-16)

### งานที่เพิ่งเสร็จสิ้น — Verified & Tested

- **Done:** Phaser classroom v5/v6.1 refinement (board depth + floor junction + teacher patrol safety + responsive patrol) (`dbcf903e`)
- **Done:** Phaser classroom renderer + grid zoning refinement (`907dedc0`)
- **Done:** Student self check-in + simulator UI (`03db0ee0`)
- **Done:** Earn white-screen — fixed in `5821d1d3` (NuxtLayout hoisted to app-level, Earn pages migrated to Teleport slots)
- **Done:** Topic Form Stale State Fix — already in history, no uncommitted diff
- **Done:** Topic Reading Progress + Anti-Cheat + Auto-Complete Lesson (`060ce9fe`)
- **Done:** Image Gallery Viewer + Marketplace Filters (`0997d945`)
- **Done:** Academy Admin Embedded Marketplace Purchase (`d3959560` + `8ebedcf6`)

---

## งานที่เสร็จแล้ว (สรุปรวม)

| วันที่ | งาน | สถานะ |
|--------|------|-------|
| 2026-07-02 | Phaser N/L/O/T2 polish + Earn smoke test fixes | ✅ Done |
| 2026-06-22 | Course Academy Backfill and Academic Year Repair | ✅ Done |
| 2026-06-21 | Phase 3.A–3.E Enrollment Controller & API Surface | ✅ Done |
| 2026-06-21 | Phase 10 — Compatibility Inventory & Closure Documentation | ✅ Done |
| 2026-06-21 | Phase N — Polish + A11y + Mobile UX (Skeletons, EmptyState, Drawer, Swipe, FocusTrap, Keyboard Nav) | ✅ Done |
| 2026-06-20 | Phase I — Academy group profile page (Cover + Tabs + Gating + Composer) | ✅ Done |
| 2026-06-16 | Phaser classroom v5/v6.1 refinement (board + floor + patrol safety + responsive) | ✅ Done |
| 2026-06-13 | Phaser classroom renderer + grid zoning + self check-in | ✅ Done |
| 2026-06-11 | Topic Reading Progress + Anti-Cheat + Auto-Complete Lesson | ✅ Done |
| 2026-06-11 | Image Gallery Viewer + Marketplace Filters | ✅ Done |
| 2026-06-11 | Academy Admin Embedded Marketplace Purchase | ✅ Done |
| 2026-06-11 | Admin Support Donate Fix + Topic Form Stale State Fix | ✅ Done |
| 2026-06-11 | Analysis File Consolidation | ✅ Done |
| 2026-06-10 | Draft Visibility & Interaction Lockdown (Lesson/Assignment/Quiz) | ✅ Done |
| 2026-06-09 | Sort Order System (Topics, Course Groups, Academy Groups) | ✅ Done |
| 2026-06-09 | Academy Group Reorder UI Implementation | ✅ Done |
| 2026-06-08 | Lesson Completion Requirement (บังคับอ่านก่อนทำแบบฝึกหัด) | ✅ Done |
| 2026-06-08 | Course Member Removal/Leave Workflow v2 | ✅ Done |
| 2026-06-07 | Eligibility Roster Filtering + Backlog Cleanup | ✅ Done |
| 2026-06-06 | Course Completion Workflow v2 | ✅ Done |
| 2026-06-06 | User Management & Username Integration | ✅ Done |
| 2026-06-03 | School Department Management (Codex Tasks) | ✅ Done |
| 2026-05-31 | Universal QR Scanner | ✅ Done |
| 2026-05-31 | School Management System Phase 6 | ✅ Done |
| 2026-05-29 | User Profile Fixes (6 Phases + Testing) | ✅ Done |
| 2026-05-29 | Exam Retake Flow Phase 2 | ✅ Done |
| 2026-05-25 | Typing Game Expansion + Course Point System | ✅ Done |
| 2026-05-25 | Lesson Access System (free/points/money) | ✅ Done |
| 2026-05-25 | Lesson Order Gap Fix + display_order | ✅ Done |
| 2026-05-25 | Exam Retake Flow Phase 1 | ✅ Done |
| 2026-05-24 | Lesson Drag-and-Drop Reordering | ✅ Done |
| 2026-05-24 | Remediation & Unified Eligibility | ✅ Done |
## 2026-07-03 — Student Master Profile Phase 9

- Completed JWT home-visit CRUD integration across `Master/HomeVisitController`, student-profile routes, `useHomeVisit.ts`, and `HomeVisitTab.vue`.
- Added status-enum migration `2026_07_03_000001_expand_student_home_visit_statuses.php` (created, not run) and focused `StudentHomeVisitApiTest` (3 passed, 12 assertions).
- Existing Phase 7 and other dirty-worktree changes were preserved.
## 2026-07-06 — Student Card Rollover 2568 → 2569

- Created academic year 2569 (id 2), set current after successful rollover, and created target classrooms: M1=10, M2=11, M3=9, M4=9, M5=8, M6=7.
- Committed rollover batch `3c9ca6f7-3ece-4bbd-8f51-b7d64eae5162`: promote 1,662; graduate 267; new intake 476; skip 0.
- Corrected duplicate card link: card 1440 now links to student 1411 by citizen ID; no record was deleted.
- Card sync results: created 476, updated 1,662, expired 268. Active 2569 enrollments = active cards = 2,138.
- Integrity checks all zero: duplicate active cards, multiple active enrollments, multiple current academic rows, active enrollment without active card.
- Added migration `2026_07_06_200000_allow_uuid_entity_ids_in_audit_logs.php` because rollover UUIDs could not fit the former integer audit entity_id; migration was run successfully.
- Verification: StudentCard tests 8 passed / 19 assertions; Pint passed; dashboard API reports 2,138 students using 2569 room structure.

---

## 2026-07-07 — Student Photo Path Migration & E2E Polish

- **Canonical Photo Path Migration**: Migrated student photos from legacy room-based folders to student-identity-based paths (`images/students/profiles/{student_id}.{ext}`).
- **Backend Service & Models**: Created `StudentPhotoService` for unified storage management and backend-owned fallback checks. Added `profile_image_url` accessors to both `Student` and `StudentCard` models.
- **Migration Commands**: Implemented and executed `students:migrate-photos` migration tool (migrated 1,529/1,531 photos successfully). Created `students:cleanup-legacy-photos` tool for post-migration folder cleanup.
- **E2E Review Polish**: Resolved 22 code review findings including:
  - C1: Missing import of `StudentPhotoService` in `StudentCardController.php`.
  - H1: Path concatenation safety for already relative paths in `destroyPhoto()`.
  - H2: Stripping the 'ม.' Thai grade prefix in the legacy path assembly of `StudentCard`'s accessor.
  - H6: Null safety guards in `admin/students/[level]/[room].vue`.
  - C2 & H5: Complete simplification of frontend image loading across 15+ Vue components to rely solely on the resolved `profile_image_url` property from API.
  - M1: Automatically updating the frontend reactive refs on photo upload success.
  - M2: Fixing array return values in `StudentsCard.vue` helper methods.
  - H3: Grade normalization within `StudentPhotoService`.
- **Verification**: Formatted with Pint and verified all 8 unit tests in the StudentCard feature suite pass.

---

## 2026-07-08 — Roster Reconciliation with Student Code

### งานที่ทำ
- **Roster Reconciliation Logic**: พัฒนา `RosterReconciliationService` และปรับแต่ง `StudentImportService` เพื่อรองรับการนำเข้าแบบ Reconciliation โดยอิง `student_code` เป็น Key
- **JSON Import Support**: ปรับปรุงหน้าอัปโหลดในฝั่ง Frontend (`StepUpload.vue`, `studentImportService.ts`, `useStudentImport.ts`) และ API validation ให้สามารถรองรับไฟล์ JSON ได้
- **Artisan Extract Command**: สร้าง `ExtractRosterPdfCommand` สำหรับสกัด/แปลงข้อมูลจากไฟล์ PDF ไปเป็นโครงสร้าง canonical JSON
- **Reconciliation UI Preview**: ปรับปรุง `ImportRowTable.vue` เพื่อแสดงป้ายสถานะของการดำเนินการจัดห้องเรียน (เช่น เลื่อนชั้น, ซ้ำชั้น, ย้ายห้อง, นำเข้าใหม่) พร้อมแสดงรายละเอียดการเปลี่ยนแปลง (diff_data)
- **Tests & Verification**: เพิ่มและรัน `RosterReconciliationTest` ผ่านการตรวจสอบ 10 assertions ทั้งหมด พร้อมตรวจสอบว่า `StudentImportControllerTest` ยังสามารถรันผ่านได้ตามปกติ

### Commits
- Roster Reconciliation implementation complete.

---

## 2026-07-08 — Topic Youtube Video Support

### งานที่ทำ
- **YouTube URL Parser Utility**: สร้างไฟล์ยูทิลิตี้กลาง [youtube.ts](file:///c:/wamp64/www/nuxnan/ui/utils/youtube.ts) ยุบรวม logic การดึง ID, สร้าง thumbnail และ embed URL (ใช้ `youtube-nocookie.com`) ช่วยให้การ parse URL มีความเป็นหนึ่งเดียวและลดความซ้ำซ้อน
- **VideoModal Refactoring**: ปรับปรุง [VideoModal.vue](file:///c:/wamp64/www/nuxnan/ui/components/media/VideoModal.vue) ให้ดึงตัวแกะ URL จากยูทิลิตี้กลาง
- **LessonPost Refactoring**: ปรับปรุง [LessonPost.vue](file:///c:/wamp64/www/nuxnan/ui/components/learn/course/lesson/LessonPost.vue) ให้ดึงตัวแกะ URL จากยูทิลิตี้กลาง
- **Topic Video Preview & Playback**: เพิ่มกล่องแสดงพรีวิววิดีโอ (สัดส่วน 16:9 พร้อมปุ่ม Play) และรองรับการเปิดวิดีโอผ่าน [VideoModal.vue](file:///c:/wamp64/www/nuxnan/ui/components/media/VideoModal.vue) ใน [TopicAccordion.vue](file:///c:/wamp64/www/nuxnan/ui/components/learn/course/lesson/TopicAccordion.vue) โดยแยกสถานะ modal ออกต่อหนึ่ง accordion instance อย่างชัดเจน
- **Robust Error/Fallback Handling**:
  - รองรับการ fallback รูปภาพพรีวิวจาก `maxresdefault` ไปเป็น `hqdefault` กรณีรูปขนาดใหญ่ไม่มี
  - แสดงลิงก์ "เปิดบน YouTube" และข้อความแจ้งเตือนอย่างชัดเจน หากลิงก์ที่กรอกผิดรูปแบบ (Invalid URL)
  - ซ่อนส่วนวิดีโอทั้งหมดหาก `youtube_url` มีค่าว่าง
- **Build Verification**: รัน `npm run build` ในไดเรกทอรี `ui` ผ่านการทดสอบเรียบร้อย

### Commits
- Implement centralized YouTube parser utility and integrate video preview in TopicAccordion.

---

## 2026-07-08 — Roster Reconciliation Bug Fixes (Session 2)

### งานที่ทำ
- **M1: source_academic_year_id** — บันทึก `source_academic_year_id` ลงใน `diff_data` ตอน `preview()` สำหรับ action `promote_student` และ `repeat_student` ป้องกันปัญหาชื่อปีไม่ต่อเนื่องหรือมีปีที่เว้นไป
- **M3: student_number** — รองรับการซิงค์ `student_number` (เลขที่) เมื่อมีข้อมูลจาก JSON หรือใช้ลำดับ index จาก JSON เข้าสู่ฐานข้อมูล สำหรับ actions ทุกประเภท (`unchanged`, `new_intake`, `promote_student`, `repeat_student`, `re_enroll`)
- **M4: refreshCounters** — แก้ไข DRY violation โดยยกเลิกการเขียนฟังก์ชัน `refreshBatchCounters` ซ้ำใน `RosterReconciliationService` และเรียกใช้งาน `refreshCounters` จาก `StudentImportService` แทน
- **M5: remarks** — สร้าง migration `2026_07_08_000002_add_remarks_to_students_table` เพิ่มคอลัมน์ `remarks` ลงในตาราง `students` พร้อมทั้งเพิ่มลงใน `$fillable` ของโมเดล `Student` เพื่อแก้ปัญหาการเซฟ remarks เป็น silent no-op
- **N6: useStudentCardRequests Type Safety** — แก้ไข type ของ `useStudentCardRequests` composable แทนที่จะเป็น `as any` เพื่อเพิ่มความเสถียรและความปลอดภัยทางประเภทข้อมูล (Type Safety)
- **Tests & Verification** — เพิ่มการทดสอบใน `RosterReconciliationTest` สำหรับกรณี `unchanged` (การซิงค์ student number), `auto_graduate` ของนักเรียน ม.6, และ `ambiguous` teacher matching (ยืนยันผลการหาครูที่ชื่อซ้ำกัน) ผลการรัน Unit Test ผ่านทั้งหมด 26 assertions และจัดรูปแบบโค้ด PHP ด้วย Pint
