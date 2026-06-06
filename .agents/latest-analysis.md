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

### 2026-06-06 — Course Completion / Grading verified gaps

แผนเดิม (Timeline 2026-06-06 Course Completion Plan Review) ถูกทิศ ยืนยันจากการอ่าน source:

- **Syntax error จริง**: `CourseCompletionController.php:375` มี `;` และ `}` เกินท้ายไฟล์ → `php -l` fail
- **Transcript sync risk จริง**: `CourseGradingService.php:309` ใช้ `Student::where('user_id')` อย่างเดียว ต้องเพิ่ม `academy_id` scope
- **`semester_id` ยังเป็น `null`**: `CourseGradingService.php:319`
- **`Semester` ยังไม่มี helper**: `Semester.php:13`

**Gap เพิ่มเติม (ใหม่)**:
- `CourseCompletionController::finalizeGrades()` (`:206`) อนุญาตเฉพาะ status `grading`
- แต่ `CourseGradingService` summary (`:474`) ระบุว่า finalize ได้ทั้ง `grading` และ `published`
- ผลกระทบ: workflow `grading → publish → student accept → finalize` จะถูก block หลัง publish

---

## Work Plan v2 (2026-06-06) — refined after source verification

### Severity legend
- 🔴 **Critical** (ระบบพัง / syntax fail / ข้อมูลสูญหาย)
- 🟠 **High** (Data integrity / สเตตัสค้าง / ข้อมูลไม่ตรงกัน)
- 🟡 **Medium** (UX / ข้อมูลรั่วไหล / ขาด Helper)
- 🔵 **Low** (Test coverage / UI Polish)

---

### Phase 1 — 🔴 Blocker Fixes
- **[CC-FIX-1] Syntax Error**: ลบ `; } }` ส่วนเกินท้ายไฟล์ `CourseCompletionController.php` (line 375-377)
- **[CC-FIX-2] Finalize Workflow (Bug A & B)**: 
  - แก้ guard ใน controller ให้ยอมรับสถานะ `published` (ปัจจุบันอนุญาตแค่ `grading` ทำให้กด finalize ไม่ได้หลังประกาศเกรด)
  - แก้ loop ใน `CourseGradingService::finalizeGrades` ให้รองรับสมาชิกที่ยังไม่ได้กดยอมรับ หรือปรับเงื่อนไขให้ครอบคลุม (ปัจจุบัน loop เฉพาะ `pending_acceptance` ทำให้หาก finalize ข้ามขั้นตอน transcript จะว่างเปล่า)

### Phase 2 — 🟠 Data Integrity & Sync
- **[CC-SYNC-1] Academy-scoped Student Lookup**: แก้ `syncToTranscripts` ให้ระบุ `academy_id` เมื่อค้นหา `Student` ป้องกันข้อมูลข้ามโรงเรียน
- **[CC-SYNC-2] Stale Transcript on Override**: เพิ่มการเรียก `syncToTranscripts()` ทันทีเมื่อมีการ `overrideGrade` หลังปิดเกรดแล้ว
- **[CC-SYNC-3] Reopen Logic**: จัดการถอนการประกาศเกรดใน Transcript (is_published = false) เมื่อมีการเรียก `reopenGrading`

### Phase 3 — 🟡 Privacy & Helpers
- **[CC-PRIV-1] Draft Grade Leak**: ตัดสถานะ `'grading'` ออกจาก `can_view` ใน `getMyGrade()` เพื่อไม่ให้นักเรียนเห็นเกรดร่างก่อนครูประกาศ
- **[CC-HELP-1] Semester Mapping**: พัฒนาวิธีระบุภาคเรียนให้รายวิชา (ดู fallback options)

---

### 🛠 Fallback Options สำหรับการออกแบบ

**ทางเลือกสำหรับ Step 3: Semester Mapping (ระบุเทอมให้วิชา)**
- *Option A*: เพิ่ม column `semester_id` ลงในตาราง `courses` (ชัดเจนที่สุด แต่ต้องทำ migration)
- *Option B*: Resolve runtime ผ่าน helper `Semester::getCurrentForCourse($course)` โดยอิงจาก `start_date` หรือ text fields (เสี่ยง mismatch)
- *Option C*: ใช้เทอมที่เป็น `is_current = true` ของ Academy ในขณะที่กดปิดเกรด (ง่าย แต่อาจผิดถ้าปิดเกรดล่าช้าข้ามเทอม)
- **แนะนำ: Option A** เพื่อความแม่นยำถาวร

