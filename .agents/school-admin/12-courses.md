# 12 — คอร์สเรียน (จัดการรายวิชาของโรงเรียน)

> ไฟล์รองของเมนู **#12 คอร์สเรียน** ใน [OVERVIEW.md](OVERVIEW.md)
> อ่านคู่กับ OVERVIEW.md · สแกนโค้ดจริงเมื่อ 2026-10-07 (ยังไม่ส่ง step ไหนให้ agy)

## 1. Scope & Purpose

เมนูนี้คือหน้าที่ **admin โรงเรียนใช้บริหารคลังรายวิชา (course catalog) ของสถาบัน** — ไม่ใช่หน้าสร้างเนื้อหาในคอร์ส

**อยู่ในขอบเขต (catalog management):**
- ดูรายการคอร์สของโรงเรียน + ค้นหา + กรองสถานะ (เผยแพร่/ร่าง/เก็บถาวร) + กรองปี/ภาคเรียน/ระดับชั้น
- สร้างรายวิชาใหม่
- แก้ไขข้อมูลหลักของรายวิชา (ชื่อ/รหัส/คำอธิบาย/ปก/สถานะ)
- ลบรายวิชา
- ซื้อ Master Copy จากตลาด (marketplace) เข้าคลังโรงเรียน (clone)

**นอกขอบเขต (เป็นงานของเจ้าของคอร์สในหน้า `/courses/{course}` เดิม — ต้องยืนยัน Q1):**
- เนื้อหาในคอร์ส: บทเรียน/หัวข้อ/ข้อสอบ/การบ้าน/โพสต์/คะแนน/แต้ม/สมาชิกคอร์ส
  (controllers ใต้ `Api/Learn/Course/{lessons,quizzes,posts,scores,points,members}/` — คนละเมนู)

**ผู้ใช้ที่เกี่ยวข้อง:** owner/admin โรงเรียน · ครูผู้สอน (สร้าง/แก้คอร์สของตัวเอง) · ฝ่ายวิชาการ (department admin)

## 2. Current State (จากการสแกนโค้ดจริง 2026-10-07)

### Frontend
- Pages:
  - `ui/pages/academies/[name]/admin/courses/index.vue` (533 บรรทัด) — list 2 แท็บ: **คลังรายวิชาโรงเรียน** + **ตลาด Master Copy** · ค้นหา · กรองสถานะ · load more · ปุ่ม ดู/แก้/ลบ ต่อแถว
  - `ui/pages/academies/[name]/admin/courses/create.vue` (290 บรรทัด) — ฟอร์มสร้างคอร์ส → `POST /api/academies/{id}/courses`
  - 🔴 **ไม่มีหน้า edit** — ลิงก์ปุ่มแก้ชี้ `/academies/{name}/admin/courses/{id}/edit` แต่ไฟล์ไม่มี (ดู G2)
- Components: `components/academy/CourseMarketCard.vue` · `CourseMarketCardSkeleton.vue` · `CoursePurchaseModal.vue`
- API ที่หน้าเรียกจริง:
  - `GET /api/academies/{name}` (resolve academyId)
  - `GET /api/academies/{id}/courses?page&per_page&search&status` (list)
  - `GET /api/courses/marketplace?academy_id&page&search` (ตลาด)
  - `POST /api/academies/{id}/courses` (create, multipart — รองรับ cover)
  - ซื้อ Master Copy ผ่าน `CoursePurchaseModal`

### Backend
- Controller หลัก: `app/Http/Controllers/Api/Learn/Academy/AcademyCourseController.php`
  - `getAcademyCourses()` → `respondWithCourses()` → `buildCourseQuery()` (list + pagination + filters)
  - `store()` — สร้างคอร์ส (authz ภายใน: `isAdmin($user)` **หรือ** เป็นครูที่อนุมัติแล้ว)
  - `create()` / `show()` / `edit()` / `index()` — มีเมธอดแต่ route ฝั่ง API ใช้แค่บางตัว
