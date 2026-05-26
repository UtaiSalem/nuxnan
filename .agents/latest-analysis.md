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

**สถานะ:** 📋 READY TO IMPLEMENT (2026-05-25)

**ไฟล์เดียว:** `ui/pages/Play/Games/typing/index.vue`

---

#### ปัญหาที่วิเคราะห์ได้:

| ปัญหา | Line | สาเหตุ |
|---|---|---|
| Difficulty 5 cols ใน sidebar 304px | 144 | `sm:grid-cols-5` fire ที่ 640px แต่ไม่มี override ที่ `lg` ที่ layout เปลี่ยนเป็น sidebar |
| Card padding แน่น mobile | 114 | `p-8` คงที่ ไม่ responsive |
| Language buttons บีบ | 127-138 | `flex-1` ใน flex container แคบ |
| CTA ใหญ่เกิน mobile | 162 | `py-5 text-xl` คงที่ |
| Outer gap บีบ sidebar | 75 | `gap-12` (48px × 2 = 96px หาย) |

#### ขั้นตอน implement:

**ขั้นที่ 1** — Outer grid gap ลด (line 75)
```diff
- <div class="grid lg:grid-cols-3 gap-12">
+ <div class="grid lg:grid-cols-3 gap-6 lg:gap-8">
```

**ขั้นที่ 2** — Settings column spacing (line 111)
```diff
- <div class="space-y-8">
+ <div class="space-y-4 lg:space-y-6">
```

**ขั้นที่ 3** — Card padding + inner spacing (line 114)
```diff
- <div class="... p-8 ... space-y-8">
+ <div class="... p-5 sm:p-6 lg:p-7 ... space-y-6 lg:space-y-7">
```

**ขั้นที่ 4** — Language: flex → grid (lines 127-138)
```diff
- <div class="flex gap-2">
+ <div class="grid grid-cols-3 gap-2">
  <button
-   class="flex-1 py-3 px-4 rounded-xl ..."
+   class="py-3 px-2 rounded-xl ... min-w-0 whitespace-nowrap overflow-hidden"
  >
    {{ lang.flag }} {{ lang.name }}
```

**ขั้นที่ 5** — Difficulty: เพิ่ม lg override **(สำคัญที่สุด)** (line 144)
```diff
- <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
+ <div class="grid grid-cols-2 sm:grid-cols-5 lg:grid-cols-2 xl:grid-cols-5 gap-2">
  <button
-   class="py-3 px-2 rounded-xl ... text-sm ..."
+   class="py-2.5 px-1.5 rounded-xl ... min-h-[58px] ..."
  >
-   <span>{{ diff.name }}</span>
-   <span class="text-[10px] opacity-60 font-medium leading-tight mt-1">{{ diff.desc }}</span>
+   <span class="whitespace-nowrap text-xs sm:text-sm">{{ diff.name }}</span>
+   <span class="text-[9px] sm:text-[10px] opacity-60 font-medium leading-tight mt-1 break-words">{{ diff.desc }}</span>
```

**ขั้นที่ 6** — CTA button (line 162-163)
```diff
- class="w-full py-5 ... font-black text-xl rounded-2xl ..."
+ class="w-full py-4 sm:py-5 ... font-black text-base sm:text-xl rounded-2xl ..."
```

#### Verification Plan:

| Viewport | เช็คอะไร |
|---|---|
| 360px | 2-col difficulty, 3-col language (1 row), card ไม่แน่น, CTA ไม่ใหญ่เกิน |
| 640px (sm) | 5-col difficulty กว้างพอ (full width card), language ยังอยู่ 1 row |
| 1024px (lg) | difficulty **กลับมา 2 cols** ใน sidebar ≈304px, language 3 cols ≈101px/col ok |
| 1280px (xl) | difficulty **expand เป็น 5 cols** ใน sidebar ≈389px ≈ 71px/col ok |
| 1440px+ | comfortable, ไม่มีอะไรล้น |

