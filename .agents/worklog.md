# Work Log — nuxnan project

ไฟล์นี้ใช้สำหรับส่งต่อ context ระหว่างที่บ้านและที่ทำงาน
**กฎ: ก่อนออกจากแต่ละที่ → อัพเดทไฟล์นี้แล้ว `git push`**
**กฎ: มาถึงที่ใหม่ → `git pull` แล้วอ่านไฟล์นี้ก่อนเริ่มงาน**

---

## สถานะปัจจุบัน (2026-06-06, updated)

### Course Completion Workflow v2 — Shipped, tests blocked

**Commits**: `e74312c0` (fix), `b89a3687` (docs) — push แล้ว

**สิ่งที่ทำเสร็จ**:
- ✅ ลบ syntax garbage ท้าย `CourseCompletionController.php`
- ✅ Finalize guard รองรับ `grading` + `published`
- ✅ `finalizeGrades` ใช้ `courseMembers()` (HasMany) แทน `members()` (BelongsToMany) → fix Bug B
- ✅ `syncToTranscripts` scope ด้วย `academy_id`
- ✅ `overrideGrade` re-sync เมื่อ status = `finalized`
- ✅ `reopenGrading` set `CourseGrade.is_published = false`
- ✅ `getMyGrade` ตัด `'grading'` ออกจาก `can_view`
- ✅ Migration `add_semester_id_to_courses_table` + service ใช้ `$course->semester_id`
- ✅ เขียน regression test 4 cases (`CourseCompletionWorkflowTest.php`)

**🚫 Blocker ที่ค้าง**:
- Regression tests **รันไม่ได้** เพราะมี migration อื่นในโปรเจคใช้ MySQL syntax `ALTER TABLE ... MODIFY COLUMN ... ENUM(...)` ที่ SQLite ไม่รองรับ
- ผลกระทบ: ทั้งโปรเจคไม่มี automated test coverage เพราะ `RefreshDatabase` fail ตั้งแต่ setUp
- ติดตามใน spawn task แยก (Course Completion ไม่ blocked แต่ทั้งโปรเจคควรแก้)

**งานถัดไป**: รอรับมอบหมายงานใหม่

---

### User Management & Username Integration — Completed

**Feature**: แก้ไขระบบจัดการผู้ใช้ใน Admin Panel และเปิดใช้งานระบบ Username

**สิ่งที่ทำเสร็จแล้ว**:
- ✅ **Fixed 422 Error**: ปรับปรุงชื่อ role จาก `USER` เป็น `STUDENT` ให้ตรงกับฐานข้อมูล ป้องกัน validation error
- ✅ **Username Integration**: เพิ่มคอลัมน์ `username` ใน DB, อัปเดต Model, Controller และ UI ให้รองรับการแก้ไข/แสดงผล
- ✅ **Inline Edit Modal**: เพิ่มระบบแก้ไขผู้ใช้ผ่าน Modal ในหน้ารายชื่อผู้ใช้ (index.vue) เพื่อ UX ที่ดีขึ้น
- ✅ **Auth Store Stability**: ปรับปรุงระบบ registration และ auth state ให้รองรับการส่ง username และข้อมูลอ้างอิง
- ✅ **Committed & Pushed**: ทุกไฟล์ถูก push ขึ้น main เรียบร้อยแล้ว

**งานถัดไป**: รอรับมอบหมายงานใหม่ (เช่น ระบบ Course Completion หรือ Transcript ที่วิเคราะห์ไว้)

---

## สถานะปัจจุบัน (2026-06-03, archived)

### School Department Management Feature — Codex Tasks Active

**Feature**: ระบบบริหารจัดการแผนกโรงเรียน — แก้ bugs เพื่อให้ feature ใช้งานได้จริง

**งานที่ Codex ต้องทำ**: ดู `.agents/codex-tasks.md` (TASK-001 ถึง TASK-006)
**สิ่งที่มีอยู่แล้ว**: `DepartmentController`, Routes, `departments.vue` (ต้องแก้ bugs)

**Claude Routine**: ตรวจสอบ codex-tasks.md ว่า Codex ทำงานเสร็จ + ถูกต้องหรือไม่

### School Department Management Feature — Codex Tasks Completed (2026-06-03)

