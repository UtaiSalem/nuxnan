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

### ข้อเสนอ: ระบบ Course Completion — ทำให้ระบบจบวิชาและออกเกรดทำงานได้จริง (2026-06-05)

#### แผนเดิมของผู้ใช้ (สรุป)

1. ซ่อม `routes/course-completion.php` ให้ include ใน `routes/api.php`
2. เชื่อม `CourseMember` + learner identity กับ `Student`
3. เมื่อจบรายวิชา/ประกาศเกรด → บันทึกลง `CourseGrade`
4. รวมการคำนวณคะแนนจาก `CourseGradingService` + `CourseScoreService`
5. ปรับ `finalization_status` workflow: `active → grading → pending_acceptance → finalized → archived`
6. ปรับ UI ให้ตรง backend
7. Frontend หน้าต่างๆ

---

### ผลการตรวจ codebase โดย Claude (verified 2026-06-05)

#### 🟢 สิ่งที่ถูกต้อง / แก้ไขจากแผนเดิม

**[CC-CORRECT-1] Routes ลงทะเบียนแล้วผ่าน `bootstrap/app.php`**
- `bootstrap/app.php` (line 24–26) มี:
  ```php
  Route::middleware(['api'])
      ->prefix('api')
      ->group(base_path('routes/course-completion.php'));
  ```
- ทุก endpoint ใน `course-completion.php` → **ทำงานได้** ไม่ใช่ 404
- แผนเดิมที่บอกว่า "route ยังไม่ถูก include" → **ผิด** ไม่ต้องแก้ route registration

**[CC-CORRECT-2] `routes/learn/gradebook.php` ≠ dead file**  
- Routes ของ gradebook/transcript ถูก define ตรงใน `routes/api.php` แล้ว (ไม่ผ่าน require gradebook.php)
- `gradebook.php` คือ file เก่า/archive ไม่ใช่ source ที่ใช้งาน

---

#### 🔴 Blockers จริง (ต่างจากที่แผนเดิมคาดไว้)

**[CC-BUG-1] ไม่มี `CoursePolicy` — `authorize('manage', $course)` fail 403 ทุก call**
- `CourseCompletionController` เรียก `$this->authorize('manage', $course)` ใน 8 methods
- ไม่มีไฟล์ Policy ใดๆ ใน `app/Policies/`
- `AppServiceProvider::boot()` ไม่มี `Gate::define()` หรือ `Gate::before()` เลย
- ผล: ทุก call ที่ controller จาก admin/teacher จะได้ 403 `AuthorizationException`
- รวมถึง `RemediationController`, `CertificateController`, `GradeAppealController` ที่ใช้ `$request->user()->can('manage', $course)` ด้วย

**[CC-BUG-2] `calculateMemberTotalScore()` query ผิด column**
- `CourseGradingService` (line 128): `->where('gradebook_scores.course_member_id', $member->id)`
- แต่ `gradebook_scores` migration (2026_02_03_000006): column เป็น `student_id` (FK → `students.id`) ไม่มี `course_member_id`
- ผล: query always returns empty → `calculateDraftGrades()` set `draft_grade = 'F'` สำหรับทุกคน

**[CC-BUG-3] `verified` middleware บล็อก users ที่ยังไม่ verify email**
- `course-completion.php` route group: `middleware(['auth:api', 'verified'])`
- `User` model implements `MustVerifyEmail` + มี `email_verified_at` field
- Users ที่ถูกสร้างโดย admin (หรือ register แล้วยังไม่ verify) → blocked จาก completion endpoints ทั้งหมด
- ต้องตรวจว่า use case ทั่วไปใน project นี้ user มี `email_verified_at` set หรือไม่

---

#### 🟡 Gaps ที่แผนเดิมยังไม่ครอบคลุม

**[CC-GAP-1] `finalization_status` ENUM ไม่มี `'published'` แต่ frontend handle มัน**
- DB ENUM (migration 2026_02_03_100001): `['active', 'grading', 'finalized', 'archived']`
- Frontend `completion.vue` (line 185): `case 'published': return 'ประกาศแล้ว'`
- Controller `publishDraftGrades()` ไม่ set status → ยังคง `'grading'`
- ผล: หลัง publish grades สถานะในหน้า UI ไม่เปลี่ยนแปลง, 'ประกาศแล้ว' จะไม่แสดงเลย
- ต้องเลือก: (a) เพิ่ม `'published'` ใน ENUM และ update workflow, หรือ (b) ลบ case นั้นออกจาก frontend

**[CC-GAP-2] `calculateMemberTotalScore()` ไม่มี fallback ไป `CourseMember.achieved_score`**
- ปัจจุบัน: ถ้า `gradebook_scores` ว่าง → return 0 (ไม่ใช้ CourseScoreService)
- `CourseMember` มี `achieved_score`, `bonus_points`, `external_score_points` ที่คำนวณไว้แล้ว
- Courses ที่ใช้ quiz/assignment โดยตรง (ไม่ผ่าน Gradebook) จะได้คะแนน 0 ทุกคน

**[CC-GAP-3] ไม่มีหน้า student view สำหรับดู grade ที่ประกาศแล้ว**
- Controller มี `getMyGrade()` และ `acceptGrade()` แล้ว
- แต่ frontend ยังไม่มีหน้า/component ให้ student กด "ยืนยันรับเกรด"
- หน้า `completion.vue` เป็น admin view เท่านั้น

**[CC-GAP-4] `finalizeGrades()` ไม่สร้าง `CourseGrade` สำหรับ Transcript**
- หลัง finalize: `CourseMember.final_grade` + `final_total_score` ถูก set แล้ว
- แต่ไม่มี logic ที่สร้าง/อัพเดท `CourseGrade` (ตาราง transcript system) ให้ตรงกัน
- ผล: Transcript system ไม่รู้ว่า course นี้จบแล้ว

**[CC-GAP-5] ไม่มี middleware guard ว่า course อยู่ใน finalization_status ที่ถูกต้อง**
- บาง method ตรวจ `finalization_status` เอง แต่ไม่ consistent
- เช่น `acceptGrade()` ตรวจ `completion_status` แต่ไม่ตรวจ `finalization_status`

---

#### 📐 ข้อเสนอด้านออกแบบที่ปรับปรุง

