# Work Log — nuxnan project

ไฟล์นี้ใช้สำหรับส่งต่อ context ระหว่างที่บ้านและที่ทำงาน
**กฎ: ก่อนออกจากแต่ละที่ → อัพเดทไฟล์นี้แล้ว `git push`**
**กฎ: มาถึงที่ใหม่ → `git pull` แล้วอ่านไฟล์นี้ก่อนเริ่มงาน**

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