**ทางเลือกสำหรับ Step 5: Transcript Sync (จัดการ Override/Reopen)**
- *Option A*: เรียก `syncToTranscripts` ตรงๆ ใน controller/service methods (ชัดเจน ไล่โค้ดง่าย)
- *Option B*: ใช้ Eloquent Model Observer บน `CourseMember` คอยดักจับการเปลี่ยนแปลง (สะอาดกว่าในแง่โครงสร้าง แต่อาจ debug ยาก)
- **แนะนำ: Option A** สำหรับ v2 เพื่อความรวดเร็วและตรวจสอบได้ง่าย

---

### 🧪 PHPUnit Regression Tests
1. `test_finalize_after_publish_succeeds`: ยืนยันว่า workflow `grading -> publish -> finalize` ทำงานได้จริง
2. `test_finalize_promotes_all_eligible_members`: ตรวจสอบ Bug B ว่าสมาชิกทุกคนได้รับเกรดสุดท้ายแม้ไม่ได้กด accept
3. `test_override_grade_updates_transcript`: ยืนยันว่าการแก้ไขเกรดหลังปิดวิชาส่งผลถึง Transcript ทันที
4. `test_student_cannot_view_draft_during_grading`: ป้องกันการรั่วไหลของเกรดร่าง

---

### 🌿 Commit & Branch Strategy
- **Branch**: `fix/course-completion-workflow`
- **Commits**: Atomic commits แยกตาม Step (e.g., `fix(backend): resolve syntax error`, `fix(grading): fix finalize loop condition`)

---

### ❓ Open Questions (ต้อง Confirm ก่อนเริ่ม)
1. **Semester Mapping**: ยืนยันให้เพิ่มฟิลด์ `semester_id` ในตาราง `courses` เลยหรือไม่? (Option A)
2. **Reopen Behavior**: เมื่อกด Reopen Grading ต้องการให้ Transcript (ตาราง `course_grades`) ซ่อนเกรดนั้นทันที (is_published = false) ใช่หรือไม่?
3. **Manual Acceptance**: หากนักเรียนไม่กด "ยอมรับเกรด" (Accept) ก่อนที่ครูจะกด Finalize ระบบควร Auto-accept ให้ทุกคนเลยใช่หรือไม่? (ปัจจุบัน Bug B ทำให้คนกลุ่มนี้ถูกข้ามไป)

---

---

## Work Plan v3 (2026-06-06) — Course Score Source-Of-Truth Refactor

> รวมข้อสรุปจาก Course Completion Plan Review รอบ 2 ทิศทางหลัก: **ย้าย source-of-truth ของคะแนนรายวิชาออกจาก `gradebook_assessments`/`gradebook_scores` มาที่ `CourseScoreService`** ที่รวบรวมจาก quiz/assignment/lesson question/external/bonus ที่มีอยู่จริง แล้วให้ `CourseGradingService` ใช้ค่าจากที่เดียวกัน

### Guiding principles
1. **คะแนนที่ครูเห็น = คะแนนที่นักเรียนได้รับจริงจากกิจกรรมการเรียน** ไม่ใช่ตัวเลขที่ครูพิมพ์ซ้ำในหน้า assessment
2. **field เดียว ความหมายเดียว** — `earned/max/percentage/grade_point/letter_grade` แยกขาดจากกัน
3. **service เดียวสำหรับคำนวณ** — ทุก controller ที่ทำให้คะแนนเปลี่ยน ต้องเรียกผ่าน `CourseScoreService::recompute()`
4. **เกรดที่ประกาศแล้วต้อง freeze** — ใช้ snapshot table ป้องกันคะแนนไหลตามหลัง publish
5. **ไม่ลบของเก่าทันที** — `gradebook_assessments` legacy mode ผ่าน flag ต่อ course

---

### Phase P0 — 🔴 Hotfix (ข้อมูลผิดอยู่จริงตอนนี้)

> เป้าหมาย: หยุดเลือดก่อน ทำใน branch เดียว แล้ว push เร็ว ไม่ refactor structure

