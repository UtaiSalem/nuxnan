# Spec — ผูกรายวิชาส่วนตัวเข้าสังกัดโรงเรียน (Course ↔ Academy affiliation)

> สถานะ: **แผน · รอเจ้าของเคาะ Q1–Q8 ก่อน implement** · เขียน 2026-11-03
> ขอบเขต: ครูที่สร้างรายวิชาเอง (personal course, `courses.academy_id = NULL`) และเป็นครูของโรงเรียน
> เลือกให้รายวิชานั้นไปอยู่ใต้สังกัดโรงเรียนได้ (ตอนสร้าง และ/หรือ ย้ายภายหลัง)

---

## 1. สภาพปัจจุบัน (สำรวจจากโค้ดจริง)

### 1.1 ความสัมพันธ์ในฐานข้อมูล — มีอยู่แล้ว ไม่ต้องเพิ่มคอลัมน์
- `courses.academy_id` (nullable) คือตัวผูก · `Course::academy()` BelongsTo · `Academy::courses()` HasMany
- personal course = `academy_id IS NULL` · ไม่มีตาราง pivot / ตารางคำขอผูกใด ๆ
- `academies.courses_offered` = ตัวนับแบบ denormalized (เพิ่มเฉพาะใน `AcademyCourseController::store` บรรทัด ~130
  · มี `courses:backfill-academy` ไว้ resync) → ผูก/ถอนเมื่อไรต้อง resync ตัวนี้
- ข้อมูล dev DB (2026-11-03): คอร์ส 25 · มีสังกัด 23 · ส่วนตัว 2 (1 ใน 2 เป็นของครูที่เป็นสมาชิกโรงเรียน)
  · คอร์สที่มีสังกัดแต่เจ้าของไม่ใช่ครู/แอดมินของโรงเรียนนั้น = 0
- precedent: `app/Console/Commands/BackfillCourseAcademy.php` — เคยต้องผูกด้วยมือผ่าน console มาแล้ว (คอร์ส 22–25 → academy 1)
  = หลักฐานว่าฟีเจอร์นี้มีความต้องการจริง

### 1.2 เส้นทางสร้างรายวิชามี 2 เส้น
| เส้น | Route | Controller | ตรวจสิทธิ์โรงเรียน |
|---|---|---|---|
| ส่วนตัว | `POST /api/courses` | `Api/Learn/Course/info/CourseController::store` (~528) | ❌ ไม่ตรวจ — รับ `academy_id` `nullable` ดิบ ๆ |
| โรงเรียน | `POST /api/academies/{academy}/courses` | `Api/Learn/Academy/AcademyCourseController::store` (36) | ✅ `isAdmin()` หรือ member status 2 + `isTeacher()`/`role==='teacher'` |

- FE เส้นส่วนตัว: `ui/pages/Learn/Courses/create.vue` → `ui/pages/Learn/Course/CreateNewCourse.vue`
  ส่ง `academy_id: ''` เสมอ (บรรทัด 235) **ไม่มีตัวเลือกโรงเรียนใน UI** (`''` ถูก middleware แปลงเป็น null)
- `ui/components/learn/course/CourseSettings.vue` มี `<select v-model="form.academy_id">` + `fetchMyAcademies()` แต่
  **เป็นโค้ดตาย** (ยังใช้ `axios`/`usePage()` ของ Inertia · ไม่มีหน้าไหน mount) — ห้ามใช้เป็นฐาน

### 1.3 หน้าตั้งค่า `ui/pages/Learn/Courses/[id]/settings.vue`
- form สร้างจาก course ใน watcher (~234) — **ไม่มี `academy_id`** ในฟอร์ม → PUT ไม่เขียนทับสังกัด (ดี)
- บันทึกผ่าน `api.put('/api/courses/{id}')` (ทั้งหน้า) และ `saveSettingsSection` (ราย section ตาม `sectionFields` ~96)
- session ต้นทางเพิ่งแก้ไฟล์นี้ (ปุ่มบันทึกข้อมูลทั่วไป) → แผนนี้**ไม่แก้ตรรกะเดิม** แค่แปะ component ใหม่ 1 บรรทัด

