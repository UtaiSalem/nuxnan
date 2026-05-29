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

## User Analysis Input (อ่านบทวิเคราะห์)

> **Trigger:** เมื่อผู้ใช้บอกว่า "อ่านบทวิเคราะห์" → Claude อ่าน section นี้แล้ว:
> 1. วิเคราะห์และตรวจสอบความถูกต้อง
> 2. ปรับปรุงและเพิ่มเติมสิ่งที่ขาด
> 3. วางแผนขั้นตอนการทำงานที่ชัดเจน
> 4. บันทึกแผนลงใน "Work Plan" ด้านล่าง

<!-- วางบทวิเคราะห์ / ความต้องการ / ปัญหา / เป้าหมายที่นี่ -->

(ยังไม่มีบทวิเคราะห์ — วางข้อความที่นี่แล้วบอก "อ่านบทวิเคราะห์")

---

## Work Plan (แผนการทำงาน)

### Feature: Typing Game Settings — Responsive Fix

**สถานะ:** ✅ COMPLETED (2026-05-27)

---

### Feature: Typing Classroom Race — Bug Fixes & Polish

**สถานะ:** ✅ COMPLETED (2026-05-27) — commit `f389406e`

5 bugs แก้แล้ว: countdown view switch, Echo leave API, memory leak throttle, finalize กับคนออก, race condition ใน rank

---

### Feature: Exam Retake Flow — Phase 2 (Authorization Logic)

**สถานะ:** 📋 Ready to implement

**เป้าหมาย:** เมื่อ student ผ่าน remediation session ที่ผูกกับ quiz → อนุญาตให้ retake quiz ได้อีก 1 ครั้ง

#### ขั้นตอน Backend

| # | ไฟล์ | การเปลี่ยนแปลง | หมายเหตุ |
|---|---|---|---|
| 1 | `RemediationService.php` | `gradeEnrollment()`: เมื่อ `passed = true` และ `session->quiz_id ≠ null` → สร้าง/อัพเดท quiz retake grant record | ใช้ model ใหม่ `QuizRetakeGrant` หรือ field `quiz_retake_unlocked_at` ใน `quiz_enrollments` |
| 2 | Migration | เพิ่ม column `retake_unlocked_at` (nullable timestamp) ใน `quiz_enrollments` หรือสร้าง `quiz_retake_grants` table | ใช้ `quiz_enrollments` approach ก่อน — simpler, 1 retake per quiz |
| 3 | `QuizAttemptController` (หรือ quiz show endpoint) | ตรวจว่า `retake_unlocked_at` มีค่าและ attempt ยังไม่ถูกใช้ → return `can_retake: true` ใน response | ใช้ `retake_used_at` เป็น flag ป้องกัน double retake |
| 4 | `QuizAttemptController::store()` | ถ้า student attempt เกิน max_attempts แต่ `can_retake = true` → อนุญาต และ mark `retake_used_at = now()` | ต้องใช้ DB lock เพื่อกัน race condition เหมือน race rank |
| 5 | Feature test | ผ่าน remediation → can_retake true; ใช้ retake แล้ว can_retake false; ไม่ผ่าน remediation → ยังเข้าไม่ได้ | ไฟล์: `tests/Feature/ExamRetakePhase2Test.php` (ใหม่) |

#### ขั้นตอน Frontend

| # | ไฟล์ | การเปลี่ยนแปลง |
|---|---|---|
| 6 | Quiz detail page / `ExamEligibilityPanel.vue` | ถ้า `can_retake = true` → แสดง state "✅ คุณผ่าน remediation — กดเพื่อเริ่มสอบ" พร้อมปุ่มเปิดได้ |
| 7 | Quiz attempt form | ส่ง request ตามปกติ — backend จัดการ unlock; หลัง submit ที่สำเร็จ refresh eligibility state |
| 8 | Quiz detail (หลัง retake ใช้แล้ว) | ถ้า `can_retake = false` และ `retake_used = true` → แสดง "คุณใช้สิทธิ์ retake แล้ว" |

#### Verification Plan

| ขั้น | Test | ผ่านเมื่อ |
|---|---|---|
| 1 | `php artisan migrate` | ไม่มี error |
| 2 | Feature test — pass remediation | `can_retake: true` ใน quiz response |
| 3 | Feature test — use retake | attempt สำเร็จ; `retake_used_at` ถูก set; attempt อีกครั้งถูก block |
| 4 | Feature test — fail remediation | `can_retake` ยัง `false` |
| 5 | Race condition test (2 requests พร้อมกัน) | ได้ attempt เดียว ไม่ duplicate |
| 6 | Browser smoke — quiz detail | panel แสดง "ผ่าน remediation" state ถูกต้อง |
| 7 | `npm run build` | ไม่มี error |