---

### Feature: Remediation & Unified Eligibility

**สถานะ:** ✅ COMPLETED (2026-05-24)

---

#### ผลลัพธ์ที่ได้:

1.  **Route Alignment:** แก้ไข `remediation.vue` ให้ตรงกับ backend:
    *   ใช้ `/api/courses/{course}/remediation` แทน `/remediation/sessions`
    *   แมพฟิลด์ `title`, `start_at`, `remediation_score` ให้ตรงกับ DB
2.  **Backend bulkEnroll:** เพิ่ม method `bulkEnroll` ใน `RemediationController` และเพิ่ม parameter `$force` ใน `RemediationService` เพื่อให้ admin ลงทะเบียนนักเรียนได้โดยไม่ติดเงื่อนไขเวลา
3.  **Unified Eligibility Panel:** สร้างคอมโพเนนต์ `ExamEligibilityPanel.vue` และฝังในหน้า Quiz เพื่อรวมทุกช่องทางคืนสิทธิ์สอบ (Self, Points, Reading, Appeal) ไว้ในที่เดียว

---

#### ตรวจสอบความถูกต้อง:

- [x] `npm run build` ผ่านฉลุย
- [x] หน้า Remediation โหลดข้อมูลได้ถูกต้อง ไม่ขึ้น Error route mismatch
- [x] ปุ่ม "ลงทะเบียนนักเรียน" ใน Remediation ทำงานได้จริง (Bulk enroll)
- [x] หน้า Quiz แสดง Panel สีแดงเมื่อไม่มีสิทธิ์สอบ พร้อมปุ่มปลดล็อคตามเงื่อนไข

---

### Feature: Course Info Page — แก้ accordion "เนื้อหาบทเรียน"

**สถานะ:** ✅ COMPLETED (2026-05-24)

**ไฟล์ที่แก้ (เรียงตามลำดับ):**

| ลำดับ | ไฟล์ | การเปลี่ยน | สถานะ |
|---|---|---|---|
| 1 | `ui/pages/Learn/Courses/[id]/index.vue` | แก้ `.name` → `.title`, ใช้ `min_read` แทน `duration` | ✅ |
| 2 | `api/.../CourseActivityController.php` | เพิ่ม `withCount('topics')` + `with('topics:id,lesson_id,title,order,status')` | ✅ |
| 3 | `api/.../CourseResource.php` | map inline แทน raw `$this->courseLessons` | ✅ |
| 4 | `api/.../CourseActivityController.php` | แก้ `return back()` → `return response()->json(...)` ใน catch | ✅ |
| 5 | `ui/pages/Learn/Courses/[id]/index.vue` | UX accordion: link ไป lesson, empty state, จำนวนหัวข้อจริง | ✅ |

---

### Feature: Exam Retake Flow — Quiz-Level Link & Frontend UX

**สถานะ:** 🔄 IN PROGRESS (Phase 1 COMPLETED) (2026-05-24)

---

#### ผลลัพธ์ที่ได้ (Phase 1):

1.  **DB Schema:** เพิ่ม `quiz_id` ใน `course_remediation_sessions` เพื่อผูกรอบแก้ตัวเข้ากับข้อสอบที่ต้องการให้ retake
2.  **Model & Controller:** อัพเดท validation และ relationship ให้รองรับการผูก Quiz
3.  **Student UX:** หน้า Quiz แสดง `remediation_status` card อัตโนมัติเมื่อมีรอบแก้ตัวที่เกี่ยวข้อง ทำให้นักเรียนรู้ว่าควรทำอะไรต่อ (ลงทะเบียน/รอผล/สอบใหม่ได้)
4.  **Admin UX:** หน้า Remediation Gradebook เพิ่ม dropdown ให้เลือกผูก Quiz ตอนสร้างรอบใหม่

---

#### แผนถัดไป (Phase 2 — Authorization Logic & Retake Implementation):