### 1.4 ตัวช่วยตรวจสมาชิกที่มีอยู่
- `Academy::isAdmin($user)` (Academy.php:218) = super admin / เจ้าของ (`academies.user_id`) / มีแถวใน `academy_admins`
  — **เจ้าของโรงเรียนไม่มีแถวใน `academy_members`** ต้องผ่านเมธอดนี้เสมอ
- `AcademyMember::STATUS_APPROVED = 2` · `isTeacher()` ผ่าน `academyRole->isTeacherRole()` · legacy `role === 'teacher'`
- ตรรกะ "ครูของโรงเรียนนี้" มีอยู่แล้วแต่ **inline** ใน `AcademyCourseController::store` → ควรยกเป็นเมธอดบนโมเดลเพื่อใช้ซ้ำ
- `GET /api/academies/users/{user}/my-academies` (`AcademyController::getMyAcademies`) = `user->academies()` (โรงเรียนที่**เป็นเจ้าของ**)
  paginate 10 — **ไม่ครอบครูสมาชิก** ใช้เป็นรายการตัวเลือกไม่ได้

### 1.5 สิ่งที่ "เปลี่ยนตาม" เมื่อ `academy_id` ถูกตั้ง (ผลข้างเคียงของการผูก)
ทุกจุดอ่าน `course->academy_id` แบบ dynamic → ผูกแล้วมีผลทันที ไม่ต้องย้ายข้อมูล:
| ด้าน | จุดในโค้ด | ผลเมื่อผูก |
|---|---|---|
| สิทธิ์แอดมินโรงเรียน | `CourseController::update` (674) / `destroy` (872) | แอดมินโรงเรียน**แก้ไข/ลบ**รายวิชาได้ทันที |
| แคตตาล็อก/รายงานโรงเรียน | `AcademyCourseController::buildCourseQuery`, `ReportController:43`, `AnalyticsController:150`, `PublicAcademyController:62` | ขึ้นในรายการ/ตัวนับของโรงเรียน |
| ดึงนักเรียนจากห้องเรียน | `CourseGroupClassroomController` + `CourseClassroomRosterService` | เปิดใช้ได้ (ก่อนผูก = 422 "ไม่ได้สังกัดโรงเรียน") · ครูทั่วไปเห็นเฉพาะห้องที่ตัวเองเป็นครูประจำชั้น |
| หลักสูตร | `CurriculumController:643` | เลือกเข้าหลักสูตรโรงเรียนได้ |
| XP โรงเรียน | `CourseMemberObserver`, `AssignmentAnswerObserver` | จบคอร์ส/ส่งงาน ให้ XP โรงเรียน |
| ผลการเรียน/ตัวตนผู้เรียน | `CourseGradingService:386`, `CourseGrade:190`, `LearnerIdentityService:44` | ผูกเลขประจำตัว/ปีการศึกษาของโรงเรียน |
| จัดสรรแต้มโรงเรียน | `AcademyAllocationService:24` | โรงเรียนโอนแต้มให้คอร์สได้ |
| แคมเปญ/โฆษณา | `CampaignAuthorizationService:26`, `StoreCampaignRequest:69`, `CoursePointCampaignController:80` | ขอบเขตแคมเปญเปลี่ยนเป็นของโรงเรียน |
| โพสต์ | `CoursePostController:112` | โพสต์**ใหม่**ติด `academy_id` (โพสต์เก่าคง null) |
| ตลาด (ฝั่งผู้ซื้อ) | `CourseMarketplaceController:134`, `CoursePurchaseService:51` | ใช้ `academy_id` ของ**คอร์สปลายทางที่ clone** ไม่ใช่คอร์สต้นทาง → การผูกคอร์สต้นทางไม่กระทบ |
| ค่าเรียน | `CourseEnrollmentPaymentService:186` | เงินเข้า `course->user` (เจ้าของคอร์ส) **เสมอ** ไม่ว่าจะมีสังกัดหรือไม่ → ผูกแล้ว**ไม่เปลี่ยนกระแสเงิน** |