#### ข้อสมมติที่ใช้ (Decisions)
- 1 remediation session → 1 quiz retake attempt (granularity 1:1)
- Retake limit: 1 ครั้ง (ใช้แล้วหมดสิทธิ์ ต้อง enroll remediation ใหม่ถ้าจะ retake อีก)
- Implementation path: เพิ่ม columns ใน `quiz_enrollments` (simple) ไม่ใช้ table ใหม่ก่อน
- `max_attempts` field ของ quiz ไม่เปลี่ยน — retake bypass ผ่าน separate flag เพื่อ auditability

---

## Current Snapshot

- Date: 2026-05-27
- Branch: main
- Repository: `C:\wamp64\www\nuxnan`
- Frontend: `ui/` Nuxt/Vue/TypeScript/Pinia/Tailwind/PrimeVue
- Backend: `api/nuxnanravel/` Laravel/PHP/JWT/MySQL/Reverb
- Current focus: **Exam Retake Flow — Phase 2 (Authorization Logic)**
- Pending commit: ไม่มี — งานล่าสุดทุกอย่าง committed ใน main

## Active Work

| Scope | Owner | Status | Files | Notes |
| --- | --- | --- | --- | --- |
| Typing Classroom Race — Bug fixes & polish | — | ✅ Done (`f389406e`) | race.vue, useClassroomRace.ts, TypingRaceController.php | 5 bugs แก้แล้ว |
| Exam retake flow — Phase 2 (Auth Logic) | — | 📋 Ready to implement | `RemediationService.php`, quiz controller, `quiz_enrollments` migration, `ExamEligibilityPanel.vue` | แผนละเอียดอยู่ใน Work Plan ด้านบน |

## Coordination Board

| Claim ID | Owner | Scope | Files or folders | Status | Handoff note |
| --- | --- | --- | --- | --- | --- |

## Decisions And Assumptions

- `AGENTS.md` is the tool-agnostic root entry point for all AI agents.
- `.agents/latest-analysis.md` is the live analysis and coordination board.
- `.agents/worklog.md` remains the cross-session handoff log.
- `CLAUDE.md` remains Claude-specific historical/project guidance.

## Open Questions

1. **Quiz Attempt Limit**: ตอนนี้ quiz มี `max_attempts` field ไหม? ถ้ามีแล้ว — remediation ควร add 1 attempt หรือ reset counter เลย? → **ตัดสินใจ (Phase 2 plan):** ไม่แตะ `max_attempts`; ใช้ separate `retake_unlocked_at` / `retake_used_at` flag แทน เพื่อ auditability
2. **Remediation → Quiz link granularity**: 1 remediation session เชื่อมได้ 1 quiz หรือหลาย quiz? → **ตัดสินใจ:** 1 ต่อ 1 (Phase 1 migration เพิ่ม `quiz_id` ใน sessions แล้ว)
3. ~~**Pending commit**: ควร commit งานที่สะสมไว้ก่อนเริ่ม Phase 1 เพื่อ isolate changes~~ → **ปิด:** งานทั้งหมด committed แล้ว
4. **`quiz_enrollments` vs ตาราง `quiz_retake_grants` ใหม่**: Phase 2 plan ใช้ `quiz_enrollments` เพิ่ม 2 columns — ถ้า scope retake ขยายในอนาคต (เช่น หลาย retake, tracking per-grant) ควรสร้าง `quiz_retake_grants` table แยก

## Analysis Timeline

### 2026-05-24 - Lesson order gap / drag-and-drop analysis
- User reported a course with only 3 lessons showing a lesson badge as "บทที่ 4"; follow-up concern was whether drag-and-drop reorder or access/draft conditions could make frontend index/display inaccurate.
- Read-only DB check for `course_id = 21` found exactly 3 lesson rows with orders `1, 2, 4`; the first row is `publication_status = draft`, and the visible published lesson with id 44 has `order = 4`.
- Code inspection found `LessonOrderWidget.vue` maps the current draggable list to `order: index + 1`, so a successful save with all course lessons would normalize the 3 rows to `1, 2, 3`.
- Backend `CourseLessonController::reorder()` only verifies incoming IDs belong to the course; it does not require the incoming payload to contain every lesson in the course, nor does it renumber untouched lessons. `index()` also returns paginated lessons, so reorder can be unsafe for courses beyond the current page.
- Existing `CourseLessonReorderTest.php` covers a complete 3-lesson reorder and authorization, but not incomplete payloads, pagination, draft/published mixes, or gap repair after delete/create.
- Likely root cause for the current course: stale/gapped `lessons.order` from a previous delete/create or unsaved/failed reorder, not the points access type itself. Draft filtering can make the displayed sequence diverge further for students.
- Recommended fix direction: backend-owned order normalization for full course lessons, explicit full-list reorder endpoint/input, `max(order)+1` default for new lessons, and separate `display_order` from raw `order` for student-visible numbering.