- Controller เนื้อหาคอร์ส (นอกขอบเขตเมนูนี้): `Api/Learn/Course/**` (lessons/quizzes/posts/scores/points/members/...)
- Routes:
  - `routes/learn/academy.php:143` `GET /{academy:name}/courses/create` → `create` (ไม่มี permission guard)
  - `routes/learn/academy.php:144` `POST /{academy}/courses` → `store` (**ไม่มี permission middleware** — ดู G5)
  - `routes/learn/academy.php:184` `GET /{academy}/courses` → `getAcademyCourses` · middleware **`academy.visibility:courses`** (ไม่ใช่ `courses.view` — ดู G4)
  - โฟลว์เจ้าของคอร์ส (คนละหน้า): `routes/learn/course.php:113-115` `PUT/PATCH/DELETE /courses/{course}` → `CourseController@update/destroy` (middleware แค่ `auth:api`+`verified`, authz ภายใน)
- Models: `app/Models/Course.php`
  - `STATUS_PUBLISHED=1` · `STATUS_DRAFT=2` · `STATUS_ARCHIVED=3` · `STATUS_MAP` (string→tinyint) · mutator setStatus
  - `finalization_status` = source-of-truth ของสถานะหลังสอน (archived/finalized/published/grading)
  - `academy()` BelongsTo
- Permission keys: **มีครบใน** `AcademyPermission.php` + assign ใน `AcademyRole.php` / migration `2026_08_29_000002`:
  `courses.view` · `courses.manage` · `courses.create` · `courses.edit` · `courses.edit.own` · `courses.delete` · `courses.view.enrolled` · `courses.view.own`
  ⚠️ **แต่ route ของเมนูนี้ไม่ได้บังคับใช้ key เหล่านี้เลย** (ดู G4/G5)

### Database
- `courses` — `status` (tinyint 1/2/3) · `finalization_status` · `academy_id` · `user_id` · `instructor_id` · `code` · `name` · `description` · `cover` · `education_level` · `education_year` · `semester` · `academic_year` · `source_course_id` (Master Copy) · `duration` · `start_date`/`end_date`
- relation: `academy->courses()` · `course->courseLessons` · `course->courseMembers`

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | ดูรายการคอร์สของโรงเรียน (list + pagination) | ✅ | `getAcademyCourses` |
| 2 | ค้นหา (ชื่อ/รหัส/คำอธิบาย) | ✅ | `buildCourseQuery` |
| 3 | กรองสถานะ published/draft/archived | ✅ (BE) / ⚠️ (FE) | BE map 1/2/3 ถูก · FE แสดงป้ายผิด (G3) |
| 4 | กรองปี/ภาคเรียน/ระดับชั้น/scope | ✅ (BE) | FE ยังไม่มี UI ให้ (มีแต่ช่องสถานะ) |
| 5 | สร้างรายวิชาใหม่ | ⚠️ | ใช้งานได้ แต่ store บันทึกแค่ name/code/description/cover · ฟิลด์อื่น comment ไว้ (G7) |
| 6 | แก้ไขรายวิชา (จากหน้า admin) | ❌ | ไม่มีหน้า edit + ไม่มี endpoint admin (G2/G6) |
| 7 | ลบรายวิชา (จากหน้า admin) | ❌ | ปุ่มลบไม่มี handler (G1) · ไม่มี endpoint admin (G6) |
| 8 | ซื้อ Master Copy จากตลาด (clone) | ✅ | `CoursePurchaseModal` + async clone (queue) |
| 9 | ด่านสิทธิ์ตาม permission model (courses.view/manage) | ❌ | route ไม่ได้ gate ด้วย key (G4/G5) |

## 4. Permission Matrix (เป้าหมาย — ต้องยืนยัน Q2/Q3)

| Permission key | Owner | Admin | ฝ่ายวิชาการ (dept admin) | Teacher | Staff | Student | Guardian |
|---|---|---|---|---|---|---|---|
| `courses.view` (ดูคลังรายวิชาโรงเรียน) | ✅ | ✅ | ✅ (ในฝ่าย) | ✅ | ⚠️ | ❌ | ❌ |
| `courses.create` (สร้างคอร์ส) | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `courses.edit` / `courses.edit.own` | ✅ | ✅ | ✅ | ✅ (เฉพาะของตัวเอง) | ❌ | ❌ | ❌ |
| `courses.delete` | ✅ | ✅ | ✅ (ในฝ่าย?) | ⚠️ (ของตัวเอง?) | ❌ | ❌ | ❌ |