1.  **RemediationService:** เมื่อครูให้เกรด `passed` → เรียก logic เพื่อ `unlockQuizRetake` (เพิ่ม attempt หรือ reset counter)
2.  **ExamEligibilityService:** เพิ่มการตรวจสอบสิทธิ์ retake จากสถานะการผ่าน remediation
3.  **Frontend Retake Button:** ปรับปรุงปุ่ม "เริ่มทำแบบทดสอบ" ให้รองรับกรณีที่ได้สิทธิ์ใหม่จากการแก้ตัว

---

#### การวิเคราะห์โค้ดปัจจุบัน (2026-05-24):

| สิ่งที่มีอยู่แล้ว | ไฟล์ | หมายเหตุ |
|---|---|---|
| `gradeEnrollment()` อัพเดท `final_grade` + `completion_status` | `RemediationService.php` | ✅ สมบูรณ์ |
| `unlockByRemediation()` ถูกเรียกเมื่อ passed | `RemediationService.php:244` | ✅ สมบูรณ์ |
| `GradeEditLog` บันทึกทุก grade change | `RemediationService.php:219` | ✅ สมบูรณ์ |
| `bulkEnroll`, `grade`, `bulkGrade`, `complete` endpoints | `RemediationController.php` | ✅ สมบูรณ์ |
| All unlock channels (self/points/reading/appeal/admin) | `ExamEligibilityController.php` | ✅ สมบูรณ์ |

| ช่องว่างที่ยังขาด | ผลกระทบ |
|---|---|
| `course_remediation_sessions` ไม่มี `quiz_id` | ไม่รู้ว่า session เพื่อ retake quiz ใด |
| Quiz controller ไม่มีตรรกะ "ผ่าน remediation → อนุญาต attempt เพิ่ม" | นักเรียนผ่านแล้วก็ยังเข้าสอบไม่ได้ |
| Frontend quiz page ไม่รู้สถานะ remediation enrollment | ไม่แสดง "มี remediation รอ" หรือ "ผ่านแล้ว retake ได้" |

---

#### แผน Phase 1 — Backend: DB + Retake Authorization

**ขั้นที่ 1** — Migration: เพิ่ม `quiz_id` ใน `course_remediation_sessions`
```
php artisan make:migration add_quiz_id_to_course_remediation_sessions_table
```
- เพิ่ม `quiz_id` (nullable, FK → `quizzes.id`, onDelete SET NULL)
- ใช้ nullable เพื่อ backward compatible กับ sessions ที่มีอยู่แล้ว

**ขั้นที่ 2** — `RemediationController::store()` + `update()`: รับ `quiz_id` ได้
- เพิ่ม `'quiz_id' => 'nullable|exists:quizzes,id'` ใน validation
- บันทึก `quiz_id` เข้า session

**ขั้นที่ 3** — `RemediationService::gradeEnrollment()`: เมื่อ passed + มี `quiz_id`
- เพิ่ม logic: ถ้า `$session->quiz_id` มีค่าและ `$enrollment->status === STATUS_PASSED`
  → เรียก `unlockQuizRetake($member, $session->quiz_id)`
- Method ใหม่ `unlockQuizRetake`: อัพเดท `quiz_attempts` ให้ student (เพิ่ม 1 attempt หรือ unlock flag)

**ขั้นที่ 4** — Quiz Controller: ตรวจสอบ retake eligibility
- ใน `show()` หรือ `attempt()` endpoint: เช็คว่า student มี `CourseRemediationEnrollment` ที่ `status = 'passed'` สำหรับ quiz นี้หรือไม่
- Return `can_retake: true` พร้อม `retake_source: 'remediation'`

---

#### แผน Phase 2 — Frontend: Quiz Page + Remediation Status