### 2026-05-24 - Lesson card admin badge overlap planning
- User reported the lesson publication status badge on `/Learn/Courses/21/lessons` is visually hidden behind the edit/delete admin buttons.
- Read-only inspection found the likely source in `ui/components/learn/course/lesson/LessonPost.vue`: left badge stack uses `absolute top-4 left-4 flex flex-wrap`, access badge uses `absolute top-4 right-4`, and admin actions also use `absolute top-4 right-4`, so header overlays can collide on narrow card widths or with multiple badges.
- Intended fix scope: frontend-only, mainly `LessonPost.vue`; optionally add a tiny helper computed for admin/action reserved spacing if the template becomes hard to read.
- Planned UX direction: restructure the cover overlay into a responsive top row with a left badge group and a right action group, reserve horizontal space for actions, move access badge into the same badge group or a second non-overlapping row, and keep mobile wrapping below the action buttons.
- Verification plan: run a focused frontend check/build if practical, then browser-smoke `/Learn/Courses/21/lessons` as course admin at desktop and mobile widths to confirm badges and edit/delete buttons no longer overlap.
- Risk: there are existing uncommitted lesson access changes in the same `LessonPost.vue`, so implementation must preserve those changes and avoid broad card redesign.

### 2026-05-24 - Plan review and improvement (Exam Retake Flow)
- อ่านโค้ดจริงใน `RemediationService.php` และ `RemediationController.php` พบว่า grade update flow สมบูรณ์แล้ว (`gradeEnrollment` อัพเดท `final_grade`, `completion_status`, เรียก `unlockByRemediation`)
- ช่องว่างจริงคือ: ไม่มี `quiz_id` ใน `course_remediation_sessions` → ไม่รู้ว่า session เพื่อ retake quiz ไหน; quiz controller ไม่มีตรรกะ "ผ่าน remediation → อนุญาต attempt เพิ่ม"
- ปรับปรุงแผนจาก "TODO막연하게" เป็น 6 ขั้นตอนที่ชัดเจน (Phase 1: migration+backend, Phase 2: frontend)
- เพิ่ม Open Questions 3 ข้อ (quiz attempt model, granularity, commit timing)
- อัพเดท Active Work table ให้แสดง 2 phases ที่รอ implement

### 2026-05-24 - Lesson access status planning
- User asked for a plan to support international-style lesson visibility/access states: free, paid by points, paid by wallet/cash, and draft/hidden from students.
- Read-only inspection found existing lesson fields: `lessons.status` enum `0/1`, `point_tuition_fee`, `order`, `min_read`; `CourseLessonController` currently deducts points in `show()` without persistent per-user lesson purchase/access records.
- Relevant implementation areas: `lessons` migration/model/resource, `CourseLessonController`, `LessonForm.vue`, `LessonPost.vue`, course lesson list/detail pages, `course.ts`, `PointsService`, `WalletService`, and focused feature tests.
- Planning decision: separate publication status from access policy, add persistent lesson access/purchase records, return safe list payloads for students, and require an explicit unlock/purchase action before protected content is returned.
- Verification plan when implemented: feature tests for draft hiding, admin visibility, free access, point unlock idempotency, wallet unlock, and frontend build/checks.

### 2026-05-24 - Remediation & Unified Eligibility (DONE)
- Fixed route mismatch in `remediation.vue` (Backend uses `/api/courses/{course}/remediation`).
- Implemented `bulkEnroll` in `RemediationController` for admins.
- Created `ExamEligibilityPanel.vue` to unify unlock channels.
- Integrated panel into quiz detail page.

### 2026-05-24 - Plan review and status correction
- ตรวจสอบโค้ดจริงพบว่า Course Info Accordion Bug Fix (5 ขั้น) เสร็จสมบูรณ์แล้ว แต่ worklog ยังแสดงว่าค้างอยู่
- อัพเดท worklog.md: ย้าย Course Info Accordion Bug Fix ไป Done Today, ลบ Compact Lesson Widget Polish ออกจาก TODO
- อัพเดท latest-analysis.md: Work Plan ขั้นที่ 1-5 ทำเครื่องหมาย ✅ COMPLETED, Current Snapshot ชี้ไปที่งานถัดไปคือ Exam retake flow
- งานที่เหลือจริงๆ: Exam eligibility / retake unlock flow (วางแผนไว้ 2026-05-24, ยังไม่ implement)
- Pending: commit งานที่สะสมไว้ (หลาย modified/untracked files ใน git status)