#### P0.1 แก้ source ของคะแนนคำถามบทเรียนให้ตรง table
- **ไฟล์**: `api/nuxnanravel/app/Services/CourseScoreService.php:108-113`
- **ปัญหา**: ดึงจาก `user_answer_questions` ที่เป็น table ของ course quiz; flow บทเรียนเขียนลง `lesson_answer_questions` → คะแนนคำถามบทเรียนถูกนับเป็น ~0 ทุกราย
- **แก้**:
  - ลบ block `lessonTestScore` ปัจจุบัน
  - ใส่ query ใหม่:
    ```php
    $lessonTestScore = DB::table('lesson_answer_questions')
        ->where('user_id', $userId)
        ->whereIn('question_id', $lessonQuestionIds)
        ->where('is_correct', true)
        ->sum('points');
    ```
- **ทดสอบ**: สร้าง factory: lesson + 3 questions points 5/5/5, user ตอบถูก 2 ข้อ → `syncMemberAchievedScore` ต้องคืน 10

#### P0.2 รวม lesson question points เข้า `total_score`
- **ไฟล์**: `api/nuxnanravel/app/Services/CourseScoreService.php:38-56` (`calculateInternalTotalScore`)
- **ปัญหา**: numerator มี lesson question แต่ denominator ไม่มี → เปอร์เซ็นต์อาจเกิน 100
- **แก้**: เพิ่ม
  ```php
  $lessonQuestionTotal = DB::table('questions')
      ->whereIn('questionable_id', $lessonIds)
      ->where('questionable_type', 'App\\Models\\Lesson')
      ->sum(DB::raw('COALESCE(points, 1)'));
  return (int)($quizTotal + $assignmentTotal + $lessonAssignmentTotal + $lessonQuestionTotal);
  ```
- **ทดสอบ**: assert `course->total_score == sum(quizzes.total_score) + sum(assignments.points) + sum(questions.points fallback 1)`

#### P0.3 ให้ `syncMemberAchievedScore` trigger grade progress
- **ไฟล์**: `api/nuxnanravel/app/Services/CourseScoreService.php:120` (ท้าย method)
- **ปัญหา**: external sync trigger, internal sync ไม่ trigger → grade_progress เพี้ยน
- **แก้**: เพิ่ม `$this->updateMemberGradeProgress($member);` ก่อน return

#### P0.4 แก้ field semantics ใน `calculateMemberTotalScore`
- **ไฟล์**: `api/nuxnanravel/app/Services/CourseGradingService.php:125-164`
- **ปัญหา**: คืนเปอร์เซ็นต์ แต่ caller copy ลง `draft_total_score` ที่ semantic ไม่ชัด แล้ว `syncToTranscripts` เขียนลง `CourseGrade.total_score` (ตั้งใจให้เป็นคะแนนดิบ)
- **แก้ระยะสั้น** (ก่อน schema migration ใน P1): ให้ method นี้เรียก `CourseScoreService::calculateGradeData($member)` แล้วคืนค่า `score_percentage` พร้อมจดไว้ใน docblock ว่า "returns 0-100 percentage" ลบ path `gradebook_scores` ออกเป็น default ย้ายไป behind `$course->use_legacy_gradebook` (P2)
- **หมายเหตุ**: ใน P1 จะแยก field semantics จริง — P0 แค่ทำให้ behavior ที่ผิดอยู่ stop bleeding

#### P0.5 Acceptance criteria ของ Phase P0
- [ ] รัน `php artisan tinker` ทดสอบ: course ที่มีคะแนน lesson question จริง → `syncAllCourseMembers` แล้ว `achieved_score` มากกว่า 0
- [ ] `(achieved_score + external + bonus) / total_score * 100 ≤ 100` ในทุก member ทดสอบ
- [ ] `grade_progress` update ทันทีหลัง `syncMemberAchievedScore`
- [ ] `php -l` ผ่านทั้ง 2 service
- [ ] commit แยก 4 ก้อนตาม P0.1–P0.4

---

### Phase P1 — 🟠 Schema & Single Source

> เป้าหมาย: แยก field semantics ให้ชัด + รวมสูตรไว้ที่ DTO/method เดียว

#### P1.1 Migration: แยก field grading ของ `course_members`
- **ไฟล์ใหม่**: `database/migrations/2026_06_07_000001_split_course_member_grade_fields.php`
- **เปลี่ยน**:
  - rename `draft_total_score` → `draft_earned_score` (decimal 10,2)
  - เพิ่ม `draft_max_score` (decimal 10,2 default 0)
  - เพิ่ม `draft_percentage` (decimal 5,2 default 0)
  - rename `final_total_score` → `final_earned_score` (decimal 10,2)
  - เพิ่ม `final_max_score` (decimal 10,2 default 0)
  - เพิ่ม `final_percentage` (decimal 5,2 default 0)
  - เปลี่ยน `bonus_points` int → decimal(8,2) signed (รองรับ penalty)
  - เปลี่ยน `achieved_score` int → decimal(10,2)