**1. สร้าง `CoursePolicy` (แก้ Blocker #1)**
```php
// app/Policies/CoursePolicy.php
class CoursePolicy
{
    public function manage(User $user, Course $course): bool
    {
        // Owner ของ course
        if ($course->user_id === $user->id) return true;
        // Course admin (isAdmin method มีอยู่แล้ว)
        return $course->isAdmin($user);
    }
    
    public function view(User $user, Course $course): bool
    {
        return $course->isMember($user) || $this->manage($user, $course);
    }
}
```

และ register ใน `AppServiceProvider::boot()`:
```php
Gate::policy(Course::class, CoursePolicy::class);
```

**2. แก้ `calculateMemberTotalScore()` — 2 paths (แก้ Blocker #2 + CC-GAP-2)**
```php
protected function calculateMemberTotalScore(CourseMember $member): float
{
    // Path 1: Gradebook scores ถ้ามี (ใช้ student_id ไม่ใช่ course_member_id)
    $student = Student::where('user_id', $member->user_id)
        ->where('academy_id', $member->course->academy_id)
        ->first();
    
    if ($student) {
        $scores = DB::table('gradebook_scores')
            ->join('gradebook_assessments', ...)
            ->where('gradebook_scores.student_id', $student->id)
            ->where('gradebook_assessments.course_id', $member->course_id)
            ->get();
        
        if ($scores->isNotEmpty()) {
            // ...weighted calculation...
            return round($totalWeightedScore, 2);
        }
    }
    
    // Path 2: Fallback ใช้ CourseMember achieved_score
    $courseScoreService = app(CourseScoreService::class);
    $courseScoreService->syncMemberAchievedScore($member);
    $member->refresh();
    
    $total = $member->achieved_score + $member->bonus_points + $member->external_score_points;
    $maxTotal = $member->course->total_score;
    
    return $maxTotal > 0 ? round(($total / $maxTotal) * 100, 2) : 0;
}
```

**3. เพิ่ม `'published'` ใน ENUM หรือ remove จาก frontend**
- ถ้าต้องการ status 'published' ใน workflow:
  ```php
  // Migration ใหม่ — addColumn ให้ ENUM รองรับ 'published'
  DB::statement("ALTER TABLE courses MODIFY COLUMN finalization_status ENUM('active','grading','published','finalized','archived') DEFAULT 'active'");
  ```
  แล้ว `publishDraftGrades()` ต้อง set `finalization_status = 'published'`
- หรือ: ลบ case 'published' ออกจาก frontend และ map 'grading' เป็น label ที่เหมาะสม

**4. Bridge `finalizeGrades()` → `CourseGrade` (แก้ CC-GAP-4)**
```php
// หลัง finalize: สร้าง/อัพเดท CourseGrade สำหรับ transcript
public function finalizeGrades(Course $course, User $performer): Course
{
    return DB::transaction(function () use ($course, $performer) {
        // ...existing logic...
        
        // Bridge to transcript system
        foreach ($course->members as $member) {
            if ($member->final_grade) {
                $semester = Semester::getCurrentForCourse($course);  // method ใหม่
                CourseGrade::updateOrCreate(
                    ['course_id' => $course->id, 'user_id' => $member->user_id],
                    [
                        'academy_id' => $course->academy_id,
                        'student_id' => Student::where('user_id', $member->user_id)->where('academy_id', $course->academy_id)->value('id'),
                        'semester_id' => $semester?->id,
                        'letter_grade' => $member->final_grade,
                        'grade_points' => $member->final_grade_point,
                        'percentage' => $member->final_total_score,
                        'status' => CourseGrade::STATUS_COMPLETED,
                        'is_published' => true,
                    ]
                );
            }
        }
        
        return $course->fresh();
    });
}
```

**5. แก้ `verified` middleware (แก้ Blocker #3)**
- ตรวจสอบ use case: ถ้า admin สร้าง user ให้โดยไม่ verify email → เพิ่ม auto-verify หรือใช้ custom middleware ที่ skip verified check สำหรับ API token auth
- ง่ายสุด: เปลี่ยน `['auth:api', 'verified']` เป็น `['auth:api']` ในไฟล์ `course-completion.php` ถ้า project ไม่บังคับ email verify

---

### แผนการทำงาน (Course Completion — ปรับปรุงแล้ว 2026-06-05)

#### Phase 0: Blockers (ทำก่อน)

```
[CC-FIX-1] สร้าง CoursePolicy
  ไฟล์ใหม่: app/Policies/CoursePolicy.php
  ลงทะเบียน: AppServiceProvider::boot() เพิ่ม Gate::policy(Course::class, CoursePolicy::class)
  Logic: manage = course owner OR course isAdmin()
  ผลที่คาดหวัง: start-grading, publish-grades, finalize ไม่ 403 อีกต่อไป

[CC-FIX-2] แก้ calculateMemberTotalScore() — column และ fallback
  ไฟล์: app/Services/CourseGradingService.php (line 126–156)
  แก้: เปลี่ยน course_member_id → student_id (lookup via Student model)
       เพิ่ม fallback path ไป CourseMember.achieved_score เมื่อ gradebook ว่าง
  ผลที่คาดหวัง: draft grades สะท้อนคะแนนจริง ไม่ใช่ F ทุกคน

[CC-FIX-3] ตรวจสอบ verified middleware
  ทำ: ตรวจว่า user ใน DB มี email_verified_at หรือไม่
  ถ้าไม่ → เปลี่ยนเป็น middleware(['auth:api']) ใน course-completion.php line 21
```

#### Phase 1: Workflow ให้ถูกต้อง

```
[CC-W1] ตัดสินใจ finalization_status: เพิ่ม 'published' หรือ remove จาก frontend
  ถ้าเพิ่ม 'published':
    - Migration ใหม่: ALTER TABLE courses MODIFY COLUMN finalization_status ENUM(...)
    - publishDraftGrades(): set finalization_status = 'published'
  ถ้าไม่เพิ่ม:
    - ลบ case 'published' ออกจาก completion.vue (line 185–186)

[CC-W2] เพิ่ม bridge finalizeGrades() → CourseGrade
  ไฟล์: app/Services/CourseGradingService.php
  เพิ่ม: สร้าง/อัพเดท CourseGrade records หลัง finalize
  ผลที่คาดหวัง: Transcript system เห็นผลการเรียนหลัง course ปิด

[CC-W3] เพิ่ม Semester::getCurrentForCourse() helper
  ไฟล์: app/Models/Semester.php (หรือ service)
  Logic: หา semester ที่ active ณ เวลา course.started_at หรือ current semester ของ academy
```

#### Phase 2: Student-facing Grade View

```
[CC-UI-1] Component/Section สำหรับ student ดูเกรดที่ประกาศ
  ไฟล์: ใหม่ หรือเพิ่มใน MyProgressDetails.vue / CoursePageShell
  เรียก: GET /api/courses/{id}/completion/my-grade
  แสดง: draft_grade, can_accept, ปุ่ม "ยืนยันรับเกรด"
  เรียก: POST /api/courses/{id}/completion/accept-grade

[CC-UI-2] ปรับ completion.vue ให้ handle status ที่ถูกต้อง
  ไฟล์: ui/pages/Learn/Courses/[id]/gradebook/completion.vue
  แก้: ลบ 'published' label (ถ้าไม่เพิ่ม ENUM) หรือ map ให้ถูกต้อง
       เพิ่ม indicator แสดง score source (gradebook vs fallback)
```

#### Phase 3: Certificate & Remediation (ถ้า CC-BUG-1 แก้แล้ว)

```
[CC-CERT-1] ตรวจ CertificateController::generate() และ bulkIssue()
  ทำ: manual test หลัง CoursePolicy สร้างแล้ว
  ตรวจ: certificate PDF template มีอยู่ใน resources/views/pdf/certificate.blade.php ✅

[CC-REM-1] ตรวจ RemediationController ว่าใช้ Policy ถูกต้อง
  ปัจจุบัน: ใช้ $request->user()->can('manage', $course) → ต้องการ CoursePolicy เช่นกัน
```

---

#### ลำดับที่แนะนำ

```
ทันที (Phase 0):
  1. CC-FIX-1: สร้าง CoursePolicy + register
  2. CC-FIX-2: แก้ calculateMemberTotalScore() (column + fallback)
  3. CC-FIX-3: ตรวจ verified middleware

หลังจากนั้น:
  4. CC-W1: ตัดสินใจ 'published' status
  5. CC-W2 + CC-W3: bridge → CourseGrade
  6. CC-UI-1: student grade view component
  7. CC-UI-2: completion.vue fix
  8. Phase 3: Certificate/Remediation test
```

---

#### ความเสี่ยงและจุดต้องระวัง

| ความเสี่ยง | วิธีจัดการ |
|------------|------------|
| CoursePolicy::manage() ต้องตรวจให้ครบ — owner vs isAdmin vs academy admin | ทดสอบด้วย user ที่เป็น owner, course admin, academy admin, student (ต้องผ่านเฉพาะ 2 แรก) |
| `CourseGrade` bridge ใน finalizeGrades อาจ fail ถ้า Course ไม่มี academy (standalone course) | Guard: `if ($course->academy_id)` ก่อน lookup Student |
| `Semester::getCurrentForCourse()` — course อาจอยู่นอก semester | fallback: `semester_id = null` ยังสร้าง `CourseGrade` ได้ เพียงแต่ไม่ถูก group ใน transcript |
| verified middleware — ถ้า remove ออก อาจเปิดช่องให้ user ที่ยังไม่ verify เข้าใช้ completion | ตรวจ business rule ก่อน: project นี้ force verify email หรือไม่? |

---

### ข้อเสนอ: ระบบ Transcript — ทำให้ module ที่มีอยู่ใช้งานจริง (2026-06-05)

#### แผนเดิมของผู้ใช้ (สรุป)

1. ซ่อม route/API ให้ endpoint transcript เปิดใช้งานจริง
2. เชื่อม `CourseMember` + learner identity เข้ากับ `Student`
3. เมื่อจบรายวิชา/ประกาศเกรด → บันทึกลง `course_grades`
4. สร้าง transcript รายภาคจาก `course_grades`
5. สร้าง transcript รายปี/GPAX จาก transcript รายภาค
6. เพิ่มหน้านักเรียนดูและดาวน์โหลด PDF
7. เพิ่มหน้า admin/teacher สำหรับ generate, review, publish

---

### ผลการตรวจ codebase โดย Claude (verified 2026-06-05)

#### 🔴 Blocker — ทำให้ทุก endpoint พัง ต้องแก้ก่อน

**[TR-BLOCKER-1] `routes/learn/gradebook.php` ไม่ถูก include ใน `routes/api.php`**
- `routes/api.php` มี `require` สำหรับ `learn/academy.php`, `learn/course.php`, `learn/student.php` ฯลฯ แต่ **ไม่มี** `require __DIR__ . '/learn/gradebook.php'`
- ผล: ทุก route ใน `gradebook.php` (gradebook, transcript, academic management) ยัง**ไม่ถูกลงทะเบียน** → 404 ทั้งหมด
- หมายเหตุ: routes ใน `gradebook.php` ซ้ำกับที่ register ใน `api.php` แล้ว ต้องตรวจว่าต้องการใช้ไฟล์ไหน

  ตรวจแล้ว: `routes/api.php` มีการ define routes สำหรับ gradebook/transcript โดยตรงแล้ว (ไม่ผ่าน require) — แต่เป็นคนละ prefix กับ `gradebook.php` ต้องตรวจว่า overlap หรือไม่

**[TR-BLOCKER-2] `barryvdh/laravel-dompdf` ไม่ได้ติดตั้งใน `composer.json`**
- `TranscriptController::downloadTranscriptPdf()` และ `downloadMyTranscriptPdf()` ใช้ `Barryvdh\DomPDF\Facade\Pdf`
- แต่ `composer.json` ไม่มี package นี้ → `Class "Barryvdh\DomPDF\Facade\Pdf" not found` ทันทีที่เรียก endpoint PDF
- Fix: `composer require barryvdh/laravel-dompdf`

---

#### ✅ Infrastructure ที่มีอยู่แล้วและทำงานได้

| ส่วน | สถานะ |
|------|--------|
| Migration `semester_transcripts`, `semester_transcript_items`, `annual_transcripts` | ✅ run แล้ว |
| Migration `gradebook_assessments`, `gradebook_scores`, `course_grades` | ✅ run แล้ว |
| Models: `SemesterTranscript`, `AnnualTranscript`, `CourseGrade`, `GradebookAssessment`, `GradebookScore`, `GradeScale`, `GradeScaleItem` | ✅ ครบ |
| `SemesterTranscript::calculate()` — คำนวณ GPA จาก `course_grades` | ✅ logic สมบูรณ์ |
| `SemesterTranscript::calculateRanking()` — อันดับในชั้น/ระดับชั้น | ✅ |
| `AnnualTranscript::calculate()` — GPAX | ✅ |
| `CourseGrade::calculateFromScores()` — คำนวณ % จาก `gradebook_scores` | ✅ |
| `CourseGrade::assignLetterGrade()` — แปลง % → letter grade จาก `GradeScale` | ✅ |
| PDF template `resources/views/pdf/transcript.blade.php` | ✅ มีอยู่แล้ว |
| `TranscriptController` — logic ครบ (generate, publish, download, overview) | ✅ |
| `GradebookController::publishGrades()` — bridge สร้าง/อัพเดท `CourseGrade` | ✅ |
| Frontend `my-transcript.vue` | ✅ มีอยู่ แต่เรียก endpoint ที่ไม่ทำงาน |

---

#### 🟡 Gap ที่แผนเดิมยังไม่ได้ระบุ

**[TR-GAP-1] ไม่มี admin UI สำหรับ transcript management**
- มีแค่ `my-transcript.vue` (student view) ใน `ui/pages/academies/[name]/`
- ไม่มีหน้าสำหรับ admin generate/review/publish transcript
- Teacher ไม่มีทาง trigger `generateSemesterTranscript()` ผ่าน UI

**[TR-GAP-2] `GradebookScore` ใช้ `student_id` FK → `students` table เท่านั้น**
- Gradebook และ transcript ทำงานได้เฉพาะนักเรียนที่ enroll เป็น `Student` ใน academy
- User ที่ join course จาก platform ทั่วไป (ไม่ใช่ academy student) → ไม่มีใน gradebook → ไม่มี transcript
- แผนเดิมพูดถึง "เชื่อม CourseMember กับ Student" แต่ยังไม่ระบุ mechanism ที่ชัดเจน

**[TR-GAP-3] `calculateRanking()` silently skip ถ้า `classroom_id` null**
- `SemesterTranscript::calculateRanking()` line 188: `if (!$this->classroom_id || !$this->gpa) return $this;`
- นักเรียนที่ไม่ได้อยู่ใน classroom → ไม่มี ranking เลย แม้ GPA มี
- ไม่มี fallback ranking ระดับ academy ทั้ง academy

**[TR-GAP-4] `getMyTranscripts()` return เฉพาะ `status='published'`**
- นักเรียนไม่เห็นอะไรเลยจนกว่า admin จะ publish → UX ไม่ดี
- ควรมี status indicator ว่า "กำลังรอประกาศผล" แม้ transcript ยังเป็น draft

**[TR-GAP-5] `CourseMember.grade_progress` ≠ `CourseGrade`**
- Course มีคะแนนใน `CourseMember` (ผ่าน assignment/quiz/bonus) แต่ `GradebookController` ใช้ `GradebookAssessment` + `GradebookScore` เป็นระบบแยก
- ถ้า teacher ใช้ quiz/assignment ใน nuxnan แต่ไม่ได้ใช้ Gradebook → `CourseGrade` จะว่างเปล่า → Transcript ว่าง
- ต้องการ bridge: เพิ่ม option ใน `publishGrades()` ให้ดึงคะแนนจาก `CourseMember.achieved_score` ด้วย (fallback)

**[TR-GAP-6] Route ซ้ำซ้อนระหว่าง `gradebook.php` กับ `api.php`**
- `api.php` มี route block สำหรับ gradebook/transcript โดยตรงอยู่แล้ว
- `gradebook.php` ก็มีชุด route เดียวกัน
- ต้องตรวจว่า: (a) `gradebook.php` คือ source ที่ถูกต้อง หรือ (b) `api.php` คือที่ register จริง

**[TR-GAP-7] ไม่มี route สำหรับ `getStudentTranscript` โดย academy admin**
- Admin ต้องการดู transcript ของนักเรียนคนใดคนหนึ่งในรูปแบบ list
- มีแค่ `getAcademyTranscriptOverview()` แต่ไม่มี per-student detail view สำหรับ admin

---

#### 📐 ข้อเสนอด้านออกแบบที่ปรับปรุงจากแผนเดิม

**1. Route fix — ตรวจก่อนว่าใช้ไฟล์ไหน**
```php
// routes/api.php — เพิ่มบรรทัดนี้ถ้า routes ทั้งหมดอยู่ใน gradebook.php เท่านั้น
require __DIR__ . '/learn/gradebook.php';

// หรือถ้า routes ถูก define ใน api.php อยู่แล้ว → ไม่ต้อง require ซ้ำ
// แต่ต้องตรวจว่า prefix ถูกต้อง
```

**2. Install dompdf**
```bash
composer require barryvdh/laravel-dompdf
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

**3. Bridge: `CourseMember` → `CourseGrade` (fallback path)**
```php
// ใน GradebookController::publishGrades() หรือ service ใหม่
// ถ้าไม่มี GradebookAssessment สำหรับ course นั้น
// → ใช้ CourseMember.achieved_score + course.total_score แทน
if (!$hasGradebookAssessments) {
    $grade->update([
        'total_score' => $member->achieved_score,
        'percentage' => $course->total_score > 0 
            ? ($member->achieved_score / $course->total_score) * 100 
            : 0,
    ]);
}
```

**4. Admin UI ที่ต้องสร้าง**
```
ui/pages/academies/[name]/admin/transcripts/
  index.vue        — overview + generate/publish controls
  [semester].vue   — transcript list per semester + bulk actions
```

**5. ปรับ `getMyTranscripts()` ให้แสดง status ด้วย**
```php
// แสดงทุก status แต่ filter ตาม role
$showDraft = $this->canManageTranscripts(...);  // admin เห็น draft ด้วย
$query->when(!$showDraft, fn($q) => $q->where('status', 'published'));
```

---

### แผนการทำงาน (Transcript — ปรับปรุงแล้ว 2026-06-05)

#### Phase 0: Blockers (แก้ก่อน ไม่มีอะไรทำงานถ้าไม่แก้)

```
[TR-FIX-1] ตรวจสอบ route registration
  ไฟล์: api/nuxnanravel/routes/api.php
  ทำ: php artisan route:list --name=transcript → ถ้าไม่เจอ route ใดเลย → เพิ่ม require
  ถ้า route มีอยู่แล้ว (จาก api.php โดยตรง) → ไม่ต้องทำอะไร

[TR-FIX-2] ติดตั้ง barryvdh/laravel-dompdf
  คำสั่ง: cd api/nuxnanravel && composer require barryvdh/laravel-dompdf
  แล้ว: php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

#### Phase 1: Bridge CourseMember → CourseGrade

```
[TR-S1] สร้าง GradePublishService (หรือขยาย GradebookController::publishGrades())
  ไฟล์: app/Services/GradePublishService.php (ใหม่)
  Logic:
    1. รับ course + list students
    2. ถ้ามี GradebookAssessment → ใช้ GradebookScore (เหมือนเดิม)
    3. ถ้าไม่มี → fallback: สร้าง CourseGrade จาก CourseMember.achieved_score
    4. Call CourseGrade.assignLetterGrade() ทุกครั้ง
    5. Set status = 'completed', is_published = true

[TR-S2] เพิ่ม semester_id ใน CourseMember (optional แต่สำคัญมาก)
  ปัญหาปัจจุบัน: CourseGrade ต้องการ semester_id แต่ CourseMember ไม่มีฟิลด์นี้
  → GradePublishService ต้องถาม/ระบุ semester ตอน publish
  → หรือ: course มี default_semester_id setting?
  ทางเลือกง่ายกว่า: เพิ่ม API param semester_id ใน publishGrades() endpoint
```

#### Phase 2: ซ่อมและเติม Admin API

```
[TR-API-1] เพิ่ม route สำหรับ admin ดู transcript รายคน
  ไฟล์: routes/learn/gradebook.php (หรือ api.php)
  เพิ่ม: GET /academies/{academy}/students/{student}/transcripts
         ไปเรียก TranscriptController::getSemesterTranscript() ใน context admin

[TR-API-2] แก้ getMyTranscripts() ให้แสดง status ทั้งหมดพร้อม filter
  ไฟล์: TranscriptController.php (line 408)
  แก้: เพิ่ม status field ใน response + ไม่ filter status=published เฉพาะ (แสดง 'pending' ด้วย)

[TR-API-3] แก้ calculateRanking() ให้มี academy-level fallback
  ไฟล์: SemesterTranscript.php (line 188)
  แก้: ถ้า classroom_id = null → คำนวณ rank ระดับ academy ทั้งหมดแทน
```

#### Phase 3: Admin UI

```
[TR-UI-1] สร้างหน้า transcript overview สำหรับ admin
  ไฟล์ใหม่: ui/pages/academies/[name]/admin/transcripts/index.vue
  Features:
    - เลือก semester
    - กดปุ่ม "Generate Transcript" → POST /academies/{id}/transcripts/semester/generate
    - แสดงตาราง draft transcripts พร้อม GPA, rank
    - กดปุ่ม "Publish" → POST /academies/{id}/transcripts/semester/publish

[TR-UI-2] ปรับ my-transcript.vue ให้แสดง status
  ไฟล์: ui/pages/academies/[name]/my-transcript.vue
  แก้: แสดง "กำลังรอประกาศผล" ถ้า transcript ยังเป็น draft
       แสดงปุ่ม download เฉพาะ status=published
```

#### Phase 4: ทดสอบ flow ครบ

```
[TR-TEST-1] Manual: Teacher กรอกคะแนนผ่าน Gradebook → publishGrades → admin generateTranscript → student เห็น transcript
[TR-TEST-2] Manual: Course ที่ใช้ quiz/assignment (ไม่มี Gradebook) → GradePublishService fallback path
[TR-TEST-3] API: GET /students/me/transcripts ต้องส่ง data ที่ถูกต้องหลัง fix route
[TR-TEST-4] PDF: downloadMyTranscriptPdf → ต้องได้ PDF ที่ render ถูกต้อง
```

---

#### ลำดับที่แนะนำ

```
ทันที (Phase 0 — ไม่มีอะไรทำงานถ้าไม่ทำ):
  1. TR-FIX-1: ตรวจ route registration (php artisan route:list)
  2. TR-FIX-2: composer require barryvdh/laravel-dompdf

หลังจากนั้น:
  3. TR-S1: GradePublishService bridge
  4. TR-S2: semester_id param ใน publishGrades
  5. TR-API-2: แก้ getMyTranscripts status filter
  6. TR-API-3: แก้ calculateRanking fallback
  7. TR-UI-1: Admin transcript management page
  8. TR-UI-2: my-transcript.vue status display
  9. Phase 4: Test flow
```

---

#### ความเสี่ยงและจุดต้องระวัง

| ความเสี่ยง | วิธีจัดการ |
|------------|------------|
| Route ซ้ำซ้อนระหว่าง `gradebook.php` กับ `api.php` → route name conflict | รัน `php artisan route:list --name=transcript` ก่อน → ตัดสินใจว่าจะใช้ไฟล์ไหน |
| Non-academy users ไม่มี `Student` record → ไม่เข้า gradebook | สำหรับ v1: รองรับเฉพาะ academy students; ใน v2 อาจเพิ่ม `course_member_id` ใน `gradebook_scores` |
| PDF rendering อาจช้า/พังกับข้อมูลมาก | เพิ่ม timeout + queue job สำหรับ bulk PDF |
| `course.total_score` อาจเป็น 0 ถ้าไม่ได้ตั้งค่า → division by zero ใน bridge | Guard: `$course->total_score > 0 ? ... : null` |
| `semester_id` ไม่มีใน course context | ต้องการ UX: admin เลือก semester ตอน publish grades |

---

### ข้อเสนอ: Learner Identity Reuse — ปรับปรุงระบบตัวตนผู้เรียนให้ reuse ข้ามรายวิชา (2026-06-05)

#### แผนเดิมของผู้ใช้ (สรุป)

1. เพิ่มตาราง `learner_identity_profiles` (user-level default)
2. ขยาย `LearnerIdentityService` ให้มี fallback chain 5 ชั้น
3. `storemember()` ใช้ service auto-populate ตามลำดับ
4. `updateOwnProfile()` sync กลับ profile กลางด้วย
5. ทุก endpoint ส่ง `effective_member_name/code/order_number` แทนค่าดิบ
6. UI form prefill จาก `effective_*`
7. เพิ่ม tests

---

### ผลการตรวจ codebase โดย Claude (verified 2026-06-05)

#### 🔴 Bug ที่พบและแผนเดิมยังไม่ได้ระบุ (Priority 1)

**[LI-BUG-1] `identity_data` ไม่ถูก inject ใน 3 controller methods**
- `index()` (line 72): inject ✅ — batch-compute ก่อน pass ให้ Resource
- `show()` (line 99–256): ❌ ไม่ compute `identity_data` → `effective_*` = null ทุกฟิลด์
- `memberSettings()` (line 701–717): ❌ เหมือนกัน
- `memberProgress()` (line 719–873): ❌ เหมือนกัน
- ผล: ทุก endpoint ที่ frontend ใช้จริง (progress, settings) ส่ง `effective_*` = null ทำให้ placeholder ใน form ว่างทั้งหมดแม้ข้อมูลมีอยู่ใน academy/classroom

**[LI-BUG-2] `updateOwnProfile()` (line 1470–1474) nullify ฟิลด์ที่ไม่ได้ส่ง**
- โค้ดปัจจุบัน:
```php
$member->update([
    'member_name' => $request->member_name,  // null ถ้าไม่ส่ง
    'order_number' => ...,
    'member_code' => $request->member_code,  // null ถ้าไม่ส่ง
]);
```
- ถ้า user ส่งเฉพาะ `member_code` → `member_name` ถูก set เป็น null → ล้างค่าที่เคยกรอกไว้
- Fix: ใช้ conditional update ด้วย `$request->has()` เหมือนที่ `update()` (line 415–432) ทำอยู่แล้ว

**[LI-BUG-3] `storemember()` (line 344–351) return raw CourseMember ไม่ใช่ Resource**
- `'newCourseMember' => CourseMember::find($new_course_member->id)` → ไม่มี `effective_*` ใน response
- Frontend ไม่รู้ว่า identity ถูก auto-populate ยังไงหลัง enroll

**[LI-BUG-4] `MyProgressDetails.vue` form prefill ใช้ raw field ไม่ใช่ effective**
- Line 117–120:
```js
form.value = {
    member_name: data.value.member.member_name ?? '',   // raw — อาจ null ถ้าไม่ได้ override
    member_code: data.value.member.member_code != null ? String(data.value.member.member_code) : '',
```
- ถ้า `member_name = null` (ยังไม่ได้ override) → form แสดงว่าง แม้ placeholder ถูก (effective_member_name) แต่ข้อมูลจาก `effective_member_name` ที่มาจาก academy/classroom จะไม่ถูกแสดงใน field เพื่อให้ user ยืนยัน

**[LI-BUG-5] `autoPopulate()` เขียน resolved value ลง DB โดยตรง**
- `storemember()` (line 311): `$this->identityService->autoPopulate($new_course_member)` → เขียน `user.name` ลง `member_name` ใน DB
- ผล: `member_name = 'John'` ถูกเก็บใน `course_members` ทำให้ service คิดว่าเป็น "course_override" แต่จริงๆ เป็นค่าที่ copy มาจาก user profile
- ต้องมีวิธีแยกแยะ "explicit override" vs "fallback copy" — ดูข้อเสนอด้านล่าง

**[LI-BUG-6] `identity_source` ใน `LearnerIdentityService` ติดตามผิด — single source สำหรับ 3 fields**
- ปัจจุบัน: `identity['source']` เป็น string เดียว เช่น `'course_override'`
- ปัญหา: `member_name` อาจมาจาก course_override ในขณะที่ `member_code` มาจาก academy — แต่ source แสดงแค่ค่าเดียว
- Resource (line 45): `'identity_source' => $this->identity_data['source'] ?? 'unknown'` → UI แสดง badge เดียวสำหรับทั้ง 3 ฟิลด์ ทำให้ผู้ใช้สับสน

---

#### ✅ สิ่งที่แผนเดิมถูกต้องและยืนยันแล้ว

| ข้อ | สถานะ |
|-----|--------|
| `LearnerIdentityService` มีอยู่แล้ว, `autoPopulate()` ถูกเรียกใน `storemember()` | ✅ |
| `CourseMemberResource` มี `effective_*` fields แล้ว (line 42–46) | ✅ |
| `index()` batch-compute identity_data ก่อน pass ให้ Resource | ✅ |
| Migration backfill (2026-05-30) มีอยู่แล้ว | ✅ |
| `MyProgressDetails.vue` placeholder ใช้ `effective_*` ✅ | ✅ |
| `updateOwnProfile` route ลงทะเบียนแล้ว | ✅ |
| ไม่มี `learner_identity_profiles` model หรือ migration ยังเลย | ✅ ต้องสร้างใหม่ |

---

#### 📐 ข้อเสนอด้านออกแบบที่ปรับปรุงจากแผนเดิม

**1. เพิ่ม flag `is_name_override` / `is_code_override` / `is_order_override` แทนการลบค่าออกจาก `autoPopulate()`**

แทนที่จะหยุดเขียน fallback value ลง DB (ซึ่งต้อง migrate ข้อมูลเก่า) — ให้เพิ่ม 3 boolean columns ใน `course_members`:
```sql
ALTER TABLE course_members ADD COLUMN is_name_override TINYINT(1) DEFAULT 0;
ALTER TABLE course_members ADD COLUMN is_code_override TINYINT(1) DEFAULT 0;
ALTER TABLE course_members ADD COLUMN is_order_override TINYINT(1) DEFAULT 0;
```
- `autoPopulate()` เขียนค่าได้แต่ตั้ง flag = 0 (fallback)
- `updateOwnProfile()` เขียนค่า + ตั้ง flag = 1 (explicit override)
- `resolve()` ใช้ flag เพื่อตัดสินใจว่าค่าใน course_members เป็น override จริงหรือแค่ fallback copy

**2. Schema ของ `learner_identity_profiles` (ปรับจากแผนเดิม)**
```sql
CREATE TABLE learner_identity_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    preferred_name VARCHAR(255) NULL,       -- ชื่อที่ผู้ใช้ต้องการใช้ข้ามรายวิชา
    preferred_code VARCHAR(50) NULL,        -- รหัสประจำตัวที่ผู้ใช้ยืนยันแล้ว
    preferred_order_number INT UNSIGNED NULL,
    confirmed_at TIMESTAMP NULL,            -- ครั้งล่าสุดที่ user ยืนยันข้อมูลนี้
    source_course_member_id BIGINT UNSIGNED NULL, -- อ้างอิงว่า copy มาจาก course_members ใด
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**3. Fallback chain ใหม่ใน `LearnerIdentityService::resolve()`**
```
สำหรับแต่ละฟิลด์ (name, code, order) แยกกัน:
1. course_members.field ถ้า is_field_override = true (user ระบุเฉพาะรายวิชานี้)
2. students/classroom_students ใน academy ของคอร์สนั้น (สำหรับ order_number)
3. academy_members.member_code (สำหรับ code)
4. learner_identity_profiles.preferred_field (user-level default ข้ามรายวิชา)
5. user.name (สำหรับ name)
```

**4. Logic `updateOwnProfile()` + sync กลับ profile กลาง**
```php
// หลัง update course_members — ถ้า user ส่ง save_as_default=true
if ($request->boolean('save_as_default')) {
    LearnerIdentityProfile::updateOrCreate(
        ['user_id' => auth()->id()],
        [
            'preferred_name' => $request->member_name ?? null,
            'preferred_code' => $request->member_code ?? null,
            'preferred_order_number' => $request->filled('order_number') ? (int)$request->order_number : null,
            'confirmed_at' => now(),
            'source_course_member_id' => $member->id,
        ]
    );
}
```

**5. Per-field source tracking (ปรับ `LearnerIdentityService`)**
```php
return [
    'member_name' => $resolvedName,
    'member_code' => $resolvedCode,
    'order_number' => $resolvedOrder,
    'source' => $primarySource,  // เดิม — keep for badge
    'name_source' => $nameSource,     // 'course_override'|'learner_profile'|'academy'|'user'
    'code_source' => $codeSource,
    'order_source' => $orderSource,
];
```

---

### แผนการทำงาน (ปรับปรุงแล้ว — 2026-06-05)

#### Phase 0: Bug Fix ทันที (ไม่ต้องมีตารางใหม่)

```
[LI-BUG-1] เพิ่ม identity_data inject ใน 3 controller methods
  ไฟล์: api/.../CourseMemberController.php
  แก้: show(), memberSettings(), memberProgress() — เพิ่ม
       $member->identity_data = $this->identityService->resolve($member->user, $member->course);
  ก่อน return response ในแต่ละ method

[LI-BUG-2] แก้ updateOwnProfile() ป้องกัน nullify
  ไฟล์: api/.../CourseMemberController.php (line 1470–1474)
  แก้: เปลี่ยนเป็น conditional update ด้วย $request->has()

[LI-BUG-3] แก้ storemember() return CourseMemberResource แทน raw model
  ไฟล์: api/.../CourseMemberController.php (line 344–351)
  แก้: $new_course_member->identity_data = $this->identityService->resolve(...)
        return CourseMemberResource ใน response

[LI-BUG-4] แก้ MyProgressDetails.vue form prefill
  ไฟล์: ui/components/learn/course/MyProgressDetails.vue (line 117–120)
  ปัจจุบัน: member_name: data.value.member.member_name ?? ''
  ใหม่: member_name: data.value.member.member_name ?? '' (คง logic เดิม)
  แต่เพิ่ม indicator "ข้อมูลนี้ copy จาก [source]" ถ้า member_name != null แต่ identity_source != 'course_override'
```

#### Phase 1: Migration + Model (ต้องทำก่อน Phase 2)

```
[LI-M1] สร้าง migration ใหม่
  ไฟล์ใหม่: database/migrations/2026_06_05_XXXXXX_create_learner_identity_profiles_table.php
  สร้างตาราง: learner_identity_profiles (ตาม schema ด้านบน)

[LI-M2] เพิ่ม override flags ใน course_members
  ไฟล์ใหม่: database/migrations/2026_06_05_XXXXXX_add_override_flags_to_course_members.php
  เพิ่ม: is_name_override, is_code_override, is_order_override (default 0)

[LI-M3] สร้าง LearnerIdentityProfile model
  ไฟล์ใหม่: app/Models/LearnerIdentityProfile.php
  belongsTo: User; hasMany: CourseMember (via source_course_member_id)
```

#### Phase 2: ขยาย LearnerIdentityService

```
[LI-S1] อัพเดท resolve() — fallback chain ใหม่ 5 ชั้น
  ไฟล์: app/Services/LearnerIdentityService.php
  แก้: เพิ่ม LearnerIdentityProfile ใน fallback chain (ก่อน user.name)
       เพิ่ม per-field source tracking (name_source, code_source, order_source)
       ใช้ is_*_override flags เพื่อตัดสินว่าค่าใน course_members เป็น override จริง

[LI-S2] อัพเดท autoPopulate() — ตั้ง flag = 0 เสมอ
  ไฟล์: app/Services/LearnerIdentityService.php
  แก้: หลัง set member fields ให้ set $member->is_name_override = 0 ฯลฯ
```

#### Phase 3: อัพเดท Controller + Resource

```
[LI-C1] updateOwnProfile() — ตั้ง override flags + optional sync to profile
  ไฟล์: api/.../CourseMemberController.php
  แก้: ตั้ง is_*_override = 1 สำหรับฟิลด์ที่ส่งมา
       เพิ่ม logic sync to LearnerIdentityProfile ถ้า save_as_default = true

[LI-C2] CourseMemberResource — เพิ่ม per-field source fields
  ไฟล์: api/.../CourseMemberResource.php
  เพิ่ม: name_source, code_source, order_source จาก identity_data
```

#### Phase 4: อัพเดท UI

```
[LI-UI1] MyProgressDetails.vue — เพิ่ม "บันทึกเป็น default" checkbox + per-field source indicator
  ไฟล์: ui/components/learn/course/MyProgressDetails.vue
  เพิ่ม: checkbox "บันทึกค่านี้เป็นค่าเริ่มต้นสำหรับรายวิชาใหม่"
         per-field source hint ใต้แต่ละ input
         ส่ง save_as_default ใน payload ถ้า user ติ๊ก

[LI-UI2] ตรวจสอบหน้า course admin — member list ใช้ effective fields หรือยัง?
  เช็คว่า component ที่แสดง member list ใช้ member_name (raw) หรือ effective_member_name
```

#### Phase 5: Tests

```
[LI-T1] Feature test: enroll course ใหม่ → member_name ถูก auto-populate จาก fallback chain ตามลำดับ
[LI-T2] Feature test: updateOwnProfile → ค่าใน course_members อัพเดท + override flag = 1
[LI-T3] Feature test: save_as_default → learner_identity_profiles ถูกสร้าง/อัพเดท
[LI-T4] Feature test: enroll คอร์สที่ 2 → ได้ค่าจาก learner_identity_profiles ของคอร์สที่ 1
[LI-T5] Feature test: course-specific override ต้องไม่กระทบค่าใน learner_identity_profiles
[LI-T6] Unit test: LearnerIdentityService.resolve() — ทุก fallback path
```

---

#### ลำดับการทำงานที่แนะนำ

```
ทันที (Phase 0 — Bug Fix ไม่ต้อง migrate):
  1. LI-BUG-1: inject identity_data ใน show/memberSettings/memberProgress
  2. LI-BUG-2: แก้ updateOwnProfile conditional update
  3. LI-BUG-3: storemember return CourseMemberResource
  
หลังจากนั้น (Phase 1–5 ตามลำดับ):
  4. LI-M1 + LI-M2 + LI-M3: migration + model
  5. LI-S1 + LI-S2: ขยาย service
  6. LI-C1 + LI-C2: อัพเดท controller/resource
  7. LI-UI1 + LI-UI2: อัพเดท UI
  8. LI-T1 ถึง LI-T6: tests
```

---

#### ความเสี่ยงและจุดต้องระวัง

| ความเสี่ยง | วิธีจัดการ |
|------------|------------|
| ข้อมูลเก่าใน `course_members.member_name` ที่ auto-populated แต่ไม่มี flag | ใช้ migration backfill: ถ้า `member_name == user.name` ให้ตั้ง `is_name_override = 0`; ถ้าต่างกัน ตั้ง `1` |
| `resolve()` มี N+1 query ถ้าเรียกใน list | ใช้ batch-compute แบบเดิมใน `index()` — เพิ่ม `LearnerIdentityProfile` ใน eager load |
| User อาจไม่ต้องการ sync ค่าไปรายวิชาอื่น | `save_as_default` เป็น opt-in ไม่ใช่ default |
| `preferred_order_number` ระดับ user อาจขัดกับ classroom order ของ academy | Fallback chain ให้ academy/classroom มาก่อน `learner_identity_profiles` สำหรับ order_number เสมอ |

---

### ข้อเสนอ: แก้ frontend state/race/cache ในหน้า course

ผู้ใช้ระบุ 4 สาเหตุหลัก:
1. Cache ใน `course.ts` คืนข้อมูลไม่ครบ — ขาด `courseMemberOfAuth`, `courseGroups`
2. `index.vue` ใช้ `authStore.user?.id` ก่อน user โหลดเสร็จ → `/users/undefined/...`
3. `auth.ts` plugin fetch user ตอน `app:mounted` ทำให้ component บางตัวเริ่มยิง API ก่อน user พร้อม
4. `CourseActionButton.vue` ไม่มี loading state ระหว่างตรวจสมาชิก

---

## Work Plan

### แก้ Frontend State/Race/Cache — Course Page (2026-06-04)

#### ผลการตรวจ codebase (verified line-by-line)

**[BUG-1] ✅ ยืนยัน — Cache hit ตัด `courseMemberOfAuth` + `courseGroups` ทิ้ง (สาเหตุหลัก)**
- `course.ts:56-57` — cache hit return: `{ success, course, academy, isCourseAdmin }` เท่านั้น
- `[id].vue:81` — `courseMemberOfAuth.value = response.courseMemberOfAuth || response.courseMember` → `undefined`
- `[id].vue:85` — `courseGroupStore.setGroups(response.courseGroups || [], ...)` → reset groups เป็น `[]`
- `[id].vue:86` — `courseMemberStore.setMember(undefined)`
- ผล: ทุกครั้งที่ SPA navigate กลับมาหน้า course ที่ cache ยัง valid (5 นาที) → `courseMemberOfAuth` กลายเป็น null → ปุ่ม "สมัครเรียน" ผุดขึ้นซ้ำ → UX พัง

**[BUG-1b] ✅ เพิ่มเติม — `courseGroupStore` ก็ได้รับผลกระทบเหมือนกัน (user's plan ไม่ได้ระบุ)**
- เมื่อ cache hit, `courseGroups` ถูก reset เป็น `[]` เสมอ
- ทำให้ group selector modal แสดงว่างเมื่อ navigate กลับมา

**[BUG-2] ✅ ยืนยัน — `CourseActionButton.vue:190` ไม่มี loading guard**
- template: `v-else-if="!courseMemberOfAuth"` → แสดงปุ่ม "สมัครเรียน" ทันทีเมื่อ prop เป็น null
- ไม่มีทางรู้ว่ากำลัง "ตรวจสอบ" หรือ "ยืนยันแล้วว่าไม่เป็นสมาชิก"
- บน non-cache path (โหลดครั้งแรก): `isLoading=true` → template ซ่อน `<template v-else-if="course">` → ไม่เกิด flash ✅
- บน cache path: `isLoading` ไม่ถูก set, course ยังอยู่ → button render ทันที → flash เกิด ❌

**[BUG-3] ✅ ยืนยันบางส่วน — `index.vue` + `authStore.user?.id`**
- `index.vue` มี `middleware: 'auth'` (line 13) ✅ → middleware await `fetchUser()` ก่อน render
- **ดังนั้น** `/users/undefined/...` ปกติจะไม่เกิดบน `index.vue` ผ่าน SPA navigation จาก login
- ⚠️ แต่ยังมีกรณีเสี่ยง: hard refresh บน `/Learn/Courses` → `app:mounted` plugin fetch user async → `onMounted` + `fetchEnrolledCourses()` fire ก่อน `fetchUser()` จบ (middleware ทำงาน server-side เท่านั้นใน Nuxt 3 SSR off → หรือถ้า SPA mode middleware วิ่งบน client แต่ onMounted อาจ race)
- ควรเพิ่ม guard `if (!userId) return` เพื่อความปลอดภัย แม้ไม่ใช่ bug หลัก

**[BUG-4] ✅ ยืนยัน — `auth.ts` plugin: `app:mounted` async race**
- Plugin fetch user ผ่าน `app:mounted` hook ซึ่งวิ่งหลัง mount — ไม่ block render
- หน้าที่ไม่มี `auth` middleware จะได้ `authStore.user = null` ระหว่าง `onMounted`
- ตัวอย่าง: component ที่ mount ใน layout หลักและเรียก API โดยใช้ user id

---

#### แผนแก้ไข (ปรับปรุงจาก user's plan)

**[FIX-1] แก้ root cause: เพิ่ม state ใน `course.ts` store (สำคัญที่สุด)**

`ui/stores/course.ts` — เพิ่ม state และ setter ใหม่:
```ts
// เพิ่ม state
const currentCourseMemberOfAuth = ref<any>(null)
const currentCourseGroups = ref<any[]>([])

// เพิ่ม setters
const setCourseMemberOfAuth = (member: any) => { currentCourseMemberOfAuth.value = member }
const setCourseGroups = (groups: any[]) => { currentCourseGroups.value = groups }
```

แก้ `fetchCourse()` ให้:
1. store `courseMemberOfAuth` และ `courseGroups` ใน state เมื่อ fetch สำเร็จ
2. cache hit return ครบทุก field:
```ts
// cache hit — return ครบ
return {
  success: true,
  course: currentCourse.value,
  academy: academy.value,
  isCourseAdmin: isCourseAdmin.value,
  courseMemberOfAuth: currentCourseMemberOfAuth.value,  // ← เพิ่ม
  courseGroups: currentCourseGroups.value,              // ← เพิ่ม
}
```

แก้ `clearCourse()` ให้ clear state ใหม่ด้วย:
```ts
currentCourseMemberOfAuth.value = null
currentCourseGroups.value = []
```

**[FIX-2] เพิ่ม `isCheckingMembership` prop ให้ `CourseActionButton.vue`**

เพิ่ม prop:
```ts
const props = defineProps({
  // ...existing...
  isCheckingMembership: { type: Boolean, default: false },
})
```

แก้ template: เมื่อ `isCheckingMembership=true` ให้แสดง skeleton/disabled button แทนปุ่ม "สมัครเรียน":
```html
<!-- Guard: กำลังตรวจสอบสถานะ -->
<button v-if="isCheckingMembership" disabled class="...opacity-50 cursor-wait...">
  <Icon icon="svg-spinners:ring-resize" />
  <span>กำลังตรวจสอบ...</span>
</button>
```

ใน `[id].vue` — pass prop:
```html
<CoursePageShell
  :is-checking-membership="isLoading && !course"
```

**[FIX-3] `[id].vue` — แยก `isCheckingMembership` ออกจาก `isLoading`**

เพิ่ม state:
```ts
const isCheckingMembership = ref(true)
```

ตั้งค่า:
- เมื่อเริ่ม `fetchCourse()`: `isCheckingMembership.value = true`
- เมื่อ `courseMemberOfAuth` ถูก set (ไม่ว่าจะเป็น null หรือ object): `isCheckingMembership.value = false`
- ต้องเป็น `false` เสมอหลัง `fetchCourse()` จบ ไม่ว่าผลจะเป็นอะไร

หลักการ: `null` มีสองความหมายต่างกัน — "ยังไม่รู้" vs "รู้แล้วว่าไม่เป็นสมาชิก" ต้องแยก state นี้ออก

**[FIX-4] `index.vue` — เพิ่ม guard ป้องกัน `/users/undefined/...`**

```ts
const fetchMyCourses = async () => {
  const userId = authStore.user?.id
  if (!userId) return  // ← เพิ่ม guard
  // ...
}

const fetchEnrolledCourses = async () => {
  const userId = authStore.user?.id
  if (!userId) return  // ← เพิ่ม guard
  // ...
}
```

เพิ่ม watcher เพื่อ refetch เมื่อ user พร้อมหลัง guard block ไป:
```ts
watch(() => authStore.user?.id, (id) => {
  if (!id) return
  if (activeTab.value === 'my') fetchMyCourses()
  else if (activeTab.value === 'enrolled') fetchEnrolledCourses()
}, { immediate: false })
```

**[FIX-5] `[id].vue` — Watcher รองรับ auth user พร้อมช้า**

เปลี่ยน `onMounted` จาก:
```ts
onMounted(() => { fetchCourse() })
```
เป็น:
```ts
const authStore = useAuthStore()

onMounted(() => {
  if (authStore.user) {
    fetchCourse()
  }
  // ถ้า user ยังไม่พร้อม รอ watcher ด้านล่าง
})

watch(() => authStore.user?.id, (id) => {
  if (id && !course.value) fetchCourse()
}, { immediate: false })
```

> หมายเหตุ: เนื่องจาก `[id].vue` มี `middleware: 'auth'` การ refactor นี้เป็น defensive measure เท่านั้น ไม่ใช่ critical fix

---

#### ลำดับการทำงาน (action items)

```
[ทำก่อน — แก้ root cause]:
  1. [FIX-1] course.ts — เพิ่ม state + setter + แก้ cache hit return + แก้ clearCourse()
     ไฟล์: ui/stores/course.ts
     ผลที่คาดหวัง: navigate กลับมาหน้า course ที่ cache valid → ปุ่มยังแสดงถูกต้อง

  2. [FIX-2] CourseActionButton.vue — เพิ่ม prop isCheckingMembership + UI loading state
     ไฟล์: ui/components/learn/course/v2/CourseActionButton.vue
     ผลที่คาดหวัง: ไม่มี false "สมัครเรียน" flash ระหว่างโหลด

  3. [FIX-3] [id].vue — เพิ่ม isCheckingMembership state + pass ลง CoursePageShell
     ไฟล์: ui/pages/Learn/Courses/[id].vue

[ทำถัดมา — defensive guards]:
  4. [FIX-4] index.vue — guard userId + watcher
     ไฟล์: ui/pages/Learn/Courses/index.vue

  5. [FIX-5] [id].vue — watcher user id + fetchCourse on user ready
     ไฟล์: ui/pages/Learn/Courses/[id].vue

[ทดสอบ]:
  6. ทดสอบ flow ทุกข้อที่ user ระบุ:
     - SPA navigate → course ที่สมัครแล้ว → ปุ่มต้องแสดง "เป็นสมาชิกแล้ว" ทุกครั้ง
     - Navigate ออกจากหน้า course → กลับมา (cache valid) → ปุ่มต้องถูกต้อง
     - Hard refresh → ปุ่มต้องถูกต้อง
     - Course ที่ไม่ได้สมัคร → ปุ่มต้องแสดง "สมัครเรียน" ถูกต้อง
     - ระหว่างโหลด → ต้องไม่เห็นปุ่ม "สมัครเรียน" กะพริบ
```

---

#### การแก้ไข user's plan (corrections)

| ข้อ | user's plan | สิ่งที่พบจากการตรวจ | ผลกระทบ |
|-----|------------|---------------------|---------|
| BUG-2 | ระบุเฉพาะ `courseMemberOfAuth` | `courseGroups` ก็หายจาก cache ด้วย — ต้อง fix พร้อมกัน | group selector modal ว่างเมื่อ cache hit |
| BUG-3 | บอกว่า index.vue ยิง `/users/undefined/...` | `index.vue` มี `middleware: 'auth'` → user พร้อมก่อน mount แน่นอนบน SPA nav | เป็น defensive fix ไม่ใช่ critical bug |
| FIX แนะนำ | ใช้ `ensureAuthUser()` หรือ `/api/me/...` | กรณี `index.vue` ไม่จำเป็น (มี middleware แล้ว) | เพิ่ม guard + watcher แทนดีกว่า — เปลี่ยน endpoint อาจ break API อื่น |
| FIX-3 | แก้ `[id].vue` อย่า set null จาก response ที่ไม่มี key | root fix ที่ดีกว่า: เก็บ state ใน store ไม่ใช่ local page ref | แก้ store ครั้งเดียว ได้ผลทุกที่ที่ใช้ store |

---

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

### 2026-06-04 - Codex AI office feasibility analysis
- Scope: plan-only analysis for applying an "AI office" concept to nuxnan.
- Findings: no existing first-class LLM/OpenAI/Gemini/Claude integration was found in Composer or package dependencies; current project has strong extension points in school management, academy/course dashboards, assignments, quizzes, notifications, chat, marketplace/wallet, audit logs, and Laravel Reverb realtime.
- Recommended direction: start with human-in-the-loop AI assistants for teachers/admins before autonomous actions; add an AI gateway/service layer, action approval logs, permission-aware context retrieval, and narrow domain agents such as teacher assistant, admin assistant, student support assistant, finance/store assistant, and analytics assistant.
- Risks: privacy for student data, hallucinated recommendations, cost control, permission leakage, and over-automation of grading or financial actions. Verification plan for future implementation should include policy tests, role/permission tests, prompt/action audit tests, and focused UI/API checks.

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

### 2026-06-05 - Codex restore user password change UI
- Scope: `ui/components/settings/AccountInfo.vue`, `ui/components/settings/Security.vue`, `ui/pages/profile/[id]/settings.vue`.
- Findings: `/api/settings/password` and `Security.vue` already exist, but password change was no longer shown inside account management; mobile settings navigation also referenced `showMobileSidebar` without declaring it after the horizontal tab scroller had been removed.
- Change: rendered the existing `Security.vue` password form under `AccountInfo.vue`, so users can change their password from the account tab again; renamed the account tab to include passwords, removed the duplicate standalone security tab from this settings page, and restored a mobile horizontal settings tab scroller.
- Verification: `git diff --check` passed for touched files; `php -l app\Http\Controllers\Api\SettingsController.php` passed; focused `vue-tsc` output filter for `components/settings` and `pages/profile/[id]/settings.vue` returned no errors. Broad `vue-tsc` still fails on known unrelated academy/course settings and `CourseLayout` casing issues.

### 2026-06-05 - Codex transcript feature analysis
- Scope: plan-only analysis for adding/finishing per-student academic transcripts alongside learner identity cleanup.
- Findings: transcript infrastructure already exists: `semester_transcripts`, `semester_transcript_items`, `annual_transcripts`, `course_grades`, `TranscriptController`, gradebook routes, PDF view, and Nuxt transcript pages. However, `routes/learn/gradebook.php` is not required from `routes/api.php` or loaded in `bootstrap/app.php`, so these endpoints may not be active. Some code also has contract drift: `TranscriptController` calls `Student::classroomStudents()` but `Student` exposes `classroomEnrollments()`/`classrooms()`, frontend/PDF use `student_number` while `students` has `student_id` plus classroom pivot `student_number`, and semester transcript responses include `gpax`/`total_accumulated_credits` fields that belong to annual transcripts.
- Recommended direction: treat this as "finish and integrate transcript module" rather than a new module from scratch. First register routes and repair backend contracts, then connect course completion/published grades to `course_grades`, then generate/publish semester transcripts and expose student/admin views.
- Intended files: `routes/api.php`, `routes/learn/gradebook.php`, `TranscriptController`, `GradebookController`, `SemesterTranscript`, `AnnualTranscript`, `Student`, PDF transcript view, `ui/pages/academies/[name]/my-transcript.vue`, admin gradebook transcript pages.
- Verification plan: route:list for transcript endpoints, PHP lint for touched controllers/models, feature tests for student own transcript/admin access/generate/publish/PDF authorization, and UI smoke tests for admin transcript overview plus student transcript page.

### 2026-06-05 - Claude course completion analysis (improved plan)
- Scope: plan analysis + verification for Course Completion system (grading workflow, certificates, appeals, remediation).
- Key correction: `course-completion.php` IS registered (via `bootstrap/app.php` then-callback) — routes are live, not 404. User's plan was wrong about route registration.
- Blocker #1: No `CoursePolicy` registered anywhere + `AppServiceProvider` has no Gate definitions → `$this->authorize('manage', $course)` throws 403 for ALL admin operations.
- Blocker #2: `calculateMemberTotalScore()` queries `gradebook_scores.course_member_id` but schema only has `student_id` FK → always returns 0 → all students get grade F on preview.
- Blocker #3: `User` implements `MustVerifyEmail` + routes use `verified` middleware → unverified users blocked from all completion endpoints.
- Gap: `finalization_status` ENUM = ['active','grading','finalized','archived'] but frontend handles 'published' label — mismatch (backend never sets 'published').
- Gap: No fallback to `CourseMember.achieved_score` when gradebook is empty — courses using quiz/assignment without gradebook setup get grade 0.
- Gap: No student-facing grade view page; no bridge `final_grade` → `CourseGrade` for transcript integration.
- Updated plan saved in User Analysis Input section (2026-06-05).

### 2026-06-05 - Claude transcript system analysis (improved plan)
- Scope: plan analysis + verification for Transcript/Gradebook system completion.
- Findings: 2 blockers found — (1) `routes/learn/gradebook.php` NOT required in `api.php` → all transcript/gradebook endpoints return 404; (2) `barryvdh/laravel-dompdf` NOT in composer.json → PDF download throws fatal error. Infrastructure is solid: migrations run, models exist, PDF template exists, `SemesterTranscript.calculate()` is complete, `CourseMember.grade_progress` → `CourseGrade` bridge exists via `publishGrades()`.
- Gaps: no admin UI for generate/review/publish; GradebookScore uses `student_id` FK (academy students only — non-academy users unserved); `calculateRanking()` silently skips if `classroom_id` null; `getMyTranscripts()` filters `status=published` only — students see nothing until admin publishes.
- Updated plan saved in User Analysis Input section (2026-06-05).

### 2026-06-05 - Claude learner identity reuse analysis (improved plan)
- Scope: plan analysis + bug discovery for learner identity reuse system.
- Findings:
  - 4 immediate bugs found: (1) `identity_data` missing from show/memberSettings/memberProgress → `effective_*` always null; (2) `updateOwnProfile()` nullifies fields not sent; (3) `storemember()` returns raw CourseMember not Resource; (4) `MyProgressDetails.vue` form doesn't show per-field source.
  - Design gaps: `autoPopulate()` writes fallback values to DB making it impossible to distinguish "explicit override" vs "fallback copy"; `identity_source` is a single field for 3 independently-sourced fields.
  - No `learner_identity_profiles` model/migration exists yet.
- Proposed additions: override flag columns (`is_name_override`, etc.) in course_members; per-field source tracking; `save_as_default` opt-in sync; `LearnerIdentityProfile` model; Phase 0 quick fixes before DB migration.
- Updated plan saved in User Analysis Input section (2026-06-05).
### 2026-06-05 - Codex course completion planning
- Scope: plan-only analysis after user decided to pause transcript work and prioritize course completion.
- Findings: existing completion infrastructure is substantial (`CourseCompletionController`, `CourseGradingService`, course finalization columns, `CourseMember.completion_status`, certificate flow, gradebook completion/certificate pages), but `routes/course-completion.php` is not required from `routes/api.php`, so `/api/courses/{course}/completion/*` and related certificate/remediation/report endpoints may be inactive. Existing grade calculation currently prefers `gradebook_scores`; if no gradebook rows exist, completion preview can return zero even when `CourseScoreService` can calculate scores from quizzes/assignments/lesson questions/external scores. Frontend has API contract drift: completion page expects `finalization_status=published` although backend enum has only `active`, `grading`, `finalized`, `archived`; certificates page calls endpoints such as `eligible`, `issue`, and `issue-all` that do not match backend routes (`generate`, `bulk-issue`, single certificate issue).
- Recommended direction: make `CourseMember` the source of truth for course completion state, split learning progress from grade/finalization state, register/repair completion routes first, centralize score calculation through `CourseScoreService`, then align Nuxt gradebook completion/certificate pages to the backend contract.
- Intended files: `routes/api.php`, `routes/course-completion.php`, `CourseCompletionController`, `CourseGradingService`, `CourseScoreService`, `CourseMember`, `CertificateController`, `CertificateService`, `ui/pages/Learn/Courses/[id]/gradebook/completion.vue`, `ui/pages/Learn/Courses/[id]/gradebook/certificates.vue`, `ui/composables/useCourseLearningProgress.ts`, `ui/composables/useMemberProgress.ts`.
- Verification plan: `php artisan route:list --path=completion`, `php artisan route:list --path=certificates`, PHP lint for touched controllers/services, focused feature tests for preview/publish/accept/finalize/certificate generation, and UI smoke tests on gradebook completion and certificates pages.