### 2026-05-24 - Multi-channel exam access restoration plan
- User confirmed they want multiple exam-right restoration channels so students/users can choose one convenient option.
- Product direction: keep multiple channels, but present them through one unified "restore exam access" panel with option cards, eligibility hints, and a recommended/default path.
- Recommended channels to formalize: instant self unlock, points unlock, lesson-reading unlock, appeal/request review, teacher/admin unlock, and remediation/retake enrollment.
- Implementation plan should avoid separate ad hoc flows; use one API status payload to return available channels, pending requests, required actions, and completed unlock state.
- Keep auditability: every successful channel writes `exam_eligibility_overrides` plus `eligibility_audit_logs`.
- Next implementation scope: align frontend option panel, normalize channel labels/statuses, and fix remediation frontend/backend route mismatch before exposing retake enrollment as a student-facing channel.

### 2026-05-24 - Exam eligibility / retake unlock flow analysis
- User asked to analyze and simplify the process/channels for unlocking rights to take or retake exams.
- Current backend channels: attendance eligibility gate in `AttendanceEligibilityService`; student unlock via self, points, reading, appeal; teacher direct/bulk unlock; remediation sessions/enrollments after failed grades.
- Current frontend entry points: quiz detail/attempt pages enforce the gate; `MyProgressDetails.vue` exposes student unlock options; settings/gradebook eligibility expose admin settings and direct/bulk unlock.
- Main UX risk: too many separate choices for students; recommended simplification is a single "restore exam access" card with one recommended next action, with teacher one-click/bulk unlock as the fallback.
- Technical risk found: gradebook remediation page appears to call legacy/non-matching endpoints and field names (`/remediation/sessions`, `name`, `scheduled_at`, `resit`) while backend routes use `/api/courses/{course}/remediation`, `title`, `start_at`, `exam_retake`.
- Verification so far: read-only inspection only; no tests run and no functional code changed.

### 2026-05-24 - Admin progress widget and paid lesson unlock planning
- User asked to plan improvements: hide student learning progress widgets for admins, and make paid lessons show title/status first while content remains locked until the student confirms payment and the system successfully deducts points or wallet money.
- Read-only inspection found `CoursePageShell.vue` shows `CourseProgressWidget`, `CourseLessonProgressWidget`, `CourseAssignmentProgressWidget`, and `CourseQuizProgressWidget` whenever `courseMemberOfAuth` exists, so an admin who is also a course member can see student-style progress cards.
- Existing lesson access work is partially implemented: `CourseLessonController::unlock()`, `LessonAccessService`, `LessonResource`, `LessonPost.vue`, and `lesson_accesses` already support locked payloads and an explicit unlock endpoint. Remaining UX/contract work is to present metadata safely, improve confirm/insufficient-balance messaging, refresh balances/access after success, and ensure locked list/detail views never expose content/topics/assignments/questions before unlock.
- Intended files for implementation: `ui/components/learn/course/v2/CoursePageShell.vue`, `ui/components/learn/course/lesson/LessonPost.vue`, `ui/pages/Learn/Courses/[id]/lessons.vue`, `ui/pages/Learn/Courses/[id]/lessons/[lessonId].vue`, `ui/stores/course.ts`, `api/nuxnanravel/app/Http/Resources/Learn/Course/lessons/LessonResource.php`, `api/nuxnanravel/app/Http/Controllers/Api/Learn/Course/lessons/CourseLessonController.php`, `api/nuxnanravel/app/Services/LessonAccessService.php`, and focused Laravel/Nuxt tests.
- Verification plan: frontend build, backend focused tests for locked resource/unlock success/insufficient points or wallet/idempotency/admin bypass, and browser smoke for admin course pages plus student paid-lesson unlock flow.
- Clarified product rule: point-priced lessons must not reveal lesson content before purchase. Student may see safe metadata (title, description/summary if allowed, status, price, locked state), then must click read/unlock, see a confirmation warning about point deduction, and only after a successful points deduction should full lesson content be returned/displayed.
- New revenue rule from user: points deducted from paid lesson reads should be credited into a course accumulated-points balance. Course owner/admin can later use that course balance either by transferring points into their own user account or creating student claim/distribution campaigns.
- Current points system is user-centric (`users.pp`, `points_transactions`, `PointsService::spend/earn/transfer`) and does not have a course-level points wallet/ledger. Recommended design is a separate course ledger (`course_point_accounts` + `course_point_transactions`) linked to lesson unlocks, not direct immediate credit to owner `users.pp`, to keep course revenue auditable and prevent double spending.