- **Down migration**: ต้องเขียนกลับให้ได้ (rename + cast int)
- **Data backfill** (ใน `up()`): สำหรับวิชาที่ `finalization_status IN ('published','finalized','archived')` → set `final_max_score = courses.total_score` snapshot, `final_percentage = final_earned_score / final_max_score * 100`

#### P1.2 Migration: `course_grades` (transcript) ก็แยก field
- **ไฟล์ใหม่**: `database/migrations/2026_06_07_000002_add_max_score_to_course_grades.php`
- เพิ่ม `max_score` (decimal 10,2) ถัดจาก `total_score`
- backfill จาก `courses.total_score` ของแถวที่มีอยู่
- เปลี่ยน semantic: `total_score` = earned ดิบ, `percentage` = % คำนวณจาก 2 ตัวบน
- update `CourseGradingService::syncMemberToTranscript` (`:325-353`) ให้เขียน 3 field แยกกัน

#### P1.3 DTO `ScoreBreakdown`
- **ไฟล์ใหม่**: `app/DataTransferObjects/ScoreBreakdown.php`
- โครงสร้าง:
  ```php
  class ScoreBreakdown {
      public float $courseQuizEarned;
      public float $courseQuizMax;
      public float $lessonQuestionEarned;
      public float $lessonQuestionMax;
      public float $courseAssignmentEarned;
      public float $courseAssignmentMax;
      public float $lessonAssignmentEarned;
      public float $lessonAssignmentMax;
      public float $externalEarned;
      public float $externalMax;
      public float $bonus; // signed
      public array $missingSources; // ['quiz_ids'=>[], 'assignment_ids'=>[], ...]
      public function totalEarned(): float;
      public function totalMax(): float;
      public function percentage(): float; // 0-100, clamp
      public function toArray(): array;
  }
  ```

#### P1.4 `CourseScoreService::computeBreakdown(CourseMember): ScoreBreakdown`
- **ไฟล์**: `app/Services/CourseScoreService.php`
- รวม sub-query เดิม 6 แหล่งเป็น method เดียว
- คำนวณทั้ง earned และ max ต่อแหล่งใน method เดียว → ไม่มีโอกาส numerator/denominator หลุดกัน
- เพิ่ม `missingSources`: list ของ quiz/assignment ที่ member ยังไม่มี answer row
- เก็บ `member->last_score_synced_at = now()`

#### P1.5 `CourseScoreService::recompute(CourseMember): ScoreBreakdown`
- public method เดียวที่ controller ทั้งหลายต้องเรียกแทนการแก้ field ตรง
- ภายใน:
  1. `breakdown = computeBreakdown($member)`
  2. update `$member->achieved_score = breakdown.internalEarned (ไม่รวม external/bonus)`
  3. update `$member->external_score_points = breakdown.externalEarned`
  4. update `$member->grade_progress = calc grade from percentage`
  5. update `$member->last_score_synced_at`
  6. return breakdown
- DB transaction + `lockForUpdate` บน member row

#### P1.6 ปรับ controllers ให้เรียก service เดียว
- ไฟล์ที่ต้องแก้ (20 ไฟล์ที่ touch `achieved_score` ตอนนี้):
  - `AssignmentAnswerController` — หลัง grade เรียก `recompute`
  - `CourseQuizController` — หลัง finalize submission
  - `LessonAnswerQuestionController` — หลัง store answer
  - `LessonAssignmentController` — หลัง grade
  - `UserAnswerQuestionController` — หลัง store answer
  - `CourseExternalScoreController` — หลัง save entry
  - `CourseMemberGradeProgressController` — refactor ให้เป็น wrapper ของ `recompute`
- ลบ logic update `achieved_score`/`grade_progress` ที่กระจายอยู่ในแต่ละ controller
- เพิ่ม Observer สำรอง: `CourseExternalScoreEntryObserver` (saved/deleted → recompute) เผื่อ flow ที่ลืม

#### P1.7 Migration: เพิ่ม `last_score_synced_at` ลง `course_members`
- timestamp nullable
- update ทุกครั้งใน `recompute`

