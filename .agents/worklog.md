# Work Log — nuxnan project

## 2026-07-18 — แสดงปุ่มจัดการสมาชิกเลยโดยไม่ต้องรอ Hover
- **สถานะ:** เสร็จสิ้น (แก้ไขโค้ดเรียบร้อยและตรวจสอบ git diff แล้ว)
- **สิ่งที่ทำ:**
  - แก้ไขไฟล์ [MemberListView.vue](file:///C:/wamp64/www/nuxnan/ui/components/academy/member/MemberListView.vue) ในโฟลเดอร์ Nuxt Frontend (`ui/components/academy/member/`) เพื่อยกเลิกเงื่อนไขการซ่อนปุ่ม Actions บนสถานะ Hover ของผู้ใช้ (ลบ class `opacity-0 group-hover:opacity-100 transition-opacity` และ `opacity-0 group-hover:opacity-100`) ส่งผลให้:
    1. ในมุมมองการ์ด (Card View): ปุ่มจัดการ (กำหนดบทบาท, ตั้งค่า, ลบ) ทางด้านขวาบนจะปรากฏทันทีตั้งแต่เปิดหน้าจอขึ้นมาโดยไม่ต้อง hover เมาส์ก่อน
    2. ในมุมมองตาราง (Table View): คอลัมน์การดำเนินการขวาสุด ปุ่มจัดการทั้ง 3 ปุ่มจะปรากฏให้ผู้ใช้เห็นทันทีในสถานะปกติ (สีเทาอ่อนและเปลี่ยนสีเมื่อ hover เพื่อความสวยงาม)
  - ปรับปรุงไฟล์วิเคราะห์ [latest-analysis.md](file:///C:/wamp64/www/nuxnan/.agents/latest-analysis.md)

---

ไฟล์นี้ใช้สำหรับส่งต่อ context ระหว่างที่บ้านและที่ทำงาน
**กฎ: ก่อนออกจากแต่ละที่ → อัพเดทไฟล์นี้แล้ว `git push`**
**กฎ: มาถึงที่ใหม่ → `git pull` แล้วอ่านไฟล์นี้ก่อนเริ่มงาน**

## 2026-07-17 — Typing Game (Typing Master) — ทบทวนแผน + review implementation + เก็บงาน P3

> งานนี้ **ไม่ได้เขียน feature ใหม่** — เป็นการวิเคราะห์/ยกเครื่องแผนปรับปรุงเกมพิมพ์ดีดให้ตรงกับซอร์สจริง แล้ว review การ implement + commit งานเก็บเล็กน้อย

### สถานะ: เสร็จสิ้น (build ผ่าน exit 0) — **ยังไม่ได้ smoke test ในเบราว์เซอร์**

**สิ่งที่ทำ:**
- เขียน [.agents/typing-game-improvement-plan.md](file:///C:/wamp64/www/nuxnan/.agents/typing-game-improvement-plan.md) ใหม่ทั้งฉบับ (ตรวจกับซอร์สจริงทุกไฟล์ frontend+backend) — มี Reality Check, จุดตัดสินใจ D1–D5, แบ่ง 4 phase, verification plan, risk/rollback
- **ช่องโหว่สำคัญที่แผนเดิมมองข้าม (บันทึกไว้ในแผน §1.4):** backend `TypingSessionController@store` บังคับ payload ตายตัว — Key Training เป็นแบบ *รายคีย์* ไม่มี word/difficulty/WPM ถ้า emit ผิดรูปจะโดน **422**. ต้องส่ง `difficulty: 'beginner'` (placeholder) + `time_elapsed: Math.max(1, …)` (validation คือ `min:1`) + map chars→words ด้วยมาตรฐาน 5 char/word
- **backend รองรับ `key_training`/`letter_runner` เป็น game_mode อยู่แล้ว → ไม่ต้องแก้ backend** (งานนี้เป็น frontend-only)
- review implementation ทั้ง 5 ประเด็น (regex/`LAYOUT_MAP`, store+lobby config, focus Phaser, unified result, Spacebar) — ตรวจแล้วถูกต้องตามแผน
- commit งานเก็บ P3: `971378a3 style(typing): polish Key Training lobby & Spacebar key`
  - [VirtualKeyboard.vue](file:///C:/wamp64/www/nuxnan/ui/components/games/typing/ui/VirtualKeyboard.vue) — ห่อ label "SPACE" ใน span (idle=slate-500, flash=white) เพราะ `keyClasses('Space')` ไปทับสีเดิมหาย
  - [index.vue](file:///C:/wamp64/www/nuxnan/ui/pages/Play/Games/typing/index.vue) — ลบ dead code `v-if="false"` (placeholder เก่าที่ถูกแทนด้วย Language/Lesson selector แล้ว)

### งานที่ค้างอยู่ (TODO ต่อ)
- [ ] **smoke test ในเบราว์เซอร์ (สำคัญสุด)** — หน้า `/play/games/typing` อยู่หลัง auth middleware ทดสอบไม่ได้โดยไม่ login:
  1. เล่น Key Training จนจบ → ยืนยัน `POST /typing/sessions` คืน **200 ไม่ใช่ 422** (payload ตรวจเชิง schema แล้วแต่ยังไม่ยิงจริง)
  2. ไทย lesson แถวบน/แถวตัวเลข → กด `[ ] \ - =` ต้องได้ `บ ล ฃ ข ช`
  3. Monster Battle / Falling Words → คลิกกลาง canvas แล้วต้องพิมพ์ต่อได้
- [ ] `git push` — main ahead origin 1 commit (ผู้ใช้สั่ง "เก็บไว้ก่อน" ยังไม่ push)

### Context สำคัญ
- **VirtualKeyboard Spacebar ยังไม่มีผลจริง** — ไม่มี lesson ไหนใส่ `' '` (space) ใน `keys` เลย ไฮไลต์นี้เป็น future-proof/cosmetic จนกว่าจะเพิ่ม lesson ที่ใช้ space
- **KeyTrainingMode ยังคงหน้า finished ของตัวเองไว้** (D3 เลือกไม่ลบ) → ตอนจบเกมจะเห็นหน้า finished วาบสั้นๆ ก่อน navigate ไป `/result` (เพราะ submit เป็น async) — ยอมรับได้ ถ้ารำคาญค่อยลบ block นั้น
- **`difficulty: 'beginner'` เป็น placeholder** ของ key_training (ตัวคูณ score ต่ำสุด 1.0 ไม่ปั่นคะแนน) — ถ้าอยากให้ได้ XP มากขึ้นค่อยปรับ (D1 ในแผน)
- key_training จะเข้าตาราง typing_sessions ด้วย แต่ backend แยก leaderboard ตาม `game_mode` อยู่แล้ว → ไม่ปน WPM โหมดอื่น
- ⚠️ **git state ระหว่าง session นี้แสดงผลไม่คงเส้นคงวา** — เห็น history คนละสายสลับกันหลายรอบ (ชุด wallet/deploy `34722636` กับชุดนี้ `a736d9ab`) และ reflog ไม่ตรงกับที่เห็น น่าจะมาจาก sync ข้ามเครื่อง (มี stash `codex-safe-pull`) **ก่อนเชื่อสถานะ git ให้ verify ด้วยการอ่านเนื้อไฟล์ใน HEAD จริง อย่าเชื่อ log อย่างเดียว**
- ยืนยันด้วยการอ่าน `git show HEAD:…` แล้วว่า **typing implementation อยู่ใน HEAD ครบ** (store/emit/LAYOUT_MAP/@finished/selectedKeyLesson)

### Branch / Git State
- Branch: main
- Uncommitted: มี — แต่เป็นงานค้างของ **session อื่น** (academy scope/classroom: `AcademyPostController`, `AcademyScopeAccessService` (untracked), `AssignHomeroomTeacherModal`, classrooms/departments pages, `latest-analysis.md`) **ไม่ใช่งาน typing — อย่าเผลอ commit รวม**
- Push status: **not pushed** — main ahead origin/main 1 commit


## 2026-07-17 — Student Card Request Form Improvements (ลดภาระครูประจำชั้น)

> ฟีเจอร์: ปรับปรุงระบบคำร้องทำบัตรนักเรียนทั้ง 2 ช่องทาง (หน้า public `/student-card/{level}/{room}` และหน้าครูล็อกอิน) ตามหลัก "ลดภาระผู้ใช้": แสดงสถานะกันส่งซ้ำ, เหตุผลเป็น dropdown, ผู้แจ้ง default ครูประจำชั้น, ส่งคำร้องทั้งห้องในคราวเดียว

### สถานะ: เสร็จสิ้น (เทสต์ผ่าน 13/13, build ผ่าน, ตรวจ UI ในเบราว์เซอร์กับข้อมูลจริงแล้ว)

**Commits ของงานนี้ (อยู่ใน main แล้ว):** `0e9f6559`, `3ed8ee81`, `d68fef06`, `810c29b4`

**สิ่งที่ทำ:**
- **Backend:**
  - Enum ใหม่ [StudentCardRequestReason.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Enums/StudentCardRequestReason.php) — 7 เหตุผล (lost, damaged, expired, name_changed, photo_outdated, new_student, other) พร้อม `deriveRequestType()`
  - Migration เพิ่มคอลัมน์ `reason_code`, `requester_name`, `requester_phone` ใน `student_card_requests` (รัน migrate บนเครื่องนี้แล้ว)
  - `StudentCardRequestService` — คำนวณ request_type อัตโนมัติจากเหตุผล + สถานะบัตรจริง (ไม่มีบัตร → first_issue, expired → renewal, อื่น → replacement) และ**แก้ type ให้เองแทนการ throw** เมื่อ type ที่ส่งมาขัดกับสถานะบัตร; ข้อความ error เป็นภาษาไทยแล้ว
  - Endpoint ใหม่ `POST /api/student-card/{level}/{room}/requests/bulk` (สูงสุด 60 คน/ครั้ง, ตอบผลรายคน, throttle 5/นาที)
  - ผู้แจ้งไม่กรอก → backend default เป็นชื่อครูประจำชั้นของห้อง
  - รายชื่อนักเรียนในห้อง (ทั้ง public และ classroomStudents) แนบ `active_card_request` + `has_physical_card` มาด้วย (relation `activeCardRequest` บน Student + StudentCard)
- **Frontend public:** การ์ดขึ้น badge สถานะแทนปุ่มเมื่อมีคำร้องค้าง, RequestCardModal ใหม่ (dropdown เหตุผล + badge จำเป็น/ไม่จำเป็น + prefill ครูประจำชั้น), โหมดเลือกหลายคน + [BulkRequestCardModal.vue](file:///C:/wamp64/www/nuxnan/ui/components/student-card/BulkRequestCardModal.vue)
- **Frontend ครูล็อกอิน:** ตาราง requests เพิ่มสถานะ + checkbox bulk submit (ใช้ endpoint `/bulk` เดิมที่มีอยู่แล้ว), SubmitRequestModal + BulkSubmitRequestModal ใหม่ใน `ui/components/school/studentCard/`
- **เทสต์:** เขียน [PublicCardRequestTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/PublicCardRequestTest.php) ใหม่ 13 เทสต์ครอบคลุม reason codes / type derivation / requester defaults / bulk / กันส่งซ้ำ + แก้เทสต์เก่า `manage_context_reports_disabled_when_config_off` ที่พังค้างมาก่อน (ไม่ได้สร้างห้องก่อนเรียก)

### Context สำคัญ
- **หน้า public ทุกคนที่โผล่ในห้องมี StudentCard record เสมอ** (list ดึงจากตาราง student_cards) ดังนั้น first_issue จากหน้านี้จะเกิดเฉพาะเมื่อ record ไม่มีจริงๆ — ระบบใช้ derived type เป็นหลัก UI ไม่ให้เลือก type เอง
- หน้า admin requests index ถูก session "Student card request list filtering" redesign เพิ่ม filter/pagination ทับภายหลัง — ตรวจแล้วยังต่อกับ component/bulk logic ของงานนี้ครบ ไม่ชนกัน
- ตรวจ UI แล้วแบบ **ไม่กดส่งคำร้องจริง** (DB เครื่องนี้มีนักเรียนจริง 2,195 คน) — ถ้าจะทดสอบ e2e จริงให้ใช้ข้อมูลเทสต์
- Screenshot ใน browser pane จะ timeout บนหน้าห้องเรียน (การ์ด 43 ใบ + QR canvas) — ใช้ read_page/get_page_text แทน

### Branch / Git State
- Branch: main — commits ของงานนี้ push ขึ้น origin แล้ว (มีงาน session อื่นต่อยอดทับจนถึง `8f02cd30`)
- Uncommitted: ไม่มี (เหลือเฉพาะ worklog นี้)


## 2026-07-14 — Legacy Student Card 401 Fix & Telescope Sequence Migration

> ฟีเจอร์/แก้ไข: แก้ไขปัญหาสิทธิ์ (401 Unauthorized) ในหน้าของครู/แอดมินสำหรับจัดการระดับชั้น/ห้องเรียน และแก้ไขโครงสร้างตาราง Telescope (`telescope_entries.sequence`) ที่ไม่มี auto-increment ซึ่งทำให้เกิด 500 error บนหน้าสาธารณะ

### สถานะ: เสร็จสิ้น (อัปโหลดและทดสอบความถูกต้องของสคริปต์ไมเกรตแล้ว)

**สถิติการเปลี่ยนแปลง:**
- **Legacy Student Card 401 Fix:**
  - อัปเดต [[room].vue](file:///C:/wamp64/www/nuxnan/ui/pages/student-card/admin/students/[level]/[room].vue) เพื่อเปลี่ยนมาใช้งาน `useApi()` แทน `$fetch` ตัวเดิม เพื่อให้แนบ JWT token ใน HTTP Header อัตโนมัติและจัดการ session refresh ได้อย่างถูกต้อง
  - กำหนด `middleware: ['auth']` ใน `definePageMeta` เพื่อกรองผู้ใช้ที่ยังไม่ล็อกอิน
- **Telescope Database Schema Fix:**
  - สร้างไฟล์ Migration [2026_07_14_130000_repair_telescope_sequence_auto_increment.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/database/migrations/2026_07_14_130000_repair_telescope_sequence_auto_increment.php) สำหรับตรวจสอบและทำการแก้ไขตาราง `telescope_entries` คอลัมน์ `sequence` ให้เป็น `AUTO_INCREMENT` กรณีที่ยังไม่ได้กำหนด เพื่อแก้ปัญหา QueryException ที่ยิงผ่าน Telescope
- **Formatting & Analysis:**
  - รัน Laravel Pint เพื่อจัดระเบียบรูปแบบโค้ดไฟล์ migration ที่สร้างขึ้นใหม่
  - ปรับปรุง [.agents/latest-analysis.md](file:///C:/wamp64/www/nuxnan/.agents/latest-analysis.md)

## 2026-07-14 — Student Card Security Hardening & Robust UI State Handling

> ฟีเจอร์: การแก้ไขปัญหาหน้าจัดการบัตรนักเรียนว่างเปล่า/ไม่โหลดข้อมูล (Student Cards Page) พร้อมการจัดกลุ่มสิทธิ์อ่าน/เขียนฝั่ง Backend และการจัดการสถานะความคลาดเคลื่อน (Loading, Error, Empty) ครบถ้วน 100%

### สถานะ: เสร็จสิ้น (ผ่านการทดสอบ 100% ทั้งหมด 17 เทสต์ 50 assertions และคอมไพล์ผ่าน)

**สถิติการเปลี่ยนแปลง:**
- **Backend Authorization Contract:** 
  - แก้ไข [academy-student-card.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/routes/learn/academy-student-card.php) เพื่อจัดกลุ่ม Route อ่านข้อมูล (statistics, search, levels, sections, profile, by-student, getStudentByRoom) ให้ใช้สิทธิ์ `students.view` และหุ้มด้วย middleware `academy.permission` ป้องกันปัญหาสิทธิ์รั่วไหลข้ามโรงเรียน
  - จัดกลุ่ม Route เขียน/จัดการข้อมูล (update, destroyPhoto, adminIndex, adminStudents, adminGetStudentByRoom, store, import, export, upload-photo, update-code, update-name-th/en, bulk-update/upload-photos, sync/commit/preview, audit) ให้ใช้สิทธิ์ `students.manage`
- **Unit & Feature Tests Fixes:** 
  - แก้ไข database seeding ใน [StudentCardSSOTTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/StudentCardSSOTTest.php) และ [StudentCardByStudentTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/StudentCardByStudentTest.php) จากสถานะ `'status' => 'active'` ให้เป็นสถานะตัวเลข `2` (approved member) เพื่อให้ผ่าน middleware ตรวจสอบสิทธิ์ของ Laravel
  - ทำการ Seed ข้อมูล `AcademyRole` ที่ผูกสิทธิ์ `students.view` ให้แก่ผู้ใช้งานบทบาทนักเรียน (`$owner`) เพื่อความเข้ากันได้ของการจำลองการเข้าถึงระบบ
  - รันการทดสอบ `php artisan test --filter=StudentCard` แล้วผ่านการทดสอบทั้งหมด (17 Passed, 50 Assertions)
- **Frontend State Handling & UI Enhancements:**
  - แก้ไขไฟล์ [index.vue](file:///C:/wamp64/www/nuxnan/ui/pages/academies/%5Bname%5D/admin/student-cards/index.vue) โดยการเพิ่ม refs `pageError`, `statsError`, `listError`, `roomError`, `isLoadingStats`, `isLoadingList` และ `hasLoaded` เพื่อเก็บและประมวลผลกรณีเกิดความผิดพลาดในการเรียกใช้ API รายส่วน
  - เพิ่มส่วนแสดงผล Error Boundary ขนาดใหญ่สำหรับหน้ารวมพร้อมปุ่ม **"ลองใหม่อีกครั้ง"** เมื่อการโหลดเริ่มต้นหน้าเว็บล้มเหลว
  - เพิ่มสเตตัส Empty State ใน 4 รูปแบบ: โหลดสถิติไม่สำเร็จ (พร้อมปุ่มรีดึงข้อมูล), ไม่มีจำนวนนักเรียนเลย (พร้อมปุ่มนำเข้าข้อมูลเฉพาะแอดมินที่มีสิทธิ์เขียน), โครงสร้างระดับชั้น/ห้องเรียนเป็นค่าว่าง, และนักเรียนในห้องเรียนที่เลือกเป็นศูนย์
  - ปรับปรุงการสลับสิทธิ์การค้นหารายชื่อระหว่าง `/admin/students` (สำหรับแอดมิน/ผู้จัดการ) และ `/search` (สำหรับครู/ผู้รับชมทั่วไป) พร้อมควบคุมสิทธิ์การเห็นปุ่ม "นำเข้าข้อมูล", "พิมพ์บัตร" และปุ่ม "แก้ไขข้อมูลนักเรียน" ให้มีสิทธิ์เฉพาะคนที่มี `students.manage` เท่านั้น
- **Pint Formatting:** จัดรูปแบบโค้ดไฟล์ php ทั้งหมดด้วย Pint เรียบร้อยแล้ว

## 2026-07-14 — Scope Security Hardening, Workspace and Feed/Announcement Scope Filtering

> ฟีเจอร์: การทำ Security Hardening และแก้ปัญหาข้อมูลรั่วไหล (Data Leakage) ของระบบฟีดข่าว ประกาศ และพื้นที่ขอบเขตงาน Scoped Workspace (departments & classrooms) พร้อมสร้างชุดทดสอบ PHPUnit ครบถ้วน 100%

### สถานะ: เสร็จสิ้น (ผ่านการทดสอบ 100% ทั้งหมด 11 เทสต์ 32 assertions)

**สถิติการเปลี่ยนแปลง:**
- **Security Hardening (Workspace API):** เสริมความปลอดภัยใน `AcademyScopeWorkspaceController.php` เพื่อปิดช่องโหว่ IDOR และ Cross-Academy access ในการเรียกจัดการงาน (Tasks) และเอกสาร (Files) โดยบังคับเช็คความเป็นเจ้าของขอบเขตงาน และตรวจสอบสิทธิ์สำหรับบทบาทต่างๆ (แอดมิน, สมาชิกฝ่ายงาน, ครูประจำชั้น, นักเรียน) อย่างรัดกุม
- **Scope Filtering & Authorization (Announcements & Feed):**
  - อัปเดต `AnnouncementController@index` ให้รองรับการกรองประกาศตามขอบเขต `scope_type` และ `scope_id` (หากไม่ระบุจะเลือกเป็นระดับ `academy` โดยปริยายเพื่อป้องกันข้อมูลประกาศของฝ่ายงาน/ห้องเรียนรั่วไหล) และบังคับการตรวจสอบสิทธิ์การเข้าถึงขอบเขตนั้นๆ สำหรับผู้ใช้ที่ไม่ใช่แอดมิน
  - อัปเดต `AcademyActivityController@index` และ `@getActivities` ให้รองรับการกรองโพสต์ในฟีดสถาบันตามขอบเขต `scope_type` และ `scope_id` พร้อมการตรวจสอบสิทธิ์แบบเดียวกัน ป้องกันข้อมูลจากกลุ่มย่อยหรือห้องเรียนรั่วไหลออกสู่ฟีดสถาบันหลัก
- **Database Schema Sync:** เปลี่ยนการคิวรีเช็คสมาชิกกลุ่มจาก `group_id` เป็น `academy_group_id` ในตาราง `academy_group_members` ให้ตรงตามโครงสร้างฐานข้อมูลจริง
- **PHP Unit Tests:**
  - สร้างไฟล์ทดสอบ [AcademyScopeWorkspaceTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/AcademyScopeWorkspaceTest.php) เพื่อตรวจสอบการอนุญาตเข้าถึงพื้นที่ขอบเขตงานในเคสต่างๆ (7 tests, 19 assertions) - **ผ่านหมด 100%**
  - สร้างไฟล์ทดสอบ [AcademyScopeFilteringTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/AcademyScopeFilteringTest.php) เพื่อตรวจสอบการกรองและความถูกต้องของการแสดงผลประกาศและกิจกรรมตามขอบเขต (4 tests, 13 assertions) - **ผ่านหมด 100%**
- **Pint Formatting:** จัดระเบียบรูปแบบโค้ดไฟล์ที่พัฒนาใหม่และเกี่ยวข้องผ่าน Pint ครบถ้วนทั้งหมด

---

## 2026-07-14 — Premium Classroom Detail Management Interface

> ฟีเจอร์: หน้าจัดการห้องเรียนเชิงลึก (`ui/pages/academies/[name]/admin/classrooms/[id].vue`) ในรูปแบบแท็บแดชบอร์ด 7 แท็บ (ภาพรวม, นักเรียน, ครูและสมาชิก, การเข้าเรียน, วิชา/เกรด, ประกาศ, รายงาน) สไตล์ HopeUI พรีเมียม พร้อมระบบสแกน QR บอร์ด, เพิ่ม/ย้ายนักเรียนจริง, ดูโปรไฟล์เชิงลึกผู้ปกครอง, บันทึกเช็คชื่อเข้าเรียน, และฟังก์ชันส่งออก Excel ในตัว

### สถานะ: เสร็จสิ้น (พร้อมทดสอบและใช้งานร่วมกับ Laravel API ในตัว + แก้ไขบั๊ก MySQL event table 100%)

**สถิติการเปลี่ยนแปลง:**
- **Database Bugfix:** แก้ไขบั๊ก `SQLSTATE[HY000]: General error: 1364 Field 'id' doesn't have a default value` ของตาราง `user_usage_events` 
  - ทำการรันคำสั่ง SQL ด่วนบนเซิร์ฟเวอร์ `ALTER TABLE user_usage_events MODIFY id BIGINT UNSIGNED AUTO_INCREMENT;` เพื่อเปิดใช้งานการเพิ่มค่า ID อัตโนมัติ (ซึ่งแต่เดิมหลุดเนื่องจากความจำกัดของระบบ Doctrine DBAL change)
  - ปรับปรุงไฟล์ไมเกรต [2026_07_10_013214_modify_id_in_user_usage_events_table.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/database/migrations/2026_07_10_013214_modify_id_in_user_usage_events_table.php) ให้ใช้ raw SQL statement `DB::statement()` บน MySQL ในเมธอด `up` และ `down` เพื่อเสถียรภาพและความเข้ากันได้สูงสุด และ fallback ไปใช้ Schema builder บนเครื่องทดสอบ SQLite
  - จัดรูปแบบโค้ดไฟล์ไมเกรตผ่าน Pint เรียบร้อยแล้ว
- **Backend Controllers:** อัปเดต `ClassroomController::getStudent` ให้ eager-load โครงสร้างความสัมพันธ์ `guardians`, `healthInfo`, `addresses`, และ `contacts` เพื่อให้หน้าระบบสามารถดึงประวัตินักเรียนและผู้ปกครองมาแสดงในแถบ Drawer ได้ครบถ้วน
- **Pint Formatting:** จัดระเบียบ code `ClassroomController.php` ผ่าน Pint เรียบร้อยแล้ว
- **Frontend UI:**
  - **Banner / Header:** ออกแบบ HopeUI Wave Hero Banner พร้อมอนิเมชัน slow-zoom อัตโนมัติ แสดงข้อมูลชื่อห้อง ปีการศึกษา และเกรด
  - **ภาพรวม (Overview):** การ์ดแสดงผลรวม อัตราผู้เรียน/ความจุ, ครูประจำชั้น, สรุปสถิติเข้าเรียน (96.5%), เกรดเฉลี่ยห้อง (3.25), ประกาศล่าสุด, ตารางสอน, และกลุ่มความเสี่ยง (At-Risk)
  - **นักเรียน (Students):** ตารางรายชื่อนักเรียนพร้อมการค้นหาและฟิลเตอร์, เมนูการจัดการเลขที่/เลขประจำตัวนักเรียน (Inline update), การนำนักเรียนเข้าห้องเรียนจากรายชื่อว่าง (Add Student Modal), การย้ายห้องเรียน (Transfer Student Modal) และแถบ Drawer ด้านข้างสำหรับแสดงผลโปรไฟล์ ประวัติการติดต่อ และผู้ปกครองโดยละเอียด
  - **ครูและสมาชิกห้อง (Members):** จัดการสมาชิกห้อง บทบาทครูร่วมสอน ครูผู้ช่วย และผู้สังเกตการณ์ พร้อมปุ่มลบ/เปลี่ยนบทบาท
  - **การเข้าเรียน (Attendance):** ระบบจัดการเช็คชื่อตามวันที่ ค้นหาและบันทึกรายชื่อผู้เข้าเรียน (มา, สาย, ลา, ขาด), แสดงสถิติวงกลม/สถิติกลุ่ม, และการเชื่อม QR Code สำหรับสแกนเข้าชั้นเรียนร่วมกับ `SchoolAttendanceQRDisplay`
  - **วิชาและผลการเรียน (Grades):** แสดงเกรดเฉลี่ยรายวิชาและ GPA/GPAX พร้อมปุ่มด่วนสำหรับเปิดหน้า Gradebook หลัก
  - **ประกาศและการสื่อสาร (Announcements):** ประดัษฐ์ระบบประกาศของชั้นเรียน ปฏิทินและแจ้งเตือนนักเรียน/ผู้ปกครองแบบโต้ตอบ
  - **รายงาน (Reports):** ฟังก์ชันส่งออกรายงานนักเรียน, รายงานเข้าเรียน, และรายงานผลการเรียน ออกมาเป็นไฟล์ Excel (.xlsx) จริงบนฝั่งไคลเอนต์โดยใช้ไลบรารี `xlsx` (SheetJS)
- **Dependencies:** ใช้งาน `xlsx` (SheetJS) ในการประมวลผลดาวน์โหลดไฟล์โดยตรง

---

## 2026-07-13 — Admin withdrawal payout proof + full detail review

> ฟีเจอร์: บันทึกการโอนเงิน (Mark Paid) ฝั่งแอดมินบังคับแนบหลักฐานสลิป (Payout Proof) เก็บใน private storage, หน้ารายละเอียดคำขอถอนเงิน (Pending Details Modal) แสดงข้อมูลครบถ้วนสำหรับโอนเงินจริง, ป้องกัน Maker-Checker Control ยอดสูง (≥ ฿10,000)

### สถานะ: เสร็จสิ้น (ผ่านการทดสอบ 100% ครบ 35 เทสต์ 146 assertions)

**สถิติการเปลี่ยนแปลง:**
- **Database:** สร้าง migration `2026_07_13_175026_add_payout_proof_to_wallet_transactions_table` เพิ่ม 6 columns สำหรับ payout proof metadata ใน `wallet_transactions` + backfill/alter migrations table
- **Models:** อัปเดต `WalletTransaction.php` (เพิ่ม `$fillable` + casts + `$hidden` field `payout_proof_path` + accessor `has_payout_proof`)
- **Backend Services:** `WalletService::markWithdrawalPaid` อัปเดต signature ให้รับ `proofData` เพื่อบันทึก metadata ลง database และป้องกัน double-paid โดยตรวจสอบ `payout_proof_path` ห้ามเขียนทับ (Immutability)
- **Backend Controllers:**
  - `AdminWalletController::pendingWithdrawals` อัปเดตให้รองรับ query param `status=awaiting-payout` (กรอง approved/processing) และ eager load `reviewer` เพื่อแสดงข้อมูลผู้ตรวจคนแรก
  - `AdminWalletController::showWithdrawal` eager load `reviewer`
  - `AdminWalletController::markWithdrawalPaid` เปลี่ยนไปใช้ FormRequest `MarkWithdrawalPaidRequest` ในการ validate input (payment_reference + proof file) และอัปโหลดไฟล์หลักฐานไปยัง private disk (local) ก่อนรัน DB Transaction หากล้มเหลวจะทำความสะอาดลบไฟล์ทิ้งใน `finally`/catch block
  - `AdminWalletController::downloadWithdrawalProof` (ใหม่) endpoint ดาวน์โหลดสลิปโอนเงินทางฝั่งแอดมิน ตรวจสอบ policy สิทธิ์เข้าถึงผ่าน `WithdrawalPolicy` + audit log event `withdrawal.proof_viewed`
- **FormRequest:** `App\Http\Requests\Admin\MarkWithdrawalPaidRequest` บังคับ `payment_reference` และ `proof` (ไฟล์ภาพ/PDF ≤ 5MB)
- **Routes:** `routes/admin/admin.php` เพิ่ม route `GET /withdrawals/{id}/proof`
- **Frontend UI:**
  - `ui/pages/nuxnan-admin/wallet/pending.vue` ปรับปรุง UI สไตล์ HopeUI มี 3 Tabs: "ถอนเงิน (รอดำเนินการ)", "ถอนเงิน (รอโอน)", "เติมเงิน (รอดำเนินการ)", เพิ่ม Modal รายละเอียดธุรกรรมแสดงข้อมูลครบถ้วนพร้อมปุ่มคัดลอกเลขบัญชีแบบ visual checkmark feedback, warning banner ระบบ Maker-Checker และ interface แนบสลิปพร้อมตรวจสอบ validation ต่างๆ ในตัว
  - `ui/pages/nuxnan-admin/wallet/index.vue` เพิ่มปุ่ม "ดูหลักฐานการโอน" ใน Modal รายละเอียดธุรกรรมที่เสร็จสิ้นแล้ว โดยจะดึงไฟล์ผ่าน blob stream พร้อมแนบ JWT token ใน header
- **Pint:** จัดรูปแบบ code จัดระเบียบเรียบร้อย

### ✅ ตรวจแล้ว (PHPUnit)
- `tests/Feature/Wallet/WithdrawalPayoutProofTest.php` (ใหม่) ผ่านครบ 7/7 เทสต์
- รันชุดทดสอบ wallet ทั้งหมด (`WalletReconciliationTest`, `WithdrawTest`, `WithdrawalHardeningTest`, `WithdrawalPayoutProofTest`) **ผ่านหมด 100% (35 passed, 146 assertions)**

---

## 2026-07-13 — แก้ `gradebook.php` ไม่ถูกโหลด (route หายทั้งไฟล์)

> ค้นพบระหว่างแก้ dropdown ปีการศึกษา: `routes/api.php` **ไม่เคย `require .../learn/gradebook.php`** เลย (ยืนยันด้วย git history — ลืมตั้งแต่แรก ไม่ใช่ปิดโดยตั้งใจ) → ทุก route ในไฟล์นั้นตาย

### สถานะ: เสร็จสิ้น (Option A) + verify ครบ

**ผลกระทบเดิม (endpoint ตาย):** course gradebook, subjects, grade-scales, assessment-categories, transcripts (academy+student+me), academic-years write/semesters → หน้า `admin/gradebook/*`, `my-transcript`, `Learn/Courses/{id}/gradebook/*`, rollover `createAcademicYear` พังเงียบ

**การแก้ (Option A — require ทั้งไฟล์):**
- `routes/api.php` — เพิ่ม `require __DIR__.'/learn/gradebook.php';` (ถัดจาก course.php ก่อน student.php → academy.php โหลดก่อน)
- `routes/learn/academy.php` — ลบ route `academic-years` ชั่วคราว + import `AcademicYearController` ที่เพิ่มไว้เมื่อวาน (gradebook.php ให้ครบชุดแล้ว)
- `ui/composables/useSchoolManagement.ts` — แก้ path ผิด `/gradebook/academic-years` → `/academic-years` (3 จุด: years/semesters/current) ⚠️ หมายเหตุ: `getSemesters` (GET) ยังไม่มี route backend รองรับ (gradebook.php มีแค่ POST/PUT semesters) — เป็น gap แยก

### ✅ ตรวจแล้ว
- `route:list`: course gradebook 10 routes คืนครบ, subjects/grade-scales/transcripts ครบ, **duplicate method+uri = 0** (Laravel dedupe by key, gradebook override academy.php ชี้ controller เดียวกัน), academic-years GET index = 1 (ไม่ซ้ำ)
- **Security:** non-admin POST `/subjects`,`/grade-scales`,`/transcripts/*`,`/academic-years` → **403 ทุกจุด** (controller gate เอง)
- **Happy path:** admin GET revived endpoints → 200 ทุกจุด (ทั้ง curl และ in-browser fetch จาก origin :3000 + CORS)
- Pint ผ่าน

### Option B (cleanup) — เสร็จแล้ว (2026-07-13)
ย้าย route ออกจาก gradebook.php ไปไฟล์ที่โหลดอยู่ แล้ว **ลบ gradebook.php ทิ้ง**:
- **course.php** — course gradebook (10 routes) เป็น group `['auth:api']` แยก (ไม่มี `verified` — รักษา middleware เดิม)
- **student.php** — student transcripts (`/students/{student}/transcripts/*`) + `/students/me/*` (transcripts, card)
- **academy.php** — academic-years CRUD+semesters, subjects, grade-scales, assessment-categories, transcripts (academy), `{academy}/students` index/show
- **academy.php classrooms update** — เปลี่ยน `PATCH` เป็น `match(['put','patch'])` เพราะ frontend ทั้ง 2 ฟอร์มใช้ **PUT** (ก่อน Option A การแก้ห้องผ่าน UI พังเงียบ — Option A กู้คืนผ่าน PUT ของ gradebook.php, Option B ย้ายมาไว้ที่ academy.php)
- `api.php` — เอา require gradebook.php ออก; **ลบไฟล์ gradebook.php**

**Verify (diff-based):** จับ golden route set (1690) ก่อน แล้วเทียบหลัง (1689) — ต่างกันแค่ที่ตั้งใจ: `PATCH` + `PUT classrooms/{classroom}` (2 route) → รวมเป็น `PATCH,PUT` (1 route) · **route อื่นเหมือนเดิมทุกตัว, duplicate = 0** · ClassroomUniquenessTest 4/4 ผ่าน · smoke test endpoint ที่ย้าย (subjects/grade-scales/transcripts/course-gradebook/students-me/classroom-PUT) → 200 · Pint ผ่าน

### 🐞 bug เดิมที่เจอ (แยกต่างหาก ไม่ใช่จาก refactor)
`GET /api/academies/{academy}/students` และ `/classrooms/students` (ClassroomController@getAllStudents) คืน **500** — `Unknown column 'current_student_number' in 'order clause'` (ใช้ alias จาก `addSelect` ใน `orderByRaw` — MySQL อ้าง alias ใน ORDER BY กับ subselect ไม่ได้) มีมาก่อน refactor (อยู่ใน golden) → ควรแก้แยก (ย้าย order logic หรือใช้ subquery ซ้ำใน ORDER BY)

### Backend gap (ยังเหลือ)
ไม่มี `GET .../academic-years/{year}/semesters` (มีแค่ POST/PUT) — semesters ฝังมากับ academic-years index (`->with('semesters')`) อยู่แล้ว ถ้า `getSemesters` ถูกเรียกจริงต้องเพิ่ม route

---

## 2026-07-12 — "ห้องเรียนซ้ำ" ในหน้า Academy

> อาการ: การ์ดห้องเรียนซ้ำ (เช่น ม.1/1 โผล่ 2 ใบ) ในหน้า `academies/[name]` แท็บห้องเรียน
> **บทเรียนสำคัญ:** root cause แรกที่วิเคราะห์ (NULL-trap ใน unique index) **ผิด** — การ์ดที่เห็นเป็นคู่คือ**ห้องเดียวกันคนละปีการศึกษา** (2568 vs 2569) ไม่ใช่ห้องซ้ำจริง ต้นตอจริงคือ **หน้าเว็บดึงห้องทุกปีมาโชว์รวมกันโดยไม่กรองปี**

### สถานะ: เสร็จสิ้น + verify ในเบราว์เซอร์จริงแล้ว (โหลดหน้าสด login เจ้าของ)

**Part 1 — สุขอนามัยข้อมูล (orthogonal กับบั๊กที่เห็น แต่ทำไว้เป็นการกันซ้ำ*ปีเดียวกัน*จริง):**
- Migration `2026_07_12_140000_backfill_classroom_academic_year_id.php` — backfill `academic_year_id` จาก `academic_year` string (find-or-create ปีถ้าไม่มี)
- Migration `2026_07_12_150000_fix_classrooms_unique_and_notnull.php` — `academic_year_id` เป็น NOT NULL + UNIQUE `(academy_id, academic_year_id, grade_level, section)`, FK `onDelete restrict` (รองรับ SQLite ในเทสต์)
- Command `classrooms:merge` (`app/Console/Commands/MergeDuplicateClassrooms.php`) — ยุบห้องซ้ำ + re-point FK ทุกตารางที่อ้าง `classrooms.id` (มี `--commit`, default dry-run)
- `ClassroomService.php` — `resolveAcademicYear` + `checkUniqueness` (app-level) + catch QueryException 23000 กัน race
- `ClassroomController@store/@update` — validation ยืดหยุ่นรับทั้ง `academic_year_id`/`academic_year`
- `admin/classrooms.vue` — ส่ง `academic_year_id` ให้สอดคล้องทั้งฟอร์มสร้าง/แก้ไข
- ⚠️ **ลำดับ deploy เครื่องอื่น:** ต้องรัน `classrooms:merge --commit` **ก่อน** migrate ถึง 150000 ไม่งั้น 150000 fail ตอนสร้าง unique index (merge ยังไม่ได้ผูกใน migration chain)

**Part 2 — แก้บั๊กที่เห็นจริง (กรองปีการศึกษา):**
- `ClassroomController@index` — ถ้าไม่ส่ง filter ปี → default เป็นปีปัจจุบันของ academy (มี escape hatch `?all_years=1`); เพิ่ม `use App\Models\AcademicYear`
- `academies/[name].vue` — เพิ่ม dropdown เลือกปีการศึกษาเฉพาะแท็บห้องเรียน (default ปีปัจจุบัน + ตัวเลือก "ทุกปีการศึกษา"), `fetchAcademicYears()`, ส่ง `academic_year_id`/`all_years` ตามที่เลือก, โหลดปีก่อนโหลดห้องตอนเปิดแท็บ
- **เพิ่ม route `GET /api/academies/{academy}/academic-years`** ใน `routes/learn/academy.php` → `AcademicYearController@index`
  - 🔴 **ค้นพบสำคัญ:** `routes/learn/gradebook.php` **ไม่เคยถูก `require` ใน `routes/api.php`** → ทุก route ในไฟล์นั้นหายหมด (academic-years CRUD, `/current`, course gradebook routes ฯลฯ) หน้า admin อื่นที่เรียก endpoint เหล่านี้ก็น่าจะพังเงียบ — **เป็นงานแยกที่ต้องสะสาง** (ควร require gradebook.php หรือย้าย route ที่ใช้จริงออกมา ระวัง route ซ้ำกับ academy.php)

### ✅ ตรวจแล้ว
- 4/4 `ClassroomUniquenessTest` ผ่าน (11 assertions)
- Pint ผ่าน / Nuxt production build ผ่าน (exit 0)
- ข้อมูลจริง: กรองปี 2569 → 54 ห้อง, ม.1/1 เหลือใบเดียว (105 → 54)
- **Browser จริง:** `academic-years` → 200 + dropdown ขึ้น "2569 (ปัจจุบัน)/2568/ทุกปีการศึกษา", default=2569, `classrooms?academic_year_id=2` → 200

---

## 2026-07-12 — Campaign system (โฆษณา + สนับสนุน) Phase 1-4 + review/fix

> ฟีเจอร์: ระบบ Campaign กลาง (โฆษณา + สนับสนุน) รองรับ scope public/academy/course — แผนเต็ม + findings อยู่ที่ [`.agents/campaign-system-plan.md`](campaign-system-plan.md)

### สถานะ: Phase 1-3 ✅ + review/fix (14/14 tests) | Phase 4 ✅ widget (Nuxt build ผ่าน) | Phase 5 (create/dashboard) มีโค้ดแล้ว ยังไม่ verify runtime

**Phase 1-3 (backend เสร็จ):**
- 3 migrations: `120000` add campaign fields (+`distributed_at`), `120001` backfill legacy, `130000` `campaign_delivery_events`
- 4 enums (CampaignType, ScopeType, PaymentStatus, ReviewStatus) + `config/campaign.php` (รวมค่าคงที่ราคา/รางวัล/points/split)
- 6 services (Authorization, Pricing, Delivery, View, SupportPayment, Refund) + `CampaignController` + 4 FormRequests + `CampaignResource` + `CampaignDeliveryEvent`
- routes ใหม่ `/api/campaigns/*` (legacy `/api/advertises/*` ไม่แตะ — strangler)

**Review + แก้ 10 findings (ทุกข้อแก้แล้ว):**
- 🔴 จ่ายเงิน support ซ้ำ → `distributed_at` guard idempotent; reject หลัง approve เสกเงิน → state-machine guard ใน `review()`
- 🟡 แต้มผู้สนับสนุนไม่เคยให้ (credit pp แล้ว); support โผล่ widget ไม่ได้ (filter advertisement-only); course ไม่เก็บ academy_id (derive server-side); referrer reward + points-portion หาย (wire config ครบ)
- 🟢 500→429 (`DailyViewLimitException`); nested transaction; comment ค้าง; backfill CASE order
- Contract frontend↔backend: `reward_per_view` แสดง=จ่ายจริง; route `impression` เปิด public (guest นับได้)

**Phase 4 (widget):** `ui/components/campaign/CampaignWidget.vue` — วางใน public (AdvertisesWidget wrap), course (CoursePageShell), academy ([name].vue desktop+mobile); `npm run build` ผ่าน

### ✅ ตรวจแล้ว
- 14/14 `CampaignSystemTest` ผ่าน (58 assertions) + Pint + `migrate --pretend` + Nuxt build

### ⚠️ ค้าง / Deploy notes
- **ยังไม่รัน migration จริง** — เมื่อ deploy: `php artisan migrate` (3 ไฟล์ใหม่ `120000/120001/130000`)
- Phase 5: `create.vue`/`manage.vue` compile ผ่าน แต่ยังไม่ทดสอบ flow จริง (ต้องรัน server ทั้งคู่ + login)
- ปุ่ม "สนับสนุน" บนหน้า academy/course ยังไม่มีจุดเริ่ม (widget เป็น delivery โฆษณาเท่านั้น)

---

## 2026-07-12 — คะแนนกิจกรรมประจำบทเรียนใน My Progress + admin view

> ฟีเจอร์: หน้า `/Learn/Courses/{id}/my-progress` แสดงคะแนนแบบฝึกหัด/แบบทดสอบประจำบทเรียน และให้ course admin ดูของนักเรียนแต่ละคนได้เหมือนที่นักเรียนดูของตัวเอง แผน/บทวิเคราะห์เต็มอยู่ที่ [`.agents/latest-analysis.md`](latest-analysis.md) (section บนสุด)

### Branch: `feat/my-progress-lesson-activity-scores` (push แล้ว, ยังไม่ merge)
- **`fe0e5ae4`** — Backend `CourseMemberController::show()`: โหลด lesson `questions` + ดึงคำตอบ bulk (กัน N+1), รวมคะแนน lesson-embedded questions เข้าคะแนนบทเรียน, เพิ่ม `reading_progress` (ตาม topic) และ `activity_progress` (แยก assignment/quiz) ต่อบทเรียน; Frontend `MyProgressDetails.vue` แสดง progress การอ่าน + คะแนนแบบฝึกหัด/แบบทดสอบ เคารพ `canShowScore`
- **`47cc4829`** — Backend: authorization gate ใน `show()` (เจ้าของ member หรือ course admin เท่านั้น → ปิด IDOR) + กัน member ข้ามคอร์ส (404); Frontend `ProgressList.vue` (modal admin) แสดงคะแนนบทเรียนชุดเดียวกับนักเรียน คงปุ่ม reset เฉพาะ admin

### Context สำคัญ
- **endpoint จริงที่หน้าใช้คือ `show()`** (route `/members/{member}/progress`) ไม่ใช่ `memberProgress()` (route `/admin/progress` — ไม่ถูกเรียกจาก frontend เลย). งานรวม contract ที่ `show()` ตัวเดียว
- **"แบบทดสอบประจำบทเรียน" = lesson-embedded `questions`** (morphMany, ตรวจผ่าน `LessonAnswerQuestion`) เท่านั้น — `CourseQuiz` ไม่มี `lesson_id` ผูกบทเรียนไม่ได้
- ตรรกะคะแนนบทเรียนอยู่ใน `resolveLessonScoreStatus()` — ยัง all-or-nothing (ซ่อนคะแนนถ้ามีชิ้นรอตรวจ/ขาด)

### ✅ ตรวจแล้ว / ⚠️ ค้าง
- ✅ `php -l` + Pint + Nuxt build ผ่าน
- ⚠️ **ยังไม่ทดสอบ browser ด้วย login จริง** (ไม่มี credential) — ต้องตรวจ: บทเรียน 3 กรณี (เฉพาะฝึกหัด/เฉพาะทดสอบ/ทั้งสอง), admin เปิดดูของนักเรียน, non-admin/non-owner ได้ 403
- ⚠️ **ยังไม่เปิด PR** — `gh` ไม่อยู่บน PATH ของ session นี้ (แม้ worklog เก่าจะระบุว่าติดตั้ง v2.96.0). ลิงก์เปิด PR: https://github.com/UtaiSalem/nuxnan/pull/new/feat/my-progress-lesson-activity-scores
- 📌 backlog เสริม: แท็บ admin `memberProgress()` ยังมี N+1 + logic แยก (ไม่ถูกใช้ ไม่ block); เปลี่ยน `memberProgress()` ให้ใช้ helper เดียวกันถ้าจะ reuse ภายหลัง

### ไฟล์ uncommitted ที่ **ไม่เกี่ยว** กับงานนี้ (ค้างบน branch, เว้นไว้ให้เจ้าของแยก)
`EditPostModal.vue` (academy post edit endpoint), `FeedPost.vue`, `pages/academies/[name].vue`, `pages/index.vue`, `pages/welcome.vue`

---

## 2026-07-12 — Withdrawal & Wallet Hardening ครบวงจร (8 PRs merged เข้า main)

> งานใหญ่: วิเคราะห์ → review งานที่ Codex/Gemini ทำ → แก้บั๊กวิกฤต → ตรวจ invariant เงินเข้า-ออก → baseline บน DB จริง → bcmath + locked_balance → decimal(15,2) → เก็บกวาด **ทั้งหมด merge เข้า `main` แล้ว (PR #3–#8)** เอกสารเต็มอยู่ที่ [`.agents/withdrawal-review-findings.md`](withdrawal-review-findings.md) + [`.agents/withdrawal-system-hardening-plan.md`](withdrawal-system-hardening-plan.md)

### PR ที่ merge เข้า main (8 PRs)
- **#3** Withdrawal hardening — atomic state machine 9 สถานะ + `lockForUpdate` ทุกจุด + maker-checker (ยอด ≥10,000 ต้อง 2 admin) + daily/monthly limit + `WithdrawalPolicy` (approve/reject = SUPER_ADMIN+ADMIN, MODERATOR = view) + audit ครบ + mask bank PII (เต็มเข้ารหัสใน `destination_snapshot`)
- **#4** ลบ dead code `WalletController::approveWithdrawal/rejectWithdrawal` (ไม่มี route ชี้)
- **#5** bcmath (เส้นถอนเงิน) + คอลัมน์ `users.locked_balance` (materialized: wallet = ยอดใช้ได้, total = wallet+locked) + `checkLockedBalance` reconciliation
- **#6** bcmath ครบทุกเมธอด (deposit/transfer/adminAdjust/points/purchase/refund)
- **#7** fix `PointsService::updateUserLevel` crash เมื่อ `$user->xp` null (user เพิ่งสร้าง) → แก้บั๊ก `user can earn points` ที่ค้างมานาน
- **#8** 🔑 **`users.wallet` เป็น `double` (float!) มาตลอด** = ต้นตอ float drift จริง → แปลงเป็น `decimal(15,2) unsigned`; fee/net_amount (10,2)→(15,2)

### เครื่องมือใหม่ (บน main)
- `WalletReconciliationService` + `php artisan wallet:reconcile [--user --mismatched]` — สรุป money-in/out, wallet↔ledger ต่อ user, ยอดถอน 9 สถานะ, refund integrity, locked integrity, ยอดติดลบ; **คืน exit≠0 เมื่อไม่ healthy**
- `php artisan wallet:baseline [--commit --force --user]` — opening-balance baseline (dry-run default, idempotent)
- `php artisan wallet:flag-legacy-withdrawals [--commit]` — flag รายการ returned เก่าที่ไม่มี refund ledger
- **Daily schedule** `wallet:reconcile` 03:30 + log alert (`routes/console.php`) → ผลที่ `storage/logs/wallet-reconcile.log`
- `app/Helpers/BcMathHelper.php` (`bcround`/`bcmax`)

### ✅ สถานะ DB จริง (nuxnan บน WAMP) — Ledger HEALTHY
- รัน migration แล้วบน dev: 000001–000005 (withdrawal fields, status enum, opening_balance type, locked_balance, decimal(15,2))
- baseline 385 users (opening_balance), flag 2 legacy cancelled, normalize wallet=ledger, แปลง wallet double→decimal
- reconcile: money out ≤ money in **OK**, wallet==ledger **OK**, 0 mismatched, 0 negatives, refund+locked integrity **OK**
- backup tables ลบหมดแล้ว

### ⚠️ Deploy notes (ต้องทำบน production ตามลำดับ)
1. `php artisan migrate` — รัน 000001–000005 (โดยเฉพาะ **000005 แปลง wallet double→decimal**; lossless ถ้าข้อมูล 2-decimal อยู่แล้ว)
2. `composer dump-autoload` (autoload.files เพิ่ม BcMathHelper — ไม่งั้น `bcround` ไม่โหลด)
3. `php artisan wallet:baseline --commit --force` — ถ้า production มี wallet ที่ไม่มี ledger กำกับ (เหมือน dev) ต้อง baseline **ก่อนเปิดถอนเงินจริง** (dry-run ดูก่อน)
4. `php artisan wallet:flag-legacy-withdrawals --commit` — ถ้ามีรายการ returned เก่าไม่มี refund
5. ยืนยัน **cron `* * * * * php artisan schedule:run`** ทำงานบน server (ไม่งั้น daily reconcile ไม่รัน)
6. หลัง deploy: `php artisan wallet:reconcile` ต้องขึ้น HEALTHY

### งานที่ค้าง (backlog — ไม่ block)
- [ ] Precision uniformity เล็กน้อย: `wallet_transactions.amount/balance_before/balance_after` เป็น decimal(20,2) บน dev แต่ (10,2) บน fresh install — ทั้งคู่ decimal ปลอดภัย ไม่รีบ
- [ ] Double-entry control accounts (แผนไว้เป็น optional ตอน scale ใหญ่ — ไม่ทำตอนนี้)
- [ ] Frontend: `ui/composables/useAdminWallet.ts` + `pending.vue` ใช้ `$fetch` ตรง (ผิด convention `useApi`) — pre-existing, ยังไม่แก้
- [ ] Load test ถอนพร้อมกันจริงหลาย process (row-lock พิสูจน์เต็มต้องใช้หลาย connection — unit test ทำไม่ได้)
- [ ] Security follow-up เดิม: ลบ public student-card route (ดู memory `project_public_student_card_pii`)

### Context สำคัญ
- **โมเดล locked_balance:** `wallet` = ยอดใช้ได้เสมอ (หักทันทีตอน withdraw), `locked_balance` = เงินกันไว้ = Σ active withdrawals (pending/under_review/approved/processing). withdraw: wallet−, locked+. paid: locked− (เงินออกจริง). reject/cancel/failed: wallet+, locked− + สร้าง refund ledger. อย่าเปลี่ยน semantics นี้ — reconciliation ทั้งหมดอิงมัน
- **Ledger เป็น source of truth:** ทุกการแก้ wallet มี WalletTransaction row ที่ delta = balance_after−balance_before → `wallet == Σ delta` เสมอ ห้ามแก้ wallet โดยไม่มี ledger row (นี่คือสิ่งที่ทำให้ reconcile ตรง)
- **`gh` CLI ติดตั้งแล้ว** (v2.96.0) แต่ยังไม่ `gh auth login` — ใช้ token จาก Git Credential Manager ผ่าน `GH_TOKEN` (scope: repo,workflow,gist — ไม่มี read:org จึงต้องใช้ `gh api` REST สำหรับ pr edit/merge ไม่ใช่ `gh pr edit`)

### Branch / Git State
- Branch: `main` (sync กับ origin) — **push แล้วทุก PR**
- Uncommitted: หลังอัปเดต worklog นี้จะมี worklog รอ commit
- ทุก feature branch (#3–#8) ลบทั้ง local + remote แล้ว

---

## 2026-07-11 — Runtime verify Intake + G1-G3 → เจอ+แก้ 3 บั๊ก (build ผ่านแต่ runtime พัง)

> ⚠️ แก้ความเข้าใจจาก entry ก่อนหน้า: intake + G1-G3 "มี code + test/build ผ่าน" **แต่ใช้งานจริงไม่ได้** — runtime verify (login จริง, ขับ UI) พบ 3 บั๊ก ทั้งหมดแก้แล้ว commit `bc57c1db` (push แล้ว)

### บั๊กที่เจอ + แก้ (ทั้งหมด pre-existing, build/test ไม่จับ)
1. **PrimeVue ไม่เคยถูก wire เข้า app** (มี `primevue` v4 ใน package.json แต่ไม่มี plugin `app.use(PrimeVue)` เลย — มีแค่ VueDatePicker plugin) → `<Stepper>` (IntakeWizard, ImportWizard) + `<Dialog>` (StudentAccountActivationModal) resolve ไม่ได้ → wizard render ทุก step ซ้อนกัน, modal ใช้ API ผิด
   - **แก้:** rewrite เป็น stack จริงของแอป — custom Tailwind stepper + Headless UI Dialog + เติม `import { Icon } from '@iconify/vue'`
2. **intake ยิง API ด้วยชื่อโรงเรียน (Thai) แทน id** — `duplicate-check` + `submit` เรียก `/api/academies/{academyName}/...` แต่ route bind `{academy}` ด้วย **id** → 404 ทุกครั้ง → wizard เดินไม่ได้เลย
   - **แก้:** `studentIntakeService` + `useStudentIntake` + `StepIdentity`/`IntakeWizard` ใช้ academyId (inject จาก admin parent, resolve lazy ด้วย `toValue` เพราะ parent fetch async)
3. **หน้า import 500 ทั้งหน้า** — `import.vue` `definePageMeta({ middleware: ['auth','academy-role'] })` แต่ middleware `academy-role` ไม่มีอยู่จริง (มีแค่ admin-guest/auth/guest/nuxnan-admin/plearnd-admin)
   - **แก้:** เหลือ `['auth']`

### Verify runtime (browser, login)
- **G2 DataTable:** ✅ โหลดจริง (stats กำลังเรียน 2662/รับใหม่ 521/ยังไม่มีห้อง 719/รอเปิดบัญชี 46), search กรองได้, pagination, action buttons
- **Intake wizard:** ✅ stepper Tailwind เดิน step 1→2, `duplicate-check` → **200** (ใช้ id แล้ว), แสดงเฉพาะ step active
- **G1 Import:** ✅ หน้าโหลด (ไม่ 500), ImportWizard stepper 3 steps render
- **G3 Activation:** ✅ modal (Headless UI) เปิด/ปิด, icons ครบ; public page error state verified ก่อนหน้า
- **`npm run build`:** ✅ ผ่าน (exit 0, ไม่มี "Failed to resolve component" — ยืนยัน dev SSR warning เป็น artifact)

### ไฟล์ที่แก้ (7)
`StudentAccountActivationModal.vue`, `IntakeWizard.vue`, `ImportWizard.vue`, `StepIdentity.vue`, `studentIntakeService.ts`, `useStudentIntake.ts`, `import.vue`

### ยังค้าง
- intake **submit จริง** (สร้างนักเรียน) ยังไม่ทดสอบ (เลี่ยง side effect); StepPreview/StepConfirm ของ import ยังไม่ขับจนจบ
- PrimeVue ยังอยู่ใน package.json แต่ไม่ได้ใช้ — พิจารณาถอดออก (มี component อื่นใช้ `<Dialog>`/`<DataTable>` แบบ PrimeVue อีกไหม ควร audit)
- deploy notes สะสม (migrations, GamificationSeeder, composer install mpdf)

---

## 2026-07-11 — Backlog audit (Intake + G1-G3 all done) + typing migration regression fix

### สิ่งที่พบ
- **Student Intake Phase 2-3 เสร็จสมบูรณ์แล้ว** (ทำในเซสชันหลัง 2026-07-05 — worklog TODO เก่า stale):
  - **Phase 2 Backend:** `StudentIntakeController` (store/duplicate-check/index/stats/export) + `StudentIntakeService` + `StoreStudentIntakeRequest`/`CheckStudentDuplicateRequest` + `EnrollmentPolicy` + routes `api/academies/{academy}/student-intakes/*` — **`StudentIntakeControllerTest` ผ่าน 8/8** (atomic intake, permission registrar/students.manage, duplicate block, cross-academy reject, full-classroom rollback, academy-scoped duplicate check)
  - **Phase 3 UI:** 5-step wizard `IntakeWizard.vue` (Identity/Personal/Admission/Guardian/Review) + `DuplicateWarning.vue` + `useStudentIntake.ts` composable + `studentIntakeService` — reachable จากปุ่ม "รับนักเรียนใหม่" ใน `students/index.vue`
  - payload frontend ↔ backend `StoreStudentIntakeRequest` keys ตรงกัน (identity/personal/admission/previous_school/guardians+contacts/account)

### 🔴 Regression ที่แก้
- migration typing `9f084ff1` (`2026_07_11_100001_..._game_mode_to_string`) ใช้ raw `ALTER TABLE ... MODIFY` = **MySQL-only syntax** → พังทุกเทสต์ที่ใช้ SQLite (`SQLSTATE near "MODIFY"`) ตอนแรก verify ด้วย curl เลยไม่เจอ
- **แก้:** driver-guarded — MySQL ใช้ raw MODIFY (ที่ verify แล้ว), driver อื่น (SQLite) ใช้ Schema `->change()`
- migration รันบน WAMP MySQL ไปแล้ว (ไม่ re-run) → WAMP ยังถูกต้อง; fix มีผลกับ test SQLite + fresh deploy
- **ยืนยัน:** `tests/Feature/Api/Academy` กลับมาเขียว **75/75** (270 assertions)

### G1-G3 ตรวจแล้ว — เสร็จหมดเช่นกัน (stale เหมือน intake)
- **G1 Import History:** ✅ backend `student-imports` CRUD ครบ (index/upload/template/show/cancel/confirm/errors/retry/rows), `StudentImportControllerTest` **5/5**; frontend `import-history.vue` (listBatches+pagination) + `import.vue`→`ImportWizard`
- **G2 Student DataTable:** ✅ `StudentDataTable.vue` + `students/index.vue` wired (list/stats/export), ปุ่ม "รับนักเรียนใหม่"
- **G3 Account Activation:** ✅ public page `activate-student/[token].vue` + `StudentActivationController` (show/activate, token เข้ารหัส `token_hash`); **runtime verified** error state (token มั่ว → "ไม่สามารถเปิดบัญชีได้"); happy path ต้อง pending invitation (มี 0 records) + ติดกฎกรอกรหัสผ่าน

### ยังค้างจริง (verification + deferred — ไม่มีโค้ดใหม่ใน intake/G1-G3)
- **Runtime verify UI admin** (ต้อง login): intake wizard, import wizard, StudentDataTable, G3 happy path — build/test ผ่านแต่ยังไม่ขับ UI จริง (บทเรียนจากบั๊ก 500 typing: build ผ่าน ≠ runtime ผ่าน)
- Home Visit [id] detail page (0 records ยังทดสอบไม่ได้)
- Deploy steps สะสม (ดูด้านล่าง), security follow-up ลบ public student-card route, typing 2 ข้อสังเกตเล็ก

### Git
- commit นี้: migration fix + worklog

---

## 2026-07-11 — Typing runtime verify + Home Visit smoke test + PDF export

### งานที่ทำ
- **Typing runtime verification (เฟส 0–3 ที่ทำไว้แล้ว):** verify ผ่าน API + UI จริง (user login) — เจอ + แก้ **blocker 500** `typing_sessions.game_mode` ENUM ไม่มี `key_training`/`letter_runner` → migration `2026_07_11_100001_change_typing_sessions_game_mode_to_string` แปลงเป็น VARCHAR(32) (รันแล้ว); ยืนยัน submit 200, XP+, PP เท่าเดิม, key mapping ไทย, Phaser focus, /result, regression ครบ (ดู `typing-game-improvement-plan.md` section "Runtime Verification Results")
- **Home Visit admin runtime smoke test:** เปิดหน้า admin จริงใน browser (ปิด gap worklog เดิม) — index/create/export โหลดได้ ทุก API 200 ไม่มี 500 (statistics/zones/admin-visits/admin-students); มี 0 visit records
- **Home Visit PDF export (feature ใหม่):** เพิ่ม PDF ควบ CSV ในหน้า export admin
  - ติดตั้ง `mpdf/mpdf` (bundle ฟอนต์ไทย Garuda ในตัว)
  - `AdminController::exportVisits` build rows ครั้งเดียว branch `?format=csv|pdf`; PDF = Blade view A4 แนวนอนไทย (`resources/views/exports/home-visits-pdf.blade.php`)
  - Frontend `export.vue`: เพิ่มตัวเลือก PDF + **ส่ง `format` param** (เดิมไม่ส่ง → fallback CSV เสมอ)

### Verification
- Typing: `TypingRewardPolicyTest` + StudentCard suite ผ่าน; runtime submit key_training 200; regression modes 200
- PDF: curl endpoint CSV+PDF → 200; PDF เป็น `%PDF-1.4` 39KB, Garuda subset embed, `pdftotext -enc UTF-8` ดึงไทยถูกต้อง (ชื่อโรงเรียน/หัวตาราง/ชื่อนักเรียน); Pint ผ่าน
- ลบ test data (9 typing_sessions + temp visit) + คืน user.xp baseline 309; ลบ temp PDF/HTML ใน public/ หมด

### ⚠️ Deploy notes (สะสม)
- รัน migration: `idempotency_key` (points_transactions) + `game_mode` VARCHAR (typing_sessions)
- reseed `GamificationSeeder` (เคลียร์ `max_daily_earnings` เดิมเป็น null)
- `composer install` (dependency ใหม่ `mpdf/mpdf`) + temp dir `storage/app/mpdf` (โค้ด auto-mkdir)

### ยังค้าง (backlog)
- Typing UI 2 ข้อสังเกตเล็ก: route case `/play` vs `/Play`, mode reset เป็น word_typing หลังจบเกม
- Home Visit: [id] detail page ยัง smoke test ไม่ได้ (0 records); PDF option บน UI ยัง verify ตอน login ไม่ได้ (JWT หมดอายุ) — โค้ดยืนยันแล้ว
- Student Intake Phase 2–3, DataTable/Activation/Import History, home-visit schema, Student Card Request System (ยังเป็นแผน)

### Git
- commits: `9f084ff1` typing fix, `b3a0bf8f` typing doc, `8e1ccfe0` home-visit PDF (+ `5130fc5a`/`183f5a6e` student-card PII mask→revert, `89a51f38` doc sync ก่อนหน้า)

---

## 2026-07-11 — Student Card Public PII (mask → revert) + doc sync

### งานที่ทำ
- **ตรวจพบช่องโหว่:** public (no-auth) `GET /api/student-card/{level}/{room}` คืน PII เต็ม (`national_id` + `birth_date`) ของนักเรียนทั้งห้อง — `StudentCardPublicResource` เดิม**คืนค่าเท่ากับ resource authenticated เป๊ะ** (แยก class ไว้แต่ไม่ได้ mask อะไรเลย)
- **แก้ mask (`5130fc5a`):** mask `national_id` เหลือ 2 กลุ่มท้าย + ตัด `birth_date` เป็น null เฉพาะ anonymous, authenticated/admin ยังเห็นเต็ม + frontend รองรับค่า masked + เทสต์ e2e ผ่าน route จริง
- **Revert (`183f5a6e`) — ตามคำสั่งเจ้าของ:** เปิด public PII เต็มกลับ **ชั่วคราว** เพราะผู้ใช้ยังไม่พร้อม login (คุมการเข้าถึงเองผ่านการแจก URL)

### ⚠️ Security decision ที่ค้าง (ต้องตัดสินก่อน production)
- public route เปิด PII เต็มโดยเจตนา = ความเสี่ยงที่ยอมรับชั่วคราว
- **แผนที่ตกลง:** เมื่อผู้ใช้ login ได้ → **ลบ route public ทิ้ง** จำกัดเป็น admin/ผู้มีสิทธิ์ (ไม่ใช่ mask) — โค้ด mask กู้กลับได้โดย revert `183f5a6e`
- บันทึกใน memory `project_public_student_card_pii.md` แล้ว (กัน mask ซ้ำ)

### Doc sync
- อัปเดต `latest-analysis.md` "แผนดำเนินงานเป็นเฟส" XP/PP: เฟส 0–3 mark `[x]` done (ตรงกับ commit `af434d89`/`a1a23d30`), เฟส 4 คง `[ ]` optional + เพิ่มบล็อกสถานะ (verification + deploy steps ยังค้าง)

### Git
- `main` = `origin/main` (push แล้ว): `5130fc5a` mask + `183f5a6e` revert

---

## 2026-07-11 — Game XP/PP Reward Policy (เฟส 0–3 + hardening)

### งานที่ทำ
ปรับนโยบายการให้คะแนนในเกมพิมพ์ดีด: **XP ให้ได้เต็มที่ (behavior-funded), PP ให้เฉพาะกิจกรรมมีเพดาน (budget-funded)** เพราะ PP แปลงเป็นเงินจริงได้ (`1200 pp = 1 บาท`)

- **เฟส 0:** migration `2026_07_11_000001_add_idempotency_key_to_points_transactions` (nullable+unique, additive) + `PointsService::awardGoverned()` (เช็ค idempotency → rule limit → `earn()` + catch `QueryException`); `earn()` รับ `idempotency_key` param ท้าย (default null)
- **เฟส 1:** ลบ PP `floor(score/100)` ใน `TypingSessionController` → typing session ปกติให้ XP อย่างเดียว
- **เฟส 2 Daily Challenge:** อ่าน wpm/accuracy จาก `TypingSession` ใน DB (ไม่เชื่อ client), guard owner/game_mode/challenge_id/`isToday()`/session ซ้ำ, ห่อ `DB::transaction`+`lockForUpdate`, จ่ายผ่าน `awardGoverned`
- **เฟส 2 Tournament:** `claim()` guard `rank===null` + `lockForUpdate` + atomic; ใช้ `FinalizeTypingTournaments` (มีอยู่เดิม + schedule `->hourly()`) set rank; เพิ่ม tie-break `best_session_id`
- **เฟส 3:** payout ผ่าน `awardGoverned` ทั้งหมด + seed rules `typing_daily_challenge`, `typing_tournament_prize`
- **Hardening:** แก้ `canEarnFromRule` daily-check ให้ scope ตาม source (mirror monthly) กันบั๊ก aggregate cross-source; `awardGoverned` log เมื่อโดน limit ตัด

### Verification
- `TypingRewardPolicyTest` ผ่าน 5/5 (25 assertions); Points/Gamification/Reward/Quest อื่นผ่าน 43; Pint ผ่านทุกไฟล์
- บั๊กเดิมนอกขอบเขต (ไม่แก้): `WalletAndPointsTest::test_user_can_earn_points` ล้มบนโค้ดเดิมด้วย (`updateUserLevel` + `xp` null); `updateDailyLimits` มี edge case เฉพาะ SQLite (production MySQL `DATE` ไม่เกิด)

### ⚠️ ต้องทำตอน deploy
- **reseed `GamificationSeeder`** บน env ที่เคย seed `typing_daily_challenge.max_daily_earnings=10` เพื่อล้างเป็น null (ตั้ง explicit null แล้ว updateOrCreate จะเขียนทับ)
- รัน migration `php artisan migrate` (เพิ่มคอลัมน์ `idempotency_key`)

### Backlog (ยังไม่ทำ)
- เฟส 4 Admin Event framework (optional); Achievement PP (ยังไม่มีฟิลด์ `pp_reward` ในโมเดล)
- รายละเอียดเต็มใน `.agents/latest-analysis.md` section "Work Plan — นโยบายการให้คะแนน XP / PP ในเกม"

### Branch / Git State
- Branch `main` — commit ชุดนี้ยังไม่ push (รอ confirm)

---

## 2026-07-10 — Home Visit Admin Legacy Cleanup

### งานที่ทำ
ลบ dead code ฝั่ง Home Visit Admin ที่เป็น legacy Inertia (`axios` + `router.visit`/`router.post`) ซึ่งถูกแทนที่ด้วยหน้า Nuxt-native ใหม่ `pages/academies/[name]/admin/home-visits/*` (index/create/export/zones/[id]) ที่ link ใน sidebar แล้ว (`admin.vue:169`) และยิง `/api/academies/{academy}/home-visits/*` (auth:api + academy.permission)

- **ลบ (scope แคบ เฉพาะที่พัง):** `pages/Learn/Student/HomeVisit/Admin/` ทั้งโฟลเดอร์ (16 ไฟล์: Dashboard + Components/* + MockData) + `composables/useVisitReports.js` (orphaned — ใช้เฉพาะ 2 component ในโฟลเดอร์เก่า)
- **คงไว้ (ไม่แตะ):** `HomeVisit/Student/`, `Teacher/`, `Auth/`, `Components/`, `Composables/` — portal เก่ายังเรียก `/api/home-visit/student/*` + `/teacher/*` ที่ยังมีอยู่ ไม่พัง
- เหตุ regression: legacy admin routes `/api/home-visit/admin/*` ถูกลบไปแล้ว (ดู `routes/homevisit/homevisit.php:139-140`) → หน้าเก่าเรียกแล้ว 404 (และใช้ Inertia router ที่ไม่มีใน Nuxt อยู่แล้ว)

### Verification
- `npm run build` ผ่าน (Build complete) ไม่มี broken import
- grep ทั้ง repo: 0 reference ค้าง (`useVisitReports` / `home-visit/admin`)
- git: commit `e849b161` (ลบ 17 ไฟล์ / −5,122 บรรทัด) — ยืนยัน commit ไม่แตะไฟล์ non-Admin

### ⚠️ Feature gap ที่ต้องตามต่อ (backlog — ยังไม่ block)
- **PDF export หาย**: หน้าเก่ามี per-visit PDF report (`/admin/visits/{id}/report`) + bulk PDF export (`/admin/visits/export/pdf`) แต่หน้าใหม่มีแค่ **Excel** export (`/admin/export/visits`) — ถ้าโรงเรียนต้องใช้รายงาน PDF จริง ต้องเพิ่ม endpoint + ปุ่มใน `academies/[name]/admin/home-visits/export.vue` ใหม่
- **Runtime smoke test ยังไม่ทำ**: build ผ่านแล้วแต่ยังไม่ได้ login เปิดหน้า admin จริงเพื่อ verify API ตอน runtime

### Branch / Git State
- Branch `main`, commit `e849b161` — pushed origin แล้ว

---

## 2026-07-10 — Academy Admin Settings Schema Fix

### งานที่ทำ
แก้ไขบั๊ก `SQLSTATE[42S22] Unknown column 'description'` ที่หน้าการตั้งค่าข้อมูลโรงเรียน `/academies/{name}/admin/settings` โดยดำเนินการดังนี้:

- **Database Migrations**
  - สร้างและรัน migration `2026_07_10_000001_add_settings_fields_to_academies_and_settings` เพื่อเพิ่มคอลัมน์ใน `academies` (`name_en`, `description`, `description_en`, `website`, `province`, `country`, `name_slug`) และใน `academy_settings` (`privacy`, `join_mode`, `allow_student_registration`, `allow_parent_registration`, `show_member_list`, `show_course_list`) พร้อม idempotent check และ auto-backfill `name_slug` สำหรับโรงเรียนที่มีอยู่เดิม
- **Backend Eloquent Models**
  - เพิ่ม attributes ใหม่ลงใน `$fillable` ของ `Academy` และเพิ่ม `$casts` boolean ใน `AcademySetting`
  - **Cache invalidation fix**: เพิ่ม boot hook ใน `AcademySetting` (`saved`/`deleted` → `Cache::forget("academy_settings_{id}")`) เพราะ `Academy::getSettings()` cache ค่าไว้ 24 ชม. และเดิมล้าง cache เฉพาะตอน `Academy` row dirty — ทำให้การแก้ "เฉพาะ setting" (เช่น สลับ privacy โดยไม่แก้ชื่อโรงเรียน) คืนค่าเก่าค้างนานถึง 24 ชม.
- **Backend Controller & Resource**
  - เพิ่ม request validation ใน `AcademyController@updateSettings`, รองรับการบันทึก `join_mode` แบบ non-lossy, ป้องกัน collision ของ `name_slug`, และสร้าง setting row ถ้ายังไม่มี
  - flatten ฟิลด์ setting ขึ้น top-level ใน `AcademyResource` ป้องกันหน้า UI รีเซ็ตค่ากลับ default ทุกครั้งหลังโหลด/บันทึก
- **Frontend**
  - อัปเดต `settings.vue` avatar/cover preview ให้ชี้ `logo_url`/`cover_url` (แทนคีย์ `avatar` เดิมที่ไม่มีอยู่จริง)
- **Code Quality & Testing**
  - `AcademySettingsUpdateTest` — **5 เทส / 57 assertions ผ่านหมด** ครอบคลุม full-field round-trip, permission denial, validation, slug collision, และ **regression test พิสูจน์ว่าการแก้ setting อย่างเดียวไม่คืนค่าค้าง cache** (ปิด hook แล้วเทสต์ fail จริง → ยืนยันว่าเทสต์มีความหมาย)
  - จัดการ format ด้วย Laravel Pint

### ไฟล์ที่สร้างใหม่/แก้ไข
- `database/migrations/2026_07_10_000001_add_settings_fields_to_academies_and_settings.php` [NEW]
- `tests/Feature/Academy/AcademySettingsUpdateTest.php` [NEW]
- `app/Models/Academy.php` [MODIFY]
- `app/Models/AcademySetting.php` [MODIFY]
- `app/Http/Controllers/Api/Learn/Academy/AcademyController.php` [MODIFY]
- `app/Http/Resources/Learn/Academy/AcademyResource.php` [MODIFY]
- `ui/pages/academies/[name]/admin/settings.vue` [MODIFY]

### Branch / Git State
- แตก branch `fix/academy-admin-settings-schema` → commit 3 ชุด (`59af6c73` backend, `2886dba0` frontend, `e1a12493` tests) → **merge เข้า `main` แล้ว** (`263ee465`, `--no-ff`) และ push origin เรียบร้อย
- Migration รันบน DB `nuxnan` แล้ว (ยืนยันคอลัมน์ครบทั้ง `academies` และ `academy_settings`)
- Uncommitted ที่เหลือ (ไม่เกี่ยวงานนี้ ปล่อยไว้): `.agents/implementation_plan.md` และ `2026_07_10_013214_modify_id_in_user_usage_events_table.php` (untracked, มีอยู่ก่อน session)

---

## 2026-07-09 — PromptPay Withdrawal Channel (branch: `fix/home-visit-admin-classroom-refactor`)

### งานที่ทำ
เพิ่มช่องทางถอนเงินผ่าน "พร้อมเพย์" ต่อจากการถอนเข้าบัญชีธนาคารเดิม โดยใช้ `wallet_transactions.metadata` (JSON) — **ไม่มี migration**

**Policy ที่ล็อก (ผู้ใช้เลือก):**
- ถอนขั้นต่ำ **25 บาท**, ค่าธรรมเนียม **13%** (ยึดตาม UI เดิม → แก้ backend ให้ตรง)
- รับพร้อมเพย์ 2 รูปแบบ: เบอร์มือถือ 10 หลัก (`0[689]xxxxxxxx`) + เลขบัตร ปชช. 13 หลัก
- field `method` เดิม รับค่า `'bank_transfer' | 'promptpay'`, marker คือ `bank_account.bank_name = 'promptpay'`

**Backend**
- `WalletService::withdraw()` — fee 13% (method-aware: `internal_deduction` = 0 กัน deduct pathway พัง), เพิ่ม `metadata.destination_type`
- `WalletController::withdraw()` — `amount` min 25, `method` in list, validate/normalize เบอร์พร้อมเพย์ (ตัด `-`/space), whitelist ธนาคาร, กันปลอม bank_name
- test ใหม่ `tests/Feature/Wallet/WithdrawTest.php` — 11 cases ผ่านหมด

**Frontend**
- `useWallet.ts` — type union + helper `normalizePromptPay`/`validatePromptPay`/`formatPromptPay`
- `Wallet.vue` — segmented toggle บัญชีธนาคาร/พร้อมเพย์, autofill เบอร์จาก profile, inline validation, min 25
- `nuxnan-admin/wallet/pending.vue` — label/icon/badge dynamic + fallback record เก่า (ไม่มี `destination_type`)

### Verification
- Backend 11/11 ผ่าน, Pint passed, `npm run build` สำเร็จ ไม่มี type error
- ⚠️ `WalletAndPointsTest > user can earn points` fail แต่ **fail บน baseline ด้วย** (pre-existing, เรื่อง points ไม่เกี่ยวงานนี้)
- ยังไม่ได้ verify ในเบราว์เซอร์ (หน้า Wallet อยู่หลัง auth middleware)

### Commit
- Backend: WalletService + WalletController + WithdrawTest
- Frontend: useWallet.ts + Wallet.vue + pending.vue
- (โน้ต `.agents/` ปล่อยไว้ไม่ commit เพื่อไม่ปนกับงาน rollover ที่ยังค้าง)

---

## 2026-07-09 — Rollover Harden Live Verification (branch: `fix/home-visit-admin-classroom-refactor`)

### งานที่ทำ
- **Regression tests ผ่าน 41/41 (106 assertions)** จาก 3 ไฟล์: `AcademicYearRolloverServiceTest`, `RolloverControllerWriteTest`, `ResourceShapeTest` — รวม 7 harden tests ใหม่ (skip/undo/rename/demote invariants) และ end-to-end mixed skip+promote → undo
- **Dry-run health checks** — `enrollment:repair-dirty-data --dry-run` พบ 3 duplicate active enrollments ในปี 2569 (student 2824/2846/2868) และ `enrollment:backfill-academic-info --dry-run` รายงาน 1929 SAI จะสร้างเพิ่ม (drift สะสมจาก rollover 2568→2569 เดิม)
- **Baseline clean-up** — รัน `enrollment:repair-dirty-data --commit`: 3 duplicate rows ถูก superseded, dry-run รอบสองยืนยัน 0 drift
- **Synthetic test student** — insert `id=2944 T-ROLLOVER-1` ใน 2569 M1/1 (isolated: single active enrollment, single current SAI, ไม่มีบัตร)
- **Live end-to-end test (commit + undo cycle) — ผ่าน 15/15 assertions** สร้างปี 2570 + ห้อง M2/1 ชั่วคราว รัน `AcademicYearRolloverService::commitRollover()` แบบ minimal plan (promote entry เดียว) แล้ว undo กลับ verify ทุก field:
   - `plan_summary.from_academic_year_name='2569'` / `to_academic_year_name='2570'` (harden invariant — frozen names)
   - `beforeSnapshots` มี student id (แสดงว่า promote ไม่ใช่ skip)
   - Post-commit: student.class_level='2', old CS 'promoted' + rollover_batch_id, new CS 'active' + rollover_batch_id, old SAI is_current=0, new SAI is_current=1 grade='ม.2'
   - Post-undo: student/CS/SAI กลับ baseline เป๊ะ, new CS+SAI ถูกลบ
- **Cleanup** — ลบ synthetic student + 2570 + M2/1 + rollover batch ครบ dry-run health check รอบสาม: 0 drift

### ไฟล์ที่แก้ / เพิ่ม
- ไม่มี code change ใน session นี้ (harden commit `95127816` ทำไว้แล้วก่อนหน้า)
- Persistent DB change: 3 rows ใน `classroom_students` เปลี่ยน status จาก duplicate `active` → `superseded` (baseline hardening ก่อน live test) — enrollment count ปี 2569 เดิม 2215 active → ปัจจุบัน 2212 active + 3 superseded (สุทธิเท่าเดิม)

### สิ่งที่ยืนยันจาก session นี้
- Commit 95127816 hardening ทำงานถูกต้องบนข้อมูลจริง (ไม่ใช่แค่ SQLite in-memory ในเทส)
- Undo pipeline คืนสถานะได้ถูกต้อง แม้เป็น full-service pathway (ไม่ใช่แค่ database transaction rollback)
- Go/No-Go gate: ✅ ผ่านทั้งหมด — regression tests, dry-run health checks, live test student, baseline restored, ไม่มี drift

### หมายเหตุก่อน live rollover ห้องจริง
- ยังมี **1929 missing SAI drift** จาก rollover เก่า — ไม่กระทบ commit/undo (test verified) แต่ควรรัน `enrollment:backfill-academic-info` (commit mode) ก่อน rollover จริงรอบใหม่ เพื่อ baseline สะอาด
- Branch นี้ยังไม่ merge เข้า main — harden commit + home-visit refactor ยังอยู่บน `fix/home-visit-admin-classroom-refactor`

### Scratchpad artifacts (ลบได้)
- `%TEMP%/…/scratchpad/create_synthetic_student.php`
- `%TEMP%/…/scratchpad/live_rollover_test.php`

---

## 2026-07-09 — Home Visit Admin Refactor (branch: `fix/home-visit-admin-classroom-refactor`)

### งานที่ทำ
- **Root cause fix**: `student_academic_info.classroom` ถูกลบไปตั้งแต่ migration `2026_04_08_050000` แต่ `AdminController` ยังอ้าง → 500 บน `/students`, `/visits`, `updateStudent`, และ CSV export
- **Multi-academy isolation**: ผูก `Academy $academy` + scope `academy_id` ให้ทุก admin endpoint (statistics/dashboard/students/visits/show*/update*/export/getAllVisits)
- **updateStudent เปลี่ยนห้องผ่าน enrollment service**: validate `classroom_id` ด้วย `Rule::exists` ที่ผูก academy → `StudentEnrollmentService::enrollStudent/transferStudent/promoteStudent/removeFromClassroom` (ไม่เขียน string ลง academic_info โดยตรง)
- **CSV/filter fixes**: null-safe visit_date, ใช้ `currentAcademicInfo->classroom_full`, แทน `teacher_name` (column ไม่มี) ด้วย `visitor_name`, พอร์ต SQL `CAST AS SIGNED` ให้ SQLite/MySQL ใช้ร่วมกันได้
- **`dashboardMock` gate ด้วย env** (local/testing เท่านั้น)
- **Backfill migration** `2026_07_09_000001_backfill_academic_info_classroom_id_from_current_enrollment.php` — idempotent, match academic_year name ก่อน fallback `is_current`/latest, log ก่อน/หลัง
- **Tests: 15 passed / 50 assertions** ครอบคลุม scoping, filter, transfer enrollment, cross-academy rejection, CSV output, legacy compat, mock 404 + migration idempotent/tie-break/no-enrollment

### Commits (บน branch `fix/home-visit-admin-classroom-refactor`)
- `c8aa028c` refactor(home-visit): scope admin endpoints to academy and drop legacy classroom column
- `16a559f5` test(home-visit): admin controller and backfill migration coverage

### ⚠️ Follow-ups ที่ยังไม่ได้ทำ (สำคัญ)
1. **Legacy routes `/api/home-visit/admin/*`** ใน `routes/homevisit/homevisit.php:141-164` ยัง active และเรียก controller methods ที่ต้องการ `Academy $academy` → **จะพัง** เมื่อ frontend เก่า (`ui/pages/Learn/Student/HomeVisit/`) หรือ page ใหม่บาง endpoint ที่ยัง hard-code `/api/home-visit/admin/*` เรียกเข้ามา  ต้องเลือก: (a) ลบ route group นี้ + migrate frontend, หรือ (b) ทำ shim ที่ resolve academy จาก session
2. **Frontend Phase 6**: `ui/composables/useVisitReports.js`, `pages/academies/[name]/admin/home-visits/*.vue` ยังใช้ URL `/api/home-visit/admin/*` และ payload/shape เดิม (ส่ง `classroom` string, รับ dropdown เป็น list string) — ต้องอัปเดตให้ใช้ `classroom_id` + shape ใหม่ `{id, name, grade_level, section}`
3. **Dead methods**: `downloadReport`, `exportToExcel`, `exportToPDF` ใน AdminController ไม่มี route ชี้ (ไม่กระทบตอนนี้ แต่ถ้าจะเปิดใช้ ต้องเพิ่ม Academy binding)
4. **Pre-existing failing test** (ไม่เกี่ยวกับ refactor): `Tests\Feature\Academy\Enrollment\ResourceShapeTest::rollover_batch_resource_reports_undoable_state` — fail แม้บน branch เดิม (ยืนยันด้วย `git stash && test`)
5. **PR**: branch นี้ยังไม่ได้ push/เปิด PR — พร้อม merge ถ้า resolve legacy route + frontend migration แล้ว

### ที่ทำงานถัดไปควรเริ่มจาก
- Follow-up 1 (legacy route decision) ก่อน merge branch นี้
- Follow-up 2 (frontend migration) เป็น PR แยก

---

## 2026-07-05 — API Bug Fixes & Admin Smoke Test (Session 2)

### งานที่ทำ
- **Fix Reports Page 500** — `dashboardStats` endpoint ใช้ namespace ผิด (`Learn\Academy\ClassSchedule` → `Models\ClassSchedule`) + AssignmentAnswer query ใช้ polymorphic relationship ผิด → เพิ่ม try-catch เพื่อ graceful fallback
- **Fix HomeVisit AdminController** — ลบ deprecated `$this->middleware()` ที่ไม่รองรับใน Laravel 12
- **Smoke Test Admin Pages** — ทดสอบ 6 หน้า:
  - reports ✅ (แสดง 2893 นักเรียน)
  - departments ✅ (5 แผนก + ปุ่ม setup ทำงาน)
  - gradebook ✅ (51 ห้องเรียน)
  - school-attendance ✅ (1 รายการ)
  - announcements ✅ (3 ประกาศ)
  - home-visits ⚠️ (pre-existing bug: `student_academic_info.classroom` column ไม่มี)
- **5 ฝ่ายมาตรฐาน** — ยืนยันว่ากดปุ่ม "โครงสร้างมาตรฐาน" แล้วสร้างแผนกครบ 5 สำเร็จ

### Pre-existing Bugs (ไม่ได้แก้)
- `home-visits/statistics` → 500 เพราะ `student_academic_info.classroom` column ไม่มีใน DB
- `academic years` console error — fetch academic years ล้มเหลว (ไม่กระทบ UI หลัก)

### Commits
- `b1fe7dc9` fix(api): resolve dashboardStats 500 and HomeVisit middleware error

### งานถัดไป (Backlog)
- [x] Student Intake Phase 2-3 (Single Student Intake backend + Registrar UI) — **done** (verified 2026-07-11, test 8/8)
- [x] Student List DataTable (Phase G2) — **done** (StudentDataTable.vue + index wired)
- [x] Student Account Activation Page (Phase G3) — **done** (public page + controller; error state verified)
- [x] Import History Page (Phase G1) — **done** (import-history.vue + backend CRUD, test 5/5)
- [x] Fix home-visits schema mismatch (student_academic_info.classroom) — **done** (home-visit refactor 2026-07-09/10)

> ⚠️ ทั้งชุดนี้ถูก implement ในเซสชันหลัง 2026-07-05 แต่ checkbox ไม่ได้อัปเดต — ยืนยัน done ทั้งหมดวันที่ 2026-07-11 (ดู entry บนสุด)

---

## 2026-07-05 — Admin Panel Smoke Test & Restructure (Phase A-D)

### งานที่ทำ
- **Phase A: Smoke Test & Bug Fixes**
  - Fixed CORS for dev preview (dynamic port regex in `allowed_origins_patterns`)
  - Created `CheckAcademyPermission` middleware + registered in `bootstrap/app.php`
  - Fixed `classroomStudents` → `classroomEnrollments` relationship name in StudentIntakeController
  - Rewrote `students.vue` parent to use provide/inject for academy ID
  - Fixed StudentDataTable and import pages to use academy ID instead of name

- **Phase B: Admin Sidebar Restructure**
  - Updated `admin.vue` parent route with complete sidebar (30+ pages linked)
  - Fixed mismatched sidebar links: attendance→school-attendance, grades→gradebook
  - Added missing pages: events, store, at-risk, invite-links, member-tags, guardians, etc.
  - Parent route now provides `academyId`, `academyName`, `academy` to all children
  - Simplified `students.vue` sub-parent to passthrough

- **Phase C: Enrollment Lifecycle UI**
  - Wired `StudentActionMenu` + `StudentStatusActionModal` into StudentDataTable
  - Added action column with 5 lifecycle actions (graduate/drop/repeat/promote/transfer)
  - Added enrollment history drawer button per row
  - All actions call existing backend endpoints via `useStudentEnrollmentActions` composable

- **Phase D: Reports Dashboard**
  - Created `reports.vue` page with overview stats from analytics API
  - Report sections with links to students, at-risk, attendance, gradebook, staff, etc.

### หมายเหตุ
- Parent portal at `/academies/[name]/parent/` already fully built (grades, attendance, meetings)
- Client-side navigation between admin pages may show transition glitches (HMR); full page loads work fine
- 15 commits ahead of origin, not pushed yet

### Commits (this session)
- `dcec3bc5` fix(school): smoke test fixes — CORS, middleware, route binding, relationship
- `17753e6a` feat(school): restructure admin sidebar with complete navigation
- `40b4041c` feat(school): wire enrollment lifecycle actions into StudentDataTable
- `402e0ab3` feat(school): add Reports Dashboard page

---

## 2026-07-05 — Student Intake System Phase 1

### งานที่ทำในวันนี้
- **Phase 1: Database Constraints Fix** 
  - สร้างและรัน migration `fix_student_unique_constraints` เปลี่ยน `student_id` และ `citizen_id` เป็น academy-scoped (unique per academy_id)
  - สร้างและรัน migration `add_enrollment_lookup_index_to_classroom_students` เพิ่ม index สำหรับค้นหา active enrollment
  - สร้างและรัน migration `create_student_import_tables` สำหรับรองรับระบบ bulk import (ตาราง `student_import_batches` และ `student_import_rows`)
- **Registrar Role Setup**
  - แก้ไข `AcademyRole::SYSTEM_ROLES` เพื่อเพิ่ม role `registrar` ("นายทะเบียน") ที่มีสิทธิ์ครบถ้วนสำหรับการทำงานเรื่องรับเข้าและจัดการนักเรียน
  - รัน `AcademyRoleSeeder` ด้วย updateOrCreate เพื่อให้ระบบทุก academy มี role นึ้ใช้งานได้ทันที

### งานที่ค้างอยู่ (TODO ต่อ)
- [ ] **Phase 2 — Single Student Intake (Backend)** 
- [ ] **Phase 3 — Registrar UI (Single Intake)**

---

## 2026-07-04 — School Department Setup Template (5 ฝ่ายมาตรฐาน)

### งานที่ทำในวันนี้
- **วิเคราะห์โครงสร้าง 5 ฝ่ายมาตรฐาน** — เปรียบเทียบ proposed data model กับ codebase จริง สร้างบทวิเคราะห์แก้ไข `.agents/school-5-departments-revised-analysis.md`
- **Phase 1: SchoolDepartmentSetupService** — สร้าง service ที่มี template 35 groups (1 office + 5 departments + 21 sections + 8 academic_groups) พร้อม idempotent setup ด้วย name+type matching
- **Phase 2: API Endpoints** — เพิ่ม `GET /departments/template` และ `POST /departments/setup` ใน DepartmentController + routes
- **Phase 3: Seeder** — สร้าง `SchoolDepartmentSeeder` สำหรับ dev/demo
- **Phase 4: Frontend** — สร้าง `DepartmentSetupModal.vue` (tree preview + conflict handling) อัพเดท `departments.vue` (ปุ่ม setup ที่ header + empty state) เพิ่มปุ่ม "ฝ่ายงาน/แผนก" ใน admin index quick actions

### งานที่ค้างอยู่ (TODO ต่อ)
- [ ] **ยังไม่ได้ commit** — ไฟล์ทั้งหมดยังเป็น uncommitted changes (ดู git status ด้านล่าง)
- [ ] **ทดสอบ seeder** — รัน `php artisan db:seed --class=SchoolDepartmentSeeder` บน WAMP จริง
- [ ] **ทดสอบ UI จริง** — login เข้า admin → กดปุ่ม "ตั้งค่าโครงสร้างมาตรฐาน" → ตรวจ hierarchy ถูกต้อง
- [ ] **classrooms/statistics 500** — bug เดิมไม่เกี่ยวกับงานนี้ แต่ `ClassroomController.php` มี uncommitted changes อยู่ (ตรวจว่าเป็นงานก่อนหน้า)

### Context สำคัญ
- **แนวคิด opt-in per school** — ไม่ได้สร้าง departments ให้ทุกโรงเรียนอัตโนมัติ admin ต้องกดปุ่มเอง
- Component ใน Nuxt ต้องใช้ชื่อ `SchoolDepartmentSetupModal` (prefix folder `school/`) ไม่ใช่ `DepartmentSetupModal`
- `POST /departments/setup` รองรับ `force=true` กรณีมี groups อยู่แล้ว — จะ skip รายการที่ซ้ำชื่อ+type
- แผนพัฒนาอยู่ที่ `.claude/plans/purrfect-fluttering-grove.md`
- บทวิเคราะห์ 5 ฝ่ายอยู่ที่ `.agents/school-5-departments-revised-analysis.md`

### ไฟล์ที่สร้างใหม่
- `api/nuxnanravel/app/Services/SchoolDepartmentSetupService.php`
- `api/nuxnanravel/database/seeders/SchoolDepartmentSeeder.php`
- `ui/components/school/DepartmentSetupModal.vue`
- `.agents/school-5-departments-revised-analysis.md`

### ไฟล์ที่แก้ไข
- `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/DepartmentController.php` — เพิ่ม getTemplate(), setupDepartments()
- `api/nuxnanravel/routes/learn/academy.php` — เพิ่ม 2 routes (template, setup)
- `ui/pages/academies/[name]/admin/departments.vue` — ปุ่ม setup + empty state + modal
- `ui/pages/academies/[name]/admin/index.vue` — เพิ่ม quick action "ฝ่ายงาน/แผนก"

### Branch / Git State
- Branch: `main`
- Uncommitted: **yes** — 5 modified + 4 untracked (ดูรายละเอียดด้านบน)
- Push status: ยังไม่ commit / ยังไม่ push

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
## 2026-07-06 — Student Card Rollover 2568 → 2569

- Created academic year 2569 (id 2), set current after successful rollover, and created target classrooms: M1=10, M2=11, M3=9, M4=9, M5=8, M6=7.
- Committed rollover batch `3c9ca6f7-3ece-4bbd-8f51-b7d64eae5162`: promote 1,662; graduate 267; new intake 476; skip 0.
- Corrected duplicate card link: card 1440 now links to student 1411 by citizen ID; no record was deleted.
- Card sync results: created 476, updated 1,662, expired 268. Active 2569 enrollments = active cards = 2,138.
- Integrity checks all zero: duplicate active cards, multiple active enrollments, multiple current academic rows, active enrollment without active card.
- Added migration `2026_07_06_200000_allow_uuid_entity_ids_in_audit_logs.php` because rollover UUIDs could not fit the former integer audit entity_id; migration was run successfully.
- Verification: StudentCard tests 8 passed / 19 assertions; Pint passed; dashboard API reports 2,138 students using 2569 room structure.

---

## 2026-07-07 — Student Photo Path Migration & E2E Polish

- **Canonical Photo Path Migration**: Migrated student photos from legacy room-based folders to student-identity-based paths (`images/students/profiles/{student_id}.{ext}`).
- **Backend Service & Models**: Created `StudentPhotoService` for unified storage management and backend-owned fallback checks. Added `profile_image_url` accessors to both `Student` and `StudentCard` models.
- **Migration Commands**: Implemented and executed `students:migrate-photos` migration tool (migrated 1,529/1,531 photos successfully). Created `students:cleanup-legacy-photos` tool for post-migration folder cleanup.
- **E2E Review Polish**: Resolved 22 code review findings including:
  - C1: Missing import of `StudentPhotoService` in `StudentCardController.php`.
  - H1: Path concatenation safety for already relative paths in `destroyPhoto()`.
  - H2: Stripping the 'ม.' Thai grade prefix in the legacy path assembly of `StudentCard`'s accessor.
  - H6: Null safety guards in `admin/students/[level]/[room].vue`.
  - C2 & H5: Complete simplification of frontend image loading across 15+ Vue components to rely solely on the resolved `profile_image_url` property from API.
  - M1: Automatically updating the frontend reactive refs on photo upload success.
  - M2: Fixing array return values in `StudentsCard.vue` helper methods.
  - H3: Grade normalization within `StudentPhotoService`.
- **Verification**: Formatted with Pint and verified all 8 unit tests in the StudentCard feature suite pass.

---

## 2026-07-08 — Roster Reconciliation with Student Code

### งานที่ทำ
- **Roster Reconciliation Logic**: พัฒนา `RosterReconciliationService` และปรับแต่ง `StudentImportService` เพื่อรองรับการนำเข้าแบบ Reconciliation โดยอิง `student_code` เป็น Key
- **JSON Import Support**: ปรับปรุงหน้าอัปโหลดในฝั่ง Frontend (`StepUpload.vue`, `studentImportService.ts`, `useStudentImport.ts`) และ API validation ให้สามารถรองรับไฟล์ JSON ได้
- **Artisan Extract Command**: สร้าง `ExtractRosterPdfCommand` สำหรับสกัด/แปลงข้อมูลจากไฟล์ PDF ไปเป็นโครงสร้าง canonical JSON
- **Reconciliation UI Preview**: ปรับปรุง `ImportRowTable.vue` เพื่อแสดงป้ายสถานะของการดำเนินการจัดห้องเรียน (เช่น เลื่อนชั้น, ซ้ำชั้น, ย้ายห้อง, นำเข้าใหม่) พร้อมแสดงรายละเอียดการเปลี่ยนแปลง (diff_data)
- **Tests & Verification**: เพิ่มและรัน `RosterReconciliationTest` ผ่านการตรวจสอบ 10 assertions ทั้งหมด พร้อมตรวจสอบว่า `StudentImportControllerTest` ยังสามารถรันผ่านได้ตามปกติ

### Commits
- Roster Reconciliation implementation complete.

---

## 2026-07-08 — Topic Youtube Video Support

### งานที่ทำ
- **YouTube URL Parser Utility**: สร้างไฟล์ยูทิลิตี้กลาง [youtube.ts](file:///c:/wamp64/www/nuxnan/ui/utils/youtube.ts) ยุบรวม logic การดึง ID, สร้าง thumbnail และ embed URL (ใช้ `youtube-nocookie.com`) ช่วยให้การ parse URL มีความเป็นหนึ่งเดียวและลดความซ้ำซ้อน
- **VideoModal Refactoring**: ปรับปรุง [VideoModal.vue](file:///c:/wamp64/www/nuxnan/ui/components/media/VideoModal.vue) ให้ดึงตัวแกะ URL จากยูทิลิตี้กลาง
- **LessonPost Refactoring**: ปรับปรุง [LessonPost.vue](file:///c:/wamp64/www/nuxnan/ui/components/learn/course/lesson/LessonPost.vue) ให้ดึงตัวแกะ URL จากยูทิลิตี้กลาง
- **Topic Video Preview & Playback**: เพิ่มกล่องแสดงพรีวิววิดีโอ (สัดส่วน 16:9 พร้อมปุ่ม Play) และรองรับการเปิดวิดีโอผ่าน [VideoModal.vue](file:///c:/wamp64/www/nuxnan/ui/components/media/VideoModal.vue) ใน [TopicAccordion.vue](file:///c:/wamp64/www/nuxnan/ui/components/learn/course/lesson/TopicAccordion.vue) โดยแยกสถานะ modal ออกต่อหนึ่ง accordion instance อย่างชัดเจน
- **Robust Error/Fallback Handling**:
  - รองรับการ fallback รูปภาพพรีวิวจาก `maxresdefault` ไปเป็น `hqdefault` กรณีรูปขนาดใหญ่ไม่มี
  - แสดงลิงก์ "เปิดบน YouTube" และข้อความแจ้งเตือนอย่างชัดเจน หากลิงก์ที่กรอกผิดรูปแบบ (Invalid URL)
  - ซ่อนส่วนวิดีโอทั้งหมดหาก `youtube_url` มีค่าว่าง
- **Build Verification**: รัน `npm run build` ในไดเรกทอรี `ui` ผ่านการทดสอบเรียบร้อย

### Commits
- Implement centralized YouTube parser utility and integrate video preview in TopicAccordion.

---

## 2026-07-08 — Roster Reconciliation Bug Fixes (Session 2)

### งานที่ทำ
- **M1: source_academic_year_id** — บันทึก `source_academic_year_id` ลงใน `diff_data` ตอน `preview()` สำหรับ action `promote_student` และ `repeat_student` ป้องกันปัญหาชื่อปีไม่ต่อเนื่องหรือมีปีที่เว้นไป
- **M3: student_number** — รองรับการซิงค์ `student_number` (เลขที่) เมื่อมีข้อมูลจาก JSON หรือใช้ลำดับ index จาก JSON เข้าสู่ฐานข้อมูล สำหรับ actions ทุกประเภท (`unchanged`, `new_intake`, `promote_student`, `repeat_student`, `re_enroll`)
- **M4: refreshCounters** — แก้ไข DRY violation โดยยกเลิกการเขียนฟังก์ชัน `refreshBatchCounters` ซ้ำใน `RosterReconciliationService` และเรียกใช้งาน `refreshCounters` จาก `StudentImportService` แทน
- **M5: remarks** — สร้าง migration `2026_07_08_000002_add_remarks_to_students_table` เพิ่มคอลัมน์ `remarks` ลงในตาราง `students` พร้อมทั้งเพิ่มลงใน `$fillable` ของโมเดล `Student` เพื่อแก้ปัญหาการเซฟ remarks เป็น silent no-op
- **N6: useStudentCardRequests Type Safety** — แก้ไข type ของ `useStudentCardRequests` composable แทนที่จะเป็น `as any` เพื่อเพิ่มความเสถียรและความปลอดภัยทางประเภทข้อมูล (Type Safety)
- **Tests & Verification** — เพิ่มการทดสอบใน `RosterReconciliationTest` สำหรับกรณี `unchanged` (การซิงค์ student number), `auto_graduate` ของนักเรียน ม.6, และ `ambiguous` teacher matching (ยืนยันผลการหาครูที่ชื่อซ้ำกัน) ผลการรัน Unit Test ผ่านทั้งหมด 26 assertions และจัดรูปแบบโค้ด PHP ด้วย Pint

---

## 2026-07-12 — Campaign System Phase 5: Create + Dashboard

### งานที่ทำ
- **หน้าสร้างแคมเปญใหม่ (Create Page)**: ปรับปรุงหน้า `ui/pages/Earn/Advertise/create.vue` ให้รองรับการทำงานใหม่แบบครบวงจร
  - เลือกประเภท: โฆษณา (Advertisement) / สนับสนุน (Support)
  - เลือกพื้นที่ (Scope): สาธารณะ (Public) / โรงเรียน (Academy) / รายวิชา (Course)
  - dynamic fields ตามที่เลือก: แสดง dropdown รายการโรงเรียนและรายวิชาที่จัดการได้, พร้อม toggle inherit (เฉพาะ course)
  - คำนวณราคา budget (จากจำนวนวิว x วินาที x 0.10 บาท) และคำนวณแต้มสนับสนุน (budget x 1080 PP) บน client อัตโนมัติ
  - เลือกช่องทางชำระเงิน: Wallet / อัปโหลดสลิป พร้อม date/time picker
  - แสดงหน้าพรีวิวบัตรโฆษณา/สนับสนุนเรียลไทม์ระหว่างกรอกข้อมูล
- **แดชบอร์ดผู้สร้างแคมเปญ (Creator Dashboard)**: สร้างหน้าใหม่ `ui/pages/Earn/Advertise/manage.vue`
  - สรุปสถิติแคมเปญ: งบประมาณสะสม ยอดวิวจริง แคมเปญที่ทำงานอยู่ และรายการรอตรวจ
  - ตารางรายการแคมเปญพร้อมรายละเอียด ยอดวิว/การเห็น สิทธิ์การแสดงผล สถานะการชำระเงิน และสถานะรีวิว
  - ตัวกรองตามประเภทและสถานะแคมเปญ
- **แดชบอร์ดผู้ดูแลระบบ (Admin Dashboard)**: ปรับปรุงหน้า `ui/pages/PlearndAdmin/Support/ApproveAdvertise.vue`
  - มี 3 แท็บสำหรับ Admin:
    1. **รอตรวจสอบ (Pending Review)**: อนุมัติ/ปฏิเสธ คำขอแคมเปญ (พร้อมระบุเหตุผลในการปฏิเสธ)
    2. **ประวัติการคืนเงิน (Refund Status)**: ตรวจสอบรายการที่ถูกปฏิเสธ หากจ่ายผ่านสลิปมีปุ่มกดเพื่อยืนยันการคืนเงินแบบแมนวล
    3. **Audit Log**: แสดงประวัติกิจกรรมทั้งหมดของแคมเปญ (สร้าง, อนุมัติ, ปฏิเสธ, เข้าชม)
  - เพิ่ม API endpoints ใหม่ในฝั่ง Laravel: `GET /api/campaigns/admin`, `GET /api/campaigns/admin/audit-logs`, และ `PATCH /api/campaigns/{campaign}/payment`
  - ปรับ backend ให้เปลี่ยนสถานะการชำระเงินสลิปเป็น `paid` อัตโนมัติเมื่อ admin กดยอมรับการรีวิว

### Verification
- รัน `php -l` ผ่านทุกไฟล์ใน backend
- Pint จัดรูปแบบโค้ด backend สำเร็จ
- UI หน้าต่างๆ ทำงานร่วมกับ API ชุดใหม่เรียบร้อย

---

## 2026-07-12 — Campaign System Phase 6: Tests and Logic Hardening

### งานที่ทำ
- **พัฒนาระบบการจัดทำ Unit/Feature Tests ทั้ง 12 เคสใน [CampaignSystemTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/Campaign/CampaignSystemTest.php)**:
  - การจ่ายเงินผ่านกระเป๋าเงิน (Wallet) และคำนวณราคาแบบคำนวณจริง (100% Correct)
  - ความปลอดภัยและการตรวจสอบขอบเขต (Scope validation integrity - HTTP 422 สำหรับ config ข้ามแบบแผน)
  - การกดยอมรับการตรวจสอบสลิปเงินและปรับสถานะเป็น Paid & Approved
  - การปฏิเสธแคมเปญและการดำเนินการคืนเงินคืนเข้า Wallet อัตโนมัติอย่างถูกต้อง
  - การจำกัดยอดรับชมการโฆษณา (Daily Reward Quota) สูงสุดไม่เกิน 5 ครั้ง/วัน/คน พร้อมระบบ Idempotency ป้องกันการส่งคีย์ซ้ำเพื่อตัดสิทธิ์
  - การแบ่งเงินสนับสนุน (Support revenue split) 70% (Academy owner), 20% (Course Instructor), และ 10% (Platform) ผ่าน `SupportPaymentService`
  - การกรองข้อมูลแคมเปญให้เข้ากับหน้าต่างโรงเรียนแบบ Scope Isolation และการเช็คเงื่อนไข `inherit_to_academy`
  - ยืนยันการไม่ปัดเศษทศนิยมงบประมาณ (Decimal Precision ในระดับ Float/Double/Decimal)
  - การ Query ตารางบริจาคตัวเก่า (Legacy `donates` compatibility) เพื่อไม่ให้เกิด Regression
  - การทำ Role-based Authorization แยกกรณีผู้เยี่ยมชม (401), สมาชิกทั่วไป (403), และผู้ดูแลระบบ (200)
- **Hardening และการกู้คืนข้อผิดพลาดใน Runtime/Database**:
  - แก้ไขปัญหา JWT guard state caching ในระบบ PHPUnit โดยใช้ `auth()->forgetUser()` เคลียร์หน่วยความจำระหว่าง request
  - เพิ่ม Fallback ให้กับ [ReviewCampaignRequest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Requests/Campaign/ReviewCampaignRequest.php) เพื่อให้สามารถค้นหา `Advert` Model ได้แบบตรงจุดแม้จะเกิดการ bind ตกหล่นใน CLI/Tests
  - ใส่ default values ให้กับคอลัมน์ NOT NULL ในตารางของ SQLite เช่น `slip`, `transfer_date`, `transfer_time`, `total_views`, `remaining_views` ในช่วงการบันทึกแคมเปญประเภทกระเป๋าเงิน เพื่อตัดปัญหา SQL constraint errors
  - จัดการรัน Pint ปรับปรุงโค้ดทั้งหมด

### Verification
- รัน `php artisan test tests/Feature/Campaign/CampaignSystemTest.php` ผ่านการตรวจสอบครบถ้วนทั้ง 12 เคส (46 assertions)
- Pint จัดรูปแบบเสร็จสมบูรณ์เรียบร้อย


