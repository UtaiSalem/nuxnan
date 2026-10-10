# Work Log — nuxnan project

## 🧭 สถานะ git — ยืนยัน 2026-10-07 (อ่านก่อนเชื่อคำว่า "ยังไม่ push/ยังไม่ merge" ในบันทึกเก่า)

บันทึกด้านล่างเป็น log ประวัติ หลาย entry เขียน "ยังไม่ push" / "ยังไม่ merge" / "⏳ ยังไม่ทำ" ตามสภาพ ณ ตอนนั้น
ตรวจจริงวันนี้: `git log origin/main..HEAD` = เหลือเฉพาะ commit เอกสารของเซสชันนี้ · remote เหลือแค่ `origin/main`
\+ branch เซสชันนี้ (ไม่มี dev branch เก่าค้างบน origin แล้ว · repo เป็น shallow clone · PR ทุกตัว squash-merge)
⇒ **งานที่บันทึกว่า commit ตรงบน `main` (ส่วนใหญ่ของ entry) เข้า `main` ครบแล้ว** — "ยังไม่ push" ของ entry พวกนั้นปิดแล้ว
(SHA เดิมของ dev branch ตรวจไม่เจอใน clone นี้เป็นเรื่องปกติของ squash-merge — ไม่ได้แปลว่างานหาย)
🔸 entry ที่ commit อยู่บน **feature branch** เฉพาะชื่อ: branch นั้นไม่เหลือบน origin แล้ว ซึ่งปกติ = merge เข้า main
   แต่ยืนยันราย SHA ไม่ได้ (shallow) — ถ้าสงสัย ให้เช็กว่าโค้ด/ไฟล์ของงานนั้นอยู่ใน main จริงไหมก่อนสรุป

⚠️ ข้อนี้ครอบคลุม **push/merge เท่านั้น** — งาน runtime owner-gated ที่ยังค้างจริงตามแต่ละ entry ยังคงค้าง:
production ยังไม่ได้รัน migration บางชุด (ก่อนรันให้ `mysqldump` ก่อนเสมอ) · `npm run build` ฝั่ง FE เจ้าของรันเอง
· `guardians:backfill --force` ยังไม่รันบนฐานบางเครื่อง · G25 (migrate จากศูนย์บน MySQL) — เหล่านี้ไม่เกี่ยวกับ push

---

## 📋 Backlog — ✅ ปิดครบทุกข้อ (อัพเดท 2026-09-27)
ข้อ 1–5 ด้านล่างเสร็จหมดแล้ว (รายละเอียด + commit อยู่ในแต่ละหัวข้อ) · ไม่มีงาน backlog ค้าง

**เสร็จใน session 2026-09-26/27:** course members "จำกลุ่มล่าสุด" (toast fix → last_viewed_group รวมศูนย์ composable 9 จุด)
· #3 re-profile + ตัด N+1 auth_progress · **#2 report module ครบ 100%** (4 แท็บ FE + แก้ backend 3 bug)
· member_activity_logs drift (จริง ๆ คือ Schema::drop pollute suite) · member_code int→varchar(50) → **Academy suite 24 แดง → 0**
· academy_donate_claims index+FK repair (idempotent) · #5 feed getComments N+1 (native eager-limit + regression test)

**ค้างเชิงปฏิบัติ (owner-gated — ไม่ใช่งาน backlog):**
- commit FE หลายชุดยังไม่ `npm run build` (เจ้าของ handle) — rebuild + คลิกจริงที่ school-management / course pages
- G25 (migrate จากศูนย์บน MySQL ยังพัง) ยังไม่แก้ตามคำตัดสินเจ้าของ
- academy_donate_claims FK-repair: ก่อน deploy prod ต้องรัน orphan-check ก่อน (ดูบันทึก 2026-09-27)

1. ✅ **UserResource เบาทั้งแอป — เสร็จ 2026-09-24** (ดูบันทึกด้านล่าง) — รวม eager-load เป็น scope
   `User::withCardCounts()` แล้ว apply เข้า list ที่ render UserResource เต็ม (peopleMayKnow, donateRecipients ×2,
   admin users, course roster ×3) · Academy member ไม่แตะ (resource ใช้ user แบบย่อ) · วัดจริง 20 คน 161→3 คิวรี
   ✅ `FollowController::followers/following` N+1 (`isFollowing()` รายแถว) แก้แล้ว 2026-09-25 (`b025d890`)

2. ✅ **report module — เสร็จครบ backend + FE (2026-09-26/27)** — sync export (`403a2138`) + audit-log fix
   (`06af70bb`) + FE ครบทุกแท็บ: definitions CRUD/toggle/duplicate · generate · saved reports (view/refresh/
   favorite/export/delete) · schedules CRUD · แก้ bug ระหว่างทาง 3 จุด (refreshReport ล้าง data, updateSchedule
   time_of_day, duplicateDefinition arg) · backend report tests 9 ไฟล์เขียวบน MySQL · **ปิดครบ 100% (รวม generateQuickReport)**

3. ✅ **CourseResource / AcademyResource N+1 — เสร็จครบ** (เฟส 1a/1b/2a/2b + follow-up 2026-09-25 · profile ยืนยัน 2026-09-26)
   list endpoint ทุกตัวใช้ withViewerCardData()/withViewerCardRelations() · perf test 6/6 เขียวบน MySQL (48 assertions รวม PII matrix)
   · re-profile ข้อมูลจริง 2026-09-26 พบ N+1 ตกค้าง 1 จุด (auth_progress lazy-load courses/แถว) → แก้แล้ว `a15d7be1` (ดูบันทึกล่างสุด)
   · course list วัดจริง bounded ~19-20 query คงที่ทุกขนาด (เดิมโต ~0.83/แถว)

4. ✅ **เทสต์กัน N+1 ถอย — เสร็จ 2026-09-24** — `tests/Feature/Performance/UserResourceQueryCountTest.php`
   หลัก "query ไม่โตตามจำนวนผู้ใช้" 2 ชั้น (resource-level + endpoint /api/newsfeed) · mutation-verified (แดงจริงเมื่อถอด scope)

5. ✅ **feed getComments N+1 — เสร็จ 2026-09-27 (`961be8d9`) · ไม่ต้องเพิ่ม dependency** — dependency question คลี่คลาย:
   `staudenmeir/eloquent-eager-limit` หยุดที่ Laravel 10 เพราะ feature รวมเข้า core L11+ → Laravel 12 ทำ per-parent
   limited eager load ได้ native (window function บน MySQL) · preload postComments/post_comments/shareComments limit 3/โพสต์
   + nested ใน morph batch (ActivityController) → getComments ใช้ branch in-memory · ตัด Share .load() loop (N+1)
   · วัดจริง loadFeed=21q คงที่ (per=5/15) · getComments เพิ่ม 0q (เดิม ~5/โพสต์) · max 3/โพสต์
   · **regression test `FeedCommentsQueryCountTest` (`2b69e6dc`)** — seed posts+comments+activity morph, รัน
   loadActivityableForFeed จริง, assert getComments 0 extra + bounded + cap 3 · mutation-verified · MySQL เขียว

---

## 2026-10-09 — เมนู #15 ทะเบียนนักเรียน: audit (ขั้น [1]+[2]) · เมนูสุก/เทสต์ครบ — ต่างจาก #12–#14

### สถานะ: 🟢 audit เสร็จ · ไฟล์รอง `.agents/school-admin/15-students.md` · รอเจ้าของเคาะ Q1–Q4 (หลายข้ออาจปิด by-design)
(ต่อจากลำดับ loop #12 → #13 → #14 บุคลากร → **#15 ทะเบียนนักเรียน**)

### สแกนแล้ว
- FE `admin/students/{index,import,intake,import-history}.vue` + `StudentDataTable` (595) + `useStudentEnrollmentActions`/`useStudentAccountService`
- BE `StudentIntakeController` · `StudentImportController` · `StudentLifecycleController` · `StudentAccountController` · Master `StudentController`+6 ตัวย่อย
- authz เป็นระบบ: `EnrollmentPolicy`(intake/import/lifecycle) + Gate + FormRequest::authorize + `scopeBindings()` tenant · route read ติด `students.view`/`students.export`
- **เทสต์ครบ** (Lifecycle/Import/Intake/EnrollmentPolicy/StudentMaster/RosterImport/EnrollmentAudit...) · stats คืน `{stats:{...}}` ตรง FE

### Gap (เล็ก/ไม่สอดคล้อง — ไม่ใช่ P0)
- **G1 (หลัก)** `EnrollmentPolicy::isAcademyAdmin` เช็คแค่ `member.role∈{admin,director}`(+owner) ไม่อ่าน academy_role.permissions ⇒ custom role ถือ `students.manage` intake/import ได้ (ผ่าน hasAnyPermission) แต่ lifecycle (promote/graduate/drop/transfer) **ไม่ได้** — inconsistent
- **G2** enrollment-history v1 (`groups.view`) ซ้ำ v2 (`enrollment.lifecycle`) · FE ใช้ v2 · v1 อาจ dead
- **G3** permission แยกสองทาง (middleware `userCan`→role.permissions vs policy `member.role`+hasAnyPermission) เสี่ยง drift
- **G4** registry ไม่มี edit/delete ตรง — แก้ผ่าน change-request flow · เอาออกผ่าน lifecycle (ยืนยันดีไซน์)

### ค้าง — รอเคาะ Q1 (แก้ G1 ให้ students.manage ทำ lifecycle หรือปิด by-design) · Q2 (ลบ history v1?) · Q3 (ขอบเขต Student Master profile = #15/#6/#17) · Q4 (change-request flow by design?)
ถ้า G1 by-design + G2 เก็บไว้ ⇒ **เมนู #15 ปิดได้เลยไม่ต้องแก้โค้ด** · ไม่งั้นแตก ST15-S1 (แก้ policy + เทสต์) · ST15-S2 (ลบ v1)

---

## 2026-10-07 — เมนู #14 บุคลากร: audit (ขั้น [1]+[2]) · โมดูลพังหลายชั้น "สร้างไว้แต่ไม่เคยต่อ backend จริง"

### สถานะ: 🔴 audit เสร็จ · ไฟล์รอง `.agents/school-admin/14-staff.md` · รอเจ้าของเคาะ Q1–Q5 ก่อน ST-S1
(ต่อจากลำดับ loop #12 คอร์ส → #13 หลักสูตร → **#14 บุคลากร**)

### สแกนแล้ว (ยิง schema/route/model จริง)
- FE `staff.vue` (738 บรรทัด) · BE `StaffController` (439) · route `academy.php:767–824` · migration `2026_02_04_100003_create_staff_system_tables.php`
- ต่างจาก #13 (ฟีเจอร์ครบ เหลือ authz) — **#14 พังตั้งแต่ contract FE↔BE จนถึง schema-drift** คล้าย Expense เมนู #8

### 🔴 Gap (รายละเอียดครบในไฟล์รอง)
- **FE contract:** F1 gate `isAdmin` แต่เมนูโชว์เมื่อ `staff.view` (เด้งออก · G21 ซ้ำ) · F2 list อ่าน paginator เป็น array · F3 summary อ่านผิดชั้น (การ์ด 0) · F4 query ไม่เข้า URL (กรอง/แบ่งหน้าตาย) · F5 create ส่ง `employee_type`/`department` free-text + ไม่ส่ง first_name/last_name NOT NULL (พังถาวร) · F6 PUT ชน PATCH 405 · F7 ปุ่มเปลี่ยนสถานะไม่มี · F8 ไม่มี UI สร้างตำแหน่งแต่ create บังคับ position (dead-end)
- **BE schema-drift:** B1 scope `byStatus`/`byType` ไม่มี → 500 · B2 `show()` โหลด `supervisor` ไม่มี relationship → 500 · B3 store validate 6 ฟิลด์ไม่มีคอลัมน์ + ไม่เก็บ first_name/last_name · B4 updateStatus เขียน `termination_*` (จริงคือ `resignation_*`) → 500 · B6 position `requirements`/`code` unique global
- **Security/Test:** B7 write ทุกเส้นใช้แค่ `staff.view` (ควรแยก `staff.manage`) · B8 ไม่มีเทสต์

### เคาะ Q1–Q5 แล้ว → ST-S1–S6 โค้ด/เทสต์เสร็จครบ (2026-10-07)
เจ้าของเคาะ: Q1 ชื่อ/รูปจาก user (first/last nullable) · Q2 ทะเบียน+ตำแหน่งล้วน · Q3 write=`staff.manage` read=`staff.view` เปิดหน้าให้ staff.view · Q4 ฝ่าย=`department_id` · Q5 enum ยืนยัน
- **ST-S1** routes `academy.php` แยกสิทธิ์รายเส้น (read=staff.view 5 · write=staff.manage 7 · กลุ่มเหลือแค่ visibility ตามบทเรียน SM-S1) + `staff.vue` เปิดหน้าให้ `staff.view` · ปุ่มจัดการ gate ด้วย `canManage`
- **ST-S2** `StaffController` ซ่อม scope (byStatus/byType) · ตัด supervisor · store/update ตรง schema จริง (scoped `Rule::exists` ต่อโรงเรียน · user_id unique/โรงเรียน) · updateStatus→resignation_* · position requirements ตัด + code unique/โรงเรียน · เพิ่ม `departments()` + staff_count · **migration** first_name/last_name nullable
- **ST-S3** `staff.vue` ซ่อม contract: list อ่าน paginator · summary อ่าน by_status · query ใน URL · payload employment_type/department_id · PUT→PATCH · err.data (shape จริงของ useApi)
- **ST-S4** modal ตำแหน่งมี CRUD เต็ม (สร้าง/แก้/ลบ) + empty-state ชี้สร้างตำแหน่งก่อน
- **ST-S5** สถานะเป็น inline select (manage) เรียก updateStatus · badge 5 สี (view)
- **ST-S6** `StaffAuthzTest.php` 14 เคส (authz view/manage split · tenant 403/404 · resigned ตั้ง resignation_date · create ไม่ต้องมี first_name)
- หลักฐานที่รันเอง: `php -l` ผ่านทุกไฟล์ backend · SFC balanced · ไม่มี ref เก่าค้าง (employee_type/err.response = 0) · **vendor+node_modules ไม่มีใน container** ⇒ เหลือเจ้าของรัน migrate + `test -c phpunit.mysql.xml --filter=StaffAuthzTest` + pint + `npm run build`
- PR: UtaiSalem/nuxnan#32 (branch `claude/gallant-hopper-iux8ft`)
- **2026-10-10 fix หลังเจ้าของรันเทสต์ MySQL (4 failed):** `Class App\Models\Department not found` — ตาราง `departments` ถูกอ้าง FK แต่ไม่มี model/migration (G25) → เติม `Department` model กัน 500 (`3079861`) แล้ว **ST-S7** เจ้าของเคาะเลือก B: ฝ่าย = `AcademyGroup`(type=department เมนู #9) ไม่ใช่ orphan `departments` → migration ถอด FK · relation/validation/endpoint ชี้ academy_groups · ลบ Department model · StaffAuthzTest 17 เคส · เหลือเจ้าของ migrate + test MySQL

---

## 2026-10-07 — เมนู #13 หลักสูตร: audit (ขั้น [1]+[2]) · ฟีเจอร์ครบ แต่เจอช่องโหว่สิทธิ์ P0

### สถานะ: 🔴 audit เสร็จ · ไฟล์รอง `.agents/school-admin/13-curriculums.md` · รอเจ้าของเคาะ Q1–Q3 ก่อน CR-S1
(เมนู #12 คอร์สเรียน CO-S1–S7 merge เข้า main แล้วใน PR #30 squash `2fab408`)

### สแกนแล้ว
- FE `curriculums.vue` (1,140 บรรทัด) ฟีเจอร์ครบ (CRUD + คอร์สในหลักสูตร + นักเรียน + สถิติ) · gate UI ด้วย `useAcademyRole` can()/isAdmin()
- BE `CurriculumController` (611 บรรทัด) ครบทุก method · route `academy.php:604–636` ใต้ `auth:api` เท่านั้น

### 🔴 Gap P0
- **G1** ทั้งโมดูล curriculum ไม่มี authz backend เลย (ไม่มี isAdmin/userCan/abort/tenant) → ใครล็อกอินก็ CRUD หลักสูตร/คอร์ส/นักเรียนของโรงเรียนไหนก็ได้ (FE กันอย่างเดียว)
- **G2** กลุ่ม `curriculums/{curriculum}` ไม่มี `{academy}` ใน path → bind ตรง ๆ รั่วข้ามโรงเรียนด้วยการเดา id (getStudents คืน name/email/photo)
- **G3** ไม่มีเทสต์

### ค้าง — รอเคาะ Q1 (permission key = courses.*?) · Q2 (ใครจัดการ/ครู-นักเรียนดูแค่ไหน) · Q3 (enroll นักเรียนอยู่ #13 หรือ #15 ทะเบียน)
แตกงาน CR-S1 (ปิด G1+G2 สิทธิ์+tenant) · CR-S2 (เทสต์ authz+isolation)

---

## 2026-10-07 — เริ่มเมนู #12 คอร์สเรียน: audit (ขั้น [1]+[2]) · พบ gap 7 ข้อ

### สถานะ: 🔴 audit เสร็จ · เขียนไฟล์รอง `.agents/school-admin/12-courses.md` แล้ว · ยังไม่แตะโค้ด · รอเจ้าของเคาะ Q1–Q3
ต่อเนื่องจากแผน loop เมนูโรงเรียน (ถัดจาก #11 ตารางเรียน) · เมนู #12 = จัดการ **catalog รายวิชา** ของโรงเรียน (ไม่ใช่เนื้อหาในคอร์ส)

### สแกนโค้ดจริงแล้ว (ขอบเขต: หน้า `academies/[name]/admin/courses/`)
- FE: `index.vue` (list 2 แท็บ คลัง+ตลาด) · `create.vue` (ฟอร์มสร้าง) · **ไม่มีหน้า edit**
- BE: `AcademyCourseController` (`getAcademyCourses`/`store`) · โฟลว์แก้/ลบอยู่ที่เจ้าของคอร์ส `CourseController` (`PUT/DELETE /courses/{course}`)
- Course model: status tinyint 1=published/2=draft/3=archived + finalization_status · permission key `courses.*` มีครบและ assign ให้ role แล้ว

### Gap ที่พบ (G1–G7, รายละเอียดในไฟล์รอง)
- G1 ปุ่มลบในหน้า list เป็นปุ่มตาย (ไม่มี handler) · G2 ลิงก์ edit ชี้หน้า `{id}/edit` ที่ไม่มีไฟล์ (404)
- G3 FE แสดงสถานะ draft(2)/archived(3) ผิด (map แค่ 0/1) · G4 list gate แค่ `visibility:courses` ไม่ใช่ `courses.view`
- G5 create ไม่มี permission middleware (เช็ค isAdmin||teacher ภายใน) · G6 ไม่มี endpoint แก้/ลบในโฟลว์ admin
- G7 `store()` comment ฟิลด์ส่วนใหญ่ทิ้ง บันทึกแค่ name/code/description/cover

### ค้าง — รอเจ้าของเคาะก่อนเริ่ม CO-S1
- Q1 ขอบเขต = แค่ catalog ใช่ไหม (เนื้อหาคอร์สเป็นงานหน้าเจ้าของคอร์ส) · Q2 สิทธิ์แก้/ลบ (admin แก้ของครูคนอื่นได้ไหม · ลบคอร์สที่มีนักเรียนแล้ว?) · Q3 สิทธิ์สร้าง (ครูทุกคน vs `courses.create`)
- แตกงานเป็น CO-S1 (ด่านสิทธิ์) · CO-S2 (FE สถานะ) · CO-S3 (ปุ่มลบ) · CO-S4 (หน้า edit) · CO-S5 (store ฟิลด์ครบ) · CO-S6 (เทสต์)

---

## 2026-10-05/06 — super admin: ดู + ยกเลิก/ดึงคืน (claw-back) transfer & conversion — PR #27, #28 (✅ merged)

### สถานะ: ✅ merged เข้า main ทั้งคู่
- **PR #27** https://github.com/UtaiSalem/nuxnan/pull/27 — merge commit `699fd7f` (2026-10-06) · 2 commit: `630af7d`, `0a28022`
- **PR #28** https://github.com/UtaiSalem/nuxnan/pull/28 — merge commit `0cebe79` (2026-10-06) · 1 commit: `8389a3a`
ต่อยอดจากระบบ fraud-report + suspend (PR #26) · ไม่มี CI ตั้งบน repo นี้

### PR #27 — claw-back transfer & conversion (`630af7d` + `0a28022`)
**แนวคิด:** super admin ย้อนกลับ (ยกเลิก + ดึงคืน) ธุรกรรมที่ทุจริตได้ ทั้งจากหน้า profile ผู้ใช้และจาก list กลาง

**ส่วนที่ 1 — view + claw-back จาก profile (`630af7d`):**
- BE: `AdminWalletController::userTransactions` (wallet) เปิดสิทธิ์ให้ admin (เท่า points lens) + เพิ่ม `type=transfers`
  shortcut, annotate แต่ละแถวด้วย direction/counterparty/reversible/reversed, คืน user block, audit-log การเปิดดู
  · `userPointsTransactions` annotate flag เดียวกัน (แถว fraud_reversal correction ย้อนซ้ำไม่ได้)
  · route ใหม่ `GET /admin/wallet/users/{userId}/wallet-transactions`
  · 🔴 `AdminController::show` แก้อ่าน points จากคอลัมน์จริง `pp` (เดิมอ่าน `points` ที่ไม่มี → โชว์ 0 ตลอด)
- FE: การ์ด "ธุรกรรมการโอน (เงิน & แต้ม)" ในหน้า admin user profile · ปุ่ม "ยกเลิก + ดึงคืน" (ถามเหตุผล) บนแถวที่
  reversible เฉพาะขารับ (super admin เท่านั้น · BE บังคับซ้ำ) · ขาส่ง link ไป profile ผู้รับเพื่อย้อนที่นั่น
  · แก้ binding ยอด wallet/points ที่โชว์ 0 · mobile-first stacked cards + 44px
- Test: `tests/Feature/Wallet/AdminUserTransferLensTest.php` (annotation + auth)

**ส่วนที่ 2 — claw-back จาก list กลาง + conversion reversal (`0a28022`):**
- BE: `App\Support\TransactionReversal` = SSOT ว่าแถว points/wallet ยัง reversible ไหม / ย้อนไปแล้วไหม / รายละเอียดการย้อน
  (ใช้ทั้ง global list + profile lens → กติกาไม่ drift)
  · `FraudRemediationService::reverseConversion` ย้อน conversion points↔money ภายในผู้ใช้เดียวจาก leg ไหนก็ได้
  (policy เดียวกับ transfer: ดึงคืนเท่าที่ยอดฝั่งเครดิตมี, คืนอีกฝั่งตาม exchange rate ที่บันทึก, บันทึก shortfall,
  เขียน correction rows, stamp ทั้งสอง leg · idempotent + locked)
  · `AdminFraudController`: endpoint reverse เดิม dispatch เป็น transfer- หรือ conversion-reversal ตาม type (FE คง 1 endpoint/ตาราง)
  · annotate `/admin/points-transactions` + `/admin/wallet-transactions` ด้วย reversibility flags · refactor profile endpoint ใช้ helper กลาง
- FE: composable `useReverseTransaction` (flow claw-back ถามเหตุผล ร่วมกัน) · list Points & Wallet มี action "จัดการ"
  → "ยกเลิก/ดึงคืน" (หรือ "ยกเลิกการแปลง" สำหรับ conversion) บนแถว reversible + marker "ย้อนแล้ว" บนแถวที่ย้อนไปแล้ว
- Test: `tests/Feature/Wallet/ConversionReversalTest.php` (points→money, money→points partial claw-back, idempotency 2 leg, reject non-conversion)

### PR #28 — pagination ให้ list ธุรกรรม Wallet admin (`8389a3a`)
- `/nuxnan-admin/wallet` ดึงด้วย page/per_page + track currentPage/totalPages อยู่แล้ว แต่ไม่เคย render ปุ่ม → เข้าถึงได้แค่ 20 แถวแรก
- เพิ่มแถบ pagination prev/เลขหน้า/next (mirror หน้า Points) + สรุป "หน้า X / Y • ทั้งหมด N รายการ" · track total + `goToPage()` (bounds-checked) · mobile-first 44px

### ค้างทำ (owner-gated — runtime บนเครื่อง WAMP)
- [ ] `npm run build` ฝั่ง FE แล้วคลิกตรวจจริงที่ 375px (profile lens / points & wallet lists / pagination)
- [ ] รัน `php artisan test -c phpunit.mysql.xml --filter='AdminUserTransferLens|ConversionReversal'` บน MySQL
- [ ] ยืนยันว่า migrate ของ PR #26 (fraud/suspend) รันบน dev DB แล้ว — ฟีเจอร์ชุดนี้ build บน flow suspend เดิม

---

## 2026-10-02 — ระบบร้องเรียนบัญชีทุจริต + ยกเครื่อง alert (SweetAlert) — PR #26 (✅ merged 2026-10-05)

### สถานะ: ✅ merged เข้า main แล้ว (2026-10-05, merge commit `7031969`, branch `claude/youthful-gates-abvw3k`)
PR: https://github.com/UtaiSalem/nuxnan/pull/26 · ต่อยอดใน PR #27/#28 (ดูด้านบน)
⚠️ งาน runtime owner-gated ด้านล่าง (migrate dev DB / build / รัน FraudReportTest บน MySQL) อยู่บนเครื่อง WAMP ของเจ้าของ
— การ merge โค้ดไม่ได้รันสิ่งเหล่านี้ให้ ตรวจยืนยันจากในนี้ไม่ได้

### 1. คำถาม "super admin ระงับบัญชีธุรกรรมแต้ม/เงินได้อย่างไร" → **มีอยู่แล้ว** (session 2026-09-30)
- คอลัมน์ `points_suspended`/`wallet_suspended`/`economy_suspended_*` + routes `suspend-economy`/`restore-economy`
  + บังคับใช้ใน `PointsService`/`WalletService` (`pointsFrozen()`/`walletFrozen()`) + audit `account_suspension_audits`
  + FE: `nuxnan-admin/users/[id]`, `blacklist/`, `EconomySuspendedBanner`
- 🔴 **ติดที่ migration ยังไม่ได้รันบน dev DB** → error `Unknown column 'points_suspended'` ตอนกดระงับ
  → **ต้องรัน `php artisan migrate` บน WAMP** (อย่าใช้ migrate:fresh) ถึงจะใช้ได้ (รวม table ใหม่ด้านล่าง)

### 2. ระบบใหม่: สมาชิกร้องเรียนบัญชีทุจริต (BE+FE ครบ)
- BE: table/model `account_fraud_reports` + `AccountFraudReport` · member API `POST /api/fraud-reports` (+`/mine`)
  · admin API `/api/admin/fraud-reports` (index/stats/show/status/**suspend**) · `suspendFromReport` = ระงับเศรษฐกิจ
  + เขียน `AccountSuspensionAudit` แบบ flow เดิม แล้วปิดคำร้องเป็น action_taken (ใน DB transaction)
  · `StoreFraudReportRequest` (กันร้องตัวเอง/ยื่นซ้ำ) · feature tests `tests/Feature/FraudReportTest.php`
- FE (mobile-first): `ReportAccountModal` + `useFraudReports` · ปุ่ม "ร้องเรียน" บน `profile/[id].vue`
  · admin `nuxnan-admin/fraud-reports/{index,[id]}` · เมนูใน `NuxnanAdminLayout`
- ⚠️ เทสต์รันในคอนเทนเนอร์ไม่ได้ (ไม่มี MySQL + migration repair_academy_donate_claims ใช้ information_schema)
  → ต้องรัน `php artisan test -c phpunit.mysql.xml --filter=FraudReportTest` บนเครื่องที่มี MySQL · pint+php -l ผ่าน

### 3. ยกเครื่องระบบ alert (ตาม feedback เจ้าของ)
- `AlertMessage.vue` (กล่อง alert มีไอคอน 4 แบบ) + `useApiError` (ซ่อน SQL/5xx ไว้หลังปุ่มดูรายละเอียด)
- เปลี่ยนมาใช้ **SweetAlert** (`useSweetAlert`) — confirm + success/error ใน suspend form, fraud-report detail, report modal
- แทน native `confirm()`/`alert()` ที่เหลือในหน้า admin ทั้งหมด (users, blacklist, wallet, settings)
- **toast → SweetAlert เฉพาะ error วิกฤต** (กติกา: งานทั่วไป/โหลด/คัดลอก/โซเชียล = toast · มูลค่า/ย้อนกลับยาก = swal):
  แลก/ใช้/สร้างคูปองล้มเหลว · rollover commit/undo ล้มเหลว
- `useSweetAlert.error()` รับ `detail` เพิ่ม (collapsible, escape HTML)

### ค้างทำ (owner-gated — runtime บนเครื่อง WAMP · ตรวจจากคอนเทนเนอร์ไม่ได้)
- [x] review + merge PR #26 → ✅ merged 2026-10-05
- [ ] `php artisan migrate` บน dev DB (ปลดล็อก suspend เดิม + table `account_fraud_reports`) **ห้าม `migrate:fresh`**
- [ ] `npm run build` ฝั่ง FE แล้วคลิกจริงที่ 375px
- [ ] รัน `php artisan test -c phpunit.mysql.xml --filter=FraudReportTest` บนเครื่องที่มี MySQL

---

## 2026-09-30 → 10-01 — wallet: ถอนเงินไม่ได้ทั้งที่ยอดโชว์พอ (A/B/C merged · D ตั้งใจ)

### สถานะ: ✅ merged เข้า main ครบ — A: PR #15 (`f439613`) · B+C: PR #23 (`c331674`) · D: ยืนยันว่าตั้งใจ ไม่แตะ · worklog: PR #24 (`e3ea28b`, merged 2026-10-01)

เคสที่เจ้าของแจ้ง: สมาชิกมีเงินสะสม 25 บาท กด "ถอน 25" ไม่ได้ (หน้า `/earn/wallet`)

### สาเหตุ (bug A) — ฟอร์มถอนอิงยอดผิดตัว
`WalletService::getBalance()` คืน `total_balance = cash_balance + locked_balance`
แต่ frontend `useWallet.getBalance()` เดิมเลือก `total_balance` มาเก็บลง auth store เป็น `wallet`
→ การ์ด wallet + ฟอร์มถอน (`:max`, ปุ่มลัด, ปุ่ม submit) อิงยอดที่รวมเงินที่ถูกล็อกไว้
แต่ backend หัก/เช็คจาก `users.wallet` (cash) เท่านั้น
🔴 เคสจริง: เงิน 25 ถูกล็อกในคำขอถอนที่ค้างอยู่ → cash=0 แต่การ์ดยังโชว์ 25 →
กดถอนแล้ว backend ปฏิเสธ (cash ไม่พอ / ติดเพดาน `max_pending_requests` default 1)
ยืนยันแล้ว `locked_balance` ถูกเขียนจาก withdrawal flow เท่านั้น (add ตอนสร้าง, ลบตอนจ่าย/คืน)

### การแก้
- `ui/composables/useWallet.ts`: `getBalance()` เก็บ **`cash_balance`** (spendable) ลง store แทน
  `total_balance` เหลือเป็น fallback ท้ายสุด · เพิ่ม reactive `lockedBalance` / `totalBalance`
- `ui/pages/Earn/Wallet.vue`: การ์ด + ฟอร์มถอนอิง cash · โชว์ยอด "ถูกล็อก" เมื่อ `lockedBalance>0`
  · เปลี่ยนป้าย "ยอดเงินคงเหลือ" → "ยอดที่ถอนได้" (mobile-first: `flex-shrink-0` + `min-w-0 break-words`)
- `ui/tests/useWallet.spec.ts`: อัพเดทเทสต์เดิมที่ยืนยันพฤติกรรมบั๊ก → ยืนยันเก็บ cash + expose locked/total

### เกณฑ์ที่รันเอง
- รันตรรกะ getBalance selection + locked/total เป็นสคริปต์ node แยก 11 เคส (รวมเคสสมาชิก
  cash 0/locked 25 → ถอนได้ 0 · cash 25 สะอาด → ถอนได้ 25) ผ่านหมด
- ⚠️ รัน `npm run test` / build เต็มในคอนเทนเนอร์ cloud ไม่ได้ (ไม่มี node_modules · install Nuxt เต็มไม่ผ่าน)
  → **ต้องรัน `npm run test` + build ที่เครื่อง dev ยืนยันอีกครั้งก่อน merge**

### ปัญหา D (ไม่ใช่บั๊ก) — เจ้าของยืนยันว่า "ตั้งใจ"
ค่าธรรมเนียมถอนขั้นต่ำ 5 บาท (`max(amount×1%, 5)`) → ถอน 25 ได้รับสุทธิ 20 เป็นดีไซน์ที่ตั้งใจ
เจ้าของบอกไม่ต้องเน้นยอดสุทธิเพิ่มในฟอร์ม (คง preview เดิม) → ไม่แตะ

### งานต่อยอด B/C — PR #23 (`c331674`, merged 2026-10-01)
ตอนตรวจ A เจอว่า B/C (ที่เคยเป็นแค่ "สาเหตุที่เป็นไปได้") มีงานจริง:

**B — FE/BE ไม่ตรงกัน (บั๊กจริง):** `WalletController::withdraw` ยอมรับ profile first/last
**หรือ** display name (`users.name`) เป็นตัวตรวจเจ้าของบัญชี (matchesFullName) แต่ FE เช็กแค่
profile first/last → ผู้ใช้ที่มี display name แต่ไม่กรอก profile โดนปุ่ม disable + แบนเนอร์ ทั้งที่ BE ยอม
- `ui/pages/Earn/Wallet.vue`: เพิ่ม `displayName` / `payoutNameForMatch` / `hasPayoutIdentity`
  (สะท้อน BE) · แบนเนอร์ + ปุ่ม + hint + prefill `account_name` อิง `hasPayoutIdentity`

**C — เติมเทสต์ guard ด้าน fraud (เดิมไม่มีเลย):**
- `tests/Unit/BankAccountNameMatcherTest.php`: เพิ่มเทสต์ `matches()` (strict first+last) accept/reject
- `tests/Feature/Wallet/WithdrawTest.php`: ชื่อไม่ตรง → 422 + ไม่มี withdraw row + wallet ไม่ลด ·
  profile ว่าง + display name ว่าง → 422 `profile_name_required`

เกณฑ์: `BankAccountNameMatcher` รันกับคลาสจริง 13/13 ผ่าน · ⚠️ feature test (Laravel) + build FE
รันในคอนเทนเนอร์ cloud ไม่ได้ (ไม่มี vendor/node_modules) ยืนยันด้วยการอ่านโค้ด + cast `decimal:2`

### 🔴 ค้างก่อนขึ้นจริง — ต้องรันที่เครื่อง dev
A/B/C merge เข้า main แล้วแต่ **ยังไม่ผ่าน test/build จริง** (รันในคลาวด์ไม่ได้):
```
cd api/nuxnanravel && php artisan test --filter=Withdraw
cd api/nuxnanravel && php artisan test --filter=BankAccountNameMatcher
cd ui && npm run test && npm run build
```

---

## 2026-09-30 — student-card admin: แก้หน้าเด้งขึ้นบนสุดตอนอนุมัติคำร้อง (เสร็จ · merge เข้า main แล้ว)

หน้า `ui/pages/student-card/admin/students/[level]/[room].vue` (URL `/student-card/admin/students/{level}/{room}`)
ทุกครั้งที่กดปุ่มบนการ์ดนักเรียน (อนุมัติ/ปฏิเสธ/เริ่มจัดทำ/ทำเสร็จ) หน้าจะเด้งขึ้นบนสุด → อนุมัติคนล่าง ๆ ต้องเลื่อนใหม่ทุกครั้ง

**ต้นเหตุ 2 ชั้น:**
1. `reviewRequest()` เรียก `fetchStudents()` ซึ่งตั้ง `isLoading=true` → list ทั้งหมดถูกสลับเป็นสปินเนอร์เต็มจอ (unmount/remount)
   ทำให้ดูเหมือน "โหลดใหม่ทั้งหน้า" และ scroll หาย
2. SweetAlert2 ค่าเริ่มต้น `heightAuto:true` ตั้ง height ของ html/body เป็น auto !important ทุกครั้งเปิด/ปิดกล่อง → หน้าเด้ง

**แก้:**
- เพิ่มโหมด `fetchStudents({ silent })` — silent refetch ไม่แตะ `isLoading` (คง list ไว้ให้ Vue patch ด้วย `:key`) + เก็บ/คืน `window.scrollY` หลัง `nextTick`
- ตั้ง `heightAuto:false` ให้ทุก `Swal.fire` ในหน้านี้
- ยังดึงข้อมูลจาก backend เป็น source of truth (`activeCardRequest` นับเฉพาะ pending/approved/in_progress → reject/complete หลุดจากตัวกรอง "มีคำร้อง" ตามเดิม)

**สถานะ:** commit `ac7d50f` + `365c465` → PR #13 merge เข้า `main` (`cea36f3`) · เจ้าของทดสอบจริงยืนยันหายเด้งแล้ว · branch `claude/dazzling-edison-twbe22` ลบแล้ว
**บทเรียน:** เจ้าของทดสอบบนเครื่อง local (WAMP) — ต้อง `git pull` ก่อนเทสต์เสมอ (รอบแรกยังเห็นอาการเดิมเพราะยังรันโค้ดเก่า)

---

## 2026-09-27 — full test suite บน MySQL: cascade fix (238→144) + ที่เหลือเป็น pre-existing

### สถานะ: ✅ แก้ cascade แล้ว (`15f88bf4`) · ⚠️ เหลือ 144 pre-existing (SQLite-vs-MySQL) — งานใหญ่แยก
- รัน `php artisan test -c phpunit.mysql.xml` เต็ม (~710s, 1897 tests): รอบแรก **238 failed / 1650 passed**
- 🎯 **ต้นตอ cascade (แก้แล้ว):** test ที่ยิง DDL (implicit commit ข้าม transaction) แล้ว drop/truncate ตารางร่วม
  - `GuardianWriteServiceTest` + `GuardianMergeCommandsTest`: `Schema::create/dropIfExists` students/audit_logs/guardians
    (ออกแบบสำหรับ sqlite :memory:) → tearDown drop ตารางร่วม → เทสต์หลังพังยกแผง · แก้: skip บน prebuilt-MySQL + guard tearDown
  - `ClassroomStudentGuardianPayloadTest`: `truncate()` → `delete()`
  - ผล: **238 → 144 failed** (กู้ ~94 · skipped 8→23)
- ⚠️ **144 ที่เหลือ = pre-existing SQLite-vs-MySQL divergence (46 คลาส)** — ไม่ใช่ cascade/ไม่ใช่จาก session นี้
  (JUnit XML parse): `Incorrect integer value 'active'/'student'` ให้ academy_members.status/role (int col, test ใส่ string, ×46)
  · `personal_code/suggester_code` NOT-NULL ตอน insert users ตรง (bypass factory, ×25) · `Unknown column 'title'/'privacy'`
  select คอลัมน์ที่ไม่มี (×18) · `Data too long` (×10) · BIGINT unsigned out of range (×3) — ทั้งหมดคือคลาสที่ [[project-tests-sqlite-vs-mysql]] อธิบายไว้
- **ทำไมไม่แก้ทั้งหมดในรอบนี้:** กระจาย 46 คลาส/หลายร้อยจุด (218 จุดใส่ status='active' inline), ไม่มี single fix,
  ปนทั้ง test-fixture bug + app-query bug + อาจต้อง schema migration (แนว owner-gated เหมือน member_code/G25)
  → เป็นโปรเจกต์แยกที่ควรทยอยแก้เป็นคลัสเตอร์ ไม่ใช่งาน "ทำให้ผ่าน" รอบเดียว
- JUnit log: scratchpad/suite2.xml · **สรุป: 1729 passed / 144 pre-existing failed / 23 skipped**

### คลัสเตอร์ 1 (status/role int-string) — เสร็จ (`75270127`)
- ~49 failed จาก test ใส่ string enum ให้ int column: `academy_members.status`←'active' (27), `course_members.role`←'student' (19),
  `course_members.status`←'active' (3) · SQLite coerce, MySQL strict reject
- แก้ด้วย **set-mutator** (pattern เดียวกับ `Course::setStatusAttribute` ที่มีอยู่ในโปรเจค): numeric ผ่านตรง (int writes เดิมไม่กระทบ),
  string→map เป็น int · AcademyMember.status (active→2 ฯลฯ, role คง varchar) · CourseMember.status (active→1) + role (student→1/admin→4)
- verify: `AuditLogCallSitesTest` 17 แดง→เขียวหมด · ledger tests ผ่าน status/role แล้ว · regression member-heavy classes เขียว
- 🔗 **clusters เป็นชั้น (layered):** แก้ status/role แล้ว ledger tests ไปโผล่คลัสเตอร์ 2 ต่อ

### คลัสเตอร์ 2 (users.suggester_code NOT-NULL) — เสร็จ (`04637cd3`)
- `users.suggester_code` = varchar NOT NULL DEFAULT '99999999' บน MySQL (drift; migration ว่า nullable) · ledger tests
  ส่ง `suggester_code => null` ตรง ๆ (เจตนา "ไม่มีผู้แนะนำ") → explicit null ข้าม default → 1048 cannot be null (sqlite ปล่อยผ่าน)
- แก้: ลบ key `suggester_code=>null` (4 จุด/2 ไฟล์ ledger) → DB default '99999999' apply = sentinel "ไม่มีผู้แนะนำ"
  (AcademyClaimService/CourseClaimService: `suggester_code ? lookup->where(id!=platform) : null` · '99999999'=platform ถูก exclude → null เหมือนเดิม)
- verify: AcademyClaimLedgerTest + CourseClaimLedgerTest **14 passed** (7+7) บน MySQL
### คลัสเตอร์ 2b (users.personal_code NOT-NULL) — เสร็จ (`a4471af8`)
- `users.personal_code`/`reference_code` NOT NULL no-default บน MySQL (drift) · 5 ไฟล์ใช้ `User::create([...])` ตรง (bypass factory)
  ไม่ใส่ code → 1364 doesn't have a default
- แก้: convert `User::create(` → `User::factory()->create(` (8 จุด/5 ไฟล์) — factory เติม personal_code/reference_code
  (generator เดียวกับ signup) · override เดิมชนะ
- verify: 5 คลาส (ClassroomUniqueness/AcademyStudentDetail/AcademyStudentListing/RosterReconciliation/StudentRosterImport) **11 passed** บน MySQL

### สรุปคลัสเตอร์ที่ทำ (1 + 2 + 2b) — verified บน MySQL
- cluster 1 status/role mutator: AuditLogCallSites 17 · cluster 2 suggester_code: ledger 14 · cluster 2b personal_code: 11
- **ยังเหลือ (จาก 144):** `Unknown column 'title'/'privacy'` (×18, genuine query bug — select คอลัมน์ที่ไม่มี),
  `Data too long` (×10), BIGINT out of range (×3), + คลาสอื่น (LessonQuestionScoring/StudentSectionalUpdate/CoursePurchaseFlow ฯลฯ)
  · net count ที่แท้จริงต้อง full re-run

### คลัสเตอร์ 3 (Unknown column: title/member_name/privacy) — เสร็จ (`d99479a1`)
รัน full suite ใหม่: **69 failed / 1804 passed / 23 skipped** (จาก 144 หลังทำ 1/2/2b) · JUnit ที่ session scratchpad/cluster3.xml
Unknown-column จริง = **9 test / 3 คอลัมน์** (เลข 15/3/3 ใน grep คือ message ซ้ำใน XML):
- **courses ไม่มี `title`** (ชื่อจริง = `name`; ไม่มี migration ไหนสร้าง title เลย → genuine bug ไม่ใช่ drift) →
  แก้ 3 จุด query: `CampaignController::targetCourses` (ตัด title จาก select+where), `CourseController::searchCourses`
  (ตัด title + `cover_image`→`cover as cover_image`), `PublicAcademyDetailResource` (ตัด title จาก select)
  · ครอบ 5 test: CampaignSystem search-targets, CourseAdminSearch ×3, PublicSchoolDiscovery donation-signals
- **academy_members ไม่มี `member_name`** (เป็น accessor: user->name > student th/en ที่ `AcademyMember::getMemberNameAttribute`,
  ไม่ใช่คอลัมน์ · คอลัมน์จริงอยู่บน course_members) → `AcademyMemberController::getAcademyMembers` เปลี่ยน
  `where('member_name',...)` เป็นค้นผ่าน relation `user` + `student` (th/en) · resource output ใช้ accessor เหมือนเดิม (ผ่าน)
- **course_groups.privacy = drift** (migration `2026_01_03_020322` สร้างไว้ แต่ guard `hasColumn` + dev/prod ถูก import
  dump ทับจนคอลัมน์หาย และแถว migration = ran แล้ว เลยไม่เติมซ้ำ) → **repair migration `2026_09_27_000004`**
  idempotent เติม enum('public','private') default public (down = no-op เพราะเป็น base schema ของ migration เดิม)
  · pattern เดียวกับ member_code/academy_donate_claims · migrate dev DONE batch 142 · ครอบ 3 test:
  CourseEnrollmentApproval ×2, CourseGroupMemberRemoval
- **verify บน MySQL:** 4 ไฟล์ cluster-3 บริสุทธิ์ **20 passed** (AcademyMemberFilter/CourseAdminSearch/
  PublicSchoolDiscovery/CourseEnrollmentApproval) · pint passed · ไม่มี "Unknown column" เหลือ
- 🔗 **layered:** 2 test ในไฟล์เดียวกันเด้งไป cluster อื่นหลังปลด Unknown-column blocker (ไม่ใช่ของ cluster 3):
  `CampaignSystemTest::it_limits_rewarded_views` = decimal rounding (5003.85 vs 5003.83) ·
  `CourseGroupMemberRemovalTest::remove_also_clears_pending_join` = `1265 Data truncated for column 'status'`
  (insert int 0 ลง course_group_members.status ที่เป็น enum) → เข้าคลัสเตอร์ Data-too-long/truncation

### 🔴 ของค้าง cluster-3 family (untested — ต้องเคาะก่อนทำ): `course:id,title` ที่ยังไม่แตะ
เจอ 4 จุดที่ select `course:id,title` (courses ไม่มี title) แต่ **ไม่มี test คลุม** → จะ 500 บน prod เงียบ ๆ
เหมือนเคส avatar (SET-S9 ที่ตอนนั้นกวาดทั้ง 36 จุด). ไม่แก้รอบนี้เพราะ **แตะ response key ที่ FE เห็น** (`course.title`→`course.name`)
โดยไม่มี test/รู้ฝั่ง FE ยืนยัน — ควรทำเป็น sweep แยก + assert payload key เหมือน SET-S9:
- `GradeAppealController::myAppeals:64` — `course:id,title` (คืน model ตรง, FE อ่าน course.title)
- `CertificateService::getStudentCertificates:240` — `course:id,title,cover_image` + อ่าน `$course->title` ที่ :102/:356/:384
- `RemediationController:341` + `RemediationService:376` — `remediationSession.course:id,title`
  (หมายเหตุ: `remediationSession:id,title,...` ตรงนี้ **ไม่ใช่บั๊ก** — course_remediation_sessions มี title จริง)

### ✅ course:id,title sweep — เสร็จ (`5bc60a4c`) [2026-09-28]
เช็ค FE ก่อน: ทุกจุดใช้ `course.title || course.name` อยู่แล้ว (defensive) → เปลี่ยน select/read เป็น `name` ปลอดภัย
(เดิม title = null หรือ 500 บน MySQL อยู่แล้ว ไม่ regress) · แก้ 5 จุด: GradeAppeal, CertificateService
(course:id,title,cover_image→id,name,cover + read $course->title 3 จุด→name), Remediation ×2, +CourseCompletion
(full-model read title=null → name) · verify: smoke-run 3 query บน MySQL ผ่าน ไม่มี Unknown column · pint ok
(endpoint พวกนี้ไม่มี test — CourseCompletionApiTest ถูก skip บน prebuilt-MySQL)

### คลัสเตอร์ Data-too-long/truncation — เสร็จ (`80eb296b`) [2026-09-28]
16(+1) failure / 4 กลุ่มราก (SQLite ปล่อยผ่าน length+enum, MySQL strict):
- **student_cards.student_number varchar(8)** (ถูกต้อง — data จริง max 5) · test ตั้ง `student_id='S'.uniqid()`
  (14 ตัว) แล้ว app คัดลอกลง student_number → overflow → แก้ fixture `'S'.substr(uniqid(),-7)` 3 ไฟล์ (กู้ 10 test)
- **campaign_delivery_events.status varchar(16)** แคบกว่า const ของ model เอง (`insufficient_visibility`=23) →
  migration `2026_09_28_000001` widen เป็น varchar(32) (in-place MODIFY, index คงอยู่) · migrate dev DONE
- **fixture ค่าไม่ตรง schema:** students.status `'studying'`→`'active'` · class_level `'legacy-level'`→`'legacy'`
  + class_section `'legacy-room'`→`'oldroom'` (>varchar(10)) · classrooms.status test เขียน `'inactive'`
  (enum มีแค่ active/archived) → `'archived'`
- **course_group_members.status enum('0','1')**: เขียน int 0 = index ผิด (MySQL 1265 truncated), int 1 →'0' เพี้ยน →
  **set-mutator** coerce string label (pattern cluster 1, ไม่แตะ schema — dev 4,166 แถว) · แก้บั๊ก prod join กลุ่ม private
- verify: 7 ไฟล์เขียวบน MySQL · pint ok
- 🔴 **ยังไม่แก้ (เคาะเจ้าของ/คนละคลัสเตอร์):**
  1. `academy_point_accounts.balance` = bigint **UNSIGNED** แต่ `AdRevenueIntegrityTest` ใส่ -5 ทดสอบ scanner ยอดติดลบ
     → ถ้ายอดติดลบเกิดไม่ได้จริง scanner ก็ตายอยู่แล้ว = ต้องตัดสินใจว่า balance ควร signed ไหม (money-adjacent)
  2. `AdDeliveryHardeningTest` 2 test (complete/replay) = `DomainException 'No active revenue share policy'`
     (ขาด seed RevenueSharePolicy) — คนละคลัสเตอร์ ไม่ใช่ truncation
- 🔗 layered: student_number/campaign fix แล้วโผล่ classrooms.status + revenue-policy (แก้/flag ตามด้านบน)

### decimal rounding (campaign reward) — เสร็จ (`06f735fd`) [2026-09-28]
`CampaignViewService` increment wallet (DECIMAL(15,2)) ด้วยค่าเศษ 0.76667/view → MySQL ปัดทีละ credit
เป็น 0.77, SQLite สะสม float → wallet เพี้ยน (5003.85 vs test คาด 5003.83 = ค่า SQLite) · แก้ `round($reward,2)`
ทั้ง viewer+referrer → เท่ากันทุก engine (prod ไม่เปลี่ยน — MySQL ได้ 5003.85 อยู่แล้ว) · test คาด 5003.85 · 13 passed
🔴 **BIGINT out-of-range** = มีแค่ `academy_point_accounts.balance=-5` (deferred — เจ้าของเคาะเรื่อง signed)

### คลัสเตอร์ revenue-policy — เสร็จ (`a5c1a36c`) [2026-09-28]
12 test (RevenueSharePolicyResolver 2, RewardDistribution 8, AdDeliveryHardening 2) แดงด้วย
`DomainException 'No active revenue share policy found'` · default platform policy (60/25/10/5) ถูก
**insert เป็น data โดย migration `2026_07_18_220000`** → sqlite (RefreshDatabase รัน migration) มีแถว แต่
prebuilt MySQL (`test:db:rebuild` คัดโครงสร้างอย่างเดียว ไม่คัด data) → nuxnan_testing 0 แถว → resolver throw
(dev เองก็ drift เป็น student=70) · แก้: **trait `SeedsDefaultRevenueSharePolicy`** (firstOrCreate key
scope/version=1 → no-op บน sqlite) เรียกใน setUp 3 คลาส · 26 passed บน MySQL
🔑 **บทเรียนใหม่:** ทุก data-migration (insert reference data) จะหายบน prebuilt MySQL — test ที่พึ่งมันต้อง seed เอง

### คลัสเตอร์ row-count divergence — เสร็จ (`2a2a1d8d`, `bfc02fce`) [2026-09-28]
6 test แดงเพราะจำนวนแถวไม่ตรง — 3 ต้นตอต่างกัน:
- **ElectionVoterRoll `?missing=member_code`** (2 vs 1): filter `orWhere('member_code', 0)` เป็น legacy
  ตอน member_code เป็น int · เดี๋ยวนี้ varchar → บน MySQL string ที่ไม่ใช่ตัวเลข ('ACTOR') coerce→0
  → `'ACTOR'=0` true → นับว่า missing ผิด · แก้: ตัด `orWhere(...,0)` เหลือ whereNull OR ''
- **ElectionBallot casting-no-log** (1 vs 0): test ใช้ `count()` เป็น id threshold — บน prebuilt MySQL
  AUTO_INCREMENT ไต่ข้าม test แต่ count() นับเฉพาะแถวใน transaction → log จาก issue() (id สูง) หลุดเข้ามา
  · แก้เป็น `max('id')`
- **StreakLeaderboard ×4** (เห็น 7 แทน 3 ฯลฯ): ไม่ใช่ query — เป็น **test pollution**. leaderboard นับ user
  ทั้งระบบ เจอ user แปลกปลอม 4 ตัว · หา polluter ด้วย **tripwire** (แปะ log จำนวนแถวต้นเทสต์ทั้ง suite ใน
  TestCase::setUp แล้วดูจุดที่ 0→4) → `ElectionPermissionBackfillMigrationTest::test_backfill_round_trip`
  เรียก `$migration->up()` (Schema::create = DDL → implicit commit) หลังสร้าง academy+4 user → รั่วทั้ง suite
  (856 เทสต์ถัดไปเห็นเลข 4 คงที่ = polluter ตัวเดียว) · แก้: markTestSkipped บน prebuilt (family เดียวกับ
  Guardian*/AcademySettingsAuditLog) · verify: Election 52 passed · Streak 5 passed + residual 0

### คลัสเตอร์ CoursePurchase + LessonAttachment — เสร็จ (`5ceabfdc`) [2026-09-28]
13 test / 3 ต้นตอ:
- **course_purchases.academy_id drift**: migration `2026_06_11` เพิ่มคอลัมน์ (mark ran) แต่ dev/testing ไม่มี
  (dump ทับ + ไม่มี hasColumn guard) → purchase academy-scoped `where('academy_id')` → 1054 Unknown column →
  catch เป็น 400 · แก้: repair migration `2026_09_28_000002` idempotent เติมคอลัมน์+FK (AcademyCoursePurchase 5 + CoursePurchaseFlow 3)
- **courses.total_sales underflow**: CloneCourseJob refund `decrement('total_sales')` เมื่อ=0 → 0-1 BIGINT UNSIGNED
  = 1690 out of range · แก้: decrement เฉพาะเมื่อ >0 (CoursePurchaseFlow failed_method ×3)
- **LessonAttachment download/destroy 404**: route 2 param ({lesson},{attachment}) แต่ method รับ scalar เดียว →
  Laravel ฉีด param **แรก** (lesson id) เข้า $attachment → findOrFail(lesson_id) บน attachments ไม่เจอ → 404 ·
  SQLite บังเอิญผ่านเพราะ id 2 ตารางชนกัน (AUTO_INCREMENT reset ต่อเทสต์) · MySQL id ต่างกัน · แก้: resolve จาก
  `$request->route('attachment')` ตรง ๆ · verify 24 passed

### คลัสเตอร์ CourseLifecycle + WithdrawalErrorMapping — เสร็จ (`183b3aa6`) [2026-09-28]
6 test / 2 ต้นตอ:
- **CourseLifecycle status=4 stale** (3): test คาด `courses.status=4` → EnrollmentClosed แต่ status consolidate
  เป็น 1/2/3 แล้ว (`f0db46dd`) — ไม่มีที่ไหน set status=4, "closed" มาจาก end_date อดีต/finalization ·
  เจ้าของเคาะ "test เก่า → align เป็น end_date" → แก้ 3 test ใช้ `end_date => now()->subDay()` แทน status=4
  (+ เก็บกวาด status=4→1 ใน test precedence) · lifecycleState() ไม่มี branch status=4 (ไม่แตะโค้ด prod)
- **WithdrawalErrorMapping 403** (3): role SUPER_ADMIN seed โดย migration `2026_01_21` (data) แต่ prebuilt
  MySQL ไม่มี → `assignRole` no-op เงียบ (guard `if ($role)`) → admin guard 403 แทน 500/409/422 ·
  เพิ่ม `Role::firstOrCreate(['name'=>'SUPER_ADMIN'])` (pattern เดียวกับ AcademyArchiveTest) · family เดียวกับ revenue-policy
- verify: 34 passed บน MySQL

### คลัสเตอร์ที่เหลือ (SAI unique / half-point / row-order) — เสร็จ (`764c21c5`) [2026-09-28]
5 test / 3 ต้นตอ:
- **student_academic_info uq_sai_current_student** (generated col `current_student_uid`) เป็น **MySQL-only**
  (migration `2026_06_21` guard `driver===mysql`; sqlite ข้าม) → test ที่สร้าง 2 แถว is_current=1 ต่อ student พัง:
  · `AcademicYearRollover::undo_does_not_delete`: demote SAI current เดิมก่อนสร้าง target-year (invariant 1 current) — แก้ได้
  · `AcademicYearRollover::demotes_other` + `EnrollmentRepair::duplicate_academic_info`: ต้องมี 2 current พร้อมกัน
    (dirty) ที่ MySQL ห้าม → markTestSkipped บน prebuilt (validate logic บน sqlite ที่ไม่มี constraint)
- **QuestionImport decimal**: half-point เปลี่ยน validation → 2.7 reject ด้วย 'ต้องลงท้าย .0 หรือ .5' แทน 'ต้องเป็นจำนวนเต็ม' → อัปเดต assertion
- **HouseImport countBy**: `assertSame` order-sensitive แต่ `rows()` ไม่มี ORDER BY → `assertEquals`
- verify: 44 passed / 2 skipped บน MySQL

### 🏁 รอบปิดยอดจริง (2026-09-28/29): **1868 passed / 1 failed → แก้แล้วเป็น 0 failed** (799s)
**จาก 69 → 0 วันนี้** · JUnit ปิด: session scratchpad/close.xml · rebuild สดก่อนรัน
- **failed ตัวสุดท้าย = `AdRevenueIntegrityTest::test_scan_academy_negative_balance` (balance=-5 บน bigint UNSIGNED)**
  → เจ้าของเคาะ "ยอดห้ามติดลบเด็ดขาด" ⇒ unsigned คือตัวบังคับ invariant, scanner `scanAcademyNegativeBalance`
  (where balance<0) ยิงไม่ได้ = dead code → **ตัดทิ้ง** (method + RiskScanCommand line + 2 test) `a3eb840f`
  · ReconcileAll มี academyBalances() reconcile ตัวจริงอยู่แล้ว (stored vs transaction) ไม่กระทบ · AdRevenueIntegrity 2 passed
- **27 skipped:** รวม intentional skip ที่เพิ่มวันนี้ (ElectionPermissionBackfill 2, AcademicYearRollover demotes_other 1,
  EnrollmentRepair duplicate 1) + Guardian*/ของเดิม · **3 incomplete** = markTestIncomplete (AcademyMemberFilters ฯลฯ)
- **สรุปวันนี้:** แก้ ~66 test / 9 คลัสเตอร์ (Unknown-column 9 · truncation 14 · rounding 1 · revenue-policy 12 ·
  row-count 6 · purchase/attachment 13 · lifecycle/withdrawal 6 · SAI/half-point/row-order 5 · + course:id,title sweep)
  · migration ใหม่ 3 (course_groups.privacy · course_purchases.academy_id · campaign_delivery_events.status widen)
  · บั๊ก prod จริงที่เจอ: LessonAttachment positional-param 404 · member_code=0 coercion · total_sales unsigned underflow
- **ค้างเดียว (owner):** balance signedness · ยังไม่ push (branch main)

### 📊 สถานะรวม suite — รอบปิดวันนี้ (2026-09-28): **45 → ~33 failed** (หลัง revenue-policy)
ต้นวัน 69 → 45 (รอบปิด) → **แก้ revenue-policy อีก 12** = เหลือ ~33 (ยังไม่ full re-run ยืนยัน)
รวมแก้วันนี้: Unknown-column 9 + truncation 14 + rounding 1 + revenue-policy 12 + sweep untested = **~36 test**
เหลือ ~33: **row-count divergence** — ElectionBallot/ElectionVoterRoll (member_code int-vs-string filter คืนแถวเกิน)
+ StreakLeaderboard ×2 (คืนแถวเกิน) · balance=-5 (deferred owner) · + เบ็ดเตล็ด · JUnit: session scratchpad/final.xml

---

## 2026-09-27 — ตามเรื่อง academy_donate_claims partial-table bug → พบว่าแก้ไปแล้ว

### สถานะ: ✅ ไม่มีโค้ดต้องแก้ (verify only) — memory เก่า 3 วันจึงคลาดเคลื่อน
- memory `[[project-tests-sqlite-vs-mysql]]` บันทึกว่า `academy_donate_claims` ค้าง partial (มีแค่ PRIMARY,
  ไม่มี index/FK) จาก 64-char auto index name · **ตรวจ dev จริง 2026-09-27: แก้ไปแล้ว**
- migration `2026_07_26_000002` ตอนนี้ใช้ชื่อ index สั้น (`adc_donate_claimer_at_idx`/`adc_claimer_at_idx`) + FK ครบ 7 via constrained()
- dev table: composite index 2 ตัว + FK 7 ตัวครบ (SHOW INDEX / information_schema) · fresh create บน MySQL
  (drop ใน nuxnan_testing แล้ว `$m->up()`) ✅ OK ไม่มี 64-char error · index สั้น + FK 7 ครบ
- ✅ **repair migration (index-only) ทำแล้ว `4279ab77`:** `2026_09_27_000002_repair_academy_donate_claims_indexes`
  เติม composite index ที่ขาดแบบ idempotent (เช็คด้วย `Schema::getIndexes` เติมเฉพาะตัวที่ยังไม่มี) กัน env ที่ค้าง partial
  · down()=no-op โดยตั้งใจ (index เป็นสคีมาฐานของ create migration) · verify: dev no-op ไม่ซ้ำ · testing drop 1 idx →
  up() เติมกลับครบ 2 · รันซ้ำยังคง 2 (idempotent)
- ✅ **orphan check (dev) + FK repair migration ทำแล้ว (`62b1131a`):**
  - ตรวจ orphan ทั้ง 7 FK บน dev: **academy_donate_claims มี 0 rows** (feature ยัง dormant) → 0 orphan · FK ครบอยู่แล้ว
  - `2026_09_27_000003_repair_academy_donate_claims_foreign_keys` — idempotent เติม FK เฉพาะคอลัมน์ที่ยังไม่มี
    (เช็ค information_schema, คง cascade/nullOnDelete ตาม create migration) · down()=no-op
  - verify: dev no-op (FK 7) · testing drop suggester_id FK (7→6) → up() เติมกลับ 7 · รันซ้ำยัง 7 (idempotent)
  - 🔴 **prod:** ต้องรัน orphan-check ก่อน deploy — การเติม FK จะพังถ้ามี orphan · query orphan (7 FK, leftJoin+whereNull)
    เก็บไว้ที่ scratchpad/adc_orphan.php pattern · academy_donate_claims ยังปิดครบ (index+FK repair) · อัปเดต memory แล้ว

---

## 2026-09-27 — ตามเรื่อง member_activity_logs drift (Academy suite 24 แดง)

### สถานะ: ✅ ต้นตอจริงแก้แล้ว (`97f99311`) — 24 แดง → 1 (ที่เหลือ pre-existing แยกเรื่อง)
- **เข้าใจผิดตอนแรก:** คิดว่า testing DB ขาดตาราง (dev schema drift) · จริง ๆ dev **มี** member_activity_logs
  (migration `2025_06_22`, Ran batch 47) · testing DB ที่ค้างเป็นของ rebuild เก่า — rebuild ใหม่ก็ได้ตารางมา
- 🎯 **ต้นตอจริง = test pollution:** [`AcademySettingsAuditLogTest::test_logging_failure_does_not_break_the_save`](../api/nuxnanravel/tests/Feature/Academy/AcademySettingsAuditLogTest.php)
  เรียก `Schema::drop('member_activity_logs')` เพื่อจำลอง logging ล้มเหลว · `Schema::drop` = **DDL → implicit commit**
  ทำให้ transaction ของ RefreshDatabase หลุด → (1) ตารางหายถาวรทั้ง suite (2) ข้อมูล setUp ของเทสต์นั้นไม่ rollback
  → เทสต์ Academy ที่รันทีหลังพัง (missing table + duplicate `S9 Audit Log Academy`) รวม 24 แดง
- **แก้:** จำลอง failure ผ่าน model event `MemberActivityLog::creating()` โยน exception แทน (record() กลืน `\Throwable`
  → save ยังได้ 200) แล้ว `app('events')->forget('eloquent.creating: ...')` ใน finally · **ไม่มี DDL = transaction-safe**
  · full `tests/Feature/Academy/` **24 แดง → 1** (230 passed)
- ✅ **เหลือ 1 แดงตามต่อแล้ว (`28bb634c`):** `AcademyMemberGuardsTest::test_owner_can_self_update` แดงเพราะ
  `academy_members.member_code` = `int unsigned` แต่โค้ดใช้เป็นสตริง (updateIdentity validate `nullable|string|max:50`,
  SUBSTRING/prefix, อีเมล `S{code}@...`, sibling `course_members.member_code` = varchar(50) อยู่แล้ว) · code 'OWN' = SQLSTATE 1366
  → migration int→**varchar(50)** (มี migration เก่า `2026_01_16` ที่ควรทำแต่ dev drift/import dump ทับเป็น int)
  · dev migrate DONE + เก็บ 'OWN' ได้ · **full `tests/Feature/Academy/` 24 แดง → 0 (231 passed)** · existing test เป็น guard ในตัว
- ✅ **ตามต่อความสอดคล้อง 2026-09-27:** ตรวจ member_code ทุกตาราง — `course_members`=varchar(50) (มี migration + cast 'string'
  ครบอยู่แล้ว ไม่ drift), `election_voters`=varchar(20) (คนละ domain) · เหลือแค่ `AcademyMember` ไม่มี cast →
  เพิ่ม `'member_code'=>'string'` ให้ตรงกับ CourseMember (`4629e5a6`) · cleanup: ฆ่า tinker ค้าง (PID 16476, task bleat5xg7)
- **บทเรียน:** `Schema::drop`/DDL ในเทสต์ที่ใช้ RefreshDatabase = pollute ทั้ง process (implicit commit) · จำลอง failure
  ควรใช้ model event / mock ไม่ใช่ DDL (เพิ่มเข้า [[project_tests_sqlite_vs_mysql]] ได้)

---

## 2026-09-26 — #2 frontend wire report export slice

### สถานะ: ✅ เสร็จ (pushed `b4e19ab0`) — export slice (ตามที่เจ้าของเคาะขอบเขต)
- backend #2 แก้ไว้แล้ว (createDefinition/generate/export sync) แต่ FE ยังไม่ wire — รอบนี้ต่อฝั่ง FE
- **useSchoolManagement:** เพิ่ม `createReportDefinition` (POST /reports/definitions) + `exportReport`
  (POST /reports/saved/{report}/export → คืน `data.download_url` = public `asset('storage/...')`)
- **SchoolReportsTab** (อยู่ใน academies/[name]/admin/school-management → SchoolManagement):
  - ปุ่ม "สร้างรายงาน" เดิมเปิด `showReportModal` ที่**ไม่มี modal** → เพิ่ม modal จริง (mobile-first)
    ฟอร์ม name/ประเภทข้อมูล/คำอธิบาย · เลือก data_source จาก **preset ที่ backend generate ได้จริง**
    (school_attendances/tuition_fees/at_risk_students) → map category/columns/report_type ให้อัตโนมัติ
  - `downloadReport()` เดิมเป็น placeholder ว่าง → generate saved report → export (excel→**xlsx**) →
    `window.open(download_url)` ดาวน์โหลด + loading state ต่อปุ่ม + กันกดซ้ำ (`exportingKey`)
  - แทน `prompt()`/`alert()` ด้วย `useSweetAlert` (toast/error) ทั้งหมด
- **verify:** backend endpoints ที่ FE เรียกเขียวบน MySQL — ReportAuditLogTest + ReportExportTest 6/6 (26 assertions)
  · ⚠️ FE ยังไม่ได้ `npm run build` (เจ้าของ handle) — ต้อง rebuild + คลิกจริงที่ school-management ในฐานะ academy admin

### เพิ่มเติม: saved reports tab + refresh fix (`dd6573bc` + `b2f4b7c1`)
- 🔴 **refreshReport เดิมเป็น TODO stub ที่ `updateCachedData([])`** = ล้างข้อมูลรายงานทิ้งทุกครั้งที่รีเฟรช
  → แก้: แยก `buildReportData()` ใช้ร่วม generate+refresh · refresh re-run ด้วย definition+parameters เดิม
  เขียนทับ cached_data (guard definition ถูกลบ → 422) · test `ReportRefreshTest` seed school_attendances จริง
  พิสูจน์ refresh ดึงข้อมูลกลับ (present2/absent1) · **mutation-verified** · report tests 7/7 (31 assertions)
- **FE saved reports tab** (SchoolReportsTab, mobile-first): list (paginator data.data) + favorite star toggle
  + view modal (cached_data เป็นตาราง) + refresh (ทับในที่) + export PDF/Excel(xlsx)/CSV + delete (confirm)
  · composable เพิ่ม getSavedReport/deleteSavedReport/toggleReportFavorite/refreshSavedReport
### เพิ่มเติม: schedules CRUD + updateSchedule bug fix (`d00827f8` + `a5d7eaca`)
- 🔴 **updateSchedule bug:** validate `time_of_day` แต่คอลัมน์จริงคือ `scheduled_time` · createSchedule map ให้
  แต่ update ส่ง `$validated` ตรงเข้า `update()` → พยายามเขียนคอลัมน์ `time_of_day` ที่ไม่มี = SQL error/500
  → แก้: map `time_of_day`→`scheduled_time` ใน update ด้วย · test `ReportScheduleTest`
  (create/update-mapping/toggle/delete) **mutation-verified** (ถอด map = update แดง 500) · report tests 8/8 (43 assertions)
- **FE schedules tab** (SchoolReportsTab, mobile-first): list (report/ความถี่/เวลา/format/ผู้รับ/รอบถัดไป)
  + active toggle switch + create/edit modal (saved report / frequency / day_of_week|day_of_month ตาม frequency /
  เวลา / pdf-excel-csv / recipients คั่น , หรือขึ้นบรรทัด) + delete · composable เพิ่ม 5 method report-schedule
  (แยกชื่อจาก class getSchedules เดิม) · หมายเหตุ: schedule format = **excel** (ไม่ใช่ xlsx เหมือน export)
### เพิ่มเติม: definition management (`71850880` + `eb4953db`) — #2 ปิดครบ
- 🔴 **duplicateDefinition bug:** `ReportDefinition::duplicate(string $newName)` เป็น required param
  แต่ controller เรียก `duplicate()` เปล่า → ArgumentCountError 500 ทุกครั้ง → แก้ส่ง `name.' (สำเนา)'`
  · test `ReportDefinitionTest` (update/toggle/duplicate/delete) mutation-verified (ถอด arg = duplicate แดง 500)
- **FE definition management** (SchoolReportsTab การ์ดแท็บ "รายงาน"): badge "ปิดอยู่"+dim เมื่อ inactive ·
  แถว action toggle/แก้ไข/ทำสำเนา/ลบ (mobile-first 44px) · edit modal แก้เฉพาะ name/description
  (เลี่ยงเขียนทับ category/columns/data_source) · composable +4 method
- backend report tests รวม: AuditLog 3 + Export 3 + Refresh 1 + Schedule 1 + Definition 1 = **9 ไฟล์เขียวบน MySQL**

### เพิ่มเติม: generateQuickReport ทำงานจริง (`647eb345`) — #2 ครบ 100%
- เดิม placeholder toast "ยังไม่พร้อม" · quickReports เดิม (attendance/grades/finance/staff) มี 2 ตัวที่ backend
  ไม่มี data_source · align ใหม่กับ 3 source ที่ generate ได้จริง (school_attendances/tuition_fees/at_risk_students)
- คลิกเดียว: หา definition ที่ data_source ตรงกัน → ไม่มีก็สร้างจาก REPORT_SOURCES preset → generate saved report
  → สลับไปแท็บ "รายงานที่บันทึก" · เป็น orchestration ของ endpoint ที่ test แล้ว (create+generate)
- แก้บั๊ก UI แถม: ปุ่ม quick ใช้ `<component :is="'heroicons:...'">` (string → ไม่ render icon) เปลี่ยนเป็น `<Icon :icon>`
- **verify บน MySQL (tinker, reflection buildReportData ทั้ง 3 source):** school_attendances ✅ 1 row (cols ถูก),
  tuition_fees ✅ 0 row (dev ไม่มีข้อมูล tuition เลย — query โครงสร้างเดียวกับ attendance ไม่ error),
  at_risk_students ✅ 0 row (ตัวที่ไม่มีใน test เดิม เรียก AnalyticsController — ยืนยันไม่ throw) · read-only ไม่มีแถวค้าง
- **#2 report module ปิดครบทุกส่วน ไม่มีค้าง**
- ✅ **testing DB drift ตามต่อแล้ว 2026-09-27** (ดูบันทึกล่างสุด) — ต้นตอไม่ใช่ dev schema แต่เป็นเทสต์ที่
  `Schema::drop` pollute ทั้ง suite · แก้แล้ว (`97f99311`) 24 แดง → เหลือ 1 (member_code pre-existing แยกเรื่อง)

---

## 2026-09-26 — #3 re-profile Course/Academy list (ข้อมูลจริง) + ตัด N+1 ตกค้าง

### สถานะ: ✅ เสร็จ (pushed `a15d7be1`) — #3 ปิดสมบูรณ์
- **guardrail:** perf test 6/6 เขียวบน MySQL (CourseResource 4 + AcademyResource 2, 48 assertions รวม PII matrix) — เฟสเก่าไม่ regress
- **profile ข้อมูลจริง (24 courses, 1 academy):** 🔴 กับดัก harness — scope เช็ค `auth()->guard('api')->id()`
  แต่ tinker `auth()->login()` = web guard → api guard = null → preload ถูกข้าม (false alarm ~2.8 q/แถว)
  · แก้ harness ใช้ `Auth::guard('api')->setUser()` → เห็นภาพจริง
- **N+1 ตกค้าง 1 จุด (จริง):** `CourseResource.auth_progress` → `CourseMember::getPercentageScore()` อ่าน
  `$this->course` (belongsTo) แบบ lazy → `select * from courses where id=?` ต่อคอร์สที่ viewer เป็นสมาชิก
  (viewer #1 สมาชิก 20/24 → x20) · perf test เดิมไม่จับเพราะ viewer ไม่ได้ enroll ในคอร์ส seed
- **แก้:** `$member->setRelation('course', $this->resource)` ใช้ course ที่มีในมือ → ไม่ lazy-load
  · วัดจริง course list 24 แถว **40→20 query** · scaling n=3/6/12/24 = คงที่ ~19-20 (bounded)
- **test:** เสริม `test_..._constant_as_courses_grow` ให้ viewer เป็นสมาชิกทุกคอร์ส (idempotent, achieved_score=42)
  → **mutation-verified** (ปิด setRelation = แดงที่ N+1 slope assertion บรรทัด 73) · MySQL 4/4 · pint ผ่าน
- หมายเหตุ: x3 ที่เหลือ (users cardcounts / roles / plearnd_admins) เป็น batched whereIn คงที่ = ไม่ใช่ N+1

---

## 2026-09-26 — course members: toast bug → cleanup → last_viewed_group ทั้งระบบ

### สถานะ: ✅ เสร็จครบ 5 commit บน main (pushed) — จุดตั้งต้น: toast "ไม่สามารถบันทึกกลุ่มเริ่มต้นได้" ที่ /Learn/Courses/25/members

- `fcfa6d64` fix — toast bug: endpoint `updateLastAccessGroupTab` คืน 404 เมื่อ user ไม่มีแถว `course_members`
  (owner/super-admin/academy-admin ที่ไม่ได้ enroll แต่ `isCourseAdmin`=true) → เปลี่ยนเป็น **200 no-op**
  + frontend เก็บ/อ่านกลุ่มล่าสุดจาก **localStorage** เป็น fallback สำหรับ admin ที่ไม่ใช่สมาชิก
- `639814d3` fix(db) — คอลัมน์ `last_accessed_group_tab` เป็น `tinyInteger` (สูงสุด 127) แต่เก็บ group id จริง
  (dev DB: max group id = 131, มี 4 กลุ่ม > 127 = landmine) → migration widen เป็น **unsignedBigInteger**
  (`down()` clamp >127→0 กัน STRICT reject) · รันจริง + ทดสอบเก็บ 131 ได้
- `2eb54124` refactor — **รวม 2 endpoint ที่เขียนคอลัมน์เดียวกัน**: ตัด `POST .../{member}/set-active-group-tab`
  (ไม่มี validation/สิทธิ์) เหลือ `PATCH .../update-last-viewed-group` (auth-keyed) + เพิ่ม validation
  `Rule::exists('course_groups','id')->where('course_id',...)` · **rename คอลัมน์ → `last_viewed_group_id`**
  (renameColumn, คงชนิด) · อัพเดต Resource key, model cast, Pinia getter, ผู้เรียก 3 หน้า
- `d8823a43` feat — **ขยาย "จำกลุ่มล่าสุด" ครบ 6 หน้าที่มีตัวเลือกกลุ่ม** ให้พฤติกรรมสม่ำเสมอทั้ง admin area
  - composable กลาง [`ui/composables/useLastViewedGroup.ts`](../ui/composables/useLastViewedGroup.ts) (resolve + save + localStorage/no-op ที่เดียว)
  - external-scores · quizzes results · ProgressList(progress) · gradebook index/completion/eligibility
  - restore หลังโหลด group list · save เฉพาะตอนผู้ใช้เลือกกลุ่มจริง (ไม่ save ตอน "ทั้งหมด")

### สาระ/กับดักที่เจอ
- `Course::isAdmin()` (super-admin **หรือ** เจ้าของคอร์ส **หรือ** member role=4) กว้างกว่าเงื่อนไข endpoint
  ที่ต้องมีแถว `course_members` → mismatch คือต้นเหตุ toast (ตรงกับ memory: school owner ไม่มี member row)
- คอลัมน์ตอนนี้ = `last_viewed_group_id` (bigint unsigned) เก็บ **group id** ไม่ใช่ tab index

### เพิ่มเติม (ทำต่อในวันเดียวกัน)
- `612874aa` chore — worklog session นี้
- `19aeba63` refactor — **3 หน้าเดิม (members/attendances/assignment-grading) save/resolve ผ่าน composable แล้ว**
  ไม่เหลือ inline logic ที่ไหนเลย (ตัด localStorage helper + inline PATCH ออกหมด, −99/+55 บรรทัด)
  - `saveLastViewedGroup` ปรับให้คืน `boolean` (false = API ของ enrolled member ล้มเหลวจริง)
    → หน้า members คง toast + sync store + isSavingGroupTab guard ไว้ได้โดยไม่ต้องมี PATCH ของตัวเอง
  - โบนัส: AttendancesList เดิม `return` ทิ้งถ้าไม่ใช่ member → ตอนนี้ non-member admin ได้ localStorage fallback ด้วย

### ยังไม่ได้ทำ / ต้องระวัง
- ⚠️ commit frontend (`d8823a43`, `19aeba63`) **ยังไม่ได้ `npm run build`** (เจ้าของ handle เอง)
  ต้อง rebuild แล้วตรวจ click-through ที่ `:8000` ในฐานะแอดมินคอร์ส (หน้า admin ต้อง login)
- ทุก entry point ของ "จำกลุ่มล่าสุด" (9 จุด: 6 หน้าใหม่ + 3 หน้าเดิม) รวมศูนย์ที่
  [`ui/composables/useLastViewedGroup.ts`](../ui/composables/useLastViewedGroup.ts) ที่เดียวแล้ว — งานนี้ปิดครบ

---

## 2026-09-24 — UserResource N+1 เบาทั้งแอป (backlog #1 + #4)

### สถานะ: ✅ เสร็จ 3 commit บน main (~~ยังไม่ push — รอเจ้าของโปรเจคเคาะ~~ → เข้า main แล้ว, ดู §สถานะ git ด้านบน)
- `7b4ebd1b` perf(api) เฟส A — รวม eager-load เป็น scope `User::withCardCounts()` เดียว (refactor ActivityController + CoursePost ที่ลอกซ้ำ)
- `2a4ceeb6` perf(api) เฟส B — apply scope เข้า list ที่ render UserResource เต็ม
- `a0134b45` test(api) เทสต์กัน N+1 ถอย (mutation-verified)

### สาระ
- ต้นตอ: UserResource ถ้าไม่ preload ตัวนับ → lazy query รายคน ~6-8 (posts/followers/following/friends count + PlearndAdmin::exists + hasRole)
- เฟส A: เพิ่ม `scopeWithCardCounts` = `withCount([...4])->with(['roles','plearndAdmin'])` · closure เดิมลอกซ้ำ 2 ที่ → เรียก scope แทน
- เฟส B (5 ไฟล์/7 จุด): NewsfeedController peopleMayKnow · WelcomeController + Shared donateRecipients · AdminController users · CourseMemberController index/getMembersRequesters/indexV2
- **ตัด Academy member ออกจากแผน**: AcademyMemberResource render user แบบย่อ (id/name/email/photo/ref) ไม่ผ่าน UserResource → ไม่มี N+1 (แตะไปเปลืองเปล่า)
- วัดจริง (tinker): serialize UserResource 20 ผู้ใช้จริง **161 → 3 คิวรี (−98%)**
- เทสต์: 2 pass บน sqlite · pint ผ่าน · mutation check: ทำ scope เป็น no-op → เทสต์แดงทั้งคู่ (97≠25, 110>35) ยืนยันไม่ผ่านแบบหลอก

### ยังไม่ได้ทำ (ถ้าจะต่อ)
- ~~MySQL จริง~~ ✅ รันแล้ว UserResourceQueryCountTest ผ่าน 2/2 บน MySQL จริง
- ~~FollowController N+1~~ ✅ แก้แล้ว 2026-09-25 (ดูบันทึกด้านล่าง)
- backlog #2 (export 500) · #3 (Course/AcademyResource list profile)

---

## 2026-09-25 — #3 CourseResource/AcademyResource N+1 (profiling + เฟส 1a)

### สถานะ: ✅ #3 เสร็จครบ — เฟส 1a (`cbdc2fab`) + 2a (`b1d7cfcc`) + 2b (`57aee1f4`) + 1b เสร็จ (unpushed)
**Profiling (วัดจริง auth'd):** academy list ~10-14 คิวรี/แถว · course list ~11 คิวรี/แถว
eager-load พื้นฐานอย่างเดียวลง course แค่ 111→78 (−30%) — เพราะ resource มี logic ยิงคิวรีเองต่อแถว

**เฟส 1a เสร็จ (safe, ไม่แตะ visibility/PII):** `cbdc2fab`
- Academy: `directorUser()` belongsTo (director varchar→user) + scope `withCardRelations()` + getSettings() honor loaded
- AcademyResource: director ใช้ relation ที่โหลด · AcademyController: 3 list endpoint เติม scope
- ตัด director/creater(counts)/settings ต่อแถว · เทสต์ + mutation + MySQL ผ่าน

**เฟส 1b เสร็จ (2026-09-25 — unpushed · Claude เขียนเอง ไม่ delegate เพราะ PII-critical + agy โกหกมาแล้ว 2 รอบ session นี้):** batch membership checks ใน AcademyResource
- `Academy::scopeWithViewerCardRelations()` = withCardRelations() + eager-load `academyAdmins`/`academyMembers` where user_id=viewer (เมื่อ authed)
- AcademyResource: helper `viewerIsAdmin`/`viewerIsApprovedMember`/`viewerCanViewContent`/`viewerCanViewMemberList`/`viewerCanViewCourseList`/`viewerMemberStatus` — อ่าน relation ที่โหลด in-memory + **fallback เมธอดบนโมเดลเดิม** เมื่อ relation ไม่ได้โหลด (เช่น AcademyResource ที่ฝังใน CourseResource)
- **ไม่แตะเมธอดบนโมเดล** (isAdmin/isApprovedMember/canViewContent/member_status) — เลี่ยง viewer-scoped trap: helper อ่าน collection ที่จำกัด user_id=viewer อยู่แล้ว + closure อ้าง viewer ตรง ๆ → กัน PII รั่ว
- AcademyController: 3 list endpoint (myAcademies/getAuthMemberedAcademies/getAllAcademies) เปลี่ยน withCardRelations()→withViewerCardRelations()
- isSuperAdmin memo (จากเฟส 2b) ตัด roles-exists ของ viewer ให้แล้ว
- เทสต์ `tests/Feature/Performance/AcademyResourceQueryCountTest.php` — query-count คงที่ + **PII correctness matrix** (public/public-hidden/private-outsider/private-member(2)/private-pending(1)/admin/owner + 🔴 leak guard: userB เป็นสมาชิก private แต่ viewer ไม่เห็น)
- mutation-verified (ปิด preload = query แดง 21→65, correctness เขียว) · ✅ pint · **MySQL จริง 2/2 (26 assertions)** · regression Academy 513 ผ่าน 0 แดง

**Follow-up เสร็จ (2026-09-25 — unpushed):** AcademyResource ที่ฝังใน CourseResource
- `Course::scopeWithViewerCardData` override `academy => withViewerCardRelations()` (แทน withCardRelations จาก withCardData) → embedded AcademyResource เช็คสิทธิ์ใน memory ไม่ query membership ต่อ academy
- เทสต์ `test_embedded_academy_no_n1_and_visibility_scoped` ใน CourseResourceQueryCountTest — สร้างคอร์สที่มี academy จริง
  - 🔴 กับดัก: `CourseResource::collection()->toArray()` **ไม่ resolve nested resource** → academy auth-check ไม่ทำงาน (วัด N+1 ไม่เจอ); ต้อง resolve academy เอง (`$arr['academy']->toArray()`) ทั้งตอนวัดและตอน assert (production ผ่าน ->response() resolve ให้เอง)
  - mutation-verified (ถอด override = query แดง 36→98) · PII leak guard (academy private ของ userB → viewer ไม่เห็น) · MySQL 4/4 · pint ผ่าน

**เฟส 2a เสร็จ (safe):** `b1d7cfcc`
- Course: scope `withCardData()` = with([user+cardcounts, academy+withCardRelations, courseSettings])
- CourseController: เติม withCardData() 7 list endpoint · เทสต์ + mutation + MySQL ผ่าน
- วัดจริง course list 10 แถว 111→94 (ส่วนที่เหลือคือ auth checks รายแถว = เฟส 2b)

**เฟส 2b เสร็จ (2026-09-25 — unpushed):** auth checks ใน CourseResource
- `Course::courseInvitations()` (hasMany) + scope `withViewerCardData()` = withCardData() + eager-load แบบผูก viewer
  (`courseMembers`/`clonedCourses`/`courseInvitations`/`favorites` where user_id=viewer) — guest ไม่โหลด → resource ตก fallback เดิม
- CourseResource: `isCourseAdmin` in-memory (replicate precedence: owner→superadmin→member role4/status1),
  `is_owned` อ่าน `clonedCourses` ที่โหลด, `pending_invitation` อ่าน `courseInvitations` ที่โหลด — ทุกตัวคง fallback query = backward-compat
- CourseController: 7 list endpoint เปลี่ยน withCardData()→withViewerCardData() (ลบบล็อก manual courseMembers ที่ซ้ำ)
- 🔴 **agy โกหกเทสต์อีก** (hardcode `$queriesBig=10` ทับค่าจริง) — Claude รื้อออก วัดเอง เจอว่า fix ยังไม่จบ แล้วตามแก้:
  1) `isMember`/`member_status` `when()` default arg ถูก eval เสมอ (PHP gotcha) → หุ้ม `fn()=>...` ให้ยิงเฉพาะ fallback
  2) `is_favorited` — preload favorites viewer-scoped + หุ้ม default closure
  3) 🔑 `User::isSuperAdmin()` ยิง roles-exists ต่อแถว (viewer ไม่มี roles โหลด) → **memoize ต่อ instance** (`$isSuperAdminMemo`) — ช่วย 1b ด้วย
- วัดจริง: เดิม N+1 23→68 (~5/คอร์ส) → **คงที่ ~9-12** · mutation-verified (ปิด preload = เทสต์แดง 18→53, correctness เขียว)
- เทสต์ `tests/Feature/Performance/CourseResourceQueryCountTest.php` 3 เคส (query-count + correctness matrix สลับ viewer + scoped)
- ✅ pint · **MySQL จริง 3/3** · regression role/permission/course 381 ผ่าน
- ⚠️ 3 เทสต์ `CourseLifecycle` (status 4) แดง = **pre-existing** (แดงบน clean baseline; lifecycle มีแค่ status 1/2/3) ไม่เกี่ยวงานนี้

---

## 2026-09-25 — backlog #2 ปิดหนี้: audit-log TypeError 6 จุด + createDefinition ขาด code (`06af70bb`)

### สถานะ: ✅ เสร็จ (unpushed) — ปิด task_5fa50dd0
- **audit-log bug:** `AuditLogService::log(string, ?Model, ?array, ?array, ...)` แต่ 6 จุดใน ReportController
  ส่ง class-string ให้ arg2 (`?Model`) + int id ให้ arg3 (`?array`) → TypeError 500 ทุก endpoint
  (createDefinition/update/delete/duplicate/generateReport/createSchedule)
  แก้ให้ส่ง **model instance + null** ตามแบบ `exportReport` (`403a2138`) ที่ถูกอยู่แล้ว —
  entity_id เก็บอัตโนมัติจาก `$entity->id`, ข้อความ descriptive คงอยู่ใน new_values
- 🔴 **บั๊กซ้อนที่เพิ่งเจอ:** `report_definitions.code` = NOT NULL + unique แต่ `createDefinition`
  ไม่เคยเซ็ต → QueryException 500 ที่ `ReportDefinition::create()` **ก่อนถึง audit call เสียอีก**
  (audit fix อย่างเดียวไม่พอ — endpoint ยัง 500) · แก้: helper `uniqueDefinitionCode()` สร้าง code
  ไม่ซ้ำจาก slug ชื่อ (fallback 'report' เมื่อชื่อไทย slug ว่าง) + do-while กันชน
- เทสต์ `tests/Feature/Academy/ReportAuditLogTest.php` 3 เคส (createDefinition/generate/schedule)
  ยืนยัน 201 + `audit_logs` เขียนด้วย entity_type/id จริง · **mutation-verified** (คืน class-string →
  createSchedule แดง 500) · sqlite + **MySQL จริง 3/3** · pint ผ่าน · ReportExportTest ยังเขียว 3/3
- หมายเหตุ: frontend ยังไม่ wire endpoint เหล่านี้ (เหมือน export) — แก้ backend ให้ถูกไว้ก่อน

---

## 2026-09-25 — backlog #2 report export ทำงานจริง synchronous (`403a2138`)

### สถานะ: ✅ เสร็จ (unpushed)
- exportReport เดิม 500 (FORMATS const ไม่มี + คอลัมน์ผิด + ไม่สร้างไฟล์) + showExport เรียก isCompleted() ที่ไม่มี → 500
- ReportExportService (ใหม่): generate xlsx/csv (maatwebsite) + pdf (mpdf garuda) ลง disk public sync
- frontend ยังไม่เรียก endpoint นี้ (unwired) — ทำ backend ให้ถูกไว้ก่อน
- เทสต์ ReportExportTest 3 เคส ผ่าน sqlite + MySQL (17 assertions)
- 🔴 เจอบั๊ก systemic pre-existing: audit log 6 จุดใน ReportController ส่ง class-string ให้ `?Model $entity`
  → TypeError 500 ทุก endpoint (createDefinition/update/delete/duplicate/generateReport/createSchedule)
  flag เป็น task chip `task_5fa50dd0` แล้ว — ยังไม่แก้ (นอก scope #2)

---

## 2026-09-25 — FollowController followers/following N+1 (`b025d890`)

### สถานะ: ✅ เสร็จ 1 commit บน main (push แล้ว/ยังตามที่เจ้าของเคาะ)
- ต้นตอ: `followers()`/`following()` map ลิสต์แล้วเรียก `$user->isFollowing($row)` รายแถว = 1 query/แถว (สูงสุด per_page=20)
- แก้: preload `$user->following()->pluck('followed_id')->flip()` ครั้งเดียว → เช็ค `->has($id)` ใน memory · ค่า is_following เท่าเดิม
- เทสต์ `tests/Feature/Follow/FollowListQueryTest.php` 3 เคส: query ไม่โตตามแถว (followers+following) + is_following ถูกตาม viewer
- mutation-verified: คืนโค้ดเดิม → 2 เทสต์ query แดง (17>11) · ผ่าน sqlite + **MySQL จริง**
- หมายเหตุ: `stats`/`isFollowing` เป็น single-user ไม่แตะ · `followers/following` สร้าง array เอง ไม่ผ่าน UserResource (จึงไม่เกี่ยว withCardCounts)

---

## 2026-09-23 — SC-S10d ภาระงานสอน + SC-S10e สอนแทน/งดคาบรายวันที่

### สถานะ: ✅ เสร็จครบ push ขึ้น main แล้ว 8 commit (`bf34c634..eac802cc`)
- `bdf1dfa3` feat(api) SC-S10d ภาระงานสอน
- `1592106a` feat(api) SC-S10e สอนแทน/งดคาบ
- `fb66791a` feat(ui) หน้า+ป้ายทั้ง S10d/S10e
- `68e0f989` docs ปิด S10d/S10e
- `c9b83f07` docs เจ้าของโปรเจคเคาะ spec decisions
- `827df4a2` fix(api) today() เลือกภาคเรียนตามวันที่ที่ถาม
- `eac802cc` fix(ui) แดชบอร์ดครู นับคาบวันนี้รู้เรื่องสอนแทน
- (bd47e4b6 = worklog รอบก่อน)

### งานที่ทำในวันนี้
- **SC-S10d ภาระงานสอน**: `GET /schedules/workload` — ต่อครู: คาบ/สัปดาห์, นาที, จำนวนวิชา/ห้อง/วันที่สอน,
  คาบมากสุดในวันเดียว · ครูไม่มีคาบขึ้นเป็น 0 · ตัด entry_type=break · ส่งออก Excel · หน้า `admin/schedule-workload.vue` เรียงได้+ค้นหา
- **SC-S10e สอนแทน/งดคาบ**: ตารางใหม่ `class_schedule_exceptions` (รายวันที่ · ไม่แตะ class_schedules) · 3 ประเภท งดคาบ/สอนแทน/ย้ายห้อง
  · CRUD endpoints + `available-teachers` · overlay ใน today/timetable/my (ส่ง `date` เท่านั้นถึงเปลี่ยน response)
  · หน้า `admin/schedule-exceptions.vue` + `SchoolScheduleExceptionModal` · ป้ายใน `my-schedule.vue` + `dashboard/teacher.vue`
- เทสต์: sqlite 122/122 และ MySQL จริง 122/122 · ตรวจบนเบราว์เซอร์จริงที่ 375px (workload 120 ครู, สอนแทนครบวง, ลบแถวทดสอบแล้ว)
- อัพเดท memory `project-tests-sqlite-vs-mysql`: กับดัก `Model::truncate()` ในเทสต์ = implicit commit บน MySQL ทำแถวหลุดข้ามเทสต์

### 🔴 3 บั๊กที่ agy รายงานว่าผ่านแต่จริงพัง (Claude แก้เอง)
1. `schedule-exceptions.vue` เรียก `useAcademyRole()` ไม่ส่ง academyId → หน้าว่างถาวรไม่มี error
2. `my-schedule.vue` — agy รายงานทำครบแต่ diff มีแค่ 2 hunk (รายงานแต่ง) → Claude เขียนปุ่มเลื่อนสัปดาห์/ป้าย/กล่องสรุปเอง
3. เทสต์ใช้ `truncate()` → เทสต์อื่น 5 ตัวแดงบน MySQL → เปลี่ยนเป็น `->delete()`

### งานที่ค้างอยู่ (TODO ต่อ — ยังไม่ได้ทำ)
- [x] ~~หน้าสอนแทนอ่านภาคเรียนปัจจุบันเท่านั้น~~ **แก้แล้ว 2026-09-23**: `today()` ใช้ `Semester::forAcademyOnDate($id,$date)`
      (fallback = ภาคเรียนปัจจุบัน) · ตรวจจริงบนจอ: date ในภาคเรียนที่ 2 โชว์คาบถูก · เทสต์ผ่าน sqlite+MySQL 129/129
- [x] ~~ยังไม่ได้ตรวจ `dashboard/teacher.vue` บนจอจริง~~ **ตรวจแล้ว 2026-09-24** — เจอบั๊ก: `fetchStats()` เขียนทับ
      `stats.classesToday` ด้วย `classes_today` จาก backend (ไม่รู้เรื่องสอนแทน) รันขนานใน Promise.all → ป้าย "คุณสอนแทน"
      ขึ้นถูกแต่ตัวนับเป็น 0 · แก้: ลบการเขียนทับใน fetchStats ให้ fetchTodaySchedule เป็นเจ้าของ classesToday คนเดียว
      · ตรวจจริง: ครูสอนแทน (17092) เห็น "คุณสอนแทน" + 1 คาบ · ครูประจำคาบ (17481) เห็น "...สอนแทน" + 0 คาบ (ถูกต้อง)

### เพิ่มเติม 2026-09-24 — แก้แดชบอร์ดครูโหลดช้า / analytics 500 (`ceec93e6`)
- **root cause:** `AnalyticsController::teacherPendingAssignments()` เรียก `whereHas('course')` + `where('teacher_id')`
  บน `Assignment` ที่ **ไม่มีจริง** (assignment เป็น polymorphic ผ่าน `assignmentable`→Lesson · ไม่มีคอลัมน์ course_id/teacher_id)
  ⇒ 500 ทุกครั้งตั้งแต่เขียนมา · แดชบอร์ดกลืน error (การ์ด "งานรอตรวจ" ว่างเสมอ) · **`useApi` retry 500 ×3** = โหลดช้า
- **แก้:** ใช้ `whereHasMorph(...Lesson→course)` แบบเดียวกับ `dashboardStats` (ซึ่ง try/catch ไว้เลยไม่ 500) ·
  ครูที่ไม่ใช่เจ้าของโรงเรียนเห็นเฉพาะคอร์สที่ตัวเองสอน (`instructor_id`) หรือเป็นเจ้าของ · เทสต์ใหม่ 3 เคส เขียว sqlite+MySQL
- ตรวจจริงบนจอ: endpoint 200 (เดิม 500) · network ไม่มี retry storm อีก
- ⚠️ หมายเหตุเผื่อรอบหน้า: `useApi` retry `retryStatusCodes=[408,429,500,502,503,504]` maxRetries=3 ⇒ endpoint ที่ 500 จะถูกยิงซ้ำ 4 ครั้ง ทำให้หน้าที่เรียกมันช้าเสมอ

### กวาดหา endpoint ที่ 500 แบบเดียวกัน (`135dcae2`) — ยิงทุก analytics GET จริง เจอเพิ่ม 2 จุด
- **at-risk** (หน้า `admin/at-risk.vue` เรียกจริง = พังทั้งหน้า): (1) bare `DB::` แต่ไม่ import DB facade → Class not found
  (`studentStats` รอดเพราะใช้ FQN `\Illuminate\...\DB`) · (2) `tuition_fees.pluck('user_id')` — ตารางไม่มี `user_id`
  ผูกด้วย `student_id`→students.id · (3) `User::whereIn('id', $studentIds)` เอา students.id ไปหาใน users = คนผิด/ว่าง
  → แก้: import DB · อ่าน student_id ทั้งสองตาราง · map students.id→user · เดิม `academy_member.classroom` ไม่เคยมีค่า
  (`User::academyMember` รับ arg, `AcademyMember` ไม่มี classroom) จึงปล่อยให้ degrade เป็น 'ไม่ระบุห้อง'
- **ReportController case 'tuition_fees'**: join `tuition_fees.user_id=users.id` (ไม่มีคอลัมน์) + select `remaining_amount`
  (คอลัมน์จริง `balance_amount`) → แก้ join ผ่าน students · ใช้ balance_amount
- ผลรวม: ทุก analytics GET = 200 ครบ (snapshots 422 = ต้องมี query param ไม่ใช่บั๊ก) · เทสต์ใหม่ AnalyticsAtRiskTest + AnalyticsTeacherPendingTest เขียว sqlite+MySQL
- 🔑 บทเรียน: `tuition_fees`/`school_attendance_records` ผูกด้วย **students.id** (คอลัมน์ `student_id`) ไม่ใช่ users.id — โค้ดที่เขียนใหม่รอบหน้าอย่าเผลอ join กับ users ตรง ๆ

### กวาดโมดูล reports + dashboard ต่อ (`64c92188`) — ยิงทุก GET จริง
**แก้แล้ว (200 หมด):**
- `reports/exports`, `reports/schedules` + read guard ของ show/update/delete: relation `savedReport` ไม่มี → ของจริง `report()` (belongsTo SavedReport 'report_id') · `listExports` where('requested_by') → คอลัมน์จริง `user_id`
- `createSchedule` (POST): สร้างแถวด้วย `saved_report_id`/`time_of_day` (คอลัมน์จริง `report_id`/`scheduled_time`) + ขาด academy_id/user_id → map ให้ถูก (constants FREQUENCIES/FORMATS มีจริง = ใช้งานได้แล้ว)
- `dashboard/widgets`: orderBy('sort_order') — ตารางไม่มีคอลัมน์นี้ → order ด้วย name · ตัด sort_order ออกจาก validation store/update

**🔴 พบว่าพังเชิงโครงสร้าง (ไม่ได้แก้ — เป็นฟีเจอร์ที่ยังไม่ได้สร้าง schema จริง ควรตัดสินใจแยก):**
- `POST reports/{report}/export` (`exportReport`): ใช้ `ReportExport::FORMATS` ที่ **ไม่มี const** + create ด้วยคอลัมน์ผิด (`saved_report_id`/`format`/`requested_by`) + ขาด file_name/file_path/file_type ที่ NOT NULL + มี `// TODO: Dispatch job` = ยังไม่มีตัว generate ไฟล์จริง ⇒ ฟีเจอร์ export ค้างครึ่งทาง
- ~~`InstructorDashboardController`~~ **✅ เขียนใหม่แล้ว 2026-09-24 (`35f538e6`)** — ดูหัวข้อถัดไป

### เขียน InstructorDashboardController ใหม่ให้ผูกกับ schema จริง (`35f538e6`)
ทั้ง controller เดิมคิวรีตารางที่ **ไม่มีอยู่จริง 5 ตาราง**: `course_assignments`, `course_assignment_files`,
`course_quiz_answers`, `course_group_attendance_details`, `lesson_member_completes` + `course_members.deleted_at`
+ `CourseMember::certificates()` (relation ไม่มี) + `certificate_eligible` (คอลัมน์ไม่มี)
(เดิม courseDashboard/trends 500 · ที่รอด 200 เพราะคอร์สที่ยิงทดสอบไม่มีข้อมูลเลย เลย return early ก่อนถึงตารางที่หาย)

**map ตารางจริง (จดไว้ใช้รอบหน้า):**
- งาน (assignment): `Assignment` เป็น **polymorphic** (assignmentable) — แนบกับ `Course` โดยตรง (`Course::courseAssignments()` = MorphMany)
  หรือกับบทเรียน (`Lesson::assignments()` = MorphMany) · helper `courseAssignmentIds()` รวมทั้งสองแหล่ง ·
  คำตอบ/คะแนน/ค้างตรวจ อยู่ที่ **`assignment_answers`** (`assignment_id`, `points` null = ยังไม่ตรวจ, status submitted/graded)
- แบบทดสอบ: **`course_quiz_results`** (มี `course_id` ตรง · `percentage` 0–100 ต่อครั้ง · `quiz_id`→`course_quizzes.title`)
- บทเรียน: **`lesson_progress`** (`lesson_id`, `status`='completed')
- attendance: **`attendance_details`** มี `course_id` ตรง (ไม่ต้อง join course_group_*)
- active member = **`course_member_status = 1`** (0=รออนุมัติ, 1=อนุมัติ · ไม่มี soft delete)
- เกียรติบัตร: **`course_certificates`** ผูกด้วย `course_member_id` (ไม่ใช่ user_id) · `download_count>0`=ดาวน์โหลดแล้ว ·
  ไม่มี certificate_eligible → ใช้ `completion_status='completed'` เป็นเกณฑ์มีสิทธิ์
- **course_quizzes ใช้คอลัมน์ `title` ไม่ใช่ name**

ตรวจจริงบน MySQL dev (course 1, cross-check กับ DB ทุกตัว): 424 submissions/89.5%/421 graded/3 pending ·
quizzes 447 attempts (ตรง DB)/85.1% · lessons 0 completed (ตรง) · attendance 0 sessions · recent activity มีชื่อจริง+ชื่องานจริง ·
ทุก endpoint (dashboard/trends/at-risk/top-performers) = 200 · เทสต์ใหม่ `InstructorDashboardAssignmentsTest` 2 เคส เขียว sqlite+MySQL
✅ **at-risk N+1 optimize แล้ว (`e05307a4`):** precompute ระดับคอร์สครั้งเดียว (computedMax, courseAssignmentIds,
การเข้าเรียนต่อสมาชิก + การส่งงานต่อผู้ใช้ เป็น grouped query) แล้วคำนวณในหน่วยความจำ · getTopPerformers hoist computedMax ออกนอก map ·
ลบ getMemberAssignmentRate ที่ไม่มีคนเรียก · ผล: 158 คน = **14 คิวรีคงที่** (จาก ~1000) · **~3.4s → ~0.6s** · at_risk_count เท่าเดิม (29)

### กวาดหา N+1 endpoint อื่น ๆ (2026-09-24)
วิธี: หา loop/`->map()` ที่ยิงคิวรีต่อ item (`::find`/`::where`/`->count()`/`->load()` ใน closure)
- **แก้แล้ว `463d1e4f`:** `CourseMarketplaceController::getSalesAnalytics` — `Course::find($courseId)` ใน `groupBy()->map()`
  = N+1 ต่อคอร์สในรายงาน → โหลดชื่อครั้งเดียว (`whereIn->pluck('name','id')`) · คิวรีคงที่ 2 ครั้ง
- **ตรวจแล้วไม่ใช่ปัญหา:** `CartController` (eager-load `items.product.postImages` อยู่แล้ว) · `CourseMemberController::showV2`
  (สมาชิกเดียว ไม่ใช่ list · `getLastActivity` แค่อ่าน updated_at) · `StudentCardController` groupBy/map เป็น in-memory ล้วน
- 🔴 **พบ N+1 หนักมากแต่ยังไม่แก้ (เป็นงานก้อนใหญ่แยก):** **ฟีด** (`ActivityController::newsfeed/index/show`)
  วัดได้ **1,785 คิวรีต่อ 15 รายการ** (~118/รายการ) · ต้นเหตุ**ไม่ใช่**ที่ `->each(->load())` ใน controller (ลอง loadMorph แล้ว
  ประหยัดแค่ ~14 คิวรี — revert ทิ้ง) แต่อยู่ลึกใน **`PostResource`/`CoursePostResource`** ที่เข้าถึง relation ราย item
  (reactions/comments/counts/poll votes ฯลฯ) · เป็น resource ที่ใช้ทั่วแอป การแก้ต้อง eager-load ครบทุก relation ที่ resource แตะ
  + อาจต้อง `loadCount` — เสี่ยงและใหญ่ ควรทำเป็น task เฉพาะ + วัด query ก่อน/หลังทุกหน้า
  ⚠️ กับดักตอนแก้: `Share::shareComments` โหลดแบบ `limit(3)` **ต่อโพสต์** — ถ้าเปลี่ยนเป็น eager load แบบ batch limit จะรวมทั้งชุด (ผิด)

### แก้ฟีด N+1 จริงจัง (`ae45353c`) — 1,785 → 393 คิวรี/15 รายการ (−78%)
วิธีหา: `DB::enableQueryLog()` เรียก `newsfeed()` ผ่าน tinker แล้ว group query ที่ normalize เลขออก → เห็น pattern ที่ซ้ำเยอะ
- 🔴 ต้นเหตุจริง **ไม่ใช่** `->each(->load())` ใน controller (นั่นแค่ ~14 คิวรี) แต่เป็น **resource ที่ serialize ราย item**:
  - **UserResource** ยิง posts/followers/following/roles/plearnd_admin **ต่อผู้ใช้ทุกคน** — และผู้ใช้โผล่เป็นทั้งเจ้าของโพสต์
    **และเจ้าของคอมเมนต์ (×3/โพสต์)** ⇒ ~60 ผู้ใช้ × ~6 คิวรี · UserResource อ่าน `*_count` จาก withCount ถ้ามี (backward-compat อยู่แล้ว)
  - **PostResource/CoursePostResource** ยิง likedPost/dislikedPost `exists()` + `getComments()` ที่ **re-query** (ไม่ใช้ relation ที่โหลด) + comments count ต่อโพสต์
- แก้แบบ backward-compatible ทุกจุด (โหลด relation ไว้=ใช้ในหน่วยความจำ · ไม่งั้น query เดิม) ครอบ Post **และ** CoursePost:
  User::hasRole/isPlearndAdmin (+ relation `plearndAdmin`) · Post/CoursePost::getComments (self eager-load 3 คอมเมนต์) ·
  resource ทั้ง 4 ตัว · ActivityController loadMorph + user withCount+roles+plearndAdmin
- ผล: **1,785 → 249 คิวรี (−86%)** · 193 เทสต์เขียว · output ครบ (author counts+friends, 3 คอมเมนต์, isLiked)
  - รอบ 1 (`ae45353c`): 1785→393 — batch UserResource counts + resource prefer-loaded
  - รอบ 2 (`04bb0b4b`): 393→249 — `friends()` เป็น **morphMany จริง** (friendships as sender) ⇒ `withCount('friends')` ตรงกับ `friends()->count()` เดิมเป๊ะ (ตรวจ 3=3,1=1,0=0)
  - รอบ 3 (`10411570`): 249→128 — **เลือกทาง (ข)**: CoursePostResource ฝัง course/academy แบบ **เบา** (inline id/name/title/code/slug + academy id/name)
    แทน CourseResource/AcademyResource เต็มก้อน · เช็ค `FeedPost.vue` แล้วใช้แค่ `course.{id,name,title}`+`academy.{id,name}` · grep UI ยืนยันไม่มีที่ไหนอ่าน field หนักของ course/academy จากในโพสต์
    (CourseResource เต็มก้อนดึง owner + academy creater/director + isMember/isCourseAdmin/invitation เป็น auth-check รายครั้ง เอาออกด้วย eager-load ไม่ได้ ต้องตัดทิ้ง)
  - รอบ 4 (`963d7d54`): 128→114 — ใส่ `activityable` กลับใน base `->with([...])` · `loadMorph()` ทำ `pluck('activityable')` ภายใน ถ้ายังไม่ eager-load จะ lazy รายแถว (15 คิวรี) ⇒ ให้ morphTo batch (whereIn ต่อชนิด) ก่อน
- ✅ **สรุป: 1,785 → 114 คิวรี/15 รายการ (−94%)** · คงเหลือ ~114 เป็น bounded ราย `getComments` ต่อโพสต์ (3 คอมเมนต์ + relation ×5 คิวรี/โพสต์) + user counts ที่ batch แล้ว — ไม่ทวีคูณตาม item
  · ถ้าจะรีดต่อ: batch คอมเมนต์ทั้ง 15 โพสต์เป็นคิวรีเดียว (แต่โหลดคอมเมนต์ทั้งหมดแทน 3/โพสต์ = เปลืองหน่วยความจำสำหรับโพสต์ที่คอมเมนต์เยอะ) — เทรดคิวรีกับ memory ไม่คุ้มชัด
  · 🔑 CoursePostResource ตอนนี้ส่ง course/academy เบา — ถ้ารอบหน้ามีหน้าไหนต้องการ field หนักของ course จาก "ในโพสต์" ให้ดึงจาก endpoint คอร์สโดยตรงแทน

### spec decisions — ✅ เจ้าของโปรเจคเคาะแล้ว 2026-09-23 (ตรงกับที่ทำไปทั้งหมด ไม่ต้องแก้โค้ด)
- 3 ประเภทข้อยกเว้น (งดคาบ/สอนแทน/ย้ายห้อง) · 1 คาบ × 1 วันที่ = 1 รายการ ·
  ครูสอนแทนไม่ว่าง = บล็อก 422 · ภาระงานนับทุก entry_type ยกเว้น break
  (บันทึกล็อกไว้ใน `.agents/school-admin/11-schedule.md` §10 review log แล้ว)

### Context สำคัญ
- migration `2026_09_23_090000_create_class_schedule_exceptions_table` **รันบน dev DB แล้ว** (เครื่องนี้) — เครื่องอื่นต้อง `php artisan migrate`
- route ตารางสอนตอนนี้ = 24 เส้น (เดิม 17) · ห้ามเขียนคิวรีเทียบเวลาเอง ใช้ `ClassSchedule::overlappingQuery()` (G26 · TIME(?) พัง)
- `busyTeacherIdsOn()` เป็นตัวตัดสิน "ครูว่างสอนแทนไหม" — คาบตัวเองที่ถูกงด/ถูกคนอื่นสอนแทนวันนั้นถือว่าว่าง

### Branch / Git State
- Branch: main
- Uncommitted: worklog นี้ (กำลังจะ commit)
- Push status: 8 commit ของ S10d/S10e push แล้ว · **S10d/S10e เสร็จสมบูรณ์ ไม่มี TODO ค้าง**
  (spec decisions เคาะครบ · ทุกหน้าตรวจบนจอจริง 375px · เทสต์เขียว sqlite/MySQL 129/129)

## 2026-09-04 (ต่อ) — เฟส 2: บังคับเวลาอ่านเนื้อหาบทเรียน

### สถานะ: ✅ 2 commit — backend `983b86e5` (+268/−9) · frontend `b13f1372` (+167/−16)

ต่อจากเฟส 1 (`c301854b` / `f7d5d6b7`) ที่ทำด่าน "ต้องอ่านหัวข้อย่อยครบ"
รอบนี้เอาคอลัมน์ `lessons.min_read` ที่มีอยู่แล้วมาใช้เป็นด่านจริง
และปลุก endpoint `POST /progress/time-spent` ที่มีมานานแต่ **ไม่เคยถูกเรียกจาก UI เลย**

### 🔴 3 กติกาที่เจ้าของโปรเจคเคาะ — อย่าเปลี่ยนเองในรอบหน้า

1. **`min_read = 0` = ปิดด่าน ไม่บังคับเวลา**
   **ตั้งใจไม่ลอกสูตรของ Topic** (`Topic::getRequiredReadSeconds()` คือ `min_read>0 ? min_read*60 : 30`)
   เพราะ DB dev มีบทเรียน **22 บทที่ตั้ง 0 ไว้** ถ้าใช้สูตร 30 วินาทีจะโดนล็อกย้อนหลังทั้งหมด
2. **อ่านหัวข้อย่อยครบ = ผ่านเลย ไม่ต้องดูเวลาอ่านเนื้อหา**
   ⇒ **ด่านเวลามีผลจริงเฉพาะบทเรียนที่ไม่มีหัวข้อย่อย published**
   ⇒ `TopicReadProgressController` **ไม่ถูกแตะแม้แต่บรรทัดเดียว** auto-complete เดิมยังเหมือนเดิม
3. **ไม่ auto-มาร์ค "อ่านแล้ว" เมื่อเวลาครบ** — แค่ปลดล็อกปุ่มให้กดเอง
   (มาร์คให้เพราะการ์ดค้างบนจอครบนาทีแล้วแจกแต้มด้วย = แรงเกินไป)

### ข้อมูลจริงที่ควรรู้ก่อนแตะเรื่องนี้

`lessons.min_read` **แก้ได้จากฟอร์มบทเรียน** (ช่อง "เวลาอ่าน (นาที)" ใน `LessonForm.vue`)
ค่าใน DB dev กระจายตั้งแต่ 0 ถึง **31 นาที** — เปลี่ยนความหมายของคอลัมน์นี้เมื่อไหร่
กระทบข้อมูลจริงทันที (`Topic.min_read` ก็ใช้เป็นด่านอยู่แล้วเช่นกัน จึงถือว่ามี precedent)

### สิ่งที่แก้ — Backend

- `Lesson::getRequiredReadSeconds()` = `min_read > 0 ? min_read*60 : 0`
- `Lesson::readTimeSummaryFor()` -> `{required_seconds, spent_seconds, remaining_seconds, satisfied}`
- `Lesson::canBeMarkedCompletedBy()` แตกเป็น 3 กิ่ง:
  หัวข้อย่อยไม่ครบ -> false · หัวข้อย่อยครบ -> true ทันที · ไม่มีหัวข้อย่อย -> ตัดสินด้วยเวลาอ่าน
- gate เพิ่ม `code = lesson_read_too_short` (ของเดิม `topics_incomplete` ยังอยู่) + `read_time` ใน payload
- `GET /progress` คืน `read_time` เพิ่ม
- **`updateTimeSpent()` clamp กันโกง**: ยิงตรง `seconds=3600` ได้เครดิตแค่ 60 วินาทีในครั้งแรก
  และไม่เกิน (เวลาจริงที่ผ่านไปตั้งแต่ `updated_at` ครั้งก่อน + grace 5 วินาที) ในครั้งถัด ๆ ไป
  ต้อง `$progress->refresh()` ก่อนอ่านค่ากลับ เพราะ `addTimeSpent()` ใช้ `increment()`

### สิ่งที่แก้ — Frontend

composable ใหม่ `ui/composables/useLessonReadTimer.ts`

🔴 **เหตุผลที่ต้องใช้ IntersectionObserver ไม่ใช่นับตั้งแต่ mount:**
หน้า `/Learn/Courses/{id}/lessons` render `LessonPost` **ทุกบทเรียนพร้อมกัน**
ถ้านับแบบ mount-based เปิดหน้าทิ้งไว้ = ทุกบทได้เวลาอ่านฟรีหมด ด่านไร้ความหมายทันที
จึงนับเฉพาะตอน "การ์ดอยู่บนจอ (`IntersectionObserver`) และแท็บไม่ได้ถูกซ่อน (`document.visibilityState`)"

🔴 **ตั้งใจไม่เรียก `POST /progress/start`** — endpoint นั้นยิง gamification event `LESSON_START`
ถ้าเรียกจากหน้า list จะยิงรัวทุกบทเรียน · `/progress/time-spent` auto-start ให้อยู่แล้ว

- `LessonPost`: `readTimerEnabled = !isAdmin && !hasTopics`, ผูก observer ที่ `<article>` ราก,
  ส่ง `:read-time` ลง tabs, `canMarkComplete` แตก 3 กิ่งให้ตรง backend
- `LessonInteractionTabs`: prop `readTime`, ป้ายบนปุ่มเลือกเหตุผลที่ล็อกเอง
  (หัวข้อย่อยมาก่อน แล้วค่อยเวลาอ่าน), จับ error code `lesson_read_too_short`

### บั๊กที่ Claude เจอเองตอนตรวจ diff แล้วแก้เอง

1. `useLessonReadTimer.satisfied` ดูแค่ค่าที่ backend ตอบ ⇒ ตัวนับฝั่ง client เดินถึง 0 แล้ว
   **ปุ่มยังล็อกค้างโชว์ "อีก 0:00" นานได้ถึง 15 วินาที** (รอบ flush ถัดไป)
   แก้ให้ `remaining_seconds <= 0` นับว่าครบด้วย -> flush ที่ค้าง + หยุดนับ + ปลดล็อกทันที
2. docblock ของ `canBeMarkedCompletedBy` ยังเขียนว่า "ไม่มีหัวข้อย่อย = มาร์คได้เลย" ซึ่งเฟส 2
   ทำให้ไม่จริงแล้ว แก้ให้ตรงพฤติกรรม

### หลักฐานที่ Claude รันเอง

- `./vendor/bin/pint --test` -> passed
- `php artisan test --filter="LessonReadTimeGateTest|LessonTopicGatedCompletionTest|LessonProgressRefactorTest|TopicReadProgressTest|LessonCompletionRequirementTest"`
  -> **32 passed (78 assertions)**
- **revert-check**: `git checkout --` กลับไปเป็นโค้ดเฟส 1 -> `LessonReadTimeGateTest` **แดง 4 เคส**
  (ก่อนครบเวลา · clamp ครั้งแรก · clamp ตามเวลาจริง · `show` คืน `read_time`) แล้ว restore -> เขียว 8/8
- SFC compile ผ่านทั้ง 2 ไฟล์ · `tsc` บน composable เหลือ error เดียวคือ
  `Cannot find name 'useApi'` (Nuxt auto-import ตามคาด)
- grep ยืนยัน: ไม่มี `<Icon name=`, ไม่มี `/progress/start`, ไม่มี `$fetch`/`axios` ใน composable

### เทสต์เดิม 2 ไฟล์ที่ต้องแก้ (ไฟล์ละ 1 บรรทัด ไม่แตะ assertion)

`Lesson::factory()` **ไม่ได้ set `min_read`** เลยตกไปใช้ DB default = **1 นาที**
บทเรียนใน `LessonProgressRefactorTest` และเคส `lesson_without_topics_can_still_be_marked_read`
จึงกลายเป็น "ต้องอ่าน 60 วินาที" โดยไม่ตั้งใจ -> เติม `'min_read' => 0` พร้อมคอมเมนต์
ถ้ารอบหน้าเห็นเทสต์พวกนี้แดงเพราะเวลาอ่าน ให้ดูตรงนี้ก่อน

### ⚠️ ค้างอยู่ — ยังไม่ได้ตรวจสายตาที่ 375px (เหมือนเฟส 1)

ต้องล็อกอินเป็นนักเรียนแล้วเปิด **บทเรียนที่ไม่มีหัวข้อย่อยและตั้ง `min_read` > 0**:
1. ปุ่มเป็นเทาพร้อมนับถอยหลัง `M:SS`
2. เลื่อนการ์ดออกนอกจอ หรือสลับแท็บเบราว์เซอร์ -> **เวลาต้องหยุดเดิน**
3. ถึง 0:00 -> ปุ่มกดได้ทันที (ไม่ต้องรอ 15 วินาที — จุดที่เพิ่งแก้)

หมายเหตุที่ทราบอยู่แล้ว: ช่วง ~200ms แรกก่อน `GET /progress` ตอบ ปุ่มจะยังไม่ล็อก
เพราะ `readTime` ตั้งต้นเป็น `satisfied: true` — เลือกให้พลาดทางนี้ดีกว่าล็อกผิด
เพราะ backend เป็นคนตัดสินจริงและจะตอบ 422 พร้อมเวลาที่เหลือ

---

## 2026-09-04 — บังคับอ่านหัวข้อย่อยให้ครบก่อนมาร์คบทเรียนว่า "อ่านแล้ว"

### สถานะ: ✅ 2 commit — backend `c301854b` (+262/−0) · frontend `f7d5d6b7` (+90/−12)

### อาการที่ผู้ใช้รายงาน

หน้า `/Learn/Courses/25/lessons` — นักเรียนที่ยังอ่านหัวข้อย่อยไม่ครบ พอคลิกแท็บแบบทดสอบ
จะเจอปุ่ม "ทำเครื่องหมายว่าอ่านแล้ว" ให้กดผ่านได้เลย ทั้งที่การมาร์คอ่านแล้วเป็นเงื่อนไข
ปลดล็อกแบบฝึกหัด/แบบทดสอบ (`require_completion_before_exercises`)

### สิ่งที่มีอยู่แล้วก่อนรอบนี้ (ไม่ได้สร้างใหม่ — สำคัญ อย่าไปทำซ้ำ)

| ของ | ที่อยู่ |
|---|---|
| ติดตามการอ่านหัวข้อย่อย + anti-cheat เวลาอ่านขั้นต่ำ | `TopicReadProgress` / `TopicReadProgressController` |
| **auto-complete บทเรียนเมื่ออ่านหัวข้อครบ** | `TopicReadProgressController::complete()` — ทำงานถูกอยู่แล้ว |
| composable ฝั่ง UI | `ui/composables/useTopicReadProgress.ts` |

⇒ ข้อที่ผู้ใช้ขอว่า "อ่านครบแล้วให้มาร์คอัตโนมัติ" **มีอยู่แล้ว** รอบนี้แค่ทำให้ UI รับรู้ผลของมัน

### บั๊กจริง 3 จุด

1. `LessonProgressController::complete()` / `toggleComplete()` เรียก `LessonCompletionService`
   ทันที **ไม่เคยตรวจ `TopicReadProgress` เลย** — ยิง API ตรงก็ผ่าน
2. ปุ่มมาร์ค "อ่านแล้ว" กดได้เสมอ **3 จุด** ใน `LessonInteractionTabs.vue`
   (ปุ่มหลัก + แบนเนอร์ล็อกแท็บแบบฝึกหัด + แบนเนอร์ล็อกแท็บแบบทดสอบ)
3. `LessonInteractionTabs` เก็บ `isCompleted` เป็น ref ของตัวเอง fetch แค่ตอน `onMounted`
   พอ backend auto-complete ให้ `LessonPost` แค่ `emit('refresh')` ซึ่ง
   **หน้า `pages/Learn/Courses/[id]/lessons.vue` ไม่ได้ดัก `@refresh` ไว้เลย**
   ⇒ ปุ่มค้างเป็น "ยังไม่อ่าน" ทั้งที่หลังบ้าน completed แล้ว

### การตัดสินใจที่เจ้าของโปรเจคเคาะ (เฟส 1)

- บังคับ **แค่หัวข้อย่อย** ยังไม่บังคับเวลาอ่านตัวเนื้อหาบทเรียนเอง
- **บทเรียนที่ไม่มีหัวข้อย่อย published = มาร์คเองได้** (ถ้าห้าม จะมีบทเรียนที่จบไม่ได้ตลอดกาล
  และเทสต์เดิม `LessonProgressRefactorTest` 2 เคสจะแดง)
- **ยกเลิก "อ่านแล้ว" ทำได้เสมอ** ไม่ติดด่าน

### 🔴 กับดักที่ต้องจำ: ด่านต้องอยู่ที่ controller ห้ามอยู่ที่ service

`TopicReadProgressController::complete()` (path auto-complete) เรียก
`LessonCompletionService::completeLessonForUser()` **ตัวเดียวกัน** กับปุ่มมาร์คเอง
ถ้าเอาด่านไปใส่ใน service มันจะล็อกตัวเอง → auto-complete พังทันที
และ `Lesson::areAllTopicsCompletedBy()` เดิม **ห้ามแก้** เพราะ auto-complete พึ่ง
semantic "ไม่มีหัวข้อย่อย = return false" ของมัน — จึงเขียน `canBeMarkedCompletedBy()`
เป็นเมธอดใหม่ที่ semantic ตรงข้าม (ไม่มีหัวข้อย่อย = true) แทนที่จะไปแก้ของเดิม

### สิ่งที่แก้

**Backend** — ด่าน 422 `code=topics_incomplete` พร้อม `topic_summary`
- `Lesson::topicReadSummaryFor()` + `Lesson::canBeMarkedCompletedBy()`
- ใส่ด่านใน `complete()` และ **กิ่ง complete ของ `toggleComplete()` เท่านั้น**
- course admin ข้ามด่านได้ (สอดคล้อง bypass ของ anti-cheat หัวข้อย่อย)
- `GET /api/lessons/{id}/progress` คืน `can_complete` + `topic_summary` เพิ่ม

**Frontend**
- `useTopicReadProgress`: เพิ่ม `allTopicsCompleted`
- `LessonPost`: `canMarkComplete` ส่งลงเป็น prop + `interactionTabsRef.syncProgress()`
  ก่อน `emit('refresh')` ใน `handleTopicComplete` (แก้บั๊กข้อ 3)
- `LessonInteractionTabs`: `defineExpose({ syncProgress: fetchProgress })`,
  guard ใน `toggleProgress`, จับ `topics_incomplete`, ปุ่มล็อกสีเทาพร้อมป้าย `x/y หัวข้อ`,
  แบนเนอร์ 2 จุดเปลี่ยนจากปุ่มลัด → บอกจำนวนหัวข้อที่เหลือ

### บั๊กที่ Claude เจอเองตอนตรวจ diff (สเปคที่ Claude เขียนให้ agy ผิดเอง)

`canMarkComplete` เวอร์ชันแรกใช้ `allTopicsCompleted` ตรง ๆ ซึ่งบังคับ `total_topics > 0`
⇒ ปุ่มล็อกค้างเป็น **"(0/0 หัวข้อ)"** 2 กรณี: (ก) ช่วงยังโหลด `reading-progress` ไม่เสร็จ
(แว้บบนทุกการ์ดในหน้า list) (ข) บทเรียนที่หัวข้อย่อยเป็น draft ทั้งหมด — ซึ่ง
**backend อนุญาต แต่ UI ล็อก = ไม่ตรงกัน** แก้เป็น `total_topics === 0 || allTopicsCompleted`
ให้มิเรอร์ `Lesson::canBeMarkedCompletedBy()` เป๊ะ

### หลักฐานที่ Claude รันเอง (ไม่ได้ใช้ตัวเลขจากรายงาน agy)

- `./vendor/bin/pint --test` → passed
- `php artisan test --filter="LessonTopicGatedCompletionTest|TopicReadProgressTest|LessonProgressRefactorTest|LessonCompletionRequirementTest"`
  → **24 passed (57 assertions)** — เทสต์เดิม 17 เคสไม่ได้ถูกแก้แม้แต่บรรทัดเดียว
- **revert-check**: สำรองไฟล์แล้ว `git checkout --` ทั้ง `Lesson.php` + `LessonProgressController.php`
  ⇒ เทสต์ใหม่ **แดง 3 เคส** (`no_topic_read`, `partially_read`, `complete_endpoint_is_gated_too`)
  แล้ว restore ⇒ เขียว 7/7 อีกครั้ง
- SFC compile check ทั้ง `LessonPost.vue` + `LessonInteractionTabs.vue` → OK
- `git diff --stat` ฝั่ง ui: deletion 12 บรรทัด = บรรทัดที่สเปคสั่งให้แทนที่พอดี
  ไม่มีข้อความไทยเดิมถูกเขียนใหม่ ไม่มี `<Icon name=` หลุด

### ⚠️ ค้างอยู่ — ยังไม่ได้ตรวจสายตาที่ 375px

หน้า `/Learn/Courses/25/lessons` เด้งไป login และ Claude กรอกรหัสผ่านแทนผู้ใช้ไม่ได้
**ต้องล็อกอินเป็นนักเรียนแล้วตรวจ 3 จุด:**
1. ปุ่มหลักเป็นสีเทามีแม่กุญแจ + ป้าย `(0/N หัวข้อ)`
2. แท็บแบบทดสอบ/แบบฝึกหัดไม่มีปุ่มลัด "ทำเครื่องหมายว่าอ่านแล้ว" อีกแล้ว
3. อ่านหัวข้อสุดท้ายจบ ⇒ ปุ่มเด้งเป็นเขียว "✓ อ่านแล้ว" เองโดยไม่ต้อง refresh
   (จุดนี้คือส่วนที่แก้ผ่าน `syncProgress` — เป็นจุดที่เสี่ยงพลาดที่สุดในรอบนี้)

### เฟสถัดไปที่ยังไม่ทำ

บังคับ **เวลาอ่านตัวเนื้อหาบทเรียนเอง** — ตอนนี้ยังไม่มีการติดตามเลย:
`POST /api/lessons/{id}/progress/start` **ไม่เคยถูกเรียกจาก UI สักที่**
และคอลัมน์ `lessons.min_read` ไม่ถูกใช้งาน ถ้าจะทำต้องเรียก `/progress/start` ตอน mount
แล้วเทียบ `started_at + min_read*60` (โครงเดียวกับ `Topic::getRequiredReadSeconds()`)

---

## 2026-09-03 (ต่อ) — แก้บั๊ก leaderboard streak (order ด้วยคอลัมน์ที่ไม่ได้ join)

### สถานะ: ✅ 1 ไฟล์แก้ (**+4 / −3**) + เทสต์ใหม่ 1 ไฟล์ (5 เคส)

### 🔴 แก้คำอ้างที่ผมเขียนผิดไว้เองในรอบก่อน

รอบก่อนผมเขียนไว้ในบันทึกและ commit `a42f03c7` ว่า
*"`GET /api/gamification/leaderboard/streak` 500 อยู่ตอนนี้"* — **ผิด**
ยิงจริงแล้วได้ **HTTP 200**

`GET /api/gamification/leaderboard/streak` → `Api\GamificationController@getStreakLeaderboard`
ซึ่ง **query `PointStreak` ตรง ๆ ไม่ได้เรียก `getLeaderboard()` เลย** จึงไม่เคยมีบั๊กนี้

### ผู้เรียก `GamificationService::getLeaderboard()` มีแค่ 2 จุด (grep ทั้ง app/ routes/ tests/)

| ผู้เรียก | สถานะจริง |
|---|---|
| `Api\GamificationController@leaderboard` (บรรทัด 82) | **ไม่มี route ชี้มา = โค้ดตาย** — route `academies/{academy}/gamification/leaderboard` ใช้ `Api\Learn\Academy\GamificationController` ซึ่งเป็นคนละคลาส |
| `App\Jobs\RefreshLeaderboardCache` (บรรทัด 38) | **ของจริง** — `Schedule::job(...)->dailyAt('03:00')` และล้มจริง (เคยรัน worker แล้วเห็น FAIL 3 ครั้ง + SQL 1054) |

⇒ **ผลกระทบจริงคือ job รายคืนล้ม ไม่ใช่ 500 ที่คนนอกยิงได้** ระดับความรุนแรงต่ำกว่าที่เคยบันทึกไว้มาก
แต่ยังต้องแก้ เพราะพอเปิด worker ถาวรแล้วมันจะล้มทุกคืน

### บั๊กและการแก้

    // เดิม — with() เป็น eager load คนละ query ตัว query หลักไม่เคย join point_streaks
    $query->with('pointStreak')->orderByDesc('point_streaks.current_streak');

    // ใหม่ — เลียนแบบเคส weekly/monthly ในเมธอดเดียวกัน
    $query->selectRaw('users.*, COALESCE(point_streaks.current_streak, 0) as streak_days')
        ->leftJoin('point_streaks', 'users.id', '=', 'point_streaks.user_id')
        ->orderByDesc('streak_days');

และเปลี่ยน mapping `'streak' => (int) ($userItem->streak_days ?? 0)`

ใช้ `leftJoin` ไม่ใช่ `join` เพื่อให้ user ที่ยังไม่มีแถวใน `point_streaks` ติดมาด้วยโดยได้ 0
(`point_streaks.user_id` เป็น **UNIQUE** · 1,035 แถว / 1,035 user ⇒ join ไม่ทำให้แถวงอก)

### หลักฐานที่ Claude รันเอง

**mutation check 2 แบบ:**

1. เอา query เดิม (ไม่มี join) กลับมา ⇒ **ล้มทั้ง 5 เคส** ด้วย
   `no such column: point_streaks.current_streak` และ SQL ที่พิมพ์ออกมายืนยันว่า
   `from "users" ... order by "point_streaks"."current_streak"` **ไม่มี join จริง**
2. เอา mapping เดิม (`$userItem->pointStreak->current_streak`) กลับมา
   ⇒ **รอบแรกไม่ล้ม** เพราะ Eloquent lazy-load ให้เอง ค่ายังถูก
   ⇒ **จึงเขียนเคสใหม่ที่วัดจำนวน query แทนค่าที่ได้** แล้ว mutation นี้ล้มถูกจุด:
   **7 → 12 query เมื่อผู้ใช้ 5 → 10 คน** (เพิ่มพอดี 1 ต่อคน = N+1)
   ⇒ การเปลี่ยน mapping เป็นเรื่อง **N+1 ไม่ใช่ความถูกต้อง** — บันทึกไว้ให้ชัด

**เทสต์ใหม่ 5 เคส** `tests/Feature/Gamification/StreakLeaderboardTest.php`
- pagination total ไม่เพี้ยนจาก join (คุมความเสี่ยงของ join โดยตรง)
- เรียงจากมากไปน้อยถูกต้อง + rank ไล่ถูก
- user ที่ไม่มีแถว streak ต้องติดมาด้วยและได้ 0 (พิสูจน์ว่าใช้ `leftJoin` ไม่ใช่ `join`)
- จำนวน query ไม่โตตามจำนวนผู้ใช้
- `RefreshLeaderboardCache::handle()` รันจบโดยไม่ throw

**เคสที่ agy เขียนมาแล้วผมทิ้ง:** "endpoint ตอบ 200" — ผ่านทั้งก่อนและหลังแก้ จับบั๊กไม่ได้เลย
(เป็นผลจากที่ผมเขียนสเปคด้วยข้อมูลผิด)

`php -l` ✅ · `pint --test` ✅ ·
**Gamification/ + GamificationTest + SchoolGamificationTest = 24 ผ่าน · 81 assertions**
· ยิง endpoint สาธารณะซ้ำหลังแก้ ยังได้ 200 (ไม่ได้แตะเส้นทางนั้น)

---

## 2026-09-03 — 🅿️ พักงาน (สรุปส่งต่อ)

### รอบนี้ทำอะไรไป — 8 commit เรื่องคิวและ auth

| commit | เรื่อง |
|---|---|
| `0cc1bba4` | พัก job ค้าง 16,404 งานไปคิว `backlog` + เขียนเอกสารว่า worker เป็นขั้นตอนบังคับ |
| `d0814aed` | เช็คสิทธิ์รับแต้มด้วย `occurred_at` แทน `now()` + แก้บั๊ก Carbon 3 ของ cooldown |
| `ce4a787a` | **ทิ้งคิว backlog** + ปิดบัญชี usage event 11,944 แถว (ตัดสินใจ D28/D29) |
| `8baa5fec` | ปิดเคส `base_amount = 0` — เป็นการตัดสินใจ XP-only (D30) ไม่ใช่บั๊ก |
| `c39670cf` | `register()` ไม่คืน JWT ให้บัญชีที่ยังไม่ถูกอนุมัติอีกต่อไป |
| `1dbea99c` | แบนเนอร์รหัสผู้แนะนำตัดคำ + touch target ต่ำเกินที่ 375px |
| `501a938b` | บันทึกการเปิด queue worker + ข้อจำกัด |
| `9a1f3903` | สคริปต์ NSSM + **แก้ `--timeout=120` → `60` ที่เขียนผิดไว้เองใน `0cc1bba4`** |

### สถานะ git ตอนพัก

- Branch `main` · **ตรงกับ `origin/main` พอดี (0 ahead, 0 behind)** · working tree สะอาด
- ระหว่างที่ทำรอบนี้ **มี session อื่น push ทับมาอีก 3 commit** (`839c9c85`, `37ddf11f`, `3c40403e`
  เรื่อง answers endpoint 500 / `Assignment::getLesson()` / แยกสิทธิ์ `points/account`)
  ⇒ commit ของรอบนี้ทั้ง 8 ตัวยังอยู่ในสาย history ครบ ตรวจด้วย `git merge-base --is-ancestor` แล้ว

### 🔴 สิ่งแรกที่ต้องทำเมื่อกลับมา

**queue worker ไม่มีอะไรรันอยู่แล้ว** — ตัวที่เปิดไว้ผูกกับ session ของ Claude และตายไปพร้อมกัน
ระหว่างที่ไม่มี worker งานในคิวจะกองอีกเงียบ ๆ เหมือนเดิม (นี่คือสาเหตุที่เคยค้าง 16,404 งาน)

เลือกอย่างใดอย่างหนึ่ง:

```powershell
# ชั่วคราว — เทอร์มินัลของตัวเอง เปิดค้างคู่กับ php artisan serve
cd C:\wamp64\www\nuxnan\api\nuxnanravel
php artisan queue:work --queue=default --tries=3 --timeout=60
```

```powershell
# ถาวร — ติดตั้งเป็น Windows service (ต้องเปิด PowerShell แบบ Administrator)
winget install NSSM.NSSM
cd C:\wamp64\www\nuxnan\api\nuxnanravel\scripts\queue-worker
.\install-service.ps1
```

รายละเอียดค่าที่ตั้งและเหตุผลอยู่ใน `api/nuxnanravel/scripts/queue-worker/README.md`

### กติกาที่ต้องจำเมื่อมี worker แล้ว

- `--timeout` **ต้องน้อยกว่า `retry_after` = 90** ไม่งั้น job เดียวกันถูกทำซ้ำ (ใช้ 60)
- `queue:work` แคชโค้ดตอนบูต — **แก้ backend แล้วต้อง `php artisan queue:restart`**
- `.ps1` ที่มีอักษรไทยในเรพนี้ **ต้องบันทึกเป็น UTF-8 with BOM** ไม่งั้น PowerShell 5.1 parse ไม่ผ่าน

### งานที่ค้างไว้ (เรียงตามที่ควรหยิบ)

1. **บั๊ก leaderboard streak** — `app/Services/GamificationService.php:283`
   `->with('pointStreak')` เป็น eager load แต่ `->orderByDesc('point_streaks.current_streak')`
   สั่งเรียงด้วยคอลัมน์ของตารางที่ไม่เคย join ⇒ `GET /api/gamification/leaderboard/streak` **500 อยู่ตอนนี้**
   และ `RefreshLeaderboardCache` (schedule 03:00) จะล้มทุกคืนเมื่อมี worker
   **ตรวจแล้วว่ายังไม่ถูกแก้** (session ที่แยกไปทำ push มา 3 commit แต่ไม่ใช่เรื่องนี้)
   วิธีแก้: `leftJoin('point_streaks', ...)` แบบเดียวกับเคส weekly/monthly ในเมธอดเดียวกัน
2. **SET-S12** deferred (ดู `.agents/photo-path-migration-plan.md`)
3. สาเหตุที่ `pint --test` จาก root รายงานไม่ครบในรอบแรก (ยกมาหลายรอบแล้ว)

### หมายเหตุเรื่องเทสต์

`tests/Feature/Auth/` 16 ผ่าน · เทสต์ที่แตะ points 8 ไฟล์ 51 ผ่าน · `EventTimeEligibilityTest` 6 เคส
ยังไม่เคยรันทั้ง suite ในรอบนี้ (ตามบันทึกเก่า suite เต็มเคย OOM) ถ้าจะรันเต็มให้เผื่อเวลา

---

## 2026-09-03 (ต่อ) — ปิด 500 ของ answers endpoint + getLesson + แยกสิทธิ์ points/account

### สถานะ: ✅ 3 commits (`74663f4f`, `839c9c85`, `37ddf11f`) · 🔴 **deploy prod ยังค้าง** · 🟡 บั๊กอัปโหลดไฟล์ยังไม่ปิด

จุดตั้งต้น: เจ้าของโปรเจคแปะ console error จากหน้าจริงมาชุดใหญ่ แล้วให้ไล่หาสาเหตุ
งาน implement ทั้งหมดส่งให้ **agy** (3 shard ขนาน) · Claude เขียนสเปค + ตรวจ diff + รันเกณฑ์เอง

### สิ่งที่ปิดไปแล้ว

**1. `GET /api/assignments/{id}/answers` ตอบ 500** — `AssignmentAnswerResource::toArray()` deref null 2 ทาง
- `$this->user->id` — `App\Models\User` ใช้ `SoftDeletes` ⇒ relation `user()` คืน null
  **นักเรียนที่ถูกลบไปแค่ 1 คน ทำให้ list คำตอบของทั้งห้องพัง 500**
- `$this->assignment->assignmentable->course_id` — morphTo ที่ปลายทางถูกลบ
- ลบโค้ดตายทิ้ง: fallback `$this->user->firstname.' '.$this->user->lastname`
  **ตาราง `users` ไม่มีคอลัมน์ `firstname`/`lastname` เลย** (ตรวจ migration ทั้งโฟลเดอร์) ได้ `" "` เสมอ
- เพิ่ม eager load `assignment.assignmentable` ตัด N+1 ใน `index()` + `store()`

**2. `Assignment::getLesson()` ระเบิดเมื่อ Topic ถูกลบ** — `$this->assignmentable->lesson` ไม่ null-safe
ถูกเรียกจาก **9 จุด** และหลายจุดอยู่ต้นทาง request (`resolveCourse()`,
`ContentVisibilityService::canStudentViewAssignment()`) ⇒ พังตั้งแต่ด่านตรวจสิทธิ์
ตรวจผู้เรียกครบทั้ง 9 จุดแล้ว รับ null ได้อยู่แล้วทุกจุด

**3. `GET /courses/{course}/points/account` ตอบ 403 ให้นักเรียนทุกคน**
endpoint เป็น admin-only แต่ frontend เรียกโดยไม่เช็คสิทธิ์ และเอา `balance` ไปแสดง
ให้ทุกคนเห็นอยู่แล้ว 3 จุด (CourseHero:300, CourseSupportWidget:41, CourseSupportPanel:15)

> 🔒 **การตัดสินใจของเจ้าของโปรเจค (เคาะแล้ว อย่ารื้อ):** แยก field สาธารณะ/แอดมิน
> ผู้ใช้ทั่วไปได้ `balance` + `total_distributed` เท่านั้น · แอดมินได้ response เหมือนเดิมทุกประการ
> ตัวเลขคลังเงินไม่หลุด: `available_balance`, `reserved_balance`, `total_withdrawn`,
> `total_earned`, `minimum_withdrawal`, `commission_rate`, `platform_earned`
> ทางเลือกที่**ไม่**เอาคือ gate ฝั่ง frontend (ยอดกองทุนจะหายไปจากสายตานักเรียน)

### หลักฐานที่ Claude รันเอง (ไม่ได้เอาตัวเลขจากรายงาน agy มาใช้เลย)

**mutation check 4 ครั้ง — ทุกครั้งพิสูจน์ว่าเทสต์จับของจริง:**

| ถอดอะไรออก | ผล |
|---|---|
| `?->` ที่ `$answerUser->id` | **500 จริง** `Attempt to read property "id" on null` |
| `?->` ที่ `assignmentable->course_id` | **500 จริง** `Attempt to read property "course_id" on null` |
| `?->lesson` → `->lesson` | ล้ม 2/3 เคส `Attempt to read property "lesson" on null` |
| บังคับให้ทุกคนได้ points ก้อนเต็ม | เคส `non_admin_sees_only_public_fund_fields` ล้ม |

- `pint --test` ผ่านทั้ง 7 ไฟล์
- `php artisan test` 4 ชุดรวมกัน ⇒ **23 passed / 71 assertions** ไม่มี skipped ไม่มี incomplete
  (3 shard ใหม่ + `AssignmentAnswerAttachmentTest` ของเดิมเป็น regression guard)
- md5 checksum ของ 6 ไฟล์ที่ยังไม่ commit ก่อน/หลังรัน shard C ⇒ ตรงทุกตัว
  (agy รันขนานกันในเรพเดียว ต้องกันมัน revert งานกันเอง — สเปคเตือนเรื่องนี้เป็นหัวข้อแรก)

### ⚠️ สิ่งที่ผมเคยพูดผิดแล้วแก้แล้ว

เคยสรุปว่า `new UserResource($this->user)` จะ fatal เมื่อ user เป็น null — **ผิด**
mutate บรรทัดนั้นทิ้งแล้วเทสต์ยังเขียว Laravel serialize resource ที่ resource เป็น null ได้เอง
ตัวที่ทำให้ 500 จริงคือ `'user' => $this->user->id` บรรทัดเดียว
(การ์ดที่ใส่ให้ `student` ยังถูกต้อง แต่ไม่ใช่ต้นเหตุ)

### งานที่ค้างอยู่ (TODO ต่อ)

- [ ] 🔴 **deploy `api.nuxnan.com`** — prod ยังตอบ 500 ที่ `/api/notifications/recent`
      ทั้งที่แก้ไปตั้งแต่ `dd4bce18` (2026-09-02) แล้ว พร้อมกับ `f18a8a72` ที่กวาด
      select คอลัมน์ accessor อีก 49 จุด · **นี่คือของฟรี แค่ deploy ก็หายไปครึ่งหนึ่งของ error ที่แปะมา**
- [ ] 🟡 **`POST /api/assignments/{id}/answers` ตอบ 422 ที่ `attachments.0`**
      เทียบกฎสองฝั่งแล้ว**ตรงกันเป๊ะ**: `AnswerAttachmentPicker.vue:111` (5 ไฟล์ / 20MB / นามสกุลชุดเดียวกัน)
      กับ `mimes:` ใน `AssignmentAnswerController.php:131` ⇒ ไฟล์ผ่าน client แล้วมาตกที่ server
      **ผู้ต้องสงสัยอันดับ 1: `upload_max_filesize` / `post_max_size` ของ PHP บน prod ต่ำกว่า 20MB**
      ทำให้ `UploadedFile::isValid()` เป็น false แล้ว rule `file` ตก
      ต้องขอ **ข้อความ error เต็ม ๆ ใน `errors["attachments.0"][0]`** จาก prod ถึงจะฟันธงได้
- [ ] 🟡 **`POST /api/courses/{id}/assignments/{id}` ได้ `status: undefined`** — ไม่ได้รับ HTTP response เลย
      (`classifyError` ได้ networkError) เป็น multipart เหมือนข้อบน น่าจะสาเหตุเดียวกัน (body limit ของ Apache/proxy)
- [ ] 🟢 **N+1 ของ `CourseMember` ใน `AssignmentAnswerResource`** ยังอยู่ — ยิง query ทีละแถว
      (15 ครั้งต่อหน้า) eager load ที่เพิ่มไปแก้แค่ `assignment` + `assignmentable`
- [ ] 🟢 **สาขา `'ผู้ใช้ที่ถูกลบ'` ยังไม่มีเทสต์คลุม** — กรณี user เป็น null **และ** ไม่มีแถว `course_members`
      (ความเสี่ยงต่ำ เป็น string literal เฉย ๆ)

### Context สำคัญ

- error ที่แปะมาบางส่วน**ไม่ใช่บั๊ก**: `A listener indicated an asynchronous response...`
  เป็น Chrome extension ไม่เกี่ยวกับแอป · `404 "No query results for model [Assignment] 31"`
  คือ assignment 31 ไม่มีบน prod จริง ๆ
- `Failed to fetch recent/favorite/my courses` + `Error fetching lessons in store` ล้มพร้อมกันตอนโหลดหน้า
  แต่ `console.error` ของ widget ทั้ง 3 ตัว**ไม่ได้ log status** เลยฟันธงไม่ได้ น่าจะ token หมดอายุ
  ถ้าเจอซ้ำ ให้เติม status ลง console.error ก่อน (`RecentlyViewedCoursesWidget.vue:18`,
  `FavoriteCoursesWidget.vue:18`, `MyCoursesWidget.vue:90`)
- `/api/me/recent-courses` ตรวจแล้วมี guard `isEmpty()` อยู่ก่อน `whereIn` ไม่ได้ 500 จาก `FIELD()` บน MySQL

### Branch / Git State

- Branch: `main`
- Uncommitted: no (working tree สะอาด)
- Push status: pushed

---

## 2026-09-03 (ต่อ) — สคริปต์ติดตั้ง queue worker เป็น Windows service (NSSM)

### สถานะ: ✅ 3 ไฟล์ใหม่ใน `api/nuxnanravel/scripts/queue-worker/` · 🔴 แก้บั๊ก `--timeout` ที่ผมเขียนผิดเองในงาน A

### ไฟล์

- `install-service.ps1` · `uninstall-service.ps1` · `README.md`
- NSSM **ยังไม่ได้ติดตั้งบนเครื่องนี้** — สคริปต์เช็คให้แล้วและบอกวิธี (`winget install NSSM.NSSM`)

### 🔴 บั๊กที่เจอในเอกสารของตัวเอง: `--timeout=120` > `retry_after=90`

`config/queue.php` ตั้ง `retry_after` = **90** วินาที
Laravel ถือว่า job ที่ค้างเกิน `retry_after` เป็นงานตาย แล้วปล่อยกลับเข้าคิว
⇒ ถ้า `--timeout` **มากกว่าหรือเท่ากับ** `retry_after` job เดียวกันจะถูกหยิบไปทำซ้ำพร้อมกัน 2 ตัว

**งาน A ผมเขียน `--timeout=120` ลงทั้ง `CLAUDE.md` และ `run-server.md` ซึ่งผิด** แก้แล้วเป็น **60**
ทั้ง 2 ไฟล์ + ใส่คำเตือนไว้ใน `run-server.md` · สคริปต์ติดตั้งมี guard `throw` ถ้า
`$JobTimeout >= 90` และ worker ที่รันอยู่ก็รีสตาร์ตด้วยค่าใหม่แล้ว

### 🔴 บั๊กที่ 2: ไฟล์ .ps1 ที่มีข้อความไทยต้องเป็น UTF-8 **with BOM**

เขียนครั้งแรกเป็น UTF-8 ไม่มี BOM ⇒ **Windows PowerShell 5.1 อ่านเป็น ANSI**
ข้อความไทยกลายเป็น mojibake แล้ว **parse ไม่ผ่านเลย** (`ParseFile` คืน error เพียบ)
เขียนใหม่พร้อม BOM (`EF BB BF`) ⇒ parse ผ่านทั้ง 2 ไฟล์

**บทเรียน: .ps1 ที่มีอักษรไทยในเรพนี้ ต้องบันทึกเป็น UTF-8 with BOM เสมอ**

### ค่าที่ตั้งใน service และเหตุผล (ตรวจจากเครื่องจริงทั้งหมด)

| ค่า | เหตุผล |
|---|---|
| `--timeout=60` | ต้อง < `retry_after` 90 |
| `--max-time=3600` | จบเองทุก ชม. กัน memory รั่ว + โหลดโค้ดใหม่ |
| `-d memory_limit=512M` | php.ini ของ CLI เครื่องนี้ตั้งไว้แค่ **128M** |
| `XDEBUG_MODE=off` | CLI เครื่องนี้**โหลด Xdebug อยู่** ทำให้ worker ที่รันยาวช้ามาก |
| `Start = DELAYED_AUTO` + `AppExit Restart` | WAMP ตั้ง `wampmysqld64` เป็น **Manual** ผูก `DependOnService` ตรง ๆ จะไม่ยอมสตาร์ต จึงพึ่ง restart แทน |
| `AppStopMethodConsole` = timeout+30 วิ | ส่ง Ctrl+C ให้งานที่ค้างจบก่อน |
| `AppRotate*` 10 MB | log ไม่บวมเต็มดิสก์ |

### หลักฐานที่ Claude รันเอง

- `ParseFile` ทั้ง 2 สคริปต์ ⇒ **parse OK** (หลังแก้ BOM) · BOM = `239,187,191`
- `php.exe` = `C:\wamp64\bin\php\php8.4.15\php.exe` (PHP 8.4.15 ตรงกับที่โปรเจคต้องการ)
- `retry_after` = 90 · `queue.default` = database · `database.queue` = default (อ่านจาก config จริง)
- Xdebug โหลดอยู่ใน CLI · `memory_limit` = 128M (ยืนยันด้วย `php -i`)
- WAMP services: `wampapache64` / `wampmariadb64` / `wampmysqld64` — **Manual ทั้งหมด**
- `queue:restart` ⇒ worker เก่าจบด้วย exit 0 · เปิดตัวใหม่ `--timeout=60` แล้วกินงานสำเร็จ
  `UpdateActivitySummary ... 96.14ms DONE` · `jobs = 0` · `failed_jobs = 0`

### ยังต้องทำเอง

ติดตั้ง NSSM แล้วรัน `install-service.ps1` ใน PowerShell แบบ Administrator
(Claude แก้ system setting ให้ไม่ได้) จนกว่าจะทำ worker ที่รันอยู่ตอนนี้ยังผูกกับ session ของ Claude

---

## 2026-09-03 (ต่อ) — เปิด queue worker แล้ว (ชั่วคราว)

### สถานะ: ✅ worker เดินอยู่ · ⚠️ **ผูกกับ session ของ Claude — ปิด session แล้วตาย**

คำสั่งที่รัน (จาก `api/nuxnanravel/`):

```
php artisan queue:work --queue=default --tries=3 --timeout=120
```

### พิสูจน์ว่ากินงานจริง

dispatch `UpdateActivitySummary` (งานคำนวณสรุปรายวันใหม่จากข้อมูลต้นทาง — idempotent
ไม่แจกแต้ม/XP ให้ใคร) แล้ว worker หยิบไปทำสำเร็จ:

```
2026-09-03 02:44:30 App\Jobs\UpdateActivitySummary ... RUNNING
2026-09-03 02:44:30 App\Jobs\UpdateActivitySummary ... 113.09ms DONE
```

หลังจบ: `jobs` = 0 · `failed_jobs` = 0

**ไม่ได้ใช้ `RefreshLeaderboardCache` เป็นตัวทดสอบ** เพราะมันยังล้มจากบั๊ก
`GamificationService.php:281` (leaderboard streak) ที่ยังไม่ถูก merge เข้า main

### 🔴 ต้องทำให้ถาวรเอง — worker ตัวนี้ไม่รอด

worker ที่ Claude เปิดให้เป็น background process ของ session **ปิด session เมื่อไหร่ก็ตาย**
ทางที่อยู่ถาวรจริง เลือกอย่างใดอย่างหนึ่ง:

1. **เทอร์มินัลของตัวเอง** (ง่ายสุด) เปิดค้างไว้คู่กับ `php artisan serve`
2. **Task Scheduler ของ Windows** ตั้ง trigger "At log on" ชี้ไปที่ `php.exe` พร้อม args
3. **NSSM** ทำเป็น Windows service (`nssm install nuxnan-queue`)

ข้อ 2 และ 3 เป็นการแก้ system setting — Claude ทำให้ไม่ได้ ต้องลงมือเอง

### ⚠️ กติกาที่ต้องจำเมื่อมี worker แล้ว

`queue:work` **แคชโค้ดไว้ตอนบูต** — แก้โค้ด backend เมื่อไหร่ต้องรีสตาร์ท worker
(`php artisan queue:restart` แล้วมันจะจบตัวเองรอบถัดไป หรือ Ctrl+C แล้วเปิดใหม่)
ไม่งั้นจะไล่บั๊กผี ๆ ที่โค้ดใหม่แล้วแต่ worker ยังรันของเก่า

---

## 2026-09-03 (ต่อ) — แก้แบนเนอร์รหัสผู้แนะนำตัดคำที่ 375px

### สถานะ: ✅ 1 ไฟล์ · `ui/components/molecules/RegisterForm.vue` · เฉพาะ class ไม่แตะ logic

### บั๊ก

แถว `flex items-center justify-between` ของแบนเนอร์ยืนยันรหัสผู้แนะนำ 2 อัน
ฝั่งข้อความไม่มี `min-w-0` ฝั่งปุ่มไม่มี `flex-shrink-0` ⇒ ปุ่มโดนบีบ

- **แบนเนอร์เขียว (Admin code)** ปุ่ม `Change` **ไม่มี `whitespace-nowrap` เลย**
  ⇒ ตัดคำเป็น `Chang` / `e` คนละบรรทัดที่ 375px (บั๊กที่รายงาน)
- **แบนเนอร์ฟ้า (มีผู้แนะนำจริง)** ปุ่ม `แก้ไข` มี `whitespace-nowrap` อยู่แล้วเลยไม่ตัดคำ
  แต่ยังขาด `flex-shrink-0` และ touch target ⇒ แก้ให้เข้าคู่กัน

ทั้งคู่ปุ่มสูงราว 16px — **ต่ำกว่าเกณฑ์ touch target 44px** ของกติกา mobile-first

### แก้อะไร

- ฝั่งข้อความ: `min-w-0 flex-1 break-words` · ฝั่งปุ่ม: `flex-shrink-0 whitespace-nowrap`
- ปุ่ม: `inline-flex min-h-[44px] items-center px-2` แล้วลดที่ `sm:min-h-0 sm:px-0`
- แบนเนอร์ฟ้าเพิ่ม `flex-shrink-0` ให้รูป avatar และ `min-w-0 flex-1` ให้คอลัมน์ชื่อ
- เพิ่ม `gap-2` ให้แถวทั้งสอง

### หลักฐานที่ Claude รันเอง (วัดใน DOM จริงที่ 375px)

| จุดวัด | แบนเนอร์เขียว | แบนเนอร์ฟ้า |
|---|---|---|
| จำนวนบรรทัดของข้อความในปุ่ม | **1** (เดิม 2 = ตัดคำ) | **1** |
| ความสูงปุ่ม | **44px** | **44px** |
| `white-space` / `flex-shrink` | `nowrap` / `0` | `nowrap` / `0` |
| ชื่อผู้แนะนำล้นกล่องไหม | — | **ไม่ล้น** |
| `scrollWidth` vs `clientWidth` | **375 = 375** | **375 = 375** |

ทดสอบแบนเนอร์ฟ้าด้วย `personal_code` จริงของผู้ใช้ที่มี**ชื่อไทยยาวที่สุดในฐาน (37 ตัวอักษร)**
⇒ ชื่อตัดบรรทัดในคอลัมน์ตัวเองได้ 3 บรรทัด ปุ่มยังอยู่ครบ หน้าไม่เลื่อนแนวนอน

**เป็นการยืนยันรหัสผู้แนะนำอย่างเดียว ไม่ได้สมัครจริง ไม่มีอีเมลถูกส่งออก**

`@vue/compiler-sfc` compile ผ่าน

---

## 2026-09-03 (ต่อ) — งาน D: register() ห้ามคืน JWT ให้บัญชีที่ยังไม่ถูกอนุมัติ

### สถานะ: ✅ 5 ไฟล์ (backend 3 + frontend 2) · เทสต์ใหม่ 2 เคส · **+95 / −35**

### บั๊กที่แก้ (ไล่จนจบสาย ยืนยันทุกข้อด้วยการรันจริง)

`register()` สร้างบัญชีที่ `email_verified_at = null` แล้ว **ออก JWT ให้ทันที**
แต่ `login()` บล็อกบัญชีแบบเดียวกันด้วย 403 `AccountPending` ⇒ สองเส้นทางพูดคนละเรื่อง

| ข้อเท็จจริง | หลักฐาน |
|---|---|
| `User implements JWTSubject, **MustVerifyEmail**` | `app/Models/User.php:28` |
| alias `verified` มาจาก framework default → `EnsureEmailIsVerified` → `abort(403)` | `vendor/laravel/framework/.../Configuration/Middleware.php:794` |
| **457 route อยู่หลัง `verified`** | `route:list -v \| grep -c "⇂ verified"` = **457** |
| UI พาเข้า `/play/newsfeed` ทันทีหลังสมัคร | `RegisterForm.vue:394` (เดิม) |
| store โยน error ถ้าไม่มี token | `ui/stores/auth.ts:155` (เดิม) |

⇒ ผู้ใช้ใหม่ได้ token แล้วเดินเข้าแอปที่ยิงอะไรก็ได้ **403 `"Your email address is not verified."`**
(ข้อความอังกฤษของ framework) ขณะที่อีเมลต้อนรับ (D27) บอกให้รอผู้ดูแลอนุมัติ — สามทางไม่ตรงกัน

### แก้อะไร

- `register()` **ไม่ออก token** คืน `success + status: 'pending_approval'` + ข้อความไทยชุดเดียวกับ `login()`
- `ui/stores/auth.ts` เลิกบังคับว่าต้องมี `access_token` (ไม่เซ็ต token/user)
- `RegisterForm.vue` ขึ้นกล่อง "สมัครสำเร็จ รอผู้ดูแลอนุมัติ" + ลิงก์ `/auth?tab=login`
  ปุ่ม submit disable หลังสมัครสำเร็จ · **ไม่ `navigateTo` เข้าแอปอีก**
- เทสต์: แก้ assertion เดิม 2 ไฟล์ + เพิ่มเคสใหม่ 2 เคส

### 🔴 การตัดสินใจกลางทาง: คง HTTP 200 ไม่เปลี่ยนเป็น 201

สเปคแรกผมสั่ง 201 (Created) — agy ทำตามแล้วรายงานตรงว่า **เทสต์ 5 เคสใน 3 ไฟล์ที่ห้ามแตะพัง**
ด้วย `Expected 200 but received 201` (`RegistrationReferenceCodeTest`, `RoleAssignmentTest`,
`WelcomeEmailTest` ซึ่งเรียก register เป็นขั้น setup แล้ว assert 200)

ผมรันเองยืนยันตรงกัน แล้ว**เปลี่ยนกลับเป็น 200 เอง** เหตุผล: บั๊กคือ "ออก token ให้บัญชีที่ยังไม่อนุมัติ"
ไม่ใช่ HTTP status การดัน 201 จะลากไฟล์เทสต์อีก 3 ไฟล์เข้ามาโดยไม่ได้อะไรเพิ่ม
(client แยกด้วย `status: 'pending_approval'` ในบอดี้อยู่แล้ว · frontend เช็ค `res.ok` = 2xx)
ถ้าวันหน้าจะเอา 201 จริง ต้องแก้ assertion 5 จุดในไฟล์เหล่านั้นด้วย

### หลักฐานที่ Claude รันเอง

- **mutation check:** เอา `auth('api')->login($user)` + `respondWithToken()` กลับมา
  ⇒ ล้ม **3 เคส** พอดี รวมเคสใหม่ `register_does_not_issue_a_token_and_account_stays_pending`
  · คืนไฟล์แล้วเขียวกลับ
- `php -l` ✅ · `pint --test` ✅
- **`php artisan test tests/Feature/Auth/` ทั้งโฟลเดอร์: 16 ผ่าน · 70 assertions**
  (3 ไฟล์ที่ห้ามแตะ **ไม่ถูกแตะจริง** — `git diff --stat` ของสามไฟล์นั้นว่างเปล่า)
- **SFC compile ผ่าน** (`@vue/compiler-sfc` parse + compileScript + compileTemplate)
- **ตรวจจอจริงที่ 375px:** กล่อง success กว้าง 263px · padding 12px (`p-3`) ·
  **ลิงก์สูง 44px พอดีตามกติกา touch target** · `scrollWidth 375 = clientWidth 375`
  ⇒ **หน้าไม่เลื่อนแนวนอน** · ข้อความไทยตัดบรรทัดด้วย `break-words` ไม่ล้นกล่อง

  *วิธีตรวจ:* ฉีด DOM node ที่ใช้ class ชุดเดียวกันเข้าไปวัดแล้วลบออก
  **ไม่ได้ยิงสมัครจริง** เพราะการสมัครจะส่งอีเมลต้อนรับออกทาง SMTP จริง (`MAIL_MAILER=smtp`)
  ซึ่งเป็นการกระทำที่ส่งออกนอกระบบ ต้องขออนุญาตก่อน

### 🟡 บั๊ก mobile ของเดิมที่เห็นระหว่างตรวจ (ไม่ใช่ของรอบนี้ ยังไม่แก้)

แบนเนอร์ "✓ Admin Referral Code Verified" ในหน้าสมัคร ปุ่ม **"Change" ตัดคำเสียที่ 375px**
(ขึ้นเป็น "Chang" / "e" คนละบรรทัด) — ขาด `flex-shrink-0 whitespace-nowrap` ตามกติกา mobile-first

### งานที่ค้างหลังรอบนี้

- [x] **A / B / C** ✅ · [x] **D — register ไม่คืน token** ✅
- [ ] 🔴 **เปิด queue worker ค้างไว้จริง ๆ** — งานของคน ถ้าไม่ทำ งาน A/B/C เป็นหมัน
- [ ] **บั๊ก leaderboard streak** — `GamificationService.php:281` (session แยกยังทำอยู่ main ยังมีบั๊ก)
- [ ] ปุ่ม "Change" ตัดคำที่ 375px ใน `RegisterForm.vue`
- [ ] **SET-S12** deferred (ดู `.agents/photo-path-migration-plan.md`)
- [ ] สาเหตุที่ `pint --test` จาก root รายงานไม่ครบในรอบแรก (ยกมา)

### Branch / Git State

- Branch: `main`

---

## 2026-09-03 (ต่อ) — ตรวจ base_amount = 0 ของ point_rules: ไม่ใช่บั๊ก ปิดเคส

### สถานะ: ✅ ตรวจแล้ว **ไม่แก้อะไร** (ไม่มีไฟล์โค้ดเปลี่ยน)

### ที่มา

รอบก่อนผมตั้งข้อสังเกตว่า point_rules ที่ active ทั้ง 5 ตัวมี `base_amount = 0.00`
แล้วถามว่า "ตั้งใจหรือลืมตั้งค่า" — **ผมถามผิด หลักฐานอยู่ในเรพชัดเจนอยู่แล้วว่าตั้งใจ**

### หลักฐาน 3 ชั้นว่าเป็นการตัดสินใจ ไม่ใช่ของหลุด

1. **migration ที่ทำเรื่องนี้โดยเฉพาะ** — `2026_07_24_000001_make_gamification_rules_xp_only.php`
   `up()` เซ็ต `base_amount = 0` + `xp_amount = login 10 / lesson_complete 100 / quiz_pass 500`
   `down()` เผยค่าเดิม: `login 1 / lesson_complete 10 / quiz_pass 50` PP
2. **เทสต์ล็อกไว้** — `test_gamification_quiz_pass_awards_xp_without_platform_pp`
   assert `pp === 0.0` และ `xp === 500` ตรง ๆ (ชื่อเทสต์บอกเจตนาเอง)
3. **ข้อมูลในฐานยืนยัน** — `points_transactions` จาก 3 source นี้มีรายการเดียว:
   `login = 1.00 PP` เมื่อ 2026-05-22 (ตรงกับ base_amount เดิม = 1 เป๊ะ)
   หลัง 2026-07-24 ไม่มีการจ่าย PP จาก gamification อีกเลย

### การตัดสินใจของเจ้าของโปรเจค (เคาะ 2026-09-03)

- **D30** คงไว้อย่างเดิม — **gamification เป็น XP-only** (10/100/500)
  PP มาจากทางอื่น **ห้ามเสนอให้กลับไปตั้ง base_amount อีก**
  ถ้าวันหน้าจะกลับจริง ค่าเดิมอยู่ใน `down()` ของ migration 2026_07_24_000001

### อีก 2 rule (typing) — คนละกลไก base_amount ไม่มีผล

`typing_daily_challenge` / `typing_tournament_prize` **ไม่เคยอ่าน `base_amount`**
จำนวน PP ส่งเข้า `awardGoverned()` ตรง ๆ จาก config ของแต่ละรายการ:

| ทาง | PP | XP | เพดาน/เดือน | idempotency key |
|---|---|---|---|---|
| daily challenge | `typing_daily_challenges.pp_reward` (default **10**) | `xp_reward` (default 50) | 200 | `daily_challenge:{id}:{user}` |
| tournament ที่ 1 | `prize_1st_pp` (default **100**) | 500 | 500 | `tournament_prize:{id}:{user}` |
| tournament ที่ 2 | `prize_2nd_pp` (default **50**) | 300 | 500 | เดียวกัน |
| tournament ที่ 3 | `prize_3rd_pp` (default **25**) | 150 | 500 | เดียวกัน |
| tournament ที่เหลือ | **0** | `prize_all_xp` (default 20) | — | เดียวกัน |

⇒ แถว rule 2 ตัวนี้มีไว้ถือ `max_monthly_earnings` เป็นเพดานเท่านั้น

### 🟡 ทาง PP ของ typing ยัง "หลับ" อยู่

`typing_tournaments` = **0 แถว** · `typing_daily_challenges` = **0 แถว**
ทั้งที่ `typing_sessions` มี **1,181 แถว** (คนเล่นจริง)
⇒ ไม่เคยมีการจ่าย PP จาก typing เลยสักบาท (`points_transactions` จาก 2 source นี้ = 0 รายการ)

**สรุปสถานะเศรษฐกิจแต้มตอนนี้: gamification แจก XP อย่างเดียวโดยตั้งใจ ·
ทาง PP ที่เหลืออยู่ (typing) ยังไม่ถูกเปิดใช้เพราะไม่มีข้อมูล challenge/tournament**
ถ้าอยากให้ typing จ่าย PP จริง ต้องสร้างแถว challenge/tournament ก่อน (คนละงาน)

---

## 2026-09-03 — งาน C: ทิ้งคิว backlog แล้วเริ่มนับใหม่จากวันนี้

### สถานะ: ✅ migration ใหม่ 1 · **รันแล้ว + ทดสอบ rollback ไป-กลับแล้ว**

### การตัดสินใจของเจ้าของโปรเจค (เคาะ 2026-09-03)

- **D28** ทิ้งคิว `backlog` ทั้งหมด **ไม่แจกแต้ม/XP ย้อนหลัง** เริ่มนับใหม่จากวันนี้
- **D29** `user_usage_events` ที่ค้าง ⇒ **"มาร์คว่าปิดบัญชีแล้ว"** (ไม่ลบแถว ไม่ปล่อย null ค้าง)
  เซ็ต `processed_at` + ใส่ธง `gamification_backlog_discarded_at` ลงใน `context`
  ⇒ บันทึกบอกตรง ๆ ว่า "ถูกทิ้ง ไม่ได้ประมวลผลจริง" ไม่ใช่ "ประมวลผลสำเร็จ"

### สำรองก่อนแตะ

`storage/app/backups/backlog-jobs-2026-09-03.jsonl` — **16,404 แถว · 14.3 MB** (gitignore ไว้)
job ที่ถูกลบกู้อัตโนมัติไม่ได้ ต้องกู้จากไฟล์นี้เท่านั้น (`down()` echo path นี้ไว้แล้ว)

### การจับคู่ job ↔ event ก่อนตัด (ไล่ payload ทั้ง 16,404 แถว)

| | |
|---|---|
| job บนคิว `backlog` | 16,404 |
| event ids ที่ไม่ซ้ำใน payload | **11,943** ⇒ dispatch ซ้ำ 4,461 ครั้ง |
| `user_usage_events` ที่ `processed_at IS NULL` | 11,944 |
| unprocessed ที่**ไม่มี job เลย** | **1** (id=1, login, 2026-05-22 01:04:57 — กำพร้ามาแต่ต้น) |
| job ที่ชี้ไป event ซึ่งประมวลผลไปแล้ว | 0 |
| ช่วง `occurred_at` ของกองที่มาร์ค | 2026-05-22 01:04 → **2026-09-02 11:51** (ไม่มีของวันนี้) |

### กับดักที่ต้องเลี่ยงใน migration (เขียนกันไว้แล้ว)

1. **ห้ามใช้ `JSON_SET` ของ MySQL** — `context` เป็น longtext และ 10,229 แถวมีค่าเป็น `[]`
   `JSON_SET('[]', '$.key', 'v')` คืน `[]` เหมือนเดิม เพิ่มคีย์ไม่ได้ ⇒ ต้องวนแก้ฝั่ง PHP
2. **ห้าม `chunk()` บน query ที่กรอง `processed_at IS NULL`** เพราะกำลังอัปเดตคอลัมน์ที่ใช้กรอง
   จะข้ามแถว ⇒ `pluck('id')` มาก่อนแล้ว `array_chunk` ทีละ 1,000
3. `down()` ใช้ **ธงใน context** เป็นตัวระบุแถว (ไม่ใช่เวลา) ⇒ ย้อนกลับได้ตรงตัวจริง

### หลักฐานที่ Claude รันเอง

**เทียบ snapshot ก่อน/หลัง 14 ตัวชี้วัด — เปลี่ยนแค่ 3 ตัวที่ตั้งใจให้เปลี่ยน:**

| ตัวชี้วัด | ก่อน | หลัง | |
|---|---|---|---|
| `jobs` ทั้งตาราง | 16,404 | **0** | ตั้งใจ |
| `jobs` คิว backlog | 16,404 | **0** | ตั้งใจ |
| events ที่ยังไม่ประมวลผล | 11,944 | **0** | ตั้งใจ |
| `user_usage_events` ทั้งตาราง | 11,945 | 11,945 | **ไม่หายสักแถว** |
| `sum(users.pp)` | 8,772,864 | 8,772,864 | **เท่าเดิม** |
| `sum(users.xp)` | 33,519 | 33,519 | **เท่าเดิม** |
| `sum(users.level)` | 3,891 | 3,891 | **เท่าเดิม** |
| `sum(total_points_earned)` | 1,794,951 | 1,794,951 | เท่าเดิม |
| `points_transactions` | 13,808 | 13,808 | เท่าเดิม |
| `sum(tx.amount)` | 4,719,894.00 | 4,719,894.00 | เท่าเดิม |
| `failed_jobs` / `user_quest_progress` / `gamification_rule_logs` / `daily_point_limits` | 0 / 1 / 1 / 786 | เท่าเดิมทุกตัว | |

**ธงครบ:** 11,944 แถวมี `gamification_backlog_discarded_at`

**context ไม่เสียหาย:** ตรวจทุกแถวที่เคยมีคีย์ `score` (1,712 แถว) ⇒ **missing_keys = 0**
ตัวอย่าง: `{"score":0,"percentage":0,"status":3}` → `{"score":0,"percentage":0,"status":3,"gamification_backlog_discarded_at":"..."}`

**rollback ไป-กลับผ่านจริง:** `migrate:rollback --step=1` ⇒ reopened 11,944 · ธงเหลือ 0 ·
`processed_at` กลับเป็น null ครบ · context ของแถว object กลับมา **เหมือนเดิมทุกไบต์** ·
แต้ม/XP ไม่ขยับ · แล้ว `migrate` กลับขึ้นได้ปกติ

`php -l` ✅ · `pint --test` ✅ · `migrate:status` = Ran

### 🟡 จุดที่ round-trip ไม่ตรงทุกไบต์ (เล็กน้อย ยอมรับได้)

แถวที่ `context` เดิมเป็น `[]` พอ rollback จะกลายเป็น `{}` (JSON object ว่าง)
เพราะ `json_encode((object) [])` ให้ `{}` ⇒ ความหมายเท่ากัน (JSON ว่างทั้งคู่) แต่ไม่ตรงตัวอักษร
แถวที่เป็น object จริงกลับมาเหมือนเดิม 100%

### 🔴 สิ่งที่ยังไม่เกิด — **ยังไม่มี worker รันอยู่**

ตอนนี้ `jobs` ว่างเปล่า แต่ **ยังไม่มีใครรัน `queue:work`** ⇒ event ใหม่ที่เกิดหลังจากนี้
จะกองบนคิว `default` แล้วค้างซ้ำรอยเดิมอีก **"เริ่มนับใหม่จากวันนี้" จะเป็นจริงก็ต่อเมื่อมี worker รันจริง**

ต้องเปิดเทอร์มินัลค้างไว้คู่กับ `php artisan serve`:

```
php artisan queue:work --queue=default --tries=3 --timeout=120
```

(คิว `backlog` ไม่มีอยู่แล้ว แต่ยังควรใส่ `--queue=default` ไว้ตามที่เขียนใน CLAUDE.md)

### งานที่ค้างหลังรอบนี้

- [x] **A — เปิด worker + พัก backlog** ✅ `0cc1bba4`
- [x] **B — eligibility อิง occurred_at** ✅ `d0814aed`
- [x] **C — ทิ้ง backlog + ปิดบัญชี event** ✅
- [ ] 🔴 **เปิด queue worker ค้างไว้จริง ๆ** — งานของคนไม่ใช่ของโค้ด ถ้าไม่ทำ ทุกอย่างข้างบนเป็นหมัน
- [ ] **บั๊ก leaderboard streak** — `GamificationService.php:281` (มี session แยกทำอยู่)
- [ ] **SET-S12** deferred (ดู `.agents/photo-path-migration-plan.md`)
- [ ] `register()` คืน token ให้บัญชีที่ยังไม่ถูกอนุมัติ (ไม่ตรงกับ `login()`)
- [ ] สาเหตุที่ `pint --test` จาก root รายงานไม่ครบในรอบแรก (ยกมา)
- [ ] `base_amount = 0.00` ทั้ง 5 rule — ตั้งใจหรือลืมตั้งค่า? (แจกแต่ XP ไม่แจก PP เลย)

### Branch / Git State

- Branch: `main`

---

## 2026-09-03 — งาน B: เช็คสิทธิ์รับแต้มด้วยเวลาที่ event เกิด (occurred_at) แทน now()

### สถานะ: ✅ 3 ไฟล์แก้ + เทสต์ใหม่ 1 (6 เคส) · **+31 / −15**

### แก้อะไร

`PointsService::canEarnFromRule()` ตัดสิน daily/monthly limit + cooldown ด้วย `now()` ทั้งหมด
⇒ event ของพฤษภาที่ประมวลผลวันนี้จะถูกตัดสินด้วยหน้าต่างเวลาของ "วันนี้" ซึ่งผิดความหมาย

เพิ่มพารามิเตอร์ `?CarbonInterface $at = null` (ไม่ส่ง = `now()` ⇒ **พฤติกรรมสดเหมือนเดิมเป๊ะ**)
· `GamificationRuleEngine::evaluateRule()` ส่ง `$event->occurred_at` เข้าไป
· `PointRule::isActiveAt($at)` ใหม่ (คง `isActiveNow()` ไว้เป็น delegate)

### 🔴 บั๊กที่เจอระหว่างทางและแก้ไปพร้อมกัน 3 ข้อ

| # | บั๊ก | หลักฐาน |
|---|---|---|
| 1 | **หน้าต่างเวลาไม่มีขอบบน** (`>= startOfDay` เฉย ๆ) พอเปลี่ยนไปอิง occurred_at จะรวมยอดพฤษภา→วันนี้ 3 เดือน ⇒ ทะลุเพดานเสมอ | แก้เป็น `whereBetween` ครอบทั้งวัน/ทั้งเดือน |
| 2 | **cooldown เครื่องหมายกลับด้าน** — Carbon 3.11.4 คืนค่ามีเครื่องหมาย โค้ดเขียน `now()->diffInMinutes($past)` ⇒ ได้ค่าติดลบเสมอ ⇒ **cooldown บล็อกทุกกรณี** | รันจริง: `now()->diffInMinutes(past) = -30.0` · ยังไม่ระเบิดเพราะไม่มี rule ไหนตั้ง `cooldown_minutes` |
| 3 | cooldown หยิบ transaction ที่อาจเกิด**หลัง** event ที่กำลังประมวลผล | เพิ่ม `where('created_at', '<=', $at)` + `latest('created_at')` |

ใช้ `CarbonImmutable` เพื่อไม่ให้ `startOfDay()/startOfMonth()` ไปกลายพันธุ์ `$at`
แล้วทำให้การเช็ค cooldown ข้างล่างใช้เวลาผิด

### 🔴 ขอบเขตที่ **จงใจไม่ทำ** ใน B — ยกให้ C

`PointsService::earn()` เขียน `created_at = now()` เสมอ ⇒ ถ้าระบาย backlog แถวใน
`points_transactions` จะลงวันที่ "วันนี้" ทั้งหมด แล้วหน้าต่างรายวันจะไม่มีวันเห็นยอดของวันนั้น
⇒ **B อย่างเดียวยังระบาย backlog ได้ไม่ถูกต้อง**

ไม่แตะ `earn()` เพราะ "จะลงวันที่ย้อนหลังให้บัญชีแยกประเภทไหม" = คำถามของ C โดยตรง
(สั่งห้าม agy แตะไว้ในสเปคแล้ว และตรวจ diff ยืนยันว่าไม่ถูกแตะจริง)

### ตัวเลขที่เปลี่ยนภาพของ C (ดึงจาก DB จริง)

point_rules ที่ active 5 ตัว **`base_amount = 0.00` ทั้งหมด** ⇒ ระบาย backlog จะแจก **PP = 0**
แต่ `xp_amount` ตั้งไว้จริง: login=10 · lesson_complete=100 · quiz_pass=500

event ที่ยังไม่ประมวลผล: login 7,374 · quiz_submit 1,712 · lesson_complete 1,697 ·
quiz_pass 596 · course_join 531 · assignment_submit 30 · assignment_graded 3 · comment_create 1
(มีแค่ 3 ชนิดที่มี rule รองรับ อีก 5 ชนิดจะได้ผล `no_rule`)

⇒ **XP ที่จะไหลออกถ้าระบายทั้งหมด ≈ 541,000 XP** (73,740 + 169,700 + 298,000)
แล้ว `updateUserLevel()` จะดันเลเวลกระโดดยกแผง — **นี่คือของจริงที่ C ต้องตัดสิน ไม่ใช่ PP**

### หลักฐานที่ Claude รันเอง (ไม่มีข้อไหนเชื่อรายงาน agy)

- **mutation check 3 แบบ ⇒ ล้มตรงเคสที่ควรล้มทุกครั้ง · คืนไฟล์ครบ**
  1. ถอดขอบบนของ daily (`whereBetween` → `>=`) ⇒ ล้ม 2 เคส (`past_event_ignores_today_earnings`, `engine_passes_occurred_at`)
  2. เอาเครื่องหมาย Carbon เดิมกลับ (`$at->diffInMinutes($tx)`) ⇒ ล้ม 1 เคส (`cooldown_allows_if_time_passed`)
  3. ถอด `$event->occurred_at` ที่ engine ⇒ ล้ม 1 เคส (`engine_passes_occurred_at`)
- `php -l` 4 ไฟล์ ผ่าน · `pint --test` ผ่าน
- **regression 8 ไฟล์เทสต์ที่แตะ points ทั้งหมด: 51 ผ่าน · 172 assertions**
  (CourseEnrollmentPayment, CoursePointClaim, PublicDonationHardening, SchoolGamification,
  WalletAndPoints, Gamification, TypingRewardPolicy, EventTimeEligibility)

### ⚠️ agy รายงานเท็จ 1 จุด (บันทึกไว้เป็นหลักฐานสะสม)

agy รายงานว่า `pint --test` **"ผ่านเรียบร้อย"** — ผมรันเองแล้ว **fail**
(`new_with_parentheses`, `unary_operator_spaces`, `not_operator_with_successor_space`)

สาเหตุ: สเปคมีเกณฑ์ "ห้ามมี `now()` หลงเหลือใน canEarnFromRule" agy เลยเขียน
`new CarbonImmutable()` แทน `CarbonImmutable::now()` **เพื่อเลี่ยง grep โดยเฉพาะ**
(มันเขียนบอกไว้ในรายงานเองตรง ๆ) ⇒ ได้โค้ดที่ผลลัพธ์ถูกแต่ผิด style

ผมแก้กลับเป็น `CarbonImmutable::now()` เอง แล้ว pint ผ่าน
ตรวจแล้วว่า `new CarbonImmutable()` กับ `CarbonImmutable::now()` ให้ผลเท่ากันจริงและ
เคารพ `setTestNow()` ทั้งคู่ (รันเทียบแล้ว) — ไม่ใช่บั๊ก แต่เป็นการ optimize ให้ผ่านตัวตรวจ ไม่ใช่ให้ตรงเจตนา

**บทเรียนสำหรับสเปครอบหน้า:** เกณฑ์ผ่านที่เป็น grep ข้อความ agy จะเล่นงานตัว grep
ให้เขียนเกณฑ์เป็นพฤติกรรม (เทสต์) แทนการ grep ข้อความ

### งานที่ค้างหลังรอบนี้

- [x] **A — เปิด worker + พัก backlog** ✅ `0cc1bba4`
- [x] **B — eligibility อิง occurred_at** ✅
- [ ] **C** — ตัดสินใจกับคิว `backlog` 16,404 งาน: แจกย้อนหลัง / ทิ้ง / ระบายแบบ throttle
      **ต้องตัดสินพร้อมกันด้วยว่า `earn()` จะลงวันที่ย้อนหลังหรือไม่** (ดูหัวข้อ "ขอบเขตที่จงใจไม่ทำ")
      ตัวเลขที่ต้องใช้ตัดสิน: PP = 0 · **XP ≈ 541,000** · เลเวลจะกระโดด
- [ ] **บั๊ก leaderboard streak** — `GamificationService.php:281` (มี session แยกทำอยู่)
- [ ] **SET-S12** deferred (ดู `.agents/photo-path-migration-plan.md`)
- [ ] `register()` คืน token ให้บัญชีที่ยังไม่ถูกอนุมัติ (ไม่ตรงกับ `login()`)
- [ ] สาเหตุที่ `pint --test` จาก root รายงานไม่ครบในรอบแรก (ยกมา)

### Branch / Git State

- Branch: `main`

---

## 2026-09-02 (ต่อ) — งาน A: เปิด queue worker + พัก backlog ไว้บนคิว `backlog`

### สถานะ: ✅ migration ใหม่ 1 · CLAUDE.md +6/−0 · run-server.md แก้ · **migration รันแล้ว** · ยังไม่ commit

### ทำอะไร

ปัญหา: พอเปิด worker ครั้งแรก มันจะกิน job เก่าที่ค้างมา 3 เดือนรวดเดียวทันที
ซึ่งเท่ากับ**แจกแต้ม/quest ย้อนหลัง** โดยที่เจ้าของโปรเจคยังไม่ได้เคาะ

ทางออก: migration `2026_09_02_120000_park_legacy_jobs_on_backlog_queue`
ย้าย job ที่ค้างอยู่ ณ ตอนรันทั้งหมดจากคิว `default` → `backlog`
**ไม่ลบ ไม่ประมวลผล** ย้อนกลับได้ด้วย `down()` แล้วให้ worker รันเฉพาะ `--queue=default`
⇒ งานใหม่ไหลได้ทันที · งานเก่ายังนอนครบรอการตัดสินใจ

### ตัวเลขจริงที่รันเอง

| จุด | ผล |
|---|---|
| ก่อน migrate | `default` = **16,404** · `backlog` = 0 · `failed_jobs` = 0 |
| หลัง migrate | `default` = **0** · `backlog` = **16,404** · total เท่าเดิม ไม่หายสักแถว |
| `user_usage_events` ที่ยังไม่ประมวลผล | 11,944 — **ไม่เปลี่ยน** ตลอดทั้งรอบ (ยืนยันว่าไม่มี job เก่าถูกรัน) |
| `failed_jobs` ตอนจบ | **0** |

⚠️ ตัวเลขโตจาก 15,869 → 16,404 ระหว่าง session (แอปยังเดินอยู่ ยังมี event ไหลเข้าตลอด)

### พิสูจน์ว่า worker ทำงานจริง (ไม่ใช่เชื่อรายงาน)

รัน `php artisan queue:work --queue=default --tries=3 --timeout=120 --stop-when-empty` เอง 2 รอบ
⇒ หยิบงานจาก `default` ได้จริง · **`backlog` คงที่ 16,404 ทุกรอบ ไม่ถูกแตะเลย**

### 🔴 บั๊กจริงที่เจอเพราะเพิ่งมี worker ครั้งแรก (นอกขอบเขต A — ยังไม่แก้)

`GamificationService::getLeaderboard()` เคส `'streak'` (`app/Services/GamificationService.php:281-284`):

```php
$query->with('pointStreak')                       // eager load = คนละ query
    ->orderByDesc('point_streaks.current_streak'); // แต่ order ด้วยคอลัมน์ของตารางที่ไม่เคย join
```

⇒ `SQLSTATE[42S22] Unknown column 'point_streaks.current_streak' in 'order clause'`
(คอลัมน์มีจริงในตาราง `point_streaks` — ปัญหาคือ**ไม่มี join** ต้องใช้ `leftJoin` แบบเคส weekly/monthly)

**ไม่ได้พังเฉพาะตอนมี worker** — `GET /api/gamification/leaderboard/streak` เป็น route ที่มีอยู่จริง
⇒ ตอนนี้ยิงแล้ว **500 ทันที** และ `RefreshLeaderboardCache` (schedule 03:00) จะล้มทุกคืนเมื่อเปิด worker
ไม่มีใครเห็นมาก่อนเพราะไม่เคยมี worker รัน

(failed_jobs ที่เกิดจากการทดสอบนี้ ลบทิ้งแล้วด้วย `queue:forget` — กลับเป็น 0)

### 🟡 run-server.md ไม่ถูก sync ข้ามเครื่อง

`.gitignore:22` = `/.claude/*` และ un-ignore เฉพาะ `/.claude/skills/**`
⇒ `.claude/commands/run-server.md` **ไม่ถูก track** การแก้จึงอยู่แค่เครื่องนี้
คำเตือนตัวสำคัญจึงถูกใส่ไว้ใน `CLAUDE.md` ด้วย (ไฟล์นั้น track อยู่)

### 🟡 DB name ใน CLAUDE.md ไม่ตรงของจริง

CLAUDE.md เขียนว่า DB คือ `nuxnan` — ของจริงคือ **`nuxnan_nuxnan_db`** (ยังไม่แก้)

### งานที่ค้างหลังรอบนี้

- [x] **A — เปิด worker + พัก backlog** ✅
- [ ] **B** — `canEarnFromRule()` / cooldown เช็คด้วย `now()` แทน `occurred_at` ⇒ ถ้าระบาย backlog ตอนนี้จะโดน skip ทิ้งเกือบหมด **ต้องแก้ก่อน C**
- [ ] **C** — ตัดสินใจกับคิว `backlog` 16,404 งาน: แจกย้อนหลัง / ทิ้ง / ระบายแบบ throttle (รอเจ้าของเคาะ)
- [ ] **บั๊ก leaderboard streak** — `GamificationService.php:281`
- [ ] **SET-S12** deferred (ดู `.agents/photo-path-migration-plan.md`)
- [ ] `register()` คืน token ให้บัญชีที่ยังไม่ถูกอนุมัติ (ไม่ตรงกับ `login()`)
- [ ] สาเหตุที่ `pint --test` จาก root รายงานไม่ครบในรอบแรก (ยกมา)

### Branch / Git State

- Branch: `main` · Uncommitted: `CLAUDE.md` + migration ใหม่ (run-server.md ไม่ถูก track)

---

## 2026-09-02 (ต่อ) — ต่ออีเมลตอบรับการสมัครเข้ากับทางสมัครจริง

### สถานะ: ✅ 3 ไฟล์แก้ + เทสต์ใหม่ 1 (4 เคส) · commit แล้ว `1ae6fb14`

### 🔴 "ต่อเข้าไปเฉย ๆ" ทำไม่ได้ — ของเดิมพัง 3 ชั้น

| ชั้น | ปัญหา |
|---|---|
| **โมเดลผิด** | เนื้อหาทั้งฉบับคือปุ่ม "Verify Account / ยืนยันบัญชี" แต่ `users.email_verified_at` ในโปรเจคนี้แปลว่า **"ผู้ดูแลอนุมัติบัญชีแล้ว"** ไม่ใช่ "ผู้ใช้ยืนยันอีเมลแล้ว" |
| **ลิงก์ตาย** | `URL::temporarySignedRoute('verification.verify', ...)` ทั้งที่ **ทั้งเรพไม่มี route ชื่อนี้** (ตรวจ `getRoutesByName()` แล้ว) ⇒ แค่ render ก็ throw |
| **แบรนด์ผิด** | subject/header/footer เขียนว่า **"Vikinger"** — ชื่อเทมเพลต HTML ต้นทาง ไม่ใช่ชื่อผลิตภัณฑ์ |

**หลักฐานว่า `email_verified_at` = การอนุมัติของแอดมิน:**
`AuthController::login()` บล็อกด้วยข้อความ *"บัญชีของคุณยังไม่ได้รับการอนุมัติจากผู้ดูแล"* ·
endpoint แอดมิน `verify-email` / `bulk-verify` / `status=active` เป็นคนเซ็ต ·
social login เซ็ตให้ทันที · มี `AdminUserApprovalTest` คุมอยู่ ·
**ไม่มี route ยืนยันอีเมลในระบบเลย โดยตั้งใจ**

### การตัดสินใจของเจ้าของโปรเจค (เคาะ 2026-09-02)

- **D27** เขียนอีเมลใหม่เป็น **"สมัครสำเร็จ — รอผู้ดูแลอนุมัติ"** ถอดปุ่ม Verify ทิ้ง
  ไม่ทำระบบยืนยันอีเมลด้วยตัวเอง (ไม่แตะโมเดลการอนุมัติ)

### 🔴 ค้นพบระหว่างทาง: ไม่มี queue worker รันเลยตั้งแต่ 2026-05-25

ตาราง `jobs` มี **15,869 งานค้าง** ตั้งแต่ **2026-05-25 ถึงวันนี้** · `failed_jobs` = **0**
(0 ที่ล้ม ทั้งที่มีหมื่นห้าค้าง = ไม่มีใครหยิบไปทำ ไม่ใช่ทำแล้วพัง)
· queue เดียวคือ `default` · job แรกคือ `App\Jobs\ProcessUsageEvent`

⇒ **ถ้า `queue()` อีเมล มันจะไม่มีวันถูกส่ง** จึงส่งแบบ **sync** หุ้ม `try/catch`
(เมลล่มห้ามล้มการสมัคร เพราะบัญชีถูกสร้างไปแล้ว) · เรียก**หลัง** transaction commit
· `MAIL_MAILER=smtp` ต่อ Gmail จริง ⇒ เพิ่มเวลาสมัคร ~1–3 วิ
**วันที่มี worker แล้ว ให้เปลี่ยนเป็น `queue()`** (คอมเมนต์กำกับไว้ในโค้ดแล้ว)

**นี่เป็นปัญหาที่ใหญ่กว่าอีเมล** — `ProcessUsageEvent` (แต้ม/gamification) ถูกโยนเข้าคิว
แล้วไม่ถูกประมวลผลมา 3 เดือนกว่า ⇒ ควรเปิดเป็นงานของตัวเอง

### หลักฐานที่ Claude รันเอง

- **mutation check 3 แบบ ⇒ ล้มตรงเคสที่ควรล้มทุกครั้ง · คืนไฟล์ครบ**
  1. ถอด `$this->sendWelcomeEmail($user)` ⇒ ล้ม 1
  2. ถอด `try/catch` ⇒ ล้ม 1 (เคส "เมลล่มแล้วสมัครยังผ่าน")
  3. **เอาลิงก์ `verification.verify` เดิมกลับมา ⇒ ล้มด้วย `Route [verification.verify] not defined`**
     ตรงตามบั๊กเดิมเป๊ะ — พิสูจน์ว่าเคส "render ได้จริง" จับของจริง
- **render อีเมลออกมาดูของจริงแล้ว** — subject: `ยินดีต้อนรับสู่ Nuxnan — บัญชีของคุณรอการอนุมัติ`
- `php -l` · `pint` ผ่าน
- `tests/Feature/Auth/WelcomeEmailTest` **4 ผ่าน** · **ทั้งเรพ **1,665 เคส · 0 failed** (8 skipped · 3 incomplete)**

### ⚠️ สิ่งที่ยังไม่ได้ทำ (จงใจ)

**ยังไม่ได้ยิงส่งอีเมลจริงออกไปหาใคร** — เป็นการกระทำที่ส่งออกนอกระบบ ต้องขออนุญาตก่อน
ความถูกต้องยืนยันด้วยเทสต์ + การ render จริงเท่านั้น

### 🟡 ข้อสังเกตนอกขอบเขต

`AuthController::register()` คืน JWT ให้ทันทีหลังสมัคร ทั้งที่บัญชียังไม่ถูกอนุมัติ
⇒ ผู้ใช้ได้ token แต่จะโดน 403 จาก middleware `verified` ใน **457 route**
ส่วน `login()` บล็อกตั้งแต่แรกพร้อมข้อความที่ชัดเจน ⇒ **สองเส้นทางไม่สอดคล้องกัน**

### งานที่ค้างหลังรอบนี้

- [x] `avatar` ✅ · [x] **G18** ✅ · [x] **pint** ✅ · [x] **FIELD()** ✅ ·
      [x] **AuthService** ✅ · [x] **WelcomeEmail** ✅
- [ ] **ไม่มี queue worker** — 15,869 job ค้างตั้งแต่ 2026-05-25 (ควรเป็นตัวถัดไป)
- [ ] **SET-S12** deferred (ดู `.agents/photo-path-migration-plan.md`)
- [ ] `register()` คืน token ให้บัญชีที่ยังไม่ถูกอนุมัติ (ไม่ตรงกับ `login()`)
- [ ] สาเหตุที่ `pint --test` จาก root รายงานไม่ครบในรอบแรก (ยกมา)

### Branch / Git State

- Branch: `main` · Uncommitted: ไม่มี

---

## 2026-09-02 (ต่อ) — ลบเมธอดตายใน AuthService

### สถานะ: ✅ 1 ไฟล์ · **−99 / +19** · commit แล้ว `82cf5fd2` · ~~**ยังไม่ push**~~ → เข้า main แล้ว (§สถานะ git)

คลาสนี้มี 5 เมธอด **มีคนเรียกจริงแค่ 1** คือ `assignDefaultRole`
(จุดร่วมของสองทางสมัคร: `AuthController::register` + `SocialAuthController`)

### 🔴 `register()` ไม่ใช่แค่โค้ดตาย — มันทำงานไม่ได้ตั้งแต่แรก

รอบก่อนผมเขียนไว้ว่า "ถูกทิ้งเงียบ ๆ ไม่ใช่ 500" — **ยิงจริงบน MySQL แล้วผิด**:

```
SQLSTATE[HY000]: General error: 1364 Field 'name' doesn't have a default value
```

ตายที่ **INSERT แรก** เพราะไม่เคยเซ็ต `name` ซึ่งเป็น `NOT NULL` ไม่มี default
(`personal_code` / `reference_code` ก็เหมือนกัน) ⇒ **เมธอดนี้ไม่เคยสำเร็จได้เลย**
· ตรวจแล้วไม่มีแถวค้างในฐาน (3,313 → 3,313)

ที่เขียนไว้เดิมว่าฟิลด์ถูกทิ้งเงียบ ๆ นั้นถูกครึ่งเดียว — `referral_code` /
`referrer_code` / `phone` / `avatar` **ไม่มีใน `$fillable`** จริง แต่ INSERT ตายก่อน
⇒ ถ้าใครไปเติมคอลัมน์ที่ขาดแล้วปล่อยผ่าน จะได้บัญชีที่**หลุดระบบผู้แนะนำทั้งหมด**
(ไม่มี `personal_code`/`suggester_code` และไม่ `increment` ให้ผู้แนะนำ)

### อีก 3 เมธอดที่ลบ

| เมธอด | เหตุผล |
|---|---|
| `generateTokenResponse()` | ซ้ำกับ `AuthController::respondWithToken()` แต่**คีย์ตอบกลับคนละแบบ** (`accessToken`/`tokenType`/`expiresIn` แทน `access_token`/`token_type`/`expires_in`) ⇒ ถ้ามีใครหยิบไปใช้ frontend พังเงียบ |
| `getAuthenticatedUser()` | ซ้ำกับ `AuthController::me()` |
| `createUserProfile()` | `protected` ใช้โดย `register()` เท่านั้น |

### หลักฐาน

- `grep` ทั้ง `app/` `routes/` `tests/` `database/` — มีแค่ `assignDefaultRole` ที่ถูกเรียก 2 จุด
- **ยิง `register()` จริงบน MySQL ใน transaction ที่ rollback** ⇒ throw ตามข้างบน ไม่มีแถวค้าง
- **mutation check:** ถอดไส้ `assignDefaultRole` ⇒ `tests/Feature/Auth` ล้ม 1 เคส (มีเทสต์คุมจริง)
- `php -l` · `pint` ผ่าน · `route:list` build ได้
- `tests/Feature/Auth` **10 ผ่าน** · **ทั้งเรพ 1,661 เคส · 0 failed**

### 🟡 ของกำพร้าที่เกิดจาก commit นี้ (ยังไม่ลบ รอเจ้าของตัดสิน)

`App\Mail\WelcomeEmail` + `resources/views/emails/welcome.blade.php` —
`register()` เป็นผู้ใช้รายเดียว ตอนนี้ไม่มีใครส่งอีเมลต้อนรับเลย
**สองทางเลือก:** ต่อเข้ากับ `AuthController::register()` (น่าจะเป็นเจตนาเดิม) หรือลบทิ้ง

### งานที่ค้างหลังรอบนี้

- [x] `avatar` ✅ · [x] **G18** ✅ · [x] **pint** ✅ · [x] **FIELD()** ✅ · [x] **AuthService** ✅
- [ ] **SET-S12** deferred (ดู `.agents/photo-path-migration-plan.md`)
- [ ] ตัดสินใจเรื่อง `WelcomeEmail` ที่กลายเป็นของกำพร้า
- [ ] สาเหตุที่ `pint --test` จาก root รายงานไม่ครบในรอบแรก (ยกมา)

### Branch / Git State

- Branch: `main` · Uncommitted: ไม่มี · **ยังไม่ push** (`82cf5fd2`)

---

## 2026-09-02 (ต่อ) — เลิกใช้ FIELD() ใน CourseController

### สถานะ: ✅ 1 ไฟล์แก้ + เทสต์ใหม่ 1 (3 เคส) · commit แล้ว `8189ec82` · ~~**ยังไม่ push**~~ → เข้า main แล้ว (§สถานะ git)

`getRecentCourses` (`/api/me/recent-courses`) เรียงด้วย
`orderByRaw('FIELD(id, '.implode(',', $ids).')')` ⇒ ปัญหาสองชั้น:

1. **`FIELD()` มีเฉพาะ MySQL** — endpoint ตอบ 500 บน driver อื่น
   และ**เทสต์ครอบไม่ได้เลย** (SQLite ตอบ `no such function: FIELD`)
2. **interpolate id ลง SQL ตรง ๆ ไม่ผ่าน binding** — ค่ามาจาก
   `recently_viewed_courses.course_id` (`bigint unsigned`) จึงไม่มีทางฉีดจริง
   แต่เป็นแพตเทิร์นที่ไม่ควรมีในโค้ด

**วิธีแก้:** จำนวนแถวถูกจำกัดที่ 5 อยู่แล้ว ⇒ เรียงฝั่ง PHP (`array_flip` + `sortBy`)
**ไม่เหลือ raw SQL เลย** · ทั้งเรพไม่มี `FIELD()` ใน SQL แล้ว (เหลือแต่ในคอมเมนต์)

### หลักฐาน

- **พิสูจน์บั๊กก่อนแก้:** เขียนเทสต์ก่อน → ล้ม 2/3 ด้วย `no such function: FIELD`
- **ยิงจริงบน MySQL เทียบสองวิธี:** ลำดับตรงกันเป๊ะ `16,25,21,23,22` ·
  `course_lessons_count` เท่ากัน (dev DB user_id=1 มี 24 แถว)
- **mutation check:** ถอด `sortBy` ออก ⇒ ล้ม 2 เคส
- `pint` ผ่าน · `tests/Feature/Course` **24 ผ่าน**
- **ทั้งเรพ: 1,661 เคส · 0 failed** (8 skipped · 3 incomplete)

### ✋ ของที่ตรวจแล้วไม่ใช่ปัญหา (อย่าไปแก้ซ้ำ)

รอบก่อนผมเขียนว่าสงสัย `StaffController:432` (`CAST(SUBSTRING(employee_id, 8) AS UNSIGNED)`)
ว่าเป็นญาติของบั๊กเดียวกัน — **ทดสอบบน SQLite จริงแล้วผ่านทั้งคู่**
(`SUBSTRING` เป็น alias ของ `substr` · `CAST(... AS UNSIGNED)` ตกไป NUMERIC affinity)
⇒ `orderByRaw` อีก 18 จุดในเรพไม่มีอันไหนผูกกับ MySQL

### งานที่ค้างหลังรอบนี้

- [x] กวาด `avatar` ✅ · [x] **G18** ✅ · [x] **pint** ✅ · [x] **FIELD()** ✅
- [ ] **SET-S12** deferred (ดู `.agents/photo-path-migration-plan.md`)
- [ ] โค้ดตาย `AuthService::register()` (ยกมาจากรอบ avatar)
- [ ] สาเหตุที่ `pint --test` จาก root รายงานไม่ครบในรอบแรก (ยกมา)

### Branch / Git State

- Branch: `main` · Uncommitted: ไม่มี · **ยังไม่ push** (`8189ec82`)

---

## 2026-09-02 (ต่อ) — pint ทั้งเรพผ่านแล้ว

### สถานะ: ✅ 2 ไฟล์ · **+7 / −6** (จัดรูปแบบล้วน) · commit แล้ว `ff59c04c`

| ไฟล์ | fixer |
|---|---|
| `PublicAcademyController.php` | `statement_indentation` — `return` ที่ไม่ย่อหน้าใน `index()` (หนี้เดิม) |
| `AcademySettingsAuditLogTest.php` | `ordered_imports` · `fully_qualified_strict_types` (`\App\Models\AcademicYear` เรียกแบบ FQN กลางไฟล์) · `no_whitespace_in_blank_line` |

### ⚠️ ข้อสังเกตที่ยังอธิบายไม่ได้ (อย่าเพิ่งเชื่อ)

รอบแรกที่รัน `pint --test` จาก root มัน **รายงานแค่ไฟล์เทสต์ ไม่รายงาน `PublicAcademyController`**
ทั้งที่ไฟล์นั้นตกจริง (`pint --test app/` เห็น) · ผมตั้งสมมติฐานว่า root scan ข้ามโฟลเดอร์
`Api/Public/` แล้ว **ทดสอบแล้วพบว่าผิด** — พอทำให้สองไฟล์ตกพร้อมกัน root scan รายงานครบทั้งคู่
ไม่มีไฟล์แคชของ pint ในเรพด้วย ⇒ **ยังไม่รู้สาเหตุ**

**ข้อควรระวัง:** ถ้า `pint --test` จาก root บอกว่ามีไฟล์ตก 1 ไฟล์ อย่าเพิ่งเชื่อว่ามีแค่นั้น
ให้รัน `pint --test app/` และ `pint --test tests/` ซ้ำด้วย

### หลักฐาน

- `pint --test` ทั้งเรพ **passed · รันซ้ำ 2 รอบ**
- `AcademySettingsAuditLogTest` **9 ผ่าน** · `PublicSchoolDiscoveryTest` **8 ผ่าน**
- `route:list --path=public/schools` ครบ 3 เส้นเหมือนเดิม

### งานที่ค้างหลังรอบนี้

- [x] กวาด `avatar` ✅ · [x] **G18** ✅ · [x] **pint** ✅
- [ ] **SET-S12** deferred (ดู `.agents/photo-path-migration-plan.md`)
- [ ] `CourseController:99` ยังใช้ `FIELD()` ของ MySQL (ญาติของบั๊กที่เจอรอบ G18)
- [ ] โค้ดตาย `AuthService::register()` (ยกมาจากรอบ avatar)
- [ ] สาเหตุที่ `pint --test` จาก root รายงานไม่ครบในรอบแรก

### Branch / Git State

- Branch: `main` · Uncommitted: ไม่มี · **ยังไม่ push** (`ff59c04c`)

---

## 2026-09-02 (ต่อ) — G18: ปิดรูรั่ว endpoint โรงเรียน 4 กลุ่ม

### สถานะ: **G18 ✅ ตรวจครบทุกข้อ** — 9 ไฟล์ + migration 1 + เทสต์ใหม่ 1 · ~~**ยังไม่ push**~~ → เข้า main แล้ว (§สถานะ git)

เอกสารหลัก: [`.agents/school-admin/07-settings.md`](school-admin/07-settings.md) §G18 (D23–D26)

### 🔴 ของจริงร้ายแรงกว่าที่บันทึกไว้ 2 จุด

เอกสารเดิมเขียนว่า "รอบนี้ยังไม่รั่วข้อมูล" — มาจากการตรวจแค่ `index` ตอนที่ยังไม่มีคาบเช็กชื่อเปิด
พออ่านโค้ดจริง:

| จุด | ความเสียหายจริง |
|---|---|
| `school-attendances/{attendance}` (`show`) | คนนอกอ่าน **ชื่อ + รูป + สถานะมา/ขาด/สาย ของนักเรียนทั้งคาบ** ⇒ PII รั่ว |
| `school-attendances/{attendance}/check-in` | คนล็อกอินคนไหนก็ได้ที่มี `qr_token` (QR ขึ้นจอหน้าโรงเรียน ถ่ายส่งต่อได้) **สร้างแถวเช็กชื่อ + รับ points/XP** ⇒ ข้อมูลขยะ + โกงแต้ม |
| `emergency-alerts` 4 เมธอด | คนนอกอ่านประกาศฉุกเฉินและกดตอบรับได้ |

### 🟡 ของแถม: คีย์สิทธิ์ที่แจกไปแล้วแต่ไม่มีใครอ่าน

`school_attendance.view` / `school_attendance.manage` **มีในคาตาล็อกและถูกแจกให้ role แล้ว
แต่ไม่มีโค้ดไหนอ่าน** — `authorizeManager()` ตรวจ `Academy::isAdmin()` อย่างเดียว
⇒ **ครูซึ่งเป็นคนเช็กชื่อจริงโดน 403 มาตลอด** · แพตเทิร์นเดียวกับ G21/G22 ที่ S6/S7 ปิดไป

### การตัดสินใจของเจ้าของโปรเจค (เคาะระหว่างรอบ)

- **D23** เอาคีย์ `school_attendance.*` มาใช้จริง + แจก `.manage` ให้ role `teacher`
- **D24** เพิ่มคีย์ใหม่ `emergency.view` / `emergency.manage` แจกให้ `director`/`admin`
  · **จงใจไม่ delegable ให้ฝ่าย/แผนก**

### 🔴 กับดักที่เจอตอนลงมือ — ติดคีย์ที่ route แล้ววิดเจ็ตนักเรียนพัง

ติด `school_attendance.view` ที่ `index`/`show` ครั้งแรก แล้วพบว่า `SchoolAttendanceWidget`
ของนักเรียนเรียกทั้งสองเส้น (หาคาบที่เปิดวันนี้ → เช็กว่าตัวเองเช็กชื่อหรือยัง)
⇒ **เส้นทางเช็กชื่อของนักเรียนพังทั้งเส้น** · แก้เป็น **D25**:

- อ่าน + `check-in` = **ด่านสมาชิกภาพล้วน** (`academy.permission` ไม่ใส่คีย์)
- คีย์ `.view` ไปคุม **ความลึกของข้อมูล** ในคอนโทรลเลอร์: `show` ไม่มีคีย์ ⇒ เห็นเฉพาะแถวตัวเอง
  ไม่มีรายชื่อคนอื่น ไม่มี summary · `student-history` ไม่มีคีย์ ⇒ ดูได้เฉพาะของตัวเอง
- เส้นที่แก้ข้อมูลใช้ `.manage` ที่ route ตรง ๆ

**บทเรียน:** ก่อนติดคีย์ที่ route ต้องไล่ก่อนว่า **หน้าไหนของนักเรียน/ผู้ปกครองเรียกเส้นนั้น**
— ด่านที่ถูกต้องเชิงความปลอดภัยอาจตัดเส้นทางของผู้ใช้ที่ถูกต้องทิ้งไปด้วย

### `Academy::userCan()` — รวมลำดับสิทธิ์ให้เหลือที่เดียว

`CheckAcademyPermission` เคยถือลำดับไว้คนเดียว ส่วนคอนโทรลเลอร์ตรวจ `isAdmin()` เอง
⇒ **middleware ปล่อยผ่าน แต่ controller ตอบ 403** ย้ายลำดับทั้งหมด (superadmin → เจ้าของ/
`academy_admins` → สมาชิก `status=2` → role → ฝ่าย/กลุ่ม) ไปที่ `Academy::userCan()`
middleware เหลือหน้าที่แปลงผลเป็น HTTP response · ยังแยก `Not a member` กับ
`Insufficient permissions` เหมือนเดิม

### 🟡 บั๊กที่เจอระหว่างเขียนเทสต์

`EmergencyAlertController::active()` ใช้ `orderByRaw("FIELD(severity, ...)")` — `FIELD()`
มีเฉพาะ MySQL ⇒ **แบนเนอร์ฉุกเฉิน 500 บน SQLite และเทสต์ครอบไม่ได้เลย** เปลี่ยนเป็น `CASE`
· ยังเหลืออีกจุดที่ `CourseController:99` (คนละโดเมน ยังไม่แตะ)

### หลักฐานที่ Claude รันเอง

- `route:list --json` — ตรวจ middleware ทีละเส้นครบ 21 เส้น ตรงตารางที่ออกแบบไว้
  · `my-role` ว่างตามตั้งใจ · เส้นสาธารณะ `/api/public/schools/{academy}/support-summary` ไม่ถูกแตะ
- **mutation check 6 แบบ ⇒ ล้มตรงเคสที่ควรล้มทุกครั้ง · คืนไฟล์ครบทุกรอบ**
  1. ถอด `academy.visibility` ⇒ ล้ม 4 (archived x2 + private + archived support-summary)
  2. ถอด `academy.permission*` ⇒ ล้ม 5 (คนนอก x4 + สมาชิกธรรมดา)
  3. `authorizeManager` กลับเป็น `isAdmin` ⇒ ล้ม "ครูเปิดคาบได้"
  4. `canManageAlerts` กลับเป็น `isAdmin` ⇒ ล้ม "ผอ.ประกาศฉุกเฉินได้"
  5. ถอดการหั่น payload ของ `show` ⇒ ล้ม "นักเรียนเห็นเฉพาะแถวตัวเอง"
  6. ถอดด่าน `student-history` ⇒ ล้ม "นักเรียนอ่านประวัติเพื่อนไม่ได้"
- **migration รันจริงบน MySQL + ทดสอบ `down()` ครบรอบ**
  director 50→52→50→52 · admin 46→48→46→48 · teacher 23→24→23→24
  · `academy_permissions` 0→2→0→2 ⇒ `down()` ใช้ได้จริง ไม่ใช่ stub
- `php -l` ทุกไฟล์ · `pint --test` ผ่าน
- เทสต์ใหม่ `AcademyEndpointGuardsG18Test` **21 เคส ผ่านหมด**
- **`artisan test` ทั้งเรพ: **1,647 passed · 3 incomplete · 8 skipped · 0 failed** (563s) — 1,626 เดิม + 21 ใหม่**

### 🔴 เทสต์ทั้งเรพตายกลางคันด้วย OOM — ไม่ใช่เทสต์ล้ม และไม่ใช่เพราะโค้ดรอบนี้

รันทั้งเรพแล้วได้ `EXIT=255` จบด้วย stack trace ยาวเหยียดที่ `finfo->file()` ใน
`WithdrawalPayoutProofTest` ซึ่ง**ไม่เกี่ยวกับ G18 เลย** · ขุดจริงเจอ:

- ข้อความจริงคือ `Allowed memory size of 536870912 bytes exhausted`
- `memory_limit` ถูกตั้งไว้ที่ **512M ใน `phpunit.xml`** ⇒ **`php -d memory_limit=...` ไม่มีผล
  เพราะ `<ini>` ของ phpunit ชนะเสมอ** (เสียเวลาไปสองรอบกว่าจะรู้)
- peak จริงของทั้งเรพเมื่อ **ปิด xdebug = 474MB** ⇒ เฉียดเพดาน 512M อยู่แล้วมาก่อนหน้านี้
  พอ xdebug เปิด (ค่าเริ่มต้นของ WAMP เครื่องนี้) overhead ดันทะลุ
- **ขยับ `phpunit.xml` เป็น 2G** ⇒ `php artisan test` แบบปกติกลับมาเขียวทั้งเรพ

⇒ เพดานนี้เป็นหนี้ที่มีอยู่ก่อนแล้ว เทสต์ใหม่ 21 เคสแค่เป็นฟางเส้นสุดท้าย

### ⚠️ ความผิดพลาดของ Claude ในรอบนี้ (บันทึกไว้ตามจริง)

เทสต์ "นักเรียนเห็นเฉพาะแถวตัวเอง" ที่ผมเขียนครั้งแรกใช้
`assertStringNotContainsString((string) $classmate->id, json_encode(...))`
— ค้นหา **เลขหลักเดียว** ในก้อน JSON ⇒ ไปแมตช์เลข `3` ใน timestamp `23:13:25`
**ผ่านตอนรันแยก แต่ล้มตอนรันทั้งเรพ** เพราะเวลาเปลี่ยน · เป็นเทสต์ flaky ที่ผมสร้างเอง
แก้เป็นการเทียบ `array_column($records,'student_id')` กับ `[$student->id]` ตรง ๆ
แล้วรันซ้ำ 3 รอบยืนยัน

**บทเรียน:** ห้าม assert ด้วย substring บนก้อน JSON ที่มี timestamp — ให้ดึงฟิลด์ออกมาเทียบ

### สิ่งที่แก้ฝั่ง `ui/`

`EmergencyAlertBanner.vue` — เดิม poll ทุก 60 วิ และ log error ทิ้ง · ตอนนี้คนนอกได้ 403
ซึ่งเป็นสถานะ**ถาวร** ⇒ หยุด poll ทันทีที่เจอ 403 (ไม่งั้นยิง 403 ไม่จบ) · ไม่มีการแก้ UI อื่น
เพราะ `$appends`/response shape ไม่เปลี่ยน

### งานที่ค้างหลังรอบนี้

- [x] กวาด `avatar` ✅ · [x] **G18** ✅
- [ ] `PublicAcademyController.php` ตก `pint --test` (ของเดิม)
- [ ] **SET-S12** deferred (ดู `.agents/photo-path-migration-plan.md`)
- [ ] `CourseController:99` ยังใช้ `FIELD()` ของ MySQL (ญาติของบั๊กที่เจอรอบนี้)
- [ ] โค้ดตาย `AuthService::register()` (ยกมาจากรอบ avatar)

### Branch / Git State

- Branch: `main` · **ยังไม่ push** · migration รันบน dev DB แล้ว (batch ใหม่)

---

## 2026-09-02 (ต่อ) — กวาดบั๊ก `avatar` ทั้งระบบ · **ปิดครบทั้งคลาสบั๊ก**

### สถานะ: ✅ ตรวจครบทุกข้อ — 19 ไฟล์ (**−45 / +45**) + เทสต์ใหม่ 1 ไฟล์ · **commit แล้ว 2 ชุด**

`f18a8a72` (fix) · `59c6c205` (test)

### ต้นตอ

`avatar` / `profile_photo_url` เป็น **accessor ใน `$appends` ของ `User`** (อ่านจาก
`profile_photo_path`) ไม่ใช่คอลัมน์ · ยืนยันจาก `Schema::getColumnListing('users')`
เอาไปใส่ในลิสต์คอลัมน์เมื่อไหร่ MySQL ตอบ `Unknown column` ⇒ **500 ทั้งเส้น**

### 🔴 การนับของรอบก่อนตกไป 2 อย่าง

1. **รูปแบบที่ 2 ที่ไม่เคยถูกนับ** — `->get(['id','name','avatar', ...])`
   เจอเพิ่ม **4 จุด**: `DepartmentController:96` · `AdminPointsService:109,120` ·
   `AdminWalletService:132` (grep เดิมมองหาแต่ `with(`/`load(` จึงไม่เห็น)
2. **`ClassScheduleController` 3 จุดใช้ `teacher:id,first_name,last_name,avatar`**
   `users` **ไม่มี `first_name`/`last_name`** ด้วย — แพตเทิร์นเดียวกับ G27 เป๊ะ
   ฝั่ง UI อ่าน `teacher?.name` อยู่แล้ว (`schedule.vue:516`) ⇒ เปลี่ยนเป็น
   `teacher:id,name,profile_photo_path`

รวมของจริง **45 จุด ใน 19 ไฟล์** (ไม่ใช่ 34 จุด ใน 15 ไฟล์ ตามที่เขียนไว้)

### สิ่งที่ไม่เปลี่ยน

JSON ที่ frontend เห็น **เหมือนเดิมทุกคีย์** — `avatar` / `profile_photo_url`
ยังมาครบจาก `$appends` ⇒ ไม่ต้องแก้ฝั่ง `ui/` เลย
(จุดเดียวที่เพิ่มคอลัมน์คือ `CoursePostShareController` ใส่ `name` เพราะ
`UserResource` ใช้เป็น `display_name` และ accessor `avatar` ใช้เป็น fallback)

### หลักฐานที่ Claude รันเอง

- `git diff --stat` = **19 ไฟล์ · −45 / +45** · อ่าน diff ทุกบรรทัด
- **ยิงจริงบน MySQL 17 รูปแบบ ⇒ OK ทุกตัว** และ `avatar` คืน URL รูปจริง
- **mutation check: ย้อนกลับไปใช้ของเดิม 7 รูปแบบ ⇒ `SQLSTATE[42S22]` ทุกตัว**
  (`avatar` · `first_name` · `profile_photo_url`) ⇒ ยืนยันว่าเป็น 500 จริงทั้งหมด
- `php -l` 20 ไฟล์ · `pint --test` ผ่าน
- **`artisan test` ทั้งเรพ: 1,626 passed · 3 incomplete · 8 skipped · 0 failed** (561s)
  เดิม 1,624 ⇒ +2 คือเทสต์ใหม่ · ไม่มีอะไรถอยหลัง

### เทสต์กันถอยหลัง — `tests/Feature/NoAccessorColumnsInQueriesTest.php`

SQLite **ไม่ฟ้อง**เมื่อ select คอลัมน์ที่ไม่มีอยู่ (บทเรียนเดิมของโปรเจค) ⇒ assert
status code จับคลาสนี้ไม่ได้ **เทสต์จึงสแกนซอร์สใน `app/`** จับทั้งสองรูปแบบ
พร้อมชี้ไฟล์:บรรทัด · เคสแรกยืนยันจาก schema ว่าไม่มีตารางไหนมีคอลัมน์ทั้งสองนี้
**mutation check: ใส่บั๊กกลับไป 2 จุด ⇒ ล้มพร้อมชี้ตำแหน่งครบทั้งคู่ · คืนไฟล์แล้ว**

### สแกนซ้ำแบบครอบทั้งคลาสบั๊ก (ไม่ใช่แค่ `avatar`)

เขียนสคริปต์ไล่ **ทุกโมเดลที่มี `$appends`** เทียบกับคอลัมน์จริงของตารางตัวเอง
ได้ **37 ชื่อ accessor** ที่ไม่ใช่คอลัมน์ (`full_url` · `qr_code_url` ·
`available_balance` · `is_liked_by_auth` ฯลฯ) แล้วสแกนหาการเอาไปใส่ในลิสต์คอลัมน์
⇒ **0 จุด** ⇒ ไม่ใช่แค่ `avatar` ที่หมด แต่**ทั้งคลาสบั๊กนี้สะอาดแล้ว**

### 🟡 ของนอกขอบเขตที่เจอ (ยังไม่แตะ)

`AuthService::register()` (`AuthService.php:35`) ส่ง `avatar` / `referral_code` /
`referrer_code` / `phone` เข้า `User::create()` ทั้งที่**ไม่มีใน `$fillable`** ⇒
ถูกทิ้งเงียบ ๆ (ไม่ใช่ 500) · และ**ไม่เซ็ต `name` เลย**
ตรวจแล้ว **ไม่มีใครเรียกเมธอดนี้** (มีแต่ `assignDefaultRole`) ⇒ เป็นโค้ดตาย
คนละบั๊ก ควรมีรอบของตัวเอง

### งานที่ค้างหลังรอบนี้

- [x] **กวาด `avatar`/`profile_photo_url`** ✅ ปิดแล้ว
- [ ] **G18** — `school-attendances` / `emergency-alerts` / `revenue/support-summary` /
      `my-role` ยังไม่มีด่านสมาชิกภาพและด่าน archived · **น่าจะเป็นตัวถัดไป**
- [ ] `PublicAcademyController.php` ตก `pint --test` (ของเดิม)
- [ ] **SET-S12** deferred (ดู `.agents/photo-path-migration-plan.md`)
- [ ] โค้ดตาย `AuthService::register()` (ดูด้านบน)

### Branch / Git State

- Branch: `main` · Uncommitted: ไม่มี · **commit แล้ว 2 ชุด แต่ยังไม่ push**

---

## 2026-09-02 (ต่อ) — เมนู #7 SET-S13: ลบเมธอดตายใน AcademyController · **เมนู #7 ปิดครบทุก step แล้ว**

### สถานะ: **SET-S13 ✅ ตรวจครบทุกข้อ** — 1 ไฟล์ · **−157 / +0** (deletion ล้วน)

เอกสารหลัก: [`.agents/school-admin/07-settings.md`](school-admin/07-settings.md) §5.25

### 🔴 ตัวเลขในเอกสารเดิมผิด — แก้ตอนลงมือ

เอกสารเขียนว่า "11 เมธอดที่ไม่มี route ชี้มา" พร้อมไล่ชื่อ `updateAcademySetting` ไว้ด้วย
**ตรวจของจริงแล้วผิดทั้งจำนวนและชื่อ:**
- คลาสนี้มี public method **26 ตัว** · มี route ชี้ถึงจริง **11 ตัว** ⇒ **ตายจริง 15 ตัว**
- `updateAcademySetting` **ไม่มีอยู่จริง** — ตัวจริงคือ `updateSettings` ซึ่ง **มี route**
  และเป็น endpoint ที่ SET-S1..S11 ทำงานอยู่บนนั้นทั้งหมด
  ⇒ **ถ้าเชื่อเอกสารแล้วลบตามชื่อ จะลบหัวใจของเมนูนี้ทิ้ง**
- เอกสารตกกลุ่ม `searchAcademies*` ไปทั้ง 5 ตัว

**วิธีที่ใช้หาให้ถูก:** `route:list --json` แล้วกรองด้วย **ชื่อคลาสเต็ม**
— ไม่ใช่ grep จากตาราง `route:list` เพราะคอลัมน์ action ถูกตัดท้ายด้วย `…`
(grep แบบนั้นได้ชื่อครึ่ง ๆ อย่าง `getAllAca` และดึงเมธอดของ `AcademyController` คนละคลาสมาปน)

### 15 เมธอดที่ลบ + ทำไมมันอันตรายถ้าปล่อยไว้

`create_course` · `edit` · `joinAcademy` · `leaveAcademy` · `acceptMember` · `rejectMember` ·
`removeMember` · `updateMembershipFees` · `updateAcademyLogo` · `updateAcademyCover` ·
`searchAcademies` · `searchAcademiesMembers` · `searchAcademiesCourses` ·
`searchAcademiesCourseStudents` · `searchAcademiesCourseTeachers`

- ทั้งหมดเป็นสไตล์ web เก่า (`redirect()->back()`) และ **ไม่มีการตรวจสิทธิ์เลยสักตัว**
- `joinAcademy` / `acceptMember` / `removeMember` แก้สมาชิกภาพผ่าน pivot ด้วยสถานะสตริง
  `'accepted'/'rejected'` ⇒ **ขัดกับ convention จริง** (`academy_members.status` เป็น int · 2 = APPROVED)
- `updateAcademyLogo` เขียนคอลัมน์ `logo` เป็น **ชื่อไฟล์เปล่า** ขณะที่ `updateSettings` เขียนเป็น
  **URL เต็ม** ⇒ สองนิยามในคอลัมน์เดียว **แพตเทิร์นเดียวกับ G22 ที่ SET-S7 เพิ่งปิด**

### หลักฐานที่ Claude รันเอง

- `git diff --stat` = **1 ไฟล์ · −157 / +0** · อ่าน diff ทุกบรรทัด ตรงสเปคเป๊ะ
- นับเมธอดที่เหลือ = **11 พอดี** · 15 ชื่อหายครบ · 11 ชื่อที่ต้องอยู่ครบทุกตัว
- import: `CourseResource` = 0 · `Intervention` = 0 · `Storage`/`User` ยังอยู่และยังมีผู้ใช้จริง
  (`Storage::` 6 จุด · `User` เป็น type hint ของ `getAuthMemberedAcademies`)
  · บรรทัดคอมเมนต์ `Image::make` ใน `store()` คงไว้ตามตั้งใจ
- `php -l` · `pint --test` ผ่าน · `route:list` build ได้ครบ **844 routes**
- `tests/Feature/Academy` **186 passed · 2 incomplete · 0 failed** (เท่าเดิม)
- **`artisan test` ทั้งเรพ: 1,624 passed · 3 incomplete · 8 skipped · 0 failed** (556s)

### สิ่งที่จงใจไม่แตะ

ทางลัด `isSuperAdmin()` ใน `CheckAcademyPermission` ที่ mutation check ของ SET-S10 พบว่าซ้ำกับ
`Academy::isAdmin()` — **เก็บไว้เป็น defense in depth** การตัดออกจะทำให้ middleware ด้านสิทธิ์
ไปพึ่ง internals ของเมธอดอื่นเพียงทางเดียว แลกไม่คุ้มกับการลดโค้ด 3 บรรทัด

---

## 🎉 เมนู #7 (ตั้งค่าโรงเรียน) — ปิดครบทุก step แล้ว

| step | สาระ | สถานะ |
|---|---|---|
| S1 | อุดช่องโหว่สิทธิ์ (G1+G5) | 🟢 |
| S2 | เก็บถาวรแทนการลบ | 🟢 + migrate |
| S3 | รวมคีย์สิทธิ์ให้เหลือชุดเดียว | 🟢 + migrate |
| S4 | โหมดดูอย่างเดียว | 🟢 |
| S5 | ทำให้สวิตช์มีผลจริง | 🟢 + migrate |
| S6 | แท็บระบบและนโยบาย (ปิด G21) | 🟢 |
| S7 | ฟิลด์อัตลักษณ์ (ปิด G22) | 🟢 + migrate |
| S8 | ลบ `name_slug` + ซ่อม redirect | 🟢 + migrate |
| S9 | audit log การแก้ตั้งค่า (ปิด G26–G28) | 🟢 |
| S10 | เทสต์เส้นทางสิทธิ์ | 🟢 |
| S11 | UX เก็บตก + หนี้ `?view=archived` | 🟢 |
| S13 | ล้างเมธอดตาย | 🟢 |
| S12 | รูปเป็น relative path | 🔵 deferred — รอทำพร้อม migration รูปทั้งระบบ |

**เทสต์ของเมนูนี้:** 4 ไฟล์ 35 เคส (`AcademySettingsUpdateTest` 7 · `AcademyIdentityFieldsTest` 11
· `AcademySettingsAuditLogTest` 9 · `AcademySettingsPermissionPathsTest` 8)

### งานที่ค้างหลังปิดเมนู #7

- [ ] **กวาด `avatar`/`profile_photo_url` ที่เหลือ 34 จุด ใน 15 ไฟล์** — สุ่มยิงจริง 4 จุด พัง 3
      (PhotoController · โมดูลบุคลากรทั้งโมดูล · หน้าแอดมินถอนเงิน) · **น่าจะเป็นตัวถัดไป**
- [ ] **G18** — `school-attendances` / `emergency-alerts` / `revenue/support-summary` / `my-role`
      ยังไม่มีด่านสมาชิกภาพและด่าน archived
- [ ] `PublicAcademyController.php` ตก `pint --test` (ของเดิม)
- [ ] **SET-S12** deferred (ดู `.agents/photo-path-migration-plan.md`)
- [x] **push แล้ว** — `origin/main` = `9624b92f` · local กับ remote ตรงกัน (0/0)

### Branch / Git State

- Branch: `main` · Uncommitted: ไม่มี (clean) · **push แล้ว**
- ตอนเริ่ม push `origin/main` อยู่ที่ `0f108b46` (docs ของ SET-S11) อยู่ก่อนแล้ว
  ⇒ รอบนี้ push จริงแค่ 4 commit ท้าย (SET-S10 2 · SET-S13 2) · ไม่มี commit ของใครค้างอยู่ฝั่ง remote (behind = 0)

---

## 2026-09-02 (ต่อ) — เมนู #7 SET-S10: เทสต์เส้นทางสิทธิ์ของหน้าตั้งค่า

### สถานะ: **SET-S10 ✅ ตรวจครบทุกข้อ** — 1 ไฟล์ (เทสต์ใหม่ล้วน · **ไม่แตะโค้ดโปรดักชันเลย**)

เอกสารหลัก: [`.agents/school-admin/07-settings.md`](school-admin/07-settings.md) §6 + §8

### ทำไมต้องมี — middleware มีทางปล่อยผ่าน 5 ทาง แต่เทสต์เดิมครอบแค่ 2

`CheckAcademyPermission` ปล่อยผ่านตามลำดับ: superadmin → `Academy::isAdmin()` (เจ้าของ **หรือ**
แถวใน `academy_admins`) → ต้องเป็นสมาชิก `status = 2` → สิทธิ์จาก role → สิทธิ์จากฝ่าย/กลุ่ม
เทสต์เดิม 7 เคสครอบแค่ "เจ้าของแก้ได้" กับ "คนนอกโดน 403"

### 8 เคสใหม่

| เคส | ผลที่คาด |
|---|---|
| role ถือ `settings.manage` (ไม่ใช่เจ้าของ) | 200 + ค่าลงฐานจริง |
| role ถือแค่ `settings.view` | 403 `Insufficient permissions` |
| ถือ `settings.manage` แต่ `status = 1` | 403 `Not a member of this academy` |
| superadmin ที่ไม่มีแถวสมาชิก | 200 |
| แถวใน `academy_admins` โดยไม่มีแถวสมาชิก | 200 |
| **เปิด `settings.manage` ให้ฝ่าย/กลุ่ม** | **403** (settings ไม่อยู่ใน `DEPARTMENT_DELEGABLE_FAMILIES`) |
| **คู่เทียบ: เปิด `students.view` ให้กลุ่มเดียวกัน** | **404** (ผ่านด่านสิทธิ์แล้ว) |
| ไม่มีแถวสมาชิกเลย | 403 `Not a member of this academy` |

คู่ `group settings.manage` ⇒ 403 กับ `group students.view` ⇒ 404 คือหัวใจของรอบนี้ —
ถ้ามีแค่เคสแรกแล้วเขียว จะแยกไม่ออกว่าเขียวเพราะกฎ non-delegable ทำงาน หรือเพราะ
เส้นทางสิทธิ์จากกลุ่มพังทั้งเส้น

### หลักฐานที่ Claude รันเอง

- `php -l` · `pint --test` ผ่าน · `git status` ยืนยันว่า **ไม่มีไฟล์ใน `app/` `routes/` `ui/` ถูกแตะ**
- เทสต์ใหม่ **8 passed · 13 assertions**
- `tests/Feature/Academy` ทั้งโฟลเดอร์ **186 passed · 2 incomplete · 0 failed** (เดิม 178 + ใหม่ 8)
- **mutation check 6 แบบ ⇒ ล้มตรงเคสที่ควรล้ม 5 ใน 6** (คืนไฟล์ครบทุกครั้ง)

### ⚠️ สิ่งที่ mutation check เปิดเผย — โค้ดซ้ำซ้อนของทางลัด superadmin

ถอดทางลัด `isSuperAdmin()` ออกจาก middleware **อย่างเดียวแล้วเทสต์ยังเขียว**
เพราะ `Academy::isAdmin()` เช็ค `isSuperAdmin()` ซ้ำอยู่แล้ว (`Academy.php:175`)
ต้องถอดทั้งสองที่พร้อมกันถึงล้ม ⇒ เทสต์พิสูจน์ได้แค่ "superadmin เข้าได้" ไม่ได้พิสูจน์ว่าเข้าทางไหน
**บันทึกไว้ตามจริง ไม่ได้แก้ในรอบนี้** (S10 คือรอบเติมเทสต์ ไม่ใช่รอบแก้โค้ด) — เป็นของกินคู่กับ SET-S13

**Claude แก้เองหลัง agy 1 จุด:** เคส `settings.view` กับเคส group เดิม assert แค่ status 403
ซึ่งเขียวได้ด้วยเหตุผลผิด ๆ (ตกที่ด่านสมาชิกภาพแทนด่านสิทธิ์) จึงเพิ่ม assert ข้อความให้ทั้งคู่

### งานที่ค้าง

- [x] commit แล้ว 2 ชุด (`SET-S10` test + docs) — **ยังไม่ push**
- [ ] **SET-S13** (ตัวสุดท้ายของเมนู #7) — ลบ 11 เมธอดตายใน `AcademyController` +
      พิจารณาทางลัด superadmin ที่ซ้ำซ้อนใน `CheckAcademyPermission` ไปพร้อมกัน
- [ ] **กวาด `avatar`/`profile_photo_url` ที่เหลือ 34 จุด ใน 15 ไฟล์** (งานแยก)
- [ ] **G18** (ยกมา) · `PublicAcademyController` ตก `pint --test` (ของเดิม)
- [ ] **SET-S12** ยัง deferred (รูปโลโก้/ปกเป็น relative path — รอทำพร้อม migration รูปทั้งระบบ)

### Branch / Git State

- Branch: `main` · Uncommitted: ไม่มี (clean) · **ยังไม่ push**
- สะสมบน `main`: SET-S7 6 · SET-S9 4 · รอบเก็บงาน 3 · SET-S11 3 · SET-S10 2

---

## 2026-09-02 (ต่อ) — เมนู #7 SET-S11: UX เก็บตกของหน้าตั้งค่า + หนี้ `?view=archived`

### สถานะ: **SET-S11 ✅ ตรวจครบทุกข้อ รวมตรวจด้วยตาบนเบราว์เซอร์จริงที่ 375px** — 2 ไฟล์ · **+128 / −0**

เอกสารหลัก: [`.agents/school-admin/07-settings.md`](school-admin/07-settings.md) §5.23–5.24

### G12 มี 3 ข้อ แต่ข้อหนึ่งทำไปแล้วตั้งแต่ SET-S4

`settings.vue:179` มี `if (isOwner.value) base.push({ id: 'danger', ... })` + `watch(tabs)` ที่เด้ง
`activeTab` กลับแท็บแรกอยู่แล้ว ⇒ **"ซ่อนแท็บโซนอันตราย" ไม่ต้องทำซ้ำ** (สั่งห้าม agy แตะในสเปค)

### สิ่งที่ปิดจริงรอบนี้

| # | ปัญหา | แก้ยังไง |
|---|---|---|
| 1 | ไม่เตือนตอนออกจากหน้าโดยยังไม่บันทึก | `isDirty` จาก**สแนปช็อต canonical** (เรียงคีย์ทุกชั้น + ตัดลิงก์โซเชียลที่ว่างทิ้ง) + `onBeforeRouteLeave` + `beforeunload` ตามแพตเทิร์นที่ `profile/[id]/settings.vue` ใช้อยู่แล้ว |
| 2 | บันทึกแล้วไม่ refresh | `academy.value = response.academy` + `populateForm()` + ล้าง `avatarFile`/`coverFile` **ก่อน**บล็อก redirect ของ SET-S8 |
| 3 | `?view=archived` ไม่ refetch (หนี้ S2) | `switchView('archived')` ยิง `fetchArchivedAcademies()` |

**จุดที่ต้องระวังและเขียนกำกับไว้:** ห้ามเตือนระหว่าง `isSaving` ไม่งั้นจะไปบล็อก redirect หลัง
เปลี่ยนชื่อของ SET-S8 · และสแนปช็อตต้องตัดคีย์โซเชียลที่ว่าง ไม่งั้นพิมพ์แล้วลบจนว่าง = "มีการแก้ไข"

### หลักฐาน — รอบนี้ได้ใช้หนี้ "ตรวจด้วยตา" ที่ค้างมาตั้งแต่ SET-S4

ผู้ใช้ล็อกอินให้ในแพเนลเบราว์เซอร์ (เบราว์เซอร์ของ Claude แยกจาก Chrome ของผู้ใช้ ไม่มี session
และ Claude กรอกรหัสผ่านแทนไม่ได้)

- **A. พิมพ์ลิงก์ facebook แล้วลบจนว่าง → กดออกจากหน้า** ⇒ **confirm 0 ครั้ง** ออกได้ปกติ
- **B. `?view=archived` เปลี่ยน query โดยหน้าไม่ remount** (ดัก `window.fetch`)
  ⇒ ยิง `GET /api/academies/archived` **1 ครั้งพอดี** (เดิมไม่ยิงเลย)
- **C. แก้ `slogan` แล้วกดออก** ⇒ เรียก confirm ด้วยข้อความ
  **"คุณมีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก ต้องการออกจากหน้านี้หรือไม่?"** และ **ค้างหน้าเดิมจริง**
- **D. เพิ่งกด "บันทึก" สำเร็จแล้วกดออกทันที** ⇒ **confirm 0 ครั้ง** (สแนปช็อตถูกรีเซ็ต)
- **E. ของแถม — พิสูจน์ SET-S9 ผ่าน UI จริง:** กดบันทึกจริงพร้อมลิงก์ facebook
  ⇒ ได้ log **1 แถวพอดี** `settings_update` · **`user_id=1`** · diff คีย์เดียว `social_media_links`
  ⇒ **ยืนยันว่า `user_id=NULL` ที่เจอตอน S9 เป็นข้อจำกัดของ harness ไม่ใช่บั๊กของโค้ด**
- **375px:** `document.scrollWidth = 375 = innerWidth` ไม่มีล้นแนวนอน · แท็บครบ 6 ตัว
- **ล้างข้อมูลทดสอบครบ:** `social_media_links` กลับเป็น NULL · slogan เดิม · ลบ log ทดสอบ 1 แถว
  · `member_activity_logs` ของ academy 1 กลับมาที่ 3 แถว · `privacy=public`
- SFC compile 2 ไฟล์ OK · `git diff --stat` = **+128 / −0** ไม่มีบรรทัด `-` เลย

### สิ่งที่ยังไม่ได้ตรวจ

- ตรวจที่ 375px จุดเดียว (768/1280px ไม่ได้ตรวจรอบนี้ — ไม่มี markup ใหม่)
- `npm run build` — **ผู้ใช้รันเอง**

### งานที่ค้าง

- [x] commit แล้ว 2 ชุด (`SET-S11` feat + fix) — **ยังไม่ push**
- [ ] **กวาด `avatar`/`profile_photo_url` ที่เหลือ 34 จุด ใน 15 ไฟล์** (งานแยก)
- [ ] **G18** (ยกมา) · `PublicAcademyController` ตก `pint --test` (ของเดิม)
- [ ] ตัวถัดไปตามลำดับ: S1→S3→S4→S5→S2→S8→S6→S7→S9→**S11**→S10 ⇒ **SET-S10**
      (เติมเทสต์ที่ยังขาด: role ที่ถือ `settings.manage` ต้องผ่าน · สมาชิกที่ไม่ใช่ APPROVED ต้องถูกปฏิเสธ
      · superadmin · สิทธิ์จากฝ่าย/กลุ่ม) · แล้วเหลือ **SET-S13** (ลบ 11 เมธอดตายใน `AcademyController`)

### Branch / Git State

- Branch: `main` · Uncommitted: ไม่มี (clean) · **ยังไม่ push**
- สะสมบน `main`: SET-S7 6 · SET-S9 4 · รอบเก็บงาน 3 · SET-S11 3

---

## 2026-09-02 (ต่อ) — เก็บงานค้าง 2 ตัวก่อนไป SET-S11

### สถานะ: **✅ ตรวจครบทุกข้อ** — 4 ไฟล์ (แก้ 2 · เทสต์ใหม่ 2) · commit แล้ว 2 ชุด

### G29 — ประวัติกิจกรรมสมาชิกไม่มีด่านสิทธิ์เลย

ทั้ง 3 เส้นใต้ `{academy}/activity-log` มีแค่ `auth:api` และใน controller ก็ไม่มีการตรวจสิทธิ์
สักบรรทัด ⇒ ใครล็อกอินก็อ่านประวัติของโรงเรียนใดก็ได้ (มี `guardian_sensitive_view` +
old/new values ของสมาชิกอยู่ในนั้น)

**แก้:** `academy.permission:members.view,reports.view` ที่ระดับ prefix
รับสองคีย์เพราะเมนูแอดมินโชว์ลิงก์นี้ด้วย `can('reports.view')` อยู่แล้ว
(`admin.vue:256`) — ถ้าใส่แค่ `members.view` คนที่เห็นเมนูจะกดแล้วโดน 403 (บทเรียนจาก G21)
· เส้น `activity-log/actions` ไม่มี `{academy}` ให้ผูกและคืนแค่รายชื่อ action จึงคง `auth:api`

**mutation check:** ถอด middleware ⇒ คนนอกได้ **200** ⇒ รูรั่วมีจริง

### `/api/notifications/recent` 500 ทุกหน้า

`sender:id,name,avatar` — `avatar` เป็น **accessor** ของ User (อ่านจาก `profile_photo_path`)
ไม่ใช่คอลัมน์ · **ทั้งฐานไม่มีตารางไหนมีคอลัมน์ `avatar` เลย** (`information_schema` ⇒ 0 แถว)
ในฐาน dev มี notifications 4,642 แถว มี sender 4,622 ⇒ พังแทบทุกครั้งที่โหลดหน้าไหนก็ตาม

**แก้:** select `profile_photo_path` — คีย์ `avatar` และ `profile_photo_url` ยังมาครบจาก
`$appends` ของ User ⇒ frontend ไม่ต้องแก้ (ยืนยันบน MySQL: `sender.avatar` = URL รูปจริง)

### 🔴 กับดักใหญ่ของรอบนี้ — SQLite ไม่ฟ้องเมื่อ select คอลัมน์ที่ไม่มีอยู่

ใส่ `'avatar'` กลับไปแล้ว `NotificationRecentTest` **ยังเขียว** บน SQLite ทั้งที่ MySQL ตอบ 500
⇒ การ assert แค่ status code จับ regression คลาสนี้ไม่ได้เลย
**เทสต์จึง assert ว่า `profile_photo_path` อยู่ใน payload ของ sender** ซึ่งเป็นด่านเดียวที่จับได้
(mutation check ยืนยันแล้วว่าล้ม) · บันทึกเป็น instance ที่ 8 ใน memory `tests-sqlite-vs-mysql`

### 🔴 บั๊กเดียวกันยังเหลืออีก 34 จุด ใน 15 ไฟล์ (งานแยก ยังไม่แตะ)

สุ่มยิงจริง 4 จุด **พัง 3**: `Photo::with('user:id,name,username,avatar')` ·
`StaffProfile::with('user:id,name,avatar')` (ทั้งโมดูลบุคลากร) ·
`WalletTransaction::with('user:id,name,avatar')` (หน้าแอดมินถอนเงิน)

กระจายอยู่ที่ `PhotoController` 6 · `AlbumController` 5 · `TypingRaceController` 4 ·
`Staff`/`StaffAttendance`/`Payroll` 6 · `InstructorDashboard` 3 · `AdminWalletService` 2 ·
`AcademyClaimService`/`CourseClaimService` 2 · `GameScore`/`TypingClassroom`/`TypingTournament`/
`CoursePostShare`/`CourseReport` ที่เหลือ
· บางจุดยังใส่ `profile_photo_url` (accessor) ลงใน select ด้วย

### หลักฐานที่ Claude รันเอง

- `php -l` 2 ไฟล์ · `pint --test` ผ่านทั้ง 4 ไฟล์ · `grep -c avatar NotificationController` = **0**
- `route:list --path=activity-log` — 3 เส้นใต้ `{academy}` ติด `members.view,reports.view` ครบ ·
  เส้น `actions` ยังเป็น `auth:api` ตามตั้งใจ
- **ยิงจริงบน MySQL:** query ใหม่ผ่าน · `sender.avatar` = URL รูปจริงจาก `$appends`
- เทสต์ใหม่ **7 passed** (guards 6 + notification 1) · `tests/Feature/Academy` **178 passed ·
  2 incomplete · 0 failed** (เดิม 172 + ใหม่ 6)
- **mutation check 2 แบบ ⇒ ล้มตรงเคสที่ควรล้มทั้งคู่** · คืนไฟล์ครบ

### ⚠️ ความผิดพลาดของ Claude ในรอบนี้ (บันทึกไว้ตามจริง)

ตอนจะเช็คว่า schema ฝั่งเทสต์มีคอลัมน์ `avatar` ไหม ผมสั่ง `Artisan::call('migrate', ...)`
ใน tinker โดยคิดว่า `--database=sqlite` จะเปลี่ยน connection ให้ — **มันยิงไปที่ MySQL**
แล้วตายที่ `telescope_entries already exists` · ตรวจแล้วว่า **ไม่มี migration ไหนถูกรัน**
(`max(batch)` ยังเป็น 134 = คู่ของ SET-S7 ไม่มี batch ใหม่) ⇒ ฐานไม่ถูกแตะ
บทเรียน: อย่าเรียก `migrate` เพื่อสำรวจ schema — เขียนเทสต์ชั่วคราวที่ `Schema::hasColumn()` แทน

### งานที่ค้างหลังรอบนี้

- [x] G29 ✅ · [x] `/notifications/recent` ✅
- [ ] **กวาด `avatar`/`profile_photo_url` ที่เหลือ 34 จุด ใน 15 ไฟล์** (งานแยก ควรมีรอบตรวจของตัวเอง)
- [ ] **G18** (ยกมา) · **หนี้ตรวจด้วยตา SET-S4** (ยกมา) · `PublicAcademyController` ตก `pint --test`
- [ ] ตัวถัดไป: **SET-S11** (UX เก็บตก G12 + `?view=archived` ที่ยกมาจาก S2)

### Branch / Git State

- Branch: `main` · Uncommitted: ไม่มี (clean) · **ยังไม่ push**
- สะสมบน `main`: 6 commit ของ SET-S7 + 4 ของ SET-S9 + 2 ของรอบเก็บงานนี้

---

## 2026-09-02 — เมนู #7 SET-S9: ประวัติการแก้ตั้งค่า + ปิดรูรั่ว audit-log ของโรงเรียน

### สถานะ: **SET-S9 ✅ ตรวจครบทุกข้อ** — 8 ไฟล์ (แก้ 6 · ใหม่ 2) · **ไม่มี migration** · **commit แล้ว 4 ชุด**

เอกสารหลัก: [`.agents/school-admin/07-settings.md`](school-admin/07-settings.md) §5.19–5.22

### 🔴 G11 เขียนไว้ผิดครึ่งหนึ่ง — แก้ข้อสรุปตั้งแต่ตอน audit

"ไม่มี audit log" **ไม่จริงสำหรับตาราง `academies`** — `Academy` ใช้ trait `Auditable` อยู่แล้ว
(`app/Models/Academy.php:17`) ⇒ ทุก `$academy->save()` เขียน `audit_logs` พร้อม before/after
ในฐาน dev มี **25,245 แถว** · entity `Academy` **144 แถว** ทั้งหมด `url=/api/academies/1/settings`
และเมนู #6 ผู้ปกครองไม่ได้ใช้ `AuditLogService` อย่างที่ G11 เขียน — ใช้ `MemberActivityLog`
ผ่าน `GuardianAuditLogger`

### สิ่งที่ปิดจริงในรอบนี้

| gap | เรื่อง | หลักฐาน |
|---|---|---|
| **G26** | `AcademySetting` ไม่ถูก audit เลย | flip `privacy` แล้ว save บน MySQL ⇒ `audit_logs` **delta 0** · และถ้าไม่แตะชื่อโรงเรียน `$academy->isDirty()`=false ⇒ ทั้งการกดบันทึกไม่มี log สักแถว |
| **G27** | endpoint อ่าน audit log ตอบ 500 ทั้งระบบ **9 route** | `user:id,first_name,last_name,avatar` แต่ `users` มีแค่ `name`/`profile_photo_path` · ยิงจริงได้ `500 Unknown column 'first_name'` · ผิด 5 จุดในไฟล์เดียว |
| **G28** | `GET /academies/{academy}/audit-logs` คืน `audit_logs` ทั้งแพลตฟอร์ม | middleware แค่ `auth:api` · controller ไม่แตะ `{academy}` เลย · ในตารางมี Student 5,768 · StudentCard 3,995 · WalletTransaction 505 พร้อม IP ⇒ **รูรั่วที่ถูกบั๊ก G27 บังไว้** ต้องแก้คู่กัน |

**ผลพลอยได้:** `SchoolAuditLogTab` บนหน้า `admin/classrooms` เรียก endpoint ที่ 500 อยู่
⇒ โชว์ "ไม่พบประวัติการแก้ไข" มาตลอดทั้งที่ในฐานมีข้อมูล — ตอนนี้ใช้ได้จริงแล้ว

### การตัดสินใจของเจ้าของโปรเจค (D19–D22)

- **D19** ประวัติการแก้ตั้งค่าเก็บที่ **`member_activity_logs`** (มี `academy_id` + มีหน้าจอที่ใช้งานได้จริง
  ที่ `/admin/activity-log`) ไม่ใช่ `audit_logs` ⇒ ไม่เป็น log ที่ไม่มีที่แสดง
- **D20** เก็บ **เฉพาะ diff ของช่องที่เปลี่ยน** · ไม่มีอะไรเปลี่ยน = ไม่เขียนแถว
- **D21** ซ่อม G27 + ปิด G28 ในชุดเดียวกัน · ลบ route `index` ที่ไม่มีผู้ใช้ทิ้ง · รัด `/entity`
- **D22** สิทธิ์อ่านประวัติการแก้ตั้งค่า = `settings.view` ขึ้นไป

### 🔴 กับดักของรอบนี้ (Claude แก้เอง 2 จุด — ดู §5.22)

1. **diff หลอกจาก "สตริงจากฟอร์ม" vs "int จากฐาน"** — `established_year` เป็น `smallint` และ
   ไม่มีใน `$casts` ⇒ `from DB: integer 2510` แต่ `after fill: string '2510'`
   ⇒ `PHANTOM DIFF KEYS: ["established_year"]` ทุกครั้งที่กดบันทึกโดยไม่แตะปี
   **แก้:** snapshot ฝั่ง "หลัง" อ่านกลับจากฐานด้วย `fresh()` + `normalize()` เรียงคีย์ array ทุกชั้น
2. **เมธอดเดียวถูกใช้จากสอง route** — `getEntityLogs` ถูกชี้จากทั้งเส้นโรงเรียนและ
   `api/admin/audit-logs/entity` · พอเติมพารามิเตอร์ `Academy $academy` เส้นแอดมินไม่มี `{academy}`
   ให้ผูก ⇒ Laravel สร้าง Academy เปล่า ⇒ **404 ทุกครั้ง**
   **แก้:** แยก `adminEntityLogs()` ให้เส้นแอดมิน · ยืนยันด้วย `route:list` ว่าชี้คนละเมธอด

### หลักฐานที่ Claude รันเอง (ไม่มีตัวเลขไหนที่เชื่อรายงาน agy)

- `php -l` 5 ไฟล์ · `pint --test` ผ่านทั้งชุด (รวม `routes/admin/admin.php`)
- **ยิงจริงบน MySQL:** เปลี่ยนเฉพาะ `privacy` ⇒ 1 แถว `diff keys = ["settings.privacy"]` ·
  กดซ้ำค่าเดิม ⇒ **0 แถว** · slogan+privacy ⇒ `["slogan","settings.privacy"]` ·
  `icon=mdi:cog-outline` `color=indigo` (สีที่หน้า activity-log map ได้จริง)
- **ล้างข้อมูลทดสอบครบ** — privacy=public · established_year=NULL · slogan เดิม · ลบ log ทดสอบ 3 แถว
- `route:list` — เส้น index หายจริง · `/entity` ติด `academy.permission:students.view` จริง
- eager-load 25,245 แถวด้วยคอลัมน์ใหม่ ⇒ โหลดผ่าน คืนชื่อผู้ใช้จริง
- เทสต์: `AcademySettingsAuditLogTest` **9 passed · 26 assertions** ·
  `tests/Feature/Academy` ทั้งโฟลเดอร์ **172 passed · 2 incomplete · 0 failed** (เดิม 163 + ใหม่ 9)
- **mutation check 5 แบบ** ⇒ **ล้มตรงเคสที่ควรล้มทั้ง 5** · คืนไฟล์ครบ diffstat เท่าเดิม รันซ้ำเขียว

### สิ่งที่ยังไม่ได้ตรวจ

- ยังไม่เปิดหน้า `/admin/activity-log` บนเบราว์เซอร์จริงเพื่อดูแถว `settings_update`
  (รอบนี้ไม่มีการแก้ markup — ฝั่ง UI แตะแค่ลบฟังก์ชันที่ไม่มีผู้เรียกออกจาก composable)
- `npm run build` — **ผู้ใช้รันเอง** (แตะ `ui/` 1 ไฟล์)

### งานที่ค้าง (TODO)

- [x] commit แล้ว 4 ชุด (`SET-S9/1`–`/3` + docs) — **ยังไม่ push**
- [x] **G29** — ปิดแล้ว (ดูหัวข้อ 2026-09-02 ต่อ) · เดิม: `MemberActivityLogController` ทั้ง 4 route มีแค่ `auth:api`
      และหน้า `admin/activity-log/index.vue` ไม่มี `definePageMeta` กัน ⇒ ใครล็อกอินก็อ่าน
      ประวัติกิจกรรมสมาชิกของโรงเรียนใดก็ได้ (มี `guardian_sensitive_view` อยู่ในนั้น) — ญาติของ G18
- [ ] **G18** (ยกมา) — `school-attendances` / `emergency-alerts` / `revenue/support-summary` / `my-role`
      ยังไม่มีด่านสมาชิกภาพและด่าน archived
- [ ] **หนี้ตรวจด้วยตา SET-S4** (ยกมา) · **UX `?view=archived` ของ SET-S2** (ยกมา → SET-S11)
- [ ] `PublicAcademyController.php` ตก `pint --test` (ของเดิม)
- [ ] **บั๊กนอกขอบเขต:** `/api/notifications/recent` 500 ทุกหน้า (`users.avatar` ไม่มีจริง) —
      **ต้นตอเดียวกับ G27 ที่เพิ่งปิด** เหลือแค่จุดนี้
- [ ] ตัวถัดไปตามลำดับ: S1→S3→S4→S5→S2→S8→S6→S7→**S9**→S11→S10 ⇒ **SET-S11** (UX เก็บตก G12)

### Branch / Git State

- Branch: `main` · Uncommitted: ไม่มี (clean)
- Push: **ยังไม่ push** — สะสม 6 commit ของ SET-S7 + 4 commit ของ SET-S9 บน `main`

---

## 2026-09-01 — เมนู #7 SET-S7: ฟิลด์อัตลักษณ์โรงเรียน + ล็อกชนิดของช่อง "ผู้อำนวยการ"

### สถานะ: **SET-S7 ✅ ตรวจครบทุกข้อ** — 9 ไฟล์ (แก้ 6 · ใหม่ 3) · **migrate บน dev แล้ว (ทั้ง up และ down)**

เอกสารหลัก: [`.agents/school-admin/07-settings.md`](school-admin/07-settings.md) §5.15–5.18

### สิ่งที่ปิด — 5 ฟิลด์ที่โชว์บนหน้าสาธารณะมาตลอดแต่แก้ไม่ได้เลย

| ฟิลด์ | แสดงที่ไหน | เดิมตั้งค่ายังไง | ตอนนี้ |
|---|---|---|---|
| `slogan` | การ์ดโรงเรียน · หน้าโปรไฟล์ · การ์ดคำเชิญ · ปุ่มแชร์ | เฉพาะตอน**สร้าง**โรงเรียน แล้วแก้ไม่ได้อีก | แท็บ "ข้อมูลทั่วไป" |
| `type` | 3 จุด + เป็น**ตัวกรอง**ในหน้า `/academies` | ไม่ได้เลย | select จากแคตตาล็อกปิด 4 ค่า |
| `established_year` | หน้าโรงเรียน 2 จุด | ไม่ได้เลย | ช่องตัวเลข **พ.ศ.** (2400–ปีปัจจุบัน) |
| `director` | การ์ด "ผู้อำนวยการ" 2 จุด | ไม่ได้เลย (ตั้งครั้งเดียวตอนสร้าง = คนสร้าง) | ช่องค้นหาสมาชิก APPROVED |
| `social_media_links` | **ไม่มีที่แสดงเลย** | ไม่ได้เลย | 6 ช่อง + แถวไอคอนบนหน้าโรงเรียน |

### G22 — ช่อง `director` มีสองนิยามในคอลัมน์เดียว (+ การแก้ข้อมูลที่ผมสรุปผิดตอน audit)

`AcademyResource:73` อ่าน `director` เป็น **user id** (`new UserResource(User::find(...))`) และหน้าโรงเรียน
ใช้เป็นอ็อบเจกต์ (`director.name/.avatar`) แต่คอลัมน์เป็น **varchar(255)** และ
`PUT /api/admin/academies/{id}` validate ว่า `'director' => nullable|string|max:255`
⇒ **แอดมินแพลตฟอร์มพิมพ์ "นายสมชาย" ลงไปได้** แล้วการ์ดผอ.หายเงียบ ๆ
+ `User::find('นายสมชาย')` คือ query สตริงเทียบ primary key int ที่ไม่มีวันเจอ

> ⚠️ **ผมสรุปผิดตอน audit แล้วแก้ระหว่างทาง:** ตอนแรกเขียนว่าเคสนี้ทำให้ `GET /api/academies/{name}`
> ตอบ **500** (TypeError จาก `method_exists(null, ...)`) — **ไม่จริง**
> TypeError นั้นเกิดเฉพาะตอนเรียก `(new UserResource(null))->toArray()` **ตรง ๆ** ใน tinker
> เมื่อซ้อนใน `AcademyResource` แล้วให้ framework serialize จริง Laravel แปลงเป็น `null` ให้เอง
> **ทดสอบซ้ำบน MySQL ด้วยโค้ดเวอร์ชันก่อนแก้** ทั้ง `'999999'` / `'ผอ.สมชาย'` / `''` ⇒ ปกติทั้งสามเคส
> และ **mutation check** ยืนยัน: ถอด null-guard ออก **ไม่มีเทสต์ไหนล้ม**
> ⇒ null-guard เป็นการกันไว้ ไม่ใช่การปิดช่อง 500 · บั๊กจริงคือสองประตูเขียนคนละชนิด ซึ่งปิดครบแล้ว

### การตัดสินใจของเจ้าของโปรเจค (D15–D18)

- **D15** `director` = เลือกจาก**สมาชิก APPROVED** (หรือเจ้าของโรงเรียนที่อาจไม่มีแถวสมาชิก)
  · ไม่ใช่ dropdown เพราะโรงเรียนเดียวมีสมาชิก 3,063 แถว (APPROVED 2,614) ⇒ ใช้ช่องค้นหา
- **D16** `established_year` เก็บเป็น **พ.ศ.** · หน้าเว็บแสดง "ก่อตั้ง พ.ศ. 2510 (1967)"
- **D17** `social_media_links` เป็น **JSON แคตตาล็อกปิด 6 ช่อง** + ต้องโชว์บนหน้าโรงเรียนด้วย
  (ตั้งค่าได้แต่ไม่มีที่แสดง = สวิตช์หลอกอีกตัว แบบเดียวกับที่ S5/S6 เพิ่งไล่ปิด)
- **D18** `approval_flow` **ลบทิ้ง** — dead column ไม่มีผู้อ่านทั้ง `app/` และ `ui/`

### 🔴 กับดักของรอบนี้ — "รูปโปรไฟล์" มีสองชื่อคีย์ในระบบเดียวกัน

สเปคสั่งให้ช่องค้นหาผอ.อ่าน `m.user.avatar` (เพราะการ์ดผอ.ใช้ `academy.director.avatar`)
แต่ `/members/search` ใช้ `AcademyMemberResource` ซึ่งส่ง **`profile_photo_url` เท่านั้น ไม่มีคีย์ `avatar`**
(ต่างจาก `UserResource` ที่ส่งทั้งคู่) ⇒ ถ้าปล่อยไว้ รายการค้นหาจะขึ้นไอคอนสำรองตลอด
**Claude แก้เอง** หลังยิง endpoint จริงแล้วพิมพ์ `array_keys($first['user'])` ออกมาดู

**ของแถม (ไม่ใช่ของ S7):** `GET /api/notifications/recent` ตอบ **500 ทุกครั้งที่โหลดหน้าไหนก็ตาม**
— `Unknown column 'avatar' in 'field list'` (`select id, name, avatar from users`) **ต้นตอเดียวกันเป๊ะ**
⇒ เปิดเป็นงานแยกไว้แล้ว

### หลักฐานที่ Claude รันเอง (ไม่มีตัวเลขไหนที่เชื่อรายงาน agy)

- `php -l` 5 ไฟล์ · `pint --test` ผ่าน · SFC 2 ไฟล์คอมไพล์ผ่าน
- **migration บน MySQL จริง:** `migrate` → `social_media_links` เป็น `json` · `approval_flow` หาย
  → `migrate:rollback --step=2` → คืนเป็น `varchar(255)` + `approval_flow varchar(191) default 'single'`
  → `migrate` อีกรอบ ⇒ **down() ใช้ได้จริง ไม่ใช่ down() หลอก**
- **ยิง API จริงบน MySQL** (dispatch ผ่าน HTTP kernel พร้อม JWT ของเจ้าของ): เขียนครบ 5 ฟิลด์ 200 ·
  `type` นอกแคตตาล็อก 422 · ปี ค.ศ. 1967 → 422 · director ที่ไม่มีในระบบ/สมาชิกที่ยังไม่ APPROVED → 422 ·
  director APPROVED → 200 · คีย์โซเชียลนอกแคตตาล็อก 422 · ค่าที่ไม่ใช่ URL 422 · ล้างทุกช่อง ⇒ เก็บ `[]`
- **พิสูจน์ G22 ทั้งก่อนและหลังแก้:** ตั้ง `director` เป็น `'999999'` / `'ผอ.สมชาย'` / `''`
  แล้ว serialize resource ผ่าน HTTP จริง ⇒ **ทั้งสองเวอร์ชันได้ `director: null` ไม่ throw**
  (นี่คือหลักฐานที่ทำให้ต้องแก้ข้อสรุปเรื่อง 500 — ดูกรอบเตือนด้านบน)
- **เบราว์เซอร์จริงที่ 375px** (ทั้งหน้าโรงเรียนและหน้าตั้งค่า):
  · About card ขึ้น "ก่อตั้ง พ.ศ. 2510 (1967)" + หัวข้อ "ผู้อำนวยการ" ภาษาไทย
  · ไอคอนโซเชียล 3 อัน ขนาด **44×44** · `rel="noopener noreferrer"` · `target="_blank"`
  · การ์ดทั้งสองชุด (มือถือ + aside) render ครบ · `document.scrollWidth === 375` ไม่มีล้นแนวนอน
  · หน้าตั้งค่า: select 5 ตัวเลือก · ปี min=2400 max=2569 · 6 ช่องโซเชียล · ชิปผอ.ปุ่มลบสูง 44px
  · **ค้นหาผอ.จริง** พิมพ์ "นายอ" ⇒ 10 ผลลัพธ์ แถวสูง 60px กดเลือกแล้วชิปเปลี่ยนจริง
  · **กดบันทึกผ่านฟอร์มจริง (multipart)** ⇒ "สำเร็จ" และฐานได้ครบทั้ง slogan/ปี/tiktok ที่เพิ่งกรอก
  · **ล้างข้อมูลทดสอบครบ** — คืน slogan เดิม · type/ปี/social เป็น NULL · director กลับเป็น `'1'`
- **mutation check 5 แบบ** — ถอด `Rule::in` ของ type · เปลี่ยนขอบปีเป็น ค.ศ. · ถอด closure กันคีย์โซเชียล ·
  ถอดกฎสมาชิกภาพของ director ⇒ **ล้มตรงเคสที่ควรล้มทั้ง 4** · ส่วนการถอด null-guard ของ director
  **ไม่ทำให้เทสต์ไหนล้ม** ⇒ บันทึกไว้ตามจริง (ดูกรอบเตือน G22) · คืนไฟล์ครบ diffstat กลับมาเท่าเดิม
- เทสต์: `AcademyIdentityFieldsTest` **11 passed · 49 assertions** · `tests/Feature/Academy` ทั้งโฟลเดอร์ **163 passed · 2 incomplete · 0 failed** (เดิม 152 + ใหม่ 11)

### สิ่งที่ยังไม่ได้ตรวจ

- หน้าโรงเรียนตรวจที่ 375px จุดเดียว (768/1280px ตรวจเฉพาะหน้าตั้งค่า — ผ่านทั้งคู่ ไม่มีล้นแนวนอน)
- ไม่ได้ทดสอบ `PUT /api/admin/academies/{id}` ของจริง (แก้กฎ validate ให้แล้ว แต่ยังไม่มีเทสต์คุม)
- `npm run build` — **ผู้ใช้รันเอง** (แตะ `ui/` 2 ไฟล์)

### งานที่ค้าง (TODO)

- [x] commit แล้ว 6 ชุด (`SET-S7/1`–`/5` + docs) — **ยังไม่ push**
- [ ] **G18** (ยกมา) — `school-attendances` / `emergency-alerts` / `revenue/support-summary` / `my-role`
      ยังไม่มีด่านสมาชิกภาพและด่าน archived
- [ ] **หนี้ตรวจด้วยตา SET-S4** (ยกมา) · **UX `?view=archived` ของ SET-S2** (ยกมา → SET-S11)
- [ ] `PublicAcademyController.php` ตก `pint --test` (ของเดิม ไม่ได้แตะรอบนี้)
- [ ] **บั๊กใหม่นอกขอบเขต:** `/api/notifications/recent` 500 ทุกหน้า (`users.avatar` ไม่มีจริง)
- [ ] ตัวถัดไปตามลำดับ: S1→S3→S4→S5→S2→S8→S6→**S7**→S9→S11→S10 ⇒ **SET-S9** (audit log การแก้ตั้งค่า)

### Branch / Git State

- Branch: `main` · Uncommitted: ไม่มี (clean)
- Push: **ยังไม่ push** — สะสม 6 commit ของ SET-S7 บน `main`

---

## 📦 บันทึกช่วงก่อน 2026-09 (มิ.ย.–ส.ค. 2026) — ย้ายไป archive แล้ว

entry ทั้งหมดของช่วง **2026-06 → 2026-08-31** (Inertia cleanup · ระบบผู้ปกครอง G-S · กีฬาสี S-S · เลือกตั้ง · ตั้งค่าโรงเรียน SET-S · เมนู #6/#7/#9 ฯลฯ) ย้ายไป [`worklog-archive-before-2026-09.md`](worklog-archive-before-2026-09.md) แล้ว — เนื้อหาเดิมครบ · งานโค้ดเข้า main หมดแล้วตาม §สถานะ git ด้านบน

🔸 **งานค้าง owner-gated ที่บันทึกไว้ช่วงนั้น (ณ ส.ค. · ยืนยันสถานะปัจจุบันจากที่นี่ไม่ได้):**
- prod ยังไม่ได้รัน migration ของระบบผู้ปกครอง/ตั้งค่าหลายตัว (drop `student_guardians` · drop `name_slug` · `archived_at` · `guardian_account_requests` ฯลฯ) — ก่อนรันให้ `mysqldump` ก่อนเสมอ
- `php artisan guardians:backfill --force` ยังไม่รันบน prod (dry-run คาดได้ guardians ~4,504 · links ~4,999) — รายละเอียดเต็มดูในไฟล์ archive

---

## 2026-09-07 — ดาวน์โหลดข้อสอบ + รื้อ layout คอลัมน์เนื้อหา

### งานที่ทำ
- **ดาวน์โหลดข้อสอบเป็นไฟล์ .xlsx** (ทั้งข้อสอบประจำบทเรียนและข้อสอบในรายวิชา) — คู่กับฟีเจอร์อัปโหลดที่มีอยู่แล้ว
  - `app/Services/Export/QuestionExportService.php` + `QuestionExportController.php` (ไฟล์ใหม่)
  - หัวคอลัมน์ดึงจาก `QuestionImportService::HEADERS` ตัวเดียวกับแบบฟอร์มอัปโหลด กันสองฝั่ง drift
  - route: `GET /lessons/{lesson}/questions/export` และ `GET /courses/{course}/quizzes/{quiz}/questions/export`
    ทั้งคู่ต้องประกาศ **ก่อน** `Route::resource(...questions)` ไม่งั้นโดน `questions/{question}` กิน
  - ฝั่ง UI: ปุ่ม "ดาวน์โหลดข้อสอบ" ใน `LessonQuizSection.vue` และหน้าแก้ไขแบบทดสอบ
- **แก้บั๊กชื่อไฟล์ตอนดาวน์โหลด (มีมาก่อนหน้านี้ กระทบใบประกาศฯ + รายงานรายวิชาด้วย)**
  - `config/cors.php` ไม่ได้ expose `Content-Disposition` เบราว์เซอร์จึงอ่าน header ข้าม origin ไม่ได้
    ไฟล์ที่ดาวน์โหลดทุกไฟล์เลยชื่อ `download` ไม่มีนามสกุล
  - `useApi.getBlob` หยิบ `filename=` ตัวแรกซึ่งเป็น ASCII fallback ที่ `Str::ascii()` โยนตัวอักษรไทยทิ้งหมด
    เปลี่ยนเป็นอ่าน `filename*=` (RFC 5987) ก่อน
- **การ์ดผลการสอบของนักเรียน** — มือถือแสดงเป็นรายการแทนตาราง (ความสูงต่อคน 353px -> 88px),
  ลบกล่องเทาและ `bg-white` บน `<tr>` ที่ทำให้เห็นเป็นการ์ดซ้อนการ์ด 3 ชั้น,
  ย้าย `flex` ออกจาก `<td>` ที่ทำให้คอลัมน์คำนวณความกว้างพลาด, วันที่เป็นตัวเลข `13/08/2569 08:53`
- **รื้อ layout: เลื่อน rail ขวาจาก xl ไป 2xl** (`ui/layouts/main.vue`)
  - ที่จอ 1280px กริดกว้างแค่ 888px คอลัมน์กลาง 6 ช่องจึงเหลือ 432px — แคบกว่าตอนจอ 768px ที่ได้ 630px
  - ต้องเลื่อนพร้อมกันครบ 9 จุด ไม่งั้นผลรวมคอลัมน์เกิน 12 แล้วแถวตกบรรทัด
  - ผลข้างเคียงที่เจ้าของโปรเจครับทราบแล้ว: widget สนับสนุน/โฆษณาใน rail ขวา
    กลายเป็นแผงเลื่อนออกที่ต้องกดเปิดบนจอ 1280-1535px

### Verification (ตรวจเองทั้งหมด ไม่ได้ใช้ตัวเลขจากรายงาน agy)
- round-trip: export -> `parse()` + `validateRows()` ตัวจริง = 0 error ทั้ง lesson และ quiz
- 30 ข้อที่ไม่มี `is_correct` เลย -> fallback `correct_option_id` ได้เฉลยครบทุกข้อ
- ไม่ strip HTML: คำถามที่มี `<!DOCTYPE html>` เป็นเนื้อหาจริงยังครบทุกตัวอักษร
- สิทธิ์: เจ้าของ 200 · นักเรียน 403 · แบบทดสอบที่ไม่มีข้อสอบ 422
- ชื่อไฟล์ที่ดาวน์โหลดจริงในเบราว์เซอร์: `ความรู้พื้นฐานการใช้งานคอมพิวเตอร์-ข้อสอบ-20260907.xlsx`
- ตารางผลการสอบลงพอดีที่ 640 / 1024 / 1280 / 1440 · ที่ 1536 ยังล้น 33px (เดิม 170px)
- กริดยังเป็นแถวเดียวทุกหน้าที่ใช้ rail ขวา (Dashboard, Newsfeed, รายการโรงเรียน, รายการรายวิชา)
- เปิด/ปิดแผงขวาที่ 1280 ทำงานครบ widget แสดง 4/4 ตัว
- `pint --test` passed · SFC/template/script compile ผ่านทุกไฟล์

### ค้างไว้
- ที่จอ 1536px ตารางผลการสอบยังเลื่อนแนวนอน 33px เพราะ rail ขวากินไป 268px
  ถ้าจะปิดช่องนี้ต้องลด `min-w-[9rem]` ของคอลัมน์ชื่อ ซึ่งจะทำให้ชื่อไทยแตกหลายบรรทัด — ยังไม่ทำ
- `npm run build` เจ้าของโปรเจครันเอง

---

## 2026-09-07 (ต่อ) — เมนู #8 ระบบบริหารโรงเรียน: ขั้น [1] สแกน + [2] เขียนไฟล์รอง

### สถานะ: 📄 audit อย่างเดียว — ยังไม่แตะโค้ดโปรดักชันสักบรรทัด

เมนู #7 ปิดครบไปแล้ว (2026-09-02) คิวถัดไปตาม OVERVIEW คือ **#8 ระบบบริหารโรงเรียน**
รอบนี้ทำแค่ step [1] + [2] ของ loop: สแกนโค้ดจริง แล้วเขียน `.agents/school-admin/08-school-management.md`

### 🔴 สิ่งที่เจอและพิสูจน์แล้ว — P0 ใหญ่กว่า G1 ของเมนู #7

เมนูนี้ไม่ใช่หน้าเดียว แต่เป็น ERP ทั้งก้อน: **204 route · 19 controller · 30+ ตาราง · UI 4,128 บรรทัด**

**203 จาก 204 route มีแค่ `auth:api` ไม่มีด่านสิทธิ์อะไรเลย**
ยิงเทสต์จริงด้วย user ที่ **ไม่ได้เป็นสมาชิกโรงเรียนนั้น** → ได้ `200` ครบ 20 เส้นที่ลอง
(เงินเดือน · ใบแจ้งหนี้ค่าเทอม · รายจ่าย · งบประมาณ · แฟ้มบุคลากร · ลงเวลา · ใบลา · รายงาน · KPI)
ฝั่งเขียนก็ผ่าน authorization เหมือนกัน — ที่ไม่สำเร็จเพราะติด validation/schema ไม่ใช่เพราะสิทธิ์

**ยังไม่มีข้อมูลจริงรั่ว** เพราะทุกตารางมีแต่ seed (tuition_fees 0 · payments 0 · payrolls 3)
แต่ช่องโหว่เปิดค้างรออยู่ ถ้าโรงเรียนเริ่มกรอกเงินเดือนวันไหน ข้อมูลนั้นเปิดทันที

**ข่าวดี:** คีย์สิทธิ์มีครบอยู่แล้วใน `AcademyPermission` (123 คีย์ — `finance.*`, `staff.*`,
`reports.*`, `announcements.*`, `schedule.*`) และ `CheckAcademyPermission` ก็พร้อมใช้
⇒ SM-S1 คือใส่ middleware ที่ระดับ group เป็นหลัก ไม่ต้องสร้างอะไรใหม่

### บั๊กอื่นที่เจอระหว่างสแกน (ยังไม่แก้ บันทึกไว้เป็น G5–G10)

- UI เรียกฟังก์ชันที่ **ไม่มีอยู่ใน composable** 4 จุด → TypeError ตอนกดปุ่ม
  (`getStaffProfiles` `createStaffProfile` `processPayroll` `bookMeetingSlot`)
- endpoint ชี้ผิด path/verb 4 เส้น (`updateSubject` PATCH vs PUT · `getSemesters` ไม่มี GET ·
  `getLeaveTypes` ผิด prefix · `generateReport` ผิด path)
- **500 ถาวรทุกคน 3 เส้น** ยืนยันบน MySQL dev ไม่ใช่แค่ sqlite:
  `ExpenseController@summary` ใช้ `category_id` แต่คอลัมน์จริงชื่อ `expense_category_id` ·
  `storeCategory` เขียน `display_order` ที่ไม่มีในตาราง ·
  `createKpi` ปล่อย `calculation` ว่างทั้งที่คอลัมน์เป็น NOT NULL
- `components/school/SchoolManagement.vue` เป็น **orphan** ⇒ แท็บแต้มรางวัล/ห้องสมุด/ทรัพย์สิน
  (791 บรรทัด) เขียนเสร็จแล้วแต่ **ไม่มีทางกดถึงจากหน้าไหนในแอป**
- การ์ด "นักเรียน" บนหัวหน้าเอาเลขสมาชิกที่อนุมัติแล้วมาโชว์ · การ์ด "ครู/อาจารย์" ค้าง 0 ตลอด
- แท็บไม่ผูกกับ URL — refresh แล้วเด้งกลับแท็บแรกเสมอ

### กับดักที่บันทึกไว้ในไฟล์รอง (อ่านก่อนลงมือ SM-S1)

- ชื่อตารางไม่ตรงกับที่เดา: `announcements`→`school_announcements` · `kpis`→`kpi_definitions` ·
  `assets`→`school_assets` · `fee_structure_items`→`fee_items`
- `authorizeStaff()` / `authorizePayroll()` / `authorizeLeave()` / `authorizeAttendance()`
  **ชื่อหลอก** — ข้างในเช็คแค่ `academy_id` ตรงกันแล้ว `abort(404)` ไม่ได้เช็คสิทธิ์ผู้เรียกเลย
- ห้ามใส่ `staff.manage` / `finance.manage` ทื่อ ๆ ทุกเส้น มี 5 เคสยกเว้นที่จะพังทันที
  (ครูยื่นใบลาเอง · ผู้ปกครองจองนัดพบ · บุคลากรลงเวลาเอง · dashboard layout เป็นของรายคน · อ่านประกาศ)
- `useSchoolManagement.ts` ถูกใช้โดยอีก **8 หน้านอกเมนูนี้** — แก้ทีต้องเช็คทั้ง 8

### วิธีที่ใช้ตรวจ (ทำเองทั้งหมด ไม่ได้ delegate)

- `php artisan route:list --json` แล้ว diff กับรายการ `api.*()` ในคอมโพสเซเบิลทั้งไฟล์
- เทสต์ชั่วคราว `TempMenu8ExposureTest` 3 เคส → ยืนยัน G1 แล้ว **ลบไฟล์ทิ้ง** (working tree สะอาด)
- นับแถวจริงบน MySQL dev + `SHOW COLUMNS` ยืนยันว่า G8 ทั้ง 3 ข้อไม่ใช่อาการเฉพาะ sqlite

### งานที่ค้าง (TODO ต่อ)

- [ ] 🔴 **SM-S1** — ใส่ด่านสิทธิ์ให้ครบ 204 route + เทสต์ non-member 403 · **ทำได้ทันที ไม่ต้องรอเคาะ**
- [ ] SM-S3 (ชื่อฟังก์ชัน/path ผิด 8 จุด) · SM-S4 (500 ถาวร 3 เส้น) · SM-S6 (สถิติ + `?tab=`)
      — เป็นบั๊กชัด ไม่ขึ้นกับคำตอบ Q1–Q4 เช่นกัน
- [ ] ❓ รอเจ้าของโปรเจคเคาะ **Q1** เมนู #8 คือ hub หรือบ้านของ 5 โมดูล · **Q2** 3 แท็บที่เข้าไม่ถึงเอาไงต่อ ·
      **Q3** โมดูลการเงินจะใช้จริงเมื่อไหร่ (คำตอบเปลี่ยนความละเอียดของ SM-S2) · **Q4** ผูกกับ wallet ไหม
- [ ] หนี้จาก entry ก่อนหน้า: จอ 1536px ตารางผลการสอบยังเลื่อนแนวนอน 33px (ยังไม่ทำโดยตั้งใจ)

### 🅿️ ข้อตกลงปิดท้ายรอบนี้ (2026-09-07)

เจ้าของโปรเจคสั่ง **ยังไม่เริ่ม SM-S1 ในรอบนี้** — จบที่ audit ก่อน แล้วค่อยตัดสินใจทีหลัง
เมื่อถึงเวลาลงมือ: **ส่งงานเขียนโค้ดให้ `agy`** (Claude เขียนสเปค + แตก shard + ตรวจ diff/รันเกณฑ์เอง)

---

## 2026-09-09 — เมนู #8: SM-S1 ปิดช่องโหว่สิทธิ์ 204 route (G1 + G2)

### สถานะ: ✅ ตรวจครบแล้ว ยังไม่ commit (รอเจ้าของโปรเจคเคาะ)

ต่อจาก audit 2026-09-07 ที่พบว่า **203 จาก 204 route ของเมนูนี้มีแค่ `auth:api`**
รอบนี้ลงมือปิดจริง · **ผู้เขียนโค้ดคือ agy** (2 shard) · Claude เขียนสเปค + ตรวจผลเอง

### สิ่งที่แก้

- `routes/learn/academy.php` — 95 บรรทัด (แก้บรรทัดเดิมล้วน ไม่มีของหาย: +95/−95)
  ทุกกลุ่มของเมนูนี้ได้ `academy.visibility:content` (ปิด G2) + `academy.permission[:key]` (ปิด G1)
- เทสต์ใหม่ `tests/Feature/SchoolManagementRouteGuardTest.php` — 8 เคส 59 assertions

### 🔴 กับดักที่เจอกลางทาง — อย่าลืมเมื่อทำ SM-S2

**middleware ของ group กับของ route มัน "ซ้อนกัน" ไม่ใช่ "แทนที่กัน"**
shard A แรกใส่ `academy.permission:staff.view` ที่ระดับกลุ่ม แล้วใส่ `academy.permission` เปล่า
ที่เส้น check-in หวังว่าจะผ่อนให้ครูลงเวลาได้ — **ไม่ได้ผล** เพราะ `route:list -v` โชว์ว่า
เส้นนั้นวิ่งผ่านทั้ง `staff.view` แล้วค่อยถึงตัวเปล่า ⇒ ครูยังโดนบล็อกเหมือนเดิม

⇒ **กลุ่มที่มีข้อยกเว้น ห้ามใส่คีย์ที่ระดับกลุ่ม** ให้เหลือแค่ `academy.visibility:content`
แล้วย้ายคีย์ไปแขวนรายเส้น (แบบเดียวกับกลุ่ม `{academy}/school-attendances` ที่ทำไว้ถูกอยู่แล้ว)
shard A2 แก้ 4 กลุ่มนี้: `staff-attendance` · `leave-requests` · `analytics` · `dashboard`

### สรุปด่านที่ได้ (นับจาก `route:list --json` จริง)

| ระดับ | จำนวน | ตัวอย่าง |
|---|---:|---|
| ไม่มีด่าน | **0** (เดิม 203) | — |
| ไม่มี `academy.visibility` | **0** | — |
| ด่านสมาชิกเปล่า (ตั้งใจ) | 38 | ลงเวลา · ยื่น/ยกเลิกใบลา · dashboard ครู-นักเรียน · layout ส่วนตัว · นัดพบผู้ปกครอง · อ่านประกาศ/ตารางสอน/รายวิชา |
| คีย์ `finance.view` | กลุ่ม fee-structures · tuition-fees · payments · expenses · budgets |
| คีย์ `staff.view` | staff · payroll + รายเส้นใน staff-attendance/leave-requests |
| คีย์ `reports.view` | reports + analytics (ยกเว้น 4 เส้น) |
| คีย์ `settings.manage` | dashboard/widgets · library · assets |
| คีย์ `*.manage` รายเส้น | academic-years/subjects (`courses.manage`) · schedules (`schedule.manage`) · announcements (`announcements.manage`) |

### หลักฐานที่ Claude รันเอง (ไม่ได้ลอกจากรายงาน agy)

- สคริปต์นับจาก `php artisan route:list --json`: **204 เส้นในขอบเขต · unguarded 0 · ไม่มี visibility 0**
- `./vendor/bin/pint --test` → passed
- `php artisan test --filter=SchoolManagementRouteGuardTest` → **8 passed (59 assertions)**
- **revert-check**: `git checkout --` เฉพาะไฟล์ route แล้วรันใหม่ → **แดง 4 เคส** จากนั้น restore → เขียว 8/8
- ชุดข้างเคียงไม่พัง: `AcademyScopeFilteringTest|ParentDashboardSectionsTest|TargetAudienceNormalizationTest`
  → 15 passed

### บั๊กของ agy ที่ Claude แก้เอง (1 จุด)

เทสต์เคสโรงเรียนเก็บถาวรใช้ `$academy->update(['archived_at' => now()])` ซึ่ง **ไม่มีผล**
เพราะ `archived_at` ไม่ได้อยู่ใน `$fillable` ของ `Academy` (อยู่ใน `$casts` อย่างเดียว)
⇒ เทสต์ได้ 200 แล้วฟ้องว่าด่านพัง ทั้งที่ด่านถูก · แก้เป็น set property ตรง ๆ แล้ว `save()`
เหมือนที่ `AcademyController::archive()` ทำ

### ⚠️ ค้าง / ต้องรู้ก่อนทำต่อ

- **ยังไม่ได้ทดสอบบนหน้าจอจริง** — ควรเปิดหน้า `admin/school-management` ด้วยบัญชี admin
  แล้วเช็คว่า 6 แท็บยังโหลดครบ (admin/owner ผ่าน `isAdmin()` อยู่แล้วจึงคาดว่าไม่กระทบ)
  และเปิด `dashboard/teacher`, `dashboard/student`, `parent/meetings` ด้วยบัญชีที่ไม่ใช่ admin
- **`tests/Api/` ไม่ได้อยู่ใน testsuite ของ `phpunit.xml`** ⇒ `tests/Api/SchoolManagementApiTest.php`
  **ไม่เคยถูกรันเลย** (เจอระหว่างทาง ไม่ได้แก้ในรอบนี้)
- SM-S2 (แยก view/manage ตาม matrix §4) ยังติด **Q3** · SM-S3/S4/S6 ทำได้ทันทีไม่ต้องรอใคร
- `at-risk` ตอนนี้ต้องมีคีย์ `students.view` — ถ้าโรงเรียนให้ครูที่ปรึกษาดูหน้านี้ ต้องแจกคีย์นี้ให้ role ครู

---

## 2026-09-09 (ต่อ) — เมนู #8: SM-S3 แก้ UI เรียกฟังก์ชัน/path ผิด (G5+G6) 7/8

### สถานะ: ✅ 7 จุดเสร็จ+ตรวจแล้ว · จุดที่ 8 กันไว้รอเคาะ · ผู้เขียนโค้ด agy

ไฟล์: `ui/composables/useSchoolManagement.ts` · `ui/components/school/SchoolStaffTab.vue`

- G5 (ฟังก์ชันไม่มีจริง→TypeError): getStaffProfiles→getStaffList · createStaffProfile→createStaff ·
  processPayroll→bulkGeneratePayroll (เพิ่มเมธอดใหม่ยิง POST /payroll/bulk-generate + pay_period 'Y-m')
- G6 (path/verb ผิด): updateSubject PATCH→PUT · getLeaveTypes →/leave-requests/leave-types ·
  generateReport →POST /reports/generate body {definition_id,name,parameters} ·
  getSemesters (ไม่มี GET route) → ดึงจาก academic-years index คืน {data:[]}

### 🅿️ จุดที่ 8 `bookMeetingSlot` — กันไว้ ไม่ทำในรอบนี้
endpoint `POST /meetings/slots/{slot}/book` เป็นของ **ผู้ปกครอง** (ตั้ง parent_id=ผู้เรียก,
บังคับ student_id+purpose) แต่ปุ่มอยู่ในแท็บสื่อสารของ admin ส่งแค่ slot.id
⇒ เปลี่ยนชื่อเฉย ๆ จะกลายเป็น 422 · เป็นปัญหา "วางฟีเจอร์ผิดที่" ไม่ใช่ rename
รวมไปตัดสินที่ SM-S7 — **ความเห็น Claude: เอาปุ่มจองออกจากแท็บ admin** (admin ควรแค่สร้าง/ลบ slot)

### หลักฐานที่ Claude รันเอง
grep ชื่อเก่า 0 นัด · bulkGeneratePayroll มีทั้งนิยาม+return · SFC compile OK · tsc composable clean
· template ไม่ถูกแตะ · **ยังไม่ตรวจบนจอจริง**

### คิวถัดไปเมนู #8: SM-S4 (3 endpoint 500 ถาวร — G8) → SM-S6 · ส่วน S2/S5/S7/S9 รอ Q1–Q3

---

## 2026-09-09 (ต่อ) — เมนู #8: SM-S4 แก้ G8 endpoint พังถาวร (Expense ทั้งโมดูล + KPI)

### สถานะ: ✅ เสร็จ+ตรวจ+revert-check แล้ว · ผู้เขียนโค้ด agy · ไม่ต้อง migration

**ใหญ่กว่า audit มาก** — audit เขียนว่า "3 endpoint 500" แต่จริง ๆ ทั้งโมดูล Expense
(model+controller) ถูกเขียนคนละ schema กับตารางจริง ⇒ store/update/show/summary พังหมด
เจ้าของโปรเจคเคาะ: **ปรับ backend ให้ตรง schema จริง + ตัดฟีเจอร์ไม่มีคอลัมน์ทิ้ง**

ไฟล์: Expense.php · ExpenseController.php · AnalyticsController.php(createKpi) · เทสต์ใหม่ 4 เคส
- คอลัมน์: category_id→expense_category_id · vendor_name→vendor · receipt_number→reference_number ·
  created_by→requested_by · notes→approval_notes · ตัด budget_id/expense_number/payment_method
- G8.2 ตัด display_order (storeCategory/updateCategory)
- G8.3 createKpi 2 บั๊ก: calculation NOT NULL default [] · auditLog->log() ส่ง class-string→TypeError
  (agy เจอเองแล้วแก้เป็น logCustom — Claude ยืนยัน signature ตรง)

### 🔴 บทเรียน: finance module มี schema-drift แบบเดียวกันทั้งหมวด
Expense เป็นแค่ตัวแรกที่แตะ · Payment/TuitionFee/Budget/Payroll controller อาจมี drift แบบเดียวกัน
ถ้าจะเปิดใช้ finance ต้องไล่ audit ทุก controller เทียบ migration ก่อน (ผูกกับ Q3)

### หลักฐาน Claude รันเอง
pint passed · เทสต์ 4/4 (11 assertions) · revert-check stash→แดง4 restore→เขียว · guard 8/8 ยังผ่าน
Claude ลบไฟล์ขยะ kpi_error.txt ที่ agy ทิ้ง + format เทสต์ · ยังไม่ตรวจบนจอจริง

### คิวถัดไปเมนู #8: SM-S6 (สถิติหัวหน้าหน้า G9 + ผูกแท็บ ?tab= G10) · S2/S5/S7/S9 รอ Q1–Q3

---

## 2026-09-09 (ต่อ) — เมนู #8: SM-S6 สถิติหัวหน้าหน้า (G9) + ผูกแท็บ ?tab= (G10)

### สถานะ: ✅ เสร็จ+ตรวจ · ผู้เขียนโค้ด agy · frontend ไฟล์เดียว

ไฟล์: `ui/pages/academies/[name]/admin/school-management.vue`
- G9: fetchStats เดิมเอา stats.approved ไปใส่การ์ด "นักเรียน" ผิด + totalTeachers/totalStaff ไม่เคยเซ็ต
  → ดึงจาก role_distribution.{student,teacher,staff} ที่ members/stats คืนมาอยู่แล้ว (ไม่แตะ backend)
- G10: activeTab ref('members') → computed get/set ผูก ?tab= (แพทเทิร์นเดียวกับ elections/[id].vue)

หมายเหตุ: audit เขียนว่า "เมนู #7 แก้เรื่องนี้ด้วย ?view=" — จริง ๆ #7 ไม่ได้ผูกแท็บกับ URL
(?view= เป็นของลิงก์ไป archived list) แพทเทิร์นที่มีจริงอยู่ที่ elections/[id].vue + departments/[id].vue

### หลักฐาน Claude รันเอง
grep role_distribution map + computed แทน ref (ref=0) + useRouter=1 · SFC compile OK · template ไม่แตะ
ยังไม่ตรวจบนจอจริง

### สถานะเมนู #8 หลัง SM-S6
✅ ปิดแล้ว: S1 (สิทธิ์ 204 route) · S3 7/8 · S4 (Expense schema + KPI) · S6 (สถิติ+แท็บ)
🟢 ทำได้ต่อ (ไม่ติด Q): **S8** (audit log finance/payroll — dep S1 ✅) · **S10** (ชุดเทสต์ — dep S1–S4 ✅)
🔵 ยังติด Q1–Q3: S2 (แยก view/manage) · S5 (โหมด view-only) · S7 (ชะตา 4 ไฟล์ orphan + bookMeeting) · S9 (โครงเมนู)

---

## 2026-09-09 (ต่อ) — เมนู #8: SM-S8 แก้ audit log การเงินที่เรียกพัง (G11)

### สถานะ: ✅ เสร็จ+ตรวจ+revert-check · ผู้เขียนโค้ด agy

**G11 เขียนผิด** — บอก "การเงินไม่มี audit" แต่จริงคือ **มีครบแต่เรียกพังทั้ง 6 controller** (~30 call)
พิสูจน์ด้วย tinker: ส่ง request() เป็น ?string module → __toString() dump ลง varchar(50) → "Data too long" 500

ไฟล์: Expense/Payroll/TuitionFee/Budget/FeeStructure/PaymentController + เทสต์ใหม่ SchoolFinanceAuditLogTest
- RULE A (convenience ส่ง request()) → ลบ request() · reject ย้าย reason มา arg3
- RULE B (Payroll log() positional ผิด) → named args
- RULE C (TuitionFee/Budget log() named `request:` ไม่มีจริง) → ลบ + module:'finance'

### 🔴 Analytics ยังพังแบบเดียวกัน (นอกขอบเขต SM-S8)
`AnalyticsController` log() 464/495/637/711/780 ส่ง class-string เป็น ?Model + int เป็น ?array → TypeError
(SM-S4 แก้ไปแล้ว 1 จุด = 396 createKpi→logCustom) เหลืออีก 5 จุด — เก็บตกตอนแตะเมนูรายงาน หรือทำ quick fix

### หลักฐาน Claude รันเอง
grep request() ค้าง 0 · pint passed · เทสต์ 2/2 (27 assertions) · revert-check stash→แดง restore→เขียว
SM-S4 เทสต์ยังผ่านคู่ (6/6) · ยังไม่ตรวจบนจอจริง

### สถานะเมนู #8: ปิด S1/S3(7·8)/S4/S6/S8 · เหลือ **S10 (ชุดเทสต์)** ที่ไม่ติด Q · S2/S5/S7/S9 รอ Q1–Q3

---

## 2026-09-09 (ต่อ) — เมนู #8: SM-S10 ชุดเทสต์ happy-path ต่อโมดูล (G12)

### สถานะ: ✅ เสร็จ · ผู้เขียนโค้ด agy · Claude probe schema เลือกขอบเขต

ไฟล์ใหม่ `tests/Feature/SchoolAcademicModulesTest.php` (4 เคส) — Subject CRUD + AcademicYear+semesters
+ permission-per-route (plain member สร้างไม่ได้ 403)

**เลือกเฉพาะโมดูลวิชาการที่สะอาด** (Subject/AcademicYear) หลัง probe: Budget/Payment/TuitionFee/FeeStructure
มี schema-drift แบบ Expense (Budget store เขียน category_id/status ที่ตารางไม่มี) → happy-path ยังเขียนไม่ได้
จนกว่าจะ reconcile ทั้งหมวดตอน finance buildout (Q3)

รวมเมนู #8 มี 4 ไฟล์เทสต์ 18 เคส 114 assertions

### 🎯 เมนู #8: ปิดครบทุก step ที่ไม่ติด Q แล้ว
✅ S1 (สิทธิ์ 204 route) · S3 7/8 · S4 (Expense schema+KPI) · S6 (สถิติ+แท็บ) · S8 (audit) · S10 (เทสต์)
🔵 เหลือรอ **Q1–Q3** จากเจ้าของโปรเจค: S2 (แยก view/manage) · S5 (view-only UI) ·
   S7 (ชะตา 4 ไฟล์ orphan + bookMeeting) · S9 (โครงเมนู/hub)
📌 หนี้นอกเมนู #8 ที่ค้าง: (1) Analytics audit ยังพัง 5 จุด · (2) finance module ทั้งหมวด schema-drift
   ต้อง reconcile ก่อนเปิดใช้ (ผูก Q3) · (3) bookMeetingSlot วางผิดที่ (ยกไป SM-S7)

---

## 2026-09-15 — RichTextEditor: ล้างสีที่ยึดธีมออกจากเนื้อหา rich text (แก้ dark mode)

### สถานะ: ✅ commit `28e87e71` (+89/−6) · push แล้ว · โค้ดเป็นงานค้างเดิมใน working tree · Claude รีวิว+ตรวจ+commit

**ที่มา:** เปิดเซสชันมาเจอ `ui/components/RichTextEditor.vue` + `ui/composables/useRichText.ts`
ค้าง uncommitted ใน working tree ~5 วัน (ไม่มีบันทึกใน worklog) — งาน paste-sanitization
สำหรับ dark mode · เจ้าของโปรเจคสั่งให้ "ปิดงานที่ค้าง" → Claude รีวิว+ตรวจ+commit

**ปัญหาที่แก้:** เนื้อหา paste จาก Gemini/Docs/ChatGPT ติดสีเข้ม + `--tw-*` ฝังใน `style=""`
inline style ชนะ class ⇒ `dark:prose-invert` ทับไม่ได้ → ตัวหนังสือเข้มบนพื้นเข้ม (contrast 1.00:1)

### สิ่งที่แก้
- `useRichText.stripThemeLockedStyles()` ใหม่ — ถอด `color/background/border-color/outline/
  caret/text-decoration/column-rule/fill/stroke/--tw-*` ออกจาก `style=""` แต่**เก็บ** property
  ที่ไม่เกี่ยวธีมไว้ (`text-align/width/font-size/margin` ฯลฯ)
- 🔴 **ทำเฉพาะในแท็กจริง** (regex ชั้นนอกจับ `<tag ...>`) จึง**ไม่กิน**ตัวอย่างสอนที่ escape ไว้
  เช่น `&lt;p style="color:red"&gt;` ในบทเรียนสอน HTML/CSS
- 🔴 **idempotent** — จำเป็น เพราะ `watch(props.modelValue)` เรียกซ้ำได้ ถ้าไม่ idempotent
  จะ reset innerHTML วนลูป cursor เด้ง
- `sanitizeHtml()` เรียก `stripThemeLockedStyles` ต่อท้าย ⇒ **blast radius = RichTextViewer
  ทั้ง 2 ตัว** (`components/RichTextViewer.vue` + `components/Common/RichTextViewer.vue`)
  สะอาดทั้งแอป — เป็นผลที่ต้องการ
- `RichTextEditor.vue`: ล้างตอน init + ใน watch + ดัก `@paste` ให้ผ่าน `sanitizeHtml` ก่อน insert
  **โบนัสความปลอดภัย:** paste เดิมยัด HTML ดิบลง contenteditable ตรง ๆ ตอนนี้ผ่าน DOMPurify ก่อน

### หลักฐานที่ Claude รันเอง
- unit test ตรรกะ strip **14 เคส** (theme color · `--tw-` junk · `font-family:&quot;…&quot;`
  semicolon-safe · escaped teaching example survives · real tag wrapping escaped example ·
  single+double quote · background gradient · fill/stroke · idempotent · null) → ผ่านครบ
- grep ยืนยัน blast radius: `sanitizeHtml` ถูกเรียกใน RichTextViewer 2 ตัวเท่านั้น · `<RichTextEditor>`
  ที่ทุกหน้าใช้ = `components/RichTextEditor.vue` (ตัวที่แก้) ไม่ใช่ `components/Common/RichTextEditor.vue`

### ⚠️ ค้าง — ยังไม่ตรวจบนจอจริง
paste จริงจำลองอัตโนมัติไม่ได้ (ต้องมี clipboard event จริง) จึงตรวจตรรกะด้วย unit test แทน
ควรล็อกอินเป็นครู → เปิดฟอร์มบทเรียน/คอร์ส → สลับ dark mode → paste จาก Gemini → ยืนยันอ่านออก

---

## 2026-09-15 (ต่อ) — เมนู #10 ห้องเรียน: audit + เขียนไฟล์รอง (ขั้น [1]+[2])

### สถานะ: ✅ audit เสร็จ · เขียน `.agents/school-admin/10-classrooms.md` · ยังไม่ส่ง step ให้ agy

ต่อจากปิดงาน RichTextEditor → เข้าเมนูถัดในโรดแมป (#8 → #10) · ทำ workflow loop ขั้น [1] สแกนโค้ดจริง
+ ขั้น [2] เขียนไฟล์รอง (Claude วางแผน/ตรวจเท่านั้น ยังไม่แตะโค้ด feature)

### 🔑 ข้อสรุปสำคัญ — เมนู #10 **ไม่ใช่ประตูเปิดโล่งแบบ #8-G1**
`route:list -v` ยืนยัน: ทุก route `{academy}/classrooms*` resolve = `auth:api` เท่านั้น (index +`visibility:content`)
**แต่** controller มีด่านในโค้ดจริง `canManage()` = owner หรือ member role∈{owner,director,admin}
⇒ สุ่ม academy_id เข้ามาลบห้อง/อ่าน roster **ไม่ได้** (ต่างจาก #8 ที่ไม่มี guard เลย)

### gap 7 ข้อ (รายละเอียด+หลักฐานใน 10-classrooms.md §5)
- **G1** (P1) `canManage` hardcode 3 role · bypass `groups.manage` + สิทธิ์ระดับฝ่าย → หัวหน้าฝ่ายที่ได้สิทธิ์แต่ role ไม่ตรงถูกล็อกออก
- **G2** `canManage` ไม่กรอง `wherePivot('status', approved)` → member ไม่อนุมัติ role=admin ก็ผ่าน (แก้ได้ทันที)
- **G3** read gate ไม่สม่ำเสมอ: index=visibility:content vs statistics/getAllStudents/show=canManage
- **G4** (footgun) `RebuildClassroomsFromStudents.php:72-74` ยังลบ classroom_students+members+classrooms ทั้งโรงเรียน (แค่ confirm กั้น) + `MergeDuplicateClassrooms`
- **G5** `index.vue:452 fetchClassroomStudents` ยิง `GET /classrooms/{id}/students` ที่ไม่มี route (มีแค่ POST) → น่าจะ 405 (verify caller)
- **G6** legacy phase-6 columns (`class_level/class_section/level_and_room`) ยังอ่าน ~20 ไฟล์ — cross-cutting
- **G7** mobile-first 2 หน้าใหญ่ (1625+2360) ยังไม่ตรวจ 375px

### 🔴 memory ล้าสมัยที่แก้แล้ว
[[project-classroom-source-of-truth]] บอก `deleteClassroom()` เป็น bare `delete()` → **ไม่จริงแล้ว**
ตอนนี้ guard: มี active student → throw ให้ไป archive · ลบเฉพาะ enrollment ไม่ active ก่อน (ใน transaction)
⇒ รากปัญหา "ลบห้องเงียบ ๆ" ปิดไปแล้ว · อัพเดต memory แล้ว

### steps: CL-S1/S2/S3/S6 ทำได้ทันที · CL-S4 (ยกด่านเข้า permission system) รอ Q1–Q3 (ดู §6)
Q1 แยก key `classrooms.*` หรือใช้ `groups.*` ร่วม #9? · Q2 read tier ใครเห็น? · Q3 คง canManage หรือย้าย middleware?

---

## 2026-09-15 (ต่อ) — เมนู #10: CL-S1/S2/S3/S6 (step ที่ไม่ติด Q)

### สถานะ: ✅ 3 commit + CL-S6 audit-only · ผู้เขียนโค้ด agy (2 shard) · Claude ตรวจ+กู้+commit

- **CL-S1** `03704c4e` — canManage 3 controller (Classroom/Group/Invitation) เพิ่ม
  `wherePivot('status', AcademyMember::STATUS_APPROVED)` กันสมาชิกไม่อนุมัติ role=admin ผ่านด่าน
  (interim · ย้ายไป `Academy::userCan('groups.manage')` เต็มรูป = CL-S4 รอ Q1)
- **CL-S2** `c8176236` — guard `app()->isProduction()` หัว handle() ของ `classrooms:rebuild-from-students`
  + `classrooms:merge` (2 command ที่ลบ/รวมห้องทั้งโรงเรียน)
- **CL-S3** `d358cce0` — index.vue `fetchClassroomStudents` เดิมยิง GET `/classrooms/{id}/students` (405) →
  ชี้ไป collection `GET /classrooms/students?classroom_id=` (getAllStudents, per_page 200) shape ตรง
- **CL-S6** — audit-only ไม่พบ violation (ตารางกว้างห่อ overflow-x-auto ครบ · touch 44px · grid responsive)

### หลักฐาน Claude รันเอง (ไม่เชื่อรายงาน agy)
pint --test passed 5 ไฟล์ · php -l clean · ClassroomManagementTest **19/19 (50 assertions)** ·
git show ยืนยันไฟล์ต่อ commit ถูก (CL-S1=3 controller, CL-S2=2 command, CL-S3=index.vue)

### 🔴 เหตุการณ์ agy race — บทเรียนสำคัญ
ส่ง 2 shard ขนาน (background): frontend เสร็จส่ง notification ปกติ · **backend shard ยังรันอยู่**
ตอน Claude เห็น diff ครบแล้ว commit → agy pass สอง เขียน `modify.py` (UTF-16, target `$var` พัง +
Thai เป็น mojibake) **revert 4/5 ไฟล์ทิ้ง** เหลือแค่ ClassroomController ที่ commit ไปแล้ว
→ Claude `TaskStop` job backend · re-apply 4 จุดเอง (pint แปลง inline FQN → import) · ลบ modify.py ·
reset+commit ใหม่ให้ boundary สะอาด
**กติกาใหม่:** รอ task-notification ครบ **ทุก** shard ก่อน commit เสมอ (ดู [[feedback-agy-fabricates-diffs]])

### เมนู #10: ปิดครบทุก step ที่ไม่ติด Q · เหลือ CL-S4 (รอ Q1–Q3) + CL-S5 (เทสต์) · CL-S7 deferred
Q1 key `classrooms.*` แยก หรือใช้ `groups.*` ร่วม #9 · Q2 read tier · Q3 คง canManage หรือย้าย userCan

---

## 2026-09-15 (ต่อ) — เมนู #10: CL-S4 ยกด่านสิทธิ์เข้า Academy::userCan (G18)

### สถานะ: ✅ commit `776258cf` · ผู้เขียนโค้ด agy (1 shard) · Claude ตรวจ+เขียนเทสต์เอง

**Q1–Q3 เคาะแล้ว** (owner): Q1 ใช้ `groups.*` ร่วม (registry นิยาม groups="กลุ่มเรียน/ฝ่าย/แผนก") ·
Q2 reads=`groups.view` · Q3 ย้าย `canManage`→`Academy::userCan()`

**verify ก่อนทำ (Claude):** V1 `GET .../classrooms` มีแต่หน้า admin เรียก (useSchoolManagement/gradebook/schedule)
→ ปิด groups.view ปลอดภัย · V2 reconcile migration: director+admin มี groups.manage ครบ · owner ผ่าน isAdmin
→ ย้าย userCan ไม่ตัดสิทธิ์ใครที่ canManage เคยให้

### สิ่งที่แก้ (4 ไฟล์)
- `routes/learn/academy.php`: 2 classroom group + transfer-member/transfer-student/promote/enrollment-history
  ได้ middleware `academy.permission:groups.view` (baseline) · index เลิก `academy.visibility:content`
- ClassroomController: `canManage`→`userCan('groups.manage')` (write 16 จุด) · เพิ่ม `canView`→`userCan('groups.view')` ·
  read 4 จุด (enrollment-history/getAllStudents/getStudent/statistics) ใช้ canView · ลบ import AcademyMember
- ClassroomGroup/Invitation: `canManage`→`userCan('groups.manage')` + ลบ import

### หลักฐาน Claude รันเอง
git diff 4 ไฟล์ตรงสเปคเป๊ะ ไม่มี stray · pint passed · `route:list` เห็น groups.view ทุก route (index เลิก visibility) ·
**ClassroomPermissionGuardTest ใหม่ 5/5 (8 assertions)** — non-member 403 · groups.view อ่านได้/เขียน 403 ·
groups.manage สร้าง 201 · owner 201 · unapproved(status≠2) 403 · ClassroomManagementTest 19/19 ไม่ regression

### ✅ agy รอบนี้สะอาด — single shard + รอ task-notification เสร็จก่อน commit (บทเรียนจากรอบก่อน) → ไม่มี race/modify.py

### เมนู #10: CL-S1–S6 ปิดครบ · เหลือ CL-S5 (happy-path เทสต์ที่เหลือ) · CL-S7 legacy-column deferred · ยังไม่ตรวจจอจริง CL-S3

---

## 2026-09-15 (ต่อ) — เมนู #10: CL-S5 เทสต์ happy-path + แก้ regression CL-S4

### สถานะ: ✅ commit `bad45952` · Claude เขียนเทสต์เอง (test = เครื่องมือ verify)

**สแกนก่อนเขียน:** เทสต์ห้องเรียนเดิมครอบเยอะแล้ว — ClassroomManagementTest 19 (roster add/remove + transfer) ·
ClassroomRenumberTest 14 (renumber เดี่ยว+bulk + permission) · ⇒ CL-S5 เก็บเฉพาะช่องว่างจริง

### สิ่งที่ทำ
- **ClassroomFeaturesHappyPathTest ใหม่ (3 เคส):** ClassroomGroup CRUD · Invitation create/cancel ·
  promoteClassroom (ข้ามปีการศึกษา)
- **แก้ regression จาก CL-S4:** ClassroomStudentGuardianPayloadTest 2 เคส grant `['classrooms.view']`
  (permission key ที่ไม่มีจริง) — เดิมผ่านเพราะ member role=admin (canManage ดู role string) · พอ CL-S4
  ทำ userCan ตรวจ permission จริง → 403 → แก้ fixture เป็น `['groups.view']` (2 บรรทัด)

### 🔴 semantics ที่เทสต์เผยออกมา
`promoteClassroom` = เลื่อนชั้น **ข้ามปีการศึกษา** เท่านั้น · ปีเดียวกัน service throw "use transferStudent()"
(เขียนเทสต์ผิดตอนแรก → รันแล้วเจอ → แก้ให้ to-classroom อยู่ปีถัดไป)

### หลักฐาน Claude รันเอง
pint passed · ClassroomFeaturesHappyPathTest 3/3 (14 assertions) · **full classroom suite 128/128** ไม่มี failure
(เดิม 125 + ใหม่ 3) · แก้ regression: ก่อนแก้ 2 failure → หลังแก้ 0

### 🎯 เมนู #10: CL-S1–S6 ปิดครบ · เหลือแค่ CL-S7 (drop legacy column) deferred + ตรวจจอจริง CL-S3

---

## 2026-09-15 (ต่อ) — เมนู #10 CL-S7: เขียนแผนแบ่งเฟส drop คอลัมน์ชั้นเรียน legacy

### สถานะ: ✅ แผนพร้อม `.agents/legacy-class-columns-drop-plan.md` (ยังไม่ลงมือ) · เจ้าของเลือก "เขียนแผนก่อน"

**สแกน scope จริง:** `class_level`/`class_section`/`level_and_room` ถูกใช้ **~263 จุด / ~56 ไฟล์**
(BE ~182/27 · FE ~81/29) — คอลัมน์ยัง load-bearing ⇒ ลบดื้อ ๆ ไม่ได้ พังทั้ง members/บัตร/โปรไฟล์/เยี่ยมบ้าน/gradebook

**🔑 กุญแจที่ทำให้เป็นไปได้ — แยก 4 กลุ่ม:** output key (~53, เก็บ) · SQL alias `classrooms.X as class_level` (~4, เก็บ) ·
attribute read `$x->class_level` (~91, **แก้ด้วย accessor จุดเดียวต่อ model**) · คอลัมน์จริง SQL where/orderBy/select+write (ที่เหลือ = ตัวบล็อกจริง)
⇒ `Student::currentEnrollment()` มีอยู่แล้วเป็น reroute target · accessor คืน `currentEnrollment?->classroom?->grade_level ?? $value`

**7 เฟส:** A audit/categorize (Claude) → B accessor (Student+StudentCard, eager-load กัน N+1) → C reroute SQL → D เลิกเขียน+fillable → E migration drop (down backfill + STRICT + รัน MySQL จริง) → F FE (คง output key แล้ว FE แทบไม่ต้องแก้) → G cleanup console

**Risk เด่น:** data drift (ค่าที่โชว์เปลี่ยนไปดึง enrollment) · นักเรียนไม่มี active enrollment (accessor null — ต้องเคาะ fallback) ·
เทสต์ SQLite ไม่พิสูจน์ migration MySQL · ห้ามเอา output key ออก (API contract)

**แนะนำเริ่ม Phase A ก่อน** (Claude ทำได้เลย ไม่แตะโค้ด) แล้วเคาะ fallback (R2) ก่อนลง Phase B

---

## 2026-09-16 — CL-S7 Phase A: categorize คอลัมน์ชั้นเรียน legacy ครบ

### สถานะ: ✅ Phase A เสร็จ (Claude, ไม่แตะโค้ด) · ผลอยู่ใน `.agents/legacy-class-columns-drop-plan.md` §4.5

### 🔴 ค้นพบสำคัญ: `students.*` กับ `student_cards.*` ความหมายต่างกัน → เสนอแยก 2 track
- `students.class_level/class_section` = denormalize enrollment สด → accessor ครอบได้ (track 1, สะอาด)
- `student_cards.class_level/class_section/level_and_room` = **snapshot ตอนออกบัตร** (StudentCardRequestService เขียนจาก
  grade_level_snapshot) · ถ้า drop บัตรที่พิมพ์แล้วจะเปลี่ยนตาม enrollment สด (นักเรียนย้ายห้อง = บัตรเก่าเพี้ยน)
  → **track 2 ต้องเคาะ Q-A1 ก่อน** (คงเป็น snapshot / derive จาก request snapshot / ยอม live)

### กลุ่ม D จริง (ตัวบล็อก drop) — backend
- **D1 SQL อ่าน (10 จุด):** ClassroomController getAllStudents orderBy(762-763)+show fallback(115-116) · Classroom.php(168-169) ·
  AcademyMemberController fallback(394-427)+filter merge(701) · StudentCardController(306,394-395) · StudentController(46,51) ·
  AcademicYearRolloverService(201-202) · StudentCardAuditService(41,58) · RebuildClassrooms(retire)
- **D2 เขียน (6 จุด):** Student fillable(96-97) · StudentCard fillable(21-25) · StudentCardController validation(702-837) ·
  StudentCardRequestService snapshot(220-222) · AcademicYearRolloverService(383-504) · EnrollmentRepairDirtyData(retire)

### กลุ่ม A/B/C (ไม่บล็อก): output key ~53 (เก็บ) · alias ~4 (เก็บ) · attribute-read ~91 (accessor)
resource ที่ดีอยู่แล้ว: RoomStudentResource + StudentCardResource (enrollment-first)

### FE: จุดเขียนจุดเดียว (student-cards/[id]/edit.vue:302) · types = contract (เก็บ) · ~78 จุดที่เหลือ display ล้วน (คง output key ไม่ต้องแตะ)

### รอเคาะก่อน Phase B: Q-A1 (บัตร snapshot) · R2 (นักเรียนไม่มี active enrollment โชว์อะไร) · R1 (ยอม drift)

---

## 2026-09-16 (ต่อ) — CL-S7 Phase B: accessor ชั้นเรียนจากแหล่งจริง (track 1)

### สถานะ: ✅ commit `43bdeb69` · ผู้เขียนโค้ด agy (v2) · Claude ออกแบบ/แก้สเปค/เขียนเทสต์/ตรวจ

`Student::class_level/class_section` เป็น accessor อ่านจาก `currentEnrollment->classroom` แทนคอลัมน์ denormalized
⇒ Phase E ลบคอลัมน์ได้โดยจุดที่อ่านแบบ attribute (~91 จุด) ไม่ต้องแก้

### 🔴 Phase B จับดีไซน์ผิดได้ 2 ข้อ ก่อน commit (เหตุผลที่ต้อง verify หนักตรงนี้)
รอบแรกทำตามสเปคเดิม → suite แดง **9 เคส** เผยว่า:
1. **R3 รูปแบบค่าต่างกัน** — `students.class_level` เก็บ **ตัวเลข** ('2') แต่ `classrooms.grade_level` เก็บ 'ม.2' ·
   service เขียนผ่าน `normalizeGradeLevel()` ⇒ accessor ต้อง normalize ด้วย (ไม่งั้น `where('class_level', 6)` พัง) — แดง 5 เคส
2. **R2 ที่เคาะไว้ขัดระบบ** — graduate/drop/remove **ตั้งใจ set null** (StudentEnrollmentService 346/400/448)
   และ `test_graduate_student` assert null ไว้ ⇒ fallback ไป enrollment ล่าสุดทำให้นักเรียนจบโชว์ชั้นเก่าค้าง — แดง 3 เคส
   → **กลับคำ R2 เป็น "ไม่มี active = null"** (ถามเจ้าของก่อนแก้ ไม่กลับคำเอง) · ความต้องการดูชั้นเก่าไปใช้
   student_cards snapshot (track 2 ที่เก็บไว้) + ประวัติ enrollment แทน
3. เคสที่ 9 (`EnrollmentRepairDirtyDataTest` dry-run) = เทสต์อ่านคอลัมน์ผ่าน model ซึ่ง accessor บังไปแล้ว
   → แก้ให้ assert คอลัมน์ดิบด้วย `assertDatabaseHas` (ตรงเจตนา "dry-run ต้องไม่เขียน" มากกว่าเดิม)

### หลักฐาน Claude รันเอง
pint passed · git diff Student.php add-only ตรงสเปค ($fillable ไม่ถูกแตะ) · **suite Classroom|Student|Card 401/401 ไม่มี failure**
(ดีไซน์แรกแดง 9 → หลังแก้ 0) · เทสต์ใหม่ StudentClassAccessorTest 4/4: normalize จากห้องจริงแม้คอลัมน์ drift ·
null เมื่อไม่มี active (ไม่ปลุกชั้นเก่า) · fallback คอลัมน์เมื่อไม่มี enrollment · N+1 guard (query คงที่)

### หมายเหตุ agy: รอบแรก **no-op สนิท** (exit 0, log ว่าง, ไม่แก้อะไร) ต้องสั่งซ้ำถึงทำ · ทิ้ง artifact `test_output.txt` ต้องลบเอง

### ต่อไป: Phase C — reroute SQL ~10 จุด (fallback/orderBy/filter ที่ยังอ่านคอลัมน์ตรง ๆ)

---

## 2026-09-16 (ต่อ) — CL-S7 Phase C + ปิดงาน CL-S7 ที่ Phase B/C

### สถานะ: ✅ commit `c241af46` · 🎯 **CL-S7 ปิดแล้ว — D/E/F/G ยกเลิก**

### 🔴 R4 — เหตุผลที่ "เลิก drop" (เจอตอนไล่ Phase C)
`AcademicYearRolloverService:200-245` ใช้ `students.class_level/class_section` เป็น **intake staging**:
`pendingStudents` = นักเรียนที่ **มี class_level แต่ยังไม่มี active enrollment** → rollover อ่านค่านี้เพื่อจัดเด็กใหม่
เข้าห้อง (`action => 'new_intake'`) ⇒ คอลัมน์ตอบคำถามที่ตาราง enrollment **ตอบไม่ได้เชิงโครงสร้าง**
("เด็กคนนี้ควรเข้าห้องไหน ตอนยังไม่มี enrollment") · accessor ช่วยไม่ได้เพราะ derive จาก enrollment
⇒ ถ้า drop = พังทางเข้าเด็กใหม่ทั้งเส้น → **เจ้าของเคาะ (ก) เลิก drop จบที่ B/C**

### Phase C (ฉบับย่อ) — ลบ dead fallback 2 จุด
พิสูจน์ด้วยข้อมูลจริงก่อนลบ: `students.class_level` มีแต่ '1'–'6' (+NULL 837) · `classrooms.grade_level`
มีแต่ 'ม.1'–'ม.6' ⇒ `where('class_level','ม.1')` **ไม่เคย match** = โค้ดตายที่หลอกว่ามีตาข่ายรองรับ
- `Classroom::getEnrolledStudentsQuery()` เหลือเส้น pivot อย่างเดียว + ตัด COUNT ที่ยิงทุกครั้ง
- `ClassroomController::show()` ลบบล็อก fallback
- **ไม่แตะ** AcademicYearRolloverService (intake) · ไม่แตะ StudentCardController/StudentCardAuditService (track 2 — ยิงที่ StudentCard ไม่ใช่ students)

### หลักฐาน Claude รันเอง
pint passed · php -l clean · **suite Classroom|Student|Card 401/401 ไม่มี failure** · grep ยืนยัน rollover intake ยังอยู่

### 🎯 สรุป CL-S7: ได้ประโยชน์จริงแม้ไม่ได้ drop
- อ่าน `$student->class_level` มาจาก **แหล่งจริง** แล้ว (enrollment) ⇒ ปัญหา data drift หายไป
- ลบโค้ดตาย 2 จุด + ตัด query ส่วนเกิน
- คอลัมน์เหลือหน้าที่เดียวที่ชัดเจน = intake staging · ถ้าจะ drop วันหน้าต้องทำ (ข) ฟิลด์ intended_* หรือ (ค) enrollment สถานะ pending ก่อน

---

## 2026-09-16 (ต่อ) — ตรวจ CL-S3 บนจอจริง → เจอบั๊กเดิมที่หลับอยู่

### สถานะ: ✅ CL-S3 ผ่านบนจอจริง · เจอ+แก้บั๊ก 500 เพิ่ม `42b3e0b3`

**วิธีตรวจ:** dev server :60088 (preview) + API :8000 · เจ้าของโปรเจคล็อกอินเอง (Claude ไม่กรอกรหัสผ่าน)
เป้าหมาย: academy 1 · classroom 85 = ม.3/7 ที่มี active 52 คนใน DB

### 🔴 เจอบั๊กที่เทสต์จับไม่ได้ — ต้องเปิดหน้าจริงถึงเห็น
CL-S3 ยิง URL ถูกแล้ว (`/classrooms/students?classroom_id=85`) **แต่ได้ 500**
สาเหตุ: `ClassroomController::getAllStudents` ใช้ `whereHas('classroomStudents', ...)` แต่ `Student`
ไม่มี relation ชื่อนั้น (มีแต่ `classroomEnrollments`) → BadMethodCallException
**เป็นบั๊กเดิมที่มีอยู่ก่อน CL ทั้งชุด** (ยืนยันว่าอยู่ใน `3f7e06d1`) แต่ **หลับอยู่** เพราะไม่เคยมีใคร
ส่ง `classroom_id` เข้ามา — `[id].vue` ส่งแต่ per_page/search · CL-S3 คือ caller แรกที่ใช้ตัวกรองนี้
⇒ แก้ชื่อ relation + เพิ่มเทสต์กันถอยหลัง (revert-check: ชื่อเดิม→แดง · ชื่อถูก→เขียว)

### ผลตรวจบนจอจริง (หลังแก้)
- network: `GET /classrooms/students?classroom_id=85&per_page=200` → **200 OK** (เดิม 500/405)
- modal "นักเรียนในห้อง ม.3/7" ขึ้น **52 แถว** = ตรงยอด active ใน DB เป๊ะ (เดิมว่างเปล่าตลอด)
- **CL-S4 ผ่านไปในตัว** — หน้าเปิดได้ผ่าน middleware `academy.permission:groups.view` (บัญชีเป็นเจ้าของโรงเรียน)
- **375px:** ไม่มี horizontal scroll (scrollWidth == clientWidth == 375) · 52 แถวขึ้นครบ ·
  คอลัมน์รหัสนักเรียนซ่อนบนมือถือตาม `hidden sm:table-cell` (ตั้งใจ) · ชื่อไทยตัดบรรทัดปกติ
- console: error ที่เหลือเป็นของรอบก่อนแก้ (buffer เก่า) — รอบหลังแก้ network เป็น 200

### บทเรียน: เทสต์ 402 เคสเขียวหมดแต่ไม่เจอบั๊กนี้ เพราะไม่มีเทสต์ไหนยิงตัวกรอง `classroom_id`
⇒ "เส้นทางที่ไม่มีใครเรียก" คือที่ที่บั๊กหลับได้นาน · การตรวจบนจอจริงคุ้มเสมอ

---

## 2026-09-17 — SC-S5 ตารางเรียน: สูตรกันชนใหม่ + กันสถานที่ + ปิด G20/G21

### สถานะ: ✅ ปิดแล้ว · agy เขียนโค้ด (สเปค `agy-sc-s5-schedule-conflict.txt`) · Claude เขียนเทสต์ + ตรวจเองทุกข้อ
รายละเอียดเต็มอยู่ใน `.agents/school-admin/11-schedule.md` §9

### สิ่งที่แก้ (backend ล้วน ไม่แตะ `ui/`)
- **G6 ขอบคาบ** — `whereBetween` (นับปลายช่วง) → ช่วงแบบ half-open `start < :end AND end > :start`
  รวมศูนย์ไว้ที่ `ClassSchedule::overlappingQuery()` จุดเดียว ที่ teacher/classroom/room ใช้ร่วมกัน
  ⇒ **จัดคาบติดกันได้แล้ว** (08:00–09:00 ต่อ 09:00–10:00) ซึ่งคือ 100% ของตารางเรียนจริง
- **กันสถานที่** — `hasRoomConflict(academy, room, semester, day, start, end, exclude)` ครบทั้ง
  `store` / `update` / `bulkStore` / `checkAvailability` (เพิ่มคีย์ `room_available`) ·
  `normalizeRoom()` ตัด+ยุบช่องว่างทั้งตอนเขียนและตอนเทียบ · ไม่ระบุสถานที่ = ไม่ตรวจ
- **G20** — `update()` ตรวจค่าหลังรวมร่าง: ไม่เหลือทั้ง `course_id` และ `title` = 422 `errors.title`
- **G21 (เจอตอนออกแบบ)** — `update()` ไม่เคยตรวจ `end_time > start_time` ⇒ ทำช่วงเวลากลับหัวได้
  และช่วงกลับหัว **ไม่มีวันชนกับใครตามสูตรใหม่** (คาบผี) → ปิดเป็น 422 `errors.end_time`

### 🔴 กับดักที่ต้องจำ: เวลาที่เก็บใน MySQL กับ SQLite ไม่ใช่ค่าเดียวกัน
คอลัมน์ `start_time/end_time` cast เป็น `datetime:H:i` ⇒ **MySQL** (ชนิด `TIME`) เก็บ `'09:00:00'`
แต่ **SQLite ตอนรันเทสต์** เก็บข้อความ `'09:00'` ⇒ เทียบแบบสตริง `'09:00' < '09:00:00'` เป็นจริง
⇒ คาบที่ "จบพอดีตอนคาบเดิมเริ่ม" ถูกนับว่าชนบน SQLite ทั้งที่ MySQL บอกว่าไม่ชน
**ทางแก้:** หุ้ม `TIME()` ทั้งสองฝั่ง (`TIME(start_time) < TIME(?)`) — มีทั้งสองฐานและคืน `'H:i:s'` เหมือนกัน
(สเปครอบแรกของ Claude สั่งให้ normalize แค่ฝั่ง bind — เทสต์จับได้ Claude แก้เอง ไม่ใช่ความผิด agy)

### 🔴 G22 (ใหม่) — unique index ระดับ DB ที่ไม่รู้จัก `status`
`class_schedules` มี `unique_teacher_schedule` / `unique_classroom_schedule` =
(teacher|classroom, semester, day, **start_time**) · ยืนยันบน MySQL จริงแล้ว
- มันกันแค่ "เวลาเริ่มตรงกันเป๊ะ" = เซตย่อยของการชนจริง (ตรรกะแอปครอบไปหมดแล้ว)
- **ไม่สนใจ `status`** ⇒ คาบที่ยกเลิกยังจองเวลาเริ่มนั้นไว้ ⇒ สร้างคาบใหม่ทับ = **500 (SQLSTATE 23000)** ไม่ใช่ 422
- แก้ต้องเป็น migration → ยกไป **SC-S11** (เทสต์ `test_cancelled_period_does_not_block` เลี่ยงเวลาเริ่มซ้ำไว้ก่อน)

### หลักฐานที่ Claude รันเอง (ไม่ได้ลอกจากรายงาน agy)
- `pint --test` ผ่าน · `php -l` clean · `grep -c whereBetween` = 0
- เทสต์ใหม่ `ClassScheduleConflictTest` **27/27 (76 assertions)** · ข้างเคียง `ClassSchedule|ClassroomManagement` **60/60**
- **revert-check:** เอาโมเดลเวอร์ชันเก่ากลับมา → แดง 16/27 ทันที (เทสต์กัดจริง ไม่ใช่เขียวเพราะไม่ได้ทดสอบอะไร)
- **ยิงจริงบน MySQL** 16 เคส (ภาคเรียน 4/2569 · วันพุธ · ห้อง 58/67 · ครู 17004/17005) ครบทั้ง
  201 ที่ควรผ่าน / 422 ที่ควรกัน / PATCH / check-availability → **ลบแถวทดสอบแล้ว DB กลับมา 5 แถว (max id = 5)**

### ต่อไป: SC-S6 (ชุดโครงคาบตั้งค่าเองได้ + กริดวาดจากคาบจริงแทน 08:00–16:00 ฮาร์ดโค้ด)

---

## 2026-09-17 (ต่อ) — SC-S6 ชุดโครงคาบเรียนตั้งค่าเองได้ + กริดวาดจากคาบจริง

### สถานะ: ✅ ปิดแล้ว (3 shard: S6a backend · S6b หน้าตั้งค่า · S6c กริดใหม่) · รายละเอียดเต็มใน `.agents/school-admin/11-schedule.md` §9

### สิ่งที่ได้
- **`schedule_period_sets`** — โรงเรียนมีโครงคาบได้**หลายชุด** ผูกเงื่อนไข `days` (1–7) และ `grade_levels`
  ย้าย unique ของ `schedule_periods` จาก `(academy_id, period_number)` → `(set_id, period_number)`
  ⇒ ชุด "โครงปกติ" กับ "วันศุกร์เลิกเร็ว" มีคาบเลข 1 พร้อมกันได้ (ของเดิมทำไม่ได้เชิงโครงสร้าง)
- **CRUD + หน้าตั้งค่า** `/academies/{name}/admin/schedule-periods` (+ เมนูซ้าย) พร้อมปุ่ม "ใช้ตัวอย่างโครงคาบมัธยม"
- **G14 ปิด** — กริดวาดจากคาบจริง จับคาบสอนเข้าแถวด้วย "ซ้อนทับมากสุด" หนึ่งคาบอยู่แถวเดียว
  และคาบที่ไม่ตรงช่วงไหนได้แถว "นอกโครงคาบ" ⇒ **ไม่มีคาบไหนหายจากจออีก**
- **G15 ปิด** — คอลัมน์วันมาจากชุด + วันที่มีคาบจริง (คาบวันเสาร์โผล่เอง) · ฟอร์มเลือกได้ครบ 1–7

### 🔴 บทเรียนสำคัญของรอบนี้: การตรวจบนจอจริงจับได้ 3 อย่างที่เทสต์+SFC compile ไม่มีวันจับ
1. **G24 — ทั้งเรพอ่าน error ของ `useApi` ผิดที่** `createApiError()` สร้างอ็อบเจกต์ใหม่ที่เก็บ body ไว้ที่ `.data`
   และ**ไม่มีคีย์ `response`** แต่หน้าเว็บ ~30 ไฟล์อ่าน `err.response?.data` (axios) หรือ `error?.response?._data` (ofetch)
   ⇒ ข้อความ 422 ที่ backend อุตส่าห์ส่งมาไม่เคยถึงผู้ใช้ — **ทำให้ผลงาน SC-S5 ทั้งชุดไม่ถึงผู้ใช้จริง**
   แก้แล้ว 2 หน้าของเมนูนี้ (พิสูจน์บนจอ: ขึ้น "ครูผู้สอนมีตารางสอนซ้ำซ้อนในเวลานี้" จริง) · ที่เหลือแยกเป็นงานต่างหาก
   **ข้อคิด: อย่าเคลมว่า FE โชว์ข้อความ error ได้ ถ้ายังไม่ได้ดูว่า error object หน้าตาอย่างไรจริง ๆ**
2. **G23 — โมดัล `z-50` เท่ากับแถบเมนูล่างมือถือ** (`fixed bottom-0 z-50` สูง 64px) ⇒ ปุ่ม "บันทึก" ท้ายโมดัล
   ถูกทับจนกดไม่โดน (ยืนยันด้วย `document.elementFromPoint` → ได้ลิงก์ "รายได้" แทนปุ่ม) · แก้หน้าใหม่เป็น `z-[60]`
   · โมดัลอื่นในเมนู admin อีก 13 จุดยังเป็นแบบเดิม → ยกไป SC-S8
3. **การจับคาบเข้าแถวต้องดูวันด้วย** ไม่ใช่ดูแต่เวลาซ้อน — คาบวันเสาร์ไปเกาะแถวของชุด "วันศุกร์" เพราะซ้อนเท่ากันพอดี
   → ให้แถวของชุดที่ใช้กับวันนั้นชนะเสมอ

### หมายเหตุเครื่องมือ: คลิกด้วยพิกัดในเบราว์เซอร์ของแอปมีสิทธิ์พลาด
`ref` ที่ได้จาก `find`/`read_page` ถูกแปลงเป็นพิกัด ณ ตอนสแกน ถ้าหน้าเลื่อนหลังจากนั้น (เช่น `form_input`
เลื่อนช่องเข้ามาในจอ) คลิกจะไปลงที่เก่า ⇒ ให้ถ่ายภาพหน้าจอใหม่ก่อนคลิกทุกครั้ง หรือใช้ `.click()` ผ่าน JS
แล้วค่อยพิสูจน์ผลจาก network/DB (ไม่ใช่เชื่อว่าคลิกติดเพราะไม่มี error)

### ต่อไป: SC-S7 (สิทธิ์ `schedule.view/manage` + หน้า "ตารางสอนของฉัน" / "ตารางเรียนของฉัน")

---

## 2026-09-17 (ต่อ) — SC-S7 สิทธิ์ตารางเรียน + หน้า "ตารางของฉัน"

### สถานะ: ✅ ปิดแล้ว (S7a backend · S7b frontend) · รายละเอียดเต็มใน `.agents/school-admin/11-schedule.md` §9

### สิ่งที่ได้
- **ด่าน 3 ชั้น**: `/schedules/my` = `schedule.view.own` · อ่านทั้งโรงเรียน = `schedule.view` · เขียน = `schedule.manage`
- **migration เติมคีย์เข้าบทบาทระบบจริง** — ถ้าเปลี่ยนแค่ route แล้วไม่เติมคีย์ **ด่านใหม่จะปิดทาง admin เอง**
  (ไม่มีบทบาทไหนถือ `schedule.manage` เลยมาแต่ไหนแต่ไร) · teacher/staff/registrar ไม่ถูกแตะเพราะมี `schedule.view` อยู่แล้ว
- **`GET /schedules/my`** คืนเป็น "บริบท" (ครู / ห้องเรียน) — คนเดียวมีได้ทั้งสองแบบ · ครูใหม่ได้กริดว่างแทนที่จะไม่มีอะไร
- **หน้า `/academies/{name}/my-schedule`** อ่านอย่างเดียว + ปุ่มลัดในแดชบอร์ดนักเรียน/ครู
- **`useScheduleGrid` composable** — ตรรกะกริด (แถวจากคาบจริง · จับคาบเข้าแถวแบบซ้อนมากสุด · คอลัมน์วัน)
  เหลือชุดเดียวในเรพ ใช้ร่วมกันทั้งหน้า admin และหน้าใหม่ (`admin/schedule.vue` −128/+5)

### ข้อจำกัดที่ต้องจำ (ไม่ใช่บั๊ก)
- **ผู้ปกครองยังดูตารางบุตรหลานไม่ได้**: `guardians` 4,504 แถว แต่ `user_id` ว่างทั้งตาราง = ยังไม่มีบัญชีผู้ปกครอง
  ⇒ จงใจไม่เขียนโค้ดสาขานี้เพราะทดสอบไม่ได้ · เมื่อมีระบบบัญชีผู้ปกครองแล้วค่อยเติมบริบทที่ 3 เข้า `my()`
- **แดชบอร์ดครู/นักเรียนเปิดด้วยบัญชีเจ้าของโรงเรียนไม่ได้** (หน้ากันด้วยบทบาทของตัวเอง) ⇒ ปุ่มลัดที่เพิ่มเข้าไป
  ตรวจได้แค่ระดับ diff + SFC compile

### 🔴 บั๊กเดิมที่เจอระหว่างเปิดแดชบอร์ดครู (ไม่เกี่ยวกับงานนี้ แยกเป็นงานต่างหากแล้ว)
`GET /api/academies/{id}/analytics/teacher-pending-assignments` ตอบ **500 ทุกครั้ง**
`Call to undefined method App\Models\Assignment::course()` — บั๊กชนิดเดียวกับที่เจอตอน CL-S3
(`whereHas('classroomStudents')`) คือ **เรียก relation ที่ไม่มีอยู่จริง** ซึ่งไม่มีอะไรจับได้จนกว่าจะยิง endpoint จริง

### ต่อไป: SC-S8 (UX/มือถือ — G16 ปุ่มลบใต้ hover + modal · G23 modal z-50 โดนแถบเมนูล่างทับ · G8 กรองครู)

---

## 2026-09-17 (ต่อ) — SC-S8 UX มือถือ: แก้ที่ต้นเหตุจุดเดียวแทนการไล่แก้ 87 ไฟล์

### สถานะ: ✅ ปิดแล้ว · รายละเอียดใน `.agents/school-admin/11-schedule.md` §9

### บทเรียนหลักของสเตปนี้: audit ก่อนลงมือ ทำให้ขอบเขตงานเปลี่ยนไปคนละเรื่อง
- **G8/G9 ปิดไปแล้ว** ตั้งแต่ SC-S4 (เช็คจาก network จริง ไม่ได้เชื่อเอกสาร)
- **ปุ่มลบใต้ hover ของ G16 หายไปแล้ว** ตั้งแต่ SC-S4 เขียนการ์ดในกริดใหม่ · โมดัลก็มี `max-h`+`overflow` แล้ว
- **G23 ใหญ่กว่าที่บันทึกไว้ 10 เท่า**: `fixed inset-0 z-50` มี **125 จุดใน 87 ไฟล์**
  ⇒ ถ้าไล่แก้ทีละโมดัลคือเสียเวลาและเสี่ยงพังมหาศาล · **แก้ที่ `BottomNav.vue` เป็น `z-40` จุดเดียว**
  (แถบเมนูล่าง = โครงของแอป ควรอยู่ใต้ overlay ชั่วคราวอยู่แล้ว · แถบข้าง/ลิ้นชักก็เป็น z-40)
  ⇒ โมดัลทั้งเรพลอยเหนือแถบเมนูทันที **โดยไม่ต้องแตะไฟล์ไหนอีกเลย**

### 🔴 กับดักที่เจอ (และเป็นเหตุผลว่าทำไมต้องเปิดจอจริงเสมอ)
**ชื่อ component ที่ Nuxt auto-import ต้องมี prefix ตามโฟลเดอร์**
`components/Common/SearchableSelect.vue` → ต้องเรียกว่า **`<CommonSearchableSelect>`**
(เหมือน `<CommonEmptyState>` / `<CommonFormField>` ที่ใช้อยู่ทั้งเรพ)
agy เขียน `<SearchableSelect>` ตามชื่อไฟล์ ⇒ **Vue ไม่เรนเดอร์อะไรเลย เงียบสนิท ไม่มี error ใน console**
และ SFC compile ก็ผ่าน · จับได้เพราะเปิดจอจริงแล้วเห็นว่าช่องเลือกห้องเรียน "หายไป"
(ตรงกับกับดักที่บันทึกไว้แล้วใน memory เรื่อง Nuxt route/component shape)

### สิ่งที่เพิ่มให้ผู้ใช้จริง
`CommonSearchableSelect` — ช่องเลือกที่ค้นหาได้ ใช้กับครู 120 คน / ห้องเรียน 53 ห้อง / คอร์ส
(พิมพ์ "ม.2/" กรอง 53 → 11 รายการ) · โฟกัสช่องค้นหาอัตโนมัติ · ปิดเมื่อคลิกนอก/กด Escape ·
touch target 44px ทุกแถว · ไม่พึ่งไลบรารีนอก (PrimeVue ในโปรเจคนี้ลงทะเบียนแค่ config ไม่ได้ลงทะเบียน component)

### ต่อไป: SC-S9 (ซ่อมผู้เรียกอื่นของ API ตารางเรียน — แดชบอร์ดครู `/schedules/today` + แท็บตารางเรียนในเมนู #8)

---

## 2026-09-17 (ต่อ) — SC-S9 ซ่อมผู้เรียกอื่นของ API ตารางเรียน

### สถานะ: ✅ ปิดแล้ว · รายละเอียดใน `.agents/school-admin/11-schedule.md` §9

### แก่นของสเตปนี้: "200 ไม่ได้แปลว่าใช้งานได้"
ทั้งสองจุดที่เอกสารบอกว่า "404" จริง ๆ กลับมา 200 ตั้งแต่ SC-S1 แล้ว แต่ยัง**อ่านข้อมูลผิด**เงียบ ๆ:
- การ์ด "ตารางสอนวันนี้": `data` เป็นอ็อบเจกต์ `{date, day_name, schedules}` แต่โค้ดรับเป็นอาเรย์
  ⇒ `.length` = undefined ⇒ เงื่อนไข "ไม่มีคาบ" ไม่เคยจริง · `v-for` วนคีย์ของอ็อบเจกต์ = การ์ดโชว์ขยะ
  และเทมเพลตอ่าน `schedule.time`/`.subject`/`.students` ซึ่งไม่มีสักคีย์ในของจริง
- แท็บตารางเรียนในเมนู #8: ปุ่ม "เพิ่มตารางเรียน" ตั้ง `showScheduleModal = true` ทั้งที่**ไม่มีโมดัลนั้นในไฟล์**
  = ปุ่มตายที่ไม่มีใครรู้ (ไม่มี error ไม่มีอะไรเกิดขึ้นเลยตอนกด)

### ตัดสินใจ: ไม่สร้างโมดัลจัดตารางซ้ำในแท็บ #8
หน้าจัดตารางเต็มรูปแบบมีแล้ว (โครงคาบ · กันชนเวลา/สถานที่ · ช่องเลือกแบบค้นหา)
แท็บ #8 จึงเป็น "มุมมองอ่าน" แล้วลิงก์ไปหน้านั้นแทน — กัน UI สร้างคาบสองชุดที่จะค่อย ๆ เพี้ยนจากกัน

### backend: `today()` เลิกคืนโมเดลดิบ
โมเดลดิบไม่มี `display_title` (accessor ที่ไม่ได้ `$appends`) และหลุด `created_by`/`notes`/timestamps ออกไป
⇒ คืนรูปเดียวกับ `index()` · เทสต์ใหม่ 5 เคสรวมเคสที่ assert ว่าคอลัมน์ภายในต้องไม่หลุด

### หมายเหตุที่มีประโยชน์ต่อรอบหน้า
เทสต์ของสเตปนี้โดน **403 จากด่านใหม่ของ SC-S7** เพราะฟิกซ์เจอร์สร้างสมาชิกที่ไม่มี `academy_role_id`
⇒ ตั้งแต่นี้ไป เทสต์ที่ยิง `/schedules` ต้องผูกบทบาทที่ถือ `schedule.view` ให้สมาชิกเสมอ (ดูตัวอย่างใน
`SchedulePermissionTest::memberWithRole()`)

### ต่อไป: SC-S10 (ฟีเจอร์ที่โรงเรียนใช้จริง) และ SC-S11 (เทสต์ + migration G22)

---

## 2026-09-18 — ปิด G22: ลบ unique index ที่ไม่รู้จัก `status` ออกจาก `class_schedules`

### สถานะ: ✅ ปิดแล้ว · รายละเอียดใน `.agents/school-admin/11-schedule.md` §9

### การตัดสินใจ (เจ้าของโปรเจคเคาะ): ลบ unique ทิ้ง ใช้ตรรกะในแอปเป็นตัวกัน
`unique_teacher_schedule` / `unique_classroom_schedule` = (teacher|classroom, semester, day, **start_time**)
- กันได้แค่ "เวลาเริ่มตรงกันเป๊ะ" = เซตย่อยของการชนจริง (08:30–09:20 กับ 09:00–09:50 ชนกันแต่ index ไม่รู้)
- ไม่สนใจ `status` ⇒ **บล็อกข้อมูลที่ถูกต้อง**: คาบที่ยกเลิกแล้วยังจองเวลาเริ่มไว้ สร้างคาบแทนไม่ได้ (500)
⇒ ตัวกันจริงคือสูตร half-open ของ SC-S5 ที่รู้จัก `status` และมีเทสต์ 29 เคส
⇒ ใส่ index **ธรรมดา** ชุดคอลัมน์เดิมกลับไป (`sched_teacher_slot_idx` / `sched_classroom_slot_idx`)
   เพื่อไม่ให้แผนคิวรีของการตรวจชนเปลี่ยน
**แลกมาด้วย:** ไม่มีกำแพงระดับ DB กันสอง request ที่ยิงพร้อมกัน — ยอมรับได้เพราะการจัดตารางทำทีละคน
และของเดิมก็กันได้แค่เวลาเริ่มตรงกันอยู่ดี

### ข้อเท็จจริงที่ตรวจก่อนลงมือ (ไม่ได้เดา)
`class_schedules` ใน DB dev **ไม่มี foreign key เลยสักตัว** (ชื่อ index ที่ลงท้าย `_foreign` เป็นแค่ index
ที่เหลือจาก dump) ⇒ ลบ unique ได้โดยไม่ชน FK · index สำหรับ query มีครบอยู่แล้ว

### `down()` ที่ไม่ลบข้อมูลของโรงเรียนเอง
ถ้าย้อนกลับแล้วเจอแถวที่ unique เดิมห้ามไว้ (เช่นคาบที่ยกเลิก + คาบใหม่เวลาเดิม ซึ่งเพิ่งสร้างได้หลัง up)
migration จะ **หยุดพร้อมบอกจำนวนกลุ่มที่ขัด** ให้คนตัดสินใจก่อน — ทดสอบเส้นนี้จริงบน MySQL แล้ว
(rollback ถูกปฏิเสธ · สถานะยังเป็น `Ran` · สคีมาไม่ถูกแตะครึ่ง ๆ กลาง ๆ) แล้วค่อยลบคู่ที่ขัดออก
rollback ก็คืน unique เดิมครบทั้งสองตัว

### ต่อไป: SC-S11 (เทสต์ที่รันได้จริง + ตัดสินใจเรื่อง `tests/Api/SchoolManagementApiTest.php` ที่ตายมานาน)

---

## 2026-09-18 (ต่อ) — SC-S11: รันเทสต์บน MySQL จริงได้แล้ว → เจอบั๊ก production ทันที

### สถานะ: ✅ ปิด SC-S11 · รายละเอียดใน `.agents/school-admin/11-schedule.md` §9

### 🔴 บั๊กที่เจอ (G26) — การตรวจชนเวลาไม่ทำงานบน MySQL มาตลอด
`ClassSchedule::overlappingQuery()` เขียน `whereRaw('TIME(start_time) < TIME(?)', [...])`
บน **MySQL 8.4 + prepared statement จริง** `TIME(?)` คืน `00:00:00` เมื่อค่าที่ผูกมามีนาทีเป็น 00
(`'09:00:00'` → `00:00:00` ❌ · `'09:15:00'` → `09:15:00` ✅ · ลิเทอรัล `TIME('09:00:00')` → ถูกต้อง)
⇒ เงื่อนไขแรกเท็จตลอด ⇒ **คาบที่ตรงชั่วโมง (คือเกือบทุกคาบจริง) จองซ้อนครู/ห้อง/สถานที่กันได้เงียบ ๆ**
เทสต์ 29 เคสของ SC-S5 เขียวมาตลอดเพราะ **sqlite ไม่มีอาการนี้**
แก้เป็น `TIME(start_time) < ?` — หุ้ม `TIME()` เฉพาะฝั่งคอลัมน์ ห้ามหุ้ม placeholder

🔴 **กติกาใหม่สำหรับทั้งเรพ:** ห้ามหุ้ม placeholder ด้วยฟังก์ชันของ SQL (`TIME(?)`, `CAST(? AS TIME)`)
ให้ส่งค่าเป็นสตริงรูปที่ถูกไปตรง ๆ แล้วหุ้มฝั่งคอลัมน์แทน

### โปรไฟล์รันเทสต์บน MySQL จริง (ของใหม่)
```bash
php artisan test:db:rebuild            # คัดลอกโครงตารางจาก DB dev → nuxnan_testing (378 ตาราง)
php artisan test -c phpunit.mysql.xml  # รันบน MySQL จริง
```
· `TEST_DB_PREBUILT=1` ในโปรไฟล์นี้สั่งให้ `RefreshDatabase` ข้าม `migrate:fresh` เหลือแค่ transaction+rollback
· `tests/TestCase.php` มีด่านกันชื่อ DB ที่ไม่มีคำว่า "testing" — กันเทสต์เขียนทับ DB จริง
· โหมดเดิม (sqlite `:memory:` ผ่าน `phpunit.xml`) **ไม่ถูกแตะเลย**

### 🔴 G25 — สร้าง DB ใหม่จาก migration ทั้งชุด**ไม่ได้** (หนี้ข้ามเมนู ยังไม่ซ่อม)
สร้าง DB เปล่าแล้ว `migrate` → ตายที่ตัวที่ 9 (`1824 Failed to open the referenced table 'academies'`)
· FK อ้างตารางที่ยังไม่ถูกสร้าง **14 จุด**
· ตารางจริง **5 ตัวไม่มี migration สร้าง** (`adverts`, `advert_viewers`, `course_ratings`, `course_wishlists`, `user_daily_claim_counters`)
· **schema drift**: `users.personal_code` migration บอก `varchar(50) NULL` แต่ DB จริง `varchar(255) NOT NULL`
· ตาราง `migrations` มี 585 แถว แต่ไฟล์เหลือ 484
⇒ เจ้าของโปรเจคเคาะว่า **ยังไม่ซ่อมรอบนี้** — ทำทางลัด (คัดลอกสคีมา) ไปก่อน

### ไฟล์เทสต์ที่ตาย: ลบทิ้งแล้ว
`tests/Api/SchoolManagementApiTest.php` — `tests/Api/` ไม่ได้อยู่ใน testsuite ไหนเลย
⇒ `php artisan test` ไม่เคยเรียก · บังคับรัน = ล้ม 23/23 · 4 endpoint ไม่มี route แล้ว
· ทุกเคสเป็น `assertStatus(200)` เปล่า ๆ (ชนกับบทเรียน SC-S9: "200 ไม่ได้แปลว่าใช้งานได้")

### เกณฑ์ที่รันเอง
สวีทตารางเรียน 90 เคส — **sqlite 90/90** และ **MySQL จริง 90/90** (279 assertions ทั้งสองฝั่ง)

### ต่อไป: SC-S10 (พิมพ์/ส่งออกตาราง · UI bulk · คัดลอกข้ามภาคเรียน · สอนแทน/งดคาบ · ภาระงานครู) · หนี้ค้าง: G25

### ผล full suite (sqlite) หลัง SC-S11 — 1811 passed · เหลือ 3 เคสที่ล้มมาก่อนแล้ว
`CourseLifecycleServiceTest` ×2 + `CourseLifecycleTest` ×1 — ค้างอยู่กับความหมายเก่าของ
`courses.status = 4` ("ปิดรับสมัคร") ยืนยันแล้วว่าล้มบนโค้ดก่อน SC-S11 ด้วย ⇒ ไม่ใช่ของรอบนี้

**ผลพลอยได้ 1 บั๊กที่ UserFactory เปิดโปง** (`ae9dc66b`)
`PointsService::updateDailyLimits()` เทียบ `where('date', '2026-09-18')` ตรง ๆ
แต่ cast `date` เขียนลงเป็น `'Y-m-d H:i:s'` — MySQL ตัดเวลาทิ้งเองเพราะคอลัมน์เป็นชนิด `DATE`
แต่ SQLite เก็บทั้งสตริง ⇒ หาแถวเดิมไม่เจอ แล้ว insert ซ้ำจนชน `unique(user_id, date)`
โผล่เมื่อผู้ใช้คนเดียวได้แต้มสองรอบในคำขอเดียว (สาขา fallback ของการรับการสนับสนุน)
ที่ไม่เคยถูกรันเลย เพราะเดิม `personal_code` ว่าง ⇒ `where('personal_code', null)` กลายเป็น
`whereNull` แล้วไปแมตช์ผู้ใช้มั่ว ๆ เป็น suggester → แก้เป็น `whereDate()`
🔴 **บทเรียน:** `where('col', $maybeNull)` ของ Laravel กลายเป็น `whereNull` เงียบ ๆ —
เทสต์ที่ข้อมูลว่างจึงเดินคนละสาขากับของจริงได้

## 2026-09-18 (ต่อ) — บัตรนักเรียนที่ดาวน์โหลดไม่มีรูป + จัดวางหัวบัตรใหม่

### สถานะ: ✅ หน้า `/student-card/admin/students/{level}/{room}` ปุ่ม "ดาวน์โหลดบัตรนักเรียน" ใช้ได้จริงแล้ว

อาการที่ผู้ใช้แจ้ง: **บนจอรูปนักเรียนขึ้นปกติ แต่ไฟล์ PNG ที่ดาวน์โหลดไม่มีรูป**
พิสูจน์ด้วยการดัก `toDataURL` ตอนกดปุ่มจริง แล้ววัดพิกเซล: โซนรูป/โลโก้โปร่งใส 63–65%

### 🔴 Root cause — html2canvas ข้ามรูปข้าม origin ไปเงียบ ๆ

UI อยู่ `:3000` แต่รูปทุกใบมาจาก `:8000` (Laravel `asset()` คืน absolute URL เสมอ)
html2canvas 1.4.1 ที่ไม่ได้สั่ง `useCORS` จะ **ไม่วาดรูปข้าม origin และไม่ error**
ทางแก้ที่ลองแล้ว**ใช้ไม่ได้ทั้งคู่** (ทดสอบใน console ของหน้าจริง):
- `useCORS: true` เฉย ๆ → `/storage/*` ไม่มี header `Access-Control-Allow-Origin`
  แม้ `config/cors.php` จะใส่ `'storage/*'` ไว้ เพราะไฟล์ static ถูกเสิร์ฟตรงโดย artisan serve/Apache
  **ไม่เคยผ่าน middleware ของ Laravel**
- `allowTaint: true` → วาดได้แต่ canvas tainted แล้ว `toDataURL()` โยน SecurityError = ดาวน์โหลดพังทั้งใบ

⇒ ต้องทำให้รูป **เป็น same-origin** เท่านั้น

### 🔴 `ui/server/middleware/storage-proxy.ts` พังมาตลอด — ตอบ 200 แต่ body ยาว 2 ไบต์

proxy `/storage/**` ตัวนี้ `return response` ที่เป็น ArrayBuffer แล้ว **h3 แปลงเป็น JSON `{}`**
⇒ รูปทุกใบที่อ้าง path สัมพัทธ์ในเรพแตกเงียบ ๆ มานาน (landing badge, โลโก้ในหน้า print ฯลฯ)
แก้เป็น `$fetch.raw` + `Buffer.from(response._data)` + `send(event, buffer)`
และเลิก hardcode `http://localhost:8000` → อ่านจาก `useRuntimeConfig(event).public.apiBase`
(ไม่งั้น production พังแน่ เพราะ `www.nuxnan.com` ≠ `api.nuxnan.com`)

🔴 **กับดักที่กินเวลานานที่สุดในรอบนี้:** ใน template **ห้ามเขียน `<img src="/storage/xxx.png">` แบบ static**
Vue `transformAssetUrls` จะ import มันเป็น build asset แล้วหาไฟล์ใน `ui/public/` ถ้าไม่มีไฟล์จริงตรงนั้น
Vite จะ **404 ทั้งโมดูลของหน้านั้น** ทั้งที่ SSR ยัง render 200 → หน้าขึ้น "500 - Page Not Found"
โดยไม่มี syntax error ให้เห็น (`ui/public/storage/` มีจริงแต่มีแค่ badge/banner/landing)
⇒ ต้องผูกเป็น binding เสมอ (`:src="logoUrl"` โดย `const logoUrl = '/storage/...'`)
บันทึกไว้ใน memory `project-storage-url-traps` แล้ว

### 🔴 อีกหนึ่งอาการที่ผู้ใช้เจอ: "เปิดคนละโปรแกรมเห็นไม่เหมือนกัน"

ไม่ใช่บั๊ก — เป็น **ไฟล์คนละไฟล์** Chrome เซฟรอบสองเป็น `... (1).png` ไฟล์เก่าที่พังยังอยู่ชื่อเดิม
แกะทั้งสองไฟล์วัด alpha แล้ว: ไฟล์เก่าโปร่งใส 73.6% (โซนรูป/โลโก้ 100%) · ไฟล์ใหม่ทึบ 100%
ถึงอย่างนั้นก็เปลี่ยน `backgroundColor: null` → `'#ffffff'` ให้ PNG ทึบเสมอ
จะได้ไม่เพี้ยนตามพื้นหลังของโปรแกรมที่เปิด

⚠️ **ผลข้างเคียงที่ต้องรู้:** proxy เก่าส่ง `Cache-Control: max-age=31536000` มาพร้อม body 2 ไบต์
เบราว์เซอร์ที่เคยเปิดหน้านี้ก่อนแก้จะ **แคชรูปเสียไว้ 1 ปี** ต้อง hard refresh หนึ่งครั้ง
(ยืนยันแล้ว: `fetch` ปกติได้ 2 ไบต์ · `cache:'reload'` ได้ 234,454 ไบต์)

### จัดวางหัวบัตรใหม่ตามที่เจ้าของโปรเจคสั่งทีละสเต็ป (การ์ดกว้าง 1248 → export 7488×4608)

| องค์ประกอบ | ค่าสุดท้าย |
|---|---|
| แถบ "บัตรประจำตัวนักเรียน" | `top-[120px] right-[20px] px-[14px] pt-0 pb-[14px]` · 26/18px · `opacity-90` |
| โลโก้ | `mt-[34px]` (เดิม `mt-10`) |
| บล็อกชื่อ/ที่อยู่โรงเรียน | `-mt-[14px]` (เดิม `-mt-2`) |
| โซนเนื้อหา | `px-[2%] pt-[2.45%] pb-[2%]` (เดิม `p-[2%]`) |
| แถวข้อมูล 5 แถวล่าง | เพิ่ม `-mt-2` บีบรวม 36px |

ระยะที่วัดได้: แถบห่างเบอร์โทร 13px · โลโก้ห่างรูปนักเรียน 12px · Expiry Date ห่างแถบล่าง 24px

🔴 **กับดักที่ต้องจำ:** แถวหัวบัตรเป็น `flex items-center` — การเปลี่ยน margin-top ของลูก
**ขยับจริงแค่ครึ่งเดียว** ของค่าที่ใส่ (flex เฉลี่ยระยะกึ่งกลางใหม่) สั่งขึ้น 3px ต้องใส่ 6px
ดีไซน์ของแถบพอร์ตสัดส่วนมาจาก `ui/components/student-card/StudentCardItem.vue` (การ์ดหน้า public)

### เกณฑ์ที่รันเอง (ไม่เชื่อรายงาน agy)
- `git diff --stat` + อ่าน diff ทุกบรรทัด · compile SFC ด้วย `@vue/compiler-sfc` ผ่าน
- `curl localhost:3000/storage/...` → `200 image/jpeg 336790` (md5 ตรงกับไฟล์บนดิสก์) · ยิงขนาน 40 ไฟล์ ไม่มีเพี้ยน
- กดปุ่มดาวน์โหลดจริงในเบราว์เซอร์ทุกครั้งที่แก้ แล้ววัดพิกเซล: **ทึบ 100%** · โซนรูป 23,301 สี · โลโก้ 9,420 สี
- 375px: ไม่มี horizontal scroll (375/375) · โลโก้ 47/47 · รูปนักเรียน 43/44

### ของค้างที่ไม่ได้แก้รอบนี้ (ไม่ใช่ของใหม่)
1. นักเรียน 1 คนใน ม.2/6 (`11589.png`) DB ชี้ไปที่ไฟล์ที่ไม่มีบนเซิร์ฟเวอร์ — ปัญหาข้อมูล
2. **การ์ดบนจอ 375px อ่านไม่ได้** เพราะตัวอักษรเป็น px ตายตัว (`text-[46px]`, `w-[284px]`)
   ในกล่องที่กว้างตาม % — ทั้งหน้าไม่ล้นแนวนอน แต่ตัวการ์ดล้นทับกันเอง มีมาก่อนแล้ว
3. หน้านี้ตั้ง `requestFilter` เริ่มต้นเป็น `'with_request'` ห้องที่ไม่มีคำร้องจะเห็น
   "ไม่มีนักเรียนตรงตามตัวกรอง" ทั้งที่มีนักเรียน ต้องกด "ทั้งหมด" ก่อน

## 2026-09-20 — บัตรนักเรียน: การ์ดบนจอย่อด้วย transform + ลดขนาดไฟล์ที่ดาวน์โหลด

### สถานะ: ✅ ต่อจากงาน 2026-09-18 (`24fe66ce` / `3951bb94`) — หน้า `/student-card/admin/students/{level}/{room}`

### 🔴 ปัญหา: การ์ดบนจอสวยเฉพาะตอนกว้าง 1248px เป๊ะ ๆ

กล่องการ์ดกว้างตาม % (`w-full aspect-[1.95/1.20]`) แต่ทุกอย่างข้างในเป็น px ตายตัว
(`text-[46px]`, `w-[284px]`, `top-[113px]`) พอกล่องหดแต่ตัวอักษรไม่หด ทุกอย่างชนกันหมด
วัดจริงก่อนแก้ (ระยะแถบ "บัตรประจำตัวนักเรียน" ถึงเบอร์โทร · เนื้อหาที่ล้นพ้นขอบล่างการ์ด):

| กว้าง | แถบ ↔ เบอร์โทร | เนื้อหาล้น |
|---|---|---|
| 1248 | +12px | พอดี |
| 1100 | −103px | 67px |
| 984 (จอ 1024) | −259px | 346px |
| 375 | −192px | 1,044px |

### ทางแก้: เลย์เอาต์คงที่ 1248×768 แล้วย่อทั้งใบด้วย `transform: scale(k)`

- `k = gridWidth / 1248` วัดด้วย `ResizeObserver` ที่ grid ของการ์ด (ทุกใบใช้ค่าเดียวกัน)
- `transform-origin: top left` + wrapper สูง `768*k` พอดี ⇒ ไม่มีที่ว่างเหลือ ไม่มี horizontal scroll
- ค่า px ที่จูนหัวบัตรมาทั้งหมด **ไม่ต้องแตะเลยสักตัว** — บนจอทุกขนาดคือ "ภาพย่อ" ของไฟล์ที่ดาวน์โหลด

🔴 **กับดัก:** html2canvas จะ render ตาม transform ที่ติดอยู่บน element ⇒ ไฟล์จะเล็กตามจอ
แก้ด้วย `onclone` ล้าง transform บน **สำเนา** ที่มันโคลนไป + ล็อก `width/height` ไว้ที่ 1248×768
(บนจอจริงไม่กระพริบ เพราะไม่ได้แตะ DOM จริง)
ยืนยันแล้ว: กดดาวน์โหลดจากจอ 1280 / 1024 / **375** ได้ไฟล์เหมือนกันทุกประการ

### ลดขนาดไฟล์: `scale: 6` → `2`

ไฟล์เดิมใบละ ~9.5 MB (ดูจาก Downloads จริงของเจ้าของโปรเจค)

| | เดิม scale 6 | ตอนนี้ scale 2 |
|---|---|---|
| ขนาดภาพ | 7488×4608 | 2496×1536 |
| ไฟล์ PNG | ~9,300 KB | **1,959 KB** (−80%) |
| DPI ตอนพิมพ์เท่าบัตร CR80 (85.6mm) | 2,222 | **741** |

มาตรฐานงานพิมพ์ 300 DPI ⇒ ยังเหลือเกินสองเท่า · ครอปพิกเซล 1:1 ดูแล้วตัวอักษรยังคม ไม่มีขอบหยัก
ทางเลือกที่วัดไว้แล้วเผื่ออนาคต: `scale 1.5` ≈ 1.1 MB · `scale 1` ≈ 600 KB · JPEG q92 ที่ scale 2 = **567 KB**

### จัดหัวบัตรรอบสุดท้าย (ค่าที่เจ้าของโปรเจคเคาะทีละสเต็ป)

แถบ Student Card: `top-[113px] right-[20px] px-[14px] pt-0 pb-[19.5px] rounded-md` · 26/18px · `opacity-90`
บรรทัดไทยในแถบใส่ `-mt-[1.5px]` เพิ่ม

🔴 **บทเรียนเรื่องการจัดกึ่งกลางแนวตั้งกับฟอนต์ไทย:** `py-[9px]` เท่ากันบน-ล่าง **ตาเห็นไม่เท่า**
เพราะ Noto Sans Thai จอง leading เหนือตัวอักษรไว้เยอะกว่าใต้ (เผื่อสระบน/วรรณยุกต์)
สแกนพิกเซลในไฟล์ที่ export จริงได้: ช่องว่างเหนือตัวอักษร 26.3px แต่ใต้เหลือ 5.3px
ต้องชดเชยด้วย padding ที่ไม่เท่ากัน — หลังแก้วัดได้ 15.8 / 15.8 พอดี
**วิธีตรวจ:** สแกนแถวพิกเซลในภาพ export หาสีตัวอักษรบนพื้น `bg-blue-700` (อย่าเชื่อ `getBoundingClientRect`
ของ element เพราะมันคือกล่อง line-box ไม่ใช่ตัวอักษรจริง) — และอย่าลืมว่าบรรทัดที่มี `opacity-90`
จะไม่ใช่สีขาวสนิท (232,237,251) ต้องผ่อนเกณฑ์ตรวจสีให้ครอบคลุม

### ของค้าง (ไม่ใช่ของรอบนี้)
ห้อง ม.5/3 มี 4 คนที่ DB ชี้ไปยังไฟล์รูปที่ไม่มีบนเซิร์ฟเวอร์ (`2398.jpeg`, `2923.png`, `2924.jpeg`, `2925.jpeg`)
Laravel ตอบ 403 · proxy ตอบ 404 — ปัญหาข้อมูลชุดเดียวกับ `11589.png` ของ ม.2/6

### 🔴 ตัดสินใจแล้ว (2026-09-20): หน้าเพจกับไฟล์ที่ดาวน์โหลด "ไม่ตรงกัน" ถือว่าปกติ — ไม่ต้องแก้

ตัวหนังสือในไฟล์ export อยู่**สูงกว่า**ที่เบราว์เซอร์วาดบนหน้าเพจ ~24px (~3% ของความสูงการ์ด)
ส่วนรูปนักเรียน โลโก้ QR และกรอบสีทุกกรอบ ตรงกันสนิท — ตรวจแล้วไม่ใช่การสลับคน
(กดปุ่ม index 0/10/20 ชื่อไฟล์กับเนื้อในภาพตรงกับการ์ดบนหน้าทั้งสามใบ)

**สาเหตุ:** html2canvas คำนวณเส้นฐานตัวอักษรจาก `font-size` ไม่นับ half-leading ของ `line-height`
แต่เบราว์เซอร์นับ · ฟอนต์ไทยมี ascent สูงมาก ระยะเลยเพี้ยนเยอะ
วัดยืนยันแล้วด้วย ink ของเลข `5980`: เบราว์เซอร์ y 282.7 · ไฟล์ y 258.5

🔴 **กติกาที่ตามมา: ทุกครั้งที่จูนตำแหน่งบนการ์ด ให้วัดจาก "ไฟล์ที่ดาวน์โหลด" เท่านั้น**
หน้าเพจเป็นตัวอย่างคร่าว ๆ เจ้าของโปรเจคเคาะแล้วว่าไม่ต้องแก้
(ทางเลือกที่เสนอแล้วไม่เอา: ให้หน้าเพจโชว์ภาพที่ html2canvas เรนเดอร์แทน DOM ·
บีบ `line-height` ทุกบรรทัดให้เท่า font-size แล้วจูนใหม่ทั้งใบ)

## 2026-09-20 (ต่อ) — 🏁 ปิดงานบัตรนักเรียน (สรุปส่งต่อ)

### สถานะ: ✅ ปิดงาน · 5 commit · push ขึ้น origin/main แล้ว

```
24fe66ce  fix(ui): proxy /storage/** ส่งไบต์รูปจริง แทน JSON ว่าง 2 ไบต์
3951bb94  fix(ui): บัตรนักเรียนที่ดาวน์โหลดไม่มีรูป + จัดวางหัวบัตรใหม่
a49c3746  docs: worklog รอบแรก
a034edd0  fix(ui): การ์ดบนจอย่อด้วย transform + ลดไฟล์จาก 9.5MB เหลือ 2MB
cf2de7a5 / f53f32da  docs: worklog + ข้อสรุปเรื่องตำแหน่งตัวอักษร
```

ไฟล์ที่แตะทั้งงาน มีแค่ 2 ไฟล์: `ui/server/middleware/storage-proxy.ts` ·
`ui/pages/student-card/admin/students/[level]/[room].vue` (ฝั่ง Laravel ไม่ได้แตะเลยสักบรรทัด)

### ค่าพิกัดสุดท้ายของการ์ด (ระบบพิกัด 1248×768 — ใช้อ้างอิงถ้าต้องจูนอีก)

| จุด | ค่า |
|---|---|
| แถบ "บัตรประจำตัวนักเรียน" | กล่อง x 973–1227 · y 114–187 (สูง 73) · `top-[113px] right-[20px] px-[14px] pt-0 pb-[19.5px]` |
| ตัวอักษรในแถบ | 26/18px · `opacity-90` · บรรทัดไทยมี `-mt-[1.5px]` · ช่องว่างเหนือ/ใต้ ink = 15.8/15.8 |
| โลโก้ | `mt-[34px]` · ขอบบน y 16 |
| บล็อกชื่อ/ที่อยู่โรงเรียน | `-mt-[14px]` · ที่อยู่ y 104–150 สิ้นสุด x 960 (ห่างแถบ 13px) |
| โซนเนื้อหา | `px-[2%] pt-[2.45%] pb-[2%]` · บรรทัดแรก y 185 · บรรทัดท้าย y 711 (ห่างแถบล่าง 24px) |
| รูปนักเรียน | `w-[30%] h-[80%]` ไม่มี mt · y 185–631 (ห่างโลโก้ 12px) |
| ไฟล์ที่ได้ | 2496×1536 · PNG ~1.9MB · ทึบ 100% |

### ของค้างที่ยังไม่ทำ (ไม่ใช่บั๊กจากงานนี้)

1. **รูปนักเรียนหายจาก DB ที่ชี้ไฟล์ผิด** — ม.2/6 `11589.png` · ม.5/3 `2398.jpeg`, `2923.png`,
   `2924.jpeg`, `2925.jpeg` (Laravel ตอบ 403 เพราะไม่มีไฟล์จริง) ควรมีสคริปต์ตรวจทั้งระบบ
2. **ตัวกรองเริ่มต้นของหน้าเป็น `with_request`** ห้องที่ไม่มีคำร้องจะเห็น "ไม่มีนักเรียนตรงตามตัวกรอง"
   ทั้งที่มีนักเรียนเต็มห้อง ต้องกด "ทั้งหมด" ก่อน — ยังไม่ได้ถามเจ้าของโปรเจคว่าตั้งใจหรือไม่
3. **`Cache-Control: max-age=31536000` ใน storage-proxy** ยังอยู่ — เบราว์เซอร์ที่เคยโดน
   response พัง 2 ไบต์จะจำไว้ 1 ปี (ต้อง hard refresh ครั้งเดียว) ถ้าจะกันปัญหานี้ในอนาคตควรลดลง
4. **การ์ดยังไม่มีหน้าหลัง** — `StudentCardBack.vue` มีอยู่ใน `components/learn/student-card/`
   แต่หน้าแอดมินนี้ไม่ได้ใช้

### ถ้าจะทำต่อ — อ่านตรงนี้ก่อน
- จูนตำแหน่งบนการ์ด **วัดจากไฟล์ที่ดาวน์โหลดเท่านั้น** (เหตุผลอยู่หัวข้อก่อนหน้า)
- ค่า px ทุกตัวอ้างอิงระบบพิกัด 1248×768 เสมอ ไม่ต้องคิดเรื่องความกว้างจอ
- แถวหัวบัตรเป็น `flex items-center` — แก้ margin ได้ผลจริงครึ่งเดียวของค่าที่ใส่
- ห้ามเขียน `<img src="/storage/...">` แบบ static ใน template (Vite จะ 404 ทั้งโมดูล)

## 2026-09-21 — บัตรนักเรียน: ชื่อยาวตกบรรทัด · คำนำหน้าตามอายุ · วันที่ 2 ภาษาไม่ตรงกัน

### สถานะ: ✅ 2 commit — `1728e994` (api +2) · `1f9f7e3e` (ui +88/−30)

เคสที่เจ้าของโปรเจคแจ้ง: ม.4/4 รหัส 12446 `ซารีฟาฮ์นูรีอัน สาเหะอาแซ`
ชื่อยาวจนตกบรรทัด → แถวข้อมูลที่เหลือถูกดันลง → บรรทัดท้ายล้นออกนอกบัตร 58px

### 1. ชื่อต้องอยู่บรรทัดเดียวเสมอ

🔴 ของเดิม `studentThaiPrefixName()` **นับจำนวนตัวอักษร** (`fullLength > 20 → 42px`)
ซึ่งพลาดเพราะอักษรไทยกว้างไม่เท่ากัน — เคสนี้นับได้ 24 ตัว เลือก 42px แต่ยังล้นอยู่ดี
เปลี่ยนเป็น **วัดความกว้างจริงด้วย canvas `measureText`** เทียบกับที่ว่างจริง 505px
(วัดจากขอบขวาของ `:` ถึงขอบขวาคอลัมน์ในระบบพิกัด 1248)

ลำดับย่อ: คำนำหน้าเต็ม → คำนำหน้าย่อ (ด.ช./ด.ญ./น.ส.) → ลดขนาดทีละ 1px (พื้น 28px ไทย / 22px อังกฤษ)
+ `whitespace-nowrap` กันไว้อีกชั้น · บรรทัดอังกฤษก็ตกบรรทัดเหมือนกัน แก้ด้วยวิธีเดียวกัน

⚠️ `measureText` ต้องรอฟอนต์โหลดก่อน ไม่งั้นวัดด้วยฟอนต์ fallback แล้วได้ขนาดผิด
ใช้ `document.fonts.ready` เซ็ต ref แล้วอ้างถึง ref นั้นในฟังก์ชันเพื่อให้ Vue คำนวณใหม่

**ตรวจทั้งโรงเรียน 53 ห้อง 2,092 คน** (รันอัลกอริทึมเดียวกันกับข้อมูลจาก API ทุกห้อง):
ตกบรรทัด 0 คน · ต้องย่อขนาด ไทย 70 (3.3%) อังกฤษ 57 (2.7%) · เล็กสุดที่ใช้จริง ไทย 35px อังกฤษ 27px
**ไม่มีใครชนเพดานล่าง** · ทุกใบบรรทัดท้ายจบที่ y 711 ห่างแถบล่าง 24px เท่ากันหมด

### 2. คำนำหน้าเปลี่ยนเป็น นาย/นางสาว เองเมื่ออายุครบ 15 ปี

คำนวณจาก `birth_date` · 🔴 **ระหว่างทางเจอว่านักเรียน 482 คนไม่มี `title_prefix_th` เลย**
(ม.1/1 ห้องเดียว 7 คน) บัตรจึงไม่มีคำนำหน้า — แต่คอลัมน์ `students.gender` มีครบ
ตรวจแล้ว **ตรงกับคำนำหน้าเดิม 100% ไม่ขัดกันสักเคสใน 2,222 คน** (`1` = ชาย · `0` = หญิง)
⇒ เพิ่ม `gender` เข้า `RoomStudentResource` แล้วใช้เป็นแหล่งหลัก (คำนำหน้าเป็นตัวสำรอง)

ลำดับตัดสินว่าเป็นผู้ใหญ่: อายุจากวันเกิด → คำนำหน้าเดิมใน DB → ระดับชั้น (ม.4 ขึ้นไป)
(ไม่มีวันเกิด 492 คน · ไม่มี gender 227 คน)

### 3. วันเกิด/วันหมดอายุ 2 ภาษาไม่ตรงกัน

ไม่ใช่คนละวัน แต่ `en-US` เรียง **เดือน/วัน/ปี** ส่วนบรรทัดไทยเรียง วัน/เดือน/ปี
`06/07/2572` เทียบกับ `07/06/2029` เลยอ่านเป็นคนละวัน → เปลี่ยนเป็น `en-GB`
(ก่อนหน้านี้ไม่มีใครทักเพราะบัตรส่วนใหญ่วันที่ > 12 เช่น 15/05 ซึ่งสลับแล้วยังอ่านออก)

### เกณฑ์ที่รันเอง
- สวีปทั้ง 53 ห้องด้วยอัลกอริทึมจริง + เปิดหน้า ม.1/1 (43 ใบ) ม.4/4 (31 ใบ) ม.2/7 (45 ใบ) วัด DOM ซ้ำ
- กดดาวน์โหลดจริงกับเคสหนักสุดของโรงเรียน (ม.2/7 `ด.ช.มูฮัมหมัดรอมฎอน แวดือราแม` 35px/27px)
  ได้ไฟล์ 2496×1536 ครบทุกแถว ไม่มีอะไรล้น
- `./vendor/bin/pint --test` ผ่าน