**ขั้นที่ 5** — `ui/pages/Learn/Courses/[id]/quizzes/[quizId]/index.vue`
- เพิ่ม API call: `GET /api/courses/{course}/remediation?quiz_id={quizId}` เพื่อดึง session ที่เชื่อมกับ quiz นี้
- แสดง badge/card ถ้า student ลงทะเบียน remediation อยู่: `"📋 คุณมีรอบแก้ตัวที่รอ — [ดูรายละเอียด]"`
- แสดง success state ถ้า `can_retake = true`: `"✅ ผ่านการแก้ตัวแล้ว — เริ่มสอบได้เลย"`

**ขั้นที่ 6** — `ui/pages/Learn/Courses/[id]/gradebook/remediation.vue`
- ตอนสร้าง session: เพิ่ม dropdown เลือก quiz ที่ต้องการ retake (filter เฉพาะ quiz ใน course)
- แสดง `quiz_id` ที่ผูกอยู่ใน session list

---

#### Verification Plan

| ขั้น | Test |
|---|---|
| 1 | `php artisan migrate` ไม่มี error; column `quiz_id` มีใน table |
| 2 | `POST /api/courses/{course}/remediation` รับ `quiz_id` ได้ |
| 3 | Grade enrollment → status `passed` → ตรวจสอบ quiz retake flag ใน DB |
| 4 | Quiz endpoint คืน `can_retake: true` สำหรับ student ที่ผ่าน remediation |
| 5 | Frontend quiz page แสดง remediation card ตาม status |
| 6 | `npm run build` ผ่าน |

---

### Feature: Compact Lesson Order Widget — Polish 3 จุด

**สถานะ:** ✅ COMPLETED

---

#### จุดที่ 1 ✅ — แก้ UX Flash เมื่อบันทึกลำดับ

**ผลลัพธ์:** เพิ่ม `silent` parameter ให้ `fetchLessons` เพื่อไม่ให้ `isLoading` เป็น true (ซึ่งจะไป trigger ContentLoader) เมื่อเป็นการอัพเดทข้อมูลหลังบ้านแบบเงียบๆ

#### จุดที่ 2 ✅ — ซ่อน Widget เมื่อมี ≤ 1 บทเรียน

**ผลลัพธ์:** เพิ่ม `v-if="lessons.length > 1"` ที่ wrapper ของ `LessonOrderWidget`

#### จุดที่ 3 ✅ — Collapsible Widget

**ผลลัพธ์:** เพิ่ม `isOpen` state, ทำให้ header คลิกได้เพื่อ toggle, และแสดง draggable เฉพาะเมื่อเปิดอยู่ พร้อมไอคอน chevron แสดงสถานะ

---

### Hotfix: Course Feeds 500 Error

**สถานะ:** ✅ COMPLETED (2026-05-24)

---

#### ปัญหา:
`GET /api/courses/{id}/feeds` คืนค่า 500 Internal Server Error เนื่องจากพยายามดึงและเรียงลำดับด้วยคอลัมน์ `order` ในตาราง `topics` ซึ่งไม่มีอยู่จริงในฐานข้อมูล

#### สิ่งที่แก้ไข:
1.  **CourseActivityController:** นำ `'order'` ออกจาก query select และเปลี่ยน `orderBy('order')` เป็น `orderBy('id')` สำหรับ topics
2.  **CourseResource:** นำการแมพฟิลด์ `'order'` ออกจาก topics array

---

## Current Snapshot

- Date: 2026-05-25
- Branch: main
- Repository: `C:\wamp64\www\nuxnan`
- Frontend: `ui/` Nuxt/Vue/TypeScript/Pinia/Tailwind/PrimeVue
- Backend: `api/nuxnanravel/` Laravel/PHP/JWT/MySQL/Reverb
- Current focus: Typing Game Settings — Responsive layout fix
- Pending commit: งานสะสม (Lesson Widget, Curriculum Fixes, Remediation, Eligibility Panel)

## Active Work

