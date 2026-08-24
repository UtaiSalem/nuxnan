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

### ❓ ยังไม่ได้ตัดสิน (ไม่บล็อกเฟส A/B — ไปตัดสินตอนเฟส C)
- **O1** — บัญชีผู้ปกครองจะเกิดขึ้นวิธีไหน: เชิญทาง SMS ไปเบอร์ที่มีอยู่ (4,852 คนมีเบอร์) / ให้สมัครเองแล้ว claim ด้วยเลขบัตร ปชช. (4,992 คนมีเลขบัตร) / เจ้าหน้าที่สร้างให้แล้วแจกรหัสผ่านครั้งแรก

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
| ~~**G3**~~ | ~~enum `guardian_type` มี 4 ค่า แต่ระบบใช้ 8~~ → **ยกเลิกตาม D6** ประเภทเป็น optional metadata ไม่ต้องซ่อม enum แค่เปลี่ยนเป็น varchar nullable ตอนย้ายโครง | ⬜ ปิด | — |
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
| **G-S3** | **GuardianService + สลับ read path** — ดู §6.1 (แตกเป็นก้อนย่อย) | G-S2 | service + refactor + tests | 🟡 **3/10 จุด** — `GuardianService` + `Academy/GuardianController` (index/getAllGuardians/getStatistics) + `Master/GuardianController::show` |
| **G-S4** | **สลับ write path + รวม controller** — เขียนผ่าน service เดียว, **`guardian_type`/`relationship` เป็น optional ตาม D6**, `status` ถูกต้อง (G4), **ปลด `linkUser` ที่ตอบ success ลอย ๆ** (G2) ให้คืน 501 จนกว่าจะถึงเฟส C | G-S3 | refactor + tests | 🟢 **verified 2026-07-29** — dual-write 3 ก้อน (`GuardianWriteService` · `StudentIntakeService` · `ChangeRequestController::approve`) · 54 เทสต์คุม |
| **G-S5** | **ตรวจนักเรียนที่ไม่มีผู้ปกครอง (482 คน)** — ไล่ 9 เส้นทาง (เยี่ยมบ้าน · บัตรนักเรียน · ติดต่อฉุกเฉิน · Parent Dashboard · at-risk · `StudentResource` · `StudentIntakeResource` · `ClassroomController::getStudent` · Student Profile) | — | **รายงาน — ไม่ต้องแก้อะไร** | 🟢 **verified 2026-07-29** |
| **G-S6** | **เก็บกวาด** — ถอดขาเขียนตารางเก่าออกจาก `GuardianWriteService` · ลบ `guardian_id` เก่าที่ `guardian_contacts` · drop `student_guardians` | **G-S3 ครบทุกจุด (คือหลัง G-S11)** | migration | 🚫 **ทำไม่ได้จนกว่าจุดอ่านจะย้ายครบ** (ดู §6.0) |

### 6.0 🔴 ทำไม G-S3 ที่เหลือถึงต้องรอ G-S11 (ตัดสิน 2026-07-29)

**"JSON เหมือนเดิม" เป็นไปไม่ได้โดยนิยาม** — โมเดลใหม่ให้ข้อมูล*ครบกว่า* เพราะรวมแถวของคนเดียวกันเข้าด้วยกัน

หลักฐานจริง: `ธีรศักดิ์ จันทร์แดง` (person 13513) รวมจาก legacy row 1, 121, 447 (ผู้ปกครองของนักเรียน 3 คน) และ **แต่ละแถวมีเบอร์คนละเบอร์** (`0937087566` / `0843981870` / `0894644119`) — ไม่ใช่เบอร์ซ้ำ จึงไม่ถูก dedupe
→ หน้าที่เคยเห็นเบอร์เดียวจะเห็น 3 เบอร์ · โครงก็ต่าง (`guardians[]→contacts[]` เทียบกับ `guardianLinks[]→guardian→contacts[]`)