### 2026-05-24 - Lesson completion reward coupon/quota planning
- User asked to analyze a motivation system where teachers/admins set reward points per lesson completion, funded from the course accumulated-points balance, with first-come quota/coupon behavior so late readers receive no reward after the quota is depleted.
- Read-only inspection found lesson completion currently runs through `LessonProgressController::complete()` and `toggleComplete()`, with a unique `lesson_progress(user_id, lesson_id)` record. The UI toggle in `LessonInteractionTabs.vue` can mark complete immediately, so reward logic must be backend-controlled and idempotent.
- Existing course point account/campaign scaffolding is present (`CoursePointAccountService`, `CoursePointAccount`, `CoursePointCampaign`, `CoursePointCampaignClaim`, `CoursePointTransaction`, routes under `/courses/{course}/points`). However the migration filenames are dated `2026_05_25_*` while current project date is 2026-05-24, so migration ordering/status should be checked before implementation.
- Current campaign creation checks balance when `max_claims` is set but does not reserve/deduct the budget at creation time. This can over-promise available course points across multiple active campaigns. Recommended fix: add reserved balance or explicit campaign budget reservation before exposing lesson rewards.
- Product decision direction: lesson reward campaigns should be tied to a lesson, require enrollment + access to lesson + first completed status + not previously claimed, optionally require minimum read time (`min_read`) before claim, and should award only while active quota and reserved budget remain.
- Intended files/modules: `LessonProgressController`, `LessonResource`, `LessonForm.vue`, `LessonPost.vue`, `LessonInteractionTabs.vue`, `CoursePointAccountService`, course point campaign models/migrations/controllers, and focused backend tests for reward budget, quota, duplicate claim, race conditions, and insufficient course points.
- Verification plan: feature tests for first N completed students receiving rewards, N+1 no reward, repeated toggle no duplicate reward, concurrent claims do not exceed quota/budget, paid locked lesson cannot claim reward before unlock, and frontend build/browser smoke for reward badges and admin setup.

### 2026-05-24 - Frontend plan for lesson reward quotas
- User requested a frontend-only planning pass for the new course accumulated-points and lesson-completion reward feature.
- Existing frontend touchpoints: `LessonForm.vue` already owns lesson access/price fields, `LessonPost.vue` owns locked lesson display and unlock CTA, `LessonInteractionTabs.vue` owns lesson progress completion, course `settings.vue` owns admin settings, and `stores/course.ts` handles course/lesson state.
- Add a focused course-points frontend API layer/composable for account, transactions, campaigns, create/update/pause/end campaign, and claim/refresh flows. Expected payloads should include course balance, reserved/available balance, campaign quota/remaining count, auth-user claim status, and `reward_result` from lesson progress completion.
- Admin/course-admin UX plan: add a course settings section for accumulated course points, available/reserved balance, recent transactions, withdrawal action, and active reward campaigns; add lesson reward controls in `LessonForm.vue` with enable toggle, points per claim, quota, schedule, budget preview, and validation against available course balance.
- Student UX plan: show reward teaser badges on lesson list/detail with points and remaining quota; keep paid locked lesson content hidden while still showing safe metadata/reward teaser; after unlock allow reading; after completion show awarded/depleted/already-claimed states and refresh user points plus lesson reward status.
- Guardrails: frontend validation is advisory only; backend remains authoritative for balance reservation, duplicate claims, access/enrollment checks, minimum read time, and depleted quotas. Frontend should map backend errors such as insufficient course balance, quota depleted, already claimed, locked lesson, and not enrolled into clear Thai messages.
- Candidate components: `CoursePointBalanceCard`, `CoursePointTransactionList`, `LessonRewardSettings`, `LessonRewardBadge`, `LessonRewardCompletionNotice`, and optionally `CoursePointCampaignList`, reusing existing course settings/card patterns without adding nested card-heavy layouts.
- Frontend verification plan: run `npm run build`; smoke-test admin settings balance, lesson reward config budget preview, paid locked lesson reveal flow, completion award before quota exhaustion, completion after quota exhaustion, duplicate completion no-award, and mobile layouts for lesson cards/detail.

### 2026-05-25 - Typing Game Settings responsive planning
- User shared a narrow mobile screenshot where `Game Settings` option buttons wrap individual words/letters vertically and the settings card feels cramped.
- Read-only inspection found the source in `ui/pages/Play/Games/typing/index.vue`: settings live in the right column of a `lg:grid-cols-3` layout; language buttons use `flex gap-2` with `flex-1`, while difficulty buttons use `grid grid-cols-2 sm:grid-cols-5`, which becomes five tiny columns at small widths once the settings card sits in a narrow sidebar.
- Recommended scope is frontend-only: refactor the settings controls in `typing/index.vue` with responsive grid tracks, stable min widths, non-breaking labels, and smaller card padding on mobile; no backend or store contract changes needed.
- UX direction: on mobile make the settings card full width below mode selection, use language grid `grid-cols-1 xs:grid-cols-3` or `auto-fit minmax`, difficulty `grid-cols-2` until enough width for 3/5 columns, keep labels readable with `whitespace-nowrap`/`break-words` decisions, and make the CTA sticky only if later testing shows long scrolling.
- Verification plan: run frontend build/check, then browser smoke `/Play/Games/typing` at about 360px, 390px, 768px, and desktop widths to confirm no clipped text, no letter-by-letter wrapping, and mode selection/settings column order remains natural.
- Risk: current worktree has many existing modified/untracked game and typing files; implementation should be limited to `ui/pages/Play/Games/typing/index.vue` unless a shared layout issue is discovered.