---

## 2. ช่องโหว่ที่เจอระหว่างสำรวจ (ต้องปิดพร้อมฟีเจอร์นี้)

- **G1 🔴 ผูกโรงเรียนใครก็ได้** — `CourseController::store` และ `update` รับ `academy_id => 'nullable'` แล้วเขียนตรง
  ผู้ใช้ใดก็ได้ยิง `POST /api/courses` หรือ `PUT /api/courses/{own}` พร้อม `academy_id` ของโรงเรียนที่ตัวเองไม่ได้เป็นสมาชิก →
  คอร์สขึ้นในแคตตาล็อก/รายงาน/หน้าสาธารณะของโรงเรียนนั้น, ได้ XP โรงเรียน, เปิดทางแคมเปญในนามโรงเรียน
  (roster ห้องเรียนรอดเพราะ scope=homeroom แต่ส่วนอื่นไม่รอด)
- **G2 🔴 `update` รับ `user_id` / `instructor_id` แบบ `nullable`** — co-admin ของคอร์ส (role 4) โอนความเป็นเจ้าของ
  (และกระแสเงินค่าเรียน §1.5) ให้ตัวเองหรือคนอื่นได้ · FE ไม่ได้ส่งสองฟิลด์นี้ → ตัดออกจาก validate ได้เลยโดยไม่กระทบ UI
- **G3 🟡** เส้นส่วนตัวไม่ increment `courses_offered` แม้ส่ง `academy_id` มา → ตัวนับเพี้ยน
- **G4 ⚪** `components/learn/course/CourseSettings.vue` โค้ดตาย Inertia — เสนอลบแยก (ไม่อยู่ในงานนี้)

> G2 เป็นงานแยกจากฟีเจอร์ได้ แต่เล็กมากและอยู่ใน validate ก้อนเดียวกัน → แนะนำปิดใน shard เดียวกับ G1

---

## 3. คำถามที่ต้องให้เจ้าของเคาะ