| Scope | Owner | Status | Files | Notes |
| --- | --- | --- | --- | --- |
| Typing Game Settings responsive fix | — | 📋 Ready to implement | `ui/pages/Play/Games/typing/index.vue` | Critical breakpoint conflict lg:grid-cols-2 xl:grid-cols-5 |
| Exam retake flow — Phase 1 (DB + Backend) | — | 📋 Ready to implement | migration, RemediationController, quiz controller | เพิ่ม quiz_id link + retake authorization |
| Exam retake flow — Phase 2 (Frontend) | — | 📋 Planned | remediation.vue, [quizId]/index.vue | แสดง remediation status ใน quiz page |

## Coordination Board

| Claim ID | Owner | Scope | Files or folders | Status | Handoff note |
| --- | --- | --- | --- | --- | --- |

## Decisions And Assumptions

- `AGENTS.md` is the tool-agnostic root entry point for all AI agents.
- `.agents/latest-analysis.md` is the live analysis and coordination board.
- `.agents/worklog.md` remains the cross-session handoff log.
- `CLAUDE.md` remains Claude-specific historical/project guidance.

## Open Questions

1. **Quiz Attempt Limit**: ตอนนี้ quiz มี `max_attempts` field ไหม? ถ้ามีแล้ว — remediation ควร add 1 attempt หรือ reset counter เลย?
2. **Remediation → Quiz link granularity**: 1 remediation session เชื่อมได้ 1 quiz หรือหลาย quiz? (สมมติ 1 ต่อ 1 ไปก่อน)
3. **Pending commit**: ควร commit งานที่สะสมไว้ก่อนเริ่ม Phase 1 เพื่อ isolate changes

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

### 2026-05-27 - Typing Classroom Race improvement plan
- User asked to plan improvements for `http://localhost:3000/Play/Games/typing/race` and shared Vue warning: `Component inside <Transition> renders non-element root node that cannot be animated`.
- Read-only inspection found the race page at `ui/pages/Play/Games/typing/race.vue`; it uses `definePageMeta({ layout: false })` and returns `<NuxtLayout name="main">` as the template root, matching the warning stack through Nuxt page transitions. Related typing pages (`typing/index.vue`, `typing/play.vue`) use the same pattern and may emit the same warning during navigation.
- Browser smoke opened the race URL successfully and rendered `Classroom Race`; console capture did not reproduce the warning on direct load, so verification should include navigation from `/Play/Games/typing` to `/Play/Games/typing/race`.
- Frontend improvement scope: wrap page templates in a stable single HTML root around `<NuxtLayout>` or switch to `definePageMeta({ layout: 'main' })` and remove manual `<NuxtLayout>` usage consistently for typing pages; then polish race responsive layout, button states, loading/error messages, and host/join flows.
- Runtime/race-flow risks found: `useClassroomRace.ts` does not surface API errors to UI, does not reset `joinCode`/loading state, leaves participants as `left` without server leave endpoint, starts local countdown from broadcast only, and depends on Echo presence whispers for progress. Backend only finalizes when all participants submit, so a left/disconnected racer can leave a room stuck in `racing`.
- Backend improvement candidates: add room leave/heartbeat or timeout handling, ensure submit/finalize ignores left participants, return normalized participant DTOs, add validation messages for full/not-found/started rooms, and consider host-only max player/config controls.
- Intended files: `ui/pages/Play/Games/typing/race.vue`, `ui/pages/Play/Games/typing/index.vue`, `ui/pages/Play/Games/typing/play.vue`, `ui/composables/useClassroomRace.ts`, `ui/components/games/typing/ui/RaceTrack.vue`, `api/nuxnanravel/app/Http/Controllers/Api/Play/Typing/TypingRaceController.php`, `api/nuxnanravel/routes/channels.php`, `api/nuxnanravel/app/Events/TypingRaceStarted.php`, and `TypingRaceFinished.php`.
- Verification plan: `npm run build`; browser smoke direct URL and navigation `/typing` -> `/typing/race`; create room and invalid join code; two-user/manual or mocked Echo test for lobby/start/progress/result; Laravel feature tests for create/join/start/submit/finalize/full room/leave or timeout behavior.