### 2026-05-25 - Typing Game Settings responsive plan review & correction (อ่านบทวิเคราะห์)
- อ่าน code จริงใน `ui/pages/Play/Games/typing/index.vue` ยืนยัน: outer layout `grid lg:grid-cols-3 gap-12` (line 75), card `p-8 space-y-8` (line 114), language `flex gap-2 flex-1 py-3 px-4` (lines 127-138), difficulty `grid grid-cols-2 sm:grid-cols-5` (line 144), CTA `py-5 text-xl` (lines 162-163).
- **Critical breakpoint conflict (จุดบอดของแผนเดิม):** `sm:grid-cols-5` เปิดที่ 640px และไม่มี override กลับเมื่อ layout เปลี่ยนเป็น sidebar ที่ `lg` (1024px). ที่ lg: outer gap-12 × 2 = 96px, settings column = (1024 - 16px padding - 96px gaps) / 3 ≈ 304px. กับ 5 cols + gap-2: per-col = (304 - 32px) / 5 = 54px — "Beginner" ล้นแน่นอน. แก้ด้วย `lg:grid-cols-2 xl:grid-cols-5`.
- **Additional gap issue (จุดบอดที่ 2):** `gap-12` (48px) ระหว่าง col บีบ settings sidebar ที่ lg. เพิ่ม `lg:gap-8` ได้ ~21px เพิ่ม เพียงพอช่วย. แผนเดิมไม่ได้กล่าวถึงเลย.
- **Language fix revision:** แผนเดิมแนะนำ `xs:grid-cols-3` แต่ `xs` ไม่ใช่ default Tailwind breakpoint. ใช้ `grid grid-cols-3` เลยดีกว่า (3 cols ทุก viewport — ที่ mobile เต็มความกว้างก็ใช้ได้, ที่ sidebar ≈ 304px / 3 = 101px ต่อ col ก็พอ).
- **Revised implementation (5 ขั้น):** (1) outer gap: `gap-6 lg:gap-8` (2) card: `p-5 sm:p-6 lg:p-7`, inner `space-y-6 lg:space-y-7` (3) language: `grid grid-cols-3 gap-2` + `whitespace-nowrap overflow-hidden` บน label (4) difficulty: `grid-cols-2 sm:grid-cols-5 lg:grid-cols-2 xl:grid-cols-5` + `min-h-[60px]`, name `whitespace-nowrap text-xs sm:text-sm`, desc `text-[9px] sm:text-[10px] break-words` (5) CTA: `py-4 sm:py-5 text-base sm:text-xl`.
- Verification breakpoints: 360px (2-col diff, 3-col lang, full-width), 640px (5-col diff ok), 1024px/lg (back to 2-col diff in sidebar, 3-col lang), 1280px/xl (5-col diff expands again), 1440px+ desktop (comfortable).
- Scope confirmed: `ui/pages/Play/Games/typing/index.vue` only. No store/backend changes needed.

### 2026-05-27 - Mental Math Training/Challenge implementation plan
- User provided a phased plan to add Mental Math into the games navigation, restyle it as Cartoon Brutalist, refactor gameplay into Training + Challenge, and save scores through `/api/game/scores`.
- Read-only inspection found `ui/pages/MentalMath.vue` is still a prototype: it uses multiple intervals and `updateRandomNumbers()` recursively retries without a hard bound. It is also outside the `/Play/Games` route structure, so it is not discoverable from the games menu.
- Existing navigation targets to update: `ui/pages/Play/Games/index.vue` games array and `ui/layouts/main.vue` `gamesSubmenu`. Recommended new route is `/play/games/mental-math-game`.
- Existing route wrapper pattern to copy: `ui/pages/Play/Games/cross-math-game.vue` uses `definePageMeta({ layout: false })`, wraps content in `<NuxtLayout name="main">`, and renders the game content inside the main app layout.
- Existing leaderboard contract is available through `api/nuxnanravel/routes/play/game.php` and `GameScoreController`: GET `/api/game/scores?game_type=...`, POST `/api/game/scores` with `game_type`, `session_id`, `score`, `level`, `time_spent`, `player_name`, and optional `metadata`.
- Implementation scope: primarily frontend (`ui/pages/MentalMath.vue`, `ui/pages/Play/Games/index.vue`, `ui/layouts/main.vue`, new `ui/pages/Play/Games/mental-math-game.vue`). Optional backend hardening: fix `GameScoreController@index` so `user_best_score` orders by highest score before returning a user's best score.
- Gameplay decision: follow the latest spec, using time-limited level sessions rather than fixed-question rounds. Challenge lets the user choose one level and plays only that level. Training uses the same answering loop and also saves/shows leaderboard, likely with metadata `mode: training`.
- Planned state model: `mode`, `gameState`, `currentLevel`, `timeLeft`, `score`, `correctCount`, `wrongCount`, `totalQuestions`, `currentQuestion`, `answerInput`, `lastJudgement`, `gameSessionId`, and `playedSeconds`.
- Planned level config: levels 1-10 with number count/range/time from the user spec: 2 numbers 1-4 25s through 6 numbers 1-15 90s.
- Planned safe loop: replace prototype recursion/intervals with `generateQuestion(level)`, one countdown interval, `submitAnswer()` validation, immediate scoring `100 + currentLevel * 10` on correct answers, 300-400ms feedback, next question generation, and `onBeforeUnmount()` interval cleanup.
- Planned UI: Cartoon Brutalist style across buttons, input, question card, timer, result card, and leaderboard modal using thick black borders, hard black shadow, white/yellow/green/red surfaces, large black number type, and no heavy gradients.
- Verification plan: `/play/games` -> Mental Math navigation smoke, Challenge level select -> timed result -> score save/leaderboard refresh, Training save/leaderboard, mobile input/button layout, interval cleanup on navigation, and frontend build from `ui/`.