#### P1.8 Acceptance criteria ของ Phase P1
- [ ] `course_members.draft_*` และ `final_*` แยก earned/max/percentage ครบ
- [ ] `course_grades.max_score` มีจริง + percentage ตรง
- [ ] grep `->update.*achieved_score` ใน controllers เหลือ 0 (ทุกจุดผ่าน service)
- [ ] ทดสอบ: ส่ง quiz ผ่าน controller → `last_score_synced_at` update ภายในคำขอเดียวกัน
- [ ] PHPUnit unit test ของ `computeBreakdown` ครอบ 6 แหล่ง

---

### Phase P2 — 🟠 Grading Service ใช้ DTO + Snapshot

#### P2.1 Refactor `CourseGradingService::calculateDraftGrades`
- **ไฟล์**: `app/Services/CourseGradingService.php:92-120`
- เปลี่ยน:
  ```php
  foreach ($members as $member) {
      $breakdown = $this->scoreService->recompute($member);
      $gradeResult = $this->calculateGrade($breakdown->percentage(), $gradeScale);
      $member->update([
          'draft_earned_score' => $breakdown->totalEarned(),
          'draft_max_score'    => $breakdown->totalMax(),
          'draft_percentage'   => $breakdown->percentage(),
          'draft_grade'        => $gradeResult['grade'],
          'draft_grade_point'  => $gradeResult['grade_point'],
      ]);
  }
  ```
- ลบ `calculateMemberTotalScore` เก่า (หรือเก็บไว้เป็น legacy method ใต้ flag)

#### P2.2 Inject `CourseScoreService`
- เพิ่มใน constructor:
  ```php
  public function __construct(
      AttendanceEligibilityService $eligibilityService,
      CourseScoreService $scoreService
  )
  ```

#### P2.3 Migration: `course_member_grade_snapshots`
- **ไฟล์ใหม่**: `database/migrations/2026_06_07_000003_create_course_member_grade_snapshots_table.php`
- columns:
  - `id`, `course_id`, `course_member_id`, `user_id`
  - `earned_score` (decimal 10,2)
  - `max_score` (decimal 10,2)
  - `percentage` (decimal 5,2)
  - `letter_grade` (string 5)
  - `grade_point` (decimal 3,2)
  - `breakdown_json` (json)
  - `published_run_id` (uuid, index)
  - `is_current` (bool, index)
  - `published_at` (timestamp)
  - `published_by` (foreignId users)
- unique: `(course_member_id, published_run_id)`
- partial-index logic: ทุกครั้งที่ insert snapshot ใหม่ → UPDATE old `is_current=false`

#### P2.4 Refactor `publishDraftGrades`
- **ไฟล์**: `app/Services/CourseGradingService.php:198-237`
- ภายใน transaction:
  1. lock course row (`Course::where('id', $courseId)->lockForUpdate()->first()`)
  2. `$runId = Str::uuid()`
  3. foreach member → recompute → คำนวณ grade → update draft_* → insert snapshot row + set `is_current=true`, mark old `is_current=false`
  4. update course status published
  5. log

#### P2.5 Refactor `acceptGrade`
- **ไฟล์**: `app/Services/CourseGradingService.php:242-254`
- เปลี่ยน source: อ่านจาก `course_member_grade_snapshots` (where `is_current=true`) แทน `draft_*`
- copy snapshot → `final_earned_score / final_max_score / final_percentage / final_grade / final_grade_point`
- ถ้าไม่มี snapshot (legacy) → fallback ใช้ `draft_*` แล้ว warn ใน log

#### P2.6 Refactor `finalizeGrades`
- **ไฟล์**: `:259-303`
- ถ้า status = `grading` → ทำ `publishDraftGrades` ก่อน (สร้าง snapshot)
- ถ้า status = `published` → ใช้ snapshot ที่มีอยู่ ไม่ recompute
- auto-accept member ที่ยังค้าง: copy snapshot → final_*

#### P2.7 Refactor `reopenGrading`
- **ไฟล์**: `:508-528`
- mark snapshot ทั้งหมดของ course `is_current=false`
- mark transcript `is_published=false`
- next publish จะสร้าง snapshot ชุดใหม่ด้วย `published_run_id` ใหม่

#### P2.8 Refactor `overrideGrade`
- **ไฟล์**: `:358-408`
- ถ้า course finalized → insert snapshot ใหม่ (run_id ใหม่) ก่อน sync transcript
- เก็บ audit trail พร้อม snapshot diff

