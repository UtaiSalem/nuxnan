# School Admin — Master Overview

> ไฟล์นี้คือดัชนีหลักของงานบริหารจัดการโรงเรียนฝั่ง Admin
> อ่านไฟล์นี้ก่อนเริ่มทำงานทุกครั้ง เพื่อรู้ว่าเมนูไหนอยู่สถานะไหน และไฟล์รองของเมนูนั้นอยู่ที่ไหน
>
> **หลักการ:** ทุกฟีเจอร์ในโรงเรียน = admin ต้องทำได้ทั้งหมด → จากนั้นค่อยแจกจ่ายสิทธิ์ให้บทบาทย่อย (ครู/ฝ่าย/หัวหน้าฝ่าย) ตามหน้าที่ของแต่ละส่วนงาน โดยไม่ให้สิทธิ์ซ้ำซ้อนกันระหว่างฝ่าย

---

## Workflow (Loop ต่อเมนู)

```
[1] สแกนโค้ดจริงของเมนู (frontend page + backend controller + routes + models)
[2] เขียนไฟล์รอง NN-<slug>.md (Scope / Current State / Feature Checklist /
    Permission Matrix / Gap Analysis / Implementation Tasks / Review Log)
[3] ส่ง task ทีละ step ให้ codex (ผ่าน Agent subagent codex:rescue)
[4] codex ทำงาน → รายงานผล → Claude ตรวจ (diff + build + test)
    - ถ้าไม่ผ่าน → แจ้ง codex แก้ → วนกลับ [4]
    - ถ้าผ่าน → update Review Log + สถานะในไฟล์นี้
[5] เมนูถัดไป
```

**กฎ:**
- Claude วางแผน + ตรวจสอบเท่านั้น ไม่เขียนโค้ดตรง (ตาม feedback memory)
- ทำทีละเมนู อย่ากระโดดข้าม อย่าทำงานคู่ขนาน (กันสับสน)
- ทุกเมนูต้อง verify build + relevant tests ก่อนเลื่อนไปเมนูถัดไป
- Commit เป็นชุดเล็ก ๆ ต่อ step

---

## Permission Model (สรุปย่อ)

- **owner** — เจ้าของโรงเรียน สิทธิ์ทุกอย่าง
- **admin** — ผู้ดูแลระบบทั้งโรงเรียน สิทธิ์ทุกอย่าง (ยกเว้นการโอนความเป็นเจ้าของ)
- **department admin** — admin ของฝ่าย/ส่วนงาน สิทธิ์เต็มเฉพาะเมนูที่ผูกกับฝ่ายตนเอง
- **staff / teacher / observer** — สิทธิ์บางส่วนตามหน้าที่ (view/manage รายเมนู)
- **student / guardian** — สิทธิ์ read เฉพาะที่ตัวเองมีส่วนเกี่ยวข้อง

สิทธิ์แต่ละอันใช้ key แบบ `<domain>.<action>` เช่น `members.view`, `students.manage`, `finance.view` — รายละเอียดแต่ละเมนูอยู่ในไฟล์รอง

---

## Menu Inventory & Status

Legend: 🟢 พร้อมใช้งาน (ผ่านการตรวจสอบครบ) · 🟡 มีแล้วแต่ยังไม่ได้ audit · 🔴 มี gap ที่ต้องอุด · ⚪ ยังไม่เริ่ม