### 2026-05-29 - Course feed admin delete/copy plan review
- User asked to review a proposed plan for `/Learn/Courses/24/feeds`, where admin deleting a member post appears to create a copy instead.
- Read-only inspection confirmed backend routes are distinct: `POST /courses/{course}/posts` creates, `PATCH /courses/{course}/posts/{course_post}` updates, and `DELETE /courses/{course}/posts/{course_post}` deletes. `CoursePostController::destroy()` performs real deletion with owner/admin authorization.
- Strongest likely bug is in `CourseEditPostModal.vue`: edit submit uses `api.post(...?_method=PATCH, formData)` with method override in the query string. The local FormData convention elsewhere in the repo appends `_method` to the body before posting to the resource URL.
- Weaker hypothesis: dropdown click propagation from delete to edit is not strongly supported by the current DOM because the buttons are siblings, but adding `.stop`/explicit action handlers and an `isDeleting` guard in `CourseFeedPost.vue` is still useful hardening.
- Recommended scope: frontend-only first. Change edit update to append `_method=PATCH` in FormData body, add delete in-flight guard and disabled state, use `api.delete`/`api.del` or keep `api.call` with explicit DELETE, stop propagation on menu actions, and refresh/remove the post only after delete success.
- Verification plan: inspect browser Network for edit as POST with FormData `_method=PATCH` to `/posts/{id}` and delete as DELETE to `/posts/{id}`; manually test admin deleting a member post, editing a member post, and double-clicking delete; run focused frontend build/check if practical.

### 2026-05-27 - Typing Classroom Race improvement plan (initial, pre-code-read)
- User asked to plan improvements for `http://localhost:3000/Play/Games/typing/race` and shared Vue warning: `Component inside <Transition> renders non-element root node that cannot be animated`.
- Read-only inspection found the race page at `ui/pages/Play/Games/typing/race.vue`; it uses `definePageMeta({ layout: false })` and returns `<NuxtLayout name="main">` as the template root, matching the warning stack through Nuxt page transitions. Related typing pages (`typing/index.vue`, `typing/play.vue`) use the same pattern and may emit the same warning during navigation.
- Browser smoke opened the race URL successfully and rendered `Classroom Race`; console capture did not reproduce the warning on direct load, so verification should include navigation from `/Play/Games/typing` to `/Play/Games/typing/race`.
- Frontend improvement scope: wrap page templates in a stable single HTML root around `<NuxtLayout>` or switch to `definePageMeta({ layout: 'main' })` and remove manual `<NuxtLayout>` usage consistently for typing pages; then polish race responsive layout, button states, loading/error messages, and host/join flows.
- Runtime/race-flow risks found: `useClassroomRace.ts` does not surface API errors to UI, does not reset `joinCode`/loading state, leaves participants as `left` without server leave endpoint, starts local countdown from broadcast only, and depends on Echo presence whispers for progress. Backend only finalizes when all participants submit, so a left/disconnected racer can leave a room stuck in `racing`.
- Backend improvement candidates: add room leave/heartbeat or timeout handling, ensure submit/finalize ignores left participants, return normalized participant DTOs, add validation messages for full/not-found/started rooms, and consider host-only max player/config controls.
- Intended files: `ui/pages/Play/Games/typing/race.vue`, `ui/pages/Play/Games/typing/index.vue`, `ui/pages/Play/Games/typing/play.vue`, `ui/composables/useClassroomRace.ts`, `ui/components/games/typing/ui/RaceTrack.vue`, `api/nuxnanravel/app/Http/Controllers/Api/Play/Typing/TypingRaceController.php`, `api/nuxnanravel/routes/channels.php`, `api/nuxnanravel/app/Events/TypingRaceStarted.php`, and `TypingRaceFinished.php`.
- Verification plan: `npm run build`; browser smoke direct URL and navigation `/typing` -> `/typing/race`; create room and invalid join code; two-user/manual or mocked Echo test for lobby/start/progress/result; Laravel feature tests for create/join/start/submit/finalize/full room/leave or timeout behavior.

