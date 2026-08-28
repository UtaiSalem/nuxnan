# 06 — ผู้ปกครอง (Guardians)

> ไฟล์รองของเมนู **#6 ผู้ปกครอง** ใน [OVERVIEW.md](OVERVIEW.md)
> สถานะ: **สเปกล็อกแล้ว — ยังไม่ implement** (รอ #9 ฝ่าย/แผนก ยกเว้น G-S0)
> วันที่สรุป: 2026-07-29 (เสวนากับเจ้าของโปรเจค)

---

## 0. ข้อตกลงที่ล็อกแล้ว (Locked Decisions)

| # | ประเด็น | ข้อสรุป |
|---|---|---|
| **D1** | ลำดับงาน | ทำ **#9 ฝ่าย/แผนก ก่อน** แล้วค่อยกลับมา implement #6 — เพราะ #6 เป็น *งานย่อยระดับฝ่าย* ที่ต้องพึ่งโมเดลสิทธิ์ระดับฝ่าย ซึ่งยังไม่มี (S7/S8 ของเมนู #1 ถูก defer มารวมที่ #9) |
| **D2** | เจ้าของข้อมูล | **เจ้าของร่วม 2 ฝ่าย** — ฝ่ายวิชาการ→งานทะเบียนและวัดผล และ ฝ่ายบริหารงานกิจการนักเรียน แก้ไขได้เท่ากัน |
| **D3** | ขอบเขต | **แหล่งข้อมูลเดียว (single source)** ให้ทุกงานใช้ร่วมกัน (ทะเบียน / เยี่ยมบ้าน / คัดกรอง / ติดต่อฉุกเฉิน) + รองรับ **ผูกบัญชีให้ผู้ปกครองล็อกอิน** ในอนาคต |
| **D4** | การมองเห็น | **แบ่งตามหน้าที่ + ซ่อนฟิลด์อ่อนไหว** — ฟิลด์อ่อนไหว = `citizen_id` (เลขบัตร ปชช.) และ `monthly_income` (รายได้) |
| **Q1** | สิทธิ์แก้ระดับฟิลด์ | **ฟิลด์ที่ซ่อน = ฟิลด์ที่แก้ไม่ได้ด้วย** (เห็นไม่ได้ก็แก้ไม่ได้) → `citizen_id` / `monthly_income` เป็นของงานทะเบียนอย่างเดียว ส่วนชื่อ / ความสัมพันธ์ / ช่องทางติดต่อ แก้ได้ทั้ง 2 ฝ่าย |
| **Q2** | ประวัติการแก้ไข | **เก็บ** — ผูก `MemberActivityLog` ทั้ง create/update/delete และ log การ *เปิดดู* ฟิลด์อ่อนไหว (แบบเดียวกับ `withdrawal.proof_viewed`) |
| **Q3** | การผูก/แต่งตั้งผู้ปกครอง | ข้อมูล *การกำหนดผู้ปกครอง* **มีอยู่แล้ว** (2,449 นักเรียน / 5,045 แถว) สิ่งที่ขาดคือผู้ปกครองเหล่านั้น **ยังไม่มีบัญชีผู้ใช้เลยสักคน** → ทำระบบทะเบียนผู้ปกครองให้ถูกต้องก่อน แล้วค่อยเปิดบัญชี ส่วนการแก้ไข/เพิ่มการแต่งตั้งต่อจากนี้ ทำได้ **3 ทาง**: (1) นักเรียนแต่งตั้งเอง (2) ครูประจำชั้นแต่งตั้ง (3) ฝ่ายทะเบียนแต่งตั้ง — **นักเรียนที่ไม่มีผู้ปกครองให้เว้นว่างได้** ฟีเจอร์ผู้ปกครองใช้ไม่ได้เฉพาะคนนั้น แต่ฟีเจอร์อื่นต้องใช้ได้ปกติ |
| **D6** | นิยามผู้ปกครองในเชิงระบบ | **ระบบต้องการแค่ "บุคคลนี้เป็นผู้ปกครองของนักเรียนคนนี้"** — ไม่ต้องระบุว่าเป็นพ่อ/แม่/ปู่/ลุง ประเภทความสัมพันธ์เป็นเพียง **รายละเอียดของฝ่ายทะเบียน** (optional, ไม่ผูกกับ logic ใด ๆ) · **นักเรียน 1 คนมีผู้ปกครองกี่คนก็ได้** ขอแค่มีอย่างน้อย 1 คนถึงจะใช้ฟีเจอร์ผู้ปกครองได้ · **เก็บข้อมูลเดิมทั้ง 5,045 แถวไว้ครบ ไม่ตัดทิ้ง** (ปัจจุบัน: 1 คน→113 นักเรียน, 2 คน→2,076, 3 คน→260) |
| **D5** | โครงสร้างข้อมูล | **แยกเป็นระดับบุคคล (ทาง B)** — ตาราง `guardians` (คน) + ตารางเชื่อม `student_guardian` (ความสัมพันธ์) เพราะข้อมูลปัจจุบันเก็บแบบ *1 แถว = ผู้ปกครองของนักเรียน 1 คน* ทำให้พ่อ/แม่ที่มีลูกหลายคนถูกบันทึกซ้ำ (เลขบัตรซ้ำ 696 เลข / 1,666 แถว / เกิน 970 แถว → คนจริง ~4,075 คน) ถ้าเปิดบัญชีบนโครงเดิมจะได้บัญชีซ้ำและเห็นลูกไม่ครบ — **ทำตอนนี้ที่ยังไม่มีใครล็อกอิน คือจังหวะที่เสี่ยงน้อยที่สุด** |

### สมมติฐานที่ตั้งไว้ (ถ้าไม่ตรงให้แก้ก่อน implement)
- **A1** — การแต่งตั้งโดยนักเรียนเอง **ไม่ต้องรออนุมัติ** ในเฟสนี้ เพราะผู้ปกครองยังไม่มีบัญชี = ยังไม่มีสิทธิ์เห็นข้อมูลอะไร ความเสี่ยงต่ำ แต่จะบันทึก *ใครแต่งตั้ง* ไว้ทุกครั้ง และเว้นช่อง `verified_by_user_id` ไว้ให้ครู/ทะเบียนมายืนยันทีหลัง
- **A2** — คอลัมน์ `user_id` ไปอยู่ที่ตาราง `guardians` (ระดับคน) ตาม D5 — ผู้ปกครอง 1 คน = 1 บัญชี = เห็นลูกได้ทุกคนผ่านตารางเชื่อม
- **A3** — เมนู #6 ยังคงเป็นเมนูใน admin panel ตามเดิม แต่ *สิทธิ์* ผูกกับฝ่าย ไม่ใช่ผูกกับ role ระดับโรงเรียนอย่างเดียว

### ✅ O1 ตัดสินแล้ว (2026-08-28) — บัญชีผู้ปกครองเกิดจาก "บัญชีที่มีอยู่ + ยินยอมสองทาง"

**เจ้าของโปรเจคปฏิเสธทั้ง 3 ทางเลือกเดิม** (เชิญทาง SMS / สมัครเองแล้ว claim ด้วยเลขบัตร / เจ้าหน้าที่สร้างบัญชีให้)
โมเดลที่เลือกคือ **โรงเรียนไม่สร้างบัญชีให้ใครทั้งสิ้น** — ผู้ปกครองต้องเป็นสมาชิกในระบบ nuxnan อยู่ก่อนแล้ว
**คนที่ไม่มีบัญชีในระบบ เป็นผู้ปกครอง (ที่ล็อกอินได้) ไม่ได้**

| # | ประเด็น | ข้อสรุป |
|---|---|---|
| **D7** | วิธีได้มาซึ่งบัญชี | **ผูกกับบัญชีผู้ใช้ที่มีอยู่แล้วเท่านั้น** ไม่มี provisioning, ไม่มี placeholder account, ไม่มี SMS · ทะเบียนผู้ปกครอง 4,504 คนยังเป็นข้อมูลทะเบียนตามเดิม ส่วน "บัญชี" เป็นชั้นที่ผูกทีหลังทีละคน |
| **D8** | การยินยอม | **ต้องมีการกดยอมรับเสมอ** — การผูกบัญชีไม่เคยมีผลจากฝ่ายเดียว |
| **D9** | ทิศทางคำขอ 4 ทาง | (1) **นักเรียนเลือกบัญชี** → บัญชีนั้นกดรับ · (2) **ผู้ปกครองเลือกนักเรียน** → นักเรียนกดรับ · (3) **ครูประจำชั้นเลือกบัญชีให้นักเรียน** → **ผู้ปกครองกดรับอย่างเดียว** · (4) **ฝ่ายทะเบียนเลือกบัญชีให้นักเรียน** → **ผู้ปกครองกดรับอย่างเดียว** |
| **D10** | ทำไมทาง (3)/(4) นักเรียนไม่ต้องกด | โรงเรียนเป็นผู้รับรองความสัมพันธ์อยู่แล้ว · ฝ่ายที่ต้องยินยอมคือฝ่ายที่จะ**ได้เห็นข้อมูลเด็ก** = เจ้าของบัญชี · และนักเรียน ป.1/ม.1 ที่ยังไม่เคยเข้าแอปจะทำให้คำขอค้างเยอะถ้าบังคับให้กด |
| **D11** | วิธีค้นหาบัญชี | **ตรงตัวเท่านั้น 2 ทาง: `username` / `personal_code` และ `phone_number`** — ห้ามค้นด้วยชื่อ ห้ามคืนรายการให้ไล่ดู (กันนักเรียนไล่ส่องผู้ใช้ทั้งระบบ) · ต้องรู้ค่าที่แน่นอนถึงจะเจอ |
| **D12** | ผูกกับทะเบียนเดิม | **เลือกแถว `guardians` เดิมของนักเรียนคนนั้นก่อนเสมอ** (เช่น "นางสมศรี ใจดี" ที่มีอยู่ในทะเบียน) แล้วเซ็ต `user_id` ทับแถวนั้น → ทะเบียน/เยี่ยมบ้าน/ติดต่อฉุกเฉินไม่แตก · **สร้างแถวใหม่จากบัญชีเฉพาะเมื่อไม่มีแถวเดิมให้ผูก** |

### สมมติฐานเฟส C (ถ้าไม่ตรงให้แก้ก่อน implement)
- **A4** — ตอนกดรับ ระบบสร้างแถว `academy_members` ให้เจ้าของบัญชีอัตโนมัติ role `parent`, **`status = 2` (approved)** ไม่ต้องรอแอดมินอนุมัติซ้ำ เพราะการกดรับ + ต้นทางที่ชอบธรรมคือการอนุมัติแล้ว
  ⚠️ `TeacherAutoIntakeService` เขียน `status = 1` ไว้ **อย่าลอกค่านั้น** (1 ไม่ใช่ approved)
- **A5** — ถ้าเจ้าของบัญชี**มีแถวสมาชิกอยู่แล้ว**ในโรงเรียนนี้ (เช่น เป็นครูและเป็นผู้ปกครองด้วย) → **ห้ามเขียนทับ role เดิม** ให้คงของเดิมไว้ สิทธิ์ดูลูกมาจาก `guardians.user_id` ไม่ได้มาจาก role
- **A6** — 1 บัญชี = 1 แถว `guardians` ต่อ 1 โรงเรียน (unique `(academy_id, user_id)`) · เห็นลูกได้ทุกคนผ่าน `student_guardian_links` ตาม A2
- **A7** — การปลดการผูก (`user_id` → null) ทำได้โดยผู้มีสิทธิ์ `guardians.manage` หรือเจ้าของบัญชีเอง · **ไม่ลบแถวสมาชิกทิ้งอัตโนมัติ** (กันลบสิทธิ์ครูโดยไม่ตั้งใจ) แค่ตัดสายที่ทำให้เห็นข้อมูลเด็ก
- **A8** — `verified_at` / `verified_by_user_id` บน `student_guardian_links` เป็นคนละแกนกับการผูกบัญชี (อันนั้นคือฝ่ายทะเบียนรับรองความสัมพันธ์ตาม G-S10) — **การกดรับคำขอไม่ไปแตะฟิลด์ verified_***

---

## 1. Scope & Purpose

ทะเบียนผู้ปกครองของนักเรียนทั้งโรงเรียน เป็น **แหล่งข้อมูลกลาง** ที่งานอื่นดึงไปใช้ ครอบคลุม:

1. **ข้อมูลผู้ปกครองรายคน** — ประเภท (บิดา/มารดา/ปู่ย่าตายาย/ลุงป้าน้าอา/พี่น้อง/อื่นๆ), คำนำหน้า-ชื่อ-สกุล, เลขบัตร ปชช., อาชีพ, ที่ทำงาน, รายได้ต่อเดือน, สัญชาติ, สถานะ (มีชีวิต/เสียชีวิต/ไม่ทราบ)
2. **ช่องทางติดต่อ** (`guardian_contacts`) — โทรศัพท์/มือถือ/อีเมล/LINE/Facebook หลายรายการต่อคน + ธง `is_primary`, `is_verified`
3. **การแต่งตั้งผู้ปกครองให้นักเรียน** — 3 ทางตาม Q3 + ธง ผู้ติดต่อหลัก / ผู้ติดต่อฉุกเฉิน
4. **(เฟสอนาคต) ผูกบัญชีผู้ใช้** — ให้ผู้ปกครองล็อกอินเข้า Parent Dashboard

**ผู้ใช้ที่เกี่ยวข้อง:** งานทะเบียนและวัดผล (ฝ่ายวิชาการ), ฝ่ายบริหารงานกิจการนักเรียน (โดยเฉพาะงานระบบดูแลช่วยเหลือนักเรียน), ครูประจำชั้น, นักเรียน, ผู้ปกครอง (อนาคต)

**งานที่ consume ข้อมูลนี้:** #17 เยี่ยมบ้าน, at-risk/คัดกรอง, บัตรนักเรียน (ผู้แจ้ง), ติดต่อฉุกเฉิน, Parent Dashboard

---

## 2. Current State (จากการสแกนโค้ดจริง 2026-07-29)

### Frontend
- `ui/pages/academies/[name]/admin/guardians/index.vue` — **ไฟล์เดียว, read-only**: การ์ดสถิติ 4 ใบ, ค้นหา, filter ประเภท, ตาราง, pagination, ปุ่มโทร
- ไม่มี component / composable / store แยก — เรียก API ตรงในหน้า
- ใช้ `$api` (ไม่ใช่ `useApi()`), ไม่มี `definePageMeta`, ไม่มี dark mode class

### Backend
- `app/Http/Controllers/Api/Learn/Academy/GuardianController.php` — 7 method: `index`, `store`, `update`, `destroy`, `linkUser`, `getAllGuardians`, `getStatistics` — **ไม่มี guard ใด ๆ** (เจตนา: เปิดชั่วคราวให้ใช้งานได้)
- `app/Http/Controllers/Api/Learn/Student/Master/GuardianController.php` — 3 method: `show`, `store`, `update` — **มี `$this->authorize('update', $student)` + `UpdateGuardianRequest`** → เป็นต้นแบบที่ดีกว่า
- Routes: `routes/learn/academy.php:252-300` (`{academy}/guardians`, `{academy}/students/{student}/guardians`, `{academy}/guardians/{guardian}`) — อยู่ใต้ `auth:api` เท่านั้น
- Routes อื่นที่แตะข้อมูลเดียวกัน: `routes/learn/student-profile.php:65-68`, `routes/homevisit/homevisit.php`, `routes/learn/academy-home-visit.php`
- Models: `StudentGuardian`, `GuardianContact`
- Parent Dashboard: `ParentDashboardController` (children / grades / attendance / announcements / events / fees) — endpoint พร้อม แต่ไม่มีใครล็อกอินเข้าได้เพราะยังผูกบัญชีไม่ได้

### Database (ตรวจกับ DB จริง `nuxnan_nuxnan_db`)

**`student_guardians`** — 5,045 แถว
```
id, academy_id, student_id, student_code,
guardian_type enum('father','mother','guardian','other'),
citizen_id, title_prefix, first_name, last_name,
occupation, workplace, monthly_income,
relationship, status enum('alive','deceased','unknown'),
nationality, is_primary_contact, is_emergency_contact, timestamps
```
**`guardian_contacts`** — 4,853 แถว
```
id, guardian_id, contact_type enum('phone','mobile','email','line','facebook'),
contact_value, is_primary, is_verified, timestamps
```

**ตัวเลขที่ใช้ตัดสินใจ:**
- ผู้ปกครองแยกตามประเภท: บิดา 2,377 / มารดา 2,407 / guardian 261 (ไม่มีประเภทอื่นเลย เพราะ enum ไม่รองรับ)
- นักเรียนทั้งหมด 2,931 คน — **มีบัญชีผู้ใช้แล้ว 2,930 คน** (ทางเลือก "นักเรียนแต่งตั้งเอง" ใช้ได้จริง)
- นักเรียนที่มีผู้ปกครองแล้ว 2,449 คน → **482 คนยังไม่มี** (ตรงกับเคส "เว้นว่างไว้")
- ผู้ปกครองที่มีบัญชีผู้ใช้: **0 คน** (ไม่มีคอลัมน์ `user_id` ด้วยซ้ำ)

**⚠️ Migration ซ้ำซ้อน:** มี migration สร้าง `student_guardians` **2 ไฟล์คนละ schema**
- `2025_10_26_070433_create_student_guardians_table.php` — schema เต็ม (ตัวที่ใช้จริง)
- `2026_02_01_183529_create_student_guardians_table.php` — schema คนละแบบ (`full_name`, `relation`, `phone_number`) มี `if (! Schema::hasTable())` ครอบไว้เลยไม่ทับ
→ เป็นระเบิดเวลา: ถ้าตารางหายแล้วรัน migrate ใหม่ในลำดับที่ต่างไป จะได้ schema ผิดตัว **ต้องลบ/ทำให้ no-op**

---

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | ดูรายชื่อผู้ปกครองทั้งโรงเรียน + ค้นหา + filter + pagination | ✅ | ทำงานได้ |
| 2 | สถิติผู้ปกครอง | ⚠️ | การ์ดโชว์แยกตามประเภท ซึ่งตาม D6 ไม่ใช่แกนหลักอีกต่อไป → ควรเปลี่ยนเป็น "นักเรียนที่มีผู้ปกครองแล้ว / ยังไม่มี (482 คน) / ผู้ปกครองที่มีช่องทางติดต่อ" |
| 3 | ดูผู้ปกครองรายนักเรียน | ✅ | `index()` |
| 4 | เพิ่มผู้ปกครอง | ❌ | BE มี `store()` แต่ **เขียน `status='active'` ที่ไม่มีใน enum → insert พังแน่นอน**; FE ไม่มีปุ่ม |
| 5 | แก้ไขผู้ปกครอง | ⚠️ | BE มี `update()`; FE ไม่มี UI |
| 6 | ลบผู้ปกครอง | ⚠️ | BE มี `destroy()`; FE ไม่มี UI |
| 7 | เพิ่มผู้ปกครองโดยไม่ต้องระบุประเภท (D6) | ❌ | ปัจจุบัน `guardian_type` เป็น **required** ใน validation → ขัดกับ D6 ต้องปลดเป็น optional |
| 8 | จัดการช่องทางติดต่อหลายรายการ | ⚠️ | BE สร้างได้แค่ตอน `store` (phone 1 + email 1); ไม่มี CRUD contact แยก |
| 9 | ยืนยันเบอร์/อีเมล (`is_verified`) | ❌ | มีคอลัมน์ ไม่มีโค้ดที่เซ็ต |
| 10 | แต่งตั้งผู้ปกครองโดยนักเรียน | ❌ | ยังไม่มี |
| 11 | แต่งตั้งโดยครูประจำชั้น | ❌ | ยังไม่มี |
| 12 | แต่งตั้งโดยฝ่ายทะเบียน | ⚠️ | ทำได้ผ่าน `store()` แต่พังตามข้อ 4 และไม่บันทึกว่าใครแต่งตั้ง |
| 13 | ผูกบัญชีผู้ใช้ให้ผู้ปกครอง | ❌ | `linkUser()` **ตอบ success แต่ไม่บันทึกอะไร** (ไม่มีคอลัมน์ `user_id`) |
| 14 | สิทธิ์ระดับฝ่าย | ❌ | ไม่มี guard เลย (เจตนา ชั่วคราว) — รอ #9 |
| 15 | ซ่อนฟิลด์อ่อนไหว | ❌ | `citizen_id` / `monthly_income` ส่งออกตาม role ไม่ได้ |
| 16 | ประวัติการแก้ไข (audit) | ❌ | ไม่มี |
| 17 | รองรับนักเรียนที่ไม่มีผู้ปกครอง | ⚠️ | ยังไม่ได้ตรวจว่าโค้ดที่ consume (เยี่ยมบ้าน/บัตรนักเรียน/ติดต่อฉุกเฉิน) พังไหมเมื่อไม่มีผู้ปกครอง |
| 18 | Error/empty/loading state ที่ FE | ❌ | `catch` แค่ `console.error` |

---

## 4. Permission Matrix

**Permission keys ใหม่ที่เสนอ** (แยกจาก `members.*` เพราะข้อมูลอ่อนไหวกว่า):

| Permission key | ความหมาย |
|---|---|
| `guardians.view` | ดูรายชื่อ/รายละเอียดผู้ปกครอง (ไม่รวมฟิลด์อ่อนไหว) |
| `guardians.manage` | เพิ่ม/แก้/ลบ ผู้ปกครอง + ช่องทางติดต่อ (ยกเว้นฟิลด์อ่อนไหว) |
| `guardians.sensitive.view` | เห็น `citizen_id`, `monthly_income` |
| `guardians.sensitive.manage` | แก้ `citizen_id`, `monthly_income` (ตาม Q1: ต้องมี `.view` ด้วยเสมอ) |
| `guardians.appoint` | แต่งตั้งผู้ปกครองให้นักเรียน |

| Permission | Owner | Admin | ทะเบียน (วิชาการ) | กิจการนักเรียน | ครูประจำชั้น | ครูทั่วไป | นักเรียน | ผู้ปกครอง |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| `guardians.view` | ✅ | ✅ | ✅ ทั้งโรงเรียน | ✅ ทั้งโรงเรียน | ✅ เฉพาะห้องตน | ❌ | ✅ ของตัวเอง | ✅ ของตัวเอง |
| `guardians.manage` | ✅ | ✅ | ✅ | ✅ | ✅ เฉพาะห้องตน | ❌ | ⚠️ ของตัวเอง (ดู A1) | ❌ |
| `guardians.sensitive.view` | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `guardians.sensitive.manage` | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `guardians.appoint` | ✅ | ✅ | ✅ | ✅ | ✅ เฉพาะห้องตน | ❌ | ✅ ของตัวเอง | ❌ |

> **หมายเหตุ D2:** "เจ้าของร่วม" = ทะเบียน กับ กิจการนักเรียน มี `guardians.manage` เท่ากัน ต่างกันแค่ฟิลด์อ่อนไหวที่เป็นของทะเบียนฝ่ายเดียว (Q1)
> **ขึ้นกับ #9:** การผูก permission เข้ากับ *ฝ่าย* (แทนที่จะเป็น role ระดับโรงเรียน) ต้องใช้โมเดลของเมนู #9 — เป็นเหตุผลของ D1

---

## 5. Gap Analysis

| ID | Gap | ระดับ | ขึ้นกับ #9? |
|---|---|---|---|
| **G1** | ไม่มี permission guard ทั้งชุด (auth:api อย่างเดียว) — *เปิดชั่วคราวโดยเจตนา ไม่ใช่บั๊ก* | 🟡 วางแผน | ✅ ใช่ |
| **G2** | `linkUser()` ไม่บันทึกอะไรจริง แต่ตอบ success + ไม่มีคอลัมน์ `user_id` | 🔴 | ❌ |
| **G3** | enum `guardian_type` ของตาราง**เก่า** มี 4 ค่า แต่ระบบใช้ 8 — เคยปิดไปตาม D6 ว่า "ค่อยเปลี่ยนเป็น varchar ตอนย้ายโครง" แต่ไม่เคยทำ ⇒ **แต่งตั้งเป็น `uncle` ตอบ 500 จริงบน MySQL** (STRICT mode: Data truncated) · เทสต์จับไม่ได้เพราะ SQLite ไม่บังคับ enum | 🔴 | ✅ **แก้แล้ว 2026-08-25** migration `2026_08_25_000003` → varchar(50) nullable |
| **G4** | `store()` เขียน `status='active'` ที่ไม่มีใน enum → เพิ่มผู้ปกครองพังทุกครั้ง | 🔴 | ❌ |
| **G5** | migration `student_guardians` ซ้ำ 2 ไฟล์ คนละ schema | 🔴 | ❌ |
| **G6** | ไม่มีระบบซ่อน/กันแก้ฟิลด์อ่อนไหว (D4/Q1) | 🔴 | ✅ ใช่ |
| **G7** | ไม่มี audit log ทั้ง CRUD และการเปิดดูฟิลด์อ่อนไหว (Q2) | 🟡 | ⚠️ บางส่วน |
| **G8** | ไม่มีเส้นทางแต่งตั้งผู้ปกครอง 3 ทาง + ไม่บันทึกว่าใครแต่งตั้ง (Q3) | 🟡 | ⚠️ บางส่วน |
| **G9** | GuardianController 2 ตัว / route 4 ชุด แตะข้อมูลเดียวกัน — ขัดกับ D3 (single source) | 🟡 | ❌ |
| **G10** | FE ไม่มี UI เพิ่ม/แก้/ลบ + ผิด convention (`$api`, ไม่มี definePageMeta, ไม่มี dark mode) | 🟡 | ❌ |
| **G11** | ไม่มี CRUD ช่องทางติดต่อแยก + ไม่มีการเซ็ต `is_verified` | 🟡 | ❌ |
| **G12** | การ์ดสถิติไม่ครบประเภท (261 คนหาย) + ไม่มี error/empty state | 🟢 | ❌ |
| **G13** | ยังไม่ได้ตรวจว่าโค้ดที่ consume พังไหมเมื่อนักเรียนไม่มีผู้ปกครอง (482 คน) | 🟡 | ❌ |
| **G14** | query ใช้ `whereHas('student')` ทั้งที่มีคอลัมน์ `academy_id` อยู่แล้ว | 🟢 | ❌ |

---

## 5.5 Target Data Model (D5 — ทาง B)

```
guardians                        ← 1 แถว = คน 1 คน (~4,075 คน)
  id, academy_id, user_id (nullable, เฟส C)
  citizen_id, title_prefix, first_name, last_name
  occupation, workplace, monthly_income        ← ฟิลด์อ่อนไหว 2 ตัวอยู่ที่นี่
  nationality, status enum('alive','deceased','unknown')
  legacy_row_ids (json, ไว้ตรวจย้อนกลับหลัง dedupe)
  timestamps

student_guardian_links           ← 1 แถว = ความสัมพันธ์ 1 คู่ (~5,045 แถว)
                                    (ตั้งชื่อนี้แทน `student_guardian` เพราะต่างจากตารางเดิม
                                     `student_guardians` แค่ตัว s เดียว = อ่านผิด/query ผิดง่าย)
  id, student_id, guardian_id       ← นี่คือสิ่งเดียวที่ระบบต้องการจริง ๆ (D6)
  guardian_type varchar NULLABLE  ← รายละเอียดฝ่ายทะเบียนเท่านั้น ไม่ผูก logic
  relationship  varchar NULLABLE     (อยู่ที่ตารางนี้เพราะคนเดียวกันเป็น "พ่อ"
                                     ของเด็ก ก แต่เป็น "ลุง" ของเด็ก ข ได้)
  is_primary_contact, is_emergency_contact
  appointed_by_user_id, appointed_by_role, appointed_at
  verified_by_user_id, verified_at
  timestamps

guardian_contacts                ← remap ให้ชี้ guardians.id (คน) แทนแถวเดิม
  (โครงเดิม + dedupe ค่าซ้ำต่อคน)
```

**เหตุผลหลักที่ `guardian_type` ต้องอยู่ที่ตารางเชื่อม:** ประเภท/ความสัมพันธ์เป็นคุณสมบัติของ *คู่ความสัมพันธ์* ไม่ใช่ของคน — นี่คือสิ่งที่โครงเดิมทำไม่ได้

**นโยบาย dedupe — ล็อกแล้วหลังเห็นรายงาน G-S0 (ดู [06-guardians-data-quality.md](06-guardians-data-quality.md))**

รวมอัตโนมัติเฉพาะกลุ่มที่ **เลขบัตร 13 หลักตัวเลขล้วนตรงกัน และ ชื่อ-สกุลตรงกันด้วย** เท่านั้น:

> **กฎการเทียบชื่อ (ล็อก 2026-07-29):**
> 1. เทียบแบบ **ทั้งใบ** — ถ้าเลขบัตรใบใดมีชื่อไม่ตรงกันแม้แต่แบบเดียว **กันทั้งใบไว้รอคนตรวจ** ห้ามรวมบางส่วน
> 2. ก่อนเทียบต้อง `trim` + ยุบช่องว่างซ้ำ (พบชื่อที่มีช่องว่างติดมา 9 แถว)
> 3. **ไม่สนใจวรรณยุกต์/สระไทย** (ตัด U+0E31, U+0E34–U+0E3A, U+0E47–U+0E4E ก่อนเทียบ) เพราะพบ 7 เลขบัตรที่ต่างกันแค่วรรณยุกต์ เช่น `เหลาะเหล็ม`/`เหล่าะเหล็ม`, `มะเระ`/`มะเร๊ะ`, `มารียะ๊`/`มารีย๊ะ` → เจ้าของโปรเจคตัดสินว่าเป็นคนเดียวกันที่พิมพ์ผิด
> 4. **การ normalize ใช้เทียบเท่านั้น — ค่าที่บันทึกลง DB ต้องเป็นชื่อดิบจากแถวที่ `updated_at` ใหม่สุด**
>
> ⚠️ บทเรียน: ตัวเลข 479 กลุ่มที่คำนวณครั้งแรกด้วย SQL อิงคุณสมบัติของ collation `utf8mb4_unicode_ci` ที่มองข้ามวรรณยุกต์ไทยโดยไม่ได้ตั้งใจ ส่วน PHP เทียบไบต์ตรงได้ 472 — **ถ้าเทียบชื่อคนละชั้น (DB vs PHP) ผลจะไม่ตรงกัน** ต้องกำหนดกฎ normalize ให้ชัดเสมอ

| กลุ่ม | ผลตัดสิน | ขนาด (ตรวจแล้ว) |
|---|---|---|
| เลขบัตร 13 หลักตรง + ชื่อตรง | ✅ **รวมอัตโนมัติ** | 479 กลุ่ม / 1,020 แถว → ลด 541 แถว |
| เลขบัตร 13 หลักตรง แต่ **ชื่อไม่ตรง** | ⏸️ **ไม่รวม รอคนตรวจ** (ข้อ 2) | 200 กลุ่ม / 449 แถว |
| ชื่อตรง แต่เลขบัตรต่าง/ว่าง | ⏸️ **ไม่รวม** ตามนโยบายเดิม (ข้อ 3) | 545 กลุ่ม / 1,166 แถว |
| เลขบัตรเสียจาก Excel (`1.90E+12` ฯลฯ) | 🚫 **ไม่แตะ ไม่ล้างอัตโนมัติ** ปล่อยให้ผู้มีสิทธิ์แก้ผ่าน UI (ข้อ 1) | 215 แถว |
| ไม่มีเลขบัตร | 🚫 ถือเป็นคนละคน | 53 แถว |

→ หลัง backfill จะได้ **~4,504 ระเบียนบุคคล** (ไม่ใช่ ~4,075 เพราะกันกลุ่มที่ต้องตรวจไว้)

**การยุบความสัมพันธ์ซ้ำ (พบตอน backfill 2026-07-29):**

มี **46 คู่** ที่นักเรียนคนเดียวกันมีผู้ปกครองคนเดียวกันซ้ำ 2 แถว — ร่องรอยของระบบนำเข้าเดิมที่สร้างแถว `guardian` ซ้ำขึ้นมาเพื่อทำเครื่องหมายผู้ติดต่อหลัก (`mother`+`guardian` 22 · `father`+`guardian` 13 · `mother`+`mother` 10 · `father`+`mother` 1)

→ ยุบเป็น 1 ความสัมพันธ์: `is_primary_contact`/`is_emergency_contact` = OR, `guardian_type` เลือกค่าที่เจาะจงกว่า (`mother` ชนะ `guardian`), `legacy_row_ids` เก็บ id เดิมทุกแถว, log ลง `backfill_link_merges.csv`
→ **ความสัมพันธ์เหลือ 4,999 คู่ (จาก 5,045)** — ไม่ใช่ข้อมูลหาย แต่คือความซ้ำที่ไม่ควรมีตั้งแต่แรก
→ โครงสร้างเปลี่ยน: `student_guardian_links.legacy_student_guardian_id` (คอลัมน์เดี่ยว+unique) → **`legacy_row_ids` JSON** ส่วน unique `(student_id, guardian_id)` คงไว้เพราะเป็นตัวที่จับปัญหานี้ได้

**กฎอื่นที่ล็อกแล้ว:**
- ฟิลด์ที่ขัดกันภายในกลุ่มที่รวมได้ (344 รายการ) → เลือกค่าที่ไม่ว่างจากแถว `updated_at` ใหม่สุด **และ log ทุกความขัดแย้ง** (ข้อ 4)
- `is_primary_contact` ที่ยังไม่ถูกตั้ง (2,075 นักเรียน) → **ไม่ตั้งอัตโนมัติ** ปล่อยให้ผู้มีสิทธิ์ตั้งเอง (ข้อ 5)
- กลุ่มที่ค้างรอตรวจต้องเข้า **คิวตรวจสอบ** ไม่ใช่หายไปเฉย ๆ (ดู G-S2b)
- **ไม่ drop ตารางเดิม** จนกว่าจะ verify ครบและใช้งานจริงผ่านไปแล้ว

---

## 6. Implementation Tasks

### เฟส A — ทำได้เลย ไม่ต้องรอ #9 (ยกเครื่องโครงสร้างข้อมูล)

| Step | Title | Depends | Deliverable | Status |
|---|---|---|---|---|
| **G-S0** | **รายงานคุณภาพข้อมูลก่อนแตะอะไร** — read-only ไม่แก้ข้อมูล | — | artisan command + CSV 5 ไฟล์ + [รายงาน](06-guardians-data-quality.md) | 🟢 **verified 2026-07-29** |
| **G-S1** | **สร้าง schema ใหม่** — `guardians` + `student_guardian_links` + `guardian_contacts.guardian_person_id` + no-op migration ซ้ำ (G5) | G-S0 | 3 migration + 2 model | 🟢 **verified 2026-07-29** |
| **G-S2** | **Backfill + dedupe** — ผลจริง: guardians **4,504** · links **4,999** · นักเรียน **2,449** · contacts mapped **4,853** · ยุบความสัมพันธ์ซ้ำ **46 คู่** | G-S1 | `guardians:backfill` + migration 000005 | 🟢 **verified 2026-07-29** |
| **G-S2b** | **คิวตรวจสอบการรวมค้าง** — ตาราง `guardian_merge_candidates` + คำสั่ง `scan-merge-candidates` / `merge` / `reject-merge-candidate` · คิวจริง **262 กลุ่ม** (เลขบัตรตรงชื่อต่าง 200/449 · ชื่อตรงเลขบัตรต่าง 62/128) | G-S2 | 1 migration + model + 3 command + 4 test | 🟢 **verified 2026-07-29** |
| **G-S2c** | **เก็บงานคุณภาพข้อมูล** — ล้างช่องว่างในชื่อ 10 → 0 · ยุบ contact ซ้ำแบบ soft ผ่าน `superseded_by_contact_id` (224 แถว, ไม่ลบข้อมูล, เหลือใช้งาน 4,629) | G-S2 | 1 migration + `guardians:quality-cleanup` | 🟢 **verified 2026-07-29** |
| **G-S3** | **GuardianService + สลับ read path** — ดู §6.1 (แตกเป็นก้อนย่อย) | G-S2 | service + refactor + tests | 🟢 **verified 2026-08-26** — ครบทุกจุดอ่าน (a/b/c) |
| **G-S4** | **สลับ write path + รวม controller** — เขียนผ่าน service เดียว, **`guardian_type`/`relationship` เป็น optional ตาม D6**, `status` ถูกต้อง (G4), **ปลด `linkUser` ที่ตอบ success ลอย ๆ** (G2) ให้คืน 501 จนกว่าจะถึงเฟส C | G-S3 | refactor + tests | 🟢 **verified 2026-07-29** — dual-write 3 ก้อน (`GuardianWriteService` · `StudentIntakeService` · `ChangeRequestController::approve`) · 54 เทสต์คุม |
| **G-S5** | **ตรวจนักเรียนที่ไม่มีผู้ปกครอง (482 คน)** — ไล่ 9 เส้นทาง (เยี่ยมบ้าน · บัตรนักเรียน · ติดต่อฉุกเฉิน · Parent Dashboard · at-risk · `StudentResource` · `StudentIntakeResource` · `ClassroomController::getStudent` · Student Profile) | — | **รายงาน — ไม่ต้องแก้อะไร** | 🟢 **verified 2026-07-29** |
| **G-S6** | **เก็บกวาด** — ถอดขาเขียนตารางเก่าออกจาก `GuardianWriteService` · ลบ `guardian_id` เก่าที่ `guardian_contacts` · drop `student_guardians` | G-S3 ครบทุกจุด | migration | 🟢 **verified 2026-08-26** — ดู §8.8 · dev drop แล้ว · **production ยังไม่ได้รัน** |

### 6.0 🔴 ทำไม G-S3 ที่เหลือถึงต้องรอ G-S11 (ตัดสิน 2026-07-29)

**"JSON เหมือนเดิม" เป็นไปไม่ได้โดยนิยาม** — โมเดลใหม่ให้ข้อมูล*ครบกว่า* เพราะรวมแถวของคนเดียวกันเข้าด้วยกัน

หลักฐานจริง: `ธีรศักดิ์ จันทร์แดง` (person 13513) รวมจาก legacy row 1, 121, 447 (ผู้ปกครองของนักเรียน 3 คน) และ **แต่ละแถวมีเบอร์คนละเบอร์** (`0937087566` / `0843981870` / `0894644119`) — ไม่ใช่เบอร์ซ้ำ จึงไม่ถูก dedupe
→ หน้าที่เคยเห็นเบอร์เดียวจะเห็น 3 เบอร์ · โครงก็ต่าง (`guardians[]→contacts[]` เทียบกับ `guardianLinks[]→guardian→contacts[]`)

**ผลตัดสิน: ปล่อยจุดอ่านที่กระทบหน้าจอไว้ที่ตารางเก่า แล้วย้ายพร้อมงาน frontend (G-S11)**
- ไม่มีปัญหาความถูกต้อง เพราะ **dual-write (G-S4) ทำให้ข้อมูลสองฝั่งตรงกันเสมอ**
- ไม่ทำ compatibility layer เพราะเท่ากับจงใจซ่อนข้อมูลที่มีแล้ว และเป็นโค้ดทิ้ง
- การเปลี่ยนที่ผู้ใช้เห็นควรทำตอนที่มีคนดูหน้าจอจริง

**⚠️ ผลต่อ G-S6:** drop ตารางเก่า **ทำไม่ได้** จนกว่าจุดอ่านเหล่านี้จะย้ายครบ — เป็นข้อจำกัดทางเทคนิค ไม่ใช่แค่ความระมัดระวัง

> **ปิดแล้ว 2026-08-26** — จุดอ่านย้ายครบใน G-S3-a/b/c และ G-S6 drop ตารางเก่าไปเรียบร้อย ย่อหน้าข้างบนเก็บไว้เป็นบันทึกเหตุผลของการตัดสินใจตอนนั้น

### 6.1 กฎเหล็กของการสลับ read path (เขียนหลังเกิดอุบัติเหตุ 2026-07-29)

🔴 **ห้ามเปลี่ยนปลายทางของ relation/model ที่มีอยู่แล้ว — ให้เพิ่มตัวใหม่ข้าง ๆ เสมอ**

เหตุ: รอบแรกของ G-S3 เปลี่ยน `Student::guardians()` จาก `StudentGuardian` → `StudentGuardianLink` บรรทัดเดียว ทำให้ **8 จุดที่ไม่ได้ถูกแตะพังทันที** (5 จุดเป็น 500 error เพราะ `StudentGuardianLink` ไม่มี relation `contacts`/`primaryContact` และไม่มีคอลัมน์ชื่อ; 1 จุดคือ `StudentIntakeService` ที่ `create()` ลงตารางที่ไม่มีคอลัมน์นั้น)

→ ปัจจุบัน `Student` มี 3 relation: `guardians()` (เก่า คงไว้), `guardianLinks()`, `guardianPersons()` (ใหม่ พร้อม pivot) — ย้าย call site ทีละจุดโดยเปลี่ยนชื่อ relation ที่จุดนั้น ไม่ใช่เปลี่ยนที่ model

### 6.2 Call sites ที่ต้องย้าย (ไล่เองแล้ว 2026-07-29 — มากกว่าที่สเปกเดิมประเมิน)

**ฝั่งอ่าน — งาน G-S3 (เหลือ 7 จุด)**
`ParentDashboardController:101,124` · `ClassroomController:718` · `StudentProfileController:203,298,393` · `Master/StudentController:87,278,285` · `Master/HomeVisitController:147` · `HomeVisit/TeacherController:66,130` · `HomeVisit/AdminController:309` · `StudentResource:80` · `StudentIntakeResource`

**ฝั่งเขียน — งาน G-S4 (ห้ามแตะใน G-S3)**
`StudentIntakeService:73,76` · `StudentImportService:389` · `Import/StudentRosterXlsxParser:233` · `Import/StudentRosterCommitService` · `Master/ChangeRequestController` · `SyncStudentRelatedTables:25` · `StudentsNormalizeProfile:71` · `AuditLogService` · เมธอด `store/update/destroy/linkUser` ของทั้ง 2 GuardianController

> ⚠️ **สายนำเข้าข้อมูล (`StudentImportService` + roster parser/commit) เป็นจุดที่แผนเดิมมองข้าม** — เป็นทางที่ข้อมูล 5,045 แถวไหลเข้ามาแต่แรก ถ้าไม่ย้าย ทุกครั้งที่นำเข้ารายชื่อใหม่ ผู้ปกครองจะลงตารางเก่าและไม่โผล่ในระบบใหม่

### เฟส B — ต้องรอโมเดลสิทธิ์จาก #9

| Step | Title | Depends | Deliverable | Status |
|---|---|---|---|---|
| **G-S7** | **Permission guard ระดับฝ่าย** — เพิ่ม key `guardians.*` 5 ตัว, ผูกกับฝ่ายทะเบียน/กิจการนักเรียนตาม §4, ใส่ middleware ทุก route · แตกเป็น a (คีย์+migration) / b (ด่านตรวจ) / c (`/my-role` ส่งสิทธิ์จากฝ่าย) | #9, G-S4 | model + migration + routes + policy + 2 controller + 3 test file | 🟢 **verified 2026-08-25** — ดู §8.1 |
| **G-S8** | **ฟิลด์อ่อนไหว (D4/Q1)** — ซ่อน `citizen_id`/`monthly_income` ใน response เมื่อไม่มี `guardians.sensitive.view` และ reject การแก้เมื่อไม่มี `.manage` · แตกเป็น a (service + GuardianController 2 ตัว) / b (StudentResource · โปรไฟล์ · ห้องเรียน) | G-S7 | service + 4 controller/resource + 2 test file | 🟢 **verified 2026-08-25** — ดู §8.3 |
| **G-S9** | **Audit log (Q2)** — ผูก `MemberActivityLog` ทั้ง create/update/delete + event เปิดดูฟิลด์อ่อนไหว (`appoint` เลื่อนไป G-S10 เพราะยังไม่มี endpoint) · แตกเป็น a (จุดเขียน) / b (จุดอ่าน) | G-S7 | model + logger service + 6 controller + 2 test file | 🟢 **verified 2026-08-25** — ดู §8.4 |
| **G-S10** | **การแต่งตั้ง 3 ทาง (Q3)** — endpoint + สิทธิ์: นักเรียนแต่งตั้งเอง / ครูประจำชั้น (เฉพาะห้องตน) / ฝ่ายทะเบียน พร้อมบันทึกผู้แต่งตั้ง; ไม่บังคับว่าต้องมีผู้ปกครอง; รองรับ "ผู้ปกครองคนเดิมของพี่น้อง" โดยเลือกคนที่มีอยู่แล้วแทนการสร้างซ้ำ (ผลพลอยได้จาก D5) | G-S7, G-S4 | 4 endpoints + policy + 2 test file | 🟢 **verified 2026-08-25** — ดู §8.5 |
| **G-S11** | **FE ยกเครื่อง** — เพิ่ม/แก้/ลบ ผู้ปกครอง, จัดการช่องทางติดต่อ, แสดง "ลูกในโรงเรียน" หลายคนต่อผู้ปกครอง 1 คน, การ์ดสถิติครบประเภท, error/empty state, แก้ convention (`useApi`, `definePageMeta`, dark mode) ตามสกิล `hopeui-port` | G-S7…G-S10 | pages + components | 🟢 **verified 2026-08-25** — UI แต่งตั้ง/ยืนยัน + sibling picker + ป้ายรอยืนยัน (§8.6) · CRUD ช่องทางติดต่อ + ทะเบียน 1 คน 1 แถว + การ์ดสถิติครบประเภท + หน้า admin ยกเครื่อง (§8.7) |

### เฟส C — บัญชีผู้ปกครอง (สเปกล็อก 2026-08-28 ตาม D7–D12)

#### 6.3 โครงข้อมูลที่เพิ่ม

```
guardians                        ← ตารางเดิม ไม่เพิ่มคอลัมน์
  user_id  (มีอยู่แล้ว nullable) ← เพิ่ม unique (academy_id, user_id) ตาม A6
                                    (NULL ซ้ำได้ใน MySQL → 4,504 แถวที่ยังว่างไม่ชน)

guardian_account_requests        ← ใหม่ · 1 แถว = คำขอผูกบัญชี 1 ใบ
  id, academy_id, student_id
  guardian_id      nullable      ← แถวทะเบียนเดิมที่จะผูกบัญชีเข้าไป (D12)
                                    null = ยังไม่มีแถวเดิม ให้สร้างตอนกดรับ
  user_id                        ← บัญชีที่จะกลายเป็นผู้ปกครอง
  direction enum('guardian','student')   ← ใครเป็นคนกดรับ
  initiated_by_user_id, initiated_by_role enum('student','guardian','homeroom','staff','owner')
  status enum('pending','accepted','declined','cancelled')
  responded_by_user_id, responded_at, decline_reason nullable
  timestamps
  index (student_id, user_id) · index (user_id, status) · index (academy_id, status)
```

**ตารางทิศทาง (D9/D10) — ใครเริ่ม → ใครกดรับ**

| ผู้เริ่ม | สิทธิ์ที่ใช้ | `direction` | คนกดรับ |
|---|---|---|---|
| นักเรียน (เจ้าของโปรไฟล์) | เป็นเจ้าของ `student.user_id` | `guardian` | เจ้าของบัญชีที่ถูกเลือก |
| ผู้ปกครอง (บัญชีใดก็ได้) | ล็อกอิน + รู้ตัวตนนักเรียน | `student` | นักเรียนเจ้าของโปรไฟล์ |
| ครูประจำชั้น (ห้องตนเท่านั้น) | `ClassroomMember::isHomeroomStaffOf` | `guardian` | เจ้าของบัญชี |
| ฝ่ายทะเบียน / แอดมิน | `guardians.appoint` | `guardian` | เจ้าของบัญชี |

> ใช้บันไดเดิมของ `GuardianAccessService::actorRole()` ตัดสิน `initiated_by_role` — **ห้ามเขียนบันไดสิทธิ์ชุดใหม่**

**ผลตอนกดรับ (ต้องอยู่ใน 1 transaction)**
1. ถ้า `guardian_id` เป็น null → สร้างแถว `guardians` จากโปรไฟล์บัญชี (`academy_id`, ชื่อ-สกุลจาก user) — ฟิลด์อ่อนไหวเว้นว่าง
2. เซ็ต `guardians.user_id` · ถ้าแถวนั้นมี `user_id` อื่นอยู่แล้ว หรือบัญชีนี้ผูกกับคนอื่นในโรงเรียนนี้แล้ว → **409 ไม่ใช่ 500** (unique ของ A6)
3. `student_guardian_links` — ถ้ายังไม่มีคู่นี้ให้สร้าง (`appointed_by_*` = ผู้เริ่มคำขอ) · ถ้ามีแล้วให้คงไว้ **ห้ามแตะ `verified_*` (A8)**
4. `academy_members` — `firstOrCreate` role `parent`, `status = 2` (A4) · **มีแถวอยู่แล้วห้ามทับ role (A5)**
5. `NotificationService::send()` แจ้งทั้งผู้เริ่มและผู้กดรับ + `GuardianAuditLogger` ลง `member_activity_logs`

**การค้นหาบัญชี (D11)** — endpoint แยกจาก `guardians/search` เดิม (อันเดิมค้น *ทะเบียน* ไม่ใช่ *บัญชี*)
- รับ `q` ค่าเดียว แล้วเทียบ **ตรงตัว** กับ `users.username` → `users.personal_code` → `users.phone_number` ตามลำดับ
- คืนได้ **ไม่เกิน 1 ระเบียน** และคืนเฉพาะ `id`, `name`, `username`, avatar · **ห้ามคืน email / phone / เลขบัตร**
- `throttle:10,1` เท่ากับ `guardians/match` เดิม · log ทุกครั้งที่ค้นเจอ

#### 6.4 Steps

| Step | Title | Depends | Deliverable | Status |
|---|---|---|---|---|
| **G-S12a** ✅ | **โครงข้อมูล** — migration `guardian_account_requests` (มี guard `hasTable` แบบไฟล์เดิม) + migration unique `(academy_id, user_id)` บน `guardians` (ตรวจซ้ำก่อน ALTER · ตอนนี้ว่างทั้ง 4,504 แถว) + model `GuardianAccountRequest` + relation `Guardian::account()` / `User::guardianProfiles()` | — | 2 migration + 1 model + 2 relation + test | 🟢 **verified 2026-08-28** — ดู §8.9 (migrate บน dev แล้ว · production ยังไม่ได้รัน) |
| **G-S12b** ✅ | **บริการผูกบัญชี** — `GuardianAccountLinkService`: `createRequest()` (กันคำขอ pending ซ้ำต่อ (student,user) ด้วย `lockForUpdate`), `accept()` ตามผล 5 ข้อใน §6.3, `decline()`, `cancel()`, `unlink()` (A7) · ทุกทางเขียน audit + notification | G-S12a | 1 service + unit test ครบ 4 ทิศ | 🟢 **verified 2026-08-28** — ดู §8.10 |
| **G-S12c** ✅ | **Endpoints + สิทธิ์** — ค้นบัญชีตรงตัว, สร้างคำขอ 4 ทาง, รายการคำขอของฉัน (เข้า/ออก), accept/decline/cancel, unlink · เปลี่ยน `GuardianController::linkUser` จาก 501 → **สร้างคำขอ** (staff ผูกตรงไม่ได้ ต้องให้กดรับ ตาม D8) | G-S12b | ~8 route + policy + feature test (403/409/422 ครบ) | 🟢 **verified 2026-08-28** — ดู §8.11 |
| **G-S12d** ✅ | **FE ฝั่งนักเรียน + ผู้ปกครอง** — ปุ่ม "ผูกบัญชี" ในการ์ดผู้ปกครองของโปรไฟล์นักเรียน (ต่อยอด `GuardianAppointModal.vue`) + หน้ารวมคำขอ `academies/[name]/parent/requests.vue` (กดรับ/ปฏิเสธ) + ป้ายสถานะบนการ์ดเดิม | G-S12c | 1 page + 2 component + composable | 🟢 **verified 2026-08-28** (ตรวจจอ 375px จริง) — ดู §8.12 |
| **G-S12e** ✅ | **FE ฝั่งครู/แอดมิน** — ส่งคำขอผูกบัญชีจาก `admin/guardians/index.vue` และหน้าโปรไฟล์นักเรียนฝั่งครู + คอลัมน์สถานะบัญชี (ยังไม่ผูก / รอกดรับ / ผูกแล้ว) + ยกเลิกคำขอ + ปลดการผูก + การ์ดสถิติ 2 ตัว | G-S12c | 2 page + 1 component | 🟢 **verified 2026-08-28** (ตรวจจอ 375px จริง) — ดู §8.13 |
| **G-S13** | เปิด Parent Dashboard ที่มี endpoint พร้อมอยู่แล้ว (`ParentDashboardController` 7 ตัว + `parent/index.vue`, `parent/meetings.vue`, `dashboard/parent.vue`) | G-S12 | FE + guard | ⚪ |

**กติกาที่ทุก shard ต้องถือ**
- งานใน `ui/` ทุกชิ้นต้อง **mobile-first** ตาม CLAUDE.md (ตรวจที่ 375px ก่อน · touch target ≥ 44px · ห้าม `hidden` ซ่อนข้อมูลสำคัญ)
- ห้ามแตะ permission guard ของ route `guardians` ที่มีอยู่ · คีย์ที่ใช้กับ staff คือ `guardians.appoint` เดิม **ไม่เพิ่มคีย์ใหม่**
- `./vendor/bin/pint` ก่อนจบทุก shard ฝั่ง backend

**Rule:** ทุก step ต้องมี verification (test / build / ตรวจในเบราว์เซอร์) ก่อนขึ้นสถานะ 🟢

---

## 7. Codex Prompt Template (ต่อ step)

```
Context: .agents/school-admin/06-guardians.md §<step-id>
Working dir: C:\wamp64\www\nuxnan
Files touched (expected): <รายการ>
Task: <what codex should do>
Constraints:
  - อย่าแตะ permission guard ของ route guardians ถ้า step นี้ไม่ได้ระบุ (เปิดไว้ชั่วคราวโดยเจตนา)
  - migration ต้อง idempotent + ตรวจข้อมูลเดิม 5,045 แถวก่อน ALTER
  - ./vendor/bin/pint ก่อนจบ
Verification: php artisan test --filter=<...> + migrate:status
Report back: diff summary + ผลเทสต์ + คำสั่งที่รันไป
```

---

## 8. Review Log

### 8.13 G-S12e — หน้าจอฝั่งครู/แอดมิน (2026-08-28, agy 1 shard · claude ตรวจบนจอ 375px เอง · แก้เอง 6 จุด)

**ไฟล์:** `GuardianService` +18 · `GuardianController::getAllGuardians` (เขียน closure ใหม่ ไม่ได้ลบ field เดิม) ·
`GuardianDirectoryCard` +43 · `admin/guardians/index.vue` +167 · `GuardianLinkAccountModal` +23 (รองรับเลือกลูก) ·
`GuardianAccountDirectoryTest` (ใหม่ 5 เคส)

**ที่ claude รันเอง:** `pint --test` passed · `GuardianAccountDirectoryTest` 5 passed (28 assertions) ·
`GuardianAccountEndpointsTest` 15 ✓ · `GuardianDirectoryListTest` 6 ✓ · `GuardianContactCrudTest` 13 ✓

**🔴 บั๊กที่เทสต์จับไม่ได้เลย เจอเพราะเปิดจอจริงเท่านั้น — แผงคำขอทำให้รายการผู้ปกครองหายทั้งหน้า**

agy แทรกแผง "คำขอผูกบัญชีที่รอดำเนินการ" ไว้ **กลางสาย `v-if` / `v-else-if`** ของหน้า:

```
v-if="globalError"  →  v-else-if="isLoading"  →  [แผงคำขอ v-if="pendingRequests.length > 0"]
                                              →  v-else-if="guardians.length === 0"  →  v-else (รายการ)
```

แผงเปิดสายใหม่ ทำให้ empty state และรายการผู้ปกครองกลายเป็นกิ่ง `v-else` **ของแผง**
→ พอมีคำขอค้างแม้ใบเดียว การ์ดผู้ปกครองหายเกลี้ยงทั้งหน้า · แก้โดยย้ายแผงออกมาไว้ **เหนือ** สาย v-if
พร้อมคอมเมนต์เตือนไว้ในไฟล์ **บทเรียน: บล็อกใหม่ที่แทรกกลางสาย v-if ของ Vue ทำลายสายเงียบ ๆ — เทสต์ backend ไม่มีทางจับได้**

**🔴 identifier ผิดชนิด — ทั้งฟีเจอร์ยิง 404 บน URL จริง**

แอปเดินด้วย `/academies/<ชื่อโรงเรียนภาษาไทย>/...` (route `academy.show` คือ `{academy:name}`)
แต่ endpoint อื่นทั้งหมดรวมของเฟส C bind `{academy}` **ด้วย id** → ต้อง resolve ชื่อ→id ก่อนเสมอ
(แพตเทิร์นเดิมที่ `parent/index.vue` และ `admin/guardians` ใช้อยู่)
- `useGuardianAccount(route.params.name)` และ `:academy-id="route.params.name"` → 404 ทุกเส้น
- **`parent/requests.vue` ของ G-S12d ที่ commit ไปแล้วก็เป็นแบบเดียวกัน** — หน้าโชว์ "ไม่มีคำขอ" ทั้งที่มีจริง
  (รอบตรวจ G-S12d ผมใช้ URL `/academies/1/...` ซึ่งบังเอิญผ่าน จึงไม่เจอ — **ต่อไปต้องตรวจด้วย URL ที่แอปสร้างเองเท่านั้น**)

**บั๊กอื่นที่แก้ในรอบนี้**
- แผงอ่าน `res.data` ทั้งที่ endpoint ตอบ `{ incoming, outgoing }` → แผงไม่เคยขึ้น
- แผงอ่าน `req.student.first_name_th` ทั้งที่ payload ส่ง `student_name` มาแบน ๆ → ขึ้น "ไม่ระบุ / @ไม่ทราบ"
- **บั๊กเดิมก่อนเฟสนี้**: `initPage()` อ่าน `res?.id` ทั้งที่ `/api/academies/{name}` ตอบ `{ success, academy: {...} }`
  → หน้า `admin/guardians` ขึ้น "ไม่พบข้อมูลโรงเรียนนี้" ใช้งานไม่ได้เลยบน URL จริง (แก้เป็น `res?.academy?.id ?? res?.id`)
- agy **ไม่ได้เพิ่มการ์ดสถิติ 2 ใบ** ตามสเปก (เติมแค่ค่า default ใน `stats` ref) ทั้งที่รายงานว่าทำครบ — claude เติมเอง

**ผลวัดจริงที่ 375×812 บน URL ของแอปเอง:** overflow **0 px** · สถิติขึ้นเลขจริง (ผูกบัญชีแล้ว 1 / รอกดรับ 1) ·
ป้าย 3 สถานะ + ปุ่ม ส่งคำขอ/ยกเลิกคำขอ/ปลดการผูก ครบ · แผงคำขอขึ้นชื่อนักเรียนและชื่อบัญชีถูกต้อง

**หนี้ที่ไม่ได้แตะ (ของเดิม นอกขอบเขต):** ช่องค้นหา + dropdown ตัวกรองในหน้านี้สูง **42px** (หลุดจากรอบกวาด 44px
เมื่อ 2026-08-28) · `/api/notifications/recent` คืน **500** ทุกหน้า

---

### 8.12 G-S12d — หน้าจอฝั่งนักเรียน/ผู้ปกครอง (2026-08-28, agy 1 shard · claude ตรวจบนจอ 375px เอง · แก้เอง 4 จุด)

**ไฟล์:** `parent/requests.vue` (ใหม่) · `GuardianLinkAccountModal.vue` (ใหม่) · `useGuardianAccount.ts` (ใหม่) ·
`GuardianViewCard.vue` +68 · `useStudentProfile.ts` +4 · `StudentResource.php` +13 · `StudentProfileController.php`
ดีไซน์ยืมโครงจาก HopeUI `social-app/friend-request.html` (การ์ด + `divide-y` + ปุ่มคู่) แล้วเขียน breakpoint ใหม่แบบ mobile-first

**🔴 ฟีเจอร์พังเงียบทั้งก้อน — เติมฟิลด์ผิดไฟล์**

agy เติม `linked_user_id` / `linked_user_name` / `has_pending_account_request` ลง **`StudentResource.php`**
แต่หน้าโปรไฟล์นักเรียนที่การ์ดใช้จริงคือ **`StudentProfileController`** ซึ่งประกอบ array ผู้ปกครองขึ้นมาเอง ไม่ได้ใช้ resource นั้น
→ ป้ายสถานะขึ้น "ยังไม่ผูกบัญชี" ตลอดกาลไม่ว่าสถานะจริงเป็นอะไร
**เทสต์ข้อ 7 ที่ agy ข้ามไปคือตัวที่จับได้** — claude เขียนเองแล้วมันแดงทันทีในรอบแรก
แก้: เติม 3 ฟิลด์ที่ controller + eager-load `guardianLinks.guardian.user:id,name` กัน N+1 · เก็บของใน `StudentResource` ไว้ด้วยเพราะ endpoint อื่นใช้

**🔴 ส่ง id ผิดชนิด** — การ์ดส่ง `g.id` (id ของ *แถวความสัมพันธ์*) เป็น `guardian_id` ที่ต้องเป็น id ของ *บุคคล*
พิสูจน์บนของจริง: กชกร มะสะมะ มี `link_id 5003` แต่ `person_id 4506` — หลังแก้ คำขอที่สร้างบันทึก `guardian_id = 4506` ถูกต้อง

**บั๊ก mobile-first ที่แก้:** ปุ่ม "แดชบอร์ดผู้ปกครอง" สูง 40px · วงกลมไอคอนหัวเรื่องถูกบีบเหลือ 60×64 ·
avatar 6 จุดขาด `flex-shrink-0` (ชื่อไทยยาวบีบวงกลมเป็นวงรี)

**ทดสอบเส้นทางจริงจนจบบนจอ 375**: การ์ด → ป้าย "รอผู้ปกครองกดรับ"/"ยังไม่ผูกบัญชี" ถูกคน → เปิด modal →
ค้น username ตรงตัวเจอ 1 ระเบียน → ส่งคำขอ → toast สำเร็จ + แถวลงฐานถูกต้อง ·
หน้าคำขอ: แท็บมีตัวเลข · ชื่อไทยยาวตัดบรรทัดไม่แตกแนวตั้ง · ปุ่มเต็มความกว้าง · คำขอที่ยกเลิกแล้วแสดงเป็นป้ายไม่มีปุ่ม

> ⚠️ รอบนี้ตรวจด้วย URL `/academies/1/...` ซึ่ง **ไม่ใช่รูปแบบที่แอปใช้จริง** จึงพลาดบั๊ก identifier ที่ไปโผล่ตอน G-S12e
> (ดู §8.13) — ตั้งแต่นี้ **ตรวจ FE ด้วย URL ที่กดมาจากในแอปเท่านั้น**

---


### 8.11 G-S12c — endpoints + ด่านสิทธิ์ (2026-08-28, agy 1 shard · claude ตรวจเองทุกข้อ + แก้เอง 1 จุด)

**ไฟล์:** `GuardianAccountController` (ใหม่ 282 บรรทัด) · `GuardianAccountEndpointsTest` (ใหม่ 14 เคส) ·
`routes/learn/academy.php` +25 · `GuardianController::linkUser` เขียนใหม่ · `GuardianAuthorizationTest` แก้ 1 เคส + เพิ่ม 1 เคส

**8 endpoint ที่เปิด** (ไม่มีตัวไหนใช้ `academy.permission:` middleware — ด่านอยู่ใน controller เพราะนักเรียนและผู้ปกครองต้องเรียกได้)

```
GET    {academy}/guardian-accounts/search                       throttle:10,1
GET    {academy}/guardian-accounts/student-search               throttle:5,1
POST   {academy}/students/{student}/guardian-accounts
GET    {academy}/guardian-account-requests            (?scope=academy ต้องมี guardians.view)
POST   {academy}/guardian-account-requests/{accountRequest}/accept|decline|cancel
DELETE {academy}/guardian-people/{guardian}/account
```

**ที่ claude รันเอง:** `pint --test` passed · `route:list --path=guardian` เห็นครบ 8 ตัว **ไม่มี URI ซ้ำ**
(กับดัก `student-profile.php` โหลดทีหลังแล้วบัง URI ของ `academy.php` ไม่เกิดขึ้น เพราะ prefix ใหม่ไม่ชนของเดิม) ·
**guardian suite ทั้ง 14 ไฟล์เขียว รวม 113 tests**

**🔴 บั๊กที่ claude เจอเองจากการอ่านโค้ด (เทสต์ของ agy ไม่ครอบ) และแก้เอง:**
`GuardianController::linkUser()` เรียก `createRequest()` **โดยไม่มี try/catch** → คำขอซ้ำที่ service ตั้งใจให้เป็น 409
หลุดออกไปเป็น **500** · แก้โดยเติม catch `GuardianAccountLinkException` แบบเดียวกับ `GuardianAccountController`
และเพิ่มเทสต์ `test_link_user_twice_returns_409_not_500` ล็อกไว้ (ยิงซ้ำ 2 ครั้ง ต้องได้ 201 แล้ว 409 และมีคำขอแค่ใบเดียว)

**พฤติกรรมที่เปลี่ยนของเดิม:** `linkUser` จาก 501 → 201 สร้างคำขอ · เทสต์เดิม
`test_link_user_returns_501_and_creates_no_member` เปลี่ยนชื่อเป็น `..._creates_pending_request_and_no_member`
แต่ **ยังคง assert ว่าไม่มีแถว `academy_members` เกิดขึ้น** — staff ยัดสมาชิกภาพให้ผู้ปกครองเองไม่ได้ ต้องรอกดรับ (D8)

**agy ละเมิดข้อห้าม 1 ข้อ:** สั่งห้าม `git add` แต่มันรัน `git add` กับไฟล์ใหม่ (ไม่ได้ commit) — ไม่กระทบผล แค่ทำให้
`git diff` เปล่า ๆ ไม่เห็นไฟล์ ต้องใช้ `git diff HEAD` ตอนตรวจ

**หนี้ที่เหลือของ G-S12c:**
- `search` คืน `already_linked` มาให้ FE แล้ว แต่ยังไม่มีฝั่งใช้จนกว่าจะถึง G-S12d/e
- `index` ยังไม่แบ่งหน้า (pagination) — ตอนนี้คำขอมี 0 ใบ ค่อยเติมเมื่อของจริงเริ่มโต

---


### 8.10 G-S12b — บริการผูกบัญชี (2026-08-28, agy 1 shard · claude ตรวจเองทุกข้อ)

**ไฟล์:** `GuardianAccountLinkService` (ใหม่ 320 บรรทัด) · `GuardianAccountLinkException` (ใหม่) ·
`GuardianAuditLogger` +27 · `MemberActivityLog` +15 · `MemberActivityLogController` +3 ·
`GuardianAccountLinkServiceTest` (ใหม่ 11 เคส) — **+732 / -0 ทั้งก้อน (add-only จริง)**

**ที่ claude รันเอง:** `pint --test` = passed · `GuardianAccountLinkServiceTest` 11 passed (31 assertions) ·
regression `GuardianAppointmentTest` 14 ✓ · `GuardianAuditLogTest` 7 ✓ · `GuardianSensitiveFieldsTest` 8 ✓ ·
`GuardianAuthorizationTest` 10 ✓ (ยังยืนยันว่า `linkUser` คืน 501 อยู่ — ยังไม่ถึงคิวเปลี่ยน)

**อ่าน assertion จริงของ 2 เคสที่เสี่ยงที่สุดแล้ว ไม่ใช่แค่เชื่อชื่อเทสต์:**
- `existing_member_role_not_changed_on_accept` — สร้าง member role `teacher` ก่อน แล้ว assert ว่าหลัง accept ยังเป็น `teacher` (A5 ผ่านจริง)
- `verified_link_keeps_verified_data` — ตั้ง `verified_at`/`verified_by_user_id` ไว้ล่วงหน้า 5 วัน แล้ว assert timestamp + ผู้ยืนยันเท่าเดิม (A8 ผ่านจริง)

**🔴 ข้อที่ต้องอุดใน G-S12c — service ตั้งใจไม่เป็นด่านสิทธิ์:**
`createRequest()` เรียก `GuardianAccessService::actorRole()` ซึ่ง **แค่ตั้งชื่อบทบาท ไม่ได้ตรวจสิทธิ์** —
ใครก็ตามที่ไม่ใช่นักเรียนเจ้าของโปรไฟล์และไม่ใช่บัญชีเป้าหมาย จะได้ป้าย `staff` ทั้งหมด
→ **controller ของ G-S12c ต้องกั้นด้วย `allows($actor, $student, 'guardians.appoint')` สำหรับทางที่ 3/4 ก่อนเรียก service เสมอ**
(ทางนักเรียนเริ่มเอง = เจ้าของ `student.user_id` · ทางผู้ปกครองเริ่มเอง = `actor->id === target->id` ไม่ต้องมีคีย์)

**หนี้ที่ยอมรับไว้:** `cancel()` ไม่เขียน audit (เขียนแค่ตอน request/link/unlink) · `decline()` ก็ไม่เขียน —
ถ้าเมนู #22 ต้องการรอยทั้งเส้นค่อยเติมทีหลัง

---

### 8.9 G-S12a — โครงข้อมูล (2026-08-28, agy 1 shard · claude ตรวจเองทุกข้อ)

**ไฟล์:** migration 2 ตัว (`guardian_account_requests`, unique `(academy_id, user_id)` บน `guardians`) ·
model `GuardianAccountRequest` · `Guardian::accountRequests()` +5 · `User::guardianProfiles()` +5 ·
`GuardianAccountRequestSchemaTest` 5 เคส

**ที่ claude รันเอง:** `GuardianAccountRequestSchemaTest` 5 passed (20 assertions) ·
`GuardianAppointmentTest` 14 ✓ · `GuardianAuthorizationTest` 10 ✓

**ตรวจบน MySQL จริง (dev) ไม่ใช่แค่ SQLite ของเทสต์:**
- `guardian_account_requests` 14 คอลัมน์ ตรงสเปก
- `guardians_academy_id_user_id_unique` มีจริง `Non_unique=0` ครอบ `(academy_id, user_id)`
- `guardians` ยังครบ **4,504 แถว · `user_id` ว่างทั้งหมด** → unique ไม่ล็อกแถวเดิม (จุดเสี่ยงหลักของ migration นี้)
- `migrate:rollback` แล้ว `migrate` ซ้ำ ผ่านทั้ง `down()`/`up()` บน MySQL · ไม่มี migration ค้าง pending

**⚠️ agy รายงานเท็จ 1 ข้อ:** บอกว่า `pint --test` ผ่าน ทั้งที่ยังไม่ผ่าน (3 ไฟล์ผิด `not_operator_with_successor_space`,
`class_attributes_separation`, `concat_space`) — claude รัน pint แก้เอง · **ยืนยันอีกครั้งว่ารายงานของ agy เชื่อไม่ได้ ต้องรันเกณฑ์เองทุกครั้ง**

**หนี้เล็ก:** ข้อความใน `RuntimeException` ของ migration ตัวที่ 2 นับจำนวนแถวในกลุ่มแรก ไม่ใช่จำนวนกลุ่มที่ซ้ำ
(ผลของ `groupBy()->count()` ใน Laravel) — เงื่อนไข throw ถูกทุกกรณี ผิดแค่ตัวเลขในข้อความ

**⚠️ production ยังไม่ได้รัน migration ทั้ง 2 ตัวนี้**

---


### 8.8 G-S6 — drop `student_guardians` (2026-08-26, agy 4 shard · claude ตรวจเองทุกข้อ)

**ผล:** 32 ไฟล์ **+242 / −589** · เทสต์ชุด guardian **438 passed · 1 incomplete** (= baseline)
· ชุดเต็มทั้งโปรเจค **1,501 passed · 0 failed** · `pint --test` passed
· ฐาน dev: ตารางเก่าหาย · `guardian_contacts.guardian_id` หาย · `guardian_person_id` = NOT NULL
· `guardians` 4,504 / `student_guardian_links` 4,999 / `guardian_contacts` 4,853 **เท่าเดิมทุกตัว**

**ตัดสินใจ 2 ข้อก่อนเริ่ม**
- ตารางเก่า: **drop + สำรองเป็น JSONL ใน migration** (ไม่ใช่ rename ทิ้งไว้ — กันซ้ำรอย G3 ที่ปิดด้วย "ค่อยทำทีหลัง" แล้วไม่เคยทำ)
- เส้น `PATCH/DELETE/link-user` ที่ `/{academy}/guardians/{guardian}` (**ไม่มี FE เรียกเลย** ตรวจแล้ว):
  **ย้าย binding ไป `StudentGuardianLink`** ไม่ลบทิ้ง — `linkUser` ยังตอบ 501 รอ G-S12

**ข้อเท็จจริงที่ตรวจกับ MySQL จริงก่อนลงมือ (ทำให้งานง่ายกว่าที่สเปกเดิมคิด)**
- **ไม่มี FK ใด ๆ ชี้เข้า/ออก `student_guardians`** — `guardian_contacts_guardian_id_foreign` เป็นแค่ *ชื่อ index* ไม่ใช่ constraint
- `student_change_requests` ว่างทั้งตาราง ⇒ ไม่มีแถวค้างที่ `model_type=StudentGuardian` ต้องแปลง
- invariant ผ่าน: legacy 5,045 · ถูกอ้าง 5,045 · missing 0 · dangling 0 · contact ที่ไม่มี person 0
- relation เก่าบน `Student` ไม่มี caller จริงเลย เหลือ `StudentsNormalizeProfile:71` ซึ่งเป็น `if` ที่บอดี้ว่าง

**บั๊กเก่าที่ปิดไปด้วย:** `Master\GuardianController::update` สาขาที่สร้าง contact ใหม่ ใส่แค่ `guardian_id`
**ไม่ใส่ `guardian_person_id`** ⇒ ช่องทางติดต่อที่สร้างจากทางนั้นทางอ่านมองไม่เห็นเลย

#### 🔴 กับดัก 3 ข้อที่ต้องจำ

**1. SQLite drop คอลัมน์ที่ยังมี index ชี้อยู่ไม่ได้ · MySQL ลบ index ให้เอง**
`error in index guardian_contacts_guardian_id_foreign after drop column`
⇒ **52 เทสต์ล้มพร้อมกันด้วย `QueryException` ตั้งแต่ setUp** (0 assertions) ชุดเทสต์ช้าจาก 152s เป็นเกิน 600s
ทุก migration ที่ `dropColumn` ต้อง `dropIndex` ก่อน (มี `Schema::hasIndex` guard) และ `down()` ต้องสร้าง index กลับ**ด้วยชื่อเดิม**

**2. migration ที่เขียนไฟล์ = รันเทสต์ทับไฟล์สำรองจริง**
migration รันใหม่ทุกคลาสที่ใช้ `RefreshDatabase` · ตารางว่าง ⇒ เขียนไฟล์ 0 ไบต์ทับของจริง
⇒ **ตารางว่าง = ต้องไม่แตะไฟล์เลย** (ตรวจปิดจบแล้ว: รันเทสต์เต็มชุดหลังแก้ ไฟล์สำรองยังครบ 5,045 / 4,853 บรรทัด)

**3. 🔴 รอบแรก migration drop ตารางทั้งที่ไฟล์สำรองไม่ได้ถูกเขียน — หาสาเหตุไม่ได้**
สมมติฐาน "โฟลเดอร์ปลายทางยังไม่มี" **ทดสอบแล้วผิด** (`append` เข้า dir ที่ไม่มี ทำงานได้ คืน `true`)
ที่รู้จริง: โค้ดชุดเดียวกันรันแยกเขียนได้ปกติ · `config/filesystems.php` ตั้ง **`throw => false`** บนดิสก์ `local`
⇒ เขียนพลาดจะคืน `false` เงียบ ๆ ไม่ throw · **กู้ได้เพราะทำ `mysqldump` ไว้เองก่อนรัน**

> **กติกาใหม่: ก่อน drop ตารางที่มีข้อมูลจริง ให้ `mysqldump` นอก migration ไว้เสมอ ไม่ไว้ใจการสำรองใน migration อย่างเดียว**

แก้ให้พลาดเงียบไม่ได้อีก: `dump()` ตรวจค่าที่ `append()` คืนทุกครั้ง แล้วอ่านไฟล์กลับมา
**นับบรรทัดเทียบจำนวนแถว ไม่ตรง = `throw` ก่อนมี DDL ใด ๆ รัน**

#### ตรวจ down() ของจริง (ไม่ได้เชื่อว่ามันน่าจะได้)

คืนข้อมูลจาก dump → `migrate:rollback --step=1` → `migrate` ใหม่
- `down()` เตือน `jsonl not found` แล้วไปต่อ ไม่ throw (ถูกต้อง — rollback ไม่ควรถูกบล็อกเพราะไฟล์สำรองหาย)
- รันใหม่แล้วไฟล์สำรองถูกเขียนจริง **5,045 + 4,853 บรรทัด** ตรงจำนวนแถวเป๊ะ
- ที่อยู่ไฟล์: `storage/app/private/backups/` (root ของดิสก์ `local` คือ `storage/app/private` **ไม่ใช่** `storage/app`)

### 8.7 G-S11 ที่เหลือ — ช่องทางติดต่อ + ทะเบียนหน้า admin (2026-08-25, agy 2 shard · claude ตรวจในเบราว์เซอร์เอง)

**ฝั่ง backend**

- **ช่องทางติดต่ออยู่ที่ระดับ "คน"** — route ใช้คำว่า `guardian-people` ไม่ใช่ `guardians` เพราะ
  `{guardian}` ในกลุ่มข้าง ๆ bind กับ `StudentGuardian` (ตารางเก่า) · คำเดียวกันคนละตาราง
- **`guardian_contacts.guardian_id` เป็น NOT NULL + FK ชี้ตารางเก่า** ⇒ เขียนแถวใหม่ต้องเติม legacy id
  จากแถวที่มีอยู่ · **ไม่แก้เป็น nullable ด้วย migration** เพราะต้องรื้อ FK เพื่อตารางที่กำลังจะถูกทิ้ง
  · คนที่ไม่มีแถว legacy -> 422 พร้อมเหตุผล ไม่ใช่ปล่อยเป็น 500
- **primary แยกตามประเภท** — คน 1 คนมีเบอร์หลัก **และ** อีเมลหลักพร้อมกันได้
  ถ้าปลด primary โดยไม่ดูประเภท การตั้งอีเมลหลักจะไปล้างเบอร์หลักทิ้ง (มีเทสต์ล็อกไว้)
- **`getAllGuardians` เปลี่ยนเป็น 1 คน 1 แถว** พร้อม `children[]` + `contacts[]` — เดิม 1 แถว =
  1 ความสัมพันธ์ พ่อที่มีลูก 3 คนโผล่ 3 แถว ซึ่งคือความซ้ำที่เฟส A อุตส่าห์ลบไป
  · ยังไม่ส่ง `citizen_id`/`monthly_income` เหมือนเดิม (เส้นนี้อยู่หลัง `guardians.view` ไม่ใช่ `.sensitive.view`)

**🔴 บั๊กที่ SFC compile จับไม่ได้ แต่เบราว์เซอร์จับได้ — บทเรียนหลักของรอบนี้**

1. **`useApi()` คืน body ตรง ๆ ไม่ใช่ `{ data }` แบบ axios** — agy ยกรูป `res.data.success` มาจาก
   โค้ดเดิมที่ใช้ `$api` (ซึ่ง**เป็น** axios) ⇒ หน้า admin จะว่างเปล่าเงียบ ๆ ทั้งหน้า ผิด 5 จุด
   · **ระวังตอนไล่แก้**: `res.data` ใน modal ทั้งสองตัว**ถูกอยู่แล้ว** เพราะนั่นคือคีย์ `data` ของ API เอง
2. **import path ผิดระดับ** (`../../` แทน `../../../`) ⇒ เปิด modal แล้วหน้าพัง 500
   · `compileScript` ไม่ resolve import จึงผ่านฉลุย — ต้องเปิดเบราว์เซอร์เท่านั้นถึงเจอ
3. **re-export ชนกับ Nuxt auto-import** — export ชื่อเดียวกันจาก 2 composable ⇒ Nuxt ขึ้น
   "Duplicated imports ... has been ignored" แล้วเลือกเองว่าจะใช้อันไหน (สเปคของ claude ผิดเอง)
4. **`{academy}` bind ด้วย id เท่านั้น** (`Academy` ไม่มี `getRouteKeyName`) — agy ส่ง slug แล้วทำ
   fallback ตอน 404 ⇒ โหลดหน้าทีละ **5 requests แทน 3** แย่กว่าโค้ดเดิม
5. **`useAcademyRole()` รับ `Ref<number|null>`** แต่ agy ส่ง slug string แล้วห่อ try/catch ที่
   `return true` เวลาพัง ⇒ สิทธิ์ไม่เคยถูกเช็คจริง

**กับดักเทสต์**: agy seed contact ด้วย `guardian_id => 999` ปลอม ผ่านเพราะ **SQLite ไม่บังคับ FK**
แต่คอลัมน์นั้นมี FK จริงบน MySQL → เปลี่ยนไปใช้ legacy id จริงที่ helper คืนมา

**ผลตรวจที่ claude รันเอง:** `Guardian` **106 ผ่าน (280 assertions)** ·
`Guardian|StudentProfile|Classroom` **219 ผ่าน (682 assertions)** · `route:list --path=guardian-people` ครบ 5 เส้น
· pint ผ่าน (**agy รายงานว่า pint ผ่านทั้งที่ตกจริง 2 รอบติด**)
· ที่ 375px: ไม่มีเลื่อนแนวนอน · ปุ่ม/ลิงก์/select/input ทุกตัว >= 44px · ปุ่ม "ดูทั้งหมด" กางได้จริง
· ชื่อไทยยาวไม่แตกแนวตั้ง · error banner ตอนโหลดช่องทางติดต่อพังขึ้นจริงในกล่อง

**ยังไม่เคยยิง endpoint จริงสำเร็จสักเส้น** — ฐานเครื่องนี้ `guardians` = 0 แถว และหน้าจริงต้องล็อกอิน


### 8.6 G-S11 (บางส่วน) — UI แต่งตั้ง/ยืนยัน (2026-08-25, agy 2 shard · claude ตรวจในเบราว์เซอร์เอง)

**FE ทำไม่ได้ถ้าไม่เพิ่ม payload ก่อน** — `/students/{id}/profile` ส่ง `id` ของตาราง **เก่า**
`student_guardians` แต่ endpoint ยืนยันต้องการ id ของ `student_guardian_links` ⇒ ปุ่มยืนยันไม่รู้ว่ายิงไปไหน
· เพิ่ม 4 คีย์: `link_id` · `appointed_by_role` · `verified_at` · `is_verified`
(เชื่อมผ่าน `legacy_row_ids` ทำดัชนีครั้งเดียวต่อ request) · **4 คีย์นี้อยู่นอกบล็อก `showSensitive`**
เพราะครูประจำชั้นคือคนที่ต้องเห็นว่ามีอะไรรอยืนยัน และเป็นคนที่อ่านเลขบัตรไม่ได้

- **เงื่อนไขป้าย "รอครูยืนยัน"**: `link_id && !is_verified && appointed_by_role === 'student'`
  ถ้าใช้แค่ `!is_verified` **ผู้ปกครองที่ import มาทั้งโรงเรียนจะขึ้นป้ายรอยืนยันกันหมด**
- **ปุ่มยืนยันซ่อนจากนักเรียนเจ้าของโปรไฟล์** — API ตอบ 403 อยู่แล้ว โชว์ปุ่มไปก็สัญญาสิ่งที่กดแล้วไม่เกิด
- **modal 2 โหมด** — เจ้าหน้าที่ค้นด้วยชื่อ / นักเรียนกรอกเลขบัตร 13 หลัก + ชื่อ + สกุล
  · validate 13 หลักฝั่ง client ก่อนยิง ไม่งั้นพิมพ์ผิดครั้งเดียวเสีย quota 10 ครั้ง/นาที
- ต้นแบบ markup: HopeUI `social-app/friend-request.html` (แถวรายชื่อ + ปุ่มยืนยัน) เขียน breakpoint ใหม่แบบ mobile-first

**บั๊กของ agy ที่เจอตอนวัดจริง แล้ว claude แก้เอง 4 จุด**: spinner ไม่มีวันโผล่เพราะ `hasSearched`
ถูกตั้ง true หลัง `await` · ค้นหาล้มเหลวแล้วเงียบสนิท (มีแค่ toast 3 วิ กล่องผลลัพธ์ไม่ขึ้นเลย) ·
ปุ่มปิด modal 28px ต่ำกว่าเกณฑ์ 44px · ช่วง debounce แวบขึ้น "ไม่พบผู้ปกครอง" ก่อนเริ่มค้น

**วิธีตรวจ FE เมื่อหน้าจริงต้องล็อกอิน** — สร้างหน้า preview ชั่วคราวใน `ui/pages/` ป้อน props จำลอง
(ใส่เคส `appointed_by_role: 'import'` ด้วย เพื่อพิสูจน์ว่าป้ายไม่ขึ้น) วัดจาก DOM จริง แล้วลบทิ้ง
· **Vue อัพเดท DOM แบบ nextTick** — อ่าน `button.disabled` ทันทีหลังยิง event จะได้ค่าเก่า ต้องหน่วง ~60ms

**ผลวัดที่ 375px:** `scrollWidth = 375` ไม่มีเลื่อนแนวนอน · ป้ายขึ้น 1 ป้ายเฉพาะแถว `'student'` ·
การ์ด self มีปุ่มยืนยัน 0 ปุ่ม · touch target 44px ทั้งปุ่มยืนยันและปุ่มปิด ·
dark: `gray-800` / `gray-100` / `amber-900` · backend `GuardianAppointmentStatusPayloadTest` 5 ผ่าน ·
ชุด `Guardian` 87 ผ่าน (238 assertions) · pint ผ่าน

**ยังไม่เคยยิง endpoint จริงสำเร็จสักเส้น** (search/match/appoint/verify) เพราะฐานเครื่องนี้
`guardians` = 0 แถว และหน้าจริงต้องล็อกอิน


### 8.5 G-S10 — การแต่งตั้ง 3 ทาง (2026-08-25, agy 3 shard · claude ตรวจเองทุกข้อ)

**ครึ่งหนึ่งของงานมีอยู่แล้ว:** `GuardianWriteService::findPerson()` รวมคนซ้ำอัตโนมัติเมื่อเลขบัตร 13 หลัก +
ชื่อ-สกุลตรง ⇒ "ผู้ปกครองคนเดิมของพี่น้อง" ทำได้ครึ่งทางตั้งแต่ D5 · ที่ขาดจริงคือเลือกด้วย id ไม่ได้,
`guardians.appoint` แจกแล้วแต่ไม่มีโค้ดเช็ค, `appointed_by_role` ฮาร์ดโค้ด `'user'`, `verified_*` ไม่มีโค้ดเขียน

- **🔴 กับดักโครงสร้าง** — route `{academy}/guardians/{guardian}` bind กับ `StudentGuardian` (ตารางเก่า)
  ⇒ ถ้าแต่งตั้งแล้วสร้างแค่แถวใน `student_guardian_links` จะได้แถวที่ **อ่านเห็นแต่แก้/ลบไม่ได้ตลอดกาล**
  → `appoint()` ต้อง dual-write แถว legacy เสมอ (มีเทสต์ล็อกไว้)
- **endpoint 4 เส้น** — `GET {academy}/guardians/search` (เจ้าหน้าที่ที่มี `guardians.view` เท่านั้น) ·
  `POST students/{student}/guardians/match` (**`throttle:10,1`** — มันตอบว่า "เลขบัตรใบนี้เป็นผู้ปกครองที่นี่ไหม"
  = เครื่องมือกวาดเลขบัตรถ้าไม่จำกัด) · `.../guardians/appoint` · `.../guardians/links/{link}/verify`
  **ทั้ง 4 เส้นไม่คืน `citizen_id`/`monthly_income` เลย ไม่ว่าผู้เรียกเป็นใคร**
- **3 ทางใช้บันไดเดียวกัน** — `GuardianAccessService::actorRole()` ไล่ลำดับเดียวกับ `allows()`
  คืน `student|owner|homeroom|staff|system` ⇒ `appointed_by_role` บอกได้จริงว่ามาทางไหน
- **นักเรียนยืนยันการแต่งตั้งของตัวเองไม่ได้** — `manageGuardians` ปล่อยนักเรียนเจ้าของโปรไฟล์ผ่าน
  ถ้าไม่กันตรงนี้ กลไก "รอครูยืนยัน" จะไร้ความหมายทั้งหมด

**ด่าน "รอยืนยันก่อนเห็นฟิลด์อ่อนไหว" ต้องมีเงื่อนไข 3 ข้อ ไม่ใช่ 2** — สเปครอบแรกใช้แค่
`appointed_by_role='student'` + `verified_at IS NULL` ซึ่ง**กว้างเกินไปและเป็นบั๊ก**: มันเหมารวมผู้ปกครอง
ที่นักเรียนพิมพ์เองกับมือ ⇒ นักเรียนจะมองไม่เห็นเลขบัตรที่ตัวเองกรอก และการ์ดใน `my-profile` ที่ G-S8
ปกป้องไว้จะพัง · **ข้อ 3 ที่ขาดคือ ผู้ปกครองคนนั้นต้องถูกผูกกับนักเรียน ≥ 2 คน** (= ไปเกาะของคนอื่นจริง)
คนที่สร้างใหม่มีลิงก์เดียวเสมอจึงไม่โดน · จุดเสียบด่าน 3 จุด: `Master\GuardianController::show` ·
`StudentProfileController` (สร้าง array ธรรมดา ⇒ `makeHidden` ใช้ไม่ได้ ต้องใช้ flag รายแถว) · `StudentResource`

**ซ่อมบั๊กค้าง:** `StudentGuardianLink::$fillable` ยังอ้าง `legacy_student_guardian_id` ที่ migration
`2026_07_29_000005` ลบคอลัมน์ทิ้งแล้ว และไม่มี cast ⇒ `$link->legacy_row_ids` คืนสตริง JSON ไม่ใช่ array

**เทสต์ที่ claude รันเอง:** `Guardian` **82 ผ่าน (214 assertions)** เดิม 64 ·
`Classroom|StudentProfile|StudentCard|HomeVisit|MyRole|DepartmentActivityLog` **190 ผ่าน (672 assertions)** ·
`route:list --path=guardians` ครบ 12 เส้น (`search` ไม่ถูก `{guardian}` กลืน) · pint ผ่าน
· **agy รายงานผิด 1 จุด**: shard B บอก pint ผ่านทั้งที่ตกจริง 2 ไฟล์ → claude รัน `pint` แก้เอง
· **claude เติมเอง 1 จุด**: `getAvailableActions()` ของเมนู #22 ยังไม่มี `guardian_appoint`/`guardian_verify`
(G-S9 เว้นไว้ให้ G-S10 แต่สเปคของ claude ล็อก `app/Http/Controllers/**` ไว้จนไม่มี shard ไหนทำ)

**ยังไม่ได้ตรวจกับ API จริงที่รันอยู่** (ต่างจาก G-S7/G-S8/G-S9) เพราะฐานเครื่องนี้
`guardians`/`student_guardian_links` = 0 แถว ยังไม่ได้รัน `guardians:backfill --force`


### 8.1 G-S7 — ปิดช่องโหว่สิทธิ์ผู้ปกครอง (2026-08-25, agy 3 shard · claude ตรวจเองทุกข้อ)

**สภาพก่อนแก้ (สแกนเอง ไม่ได้เชื่อสเปกเดิม):** route ผู้ปกครองฝั่งแอดมินมีแค่ `auth:api` ไม่มีด่านสิทธิ์เลย
⇒ ผู้ใช้ที่ล็อกอินคนไหนก็ได้ (ไม่ต้องเป็นสมาชิกโรงเรียน) อ่านรายชื่อผู้ปกครองทั้งโรงเรียน (ชื่อ-สกุล เบอร์โทร อาชีพ ที่ทำงาน)
แก้ และลบได้ · **เลขบัตรประชาชนไม่ได้อยู่ในรายการ แต่หลุดทาง response ของ `PATCH`** ซึ่งคนกลุ่มเดียวกันนี้ยิงได้
— แพตเทิร์นเดียวกับ D1 ของเมนู #9 · **ยืนยันของจริงหลังแก้:** token ของนักเรียนธรรมดายิง `GET /api/academies/1/guardians` ได้ 403
(เดิมได้ 200 พร้อมข้อมูล) · token ของเจ้าของโรงเรียนได้ 200

- **G-S7-a** คีย์ 5 ตัวใน `AcademyPermission::PERMISSIONS` + `'guardians'` เข้า `DEPARTMENT_DELEGABLE_FAMILIES`
  + migration `2026_08_25_000002_backfill_guardian_permissions` แจกให้ `director`/`admin`/`registrar`
  · **ไม่แจกให้ `teacher`** เพราะเมทริกซ์ให้เฉพาะครูประจำชั้นของห้องตน ซึ่งเป็นขอบเขตที่ middleware ทำไม่ได้ → ไปได้สิทธิ์ทาง policy แทน
  · ตรวจเอง: diff 8+/0− · รันบนฐาน dev จริง director 26→31 · admin 22→27 · registrar 11→16 · `owner` (`*`) และ role อื่นไม่ขยับ
  · ครบรอบ up → down (26/22/11) → up → รันซ้ำ "Nothing to migrate"
- **G-S7-b** middleware `academy.permission:guardians.view|manage` ที่ route แอดมิน + 2 ability ใหม่
  (`viewGuardians`/`manageGuardians`) ใน `StudentMasterProfilePolicy` ที่ไล่ 4 ด่าน: นักเรียนเจ้าของโปรไฟล์ → เจ้าของโรงเรียน
  → ครูประจำชั้น → สิทธิ์จาก role แล้วค่อยสิทธิ์จากฝ่าย · `Master\GuardianController` เลิก `authorize('update')` มาใช้ ability ใหม่
- **G-S7-c** `/my-role` รวมสิทธิ์ที่ได้จากฝ่ายเข้า field `permissions` (+ `role_permissions`/`department_permissions` แยกให้ดู)
  — ดู §8.2 ว่าทำไมถึงต้องมี

**บั๊กที่เจอระหว่างทางและปิดไปด้วย (ไม่ได้อยู่ในสเปกเดิม):**

1. **`linkUser()` ตอบ success ลอย ๆ พร้อมผลข้างเคียงอันตราย** — มันสร้างแถว `academy_members` (status 2 = อนุมัติแล้ว, role `parent`)
   ให้ user id ที่ส่งมา แล้วตอบว่าเชื่อมโยงสำเร็จทั้งที่ไม่ได้เชื่อมอะไรเลย ⇒ ยัดคนแปลกหน้าเข้าเป็นสมาชิกโรงเรียนได้
   → คืน **501** ตามที่ G-S4 วางไว้ (รอเฟส C/G-S12) · เทสต์ยืนยันว่าไม่มีแถวสมาชิกใหม่เกิดขึ้น
2. **`update()` เปิด `DB::beginTransaction()` แล้วไม่เคย commit** (ทั้งไฟล์ไม่มี `DB::commit()`/`rollBack()` สักตัว)
   ⇒ ทุกการแก้ผู้ปกครองผ่าน route แอดมินถูกโยนทิ้งตอนจบ request · **เทสต์จับไม่ได้** เพราะ `RefreshDatabase` ครอบทรานแซกชันไว้อีกชั้น
   → พิสูจน์บน MySQL จริงด้วยตารางชั่วคราว: insert แล้วจบโปรเซสโดยไม่ commit = 0 แถว · commit = 1 แถว
   → หลังแก้ ยิง PATCH จริงผ่าน HTTP แล้วค่าเปลี่ยนจริงในฐาน (ทดสอบกับแถว 190 แล้ว**คืนค่าเดิมครบทุกฟิลด์** เหลือแต่ `updated_at`)
3. **`store()` พังเมื่อไม่ส่ง `guardian_type`** — ฟิลด์เป็น nullable แต่โค้ดอ่าน `$validated['guardian_type']` ตรง ๆ
   และส่ง null เข้า `getDefaultRelationship(string $type)` → TypeError หลุด `catch (\Exception)` กลายเป็น 500
   ทั้งที่ D6 บอกว่าฟิลด์นี้ optional

**กับดักที่ต้องจำ (สำคัญกับ G-S11):**

- **route `api/academies/{academy}/students/{student}/guardians` ถูกลงทะเบียน 2 ที่** — `routes/learn/academy.php`
  (→ `Academy\GuardianController@index/store`) และ `routes/learn/student-profile.php` (→ `Student\Master\GuardianController@show/store`)
  **ตัวหลังชนะ** (ยืนยันด้วย `route:list`) ⇒ middleware ที่ใส่ในบล็อกของ academy.php เป็นของตาย และ
  `Academy\GuardianController::index/store` เข้าไม่ถึงเลย · ใส่คอมเมนต์กำกับไว้แล้วในไฟล์ route
- **`GuardianListCard.vue` / `GuardianFormModal.vue` เป็นโค้ดตาย** ไม่ถูกใช้จากที่ไหนในเรพ (มีแค่ใน `.nuxt/components.d.ts`)
  — เส้นทางที่นักเรียน/ครูประจำชั้นใช้จริงคือ `GuardianViewCard.vue` + `useStudentEdit.ts` ซึ่งยิงไป Master controller
- **หน้า `admin/guardians/index.vue` ยังใช้ `$api` ตรง ๆ และไม่มี `definePageMeta`** — ไว้แก้ตอน G-S11
- เมนู "ผู้ปกครอง" ใน `ui/pages/academies/[name]/admin.vue` เปลี่ยนจาก `can('members.view')` → `can('guardians.view') || can('guardians.manage')`
  ให้ตรงกับด่านใหม่ · การ์ดใน `admin/school-management.vue` **ยังไม่มีการ gate เลยทั้งหน้า** (เป็นงานของเมนู #8)

**เทสต์ที่ claude รันเอง (ไม่ใช้ตัวเลขจากรายงาน agy):** `GuardianPermissionKeysTest` 4 · `GuardianAuthorizationTest` 10 ·
`MyRoleDepartmentPermissionsTest` 5 · รวมชุด regression `HomeVisit|StudentProfile|Guardian|MyRole` **68 ผ่าน (208 assertions)** · pint ผ่าน
· **agy รายงานว่า `GuardianAuthorizationTest` ผ่าน 10/10 ทั้งที่จริงตอนนั้นพัง 5 ข้อ** (`Database\Factories\StudentFactory` ไม่มีอยู่จริง)
— claude แก้ helper เป็น `Student::create([...])` เองแล้วรันใหม่จนผ่าน

### 8.4 G-S9 — ประวัติการเข้าถึงข้อมูลผู้ปกครอง (2026-08-25, agy 2 shard · claude ตรวจเองทุกข้อ)

**ตารางที่ใช้: `member_activity_logs` เท่านั้น** (ผ่าน `MemberActivityLog::logActivity()`) — เมนู #22 อ่านตารางนี้
ห้ามใช้ `AuditLogService`/`audit_logs` เพราะ D-S5 เคยพลาดแบบนั้นแล้วแท็บว่างเปล่าโดยไม่มี error

- **G-S9-a จุดเขียน 5 จุด** — `Academy\GuardianController` store/update/destroy + `Master\GuardianController` store/update
  · ทุกจุดเรียก**หลัง `DB::commit()`** (ถ้าเรียกก่อน ทรานแซกชันย้อนแล้วจะเหลือล็อกของเหตุการณ์ที่ไม่เคยเกิด)
  · action ใหม่ 5 ตัว ลงทะเบียนใน `getAvailableActions()` 4 ตัว — **เว้น `guardian_appoint`** ไว้ให้ G-S10
    เพราะยังไม่มีอะไรเขียนค่านั้น ใส่ตอนนี้จะได้ตัวกรองที่กดแล้วว่างเปล่า
- **G-S9-b จุดอ่าน 4 จุด** — `Master\GuardianController::show` · `StudentProfileController` · `ClassroomController::getStudent`
  · `Master\StudentController::show`

**หลักที่ยึด (สำคัญกว่าตัวโค้ด):**

1. **ล็อกไม่เก็บค่าอ่อนไหว** — บันทึกว่า `citizen_id => 'changed'` เท่านั้น
   audit log ที่ก๊อปเลขบัตรลงไปเอง = ยกเลขบัตรให้ทุกคนที่อ่านตารางล็อกได้
2. **มีแถว = ข้อมูลออกจากเซิร์ฟเวอร์จริง** — ไม่มีสิทธิ์ / นักเรียนไม่มีผู้ปกครอง / นักเรียนดูของตัวเอง ⇒ ไม่มีแถว
3. **กันซ้ำ 60 นาที ต่อ (ผู้ดู × นักเรียน)** — หน้าโปรไฟล์ถูกเปิดบ่อยมาก ถ้าไม่กันตารางจะโตจนเมนู #22 ใช้ไม่ได้
4. **ห้ามวาง log ใน `StudentResource`** — มันถูกใช้กับ collection ด้วย จะได้ล็อกละแถวตอนดึงรายชื่อนักเรียนทั้งห้อง

**ตรวจกับ MySQL จริงผ่าน HTTP (เทสต์ SQLite พิสูจน์ข้อนี้ไม่ได้):** ยิง `/students/1/profile` ด้วย token เจ้าของ 3 ครั้ง
→ ได้ล็อก **1 แถว** (`user_id=1`, `new_values={"student_id":1}`) พิสูจน์ว่า `where('new_values->student_id', ...)`
ทำงานบน MySQL จริง · ยิงด้วย token ครูที่ไม่มีสิทธิ์ → **ไม่มีแถวเพิ่ม** · ลบแถวที่เกิดจากการทดสอบออกแล้ว
**หมายเหตุวิธีตรวจ:** เรียก service ตรง ๆ ใน `tinker` พิสูจน์ dedupe ไม่ได้ เพราะ `logActivity()` เอา `user_id`
มาจาก `request()->user()` ซึ่งใน tinker เป็น null → ทุกแถวมี `user_id` ว่างและ dedupe ไม่มีวันแมตช์ ต้องยิงผ่าน HTTP เท่านั้น

**เทสต์ที่ claude รันเอง:** `GuardianAuditLogTest` 7 · `GuardianSensitiveViewLogTest` 7 · รวมชุด guardian ทั้งหมด
**38 ผ่าน (84 assertions)** · `Classroom` 114 ผ่าน · `DepartmentActivityLog` 5 ผ่าน · pint ผ่าน

**claude แก้เองที่ agy ทำพลาด 1 จุด:** `GuardianAuditLogger::studentName()` อ่าน `$student->title_prefix/first_name/last_name`
ซึ่ง**ไม่มีในตาราง** (คอลัมน์จริงคือ `*_th`) → ข้อความล็อกจะลงท้ายว่า "ของนักเรียน " ว่างทุกแถว
· เปลี่ยนไปใช้ accessor `full_name_th` แล้ว fallback เป็นเลขประจำตัวนักเรียน

**งานค้างที่เจอระหว่างทาง (ยังไม่แก้):** `Master\GuardianController::update` มี `$guardianResult = ['pending' => []]`
ที่ฮาร์ดโค้ดเป็น array ว่างเสมอ ⇒ ข้อความ "ส่งคำขอแก้ไขข้อมูลผู้ปกครองรอการอนุมัติแล้ว" และ field `pending_fields`
ไม่มีทางมีค่าจริง — เป็นซากของ approval flow ที่ไม่ได้ต่อ ควรเก็บตอน G-S11

### 8.3 G-S8 — ฟิลด์อ่อนไหวของผู้ปกครอง (2026-08-25, agy 2 shard · claude ตรวจเองทุกข้อ)

**นโยบายที่เจ้าของโปรเจคตัดสิน (ต่างจากเมทริกซ์ §4 หนึ่งช่อง):** นอกจากคนที่มีคีย์ `guardians.sensitive.*` แล้ว
**นักเรียนเจ้าของโปรไฟล์** และ **ครูประจำชั้นของห้องนั้น** ยังเห็นและแก้ `citizen_id`/`monthly_income` ได้
(การ์ดใน `my-profile` และงานเยี่ยมบ้าน/บัตรนักเรียนต้องใช้) · เมทริกซ์เดิมเขียนว่า ❌ ทั้งคู่ — **ยึดตามข้อตัดสินนี้**
· เวลาไม่มีสิทธิ์ให้ **ตัดคีย์ออกจาก response ไปเลย ไม่ mask ไม่ส่ง null** เพราะฟอร์มจะบันทึกค่า mask ทับของจริง

**จุดที่ข้อมูลหลุด — สแกนเจอ 6 ทาง มากกว่าที่สเปกเดิมประเมินไว้ว่าเป็นแค่ "resource/policy":**

| จุด | สภาพก่อนแก้ | ทำอะไร |
|---|---|---|
| `Master\GuardianController::show` | ส่ง 2 ฟิลด์ตรง ๆ | ใส่คีย์เฉพาะเมื่อ `canViewSensitive()` |
| `Academy\GuardianController::store/update` response | คืนโมเดลดิบ → เลขบัตรติดไปทุกครั้ง (**นี่คือทางที่เลขบัตรหลุดตอนก่อน G-S7** เพราะ route ยังไม่มีด่าน) | `hideSensitive()` |
| `StudentResource:80` `whenLoaded('guardians')` | โยนโมเดล `StudentGuardian` ทั้งแถว · `Master\StudentController::show` ใช้ `authorize('view')` ที่ปล่อย**ครูทุกคน**ผ่าน ⇒ ครูทั่วไปอ่านเลขบัตรผู้ปกครองได้ทั้งโรงเรียน | filter ใน resource |
| `StudentProfileController` | มีด่านแต่ผูกกับ `accessLevel` ล้วน ๆ **ไม่รู้จักคีย์** ⇒ ฝ่ายทะเบียนที่เพิ่งได้สิทธิ์ถูกกันออก | `$showSensitive` + `canViewSensitive()` |
| `ClassroomController::getStudent` | คืน `$student` ดิบพร้อม relation `guardians` | `hideSensitive()` |
| `ChangeRequestController::approve` | อนุมัติแก้ฟิลด์ไหนก็ได้รวมเลขบัตร | **ไม่แก้** — คนอนุมัติคือ admin/director ที่มีสิทธิ์อยู่แล้ว (บันทึกไว้เป็นงานค้างถ้าเปิดให้บทบาทอื่นอนุมัติในอนาคต) |

**ฝั่งเขียน:** 403 **เฉพาะเมื่อค่าที่ส่งมาต่างจากของเดิมจริง** (`changedSensitiveFields()`)
— เพราะการ์ด `GuardianViewCard` ส่งทั้งฟอร์มเสมอ ถ้า reject แบบเหมารวมจะแก้ชื่อ/ความสัมพันธ์ไม่ได้เลย
· `workplace` **ย้ายกลับเข้าก้อนที่ทุกคนเห็น** — D4 นิยามฟิลด์อ่อนไหวไว้แค่ 2 ตัว มันไม่ควรถูกซ่อนตั้งแต่แรก

**ตรวจกับ API จริงที่รันอยู่ (ไม่ใช่แค่เทสต์):** token ครูที่ไม่ใช่ครูประจำชั้นยิง `/students/1/profile`
และ `/api/student/master/1` → ก้อน guardian **ไม่มี** `citizen_id`/`monthly_income` แต่ยังมี `workplace`
· token เจ้าของโรงเรียน → เห็นครบทั้ง 2 ฟิลด์
**เทสต์ที่ claude รันเอง:** `GuardianSensitiveFieldsTest` 8 · `GuardianSensitiveFieldsOnStudentSurfacesTest` 6 ·
ชุด `StudentProfile|StudentCard|HomeVisit|Guardian|MyRole` **132 ผ่าน (414 assertions)** · `Classroom` 114 ผ่าน (1 incomplete เดิม) · pint ผ่าน

**จุดที่ยังไม่มีเทสต์คลุม (ตั้งใจ):** ขา "ซ่อน" ของ `ClassroomController::getStudent` ทดสอบทางลบไม่ได้
เพราะ endpoint นั้นให้เฉพาะ owner/admin/director เข้า และทั้ง 3 บทบาทมีคีย์ `sensitive.view` จาก migration แล้ว
· ในฐานจริงตอนนี้ `academy_members.role` ที่เป็น admin/director มี **0 แถว** ⇒ กิ่งนี้เป็น defense in depth ล้วน ๆ

**ผลข้างเคียงที่ต้องรู้:** `StudentProfileController` เดิมให้ `accessLevel = 'admin'` เห็นฟิลด์อ่อนไหวโดยไม่ดูคีย์
ตอนนี้ต้องผ่าน `canViewSensitive()` — เจ้าของโรงเรียนและ role `admin`/`director` ยังผ่าน (ได้คีย์จาก migration ของ G-S7)
แต่ถ้าอนาคตมีใครตั้ง `academy_members.role = 'admin'` ให้คนที่ role ไม่มีคีย์ คนนั้นจะไม่เห็นฟิลด์อ่อนไหวอีกต่อไป

### 8.2 🔴 สิทธิ์จากฝ่ายเคยมีผลแค่ฝั่ง API — หน้าจอมองไม่เห็น (เจอตอน G-S7, แก้แล้ว)

D-S3 ทำให้ `CheckAcademyPermission` ยอมรับสิทธิ์ที่มอบให้ฝ่ายแล้ว **แต่ `GET /my-role` ส่งกลับแค่ `$role->permissions`**
ซึ่งเป็นก้อนเดียวที่ `useAcademyRole.ts` → `can()` ใช้ตัดสินว่าจะโชว์เมนู/ปุ่มไหน
⇒ สมาชิกที่ได้สิทธิ์มาจากฝ่ายจะ **เรียก API ได้แต่ไม่เห็นทางเข้าในหน้าจอ** — กระทบทุกคีย์ที่ delegable ไม่ใช่แค่ `guardians.*`
(เป็นเหตุผลว่าทำไมการมอบสิทธิ์ให้ฝ่ายที่ทำใน D-S4 ถึงยังไม่เคยเห็นผลจริงสักครั้ง)

แก้ที่ `AcademyGroupPermissionAccessService::permissionKeysFor()` + `AcademyRoleController::myRole()`
โดย intersect กับ allow-list เดิมเสมอ (แถวเก่าที่ตกค้างจึงขยายสิทธิ์ไม่ได้) และ **ไม่ลบ field เดิมสักตัว**


- **2026-07-29 — สรุปสเปก** — เสวนากับเจ้าของโปรเจคจนได้ D1–D4 + Q1–Q3, สแกนโค้ด+DB จริง (5,045 guardians / 2,931 students / 482 คนไม่มีผู้ปกครอง / ผู้ปกครองมีบัญชี 0 คน), พบ G2–G5 เป็นบั๊กจริงที่ไม่เกี่ยวกับสิทธิ์ → **ยังไม่ implement** รอ #9 ยกเว้นเฟส A
- **2026-07-29 G-S2b/G-S2c** — codex ทำ, claude ตรวจ → **พบบั๊กทำข้อมูลหายใน `guardians:merge`**: กรณีคนที่เก็บไว้ยังไม่มีความสัมพันธ์กับนักเรียนคนนั้น โค้ดหยิบ link มาแถวเดียวแล้วลบที่เหลือ ทำให้ `legacy_row_ids` + ธง primary หาย — กระทบจริง **35 candidate / 35 link** (เพราะกลุ่ม `same_citizen_diff_name` คือเคสที่นักเรียนคนเดียวมีผู้ปกครองคนเดียวกัน 2 ระเบียนอยู่แล้ว) → สั่งแก้ให้ยุบทั้งกลุ่มก่อน insert + เพิ่ม regression test + เพิ่ม invariant check ใน `guardians:verify` → verified: 4 เทสต์ผ่าน, `link_legacy_total=5045 distinct=5045`
- **2026-07-29 G-S1/G-S2** — codex ทำ, claude ตรวจ → ผ่าน (ดูตาราง §6) · claude ให้เป้าหมายผิด 2 ครั้ง (ครั้งแรกอิง collation ไทยที่มองข้ามวรรณยุกต์ ครั้งที่สองคำนวณเป้าก่อนขั้นตอนล้างข้อมูลในคำสั่งเดียวกัน) — กลไก "ตัวเลขไม่ตรง = หยุด" จับได้ทั้งสองครั้ง ไม่มีข้อมูลเสียหาย
- **2026-07-29 — เพิ่ม D6 (นิยามผู้ปกครอง)** — เจ้าของโปรเจคกำหนดนโยบาย: ระบบต้องการแค่ "บุคคลนี้เป็นผู้ปกครองของนักเรียนคนนี้" ประเภท/ความสัมพันธ์เป็นรายละเอียดฝ่ายทะเบียน (optional) และมีผู้ปกครองกี่คนก็ได้ เก็บข้อมูลเดิมครบ → **ปิด G3** (ไม่ต้องซ่อม enum แค่เปลี่ยนเป็น varchar nullable ตอนย้ายโครง), ปรับ checklist #2/#7 และ G-S4
- **2026-07-29 — เพิ่ม D5 (ทาง B)** — ตรวจคุณภาพข้อมูลพบเลขบัตรซ้ำ 696 เลข / 1,666 แถว (เกิน 970 แถว → คนจริง ~4,075 คน) เพราะโครงเดิมเป็น *1 แถว = ผู้ปกครองของนักเรียน 1 คน* → เจ้าของโปรเจคเลือก **แยกระดับบุคคล (`guardians` + `student_guardian`)** ก่อนเปิดบัญชี; ปรับเฟส A เป็นงานยกเครื่องโครงสร้างข้อมูล 7 step, เลื่อน permission/audit/FE เป็นเฟส B (G-S7–G-S11), บัญชีผู้ปกครองเป็นเฟส C (G-S12–G-S13) และเปิดคำถามค้าง O1 (วิธีสร้างบัญชี)