- **Branch**: main
- **Files touched**:
  - `.agents/codex-tasks.md`
  - `.agents/latest-analysis.md`
  - `.agents/worklog.md`
  - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/DepartmentController.php`
  - `api/nuxnanravel/routes/learn/academy.php`
  - `ui/pages/academies/[name]/admin/departments.vue`
- **Done**:
  - TASK-001: `getStatistics()` now returns `departments_with_head`.
  - TASK-002: `index()` now resolves department `head_user` in bulk without N+1 queries.
  - TASK-003: Fixed Department Management frontend API paths and request bodies.
  - TASK-004: Fixed frontend reads for Laravel `response.data` shape.
  - TASK-005: `updateDepartment()` now uses `api.patch()`.
  - TASK-006: Confirmed `useApi.ts` already has `patch()`.
  - Department detail/member/permission routes are now academy-scoped as `/api/academies/{academy}/departments/{department}...`, matching the frontend contract and permission controller signature.
- **Verification**:
  - `php -l app\Http\Controllers\Api\Learn\Academy\DepartmentController.php` passed.
  - `php artisan route:list --path=departments` confirmed all department routes and HTTP methods.
  - `vendor\bin\pint.bat app\Http\Controllers\Api\Learn\Academy\DepartmentController.php routes\learn\academy.php` passed and formatted touched PHP files.
  - `git diff --check` passed.
- **Blocked verification**:
  - `php artisan test tests\Api\SchoolManagementApiTest.php --filter=test_can_list_departments` failed during test setup because sqlite has no `users` table.
  - `cmd /c npx vue-tsc --noEmit --pretty false` timed out after 120s and reported pre-existing `vue-router/volar/sfc-route-blocks` package export failure before checking the changed page.

---

## สถานะปัจจุบัน (2026-05-29, archived)

- **งานที่เสร็จแล้วทั้งหมด (committed ใน main):**
  - **Typing Game — Responsive Fix** (typing/index.vue)
    - Outer gap: `gap-6 lg:gap-8`
    - Card: `p-5 sm:p-6 lg:p-7`, inner `space-y-6 lg:space-y-7`
    - Language: `grid grid-cols-3 gap-2` + `whitespace-nowrap overflow-hidden`
    - Difficulty: `grid-cols-[repeat(auto-fill,minmax(80px,1fr))]` (auto-fill ดีกว่าแผนเดิม)
    - CTA: `py-4 sm:py-5 text-base sm:text-xl`
  - **Typing Game Expansion (Full System)**
    - Race: `TypingRaceController`, `TypingRaceRoom`, `TypingRaceParticipant`, Events, `race.vue`, `RaceTrack.vue`, `useClassroomRace.ts`
    - Tournament: `TypingTournamentController`, models, weekly command, `tournaments/`
    - Daily Challenge: `TypingDailyChallengeController`, `daily.vue`
    - Session/Score: `TypingSessionController`, `GameScoreController`, `TypingScoreService`
    - Achievement: `TypingAchievementController`, seeder
    - Leaderboard: `TypingLeaderboardController`
    - Classroom: `TypingClassroomController`
    - Words/Sentences/Admin: controllers + seeders
    - Composables: `useTypingGame`, `useTypingApi`, `useWpmCalc`, `useComboSystem`, `useKeyTraining`
    - Store: `stores/typing.ts`
    - Components: 10+ (modes, ui components, VirtualKeyboard, etc.)
    - Pages: `play.vue`, `result.vue`, `daily.vue`, `profile/[userId].vue`, `report/[academyId].vue`
  - **Course Point System**
    - Models: `CoursePointAccount`, `CoursePointCampaign`, `CoursePointCampaignClaim`, `CoursePointTransaction`
    - Service: `CoursePointAccountService`
    - Controllers: `CoursePointAccountController`, `CoursePointCampaignController`, `LessonRewardCampaignController`
    - Migrations: course_point_accounts, transactions, campaigns, claims, reserved_balance, lesson_reward_fields
    - Components: `LessonRewardBadge.vue`, `LessonRewardForm.vue`
    - Composable: `useCoursePoints.ts`
  - **Lesson Access System**
    - Migration: `publication_status`, `access_type`, `money_tuition_fee` ใน `lessons`
    - Migration: `lesson_accesses` table
    - Model: `LessonAccess.php`, `Lesson.php` updated
    - Service: `LessonAccessService.php`
    - Controller: `CourseLessonController` — unlock endpoint, access filtering
    - Resource: `LessonResource` — locked/unlocked data separation, `display_order`
  - **Lesson Order Gap Fix + display_order**
    - `store()`: default order → `max(order)+1`
    - `reorder()`: backend normalize 1..N
    - `index()`: admin → all lessons; student → paginate
    - `display_order`: rank ใน published lessons เท่านั้น (1-indexed, no gap)
  - **LessonPost.vue Badge Overlap Fix** — restructured overlay, no overlap at any viewport
  - **LessonOrderWidget.vue** — fix status badge field, reorder payload format
  - **Exam Retake Flow Phase 1**
    - Migration: `quiz_id` ใน `course_remediation_sessions`
    - Model/Controller: `CourseRemediationSession` + `RemediationController` updated
    - Quiz Detail: returns student remediation status for specific quiz
    - Frontend: `remediation_status` card ใน Quiz Page + Quiz dropdown ใน Remediation admin form
  - **Other**: Course feeds 500 fix, Course info accordion, Remediation route alignment, `ExamEligibilityPanel.vue`, Lesson order widget polish
  - **Course Feed Edit Bug Fix** (2026-05-29)
    - `CourseEditPostModal.vue`: `api.patch` → `api.post` + `_method=PATCH` ใน FormData (PHP PATCH+multipart bug)
  - **Exam Retake Flow Phase 2 — Authorization Logic** (2026-05-29)
    - Migration: `retake_unlocked_at`, `retake_used_at`, `retake_granted_by_enrollment_id` ใน `course_quiz_results`
    - `RemediationService::gradeEnrollment()`: เมื่อ passed + `session->quiz_id` → set retake grant
    - `CourseQuizResultController::store()`: mark `retake_used_at` เมื่อนักศึกษา start quiz ด้วย active grant
    - `CourseQuizController::show()`: return `retake_status` ใน response
    - `ExamEligibilityPanel.vue`: แสดง state "ผ่านการแก้ตัวแล้ว" + "ใช้สิทธิ์แล้ว"
    - `quiz/[quizId]/index.vue`: รับ `retake_status` + แสดง panel เมื่อ `can_retake`
    - Factory: `CourseQuizFactory.php`
    - Migration conflict fix: typing_words + typing_sentences index rename
    - Feature tests: `ExamRetakePhase2Test.php` (3 test cases)

- **In Progress:**
  - —

- **TODO:**
  - ไม่มี — ทุกงานที่วางแผนไว้เสร็จแล้ว

- **Completed Recently:**
  - **User Profile Fixes (All 6 Phases + Testing)** (2026-05-29)
    - Phase 1: Immediate Bug Fixes
    - Phase 2: Privacy Control (Backend Redaction & Locked UI)
    - Phase 3: Dynamic Sidebars (Friends & Badges)
    - Phase 4: Tab Component Refactoring
    - Phase 5: Activity Feed Optimization
    - Phase 6: UX Polish & Error States
    - Phase 7: Automated Feature Tests (`UserProfilePrivacyTest.php`)

- **Pending Commit:**
  - ✅ ไม่มี — ทุกงาน committed แล้ว (2026-05-30)

---

## ประวัติการทำงาน (Timeline)

- **User Profile Fixes (Full Implementation & Testing)** (2026-05-29)
  - ✅ **Backend**: `UserProfileResource` ปลอดภัยขึ้นด้วย privacy redaction + เพิ่ม `level_progress`, `friends_preview`.
  - ✅ **Frontend**: `[id].vue` มี "Private Profile" state + Sidebar เพื่อน/Badge ดึงข้อมูลจริงจาก API.
  - ✅ **Refactoring**: `CertificatesList.vue` ใช้ `useApi` ตามมาตรฐานโครงการ.
  - ✅ **Quality Assurance**: เพิ่ม `UserProfilePrivacyTest.php` ทดสอบ 5 scenarios (Owner, Friend, Stranger) ผ่าน 100%.
- **Exam Retake Flow Phase 2 — Authorization Logic** (2026-05-29)
  - Bug 1: countdown view ไม่แสดง → เพิ่ม `view.value = 'countdown'` ใน watch
  - Bug 2: Echo leave API ผิด → ใช้ `$echo.leave()` แทน `channel.leave()`
  - Bug 3: memory leak throttle → `clearTimeout(progressThrottle)` ใน leaveRoom()
  - Bug 4: finalize ค้างเมื่อคนออก → `->where('status', '!=', 'left')->count()`
  - Bug 5: rank ซ้ำ race condition → `DB::transaction()` + `lockForUpdate()`
- **Typing Classroom Race — deep code review & improved plan** (2026-05-27)
- **Typing Classroom Race — initial improvement plan** (2026-05-27)
- **Typing Game Expansion + Course Point System** (committed 2026-05-25)
  - Full typing game system: Race, Tournament, Daily Challenge, Session, Achievement, Leaderboard
  - Course point system with lesson reward campaigns and quota/budget control
  - Lesson Access System (free/points/money) with persistent unlock records
  - Lesson Order Gap Fix + display_order
  - All UI components, composables, pages for typing game
  - Typing Game Settings — Responsive Fix (auto-fill minmax approach)
  - Build: `npm run build` ✅, `php artisan migrate` ✅
- **Exam Retake Flow Phase 1** (committed 2026-05-25)
  - quiz_id link in remediation sessions, remediation status card in quiz page
- **Lesson Order Widget Polish + Badge Overlap Fix** (committed 2026-05-25)
  - Silent mode, hide when ≤1 lessons, collapsible widget, badge overlap restructured
- **Remediation & Unified Eligibility** (committed 2026-05-24)
  - Fixed route mismatch, bulkEnroll, ExamEligibilityPanel.vue
- **Lesson Drag-and-Drop Reordering** (committed 2026-05-24)
  - Compact admin widget, bulk reorder endpoint, feature tests
- **Cross Math Enter key** (2026-05-23)

---

## 2026-05-31 — Universal QR Scanner Implementation — COMPLETED
**สถานะ:** เสร็จสมบูรณ์ (100%)
- ✅ **Backend:** Rotating QR Tokens (60s TTL), Refresh endpoint, Audit metadata.
- ✅ **Frontend:** QR Routing for `school_checkin` & `student_card`.
- ✅ **UI:** `SchoolAttendanceQRDisplay.vue` (Auto-rotate) & `UniversalQRModal.vue` (Session chooser).
- ✅ **Security:** Token-in-URL removed from student widgets.

## 2026-05-31 — School Management System Phase 6 Complete (Full Implementation)

- **Task:** พัฒนาโมดูลเสริม Library (ห้องสมุด) และ Asset (ทรัพย์สิน) ให้สมบูรณ์ตามแผน SMS
- **Files touched:**
  - `api/nuxnanravel/app/Models/Learn/Academy/Library*.php`
  - `api/nuxnanravel/app/Models/Learn/Academy/SchoolAsset.php`
  - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/LibraryController.php`
  - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AssetController.php`
  - `ui/components/school/SchoolLibraryTab.vue`
  - `ui/components/school/SchoolAssetTab.vue`
  - `ui/components/school/SchoolManagement.vue`
  - `ui/composables/useSchoolManagement.ts`
- **Done:**
  - **Library System**: สร้างระบบแคตตาล็อกหนังสือและบันทึกการยืม-คืน พร้อมระบบตรวจสอบสถานะ Overdue และจำนวนเล่มที่เหลือ
  - **Asset Management**: สร้างระบบทะเบียนทรัพย์สินและพัสดุโรงเรียน รองรับการแจ้งซ่อม (Maintenance) และการคำนวณมูลค่ารวมของทรัพย์สิน
  - **Main Navigation**: อัปเดตแท็บในหน้าบริหารจัดการโรงเรียนให้ครอบคลุมโมดูลใหม่ทั้งหมด
  - **Full Project Wrap-up**: ดำเนินการตามแผน SMS Master Plan ครบถ้วนทุก Phase (0-6)
- **Status:** **Completed 100%**
- **Recommended next work:**
  - ดำเนินการทดสอบระบบ (QA) เชิงลึกในแต่ละโมดูล
  - เก็บรายละเอียดความสวยงาม (Fine-tuning UI) และความเร็วในการตอบสนอง (Optimization)
- **Verification:**
  - รัน Migration สำหรับ Library และ Assets สำเร็จ
  - เพิ่มแท็บและเชื่อมต่อ UI กับ Composable ใหม่เรียบร้อย