| # | คำถาม | ตัวเลือก | แนะนำ |
|---|---|---|---|
| **Q1** | เลือกสังกัดได้ตอนไหน | (a) ตอนสร้างเท่านั้น (b) ตอนสร้าง + ย้ายภายหลังในหน้าตั้งค่า | **(b)** — คอร์สส่วนตัวเดิมต้องย้ายได้ (เคสใน BackfillCourseAcademy) |
| **Q2** | ต้องให้แอดมินโรงเรียนอนุมัติไหม | (a) ผูกทันที (b) ส่งคำขอ → แอดมินอนุมัติ | **(a)** — ตรงกับเส้น `AcademyCourseController::store` ที่ครูสร้างคอร์สในโรงเรียนได้ทันทีโดยไม่ต้องอนุมัติ; (b) ต้องเพิ่มตาราง+หน้าคิว (ดู §5) |
| **Q3** | ใครมีสิทธิ์ผูก | (a) ครู (role teacher) + แอดมิน/เจ้าของโรงเรียน (b) สมาชิก approved ทุก role | **(a)** — ใช้เกณฑ์เดียวกับการสร้างคอร์สในโรงเรียน |
| **Q4** | ถอนออกจากโรงเรียนได้ไหม ใครถอน | (a) เจ้าของคอร์สถอนเองได้ (b) เจ้าของคอร์ส **หรือ** แอดมินโรงเรียน (c) ถอนไม่ได้ ต้องให้ super admin | **(b)** — โรงเรียนต้องเอาคอร์สที่ไม่ต้องการออกจากแคตตาล็อกได้ โดยไม่ต้องลบคอร์สของครู |
| **Q5** | ครูออกจากโรงเรียน (member ถูกลบ/ระงับ) แล้วคอร์สเป็นอย่างไร | (a) คงสังกัดไว้ (โรงเรียนยังเห็นผลการเรียนเดิม) (b) ถอนอัตโนมัติ (c) คงไว้ + แอดมินโรงเรียนถอนเองได้ | **(c)** — เกรด/หลักสูตร/รายงานเก่าผูกกับโรงเรียนแล้ว ถอนอัตโนมัติจะทำให้ข้อมูลโรงเรียนหาย · ไม่ต้องมี hook ตอน member ออก |
| **Q6** | สิทธิ์แอดมินโรงเรียนต่อคอร์สที่ผูกแล้ว | (a) เท่าที่มีอยู่ตอนนี้ = แก้ไข+ลบได้ (b) แก้ไขได้แต่**ลบไม่ได้** (ถอนสังกัดแทน) | **(b)** — ครูเป็นเจ้าของคอร์สส่วนตัวมาก่อน ไม่ควรถูกลบทิ้งโดยโรงเรียน · ต้องแก้ `destroy` (872) ให้แอดมินโรงเรียนลบได้เฉพาะคอร์สที่ `user_id` เป็นของโรงเรียน/สร้างผ่านเส้นโรงเรียน — **ถ้าเลือก (b) ต้องตกลงนิยาม "คอร์สของโรงเรียน" เพิ่ม** (ง่ายสุด: คงพฤติกรรมเดิม = (a) ไปก่อน แล้วแจ้งครูชัด ๆ ใน UI ตอนผูก) |
| **Q7** | ค่าเรียน/ตลาด เมื่อผูกแล้ว | (a) ไม่เปลี่ยน — เงินค่าเรียนเข้าเจ้าของคอร์สเหมือนเดิม, ขายในตลาดได้เหมือนเดิม (b) บังคับปิดตลาด/ค่าเรียน หรือแบ่งเงินให้โรงเรียน | **(a)** — โค้ดปัจจุบันเป็นแบบนี้อยู่แล้ว (§1.5) · (b) เป็นงานระบบเงินขนาดใหญ่ ควรเป็น spec แยก |
| **Q8** | ย้ายคอร์สจากโรงเรียน A ไป B ตรง ๆ ได้ไหม | (a) ได้ ถ้าเป็นครูของ B (b) ต้องถอนจาก A ก่อน | **(b)** — ลดความผิดพลาด; ถ้าคอร์สมีหลักสูตร/เกรดของ A ต้องจัดการก่อน (ดู §4.3) |

คำถามรอง (ตอบพร้อมกันได้):
- **Q9** ตอนผูก ให้ตั้ง `semester` / `academic_year` ตามปีการศึกษาปัจจุบันของโรงเรียนอัตโนมัติไหม? (แนะนำ: ไม่ แสดงคำเตือนถ้าว่าง)
- **Q10** แจ้งเตือนแอดมินโรงเรียนเมื่อมีคอร์สผูกเข้ามาไหม? (แนะนำ: บันทึก AuditLog เสมอ + notification เป็น nice-to-have)

---

## 4. แผน (สมมติคำตอบตามคอลัมน์ "แนะนำ" — ถ้าเคาะต่างจากนี้ ปรับ shard ที่ระบุ)

### 4.1 Backend

**B1 — เมธอดสิทธิ์บนโมเดล (ไม่มี migration)**
- `app/Models/Academy.php` เพิ่ม `canHostCourse(?User $user): bool`
  = `isAdmin($user)` **หรือ** มีแถว `academy_members` (`user_id`, `status = AcademyMember::STATUS_APPROVED`) ที่ `isTeacher()` หรือ `role === 'teacher'`
  และ `archived_at IS NULL`