> ช่อง ⚠️ = ต้องให้เจ้าของโปรเจคเคาะ (ดู Q2/Q3)

## 5. Gap Analysis

- **G1 — ปุ่มลบคอร์สเป็นปุ่มตาย** · `index.vue:410-415` ปุ่มลบไม่มี `@click` ไม่มี handler ไม่เรียก API อะไรเลย
- **G2 — หน้าแก้ไขคอร์สไม่มีจริง** · ปุ่มแก้ลิงก์ไป `/academies/{name}/admin/courses/{id}/edit` แต่โฟลเดอร์มีแค่ `index.vue` + `create.vue` → กดแล้ว 404
- **G3 — FE แสดงสถานะผิด** · `getStatusLabel/getStatusBadge` map แค่ `'1'`/`'0'` + string · โมเดลจริงเป็น 1/2/3 ⇒ คอร์ส **draft (2)** และ **archived (3)** โชว์ "ไม่ทราบ" / ป้ายผิด (published=1 เท่านั้นที่ถูก)
- **G4 — ❌ ไม่ใช่ gap จริง (ตรวจแล้ว 2026-10-07)** · endpoint `GET /academies/{id}/courses` ถูกเรียกจาก **9 หน้า** รวมโปรไฟล์โรงเรียนสาธารณะ (`academies/[name].vue`) · dashboard นักเรียน/ครู/admin · schedule · allocations ⇒ เป็น endpoint **รายการคอร์สทั่วไป ไม่ใช่ admin-only** · `academy.visibility:courses` (คุม public/private ของโรงเรียน) คือด่านที่ **ถูกต้องแล้ว** — ถ้าเปลี่ยนเป็น `courses.view` (สิทธิ์ admin) หน้าสาธารณะ/นักเรียน/ครูจะพังทันที ⇒ **ไม่แตะ** · สิทธิ์ `courses.view` ของ permission model ใช้คุม "การเข้าถึงเมนู admin" ที่ระดับหน้า/route admin ไม่ใช่ที่ data endpoint นี้
- **G5 — create ไม่มี permission middleware (✅ by design ตาม Q3)** · `POST /{academy}/courses` authz ภายในเช็ค `isAdmin || ครูที่อนุมัติ` · เจ้าของเคาะ Q3 แล้วว่า **ครูทุกคนที่อนุมัติสร้างได้** ⇒ พฤติกรรมปัจจุบันถูกต้อง ไม่ต้องเพิ่ม gate (เก็บไว้เป็นบันทึก ไม่ใช่งานแก้)
- **G6 — ไม่มี endpoint แก้/ลบคอร์สในโฟลว์ admin** · การแก้/ลบทำได้แค่ผ่านหน้าเจ้าของคอร์ส (`PUT/DELETE /courses/{course}`) ⇒ เมนู admin ยังไม่มีทางแก้/ลบที่ใช้ได้ (คู่กับ G1/G2) · ต้องตัดสินว่า (ก) ชี้ปุ่ม admin ไปใช้ endpoint เจ้าของคอร์ส + เพิ่ม authz ให้ academy admin แก้/ลบของครูคนอื่นได้ หรือ (ข) เพิ่ม endpoint admin-scoped ใหม่
- **G7 — store บันทึกฟิลด์ไม่ครบ** · `store()` comment ฟิลด์ส่วนใหญ่ทิ้ง (status/level/credit/dates/price/saleable) · บันทึกจริงแค่ name/code/description/cover ⇒ สร้างคอร์สได้แบบ minimal · สถานะเริ่มต้นไม่ได้เซ็ตชัดเจนตอนสร้าง

## 6. คำถามที่เจ้าของโปรเจคเคาะแล้ว (2026-10-07)

