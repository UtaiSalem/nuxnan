# Work Log — nuxnan project

ไฟล์นี้ใช้สำหรับส่งต่อ context ระหว่างที่บ้านและที่ทำงาน
**กฎ: ก่อนออกจากแต่ละที่ → อัพเดทไฟล์นี้แล้ว `git push`**
**กฎ: มาถึงที่ใหม่ → `git pull` แล้วอ่านไฟล์นี้ก่อนเริ่มงาน**

---

## สถานะปัจจุบัน (2026-05-27)

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

- **In Progress:**
  - —

- **TODO:**
  - **Exam Retake Flow Phase 2 — Authorization Logic**
    - `RemediationService::gradeEnrollment()`: เมื่อ `passed` + มี `quiz_id` → unlock quiz retake attempt
    - Quiz Controller: return `can_retake: true` ถ้า student ผ่าน remediation ที่ผูกกับ quiz นี้
    - Frontend quiz page: แสดง "✅ ผ่านแล้ว เริ่มสอบได้เลย" state
  - **Typing Classroom Race — Polish & Bug Fixes**
    - Fix Vue `<Transition>` warning: wrap page template in stable root หรือ switch to `definePageMeta({ layout: 'main' })`
    - `useClassroomRace.ts`: surface API errors, reset join state, add leave endpoint
    - Backend: room leave/heartbeat/timeout, normalize participant DTOs, finalize ข้ามคนที่ออก

- **Pending Commit:**
  - `.agents/latest-analysis.md` (2026-05-27 Typing Race analysis entry)

---

## ประวัติการทำงาน (Timeline)

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