| # | เมนู | หน้า (`ui/`) | Permission | ไฟล์รอง | สถานะ |
|---|---|---|---|---|---|
| **1** | **บทบาทและสิทธิ์** | `admin/roles.vue` | `roles.view` / `roles.manage` | [01-roles-permissions.md](01-roles-permissions.md) | 🟢 S1–S6 done · S7/S8 (dept scope) deferred → เมนู #9 |
| 2 | สมาชิก | `admin/members.vue` + `admin/members/` | `members.view` / `members.manage` | [02-members.md](02-members.md) | 🟢 M-S1–S7 done · M-S8/S9/S10 deferred |
| 3 | คำขอเข้าร่วม | `admin/requests.vue` | `members.manage` | [03-join-requests.md](03-join-requests.md) | 🟢 S1 done · deferred: reject reason, pagination, realtime badge |
| 4 | ลิงก์เชิญสมาชิก | `admin/invite-links/` | `members.manage` | [04-invite-links.md](04-invite-links.md) | 🟢 L-S1–S3 done · deferred: L-S4 edit/filter, L-S5 usage history, L-S6 QR |
| 5 | แท็กสมาชิก | `admin/member-tags/` | `members.manage` | [05-member-tags.md](05-member-tags.md) | 🟢 T-S1–S3 done · deferred: T-S4 tag members page, T-S5 reorder |
| **6** | **ผู้ปกครอง** | `admin/guardians/` | `guardians.*` | [06-guardians.md](06-guardians.md) | 🟢 **เฟส A + B เสร็จครบ verified 2026-08-26** — G-S0–G-S5 ✅ · **G-S3 ครบทุกจุดอ่าน** ✅ · **G-S6 drop `student_guardians` แล้ว** ✅ (dev · production ยังไม่ได้รัน) · G-S7–G-S11 ✅ → **เฟส C เริ่มแล้ว 2026-08-28** — O1 ตัดสินแล้ว (ผูกกับบัญชีที่มีอยู่ + ยินยอมสองทาง ดู §0 D7–D12) · แตกเป็น G-S12a–e + G-S13 · แดชบอร์ดผู้ปกครองยังใช้งานไม่ได้จนกว่าจะจบเฟสนี้ |
| **7** | **ตั้งค่าโรงเรียน** | `admin/settings.vue` | `settings.manage` | [07-settings.md](07-settings.md) | 🟢 **ปิดครบทุก step แล้ว 2026-09-02** — SET-S1–S11 + S13 ✅ (gap 12 ข้อปิดหมด · เทสต์ 4 ไฟล์ 35 เคส) · เหลือ SET-S12 🔵 deferred (รูปเป็น relative path — รอทำพร้อม migration รูปทั้งระบบ ดู `.agents/photo-path-migration-plan.md`) |
| **8** | **ระบบบริหารโรงเรียน** | `admin/school-management.vue` | `settings.manage` | [08-school-management.md](08-school-management.md) | 🔴 **audit เสร็จ 2026-09-07 — พบ gap 14 ข้อ** · P0: **203 จาก 204 route ไม่มีด่านสิทธิ์เลย** พิสูจน์แล้วว่า user ที่ไม่ใช่สมาชิกอ่านเงินเดือน/ค่าเทอม/รายจ่าย/แฟ้มบุคลากรของโรงเรียนไหนก็ได้ (G1) · UI เรียกฟังก์ชันที่ไม่มีจริง 4 จุด (G5) · endpoint ชี้ผิด path/verb 4 เส้น (G6) · แท็บที่เขียนเสร็จแต่เข้าไม่ถึง 791 บรรทัด (G7) · แตกเป็น SM-S1–S10 · **SM-S1 ✅ ปิดแล้ว 2026-09-09** (204/204 route มีด่านสิทธิ์ + ด่านโรงเรียนเก็บถาวร · เทสต์ 8 เคส · agy เขียน Claude ตรวจ) · ต่อไปทำได้ทันที: **S3/S4/S6** · ส่วน S2/S5/S7/S9 รอ Q1–Q3 |
| **9** | **ฝ่าย/แผนก** | `admin/departments/` | `departments.*` | [09-departments.md](09-departments.md) | 🟢 **ปิดครบ D-S1–D-S7** (2026-08-24) — ช่องโหว่ route อุดแล้ว · สิทธิ์ระดับฝ่ายมีผลจริง · หน้ารายละเอียดฝ่ายยกเครื่องแล้ว · **เหลืองานป้อนข้อมูล: จัดครู 120 คนเข้าฝ่าย (รอรายชื่อจากโรงเรียน)** |
| 10 | ห้องเรียน | `admin/classrooms/` | `groups.view` / `groups.manage` | [10-classrooms.md](10-classrooms.md) | 🟢 **CL-S1–S6 ปิดครบ 2026-09-15** — ปิดครบทุก step ที่ไม่ติด Q (CL-S1 wherePivot status=2 `03704c4e` · CL-S2 prod-guard command `c8176236` · CL-S3 FE roster 405 `d358cce0` · CL-S6 mobile static-clear) · **CL-S1/S2/S3/S4/S6 done 2026-09-15** — CL-S4 ยกด่านเข้า `Academy::userCan(groups.view/manage)` แล้ว `776258cf` (ClassroomPermissionGuardTest 5/5 · ClassroomManagementTest 19/19) · Q1–Q3 เคาะแล้ว (groups.* ร่วม · reads=groups.view · userCan) · CL-S5 เทสต์ปิดแล้ว `bad45952` (full classroom suite 128/128) · **เหลือแค่ CL-S7 legacy-column deferred + ตรวจจอจริง CL-S3** |
| **11** | **ตารางเรียน** | `admin/schedule.vue` | `schedule.view` / `schedule.manage` | [11-schedule.md](11-schedule.md) | 🔴 **audit เสร็จ 2026-09-16 — เมนูตายทั้งเมนู** · ทุก endpoint `/schedules` ตอบ 404 ให้ทุกคนรวมเจ้าของโรงเรียน (ด่านหา academy ด้วย `id` · controller หาด้วย `name` — ยิงจริงยืนยันแล้ว G1) · อีก 4 จุด P0: FE ไม่ส่ง query param เลย (G2) · ไม่ส่ง `semester_id` (G3) · ใช้ PUT ชน PATCH 405 (G4) · endpoint วิชาผิด path 404 (G5) · กันเวลาชนผิดขอบ ทำให้จัดคาบติดกันไม่ได้ (G6) · ภาคเรียนชี้ปี 2568 ทั้งที่ปีปัจจุบัน 2569 (G7) · **เคาะ D1–D5 แล้ว 2026-09-16** (id · โครงคาบตั้งค่าเองได้ · สร้างภาคเรียน 2569 · ฝ่ายวิชาการจัดตาราง+อ่านเปิดกว้าง · **วิชา = `courses` ไม่ใช่ `subjects`**) · แตกเป็น SC-S1–S11 · **SC-S1 ✅ ปิดแล้ว 2026-09-16** (resolve ด้วย id + ด่าน tenant + `update` ย้ายห้องได้จริง · เทสต์ใหม่ 8 เคส · ยิงเซิร์ฟเวอร์จริงแล้ว `/schedules` กลับมา 200) · **SC-S2 ✅ ปิดแล้ว 2026-09-16** (migration `subject_id`→`course_id` + `title`/`entry_type`/`period_id` · รัน migrate→rollback→migrate บน dev พิสูจน์ `down()` แล้ว · ยิงจริงสร้างคาบผูกคอร์ส `ง 20201` และคาบ "ชุมนุม" แบบไม่มีคอร์สได้) · **SC-S3 ✅ ปิดแล้ว 2026-09-16** (ภาคเรียน 1/2569 + 2/2569 สร้างแล้ว · `is_current` ย้ายมาปีปัจจุบัน · `Semester::currentForAcademy()` ผูกโรงเรียน + แก้ต้นตอ `setAsCurrent()` ที่ทำให้ภาคเรียนค้างปีเก่า) · **SC-S4 ✅ ปิดแล้ว 2026-09-16** (หน้าจอกลับมาใช้งานได้ครั้งแรก: ส่ง query params/PATCH/semester_id · ตัวเลือกปี-ภาคเรียน · ตัวเลือกคอร์สจริง 22 รายการ + ครูจริง 120 คน · ตรวจบนจอจริง 375/768/1280 และทำ CRUD ครบวงผ่าน UI) · **SC-S5 ✅ ปิดแล้ว 2026-09-17** (สูตรกันชนเป็น half-open ⇒ **จัดคาบติดกันได้แล้ว** G6 · เพิ่มกันสถานที่ `room` ชนครบทั้ง store/update/bulk/check-availability · ปิด G20 (เคลียร์คอร์สจนคาบไร้ชื่อ) + G21 (PATCH ช่วงเวลากลับหัว) · เทสต์ใหม่ 27 เคส + ยิงจริงบน MySQL 16 เคส · เจอ **G22** unique index ระดับ DB ที่ไม่รู้จัก `status` ยกไป SC-S11) · **SC-S6 ✅ ปิดแล้ว 2026-09-17** (ชุดโครงคาบตั้งเองได้**หลายชุด** ผูกวัน/ระดับชั้น · หน้าตั้งค่า `/admin/schedule-periods` + เมนูซ้าย · กริดวาดจากคาบจริงแทน 08:00–16:00 ฮาร์ดโค้ด **G14 ปิด** · คอลัมน์วันมาจากข้อมูลจริงและรับเสาร์–อาทิตย์ **G15 ปิด** · คาบนอกโครงมีแถวของตัวเองไม่หายจากจอ · เทสต์ใหม่ 20 เคส · migrate→rollback→migrate บน MySQL จริง · ตรวจบนจอจริง 375/768/1280) · เจอเพิ่ม **G23** (โมดัล admin `z-50` โดนแถบเมนูล่างมือถือทับจนกดบันทึกไม่ได้ → ยกไป SC-S8) และ **G24** (ทั้งเรพอ่าน error ของ `useApi` ผิดที่ ข้อความ 422 ไม่ถึงผู้ใช้ — แก้แล้วเฉพาะ 2 หน้าของเมนูนี้) · **SC-S7 ✅ ปิดแล้ว 2026-09-17** (ด่านสิทธิ์ 3 ชั้น `schedule.view.own` / `schedule.view` / `schedule.manage` **G10 ปิด** · migration เติมคีย์เข้าบทบาทจริง: director/admin ได้ `schedule.manage` · student/parent ได้ `schedule.view` · `GET /schedules/my` คืนเป็นบริบท (ครู/ห้องเรียน) · หน้า `my-schedule` ใช้ composable `useScheduleGrid` ร่วมกับหน้า admin · เทสต์ใหม่ 17 เคส · ยิงจริงด้วยบัญชีครู/นักเรียนจริง · **ผู้ปกครองยังทำไม่ได้** เพราะ `guardians.user_id` ว่างทั้งตาราง) · **SC-S8 ✅ ปิดแล้ว 2026-09-17** (G16 + **G23 ปิด** — ต้นเหตุคือ `BottomNav` เป็น `z-50` เท่าโมดัล **125 จุดใน 87 ไฟล์** จึงแก้ที่แถบเมนูจุดเดียวเป็น `z-40` คุ้มทั้งเรพ · เพิ่ม `CommonSearchableSelect` ให้ช่องเลือกครู 120 คน/ห้อง 53 ห้อง/คอร์ส ค้นหาได้ · G8/G9 ปิดไปแล้วตั้งแต่ SC-S4) · **SC-S9 ✅ ปิดแล้ว 2026-09-17** (ทั้งสองจุด "ยิงติดแล้วแต่อ่านผิด": การ์ดครูอ่าน `data` เป็นอาเรย์ ทั้งที่เป็นอ็อบเจกต์ `{date, day_name, schedules}` และอ่านคีย์ที่ไม่มีจริง (`time`/`subject`/`students`) · แท็บ #8 ไม่บอกวิชา/ครู และปุ่ม "เพิ่มตารางเรียน" เป็นปุ่มตายเพราะไม่มีโมดัล · แก้ให้ `today()` คืนรูปเดียวกับ `index()` + เทสต์ใหม่ 5 เคส) · ต่อไป **SC-S10** (พิมพ์/ส่งออกตาราง · UI bulk · คัดลอกข้ามภาคเรียน · สอนแทน/งดคาบ · ภาระงานครู) และ **SC-S11** (เทสต์ + migration จัดการ unique index G22) |
| 12 | คอร์สเรียน | `admin/courses/` | `courses.view` / `courses.manage` | 12-courses.md | ⚪ |
| 13 | หลักสูตร | `admin/curriculums.vue` | `courses.view` / `courses.manage` | 13-curriculums.md | ⚪ |
| 14 | บุคลากร | `admin/staff.vue` | `staff.view` / `staff.manage` | 14-staff.md | ⚪ |
| 15 | ทะเบียนนักเรียน | `admin/students.vue` + `admin/students/` | `students.view` / `students.manage` | 15-students.md | ⚪ |
| 16 | บัตรนักเรียน | `admin/student-cards/` | `students.view` / `students.manage` | 16-student-cards.md | 🟡 |
| 17 | เยี่ยมบ้าน | `admin/home-visits/` | `home_visits.view` / `home_visits.manage` | 17-home-visits.md | ⚪ |
| 18 | การเข้าเรียน | `admin/attendance` + `admin/school-attendance/` | `attendance.view` / `attendance.manage` | 18-attendance.md | ⚪ |
| 19 | ผลการเรียน | `admin/grades` + `admin/gradebook/` | `grades.view` / `grades.manage` | 19-grades.md | ⚪ |
| 20 | ประกาศ | `admin/announcements.vue` | `announcements.manage` | 20-announcements.md | ⚪ |
| 21 | สถิติและรายงาน | `admin/reports.vue` | `reports.view` | 21-reports.md | ⚪ |
| 22 | ประวัติกิจกรรม | `admin/activity-log/` | `reports.view` | 22-activity-log.md | ⚪ |
| 23 | รายได้ | `admin/revenue.vue` | `finance.view` / `finance.manage` | 23-revenue.md | 🟡 Phase 1 done |
| **24** | **แดชบอร์ด** | `admin/index.vue` | `academy.view` | 24-dashboard.md | ⚪ (ทำหลังสุด — รวม signal) |
| **25** | **การเลือกตั้งสภานักเรียน** | `admin/elections/` + `elections/[id]/station.vue` | `elections.view` / `elections.manage` / `elections.station` | [25-elections.md](25-elections.md) | 🟢 **E-S1–E-S12 ปิดครบ · หนี้ตรวจบนจอจริงปิดแล้ว 2026-08-27** (§13.9 — 429 ขึ้นจอที่ 374px · limiter คีย์ต่อหน่วยพิสูจน์แล้ว · แบนเนอร์ไม่ทับแผงยืนยัน gap 14px) · **เหลืองานที่ไม่ใช่โค้ด: ยังไม่ได้ซ้อมตาม §9 เลย** |
| **26** | **เช็คชื่อเข้าร่วมกิจกรรม** | `admin/events/` + หน้า session check-in (ยังไม่มี) | `events.view` / `events.manage` | [26-activity-attendance.md](26-activity-attendance.md) | 🟢 **ปิดครบ A-S0–A-S6** (2026-08-01) |
| **27** | **กีฬาสี** | ยังไม่มี | `sports.*` (เสนอใหม่) | [27-sports-day.md](27-sports-day.md) | 🟢 **ปิดครบ S-S1–S-S7** (2026-08-20) |

> **เมนู #25–#27 เพิ่ม 2026-07-31 ตามคำสั่งเร่งด่วนของเจ้าของโปรเจค** — ทั้งสามเป็นงานของ **ฝ่ายบริหารงานกิจการนักเรียน** และแทรกคิวก่อนเมนูที่เหลือ · ลำดับที่ตกลง: **#25 ก่อน** แล้วค่อย #26 → #27

---

## ลำดับการทำงาน (ตามที่ตกลงกัน)

เริ่มจาก **#1 บทบาทและสิทธิ์** (เป็นรากของทุกอย่าง) แล้วไล่ตามลำดับตัวเลขในตารางด้านบน ยกเว้น #24 แดชบอร์ดทำท้ายสุด

**ไม่ทำงานคู่ขนานข้ามเมนู** — จบเมนูหนึ่งค่อยขึ้นเมนูถัดไป

### ปรับลำดับ (2026-07-29)

**#6 ผู้ปกครอง → เขียนสเปกไว้แล้วแต่ยังไม่ implement, ข้ามไปทำ #9 ฝ่าย/แผนก ก่อน**

เหตุผล: #6 เป็น *งานย่อยระดับฝ่าย* (ใต้ฝ่ายบริหารงานกิจการนักเรียน + งานทะเบียนของฝ่ายวิชาการ) ซึ่งต้องใช้โมเดลสิทธิ์ระดับฝ่ายที่ยังไม่มี — ตัวเดียวกับที่ S7/S8 ของเมนู #1 ถูก defer มารอที่ #9 เมนูที่รอข้างหน้า (#14 บุคลากร, #17 เยี่ยมบ้าน, #18 การเข้าเรียน, #19 ผลการเรียน) ก็ผูกกับฝ่ายแบบเดียวกัน → ตัดสินใจโมเดลทีเดียวที่ #9 แล้วเมนูที่เหลือไหลตามได้ทั้งแถว

**ลำดับใหม่:** #9 → กลับมา #6 (เฟส B/C) → ต่อ #7, #8, #10 …
รายละเอียดข้อตกลงทั้งหมดของ #6 อยู่ใน [06-guardians.md](06-guardians.md) §0

### สถานะคิว (ทวนกับโค้ดจริง 2026-08-25)

**คิวเร่งด่วนปิดครบแล้ว** — #25 เลือกตั้ง (E-S1–E-S12 ✅ 2026-08-24) · #26 เช็คชื่อกิจกรรม ✅ · #27 กีฬาสี ✅
**#9 ฝ่าย/แผนก ปิดครบ D-S1–D-S7 แล้ว (2026-08-24)** ซึ่งเป็นตัวที่เมนูอื่นรออยู่

→ **#6 ผู้ปกครอง เฟส A + B ปิดครบแล้ว (2026-08-26)** — **เฟส C เริ่ม 2026-08-28**: O1 ปิดแล้ว, สเปก G-S12a–e ล็อกใน [06-guardians.md](06-guardians.md) §6.3–6.4
   ลำดับในเมนูนี้: ~~G-S7~~ → ~~G-S8~~ → ~~G-S9~~ → ~~G-S10~~ → ~~G-S11 (FE)~~ → ~~G-S3 ที่เหลือ~~ → ~~G-S6 (drop ตารางเก่า)~~ — **ครบแล้ว 2026-08-26** เหลือเฟส C (G-S12)
   หลังจบ #6 ค่อยไป #7 ตั้งค่าโรงเรียน → #8 → #10

→ **#7 ตั้งค่าโรงเรียน ปิดครบทุก step แล้ว 2026-09-02** (SET-S1–S11 + S13 · เหลือ S12 deferred)

→ **#8 ระบบบริหารโรงเรียน เริ่มแล้ว 2026-09-07** — ขั้น [1] สแกนโค้ด + [2] เขียนไฟล์รอง **เสร็จแล้ว**
   ([08-school-management.md](08-school-management.md)) · ยังไม่ส่ง step ไหนให้ codex/agy
   ✅ **SM-S1 ปิดช่องโหว่สิทธิ์ครบ 204 route แล้ว 2026-09-09** — ทุกกลุ่มมี `academy.permission` + `academy.visibility:content`
   (38 เส้นเป็นด่านสมาชิกเปล่าโดยตั้งใจ: ลงเวลา · ใบลาของตัวเอง · dashboard ครู-นักเรียน · layout ส่วนตัว · นัดพบผู้ปกครอง)
   **S1/S3(7·8)/S4/S6/S8/S10 ✅ done 2026-09-09** — ปิดครบทุก step ที่ไม่ติด Q (เทสต์ 4 ไฟล์ 18 เคส) · **เหลือ S2/S5/S7/S9 รอ Q1–Q3** · หนี้นอกเมนู: Analytics audit 5 จุด · finance schema-drift ทั้งหมวด (ผูก Q3)
   ส่วน SM-S2/S5/S7/S9 ติด Q1–Q3 ที่ต้องให้เจ้าของโปรเจคเคาะ (ดู [08-school-management.md](08-school-management.md) §5.1)
   หลัง #8 ค่อยไป #10 ห้องเรียน

✅ **backfill รันจริงแล้ว (2026-08-25) และ G-S6 drop ตารางเก่าแล้ว (2026-08-26)** — ฐาน dev มี
   `guardians` 4,504 · `student_guardian_links` 4,999 · `guardian_contacts` 4,853 · **`student_guardians` ไม่มีแล้ว**
   ⚠️ **production ยังไม่ได้รัน migration `2026_08_26_000001_drop_legacy_student_guardians_table`**
   ก่อนรันบน production ให้ `mysqldump` ตาราง `student_guardians` + `guardian_contacts` ไว้เองก่อนเสมอ (ดู 06-guardians.md §8.8)

→ **#11 ตารางเรียน เริ่มแล้ว 2026-09-16** — ขั้น [1] สแกนโค้ด + [2] เขียนไฟล์รอง **เสร็จแล้ว**
   ([11-schedule.md](11-schedule.md)) · ยังไม่ส่ง step ไหนให้ codex/agy
   🔴 เมนูนี้ **ยังไม่เคยใช้งานได้จริงเลยสักครั้ง** — `/schedules` ทุกเส้น 404 (G1) ยิงจริงยืนยันแล้ว §2.5
   ข้อมูลใน `class_schedules` มีแค่ 5 แถวเดโมของปี 2568 · `schedule_periods` / `teacher_assignments` ว่างเปล่า
   **เคาะครบแล้ว D1–D5** (ดู [11-schedule.md](11-schedule.md) §6) — ที่สำคัญที่สุดคือ **D5: ตารางเรียนผูกกับ `courses` ไม่ใช่ `subjects`**
   (`subject_id` มีอยู่ใน 5 ตารางและว่างหมด · งานจริงทั้งระบบแขวนกับ `course_id`) ⇒ SC-S2 เป็น migration สลับคอลัมน์
   ⚠️ หนี้ข้ามเมนู: คอร์สจริงมีแค่ 22 รายการ (วิชาคอมพิวเตอร์ของครูคนเดียว) วิชาที่เหลือของโรงเรียนต้องรอ **เมนู #12**
   ระหว่างนี้ให้แถวตารางกรอกชื่อรายการเองได้ (`course_id` nullable + `title`) จะได้ไม่บล็อกฝ่ายวิชาการ

**งานที่ไม่ใช่งานโค้ดและยังค้าง:** ซ้อมการเลือกตั้งกับนักเรียน 1 ห้องตาม [25-elections.md](25-elections.md) §9
(ยังไม่ได้ทำเลย) · จัดครู 120 คนเข้า 5 ฝ่าย (รอใบกรอกจากฝ่ายบุคคล)

---

## ตำแหน่งงานอื่น ๆ (nested pages) ที่ไม่อยู่ในเมนูตรง แต่ต้องนับรวมในเมนูแม่

- `admin/allocations.vue`, `admin/at-risk.vue`, `admin/events/`, `admin/store/` — ต้อง map เข้าเมนูใดเมนูหนึ่งใน audit
- `admin/dashboard/admin.vue` (จาก `academies/[name]/dashboard/admin.vue`) — ตรวจว่าซ้ำกับ `admin/index.vue` หรือไม่

Audit จุดพวกนี้ให้เสร็จตอนเข้าเมนูที่เกี่ยวข้อง

---

## Template ของไฟล์รอง

ดู [_template.md](_template.md) (สร้างเมื่อเริ่มเมนูแรก)