#### P2.9 Acceptance criteria
- [ ] หลัง publish → คะแนนแหล่งใดแหล่งหนึ่งเปลี่ยน → snapshot เดิม `is_current=true` ไม่เปลี่ยน
- [ ] reopen → publish ใหม่ → มี 2 snapshot rows, ตัวใหม่ `is_current=true`
- [ ] finalize 2 ครั้งพร้อมกัน → ครั้งที่ 2 รอ lock, ไม่สร้าง snapshot ซ้ำ
- [ ] PHPUnit: `test_publish_creates_snapshot`, `test_score_change_after_publish_does_not_alter_snapshot`, `test_reopen_invalidates_current_flag`

---

### Phase P3 — 🟡 Legacy Gradebook Flag

#### P3.1 Migration: `courses.use_legacy_gradebook`
- boolean default false
- data migration:
  ```sql
  UPDATE courses SET use_legacy_gradebook = true
  WHERE id IN (SELECT DISTINCT course_id FROM gradebook_assessments);
  ```

#### P3.2 `CourseScoreService::recompute` แตก path
- if `$course->use_legacy_gradebook` → ใช้ `gradebook_scores` (logic เดิมที่ย้ายมาจาก `calculateMemberTotalScore`)
- else → ใช้ `computeBreakdown`

#### P3.3 UI: ซ่อน assessment manual
- ใน `GradebookSubNav.vue` ตรวจ flag → ถ้า false ไม่แสดง tab "การประเมิน"
- ใน [index.vue](ui/pages/Learn/Courses/[id]/gradebook/index.vue) ถ้าเข้าหน้า assessment โดย course ไม่ใช่ legacy → redirect `/gradebook` + toast "ระบบนี้ใช้สำหรับวิชาที่เริ่มก่อน 2026-06-07"

#### P3.4 Acceptance
- [ ] วิชาเก่าที่มี gradebook_assessments เปิดได้เหมือนเดิม
- [ ] วิชาใหม่ใช้ flow ใหม่ ไม่เห็น tab assessment

---

### Phase P4 — 🟡 UI ใหม่: Gradebook Dashboard

#### P4.1 หน้า `/Learn/Courses/[id]/gradebook/index.vue`
- เปลี่ยนจาก "list of assessments" → "score breakdown table"
- columns ต่อ member: ชื่อ | Course Quiz | Lesson Q | Course Asg | Lesson Asg | External | Bonus | Earned | Max | % | เกรดคาดการณ์ | แหล่งที่ขาด | last_synced_at | action
- ใช้ endpoint ใหม่: `GET /api/courses/{id}/score-breakdown` คืน array ของ breakdown ต่อ member
- ปุ่ม **Resync ทั้ง course** → `POST /api/courses/{id}/score-breakdown/resync`
- click ที่ "แหล่งที่ขาด" → modal แสดง quiz/assignment ที่นักเรียนยังไม่ทำ พร้อม deep link

#### P4.2 หน้า `/gradebook/completion.vue`
- step bar: เริ่มออกเกรด → ประกาศ → ปิดเกรด
- ก่อนปุ่ม "ประกาศเกรด": auto-call resync + แสดง preview จาก snapshot ที่จะ commit
- ป้องกัน double-click ด้วย disabled state ระหว่าง backend ทำงาน

#### P4.3 Component ใหม่
- `GradebookScoreTable.vue`
- `MissingSourcesModal.vue`
- `ResyncButton.vue`

#### P4.4 Rename navigation
- `บันทึกคะแนน` → `บันทึกคะแนนภายนอก` (route `/external-scores`)
- `สมุดเกรด` → `สรุปคะแนน` (route `/gradebook`)
- `จบรายวิชา` → คงเดิม (route `/gradebook/completion`)
- ลบ tab `assessments` จาก nav (ยังเข้า direct ได้สำหรับ legacy)

#### P4.5 Acceptance
- [ ] หน้า gradebook โหลด < 500ms สำหรับ class 100 คน
- [ ] resync button ทำงาน + toast แจ้งจำนวน member ที่ update
- [ ] missing sources modal ลิงก์ไปยัง quiz/assignment ได้จริง

---

### Phase P5 — 🔵 Tests & Verification

#### P5.1 Unit tests (`tests/Unit/Services/`)
- `CourseScoreServiceTest::test_compute_breakdown_sums_all_six_sources`
- `CourseScoreServiceTest::test_recompute_updates_grade_progress`
- `CourseScoreServiceTest::test_breakdown_handles_zero_max_without_division_error`
- `CourseScoreServiceTest::test_lesson_question_uses_lesson_answer_questions_table`
- `CourseScoreServiceTest::test_negative_bonus_does_not_make_percentage_negative`
- `CourseScoreServiceTest::test_external_score_over_max_clamps_or_warns`