- `AcademyCourseController::store` (41–58) เปลี่ยนไปเรียกเมธอดนี้ (พฤติกรรมเท่าเดิม ข้อความ 403 เดิม)
- `app/Models/Course.php` เพิ่ม `canManageAffiliation(?User $user): bool` = `isAdmin($user)` (เจ้าของ/co-admin/super) — ใช้ฝั่งผูก
  และ `canDetachAffiliation` = `isAdmin($user) || academy?->isAdmin($user)` — ใช้ฝั่งถอน (Q4-b)

**B2 — ปิดช่อง G1/G2/G3 ใน `CourseController`**
- `store`: เปลี่ยน `academy_id` เป็น `['nullable', 'integer', 'exists:academies,id']` + ถ้ามีค่า → `Academy::find()->canHostCourse($user)` ไม่ผ่าน = 403
  ข้อความไทย `'คุณต้องเป็นครูหรือผู้ดูแลของโรงเรียนนี้ จึงจะเพิ่มรายวิชาเข้าสังกัดได้'` · ผ่าน → resync `courses_offered` (G3)
- `update`: **ลบ** `user_id`, `instructor_id`, `academy_id` ออกจาก validate (G1/G2) — การเปลี่ยนสังกัดไปทางเส้น B3 เท่านั้น
  (ตรวจแล้ว settings.vue ไม่ส่งสามฟิลด์นี้ · ต้อง grep `ui/` ซ้ำก่อนลงมือว่าไม่มีที่อื่นส่ง `PUT /api/courses/{id}` พร้อมฟิลด์เหล่านี้)

**B3 — endpoint ใหม่ (routes/learn/course.php กลุ่ม `auth:api`)**
| Method | Path | Controller | ทำอะไร |
|---|---|---|---|
| GET | `/api/me/course-host-academies` | `CourseAffiliationController::hostAcademies` | รายการโรงเรียนที่ผู้ใช้ผูกคอร์สได้ = เป็นเจ้าของ ∪ `academy_admins` ∪ member approved ที่เป็นครู · `notArchived()` · คืน `{id, name, display_name, logo}` ไม่ paginate (จำนวนน้อย) |
| PATCH | `/api/courses/{course}/academy` | `CourseAffiliationController::update` | body `{academy_id: int}` → ผูก · ต้อง `course->academy_id === null` (Q8-b, ไม่งั้น 422) · `course->canManageAffiliation` + `academy->canHostCourse` |
| DELETE | `/api/courses/{course}/academy` | `CourseAffiliationController::destroy` | ถอน · `course->canDetachAffiliation` · ผ่าน §4.3 ก่อน |