### 2026-05-27 - Plan sync & Phase 2 improvement (อ่านแผนและปรับปรุง)
- ตรวจสอบ `latest-analysis.md` + `worklog.md` กับ `git log` พบ 4 จุด outdated: Current Snapshot date/focus, Active Work table, Work Plan Phase 2 ขาด action steps, Open Questions ยังไม่ปิด
- อัพเดท Current Snapshot → 2026-05-27, focus = Exam Retake Phase 2, Pending commit = none
- อัพเดท Active Work → Race เป็น ✅ Done (commit `f389406e`); Phase 2 = Ready to implement
- เพิ่ม Work Plan Phase 2 ละเอียด: 5 backend steps + 3 frontend steps + verification table + decisions (1:1 granularity, retake flag แทนแตะ max_attempts, DB lock กัน race condition)
- ปิด Open Questions Q2, Q3; เพิ่ม Q4 เรื่อง `quiz_retake_grants` table ใน future scope
- อัพเดท worklog.md: ย้าย Race bugs → Done, ปิด Pending Commit, TODO เหลือแค่ Phase 2

### 2026-05-27 - Typing Classroom Race — deep code review & improved plan
- อ่านโค้ดจริงทุกไฟล์: `race.vue`, `useClassroomRace.ts`, `TypingRaceController.php`, `channels.php`, `RaceTrack.vue` ยืนยันและพบ bug เพิ่มจากแผนเดิม
- **Bug 1 (Critical — countdown ไม่แสดง):** `race.vue` template เช็ค `v-if="view === 'home'"` → `v-else-if="view === 'lobby'"` → `v-else-if="raceStatus === 'countdown'"` ตามลำดับ เมื่อ `raceStatus` เปลี่ยนเป็น `'countdown'` ค่า `view` ยังคงเป็น `'lobby'` อยู่ (watch ไม่ได้ set view ที่ countdown) ทำให้ branch `v-else-if="view === 'lobby'"` จับก่อนและ countdown panel ไม่มีวันแสดงขึ้นมา แก้: เพิ่ม `view.value = 'countdown'` ใน watch เมื่อ `s === 'countdown'`
- **Bug 2 (Echo API ผิด):** `useClassroomRace.ts:186` เรียก `channel.leave(`race.${room.value?.room_code}`)` แต่ `channel` คือ channel object ที่ได้จาก `.join()` ซึ่งไม่มี method `.leave()` การ leave ที่ถูกต้องคือ `($echo as any).leave('race.CODE')` โดยเรียกผ่าน Echo instance ไม่ใช่ channel object ตอนนี้ leaveRoom() ไม่ได้ unsubscribe จริง
- **Bug 3 (Memory leak):** `useClassroomRace.ts:39` มี `let progressThrottle` แต่ `leaveRoom()` ไม่ได้ `clearTimeout(progressThrottle)` ถ้า user ออกระหว่างที่ throttle ยังค้างอยู่ จะส่ง whisper ไปยัง channel ที่ถูก leave แล้ว
- **Bug 4 (Backend finalize ค้าง — ยืนยันจากโค้ด):** `TypingRaceController.php:204` นับ `$totalParticipants = $room->participants()->count()` รวม status `'left'` ด้วย แต่ `$totalFinished` นับเฉพาะ `'finished'` ทำให้ถ้ามีคนออก finalize condition จะไม่ถึง 100% ห้องค้างถาวร แก้: เปลี่ยนเป็น `->where('status', '!=', 'left')->count()`
- **Bug 5 (Race condition ใน rank):** `TypingRaceController.php:190` อ่าน `$finishedCount` ก่อน แล้วค่อย update status ที่ line 196 ถ้า 2 คน submit พร้อมกัน จะอ่าน count เดียวกันและได้ rank เดียวกัน แก้: ห่อด้วย `DB::transaction()` + `lockForUpdate()` บน participant row
- **ยืนยันว่า OK:** realtime contract ใช้ field สอดคล้องกัน — `channels.php` return `{id, name, avatar}`, frontend ใช้ `user.id` → `upsertParticipant(user.id, ...)` ถูก; `RaceTrack.vue` filter `status !== 'left'` ถูกแล้ว; `finalizeRace()` ใช้ `profile_photo_url ?? avatar` ตรงกับ channels.php
- **ยืนยันว่า missing:** ไม่มี backend leave endpoint → presence `.leaving()` callback แค่ mark local state ไม่ได้ update DB; participant ใน DB จะค้าง status `'racing'` ตลอดแม้ user จะออกไปแล้ว