#### P5.2 Feature tests (`tests/Feature/Grading/`)
- `quiz_submission_updates_member_achieved_score_within_request`
- `assignment_grading_triggers_recompute_via_controller`
- `lesson_question_answer_contributes_to_achieved_score`
- `external_score_save_recomputes_grade_progress`
- `publish_creates_snapshot_with_is_current_true`
- `score_change_after_publish_does_not_affect_snapshot`
- `reopen_and_republish_creates_new_snapshot_run_id`
- `finalize_uses_snapshot_not_live_recompute`
- `course_with_no_sources_does_not_divide_by_zero`
- `concurrent_finalize_uses_row_lock_no_duplicate_transcript`
- `legacy_gradebook_course_still_uses_gradebook_scores`

#### P5.3 Manual verification script
- artisan command `php artisan grading:verify {course}` พิมพ์:
  - breakdown ของแต่ละ member
  - diff ระหว่าง snapshot ล่าสุด vs live recompute
  - rows ที่ percentage > 100 หรือ < 0
- ใช้ตรวจ regression ก่อน deploy

#### P5.4 Blocker ที่ต้องแก้ก่อนรัน PHPUnit
- SQLite/MySQL migration mismatch (รู้แล้วจากบล็อก Known Blockers ด้านบน) — fix ก่อนเริ่ม P5 ไม่งั้น test ใหม่รันไม่ได้
- หาตัวการ: `grep -rn "MODIFY COLUMN.*ENUM" api/nuxnanravel/database/migrations`
- option: ใช้ `Schema::table()->change()` + Doctrine DBAL หรือเปลี่ยน phpunit env เป็น MySQL test db

---

### ลำดับการ Ship (commit & branch plan)

- **Branch**: `refactor/course-score-source-of-truth` (off main)
- **Commits ต่อ Phase** (atomic, รีวิวง่าย):
  - P0 → 4 commits (1 ต่อ subtask) — push หลัง P0 เสร็จเพื่อ unblock production
  - P1 → 5 commits (migration / DTO / service / controllers / observer)
  - P2 → 4 commits (migration snapshot / publish / accept-finalize / reopen-override)
  - P3 → 2 commits (migration flag / legacy path)
  - P4 → 3 commits (API endpoint / table component / nav rename)
  - P5 → 1 commit ต่อกลุ่ม test
- **Merge gates**: ทุก Phase ต้องผ่าน `./vendor/bin/pint`, `php artisan test --filter=...`, manual smoke ก่อน merge ลง main
- **Rollback plan**: ทุก migration ต้องมี `down()` ที่ใช้ได้จริง; snapshot table drop ปลอดภัยเพราะไม่กระทบ field เดิม

---

### Open Questions (✅ ตอบแล้ว)

1. **`course_quiz_results.score` semantics?** -> **คำตอบ:** เป็นคะแนนดิบ (`sum('points')`) เช็คแล้วจาก `CourseQuizResultController::submit`
2. **Weight per category ต้องการไหม?** -> **คำตอบ:** Sum-based ไปก่อน
3. **Penalty cap** -> **คำตอบ:** Cap -100%
4. **Snapshot retention** -> **คำตอบ:** เก็บเฉพาะ 3 Run ล่าสุด
5. **Resync on view** -> **คำตอบ:** Auto-resync ทุกครั้งที่เปิดหน้า (เดี๋ยวจะ optimization query เอา)

---

## Current Snapshot

- Date: 2026-06-07
- Branch: main
- Active Work: 
  - **Course Lifecycle Policy**: ✅ Merged to main. Implement CourseLifecycleState enum, service, and policy integration. UI components (badge) and composables verified.
  - **P0-P4 Course Score Refactor**: ✅ Merged to main. P4 UI Dashboard and P5 SQLite blocker fix included in recent merge.
  - **Course Completion Workflow v2**: ⏳ Pending. Tests blocked by SQLite migration issue.

## Known Blockers (project-wide)