- ไฟล์ใหม่ `app/Http/Controllers/Api/Learn/Course/info/CourseAffiliationController.php`
- FormRequest `app/Http/Requests/Learn/Course/UpdateCourseAffiliationRequest.php` (`academy_id` required|integer|exists)
- ทุกครั้งที่ผูก/ถอน: ใน `DB::transaction` → update `academy_id` → resync `courses_offered` ของโรงเรียนที่เกี่ยวข้อง
  (`$academy->update(['courses_offered' => $academy->courses()->count()])` แบบเดียวกับ BackfillCourseAcademy) → `AuditLogService::log()`
  (มี `academy_id` อัตโนมัติจาก entity ตามงาน #22)
- response: `{ success, course: CourseResource }` (โหลด `academy` ให้ FE อัพเดทการ์ดทันที)

### 4.2 Frontend (mobile-first บังคับ — ดูบล็อกกติกาใน CLAUDE.md)

**F1 — composable** `ui/composables/useCourseAffiliation.ts` (ผ่าน `useApi` เท่านั้น; error อ่าน `err.data.message`)
- `fetchHostAcademies()`, `attach(courseId, academyId)`, `detach(courseId)`

**F2 — ตอนสร้าง** `ui/pages/Learn/Course/CreateNewCourse.vue`
- ถ้า `hostAcademies.length > 0` → แสดง field "สังกัด" : ตัวเลือก "รายวิชาส่วนตัว (ไม่สังกัด)" + รายชื่อโรงเรียน · ค่าเริ่มต้น = ส่วนตัว
- ถ้าไม่มีโรงเรียน → ไม่แสดง field (ส่ง `academy_id` ว่างเหมือนเดิม)
- รองรับ query `?academy=<id>` เติมค่าล่วงหน้า (ไว้ลิงก์จากหน้าโรงเรียน — optional)

**F3 — ภายหลัง** component ใหม่ `ui/components/learn/course/settings/CourseAffiliationCard.vue`
- ใส่ใน `settings.vue` **บรรทัดเดียว** (ไม่แตะ `sectionFields`/ตรรกะบันทึกเดิม — มี save ของตัวเอง)
- สถานะ A: ยังไม่สังกัด → dropdown โรงเรียน + ปุ่ม "เพิ่มเข้าสังกัด" → **dialog ยืนยัน** อธิบายผล (§1.5: แอดมินโรงเรียนแก้ไข/ลบได้, ขึ้นในรายงานโรงเรียน)
- สถานะ B: สังกัดแล้ว → การ์ดโลโก้+ชื่อโรงเรียน + ปุ่ม "ถอนออกจากสังกัด" (แสดงเมื่อเป็นเจ้าของคอร์สหรือแอดมินโรงเรียน) + dialog ยืนยัน
- โชว์การ์ดเฉพาะผู้ที่ `course.isAdmin` (เหมือน section อื่นของหน้า)
- layout: `flex-col sm:flex-row`, ปุ่ม `min-h-[44px] w-full sm:w-auto`, ชื่อโรงเรียน `min-w-0 flex-1 break-words`, ป้าย `flex-shrink-0 whitespace-nowrap`, `p-3 sm:p-6`, dark mode ครบ
- ตรวจจริงที่ 375 / 768 / 1280

### 4.3 กติกาตอนถอน (DELETE) — ต้องเคาะ Q5/Q8 ก่อนยืนยัน
ก่อนถอน ตรวจสิ่งที่ผูกกับโรงเรียนอยู่ แล้ว **block (422 + รายการเหตุผล)** หรือ **ล้างให้** ตามที่เคาะ:
- คอร์สอยู่ในหลักสูตรโรงเรียน (pivot ของ `Curriculum::courses()`) → แนะนำ block ให้ถอดจากหลักสูตรก่อน
- กลุ่มเรียนที่ดึงนักเรียนจากห้องเรียนโรงเรียน (`CourseGroupClassroomController`) → แนะนำ **ไม่** ลบสมาชิก แค่ตัดการ sync
- เกรดที่ออกแล้ว (`CourseGrade` อ่าน academy แบบ dynamic) → ต้องตรวจตอน implement ว่าเกรดเก่ายังแสดงถูกในรายงานโรงเรียนหรือไม่ ถ้าไม่ → block เมื่อมีเกรดแล้ว
- ⚠️ ต้องสำรวจเพิ่มตอนเริ่ม shard B3: ตารางอื่นที่เก็บ `academy_id` ลอก (denormalize) จากคอร์ส เช่น `course_posts.academy_id`, `course_grades` — ผูก/ถอนไม่ย้ายข้อมูลเหล่านี้ (ตั้งใจ)

### 4.4 Migration
- **ไม่ต้องมี** สำหรับทางเลือกที่แนะนำ (Q2-a) — คอลัมน์ `courses.academy_id` มีอยู่แล้ว
- ถ้าเคาะ **Q2-b (อนุมัติ)** → เพิ่มตาราง `course_academy_requests`
  (`id, course_id FK, academy_id FK, requested_by FK users, status tinyint(1=pending,2=approved,3=rejected,4=cancelled), decided_by nullable, decided_at nullable, note nullable, timestamps`
  · unique ที่ใช้ได้บน MySQL: index (`course_id`,`status`) + ตรวจซ้ำใน service เพราะ MySQL ไม่มี partial unique · ชื่อ index ≤ 64 ตัว)
  + endpoint คิวฝั่งโรงเรียน `GET/POST /api/academies/{academy}/course-requests[/{id}/approve|reject]` (permission `courses.manage`?) + หน้าคิวในเมนูแอดมินโรงเรียน → เพิ่มงาน ~2 shard

### 4.5 เทสต์ (`tests/Feature/Learn/CourseAffiliationTest.php` · รันบน MySQL: `php artisan test -c phpunit.mysql.xml --filter=CourseAffiliation`)
1. ครู (member status 2 + teacher role) ผูกคอร์สตัวเองได้ → `academy_id` ตั้ง, `courses_offered` +1
2. เจ้าของโรงเรียน (ไม่มีแถว `academy_members`) ผูกได้ — กันกับดัก isAdmin
3. member status 1 (pending) / role student → 403
4. คนนอก `POST /api/courses` พร้อม `academy_id` → 403 (G1)
5. `PUT /api/courses/{id}` พร้อม `academy_id` / `user_id` → ค่าไม่เปลี่ยน (G1/G2)
6. co-admin คอร์สผูกได้ / คนที่ไม่ใช่แอดมินคอร์ส → 403
7. ผูกซ้ำทั้งที่มีสังกัดแล้ว → 422 (Q8-b)
8. ถอน: เจ้าของคอร์ส ✅ · แอดมินโรงเรียน ✅ · ครูคนอื่นในโรงเรียน 403 · `courses_offered` −1
9. โรงเรียน archived ไม่อยู่ใน `/me/course-host-academies` และผูกไม่ได้
10. `AcademyCourseController::store` เดิมยังผ่าน (regression ของ refactor B1)

---

## 5. Shards สำหรับ agy/Codex (เรียงตามลำดับ · Claude ตรวจ diff เองทุก shard)

| Shard | ไฟล์ | เกณฑ์ผ่าน |
|---|---|---|
| **S1** BE สิทธิ์ + ปิดช่อง | `Models/Academy.php`, `Models/Course.php`, `AcademyCourseController.php` (store), `CourseController.php` (store/update validate) | เทสต์ 2–5, 10 ผ่านบน MySQL · pint |
| **S2** BE endpoint ผูก/ถอน | `CourseAffiliationController.php` (ใหม่), `UpdateCourseAffiliationRequest.php` (ใหม่), `routes/learn/course.php` | เทสต์ 1, 6–9 ผ่าน · ตั้งชื่อ route `course.affiliation.*` แล้ว `route:list --name=course.affiliation` เห็น 3 เส้น |
| **S3** FE ตอนสร้าง | `composables/useCourseAffiliation.ts` (ใหม่), `pages/Learn/Course/CreateNewCourse.vue` | ไม่มี `$fetch`/`axios` ใหม่ · ตรวจจอ 375px สร้างคอร์สแบบส่วนตัวและแบบมีสังกัดได้ |
| **S4** FE หน้าตั้งค่า | `components/learn/course/settings/CourseAffiliationCard.vue` (ใหม่), `pages/Learn/Courses/[id]/settings.vue` (+1 บรรทัด) | diff ของ settings.vue = เพิ่ม 1–2 บรรทัด, deletion 0 · ตรวจจอ 375/768/1280 · ผูก→ถอน ครบวง |
| (S5) ถ้า Q2-b | migration `course_academy_requests` + model + คิวโรงเรียน | แยกสเปคเพิ่ม |

- S1 ทำแยกได้ก่อนเคาะ Q ที่เหลือ (เป็นการปิดช่องโหว่ล้วน ไม่เปลี่ยนพฤติกรรมที่ UI ใช้อยู่) — **แนะนำทำทันทีที่เจ้าของโอเค**
- S3/S4 อย่ารันพร้อมกับงานอื่นที่แก้ `settings.vue`
- ห้ามรัน `npm run build` (เจ้าของทำเอง) · BE ต้อง `php artisan test -c phpunit.mysql.xml` ไม่ใช่ sqlite อย่างเดียว