- **Q1 — ขอบเขตเมนู:** ✅ **แค่ catalog** (สร้าง/แก้ข้อมูลหลัก/สถานะ/ลบ รายการคอร์ส) · เนื้อหาในคอร์ส (บทเรียน/ข้อสอบ) เป็นงานหน้าเจ้าของคอร์ส `/courses/{course}` ตามเดิม ⇒ เมนูนี้ไม่แตะ `Api/Learn/Course/**`
- **Q2 — สิทธิ์แก้/ลบ:** ✅ **academy admin แก้/ลบคอร์สของครูคนอื่นได้** · ครูยังแก้/ลบของตัวเองได้ (`courses.edit.own`) · (การลบคอร์สที่มีนักเรียน: ใช้พฤติกรรม `CourseController@destroy` เดิมไปก่อน — ถ้าเจอว่าลบ hard แล้วเสี่ยง จะเสนอ archive เพิ่มใน CO-S3)
- **Q3 — สิทธิ์สร้าง:** ✅ **ครูทุกคนที่อนุมัติสร้างได้** (ตามโค้ด `store()` ปัจจุบัน) ⇒ ไม่ต้องเพิ่ม `courses.create` gate ที่ route create · CO-S1 โฟกัสที่ **list (G4)** + ทำ authz ของ **แก้/ลบ (G6)** ให้รองรับ academy admin

## 7. Implementation Tasks (ส่งให้ agy ทีละ step — ยังไม่เริ่ม)

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| CO-S1 | **แก้ authz ของ `CourseController@update/destroy` ให้ academy admin แก้/ลบคอร์สของครูคนอื่นในโรงเรียนตัวเองได้** (G6, ตาม Q2) · ครูยังแก้/ลบของตัวเอง · กันข้ามโรงเรียน | G6, Q2 | BE authz + test | ⚪ pending |
| CO-S2 | แก้ FE สถานะให้ตรง 1/2/3 (label/badge) + mobile-first (G3) | — | `index.vue` | 🟢 verified (2026-10-07) |
| CO-S3 | ทำปุ่มลบให้ทำงาน (handler + confirm SweetAlert + เรียก `DELETE /courses/{id}` + ลบออกจาก list) (G1) | CO-S1 | FE | ⚪ pending |
| CO-S4 | สร้างหน้าแก้ไข `admin/courses/[id]/edit.vue` + ต่อ `PATCH /courses/{id}` (G2) | CO-S1 | FE page | ⚪ pending |
| CO-S5 | เปิดฟิลด์คอร์สที่ store comment ไว้ให้บันทึกจริง (status/level/ภาคเรียน ฯลฯ) ตามที่ฟอร์มรองรับ (G7) | — | `store()` + create.vue | ⚪ pending |
| CO-S6 | ชุดเทสต์ happy-path (create/edit/delete/filter/authz ข้ามโรงเรียน) บน MySQL | CO-S1..S5 | test suite | ⚪ pending |

> **หมายเหตุ re-scope 2026-10-07:** G4 (ปิด list ด้วย courses.view) ถูกตัดทิ้ง — endpoint list เป็น shared (9 หน้า) `visibility:courses` ถูกแล้ว · G5 (create gate) by design ตาม Q3 ⇒ CO-S1 เดิมที่เป็น "ปิด list/create" ไม่ทำแล้ว เปลี่ยนเป็น authz แก้/ลบของ admin แทน

**Rule:** ทุก step ต้อง verify (build/test/manual browser 375px) ก่อนขึ้น 🟢 · รายงาน agy เชื่อไม่ได้ ต้อง `git diff` + รันเกณฑ์เอง

## 8. Codex/agy Prompt Template (ต่อ step)
```
Context: .agents/school-admin/12-courses.md §<step-id>
Working dir: C:\wamp64\www\nuxnan
Files touched (expected): <รายการ>
Task: <what to do>
Constraints: mobile-first (375px ก่อน · 44px touch · min-w-0 break-words) · ไม่แตะเนื้อหาคอร์ส (lessons/quizzes) · ไม่ลบไฟล์นอกสเปค
Verification: <build/test/manual>
Report back: <diff + ผลเกณฑ์>
```

## 9. Review Log
- **2026-10-07** — ขั้น [1] สแกนโค้ด + [2] เขียนไฟล์รองนี้ เสร็จ (claude) · พบ gap G1–G7 · ยังไม่ส่ง step ไหนให้ agy · รอเจ้าของเคาะ Q1–Q3 ก่อนเริ่ม CO-S1