**ผลตัดสิน: ปล่อยจุดอ่านที่กระทบหน้าจอไว้ที่ตารางเก่า แล้วย้ายพร้อมงาน frontend (G-S11)**
- ไม่มีปัญหาความถูกต้อง เพราะ **dual-write (G-S4) ทำให้ข้อมูลสองฝั่งตรงกันเสมอ**
- ไม่ทำ compatibility layer เพราะเท่ากับจงใจซ่อนข้อมูลที่มีแล้ว และเป็นโค้ดทิ้ง
- การเปลี่ยนที่ผู้ใช้เห็นควรทำตอนที่มีคนดูหน้าจอจริง

**⚠️ ผลต่อ G-S6:** drop ตารางเก่า **ทำไม่ได้** จนกว่าจุดอ่านเหล่านี้จะย้ายครบ — เป็นข้อจำกัดทางเทคนิค ไม่ใช่แค่ความระมัดระวัง

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
| **G-S8** | **ฟิลด์อ่อนไหว (D4/Q1)** — ซ่อน `citizen_id`/`monthly_income` ใน response เมื่อไม่มี `guardians.sensitive.view` และ reject การแก้เมื่อไม่มี `.manage` | G-S7 | resource/policy + tests | ⚪ |
| **G-S9** | **Audit log (Q2)** — ผูก `MemberActivityLog` ทั้ง create/update/delete/appoint + event เปิดดูฟิลด์อ่อนไหว | G-S7 | controller/service + tests | ⚪ |
| **G-S10** | **การแต่งตั้ง 3 ทาง (Q3)** — endpoint + สิทธิ์: นักเรียนแต่งตั้งเอง / ครูประจำชั้น (เฉพาะห้องตน) / ฝ่ายทะเบียน พร้อมบันทึกผู้แต่งตั้ง; ไม่บังคับว่าต้องมีผู้ปกครอง; รองรับ "ผู้ปกครองคนเดิมของพี่น้อง" โดยเลือกคนที่มีอยู่แล้วแทนการสร้างซ้ำ (ผลพลอยได้จาก D5) | G-S7, G-S4 | endpoints + tests | ⚪ |
| **G-S11** | **FE ยกเครื่อง** — เพิ่ม/แก้/ลบ ผู้ปกครอง, จัดการช่องทางติดต่อ, แสดง "ลูกในโรงเรียน" หลายคนต่อผู้ปกครอง 1 คน, การ์ดสถิติครบประเภท, error/empty state, แก้ convention (`useApi`, `definePageMeta`, dark mode) ตามสกิล `hopeui-port` | G-S7…G-S10 | pages + components | ⚪ |

### เฟส C — อนาคต (เมื่อพร้อมให้ผู้ปกครองมีบัญชี)

| Step | Title | Depends | Deliverable | Status |
|---|---|---|---|---|
| **G-S12** | ผูกบัญชีผู้ปกครอง — ตัดสิน O1 ก่อน แล้ว implement: เซ็ต `guardians.user_id` + role `parent` (1 คน = 1 บัญชี = เห็นลูกครบทุกคนผ่านตารางเชื่อม) | G-S10, O1 | flow + tests | ⚪ |
| **G-S13** | เปิด Parent Dashboard ที่มี endpoint พร้อมอยู่แล้ว | G-S12 | FE + guard | ⚪ |

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

### 8.1 G-S7 — ปิดช่องโหว่สิทธิ์ผู้ปกครอง (2026-08-25, agy 3 shard · claude ตรวจเองทุกข้อ)

**สภาพก่อนแก้ (สแกนเอง ไม่ได้เชื่อสเปกเดิม):** route ผู้ปกครองฝั่งแอดมินมีแค่ `auth:api` ไม่มีด่านสิทธิ์เลย
⇒ ผู้ใช้ที่ล็อกอินคนไหนก็ได้ (ไม่ต้องเป็นสมาชิกโรงเรียน) อ่านรายชื่อผู้ปกครองทั้งโรงเรียนพร้อมเลขบัตรประชาชน แก้ และลบได้
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