- 🚫 **SQLite/MySQL migration mismatch**: PHPUnit ใช้ SQLite แต่มี migration ใช้ `ALTER TABLE ... MODIFY COLUMN finalization_status ENUM(...)` (MySQL-only) → `RefreshDatabase` พัง → ทุก Feature test ที่ใช้ `RefreshDatabase` รันไม่ได้
- Action: ค้น migration ตัวการด้วย `grep -rn "MODIFY COLUMN.*finalization_status" api/nuxnanravel/database/migrations` แล้วเขียนให้ portable หรือเปลี่ยน phpunit env เป็น MySQL

## Active Work

**✅ เสร็จแล้ว (2026-06-07)**

| ไฟล์ | การเปลี่ยนแปลง |
|------|----------------|
| `feat/course-lifecycle-policy` | Merged into main: Enum, Service, Policy integration, UI badges, and tests. |
| `AdminController.php` | แก้ไข 422 error, ปรับปรุงการจัดการ username, เพิ่ม bulkDelete |
| `AdminRoleController.php` | เปลี่ยนชื่อ role จาก `USER` เป็น `STUDENT` เพื่อให้ตรงกับ DB |
| `UsersDataTable.php` | ปรับปรุง badge color และ role mapping |
| `UserResource.php` | เพิ่มฟิลด์ที่ขาดหาย และตั้งค่า default role เป็น `student` |
| `users/index.vue` | เพิ่ม Edit User Modal แบบ inline, ปรับปรุง role filter |
| `users/[id]/edit.vue` | รองรับ username field และแก้ปัญหา validation |
| `users/create.vue` | ปรับปรุงการสร้าง user ให้รองรับ username และ role `student` |
| `auth.ts` store | ปรับปรุงความเสถียรของ auth state และ registration flow |
| `2026_06_05_..._add_username_column_to_users.php` | เพิ่มคอลัมน์ username ในตาราง users |

## Coordination Board

- (ว่าง)

## Decisions And Assumptions

- ระบบใช้ role `STUDENT` แทน `USER` เป็นค่าเริ่มต้นสำหรับผู้ใช้ทั่วไป
- `username` ถูกใช้เป็นฟิลด์หลักในการระบุตัวตนและแก้ไขปัญหา validation ใน admin panel
- การแก้ไข user ในหน้า index ใช้ modal เพื่อความรวดเร็ว (UX consistency)

## Open Questions

- (ว่าง)

## Analysis Timeline

### 2026-06-07 - Fixed Course Score Breakdown 500 Error
- **Action**: Modified `CourseScoreBreakdownController::index` eager loading.
- **Problem**: Encountered `Unknown column "avatar" in "field list"` because `avatar` is an appended accessor on the `User` model, not a DB column.
- **Fix**: Replaced `avatar` with `profile_photo_path` in the `with('user:id,name,username,profile_photo_path')` clause to resolve the 500 error. The accessor still functions correctly in the serialized JSON.

### 2026-06-07 - Merged Course Lifecycle Policy
- **Action**: Merged `feat/course-lifecycle-policy` into `main`.
- **Verification**: Ran `php artisan test --filter=Lifecycle` on main, 31 tests passed.
- **Scope**: L0-L4 lifecycle states (Draft, Active, Enrollment Closed, Finalized, Archived) now enforced across controllers and UI.

### 2026-06-06 - Resolved Admin User Management 422 & Username Integration
- **Implemented**: Username column migration, backend validation logic, and frontend UI integration.
- **Fixed**: 422 Unprocessable Content error by aligning role names (`USER` -> `STUDENT`) between frontend and backend.
- **Enhanced**: Added inline Edit Modal in users list for better UX.
- **Verified**: PHP lint passed, git push successful.

### 2026-06-06 - Course Completion Plan Review
- **Findings**: User plan is directionally correct. `CourseCompletionController.php` has trailing stray `; } }` and `php -l` fails with unmatched `}`. `CourseGradingService::syncToTranscripts()` uses only `user_id` for `Student` and writes `semester_id => null`.
- **Additional risk**: `CourseCompletionController::finalizeGrades()` only accepts `grading`, while summary/service allow finalization from `grading` or `published`, so the publish -> accept -> finalize workflow may be blocked.
- **Likely files**: `api/nuxnanravel/app/Http/Controllers/Api/CourseCompletionController.php`, `api/nuxnanravel/app/Services/CourseGradingService.php`, `api/nuxnanravel/app/Models/Semester.php`, focused backend tests if existing factories support them.
- **Verification plan**: `php -l` changed PHP files, focused route/workflow check, and targeted PHPUnit coverage for transcript sync with multi-academy student records if feasible.
